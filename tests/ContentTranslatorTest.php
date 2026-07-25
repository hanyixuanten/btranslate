<?php

use PHPUnit\Framework\TestCase;

class ContentTranslatorTest extends TestCase {
	public function test_translates_link_text_separately_and_preserves_link_attributes() {
		$store      = new WPT_Test_Translation_Store();
		$provider   = new WPT_Test_Translation_Provider();
		$service    = new WPT_Translation_Service( $store, $provider );
		$translator = new WPT_Content_Translator( $service );

		$result = $translator->translate( 'Read <a href="https://example.com/docs" target="_blank">our guide</a>.', 'en', 'zh', 'post:1:post_content' );

		$this->assertTrue( $result->success );
		$this->assertSame( '[zh] Read <a href="https://example.com/docs" target="_blank">[zh] our guide</a>.', $result->value );
		$this->assertSame( 2, $provider->calls );
		$this->assertSame( 'post:1:post_content:template', $store->save_calls[0]['field_context'] );
		$this->assertSame( 'post:1:post_content:anchor:0', $store->save_calls[1]['field_context'] );
	}

	public function test_restores_tokens_when_the_provider_wraps_them_in_brackets() {
		$store    = new WPT_Test_Translation_Store();
		$provider = new class implements WPT_Translation_Provider {
			public function get_version() {
				return 'test-provider-v1';
			}

			public function translate( $source_value, $source_language, $target_language, $context ) {
				$value = preg_replace( '/(WPTTOKEN[A-Z]+\d+END)/', '[$1]', $source_value );

				return WPT_Translation_Result::success( $value );
			}
		};
		$translator = new WPT_Content_Translator( new WPT_Translation_Service( $store, $provider ) );

		$result = $translator->translate( 'See <a href="/docs">docs</a><img src="/hero.jpg" alt="Hero">.', 'en', 'zh', 'post:1:post_content' );

		$this->assertTrue( $result->success );
		$this->assertSame( 'See <a href="/docs">docs</a><img src="/hero.jpg" alt="Hero">.', $result->value );
	}
}