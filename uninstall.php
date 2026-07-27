<?php
/**
 * Removes all data created by Btranslate.
 *
 * @package BTRANSLATE
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-btranslate-uninstaller.php';

BTRANSLATE_Uninstaller::uninstall();