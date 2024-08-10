<?php
/**
 * File which is called during the plugin is deleting to remove all database options and tables.
 *
 * @package miniOrange-2-factor-authentication.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}
global $wpdb;
$tablename = $wpdb->prefix . 'options';
$prefix    = 'mo2f_%';
$wpdb->query( $wpdb->prepare( 'DELETE FROM %1s  WHERE `option_name` LIKE %s', $tablename, $prefix ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring complex warning of Direct Database call as it is used for tablename.
$value = get_option( 'mo_wpns_registration_status' );
if ( isset( $value ) || ! empty( $value ) ) {
	delete_option( 'mo2f_email' );
}
update_option( 'mo2f_activate_plugin', 1 );
delete_option( 'mo_wpns_transactionId' );
delete_option( 'mo_wpns_registration_status' );
delete_option( 'mo_wpns_customer_token' );
delete_option( 'mo_wpns_message' );
delete_option( 'mo_wpns_transactionId' );
delete_option( 'mo_wpns_registration_status' );
delete_site_option( 'mo2fa_free_plan_new_user_methods' );
delete_site_option( 'mo2fa_free_plan_existing_user_methods' );
delete_option( 'mo2fa_reconfiguration_via_email' );
delete_option( 'mo2fa_userProfile_method' );
delete_option( 'mo_wpns_enable_brute_force' );
delete_option( 'mo_wpns_show_remaining_attempts' );
delete_option( 'mo_wpns_enable_ip_blocked_email_to_admin' );
delete_option( 'mo_wpns_enable_unusual_activity_email_to_user' );
delete_option( 'mo_wpns_company' );
delete_option( 'mo_wpns_firstName' );
delete_option( 'mo_wpns_lastName' );
delete_option( 'mo_wpns_password' );
delete_option( 'mo_wpns_admin_phone' );
delete_option( 'mo_wpns_registration_status' );
delete_option( 'mo_wpns_block_chrome' );
delete_option( 'mo_wpns_block_firefox' );
delete_option( 'mo_wpns_block_ie' );
delete_option( 'mo_wpns_block_safari' );
delete_option( 'mo_wpns_block_opera' );
delete_option( 'mo_wpns_block_edge' );
delete_site_option( base64_encode( 'totalUsersCloud' ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Base64 is needed
delete_option( 'mo_2factor_user_registration_status' );
delete_option( 'mo_2f_switch_all' );
delete_option( 'mo_wpns_scan_initialize' );
delete_site_option( 'mo_2fa_plan_type' );
delete_site_option( 'mo_2fa_addon_plan_type' );
delete_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' );
delete_option( 'mo_wpns_enable_htaccess_blocking' );
delete_option( 'mo_wpns_enable_user_agent_blocking' );
delete_option( 'mo_wpns_countrycodes' );
delete_option( 'mo_wpns_referrers' );
delete_option( 'protect_wp_config' );
delete_option( 'prevent_directory_browsing' );
delete_option( 'disable_file_editing' );
delete_option( 'mo_wpns_enable_comment_spam_blocking' );
delete_option( 'mo_wpns_enable_comment_recaptcha' );
delete_option( 'mo_wpns_2fa_with_network_security' );
delete_option( 'mo_wpns_2fa_with_network_security_popup_visible' );
delete_option( 'mo_wpns_slow_down_attacks' );
delete_option( 'mo_wpns_enable_2fa' );
delete_option( 'mo_wpns_activate_recaptcha' );
delete_option( 'mo_wpns_activate_recaptcha_for_login' );
delete_option( 'mo_wpns_activate_recaptcha_for_registration' );
delete_option( 'mo_wpns_activate_recaptcha_for_woocommerce_login' );
delete_option( 'mo_wpns_activate_recaptcha_for_woocommerce_registration' );
delete_option( 'mo_wpns_recaptcha_site_key' );
delete_option( 'mo_wpns_recaptcha_secret_key' );
delete_option( 'custom_user_template' );
delete_option( 'custom_admin_template' );
delete_option( 'mo_wpns_enable_fake_domain_blocking' );
delete_option( 'mo_wpns_enable_advanced_user_verification' );
delete_option( 'mo_customer_validation_wp_default_enable' );
delete_option( 'mo_wpns_enable_social_integration' );
delete_option( 'mo_wpns_scan_plugins' );
delete_option( 'mo_wpns_scan_themes' );
delete_option( 'mo_wpns_check_vulnerable_code' );
delete_option( 'mo_wpns_check_sql_injection' );
delete_option( 'mo_wpns_scan_wp_files' );
delete_option( 'mo_wpns_skip_folders' );
delete_option( 'mo_wpns_check_external_link' );
delete_option( 'mo_wpns_scan_files_with_repo' );
delete_option( 'mo_wpns_files_scanned' );
delete_option( 'mo_wpns_infected_files' );
delete_option( 'mo_wpns_dbversion' );
delete_site_option( 'mo2fa_superadmin' );
delete_site_option( 'duo_credentials_save_successfully' );

if ( get_option( 'is_onprem' ) ) {
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- DB Direct Query is necessary here.
		$wpdb->prepare( 'DELETE FROM  %1s WHERE meta_key LIKE %s or meta_key=%s or meta_key=%s or meta_key=%s or meta_key=%s  or meta_key=%s or meta_key=%s', array( $wpdb->base_prefix . 'usermeta', $prefix, 'currentMethod', 'email', 'Security Questions', 'Email Verification', 'kba_questions_user', 'Google Authenticator' ) ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- We can not have table name in quotes.
	);

}
$mo_prefix = 'mo_%';
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- DB Direct Query is necessary here.
	$wpdb->prepare( 'DELETE FROM  %1s WHERE meta_key LIKE %s or meta_key LIKE %s or meta_key=%s or meta_key=%s or meta_key=%s or meta_key=%s  or meta_key=%s or meta_key=%s', array( $wpdb->base_prefix . 'usermeta', $prefix, $mo_prefix, 'phone_verification_status', 'test_2FA', 'mo2f_configure_2FA', 'miniorageqr', 'tempRegEmail', 'user_not_enroll' ) ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- We can not have table name in quotes.
);
if ( ! class_exists( 'WPSecurityPro' ) ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpns_transactions" );  //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpns_blocked_ips" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpns_whitelisted_ips" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpns_email_sent_audit" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpns_malware_scan_report" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpns_malware_scan_report_details" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpns_malware_skip_files" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpns_malware_hash_file" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename..
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpns_attack_logs" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpns_ip_rate_details" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
}
// Remove all values of 2FA on deactivate.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mo2f_user_details" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mo2f_user_login_info" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mo2f_network_blocked_ips" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mo2f_network_email_sent_audit" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename. 
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mo2f_network_transactions" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename. 
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mo2f_network_whitelisted_ips" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ignoring complex schema change query as it is used for tablename. 
delete_option( 'mo2f_dbversion' );
delete_site_option( 'mo_2fa_pnp' );

if ( ! is_multisite() ) {
	delete_option( 'user_phone' );
	delete_option( 'mo_2factor_admin_registration_status' );
	delete_option( 'mo_2f_login_type_enabled' );
	delete_option( 'mo2_admin_last_name' );
	delete_option( 'kba_questions' );
	delete_option( 'mo2_admin_last_name' );
	delete_option( 'SQLInjection' );
	delete_site_option( 'WAF' );
	delete_site_option( 'WAFEnabled' );
	delete_option( 'XSSAttack' );
	delete_option( 'RFIAttack' );
	delete_option( 'LFIAttack' );
	delete_option( 'RCEAttack' );
	delete_option( 'actionRateL' );
	delete_option( 'Rate_limiting' );
	delete_option( 'Rate_request' );
	delete_option( 'limitAttack' );
	delete_option( 'skip_tour' );
	delete_option( 'mo_wpns_new_registration' );
	delete_option( 'mo_wpns_enable_log_requests' );
	delete_option( 'mo_wpns_scan_files_extensions' );
	delete_option( 'donot_show_feedback_message' );
	delete_option( 'mo_wpns_enable_rename_login_url' );
	delete_option( 'login_page_url' );
	delete_option( 'mo_wpns_scan_mode' );
	delete_option( 'mo_wpns_malware_scan_in_progress' );
	delete_option( 'scan_failed' );
	delete_option( 'recovery_mode_email_last_sent' );
	// delete all stored key-value pairs for the roles.
	global $wp_roles;
	foreach ( $wp_roles->role_names as $user_id => $name ) {
		delete_option( 'mo2fa_' . $user_id );
		delete_option( 'mo2fa_' . $user_id . '_login_url' );
	}
}
// delete previous version key-value pairs.
delete_option( 'mo_2factor_admin_mobile_registration_status' );
delete_option( 'mo_2factor_registration_status' );
delete_option( 'mo_2factor_temp_status' );
delete_option( 'mo_2factor_login_status' );
delete_option( 'kba_questions' );
delete_option( 'mo_2f_switch_waf' );
delete_option( 'mo_2f_switch_loginspam' );
delete_option( 'mo_2f_switch_malware' );
delete_option( 'mo_2f_switch_adv_block' );
delete_option( 'mo_wpns_last_themes' );
delete_option( 'mo_wpns_last_plugins' );
delete_option( 'mo_wpns_last_scan_time' );
delete_option( 'infected_dismiss' );
delete_option( 'weekly_dismiss' );
delete_option( 'donot_show_infected_file_notice' );
delete_option( 'donot_show_new_plugin_theme_notice' );
delete_option( 'donot_show_weekly_scan_notice' );
