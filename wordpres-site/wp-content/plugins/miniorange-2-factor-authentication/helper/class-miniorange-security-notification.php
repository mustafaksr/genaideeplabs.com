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
 * @package        miniorange-2-factor-authentication/helper
 */

namespace TwoFA;

use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Miniorange_Security_Notification' ) ) {
	/**
	 * This library is miniOrange Authentication Service.
	 * Contains Request Calls to Customer service.
	 **/
	class Miniorange_Security_Notification {
		/**
		 * It will help to show dashboard
		 *
		 * @return void
		 */
		public function my_custom_dashboard_widgets() {

			wp_add_dashboard_widget( 'custom_help_widget', 'MiniOrange Website Security', array( $this, 'custom_dashboard_help' ) );
		}
		/**
		 * It will help to custom dashboard
		 *
		 * @return void
		 */
		public function custom_dashboard_help() {

			if ( current_user_can( 'administrator' ) ) {
				echo "<html>
                       
              <div style='width:100%;background-color:#555f5f;padding-top:10px;''>
              <div style='font-size:25px;color:white;text-align:center'>
            <strong style='font-weight:300;''>Remaining Transactions <span style='color:orange;'>[OTPs]</strong>
      
                </div>
                <hr>
       
                ";

				$email_transactions = MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
				$email_transactions = $email_transactions ? $email_transactions : 0;
				$sms_transactions   = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) ? get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) : 0;

				echo '<table style="solid #CCCCCC; border-collapse: collapse; padding:0px 0px 0px 10px; margin:2px; width:99%">
          <tr>  
                        <td style="font-size:18px;color:#ffffff;padding: 10px;"><strong style="font-weight:300;">Remaining SMS transactions </strong></td>
                        <td style="text-align:center;font-size:36px;color:#ffffff;font-weight:400" ><strong>' . esc_html( isset( $sms_transactions ) ? $sms_transactions : '' ) . '</strong></td>
                                                              
                    </tr>
                    <tr>
                        <td style="font-size:18px;color:#ffffff;padding: 10px;"><strong style="font-weight:300;">Remaining Email transactions </strong></td>
                        <td style="text-align:center;font-size:36px;color:#ffffff;font-weight:400" ><strong>' . esc_html( isset( $email_transactions ) ? $email_transactions : '' ) . '</strong></td>
                                      
                        
                    </tr>

                </table><br>';

				echo '</div>

          ';
			}

		}

	}
}


