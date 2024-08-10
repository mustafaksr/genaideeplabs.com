<?php
/**
 * This file contains frontend to show setup wizard to configure Google Authenticator.
 *
 * @package miniorange-2-factor-authentication/views/twofa/setup
 */

// Needed in onpremise.

use TwoFA\Onprem\Google_Auth_Onpremise;
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Helper\MoWpnsConstants;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Function to show setup wizard to configure Google Authenticator.
 *
 * @param string $secret Secret key.
 * @param string $url URL to show QR code.
 * @param string $otpcode 6-8 digit string.
 * @param string $session_id_encrypt Encrypted session id.
 * @return void
 */
function mo2f_configure_google_authenticator_setupwizard( $secret, $url, $otpcode, $session_id_encrypt ) {
	?>
	<div><h4> <?php esc_html_e( '1. Please scan the QR code below in your Authenticator app on your mobile', 'miniorange-2-factor-authentication' ); ?></h4></div>
	<div class="mo_qr_code_margin">
		<div class="mo2f_gauth" style="background: white;" data-qrcode="<?php echo esc_attr( $url ); ?>" ></div>
	</div>				

	<div id="mo2f_entergoogle_auth_code">			
		<h4><?php esc_html_e( '2. Enter the code generated in your Authenticator app:', 'miniorange-2-factor-authentication' ); ?> <input class ='mo_input_text_box_size' type="text" id="mo2f_google_auth_code" name="mo2f_google_auth_code" placeholder="Enter OTP" /> </h4></b>
		<input type="hidden" name="mo2f_session_id" id="mo2f_session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>">						
	</div>
	<div style="margin: 2%;"><a data-toggle="collapse" href="#mo2f_scanbarcode_a"
			aria-expanded="false"><b><?php esc_html_e( 'Can\'t scan the barcode? ', 'miniorange-2-factor-authentication' ); ?></b></a>
	</div>
	<div class="mo2f_collapse"  id="mo2f_scanbarcode_a" style="background: white; display: none;">
		<ol class="mo2f_ol">
			<li><?php esc_html_e( 'Tap on Menu and select', 'miniorange-2-factor-authentication' ); ?>
				<b> <?php esc_html_e( ' Set up account ', 'miniorange-2-factor-authentication' ); ?></b>.
			</li>
			<li><?php esc_html_e( 'Select', 'miniorange-2-factor-authentication' ); ?>
				<b> <?php esc_html_e( ' Enter provided key ', 'miniorange-2-factor-authentication' ); ?></b>.
			</li>
			<li><?php esc_html_e( 'For the', 'miniorange-2-factor-authentication' ); ?>
				<b> <?php esc_html_e( ' Enter account name ', 'miniorange-2-factor-authentication' ); ?></b>
				<?php esc_html_e( 'field, type your preferred account name', 'miniorange-2-factor-authentication' ); ?>.
			</li>
			<li><?php esc_html_e( 'For the', 'miniorange-2-factor-authentication' ); ?>
				<b> <?php esc_html_e( ' Enter your key ', 'miniorange-2-factor-authentication' ); ?></b>
				<?php esc_html_e( 'field, type the below secret key', 'miniorange-2-factor-authentication' ); ?>:
			</li>

			<div class="mo2f_google_authy_secret_outer_div">
				<div class="mo2f_google_authy_secret_inner_div">
					<?php echo esc_html( $secret ); ?>
				</div>
				<div class="mo2f_google_authy_secret">
					<?php esc_html_e( 'Spaces do not matter', 'miniorange-2-factor-authentication' ); ?>.
				</div>
			</div>
			<li><?php esc_html_e( 'Key type: make sure', 'miniorange-2-factor-authentication' ); ?>
				<b> <?php esc_html_e( ' Time-based ', 'miniorange-2-factor-authentication' ); ?></b>
				<?php esc_html_e( ' is selected', 'miniorange-2-factor-authentication' ); ?>.
			</li>

			<li><?php esc_html_e( 'Tap Add.', 'miniorange-2-factor-authentication' ); ?></li>
		</ol>
	</div>
	<script type="text/javascript">
		jQuery('a[href="#mo2f_scanbarcode_a"]').click(function(e){
			jQuery("#mo2f_scanbarcode_a").slideToggle();
		});
		jQuery(document).ready(function() {
			jQuery('.mo2f_gauth').qrcode({
				'render': 'image',
				size: 175,
				'text': jQuery('.mo2f_gauth').data('qrcode')
			});
		});			
	</script>
	<?php
}
/**
 * On-premise flow for configuring Google Authenticator.
 *
 * @param string $secret Secret key.
 * @param string $url URL to show QR code.
 * @param string $session_id_encrypt Encrypted session ID.
 * @return void
 */
function mo2f_configure_google_authenticator_onprem( $secret, $url, $session_id_encrypt ) {
	global $main_dir;
	$gauth_name    = get_option( 'mo2f_google_appname' );
	$gauth_name    = $gauth_name ? preg_replace( '#^https?://#i', '', $gauth_name ) : DEFAULT_GOOGLE_APPNAME;
	$gauth_obj     = new Google_auth_onpremise();
	$microsoft_url = $gauth_obj->mo2f_geturl( $secret, $gauth_name, '' );
	MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'secret_ga', $secret );

	$qr_code = '<div class="mo2f_gauth" id= "mo2f_google_auth_qr_code" style="background: white;" data-qrcode="' . $url . '" ></div>';

	$dashboard_back_button = '<form name="mo2f_go_to_setup_2FA_form" method="post" action="" id="mo2f_go_back_form">
	<input type="hidden" name="option" value="mo2f_go_back"/>
	<input type="hidden" name="mo2f_go_back_nonce" value="' . wp_create_nonce( 'mo2f-go-back-nonce' ) . '"/>
	<input type="submit" name="back" id="go_back" class="button button-primary button-large" value="' . esc_attr__( 'Back', 'miniorange-2-factor-authentication' ) . '"/>
</form>';

	mo2f_configure_google_auth_common_view( $secret, $gauth_name, $qr_code, $url, $microsoft_url, $session_id_encrypt, null, false, $dashboard_back_button );
	wp_enqueue_script( 'mo2f_google_auth_dashboard', $main_dir . 'includes/js/google-authenticator-dashboard.min.js', array(), MO2F_VERSION, false );

}
?>
