<?php
/**
 * This includes files according to the switch case.
 *
 * @package miniOrange-2-factor-authentication/controllers
 */

// Not included this file anywhere in the plugin. Can remove this.

use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MocURL;
global $mo_wpns_utility,$mo2f_dir_name;
$user  = wp_get_current_user();
$email = get_option( 'mo2f_email' );
$phone = get_option( 'mo_wpns_admin_phone' );

if ( empty( $email ) ) {
	$email = $user->user_email;
}
require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'feedback-footer.php';
$nonce = isset( $_POST['mo_wpns_feedback_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_wpns_feedback_nonce'] ) ) : '';
if ( ! wp_verify_nonce( $nonce, 'mo-wpns-feedback-footer-nonce' ) ) {
	$wperror = new WP_Error();
	$wperror->add( 'empty_username_feedback', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
	return $wperror;
}
if ( current_user_can( 'manage_options' ) && isset( $_POST['option'] ) ) {
	switch ( sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) {
		case 'mo_wpns_send_query':
			mo2fa_wpns_handle_support_form_new( isset( $_POST ['mo2fa_query_email'] ) ? sanitize_email( wp_unslash( $_POST ['mo2fa_query_email'] ) ) : '', isset( $_POST['mo2fa_query'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2fa_query'] ) ) : '', isset( $_POST['mo2fa_query_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2fa_query_phone'] ) ) : '' );
			break;
	}
}

/**
 * This function will invoke to handle the support form .
 *
 * @param string $email It will carry the email .
 * @param string $query It will carry the query .
 * @param string $phone It will carry the phone no .
 * @return void
 */
function mo2fa_wpns_handle_support_form_new( $email, $query, $phone ) {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
	$show_message = new MoWpnsMessages();
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $nonce, 'mo2f-support-form-nonce' ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
	}
	$send_configuration = ( isset( $_POST['mo2f_send_configuration'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_send_configuration'] ) ) : 0 );
	if ( empty( $email ) || empty( $query ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_VALUES ), 'ERROR' );
		return;
	}
	$contact_us   = new MocURL();
	if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_ERROR ), 'ERROR' );
	} else {
		$submitted = json_decode( $contact_us->submit_contact_us( $email, $phone, $query ), true );
	}
	if ( json_last_error() === JSON_ERROR_NONE && $submitted ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_SENT ), 'SUCCESS' );
	} else {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_ERROR ), 'ERROR' );
	}
}
