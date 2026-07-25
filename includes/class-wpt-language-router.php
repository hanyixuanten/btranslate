<?php

defined( 'ABSPATH' ) || exit;

class WPT_Language_Router {
	public function register() {
		add_rewrite_tag( '%wpt_language%', '([a-zA-Z-]+)' );
		add_rewrite_rule( '^([a-zA-Z-]+)/(.*)?$', 'index.php?wpt_language=$matches[1]&pagename=$matches[2]', 'top' );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'parse_request', array( $this, 'resolve_domain_language' ) );
	}

	public function query_vars( $query_vars ) {
		$query_vars[] = 'wpt_language';

		return $query_vars;
	}

	public function resolve_domain_language( $wp ) {
		$settings = WPT_Settings::get();
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$bindings = (array) $settings['domain_bindings'];

		if ( 'domain' === $settings['routing_mode'] && isset( $bindings[ $host ] ) && WPT_Settings::is_supported_language( $bindings[ $host ] ) ) {
			$wp->query_vars['wpt_language'] = sanitize_key( $bindings[ $host ] );
		}
	}

	public function current_language() {
		$settings = WPT_Settings::get();
		$language = get_query_var( 'wpt_language' );

		if ( $language && WPT_Settings::is_supported_language( $language ) ) {
			return sanitize_key( $language );
		}

		return sanitize_key( $settings['source_language'] );
	}
}
