<?php
/**
 * This file show frontend to configure OTP over SMS/Email/Telegram method.
 *
 * @package miniorange-2-factor-authentication/views/twofa/setup
 */

// Needed in both.

use TwoFA\Onprem\Miniorange_Password_2Factor_Login;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$pass2fa_login_session = new Miniorange_Password_2Factor_Login();
$session_id_encrypt    = isset( $_POST['mo2f_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_session_id'] ) ) : $pass2fa_login_session->create_session(); //phpcs:ignore WordPress.Security.NonceVerification.Missing -- frontEnd, nonce is not needed here
echo '<hr>';
if ( current_user_can( 'administrator' ) ) {
	echo wp_kses(
		$skeleton['##remaining_transactions##'],
		array(
			'h3' => array(
				'style' => array(),
				'class' => array(),
			),
			'h4' => array(
				'style' => array(),
			),
			'hr' => array(),
			'b'  => array(),
			'i'  => array(),
			'a'  => array(
				'id'    => array(),
				'class' => array(),
				'style' => array(),
			),
			'br' => array(),

		)
	);
}
echo '
	<form name="f" method="post" action="" id="mo2f_verifyphone_form">
		<input type="hidden" name="option" value="mo2f_configure_otp_based_twofa"/>
		<input type="hidden" name="mo2f_otp_based_method" value="' . esc_attr( $twofa_method ) . '"/>
		<input type="hidden" name="mo2f_session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
     ';
echo wp_kses(
	$skeleton['##instructions##'],
	array(
		'h4' => array(
			'clase' => array(),
			'style' => array(),

		),
		'b'  => array(),

	)
);
echo '
	<div style="display:inline;">';
echo wp_kses(
	$skeleton['##input_field##'],
	array(
		'div'   => array(
			'style' => array(),
			'class' => array(),
		),
		'h2'    => array(),
		'i'     => array(
			'style' => array(),
		),
		'br'    => array(),
		'input' => array(
			'id'      => array(),
			'class'   => array(),
			'name'    => array(),
			'type'    => array(),
			'value'   => array(),
			'style'   => array(),
			'pattern' => array(),
			'title'   => array(),
			'size'    => array(),

		),
		'a'     => array(
			'href'   => array(),
			'target' => array(),
		),
		'span'  => array(
			'title' => array(),
			'class' => array(),
			'style' => array(),
		),

	)
);

$show_validation_form = get_user_meta( $user_id, 'mo2f_otp_send_true', true ) ? 'block' : 'none';
echo '<br>';


echo '<input type="button" name="mo2f_verify" id="mo2f_verify" class="button button-primary button-large" value="' . esc_attr__( 'Verify', 'miniorange-2-factor-authentication' ) . '"/>
</div>
</form>';

echo '
<form name="f" method="post" action="" id="mo2f_validateotp_form" style="display:' . esc_attr( $show_validation_form ) . '">
	<input type="hidden" name="option" value="mo2f_configure_otp_based_methods_validate"/>
	<input type="hidden" name="mo2f_session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
	<input type="hidden" name="mo2f_otp_based_method" value="' . esc_attr( $twofa_method ) . '"/>
	<input type="hidden" name="mo2f_configure_otp_based_methods_validate_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f-configure-otp-based-methods-validate-nonce' ) ) . '"/> <p>' . esc_html__( 'Enter One Time Passcode', 'miniorange-2-factor-authentication' ) . '</p>
	<input class="mo2f_table_textbox" style="width:200px;height:25px;" autofocus="true" type="text" name="otp_token" placeholder="' . esc_attr__( 'Enter OTP', 'miniorange-2-factor-authentication' ) . '" style="width:95%;"/> <a href="#resendotplink">' . esc_html__( 'Resend OTP?', 'miniorange-2-factor-authentication' ) . '</a>
	<br><br>';
	echo '</form><br>';
	echo '<form name="f" method="post" action="" id="mo2f_go_back_form">
            <input type="hidden" name="option" value="mo2f_go_back"/>
            <input type="hidden" name="mo2f_go_back_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ) . '"/>
      </form>';
