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

		$request_path = $this->request_path();
		if ( ! $this->is_sitemap_path( $request_path ) ) {
			return;
		}

		$source_url = $this->source_sitemap_url( $request_path, $language, $settings['routing_mode'] );
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

		$xml = $this->rewrite_sitemap( wp_remote_retrieve_body( $response ) );
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

	private function is_sitemap_path( $path ) {
		$filename = basename( $path );

		return 1 === preg_match( '/^[a-z0-9_-]*sitemap[a-z0-9_.-]*\.xml$/i', $filename );
	}

	private function source_sitemap_url( $request_path, $language, $routing_mode ) {
		$home_url = (string) get_option( 'home', '' );
		$parts    = wp_parse_url( $home_url );

		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}

		if ( 'subdirectory' === $routing_mode ) {
			$request_path = preg_replace( '#^/' . preg_quote( $language, '#' ) . '(?=/|$)#i', '', $request_path, 1 );
			$request_path = '/' . ltrim( (string) $request_path, '/' );
		}

		$port = isset( $parts['port'] ) ? ':' . $parts['port'] : '';

		return $parts['scheme'] . '://' . $parts['host'] . $port . $request_path;
	}

	private function rewrite_sitemap( $xml ) {
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

		foreach ( $document->childNodes as $child_node ) {
			if ( XML_PI_NODE === $child_node->nodeType && 'xml-stylesheet' === $child_node->nodeName ) {
				$document->removeChild( $child_node );
			}
		}

		$xpath = new DOMXPath( $document );
		$nodes = $xpath->query( '/*[local-name()="urlset" or local-name()="sitemapindex"]/*[local-name()="url" or local-name()="sitemap"]/*[local-name()="loc"]' );

		if ( false === $nodes ) {
			return '';
		}

		foreach ( $nodes as $node ) {
			$node->nodeValue = $this->router->localized_url( trim( $node->textContent ) );
		}

		$result = $document->saveXML();

		return false === $result ? '' : $result;
	}
}