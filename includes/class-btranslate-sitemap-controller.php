<?php

defined( 'ABSPATH' ) || exit;

class BTRANSLATE_Sitemap_Controller {
	private $router;

	public function __construct( BTRANSLATE_Language_Router $router ) {
		$this->router = $router;
	}

	public function register() {
		add_action( 'template_redirect', array( $this, 'maybe_serve_localized_sitemap' ), 0 );
	}

	public function maybe_serve_localized_sitemap() {
		$settings = BTRANSLATE_Settings::get();
		$language = $this->router->current_language();

		if ( $language === sanitize_key( $settings['source_language'] ) || ! in_array( $language, BTRANSLATE_Settings::target_languages(), true ) ) {
			return;
		}

		if ( ! $this->is_sitemap_request( $this->request_path(), $language, $settings['routing_mode'] ) ) {
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

	private function is_sitemap_request( $path, $language, $routing_mode ) {
		$expected_path = 'subdirectory' === $routing_mode ? '/' . $language . '/sitemap.xml' : '/sitemap.xml';

		return $expected_path === untrailingslashit( $path );
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
