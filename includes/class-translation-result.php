<?php

defined( 'ABSPATH' ) || exit;

class HTBD_Translation_Result {
	public $success;
	public $value;
	public $error_code;
	public $error_message;

	private function __construct( $success, $value = '', $error_code = '', $error_message = '' ) {
		$this->success       = $success;
		$this->value         = $value;
		$this->error_code    = $error_code;
		$this->error_message = $error_message;
	}

	public static function success( $value ) {
		return new self( true, $value );
	}

	public static function failure( $error_code, $error_message ) {
		return new self( false, '', $error_code, $error_message );
	}
}
