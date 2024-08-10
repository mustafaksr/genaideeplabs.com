<?php
/**
 * This file includes the UI for 2fa methods options.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

// Needed in both.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$email_registered = 1;
global $mo2fdb_queries;

$email = wp_get_current_user()->user_email;

if ( isset( $email ) ) {
	$email_registered = 1;
} else {
	$email_registered = 0;
}

require_once $mo2f_dir_name . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'setup-twofa.php';
