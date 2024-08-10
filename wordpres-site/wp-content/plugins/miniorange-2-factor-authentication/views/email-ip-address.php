<?php
/**
 * This file contains email template to be sent during login from new IP.
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.

use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Email template to be send for login from new IP.
 *
 * @return string
 */
function mo_i_p_template() {
	global $mo_wpns_utility,$image_path;
	$ip_address = $mo_wpns_utility->get_client_ip();
	$ip_address = sanitize_text_field( $ip_address );
	$result     = wp_remote_get( 'http://www.geoplugin.net/json.gp?ip=' . $ip_address );

	$mo2f_city_name = '-';
	$mo2f_country   = '-';

	if ( ! is_wp_error( $result ) ) {
		try {
			$result         = wp_remote_retrieve_body( $result );
			$mo2f_city_name = isset( $result['geoplugin_city'] ) ? sanitize_text_field( $result['geoplugin_city'] ) : '-';
			$mo2f_country   = isset( $result['geoplugin_countryName'] ) ? sanitize_text_field( $result['geoplugin_countryName'] ) : '-';
		} catch ( Exception $e ) {
			$mo2f_city_name = 'Unable to fetch city name';
			$mo2f_country   = 'Unable to fetch country name';
		}
	}
	$hostname = get_site_url();
	$t        = gmdate( 'Y-m-d' );
	return '<!DOCTYPE html>
<html>
<head>

	<title></title>
</head>
<body style=background-color:#f6f4f4>
<style>
	.mo_2fa_description
	{

		/*min-height: 400px;*/
		/*width: 29%;*/
		margin: 3%;
		/*float: left;*/
		text-align: left;
		color: black;
		padding: 19px 12px;
		margin-top: -9px;
		width :91%;
		margin-left:3%;
		font-size:19px;
		border: 4px solid #2271b1;

	}
	.mo_2fa_feature
	{
		width: 78%;
		/*margin: 2%;*/
		float: left;
		background-color: white;
		/*border: 1px solid gray;*/
		min-height: 400px;	
    	overflow: hidden;
	}
	.mo_2fa_email_template_details
	{
		width: 40%;
		margin: 1%;
		float: left;
		background-color: white;
		border-top: 5px solid #2271b1;
		min-height: 320px;
		text-align: center;
		overflow: hidden;
		margin-top:47px;
		font-size:23px;
	}
	.mo_2fa_email_template_details:hover
	{
		box-shadow: 0 0px 0px 0 #9894f6, 0 6px 10px 0 #837fea;
		border-top: 4px solid black;
    	margin-top: -0.5%;
	}
	.mo_2fa_email_feature_details
	{
		width: 29%;
		margin: 2.16%;
		margin-bottom: 5%;
		float: left;
		background-color: #FF4500;
		text-align: center;
		min-height: 250px;
		overflow: hidden;
		color: #100505;
    	font-family: cursive;
    	border-radius: 15px;
		box-shadow: 0 0px 0px 0 #b5b2f6, 0 6px 10px 0 #bcbaf4;

	}
	.mo_2fa_email_feature_details:hover
	{
		color: #110d8b;
		box-shadow: 0 0px 0px 0 #9894f6, 0 6px 10px 0 #837fea;
	}
	.mo_2fa_ef_button:hover
	{
		box-shadow: 0 0px 0px 0 #ffa792, 0 6px 10px 0 #cb8473;
	}
	.mo_2fa_feature_block
	{
		/*width: 91%;*/
	    margin-left: 3%;
	    display: flex;
	    color:white;
	}
	.mo_2fa_ef_h2
	{
		color: #ad2100;
		font-family: cursive;
	}
	.mo_2fa_ef_h1
	{
		color: #100505;
		

	}
	.mo_2fa_ef_button
	{
		font-size: x-large;
	    background-color:#2271b1;
	    color: white;
	    padding: 17px 127px;
	    font-family: cursive;
	    margin-left: -42px;
	}
	.mo_2fa_ef_read_more
	{
		color: #2271b1;
    	border: 2px solid #2271b1;
	    padding: 17px 27px;
	    font-family: cursive;
	}
	.mo_2fa_ef_read_more:hover
	{
		
		/*font-size: x-large;*/
	    background-color: #2271b1;
	    color: white;
	    border: 1px solid white;
	    padding: 17px 27px;
	    font-family: cursive;
	}
    .mo_2fa_ef_hr
    {
		border: 2px solid #100505;
	    margin: 0px 7%;
    }
    .myDiv {
 
  		/*min-height: 300px;*/
		background-color: #18272a;
		/*width: 29%;*/
		/*float: left;*/
		text-align: center;
		color: white;
		padding: 2px 2px;
		font-size:18px;
		margin-top:14px;
}
</style>
<div style="border: 2px solid black;">
<div style="text-align: center;"><img src="' . $image_path . 'includes/images/40290_shield.png" alt="miniOrange 2FA" width="350" height="175"></div>
			<div class="mo_2fa_description"><div style="text-align: center;"><h2> Dear Customer</h2></div>
				<h2>A new login to your account has been made from this IP Address ' . esc_attr( $ip_address ) . '.  If you recently logged in and recognize the logged in location,you may disregard this email.  If you did not recently log in, you should immediately 	change your password . Passwords should be unique and not used for any other sites or services.If not MFA enabled To further protect your account, consider configuring a multi-factor authentication method <a style="color: #000080"href="' . esc_url( MoWpnsConstants::MO2F_PLUGINS_PAGE_URL ) . '"/2-factor-authentication-for-wordpress">See 2FA methods</a>.
				</h2>
			</div>

 			<div>
 			<div style="text-align: center;"><h2 style="color: black; font-size:40px"> Your Account Sign in with New Location </h2></div>
 			<div style="text-align: center;"> <table style="display: flex;align-items: center;justify-content: center;color:blue"> 
 						<tr>
 								<td><h2> IP ADDRESS </h2></td>
 								<td><h2>::   ' . esc_attr( $ip_address ) . ' </h2></td>
 						</tr>
 						<tr>
 								<td><h2> WEBSITE   </h2></td>
 								<td><h2>::   ' . esc_attr( $hostname ) . ' </h2></td>
 						</tr>
 						<tr>
 								<td><h2>LOGIN DATE  </h2> </td>
 								<td><h2>::   ' . esc_attr( $t ) . '</h2> </td>
 						</tr>
 						<tr>
 								<td><h2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;LOGIN LOCATION</h2> </td>
 								<td><h2>::    ' . esc_attr( $mo2f_city_name ) . '/' . esc_attr( $mo2f_country ) . '</h2> </td>
 			
 						</tr>
 						</table>
 			</div>
 			</div>

			<div>
					<br><br>
			<div style="text-align: center;"><a class="mo_2fa_ef_button" href="' . esc_url( MoWpnsConstants::MO2F_PLUGINS_PAGE_URL ) . '"/2-factor-authentication-for-wordpress">Feature Details</a></div>
			</div>
	
			<div class="mo_2fa_feature_block" style="margin-left: 14%;">
				<div class="mo_2fa_email_template_details">
						<i  class="dashicons dashicons-admin-site" style="font-size:50px;color: black;margin-top: 6%"></i>
						<div style="min-height: 150px;">
							<h2 style="color: black;">Website</h2>
							<p style="color: black;padding: 0px 27px;text-align: justify;">miniOrange provides easy to use 2-factor authentication for secure login to your WordPress site.</p>
						</div>
						<div>
								<br><br>
						<div style="text-align: center;">
									<a class="mo_2fa_ef_read_more"href="' . esc_url( MoWpnsConstants::MO2F_PLUGINS_PAGE_URL ) . '"/">Read More</a>
							</div>
						</div>
				</div>
				<div class="mo_2fa_email_template_details">
						<i class="fa fa-headphones" style="font-size:50px;color: black;margin-top: 6%"></i>
						<div style="min-height: 150px;">
								<h2 style="color: black;">Documentation</h2>
								<p style="color: black;padding: 0px 27px;text-align: justify;">miniOrange Two-Factor Authentication in which you have to provide two factors to gain the access</p>
						</div>
						<div>
							<br><br>
							<div style="text-align: center;">
								<a class="mo_2fa_ef_read_more" href="https://developers.miniorange.com/docs/security/wordpress/wp-security">Read More</a>
						</div>

						</div>
				</div>
			</div>
				<div class="mo_2fa_feature_block" style="margin-left: 14%;">
					<div class="mo_2fa_email_template_details">
							<i class="fa fa-file-text" style="font-size:50px;color: black;margin-top: 6%"></i>
						<div style="min-height: 150px;">
							<h2 style="color: black;">Support</h2>
							<p style="color: black;padding: 0px 27px;text-align: justify;">You are not going to hit a ridiculously long phone menu when you call us or contact us.</p>
						</div>
						<div>
						<br><br>
						<div style="text-align: center;">
							<a class="mo_2fa_ef_read_more" href="https://www.miniorange.com/contact">Read More</a>
							</div>
						</div>
					</div>
					<div class="mo_2fa_email_template_details">
							<i class="fa fa-shield" style="font-size:50px;color: black;margin-top: 6%"></i>
						<div style="min-height: 150px;">
							<h2 style="color: black;">Security site</h2>
							<p style="color: black;padding: 0px 27px;text-align: justify;">miniOrange combines Web Application Firewall (WAF),Malware Scanner, Encrypted Database and File backup</p>
						</div>
						<div>
							<br><br>
							<div style="text-align: center;">
								<a class="mo_2fa_ef_read_more" href="https://security.miniorange.com/">Read More</a>
							</div>
						</div>
					</div>
				</div>
				<div class="myDiv">
		   <h2 style="margin-bottom: -36px;"><b>You are welcome to use our New Features</b></h2>.
			<h2 style="margin-bottom: -36px;"  > Thank you</h2><br>
			<p style="margin-top: 26px;">If you need any help we are just a mail away <p> <br>
			      <p style="margin-top: -47px;"> Contact us at :-  <b>info@xecurify.com /2fasupport@xecurify.com<b></p><br>
			      <p style="margin-top: -10px;"> If you want to disable this notification please turn of the toggle of email from Notification TAB
			      		</p>
			
		</div>
	</div>
</body>
</html>';
}


