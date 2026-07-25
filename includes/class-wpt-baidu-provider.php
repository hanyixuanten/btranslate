<?php

defined( 'ABSPATH' ) || exit;

class WPT_Baidu_Provider implements WPT_Translation_Provider {
	private $app_id;
	private $secret_key;

	public function __construct( $app_id, $secret_key ) {
		$this->app_id     = $app_id;
		$this->secret_key = $secret_key;
	}

	public function get_version() {
		return 'baidu-vip-v1';
	}

	public function translate( $source_value, $source_language, $target_language, $context ) {
		if ( '' === $this->app_id || '' === $this->secret_key ) {
			return WPT_Translation_Result::failure( 'missing_credentials', __( 'Baidu Translate credentials are not configured.', 'wp-translate' ) );
		}

		$salt      = (string) wp_rand( 100000, 999999 );
		$signature = md5( $this->app_id . $source_value . $salt . $this->secret_key );
		$response  = wp_remote_post(
			'https://fanyi-api.baidu.com/api/trans/vip/translate',
			array(
				'timeout' => 15,
				'body'    => array(
					'q'     => $source_value,
					'from'  => $source_language,
					'to'    => $target_language,
					'appid' => $this->app_id,
					'salt'  => $salt,
					'sign'  => $signature,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return WPT_Translation_Result::failure( 'request_failed', $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['trans_result'] ) || ! is_array( $body['trans_result'] ) ) {
			$error_code    = isset( $body['error_code'] ) ? sanitize_key( $body['error_code'] ) : 'invalid_response';
			$error_message = isset( $body['error_msg'] ) ? sanitize_text_field( $body['error_msg'] ) : __( 'Baidu Translate returned an invalid response.', 'wp-translate' );

			return WPT_Translation_Result::failure( $error_code, $error_message );
		}

		$translated_parts = wp_list_pluck( $body['trans_result'], 'dst' );

		return WPT_Translation_Result::success( implode( "\n", $translated_parts ) );
	}
}
