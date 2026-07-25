<?php

defined( 'ABSPATH' ) || exit;

class WPT_Admin {
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_settings_page() {
		add_options_page( __( 'WP Translate', 'wp-translate' ), __( 'WP Translate', 'wp-translate' ), 'manage_options', 'wp-translate', array( $this, 'render_settings_page' ) );
	}

	public function register_settings() {
		register_setting( 'wpt_settings', 'wpt_settings', array( $this, 'sanitize_settings' ) );
	}

	public function sanitize_settings( $settings ) {
		$settings = (array) $settings;
		$bindings = array();

		foreach ( preg_split( '/\r\n|\r|\n/', (string) ( $settings['domain_bindings'] ?? '' ) ) as $line ) {
			$parts = array_map( 'trim', explode( '=', $line, 2 ) );
			if ( 2 === count( $parts ) && '' !== $parts[0] && '' !== $parts[1] ) {
				$bindings[ sanitize_text_field( $parts[0] ) ] = sanitize_key( $parts[1] );
			}
		}

		return array(
			'source_language'   => sanitize_key( $settings['source_language'] ?? 'zh' ),
			'target_languages'  => array_values( array_filter( array_map( 'sanitize_key', explode( ',', (string) ( $settings['target_languages'] ?? 'en' ) ) ) ) ),
			'routing_mode'      => in_array( $settings['routing_mode'] ?? '', array( 'subdirectory', 'domain' ), true ) ? $settings['routing_mode'] : 'subdirectory',
			'domain_bindings'   => $bindings,
			'fallback_language' => sanitize_key( $settings['fallback_language'] ?? 'zh' ),
			'baidu_app_id'      => sanitize_text_field( $settings['baidu_app_id'] ?? '' ),
			'baidu_secret_key'  => sanitize_text_field( $settings['baidu_secret_key'] ?? '' ),
		);
	}

	public function render_settings_page() {
		$settings = WPT_Settings::get();
		$bindings = array();
		foreach ( (array) $settings['domain_bindings'] as $domain => $language ) {
			$bindings[] = $domain . '=' . $language;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Translate', 'wp-translate' ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'wpt_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="wpt-source-language"><?php esc_html_e( 'Source language', 'wp-translate' ); ?></label></th><td><input id="wpt-source-language" name="wpt_settings[source_language]" type="text" value="<?php echo esc_attr( $settings['source_language'] ); ?>" /></td></tr>
					<tr><th scope="row"><label for="wpt-target-languages"><?php esc_html_e( 'Target languages', 'wp-translate' ); ?></label></th><td><input id="wpt-target-languages" name="wpt_settings[target_languages]" type="text" value="<?php echo esc_attr( implode( ',', $settings['target_languages'] ) ); ?>" class="regular-text" /></td></tr>
					<tr><th scope="row"><label for="wpt-routing-mode"><?php esc_html_e( 'URL mode', 'wp-translate' ); ?></label></th><td><select id="wpt-routing-mode" name="wpt_settings[routing_mode]"><option value="subdirectory" <?php selected( $settings['routing_mode'], 'subdirectory' ); ?>><?php esc_html_e( 'Subdirectory', 'wp-translate' ); ?></option><option value="domain" <?php selected( $settings['routing_mode'], 'domain' ); ?>><?php esc_html_e( 'Domain binding', 'wp-translate' ); ?></option></select></td></tr>
					<tr><th scope="row"><label for="wpt-domain-bindings"><?php esc_html_e( 'Domain bindings', 'wp-translate' ); ?></label></th><td><textarea id="wpt-domain-bindings" name="wpt_settings[domain_bindings]" rows="4" class="large-text"><?php echo esc_textarea( implode( "\n", $bindings ) ); ?></textarea></td></tr>
					<tr><th scope="row"><label for="wpt-baidu-app-id"><?php esc_html_e( 'Baidu app ID', 'wp-translate' ); ?></label></th><td><input id="wpt-baidu-app-id" name="wpt_settings[baidu_app_id]" type="text" value="<?php echo esc_attr( $settings['baidu_app_id'] ); ?>" class="regular-text" /></td></tr>
					<tr><th scope="row"><label for="wpt-baidu-secret-key"><?php esc_html_e( 'Baidu secret key', 'wp-translate' ); ?></label></th><td><input id="wpt-baidu-secret-key" name="wpt_settings[baidu_secret_key]" type="password" value="<?php echo esc_attr( $settings['baidu_secret_key'] ); ?>" class="regular-text" autocomplete="new-password" /></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}