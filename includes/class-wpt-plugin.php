<?php

defined( 'ABSPATH' ) || exit;

class WPT_Plugin {
	private static $instance;
	private $router;
	private $sitemap_controller;
	private $content_controller;
	private $admin;

	private function __construct() {
		$this->router             = new WPT_Language_Router();
		$this->sitemap_controller = new WPT_Sitemap_Controller( $this->router );
		$this->content_controller = new WPT_Content_Controller( new WPT_Translation_Store(), $this->router );
		$this->admin              = new WPT_Admin();

		add_action( 'init', array( $this, 'register' ) );
		add_action( 'update_option_wpt_settings', array( $this, 'refresh_rewrite_rules' ), 10, 2 );
		$this->admin->register();
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate() {
		WPT_Translation_Store::install();
		add_option(
			'wpt_settings',
			array(
				'source_language'   => 'zh',
				'target_languages'  => array( 'en' ),
				'routing_mode'      => 'subdirectory',
				'domain_bindings'   => array(),
				'fallback_language' => 'zh',
			),
			'',
			false
		);
		$router = new WPT_Language_Router();
		$router->register_rewrite_rules();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'wpt_process_translation' );
		flush_rewrite_rules();
	}

	public function register() {
		$this->router->register();
		$this->sitemap_controller->register();
		$this->content_controller->register();
	}

	public function refresh_rewrite_rules( $old_value, $new_value ) {
		if ( (array) $old_value !== (array) $new_value ) {
			$this->router->register_rewrite_rules();
			flush_rewrite_rules();
		}
	}
}