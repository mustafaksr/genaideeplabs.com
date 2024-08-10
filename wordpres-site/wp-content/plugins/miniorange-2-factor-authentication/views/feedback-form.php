<?php
/**
 * This File contains Feedback form UI.
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( 'plugins.php' !== basename( isset( $_SERVER['PHP_SELF'] ) ? sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) : '' ) ) {
	return;
}
global $mo2fdb_queries;
$mo2f_configured_2_f_a_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', get_current_user_id() );
$no_of_2fa_users              = $mo2fdb_queries->get_no_of_2fa_users();
$deactivate_reasons           = array(
	'Conflicts with other plugins',
	'Redirecting back to login page after Authentication',
);
if ( strlen( $mo2f_configured_2_f_a_method ) ) {
	array_push( $deactivate_reasons, "Couldn't understand how to make it work" );
} elseif ( strpos( $mo2f_configured_2_f_a_method, MoWpnsConstants::GOOGLE_AUTHENTICATOR ) !== false ) {
	array_push( $deactivate_reasons, 'Unable to verify Google Authenticator' );
} elseif ( strpos( $mo2f_configured_2_f_a_method, MoWpnsConstants::OTP_OVER_SMS ) !== false || strpos( $mo2f_configured_2_f_a_method, MoWpnsConstants::OTP_OVER_EMAIL ) !== false ) {
	array_push( $deactivate_reasons, 'Exhausted Email or SMS transactions' );
}
if ( 'MO_2_FACTOR_PLUGIN_SETTINGS' !== get_option( 'mo_2factor_user_registration_status' ) ) {
	array_push( $deactivate_reasons, 'Did not want to create an account' );
}
if ( get_site_option( 'mo2fa_visit' ) ) {
	array_push( $deactivate_reasons, 'Plans are expensive' );
}
if ( 3 === $no_of_2fa_users ) {
	array_push( $deactivate_reasons, 'User Limit' );
}
array_push( $deactivate_reasons, 'Other Reasons:' );
?>
</head>

<body>
	<div id="mo_wpns_feedback_modal" class="mo_modal">
		<div class="mo_wpns_modal-content">
			<h3>
				<b>Your feedback</b>
				<span class="mo_wpns_close">&times;</span>
			</h3>
			<hr>
			<form name="f" method="post" action="" id="mo_wpns_feedback">
				<input type="hidden" id="mo_wpns_feedback_nonce" name="mo_wpns_feedback_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo-wpns-feedback-nonce' ) ); ?>"/>
				<input type="hidden" name="option" value="mo_wpns_feedback" />
				<div>
					<h4>Please help us to improve our plugin by giving your
						opinion.<br></h4>

				</div>
				<div class="mo2f_feedback_text">
					<span id="mo2f_link_id"></span>
					<?php

					foreach ( $deactivate_reasons as $deactivate_reason ) {
						?>
						<div>
							<label for="<?php echo esc_attr( $deactivate_reason ); ?>">
								<input type="radio" name="mo_wpns_deactivate_plugin" value="<?php echo esc_attr( $deactivate_reason ); ?>" required>
								<?php echo esc_attr( $deactivate_reason ); ?>
								<?php if ( 'Conflicts with other plugins' === $deactivate_reason ) { ?>
									<div id="mo_wpns_other_plugins_installed">
										<?php
											MO2f_Utility::get_all_plugins_installed();
										?>
									</div>
								<?php } ?>

							</label>
						</div>
					<?php } ?>
					<br>
					<textarea id="wpns_query_feedback" name="wpns_query_feedback" rows="4" cols="50" placeholder="Write your query here"></textarea>
					<div class="mo2f_modal-footer">
						<div>

							<input type="checkbox" name="mo2f_get_reply" value="1" checked />
							<label for="mo2f_get_reply">Send plugin Configuration</label>
							</input>
						</div>

						<input type="submit" name="miniorange_feedback_submit" class="button button-primary button-large" style="float:right" value="Submit" />
						<input type="button" name="miniorange_feedback_skip" class="button button-primary button-large" style="float:left" value="Skip & Deactivate" onclick="document.getElementById('mo_wpns_feedback_form_close').submit();" />
					</div>
					<br><br>
				</div>
			</form>
			<form name="f" method="post" action="" id="mo_wpns_feedback_form_close">
				<input type="hidden" id="mo_wpns_feedback_nonce" name="mo_wpns_feedback_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo-wpns-feedback-nonce' ) ); ?>"/>
				<input type="hidden" name="option" value="mo_wpns_skip_feedback" />
			</form>

		</div>

	</div>
	<script>
		var label = document.getElementById('deactivate-miniorange-2-factor-authentication').getAttribute("aria-label");
		plugin_active_label = 'a[aria-label="' + label + '"]';
		jQuery('#mo_wpns_other_plugins_installed').hide();
		jQuery(plugin_active_label).click(function() {
			var mo_modal = document.getElementById('mo_wpns_feedback_modal');
			var span = document.getElementsByClassName("mo_wpns_close")[0];
			// When the user clicks the button, open the mo2f_modal
			mo_modal.style.display = "block";
			jQuery('input:radio[name="mo_wpns_deactivate_plugin"]').click(function() {
				var reason = jQuery(this).val();
				jQuery('#wpns_query_feedback').removeAttr('required');
				if (reason == "Did not want to create an account") {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Write your query here.");
					jQuery('#mo2f_link_id').html('<p>We suggest you to create an account for only those methods which require miniOrange cloud for working purpose.</p>');
					jQuery('#mo2f_link_id').show();
				} else if (reason == "Upgrading to Premium") {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Write your query here.");
					jQuery('#mo2f_link_id').html('<p>Thanks for upgrading. For setup instructions, please follow this guide' +
						', <a href="<?php echo esc_url( MoWpnsConstants::SETUPGUIDE ); ?>" target="_blank"><b>VIDEO GUIDE.</b></a></p>');
					jQuery('#mo2f_link_id').show();
				} else if (reason == "User Limit") {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Write your query here.");
					jQuery('#mo2f_link_id').html('<p>You can download our <a href="https://wordpress.org/plugins/miniorange-login-security/" target="_blank"><b>Multi Factor Authentication</b></a> plugin to setup 2FA for unlimited admin users.</p>');
					jQuery('#mo2f_link_id').show();

				} else if (reason == "Exhausted Email or SMS") {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Write your query here.");
					jQuery('#mo2f_link_id').html('<p>You can recharge your SMS and Email transactions using this link' +
						', <a href="<?php echo esc_url( MoWpnsConstants::RECHARGELINK ); ?>" target="_blank"><b>Recharge Link.</b></a> Otherwise, you can configure your own gateway <a href="<?php echo esc_url( MoWpnsConstants::CUSTOMSMSGATEWAY ); ?>" target="_blank"><b>here.</b></a></p>');
					jQuery('#mo2f_link_id').show();
				} else if (reason == "Conflicts with other plugins") {
					jQuery('#wpns_query_feedback').attr("placeholder", "Can you please mention the plugin name, and the issue?");
					jQuery('#mo_wpns_other_plugins_installed').show();
					jQuery('#mo2f_link_id').hide();
				} else if (reason == "Other Reasons:") {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Can you let us know the reason for deactivation");
					jQuery('#wpns_query_feedback').prop('required', true);
					jQuery('#mo2f_link_id').hide();
				} else {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Write your query here.");
					jQuery('#mo2f_link_id').hide();
				}
			});

			span.onclick = function() {
				mo_modal.style.display = "none";
			}

			// When the user clicks anywhere outside of the mo2f_modal, mo2f_close it
			window.onclick = function(event) {
				if (event.target == mo_modal) {
					mo_modal.style.display = "none";
				}
			}
			return false;

		});
	</script>
