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
		if ( ! BTRANSLATE_Settings::is_routing_mode_enabled( 'subdirectory' ) ) {
			return;
		}

		$language_pattern = implode( '|', array_map( 'preg_quote', array_map( 'strtolower', BTRANSLATE_Settings::subdirectory_languages() ) ) );

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

		if ( BTRANSLATE_Settings::is_routing_mode_enabled( 'domain' ) && '' !== $host && isset( $bindings[ $host ] ) && BTRANSLATE_Settings::is_supported_language( $bindings[ $host ] ) ) {
			if ( ! empty( $wp->query_vars['btranslate_language'] ) ) {
				$wp->query_vars = array( 'error' => '404' );

				return;
			}

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

		if ( BTRANSLATE_Settings::is_routing_mode_enabled( 'domain' ) && $this->request_uses_domain( $language ) ) {
			$domain = array_search( $language, (array) $settings['domain_bindings'], true );
			$parts  = wp_parse_url( $url );

			if ( false !== $domain && is_array( $parts ) && ! empty( $parts['host'] ) ) {
				$scheme   = isset( $parts['scheme'] ) ? $parts['scheme'] : 'https';
				$path     = $this->source_path( isset( $parts['path'] ) ? $parts['path'] : '/' );
				$query    = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
				$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

				return $scheme . '://' . $domain . $path . $query . $fragment;
			}
		}

		if ( ! BTRANSLATE_Settings::is_routing_mode_enabled( 'subdirectory' ) ) {
			return $url;
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['path'] ) ) {
			return $url;
		}
		$parts = $this->source_origin_parts( $parts );

		$scheme   = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '//';
		$host     = isset( $parts['host'] ) ? $parts['host'] : '';
		$port     = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
		$path     = $this->localized_path( $parts['path'], $language );
		$query    = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
		$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

		return $scheme . $host . $port . $path . $query . $fragment;
	}

	public function localize_home_url( $url, $path, $scheme, $blog_id ) {
		return $this->localized_url( $url );
	}

	private function request_uses_domain( $language ) {
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? BTRANSLATE_Settings::normalize_domain( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$bindings = (array) BTRANSLATE_Settings::get()['domain_bindings'];

		return '' !== $host && isset( $bindings[ $host ] ) && sanitize_key( $bindings[ $host ] ) === $language;
	}

	private function source_origin_parts( $parts ) {
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? BTRANSLATE_Settings::normalize_domain( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$bindings = (array) BTRANSLATE_Settings::get()['domain_bindings'];

		if ( '' === $host || ! isset( $bindings[ $host ] ) ) {
			return $parts;
		}

		$home_parts = wp_parse_url( (string) get_option( 'home', '' ) );
		if ( ! is_array( $home_parts ) || empty( $home_parts['host'] ) ) {
			return $parts;
		}

		$parts['scheme'] = isset( $home_parts['scheme'] ) ? $home_parts['scheme'] : 'https';
		$parts['host']   = $home_parts['host'];

		if ( isset( $home_parts['port'] ) ) {
			$parts['port'] = $home_parts['port'];
		} else {
			unset( $parts['port'] );
		}

		return $parts;
	}

	private function localized_path( $path, $language ) {
		$home_path = $this->home_path();
		$relative  = $this->relative_path( $path );
		$languages = array_map( 'preg_quote', BTRANSLATE_Settings::target_languages() );

		if ( ! empty( $languages ) ) {
			$relative = preg_replace( '#^(?:' . implode( '|', $languages ) . ')(?=/|$)#', $language, $relative, 1 );
		}

		if ( $language !== strtok( $relative, '/' ) ) {
			$relative = $language . '/' . $relative;
		}

		return '/' . ( '' !== $home_path ? trim( $home_path, '/' ) . '/' : '' ) . ltrim( $relative, '/' );
	}

	private function source_path( $path ) {
		$home_path = $this->home_path();
		$relative  = $this->relative_path( $path );
		$languages = array_map( 'preg_quote', BTRANSLATE_Settings::target_languages() );

		if ( ! empty( $languages ) ) {
			$relative = preg_replace( '#^(?:' . implode( '|', $languages ) . ')(?=/|$)/?#', '', $relative, 1 );
		}

		return '/' . ( '' !== $home_path ? trim( $home_path, '/' ) . '/' : '' ) . ltrim( $relative, '/' );
	}

	private function relative_path( $path ) {
		return ltrim( (string) preg_replace( '#^' . preg_quote( $this->home_path(), '#' ) . '(?=/|$)#', '', $path, 1 ), '/' );
	}

	private function home_path() {
		$home_path = wp_parse_url( (string) get_option( 'home', '' ), PHP_URL_PATH );

		return is_string( $home_path ) ? untrailingslashit( $home_path ) : '';
	}
}
