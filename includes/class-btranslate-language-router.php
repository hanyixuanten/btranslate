<?php

defined( 'ABSPATH' ) || exit;

class BTRANSLATE_Language_Router {
	private $request_language = '';

	public function register() {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_filter( 'do_parse_request', array( $this, 'strip_language_prefix' ), 1, 3 );
		add_action( 'parse_request', array( $this, 'resolve_domain_language' ) );
		add_filter( 'home_url', array( $this, 'localize_home_url' ), 20, 4 );
	}

	public function register_rewrite_rules() {
		// Subdirectory requests reuse WordPress's existing rewrite rules after their language prefix is removed.
	}

	public function query_vars( $query_vars ) {
		$query_vars[] = 'btranslate_language';

		return $query_vars;
	}

	public function strip_language_prefix( $do_parse_request, $wp, $extra_query_vars ) {
		if ( ! $do_parse_request || ! BTRANSLATE_Settings::is_routing_mode_enabled( 'subdirectory' ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			return $do_parse_request;
		}

		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
		$query       = wp_parse_url( $request_uri, PHP_URL_QUERY );

		if ( ! is_string( $path ) ) {
			return $do_parse_request;
		}

		$home_path = $this->home_path();
		$relative  = ltrim( (string) preg_replace( '#^' . preg_quote( $home_path, '#' ) . '(?=/|$)#', '', $path, 1 ), '/' );
		$segments  = explode( '/', $relative, 2 );
		$language  = sanitize_key( $segments[0] );

		if ( ! in_array( $language, BTRANSLATE_Settings::subdirectory_languages(), true ) ) {
			return $do_parse_request;
		}

		$this->request_language = $language;
		$remaining_path         = isset( $segments[1] ) ? $segments[1] : '';
		$source_path            = '/' . ( '' !== $home_path ? trim( $home_path, '/' ) . '/' : '' ) . ltrim( $remaining_path, '/' );
		$_SERVER['REQUEST_URI'] = $source_path . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );

		return $do_parse_request;
	}

	public function resolve_domain_language( $wp ) {
		$settings = BTRANSLATE_Settings::get();
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? BTRANSLATE_Settings::normalize_domain( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
		$bindings = (array) $settings['domain_bindings'];

		if ( '' !== $this->request_language ) {
			$wp->query_vars['btranslate_language'] = $this->request_language;
		}

		if ( BTRANSLATE_Settings::is_routing_mode_enabled( 'domain' ) && '' !== $host && isset( $bindings[ $host ] ) && BTRANSLATE_Settings::is_supported_language( $bindings[ $host ] ) ) {
			if ( '' !== $this->request_language ) {
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
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? BTRANSLATE_Settings::normalize_domain( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
		$bindings = (array) BTRANSLATE_Settings::get()['domain_bindings'];

		return '' !== $host && isset( $bindings[ $host ] ) && sanitize_key( $bindings[ $host ] ) === $language;
	}

	private function source_origin_parts( $parts ) {
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? BTRANSLATE_Settings::normalize_domain( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
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
