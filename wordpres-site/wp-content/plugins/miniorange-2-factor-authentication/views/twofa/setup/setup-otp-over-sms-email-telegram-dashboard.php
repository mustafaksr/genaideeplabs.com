<?php
/**
 * This file show frontend to configure OTP over SMS/Email/Telegram method.
 *
 * @package miniorange-2-factor-authentication/views/twofa/setup
 */

// Needed in both.

use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( isset( $_POST['mo2f_session_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing -- frontEnd, nonce is not needed here
	$session_id_encrypt = sanitize_text_field( wp_unslash( $_POST['mo2f_session_id'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing -- frontEnd, nonce is not needed here
} else {
	$pass2fa_login_session = new Miniorange_Password_2Factor_Login();
	$session_id_encrypt    = $pass2fa_login_session->create_session();
}
echo '<h3>' . esc_html__( 'Configure ' . MoWpnsConstants::mo2f_convert_method_name( $twofa_method, 'cap_to_small' ), 'miniorange-2-factor-authentication' ) . '</h3>';  //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
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
			),

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
		'i'     => array(),
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

$show_validation_form = get_user_meta( $user->ID, 'mo2f_otp_send_true', true ) ? 'block' : 'none';
echo '<br>';
if ( ! get_user_meta( $user->ID, 'mo2f_otp_send_true', true ) ) {
	echo '<input type="button" name="back" id="go_back_verify" class="button button-primary button-large" value="' . esc_attr__( 'Back', 'miniorange-2-factor-authentication' ) . '"/>&nbsp;&nbsp;';
}
echo '<input type="button" name="verify" id="verify" class="button button-primary button-large" value="' . esc_attr__( 'Verify', 'miniorange-2-factor-authentication' ) . '"/>
	</div>
</form>';

echo '
<form name="f" method="post" action="" id="mo2f_validateotp_form" style="display:' . esc_attr( $show_validation_form ) . '">
	<input type="hidden" name="option" value="mo2f_configure_otp_based_methods_validate"/>
	<input type="hidden" name="mo2f_session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
	<input type="hidden" name="mo2f_otp_based_method" value="' . esc_attr( $twofa_method ) . '"/>
	<input type="hidden" name="mo2f_configure_otp_based_methods_validate_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f-configure-otp-based-methods-validate-nonce' ) ) . '"/> <p>' . esc_html__( 'Enter One Time Passcode', 'miniorange-2-factor-authentication' ) . '</p>
	<input class="mo2f_table_textbox" style="width:200px;" autofocus="true" type="text" name="otp_token" placeholder="' . esc_attr__( 'Enter OTP', 'miniorange-2-factor-authentication' ) . '" style="width:95%;"/> <a href="#resendsmslink">' . esc_html__( 'Resend OTP?', 'miniorange-2-factor-authentication' ) . '</a>
	<br><br>
	<input type="button" name="back" id="go_back" class="button button-primary button-large" value="' . esc_attr__( 'Back', 'miniorange-2-factor-authentication' ) . '"/>
	<input type="button" name="validate" id="validate" class="button button-primary button-large" value="' . esc_attr__( 'Validate OTP', 'miniorange-2-factor-authentication' ) . '"/>
</form><br>';
echo '<form name="f" method="post" action="" id="mo2f_go_back_form">
            <input type="hidden" name="option" value="mo2f_go_back"/>
            <input type="hidden" name="mo2f_go_back_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ) . '"/>
      </form>';

?>
	<form name="f" method="post" action="" id="mo2f_2factor_test_prompt_cross">
		<input type="hidden" name="option" value="mo2f_2factor_test_prompt_cross"/>
		<input type="hidden" name="mo2f_2factor_test_prompt_cross_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-2factor-test-prompt-cross-nonce' ) ); ?>"/>
	</form>

<script>

		jQuery("#mo2f_transactions_check").click(function()
		{   
			var nonce = '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
			var data =
			{
				'action'                  : 'wpns_login_security',
				'wpns_loginsecurity_ajax' : 'wpns_check_transaction',
				'nonce'                   :nonce
			};
			jQuery.post(ajaxurl, data, function(response) {
				window.location.reload(true);
			});
		});
		jQuery("#verify").click(function()
		{  
			var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>';
			var data = {
				'action'  : 'mo_two_factor_ajax',
				'mo_2f_two_factor_ajax'  : 'mo2f_configure_otp_based_twofa',
				'mo2f_otp_based_method'  : jQuery('input[name=mo2f_otp_based_method]').val(),
				'mo2f_phone_email_telegram'  : jQuery('input[name=mo2f_phone_email_telegram]').val(),
				'mo2f_session_id'  : jQuery('input[name=mo2f_session_id]').val(),
				'nonce'  : nonce,	
			};
			jQuery.post(ajaxurl, data, function(response) {
				if( response['success'] ){
					jQuery('#go_back_verify').css('display','none');
					jQuery('#mo2f_validateotp_form').css('display','block');
					jQuery("input[name=otp_token]").focus();
					success_msg(response['data'] );
				}else if( ! response['success'] ){
					error_msg( response['data']);
				}else{
					error_msg('Unknown error occured. Please try again!');
				}
			});
		});
		jQuery("#validate").click(function()
		{   
			var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>';
			var data = {
				'action'  : 'mo_two_factor_ajax',
				'mo_2f_two_factor_ajax'  : 'mo2f_configure_otp_based_methods_validate',
				'mo2f_otp_based_method'  : jQuery('input[name=mo2f_otp_based_method]').val(),
				'otp_token'  : jQuery('input[name=otp_token]').val(),
				'mo2f_session_id'  : jQuery('input[name=mo2f_session_id]').val(),
				'nonce'  : nonce,	
			};
			jQuery.post(ajaxurl, data, function(response) {
				if( response['success'] ){
					jQuery('#mo2f_2factor_test_prompt_cross').submit();
				}else if( ! response['success'] ){
					error_msg( response['data']);
				}else{
					error_msg('Unknown error occured. Please try again!');
				}
			});
		});

		jQuery("#phone").intlTelInput();
		jQuery('#go_back').click(function () {
			jQuery('#mo2f_go_back_form').submit();
		});
		jQuery('#go_back_verify').click(function () {
			jQuery('#mo2f_go_back_form').submit();
		});

		jQuery('a[href=\"#resendsmslink\"]').click(function (e) {
			jQuery('#verify').click();
		});
		jQuery("input[name=mo2f_phone_email_telegram]").keypress(function(e) {
			if (e.which === 13) {
				e.preventDefault();
				jQuery("#verify").click();
				jQuery("input[name=otp_token]").focus();
			}

		});
		jQuery("input[name=otp_token]").keypress(function(e) {
			if (e.which === 13) {
				e.preventDefault();
				jQuery("#validate").click();
			}

		});
</script>

