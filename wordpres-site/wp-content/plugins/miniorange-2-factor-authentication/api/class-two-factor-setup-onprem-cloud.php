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

namespace TwoFA\Onprem;

use TwoFA\Onprem\Mo2f_Api;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsMessages;

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */

require_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'class-mo2f-api.php';

if ( ! class_exists( 'Two_Factor_Setup_Onprem_Cloud' ) ) {
	/**
	 * Class contains function to create, update users and to check curl is enabled or not.
	 */
	class Two_Factor_Setup_Onprem_Cloud {
		/**
		 * Email id of user.
		 *
		 * @var string
		 */
		public $email;
		/**
		 * Instantiation of MO2f_Api class.
		 *
		 * @var object
		 */
		private $mo2f_api;

		/**
		 * Constructor of the class
		 */
		public function __construct() {
			$this->mo2f_api = new Mo2f_Api();
		}

		/**
		 * Check mobile authentication status for miniOrange authenticator method.
		 *
		 * @param string $t_id transaction id.
		 * @return mixed
		 */
		public function check_mobile_status( $t_id ) {

			$url               = MO_HOST_NAME . '/moas/api/auth/auth-status';
			$fields            = array(
				'txId' => $t_id,
			);
			$http_header_array = $this->mo2f_api->get_http_header_array();

			return $this->mo2f_api->mo2f_http_request( $url, $fields, $http_header_array );
		}

		/**
		 * Function registers user email id with miniOrange.
		 *
		 * @param string $useremail Email id of user.
		 * @return string
		 */
		public function register_mobile( $useremail ) {

			$url          = MO_HOST_NAME . '/moas/api/auth/register-mobile';
			$customer_key = get_option( 'mo2f_customerKey' );
			$fields       = array(
				'customerId' => $customer_key,
				'username'   => $useremail,
			);

			$http_header_array = $this->mo2f_api->get_http_header_array();

			return $this->mo2f_api->mo2f_http_request( $url, $fields, $http_header_array );
		}

		/**
		 * Function to check user email already exist with miniOrange or not.
		 *
		 * @param string $email Email id of user.
		 * @return string
		 */
		public function mo_check_user_already_exist( $email ) {

			$url               = MO_HOST_NAME . '/moas/api/admin/users/search';
			$customer_key      = get_option( 'mo2f_customerKey' );
			$fields            = array(
				'customerKey' => $customer_key,
				'username'    => $email,
			);
			$http_header_array = $this->mo2f_api->get_http_header_array();

			return $this->mo2f_api->mo2f_http_request( $url, $fields, $http_header_array );
		}
		/**
		 * Function to create user with miniOrange.
		 *
		 * @param object $currentuser Contains details of current user.
		 * @param string $email Email id of user.
		 * @return string
		 */
		public function mo_create_user( $currentuser, $email ) {

			$url               = MO_HOST_NAME . '/moas/api/admin/users/create';
			$customer_key      = get_option( 'mo2f_customerKey' );
			$fields            = array(
				'customerKey' => $customer_key,
				'username'    => $email,
				'firstName'   => $currentuser->user_firstname,
				'lastName'    => $currentuser->user_lastname,
			);
			$http_header_array = $this->mo2f_api->get_http_header_array();

			return $this->mo2f_api->mo2f_http_request( $url, $fields, $http_header_array );
		}
		/**
		 * Function to get remaining otp transactions of the user.
		 *
		 * @param int    $c_key Customer key of the user.
		 * @param string $api_key Api key of the user.
		 * @param string $license_type License type assigned by miniOrange to check whether the user is onPremise or cloud.
		 * @return string
		 */
		public function get_customer_transactions( $c_key, $api_key, $license_type ) {
			$url = MO_HOST_NAME . '/moas/rest/customer/license';

			$customer_key = $c_key;
			$api_key      = $api_key;

			$fields = '';
			if ( 'DEMO' === $license_type ) {
				$fields = array(
					'customerId'      => $customer_key,
					'applicationName' => '-1',
					'licenseType'     => $license_type,
				);
			} else {
				$fields = array(
					'customerId'      => $customer_key,
					'applicationName' => 'otp_recharge_plan',
					'licenseType'     => $license_type,
				);
			}

			$field_string = wp_json_encode( $fields );

			$headers = $this->mo2f_api->get_http_header_array();

			$content = $this->mo2f_api->mo2f_http_request( $url, $field_string, $headers );

			return $content;
		}

		/**
		 * Sends the OTP over Telegram.
		 *
		 * @param string $u_key Email.
		 * @param object $currentuser Current user.
		 * @return array
		 */
		public function mo2f_send_telegram_otp( $u_key, $currentuser ) {

			update_user_meta( $currentuser->ID, 'mo2f_temp_chatID', $u_key );

			$otp_token = '';
			for ( $i = 1; $i < 7; $i++ ) {
				$otp_token .= wp_rand( 0, 9 );
			}
			update_user_meta( $currentuser->ID, 'mo2f_otp_token', $otp_token );
			update_user_meta( $currentuser->ID, 'mo2f_telegram_time', time() );

			$url      = esc_url( MoWpnsConstants::TELEGRAM_OTP_LINK );
			$postdata = array(
				'mo2f_otp_token' => $otp_token,
				'mo2f_chatid'    => $u_key,
			);

			$args = array(
				'method'    => 'POST',
				'timeout'   => 10,
				'sslverify' => false,
				'headers'   => array(),
				'body'      => $postdata,
			);

			$mo2f_api = new Mo2f_Api();
			$data     = $mo2f_api->mo2f_wp_remote_post( $url, $args );
			$content  = array( 'status' => $data );

			return $content;
		}

		/**
		 * Validates OTP for Telegram.
		 *
		 * @param string $auth_type Auth type.
		 * @param string $otp_token Otp token.
		 * @param object $current_user Current user.
		 * @return array
		 */
		public function mo2f_validate_telegram_code( $auth_type, $otp_token, $current_user ) {
			$otp           = get_user_meta( $current_user->ID, 'mo2f_otp_token', true );
			$time          = get_user_meta( $current_user->ID, 'mo2f_telegram_time', true );
			$accepted_time = time() - 300;
			$time          = (int) $time;
			if ( (int) ( $otp ) === (int) $otp_token ) {
				if ( $accepted_time < $time ) {
					$content = array( 'status' => 'SUCCESS' );

				} else {
					$content = array(
						'status'  => 'ERROR',
						'message' => 'OTP has been expired please reinitiate another transaction.',
					);

					delete_user_meta( $current_user->ID, 'mo2f_telegram_time' );
				}
			} else {
				$content = array(
					'status'  => 'INVALID_OTP',
					'message' => MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ),
				);
			}
			return $content;
		}
	}
}

