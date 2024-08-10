<?php
/** The miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
 * Copyright (C) 2015  miniOrange
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @package        miniorange-2-factor-authentication/handler
 */

use TwoFA\Helper\MoWpnsMessages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2fa_Security_Features' ) ) {
	/**
	 * This library is miniOrange Authentication Service.
	 * Contains Request Calls to Customer service.
	 **/
	class Mo2fa_Security_Features {

		/**
		 * It will return the 2fa features
		 *
		 * @return void
		 */
		public function wpns_2fa_features_only() {
			update_option( 'mo_wpns_2fa_with_network_security', 0 );
			update_option( 'mo_wpns_2fa_with_network_security_popup_visible', 0 );
			?><script>window.location.href="admin.php?page=mo_2fa_two_fa";</script>
			<?php

		}
		/**
		 * It will find the network security
		 *
		 * @param string $postvalue carry postdata .
		 * @return void
		 */
		public function wpns_2fa_with_network_security( $postvalue ) {
			$show_message              = new MoWpnsMessages();
			$nonce = isset( $_POST['mo_security_features_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_security_features_nonce'] ) ) : '';

			if ( wp_verify_nonce( $nonce, 'mo_2fa_security_features_nonce' ) ) {
				$enable_newtwork_security_features = isset( $postvalue['mo_wpns_2fa_with_network_security'] ) ? true : false;

				update_option( 'mo_wpns_2fa_with_network_security', $enable_newtwork_security_features );

				if ( $enable_newtwork_security_features ) {
					$mo2f_enable_all_enable = new Mo2f_ajax_dashboard();
					$mo2f_enable_all_enable->mo2f_handle_all_enable( 1 );
				}

				update_option( 'mo_wpns_2fa_with_network_security_popup_visible', 0 );
				?>
			<script>window.location.href="admin.php?page=mo_2fa_two_fa";</script>
				<?php
			} else {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
			}
		}
	}new Mo2fa_Security_Features();
}
?>
