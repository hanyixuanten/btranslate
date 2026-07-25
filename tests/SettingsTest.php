<?php

use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase {
	public function test_normalize_domain_accepts_a_subdomain_and_removes_protocol_port_and_path() {
		$this->assertSame( 'en.example.com', WPT_Settings::normalize_domain( 'https://EN.Example.com:8443/news' ) );
	}

	public function test_normalize_domain_rejects_a_domain_without_a_public_suffix() {
		$this->assertSame( '', WPT_Settings::normalize_domain( 'invalid-domain' ) );
	}
}