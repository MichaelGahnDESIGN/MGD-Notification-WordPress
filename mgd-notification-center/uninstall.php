<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Abmelden bei theme.michael-gahn.de + Baukasten-Daten weg, immer (auch ohne Daten löschen)
spl_autoload_register( static function ( string $class ): void {
    $prefix = 'Mgd\\NotificationCenter\\Kit\\';
    if ( str_starts_with( $class, $prefix ) ) {
        $file = __DIR__ . '/includes/Kit/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
        if ( is_file( $file ) ) {
            require $file;
        }
    }
} );
try {
    \Mgd\NotificationCenter\Kit\Platform\WordPress\Setup::uninstall( 'mgd-notification-center-wordpress', 'wordpress' );
} catch ( \Throwable $e ) {
    // egal, Deinstallieren läuft weiter
}
if ( ! get_option( 'mgd_notification_delete_data_on_uninstall', false ) ) { return; }
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mgd_notification_recipients" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mgd_notifications" );
delete_option( 'mgd_notification_settings' );

// Nur beim bereits ausdrücklich aktivierten vollständigen Datenlöschen entfernen.
foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'mgd_notification_modal_' ) . '%' ) ) as $option_name ) { delete_option( $option_name ); }
