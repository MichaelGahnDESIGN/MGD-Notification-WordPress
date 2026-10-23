<?php
defined( 'ABSPATH' ) || exit;

/** Kapselt Tabellenzugriff, damit Berechtigungs- und Sichtbarkeitsregeln nicht im Frontend verteilt werden. */
final class MGD_Notification_Repository {
    private string $notifications_table;
    private string $recipients_table;

    public function __construct() {
        global $wpdb;
        $this->notifications_table = $wpdb->prefix . 'mgd_notifications';
        $this->recipients_table    = $wpdb->prefix . 'mgd_notification_recipients';
    }

    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $notifications = $wpdb->prefix . 'mgd_notifications';
        $recipients = $wpdb->prefix . 'mgd_notification_recipients';
        dbDelta( "CREATE TABLE {$notifications} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            scope varchar(16) NOT NULL,
            event_type varchar(64) NOT NULL,
            source_key varchar(191) NULL,
            title text NOT NULL,
            message longtext NOT NULL,
            target_url text NULL,
            status varchar(16) NOT NULL DEFAULT 'draft',
            published_at datetime NULL,
            expires_at datetime NULL,
            created_by bigint(20) unsigned NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY source_key (source_key),
            KEY public_feed (scope, status, published_at),
            KEY expiry (expires_at)
        ) {$charset};" );
        dbDelta( "CREATE TABLE {$recipients} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            notification_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            read_at datetime NULL,
            dismissed_at datetime NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY recipient (notification_id, user_id),
            KEY inbox (user_id, read_at, dismissed_at)
        ) {$charset};" );
    }

    public function create( array $input, array $recipient_ids = array() ): int {
        global $wpdb;
        $scope = in_array( $input['scope'] ?? '', array( 'public', 'personal' ), true ) ? $input['scope'] : 'public';
        $status = in_array( $input['status'] ?? '', array( 'draft', 'scheduled', 'published', 'archived' ), true ) ? $input['status'] : 'draft';
        $now = current_time( 'mysql', true );
        $published_at = ! empty( $input['published_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $input['published_at'] ) ) : ( 'published' === $status ? $now : null );
        $data = array(
            'scope' => $scope, 'event_type' => sanitize_key( $input['event_type'] ?? 'manual' ),
            'source_key' => empty( $input['source_key'] ) ? null : sanitize_text_field( $input['source_key'] ),
            'title' => sanitize_text_field( $input['title'] ?? '' ),
            'message' => wp_kses_post( $input['message'] ?? '' ), 'target_url' => esc_url_raw( $input['target_url'] ?? '' ),
            'status' => $status, 'published_at' => $published_at,
            'expires_at' => empty( $input['expires_at'] ) ? null : gmdate( 'Y-m-d H:i:s', strtotime( $input['expires_at'] ) ),
            'created_by' => get_current_user_id() ?: null, 'created_at' => $now, 'updated_at' => $now,
        );
        $wpdb->insert( $this->notifications_table, $data );
        $id = (int) $wpdb->insert_id;
        foreach ( array_unique( array_map( 'absint', $recipient_ids ) ) as $user_id ) {
            if ( $user_id > 0 && get_user_by( 'id', $user_id ) ) {
                $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$this->recipients_table} (notification_id, user_id, created_at) VALUES (%d, %d, %s)", $id, $user_id, $now ) );
            }
        }
        return $id;
    }

    /** Ereignisse liefern einen festen Quellschlüssel; ein wiederholter Hook erzeugt keine zweite Meldung. */
    public function create_once( array $input, array $recipients = array() ): int {
        global $wpdb;
        $source_key = sanitize_text_field( $input['source_key'] ?? '' );
        if ( '' === $source_key ) {
            return $this->create( $input, $recipients );
        }
        $existing = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->notifications_table} WHERE source_key = %s", $source_key ) );
        return $existing ?: $this->create( $input, $recipients );
    }

    public function public_feed( int $limit = 8 ): array {
        global $wpdb;
        $now = current_time( 'mysql', true );
        return $wpdb->get_results( $wpdb->prepare( "SELECT id, title, message, target_url, published_at FROM {$this->notifications_table} WHERE scope = 'public' AND status = 'published' AND published_at <= %s AND (expires_at IS NULL OR expires_at > %s) ORDER BY published_at DESC LIMIT %d", $now, $now, max( 1, min( 30, $limit ) ) ), ARRAY_A );
    }

    public function personal_feed( int $user_id ): array {
        global $wpdb;
        $now = current_time( 'mysql', true );
        return $wpdb->get_results( $wpdb->prepare( "SELECT n.id, n.title, n.message, n.target_url, n.published_at, r.read_at FROM {$this->notifications_table} n INNER JOIN {$this->recipients_table} r ON r.notification_id = n.id WHERE r.user_id = %d AND r.dismissed_at IS NULL AND n.scope = 'personal' AND n.status = 'published' AND n.published_at <= %s AND (n.expires_at IS NULL OR n.expires_at > %s) ORDER BY n.published_at DESC LIMIT 30", $user_id, $now, $now ), ARRAY_A );
    }

    public function mark_read( int $notification_id, int $user_id ): bool {
        global $wpdb;
        return false !== $wpdb->update( $this->recipients_table, array( 'read_at' => current_time( 'mysql', true ) ), array( 'notification_id' => $notification_id, 'user_id' => $user_id ), array( '%s' ), array( '%d', '%d' ) );
    }
}
