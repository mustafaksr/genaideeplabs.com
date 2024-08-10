<?php
/**
 * Used to send the support query if user face any issue.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

// Needed in both.
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MoWpnsUtility;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	global $mo2f_dir_name;

if ( current_user_can( 'manage_options' ) && isset( $_POST['nonce'] ) ? wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'mo2f-support-form-nonce' ) : '0' ) {

	$option = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
	switch ( $option ) {
		case 'mo_wpns_send_query':
			wpns_handle_support_form();
			break;
	}
}

	$current_user_info = wp_get_current_user();
	$email             = get_option( 'mo2f_email' );
	$phone             = get_option( 'mo_wpns_admin_phone' );


if ( empty( $email ) ) {
	$email = $current_user_info->user_email;
}
	$support_form_nonce = wp_create_nonce( 'mo2f-support-form-nonce' );
	$query_submitted    = get_transient( 'mo2f_query_sent' ) ? 'true' : 'false';
	require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'support.php';


	/* SUPPORT FORM RELATED FUNCTIONS */

	// Function to handle support form submit.
	/**
	 * This method is used to receive the customer query.
	 *
	 * @return void
	 */
function wpns_handle_support_form() {
	$show_message = new MoWpnsMessages();
	$nonce        = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
	if ( ! wp_verify_nonce( $nonce, 'mo2f-support-form-nonce' ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
	}
	$email              = isset( $_POST['mo2f_query_email'] ) ? sanitize_email( wp_unslash( $_POST['mo2f_query_email'] ) ) : '';
	$query              = isset( $_POST['mo2f_query'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_query'] ) ) : '';
	$phone              = isset( $_POST['mo2f_query_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_query_phone'] ) ) : '';
	$send_configuration = ( isset( $_POST['mo2f_send_configuration'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_send_configuration'] ) ) : 0 );
	if ( empty( $email ) || empty( $query ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_VALUES ), 'ERROR' );
		return;
	}
	$contact_us = new MocURL();
	if ( $send_configuration ) {
		$query = $query . MoWpnsUtility::mo_2fa_send_configuration( true );
	}
	if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_ERROR ), 'ERROR' );
	} elseif ( get_transient( 'mo2f_query_sent' ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::QUERY_SUBMITTED ), 'SUCCESS' );
	} else {
		$submited = json_decode( $contact_us->submit_contact_us( $email, $phone, $query ), true );
	}
	if ( json_last_error() === JSON_ERROR_NONE && $submited ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_SENT ), 'SUCCESS' );
	} else {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_ERROR ), 'ERROR' );
	}
}
