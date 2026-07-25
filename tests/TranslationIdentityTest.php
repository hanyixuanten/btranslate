<?php

use PHPUnit\Framework\TestCase;

class TranslationIdentityTest extends TestCase {
	public function test_key_is_stable_for_identical_translation_input() {
		$first = WPT_Translation_Identity::key( 'Hello', 'en', 'zh', 'post:15:post_title', 'provider-v1' );
		$second = WPT_Translation_Identity::key( 'Hello', 'en', 'zh', 'post:15:post_title', 'provider-v1' );

		$this->assertSame( $first, $second );
	}

	public function test_key_changes_when_source_value_changes() {
		$first = WPT_Translation_Identity::key( 'Hello', 'en', 'zh', 'post:15:post_title', 'provider-v1' );
		$second = WPT_Translation_Identity::key( 'Hello again', 'en', 'zh', 'post:15:post_title', 'provider-v1' );

		$this->assertNotSame( $first, $second );
	}
}