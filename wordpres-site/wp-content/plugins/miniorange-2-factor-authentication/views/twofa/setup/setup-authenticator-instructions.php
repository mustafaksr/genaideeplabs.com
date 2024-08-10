<?php
/**
 * This file contains frontend to show instructions for different authenticator apps.
 *
 * @package miniorange-2-factor-authentication/views/twofa/setup
 */

$auth_methods_and_instructions = array(
	'google_authenticator' => array( 'In the app, tap on "%1$1sAdd a code%2$2s" or "%3$3s+%4$4s" sign at right bottom corner of the phone', 'Select "%1$1sScan a QR code%2$2s".' ),
	'msft_authenticator'   => array( 'In the app, tap on "%1$1sAdd account%2$2s" or "%3$3s+%4$4s" sign at right top corner of the phone', 'Select any account from "%1$1sPersonal account/Work or school account/other account(Google, Facebook, etc.)%2$2s".' ),
	'authy_authenticator'  => array( 'In the app, tap on "%1$1s+%2$2s" sign at the center of the phone', 'Tap on "%1$1sScan QR Code%2$2s".' ),
	'last_pass_auth'       => array( 'In the app, tap on "%1$1sAdd account%2$2s" or "%3$3s+%4$4s" sign at right bottom corner of the phone', 'Tap on "%1$1sScan QR Code%2$2s".' ),
	'free_otp_auth'        => array( 'In the app, tap on "%1$1s+%2$2s" sign at the right bottom corner of the phone', 'Tap on "%1$1sQR Code%2$2s" symbol.' ),
	'duo_auth'             => array( 'In the app, tap on "%1$1sSet up account%2$2s"', 'Tap on "%1$1sUse a QR code%2$2s"' ),

);
foreach ( $auth_methods_and_instructions as $method => $instructions ) {
	echo '<div id="mo2f_' . esc_attr( $method ) . '_instructions" style="display:' . esc_attr( 'google_authenticator' !== $method ? 'none' : 'block' ) . '">';
	foreach ( $instructions as $instruction ) {
		echo '<li>';
		printf(
		/* Translators: %s: bold tags */
			esc_html( __( $instruction, 'miniorange-2-factor-authentication' ) ),  //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
			'<b>',
			'</b>',
			'<b>',
			'</b>',
		);
		echo '</li>';
	}
	echo '</div>';
}

echo '<li>';
printf(
	/* Translators: %s: bold tags */
	esc_html( __( 'Scan below QR Code.', 'miniorange-2-factor-authentication' ) ),
);
echo '</li>';
