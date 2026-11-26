<?php
/**
 * Plugin Name: MGD Notification Center
 * Plugin URI: https://theme.michael-gahn.de/plugins/plugin-notification-wordpress
 * Description: Öffentliche und persönliche Benachrichtigungen ohne externe Dienste.
 * Version: 2.1.4
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Michael Gahn DESIGN
 * Author URI: https://michael-gahn.de
 * Text Domain: mgd-notification-center
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress
 */

defined( 'ABSPATH' ) || exit;

define( 'MGD_NOTIFICATION_VERSION', '2.1.4' );
define( 'MGD_NOTIFICATION_FILE', __FILE__ );
define( 'MGD_NOTIFICATION_DIR', plugin_dir_path( __FILE__ ) );
define( 'MGD_NOTIFICATION_URL', plugin_dir_url( __FILE__ ) );

require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-icons.php';
require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-repository.php';
require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-events.php';
require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-rest.php';
require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-renderer.php';
require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-admin.php';
require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-plugin.php';

register_activation_hook( __FILE__, array( 'MGD_Notification_Plugin', 'activate' ) );

MGD_Notification_Plugin::boot();

// Baukasten aus MichaThemeV5-Pro > includes/Kit (bin/build-plugin-kit.php)
spl_autoload_register( static function ( string $class ): void {
    $prefix = 'Mgd\\NotificationCenter\\Kit\\';
    if ( str_starts_with( $class, $prefix ) ) {
        $file = MGD_NOTIFICATION_DIR . 'includes/Kit/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
        if ( is_file( $file ) ) {
            require $file;
        }
    }
} );
define( 'MGD_NOTIFICATION_PRODUCT', 'mgd-notification-center-wordpress' );

// Updater vom Baukasten, startet auch den Install-Melder
$mgd_nc_updater = \Mgd\NotificationCenter\Kit\Platform\WordPress\Setup::updater( __FILE__, 'mgd-notification-center-wordpress', 'wordpress', MGD_NOTIFICATION_VERSION, 'MGD Notification Center', \Mgd\NotificationCenter\Kit\Platform\WordPress\Tools::url() );
MGD_Notification_Plugin::$updater = $mgd_nc_updater;
// MGD Tools - eigenes Menü ist raus, Seiten-Kennung bleibt
\Mgd\NotificationCenter\Kit\Platform\WordPress\Setup::tools( __FILE__, 'mgd-notification-center-wordpress', 'wordpress', MGD_NOTIFICATION_VERSION, 'MGD Notification Center', $mgd_nc_updater, array(
    'slug'     => 'plugin-notification-wordpress',
    'repo'     => 'MGD-Notification-WordPress',
    'settings' => array( 'page' => 'mgd-notification-center', 'title' => 'Notification Center', 'cap' => 'manage_options', 'render' => static function (): void { ( new MGD_Notification_Admin( new MGD_Notification_Repository() ) )->page(); } ),
    'license'  => false,
) );
// Reste vom alten Updater wegräumen, 1x pro Version
\Mgd\NotificationCenter\Kit\Platform\WordPress\Setup::cleanupLegacy( MGD_NOTIFICATION_PRODUCT, MGD_NOTIFICATION_VERSION, array( 'mgd_admin_release_*' ) );
