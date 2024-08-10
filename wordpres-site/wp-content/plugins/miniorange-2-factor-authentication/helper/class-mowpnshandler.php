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

namespace TwoFA\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MoWpnsHandler' ) ) {

	/**
	 * This library is miniOrange Authentication Service.
	 * Contains Request Calls to Customer service.
	 **/
	class MoWpnsHandler {

		/**
		 * It is for check the ip is block
		 *
		 * @param string $ip_address .
		 * @return boolean .
		 */
		public function mo_wpns_is_ip_blocked( $ip_address ) {
			global $wpns_db_queries;
			if ( empty( $ip_address ) ) {
				return false;
			}

			$user_count = $wpns_db_queries->get_ip_blocked_count( $ip_address );

			if ( $user_count ) {
				$user_count = intval( $user_count );
			}
			if ( $user_count > 0 ) {
				return true;
			}

			return false;
		}
		/**
		 * Get the all blocked ip by waf
		 *
		 * @return string
		 */
		public function get_blocked_ip_waf() {
			global $wpns_db_queries;
			$ip_count = $wpns_db_queries->get_total_blocked_ips_waf();
			if ( $ip_count ) {
				$ip_count = intval( $ip_count );
			}

			return $ip_count;
		}
		/**
		 * Get manual ip blocked by admin from dashboard
		 *
		 * @return string
		 */
		public function get_manual_blocked_ip_count() {
			global $wpns_db_queries;
			$ip_count = $wpns_db_queries->get_total_manual_blocked_ips();
			if ( $ip_count ) {
				$ip_count = intval( $ip_count );
			}
			return $ip_count;
		}
		/**
		 * Get the blocked ip
		 *
		 * @return object
		 */
		public function get_blocked_ips() {
			global $wpns_db_queries;
			return $wpns_db_queries->get_blocked_ip_list();
		}
		/**
		 * Blocking the Ip addresses
		 *
		 * @param string $ip_address .
		 * @param string $reason .
		 * @param string $permenently .
		 * @return void
		 */
		public function mo_wpns_block_ip( $ip_address, $reason, $permenently ) {
			global $wpns_db_queries;
			if ( empty( $ip_address ) ) {
				return;
			}
			if ( $this->mo_wpns_is_ip_blocked( $ip_address ) ) {
				return;
			}
			$blocked_for_time = null;
			if ( ! $permenently && get_option( 'mo2f_time_of_blocking_type' ) ) {
				$blocking_type        = get_option( 'mo2f_time_of_blocking_type' );
				$time_of_blocking_val = 3;
				if ( get_option( 'mo2f_time_of_blocking_val' ) ) {
					$time_of_blocking_val = get_option( 'mo2f_time_of_blocking_val' );
				}
				if ( 'months' === $blocking_type ) {
					$blocked_for_time = current_time( 'timestamp' ) + $time_of_blocking_val * 30 * 24 * 60 * 60; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- This will return the current time of user action
				} elseif ( 'days' === $blocking_type ) {
					$blocked_for_time = current_time( 'timestamp' ) + $time_of_blocking_val * 24 * 60 * 60; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- This will return the current time of user action
				} elseif ( 'hours' === $blocking_type ) {
					$blocked_for_time = current_time( 'timestamp' ) + $time_of_blocking_val * 60 * 60; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- This will return the current time of user action
				}
			}

			$wpns_db_queries->insert_blocked_ip( $ip_address, $reason, $blocked_for_time );
			// send notification.
			global $mo_wpns_utility;
			if ( MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_enable_ip_blocked_email_to_admin', 'get_option' ) ) {
				$mo_wpns_utility->sendIpBlockedNotification( $ip_address, MoWpnsConstants::LOGIN_ATTEMPTS_EXCEEDED );
			}

		}
		/**
		 * This will unblock the ip from the blocked table .
		 *
		 * @param string $entryid .
		 * @return void
		 */
		public function unblock_ip_entry( $entryid ) {
			global $wpns_db_queries;
			$wpns_db_queries->delete_blocked_ip( $entryid );
		}

		/**
		 * The function is to check the whitelisted ip
		 *
		 * @param string $ip_address .
		 * @return boolean
		 */
		public function is_whitelisted( $ip_address ) {
			global $wpns_db_queries;
			$count = $wpns_db_queries->get_whitelisted_ip_count( $ip_address );

			if ( empty( $ip_address ) ) {
				return false;
			}
			if ( $count ) {
				$count = intval( $count );
			}

			if ( $count > 0 ) {
				return true;
			}
			return false;
		}
		/**
		 * White listing the ips
		 *
		 * @param string $ip_address .
		 * @return void
		 */
		public function whitelist_ip( $ip_address ) {
			global $wpns_db_queries;

			if ( empty( $ip_address ) ) {
				return;
			}
			if ( $this->is_whitelisted( $ip_address ) ) {
				return;
			}

			$wpns_db_queries->insert_whitelisted_ip( $ip_address );
		}
		/**
		 * Remove the ip from whitelist entry
		 *
		 * @param string $entryid .
		 * @return void
		 */
		public function remove_whitelist_entry( $entryid ) {
			global $wpns_db_queries;
			$wpns_db_queries->delete_whitelisted_ip( $entryid );
		}
		/**
		 * It will get the all whitelist ip
		 *
		 * @return object
		 */
		public function get_whitelisted_ips() {
			global $wpns_db_queries;
			return $wpns_db_queries->get_whitelisted_ips_list();
		}
		/**
		 * It will sent the email
		 *
		 * @param string $username .
		 * @param string $ip_address .
		 * @return boolean
		 */
		public function is_email_sent_to_user( $username, $ip_address ) {
			global $wpns_db_queries;
			if ( empty( $ip_address ) ) {
				return false;
			}
			$sent_count = $wpns_db_queries->get_email_audit_count( $ip_address, $username );
			if ( $sent_count ) {
				$sent_count = intval( $sent_count );
			}
			if ( $sent_count > 0 ) {
				return true;
			}
			return false;
		}
		/**
		 * It will sent the notification to the user
		 *
		 * @param string $username .
		 * @param string $ip_address .
		 * @param string $reason .
		 * @return void
		 */
		public function audit_email_notification_sent_to_user( $username, $ip_address, $reason ) {
			if ( empty( $ip_address ) || empty( $username ) ) {
				return;
			}
			global $wpns_db_queries;
			$wpns_db_queries->insert_email_audit( $ip_address, $username, $reason );
		}
		/**
		 * It will add transaction detail
		 *
		 * @param string $ip_address .
		 * @param string $username .
		 * @param string $type .
		 * @param string $status .
		 * @param string $url .
		 * @return void
		 */
		public function add_transactions( $ip_address, $username, $type, $status, $url = null ) {
			global $wpns_db_queries;
			$wpns_db_queries->insert_transaction_audit( $ip_address, $username, $type, $status, $url );
		}
		/**
		 * It will help to get the login transaction report
		 *
		 * @return string
		 */
		public function get_login_transaction_report() {
			global $wpns_db_queries;
			return $wpns_db_queries->get_login_transaction_report();
		}
		/**
		 * It will get the report in tabular form
		 *
		 * @return string
		 */
		public function get_error_transaction_report() {
			global $wpns_db_queries;
			return $wpns_db_queries->get_error_transaction_report();
		}
		/**
		 * Move failed transaction on table
		 *
		 * @param string $ip_address .
		 * @return void
		 */
		public function move_failed_transactions_to_past_failed( $ip_address ) {
			global $wpns_db_queries;
			$wpns_db_queries->update_transaction_table(
				array(
					'status'     => MoWpnsConstants::FAILED,
					'ip_address' => $ip_address,
				),
				array( 'status' => MoWpnsConstants::PAST_FAILED )
			);
		}
		/**
		 * It will check the ip is block or not
		 *
		 * @param string $user_ip .
		 * @return boolean
		 */
		public function is_ip_blocked_in_anyway( $user_ip ) {
			$is_blocked = false;
			if ( $this->mo_wpns_is_ip_blocked( $user_ip ) ) {
				$is_blocked = true;
			} elseif ( $this->is_ip_range_blocked( $user_ip ) ) {
				$is_blocked = true;
			}

			return $is_blocked;
		}
		/**
		 * It will help to block the range of ip
		 *
		 * @param string $user_ip .
		 * @return boolean
		 */
		public function is_ip_range_blocked( $user_ip ) {
			if ( empty( $user_ip ) ) {
				return false;
			}
			$range_count = 0;
			if ( is_numeric( get_option( 'mo_wpns_iprange_count' ) ) ) {
				$range_count = intval( get_option( 'mo_wpns_iprange_count' ) );
			}
			for ( $i = 1; $i <= $range_count; $i++ ) {
				$blockedrange = get_option( 'mo_wpns_iprange_range_' . $i );
				$rangearray   = explode( '-', $blockedrange );
				if ( 2 === count( $rangearray ) ) {
					$lowip  = ip2long( trim( $rangearray[0] ) );
					$highip = ip2long( trim( $rangearray[1] ) );
					if ( ip2long( $user_ip ) >= $lowip && ip2long( $user_ip ) <= $highip ) {
						$mo_wpns_config = new MoWpnsHandler();
						$mo_wpns_config->mo_wpns_block_ip( $user_ip, MoWpnsConstants::IP_RANGE_BLOCKING, true );
						return true;
					}
				}
			}
			return false;
		}

		/**
		 * Lockedoutlink
		 *
		 * @return string
		 */
		public function locked_out_link() {
			if ( MO2F_IS_ONPREM ) {
				return MoWpnsConstants::ONPREMISELOCKEDOUT;
			} else {
				return MoWpnsConstants::CLOUDLOCKOUT;
			}
		}


	}
}
