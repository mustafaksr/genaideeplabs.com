<?php
/**
 * This files contains UI related to adaptive authentication.
 *
 *  @package miniorange-2-factor-authentication/views/twofa
 */

// Needed in both.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$setup_dir_name = dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
require $setup_dir_name;
global $is_register;

?>
<div class="mo2f_table_divide_border">
	<form id="settings_from_addon" method="post" action="">
		<input type="hidden" name="option" value="mo_auth_addon_settings_save"/>
		<h2><?php esc_html_e( '1. Remember Device', 'miniorange-2-factor-authentication' ); ?>
		<a class="mo2fa-addons-preview-alignment" onclick="mo2f_rba_functionality()">See Preview</a>
			<a href='<?php echo esc_url( $two_factor_premium_doc['Remember Device'] ); ?>'target="_blank">
				<span class="dashicons dashicons-text-page" style="font-size:19px;color:#413c69;float: right;"></span>
			</a>


		</h2>
		<hr>
		<p id="rba_description" >
			It helps you to remember the device where you will not be asked to authenticate the 2-factor if you login from the remembered device. 
		</p>
	<div id="mo2f_hide_login_form" style="display: none;">
			<div class="mo2f_table_layout" style="background-color: aliceblue; border:none;">
			<h2>Device Profile Settings</h2>
			<hr>
			<br>
				<input type="checkbox" id="mo2f_remember_device" name="mo2f_remember_device" value="1"/>
				<?php esc_html_e( 'Enable', 'miniorange-2-factor-authentication' ); ?> '<b><?php esc_html_e( 'Remember device', 'miniorange-2-factor-authentication' ); ?></b>' <?php esc_html_e( 'option ', 'miniorange-2-factor-authentication' ); ?> <br><span style="color:red;">&emsp;(<?php esc_html_e( 'Applicable only for ', 'miniorange-2-factor-authentication' ); ?><i><?php esc_html_e( 'Login with password + 2nd Factor. The option is available in Login Settings tab.', 'miniorange-2-factor-authentication' ); ?>)</i></span><br><br>
				<div style="margin-left:30px;">
				<input type="radio" name="1" <?php echo 'disabled'; ?><?php checked( true ); ?>><?php esc_html_e( ' Give users an option to enable', 'miniorange-2-factor-authentication' ); ?> <b><?php esc_html_e( 'Remember Device', 'miniorange-2-factor-authentication' ); ?></b>
				<br><br>
				<input type="radio" name="1" <?php echo 'disabled'; ?>><?php echo 'Silently enable '; ?><b><?php esc_html_e( 'Remember Device', 'miniorange-2-factor-authentication' ); ?></b>
				</div>
				<br>
				<div>
				<?php esc_html_e( 'Remember Device for', 'miniorange-2-factor-authentication' ); ?> <input type="number" class="mo2f_table_textbox" style="width:10%; margin-left: 1%; margin-right: 1%;" name="mo2fa_device_expiry" 
								<?php
								if ( $is_register ) {
										echo '!disabled';
								} else {
										echo 'disabled';
								}
								?>
						/> <?php esc_html_e( 'days', 'miniorange-2-factor-authentication' ); ?>.
					<br><br>
					<?php esc_html_e( 'Allow', 'miniorange-2-factor-authentication' ); ?> <input type="number" class="mo2f_table_textbox" style="width:10%; margin-left: 1%; margin-right: 1%;" name="mo2fa_device_limit" 
								<?php
								if ( $is_register ) {
										echo '!disabled';
								} else {
										echo 'disabled';
								}
								?>
						/><?php esc_html_e( 'devices for users to remember', 'miniorange-2-factor-authentication' ); ?>.
					<br><br>
					<?php esc_html_e( 'Action on exceeding device limit:', 'miniorange-2-factor-authentication' ); ?>
					&emsp;
					<input type="radio" name="mo2f_rba_login_limit" value="1" <?php echo 'disabled'; ?> <?php checked( true ); ?>>
					Ask for '<b>Two Factor</b>'  &emsp;
					<input type="radio" name="mo2f_rba_login_limit" value="0"  <?php echo 'disabled'; ?>>
					Deny Access 
				</div>
				<br>
				<div class="mo2f_advanced_options_note" style="background-color: #bfe5e9;padding:12px"><b>Note:</b> <?php esc_html_e( 'Checking this option will enable', 'miniorange-2-factor-authentication' ); ?> '<b>Remember Device</b>'. <?php esc_html_e( 'When a user logs in using the remembered device, the 2nd factor will be skipped and the user will only need to provide their username and password to log in.', 'miniorange-2-factor-authentication' ); ?>.</div>		   

				<br>
				<div style="margin-top: 10px;">
					<button style="box-shadow: none;" class="button button-primary button-large" id="set_remember_device_button" target="_blank"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
				</div>
			<script type="text/javascript">
			document.getElementById("set_remember_device_button").disabled = true;
			</script>
			</form>
			<br>

		</div> 
</div>

</div>

<script>
	if(document.getElementById("rbaConfiguration_deviceExceedActionCHALLENGE2") !== null)
		document.getElementById("rbaConfiguration_deviceExceedActionCHALLENGE2").disabled = true;
	if(document.getElementById("rbaConfiguration_deviceExceedActionCHALLENGE1") !== null)
		document.getElementById("rbaConfiguration_deviceExceedActionCHALLENGE1").disabled = true;
	if(document.getElementById("rbaConfiguration_deviceExceedActionDENY1") !== null)
		document.getElementById("rbaConfiguration_deviceExceedActionDENY1").disabled = true;
	jQuery('#mo2f_hide_rba_content').hide();
	jQuery('#mo2f_activate_rba_addon').hide();
	function mo2f_rba_functionality() {
		<?php
		global $current_user_info;
		$current_user_info = wp_get_current_user();
		global $db_queries,$mo2fdb_queries;
		$upgrade_url = add_query_arg( array( 'page' => 'mo_2fa_upgrade' ), isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
		?>
		jQuery('#mo2f_hide_login_form').toggle();
	}
	</script>			
