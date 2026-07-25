<?php

defined( 'ABSPATH' ) || exit;

class WPT_Content_Translator {
	private $service;

	public function __construct( WPT_Translation_Service $service ) {
		$this->service = $service;
	}

	public function translate( $content, $source_language, $target_language, $context, $force_refresh = false ) {
		$links = array();
		$template = preg_replace_callback(
			'#<a\b([^>]*)>(.*?)</a>#is',
			function ( $matches ) use ( &$links ) {
				$index          = count( $links );
				$links[ $index ] = array(
					'attributes' => $matches[1],
					'text'       => $matches[2],
				);

				return '[[WPT_LINK_' . $index . ']]';
			},
			$content
		);

		if ( null === $template ) {
			return WPT_Translation_Result::failure( 'content_parse_failed', 'Unable to protect links before translation.' );
		}

		$template_context = empty( $links ) ? $context : $context . ':template';
		$template_result  = $this->service->get_or_translate( $template, $source_language, $target_language, $template_context, $force_refresh );

		if ( ! $template_result->success || empty( $links ) ) {
			return $template_result;
		}

		$translated_content = $template_result->value;

		foreach ( $links as $index => $link ) {
			$link_result = $this->service->get_or_translate(
				$link['text'],
				$source_language,
				$target_language,
				$context . ':link:' . $index,
				$force_refresh
			);

			if ( ! $link_result->success ) {
				return $link_result;
			}

			$anchor = '<a' . $link['attributes'] . '>' . $link_result->value . '</a>';
			$translated_content = str_replace( '[[WPT_LINK_' . $index . ']]', $anchor, $translated_content );
		}

		return WPT_Translation_Result::success( $translated_content );
	}
}