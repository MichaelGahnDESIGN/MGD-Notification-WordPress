<?php
/**
 * Plugin Name: MGD Notification Center
 * Plugin URI: https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress
 * Description: Öffentliche und persönliche Benachrichtigungen ohne externe Dienste.
 * Version: 0.1.0
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

define( 'MGD_NOTIFICATION_VERSION', '0.1.0' );
define( 'MGD_NOTIFICATION_FILE', __FILE__ );
define( 'MGD_NOTIFICATION_DIR', plugin_dir_path( __FILE__ ) );
define( 'MGD_NOTIFICATION_URL', plugin_dir_url( __FILE__ ) );

require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-repository.php';
require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-events.php';
require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-rest.php';
require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-renderer.php';
require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-admin.php';
require_once MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-plugin.php';

register_activation_hook( __FILE__, array( 'MGD_Notification_Plugin', 'activate' ) );

MGD_Notification_Plugin::boot();
