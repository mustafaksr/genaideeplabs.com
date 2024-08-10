<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, WPShield_Standard.Security.DisallowBrandAndImproperPluginName.ImproperPluginName, WordPress.Files.FileName.NotHyphenatedLowercase -- Cannot change the main settings filename
/**
 * Main plugin settings file for miniOrange 2-factor Authentication.
 *
 * @package miniOrange 2FA
 */

/**
 * Plugin Name: miniOrange 2 Factor Authentication
 * Plugin URI: https://miniorange.com
 * Description: This TFA plugin provides various two-factor authentication methods as an additional layer of security after the default WordPress login. We Support Google/Authy/LastPass/Microsoft Authenticator, QR Code, Push Notification, Soft Token and Security Questions(KBA) for 3 User in the free version of the plugin.
 * Version: 5.8.4
 * Author: miniOrange
 * Author URI: https://miniorange.com
 * Text Domain: miniorange-2-factor-authentication
 * License: MIT/Expat
 */

namespace TwoFA;

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Onprem\Miniorange_Authentication;
use TwoFA\Views\Mo2f_Setup_Wizard;
use TwoFACustomRegFormShortcode;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'mo2f-db-options.php';
require dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'new-release-email.php';
require dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'email-ip-address.php';

define( 'MO_HOST_NAME', 'https://login.xecurify.com' );
define( 'MO2F_VERSION', '5.8.4' );
define( 'MO2F_PLUGIN_URL', ( plugin_dir_url( __FILE__ ) ) );
define( 'MO2F_TEST_MODE', false );
define( 'MO2F_IS_ONPREM', get_option( 'is_onprem', 1 ) );
define( 'MO2F_PREMIUM_PLAN', true );
define( 'DEFAULT_GOOGLE_APPNAME', preg_replace( '#^https?://#i', '', home_url() ) );

global $main_dir, $image_path;
$main_dir   = plugin_dir_url( __FILE__ );
$image_path = plugin_dir_url( __FILE__ );

if ( ! class_exists( 'Miniorange_TwoFactor' ) ) {
	/**
	 * Includes all the hooks and actions in the main plugin file.
	 */
	class Miniorange_TwoFactor {

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'mo2f_add_plugin_action_link' ), 10, 1 );
			register_deactivation_hook( __FILE__, array( $this, 'mo_wpns_deactivate' ) );
			register_activation_hook( __FILE__, array( $this, 'mo_wpns_activate' ) );
			add_action( 'admin_menu', array( $this, 'mo_wpns_widget_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'mo_wpns_settings_style' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'mo_wpns_settings_script' ) );
			add_action( 'admin_init', array( $this, 'mo2f_change_method_names_from_db' ) );
			add_action( 'admin_init', array( $this, 'miniorange_reset_save_settings' ) );
			add_action( 'admin_init', array( $this, 'mo2f_mail_send' ) );
			add_filter( 'manage_users_columns', array( $this, 'mo2f_mapped_email_column' ) );
			add_action( 'manage_users_custom_column', array( $this, 'mo2f_mapped_email_column_content' ), 10, 3 );
			add_action( 'admin_notices', array( $this, 'mo2f_notices' ) );
			$actions = add_filter( 'user_row_actions', array( $this, 'miniorange_reset_users' ), 10, 2 );
			add_action( 'admin_footer', array( $this, 'feedback_request' ) );
			add_action( 'plugins_loaded', array( $this, 'mo2fa_load_textdomain' ) );
			if ( ! defined( 'DISALLOW_FILE_EDIT' ) && get_option( 'mo2f_disable_file_editing' ) ) {
				define( 'DISALLOW_FILE_EDIT', true );
			}
			$this->includes();
			$notify = new Miniorange_Security_Notification();
			add_action( 'wp_dashboard_setup', array( $notify, 'my_custom_dashboard_widgets' ) );
			add_action( 'plugins_loaded', array( $this, 'mo2f_add_wizard_actions' ), 1 );
			$custom_short = new TwoFACustomRegFormShortcode();
			add_action( 'admin_init', array( $this, 'mo2f_enable_register_shortcode' ) );
			add_action( 'admin_init', array( $custom_short, 'mo_enqueue_shortcode' ) );
			add_action( 'elementor/init', array( $this, 'mo2fa_login_elementor_note' ) );
			add_shortcode( 'mo2f_enable_register', array( $this, 'mo2f_enable_register_shortcode' ) );
		}

		/**
		 * Changes the method names from database.
		 *
		 * @return void
		 */
		public function mo2f_change_method_names_from_db() {
			if ( ! get_site_option( 'mo2f_is_methods_name_updated' ) ) {
				global $mo2fdb_queries;
				$configured_methods = $mo2fdb_queries->mo2f_get_distinct_configured_methods();
				if ( count( $configured_methods ) <= 0 ) {
					return;
				}
				$mo2fdb_queries->mo2f_run_queries_to_change_method_names( MoWpnsConstants::$mo2f_small_to_cap, $configured_methods );
			}

		}

		/**
		 * Includes scripts and localize parameters for elementor login form.
		 *
		 * @return void
		 */
		public function mo2fa_login_elementor_note() {
			global $main_dir;

			if ( ! is_user_logged_in() ) {
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'mo2fa_elementor_script', $main_dir . 'includes/js/mo2fa_elementor.min.js', array(), MO2F_VERSION, false );

				wp_localize_script(
					'mo2fa_elementor_script',
					'my_ajax_object',
					array(
						'ajax_url'          => get_site_url() . '/login/',
						'nonce'             => wp_create_nonce( 'miniorange-2-factor-login-nonce' ),
						'mo2f_login_option' => MoWpnsUtility::get_mo2f_db_option( 'mo2f_login_option', 'get_option' ),
						'mo2f_enable_login_with_2nd_factor' => get_option( 'mo2f_enable_login_with_2nd_factor' ),
					)
				);
			}
		}
		/**
		 * Localizing scripts for registration shortcode and includes required CSS and js files.done
		 *
		 * @return void
		 */
		public function mo2f_enable_register_shortcode() {
			$submit_selector       = get_site_option( 'mo2f_custom_submit_selector' );
			$form_submit           = get_site_option( 'mo2f_form_submit_after_validation' );
			$form_name             = get_site_option( 'mo2f_custom_form_name' );
			$email_field           = get_site_option( 'mo2f_custom_email_selector' );
			$auth_type             = get_site_option( 'mo2f_custom_auth_type' );
			$phone_selector        = get_site_option( 'mo2f_custom_phone_selector' );
			$notification_selector = get_site_option( 'mo2f_custom_notification_selector', '#otpmessage' );
			$success_class         = get_site_option( 'mo2f_custom_success_selector', 'mo2f_green' );
			$error_class           = get_site_option( 'mo2f_custom_error_selector', 'mo2f_red' );
			if ( get_site_option( 'mo2f_customerkey' ) > 0 ) {
				$is_registered = get_site_option( 'mo2f_customerkey' );
			} else {
				$is_registered = 'false';
			}

			$form_ajax = array( '.um-form', '.wpcf7-form', '#um-submit-btn' );

			$form_r_c_p = array( '#rcp_registration_form', '.rcp_form', '#rc_registration_form', '.rc_form' );
			$form_mepr  = array( '.mepr-signup-form' );

			if ( in_array( $form_name, $form_ajax, true ) ) {
				$java_script = 'includes/js/custom-form-ajax.min.js';
			} elseif ( in_array( $form_name, $form_r_c_p, true ) ) {
				$java_script = 'includes/js/custom-ajax-rcp.min.js';
			} elseif ( in_array( $form_name, $form_mepr, true ) ) {
				$java_script = 'includes/js/custom-ajax-mepr.min.js';
			} else {
				$java_script = 'includes/js/custom-form.min.js';
			}

			update_site_option( 'mo2f_country_code', array( 'US', '+1' ) );
			wp_enqueue_style( 'mo2f_intl_tel_style', plugin_dir_url( __FILE__ ) . 'includes/css/phone.min.css', array(), MO2F_VERSION );
			$country_details = is_array( get_site_option( 'mo2f_country_code' ) ) ? wp_unslash( get_site_option( 'mo2f_country_code' ) ) : array();
			wp_enqueue_script( 'mo2f_intl_tel_script', plugin_dir_url( __FILE__ ) . 'includes/js/phone.min.js', array( 'jquery' ), MO2F_VERSION, false );
			wp_localize_script( 'mo2f_intl_tel_script', 'countryDetails', $country_details );
			wp_register_script( 'mo2f_otpVerification', plugin_dir_url( __FILE__ ) . $java_script, array( 'jquery' ), MO2F_VERSION, false );
			wp_localize_script(
				'mo2f_otpVerification',
				'otpverificationObj',
				array(
					'siteURL'              => admin_url( 'admin-ajax.php' ),
					'nonce'                => wp_create_nonce( 'ajax-nonce' ),
					'authType'             => $auth_type,
					'submitSelector'       => $submit_selector,
					'formSubmit'           => $form_submit,
					'formname'             => $form_name,
					'emailselector'        => $email_field,
					'isRegistered'         => $is_registered,
					'phoneSelector'        => $phone_selector,
					'errorClass'           => $error_class,
					'successClass'         => $success_class,
					'notificationSelector' => $notification_selector,
					'loaderUrl'            => plugin_dir_url( __FILE__ ) . 'includes/images/loader.gif',
					'isEnabledShortcode'   => get_site_option( 'enable_form_shortcode' ),
				)
			);

			wp_localize_script(
				'mo2f_otpVerification',
				'otpverificationStringsObj',
				array(
					'contact_admin'         => __( 'Contact Site Administrator', 'miniorange-2-factor-authentication' ),
					'sending_otp'           => __( 'Sending OTP ', 'miniorange-2-factor-authentication' ),
					'invalid_phone'         => __( 'Invalid Phone Number', 'miniorange-2-factor-authentication' ),
					'phone_num'             => __( 'Phone Number', 'miniorange-2-factor-authentication' ),
					'invalid_email'         => __( 'Invalid Email Address', 'miniorange-2-factor-authentication' ),
					'send_otp'              => __( 'Send OTP ', 'miniorange-2-factor-authentication' ),
					'enter_otp'             => __( 'Enter OTP ', 'miniorange-2-factor-authentication' ),
					'resend_otp'            => __( 'Resend OTP ', 'miniorange-2-factor-authentication' ),
					'validate_otp'          => __( 'Validate OTP ', 'miniorange-2-factor-authentication' ),
					'otp_sent_phone'        => __( 'An OTP will be sent to your Mobile Number', 'miniorange-2-factor-authentication' ),
					'otp_sent_both'         => __( 'An OTP will be sent to your Mobile Number and Email', 'miniorange-2-factor-authentication' ),
					'otp_sent_email'        => __( 'An OTP will be sent to your Email ID', 'miniorange-2-factor-authentication' ),
					'already_validated'     => __( 'Already Validated', 'miniorange-2-factor-authentication' ),
					'validate_phone'        => __( 'Please Validate Phone first', 'miniorange-2-factor-authentication' ),
					'validate_email'        => __( 'Please Validate Email first', 'miniorange-2-factor-authentication' ),
					'phone_field_not_found' => __( 'miniOrange : Phone Field not Found.', 'miniorange-2-factor-authentication' ),
					'email_field'           => __( 'miniOrange : Email Field', 'miniorange-2-factor-authentication' ),
					'not_found'             => __( ' not Found. Please check Selector', 'miniorange-2-factor-authentication' ),
					'validate_both'         => __( 'Please Validate Email and Phone first', 'miniorange-2-factor-authentication' ),
					'account_register'      => __( 'miniOrange : Register/Login with miniOrange to Enable 2FA for this Form', 'miniorange-2-factor-authentication' ),
					'register'              => __( 'Register', 'miniorange-2-factor-authentication' ),
					'validation_error'      => __( 'Error Validating OTP', 'miniorange-2-factor-authentication' ),
					'phone_validated'       => __( 'Phone Number Validated', 'miniorange-2-factor-authentication' ),
				)
			);
			wp_enqueue_script( 'mo2f_otpVerification' );
			// Register Shortcode JavaScript And Pass Parameters To JS.
		}

		/**
		 * As on plugins.php page not in the plugin.
		 *
		 * @return void
		 */
		public function feedback_request() {
			if ( isset( $_SERVER['PHP_SELF'] ) && 'plugins.php' !== basename( esc_url_raw( wp_unslash( $_SERVER['PHP_SELF'] ) ) ) ) {
				return;
			}
			global $mo2f_dir_name;

			$email = get_option( 'mo2f_email' );
			if ( empty( $email ) ) {
				$user  = wp_get_current_user();
				$email = $user->user_email;
			}

			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_script( 'utils' );
			wp_enqueue_style( 'mo_wpns_admin_plugins_page_style', plugins_url( '/includes/css/style_settings.min.css', __FILE__ ), array(), MO2F_VERSION );

			include $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'feedback-form.php';
		}
		/**
		 * Function tells where to look for translations.
		 */
		public function mo2fa_load_textdomain() {
			load_plugin_textdomain( 'miniorange-2-factor-authentication', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		}
		/**
		 * Including setup wiard actions.
		 *
		 * @return void
		 */
		public function mo2f_add_wizard_actions() {
			$object = new Mo2f_Setup_Wizard();
			if ( function_exists( 'wp_get_current_user' ) && current_user_can( 'administrator' ) ) {
				add_action( 'admin_init', array( $object, 'mo2f_setup_page' ), 11 );
				add_action( 'admin_init', array( $object, 'mo2f_setup_twofa_dynamically' ), 12 );
			}
		}
		/**
		 * Add notices to admin dashboard.
		 *
		 * @return void
		 */
		public function mo2f_notices() {
			$one_day      = 60 * 60 * 24;
			$dismiss_time = get_site_option( 'notice_dismiss_time' );

			$dismiss_time = ( time() - $dismiss_time ) / $one_day;
			$dismiss_time = (int) $dismiss_time;

			// setting variables for low SMS/email notification.
			global $mo2fdb_queries;
			$user_object                  = wp_get_current_user();
			$mo2f_configured_2_f_a_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user_object->ID );
			$one_day                      = 60 * 60 * 24;
			$day_sms                      = ( time() - get_site_option( 'mo2f_wpns_sms_dismiss' ) ) / $one_day;
			$day_sms                      = floor( $day_sms );
			$day_email                    = ( time() - get_site_option( 'mo2f_wpns_email_dismiss' ) ) / $one_day;
			$day_email                    = floor( $day_email );

			$count = $mo2fdb_queries->mo2f_get_specific_method_users_count( MoWpnsConstants::OTP_OVER_SMS );
			if ( ! get_site_option( 'mo2f_wpns_donot_show_low_email_notice' ) && ( get_site_option( 'cmVtYWluaW5nT1RQ' ) <= 5 ) && ( $day_email >= 1 ) && MoWpnsConstants::OTP_OVER_EMAIL === $mo2f_configured_2_f_a_method ) {
				echo wp_kses_post( MoWpnsMessages::show_message( 'LOW_EMAIL_TRANSACTIONS' ) );
			}
			if ( ! get_site_option( 'mo2f_wpns_donot_show_low_sms_notice' ) && ( get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) <= 4 ) && ( $day_sms >= 1 ) && 0 !== $count ) {
				echo wp_kses_post( MoWpnsMessages::show_message( 'LOW_SMS_TRANSACTIONS' ) );
			}

		}
		/**
		 * Add submenu options in the miniOrange plugin menu.
		 *
		 * @return void
		 */
		public function mo_wpns_widget_menu() {
			global $mo2fdb_queries;
			$user         = wp_get_current_user();
			$user_id      = $user->ID;
			$onprem_admin = get_option( 'mo2f_onprem_admin' );
			$roles        = (array) $user->roles;
			$flag         = 0;
			foreach ( $roles as $role ) {
				if ( get_option( 'mo2fa_' . $role ) === '1' ) {
					$flag = 1;
				}
			}

			$is_2fa_enabled = ( ( $flag ) || ( $user_id === (int) $onprem_admin ) );
			$menu_slug = 'mo_2fa_two_fa';
			if ( get_site_option( 'mo2f_plugin_redirect' ) ) {
				delete_site_option( 'mo2f_plugin_redirect' );
				$menu_slug = 'mo2f-setup-wizard';
			}
			if ( $is_2fa_enabled ) {
				add_menu_page( 'miniOrange 2-Factor', 'miniOrange 2-Factor', 'read', $menu_slug, array( $this, 'mo_wpns' ), plugin_dir_url( __FILE__ ) . 'includes/images/miniorange_icon.png' );
				add_submenu_page( $menu_slug, 'miniOrange 2-Factor', 'Two Factor', 'read', 'mo_2fa_two_fa', array( $this, 'mo_wpns' ), 1 );
			}
			if ( MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_2fa_with_network_security', 'get_option' ) ) {
				add_submenu_page( $menu_slug, 'miniOrange 2-Factor', 'IP Blocking', 'administrator', 'mo_2fa_advancedblocking', array( $this, 'mo_wpns' ), 2 );
			}
			add_submenu_page( $menu_slug, 'miniOrange 2-Factor', 'Reports', 'administrator', 'mo_2fa_reports', array( $this, 'mo_wpns' ), 3 );
			add_submenu_page( $menu_slug, 'miniOrange 2-Factor', 'Troubleshooting', 'administrator', 'mo_2fa_troubleshooting', array( $this, 'mo_wpns' ), 4 );
			add_submenu_page( $menu_slug, 'miniOrange 2-Factor', 'Addons', 'administrator', 'mo_2fa_addons', array( $this, 'mo_wpns' ), 6 );
			add_submenu_page( $menu_slug, 'miniOrange 2-Factor', 'Upgrade', 'administrator', 'mo_2fa_upgrade', array( $this, 'mo_wpns' ), 7 );
			add_submenu_page( $menu_slug, 'miniOrange 2-Factor', 'Notifications', 'administrator', 'mo_2fa_notifications', array( $this, 'mo_wpns' ), 8 );
			add_submenu_page( $menu_slug, 'miniOrange 2-Factor', 'Users\' 2FA Status', 'administrator', 'mo_2fa_all_users', array( $this, 'mo_wpns' ), 9 );
			add_submenu_page( $menu_slug, 'miniOrange 2-Factor', 'Setup Wizard - 2FA Settings', 'administrator', 'mo2f-setup-wizard', array( $this, 'mo_wpns' ), 10 );
			add_submenu_page( 'menu_slug', 'miniOrange 2-Factor', 'Setup Wizard - 2FA', 'administrator', 'mo2f-setup-wizard-method', array( $this, 'mo_wpns' ), 11 );
			if ( isset( $_GET['action'] ) && 'reset_edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {  //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here.
				$mo2fa_hook_page = add_users_page( 'Reset 2nd Factor', null, 'manage_options', 'reset', array( $this, 'mo2f_reset_2fa_for_users_by_admin' ), 66 );
			}
		}
		/**
		 * Adding some options and calling functions after activation.
		 *
		 * @return void
		 */
		public function mo_wpns() {
			global $wpns_db_queries, $mo2fdb_queries;
			$wpns_db_queries->mo_plugin_activate();
			$mo2fdb_queries->mo_plugin_activate();
			add_option( 'SQLInjection', 1 );
			add_option( 'WAFEnabled', 0 );
			add_option( 'XSSAttack', 1 );
			add_option( 'RFIAttack', 0 );
			add_option( 'LFIAttack', 0 );
			add_option( 'RCEAttack', 0 );
			add_option( 'actionRateL', 0 );
			add_option( 'Rate_limiting', 0 );
			add_option( 'Rate_request', 240 );
			add_option( 'limitAttack', 10 );
			add_site_option( 'EmailTransactionCurrent', 30 );
			add_site_option( 'mo2f_added_ips_realtime', '' );
			include 'controllers/main-controller.php';
		}
		/**
		 * Settings options and calling required functions after register activation hook.
		 *
		 * @return void
		 */
		public function mo_wpns_activate() {
			global $wpns_db_queries, $mo2fdb_queries, $wp_roles;
			$userid = wp_get_current_user()->ID;
			$wpns_db_queries->mo_plugin_activate();
			$mo2fdb_queries->mo_plugin_activate();
			add_option( 'mo2f_is_NC', 1 );
			add_option( 'mo2f_is_NNC', 1 );
			add_option( 'mo2fa_administrator', 1 );
			add_action( 'mo2f_auth_show_success_message', array( $this, 'mo2f_auth_show_success_message' ), 10, 1 );
			add_action( 'mo2f_auth_show_error_message', array( $this, 'mo2f_auth_show_error_message' ), 10, 1 );
			add_option( 'mo2f_onprem_admin', $userid );
			add_option( 'mo2f_nonce_enable_configured_methods', true );
			add_option( 'mo_wpns_last_scan_time', time() );
			update_site_option( 'mo2f_mail_notify_new_release', 'on' );
			add_site_option( 'mo2f_mail_notify', 'on' );
			if ( get_site_option( 'mo2f_activated_time' ) === null ) {
				add_site_option( 'mo2f_activated_time', time() );
			}
			$no_of2fa_users = $mo2fdb_queries->get_no_of_2fa_users();
			if ( ! $no_of2fa_users ) {
				update_site_option( 'mo2f_plugin_redirect', true );
			}
			if ( is_multisite() ) {
				add_site_option( 'mo2fa_superadmin', 1 );
			}
			if ( isset( $wp_roles ) ) {
				foreach ( $wp_roles->role_names as $role => $name ) {
					update_option( 'mo2fa_' . $role, 1 );
				}
			}
			MO2f_Utility::mo2f_debug_file( 'Plugin activated' );
		}

		/**
		 * Settings options and calling required functions after register dectivation hook.
		 *
		 * @return void
		 */
		public function mo_wpns_deactivate() {
			update_option( 'mo2f_activate_plugin', 1 );
			if ( ! MO2F_IS_ONPREM ) {
				delete_option( 'mo2f_customerKey' );
				delete_option( 'mo2f_api_key' );
				delete_option( 'mo2f_customer_token' );
			}
			delete_option( 'mo2f_wizard_selected_method' );
			delete_option( 'mo2f_wizard_skipped' );
			$two_fa_settings = new Miniorange_Authentication();
			$two_fa_settings->mo2f_auth_deactivate();
		}
		/**
		 * Including css files on 2fa dashboard.
		 *
		 * @param int $hook - Hook suffix for the current admin page.
		 * @return void
		 */
		public function mo_wpns_settings_style( $hook ) {
			if ( strpos( $hook, 'page_mo_2fa' ) ) {
				wp_enqueue_style( 'mo_2fa_admin_settings_jquery_style', plugins_url( 'includes/css/jquery.ui.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_2fa_admin_settings_phone_style', plugins_url( 'includes/css/phone.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_wpns_admin_settings_style', plugins_url( 'includes/css/style_settings.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_wpns_admin_settings_phone_style', plugins_url( 'includes/css/phone.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_wpns_admin_settings_datatable_style', plugins_url( 'includes/css/jquery.dataTables.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_wpns_button_settings_style', plugins_url( 'includes/css/button_styles.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_wpns_popup_settings_style', plugins_url( 'includes/css/popup.min.css', __FILE__ ), array(), MO2F_VERSION );
			}
		}
		/**
		 * Including javascript files on 2fa dashboard.
		 *
		 * @param int $hook - Hook suffix for the current admin page.
		 * @return void
		 */
		public function mo_wpns_settings_script( $hook ) {
			wp_enqueue_script( 'mo_wpns_admin_settings_script', plugins_url( 'includes/js/settings_page.min.js', __FILE__ ), array( 'jquery' ), MO2F_VERSION, false );
			wp_localize_script(
				'mo_wpns_admin_settings_script',
				'settings_page_object',
				array(
					'nonce' => wp_create_nonce( 'mo2f_settings_nonce' ),
				)
			);
			if ( strpos( $hook, 'page_mo_2fa' ) ) {
				wp_enqueue_script( 'mo_wpns_hide_warnings_script', plugins_url( 'includes/js/hide.min.js', __FILE__ ), array( 'jquery' ), MO2F_VERSION, false );
				wp_enqueue_script( 'mo_wpns_admin_settings_phone_script', plugins_url( 'includes/js/phone.min.js', __FILE__ ), array(), MO2F_VERSION, false );
				wp_enqueue_script( 'mo_wpns_admin_datatable_script', plugins_url( 'includes/js/jquery.dataTables.min.js', __FILE__ ), array( 'jquery' ), MO2F_VERSION, false );
				wp_enqueue_script( 'mo_wpns_min_qrcode_script', plugins_url( '/includes/jquery-qrcode/jquery-qrcode.min.js', __FILE__ ), array(), MO2F_VERSION, false );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-autocomplete' );
				wp_enqueue_script( 'mo_2fa_select2_script', plugins_url( '/includes/js/select2.min.js', __FILE__ ), array(), MO2F_VERSION, false );
			}
		}
		/**
		 * Includes all the required handler and controller files.
		 *
		 * @return void
		 */
		public function includes() {
			require 'helper/class-mowpnshandler.php';
			require 'database/class-mowpnsdb.php';
			require 'database/class-mo2fdb.php';
			require 'helper/class-mowpnsutility.php';
			require 'handler/class-ajaxhandler.php';
			require 'api/class-two-factor-setup-onprem-cloud.php';
			if ( ! MO2F_IS_ONPREM ) {
				require 'cloud/class-customer-cloud-setup.php';
				require 'cloud/class-mo2f-cloud-challenge.php';
				require 'cloud/class-mo2f-cloud-utility.php';
				require 'cloud/class-mo2f-cloud-validate.php';
				require 'cloud/class-two-factor-setup.php';
			}
			if ( MO2F_IS_ONPREM ) {
				require 'onprem/class-mo2f-onprem-setup.php';
			}
			require 'views/class-mo2f-setup-wizard.php';
			require 'handler/twofa/class-mo2f-cloud-onprem-interface.php';
			require 'handler/class-mo2fa-security-features.php';
			require 'handler/class-feedbackhandler.php';
			require 'controllers/twofa/setup-twofa-for-me.php';
			require 'handler/twofa/class-miniorange-authentication.php';
			require 'handler/class-loginhandler.php';
			require 'handler/twofa/class-mo2f-utility.php';
			require 'handler/class-registrationhandler.php';
			require 'handler/class-mo2f-logger.php';
			require 'helper/class-miniorange-security-notification.php';
			require 'helper/class-mo2f-common-otp-setup.php';
			require 'helper/class-mocurl.php';
			require 'helper/class-mowpnsconstants.php';
			require 'helper/class-mowpnsmessages.php';
			require 'views/common-elements.php';
			require 'handler/twofa/class-twofacustomregformshortcode.php';
			require 'controllers/class-wpns-ajax.php';
			require 'controllers/duo_authenticator/class-mo-2f-duo-authenticator.php';
			require 'helper/class-mo2f-setupwizard.php';
			require 'controllers/twofa/class-mo-2f-ajax.php';
			require 'controllers/class-mo2f-ajax-dashboard.php';
		}
		/**
		 * Handle reset users functionality from user's profile section.
		 *
		 * @param string[] $actions - An array of action links to be displayed.
		 * @param object   $user_object - object for the currently listed user.
		 * @return string[]
		 */
		public function miniorange_reset_users( $actions, $user_object ) {
			global $mo2fdb_queries;
			$mo2f_configured_2_f_a_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user_object->ID );
			if ( current_user_can( 'edit_users', $user_object->ID ) && $mo2f_configured_2_f_a_method ) {
				if ( get_current_user_id() !== $user_object->ID ) {
					$actions['miniorange_reset_users'] = "<a class='miniorange_reset_users' href='" . wp_nonce_url( "users.php?page=reset&action=reset_edit&amp;user_id=$user_object->ID", 'reset_edit', 'mo2f_reset-2fa' ) . "'>" . __( 'Reset 2 Factor', 'miniorange-2-factor-authentication' ) . '</a>';
				}
			} elseif ( miniorange_check_if_2fa_enabled_for_roles( $user_object->roles ) ) {
				$edit_link                         = esc_url(
					add_query_arg(
						'wp_http_referer',
						rawurlencode( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ),
						get_edit_user_link( $user_object->ID )
					)
				);
				$actions['miniorange_reset_users'] = '<a href="' . $edit_link . '">' . __( 'Set 2 Factor', 'cgc_ub' ) . '</a>';
			}
			return $actions;
		}
		/**
		 * Add UTM links for plugin actions on plugins.php.
		 *
		 * @param string[] $links - UTM links.
		 * @return string[]
		 */
		public function mo2f_add_plugin_action_link( $links ) {
			$custom['pro'] = sprintf(
				'<a href="%1$s" aria-label="%2$s" target="_blank" rel="noopener noreferrer" 
				style="color: #EF8354; font-weight: 700;" 
				onmouseover="this.style.color=\'#F5AD8F\';" 
				onmouseout="this.style.color=\'#EF8354\';"
				>%3$s</a>',
				esc_url(
					add_query_arg(
						array(
							'utm_content'  => 'pricing',
							'utm_campaign' => 'mo2f',
							'utm_medium'   => 'wp',
							'utm_source'   => 'wpf_plugin',
						),
						MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/2-factor-authentication-for-wordpress-wp-2fa#pricing'
					)
				),
				esc_attr( 'Upgrade to Premium' ),
				esc_html( 'Upgrade to Premium' )
			);

			$custom['docs'] = sprintf(
				'<a href="%1$s" target="_blank" aria-label="%2$s" rel="noopener noreferrer">%3$s</a>',
				esc_url(
					add_query_arg(
						array(
							'utm_content'  => 'docs',
							'utm_campaign' => 'mo2f',
							'utm_medium'   => 'wp',
							'utm_source'   => 'wpf_plugin',
						),
						MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/wordpress-two-factor-authentication-setup-guides'
					)
				),
				esc_attr( 'miniorange.com documentation page' ),
				esc_html( 'Docs' )
			);

			return array_merge( $custom, (array) $links );
		}

		/**
		 * Add column in user profile section of WordPress.
		 *
		 * @param string[] $columns - The column header labels keyed by column ID.
		 * @return string[]
		 */
		public function mo2f_mapped_email_column( $columns ) {
			$columns['current_method'] = '2FA Method';
			return $columns;
		}
		/**
		 * Users page to reset 2FA for specific user
		 *
		 * @return void
		 */
		public function mo2f_reset_2fa_for_users_by_admin() {
			$nonce = wp_create_nonce( 'ResetTwoFnonce' );
			if ( ! isset( $_GET['mo2f_reset-2fa'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['mo2f_reset-2fa'] ) ), 'reset_edit' ) ) {
				wp_send_json( 'ERROR' );
			}
			if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'reset_edit' ) {
				$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : '';
				$user_info = get_userdata( $user_id );
				if ( is_numeric( $user_id ) && $user_info ) {
					?>
				<div class="wrap">
					<form method="post" name="reset2fa" id="reset2fa" action="<?php echo esc_url( 'users.php' ); ?>">
						<h1>Reset 2nd Factor</h1>

						<p>You have specified this user for reset:</p>

						<ul>
							<li>ID #<?php echo esc_html( $user_info->ID ); ?>: <?php echo esc_html( $user_info->user_login ); ?></li>
						</ul>
						<input type="hidden" name="userid" value="<?php echo esc_attr( $user_id ); ?>">
						<input type="hidden" name="miniorange_reset_2fa_option" value="mo_reset_2fa">
						<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
						<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Confirm Reset"></p>
					</form>
				</div>

					<?php
				} else {
					?>
				<h2> Invalid User Id </h2>
					<?php
				}
			}
		}
		/**
		 * Function to save settings on 2FA reset.
		 *
		 * @return void
		 */
		public function miniorange_reset_save_settings() {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'ResetTwoFnonce' ) ) {
				return;
			}
			if ( isset( $_POST['miniorange_reset_2fa_option'] ) && sanitize_text_field( wp_unslash( $_POST['miniorange_reset_2fa_option'] ) ) === 'mo_reset_2fa' ) {
				$user_id = isset( $_POST['userid'] ) && ! empty( $_POST['userid'] ) ? sanitize_text_field( wp_unslash( $_POST['userid'] ) ) : '';
				if ( ! empty( $user_id ) ) {
					$user_object  = wp_get_current_user();
					$capabilities = $user_object->allcaps;

					if ( current_user_can( 'edit_users' ) || ( isset( $capabilities['edit_users'] ) && $capabilities['edit_users'] ) ) {

						global $mo2fdb_queries;
						delete_user_meta( $user_id, 'mo2f_kba_challenge' );
						delete_user_meta( $user_id, 'mo2f_2FA_method_to_configure' );
						delete_user_meta( $user_id, MoWpnsConstants::SECURITY_QUESTIONS );
						delete_user_meta( $user_id, 'mo2f_chat_id' );
						delete_user_meta( $user_id, 'mo2f_whatsapp_num' );
						delete_user_meta( $user_id, 'mo2f_whatsapp_id' );
						delete_user_meta( $user_id, 'mo2f_configure_2FA' );
						$mo2fdb_queries->delete_user_details( $user_id );
						delete_user_meta( $user_id, 'mo2f_2FA_method_to_test' );
						delete_option( 'mo2f_user_login_status_' . $user_id );
						delete_option( 'mo2f_grace_period_status_' . $user_id );
						delete_user_meta( $user_id, 'mo2f_grace_period_start_time' );
					}
				}
			}
		}
		/**
		 * Get mapped user profile column
		 *
		 * @param string $value Row value to be shown.
		 * @param string $column_name Column name.
		 * @param  int    $user_id User ID of the details to be shown.
		 * @return string
		 */
		public function mo2f_mapped_email_column_content( $value, $column_name, $user_id ) {
			global $mo2fdb_queries;
			$current_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user_id );
			if ( ! $current_method ) {
				$check_if_skipped = $mo2fdb_queries->get_user_detail( 'mo2f_2factor_enable_2fa_byusers', $user_id );
				if ( '0' === $check_if_skipped ) {
					$current_method = 'Two-Factor skipped by user';
				} else {
					$current_method = 'Not Registered for 2FA';
				}
			}
			if ( 'current_method' === $column_name ) {
				return $current_method;
			}
			return $value;
		}

		/**
		 * Check whether email should be sent after plugin update.
		 *
		 * @return void
		 */
		public function mo2f_mail_send() {
			if ( get_site_option( 'mo2f_mail_notify_new_release' ) === 'on' ) {
				if ( ! get_site_option( 'mo2f_feature_vers' ) ) {
					$this->mo2f_email_send();
				} else {
					$current_versions = get_site_option( 'mo2f_feature_vers' );

					if ( $current_versions < MoWpnsConstants::DB_FEATURE_MAIL ) {
						$this->mo2f_email_send();
					}
				}
			}
		}
		/**
		 * Function contains Email template to send to users after updating the plugin.
		 *
		 * @return void
		 */
		public function mo2f_email_send() {
			$subject  = 'miniOrange 2FA V' . MO2F_VERSION . ' | What\'s New?';
			$messages = mail_tem();
			$headers  = array( 'Content-Type: text/html; charset=UTF-8' );
			$email    = get_option( 'admin_email' );

			update_site_option( 'mo2f_feature_vers', MoWpnsConstants::DB_FEATURE_MAIL );
			if ( empty( $email ) ) {
				$user  = wp_get_current_user();
				$email = $user->user_email;
			}
			if ( is_email( $email ) ) {
				wp_mail( $email, $subject, $messages, $headers );
			}
		}

	}
}

new Miniorange_TwoFactor();

?>
