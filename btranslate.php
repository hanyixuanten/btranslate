<?php
/**
 * Plugin Name: Btranslate
 * Description: Persistent multilingual WordPress translations powered by Baidu Translate.
 * Version: 0.2.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: hanyixuanten
 * Text Domain: btranslate
 */

defined( 'ABSPATH' ) || exit;

define( 'BTRANSLATE_VERSION', '0.2.0' );
define( 'BTRANSLATE_FILE', __FILE__ );
define( 'BTRANSLATE_PATH', plugin_dir_path( __FILE__ ) );

require_once BTRANSLATE_PATH . 'includes/interface-btranslate-translation-provider.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-translation-result.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-translation-identity.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-translation-store.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-baidu-provider.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-translation-service.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-content-translator.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-settings.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-language-router.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-sitemap-controller.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-content-controller.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-admin.php';
require_once BTRANSLATE_PATH . 'includes/class-btranslate-plugin.php';

register_activation_hook( BTRANSLATE_FILE, array( 'BTRANSLATE_Plugin', 'activate' ) );
register_deactivation_hook( BTRANSLATE_FILE, array( 'BTRANSLATE_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'BTRANSLATE_Plugin', 'instance' ) );
