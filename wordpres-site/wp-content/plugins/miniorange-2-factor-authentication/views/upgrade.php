<?php
/**
 * Pricing page of the plugin.
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $mo2fdb_queries, $main_dir;
$user                   = wp_get_current_user();
$is_customer_registered = 'MO_2_FACTOR_PLUGIN_SETTINGS' === get_option( 'mo_2factor_user_registration_status' );

if ( isset( $_GET['page'] ) && sanitize_text_field( ( wp_unslash( $_GET['page'] ) ) ) === 'mo_2fa_upgrade' ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
	?><br><br>
	<?php
}
echo '
<a class="mo2f_back_button" style="font-size: 16px; color: #000;" href="' . esc_url( $two_fa ) . '"><span class="dashicons dashicons-arrow-left-alt" style="vertical-align: bottom;"></span> Back To Plugin Configuration</a>';
?>
<br><br>

<?php
wp_register_style( 'mo2f_upgrade_css', $main_dir . '/includes/css/upgrade.min.css', array(), MO2F_VERSION );
wp_register_style( 'mo2f_font_awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css', array(), MO2F_VERSION );
wp_enqueue_style( 'mo2f_upgrade_css' );
wp_enqueue_style( 'mo2f_font_awesome' );

global $image_path;
?>
<span class="cd-switch"></span>

<h2 class="mo2fa-heading-plan-comparision">WordPress 2FA <span class="mo2fa-text-red">Plans & Pricing</span></h2>
<p class="mo2fa-text-center">Upgrade Your Security with Preferred Plan</p>

<div class="mo2fa-pricing-section">
	<div class="mo2fa-mo2fa-twofa-pricing-div">
		<h3 class="mo2fa-twofa-pricing-heading">STARTER
		</h3> <sub class="mo2fa-sub-heading">2FA For 100 users</sub>
		<div class="mo2fa-one-row-price">
			<div class="item-one">
				<p class="mt"><span class="display-1"><span>$</span><span id="dollar_mo_basic_price" class="mo_premium_price">99</span><sub class="year">/year</sub><sup>*</sup></span></p>
			</div>
			<div class="container-dropdown discount-price">
				<div class="select-dropdown">
					<span class="mo2f_no_of_sites">2FA on no. of sites :</span>
					<select class="mo2f-dropdown-width inst-btn2" id="mo_basic_price" onchange="update_site('mo_basic_price')">
						<option value="99" data-price="99"> 1 </option>
						<option value="178" data-price="178"> 2</option>
						<option value="248" data-price="248"> 3 </option>
						<option value="318" data-price="318"> 4 </option>
						<option value="378" data-price="378"> 5</option>
					</select>
				</div>
			</div>
		</div>
		<div class="text-align">
			<div class="mo2fa_text-align-center">
				<div id="mo2fa_custom_my_plan_2fa_mo">
					<?php if ( isset( $is_customer_registered ) && $is_customer_registered ) { ?>
						<a onclick="mo2f_upgradeform('wp_security_two_factor_basic_plan' )" target="blank" class="mo2fa-license-btn-2fa-premise mo2f-license-btn-2fa">UPGRADE NOW</a>
					<?php } else { ?>
						<a onclick="mo2f_register_and_upgradeform('wp_security_two_factor_basic_plan','2fa_plan')" target="blank" class=" mo2fa-license-btn-2fa-premise mo2f-license-btn-2fa">UPGRADE NOW</a>
					<?php } ?>
			</div>
		</div>
		</div>
		<div class="mo2f-feature-points">
			<div class="flex-row-align-center mt-4">
				<img class="mo2fa-feature-point" src="<?php echo esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ); ?>/miniorange-2-factor-authentication/includes/images/Tick-Mark-1.webp"/>
				<span class="mo2fa-pricing-feature">Role-Based 2FA</span>
			</div>
			<div class="flex-row-align-center mt-4">
				<img class="mo2fa-feature-point" src="<?php echo esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ); ?>/miniorange-2-factor-authentication/includes/images/Tick-Mark-1.webp">
				<span class="mo2fa-pricing-feature">Backup Login Methods</span>
			</div>
			<div class="flex-row-align-center mt-4">
				<img class="mo2fa-feature-point" src="<?php echo esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ); ?>/miniorange-2-factor-authentication/includes/images/Tick-Mark-1.webp"/>
				<span class="mo2fa-pricing-feature"><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_EMAIL, 'cap_to_small' ) ); ?>/SMS*</span>
			</div>
			<div class="flex-row-align-center mt-4">
				<img class="mo2fa-feature-point" src="<?php echo esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ); ?>/miniorange-2-factor-authentication/includes/images/Tick-Mark-1.webp">
				<span class="mo2fa-pricing-feature">Google/Microsoft Authenticator</span>
			</div>
			<div class="flex-row-align-center mt-4">
				<img class="mo2fa-feature-point" src="<?php echo esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ); ?>/miniorange-2-factor-authentication/includes/images/Tick-Mark-1.webp">
				<span class="mo2fa-pricing-feature">Grace Period For Users To Configure 2FA</span>
			</div>
			<div class="mo2fa-basic-div-space"> </div>
			<div class="flex-read-more-align mt-4">
				<a href="#mo2fa-read-more" class="mo2fa_read_more mo2f_read_more_buttons"> Read More
				</a>
			</div>
		</div>
	</div>

	<div class="mo2fa-mo2fa-twofa-pricing-div">
		<h3 class="mo2fa-twofa-pricing-heading">ENTERPRISE
		</h3><sub class="mo2fa-sub-heading">2FA For Unlimited Users</sub>
		<div class="mo2fa-one-row-price">
			<div class="item-one">
				<p class="mt"><span class="display-1"><span>$</span><span id="dollar_mo_lms_price" class="mo_premium_price">199</span><sub class="year">/year</sub><sup>*</sup></span></p>
			</div>
			<div class="container-dropdown discount-price">
				<div class="select-dropdown">
				<span class="mo2f_no_of_sites">2FA on no. of sites :</span>
					<select class="mo2f-dropdown-width inst-btn2" id="mo_lms_price" onchange="update_site('mo_lms_price')">
						<option value="199" data-price="199"> 1 </option>
						<option value="298" data-price="298"> 2 </option>
						<option value="398" data-price="398"> 3 </option>
						<option value="498" data-price="498"> 4 </option>
						<option value="598" data-price="598"> 5 </option>
					</select>
				</div>
			</div>
		</div>
		<div class="text-align">
			<div class="mo2fa_text-align-center">
								<div id="mo2fa_custom_my_plan_2fa_mo">
						<?php if ( isset( $is_customer_registered ) && $is_customer_registered ) { ?>
							<a onclick="mo2f_upgradeform('wp_security_two_factor_ecommerce_plan' )" target="blank" class="mo2fa-license-btn-2fa-premise mo2f-license-btn-2fa">UPGRADE NOW</a>
						<?php } else { ?>
							<a onclick="mo2f_register_and_upgradeform('wp_security_two_factor_ecommerce_plan','2fa_plan')" target="blank" class="mo2fa-license-btn-2fa-premise mo2f-license-btn-2fa">UPGRADE NOW</a>
						<?php } ?>
					</div>
			</div>
		</div>	
		<div class="mo2f-feature-points">
			<div class="flex-row-align-center mt-4">
				<img class="mo2fa-feature-point" src="<?php echo esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ); ?>/miniorange-2-factor-authentication/includes/images/Tick-Mark-1.webp">
				<span class="mo2fa-pricing-feature"><b>All Features In STARTER Plan</b></span>
			</div>

			<div class="flex-row-align-center mt-4">
				<img class="mo2fa-feature-point" src="<?php echo esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ); ?>/miniorange-2-factor-authentication/includes/images/Tick-Mark-1.webp">
				<span class="mo2fa-pricing-feature" >2FA Supported On WordPress Default, WooCommerce and Ultimate Member Login Forms</span>
			</div>

			<div class="flex-row-align-center mt-4">
				<img class="mo2fa-feature-point" src="<?php echo esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ); ?>/miniorange-2-factor-authentication/includes/images/Tick-Mark-1.webp">
				<span class="mo2fa-pricing-feature">Skip 2FA For Trusted Devices</span>
			</div>

			<div class="flex-row-align-center mt-4">
				<img class="mo2fa-feature-point" src="<?php echo esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ); ?>/miniorange-2-factor-authentication/includes/images/Tick-Mark-1.webp">
				<span class="mo2fa-pricing-feature">Login With Username + 2FA (Passwordless Login)</span>
			</div>

			<div class="flex-row-align-center mt-4">
				<img class="mo2fa-feature-point" src="<?php echo esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ); ?>/miniorange-2-factor-authentication/includes/images/Tick-Mark-1.webp">
				<span class="mo2fa-pricing-feature">Restrict Simultaneous Multiple Sessions</span>
			</div>

			<div class="flex-row-align-center mt-4">
				<img class="mo2fa-feature-point" src="<?php echo esc_url( dirname( plugin_dir_url( dirname( __FILE__ ) ) ) ); ?>/miniorange-2-factor-authentication/includes/images/Tick-Mark-1.webp">
				<span class="mo2fa-pricing-feature">White Labelling </span>
			</div>
			<div class="mo2fa-enter-div-space"> </div>
			<div class="flex-read-more-align mt-4">
				<a href="#mo2fa-read-more" class="mo2fa_read_more mo2f_read_more_buttons"> Read More 
				</a> 
			</div>

		</div>
	</div>

	<!-- End of enterprise plan -->

	<div class="mo2fa-mo2fa-twofa-pricing-div">
		<h3 class="mo2fa-twofa-pricing-heading">ALL INCLUSIVE
		</h3><sub class="mo2fa-sub-heading">2FA For Unlimited Users</sub>
		<div class="mo2fa-one-row-price">
			<div class="item-one">
				<p class="mt"><span class="display-1"><span>$</span><span id="dollar_mo_membership_price" class="mo_premium_price">249</span><sub class="year">/year</sub><sup>*</sup></span><br></p>
			</div>
				<div class="container-dropdown discount-price">
					<div class="select-dropdown">
					<span class="mo2f_no_of_sites">2FA on no. of sites :</span>
						<select class="mo2f-dropdown-width inst-btn2" id="mo_membership_price" onchange="update_site('mo_membership_price')">
							<option value="249" data-price="249"> 1 </option>
							<option value="348" data-price="348"> 2 </option>
							<option value="448" data-price="448"> 3 </option>
							<option value="548" data-price="548"> 4 </option>
							<option value="648" data-price="648"> 5 </option>
						</select>
				</div>
			</div>
		</div>
		<div class="text-align">
			<div class="mo2fa_text-align-center">
					<div id="mo2fa_custom_my_plan_2fa_mo">
						<?php if ( isset( $is_customer_registered ) && $is_customer_registered ) { ?>
							<a onclick="mo2f_upgradeform('wp_security_two_factor_business_plan' )" target="blank" class="mo2fa-license-btn-2fa-premise mo2f-license-btn-2fa">UPGRADE NOW</a>
						<?php } else { ?>
							<a onclick="mo2f_register_and_upgradeform('wp_security_two_factor_business_plan','2fa_plan')" target="blank" class="mo2fa-license-btn-2fa-premise mo2f-license-btn-2fa">UPGRADE NOW</a>
						<?php } ?>
					</div>
			</div>
		</div>

	<div class="mo2f-feature-points">
		<div class="flex-row-align-center mt-4">
			<img class="mo2fa-feature-point" src="https://plugins.miniorange.com/wp-content/uploads/2023/10/Tick-Mark-1.webp">
			<span class="mo2fa-pricing-feature"><b>All Features In ENTERPRISE Plan</b></span>
		</div>

		<div class="flex-row-align-center mt-4">
			<img class="mo2fa-feature-point" src="https://plugins.miniorange.com/wp-content/uploads/2023/10/Tick-Mark-1.webp">
			<span class="mo2fa-pricing-feature">2FA Supported On All Login Forms</span>
		</div>

		<div class="flex-row-align-center mt-4">
			<img class="mo2fa-feature-point" src="https://plugins.miniorange.com/wp-content/uploads/2023/10/Tick-Mark-1.webp">
			<span class="mo2fa-pricing-feature">Prevent credential Sharing</span>
		</div>

		<div class="flex-row-align-center mt-4">
			<img class="mo2fa-feature-point" src="https://plugins.miniorange.com/wp-content/uploads/2023/10/Tick-Mark-1.webp">
			<span class="mo2fa-pricing-feature">Option To Reconfigure 2FA For Users</span>
		</div>

		<div class="flex-row-align-center mt-4">
			<img class="mo2fa-feature-point" src="https://plugins.miniorange.com/wp-content/uploads/2023/10/Tick-Mark-1.webp">
			<span class="mo2fa-pricing-feature">OTP Over WhatsApp Add-on</span>
		</div>

		<div class="flex-row-align-center mt-4">
			<br>
		</div>

		<div class="flex-row-align-center mt-4">
			<br>
		</div>
		<div class="flex-row-align-center mt-4">
			<br>
		</div>

		<div class="flex-read-more-align mt-4">
			<a  href="#mo2fa-read-more" class="mo2fa_read_more mo2f_read_more_buttons"> Read More 
			</a>
		</div>
		</div> 
	</div>

</div>
<br>

<div class="mo2fa-unlimited-sites-note"> 
		<?php
		printf(
			esc_html(
				/* Translators: %s: bold tags and links*/
				__(
					'%1$sMore than 5 sites?: %2$sReach out to us at %3$smfasupport@xecurify.com%4$s with no. of sites and get a quote.',
					'miniorange-2-factor-authentication'
				)
			),
			'<b class="mo2fa_note">',
			'</b>',
			'<a href="mailto:mfasupport@xecurify.com" class="mo2fa-note-email-link">',
			'</a>'
		);
		?>
	</div>
<script>
	jQuery("dollar_mo_basic_price").click();
	jQuery("dollar_mo_ecommerce_price").click();
	jQuery("dollar_mo_all_inclusive_price").click();

	function update_site(plan_name) {	
		var sites = document.getElementById(plan_name).value;
		var users_addion = parseInt(sites);
		document.getElementById("dollar_"+plan_name).innerHTML = + users_addion;
	}

	function mo2f_upgradeform(planname) {
		//check if the customer is logged in or created in the plugin or not using account setup tab
		const url = `https://portal.miniorange.com/initializepayment?requestOrigin=${planname}`;

		var nonce = '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
		var data = {
			'action': 'wpns_login_security',
			'wpns_loginsecurity_ajax': 'update_plan',
			'planname': '2fa_plan',
			'planType': planname,
			'nonce'    :nonce
		}
		jQuery.post(ajaxurl, data, function(response) {});
		window.open(url, "_blank");
	}
	function mo2f_register_and_upgradeform(planType, planname) {
		jQuery('#requestOrigin').val(planType);
		jQuery('input[name="requestOrigin"]').val(planType);
		var nonce = '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
		var data = {
			'action': 'wpns_login_security',
			'wpns_loginsecurity_ajax': 'wpns_all_plans',
			'planname': planname,
			'planType': planType,
			'nonce'   :nonce
		}
		localStorage.setItem("2fa_last_tab", 'my_account_2fa');
		window.location.href = '<?php echo esc_url( admin_url() . 'admin.php?page=mo_2fa_two_fa' ); ?>';
		jQuery.post(ajaxurl, data, function(response) {});
	}
   
	function showData(e){
		var parent = e.parentElement
		var x = GetElementInsideContainer(parent, "plugin-features");
		var H = document.createElement("i");
		let childarry=e.childNodes
		let childelement=childarry[1];
		if(x.style.display == "none"){
			x.style.display = "block";
			H.setAttribute("class","fa fa-minus-circle");
			e.replaceChild(H,childarry[1]);
		}else{
			x.style.display = "none";
			H.setAttribute("class","fa fa-plus-circle");
			e.replaceChild(H,childarry[1]);
		}
	}

	function GetElementInsideContainer(parentElement, childID) {
		var elm = {};
		var elms = parentElement.getElementsByTagName("*");
		for (var i = 0; i < elms.length; i++) {
			if (elms[i].id === childID) {
				elm = elms[i];
				break;
			}
		}
		return elm;
	}
</script>

<div class="mo2fa-plan-comparision-outer-box saml-scroll">
	<h2 class="mo2fa-heading-plan-comparision">Detailed <span class="mo2fa-text-red">Feature Comparison Of Plans</span></h2>
	<p class="mo2fa-text-center">Compare What you get In Every Plan WordPress 2FA or WordPress Two-Factor Authentication Plans</p>
<br>
	<div class="plan-comparison">
		<table class="mo2fa-comparision-table-pricing">
		<thead>
			<tr class="table-heading-border">
				<th class="table-heading" style="width:30%; text-align:left;">Features</th>
				<th class="table-heading">STARTER 2FA</th>
				<th class="table-heading">ENTERPRISE 2FA</th>
				<th class="table-heading">All INCLUSIVE 2FA</th>
			</tr>
		</thead>
		<tbody>
			<tr class="table-row">
				<td>
				<div> &nbsp;&nbsp;Unlimited Users </div>
				</td>
				<td class="table-checks">
				Upto 100 Users
				</td>
				<td class="table-checks">
				<i class="fa fa-check"></i>
				</td>
				<td class="table-checks">
				<i class="fa fa-check"></i>
				</td>
			</tr>
			<tr class="table-row mo2fa-add-on-table">
					<td colspan="6" class="mo2fa-add-on-table">
					<div class="plugin-data" id="mo2fa-read-more">
					&nbsp;&nbsp;Authentication Methods
					</div>
					<div id="plugin-features" class="plugin-features-class">
						<table class="mo2fa-add-on-table">
						<tbody class="mo2fa-add-on-table">
							<tr class="table-heading">
								<th class="table-heading" style="width:30%; text-align:left;"></th>
								<th class="table-heading"></th>
								<th class="table-heading"></th>
								<th class="table-heading"></th>
							</tr>
							<tr class="table-row">
							<td>		
								<div class="plugin-data" id="plugin-data" onclick="showData(this)">
								<i class="fa fa-plus-circle table-plus-icon" aria-hidden="true" style="display:contents"></i>&nbsp;TOTP Based Authenticators
								</div>
								<div id="plugin-features" class="plugin-features-class" style="display:none;">
									<ul class="mo2fa_gauth_list">
									<li><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::GOOGLE_AUTHENTICATOR, 'cap_to_small' ) ); ?></li>
									<li><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::MSFT_AUTHENTICATOR, 'cap_to_small' ) ); ?></li>
									<li><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::AUTHY_AUTHENTICATOR, 'cap_to_small' ) ); ?></li>
									<li><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::LASTPASS_AUTHENTICATOR, 'cap_to_small' ) ); ?></li>
									<li><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::DUO_AUTHENTICATOR, 'cap_to_small' ) ); ?></li>
									</ul>
								</div>
							</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							</tr>

							<tr class="table-row">
									<td>&nbsp;&nbsp;<?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::SECURITY_QUESTIONS, 'cap_to_small' ) ); ?></td>
									<td class="table-checks"><i class="fa fa-check"></i></td>
									<td class="table-checks"><i class="fa fa-check"></i></td>
									<td class="table-checks"><i class="fa fa-check"></i></td>
							</tr>
							<tr class="table-row">
									<td>&nbsp;&nbsp;<?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OUT_OF_BAND_EMAIL, 'cap_to_small' ) ); ?>  Via Link</td>
									<td class="table-checks"><i class="fa fa-check"></i></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
								</tr>
								<tr class="table-row">
									<td>&nbsp;&nbsp;<?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_EMAIL, 'cap_to_small' ) ); ?></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
								</tr>
								<tr class="table-row">
									<td>&nbsp;&nbsp;<?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_SMS, 'cap_to_small' ) ); ?> (<a class="otp-over-sms-link" target="_blank" href="https://plugins.miniorange.com/sms-and-email-transaction-pricing-2fa">Check SMS Transactions Pricing <i class="fas fa-external-link-alt"></i></a>)</td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
								</tr>
								<tr class="table-row">
									<td>&nbsp;&nbsp;Yubikey (Hardware Token)</td>
									<td class="table-checks">  <i class="fa fa-times"></i></td>
									<td class="table-checks">  <i class="fa fa-times"></i></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
								</tr>
								<tr class="table-row">
									<td>&nbsp;&nbsp;<?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_WHATSAPP, 'cap_to_small' ) ); ?>  (Add-on)</td>
									<td class="table-checks">  <i class="fa fa-times"></i></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
								</tr>
								<tr class="table-row">
									<td>&nbsp;&nbsp;<?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_TELEGRAM, 'cap_to_small' ) ); ?> </td>
									<td class="table-checks">  <i class="fa fa-times"></i></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
									<td class="table-checks"> <i class="fa fa-check"></i></td>
								</tr>

						</tbody>
					</table>
					</div>
					</td>
			</tr>

			<tr class="table-row">
				<td>
				<div>&nbsp;&nbsp;Passwordless Login</div>
				</td>
				<td class="table-checks">
				<i class="fa fa-times"></i>
				</td>
				<td class="table-checks">
				<i class="fa fa-check"></i>
				</td>
				<td class="table-checks">
				<i class="fa fa-check"></i>
				</td>
			</tr>
			<tr class="table-row">
				<td>
				<div class="plugin-data" id="plugin-data" onclick="showData(this)">
								<i class="fa fa-plus-circle table-plus-icon" aria-hidden="true" style="display:contents"></i>&nbsp;&nbsp;Personalization/White Labelling
								</div>
				<div id="plugin-features" class="plugin-features-class" style="display: none;">
				<small> You'll get many more customization options in Personalization, such as Custom Email and SMS Template, Custom Login Popup, Custom Security Questions, and many more.</small>
				</div>
				</td>
				<td class="table-checks">
				<i class="fa fa-times"></i>
				</td>
				<td class="table-checks">
				<i class="fa fa-check">
				</i></td>
				<td class="table-checks">
				<i class="fa fa-check"></i>
				</td>
			</tr>
			<tr class="table-row">
				<td>
				<div>&nbsp;&nbsp;Custom SMS Gateway </div>
				</td>
				<td class="table-checks">
				<i class="fa fa-times"></i>
				</td>
				<td class="table-checks">
				<i class="fa fa-check"></i>
				</td>
				<td class="table-checks">
				<i class="fa fa-check"></i>
				</td>
			</tr>
			<tr class="table-row">
				<td>
				<div class="plugin-data" id="plugin-data" onclick="showData(this)">
					<i class="fa fa-plus-circle table-plus-icon" aria-hidden="true" style="display:contents"></i>&nbsp;&nbsp;Backup Login Method
				</div>
				<div id="plugin-features" class="plugin-features-class" style="display: none;">
				<ul class="mo2fa_gauth_list"><li>Security Questions(KBA)</li>
					<li><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::OTP_OVER_EMAIL, 'cap_to_small' ) ); ?></li>
					<li>Backup Codes</li>
				</ul>
				</div>
				</td>
				<td class="table-checks">
				<i class="fa fa-check"></i>
				</td>
				<td class="table-checks">
				<i class="fa fa-check"></i>
				</td>
				<td class="table-checks">
				<i class="fa fa-check"></i>
				</td>
			</tr>
			<tr class="table-row">
				<td style="width:30%;">
				&nbsp;&nbsp;Skip 2FA For Trusted Devices
				</td>
				<td class="table-checks">
					<i class="fa fa-times"></i>
				</td>
				<td class="table-checks">
					<i class="fa fa-check"></i>
				</td>
				<td class="table-checks">
					<i class="fa fa-check"></i>
				</td>
			</tr>
			<tr class="table-row">
				<td>
					<div class="plugin-data" id="plugin-data" onclick="showData(this)">
					<i class="fa fa-plus-circle table-plus-icon" aria-hidden="true" style="display:contents"></i>&nbsp;&nbsp;Short Codes
					</div>
				<div id="plugin-features" class="plugin-features-class" style="display: none;">
					<small>Shortcode Add-ons mostly include Allow 2fa shortcode (you can use this to add 2fa on any page), Reconfigure 2fa (you can use this shortcode to reconfigure your 2fa if you have lost your 2fa verification ability), Remember Device shortcode(you can use this to skip 2FA for trusted devices).
				</small>
				</div>
					<div>
				</div></td>
				<td class="table-checks">
					<i class="fa fa-times"></i>
				</td>
				<td class="table-checks">
					<i class="fa fa-times"></i>
				</td>
				<td class="table-checks">
					<i class="fa fa-check"></i>
				</td>
			</tr> 
			<tr class="table-row">
				<td>&nbsp;&nbsp;Restrict Simultaneous Multiple Sessions</td>
				<td class="table-checks">
					<i class="fa fa-times"></i>
				</td>
				<td class="table-checks">
					<i class="fa fa-check"></i>
				</td>
				<td class="table-checks">
					<i class="fa fa-check"></i>
				</td>
			</tr>
			<tr class="table-row">
				<td>&nbsp;&nbsp;Page Restriction Add-On</td>
				<td class="table-checks">
					<i class="fa fa-times"></i>
				</td>
				<td class="table-checks">
					<i class="fa fa-times"></i>
				</td>
				<td class="table-checks">
					<i class="fa fa-check"></i>
				</td>
			</tr>    

				<tr class="table-row mo2fa-add-on-table">
					<td colspan="6" class="mo2fa-add-on-table">
					<div class="plugin-data" id="plugin-data" onclick="showData(this)">
						<i class="fa fa-plus-circle table-plus-icon" aria-hidden="true" style="display:contents"></i>&nbsp;&nbsp;Advance WordPress Login Settings
					</div>
					<div id="plugin-features" class="plugin-features-class" style="display: none;">
						<table class="mo2fa-add-on-table">
						<tbody class="mo2fa-add-on-table">
							<tr class="table-heading" style="background:white">
								<th class="table-heading" style="width:30%; text-align:left;"></th>
								<th class="table-heading"></th>
								<th class="table-heading"></th>
								<th class="table-heading"></th>
							</tr>
							<tr class="table-row">
							<td>&nbsp;&nbsp;Grace Period For Users To Configure 2FA</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							</tr>

							<tr class="table-row">
							<td >&nbsp;&nbsp;Role Based and User Based Authentication settings</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							</tr>
							<tr class="table-row">
							<td>&nbsp;&nbsp;Mobile Support</td>
							<td class="table-checks">
								<i class="fa fa-times"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-times"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							</tr>

							<tr class="table-row">
							<td>&nbsp;&nbsp;Privacy Policy Settings</td>
							<td class="table-checks">
								<i class="fa fa-times"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-times"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							</tr>

							<tr class="table-row">
							<td>&nbsp;&nbsp;XML-RPC</td>
							<td class="table-checks">
								<i class="fa fa-times"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-times"></i>
							</td>
							<td class="table-checks">
								<i class="fa fa-check"></i>
							</td>
							</tr>

						</tbody>
						</table>
					</div>
					</td>
				</tr>

				<!-- asfdsfdsfdfdf -->

				<tr class="table-row">
					<td>
					<div>&nbsp;&nbsp;Multi-Site Support </div>
					</td>
					<td class="table-checks">
					Single site
					</td>
					<td class="table-checks">
					Upto 3 subsites
					</td>
					<td class="table-checks">
					Upto 3 subsites
					</td>
				</tr>
				<tr class="table-row">
					<td>
					<div>&nbsp;&nbsp;Language Translation Support </div>
					</td>
					<td class="table-checks">
					<i class="fa fa-check"></i>
					</td>
					<td class="table-checks">
					<i class="fa fa-check"></i>
					</td>
					<td class="table-checks">
					<i class="fa fa-check"></i>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>



<div class="mo2f_table_layout mo2fa-font-text-table" style="width: 90%;margin-left:3%">
	<div>
		<h2><?php esc_html_e( 'Steps to upgrade to the Premium Plan :', 'miniorange-2-factor-authentication' ); ?></h2>
		<ol class="mo2f_licensing_plans_ol">
			<li>
				<?php
				printf(
				/* Translators: %s: bold tags */
					esc_html( __( 'Click on %1$1sUpgrade Now%2$2s button of your preferred plan above.', 'miniorange-2-factor-authentication' ) ),
					'<b>',
					'</b>'
				);
				?>
				</li>
				<li><?php esc_html_e( 'You will be redirected to the payment page.', 'miniorange-2-factor-authentication' ); ?></li>
				<li>
					<?php
					printf(
					/* Translators: %s: bold tags */
						esc_html( __( 'Select the number of users/sites you wish to upgrade for and any add-ons if you wish to purchase and click on %1$1sProceed to Payment%2$2s.', 'miniorange-2-factor-authentication' ) ),
						'<b>',
						'</b>',
					);
					?>
				</li>
				<li>
					<?php
					printf(
					/* Translators: %s: bold tags */
						esc_html( __( ' You will be redirected to the miniOrange Console. Enter your miniOrange username and password, after which you will be redirected to the %1$1sPayment Details%2$2s page.', 'miniorange-2-factor-authentication' ) ),
						'<b>',
						'</b>',
					);
					?>
				</li>
				<li>
					<?php
					printf(
					/* Translators: %s: bold tags */
						esc_html( __( 'Click on %1$1sProceed to Payment%2$2s and make the payment.', 'miniorange-2-factor-authentication' ) ),
						'<b>',
						'</b>',
					);
					?>
				</li>
				<li>
					<?php
					printf(
					/* Translators: %s: bold tags */
						esc_html( __( 'After making the payment, you can find the respective %1$1splugins%2$2s to download from the %3$3sLicense%4$4s tab in the left navigation bar of the miniOrange Console.', 'miniorange-2-factor-authentication' ) ),
						'<b>',
						'</b>',
						'<b>',
						'</b>'
					);
					?>
				</li>
				<li>
					<?php
					printf(
					/* Translators: %s: bold tags */
						esc_html( __( 'Download the paid plugin from the %1$1sReleases and Downloads%2$2s tab through miniOrange Console .', 'miniorange-2-factor-authentication' ) ),
						'<b>',
						'</b>'
					);
					?>
				</li>
				<li>
					<?php
					printf(
					/* Translators: %s: bold tags */
						esc_html( __( 'Deactivate and delete the free plugin from %1$1sWordPress dashboard%2$2s and install the paid plugin downloaded.', 'miniorange-2-factor-authentication' ) ),
						'<b>',
						'</b>'
					);
					?>
				</li>
				<li><?php esc_html_e( 'Login to the paid plugin with the miniOrange account you used to make the payment, after this your users will be able to set up 2FA.', 'miniorange-2-factor-authentication' ); ?></li>
			</ol>
		</div>
		<hr>
		<div>
			<h2><?php esc_html_e( 'Note :', 'miniorange-2-factor-authentication' ); ?></h2>
			<ol class="mo2f_licensing_plans_ol">
				<li><?php esc_html_e( 'The plugin works with many of the default custom login forms (like Woocommerce/Theme My Login/Login With Ajax/User Pro/Elementor), however if you face any issues with your custom login form, contact us and we will help you with it.', 'miniorange-2-factor-authentication' ); ?></li>
				<li>
					<?php
					printf(
					/* Translators: %s: bold tags */
						esc_html( __( 'The %1$1slicense key %2$2sis required to activate the premium Plugins. You will have to login with the miniOrange Account you used to make the purchase then enter license key to activate plugin.', 'miniorange-2-factor-authentication' ) ),
						'<b>',
						'</b>'
					);
					?>
				</li>
		</ol>
	</div>
	<hr>
	<br>
	<div>
		<?php
		printf(
			esc_html(
				/* Translators: %s: bold tags and links*/
				__(
					'%1$1sRefund Policy : %2$2s %3$3sClick Here%4$4s to read our Refund Policy.',
					'miniorange-2-factor-authentication'
				)
			),
			'<b class="mo2fa_note">',
			'</b>',
			'<a href="https://plugins.miniorange.com/end-user-license-agreement/#v5-software-warranty-refund-policy" target="blank">',
			'</a>'
		);
		?>
	</div>
	<br>
	<hr>
	<br>
	<div>
		<?php
		printf(
			esc_html(
				/* Translators: %s: bold tags */
				__(
					'%1$1sSMS Charges : %2$2sIf you wish to choose OTP Over SMS/OTP Over SMS and Email as your authentication method,
	SMS transaction prices & SMS delivery charges apply and they depend on country. The SMS transactions\' validity is for lifetime.',
					'miniorange-2-factor-authentication'
				)
			),
			'<b class="mo2fa_note">',
			'</b>'
		);
		?>
	</div>
	<br>
	<hr>
	<br>
	<div>
		<?php
				printf(
					esc_html(
						/* Translators: %s: bold tags */
						__(
							'%1$sMultisite : %2$sFor your first license 3 subsites will be activated automatically on the same domain. And if you wish to use it for more please contact support ',
							'miniorange-2-factor-authentication'
						)
					),
					'<b class="mo2fa_note">',
					'</b>'
				);
				?>
	</div>
	<br>
	<hr>
	<br>
	<div>
		<?php
				printf(
					esc_html(
						/* Translators: %s: bold tags and links*/
						__(
							'%1$sEnd User License Agreement : %2$2s %3$3sClick Here%4$4s to read our End User License Agreement.',
							'miniorange-2-factor-authentication'
						)
					),
					'<b class="mo2fa_note">',
					'</b>',
					'<a href="https://plugins.miniorange.com/end-user-license-agreement" target="blank">',
					'</a>'
				);
				?>
	</div>
	<br>
	<hr>
	<br>
	<div>
		<?php
		printf(
			esc_html(
				/* Translators: %s: bold tags and links*/
				__(
					'%1$sContact Us : %2$sIf you have any doubts regarding the licensing plans, you can mail us at %3$sinfo@xecurify.com%4$s or submit a query using the support form.',
					'miniorange-2-factor-authentication'
				)
			),
			'<b class="mo2fa_note">',
			'</b>',
			'<a href="mailto:info@xecurify.com"><i>',
			'</i></a>'
		);
		?>
	</div>
</div>
<div id="mo2f_payment_option" class="mo2f_table_layout mo2fa-supported-payment-method" style="width: 90%;margin-left:3%">
	<div>
		<h3>Supported Payment Methods</h3>
		<hr>
		<div class="mo_2fa_container">
			<div class="mo_2fa_card-deck">
				<div class="mo_2fa_card mo_2fa_animation">
					<div class="mo_2fa_Card-header">
						<?php
						echo '<img src="' . esc_url( dirname( plugin_dir_url( __FILE__ ) ) ) . '/includes/images/card.png" class="mo2fa_card">';
						?>
					</div>
					<hr class="mo2fa_hr">
					<div class="mo_2fa_card-body">
						<p class="mo2fa_payment_p">If payment is done through Credit Card/Intenational debit card, the license would be created automatically once payment is completed. </p>
						<p class="mo2fa_payment_p"><i><b>For guide
							<?php echo '<a href=' . esc_url( MoWpnsConstants::FAQ_PAYMENT_URL ) . ' target="blank">Click Here.</a>'; ?></b></i></p>
						</div>
				</div>
				<div class="mo_2fa_card mo_2fa_animation">
					<div class="mo_2fa_Card-header">
						<?php
						echo '<img src="' . esc_url( dirname( plugin_dir_url( __FILE__ ) ) ) . '/includes/images/bank-transfer.png" class="mo2fa_card mo2fa_bank_transfer">';
						?>

					</div>
					<hr class="mo2fa_hr">
					<div class="mo_2fa_card-body">
						<?php echo '<p class="mo2fa_payment_p">If you want to use Bank Transfer for payment then contact us at <i><b style="color:#1261d8"><a href="mailto:' . esc_html( MoWpnsConstants::SUPPORT_EMAIL ) . '">info@xecurify.com</a></b></i> so that we can provide you bank details. </i></p>'; ?>
					</div>
				</div>
			</div>
		</div>
		<div class="mo_2fa_mo-supportnote">
			<p class="mo2fa_payment_p"><b>Note :</b> Once you have paid through Bank Transfer, please inform us at <i><b style="color:#1261d8"><a href="mailto:<?php echo esc_html( MoWpnsConstants::SUPPORT_EMAIL ); ?>">info@xecurify.com</a></b></i>, so that we can confirm and update your License.</p>
		</div>
	</div>
</div>