<?php
defined( 'ABSPATH' ) || exit;

/** Backend für autorisierte Redakteure. Empfängerwahl bleibt auf WordPress-Konten begrenzt. */
final class MGD_Notification_Admin {
    public function __construct( private MGD_Notification_Repository $repository ) {}

    public function register(): void {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_post_mgd_notification_save', array( $this, 'save' ) );
        add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );
        ( new MGD_WordPress_Plugin_Catalog_V4() )->register( 'mgd-notification-center' );
    }

    /** Lädt das ruhige, eigenständige Backend-Design nur im Notification Center. */
    public function assets( string $hook ): void {
        if ( ! str_contains( $hook, 'mgd-notification' ) && ! str_contains( $hook, 'toplevel_page_mgd-notification-center' ) ) {
            return;
        }
        wp_enqueue_style( 'mgd-notification-admin', MGD_NOTIFICATION_URL . 'assets/admin.css', array(), MGD_NOTIFICATION_VERSION );
    }

    public function menu(): void {
        add_menu_page( __( 'Notification Center', 'mgd-notification-center' ), __( 'Notification Center', 'mgd-notification-center' ), 'manage_options', 'mgd-notification-center', array( $this, 'notifications_page' ), 'dashicons-bell', 58 );
        add_submenu_page( 'mgd-notification-center', __( 'Einstellungen', 'mgd-notification-center' ), __( 'Einstellungen', 'mgd-notification-center' ), 'manage_options', 'mgd-notification-settings', array( $this, 'settings_page' ) );
    }

    public function notifications_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Nicht berechtigt.', 'mgd-notification-center' ) ); }
        $edit_id = absint( $_GET['edit'] ?? 0 );
        $item = $edit_id ? $this->repository->get_for_admin( $edit_id ) : null;
        $item = $item ?: array( 'id' => 0, 'title' => '', 'message' => '', 'target_url' => '', 'scope' => 'public', 'status' => 'draft', 'published_at' => '', 'recipient_ids' => '' );
        $date_value = ! empty( $item['published_at'] ) ? gmdate( 'Y-m-d\\TH:i', strtotime( $item['published_at'] ) ) : '';
        $rows = $this->repository->list_for_admin();
        $counts = array_count_values( array_column( $rows, 'status' ) );
        ?>
        <div class="wrap mgd-notification-admin"><header class="mgd-notification-admin__hero"><div><p class="mgd-notification-admin__kicker">MGD · KOMMUNIKATION</p><h1><?php echo $item['id'] ? esc_html__( 'Meldung bearbeiten', 'mgd-notification-center' ) : esc_html__( 'Neue Meldung', 'mgd-notification-center' ); ?></h1><p>Öffentliche Hinweise und persönliche Nachrichten bleiben klar getrennt.</p></div><a class="button button-primary" href="#mgd-notification-form">Meldung erstellen</a></header>
        <section class="mgd-notification-admin__stats" aria-label="Meldungsübersicht"><div><span>Entwürfe</span><strong><?php echo (int) ( $counts['draft'] ?? 0 ); ?></strong></div><div><span>Geplant</span><strong><?php echo (int) ( $counts['scheduled'] ?? 0 ); ?></strong></div><div><span>Veröffentlicht</span><strong><?php echo (int) ( $counts['published'] ?? 0 ); ?></strong></div><div><span>Archiviert</span><strong><?php echo (int) ( $counts['archived'] ?? 0 ); ?></strong></div></section>
        <form id="mgd-notification-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'mgd_notification_save' ); ?><input type="hidden" name="action" value="mgd_notification_save"><input type="hidden" name="notification_id" value="<?php echo esc_attr( $item['id'] ); ?>">
            <table class="form-table"><tr><th><label for="mgd-title">Titel</label></th><td><input id="mgd-title" class="regular-text" name="title" value="<?php echo esc_attr( $item['title'] ); ?>" required></td></tr>
            <tr><th><label for="mgd-message">Text</label></th><td><textarea id="mgd-message" class="large-text" rows="5" name="message" required><?php echo esc_textarea( $item['message'] ); ?></textarea></td></tr>
            <tr><th><label for="mgd-url">Link</label></th><td><input id="mgd-url" class="regular-text" name="target_url" type="url" value="<?php echo esc_attr( $item['target_url'] ); ?>"></td></tr>
            <tr><th>Öffnungsverhalten</th><td><label><input type="checkbox" name="open_in_modal" value="1" <?php checked( ! empty( $item['open_in_modal'] ) ); ?>> Inhalt als Modal öffnen</label><p class="description">Zeigt den vollständigen Benachrichtigungstext als Klartext im Dialog. Ein optionaler Link bleibt dort verfügbar. Es werden keine fremden Seiten eingebettet.</p></td></tr>
            <tr><th>Bereich</th><td><fieldset><legend class="screen-reader-text">Bereich</legend><label><input type="radio" name="scope" value="public" <?php checked( $item['scope'], 'public' ); ?>> Öffentlich</label><br><label><input type="radio" name="scope" value="personal" <?php checked( $item['scope'], 'personal' ); ?>> Persönlich</label></fieldset><p class="description">Persönliche Meldungen werden ausschließlich dem gewählten WordPress-Konto gezeigt.</p></td></tr>
            <tr><th><label for="mgd-recipient">Empfänger-ID</label></th><td><input id="mgd-recipient" name="recipient_id" type="number" min="1" value="<?php echo esc_attr( $item['recipient_ids'] ); ?>" aria-describedby="mgd-recipient-help"><p id="mgd-recipient-help" class="description">Nur für persönliche Meldungen. Eine unbekannte ID wird nicht gespeichert.</p></td></tr>
            <tr><th>Status</th><td><select name="status"><?php foreach ( array( 'draft' => 'Entwurf', 'published' => 'Veröffentlicht', 'scheduled' => 'Geplant', 'archived' => 'Archiviert' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $item['status'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
            <tr><th><label for="mgd-publish-at">Veröffentlichen am</label></th><td><input id="mgd-publish-at" name="published_at" type="datetime-local" value="<?php echo esc_attr( $date_value ); ?>"></td></tr></table>
            <?php submit_button( __( 'Meldung speichern', 'mgd-notification-center' ) ); ?>
        </form><h2><?php esc_html_e( 'Vorhandene Meldungen', 'mgd-notification-center' ); ?></h2><table class="widefat striped"><thead><tr><th>Titel</th><th>Bereich</th><th>Status</th><th>Aktualisiert</th></tr></thead><tbody><?php foreach ( $rows as $row ) : ?><tr><td><a href="<?php echo esc_url( admin_url( 'admin.php?page=mgd-notification-center&edit=' . absint( $row['id'] ) ) ); ?>"><?php echo esc_html( $row['title'] ); ?></a></td><td><?php echo 'personal' === $row['scope'] ? 'Persönlich' : 'Öffentlich'; ?></td><td><span class="mgd-notification-admin__status mgd-notification-admin__status--<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( $row['status'] ); ?></span></td><td><?php echo esc_html( $row['updated_at'] ); ?></td></tr><?php endforeach; ?></tbody></table></div><?php
    }

    /** Platzierungen mit lesbaren Namen fürs Backend. */
    private const PLACEMENTS = array(
        'floating-bottom-right' => 'Schwebend unten rechts',
        'floating-bottom-left'  => 'Schwebend unten links',
        'floating-top-right'    => 'Schwebend oben rechts',
        'floating-top-left'     => 'Schwebend oben links',
        'menu'                  => 'Im Menü (sonst schwebend unten rechts)',
        'footer'                => 'Im Footer',
        'none'                  => 'Keine (nur Shortcode)',
    );

    public function settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Nicht berechtigt.', 'mgd-notification-center' ) ); }
        $settings = MGD_Notification_Plugin::settings();
        ?><div class="wrap mgd-notification-admin"><h1><?php esc_html_e( 'Notification Center Einstellungen', 'mgd-notification-center' ); ?></h1>
        <?php if ( ! empty( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Einstellungen gespeichert.', 'mgd-notification-center' ); ?></p></div><?php endif; ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'mgd_notification_save' ); ?><input type="hidden" name="action" value="mgd_notification_save"><input type="hidden" name="settings" value="1">
        <table class="form-table">
        <tr><th><label for="mgd-placement">Platzierung</label></th><td><select id="mgd-placement" name="placement"><?php foreach ( self::PLACEMENTS as $placement => $label ) : ?><option value="<?php echo esc_attr( $placement ); ?>" <?php selected( $settings['placement'], $placement ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><p class="description">Im Menü: Die Glocke kommt an das erste Menü der Seite, auch beim Menü-Modul von Divi. Gibt es kein Menü, erscheint sie unten rechts. Bei „Keine“ funktioniert weiter der Shortcode <code>[mgd_notification_center]</code>, zum Beispiel in einem Divi-Code-Modul im Header.</p></td></tr>
        <tr><th><label for="mgd-visible-limit">Anzahl im Panel</label></th><td><input id="mgd-visible-limit" name="visible_limit" type="number" min="1" max="<?php echo (int) MGD_Notification_Plugin::FEED_LIMIT; ?>" value="<?php echo (int) $settings['visible_limit']; ?>" class="small-text"><p class="description">So viele aktuelle Meldungen stehen beim Klick auf die Glocke. Alle weiteren (bis <?php echo (int) MGD_Notification_Plugin::FEED_LIMIT; ?>) zeigt „Alle anzeigen“.</p></td></tr>
        <tr><th>Icon der Glocke</th><td><fieldset class="mgd-notification-admin__icons"><legend class="screen-reader-text">Icon der Glocke</legend><?php foreach ( MGD_Notification_Icons::choices() as $icon => $label ) : ?><label class="mgd-notification-admin__icon"><input type="radio" name="icon" value="<?php echo esc_attr( $icon ); ?>" <?php checked( $settings['icon'], $icon ); ?>><span><?php echo MGD_Notification_Icons::svg( $icon, 'mgd-notification-admin__icon-svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $label ); ?></span></label><?php endforeach; ?></fieldset><p class="description">Alle Icons sind im Plugin eingebettet. Es wird nichts von außen geladen. Die Farbe passt sich dem Theme an.</p></td></tr>
        </table><?php submit_button( __( 'Einstellungen speichern', 'mgd-notification-center' ) ); ?></form></div><?php
    }

    public function save(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Nicht berechtigt.', 'mgd-notification-center' ) ); }
        check_admin_referer( 'mgd_notification_save' );
        if ( ! empty( $_POST['settings'] ) ) {
            $placement = sanitize_key( wp_unslash( $_POST['placement'] ?? '' ) );
            $limit = max( 1, min( MGD_Notification_Plugin::FEED_LIMIT, absint( $_POST['visible_limit'] ?? 5 ) ) );
            update_option( 'mgd_notification_settings', array_merge( MGD_Notification_Plugin::settings(), array(
                'placement'     => isset( self::PLACEMENTS[ $placement ] ) ? $placement : 'floating-bottom-right',
                'visible_limit' => $limit,
                'icon'          => MGD_Notification_Icons::valid( sanitize_key( wp_unslash( $_POST['icon'] ?? 'bell' ) ) ),
            ) ) );
            wp_safe_redirect( admin_url( 'admin.php?page=mgd-notification-settings&updated=1' ) ); exit;
        }
        $scope = sanitize_key( wp_unslash( $_POST['scope'] ?? 'public' ) );
        $recipient = absint( $_POST['recipient_id'] ?? 0 );
        if ( 'personal' === $scope && ( ! $recipient || ! get_user_by( 'id', $recipient ) ) ) {
            /*
             * Die Repository-Schicht blendet unbekannte Empfänger beim Ausliefern aus.
             * Diese Prüfung verhindert zusätzlich, dass eine verwaiste persönliche
             * Meldung überhaupt in der Datenbank angelegt oder gespeichert wird.
             */
            wp_die( esc_html__( 'Für eine persönliche Meldung wird ein vorhandenes WordPress-Konto benötigt.', 'mgd-notification-center' ), esc_html__( 'Empfänger prüfen', 'mgd-notification-center' ), array( 'response' => 400 ) );
        }
        $input = array(
            'open_in_modal' => isset( $_POST['open_in_modal'] ) && '1' === $_POST['open_in_modal'],
            'scope' => $scope, 'event_type' => 'manual', 'title' => wp_unslash( $_POST['title'] ?? '' ),
            'message' => wp_unslash( $_POST['message'] ?? '' ), 'target_url' => wp_unslash( $_POST['target_url'] ?? '' ),
            'status' => sanitize_key( wp_unslash( $_POST['status'] ?? 'draft' ) ), 'published_at' => wp_unslash( $_POST['published_at'] ?? '' ),
        );
        $notification_id = absint( $_POST['notification_id'] ?? 0 );
        if ( $notification_id ) {
            $this->repository->update_manual( $notification_id, $input, $recipient ? array( $recipient ) : array() );
            wp_safe_redirect( admin_url( 'admin.php?page=mgd-notification-center&updated=1' ) ); exit;
        }
        $this->repository->create( $input, $recipient ? array( $recipient ) : array() );
        wp_safe_redirect( admin_url( 'admin.php?page=mgd-notification-center&created=1' ) ); exit;
    }

    public function meta_box(): void {
        foreach ( MGD_Notification_Plugin::settings()['announce_post_types'] as $post_type ) { add_meta_box( 'mgd-notification-update', __( 'Notification Center', 'mgd-notification-center' ), array( $this, 'meta_box_html' ), $post_type, 'side' ); }
    }

    public function meta_box_html( WP_Post $post ): void {
        wp_nonce_field( 'mgd_notification_update', 'mgd_notification_update_nonce' );
        ?><label><input type="checkbox" name="mgd_notification_announce_update" value="1"> <?php esc_html_e( 'Diese Aktualisierung als Neuigkeit veröffentlichen', 'mgd-notification-center' ); ?></label><?php
    }
}
