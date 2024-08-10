<?php
/**
 * This file contains functions related to inline registration.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

// Mostly both cloud and onprem code.
use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Onprem\Two_Factor_Setup_Onprem_Cloud;
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Onprem\Google_Auth_Onpremise;
use TwoFA\Helper\Mo2f_Common_Otp_Setup;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * This function returns array of methods
 *
 * @param object $current_user object containing user details.
 * @return array
 */
function fetch_methods( $current_user = null ) {
	$methods = array( MoWpnsConstants::OTP_OVER_SMS, MoWpnsConstants::OUT_OF_BAND_EMAIL, MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SECURITY_QUESTIONS, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OTP_OVER_TELEGRAM );
	if ( ! is_null( $current_user ) && ( 'administrator' !== $current_user->roles[0] ) && ! get_option( 'mo2f_email' ) || ! get_option( 'mo2f_customerKey' ) ) {
		$methods = array( MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SECURITY_QUESTIONS, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OTP_OVER_TELEGRAM, MoWpnsConstants::OUT_OF_BAND_EMAIL );
	}
	if ( get_site_option( 'duo_credentials_save_successfully' ) ) {
		array_push( $methods, 'DUO' );
	}
	return $methods;
}

/**
 * This user shows popup to select inline method.
 *
 * @param string $current_user_id user id of current user of current user.
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $session_id session id.
 * @param array  $qr_code array containg qr code data.
 * @return void
 */
function prompt_user_to_select_2factor_mthod_inline( $current_user_id, $login_status, $login_message, $redirect_to, $session_id, $qr_code ) {
	global $mo2fdb_queries;
	$current_user             = get_userdata( $current_user_id );
	$mo2f_otp_setup           = new Mo2f_Common_Otp_Setup();
	$selected_methods         = fetch_methods( $current_user );
	$current_selected_method  = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $current_user_id );
	$setup_inline_otp_address = dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR . 'setup-otp-over-sms-email-telegram-inline.php';
	if ( MoWpnsConstants::MOBILE_AUTHENTICATION === $current_selected_method || MoWpnsConstants::PUSH_NOTIFICATIONS === $current_selected_method || MoWpnsConstants::SOFT_TOKEN === $current_selected_method ) {
		if ( get_option( 'mo_2factor_admin_registration_status' ) === 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' ) {
			prompt_user_for_miniorange_app_setup( $current_user_id, $login_status, $login_message, $qr_code, $current_selected_method, $redirect_to, $session_id );
		} else {
			prompt_user_for_miniorange_register( $current_user_id, $login_status, $login_message, $redirect_to, $session_id );
		}
	} elseif ( MoWpnsConstants::OTP_OVER_SMS === $current_selected_method ) {
		if ( get_option( 'mo_2factor_admin_registration_status' ) === 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' ) {
			$skeleton = $mo2f_otp_setup->mo2f_sms_common_skeleton( $current_user_id );
			require_once $setup_inline_otp_address;
		} else {
			prompt_user_for_miniorange_register( $current_user_id, $login_status, $login_message, $redirect_to, $session_id );
		}
	} elseif ( MoWpnsConstants::OTP_OVER_TELEGRAM === $current_selected_method ) {
		$skeleton = $mo2f_otp_setup->mo2f_telegram_common_skeleton( $current_user_id );
		require_once $setup_inline_otp_address;
	} elseif ( MoWpnsConstants::OTP_OVER_EMAIL === $current_selected_method ) {
		$skeleton = $mo2f_otp_setup->mo2f_email_common_skeleton( $current_user_id );
		require_once $setup_inline_otp_address;
	} elseif ( MoWpnsConstants::DUO_AUTHENTICATOR === $current_selected_method ) {
		prompt_user_for_duo_authenticator_setup( $current_user_id, $login_status, $login_message, $redirect_to, $session_id );
	} elseif ( MoWpnsConstants::GOOGLE_AUTHENTICATOR === $current_selected_method ) {
		prompt_user_for_google_authenticator_setup( $current_user_id, $login_status, $login_message, $redirect_to, $session_id );
	} elseif ( MoWpnsConstants::SECURITY_QUESTIONS === $current_selected_method ) {
		prompt_user_for_kba_setup( $current_user_id, $login_status, $login_message, $redirect_to, $session_id );
	} elseif ( MoWpnsConstants::OUT_OF_BAND_EMAIL === $current_selected_method ) {
		$status = $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $current_user_id );
		if ( ( 'MO_2_FACTOR_PLUGIN_SETTINGS' === $status ) || ( get_site_option( 'mo2f_disable_kba' ) && 'MO_2_FACTOR_SETUP_SUCCESS' === $login_status ) ) {
			if ( ! MO2F_IS_ONPREM ) {
				$current_user = get_userdata( $current_user_id );
				$email        = $current_user->user_email;
				$temp_email   = get_user_meta( $current_user->ID, 'mo2f_email_miniOrange', true );
				if ( isset( $temp_email ) && ! empty( $temp_email ) ) {
					$email = $temp_email;
				}
				create_user_in_miniorange( $current_user_id, $email, $current_selected_method );
			}
			$mo2fdb_queries->update_user_details( $current_user_id, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS' ) );
			$pass2fa = new Miniorange_Password_2Factor_Login();
			$pass2fa->mo2fa_pass2login( $redirect_to, $session_id );
		}
		prompt_user_for_setup_success( $current_user_id, $login_status, $login_message, $redirect_to, $session_id );
	} else {
		$current_user = get_userdata( $current_user_id );
		if ( isset( $current_user->roles[0] ) ) {
			$current_user_role = $current_user->roles[0];
		}
		?>  
		<html>
			<head>
				<meta charset="utf-8"/>
				<meta http-equiv="X-UA-Compatible" content="IE=edge">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<?php
					mo2f_inline_css_and_js();
				?>
			</head>
			<body>
				<div class="mo2f_modal1" tabindex="-1" role="dialog" id="myModal51">
					<div class="mo2f-modal-backdrop"></div>
					<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
						<div class="login mo_customer_validation-modal-content">
							<div class="mo2f_modal-header">
								<h3 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>

								<?php esc_html_e( 'New security system has been enabled', 'miniorange-2-factor-authentication' ); ?></h3>
							</div>
							<div class="mo2f_modal-body">
							<b>
								<?php
									esc_html_e( 'Configure a Two-Factor method to protect your account', 'miniorange-2-factor-authentication' );
								?>
							</b>
							<?php
							if ( isset( $login_message ) && ! empty( $login_message ) ) {
								echo '<br><br>';
								?>

								<div  id="otpMessage">
									<p class="mo2fa_display_message_frontend" style="text-align: left !important;"><?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?></p>
								</div>
									<?php
							} else {
								echo '<br>';
							}
							?>

								<br>
								<span class="
								<?php
								if ( ! ( in_array( MoWpnsConstants::GOOGLE_AUTHENTICATOR, $selected_methods, true ) ) ) {
									echo 'mo2f_td_hide';
								} else {
									echo 'mo2f_td_show'; }
								?>
								">
									<label title="<?php esc_attr_e( 'You have to enter 6 digits code generated by Authenticator App to login. Supported in Smartphones only.', 'miniorange-2-factor-authentication' ); ?>">
									<input type="radio"  name="mo2f_selected_2factor_method"  value="<?php echo esc_attr( MoWpnsConstants::GOOGLE_AUTHENTICATOR ); ?>"  />
									<?php
									esc_html_e(
										'Google / Authy / Microsoft Authenticator',
										'miniorange-2-factor-authentication'
									);
									?>
									<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<?php
									esc_html_e(
										'(Any TOTP Based Authenticator App)',
										'miniorange-2-factor-authentication'
									);
									?>
								</label>
								<br>
								</span>
								<span class="
								<?php
								if ( ! ( in_array( MoWpnsConstants::OUT_OF_BAND_EMAIL, $selected_methods, true ) ) ) {
									echo 'mo2f_td_hide';
								} else {
									echo 'mo2f_td_show'; }
								?>
								" >
									<label title="<?php esc_attr_e( 'You will receive an email with link. You have to click the ACCEPT or DENY link to verify your email. Supported in Desktops, Laptops, Smartphones.', 'miniorange-2-factor-authentication' ); ?>">
												<input type="radio"  name="mo2f_selected_2factor_method"  value="<?php echo esc_attr( MoWpnsConstants::OUT_OF_BAND_EMAIL ); ?>"  />
												<?php esc_html_e( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OUT_OF_BAND_EMAIL, 'cap_to_small' ), 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?>
									</label>
									<br>
								</span> 
								<span class="
								<?php
								if ( ! ( in_array( MoWpnsConstants::OTP_OVER_SMS, $selected_methods, true ) ) ) {
									echo 'mo2f_td_hide';
								} else {
									echo 'mo2f_td_show'; }
								?>
								" >
										<label title="<?php esc_attr_e( 'You will receive a one time passcode via SMS on your phone. You have to enter the otp on your screen to login. Supported in Smartphones, Feature Phones.', 'miniorange-2-factor-authentication' ); ?>">
											<input type="radio"  name="mo2f_selected_2factor_method"  value="<?php echo esc_attr( MoWpnsConstants::OTP_OVER_SMS ); ?>"  />
											<?php esc_html_e( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_SMS, 'cap_to_small' ), 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?>
										</label>
									<br>
								</span>
								<span class="
								<?php
								if ( ! ( in_array( MoWpnsConstants::SOFT_TOKEN, $selected_methods, true ) ) ) {
									echo 'mo2f_td_hide';
								} else {
									echo 'mo2f_td_show'; }
								?>
								" >
										<label title="<?php esc_attr_e( 'You have to enter 6 digits code generated by miniOrange Authenticator App like Google Authenticator code to login. Supported in Smartphones only.', 'miniorange-2-factor-authentication' ); ?>" >
											<input type="radio"  name="mo2f_selected_2factor_method"  value="SOFT TOKEN"  />
											<?php esc_html_e( 'Soft Token', 'miniorange-2-factor-authentication' ); ?>
										</label>
									<br>
								</span>
								<span class="
								<?php
								if ( ! ( in_array( MoWpnsConstants::OTP_OVER_TELEGRAM, $selected_methods, true ) ) ) {
									echo 'mo2f_td_hide';
								} else {
									echo 'mo2f_td_show'; }
								?>
								" >
										<label title="<?php esc_attr_e( 'You will get an OTP on your Telegram app from miniOrange Bot.', 'miniorange-2-factor-authentication' ); ?>" >
											<input type="radio"  name="mo2f_selected_2factor_method"  value="<?php esc_html_e( MoWpnsConstants::OTP_OVER_TELEGRAM, 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?>"  />
											<?php esc_html_e( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_TELEGRAM, 'cap_to_small' ), 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?>
										</label>
									<br>
								</span>
								<span class="
								<?php
								if ( ! ( in_array( MoWpnsConstants::MOBILE_AUTHENTICATION, $selected_methods, true ) ) ) {
									echo 'mo2f_td_hide';
								} else {
									echo 'mo2f_td_show'; }
								?>
								">
										<label title="<?php esc_attr_e( 'You have to scan the QR Code from your phone using miniOrange Authenticator App to login. Supported in Smartphones only.', 'miniorange-2-factor-authentication' ); ?>">
											<input type="radio"  name="mo2f_selected_2factor_method"  value="MOBILE AUTHENTICATION"  />
											<?php esc_html_e( 'QR Code Authentication', 'miniorange-2-factor-authentication' ); ?>
										</label>
									<br>
								</span>
								<span class="
								<?php
								if ( ! ( in_array( MoWpnsConstants::PUSH_NOTIFICATIONS, $selected_methods, true ) ) ) {
									echo 'mo2f_td_hide';
								} else {
									echo 'mo2f_td_show'; }
								?>
								" >
										<label title="<?php esc_attr_e( 'You will receive a push notification on your phone. You have to ACCEPT or DENY it to login. Supported in Smartphones only.', 'miniorange-2-factor-authentication' ); ?>">
											<input type="radio"  name="mo2f_selected_2factor_method"  value="PUSH NOTIFICATIONS"  />
											<?php esc_html_e( 'Push Notification', 'miniorange-2-factor-authentication' ); ?>
										</label>
										<br>    
								</span>
								<span class="
								<?php
								if ( ! ( in_array( MoWpnsConstants::AUTHY_AUTHENTICATOR, $selected_methods, true ) ) ) {
									echo 'mo2f_td_hide';
								} else {
									echo 'mo2f_td_show'; }
								?>
								">
											<label title="<?php esc_attr_e( 'You have to enter 6 digits code generated by Authy 2-Factor Authentication App to login. Supported in Smartphones only.', 'miniorange-2-factor-authentication' ); ?>">
												<input type="radio"  name="mo2f_selected_2factor_method"  value="<?php echo esc_attr( MoWpnsConstants::AUTHY_AUTHENTICATOR ); ?>"  />
												<?php esc_html_e( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::AUTHY_AUTHENTICATOR, 'cap_to_small' ), 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?>
											</label>
											<br>
								</span>
								<span class="
								<?php
								if ( ! ( in_array( MoWpnsConstants::SECURITY_QUESTIONS, $selected_methods, true ) ) ) {
									echo 'mo2f_td_hide';
								} else {
									echo 'mo2f_td_show'; }
								?>
								">
									<label title="<?php esc_attr_e( 'You have to answers some knowledge based security questions which are only known to you to authenticate yourself. Supported in Desktops,Laptops,Smartphones.', 'miniorange-2-factor-authentication' ); ?>" >
									<input type="radio"  name="mo2f_selected_2factor_method"  value="<?php echo esc_attr( MoWpnsConstants::SECURITY_QUESTIONS ); ?>"  />
												<?php esc_html_e( 'Security Questions ( KBA )', 'miniorange-2-factor-authentication' ); ?>
											</label>
											<br>
								</span>
								<span class="
								<?php
								if ( ! ( in_array( MoWpnsConstants::OTP_OVER_EMAIL, $selected_methods, true ) ) ) {
									echo 'mo2f_td_hide';
								} else {
									echo 'mo2f_td_show'; }
								?>
								">
									<label title="<?php esc_attr_e( 'You will receive a one time passcode on your email. You have to enter the otp on your screen to login. Supported in Smartphones, Feature Phones.', 'miniorange-2-factor-authentication' ); ?>" >
									<input type="radio"  name="mo2f_selected_2factor_method"  value="<?php echo esc_attr( MoWpnsConstants::OTP_OVER_EMAIL ); ?>"  />
												<?php esc_html_e( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_EMAIL, 'cap_to_small' ), 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?>
											</label>
											<br>
								</span>

								<?php

								$object = new Miniorange_Password_2Factor_Login();
 
								if ( get_option( 'mo2f_grace_period' ) === 'on' && ( ! $object->mo2f_is_grace_period_expired( $current_user ) ) ) {

									?>
								<br>
										<a href="#skiptwofactor" style="color:#F4D03F ;font-weight:bold;margin-left:35%;"><?php esc_html_e( 'Skip Two Factor', 'miniorange-2-factor-authentication' ); ?></a>
										<br>
										<?php } ?>

								<?php mo2f_customize_logo(); ?>
							</div>
						</div>
					</div>
				</div>
				<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" style="display:none;">
					<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
					<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
				</form>
				<form name="f" method="post" action="" id="mo2f_select_2fa_methods_form" style="display:none;">
					<input type="hidden" name="mo2f_selected_2factor_method" />
					<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ); ?>" />
					<input type="hidden" name="option" value="miniorange_inline_save_2factor_method" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
					<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
				</form>

				<form name="f" id="mo2f_skip_loginform" method="post" action="" style="display:none;">
					<input type="hidden" name="option" value="mo2f_skip_2fa_setup" />
					<input type="hidden" name="miniorange_skip_2fa_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-skip-nonce' ) ); ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
					<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
				</form>
			<script>
				function mologinback(){
					jQuery('#mo2f_backto_mo_loginform').submit();
				}
				jQuery('input:radio[name=mo2f_selected_2factor_method]').click(function() {
					var selectedMethod = jQuery(this).val();
					document.getElementById("mo2f_select_2fa_methods_form").elements[0].value = selectedMethod;
					jQuery('#mo2f_select_2fa_methods_form').submit();
				});
				jQuery('a[href="#skiptwofactor"]').click(function(e) {
				jQuery('#mo2f_skip_loginform').submit();
			});
			</script>
			</body>
		</html>
		<?php
	}

}

/**
 * This function used for register user.
 *
 * @param string $current_user_id user id of current user.
 * @param string $email user email.
 * @param string $current_method current 2fa method.
 * @return string
 */
function create_user_in_miniorange( $current_user_id, $email, $current_method ) {
	global $mo2fdb_queries;
	$mo2f_user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $current_user_id );
	if ( isset( $mo2f_user_email ) && ! empty( $mo2f_user_email ) ) {
		$email = $mo2f_user_email;
	}

	$current_user = get_userdata( $current_user_id );
	if ( get_option( 'mo2f_miniorange_admin' ) === $current_user_id ) {
		$email = get_option( 'mo2f_email' );
	}

		$enduser    = new Two_Factor_Setup_Onprem_Cloud();
		$check_user = json_decode( $enduser->mo_check_user_already_exist( $email ), true );

	if ( json_last_error() === JSON_ERROR_NONE ) {

		if ( 'ERROR' === $check_user['status'] ) {
			return MoWpnsMessages::lang_translate( $check_user['message'] );

		} elseif ( strcasecmp( $check_user['status'], 'USER_FOUND' ) === 0 ) {

			$mo2fdb_queries->update_user_details(
				$current_user_id,
				array(
					'user_registration_with_miniorange'   => 'SUCCESS',
					'mo2f_user_email'                     => $email,
					'mo_2factor_user_registration_status' => 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR',
				)
			);
			update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- This is warning for base64_encode function.

			$mo2fa_login_status = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
		} elseif ( strcasecmp( $check_user['status'], 'USER_NOT_FOUND' ) === 0 ) {

			$content = json_decode( $enduser->mo_create_user( $current_user, $email ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) {
					update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- This is warning for base64_encode function.
					$mo2fdb_queries->update_user_details(
						$current_user_id,
						array(
							'user_registration_with_miniorange' => 'SUCCESS',
							'mo2f_user_email' => $email,
							'mo_2factor_user_registration_status' => 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR',
						)
					);

					$mo2fa_login_message = '';
					$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
				}
			}
		} elseif ( strcasecmp( $check_user['status'], 'USER_FOUND_UNDER_DIFFERENT_CUSTOMER' ) === 0 ) {
			$mo2fa_login_message = __( 'The email associated with your account is already registered. Please contact your admin to change the email.', 'miniorange-2-factor-authentication' );
			$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_FOR_RELOGIN';
			mo2f_inline_email_form( $email, $current_user_id );
			exit;
		}
	}

}

/**
 * This functions shows inline email form
 *
 * @param string $email user email.
 * @param string $current_user_id user id of current user.
 * @return void
 */
function mo2f_inline_email_form( $email, $current_user_id ) {
	?>
	<html>
			<head>
				<meta charset="utf-8"/>
				<meta http-equiv="X-UA-Compatible" content="IE=edge">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<?php
					mo2f_inline_css_and_js();
				?>
			</head>
			<body>
				<div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
					<div class="mo2f-modal-backdrop"></div>
					<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
						<div class="login mo_customer_validation-modal-content">
							<div class="mo2f_modal-header">
								<h3 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
								<?php esc_html_e( 'Email already registered.', 'miniorange-2-factor-authentication' ); ?></h3>
							</div>
							<div class="mo2f_modal-body">
								<form action="" method="post" name="f">
									<p>The Email assoicated with your account is already registered in miniOrange. Please use a different email address or contact miniOrange.
									</p><br>
									<i><b>Enter your Email:&nbsp;&nbsp;&nbsp; </b> <input type ='email' id='emailInlineCloud' name='emailInlineCloud' size= '40' required value="<?php echo esc_attr( $email ); ?>"/></i>
									<br>
									<p id="emailalredyused" style="color: red;" hidden>This email is already associated with miniOrange.</p>
									<br>
									<input type="hidden" name="miniorange_emailChange_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-email-change-nonce' ) ); ?>" />
									<input type="text" name="current_user_id" hidden id="current_user_id" value="<?php echo esc_attr( $current_user_id ); ?>" />
									<button type="submit" class="button button-primary button-large" style ="margin-left: 165px;" id="save_entered_email_inlinecloud">Save</button>
								</form>
									<br>
								<?php mo2f_customize_logo(); ?>
							</div>
						</div>
					</div>
				</div>
				<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" style="display:none;">
					<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo esc_attr( ( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>" />
				</form>
				<form name="f" method="post" action="" id="mo2f_select_2fa_methods_form" style="display:none;">
					<input type="hidden" name="mo2f_selected_2factor_method" />
					<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ); ?>" />
					<input type="hidden" name="option" value="miniorange_inline_save_2factor_method" />
				</form>
				<?php if ( get_site_option( 'mo2f_skip_inline_option' ) && ! get_site_option( 'mo2f_enable_emailchange' ) ) { ?>
				<form name="f" id="mo2f_skip_loginform" method="post" action="" style="display:none;">
					<input type="hidden" name="miniorange_skip_2fa" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-skip-nonce' ) ); ?>" />
				</form>
							<?php } ?>
			<script type="text/javascript">
				jQuery('#save_entered_email_inlinecloud1').click(function(){
					var email = jQuery('#emailInlineCloud').val();
					var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>';
					var data = {
								'action'                    : 'mo_two_factor_ajax',
								'mo_2f_two_factor_ajax'     : 'mo2f_check_user_exist_miniOrange',
								'email'                     : email,
								'nonce' :  nonce
							};

					var ajaxurl = '<?php echo esc_url( admin_url( '' ) ); ?>';

					jQuery.post(ajaxurl, data, function(response) {
						if(response === 'alreadyExist')
						{
							jQuery('#emailalredyused').show();
						}
						else if(response ==='USERCANBECREATED')
						{
							document.getElementById("mo2f_select_2fa_methods_form").elements[0].value = selectedMethod;
							jQuery('#mo2f_select_2fa_methods_form').submit();
						}
					});

				});

			</script>
			</body>

	<?php
}

/**
 * This function shows QR code.
 *
 * @param string $current_user_id user id of current user.
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param array  $qr_code array containg qr code data.
 * @param string $current_method current method.
 * @param string $redirect_to redirect url.
 * @param string $session_id session id.
 * @return void
 */
function prompt_user_for_miniorange_app_setup( $current_user_id, $login_status, $login_message, $qr_code, $current_method, $redirect_to, $session_id ) {

	global $mo2fdb_queries;
	if ( isset( $qr_code ) ) {
		$qr_codedata  = $qr_code['mo2f-login-qrCode'];
		$show_qr_code = $qr_code['mo2f_show_qr_code'];
	}
	$current_user = get_userdata( $current_user_id );
	$email        = $current_user->user_email;

	$opt = fetch_methods( $current_user );

	$mobile_registration_status = $mo2fdb_queries->get_user_detail( 'mobile_registration_status', $current_user_id );
	?>
	<html>
		<head>  <meta charset="utf-8"/>
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php
				mo2f_inline_css_and_js();
			?>
		</head>
		<body>
			<div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
				<div class="mo2f-modal-backdrop"></div>
				<div class="mo2f_modal-dialog mo2f_modal-lg" >
					<div class="login mo_customer_validation-modal-content">
						<div class="mo2f_modal-header">
							<h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
							<?php esc_html_e( 'Setup miniOrange', 'miniorange-2-factor-authentication' ); ?> <b><?php esc_html_e( 'Authenticator', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( 'App', 'miniorange-2-factor-authentication' ); ?></h4>
						</div>
						<div class="mo2f_modal-body">
							<?php if ( isset( $login_message ) && ! empty( $login_message ) ) { ?>
								<div  id="otpMessage">
									<p class="mo2fa_display_message_frontend" style="text-align: left !important;"><?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?></p>
								</div>
							<?php } ?>
							<div style="margin-right:7px;"><?php download_instruction_for_mobile_app( $current_user_id, $mobile_registration_status ); ?></div>
							<div class="mo_margin_left">
								<h3><?php esc_html_e( 'Step-2 : Scan QR code', 'miniorange-2-factor-authentication' ); ?></h3><hr class="mo_hr">
								<div id="mo2f_configurePhone"><h4><?php esc_html_e( 'Please click on \'Configure your phone\' button below to see QR Code.', 'miniorange-2-factor-authentication' ); ?></h4>
									<div class="mo2fa_text-align-center">
									<?php if ( count( $opt ) > 1 ) { ?>
										<input type="button" name="back" id="mo2f_inline_back_btn" class="miniorange_button" value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>" />
									<?php } ?>
										<input type="button" name="submit" onclick="moconfigureapp();" class="miniorange_button" value="<?php esc_attr_e( 'Configure your phone', 'miniorange-2-factor-authentication' ); ?>" />
									</div>
								</div>
								<?php
								if ( isset( $show_qr_code ) && 'MO_2_FACTOR_SHOW_QR_CODE' === $show_qr_code && isset( $_POST['miniorange_inline_show_qrcode_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['miniorange_inline_show_qrcode_nonce'] ) ), 'miniorange-2-factor-inline-show-qrcode-nonce' ) ) {
									initialize_inline_mobile_registration( $current_user, $session_id, $qr_codedata );
									?>
								<?php } ?>
							<?php mo2f_customize_logo(); ?>
							</div>
							<br>
							<br>
						</div>
					</div>
				</div>
			</div>
			<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" style="display:none;">
				<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>" />
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
			</form>
			<form name="f" method="post" action="" id="mo2f_inline_configureapp_form" style="display:none;">
				<input type="hidden" name="option" value="miniorange_inline_show_mobile_config"/>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
				<input type="hidden" name="miniorange_inline_show_qrcode_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-show-qrcode-nonce' ) ); ?>" />
			</form>
			<form name="f" method="post" id="mo2f_inline_mobile_register_form" action="" style="display:none;">
				<input type="hidden" name="option" value="miniorange_inline_complete_mobile"/>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
				<input type="hidden" name="mo_auth_inline_mobile_registration_complete_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-mobile-registration-complete-nonce' ) ); ?>" />
			</form>
			<?php if ( count( $opt ) > 1 ) { ?>
				<form name="f" method="post" action="" id="mo2f_goto_two_factor_form">
					<input type="hidden" name="option" value="miniorange_back_inline"/>
					<input type="hidden" name="miniorange_inline_two_factor_setup" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-setup-nonce' ) ); ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
					<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
				</form>
			<?php } ?>
		<script>
			function mologinback(){
				jQuery('#mo2f_backto_mo_loginform').submit();
			}
			function moconfigureapp(){
				jQuery('#mo2f_inline_configureapp_form').submit();
			}
			jQuery('#mo2f_inline_back_btn').click(function() {  
					jQuery('#mo2f_goto_two_factor_form').submit();
			});
			<?php
			if ( isset( $show_qr_code ) && 'MO_2_FACTOR_SHOW_QR_CODE' === $show_qr_code && isset( $_POST['miniorange_inline_show_qrcode_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['miniorange_inline_show_qrcode_nonce'] ) ), 'miniorange-2-factor-inline-show-qrcode-nonce' ) ) {
				?>
			<?php } ?>
		</script>
		</body>
	</html>
	<?php
}

/**
 * This function shows duo authenticator setup
 *
 * @param string $current_user_id user id of current user.
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $session_id session id.
 * @return void
 */
function prompt_user_for_duo_authenticator_setup( $current_user_id, $login_status, $login_message, $redirect_to, $session_id ) {
	global $mo2fdb_queries;
	$current_user               = get_userdata( $current_user_id );
	$email                      = $current_user->user_email;
	$opt                        = fetch_methods( $current_user );
	$mobile_registration_status = $mo2fdb_queries->get_user_detail( 'mobile_registration_status', $current_user_id );

	?>
	<html>
		<head>  <meta charset="utf-8"/>
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php
				mo2f_inline_css_and_js();
			?>
		</head>
		<body>
			<div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
				<div class="mo2f-modal-backdrop"></div>
				<div class="mo2f_modal-dialog mo2f_modal-lg" >
					<div class="login mo_customer_validation-modal-content">
						<div class="mo2f_modal-header">
							<h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
							<?php esc_html_e( 'Setup Duo', 'miniorange-2-factor-authentication' ); ?> <b><?php esc_html_e( 'Authenticator', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( 'App', 'miniorange-2-factor-authentication' ); ?></h4>
						</div>
						<div class="mo2f_modal-body">
							<?php if ( isset( $login_message ) && ! empty( $login_message ) ) { ?>

								<div  id="otpMessage">
									<p class="mo2fa_display_message_frontend" style="text-align: left !important;"><?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?></p>
								</div>
							<?php } ?>
							<div style="margin-right:7px;">
							<?php
							mo2f_inline_download_instruction_for_duo_mobile_app( $mobile_registration_status );

							?>
							</div>
							<div class="mo_margin_left">
								<h3><?php esc_html_e( 'Step-2 : Setup Duo Push Notification', 'miniorange-2-factor-authentication' ); ?></h3><hr class="mo_hr">
								<div id="mo2f_configurePhone"><h4><?php esc_html_e( 'Please click on \'Configure your phone\' button below to setup duo push notification.', 'miniorange-2-factor-authentication' ); ?></h4>
									<div class="mo2fa_text-align-center">
									<?php if ( count( $opt ) > 1 ) { ?>
										<input type="button" name="back" id="mo2f_inline_back_btn" class="miniorange_button" value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>" />
									<?php } ?>
										<input type="button" name="submit" onclick="moconfigureapp();" class="miniorange_button" value="<?php esc_attr_e( 'Configure your phone', 'miniorange-2-factor-authentication' ); ?>" />
									</div>
								</div>
								<?php

								$duo_nonce       = isset( $_POST['mo_auth_inline_duo_auth_mobile_registration_complete_nonce'] ) ? sanitize_key( $_POST['mo_auth_inline_duo_auth_mobile_registration_complete_nonce'] ) : '';
								$push_noti_nonce = isset( $_POST['duo_mobile_send_push_notification_inline_form_nonce'] ) ? sanitize_key( $_POST['duo_mobile_send_push_notification_inline_form_nonce'] ) : '';

								if ( wp_verify_nonce( $duo_nonce, 'miniorange-2-factor-inline-duo_auth-registration-complete-nonce' ) && isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) === 'miniorange_inline_duo_auth_mobile_complete' ) ) {
									mo2f_go_for_user_enroll_on_duo( $current_user, $session_id );
								} elseif ( wp_verify_nonce( $duo_nonce, 'mo2f-send-duo-push-notification-inline-nonce' ) && isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'duo_mobile_send_push_notification_for_inline_form' ) {
										initialize_inline_duo_auth_registration( $current_user, $session_id );
								}
								mo2f_customize_logo();
								?>
							</div>
							<br>
							<br>
						</div>
					</div>
				</div>
			</div>
			<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" style="display:none;">
				<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>" />
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
			</form>
			<form name="f" method="post" action="" id="mo2f_inline_configureapp_form" style="display:none;">
				<input type="hidden" name="option" value="miniorange_inline_show_mobile_config"/>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
				<input type="hidden" name="miniorange_inline_show_qrcode_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-show-qrcode-nonce' ) ); ?>" />
			</form>
			<form name="f" method="post" id="mo2f_inline_duo_auth_register_form" action="" style="display:none;">
				<input type="hidden" name="option" value="miniorange_inline_duo_auth_mobile_complete"/>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
				<input type="hidden" name="mo_auth_inline_duo_auth_mobile_registration_complete_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-duo_auth-registration-complete-nonce' ) ); ?>" />
			</form>
			<?php if ( count( $opt ) > 1 ) { ?>
				<form name="f" method="post" action="" id="mo2f_goto_two_factor_form">
					<input type="hidden" name="option" value="miniorange_back_inline"/>
					<input type="hidden" name="miniorange_inline_two_factor_setup" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-setup-nonce' ) ); ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
					<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
				</form>
			<?php } ?>
		<script>
			function mologinback(){
				jQuery('#mo2f_backto_mo_loginform').submit();
			}
			function moconfigureapp(){
				jQuery('#mo2f_inline_duo_auth_register_form').submit();
			}
			jQuery('#mo2f_inline_back_btn').click(function() {  
					jQuery('#mo2f_goto_two_factor_form').submit();
			});
			<?php
			if ( isset( $show_qr_code ) && 'MO_2_FACTOR_SHOW_QR_CODE' === $show_qr_code && isset( $_POST['miniorange_inline_show_qrcode_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['miniorange_inline_show_qrcode_nonce'] ) ), 'miniorange-2-factor-inline-show-qrcode-nonce' ) ) {
				?>
			<?php } ?>
		</script>
		</body>
	</html>
	<?php
}

/**
 * This user shows google authenticator setup
 *
 * @param string $current_user_id user id of current user.
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $session_id session id.
 * @return void
 */
function prompt_user_for_google_authenticator_setup( $current_user_id, $login_status, $login_message, $redirect_to, $session_id ) {
	include_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
	$ga_secret = MO2f_Utility::mo2f_get_transient( $session_id, 'secret_ga' );
	$data      = MO2f_Utility::mo2f_get_transient( $session_id, 'ga_qrCode' );
	$ga_data   = new Google_Auth_Onpremise();
	if ( ! $ga_secret ) {
		$ga_secret = $ga_data->mo2f_create_secret();
		MO2f_Utility::mo2f_set_transient( $session_id, 'secret_ga', $ga_secret );
	}
	$gauth_name = get_option( 'mo2f_google_appname', DEFAULT_GOOGLE_APPNAME );
	$gauth_name = preg_replace( '#^https?://#i', '', $gauth_name );
	global $mo2fdb_queries;
	if ( empty( $data ) || ! MO2F_IS_ONPREM ) {
		$user      = get_user_by( 'ID', $current_user_id );
		$email     = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
		$ga_secret = $ga_data->mo2f_create_secret();
		$data      = $ga_data->mo2f_geturl( $ga_secret, $gauth_name, $email );
		MO2f_Utility::mo2f_set_transient( $session_id, 'ga_qrCode', $data );

	}
	$microsoft_url = $ga_data->mo2f_geturl( $ga_secret, $gauth_name, '' );
	$qr_code       = '<div class="mo2f_gauth" id= "mo2f_google_auth_qr_code" style="background: white;" data-qrcode="' . $data . '" ></div>';
	MO2f_Utility::mo2f_set_transient( $session_id, 'secret_ga', $ga_secret );
	global $main_dir;
	wp_register_script( 'mo2f_qr_code_minjs', plugins_url( '/includes/jquery-qrcode/jquery-qrcode.min.js', dirname( dirname( __FILE__ ) ) ), array(), MO2F_VERSION, false );
	?>
	<html>
		<head>  <meta charset="utf-8"/>
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php
				mo2f_inline_css_and_js();
				wp_print_scripts( 'mo2f_qr_code_js' );
				wp_print_scripts( 'mo2f_qr_code_minjs' );
			?>
		</head>
		<body>
			<div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
				<div class="mo2f-modal-backdrop">
				</div>
				<div class="mo2f_modal-dialog mo2f_modal-lg">
					<div class="login mo_customer_validation-modal-content">
					<?php
					$inline_back_button = '<form name="f" method="post" action="" id="mo2f_goto_two_factor_form" class="center">
					<input type="hidden" name="option" value="miniorange_back_inline"/>
					<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
					<input type="hidden" name="session_id" value="' . esc_attr( $session_id ) . '"/>
					<input type="hidden" name="miniorange_inline_two_factor_setup" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-setup-nonce' ) ) . '" />
					<input type="submit" name="back" id="mo2f_inline_back_btn" class="button button-primary button-large" value="' . esc_attr__( 'Back', 'miniorange-2-factor-authentication' ) . '" />
					</form>';

					$validate_input_fields = '<input type="hidden" name="option" value="miniorange_inline_ga_setup_success">
					<input type="hidden" name="redirect_to" value="' . $redirect_to . '"/>
					<input type="hidden" name="session_id" value="' . $session_id . '"/>
					<input type="hidden" name="mo2f_inline_validate_ga_nonce" value="' . wp_create_nonce( 'miniorange-2-factor-inline-google-auth-nonce' ) . '" />';
					mo2f_configure_google_auth_common_view( $ga_secret, $gauth_name, $qr_code, $data, $microsoft_url, $session_id, $redirect_to, true, $inline_back_button, $validate_input_fields, $login_message );
					?>
					<div class="mo2f_customize_logo">
						<?php mo2f_customize_logo(); ?>
					</div>
					</div>
				</div>
			</div>
	</body>

		<script>
			function mo2f_validate_gauth(nonce, ga_secret){
				var data = {
					"action"                 : "mo_two_factor_ajax",
					"mo_2f_two_factor_ajax"  : "mo2f_validate_google_authenticator",
					"session_id"             : gAuthValidate.session_id,
					"redirect_to"            : gAuthValidate.redirect_to,
					"nonce"                  : nonce,	
					"otp_token"              : jQuery("#google_auth_code").val(),
					"ga_secret"              : ga_secret,
				};
				jQuery.ajax({
					url: ajaxurl,
					type: "POST",
					dataType: "json",
					data: data,
					success: function (response) {
						if (response.success) {
							jQuery('#mo2f_validate_code_form').submit();
						} else {
							jQuery('#otpMessage').css('display','block');
							jQuery('#mo2f_gauth_inline_message').text(response.data);
						}
					}
				});
			}
			function mologinback(){
				jQuery('#mo2f_backto_mo_loginform').submit();
			}
			var ajaxurl = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
			var nonce = "<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>";
			var ms_url = "<?php echo esc_js( $microsoft_url ); ?>";
			var gu_url ="<?php echo esc_js( $data ); ?>";
			var session_id = "<?php echo esc_js( $session_id ); ?>";
			jQuery('a[href="#mo2f_scanbarcode_a"]').click(function(e){
				var element = document.getElementById('mo2f_scanbarcode_a');
				if(element.style.display === 'none')
					element.style.display = 'block';				
				else
					element.style.display = "none";
			}); 
		</script>';
		<?php
}

/**
 * This function includes css,js scripts.
 *
 * @return void
 */
function mo2f_inline_css_and_js() {

	wp_register_style( 'mo2f_bootstrap', plugins_url( 'includes/css/bootstrap.min.css', dirname( dirname( __FILE__ ) ) ), array(), MO2F_VERSION, false );
	wp_register_style( 'mo2f_front_end_login', plugins_url( 'includes/css/front_end_login.min.css', dirname( dirname( __FILE__ ) ) ), array(), MO2F_VERSION, false );
	wp_register_style( 'mo2f_style_setting', plugins_url( 'includes/css/style_settings.min.css', dirname( dirname( __FILE__ ) ) ), array(), MO2F_VERSION, false );
	wp_register_style( 'mo2f_hide-login', plugins_url( 'includes/css/hide-login.min.css', dirname( dirname( __FILE__ ) ) ), array(), MO2F_VERSION, false );

	wp_print_styles( 'mo2f_bootstrap' );
	wp_print_styles( 'mo2f_front_end_login' );
	wp_print_styles( 'mo2f_style_setting' );
	wp_print_styles( 'mo2f_hide-login' );

	wp_register_script( 'mo2f_bootstrap_js', plugins_url( 'includes/js/bootstrap.min.js', dirname( dirname( __FILE__ ) ) ), array(), MO2F_VERSION, false );
	wp_print_scripts( 'jquery' );
	wp_print_scripts( 'mo2f_bootstrap_js' );
}

/**
 * Show authenticator methods instructions.
 *
 * @return void
 */
function mo2f_authenticator_methods_instructions() {
	$auth_methods_and_instructions = array(
		'google_authenticator' => array( 'In the app, tap on "%1$1sAdd a code%2$2s" or "%3$3s+%4$4s" sign at right bottom corner of the phone', 'Select "%1$1sScan a QR code%2$2s".' ),
		'msft_authenticator'   => array( 'In the app, tap on "%1$1sAdd account%2$2s" or "%3$3s+%4$4s" sign at right top corner of the phone', 'Select any account from "%1$1sPersonal account/Work or school account/other account(Google, Facebook, etc.)%2$2s".' ),
		'authy_authenticator'  => array( 'In the app, tap on "%1$1s+%2$2s" sign at the center of the phone', 'Tap on "%1$1sScan QR Code%2$2s".' ),
		'last_pass_auth'       => array( 'In the app, tap on "%1$1sAdd account%2$2s" or "%3$3s+%4$4s" sign at right bottom corner of the phone', 'Tap on "%1$1sScan QR Code%2$2s".' ),
		'free_otp_auth'        => array( 'In the app, tap on "%1$1s+%2$2s" sign at the right bottom corner of the phone', 'Tap on "%1$1sQR Code%2$2s" symbol.' ),
		'duo_auth'             => array( 'In the app, tap on "%1$1sSet up account%2$2s"', 'Tap on "%1$1sUse a QR code%2$2s"' ),

	);
	foreach ( $auth_methods_and_instructions as $method => $instructions ) {
		?>
<div id="mo2f_<?php echo esc_attr( $method ); ?>_instructions" style="display:<?php echo esc_attr( 'google_authenticator' !== $method ? 'none' : 'block' ); ?>">
		<?php
		foreach ( $instructions as $instruction ) {
			?>
<li>
			<?php
			printf(
			/* Translators: %s: bold tags */
				esc_html( __( $instruction, 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
				'<b>',
				'</b>',
				'<b>',
				'</b>',
			);
			?>
	</li>
			<?php

		}
		?>

</div>

		<?php
	}
	?>

	<li>
	<?php
	printf(
		/* Translators: %s: bold tags */
		esc_html( __( 'Scan below QR Code.', 'miniorange-2-factor-authentication' ) ),
	);
	?>
	</li>
	<?php
}
/**
 * This function initializes mobie registration
 *
 * @param object $current_user user object.
 * @param string $session_id session id.
 * @param array  $qr_code array containg qr code data.
 * @return void
 */
function initialize_inline_mobile_registration( $current_user, $session_id, $qr_code ) {
		$data = $qr_code;

		$mo2f_login_transaction_id = MO2f_Utility::mo2f_get_transient( $session_id, 'mo2f_transactionId' );

		$url = MO_HOST_NAME;
		$opt = fetch_methods( $current_user );
	?>
			<p><?php esc_html_e( 'Open your miniOrange', 'miniorange-2-factor-authentication' ); ?><b> <?php esc_html_e( 'Authenticator', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( 'app and click on', 'miniorange-2-factor-authentication' ); ?> <b><?php esc_html_e( 'Configure button', 'miniorange-2-factor-authentication' ); ?> </b> <?php esc_html_e( 'to scan the QR Code. Your phone should have internet connectivity to scan QR code.', 'miniorange-2-factor-authentication' ); ?> </p>
			<div class="red" style="color:#E74C3C;">
			<p><?php esc_html_e( 'I am not able to scan the QR code,', 'miniorange-2-factor-authentication' ); ?> <a  data-toggle="mo2f_collapse" href="#mo2f_scanqrcode" aria-expanded="false"  style="color:#3498DB;"><?php esc_html_e( 'click here ', 'miniorange-2-factor-authentication' ); ?></a></p></div>
			<div class="mo2f_collapse" id="mo2f_scanqrcode" style="margin-left:5px;">
				<?php esc_html_e( 'Follow these instructions below and try again.', 'miniorange-2-factor-authentication' ); ?>
				<ol>
					<li><?php esc_html_e( 'Make sure your desktop screen has enough brightness.', 'miniorange-2-factor-authentication' ); ?></li>
					<li><?php esc_html_e( 'Open your app and click on Configure button to scan QR Code again.', 'miniorange-2-factor-authentication' ); ?></li>
					<li><?php esc_html_e( 'If you get cross mark on QR Code then click on \'Refresh QR Code\' link.', 'miniorange-2-factor-authentication' ); ?></li>
				</ol>
			</div>
			<table class="mo2f_settings_table">
				<a href="#mo2f_refreshQRCode" style="color:#3498DB;"><?php esc_html_e( 'Click here to Refresh QR Code.', 'miniorange-2-factor-authentication' ); ?></a>
				<div id="displayInlineQrCode" style="margin-left:36%;"><?php echo '<img style="width:200px;" src="data:image/jpg;base64,' . esc_html( $data ) . '" />'; ?>
				</div>
			</table>
			<div class="mo2fa_text-align-center">
				<?php
				if ( count( $opt ) > 1 ) {
					?>
					<input type="button" name="back" id="mo2f_inline_back_btn" class="miniorange_button" value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>" />
					<?php
				}
				?>
			</div>
			<script>
				jQuery('a[href="#mo2f_refreshQRCode"]').click(function(e) { 
					jQuery('#mo2f_inline_configureapp_form').submit();
				});
					jQuery("#mo2f_configurePhone").empty();
					jQuery("#mo2f_app_div").hide();
					var timeout;
					pollInlineMobileRegistration();
					function pollInlineMobileRegistration()
					{
						var transId = "<?php echo esc_js( $mo2f_login_transaction_id ); ?>";
						var jsonString = "{\"txId\":\""+ transId + "\"}";
						var postUrl = "<?php echo esc_url( $url ); ?>" + "/moas/api/auth/registration-status";
						jQuery.ajax({
							url: postUrl,
							type : "POST",
							dataType : "json",
							data : jsonString,
							contentType : "application/json; charset=utf-8",
							success : function(result) {
								var status = JSON.parse(JSON.stringify(result)).status;
								if (status === 'SUCCESS') {
									var content = "<br/><div id='success'><img style='width:165px;margin-top:-1%;margin-left:2%;' src='" + "<?php echo esc_url( plugins_url( 'includes/images/right.png', dirname( dirname( __FILE__ ) ) ) ); ?>" + "' /></div>";
									jQuery("#displayInlineQrCode").empty();
									jQuery("#displayInlineQrCode").append(content);
									setTimeout(function(){jQuery("#mo2f_inline_mobile_register_form").submit();}, 1000);
								} else if (status === 'ERROR' || status === 'FAILED') {
									var content = "<br/><div id='error'><img style='width:165px;margin-top:-1%;margin-left:2%;' src='" + "<?php echo esc_url( plugins_url( 'includes/images/wrong.png', __FILE__ ) ); ?>" + "' /></div>";
									jQuery("#displayInlineQrCode").empty();
									jQuery("#displayInlineQrCode").append(content);
									jQuery("#messages").empty();
									jQuery("#messages").append("<div class='error mo2f_error_container'> <p class='mo2f_msgs'>An Error occured processing your request. Please try again to configure your phone.</p></div>");
								} else {
									timeout = setTimeout(pollInlineMobileRegistration, 3000);
								}
							}
						});
					}   
			</script>
	<?php
}

/**
 * This function initialize duo authenticator registration in inline flow.
 *
 * @param object $current_user object containing user details.
 * @param string $session_id_encrypt encrypted session id.
 * @return void
 */
function initialize_inline_duo_auth_registration( $current_user, $session_id_encrypt ) {

	$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
	update_user_meta( $user_id, 'current_user_email', $current_user->user_email );

	$opt = fetch_methods( $current_user );
	?>
			<h3><?php echo esc_html__( 'Test Duo Authenticator', 'miniorange-2-factor-authentication' ); ?></h3>
	<hr>
	<div>
		<br>
		<br>
		<div classs="mo2fa_text-align-center">
			<h3><?php echo esc_html__( 'Duo push notification is sent to your mobile phone.', 'miniorange-2-factor-authentication' ); ?>
				<br>
				<?php echo esc_html__( 'We are waiting for your approval...', 'miniorange-2-factor-authentication' ); ?></h3>
			<img src="<?php echo esc_url( plugins_url( 'includes/images/ajax-loader-login.gif', dirname( dirname( __FILE__ ) ) ) ); ?>"/>
		</div>

		<input type="button" name="back" id="go_back" class="button button-primary button-large"
				value="<?php echo esc_attr__( 'Back', 'miniorange-2-factor-authentication' ); ?>"
				style="margin-top:100px;margin-left:10px;"/>
	</div>

	<form name="f" method="post" action="" id="mo2f_go_back_form">
		<input type="hidden" name="option" value="mo2f_go_back"/>
		<input type="hidden" name="mo2f_go_back_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
	</form>
	<form name="f" method="post" id="mo2f_inline_duo_authenticator_success_form" action="">
		<input type="hidden" name="option" value="mo2f_inline_duo_authenticator_success_form"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
		<input type="hidden" name="mo2f_duo_authenticator_success_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'mo2f-duo-authenticator-success-nonce' ) ); ?>"/>
	</form>
	<form name="f" method="post" id="mo2f_duo_authenticator_error_form" action="">
		<input type="hidden" name="option" value="mo2f_inline_duo_authenticator_error"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
		<input type="hidden" name="mo2f_inline_duo_authentcator_error_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'mo2f-inline-duo-authenticator-error-nonce' ) ); ?>"/>
	</form>

	<script>
		jQuery('#go_back').click(function () {
			jQuery('#mo2f_go_back_form').submit();
		});
		jQuery("#mo2f_configurePhone").empty();
		jQuery("#mo2f_app_div").hide();
		var timeout;



			pollMobileValidation();
			function pollMobileValidation() {
				var ajax_url = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
				var nonce = "<?php echo esc_js( wp_create_nonce( 'miniorange-2-factor-duo-nonce' ) ); ?>";
				var session_id_encrypt = "<?php echo esc_js( $session_id_encrypt ); ?>";

				var data={
				'action':'mo2f_duo_ajax_request',
				'call_type':'check_duo_push_auth_status',
				'session_id_encrypt': session_id_encrypt,
				'nonce': nonce,
			}; 

			jQuery.post(ajax_url, data, function(response){
						if (response === 'SUCCESS') {
							jQuery('#mo2f_inline_duo_authenticator_success_form').submit();
						} else if (response === 'ERROR' || response === 'FAILED' || response === 'DENIED') {

							jQuery('#mo2f_duo_authenticator_error_form').submit();
						} else {
							timeout = setTimeout(pollMobileValidation, 3000);
						}
				});
			}

	</script>

	<?php
}

/**
 * This function shows KBA setup screen
 *
 * @param string $current_user_id user id of current user.
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $session_id session id.
 * @return void
 */
function prompt_user_for_kba_setup( $current_user_id, $login_status, $login_message, $redirect_to, $session_id ) {
	$current_user = get_userdata( $current_user_id );
	$opt          = fetch_methods( $current_user );

	?>
	<html>
		<head>  <meta charset="utf-8"/>
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php
				mo2f_inline_css_and_js();
			?>
			<style>
				.mo2f_kba_ques, .mo2f_table_textbox{
					background: whitesmoke none repeat scroll 0% 0%;
				}
			</style>
		</head>
		<body>
			<div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
				<div class="mo2f-modal-backdrop"></div>
				<div class="mo2f_modal-dialog mo2f_modal-lg">
					<div class="login mo_customer_validation-modal-content">
						<div class="mo2f_modal-header">
							<h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
							<?php esc_html_e( 'Setup Security Question (KBA)', 'miniorange-2-factor-authentication' ); ?></h4>
						</div>
						<div class="mo2f_modal-body">
							<?php if ( isset( $login_message ) && ! empty( $login_message ) ) { ?>
								<div  id="otpMessage">
									<p class="mo2fa_display_message_frontend" style="text-align: left !important;"><?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?></p>
								</div>
							<?php } ?>
							<form name="f" method="post" action="" >
								<?php mo2f_configure_kba_questions(); ?>
								<br />
								<div class ="row">
									<div class="col-md-4" style="margin: 0 auto;width: 100px;">
										<?php
										if ( ! get_user_meta( $current_user_id, 'mo2f_user_profile_set', true ) ) {
											?>
										<button type="button" class="miniorange_button" style="width: 30%;background-color:#ff4168;" onclick="mobackinline();"><?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?></button>
<?php } ?>
										<input type="submit" name="validate" class="miniorange_button" style="width: 30%;background-color:#ff4168;" value="<?php esc_attr_e( 'Save', 'miniorange-2-factor-authentication' ); ?>" />
									</div>
								</div>
								<input type="hidden" name="option" value="mo2f_inline_kba_option" />
								<input type="hidden" name="mo2f_inline_save_kba_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-kba-nonce' ) ); ?>" />
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
								<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
							</form>
							<?php if ( count( $opt ) > 1 ) { ?>
									<form name="f" method="post" action="" id="mo2f_goto_two_factor_form" class="mo2f_display_none_forms">
										<div class ="row">
											<div class="col-md-4" style="margin: 0 auto;width: 100px;">
											<input type="hidden" name="option" value="miniorange_back_inline"/>
											</div>
										</div>
										<input type="hidden" name="miniorange_inline_two_factor_setup" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-setup-nonce' ) ); ?>" />
										<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
										<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
									</form>
							<?php } ?>

							<?php mo2f_customize_logo(); ?>
						</div>
					</div>
				</div>
			</div>
			<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" style="display:none;">
				<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>" />
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
			</form>
		<script>
			function mologinback(){
				jQuery('#mo2f_backto_mo_loginform').submit();
			}            

			function mobackinline(){
				jQuery('#mo2f_goto_two_factor_form').submit();
			}
		</script>
		</body>
	</html>
	<?php
}

/**
 * This function shows miniorange registration screen
 *
 * @param string $current_user_id user id of current user.
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $session_id session id.
 * @return void
 */
function prompt_user_for_miniorange_register( $current_user_id, $login_status, $login_message, $redirect_to, $session_id ) {
	$current_user = get_userdata( $current_user_id );
	$opt          = fetch_methods( $current_user );
	?>
	<html>
		<head>  <meta charset="utf-8"/>
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php
				mo2f_inline_css_and_js();
			?>
			<style>
				.mo2f_kba_ques, .mo2f_table_textbox{
					background: whitesmoke none repeat scroll 0% 0%;
				}
			</style>
		</head>
		<body>
			<div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
				<div class="mo2f-modal-backdrop"></div>
				<div class="mo2f_modal-dialog mo2f_modal-lg">
					<div class="login mo_customer_validation-modal-content">
						<div class="mo2f_modal-header">
							<h3 class="mo2f_modal-title" style="color:black;"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
							<b> <?php esc_html_e( 'Connect with miniOrange', 'miniorange-2-factor-authentication' ); ?></b></h3>
						</div>
						<div class="mo2f_modal-body">
							<?php if ( isset( $login_message ) && ! empty( $login_message ) ) { ?>
									<div  id="otpMessage">
										<p class="mo2fa_display_message_frontend" style="text-align: left !important;"  ><?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?></p>
									</div> 
								<?php } ?>
							<form name="mo2f_inline_register_form" id="mo2f_inline_register_form" method="post" action="">
								<input type="hidden" name="option" value="miniorange_inline_register" />
								<input type="hidden" name="mo2f_inline_register_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-inline-register-nonce' ) ); ?>"/>
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
								<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
								<p>This method requires you to have an account with miniOrange.</p>
								<table class="mo_wpns_settings_table">
									<tr>
									<td><b><span class="mo2fa_star_input-field">*</span>Email:</b></td>
									<td><input class="mo_wpns_table_textbox" type="email" name="email"
									required placeholder="person@example.com"/></td>
									</tr>
									<tr>
										<td><b><span class="mo2fa_star_input-field">*</span>Password:</b></td>
										<td><input class="mo_wpns_table_textbox" required type="password"
									name="password" placeholder="Choose your password (Min. length 6)" /></td>
									</tr>
									<tr>
										<td><b><span class="mo2fa_star_input-field">*</span>Confirm Password:</b></td>
										<td><input class="mo_wpns_table_textbox" required type="password"
									name="confirmPassword" placeholder="Confirm your password" /></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><br><input type="submit" name="submit" value="Create Account" 
									class="miniorange_button" />
									<a href="#mo2f_account_exist"><button class="button button-primary button-large miniorange_button">Already have an account?</button></a>
									</tr>
								</table>
							</form>
				<form name="f" id="mo2f_inline_login_form" method="post" action="" hidden>
					<p><b>It seems you already have an account with miniOrange. Please enter your miniOrange email and password.<br></b><a target="_blank" href="<?php echo esc_url( MO_HOST_NAME . '/moas/idp/resetpassword' ); ?>"> Click here if you forgot your password?</a></p>
					<input type="hidden" name="option" value="miniorange_inline_login"/>
					<input type="hidden" name="mo2f_inline_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-inline-login-nonce' ) ); ?>"/>
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
					<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
					<table class="mo_wpns_settings_table">
						<tr>
						<td><b><span class="mo2fa_star_input-field">*</span>Email:</b></td>
						<td><input class="mo_wpns_table_textbox" type="email" name="email"
						required placeholder="person@example.com"
						/></td>
						</tr>
						<tr>
						<td><b><span class="mo2fa_star_input-field">*</span>Password:</b></td>
						<td><input class="mo_wpns_table_textbox" required type="password"
						name="password" placeholder="Enter your miniOrange password" /></td>
						</tr>
						<tr>
						<td>&nbsp;</td>
						<td><input type="submit" class="miniorange_button" />
							<input type="button" id="cancel_link" class="miniorange_button" value="<?php esc_attr_e( 'Go Back to Registration', 'miniorange-2-factor-authentication' ); ?>" />
						</tr>
					</table>
				</form>
							<br>
					<input type="button" name="back" id="mo2f_inline_back_btn" class="miniorange_button" value="<?php esc_attr_e( '<< Back to Menu', 'miniorange-2-factor-authentication' ); ?>" />
							<?php mo2f_customize_logo(); ?>
						</div>
					</div>
				</div>
			</div>
			<form name="f" method="post" action="" id="mo2f_goto_two_factor_form" >              
				<input type="hidden" name="option" value="miniorange_back_inline"/>
				<input type="hidden" name="miniorange_inline_two_factor_setup" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-setup-nonce' ) ); ?>" />
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
			</form>
			<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" style="display:none;">
				<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>" />
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
			</form>
		<script>
			jQuery('#mo2f_inline_back_btn').click(function() {  
					jQuery('#mo2f_goto_two_factor_form').submit();
			});
			jQuery('a[href=\"#mo2f_account_exist\"]').click(function (e) {
					jQuery('#mo2f_inline_login_form').show();
					jQuery('#mo2f_inline_register_form').hide();
					jQuery('#otpMessage').hide();
			});
			jQuery('#cancel_link').click(function(){                               
					jQuery('#mo2f_inline_register_form').show();
					jQuery('#mo2f_inline_login_form').hide();
				});     
			function mologinback(){
				jQuery('#mo2f_backto_mo_loginform').submit();
			}
		</script>
		</body>
	</html>
	<?php
}

/**
 * This function shows setup success screen.
 *
 * @param string $id user id.
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $session_id session id.
 * @return void
 */
function prompt_user_for_setup_success( $id, $login_status, $login_message, $redirect_to, $session_id ) {
	global $mo2fdb_queries;
	?>
	<html>
		<head>  <meta charset="utf-8"/>
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php
				mo2f_inline_css_and_js();
			?>
			<style>
				.mo2f_kba_ques, .mo2f_table_textbox{
					background: whitesmoke none repeat scroll 0% 0%;
				}
			</style>
		</head>
		<body>
			<div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
				<div class="mo2f-modal-backdrop"></div>
				<div class="mo2f_modal-dialog mo2f_modal-lg">
					<div class="login mo_customer_validation-modal-content">
						<div class="mo2f_modal-header">
							<h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
							<?php esc_html_e( 'Two Factor Setup Complete', 'miniorange-2-factor-authentication' ); ?></h4>
						</div>
						<div class="mo2f_modal-body center">
							<?php
							global $mo2fdb_queries;
							$mo2f_second_factor = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $id );
							$status             = $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $id );

							if ( get_site_option( 'mo2f_disable_kba' ) !== 1 ) {
								if ( 'MO_2_FACTOR_PLUGIN_SETTINGS' !== $status ) {
									?>
							<div id="validation_msg" style="color:red;text-align:left !important;"></div>
								<div id="mo2f_show_kba_reg" class="mo2f_inline_padding" style="text-align:left !important;" >
									<?php if ( isset( $login_message ) && ! empty( $login_message ) ) { ?>
									<div  id="otpMessage">
										<p class="mo2fa_display_message_frontend" style="text-align: left !important;"  ><?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?></p>
									</div> 
								<?php } ?>
								<h4> <?php esc_html_e( 'Please set your security questions as an alternate login or backup method.', 'miniorange-2-factor-authentication' ); ?></h4>
								<form name="f" method="post" action="" >
									<?php mo2f_configure_kba_questions(); ?>
									<br>
									<div class="mo2fa_text-align-center">
										<input type="submit" name="validate" class="miniorange_button" value="<?php esc_attr_e( 'Save', 'miniorange-2-factor-authentication' ); ?>" /> 
									</div>
									<input type="hidden" name="mo2f_inline_kba_option" />
									<input type="hidden" name="mo2f_inline_save_kba_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-kba-nonce' ) ); ?>" />
									<input type="hidden" name="mo2f_inline_kba_status" value="<?php echo esc_attr( $login_status ); ?>" />
									<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
									<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
								</form>
								</div>
									<?php
								}
							} else {
								$mo2fa_login_status = 'MO_2_FACTOR_SETUP_SUCCESS';
								$mo2fdb_queries->update_user_details( $id, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS' ) );
								$status = 'MO_2_FACTOR_PLUGIN_SETTINGS';
							}
							if ( 'MO_2_FACTOR_PLUGIN_SETTINGS' === $status ) {
									$pass2fa = new Miniorange_Password_2Factor_Login();
									$pass2fa->mo2fa_pass2login( site_url(), $session_id );
								?>
								<div class="mo2fa_text-align-center">
								<p style="font-size:17px;"><?php esc_html_e( 'You have successfully set up ', 'miniorange-2-factor-authentication' ); ?><b style="color:#28B463;"><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( $mo2f_second_factor, 'cap_to_small' ) ); ?> </b><?php esc_html_e( 'as your Two Factor method.', 'miniorange-2-factor-authentication' ); ?><br><br>
									<?php esc_html_e( 'From now, when you login, you will be prompted for', 'miniorange-2-factor-authentication' ); ?>  <span style="color:#28B463;"><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( $mo2f_second_factor, 'cap_to_small' ) ); ?></span>  <?php esc_html_e( 'as your 2nd factor method of authentication.', 'miniorange-2-factor-authentication' ); ?>
								</p>
								</div>
								<br>
								<div class="mo2fa_text-align-center">
								<p style="font-size:16px;"><a href="#" onclick="mologinback();"style="color:#CB4335;"><b><?php esc_html_e( 'Click Here', 'miniorange-2-factor-authentication' ); ?></b></a> <?php esc_html_e( 'to sign-in to your account.', 'miniorange-2-factor-authentication' ); ?>
								<br>
								</div>
									<?php
							}
							mo2f_customize_logo()
							?>
						</div>
					</div>
				</div>
			</div>
			<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" style="display:none;">
				<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>" />
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
			</form>

		<script>
			function mologinback(){
				jQuery('#mo2f_backto_mo_loginform').submit();
			}
		</script>
		</body>
	</html>
	<?php
}
