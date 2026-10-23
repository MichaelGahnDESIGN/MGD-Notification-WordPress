<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
if ( ! get_option( 'mgd_notification_delete_data_on_uninstall', false ) ) { return; }
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mgd_notification_recipients" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mgd_notifications" );
delete_option( 'mgd_notification_settings' );
