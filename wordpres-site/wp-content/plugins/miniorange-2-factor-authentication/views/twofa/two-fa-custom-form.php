<?php
/**
 * This file contains the information regarding custom login form support.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

// Needed in both.

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$login_forms = array(
	'WooCommerce'                               => array(
		'form_logo' => 'woocommerce',
		'form_link' => 'Woocommerce',
	),
	'Ultimate Member'                           => array(
		'form_logo' => 'ultimate_member',
		'form_link' => 'Ultimate Member',
	),
	'Restrict Content Pro'                      => array(
		'form_logo' => 'restrict_content_pro',
		'form_link' => 'Restrict Content Pro',
	),
	'Theme My Login'                            => array(
		'form_logo' => 'theme_my_login',
		'form_link' => 'Theme My Login',
	),
	'User Registration'                         => array(
		'form_logo' => 'user_registration',
		'form_link' => 'User Registration',
	),
	'LoginPress | Custom Login Page Customizer' => array(
		'form_logo' => 'Custom_Login_Page_Customizer_LoginPress',
		'form_link' => 'LoginPress',
	),
	'Admin Custom Login'                        => array(
		'form_logo' => 'Admin_Custom_Login',
		'form_link' => 'Admin Custom Login',
	),
	'RegistrationMagic – Custom Registration Forms and User Login' => array(
		'form_logo' => 'RegistrationMagic_Custom_Registration_Forms_and_User_Login',
		'form_link' => 'RegistrationMagic',
	),
	'BuddyPress'                                => array(
		'form_logo' => 'buddypress',
		'form_link' => 'BuddyPress',
	),
	'Profile Builder'                           => array(
		'form_logo' => 'profile-builder',
		'form_link' => 'Profile Builder',
	),
	'Elementor Pro'                             => array(
		'form_logo' => 'elementor-pro',
		'form_link' => 'Elementor Pro',
	),
	'Login with Ajax'                           => array(
		'form_logo' => 'login-with-ajax',
		'form_link' => 'Login with Ajax',
	),
);

?>
<div>

<div>
	<h2>Custom Login Forms</h2>
	<p>We support most of the login forms present on the WordPress. Our plugin is tested with almost all the forms mentioned below.</p>
</div>
<div>
	<div>
		<table class="customloginform" style="width: 95%">
			<tr>
				<th style="width: 65%">
					Custom Login form
				</th>
				<th style="width: 13%">
					Documents
				</th>
			</tr>
			<?php
			foreach ( $login_forms as $key => $value ) {
				?>
			<tr>
				<td>
					<?php echo '<img style="width:30px; height:30px;display: inline;" src="' . esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ) . '/includes/images/' . esc_attr( $value['form_logo'] ) . '.png">'; ?><h3 style="margin-left: 15px; font-size: large; display: inline; float: inherit; padding-right: 50px;"><?php echo esc_html( $key ); ?></h3>
				</td>
				<td>
					<div style="text-align: center;">
						<a href='<?php echo esc_url( $two_factor_premium_doc[ $value['form_link'] ] ); ?>' target="blank"><span class="dashicons dashicons-text-page mo2f_doc_icon_style mo2f-custom-guide"></span></a>
					</div>
				</td>
			</tr>
				<?php
			}
			?>
		</table>
		<div style="text-align: center">
		<b style="color: red; " >**If you want to enable/disable 2FA prompt on other Custom login pages please Contact us.</b>
		<br>
		<b style="color: red;" >**This feature will only work when you enable 2FA prompt on WordPress login page.</li></b>

		<p style="font-size:15px">If there is any custom login form where Two Factor is not initiated for you, please reach out to us by dropping a query in the <b>Support</b> section.</p>
		</div>
	</div>

	<hr>

	<form name="form_custom_form_config" method="post" action="" id="mo2f_custom_form_config">
	<h2><input type="checkbox" id="mo2f_use_shortcode_config" name="mo2f_use_shortcode_config" value="yes" 
			<?php
			if ( 'true' === get_option( 'enable_form_shortcode' ) ) {
				echo 'checked';}
			?>
			>
			<label for="mo2f_use_shortcode_config">Enable OTP Verification on Registration form</label></h2>
		<?php
		$is_registered = get_site_option( 'mo2f_customerkey' ) ? get_site_option( 'mo2f_customerkey' ) : 'false';
		if ( 'false' === $is_registered ) {
			?>
			<br>
			<div style="padding: 10px;border: red 1px solid">
				<a onclick="registerwithminiOrange()"> Register/Login</a> with miniOrange to Use the Shortcode
			</div>
			<?php
		}
		?>
		<div style="padding: 20px;border: 1px #DCDCDC solid">
		<h3> <?php esc_html_e( 'Step 1 : Select Authentication Method', 'miniorange-2-factor-authentication' ); ?> </h3>
			<hr>
			<table>
				<tbody>
					<tr>
						<td>
							<input type="checkbox" name="mo2f_method_phone" id="mo2f_method_phone" value="phone" 
							<?php
							if ( MoWpnsConstants::OTP_OVER_SMS === get_site_option( 'mo2f_custom_auth_type' ) || 'both' === get_site_option( 'mo2f_custom_auth_type' ) ) {
								echo 'checked';}
							?>
						>
						<label for="mo2f_method_phone"> Verify Phone Number </label>
					</td>
					<td>
						<input type="checkbox" name="mo2f_method_email" id="mo2f_method_email" value="email" 
						<?php
						if ( MoWpnsConstants::OTP_OVER_EMAIL === get_site_option( 'mo2f_custom_auth_type' ) || 'both' === get_site_option( 'mo2f_custom_auth_type' ) ) {
							echo 'checked';}
						?>
						>
						<label for="mo2f_method_email"> Verify Email Address </label>
					</td>
				</tr>
				</tbody>
			</table>

			<table>
				<h3>Step 2 : Select Form</h3>
				<i class="note">NOTE :- If you don't know how to choose selectors to enable 2FA on registration then refer to this video.</i>
				<a href='<?php echo esc_url( MoWpnsConstants::CHOOSE_SELECTORS_YOUTUBE ); ?>' target="_blank">
					<span title="Watch Setup Video" class="dashicons dashicons-video-alt3" style="font-size:18px;color:red;margin-right: 5px;margin-bottom:5px;"></span>
				</a>
				<br>		
				<tbody>
				<tr>
					<td>
						<select id="regFormList" name="regFormList">
							<?php

							$default_wordpress = array(
								'formName'       => 'Wordpress Registration',
								'formSelector'   => '#wordpress-register',
								'emailSelector'  => '#wordpress-register',
								'submitSelector' => '#wordpress-register',
							);

							$wc_form = array(
								'formName'       => 'Woo Commerce',
								'formSelector'   => '.woocommerce-form-register',
								'emailSelector'  => '#reg_email',
								'submitSelector' => '.woocommerce-form-register__submit',
							);

							$bb_form = array(
								'formName'       => 'Buddy Press',
								'formSelector'   => '#signup-form',
								'emailSelector'  => '#signup_email',
								'submitSelector' => '#submit',
							);

							$login_press_form = array(
								'formName'       => 'Login Press',
								'formSelector'   => '#registerform',
								'emailSelector'  => '#user_email',
								'submitSelector' => '#wp-submit',
							);

							$user_reg_form = array(
								'formName'       => 'User Registration',
								'formSelector'   => '.register',
								'emailSelector'  => '#user_email',
								'submitSelector' => '.ur-submit-button',
							);

							$pm_pro_form = array(
								'formName'       => 'Paid MemberShip Pro',
								'formSelector'   => '#pmpro_form',
								'emailSelector'  => '#bemail',
								'phoneSelector'  => '#bphone',
								'submitSelector' => '#pmpro_btn-submit',
							);

							$custom_form = array(
								'formName'       => 'Custom Form',
								'formSelector'   => '',
								'emailSelector'  => '',
								'submitSelector' => '',
							);

							$forms_array     = array( 'forms' => array( $default_wordpress, $wc_form, $bb_form, $login_press_form, $user_reg_form, $pm_pro_form, $custom_form ) );
							$form_size_array = count( $forms_array['forms'] );
							for ( $i = 0; $i < $form_size_array; $i++ ) {
								$form_name = $forms_array['forms'];
								echo '<option' . ( get_site_option( 'mo2f_custom_form_name' ) === $form_name[ $i ]['formSelector'] ? ' selected ' : '' ) . ' value=' . esc_attr( strtolower( str_replace( ' ', '', esc_attr( $form_name[ $i ]['formName'] ) ) ) ) . '>' . esc_html( $form_name[ $i ]['formName'] ) . '</option>';
								?>
								<?php
							}
							?>
						</select>
					</td>
				</tr>
				</tbody>
			</table>
			<div id="selector_div">
			<h4 id="enterMessage" name="enterMessage" style="display: none;padding:8px; color: white; background-color: teal">Enter Selectors for your Form</h4>
			<div id="formDiv">
				<h4>Form Selector<span style="color: red;font-size: 14px">*</span></h4>
				<input type="text" value="<?php echo esc_attr( get_site_option( 'mo2f_custom_form_name' ) ); ?>" style="width: 100%" name="mo2f_shortcode_form_selector" id="mo2f_shortcode_form_selector" placeholder="Example #form_id" 
													<?php
													if ( $is_any_of_woo_bb ) {
															echo 'disabled';}
													?>
					>
		</div>
			<div id="emailDiv">
				<h4>Email Field Selector <span style="color: red;font-size: 14px">*</span></h4>
				<input type="text" style="width: 100%" value="<?php echo esc_attr( get_site_option( 'mo2f_custom_email_selector' ) ); ?>" name="mo2f_shortcode_email_selector" id="mo2f_shortcode_email_selector" placeholder="example #email_field_id" 
																		<?php
																		if ( $is_any_of_woo_bb ) {
																				echo 'disabled';}
																		?>
					>
			</div>
			<div id="phoneDiv">
				<h4>Phone Field Selector <span style="color: red;font-size: 14px">*</span></h4>
				<input type="text" style="width: 100%" value="<?php echo esc_attr( get_site_option( 'mo2f_custom_phone_selector' ) ); ?>" name="mo2f_shortcode_phone_selector" id="mo2f_shortcode_phone_selector" placeholder="example #phone_field_id" >
			</div>
			<div id="submitDiv">
				<h4>Submit Button Selector <span style="color: red;font-size: 14px">*</span></h4>
				<input type="text" style="width: 100%" value="<?php echo esc_attr( get_site_option( 'mo2f_custom_submit_selector' ) ); ?>" name="mo2f_shortcode_submit_selector" id="mo2f_shortcode_submit_selector" placeholder="example #submit_button_id" 
																		<?php
																		if ( $is_any_of_woo_bb ) {
																				echo 'disabled';}
																		?>
					>
				<p style="color:red;">* Required</p>
			</div>
			</div>
			<br>
			<input type="checkbox" id="mo2f_form_submit_after_validation" name="mo2f_form_submit_after_validation" value="yes" 
			<?php
			if ( 'true' === get_option( 'mo2f_form_submit_after_validation' ) ) {
				echo 'checked';}
			?>
			>
			<label for="mo2f_form_submit_after_validation">Submit form after validating OTP</label>
			<br>
			<h2> Step 3 : Copy Shortcode </h2>
			<p style="color: red">*Add this on the page where you have your registration form / checkout form to enable OTP verification for the same.</p>
			<h4 class="shortcode_form" style="font-family: monospace">[mo2f_enable_register]</h4>
			<input type="button" style="float: right" class="button button-primary" value="Save Settings"
				id="mo2f_form_config_save"  name= "mo2f_form_config_save">
			<input type="hidden" id="mo2f_nonce_save_form_settings" name="mo2f_nonce_save_form_settings"
				value="<?php echo esc_attr( wp_create_nonce( 'mo2f-nonce-save-form-settings' ) ); ?>"/>
			<br>
		</div>


	</form>
	<script>
		jQuery(document).ready(function () {


			let formArray = <?php echo wp_json_encode( $form_name ); ?>;
				let $mo = jQuery;
			$mo('#mo2f_shortcode_form_selector').prop('disabled',true)
			$mo('#mo2f_shortcode_submit_selector').prop('disabled',true)
			$mo('#mo2f_shortcode_email_selector').prop('disabled',true)
			let customForm = false;
			is_registered   = '<?php echo esc_js( $is_registered ); ?>';

			$mo('#phoneDiv').css('display','none')
			mo2f_reg_form_click();
			$mo("#mo2f_method_phone").change(function() {
				let checked = $mo('#mo2f_method_phone').is(':checked')
				if(checked)
				{
					$mo('#phoneDiv').css('display','inherit')
				}
				else
				{
					$mo('#phoneDiv').css('display','none')
				}
			});
			if(!is_registered)
			{
				$mo('#mo2f_use_shortcode_config').prop('checked',false)
				$mo('#mo2f_use_shortcode_config').prop('disabled',true)
			}

			$mo("#regFormList").change(mo2f_reg_form_click);

			function mo2f_reg_form_click(){
				let index = $mo("#regFormList").prop('selectedIndex')
				let count = $mo('#regFormList option').length-1; //count of forms in select tags excluding custom form.
				if(index<count) 
				{
					$mo('#selector_div div').css('display','none')
				}
				else
				{
					$mo('#mo2f_shortcode_email_selector').prop('disabled',false);
					$mo('#mo2f_shortcode_form_selector').prop('disabled',false);
					$mo('#mo2f_shortcode_phone_selector').prop('disabled',false);
					$mo('#mo2f_shortcode_submit_selector').prop('disabled',false);

					$mo('#selector_div div').css('display','inherit')

				}

				$mo('#mo2f_shortcode_form_selector').val(formArray[index]["formSelector"])
				$mo('#mo2f_shortcode_submit_selector').val(formArray[index]["submitSelector"])
				$mo('#mo2f_shortcode_email_selector').val(formArray[index]["emailSelector"])
				if(formArray[index]["phoneSelector"] !== null){
				$mo('#phoneDiv').css('display','block')
				$mo('#mo2f_shortcode_phone_selector').val(formArray[index]["phoneSelector"])
			}
				if(index===0)
				{
					$mo('#mo2f_shortcode_phone_selector').val("#wp-register");
				}
			}
			$mo('#custom_auto').click(function()
			{
				customForm = true;
				$mo('#formDiv').css('display','inherit')
				$mo('#submitDiv').css('display','inherit')
				$mo('#emailDiv').css('display','inherit')
				$mo('#mo2f_shortcode_form_selector').val('<?php echo esc_attr( get_site_option( 'mo2f_custom_form_name' ) ); ?>');
				$mo('#mo2f_shortcode_submit_selector').val('<?php echo esc_attr( get_site_option( 'mo2f_custom_submit_selector' ) ); ?>');
				$mo('#mo2f_shortcode_email_selector').val('<?php echo esc_attr( get_site_option( 'mo2f_custom_email_selector' ) ); ?>');
			});

			$mo('#mo2f_form_config_save').click(function () {
				is_registered   = '<?php echo esc_js( $is_registered ); ?>';
				if(!is_registered)
					error_msg("Please Register/Login with miniOrange");
				else
				{
					let sms,email,authType,enableShortcode,formSubmit;
					formSubmit      = $mo('#mo2f_form_submit_after_validation').is(':checked');
					enableShortcode = $mo('#mo2f_use_shortcode_config').is(':checked');
					sms             = $mo('#mo2f_method_phone').is(':checked');
					email           = $mo('#mo2f_method_email').is(':checked');
					email_selector  = $mo('#mo2f_shortcode_email_selector').val();
					phone_selector  = $mo('#mo2f_shortcode_phone_selector').val();
					form_selector   = $mo('#mo2f_shortcode_form_selector').val();
					submit_selector = $mo('#mo2f_shortcode_submit_selector').val();
					authType        = (email === true && sms === true) ? 'both' : (email === false && sms=== true) ? '<?php echo esc_js( MoWpnsConstants::OTP_OVER_SMS ); ?>' : '<?php echo esc_js( MoWpnsConstants::OTP_OVER_EMAIL ); ?>';
					error          = "";
					if(authType === 'both' || authType === '<?php echo esc_js( MoWpnsConstants::OTP_OVER_SMS ); ?>' )
						if(email_selector === ''){
							error = "Add email selector to use OTP Over Email";
						}
					if(authType === 'both' || authType === '<?php echo esc_js( MoWpnsConstants::OTP_OVER_SMS ); ?>' )
						if(phone_selector === ''){
							error = "Add phone selector to use OTP Over SMS";
						}

					if(!validate_selector(email_selector))
						error = "NOTE: Please enter valid selectors. Element\'s ID Selector looks like #element_id and Element\'s name Selector looks like input[name=element_name].";
					if(error != ""){
						error_msg(error);
					}
					else{
						let data =  {
							'action'                        : 'mo_two_factor_ajax',
							'mo_2f_two_factor_ajax'         : 'mo2f_save_custom_form_settings',
							'mo2f_nonce_save_form_settings' :  $mo('#mo2f_nonce_save_form_settings').val(),
							'nonce' : "<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>",
							'submit_selector'               :  submit_selector,
							'form_selector'                 :  form_selector,
							'email_selector'                :  email_selector,
							'phone_selector'                :  phone_selector,
							'authType'                      :  authType,
							'customForm'                    :  customForm,
							'enableShortcode'               :  enableShortcode,
							'formSubmit'                    :  formSubmit
						};
						jQuery.post(ajaxurl, data, function(response) {
							if(response.saved === false)
							{
								error_msg('One or more fields are empty.');
							}
							else if(response == "error")
							{
								error_msg("Error occured while saving the settings.");
							}
							else if(response.saved === true)
							{
								success_msg("Selectors Saved Successfully.");
							}
						});
					}
				}
			});
		});
		function registerwithminiOrange(){
			jQuery('#my_account_2fa').click();
		}
		function validate_selector(selector){
			let is_valid = false
			if(/^#/.test(selector))
				is_valid = true
			if(/^\./.test(selector))
				is_valid = true
			if(/^input\[name=/.test(selector))
				is_valid = true

			return is_valid;
		}



	</script>

</div>

</div>

