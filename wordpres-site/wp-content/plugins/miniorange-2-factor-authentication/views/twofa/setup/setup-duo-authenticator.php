<?php
/**
 * Frontend for Duo Authenticator set up.
 *
 * @package miniorange-2-factor-authentication/views/twofa/setup
 */

// Needed in both.

use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shows frontend for Duo Authenticator set up.
 *
 * @param object $user User object.
 * @return void
 */
function mo2f_configure_duo_authenticator( $user ) {
	if ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'duo_mobile_send_push_notification_inside_plugin' ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is not necessary here.
			mo2f_setup_duo_authenticator();
	} elseif ( get_user_meta( $user->ID, 'user_not_enroll' ) ) {
		mo2f_inside_plugin_go_for_user_enroll_on_duo( $user );
	} elseif ( get_site_option( 'duo_credentials_save_successfully' ) ) {
		mo2f_download_instruction_for_duo_mobile_app();
	} else {
		if ( current_user_can( 'administrator' ) ) {
			mo2f_save_duo_configuration_credentials();
		} else {
			mo2f_non_admin_notice();
		}
	}

}

/**
 * Frontend to test duo authenticator.
 *
 * @return void
 */
function mo2f_setup_duo_authenticator() {

	?>
<h3><?php esc_html_e( 'Test Duo Authenticator', 'miniorange-2-factor-authentication' ); ?></h3>
	<hr>
	<div>
		<br>
		<br>
		<div class="mo2f_align_center">
			<h3><?php esc_html_e( 'Duo push notification is sent to your mobile phone.', 'miniorange-2-factor-authentication' ); ?>
				<br>
				<?php esc_html_e( 'We are waiting for your approval...', 'miniorange-2-factor-authentication' ); ?></h3>
			<img src="<?php echo esc_url( plugins_url( 'includes/images/ajax-loader-login.gif', dirname( dirname( dirname( __FILE__ ) ) ) ) ); ?>"/>
		</div>

		<input type="button" name="back" id="go_back" class="button button-primary button-large"
			value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>"
			style="margin-top:100px;margin-left:10px;"/>
	</div>

	<form name="f" method="post" action="" id="mo2f_go_back_form">
		<input type="hidden" name="option" value="mo2f_go_back"/>
		<input type="hidden" name="mo2f_go_back_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
	</form>
	<form name="f" method="post" id="duo_mobile_register_form" action="">
		<input type="hidden" name="option" value="mo2f_configure_duo_authenticator_validate_nonce"/>
		<input type="hidden" name="mo2f_configure_duo_authenticator_validate_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'mo2f-configure-duo-authenticator-validate-nonce' ) ); ?>"/>
	</form>
	<form name="f" method="post" id="mo2f_duo_authenticator_error_form" action="">
		<input type="hidden" name="option" value="mo2f_duo_authenticator_error"/>

		<input type="hidden" name="mo2f_duo_authentcator_error_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'mo2f-duo-authenticator-error-nonce' ) ); ?>"/>
	</form>

	<script>
		jQuery('#go_back').click(function () {
			jQuery('#mo2f_go_back_form').submit();
		});

		var timeout;



			pollMobileValidation();
			function pollMobileValidation() {
				var nonce = "<?php echo esc_js( wp_create_nonce( 'miniorange-2-factor-duo-nonce' ) ); ?>";
				var data={
				'action':'mo2f_duo_authenticator_ajax',
				'call_type':'check_duo_push_auth_status',
				'nonce': nonce,				
			}; 

			jQuery.post(ajaxurl, data, function(response){						
						if (response == 'SUCCESS') {
							jQuery('#duo_mobile_register_form').submit();
						} else if (response == 'ERROR' || response == 'FAILED' || response == 'DENIED') {
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
 * Frontend to enroll user on DUO Authenticator.
 *
 * @param object $user User object.
 * @return void
 */
function mo2f_inside_plugin_go_for_user_enroll_on_duo( $user ) {
	$regis = get_user_meta( $user->ID, 'user_not_enroll_on_duo_before' );
	$regis = isset( $regis[0] ) ? $regis[0] : MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/2-factor-authentication-for-wordpress';
	?>
	<div style = " background-color: #d9eff6;">
	<p style = "font-size: 17px;">
		<?php esc_html_e( 'Register push notification as Two Factor Authentication using the below link.', 'miniorange-2-factor-authentication' ); ?> 
		<?php esc_html_e( 'After registration if you have not received  authenticate requestyet, please click on ', 'miniorange-2-factor-authentication' ); ?><b><?php esc_html_e( 'Send Me Push Notification.', 'miniorange-2-factor-authentication' ); ?></b> 
	</p>
	</div>
	<br>
	<p style = " font-size: 17px;"><b>Step : 1 </b></p>
	<div style = " background-color: #d9eff6;" > 
	<p style = " font-size: 17px;">
	<b> <a href="<?php echo esc_url( $regis ); ?>" target="_blank">Click Here</a></b> <?php esc_html_e( 'to configure DUO Push Notification. Once done with registration click on ', 'miniorange-2-factor-authentication' ); ?><b><?php esc_html_e( 'Send Me Push Notification Button.', 'miniorange-2-factor-authentication' ); ?></b>  
	</p>
	</div> 
	<br>
	<form name="f" method="post" id="duo_mobile_send_push_notification_inside_plugin" action="" >
		<input type="hidden" name="option" value="duo_mobile_send_push_notification_inside_plugin" />
		<input type="hidden" name="duo_mobile_send_push_notification_inside_plugin_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'mo2f-send-duo-push-notification-inside-plugin-nonce' ) ); ?>"/>
		<p style = " font-size: 17px;"><b>Step : 2 </b></p>
		<input type="submit" name="validate" id="validate" class="button button-primary button-large"
			value="<?php esc_attr_e( 'Send Me Push Notification', 'miniorange-2-factor-authentication' ); ?>"/>
		<br><br><br>
		<input type="button" name="back" id="go_back_form" class="button button-primary button-large" value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>" />
		<?php if ( current_user_can( 'administrator' ) ) { ?>
		<input type="button" name="back" id="reset_duo_configuration" class="button button-primary button-large" value="<?php esc_attr_e( 'Reset Duo Configuration', 'miniorange-2-factor-authentication' ); ?>" />
		<?php } ?>
</form>
<form name="f" method="post" action="" id="mo2f_go_back_form">
				<input type="hidden" name="option" value="mo2f_go_back" />
				<input type="hidden" name="mo2f_go_back_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
</form>
<form name="f" method="post" action="" id="mo2f_reset_duo_configuration">
						<input type="hidden" name="option" value="mo2f_reset_duo_configuration" />
						<input type="hidden" name="mo2f_duo_reset_configuration_nonce"
							value="<?php echo esc_attr( wp_create_nonce( 'mo2f-duo-reset-configuration-nonce' ) ); ?>"/>
</form>   
			<script>
				jQuery('#go_back_form').click(function() {
					jQuery('#mo2f_go_back_form').submit();
				});
				jQuery('#reset_duo_configuration').click(function() {
					jQuery('#mo2f_reset_duo_configuration').submit();
				});
				jQuery("#mo2f_configurePhone").empty();
				jQuery("#mo2f_app_div").hide();
			</script>

	<?php
}

/**
 * Frontend to enroll user on DUO.
 *
 * @param object $user User object.
 * @param string $session_id Session ID.
 * @return void
 */
function mo2f_go_for_user_enroll_on_duo( $user, $session_id ) {
	$regis = get_user_meta( $user->ID, 'user_not_enroll_on_duo_before' );
	$regis = isset( $regis[0] ) ? $regis[0] : MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/2-factor-authentication-for-wordpress';
	?>
	<div style = " background-color: #d9eff6;">
	<p style = "font-size: 17px;">
		<?php esc_html_e( 'Register push notification as Two Factor Authentication using the below link.', 'miniorange-2-factor-authentication' ); ?> 
		<?php esc_html_e( 'After registration if you have not received  authenticate request yet, please click on ', 'miniorange-2-factor-authentication' ); ?><b><?php esc_html_e( 'Send Me Push Notification.', 'miniorange-2-factor-authentication' ); ?></b> 
	</p>
	</div>
	<br>
	<p style = " font-size: 17px;"><b>Step : A </b></p>
	<div style = " background-color: #d9eff6;" > 
	<p style = " font-size: 17px;">
	<a href="<?php echo esc_url( $regis ); ?>" target="_blank">Click Here</a> <?php esc_html_e( 'to configure DUO Push Notification. Once done with registration click on ', 'miniorange-2-factor-authentication' ); ?><b><?php esc_html_e( 'Send Me Push Notification.', 'miniorange-2-factor-authentication' ); ?></b>  
	</p>
	</div> 

	<form name="f" method="post" id="duo_mobile_send_push_notification_for_inline_form" action="" >
		<input type="hidden" name="option" value="duo_mobile_send_push_notification_for_inline_form" />
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>" />
		<input type="hidden" name="duo_mobile_send_push_notification_inline_form_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'mo2f-send-duo-push-notification-inline-nonce' ) ); ?>"/>
		<p style = " font-size: 17px;"><b>Step : B </b></p>
		<input type="submit" name="validate" id="validate" class="button button-primary button-large"
			value="<?php esc_attr_e( 'Send Me Push Notification', 'miniorange-2-factor-authentication' ); ?>"/>
		<br><br><br>
		<input type="button" name="back" id="go_back_form" class="button button-primary button-large" value="<?php esc_attr_e( 'Back' ); ?>" />
	</form>
	<form name="f" method="post" action="" id="mo2f_go_back_form">
				<input type="hidden" name="option" value="mo2f_go_back" />
				<input type="hidden" name="mo2f_go_back_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
	</form>
			<script>
				jQuery('#go_back_form').click(function() {
					jQuery('#mo2f_go_back_form').submit();
				});
				jQuery("#mo2f_configurePhone").empty();
				jQuery("#mo2f_app_div").hide();
			</script>

	<?php
}

/**
 * Show notification for non-admin users to contact administrator.
 *
 * @return void
 */
function mo2f_non_admin_notice() {

	?>
	<div style = " background-color: #d9eff6;">
	<p style = "font-size: 25px;">
		<?php esc_html_e( 'Please contact your administrator, to configure DUO push notification, your administrator needs to enter DUO credentials first after that you can configure. Click BACK button to configure other method.', 'miniorange-2-factor-authentication' ); ?> 

	</p>
	</div>

	<form name="f" method="post" id="duo_notice_for_non_admin" action="" >
		<input type="hidden" name="option" value="duo_notice_for_non_admin" />
		<input type="hidden" name="duo_notice_for_non_admin_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'duo-notice-for-non-admin-nonce' ) ); ?>"/>
		<input type="button" name="back" id="go_back_form" class="button button-primary button-large" value="<?php esc_attr_e( 'Back' ); ?>" />
	</form>
	<form name="f" method="post" action="" id="mo2f_go_back_form">
				<input type="hidden" name="option" value="mo2f_go_back" />
				<input type="hidden" name="mo2f_go_back_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
	</form>
		<script>
			jQuery('#go_back_form').click(function() {
				jQuery('#mo2f_go_back_form').submit();
			});
			jQuery("#mo2f_configurePhone").empty();
			jQuery("#mo2f_app_div").hide();
		</script>

	<?php
}

/**
 * Shows download instructions for DUO mobile APP
 *
 * @return void
 */
function mo2f_download_instruction_for_duo_mobile_app() {

	?>
	<form name="f" method="post" id="duo_mobile_register_form" action="">
		<input type="hidden" name="option" value="mo2f_configure_duo_authenticator_abc"/>
		<input type="hidden" name="mo2f_configure_duo_authenticator_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'mo2f-configure-duo-authenticator-nonce' ) ); ?>"/>
		<a class="mo_app_link" data-toggle="collapse" href="#mo2f_sub_header_app" aria-expanded="false">
			<h3 class="mo2f_authn_header"><?php esc_html_e( 'Step-1 : Download the Duo', 'miniorange-2-factor-authentication' ); ?> <span style="color: #F78701;"> <?php esc_html_e( 'Authenticator', 'miniorange-2-factor-authentication' ); ?></span> <?php esc_html_e( 'App', 'miniorange-2-factor-authentication' ); ?>
		</h3>
		</a>
		<hr class="mo_hr">
		<div class="mo2f_collapse in" id="mo2f_sub_header_app">
			<table width="100%;" id="mo2f_inline_table">
				<tr id="mo2f_inline_table">
					<td style="padding:10px;">
						<h4 id="user_phone_id"><?php esc_html_e( 'iPhone Users', 'miniorange-2-factor-authentication' ); ?></h4>
						<hr>
						<ol>
							<li>
								<?php esc_html_e( 'Go to App Store', 'miniorange-2-factor-authentication' ); ?>
							</li>
							<li>
								<?php esc_html_e( 'Search for', 'miniorange-2-factor-authentication' ); ?> <b><?php esc_html_e( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::DUO_AUTHENTICATOR, 'cap_to_small' ), 'miniorange-2-factor-authentication' ); ?></b>
							</li>
							<li>
								<?php esc_html_e( 'Download and install ', 'miniorange-2-factor-authentication' ); ?><span style="color: #F78701;"><?php esc_html_e( 'Duo', 'miniorange-2-factor-authentication' ); ?><b> <?php esc_html_e( 'Authenticator', 'miniorange-2-factor-authentication' ); ?></b></span>
								<?php esc_html_e( 'app ', 'miniorange-2-factor-authentication' ); ?>(<b><?php esc_html_e( 'NOT Duo', 'miniorange-2-factor-authentication' ); ?></b>)
							</li>
						</ol>
						<br>
						<a style="margin-left:10%" target="_blank" href="https://apps.apple.com/app/id1482362759"><img src="<?php echo esc_url( plugins_url( 'includes/images/appstore.png', dirname( dirname( dirname( __FILE__ ) ) ) ) ); ?>" style="width:120px; height:45px; margin-left:6px;">
						</a>
					</td>
					<td style="padding:10px;">
						<h4 id="user_phone_id"><?php esc_html_e( 'Android Users', 'miniorange-2-factor-authentication' ); ?></h4>
						<hr>
						<ol>
							<li>
								<?php esc_html_e( 'Go to Google Play Store.', 'miniorange-2-factor-authentication' ); ?>
							</li>
							<li>
								<?php esc_html_e( 'Search for ', 'miniorange-2-factor-authentication' ); ?><b><?php esc_html_e( 'Duo Authenticator.', 'miniorange-2-factor-authentication' ); ?></b>
							</li>
							<li>
								<?php esc_html_e( 'Download and install', 'miniorange-2-factor-authentication' ); ?> <span style="color: #F78701;"><b><?php esc_html_e( 'Authenticator', 'miniorange-2-factor-authentication' ); ?></b></span>
								<?php esc_html_e( 'app', 'miniorange-2-factor-authentication' ); ?> (<b><?php esc_html_e( 'NOT Duo', 'miniorange-2-factor-authentication' ); ?> </b>)
							</li>
						</ol>
						<br>
						<a style="margin-left:10%" target="_blank" href="https://play.google.com/store/apps/details?id=com.miniorange.android.authenticator&hl=en"><img src="<?php echo esc_url( plugins_url( 'includes/images/playStore.png', dirname( dirname( dirname( __FILE__ ) ) ) ) ); ?>" style="width:120px; height:=45px; margin-left:6px;"></a>
					</td>
				</tr>
			</table>

			<input type="button" name="back" id="mo2f_inline_back_btn" class="button button-primary button-large" value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>" />								
			<input type="submit" name="submit" id="mo2f_plugin_configure_btn" class="button button-primary button-large" value="<?php esc_attr_e( 'Configure your phone', 'miniorange-2-factor-authentication' ); ?>" />
		</div>
	</form>
	<form name="f" method="post" action="" id="mo2f_go_back_form">
		<input type="hidden" name="option" value="mo2f_go_back" />
		<input type="hidden" name="mo2f_go_back_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
	</form>
			<script>
				jQuery('#mo2f_inline_back_btn').click(function() {
					jQuery('#mo2f_go_back_form').submit();
				});								
			</script>
	<?php
}
/**
 * Show DUO mobile app download instructions in inline registration flow.
 *
 * @param boolean $mobile_registration_status Mobile registration status.
 * @return void
 */
function mo2f_inline_download_instruction_for_duo_mobile_app( $mobile_registration_status = false ) {

	?>

	<div id="mo2f_app_div" class="mo_margin_left">
		<a class="mo_app_link" data-toggle="collapse" href="#mo2f_sub_header_app" aria-expanded="false">
			<h3 class="mo2f_authn_header"><?php esc_html_e( 'Step-1 : Download the Duo', 'miniorange-2-factor-authentication' ); ?> <span style="color: #F78701;"> <?php esc_html_e( 'Authenticator', 'miniorange-2-factor-authentication' ); ?></span> <?php esc_html_e( 'App' ); ?>
		</h3>
		</a>
		<hr class="mo_hr">
		<div class="mo2f_collapse in" id="mo2f_sub_header_app">
			<table width="100%;" id="mo2f_inline_table">
				<tr id="mo2f_inline_table">
					<td style="padding:10px;">
						<h4 id="user_phone_id"><?php esc_html_e( 'iPhone Users', 'miniorange-2-factor-authentication' ); ?></h4>
						<hr>
						<ol>
							<li>
								<?php esc_html_e( 'Go to App Store', 'miniorange-2-factor-authentication' ); ?>
							</li>
							<li>
								<?php esc_html_e( 'Search for', 'miniorange-2-factor-authentication' ); ?> <b><?php esc_html_e( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::DUO_AUTHENTICATOR, 'cap_to_small' ), 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?></b>
							</li>
							<li>
								<?php esc_html_e( 'Download and install ', 'miniorange-2-factor-authentication' ); ?><span style="color: #F78701;"><?php esc_html_e( 'Duo', 'miniorange-2-factor-authentication' ); ?><b> <?php esc_html_e( 'Authenticator', 'miniorange-2-factor-authentication' ); ?></b></span>
								<?php esc_html_e( 'app ', 'miniorange-2-factor-authentication' ); ?>(<b><?php esc_html_e( 'NOT Duo', 'miniorange-2-factor-authentication' ); ?></b>)
							</li>
						</ol>
						<br>
						<a style="margin-left:10%" target="_blank" href="https://apps.apple.com/app/id1482362759"><img src="<?php echo esc_url( plugins_url( 'includes/images/appstore.png', dirname( dirname( dirname( __FILE__ ) ) ) ) ); ?>" style="width:120px; height:45px; margin-left:6px;">
						</a>
					</td>
					<td style="padding:10px;">
						<h4 id="user_phone_id"><?php esc_html_e( 'Android Users', 'miniorange-2-factor-authentication' ); ?></h4>
						<hr>
						<ol>
							<li>
								<?php esc_html_e( 'Go to Google Play Store.', 'miniorange-2-factor-authentication' ); ?>
							</li>
							<li>
								<?php esc_html_e( 'Search for ', 'miniorange-2-factor-authentication' ); ?><b><?php esc_html_e( 'Duo Authenticator.', 'miniorange-2-factor-authentication' ); ?></b>
							</li>
							<li>
								<?php esc_html_e( 'Download and install', 'miniorange-2-factor-authentication' ); ?> <span style="color: #F78701;"><b><?php esc_html_e( 'Authenticator', 'miniorange-2-factor-authentication' ); ?></b></span>
								<?php esc_html_e( 'app', 'miniorange-2-factor-authentication' ); ?> (<b><?php esc_html_e( 'NOT Duo', 'miniorange-2-factor-authentication' ); ?> </b>)
							</li>
						</ol>
						<br>
						<a style="margin-left:10%" target="_blank" href="https://play.google.com/store/apps/details?id=com.miniorange.android.authenticator&hl=en"><img src="<?php echo esc_url( plugins_url( 'includes/images/playStore.png', dirname( dirname( dirname( __FILE__ ) ) ) ) ); ?>" style="width:120px; height:=45px; margin-left:6px;"></a>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<?php
	if ( $mobile_registration_status ) {
		?>
				<script>
					jQuery("#mo2f_app_div").hide();
				</script>
			<?php } else { ?>				
				<script>
					jQuery("#mo2f_app_div").show();
				</script>
		<?php } ?> 

	<?php
}
/**
 * Frontend to save DUO credentials for configuration.
 *
 * @return void
 */
function mo2f_save_duo_configuration_credentials() {

	?>
<h3><?php esc_html_e( 'Please enter required details', 'miniorange-2-factor-authentication' ); ?>
	</h3> 
	<p  style = "font-size: 17px;">
		<?php esc_html_e( '1. If you do not have an account in duo, please', 'miniorange-2-factor-authentication' ); ?>  <a href="https://signup.duo.com/" target="_blank">Click Here </a><?php esc_html_e( 'to create an account.', 'miniorange-2-factor-authentication' ); ?> 

	</p>
	<p  style = "font-size: 17px;">
		<?php esc_html_e( '2. Follow these steps( ', 'miniorange-2-factor-authentication' ); ?> <a href=" https://duo.com/docs/authapi#first-steps" target="_blank">Click Here </a> <?php esc_html_e( ') to create AUTH API application on duo side. After creating auth API, you will get the all credentials which you need to enter below.', 'miniorange-2-factor-authentication' ); ?> 

	</p>
	<br>
	<div> 
	<form name="f" method="post" action="" id="mo2f_save_duo_configration">
		<input type="hidden" name="option" value="mo2f_configure_duo_authenticator"/>
		<input type="hidden" name="mo2f_configure_duo_authenticator_nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'mo2f-configure-duo-authenticator' ) ); ?>"/>
		<p><?php esc_html_e( 'Integration key', 'miniorange-2-factor-authentication' ); ?> 
		&nbsp &nbsp <input class="mo2f_table_textbox" style="width:400px;" autofocus="true" type="text" name="ikey"
			placeholder="<?php esc_attr_e( 'Integration key', 'miniorange-2-factor-authentication' ); ?>" style="width:95%;"/>		
		</p>
		<br><br>
		<p><?php esc_html_e( 'Secret Key', 'miniorange-2-factor-authentication' ); ?> 
		&nbsp &nbsp &nbsp &nbsp &nbsp &nbsp<input class="mo2f_table_textbox" style="width:400px;" autofocus="true" type="text" name="skey"
			placeholder="<?php esc_attr_e( 'Secret key', 'miniorange-2-factor-authentication' ); ?>" style="width:95%;"/>		
		</p>
		<br><br>
		<p><?php esc_html_e( 'API Hostname', 'miniorange-2-factor-authentication' ); ?> 
		&nbsp &nbsp <input class="mo2f_table_textbox" style="width:400px;" autofocus="true" type="text" name="apihostname"
			placeholder="<?php esc_attr_e( 'API Hostname', 'miniorange-2-factor-authentication' ); ?>" style="width:95%;"/>		
		</p>
		<br><br>
		<input type="button" name="back" id="go_back" class="button button-primary button-large"
			value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>"/>
		<input type="submit" name="validate" id="validate" class="button button-primary button-large"
			value="<?php esc_attr_e( 'Save', 'miniorange-2-factor-authentication' ); ?>"/>
	</form><br>
	<form name="f" method="post" action="" id="mo2f_go_back_form">
		<input type="hidden" name="option" value="mo2f_go_back"/>
		<input type="hidden" name="mo2f_go_back_nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
	</form>
			<script>
				jQuery('#go_back').click(function() {
					jQuery('#mo2f_go_back_form').submit();
				});
			</script> 
	</div>  

	<script>
		jQuery("#phone").intlTelInput();
		jQuery('#go_back').click(function () {
			jQuery('#mo2f_go_back_form').submit();
		});
		jQuery('a[href=\"#resendsmslink\"]').click(function (e) {
			jQuery('#mo2f_verifyphone_form').submit();
		});

	</script>

	<?php

}

?>
