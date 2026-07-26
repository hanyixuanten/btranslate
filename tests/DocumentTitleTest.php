<?php

use PHPUnit\Framework\TestCase;

class WPT_Test_Language_Router extends WPT_Language_Router {
	public function current_language() {
		return 'en';
	}
}

class DocumentTitleTest extends TestCase {
	public function test_document_title_callback_leaves_non_singular_title_parts_unchanged() {
		$controller = new WPT_Content_Controller( new WPT_Test_Translation_Store(), new WPT_Test_Language_Router() );
		$parts      = array( 'title' => '中文主页', 'site' => '示例站点' );

		$this->assertSame( $parts, $controller->translate_document_title_parts( $parts ) );
	}

	public function test_wordpress_ai_meta_description_metadata_uses_persisted_translation() {
		$store       = new WPT_Test_Translation_Store();
		$description = 'Original description';
		$identity_key = WPT_Translation_Identity::key( $description, 'zh', 'en', 'post:42:meta:wpai_meta_description', 'baidu-vip-v1' );
		$store->values[ $identity_key ] = array( 'translated_value' => 'Translated description' );
		$controller  = new WPT_Content_Controller( $store, new WPT_Test_Language_Router() );

		$this->assertSame(
			'Translated description',
			$controller->translate_wordpress_ai_meta_description_metadata( $description, 42, 'wpai_meta_description', true )
		);
		$this->assertNull( $controller->translate_wordpress_ai_meta_description_metadata( null, 42, 'wpai_meta_description', true ) );
		$this->assertSame( $description, $controller->translate_wordpress_ai_meta_description_metadata( $description, 42, '_unrelated_meta', true ) );
	}
}