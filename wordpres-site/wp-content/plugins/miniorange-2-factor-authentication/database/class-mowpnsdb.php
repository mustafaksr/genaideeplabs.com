<?php
/**
 * This file contains functions related to login flow.
 *
 * @package miniorange-2-factor-authentication/database.
 */

namespace TwoFA\Database;

use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *   Including  upgrade.php.
 */
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

if ( ! class_exists( 'MoWpnsDB' ) ) {
	/**
	 * Class used to perform DB operation on security functions.
	 */
	class MoWpnsDB {

		/**
		 * Transaction table name.
		 *
		 * @var string
		 */
		private $transaction_table;

		/**
		 * Blocked IPs table name.
		 *
		 * @var string
		 */
		private $blocked_ips_table;

		/**
		 * WHitelist IPs table name.
		 *
		 * @var string
		 */
		private $whitelist_ips_table;

		/**
		 * Email audit table name.
		 *
		 * @var string
		 */
		private $email_audit_table;

		/**
		 * Attack list table name.
		 *
		 * @var string
		 */
		private $attack_list;

		/**
		 * Transaction table name.
		 *
		 * @var string
		 */
		private $filescan;

		/**
		 * Constructor for class MoWpnsDB.
		 */
		public function __construct() {
			global $wpdb;
			$this->transaction_table   = $wpdb->base_prefix . 'mo2f_network_transactions';
			$this->blocked_ips_table   = $wpdb->base_prefix . 'mo2f_network_blocked_ips';
			$this->attack_list         = $wpdb->base_prefix . 'wpns_attack_logs';
			$this->whitelist_ips_table = $wpdb->base_prefix . 'mo2f_network_whitelisted_ips';
			$this->email_audit_table   = $wpdb->base_prefix . 'mo2f_network_email_sent_audit';
		}

		/**
		 * This function should run on activation of plugin.
		 *
		 * @return void
		 */
		public function mo_plugin_activate() {
			if ( ! get_option( 'mo_wpns_dbversion' ) || get_option( 'mo_wpns_dbversion' ) < MoWpnsConstants::DB_VERSION ) {
				update_option( 'mo_wpns_dbversion', MoWpnsConstants::DB_VERSION );
				$this->generate_tables();
			} else {
				$current_db_version = get_option( 'mo_wpns_dbversion' );
				if ( $current_db_version < MoWpnsConstants::DB_VERSION ) {
					update_option( 'mo_wpns_dbversion', MoWpnsConstants::DB_VERSION );
				}
			}
		}

		/**
		 * This function generates tables.
		 *
		 * @return void
		 */
		public function generate_tables() {
			global $wpdb;

			$table_name = $this->transaction_table;

			if ( $wpdb->get_var( $wpdb->prepare( 'show tables like %s', array( $table_name ) ) ) !== $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Ignoring warning related to Schema change
				$sql = 'CREATE TABLE ' . $table_name . ' ( `id` bigint NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL ,  `username` mediumtext NOT NULL , `type` mediumtext NOT NULL , `url` mediumtext NOT NULL , `status` mediumtext NOT NULL , `created_timestamp` int, UNIQUE KEY id (id) );'; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Ignoring warning related to Schema change
				dbDelta( $sql );
			}

			$table_name = $this->blocked_ips_table;

			if ( $wpdb->get_var( $wpdb->prepare( 'show tables like %s', array( $table_name ) ) ) !== $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
				$sql = 'CREATE TABLE ' . $table_name . ' ( `id` int NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL , `reason` mediumtext, `blocked_for_time` int, `created_timestamp` int, UNIQUE KEY id (id) );'; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Ignoring warning related to Schema change
				dbDelta( $sql );
			}

			$table_name = $this->whitelist_ips_table;

			if ( $wpdb->get_var( $wpdb->prepare( 'show tables like %s', array( $table_name ) ) ) !== $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
				$sql = 'CREATE TABLE ' . $table_name . ' ( `id` int NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL , `created_timestamp` int, UNIQUE KEY id (id) );'; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Ignoring warning related to Schema change
				dbDelta( $sql );
			}

			$result = $wpdb->get_var( $wpdb->prepare( ' SHOW COLUMNS FROM %1s LIKE %s ', array( $table_name, 'plugin_path' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder

			if ( is_null( $result ) ) {
				$sql = "ALTER TABLE  `$table_name` ADD  `plugin_path` mediumtext AFTER  `created_timestamp` ;"; // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning -- Ignoring warning related to Schema change
				$results1 = $wpdb->query( $wpdb->prepare( 'ALTER TABLE %1s ADD  %1s mediumtext AFTER  %1s ', array( $table_name, 'plugin_path', 'created_timestamp' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Ignoring warning related to Schema change
			}

			$table_name = $this->email_audit_table;
			if ( $wpdb->get_var( $wpdb->prepare( 'show tables like %s', array( $table_name ) ) ) !== $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
				$sql = 'CREATE TABLE ' . $table_name . ' ( `id` int NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL , `username` mediumtext NOT NULL, `reason` mediumtext, `created_timestamp` int, UNIQUE KEY id (id) );'; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Ignoring warning related to Schema change
				dbDelta( $sql );
			}

			$table_name = $this->attack_list;
			if ( $wpdb->get_var( $wpdb->prepare( 'show tables like %s', array( $table_name ) ) ) !== $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange , WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring warning related to Schema change, Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
				$results = $wpdb->query( $wpdb->prepare( 'create table %1s( ip varchar(20), type varchar(20), time bigint, input mediumtext );', array( $table_name ) ) );
			}
		}


		/**
		 * Returns blocked IP count
		 *
		 * @param string $ip_address ip address.
		 * @return string
		 */
		public function get_ip_blocked_count( $ip_address ) {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s WHERE ip_address = %s', array( $this->blocked_ips_table, $ip_address ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Returns total blocked ips.
		 *
		 * @return object
		 */
		public function get_total_blocked_ips() {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s', array( $this->blocked_ips_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Returns total manual blocked ips.
		 *
		 * @return object
		 */
		public function get_total_manual_blocked_ips() {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %1s WHERE reason = 'Blocked by Admin' ", array( $this->blocked_ips_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Returns total blocked ips by waf.
		 *
		 * @return object
		 */
		public function get_total_blocked_ips_waf() {
			global $wpdb;
			$total_ip_blocked = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s', array( $this->blocked_ips_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
			return $total_ip_blocked - $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %1s WHERE reason = 'Blocked by Admin'", array( $this->blocked_ips_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Returns total blocked attach count
		 *
		 * @param string $attack attack type.
		 * @return object
		 */
		public function get_blocked_attack_count( $attack ) {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s WHERE type = %s', array( $this->attack_list, $attack ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Returns count of blocked ips.
		 *
		 * @return object
		 */
		public function get_count_of_blocked_ips() {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s', array( $this->blocked_ips_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}


		/**
		 * Returns if IP blocked
		 *
		 * @param string $entryid ip address.
		 * @return object
		 */
		public function get_blocked_ip( $entryid ) {
			global $wpdb;
			return $wpdb->get_results( $wpdb->prepare( 'SELECT ip_address FROM %1s WHERE id= %d', array( $this->blocked_ips_table, $entryid ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Get blocked ip list
		 *
		 * @return object
		 */
		public function get_blocked_ip_list() {
			global $wpdb;
			return $wpdb->get_results( $wpdb->prepare( 'SELECT id, reason, ip_address, created_timestamp FROM %1s', array( $this->blocked_ips_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Get blocked attack list
		 *
		 * @return object
		 */
		public function get_blocked_attack_list() {
			global $wpdb;
			return $wpdb->get_results( $wpdb->prepare( 'SELECT ip, type, time, input FROM %1s', array( $this->attack_list ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}


		/**
		 * Insert blocked IP
		 *
		 * @param string $ip_address ip.
		 * @param string $reason reason.
		 * @param string $blocked_for_time blocked duration.
		 * @return void
		 */
		public function insert_blocked_ip( $ip_address, $reason, $blocked_for_time ) {
			global $wpdb;
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
				$this->blocked_ips_table,
				array(
					'ip_address'        => $ip_address,
					'reason'            => $reason,
					'blocked_for_time'  => $blocked_for_time,
					'created_timestamp' => current_time( 'timestamp' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested  -- Ignoring warning related to timestamp use
				),
				array( '%s', '%s', '%d', '%d' )
			);
		}

		/**
		 * Delete blocked ips
		 *
		 * @param string $entryid ip address.
		 * @return void
		 */
		public function delete_blocked_ip( $entryid ) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( 'DELETE FROM %1s WHERE id = %d', array( $this->blocked_ips_table, $entryid ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Whiteliisted ip count.
		 *
		 * @param string $ip_address ip address.
		 * @return object
		 */
		public function get_whitelisted_ip_count( $ip_address ) {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s WHERE ip_address = %s', array( $this->whitelist_ips_table, $ip_address ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Insert whitelisted ip.
		 *
		 * @param string $ip_address ip address.
		 * @return void
		 */
		public function insert_whitelisted_ip( $ip_address ) {
			global $wpdb;
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
				$this->whitelist_ips_table,
				array(
					'ip_address'        => $ip_address,
					'created_timestamp' => current_time( 'timestamp' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested  -- Ignoring warning related to timestamp use
				),
				array( '%s', '%d' )
			);
		}

		/**
		 * Returns whitelisted IP count
		 *
		 * @return object
		 */
		public function get_number_of_whitelisted_ips() {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s', array( $this->whitelist_ips_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Delete whitelisted ip
		 *
		 * @param string $entryid ip address.
		 * @return void
		 */
		public function delete_whitelisted_ip( $entryid ) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( 'DELETE FROM %1s WHERE id = %d', array( $this->whitelist_ips_table, $entryid ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Get whitelisted IP list
		 *
		 * @return string
		 */
		public function get_whitelisted_ips_list() {
			global $wpdb;
			return $wpdb->get_results( $wpdb->prepare( 'SELECT id, ip_address, created_timestamp FROM %1s', array( $this->whitelist_ips_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Get email audit count
		 *
		 * @param string $ip_address ip address.
		 * @param string $username username.
		 * @return object
		 */
		public function get_email_audit_count( $ip_address, $username ) {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s WHERE ip_address = %s AND username= %s', array( $this->email_audit_table, $ip_address, $username ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Insert email audit.
		 *
		 * @param string $ip_address ip address.
		 * @param string $username username.
		 * @param string $reason reason.
		 * @return void
		 */
		public function insert_email_audit( $ip_address, $username, $reason ) {
			global $wpdb;
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
				$this->email_audit_table,
				array(
					'ip_address'        => $ip_address,
					'username'          => $username,
					'reason'            => $reason,
					'created_timestamp' => current_time( 'timestamp' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested  -- Ignoring warning related to timestamp use
				),
				array( '%s', '%s', '%s', '%d' )
			);
		}

		/**
		 * Insrt transaction.
		 *
		 * @param string $ip_address ip address.
		 * @param string $username username.
		 * @param string $type type.
		 * @param string $status status.
		 * @param string $url url.
		 * @return void
		 */
		public function insert_transaction_audit( $ip_address, $username, $type, $status, $url = null ) {
			global $wpdb;
			$data        = array(
				'ip_address'        => $ip_address,
				'username'          => $username,
				'type'              => $type,
				'status'            => $status,
				'created_timestamp' => current_time( 'timestamp' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Ignoring warning related to timestamp use
			);
			$format      = array( '%s', '%s', '%s', '%s', '%d' );
			$data['url'] = is_null( $url ) ? '' : $url;
			$url         = esc_url_raw( $url );
			$wpdb->insert( $this->transaction_table, $data, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Get transaction list.
		 *
		 * @return object.
		 */
		public function get_transasction_list() {
			global $wpdb;
			return $wpdb->get_results( $wpdb->prepare( 'SELECT ip_address, username, type, status, created_timestamp FROM %1s order by id desc limit 5000', array( $this->transaction_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Get login transaction limit.
		 *
		 * @return object
		 */
		public function get_login_transaction_report() {
			global $wpdb;

			return $wpdb->get_results( $wpdb->prepare( "SELECT ip_address, username, status, created_timestamp FROM %1s WHERE type='User Login' order by id desc limit 5000", array( $this->transaction_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Get error transaction report.
		 *
		 * @return object
		 */
		public function get_error_transaction_report() {
			global $wpdb;
			return $wpdb->get_results( $wpdb->prepare( "SELECT ip_address, username, url, type, created_timestamp FROM %1s WHERE type <> 'User Login' order by id desc limit 5000", array( $this->transaction_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Update transaction report
		 *
		 * @param mixed $where where.
		 * @param mixed $update update.
		 * @return void
		 */
		public function update_transaction_table( $where, $update ) {
			global $wpdb;

			$sql = 'UPDATE ' . $this->transaction_table . ' SET ';
			$i   = 0;
			foreach ( $update as $key => $value ) {
				if ( 0 !== $i % 2 ) {
					$sql .= ' , ';
				}
				if ( 'created_timestamp' === $key || 'id' === $key ) {
					$sql .= $wpdb->prepare( '%1s = %d', array( $key, $value ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring complex placeholder warning as it is used for table name
				} else {
					$sql .= $wpdb->prepare( '%1s = %s', array( $key, $value ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring complex placeholder warning as it is used for table name
				}
				$i++;
			}
			$sql .= ' WHERE ';
			$i    = 0;
			foreach ( $where as $key => $value ) {
				if ( 0 !== $i % 2 ) {
					$sql .= ' AND ';
				}
				if ( 'created_timestamp' === $key || 'id' === $key ) {
					$sql .= $wpdb->prepare( ' %1s = %d ', array( $key, $value ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring complex placeholder warning as it is used for table name
				} else {
					$sql .= $wpdb->prepare( ' %1s = %s ', array( $key, $value ) );  // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring complex placeholder warning as it is used for table name
				}
				$i++;
			}

			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Ignoring warnings as prepare() is used in above statement
		}

		/**
		 * Get count of attack blocked.
		 *
		 * @return string
		 */
		public function get_count_of_attacks_blocked() {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s WHERE status = %s OR status = %s', array( $this->transaction_table, MoWpnsConstants::FAILED, MoWpnsConstants::PAST_FAILED ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Undocumented function
		 *
		 * @param string $ip_address ip address.
		 * @return string
		 */
		public function get_failed_transaction_count( $ip_address ) {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s WHERE ip_address = %s AND status = %s', array( $this->transaction_table, $ip_address, MoWpnsConstants::FAILED ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Delete transactions
		 *
		 * @param string $ip_address ip address.
		 * @return void
		 */
		public function delete_transaction( $ip_address ) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( 'DELETE FROM %1s WHERE ip_address = %s AND status= %s ', array( $this->transaction_table, $ip_address, MoWpnsConstants::FAILED ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}
	}
}
