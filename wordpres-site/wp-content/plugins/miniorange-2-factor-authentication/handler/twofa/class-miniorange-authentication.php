<?php
/**
 * This file contains Create, read, update and delete user operations on miniOrange idp.
 *
 * @package miniorange-2-factor-authentication/handler/twofa
 */

namespace TwoFA\Onprem;

use TwoFA\Cloud\Customer_Cloud_Setup;
use TwoFA\Onprem\MO2f_Cloud_Onprem_Interface;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Traits\Instance;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Handler\Miniorange_Mobile_Login;
use TwoFA\Database\Mo2fDB;
use TwoFA\Helper\MocURL;
use WP_Error;
use TwoFA\Cloud\Two_Factor_Setup;
use TwoFA\Cloud\Mo2f_Cloud_Utility;
use TwoFA\Helper\MoWpnsMessages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Miniorange_Password_2factor_Login.
 */
require 'class-miniorange-password-2factor-login.php';

/**
 * Including two-fa-setup-notification.php.
 */
require dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-setup-notification.php';

if ( ! class_exists( 'Miniorange_Authentication' ) ) {
	/**
	 * Class Miniorange_Authentication.
	 */
	class Miniorange_Authentication {

		use Instance;

		/**
		 * Default customer key
		 *
		 * @var string
		 */
		private $default_customer_key = '16555';

		/**
		 * Default api key
		 *
		 * @var string
		 */
		private $default_api_key = 'fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq';

		/**
		 * Class Mo2f_Cloud_Onprem_Interface object
		 *
		 * @var object
		 */
		private $mo2f_onprem_cloud_obj;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->mo2f_onprem_cloud_obj = MO2f_Cloud_Onprem_Interface::instance();
			add_action( 'admin_init', array( $this, 'mo2f_auth_save_settings' ) );
			add_action( 'plugins_loaded', array( $this, 'mo2f_update_db_check' ) );

			if ( (int) ( MoWpnsUtility::get_mo2f_db_option( 'mo2f_activate_plugin', 'get_option' ) ) === 1 ) {
				$pass2fa_login = new Miniorange_Password_2Factor_Login();
				add_action( 'init', array( $pass2fa_login, 'miniorange_pass2login_redirect' ) );
				// for shortcode addon.
				add_action( 'login_form', array( $pass2fa_login, 'mo_2_factor_pass2login_show_wp_login_form' ), 10 );
				add_filter( 'mo2f_shortcode_rba_gauth', array( $this->mo2f_onprem_cloud_obj, 'mo2f_validate_google_auth' ), 10, 3 );
				add_filter( 'mo2f_shortcode_kba', array( $this->mo2f_onprem_cloud_obj, 'mo2f_register_kba_details' ), 10, 7 );
				add_filter( 'mo2f_update_info', array( $this->mo2f_onprem_cloud_obj, 'mo2f_update_user_info' ), 10, 5 );
				add_action(
					'mo2f_shortcode_form_fields',
					array(
						$pass2fa_login,
						'miniorange_pass2login_form_fields',
					),
					10,
					5
				);

				add_action( 'delete_user', array( $this, 'mo2f_delete_user' ) );

				add_filter( 'mo2f_gauth_service', array( $this->mo2f_onprem_cloud_obj, 'mo2f_google_auth_service' ), 10, 1 );

				if ( get_option( 'mo_2factor_admin_registration_status' ) === 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' || MO2F_IS_ONPREM ) {
					remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );

					add_filter( 'authenticate', array( $pass2fa_login, 'mo2f_check_username_password' ), 99999, 4 );
					add_action( 'init', array( $pass2fa_login, 'miniorange_pass2login_redirect' ) );
					add_action(
						'login_form',
						array(
							$pass2fa_login,
							'mo_2_factor_pass2login_show_wp_login_form',
						),
						10
					);

					add_action(
						'login_enqueue_scripts',
						array(
							$pass2fa_login,
							'mo_2_factor_enable_jquery_default_login',
						)
					);

					if ( get_site_option( 'mo2f_woocommerce_login_prompt' ) ) {
						add_action(
							'woocommerce_login_form',
							array(
								$pass2fa_login,
								'mo_2_factor_pass2login_show_wp_login_form',
							)
						);
					}
					add_action(
						'wp_enqueue_scripts',
						array(
							$pass2fa_login,
							'mo_2_factor_enable_jquery_default_login',
						)
					);

					// Actions for other plugins to use miniOrange 2FA plugin.
					add_action(
						'miniorange_pre_authenticate_user_login',
						array(
							$pass2fa_login,
							'mo2f_check_username_password',
						),
						1,
						4
					);
					add_action(
						'miniorange_post_authenticate_user_login',
						array(
							$pass2fa_login,
							'miniorange_initiate_2nd_factor',
						),
						1,
						3
					);
					add_action(
						'miniorange_collect_attributes_for_authenticated_user',
						array(
							$pass2fa_login,
							'mo2f_collect_device_attributes_for_authenticated_user',
						),
						1,
						2
					);
				}
			}
		}


		/**
		 * Define globals.
		 *
		 * @return void
		 */
		public function mo2f_define_global() {

			global $mo2fdb_queries;
			$mo2fdb_queries = new Mo2fDB();
		}
		/**
		 * Delete user.
		 *
		 * @param int $user_id User id.
		 *
		 * @return void
		 */
		public function mo2f_delete_user( $user_id ) {

			global $mo2fdb_queries;
			delete_user_meta( $user_id, 'mo2f_kba_challenge' );
			delete_user_meta( $user_id, 'mo2f_2FA_method_to_configure' );
			delete_user_meta( $user_id, MoWpnsConstants::SECURITY_QUESTIONS );
			delete_user_meta( $user_id, 'mo2f_chat_id' );
			$mo2fdb_queries->delete_user_details( $user_id );
			delete_user_meta( $user_id, 'mo2f_2FA_method_to_test' );
			delete_option( 'mo2f_grace_period_status_' . $user_id );
		}

		/**
		 * Update database check.
		 */
		public function mo2f_update_db_check() {

			$userid = wp_get_current_user()->ID;
			add_option( 'mo2f_onprem_admin', $userid );
			if ( is_multisite() ) {
				add_site_option( 'mo2fa_superadmin', 1 );
			}
			if ( get_option( 'mo2f_network_features', 'not_exits' ) === 'not_exits' ) {
				do_action( 'mo2f_network_create_db' );
				update_option( 'mo2f_network_features', 1 );
			}
			if ( get_option( 'mo2f_encryption_key', 'not_exits' ) === 'not_exits' ) {
				$get_encryption_key = MO2f_Utility::random_str( 16 );
				update_option( 'mo2f_encryption_key', $get_encryption_key );
			}
			global $mo2fdb_queries;
			$user_id            = get_option( 'mo2f_miniorange_admin' );
			$current_db_version = get_option( 'mo2f_dbversion' );

			if ( $current_db_version < MoWpnsConstants::DB_VERSION ) {
				update_option( 'mo2f_dbversion', MoWpnsConstants::DB_VERSION );
				$mo2fdb_queries->generate_tables();
			}
			if ( MO2F_IS_ONPREM ) {
				$twofactordb = new Mo2fDB();
				$user_sync   = get_site_option( 'mo2f_user_sync' );
				if ( $user_sync < 1 ) {
					update_site_option( 'mo2f_user_sync', 1 );
					$twofactordb->get_all_onprem_userids();
				}
			}

			if ( $user_id && ! get_option( 'mo2f_login_option_updated' ) ) {
				$does_table_exist = $mo2fdb_queries->check_if_table_exists();
				if ( $does_table_exist ) {
					$check_if_user_column_exists = $mo2fdb_queries->check_if_user_column_exists( $user_id );
					if ( $check_if_user_column_exists ) {
						$selected_2_f_a_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user_id );

						update_option( 'mo2f_login_option_updated', 1 );
					}
				}
			}
		}

		/**
		 * Save settings on miniOrange authetication.
		 */
		public function mo2f_auth_save_settings() {
			if ( array_key_exists( 'page', $_REQUEST ) && sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) === 'mo_2fa_two_fa' ) {
				if ( ! session_id() || session_id() === '' || ! isset( $_SESSION ) ) {
					if ( session_status() !== PHP_SESSION_DISABLED ) {
						session_start();
					}
				}
			}

			global $user;
			global $mo2fdb_queries;
			$default_customer_key = $this->default_customer_key;
			$default_api_key      = $this->default_api_key;
			$show_message         = new MoWpnsMessages();

			$user    = wp_get_current_user();
			$user_id = $user->ID;

			if ( current_user_can( 'manage_options' ) ) {
				if ( strlen( get_option( 'mo2f_encryption_key' ) ) > 17 ) {
					$get_encryption_key = MO2f_Utility::random_str( 16 );
					update_option( 'mo2f_encryption_key', $get_encryption_key );
				}

				if ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo_auth_deactivate_account' ) {
					$nonce = isset( $_POST['mo_auth_deactivate_account_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_auth_deactivate_account_nonce'] ) ) : null;
					if ( ! wp_verify_nonce( $nonce, 'mo-auth-deactivate-account-nonce' ) ) {
						$error = new WP_Error();
						$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );

						return $error;
					} else {
						$url = admin_url( 'plugins.php' );
						wp_safe_redirect( $url );
						exit();
					}
				} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo_auth_remove_account' ) {
					$nonce = isset( $_POST['mo_auth_remove_account_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_auth_remove_account_nonce'] ) ) : null;
					if ( ! wp_verify_nonce( $nonce, 'mo-auth-remove-account-nonce' ) ) {
						$error = new WP_Error();
						$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
						return $error;
					} else {
						update_option( 'mo2f_register_with_another_email', 1 );
						$this->mo2f_auth_deactivate();
					}
				} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_skiplogin' ) {
					$nonce = isset( $_POST['mo2f_skiplogin_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_skiplogin_nonce'] ) ) : null;
					if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-skiplogin-failed-nonce' ) ) {
						$error = new WP_Error();
						$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
						return $error;
					} else {
						update_option( 'mo2f_tour_started', 2 );
					}
				} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_userlogout' ) {
					$nonce = isset( $_POST['mo2f_userlogout_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_userlogout_nonce'] ) ) : null;
					if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-userlogout-failed-nonce' ) ) {
						$error = new WP_Error();
						$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
						return $error;
					} else {
						update_option( 'mo2f_tour_started', 2 );
						wp_logout();
						wp_safe_redirect( admin_url() );
						exit();
					}
				} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo_2factor_resend_otp' ) { // resend OTP over email for admin.
					$nonce = isset( $_POST['mo_2factor_resend_otp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_2factor_resend_otp_nonce'] ) ) : null;
					if ( ! wp_verify_nonce( $nonce, 'mo-2factor-resend-otp-nonce' ) ) {
						$error = new WP_Error();
						$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );

						return $error;
					} else {
						$content = json_decode( $this->mo2f_onprem_cloud_obj->send_otp_token( get_option( 'mo2f_email' ), MoWpnsConstants::OTP_OVER_EMAIL, $default_customer_key, $default_api_key ), true );
						if ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) {
							if ( get_user_meta( $user->ID, 'mo2f_email_otp_count', true ) ) {
								update_user_meta( $user->ID, 'mo2f_email_otp_count', get_user_meta( $user->ID, 'mo2f_email_otp_count', true ) + 1 );
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::RESENT_OTP ) . ' <b>( ' . get_user_meta( $user->ID, 'mo2f_email_otp_count', true ) . ' )</b> to <b>' . ( get_option( 'mo2f_email' ) ) . '</b> ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ), 'SUCCESS' );
							} else {
								update_user_meta( $user->ID, 'mo2f_email_otp_count', 1 );
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::OTP_SENT ) . '<b> ' . ( get_option( 'mo2f_email' ) ) . ' </b>' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ), 'SUCCESS' );
							}
							$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_SUCCESS';
							$mo2fdb_queries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
							update_user_meta( $user->ID, 'mo_2fa_verify_otp_create_account', $content['txId'] );
						} else {
							$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_FAILURE';
							$mo2fdb_queries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_IN_SENDING_EMAIL ), 'ERROR' );
						}
					}
				} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_dismiss_notice_option' ) {
					update_option( 'mo2f_bug_fix_done', 1 );
				} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'woocommerce_disable_login_prompt' ) {
					if ( isset( $_POST['woocommerce_login_prompt'] ) ) {
						update_site_option( 'mo2f_woocommerce_login_prompt', true );
					} else {
						update_site_option( 'mo2f_woocommerce_login_prompt', false );
					}
				}
			}
			if ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_registration_closed' ) {
				$nonce = isset( $_POST['mo2f_registration_closed_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_registration_closed_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo2f-registration-closed-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					delete_user_meta( $user->ID, 'register_account_popup' );
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SETUP_2FA ), 'SUCCESS' );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_goto_verifycustomer' ) {
				$nonce = isset( $_POST['mo2f_general_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_general_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'miniOrange_2fa_nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					$mo2fdb_queries->insert_user( $user_id, array( 'user_id' => $user_id ) );
					update_option( 'mo_2factor_user_registration_status', 'MO_2_FACTOR_VERIFY_CUSTOMER' );
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_YOUR_EMAIL_PASSWORD ), 'SUCCESS' );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo_2factor_gobackto_registration_page' ) { // back to registration page for admin.
				$nonce = isset( $_POST['mo_2factor_gobackto_registration_page_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_2factor_gobackto_registration_page_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo-2factor-gobackto-registration-page-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					delete_option( 'mo2f_email' );
					delete_option( 'mo2f_password' );
					update_option( 'mo2f_message', '' );

					MO2f_Utility::unset_session_variables( 'mo2f_transactionId' );
					delete_option( 'mo2f_transactionId' );
					delete_user_meta( $user->ID, 'mo2f_sms_otp_count' );
					delete_user_meta( $user->ID, 'mo2f_email_otp_count' );
					delete_user_meta( $user->ID, 'mo2f_email_otp_count' );
					$mo2fdb_queries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => 'REGISTRATION_STARTED' ) );
				}
			} elseif ( isset( $_POST['option'] ) && ( sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_configure_miniorange_authenticator_validate' || sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo_auth_mobile_reconfiguration_complete' ) ) { // mobile registration successfully complete for all users.
				delete_option( 'mo2f_transactionId' );
				$session_variables = array( 'mo2f_qrCode', 'mo2f_transactionId', 'mo2f_show_qr_code' );
				MO2f_Utility::unset_session_variables( $session_variables );

				$email                       = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
				$two_f_a_method_to_configure = isset( $_POST['mo2f_method'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_method'] ) ) : '';
				$response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user->ID, true, $two_f_a_method_to_configure, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, null ), true );

				if ( json_last_error() === JSON_ERROR_NONE ) { /* Generate Qr code */
					if ( 'ERROR' === $response['status'] ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $response['message'] ), 'ERROR' );
					} elseif ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
						delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );
						delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
						mo2f_display_test_2fa_notification( $user );
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
					}
				} else {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ), 'ERROR' );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_mobile_authenticate_success' ) { // mobile registration for all users(common).
				$session_variables = array( 'mo2f_qrCode', 'mo2f_transactionId', 'mo2f_show_qr_code' );
				MO2f_Utility::unset_session_variables( $session_variables );
				delete_user_meta( $user->ID, 'test_2FA' );
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::COMPLETED_TEST ), 'SUCCESS' );
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_mobile_authenticate_error' ) { // mobile registration failed for all users(common).
				$nonce = isset( $_POST['mo2f_mobile_authenticate_error_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_mobile_authenticate_error_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo2f-mobile-authenticate-error-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					MO2f_Utility::unset_session_variables( 'mo2f_show_qr_code' );
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::AUTHENTICATION_FAILED ), 'ERROR' );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo_auth_setting_configuration' ) {
				$mo2fdb_queries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS ) );
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo_auth_refresh_mobile_qrcode' ) { // refrsh Qrcode for all users.
				$session_id             = isset( $_POST['mo2f_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_session_id'] ) ) : null;
				$twofactor_transactions = new Mo2fDB();
				$exceeded               = $twofactor_transactions->check_alluser_limit_exceeded( $user_id );
				if ( $exceeded ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::USER_LIMIT_EXCEEDED ), 'ERROR' );
					return;
				}
				$mo_2factor_user_registration_status = get_option( 'mo_2factor_user_registration_status' );
				if ( in_array(
					$mo_2factor_user_registration_status,
					array(
						'MO_2_FACTOR_INITIALIZE_TWO_FACTOR',
						'MO_2_FACTOR_INITIALIZE_MOBILE_REGISTRATION',
						'MO_2_FACTOR_PLUGIN_SETTINGS',
					),
					true
				) ) {
					$email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
					$this->mo2f_get_qr_code_for_mobile( $email, $user->ID, $session_id );
				} else {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::REGISTER_WITH_MO ), 'ERROR' );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo_2factor_backto_user_registration' ) { // back to registration page for additional admin and non-admin.
				delete_user_meta( $user->ID, 'user_email' );
				$mo2fdb_queries->delete_user_details( $user->ID );
				MO2f_Utility::unset_session_variables( 'mo2f_transactionId' );
				delete_option( 'mo2f_transactionId' );
			} elseif ( isset( $_POST['option'] ) && 'mo2f_validate_soft_token' === $_POST['option'] ) {  // validate Soft Token during test for all users.
				$otp_token  = '';
				$otp_token1 = isset( $_POST['otp_token'] ) ? sanitize_text_field( wp_unslash( $_POST['otp_token'] ) ) : '';
				if ( MO2f_Utility::mo2f_check_empty_or_null( $otp_token1 ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_VALUE ), 'ERROR' );
					return;
				} else {
					$otp_token = sanitize_text_field( wp_unslash( $_POST['otp_token'] ) );
				}
				$email    = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
				$customer = new MocURL();
				$content  = json_decode( $customer->miniorange_authenticator_validate( MoWpnsConstants::SOFT_TOKEN, $email, $otp_token, get_option( 'mo2f_customerKey' ) ), true );
				if ( 'ERROR' === $content['status'] ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $content['message'] ), 'ERROR' );
				} else {
					if ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) { // OTP validated and generate QRCode.
						delete_user_meta( $user->ID, 'test_2FA' );
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::COMPLETED_TEST ), 'SUCCESS' );
					} else {  // OTP Validation failed.
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ), 'ERROR' );
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_validate_otp_over_Telegram' ) { // validate otp over Telegram.
				$nonce = isset( $_POST['mo2f_test_validate_otp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_test_validate_otp_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-validate-otp-over-Telegram-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					$otp       = isset( $_POST['otp_token'] ) ? sanitize_text_field( wp_unslash( $_POST['otp_token'] ) ) : '';
					$otp_token = get_user_meta( $user->ID, 'mo2f_otp_token', true );

					$time          = get_user_meta( $user->ID, 'mo2f_telegram_time', true );
					$accepted_time = time() - 300;
					$time          = (int) $time;
					global $mo2fdb_queries;
					if ( (int) ( $otp_token ) === (int) $otp ) {
						if ( $accepted_time < $time ) {
							delete_user_meta( $user->ID, 'test_2FA' );
							delete_user_meta( $user->ID, 'mo2f_telegram_time' );
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::COMPLETED_TEST ), 'SUCCESS' );
						} else {
							delete_user_meta( $user->ID, 'test_2FA' );
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::OTP_EXPIRED ), 'ERROR' );
						}
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ), 'ERROR' );
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_validate_otp_over_sms' ) { // validate otp over sms and phone call during test for all users.
				$nonce = isset( $_POST['mo2f_test_validate_otp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_test_validate_otp_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-validate-otp-over-sms-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					$otp_token  = '';
					$otp_token1 = isset( $_POST['otp_token'] ) ? sanitize_text_field( wp_unslash( $_POST['otp_token'] ) ) : '';
					if ( MO2f_Utility::mo2f_check_empty_or_null( $otp_token1 ) ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_VALUE ), 'ERROR' );
						return;
					} else {
						$otp_token = isset( $_POST['otp_token'] ) ? sanitize_text_field( wp_unslash( $_POST['otp_token'] ) ) : '';
					}
					$mo2f_transaction_id       = get_user_meta( $user->ID, 'mo2f_transactionId', true );
					$email                     = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
					$selected_2_2factor_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
					$content                   = json_decode( $this->mo2f_onprem_cloud_obj->validate_otp_token( $selected_2_2factor_method, $email, $mo2f_transaction_id, $otp_token, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ) ), true );

					if ( 'ERROR' === $content['status'] ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $content['message'] ), 'ERROR' );
					} else {
						if ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) { // OTP validated.
							delete_user_meta( $user->ID, 'test_2FA' );
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::COMPLETED_TEST ), 'SUCCESS' );
						} else {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ), 'ERROR' );
						}
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_out_of_band_success' ) {
				$nonce = isset( $_POST['mo2f_out_of_band_success_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_out_of_band_success_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-out-of-band-success-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					if ( MO2F_IS_ONPREM ) {
						$txid   = isset( $_POST['TxidEmail'] ) ? sanitize_text_field( wp_unslash( $_POST['TxidEmail'] ) ) : null;
						$status = get_site_option( $txid );
						if ( ! empty( $status ) ) {
							if ( 1 !== (int) $status ) {
								delete_user_meta( $user->ID, 'test_2FA' );
								delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_EMAIL_VER_REQ ), 'ERROR' );
								return;
							}
						}
					}
					$mo2f_configured_2_f_a_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
					if ( MO2F_IS_ONPREM && isset( $_POST['TxidEmail'] ) ) {
						$mo2f_configured_2_f_a_method = MoWpnsConstants::OUT_OF_BAND_EMAIL;
					}
					$mo2f_email_verification_config_status = $mo2fdb_queries->get_user_detail( 'mo2f_EmailVerification_config_status', $user->ID );
					if ( ! current_user_can( 'manage_options' ) && MoWpnsConstants::OUT_OF_BAND_EMAIL === $mo2f_configured_2_f_a_method ) {

						if ( $mo2f_email_verification_config_status ) {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::COMPLETED_TEST ), 'SUCCESS' );
						} else {
							$email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
							$this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user->ID, true, $mo2f_configured_2_f_a_method, null, null, null, $email );
							$show_message->mo2f_show_message( '<b> ' . MoWpnsMessages::lang_translate( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OUT_OF_BAND_EMAIL, 'cap_to_small' ) ) . '</b> ' . MoWpnsMessages::lang_translate( MoWpnsMessages::SET_AS_2ND_FACTOR ), 'SUCCESS' );
						}
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::COMPLETED_TEST ), 'SUCCESS' );
					}
					$email      = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
					$temp_email = $email ? $email : get_user_meta( $user->ID, 'tempEmail', true );
					delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
					delete_user_meta( $user->ID, 'test_2FA' );
					$response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user->ID, true, $mo2f_configured_2_f_a_method, null, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $temp_email ), true );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_out_of_band_error' ) { // push and out of band email denied.
				$nonce = isset( $_POST['mo2f_out_of_band_error_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_out_of_band_error_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-out-of-band-error-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
					$temp_email = get_user_meta( $user->ID, 'tempEmail', true );
					delete_user_meta( $user->ID, 'test_2FA' );
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::DENIED_REQUEST ), 'ERROR' );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_duo_authenticator_success_form' ) {
				$nonce = isset( $_POST['mo2f_duo_authenticator_success_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_duo_authenticator_success_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-duo-authenticator-success-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					delete_user_meta( $user->ID, 'test_2FA' );
					$mo2fdb_queries->update_user_details(
						$user->ID,
						array(
							'mo_2factor_user_registration_status' => MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS,
							'mo2f_DuoAuthenticator_config_status' => true,
						)
					);
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::COMPLETED_TEST ), 'SUCCESS' );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_duo_authenticator_error' ) { // push and out of band email denied.
				$nonce = isset( $_POST['mo2f_duo_authentcator_error_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_duo_authentcator_error_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-duo-authenticator-error-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					global  $mo2fdb_queries;
					delete_user_meta( $user->ID, 'test_2FA' );
					$mo2fdb_queries->update_user_details(
						$user->ID,
						array(
							'mobile_registration_status' => false,
						)
					);
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::DENIED_DUO_REQUEST ), 'ERROR' );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_validate_google_authy_test' ) {
				$nonce = isset( $_POST['mo2f_test_validate_otp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_test_validate_otp_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-validate-google-authy-test-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					$otp_token  = '';
					$otp_token1 = isset( $_POST['otp_token'] ) ? sanitize_text_field( wp_unslash( $_POST['otp_token'] ) ) : '';
					if ( MO2f_Utility::mo2f_check_empty_or_null( $otp_token1 ) ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_VALUE ), 'ERROR' );
						return;
					} else {
						$otp_token = sanitize_text_field( wp_unslash( $_POST['otp_token'] ) );
					}
					$email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );

					$content = json_decode( $this->mo2f_onprem_cloud_obj->validate_otp_token( MoWpnsConstants::GOOGLE_AUTHENTICATOR, $email, null, $otp_token, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ) ), true );

					if ( json_last_error() === JSON_ERROR_NONE ) {
						if ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) { // Google OTP validated.
							delete_user_meta( $user->ID, 'test_2FA' );
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::COMPLETED_TEST ), 'SUCCESS' );
						} else {  // OTP Validation failed.
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ), 'ERROR' );
						}
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_WHILE_VALIDATING_OTP ), 'ERROR' );
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_validate_otp_over_email' ) {
				$nonce = isset( $_POST['mo2f_test_validate_otp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_test_validate_otp_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-validate-otp-over-email-test-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					$otp_token  = '';
					$otp_token1 = isset( $_POST['otp_token'] ) ? sanitize_text_field( wp_unslash( $_POST['otp_token'] ) ) : '';
					if ( MO2f_Utility::mo2f_check_empty_or_null( $otp_token1 ) ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_VALUE ), 'ERROR' );
						return;
					} else {
						$otp_token = sanitize_text_field( wp_unslash( $_POST['otp_token'] ) );
					}
					$email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );

					$mo2f_transaction_id = get_user_meta( $user->ID, 'mo2f_transactionId', true );
					$content             = json_decode( $this->mo2f_onprem_cloud_obj->validate_otp_token( MoWpnsConstants::OTP_OVER_EMAIL, $email, $mo2f_transaction_id, $otp_token, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ) ), true );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						if ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) { // Google OTP validated.
							delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
							$mo2fdb_queries->update_user_details(
								$user->ID,
								array(
									'mo2f_configured_2FA_method' => MoWpnsConstants::OTP_OVER_EMAIL,
									'mo2f_OTPOverEmail_config_status' => true,
								)
							);
							delete_user_meta( $user->ID, 'test_2FA' );
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::COMPLETED_TEST ), 'SUCCESS' );
						} else {  // OTP Validation failed.
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ), 'ERROR' );
						}
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_WHILE_VALIDATING_OTP ), 'ERROR' );
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_google_appname' ) {
				$nonce = isset( $_POST['mo2f_google_appname_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_google_appname_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-google-appname-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					update_option( 'mo2f_google_appname', ( ( isset( $_POST['mo2f_google_auth_appname'] ) && ! empty( $_POST['mo2f_google_auth_appname'] ) ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_google_auth_appname'] ) ) : DEFAULT_GOOGLE_APPNAME ) );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_configure_google_authenticator_validate' ) {
				$nonce = isset( $_POST['mo2f_configure_google_authenticator_validate_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_configure_google_authenticator_validate_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-google-authenticator-validate-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					$otp_token = isset( $_POST['google_token'] ) ? sanitize_text_field( wp_unslash( $_POST['google_token'] ) ) : null;
					$ga_secret = isset( $_POST['google_auth_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['google_auth_secret'] ) ) : null;

					if ( MO2f_Utility::mo2f_check_number_length( $otp_token ) ) {
						$email                  = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
						$user                   = wp_get_current_user();
						$email                  = ( empty( $email ) ) ? $user->user_email : $email;
						$twofactor_transactions = new Mo2fDB();
						$exceeded               = $twofactor_transactions->check_alluser_limit_exceeded( $user_id );

						if ( $exceeded ) {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::USER_LIMIT_EXCEEDED ), 'ERROR' );
							return;
						}
						$google_response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_validate_google_auth( $email, $otp_token, $ga_secret ), true );
						if ( json_last_error() === JSON_ERROR_NONE ) {
							if ( MoWpnsConstants::SUCCESS_RESPONSE === $google_response['status'] ) {
								$response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user->ID, true, MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, null ), true );
								if ( json_last_error() === JSON_ERROR_NONE ) {
									if ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
										delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );
										delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
										update_user_meta( $user->ID, 'mo2f_external_app_type', MoWpnsConstants::GOOGLE_AUTHENTICATOR );
										mo2f_display_test_2fa_notification( $user );
										delete_user_meta( $user->ID, 'mo2f_google_auth' );
									} else {
										$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
									}
								} else {
									$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
								}
							} else {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_IN_SENDING_OTP_CAUSES ) . '<br>1. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ) . '<br>2. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::APP_TIME_SYNC ) . '<br>3.' . MoWpnsMessages::lang_translate( MoWpnsMessages::SERVER_TIME_SYNC ), 'ERROR' );
							}
						} else {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_WHILE_VALIDATING_USER ), 'ERROR' );
						}
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ONLY_DIGITS_ALLOWED ), 'ERROR' );
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_configure_duo_authenticator_validate_nonce' ) {
				$nonce = isset( $_POST['mo2f_configure_duo_authenticator_validate_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_configure_duo_authenticator_validate_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-duo-authenticator-validate-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );
					delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
					delete_user_meta( $user->ID, 'user_not_enroll' );
					$update_details = new Miniorange_Password_2Factor_Login();
					$update_details->mo2fa_update_user_details( $user->ID, true, MoWpnsConstants::DUO_AUTHENTICATOR, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, 1 );
					update_user_meta( $user->ID, 'mo2f_external_app_type', MoWpnsConstants::DUO_AUTHENTICATOR );
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::VALIDATE_DUO ), 'SUCCESS' );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_configure_authy_authenticator' ) {
				$nonce = isset( $_POST['mo2f_configure_authy_authenticator_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_configure_authy_authenticator_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-authy-authenticator-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					$authy          = new Mo2f_Cloud_Utility();
					$user_email     = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
					$authy_response = json_decode( $authy->mo2f_google_auth_service( $user_email ), true );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						if ( MoWpnsConstants::SUCCESS_RESPONSE === $authy_response['status'] ) {
							$mo2f_authy_keys                      = array();
							$mo2f_authy_keys['authy_qrCode']      = $authy_response['qrCodeData'];
							$mo2f_authy_keys['mo2f_authy_secret'] = $authy_response['secret'];
							$_SESSION['mo2f_authy_keys']          = $mo2f_authy_keys;
						} else {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_USER_REGISTRATION ), 'ERROR' );
						}
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_USER_REGISTRATION ), 'ERROR' );
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_configure_authy_authenticator_validate' ) {
				$nonce = isset( $_POST['mo2f_configure_authy_authenticator_validate_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_configure_authy_authenticator_validate_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-authy-authenticator-validate-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					$otp_token    = isset( $_POST['mo2f_authy_token'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_authy_token'] ) ) : null;
					$authy_secret = isset( $_POST['mo2f_authy_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_authy_secret'] ) ) : null;
					if ( MO2f_Utility::mo2f_check_number_length( $otp_token ) ) {
						$email          = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
						$authy_response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_validate_google_auth( $email, $otp_token, $authy_secret ), true );
						if ( json_last_error() === JSON_ERROR_NONE ) {
							if ( MoWpnsConstants::SUCCESS_RESPONSE === $authy_response['status'] ) {
								$response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user->ID, true, MoWpnsConstants::AUTHY_AUTHENTICATOR, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, null ), true );
								if ( json_last_error() === JSON_ERROR_NONE ) {
									if ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
										$mo2fdb_queries->update_user_details(
											$user->ID,
											array(
												'mo2f_GoogleAuthenticator_config_status' => false,
											)
										);
										update_user_meta( $user->ID, 'mo2f_external_app_type', MoWpnsConstants::AUTHY_AUTHENTICATOR );
										delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );
										delete_user_meta( $user->ID, 'mo2f_configure_2FA' );

										mo2f_display_test_2fa_notification( $user );
									} else {
										$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
									}
								} else {
									$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
								}
							} else {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_IN_SENDING_OTP_CAUSES ) . '<br>1. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ) . '<br>2. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::APP_TIME_SYNC ), 'ERROR' );
							}
						} else {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_WHILE_VALIDATING_USER ), 'ERROR' );
						}
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ONLY_DIGITS_ALLOWED ), 'ERROR' );
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_save_kba' ) {
				$nonce = isset( $_POST['mo2f_save_kba_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_save_kba_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo2f-save-kba-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				}
				$twofactor_transactions = new Mo2fDB();
				$exceeded               = $twofactor_transactions->check_alluser_limit_exceeded( $user_id );
				if ( $exceeded ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::USER_LIMIT_EXCEEDED ), 'ERROR' );
					return;
				}
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
				$email           = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
				$email           = ( empty( $email ) ) ? $user->user_email : $email;
				$kba_reg_reponse = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_register_kba_details( $email, $kba_ques_ans['kba_q1'], $kba_ques_ans['kba_a1'], $kba_ques_ans['kba_q2'], $kba_ques_ans['kba_a2'], $kba_ques_ans['kba_q3'], $kba_ques_ans['kba_a3'], $user->ID ), true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					if ( MoWpnsConstants::SUCCESS_RESPONSE === $kba_reg_reponse['status'] ) {
						if ( isset( $_POST['mobile_kba_option'] ) && sanitize_text_field( wp_unslash( $_POST['mobile_kba_option'] ) ) === 'mo2f_request_for_kba_as_emailbackup' ) {
							MO2f_Utility::unset_session_variables( 'mo2f_mobile_support' );
							delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
							delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );
							$message = esc_html__( 'Your KBA as alternate 2 factor is configured successfully.', 'miniorange-2-factor-authentication' );
							$show_message->mo2f_show_message( $message, 'SUCCESS' );
						} else {
							$response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user->ID, true, MoWpnsConstants::SECURITY_QUESTIONS, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, null ), true );
							if ( json_last_error() === JSON_ERROR_NONE ) {
								if ( 'ERROR' === $response['status'] ) {
									$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $response['message'] ), 'ERROR' );
								} elseif ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
									delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
									mo2f_display_test_2fa_notification( $user );
								} else {
									$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
								}
							} else {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ), 'ERROR' );
							}
						}
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_WHILE_SAVING_KBA ), 'ERROR' );
						return;
					}
				} else {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_WHILE_SAVING_KBA ), 'ERROR' );
					return;
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_validate_kba_details' ) {
				$nonce = isset( $_POST['mo2f_authenticate_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_authenticate_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-soft-token-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					$kba_ans_1 = '';
					$kba_ans_2 = '';
					if ( MO2f_Utility::mo2f_check_empty_or_null( isset( $_POST['mo2f_answer_1'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_1'] ) ) : null ) || MO2f_Utility::mo2f_check_empty_or_null( isset( $_POST['mo2f_answer_2'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_2'] ) ) : null ) ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_ENTRY ), 'ERROR' );
						return;
					} else {
						$kba_ans_1 = sanitize_text_field( wp_unslash( $_POST['mo2f_answer_1'] ) );
						$kba_ans_2 = sanitize_text_field( wp_unslash( $_POST['mo2f_answer_2'] ) );
					}
					// if the php session folder has insufficient permissions, temporary options to be used.
					$kba_questions = get_user_meta( $user->ID, 'mo_2_factor_kba_questions', true );
					$kba_ans       = array();
					if ( ! MO2F_IS_ONPREM ) {
						$kba_ans[0] = $kba_questions[0]['question'];
						$kba_ans[1] = $kba_ans_1;
						$kba_ans[2] = $kba_questions[1]['question'];
						$kba_ans[3] = $kba_ans_2;
					}
					// if the php session folder has insufficient permissions, temporary options to be used.
					$mo2f_transaction_id   = get_option( 'mo2f_transactionId' );
					$kba_validate_response = json_decode( $this->mo2f_onprem_cloud_obj->validate_otp_token( MoWpnsConstants::SECURITY_QUESTIONS, null, $mo2f_transaction_id, $kba_ans, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ) ), true );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						if ( strcasecmp( $kba_validate_response['status'], 'SUCCESS' ) === 0 ) {
							delete_option( 'mo2f_transactionId' );
							delete_option( 'kba_questions' );
							delete_user_meta( $user->ID, 'test_2FA' );
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::COMPLETED_TEST ), 'SUCCESS' );
						} else {  // KBA Validation failed.
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_ANSWERS ), 'ERROR' );
						}
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_2factor_test_prompt_cross' ) {
				$nonce = isset( $_POST['mo2f_2factor_test_prompt_cross_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_2factor_test_prompt_cross_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo2f-2factor-test-prompt-cross-nonce' ) ) {
					update_user_meta( $user->ID, 'mo2f_otp_send_true', true );
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				}
				mo2f_display_test_2fa_notification( $user );
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_configure_duo_authenticator' ) {
				$nonce = isset( $_POST['mo2f_configure_duo_authenticator_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_configure_duo_authenticator_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-duo-authenticator' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					if ( isset( $_POST['ikey'] ) && sanitize_key( $_POST['ikey'] ) === '' || isset( $_POST['skey'] ) && sanitize_key( $_POST['skey'] ) === '' || empty( $_POST['apihostname'] ) && esc_url_raw( wp_unslash( $_POST['apihostname'] ) ) === '' ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::FIELD_MISSING ), 'ERROR' );
						return;
					} else {
						update_site_option( 'mo2f_d_integration_key', isset( $_POST['ikey'] ) ? sanitize_key( $_POST['ikey'] ) : '' );
						update_site_option( 'mo2f_d_secret_key', isset( $_POST['skey'] ) ? sanitize_key( $_POST['skey'] ) : '' );
						update_site_option( 'mo2f_d_api_hostname', isset( $_POST['apihostname'] ) ? esc_url_raw( wp_unslash( $_POST['apihostname'] ) ) : '' );

						$ikey = isset( $_POST['ikey'] ) ? sanitize_key( wp_unslash( $_POST['ikey'] ) ) : '';
						$skey = isset( $_POST['skey'] ) ? sanitize_key( wp_unslash( $_POST['skey'] ) ) : '';
						$host = isset( $_POST['apihostname'] ) ? esc_url_raw( wp_unslash( $_POST['apihostname'] ) ) : '';

						include_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-duo-handler.php';

						$duo_up_response = ping( $skey, $ikey, $host );

						if ( 'OK' === $duo_up_response['response']['stat'] ) {
							$duo_check_credentials = check( $skey, $ikey, $host );

							if ( 'OK' !== $duo_check_credentials['response']['stat'] ) {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_CREDENTIALS ), 'ERROR' );
								return;
							}
						} else {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::DUO_SERVER_NOT_RESPONDING ), 'ERROR' );
							return;
						}
						update_site_option( 'duo_credentials_save_successfully', 1 );
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED ), 'SUCCESS' );
						return;
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_configure_duo_authenticator_abc' ) {
				$nonce = isset( $_POST['mo2f_configure_duo_authenticator_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_configure_duo_authenticator_nonce'] ) ) : null;

				if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-duo-authenticator-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					include_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-duo-handler.php';
					$ikey        = get_site_option( 'mo2f_d_integration_key' );
					$skey        = get_site_option( 'mo2f_d_secret_key' );
					$host        = get_site_option( 'mo2f_d_api_hostname' );
					$user_email  = $user->user_email;
					$duo_preauth = preauth( $user_email, true, $skey, $ikey, $host );
					if ( 'OK' === $duo_preauth['response']['stat'] ) {
						if ( isset( $duo_preauth['response']['response']['status_msg'] ) && 'Account is active' === $duo_preauth['response']['response']['status_msg'] ) {
							update_user_meta( $user->ID, 'user_not_enroll', true );
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::DUO_USER_EXISTS ), 'SUCCESS' );
							return;
						} elseif ( isset( $duo_preauth['response']['response']['enroll_portal_url'] ) ) {
							$duo_enroll_url = $duo_preauth['response']['response']['enroll_portal_url'];
							update_user_meta( $user->ID, 'user_not_enroll_on_duo_before', $duo_enroll_url );
							update_user_meta( $user->ID, 'user_not_enroll', true );
						} else {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::DUO_ACCOUNT_INACTIVE ), 'ERROR' );

							return;
						}
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::DUO_INVALID_REQ ), 'ERROR' );
						return;
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'duo_mobile_send_push_notification_inside_plugin' ) {
				$nonce = isset( $_POST['duo_mobile_send_push_notification_inside_plugin_nonce'] ) ? sanitize_key( wp_unslash( $_POST['duo_mobile_send_push_notification_inside_plugin_nonce'] ) ) : null;
				if ( ! isset( $_POST['duo_mobile_send_push_notification_inside_plugin_nonce'] ) || ! wp_verify_nonce( $nonce, 'mo2f-send-duo-push-notification-inside-plugin-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				}
			} elseif ( ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_save_free_plan_auth_methods' ) ) { // user clicks on Set 2-Factor method.
				$nonce = isset( $_POST['miniorange_save_form_auth_methods_nonce'] ) ? sanitize_key( wp_unslash( $_POST['miniorange_save_form_auth_methods_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'miniorange-save-form-auth-methods-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					$configured_method = isset( $_POST['mo2f_configured_2FA_method_free_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_configured_2FA_method_free_plan'] ) ) : '';
					$cloud_methods     = array( 'OTPOverSMS', 'miniOrangeQRCodeAuthentication', 'miniOrangePushNotification', 'miniOrangeSoftToken' );

					if ( 'OTPOverSMS' === $configured_method ) {
						$configured_method = MoWpnsConstants::OTP_OVER_SMS;
					}
					// limit exceed check.
					$exceeded = $mo2fdb_queries->check_alluser_limit_exceeded( $user_id );
					if ( $exceeded ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::USER_LIMIT_EXCEEDED ), 'ERROR' );
						return;
					}
					$selected_2_f_a_method = MoWpnsConstants::mo2f_convert_method_name( isset( $_POST['mo2f_configured_2FA_method_free_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_configured_2FA_method_free_plan'] ) ) : ( isset( $_POST['mo2f_selected_action_standard_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_selected_action_standard_plan'] ) ) : '' ), 'pascal_to_cap' );
					$onprem_methods        = array( MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SECURITY_QUESTIONS, 'OTPOverTelegram', 'DuoAuthenticator' );
					$mo2fdb_queries->insert_user( $user->ID );
					if ( MO2F_IS_ONPREM && ! in_array( $selected_2_f_a_method, $onprem_methods, true ) ) {
						foreach ( $cloud_methods as $cloud_method ) {
							$is_end_user_registered = $mo2fdb_queries->get_user_detail( 'mo2f_' . $cloud_method . '_config_status', $user->ID );
							if ( ! is_null( $is_end_user_registered ) && 1 === $is_end_user_registered ) {
								break;
							}
						}
					} else {
						$is_end_user_registered = $mo2fdb_queries->get_user_detail( 'user_registration_with_miniorange', $user->ID );
					}
					$is_customer_registered = false;
					if ( ! MO2F_IS_ONPREM || 'miniOrangeSoftToken' === $configured_method || 'miniOrangeQRCodeAuthentication' === $configured_method || 'miniOrangePushNotification' === $configured_method || 'OTPOverSMS' === $configured_method || MoWpnsConstants::OTP_OVER_SMS === $configured_method ) {
						$is_customer_registered = get_option( 'mo2f_api_key' ) ? true : false;
					}
					$email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
					if ( ! isset( $email ) || is_null( $email ) || empty( $email ) ) {
						$email = $user->user_email;
					}
					$is_end_user_registered = $is_end_user_registered ? $is_end_user_registered : false;
					$allowed                = false;
					if ( get_option( 'mo2f_miniorange_admin' ) ) {
						$allowed = wp_get_current_user()->ID === get_option( 'mo2f_miniorange_admin' );
					}
					if ( ! MO2F_IS_ONPREM && $is_customer_registered && ! $is_end_user_registered && ! $allowed ) {
						$enduser    = new Two_Factor_Setup_Onprem_Cloud();
						$check_user = json_decode( $enduser->mo_check_user_already_exist( $email ), true );
						if ( json_last_error() === JSON_ERROR_NONE ) {
							if ( 'ERROR' === $check_user['status'] ) {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $check_user['message'] ), 'ERROR' );
								return;
							} elseif ( strcasecmp( $check_user['status'], 'USER_FOUND' ) === 0 ) {
								$mo2fdb_queries->update_user_details(
									$user->ID,
									array(
										'user_registration_with_miniorange' => 'SUCCESS',
										'mo2f_user_email' => $email,
									)
								);
								update_site_option( base64_encode( 'totalUsersCloud' ), intval( get_site_option( base64_encode( 'totalUsersCloud' ) ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
							} elseif ( strcasecmp( $check_user['status'], 'USER_NOT_FOUND' ) === 0 ) {
								$content = json_decode( $enduser->mo_create_user( $user, $email ), true );
								if ( json_last_error() === JSON_ERROR_NONE ) {
									if ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) {
										update_site_option( base64_encode( 'totalUsersCloud' ), intval( get_site_option( base64_encode( 'totalUsersCloud' ) ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
										$mo2fdb_queries->update_user_details(
											$user->ID,
											array(
												'user_registration_with_miniorange' => 'SUCCESS',
												'mo2f_user_email' => $email,
											)
										);
									}
								}
							} elseif ( strcasecmp( $check_user['status'], 'USER_FOUND_UNDER_DIFFERENT_CUSTOMER' ) === 0 ) {
								$mo2fa_login_message = esc_html__( 'The email associated with your account is already registered in miniOrange. Please Choose another email or contact miniOrange.', 'miniorange-2-factor-authentication' );
								$show_message->mo2f_show_message( $mo2fa_login_message, 'ERROR' );
							}
						}
					}
					update_user_meta( $user->ID, 'mo2f_2FA_method_to_configure', $selected_2_f_a_method );
					if ( MO2F_IS_ONPREM && ( MoWpnsConstants::GOOGLE_AUTHENTICATOR === $selected_2_f_a_method || MoWpnsConstants::SECURITY_QUESTIONS === $selected_2_f_a_method || MoWpnsConstants::OTP_OVER_EMAIL === $selected_2_f_a_method || MoWpnsConstants::OUT_OF_BAND_EMAIL === $selected_2_f_a_method || MoWpnsConstants::OTP_OVER_TELEGRAM === $selected_2_f_a_method || MoWpnsConstants::DUO_AUTHENTICATOR === $selected_2_f_a_method ) ) {
						$is_customer_registered = 1;
					}
					if ( $is_customer_registered ) {
						$selected_action = isset( $_POST['mo2f_selected_action_free_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_selected_action_free_plan'] ) ) : sanitize_text_field( wp_unslash( $_POST['mo2f_selected_action_standard_plan'] ) );
						$user_phone      = '';
						if ( isset( $_SESSION['user_phone'] ) ) {
							$user_phone = 'false' !== $_SESSION['user_phone'] ? sanitize_text_field( $_SESSION['user_phone'] ) : $mo2fdb_queries->get_user_detail( 'mo2f_user_phone', $user->ID );
						}
						if ( 'select2factor' === $selected_action ) {
							if ( MoWpnsConstants::OTP_OVER_SMS === $selected_2_f_a_method && 'false' === $user_phone ) {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::PHONE_NOT_CONFIGURED ), 'ERROR' );
							} else {
								// update in the WordPress DB.
								$email        = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
								$customer_key = get_option( 'mo2f_customerKey' );
								$api_key      = get_option( 'mo2f_api_key' );
								$mo2fdb_queries->update_user_details( $user->ID, array( 'mo2f_configured_2FA_method' => $selected_2_f_a_method ) );
								// update the server.
								if ( ! MO2F_IS_ONPREM ) {
									$this->mo2f_save_2_factor_method( $user, $selected_2_f_a_method );
								}
							}
						} elseif ( 'configure2factor' === $selected_action ) {
							// show configuration form of respective Two Factor method.
							update_user_meta( $user->ID, 'mo2f_configure_2FA', 1 );
							update_user_meta( $user->ID, 'mo2f_2FA_method_to_configure', $selected_2_f_a_method );
						}
					} else {
						update_option( 'mo_2factor_user_registration_status', 'REGISTRATION_STARTED' );
						update_user_meta( $user->ID, 'register_account_popup', 1 );
					}
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_enable_2FA_for_users_option' ) {
				$nonce = isset( $_POST['mo2f_enable_2FA_for_users_option_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_enable_2FA_for_users_option_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo2f-enable-2FA-for-users-option-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					update_option( 'mo2f_enable_2fa_for_users', isset( $_POST['mo2f_enable_2fa_for_users'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_enable_2fa_for_users'] ) ) : 0 );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_enable_2FA_option' ) {
				$nonce = isset( $_POST['mo2f_enable_2FA_option_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_enable_2FA_option_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo2f-enable-2FA-option-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					update_option( 'mo2f_enable_2fa', isset( $_POST['mo2f_enable_2fa'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_enable_2fa'] ) ) : 0 );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo_2factor_test_authentication_method' ) {
				// network security feature.
				$nonce = isset( $_POST['mo_2factor_test_authentication_method_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_2factor_test_authentication_method_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo-2factor-test-authentication-method-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					update_user_meta( $user->ID, 'test_2FA', 1 );
					$selected_2_f_a_method = isset( $_POST['mo2f_configured_2FA_method_test'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_configured_2FA_method_test'] ) ) : '';
					$email                 = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
					$customer_key          = get_option( 'mo2f_customerKey' );
					$api_key               = get_option( 'mo2f_api_key' );
					if ( MoWpnsConstants::SECURITY_QUESTIONS === $selected_2_f_a_method ) {
						$response = json_decode( $this->mo2f_onprem_cloud_obj->send_otp_token( $email, $selected_2_f_a_method, $customer_key, $api_key ), true );

						if ( json_last_error() === JSON_ERROR_NONE ) { /* Generate KBA Questions*/
							if ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
								update_option( 'mo2f_transactionId', $response['txId'] );
								$questions    = array();
								$questions[0] = $response['questions'][0];
								$questions[1] = $response['questions'][1];
								update_user_meta( $user->ID, 'mo_2_factor_kba_questions', $questions );
								$show_message = new MoWpnsMessages();
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ANSWER_SECURITY_QUESTIONS ), 'SUCCESS' );
							} elseif ( 'ERROR' === $response['status'] ) {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_FETCHING_QUESTIONS ), 'ERROR' );
							}
						} else {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_FETCHING_QUESTIONS ), 'ERROR' );
						}
					} elseif ( MoWpnsConstants::PUSH_NOTIFICATIONS === $selected_2_f_a_method ) {
						$customer = new MocURL();
						$response = json_decode( $customer->miniorange_auth_challenge( $email, $selected_2_f_a_method, $customer_key, $api_key ), true );
						if ( json_last_error() === JSON_ERROR_NONE ) { /* Generate Qr code */
							if ( 'ERROR' === $response['status'] ) {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $response['message'] ), 'ERROR' );
							} else {
								if ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
									update_user_meta( $user->ID, 'mo2f_transactionId', $response['txId'] );
									update_user_meta( $user->ID, 'mo2f_show_qr_code', 'MO_2_FACTOR_SHOW_QR_CODE' );
									$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::PUSH_NOTIFICATION_SENT ), 'SUCCESS' );
								} else {
									$session_variables = array( 'mo2f_qrCode', 'mo2f_transactionId', 'mo2f_show_qr_code' );
									MO2f_Utility::unset_session_variables( $session_variables );
									delete_option( 'mo2f_transactionId' );
									$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
								}
							}
						} else {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ), 'ERROR' );
						}
					} elseif ( MoWpnsConstants::OTP_OVER_TELEGRAM === $selected_2_f_a_method ) {
						$user      = wp_get_current_user();
						$chat_i_d  = get_user_meta( $user->ID, 'mo2f_chat_id', true );
						$otp_token = '';
						for ( $i = 1; $i < 7; $i++ ) {
							$otp_token .= wp_rand( 0, 9 );
						}
						update_user_meta( $user->ID, 'mo2f_otp_token', $otp_token );
						update_user_meta( $user->ID, 'mo2f_telegram_time', time() );
						$url      = esc_url( MoWpnsConstants::TELEGRAM_OTP_LINK );
						$postdata = array(
							'mo2f_otp_token' => $otp_token,
							'mo2f_chatid'    => $chat_i_d,
						);
						$args     = array(
							'method'    => 'POST',
							'timeout'   => 10,
							'sslverify' => false,
							'headers'   => array(),
							'body'      => $postdata,
						);
						$mo2f_api = new Mo2f_Api();
						$data     = $mo2f_api->mo2f_wp_remote_post( $url, $args );
						if ( MoWpnsConstants::SUCCESS_RESPONSE === $data ) {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::OTP_SENT ) . 'your telegram number.' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ), 'SUCCESS' );
						} else {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::VERIFY_CHAT_ID ), 'ERROR' );
						}
					} elseif ( MoWpnsConstants::OTP_OVER_SMS === $selected_2_f_a_method || MoWpnsConstants::OTP_OVER_EMAIL === $selected_2_f_a_method ) {
						$phone = $mo2fdb_queries->get_user_detail( 'mo2f_user_phone', $user->ID );
						$check = 1;
						if ( MoWpnsConstants::OTP_OVER_EMAIL === $selected_2_f_a_method ) {
							$phone = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
							if ( MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' ) <= 0 ) {
								update_site_option( 'bGltaXRSZWFjaGVk', 1 );
								$check = 0;
							}
						}

						if ( 1 === $check ) {
							$response = json_decode( $this->mo2f_onprem_cloud_obj->send_otp_token( $phone, $selected_2_f_a_method, $customer_key, $api_key ), true );

						} else {
							$response['status'] = 'FAILED';
						}
						if ( strcasecmp( $response['status'], 'SUCCESS' ) === 0 ) {
							if ( MoWpnsConstants::OTP_OVER_EMAIL === $selected_2_f_a_method || MoWpnsConstants::OUT_OF_BAND_EMAIL === $selected_2_f_a_method ) {
								$cm_vt_y_wlua_w5n_t1_r_q = MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
								if ( $cm_vt_y_wlua_w5n_t1_r_q > 0 ) {
									update_site_option( 'cmVtYWluaW5nT1RQ', $cm_vt_y_wlua_w5n_t1_r_q - 1 );
								}
							} elseif ( MoWpnsConstants::OTP_OVER_SMS === $selected_2_f_a_method ) {
								$mo2f_sms = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' );
								if ( $mo2f_sms > 0 ) {
									update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', $mo2f_sms - 1 );
								}
							}
							update_option( 'mo2f_number_of_transactions', MoWpnsUtility::get_mo2f_db_option( 'mo2f_number_of_transactions', 'get_option' ) - 1 );
							update_user_meta( $user->ID, 'mo2f_transactionId', $response['txId'] );
							update_option( 'mo2f_transactionId', $response['txId'] );
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::OTP_SENT ) . ' <b>' . ( $phone ) . '</b>. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ), 'SUCCESS' );
						} else {
							if ( ! MO2F_IS_ONPREM || MoWpnsConstants::OTP_OVER_SMS === $selected_2_f_a_method ) {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_IN_SENDING_OTP ), 'ERROR' );
							} else {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_IN_SENDING_OTP_ONPREM ), 'ERROR' );
							}
						}
					} elseif ( MoWpnsConstants::MOBILE_AUTHENTICATION === $selected_2_f_a_method ) {
						$customer = new MocURL();
						$response = json_decode( $customer->miniorange_auth_challenge( $email, $selected_2_f_a_method, $customer_key, $api_key ), true );
						if ( json_last_error() === JSON_ERROR_NONE ) { /* Generate Qr code */
							if ( 'ERROR' === $response['status'] ) {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $response['message'] ), 'ERROR' );
							} else {
								if ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
									update_user_meta( $user->ID, 'mo2f_qrCode', $response['qrCode'] );
									update_user_meta( $user->ID, 'mo2f_transactionId', $response['txId'] );
									update_user_meta( $user->ID, 'mo2f_show_qr_code', 'MO_2_FACTOR_SHOW_QR_CODE' );
									$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SCAN_QR_CODE ), 'SUCCESS' );
								} else {
									$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
								}
							}
						} else {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ), 'ERROR' );
						}
					} elseif ( MoWpnsConstants::OUT_OF_BAND_EMAIL === $selected_2_f_a_method ) {
						global $mo2f_onprem_cloud_obj;
						$mo2f_onprem_cloud_obj->mo2f_email_verification_call( $user );
					}
					update_user_meta( $user->ID, 'mo2f_2FA_method_to_test', $selected_2_f_a_method );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_go_back' ) {
				$nonce = isset( $_POST['mo2f_go_back_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_go_back_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo2f-go-back-nonce' ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
					return;
				} else {
					$session_variables = array(
						'mo2f_qrCode',
						'mo2f_transactionId',
						'mo2f_show_qr_code',
						'user_phone',
						'mo2f_google_auth',
						'mo2f_mobile_support',
						'mo2f_authy_keys',
					);
					MO2f_Utility::unset_session_variables( $session_variables );
					delete_option( 'mo2f_transactionId' );
					delete_user_meta( $user->ID, 'user_phone_temp' );
					delete_user_meta( $user->ID, 'test_2FA' );
					delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
					delete_user_meta( $user->ID, 'mo2f_otp_send_true' );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_reset_duo_configuration' ) {
				$nonce = isset( $_POST['mo2f_duo_reset_configuration_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_duo_reset_configuration_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo2f-duo-reset-configuration-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					delete_site_option( 'duo_credentials_save_successfully' );
					delete_user_meta( $user->ID, 'user_not_enroll' );
					delete_site_option( 'mo2f_d_integration_key' );
					delete_site_option( 'mo2f_d_secret_key' );
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::RESET_DUO_CONFIGURATON ), 'SUCCESS' );
				}
			} elseif ( isset( $_POST['option'] ) && sanitize_text_field( wp_unslash( $_POST['option'] ) ) === 'mo2f_2factor_generate_backup_codes' ) {
				$nonce = isset( $_POST['mo_2factor_generate_backup_codes_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_2factor_generate_backup_codes_nonce'] ) ) : null;
				if ( ! wp_verify_nonce( $nonce, 'mo-2factor-generate-backup-codes-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					$codes = MO2f_Utility::mo2f_mail_and_download_codes();
					if ( 'TransientActive' === $codes ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::TRANSIENT_ACTIVE ), 'ERROR' );
					}
					if ( 'InternetConnectivityError' === $codes ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INTERNET_CONNECTIVITY_ERROR ), 'ERROR' );
					}
					if ( 'LimitReached' === $codes || 'UserLimitReached' === $codes || 'AllUsed' === $codes || 'invalid_request' === $codes ) {
						$id = get_current_user_id();
						update_user_meta( $id, 'mo_backup_code_generated', 1 );
						update_user_meta( $id, 'mo_backup_code_downloaded', 1 );
						if ( 'AllUsed' === $codes ) {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::USED_ALL_BACKUP_CODES ), 'ERROR' );
						} elseif ( 'LimitReached' === $codes ) {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::BACKUP_CODE_LIMIT_REACH ), 'ERROR' );
						} elseif ( 'UserLimitReached' === $codes ) {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::BACKUP_CODE_DOMAIN_LIMIT_REACH ), 'ERROR' );
						} elseif ( 'invalid_request' === $codes ) {
							update_user_meta( $id, 'mo_backup_code_generated', 0 );
							update_user_meta( $id, 'mo_backup_code_downloaded', 0 );
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::BACKUP_CODE_INVALID_REQUEST ), 'ERROR' );
						}
					}
				}
			}
		}
		/**
		 * Delete user details on deativation.
		 */
		public function mo2f_auth_deactivate() {

			global $mo2fdb_queries;
			$mo2f_register_with_another_email = get_option( 'mo2f_register_with_another_email' );
			if ( $mo2f_register_with_another_email ) {
				update_option( 'mo2f_register_with_another_email', 0 );
				$mo2fdb_queries->mo2f_delete_user_details();
			}
		}

		/**
		 * Get QR code for mobile.
		 *
		 * @param string $email user email.
		 * @param int    $id user id.
		 * @param string $session_id user session id.
		 * @return void
		 */
		public function mo2f_get_qr_code_for_mobile( $email, $id, $session_id = null ) {
			$register_mobile = new Two_Factor_Setup_Onprem_Cloud();
			$content         = $register_mobile->register_mobile( $email );
			$show_message    = new MoWpnsMessages();
			$response        = json_decode( $content, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'ERROR' === $response['status'] ) {
					$session_variables = array( 'mo2f_qrCode', 'mo2f_transactionId', 'mo2f_show_qr_code' );
					MO2f_Utility::unset_session_variables( $session_variables );
					delete_option( 'mo2f_transactionId' );
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $response['message'] ), 'ERROR' );
				} else {
					if ( 'IN_PROGRESS' === $response['status'] ) {
						MO2f_Utility::mo2f_set_transient( $session_id, 'mo2f_qrCode', $response['qrCode'] );
						MO2f_Utility::mo2f_set_transient( $session_id, 'mo2f_transactionId', $response['txId'] );
						update_user_meta( $id, 'mo2f_transactionId', $response['txId'] );
						MO2f_Utility::mo2f_set_transient( $session_id, 'mo2f_show_qr_code', 'MO_2_FACTOR_SHOW_QR_CODE' );
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SCAN_QR_CODE ), 'SUCCESS' );
					} else {
						$session_variables = array( 'mo2f_qrCode', 'mo2f_transactionId', 'mo2f_show_qr_code' );
						MO2f_Utility::unset_session_variables( $session_variables );
						delete_option( 'mo2f_transactionId' );
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
					}
				}
			}
		}
		/**
		 * Save 2-factor method of a user.
		 *
		 * @param object $user user object.
		 * @param string $mo2f_configured_2_f_a_method configured 2FA method of a user.
		 * @return void
		 */
		public function mo2f_save_2_factor_method( $user, $mo2f_configured_2_f_a_method ) {

			global $mo2fdb_queries;
			$email        = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
			$phone        = $mo2fdb_queries->get_user_detail( 'mo2f_user_phone', $user->ID );
			$show_message = new MoWpnsMessages();
			$response     = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user->ID, true, $mo2f_configured_2_f_a_method, null, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, null, $email, null ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'ERROR' === $response['status'] ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $response['message'] ), 'ERROR' );
				} elseif ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
					$configured_2_f_a_method = empty( $mo2f_configured_2_f_a_method ) ? $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID ) : $mo2f_configured_2_f_a_method;
					if ( in_array( $configured_2_f_a_method, array( MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::AUTHY_AUTHENTICATOR ), true ) ) {
						update_user_meta( $user->ID, 'mo2f_external_app_type', $configured_2_f_a_method );
					}
					delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
					if ( MoWpnsConstants::OTP_OVER_EMAIL === $configured_2_f_a_method || MoWpnsConstants::OTP_OVER_SMS === $configured_2_f_a_method ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $configured_2_f_a_method ) . ' ' . MoWpnsMessages::lang_translate( MoWpnsMessages::SET_2FA_OTP ), 'SUCCESS' );
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $configured_2_f_a_method ) . ' ' . MoWpnsMessages::lang_translate( MoWpnsMessages::SET_2FA ), 'ERROR' );
					}
				} else {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
				}
			} else {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ), 'ERROR' );
			}
		}

		/**
		 * Low otp alert.
		 *
		 * @param string $auth_type authentication type.
		 * @return void
		 */
		public static function mo2f_low_otp_alert( $auth_type ) {

			global $image_path;
			$email = get_option( 'mo2f_email' ) ? get_option( 'mo2f_email' ) : get_option( 'admin_email' );
			if ( MO2F_IS_ONPREM ) {
				$count = 0;
				if ( 'email' === $auth_type ) {
					$subject = 'Two Factor Authentication(Low Email Alert)';
					$count   = get_site_option( 'cmVtYWluaW5nT1RQ' ) - 1; // database value is updated after public function call.
					$string  = 'Email';
				} elseif ( 'sms' === $auth_type ) {
					$subject = 'Two Factor Authentication(Low SMS Alert)';
					$count   = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) - 1; // database value is updated after public function call.
					$string  = 'SMS';
				}
				$admin_url    = network_site_url();
				$url          = explode( '/wp-admin/admin.php?page=mo_2fa_upgrade', $admin_url );
				$headers      = array( 'Content-Type: text/html; charset=UTF-8' );
				$headers[]    = 'Cc: 2fasupport <mfasupport@xecurify.com>';
				$message      = '<table cellpadding="25" style="margin:0px auto">
			<tbody>
			<td>
			<td>
			<table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;border:1px solid #a8adad">
			<tbody>
			<td>
			<td><img src="' . $image_path . 'includes/images/xecurify-logo.png" alt="Xecurify" style="color:#5fb336;text-decoration:none;display:block;width:auto;height:auto;max-height:35px" class="CToWUd"></td>
			</tr>
			</tbody>
			</table>
			<table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
			<tbody>
			<td>
			<td>
			<p style="margin-top:0;margin-bottom:20px">Dear Customer,</p>
			<p style="margin-top:0;margin-bottom:20px"> You are going to exhaust all your ' . $string . '. You have only <b>' . $count . '</b> ' . $string . ' remaining. You can recharge || add ' . $string . ' to your account: <a href=' . MoWpnsConstants::RECHARGELINK . '>Recharge</a></p>
			<p style="margin-top:0;margin-bottom:10px">After Recharge you can continue using your current plan. To know more about our plans you can also visit our site: <a href=' . $url[0] . '/wp-admin/admin.php?page=mo_2fa_upgrade>2FA Plans</a>.</p>
			<p style="margin-top:0;margin-bottom:10px">If you do not wish to recharge, we advise you to <a href=' . $url[0] . '/wp-admin/admin.php?page=mo_2fa_two_fa>change the 2FA method</a> before you have no ' . $string . ' left. In case you get locked out, please use this guide to gain access: <a href=' . MoWpnsConstants::ONPREMISELOCKEDOUT . '>Guide link</a></p>
			<p style="margin-top:0;margin-bottom:20px">For more information, you can contact us directly at 2fasupport@xecurify.com.</p>
			<p style="margin-top:0;margin-bottom:15px">Thank you,<br>miniOrange Team</p>
			<p style="margin-top:0;margin-bottom:0px;font-size:11px">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual || entity to whom they are addressed.</p>
			</div></div></td>
			</tr>
			</tbody>
			</table>
			</td>
			</tr>
			</tbody>
			</table>';
				$result = wp_mail( $email, $subject, $message, $headers );
			}
		}

		/**
		 * Check if a customer is registered.
		 *
		 * @return boolean
		 */
		public static function mo2f_is_customer_registered() {
			$email        = get_option( 'mo2f_email' );
			$customer_key = get_option( 'mo2f_customerKey' );
			if ( ! $email || ! $customer_key || ! is_numeric( trim( $customer_key ) ) ) {
				return 0;
			} else {
				return 1;
			}
		}
	}
	new Miniorange_Authentication();
}

