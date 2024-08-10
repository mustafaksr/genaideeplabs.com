<?php
/**
 * This file is controller for views/twofa/two-fa.php.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'RegistrationHandler' ) ) {
	/**
	 * Class for handling registration.
	 */
	class RegistrationHandler {

		/**
		 * Constructor for RegistrationHandler.
		 */
		public function __construct() {
			add_filter( 'registration_errors', array( $this, 'mo_wpns_registration_validations' ), 10, 3 );
			if ( get_site_option( 'mo2f_custom_form_name' ) === '#wordpress-register' ) {
				add_action( 'register_form', array( $this, 'mo2f_wp_verification' ) );
			}
		}

		/**
		 * Function for registration verification.
		 *
		 * @return void
		 */
		public function mo2f_wp_verification() {
			global $main_dir;
			$submit_selector       = '#wp-submit';
			$form_name             = '#registerform';
			$email_field           = '#user_email';
			$auth_type             = get_site_option( 'mo2f_custom_auth_type' );
			$phone_selector        = get_site_option( 'mo2f_custom_phone_selector' );
			$notification_selector = get_site_option( 'mo2f_custom_notification_selector', '#otpmessage' );
			$success_class         = get_site_option( 'mo2f_custom_success_selector', 'mo2f_green' );
			$error_class           = get_site_option( 'mo2f_custom_error_selector', 'mo2f_red' );
			if ( get_site_option( 'mo2f_customerkey' ) > 0 ) {
				$is_registered = get_site_option( 'mo2f_customerkey' );
			} else {
				$is_registered = 'false';
			}
			$java_script     = 'includes/js/custom-form.min.js';
			$country_details = is_array( get_site_option( 'mo2f_country_code' ) ) ? wp_unslash( get_site_option( 'mo2f_country_code' ) ) : array();

			wp_enqueue_style( 'mo2f_intl_tel_style', $main_dir . 'includes/css/phone.min.css', array(), MO2F_VERSION, false );
			wp_enqueue_script( 'mo2f_intl_tel_script', $main_dir . 'includes/js/phone.min.js', array(), MO2F_VERSION, false );
			wp_localize_script( 'mo2f_intl_tel_script', 'countryDetails', $country_details );
			wp_register_script( 'mo2f_otpVerification', $main_dir . $java_script, array(), MO2F_VERSION, false );
			wp_localize_script(
				'mo2f_otpVerification',
				'otpverificationObj',
				array(
					'siteURL'              => admin_url( 'admin-ajax.php' ),
					'nonce'                => wp_create_nonce( 'ajax-nonce' ),
					'authType'             => $auth_type,
					'submitSelector'       => $submit_selector,
					'formname'             => $form_name,
					'emailselector'        => $email_field,
					'isRegistered'         => $is_registered,
					'phoneSelector'        => $phone_selector,
					'errorClass'           => $error_class,
					'successClass'         => $success_class,
					'notificationSelector' => $notification_selector,
					'loaderUrl'            => plugin_dir_url( __FILE__ ) . 'includes/images/loader.gif',
					'isEnabledShortcode'   => get_site_option( 'enable_form_shortcode' ),
				)
			);
			wp_localize_script(
				'mo2f_otpVerification',
				'otpverificationStringsObj',
				array(
					'contact_admin'         => __( 'Contact Site Administrator', 'miniorange-2-factor-authentication' ),
					'sending_otp'           => __( 'Sending OTP ', 'miniorange-2-factor-authentication' ),
					'invalid_phone'         => __( 'Invalid Phone Number', 'miniorange-2-factor-authentication' ),
					'phone_num'             => __( 'Phone Number', 'miniorange-2-factor-authentication' ),
					'invalid_email'         => __( 'Invalid Email Address', 'miniorange-2-factor-authentication' ),
					'send_otp'              => __( 'Send OTP ', 'miniorange-2-factor-authentication' ),
					'enter_otp'             => __( 'Enter OTP ', 'miniorange-2-factor-authentication' ),
					'resend_otp'            => __( 'Resend OTP ', 'miniorange-2-factor-authentication' ),
					'validate_otp'          => __( 'Validate OTP ', 'miniorange-2-factor-authentication' ),
					'otp_sent_phone'        => __( 'An OTP will be sent to your Mobile Number', 'miniorange-2-factor-authentication' ),
					'otp_sent_both'         => __( 'An OTP will be sent to your Mobile Number and Email', 'miniorange-2-factor-authentication' ),
					'otp_sent_email'        => __( 'An OTP will be sent to your Email ID', 'miniorange-2-factor-authentication' ),
					'already_validated'     => __( 'Already Validated', 'miniorange-2-factor-authentication' ),
					'validate_phone'        => __( 'Please Validate Phone first', 'miniorange-2-factor-authentication' ),
					'validate_email'        => __( 'Please Validate Email first', 'miniorange-2-factor-authentication' ),
					'phone_field_not_found' => __( 'miniOrange : Phone Field not Found.', 'miniorange-2-factor-authentication' ),
					'email_field'           => __( 'miniOrange : Email Field', 'miniorange-2-factor-authentication' ),
					'not_found'             => __( ' not Found. Please check Selector', 'miniorange-2-factor-authentication' ),
					'validate_both'         => __( 'Please Validate Email and Phone first', 'miniorange-2-factor-authentication' ),
					'account_register'      => __( 'miniOrange : Register/Login with miniOrange to Enable 2FA for this Form', 'miniorange-2-factor-authentication' ),
					'register'              => __( 'Register', 'miniorange-2-factor-authentication' ),
					'validation_error'      => __( 'Error Validating OTP', 'miniorange-2-factor-authentication' ),
					'phone_validated'       => __( 'Phone Number Validated', 'miniorange-2-factor-authentication' ),
				)
			);
			wp_enqueue_script( 'mo2f_otpVerification' );
		}

		/**
		 * This function validates registration of user.
		 *
		 * @param object $errors error object.
		 * @param string $sanitized_user_login user login.
		 * @param string $user_email email.
		 * @return object
		 */
		public function mo_wpns_registration_validations( $errors, $sanitized_user_login, $user_email ) {
			global $mo_wpns_utility;
			if ( get_option( 'mo_wpns_activate_recaptcha_for_registration' ) ) {
				$g_captcha_response = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Cannot do nonce verification here as this is external flow of captcha verification
				if ( get_option( 'mo_wpns_recaptcha_version' ) === 'reCAPTCHA_v3' ) {
					$recaptcha_error = $mo_wpns_utility->verify_recaptcha_3( $g_captcha_response );
				} elseif ( get_option( 'mo_wpns_recaptcha_version' ) === 'reCAPTCHA_v2' ) {
					$recaptcha_error = $mo_wpns_utility->verify_recaptcha( sanitize_text_field( $g_captcha_response ) );
				}
				if ( ! empty( $recaptcha_error->errors ) ) {
					$errors = $recaptcha_error;
				}
			}
			if ( get_site_option( 'mo_wpns_enable_fake_domain_blocking' ) ) {
				if ( $mo_wpns_utility->check_if_valid_email( $user_email ) && empty( $recaptcha_error->errors ) ) {
					$errors->add( 'blocked_email_error', __( '<strong>ERROR</strong>: Your email address is not allowed to register. Please select different email address.' ) );
				} elseif ( ! empty( $recaptcha_error->errors ) ) {
					$errors = $recaptcha_error;
				}
			} else {
				$count = get_site_option( 'number_of_fake_reg' );
				if ( $mo_wpns_utility->check_if_valid_email( $user_email ) && empty( $recaptcha_error->errors ) ) {
					$count = $count ++;
					update_site_option( 'number_of_fake_reg', $count );
				}
			}
			return $errors;
		}
	}
	new RegistrationHandler();
}
