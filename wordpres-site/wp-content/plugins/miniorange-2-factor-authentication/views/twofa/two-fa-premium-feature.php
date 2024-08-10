<?php
/**
 * This file is used to advertise the all premium feature.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

 use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$setup_dir_name = dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
require $setup_dir_name;
$premium_feature_tooltip_array = array(
	'This option will provide users an alternate way of login in case their phone is lost, discharged or not with them.',
	' You can select which Two Factor methods you want to enable for your users. By default all Two Factor methods are enabled for all users of the role you have selected above.',
	' If this option is enabled then users will have an option to skip the 2FA setup prompted after initial login',
	' If this option is enabled then users can edit their email during User Enrollment with miniOrange, and they will be prompted for e-mail verification. By selecting second option, the user will be silently registered with miniOrange without the need of e-mail verification.',
	'By default 2nd Factor is enabled after password authentication. If you do not want to remember passwords anymore and just login with 2nd Factor, please select 2nd option.',
	'Users have an option to Login with Username and password or Login with just username + One Time Passcode ',
	'Checking this option will hide default login form',
);
?>

<div id = "premium_feature_phone_lost">
	<h3>What happens if my phone is lost, discharged or not with me
	<?php mo2f_tooltip_array( $premium_feature_tooltip_array[0] ); ?>
		<a href='<?php echo esc_url( $two_factor_premium_doc['What happens if my phone is lost, discharged or not with me'] ); ?>' target="_blank">
			<span class="dashicons dashicons-text-page" title="More Information" style="font-size:19px;color:#413c69;float: right;"></span>

		</a></h3>
	<p>
		<input type="checkbox" class="option_for_auth" name="mo2f_all_users_method" value="1" checked="checked" disabled>Enable Forgot Phone.
	<p>Select the alternate login method in case your phone is lost, discharged or not with you.</p>
	<input type="checkbox" class="option_for_auth" name="mo2f_all_users_method" value="1" checked="checked" disabled>KBA&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="checkbox" class="option_for_auth" name="mo2f_all_users_method" value="1" checked="checked" disabled><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_EMAIL, 'cap_to_small' ) ); ?>
	</p>
	</br><hr>

	<?php
		$current_user_info = wp_get_current_user();
		$twofa_methods     = array(
			'row_1' => array(
				MoWpnsConstants::OUT_OF_BAND_EMAIL => MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OUT_OF_BAND_EMAIL, 'cap_to_small' ),
				MoWpnsConstants::OTP_OVER_SMS      => MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_SMS, 'cap_to_small' ),
				'PHONE VERIFICATION'               => 'Phone Call Verification',
			),
			'row_2' => array(
				MoWpnsConstants::SOFT_TOKEN            => MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::SOFT_TOKEN, 'cap_to_small' ),
				MoWpnsConstants::MOBILE_AUTHENTICATION => MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::MOBILE_AUTHENTICATION, 'cap_to_small' ),
				MoWpnsConstants::PUSH_NOTIFICATIONS    => MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::PUSH_NOTIFICATIONS, 'cap_to_small' ),
			),
			'row_3' => array(
				MoWpnsConstants::GOOGLE_AUTHENTICATOR => MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::GOOGLE_AUTHENTICATOR, 'cap_to_small' ),
				MoWpnsConstants::AUTHY_AUTHENTICATOR  => MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::AUTHY_AUTHENTICATOR, 'cap_to_small' ),
				MoWpnsConstants::SECURITY_QUESTIONS   => MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::SECURITY_QUESTIONS, 'cap_to_small' ),
			),
			'row_4' => array(
				MoWpnsConstants::OTP_OVER_SMS_AND_EMAIL => MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_SMS_AND_EMAIL, 'cap_to_small' ),
				MoWpnsConstants::OTP_OVER_EMAIL         => MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_EMAIL, 'cap_to_small' ),
			),
		);


		?>
		<h3><?php esc_html_e( 'Select the specific set of authentication methods for your users', 'miniorange-2-factor-authentication' ); ?>
		<?php mo2f_tooltip_array( $premium_feature_tooltip_array[1] ); ?>
		<a href='<?php echo esc_url( $two_factor_premium_doc['Specific set of authentication methods'] ); ?>' target="_blank"><span class="dashicons dashicons-text-page" title="More Information" style="font-size:19px;color:#413c69;float: right;"></span></a></h3>
		<p>
		<input type="radio" class="option_for_auth" name="mo2f_all_users_method" value="1" checked="checked" />
				<?php esc_html_e( 'For all Users', 'miniorange-2-factor-authentication' ); ?>&nbsp;&nbsp;
				<input type="radio" class="option_for_auth2" name="mo2f_all_users_method" value="0"  />
				<?php esc_html_e( 'Specific Roles', 'miniorange-2-factor-authentication' ); ?>
				</p>
				<table class="mo2f_for_all_users" 
				<?php
				if ( ! get_site_option( 'mo2f_all_users_method' ) ) {
					echo 'hidden';}
				?>
				><tbody>
				<?php
				mo2f_show_authentication_method_set( $twofa_methods );
				?>
			</tbody>
				</table>
		<?php
		$opt     = (array) get_site_option( 'mo2f_auth_methods_for_users' );
		$copt    = array();
		$newcopt = array();
		global $wordpress_roles;
		if ( ! isset( $wordpress_roles ) ) {
			$wordpress_roles = new WP_Roles();
		}
		foreach ( $wordpress_roles->role_names as $user_id => $name ) {
			$copt[ $user_id ] = get_site_option( 'mo2f_auth_methods_for_' . $user_id );
			if ( empty( $copt[ $user_id ] ) ) {
				$copt[ $user_id ] = array( 'No Two Factor Selected' );
			}
			?>
			<span class="mo2f_display_tab mo2f_btn_premium_features" style="padding: 7px 25px;"	 ID="mo2f_role_<?php echo esc_attr( $user_id ); ?>" onclick="displayTab('<?php echo esc_js( $user_id ); ?>');" value="<?php echo esc_attr( $user_id ); ?>" 
						<?php
						if ( get_site_option( 'mo2f_all_users_method' ) ) {
							echo 'hidden';}
						?>
			> <?php echo esc_html( $name ); ?></span>

			<?php
		}
		?>
		<br><br>
		<?php
			global $wordpress_roles;
		if ( ! isset( $wordpress_roles ) ) {
				$wordpress_roles = new WP_Roles();
		}
			print '<div> ';
		foreach ( $wordpress_roles->role_names as $user_id => $name ) {
					$setting = get_site_option( 'mo2fa_' . $user_id );
					$newcopt = $copt[ $user_id ];
			?>
				<table class="mo2f_for_all_roles" id="mo2f_for_all_<?php echo esc_attr( $user_id ); ?>" hidden><tbody>
				<?php
				mo2f_show_authentication_method_set( $twofa_methods );
				?>
				</tbody>
				</div>
				</table>
				<?php
		}
			print '</div>';

		?>

	<hr>

	<h3>Skip Option for Users During User Enrollment
	<?php mo2f_tooltip_array( $premium_feature_tooltip_array[2] ); ?></h3>
	<p>
	<input type="checkbox" class="option_for_auth" name=" Skip Option for users." value="1" checked="checked" disabled> Skip Option for users.
	</p>
	</br><hr>

	<h3>Email verification of Users during User Enrollment
	<?php mo2f_tooltip_array( $premium_feature_tooltip_array[3] ); ?>
	<a href='<?php echo esc_url( $two_factor_premium_doc['Email verification of Users during Inline Registration'] ); ?>' target="_blank">
					<span class="dashicons dashicons-text-page"title="More Information" style="font-size:19px;color:#413c69;float: right;"></span>
	</a></h3>
	<p>
	<input type="radio" class="option_for_auth" name="mo2f_all_users_method" value="1" checked="checked" disabled> Enable users to edit their email address for registration with miniOrange.<br><br>
	<input type="radio" class="option_for_auth" name="mo2f_all_users_method" value="1" checked="checked" disabled>Skip e-mail verification by user.
	</p>

</br><hr>

	<h3>Select Login Screen Options
		<a href='<?php echo esc_url( $two_factor_premium_doc['Select login screen option'] ); ?>'  target="_blank">
						<span class="dashicons dashicons-text-page" title="More Information" style="font-size:19px;color:#413c69;float: right;"></span>
	</a></h3>
	<input type="radio" class="option_for_auth" name="mo2f_all_users_method" value="1" checked="checked" disabled> Login with password + 2nd Factor <span style="color: red">(Recommended)</span>
	<?php mo2f_tooltip_array( $premium_feature_tooltip_array[4] ); ?>
	</br>
	</br>
	<input type="radio" class="option_for_auth" name="mo2f_all_users_method" value="0" disabled>
		Login with 2nd Factor only <span style="color: red">(No password required)
			<a onclick="mo2f_login_with_username_only()">&nbsp;&nbsp;See Preview</a></span>
			<?php mo2f_tooltip_array( $premium_feature_tooltip_array[5] ); ?>
	</br>     	

	<div id="mo2f_login_with_username_only" style="display: none;">
		<?php
		echo '<div style="text-align:center;"><img  style="margin-top:5px;" src="' . esc_url( $login_with_usename_only_url ) . '"></div><br>';
		?>
	</div>
	</br>
	<input type="checkbox" class="option_for_auth" value="0" disabled>I want to hide default login form.
	<a onclick="mo2f_hide_login_form()">&nbsp;&nbsp;See Preview</a>   
	<?php mo2f_tooltip_array( $premium_feature_tooltip_array[6] ); ?>
	<div id="mo2f_hide_login" style="display: none;">
		<?php
			echo '<div style="text-align:center;"><img  style="margin-top:5px;" src="' . esc_url( $hide_login_form_url ) . '"></div><br>';
		?>
	</div>

</div>

<?php
/**
 * This function is used to show the information about premium plugin feature.
 *
 * @param string $mo2f_addon_feature contains the info about premium feature.
 * @return void
 */
function mo2f_tooltip_array( $mo2f_addon_feature ) {
	echo '<div class="mo2f_tooltip_addon">
			<span class="dashicons dashicons-info mo2f_info_tab"></span>
			<span class="mo2f_tooltiptext_addon" >' . esc_html( $mo2f_addon_feature ) . '
			</span>
		</div>';
}

/**
 * Shows 2FA methods.
 *
 * @param array $twofa_methods Authentication methods array.
 * @return void
 */
function mo2f_show_authentication_method_set( $twofa_methods ) {
	foreach ( $twofa_methods as $key => $values ) {
		?>
			<tr>
		<?php
		foreach ( $values as $method_value => $method_name ) {
			?>
	<td>
	<input type='checkbox'  name='mo2f_authmethods[]'  value="<?php echo esc_attr( $method_value ); ?>" disabled/> 	
			<?php
			printf(
			/* translators: %s: Name of the 2fa method */
				esc_html__( '%s', 'miniorange-2-factor-authentication' ),
				esc_html( $method_name )
			);
			?>
&nbsp;&nbsp;
	</td>
				<?php
		}
		?>
			</tr>
		<?php
	}

}
?>
<script type="text/javascript">
	function mo2f_login_with_username_only()
	{
		jQuery('#mo2f_login_with_username_only').toggle();
	}
	function mo2f_hide_login_form()
	{
		jQuery('#mo2f_hide_login').toggle();
	}

	jQuery('.mo2f_display_tab').hide();
	jQuery('.mo2f_for_all_roles').hide();
	jQuery('.mo2f_for_all_users').show();

	function displayTab(role){
		jQuery('.mo2f_display_tab').removeClass("mo2f_blue_premium_features");
		jQuery('.mo2f_display_tab').addClass("mo2f_btn_premium_features");
		jQuery('#mo2f_role_'+role).removeClass("mo2f_btn_premium_features");
		jQuery('#mo2f_role_'+role).addClass("mo2f_blue_premium_features");
		jQuery('.mo2f_for_all_roles').hide();
		jQuery('#mo2f_for_all_'+role).show();
	}
	jQuery(".option_for_auth").click(function(){
		jQuery('.mo2f_display_tab').hide();
		jQuery('.mo2f_for_all_roles').hide();
		jQuery('.mo2f_for_all_users').show();
	})
	jQuery(".option_for_auth2").click(function(){
		jQuery('.mo2f_display_tab').show();
		jQuery('.mo2f_for_all_users').hide();
	}
	)
</script>
