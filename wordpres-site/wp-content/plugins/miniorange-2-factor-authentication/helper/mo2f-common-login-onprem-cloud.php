<?php
/**
 * This file contains functions related to login flow.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Onprem\MO2f_Cloud_Onprem_Interface;
use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prompts mfa form for users.
 *
 * @param array  $configure_array_method array of methods.
 * @param string $session_id_encrypt encrypted session id.
 * @param string $redirect_to redirect to url.
 * @return void
 */
function mo2fa_prompt_mfa_form_for_user( $configure_array_method, $session_id_encrypt, $redirect_to ) {
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

								<?php esc_html_e( 'Select 2 Factor method for authentication', 'miniorange-2-factor-authentication' ); ?></h3>
							</div>
							<div class="mo2f_modal-body">
									<?php
									foreach ( $configure_array_method as $key => $value ) {
										echo '<span  >
                                    		<label>
                                    			<input type="radio"  name="mo2f_selected_mfactor_method" class ="mo2f-styled-radio_conf" value="' . esc_html( $value ) . '"/>';
												echo '<span class="mo2f-styled-radio-text_conf">';
												echo esc_html( MoWpnsConstants::mo2f_convert_method_name( $value, 'cap_to_small' ) );
											echo ' </span> </label>
                                			<br>
                                			<br>
                                		</span>';

									}
									mo2f_customize_logo();
									?>
							</div>
						</div>
					</div>
				</div>
				<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" style="display:none;">
					<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
					<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
				</form>
				<form name="f" method="post" action="" id="mo2f_select_mfa_methods_form" style="display:none;">
					<input type="hidden" name="mo2f_selected_mfactor_method" />
					<input type="hidden" name="mo2f_miniorange_2factor_method_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f_miniorange-2factor-method-nonce' ) ); ?>" />
					<input type="hidden" name="option" value="miniorange_mfactor_method" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
					<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
				</form>
			<script>
				function mologinback(){
					jQuery('#mo2f_backto_mo_loginform').submit();
				}
				jQuery('input:radio[name=mo2f_selected_mfactor_method]').click(function() {
					var selectedMethod = jQuery(this).val();
					document.getElementById("mo2f_select_mfa_methods_form").elements[0].value = selectedMethod;
					jQuery('#mo2f_select_mfa_methods_form').submit();
				});				
			</script>
			</body>
		</html>
		<?php
}

/**
 * This function redirect user to given url.
 *
 * @param object $user object containing user details.
 * @param string $redirect_to redirect url.
 * @return void
 */
function redirect_user_to( $user, $redirect_to ) {
	$roles        = $user->roles;
	$current_role = array_shift( $roles );

	if ( is_multisite() ) {
		$blog_id = get_current_blog_id();
		if ( is_super_admin( $user->ID ) ) {

			$redirect_url = isset( $redirect_to ) && ! empty( $redirect_to ) ? $redirect_to : admin_url();

		} elseif ( 'administrator' === $current_role ) {

			$redirect_url = empty( $redirect_to ) ? admin_url() : $redirect_to;

		} else {

			$redirect_url = empty( $redirect_to ) ? home_url() : $redirect_to;
		}
	} else {
		if ( 'administrator' === $current_role ) {
			$redirect_url = empty( $redirect_to ) ? admin_url() : $redirect_to;
		} else {
			$redirect_url = empty( $redirect_to ) ? home_url() : $redirect_to;
		}
	}

	if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
		$redirect = array(
			'redirect' => $redirect_url,
		);

			wp_send_json_success( $redirect );
	} else {

		wp_safe_redirect( $redirect_url );
		exit();
	}
}

/**
 * Function checks if 2fa enabled for given user roles (used in shortcode addon)
 *
 * @param array $current_roles array containing roles of user.
 * @return boolean
 */
function miniorange_check_if_2fa_enabled_for_roles( $current_roles ) {
	if ( empty( $current_roles ) ) {
		return 0;
	}

	foreach ( $current_roles as $value ) {
		if ( get_option( 'mo2fa_' . $value ) ) {
			return 1;
		}
	}

	return 0;
}

/**
 * This function prompts forgot phone form.
 *
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $session_id_encrypt encrypted session id.
 * @return void
 */
function mo2f_get_forgotphone_form( $login_status, $login_message, $redirect_to, $session_id_encrypt ) {
	$mo2f_forgotphone_enabled     = MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_forgotphone', 'get_option' );
	$mo2f_email_as_backup_enabled = get_option( 'mo2f_enable_forgotphone_email' );
	$mo2f_kba_as_backup_enabled   = get_option( 'mo2f_enable_forgotphone_kba' );
	?>
	<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php
		echo_js_css_files();
		?>
	</head>
	<body>
	<div class="mo2f_modal" tabindex="-1" role="dialog">
		<div class="mo2f-modal-backdrop"></div>
		<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
			<div class="login mo_customer_validation-modal-content">
				<div class="mo2f_modal-header">
					<h4 class="mo2f_modal-title">
						<button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close"
								title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>"
								onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
						<?php esc_html_e( 'How would you like to authenticate yourself?', 'miniorange-2-factor-authentication' ); ?>
					</h4>
				</div>
				<div class="mo2f_modal-body">
					<?php
					if ( $mo2f_forgotphone_enabled ) {
						if ( isset( $login_message ) && ! empty( $login_message ) ) {
							?>
							<div id="otpMessage" class="mo2fa_display_message_frontend">
								<p class="mo2fa_display_message_frontend"><?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?></p>
							</div>
						<?php } ?>
						<p class="mo2f_backup_options"><?php esc_html_e( 'Please choose the options from below:', 'miniorange-2-factor-authentication' ); ?></p>
						<div class="mo2f_backup_options_div">
							<?php if ( $mo2f_email_as_backup_enabled ) { ?>
								<input type="radio" name="mo2f_selected_forgotphone_option"
									value="One Time Passcode over Email"
									checked="checked"/><?php esc_html_e( 'Send a one time passcode to my registered email', 'miniorange-2-factor-authentication' ); ?>
								<br><br>
								<?php
							}
							if ( $mo2f_kba_as_backup_enabled ) {
								?>
								<input type="radio" name="mo2f_selected_forgotphone_option"
									value="'<?php echo esc_js( MoWpnsConstants::SECURITY_QUESTIONS ); ?>'"/><?php esc_html_e( 'Answer your Security Questions (KBA)', 'miniorange-2-factor-authentication' ); ?>
							<?php } ?>
							<br><br>
							<input type="button" name="miniorange_validate_otp" value="<?php esc_attr_e( 'Continue', 'miniorange-2-factor-authentication' ); ?>" class="miniorange_validate_otp"
								onclick="mo2fselectforgotphoneoption();"/>
						</div>
						<?php
						mo2f_customize_logo();
					}
					?>
				</div>
			</div>
		</div>
	</div>
	<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>"
		class="mo2f_display_none_forms">
		<input type="hidden" name="miniorange_mobile_validation_failed_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>"/>
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
	</form>
	<form name="f" id="mo2f_challenge_forgotphone_form" method="post" class="mo2f_display_none_forms">
		<input type="hidden" name="mo2f_configured_2FA_method"/>
		<input type="hidden" name="miniorange_challenge_forgotphone_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-challenge-forgotphone-nonce' ) ); ?>"/>
		<input type="hidden" name="option" value="miniorange_challenge_forgotphone">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
	</form>

	<script>
		function mologinback() {
			jQuery('#mo2f_backto_mo_loginform').submit();
		}

		function mo2fselectforgotphoneoption() {
			var option = jQuery('input[name=mo2f_selected_forgotphone_option]:checked').val();
			document.getElementById("mo2f_challenge_forgotphone_form").elements[0].value = option;
			jQuery('#mo2f_challenge_forgotphone_form').submit();
		}
	</script>
	</body>
	</html>
	<?php
}

/**
 * This Function prompts user`s backup method
 *
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $session_id_encrypt encrypted session id.
 * @return void
 */
function mo2f_backup_form( $login_status, $login_message, $redirect_to, $session_id_encrypt ) {
	?>
<html>
	<head>  
		<meta charset="utf-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php
			echo_js_css_files();
		?>
	</head>
	<body>
		<div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
		<div class="mo2f-modal-backdrop"></div>
		<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
			<div class="login mo_customer_validation-modal-content">
				<div class="mo2f_modal-header">
					<h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
						<?php esc_html_e( 'Validate Backup Code', 'miniorange-2-factor-authentication' ); ?>
					</h4>
				</div>
				<div class="mo2f_modal-body">
					<div id="kbaSection" style="padding-left:10px;padding-right:10px;">
					<div  id="otpMessage" >
						<p style="font-size:15px;">
						<?php
						if ( isset( $login_message ) && ! empty( $login_message ) ) {
							echo wp_kses(
								$login_message,
								array(
									'a' => array(
										'href'   => array(),
										'target' => array(),
									),
								)
							);
						} else {
							echo esc_html__( 'Please answer the following questions:', 'miniorange-2-factor-authentication' );
						}
						?>
						</p>
					</div>
					<form name="f" id="mo2f_submitbackup_loginform" method="post" action="">
						<div id="mo2f_kba_content">
							<p style="font-size:15px;">
								<input class="mo2f-textbox" type="text" name="mo2f_backup_code" id="mo2f_backup_code" required="true" autofocus="true"  title="<?php esc_attr_e( 'Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed.', 'miniorange-2-factor-authentication' ); ?>" autocomplete="off" ><br />
							</p>
						</div>
						<input type="submit" name="miniorange_backup_validate" id="miniorange_backup_validate" class="miniorange_otp_token_submit"  style="float:left;" value="<?php esc_attr_e( 'Validate', 'miniorange-2-factor-authentication' ); ?>" />
						<input type="hidden" name="miniorange_validate_backup_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-validate-backup-nonce' ) ); ?>" />
						<input type="hidden" name="option" value="miniorange_validate_backup_nonce">
						<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>" />
						<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>" />
					</form>
					</br>
					</div>
					<br /><br /><br />
					<?php mo2f_customize_logo(); ?>
				</div>
			</div>
		</div>
	</div>
	<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" style="display:none;">
		<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>" />
	</form>
</body>
<script>
	function mologinback(){
		jQuery('#mo2f_backto_mo_loginform').submit();
	}
</script>
</html>
	<?php
}

/**
 * This function prompts duo authentication
 *
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $session_id_encrypt encrypted session id.
 * @param string $user_id user id.
 * @return void
 */
function mo2f_get_duo_push_authentication_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $user_id ) {

	$mo_wpns_config = new MO2f_Cloud_Onprem_Interface();

	global $mo2fdb_queries,$txid,$mo_wpns_utility;
	$mo2f_enable_forgotphone = MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_forgotphone', 'get_option' );
	$mo2f_kba_config_status  = $mo2fdb_queries->get_user_detail( 'mo2f_SecurityQuestions_config_status', $user_id );
	$mo2f_ev_txid            = get_user_meta( $user_id, 'mo2f_transactionId', true );
	$user_id                 = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

	$current_user = get_user_by( 'id', $user_id );
	MO2f_Utility::mo2f_debug_file( 'Waiting for duo push notification validation User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $current_user->ID . ' Email-' . $current_user->user_email );
	update_user_meta( $user_id, 'current_user_email', $current_user->user_email );

	?>

	<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php
			echo_js_css_files();
		?>
	</head>
	<body>
	<div class="mo2f_modal" tabindex="-1" role="dialog">
		<div class="mo2f-modal-backdrop"></div>
		<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
			<div class="login mo_customer_validation-modal-content">
				<div class="mo2f_modal-header">
					<h4 class="mo2f_modal-title">
						<button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close"
								title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>"
								onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
						<?php esc_html_e( 'Accept Your Transaction', 'miniorange-2-factor-authentication' ); ?></h4>
				</div>
				<div class="mo2f_modal-body">
					<?php if ( isset( $login_message ) && ! empty( $login_message ) ) { ?>
						<div id="otpMessage">
							<p class="mo2fa_display_message_frontend"><?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?></p>
						</div>
					<?php } ?>
					<div id="pushSection">

						<div>
							<div class="mo2fa_text-align-center">
								<p class="mo2f_push_oob_message"><?php esc_html_e( 'Waiting for your approval...', 'miniorange-2-factor-authentication' ); ?></p>
					</div>
						</div>
						<div id="showPushImage">
							<div class="mo2fa_text-align-center">
								<img src="<?php echo esc_url( plugins_url( 'includes/images/ajax-loader-login.gif', dirname( dirname( __FILE__ ) ) ) ); ?>"/>
					</div>
						</div>


						<span style="padding-right:2%;">
							<?php if ( isset( $login_status ) && 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' === $login_status ) { ?>
								<div class="mo2fa_text-align-center">
									&emsp;&emsp;
									</div>
							<?php } elseif ( isset( $login_status ) && 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL' === $login_status && $mo2f_enable_forgotphone && $mo2f_kba_config_status ) { ?>
								<div class="mo2fa_text-align-center">
								<a href="#mo2f_alternate_login_kba">
									<p class="mo2f_push_oob_backup"><?php esc_html_e( 'Didn\'t receive push nitification?', 'miniorange-2-factor-authentication' ); ?></p>
								</a>
							</div>
							<?php } ?>
						</span>
						<div class="mo2fa_text-align-center">
							<?php
							if ( empty( get_user_meta( $user_id, 'mo_backup_code_generated', true ) ) ) {
								?>
									<div>
										<a href="#mo2f_backup_generate">
											<p style="font-size:14px; font-weight:bold;"><?php esc_html_e( 'Send backup codes on email', 'miniorange-2-factor-authentication' ); ?></p>
										</a>
									</div>
							<?php } else { ?>
									<div>
										<a href="#mo2f_backup_option">
											<p style="font-size:14px; font-weight:bold;"><?php esc_html_e( 'Use Backup Codes', 'miniorange-2-factor-authentication' ); ?></p>
										</a>
									</div>
								<?php
							}
							?>
							<div style="padding:10px;">
								<p><a href="<?php echo esc_url( $mo_wpns_config->locked_out_link() ); ?>" target="_blank" style="color:#ca2963;font-weight:bold;">I'm locked out & unable to login.</a></p>
							</div>
						</div>
					</div>

					<?php
						mo2f_customize_logo();
						mo2f_create_backup_form( $redirect_to, $session_id_encrypt, $login_status, $login_message );
					?>
				</div>
			</div>
		</div>
	</div>
	<form name="f" id="mo2f_backto_duo_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>"
			class="mo2f_display_none_forms">
		<input type="hidden" name="miniorange_duo_push_validation_failed_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-duo-push-validation-failed-nonce' ) ); ?>"/>
		<input type="hidden" name="option" value="miniorange_duo_push_validation_failed">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
		<input type="hidden" name="currentMethod" value="emailVer"/>
	</form>
	<form name="f" id="mo2f_duo_push_validation_form" method="post" class="mo2f_display_none_forms">
		<input type="hidden" name="miniorange_duo_push_validation_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-duo-validation-nonce' ) ); ?>"/>
		<input type="hidden" name="option" value="miniorange_duo_push_validation">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="tx_type"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
		<input type="hidden" name="TxidEmail" value="<?php echo esc_attr( $mo2f_ev_txid ); ?>"/>
	</form>
	<form name="f" id="mo2f_show_forgotphone_loginform" method="post" class="mo2f_display_none_forms">
		<input type="hidden" name="request_origin_method" value="<?php echo esc_attr( $login_status ); ?>"/>
		<input type="hidden" name="miniorange_forgotphone" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-forgotphone' ) ); ?>"/>
		<input type="hidden" name="option" value="miniorange_forgotphone">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
	</form>
	<form name="f" id="mo2f_alternate_login_kbaform" method="post" class="mo2f_display_none_forms">
		<input type="hidden" name="miniorange_alternate_login_kba_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-alternate-login-kba-nonce' ) ); ?>"/>
		<input type="hidden" name="option" value="miniorange_alternate_login_kba">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
	</form>

	<script>
		var timeout;

			pollPushValidation();
			function pollPushValidation()
			{   
				var ajax_url = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"; 
				var nonce = "<?php echo esc_js( wp_create_nonce( 'miniorange-2-factor-duo-nonce' ) ); ?>";
				var session_id_encrypt = "<?php echo esc_js( $session_id_encrypt ); ?>";
				var data={
					'action':'mo2f_duo_ajax_request',
					'call_type':'check_duo_push_auth_status',
					'session_id_encrypt': session_id_encrypt,
					'nonce' : nonce,
				}; 

				jQuery.post(ajax_url, data, function(response){


							if (response.success) {
								jQuery('#mo2f_duo_push_validation_form').submit();
							} else if (response.data == 'ERROR' || response.data == 'FAILED' || response.data == 'DENIED' || response.data ==0) {
								jQuery('#mo2f_backto_duo_mo_loginform').submit();
							} else {
								timeout = setTimeout(pollMobileValidation, 3000);
							}
				});
		}

		function mologinforgotphone() {
			jQuery('#mo2f_show_forgotphone_loginform').submit();
		}

		function mologinback() {
			jQuery('#mo2f_backto_duo_mo_loginform').submit();
		}

		jQuery('a[href="#mo2f_alternate_login_kba"]').click(function () {
			jQuery('#mo2f_alternate_login_kbaform').submit();
		});
		jQuery('a[href="#mo2f_backup_option"]').click(function() {
			jQuery('#mo2f_backup').submit();
		});
		jQuery('a[href="#mo2f_backup_generate"]').click(function() {
			jQuery('#mo2f_create_backup_codes').submit();
		});

	</script>
	</body>
	</html>

	<?php
}

/**
 * Gets skeleton values according to the 2fa method.
 *
 * @param string $login_message Login message.
 * @param string $login_status Login status.
 * @param array  $kba_question1 KBA question 1.
 * @param array  $kba_question2 KBA question 2.
 * @param int    $user_id User Id.
 * @return array
 */
function mo2f_twofa_login_prompt_skeleton_values( $login_message, $login_status, $kba_question1, $kba_question2, $user_id ) {
	global $mo2fdb_queries;
	if ( ! get_user_meta( $user_id, 'mo2f_attempts_before_redirect', true ) ) {
		update_user_meta( $user_id, 'mo2f_attempts_before_redirect', 3 );
	}
	$attempts        = get_user_meta( $user_id, 'mo2f_attempts_before_redirect', true );
	$skeleton_blocks = array(
		'login_prompt_title'   => 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION' === $login_status ? __( 'Validate Security Questions', 'miniorange-2-factor-authentication' ) : __( 'Validate OTP', 'miniorange-2-factor-authentication' ),
		'login_prompt_message' => $login_message . '<br>',
		'attempt_left'         => '1' !== $attempts ? '<br><span><b>Attempts left</b>:</span>' . esc_html( $attempts ) . '<br><br>' : '<br><span><b>Attempts left</b>:</span>' . esc_html( $attempts ) . '<br> <span style="color:red;"><b>If you fail to verify your identity, you will be redirected back to login page to verify your credentials.</b></span><br><br>',
		'enter_otp'            => '<div class="mo2fa_text-align-center">
                                        <input type="text" name="mo2fa_softtoken" style="height:28px !important;"
                                        placeholder="' . esc_attr__( 'Enter code', 'miniorange-2-factor-authentication' ) . '"
                                        id="mo2fa_softtoken" required="true" class="mo_otp_token" autofocus="true"
                                        pattern="[0-9]{4,8}"
                                        title="' . esc_attr__( 'Only digits within range 4-8 are allowed.', 'miniorange-2-factor-authentication' ) . '"/>
                                    </div><br>',
		'enter_answers'        => '<p style="font-size:15px;"> ' .
											esc_html( $kba_question1 ) . '
                                            <br>
                                            <input class="mo2f-textbox" type="password" name="mo2f_answer_1" id="mo2f_answer_1"
                                                required="true" autofocus="true"
                                                pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+\-\s]{1,100}"
                                                title="Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed."
                                                autocomplete="off"><br> ' . esc_html( $kba_question2 ) . '<br>
                                            <input class="mo2f-textbox" type="password" name="mo2f_answer_2" id="mo2f_answer_2"
                                                required="true" pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+\-\s]{1,100}"
                                                title="Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed."
                                                autocomplete="off">
                                    </p>',
		'resend_otp'           => '<span style="color:#1F618D;">' . esc_html__( 'Didn\'t get code?', 'miniorange-2-factor-authentication' ) . '&nbsp;&nbsp;&nbsp;</span><span><a href="#resend" style="color:#F4D03F ;font-weight:bold;"><u>' . esc_html__( 'RESEND IT', 'miniorange-2-factor-authentication' ) . '</u></a></span>&nbsp;<br><br>',
		'back_button'          => ' <input type="button" name="miniorange_otp_token_back" id="miniorange_otp_token_back" class="miniorange_otp_token_submit" value="' . esc_attr__( 'Back', 'miniorange-2-factor-authentication' ) . '"/>',
		'use_backup_codes'     => '<div> <a href="#mo2f_backup_option">
                                     <p style="font-size:14px; font-weight:bold;">' . esc_html__( 'Use Backup Codes', 'miniorange-2-factor-authentication' ) . '</p>
                                     </a>
                                    </div>',
		'send_backup_codes'    => '<div> <a href="#mo2f_backup_generate">
                                         <p style="font-size:14px; font-weight:bold;">' . esc_html__( 'Send backup codes on email', 'miniorange-2-factor-authentication' ) . '</p>
                                         </a>
                                    </div>',

		'custom_logo'          => '	<div style="float:right;"><a target="_blank" href="http://miniorange.com/2-factor-authentication"><img
                                     alt="logo"  src="' . esc_url( plugins_url( 'includes/images/miniOrange2.png', dirname( __FILE__ ) ) ) . '"/></a></div>',

	);
	$login_status_blocks = array();
	if ( empty( get_user_meta( $user_id, 'mo_backup_code_generated', true ) ) ) {
		$skeleton_blocks['use_backup_codes'] = '';
	} else {
		$skeleton_blocks['send_backup_codes'] = '';
	}
	$configured_methods = new Miniorange_Password_2Factor_Login();
	if ( ! MoWpnsUtility::get_mo2f_db_option( 'mo2f_nonce_enable_configured_methods', 'site_option' ) || count( $configured_methods->mo2fa_return_methods_value( $user_id ) ) < 2 && 'MO_2_FACTOR_PLUGIN_SETTINGS' === $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $user_id ) ) {
		$skeleton_blocks['back_button'] = '';
	}
	$login_status_blocks = array(
		'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL'        => array(
			'##mo2f_title##'      => $skeleton_blocks['login_prompt_title'],
			'##login_message##'   => $skeleton_blocks['login_prompt_message'],
			'##attemptleft##'     => $skeleton_blocks['attempt_left'],
			'##enterotp##'        => $skeleton_blocks['enter_otp'],
			'##enteranswers##'    => '',
			'##resendotp##'       => $skeleton_blocks['resend_otp'],
			'##backbutton##'      => $skeleton_blocks['back_button'],
			'##usebackupcodes##'  => $skeleton_blocks['use_backup_codes'],
			'##sendbackupcodes##' => $skeleton_blocks['send_backup_codes'],
			'##customlogo##'      => $skeleton_blocks['custom_logo'],
		),
		'MO_2_FACTOR_CHALLENGE_OTP_OVER_TELEGRAM'     => array(
			'##mo2f_title##'      => $skeleton_blocks['login_prompt_title'],
			'##login_message##'   => $skeleton_blocks['login_prompt_message'],
			'##attemptleft##'     => $skeleton_blocks['attempt_left'],
			'##enterotp##'        => $skeleton_blocks['enter_otp'],
			'##enteranswers##'    => '',
			'##resendotp##'       => $skeleton_blocks['resend_otp'],
			'##backbutton##'      => $skeleton_blocks['back_button'],
			'##usebackupcodes##'  => $skeleton_blocks['use_backup_codes'],
			'##sendbackupcodes##' => $skeleton_blocks['send_backup_codes'],
			'##customlogo##'      => $skeleton_blocks['custom_logo'],
		),
		'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION' => array(
			'##mo2f_title##'      => $skeleton_blocks['login_prompt_title'],
			'##login_message##'   => $skeleton_blocks['login_prompt_message'],
			'##attemptleft##'     => $skeleton_blocks['attempt_left'],
			'##enterotp##'        => $skeleton_blocks['enter_otp'],
			'##enteranswers##'    => '',
			'##resendotp##'       => '',
			'##backbutton##'      => $skeleton_blocks['back_button'],
			'##usebackupcodes##'  => $skeleton_blocks['use_backup_codes'],
			'##sendbackupcodes##' => $skeleton_blocks['send_backup_codes'],
			'##customlogo##'      => $skeleton_blocks['custom_logo'],
		),
		'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN'            => array(
			'##mo2f_title##'      => $skeleton_blocks['login_prompt_title'],
			'##login_message##'   => $skeleton_blocks['login_prompt_message'],
			'##attemptleft##'     => $skeleton_blocks['attempt_left'],
			'##enterotp##'        => $skeleton_blocks['enter_otp'],
			'##enteranswers##'    => '',
			'##resendotp##'       => '',
			'##backbutton##'      => $skeleton_blocks['back_button'],
			'##usebackupcodes##'  => $skeleton_blocks['use_backup_codes'],
			'##sendbackupcodes##' => $skeleton_blocks['send_backup_codes'],
			'##customlogo##'      => $skeleton_blocks['custom_logo'],
		),
		'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS'          => array(
			'##mo2f_title##'      => $skeleton_blocks['login_prompt_title'],
			'##login_message##'   => $skeleton_blocks['login_prompt_message'],
			'##attemptleft##'     => $skeleton_blocks['attempt_left'],
			'##enterotp##'        => $skeleton_blocks['enter_otp'],
			'##enteranswers##'    => '',
			'##resendotp##'       => $skeleton_blocks['resend_otp'],
			'##backbutton##'      => $skeleton_blocks['back_button'],
			'##usebackupcodes##'  => $skeleton_blocks['use_backup_codes'],
			'##sendbackupcodes##' => $skeleton_blocks['send_backup_codes'],
			'##customlogo##'      => $skeleton_blocks['custom_logo'],
		),
		'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION'    => array(
			'##mo2f_title##'      => $skeleton_blocks['login_prompt_title'],
			'##login_message##'   => $skeleton_blocks['login_prompt_message'],
			'##attemptleft##'     => '',
			'##enterotp##'        => '',
			'##enteranswers##'    => $skeleton_blocks['enter_answers'],
			'##resendotp##'       => '',
			'##backbutton##'      => $skeleton_blocks['back_button'],
			'##usebackupcodes##'  => $skeleton_blocks['use_backup_codes'],
			'##sendbackupcodes##' => $skeleton_blocks['send_backup_codes'],
			'##customlogo##'      => $skeleton_blocks['custom_logo'],
		),
	);
	return $login_status_blocks[ $login_status ];
}

/**
 * Shows two factor authentication login prompt.
 *
 * @param string $login_status Login status.
 * @param string $login_message Login message.
 * @param string $redirect_to Redirection url.
 * @param string $session_id_encrypt Session Id.
 * @param array  $skeleton_values Skeleton values.
 * @return void
 */
function mo2f_twofa_authentication_login_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values ) {
	$mo_wpns_config = new MoWpnsHandler();
	echo '
<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	';
	echo_js_css_files();
	echo '

	</head>
	<?php
	
	<body>

		<div class="mo2f_modal" tabindex="-1" role="dialog">
			<div class="mo2f-modal-backdrop"></div>
			<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
				<div class="login mo_customer_validation-modal-content">
					
					<div class="mo2f_modal-header center">
							<h4 class="mo2f_modal-title">
								<button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close"
										title="' . esc_attr__( 'Back to login', 'miniorange-2-factor-authentication' ) . '"
										onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
								';
							echo esc_html( $skeleton_values['##mo2f_title##'] );
							echo '
							</h4>

					</div>
					<div class="mo2f_modal-body center">
	
						<div id="otpMessage">
							<p class="mo2fa_display_message_frontend">';
						echo wp_kses(
							$skeleton_values['##login_message##'],
							array(
								'b'  => array(),
								'br' => array(),
								'a'  => array(
									'href'   => array(),
									'target' => array(),
								),
							)
						);
							echo '
							
							</p>
						</div>';
						echo wp_kses(
							$skeleton_values['##attemptleft##'],
							array(
								'b'    => array(),
								'br'   => array(),
								'span' => array(
									'style' => array(),
								),
							)
						);
						echo '
						 <div id="showOTP">
								<div class="mo2f-login-container">
									<form name="f" id="mo2f_submitotp_loginform" method="post"> ';
									echo wp_kses(
										$skeleton_values['##enterotp##'],
										array(
											'div'   => array(
												'class' => array(),
											),
											'input' => array(
												'type'     => array(),
												'name'     => array(),
												'style'    => array(),
												'placeholder' => array(),
												'id'       => array(),
												'required' => array(),
												'class'    => array(),
												'autofocus' => array(),
												'pattern'  => array(),
												'title'    => array(),

											),
											'br'    => array(),

										)
									);
									echo wp_kses(
										$skeleton_values['##enteranswers##'],
										array(
											'p'     => array(
												'style' => array(),
											),
											'br'    => array(),
											'input' => array(
												'type'     => array(),
												'name'     => array(),
												'style'    => array(),
												'placeholder' => array(),
												'id'       => array(),
												'required' => array(),
												'class'    => array(),
												'autofocus' => array(),
												'pattern'  => array(),
												'title'    => array(),
												'autocomplete' => array(),

											),

										)
									);
									echo wp_kses(
										$skeleton_values['##resendotp##'],
										array(
											'span' => array(
												'style' => array(),
											),
											'br'   => array(),
											'a'    => array(
												'href'  => array(),
												'style' => array(),

											),
											'u'    => array(),

										)
									);
									echo wp_kses(
										$skeleton_values['##backbutton##'],
										array(
											'br'    => array(),
											'input' => array(
												'type'  => array(),
												'name'  => array(),
												'value' => array(),
												'id'    => array(),
												'class' => array(),
											),

										)
									);
									echo '
										<input type="submit" name="miniorange_otp_token_submit" id="miniorange_otp_token_submit" class="miniorange_otp_token_submit" value="' . esc_attr__( 'Validate', 'miniorange-2-factor-authentication' ) . '"/>';

									echo '
									<input type="hidden" name="request_origin_method" value="' . esc_attr( $login_status ) . '"/>
									<input type="hidden" name="mo2f_authenticate_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-soft-token-nonce' ) ) . '"/>
                                    <input type="hidden" name="option" value="mo2f_validate_user_for_login">
									<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
									<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
									</form>';
									echo wp_kses(
										$skeleton_values['##sendbackupcodes##'],
										array(

											'a' => array(
												'href' => array(),

											),
											'p' => array(
												'style' => array(),

											),

										)
									);
									echo wp_kses(
										$skeleton_values['##usebackupcodes##'],
										array(

											'a' => array(
												'href' => array(),

											),
											'p' => array(
												'style' => array(),

											),

										)
									);

									echo '
										<div style="padding:10px;">
											<p><a href="' . esc_url( $mo_wpns_config->locked_out_link() ) . '" target="_blank" style="color:#ca2963;font-weight:bold;">' . esc_html__( 'I\'m locked out & unable to login.', 'miniorange-2-factor-authentication' ) . ' </a></p>
                                            
										</div>
                                      

								</div>
                                
						 </div> ';
						echo wp_kses(
							$skeleton_values['##customlogo##'],
							array(

								'div' => array(
									'style' => array(),

								),
								'a'   => array(
									'target' => array(),
									'href'   => array(),

								),
								'img' => array(
									'alt' => array(),
									'src' => array(),

								),

							)
						);

						echo '
                    <form name="f" id="mo2f_backup" method="post" action="" style="display:none;">
                        <input type="hidden" name="miniorange_backup_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-backup-nonce' ) ) . '" />
                        <input type="hidden" name="option" value="miniorange_backup_nonce">
                        <input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '" />
                        <input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '" />
                    </form>
                        ';
						mo2f_create_backup_form( $redirect_to, $session_id_encrypt, $login_status, $login_message );
						echo '
                    </div>


				</div>
               
			</div>
		</div>
		<form name="f" id="mo2f_backto_mo_loginform" method="post" action="' . esc_url( wp_login_url() ) . '" class="mo2f_display_none_forms">
			<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ) . '"/>
			<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
		</form>
		<form name="f" id="mo2f_resend_otp" method="post" action="" style="display:none;">
			<input type="hidden" name="mo2f_resend_otp_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f_resend_otp_nonce' ) ) . '" />
			<input type="hidden" name="option" value="mo2f_resend_otp_nonce">
			<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
			<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
		</form>
		<form name="f" id="mo2f_backto_inline_registration" method="post" action="' . esc_url( wp_login_url() ) . '" class="mo2f_display_none_forms">
			<input type="hidden" name="miniorange_back_inline_reg_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-back-inline-reg-nonce' ) ) . '"/>
			<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
			<input type="hidden" name="option" value="miniorange2f_back_to_inline_registration"> 
			<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
		</form>
	   
	</body>
</html>';
	?>
		<script>
			jQuery("a[href='#mo2f_backup_option']").click(function() {
				jQuery("#mo2f_backup").submit();
			});
			jQuery("a[href='#mo2f_backup_generate']").click(function() {
				jQuery("#mo2f_create_backup_codes").submit();
			});
			jQuery("a[href='#resend']").click(function() {
				jQuery("#mo2f_resend_otp").submit();
			});
			jQuery("#miniorange_otp_token_back").click(function(){
				jQuery("#mo2f_backto_inline_registration").submit();
			});
			function mologinback() {
			jQuery("#mo2f_backto_mo_loginform").submit();
			}
		</script>
	<?php
}

/**
 * This function prints customized logo.
 *
 * @return void
 */
function mo2f_customize_logo() {
	?>
	<div style="float:right;"><img
					alt="logo"
					src="<?php echo esc_url( plugins_url( 'includes/images/miniOrange2.png', dirname( __FILE__ ) ) ); ?>"/></div>

		<?php
}

/**
 * This function used to include css and js files.
 *
 * @return void
 */
function echo_js_css_files() {

	wp_register_style( 'mo2f_style_settings', plugins_url( 'includes/css/twofa_style_settings.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
	wp_print_styles( 'mo2f_style_settings' );

	wp_register_script( 'mo2f_bootstrap_js', plugins_url( 'includes/js/bootstrap.min.js', dirname( __FILE__ ) ), array(), MO2F_VERSION, true );
	wp_print_scripts( 'jquery' );
	wp_print_scripts( 'mo2f_bootstrap_js' );
}

/**
 * This function shows download backup code popup.
 *
 * @param string $redirect_to redirect url.
 * @param string $session_id_encrypt encrypted session id.
 * @return void
 */
function mo2f_show_generated_backup_codes_inline( $redirect_to, $session_id_encrypt ) {

	$codes = mo2f_create_and_send_backupcodes_inline( $session_id_encrypt );
	?>
	<html>
		<head>  <meta charset="utf-8"/>
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php

			echo_js_css_files();
			wp_register_style( 'mo2f_bootstrap', plugins_url( 'includes/css/bootstrap.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION, false );
			wp_print_styles( 'mo2f_bootstrap' );
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

							<h3> <?php esc_html_e( 'Please download the backup codes for account recovery.', 'miniorange-2-factor-authentication' ); ?></h3>

							<h4> 
								<?php
								esc_html_e(
									'You will receive the backup codes via email if you have your SMTP configured.',
									'miniorange-2-factor-authentication'
								);
								?>
								<br>
								<?php
								esc_html_e(
									'If you have received the codes on your email and do not wish to download the codes, click on Finish.',
									'miniorange-2-factor-authentication'
								);
								?>
									</h4>
							<h4> 
								<?php
								esc_html_e(
									'Backup Codes can be used to login into user account in case you forget your phone or get locked out.',
									'miniorange-2-factor-authentication'
								);
								?>
								<br>
								<?php
								esc_html_e(
									'Please use this carefully as each code can only be used once. Please do not share these codes with anyone.',
									'miniorange-2-factor-authentication'
								);
								?>
									</h4>
							<div>   
								<div style="display: inline-flex;width: 350px; ">
									<div id="clipboard" style="border: solid;width: 55%;float: left;">
										<?php
										$size = count( $codes );
										for ( $x = 0; $x < $size; $x++ ) {
											$str = $codes[ $x ];
											echo( '<br>' . esc_html( $str ) . ' <br>' );
										}

										$str1 = '';
										$size = count( $codes );
										for ( $x = 0; $x < $size; $x++ ) {
											$str   = $codes[ $x ];
											$str1 .= $str;
											if ( 4 !== $x ) {
												$str1 .= ',';
											}
										}
										?>
									</div>
									<div  style="width: 50%;float: right;">
										<form name="f" method="post" id="mo2f_users_backup1" action="">
											<input type="hidden" name="option" value="mo2f_users_backup1" />
											<input type="hidden" name="mo2f_inline_backup_codes" value="<?php echo esc_attr( $str1 ); ?>" />
											<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
											<input type="hidden" name="mo2f_inline_backup_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-backup-nonce' ) ); ?>" />
											<input type="submit" name="Generate Codes1" id="codes" style="display:inline;width:100%;margin-left: 20%;margin-bottom: 37%;margin-top: 29%" class="miniorange_button button button-primary button-large" value="<?php esc_attr_e( 'Download Codes', 'miniorange-2-factor-authentication' ); ?>" />
										</form>
									</div>

									<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" >
										<input type="hidden" name="option" value="mo2f_goto_wp_dashboard" />
										<input type="hidden" name="mo2f_inline_wp_dashboard_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-wp-dashboard-nonce' ) ); ?>" />
										<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
										<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
										<input type="submit" name="login_page" id="login_page" style="display:inline;margin-left:-198%;margin-top: 289% !important;margin-right: 24% !important;width: 209%" class="miniorange_button button button-primary button-large" value="<?php esc_attr_e( 'Finish', 'miniorange-2-factor-authentication' ); ?>"  /><br>
									</form>
								</div>
							</div>

								<?php
								mo2f_customize_logo()
								?>
						</div>
					</div>
				</div>
			</div>
			<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" style="display:none;">
				<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>" />
			</form>
		</body>
		<script>
			function mologinback(){
				jQuery('#mo2f_backto_mo_loginform').submit();
			}
		</script>
	</html>
		<?php

}

/**
 * Creates and sends backupcodes.
 *
 * @param string $session_id_encrypt Session Id.
 * @return array
 */
function mo2f_create_and_send_backupcodes_inline( $session_id_encrypt ) {

	global $mo2fdb_queries;
	$id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
	update_site_option( 'mo2f_is_inline_used', '1' );
	$mo2f_user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $id );
	if ( empty( $mo2f_user_email ) ) {
		$currentuser     = get_user_by( 'id', $id );
		$mo2f_user_email = $currentuser->user_email;
	}
	$generate_backup_code = new MocURL();
	$codes                = $generate_backup_code->mo_2f_generate_backup_codes( $mo2f_user_email, site_url() );
	$codes                = explode( ' ', $codes );
	$result               = MO2f_Utility::mo2f_email_backup_codes( $codes, $mo2f_user_email );
	update_user_meta( $id, 'mo_backup_code_generated', 1 );
	update_user_meta( $id, 'mo_backup_code_screen_shown', 1 );
	return $codes;
}

/**
 * This function used for creation of backup codes
 *
 * @param string $redirect_to redirect url.
 * @param string $session_id_encrypt encrypted session id.
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @return void
 */
function mo2f_create_backup_form( $redirect_to, $session_id_encrypt, $login_status, $login_message ) {
	?>
		<form name="f" id="mo2f_backup" method="post" action="" style="display:none;">
			<input type="hidden" name="miniorange_backup_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-backup-nonce' ) ); ?>" />
			<input type="hidden" name="option" value="miniorange_backup_nonce">
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>" />
			<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>" />
		</form>
		<form name="f" id="mo2f_create_backup_codes" method="post" action="" style="display:none;">
			<input type="hidden" name="miniorange_generate_backup_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-generate-backup-nonce' ) ); ?>" />
			<input type="hidden" name="option" value="miniorange_create_backup_codes">
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>" />
			<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>" />
			<input type="hidden" name="login_status" value="<?php echo esc_attr( $login_status ); ?>" />
			<input type="hidden" name="login_message" value="<?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?>" />
		</form>
	<?php
}
