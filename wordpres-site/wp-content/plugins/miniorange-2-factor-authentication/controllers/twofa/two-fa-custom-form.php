<?php
/**
 * This file is controller for views/two-fa-custom-form.php.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

// Needed in both.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$is_woocommerce   = get_site_option( 'mo2f_custom_reg_wocommerce' );
$is_bbpress       = get_site_option( 'mo2f_custom_reg_bbpress' );
$is_pmpro         = get_site_option( 'mo2f_custom_reg_pmpro' );
$is_custom        = get_site_option( 'mo2f_custom_reg_custom' );
$is_registered    = get_site_option( 'mo2f_customerkey' );
$is_any_of_woo_bb = $is_woocommerce || $is_bbpress || $is_pmpro;

require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-custom-form.php';
