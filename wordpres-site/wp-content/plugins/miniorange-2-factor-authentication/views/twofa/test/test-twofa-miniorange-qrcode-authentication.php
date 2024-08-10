<?php
/**
 * This file contains the Test miniOrange QR code Authentication.
 *
 * @package miniorange-2-factor-authentication/views/twofa/test
 */

// Needed in both.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Test miniOrange QR code authentication.
 *
 * @param object $user User Object.
 * @return void
 */
function mo2f_test_miniorange_qr_code_authentication( $user ) {
	?>
		<h3><?php esc_html_e( 'Test QR Code Authentication', 'miniorange-2-factor-authentication' ); ?></h3>
		<hr>
	<p><?php esc_html_e( 'Open your miniOrange', 'miniorange-2-factor-authentication' ); ?>
		<b><?php esc_html_e( 'Authenticator App', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( 'and click on', 'miniorange-2-factor-authentication' ); ?>
		<b><?php esc_html_e( 'SCAN QR Code', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( 'to scan the QR code. Your phone should have internet connectivity to scan QR code.', 'miniorange-2-factor-authentication' ); ?>
	</p>

	<div style="color:indianred;">
		<b><?php esc_html_e( 'I am not able to scan the QR code,', 'miniorange-2-factor-authentication' ); ?> <a
					data-toggle="collapse" href="#mo2f_testscanqrcode"
					aria-expanded="false"><?php esc_html_e( 'click here ', 'miniorange-2-factor-authentication' ); ?></a></b>
	</div>
	<div class="mo2f_collapse" id="mo2f_testscanqrcode">
		<br><?php esc_html_e( 'Follow these instructions below and try again.', 'miniorange-2-factor-authentication' ); ?>
		<ol>
			<li><?php esc_html_e( 'Make sure your desktop screen has enough brightness.', 'miniorange-2-factor-authentication' ); ?></li>
			<li><?php esc_html_e( 'Open your app and click on Green button (your registered email is displayed on the button) to scan QR Code.', 'miniorange-2-factor-authentication' ); ?></li>
			<li><?php esc_html_e( 'If you get cross mark on QR Code then click on \'Back\' button and again click on \'Test\' link.', 'miniorange-2-factor-authentication' ); ?></li>
		</ol>
	</div>
	<br>
	<table class="mo2f_settings_table">
		<div id="qr-success"></div>
		<div id="displayQrCode" >
			<br><?php echo '<img style="width:165px;" src="data:image/jpg;base64,' . esc_html( get_user_meta( $user->ID, 'mo2f_qrCode', true ) ) . '" />'; ?>
		</div>

	</table>

	<div id="mobile_registered">
		<?php
		$auth_status = array( 'success', 'error' );
		foreach ( $auth_status as $status ) {
			?>
		<form name="f" method="post" id="mo2f_mobile_authenticate_'<?php echo esc_attr( $status ); ?>'_form" action="">
			<input type="hidden" name="option" value="mo2f_mobile_authenticate_'<?php echo esc_attr( $status ); ?>"/>
			<input type="hidden" name="mo2f_mobile_authenticate_'<?php echo esc_attr( $status ); ?>'_nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'mo2f-mobile-authenticate-' . $status . '-nonce' ) ); ?>"/>
		</form>
			<?php
		}
		?>
			<form name="f" method="post" action="" id="mo2f_go_back_form">
				<input type="hidden" name="option" value="mo2f_go_back"/>
				<input type="hidden" name="mo2f_go_back_nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
				<input type="submit" name="validate" id="validate" class="button button-primary button-large"
					value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>"/>
			</form>
				<form name="f" method="post" action="" id="mo2f_2factor_test_prompt_cross">
				<input type="hidden" name="option" value="mo2f_2factor_test_prompt_cross"/>
				<input type="hidden" name="mo2f_2factor_test_prompt_cross_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-2factor-test-prompt-cross-nonce' ) ); ?>"/>
			</form>
	</div>


	<script>
		var timeout;
		pollMobileValidation();

		function pollMobileValidation() {
			var transId = "<?php echo esc_js( get_user_meta( $user->ID, 'mo2f_transactionId', true ) ); ?>";
			var jsonString = "{\"txId\":\"" + transId + "\"}";
			var postUrl = "<?php echo esc_url( MO_HOST_NAME ); ?>" + "/moas/api/auth/auth-status";

			jQuery.ajax({
				url: postUrl,
				type: "POST",
				dataType: "json",
				data: jsonString,
				contentType: "application/json; charset=utf-8",
				success: function (result) {
					var status = JSON.parse(JSON.stringify(result)).status;
					if (status == 'SUCCESS') {
						var content = "<br><div id='success'><img style='width:165px;margin-top:-1%;margin-left:2%;' src='" + "<?php echo esc_url( plugins_url( 'includes/images/right.png', dirname( dirname( dirname( __FILE__ ) ) ) ) ); ?>" + "' /></div>";
						jQuery("#displayQrCode").empty();
						jQuery("#displayQrCode").append(content);
						success_msg("QR code authentication has been tested successfully.");
						setTimeout(function () {
							jQuery('#mo2f_go_back_form').submit();
						}, 1000);
					} else if (status == 'ERROR' || status == 'FAILED') {
						var content = "<br><div id='error'><img style='width:165px;margin-top:-1%;margin-left:2%;' src='" + "<?php echo esc_url( plugins_url( 'includes/images/wrong.png', dirname( dirname( dirname( __FILE__ ) ) ) ) ); ?>" + "' /></div>";
						jQuery("#displayQrCode").empty();
						jQuery("#displayQrCode").append(content);
						setTimeout(function () {
							jQuery('#mo2f_mobile_authenticate_error_form').submit();
						}, 1000);
					} else {
						timeout = setTimeout(pollMobileValidation, 3000);
					}
				}
			});
		}

		jQuery('html,body').animate({scrollTop: jQuery(document).height()}, 600);
	</script>
	<?php
} ?>
