<?php
/**
 * This file contains frontend to show setup wizard to configure Google Authenticator.
 *
 * @package miniorange-2-factor-authentication/views/twofa/setup
 */

/**
 * Common view of google authenticator setup.
 *
 * @param string  $secret GA secret.
 * @param string  $gauth_name GA appname.
 * @param string  $qr_code QR code.
 * @param string  $url QR code url.
 * @param string  $microsoft_url Microsoft QR code URL.
 * @param string  $session_id_encrypt Encrypted session id.
 * @param string  $redirect_to Redirect to URL.
 * @param boolean $is_inline Encrypted session id.
 * @param string  $back_button_handler html for back button form.
 * @param string  $validate_input_fields html for input fields of validate form.
 * @param string  $login_message Message to be shown on registration popup only.
 * @return void
 */
function mo2f_configure_google_auth_common_view( $secret, $gauth_name, $qr_code, $url, $microsoft_url, $session_id_encrypt, $redirect_to, $is_inline, $back_button_handler, $validate_input_fields = null, $login_message = null ) {
	require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'mo2f-google-auth-app-links.php';
	echo '<h3>';
	esc_html_e( 'Set up Google/Authy/Microsoft Authenticator', 'miniorange-2-factor-authentication' );
	echo '<span style="float:right">
				<a href="https://plugins.miniorange.com/google-authenticator-for-wordpress-two-factor-authenticator-2fa-mfa" target="_blank">
					<span class="dashicons dashicons-text-page" style="font-size:26px;color:#413c69;float: right;"></span>
				</a>
				<a href="https://www.youtube.com/watch?v=vVGXjedIaGs" target="_blank">
					<span class="dashicons dashicons-video-alt3" style="font-size:30px;color:red;float: right; margin-right: 16px;margin-top: -3px;"></span>
				</a>
		</span>
	</h3>';
	echo '<hr>';
	echo '<table class="mo2f_configure_ga">
		<tr>
			<td class="mo2f_google_authy_step2">';
		echo '<div id="otpMessage" style="display:none;"><p id="mo2f_gauth_inline_message" class="mo2fa_display_message_frontend" style="text-align: left !important;"></p>
				</div>';
					echo '<div style="line-height: 4; margin-left:20px;" id = "mo2f_choose_app_tour">
						<label for="authenticator_type"><b>1. Choose an Authenticator app:</b></label>

						<select id="authenticator_type">';
	foreach ( $auth_app_links as $auth_app => $auth_app_link ) {
		echo '<option data-apptype="' . esc_attr( $auth_app ) . '" data-playstorelink="' . esc_attr( $auth_app_link['Android'] ) . '" data-appstorelink="' . esc_attr( $auth_app_link['Ios'] ) . '">' . esc_html( $auth_app_link['app_name'] ) . '</option>';
	}
				echo '</select>
					</div>';

				echo '<h4 style="margin-left:20px;">';

				esc_html_e( '2. Scan the QR code from the Authenticator App.', 'miniorange-2-factor-authentication' );

				echo '</h4>';
				echo '<div style="margin-left:29px;">
					<ol>';
					include_once 'setup-authenticator-instructions.php';
					$allowed_protocols   = wp_allowed_protocols();
					$allowed_protocols[] = 'data';

					echo '
						<div class="mo2f_ga_qr_container">
							<div>
								<div class="mo2f_gauth_column mo2f_gauth_left" >' . wp_kses(
						$qr_code,
						array(
							'div' => array(
								'class'       => true,
								'id'          => true,
								'style'       => true,
								'data-qrcode' => true,
							),
							'img' => array(
								'id'    => true,
								'style' => true,
								'src'   => array(
									'data' => true,
								),
							),
						),
						$allowed_protocols
					) . '<div class="mo2f_gauth_microsoft" id= "mo2f_microsoft_auth_qr_code" style="background:white;display:none" data-qrcode="' . esc_html( $microsoft_url ) . '" ></div>
								</div>
							</div>
							<hr>
							<div style="display: block;width: 110%;">
								<form name="mo2f_validate_code_form" id="mo2f_validate_code_form" method="post" style="margin: 0px;">
									<span><b>';
									esc_html_e( 'Enter the code from authenticator app:', 'miniorange-2-factor-authentication' );
									echo '</b>
									<input class="mo2f_table_textbox" style="width:230px;margin: 2% 0%;" id="google_auth_code" autofocus="true" required="true"
										type="text" name="google_token" placeholder="' . esc_attr__( 'Enter OTP', 'miniorange-2-factor-authentication' ) . '"
										style="width:95%;"/></span><br><br>';
										echo $is_inline ? wp_kses(
											$validate_input_fields,
											array(
												'input' => array(
													'type' => true,
													'name' => true,
													'value' => true,
												),
											)
										) : '';
								echo '</form>';
							echo '<div style="display:flex;">';
							$allowed_html = array(
								'form'   => array(
									'name'   => true,
									'method' => true,
									'action' => true,
									'id'     => true,
									'class'  => true,
								),
								'input'  => array(
									'type'  => true,
									'name'  => true,
									'value' => true,
									'id'    => true,
									'class' => true,
								),
								'submit' => array(
									'name'  => true,
									'id'    => true,
									'class' => true,
									'value' => true,
								),
							);

							echo wp_kses( $back_button_handler, $allowed_html );
							echo '<button name="mo2f_validate_gauth" id="mo2f_save_otp_ga_tour" class="button button-primary button-large" style="margin-left:5px;height: 10%;"/>Verify</button>';
							echo '</div>';
							echo '<form name="f" method="post" action="" id="mo2f_2factor_test_prompt_cross">
								<input type="hidden" name="option" value="mo2f_2factor_test_prompt_cross"/>
								<input type="hidden" name="mo2f_2factor_test_prompt_cross_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f-2factor-test-prompt-cross-nonce' ) ) . '"/>
							</form>';
							echo '</div><br>';
							echo '</div>
					
					</ol>
				<br>
				</div>
			</td>';
							echo '<td class="mo2f_vertical_line" ></td>
			<td class="mo2f_google_authy_step3">';
							if ( current_user_can( 'administrator' ) ) {
								echo '<form name="mo2f_save_google_appname"  id="login_settings_appname_form" method="post" action="">
						<input type="hidden" name="option" value="mo2f_google_appname" />
						<input type="hidden" name="mo2f_google_appname_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f-google-appname-nonce' ) ) . '"/>
				<div>';
									echo '<span><b>';
									esc_html_e( 'Change App name in authenticator app:', 'miniorange-2-factor-authentication' );
									echo '</b>&nbsp;					
					<input type="text" class="mo2f_table_textbox" style="margin: 2% 0%;" id= "mo2f_change_app_name" name="mo2f_google_auth_appname" placeholder="Enter the app name" value="' . esc_attr( $gauth_name ) . '"  />
					<input type="submit" name="submit" value="Save App Name" class="button button-primary button-large"/>							
				</div><br></form>';
							}
							echo '<div><a href="#mo2f_scanbarcode_a"><b>';
							esc_html_e( 'Can\'t scan the QR code? ', 'miniorange-2-factor-authentication' );
							echo '</b></a>';
							echo '</div>';
							echo '<div  id="mo2f_secret_key" style="background: white;display:none;">
					<ol style="padding-left: 20px;">
						<li>' .
							esc_html__( 'Tap on Menu and select', 'miniorange-2-factor-authentication' ) . '<b>' .
							esc_html__( ' Set up account ', 'miniorange-2-factor-authentication' ) . '</b>.
						</li>
						<li>' .
							esc_html__( 'Select', 'miniorange-2-factor-authentication' ) . '<b>' .
							esc_html__( ' Enter provided key ', 'miniorange-2-factor-authentication' ) . '</b>.
						</li>
						<li>' .
							esc_html__( 'For the', 'miniorange-2-factor-authentication' ) . '<b>' .
							esc_html__( ' Enter account name ', 'miniorange-2-factor-authentication' ) . '</b>' .
							esc_html__( 'field, type your preferred account name.', 'miniorange-2-factor-authentication' ) . '</li>
						<li>' .
							esc_html__( 'For the', 'miniorange-2-factor-authentication' ) .
							'<b>' . esc_html__( ' Enter your key ', 'miniorange-2-factor-authentication' ) . '</b>' .
							esc_html__( 'field, type the below secret key', 'miniorange-2-factor-authentication' ) . ':
						</li>

						<div class="mo2f_google_authy_secret_outer_div">
							<div class="mo2f_google_authy_secret_inner_div">
								' . esc_html( $secret ) . '
							</div>
							<div class="mo2f_google_authy_secret">' .
								esc_html__( 'Spaces do not matter', 'miniorange-2-factor-authentication' ) . '.
							</div>
						</div>
						<li>' .
							esc_html__( 'Key type: make sure', 'miniorange-2-factor-authentication' ) . '<b>' .
							esc_html__( ' Time-based ', 'miniorange-2-factor-authentication' ) . '</b>' .
							esc_html__( ' is selected', 'miniorange-2-factor-authentication' ) . '.
						</li>

						<li>' . esc_html__( 'Tap Add.' ) . '</li>
					</ol>
				</div><br>';
							echo '<div>
					<h4><a href="https://faq.miniorange.com/knowledgebase/sync-mobile-app/" target="_blank">Sync your server time with authenticator app time</a></h4>
					<h4 style="color: red; text-align: center;">Current Server Time: <span id="mo2f_server_time">--</span></h4>
				</div>';
							echo '<div id="links_to_apps_tour" style="background-color:white;padding:5px;width:90%;">
					<span id="links_to_apps"></span>
				</div>';
							echo '</td>
		</tr>
	</table>';
							global $main_dir;
							$server_time = isset( $_SERVER['REQUEST_TIME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_TIME'] ) ) * 1000 : null;
							wp_register_script( 'mo2f_google_auth_script', $main_dir . 'includes/js/google-authenticator.min.js', array( 'jquery' ), MO2F_VERSION, false );
							wp_localize_script(
								'mo2f_google_auth_script',
								'gAuthValidate',
								array(
									'ajaxurl'     => admin_url( 'admin-ajax.php' ),
									'nonce'       => wp_create_nonce( 'mo-two-factor-ajax-nonce' ),
									'ms_url'      => $microsoft_url,
									'gu_url'      => $url,
									'session_id'  => $session_id_encrypt,
									'ga_secret'   => $secret,
									'is_inline'   => $is_inline,
									'server_time' => $server_time,
									'redirect_to' => $redirect_to,
								)
							);
							wp_print_scripts( 'mo2f_google_auth_script' );
}
