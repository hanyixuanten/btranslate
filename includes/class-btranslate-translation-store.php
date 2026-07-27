<?php

defined( 'ABSPATH' ) || exit;

class BTRANSLATE_Translation_Store {
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'btranslate_translations';
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

	public function clear() {
		global $wpdb;

		return $wpdb->query( 'DELETE FROM ' . self::table_name() );
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

	public function get_completed_item_counts( $target_languages, $post_ids, $term_ids, $since = '' ) {
		global $wpdb;

		$target_languages = array_values( array_filter( array_map( 'sanitize_key', (array) $target_languages ) ) );
		$post_ids         = array_values( array_filter( array_map( 'absint', (array) $post_ids ) ) );
		$term_ids         = array_values( array_filter( array_map( 'absint', (array) $term_ids ) ) );
		if ( empty( $target_languages ) ) {
			return array(
				'posts' => 0,
				'terms' => 0,
			);
		}

		$table_name           = self::table_name();
		$language_placeholders = implode( ',', array_fill( 0, count( $target_languages ), '%s' ) );
		$since_clause          = '' === $since ? '' : ' AND updated_at >= %s';
		$post_sql              = $this->get_completed_item_count_sql( 'post', $post_ids, $target_languages, $language_placeholders, $since, $since_clause, $table_name );
		$term_sql              = $this->get_completed_item_count_sql( 'term', $term_ids, $target_languages, $language_placeholders, $since, $since_clause, $table_name );

		return array(
			'posts' => (int) $wpdb->get_var( $post_sql ),
			'terms' => (int) $wpdb->get_var( $term_sql ),
		);
	}

	private function get_completed_item_count_sql( $item_type, $item_ids, $target_languages, $language_placeholders, $since, $since_clause, $table_name ) {
		global $wpdb;

		if ( empty( $item_ids ) ) {
			return 'SELECT 0';
		}

		$contexts             = array_map(
			static function ( $item_id ) use ( $item_type ) {
				return $item_type . ':' . $item_id;
			},
			$item_ids
		);
		$context_placeholders = implode( ',', array_fill( 0, count( $contexts ), '%s' ) );
		$query_args           = array_merge( $target_languages, $contexts );
		if ( '' !== $since ) {
			$query_args[] = $since;
		}

		return $wpdb->prepare(
			"SELECT COUNT(DISTINCT CONCAT(target_language, '|', SUBSTRING_INDEX(field_context, ':', 2)))
			FROM {$table_name}
			WHERE status = 'complete'
			AND target_language IN ({$language_placeholders})
			AND SUBSTRING_INDEX(field_context, ':', 2) IN ({$context_placeholders}){$since_clause}",
			$query_args
		);
	}
}