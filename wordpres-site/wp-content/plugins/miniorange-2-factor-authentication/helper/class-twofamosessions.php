<?php
/**
 * This file has function to set/ fetch transient variables.
 *
 * @package miniorange-2-factor-authentication/helper
 */

namespace TwoFA\Helper;

use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'TwoFAMoSessions' ) ) {
	/**
	 * This class has function to set/ fetch transient functions
	 */
	class TwoFAMoSessions {
		/**
		 * Set cookie and transient variable.
		 *
		 * @param string $key Key of the session and transient to be set.
		 * @param string $val Value of the session and transient to be set.
		 * @return void
		 */
		public static function add_session_var( $key, $val ) {
			switch ( MO2F_SESSION_TYPE ) {
				case 'TRANSIENT':
					if ( ! isset( $_COOKIE['transient_key'] ) ) {
						if ( ! wp_cache_get( 'transient_key' ) ) {
							$transient_key = MoWpnsUtility::rand();
							if ( ob_get_contents() ) {
								ob_clean();
							}
							setcookie( 'transient_key', $transient_key, time() + 12 * HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
							wp_cache_add( 'transient_key', $transient_key );
						} else {
							$transient_key = wp_cache_get( 'transient_key' );
						}
					} else {
						$transient_key = sanitize_text_field( wp_unslash( $_COOKIE['transient_key'] ) );
					}
					set_site_transient( $transient_key . $key, $val, 12 * HOUR_IN_SECONDS );
					break;
			}
		}
		/**
		 * Get cookie and transient variable
		 *
		 * @param string $key Key of the session and transient to fetch.
		 */
		public static function get_session_var( $key ) {
			switch ( MO2F_SESSION_TYPE ) {
				case 'TRANSIENT':
					$transient_key = isset( $_COOKIE['transient_key'] )
					? sanitize_text_field( wp_unslash( $_COOKIE['transient_key'] ) ) : wp_cache_get( 'transient_key' );
					return get_site_transient( $transient_key . $key );
			}
		}
		/**
		 * Unset cookie and transient variable.
		 *
		 * @param string $key Key of the session and transient to be unset.
		 */
		public static function unset_session( $key ) {
			switch ( MO2F_SESSION_TYPE ) {
				case 'TRANSIENT':
					$transient_key = isset( $_COOKIE['transient_key'] )
					? sanitize_text_field( wp_unslash( $_COOKIE['transient_key'] ) ) : wp_cache_get( 'transient_key' );
					if ( ! MoWpnsUtility::check_empty_or_null( $transient_key ) ) {
						delete_site_transient( $transient_key . $key );
					}
					break;
			}
		}

	}
}
