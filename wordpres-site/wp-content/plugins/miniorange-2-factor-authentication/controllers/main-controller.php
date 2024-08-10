<?php
/**
 * This includes files according to the switch case.
 *
 * @package miniOrange-2-factor-authentication/controllers
 */

// Needed in both.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $mo_wpns_utility,$mo2f_dir_name;

$controller = $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR;

require_once $controller . 'navbar.php';

$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
if ( current_user_can( 'administrator' ) ) {

	if ( ! is_null( $current_page ) ) {
		switch ( $current_page ) {
			case 'mo_2fa_upgrade':
				include_once $controller . 'upgrade.php';
				break;
			case 'mo_2fa_blockedips':
				include_once $controller . 'ip-blocking.php';
				break;
			case 'mo_2fa_advancedblocking':
				include_once $controller . 'advanced-blocking.php';
				break;
			case 'mo_2fa_notifications':
				include_once $controller . 'notification-settings.php';
				break;
			case 'mo_2fa_all_users':
				include_once $controller . 'all-users.php';
				break;
			case 'mo_2fa_reports':
				include_once $controller . 'reports.php';
				break;
			case 'mo_2fa_troubleshooting':
				include_once $controller . 'troubleshooting.php';
				break;
			case 'mo_2fa_addons':
				include_once $controller . 'addons.php';
				break;
			case 'mo_2fa_two_fa':
				include_once $controller . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa.php';
				break;
			case 'mo_2fa_request_demo':
				include_once $controller . 'request-demo.php';
				break;
		}
	}
} else {
	if ( ! is_null( $current_page ) ) {
		switch ( $current_page ) {
			case 'mo_2fa_two_fa':
				include_once $controller . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa.php';
				break;
		}
	}
}


