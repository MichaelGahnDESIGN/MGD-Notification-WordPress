<?php
defined( 'ABSPATH' ) || exit;

/** Rendert nur eine leere, zugängliche Hülle. Die Daten kommen anschließend aus getrennten REST-Routen. */
final class MGD_Notification_Renderer {
    public function __construct( private MGD_Notification_Repository $repository ) {}

    public function register(): void {
        add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
        add_shortcode( 'mgd_notification_center', array( $this, 'shortcode' ) );
        add_action( 'wp_footer', array( $this, 'automatic_output' ), 20 );
        add_filter( 'wp_nav_menu_items', array( $this, 'menu_output' ), 10, 2 );
    }

    public function assets(): void {
        wp_register_style( 'mgd-notification-center', MGD_NOTIFICATION_URL . 'assets/frontend.css', array(), MGD_NOTIFICATION_VERSION );
        wp_register_script( 'mgd-notification-center', MGD_NOTIFICATION_URL . 'assets/frontend.js', array(), MGD_NOTIFICATION_VERSION, true );
        wp_localize_script( 'mgd-notification-center', 'MGDNotificationCenter', array(
            'publicUrl' => esc_url_raw( rest_url( 'mgd-notification/v1/public' ) ),
            'personalUrl' => esc_url_raw( rest_url( 'mgd-notification/v1/me' ) ),
            'nonce' => wp_create_nonce( 'wp_rest' ), 'loggedIn' => is_user_logged_in(),
            'text' => array( 'label' => __( 'Benachrichtigungen', 'mgd-notification-center' ), 'empty' => __( 'Keine neuen Benachrichtigungen.', 'mgd-notification-center' ) ),
        ) );
    }

    public function shortcode( array $attributes = array() ): string {
        $attributes = shortcode_atts( array( 'placement' => 'inline' ), $attributes, 'mgd_notification_center' );
        return $this->markup( sanitize_key( $attributes['placement'] ) );
    }

    public function automatic_output(): void {
        $placement = apply_filters( 'mgd_notification_center_auto_placement', MGD_Notification_Plugin::settings()['placement'] );
        if ( str_starts_with( $placement, 'floating-' ) || 'footer' === $placement ) {
            echo $this->markup( $placement ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- intern erzeugtes Markup.
        }
    }

    public function menu_output( string $items, stdClass $args ): string {
        if ( 'menu' !== apply_filters( 'mgd_notification_center_auto_placement', MGD_Notification_Plugin::settings()['placement'] ) || empty( $args->theme_location ) ) {
            return $items;
        }
        return $items . '<li class="menu-item mgd-notification-menu-item">' . $this->markup( 'menu' ) . '</li>';
    }

    private function markup( string $placement ): string {
        wp_enqueue_style( 'mgd-notification-center' );
        wp_enqueue_script( 'mgd-notification-center' );
        ob_start();
        $template = MGD_NOTIFICATION_DIR . 'templates/notification-center.php';
        include $template;
        return (string) ob_get_clean();
    }
}
