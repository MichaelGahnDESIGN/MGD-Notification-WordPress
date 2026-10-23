<?php
defined( 'ABSPATH' ) || exit;

/** Zentraler Einstiegspunkt; hält Initialisierung und Migration bewusst getrennt. */
final class MGD_Notification_Plugin {
    public static function boot(): void {
        add_action( 'plugins_loaded', array( __CLASS__, 'load' ) );
    }

    public static function activate(): void {
        MGD_Notification_Repository::install();
        add_option( 'mgd_notification_settings', self::default_settings() );
    }

    public static function load(): void {
        $repository = new MGD_Notification_Repository();
        ( new MGD_Notification_Events( $repository ) )->register();
        ( new MGD_Notification_REST( $repository ) )->register();
        ( new MGD_Notification_Renderer( $repository ) )->register();
        ( new MGD_Notification_Admin( $repository ) )->register();
    }

    public static function default_settings(): array {
        return array(
            'placement'           => 'floating-bottom-right',
            'announce_post_types' => array( 'post', 'page', 'product' ),
            'public_limit'        => 8,
        );
    }

    public static function settings(): array {
        return wp_parse_args( get_option( 'mgd_notification_settings', array() ), self::default_settings() );
    }
}
