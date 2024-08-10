<?php
/**
 * File contains the functions related to the network security.
 *
 * @package miniOrange-2-factor-authentication/controllers
 */

// Needed in both.
use TwoFA\Helper\MoWpnsMessages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Mo2f_Ajax_Dashboard' ) ) {
	/**
	 * Class Mo2f_Ajax_Dashboard
	 */
	class Mo2f_Ajax_Dashboard {

		/**
		 * Calls the network security functions and updates the option in options table.
		 *
		 * @param integer $posted 1 if respective security method enable.
		 * @return void
		 */
		public function mo2f_handle_all_enable( $posted ) {
			$this->mo2f_handle_block_enable( $posted );
			$show_message = new MoWpnsMessages();
			if ( $posted ) {
				update_site_option( 'mo2f_tab_count', 5 );
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ALL_ENABLED ), 'SUCCESS' );
			} else {
				update_site_option( 'mo2f_tab_count', 0 );
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ALL_DISABLED ), 'ERROR' );
			}
		}
		/**
		 * Handles the flow when ip block switch is changed.
		 *
		 * @param integer $posted 1 if switch is enabled.
		 * @return void
		 */
		public function mo2f_handle_block_enable( $posted ) {
			if ( ! check_ajax_referer( 'mo_2fa_security_features_nonce', 'mo_security_features_nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( $error );
			} else {
				$show_message = new MoWpnsMessages();
				if ( $posted ) {
					update_site_option( 'mo_2f_switch_adv_block', 1 );
					update_site_option( 'mo2f_tab_count', get_site_option( 'mo2f_tab_count' ) + 1 );
					if ( isset( $_POST['option'] ) ) {
						if ( 'tab_block_switch' === sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) {
							$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ADV_BLOCK_ENABLE ), 'SUCCESS' );
						}
					}
				} else {
					update_site_option( 'mo_2f_switch_adv_block', 0 );
					update_site_option( 'mo2f_tab_count', get_site_option( 'mo2f_tab_count' ) - 1 );
					update_site_option( 'mo_wpns_iprange_count', 0 );
					update_site_option( 'mo_wpns_enable_htaccess_blocking', 0 );
					update_site_option( 'mo_wpns_enable_user_agent_blocking', 0 );
					update_site_option( 'mo_wpns_referrers', false );
					update_site_option( 'mo_wpns_countrycodes', false );
					if ( 'tab_block_switch' === sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ADV_BLOCK_DISABLE ), 'ERROR' );
					}
				}
			}
		}


	}
}
new Mo2f_ajax_dashboard();

