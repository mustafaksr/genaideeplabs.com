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

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */

require_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'class-mo2f-api.php';

if ( ! class_exists( 'Two_Factor_Setup' ) ) {
	/**
	 * Class contains function to create, update users and to check curl is enabled or not.
	 */
	class Two_Factor_Setup {
		/**
		 * Email id of user.
		 *
		 * @var string
		 */
		public $email;

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

			$url          = MO_HOST_NAME . '/moas/api/auth/register';
			$customer_key = get_option( 'mo2f_customerKey' );
			$q_and_a_list = '[{"question":"' . $question1 . '","answer":"' . $answer1 . '" },{"question":"' . $question2 . '","answer":"' . $answer2 . '" },{"question":"' . $question3 . '","answer":"' . $answer3 . '" }]';
			$field_string = '{"customerKey":"' . $customer_key . '","username":"' . $email . '","questionAnswerList":' . $q_and_a_list . '}';

			$mo2f_api          = new Mo2f_Api();
			$http_header_array = $mo2f_api->get_http_header_array();

			$response = $mo2f_api->mo2f_http_request( $url, $field_string, $http_header_array );
			return $response;

		}
	}
	new Two_Factor_Setup();
}


