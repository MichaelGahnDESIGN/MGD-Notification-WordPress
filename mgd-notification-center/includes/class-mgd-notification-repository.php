<?php
defined( 'ABSPATH' ) || exit;

/** Kapselt Tabellenzugriff, damit Berechtigungs- und Sichtbarkeitsregeln nicht im Frontend verteilt werden. */
final class MGD_Notification_Repository {
    private string $notifications_table;
    private string $recipients_table;
    private const CACHE_GROUP = 'mgd_notification_request';
    private static array $cache_keys = array();

    /** Nur Arbeitsspeicher der laufenden Anfrage, keine persönlichen Feeds in Redis. */
    private function cache_key(string $purpose, array $args = array()): string {
        wp_cache_add_non_persistent_groups(array(self::CACHE_GROUP));
        $key = md5($this->notifications_table . $purpose . wp_json_encode($args));
        self::$cache_keys[$key] = true;
        return $key;
    }

    private function invalidate_cache(): void {
        foreach (array_keys(self::$cache_keys) as $key) {
            wp_cache_delete($key, self::CACHE_GROUP);
        }
    }

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
        $this->invalidate_cache();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Eigene Tabelle, typisierte Eingaben; Anfragecache oben verworfen.
        $wpdb->insert( $this->notifications_table, $data );
        $id = (int) $wpdb->insert_id;
        foreach ( array_unique( array_map( 'absint', $recipient_ids ) ) as $user_id ) {
            if ( $user_id > 0 && get_user_by( 'id', $user_id ) ) {
                $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO %i (notification_id, user_id, created_at) VALUES (%d, %d, %s)", $this->recipients_table, $id, $user_id, $now ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Eigene Zuordnungstabelle; Cache bereits vor der übergeordneten Schreibaktion verworfen.
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
        $key = $this->cache_key('source', array($source_key));
        $cached = wp_cache_get($key, self::CACHE_GROUP, false, $found);
        if ($found) { return $cached ?: $this->create($input, $recipients); }
        $existing = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM %i WHERE source_key = %s", $this->notifications_table, $source_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Vorbereitete Abfrage der eigenen Plugin-Tabellen.
        if (!$wpdb->last_error) { wp_cache_set($key, $existing, self::CACHE_GROUP); }
        return $existing ?: $this->create( $input, $recipients );
    }

    public function public_feed( int $limit = 8 ): array {
        global $wpdb;
        $now = current_time( 'mysql', true );
        $key = $this->cache_key('public', array($limit, $now));
        $cached = wp_cache_get($key, self::CACHE_GROUP, false, $found);
        if ($found) { return $cached; }
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT id, title, message, target_url, published_at FROM %i WHERE scope = 'public' AND status = 'published' AND published_at <= %s AND (expires_at IS NULL OR expires_at > %s) ORDER BY published_at DESC LIMIT %d", $this->notifications_table, $now, $now, max( 1, min( 30, $limit ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Vorbereitete Abfrage der eigenen Plugin-Tabellen.
        $items = is_array($items) ? $items : array();
        if (!$wpdb->last_error) { wp_cache_set($key, $items, self::CACHE_GROUP); }
        return $items;
    }

    public function personal_feed( int $user_id ): array {
        global $wpdb;
        $now = current_time( 'mysql', true );
        $key = $this->cache_key('personal', array($user_id, $now));
        $cached = wp_cache_get($key, self::CACHE_GROUP, false, $found);
        if ($found) { return $cached; }
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT n.id, n.title, n.message, n.target_url, n.published_at, r.read_at FROM %i n INNER JOIN %i r ON r.notification_id = n.id WHERE r.user_id = %d AND r.dismissed_at IS NULL AND n.scope = 'personal' AND n.status = 'published' AND n.published_at <= %s AND (n.expires_at IS NULL OR n.expires_at > %s) ORDER BY n.published_at DESC LIMIT 30", $this->notifications_table, $this->recipients_table, $user_id, $now, $now ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Vorbereitete Abfrage der eigenen Plugin-Tabellen.
        $items = is_array($items) ? $items : array();
        if (!$wpdb->last_error) { wp_cache_set($key, $items, self::CACHE_GROUP); }
        return $items;
    }

    public function mark_read( int $notification_id, int $user_id ): bool {
        global $wpdb;
        $this->invalidate_cache();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Eigene Empfängertabelle; Cache direkt davor invalidiert.
        return 0 < (int) $wpdb->update( $this->recipients_table, array( 'read_at' => current_time( 'mysql', true ) ), array( 'notification_id' => $notification_id, 'user_id' => $user_id ), array( '%s' ), array( '%d', '%d' ) );
    }

    /** Liefert nur Verwaltungsdaten; persönliche Empfänger werden nie an das öffentliche Frontend gegeben. */
    public function get_for_admin( int $notification_id ): ?array {
        global $wpdb;
        $key = $this->cache_key('admin_item', array($notification_id));
        $cached = wp_cache_get($key, self::CACHE_GROUP, false, $found);
        if ($found) { return $cached; }
        $item = $wpdb->get_row( $wpdb->prepare( "SELECT n.*, GROUP_CONCAT(r.user_id ORDER BY r.user_id) AS recipient_ids FROM %i n LEFT JOIN %i r ON r.notification_id = n.id WHERE n.id = %d GROUP BY n.id", $this->notifications_table, $this->recipients_table, $notification_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Vorbereitete Abfrage der eigenen Plugin-Tabellen.
        $item = is_array($item) ? $item : null;
        if (!$wpdb->last_error) { wp_cache_set($key, $item, self::CACHE_GROUP); }
        return is_array( $item ) ? $item : null;
    }

    public function list_for_admin(): array {
        global $wpdb;
        $key = $this->cache_key('admin_list', array());
        $cached = wp_cache_get($key, self::CACHE_GROUP, false, $found);
        if ($found) { return $cached; }
        $items = $wpdb->get_results( $wpdb->prepare( 'SELECT id, scope, title, status, published_at, updated_at FROM %i ORDER BY updated_at DESC LIMIT 100', $this->notifications_table ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Vorbereitete Abfrage der eigenen Plugin-Tabellen.
        $items = is_array($items) ? $items : array();
        if (!$wpdb->last_error) { wp_cache_set($key, $items, self::CACHE_GROUP); }
        return $items;
    }

    /** Aktualisiert eine manuelle Meldung und ersetzt deren Empfängerliste atomar innerhalb derselben Anfrage. */
    public function update_manual( int $notification_id, array $input, array $recipient_ids = array() ): bool {
        global $wpdb;
        $existing = $this->get_for_admin( $notification_id );
        if ( ! $existing ) {
            return false;
        }
        $scope = in_array( $input['scope'] ?? '', array( 'public', 'personal' ), true ) ? $input['scope'] : 'public';
        $status = in_array( $input['status'] ?? '', array( 'draft', 'scheduled', 'published', 'archived' ), true ) ? $input['status'] : 'draft';
        $published_at = ! empty( $input['published_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $input['published_at'] ) ) : ( 'published' === $status ? ( $existing['published_at'] ?: current_time( 'mysql', true ) ) : null );
        $this->invalidate_cache();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Eigene Tabelle; bisherige Feeds direkt davor invalidiert.
        $updated = $wpdb->update( $this->notifications_table, array(
            'scope' => $scope,
            'title' => sanitize_text_field( $input['title'] ?? '' ),
            'message' => wp_kses_post( $input['message'] ?? '' ),
            'target_url' => esc_url_raw( $input['target_url'] ?? '' ),
            'status' => $status,
            'published_at' => $published_at,
            'updated_at' => current_time( 'mysql', true ),
        ), array( 'id' => $notification_id ) );
        if ( false === $updated ) {
            return false;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Eigene Zuordnungstabelle; update_manual invalidiert den Anfragecache.
        $wpdb->delete( $this->recipients_table, array( 'notification_id' => $notification_id ), array( '%d' ) );
        if ( 'personal' === $scope ) {
            $now = current_time( 'mysql', true );
            foreach ( array_unique( array_map( 'absint', $recipient_ids ) ) as $user_id ) {
                if ( $user_id > 0 && get_user_by( 'id', $user_id ) ) {
                    $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO %i (notification_id, user_id, created_at) VALUES (%d, %d, %s)", $this->recipients_table, $notification_id, $user_id, $now ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Eigene Zuordnungstabelle; Cache bereits vor der übergeordneten Schreibaktion verworfen.
                }
            }
        }
        return true;
    }
}
