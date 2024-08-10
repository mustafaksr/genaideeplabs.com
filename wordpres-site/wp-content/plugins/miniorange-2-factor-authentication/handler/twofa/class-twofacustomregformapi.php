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
 * @license        http://www.gnu.org/copyleft/gpl.html MIT/Expat, see LICENSE.php
 */

namespace TwoFA\Handler;

use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */
require_once 'class-twofamogateway.php';

if ( ! class_exists( 'TwoFACustomRegFormAPI' ) ) {
	/**
	 * Twofa customer registration
	 */
	class TwoFACustomRegFormAPI {

		/**
		 * It is a constructor
		 */
		public function __construct() {
		}
		/**
		 * It will invoke while sending the otp on email
		 *
		 * @param string $phone_number It will carry the phone number .
		 * @param string $email It will carry the email address.
		 * @param string $auth_type_send It will carry the authentication type .
		 * @return void
		 */
		public static function challenge( $phone_number, $email, $auth_type_send ) {
			if ( MoWpnsConstants::OTP_OVER_EMAIL === $auth_type_send ) {
				$auierpyasdc_ry   = MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
				$cmvtywluaw5nt1rq = $auierpyasdc_ry ? $auierpyasdc_ry : 0;
				if ( $cmvtywluaw5nt1rq > 0 ) {
					$response = TwoFAMOGateway::mo_send_otp_token( 'EMAIL', '', $email );
					update_site_option( 'cmVtYWluaW5nT1RQ', $cmvtywluaw5nt1rq - 1 );
				} else {
					$response = array(
						'status'  => 'ERROR',
						'message' => __( 'Email Transaction Limit Exceeded', 'miniorange-2-factor-authentication' ),
					);
					wp_send_json( $response );
				}
			} else {
				$response = TwoFAMOGateway::mo_send_otp_token( MoWpnsConstants::OTP_OVER_SMS, $phone_number, $email );
			}
			if ( isset( $response['status'] ) && isset( $response['message'] ) && 'ERROR' === $response['status'] && strpos( $response['message'], 'curl extension' ) !== false ) {
				$response['message'] = 'Please enable curl extension.';
			}
			if ( isset( $response['phoneDelivery'] ) && isset( $response['phoneDelivery']['contact'] ) ) {
				$response['message'] = MoWpnsMessages::lang_translate( MoWpnsMessages::SENT_OTP ) . ' ' . MO2f_Utility::get_hidden_phone( $response['phoneDelivery']['contact'] ) . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_SENT_OTP );
			} elseif ( isset( $response['emailDelivery'] ) && isset( $response['emailDelivery']['contact'] ) ) {
				$response['message'] = MoWpnsMessages::lang_translate( MoWpnsMessages::SENT_OTP ) . ' ' . MO2f_Utility::get_hidden_phone( $response['emailDelivery']['contact'] ) . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_SENT_OTP );
			} elseif ( isset( $response['message'] ) ) {
				$response['message'] = MoWpnsMessages::lang_translate( $response['message'] );
			}

			wp_send_json( $response );
		}
		/**
		 * It will help to validate the otp token
		 *
		 * @param string $auth_type Authentication type.
		 * @param string $txid It will carry the transaction id .
		 * @param string $otp  It will carry the otp .
		 * @return void
		 */
		public static function validate( $auth_type, $txid, $otp ) {
			$response = TwoFAMOGateway::mo_validate_otp_token( $auth_type, $txid, $otp );
			if ( isset( $response['message'] ) ) {
				$response['message'] = MoWpnsMessages::lang_translate( $response['message'] );
			}
			wp_send_json( $response );
		}
	}
}
