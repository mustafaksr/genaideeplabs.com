<?php
/**
 * This file shows frontend to test DUO authenticator method.
 *
 * @package miniorange-2-factor-authentication/views/twofa/test
 */

// Needed in both.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Function to show frontend to test DUO authenticator.
 *
 * @return void
 */
function mo2f_test_duo_authenticator() { ?>
	<h3><?php esc_html_e( 'Test Duo Authenticator', 'miniorange-2-factor-authentication' ); ?></h3>
	<hr>
	<div>
		<br>
		<br>
		<div class="mo2f_align_center">
			<h3><?php esc_html_e( 'Duo push notification is sent to your mobile phone.', 'miniorange-2-factor-authentication' ); ?>
				<br>
				<?php esc_html_e( 'We are waiting for your approval...', 'miniorange-2-factor-authentication' ); ?></h3>
			<img src="<?php echo esc_url( plugins_url( 'includes/images/ajax-loader-login.gif', dirname( dirname( dirname( __FILE__ ) ) ) ) ); ?>"/>
		</div>

		<input type="button" name="back" id="go_back" class="button button-primary button-large"
			value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>"
			style="margin-top:100px;margin-left:10px;"/>
	</div>

	<form name="f" method="post" action="" id="mo2f_go_back_form">
		<input type="hidden" name="option" value="mo2f_go_back"/>
		<input type="hidden" name="mo2f_go_back_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
	</form>
	<form name="f" method="post" id="mo2f_duo_authenticator_success_form" action="">
		<input type="hidden" name="option" value="mo2f_duo_authenticator_success_form"/>
		<input type="hidden" name="mo2f_duo_authenticator_success_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'mo2f-duo-authenticator-success-nonce' ) ); ?>"/>
	</form>
	<form name="f" method="post" id="mo2f_duo_authenticator_error_form" action="">
		<input type="hidden" name="option" value="mo2f_duo_authenticator_error"/>

		<input type="hidden" name="mo2f_duo_authentcator_error_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'mo2f-duo-authenticator-error-nonce' ) ); ?>"/>
	</form>

	<script>
		jQuery('#go_back').click(function () {
			jQuery('#mo2f_go_back_form').submit();
		});

		var timeout;



			pollMobileValidation();
			function pollMobileValidation() {
				var nonce = "<?php echo esc_js( wp_create_nonce( 'miniorange-2-factor-duo-nonce' ) ); ?>";
				var data={
				'action':'mo2f_duo_authenticator_ajax',
				'call_type':'check_duo_push_auth_status',
				'nonce' : nonce,				
			}; 

			jQuery.post(ajaxurl, data, function(response){						
						if (response == 'SUCCESS') {
							jQuery('#mo2f_duo_authenticator_success_form').submit();
						} else if (response == 'ERROR' || response == 'FAILED' || response == 'DENIED') {
							jQuery('#mo2f_duo_authenticator_error_form').submit();
						} else {
							timeout = setTimeout(pollMobileValidation, 3000);
						}					
				});			
			}

	</script>

<?php }

?>
