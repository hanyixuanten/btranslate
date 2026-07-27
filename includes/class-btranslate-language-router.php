<?php

defined( 'ABSPATH' ) || exit;

class BTRANSLATE_Language_Router {
	public function register() {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'parse_request', array( $this, 'resolve_domain_language' ) );
		add_filter( 'home_url', array( $this, 'localize_home_url' ), 20, 4 );
		$this->register_rewrite_rules();
	}

	public function register_rewrite_rules() {
		if ( 'subdirectory' !== BTRANSLATE_Settings::get()['routing_mode'] ) {
			return;
		}

		$language_pattern = implode( '|', array_map( 'preg_quote', array_map( 'strtolower', BTRANSLATE_Settings::target_languages() ) ) );

		if ( '' === $language_pattern ) {
			return;
		}

		add_rewrite_tag( '%btranslate_language%', '(' . $language_pattern . ')' );
		add_rewrite_rule( '^(' . $language_pattern . ')/sitemap\.xml$', 'index.php?btranslate_language=$matches[1]&btranslate_sitemap=1', 'top' );
		add_rewrite_rule( '^(' . $language_pattern . ')/(.*)$', 'index.php?btranslate_language=$matches[1]&pagename=$matches[2]', 'top' );
		add_rewrite_rule( '^(' . $language_pattern . ')/?$', 'index.php?btranslate_language=$matches[1]', 'top' );
	}

	public function query_vars( $query_vars ) {
		$query_vars[] = 'btranslate_language';

		return $query_vars;
	}

	public function resolve_domain_language( $wp ) {
		$settings = BTRANSLATE_Settings::get();
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? BTRANSLATE_Settings::normalize_domain( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$bindings = (array) $settings['domain_bindings'];

		if ( 'domain' === $settings['routing_mode'] && '' !== $host && isset( $bindings[ $host ] ) && BTRANSLATE_Settings::is_supported_language( $bindings[ $host ] ) ) {
			$wp->query_vars['btranslate_language'] = sanitize_key( $bindings[ $host ] );
		}
	}

	public function current_language() {
		$settings = BTRANSLATE_Settings::get();
		$language = get_query_var( 'btranslate_language' );

		if ( $language && BTRANSLATE_Settings::is_supported_language( $language ) ) {
			return sanitize_key( $language );
		}

		return sanitize_key( $settings['source_language'] );
	}

	public function localized_url( $url, $language = '' ) {
		$settings = BTRANSLATE_Settings::get();
		$language = '' === $language ? $this->current_language() : sanitize_key( $language );

		if ( $language === $settings['source_language'] || ! BTRANSLATE_Settings::is_supported_language( $language ) ) {
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

		$scheme   = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '//';
		$host     = isset( $parts['host'] ) ? $parts['host'] : '';
		$port     = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
		$path     = '/' . $language . '/' . ltrim( $parts['path'], '/' );
		$query    = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
		$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

		return $scheme . $host . $port . $path . $query . $fragment;
	}

	public function localize_home_url( $url, $path, $scheme, $blog_id ) {
		return $this->localized_url( $url );
	}
}
