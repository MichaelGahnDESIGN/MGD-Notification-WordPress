<?php
defined( 'ABSPATH' ) || exit;

/** Zentraler Einstiegspunkt; hält Initialisierung und Migration bewusst getrennt. */
final class MGD_Notification_Plugin {
    private const SCHEMA_VERSION = '1';
    private const SCHEMA_OPTION = 'mgd_notification_schema_version';
    public static function boot(): void {
        add_action( 'plugins_loaded', array( __CLASS__, 'load' ) );
    }

    public static function activate(): void {
        self::ensure_schema();
        add_option( 'mgd_notification_settings', self::default_settings() );
    }

    public static function load(): void {
        if (!self::ensure_schema()) {
            add_action('admin_notices', array(__CLASS__, 'schema_notice'));
            return;
        }
        $repository = new MGD_Notification_Repository();
        ( new MGD_Notification_Events( $repository ) )->register();
        ( new MGD_Notification_REST( $repository ) )->register();
        ( new MGD_Notification_Renderer( $repository ) )->register();
        ( new MGD_Notification_Admin( $repository ) )->register();
    }

    /** Neue Installationen und neue Site-Präfixe erhalten ihre eigenen Tabellen. */
    public static function ensure_schema(): bool {
        if (get_option(self::SCHEMA_OPTION) === self::SCHEMA_VERSION) {
            return true;
        }
        if (!MGD_Notification_Repository::install()) {
            return false;
        }
        update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION, false);
        return true;
    }

    public static function schema_notice(): void {
        if (!current_user_can('manage_options')) { return; }
        echo '<div class="notice notice-error"><p>' . esc_html__('MGD Notification Center konnte seine Datenbanktabellen nicht vorbereiten. Bitte Datenbankrechte und Serverprotokoll prüfen.', 'mgd-notification-center') . '</p></div>';
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
