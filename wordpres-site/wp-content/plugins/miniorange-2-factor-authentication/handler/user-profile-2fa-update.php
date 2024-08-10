<?php
/**
 * User profile 2fa update file.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Onprem\MO2f_Cloud_Onprem_Interface;
use TwoFA\Onprem\Miniorange_Authentication;
use TwoFA\Onprem\Two_Factor_Setup_Onprem_Cloud;
use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Helper\MoWpnsMessages;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$is_userprofile_enabled = isset( $_POST['mo2f_enable_userprofile_2fa'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_enable_userprofile_2fa'] ) ) : false;
if ( ! $is_userprofile_enabled ) {
	return;
}

$nonce = isset( $_POST['mo2f-update-mobile-nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f-update-mobile-nonce'] ) ) : '';

if ( ! wp_verify_nonce( $nonce, 'mo2f-update-mobile-nonce' ) || ! current_user_can( 'manage_options' ) ) {
	$mo2f_error = new WP_Error();
	$mo2f_error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
	return $mo2f_error;
} else {

	if ( isset( $_POST['method'] ) ) {
		$method = sanitize_text_field( wp_unslash( $_POST['method'] ) );
	} else {
		return;
	}
	global $mo2fdb_queries;
	$email   = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id );
	$email   = sanitize_email( $email );
	$enduser = new MocURL();
	if ( isset( $_POST['verify_phone'] ) ) {
		$phone = strlen( $_POST['verify_phone'] > 4 ) ? sanitize_text_field( wp_unslash( $_POST['verify_phone'] ) ) : null;
	} else {
		$phone = null;
	}
	$pass2flogin = new Miniorange_Password_2Factor_Login();
	$creatuser   = new Two_Factor_Setup_Onprem_Cloud();
	$currentuser = get_user_by( 'id', $user_id );
	$content     = json_decode( $creatuser->mo_create_user( $currentuser, $email ), true );

	$response  = json_decode( $enduser->mo2f_update_user_info( $email, $method, $phone, null, null ), true );
	$userid    = get_current_user_id();
	$tfastatus = ( $userid === $user_id ) ? 'MO_2_FACTOR_PLUGIN_SETTINGS' : 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR';

	if ( 'SUCCESS' !== $response['status'] ) {
		return;
	}
	switch ( $method ) {
		// free version code.
		case MoWpnsConstants::MOBILE_AUTHENTICATION:
		case MoWpnsConstants::PUSH_NOTIFICATIONS:
		case MoWpnsConstants::SOFT_TOKEN:
			if ( $userid !== $user_id ) {
				mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
			} elseif ( isset( $_POST['mo2f_configuration_status'] ) && sanitize_text_field( wp_unslash( $_POST['mo2f_configuration_status'] ) ) !== 'SUCCESS' ) {
				return;
			}
			delete_user_meta( $user_id, 'mo2f_configure_2FA' );
			update_user_meta( $user_id, 'mo2f_2FA_method_to_configure', $method );
			$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, $tfastatus, 1 );
			break;
		case MoWpnsConstants::GOOGLE_AUTHENTICATOR:
			if ( $userid !== $user_id ) {
				$content = mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
			} elseif ( isset( $_POST['mo2f_configuration_status'] ) && sanitize_text_field( wp_unslash( $_POST['mo2f_configuration_status'] ) ) !== 'SUCCESS' ) {
				return;
			}
			$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, $tfastatus, 1, $email );

			if ( ! MO2F_IS_ONPREM ) {
				update_user_meta( $user_id, 'mo2f_external_app_type', MoWpnsConstants::GOOGLE_AUTHENTICATOR );
			}
			break;
		case MoWpnsConstants::OTP_OVER_SMS:
			$content = mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
			$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, 1, $email );
			break;
		case MoWpnsConstants::SECURITY_QUESTIONS:
			if ( $userid === $user_id ) {
				$obj              = new Miniorange_Authentication();
				$kba_ques_ans_obj = new Miniorange_Password_2Factor_Login();
				$kba_ques_ans     = $kba_ques_ans_obj->mo2f_get_kba_details( $_POST );
				if ( MO2f_Utility::mo2f_check_empty_or_null( $kba_ques_ans['kba_q1'] ) || MO2f_Utility::mo2f_check_empty_or_null( $kba_ques_ans['kba_a1'] ) || MO2f_Utility::mo2f_check_empty_or_null( $kba_ques_ans['kba_q2'] ) || MO2f_Utility::mo2f_check_empty_or_null( $kba_ques_ans['kba_a2'] ) || MO2f_Utility::mo2f_check_empty_or_null( $kba_ques_ans['kba_q3'] ) || MO2f_Utility::mo2f_check_empty_or_null( $kba_ques_ans['kba_a3'] ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_ENTRY ), 'ERROR' );
					return;
				}

				if ( 0 === strcasecmp( $kba_ques_ans['kba_q1'], $kba_ques_ans['kba_q2'] ) || 0 === strcasecmp( $kba_ques_ans['kba_q2'], $kba_ques_ans['kba_q3'] ) || 0 === strcasecmp( $kba_ques_ans['kba_q3'], $kba_ques_ans['kba_q1'] ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::UNIQUE_QUESTION ), 'ERROR' );
					return;
				}
				$kba_registration = new MO2f_Cloud_Onprem_Interface();
				$kba_reg_reponse  = json_decode( $kba_registration->mo2f_register_kba_details( $email, $kba_ques_ans['kba_q1'], $kba_ques_ans['kba_a1'], $kba_ques_ans['kba_q2'], $kba_ques_ans['kba_a2'], $kba_ques_ans['kba_q3'], $kba_ques_ans['kba_a3'], $user_id ), true );
			}
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $response['status'] ) {
					$content = mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
					$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, $tfastatus, 1, $email );
				} else {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
				}
			}
			break;
		case MoWpnsConstants::OTP_OVER_EMAIL:
			$content = mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
			if ( 'SUCCESS' === $content['status'] ) {
				$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, 1, $email );
				delete_user_meta( $user_id, 'mo2f_configure_2FA' );
				delete_user_meta( $user_id, 'test_2FA' );
			}

			break;
		case MoWpnsConstants::OUT_OF_BAND_EMAIL:
			$content = mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
			if ( 'SUCCESS' === $content['status'] ) {
				$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, 1, $email );
			}
			break;
	}
}

/**
 * Sends the 2fa method reconfiguration link on user's email id.
 *
 * @param string  $email User's email id.
 * @param integer $user_id User id of the user.
 * @param string  $tfa_method Name of the 2fa method.
 * @return mixed
 */
function mo2f_send_twofa_setup_link_on_email( $email, $user_id, $tfa_method ) {
	global $mo2f_dir_name,$image_path;
	$method_description = array(
		MoWpnsConstants::GOOGLE_AUTHENTICATOR => 'configure the 2nd factor',
		MoWpnsConstants::SECURITY_QUESTIONS   => 'configure the 2nd factor',
		MoWpnsConstants::OTP_OVER_SMS         => 'Login to the site',
		MoWpnsConstants::OTP_OVER_EMAIL       => 'Login to the site',
		MoWpnsConstants::OUT_OF_BAND_EMAIL    => 'Login to the site',
	);
	$method                = strval( $tfa_method );
	$reconfiguraion_method = hash( 'sha512', $method );
	update_site_option( $reconfiguraion_method, $method );
	$txid = bin2hex( openssl_random_pseudo_bytes( 32 ) );
	update_site_option( $txid, get_current_user_id() );
	update_user_meta( $user_id, 'mo2f_transactionId', $txid );
	update_user_meta( $user_id, 'mo2f_user_profile_set', true );
	$subject      = '2FA-Configuration';
	$headers      = array( 'Content-Type: text/html; charset=UTF-8' );
	$path         = plugins_url( DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'qr_over_email.php', dirname( __FILE__ ) ) . '?email=' . $email . '&amp;user_id=' . $user_id;
	$url          = get_site_option( 'siteurl' ) . '/wp-login.php?';
	$path         = $url . '&amp;reconfigureMethod=' . $reconfiguraion_method . '&amp;transactionId=' . $txid;
	$message      = '
    <table>
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
    <input type="hidden" name="user_id" id="user_id" value="' . esc_attr( $user_id ) . '">
    <input type="hidden" name="email" id="email" value="' . esc_attr( $email ) . '">
    <p style="margin-top:0;margin-bottom:20px">Dear ' . get_user_by( 'id', $user_id )->user_login . ',</p>
    <p style="margin-top:0;margin-bottom:10px">Your 2FA method (' . esc_attr( MoWpnsConstants::mo2f_convert_method_name( $tfa_method, 'cap_to_small' ) ) . ') has been set by site admin.</p>
    <p><a href="' . esc_url( $path ) . '" > Click to ' . $method_description[ $tfa_method ] . '</a></p>
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
	$show_message = new MoWpnsMessages();
	$result       = wp_mail( $email, $subject, $message, $headers );
	if ( $result ) {
		$arr = array(
			'status'  => 'SUCCESS',
			'message' => 'Successfully validated.',
			'txid'    => '',
		);

	} else {
		$arr = array(
			'status'  => 'FAILED',
			'message' => 'TEST FAILED.',
		);
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL ), 'ERROR' );
		return;
	}

	return $arr;
}

