<?php
/**
 * File consists of functions to save configuration for Duo Authenticator method.
 *
 * @package miniOrange-2-factor-authentication/controllers
 */

use TwoFA\Onprem\MO2f_Utility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo_2f_Duo_Authenticator' ) ) {

	/**
	 * Class Mo_2f_duo_authenticator
	 */
	class Mo_2f_Duo_Authenticator {

		/**
		 * Class Mo_2f_duo_authenticator constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'mo2f_duo_authenticator_functions' ) );

		}

		/**
		 * Calls 'mo2f_duo_authenticator_ajax' and 'mo2f_duo_ajax_request' functions.
		 *
		 * @return void
		 */
		public function mo2f_duo_authenticator_functions() {
			add_action( 'wp_ajax_mo2f_duo_authenticator_ajax', array( $this, 'mo2f_duo_authenticator_ajax' ) );
			add_action( 'wp_ajax_nopriv_mo2f_duo_ajax_request', array( $this, 'mo2f_duo_ajax_request' ) );
		}

		/**
		 * Calls 'mo2f_check_duo_push_auth_status' function if switch case is 'check_duo_push_auth_status'.
		 *
		 * @return void
		 */
		public function mo2f_duo_ajax_request() {

			if ( ! check_ajax_referer( 'miniorange-2-factor-duo-nonce', 'nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( $error );

			} else {
				$call_type = isset( $_POST['call_type'] ) ? sanitize_text_field( wp_unslash( $_POST['call_type'] ) ) : '';
				switch ( $call_type ) {
					case 'check_duo_push_auth_status':
						$this->mo2f_check_duo_push_auth_status();
						break;
				}
			}
		}

		/**
		 * Calls 'mo2f_check_duo_push_auth_status' function is switch case is 'check_duo_push_auth_status'.
		 *
		 * @return void
		 */
		public function mo2f_duo_authenticator_ajax() {

			if ( ! check_ajax_referer( 'miniorange-2-factor-duo-nonce', 'nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( $error );

			} else {
				$call_type = isset( $_POST['call_type'] ) ? sanitize_text_field( wp_unslash( $_POST['call_type'] ) ) : '';
				switch ( $call_type ) {
					case 'check_duo_push_auth_status':
						$this->mo2f_check_duo_push_auth_status();
						break;
				}
			}
		}

		/**
		 * Checks duo authentication push notification status.
		 *
		 * @return void
		 */
		public function mo2f_check_duo_push_auth_status() {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! check_ajax_referer( 'miniorange-2-factor-duo-nonce', 'nonce', false ) ) {
				wp_send_json_error();

			} else {
				include_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-duo-handler.php';
				$ikey         = get_site_option( 'mo2f_d_integration_key' );
				$skey         = get_site_option( 'mo2f_d_secret_key' );
				$host         = get_site_option( 'mo2f_d_api_hostname' );
				$current_user = wp_get_current_user();

				$session_id_encrypt = isset( $_POST['session_id_encrypt'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id_encrypt'] ) ) : '';
				$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
				$user_email         = get_user_meta( $user_id, 'current_user_email' );
				$user_email         = isset( $user_email[0] ) ? $user_email[0] : '';

				if ( empty( $user_email ) ) {
					$user_email = sanitize_email( $current_user->user_email );
				}

				$device['device'] = 'auto';
				$auth_response    = mo2f_duo_auth( $user_email, 'push', $device, $skey, $ikey, $host, true );

				if ( isset( $auth_response['response']['response']['result'] ) && 'allow' === $auth_response['response']['response']['result'] ) {
					wp_send_json_success();
				} else {

					wp_send_json_error();
				}
			}

		}

	}
	new Mo_2f_duo_authenticator();
}


