<?php
/**
 * This file used to show the plugin troubleshooting steps.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

// Needed in both.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	global $mo_wpns_utility,$mo2f_dir_name;

	require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'troubleshooting.php';
