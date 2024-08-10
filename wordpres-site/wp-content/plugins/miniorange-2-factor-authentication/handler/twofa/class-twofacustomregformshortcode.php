<?php
/** This file handles the OTP verification flow for registering user.
 *
 * @package        miniorange-2-factor-authentication/handler/twofa
 */

use TwoFA\Handler\TwoFACustomRegFormAPI;
use TwoFA\Helper\MoWpnsMessages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */
require_once 'class-twofacustomregformapi.php';

if ( ! class_exists( 'TwoFACustomRegFormShortcode' ) ) {
	/**
	 * Class is basically for shortcode
	 */
	class TwoFACustomRegFormShortcode {

		/**
		 * Constructor function
		 */
		public function __construct() {
			if ( 'true' === get_option( 'enable_form_shortcode' ) ) {
				add_action( 'woocommerce_created_customer', array( $this, 'wc_post_registration' ), 1, 3 );
			}
		}
		/**
		 * It will enqueue the shortcode
		 *
		 * @return void
		 */
		public function mo_enqueue_shortcode() {
			add_action( 'wp_ajax_mo_shortcode', array( $this, 'mo_shortcode' ) );
			add_action( 'wp_ajax_nopriv_mo_shortcode', array( $this, 'mo_shortcode' ) );
			add_action( 'wp_ajax_mo_ajax_register', array( $this, 'mo_ajax_register' ) );
			add_action( 'wp_ajax_nopriv_mo_ajax_register', array( $this, 'mo_ajax_register' ) );
		}
		/**
		 * It will call the shortcode
		 *
		 * @return void
		 */
		public function mo_shortcode() {
			$show_message = new MoWpnsMessages();
			$nonce        = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
			}

			$choice = isset( $_POST['mo_action'] ) ? sanitize_text_field( wp_unslash( $_POST['mo_action'] ) ) : '';

			switch ( $choice ) {
				case 'challenge':
					$email          = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
					$phone          = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
					$auth_type_send = isset( $_POST['authTypeSend'] ) ? sanitize_text_field( wp_unslash( $_POST['authTypeSend'] ) ) : '';
					TwoFACustomRegFormAPI::challenge( $phone, $email, $auth_type_send );
					break;

				case 'validate':
					$otp       = isset( $_POST['otp'] ) ? sanitize_text_field( wp_unslash( $_POST['otp'] ) ) : '';
					$txid      = isset( $_POST['txId'] ) ? sanitize_key( $_POST['txId'] ) : '';
					$auth_type = isset( $_POST['authType'] ) ? sanitize_text_field( wp_unslash( $_POST['authType'] ) ) : '';
					TwoFACustomRegFormAPI::validate( $auth_type, $txid, $otp );
					break;
			}
		}
		/**
		 * It will help to register the ajax forms
		 *
		 * @return void
		 */
		public function mo_ajax_register() {
			$show_message = new MoWpnsMessages();
			$nonce        = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
			}

			$choice = isset( $_POST['mo_action'] ) ? sanitize_text_field( wp_unslash( $_POST['mo_action'] ) ) : '';
			switch ( $choice ) {
				case 'send_otp_over_email':
					$email          = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
					$phone          = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
					$auth_type_send = isset( $_POST['authTypeSend'] ) ? sanitize_text_field( wp_unslash( $_POST['authTypeSend'] ) ) : '';
					TwoFACustomRegFormAPI::challenge( $phone, $email, $auth_type_send );
					break;
				case 'send_otp_over_sms':
					$email          = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
					$phone          = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
					$auth_type_send = sanitize_text_field( wp_unslash( $_POST['authTypeSend'] ) );
					TwoFACustomRegFormAPI::challenge( $phone, $email, $auth_type_send );
					break;

				default:
					$otp       = isset( $_POST['otp'] ) ? sanitize_text_field( wp_unslash( $_POST['otp'] ) ) : '';
					$txid      = isset( $_POST['txid'] ) ? sanitize_key( $_POST['txid'] ) : '';
					$auth_type = isset( $_POST['authType'] ) ? sanitize_text_field( wp_unslash( $_POST['authType'] ) ) : '';
					TwoFACustomRegFormAPI::validate( $auth_type, $txid, $otp );
					break;
			}
		}
		/**
		 * It will help to post registration
		 *
		 * @param string $user_id It will carry the user id .
		 * @param string $new_customer_data It will carry the new customer data .
		 * @param string $password_generated It will carry generate password .
		 * @return void
		 */
		public function wc_post_registration( $user_id, $new_customer_data, $password_generated ) {
			$show_message = new MoWpnsMessages();
			$nonce        = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
			}
			if ( isset( $_POST['phone'] ) ) {
				update_user_meta( $user_id, 'billing_phone', sanitize_text_field( wp_unslash( $_POST['phone'] ) ) );
			}
		}
	}
}

