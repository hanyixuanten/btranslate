<?php

defined( 'ABSPATH' ) || exit;

class HTBD_Plugin {
	private static $instance;
	private $router;
	private $sitemap_controller;
	private $content_controller;
	private $admin;

	private function __construct() {
		$this->router             = new HTBD_Language_Router();
		$this->sitemap_controller = new HTBD_Sitemap_Controller( $this->router );
		$this->content_controller = new HTBD_Content_Controller( new HTBD_Translation_Store(), $this->router );
		$this->admin              = new HTBD_Admin();

		add_action( 'init', array( $this, 'register' ) );
		add_action( 'update_option_htbd_settings', array( $this, 'refresh_rewrite_rules' ), 10, 2 );
		$this->admin->register();
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate() {
		HTBD_Translation_Store::install();
		wp_clear_scheduled_hook( 'btranslate_process_translation' );
		wp_clear_scheduled_hook( 'btranslate_process_term_translation' );
		wp_clear_scheduled_hook( 'btranslate_process_seo_output_translation' );
		$legacy_settings = get_option( 'btranslate_settings', null );
		if ( null !== $legacy_settings && false === get_option( 'htbd_settings', false ) ) {
			add_option( 'htbd_settings', $legacy_settings, '', false );
		}
		add_option(
			'htbd_settings',
			array(
				'source_language'   => 'zh',
				'target_languages'  => array( 'en' ),
				'routing_mode'      => array( 'subdirectory' ),
				'domain_bindings'   => array(),
				'fallback_language' => 'zh',
			),
			'',
			false
		);
		$router = new HTBD_Language_Router();
		$router->register_rewrite_rules();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'htbd_process_translation' );
		wp_clear_scheduled_hook( 'htbd_process_term_translation' );
		wp_clear_scheduled_hook( 'htbd_process_seo_output_translation' );
		flush_rewrite_rules();
	}

	public function register() {
		$this->router->register();
		$this->sitemap_controller->register();
		$this->content_controller->register();

		if ( get_option( 'htbd_flush_rewrite_rules', false ) ) {
			delete_option( 'htbd_flush_rewrite_rules' );
			flush_rewrite_rules();
		}
	}

	public function refresh_rewrite_rules( $old_value, $new_value ) {
		if ( (array) $old_value !== (array) $new_value ) {
			update_option( 'htbd_flush_rewrite_rules', 1, false );
		}
	}
}