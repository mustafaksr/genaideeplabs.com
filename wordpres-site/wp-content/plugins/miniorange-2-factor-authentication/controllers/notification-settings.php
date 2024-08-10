<?php
/**
 * This file contains all methods to show the notification in the plugin.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

// Needed in both.
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MoWpnsConstants;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	global $mo_wpns_utility,$mo2f_dir_name;

	$template1 = 'Hello,<br><br>The user with IP Address <b>##ipaddress##</b> has exceeded allowed trasaction limit on your website <b>' . get_bloginfo() . '</b> and we have blocked his IP address for further access to website.<br><br>You can login to your WordPress dashaboard to check more details.<br><br>Thanks,<br>miniOrange';
	$template2 = 'Hello ##username##,<br><br>Your account was logged in from new IP Address <b>##ipaddress##</b> on website <b>' . get_bloginfo() . "</b>. Please <a href='mailto:" . MoWpnsConstants::SUPPORT_EMAIL . "'>contact us</a> if you don't recognise this activity.<br><br>Thanks,<br>" . get_bloginfo();

if ( current_user_can( 'manage_options' ) && isset( $_POST['nonce'] ) ? wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'mo2f-notification-settings-nonce' ) : '0' ) {
	$option = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
	switch ( $option ) {
		case 'mo_wpns_enable_ip_blocked_email_to_admin':
			wpns_handle_notify_admin_on_ip_block( $_POST );
			break;
		case 'mo_wpns_enable_unusual_activity_email_to_user':
			wpns_handle_notify_unusual_activity( $_POST );
			break;
		case 'custom_user_template':
			if ( isset( $_POST['custom_user_template'] ) ) {
				wpns_handle_custom_template( wp_kses_post( wp_unslash( $_POST['custom_user_template'] ) ) );
			}
			break;
		case 'mo_wpns_get_manual_email':
			wpns_handle_admin_email( $_POST );
			break;
		case 'custom_admin_template':
			if ( isset( $_POST['custom_admin_template'] ) ) {
				wpns_handle_custom_template( null, wp_kses_post( wp_unslash( $_POST['custom_admin_template'] ) ) );
			}
			break;
	}
}
if ( ! get_option( 'admin_email_address_status' ) || '' === get_option( 'admin_email_address' ) ) {
	update_option( 'mo_wpns_enable_ip_blocked_email_to_admin', '0' );
	$notify_admin_on_ip_block = MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_enable_ip_blocked_email_to_admin', 'get_option' ) ? '' : 'unchacked';
}
	$notify_admin_on_ip_block      = MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_enable_ip_blocked_email_to_admin', 'get_option' ) ? 'checked' : '';
	$notify_admin_unusual_activity = get_option( 'mo_wpns_enable_unusual_activity_email_to_user' ) ? 'checked' : '';

	$template1                   = get_option( 'custom_admin_template' ) ? get_option( 'custom_admin_template' ) : $template1;
	$template_type1              = 'custom_admin_template';
	$ip_blocking_template        = array(
		'textarea_name' => 'custom_admin_template',
		'wpautop'       => false,
	);
	$from_email                  = get_option( 'mo2f_email' );
	$template2                   = get_option( 'custom_user_template' ) ? get_option( 'custom_user_template' ) : $template2;
	$template_type2              = 'custom_user_template';
	$user_activity_template      = array(
		'textarea_name' => 'custom_user_template',
		'wpautop'       => false,
	);
	$notification_settings_nonce = wp_create_nonce( 'mo2f-notification-settings-nonce' );
	require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'notification-settings.php';

	/**
	 * Used to save admin email.
	 *
	 * @param object $post_value contails admin email address.
	 * @return void
	 */
	function wpns_handle_admin_email( $post_value ) {
		$show_message = new MoWpnsMessages();
		$nonce        = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
		if ( ! wp_verify_nonce( $nonce, 'mo2f-manual-email-nonce' ) ) {
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
		}
		$email = isset( $_POST['admin_email_address'] ) ? sanitize_email( wp_unslash( $_POST['admin_email_address'] ) ) : '';
		if ( is_email( $email ) ) {
			$admin_email_address_status = isset( $post_value['admin_email_address'] ) ? '1' : '0';
			update_option( 'admin_email_address', $email );
			update_option( 'admin_email_address_status', $admin_email_address_status );
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::EMAIL_SAVED ), 'SUCCESS' );
		} else {
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_EMAIL ), 'ERROR' );
		}
	}
	/**
	 * Used to validate email format.
	 *
	 * @param string $str receive email to validate.
	 * @return bool
	 */
	function validate_email( $str ) {
		return ( ! preg_match( '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix', $str ) ) ? false : true;
	}

	/**
	 * To handle the IP blocking feature.
	 *
	 * @param object $post_value receive an option value to enable this feature.
	 * @return void
	 */
	function wpns_handle_notify_admin_on_ip_block( $post_value ) {
		$show_message = new MoWpnsMessages();
		$nonce        = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
		if ( ! wp_verify_nonce( $nonce, 'mo2f-verify-ip_blocked-nonce' ) ) {
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
		}
		$enable_ip_blocked_email_to_admin = isset( $post_value['enable_ip_blocked_email_to_admin'] ) ? true : false;
		update_option( 'mo_wpns_enable_ip_blocked_email_to_admin', $enable_ip_blocked_email_to_admin );

		if ( $enable_ip_blocked_email_to_admin ) {
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::NOTIFY_ON_IP_BLOCKED ), 'SUCCESS' );
		} else {
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::DONOT_NOTIFY_ON_IP_BLOCKED ), 'ERROR' );
		}
	}

	/**
	 * This function is used to show the unusual activity.
	 *
	 * @param object $post_value post value is option value to enable or disable the feature.
	 * @return void
	 */
	function wpns_handle_notify_unusual_activity( $post_value ) {
		$show_message = new MoWpnsMessages();
		$nonce        = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
		if ( ! wp_verify_nonce( $nonce, 'mo2f-unusual_activity-nonce' ) ) {
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
		}
		$enable_unusual_activity_email_to_user = isset( $post_value['enable_unusual_activity_email_to_user'] ) ? true : false;
		update_option( 'mo_wpns_enable_unusual_activity_email_to_user', $enable_unusual_activity_email_to_user );

		if ( $enable_unusual_activity_email_to_user ) {
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::NOTIFY_ON_UNUSUAL_ACTIVITY ), 'SUCCESS' );
		} else {
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::DONOT_NOTIFY_ON_UNUSUAL_ACTIVITY ), 'ERROR' );
		}
	}

	/**
	 * This function used to save the custom notification template.
	 *
	 * @param string $template1 custom user notification template.
	 * @param string $template2 custom admin notification template.
	 * @return void
	 */
	function wpns_handle_custom_template( $template1, $template2 = null ) {
		$show_message = new MoWpnsMessages();
		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
		if ( ! wp_verify_nonce( $nonce, 'mo2f-admin-templat-nonce' ) ) {
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
		}
		if ( ! is_null( $template1 ) ) {
			update_option( 'custom_user_template', stripslashes( $template1 ) );
		}
		if ( ! is_null( $template2 ) ) {
			update_option( 'custom_admin_template', stripslashes( $template2 ) );
		}
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::TEMPLATE_SAVED ), 'SUCCESS' );
	}
