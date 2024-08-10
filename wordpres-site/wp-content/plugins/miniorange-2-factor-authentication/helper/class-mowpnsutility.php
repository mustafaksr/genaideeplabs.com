<?php
/** The miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
 *
 * @package      miniorange-2-factor-authentication/helper
 * @license      http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

namespace TwoFA\Helper;

use TwoFA\Traits\Instance;
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Onprem\Miniorange_Authentication;

require_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'traits' . DIRECTORY_SEPARATOR . 'class-instance.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MoWpnsUtility' ) ) {
	/**
	 * This class has all functions used throughout the plugin to log in through mobile authentication as an additional layer of security over password.
	 */
	class MoWpnsUtility {

		use Instance;

		/**
		 * To check whether the customer has registered a miniOrange account.
		 *
		 * @return boolean
		 */
		public static function icr() {
			$email        = get_option( 'mo2f_email' );
			$customer_key = get_option( 'mo2f_customerKey' );
			if ( ! $email || ! $customer_key || ! is_numeric( trim( $customer_key ) ) ) {
				return 0;
			} else {
				return 1;
			}
		}

		/**
		 * To check whether the variable is empty or null
		 *
		 * @param string $value variable that needs to chekc if empty or null.
		 * @return boolean
		 */
		public static function check_empty_or_null( $value ) {
			if ( ! isset( $value ) || empty( $value ) ) {
				return true;
			}
			return false;
		}
		/**
		 * To generate the random string.
		 *
		 * @return string
		 */
		public static function rand() {
			$length        = wp_rand( 0, 15 );
			$characters    = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$random_string = '';
			for ( $i = 0; $i < $length; $i++ ) {
				$random_string .= $characters[ wp_rand( 0, strlen( $characters ) - 1 ) ];
			}
			return $random_string;
		}
		/**
		 * To check if the curl extension in installed.
		 *
		 * @return boolean
		 */
		public static function is_curl_installed() {
			if ( in_array( 'curl', get_loaded_extensions(), true ) ) {
				return 1;
			} else {
				return 0;
			}
		}
		/**
		 * TO check if the given Ip is valid or not.
		 *
		 * @param string $ip Ip that needs to be checked.
		 * @return boolean
		 */
		public static function mo2f_is_valid_ip( $ip ) {
			$new_ip = explode( ',', $ip );
			if ( is_array( $new_ip ) ) {
				$ip = $new_ip[0];
			}
			return filter_var( self::get_unique_ip( $ip ), FILTER_VALIDATE_IP ) !== false;
		}
		/**
		 * To get the IP of the user.
		 *
		 * @return string
		 */
		public static function get_client_ip() {
			if ( ( isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) && ! empty( $_SERVER['REMOTE_ADDR'] ) && self::mo2f_is_valid_ip( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ) ) ) {
				return self::get_unique_ip( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) );
			} else {
				return 'UNKNOWN';
			}
		}
		/**
		 * Get single or first IP in the REMOTE_ADDR paramter of the SERVER.
		 *
		 * @param string $ip Available IP in the REMOTE_ADDR.
		 * @return string
		 */
		public static function get_unique_ip( $ip ) {
			$ip = explode( ',', $ip );
			if ( is_array( $ip ) ) {
				return sanitize_text_field( $ip[0] );
			}
			return sanitize_text_field( $ip );
		}
		/**
		 * To check if the email ID is valid or not.
		 *
		 * @param string $email Email that needs to be checked.
		 * @return boolean
		 */
		public static function check_if_valid_email( $email ) {
			$emailarray = explode( '@', $email );
			if ( 2 === count( $emailarray ) ) {
				return in_array( trim( $emailarray[1] ), MoWpnsConstants::$domains, true );
			} else {
				return false;
			}
		}
		/**
		 * To get the URL of the page
		 *
		 * @return string
		 */
		public static function get_current_url() {
			$protocol = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== sanitize_text_field( sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) ) || isset( $_SERVER['SERVER_PORT'] ) && 443 === sanitize_text_field( wp_unslash( $_SERVER['SERVER_PORT'] ) ) ) ? 'https://' : 'http://';
			$url      = $protocol . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
			return $url;
		}
		/**
		 * To fetch option from options table or sitemeta.
		 *
		 * @param string $value option vaue to be checked in the table.
		 * @param string $type To distinguish between site_option and get_option.
		 * @return string
		 */
		public static function get_mo2f_db_option( $value, $type ) {
			if ( 'site_option' === $type ) {
				$db_value = get_site_option( $value, $GLOBALS[ $value ] );
			} else {
				$db_value = get_option( $value, $GLOBALS[ $value ] );
			}
			return $db_value;
		}
		/**
		 * Collect data for the plugin configurations.
		 *
		 * @param boolean $send_all_configuration To check whether to send all the plugin configuration.
		 * @return string
		 */
		public static function mo_2fa_send_configuration( $send_all_configuration = false ) {
			global $mo2fdb_queries;
			$user_object   = wp_get_current_user();
			$other_methods = $mo2fdb_queries->get_all_user_2fa_methods();
			$method_count  = array_count_values( $other_methods );
			$show_methods  = array();
			foreach ( $method_count as $method => $count ) {
				if ( ! empty( $method ) ) {
					array_push( $show_methods, ' ' . $method . ' (' . $count . ')' );
				}
			}
			$show_methods                 = implode( ',', $show_methods );
			$key                          = get_option( 'mo2f_customerKey' );
			$is_plugin_active_for_network = is_plugin_active_for_network( MoWpnsConstants::TWO_FACTOR_SETTINGS );
			$is_onprem                    = MO2F_IS_ONPREM;
			$pricing_page_visits          = get_site_option( 'mo2fa_visit', 0 );
			$no_of_2fa_users              = $mo2fdb_queries->get_no_of_2fa_users();
			$is_inline_used               = get_site_option( 'mo2f_is_inline_used' );
			$login_with_mfa_use           = get_site_option( 'mo2f_login_with_mfa_use' );
			$email_transactions           = self::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
			$sms_transactions             = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) ? get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) : 0;
			$space                        = '<span>&nbsp;&nbsp;&nbsp;</span>';
			$user_count                   = isset( count_users()  ['total_users'] ) ? count_users()  ['total_users'] : '';
			$specific_plugins             = array(
				'UM_Functions'   => 'Ultimate Member',
				'wc_get_product' => 'WooCommerce',
				'pmpro_gateways' => 'Paid MemberShip Pro',
				'MoOTP'          => 'OTP',
			);
			$backup_codes_remaining       = get_user_meta( $user_object->ID, 'mo2f_backup_codes', true );

			if ( is_array( $backup_codes_remaining ) ) {
				$backup_codes_remaining = count( $backup_codes_remaining );
			} else {
				$backup_codes_remaining = 0;
			}
			$plugin_configuration = '<br><br><I>Plugin Configuration :-</I>' . $space . 'On-premise:' . ( $is_onprem ? 'Yes' : 'No' ) . $space . 'Login with MFA:' . ( '1' === $login_with_mfa_use ? 'Yes' : 'No' ) . $space . 'Inline Registration:' . ( '1' === $is_inline_used ? 'Yes' : 'No' ) . $space . 'No. of 2FA users :' . $no_of_2fa_users . $space . 'Total users : ' . $user_count . $space . 'Methods of users:' . ( '' === $show_methods ? 'NONE' : $show_methods ) . $space . 'Email transactions:' . $email_transactions . $space . 'SMS Transactions:' . $sms_transactions . $space . ( is_multisite() ? 'Multisite:Yes' : 'Single-site:Yes' ) . ( ( Miniorange_Authentication::mo2f_is_customer_registered() ) ? ( $space . 'Customer Key:' . $key ) : ( $space . "Customer Registered:'No" ) );

			if ( get_user_meta( $user_object->ID, 'mo_backup_code_generated', true ) || get_user_meta( $user_object->ID, 'mo_backup_code_downloaded', true ) ) {
				$plugin_configuration = $plugin_configuration . $space . 'Backup Codes:' . $backup_codes_remaining . '/5';
			}

			$plugins = '';

			foreach ( $specific_plugins as $class_name => $plugin_name ) {
				if ( class_exists( $class_name ) || function_exists( $class_name ) ) {
					$plugins = $plugins . "<span>&nbsp;</span>'" . $plugin_name . "'";
				}
			}

			$plugin_configuration = $plugin_configuration . ( '' !== $plugins ? $space . 'Installed Plugins:' . $plugins : '' );

			if ( is_multisite() ) {
				$plugin_configuration = $plugin_configuration . $space . ( $is_plugin_active_for_network ? "Network activated:'Yes" : "Site activated:'Yes" );
			}

			$plugin_configuration = $plugin_configuration . $space . 'Pricing Page visits : ' . $pricing_page_visits;

			if ( time() - get_site_option( 'mo_2fa_pnp' ) < 2592000 && ( get_site_option( 'mo_2fa_plan_type' ) || get_site_option( 'mo_2fa_addon_plan_type' ) ) ) {
				$plugin_configuration = $plugin_configuration . $space . "Checked plans:'";

				if ( get_site_option( 'mo_2fa_plan_type' ) ) {
					$plugin_configuration = $plugin_configuration . get_site_option( 'mo_2fa_plan_type' ) . "'";
				}

				if ( get_site_option( 'mo_2fa_addon_plan_type' ) ) {
					$plugin_configuration = $plugin_configuration . "<span>&nbsp;</span>'" . get_site_option( 'mo_2fa_addon_plan_type' ) . "'";
				}
			}

			$plugin_configuration = $plugin_configuration . $space . 'PHP_version:' . phpversion();
			if ( 'on' === get_site_option( 'mo2f_grace_period' ) ) {
				$plugin_configuration = $plugin_configuration . $space . 'Grace Period: ' . esc_html( get_site_option( 'mo2f_grace_period_value' ) ) . '<span>&nbsp;</span>' . esc_html( get_site_option( 'mo2f_grace_period_type' ) );
			}
			$mo2f_wizard_skipped = get_option( 'mo2f_wizard_selected_method' ) ? esc_html( get_option( 'mo2f_wizard_selected_method' ) ) : esc_html( get_option( 'mo2f_wizard_skipped' ) );
			if ( get_option( 'mo2f_wizard_skipped' ) ) {
				$plugin_configuration = $plugin_configuration . $space . 'Setup Wizard Skipped: ' . $mo2f_wizard_skipped;
			} else {
				$plugin_configuration = $plugin_configuration . $space . 'Setup Wizard Skipped: No';
			}

			if ( ! $send_all_configuration ) {
				return $plugin_configuration;
			}

			if ( get_site_option( 'enable_form_shortcode' ) ) {
				$forms = array( 'mo2f_custom_reg_bbpress', 'mo2f_custom_reg_wocommerce', 'mo2f_custom_reg_custom', 'mo2f_custom_reg_pmpro' );
				foreach ( $forms as $form ) {
					if ( get_site_option( $form ) ) {
						$plugin_configuration = $plugin_configuration . $space . $form . ':' . get_option( $form );
					}
				}
			}

			return $plugin_configuration;
		}

		/**
		 * Sends unusual activity email to the users.
		 *
		 * @param string $username username of the user.
		 * @param string $ip_adress ip adress of the user.
		 * @param string $reason Reason of unusual activity.
		 * @return mixed
		 */
		public function send_notification_to_user_for_unusual_activities( $username, $ip_adress, $reason ) {
			$content = '';

			if ( get_option( $ip_adress . $reason ) ) {
				return wp_json_encode(
					array(
						'status'        => 'SUCCESS',
						'statusMessage' => 'SUCCESS',
					)
				);
			}
			$user = get_user_by( 'login', $username );
			if ( $user && ! empty( $user->user_email ) ) {
				$to_email = $user->user_email;
			} else {
				return;
			}

			$mo_wpns_config = new MoWpnsHandler();
			if ( $mo_wpns_config->is_email_sent_to_user( $username, $ip_adress ) ) {
				return;
			}

			$from_email = get_option( 'mo2f_email' );
			$subject    = 'Sign in from new location for your user account | ' . get_bloginfo();

			if ( get_option( 'custom_user_template' ) ) {
				$content = get_option( 'custom_user_template' );
				$content = str_replace( '##ipaddress##', $ip_adress, $content );
				$content = str_replace( '##username##', $username, $content );
			} else {
				$content = $this->get_message_content( $reason, $ip_adress, $username, $from_email );
			}

			$mo_wpns_config->audit_email_notification_sent_to_user( $username, $ip_adress, $reason );
			$status = $this->wp_mail_send_notification( $to_email, $subject, $content, $from_email );
			return $status;
		}

		/**
		 * Gets the content for the email according to the reason.
		 *
		 * @param string $reason Reason of sending notification.
		 * @param string $ip_adress ip adress of the user.
		 * @param string $username username of the user.
		 * @param string $from_email Email adress of the administrator.
		 * @return string
		 */
		public function get_message_content( $reason, $ip_adress, $username = null, $from_email = null ) {
			switch ( $reason ) {
				case MoWpnsConstants::LOGIN_ATTEMPTS_EXCEEDED:
					$content = 'Hello,<br><br>The user with IP Address <b>' . $ip_adress . '</b> has exceeded allowed failed login attempts on your website <b>' . get_bloginfo() . '</b> and we have blocked his IP address for further access to website.<br><br>You can login to your WordPress dashaboard to check more details.<br><br>Thanks,<br>miniOrange';
					return $content;
				case MoWpnsConstants::IP_RANGE_BLOCKING:
					$content = "Hello,<br><br>The user's IP Address <b>" . $ip_adress . '</b> was found in IP Range specified by you in Advanced IP Blocking and we have blocked his IP address for further access to your website <b>' . get_bloginfo() . '</b>.<br><br>You can login to your WordPress dashaboard to check more details.<br><br>Thanks,<br>miniOrange';
					return $content;
				case MoWpnsConstants::BLOCKED_BY_ADMIN:
					$content = 'Hello,<br><br>The user with IP Address <b>' . $ip_adress . '</b> has blocked by admin and we have blocked his IP address for further access to website.<br><br>You can login to your WordPress dashaboard to check more details.<br><br>Thanks,<br>miniOrange';
					return $content;
				case MoWpnsConstants::LOGGED_IN_FROM_NEW_IP:
					$content = 'Hello ' . $username . ',<br><br>Your account was logged in from new IP Address <b>' . $ip_adress . '</b> on website <b>' . get_bloginfo() . "</b>. Please <a href='mailto:" . $from_email . "'>contact us</a> if you don't recognise this activity.<br><br>Thanks,<br>" . get_bloginfo();
					return $content;
				case MoWpnsConstants::FAILED_LOGIN_ATTEMPTS_FROM_NEW_IP:
					$subject = 'Someone trying to access you account | ' . get_bloginfo();
					$content = 'Hello ' . $username . ',<br><br>Someone tried to login to your account from new IP Address <b>' . $ip_adress . '</b> on website <b>' . get_bloginfo() . "</b> with failed login attempts. Please <a href='mailto:" . $from_email . "'>contact us</a> if you don't recognise this activity.<br><br>Thanks,<br>" . get_bloginfo();
					return $content;
				default:
					if ( is_null( $username ) ) {
						$content = 'Hello,<br><br>The user with IP Address <b>' . $ip_adress . '</b> has exceeded allowed trasaction limit on your website <b>' . get_bloginfo() . '</b> and we have blocked his IP address for further access to website.<br><br>You can login to your WordPress dashaboard to check more details.<br><br>Thanks,<br>miniOrange';
					} else {
						$content = 'Hello ' . $username . ',<br><br>Your account was logged in from new IP Address <b>' . $ip_adress . '</b> on website <b>' . get_bloginfo() . "</b>. Please <a href='mailto:" . $from_email . "'>contact us</a> if you don't recognise this activity.<br><br>Thanks,<br>" . get_bloginfo();
					}
					return $content;
			}
		}

		/**
		 * Sends email.
		 *
		 * @param string $to_email The email adress to whom the email will be sent.
		 * @param string $subject The subject of the email.
		 * @param string $content Content in the email.
		 * @return void
		 */
		public function wp_mail_send_notification( $to_email, $subject, $content ) {
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			wp_mail( $to_email, $subject, $content, $headers );

		}

	}
}
