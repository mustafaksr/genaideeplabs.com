<?php
/**
 * File contains functions to validate KBA, Google Authenticator code and to send and verify OTP over Email.
 *
 * @package miniOrange-2-factor-authentication/api
 */

namespace TwoFA\Onprem;

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\TwoFAMoSessions;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsMessages;

if ( ! class_exists( 'Mo2f_OnPremRedirect' ) ) {
	/**
	 * Class contains functions to validate several OnPremise methods like Google Authenticator and KBA and to send email to the users containing OTP.
	 */
	class Mo2f_OnPremRedirect {

		/**
		 * Function to redirect the login flow based on the authentication method.
		 *
		 * @param  string $auth_type    Authentication method of user.
		 * @param  int    $otp_token    Otp received by the user.
		 * @param  object $current_user Contains information about the current user.
		 * @return array
		 */
		public function on_prem_validate_redirect( $auth_type, $otp_token, $current_user = null ) {
			switch ( $auth_type ) {

				case MoWpnsConstants::GOOGLE_AUTHENTICATOR:
					$content = $this->mo2f_google_authenticator_onpremise( $otp_token, $current_user );
					return $content;
				case MoWpnsConstants::OTP_OVER_TELEGRAM:
					$mo2f_onprem_cloud_api = new Two_Factor_Setup_Onprem_Cloud();
					return $mo2f_onprem_cloud_api->mo2f_validate_telegram_code( $auth_type, $otp_token, $current_user );
				case MoWpnsConstants::SECURITY_QUESTIONS:
					$content = $this->mo2f_kba_onpremise();
					return $content;
				case MoWpnsConstants::OTP_OVER_EMAIL:
					return $this->mo2f_otp_over_email( $otp_token, $current_user );

			}

		}

		/**
		 * Function to validate security questions.
		 *
		 * @return array
		 */
		private function mo2f_kba_onpremise() {
			$nonce = isset( $_POST['mo2f_authenticate_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_authenticate_nonce'] ) ) : '';
			if ( wp_verify_nonce( $nonce, 'miniorange-2-factor-soft-token-nonce' ) ) {
				$session_id_encrypt = isset( $_POST['session_id'] ) ? ( isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null ) : null;
				if ( isset( $_POST['validate'] ) ) {
					$user_id = wp_get_current_user()->ID;
				} else {
					$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
				}
				$redirect_to          = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
				$kba_ans_1            = isset( $_POST['mo2f_answer_1'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_1'] ) ) : null;
				$kba_ans_2            = isset( $_POST['mo2f_answer_2'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_2'] ) ) : null;
				$questions_challenged = get_user_meta( $user_id, 'kba_questions_user' );
				$questions_challenged = $questions_challenged[0];
				$all_ques_ans         = get_user_meta( $user_id, 'mo2f_kba_challenge' );
				$all_ques_ans         = $all_ques_ans[0];
				$ans_1                = $all_ques_ans[ $questions_challenged[0]['question'] ];
				$ans_2                = $all_ques_ans[ $questions_challenged[1]['question'] ];

				if ( ! strcmp( md5( strtolower( $kba_ans_1 ) ), $ans_1 ) && ! strcmp( md5( strtolower( $kba_ans_2 ) ), $ans_2 ) ) {
					$arr     = array(
						'status'  => 'SUCCESS',
						'message' => 'Successfully validated.',
					);
					$content = wp_json_encode( $arr );
					delete_user_meta( $user_id, 'test_2FA' );
					return $content;
				} else {
					$arr     = array(
						'status'  => 'FAILED',
						'message' => 'TEST FAILED.',
					);
					$content = wp_json_encode( $arr );
					return $content;
				}
			}
		}
		/**
		 * Function to redirect login flow.
		 *
		 * @param  string $u_key User key.
		 * @param  string $auth_type   Authentication type of user.
		 * @param  string $currentuser Contains details of current user.
		 * @return array
		 */
		public function on_prem_send_redirect( $u_key, $auth_type, $currentuser ) {
			switch ( $auth_type ) {

				case MoWpnsConstants::OUT_OF_BAND_EMAIL:
					$content = $this->mo2f_pass2login_push_email_onpremise( $currentuser );
					return $content;
				case MoWpnsConstants::OTP_OVER_EMAIL:
					$content = $this->on_prem_otp_over_email( $currentuser, $u_key );
					return $content;
				case MoWpnsConstants::SECURITY_QUESTIONS:
					$content = $this->on_prem_security_questions( $currentuser );
					return $content;
				case MoWpnsConstants::OTP_OVER_TELEGRAM:
					$mo2f_onprem_cloud_api = new Two_Factor_Setup_Onprem_Cloud();
					$content               = $mo2f_onprem_cloud_api->mo2f_send_telegram_otp( $u_key, $currentuser );
					return $content;

			}

		}

		/**
		 * Function to validate security questions.
		 *
		 * @param  object $user Contain details of current user.
		 * @return array
		 */
		private function on_prem_security_questions( $user ) {
			$question_answers    = get_user_meta( $user->ID, 'mo2f_kba_challenge' );
			$challenge_questions = array_keys( $question_answers[0] );
			$random_keys         = array_rand( $challenge_questions, 2 );
			$challenge_ques1     = array( 'question' => $challenge_questions[ $random_keys[0] ] );
			$challenge_ques2     = array( 'question' => $challenge_questions[ $random_keys[1] ] );
			$questions           = array( $challenge_ques1, $challenge_ques2 );
			update_user_meta( $user->ID, 'kba_questions_user', $questions );
			$response = wp_json_encode(
				array(
					'txId'      => wp_rand( 100, 10000000 ),
					'status'    => 'SUCCESS',
					'message'   => 'Please answer the following security questions.',
					'questions' => $questions,
				)
			);
			return $response;

		}
		/**
		 * Function to redirect login flow to verify code.
		 *
		 * @param  int    $otp_token    OTP token received by user.
		 * @param  object $current_user Details of current user.
		 * @return array
		 */
		private function mo2f_google_authenticator_onpremise( $otp_token, $current_user = null ) {
			include_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			$gauth_obj          = new Google_auth_onpremise();
			$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Ignoring nonce verification warning as the flow is coming from multiple files.
			if ( is_user_logged_in() ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
			} elseif ( isset( $current_user ) && ! empty( $current_user->ID ) ) {
				$user_id = $current_user->ID;
			} else {
				$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			}
			$secret  = $gauth_obj->mo_a_auth_get_secret( $user_id );
			$content = $gauth_obj->mo2f_verify_code( $secret, $otp_token );
			return $content;
		}
		/**
		 * Function to send otp.
		 *
		 * @param  object $current_user Details of current user.
		 * @param  string $useremail    Email id of user.
		 * @return array
		 */
		private function on_prem_otp_over_email( $current_user, $useremail = false ) {
			return $this->on_prem_send_otp_email( $current_user, 'mo2f_otp_email_code', 'mo2f_otp_email_time', $useremail );
		}
		/**
		 * Function to send email to users.
		 *
		 * @param  object $current_user Details of the current user.
		 * @param  int    $token_name   OTP received by the user.
		 * @param  string $time_name    Name of the time of sending otp.
		 * @param  string $email        Email id of user.
		 * @return array
		 */
		private function on_prem_send_otp_email( $current_user, $token_name, $time_name, $email = null ) {
			$count_threshold = 5;
			global $mo2fdb_queries,$image_path;
			if ( ! isset( $current_user ) || is_null( $current_user ) ) {
				if ( is_user_logged_in() ) {
					$current_user = wp_get_current_user();
				} else {
					$current_user = wp_json_encode( $_SESSION['mo2f_current_user'] );
				}
			}

			if ( is_null( $email ) || empty( $email ) || ! isset( $email ) ) {
				$email = get_user_meta( $current_user->ID, 'tempEmail', true );

				if ( empty( $email ) ) {
					$email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $current_user->ID );
				}
			}
			if ( is_null( $email ) || empty( $email ) || ! isset( $email ) ) {
				$email = $current_user->user_email;
			}

			delete_user_meta( $current_user->ID, 'tempEmail' );
			$subject   = '2-Factor Authentication';
			$headers   = array( 'Content-Type: text/html; charset=UTF-8' );
			$otp_token = '';
			for ( $i = 1;$i < 7;$i++ ) {
				$otp_token .= wp_rand( 0, 9 );
			}
			update_user_meta( $current_user->ID, $token_name, $otp_token );
			TwoFAMoSessions::add_session_var( $token_name, $otp_token ); // adding OTP token in session variable to store it in the otp verification on registration flow.
			update_user_meta( $current_user->ID, $time_name, time() );
			update_user_meta( $current_user->ID, 'tempRegEmail', $email );
			$message      = '<table cellpadding="25" style="margin:0px auto">
		<tbody>
		<tr>
		<td>
		<table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;border:1px solid #a8adad">
		<tbody>
		<tr>
		<td><img src="' . $image_path . 'includes/images/xecurify-logo.png" alt="Xecurify"  style="color:#5fb336;text-decoration:none;display:block;width:auto;height:auto;max-height:35px" class="CToWUd"></td>
		</tr>
		</tbody>
		</table>
		<table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
		<tbody>
		<tr>
		<td>
		<p style="margin-top:0;margin-bottom:20px">Dear Customers,</p>
		<p style="margin-top:0;margin-bottom:10px">You initiated a transaction <b>WordPress 2 Factor Authentication Plugin</b>:</p>
		<p style="margin-top:0;margin-bottom:10px">Your one time passcode is ' . $otp_token . '.
		<p style="margin-top:0;margin-bottom:15px">Thank you,<br>miniOrange Team</p>
		<p style="margin-top:0;margin-bottom:0px;font-size:11px">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed.</p>
		</div></div></td>
		</tr>
		</tbody>
		</table>
		</td>
		</tr>
		</tbody>
		</table>';
			$result  = wp_mail( $email, $subject, $message, $headers );
			if ( $result ) {
				if ( get_site_option( 'cmVtYWluaW5nT1RQ' ) === $count_threshold ) {
					Miniorange_Authentication::mo2f_low_otp_alert( 'email' );
				}
				$arr = array(
					'status'  => 'SUCCESS',
					'message' => 'An OTP code has been sent to you on your email.',
					'txId'    => '',
					'email'   => $email,
				);
			} else {
				$arr = array(
					'status'  => 'FAILED',
					'message' => 'TEST FAILED.',
				);
			}
			$content = wp_json_encode( $arr );
			return $content;

		}
		/**
		 * Function to redirect login flow to verify otp over email.
		 *
		 * @param  int    $otp_token    OTP received by user via email.
		 * @param  object $current_user Contains detail of current user.
		 * @return array
		 */
		public function mo2f_otp_over_email( $otp_token, $current_user ) {
			return $this->mo2f_otp_email_verify( $otp_token, $current_user, 'mo2f_otp_email_code', 'mo2f_otp_email_time' );
		}
		/**
		 * Function verifies otp received by user via email.
		 *
		 * @param  int    $otp_token    otp received by user.
		 * @param  object $current_user Contains details of current user.
		 * @param  string $dtoken       Token name to verify the transaction is send through email or not.
		 * @param  string $dtime        Time name to verify the transaction is send through email or not.
		 * @return array
		 */
		private function mo2f_otp_email_verify( $otp_token, $current_user, $dtoken, $dtime ) {
			global $mo2fdb_queries;
			if ( is_null( $current_user ) ) {
				$current_user = wp_get_current_user();
			}
			if ( isset( $otp_token ) && ! empty( $otp_token ) && ! is_null( $current_user ) ) {
				$user_id       = $current_user->ID;
				$valid_token   = ! empty( get_user_meta( $user_id, $dtoken, true ) ) ? get_user_meta( $user_id, $dtoken, true ) : TwoFAMoSessions::get_session_var( 'mo2f_otp_email_code' );
				$cd            = get_user_meta( $user_id, 'mo2f_email_check_code', true );
				$time          = get_user_meta( $user_id, $dtime, true ) ? get_user_meta( $user_id, $dtime, true ) : TwoFAMoSessions::get_session_var( 'sent_on' );
				$accepted_time = time() - 300;
				if ( $accepted_time > $time ) {

					delete_user_meta( $user_id, $dtoken );
					delete_user_meta( $user_id, $dtime );
					delete_user_meta( $user_id, 'tempRegEmail' );
					TwoFAMoSessions::unset_session( 'mo2f_otp_email_code' );
					$arr = array(
						'status'  => 'FAILED',
						'message' => 'OTP Expire.',
					);
				} elseif ( (int) $valid_token === (int) $otp_token ) {
					$arr = array(
						'status'  => 'SUCCESS',
						'message' => 'Successfully validated.',
					);
					delete_user_meta( $user_id, $dtoken );
					TwoFAMoSessions::unset_session( 'mo2f_otp_email_code' );
					if ( 'mo2f_email_check_code' === $dtoken || 'mo2f_otp_email_code' === $dtoken ) {
						$temp_reg_email = get_user_meta( $user_id, 'tempRegEmail', true );
						if ( ! is_null( $temp_reg_email ) || ! empty( $temp_reg_email ) ) {
							$mo2fdb_queries->update_user_details(
								$user_id,
								array(
									'mo2f_configured_2FA_method' => MoWpnsConstants::OTP_OVER_EMAIL,
									'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS',
									'mo2f_user_email' => $temp_reg_email,
								)
							);
						}
					}
					delete_user_meta( $user_id, 'tempRegEmail' );
				} else {
					$arr = array(
						'status'  => 'FAILED',
						'message' => 'TEST FAILED.',
					);
				}

				$content = wp_json_encode( $arr );
				return $content;

			}
		}

		/**
		 * Function to send email to the user for email verification method.
		 *
		 * @param object  $current_user Details of current user.
		 * @param boolean $in_dashboard_flow Details of current user.
		 * @return array
		 */
		public function mo2f_pass2login_push_email_onpremise( $current_user, $in_dashboard_flow = false ) {
			global $mo2fdb_queries;
			$email = get_user_meta( $current_user->ID, 'tempEmail', true );

			if ( empty( $email ) ) {
				$email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $current_user->ID );
			}

			$subject     = '2-Factor Authentication(Email verification)';
			$headers     = array( 'Content-Type: text/html; charset=UTF-8' );
			$txid        = '';
			$otp_token   = '';
			$otp_token_d = '';
			for ( $i = 1;$i < 7;$i++ ) {
				$otp_token   .= wp_rand( 0, 9 );
				$txid        .= wp_rand( 100, 999 );
				$otp_token_d .= wp_rand( 0, 9 );
			}
			$otp_token_h   = hash( 'sha512', $otp_token );
			$otp_token_d_h = hash( 'sha512', $otp_token_d );
			update_user_meta( $current_user->ID, 'mo2f_transactionId', $txid );
			$user_id = hash( 'sha512', $current_user->ID );
			update_site_option( $user_id, $otp_token_h );
			update_site_option( $txid, 3 );
			$user_idd = $user_id . 'D';
			update_site_option( $user_idd, $otp_token_d_h );

			$message                 = $this->getemailtemplate( $user_id, $otp_token_h, $otp_token_d_h, $txid, $email );
			$cm_vt_y_wlua_w5n_t1_r_q = MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );

			$result   = ( $cm_vt_y_wlua_w5n_t1_r_q > 0 ) ? wp_mail( $email, $subject, $message, $headers ) : false;
			$response = array( 'txId' => $txid );
			if ( $result ) {
				if ( get_site_option( 'cmVtYWluaW5nT1RQ' ) === 5 ) {
					Miniorange_Authentication::mo2f_low_otp_alert( 'email' );
				}
				update_site_option( 'cmVtYWluaW5nT1RQ', $cm_vt_y_wlua_w5n_t1_r_q - 1 );
				$response['status']     = 'SUCCESS';
				$time                   = 'time' . $txid;
				$current_time_in_millis = round( microtime( true ) * 1000 );
				update_site_option( $time, $current_time_in_millis );
			} else {
				$response['status'] = 'FAILED';
				if ( $in_dashboard_flow ) {
					$show_message = new MoWpnsMessages();
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL ), 'ERROR' );
				}
			}
			return wp_json_encode( $response );
		}
		/**
		 * Function to fetch customize email template.
		 *
		 * @param  int    $user_id       Id of user.
		 * @param  string $otp_token_h   OTP token sent to email.
		 * @param  string $otp_token_d_h Variable sent to email.
		 * @param  string $txid          Transaction id to verify the email transaction.
		 * @param  string $email         Email id of user.
		 * @return string
		 */
		public function getemailtemplate( $user_id, $otp_token_h, $otp_token_d_h, $txid, $email ) {

			global $image_path;
			$url     = get_site_option( 'siteurl' ) . '/wp-login.php?';
			$message = '<table cellpadding="25" style="margin:0px auto">
		<tbody>
		<tr>
		<td>
		<table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;border:1px solid #a8adad">
		<tbody>
		<tr>
		<td><img src="' . $image_path . 'includes/images/xecurify-logo.png" alt="Xecurify" style="color:#5fb336;text-decoration:none;display:block;width:auto;height:auto;max-height:35px" class="CToWUd"></td>
		</tr>
		</tbody>
		</table>
		<table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
		<tbody>
		<tr>
		<td>
		<p style="margin-top:0;margin-bottom:20px">Dear Customers,</p>
		<p style="margin-top:0;margin-bottom:10px">You initiated a transaction <b>WordPress 2 Factor Authentication Plugin</b>:</p>
		<p style="margin-top:0;margin-bottom:10px">To accept, <a href="' . $url . 'userID=' . $user_id . '&amp;accessToken=' . $otp_token_h . '&amp;secondFactorAuthType=OUT+OF+BAND+EMAIL&amp;Txid=' . $txid . '&amp;user=' . $email . '" target="_blank" data-saferedirecturl="https://www.google.com/url?q=' . MO_HOST_NAME . '/moas/rest/validate-otp?customerKey%3D182589%26otpToken%3D735705%26secondFactorAuthType%3DOUT%2BOF%2BBAND%2BEMAIL%26user%3D' . $email . '&amp;source=gmail&amp;ust=1569905139580000&amp;usg=AFQjCNExKCcqZucdgRm9-0m360FdYAIioA">Accept Transaction</a></p>
		<p style="margin-top:0;margin-bottom:10px">To deny, <a href="' . $url . 'userID=' . $user_id . '&amp;accessToken=' . $otp_token_d_h . '&amp;secondFactorAuthType=OUT+OF+BAND+EMAIL&amp;Txid=' . $txid . '&amp;user=' . $email . '" target="_blank" data-saferedirecturl="https://www.google.com/url?q=' . MO_HOST_NAME . '/moas/rest/validate-otp?customerKey%3D182589%26otpToken%3D735705%26secondFactorAuthType%3DOUT%2BOF%2BBAND%2BEMAIL%26user%3D' . $email . '&amp;source=gmail&amp;ust=1569905139580000&amp;usg=AFQjCNExKCcqZucdgRm9-0m360FdYAIioA">Deny Transaction</a></p><div><div class="adm"><div id="q_31" class="ajR h4" data-tooltip="Hide expanded content" aria-label="Hide expanded content" aria-expanded="true"><div class="ajT"></div></div></div><div class="im">
		<p style="margin-top:0;margin-bottom:15px">Thank you,<br>miniOrange Team</p>
		<p style="margin-top:0;margin-bottom:0px;font-size:11px">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed.</p>
		</div></div></td>
		</tr>
		</tbody>
		</table>
		</td>
		</tr>
		</tbody>
		</table>';
			return $message;
		}
	}
}
