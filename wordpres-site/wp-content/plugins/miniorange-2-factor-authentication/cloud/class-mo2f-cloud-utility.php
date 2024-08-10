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
 * @package        miniorange-2-factor-authentication/cloud
 */

namespace TwoFA\Cloud;

use TwoFA\OnPrem\Mo2f_Api;
use TwoFA\Helper\MoWpnsMessages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */

if ( ! class_exists( 'Mo2f_Cloud_Validate' ) ) {

	/**
	 *  Class contains functions to validate 2FA.
	 */
	class Mo2f_Cloud_Utility {

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
		 * Get google authenticators parameters.
		 *
		 * @param object $user User object.
		 * @return void
		 */
		public static function mo2f_get_g_a_parameters( $user ) {
			global $mo2fdb_queries;
			$email           = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
			$gauth_name      = get_option( 'mo2f_google_appname' );
			$gauth_name      = $gauth_name ? $gauth_name : 'miniOrangeAu';
			$gauth_setup     = new Mo2f_Cloud_Utility();
			$google_response = json_decode( $gauth_setup->mo2f_google_auth_service( $email, $gauth_name ), true );
			$show_message    = new MoWpnsMessages();
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $google_response['status'] ) {
					$mo2f_google_auth              = array();
					$mo2f_google_auth['ga_qrCode'] = $google_response['qrCodeData'];
					$mo2f_google_auth['ga_secret'] = $google_response['secret'];
					update_user_meta( $user->ID, 'mo2f_google_auth', $mo2f_google_auth );
				} else {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_USER_REGISTRATION ), 'ERROR' );
				}
			} else {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_USER_REGISTRATION ), 'ERROR' );
			}
		}
		/**
		 * This function perform google authentication task.
		 *
		 * @param string $useremail user email.
		 * @param string $google_authenticator_name google auth name.
		 * @return string
		 */
		public function mo2f_google_auth_service( $useremail, $google_authenticator_name = '' ) {

			$url          = MO_HOST_NAME . '/moas/api/auth/google-auth-secret';
			$customer_key = get_option( 'mo2f_customerKey' );
			$field_string = array(
				'customerKey'       => $customer_key,
				'username'          => $useremail,
				'authenticatorName' => $google_authenticator_name,
			);

			$http_header_array = $this->mo2f_api->get_http_header_array();

			return $this->mo2f_api->mo2f_http_request( $url, $field_string, $http_header_array );
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
			$url          = MO_HOST_NAME . '/moas/api/auth/register';
			$customer_key = get_option( 'mo2f_customerKey' );
			$q_and_a_list = '[{"question":"' . $question1 . '","answer":"' . $answer1 . '" },{"question":"' . $question2 . '","answer":"' . $answer2 . '" },{"question":"' . $question3 . '","answer":"' . $answer3 . '" }]';
			$field_string = '{"customerKey":"' . $customer_key . '","username":"' . $email . '","questionAnswerList":' . $q_and_a_list . '}';

			$http_header_array = $this->mo2f_api->get_http_header_array();

			$response = $this->mo2f_api->mo2f_http_request( $url, $field_string, $http_header_array );
			return $response;
		}
	}
	new Mo2f_Cloud_Utility();

}
