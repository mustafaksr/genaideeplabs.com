<?php
/**
 * This file contains the html UI for the miniOrange account login page in the plugin.
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '	<form name="f" method="post" action="">
			<input type="hidden" name="option" value="mo_wpns_verify_customer" />
			<input type="hidden" name="mo2f_general_nonce" value=" ' . esc_attr( wp_create_nonce( 'miniOrange_2fa_nonce' ) ) . ' " />
			<div class="mo2f_table_layout">
				<div>
					<h3>Login with miniOrange
						<div style="float: right;">';
				echo '</div>
					</h3>
					<p><b>It seems you already have an account with miniOrange. Please enter your miniOrange email and password.</td><a target="_blank" href="' . esc_url( MO_HOST_NAME ) . '/moas/idp/resetpassword"> Click here if you forgot your password?</a></b></p>
					<table class="mo_wpns_settings_table">
						<tr>
							<td><b><span class="mo2fa_font-color-astrisk">*</span>Email:</b></td>
							<td><input class="mo_wpns_table_textbox" type="email" name="email"
								required placeholder="person@example.com"
								value="' . esc_attr( $admin_email ) . '" /></td>
						</tr>
						<tr>
							<td><b><span class="mo2fa_font-color-astrisk">*</span>Password:</b></td>
							<td><input class="mo_wpns_table_textbox mo2f_input_password" required type="password"
								name="password" placeholder="Enter your miniOrange password" /></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><input type="submit" class="button button-primary button-large" />
								<a href="#cancel_link" class="button button-primary button-large">Go Back to Registration</a>
						</tr>
					</table>
				</div>
			</div>
		</form>
		<form id="cancel_form" method="post" action="">
			<input type="hidden" name="option" value="mo_wpns_cancel" />
			<input type="hidden" name="mo2f_general_nonce" value=" ' . esc_attr( wp_create_nonce( 'miniOrange_2fa_nonce' ) ) . ' " />
		</form>
		<script>
			jQuery(document).ready(function(){
				$(\'a[href="#cancel_link"]\').click(function(){
					$("#cancel_form").submit();
				});		
			});
		</script>';

