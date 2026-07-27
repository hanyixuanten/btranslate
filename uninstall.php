<?php
/**
 * Removes all data created by Btranslate.
 *
 * @package BTRANSLATE
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function btranslate_uninstall_site_data() {
	global $wpdb;

	wp_clear_scheduled_hook( 'btranslate_process_translation' );
	wp_clear_scheduled_hook( 'btranslate_process_term_translation' );
	wp_clear_scheduled_hook( 'btranslate_process_seo_output_translation' );
	delete_option( 'btranslate_settings' );
	delete_option( 'btranslate_translation_task' );
	delete_option( 'btranslate_retranslation_batch' );

	$table_name = $wpdb->prefix . 'btranslate_translations';
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
}

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		btranslate_uninstall_site_data();
		restore_current_blog();
	}
} else {
	btranslate_uninstall_site_data();
}