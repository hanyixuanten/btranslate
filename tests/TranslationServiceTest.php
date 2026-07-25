<?php

use PHPUnit\Framework\TestCase;

class WPT_Test_Translation_Store extends WPT_Translation_Store {
	public $values = array();
	public $save_calls = array();

	public function find_valid( $identity_key ) {
		return $this->values[ $identity_key ] ?? null;
	}

	public function save( $identity_key, $source_language, $target_language, $field_context, $source_fingerprint, $translated_value, $status ) {
		$this->save_calls[] = compact( 'identity_key', 'source_language', 'target_language', 'field_context', 'source_fingerprint', 'translated_value', 'status' );
		if ( 'complete' === $status ) {
			$this->values[ $identity_key ] = array( 'translated_value' => $translated_value );
		}
	}
}

class WPT_Test_Translation_Provider implements WPT_Translation_Provider {
	public $calls = 0;

	public function get_version() {
		return 'test-provider-v1';
	}

	public function translate( $source_value, $source_language, $target_language, $context ) {
		++$this->calls;

		return WPT_Translation_Result::success( '[' . $target_language . '] ' . $source_value );
	}
}

class TranslationServiceTest extends TestCase {
	public function test_second_request_reuses_persisted_translation_without_provider_call() {
		$store    = new WPT_Test_Translation_Store();
		$provider = new WPT_Test_Translation_Provider();
		$service  = new WPT_Translation_Service( $store, $provider );

		$first = $service->get_or_translate( 'Welcome', 'en', 'zh', 'post:8:post_title' );
		$second = $service->get_or_translate( 'Welcome', 'en', 'zh', 'post:8:post_title' );

		$this->assertTrue( $first->success );
		$this->assertSame( '[zh] Welcome', $second->value );
		$this->assertSame( 1, $provider->calls );
		$this->assertCount( 1, $store->save_calls );
	}

	public function test_failed_request_is_persisted_with_failed_status() {
		$store    = new WPT_Test_Translation_Store();
		$provider = new class implements WPT_Translation_Provider {
			public function get_version() {
				return 'test-provider-v1';
			}

			public function translate( $source_value, $source_language, $target_language, $context ) {
				return WPT_Translation_Result::failure( 'request_failed', 'Network error' );
			}
		};
		$service  = new WPT_Translation_Service( $store, $provider );

		$result = $service->get_or_translate( 'Welcome', 'en', 'zh', 'post:8:post_title' );

		$this->assertFalse( $result->success );
		$this->assertSame( 'failed', $store->save_calls[0]['status'] );
	}
}