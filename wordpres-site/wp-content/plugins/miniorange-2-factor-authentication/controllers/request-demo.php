<?php
/**
 * This file used to get demo request of the plugin.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

// Can remove this file.

use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MocURL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( current_user_can( 'manage_options' ) && isset( $_POST['nonce'] ) ? wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'mo2f-Request-demo' ) : '0' ) {
	$option = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
	switch ( $option ) {
		case 'mo_2FA_demo_request_form':
			wpns_handle_demo_request_form();
			break;
	}
}

	require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'request-demo.php';
/**
 * This method is used to get the request's demo details
 *
 * @return void
 */
function wpns_handle_demo_request_form() {
	$nonce     = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
	$usecase   = isset( $_POST['mo_2FA_demo_usecase'] ) ? sanitize_text_field( wp_unslash( $_POST['mo_2FA_demo_usecase'] ) ) : null;
	$email     = isset( $_POST['mo_2FA_demo_email'] ) ? sanitize_email( wp_unslash( $_POST['mo_2FA_demo_email'] ) ) : null;
	$demo_plan = isset( $_POST['mo_2FA_demo_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['mo_2FA_demo_plan'] ) ) : null;
	if ( ! wp_verify_nonce( $nonce, 'mo2f-Request-demo' ) ) {
			return;
	}
	if ( empty( $usecase ) || empty( $email ) || empty( $demo_plan ) ) {
		do_action( 'wpns_show_message', MoWpnsMessages::show_message( 'DEMO_FORM_ERROR' ), 'SUCCESS' );
		return;
	} else {

		$query      = 'REQUEST FOR DEMO';
		$query     .= ' =>';
		$query     .= $demo_plan;
		$query     .= ' : ';
		$query     .= $usecase;
		$contact_us = new MocURL();
		$submitted  = json_decode( $contact_us->submit_contact_us( $email, '', $query ), true );

		if ( json_last_error() === JSON_ERROR_NONE && $submitted ) {
				do_action( 'wpns_show_message', MoWpnsMessages::show_message( 'SUPPORT_FORM_SENT' ), 'SUCCESS' );
				return;
		} else {
			do_action( 'wpns_show_message', MoWpnsMessages::show_message( 'SUPPORT_FORM_ERROR' ), 'ERROR' );
		}
	}
}

