<?php
/**
 * File updates network security options in the options table.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

// Needed in both.
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$nonce = isset( $_POST['mo_security_features_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_security_features_nonce'] ) ) : '';
if ( ! wp_verify_nonce( $nonce, 'mo_2fa_security_features_nonce' ) ) {
	$mo2f_error = new WP_Error();
	$mo2f_error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );

} else {
	global $mo_wpns_utility,$mo2f_dir_name;
	if ( current_user_can( 'manage_options' ) && isset( $_POST['option'] ) ) {
		switch ( sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) {
			case 'mo_wpns_2fa_with_network_security':
				$security_features = new Mo2fa_Security_Features();
				$security_features->wpns_2fa_with_network_security( $_POST );
				break;
		}
	}
}
$network_security_features = MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_2fa_with_network_security', 'get_option' ) ? 'checked' : '';

if ( isset( $_GET['page'] ) ) {
	$tab_count = get_site_option( 'mo2f_tab_count', 0 );
	switch ( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {

		case 'mo_2fa_advancedblocking':
			update_option( 'mo_2f_switch_adv_block', 1 );
			if ( $tab_count < 5 && ! get_site_option( 'mo_2f_switch_adv_block' ) ) {
				update_site_option( 'mo2f_tab_count', get_site_option( 'mo2f_tab_count' ) + 1 );
			}
			break;


	}
}
	$login_security = esc_url( add_query_arg( array( 'page' => 'default' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	$register_url   = esc_url( add_query_arg( array( 'page' => 'registration' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	$blocked_ips    = esc_url( add_query_arg( array( 'page' => 'mo_2fa_blockedips' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	$advance_block  = esc_url( add_query_arg( array( 'page' => 'mo_2fa_advancedblocking' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	$notif_url      = esc_url( add_query_arg( array( 'page' => 'mo_2fa_notifications' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	$reports_url    = esc_url( add_query_arg( array( 'page' => 'mo_2fa_reports' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	$license_url    = esc_url( add_query_arg( array( 'page' => 'mo_2fa_upgrade' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	$help_url       = esc_url( add_query_arg( array( 'page' => 'mo_2fa_troubleshooting' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	$addons_url     = esc_url( add_query_arg( array( 'page' => 'mo_2fa_addons' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	$two_fa         = esc_url( add_query_arg( array( 'page' => 'mo_2fa_two_fa' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	// Added for new design.
	$upgrade_url       = esc_url( add_query_arg( array( 'page' => 'mo_2fa_upgrade' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	$request_demo_url  = esc_url( add_query_arg( array( 'page' => 'mo_2fa_request_demo' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	$request_offer_url = esc_url( add_query_arg( array( 'page' => 'mo_2fa_request_offer' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	// dynamic.
	$logo_url                    = plugin_dir_url( dirname( __FILE__ ) ) . 'includes/images/miniorange_logo.png';
	$login_with_usename_only_url = plugin_dir_url( dirname( __FILE__ ) ) . 'includes/images/login-with-2fa-and-password.png';
	$hide_login_form_url         = plugin_dir_url( dirname( __FILE__ ) ) . 'includes/images/hide_default_login_form.png';
	$new_url                     = plugin_dir_url( dirname( __FILE__ ) ) . 'includes/images/new.png';
	$shw_feedback                = get_option( 'donot_show_feedback_message' ) ? false : true;

	$mo_plugin_handler            = new MoWpnsHandler();
	$safe                         = $mo_plugin_handler->is_whitelisted( $mo_wpns_utility->get_client_ip() );
	$active_tab                   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	$user_id                      = get_current_user_id();
	$mo2f_two_fa_method           = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user_id );
	$backup_codes_remaining       = get_user_meta( $user_id, 'mo2f_backup_codes', true );
	$mo_auth_methods              = array(
		MoWpnsConstants::SOFT_TOKEN,
		MoWpnsConstants::PUSH_NOTIFICATIONS,
		MoWpnsConstants::MOBILE_AUTHENTICATION,
	);
	$is_miniorange_auth_confgured = in_array( $mo2f_two_fa_method, $mo_auth_methods, true );
	if ( is_array( $backup_codes_remaining ) ) {
		$backup_codes_remaining = count( $backup_codes_remaining );
	} else {
		$backup_codes_remaining = 0;
	}
	require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'navbar.php';
