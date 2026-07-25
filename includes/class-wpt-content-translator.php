<?php

defined( 'ABSPATH' ) || exit;

class WPT_Content_Translator {
	private $service;

	public function __construct( WPT_Translation_Service $service ) {
		$this->service = $service;
	}

	public function translate( $content, $source_language, $target_language, $context, $force_refresh = false ) {
		$segments = self::segments( $content );
		if ( false === $segments ) {
			return WPT_Translation_Result::failure( 'content_parse_failed', 'Unable to protect links before translation.' );
		}

		$text_index = 0;
		foreach ( $segments as $segment ) {
			if ( self::is_tag( $segment ) || '' === trim( $segment ) ) {
				continue;
			}

			$result = $this->service->get_or_translate( trim( $segment ), $source_language, $target_language, $context . ':text:' . $text_index, $force_refresh );
			if ( ! $result->success ) {
				return $result;
			}

			++$text_index;
		}

		return WPT_Translation_Result::success( $content );
	}

	public static function segments( $content ) {
		$segments = preg_split( '/(<[^>]+>)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE );

		if ( false === $segments ) {
			return false;
		}

		return array_values( array_filter( $segments, static function ( $segment ) {
			return '' !== $segment;
		} ) );
	}

	public static function is_tag( $segment ) {
		return 1 === preg_match( '/^<[^>]+>$/s', $segment );
	}

	public static function surrounding_whitespace( $segment ) {
		preg_match( '/^(\s*)(.*?)(\s*)$/s', $segment, $matches );

		return array(
			'leading' => $matches[1],
			'text'    => $matches[2],
			'trailing' => $matches[3],
		);
	}
}