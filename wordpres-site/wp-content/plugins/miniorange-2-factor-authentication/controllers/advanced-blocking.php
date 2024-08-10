<?php
/**
 * Description: This file is user to handle ip range blocking.
 *
 * @package miniOrange-2-factor-authentication/controllers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Needed in both.
use TwoFA\Helper\MoWpnsMessages;
global $mo_wpns_utility,$mo2f_dir_name;
if ( current_user_can( 'manage_options' ) && isset( $_POST['option'] ) ) {
	if ( ! isset( $_POST['mo2f_security_features_nonce'] ) || ! wp_verify_nonce( ( sanitize_key( $_POST['mo2f_security_features_nonce'] ) ), 'mo2f_security_nonce' ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
	}
	switch ( sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) {
		case 'mo_wpns_block_ip_range':
			wpns_handle_range_blocking( $_POST );
			break;
	}
}

	$range_count = is_numeric( get_option( 'mo_wpns_iprange_count' ) ) && intval( get_option( 'mo_wpns_iprange_count' ) ) !== 0 ? intval( get_option( 'mo_wpns_iprange_count' ) ) : 1;
for ( $i = 1; $i <= $range_count; $i++ ) {
	$ip_range = get_option( 'mo_wpns_iprange_range_' . $i );
	if ( $ip_range ) {
		$a = explode( '-', $ip_range );

		$start[ $i ] = $a[0];
		$end[ $i ]   = $a[1];
	}
}
if ( ! isset( $start[1] ) ) {
	$start[1] = '';
}
if ( ! isset( $end[1] ) ) {
	$end[1] = '';
}



	require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'advanced-blocking.php';

	/* ADVANCD BLOCKING FUNCTIONS */
	/**
	 * Description: Function to save range of ips.
	 *
	 * @param array $posted_value It contains the start and end of range of ips.
	 * @return void
	 */
function wpns_handle_range_blocking( $posted_value ) {
	$flag                  = 0;
	$max_allowed_ranges    = 100;
	$added_mappings_ranges = 0;
	$show_message          = new MoWpnsMessages();
	for ( $i = 1;$i <= $max_allowed_ranges;$i++ ) {
		if ( isset( $posted_value[ 'start_' . $i ] ) && isset( $posted_value[ 'end_' . $i ] ) && ! empty( $posted_value[ 'start_' . $i ] ) && ! empty( $posted_value[ 'end_' . $i ] ) ) {

			$posted_value[ 'start_' . $i ] = sanitize_text_field( $posted_value[ 'start_' . $i ] );
			$posted_value[ 'end_' . $i ]   = sanitize_text_field( $posted_value[ 'end_' . $i ] );

			if ( filter_var( $posted_value[ 'start_' . $i ], FILTER_VALIDATE_IP ) && filter_var( $posted_value[ 'end_' . $i ], FILTER_VALIDATE_IP ) && ( ip2long( $posted_value[ 'end_' . $i ] ) > ip2long( $posted_value[ 'start_' . $i ] ) ) ) {
				$range  = '';
				$range  = sanitize_text_field( $posted_value[ 'start_' . $i ] );
				$range .= '-';
				$range .= sanitize_text_field( $posted_value[ 'end_' . $i ] );
				$added_mappings_ranges++;
				update_option( 'mo_wpns_iprange_range_' . $added_mappings_ranges, $range );

			} else {
				$flag = 1;
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_IP ), 'ERROR' );
				return;
			}
		}
	}

	if ( 0 === $added_mappings_ranges ) {
		update_option( 'mo_wpns_iprange_range_1', '' );
	}
	update_option( 'mo_wpns_iprange_count', $added_mappings_ranges );
	if ( 0 === $flag ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ALL_ENABLED ), 'SUCCESS' );
	}
}

