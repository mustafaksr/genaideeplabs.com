<?php
/** The miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
 * Copyright (C) 2015  miniOrange
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
 * @package        miniorange-2-factor-authentication/handler/twofa
 */

namespace TwoFA\Handler;

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Onprem\MO2f_Cloud_Onprem_Interface;
use TwoFA\Helper\TwoFAMoSessions;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */
global $mo2f_dir_name;

require_once $mo2f_dir_name . 'helper' . DIRECTORY_SEPARATOR . 'class-twofamosessions.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'MO2F_DEFAULT_APIKEY', 'fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq' );
define( 'MO2F_FAIL_MODE', false );
define( 'MO2F_SESSION_TYPE', 'TRANSIENT' );

if ( ! class_exists( 'TwoFAMOGateway' ) ) {
	/**
	 * Twofa Gatewayclass class
	 */
	class TwoFAMOGateway {

		/**
		 * It will help to send the otp
		 *
		 * @param string $auth_type .
		 * @param string $phone .
		 * @param string $email .
		 * @return string
		 */
		public static function mo_send_otp_token( $auth_type, $phone, $email ) {
			if ( MO2F_TEST_MODE ) {
				return array(
					'message' => 'OTP Sent Successfully',
					'status'  => 'SUCCESS',
					'txid'    => wp_rand( 1000, 9999 ),
				);
			} else {
				$customer_key = get_site_option( 'mo2f_customerKey' );
				$api_key      = get_site_option( 'mo2f_api_key' );
				TwoFAMoSessions::add_session_var( 'mo2f_transactionId', true );
				TwoFAMoSessions::add_session_var( 'sent_on', time() );

				if ( MoWpnsConstants::OTP_OVER_EMAIL === $auth_type ) {
					$cmvtywluaw5nt1rq = MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
					if ( $cmvtywluaw5nt1rq > 0 ) {
						update_site_option( 'cmVtYWluaW5nT1RQ', $cmvtywluaw5nt1rq - 1 );
					}
					$content = ( new MO2f_Cloud_Onprem_Interface() )->send_otp_token( $email, $auth_type, $customer_key, $api_key, null );
				} else {
					$mo2f_sms = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' );
					if ( $mo2f_sms > 0 ) {
						update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', $mo2f_sms - 1 );
					}

					$content = ( new MO2f_Cloud_Onprem_Interface() )->send_otp_token( $phone, $auth_type, $customer_key, $api_key, null );
				}
				return json_decode( $content, true );
			}
		}
		/**
		 * It will help to validate the request
		 *
		 * @param string $auth_type .
		 * @param string $txid .
		 * @param string $otp_token .
		 * @return string
		 */
		public static function mo_validate_otp_token( $auth_type, $txid, $otp_token ) {
			if ( MO2F_TEST_MODE ) {
				TwoFAMoSessions::unset_session( 'mo2f_transactionId' );
				return MO2F_FAIL_MODE ? array(
					'status'  => 'FAILED',
					'message' => 'OTP is Invalid',
				) : array(
					'status'  => 'SUCCESS',
					'message' => 'Successfully Validated',
				);
			} else {
				$content = '';
				if ( TwoFAMoSessions::get_session_var( 'mo2f_transactionId' ) ) {
					$customer_key = get_site_option( 'mo2f_customerKey' );
					$api_key      = get_site_option( 'mo2f_api_key' );
					$content      = ( new MO2f_Cloud_Onprem_Interface() )->validate_otp_token( strtoupper( $auth_type ), null, $txid, $otp_token, $customer_key, $api_key );
					$content      = json_decode( $content, true );
					if ( 'SUCCESS' === $content['status'] ) {
						TwoFAMoSessions::unset_session( 'mo2f_transactionId' );
					}
				}
				return $content;
			}
		}
	}
}
