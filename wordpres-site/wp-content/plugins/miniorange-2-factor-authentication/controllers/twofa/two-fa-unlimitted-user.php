<?php
/**
 * This file is controller for views/twofa/two-fa-shortcode.php.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

use TwoFA\Helper\MoWpnsConstants;
// Needed in both.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Including the file for frontend.
 */

global $mo_wpns_utility, $mo2f_dir_name, $current_user;
$setup_dir_name             = dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
$settings_tab_tooltip_array = array( 'Users can either have a grace period to configure 2FA (users who don\'t have 2fa setup after grace period, will be enforced to setup 2FA)', 'Selecting the below roles will enable 2-Factor for all users associated with that role.' );
$user                       = wp_get_current_user();
$configured_2_f_a_method    = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
$configured_meth            = array();
$configured_meth            = array( MoWpnsConstants::OUT_OF_BAND_EMAIL, MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SECURITY_QUESTIONS, MoWpnsConstants::AUTHY_AUTHENTICATOR, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OTP_OVER_SMS );
$method_exisits             = in_array( $configured_2_f_a_method, $configured_meth, true );
$image_path                 = plugins_url( '/includes/images/', dirname( dirname( __FILE__ ) ) );

require $setup_dir_name;
require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-unlimitted-user.php';
