<?php
/**
 * This file contains the ajax request handler.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Onprem\Two_Factor_Setup_Onprem_Cloud;
use TwoFA\Database\Mo2fDB;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MocURL;
use TwoFA\Onprem\MO2f_Cloud_Onprem_Interface;
use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Onprem\Google_Auth_Onpremise;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo_2f_Ajax' ) ) {
	/**
	 * Class Mo_2f_Ajax
	 */
	class Mo_2f_Ajax {
		/**
		 * Class Mo2f_Cloud_Onprem_Interface object
		 *
		 * @var object
		 */
		private $mo2f_onprem_cloud_obj;
		/**
		 * Constructor of class.
		 */
		public function __construct() {
			$this->mo2f_onprem_cloud_obj = MO2f_Cloud_Onprem_Interface::instance();
			add_action( 'admin_init', array( $this, 'mo_2f_two_factor' ) );
		}
		/**
		 * Function for handling ajax requests.
		 *
		 * @return void
		 */
		public function mo_2f_two_factor() {
			add_action( 'wp_ajax_mo_two_factor_ajax', array( $this, 'mo_two_factor_ajax' ) );
			add_action( 'wp_ajax_nopriv_mo_two_factor_ajax', array( $this, 'mo_two_factor_ajax' ) );
		}
		/**
		 * Call functions as per ajax requests.
		 *
		 * @return void
		 */
		public function mo_two_factor_ajax() {
			$GLOBALS['mo2f_is_ajax_request'] = true;
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-mo2f-ajax' );

			}

			$mo2f_enable_2fa_settings = isset( $_POST['mo2f_enable_2fa_settings'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_enable_2fa_settings'] ) ) : null;
			$enable_settings          = 'true' === $mo2f_enable_2fa_settings ? 1 : 0;
			switch ( isset( $_POST['mo_2f_two_factor_ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['mo_2f_two_factor_ajax'] ) ) : '' ) {
				case 'mo2f_ajax_login_redirect':
					$this->mo2f_ajax_login_redirect();
					break;
				case 'mo2f_save_email_verification':
					$this->mo2f_save_email_verification();
					break;
				case 'mo2f_unlimitted_user':
					$this->mo2f_unlimitted_user();
					break;
				case 'mo2f_check_user_exist_miniOrange':
					$this->mo2f_check_user_exist_miniorange();
					break;
				case 'mo2f_single_user':
					$this->mo2f_single_user();
					break;
				case 'mo2f_configure_otp_based_twofa':
					$this->mo2f_configure_otp_based_twofa();
					break;
				case 'mo2f_configure_otp_based_methods_validate':
					$this->mo2f_configure_otp_based_methods_validate();
					break;
				case 'CheckEVStatus':
					$this->check_email_verification_status();
					break;
				case 'mo2f_role_based_2_factor':
					$this->mo2f_role_based_2_factor();
					break;
				case 'mo2f_enable_disable_twofactor':
					$this->mo2f_enable_disable_twofactor( $enable_settings );
					break;
				case 'mo2f_enable_disable_inline':
					$this->mo2f_enable_disable_inline( $enable_settings );
					break;
				case 'mo2f_enable_disable_configurd_methods':
					$this->mo2f_enable_disable_configurd_methods( $enable_settings );
					break;
				case 'mo2f_shift_to_onprem':
					$this->mo2f_shift_to_onprem();
					break;
				case 'mo2f_save_custom_form_settings':
					$this->mo2f_save_custom_form_settings();
					break;
				case 'mo2f_enable_disable_debug_log':
					$this->mo2f_enable_disable_debug_log( $enable_settings );
					break;
				case 'mo2f_delete_log_file':
					$this->mo2f_delete_log_file();
					break;
				case 'mo2f_grace_period_save':
					$this->mo2f_grace_period_save();
					break;
				case 'mo_wpns_register_verify_customer':
					$this->mo_wpns_register_verify_customer();
					break;
				case 'mo2f_set_otp_over_sms':
					$this->mo2f_set_otp_over_sms();
					break;
				case 'mo2f_set_miniorange_methods':
					$this->mo2f_set_miniorange_methods();
					break;
				case 'mo2f_enable_twofactor_userprofile':
					$this->mo2f_enable_twofactor_userprofile();
					break;
				case 'mo2f_set_GA':
					$this->mo2f_set_ga();
					break;
				case 'mo2f_enable_transactions_report':
					$this->mo2f_enable_transactions_report();
					break;
				case 'mo2f_google_auth_set_transient':
					$this->mo2f_google_auth_set_transient();
					break;
				case 'mo2f_validate_google_authenticator':
					$this->mo2f_validate_google_authenticator();
					break;
			}
		}

		/**
		 * Sets google authenticator transients.
		 *
		 * @return void
		 */
		public function mo2f_google_auth_set_transient() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'edit_users' ) ) {
				wp_send_json_error( 'mo2f-ajax' );
			} else {
				$auth_name  = isset( $_POST['auth_name'] ) ? sanitize_text_field( wp_unslash( $_POST['auth_name'] ) ) : null;
				$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
				if ( MoWpnsConstants::MSFT_AUTH === $auth_name ) {
					$url = isset( $_POST['micro_soft_url'] ) ? sanitize_text_field( wp_unslash( $_POST['micro_soft_url'] ) ) : null;
				} else {
					$url = isset( $_POST['g_auth_url'] ) ? sanitize_text_field( wp_unslash( $_POST['g_auth_url'] ) ) : null;
				}

				MO2f_Utility::mo2f_set_transient( $session_id, 'ga_qrCode', $url );
				wp_send_json_success();

			}
		}

		/**
		 * Validate Google authenticator in dashboard.
		 *
		 * @return void
		 */
		public function mo2f_validate_google_authenticator() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_WHILE_VALIDATING_OTP ) );
			} else {
				$otp_token          = isset( $_POST['otp_token'] ) ? sanitize_text_field( wp_unslash( $_POST['otp_token'] ) ) : null;
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
				$ga_secret          = isset( $_POST['ga_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['ga_secret'] ) ) : ( isset( $_POST['session_id'] ) ? MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'secret_ga' ) : null );

				global $mo2fdb_queries, $user;
				if ( MO2f_Utility::mo2f_check_number_length( $otp_token ) ) {
					$email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
					$user  = wp_get_current_user();
					if ( ! $user->ID ) {
						$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
						$user    = get_user_by( 'id', $user_id );
					}
					$email                  = ( empty( $email ) ) ? $user->user_email : $email;
					$twofactor_transactions = new Mo2fDB();
					$exceeded               = $twofactor_transactions->check_alluser_limit_exceeded( $user->ID );
					if ( $exceeded ) {
						wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::USER_LIMIT_EXCEEDED ) );
					}
					$google_response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_validate_google_auth( $email, $otp_token, $ga_secret ), true );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						if ( MoWpnsConstants::SUCCESS_RESPONSE === $google_response['status'] ) {
							$response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user->ID, true, MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, null ), true );
							if ( json_last_error() === JSON_ERROR_NONE ) {
								if ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
									delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );
									delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
									delete_user_meta( $user->ID, 'mo2f_google_auth' );
									$configured_2fa_method = MoWpnsConstants::GOOGLE_AUTHENTICATOR;
									if ( MO2F_IS_ONPREM ) {
										update_user_meta( $user->ID, 'mo2f_2FA_method_to_configure', $configured_2fa_method );
										$gauth_obj = new Google_Auth_Onpremise();
										$gauth_obj->mo_g_auth_set_secret( $user->ID, $ga_secret );
									}
									update_user_meta( $user->ID, 'mo2f_external_app_type', $configured_2fa_method );
									delete_user_meta( $user->ID, 'mo2f_user_profile_set' );
									wp_send_json_success( $configured_2fa_method . ' has been configured successfully.' );
								}
							}
						}
						wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ) );
					}
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_WHILE_VALIDATING_OTP ) );
				} else {
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::ONLY_DIGITS_ALLOWED ) );
				}
			}
		}

		/** Sends otp for otp based 2FA methods.
		 *
		 * @return void
		 */
		public function mo2f_configure_otp_based_twofa() {
			global $mo2fdb_queries;
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'mo2f-ajax' );
			} else {
				$twofa_method       = isset( $_POST['mo2f_otp_based_method'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_otp_based_method'] ) ) : '';
				$otp_input          = isset( $_POST['mo2f_phone_email_telegram'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_phone_email_telegram'] ) ) : null;
				$session_id_encrypt = isset( $_POST['mo2f_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_session_id'] ) ) : null;
				if ( MO2f_Utility::mo2f_check_empty_or_null( $otp_input ) ) {
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_ENTRY ) );
				}
				$otp_input = str_replace( ' ', '', $otp_input );
				$interface = new MO2f_Cloud_Onprem_Interface();
				$user      = wp_get_current_user();
				if ( ! $user->ID ) {
					$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$user    = get_user_by( 'id', $user_id );
				}
				if ( MoWpnsConstants::OTP_OVER_SMS === $twofa_method ) {
					MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'user_phone', $otp_input );
					update_user_meta( $user->ID, 'user_phone_temp', $otp_input );
					$current_method = 'SMS';
					$content        = json_decode( $interface->send_otp_token( $otp_input, $current_method, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), $user ), true );
				} elseif ( MoWpnsConstants::OTP_OVER_TELEGRAM === $twofa_method ) {
					$content = $interface->send_otp_token( $otp_input, $twofa_method, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), $user );
				} else {
					$error        = false;
					$customer_key = get_site_option( 'mo2f_customerKey' );
					$api_key      = get_site_option( 'mo2f_api_key' );
					update_user_meta( $user->ID, 'mo2f_temp_email', $otp_input );
					if ( ! filter_var( $otp_input, FILTER_VALIDATE_EMAIL ) ) {
						$error = true;
					}
					if ( ! empty( $otp_input ) && ! $error ) {
						$content = $interface->send_otp_token( $otp_input, MoWpnsConstants::OTP_OVER_EMAIL, $customer_key, $api_key, $user );
						$content = json_decode( $content, true );
						if ( 'FAILED' === $content['status'] ) {
							$content = array(
								'status'  => 'ERROR',
								'message' => MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL ),
							);
						}
					} else {
						$content = array(
							'status'  => 'ERROR',
							'message' => 'Invalid email address. Please add the appropriate email.',
						);
					}
				}
				if ( json_last_error() === JSON_ERROR_NONE ) { /* Generate otp token */
					if ( 'ERROR' === $content['status'] ) {
						wp_send_json_error( $content['message'] );
					} elseif ( MoWpnsConstants::SUCCESS_RESPONSE === $content['status'] ) {
						$mo2fdb_queries->insert_user( $user->ID );
						if ( MoWpnsConstants::OTP_OVER_SMS === $twofa_method ) {
							MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'mo2f_transactionId', $content['txId'] );

							update_option( 'mo2f_transactionId', $content['txId'] );
							update_option( 'mo2f_number_of_transactions', MoWpnsUtility::get_mo2f_db_option( 'mo2f_number_of_transactions', 'get_option' ) - 1 );
							$mo2f_sms = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' );
							if ( $mo2f_sms > 0 ) {
								update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', $mo2f_sms - 1 );
							}
							wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::OTP_SENT ) . ' ' . $otp_input . '. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ) );
						} elseif ( MoWpnsConstants::OTP_OVER_TELEGRAM === $twofa_method ) {
							wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::OTP_SENT ) . 'your telegram number. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ) );
						} else {
							MO2f_Utility::mo2f_debug_file( 'OTP has been sent successfully over Email' );
							update_user_meta( $user->ID, 'mo2f_configure_2FA', 1 );
							update_user_meta( $user->ID, 'Mo2fOtpOverEmailtxid', $content['txId'] );
							update_user_meta( $user->ID, 'tempRegEmail', $otp_input );
							wp_send_json_success( 'A 2FA code has been sent successfully on ' . $otp_input . '. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ) );
						}
					} else {
						wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ) );
					}
				} else {
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ) );
				}
			}

		}

		/**
		 * Validates the OTP for OTP Over SMS/Email/Telegram.
		 *
		 * @return void
		 */
		public function mo2f_configure_otp_based_methods_validate() {
			global $mo2fdb_queries;
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'mo2f-ajax' );
			} else {
				$session_id_encrypt = isset( $_POST['mo2f_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_session_id'] ) ) : null;
				$user               = wp_get_current_user();
				if ( ! $user->ID ) {
					$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$user    = get_user_by( 'id', $user_id );
				}
				$twofactor_transactions = new Mo2fDB();
				$exceeded               = $twofactor_transactions->check_alluser_limit_exceeded( $user->ID );
				$interface              = new MO2f_Cloud_Onprem_Interface();
				if ( $exceeded ) {
					update_user_meta( $user->ID, 'mo2f_otp_send_true', true );
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::USER_LIMIT_EXCEEDED ) );
				}
				$otp_token    = '';
				$otp_token    = isset( $_POST['otp_token'] ) ? sanitize_text_field( wp_unslash( $_POST['otp_token'] ) ) : '';
				$twofa_method = isset( $_POST['mo2f_otp_based_method'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_otp_based_method'] ) ) : '';
				$user_phone   = '';
				if ( MO2f_Utility::mo2f_check_empty_or_null( $otp_token ) ) {
					update_user_meta( $user->ID, 'mo2f_otp_send_true', true );
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_ENTRY ) );
				}
				$mo2f_transaction_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_transactionId' );
				if ( MoWpnsConstants::OTP_OVER_SMS === $twofa_method ) {
					$user_phone = get_user_meta( $user->ID, 'user_phone_temp', true );
					$phone      = $mo2fdb_queries->get_user_detail( 'mo2f_user_phone', $user->ID );
					$content    = json_decode( $interface->validate_otp_token( $twofa_method, null, $mo2f_transaction_id, $otp_token, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), $user ), true );
				} elseif ( MoWpnsConstants::OTP_OVER_TELEGRAM === $twofa_method ) {
					$content = $interface->validate_otp_token( $twofa_method, null, $mo2f_transaction_id, $otp_token, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), $user );
				} else {
					$email               = get_user_meta( $user->ID, 'mo2f_temp_email', true );
					$mo2f_transaction_id = get_user_meta( $user->ID, 'mo2f_transactionId', true );
					$content             = json_decode( $interface->validate_otp_token( MoWpnsConstants::OTP_OVER_EMAIL, $email, $mo2f_transaction_id, $otp_token, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), $user ), true );
				}
				if ( 'ERROR' === $content['status'] ) {
					update_user_meta( $user->ID, 'mo2f_otp_send_true', true );
					wp_send_json_error( MoWpnsMessages::lang_translate( $content['message'] ) );
				} elseif ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) {

					if ( MoWpnsConstants::OTP_OVER_SMS === $twofa_method ) {
						if ( $phone && strlen( $phone ) >= 4 ) {
							if ( $user_phone !== $phone ) {
								$mo2fdb_queries->update_user_details( $user->ID, array( 'mobile_registration_status' => false ) );
							}
						}
						$email = get_user_by( 'id', $user->ID )->user_email;
					}
					if ( MoWpnsConstants::OTP_OVER_TELEGRAM === $twofa_method ) {
						if ( isset( $user->ID ) ) {
							$update_details = new Miniorange_Password_2Factor_Login();
							$update_details->mo2fa_update_user_details( $user->ID, true, $twofa_method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $user->user_email, null );
						}
						$response = array( 'status' => 'SUCCESS' );
					} else {
						$response = json_decode( $interface->mo2f_update_user_info( $user->ID, true, $twofa_method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, $user_phone, 'API_2FA', true ), true );
					}

					if ( json_last_error() === JSON_ERROR_NONE ) {
						if ( 'ERROR' === $response['status'] ) {
							update_user_meta( $user->ID, 'mo2f_otp_send_true', true );
							MO2f_Utility::unset_session_variables( 'user_phone' );
							delete_user_meta( $user->ID, 'user_phone_temp' );
							wp_send_json_error( MoWpnsMessages::lang_translate( $response['message'] ) );
						} elseif ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
							delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
							delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );
							if ( MoWpnsConstants::OTP_OVER_SMS === $twofa_method ) {
								MO2f_Utility::unset_session_variables( 'user_phone' );
								delete_user_meta( $user->ID, 'user_phone_temp' );
							} elseif ( MoWpnsConstants::OTP_OVER_TELEGRAM === $twofa_method ) {
								update_user_meta( $user->ID, 'mo2f_chat_id', get_user_meta( $user->ID, 'mo2f_temp_chatID', true ) );
								delete_user_meta( $user->ID, 'mo2f_temp_chatID' );
								delete_user_meta( $user->ID, 'mo2f_otp_token' );
								delete_user_meta( $user->ID, 'mo2f_telegram_time' );
							} else {
								delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
								delete_user_meta( $user->ID, 'test_2FA' );
								delete_user_meta( $user->ID, 'mo2f_temp_email' );
							}
							delete_user_meta( $user->ID, 'mo2f_otp_send_true', true );
							delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
							wp_send_json_success( 'Your 2FA method has been set successfully.' );
						} else {
							update_user_meta( $user->ID, 'mo2f_otp_send_true', true );
							MO2f_Utility::unset_session_variables( 'user_phone' );
							delete_user_meta( $user->ID, 'user_phone_temp' );
							delete_user_meta( $user->ID, 'mo2f_temp_email' );
							wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ) );
						}
					} else {
						update_user_meta( $user->ID, 'mo2f_otp_send_true', true );
						MO2f_Utility::unset_session_variables( 'user_phone' );
						delete_user_meta( $user->ID, 'user_phone_temp' );
						delete_user_meta( $user->ID, 'mo2f_temp_email' );
						wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ) );
					}
				} else {  // OTP Validation failed.
					update_user_meta( $user->ID, 'mo2f_otp_send_true', true );
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ) );
				}
			}
		}

		/**
		 * Enable/disables the login transactions report.
		 *
		 * @return void
		 */
		public function mo2f_enable_transactions_report() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'edit_users' ) ) {
				wp_send_json_error( 'mo2f-ajax' );
			} else {
				$is_transaction_report_enabled = isset( $_POST['mo2f_enable_transaction_report'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_enable_transaction_report'] ) ) : 0;
				update_site_option( 'mo2f_enable_login_report', $is_transaction_report_enabled );
				wp_send_json( $is_transaction_report_enabled );
			}

		}
		/**
		 * Enables/disables the 2fa through user's profile.
		 *
		 * @return void
		 */
		public function mo2f_enable_twofactor_userprofile() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'edit_users' ) ) {
				wp_send_json_error( 'mo2f-ajax' );
			} else {
				$is_userprofile_2fa_enabled = isset( $_POST['is_enabled'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['is_enabled'] ) ) ? 1 : 0;
				wp_send_json( $is_userprofile_2fa_enabled );
			}
		}

		/**
		 * Save settings for grace period feature
		 *
		 * @return void
		 */
		public function mo2f_grace_period_save() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( 'mo2f-ajax' );

			} else {

				$enable = isset( $_POST['mo2f_graceperiod_use'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_graceperiod_use'] ) ) : '';
				if ( 'true' === $enable ) {
					update_option( 'mo2f_grace_period', 'on' );
					$grace_type = isset( $_POST['mo2f_graceperiod_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_graceperiod_hour'] ) ) : '';
					if ( 'true' === $grace_type ) {
						update_option( 'mo2f_grace_period_type', 'hours' );
					} else {
						update_option( 'mo2f_grace_period_type', 'days' );
					}
					if ( isset( $_POST['mo2f_graceperiod_value'] ) && $_POST['mo2f_graceperiod_value'] > 0 && $_POST['mo2f_graceperiod_value'] <= 10 ) {
						update_option( 'mo2f_grace_period_value', sanitize_text_field( wp_unslash( $_POST['mo2f_graceperiod_value'] ) ) );
					} else {
						update_option( 'mo2f_grace_period_value', 1 );
						wp_send_json_error( 'invalid_input' );
					}
				} else {
					update_option( 'mo2f_grace_period', 'off' );

					update_option( 'mo2f_inline_registration', 1 );

				}
				wp_send_json_success( 'true' );
			}
		}

		/**
		 * Function to register customer
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_register_customer( $post ) {
			global $mo2fdb_queries;
			$user    = wp_get_current_user();
			$email   = isset( $post['email'] ) ? sanitize_email( wp_unslash( $post['email'] ) ) : '';
			$company = isset( $_SERVER['SERVER_NAME'] ) ? esc_url_raw( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : null;

			$password         = $post['password'];
			$confirm_password = $post['confirmPassword'];

			if ( strlen( $password ) < 6 || strlen( $confirm_password ) < 6 ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::PASS_LENGTH ) );
			}

			if ( $password !== $confirm_password ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::PASS_MISMATCH ) );
			}
			if ( MoWpnsUtility::check_empty_or_null( $email ) || MoWpnsUtility::check_empty_or_null( $password )
			|| MoWpnsUtility::check_empty_or_null( $confirm_password ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::REQUIRED_FIELDS ) );
			}

			update_option( 'mo2f_email', $email );

			update_option( 'mo_wpns_company', $company );

			update_option( 'mo_wpns_password', $password );

			$customer = new MocURL();
			$content  = json_decode( $customer->check_customer( $email ), true );
			$mo2fdb_queries->insert_user( $user->ID );
			switch ( $content['status'] ) {
				case 'CUSTOMER_NOT_FOUND':
					$customer_key = json_decode( $customer->create_customer( $email, $company, $password ), true );
					$message      = isset( $customer_key['message'] ) ? $customer_key['message'] : __( 'Error occured while creating an account.', 'miniorange-2-factor-authentication' );
					if ( strcasecmp( $customer_key['status'], 'SUCCESS' ) === 0 ) {
						update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to obfuscate the option as it will be stored in database.
						update_option( 'mo2f_email', $email );
						$id         = isset( $customer_key['id'] ) ? $customer_key['id'] : '';
						$api_key    = isset( $customer_key['apiKey'] ) ? $customer_key['apiKey'] : '';
						$token      = isset( $customer_key['token'] ) ? $customer_key['token'] : '';
						$app_secret = isset( $customer_key['appSecret'] ) ? $customer_key['appSecret'] : '';
						$this->mo2f_save_success_customer_config( $email, $id, $api_key, $token, $app_secret );
						$this->mo2f_get_current_customer( $email, $password );
						wp_send_json_success( $message );
					} else {
						wp_send_json_error( $message );
					}

					break;
				case 'SUCCESS':
					update_option( 'mo_2factor_user_registration_status', 'MO_2_FACTOR_VERIFY_CUSTOMER' );
					update_option( 'mo_wpns_verify_customer', 'true' );
					delete_option( 'mo_wpns_new_registration' );
					wp_send_json_error( MoWpnsMessages::show_message( 'ACCOUNT_EXISTS_SETUPWIZARD' ) );
					break;
				case 'ERROR':
					wp_send_json_error( $content['message'] );
					break;
				default:
					$res = $this->mo2f_get_current_customer( $email, $password );
					if ( 'SUCCESS' === $res ) {
						wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::REG_SUCCESS ) );
					}
					$message = __( 'Email is already registered in miniOrange. Please try to login to your account.', 'miniorange-2-factor-authentication' );
					wp_send_json_success( $message );

			}
				$message = __( 'Error Occured while registration', 'miniorange-2-factor-authentication' );
				wp_send_json_error( $message );

		}
		/**
		 * Function to verify customer.
		 *
		 * @param array $post $_POST array.
		 * @return object
		 */
		public function mo2f_verify_customer( $post ) {
			global $mo_wpns_utility;
			$email    = isset( $post['email'] ) ? sanitize_email( wp_unslash( $post['email'] ) ) : '';
			$password = $post['password'];

			if ( $mo_wpns_utility->check_empty_or_null( $email ) || $mo_wpns_utility->check_empty_or_null( $password ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::REQUIRED_FIELDS ) );
			}
			return $this->mo2f_get_current_customer( $email, $password );
		}
		/**
		 * Function to get current customer
		 *
		 * @param string $email User email.
		 * @param string $password User password.
		 * @return void
		 */
		public function mo2f_get_current_customer( $email, $password ) {
			global $mo2fdb_queries;
			$customer     = new MocURL();
			$content      = $customer->get_customer_key( $email, $password );
			$customer_key = json_decode( $content, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $customer_key['status'] ) {
					if ( isset( $customer_key['phone'] ) ) {
						update_option( 'mo_wpns_admin_phone', $customer_key['phone'] );
					}
					update_option( 'mo2f_email', $email );
					$id           = isset( $customer_key['id'] ) ? $customer_key['id'] : '';
					$api_key      = isset( $customer_key['apiKey'] ) ? $customer_key['apiKey'] : '';
					$token        = isset( $customer_key['token'] ) ? $customer_key['token'] : '';
					$app_secret   = isset( $customer_key['appSecret'] ) ? $customer_key['appSecret'] : '';
					$current_user = wp_get_current_user();
					$mo2fdb_queries->insert_user( $current_user->ID );
					$this->mo2f_save_success_customer_config( $email, $id, $api_key, $token, $app_secret );
					update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to obfuscate the option as it will be stored in database.
					$customer_t = new Two_Factor_Setup_Onprem_Cloud();
					$content    = json_decode( $customer_t->get_customer_transactions( get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), 'PREMIUM' ), true );
					if ( 'SUCCESS' === $content['status'] ) {
						update_site_option( 'mo2f_license_type', 'PREMIUM' );
					} else {
						update_site_option( 'mo2f_license_type', 'DEMO' );
						$content = json_decode( $customer_t->get_customer_transactions( get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), 'DEMO' ), true );
					}
					if ( isset( $content['smsRemaining'] ) ) {
						update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', $content['smsRemaining'] );
					} elseif ( 'SUCCESS' === $content['status'] ) {
						update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', 0 );
					}

					if ( isset( $content['emailRemaining'] ) && 10 === (int) $content['emailRemaining'] && get_site_option( 'cmVtYWluaW5nT1RQ' ) > 30 ) {

							update_site_option( 'cmVtYWluaW5nT1RQ', 30 );
					}

					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::REG_SUCCESS ) );
				} else {
					update_option( 'mo_2factor_user_registration_status', 'MO_2_FACTOR_VERIFY_CUSTOMER' );
					update_option( 'mo_wpns_verify_customer', 'true' );
					delete_option( 'mo_wpns_new_registration' );
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::ACCOUNT_EXISTS ) );
				}
			} else {
				$mo2f_message = is_string( $content ) ? $content : '';
				wp_send_json_error( MoWpnsMessages::lang_translate( $mo2f_message ) );
			}
		}

		/**
		 * Function to save configuration of customer.
		 *
		 * @param string $email User email.
		 * @param int    $id User ID.
		 * @param string $api_key API key.
		 * @param string $token token.
		 * @param string $app_secret App secret.
		 * @return void
		 */
		public function mo2f_save_success_customer_config( $email, $id, $api_key, $token, $app_secret ) {
			global $mo2fdb_queries;

			$user = wp_get_current_user();
			update_option( 'mo2f_customerKey', $id );
			update_option( 'mo2f_api_key', $api_key );
			update_option( 'mo2f_customer_token', $token );
			update_option( 'mo2f_app_secret', $app_secret );
			update_option( 'mo_wpns_enable_log_requests', true );
			update_option( 'mo2f_miniorange_admin', $user->ID );
			update_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' );
			update_option( 'mo_2factor_user_registration_status', 'MO_2_FACTOR_PLUGIN_SETTINGS' );

			$mo2fdb_queries->update_user_details(
				$user->ID,
				array(
					'mo2f_user_email'                   => $email,
					'user_registration_with_miniorange' => 'SUCCESS',
				)
			);

			delete_option( 'mo_wpns_verify_customer' );
			delete_option( 'mo_wpns_registration_status' );
			delete_option( 'mo_wpns_password' );
		}

		/**
		 * Function to register and verify customer.
		 *
		 * @return void
		 */
		public function mo_wpns_register_verify_customer() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'mo2f-ajax' );
			}
			$res = '';
			if ( isset( $_POST['Login_and_Continue'] ) && sanitize_text_field( wp_unslash( $_POST['Login_and_Continue'] ) ) === 'Login & Continue' ) {
				$res = $this->mo2f_verify_customer( $_POST );

			} else {
				$res = $this->mo2f_register_customer( $_POST );
			}
			wp_send_json( $res );
		}

		/**
		 * Function to set miniOrange authenticator methods.
		 *
		 * @return void
		 */
		public function mo2f_set_miniorange_methods() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( 'mo2f-ajax' );
			}
			global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
			$transient_id = isset( $_POST['transient_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transient_id'] ) ) : null;
			$user_id      = MO2f_Utility::mo2f_get_transient( $transient_id, 'mo2f_user_id' );
			if ( empty( $user_id ) ) {
				wp_send_json_error( 'UserIdNotFound' );
			}
			$user      = get_user_by( 'id', $user_id );
			$email     = ! empty( $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id ) ) ? $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id ) : $user->user_email;
			$otp_token = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : null;
			$content   = json_decode( $mo2f_onprem_cloud_obj->validate_otp_token( MoWpnsConstants::SOFT_TOKEN, $email, null, $otp_token, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ) ), true );
			wp_send_json_success( $content );
		}
		/**
		 * Function to set OTP over SMS of user.
		 *
		 * @return void
		 */
		public function mo2f_set_otp_over_sms() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( 'mo2f-ajax' );
				exit;
			}
			$is_2fa_enabled = isset( $_POST['is_2fa_enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['is_2fa_enabled'] ) ) : null;
			if ( 'true' !== $is_2fa_enabled ) {
				wp_send_json( '2fadisabled' );
			}
			global $mo2fdb_queries;
			$transient_id = isset( $_POST['transient_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transient_id'] ) ) : null;
			$user_id      = MO2f_Utility::mo2f_get_transient( $transient_id, 'mo2f_user_id' );
			if ( empty( $user_id ) ) {
				wp_send_json_error( 'UserIdNotFound' );
			}
			$new_phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : null;
			$new_phone = str_replace( ' ', '', $new_phone );
			$mo2fdb_queries->update_user_details( $user_id, array( 'mo2f_user_phone' => $new_phone ) );
			$user_phone = $mo2fdb_queries->get_user_detail( 'mo2f_user_phone', $user_id );
			wp_send_json_success( $user_phone );
		}
		/**
		 * Function to set Google Authenticator method of user.
		 *
		 * @return void
		 */
		public function mo2f_set_ga() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( 'mo2f-ajax' );
			}
			include_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
			$transient_id = isset( $_POST['transient_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transient_id'] ) ) : null;
			$user_id      = MO2f_Utility::mo2f_get_transient( $transient_id, 'mo2f_user_id' );
			if ( empty( $user_id ) ) {
				wp_send_json_error( 'UserIdNotFound' );
			}
			$user      = get_user_by( 'id', $user_id );
			$email     = ! empty( $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id ) ) ? $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id ) : $user->user_email;
			$otp_token = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : null;
			$ga_secret = isset( $_POST['ga_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['ga_secret'] ) ) : null;

			$mo2f_onprem_cloud_obj->mo2f_set_gauth_secret( $user_id, $email, $ga_secret );

			$google_response = json_decode( $mo2f_onprem_cloud_obj->mo2f_validate_google_auth( $email, $otp_token, $ga_secret ), true );
			wp_send_json_success( $google_response['status'] );
		}
		/**
		 * Function to redirect user on ajax login.
		 *
		 * @return void
		 */
		public function mo2f_ajax_login_redirect() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'mo2f-ajax' );
			}
			$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : null;
			$password = isset( $_POST['password'] ) ? $_POST['password'] : null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,  WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Password should not be sanitized.
			apply_filters( 'authenticate', null, $username, $password );
		}
		/**
		 * Function to save setings for custom login form.
		 *
		 * @return string
		 */
		public function mo2f_save_custom_form_settings() {
			$custom_form = false;
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json( 'error' );
			}
			if ( ! current_user_can( 'administrator' ) ) {
				wp_send_json( 'error' );
			}
			if ( isset( $_POST['submit_selector'] ) &&
			isset( $_POST['email_selector'] ) &&
			isset( $_POST['authType'] ) &&
			isset( $_POST['customForm'] ) &&
			isset( $_POST['form_selector'] ) &&

			sanitize_text_field( wp_unslash( $_POST['submit_selector'] ) ) !== '' &&
			sanitize_text_field( wp_unslash( $_POST['email_selector'] ) ) !== '' &&
			sanitize_text_field( wp_unslash( $_POST['customForm'] ) ) !== '' &&
			sanitize_text_field( wp_unslash( $_POST['form_selector'] ) ) !== '' ) {
				$submit_selector  = sanitize_text_field( wp_unslash( $_POST['submit_selector'] ) );
				$form_selector    = sanitize_text_field( wp_unslash( $_POST['form_selector'] ) );
				$email_selector   = sanitize_text_field( wp_unslash( $_POST['email_selector'] ) );
				$phone_selector   = isset( $_POST['phone_selector'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_selector'] ) ) : '';
				$auth_type        = sanitize_text_field( wp_unslash( $_POST['authType'] ) );
				$custom_form      = sanitize_text_field( wp_unslash( $_POST['customForm'] ) );
				$enable_shortcode = isset( $_POST['enableShortcode'] ) ? sanitize_text_field( wp_unslash( $_POST['enableShortcode'] ) ) : '';
				$form_submit      = isset( $_POST['formSubmit'] ) ? sanitize_text_field( wp_unslash( $_POST['formSubmit'] ) ) : '';

				switch ( $form_selector ) {
					case '.bbp-login-form':
						update_site_option( 'mo2f_custom_reg_bbpress', true );
						update_site_option( 'mo2f_custom_reg_wocommerce', false );
						update_site_option( 'mo2f_custom_reg_custom', false );
						update_site_option( 'mo2f_custom_reg_pmpro', false );
						break;
					case '.woocommerce-form woocommerce-form-register':
						update_site_option( 'mo2f_custom_reg_bbpress', false );
						update_site_option( 'mo2f_custom_reg_wocommerce', true );
						update_site_option( 'mo2f_custom_reg_custom', false );
						update_site_option( 'mo2f_custom_reg_pmpro', false );
						break;
					case '#pmpro_form':
						update_site_option( 'mo2f_custom_reg_bbpress', false );
						update_site_option( 'mo2f_custom_reg_wocommerce', false );
						update_site_option( 'mo2f_custom_reg_custom', false );
						update_site_option( 'mo2f_custom_reg_pmpro', true );
						update_site_option( 'mo2f_activate_plugin', false );
						break;
					default:
						update_site_option( 'mo2f_custom_reg_bbpress', false );
						update_site_option( 'mo2f_custom_reg_wocommerce', false );
						update_site_option( 'mo2f_custom_reg_custom', true );
						update_site_option( 'mo2f_custom_reg_pmpro', false );
				}

				update_site_option( 'mo2f_custom_form_name', $form_selector );
				update_site_option( 'mo2f_custom_email_selector', $email_selector );
				update_site_option( 'mo2f_custom_phone_selector', $phone_selector );
				update_site_option( 'mo2f_custom_submit_selector', $submit_selector );
				update_site_option( 'mo2f_custom_auth_type', $auth_type );
				update_site_option( 'mo2f_form_submit_after_validation', $form_submit );

				update_site_option( 'enable_form_shortcode', $enable_shortcode );
				$saved = true;
			} else {
				$submit_selector = 'NA';
				$form_selector   = 'NA';
				$email_selector  = 'NA';
				$auth_type       = 'NA';
				$saved           = false;
			}
			$return = array(
				'authType'        => $auth_type,
				'submit'          => $submit_selector,
				'emailSelector'   => $email_selector,
				'phone_selector'  => $phone_selector,
				'form'            => $form_selector,
				'saved'           => $saved,
				'customForm'      => $custom_form,
				'formSubmit'      => $form_submit,
				'enableShortcode' => $enable_shortcode,
			);

			return wp_send_json( $return );
		}
		/**
		 * Function to check if user exists with miniOrange.
		 *
		 * @return void
		 */
		public function mo2f_check_user_exist_miniorange() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				echo 'NonceDidNotMatch';
				exit;
			}

			if ( ! get_option( 'mo2f_customerKey' ) ) {
				echo 'NOTLOGGEDIN';
				exit;
			}
			$user = wp_get_current_user();
			global $mo2fdb_queries;
			$email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
			if ( empty( $email ) || is_null( $email ) ) {
				$email = $user->user_email;
			}

			if ( isset( $_POST['email'] ) ) {
				$email = sanitize_email( wp_unslash( $_POST['email'] ) );
			}

			$enduser    = new Two_Factor_Setup_Onprem_Cloud();
			$check_user = json_decode( $enduser->mo_check_user_already_exist( $email ), true );

			if ( strcasecmp( $check_user['status'], 'USER_FOUND_UNDER_DIFFERENT_CUSTOMER' ) === 0 ) {
				echo 'alreadyExist';
				exit;
			} else {

				update_user_meta( $user->ID, 'mo2f_email_miniOrange', $email );
				echo 'USERCANBECREATED';
				exit;
			}

		}

		// Not in use. Can remove this function.
		/**
		 * Function to shift user to Onpremise.
		 *
		 * @return void
		 */
		public function mo2f_shift_to_onprem() {

			$current_user    = wp_get_current_user();
			$current_user_id = $current_user->ID;
			$miniorange_id   = get_option( 'mo2f_miniorange_admin' );
			if ( is_null( $miniorange_id ) || empty( $miniorange_id ) ) {
				$is_customer_admin = true;
			} else {
				$is_customer_admin = $miniorange_id === $current_user_id;
			}
			if ( $is_customer_admin ) {
				update_option( 'is_onprem', 1 );
				wp_send_json_success();
			} else {
				$admin_user = get_user_by( 'id', $miniorange_id );
				$email      = $admin_user->user_email;
				wp_send_json_error( $email );
			}
		}

		/**
		 * Function to delete generated log file.
		 *
		 * @return void
		 */
		public function mo2f_delete_log_file() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( $error );
			} else {
				$debug_log_path = wp_upload_dir();
				$debug_log_path = $debug_log_path['basedir'];
				$file_name      = 'miniorange_debug_log.txt';
				$status         = file_exists( $debug_log_path . DIRECTORY_SEPARATOR . $file_name );
				if ( $status ) {
					unlink( $debug_log_path . DIRECTORY_SEPARATOR . $file_name );
					wp_send_json_success( 'true' );
				} else {
					wp_send_json_error( 'false' );
				}
			}
		}
		/**
		 * Send json response in ajax calls.
		 *
		 * @param boolean $is_true booloean value.
		 * @param string  $message status message.
		 * @return void
		 */
		public function mo2f_wp_send_json_msg( $is_true, $message ) {
			$is_true ? wp_send_json_success( array( 'message' => $message ) ) : wp_send_json_error( array( 'error' => $message ) );
		}

		/**
		 * Function to enable and disable debug log.
		 *
		 * @param boolean $enable_settings booloean value.
		 * @return void
		 */
		public function mo2f_enable_disable_debug_log( $enable_settings ) {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
			}
			update_site_option( 'mo2f_enable_debug_log', $enable_settings );
			$settings_status = $enable_settings ? MoWpnsMessages::ENABLE : MoWpnsMessages::DISABLE;
			$message         = MoWpnsMessages::PLUGIN_LOG . $settings_status;

			$this->mo2f_wp_send_json_msg( $enable_settings, $message );
		}
		/**
		 * Function to enable and disable 2-factor for users
		 *
		 * @param boolean $enable_settings booloean value.
		 * @return void
		 */
		public function mo2f_enable_disable_twofactor( $enable_settings ) {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'administrator' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( array( 'error' => MoWpnsMessages::UNKNOWN_ERROR ) );
			}
			update_option( 'mo2f_activate_plugin', $enable_settings );
			$settings_status = $enable_settings ? MoWpnsMessages::ENABLE : MoWpnsMessages::DISABLE;
			$message         = MoWpnsMessages::TWO_FA . $settings_status;

			$this->mo2f_wp_send_json_msg( $enable_settings, $message );

		}

		/**
		 * Function to enable or disable inline registration.
		 *
		 * @param boolean $enable_settings booloean value.
		 * @return void
		 */
		public function mo2f_enable_disable_inline( $enable_settings ) {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'error' => MoWpnsMessages::UNKNOWN_ERROR ) );
			}
			update_option( 'mo2f_inline_registration', $enable_settings );
			$settings_status = $enable_settings ? MoWpnsMessages::ENABLE : MoWpnsMessages::DISABLE;
			$message         = MoWpnsMessages::INLINE_2FA . $settings_status;

			$this->mo2f_wp_send_json_msg( $enable_settings, $message );
		}
		/**
		 * Function to enable/disable configured methods
		 *
		 * @param boolean $enable_settings booloean value.
		 * @return void
		 */
		public function mo2f_enable_disable_configurd_methods( $enable_settings ) {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'error' => MoWpnsMessages::UNKNOWN_ERROR ) );
			}

			update_option( 'mo2f_nonce_enable_configured_methods', $enable_settings );
			$settings_status = $enable_settings ? MoWpnsMessages::ENABLE : MoWpnsMessages::DISABLE;
			$message         = MoWpnsMessages::MULTI_FA . $settings_status;

			$this->mo2f_wp_send_json_msg( $enable_settings, $message );
		}
		/**
		 * Function for role based 2-factor settings.
		 *
		 * @return void
		 */
		public function mo2f_role_based_2_factor() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error();
			}
			global $wp_roles;
			foreach ( $wp_roles->role_names as $id => $name ) {
				update_option( 'mo2fa_' . $id, 0 );
			}
			if ( isset( $_POST['enabledrole'] ) ) {
				$enabledrole = isset( $_POST['enabledrole'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['enabledrole'] ) ) : null;
			} else {
				$enabledrole = array();
			}

			foreach ( $enabledrole as $role ) {
				update_option( $role, 1 );
			}
			wp_send_json_success();
		}

		// Not in use. Can remove this.
		/**
		 * Function to check if customer is admin.
		 *
		 * @return void
		 */
		public function mo2f_single_user() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				echo 'NonceDidNotMatch';
				exit;
			} else {
				$current_user      = wp_get_current_user();
				$current_user_id   = $current_user->ID;
				$miniorange_id     = get_option( 'mo2f_miniorange_admin' );
				$is_customer_admin = $miniorange_id === $current_user_id ? true : false;

				if ( is_null( $miniorange_id ) || empty( $miniorange_id ) ) {
					$is_customer_admin = true;
				}

				if ( $is_customer_admin ) {
					update_option( 'is_onprem', 0 );
					wp_send_json( 'true' );
				} else {
					$admin_user = get_user_by( 'id', $miniorange_id );
					$email      = $admin_user->user_email;
					wp_send_json( $email );
				}
			}
		}

		// Not in use. Can remove this.
		/**
		 * Function to check if On-premise is active or not.
		 *
		 * @return void
		 */
		public function mo2f_unlimitted_user() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_success();
			} else {
				if ( isset( $_POST['enableOnPremise'] ) && sanitize_text_field( wp_unslash( $_POST['enableOnPremise'] ) ) === 'on' ) {
					global $wp_roles;
					foreach ( $wp_roles->role_names as $id => $name ) {
						add_site_option( 'mo2fa_' . $id, 1 );
						if ( 'administrator' === $id ) {
							add_option( 'mo2fa_' . $id . '_login_url', admin_url() );
						} else {
							add_option( 'mo2fa_' . $id . '_login_url', home_url() );
						}
					}
					wp_send_json_success( 'OnPremiseActive' );
				} else {
					wp_send_json_success( 'OnPremiseDeactive' );
				}
			}
		}
		/**
		 * Function to save email verification settings.
		 *
		 * @return void
		 */
		public function mo2f_save_email_verification() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( ' An unknown error has occured.' );
			} else {
				global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
				$user_id                = get_current_user_id();
				$twofactor_transactions = new Mo2fDB();
				$exceeded               = $twofactor_transactions->check_alluser_limit_exceeded( $user_id );

				if ( $exceeded ) {
					wp_send_json_error( ' Your limit of 3 users has exceeded. Please upgrade to premium plans to setup 2FA for more users.' );
				}
				$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : null;
				$error = false;

				$customer_key            = get_site_option( 'mo2f_customerKey' );
				$api_key                 = get_site_option( 'mo2f_api_key' );
				$cm_vt_y_wlua_w5n_t1_r_q = MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
				if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
					$error = true;
				}
				if ( ! empty( $email ) && ! $error ) {
					update_user_meta( $user_id, 'tempEmail', $email );
					$content = json_decode( $mo2f_onprem_cloud_obj->send_otp_token( $email, 'OUT OF BAND EMAIL', $customer_key, $api_key ), true );
					if ( 'FAILED' === $content['status'] ) {
						$error_message = ( $cm_vt_y_wlua_w5n_t1_r_q > 0 ) ? ' Please set up SMTP for your website to receive emails and prevent the accidental lock out.' : ' It seems your email transactions are exhausted. Please check your email transactions on \'My Account\' page.';
						wp_send_json_error( $error_message );
					} else {
						update_user_meta( $user_id, 'tempRegEmail', $email );
						wp_send_json_success( 'settingsSaved' );
					}
				} else {
					wp_send_json_error( ' Invalid Email.' );
				}
			}

		}
		/**
		 * Function to check email verification status.
		 *
		 * @return void
		 */
		public function check_email_verification_status() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'ERROR' );
			}
			if ( isset( $_POST['txId'] ) ) {
				$txid   = isset( $_POST['txId'] ) ? sanitize_text_field( wp_unslash( $_POST['txId'] ) ) : null;
				$status = get_site_option( $txid );
				if ( 1 === (int) $status || 0 === (int) $status ) {
					delete_site_option( $txid );
				}
				wp_send_json_success( $status );
			}
			echo 'empty txId';
			exit;
		}


	}
	new Mo_2f_Ajax();
}

