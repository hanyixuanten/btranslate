<?php

defined( 'ABSPATH' ) || exit;

class BTRANSLATE_Translation_Service {
	private $store;
	private $provider;

	public function __construct( BTRANSLATE_Translation_Store $store, BTRANSLATE_Translation_Provider $provider ) {
		$this->store    = $store;
		$this->provider = $provider;
	}

	public function get_or_translate( $source_value, $source_language, $target_language, $context, $force_refresh = false ) {
		$source_value       = (string) $source_value;
		$source_fingerprint = BTRANSLATE_Translation_Identity::source_fingerprint( $source_value );
		$identity_key       = BTRANSLATE_Translation_Identity::key( $source_value, $source_language, $target_language, $context, $this->provider->get_version() );
		$existing           = $this->store->find_valid( $identity_key );

		if ( ! $force_refresh && ! empty( $existing['translated_value'] ) ) {
			return BTRANSLATE_Translation_Result::success( $existing['translated_value'] );
		}

		$result = $this->provider->translate( $source_value, $source_language, $target_language, $context );

		if ( $result->success ) {
			$this->store->save( $identity_key, $source_language, $target_language, $context, $source_fingerprint, $result->value, 'complete' );
		} elseif ( empty( $existing['translated_value'] ) ) {
			$this->store->save( $identity_key, $source_language, $target_language, $context, $source_fingerprint, '', 'failed' );
		}

		return $result;
	}
}