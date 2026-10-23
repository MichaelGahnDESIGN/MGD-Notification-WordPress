<?php
defined( 'ABSPATH' ) || exit;

/** Backend für autorisierte Redakteure. Empfängerwahl bleibt auf WordPress-Konten begrenzt. */
final class MGD_Notification_Admin {
    public function __construct( private MGD_Notification_Repository $repository ) {}

    public function register(): void {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_post_mgd_notification_save', array( $this, 'save' ) );
        add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );
    }

    public function menu(): void {
        add_menu_page( __( 'Notification Center', 'mgd-notification-center' ), __( 'Notification Center', 'mgd-notification-center' ), 'manage_options', 'mgd-notification-center', array( $this, 'notifications_page' ), 'dashicons-bell', 58 );
        add_submenu_page( 'mgd-notification-center', __( 'Einstellungen', 'mgd-notification-center' ), __( 'Einstellungen', 'mgd-notification-center' ), 'manage_options', 'mgd-notification-settings', array( $this, 'settings_page' ) );
    }

    public function notifications_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Nicht berechtigt.', 'mgd-notification-center' ) ); }
        ?>
        <div class="wrap"><h1><?php esc_html_e( 'Neue Meldung', 'mgd-notification-center' ); ?></h1>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'mgd_notification_save' ); ?><input type="hidden" name="action" value="mgd_notification_save">
            <table class="form-table"><tr><th><label for="mgd-title">Titel</label></th><td><input id="mgd-title" class="regular-text" name="title" required></td></tr>
            <tr><th><label for="mgd-message">Text</label></th><td><textarea id="mgd-message" class="large-text" rows="5" name="message" required></textarea></td></tr>
            <tr><th><label for="mgd-url">Link</label></th><td><input id="mgd-url" class="regular-text" name="target_url" type="url"></td></tr>
            <tr><th>Bereich</th><td><label><input type="radio" name="scope" value="public" checked> Öffentlich</label><br><label><input type="radio" name="scope" value="personal"> Persönlich</label><p class="description">Für persönliche Meldungen ist eine WordPress-Benutzer-ID erforderlich.</p></td></tr>
            <tr><th><label for="mgd-recipient">Empfänger-ID</label></th><td><input id="mgd-recipient" name="recipient_id" type="number" min="1"></td></tr>
            <tr><th>Status</th><td><select name="status"><option value="draft">Entwurf</option><option value="published">Veröffentlicht</option><option value="scheduled">Geplant</option></select></td></tr>
            <tr><th><label for="mgd-publish-at">Veröffentlichen am</label></th><td><input id="mgd-publish-at" name="published_at" type="datetime-local"></td></tr></table>
            <?php submit_button( __( 'Meldung speichern', 'mgd-notification-center' ) ); ?>
        </form></div><?php
    }

    public function settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Nicht berechtigt.', 'mgd-notification-center' ) ); }
        $settings = MGD_Notification_Plugin::settings();
        ?><div class="wrap"><h1><?php esc_html_e( 'Notification Center Einstellungen', 'mgd-notification-center' ); ?></h1>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'mgd_notification_save' ); ?><input type="hidden" name="action" value="mgd_notification_save"><input type="hidden" name="settings" value="1">
        <table class="form-table"><tr><th><label for="mgd-placement">Platzierung</label></th><td><select id="mgd-placement" name="placement"><?php foreach ( array( 'floating-top-left', 'floating-top-right', 'floating-bottom-left', 'floating-bottom-right', 'menu', 'footer', 'none' ) as $placement ) : ?><option value="<?php echo esc_attr( $placement ); ?>" <?php selected( $settings['placement'], $placement ); ?>><?php echo esc_html( $placement ); ?></option><?php endforeach; ?></select><p class="description">„none“ deaktiviert die automatische Platzierung; der Shortcode bleibt verfügbar.</p></td></tr></table><?php submit_button( __( 'Einstellungen speichern', 'mgd-notification-center' ) ); ?></form></div><?php
    }

    public function save(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Nicht berechtigt.', 'mgd-notification-center' ) ); }
        check_admin_referer( 'mgd_notification_save' );
        if ( ! empty( $_POST['settings'] ) ) {
            $allowed = array( 'floating-top-left', 'floating-top-right', 'floating-bottom-left', 'floating-bottom-right', 'menu', 'footer', 'none' );
            $placement = sanitize_key( wp_unslash( $_POST['placement'] ?? '' ) );
            update_option( 'mgd_notification_settings', array_merge( MGD_Notification_Plugin::settings(), array( 'placement' => in_array( $placement, $allowed, true ) ? $placement : 'floating-bottom-right' ) ) );
            wp_safe_redirect( admin_url( 'admin.php?page=mgd-notification-settings&updated=1' ) ); exit;
        }
        $scope = sanitize_key( wp_unslash( $_POST['scope'] ?? 'public' ) );
        $recipient = absint( $_POST['recipient_id'] ?? 0 );
        if ( 'personal' === $scope && ! $recipient ) { wp_die( esc_html__( 'Persönliche Meldungen benötigen einen Empfänger.', 'mgd-notification-center' ) ); }
        $this->repository->create( array(
            'scope' => $scope, 'event_type' => 'manual', 'title' => wp_unslash( $_POST['title'] ?? '' ),
            'message' => wp_unslash( $_POST['message'] ?? '' ), 'target_url' => wp_unslash( $_POST['target_url'] ?? '' ),
            'status' => sanitize_key( wp_unslash( $_POST['status'] ?? 'draft' ) ), 'published_at' => wp_unslash( $_POST['published_at'] ?? '' ),
        ), $recipient ? array( $recipient ) : array() );
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
