<?php
/**
 * Description: This file is used to add addons to the plugin.
 *
 * @package miniorange-2-factor-authentication/controllers.
 */

// Needed in both.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

	global $mo_wpns_utility,$mo2f_dir_name;

	require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'addons.php';
