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
 * @package        miniorange-2-factor-authentication/api
 */

namespace TwoFA\Cloud;

use TwoFA\Onprem\Mo2f_Api;
use TwoFA\Onprem\Miniorange_Authentication;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Traits\Instance;
use TwoFA\Helper\MocURL;
use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Onprem\Two_Factor_Setup_Onprem_Cloud;
use TwoFA\Helper\MoWpnsMessages;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */

if ( ! class_exists( 'Customer_Cloud_Setup' ) ) {

	/**
	 *  Class contains functions to setup customer with miniOrange.
	 */
	class Customer_Cloud_Setup {
		use Instance;

		/**
		 * Email id of user.
		 *
		 * @var $email string.
		 */
		public $email;
		/**
		 * Phone number of user.
		 *
		 * @var int.
		 */
		public $phone;
		/**
		 * Customer key of user.
		 *
		 * @var string
		 */
		public $customer_key;
		/**
		 * Transaction id of the customer to send OTP via SMS or Email.
		 *
		 * @var string
		 */
		public $transaction_id;
		/**
		 * Instantiation of Mo2f_Api class.
		 *
		 * @var object
		 */
		private $mo2f_api;

		/**
		 * Constructor of the class.
		 */
		public function __construct() {
			$this->mo2f_api = Mo2f_Api::instance();
		}
		/**
		 * Function to add the customer on miniOrange idp.
		 *
		 * @return string
		 */
		public function create_customer() {
			global $mo2fdb_queries;
			$url = MO_HOST_NAME . '/moas/rest/customer/add';
			global $user;
			$user        = wp_get_current_user();
			$this->email = get_option( 'mo2f_email' );
			$this->phone = $mo2fdb_queries->get_user_detail( 'mo2f_user_phone', $user->ID );
			$password    = get_option( 'mo_wpns_password' );
			$company     = get_option( 'mo_wpns_company' ) !== '' ? get_option( 'mo_wpns_company' ) : ( isset( $_SERVER['SERVER_NAME'] ) ? esc_url_raw( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : null );

			$fields       = array(
				'companyName'     => $company,
				'areaOfInterest'  => 'WordPress 2 Factor Authentication Plugin',
				'productInterest' => '',
				'email'           => $this->email,
				'phone'           => $this->phone,
				'password'        => $password,
			);
			$field_string = wp_json_encode( $fields );

			$headers = array(
				'Content-Type'  => 'application/json',
				'charset'       => 'UTF-8',
				'Authorization' => 'Basic',
			);

			$content = $this->mo2f_api->mo2f_http_request( $url, $field_string, $headers );

			return $content;
		}

		/**
		 * Function to get customer key of user.
		 *
		 * @return string
		 */
		public function get_customer_key() {

			$url = MO_HOST_NAME . '/moas/rest/customer/key';

			$email        = get_option( 'mo2f_email' );
			$password     = get_option( 'mo2f_password' );
			$fields       = array(
				'email'    => $email,
				'password' => $password,
			);
			$field_string = wp_json_encode( $fields );

			$headers = array(
				'Content-Type'  => 'application/json',
				'charset'       => 'UTF-8',
				'Authorization' => 'Basic',
			);

			$content = $this->mo2f_api->mo2f_http_request( $url, $field_string );

			return $content;
		}

		/**
		 * Update user info on cloud.
		 *
		 * @param int     $user_id User id.
		 * @param string  $config_status 2FA configuration status.
		 * @param string  $twofa_method 2fa method of user.
		 * @param string  $user_registration User registrtion status.
		 * @param string  $twofa_reg_status 2fa registration status.
		 * @param boolean $twofa_by_user Enable 2fa by users.
		 * @param string  $email User's email.
		 * @param string  $phone User's phone.
		 * @param string  $transaction_name 2fa transactin name.
		 * @param boolean $enable_admin_2fa 2fa enable for admin.
		 * @return mixed
		 */
		public function mo2f_update_user_info( $user_id, $config_status, $twofa_method, $user_registration, $twofa_reg_status, $twofa_by_user, $email, $phone = null, $transaction_name = null, $enable_admin_2fa = null ) {
			$mocurl  = new MocURL();
			$content = $mocurl->mo2f_update_user_info( $email, $twofa_method, $phone, $transaction_name, $enable_admin_2fa );

			if ( isset( $user_id ) ) {
				$update_details = new Miniorange_Password_2Factor_Login();
				$update_details->mo2fa_update_user_details( $user_id, $config_status, $twofa_method, $user_registration, $twofa_reg_status, $twofa_by_user, $email, $phone );
			}

			return $content;
		}

		/**
		 * Function to send otp to the user via miniOrange service.
		 *
		 * @param string $u_key It can be a phone number or email id to which the otp to be sent.
		 * @param string $auth_type Authentication method of the user.
		 * @param string $customer_key Customer key of the user.
		 * @param string $api_key Api key of the user.
		 * @param object $currentuser Contains details of current user.
		 * @return string
		 */
		public function send_otp_token( $u_key, $auth_type, $customer_key, $api_key, $currentuser ) {
			$url     = MO_HOST_NAME . '/moas/api/auth/challenge';
			$headers = $this->mo2f_api->get_http_header_array();
			$fields  = '';
			if ( MoWpnsConstants::OTP_OVER_EMAIL === $auth_type || MoWpnsConstants::OUT_OF_BAND_EMAIL === $auth_type ) {
				$fields = array(
					'customerKey'     => $customer_key,
					'email'           => $u_key,
					'authType'        => $auth_type,
					'transactionName' => 'WordPress 2 Factor Authentication Plugin',
				);
			} elseif ( MoWpnsConstants::OTP_OVER_SMS === $auth_type ) {
				$fields = array(
					'customerKey' => $customer_key,
					'phone'       => $u_key,
					'authType'    => $auth_type,
				);
			} elseif ( MoWpnsConstants::OTP_OVER_TELEGRAM === $auth_type ) {
				$mo2f_onprem_cloud_api = new Two_Factor_Setup_Onprem_Cloud();
				return $mo2f_onprem_cloud_api->mo2f_send_telegram_otp( $u_key, $currentuser );
			} else {
				$fields = array(
					'customerKey'     => $customer_key,
					'username'        => $u_key,
					'authType'        => $auth_type,
					'transactionName' => 'WordPress 2 Factor Authentication Plugin',
				);
			}

			$field_string = wp_json_encode( $fields );

			$content = $this->mo2f_api->mo2f_http_request( $url, $field_string, $headers );

			$content1 = json_decode( $content, true );
			if ( 'SUCCESS' === $content1['status'] ) {
				if ( 4 === get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) && MoWpnsConstants::OTP_OVER_SMS === $auth_type ) {
					Miniorange_Authentication::mo2f_low_otp_alert( 'sms' );
				}
				if ( 5 === get_site_option( 'cmVtYWluaW5nT1RQ' ) && ( MoWpnsConstants::OTP_OVER_EMAIL === $auth_type || MoWpnsConstants::OUT_OF_BAND_EMAIL === $auth_type ) ) {
					Miniorange_Authentication::mo2f_low_otp_alert( 'email' );
				}
			}

			return $content;
		}

		/**
		 * Function to validate the otp token.
		 *
		 * @param string $auth_type Authentication method of user.
		 * @param string $username Username of user.
		 * @param string $transaction_id Transaction id which is used to validate the sent otp token.
		 * @param string $otp_token OTP token received by user.
		 * @param string $c_key Customer key of user.
		 * @param string $customer_api_key Customer api key assigned by IDP to the user.
		 * @param object $current_user Contains details of current user.
		 * @return string
		 */
		public function validate_otp_token( $auth_type, $username, $transaction_id, $otp_token, $c_key, $customer_api_key, $current_user = null ) {
			$content = '';
			$url     = MO_HOST_NAME . '/moas/api/auth/validate';
			/* The customer Key provided to you */
			$customer_key = $c_key;

			/* The customer API Key provided to you */
			$api_key = $customer_api_key;

			/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
			$current_time_in_millis = $this->mo2f_api->get_timestamp();

			/* Creating the Hash using SHA-512 algorithm */
			$string_to_hash = $customer_key . $current_time_in_millis . $api_key;
			$hash_value     = hash( 'sha512', $string_to_hash );

			$headers = $this->mo2f_api->get_http_header_array();
			$fields  = '';
			if ( MoWpnsConstants::SOFT_TOKEN === $auth_type || MoWpnsConstants::GOOGLE_AUTHENTICATOR === $auth_type ) {
				/*check for soft token*/
				$fields = array(
					'customerKey' => $customer_key,
					'username'    => $username,
					'token'       => $otp_token,
					'authType'    => $auth_type,
				);
			} elseif ( MoWpnsConstants::SECURITY_QUESTIONS === $auth_type ) {
				$fields = array(
					'txId'    => $transaction_id,
					'answers' => array(
						array(
							'question' => $otp_token[0],
							'answer'   => $otp_token[1],
						),
						array(
							'question' => $otp_token[2],
							'answer'   => $otp_token[3],
						),
					),
				);
			} elseif ( MoWpnsConstants::OTP_OVER_TELEGRAM === $auth_type ) {
				$mo2f_onprem_cloud_api = new Two_Factor_Setup_Onprem_Cloud();
				return $mo2f_onprem_cloud_api->mo2f_validate_telegram_code( $auth_type, $otp_token, $current_user );
			} else {
				// *check for otp over sms/email
				$fields = array(
					'txId'  => $transaction_id,
					'token' => $otp_token,
				);
			}
			$field_string = wp_json_encode( $fields );

			$content = $this->mo2f_api->mo2f_http_request( $url, $field_string, $headers );
			return $content;
		}
		/**
		 * Function to raise support query.
		 *
		 * @param string $q_email Email id of customer to be sent to the query.
		 * @param int    $q_phone Phone number of customer to be sent to the query.
		 * @param string $query Query raised by the customer.
		 * @return boolean
		 */
		public function submit_contact_us( $q_email, $q_phone, $query ) {

			$url = MO_HOST_NAME . '/moas/rest/customer/contact-us';
			global $user;
			$user              = wp_get_current_user();
			$is_nc_with_1_user = MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NC', 'get_option' ) && MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NNC', 'get_option' );
			$is_ec_with_1_user = ! MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NC', 'get_option' );

			$customer_feature = '';

			if ( $is_ec_with_1_user ) {
				$customer_feature = 'V1';
			} elseif ( $is_nc_with_1_user ) {
				$customer_feature = 'V3';
			}
			global $mo_wpns_utility;
			$query        = '[WordPress 2 Factor Authentication Plugin: ' . $customer_feature . ' - V ' . MO2F_VERSION . ' ]: ' . $query;
			$fields       = array(
				'firstName' => $user->user_firstname,
				'lastName'  => $user->user_lastname,
				'company'   => isset( $_SERVER['SERVER_NAME'] ) ? esc_url_raw( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : null,
				'email'     => $q_email,
				'ccEmail'   => 'mfasupport@xecurify.com',
				'phone'     => $q_phone,
				'query'     => $query,
			);
			$field_string = wp_json_encode( $fields );

			$headers = array(
				'Content-Type'  => 'application/json',
				'charset'       => 'UTF-8',
				'Authorization' => 'Basic',
			);

			$content = $this->mo2f_api->mo2f_http_request( $url, $field_string );

			return true;
		}
		/**
		 * Google Authenticator setup
		 *
		 * @param object $user user object.
		 * @param string $session_id session id.
		 * @return void
		 */
		public function mo2f_gauth_setup( $user, $session_id ) {
			$cloud_setup = new Mo2f_Cloud_Challenge();
			$cloud_setup->mo2f_gauth_setup( $user, $session_id );
		}
		/**
		 * Sends email verification link to the users email.
		 *
		 * @param string $user_email user email.
		 * @param string $mo2f_second_factor 2FA method of user.
		 * @param string $mo2f_customer_key customer key of the miniorange customer.
		 * @param string $mo2f_api_key API key.
		 * @param string $current_user Current User.
		 * @return mixed
		 */
		public function mo2f_send_verification_link( $user_email, $mo2f_second_factor, $mo2f_customer_key, $mo2f_api_key, $current_user ) {
			$content = $this->send_otp_token( $user_email, $mo2f_second_factor, $mo2f_customer_key, $mo2f_api_key, $current_user );
			return $content;
		}
		/**
		 * Set Google authenticator secret key.
		 *
		 * @param int    $user_id User ID.
		 * @param string $email User email.
		 * @param string $ga_secret Google authenticator secret key.
		 * @return mixed
		 */
		public function mo2f_set_gauth_secret( $user_id, $email, $ga_secret ) {
			$google_auth     = new Mo2f_Cloud_Utility();
			$google_response = json_decode( $google_auth->mo2f_google_auth_service( $email, 'miniOrangeAu' ), true );
			return $google_response;
		}

		/**
		 * Set Up email verification
		 *
		 * @param object $current_user current user object.
		 * @param string $default_customer_key customer key.
		 * @param string $default_api_key API key.
		 * @return void
		 */
		public function mo2f_email_verification_call( $current_user, $default_customer_key, $default_api_key ) {
			$email        = $current_user->user_email;
			$content      = $this->send_otp_token( $email, MoWpnsConstants::OUT_OF_BAND_EMAIL, $default_customer_key, $default_api_key, $current_user );
			$response     = json_decode( $content, true );
			$show_message = new MoWpnsMessages();
			if ( json_last_error() === JSON_ERROR_NONE ) { /* Generate out of band email */
				if ( isset( $response['status'] ) && 'ERROR' === $response['status'] ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $response['message'] ), 'ERROR' );
				} else {
					if ( 'SUCCESS' === $response['status'] ) {
						update_user_meta( $current_user->ID, 'mo2f_transactionId', $response['txId'] );
						update_option( 'mo2f_transactionId', $response['txId'] );
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::VERIFICATION_EMAIL_SENT ) . '<b> ' . $email . '</b>. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ACCEPT_LINK_TO_VERIFY_EMAIL ), 'SUCCESS' );
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
					}
				}
			} else {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ), 'ERROR' );

			}
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
			$current_user    = get_userdata( $current_user->ID );
			$email           = $current_user->user_email;
			$twofactor_login = new Miniorange_Password_2Factor_Login();
			$response        = $twofactor_login->create_user_in_miniorange( $current_user->ID, $email, $selected_method );

			if ( isset( $response['status'] ) && 'ERROR' === $response['status'] ) {
				$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;
				$mo2fa_login_message = $response['message'] . 'Skip the two-factor for login';
			} else {

				$mo2fdb_queries->update_user_details(
					$current_user->ID,
					array(
						'mo2f_email_verification_status' => true,
						'mo2f_configured_2fa_method'     => MoWpnsConstants::OUT_OF_BAND_EMAIL,
						'mo2f_user_email'                => $email,
					)
				);
				$mo2fa_login_status  = 'MO_2_FACTOR_SETUP_SUCCESS';
				$mo2fa_login_message = '';
			}
			return array(
				'mo2fa_login_status'  => $mo2fa_login_status,
				'mo2fa_login_message' => $mo2fa_login_message,
			);
		}
		/**
		 * Set OTP over email method for a user.
		 *
		 * @param object $current_user user object.
		 * @param string $selected_method selcted 2fa method.
		 * @param string $session_id_encrypt encrypted session id.
		 * @param string $redirect_to redirect url.
		 * @return mixed
		 */
		public function mo2f_set_otp_over_email( $current_user, $selected_method, $session_id_encrypt, $redirect_to ) {
			global $mo2fdb_queries;
			$twofactor_login = new Miniorange_Password_2Factor_Login();
			$email           = $current_user->user_email;
			$response        = $twofactor_login->create_user_in_miniorange( $current_user->ID, $email, $selected_method );
			if ( isset( $response['status'] ) && 'ERROR' === $response['status'] || null === $response ) {
				$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;
				$mo2fa_login_message = isset( $response['message'] ) ? $response['message'] : 'An unknown error occured.';
				$mo2fa_login_message = $mo2fa_login_message . ' Please try again or select another method.';
				return array(
					'mo2fa_login_status'  => $mo2fa_login_status,
					'mo2fa_login_message' => $mo2fa_login_message,
				);
			} else {
				$user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $current_user->ID );
				if ( ! empty( $user_email ) && ! is_null( $user_email ) ) {
					$email = $user_email;
				}
				$twofactor_login->mo2f_otp_over_email_send( $email, $redirect_to, $session_id_encrypt, $current_user );
			}
		}
		/**
		 * Set google authenticator for a user
		 *
		 * @param object $current_user user object.
		 * @param string $selected_method selected 2FA method.
		 * @param string $google_account_name Google authenticator app name.
		 * @param string $session_id_encrypt encrypted session id.
		 * @return array
		 */
		public function mo2f_set_google_authenticator( $current_user, $selected_method, $google_account_name, $session_id_encrypt ) {
			global $mo2fdb_queries;
			$email     = $current_user->user_email;
			$tempemail = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $current_user->ID );

			if ( ! isset( $tempemail ) && ! is_null( $tempemail ) && ! empty( $tempemail ) ) {
				$email = $tempemail;
			}
			$twofactor_login     = new Miniorange_Password_2Factor_Login();
			$response            = $twofactor_login->create_user_in_miniorange( $current_user->ID, $email, $selected_method );
			$mo2fa_login_message = '';
			$mo2fa_login_status  = '';
			if ( isset( $response['status'] ) && 'ERROR' === $response['status'] ) {
				$mo2fa_login_message = $response['message'];
				$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;
			} else {
				$mo2fdb_queries->update_user_details(
					$current_user->ID,
					array(
						'mo2f_configured_2fa_method' => $selected_method,
					)
				);
				$google_auth     = new Mo2f_Cloud_Utility();
				$google_response = json_decode( $google_auth->mo2f_google_auth_service( $email, $google_account_name ), true );
				if ( JSON_ERROR_NONE === json_last_error() ) {
					if ( 'SUCCESS' === $google_response['status'] ) {
						$mo2f_google_auth              = array();
						$mo2f_google_auth['ga_qrCode'] = $google_response['qrCodeData'];
						$mo2f_google_auth['ga_secret'] = $google_response['secret'];

						MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'secret_ga', $mo2f_google_auth['ga_secret'] );
						MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'ga_qrCode', $mo2f_google_auth['ga_qrCode'] );
					} else {
						$mo2fa_login_message = __( 'Invalid request. Please register with miniOrange to configure 2 Factor plugin.', 'miniorange-2-factor-authentication' );
					}
				}
			}
			return array(
				'mo2fa_login_status'  => $mo2fa_login_status,
				'mo2fa_login_message' => $mo2fa_login_message,
			);
		}
		/**
		 * Google authenticator screen in inline registration.
		 *
		 * @param object $user currently logged in user.
		 * @return void
		 */
		public function mo2f_show_gauth_screen( $user ) {
			Mo2f_Cloud_Utility::mo2f_get_g_a_parameters( $user );
			echo '<div class="mo2f_table_layout mo2f_table_layout1">';
			$setup_dir_name = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR;
			require_once $setup_dir_name . 'setup-google-authenticator.php';
			mo2f_configure_google_authenticator_cloud( $user );
			echo '</div>';

		}
		/**
		 * Set 2fa method for a user
		 *
		 * @param object $current_user currently logged in user.
		 * @param string $selected_method 2fa method seleced by user.
		 * @return array
		 */
		public function mo2f_set_user_two_fa( $current_user, $selected_method ) {
			global $mo2fdb_queries;
			$twofactor_login     = new Miniorange_Password_2Factor_Login();
			$current_user        = get_userdata( $current_user->ID );
			$email               = $current_user->user_email;
			$mo2fa_login_message = '';
			$response            = $twofactor_login->create_user_in_miniorange( $current_user->ID, $email, $selected_method );
			if ( ! is_null( $response ) && 'ERROR' === $response['status'] ) {
				$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;
				$mo2fa_login_message = $response['message'] . 'Skip the two-factor for login';
			} else {
				$mo2fdb_queries->update_user_details( $current_user->ID, array( 'mo2f_configured_2fa_method' => $selected_method ) );
			}

			return array(
				'mo2fa_login_status'  => MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS,
				'mo2fa_login_message' => $mo2fa_login_message,
			);
		}
		/**
		 * Set google authenticator on WordPress user profile.
		 *
		 * @param object $user user object.
		 * @return mixed
		 */
		public function mo2f_user_profile_ga_setup( $user ) {
			if ( ! get_user_meta( $user->ID, 'mo2f_google_auth', true ) ) {
				Mo2f_Cloud_Utility::mo2f_get_g_a_parameters( $user );
			}
			$mo2f_google_auth = get_user_meta( $user->ID, 'mo2f_google_auth', true );
			$data             = isset( $mo2f_google_auth['ga_qrCode'] ) ? $mo2f_google_auth['ga_qrCode'] : null;
			$ga_secret        = isset( $mo2f_google_auth['ga_secret'] ) ? $mo2f_google_auth['ga_secret'] : null;
			echo '<br><div id="displayQrCode">
			<img id="mo2f_gauth" style="line-height: 0;background:white;" src="data:image/jpg;base64,' . esc_attr( $data ) . '" />
			</div>';
			return $ga_secret;
		}


		/**
		 * KBA verification function.
		 *
		 * @param int    $user_id User id.
		 * @param string $session_id Session id.
		 * @param string $redirect_to Redirect url.
		 * @return mixed
		 */
		public function mo2f_login_kba_verification( $user_id, $session_id, $redirect_to ) {
			$kba_verification = new Mo2f_Cloud_Validate();
			return $kba_verification->mo2f_login_kba_verification( $user_id, $session_id, $redirect_to );
		}

		/**
		 * This function returns status of 2nd factor
		 *
		 * @param object $user object containing user details.
		 * @return string
		 */
		public function mo2f_get_user_2ndfactor( $user ) {
			global $mo2fdb_queries;
			$mo2f_user_email        = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
			$auth_type              = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2fa_method', $user->ID );
			$is_telegram_configured = MoWpnsConstants::OTP_OVER_TELEGRAM === $auth_type;
			if ( $is_telegram_configured ) {
				$mo2f_second_factor = $auth_type;
			} else {
				$enduser  = new MocURL();
				$userinfo = json_decode( $enduser->mo2f_get_userinfo( $mo2f_user_email ), true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					if ( 'ERROR' === $userinfo['status'] ) {
						$mo2f_second_factor = 'NONE';
					} elseif ( 'SUCCESS' === $userinfo['status'] ) {
						$mo2f_second_factor = $userinfo['authType'];
					} elseif ( 'FAILED' === $userinfo['status'] ) {
						$mo2f_second_factor = 'USER_NOT_FOUND';
					} else {
						$mo2f_second_factor = 'NONE';
					}
				} else {
					$mo2f_second_factor = 'NONE';
				}
			}

			return $mo2f_second_factor;
		}

		/**
		 * Register KBA on cloud.
		 *
		 * @param string $email user email.
		 * @param string $question1 kba question 1.
		 * @param string $question2 kba question 2.
		 * @param string $question3 kba question 3.
		 * @param string $answer1 kba answer 1.
		 * @param string $answer2 kba answer 2.
		 * @param string $answer3 kba answer 3.
		 * @return mixed
		 */
		public function mo2f_cloud_register_kba( $email, $question1, $question2, $question3, $answer1, $answer2, $answer3 ) {
			$response = new Mo2f_Cloud_Utility();

			return $response->mo2f_cloud_register_kba( $email, $question1, $question2, $question3, $answer1, $answer2, $answer3 );
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
			$customer = new Mo2f_Cloud_Validate();
			$content  = $customer->mo2f_google_auth_validate( $useremail, $otptoken, $secret );

			return $content;
		}
	}
}
