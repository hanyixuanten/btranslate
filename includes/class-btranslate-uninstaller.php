<?php

defined( 'ABSPATH' ) || exit;

class BTRANSLATE_Uninstaller {
	private const OPTION_NAMES = array(
		'btranslate_settings',
		'btranslate_translation_task',
		'btranslate_retranslation_batch',
	);

	private const CRON_HOOKS = array(
		'btranslate_process_translation',
		'btranslate_process_term_translation',
		'btranslate_process_seo_output_translation',
	);

	public static function uninstall() {
		if ( is_multisite() ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::delete_site_data();
				restore_current_blog();
			}
		} else {
			self::delete_site_data();
		}

		foreach ( self::OPTION_NAMES as $option_name ) {
			delete_site_option( $option_name );
		}
	}

	private static function delete_site_data() {
		global $wpdb;

		foreach ( self::CRON_HOOKS as $hook_name ) {
			wp_clear_scheduled_hook( $hook_name );
		}

		foreach ( self::OPTION_NAMES as $option_name ) {
			delete_option( $option_name );
		}

		$table_name = $wpdb->prefix . 'btranslate_translations';
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}
}