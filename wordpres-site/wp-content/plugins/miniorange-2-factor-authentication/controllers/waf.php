<?php
/**
 * This file used to show the network security details.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

// Needed in both.

use TwoFA\Helper\MoWpnsHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	global $mo_wpns_utility,$mo2f_dir_name;
	$mo_wpns_handler      = new MoWpnsHandler();
	$total_attacks        = $sql_c + $lfi_ + $rfi_c + $xss_c + $rce_c;
	$manual_blocks        = $mo_wpns_handler->get_manual_blocked_ip_count();
	$real_time            = 0;
	$i_p_blocked_by_w_w_f = $mo_wpns_handler->get_blocked_ip_waf();
	$total_i_p_blocked    = $manual_blocks + $real_time + $i_p_blocked_by_w_w_f;
	$mo_waf               = get_option( 'WAFEnabled' );
if ( $mo_waf ) {
	$mo_waf = false;
} else {
	$mo_waf = true;
}


	$gif_path = dirname( dirname( __FILE__ ) ) . '/includes/images/loader.gif';
	$gif_path = explode( 'plugins', $gif_path );


	$img_loader_url = plugins_url() . '/' . $gif_path[1];
if ( $total_i_p_blocked > 999 ) {
	$total_i_p_blocked = strval( intval( $total_i_p_blocked / 1000 ) ) . 'k+';
}

if ( $total_attacks > 999 ) {
	$total_attacks = strval( intval( $total_attacks / 1000 ) ) . 'k+';
}
	update_site_option( 'mo2f_visit_waf', true );

	require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'waf.php';




