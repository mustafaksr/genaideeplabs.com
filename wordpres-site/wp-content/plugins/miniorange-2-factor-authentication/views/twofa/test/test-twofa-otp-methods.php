<?php
/**
 * Description: File contains function to test otp over telegram
 *
 * @package miniorange-2-factor-authentication/views/twofa/test.
 */

 use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Description: Function displays form screen to test otp over telegram method.
 *
 * @param array $mo2f_method_components mo2f method components.
 * @return void
 */
function mo2f_test_otp_methods( $mo2f_method_components ) {
	?>
	<h3>
	<?php
	printf(
				/* translators: %s: Name of the method */
		esc_html__( 'Test %s', 'miniorange-2-factor-authentication' ),
		esc_html( MoWpnsConstants::mo2f_convert_method_name( $mo2f_method_components['selected_2fa_method'], 'cap_to_small' ) )
	);
	?>
		<hr>
	</h3>
	<p>
	<?php
	printf(
				/* translators: %s: Instructions */
		esc_html__( 'Enter the %s', 'miniorange-2-factor-authentication' ),
		esc_html( $mo2f_method_components['test_method_instructions'] )
	);
	?>
			</p>


	<form name="f" method="post" action="" id="mo2f_test_token_form">
		<input type="hidden" name="option" value="<?php echo esc_attr( $mo2f_method_components['option_name'] ); ?>"/>
		<input type="hidden" name="mo2f_test_validate_otp_nonce"
						value="<?php echo esc_attr( wp_create_nonce( $mo2f_method_components['nonce_name'] ) ); ?>"/>
		<input class="mo2f_table_textbox" style="width:200px;" autofocus="true" type="text" name="otp_token" required
						placeholder="<?php esc_attr_e( 'Enter OTP', 'miniorange-2-factor-authentication' ); ?>" style="width:95%;"/>
		<?php if ( 'mo2f_validate_google_authy_test' !== $mo2f_method_components['option_name'] && 'mo2f_validate_soft_token' !== $mo2f_method_components['option_name'] ) { ?>
			<a href="#resendotplink"><?php esc_html_e( 'Resend OTP?', 'miniorange-2-factor-authentication' ); ?></a>
		<?php } ?>
		<br><br>
		<input type="button" name="back" id="go_back" class="button button-primary button-large"
			value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>"/>
		<input type="submit" name="validate" id="validate" class="button button-primary button-large"
			value="<?php esc_attr_e( 'Validate OTP', 'miniorange-2-factor-authentication' ); ?>"/>

	</form>
	<form name="f" method="post" action="" id="mo2f_go_back_form">
		<input type="hidden" name="option" value="mo2f_go_back"/>
		<input type="hidden" name="mo2f_go_back_nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
	</form>

	<form name="f" method="post" action="" id="mo2f_2factor_test_authentication_method_form">
		<input type="hidden" name="option" value="mo_2factor_test_authentication_method"/>
		<input type="hidden" name="mo_2factor_test_authentication_method_nonce"
							value="<?php echo esc_attr( wp_create_nonce( 'mo-2factor-test-authentication-method-nonce' ) ); ?>"/>
		<input type="hidden" name="mo2f_configured_2FA_method_test" id="mo2f_configured_2FA_method_test"
			value="<?php echo esc_attr( $mo2f_method_components['selected_2fa_method'] ); ?>"/>
	</form>


		<script>
			jQuery('#go_back').click(function () {
				jQuery('#mo2f_go_back_form').submit();
			});
			jQuery('a[href=\"#resendotplink\"]').click(function (e) {
				jQuery('#mo2f_2factor_test_authentication_method_form').submit();
			});
		</script>

<?php } ?>
