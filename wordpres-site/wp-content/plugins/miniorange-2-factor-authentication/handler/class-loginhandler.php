<?php
/**
 * File contains function related to login flow.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'LoginHandler' ) ) {
	/**
	 * Class LoginHandler
	 */
	class LoginHandler {

		/**
		 * Class LoginHandler constructor
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'mo_wpns_init' ) );
			if ( get_site_option( 'mo2f_restrict_restAPI' ) ) {
				add_action( 'rest_api_init', array( $this, 'mo_block_rest_api' ) );
			}

			add_action( 'wp_login', array( $this, 'mo_wpns_login_success' ) );
			add_action( 'wp_login_failed', array( $this, 'mo_wpns_login_failed' ) );

			if ( get_option( 'mo_wpns_activate_recaptcha_for_woocommerce_registration' ) ) {
				add_action( 'woocommerce_register_post', array( $this, 'wooc_validate_user_captcha_register' ), 1, 3 );
			}
		}

		/**
		 * Blocks the rest api and show 403 error screen.
		 *
		 * @return void
		 */
		public function mo_block_rest_api() {
			if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/wp-json/wp/v2/users' ) ) {
				include_once 'mo-block.html';
				exit;
			}
		}

		/**
		 * Initiates network security flow.
		 *
		 * @return void
		 */
		public function mo_wpns_init() {
			add_action( 'show_user_profile', array( $this, 'twofa_on_user_profile' ), 10, 3 );
			add_action( 'edit_user_profile', array( $this, 'twofa_on_user_profile' ), 10, 3 );
			add_action( 'personal_options_update', array( $this, 'user_two_factor_options_update' ), 10, 3 );
			add_action( 'edit_user_profile_update', array( $this, 'user_two_factor_options_update' ), 10, 3 );
			global $mo_wpns_utility,$mo2f_dir_name;
			$w_a_f_enabled = get_option( 'WAFEnabled' );
			$waflevel      = get_option( 'WAF' );
			if ( 1 === $w_a_f_enabled ) {
				if ( 'PluginLevel' === $waflevel ) {
					if ( file_exists( $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'WAF' . DIRECTORY_SEPARATOR . 'mo-waf-plugin.php' ) ) {
						include_once $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'WAF' . DIRECTORY_SEPARATOR . 'mo-waf-plugin.php';
					}
				}
			}

			$user_ip        = $mo_wpns_utility->get_client_ip();
			$user_ip        = sanitize_text_field( $user_ip );
			$mo_wpns_config = new MoWpnsHandler();
			$is_whitelisted = $mo_wpns_config->is_whitelisted( $user_ip );
			$is_ip_blocked  = false;
			if ( ! $is_whitelisted ) {
				$is_ip_blocked = $mo_wpns_config->is_ip_blocked_in_anyway( $user_ip );
			}
			if ( $is_ip_blocked ) {
				include_once 'mo-block.html';
				exit;
			}
		}

		/**
		 * Includes user-profile-2fa.php file if exists.
		 *
		 * @param object $user User object.
		 * @return void
		 */
		public function twofa_on_user_profile( $user ) {
			global $mo2f_dir_name;
			if ( file_exists( $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'user-profile-2fa.php' ) ) {
				include_once $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'user-profile-2fa.php';
			}
		}

		/**
		 * Includes user-profile-2fa-update.php file if exists.
		 *
		 * @param integer $user_id User Id.
		 * @return void
		 */
		public function user_two_factor_options_update( $user_id ) {
			global $mo2f_dir_name;
			if ( file_exists( $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'user-profile-2fa-update.php' ) ) {
				include_once $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'user-profile-2fa-update.php';
			}
		}

		/**
		 * New IP detected alert email will be sent on user's email ID.
		 *
		 * @return void
		 */
		public function mo2f_ip_email_send() {
			global $mo_wpns_utility, $mo2fdb_queries;
			$user_ip  = $mo_wpns_utility->get_client_ip();
			$user_ip  = sanitize_text_field( $user_ip );
			$user     = wp_get_current_user();
			$user_id  = $user->ID;
			$meta_key = 'mo2f_user_IP';
			add_user_meta( $user->ID, $meta_key, $user_ip );
			$email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
			if ( empty( $email ) ) {
				$email = $user->user_email;
			}
			if ( get_user_meta( $user->ID, $meta_key ) ) {
				$check_ip = get_user_meta( $user->ID, $meta_key )[0];

				if ( $check_ip !== $user_ip ) {
					$subject = 'Alert: New IP Detected';
					$message = mo_i_p_template();
					$headers = array( 'Content-Type: text/html; charset=UTF-8' );
					if ( is_email( $email ) ) {
						wp_mail( $email, $subject, $message, $headers );
					}
				}
			}
		}


		/**
		 * Adds transaction report to network transaction table and updates the users with password option in the options table.
		 *
		 * @param string $username Username of the user.
		 * @return mixed
		 */
		public function mo_wpns_login_success( $username ) {
			global $mo_wpns_utility, $mo2fdb_queries;
			if ( get_site_option( 'mo2f_mail_notify' ) === 'on' ) {
				$this->mo2f_ip_email_send();
				$mo_wpns_config = new MoWpnsHandler();
				$user_ip        = $mo_wpns_utility->get_client_ip();
				$mo_wpns_config->move_failed_transactions_to_past_failed( $user_ip );
				$user              = get_user_by( 'login', $username );
				$user_roles        = get_userdata( $user->ID )->roles;
				$user_role_enabled = 0;
				foreach ( $user_roles as $user_role ) {
					if ( get_site_option( 'mo2fa_' . $user_role ) ) {
						$user_role_enabled = 1;
						break;
					}
				}
				$is_customer_registered = 'SUCCESS' === $mo2fdb_queries->get_user_detail( 'user_registration_with_miniorange', $user->ID );
				if ( get_option( 'mo_wpns_enable_unusual_activity_email_to_user' ) && $user_role_enabled && $is_customer_registered ) {
					$mo_wpns_utility->send_notification_to_user_for_unusual_activities( $username, $user_ip, MoWpnsConstants::LOGGED_IN_FROM_NEW_IP );
				}
				if ( 'true' === get_site_option( 'mo2f_enable_login_report' ) ) {
					$mo_wpns_config->add_transactions( $user_ip, $username, MoWpnsConstants::LOGIN_TRANSACTION, MoWpnsConstants::SUCCESS );
				}
			}

		}

		/**
		 * Adds the failed login entry in network transactions table and sends the notification regarding the same on administrator's email ID.
		 *
		 * @param string $username Username of the user.
		 * @return void
		 */
		public function mo_wpns_login_failed( $username ) {
			global $mo_wpns_utility, $mo2fdb_queries;
			$user_ip = $mo_wpns_utility->get_client_ip();
			if ( empty( $user_ip ) || empty( $username ) || ! MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_brute_force', 'get_option' ) ) {
				return;
			}
			$mo_wpns_config = new MoWpnsHandler();
			$is_whitelisted = $mo_wpns_config->is_whitelisted( $user_ip );
			if ( 'true' === get_site_option( 'mo2f_enable_login_report' ) ) {
				$mo_wpns_config->add_transactions( $user_ip, $username, MoWpnsConstants::LOGIN_TRANSACTION, MoWpnsConstants::FAILED );
			}
			if ( ! $is_whitelisted ) {
				$user              = get_user_by( 'login', $username );
				$user_roles        = get_userdata( $user->ID )->roles;
				$user_role_enabled = 0;
				foreach ( $user_roles as $user_role ) {
					if ( get_site_option( 'mo2fa_' . $user_role ) ) {
						$user_role_enabled = 1;
						break;
					}
				}
				$is_customer_registered = 'SUCCESS' === $mo2fdb_queries->get_user_detail( 'user_registration_with_miniorange', $user->ID );
				if ( get_option( 'mo_wpns_enable_unusual_activity_email_to_user' ) && $user_role_enabled && $is_customer_registered ) {
					$mo_wpns_utility->send_notification_to_user_for_unusual_activities( $username, $user_ip, MoWpnsConstants::FAILED_LOGIN_ATTEMPTS_FROM_NEW_IP );
				}
			}
		}

	}
	new LoginHandler();
}
