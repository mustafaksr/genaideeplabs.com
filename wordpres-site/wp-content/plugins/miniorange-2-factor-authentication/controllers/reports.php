<?php
/**
 * Used to show the user login activity.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

// Needed in both.
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Helper\MoWpnsMessages;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $mo_wpns_utility,$mo2f_dir_name;

if ( isset( $_POST['option'] ) && 'mo_wpns_manual_clear' === sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
	if ( ! wp_verify_nonce( $nonce, 'mo2f-manual-report-clear' ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
	}
	global $wpdb;

	$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . "mo2f_network_transactions WHERE Status='success' or Status= 'pastfailed' or Status='failed' " ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
}
if ( isset( $_POST['option'] ) && 'mo_wpns_manual_errorclear' === sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
	if ( ! wp_verify_nonce( $nonce, 'mo2f-manual-error-clear' ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
	}
	global $wpdb;
	$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . "mo2f_network_transactions WHERE Status='accessDenied'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- DB Direct Query is necessary here. 
}

	$mo_wpns_handler   = new MoWpnsHandler();
	$logintranscations = $mo_wpns_handler->get_login_transaction_report();
	$errortranscations = $mo_wpns_handler->get_error_transaction_report();

	$manual_report_clear_nonce = wp_create_nonce( 'mo2f-manual-report-clear' );
	$manual_error_clear_nonce  = wp_create_nonce( 'mo2f-manual-error-clear' );
	require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'reports.php';


