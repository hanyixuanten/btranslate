<?php

defined( 'ABSPATH' ) || exit;

class WPT_Translation_Store {
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'wpt_translations';
	}

	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			identity_key char(64) NOT NULL,
			source_language varchar(20) NOT NULL,
			target_language varchar(20) NOT NULL,
			field_context varchar(191) NOT NULL,
			source_fingerprint char(64) NOT NULL,
			translated_value longtext NULL,
			status varchar(20) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY identity_key (identity_key),
			KEY target_language (target_language),
			KEY source_fingerprint (source_fingerprint)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	public function find_valid( $identity_key ) {
		global $wpdb;

		$table_name = self::table_name();
		$sql        = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE identity_key = %s AND status = 'complete' LIMIT 1",
			$identity_key
		);

		return $wpdb->get_row( $sql, ARRAY_A );
	}

	public function save( $identity_key, $source_language, $target_language, $field_context, $source_fingerprint, $translated_value, $status ) {
		global $wpdb;

		$now = current_time( 'mysql', true );

		return $wpdb->replace(
			self::table_name(),
			array(
				'identity_key'       => $identity_key,
				'source_language'    => $source_language,
				'target_language'    => $target_language,
				'field_context'      => $field_context,
				'source_fingerprint' => $source_fingerprint,
				'translated_value'   => $translated_value,
				'status'             => $status,
				'created_at'         => $now,
				'updated_at'         => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}
}