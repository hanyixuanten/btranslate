<?php

defined( 'ABSPATH' ) || exit;

class WPT_Content_Translator {
	private $service;

	public function __construct( WPT_Translation_Service $service ) {
		$this->service = $service;
	}

	public function translate( $content, $source_language, $target_language, $context, $force_refresh = false ) {
		$protected = self::protect( $content );
		if ( false === $protected ) {
			return WPT_Translation_Result::failure( 'content_parse_failed', 'Unable to protect links before translation.' );
		}

		$template_result = $this->service->get_or_translate( $protected['template'], $source_language, $target_language, $context . ':template', $force_refresh );

		if ( ! $template_result->success ) {
			return $template_result;
		}

		$anchors = array();

		foreach ( $protected['anchors'] as $index => $anchor ) {
			$link_result = $this->service->get_or_translate(
				$anchor['text'],
				$source_language,
				$target_language,
				$context . ':anchor:' . $index,
				$force_refresh
			);

			if ( ! $link_result->success ) {
				return $link_result;
			}

			$anchors[ $index ] = '<a' . $anchor['attributes'] . '>' . $link_result->value . '</a>';
		}

		return WPT_Translation_Result::success( self::restore( $template_result->value, $anchors, $protected['tags'] ) );
	}

	public static function protect( $content ) {
		$anchors = array();
		$template = preg_replace_callback(
			'#<a\b([^>]*)>(.*?)</a>#is',
			function ( $matches ) use ( &$anchors ) {
				$index             = count( $anchors );
				$anchors[ $index ] = array(
					'attributes' => $matches[1],
					'text'       => $matches[2],
				);

				return self::token( 'ANCHOR', $index );
			},
			$content
		);
		if ( null === $template ) {
			return false;
		}

		$tags = array();
		$template = preg_replace_callback(
			'#<[^>]+>#s',
			function ( $matches ) use ( &$tags ) {
				$index         = count( $tags );
				$tags[ $index ] = $matches[0];

				return self::token( 'TAG', $index );
			},
			$template
		);

		if ( null === $template ) {
			return false;
		}

		return array(
			'template' => $template,
			'anchors'  => $anchors,
			'tags'     => $tags,
		);
	}

	public static function restore( $content, $anchors, $tags ) {
		foreach ( $anchors as $index => $anchor ) {
			$content = preg_replace( self::token_pattern( 'ANCHOR', $index ), $anchor, $content );
		}

		foreach ( $tags as $index => $tag ) {
			$content = preg_replace( self::token_pattern( 'TAG', $index ), $tag, $content );
		}

		return $content;
	}

	private static function token( $type, $index ) {
		return 'WPTTOKEN' . $type . $index . 'END';
	}

	private static function token_pattern( $type, $index ) {
		return '#(?:\[\s*)?WPT\s*TOKEN\s*' . $type . '\s*' . $index . '\s*END(?:\s*\]|)#i';
	}
}