<?php
/**
 * Plugin Name: WP Translate
 * Description: Persistent multilingual WordPress translations powered by Baidu Translate.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: WP Translate
 * Text Domain: wp-translate
 */

defined( 'ABSPATH' ) || exit;

define( 'WPT_VERSION', '0.1.0' );
define( 'WPT_FILE', __FILE__ );
define( 'WPT_PATH', plugin_dir_path( __FILE__ ) );

require_once WPT_PATH . 'includes/interface-wpt-translation-provider.php';
require_once WPT_PATH . 'includes/class-wpt-translation-result.php';
require_once WPT_PATH . 'includes/class-wpt-translation-identity.php';
require_once WPT_PATH . 'includes/class-wpt-translation-store.php';
require_once WPT_PATH . 'includes/class-wpt-baidu-provider.php';
require_once WPT_PATH . 'includes/class-wpt-translation-service.php';
require_once WPT_PATH . 'includes/class-wpt-content-translator.php';
require_once WPT_PATH . 'includes/class-wpt-settings.php';
require_once WPT_PATH . 'includes/class-wpt-language-router.php';
require_once WPT_PATH . 'includes/class-wpt-content-controller.php';
require_once WPT_PATH . 'includes/class-wpt-admin.php';
require_once WPT_PATH . 'includes/class-wpt-plugin.php';

register_activation_hook( WPT_FILE, array( 'WPT_Plugin', 'activate' ) );
register_deactivation_hook( WPT_FILE, array( 'WPT_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'WPT_Plugin', 'instance' ) );
