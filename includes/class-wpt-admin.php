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
			$domain = 2 === count( $parts ) ? WPT_Settings::normalize_domain( $parts[0] ) : '';
			$language = 2 === count( $parts ) ? sanitize_key( $parts[1] ) : '';
			if ( '' !== $domain && '' !== $language ) {
				$bindings[ $domain ] = $language;
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
			<h1>WP Translate</h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'wpt_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="wpt-source-language">源语言</label></th><td><input id="wpt-source-language" name="wpt_settings[source_language]" type="text" value="<?php echo esc_attr( $settings['source_language'] ); ?>" /><p class="description">使用百度翻译语言代码，例如 <code>zh</code>。</p></td></tr>
					<tr><th scope="row"><label for="wpt-target-languages">目标语言</label></th><td><input id="wpt-target-languages" name="wpt_settings[target_languages]" type="text" value="<?php echo esc_attr( implode( ',', $settings['target_languages'] ) ); ?>" class="regular-text" /><p class="description">多个语言代码用英文逗号分隔，例如 <code>en,ja</code>。</p></td></tr>
					<tr><th scope="row"><label for="wpt-routing-mode">网址模式</label></th><td><select id="wpt-routing-mode" name="wpt_settings[routing_mode]"><option value="subdirectory" <?php selected( $settings['routing_mode'], 'subdirectory' ); ?>>子目录</option><option value="domain" <?php selected( $settings['routing_mode'], 'domain' ); ?>>绑定域名</option></select></td></tr>
					<tr><th scope="row"><label for="wpt-domain-bindings">域名绑定</label></th><td><textarea id="wpt-domain-bindings" name="wpt_settings[domain_bindings]" rows="4" class="large-text" placeholder="en.example.com=en"><?php echo esc_textarea( implode( "\n", $bindings ) ); ?></textarea><p class="description">每行一个 <code>域名=语言代码</code>，例如 <code>en.example.com=en</code>。请勿填写 <code>https://</code>、路径或端口。</p></td></tr>
					<tr><th scope="row"><label for="wpt-baidu-app-id">百度应用 ID</label></th><td><input id="wpt-baidu-app-id" name="wpt_settings[baidu_app_id]" type="text" value="<?php echo esc_attr( $settings['baidu_app_id'] ); ?>" class="regular-text" /></td></tr>
					<tr><th scope="row"><label for="wpt-baidu-secret-key">百度密钥</label></th><td><input id="wpt-baidu-secret-key" name="wpt_settings[baidu_secret_key]" type="password" value="<?php echo esc_attr( $settings['baidu_secret_key'] ); ?>" class="regular-text" autocomplete="new-password" /></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}