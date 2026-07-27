<?php

defined( 'ABSPATH' ) || exit;

class BTRANSLATE_Translation_Identity {
	public static function source_fingerprint( $source_value ) {
		return hash( 'sha256', (string) $source_value );
	}

	public static function key( $source_value, $source_language, $target_language, $context, $provider_version ) {
		$parts = array(
			self::source_fingerprint( $source_value ),
			(string) $source_language,
			(string) $target_language,
			(string) $context,
			(string) $provider_version,
		);

		return hash( 'sha256', implode( '|', $parts ) );
	}
}
