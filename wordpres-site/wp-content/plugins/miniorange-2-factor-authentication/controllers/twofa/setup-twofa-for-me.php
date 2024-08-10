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

require_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'setup-twofa-for-me.php';
