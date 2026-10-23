<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
if ( ! get_option( 'mgd_notification_delete_data_on_uninstall', false ) ) { return; }
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Nur nach ausdrücklicher Datenlöschoption bei Deinstallation; kein Lese-Cache.
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'mgd_notification_recipients' ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Nur nach ausdrücklicher Datenlöschoption bei Deinstallation; kein Lese-Cache.
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'mgd_notifications' ) );
delete_option( 'mgd_notification_settings' );
