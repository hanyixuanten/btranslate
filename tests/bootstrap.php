<?php

define( 'ABSPATH', __DIR__ . '/' );

function is_singular() {
	return false;
}

function get_option( $option, $default = false ) {
	return $default;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, $args );
}

function get_metadata_raw( $meta_type, $object_id, $meta_key = '', $single = false ) {
	global $wpt_test_post_meta;

	$value = $wpt_test_post_meta[ $object_id ][ $meta_key ] ?? null;

	return $single ? $value : array( $value );
}

require_once dirname( __DIR__ ) . '/includes/interface-wpt-translation-provider.php';
require_once dirname( __DIR__ ) . '/includes/class-wpt-translation-result.php';
require_once dirname( __DIR__ ) . '/includes/class-wpt-translation-identity.php';
require_once dirname( __DIR__ ) . '/includes/class-wpt-translation-store.php';
require_once dirname( __DIR__ ) . '/includes/class-wpt-translation-service.php';
require_once dirname( __DIR__ ) . '/includes/class-wpt-baidu-provider.php';
require_once dirname( __DIR__ ) . '/includes/class-wpt-content-translator.php';
require_once dirname( __DIR__ ) . '/includes/class-wpt-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-wpt-language-router.php';
require_once dirname( __DIR__ ) . '/includes/class-wpt-content-controller.php';