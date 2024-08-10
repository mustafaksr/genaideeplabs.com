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

use TwoFA\Onprem\MO2f_Utility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */

if ( ! class_exists( 'Mo2f_Cloud_Challenge' ) ) {

	/**
	 *  Class contains functions to validate 2FA.
	 */
	class Mo2f_Cloud_Challenge {
		/**
		 * Google Authenticator setup
		 *
		 * @param object $user user object.
		 * @param string $session_id session id.
		 * @return void
		 */
		public function mo2f_gauth_setup( $user, $session_id ) {
			if ( ! get_user_meta( $user->ID, 'mo2f_google_auth', true ) ) {
				Mo2f_Cloud_Utility::mo2f_get_g_a_parameters( $user );
			}
			$mo2f_google_auth = get_user_meta( $user->ID, 'mo2f_google_auth', true );
			$data             = isset( $mo2f_google_auth['ga_qrCode'] ) ? $mo2f_google_auth['ga_qrCode'] : null;
			$ga_secret        = isset( $mo2f_google_auth['ga_secret'] ) ? $mo2f_google_auth['ga_secret'] : null;
			MO2f_Utility::mo2f_set_transient( $session_id, 'secret_ga', $ga_secret );
			MO2f_Utility::mo2f_set_transient( $session_id, 'ga_qrCode', $data );
		}

	}
}
