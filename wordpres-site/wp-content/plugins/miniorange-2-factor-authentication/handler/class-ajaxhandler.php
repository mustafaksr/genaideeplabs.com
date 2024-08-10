<?php
/**
 * This file is part of GAuth plugin.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AjaxHandler' ) ) {
	/**
	 * Class Ajaxhandler
	 */
	class AjaxHandler {

		/**
		 * Class Ajaxhandler constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'mo_wpns_2fa_actions' ) );
		}

		/**
		 * Checks for the requested option value in the switch case.
		 *
		 * @return object
		 */
		public function mo_wpns_2fa_actions() {
			$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mo2f_settings_nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username_Hello', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} elseif ( current_user_can( 'manage_options' ) && isset( $_REQUEST['option'] ) ) {
				$option = sanitize_text_field( wp_unslash( $_REQUEST['option'] ) );
				$ip     = isset( $_GET['ip'] ) ? filter_var( wp_unslash( $_GET['ip'] ), FILTER_VALIDATE_IP ) : null;

				switch ( $option ) {
					case 'iplookup':
						$this->lookup_i_p( $ip );
						break;

					case 'whitelistself':
						$this->whitelist_self();
						break;

					case 'dismissplugin':
						$this->wpns_plugin_notice();
						break;

					case 'plugin_warning_never_show_again':
						$this->wpns_plugin_warning_never_show_again();
						break;

					case 'mo2f_banner_never_show_again':
						$this->wpns_mo2f_banner_never_show_again();
						break;

					case 'dismissSms':
						$this->wpns_sms_notice();
						break;

					case 'dismissEmail':
						$this->wpns_email_notice();
						break;

					case 'dismissSms_always':
						$this->wpns_sms_notice_always();
						break;

					case 'dismissEmail_always':
						$this->wpns_email_notice_always();
						break;

					case 'dismisscodeswarning':
						$this->mo2f_backup_codes_dismiss();
						break;
				}
			}
		}

		/**
		 * Updates the lookup ip template for a given ip.
		 *
		 * @param string $ip The ip adress for which the lookup ip template need to be updated.
		 * @return void
		 */
		private function lookup_i_p( $ip ) {
			$result = wp_remote_get( 'http://www.geoplugin.net/json.gp?ip=' . $ip );
			if ( ! is_wp_error( $result ) ) {
				$result = wp_remote_retrieve_body( $result );
			}

			$hostname = gethostbyaddr( $result['geoplugin_request'] );
			try {
				$timeoffset = timezone_offset_get( new DateTimeZone( $result['geoplugin_timezone'] ), new DateTime( 'now' ) );
				$timeoffset = $timeoffset / 3600;
			} catch ( Exception $e ) {
				$result['geoplugin_timezone'] = '';
				$timeoffset                   = '';
			}

			$ip_look_up_template = MoWpnsConstants::IP_LOOKUP_TEMPLATE;
			if ( $result['geoplugin_request'] === $ip ) {
				$ip_look_up_template = str_replace( '{{status}}', $result['geoplugin_status'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{ip}}', $result['geoplugin_request'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{region}}', $result['geoplugin_region'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{country}}', $result['geoplugin_countryName'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{city}}', $result['geoplugin_city'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{continent}}', $result['geoplugin_continentName'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{latitude}}', $result['geoplugin_latitude'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{longitude}}', $result['geoplugin_longitude'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{timezone}}', $result['geoplugin_timezone'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{curreny_code}}', $result['geoplugin_currencyCode'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{curreny_symbol}}', $result['geoplugin_currencySymbol'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{per_dollar_value}}', $result['geoplugin_currencyConverter'], $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{hostname}}', $hostname, $ip_look_up_template );
				$ip_look_up_template = str_replace( '{{offset}}', $timeoffset, $ip_look_up_template );

				$result['ipDetails'] = $ip_look_up_template;
			} else {
				$result['ipDetails']['status'] = 'ERROR';
			}

			wp_send_json( $result );
		}

		/**
		 * Whitelists the self ip adress.
		 *
		 * @return void
		 */
		private function whitelist_self() {
			global $mo_wpns_utility;
			$mo_plugins_utility = new MoWpnsHandler();
			$mo_plugins_utility->whitelist_ip( $mo_wpns_utility->get_client_ip() );
			wp_send_json_success();
		}

		/**
		 * Updates the malware notification and notice dismiss time options in the options table.
		 */
		private function wpns_plugin_notice() {
			update_site_option( 'malware_notification_option', 1 );
			update_site_option( 'notice_dismiss_time', time() );
			wp_send_json_success();
		}


		/**
		 * Updates the plugin warning never show again option in the options table.
		 */
		public function wpns_plugin_warning_never_show_again() {
			update_site_option( 'plugin_warning_never_show_again', 1 );
			wp_send_json_success();
		}

		/**
		 * Updates the banner never show again option in the options table.
		 *
		 * @return void
		 */
		public function wpns_mo2f_banner_never_show_again() {
			update_site_option( 'mo2f_banner_never_show_again', 1 );
			wp_send_json_success();
		}


		/**
		 * Updates the wpns sms dismiss option in the options table.
		 *
		 * @return void
		 */
		private function wpns_sms_notice() {
			update_site_option( 'mo2f_wpns_sms_dismiss', time() );
			wp_send_json_success();
		}

		/**
		 * Updates the wpns email dismiss option in the options table.
		 *
		 * @return void
		 */
		private function wpns_email_notice() {
			update_site_option( 'mo2f_wpns_email_dismiss', time() );
			wp_send_json_success();
		}

		/**
		 * Updates the show low sms notice option in the database.
		 *
		 * @return void
		 */
		private function wpns_sms_notice_always() {
			update_site_option( 'mo2f_wpns_donot_show_low_sms_notice', 1 );
			wp_send_json_success();
		}

		/**
		 * Updates the show low email notice option in the database.
		 *
		 * @return void
		 */
		private function wpns_email_notice_always() {
			update_site_option( 'mo2f_wpns_donot_show_low_email_notice', 1 );
			wp_send_json_success();
		}

		/**
		 * Updates the current user meta for the given meta key in the usermeta table.
		 *
		 * @return void
		 */
		private function mo2f_backup_codes_dismiss() {
			$user_id = get_current_user_id();
			update_user_meta( $user_id, 'donot_show_backup_code_notice', 1 );
			wp_send_json_success();
		}
	}new AjaxHandler();
}
