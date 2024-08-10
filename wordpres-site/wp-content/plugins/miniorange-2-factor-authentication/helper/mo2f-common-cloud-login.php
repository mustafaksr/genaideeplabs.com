<?php
/**
 * This file contains functions related to login flow.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Onprem\MO2f_Utility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This function prompts QR code authentication
 *
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $qr_code qr code data url.
 * @param string $session_id_encrypt encrypted session id.
 * @param object $cookievalue cookie value.
 * @return void
 */
function mo2f_get_qrcode_authentication_prompt( $login_status, $login_message, $redirect_to, $qr_code, $session_id_encrypt, $cookievalue ) {
	$mo_wpns_config = new MoWpnsHandler();
	$user_id        = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
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
						<?php esc_html_e( 'Scan QR Code', 'miniorange-2-factor-authentication' ); ?></h4>
				</div>
				<div class="mo2f_modal-body center">
					<?php if ( isset( $login_message ) && ! empty( $login_message ) ) { ?>
						<div id="otpMessage">
							<p class="mo2fa_display_message_frontend"><?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?></p>
						</div>
						<br>
					<?php } ?>
					<div id="scanQRSection">
						<div style="margin-bottom:10%;">
							<div class="mo2fa_text-align-center">
								<p class="mo2f_login_prompt_messages"><?php esc_html_e( 'Identify yourself by scanning the QR code with miniOrange Authenticator app.', 'miniorange-2-factor-authentication' ); ?></p>
					</div>
						</div>
						<div id="showQrCode" style="margin-bottom:10%;">
							<div class="mo2fa_text-align-center"><?php echo '<img src="data:image/jpg;base64,' . esc_html( $qr_code ) . '" />'; ?></div>
						</div>
						<span style="padding-right:2%;">
						<div class="mo2fa_text-align-center">
							<input type="button" name="miniorange_login_offline" onclick="mologinoffline();"
									id="miniorange_login_offline" class="miniorange_login_offline"
									value="<?php esc_attr_e( 'Phone is Offline?', 'miniorange-2-factor-authentication' ); ?>"/>
					</div>
					</span>
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
					<?php
						mo2f_customize_logo();
						mo2f_create_backup_form( $redirect_to, $session_id_encrypt, $login_status, $login_message );
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
	<form name="f" id="mo2f_mobile_validation_form" method="post" class="mo2f_display_none_forms">
		<input type="hidden" name="miniorange_mobile_validation_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-nonce' ) ); ?>"/>
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="option" value="miniorange_mobile_validation">
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
	</form>
	<form name="f" id="mo2f_show_softtoken_loginform" method="post" class="mo2f_display_none_forms">
		<input type="hidden" name="miniorange_softtoken"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-softtoken' ) ); ?>"/>
		<input type="hidden" name="option" value="miniorange_softtoken">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
	</form>
	<form name="f" id="mo2f_show_forgotphone_loginform" method="post" class="mo2f_display_none_forms">
		<input type="hidden" name="request_origin_method" value="<?php echo esc_attr( $login_status ); ?>"/>
		<input type="hidden" name="miniorange_forgotphone"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-forgotphone' ) ); ?>"/>
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="option" value="miniorange_forgotphone">
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
	</form>

	<script>
		var timeout;
		pollMobileValidation();

		function pollMobileValidation() {
			var transId = "<?php echo esc_js( $cookievalue ); ?>";
			var jsonString = "{\"txId\":\"" + transId + "\"}";
			var postUrl = "<?php echo esc_url( MO_HOST_NAME ); ?>" + "/moas/api/auth/auth-status";
			jQuery.ajax({
				url: postUrl,
				type: "POST",
				dataType: "json",
				data: jsonString,
				contentType: "application/json; charset=utf-8",
				success: function (result) {
					var status = JSON.parse(JSON.stringify(result)).status;
					if (status === 'SUCCESS') {
						var content = "<div id='success'><div class='mo2fa_text-align-center'><img src='" + "<?php echo esc_url( plugins_url( 'includes/images/right.png', dirname( __FILE__ ) ) ); ?>" + "' /></div></div>";
						jQuery("#showQrCode").empty();
						jQuery("#showQrCode").append(content);
						setTimeout(function () {
							jQuery("#mo2f_mobile_validation_form").submit();
						}, 100);
					} else if (status === 'ERROR' || status === 'FAILED') {
						var content = "<div id='error'><div class='mo2fa_text-align-center'><img src='" + "<?php echo esc_url( plugins_url( 'includes/images/wrong.png', dirname( __FILE__ ) ) ); ?>" + "' /></div></div>";
						jQuery("#showQrCode").empty();
						jQuery("#showQrCode").append(content);
						setTimeout(function () {
							jQuery('#mo2f_backto_mo_loginform').submit();
						}, 1000);
					} else {
						timeout = setTimeout(pollMobileValidation, 3000);
					}
				}
			});
		}

		function mologinoffline() {
			jQuery('#mo2f_show_softtoken_loginform').submit();
		}

		function mologinforgotphone() {
			jQuery('#mo2f_show_forgotphone_loginform').submit();
		}

		function mologinback() {
			jQuery('#mo2f_backto_mo_loginform').submit();
		}
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
 * This function prompts email authentication
 *
 * @param string $id user id.
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $session_id_encrypt encrypted session id.
 * @param string $cookievalue cookie value.
 * @return void
 */
function mo2f_get_push_notification_oobemail_prompt( $id, $login_status, $login_message, $redirect_to, $session_id_encrypt, $cookievalue ) {
	$mo_wpns_config = new MoWpnsHandler();
	global $mo2fdb_queries,$mo_wpns_utility;
	$mo2f_enable_forgotphone = MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_forgotphone', 'get_option' );
	$mo2f_kba_config_status  = $mo2fdb_queries->get_user_detail( 'mo2f_SecurityQuestions_config_status', $id );
	$mo2f_ev_txid            = get_user_meta( $id, 'mo2f_transactionId', true );
	$user_id                 = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
	MO2f_Utility::mo2f_debug_file( 'Waiting for push notification validation User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
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
						<?php
							esc_html_e( 'Accept Your Transaction', 'miniorange-2-factor-authentication' );
						?>
						</h4>
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
								<img src="<?php echo esc_url( plugins_url( 'includes/images/ajax-loader-login.gif', dirname( __FILE__ ) ) ); ?>"/>
					</div>
						</div>


						<span style="padding-right:2%;">
							<?php if ( isset( $login_status ) && 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' === $login_status ) { ?>
								<div class="mo2fa_text-align-center">
									&emsp;&emsp;
								<input type="button" name="miniorange_login_offline" onclick="mologinoffline();"
										id="miniorange_login_offline" class="miniorange_login_offline"
									value="<?php esc_attr_e( 'Phone is Offline?', 'miniorange-2-factor-authentication' ); ?>"/>
									</div>
							<?php } elseif ( isset( $login_status ) && 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL' === $login_status && $mo2f_enable_forgotphone && $mo2f_kba_config_status ) { ?>
								<div class="mo2fa_text-align-center">
								<a href="#mo2f_alternate_login_kba">
									<p class="mo2f_push_oob_backup"><?php esc_html_e( 'Didn\'t receive mail?', 'miniorange-2-factor-authentication' ); ?></p>
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
	<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>"
			class="mo2f_display_none_forms">
		<input type="hidden" name="miniorange_mobile_validation_failed_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>"/>
		<input type="hidden" name="option" value="miniorange_mobile_validation_failed">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
		<input type="hidden" name="currentMethod" value="emailVer"/>

	</form>
	<form name="f" id="mo2f_mobile_validation_form" method="post" class="mo2f_display_none_forms">
		<input type="hidden" name="miniorange_mobile_validation_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-nonce' ) ); ?>"/>
		<input type="hidden" name="option" value="miniorange_mobile_validation">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="tx_type"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
		<input type="hidden" name="TxidEmail" value="<?php echo esc_attr( $mo2f_ev_txid ); ?>"/>   

	</form>
	<form name="f" id="mo2f_show_softtoken_loginform" method="post" class="mo2f_display_none_forms">
		<input type="hidden" name="miniorange_softtoken"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-softtoken' ) ); ?>"/>
		<input type="hidden" name="option" value="miniorange_softtoken">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
	</form>
	<form name="f" id="mo2f_show_forgotphone_loginform" method="post" class="mo2f_display_none_forms">
		<input type="hidden" name="request_origin_method" value="<?php echo esc_attr( $login_status ); ?>"/>
		<input type="hidden" name="miniorange_forgotphone"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-forgotphone' ) ); ?>"/>
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
		var login_status = '<?php echo esc_js( $login_status ); ?>';
		var calls     = 0;
		var onprem = '<?php echo esc_js( MO2F_IS_ONPREM ); ?>';
		if( login_status !== "MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS" && onprem == 1 )
		{
			pollPushValidation();
			function pollPushValidation()
			{   calls = calls + 1;
				var data = {'txid':'<?php echo esc_js( $mo2f_ev_txid ); ?>'};
					jQuery.ajax({
					url: '<?php echo esc_url( get_site_option( 'siteurl' ) ); ?>'+"/wp-login.php",
					type: "POST",
					data: data,
					success: function (result) {

					var status = result;
						if (status == 1) {
							jQuery('input[name="tx_type"]').val("EV");
							jQuery('#mo2f_mobile_validation_form').submit();
						} else if (status == 'ERROR' || status == 'FAILED' || status == 'DENIED' || status == 0) {
							jQuery('#mo2f_backto_mo_loginform').submit();
						} else {
							if(calls<300)
							{
							timeout = setTimeout(pollPushValidation, 1000);
							}
							else
							{
								jQuery('#mo2f_backto_mo_loginform').submit();
							}
						}
					}
				});
			}
		} else {
			mo2f_pollPushValidation();
			function mo2f_pollPushValidation() {
				var transId = "<?php echo esc_js( $cookievalue ); ?>";
				var jsonString = "{\"txId\":\"" + transId + "\"}";

				var postUrl = "<?php echo esc_url( MO_HOST_NAME ); ?>" + "/moas/api/auth/auth-status";
				jQuery.ajax({
					url: postUrl,
					type: "POST",
					dataType: "json",
					data: jsonString,
					contentType: "application/json; charset=utf-8",
					success: function (result) {
						var status = JSON.parse(JSON.stringify(result)).status;
						if (status === 'SUCCESS') {
							jQuery('input[name="tx_type"]').val("PN");
							jQuery('#mo2f_mobile_validation_form').submit();
						} else if (status == 'ERROR' || status == 'FAILED' || status == 'DENIED') {
							jQuery('#mo2f_backto_mo_loginform').submit();
						} else {
							timeout = setTimeout(mo2f_pollPushValidation, 3000);
						}
					}
				});
			}
		}

		function mologinoffline() {
			jQuery('#mo2f_show_softtoken_loginform').submit();
		}

		function mologinforgotphone() {
			jQuery('#mo2f_show_forgotphone_loginform').submit();
		}

		function mologinback() {
			jQuery('#mo2f_backto_mo_loginform').submit();
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
