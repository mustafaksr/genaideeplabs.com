<?php
/** This file contains functions regarding mobile login or passwordless login.
 *
 * @package miniorange-2-factor-authentication/handler/twofa
 */

namespace TwoFA\Handler;

use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use WP_Error;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */
require dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'mo2f-common-login-onprem-cloud.php';
require dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'mo2f-common-cloud-login.php';
require dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'mo2fa-inline-registration.php';

if ( ! class_exists( 'Miniorange_Mobile_Login' ) ) {
	/**
	 * Mobile Login class
	 */
	class Miniorange_Mobile_Login {


		/**
		 * This is function is to create the session.
		 */
		public function miniorange_login_start_session() {
			if ( ! session_id() || '' === session_id() || ! isset( $_SESSION ) ) {
				session_start();
			}
		}
		/**
		 * This function displays the error messages.
		 *
		 * @param string $value carry a value.
		 * @return void
		 */
		public function mo2f_auth_show_error_message( $value = null ) {

			remove_filter( 'login_message', array( $this, 'mo_auth_success_message' ) );
			add_filter( 'login_message', array( $this, 'mo_auth_error_message' ) );
		}

		/**
		 * This function is useful for removing the current activity stoed in sessionand cookie.
		 *
		 * @param string $session_id - Encrypted session id.
		 * @return void
		 */
		public function remove_current_activity( $session_id ) {

			$session_variables = array(
				'mo2f_current_user_id',
				'mo2f_1stfactor_status',
				'mo_2factor_login_status',
				'mo2f-login-qrCode',
				'mo2f_transactionId',
				'mo2f_login_message',
				'mo_2_factor_kba_questions',
				'mo2f_show_qr_code',
				'mo2f_google_auth',
				'mo2f_authy_keys',
			);

			$cookie_variables = array(
				'mo2f_current_user_id',
				'mo2f_1stfactor_status',
				'mo_2factor_login_status',
				'mo2f-login-qrCode',
				'mo2f_transactionId',
				'mo2f_login_message',
				'kba_question1',
				'kba_question2',
				'mo2f_show_qr_code',
				'mo2f_google_auth',
				'mo2f_authy_keys',
			);

			MO2f_Utility::unset_session_variables( $session_variables );
			MO2f_Utility::unset_cookie_variables( $cookie_variables );
			MO2f_Utility::unset_temp_user_details_in_table( null, $session_id, 'destroy' );
		}
		/**
		 * This function enqueues custom login script.
		 *
		 * @return void
		 */
		public function custom_login_enqueue_scripts() {

			wp_enqueue_script( 'jquery' );
			$bootstrappath = plugins_url( 'includes/css/bootstrap.min.css', dirname( dirname( __FILE__ ) ) );
			$bootstrappath = str_replace( '/handler/includes/css', '/includes/css', $bootstrappath );
			wp_enqueue_style( 'bootstrap_script', $bootstrappath, array(), MO2F_VERSION );
			wp_enqueue_script( 'bootstrap_script', plugins_url( 'includes/js/bootstrap.min.js', dirname( dirname( __FILE__ ) ) ), array(), MO2F_VERSION, false );
		}
		/**
		 * This function is useful for hide login form.
		 *
		 * @return void
		 */
		public function mo_2_factor_hide_login() {

			$bootstrappath = plugins_url( 'includes/css/bootstrap.min.css', dirname( dirname( __FILE__ ) ) );
			$bootstrappath = str_replace( '/handler/includes/css', '/includes/css', $bootstrappath );
			$hidepath      = plugins_url( 'includes/css/hide-login-form.min.css', dirname( dirname( __FILE__ ) ) );
			$hidepath      = str_replace( '/handler/includes/css', '/includes/css', $hidepath );

			wp_register_style( 'hide-login', $hidepath, array(), MO2F_VERSION );
			wp_register_style( 'bootstrap', $bootstrappath, array(), MO2F_VERSION );
			wp_enqueue_style( 'hide-login' );
			wp_enqueue_style( 'bootstrap' );
		}
		/**
		 * This function displays the success messages.
		 *
		 * @return string
		 */
		public function mo_auth_success_message() {

			$message    = isset( $_SESSION['mo2f_login_message'] ) ? $_SESSION['mo2f_login_message'] : '';
			$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Missing -- It is on default WordPress login form.
			$message    = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_login_message', $session_id );

			if ( ! empty( $message ) ) { // if the php session folder has insufficient permissions, cookies to be used.
				$message = 'Please login into your account using password.';
			}

			return "<div> <p class='message'>" . $message . '</p></div>';
		}
		/**
		 * This function displaky error message
		 *
		 * @return string
		 */
		public function mo_auth_error_message() {

			$id         = 'login_error1';
			$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Missing -- It is on default WordPress login form.
			// if the php session folder has insufficient permissions, cookies to be used.
			$message = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_login_message', $session_id );
			if ( ! empty( $message ) ) {
				$message = 'Invalid Username';
			}
			if ( get_option( 'mo_wpns_activate_recaptcha_for_login' ) ) { // test.
				$message = 'Invalid Username or recaptcha';
			}
			return "<div id='" . $id . "'> <p>" . $message . '</p></div>';
		}
		/**
		 * This function is use to show the success message.
		 *
		 * @return void
		 */
		public function mo2f_auth_show_success_message() {

			remove_filter( 'login_message', array( $this, 'mo_auth_error_message' ) );
			add_filter( 'login_message', array( $this, 'mo_auth_success_message' ) );
		}
		/**
		 * This function is use to show the login form fields
		 *
		 * @param string $mo2fa_login_status It will caryy the login status.
		 * @param string $mo2fa_login_message It will caryy the login message.
		 * @return void
		 */
		public function miniorange_login_form_fields( $mo2fa_login_status = null, $mo2fa_login_message = null ) {

			global $mo2fdb_queries;
			$session_id_encrypt    = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
			$pass2fa_login_session = new Miniorange_Password_2Factor_Login();

			if ( is_null( $session_id_encrypt ) ) {
				$session_id_encrypt = $pass2fa_login_session->create_session();
			}

			if ( get_option( 'mo2f_enable_login_with_2nd_factor' ) ) { // login with phone overwrite default login form
				// if the php session folder has insufficient permissions, cookies to be used.
				$login_status_phone_enable = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo_2factor_login_status', $session_id_encrypt );

				if ( MO2F_IS_ONPREM ) {
					$user_name = isset( $_POST['mo2fa_username'] ) ? sanitize_user( wp_unslash( $_POST['mo2fa_username'] ) ) : '';

					if ( ! empty( $user_name ) ) {
						$user = get_user_by( 'login', $user_name );
						if ( $user ) {
							$current_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
							if ( 'None' === $current_method || empty( $current_method ) ) {
								$login_status_phone_enable = 'MO_2_FACTOR_LOGIN_WHEN_PHONELOGIN_ENABLED';
							}
						}
					}
				}
				if ( 'MO_2_FACTOR_LOGIN_WHEN_PHONELOGIN_ENABLED' === $login_status_phone_enable && isset( $_POST['miniorange_login_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['miniorange_login_nonce'] ) ), 'miniorange-2-factor-login-nonce' ) ) {
					$this->mo_2_factor_show_login_with_password_when_phonelogin_enabled();
					$this->mo_2_factor_show_wp_login_form_when_phonelogin_enabled();
					$user            = isset( $_SESSION['mo2f_current_user'] ) ? maybe_serialize( sanitize_text_field( $_SESSION['mo2f_current_user'] ) ) : null;
					$mo2f_user_login = is_null( $user ) && ! is_object( $user ) ? null : $user->user_login;
					?>
				<script>
					jQuery('#user_login').val(<?php echo "'" . esc_js( $mo2f_user_login ) . "'"; ?>);
				</script>
					<?php
				} else {
					$this->mo_2_factor_show_login();
					$this->mo_2_factor_show_wp_login_form();
				}
			} else { // Login with phone is alogin with default login form.
				$this->mo_2_factor_show_login();
				$this->mo_2_factor_show_wp_login_form();
			}
		}
		/**
		 * This function login with password when phonelogin enabled.
		 *
		 * @return void
		 */
		public function mo_2_factor_show_login_with_password_when_phonelogin_enabled() {

			wp_register_style( 'show-login', plugins_url( 'includes/css/show-login.min.css', dirname( dirname( __FILE__ ) ) ), array(), MO2F_VERSION );
			wp_enqueue_style( 'show-login' );
		}
		/**
		 * This function is useful for login form fields
		 *
		 * @return void
		 */
		public function mo_2_factor_show_wp_login_form_when_phonelogin_enabled() {
			?>
		<script>
			var content = ' <a href="javascript:void(0)" id="backto_mo" onClick="mo2fa_backtomologin()" style="float:right">← Back</a>';
			jQuery('#login').append(content);

			function mo2fa_backtomologin() {
				jQuery('#mo2f_backto_mo_loginform').submit();
			}
		</script>
			<?php
		}
		/**
		 * This function show login.
		 *
		 * @return void
		 */
		public function mo_2_factor_show_login() {

			$hidepath = plugins_url( 'includes/css/hide-login-form.min.css', dirname( dirname( __FILE__ ) ) );

			$showpath = plugins_url( 'includes/css/show-login.min.css', dirname( dirname( __FILE__ ) ) );

			if ( get_option( 'mo2f_enable_login_with_2nd_factor' ) ) {
				wp_register_style( 'show-login', $hidepath, array(), MO2F_VERSION );
			} else {
				wp_register_style( 'show-login', $showpath, array(), MO2F_VERSION );
			}
			wp_enqueue_style( 'show-login' );
		}
		/**
		 * This function handle wp login.
		 *
		 * @return void
		 */
		public function mo_2_factor_show_wp_login_form() {

			$mo2f_enable_login_with_2nd_factor = get_option( 'mo2f_enable_login_with_2nd_factor' );

			?>
		<div class="mo2f-login-container">
			<?php if ( ! $mo2f_enable_login_with_2nd_factor ) { ?>
				<div style="position: relative" class="or-container">
					<div class="login_with_2factor_inner_div"></div>
					<h2 class="login_with_2factor_h2"><?php esc_html_e( 'or' ); ?></h2>
				</div>
			<?php } ?>			
			<br>
			<div class="mo2f-button-container" id="mo2f_button_container">
				<input type="text" name="mo2fa_usernamekey" id="mo2fa_usernamekey" autofocus="true"
				placeholder="<?php esc_attr_e( 'Username' ); ?>"/>
				<p>			
					<input type="button" name="miniorange_login_submit" style="width:100% !important;"
						onclick="mouserloginsubmit();" id="miniorange_login_submit"
						class="button button-primary button-large"
						value="<?php esc_attr_e( 'Login with 2nd factor' ); ?>"/>
				</p>
				<br><br><br>
				<?php
				if ( ! $mo2f_enable_login_with_2nd_factor ) {
					?>
					<br><br><?php } ?>
			</div>
		</div>

		<script>
			jQuery(window).scrollTop(jQuery('#mo2f_button_container').offset().top);

			function mouserloginsubmit() {
				var username = jQuery('#mo2fa_usernamekey').val();
				var recap    = jQuery('#g-recaptcha-response').val();
				if(document.getElementById("mo2fa-g-recaptcha-response-form") !== null){
				document.getElementById("mo2fa-g-recaptcha-response-form").elements[0].value = username;
				document.getElementById("mo2fa-g-recaptcha-response-form").elements[1].value = recap;			
				jQuery('#mo2fa-g-recaptcha-response-form').submit();
				}
			}

			jQuery('#mo2fa_usernamekey').keypress(function (e) {
				if (e.which == 13) {//Enter key pressed
					e.preventDefault();
					var username = jQuery('#mo2fa_usernamekey').val();
					if(document.getElementById("mo2fa-g-recaptcha-response-form") !== null){
						document.getElementById("mo2fa-g-recaptcha-response-form").elements[0].value = username;
						jQuery('#mo2fa-g-recaptcha-response-form').submit();
					}
				}

			});
		</script>
			<?php
		}
		/**
		 * This function have login footer
		 *
		 * @return void
		 */
		public function miniorange_login_footer_form() {

			$session_id_encrypt    = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Added in Login footer form.
			$pass2fa_login_session = new Miniorange_Password_2Factor_Login();
			if ( is_null( $session_id_encrypt ) ) {
				$session_id_encrypt = $pass2fa_login_session->create_session();
			}

			?>
		<input type="hidden" name="miniorange_login_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-login-nonce' ) ); ?>"/>
		<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" hidden>
			<input type="hidden" name="miniorange_mobile_validation_failed_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>"/>
				<input type="hidden" id="sessids" name="session_id"
				value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
		</form>
		<form name="f" id="mo2fa-g-recaptcha-response-form" method="post" action="" hidden>
			<input type="text" name="mo2fa_username" id="mo2fa_username" hidden/>
			<input type="text" name="g-recaptcha-response" id = 'g-recaptcha-response' hidden/>
			<input type="hidden" name="miniorange_login_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-login-nonce' ) ); ?>"/>
				<input type="hidden" id="sessid" name="session_id"
				value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
		</form>
		<script>
		jQuery(document).ready(function () {
			var session_ids="<?php echo esc_js( $session_id_encrypt ); ?>";
				if (document.getElementById('loginform') != null) {
					jQuery("#user_pass").after( "<input type='hidden' id='sessid' name='session_id' value='"+session_ids+"'/>");
					jQuery(".wp-hide-pw").addClass('mo2fa_visible');			   
				}
		});
		</script>
			<?php

		}
	}
}
?>
