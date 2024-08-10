<?php
/**
 * This file shows the plugin settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

// Needed in both.

use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mo2f_settings = array(
	'Enable 2FA for Users'                         => array(
		'premium_doc_link'      => 'Enable/disable 2-factor Authentication',
		'form_id'               => 'mo2f_enable_2fa_for_users',
		'mo_2f_two_factor_ajax' => 'mo2f_enable_disable_twofactor',
		'checkbox_id'           => 'mo2f_enable_2faa',
		'checkbox_name'         => 'mo2f_enable_2fa',
		'checkbox_option'       => 'mo2f_activate_plugin',
		'settings_note'         => 'If you enable this checkbox, Two-Factor will be invoked for any user during login.',
		'settings_tooltip'      => 'Disable this to temporarily disable 2FA prompt for all users',
	),
	'Enable plugin log'                            => array(
		'form_id'               => 'mo2f_enable_debuglog_form_id',
		'mo_2f_two_factor_ajax' => 'mo2f_enable_disable_debug_log',
		'checkbox_id'           => 'mo2f_debug_log_id',
		'checkbox_name'         => 'mo2f_enable_debug_log',
		'checkbox_option'       => 'mo2f_enable_debug_log',
		'settings_note'         => 'If you enable this checkbox, the plugin debug logs will be enabled.',
		'settings_tooltip'      => 'Plugin debug log file is very helpful to debug the issue in case you face any.',
	),
	'On the Fly 2FA Configuration'                 => array(
		'form_id'               => '',
		'mo_2f_two_factor_ajax' => 'mo2f_enable_disable_inline',
		'checkbox_id'           => 'mo2f_inline_registration',
		'checkbox_name'         => 'mo2f_inline_registration',
		'checkbox_option'       => 'mo2f_inline_registration',
		'settings_note'         => 'If you enable this checkbox, 2FA setup will be forced for all users after Initial login.',
		'settings_tooltip'      => 'If you disable this checkbox, user enrollment (forcing users to setup 2FA after initial login) will not be done',
	),
	'Enable the login with all configured methods' => array(
		'form_id'               => '',
		'mo_2f_two_factor_ajax' => 'mo2f_enable_disable_configurd_methods',
		'checkbox_id'           => 'mo2f_nonce_enable_configured_methods',
		'checkbox_name'         => 'mo2f_nonce_enable_configured_methods',
		'checkbox_option'       => 'mo2f_nonce_enable_configured_methods',
		'settings_note'         => 'If you enable this checkbox, users will have a choice to login using any of the methods that is already configured.',
		'settings_tooltip'      => 'It will help the user to login with any of the configured methods',
	),

);

/**
 * Show the roles and checkboxes to enable/disable 2FA.
 *
 * @return void
 */
function miniorange_2_factor_user_roles() {
	include dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
	global $wp_roles;

	$upgrade_url = add_query_arg( array( 'page' => 'mo_2fa_upgrade' ), isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null );?>

	<div><span style="font-size:16px;">Roles<div style="float:right;">Custom Redirection URL <a href="<?php echo esc_url( $upgrade_url ); ?>" style="color: red">[ PREMIUM ]</a>&nbsp;&nbsp;&nbsp;
	</span></a>
	</div></span><br /><br />
	<?php
	if ( is_multisite() ) {
		$first_role           = array( 'superadmin' => 'Superadmin' );
		$wp_roles->role_names = array_merge( $first_role, $wp_roles->role_names );
	}
	foreach ( $wp_roles->role_names as $id => $name ) {
		$setting = get_option( 'mo2fa_' . $id );
		?>
		<div>
			<input type="checkbox" name="role" value="<?php echo 'mo2fa_' . esc_attr( $id ); ?>"
			<?php

			if ( get_option( 'mo2fa_' . $id ) ) {
				echo 'checked';
			} else {
				echo 'unchecked';
			}
			?>
				/>
			<?php
			echo esc_html( $name );
			?>
			<input type="text" class="mo2f_table_textbox" style="width:50% !important;float:right;" id="<?php echo 'mo2fa_' . esc_attr( $id ); ?>_login_url" value="<?php echo esc_url( site_url() ); ?>"
			<?php
				echo 'disabled';
			?>
			/>	
		</div>
		<br/>
		<?php
	}
	print '</div>';
}

?>
<?php
if ( current_user_can( 'administrator' ) ) {
	?>
	<div id="disable_two_factor_tour">

		<?php
		foreach ( $mo2f_settings as $form_title => $form_components ) {
			?>
			<h2>
				<?php
				printf(
				/* translators: %s: Name of the 2fa settings */
					esc_html__( '%s', 'miniorange-2-factor-authentication' ),
					esc_html( $form_title )
				);
				mo2f_setting_tooltip_array( $form_components['settings_tooltip'] );

				if ( isset( $form_components['premium_doc_link'] ) ) {
					?>
					<a href='<?php echo esc_url( $two_factor_premium_doc[ $form_components['premium_doc_link'] ] ); ?>' target="_blank">
					<span class="dashicons dashicons-text-page" title="More Information" style="font-size:19px;color:#4a47a3;float: right;"></span>

					</a>
					<?php
				}
				?>
			</h2>

			<div>
				<form name="f" method="post" action="" >
					<label class="mo_wpns_switch" style="float: right">
					<input type="hidden" id="mo_2f_two_factor_ajax" value="<?php echo esc_attr( $form_components['mo_2f_two_factor_ajax'] ); ?>">
					<input type="checkbox" onChange="mo2f_toggle_checkbox(this)" style="padding-top: 50px;" id="<?php echo esc_attr( $form_components['checkbox_id'] ); ?>"
						name="<?php echo esc_attr( $form_components['checkbox_name'] ); ?>"
						value="<?php MoWpnsUtility::get_mo2f_db_option( $form_components['checkbox_option'], 'get_option' ); ?>"<?php checked( MoWpnsUtility::get_mo2f_db_option( $form_components['checkbox_option'], 'get_option' ) ); ?>/>
					<span class="mo_wpns_slider"></span>
					</label>
					<p><?php echo esc_html( $form_components['settings_note'] ); ?>
					</p>
				</form>
			</div>
			<?php if ( MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_debug_log', 'site_option' ) === '1' && 'mo2f_enable_debuglog_form_id' === $form_components['form_id'] ) { ?>
				<form name="f" method="post" action="" id="mo2f_download_log_file">
						<input type="submit" class="button button-primary" value="Download log file"
					id="mo2f_debug_form"  name= "mo2f_debug_form">
						<input type="button" class="button button-primary" value="Delete log file"
						id="mo2f_debug_delete_form"  name= "mo2f_debug_delete_form">
						<input type="hidden" id="mo2f_download_log" name="mo_wpns_feedback_nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'mo-wpns-feedback-nonce' ) ); ?>"/>
						<input type="hidden" id="mo2f_download_log" name="option"
						value="log_file_download"/>
				</form>
				<form name="f" method="post" action="" id="mo2f_delete_log_file">
					<input type="hidden" id="mo2f_delete_log" name="nonce"
					value="<?php echo esc_attr( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>"/>
						<input type="hidden" id="mo2f_delete_logs" name="option"
					value="log_file_delete"/>
				</form>
			<?php } ?>
			</br> <hr>
		<?php } ?>

<h2>Should users be given a grace period or should they be directly enforced for 2FA setup?
		<?php mo2f_setting_tooltip_array( $settings_tab_tooltip_array[0] ); ?>
	</h2>
	<div>
	<form name="f" method="post" action="">			
			<input type="hidden" id="mo2f_nonce_enable_grace_period" name="mo2f_nonce_enable_grace_period" value="<?php echo esc_attr( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>"/>
			<input type="radio" name="mo2f_grace_period" id="mo2f_no_grace_period" value="off" 	<?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period', 'get_option' ) === 'off' ); ?>/>
			<span> Users should be directly enforced for 2FA setup </span><br><br>

			<input type="radio" name="mo2f_grace_period" id="mo2f_use_grace_period" value="on" 	<?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period', 'get_option' ) === 'on' ); ?>/>
			<span> Give users a grace period to configure 2FA (Users will be enforced to setup 2FA after grace period expiry).&nbsp;&nbsp;&nbsp; </span>						
			</br>			
			<div id="mo2f_grace_period_show" style="display: <?php echo( get_option( 'mo2f_grace_period' ) === 'on' ) ? 'block' : 'none'; ?>;" >
				</br>
				<span style="font-size:15px;"><b>Grace Period:</b></span>   
				<input type="number" id="mo2f_grace_period" name= "mo2f_grace_period_value" value="<?php echo esc_attr( get_option( 'mo2f_grace_period_value', 1 ) ); ?>" min=1 max=10>					  
				<input type="radio" name="mo2f_grace_period_type" id="mo2f_grace_hour" value="hours" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period_type', 'get_option' ) === 'hours' ); ?>/> hours					  
				<input type="radio" name="mo2f_grace_period_type" id="mo2f_grace_day" value= "days" 	<?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period_type', 'get_option' ) === 'days' ); ?>/> days				
				</br>	
			</div>
		</br>     
		<input type="button" style="float: left;" id="mo2f_save_grace_period"  name="submit" value="Save Grace Period" class="button button-primary button-large "/>	
	</form>
	</div>					
	</br></br></br><hr>	

	<input type="hidden" name="option" value="" />
	<span>
		<h2>Select User Roles to enable 2-Factor for <b  style="font-size: 70%;color: red;">(Upto 3 users in Free version)</b>
		<?php mo2f_setting_tooltip_array( $settings_tab_tooltip_array[1] ); ?>
		<a href= '<?php echo esc_url( $two_factor_premium_doc['Enable 2FA Role Based'] ); ?>' target="_blank">
		<span class="dashicons dashicons-text-page" title="More Information" style="font-size:19px;color:#4a47a3;float: right;"></span>
		</a></h2>
	</br>
	<span>
		<?php
		echo esc_html( miniorange_2_factor_user_roles() );
		?>
		<br>
		</span>
		<input type="submit" style="float: left;" id="save_role_2FA"  name="submit" value="Enable 2FA for Selected Roles" class="button button-primary button-large" />
		<br>
	</span>
	<br><br>



	<script>
		jQuery(document).ready(function($){
			jQuery(function(){			

				jQuery("#mo2f_use_grace_period").click(function()
				{
					if(jQuery(this).is(':checked'))
					{
						jQuery("#mo2f_grace_period_show").show();								
						jQuery("#mo2f_grace_period").focus();
					}
				});
				jQuery("#mo2f_no_grace_period").click(function()
				{
					if(jQuery(this).is(':checked'))
					{
						jQuery("#mo2f_grace_period_show").hide();
					}
				});
				jQuery("#mo2f_grace_hour").click(function(){
					jQuery("#mo2f_grace_period").focus();
				});
				jQuery("#mo2f_grace_day").click(function(){
					jQuery("#mo2f_grace_period").focus();
				});		
			});
		});
		jQuery("#mo2f_grace_period").keypress(function(e) {
				if (e.which === 13) {
					e.preventDefault();
					jQuery("#mo2f_save_grace_period").click();
				}

		});

		jQuery('#mo2f_debug_delete_form').click(function(){

		var data =  {
				'action'                        : 'mo_two_factor_ajax',
				'mo_2f_two_factor_ajax'         : 'mo2f_delete_log_file',
				'nonce'         :  jQuery('#mo2f_delete_log').val(),

			};
			jQuery.post(ajaxurl, data, function(response) {
				if (response['data'] === "true"){
					success_msg("Log file deleted.");
				}else{
					error_msg("Log file is not available.");
				}
			});
		});

		jQuery('#mo2f_save_grace_period').click(function(){

				var data =  {
							'action'                        : 'mo_two_factor_ajax',
							'mo_2f_two_factor_ajax'         : 'mo2f_grace_period_save',
							'nonce'					        :  jQuery('#mo2f_nonce_enable_grace_period').val(),
							'mo2f_graceperiod_use'          :  jQuery('#mo2f_use_grace_period').is(":checked"),
							'mo2f_graceperiod_no'           :  jQuery('#mo2f_no_grace_period').is(":checked"),
							'mo2f_graceperiod_hour'         :  jQuery('#mo2f_grace_hour').is(":checked"),
							'mo2f_graceperiod_day'          :  jQuery('#mo2f_grace_day').is(":checked"),
							'mo2f_graceperiod_value'        :  jQuery('#mo2f_grace_period').val(),
						};
				jQuery.post(ajaxurl, data, function(response) {
					if (response['data'] === "true"){
						success_msg("Grace period saved successfully");
					}else if(response['data'] === 'invalid_input'){
						error_msg("Please enter valid input");
					}else{
						error_msg("Error while saving the settings");
					}
				});
		});

		function mo2f_toggle_checkbox(element){
			var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>';
			var parent = jQuery(element).parent();
			var child = parent.children('#mo_2f_two_factor_ajax');
			var mo2f_ajax_function = child.val();
			var data =  {
				'action'                        : 'mo_two_factor_ajax',
				'mo_2f_two_factor_ajax'         : child.val(),
				'nonce'         				:  nonce,
				'mo2f_enable_2fa_settings'      :  jQuery(element).is(":checked"),
			};
			jQuery.ajax({
				url: ajaxurl,
				type: "POST",
				dataType: "json",
				data: data,
				success: function (response) {
					if (response.success) {
						success_msg(response.data.message);
					} else {
						error_msg(response.data.error);
					}
				}
			});

		}
		jQuery('#previewwploginpage').hide();
		jQuery('#showpreviewwploginpage').on('click', function() {
			if ( jQuery("#previewwploginpage").is(":visible") ) {
				jQuery('#previewwploginpage').slideToggle('slow');
			} else if ( jQuery("#previewwploginpage").is(":hidden") ) {
				jQuery('#previewwploginpage').slideToggle('slow');
			}
		});
		jQuery("#save_role_2FA").click(function(){
			var enabledrole = [];
			$.each($("input[name='role']:checked"), function(){
				enabledrole.push($(this).val());
			});
			var mo2fa_administrator_login_url   =   $('#mo2fa_administrator_login_url').val();
			var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>';
			var data =  {
				'action'                        : 'mo_two_factor_ajax',
				'mo_2f_two_factor_ajax'         : 'mo2f_role_based_2_factor',
				'nonce'                         :  nonce,
				'enabledrole'                   :  enabledrole,
				'mo2fa_administrator_login_url' :  mo2fa_administrator_login_url
			};
			jQuery.post(ajaxurl, data, function(response) {
				if (response['success']){
					success_msg("Settings are saved.");
				}
				else
				{
					jQuery('#mo2f_confirmcloud').css('display', 'none');
					jQuery( "#singleUser" ).prop( "checked", false );
					jQuery('#single_user').css('display', 'none');

					error_msg("<b>You are not authorized to perform this action</b>. Only <b>"+response['data']+"</b> is allowed. For more details contact miniOrange.");
				}
			});
		});
	</script>

	<?php
}
?>
</div>
<?php
/**
 * Shows description of a feature in a tooltip.
 *
 * @param string $mo2f_addon_feature Feature description.
 * @return void
 */
function mo2f_setting_tooltip_array( $mo2f_addon_feature ) {
	echo '<div class="mo2f_tooltip_addon">
            <span class="dashicons dashicons-info mo2f_info_tab"></span>
            <span class="mo2f_tooltiptext_addon" >' . esc_html( $mo2f_addon_feature ) . '
            </span>
        </div>';
}
?>
