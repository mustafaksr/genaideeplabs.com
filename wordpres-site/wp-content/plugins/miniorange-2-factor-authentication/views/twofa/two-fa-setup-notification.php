<?php
/**
 * This file is used to show notifiction in the plugin.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

// Needed in both.

use TwoFA\Onprem\Google_auth_onpremise;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The method is used to display notification in the plugin .
 *
 * @param object $user used to get customer email and id.
 * @return void
 */
function mo2f_display_test_2fa_notification( $user ) {

	global $mo2fdb_queries, $mo2f_dir_name;
	$mo2f_configured_2_f_a_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );

	if ( MO2F_IS_ONPREM ) {
		if ( MoWpnsConstants::GOOGLE_AUTHENTICATOR === $mo2f_configured_2_f_a_method ) {
			$current_time_slice = floor( time() / 30 );
			$code_array         = array();
			include_once $mo2f_dir_name . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			$gauth_obj = new Google_auth_onpremise();
			$secret    = $gauth_obj->mo_a_auth_get_secret( $user->ID );
			for ( $i = -3; $i <= 3; ++$i ) {
				$calculated_code = $gauth_obj->mo2f_get_code( $secret, $current_time_slice + $i );
				array_push( $code_array, $calculated_code );
			}
		}
	}

	wp_print_scripts( 'jquery' );
	?>
	<div id="twoFAtestAlertModal" class="modal" role="dialog">
		<div class="mo2f_modal-dialog">
			<div class="modal-content" style="width:660px !important;">
			<div class="mo2fa_text-align-center">
				<div class="modal-header">
					<h2 class="mo2f_modal-title" style="color: #2271b1;">2FA Setup Successful.</h2>
					<span type="button" id="test-methods" class="modal-span-close" data-dismiss="modal">&times;</span>
				</div>
				<div class="mo2f_modal-body">
					<p style="font-size:14px;"><b><?php echo esc_attr( MoWpnsConstants::mo2f_convert_method_name( $mo2f_configured_2_f_a_method, 'cap_to_small' ) ); ?> </b> has been set as your 2-factor authentication method.
					<br>
					<?php if ( MoWpnsConstants::GOOGLE_AUTHENTICATOR === $mo2f_configured_2_f_a_method && MO2F_IS_ONPREM ) { ?>
						<p><b>Current valid OTPs for Google Authenticator</b></p>
						<table cellspacing="10" style="display: flex;align-items: center;justify-content: center;">
							<tr><td><?php echo esc_html( $code_array[0] ); ?></td><td><?php echo esc_html( $code_array[1] ); ?></td><td><?php echo esc_html( $code_array[2] ); ?></td><td><?php echo esc_html( $code_array[3] ); ?></td><td><?php echo esc_html( $code_array[4] ); ?></td></tr>
						</table>
					<?php } ?>
					<br>Please test the login flow once with 2nd factor in another browser or in an incognito window of the same browser to ensure you don't get locked out of your site.</p>
				</div>
				<div class="mo2f_modal-footer">
					<button type="button" id="test-methods-button" class="button button-primary button-large" data-dismiss="modal">Test it!</button>
				</div>
					<br>
					</div>
			</div>
		</div>
	</div>

	<script>
		jQuery('#twoFAtestAlertModal').css('display', 'block');
		jQuery('#test-methods').click(function(){
			jQuery('#twoFAtestAlertModal').css('display', 'none');
		});
		jQuery('#test-methods-button').click(function(){
			jQuery('#twoFAtestAlertModal').css('display', 'none');
			testAuthenticationMethod('<?php echo esc_js( $mo2f_configured_2_f_a_method ); ?>');
		});
	</script>
<?php }
?>
