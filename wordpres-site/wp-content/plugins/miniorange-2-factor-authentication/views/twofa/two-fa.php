<?php
/**
 * Frontend for navigation bar containing 2fa tabs.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

// Needed in both.

use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$mo_2fa_with_network_security = MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_2fa_with_network_security', 'get_option' );
if ( $mo_2fa_with_network_security ) {
	?>
	<div class="nav-tab-wrapper" >
	<?php
} else {
	?>
		<div class="nav-tab-wrapper" style="margin-top: -1%;width: 98%;">
	<?php
}
if ( current_user_can( 'administrator' ) ) {
	?>
	<button class="nav-tab" onclick="mo2f_wpns_openTab2fa(this)" id="my_account_2fa">My Account</button>
	<?php
}
?>

	<button class="nav-tab" onclick="mo2f_wpns_openTab2fa(this)" id="setup_2fa">Setup 2FA For Me</button>
	<?php
	if ( current_user_can( 'administrator' ) ) {
		?>
		<button class="nav-tab" onclick="mo2f_wpns_openTab2fa(this)" id="unlimittedUser_2fa">Login Settings</button>
		<?php
	}
	if ( current_user_can( 'administrator' ) ) {
		?>
		<button class="nav-tab" onclick="mo2f_wpns_openTab2fa(this)" id="custom_form_2fa">Custom Login Forms</button>
		<button class="nav-tab" onclick="mo2f_wpns_openTab2fa(this)" id="custom_login_2fa">Premium Features</button>
		<button class="nav-tab" onclick="mo2f_wpns_openTab2fa(this)" id="rba_2fa">AddOns</button>
		<?php

	}
	?>

</div>

<div id="mo_scan_message" style=" padding-top:8px"></div>

		<div class="mo2f_flexbox">
			<div class="mo2f_table_layout" id="setup_2fa_div">
				<?php require_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'setup-twofa.php'; ?>
			</div>

			<?php
			if ( current_user_can( 'administrator' ) ) {
				?>
							<div class="mo2f_table_layout" id="my_account_2fa_div">
							<?php require_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'account.php'; ?>
							</div>
							<div class="mo2f_table_layout" id="rba_2fa_div">
							<div>
							<h2>Addons Provided In <span style="color:red;"> PREMIUM </span>Plan</h2>
							</div>			   
								<?php
								include_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-rba.php';
								?>
							<?php
								include_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-session-control.php';
							?>
							<?php
							if ( get_option( 'mo2f_personalization_installed' ) ) {
								mo2f_personalization_description( $mo2f_user_email );
							} else {
								include_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-custom-login.php';
							}
							?>
							<?php
							if ( get_option( 'mo2f_shortcode_installed' ) ) {
								mo2f_shortcode_description( $mo2f_user_email );
							} else {
								include_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-shortcode.php';
							}
							?>
							</div>
							<div class="mo2f_table_layout" id="custom_login_2fa_div">
								<?php
								if ( get_option( 'mo2f_personalization_installed' ) ) {
									mo2f_personalization_description( $mo2f_user_email );
								} else {
									include_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-premium-feature.php';
								}
								?>
							</div>

						<div class="mo2f_table_layout" id="custom_form_2fa_div">
							<?php include_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-custom-form.php'; ?>
						</div>
						<div class="mo2f_table_layout" id="unlimittedUser_2fa_div">
							<?php include_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two-fa-unlimitted-user.php'; ?>
						</div>
						<div class="mo2f_support_flex">
							<?php include $controller . 'support.php'; ?>
						</div>
							<?php
			}
			?>
		</div>
<script>
	jQuery('#mo_2fa_2fa').addClass('nav-tab-active');

	function mo2f_wpns_openTab2fa(elmt){
		var tabname = elmt.id;
		var tabarray = ["setup_2fa","rba_2fa","custom_login_2fa","login_option_2fa", "custom_form_2fa","unlimittedUser_2fa","my_account_2fa"];
		for (var i = 0; i < tabarray.length; i++) {
			if(tabarray[i] == tabname){
				jQuery("#"+tabarray[i]).addClass("nav-tab-active");
				jQuery("#"+tabarray[i]+"_div").css("display", "block");
			}else{
				jQuery("#"+tabarray[i]).removeClass("nav-tab-active");
				jQuery("#"+tabarray[i]+"_div").css("display", "none");
			}
		}
		localStorage.setItem("2fa_last_tab", tabname);
	}
	var tour 	= '<?php echo esc_js( MoWpnsUtility::get_mo2f_db_option( 'mo2f_two_factor_tour', 'get_option' ) ); ?>';

	if(tour != 1)
		var tab = localStorage.getItem("2fa_last_tab");
	else
		var tab = '<?php echo esc_js( get_option( 'mo2f_tour_tab' ) ); ?>'; 

	if(tab && tab.length>0)
		document.getElementById(tab).click();
	else{
		document.getElementById("setup_2fa").click();
	}
</script>
