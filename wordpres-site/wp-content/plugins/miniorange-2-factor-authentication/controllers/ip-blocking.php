<?php
/**
 * File contains functions related to ip blocking.
 *
 * @package miniOrange-2-factor-authentication/controllers
 */

// Needed in both.

use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Helper\MoWpnsConstants;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $mo_wpns_utility,$mo2f_dir_name;
$mo_wpns_handler = new MoWpnsHandler();

if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) ) {
	$mo2f_error = new WP_Error();
	$mo2f_error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
	wp_send_json_error( $mo2f_error );
} elseif ( current_user_can( 'manage_options' ) && isset( $_POST['option'] ) ) {
	$option  = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
	$ip      = isset( $_POST['IP'] ) ? sanitize_text_field( wp_unslash( $_POST['IP'] ) ) : '';
	$mo2f_id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	switch ( $option ) {
		case 'mo_wpns_manual_block_ip':
			wpns_handle_manual_block_ip( wp_unslash( $ip ) );
			break;
		case 'mo_wpns_unblock_ip':
			wpns_handle_unblock_ip( $mo2f_id );
			break;
		case 'mo_wpns_whitelist_ip':
			wpns_handle_whitelist_ip( $ip );
			break;
		case 'mo_wpns_remove_whitelist':
			wpns_handle_remove_whitelist( $mo2f_id );
			break;
	}
}

$blockedips      = $mo_wpns_handler->get_blocked_ips();
$whitelisted_ips = $mo_wpns_handler->get_whitelisted_ips();
$mo2f_path       = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'loader.gif';
$mo2f_path       = explode( 'plugins', $mo2f_path );
$img_loader_url  = plugins_url() . $mo2f_path[1];
$page_url        = '';
$license_url     = add_query_arg( array( 'page' => 'mo_2fa_upgrade' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) );

/**
 * Handles manual ip blocking and whitelisting.
 *
 * @param string $ip The ip adress which needs to block or whitelist.
 * @return void
 */
function wpns_handle_manual_block_ip( $ip ) {
	global $mo_wpns_utility;
	if ( $mo_wpns_utility->check_empty_or_null( $ip ) ) {
		echo( 'empty IP' );
		exit;
	}
	if ( ! preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $ip ) ) {
		echo( 'INVALID_IP_FORMAT' );
		exit;
	} else {

		$ip_address     = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'INVALID_IP_FORMAT';
		$mo_wpns_config = new MoWpnsHandler();
		$is_whitelisted = $mo_wpns_config->is_whitelisted( $ip_address );
		if ( ! $is_whitelisted ) {
			if ( $mo_wpns_config->mo_wpns_is_ip_blocked( $ip_address ) ) {
				echo( 'already blocked' );
				exit;
			} else {
				$mo_wpns_config->mo_wpns_block_ip( $ip_address, MoWpnsConstants::BLOCKED_BY_ADMIN, true );
				?>
					<table id="blockedips_table1" class="display">
				<thead><tr><th>IP Address&emsp;&emsp;</th><th>Reason&emsp;&emsp;</th><th>Blocked Until&emsp;&emsp;</th><th>Blocked Date&emsp;&emsp;</th><th>Action&emsp;&emsp;</th></tr></thead>
				<tbody>
				<?php
				$mo_wpns_handler = new MoWpnsHandler();
				$blockedips      = $mo_wpns_handler->get_blocked_ips();
				$whitelisted_ips = $mo_wpns_handler->get_whitelisted_ips();
				global $mo2f_dir_name;
				foreach ( $blockedips as $blockedip ) {
					echo "<tr class='mo_wpns_not_bold'><td>" . esc_html( $blockedip->ip_address ) . '</td><td>' . esc_html( $blockedip->reason ) . '</td><td>';
					if ( empty( $blockedip->blocked_for_time ) ) {
						echo '<span class=redtext>Permanently</span>';
					} else {
						echo esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->blocked_for_time ) );
					}
					echo '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->created_timestamp ) ) . "</td><td><a  onclick=unblockip('" . esc_js( $blockedip->id ) . "')>Unblock IP</a></td></tr>";
				}
				?>
					</tbody>
					</table>
					<script type="text/javascript">
						jQuery("#blockedips_table1").DataTable({
						"order": [[ 3, "desc" ]]
						});
					</script>
				<?php
				exit;
			}
		} else {
			echo( 'IP_IN_WHITELISTED' );
			exit;
		}
	}
}

/**
 * Handles the unblock ip.
 *
 * @param integer $entry_id User id.
 * @return void
 */
function wpns_handle_unblock_ip( $entry_id ) {
	global $mo_wpns_utility;

	if ( $mo_wpns_utility->check_empty_or_null( $entry_id ) ) {
		echo( 'UNKNOWN_ERROR' );
		exit;
	} else {
		$entryid        = sanitize_text_field( $entry_id );
		$mo_wpns_config = new MoWpnsHandler();
		$mo_wpns_config->unblock_ip_entry( $entryid );
		?>
				<table id="blockedips_table1" class="display">
				<thead><tr><th>IP Address&emsp;&emsp;</th><th>Reason&emsp;&emsp;</th><th>Blocked Until&emsp;&emsp;</th><th>Blocked Date&emsp;&emsp;</th><th>Action&emsp;&emsp;</th></tr></thead>
				<tbody>
		<?php
			$mo_wpns_handler = new MoWpnsHandler();
			$blockedips      = $mo_wpns_handler->get_blocked_ips();
			$whitelisted_ips = $mo_wpns_handler->get_whitelisted_ips();
			global $mo2f_dir_name;
		foreach ( $blockedips as $blockedip ) {
			echo "<tr class='mo_wpns_not_bold'><td>" . esc_html( $blockedip->ip_address ) . '</td><td>' . esc_html( $blockedip->reason ) . '</td><td>';
			if ( empty( $blockedip->blocked_for_time ) ) {
				echo '<span class=redtext>Permanently</span>';
			} else {
				echo esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->blocked_for_time ) );
			}
			echo '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->created_timestamp ) ) . "</td><td><a onclick=unblockip('" . esc_js( $blockedip->id ) . "')>Unblock IP</a></td></tr>";
		}
		?>
					</tbody>
					</table>
					<script type="text/javascript">
						jQuery("#blockedips_table1").DataTable({
						"order": [[ 3, "desc" ]]
						});
					</script>
				<?php

				exit;
	}
}

/**
 * Handles the whitelisting ips.
 *
 * @param string $ip The ip adress which need to be whitelist.
 * @return void
 */
function wpns_handle_whitelist_ip( $ip ) {
	global $mo_wpns_utility;
	if ( $mo_wpns_utility->check_empty_or_null( $ip ) ) {
		echo( 'EMPTY IP' );
		exit;
	}
	if ( ! preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $ip ) ) {
			echo( 'INVALID_IP' );
			exit;
	} else {
		$ip_address     = ( filter_var( $ip, FILTER_VALIDATE_IP ) ) ? $ip : 'INVALID_IP';
		$mo_wpns_config = new MoWpnsHandler();
		if ( $mo_wpns_config->is_whitelisted( $ip_address ) ) {
			echo( 'IP_ALREADY_WHITELISTED' );
			exit;
		} else {
			$mo_wpns_config->whitelist_ip( $ip );
			$mo_wpns_handler = new MoWpnsHandler();
			$whitelisted_ips = $mo_wpns_handler->get_whitelisted_ips();

			?>
				<table id="whitelistedips_table1" class="display">
				<thead><tr><th >IP Address</th><th >Whitelisted Date</th><th >Remove from Whitelist</th></tr></thead>
				<tbody>
			<?php
			foreach ( $whitelisted_ips as $whitelisted_ip ) {
				echo "<tr class='mo_wpns_not_bold'><td>" . esc_html( $whitelisted_ip->ip_address ) . '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $whitelisted_ip->created_timestamp ) ) . "</td><td><a  onclick=removefromwhitelist('" . esc_js( $whitelisted_ip->id ) . "')>Remove</a></td></tr>";
			}

			?>
				</tbody>
				</table>
			<script type="text/javascript">
				jQuery("#whitelistedips_table1").DataTable({
				"order": [[ 1, "desc" ]]
				});
			</script>

			<?php
			exit;
		}
	}
}

/**
 * Remove the whitelisted ips.
 *
 * @param integer $entry_id User id.
 * @return void
 */
function wpns_handle_remove_whitelist( $entry_id ) {
	global $mo_wpns_utility;
	if ( $mo_wpns_utility->check_empty_or_null( $entry_id ) ) {
		echo( 'UNKNOWN_ERROR' );
		exit;
	} else {
		$entryid        = isset( $entry_id ) ? sanitize_text_field( $entry_id ) : '';
		$mo_wpns_config = new MoWpnsHandler();
		$mo_wpns_config->remove_whitelist_entry( $entryid );
		$mo_wpns_handler = new MoWpnsHandler();
		$whitelisted_ips = $mo_wpns_handler->get_whitelisted_ips();

		?>
				<table id="whitelistedips_table1" class="display">
				<thead><tr><th >IP Address</th><th >Whitelisted Date</th><th >Remove from Whitelist</th></tr></thead>
				<tbody>
		<?php
		foreach ( $whitelisted_ips as $whitelisted_ip ) {
			echo "<tr class='mo_wpns_not_bold'><td>" . esc_html( $whitelisted_ip->ip_address ) . '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $whitelisted_ip->created_timestamp ) ) . "</td><td><a onclick=removefromwhitelist('" . esc_js( $whitelisted_ip->id ) . "')>Remove</a></td></tr>";
		}

		?>
				</tbody>
				</table>
			<script type="text/javascript">
				jQuery("#whitelistedips_table1").DataTable({
				"order": [[ 1, "desc" ]]
				});
			</script>

		<?php
		exit;
	}
}


