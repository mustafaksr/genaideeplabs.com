<?php
/**
 * This file contains html UI of premium features.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

// Needed in both.

use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$setup_dir_name = dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
require $setup_dir_name;
	global $current_user_info;
		$current_user_info = wp_get_current_user();
		global $mo2fdb_queries;
?>
	<div class="mo2f_table_divide_border" id="mo2f_customization_tour">
		<form name="f" id="custom_css_form_add" method="post" action="">
			<input type="hidden" name="option" value="mo_auth_custom_options_save" />
			<div id="mo2f_custom_addon_hide">
				<h2>3. Personalization
				<a  class="mo2fa-addons-preview-alignment" onclick="mo2f_Personalization_Plugin_Icon()">&nbsp;&nbsp;See Preview</a>
				</h2>
					<hr>
				<p id="custom_description">
					<?php esc_html_e( 'This helps you to modify and redesign the 2FA prompt to match according to your website and various customizations in the plugin dashboard.', 'miniorange-2-factor-authentication' ); ?>
			</p>
			</div>
			<div id="mo2f_Personalization_Plugin_Icon" style="display: none;">
			<div class="mo2f_table_layout" style="background-color: aliceblue; border:none;">
				<h3><?php esc_html_e( 'Customize Plugin Icon', 'miniorange-2-factor-authentication' ); ?>
					<a href='<?php echo esc_url( $two_factor_premium_doc['Custom plugin logo'] ); ?>'  target="_blank">
						<span class="dashicons dashicons-text-page" style="font-size:19px;color:#413c69;float: right;"></span>
					</a>
				</h3><br>
				<div>   	
				<div style="margin-left:2%">
					<input type="checkbox" id="mo2f_enable_custom_icon" name="mo2f_enable_custom_icon" value="1" 
					<?php
					checked( 1 === get_option( 'mo2f_enable_custom_icon' ) );
					echo 'disabled';
					?>
					/>
					<?php esc_html_e( 'Change Plugin Icon.', 'miniorange-2-factor-authentication' ); ?>
					<br>
					<div class="mo2f_advanced_options_note"><p style="padding:5px;"><i>
					<?php
					esc_html_e( 'Go to /wp-content/uploads/miniorange folder and upload a .png image with the name "plugin_icon" (Max Size: 20x34px).', 'miniorange-2-factor-authentication' );
					?>
						</i></p>
					</div>
				</div> </div><hr>
				<h3><?php esc_html_e( 'Customize Plugin Name', 'miniorange-2-factor-authentication' ); ?><a href='<?php esc_url( $two_factor_premium_doc['Custom plugin name'] ); ?>' target="_blank">
						<span class="dashicons dashicons-text-page" style="font-size:19px;color:#413c69;float: right;"></span>
						</a></h3>
						<div> 
				<div style="margin-left:2%">
					<?php esc_html_e( 'Change Plugin Name:', 'miniorange-2-factor-authentication' ); ?> &nbsp;
					<input type="text" class="mo2f_table_textbox" style="width:35% 	" id="mo2f_custom_plugin_name" name="mo2f_custom_plugin_name" <?php echo 'disabled'; ?> value="<?php echo esc_attr( MoWpnsUtility::get_mo2f_db_option( 'mo2f_custom_plugin_name', 'get_option' ) ); ?>" placeholder="<?php esc_html( 'Enter a custom Plugin Name.' ); ?>" />
					<br>
					<div class="mo2f_advanced_options_note"><p style="padding:5px;"><i>
						<?php esc_html_e( 'This will be the Plugin Name You and your Users see in  WordPress Dashboard.', 'miniorange-2-factor-authentication' ); ?>
					</i></p> </div>
				</div>
			</div><hr> 
	</form>		
	<?php mo2f_show_2_factor_custom_design_options( $current_user_info ); ?>
	<div id="mo2f_Personalization_Plugin_Icon" style="display: none;">
	<h3><?php esc_html_e( 'Custom Email and SMS Templates', 'miniorange-2-factor-authentication' ); ?>
	<a href="https://developers.miniorange.com/docs/security/wordpress/wp-security/customize-email-template" target="_blank"><span class="dashicons dashicons-text-page" style="font-size:19px;color:#413c69;float: right;"></span> </a>	</h3>  <hr>
	<div>
	<div style="margin-left:2%">
					<p><?php esc_html_e( 'You can change the templates for Email and SMS as per your requirement.', 'miniorange-2-factor-authentication' ); ?></p>
					<?php
					if ( get_option( 'mo2f_email' ) && get_option( 'mo2f_customerKey' ) ) {
						if ( get_option( 'mo2f_miniorange_admin' ) === $current_user_info->ID ) {
							?>
								<a style="box-shadow: none;" class="button button-primary button-large"<?php echo 'disabled'; ?>><?php esc_html_e( 'Customize Email Template', 'miniorange-2-factor-authentication' ); ?></a><span style="margin-left:10px;"></span>
								<a style="box-shadow: none;" class="button button-primary button-large"<?php echo 'disabled'; ?> ><?php esc_html_e( 'Customize SMS Template', 'miniorange-2-factor-authentication' ); ?></a>
							<?php
						}
					} else {
						?>
						<a class="button button-primary button-large"<?php echo 'disabled'; ?>style="pointer-events: none;cursor: default;box-shadow: none;"><?php esc_html_e( 'Customize Email Template', 'miniorange-2-factor-authentication' ); ?></a>
							<span style="margin-left:10px;"></span>
						<a class="button button-primary button-large"<?php echo 'disabled'; ?> style="pointer-events: none;cursor: default;box-shadow: none;"><?php esc_html_e( 'Customize SMS Template', 'miniorange-2-factor-authentication' ); ?></a>
					<?php } ?>
					</div>
					</div>
				</div>
			</div>
		<form style="display:none;" id="mo2fa_addon_loginform" action="<?php echo esc_url( get_option( 'mo2f_host_name' ) . '/moas/login' ); ?>" 
		target="_blank" method="post">
			<input type="email" name="username" value="<?php echo esc_attr( $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $current_user_info->ID ) ); ?>" />
			<input type="text" name="redirectUrl" value="" />
		</form>
				<script>
			function mo2fLoginMiniOrangeDashboard(redirectUrl){ 
				document.getElementById('mo2fa_addon_loginform').elements[1].value = redirectUrl;
				jQuery('#mo2fa_addon_loginform').submit();
			}
		</script>

	<?php
	/**
	 * This function is used to show UI for personalisation of login form.
	 *
	 * @param object $current_user_info is used to get current user's email or id.
	 * @return void
	 */
	function mo2f_show_2_factor_custom_design_options( $current_user_info ) {
			include dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
			$login_popup_features = array(
				'Background'               => 'background',
				'Popup Background'         => 'popup_bg',
				'Button'                   => 'button',
				'Links Text'               => 'links_text',
				'Popup Message Text'       => 'notif_text',
				'Popup Message Background' => 'notif_bg',
				'OTP Token Background'     => 'otp_bg',
				'OTP Token Text'           => 'otp_text',

			);
			?>
			<div>
			<div id="mo2f_custom_addon_hide">
			</div>
			<div>
			<form name="f"  id="custom_css_form" method="post" action="">
			<input type="hidden" name="option" value="mo_auth_custom_design_options_save" />
			<br>
			<h2> Customize UI of Login Pop up
				<a href='<?php echo esc_url( $two_factor_premium_doc['custom login popup'] ); ?>' target="_blank">
						<span class="dashicons dashicons-text-page" style="font-size:19px;color:#413c69;float: right;"></span>
					</a>
				</h2>	
				<hr>
				<br>
					<table class="mo2f_settings_table" style="margin-left:2%">
					<?php
					foreach ( $login_popup_features as $key => $value ) {
						?>
						<tr>
						<td><?php esc_html_e( $key . ' Color:', 'miniorange-2-factor-authentication' );  //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?> </td>
						<td><input type="text" id="mo2f_custom_'<?php esc_attr( $value ); ?>'_color" name="mo2f_custom_'<?php esc_attr( $value ); ?>'_color" <?php echo 'disabled'; ?> value="<?php echo esc_attr( get_option( 'mo2f_custom_' . $value . '_color' ) ); ?>" class="my-color-field" /> </td>
					</tr>
						<?php
					}
					?>
					</table>
			<br>
			<label>
			<input  type="submit" value="Save Settings" <?php echo 'disabled'; ?> class="button button-primary button-large">
			</label>
		</div>					
			</form>
			</div>

			</div>
		</div>
			<script type="text/javascript">
				function mo2f_Personalization_Plugin_Icon()
				{
					jQuery('#mo2f_Personalization_Plugin_Icon').toggle();
				}
			</script>
			<?php
	}
