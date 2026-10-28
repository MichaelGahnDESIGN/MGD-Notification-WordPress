<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
if ( ! get_option( 'mgd_notification_delete_data_on_uninstall', false ) ) { return; }
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mgd_notification_recipients" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mgd_notifications" );
delete_option( 'mgd_notification_settings' );

// Nur beim bereits ausdrücklich aktivierten vollständigen Datenlöschen entfernen.
foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'mgd_notification_modal_' ) . '%' ) ) as $option_name ) { delete_option( $option_name ); }
