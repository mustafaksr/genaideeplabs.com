<?php
/**
 * This file contains frontend to show setup wizard to configure Google Authenticator.
 *
 * @package miniorange-2-factor-authentication/views/twofa/setup
 */

// Needed in cloud.
use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Function to configure Google Authenticator.
 *
 * @param object $user User object.
 * @return void
 */
function mo2f_configure_google_authenticator_cloud( $user ) {
	global $main_dir;
	$mo2f_google_auth      = get_user_meta( $user->ID, 'mo2f_google_auth', true );
	$url                   = isset( $mo2f_google_auth['ga_qrCode'] ) ? $mo2f_google_auth['ga_qrCode'] : null;
	$ga_secret             = isset( $mo2f_google_auth['ga_secret'] ) ? $mo2f_google_auth['ga_secret'] : null;
	$gauth_name            = get_option( 'mo2f_google_appname', preg_replace( '#^https?://#i', '', home_url() ) );
	$pass2fa_login         = new Miniorange_Password_2Factor_Login();
	$session_id_encrypt    = isset( $mo2f_google_auth['mo2f_session_id'] ) ? $mo2f_google_auth['mo2f_session_id'] : $pass2fa_login->create_session();
	$microsoft_url         = 'otpauth://totp/?secret=' . $ga_secret . '&issuer=' . $gauth_name;
	$url                   = 'data:image/jpg;base64,' . $url;
	$qr_code               = '<div id="mo2f_google_auth_qr_code">
		<img id="displayGAQrCodeTour" style="line-height: 0;background:white;" src="' . $url . '" />
	</div>';
	$dashboard_back_button = '<form name="mo2f_go_to_setup_2FA_form" method="post" action="" id="mo2f_go_back_form">
	<input type="hidden" name="option" value="mo2f_go_back"/>
	<input type="hidden" name="mo2f_go_back_nonce" value="' . wp_create_nonce( 'mo2f-go-back-nonce' ) . '"/>
	<input type="submit" name="back" id="go_back" class="button button-primary button-large" value="' . esc_attr__( 'Back', 'miniorange-2-factor-authentication' ) . '"/>
</form>';
	MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'secret_ga', $ga_secret );
	mo2f_configure_google_auth_common_view( $ga_secret, $gauth_name, $qr_code, $url, $microsoft_url, $session_id_encrypt, null, false, $dashboard_back_button );
	wp_enqueue_script( 'mo2f_google_auth_dashboard', $main_dir . 'includes/js/google-authenticator-dashboard.min.js', array(), MO2F_VERSION, false );
}

