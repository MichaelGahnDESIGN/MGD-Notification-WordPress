<?php
defined( 'ABSPATH' ) || exit;

/** Rendert nur eine leere, zugängliche Hülle. Die Daten kommen anschließend aus getrennten REST-Routen. */
final class MGD_Notification_Renderer {
    // Glocke schon ausgegeben? (Menü, Shortcode, Footer)
    private bool $rendered = false;

    public function __construct( private MGD_Notification_Repository $repository ) {}

    public function register(): void {
        add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
        add_shortcode( 'mgd_notification_center', array( $this, 'shortcode' ) );
        // vor wp_print_footer_scripts (Prio 20), sonst kommt das Skript zu spät
        add_action( 'wp_footer', array( $this, 'automatic_output' ), 5 );
        add_filter( 'wp_nav_menu_items', array( $this, 'menu_output' ), 10, 2 );
    }

    public function assets(): void {
        $settings = MGD_Notification_Plugin::settings();
        wp_register_style( 'mgd-notification-center', MGD_NOTIFICATION_URL . 'assets/frontend.css', array(), MGD_NOTIFICATION_VERSION );
        wp_register_script( 'mgd-notification-center', MGD_NOTIFICATION_URL . 'assets/frontend.js', array(), MGD_NOTIFICATION_VERSION, true );
        wp_localize_script( 'mgd-notification-center', 'MGDNotificationCenter', array(
            'publicUrl' => esc_url_raw( rest_url( 'mgd-notification/v1/public' ) ),
            'personalUrl' => esc_url_raw( rest_url( 'mgd-notification/v1/me' ) ),
            'nonce' => wp_create_nonce( 'wp_rest' ), 'loggedIn' => is_user_logged_in(),
            'visibleLimit' => (int) $settings['visible_limit'],
            'text' => array( 'label' => __( 'Benachrichtigungen', 'mgd-notification-center' ), 'empty' => __( 'Keine neuen Benachrichtigungen.', 'mgd-notification-center' ) ),
        ) );
    }

    public function shortcode( array|string $attributes = array() ): string {
        $attributes = shortcode_atts( array( 'placement' => 'inline' ), (array) $attributes, 'mgd_notification_center' );
        return $this->markup( sanitize_key( $attributes['placement'] ) );
    }

    public function automatic_output(): void {
        $placement = $this->placement();
        if ( str_starts_with( $placement, 'floating-' ) || 'footer' === $placement ) {
            echo $this->markup( $placement ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- intern erzeugtes Markup.
            return;
        }
        // Menü gewählt, aber kein Menü gefunden (z.B. Divi 5 Theme Builder) > schwebend unten rechts
        if ( 'menu' === $placement && ! $this->rendered ) {
            echo $this->markup( 'floating-bottom-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- intern erzeugtes Markup.
        }
    }

    /** Hängt die Glocke an das erste Menü der Seite, auch ohne Theme-Position (Divi-Menümodul). */
    public function menu_output( string $items, $args ): string {
        if ( 'menu' !== $this->placement() || $this->rendered || is_admin() ) {
            return $items;
        }
        return $items . '<li class="menu-item mgd-notification-menu-item">' . $this->markup( 'menu' ) . '</li>';
    }

    private function placement(): string {
        return (string) apply_filters( 'mgd_notification_center_auto_placement', MGD_Notification_Plugin::settings()['placement'] );
    }

    private function markup( string $placement ): string {
        $this->rendered = true;
        wp_enqueue_style( 'mgd-notification-center' );
        wp_enqueue_script( 'mgd-notification-center' );
        ob_start();
        $template = MGD_NOTIFICATION_DIR . 'templates/notification-center.php';
        include $template;
        return (string) ob_get_clean();
    }
}
