<?php
/**
 * This file show frontend to configure OTP over SMS/Email/Telegram method.
 *
 * @package miniorange-2-factor-authentication/views/twofa/setup
 */

use TwoFA\Helper\MoWpnsConstants;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
	<html>
		<head>  <meta charset="utf-8"/>
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php
				mo2f_inline_css_and_js();
				wp_register_script( 'mo2f_phone_js', plugins_url( 'includes/js/phone.min.js', dirname( dirname( dirname( __FILE__ ) ) ) ), array(), MO2F_VERSION, false );
				wp_print_scripts( 'mo2f_phone_js' );
				wp_register_style( 'mo2f_phone', plugins_url( 'includes/css/phone.min.css', dirname( dirname( dirname( __FILE__ ) ) ) ), array(), MO2F_VERSION, false );
				wp_print_styles( 'mo2f_phone' );
			?>
		</head>
		<body>
			<div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
				<div class="mo2f-modal-backdrop"></div>
				<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md" >
					<div class="login mo_customer_validation-modal-content">
						<div class="mo2f_modal-header">
							<h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
							<?php esc_html_e( 'Configure ' . MoWpnsConstants::mo2f_convert_method_name( $current_selected_method, 'cap_to_small' ), 'miniorange-2-factor-authentication' ); ?></h4>
						</div>
						<div class="mo2f_modal-body">
							<div id="otpMessaghide" style="display: none;">
								<p class="mo2fa_display_message_frontend" style="text-align: left !important; "> <?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?></p>
							</div>

							<div class="mo2f_row">
								<form name="f" method="post" action="" id="mo2f_inline_verifyphone_form">
									<?php
									echo wp_kses(
										$skeleton['##instructions##'],
										array(
											'h4' => array(
												'clase' => array(),
												'style' => array(),

											),
											'b'  => array(),

										)
									);

									echo wp_kses(
										$skeleton['##input_field##'],
										array(
											'div'   => array(
												'style' => array(),
												'class' => array(),
											),
											'h2'    => array(),
											'i'     => array(),
											'br'    => array(),
											'input' => array(
												'id'      => array(),
												'class'   => array(),
												'name'    => array(),
												'type'    => array(),
												'value'   => array(),
												'style'   => array(),
												'pattern' => array(),
												'title'   => array(),
												'size'    => array(),

											),
											'a'     => array(
												'href'   => array(),
												'target' => array(),
											),
											'span'  => array(
												'title' => array(),
												'class' => array(),
												'style' => array(),
											),

										)
									);
									$show_validation_form = get_user_meta( $current_user_id, 'mo2f_otp_send_true', true ) ? 'block' : 'none';
									?>
									<br>
									<input type="hidden" name="option" value="mo2f_configure_otp_based_twofa"/>
									<input type="hidden" name="mo2f_otp_based_method" value="<?php echo esc_attr( $current_selected_method ); ?>"/>
									<input type="hidden" name="mo2f_session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
									<input type="button" name="back" id="go_back_verify" class="button button-primary button-large" value="<?php echo esc_attr__( 'Back', 'miniorange-2-factor-authentication' ); ?>"/>
									<input type="button" id ="verify" name="verify" class="miniorange_button" value="<?php esc_attr_e( 'Send OTP', 'miniorange-2-factor-authentication' ); ?>" />
									<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
								</form>
							</div>  

							<form name="f" method="post" action="" id="mo2f_validateotp_form" style="display:<?php echo esc_attr( $show_validation_form ); ?>">
								<input type="hidden" name="option" value="mo2f_configure_otp_based_methods_validate"/>
								<input type="hidden" name="mo2f_session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
								<input type="hidden" name="mo2f_otp_based_method" value="'<?php echo esc_attr( $current_selected_method ); ?>'"/>
								<input type="hidden" name="mo2f_configure_otp_based_methods_validate_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-configure-otp-based-methods-validate-nonce' ) ); ?> "/> <p> <?php echo esc_html__( 'Enter One Time Passcode', 'miniorange-2-factor-authentication' ); ?></p>
								<input class="mo2f_table_textbox" style="width:200px;" autofocus="true" type="text" name="otp_token" placeholder=" <?php echo esc_attr__( 'Enter OTP', 'miniorange-2-factor-authentication' ); ?>" style="width:95%;"/> <a href="#resendsmslink"> <?php echo esc_html__( 'Resend OTP?', 'miniorange-2-factor-authentication' ); ?> </a>
								<br><br>
								<input type="button" name="back" id="go_back" class="button button-primary button-large" value="<?php echo esc_attr__( 'Back', 'miniorange-2-factor-authentication' ); ?>"/>
								<input type="button" name="validate" id="validate" class="button button-primary button-large" value="<?php echo esc_attr__( 'Validate OTP', 'miniorange-2-factor-authentication' ); ?>"/>
							</form><br>

							<?php mo2f_customize_logo(); ?>
						</div>
					</div>
				</div>
			</div>
			<form name="mo2f_backto_mo_loginform" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" style="display:none;">
				<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>" />
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
			</form>
			<form name="mo2f_inline_otp_validated_form" method="post" action="" id="mo2f_inline_otp_validated_form" style="display:none;">
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
				<input type="hidden" name="option" value="miniorange_inline_complete_otp"/>
				<input type="hidden" name="miniorange_inline_validate_otp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-validate-otp-nonce' ) ); ?>" />
			</form>
			<?php if ( count( $selected_methods ) > 1 ) { ?>
			<form name="mo2f_goto_two_factor_form" method="post" action="" id="mo2f_goto_two_factor_form" >              
				<input type="hidden" name="option" value="miniorange_back_inline"/>
				<input type="hidden" name="miniorange_inline_two_factor_setup" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-setup-nonce' ) ); ?>" />
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>"/>
			</form>
			<?php } ?>
		<script>
			jQuery("#phone").intlTelInput();
			function mologinback(){
				jQuery('#mo2f_backto_mo_loginform').submit();
			}
			jQuery('#go_back').click(function() {  
					jQuery('#mo2f_goto_two_factor_form').submit();
			});
			jQuery('#go_back_verify').click(function() {  
					jQuery('#mo2f_goto_two_factor_form').submit();
			});
			jQuery('a[href="#resendsmslink"]').click(function(e) {
				jQuery('#verify').click();
			});
			var ajaxurl = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
			jQuery("#verify").click(function()
			{
				var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>';
				var data = {
					'action'  : 'mo_two_factor_ajax',
					'mo_2f_two_factor_ajax'  : 'mo2f_configure_otp_based_twofa',
					'mo2f_otp_based_method'  : jQuery('input[name=mo2f_otp_based_method]').val(),
					'mo2f_phone_email_telegram'  : jQuery('input[name=mo2f_phone_email_telegram]').val(),
					'mo2f_session_id'  : jQuery('input[name=mo2f_session_id]').val(),
					'nonce'  : nonce,	
				};
				jQuery.post(ajaxurl, data, function(response) {
					if( response['success'] ){
						jQuery('#go_back_verify').css('display','none');
						jQuery('#mo2f_validateotp_form').css('display','block');
						jQuery("input[name=otp_token]").focus();
						mo2f_show_message(response['data']);
					}else if( ! response['success'] ){
						mo2f_show_message(response['data']);
					}else{
						mo2f_show_message('Unknown error occured. Please try again!');
					}
				});
			});
			jQuery("#validate").click(function()
			{   
				var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>';
				var data = {
					'action'  : 'mo_two_factor_ajax',
					'mo_2f_two_factor_ajax'  : 'mo2f_configure_otp_based_methods_validate',
					'mo2f_otp_based_method'  : jQuery('input[name=mo2f_otp_based_method]').val(),
					'otp_token'  : jQuery('input[name=otp_token]').val(),
					'mo2f_session_id'  : jQuery('input[name=mo2f_session_id]').val(),
					'nonce'  : nonce,	
				};
				jQuery.post(ajaxurl, data, function(response) {
					if( response['success'] ){
						jQuery('#mo2f_inline_otp_validated_form').submit();
					}else if( ! response['success'] ){
						mo2f_show_message(response['data']);
					}else{
						mo2f_show_message('Unknown error occured. Please try again!');
					}
				});
			});
			jQuery("input[name=mo2f_phone_email_telegram]").keypress(function(e) {
				if (e.which === 13) {
					e.preventDefault();
					jQuery("#verify").click();
					jQuery("input[name=otp_token]").focus();
				}

			});
			jQuery("input[name=otp_token]").keypress(function(e) {
				if (e.which === 13) {
					e.preventDefault();
					jQuery("#validate").click();
				}

			});
			function mo2f_show_message(response) {
				var html = '<div id="otpMessage"><p class="mo2fa_display_message_frontend" style="text-align: left !important; ">' + response + '</p></div>';
				jQuery('#otpMessage').empty();
				jQuery('#otpMessaghide').after(html);
			}
		</script>
		</body>
	</html>
