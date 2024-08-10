<?php
/** It enables user to log in through mobile authentication as an additional layer of security over password.
 *
 * @package        miniorange-2-factor-authentication/handler/twofa
 * @license        http://www.gnu.org/copyleft/gpl.html MIT/Expat, see LICENSE.php
 */

namespace TwoFA\Onprem;

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Handler\Miniorange_Mobile_Login;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Database\Mo2fDB;
use TwoFA\Onprem\MO2f_Cloud_Onprem_Interface;
use TwoFA\Cloud\Customer_Cloud_Setup;
use WP_Error;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */
require 'class-miniorange-mobile-login.php';

if ( ! class_exists( 'Miniorange_Password_2Factor_Login' ) ) {
	/**
	 * Class will help to set two factor on login
	 */
	class Miniorange_Password_2Factor_Login {

		/**
		 *  It will store the KBA Question
		 *
		 * @var string .
		 */
		private $mo2f_kbaquestions;

		/**
		 * For user id variable
		 *
		 * @var string
		 */
		private $mo2f_user_id;

		/**
		 * It will strore the transaction id
		 *
		 * @var string .
		 */
		private $mo2f_transactionid;

		/**
		 * First 2FA
		 *
		 * @var string .
		 */
		private $fstfactor;

		/**
		 * Class Mo2f_Cloud_Onprem_Interface object
		 *
		 * @var object
		 */
		private $mo2f_onprem_cloud_obj;

		/**
		 * Constructor of the class
		 */
		public function __construct() {
			$this->mo2f_onprem_cloud_obj = MO2f_Cloud_Onprem_Interface::instance();
		}

		/**
		 * This function will invoke to prompt 2fa on login
		 *
		 * @return null
		 */
		public function mo2f_inline_login() {
			global $mo_wpns_utility;
			$nonce = isset( $_POST['mo2f_inline_nonce'] ) ? sanitize_key( $_POST['mo2f_inline_nonce'] ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mo2f-inline-login-nonce' ) ) {
				$error = new WP_Error();
				return $error;
			}
			$email              = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			$password           = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
			$session_id_encrypt = isset( $_POST['session_id'] ) ? wp_unslash( $_POST['session_id'] ) : null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
			$redirect_to        = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			if ( $mo_wpns_utility->check_empty_or_null( $email ) || $mo_wpns_utility->check_empty_or_null( $password ) ) {
				$login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::REQUIRED_FIELDS );
				$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
				$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
				return;
			}
			$this->mo2f_inline_get_current_customer( $user_id, $email, $password, $redirect_to, $session_id_encrypt );
		}
		/**
		 * This function will help you to register 2fa on login
		 *
		 * @return object
		 */
		public function mo2f_inline_register() {
			global $mo_wpns_utility, $mo2fdb_queries;
			$nonce = isset( $_POST['mo2f_inline_register_nonce'] ) ? sanitize_key( $_POST['mo2f_inline_register_nonce'] ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mo2f-inline-register-nonce' ) ) {
				$error = new WP_Error();
				return $error;
			}

			$email              = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			$company            = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
			$password           = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
			$confirm_password   = isset( $_POST['confirmPassword'] ) ? wp_unslash( $_POST['confirmPassword'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
			$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

			$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
			if ( strlen( $password ) < 6 || strlen( $confirm_password ) < 6 ) {
				$login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::PASS_LENGTH );
				$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
				$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
				return;

			}
			if ( $password !== $confirm_password ) {
				$login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::PASS_MISMATCH );
				$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
				$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
				return;

			}
			if ( MoWpnsUtility::check_empty_or_null( $email ) || MoWpnsUtility::check_empty_or_null( $password )
				|| MoWpnsUtility::check_empty_or_null( $confirm_password ) ) {
				$login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::REQUIRED_FIELDS );
				$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
				$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
				return;

			}

			update_option( 'mo2f_email', $email );

			update_option( 'mo_wpns_company', $company );

			update_option( 'mo_wpns_password', $password );

			$customer = new MocURL();
			$content  = json_decode( $customer->check_customer( $email ), true );
			$mo2fdb_queries->insert_user( $user_id );
			switch ( $content['status'] ) {
				case 'CUSTOMER_NOT_FOUND':
					$customer_key  = json_decode( $customer->create_customer( $email, $company, $password ), true );
					$login_message = isset( $customer_key['message'] ) ? $customer_key['message'] : __( 'Error occured while creating an account.', 'miniorange-2-factor-authentication' );

					if ( strcasecmp( $customer_key['status'], 'SUCCESS' ) === 0 ) {
						$id         = isset( $customer_key['id'] ) ? $customer_key['id'] : '';
						$api_key    = isset( $customer_key['apiKey'] ) ? $customer_key['apiKey'] : '';
						$token      = isset( $customer_key['token'] ) ? $customer_key['token'] : '';
						$app_secret = isset( $customer_key['appSecret'] ) ? $customer_key['appSecret'] : '';
						$this->mo2f_inline_save_success_customer_config( $user_id, $email, $id, $api_key, $token, $app_secret );
						$this->mo2f_inline_get_current_customer( $user_id, $email, $password, $redirect_to, $session_id_encrypt );
						return;
					} else {
						$login_status = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
						$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
						return;
					}
					break;
				case 'SUCCESS':
					$login_message = MoWpnsMessages::show_message( 'ACCOUNT_EXISTS_SETUPWIZARD' );
					$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
					return;

				case 'ERROR':
					$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$login_message = $content['message'];
					$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
					return;
				default:
					$this->mo2f_inline_get_current_customer( $user_id, $email, $password, $redirect_to, $session_id_encrypt );
					return;
			}
			$login_message = __( 'Error Occured while registration', 'miniorange-2-factor-authentication' );
			$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
			$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
		}
		/**
		 * It is to download the backup code
		 *
		 * @return string
		 */
		public function mo2f_download_backup_codes_inline() {
			$nonce   = isset( $_POST['mo2f_inline_backup_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_inline_backup_nonce'] ) ) : '';
			$backups = isset( $_POST['mo2f_inline_backup_codes'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_inline_backup_codes'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-backup-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} else {
				$codes      = explode( ',', $backups );
				$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
				$id         = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id, 'mo2f_current_user_id' );

				MO2f_Utility::mo2f_download_backup_codes( $id, $codes );
			}
		}
		/**
		 * This function will redirect to wp dashboard
		 *
		 * @return string
		 */
		public function mo2f_goto_wp_dashboard() {

			global $mo2fdb_queries;
			$nonce = isset( $_POST['mo2f_inline_wp_dashboard_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_inline_wp_dashboard_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-wp-dashboard-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} else {
				$pass2fa = new Miniorange_Password_2Factor_Login();
				$pass2fa->mo2fa_pass2login( isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '', isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '' );
				exit;
			}
		}
		/**
		 * This will validate or Use the backcode
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function mo2f_use_backup_codes( $posted ) {

			$nonce = sanitize_text_field( $posted['miniorange_backup_nonce'] );
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-backup-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt  = isset( $posted['session_id'] ) ? sanitize_text_field( $posted['session_id'] ) : null;
				$redirect_to         = isset( $posted['redirect_to'] ) ? esc_url_raw( $posted['redirect_to'] ) : null;
				$mo2fa_login_message = __( 'Please provide your backup codes.', 'miniorange-2-factor-authentication' );
				$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_BACKUP';
				$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
			}
		}
		/**
		 * This function will invoke for back up code validation
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_backup_codes_validation( $posted ) {
			global $mo2fdb_queries;
			$nonce              = sanitize_text_field( $posted['miniorange_validate_backup_nonce'] );
			$session_id_encrypt = isset( $posted['session_id'] ) ? sanitize_text_field( $posted['session_id'] ) : null;
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-validate-backup-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$currentuser_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
				$redirect_to    = isset( $posted['redirect_to'] ) ? esc_url_raw( $posted['redirect_to'] ) : null;
				if ( isset( $currentuser_id ) ) {
					if ( MO2f_Utility::mo2f_check_empty_or_null( $posted['mo2f_backup_code'] ) ) {
						$mo2fa_login_message = __( 'Please provide backup code.', 'miniorange-2-factor-authentication' );
						$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_BACKUP';
						$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
					}
					$backup_codes     = get_user_meta( $currentuser_id, 'mo2f_backup_codes', true );
					$mo2f_backup_code = sanitize_text_field( $posted['mo2f_backup_code'] );
					$mo2f_user_email  = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $currentuser_id );

					if ( ! empty( $backup_codes ) ) {
						$mo2f_backup_code = md5( $mo2f_backup_code );
						if ( in_array( $mo2f_backup_code, $backup_codes, true ) ) {
							foreach ( $backup_codes as $key => $value ) {
								if ( $value === $mo2f_backup_code ) {
									unset( $backup_codes[ $key ] );
									update_user_meta( $currentuser_id, 'mo2f_backup_codes', $backup_codes );
									$mo2fdb_queries->delete_user_details( $currentuser_id );
									$mo2fdb_queries->insert_user( $currentuser_id );
									$mo2fdb_queries->update_user_details( $currentuser_id, array( 'user_registration_with_miniorange' => 'SUCCESS' ) );
									$mo2fa_login_message = 'Please configure your 2FA again so that you can avoid being locked out.';
									$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
									$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
								}
							}
						} else {
							$mo2fa_login_message = __( 'The code you provided is already used or incorrect.', 'miniorange-2-factor-authentication' );
							$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_BACKUP';
							$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
						}
					} else {
						if ( isset( $mo2f_backup_code ) ) {
							$generate_backup_code = new MocURL();
							$data                 = $generate_backup_code->mo2f_validate_backup_codes( $mo2f_backup_code, $mo2f_user_email );
							if ( 'success' === $data ) {
								$mo2f_delete_details = new Miniorange_Authentication();
								$mo2f_delete_details->mo2f_delete_user( $currentuser_id );
								$this->miniorange_initiate_2nd_factor( get_user_by( 'id', $currentuser_id ), $redirect_to, $session_id_encrypt );
							} elseif ( 'error_in_validation' === $data ) {
								$mo2fa_login_message = __( 'Error occurred while validating the backup codes.', 'miniorange-2-factor-authentication' );
								$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_BACKUP';
								$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
							} elseif ( 'used_code' === $data ) {
								$mo2fa_login_message = __( 'The code you provided is already used or incorrect.', 'miniorange-2-factor-authentication' );
								$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_BACKUP';
								$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
							} elseif ( 'total_code_used' === $data ) {
								$mo2fa_login_message = __( 'You have used all the backup codes. Please contact <a href="mailto:mfasupport@xecurify.com">2fasupport@xecurify.com</a>', 'miniorange-2-factor-authentication' );
								$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_BACKUP';
								$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
							} elseif ( 'backup_code_not_generated' === $data ) {
								$mo2fa_login_message = __( 'Backup code has not generated for you.', 'miniorange-2-factor-authentication' );
								$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_BACKUP';
								$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
							} elseif ( 'TokenNotFound' === $data ) {
								$mo2fa_login_message = __( 'Validation request authentication failed' );
								$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_BACKUP';
								$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
							} elseif ( 'DBConnectionerror' === $data ) {
								$mo2fa_login_message = __( 'Error occurred while establising connection.', 'miniorange-2-factor-authentication' );
								$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_BACKUP';
								$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
							} elseif ( 'missingparameter' === $data ) {
								$mo2fa_login_message = __( 'Some parameters are missing while validating backup codes.' );
								$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_BACKUP';
								$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
							} else {
								$current_user = get_userdata( $currentuser_id );
								if ( in_array( 'administrator', $current_user->roles, true ) ) {
									$mo2fa_login_message = __( 'Error occured while connecting to server. Please follow the <a href="https://faq.miniorange.com/knowledgebase/i-am-locked-cant-access-my-account-what-do-i-do/" target="_blank">Locked out guide</a> to get immediate access to your account.', 'miniorange-2-factor-authentication' );
								} else {
									$mo2fa_login_message = __( 'Error occured while connecting to server. Please contact your administrator.', 'miniorange-2-factor-authentication' );
								}
								$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_BACKUP';
								$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
							}
						} else {
							$mo2fa_login_message = __( 'Please enter backup code.', 'miniorange-2-factor-authentication' );
							$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_BACKUP';
							$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
						}
					}
				} else {
					$this->remove_current_activity( $session_id_encrypt );
					return new WP_Error( 'invalid_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Please try again..', 'miniorange-2-factor-authentication' ) );
				}
			}
		}
		/**
		 * This function will help for generating the backupcode
		 *
		 * @return string
		 */
		public function mo2f_create_backup_codes() {
			$nonce = isset( $_POST['miniorange_generate_backup_nonce'] ) ? sanitize_key( wp_unslash( $_POST['miniorange_generate_backup_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-generate-backup-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} else {
				global $mo2fdb_queries;

				$redirect_to     = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
				$session_id      = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
				$id              = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id, 'mo2f_current_user_id' );
				$mo2f_user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $id );
				if ( empty( $mo2f_user_email ) ) {
					$currentuser     = get_user_by( 'id', $id );
					$mo2f_user_email = $currentuser->user_email;
				}
				$generate_backup_code = new MocURL();
				$codes                = $generate_backup_code->mo_2f_generate_backup_codes( $mo2f_user_email, site_url() );

				if ( 'InternetConnectivityError' === $codes ) {
					$mo2fa_login_message = 'Error in sending backup codes.';
					$mo2fa_login_status  = isset( $_POST['login_status'] ) ? sanitize_text_field( wp_unslash( $_POST['login_status'] ) ) : '';
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
				} elseif ( 'AllUsed' === $codes ) {
					$mo2fa_login_message = 'You have already used all the backup codes for this user and domain.';
					$mo2fa_login_status  = sanitize_text_field( wp_unslash( $_POST['login_status'] ) );
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
				} elseif ( 'UserLimitReached' === $codes ) {
					$mo2fa_login_message = 'Backup code generation limit has reached for this domain.';
					$mo2fa_login_status  = sanitize_text_field( wp_unslash( $_POST['login_status'] ) );
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
				} elseif ( 'LimitReached' === $codes ) {
					$mo2fa_login_message = 'backup code generation limit has reached for this user.';
					$mo2fa_login_status  = sanitize_text_field( wp_unslash( $_POST['login_status'] ) );
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
				} elseif ( 'invalid_request' === $codes ) {
					$mo2fa_login_message = 'Invalid request.';
					$mo2fa_login_status  = sanitize_text_field( wp_unslash( $_POST['login_status'] ) );
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
				}
				$codes = explode( ' ', $codes );

				$mo2f_user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $id );
				if ( empty( $mo2f_user_email ) ) {
					$currentuser     = get_user_by( 'id', $id );
					$mo2f_user_email = $currentuser->user_email;
				}
				$result = MO2f_Utility::mo2f_email_backup_codes( $codes, $mo2f_user_email );

				if ( $result ) {
					$mo2fa_login_message = 'An email containing the backup codes has been sent. Please click on Use backup codes to login using the backup codes.';
					update_user_meta( $id, 'mo_backup_code_generated', 1 );
				} else {
					$mo2fa_login_message = " If you haven\'t configured SMTP, please set your SMTP to get the backup codes on email.";
					update_user_meta( $id, 'mo_backup_code_generated', 0 );
				}

				$mo2fa_login_status = sanitize_text_field( wp_unslash( $_POST['login_status'] ) );

				$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
			}
		}
		/**
		 * Resend the OTP on the provided phone number
		 *
		 * @return mixed
		 */
		public function mo2f_resend_otp_nonce() {
			$nonce = isset( $_POST['mo2f_resend_otp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_resend_otp_nonce'] ) ) : null;
			if ( ! wp_verify_nonce( $nonce, 'mo2f_resend_otp_nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html_e( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html_e( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
				$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
				$currentuser        = get_user_by( 'id', $user_id );
				$redirect_to        = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
				update_user_meta( $user_id, 'resend_otp', true );
				$this->miniorange_initiate_2nd_factor( $currentuser, $redirect_to, $session_id_encrypt, false );
			}
		}
		/**
		 * It is for getting the user id or current customer
		 *
		 * @param string $user_id  It will carry the user id.
		 * @param string $email It will carry the email address.
		 * @param string $password It will store the password .
		 * @param string $redirect_to It will carry the redirect url.
		 * @param string $session_id_encrypt  It will carry the session id.
		 * @return void
		 */
		public function mo2f_inline_get_current_customer( $user_id, $email, $password, $redirect_to, $session_id_encrypt ) {
			global $mo2fdb_queries;
			$customer     = new MocURL();
			$content      = $customer->get_customer_key( $email, $password );
			$customer_key = json_decode( $content, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $customer_key['status'] ) {
					if ( isset( $customer_key['phone'] ) ) {
						update_option( 'mo_wpns_admin_phone', $customer_key['phone'] );
						$mo2fdb_queries->update_user_details( $user_id, array( 'mo2f_user_phone' => $customer_key['phone'] ) );
					}
					update_option( 'mo2f_email', $email );
					$id         = isset( $customer_key['id'] ) ? $customer_key['id'] : '';
					$api_key    = isset( $customer_key['apiKey'] ) ? $customer_key['apiKey'] : '';
					$token      = isset( $customer_key['token'] ) ? $customer_key['token'] : '';
					$app_secret = isset( $customer_key['appSecret'] ) ? $customer_key['appSecret'] : '';
					$this->mo2f_inline_save_success_customer_config( $user_id, $email, $id, $api_key, $token, $app_secret );
					$login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::REG_SUCCESS );
					$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
					return;
				} else {
					$mo2fdb_queries->update_user_details( $user_id, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_VERIFY_CUSTOMER' ) );
					$login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::ACCOUNT_EXISTS );
					$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
					return;
				}
			} else {
				$login_message = is_string( $content ) ? $content : '';
				$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
				$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
				return;
			}

		}
		/**
		 * It is to save the inline settings
		 *
		 * @param string $user_id It will carry the user id .
		 * @param string $email It will carry the email .
		 * @param string $id It will carry the id .
		 * @param string $api_key It will carry the api key .
		 * @param string $token It will carry the token value .
		 * @param string $app_secret It will carry the secret data .
		 * @return void
		 */
		public function mo2f_inline_save_success_customer_config( $user_id, $email, $id, $api_key, $token, $app_secret ) {
			global $mo2fdb_queries;
			update_option( 'mo2f_customerKey', $id );
			update_option( 'mo2f_api_key', $api_key );
			update_option( 'mo2f_customer_token', $token );
			update_option( 'mo2f_app_secret', $app_secret );
			update_option( 'mo_wpns_enable_log_requests', true );
			update_option( 'mo2f_miniorange_admin', $id );
			update_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' );
			update_option( 'mo_2factor_user_registration_status', 'MO_2_FACTOR_PLUGIN_SETTINGS' );
			$mo2fdb_queries->update_user_details(
				$user_id,
				array(
					'mo2f_user_email' => sanitize_email( $email ),
				)
			);
		}

		/**
		 * It is to validate the otp in inline
		 *
		 * @return string
		 */
		public function mo2f_inline_validate_otp_complete() {
			if ( isset( $_POST['miniorange_inline_validate_otp_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['miniorange_inline_validate_otp_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-validate-otp-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					$redirect_to        = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$mo2fa_login_status = 'MO_2_FACTOR_SETUP_SUCCESS';
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, '', $redirect_to, null, $session_id_encrypt );
				}
			}
		}

		/**
		 * It is for validating the kba
		 *
		 * @return string
		 */
		public function mo2f_inline_validate_kba() {
			if ( isset( $_POST['mo2f_inline_save_kba_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['mo2f_inline_save_kba_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-save-kba-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					$this->miniorange_pass2login_start_session();
					$session_id_encrypt  = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					$redirect_to         = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$mo2fa_login_message = '';
					$mo2fa_login_status  = isset( $_POST['mo2f_inline_kba_status'] ) ? 'MO_2_FACTOR_SETUP_SUCCESS' : 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';

					$kba_ques_ans = array(
						'kba_q1' => 'mo2f_kbaquestion_1',
						'kba_a1' => 'mo2f_kba_ans1',
						'kba_q2' => 'mo2f_kbaquestion_2',
						'kba_a2' => 'mo2f_kba_ans2',
						'kba_q3' => 'mo2f_kbaquestion_3',
						'kba_a3' => 'mo2f_kba_ans3',
					);
					foreach ( $kba_ques_ans as $key => $value ) {

						$kba_ques_ans[ $key ] = isset( $_POST[ $value ] ) ? sanitize_text_field( wp_unslash( $_POST[ $value ] ) ) : '';
					}
					$temp_array    = array( $kba_ques_ans['kba_q1'], $kba_ques_ans['kba_q2'], $kba_ques_ans['kba_q3'] );
					$kba_questions = array();
					foreach ( $temp_array as $question ) {
						if ( MO2f_Utility::mo2f_check_empty_or_null( $question ) ) {
							$mo2fa_login_message = __( 'All the fields are required. Please enter valid entries.', 'miniorange-2-factor-authentication' );
							$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
						} else {
							$ques = sanitize_text_field( $question );
							$ques = addcslashes( stripslashes( $ques ), '"\\' );
							array_push( $kba_questions, $ques );
						}
					}
					if ( ! ( array_unique( $kba_questions ) === $kba_questions ) ) {
						$mo2fa_login_message = __( 'The questions you select must be unique.', 'miniorange-2-factor-authentication' );
						$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
					}
					$temp_array_ans = array( $kba_ques_ans['kba_a1'], $kba_ques_ans['kba_a2'], $kba_ques_ans['kba_a3'] );
					$kba_answers    = array();
					foreach ( $temp_array_ans as $answer ) {
						if ( MO2f_Utility::mo2f_check_empty_or_null( $answer ) ) {
							$mo2fa_login_message = __( 'All the fields are required. Please enter valid entries.', 'miniorange-2-factor-authentication' );
							$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
						} else {
							$ques   = sanitize_text_field( $answer );
							$answer = strtolower( $answer );
							array_push( $kba_answers, $answer );
						}
					}
					$size         = count( $kba_questions );
					$kba_q_a_list = array();
					for ( $c = 0; $c < $size; $c++ ) {
						array_push( $kba_q_a_list, $kba_questions[ $c ] );
						array_push( $kba_q_a_list, $kba_answers[ $c ] );
					}

					$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					$current_user       = get_user_by( 'id', $user_id );
					$email              = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $current_user->ID );
					$mo2fa_login_status = 'MO_2_FACTOR_SETUP_SUCCESS';
					delete_user_meta( $user_id, 'mo2f_user_profile_set' );
					$mo2fdb_queries->update_user_details(
						$current_user->ID,
						array(
							'mo2f_SecurityQuestions_config_status' => true,
							'mo_2factor_user_registration_status'  => 'MO_2_FACTOR_PLUGIN_SETTINGS',
						)
					);
					$kba_q1          = $kba_q_a_list[0];
					$kba_a1          = md5( $kba_q_a_list[1] );
					$kba_q2          = $kba_q_a_list[2];
					$kba_a2          = md5( $kba_q_a_list[3] );
					$kba_q3          = $kba_q_a_list[4];
					$kba_a3          = md5( $kba_q_a_list[5] );
					$question_answer = array(
						$kba_q1 => $kba_a1,
						$kba_q2 => $kba_a2,
						$kba_q3 => $kba_a3,
					);
					update_user_meta( $current_user->ID, 'mo2f_kba_challenge', $question_answer );

					$this->mo2f_inline_kba_validation( $_POST, $user_id, $email );

					if ( ! isset( $_POST['mo2f_inline_kba_status'] ) ) {
						update_user_meta( $current_user->ID, 'mo2f_2FA_method_to_configure', MoWpnsConstants::SECURITY_QUESTIONS );
						$mo2fdb_queries->update_user_details( $current_user->ID, array( 'mo2f_configured_2fa_method' => MoWpnsConstants::SECURITY_QUESTIONS ) );
					}
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}

		/**
		 * Cloud flow for validating KBA
		 *
		 * @param array   $post $_POST data.
		 * @param integer $user_id user id.
		 * @param string  $email user email.
		 * @return mixed
		 */
		public function mo2f_inline_kba_validation( $post, $user_id, $email ) {
			global $mo2f_onprem_cloud_obj;
			$kba_ques_ans    = $this->mo2f_get_kba_details( $post );
			$kba_reg_reponse = json_decode( $mo2f_onprem_cloud_obj->mo2f_register_kba_details( $email, $kba_ques_ans['kba_q1'], $kba_ques_ans['kba_a1'], $kba_ques_ans['kba_q2'], $kba_ques_ans['kba_a2'], $kba_ques_ans['kba_q3'], $kba_ques_ans['kba_a3'], $user_id ), true );

			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $kba_reg_reponse['status'] ) {
					$response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_update_user_info( null, null, MoWpnsConstants::SECURITY_QUESTIONS, null, null, null, $email ), true );
				}
			}
			return $response;
		}

		/**
		 * Gets kba details
		 *
		 * @param mixed $post Post data.
		 * @return array
		 */
		public function mo2f_get_kba_details( $post ) {
			$kba_ques_ans = array(
				'kba_q1' => 'mo2f_kbaquestion_1',
				'kba_a1' => 'mo2f_kba_ans1',
				'kba_q2' => 'mo2f_kbaquestion_2',
				'kba_a2' => 'mo2f_kba_ans2',
				'kba_q3' => 'mo2f_kbaquestion_3',
				'kba_a3' => 'mo2f_kba_ans3',
			);
			foreach ( $kba_ques_ans as $key => $value ) {

				$kba_ques_ans[ $key ] = isset( $post[ $value ] ) ? sanitize_text_field( wp_unslash( $post[ $value ] ) ) : '';
			}
			foreach ( $kba_ques_ans as $key => $value ) {

				$kba_ques_ans[ $key ] = addcslashes( stripslashes( $value ), '"\\' );
			}
			return $kba_ques_ans;
		}

		/**
		 * Validating the mobile authentication
		 *
		 * @return string
		 */
		public function mo2f_inline_validate_mobile_authentication() {
			if ( isset( $_POST['mo_auth_inline_mobile_registration_complete_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['mo_auth_inline_mobile_registration_complete_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-mobile-registration-complete-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					$this->miniorange_pass2login_start_session();
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );
					$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					$redirect_to             = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$selected_2factor_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2fa_method', $user_id );
					$email                   = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id );
					$mo2fa_login_message     = '';
					$mo2fa_login_status      = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$enduser                 = new MO2f_Cloud_Onprem_Interface();
					$response                = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user_id, true, $selected_2factor_method, null, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, null ), true );

					if ( JSON_ERROR_NONE === json_last_error() ) { /* Generate Qr code */
						if ( 'ERROR' === $response['status'] ) {
							$mo2fa_login_message = MoWpnsMessages::lang_translate( $response['message'] );
						} elseif ( 'SUCCESS' === $response['status'] ) {
							$mo2fa_login_status = 'MO_2_FACTOR_SETUP_SUCCESS';
						} else {
							$mo2fa_login_message = __( 'An error occured while validating the user. Please Try again.', 'miniorange-2-factor-authentication' );
						}
					} else {
						$mo2fa_login_message = __( 'Invalid request. Please try again', 'miniorange-2-factor-authentication' );
					}
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}
		/**
		 * This function will invoke the duo push notification
		 *
		 * @return string
		 */
		public function mo2f_duo_mobile_send_push_notification_for_inline_form() {
			if ( isset( $_POST['duo_mobile_send_push_notification_inline_form_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['duo_mobile_send_push_notification_inline_form_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mo2f-send-duo-push-notification-inline-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					$this->miniorange_pass2login_start_session();
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );
					$user_id     = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;

					$mo2fdb_queries->update_user_details(
						$user_id,
						array(
							'mobile_registration_status' => true,
						)
					);
					$mo2fa_login_message = '';

					$mo2fa_login_status = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';

					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}
		/**
		 * This function will invoke on duo authentication validation
		 *
		 * @return string
		 */
		public function mo2f_inline_validate_duo_authentication() {
			if ( isset( $_POST['mo_auth_inline_duo_auth_mobile_registration_complete_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['mo_auth_inline_duo_auth_mobile_registration_complete_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-duo_auth-registration-complete-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					$this->miniorange_pass2login_start_session();
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );
					$user_id                 = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$redirect_to             = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$selected_2factor_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2fa_method', $user_id );
					$email                   = sanitize_email( $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id ) );
					$mo2fdb_queries->update_user_details(
						$user_id,
						array(
							'mobile_registration_status' => true,
						)
					);
					$mo2fa_login_message = '';

					include_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two_fa_duo_handler.php';
					$ikey = get_site_option( 'mo2f_d_integration_key' );
					$skey = get_site_option( 'mo2f_d_secret_key' );
					$host = get_site_option( 'mo2f_d_api_hostname' );

					$duo_preauth = preauth( $email, true, $skey, $ikey, $host );

					if ( isset( $duo_preauth['response']['stat'] ) && 'OK' === $duo_preauth['response']['stat'] ) {
						if ( isset( $duo_preauth['response']['response']['status_msg'] ) && 'Account is active' === $duo_preauth['response']['response']['status_msg'] ) {
							$mo2fa_login_message = $email . ' user is already exists, please go for step B duo will send push notification on your configured mobile.';
						} elseif ( isset( $duo_preauth['response']['response']['enroll_portal_url'] ) ) {
							$duo_enroll_url = $duo_preauth['response']['response']['enroll_portal_url'];
							update_user_meta( $user_id, 'user_not_enroll_on_duo_before', $duo_enroll_url );
							update_user_meta( $user_id, 'user_not_enroll', true );
						} else {
							$mo2fa_login_message = 'Your account is inactive from duo side, please contact to your administrator.';
						}
					} else {
						$mo2fa_login_message = 'Error through during preauth.';
					}

					$mo2fa_login_status = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';

					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}
		/**
		 * It will invoke after inline registration setup success
		 *
		 * @param string $current_user_id It will carry the user id value .
		 * @param string $redirect_to It will carry the redirect url .
		 * @param string $session_id It will carry the session id .
		 * @return void
		 */
		public function mo2f_inline_setup_success( $current_user_id, $redirect_to, $session_id ) {
			global $mo2fdb_queries;
			$mo2fdb_queries->update_user_details( $current_user_id, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS' ) );

			$mo2f_user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $current_user_id );
			if ( empty( $mo2f_user_email ) ) {
				$currentuser     = get_user_by( 'id', $current_user_id );
				$mo2f_user_email = $currentuser->user_email;
			}
			$generate_backup_code = new MocURL();
			$codes                = $generate_backup_code->mo_2f_generate_backup_codes( $mo2f_user_email, site_url() );

			$code_generate = get_user_meta( $current_user_id, 'mo_backup_code_generated', false );

			if ( empty( $code_generate ) && 'InternetConnectivityError' !== $codes && 'DBConnectionIssue' !== $codes && 'UnableToFetchData' !== $codes && 'UserLimitReached' !== $codes && 'ERROR' !== $codes && 'LimitReached' !== $codes && 'AllUsed' !== $codes && 'invalid_request' !== $codes ) {
				$mo2fa_login_message = '';
				$mo2fa_login_status  = 'MO_2_FACTOR_GENERATE_BACKUP_CODES';
				$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
			} else {
				$pass2fa = new Miniorange_Password_2Factor_Login();
				$pass2fa->mo2fa_pass2login( $redirect_to, $session_id );
				update_user_meta( $current_user_id, 'error_during_code_generation', $codes );
				exit;
			}
		}
		/**
		 * Inline qr code for mobile
		 *
		 * @param string $email It will carry the email address.
		 * @param string $id It will carry the id .
		 * @return string
		 */
		public function mo2f_inline_get_qr_code_for_mobile( $email, $id ) {
			$register_mobile = new Two_Factor_Setup_Onprem_Cloud();
			$content         = $register_mobile->register_mobile( $email );
			$response        = json_decode( $content, true );
			$message         = '';
			$miniorageqr     = array();
			if ( JSON_ERROR_NONE === json_last_error() ) {
				if ( 'ERROR' === $response['status'] ) {
					$miniorageqr['message'] = MoWpnsMessages::lang_translate( $response['message'] );

					delete_user_meta( $id, 'miniorageqr' );
				} else {
					if ( 'IN_PROGRESS' === $response['status'] ) {
						$miniorageqr['message']                  = '';
						$miniorageqr['mo2f-login-qrCode']        = $response['qrCode'];
						$miniorageqr['mo2f-login-transactionId'] = $response['txId'];
						$miniorageqr['mo2f_show_qr_code']        = 'MO_2_FACTOR_SHOW_QR_CODE';
						update_user_meta( $id, 'miniorageqr', $miniorageqr );
					} else {
						$miniorageqr['message'] = __( 'An error occured while processing your request. Please Try again.', 'miniorange-2-factor-authentication' );
						delete_user_meta( $id, 'miniorageqr' );
					}
				}
			}
			return $miniorageqr;
		}
		/**
		 * Inline mobile configure
		 *
		 * @return string
		 */
		public function inline_mobile_configure() {
			if ( isset( $_POST['miniorange_inline_show_qrcode_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['miniorange_inline_show_qrcode_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-show-qrcode-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					$this->miniorange_pass2login_start_session();
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					$redirect_to              = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$current_user             = get_user_by( 'id', $user_id );
					$mo2fa_login_message      = '';
					$mo2fa_login_status       = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$user_registration_status = $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $current_user->ID );
					if ( 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' === $user_registration_status ) {
						$email               = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $current_user->ID );
						$miniorageqr         = $this->mo2f_inline_get_qr_code_for_mobile( $email, $current_user->ID );
						$mo2fa_login_message = $miniorageqr['message'];
						MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'mo2f_transactionId', $miniorageqr['mo2f-login-transactionId'] );

						$this->mo2f_transactionid = $miniorageqr['mo2f-login-transactionId'];
					} else {
						$mo2fa_login_message = __( 'Invalid request. Please register with miniOrange before configuring your mobile.', 'miniorange-2-factor-authentication' );
					}
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, $miniorageqr, $session_id_encrypt );
				}
			}
		}
		/**
		 * It will invoke the inline and validate the google authenticator
		 *
		 * @return string
		 */
		public function miniorange_inline_ga_setup_success() {

			if ( isset( $_POST['mo2f_inline_validate_ga_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['mo2f_inline_validate_ga_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-google-auth-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					$session_id_encrypt  = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					$redirect_to         = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$mo2fa_login_message = '';
					$mo2fa_login_status  = 'MO_2_FACTOR_SETUP_SUCCESS';

					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}
		/**
		 * Back to select 2fa methods
		 *
		 * @return string
		 */
		public function back_to_select_2fa() {
			if ( isset( $_POST['miniorange_inline_two_factor_setup'] ) ) { /* return back to choose second factor screen */
				$nonce = sanitize_key( wp_unslash( $_POST['miniorange_inline_two_factor_setup'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-setup-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					$this->miniorange_pass2login_start_session();
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;

					$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					$redirect_to  = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$current_user = get_user_by( 'id', $user_id );
					$mo2fdb_queries->update_user_details( $current_user->ID, array( 'mo2f_configured_2fa_method' => '' ) );
					$mo2fa_login_message = '';
					$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}

		/**
		 * It will help to create user in miniorange
		 *
		 * @param string $current_user_id It will carry the current user id .
		 * @param string $email It will carry the email address .
		 * @param string $current_method It will carry the current method .
		 * @return string
		 */
		public function create_user_in_miniorange( $current_user_id, $email, $current_method ) {
			$tempemail = get_user_meta( $current_user_id, 'mo2f_email_miniOrange', true );
			if ( isset( $tempemail ) && ! empty( $tempemail ) ) {
				$email = $tempemail;
			}
			global $mo2fdb_queries;

			$enduser = new Two_Factor_Setup_Onprem_Cloud();
			if ( get_option( 'mo2f_miniorange_admin' === $current_user_id ) ) {
				$email = get_option( 'mo2f_email' );
			}

			$check_user = json_decode( $enduser->mo_check_user_already_exist( $email ), true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				if ( 'ERROR' === $check_user['status'] && 'You are not authorized to create users. Please upgrade to premium plan.' === $check_user['message'] ) {
					$current_user = get_user_by( 'id', $current_user_id );
					$content      = json_decode( $enduser->mo_create_user( $current_user, $email ), true );

						update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not using for obfuscation
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

				} elseif ( strcasecmp( $check_user['status'], 'USER_FOUND' ) === 0 ) {
					$mo2fdb_queries->update_user_details(
						$current_user_id,
						array(
							'user_registration_with_miniorange' => 'SUCCESS',
							'mo2f_user_email' => $email,
							'mo_2factor_user_registration_status' => 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR',
						)
					);
					update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not using for obfuscation

					$mo2fa_login_status = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					return $check_user;
				} elseif ( 0 === strcasecmp( $check_user['status'], 'USER_NOT_FOUND' ) ) {
					$current_user = get_user_by( 'id', $current_user_id );
					$content      = json_decode( $enduser->mo_create_user( $current_user, $email ), true );
					if ( JSON_ERROR_NONE === json_last_error() ) {
						if ( 0 === strcasecmp( $content['status'], 'SUCCESS' ) ) {
							update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not using for obfuscation
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
							return $check_user;
						} else {
							$check_user['status']  = 'ERROR';
							$check_user['message'] = 'There is an issue in user creation in miniOrange. Please skip and contact miniorange';
							return $check_user;
						}
					}
				} elseif ( 0 === strcasecmp( $check_user['status'], 'USER_FOUND_UNDER_DIFFERENT_CUSTOMER' ) ) {
					$mo2fa_login_message   = __( 'The email associated with your account is already registered. Please contact your admin to change the email.', 'miniorange-2-factor-authentication' );
					$check_user['status']  = 'ERROR';
					$check_user['message'] = $mo2fa_login_message;
					return $check_user;
				}
			}
		}
		/**
		 * It will invoke to Skip 2fa setup
		 *
		 * @return string
		 */
		public function mo2f_skip_2fa_setup() {

			if ( isset( $_POST['miniorange_skip_2fa_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['miniorange_skip_2fa_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-skip-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					global $mo2fdb_queries;
					$redirect_to        = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
					$session_id_encrypt = sanitize_text_field( $session_id_encrypt );
					$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$currentuser        = get_user_by( 'id', $user_id );

					$mo2fdb_queries->update_user_details( $user_id, array( 'mo2f_2factor_enable_2fa_byusers' => 1 ) );
					if ( ! get_user_meta( $user_id, 'mo2f_grace_period_start_time', true ) ) {
						update_user_meta( $user_id, 'mo2f_grace_period_start_time', strtotime( current_datetime()->format( 'h:ia M d Y' ) ) );
					}
					$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
				}
			}
		}
		/**
		 * This will invoke to save 2fa method on inline
		 *
		 * @return string
		 */
		public function save_inline_2fa_method() {
			if ( isset( $_POST['miniorange_inline_save_2factor_method_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['miniorange_inline_save_2factor_method_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					global $mo2fdb_queries;
					$this->miniorange_pass2login_start_session();
					$mo2fa_login_message = '';
					$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';

					$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					$redirect_to                       = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$current_user                      = get_user_by( 'id', $user_id );
					$user_registration_with_miniorange = $mo2fdb_queries->get_user_detail( 'user_registration_with_miniorange', $current_user->ID );
					if ( 'SUCCESS' === $user_registration_with_miniorange ) {
						$selected_method = isset( $_POST['mo2f_selected_2factor_method'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_selected_2factor_method'] ) ) : 'NONE';

						if ( MoWpnsConstants::OUT_OF_BAND_EMAIL === $selected_method ) {
							$this->mo2f_pass2login_push_oobemail_verification( $current_user, $selected_method, $redirect_to, $session_id_encrypt );
						} elseif ( MoWpnsConstants::OTP_OVER_EMAIL === $selected_method && ! MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_email_change', 'site_option' ) ) {
							$content             = $this->mo2f_onprem_cloud_obj->mo2f_set_otp_over_email( $current_user, $selected_method, $session_id_encrypt, $redirect_to );
							$mo2fa_login_message = isset( $content['mo2fa_login_message'] ) ? $content['mo2fa_login_message'] : '';
							$mo2fa_login_status  = isset( $content['mo2fa_login_status'] ) ? $content['mo2fa_login_status'] : '';
						} elseif ( MoWpnsConstants::GOOGLE_AUTHENTICATOR === $selected_method ) {
							$this->miniorange_pass2login_start_session();
							$mo2fa_login_message = '';
							$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;

							$gauth_name          = get_site_option( 'mo2f_google_appname' );
							$google_account_name = $gauth_name ? $gauth_name : DEFAULT_GOOGLE_APPNAME;

							$this->mo2f_onprem_cloud_obj->mo2f_set_google_authenticator( $current_user, $selected_method, $google_account_name, $session_id_encrypt );
						} elseif ( 'DUO PUSH NOTIFICATIONS' === $selected_method ) {
							$this->miniorange_pass2login_start_session();
							$mo2fa_login_message = '';
							$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;

							$selected_method = MoWpnsConstants::DUO_AUTHENTICATOR;

							$mo2fdb_queries->update_user_details(
								$current_user->ID,
								array(
									'mo2f_configured_2fa_method' => $selected_method,
								)
							);
						} else {
							$content             = $this->mo2f_onprem_cloud_obj->mo2f_set_user_two_fa( $current_user, $selected_method );
							$mo2fa_login_message = $content['mo2fa_login_message'];
							$mo2fa_login_status  = $content['mo2fa_login_status'];
						}
					} else {
						$mo2fa_login_message = __( 'Invalid request. Please register with miniOrange to configure 2 Factor plugin.', 'miniorange-2-factor-authentication' );
					}
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}
		/**
		 * This function will help to check Kba answers and validated it
		 *
		 * @return string
		 */
		public function check_kba_validation() {
			global $mo_wpns_utility, $mo2f_onprem_cloud_obj;
			if ( isset( $_POST['mo2f_authenticate_nonce'] ) ) { /*check kba validation*/
				$nonce = sanitize_text_field( wp_unslash( $_POST['mo2f_authenticate_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-soft-token-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
					return $error;
				} else {
					$this->miniorange_pass2login_start_session();
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$redirect_to        = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					if ( isset( $user_id ) ) {
						if ( MO2f_Utility::mo2f_check_empty_or_null( isset( $_POST['mo2f_answer_1'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_1'] ) ) : '' ) || MO2f_Utility::mo2f_check_empty_or_null( isset( $_POST['mo2f_answer_2'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_2'] ) ) : '' ) ) {
							MO2f_Utility::mo2f_debug_file( 'Please provide both the answers of KBA User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
							$mo2fa_login_message = 'Please provide both the answers.';
							$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION';
							$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
						}
						$otp_token          = array();
						$kba_questions      = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo_2_factor_kba_questions' );
						$otp_token[0]       = $kba_questions[0]['question'];
						$otp_token[1]       = isset( $_POST['mo2f_answer_1'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_1'] ) ) : '';
						$otp_token[2]       = $kba_questions[1]['question'];
						$otp_token[3]       = isset( $_POST['mo2f_answer_2'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_2'] ) ) : '';
						$check_trust_device = isset( $_POST['mo2f_trust_device'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_trust_device'] ) ) : 'false';
						// if the php session folder has insufficient permissions, cookies to be used .
						$mo2f_login_transaction_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_transactionId' );
						MO2f_Utility::mo2f_debug_file( 'Transaction Id-' . $mo2f_login_transaction_id . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
						$kba_validate_response = json_decode( $mo2f_onprem_cloud_obj->validate_otp_token( MoWpnsConstants::SECURITY_QUESTIONS, null, $mo2f_login_transaction_id, $otp_token, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ) ), true );
						if ( 0 === strcasecmp( $kba_validate_response['status'], 'SUCCESS' ) ) {
							MO2f_Utility::mo2f_debug_file( 'Logged in successfully User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
							$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
						} else {
							MO2f_Utility::mo2f_debug_file( 'The answers you have provided for KBA are incorrect User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
							$mo2fa_login_message = 'The answers you have provided are incorrect.';
							$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION';
							$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
						}
					} else {
						MO2f_Utility::mo2f_debug_file( 'User id not found User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
						$this->remove_current_activity( $session_id_encrypt );
						return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Please try again..' ) );
					}
				}
			}
		}
		/**
		 * This function will help to redirect back to inline form
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function miniorange2f_back_to_inline_registration( $posted ) {
			global $mo2fdb_queries;
			$nonce = isset( $_POST['miniorange_back_inline_reg_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['miniorange_back_inline_reg_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-back-inline-reg-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
				return $error;
			} else {
				$session_id_encrypt     = sanitize_text_field( $posted['session_id'] );
				$redirect_to            = esc_url_raw( $posted['redirect_to'] );
				$user_id                = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
				$configure_array_method = $this->mo2fa_return_methods_value( $user_id );
				if ( $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $user_id ) === 'MO_2_FACTOR_PLUGIN_SETTINGS' && count( $configure_array_method ) > 1 ) {
					mo2fa_prompt_mfa_form_for_user( $configure_array_method, $session_id_encrypt, $redirect_to );
					exit;
				} else {
					$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$mo2fa_login_message = '';
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}

		/**
		 * It is a alternate login method
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_miniorange_alternate_login_kba( $posted ) {
			$nonce = $posted['miniorange_alternate_login_kba_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-alternate-login-kba-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $posted['session_id'] ) ? $posted['session_id'] : null;
				$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
				$redirect_to        = isset( $posted['redirect_to'] ) ? esc_url_raw( $posted['redirect_to'] ) : null;
				$this->mo2f_onprem_cloud_obj->mo2f_pass2login_kba_verification( get_user_by( 'id', $user_id ), '', $redirect_to, $session_id_encrypt );
			}
		}
		/**
		 * It is for duo push notification validation
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_miniorange_duo_push_validation( $posted ) {
			global $mo_wpns_utility;
			$nonce = $posted['miniorange_duo_push_validation_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-duo-validation-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $posted['session_id'] ) ? sanitize_text_field( wp_unslash( $posted['session_id'] ) ) : null;
				$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

				$redirect_to = isset( $posted['redirect_to'] ) ? esc_url_raw( $posted['redirect_to'] ) : null;
				MO2f_Utility::mo2f_debug_file( 'Duo push notification - Logged in successfully User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
				$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
			}
		}
		/**
		 * This will invoke Duo push validation failed
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_miniorange_duo_push_validation_failed( $posted ) {
			global $mo_wpns_utility;
			$nonce = $posted['miniorange_duo_push_validation_failed_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-duo-push-validation-failed-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_textarea( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} else {
				MO2f_Utility::mo2f_debug_file( 'Denied duo push notification  User_IP-' . $mo_wpns_utility->get_client_ip() );
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $posted['session_id'] ) ? sanitize_text_field( wp_unslash( $posted['session_id'] ) ) : null;
				$this->remove_current_activity( $session_id_encrypt );
			}
		}
		/**
		 * This will invoke on mobile validation
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_miniorange_mobile_validation( $posted ) {
			/*check mobile validation */
			global $mo_wpns_utility;
			$nonce = $posted['miniorange_mobile_validation_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-mobile-validation-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
				return $error;
			} else {
				global $mo2fdb_queries;
				if ( MO2F_IS_ONPREM && ( isset( $posted['tx_type'] ) && 'PN' !== $posted['tx_type'] ) ) {
					$txid   = $posted['TxidEmail'];
					$status = get_option( $txid );
					if ( ! empty( $status ) ) {
						if ( 1 !== (int) $status ) {
							return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Please try again.' ) );
						}
					}
				}
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $posted['session_id'] ) ? sanitize_text_field( $posted['session_id'] ) : null;
				// if the php session folder has insufficient permissions, cookies to be used .
				$mo2f_login_transaction_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_transactionId' );
				MO2f_Utility::mo2f_debug_file( 'Transaction_id-' . $mo2f_login_transaction_id . ' User_IP-' . $mo_wpns_utility->get_client_ip() );
				$redirect_to = isset( $posted['redirect_to'] ) ? esc_url_raw( $posted['redirect_to'] ) : null;
				$user_id     = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
				$currentuser = get_user_by( 'id', $user_id );
				if ( 'MO_2_FACTOR_PLUGIN_SETTINGS' !== $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $user_id ) ) {
					$enduser = new Two_Factor_Setup_Onprem_Cloud();
					$enduser->mo_create_user( $currentuser, $currentuser->user_email );
					$this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user_id, true, MoWpnsConstants::OUT_OF_BAND_EMAIL, null, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $currentuser->user_email, null );
				}
				$mo2fa_login_status = 'MO_2_FACTOR_SETUP_SUCCESS';
				$this->miniorange_pass2login_form_fields( $mo2fa_login_status, '', $redirect_to, null, $session_id_encrypt );
			}
		}
		/**
		 * This will invoke mobile validation failed
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_miniorange_mobile_validation_failed( $posted ) {
			/*Back to miniOrange Login Page if mobile validation failed and from back button of mobile challenge, soft token and default login*/
			$nonce = $posted['miniorange_mobile_validation_failed_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-mobile-validation-failed-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html( 'ERROR' ) . '</strong>: ' . esc_html( 'Invalid Request.' ) );
				return $error;
			} else {
				MO2f_Utility::mo2f_debug_file( 'MO QR-code/push notification auth denied.' );
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $posted['session_id'] ) ? $posted['session_id'] : null;
				$this->remove_current_activity( $session_id_encrypt );
			}
		}
		/**
		 * Duo authenticator setup success form
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_mo2f_duo_authenticator_success_form( $posted ) {
			if ( isset( $posted['mo2f_duo_authenticator_success_nonce'] ) ) {
				$nonce = sanitize_text_field( $posted['mo2f_duo_authenticator_success_nonce'] );
				if ( ! wp_verify_nonce( $nonce, 'mo2f-duo-authenticator-success-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					$this->miniorange_pass2login_start_session();
					$session_id_encrypt = isset( $posted['session_id'] ) ? sanitize_text_field( wp_unslash( $posted['session_id'] ) ) : null;
					MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );
					$user_id                 = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$redirect_to             = isset( $posted['redirect_to'] ) ? esc_url_raw( $posted['redirect_to'] ) : null;
					$selected_2factor_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2fa_method', $user_id );
					$email                   = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id );
					$mo2fa_login_message     = '';

					delete_user_meta( $user_id, 'user_not_enroll' );
					delete_site_option( 'current_user_email' );
					$mo2fdb_queries->update_user_details(
						$user_id,
						array(
							'mobile_registration_status' => true,
							'mo2f_DuoAuthenticator_config_status' => true,
							'mo2f_configured_2fa_method' => $selected_2factor_method,
							'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS',
						)
					);
					$mo2fa_login_status = 'MO_2_FACTOR_SETUP_SUCCESS';

					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}
		/**
		 * Duo authenticator error function
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_inline_mo2f_duo_authenticator_error( $posted ) {
			$nonce = $posted['mo2f_inline_duo_authentcator_error_nonce'];

			if ( ! wp_verify_nonce( $nonce, 'mo2f-inline-duo-authenticator-error-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html( 'ERROR' ) . '</strong>: ' . esc_html( 'Invalid Request.' ) );

				return $error;
			} else {
				global  $mo2fdb_queries;
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $posted['session_id'] ) ? sanitize_text_field( wp_unslash( $posted['session_id'] ) ) : null;
				MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );
				$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

				$mo2fdb_queries->update_user_details(
					$user_id,
					array(
						'mobile_registration_status' => false,
					)
				);
			}
		}

		/**
		 * It will check the soft token
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_miniorange_softtoken( $posted ) {
			/*Click on the link if phone is offline */
			$nonce = $posted['miniorange_softtoken'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-softtoken' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt       = isset( $posted['session_id'] ) ? $posted['session_id'] : null;
				$session_cookie_variables = array( 'mo2f-login-qrCode', 'mo2f_transactionId' );
				MO2f_Utility::unset_session_variables( $session_cookie_variables );
				MO2f_Utility::unset_cookie_variables( $session_cookie_variables );
				MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );
				$redirect_to         = isset( $posted['redirect_to'] ) ? esc_url_raw( $posted['redirect_to'] ) : null;
				$mo2fa_login_message = 'Please enter the one time passcode shown in the miniOrange<b> Authenticator</b> app.';
				$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN';
				$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
			}
		}
		/**
		 * Chooses function for 2fa validation.
		 *
		 * @return string
		 */
		public function mo2f_validate_user_for_login() {
			$nonce = isset( $_POST['mo2f_authenticate_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_authenticate_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-soft-token-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
				return $error;
			} else {
				$two_method_status = isset( $_POST['request_origin_method'] ) ? sanitize_text_field( wp_unslash( $_POST['request_origin_method'] ) ) : null;
				if ( 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION' === $two_method_status ) {
					$this->check_kba_validation();
				} else {
					$this->check_miniorange_soft_token();
				}
			}
		}

		/**
		 * Checking miniOrange soft token
		 *
		 * @return string
		 */
		public function check_miniorange_soft_token() {
			// Validate Soft Token,OTP over SMS,OTP over EMAIL,Phone verification.

			global $mo_wpns_utility;
			$nonce = isset( $_POST['mo2f_authenticate_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_authenticate_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-soft-token-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt     = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
				$mo2fa_login_status     = isset( $_POST['request_origin_method'] ) ? sanitize_text_field( wp_unslash( $_POST['request_origin_method'] ) ) : null;
				$redirect_to            = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
				$softtoken              = '';
				$user_id                = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
				$attempts               = get_user_meta( $user_id, 'mo2f_attempts_before_redirect', true );
				$configure_array_method = $this->mo2fa_return_methods_value( $user_id );
				$mfa_configured         = ( (int) get_site_option( 'mo2f_nonce_enable_configured_methods' ) && ( count( $configure_array_method ) > 1 ) ) ? 1 : 0;

				if ( MO2f_utility::mo2f_check_empty_or_null( isset( $_POST['mo2fa_softtoken'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2fa_softtoken'] ) ) : '' ) ) {
					if ( $attempts > 1 || 'disabled' === $attempts ) {
						update_user_meta( $user_id, 'mo2f_attempts_before_redirect', $attempts - 1 );
						$mo2fa_login_message = 'Please enter OTP to proceed.';
						MO2f_Utility::mo2f_debug_file( 'Please enter OTP to proceed User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
						$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
					} else {
						$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
						$this->remove_current_activity( $session_id_encrypt );
						MO2f_Utility::mo2f_debug_file( 'Number of attempts exceeded User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
						return new WP_Error( 'limit_exceeded', '<strong>ERROR</strong>: Number of attempts exceeded.' );
					}
				} else {
					$softtoken = isset( $_POST['mo2fa_softtoken'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2fa_softtoken'] ) ) : '';
					if ( ! MO2f_utility::mo2f_check_number_length( $softtoken ) ) {
						if ( $attempts > 1 || 'disabled' === $attempts ) {
							update_user_meta( $user_id, 'mo2f_attempts_before_redirect', $attempts - 1 );
							$mo2fa_login_message = 'Invalid OTP. Only digits within range 4-8 are allowed. Please try again.';
							MO2f_Utility::mo2f_debug_file( 'Invalid OTP. Only digits within range 4-8 are allowed User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
							$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
						} else {
							$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
							$this->remove_current_activity( $session_id_encrypt );
							update_user_meta( $user_id, 'mo2f_attempts_before_redirect', 3 );
							if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
								$data = array( 'reload' => 'reload' );
								wp_send_json_success( $data );
							} else {
								MO2f_Utility::mo2f_debug_file( 'Number of attempts exceeded  User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
								return new WP_Error( 'limit_exceeded', '<strong>ERROR</strong>: Number of attempts exceeded.' );
							}
						}
					}
				}

				global $mo2fdb_queries;
				$user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id );
				if ( isset( $user_id ) ) {
					$content      = '';
					$current_user = get_userdata( $user_id );
					// if the php session folder has insufficient permissions, cookies to be used .

					$mo2f_login_transaction_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_transactionId' );

					MO2f_Utility::mo2f_debug_file( 'Transaction_id-' . $mo2f_login_transaction_id . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id . ' Email-' . $user_email );
					if ( isset( $mo2fa_login_status ) && 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL' === $mo2fa_login_status ) {
						$content = json_decode( $this->mo2f_onprem_cloud_obj->validate_otp_token( MoWpnsConstants::OTP_OVER_EMAIL, null, $mo2f_login_transaction_id, $softtoken, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), $current_user ), true );
					} elseif ( isset( $mo2fa_login_status ) && 'MO_2_FACTOR_CHALLENGE_OTP_OVER_WHATSAPP' === $mo2fa_login_status ) {
						$otp_token     = get_user_meta( $current_user->ID, 'mo2f_otp_token_wa', true );
						$time          = get_user_meta( $current_user->ID, 'mo2f_whatsapp_time', true );
						$accepted_time = time() - 600;
						$time          = (int) $time;
						global $mo2fdb_queries;

						if ( $otp_token === $softtoken ) {
							if ( $accepted_time < $time ) {
								update_user_meta( $user_id, 'mo2f_attempts_before_redirect', 3 );
								$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
							} else {
								$this->remove_current_activity( $session_id_encrypt );

								return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: OTP has been Expired please reinitiate another transaction.' ) );
							}
						} else {
							update_user_meta( $user_id, 'mo2f_attempts_before_redirect', $attempts - 1 );
							$message = 'Invalid OTP. Please enter again.';
							$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $message, $redirect_to, null, $session_id_encrypt, $mfa_configured );
						}
					} elseif ( isset( $mo2fa_login_status ) && 'MO_2_FACTOR_CHALLENGE_OTP_OVER_TELEGRAM' === $mo2fa_login_status ) {
						$otp_token     = get_user_meta( $current_user->ID, 'mo2f_otp_token', true );
						$time          = get_user_meta( $current_user->ID, 'mo2f_telegram_time', true );
						$accepted_time = time() - 300;
						$time          = (int) $time;
						global $mo2fdb_queries;

						if ( $otp_token === $softtoken ) {
							if ( $accepted_time < $time ) {
								update_user_meta( $user_id, 'mo2f_attempts_before_redirect', 3 );
								MO2f_Utility::mo2f_debug_file( 'OTP over Telegram - Logged in successfully User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id . ' Email-' . $user_email );
								$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
							} else {
								$this->remove_current_activity( $session_id_encrypt );
								MO2f_Utility::mo2f_debug_file( 'OTP has been Expired please reinitiate another transaction. User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id . ' Email-' . $user_email );
								return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: OTP has been Expired please reinitiate another transaction.' ) );
							}
						} else {
							if ( $attempts <= 1 ) {
								$this->remove_current_activity( $session_id_encrypt );
								update_user_meta( $user_id, 'mo2f_attempts_before_redirect', 3 );
								return new WP_Error( 'attempts failed try again ', __( '<strong>ERROR</strong>: maximum attempts.' ) );
							}
							MO2f_Utility::mo2f_debug_file( 'OTP over Telegram - Invalid OTP User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id . ' Email-' . $user_email );
							update_user_meta( $user_id, 'mo2f_attempts_before_redirect', $attempts - 1 );
							$message = 'Invalid OTP. Please enter again.';
							$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $message, $redirect_to, null, $session_id_encrypt, $mfa_configured );
						}
					} elseif ( isset( $mo2fa_login_status ) && 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS' === $mo2fa_login_status ) {
						$content = json_decode( $this->mo2f_onprem_cloud_obj->validate_otp_token( MoWpnsConstants::OTP_OVER_SMS, null, $mo2f_login_transaction_id, $softtoken, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ) ), true );
					} elseif ( isset( $mo2fa_login_status ) && 'MO_2_FACTOR_CHALLENGE_PHONE_VERIFICATION' === $mo2fa_login_status ) {
						$customer = new MocURL();
						$content  = json_decode( $customer->miniorange_authenticator_validate( 'PHONE VERIFICATION', $user_email, $softtoken, get_option( 'mo2f_customerKey' ) ), true );
					} elseif ( isset( $mo2fa_login_status ) && 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN' === $mo2fa_login_status ) {
						$customer = new MocURL();
						$content  = json_decode( $customer->miniorange_authenticator_validate( MoWpnsConstants::SOFT_TOKEN, $user_email, $softtoken, get_option( 'mo2f_customerKey' ) ), true );
					} elseif ( isset( $mo2fa_login_status ) && 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION' === $mo2fa_login_status ) {
						$content = json_decode( $this->mo2f_onprem_cloud_obj->validate_otp_token( MoWpnsConstants::GOOGLE_AUTHENTICATOR, $user_email, null, $softtoken, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ) ), true );
					} else {
						$this->remove_current_activity( $session_id_encrypt );
						return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Invalid Request. Please try again.' ) );
					}
					if ( 0 === strcasecmp( $content['status'], 'SUCCESS' ) ) {
						update_user_meta( $user_id, 'mo2f_attempts_before_redirect', 3 );
						if ( 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL' === $mo2fa_login_status ) {
							$this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user_id, true, MoWpnsConstants::OTP_OVER_EMAIL, null, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, null, $user_email, null );
						}
						MO2f_Utility::mo2f_debug_file( $mo2fa_login_status . ' Logged in successfully User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id . ' Email-' . $user_email );
						$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
					} else {
						if ( $attempts > 1 || 'disabled' === $attempts ) {
							MO2f_Utility::mo2f_debug_file( $mo2fa_login_status . ' Enter wrong OTP User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id . ' Email-' . $user_email );
							update_user_meta( $user_id, 'mo2f_attempts_before_redirect', $attempts - 1 );
							$message = 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN' === $mo2fa_login_status ? 'You have entered an invalid OTP.<br>Please click on <b>Sync Time</b> in the miniOrange Authenticator app to sync your phone time with the miniOrange servers and try again.' : 'Invalid OTP. Please try again.';
							$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $message, $redirect_to, null, $session_id_encrypt, $mfa_configured );
						} else {
							$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
							$this->remove_current_activity( $session_id_encrypt );
							update_user_meta( $user_id, 'mo2f_attempts_before_redirect', $attempts - 1 );
							if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
								$data = array( 'reload' => 'reload' );
								wp_send_json_success( $data );
							} else {
								MO2f_Utility::mo2f_debug_file( 'Number of attempts exceeded User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id . ' Email-' . $user_email );
								return new WP_Error( 'limit_exceeded', '<strong>ERROR</strong>: Number of attempts exceeded.' );
							}
						}
					}
				} else {
					$this->remove_current_activity( $session_id_encrypt );
					MO2f_Utility::mo2f_debug_file( 'User id not found User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id . ' Email-' . $user_email );
					return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Please try again..' ) );
				}
			}
		}
		/**
		 * It will invoke on checking weather inline registration is skip or not
		 *
		 * @param string $posted It will carry the post data .
		 * @return void
		 */
		public function check_miniorange_inline_skip_registration( $posted ) {
			$error = new WP_Error();
			$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
		}
		/**
		 * Pass2 login redirect function
		 *
		 * @return string
		 */
		public function miniorange_pass2login_redirect() {
			do_action( 'mo2f_network_init' );
			global $mo2fdb_queries;
			if ( isset( $_GET['reconfigureMethod'] ) && is_user_logged_in() ) {
				$useridget = get_current_user_id();
				$txidget   = isset( $_GET['transactionId'] ) ? sanitize_text_field( wp_unslash( $_GET['transactionId'] ) ) : '';
				$methodget = isset( $_GET['reconfigureMethod'] ) ? sanitize_text_field( wp_unslash( $_GET['reconfigureMethod'] ) ) : '';
				if ( get_site_option( $txidget ) === $useridget && ctype_xdigit( $txidget ) && ctype_xdigit( $methodget ) ) {
					$method = get_site_option( $methodget );
					$mo2fdb_queries->update_user_details(
						$useridget,
						array(
							'mo_2factor_user_registration_status' => 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS',
							'mo2f_configured_2fa_method' => $method,
						)
					);
					$is_authy_configured = $mo2fdb_queries->get_user_detail( 'mo2f_AuthyAuthenticator_config_status', $useridget );
					if ( MoWpnsConstants::GOOGLE_AUTHENTICATOR === $method || $is_authy_configured ) {
						update_user_meta( $useridget, 'mo2fa_set_Authy_inline', true );
					}
					delete_site_option( $txidget );
				} else {
					$head = 'You are not authorized to perform this action';
					$body = 'Please contact to your admin';
					$this->display_email_verification( $head, $body, 'red' );
					exit();
				}
			}
			if ( isset( $_GET['Txid'] ) && isset( $_GET['accessToken'] ) ) {
				$useridget     = isset( $_GET['userID'] ) ? sanitize_text_field( wp_unslash( $_GET['userID'] ) ) : '';
				$txidget       = isset( $_GET['Txid'] ) ? sanitize_text_field( wp_unslash( $_GET['Txid'] ) ) : '';
				$otp_token     = get_site_option( $useridget );
				$txidstatus    = get_site_option( $txidget );
				$useridd       = $useridget . 'D';
				$otp_tokend    = get_site_option( $useridd );
				$mo2f_dir_name = dirname( __FILE__ );
				$mo2f_dir_name = explode( 'wp-content', $mo2f_dir_name );
				$mo2f_dir_name = explode( 'handler', $mo2f_dir_name[1] );

				$head  = __( 'You are not authorized to perform this action', 'miniorange-2-factor-authentication' );
				$body  = __( 'Please contact to your admin', 'miniorange-2-factor-authentication' );
				$color = 'red';
				if ( 3 === (int) $txidstatus ) {
					$time                   = 'time' . $txidget;
					$current_time_in_millis = round( microtime( true ) * 1000 );
					$generatedtimeinmillis  = get_site_option( $time );
					$difference             = ( $current_time_in_millis - $generatedtimeinmillis ) / 1000;
					if ( $difference <= 300 ) {
						$accesstokenget = isset( $_GET['accessToken'] ) ? sanitize_text_field( wp_unslash( $_GET['accessToken'] ) ) : '';
						if ( $accesstokenget === $otp_token ) {
							update_site_option( $txidget, 1 );
							$body  = __( 'Transaction has been successfully validated. Please continue with the transaction.', 'miniorange-2-factor-authentication' );
							$head  = __( 'TRANSACTION SUCCESSFUL', 'miniorange-2-factor-authentication' );
							$color = 'green';
						} elseif ( $accesstokenget === $otp_tokend ) {
							update_site_option( $txidget, 0 );
							$body = 'Transaction has been Canceled. Please Try Again.';
							$head = 'TRANSACTION DENIED';
						}
					}
					delete_site_option( $useridget );
					delete_site_option( $useridd );
					delete_site_option( $time );
				}

				$this->display_email_verification( $head, $body, $color );
				exit;
			} elseif ( isset( $_POST['emailInlineCloud'] ) ) {
				$nonce = isset( $_POST['miniorange_emailChange_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['miniorange_emailChange_nonce'] ) ) : '';
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-email-change-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . esc_html( 'ERROR' ) . '</strong>: ' . esc_html( 'Invalid Request.' ) );
					return $error;
				} else {
					$email              = sanitize_text_field( wp_unslash( $_POST['emailInlineCloud'] ) );
					$current_user_id    = isset( $_POST['current_user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['current_user_id'] ) ) : '';
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					$redirect_to        = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
						global  $mo2fdb_queries;
						$mo2fdb_queries->update_user_details(
							$current_user_id,
							array(
								'mo2f_user_email' => $email,
								'mo2f_configured_2fa_method' => '',
							)
						);
						prompt_user_to_select_2factor_mthod_inline( $current_user_id, 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR', '', $redirect_to, $session_id_encrypt, null );
					}
				}
			} elseif ( isset( $_POST['txid'] ) ) {
				$txidpost = sanitize_text_field( wp_unslash( $_POST['txid'] ) );
				$status   = get_site_option( $txidpost );
				update_option( 'optionVal1', $status ); // ??
				if ( 1 === $status || 0 === $status ) {
					delete_site_option( $txidpost );
				}
				echo esc_html( $status );
				exit();
			} else {
				$value = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : false;
				switch ( $value ) {
					case 'miniorange_mfactor_method':
						$session_id                   = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
						$current_userid               = MO2f_Utility::mo2f_get_transient( $session_id, 'mo2f_current_user_id' );
						$currentuser                  = get_user_by( 'id', $current_userid );
						$mo2f_selected_mfactor_method = isset( $_POST['mo2f_selected_mfactor_method'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_selected_mfactor_method'] ) ) : '';
						update_user_meta( $currentuser->ID, 'mo2f_mfactor_method_temp', $mo2f_selected_mfactor_method );
						$this->mo2fa_select_method( $currentuser, $mo2f_selected_mfactor_method, $session_id, esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) );
						break;

					case 'miniorange2f_back_to_inline_registration':
						$this->miniorange2f_back_to_inline_registration( $_POST );
						exit;

					case 'miniorange_alternate_login_kba':
						$this->check_miniorange_alternate_login_kba( $_POST );
						break;

					case 'miniorange_mobile_validation':
						$this->check_miniorange_mobile_validation( $_POST );
						break;

					case 'miniorange_duo_push_validation':
						$this->check_miniorange_duo_push_validation( $_POST );
						break;

					case 'mo2f_inline_duo_authenticator_success_form':
						$this->check_mo2f_duo_authenticator_success_form( $_POST );
						break;

					case 'mo2f_inline_duo_authenticator_error':
						$this->check_inline_mo2f_duo_authenticator_error( $_POST );
						break;

					case 'miniorange_mobile_validation_failed':
						$this->check_miniorange_mobile_validation_failed( $_POST );
						break;

					case 'miniorange_duo_push_validation_failed':
						$this->check_miniorange_duo_push_validation_failed( $_POST );
						break;

					case 'miniorange_softtoken':
						$this->check_miniorange_softtoken( $_POST );
						break;
					case 'mo2f_validate_user_for_login':
						$this->mo2f_validate_user_for_login();
						break;
					case 'miniorange_inline_skip_registration':
						$this->check_miniorange_inline_skip_registration( $_POST );
						break;

					case 'miniorange_inline_save_2factor_method':
						$this->save_inline_2fa_method();
						break;

					case 'mo2f_skip_2fa_setup':
						$this->mo2f_skip_2fa_setup();
						break;

					case 'miniorange_back_inline':
						$this->back_to_select_2fa();
						break;

					case 'miniorange_inline_ga_setup_success':
						$this->miniorange_inline_ga_setup_success();
						break;

					case 'miniorange_inline_show_mobile_config':
						$this->inline_mobile_configure();
						break;

					case 'miniorange_inline_complete_mobile':
						$this->mo2f_inline_validate_mobile_authentication();
						break;
					case 'miniorange_inline_duo_auth_mobile_complete':
						$this->mo2f_inline_validate_duo_authentication();
						break;
					case 'duo_mobile_send_push_notification_for_inline_form':
						$this->mo2f_duo_mobile_send_push_notification_for_inline_form();
						break;
					case 'mo2f_inline_kba_option':
						$this->mo2f_inline_validate_kba();
						break;

					case 'miniorange_inline_complete_otp':
						$this->mo2f_inline_validate_otp_complete();
						break;

					case 'miniorange_inline_login':
						$this->mo2f_inline_login();
						break;
					case 'miniorange_inline_register':
						$this->mo2f_inline_register();
						break;
					case 'mo2f_users_backup1':
						$this->mo2f_download_backup_codes_inline();
						break;
					case 'mo2f_goto_wp_dashboard':
						$this->mo2f_goto_wp_dashboard();
						break;
					case 'miniorange_backup_nonce':
						$this->mo2f_use_backup_codes( $_POST );
						break;
					case 'miniorange_validate_backup_nonce':
						$this->check_backup_codes_validation( $_POST );
						break;
					case 'miniorange_create_backup_codes':
						$this->mo2f_create_backup_codes();
						break;
					case 'mo2f_resend_otp_nonce':
						$this->mo2f_resend_otp_nonce();
						break;
					default:
						$error = new WP_Error();
						$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
						return $error;
				}
			}
		}
		/**
		 * It will invoke when you denied message
		 *
		 * @param string $message It will carry the message .
		 * @return string
		 */
		public function denied_message( $message ) {
			if ( empty( $message ) && get_option( 'denied_message' ) ) {
				delete_option( 'denied_message' );
			} else {
				return $message;
			}
		}
		/**
		 * Removing the current activity
		 *
		 * @param string $session_id It will carry the session id .
		 * @return void
		 */
		public function remove_current_activity( $session_id ) {
			global $mo2fdb_queries;
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

			$temp_table_variables = array(
				'session_id',
				'mo2f_current_user_id',
				'mo2f_login_message',
				'mo2f_1stfactor_status',
				'mo2f_transactionId',
				'mo_2_factor_kba_questions',
				'ts_created',
			);

			MO2f_Utility::unset_session_variables( $session_variables );
			MO2f_Utility::unset_cookie_variables( $cookie_variables );
			$key             = get_option( 'mo2f_encryption_key' );
			$session_id      = MO2f_Utility::decrypt_data( $session_id, $key );
			$session_id_hash = md5( $session_id );
			$mo2fdb_queries->save_user_login_details(
				$session_id_hash,
				array(

					'mo2f_current_user_id'      => '',
					'mo2f_login_message'        => '',
					'mo2f_1stfactor_status'     => '',
					'mo2f_transactionId'        => '',
					'mo_2_factor_kba_questions' => '',
					'ts_created'                => '',
				)
			);
		}

		/**
		 * It will use to start the session
		 *
		 * @return void
		 */
		public function miniorange_pass2login_start_session() {
			if ( ! session_id() || '' === session_status() || ! isset( $_SESSION ) ) {
				$session_path = ini_get( 'session.save_path' );
				if ( is_writable( $session_path ) && is_readable( $session_path ) ) {
					if ( PHP_SESSION_DISABLED !== session_status() ) {
						session_start();
					}
				}
			}
		}

		/**
		 * It will pass 2fa on login flow
		 *
		 * @param string  $mo2fa_login_status It will carry the login status message .
		 * @param string  $mo2fa_login_message It will carry the login message .
		 * @param string  $redirect_to It will carry the redirect url .
		 * @param string  $qr_code It will carry the qr code .
		 * @param string  $session_id_encrypt It will carry the session id .
		 * @param string  $show_back_button It will help to show button .
		 * @param boolean $mo2fa_transaction_id It will carry the transaction id .
		 * @return void
		 */
		public function miniorange_pass2login_form_fields( $mo2fa_login_status = null, $mo2fa_login_message = null, $redirect_to = null, $qr_code = null, $session_id_encrypt = null, $show_back_button = null, $mo2fa_transaction_id = false ) {
			$login_status  = $mo2fa_login_status;
			$login_message = $mo2fa_login_message;
			switch ( $login_status ) {
				case 'MO_2_FACTOR_CHALLENGE_MOBILE_AUTHENTICATION':
					$transactionid = $this->mo2f_transactionid ? $this->mo2f_transactionid : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_transactionId' );
					include_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'mo2f-common-cloud-login.php';
					mo2f_get_qrcode_authentication_prompt( $login_status, $login_message, $redirect_to, $qr_code, $session_id_encrypt, $transactionid );
					break;
				case 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN':
					$user_id         = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$skeleton_values = mo2f_twofa_login_prompt_skeleton_values( $login_message, $login_status, null, null, $user_id );
					mo2f_twofa_authentication_login_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values );
					break;
				case 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL':
					$user_id         = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$skeleton_values = mo2f_twofa_login_prompt_skeleton_values( $login_message, $login_status, null, null, $user_id );
					mo2f_twofa_authentication_login_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values );
					break;
				case 'MO_2_FACTOR_CHALLENGE_OTP_OVER_TELEGRAM':
					$user_id         = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$skeleton_values = mo2f_twofa_login_prompt_skeleton_values( $login_message, $login_status, null, null, $user_id );
					mo2f_twofa_authentication_login_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values );
					break;
				case 'MO_2_FACTOR_CHALLENGE_OTP_OVER_WHATSAPP':
					$user_id         = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$skeleton_values = mo2f_twofa_login_prompt_skeleton_values( $login_message, $login_status, null, null, $user_id );
					mo2f_twofa_authentication_login_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values );
					break;
				case 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS':
					$user_id         = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$skeleton_values = mo2f_twofa_login_prompt_skeleton_values( $login_message, $login_status, null, null, $user_id );
					mo2f_twofa_authentication_login_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values );
					break;
				case 'MO_2_FACTOR_CHALLENGE_PHONE_VERIFICATION':
					$user_id         = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$skeleton_values = mo2f_twofa_login_prompt_skeleton_values( $login_message, $login_status, null, null, $user_id );
					mo2f_twofa_authentication_login_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values );
					break;
				case 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION':
					$user_id         = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$skeleton_values = mo2f_twofa_login_prompt_skeleton_values( $login_message, $login_status, null, null, $user_id );
					mo2f_twofa_authentication_login_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values );
					break;
				case 'MO_2_FACTOR_CHALLENGE_DUO_PUSH_NOTIFICATIONS':
					$user_id = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					mo2f_get_duo_push_authentication_prompt(
						$login_status,
						$login_message,
						$redirect_to,
						$session_id_encrypt,
						$user_id
					);
					break;

				case 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL':
					mo2f_get_forgotphone_form( $login_status, $login_message, $redirect_to, $session_id_encrypt );
					break;

				case 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS':
					$transactionid = $this->mo2f_transactionid ? $this->mo2f_transactionid : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_transactionId' );
					$user_id       = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					include_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'mo2f-common-cloud-login.php';
					mo2f_get_push_notification_oobemail_prompt( $user_id, $login_status, $login_message, $redirect_to, $session_id_encrypt, $transactionid );
					break;

				case 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL':
					$transactionid = $this->mo2f_transactionid ? $this->mo2f_transactionid : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_transactionId' );
					$user_id       = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					mo2f_get_push_notification_oobemail_prompt( $user_id, $login_status, $login_message, $redirect_to, $session_id_encrypt, $transactionid );
					break;

				case 'MO_2_FACTOR_RECONFIG_GOOGLE':
					$user_id = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					$this->mo2f_redirect_shortcode_addon( $user_id, $login_status, $login_message, 'reconfigure_google' );
					break;

				case 'MO_2_FACTOR_RECONFIG_KBA':
					$user_id = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					$this->mo2f_redirect_shortcode_addon( $user_id, $login_status, $login_message, 'reconfigure_kba' );
					break;

				case 'MO_2_FACTOR_SETUP_SUCCESS':
					$user_id = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					$this->mo2f_inline_setup_success( $user_id, $redirect_to, $session_id_encrypt );
					break;

				case 'MO_2_FACTOR_GENERATE_BACKUP_CODES':
					mo2f_show_generated_backup_codes_inline( $redirect_to, $session_id_encrypt );
					exit;

				case 'MO_2_FACTOR_CHALLENGE_BACKUP':
					mo2f_backup_form( $login_status, $login_message, $redirect_to, $session_id_encrypt );
					exit;

				case 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION':
					$user_id = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					if ( MO2F_IS_ONPREM ) {
						$ques            = get_user_meta( $user_id, 'kba_questions_user' );
						$skeleton_values = mo2f_twofa_login_prompt_skeleton_values( $login_message, $login_status, $ques[0][0]['question'], $ques[0][1]['question'], $user_id );
						mo2f_twofa_authentication_login_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values );
					} else {
						$kbaquestions    = $this->mo2f_kbaquestions ? $this->mo2f_kbaquestions : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo_2_factor_kba_questions' );
						$skeleton_values = mo2f_twofa_login_prompt_skeleton_values( $login_message, $login_status, $kbaquestions[0]['question'], $kbaquestions[1]['question'], $user_id );
						mo2f_twofa_authentication_login_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values );
					}
					break;

				case 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS':
					$user_id = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					prompt_user_to_select_2factor_mthod_inline( $user_id, $login_status, $login_message, $redirect_to, $session_id_encrypt, $qr_code );
					break;

				default:
					$this->mo_2_factor_pass2login_show_wp_login_form();
					break;
			}
			exit();
		}
		/**
		 * It will check the mobile status
		 *
		 * @param string $login_status It will store the login status message .
		 * @return boolean
		 */
		public function miniorange_pass2login_check_mobile_status( $login_status ) {
			// mobile authentication .
			if ( 'MO_2_FACTOR_CHALLENGE_MOBILE_AUTHENTICATION' === $login_status ) {
				return true;
			}

			return false;
		}
		/**
		 * Pass2login otp check status
		 *
		 * @param string  $login_status It will store the login status message .
		 * @param boolean $sso It will store the softtoken message .
		 * @return boolean
		 */
		public function miniorange_pass2login_check_otp_status( $login_status, $sso = false ) {
			if ( 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN' === $login_status || 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL' === $login_status || 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS' === $login_status || 'MO_2_FACTOR_CHALLENGE_PHONE_VERIFICATION' === $login_status || 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION' === $login_status ) {
				return true;
			}

			return false;
		}
		/**
		 * Forgot phone status
		 *
		 * @param string $login_status It will store the login status message .
		 * @return boolean
		 */
		public function miniorange_pass2login_check_forgotphone_status( $login_status ) {
			// after clicking on forgotphone link when both kba and email are configured .
			if ( 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL' === $login_status ) {
				return true;
			}

			return false;
		}
		/**
		 * Email verification method
		 *
		 * @param string $login_status It will store the login status message .
		 * @return boolean
		 */
		public function miniorange_pass2login_check_push_oobemail_status( $login_status ) {
			// for push and out of and email .
			if ( 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' === $login_status || 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL' === $login_status ) {
				return true;
			}

			return false;
		}
		/**
		 * Rconfig Google method
		 *
		 * @param string $login_status It will store the login status message .
		 * @return boolean
		 */
		public function miniorange_pass2login_reconfig_google( $login_status ) {
			if ( 'MO_2_FACTOR_RECONFIG_GOOGLE' === $login_status ) {
				return true;
			}

			return false;
		}
		/**
		 * It will redirect to shortcode addon
		 *
		 * @param string $current_user_id .
		 * @param string $login_status It will store the login status message .
		 * @param string $login_message .
		 * @param string $identity .
		 * @return void
		 */
		public function mo2f_redirect_shortcode_addon( $current_user_id, $login_status, $login_message, $identity ) {
			do_action( 'mo2f_shortcode_addon', $current_user_id, $login_status, $login_message, $identity );
		}
		/**
		 * It will invoke to reconfig the Kba
		 *
		 * @param string $login_status It will store the login status message .
		 * @return boolean
		 */
		public function miniorange_pass2login_reconfig_kba( $login_status ) {
			if ( 'MO_2_FACTOR_RECONFIG_KBA' === $login_status ) {
				return true;
			}

			return false;
		}
		/**
		 * It will Check kba status
		 *
		 * @param string $login_status It will store the login status message .
		 * @return boolean
		 */
		public function miniorange_pass2login_check_kba_status( $login_status ) {
			if ( 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION' === $login_status ) {
				return true;
			}

			return false;
		}

		/**
		 * Pass2login for showing login form
		 *
		 * @return mixed
		 */
		public function mo_2_factor_pass2login_show_wp_login_form() {
			$session_id_encrypt = $this->create_session();
			if ( class_exists( 'Theme_My_Login' ) ) {
				wp_enqueue_script( 'tmlajax_script', plugins_url( 'includes/js/tmlajax.min.js', dirname( dirname( __FILE__ ) ) ), array( 'jQuery' ), MO2F_VERSION, false );
				wp_localize_script(
					'tmlajax_script',
					'my_ajax_object',
					array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
				);
			}
			if ( class_exists( 'LoginWithAjax' ) ) {
				wp_enqueue_script( 'login_with_ajax_script', plugins_url( 'includes/js/login_with_ajax.min.js', dirname( dirname( __FILE__ ) ) ), array( 'jQuery' ), MO2F_VERSION, false );
				wp_localize_script(
					'login_with_ajax_script',
					'my_ajax_object',
					array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
				);
			}
			?>
		<p><input type="hidden" name="miniorange_login_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-login-nonce' ) ); ?>"/>

			<input type="hidden" id="sessid" name="session_id"
				value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>

		</p>

			<?php
		}

		/**
		 * Pass to login push verification
		 *
		 * @param string $currentuser It will carry the current user .
		 * @param string $mo2f_second_factor It will store the second factor method .
		 * @param string $redirect_to It will store the redirect url .
		 * @param string $session_id_encrypt It will carry the session id .
		 * @return void
		 */
		public function mo2f_pass2login_duo_push_verification( $currentuser, $mo2f_second_factor, $redirect_to, $session_id_encrypt ) {
			global $mo2fdb_queries;
			include_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two_fa_duo_handler.php';
			if ( is_null( $session_id_encrypt ) ) {
				$session_id_encrypt = $this->create_session();
			}

			$mo2fa_login_message = '';
			$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_DUO_PUSH_NOTIFICATIONS';
			$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
		}
		/**
		 * Pass2login verification
		 *
		 * @param object $current_user It will carry the current user .
		 * @param string $mo2f_second_factor It will store the second factor method .
		 * @param string $redirect_to It will store the redirect url .
		 * @param string $session_id It will carry the session id .
		 * @return string
		 */
		public function mo2f_pass2login_push_oobemail_verification( $current_user, $mo2f_second_factor, $redirect_to, $session_id = null ) {
			global $mo2fdb_queries,$mo_wpns_utility;
			if ( is_null( $session_id ) ) {
				$session_id = $this->create_session();
			}
			$user_email            = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $current_user->ID );
			$mo2f_1stfactor_status = $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $current_user->ID );
			if ( MoWpnsConstants::PUSH_NOTIFICATIONS === $mo2f_second_factor ) {
				$miniorange_push_auth = new MocURL();
				$content              = $miniorange_push_auth->miniorange_auth_challenge( $user_email, $mo2f_second_factor, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ) );
			} else {
				$cm_vt_y_wlua_w5n_t1_r_q = MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
				if ( $cm_vt_y_wlua_w5n_t1_r_q <= 0 && MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS !== $mo2f_1stfactor_status ) {
					$mo2fa_login_message = user_can( $current_user->ID, 'administrator' ) ? MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL : MoWpnsMessages::ERROR_DURING_PROCESS;
					$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
				}
				$content = $this->mo2f_onprem_cloud_obj->mo2f_push_email_verification( $current_user, $mo2f_second_factor, $client_ip = null );
			}
			$response = json_decode( $content, true );
			if ( JSON_ERROR_NONE === json_last_error() ) { /* Generate Qr code */
				if ( 'SUCCESS' === $response['status'] ) {
					MO2f_Utility::mo2f_set_transient( $session_id, 'mo2f_transactionId', $response['txId'] );
					update_user_meta( $current_user->ID, 'mo2f_transactionId', $response['txId'] );

					MO2f_Utility::mo2f_debug_file( 'Push notification has sent successfully for ' . $mo2f_second_factor . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $current_user->ID . ' Email-' . $current_user->user_email );
					$this->mo2f_transactionid = $response['txId'];

					$mo2fa_login_message = MoWpnsConstants::PUSH_NOTIFICATIONS === $mo2f_second_factor ? 'A Push Notification has been sent to your phone. We are waiting for your approval.' : 'An email has been sent to ' . MO2f_Utility::mo2f_get_hidden_email( $user_email ) . '. We are waiting for your approval.';
					$mo2fa_login_status  = MoWpnsConstants::PUSH_NOTIFICATIONS === $mo2f_second_factor ? 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' : 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL';
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
				} elseif ( 'ERROR' === $response['status'] || 'FAILED' === $response['status'] ) {
					$this->mo2f_transactionid = isset( $response['txId'] ) ? $response['txId'] : '';
					MO2f_Utility::mo2f_set_transient( $session_id, 'mo2f_transactionId', $this->mo2f_transactionid );
					update_user_meta( $current_user->ID, 'mo2f_transactionId', $this->mo2f_transactionid );
					MO2f_Utility::mo2f_debug_file( 'An error occured while sending push notification-' . $mo2f_second_factor . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $current_user->ID . ' Email-' . $current_user->user_email );
					$this->mo2f_transactionid = $response['txId'];
					$mo2fa_login_message      = MoWpnsConstants::PUSH_NOTIFICATIONS === $mo2f_second_factor ? MoWpnsMessages::ERROR_IN_SENDING_PN : ( user_can( $current_user->ID, 'administrator' ) ? MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL : MoWpnsMessages::ERROR_DURING_PROCESS );
					$mo2fa_login_status       = MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS === $mo2f_1stfactor_status ? ( 'PUSH NOTIFICATIONS' === $mo2f_second_factor ? 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' : 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL' ) : MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;;
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
				}
			} else {
				MO2f_Utility::mo2f_debug_file( 'An error occured while processing your request. User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $current_user->ID . ' Email-' . $current_user->user_email );
				$this->remove_current_activity( $session_id );
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.' ) );

				return $error;
			}
		}
		/**
		 * Otp verification
		 *
		 * @param object $user It will carry the current user .
		 * @param string $mo2f_second_factor It will store the second factor method .
		 * @param string $redirect_to It will store the redirect url .
		 * @param string $session_id It will carry the session id .
		 * @return string
		 */
		public function mo2f_pass2login_otp_verification( $user, $mo2f_second_factor, $redirect_to, $session_id = null ) {
			global $mo2fdb_queries,$mo_wpns_utility;

			if ( is_null( $session_id ) ) {
				$session_id = $this->create_session();
			}
			$mo2f_external_app_type = get_user_meta( $user->ID, 'mo2f_external_app_type', true );
			if ( MoWpnsConstants::OTP_OVER_EMAIL === $mo2f_second_factor ) {
				$mo2f_user_phone = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
				if ( get_site_option( base64_encode( 'limitReached' ) ) ) { //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not using for obfuscation
					update_site_option( base64_encode( 'remainingOTP' ), 0 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not using for obfuscation
				}
			} else {
				$mo2f_user_phone = $mo2fdb_queries->get_user_detail( 'mo2f_user_phone', $user->ID );
			}
			if ( MoWpnsConstants::SOFT_TOKEN === $mo2f_second_factor ) {
				$mo2fa_login_message = 'Please enter the one time passcode shown in the miniOrange<b> Authenticator</b> app.';
				$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN';
				MO2f_Utility::mo2f_debug_file( $mo2fa_login_status . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user->ID . ' Email-' . $user->user_email );
				$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
			} elseif ( MoWpnsConstants::GOOGLE_AUTHENTICATOR === $mo2f_second_factor ) {
				$mo2fa_login_message = 'Please enter the one time passcode shown in the <b> Authenticator</b> app.';
				$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION';
				MO2f_Utility::mo2f_debug_file( $mo2fa_login_status . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user->ID . ' Email-' . $user->user_email );
				$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
			} elseif ( MoWpnsConstants::OTP_OVER_TELEGRAM === $mo2f_second_factor ) {
				$chatid    = get_user_meta( $user->ID, 'mo2f_chat_id', true );
				$otp_token = '';
				for ( $i = 1;$i < 7;$i++ ) {
					$otp_token .= wp_rand( 0, 9 );
				}

				update_user_meta( $user->ID, 'mo2f_otp_token', $otp_token );
				update_user_meta( $user->ID, 'mo2f_telegram_time', time() );

				$url      = esc_url( MoWpnsConstants::TELEGRAM_OTP_LINK );
				$postdata = array(
					'mo2f_otp_token' => $otp_token,
					'mo2f_chatid'    => $chatid,
				);

				$args = array(
					'method'    => 'POST',
					'timeout'   => 10,
					'sslverify' => false,
					'headers'   => array(),
					'body'      => $postdata,
				);

				$mo2f_api = new Mo2f_Api();
				$data     = $mo2f_api->mo2f_wp_remote_post( $url, $args );

				if ( 'SUCCESS' === $data ) {
					$mo2fa_login_message = 'Please enter the one time passcode sent on your<b> Telegram</b> app.';
					$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_OTP_OVER_TELEGRAM';
					MO2f_Utility::mo2f_debug_file( $mo2fa_login_status . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user->ID . ' Email-' . $user->user_email );
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
				}
			} else {
				$content  = '';
				$response = array();
				$otplimit = 0;
				if ( MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' ) > 0 || MoWpnsConstants::OTP_OVER_EMAIL !== $mo2f_second_factor ) {
					$content  = $this->mo2f_onprem_cloud_obj->send_otp_token( $mo2f_user_phone, $mo2f_second_factor, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), $user );
					$response = json_decode( $content, true );
				} else {
					MO2f_Utility::mo2f_debug_file( 'Error in sending OTP over Email or SMS. User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user->ID . ' Email-' . $user->user_email );
					$response['status']  = 'FAILED';
					$response['message'] = '<p style = "color:red;">OTP limit has been exceeded</p>';
					$otplimit            = 1;
				}
				if ( json_last_error() === JSON_ERROR_NONE ) {
					if ( 'SUCCESS' === $response['status'] ) {
						if ( MoWpnsConstants::OTP_OVER_EMAIL === $mo2f_second_factor ) {
							MO2f_Utility::mo2f_debug_file( ' OTP has been sent successfully over email. User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user->ID . ' Email-' . $user->user_email );
							$cmvtywluaw5nt1rq = MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
							if ( $cmvtywluaw5nt1rq > 0 ) {
								update_site_option( 'cmVtYWluaW5nT1RQ', $cmvtywluaw5nt1rq - 1 );
							}
						} elseif ( MoWpnsConstants::OTP_OVER_SMS === $mo2f_second_factor ) {
							MO2f_Utility::mo2f_debug_file( ' OTP has been sent successfully over phone. User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user->ID . ' Email-' . $user->user_email );
							$mo2f_sms = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' );
							if ( $mo2f_sms > 0 ) {
								update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', $mo2f_sms - 1 );
							}
						}
						if ( ! isset( $response['phoneDelivery']['contact'] ) ) {
							$response['phoneDelivery']['contact'] = '';
						}
						if ( MO2F_IS_ONPREM ) {
							$mo2f_user_phone = ! empty( $response['phoneDelivery']['contact'] ) ? MO2f_Utility::get_hidden_phone( $response['phoneDelivery']['contact'] ) : ( ! empty( $response['email'] ) ? MO2f_Utility::mo2f_get_hidden_email( $response['email'] ) : '' );
						} else {
							$mo2f_user_phone = ! empty( $response['phoneDelivery']['contact'] ) ? MO2f_Utility::get_hidden_phone( $response['phoneDelivery']['contact'] ) : ( ! empty( $response['emailDelivery']['contact'] ) ? MO2f_Utility::mo2f_get_hidden_email( $response['emailDelivery']['contact'] ) : '' );
						}
						$message = __( 'A one time passcode has been sent to ', 'miniorange-2-factor-authentication' ) . $mo2f_user_phone . __( '. Please enter the OTP to verify your identity.', 'miniorange-2-factor-authentication' );
						if ( get_user_meta( $user->ID, 'resend_otp' ) ) {
							$message .= ' If you have not recieved any OTP code please try resending the OTP or please contact ';
							$message .= ( get_site_option( 'mo2f_email' ) === $user->user_email ) ? '<a href="mailto:mfasupport@xecurify.com">2fasupport@xecurify.com</a>' : 'your administrator';
							delete_user_meta( $user->ID, 'resend_otp' );
						}
						update_option( 'mo2f_number_of_transactions', MoWpnsUtility::get_mo2f_db_option( 'mo2f_number_of_transactions', 'get_option' ) - 1 );
						MO2f_Utility::mo2f_set_transient( $session_id, 'mo2f_transactionId', $response['txId'] );

						$this->mo2f_transactionid = $response['txId'];
						$mo2fa_login_message      = $message;
						$current_method           = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2fa_method', $user->ID );
						if ( MoWpnsConstants::OTP_OVER_EMAIL === $mo2f_second_factor ) {
							$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL';
						} else {
							$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS';
						}
						$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
					} else {
						if ( 'TEST FAILED.' === $response['message'] ) {
							$response['message'] = 'There is an error in sending the OTP.';
						}

						$last_message = ' Or  <a href = " ' . MoWpnsConstants::RECHARGELINK . '" target="_blank">purchase transactions</a>';

						if ( 1 === $otplimit ) {
							$last_message .= ' or contact miniOrange';
						} elseif ( MO2F_IS_ONPREM && ( MoWpnsConstants::OTP_OVER_EMAIL === $mo2f_second_factor || MoWpnsConstants::OUT_OF_BAND_EMAIL === $mo2f_second_factor ) ) {
							$last_message .= ' Or check your SMTP Server and remaining transactions.';
						} else {
							$last_message .= ' Or <a href="' . MoWpnsConstants::VIEW_TRANSACTIONS . '" target="_blank"> Check your remaining transactions </a>';
							if ( get_site_option( 'mo2f_email' ) === $user->user_email ) {
								$last_message .= 'or </br><a href="' . MoWpnsConstants::RECHARGELINK . '" target="_blank">Add SMS Transactions</a> to your account';
							}
						}
						$message = $response['message'] . '. You can click on <a href="https://faq.miniorange.com/knowledgebase/i-am-locked-cant-access-my-account-what-do-i-do/" target="_blank">I am locked out</a> to login via alternate method ' . $last_message;
						if ( ! isset( $response['txid'] ) ) {
							$response['txid'] = '';
						}
						MO2f_Utility::mo2f_set_transient( $session_id, 'mo2f_transactionId', $response['txid'] );

						$this->mo2f_transactionid = $response['txid'];
						$mo2fa_login_message      = 'administrator' === get_userdata( $user->ID )->roles[0] ? $message : 'An error occured while sending OTP. Please contact your administrator.';
						$mo2fa_login_status       = 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS';
						$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
					}
				} else {
					$this->remove_current_activity( $session_id );
					$error = new WP_Error();
					$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.' ) );
					return $error;
				}
			}
		}

		/**
		 * Mobile verification
		 *
		 * @param object $user It will carry the user detail .
		 * @param string $mo2f_second_factor 2FA method name.
		 * @param string $redirect_to It will carry the redirect url .
		 * @param string $session_id_encrypt It will store the session id .
		 * @return string
		 */
		public function mo2f_pass2login_mobile_verification( $user, $mo2f_second_factor, $redirect_to, $session_id_encrypt = null ) {
			global $mo2fdb_queries,$mo_wpns_utility;
			if ( is_null( $session_id_encrypt ) ) {
				$session_id_encrypt = $this->create_session();
			}
			$useragent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
			MO2f_Utility::mo2f_debug_file( 'Check user agent to check request from mobile device ' . $useragent );
			if ( MO2f_Utility::check_if_request_is_from_mobile_device( $useragent ) ) {
				$session_cookie_variables = array( 'mo2f-login-qrCode', 'mo2f_transactionId' );

				MO2f_Utility::unset_session_variables( $session_cookie_variables );
				MO2f_Utility::unset_cookie_variables( $session_cookie_variables );
				MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );

				$mo2fa_login_message = 'Please enter the one time passcode shown in the miniOrange<b> Authenticator</b> app.';
				$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN';
				MO2f_Utility::mo2f_debug_file( 'Request from mobile device so promting soft token  User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user->ID . ' Email-' . $user->user_email );
				$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
			} else {
				$email               = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
				$mo2f_send_otp_token = new MocURL();
				$content             = $mo2f_send_otp_token->miniorange_auth_challenge( $email, MoWpnsConstants::MOBILE_AUTHENTICATION, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ) );
				$response            = json_decode( $content, true );
				if ( json_last_error() === JSON_ERROR_NONE ) { /* Generate Qr code */
					if ( 'SUCCESS' === $response['status'] ) {
						$qr_code = $response['qrCode'];
						MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'mo2f_transactionId', $response['txId'] );
						$mo2fa_login_message = '';
						$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_MOBILE_AUTHENTICATION';
						MO2f_Utility::mo2f_debug_file( $mo2fa_login_status . ' Sent miniOrange QR code Authentication successfully. User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user->ID . ' Email-' . $user->user_email );
						$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, $qr_code, $session_id_encrypt );
					} elseif ( 'ERROR' === $response['status'] ) {
						$this->remove_current_activity( $session_id_encrypt );
						MO2f_Utility::mo2f_debug_file( $response['status'] . ' An error occured while processing your request  User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user->ID . ' Email-' . $user->user_email );
						$error = new WP_Error();
						$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.' ) );

						return $error;
					}
				} else {
					MO2f_Utility::mo2f_debug_file( ' An error occured while processing your request  User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user->ID . ' Email-' . $user->user_email );
					$this->remove_current_activity( $session_id_encrypt );
					$error = new WP_Error();
					$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.' ) );

					return $error;
				}
			}
		}
		/**
		 * Pass2 login method
		 *
		 * @param string $redirect_to It will carry the redirect url.
		 * @param string $session_id_encrypted It will carry the session id.
		 * @return void
		 */
		public function mo2fa_pass2login( $redirect_to = null, $session_id_encrypted = null ) {
			if ( empty( $this->mo2f_user_id ) && empty( $this->fstfactor ) ) {
				$user_id               = MO2f_Utility::mo2f_get_transient( $session_id_encrypted, 'mo2f_current_user_id' );
				$mo2f_1stfactor_status = MO2f_Utility::mo2f_get_transient( $session_id_encrypted, 'mo2f_1stfactor_status' );
			} else {
				$user_id               = $this->mo2f_user_id;
				$mo2f_1stfactor_status = $this->fstfactor;
			}

			if ( $user_id && $mo2f_1stfactor_status && ( 'VALIDATE_SUCCESS' === $mo2f_1stfactor_status ) ) {
				$currentuser = get_user_by( 'id', $user_id );
				wp_set_current_user( $user_id, $currentuser->user_login );
				$mobile_login = new Miniorange_Mobile_Login();
				$mobile_login->remove_current_activity( $session_id_encrypted );

				delete_expired_transients( true );
				delete_site_option( $session_id_encrypted );

				wp_set_auth_cookie( $user_id, true );
				delete_user_meta( $currentuser->ID, 'mo2f_mfactor_method_temp' );
				do_action( 'wp_login', $currentuser->user_login, $currentuser );
				redirect_user_to( $currentuser, $redirect_to );
				exit;
			} else {
				$this->remove_current_activity( $session_id_encrypted );
			}
		}
		/**
		 * This function will invoke to create session for user
		 *
		 * @return string
		 */
		public function create_session() {
			global $mo2fdb_queries;
			$session_id      = MO2f_Utility::random_str( 20 );
			$session_id_hash = md5( $session_id );
			$mo2fdb_queries->insert_user_login_session( $session_id_hash );
			$key                = get_option( 'mo2f_encryption_key' );
			$session_id_encrypt = MO2f_Utility::encrypt_data( $session_id, $key );
			return $session_id_encrypt;
		}
		/**
		 * It will initiate 2nd factor
		 *
		 * @param object $currentuser It will carry the current user detail .
		 * @param string $redirect_to It will carry the redirect url .
		 * @param string $session_id_encrypt It will carry the session id .
		 * @return string
		 */
		public function miniorange_initiate_2nd_factor( $currentuser, $redirect_to = null, $session_id_encrypt = null ) {
			global $mo2fdb_queries,$mo_wpns_utility, $mo2f_onprem_cloud_obj;
			MO2f_Utility::mo2f_debug_file( 'MO initiate 2nd factor User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
			$this->miniorange_pass2login_start_session();

			if ( is_null( $session_id_encrypt ) ) {
				$session_id_encrypt = $this->create_session();
			}

			$redirect_to = class_exists( 'UM_Functions' ) ? $this->mo2f_redirect_url_for_um( $currentuser ) : $redirect_to;

			MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'mo2f_current_user_id', $currentuser->ID, 600 );
			MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'mo2f_1stfactor_status', 'VALIDATE_SUCCESS', 600 );

			$this->mo2f_user_id = $currentuser->ID;
			$this->fstfactor    = 'VALIDATE_SUCCESS';
			$roles              = (array) $currentuser->roles;
			$twofactor_enabled  = 0;
			foreach ( $roles as $role ) {
				if ( get_option( 'mo2fa_' . $role ) === '1' ) {
					$twofactor_enabled = 1;
					break;
				}
			}
			if ( 1 !== $twofactor_enabled && is_super_admin( $currentuser->ID ) && (int) get_site_option( 'mo2fa_superadmin' ) === 1 ) {
				$twofactor_enabled = 1;
			}

			if ( $twofactor_enabled ) {
				$mo_2factor_user_registration_status = $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $currentuser->ID );
				$twofactor_transactions = new Mo2fDB();
				$exceeded               = $twofactor_transactions->check_alluser_limit_exceeded( $currentuser->ID );
				$tfa_enabled            = $mo2fdb_queries->get_user_detail( 'mo2f_2factor_enable_2fa_byusers', $currentuser->ID );
				if ( 0 === $tfa_enabled && ( 'MO_2_FACTOR_PLUGIN_SETTINGS' !== $mo_2factor_user_registration_status ) && ! empty( $tfa_enabled ) ) {
					$exceeded = 1;
				}

				if ( 'MO_2_FACTOR_PLUGIN_SETTINGS' === $mo_2factor_user_registration_status ) { // checking if user has configured any 2nd factor method.
					$mo2f_second_factor     = $mo2f_onprem_cloud_obj->mo2f_get_user_2ndfactor( $currentuser );
					$configure_array_method = $this->mo2fa_return_methods_value( $currentuser->ID );
					$resend_otp_check       = isset( $_POST['option'] ) && ( 'mo2f_resend_otp_nonce' === $_POST['option'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified the nonce already in the flow.
					if ( count( $configure_array_method ) > 1 && (int) get_site_option( 'mo2f_nonce_enable_configured_methods' ) === 1 && ! $resend_otp_check ) {
						update_site_option( 'mo2f_login_with_mfa_use', '1' );
						mo2fa_prompt_mfa_form_for_user( $configure_array_method, $session_id_encrypt, $redirect_to );
						exit;
					} else {
						if ( $resend_otp_check && get_user_meta( $currentuser->ID, 'mo2f_mfactor_method_temp', true ) ) {
							$mo2f_second_factor = get_user_meta( $currentuser->ID, 'mo2f_mfactor_method_temp', true );
						}
						$user = $this->mo2fa_select_method( $currentuser, $mo2f_second_factor, $session_id_encrypt, $redirect_to );
						return $user;
					}
				} elseif ( ! $exceeded && ! get_user_meta( $currentuser->ID, 'resend_otp', true ) && ( MoWpnsUtility::get_mo2f_db_option( 'mo2f_inline_registration', 'get_option' ) || $this->mo2f_is_grace_period_expired( $currentuser ) ) ) {
					$this->mo2fa_inline( $currentuser, $redirect_to, $session_id_encrypt );
				} elseif ( get_user_meta( $currentuser->ID, 'resend_otp', true ) ) {
					delete_user_meta( $currentuser->ID, 'resend_otp' );
					$this->mo2f_otp_over_email_send( $currentuser->user_email, $redirect_to, $session_id_encrypt, $currentuser );
				} else {
					if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
						$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
					} else {
						return $currentuser;
					}
				}
			} else { // plugin is not activated for current role then logged him in without asking 2 factor .
				$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
			}
		}

		/**
		 * Get redirect url for Ultimate Member Form
		 *
		 * @param object $currentuser Current user.
		 * @return string
		 */
		public function mo2f_redirect_url_for_um( $currentuser ) {
			MO2f_Utility::mo2f_debug_file( 'Using UM login form.' );
			$redirect_to = '';
			if ( ! isset( $_POST['wp-submit'] ) && isset( $_POST['um_request'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is coming from Ultimate member login form.
				$meta = get_option( 'um_role_' . $currentuser->roles[0] . '_meta' );
				if ( isset( $meta ) && ! empty( $meta ) ) {
					if ( isset( $meta['_um_login_redirect_url'] ) ) {
						$redirect_to = $meta['_um_login_redirect_url'];
					}
					if ( empty( $redirect_to ) ) {
						$redirect_to = get_site_url();
					}
				}
				$login_form_url = '';
				if ( isset( $_POST['redirect_to'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is coming from Ultimate member login form.
					$login_form_url = esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is coming from Ultimate member login form.
				}
				if ( ! empty( $login_form_url ) && ! is_null( $login_form_url ) ) {
					$redirect_to = $login_form_url;
				}
			}
			return $redirect_to;
		}

		/**
		 * This function will return the configured method value
		 *
		 * @param string $currentuserid It will carry the current user id .
		 * @return array
		 */
		public function mo2fa_return_methods_value( $currentuserid ) {
			global $mo2fdb_queries;
			$count_methods          = $mo2fdb_queries->get_user_configured_methods( $currentuserid );
			$value                  = empty( $count_methods ) ? '' : get_object_vars( $count_methods[0] );
			$configured_methods_arr = array();
			foreach ( $value as $config_status_option => $config_status ) {
				if ( strpos( $config_status_option, 'config_status' ) ) {
					$config_status_string_array = explode( '_', $config_status_option );
					$config_method              = MoWpnsConstants::mo2f_convert_method_name( $config_status_string_array[1], 'pascal_to_cap' );
					if ( '1' === $value[ $config_status_option ] ) {
						array_push( $configured_methods_arr, $config_method );
					}
				}
			}
			return $configured_methods_arr;
		}
		/**
		 * Select methods for twofa
		 *
		 * @param object $currentuser It will carry the current user .
		 * @param string $mo2f_second_factor It will carry the second factor .
		 * @param string $session_id_encrypt It will carry the session id .
		 * @param string $redirect_to It will carry the redirect url .
		 * @return string
		 */
		public function mo2fa_select_method( $currentuser, $mo2f_second_factor, $session_id_encrypt, $redirect_to ) {
			global $mo_wpns_utility, $mo2fdb_queries;
			if ( MoWpnsConstants::OTP_OVER_EMAIL === $mo2f_second_factor ) {
				if ( MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' ) <= 0 ) {
					update_site_option( 'bGltaXRSZWFjaGVk', 1 );
				}
			}
			$mo_2fa_load_2fa_login_method_view = array(
				MoWpnsConstants::MOBILE_AUTHENTICATION => array( $this, 'mo2f_pass2login_mobile_verification' ),
				MoWpnsConstants::PUSH_NOTIFICATIONS    => array( $this, 'mo2f_pass2login_push_oobemail_verification' ),
				MoWpnsConstants::OUT_OF_BAND_EMAIL     => array( $this, 'mo2f_pass2login_push_oobemail_verification' ),
				MoWpnsConstants::SOFT_TOKEN            => array( $this, 'mo2f_pass2login_otp_verification' ),
				MoWpnsConstants::OTP_OVER_SMS          => array( $this, 'mo2f_pass2login_otp_verification' ),
				MoWpnsConstants::OTP_OVER_EMAIL        => array( $this, 'mo2f_pass2login_otp_verification' ),
				MoWpnsConstants::OTP_OVER_TELEGRAM     => array( $this, 'mo2f_pass2login_otp_verification' ),
				MoWpnsConstants::GOOGLE_AUTHENTICATOR  => array( $this, 'mo2f_pass2login_otp_verification' ),
				MoWpnsConstants::SECURITY_QUESTIONS    => array( $this->mo2f_onprem_cloud_obj, 'mo2f_pass2login_kba_verification' ),
				MoWpnsConstants::DUO_AUTHENTICATOR     => array( $this, 'mo2f_pass2login_duo_push_verification' ),

			);
			if ( ! empty( $mo_2fa_load_2fa_login_method_view[ $mo2f_second_factor ] ) ) {
				call_user_func( $mo_2fa_load_2fa_login_method_view[ $mo2f_second_factor ], $currentuser, $mo2f_second_factor, $redirect_to, $session_id_encrypt );
			} elseif ( 'NONE' === $mo2f_second_factor ) {
				MO2f_Utility::mo2f_debug_file( 'mo2f_second_factor is NONE User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
				if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
					$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
				} else {
					return $currentuser;
				}
			} else {
				$this->remove_current_activity( $session_id_encrypt );
				$error = new WP_Error();
				if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
					MO2f_Utility::mo2f_debug_file( 'Two factor method has not been configured User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
					$data = array( 'notice' => '<div style="border-left:3px solid #dc3232;">&nbsp; Two Factor method has not been configured.' );
					wp_send_json_success( $data );
				} else {
					MO2f_Utility::mo2f_debug_file( 'Two factor method has not been configured User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
					$error->add( 'empty_username', __( '<strong>ERROR</strong>: Two Factor method has not been configured.' ) );
					return $error;
				}
			}
		}

		/**
		 * Inline invoke 2fa
		 *
		 * @param object $currentuser It will carry the current user detail .
		 * @param string $redirect_to It will carry the redirect url .
		 * @param string $session_id It will carry the session id .
		 * @return void
		 */
		public function mo2fa_inline( $currentuser, $redirect_to, $session_id ) {
			global $mo2fdb_queries;
			$current_user_id = $currentuser->ID;
			$email           = $currentuser->user_email;
			$mo2fdb_queries->insert_user( $current_user_id, array( 'user_id' => $current_user_id ) );
			$mo2fdb_queries->update_user_details(
				$current_user_id,
				array(
					'user_registration_with_miniorange'   => 'SUCCESS',
					'mo2f_user_email'                     => $email,
					'mo_2factor_user_registration_status' => 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR',
				)
			);

			$mo2fa_login_message = '';
			$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';

			$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id );
		}
		/**
		 * This function will validating the soft token
		 *
		 * @param object $currentuser It will carry the current user detail .
		 * @param string $mo2f_second_factor It will carry the second factor method .
		 * @param string $softtoken It will carry the soft token .
		 * @param string $session_id_encrypt It will carry the session id .
		 * @param string $redirect_to It will carry the redirect url .
		 * @return string
		 */
		public function mo2f_validate_soft_token( $currentuser, $mo2f_second_factor, $softtoken, $session_id_encrypt, $redirect_to = null ) {
			global $mo2fdb_queries;
			$email   = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $currentuser->ID );
			$content = json_decode( $this->mo2f_onprem_cloud_obj->validate_otp_token( $mo2f_second_factor, $email, null, $softtoken, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ) ), true );
			if ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) {
				$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
			} else {
				if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
					$data = array( 'notice' => '<div style="border-left:3px solid #dc3232;">&nbsp; Invalid One Time Passcode.' );
					wp_send_json_success( $data );
				} else {
					return new WP_Error( 'invalid_one_time_passcode', '<strong>ERROR</strong>: Invalid One Time Passcode.' );
				}
			}
		}
		/**
		 * Sending the otp over email
		 *
		 * @param string $email It will carry the email address .
		 * @param string $redirect_to It will carry the redirect url .
		 * @param string $session_id_encrypt It will carry the session id .
		 * @param object $current_user It will carry the current user .
		 * @return void
		 */
		public function mo2f_otp_over_email_send( $email, $redirect_to, $session_id_encrypt, $current_user ) {
			$response = array();
			if ( get_site_option( 'cmVtYWluaW5nT1RQ' ) > 0 ) {
				$content  = $this->mo2f_onprem_cloud_obj->send_otp_token( $email, MoWpnsConstants::OTP_OVER_EMAIL, get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), $current_user );
				$response = json_decode( $content, true );
				if ( ! MO2F_IS_ONPREM ) {
					if ( isset( $response['txId'] ) ) {
						MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'mo2f_transactionId', $response['txId'] );
					}
				}
			} else {
				$response['status'] = 'FAILED';
			}
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $response['status'] ) {
					$cmvtywluaw5nt1rq = get_site_option( 'cmVtYWluaW5nT1RQ' );
					if ( $cmvtywluaw5nt1rq > 0 ) {
						update_site_option( 'cmVtYWluaW5nT1RQ', $cmvtywluaw5nt1rq - 1 );
					}
					$mo2fa_login_message  = 'An OTP has been sent to ' . MO2f_Utility::mo2f_get_hidden_email( $email ) . '. Please verify to set the two-factor';
					$mo2fa_login_status   = 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL';
					$mo2fa_transaction_id = isset( $response['txId'] ) ? $response['txId'] : null;
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt, 1, $mo2fa_transaction_id );
				} else {
					$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$mo2fa_login_message = user_can( $current_user->ID, 'administrator' ) ? MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL : MoWpnsMessages::ERROR_DURING_PROCESS;
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt, 1 );
				}
			}
		}
		/**
		 * It will call at the time of authentication .
		 *
		 * @param object $user It will carry the user detail.
		 * @param string $username It will carry the username .
		 * @param string $password It will carry the password .
		 * @param string $redirect_to It will carry the redirect url .
		 * @return string
		 */
		public function mo2f_check_username_password( $user, $username, $password, $redirect_to = null ) {
			global $mo_wpns_utility;
			$is_ajax_request = MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' );
			if ( is_a( $user, 'WP_Error' ) && ! empty( $user ) ) {
				if ( $is_ajax_request ) {
					$data = MO2f_Utility::mo2f_show_error_on_login( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_CREDS ) );
					wp_send_json_success( $data );
				} else {
					return $user;
				}
			}
			$currentuser = wp_authenticate_username_password( $user, $username, $password );
			if ( is_wp_error( $currentuser ) ) {
				if ( $is_ajax_request ) {
					$data = MO2f_Utility::mo2f_show_error_on_login( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_CREDS ) );
					wp_send_json_success( $data );
				} else {
					MO2f_Utility::mo2f_debug_file( 'Invalid username and password. User_IP-' . $mo_wpns_utility->get_client_ip() );
					$currentuser->add( 'invalid_username_password', '<strong>' . esc_html( 'ERROR' ) . '</strong>: ' . esc_html( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_CREDS ) ) );
					return $currentuser;
				}
			} else {
					MO2f_Utility::mo2f_debug_file( 'Username and password  validate successfully User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
					$session_id  = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : $this->create_session();//phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is coming from Wordpres login form.
					$redirect_to = $this->mo2f_get_redirect_url();
					$error       = $this->miniorange_initiate_2nd_factor( $currentuser, $redirect_to, $session_id );
					return $error;
			}
		}

		/**
		 * Get redirect URL.
		 *
		 * @return string
		 */
		public function mo2f_get_redirect_url() {
			if ( isset( $_REQUEST['woocommerce-login-nonce'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
				MO2f_Utility::mo2f_debug_file( 'It is a woocommerce login form. Get woocommerce redirectUrl' );
				if ( ! empty( $_REQUEST['redirect_to'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
					$redirect_to = sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
				} elseif ( isset( $_REQUEST['_wp_http_referer'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
					$redirect_to = sanitize_text_field( wp_unslash( $_REQUEST['_wp_http_referer'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
				} else {
					if ( function_exists( 'wc_get_page_permalink' ) ) {
						$redirect_to = wc_get_page_permalink( 'myaccount' ); // function exists in WooCommerce plugin.
					}
				}
			} else {
				$redirect_to = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) ) : ( isset( $_REQUEST['redirect'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['redirect'] ) ) : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
			}
			return esc_url_raw( $redirect_to );
		}

		/**
		 * It is useful for grace period
		 *
		 * @param object $currentuser It will carry the current user .
		 * @return string
		 */
		public function mo2f_is_grace_period_expired( $currentuser ) {
			$grace_period_set_time = get_user_meta( $currentuser->ID, 'mo2f_grace_period_start_time', true );
			if ( ! $grace_period_set_time ) {
				return false;
			}
			$grace_period = get_option( 'mo2f_grace_period_value' );
			if ( get_option( 'mo2f_grace_period_type' ) === 'hours' ) {
				$grace_period = $grace_period * 60 * 60;
			} else {
				$grace_period = $grace_period * 24 * 60 * 60;
			}

			$total_grace_period = $grace_period + (int) $grace_period_set_time;
			$current_time_stamp = strtotime( current_datetime()->format( 'h:ia M d Y' ) );
			return $total_grace_period <= $current_time_stamp;
		}

		/**
		 * It will help to display the email verification
		 *
		 * @param string $head It will carry the header .
		 * @param string $body It will carry the body .
		 * @param string $color It will carry the color .
		 * @return void
		 */
		public function display_email_verification( $head, $body, $color ) {
			global $main_dir;

			echo "<div  style='background-color: #d5e3d9; height:850px;' >
		    <div style='height:350px; background-color: #3CB371; border-radius: 2px; padding:2%;  '>
		        <div class='mo2f_tamplate_layout' style='background-color: #ffffff;border-radius: 5px;box-shadow: 0 5px 15px rgba(0,0,0,.5); width:850px;height:350px; align-self: center; margin: 180px auto; ' >
		            <img  alt='logo'  style='margin-left:400px ;
		        margin-top:10px;' src='" . esc_url( $main_dir ) . "includes/images/miniorange_logo.png'>
		            <div><hr></div>

		            <tbody>
		            <tr>
		                <td>

		                    <p style='margin-top:0;margin-bottom:10px'>
		                    <p style='margin-top:0;margin-bottom:10px'> <h1 style='color:" . esc_attr( $color ) . ";text-align:center;font-size:50px'>" . esc_attr( $head ) . "</h1></p>
		                    <p style='margin-top:0;margin-bottom:10px'>
		                    <p style='margin-top:0;margin-bottom:10px;text-align:center'><h2 style='text-align:center'>" . esc_html( $body ) . "</h2></p>
		                    <p style='margin-top:0;margin-bottom:0px;font-size:11px'>

		                </td>
		            </tr>

		        </div>
		    </div>
		</div>";
		}
		/**
		 * It will help to enqueue the default login
		 *
		 * @return void
		 */
		public function mo_2_factor_enable_jquery_default_login() {
			wp_enqueue_script( 'jquery' );
		}

		/**
		 * Save user details in mo2f_user_details table
		 *
		 * @param int     $user_id user id.
		 * @param boolean $config_status configuration status.
		 * @param string  $twofa_method 2FA method.
		 * @param string  $user_registation user registration status.
		 * @param string  $tfastatus 2FA registration status.
		 * @param boolean $enable_byuser Enable 2FA for user.
		 * @param string  $email user's email.
		 * @param string  $phone user'phone.
		 * @return void
		 */
		public function mo2fa_update_user_details( $user_id, $config_status, $twofa_method, $user_registation, $tfastatus, $enable_byuser, $email = null, $phone = null ) {
			global $mo2fdb_queries;
			$details_to_be_updated  = array();
			$user_details_key_value = array(
				'mo2f_' . implode( '', explode( ' ', MoWpnsConstants::mo2f_convert_method_name( $twofa_method, 'cap_to_small' ) ) ) . '_config_status' => $config_status,
				'mo2f_configured_2FA_method'          => $twofa_method,
				'user_registration_with_miniorange'   => $user_registation,
				'mo_2factor_user_registration_status' => $tfastatus,
				'mo2f_2factor_enable_2fa_byusers'     => $enable_byuser,
				'mo2f_user_email'                     => $email,
				'mo2f_user_phone'                     => $phone,
			);

			foreach ( $user_details_key_value as $key => $value ) {
				if ( isset( $value ) ) {
					if ( 'mo2f_miniOrangeQRCodeAuthentication_config_status' === $key || 'mo2f_miniOrangeSoftToken_config_status' === $key || 'mo2f_miniOrangePushNotification_config_status' === $key ) {
						$details_to_be_updated = array_merge(
							$details_to_be_updated,
							array(
								'mobile_registration_status' => true,
								'mo2f_miniOrangeQRCodeAuthentication_config_status' => true,
								'mo2f_miniOrangeSoftToken_config_status' => true,
								'mo2f_miniOrangePushNotification_config_status' => true,
							)
						);
					} else {
						$details_to_be_updated = array_merge( $details_to_be_updated, array( $key => $value ) );
					}
				}
			}
			delete_user_meta( $user_id, 'mo2f_grace_period_start_time' );
			$mo2fdb_queries->update_user_details( $user_id, $details_to_be_updated );
		}

	}
	new Miniorange_Password_2Factor_Login();
}
?>
