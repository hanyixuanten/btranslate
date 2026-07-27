<?php

defined( 'ABSPATH' ) || exit;

class BTRANSLATE_Sitemap_Controller {
	private $router;

	public function __construct( BTRANSLATE_Language_Router $router ) {
		$this->router = $router;
	}

	public function register() {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'parse_request', array( $this, 'capture_request' ), 20 );
		add_action( 'template_redirect', array( $this, 'maybe_serve_localized_sitemap' ), 0 );
	}

	public function query_vars( $query_vars ) {
		$query_vars[] = 'btranslate_sitemap';

		return $query_vars;
	}

	public function capture_request( $wp ) {
		$settings = BTRANSLATE_Settings::get();
		$language = $this->requested_language( $this->request_path(), $settings );

		if ( '' === $language ) {
			return;
		}

		$wp->query_vars['btranslate_language'] = $language;
		$wp->query_vars['btranslate_sitemap']  = '1';
	}

	public function maybe_serve_localized_sitemap() {
		$settings = BTRANSLATE_Settings::get();
		$language = $this->router->current_language();

		if ( '1' !== (string) get_query_var( 'btranslate_sitemap' ) || $language === sanitize_key( $settings['source_language'] ) || ! in_array( $language, BTRANSLATE_Settings::target_languages(), true ) ) {
			return;
		}

		$source_url = $this->source_sitemap_url();
		if ( '' === $source_url ) {
			return;
		}

		$context = stream_context_create(
			array(
				'http' => array(
					'method'        => 'GET',
					'timeout'       => 10,
					'ignore_errors' => false,
					'follow_location' => 0,
					'max_redirects' => 0,
					'header'        => "Accept: application/xml, text/xml;q=0.9\r\n",
				),
			)
		);
		$source_xml = @file_get_contents( $source_url, false, $context ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.NoSilencedErrors.Discouraged

		if ( false === $source_xml ) {
			return;
		}

		$xml = $this->rewrite_sitemap( $source_xml, $source_url );
		if ( '' === $xml ) {
			return;
		}

		status_header( 200 );
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow', true );
		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Serialized XML from DOMDocument.
		exit;
	}

	private function request_path() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

		return is_string( $path ) ? '/' . ltrim( $path, '/' ) : '';
	}

	private function requested_language( $path, $settings ) {
		if ( 'subdirectory' === $settings['routing_mode'] ) {
			foreach ( BTRANSLATE_Settings::target_languages() as $language ) {
				$language = sanitize_key( $language );

				if ( '/' . $language . '/sitemap.xml' === untrailingslashit( $path ) ) {
					return $language;
				}
			}

			return '';
		}

		if ( '/sitemap.xml' !== untrailingslashit( $path ) ) {
			return '';
		}

		$host     = isset( $_SERVER['HTTP_HOST'] ) ? BTRANSLATE_Settings::normalize_domain( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$bindings = (array) $settings['domain_bindings'];
		$language = isset( $bindings[ $host ] ) ? sanitize_key( $bindings[ $host ] ) : '';

		return in_array( $language, BTRANSLATE_Settings::target_languages(), true ) ? $language : '';
	}

	private function source_sitemap_url() {
		$home_url = (string) get_option( 'home', '' );
		$parts    = wp_parse_url( $home_url );

		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}

		$port = isset( $parts['port'] ) ? ':' . $parts['port'] : '';

		return $parts['scheme'] . '://' . $parts['host'] . $port . '/sitemap.xml';
	}

	private function rewrite_sitemap( $xml, $source_url ) {
		if ( '' === trim( $xml ) || ! class_exists( 'DOMDocument' ) ) {
			return '';
		}

		$document                     = new DOMDocument();
		$document->preserveWhiteSpace = true;
		$previous_errors              = libxml_use_internal_errors( true );
		$loaded                       = $document->loadXML( $xml, LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );

		if ( ! $loaded ) {
			return '';
		}

		$xpath = new DOMXPath( $document );
		$nodes = $xpath->query( '//text() | //@*' );

		if ( false === $nodes ) {
			return '';
		}

		foreach ( $nodes as $node ) {
			$node->nodeValue = $this->rewrite_source_urls( $node->nodeValue, $source_url );
		}

		$result = $document->saveXML();

		return false === $result ? '' : $result;
	}

	private function rewrite_source_urls( $value, $source_url ) {
		$source_parts = wp_parse_url( $source_url );

		if ( ! is_array( $source_parts ) || empty( $source_parts['host'] ) ) {
			return $value;
		}

		$rewritten = preg_replace_callback(
			'#https?://[^\s<>"\']+#i',
			function ( $matches ) use ( $source_parts ) {
				$url_parts = wp_parse_url( $matches[0] );

				if ( ! is_array( $url_parts ) || empty( $url_parts['host'] ) || 0 !== strcasecmp( $url_parts['host'], $source_parts['host'] ) ) {
					return $matches[0];
				}

				return $this->router->localized_url( $matches[0] );
			},
			$value
		);

		return null === $rewritten ? $value : $rewritten;
	}
}
