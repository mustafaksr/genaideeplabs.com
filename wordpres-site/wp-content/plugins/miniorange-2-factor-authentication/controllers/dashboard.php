<?php
/**
 * All the variables related to network security.
 *
 * @package miniOrange-2-factor-authentication/controllers
 */

use TwoFA\Database\MoWpnsDB;
use TwoFA\Helper\MoWpnsHandler;

// We can remove this file as the details mentioned below are not getting used anywhere else.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$wpns_database              = new MoWpnsDB();
$wpns_count_ips_blocked     = $wpns_database->get_count_of_blocked_ips();
$wpns_count_ips_whitelisted = $wpns_database->get_number_of_whitelisted_ips();
$wpns_attacks_blocked       = $wpns_database->get_count_of_attacks_blocked();
$mo_wpns_handler            = new MoWpnsHandler();
require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'dashboard.php';
