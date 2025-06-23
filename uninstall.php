<?php
// If this file is called directly, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Optionally, check for a custom flag to confirm user intent (set via admin UI)
$option_flag = get_option( 'g2wpi_confirmed_uninstall', false );
if ( ! $option_flag ) {
	// Do not delete anything if not confirmed
	return;
}

// Delete plugin options
// Replace 'g2wpi_options' with your actual option names
delete_option( 'g2wpi_settings' );
delete_option( 'g2wpi_confirmed_uninstall' );
delete_option( 'g2wpi_tokens' );

// Delete other plugin data (custom tables, etc.) as needed
global $wpdb;
$table_name = $wpdb->prefix . 'google_docs_importados';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// Delete transients relacionados
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_g2wpi_%' OR option_name LIKE '_transient_timeout_g2wpi_%'" );

// Si existiera una tabla duplicada, también eliminarla
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}google_docs_imported" );

// Add more cleanup as needed
