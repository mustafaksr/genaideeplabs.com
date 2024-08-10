<?php
/**
 * File contains 2fa-network security ajax functions.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

// Needed in both.
use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Onprem\Miniorange_Authentication;
use TwoFA\Onprem\Two_Factor_Setup_Onprem_Cloud;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wpns_Ajax' ) ) {
	/**
	 * Class Wpns_Ajax
	 */
	class Wpns_Ajax {

		/**
		 * Class Wpns_Ajax constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'mo_login_security_ajax' ) );
			add_action( 'init', array( $this, 'mo2fa_elementor_ajax_fun' ) );
		}

		/**
		 * Contains hooks to call functions.
		 *
		 * @return void
		 */
		public function mo_login_security_ajax() {

			add_action( 'wp_ajax_wpns_login_security', array( $this, 'wpns_login_security' ) );
			add_action( 'wp_ajax_mo2f_ajax', array( $this, 'mo2f_ajax' ) );
			add_action( 'wp_ajax_nopriv_mo2f_ajax', array( $this, 'mo2f_ajax' ) );
		}

		/**
		 * Calls the function according to the switch case.
		 *
		 * @return void
		 */
		public function mo2f_ajax() {

			if ( ! check_ajax_referer( 'miniorange-2-factor-login-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );
			}
			$GLOBALS['mo2f_is_ajax_request'] = true;
			$option                          = isset( $_POST['mo2f_ajax_option'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_ajax_option'] ) ) : '';
			switch ( $option ) {
				case 'mo2f_ajax_kba':
					$this->mo2f_ajax_kba();
					break;
				case 'mo2f_ajax_login':
					$this->mo2f_ajax_login();
					break;
				case 'mo2f_ajax_otp':
					$this->mo2f_ajax_otp();
					break;
			}
		}

		/**
		 * Handles the elementor login flow.
		 *
		 * @return void
		 */
		public function mo2fa_elementor_ajax_fun() {
			if ( isset( $_POST['miniorange_elementor_login_nonce'] ) ) {

				if ( ! check_ajax_referer( 'miniorange-2-factor-login-nonce', 'miniorange_elementor_login_nonce', false ) ) {
					wp_send_json_error( 'class-wpns-ajax' );

				}
				if ( isset( $_POST['mo2fa_elementor_user_password'] ) && ! empty( $_POST['mo2fa_elementor_user_password'] ) && isset( $_POST['mo2fa_elementor_user_name'] ) ) {
					$info                  = array();
					$info['user_login']    = sanitize_user( wp_unslash( $_POST['mo2fa_elementor_user_name'] ) );
					$info['user_password'] = $_POST['mo2fa_elementor_user_password']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
					$info['remember']      = false;
					$user_signon           = wp_signon( $info, false );
					if ( is_wp_error( $user_signon ) ) {
						wp_send_json_error(
							array(
								'loggedin' => false,
								'message'  => __( 'Wrong username or password.' ),
							)
						);
					}
				}
			}
		}

		/**
		 * Calls the network security functions according to the switch case.
		 *
		 * @return void
		 */
		public function wpns_login_security() {
			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			}
			$option = isset( $_POST['wpns_loginsecurity_ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['wpns_loginsecurity_ajax'] ) ) : '';
			switch ( $option ) {
				case 'wpns_ManualIPBlock_form':
					$this->wpns_handle_i_p_blocking();
					break;
				case 'wpns_WhitelistIP_form':
					$this->wpns_whitelist_ip();
					break;
				case 'wpns_ip_lookup':
					$this->wpns_ip_lookup();
					break;
				case 'wpns_all_plans':
					$this->wpns_all_plans();
					break;
				case 'wpns_logout_form':
					$this->wpns_logout_form();
					break;
				case 'wpns_check_transaction':
					$this->wpns_check_transaction();
					break;
				case 'waf_settings_mail_form_notify':
					$this->waf_settings_mail_form_notify();
					break;
				case 'waf_settings_IP_mail_form':
					$this->waf_settings_i_p_mail_form();
					break;
				case 'update_plan':
					$this->update_plan();
					break;
			}
		}

		/**
		 * Updates plan name and plan type options in the options table.
		 *
		 * @return void
		 */
		public function update_plan() {

			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			}
			$mo2f_all_plannames = isset( $_POST['planname'] ) ? sanitize_text_field( wp_unslash( $_POST['planname'] ) ) : '';
			$mo_2fa_plan_type   = isset( $_POST['planType'] ) ? sanitize_text_field( wp_unslash( $_POST['planType'] ) ) : '';
			update_site_option( 'mo2f_planname', $mo2f_all_plannames );
			if ( 'addon_plan' === $mo2f_all_plannames ) {
				update_site_option( 'mo2f_planname', 'addon_plan' );
				update_site_option( 'mo_2fa_addon_plan_type', $mo_2fa_plan_type );
			} elseif ( '2fa_plan' === $mo2f_all_plannames ) {
				update_site_option( 'mo2f_planname', '2fa_plan' );
				update_site_option( 'mo_2fa_plan_type', $mo_2fa_plan_type );
			}
		}

		/**
		 * Calls miniorange soft token validation function.
		 *
		 * @return void
		 */
		public function mo2f_ajax_otp() {
			if ( ! check_ajax_referer( 'miniorange-2-factor-login-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			}
			$obj = new Miniorange_Password_2Factor_Login();
			$obj->check_miniorange_soft_token();
		}

		/**
		 * Calls kba validation function.
		 *
		 * @return void
		 */
		public function mo2f_ajax_kba() {

			if ( ! check_ajax_referer( 'miniorange-2-factor-login-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			}
			$obj = new Miniorange_Password_2Factor_Login();
			$obj->check_kba_validation();
		}

		/**
		 * Checks customer transactions and updates the same in options table.
		 *
		 * @return void
		 */
		public function wpns_check_transaction() {
			$customer_t = new Two_Factor_Setup_Onprem_Cloud();
			$content    = json_decode( $customer_t->get_customer_transactions( get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), 'WP_OTP_VERIFICATION_PLUGIN)' ), true );
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

			if ( isset( $content['emailRemaining'] ) ) {
				if ( MO2F_IS_ONPREM ) {
					$available_transaction = get_site_option( 'EmailTransactionCurrent', 30 );
					if ( $content['emailRemaining'] > $available_transaction && $content['emailRemaining'] > 10 ) {
						$current_transaction = $content['emailRemaining'] + get_site_option( 'cmVtYWluaW5nT1RQ' );
						update_site_option( 'bGltaXRSZWFjaGVk', 0 );
						if ( $available_transaction > 30 ) {
							$current_transaction = $current_transaction - $available_transaction;
						}

						update_site_option( 'cmVtYWluaW5nT1RQ', $current_transaction );
						update_site_option( 'EmailTransactionCurrent', $content['emailRemaining'] );
					}
				} else {
					update_site_option( 'cmVtYWluaW5nT1RQ', $content['emailRemaining'] );
					if ( $content['emailRemaining'] > 0 ) {
						update_site_option( 'bGltaXRSZWFjaGVk', 0 );
					}
				}
			}

		}

		/**
		 * Gets username and password from ajax login form.
		 *
		 * @return void
		 */
		public function mo2f_ajax_login() {
			if ( ! check_ajax_referer( 'miniorange-2-factor-login-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );
			} else {
				$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
				$password = isset( $_POST['password'] ) ? $_POST['password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
				apply_filters( 'authenticate', null, $username, $password );
			}
		}

		/**
		 * Handles logout form.
		 *
		 * @return void
		 */
		public function wpns_logout_form() {
			global $mo_wpns_utility, $mo2fdb_queries;
			if ( ! $mo_wpns_utility->check_empty_or_null( get_option( 'mo_wpns_registration_status' ) ) ) {
				delete_option( 'mo2f_email' );
			}
			delete_option( 'mo2f_customerKey' );
			delete_option( 'mo2f_api_key' );
			delete_option( 'mo2f_customer_token' );
			delete_option( 'mo_wpns_transactionId' );
			delete_option( 'mo_wpns_registration_status' );
			delete_option( 'mo_2factor_admin_registration_status' );
			delete_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' );
			if ( ! MO2F_IS_ONPREM ) {
				$mo2fdb_queries->mo2f_delete_cloud_meta_on_account_remove();

			}
			$two_fa_settings = new Miniorange_Authentication();
			$two_fa_settings->mo2f_auth_deactivate();

		}

		/**
		 * Handles new release mail notification form.
		 *
		 * @return void
		 */
		public function waf_settings_mail_form_notify() {

			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			}
			$mo2f_all_mail_noyifying = '';
			if ( isset( $_POST['send_mail'] ) ) {
				$mo2f_all_mail_noyifying = sanitize_text_field( wp_unslash( $_POST['send_mail'] ) );
				update_site_option( 'mo2f_mail_notify_new_release', $mo2f_all_mail_noyifying );
				wp_send_json_success();
			} else {
				update_site_option( 'mo2f_mail_notify_new_release', $mo2f_all_mail_noyifying );
				wp_send_json_error( 'class-wpns-ajax' );

			}
		}

		/**
		 * Updates mo2f_mail_notify option in options table if send_mail is set.
		 *
		 * @return void
		 */
		public function waf_settings_i_p_mail_form() {

			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			}

			$mo2f_mail_notifying_i_p = '';
			if ( isset( $_POST['send_mail'] ) ) {
				$mo2f_mail_notifying_i_p = sanitize_email( ( wp_unslash( $_POST['send_mail'] ) ) );
				update_site_option( 'mo2f_mail_notify', $mo2f_mail_notifying_i_p );
				wp_send_json_success();
			} else {
				update_site_option( 'mo2f_mail_notify', $mo2f_mail_notifying_i_p );
				wp_send_json_error( 'class-wpns-ajax' );

			}
		}

		/**
		 * Updates the plan names in the options table.
		 *
		 * @return void
		 */
		public function wpns_all_plans() {

			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			}
			$mo2f_all_plannames = isset( $_POST['planname'] ) ? sanitize_text_field( wp_unslash( $_POST['planname'] ) ) : '';
			$mo_2fa_plan_type   = isset( $_POST['planType'] ) ? sanitize_text_field( wp_unslash( $_POST['planType'] ) ) : '';
			update_site_option( 'mo2f_planname', $mo2f_all_plannames );
			if ( 'addon_plan' === $mo2f_all_plannames ) {
				update_site_option( 'mo2f_planname', 'addon_plan' );
				update_site_option( 'mo_2fa_addon_plan_type', $mo_2fa_plan_type );
				update_option( 'mo2f_customer_selected_plan', $mo_2fa_plan_type );
			} elseif ( '2fa_plan' === $mo2f_all_plannames ) {
				update_site_option( 'mo2f_planname', '2fa_plan' );
				update_site_option( 'mo_2fa_plan_type', $mo_2fa_plan_type );
				update_option( 'mo2f_customer_selected_plan', $mo_2fa_plan_type );
			}
		}

		/**
		 * Includes ip-blocking.php .
		 *
		 * @return void
		 */
		public function wpns_handle_i_p_blocking() {

			global $mo2f_dir_name;
			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			} else {

				include_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'ip-blocking.php';
			}

		}
		/**
		 * Includes ip-blocking.php .
		 *
		 * @return void
		 */
		public function wpns_whitelist_ip() {
			global $mo2f_dir_name;
			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );
				exit;
			} else {
				include_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'ip-blocking.php';
			}
		}

		/**
		 * Creates ip look up template.
		 *
		 * @return void
		 */
		public function wpns_ip_lookup() {

			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			} else {
				$ip = isset( $_POST['IP'] ) ? sanitize_text_field( wp_unslash( $_POST['IP'] ) ) : '';
				if ( ! preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $ip ) ) {
					wp_send_json_error( 'INVALID_IP_FORMAT' );

				} elseif ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					wp_send_json_error( 'INVALID_IP' );

				}
				$result = wp_remote_get( 'http://www.geoplugin.net/json.gp?ip=' . $ip );

				if ( ! is_wp_error( $result ) ) {
					$result = json_decode( wp_remote_retrieve_body( $result ), true );
				}

				try {
					$timeoffset = timezone_offset_get( new DateTimeZone( $result['geoplugin_timezone'] ), new DateTime( 'now' ) );
					$timeoffset = $timeoffset / 3600;

				} catch ( Exception $e ) {
					$result['geoplugin_timezone'] = '';
					$timeoffset                   = '';
				}
				$ip_look_up_template = MoWpnsConstants::IP_LOOKUP_TEMPLATE;
				if ( $result['geoplugin_request'] === $ip ) {
					$ip_parameters = array(
						'status'           => 'geoplugin_status',
						'ip'               => 'geoplugin_request',
						'region'           => 'geoplugin_region',
						'country'          => 'geoplugin_countryName',
						'city'             => 'geoplugin_city',
						'continent'        => 'geoplugin_continentName',
						'latitude'         => 'geoplugin_latitude',
						'longitude'        => 'geoplugin_longitude',
						'timezone'         => 'geoplugin_timezone',
						'curreny_code'     => 'geoplugin_currencyCode',
						'curreny_symbol'   => 'geoplugin_currencySymbol',
						'per_dollar_value' => 'geoplugin_currencyConverter',
						'offset'           => $timeoffset,
					);

					foreach ( $ip_parameters as $parameter => $value ) {
						$ip_look_up_template = str_replace( '{{' . $parameter . '}}', $result[ $value ], $ip_look_up_template );
					}
					$result['ipDetails'] = $ip_look_up_template;
				} else {
					$result['ipDetails']['status'] = 'ERROR';
				}
				wp_send_json( $result );
			}
		}
	}
	new Wpns_Ajax();
}
