<?php

defined( 'ABSPATH' ) || exit;

interface BTRANSLATE_Translation_Provider {
	public function get_version();

	public function translate( $source_value, $source_language, $target_language, $context );
}
