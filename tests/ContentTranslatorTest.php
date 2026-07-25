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
		$this->assertSame( 'Read <a href="https://example.com/docs" target="_blank">our guide</a>.', $result->value );
		$this->assertSame( 3, $provider->calls );
		$this->assertSame( 'post:1:post_content:text:0', $store->save_calls[0]['field_context'] );
		$this->assertSame( 'post:1:post_content:text:1', $store->save_calls[1]['field_context'] );
		$this->assertSame( 'post:1:post_content:text:2', $store->save_calls[2]['field_context'] );
	}

	public function test_splits_text_while_preserving_links_images_and_other_tags() {
		$segments = WPT_Content_Translator::segments( 'See <a href="/docs">docs</a><img src="/hero.jpg" alt="Hero"><strong>now</strong>.' );

		$this->assertSame(
			array( 'See ', '<a href="/docs">', 'docs', '</a>', '<img src="/hero.jpg" alt="Hero">', '<strong>', 'now', '</strong>', '.' ),
			$segments
		);
	}
}