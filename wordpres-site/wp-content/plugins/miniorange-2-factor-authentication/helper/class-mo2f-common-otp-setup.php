<?php
/**
 * This file contains OTP based methods' configuration functions.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

namespace TwoFA\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use TwoFA\Helper\MoWpnsConstants;

if ( ! class_exists( 'Mo2f_Common_Otp_Setup' ) ) {

	/**
	 * Class Mo2f_Common_Otp_Setup.
	 */
	class Mo2f_Common_Otp_Setup {

		/**
		 * Returns skeleton values for OTP Over SMS.
		 *
		 * @param int $user_id User ID.
		 * @return array
		 */
		public function mo2f_sms_common_skeleton( $user_id ) {
			global $mo2fdb_queries;
			$mo2f_user_phone = $mo2fdb_queries->get_user_detail( 'mo2f_user_phone', $user_id );
			$user_phone      = $mo2f_user_phone ? $mo2f_user_phone : get_user_meta( $user_id, 'user_phone_temp', true );
			$skeleton        = array(
				'##input_field##'            => '<span style="font-size:17px;"><i>Enter your Phone:</i></span><br><br><input class="mo2f_table_textbox" style="width:200px;" type="text" name="mo2f_phone_email_telegram" id="phone"
                                    value="' . esc_attr( $user_phone ) . '" pattern="[\+]?[0-9]{1,4}\s?[0-9]{7,12}"
                                    title="' . esc_attr__( 'Enter phone number without any space or dashes', 'miniorange-2-factor-authentication' ) . '"/>',
				'##remaining_transactions##' => '<h3 class="mo2f_sms_rem_transac" style="font-size:20px;"> Remaining SMS Transactions: <b><i>' . intval( esc_html( get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) ) ) . '</i></b>
									<a id="mo2f_transactions_check" style="margin-left:40px;" class="button button-primary mo2f_check_sms">Refresh Available SMS</a>
                                    </h3><br>',
				'##instructions##'           => '',
			);
			return $skeleton;
		}

		/**
		 * Returns skeleton values for OTP Over Email.
		 *
		 * @param int $user_id User ID.
		 * @return array
		 */
		public function mo2f_email_common_skeleton( $user_id ) {
			global $two_factor_premium_doc, $mo2fdb_queries;
			require dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
			$smtp_message = current_user_can( 'administrator' ) ? '<br><i style="font-size:13px;">NOTE :- If you haven\'t configured SMTP, please set your SMTP to get the OTP over email.</i>
				<a href="' . esc_url( $two_factor_premium_doc['Setup SMTP'] ) . '" target="_blank">
					<span title="View Setup Guide" class="dashicons dashicons-text-page mo2f-setup-guide"></span>
				</a>' : '';
			if ( ! $user_id && is_user_logged_in() ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
			}
			$mo2f_user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id );
			$email           = $mo2f_user_email ? $mo2f_user_email : ( get_user_meta( $user_id, 'mo2f_temp_email', true ) ? get_user_meta( $user_id, 'mo2f_temp_email', true ) : get_user_by( 'id', $user_id )->user_email );
			$skeleton        = array(
				'##input_field##'            => '<br><div class="modal-body" style="height:auto;">
                                    <span style="font-size:17px;"><i>Enter your Email:  </i></span>
                                    <input type ="text" style="height:25px;margin-left:10px;" id="emailEntered" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" name="mo2f_phone_email_telegram"  size="30" required value="' . esc_attr( $email ) . '"/><br>
                                    ' . $smtp_message . '
                                    </div>',
				'##remaining_transactions##' => '',
				'##instructions##'           => '',
			);
			return $skeleton;
		}

		/**
		 * Returns skeleton values for OTP Over Telegram.
		 *
		 * @param int $user_id User ID.
		 * @return array
		 */
		public function mo2f_telegram_common_skeleton( $user_id ) {
			$chat_id = get_user_meta( $user_id, 'mo2f_chat_id', true );

			if ( empty( $chat_id ) ) {
				$chat_id = get_user_meta( $user_id, 'mo2f_temp_chatID', true );
			}

			$skeleton = array(
				'##input_field##'            => '<input class="mo2f_table_textbox" style="width:200px;height:25px;" type="text" name="mo2f_phone_email_telegram" id="mo2f_telegram"
                                    value="' . esc_attr( $chat_id ) . '" pattern="[0-9]+" 
                                    title="' . esc_attr__( 'Enter Chat ID recieved on your Telegram without any space or dashes', 'miniorange-2-factor-authentication' ) . '"/><br></h4>',
				'##remaining_transactions##' => '<h4 style="padding:10px; background-color: #a7c5eb"> Remaining Telegram Transactions: <b>Unlimited</b></h4>
                                    ',
				'##instructions##'           => '<h4 class="mo_wpns_not_bold">' . esc_html__( '1. Open the telegram app and search for \'miniorange2fa\'. Click on start button or send \'/start\' message.', 'miniorange-2-factor-authentication' ) . '</h4>
                                    <h4 class="mo_wpns_not_bold">' . esc_html__( '2. Enter the recieved chat id in the below box.', 'miniorange-2-factor-authentication' ) . '</h4>',

			);
			return $skeleton;

		}
	}

}
