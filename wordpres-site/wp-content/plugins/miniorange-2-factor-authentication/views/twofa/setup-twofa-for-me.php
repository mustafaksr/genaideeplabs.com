<?php
/**
 * This file includes the UI for 2fa methods options.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

use TwoFA\Onprem\MO2f_Cloud_Onprem_Interface;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Database\Mo2fDB;
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Onprem\Mo2fConstants;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\Mo2f_Common_Otp_Setup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */
	$setup_dir_name = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR;
	$test_dir_name  = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR;
	require_once $setup_dir_name . 'setup-google-authenticator.php';
	require_once $setup_dir_name . 'setup-google-authenticator-onpremise.php';
	require_once $setup_dir_name . 'setup-google-authenticator-common-view.php';
	require_once $setup_dir_name . 'setup-authy-authenticator.php';
	require_once $setup_dir_name . 'setup-kba-questions.php';
	require_once $setup_dir_name . 'setup-miniorange-authenticator.php';
	require_once $setup_dir_name . 'setup-duo-authenticator.php';
	require_once $test_dir_name . 'test-twofa-email-verification.php';
	require_once $test_dir_name . 'test-twofa-duo-authenticator.php';
	require_once $test_dir_name . 'test-twofa-miniorange-qrcode-authentication.php';
	require_once $test_dir_name . 'test-twofa-kba-questions.php';
	require_once $test_dir_name . 'test-twofa-miniorange-push-notification.php';
	require_once $test_dir_name . 'test-twofa-otp-methods.php';

/**
 * It is help to create 2fa form
 *
 * @param object $user It will carry the user .
 * @param string $category It will carry the category .
 * @param array  $auth_methods It will carry the auth methods .
 * @param string $can_display_admin_features .
 */
function mo2f_create_2fa_form( $user, $category, $auth_methods, $can_display_admin_features = '' ) {
	global $mo2fdb_queries;

	$miniorange_authenticator      = array(
		MoWpnsConstants::MOBILE_AUTHENTICATION,
		MoWpnsConstants::SOFT_TOKEN,
		MoWpnsConstants::PUSH_NOTIFICATIONS,
	);
	$two_factor_methods_details    = array(
		MoWpnsConstants::SECURITY_QUESTIONS       => array(
			'doc'   => MoWpnsConstants::KBA_DOCUMENT_LINK,
			'video' => MoWpnsConstants::KBA_YOUTUBE,
			'desc'  => 'Configure and Answer Three Security Questions to login',
		),
		MoWpnsConstants::GOOGLE_AUTHENTICATOR     => array(
			'doc'   => MoWpnsConstants::GA_DOCUMENT_LINK,
			'video' => MoWpnsConstants::GA_YOUTUBE,
			'desc'  => 'Use One Time Password shown in <b>Google/Authy/Microsoft Authenticator App</b> to login',
		),
		MoWpnsConstants::OUT_OF_BAND_EMAIL        => array(
			'doc'   => MoWpnsConstants::EMAIL_VERIFICATION_DOCUMENT_LINK,
			'video' => MoWpnsConstants::EMAIL_VERIFICATION_YOUTUBE,
			'desc'  => 'Accept the verification link sent to your email address',
		),
		MoWpnsConstants::SOFT_TOKEN               => array(
			'doc'   => MoWpnsConstants::MO_TOTP_DOCUMENT_LINK,
			'video' => MoWpnsConstants::MO_TOTP_YOUTUBE,
			'desc'  => 'Use One Time Password / Soft Token shown in the miniOrange Authenticator App',
		),
		MoWpnsConstants::PUSH_NOTIFICATIONS       => array(
			'doc'   => MoWpnsConstants::MO_PUSHNOTIFICATION_DOCUMENT_LINK,
			'video' => MoWpnsConstants::MO_PUSH_NOTIFICATION_YOUTUBE,
			'desc'  => 'A Push notification will be sent to the miniOrange Authenticator App for your account,
			Accept it to log in',
		),
		MoWpnsConstants::AUTHY_AUTHENTICATOR      => array(
			'doc'   => null,
			'video' => MoWpnsConstants::AUTHY_AUTHENTICATOR_YOUTUBE,
			'desc'  => 'Enter Soft Token/ One Time Password from the Authy Authenticator App',
		),
		MoWpnsConstants::OTP_OVER_SMS             => array(
			'doc'   => MoWpnsConstants::OTP_OVER_SMS_DOCUMENT_LINK,
			'video' => MoWpnsConstants::OTP_OVER_SMS_YOUTUBE,
			'desc'  => 'A One Time Passcode (OTP) will be sent to your Phone number',
		),
		MoWpnsConstants::OTP_OVER_EMAIL           => array(
			'doc'   => MoWpnsConstants::OTP_OVER_EMAIL_DOCUMENT_LINK,
			'video' => null,
			'desc'  => 'A One Time Passcode (OTP) will be sent to your Email address',
		),
		MoWpnsConstants::OTP_OVER_TELEGRAM        => array(
			'doc'   => MoWpnsConstants::OTP_OVER_TELEGRAM_DOCUMENT_LINK,
			'video' => MoWpnsConstants::OTP_OVER_TELEGRAM_YOUTUBE,
			'desc'  => 'Enter the One Time Passcode sent to your Telegram account',
		),
		MoWpnsConstants::DUO_AUTHENTICATOR        => array(
			'doc'   => null,
			'video' => MoWpnsConstants::DUO_AUTHENTICATOR_YOUTUBE,
			'desc'  => 'A Push notification will be sent to the Duo Authenticator App',
		),
		MoWpnsConstants::MINIORANGE_AUTHENTICATOR => array(
			'doc'   => null,
			'video' => MoWpnsConstants::MO_AUTHENTICATOR_YOUTUBE,
			'desc'  => 'Scan the QR code from the account in your miniOrange Authenticator App to login.',
		),
		MoWpnsConstants::OTP_OVER_WHATSAPP        => array(
			'doc'   => MoWpnsConstants::OTP_OVER_WA_DOCUMENT_LINK,
			'video' => null,
			'desc'  => 'Enter the One Time Passcode sent to your WhatsApp account.',
		),
		MoWpnsConstants::OTP_OVER_SMS_AND_EMAIL   => array(
			'doc'   => null,
			'video' => null,
			'desc'  => 'A One Time Passcode (OTP) will be sent to your Phone number and Email address',
		),
		MoWpnsConstants::HARDWARE_TOKEN           => array(
			'doc'   => null,
			'video' => null,
			'desc'  => 'Enter the One Time Passcode on your Hardware Token',
		),
		''                                        => array(
			'doc'   => null,
			'video' => null,
			'desc'  => '<b>All methods in the FREE Plan in addition to the following methods.</b>',
		),
	);
	$onprem_two_factor_methods     = array_slice( $auth_methods, 0, 8 );
	$is_customer_registered        = 'SUCCESS' === $mo2fdb_queries->get_user_detail( 'user_registration_with_miniorange', $user->ID ) ? true : false;
	$can_user_configure_2fa_method = $can_display_admin_features || ( ! $can_display_admin_features && $is_customer_registered );

	echo '<div class="overlay1" id="overlay" hidden ></div>';
	echo '<form name="f" method="post" action="" id="mo2f_save_' . esc_attr( $category ) . '_auth_methods_form">
            <div id="mo2f_' . esc_attr( $category ) . '_auth_methods" >
                <br>
                <table class="mo2f_auth_methods_table">';

	$configured_auth_method     = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
	$selected_miniorange_method = false;
	if ( in_array( $configured_auth_method, $miniorange_authenticator, true ) ) {
		$selected_miniorange_method = true;
	}

	foreach ( $auth_methods as $auth_method ) {
		$auth_method_abr         = str_replace( ' ', '', MoWpnsConstants::mo2f_convert_method_name( $auth_method, 'cap_to_small' ) );
		$auth_method_abr         = empty( $auth_method_abr ) ? 'NoMethod' : $auth_method_abr;
		$configured_auth_method  = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
		$is_auth_method_selected = ( $auth_method === $configured_auth_method ? true : false );
		if ( MoWpnsConstants::MINIORANGE_AUTHENTICATOR === $auth_method && $selected_miniorange_method ) {
			$is_auth_method_selected = true;
		}
		$thumbnail_height = 'free_plan' === $category ? 190 : 160;
		echo '<div class="mo2f_thumbnail" id="' . esc_attr( $auth_method_abr ) . '_thumbnail_2_factor" style="height:' . esc_attr( $thumbnail_height ) . 'px;';
		if ( MO2F_IS_ONPREM ) {
			$is_auth_method_selected = 0;
			$current_method          = $configured_auth_method;
			if ( $auth_method === $current_method ) {
				$is_auth_method_selected = 1;
			}
		}
			echo $is_auth_method_selected ? '#07b52a' : 'var(--mo2f-theme-blue)';
			echo $is_auth_method_selected ? '#07b52a' : 'var(--mo2f-theme-blue)';
			echo ';">';
			echo '<div>
					<div class="mo2f_thumbnail_method" style="width:100%";>
						<div style="width: 17%; float:left;padding-top:20px;padding-left:20px;">';

			echo '<img src="' . esc_url( plugins_url( 'includes/images/authmethods/' . $auth_method_abr . '.png', dirname( dirname( __FILE__ ) ) ) ) . '" style="width: 50px;height: 50px !important; line-height: 80px; border-radius:10px; overflow:hidden" />';

			echo '</div>
                        <div class="mo2f_thumbnail_method_desc" style="width: 75%;">';

				mo2f_common_form_for_2fa_methods_setup_docs( isset( $two_factor_methods_details[ $auth_method ]['doc'] ) ? $two_factor_methods_details[ $auth_method ]['doc'] : null, isset( $two_factor_methods_details[ $auth_method ]['video'] ) ? $two_factor_methods_details[ $auth_method ]['video'] : null );
			echo ' <b>' . esc_html( MoWpnsConstants::mo2f_convert_method_name( $auth_method, 'cap_to_small' ) ) .
				'</b><br>
                        <p style="padding:0px; padding-left:0px;font-size: 14px;"> ' . wp_kses_post( __( $two_factor_methods_details[ $auth_method ]['desc'], 'miniorange-2-factor-authentication' ) ) //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal 
						. '</p> 
                        </div>
                    </div>
                </div>';
		if ( 'free_plan' === $category ) {
			$is_auth_method_configured = 0;
			if ( 'miniOrangeAuthenticator' === $auth_method_abr ) {
				$is_auth_method_configured = $mo2fdb_queries->get_user_detail( 'mo2f_miniOrangeSoftToken_config_status', $user->ID );
			} elseif ( ( MoWpnsConstants::OUT_OF_BAND_EMAIL === $auth_method || MoWpnsConstants::OTP_OVER_EMAIL === $auth_method || MoWpnsConstants::OUT_OF_BAND_EMAIL === $auth_method ) && ! MO2F_IS_ONPREM ) {
				$is_auth_method_configured = 1;
			} else {
				$is_auth_method_configured = $mo2fdb_queries->get_user_detail( 'mo2f_' . $auth_method_abr . '_config_status', $user->ID );
			}

			$chat_id = get_user_meta( $user->ID, 'mo2f_chat_id', true );
			echo '<div style="height:40px;width:100%;position: absolute;bottom: 0;background-color:';
			if ( MO2F_IS_ONPREM ) {
				$is_auth_method_selected = 0;
				$current_method          = $configured_auth_method;
				if ( $auth_method === $current_method || ( MoWpnsConstants::MINIORANGE_AUTHENTICATOR === $auth_method && $selected_miniorange_method ) ) {
					$is_auth_method_selected = 1;
				}
			}
			echo $is_auth_method_selected ? '#07b52a' : 'var(--mo2f-theme-blue)';
			if ( MO2F_IS_ONPREM ) {
				$twofactor_transactions = new Mo2fDB();
				$exceeded               = $twofactor_transactions->check_alluser_limit_exceeded( $user->ID );
				if ( $exceeded ) {
					if ( empty( $configured_auth_method ) ) {
						$can_user_configure_2fa_method = false;
					} else {
						$can_user_configure_2fa_method = true;
					}
				} else {
					$can_user_configure_2fa_method = true;
				}
				$is_customer_registered = true;
				$user                   = wp_get_current_user();
				echo ';color:white">';

				$check = $is_customer_registered ? true : false;
				$show  = 0;
				if ( ( in_array( $auth_method, $onprem_two_factor_methods, true ) ) ) {
					$show = 1;
				}
				if ( $check ) {
					echo '<div class="mo2f_configure_2_factor">
	                              <button type="button" id="' . esc_attr( $auth_method_abr ) . '_configuration" class="mo2f_configure_set_2_factor" onclick="configureOrSet2ndFactor_' . esc_attr( $category ) . '(\'' . esc_js( $auth_method_abr ) . '\', \'configure2factor\');"';
					echo 1 === $show ? '' : ' disabled ';
					echo '>';
					if ( $show ) {
						echo $is_auth_method_configured ? 'Reconfigure' : 'Configure';
					} else {
						echo 'Available in cloud solution';
					}
					echo '</button></div>';
				}
				if ( ( $is_auth_method_configured && ! $is_auth_method_selected ) || MO2F_IS_ONPREM ) {
					echo '<div class="mo2f_set_2_factor">
	                               <button type="button" id="' . esc_attr( $auth_method_abr ) . '_set_2_factor" class="mo2f_configure_set_2_factor" onclick="configureOrSet2ndFactor_' . esc_attr( $category ) . '(\'' . esc_js( $auth_method_abr ) . '\', \'select2factor\');"';
					echo $can_user_configure_2fa_method ? '' : ' disabled ';
					echo 1 === $show ? '' : ' disabled ';
					if ( 1 === $show && $is_auth_method_configured && 0 === $is_auth_method_selected ) {
						echo '>Set as 2-factor</button>
		                              </div>';
					} else {
						echo '
	                    	</button>
	                              </div>';
					}
				}
			} else {
				if ( get_option( 'mo2f_miniorange_admin' ) ) {
					$allowed = get_option( 'mo2f_miniorange_admin' ) === wp_get_current_user()->ID;
				} else {
					$allowed = 1;
				}
				$cloudswitch = 0;
				if ( ! $allowed ) {
					$allowed = 2;
				}
				echo ';color:white">';
				$check = ! $is_customer_registered ? true : ( MoWpnsConstants::OUT_OF_BAND_EMAIL !== $auth_method && MoWpnsConstants::OTP_OVER_EMAIL !== $auth_method );

				if ( ! MO2F_IS_ONPREM && ( MoWpnsConstants::OUT_OF_BAND_EMAIL === $auth_method || MoWpnsConstants::OTP_OVER_EMAIL === $auth_method ) ) {
					$check = 0;
				}
				if ( $check ) {
					echo '<div class="mo2f_configure_2_factor">
	                              <button type="button" id="' . esc_attr( $auth_method_abr ) . '_configuration" class="mo2f_configure_set_2_factor" onclick="configureOrSet2ndFactor_' . esc_attr( $category ) . '(\'' . esc_js( $auth_method_abr ) . '\', \'configure2factor\',' . esc_js( $cloudswitch ) . ',' . esc_js( $allowed ) . ');"';
					echo $can_user_configure_2fa_method ? '' : '  ';
					echo '>';
					echo $is_auth_method_configured ? 'Reconfigure' : 'Configure';
					echo '</button></div>';
				}
				if ( ( $is_auth_method_configured && ! $is_auth_method_selected ) || MO2F_IS_ONPREM ) {
					echo '<div class="mo2f_set_2_factor">
	                               <button type="button" id="' . esc_attr( $auth_method_abr ) . '_set_2_factor" class="mo2f_configure_set_2_factor" onclick="configureOrSet2ndFactor_' . esc_attr( $category ) . '(\'' . esc_js( $auth_method_abr ) . '\', \'select2factor\',' . esc_js( $cloudswitch ) . ',' . esc_js( $allowed ) . ');"';
					echo $can_user_configure_2fa_method ? '' : '  ';
					echo '>Set as 2-factor</button>
	                              </div>';
				}
			}
			if ( $is_auth_method_selected && MoWpnsConstants::MINIORANGE_AUTHENTICATOR === $auth_method ) {
					echo '<select name="mo2fa_MO_methods" id="mo2fa_MO_methods" class="mo2f_set_2_factor mo2f_configure_switch_2_factor mo2f_kba_ques" style="color: white;font-weight: 700;background: #48b74b;background-size: 16px 16px;border: 1px solid #48b74b;padding: 0px 0px 0px 17px;min-height: 30px;max-width: 9em;max-width: 9em;" onchange="show_3_minorange_methods();">
							      <option value="" selected disabled hidden style="color:white!important;">Switch to >></option>
							      <option value="miniOrangeSoftToken">Soft Token</option>
							      <option value="miniOrangeQRCodeAuthentication">QR Code</option>
							      <option value="miniOrangePushNotification">Push Notification</option>
							  </select></div>
							  <br><br>

							  ';
			}
				echo '</div>';
		}
			echo '</div></div>';

	}

	echo '</table>';
	if ( 'free_plan' !== $category ) {
		if ( current_user_can( 'administrator' ) ) {
			echo '<div class="mo2f_premium_footer">
                            <p style="font-size:16px;margin-left: 1%">In addition to these authentication methods, for other features in this plan, <a href="admin.php?page=mo_2fa_upgrade"><i>Click here.</i></a></p>
                 </div>';
		}
	}
	$configured_auth_method_abr = str_replace( ' ', '', $configured_auth_method );
	echo '</div> <input type="hidden" name="miniorange_save_form_auth_methods_nonce"
                   value="' . esc_attr( wp_create_nonce( 'miniorange-save-form-auth-methods-nonce' ) ) . '"/>
                <input type="hidden" name="option" value="mo2f_save_' . esc_attr( $category ) . '_auth_methods" />
                <input type="hidden" name="mo2f_configured_2FA_method_' . esc_attr( $category ) . '" id="mo2f_configured_2FA_method_' . esc_attr( $category ) . '" />
                <input type="hidden" name="mo2f_selected_action_' . esc_attr( $category ) . '" id="mo2f_selected_action_' . esc_attr( $category ) . '" />
                </form><script>
                var selected_miniorange_method = "' . esc_attr( $selected_miniorange_method ) . '";
                if(selected_miniorange_method)
                	jQuery("<input>").attr({type: "hidden",id: "miniOrangeAuthenticator",value: "' . esc_attr( $configured_auth_method_abr ) . '"}).appendTo("form");
                else                	
                	jQuery("<input>").attr({type: "hidden",id: "miniOrangeAuthenticator",value: "miniOrangeSoftToken"}).appendTo("form");
                </script>';
}

/**
 * Form for set up doc links of the 2FA methods.
 *
 * @param string $doc_link Doc link.
 * @param string $video_link Video link.
 * @return void
 */
function mo2f_common_form_for_2fa_methods_setup_docs( $doc_link, $video_link ) {

	echo '   <span style="float:right">';
	if ( isset( $doc_link ) ) {
		echo '<a href=' . esc_url( $doc_link ) . ' target="_blank">
        <span title="View Setup Guide" class="dashicons dashicons-text-page" style="font-size:19px;color:#413c69;float: right;"></span>
        </a>';
	}
	if ( isset( $video_link ) ) {
		echo '<a href=' . esc_url( $video_link ) . ' target="_blank">
		<span title="Watch Setup Video" class="dashicons dashicons-video-alt3" style="font-size:18px;color:red;float: right;    margin-right: 5px;"></span>
		</a>';

	}
	echo '</span>';

}

/**
 * It will use to activate Second factor
 *
 * @param object $user It will carry the user .
 * @return string
 */
function mo2f_get_activated_second_factor( $user ) {

	global $mo2fdb_queries;
	$user_registration_status = $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID );
	$is_customer_registered   = 'SUCCESS' === $mo2fdb_queries->get_user_detail( 'user_registration_with_miniorange', $user->ID ) ? true : false;
	$useremail                = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
	if ( 'MO_2_FACTOR_SUCCESS' === $user_registration_status ) {
		// checking this option for existing users.
		$mo2fdb_queries->update_user_details( $user->ID, array( 'mobile_registration_status' => true ) );
		$mo2f_second_factor = MoWpnsConstants::MOBILE_AUTHENTICATION;

		return $mo2f_second_factor;
	} elseif ( 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' === $user_registration_status ) {
		return 'NONE';
	} else {
		if ( 'MO_2_FACTOR_PLUGIN_SETTINGS' === $user_registration_status && $is_customer_registered ) {
			$enduser  = new MocURL();
			$userinfo = json_decode( $enduser->mo2f_get_userinfo( $useremail ), true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				if ( 'ERROR' === $userinfo['status'] ) {
					$mo2f_second_factor = 'NONE';
				} elseif ( 'SUCCESS' === $userinfo['status'] ) {
					$mo2f_second_factor = mo2f_update_and_sync_user_two_factor( $user->ID, $userinfo );
				} elseif ( 'FAILED' === $userinfo['status'] ) {
					$mo2f_second_factor = 'NONE';
				} else {
					$mo2f_second_factor = 'NONE';
				}
			} else {
				$mo2f_second_factor = 'NONE';
			}
		} else {
			$mo2f_second_factor = 'NONE';
		}
		return $mo2f_second_factor;
	}
}
/**
 * It will update and sync the two factor settings
 *
 * @param string $user_id It will carry the user id .
 * @param object $userinfo It will carry the user info .
 * @return string
 */
function mo2f_update_and_sync_user_two_factor( $user_id, $userinfo ) {
	global $mo2fdb_queries;
	$mo2f_second_factor = isset( $userinfo['authType'] ) && ! empty( $userinfo['authType'] ) ? $userinfo['authType'] : 'NONE';
	if ( MO2F_IS_ONPREM ) {
		$mo2f_second_factor = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user_id );
		$mo2f_second_factor = $mo2f_second_factor ? $mo2f_second_factor : 'NONE';
		return $mo2f_second_factor;
	}

	$mo2fdb_queries->update_user_details( $user_id, array( 'mo2f_user_email' => $userinfo['email'] ) );
	if ( MoWpnsConstants::OUT_OF_BAND_EMAIL === $mo2f_second_factor ) {
		$mo2fdb_queries->update_user_details( $user_id, array( 'mo2f_EmailVerification_config_status' => true ) );
	} elseif ( MoWpnsConstants::OTP_OVER_SMS === $mo2f_second_factor && ! MO2F_IS_ONPREM ) {
		$phone_num = isset( $userinfo['phone'] ) ? sanitize_text_field( $userinfo['phone'] ) : '';
		$mo2fdb_queries->update_user_details( $user_id, array( 'mo2f_OTPOverSMS_config_status' => true ) );
		$_SESSION['user_phone'] = $phone_num;
	} elseif ( in_array(
		$mo2f_second_factor,
		array(
			MoWpnsConstants::SOFT_TOKEN,
			MoWpnsConstants::MOBILE_AUTHENTICATION,
			MoWpnsConstants::PUSH_NOTIFICATIONS,
		),
		true
	) ) {
		if ( ! MO2F_IS_ONPREM ) {
			$mo2fdb_queries->update_user_details(
				$user_id,
				array(
					'mo2f_miniOrangeSoftToken_config_status' => true,
					'mo2f_miniOrangeQRCodeAuthentication_config_status' => true,
					'mo2f_miniOrangePushNotification_config_status' => true,
				)
			);
		}
	} elseif ( MoWpnsConstants::SECURITY_QUESTIONS === $mo2f_second_factor ) {
		$mo2fdb_queries->update_user_details( $user_id, array( 'mo2f_SecurityQuestions_config_status' => true ) );
	} elseif ( MoWpnsConstants::GOOGLE_AUTHENTICATOR === $mo2f_second_factor ) {
		$app_type = get_user_meta( $user_id, 'mo2f_external_app_type', true );
		if ( MoWpnsConstants::AUTHY_AUTHENTICATOR === $app_type ) {
			$mo2fdb_queries->update_user_details(
				$user_id,
				array(
					'mo2f_AuthyAuthenticator_config_status' => true,
				)
			);
		} else {
			$mo2fdb_queries->update_user_details(
				$user_id,
				array(
					'mo2f_GoogleAuthenticator_config_status' => true,
				)
			);

			update_user_meta( $user_id, 'mo2f_external_app_type', MoWpnsConstants::GOOGLE_AUTHENTICATOR );
		}
	}

	return $mo2f_second_factor;
}
/**
 * It will help to display the customer registration
 *
 * @param object $user It will to show the.
 * @return void
 */
function display_customer_registration_forms( $user ) {

	global $mo2fdb_queries;
	$mo2f_current_registration_status = get_option( 'mo_2factor_user_registration_status' );
	$mo2f_message                     = get_option( 'mo2f_message' );
	?>

	<div id="smsAlertModal" class="modal" role="dialog" data-backdrop="static" data-keyboard="false" >
		<div class="mo2f_modal-dialog" style="margin-left:30%;">
			<div class="modal-content">
				<div class="mo2f_modal-header">
					<h2 class="mo2f_modal-title">You are just one step away from setting up 2FA.</h2><span type="button" id="mo2f_registration_closed" class="modal-span-close" data-dismiss="modal">&times;</span>
				</div>
				<div class="mo2f_modal-body">
					<span style="color:green;cursor: pointer;float:right;" onclick="show_content();">Why Register with miniOrange?</span><br>
				<div id="mo2f_register" style="background-color:#f1f1f1;padding: 1px 4px 1px 14px;display: none;">
					<p>miniOrange Two Factor plugin uses highly secure miniOrange APIs to communicate with the plugin. To keep this communication secure, we ask you to register and assign you API keys specific to your account.			This way your account and users can be only accessed by API keys assigned to you. Also, you can use the same account on multiple applications and your users do not have to maintain multiple accounts or 2-factors.</p>
				</div>
					<?php if ( $mo2f_message ) { ?>
						<div style="padding:5px;">
							<div class="alert alert-info" style="margin-bottom:0px;padding:3px;">
								<p style="font-size:15px;margin-left: 2%;"><?php wp_kses( $mo2f_message, array( 'b' => array() ) ); ?></p>
							</div>
						</div>
						<?php
					}
					if ( in_array( $mo2f_current_registration_status, array( 'REGISTRATION_STARTED', 'MO_2_FACTOR_OTP_DELIVERED_SUCCESS', 'MO_2_FACTOR_OTP_DELIVERED_FAILURE', 'MO_2_FACTOR_VERIFY_CUSTOMER' ), true ) ) {
						mo2f_show_registration_screen( $user );
					}
					?>
				</div>
			</div>
		</div>
		<form name="f" method="post" action="" class="mo2f_registration_closed_form">
			<input type="hidden" name="mo2f_registration_closed_nonce"
							value="<?php echo esc_html( wp_create_nonce( 'mo2f-registration-closed-nonce' ) ); ?>"/>
			<input type="hidden" name="option" value="mo2f_registration_closed"/>
		</form>
	</div>
	<script>
		function show_content() {
			jQuery('#mo2f_register').slideToggle();
		}
		jQuery(function () {
			jQuery('#smsAlertModal').modal();
		});

		jQuery('#mo2f_registration_closed').click(function () {
			jQuery('.mo2f_registration_closed_form').submit();
		});
	</script>

	<?php
	wp_register_script( 'mo2f_bootstrap_js', plugins_url( 'includes/js/bootstrap.min.js', dirname( dirname( __FILE__ ) ) ), array(), MO2F_VERSION, false );
	wp_print_scripts( 'mo2f_bootstrap_js' );
}
/**
 * It will help to show the registration screen
 *
 * @param object $user .
 * @return void
 */
function mo2f_show_registration_screen( $user ) {
	global $mo2f_dir_name;

	include $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'account.php';

}
/**
 * It will help to show the 2fa screen
 *
 * @param object $user .
 * @param string $selected_2fa_method  .
 * @return void
 */
function mo2f_show_2fa_configuration_screen( $user, $selected_2fa_method ) {
	global $mo2f_dir_name;
	$user_id                     = $user->ID;
	$twofa_method                = $selected_2fa_method;
	$mo2f_otp_setup              = new Mo2f_Common_Otp_Setup();
	$setup_otp_dashboard_address = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR . 'setup-otp-over-sms-email-telegram-dashboard.php';
	switch ( $selected_2fa_method ) {
		case MoWpnsConstants::GOOGLE_AUTHENTICATOR:
			$cloud_onprem_interface_obj = new MO2f_Cloud_Onprem_Interface();
			$cloud_onprem_interface_obj->mo2f_show_gauth_screen( $user );
			break;
		case MoWpnsConstants::AUTHY_AUTHENTICATOR:
			echo '<div class="mo2f_table_layout mo2f_table_layout1">';
			mo2f_configure_authy_authenticator( $user );
			echo '</div>';
			break;
		case MoWpnsConstants::SECURITY_QUESTIONS:
			echo '<div class="mo2f_table_layout mo2f_table_layout1">';
			mo2f_configure_for_mobile_suppport_kba( $user );
			echo '</div>';
			break;
		case MoWpnsConstants::OUT_OF_BAND_EMAIL:
			echo '<div class="mo2f_table_layout mo2f_table_layout1">';
			mo2f_test_email_verification( $user );
			echo '</div>';
			break;
		case MoWpnsConstants::OTP_OVER_SMS:
			echo '<div class="mo2f_table_layout mo2f_table_layout1">';
			$skeleton = $mo2f_otp_setup->mo2f_sms_common_skeleton( $user_id );
			require_once $setup_otp_dashboard_address;
			echo '</div>';
			break;
		case MoWpnsConstants::OTP_OVER_EMAIL:
			echo '<div class="mo2f_table_layout mo2f_table_layout1">';
			$skeleton = $mo2f_otp_setup->mo2f_email_common_skeleton( $user_id );
			require_once $setup_otp_dashboard_address;
			echo '</div>';
			break;
		case MoWpnsConstants::OTP_OVER_TELEGRAM:
			echo '<div class="mo2f_table_layout mo2f_table_layout1">';
			$skeleton = $mo2f_otp_setup->mo2f_telegram_common_skeleton( $user_id );
			require_once $setup_otp_dashboard_address;
			echo '</div>';
			break;
		case MoWpnsConstants::SOFT_TOKEN:
			echo '<div class="mo2f_table_layout mo2f_table_layout1">';
			mo2f_configure_miniorange_authenticator( $user );
			echo '</div>';
			break;
		case MoWpnsConstants::MOBILE_AUTHENTICATION:
			echo '<div class="mo2f_table_layout mo2f_table_layout1">';
			mo2f_configure_miniorange_authenticator( $user );
			echo '</div>';
			break;
		case MoWpnsConstants::PUSH_NOTIFICATIONS:
			echo '<div class="mo2f_table_layout mo2f_table_layout1">';
			mo2f_configure_miniorange_authenticator( $user );
			echo '</div>';
			break;
		case MoWpnsConstants::DUO_AUTHENTICATOR:
			echo '<div class="mo2f_table_layout mo2f_table_layout1">';
			mo2f_configure_duo_authenticator( $user );
			echo '</div>';
			break;
	}

}
/**
 * It will help to show the 2fa test screen
 *
 * @param object $user .
 * @param string $selected_2fa_method .
 * @return void
 */
function mo2f_show_2fa_test_screen( $user, $selected_2fa_method ) {

	switch ( $selected_2fa_method ) {
		case MoWpnsConstants::MOBILE_AUTHENTICATION:
			mo2f_test_miniorange_qr_code_authentication( $user );
			break;
		case MoWpnsConstants::PUSH_NOTIFICATIONS:
			mo2f_test_miniorange_push_notification( $user );
			break;
		case MoWpnsConstants::OUT_OF_BAND_EMAIL:
			mo2f_test_email_verification( $user );
			break;
		case MoWpnsConstants::SECURITY_QUESTIONS:
			mo2f_test_kba_security_questions( $user );
			break;
		case MoWpnsConstants::DUO_AUTHENTICATOR:
			mo2f_test_duo_authenticator();
			break;
		default:
			mo2f_test_otp_methods( MoWpnsConstants::$mo2f_otp_method_components[ $selected_2fa_method ] );
	}

}

/**
 * It will help to personalization
 *
 * @param string $mo2f_user_email .
 * @return void
 */
function mo2f_personalization_description( $mo2f_user_email ) {
	?>
	<div id="mo2f_custom_addon">
		<?php if ( get_option( 'mo2f_personalization_installed' ) ) { ?>
			<a href="<?php echo esc_url( admin_url() ); ?>plugins.php" id="mo2f_activate_custom_addon"
					class="button button-primary button-large"
					style="float:right; margin-top:2%;"><?php esc_html_e( 'Activate Plugin', 'miniorange-2-factor-authentication' ); ?></a>
				<?php } ?>
		<?php
		if ( ! get_option( 'mo2f_personalization_purchased' ) ) {
			?>
			<a
						onclick="mo2f_addonform('wp_2fa_addon_shortcode')" id="mo2f_purchase_custom_addon"
						class="button button-primary button-large"
						style="float:right;"><?php esc_html_e( 'Purchase', 'miniorange-2-factor-authentication' ); ?></a>
				<?php } ?>
		<div id="mo2f_custom_addon_hide">						
			<br>
			<div id="mo2f_hide_custom_content">
				<div class="mo2f_box">
					<h3><?php esc_html_e( 'Customize Plugin Icon', 'miniorange-2-factor-authentication' ); ?></h3>
					<hr>
					<p>
						<?php esc_html_e( 'With this feature, you can customize the plugin icon in the dashboard which is useful when you want your custom logo to be displayed to the users.', 'miniorange-2-factor-authentication' ); ?>
					</p>
					<br>
					<h3><?php esc_html_e( 'Customize Plugin Name', 'miniorange-2-factor-authentication' ); ?></h3>
					<hr>
					<p>
						<?php esc_html_e( 'With this feature, you can customize the name of the plugin in the dashboard.', 'miniorange-2-factor-authentication' ); ?>
					</p>

				</div>
				<br>
				<div class="mo2f_box">
					<h3><?php esc_html_e( 'Customize UI of Login Pop up\'s', 'miniorange-2-factor-authentication' ); ?></h3>
					<hr>
					<p>
						<?php esc_html_e( 'With this feature, you can customize the login pop-ups during two factor authentication according to the theme of                 your website.', 'miniorange-2-factor-authentication' ); ?>
					</p>
				</div>

				<br>
				<div class="mo2f_box">
					<h3><?php esc_html_e( 'Custom Email and SMS Templates', 'miniorange-2-factor-authentication' ); ?></h3>
					<hr>

					<p><?php esc_html_e( 'You can change the templates for Email and SMS which user receives during authentication.', 'miniorange-2-factor-authentication' ); ?></p>

				</div>
			</div>
		</div>
		<div id="mo2f_custom_addon_show"><?php $x = apply_filters( 'mo2f_custom', 'custom' ); ?></div> 
	</div> 
	<?php
}
/**
 * It will help add the description of shortcode
 *
 * @param string $mo2f_user_email .
 * @return void
 */
function mo2f_shortcode_description( $mo2f_user_email ) {
	?>
	<div id="mo2f_Shortcode_addon_hide">
		<?php if ( get_option( 'mo2f_shortcode_installed' ) ) { ?>
			<a href="<?php echo esc_url( admin_url() ); ?>plugins.php" id="mo2f_activate_shortcode_addon"
						class="button button-primary button-large" style="float:right; margin-top:2%;">
						<?php
							esc_html_e(
								'Activate
                        Plugin',
								'miniorange-2-factor-authentication'
							);
						?>
																											</a>
		<?php } if ( ! get_option( 'mo2f_shortcode_purchased' ) ) { ?>
				<a onclick="mo2f_addonform('wp_security_two_factor_basic_plan')" id="mo2f_purchase_shortcode_addon"
						class="button button-primary button-large"
						style="float:right;"><?php esc_html_e( 'Purchase', 'miniorange-2-factor-authentication' ); ?></a>
		<?php } ?>	
	<div id="shortcode" class="description">		
			<br>
			<div id="mo2f_hide_shortcode_content" class="mo2f_box">
				<h3><?php esc_html_e( 'List of Shortcodes', 'miniorange-2-factor-authentication' ); ?>:</h3>
				<hr>
				<ol style="margin-left:2%">
					<li>
						<b><?php esc_html_e( 'Enable Two Factor: ', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( 'This shortcode provides an option to turn on/off 2-factor by user.', 'miniorange-2-factor-authentication' ); ?>
					</li>
					<li>
						<b><?php esc_html_e( 'Enable Reconfiguration: ', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( 'This shortcode provides an option to configure the Google Authenticator and Security Questions by user.', 'miniorange-2-factor-authentication' ); ?>
					</li>
					<li>
						<b><?php esc_html_e( 'Enable Remember Device: ', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( ' This shortcode provides\'Enable Remember Device\' from your custom login form.', 'miniorange-2-factor-authentication' ); ?>
					</li>
				</ol>
			</div>
			<div id="mo2f_Shortcode_addon_show"><?php $x = apply_filters( 'mo2f_shortcode', 'shortcode' ); ?></div>
		</div>
		<br>
	</div>
	<script>
		function mo2f_addonform(planname) {
		//check if the customer is logged in or created in the plugin or not using account setup tab
		const url = `https://portal.miniorange.com/initializepayment?requestOrigin=${planname}`;
		window.open(url, "_blank");
	}
	</script>
	<?php
}

?>
