<?php
/**
 * This file shows Test Email verification frontend.
 *
 * @package miniorange-2-factor-authentication/views/twofa/test
 */

// Needed patially in both. Can bifurcate the below function in subfuctions.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Function to show Test Email verification frontend.
 *
 * @param object $user User object.
 * @return void
 */
function mo2f_test_email_verification( $user = null ) {
	$mo2f_dir_name = dirname( __FILE__ );
	$mo2f_dir_name = explode( 'wp-content', $mo2f_dir_name );
	$mo2f_dir_name = explode( 'views', $mo2f_dir_name[1] );
	?>

	<h3><?php esc_html_e( 'Test Email Verification', 'miniorange-2-factor-authentication' ); ?></h3>
	<hr>
	<div>
		<br>
		<br>
		<div class="mo2f_align_center">
			<h3><?php esc_html_e( 'A verification email is sent to your registered email.', 'miniorange-2-factor-authentication' ); ?>
				<br>
				<?php esc_html_e( 'We are waiting for your approval...', 'miniorange-2-factor-authentication' ); ?></h3>
		</div>
		<div class="mo2f_align_center">
			<img src="<?php echo esc_url( plugins_url( 'includes/images/ajax-loader-login.gif', dirname( dirname( dirname( __FILE__ ) ) ) ) ); ?>"/>
		</div>
		<div class="mo2f_align_center">
			<input type="button" name="back" id="go_back" class="button button-primary button-large"
				value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>"
				style="margin-top:100px;margin-left:10px;"/>
		</div>
	</div>

	<form name="f" method="post" action="" id="mo2f_go_back_form">
		<input type="hidden" name="option" value="mo2f_go_back"/>
		<input type="hidden" name="mo2f_go_back_nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
	</form>
	<form name="f" method="post" id="mo2f_out_of_band_success_form" action="">
		<input type="hidden" name="option" value="mo2f_out_of_band_success"/>
		<input type="hidden" name="mo2f_out_of_band_success_nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'mo2f-out-of-band-success-nonce' ) ); ?>"/>
		<input type="hidden" name="TxidEmail" value="<?php echo esc_attr( get_user_meta( $user->ID, 'mo2f_transactionId', true ) ); ?>"/>
	</form>
	<form name="f" method="post" id="mo2f_out_of_band_error_form" action="">
		<input type="hidden" name="option" value="mo2f_out_of_band_error"/>		
		<input type="hidden" name="mo2f_out_of_band_error_nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'mo2f-out-of-band-error-nonce' ) ); ?>"/>
	</form>

	<script type="text/javascript">
		jQuery('#go_back').click(function () {
			jQuery('#mo2f_go_back_form').submit();
		});
	</script>
	<?php

	if ( MO2F_IS_ONPREM ) {

		$txid = get_user_meta( $user->ID, 'mo2f_transactionId', true );

		?>
	<script type="text/javascript">
		var timeout;
		pollMobileValidation();
		function pollMobileValidation() {
			var txid = '<?php echo esc_js( $txid ); ?>';
			var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>' 
			var data = {
				'action'                    : 'mo_two_factor_ajax',
				'nonce'						: nonce,
				'mo_2f_two_factor_ajax'     : 'CheckEVStatus', 
				'txId'                      : txid
			};
			jQuery.post(ajaxurl, data, function(response) {
				if (response.data == '1') {
					jQuery('#mo2f_out_of_band_success_form').submit();
				} else if (response.data == 'ERROR' || response.data == 'FAILED' || response.data == 'DENIED' || response.data =='0') {
					jQuery('#mo2f_out_of_band_error_form').submit();
				} else {
					timeout = setTimeout(pollMobileValidation, 1000);
				}
			});			
		}

</script>
		<?php
	} else {
		$mo2f_transaction_id = get_user_meta( $user->ID, 'mo2f_transactionId', true );

		?>
		<script type="text/javascript">
		var timeout;
		pollMobileValidation();
		function pollMobileValidation() {
			var transId = "<?php echo esc_js( $mo2f_transaction_id ); ?>";
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
						jQuery('#mo2f_out_of_band_success_form').submit();
					} else if (status == 'ERROR' || status == 'FAILED' || status == 'DENIED') {
						jQuery('#mo2f_out_of_band_error_form').submit();
					} else {
						timeout = setTimeout(pollMobileValidation, 3000);
					}
				}
			});
		}
		</script>

<?php }
}

?>
