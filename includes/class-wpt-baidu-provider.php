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
			$this->log_request( $source_value, $source_language, $target_language, $context, 'missing_credentials' );

			return WPT_Translation_Result::failure( 'missing_credentials', __( 'Baidu Translate credentials are not configured.', 'wp-btranslate' ) );
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
			$this->log_request( $source_value, $source_language, $target_language, $context, 'request_failed', $response->get_error_code() );

			return WPT_Translation_Result::failure( 'request_failed', $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['trans_result'] ) || ! is_array( $body['trans_result'] ) ) {
			$error_code    = isset( $body['error_code'] ) ? sanitize_key( $body['error_code'] ) : 'invalid_response';
			$error_message = isset( $body['error_msg'] ) ? sanitize_text_field( $body['error_msg'] ) : __( 'Baidu Translate returned an invalid response.', 'wp-btranslate' );
			$this->log_request( $source_value, $source_language, $target_language, $context, 'failed', $error_code );

			return WPT_Translation_Result::failure( $error_code, $error_message );
		}

		$translated_parts = wp_list_pluck( $body['trans_result'], 'dst' );
		$this->log_request( $source_value, $source_language, $target_language, $context, 'complete' );

		return WPT_Translation_Result::success( implode( "\n", $translated_parts ) );
	}

	private function log_request( $source_value, $source_language, $target_language, $context, $status, $error_code = '' ) {
		$settings = WPT_Settings::get();

		if ( empty( $settings['log_requests'] ) ) {
			return;
		}

		$entry = array(
			'provider'           => 'baidu',
			'source_language'    => sanitize_key( $source_language ),
			'target_language'    => sanitize_key( $target_language ),
			'context'            => sanitize_key( $context ),
			'source_fingerprint' => WPT_Translation_Identity::source_fingerprint( (string) $source_value ),
			'source_length'      => strlen( (string) $source_value ),
			'status'             => sanitize_key( $status ),
			'error_code'         => sanitize_key( $error_code ),
		);

		error_log( 'WPT translation request: ' . wp_json_encode( $entry ) );
	}
}
