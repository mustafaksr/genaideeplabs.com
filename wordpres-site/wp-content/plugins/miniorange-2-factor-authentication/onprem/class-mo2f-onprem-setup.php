<?php
/** The miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
 * Copyright (C) 2023  miniOrange
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @package        miniorange-2-factor-authentication/onprem
 */

namespace TwoFA\OnPrem;

use TwoFA\Traits\Instance;
use TwoFA\Onprem\Google_Auth_Onpremise;
use TwoFA\Onprem\Mo2f_OnPremRedirect;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Onprem\MO2f_Utility;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'class-mo2f-onpremredirect.php';

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */

if ( ! class_exists( 'Mo2f_Onprem_Setup' ) ) {

	/**
	 *  Class contains functions to validate 2FA.
	 */
	class Mo2f_Onprem_Setup {

		use Instance;

		/**
		 * Send OTP token according to different authentication methods
		 *
		 * @param string $u_key end user's unique key.
		 * @param string $auth_type authentication type.
		 * @param string $c_key customer key.
		 * @param string $api_key customer api key.
		 * @param mixed  $currentuser current user object.
		 * @return mixed
		 */
		public function send_otp_token( $u_key, $auth_type, $c_key = null, $api_key = null, $currentuser = null ) {
			if ( is_null( $currentuser ) || ! isset( $currentuser ) ) {
				$currentuser = wp_get_current_user();
			}
			
			if ( MoWpnsConstants::OTP_OVER_SMS === $auth_type ) {
				$mo2f_sms_mo2f_curl_redirect = new MocURL();
				$content                     = $mo2f_sms_mo2f_curl_redirect->send_otp_token( $auth_type, $u_key );
			} else {
				$mo2f_email_on_prem_redirect = new Mo2f_OnPremRedirect();
				$content                     = $mo2f_email_on_prem_redirect->on_prem_send_redirect( $u_key, $auth_type, $currentuser );
			}

			return $content;
		}

		/**
		 * Validate otp token for different authentication methods.
		 *
		 * @param string $auth_type authentication type.
		 * @param string $username user's name.
		 * @param string $transaction_id transaction ID.
		 * @param string $otp_token OTP token.
		 * @param string $c_key customer key.
		 * @param string $customer_api_key customer api key.
		 * @param object $current_user current user object.
		 * @return mixed
		 */
		public function validate_otp_token( $auth_type, $username, $transaction_id, $otp_token, $c_key, $customer_api_key, $current_user = null ) {
			if ( ! isset( $current_user ) || is_null( $current_user ) ) {
				$current_user = wp_get_current_user();
			}
			if ( MoWpnsConstants::OTP_OVER_SMS === $auth_type ) {
				$mo2f_sms_mo2f_curl_redirect = new MocURL();
				$content                     = $mo2f_sms_mo2f_curl_redirect->validate_otp_token( $transaction_id, $otp_token );
			} elseif ( MoWpnsConstants::SOFT_TOKEN === $auth_type ) {
				$mo2f_sms_mo2f_curl_redirect = new MocURL();
				$content                     = $mo2f_sms_mo2f_curl_redirect->miniorange_authenticator_validate( $auth_type, $username, $otp_token, $c_key );
			} else {
				$mo2f_email_on_prem_redirect = new Mo2f_OnPremRedirect();
				$content                     = $mo2f_email_on_prem_redirect->on_prem_validate_redirect( $auth_type, $otp_token, $current_user );

			}
			return $content;
		}

		/**
		 * Google Authenticator validation
		 *
		 * @param string $useremail user email.
		 * @param string $otptoken google authenticator secret key.
		 * @param string $secret otp token.
		 * @return string
		 */
		public function mo2f_google_auth_validate( $useremail, $otptoken, $secret ) {

			include_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			$gauth_obj          = new Google_auth_onpremise();
			$session_id_encrypt = isset( $_POST['mo2f_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_session_id'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification has been performed.
			$secret_ga          = isset( $session_id_encrypt ) ? MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'secret_ga' ) : $secret;
			$content            = $gauth_obj->mo2f_verify_code( $secret_ga, $otptoken );
			$value              = json_decode( $content, true );
			if ( 'SUCCESS' === $value['status'] ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
				$gauth_obj->mo_g_auth_set_secret( $user_id, $secret_ga );
				update_user_meta( $user_id, 'mo2f_2FA_method_to_configure', MoWpnsConstants::GOOGLE_AUTHENTICATOR );
				update_user_meta( $user_id, 'mo2f_external_app_type', MoWpnsConstants::GOOGLE_AUTHENTICATOR );
				global $mo2fdb_queries;
				$mo2fdb_queries->update_user_details( $user_id, array( 'mo2f_configured_2FA_method' => MoWpnsConstants::GOOGLE_AUTHENTICATOR ) );
			}
			return $content;
		}

		/**
		 * Undocumented function
		 *
		 * @param int     $user_id user id.
		 * @param boolean $config_status 2fa configuration status.
		 * @param string  $twofa_method 2fa method.
		 * @param string  $user_registration user registration status.
		 * @param string  $twofa_reg_status 2fa registration status.
		 * @param boolean $twofa_by_user enable 2fa by user.
		 * @param string  $email user's email.
		 * @param string  $phone user'phone.
		 * @return mixed
		 */
		public function mo2f_update_user_info( $user_id, $config_status, $twofa_method, $user_registration, $twofa_reg_status, $twofa_by_user, $email, $phone = null ) {
			if ( isset( $user_id ) ) {
				$update_details = new Miniorange_Password_2Factor_Login();
				$update_details->mo2fa_update_user_details( $user_id, $config_status, $twofa_method, $user_registration, $twofa_reg_status, $twofa_by_user, $email, $phone );
			}
			return wp_json_encode( array( 'status' => 'SUCCESS' ) );
		}

		/**
		 * Register KbA details on onpremise.
		 *
		 * @param string $email user email.
		 * @param string $question1 kba question 1.
		 * @param string $question2 kba question 2.
		 * @param string $question3 kba question 3.
		 * @param string $answer1 KBA answer 1.
		 * @param string $answer2 KBA answer 2.
		 * @param string $answer3 KBA answer 3.
		 * @param string $user_id User ID.
		 * @return mixed
		 */
		public function mo2f_cloud_register_kba( $email, $question1, $question2, $question3, $answer1, $answer2, $answer3, $user_id = null ) {
			$answer1         = md5( strtolower( $answer1 ) );
			$answer2         = md5( strtolower( $answer2 ) );
			$answer3         = md5( strtolower( $answer3 ) );
			$question_answer = array(
				$question1 => $answer1,
				$question2 => $answer2,
				$question3 => $answer3,
			);
			update_user_meta( $user_id, 'mo2f_kba_challenge', $question_answer );
			global $mo2fdb_queries;
			$mo2fdb_queries->update_user_details( $user_id, array( 'mo2f_configured_2FA_method' => MoWpnsConstants::SECURITY_QUESTIONS ) );
			$response = wp_json_encode( array( 'status' => 'SUCCESS' ) );
			return $response;
		}

		/**
		 * Verifies the answers of KBA while logging in.
		 *
		 * @param int    $user_id User ID.
		 * @param string $session_id Session ID.
		 * @param string $redirect_to Redirect URL.
		 * @return void
		 */
		public function mo2f_login_kba_verification( $user_id, $session_id, $redirect_to ) {
			$question_answers    = is_array( get_user_meta( $user_id, 'mo2f_kba_challenge', true ) ) ? get_user_meta( $user_id, 'mo2f_kba_challenge', true ) : array();
			$challenge_questions = array_keys( $question_answers );
			$random_keys         = array_rand( $challenge_questions, 2 );
			$challenge_ques1     = $challenge_questions[ $random_keys[0] ];
			$challenge_ques2     = $challenge_questions[ $random_keys[1] ];
			$questions[0]        = array( 'question' => addslashes( $challenge_ques1 ) );
			$questions[1]        = array( 'question' => addslashes( $challenge_ques2 ) );
			update_user_meta( $user_id, 'kba_questions_user', $questions );
			$mo2fa_login_message = 'Please answer the following questions:';
			$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION';
			$mo2f_kbaquestions   = $questions;
			MO2f_Utility::mo2f_set_transient( $session_id, 'mo_2_factor_kba_questions', $questions );
			$pass2fa_login = new Miniorange_Password_2Factor_Login();
			$pass2fa_login->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id, $mo2f_kbaquestions );
		}

		/**
		 * Google Authenticator setup
		 *
		 * @param object $user user object.
		 * @param string $session_id session id.
		 * @return void
		 */
		public function mo2f_gauth_setup( $user, $session_id ) {
			global $mo2fdb_queries;
			include_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			$gauth_obj        = new Google_auth_onpremise();
			$email            = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
			$onpremise_secret = $gauth_obj->mo2f_create_secret();
			$issuer           = get_site_option( 'mo2f_google_appname', DEFAULT_GOOGLE_APPNAME );
			$url              = $gauth_obj->mo2f_geturl( $onpremise_secret, $issuer, $email );
			MO2f_Utility::mo2f_set_transient( $session_id, 'secret_ga', $onpremise_secret );
			MO2f_Utility::mo2f_set_transient( $session_id, 'ga_qrCode', $url );
		}
		/**
		 * Sends email verification link to the users email.
		 *
		 * @param string $user_email user email.
		 * @param string $mo2f_second_factor 2FA method of user.
		 * @param string $mo2f_customer_key customer key of the miniorange customer.
		 * @param string $mo2f_api_key API key.
		 * @param string $current_user Current user.
		 * @return mixed
		 */
		public function mo2f_send_verification_link( $user_email, $mo2f_second_factor, $mo2f_customer_key, $mo2f_api_key, $current_user ) {
			MO2f_Utility::mo2f_debug_file( 'Push notification has sent successfully for ' . $mo2f_second_factor . ' Email-' . $user_email . ' customer key: ' . $mo2f_customer_key . ' API key: ' . $mo2f_api_key );
			$mo2f_on_prem_redirect = new Mo2f_OnPremRedirect();
			$content               = $mo2f_on_prem_redirect->mo2f_pass2login_push_email_onpremise( $current_user );
			return $content;
		}
		/**
		 * Set Google authenticator secret key.
		 *
		 * @param int    $user_id User ID.
		 * @param string $email User email.
		 * @param string $ga_secret Google authenticator secret key.
		 * @return void
		 */
		public function mo2f_set_gauth_secret( $user_id, $email, $ga_secret ) {
			$gauth_obj = new Google_auth_onpremise();
			$gauth_obj->mo_g_auth_set_secret( $user_id, $ga_secret );
		}
		/**
		 * Send Email verification link to the user's email.
		 *
		 * @param object $current_user User object.
		 * @return void
		 */
		public function mo2f_email_verification_call( $current_user ) {
			$mo2f_on_prem_redirect = new Mo2f_OnPremRedirect();
			$mo2f_on_prem_redirect->mo2f_pass2login_push_email_onpremise( $current_user, true );
		}
		/**
		 * Set email verification for user.
		 *
		 * @param object $current_user currently logged in user object.
		 * @param string $selected_method Selected 2fa method by user.
		 * @return array
		 */
		public function mo2f_cloud_set_oob_email( $current_user, $selected_method ) {
			global $mo2fdb_queries;
			$mo2fdb_queries->update_user_details(
				$current_user->ID,
				array(
					'mo2f_EmailVerification_config_status' => true,
					'mo2f_configured_2FA_method'           => MoWpnsConstants::OUT_OF_BAND_EMAIL,
					'mo2f_user_email'                      => $current_user->user_email,
					'mo_2factor_user_registration_status'  => 'MO_2_FACTOR_PLUGIN_SETTINGS',
				)
			);
			$mo2fa_login_status = 'MO_2_FACTOR_SETUP_SUCCESS';

			return array(
				'mo2fa_login_status' => $mo2fa_login_status,
			);
		}
		/**
		 * Set OTP over email method for a user.
		 *
		 * @param object $current_user user object.
		 * @param string $selected_method selcted 2fa method.
		 * @param string $session_id_encrypt encrypted session id.
		 * @param string $redirect_to redirect url.
		 * @return void
		 */
		public function mo2f_set_otp_over_email( $current_user, $selected_method, $session_id_encrypt, $redirect_to ) {
			$twofactor_login = new Miniorange_Password_2Factor_Login();
			$email           = $current_user->user_email;
			$twofactor_login->mo2f_otp_over_email_send( $email, $redirect_to, $session_id_encrypt, $current_user );
		}
		/**
		 * Set google authenticator for a user
		 *
		 * @param object $current_user user object.
		 * @param string $selected_method selected 2FA method.
		 * @param string $google_account_name Google authenticator app name.
		 * @param string $session_id_encrypt encrypted session id.
		 * @return void
		 */
		public function mo2f_set_google_authenticator( $current_user, $selected_method, $google_account_name, $session_id_encrypt ) {
			global $mo2fdb_queries;
			$mo2fdb_queries->update_user_details(
				$current_user->ID,
				array(
					'mo2f_configured_2fa_method' => $selected_method,
				)
			);
			include_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			$gauth_obj = new Google_auth_onpremise();

			$onpremise_secret              = $gauth_obj->mo2f_create_secret();
			$issuer                        = get_site_option( 'mo2f_google_appname', DEFAULT_GOOGLE_APPNAME );
			$url                           = $gauth_obj->mo2f_geturl( $onpremise_secret, $issuer, $current_user->user_email );
			$mo2f_google_auth              = array();
			$mo2f_google_auth['ga_qrCode'] = $url;
			$mo2f_google_auth['ga_secret'] = $onpremise_secret;

			MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'secret_ga', $onpremise_secret );
			MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'ga_qrCode', $url );
		}
		/**
		 * Google authenticator screen in inline registration.
		 *
		 * @param object $user currently logged in user.
		 * @return void
		 */
		public function mo2f_show_gauth_screen( $user ) {
			include_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			$obj = new Google_auth_onpremise();
			$obj->mo_g_auth_get_details();
		}
		/**
		 * Set 2fa method for a user
		 *
		 * @param object $current_user currently logged in user.
		 * @param string $selected_method 2fa method seleced by user.
		 * @return mixed
		 */
		public function mo2f_set_user_two_fa( $current_user, $selected_method ) {
			global $mo2fdb_queries;
			$mo2fdb_queries->update_user_details(
				$current_user->ID,
				array(
					'mo2f_configured_2fa_method' => $selected_method,
				)
			);
			$response = array(
				'mo2fa_login_status'  => MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS,
				'mo2fa_login_message' => '',
			);
			return $response;

		}
		/**
		 * Set google authenticator on WordPress user profile.
		 *
		 * @param object $user user object.
		 * @return mixed
		 */
		public function mo2f_user_profile_ga_setup( $user ) {
			global $main_dir;
			include_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
				$gauth_obj        = new Google_auth_onpremise();
				$ga_secret        = $gauth_obj->mo2f_create_secret();
				$issuer           = get_site_option( 'mo2f_google_appname', DEFAULT_GOOGLE_APPNAME );
				$url              = $gauth_obj->mo2f_geturl( $ga_secret, $issuer, $user->user_email );
				$mo2f_google_auth = array(
					'ga_qrCode' => $url,
					'ga_secret' => $ga_secret,
				);
				update_user_meta( $user->ID, 'mo2f_google_auth', wp_json_encode( $mo2f_google_auth ) );
				$otpcode = $gauth_obj->mo2f_get_code( $ga_secret );
				$data    = isset( $mo2f_google_auth ) ? $mo2f_google_auth['ga_qrCode'] : null;
				wp_enqueue_script( 'mo_wpns_min_qrcode_script', $main_dir . '/includes/jquery-qrcode/jquery-qrcode.min.js', array(), MO2F_VERSION, false );
				echo '<div class="mo2f_gauth_column mo2f_gauth_left" >';
				echo '<div class="mo2f_gauth"  data-qrcode=' . esc_attr( $data ) . '></div>';
				echo '</div>';
				return $ga_secret;
		}
		/**
		 * Function to register the kba information with miniOrange.
		 *
		 * @param string $email Email id of user.
		 * @param string $question1 Question 1 selected by user.
		 * @param string $answer1 Answer 1 given by the user.
		 * @param string $question2 Question 2 selected by user.
		 * @param string $answer2 Answer 2 given by the user.
		 * @param string $question3 Question 3 selected by user.
		 * @param string $answer3 Answer 3 given by the user.
		 * @param int    $user_id Id of user.
		 * @return string
		 */
		public function mo2f_register_kba_details( $email, $question1, $answer1, $question2, $answer2, $question3, $answer3, $user_id = null ) {
			$answer1         = md5( strtolower( $answer1 ) );
			$answer2         = md5( strtolower( $answer2 ) );
			$answer3         = md5( strtolower( $answer3 ) );
			$question_answer = array(
				$question1 => $answer1,
				$question2 => $answer2,
				$question3 => $answer3,
			);
			update_user_meta( $user_id, 'mo2f_kba_challenge', $question_answer );
			global $mo2fdb_queries;
			$mo2fdb_queries->update_user_details( $user_id, array( 'mo2f_configured_2FA_method' => MoWpnsConstants::SECURITY_QUESTIONS ) );
			$response = wp_json_encode( array( 'status' => 'SUCCESS' ) );
			return $response;
		}

		/**
		 * Get 2FA method of a user.
		 *
		 * @param object $user user object.
		 * @return string
		 */
		public function mo2f_get_user_2ndfactor( $user ) {
			global $mo2fdb_queries;
			return $mo2fdb_queries->get_user_detail( 'mo2f_configured_2fa_method', $user->ID );
		}

	}

}
