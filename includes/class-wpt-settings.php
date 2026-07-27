<?php

defined( 'ABSPATH' ) || exit;

class WPT_Settings {
	public static function get() {
		$defaults = array(
			'source_language'   => 'zh',
			'target_languages'  => array( 'en' ),
			'routing_mode'      => 'subdirectory',
			'domain_bindings'   => array(),
			'fallback_language' => 'zh',
			'baidu_app_id'      => '',
			'baidu_secret_key'  => '',
			'log_requests'      => false,
		);

		return wp_parse_args( (array) get_option( 'wpt_settings', array() ), $defaults );
	}

	public static function target_languages() {
		return array_values( array_filter( array_map( 'sanitize_key', (array) self::get()['target_languages'] ) ) );
	}

	public static function is_supported_language( $language ) {
		$settings = self::get();
		$languages = array_merge( array( sanitize_key( $settings['source_language'] ) ), self::target_languages() );

		return in_array( sanitize_key( $language ), $languages, true );
	}

	public static function normalize_domain( $domain ) {
		$domain = strtolower( trim( (string) $domain ) );
		$domain = preg_replace( '#^https?://#', '', $domain );
		$domain = explode( '/', $domain, 2 )[0];
		$domain = preg_replace( '/:\d+$/', '', $domain );

		return preg_match( '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $domain ) ? $domain : '';
	}
}
