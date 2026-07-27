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

		$response = wp_remote_get(
			$source_url,
			array(
				'timeout'     => 10,
				'redirection' => 0,
				'headers'     => array(
					'Accept' => 'application/xml, text/xml;q=0.9',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return;
		}

		$xml = $this->rewrite_sitemap( wp_remote_retrieve_body( $response ), $source_url );
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
			$value     = trim( $node->nodeValue );
			$rewritten = $this->rewrite_source_url( $value, $source_url );

			if ( $rewritten !== $value ) {
				$node->nodeValue = str_replace( $value, $rewritten, $node->nodeValue );
			}
		}

		$result = $document->saveXML();

		return false === $result ? '' : $result;
	}

	private function rewrite_source_url( $url, $source_url ) {
		$url_parts    = wp_parse_url( $url );
		$source_parts = wp_parse_url( $source_url );

		if (
			! is_array( $url_parts ) ||
			! is_array( $source_parts ) ||
			empty( $url_parts['scheme'] ) ||
			empty( $url_parts['host'] ) ||
			0 !== strcasecmp( $url_parts['host'], $source_parts['host'] ) ||
			$this->url_port( $url_parts ) !== $this->url_port( $source_parts )
		) {
			return $url;
		}

		return $this->router->localized_url( $url );
	}

	private function url_port( $parts ) {
		if ( isset( $parts['port'] ) ) {
			return (int) $parts['port'];
		}

		return isset( $parts['scheme'] ) && 'http' === strtolower( $parts['scheme'] ) ? 80 : 443;
	}
}
