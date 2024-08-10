<?php
/**
 * This file contains all the functions and views regarding setup wizard flow
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.

namespace TwoFA\Views;

use TwoFA\Helper\Mo2f_Common_Otp_Setup;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Onprem\Google_Auth_Onpremise;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2f_Setup_Wizard' ) ) {
	/**
	 * Includes all the functions and views regarding setup wizard flow
	 */
	class Mo2f_Setup_Wizard {

		/**
		 * Total steps present in the setup wizard.
		 *
		 * @var array
		 */
		private $wizard_steps;

		/**
		 * Step on which user is present during setup wizard.
		 *
		 * @var string
		 */
		private $current_step;

		/**
		 * Includes styles , scripts and redirected URLs.
		 *
		 * @return void
		 */
		public function mo2f_setup_page() {
			// Get page argument from $_GET array.

			$page = ( isset( $_GET['page'] ) ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
			if ( empty( $page ) || 'mo2f-setup-wizard' !== $page ) {
				return;
			}
			if ( get_site_option( 'mo2f_setup_complete' ) === 1 ) {
				$this->mo2f_redirect_to_2fa_dashboard();
			}
			$wizard_steps       = array(
				'welcome'                => array(
					'content' => array( $this, 'mo2f_step_welcome' ),
				),
				'settings_configuration' => array(
					'content' => array( $this, 'mo2f_step_global_2fa_methods' ),
					'save'    => array( $this, 'mo2f_step_global_2fa_methods_save' ),
				),
				'finish'                 => array(
					'content' => array( $this, 'mo2f_step_finish' ),
					'save'    => array( $this, 'mo2f_step_finish_save' ),
				),
			);
			$this->wizard_steps = apply_filters( 'mo2f_wizard_default_steps', $wizard_steps );

			// Set current step.
			$current_step       = ( isset( $_GET['current-step'] ) ) ? sanitize_text_field( wp_unslash( $_GET['current-step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
			$this->current_step = ! empty( $current_step ) ? $current_step : current( array_keys( $this->wizard_steps ) );
			wp_register_style( 'mo_2fa_admin_setupWizard', plugins_url( 'includes' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'setup-wizard.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
			wp_enqueue_script( 'mo2f_setup_wizard', plugins_url( 'includes' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'setup-wizard.min.js', dirname( __FILE__ ) ), array(), MO2F_VERSION, false );
			$save_step = ( isset( $_POST['save_step'] ) ) ? sanitize_text_field( wp_unslash( $_POST['save_step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading POST parameter for checking the saved step, doesn't require nonce verification for the 1st window.
			if ( ! empty( $save_step ) && ! empty( $this->wizard_steps[ $this->current_step ]['save'] ) ) {
				call_user_func( $this->wizard_steps[ $this->current_step ]['save'] );
			}
			$this->mo2f_setup_page_header();
			$this->mo2f_setup_page_content();
			exit();
		}

		/**
		 * Setupwizard twofa method configuration part.
		 *
		 * @return void
		 */
		public function mo2f_setup_twofa_dynamically() {
			// Get page argument from $_GET array.
			$page = ( isset( $_GET['page'] ) ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
			if ( empty( $page ) || 'mo2f-setup-wizard-method' !== $page ) {
				return;
			}
			if ( get_site_option( 'mo2f_setup_complete' ) === 1 ) {
				$this->mo2f_redirect_to_2fa_dashboard();
			}
			$wizard_steps = array(
				'step_1_of_4' => array(
					'content' => array( $this, 'mo2f_select_2fa_method' ),
				),
				'step_2_of_4' => array(
					'content' => array( $this, 'mo_2fa_steup_wizard_user_register_login' ),
				),
				'step_3_of_4' => array(
					'content' => array( $this, 'mo_2fa_configure_twofa_setup_wizard' ),
				),
				'step_4_of_4' => array(
					'content' => array( $this, 'mo_2fa_setup_wizard_completed' ),
				),

			);
			$this->wizard_steps = apply_filters( 'mo2f_wizard_default_steps', $wizard_steps );
			// Set current step.
			$current_step       = ( isset( $_GET['current-step'] ) ) ? sanitize_text_field( wp_unslash( $_GET['current-step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
			$this->current_step = ! empty( $current_step ) ? $current_step : current( array_keys( $this->wizard_steps ) );
			wp_enqueue_script( 'jquery' );

			$this->mo2f_setup_page_header();

			wp_register_script( 'mo2f_qr_code_minjs', plugins_url( '/includes/jquery-qrcode/jquery-qrcode.min.js', dirname( __FILE__ ) ), array(), MO2F_VERSION, true );
			wp_register_script( 'mo2f_phone_js', plugins_url( '/includes/js/phone.min.js', dirname( __FILE__ ) ), array(), MO2F_VERSION, true );
			wp_register_style( 'mo_2fa_admin_setupWizard', plugins_url( 'includes/css/setup-wizard.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
			wp_register_style( 'mo2f_phone_css', plugins_url( 'includes/css/phone.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
			echo '<head>';
			wp_print_scripts( 'mo2f_qr_code_minjs' );
			wp_print_scripts( 'mo2f_phone_js' );
			wp_print_styles( 'mo2f_phone_css' );
			wp_print_styles( 'mo_2fa_admin_setupWizard' );
			wp_print_styles( 'dashicons' );
			echo '</head>';

			$this->mo2f_setup_page_content();
			exit();
		}

		/**
		 * Step 1 of 4 - Setup wizard.
		 *
		 * @return void
		 */
		public function mo2f_select_2fa_method() {
			?>
				<p class="mo2f-step-show"><?php esc_html_e( 'Step 1 of 4', 'miniorange-2-factor-authentication' ); ?> </p>
				<h3> <?php esc_html_e( 'Select the Authentication method you want to configure', 'miniorange-2-factor-authentication' ); ?> </h3>
				<div class="mo2f-input-radios-with-icons">
					<br>
					<label title="<?php esc_attr_e( 'You have to enter 6 digits code generated by google Authenticator App to login. Supported in Smartphones only.', 'miniorange-2-factor-authentication' ); ?>">
						<input type="radio" name="mo2f_selected_2factor_method" class="mo2f-styled-radio" value="<?php echo esc_attr( MoWpnsConstants::GOOGLE_AUTHENTICATOR ); ?>" />
						<span class="mo2f-styled-radio-text"> 
							<?php esc_html_e( 'Google/Microsoft/Authy Authenticator', 'miniorange-2-factor-authentication' ); ?>
						</span>
					</label>
					<label title="<?php esc_attr_e( 'You will receive a one time passcode via SMS on your phone. You have to enter the otp on your screen to login. Supported in Smartphones, Feature Phones.', 'miniorange-2-factor-authentication' ); ?>">
						<input type="radio" name="mo2f_selected_2factor_method" class="mo2f-styled-radio" value="<?php echo esc_attr( MoWpnsConstants::OTP_OVER_SMS ); ?>" />
						<span class="mo2f-styled-radio-text">
							<?php esc_html_e( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_SMS, 'cap_to_small' ) . '(Registration Required)', 'miniorange-2-factor-authentication' );  //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?>
						</span>
					</label>
					<label title="<?php esc_attr_e( 'You will receive a one time passcode on your email. You have to enter the otp on your screen to login. Supported in Smartphones, Feature Phones.', 'miniorange-2-factor-authentication' ); ?>">
						<input type="radio" name="mo2f_selected_2factor_method" class="mo2f-styled-radio" value="<?php echo esc_attr( MoWpnsConstants::OTP_OVER_EMAIL ); ?>" />
						<span class="mo2f-styled-radio-text">
							<?php esc_html_e( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_EMAIL, 'cap_to_small' ), 'miniorange-2-factor-authentication' );  //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?>
						</span>
					</label>
					<label title="<?php esc_attr_e( 'You have to answers some knowledge based security questions which are only known to you to authenticate yourself. Supported in Desktops,Laptops,Smartphones.', 'miniorange-2-factor-authentication' ); ?>">
						<input type="radio" name="mo2f_selected_2factor_method" class="mo2f-styled-radio" value="<?php echo esc_attr( MoWpnsConstants::SECURITY_QUESTIONS ); ?>" />
						<span class="mo2f-styled-radio-text">
							<?php esc_html_e( 'Security Questions ( KBA )', 'miniorange-2-factor-authentication' ); ?>
						</span>
					</label>

					<label title="<?php esc_attr_e( 'You will get an OTP on your TELEGRAM app from miniOrange Bot.', 'miniorange-2-factor-authentication' ); ?>">
						<input type="radio" name="mo2f_selected_2factor_method" class="mo2f-styled-radio" value="<?php echo esc_attr( MoWpnsConstants::OTP_OVER_TELEGRAM ); ?>" />
						<span class="mo2f-styled-radio-text">
							<?php esc_html_e( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_TELEGRAM, 'cap_to_small' ), 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?>
						</span>
					</label>
				</div>
				<div class="mo2f-setup-wizard-step-footer" style="display: flex;">
					<div style="margin:0px;width:30%">
					</div>
					<a href=""></a>
					<div class="mo2f-setup-actions mo_save_and_continue_step1">
						<input type="button" name="mo2f_next_step1" id="mo2f_next_step1" class="button button-primary" value="Save & Continue" />
					</div>
					<div class="mo2fa_skiptwofactor1">
						<a href="#skiptwofactor1" class="mo2f_setup_wizard_footer_buttons" style=""><?php esc_html_e( 'Skip Setup', 'miniorange-2-factor-authentication' ); ?></a>
					</div>
				</div>
				<script>
					jQuery("#mo2f_next_step1").click(function(e) {
						var ele = document.getElementsByName('mo2f_selected_2factor_method');
						var selected_2FA_method = '';
						for (i = 0; i < ele.length; i++) {
							if (ele[i].checked)
								selected_2FA_method = ele[i].value;
						}
						if (selected_2FA_method === '') {
							return '';
						}
						var mo2f_setup_call = "";
						is_customer_registered = '<?php echo esc_js( get_option( 'mo2f_api_key' ) ? 'true' : 'false' ); ?>';
						if( selected_2FA_method === "<?php echo esc_attr( MoWpnsConstants::OTP_OVER_SMS ); ?>" && is_customer_registered == 'false' ){
							window.location.href = '<?php echo esc_url( admin_url() ); ?>' + 'admin.php?page=mo2f-setup-wizard-method&current-step=step_2_of_4';
						}else{
							window.location.href = '<?php echo esc_url( admin_url() ); ?>' + 'admin.php?page=mo2f-setup-wizard-method&current-step=step_3_of_4&twofa-method='+selected_2FA_method;
						}
					});
					jQuery('a[href="#skiptwofactor1"]').click(function() {
						localStorage.setItem("last_tab", 'setup_2fa');
						var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>';
						var skiptwofactorstage = 'first_page';
						var data = {
							'action': 'mo_two_factor_ajax',
							'mo_2f_two_factor_ajax': 'mo2f_skiptwofactor_wizard',
							'nonce': nonce,
							'twofactorskippedon': skiptwofactorstage,
						};
						var ajax_url = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
						jQuery.post(ajax_url, data, function(response) {
							window.location.href = '<?php echo esc_js( admin_url() ); ?>' + 'admin.php?page=mo_2fa_two_fa';
						});
					});
				</script>
			<?php

		}

		/**
		 * Step 2 of 4 - Setup wizard.
		 *
		 * @return void
		 */
		public function mo_2fa_steup_wizard_user_register_login() {
			?>
			<p class="mo2f-step-show"><?php esc_html_e( 'Step 2 of 4', 'miniorange-2-factor-authentication' ); ?>  </p>
				<h3 id="mo2f_register_login_heading"> <?php esc_html_e( 'Register with miniOrange', 'miniorange-2-factor-authentication' ); ?> </h3>
				<form name="f" id="mo2f_registration_form" method="post" action="">
					<input type="hidden" name="option" value="mo_wpns_register_customer" />
					<input type="hidden" name="mo2f_general_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniOrange_2fa_nonce' ) ); ?>" />
					<div class="mo2f_table_layout">
						<div style="margin-bottom:30px;">
							<div class="overlay_error mo2f_Error_block" style="display: none;" id="mo2f_Error_block">
								<p class="popup_text mo2f_Error_message" id="mo2f_Error_message" style="color: red;"><?php esc_html_e( 'Seems like email is already registered. Please click on \'Already have an account\'', 'miniorange-2-factor-authentication' ); ?></p>
							</div>
							<p> <?php esc_html_e( 'Please enter a valid email id that you have access to and select a password', 'miniorange-2-factor-authentication' ); ?></p>
							<table class="mo_wpns_settings_table mo2f_width_80">
								<tr>
									<td><b><span class="mo2f_setup_font_color">*</span><?php esc_html_e( 'Email', 'miniorange-2-factor-authentication' ); ?>:</b></td>
									<td><input style="padding: 4px;" class="mo_wpns_table_textbox" type="text" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" id="mo2f_email" name="email" required placeholder="person@example.com" /></td>
								</tr>

								<tr>
									<td><b><span class="mo2f_setup_font_color">*</span><?php esc_html_e( 'Password', 'miniorange-2-factor-authentication' ); ?>:</b></td>
									<td><input style="padding: 4px;" class="mo_wpns_table_textbox" required id="mo2f_password" type="password" name="password" placeholder="Choose your password (Min. length 6)" /></td>
								</tr>
								<tr>
									<td><b><span class="mo2f_setup_font_color">*</span><?php esc_html_e( 'Confirm Password', 'miniorange-2-factor-authentication' ); ?>:</b></td>
									<td><input style="padding: 4px;" class="mo_wpns_table_textbox" id="mo2f_confirmPassword" required type="password" name="confirmPassword" placeholder="Confirm your password" /></td>
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td><br>
										<a href="#mo2f_account_exist"><?php esc_html_e( 'Already have an account?', 'miniorange-2-factor-authentication' ); ?></a>

								</tr>
							</table>
						</div>
					</div>
				</form>
				<form name="f" id="mo2f_login_form" style="display: none;" method="post" action="">
					<input type="hidden" name="option" value="mo_wpns_verify_customer" />
					<input type="hidden" name="mo2f_general_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniOrange_2fa_nonce' ) ); ?>" />
					<div class="mo2f_table_layout">
						<div style="margin-bottom:30px;">
							<div class="overlay_error mo2f_Error_block" style="display: none;" id="mo2f_Error_block">
								<p class="popup_text mo2f_Error_message" id="mo2f_Error_message" style="color: red;"><?php esc_html_e( 'Invalid Credentials', 'miniorange-2-factor-authentication' ); ?></p>
							</div>
							<p><?php esc_html_e( 'Please enter your miniOrange email and password.', 'miniorange-2-factor-authentication' ); ?><a target="_blank" href="
							<?php
							echo esc_url( MO_HOST_NAME . '/moas/idp/resetpassword' );
							?>
							"> <?php esc_html_e( 'Click here if you forgot your password?', 'miniorange-2-factor-authentication' ); ?></a></p>
							<table class="mo_wpns_settings_table mo2f_width_80">
								<tr>
									<td><b><span class="mo2f_setup_font_color">*</span><?php esc_html_e( 'Email', 'miniorange-2-factor-authentication' ); ?>:</b></td>
									<td><input style="padding: 4px;" class="mo_wpns_table_textbox" type="email" id="mo2f_email_login" autofocus="true" name="email" required placeholder="person@example.com" /></td>
								</tr>
								<tr>
									<td><b><span class="mo2f_setup_font_color">*</span><?php esc_html_e( 'Password', 'miniorange-2-factor-authentication' ); ?>:</b></td>
									<td><input style="padding: 4px;" class="mo_wpns_table_textbox" required id="mo2f_password_login" type="password" name="password" placeholder="Enter your miniOrange password" /></td>
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td><br>
										<a href="#mo2f_register_new_account"><?php esc_html_e( 'Go Back to Registration Page', 'miniorange-2-factor-authentication' ); ?></a>

								</tr>
							</table>
						</div>
					</div>
				</form>

				<div class="mo2f-setup-wizard-step-footer">
					<div class="mo2f_previousStep2">

						<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=mo2f-setup-wizard-method&current-step=step_1_of_4"><span style="float:left;" class="text-with-arrow text-with-arrow-left mo2f_setup_wizard_footer_buttons"><svg viewBox="0 0 448 512" role="img" class="icon" data-icon="long-arrow-alt-left" data-prefix="far" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="12">
									<path xmlns="http://www.w3.org/2000/svg" fill="currentColor" d="M107.515 150.971L8.485 250c-4.686 4.686-4.686 12.284 0 16.971L107.515 366c7.56 7.56 20.485 2.206 20.485-8.485v-71.03h308c6.627 0 12-5.373 12-12v-32c0-6.627-5.373-12-12-12H128v-71.03c0-10.69-12.926-16.044-20.485-8.484z"></path>
								</svg> <?php esc_html_e( 'Go Back', 'miniorange-2-factor-authentication' ); ?></span></a>
					</div>
					<div class="mo2f-setup-actions mo2f-setup-wizard-step-footer-buttons">
						<input type="button" name="mo2f_next_step2" id="mo2f_next_step2" class="button button-primary" value="Create Account & Continue" />
					</div>
					<div class="mo2fa_skiptwofactor2">
						<a href="#skiptwofactor2" class="mo2f_setup_wizard_footer_buttons" style=""><?php esc_html_e( 'Skip Setup', 'miniorange-2-factor-authentication' ); ?></a>
					</div>
				</div>
				<script>
					jQuery('a[href=\"#mo2f_account_exist\"]').click(function(e) {
						document.getElementById('mo2f_registration_form').style.display = "none";
						document.getElementById('mo2f_login_form').style.display = "block";
						document.getElementById('mo2f_register_login_heading').innerHTML = "Login with miniOrange";
						var nodelist = document.getElementsByClassName('mo2f_Error_block');
						for (let i = 0; i < nodelist.length; i++) {
							nodelist[i].style.display = "none";
						}
						var input = jQuery("#mo2f_password_login");
						var len = input.val().length;
						input[0].focus();
						input[0].setSelectionRange(len, len);
						jQuery("#mo2f_password_login").keypress(function(e) {
							if (e.which === 13) {
								e.preventDefault();
								jQuery("#mo2f_next_step2").click();
							}

						});
						document.getElementById('mo2f_next_step2').value = 'Login & Continue';
						jQuery("#mo2f_otp_token").focus();
					});
					jQuery('a[href=\"#mo2f_register_new_account\"]').click(function(e) {
						document.getElementById('mo2f_registration_form').style.display = "block";
						document.getElementById('mo2f_login_form').style.display = "none";
						document.getElementById('mo2f_register_login_heading').innerHTML = "Register with miniOrange";
						var nodelist = document.getElementsByClassName('mo2f_Error_block');
						for (let i = 0; i < nodelist.length; i++) {
							nodelist[i].style.display = "none";
						}

						var input = jQuery("#mo2f_email");
						var len = input.val().length;
						input[0].focus();
						input[0].setSelectionRange(len, len);
						document.getElementById('mo2f_next_step2').value = 'Create Account and Continue';
					});
					jQuery("#mo2f_next_step2").click(function(e) {
						var ajax_url = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
						var email = jQuery("#mo2f_email").val();
						var password = jQuery("#mo2f_password").val();
						if (jQuery("#mo2f_next_step2").val() === 'Login & Continue') {
							email = jQuery("#mo2f_email_login").val();
							password = jQuery("#mo2f_password_login").val();
						}
						var nonce = "<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>";
						var data = {
							'action': 'mo_two_factor_ajax',
							'mo_2f_two_factor_ajax': 'mo_wpns_register_verify_customer',
							'nonce': nonce,
							'email': email,
							'password': password,
							'confirmPassword': jQuery("#mo2f_confirmPassword").val(),
							'Login_and_Continue': jQuery("#mo2f_next_step2").val()
						};
						jQuery.post(ajax_url, data, function(response) {
							if (response.success) {
								window.location.href = '<?php echo esc_url( admin_url() ); ?>' + 'admin.php?page=mo2f-setup-wizard-method&current-step=step_3_of_4&twofa-method=SMS';
							} else {
								nodelist = document.getElementsByClassName('mo2f_Error_message');
								for (let i = 0; i < nodelist.length; i++) {
									nodelist[i].innerHTML = response.data;
								}
								document.getElementById('mo2f_Error_block').style.display = "block";
							}
						});

					});
					jQuery('a[href="#skiptwofactor2"]').click(function() {
						localStorage.setItem("last_tab", 'setup_2fa');
						var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>';
						var skiptwofactorstage = 'Login/registration page';
						var data = {
							'action': 'mo_two_factor_ajax',
							'mo_2f_two_factor_ajax': 'mo2f_skiptwofactor_wizard',
							'nonce': nonce,
							'twofactorskippedon': skiptwofactorstage,
						};
						var ajax_url = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
						jQuery.post(ajax_url, data, function(response) {
							window.location.href = '<?php echo esc_js( admin_url() ); ?>' + 'admin.php?page=mo_2fa_two_fa';
						});
					});
				</script>
				<?php
		}

		/**
		 * Step 3 of 4 - Setup wizard.
		 *
		 * @return void
		 */
		public function mo_2fa_configure_twofa_setup_wizard() {
			$twofa_method         = ( isset( $_GET['twofa-method'] ) ) ? sanitize_text_field( wp_unslash( $_GET['twofa-method'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
			$method_and_functions = array(
				MoWpnsConstants::GOOGLE_AUTHENTICATOR => array( $this, 'mo_2fa_configure_ga_setup_wizard' ),
				MoWpnsConstants::SECURITY_QUESTIONS   => array( $this, 'mo_2fa_configure_kba_setup_wizard' ),
				MoWpnsConstants::OTP_OVER_TELEGRAM    => array( $this, 'mo_2fa_configure_otp_over_telegram_setup_wizard' ),
				MoWpnsConstants::OTP_OVER_SMS         => array( $this, 'mo_2fa_configure_otp_over_sms_setup_wizard' ),
				MoWpnsConstants::OTP_OVER_EMAIL       => array( $this, 'mo_2fa_configure_otp_over_email_setup_wizard' ),
			);
			?>
			<p class="mo2f-step-show"><?php esc_html_e( 'Step 3 of 4', 'miniorange-2-factor-authentication' ); ?> </p>
			<h3 style="text-align:center;" id="mo2f_setup_method_title"> <?php esc_html_e( 'Configure : ' . esc_html( MoWpnsConstants::mo2f_convert_method_name( $twofa_method, 'cap_to_small' ) ), 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?> </h3> 
			<div class="overlay_success" style="display:none;height:60px;" id="mo2f_success_block_configuration">
				<p class="popup_text" id="mo2f_configure_success_message"><?php esc_html_e( 'An OTP has been sent to the below email.', 'miniorange-2-factor-authentication' ); ?></p>
			</div>
			<div class="overlay_error" style="display: none;" id="mo2f_Error_block_configuration">
				<p class="popup_text" id="mo2f_configure_Error_message" style="color: red;"><?php esc_html_e( 'Invalid OTP', 'miniorange-2-factor-authentication' ); ?></p>
			</div>
			<?php
			if ( ! empty( $method_and_functions[ $twofa_method ] ) ) {
				call_user_func( $method_and_functions[ $twofa_method ] );
			}
			?>
			<div class="mo2f-setup-wizard-step-footer">
				<div class="mo2fa_previous_step3">
					<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=mo2f-setup-wizard-method&current-step=step_1_of_4"><span style="float:left;" class="text-with-arrow text-with-arrow-left mo2f_setup_wizard_footer_buttons"><svg viewBox="0 0 448 512" role="img" class="icon" data-icon="long-arrow-alt-left" data-prefix="far" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="12">
								<path xmlns="http://www.w3.org/2000/svg" fill="currentColor" d="M107.515 150.971L8.485 250c-4.686 4.686-4.686 12.284 0 16.971L107.515 366c7.56 7.56 20.485 2.206 20.485-8.485v-71.03h308c6.627 0 12-5.373 12-12v-32c0-6.627-5.373-12-12-12H128v-71.03c0-10.69-12.926-16.044-20.485-8.484z"></path>
							</svg><?php esc_html_e( 'Go Back', 'miniorange-2-factor-authentication' ); ?> </span></a>
				</div>
				<div class="mo2f-setup-actions mo_save_and_continue3">
					<input type="button" name="mo2f_next_step3" id="mo2f_next_step3" class="button button-primary" value="Save & Continue" />
				</div>
				<div class="mo2fa_skiptwofactor3">
					<a href="#skiptwofactor3" class="mo2f_setup_wizard_footer_buttons" style=""><?php esc_html_e( 'Skip Setup', 'miniorange-2-factor-authentication' ); ?></a>
				</div>
			</div>
			<script>
				var ajax_url = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
				var nonce = "<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>";
				var selected_2FA_method = "<?php echo esc_js( str_replace( '-', ' ', $twofa_method ) ); ?>";
				jQuery('a[href="#skiptwofactor3"]').click(function() {
					localStorage.setItem("last_tab", 'setup_2fa');
					var skiptwofactorstage = 'configuration';
					var data = {
						'action': 'mo_two_factor_ajax',
						'mo_2f_two_factor_ajax': 'mo2f_skiptwofactor_wizard',
						'nonce': nonce,
						'twofactorskippedon': skiptwofactorstage,
					};
					jQuery.post(ajax_url, data, function(response) {
						window.location.href = '<?php echo esc_js( admin_url() ); ?>' + 'admin.php?page=mo_2fa_two_fa';
					});
				});
				if (selected_2FA_method === '<?php echo esc_js( MoWpnsConstants::OTP_OVER_TELEGRAM ); ?>' || selected_2FA_method === '<?php echo esc_js( MoWpnsConstants::OTP_OVER_EMAIL ); ?>'  ||  selected_2FA_method == '<?php echo esc_attr( MoWpnsConstants::OTP_OVER_SMS ); ?>' ){
					var input = jQuery('input[name=mo2f_phone_email_telegram]');
					var len = input.val().length;
					input[0].focus();
					input[0].setSelectionRange(len, len);
					jQuery('input[name=mo2f_phone_email_telegram]').keypress(function(e) {
						if (e.which === 13) {
							e.preventDefault();
							jQuery("#mo2f_verify").click();
							jQuery("#mo2f_otp_token").focus();
						}

					});
					jQuery("input[name=otp_token]").keypress(function(e) {
						if (e.which === 13) {
							e.preventDefault();
							jQuery("#mo2f_next_step3").click();
						}

					});
				}
				jQuery('#mo2f_verify, a[href=\"#resendotplink\"]').click(function(e) {
					var selected_2FA_method = "<?php echo esc_js( str_replace( '-', ' ', $twofa_method ) ); ?>";
					document.getElementById('mo2f_success_block_configuration').style.display = "none";
					document.getElementById('mo2f_Error_block_configuration').style.display = "none";
					var data = {
						'action': 'mo_two_factor_ajax',
						'mo_2f_two_factor_ajax': 'mo2f_configure_otp_based_twofa',
						'nonce': nonce,
						'mo2f_phone_email_telegram': jQuery('input[name=mo2f_phone_email_telegram]').val(),
						'mo2f_session_id': jQuery("input[name=mo2f_session_id]").val(),
						'mo2f_otp_based_method': selected_2FA_method,
					};
					jQuery.post(ajax_url, data, function(response) {
						if (response['success']) {
							message = response['data'] ;
							document.getElementById('mo2f_configure_success_message').innerHTML = message;
							document.getElementById('mo2f_success_block_configuration').style.display = "block";
							jQuery('#go_back_verify').css('display','none');
							jQuery('#mo2f_validateotp_form').css('display','block');
							jQuery("input[name=otp_token]").focus();
						} else if ( ! response['success'] ) {
							message = response['data'];
							document.getElementById('mo2f_configure_Error_message').innerHTML = message;
							document.getElementById('mo2f_success_block_configuration').style.display = "none";
							document.getElementById('mo2f_Error_block_configuration').style.display = "block";
						} else {
							message = 'Unknown error occured. Please try again!'; 
							document.getElementById('mo2f_configure_Error_message').innerHTML = message;
							document.getElementById('mo2f_success_block_configuration').style.display = "none";
							document.getElementById('mo2f_Error_block_configuration').style.display = "block";
						}
					});
				});
				jQuery('#mo2f_next_step3').click(function(e) {
					var selected_2FA_method = "<?php echo esc_js( str_replace( '-', ' ', $twofa_method ) ); ?>";
					var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>';
					if (selected_2FA_method === '<?php echo esc_js( MoWpnsConstants::GOOGLE_AUTHENTICATOR ); ?>') {
						data = {
							'action': 'mo_two_factor_ajax',
							'nonce': nonce,
							'mo_2f_two_factor_ajax': 'mo_2fa_verify_GA_setup_wizard',
							'mo2f_google_auth_code': jQuery('#mo2f_google_auth_code').val(),
							'mo2f_session_id': jQuery('#mo2f_session_id').val()
						};
					} 
					else if ( selected_2FA_method === '<?php echo esc_js( MoWpnsConstants::OTP_OVER_TELEGRAM ); ?>' || selected_2FA_method === '<?php echo esc_js( MoWpnsConstants::OTP_OVER_EMAIL ); ?>' ||  selected_2FA_method == '<?php echo esc_attr( MoWpnsConstants::OTP_OVER_SMS ); ?>' ) {
						var data = {
							'action'  : 'mo_two_factor_ajax',
							'mo_2f_two_factor_ajax'  : 'mo2f_configure_otp_based_methods_validate',
							'mo2f_otp_based_method'  : selected_2FA_method,
							'otp_token'  :  jQuery('input[name=otp_token]').val(),
							'mo2f_session_id'  : jQuery('input[name=mo2f_session_id]').val(),
							'nonce'  : nonce,	
						};
					} 
					else if (selected_2FA_method === '<?php echo esc_js( MoWpnsConstants::SECURITY_QUESTIONS ); ?>' ) {
						data = {
							'action': 'mo_two_factor_ajax',
							'mo_2f_two_factor_ajax': 'mo_2fa_verify_KBA_setup_wizard',
							'nonce': nonce,
							'mo2f_kbaquestion_1': jQuery('#mo2f_kbaquestion_1').val(),
							'mo2f_kbaquestion_2': jQuery('#mo2f_kbaquestion_2').val(),
							'mo2f_kbaquestion_3': jQuery('#mo2f_kbaquestion_3').val(),
							'mo2f_kba_ans1': jQuery('#mo2f_kba_ans1').val(),
							'mo2f_kba_ans2': jQuery('#mo2f_kba_ans2').val(),
							'mo2f_kba_ans3': jQuery('#mo2f_kba_ans3').val()
						};
					}
					jQuery.post(ajax_url, data, function(response) {
						if (response['success']) {
							window.location.href = '<?php echo esc_url( admin_url() ); ?>' + 'admin.php?page=mo2f-setup-wizard-method&current-step=step_4_of_4';
						} else {
							jQuery("input[name=otp_token]").val('');
							document.getElementById('mo2f_configure_Error_message').innerHTML = response['data'];
							document.getElementById('mo2f_success_block_configuration').style.display = "none";
							document.getElementById('mo2f_Error_block_configuration').style.display = "block";
						}
					});
				});
			</script>
			<?php
		}

		/**
		 * Function to configure GA in setup wizard
		 *
		 * @return void
		 */
		public function mo_2fa_configure_ga_setup_wizard() {
			$path = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			include_once $path;
			$obj_google_auth = new Google_auth_onpremise();
			$gauth_name      = isset( $_SERVER['SERVER_NAME'] ) ? esc_url_raw( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : null;
			$gauth_name      = preg_replace( '#^https?://#i', '', $gauth_name ); // To remove http:// or https:// from the Google Authenticator Appname.
			update_option( 'mo2f_google_appname', $gauth_name );
			update_option( 'mo2f_wizard_selected_method', 'GA' );
			$obj_google_auth->mo_g_auth_get_details( true );
		}

		/**
		 * Configures OTP Over SMS.
		 *
		 * @return void
		 */
		public function mo_2fa_configure_otp_over_sms_setup_wizard() {
			$twofa_method   = MoWpnsConstants::OTP_OVER_SMS;
			$user_id        = wp_get_current_user()->ID;
			$mo2f_otp_setup = new Mo2f_Common_Otp_Setup();
			$skeleton       = $mo2f_otp_setup->mo2f_sms_common_skeleton( $user_id );
			require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR . 'setup-otp-over-sms-email-telegram-setupwizard.php';
			?>
			<script>
				jQuery("#phone").intlTelInput();
				jQuery("#mo2f_transactions_check").click(function()
				{   
					var nonce = '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
					var data =
					{
						'action'                  : 'wpns_login_security',
						'wpns_loginsecurity_ajax' : 'wpns_check_transaction',
						'nonce'                   :nonce
					};
					var ajax_url = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
					jQuery.post(ajax_url, data, function(response) {
						window.location.reload(true);
					});
				});
			</script>
			<?php
		}

		/**
		 * Configures OTP Over Email.
		 *
		 * @return void
		 */
		public function mo_2fa_configure_otp_over_email_setup_wizard() {
			$twofa_method   = MoWpnsConstants::OTP_OVER_EMAIL;
			$user_id        = wp_get_current_user()->ID;
			$mo2f_otp_setup = new Mo2f_Common_Otp_Setup();
			$skeleton       = $mo2f_otp_setup->mo2f_email_common_skeleton( $user_id );
			require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR . 'setup-otp-over-sms-email-telegram-setupwizard.php';

		}

		/**
		 * Configures OTP Over Telegram.
		 *
		 * @return void
		 */
		public function mo_2fa_configure_otp_over_telegram_setup_wizard() {
			$twofa_method   = MoWpnsConstants::OTP_OVER_TELEGRAM;
			$user_id        = wp_get_current_user()->ID;
			$mo2f_otp_setup = new Mo2f_Common_Otp_Setup();
			$skeleton       = $mo2f_otp_setup->mo2f_telegram_common_skeleton( $user_id );
			require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR . 'setup-otp-over-sms-email-telegram-setupwizard.php';
		}

		/**
		 * Function to configure KBA in Setup wizard
		 *
		 * @return void
		 */
		public function mo_2fa_configure_kba_setup_wizard() {
			update_option( 'mo2f_wizard_selected_method', MoWpnsConstants::SECURITY_QUESTIONS );
			?>
			<br>
			<div class="mo2f_kba_header"><?php esc_html_e( 'Please choose 3 questions', 'miniorange-2-factor-authentication' ); ?></div>
			<br>
			<table cellspacing="10">
				<tr class="mo2f_kba_header">
					<th style="width: 10%;">
						<?php esc_html_e( 'Sr. No.', 'miniorange-2-factor-authentication' ); ?>
					</th>
					<th class="mo2f_kba_tb_data">
						<?php esc_html_e( 'Questions', 'miniorange-2-factor-authentication' ); ?>
					</th>
					<th>
						<?php esc_html_e( 'Answers', 'miniorange-2-factor-authentication' ); ?>
					</th>
				</tr>
				<tr class="mo2f_kba_body">
					<td>
						<div class="mo2fa_text-align-center">1.</div>
					</td>
					<td class="mo2f_kba_tb_data">
					<?php
					$this->mo2f_kba_question_set( 1 );
					?>
					</td>
					<td style="text-align: end;">
						<input class="mo2f_table_textbox_KBA" type="password" name="mo2f_kba_ans1" id="mo2f_kba_ans1"
							title="<?php esc_attr_e( 'Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed.', 'miniorange-2-factor-authentication' ); ?>"
							pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+\-\s]{1,100}" required="true" 
							placeholder="<?php esc_attr_e( 'Enter your answer', 'miniorange-2-factor-authentication' ); ?>"/>
					</td>
				</tr>
				<tr class="mo2f_kba_body">
					<td>
						<div class="mo2fa_text-align-center">2.</div>
					</td>
					<td class="mo2f_kba_tb_data">
					<?php
					$this->mo2f_kba_question_set( 2 );
					?>
					</td>
					<td style="text-align: end;">
						<input class="mo2f_table_textbox_KBA" type="password" name="mo2f_kba_ans2" id="mo2f_kba_ans2"
							title="<?php esc_attr_e( 'Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed.', 'miniorange-2-factor-authentication' ); ?>"
							pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+\-\s]{1,100}" required="true"
							placeholder="<?php esc_attr_e( 'Enter your answer', 'miniorange-2-factor-authentication' ); ?>"/>
					</td>
				</tr>
				<tr class="mo2f_kba_body">
					<td>
						<div class="mo2fa_text-align-center">3.</div>
					</td>
					<td class="mo2f_kba_tb_data">
						<input class="mo2f_kba_ques" type="text" style="width: 100%;"name="mo2f_kbaquestion_3" id="mo2f_kbaquestion_3"
							required="true"
							placeholder="<?php esc_attr_e( 'Enter your custom question here', 'miniorange-2-factor-authentication' ); ?>"/>
					</td>
					<td style="text-align: end;">
						<input class="mo2f_table_textbox_KBA" type="password" name="mo2f_kba_ans3" id="mo2f_kba_ans3"
							title="<?php esc_attr_e( 'Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed.', 'miniorange-2-factor-authentication' ); ?>"
							pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+\-\s]{1,100}" required="true"
							placeholder="<?php esc_attr_e( 'Enter your answer', 'miniorange-2-factor-authentication' ); ?>"/>
					</td>
				</tr>
			</table>

			<script type="text/javascript">
				var mo_option_to_hide1;
				var mo_option_to_hide2;
				function mo_option_hide(list) {
					var list_selected = document.getElementById("mo2f_kbaquestion_" + list).selectedIndex;
					if (typeof (mo_option_to_hide1) != "undefined" && mo_option_to_hide1 !== null && list == 2) {
						mo_option_to_hide1.style.display = 'block';
					} else if (typeof (mo_option_to_hide2) != "undefined" && mo_option_to_hide2 !== null && list == 1) {
						mo_option_to_hide2.style.display = 'block';
					}
					if (list == 1) {
						if (list_selected != 0) {
							mo_option_to_hide2 = document.getElementById("mq" + list_selected + "_2");
							mo_option_to_hide2.style.display = 'none';
						}
					}
					if (list == 2) {
						if (list_selected != 0) {
							mo_option_to_hide1 = document.getElementById("mq" + list_selected + "_1");
							mo_option_to_hide1.style.display = 'none';
						}
					}
				}

			</script>
			<?php
		}

		/**
		 * Show KBA question set.
		 *
		 * @param integer $question_no Question number.
		 * @return void
		 */
		public function mo2f_kba_question_set( $question_no ) {
			$question_set = array( 'What is your first company name?', 'What was your childhood nickname?', 'In what city did you meet your spouse/significant other?', 'What is the name of your favorite childhood friend?', 'What school did you attend for sixth grade?', 'In what city or town was your first job?', 'What is your favourite sport?', 'Who is your favourite sports player?', 'What is your grandmother\'s maiden name?', 'What was your first vehicle\'s registration number?' );
			?>

			<select name="mo2f_kbaquestion_<?php echo esc_attr( $question_no ); ?>" id="mo2f_kbaquestion_<?php echo esc_attr( $question_no ); ?>" class="mo2f_kba_ques" required="true" onchange="mo_option_hide(<?php echo esc_attr( $question_no ); ?>)">
				<option value="" selected="selected">
					------------<?php esc_html_e( 'Select your question', 'miniorange-2-factor-authentication' ); ?>
					------------
				</option>
				<?php
				foreach ( $question_set as $question ) {
					?>
					<option id="mq<?php echo esc_attr( array_search( $question, $question_set, true ) + 1 ); ?>_<?php echo esc_attr( $question_no ); ?>"
					value="<?php echo esc_attr( $question ); ?>">
						<?php
							printf(
							/* translators: %s: Name of the 2fa method */
								esc_html__( '%s', 'miniorange-2-factor-authentication' ),  //phpcs:ignore WordPress.WP.I18n.NoEmptyStrings -- The string is translatable
								esc_html( $question )
							);
						?>
				</option>
				<?php } ?>
			</select>
			<?php
		}

		/**
		 * Shows congratulations message.
		 *
		 * @return void
		 */
		public function mo_2fa_setup_wizard_completed() {
			?>
			<p class="mo2f-step-show"> <?php esc_html_e( 'Step 4 of 4', 'miniorange-2-factor-authentication' ); ?></p>
			<div style="text-align: center;">
				<h3 style="text-align:center;font-size: xx-large;"> <?php esc_html_e( 'Congratulations!', 'miniorange-2-factor-authentication' ); ?> </h3>
				<br>
				<?php esc_html_e( 'You have successfully configured the two-factor authentication.', 'miniorange-2-factor-authentication' ); ?>
				<br><br><br>
				<input type="button" name="mo2f_next_step4" id="mo2f_next_step4" class="mo2f-modal__btn button button-primary" value="Advance Settings" />
			</div>
			<script>
				jQuery('#mo2f_next_step4').click(function(e) {
					localStorage.setItem("last_tab", 'unlimittedUser_2fa');
					window.location.href = '<?php echo esc_js( admin_url() ); ?>' + 'admin.php?page=mo_2fa_two_fa';
				});
			</script>
			<?php
		}

		/**
		 * Load script in header on setup wizard.
		 *
		 * @return void
		 */
		public function mo2f_setup_wizard_header() {
			// both.
			?>
			<!DOCTYPE html>
			<html <?php language_attributes(); ?>>

			<head>
				<meta name="viewport" content="width=device-width" />
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title><?php esc_html_e( 'miniOrange 2-factor Setup Wizard', 'miniorange-2-factor-authentication' ); ?></title>
				<?php do_action( 'admin_print_styles' ); ?>
				<?php do_action( 'admin_print_scripts' ); ?>
				<?php do_action( 'admin_head' ); ?>
			</head>

			<body class="mo2f_setup_wizard">
			<?php
		}
		/**
		 * Header of the setup wizard
		 *
		 * @return void
		 */
		private function mo2f_setup_page_header() {
			?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php esc_html_e( 'miniOrange 2FA &rsaquo; Setup Wizard', 'miniorange-2-factor-authentication' ); ?></title>
			<?php
			wp_print_styles( 'mo_2fa_admin_setupWizard' );
			wp_print_scripts( 'jquery' );
			wp_print_scripts( 'jquery-ui-core' );
			wp_print_scripts( 'mo2f_setup_wizard' );
			?>
		<head>
		<body class="mo2f_body">
				<header class="mo2f-setup-wizard-header">
					<img width="70px" height="auto" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'includes/images/miniorange-new-logo.png' ); ?>" alt="<?php esc_attr_e( 'miniOrange 2-factor Logo', 'miniorange-2-factor-authentication' ); ?>" >
					<h1><?php esc_html_e( 'miniOrange 2-factor authentication Setup', 'miniorange-2-factor-authentication' ); ?></h1>
				</header>
			<?php
		}
		/**
		 * To redirect to the dashboard.
		 *
		 * @return void
		 */
		private function mo2f_redirect_to_2fa_dashboard() {
			wp_safe_redirect(
				add_query_arg(
					array( 'page' => 'mo_2fa_two_fa' ),
					admin_url( 'admin.php' )
				)
			);
			exit();
		}
		/**
		 * Get the next setup during setup wizard.
		 *
		 * @return string
		 */
		private function mo2f_get_next_step() {
			// Get current step.
			$current_step = $this->current_step;

			// Array of step keys.
			$keys = array_keys( $this->wizard_steps );
			if ( end( $keys ) === $current_step ) { // If last step is active then return WP Admin URL.
				return admin_url();
			}

			// Search for step index in step keys.
			$step_index = array_search( $current_step, $keys, true );
			if ( false === $step_index ) { // If index is not found then return empty string.
				return '';
			}

			// Return next step.
			return add_query_arg( 'current-step', $keys[ $step_index + 1 ] );
		}
		/**
		 * Call respective function based on current step.
		 *
		 * @return void
		 */
		private function mo2f_setup_page_content() {
			?>
		<div class="mo2f-setup-content">
			<?php
			if ( ! empty( $this->wizard_steps[ $this->current_step ]['content'] ) ) {
				call_user_func( $this->wizard_steps[ $this->current_step ]['content'] );
			}
			?>
		</div>
			<?php
		}
		/**
		 * Step View of welcome Page
		 *
		 * @return void
		 */
		private function mo2f_step_welcome() {
			$this->mo2f_welcome_step( $this->mo2f_get_next_step() );
		}
		/**
		 * Welcome step
		 *
		 * @param array $next_step url of the next step.
		 * @return void
		 */
		public function mo2f_welcome_step( $next_step ) {
			$redirect  = 'enforce-2fa';
			$admin_url = is_network_admin() ? network_admin_url() . 'admin.php?page=mo_2fa_two_fa' : admin_url() . 'admin.php?page=mo_2fa_two_fa';

			?>
		<h3><?php esc_html_e( 'Let us help you get started', 'miniorange-2-factor-authentication' ); ?></h3>
		<p class="mo2f-setup-wizard-font"><?php esc_html_e( 'This wizard will assist you with plugin configuration and the 2FA settings for you and the users on this website.', 'miniorange-2-factor-authentication' ); ?></p>

		<div class="mo2f-setup-actions">
			<a class="button button-primary"
				href="<?php echo esc_url( $next_step ); ?>">
				<?php esc_html_e( 'Let’s get started!', 'miniorange-2-factor-authentication' ); ?>
			</a>
			<a class="button button-secondary mo2f-first-time-wizard"
				href="<?php echo esc_url( $admin_url ); ?>">
				<?php esc_html_e( 'Skip Setup Wizard', 'miniorange-2-factor-authentication' ); ?>
			</a>
		</div>
			<?php
		}

		/**
		 * Finish Step
		 *
		 * @return void
		 */
		private function mo2f_step_finish() {
			$this->mo2f_congratulations_step();
		}
		/**
		 * Congratulations Screen
		 *
		 * @return void
		 */
		public function mo2f_congratulations_step() {
			$this->mo2f_congratulations_step_plugin_wizard();
		}

		/**
		 * Congratulations screen for settings
		 *
		 * @return void
		 */
		public static function mo2f_congratulations_step_plugin_wizard() {
			$redirect_to_2fa = is_network_admin() ? network_admin_url() . 'admin.php?page=mo2f-setup-wizard-method' : admin_url() . 'admin.php?page=mo2f-setup-wizard-method';
			$redirect        = is_network_admin() ? network_admin_url() . 'admin.php?page=mo_2fa_two_fa' : admin_url() . 'admin.php?page=mo_2fa_two_fa';
			update_site_option( 'mo2f_setup_complete', 1 );
			$user           = wp_get_current_user();
			$roles          = (array) $user->roles;
			$two_fa_enabled = 0;
			foreach ( $roles as $role ) {
				if ( get_option( 'mo2fa_' . $role ) === '1' ) {
					$two_fa_enabled = 1;
				}
			}
			$is_user_excluded = 1 !== $two_fa_enabled;
			$slide_title      = ( $is_user_excluded || ! MO2F_IS_ONPREM ) ? esc_html__( 'Congratulations.', 'miniorange-2-factor-authentication' ) : esc_html__( 'Congratulations, you\'re almost there...', 'miniorange-2-factor-authentication' );
			?>
		<h3><?php echo \esc_html( $slide_title ); ?></h3>
		<p><?php esc_html_e( 'Great job, the plugin and 2FA policies are now configured. You can always change the plugin settings and 2FA policies at a later stage from the miniOrange 2FA entry in the WordPress menu.', 'miniorange-2-factor-authentication' ); ?></p>

			<?php
			if ( $is_user_excluded || ! MO2F_IS_ONPREM ) {
				?>
		<div class="mo2f-setup-actions">
			<a href="<?php echo esc_url( $redirect ); ?>" class="button button-secondary mo2f-first-time-wizard">
					<?php esc_html_e( 'Close wizard', 'miniorange-2-factor-authentication' ); ?>
			</a>
		</div>
				<?php
			} else {
				?>
		<p><?php esc_html_e( 'Now you need to configure 2FA for your own user account. You can do this now (recommended) or later.', 'miniorange-2-factor-authentication' ); ?></p>
		<div class="mo2f-setup-actions">
			<input type="hidden" name="mo2f-setup-wizard-nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f_setup_wizard_nonce' ) ); ?>">
			<a href="<?php echo esc_url( $redirect_to_2fa ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Configure 2FA for yourself', 'miniorange-2-factor-authentication' ); ?>
			</a>
			<a href="<?php echo esc_url( $redirect ); ?>" class="button button-secondary mo2f-first-time-wizard">
					<?php esc_html_e( 'Close wizard & configure 2FA later', 'miniorange-2-factor-authentication' ); ?>
			</a>
		</div>
			<?php } ?>
			<?php
		}

		/**
		 * Redirect to next step after Finish Screen
		 *
		 * @return void
		 */
		private function mo2f_step_finish_save() {
			// Verify nonce.
			wp_safe_redirect( esc_url_raw( $this->mo2f_get_next_step() ) );
			exit();
		}

		/**
		 * Setup Wizard settings
		 *
		 * @return void
		 */
		private function mo2f_step_global_2fa_methods() {
			?>
			<form method="post" class="mo2f-setup-form mo2f-form-styles" autocomplete="off">
				<?php wp_nonce_field( 'mo2f-step-choose-method' ); ?>
			<div class="mo2f-step-setting-wrapper active" data-step-title="<?php esc_html_e( 'Inline Registration', 'miniorange-2-factor-authentication' ); ?>">
				<?php $this->mo2f_inline_registration(); ?>
				<div class="mo2f-setup-actions">
					<a class="button button-primary" style="margin-left:100px;" name="next_step_setting" onclick="mo2f_change_settings()" value="<?php esc_attr_e( 'Continue Setup', 'miniorange-2-factor-authentication' ); ?>"><?php esc_html_e( 'Continue Setup', 'miniorange-2-factor-authentication' ); ?></a>
					<a href="#skipwizard" class="mo2f_setup_wizard_footer_buttons" style="float:right;"><?php esc_html_e( 'Skip Setup', 'miniorange-2-factor-authentication' ); ?></a>
				</div>
			</div>
			<div class="mo2f-step-setting-wrapper" data-step-title="<?php esc_html_e( 'Choose User roles', 'miniorange-2-factor-authentication' ); ?>">
				<?php $this->mo2f_select_user_roles(); ?>
				<div class="mo2f-setup-actions">
					<a href="#inlinereg" onclick="mo2f_go_back_settings()">
						<span class="text-with-arrow text-with-arrow-left mo2f_setup_wizard_footer_buttons" style="float:left;">
							<svg viewBox="0 0 448 512" role="img" class="icon" data-icon="long-arrow-alt-left" data-prefix="far" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="12">
									<path xmlns="http://www.w3.org/2000/svg" fill="currentColor" d="M107.515 150.971L8.485 250c-4.686 4.686-4.686 12.284 0 16.971L107.515 366c7.56 7.56 20.485 2.206 20.485-8.485v-71.03h308c6.627 0 12-5.373 12-12v-32c0-6.627-5.373-12-12-12H128v-71.03c0-10.69-12.926-16.044-20.485-8.484z"></path>
							</svg> <?php esc_html_e( 'Go Back', 'miniorange-2-factor-authentication' ); ?>
						</span>
					</a>
					<a class="button button-primary" name="next_step_setting" onclick="mo2f_change_settings()" value="<?php esc_attr_e( 'Continue Setup', 'miniorange-2-factor-authentication' ); ?>"><?php esc_html_e( 'Continue Setup', 'miniorange-2-factor-authentication' ); ?></a>
					<a href="#skipwizard" class="mo2f_setup_wizard_footer_buttons" style="float:right;"><?php esc_html_e( 'Skip Setup', 'miniorange-2-factor-authentication' ); ?></a>
				</div>
			</div>

			<div class="mo2f-step-setting-wrapper" data-step-title="<?php esc_html_e( 'Grace period', 'miniorange-2-factor-authentication' ); ?>">
			<?php $this->mo2f_grace_period(); ?>
				<div class="mo2f-setup-actions">
					<a href="#chooseuserroles" onclick="mo2f_go_back_settings()">
						<span class="text-with-arrow text-with-arrow-left mo2f_setup_wizard_footer_buttons" style="float:left;">
							<svg viewBox="0 0 448 512" role="img" class="icon" data-icon="long-arrow-alt-left" data-prefix="far" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="12">
									<path xmlns="http://www.w3.org/2000/svg" fill="currentColor" d="M107.515 150.971L8.485 250c-4.686 4.686-4.686 12.284 0 16.971L107.515 366c7.56 7.56 20.485 2.206 20.485-8.485v-71.03h308c6.627 0 12-5.373 12-12v-32c0-6.627-5.373-12-12-12H128v-71.03c0-10.69-12.926-16.044-20.485-8.484z"></path>
							</svg> <?php esc_html_e( 'Go Back', 'miniorange-2-factor-authentication' ); ?>
						</span>
					</a>
					<button class="button button-primary save-wizard" type="submit" name="save_step" value="<?php esc_attr_e( 'All done', 'miniorange-2-factor-authentication' ); ?>"><?php esc_html_e( 'All Done', 'miniorange-2-factor-authentication' ); ?></button>
					<a href="#skipwizard" class="mo2f_setup_wizard_footer_buttons" style="float:right;"><?php esc_html_e( 'Skip Setup', 'miniorange-2-factor-authentication' ); ?></a>
				</div>
			</div>
			</form>
			<script>
				jQuery('a[href="#skipwizard"]').click(function() {
					localStorage.setItem("last_tab", 'setup_2fa');
					var nonce = "<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>";
					var skiptwofactorstage = 'Settings Configuration';
					var data = {
						'action': 'mo_two_factor_ajax',
						'mo_2f_two_factor_ajax': 'mo2f_skiptwofactor_wizard',
						'nonce': nonce,
						'twofactorskippedon': skiptwofactorstage,
					};
					var ajax_url = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
					jQuery.post(ajax_url, data, function(response) {
						window.location.href = "<?php echo esc_url( admin_url() . 'admin.php?page=mo_2fa_two_fa' ); ?>";
					});
				});
			</script>
			<?php
		}

		/**
		 * Inline registration UI in setup Wizard
		 *
		 * @return void
		 */
		public function mo2f_inline_registration() {
			?>
		<h3 id="mo2f_login_with_mfa_settings"><?php esc_html_e( 'Prompt users to setup 2FA after login? ', 'miniorange-2-factor-authentication' ); ?></h3>
		<p class="mo2f_description">
			<?php esc_html_e( 'When you enable this, the users will be prompted to set up the 2FA method after entering username and password. Users can select from the list of all 2FA methods. Once selected, user will setup and will login to the site ', 'miniorange-2-factor-authentication' ); ?><a href="<?php echo esc_url( MoWpnsConstants::MO2F_PLUGINS_PAGE_URL ) . '/setup-login-with-any-configured-method-wordpress-2fa'; ?>" target="_blank" rel=noopener><?php esc_html_e( 'Learn more.', 'miniorange-2-factor-authentication' ); ?></a>
		</p>
		<fieldset class="mo2f-contains-hidden-inputs">
			<label for="mo2f-use-inline-registration" style="margin-bottom: 10px; display: block;">
				<input type="radio" name="mo2f_policy[mo2f_inline_registration]" id="mo2f-use-inline-registration" value="1"
				<?php checked( get_site_option( 'mo2f_inline_registration' ), '1' ); ?>
				>
			<span><?php esc_html_e( 'Users should setup 2FA after first login.', 'miniorange-2-factor-authentication' ); ?></span>
			</label>
			<label for="mo2f-no-inline-registration">
				<input type="radio" name="mo2f_policy[mo2f_inline_registration]" id="mo2f-no-inline-registration" value="0"
				<?php checked( get_site_option( 'mo2f_inline_registration' ), '0' ); ?>
				>
				<span><?php esc_html_e( 'Users will setup 2FA in plugin dashboard', 'miniorange-2-factor-authentication' ); ?></span>
			</label>
		</fieldset>
			<?php
		}

		/**
		 * Select user roles settings
		 *
		 * @return void
		 */
		public function mo2f_select_user_roles() {
			?>
		<h3 id="mo2f_enforcement_settings"><?php esc_html_e( 'Do you want to enable 2FA for some, or all the user roles? ', 'miniorange-2-factor-authentication' ); ?></h3>
		<p class="mo2f_description">
			<?php esc_html_e( 'When you enable 2FA, the users will be prompted to configure 2FA the next time they login. Users have a grace period for configuring 2FA. You can configure the grace period and also exclude role(s) in this settings page. ', 'miniorange-2-factor-authentication' ); ?>
		</p>
		<fieldset class="mo2f-contains-hidden-inputs">
			<div onclick="mo2f_toggle_select_roles_and_users()">
				<label for="mo2f-all-users" style="margin:.35em 0 .5em !important; display: block;">
					<input type="radio" name="mo2f_policy[mo2f-enforcement-policy]" id="mo2f-all-users" value="mo2f-all-users"
					<?php checked( get_site_option( 'mo2f-enforcement-policy' ), 'mo2f-all-users' ); ?>
					>
					<span><?php esc_html_e( 'All users', 'miniorange-2-factor-authentication' ); ?></span>
				</label>
			</div>
			<div onclick="mo2f_toggle_select_roles_and_users()">
				<label for="mo2f-certain-roles-only" style="margin:.35em 0 .5em !important; display: block;">
					<?php $checked = in_array( get_site_option( 'mo2f-enforcement-policy' ), array( 'mo2f-certain-roles-only', 'certain-users-only' ), true ); ?>
					<input type="radio" name="mo2f_policy[mo2f-enforcement-policy]" id="mo2f-certain-roles-only" value="mo2f-certain-roles-only"
					data-unhide-when-checked=".mo2f-grace-period-inputs"
					<?php checked( get_site_option( 'mo2f-enforcement-policy' ), 'mo2f-certain-roles-only' ); ?>
					>
					<span><?php esc_html_e( 'Only for specific roles', 'miniorange-2-factor-authentication' ); ?></span>
				</label>
			</div>
			<div id='mo2f-show-certain-roles-only' style="display:none;">
				<fieldset class="hidden mo2f-certain-roles-only-inputs">
					<div class="mo2f-line-height">
						<?php $this->mo2f_display_user_roles(); ?>
					</div>
				</fieldset>
			</div>
		</fieldset>
			<?php
		}

		/**
		 * Display User roles settings
		 *
		 * @return void
		 */
		public function mo2f_display_user_roles() {
			global $wp_roles;
			if ( is_multisite() ) {
				$first_role           = array( 'superadmin' => 'Superadmin' );
				$wp_roles->role_names = array_merge( $first_role, $wp_roles->role_names );
			}
			?>
			<input type="button" class="button button-secondary" name="mo2f_select_all_roles" id="mo2f_select_all_roles" value="Select all"/>
			<?php
			foreach ( $wp_roles->role_names as $id => $name ) {
				$setting = get_site_option( 'mo2fa_' . $id );
				?>
				<div>
					<input type="checkbox" name="mo2f_policy[mo2f-enforce-roles][]" value="<?php echo 'mo2fa_' . esc_html( $id ); ?>"
					<?php
					if ( get_site_option( 'mo2fa_' . $id ) ) {
						echo 'checked';
					} else {
						echo 'unchecked';
					}
					?>
						/>
					<?php
					echo esc_html( $name );
					?>
				</div>
				<?php
			}
		}
		/**
		 * Save the setup wizard settings
		 *
		 * @return void
		 */
		private function mo2f_step_global_2fa_methods_save() {
			// Check nonce.
			check_admin_referer( 'mo2f-step-choose-method' );
			$array                       = isset( $_POST['mo2f_policy'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mo2f_policy'] ) ) : array();
			$array['mo2f-enforce-roles'] = isset( $_POST['mo2f_policy']['mo2f-enforce-roles'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mo2f_policy']['mo2f-enforce-roles'] ) ) : array();
			$this->mo2f_update_plugin_settings( $array );
			wp_safe_redirect( esc_url_raw( $this->mo2f_get_next_step() ) );
			exit();
		}
		/**
		 * Save the setup wizard settings in database
		 *
		 * @param array $settings Setup wizard settings that needs to be saved.
		 * @return void
		 */
		private function mo2f_update_plugin_settings( $settings ) {
			global $wp_roles;
			foreach ( $settings as $setting => $value ) {
				$setting = sanitize_text_field( $setting );
				$value   = sanitize_text_field( $value );

				if ( 'mo2f_grace_period_value' === $setting ) {
					update_site_option( $setting, ( $value <= 10 && $value > 0 ) ? floor( $value ) : 1 );
				} else {
					update_site_option( $setting, $value );
				}
			}
			if ( isset( $settings['mo2f-enforcement-policy'] ) && 'mo2f-all-users' === $settings['mo2f-enforcement-policy'] ) {
				if ( isset( $wp_roles ) ) {
					foreach ( $wp_roles->role_names as $role => $name ) {
						update_option( 'mo2fa_' . $role, 1 );
					}
				}
			} elseif ( isset( $settings['mo2f-enforcement-policy'] ) && 'mo2f-certain-roles-only' === $settings['mo2f-enforcement-policy'] && isset( $settings['mo2f-enforce-roles'] ) && is_array( $settings['mo2f-enforce-roles'] ) ) {
				foreach ( $wp_roles->role_names as $role => $name ) {
					if ( in_array( 'mo2fa_' . $role, $settings['mo2f-enforce-roles'], true ) ) {
						update_option( 'mo2fa_' . $role, 1 );
					} else {
						update_option( 'mo2fa_' . $role, 0 );
					}
				}
			}
		}
		/**
		 * Display Grace period settings
		 *
		 * @return void
		 */
		private function mo2f_grace_period() {
			$grace_period = get_site_option( 'mo2f_grace_period' );
			$testing      = apply_filters( 'mo2f_allow_grace_period_in_seconds', false );
			if ( $testing ) {
				$grace_max = 600;
			} else {
				$grace_max = 10;
			}
			?>
		<h3><?php esc_html_e( 'Should users be given a grace period or should they be directly enforced for 2FA setup?', 'miniorange-2-factor-authentication' ); ?></h3>
			<p class="mo2f_description"><?php esc_html_e( 'When you configure the 2FA policies and require users to configure 2FA, they can either have a grace period to configure 2FA (users who don\'t have 2fa setup after grace period, will be enforced to setup 2FA ). Choose which method you\'d like to use:', 'miniorange-2-factor-authentication' ); ?></p>
		<fieldset class="mo2f-contains-hidden-inputs">
			<div >
		<input type="radio" style="margin-bottom: 10px;" name="mo2f_policy[mo2f_grace_period]" id="mo2f-no-grace-period" value="off" <?php checked( get_site_option( 'mo2f_grace_period' ), 'off' ); ?>>
			<?php esc_html_e( 'Users should be directly enforced for 2FA setup.', 'miniorange-2-factor-authentication' ); ?>
			</div>
			<div style="display:inline-flex;">
				<div>
					<input type="radio" name="mo2f_policy[mo2f_grace_period]" id="mo2f-use-grace-period" value="on" <?php checked( get_site_option( 'mo2f_grace_period' ), 'on' ); ?> data-unhide-when-checked=".mo2f-grace-period-inputs">
				</div> 
				<div class="mo2f_setupwizard_grace_period">
					<p><?php esc_html_e( 'Give users a grace period to configure 2FA (Users will be enforced to setup 2FA after grace period expiry).', 'miniorange-2-factor-authentication' ); ?></p>
				</div>
			</div>
			<fieldset class="mo2f-grace-period-inputs" 
			<?php
			if ( get_site_option( 'mo2f_grace_period' ) ) {
				echo 'hidden';
			}
			?>
			hidden>
				<br/>
				<input type="number" id="mo2f-grace-period"  name="mo2f_policy[mo2f_grace_period_value]" value="<?php echo ( get_site_option( 'mo2f_grace_period_value' ) ) ? esc_attr( get_site_option( 'mo2f_grace_period_value' ) ) : 1; ?>" min="1" max="<?php echo esc_attr( $grace_max ); ?>">
				<label class="radio-inline">
					<input class="js-nested" type="radio" name="mo2f_policy[mo2f_grace_period_type]" value="hours"
					<?php checked( get_site_option( 'mo2f_grace_period_type' ), 'hours' ); ?>
					>
					<?php esc_html_e( 'hours', 'miniorange-2-factor-authentication' ); ?>
				</label>
				<label class="radio-inline">
					<input class="js-nested" type="radio" name="mo2f_policy[mo2f_grace_period_type]" value="days"
					<?php checked( get_site_option( 'mo2f_grace_period_type' ), 'days' ); ?>
					>
					<?php esc_html_e( 'days', 'miniorange-2-factor-authentication' ); ?>
				</label>
				<?php
				/**
				 * Via that, you can change the grace period TTL.
				 *
				 * @param bool - Default at this point is true - no method is selected.
				 */
				$testing = apply_filters( 'mo2f_allow_grace_period_in_seconds', false );
				if ( $testing ) {
					?>
					<label class="radio-inline">
						<input class="js-nested" type="radio" name="mo2f_policy[mo2f_grace_period_type]" value="seconds"
						<?php checked( get_site_option( 'mo2f_grace_period_type' ), 'seconds' ); ?>
						>
						<?php esc_html_e( 'Seconds', 'miniorange-2-factor-authentication' ); ?>
					</label>
					<?php
				}
				$user                         = wp_get_current_user();
				$last_user_to_update_settings = $user->ID;
				?>
				<input type="hidden" id="mo2f_main_user" name="mo2f_policy[2fa_settings_last_updated_by]" value="<?php echo esc_attr( $last_user_to_update_settings ); ?>">
			</fieldset>
			<br/>
		</fieldset>
		<script>
			jQuery(document).ready(function($){
				jQuery("#mo2f-use-grace-period").click(function()
				{
						jQuery("#mo2f-grace-period").focus();
				});
				jQuery(".radio-inline").click(function()
				{
						jQuery("#mo2f-grace-period").focus();
				});
			});
			</script>
			<?php
		}
	}
}
