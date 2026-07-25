<?php

defined( 'ABSPATH' ) || exit;

class WPT_Language_Router {
	public function register() {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'parse_request', array( $this, 'resolve_domain_language' ) );
		$this->register_rewrite_rules();
	}

	public function register_rewrite_rules() {
		if ( 'subdirectory' !== WPT_Settings::get()['routing_mode'] ) {
			return;
		}

		$language_pattern = implode( '|', array_map( 'preg_quote', array_map( 'strtolower', WPT_Settings::target_languages() ) ) );

		if ( '' === $language_pattern ) {
			return;
		}

		add_rewrite_tag( '%wpt_language%', '(' . $language_pattern . ')' );
		add_rewrite_rule( '^(' . $language_pattern . ')/(.*)$', 'index.php?wpt_language=$matches[1]&pagename=$matches[2]', 'top' );
		add_rewrite_rule( '^(' . $language_pattern . ')/?$', 'index.php?wpt_language=$matches[1]', 'top' );
	}

	public function query_vars( $query_vars ) {
		$query_vars[] = 'wpt_language';

		return $query_vars;
	}

	public function resolve_domain_language( $wp ) {
		$settings = WPT_Settings::get();
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? WPT_Settings::normalize_domain( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$bindings = (array) $settings['domain_bindings'];

		if ( 'domain' === $settings['routing_mode'] && '' !== $host && isset( $bindings[ $host ] ) && WPT_Settings::is_supported_language( $bindings[ $host ] ) ) {
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

	public function localized_url( $url ) {
		$settings = WPT_Settings::get();
		$language = $this->current_language();

		if ( $language === $settings['source_language'] || ! WPT_Settings::is_supported_language( $language ) ) {
			return $url;
		}

		if ( 'domain' === $settings['routing_mode'] ) {
			$domain = array_search( $language, (array) $settings['domain_bindings'], true );
			$parts  = wp_parse_url( $url );

			if ( false === $domain || ! is_array( $parts ) || empty( $parts['host'] ) ) {
				return $url;
			}

			$scheme   = isset( $parts['scheme'] ) ? $parts['scheme'] : 'https';
			$path     = isset( $parts['path'] ) ? $parts['path'] : '/';
			$query    = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
			$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

			return $scheme . '://' . $domain . $path . $query . $fragment;
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['path'] ) ) {
			return $url;
		}

		$path     = '/' . $language . '/' . ltrim( $parts['path'], '/' );
		$query    = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
		$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

		return home_url( $path ) . $query . $fragment;
	}
}
