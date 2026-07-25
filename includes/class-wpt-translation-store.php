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

	public function get_post_language_status( $post_id, $target_language ) {
		global $wpdb;

		$table_name = self::table_name();
		$sql        = $wpdb->prepare(
			"SELECT COUNT(*) AS translated_fields, MAX(updated_at) AS last_translated_at
			FROM {$table_name}
			WHERE target_language = %s
			AND status = 'complete'
			AND field_context LIKE %s",
			$target_language,
			'post:' . absint( $post_id ) . ':%'
		);

		return $wpdb->get_row( $sql, ARRAY_A );
	}

	public function get_completed_item_counts( $target_languages ) {
		global $wpdb;

		$target_languages = array_values( array_filter( array_map( 'sanitize_key', (array) $target_languages ) ) );
		if ( empty( $target_languages ) ) {
			return array(
				'posts' => 0,
				'terms' => 0,
			);
		}

		$table_name   = self::table_name();
		$placeholders = implode( ',', array_fill( 0, count( $target_languages ), '%s' ) );
		$post_sql     = $wpdb->prepare(
			"SELECT COUNT(DISTINCT CONCAT(target_language, '|', SUBSTRING_INDEX(field_context, ':', 2)))
			FROM {$table_name}
			WHERE status = 'complete' AND target_language IN ({$placeholders}) AND field_context LIKE 'post:%'",
			$target_languages
		);
		$term_sql     = $wpdb->prepare(
			"SELECT COUNT(DISTINCT CONCAT(target_language, '|', SUBSTRING_INDEX(field_context, ':', 2)))
			FROM {$table_name}
			WHERE status = 'complete' AND target_language IN ({$placeholders}) AND field_context LIKE 'term:%'",
			$target_languages
		);

		return array(
			'posts' => (int) $wpdb->get_var( $post_sql ),
			'terms' => (int) $wpdb->get_var( $term_sql ),
		);
	}
}