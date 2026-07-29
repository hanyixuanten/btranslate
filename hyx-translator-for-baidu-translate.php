<?php
/**
 * Plugin Name: HTBD - hyx Translator powered by Baidu Translate
 * Plugin URI: https://github.com/hanyixuanten/HTBD
 * Description: Persistent multilingual WordPress translations powered by Baidu Translate.
 * Version: 0.2.2
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: hanyixuanten
 * License: GPL-3.0-only
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: hyx-translator-for-baidu-translate
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'HTBD_VERSION', '0.2.2' );
define( 'HTBD_FILE', __FILE__ );
define( 'HTBD_PATH', plugin_dir_path( __FILE__ ) );

require_once HTBD_PATH . 'includes/interface-translation-provider.php';
require_once HTBD_PATH . 'includes/class-translation-result.php';
require_once HTBD_PATH . 'includes/class-translation-identity.php';
require_once HTBD_PATH . 'includes/class-translation-store.php';
require_once HTBD_PATH . 'includes/class-baidu-provider.php';
require_once HTBD_PATH . 'includes/class-translation-service.php';
require_once HTBD_PATH . 'includes/class-content-translator.php';
require_once HTBD_PATH . 'includes/class-settings.php';
require_once HTBD_PATH . 'includes/class-language-router.php';
require_once HTBD_PATH . 'includes/class-sitemap-controller.php';
require_once HTBD_PATH . 'includes/class-content-controller.php';
require_once HTBD_PATH . 'includes/class-admin.php';
require_once HTBD_PATH . 'includes/class-plugin.php';
require_once HTBD_PATH . 'includes/class-uninstaller.php';

register_activation_hook( HTBD_FILE, array( 'HTBD_Plugin', 'activate' ) );
register_deactivation_hook( HTBD_FILE, array( 'HTBD_Plugin', 'deactivate' ) );

function htbd_load_textdomain() {
	load_plugin_textdomain( 'hyx-translator-for-baidu-translate', false, dirname( plugin_basename( HTBD_FILE ) ) . '/languages' );
}

add_action( 'plugins_loaded', 'htbd_load_textdomain', 5 );
add_action( 'plugins_loaded', array( 'HTBD_Plugin', 'instance' ) );