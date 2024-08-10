<?php
/**
 * This file contains the html UI for the miniOrange account registration.
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '<!--Register with miniOrange-->
	<form name="f" method="post" action="">
		<input type="hidden" name="option" value="mo_wpns_register_customer" />
		<input type="hidden" name="mo2f_general_nonce" value=" ' . esc_attr( wp_create_nonce( 'miniOrange_2fa_nonce' ) ) . ' " />
		<div class="mo2f_table_layout">
		<div style="margin-bottom:30px;">
			
				<h3>Register with miniOrange
					<div style="float: right;">';
					echo '</div>
				</h3>
				<p>Just complete the short registration below to configure miniOrange 2-Factor plugin. Please enter a valid email id that you have access to. You will be able to move forward after verifying an OTP that we will send to this email.</p>
				<table class="mo_wpns_settings_table">
					<tr>
						<td><b><span class="mo2fa_font-color-astrisk">*</span>Email:</b></td>
						<td><input class="mo_wpns_table_textbox" type="email" name="email"
							required placeholder="person@example.com"
							value="' . esc_attr( $user->user_email ) . '" /></td>
					</tr>

					<tr>
						<td><b><span class="mo2fa_font-color-astrisk">*</span>Password:</b></td>
						<td><input class="mo_wpns_table_textbox" required type="password"
							name="password" placeholder="Choose your password (Min. length 6)" /></td>
					</tr>
					<tr>
						<td><b><span class="mo2fa_font-color-astrisk">*</span>Confirm Password:</b></td>
						<td><input class="mo_wpns_table_textbox" required type="password"
							name="confirmPassword" placeholder="Confirm your password" /></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td><br><input type="submit" name="submit" value="Create Account"
							class="button button-primary button-large" />
						
							<a href="#mo2f_account_exist" class="button button-primary button-large">Already have an account?</a>
					</tr>
				</table>
		</div>	
		</div>
	</form>
	 <form name="f" method="post" action="" class="mo2f_verify_customerform">
        <input type="hidden" name="option" value="mo2f_goto_verifycustomer">
		<input type="hidden" name="mo2f_general_nonce" value=" ' . esc_attr( wp_create_nonce( 'miniOrange_2fa_nonce' ) ) . ' " />
       </form>';
?>

	<script>
		jQuery('a[href=\"#mo2f_account_exist\"]').click(function (e) {
			jQuery('.mo2f_verify_customerform').submit();
		});
	</script>
