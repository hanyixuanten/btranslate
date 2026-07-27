<?php

defined( 'ABSPATH' ) || exit;

class BTRANSLATE_Settings {
	public static function get() {
		$defaults = array(
			'source_language'   => 'zh',
			'target_languages'  => array( 'en' ),
			'routing_mode'      => array( 'subdirectory' ),
			'domain_bindings'   => array(),
			'fallback_language' => 'zh',
			'baidu_app_id'      => '',
			'baidu_secret_key'  => '',
			'log_requests'      => false,
		);

		$settings                 = wp_parse_args( (array) get_option( 'btranslate_settings', array() ), $defaults );
		$settings['routing_mode'] = self::routing_modes( $settings['routing_mode'] );

		return $settings;
	}

	public static function routing_modes( $routing_mode = null ) {
		if ( null === $routing_mode ) {
			$routing_mode = self::get()['routing_mode'];
		}

		$modes = array_values( array_intersect( (array) $routing_mode, array( 'subdirectory', 'domain' ) ) );

		return empty( $modes ) ? array( 'subdirectory' ) : $modes;
	}

	public static function is_routing_mode_enabled( $mode ) {
		return in_array( $mode, self::routing_modes(), true );
	}

	public static function target_languages() {
		return array_values( array_filter( array_map( 'sanitize_key', (array) self::get()['target_languages'] ) ) );
	}

	public static function subdirectory_languages() {
		$languages = self::target_languages();

		if ( self::is_routing_mode_enabled( 'domain' ) ) {
			$languages = array_diff( $languages, array_values( (array) self::get()['domain_bindings'] ) );
		}

		return array_values( $languages );
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
