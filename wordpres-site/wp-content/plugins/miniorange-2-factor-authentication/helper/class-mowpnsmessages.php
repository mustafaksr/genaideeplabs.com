<?php
/**
 * This file has all the notifications that are shown throughout the plugin.
 *
 * @package miniorange-2-factor-authentication/helper/
 */

namespace TwoFA\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'MoWpnsMessages' ) ) {
	/**
	 * This Class has all the notifications that are shown throughout the plugin.
	 */
	class MoWpnsMessages {
		const EMAIL_SAVED            = 'Email ID saved successfully.';
		const IP_ALREADY_WHITELISTED = 'IP Address is already Whitelisted.';
		const IP_IN_WHITELISTED      = 'IP Address is Whitelisted. Please remove it from the whitelisted list.';
		const IP_WHITELISTED         = 'IP has been whitelisted successfully';

		// Advanced security.
		const INVALID_IP_FORMAT = 'Please enter Valid IP Range.';
		const DEACTIVATE_PLUGIN = 'Plugin deactivated successfully';

		// common messages.
		const GET_BACKUP_CODES           = "<div class='mo2f-custom-notice notice notice-warning backupcodes-notice'><p><p class='notice-message'><b>Please download backup codes using the 'Get backup codes' button to avoid getting locked out. Backup codes will be emailed as well as downloaded.</b></p><button class='backup_codes_dismiss notice-button'><i>NEVER SHOW AGAIN</i></button></p></div>";
		const REG_SUCCESS                = 'Your account has been retrieved successfully.';
		const ACCOUNT_EXISTS             = 'You already have an account with miniOrange. Please enter a valid password.';
		const ACCOUNT_EXISTS_SETUPWIZARD = 'You already have an account with miniOrange. Please click on "Already have an account?" to continue.';

		const INVALID_OTP   = 'Invalid one time passcode. Please enter a valid passcode.';
		const INVALID_PHONE = 'Please enter a valid phone number.';
		const INVALID_INPUT = 'Please enter a valid value in the input fields.';
		const INVALID_CREDS = 'Invalid username or password. Please try again.';
		const ALL_ENABLED   = 'All Website security features are available.';
		const ALL_DISABLED  = 'All Website security features are disabled.';
		const LOGIN_ENABLE  = 'Login security and spam protection features are available. Configure it in the Login and Spam tab.';
		const DELETE_FILE   = 'Someone has deleted the backup by going to directory please refreash the page';
		const NOT_ADMIN     = 'You are not a admin. Only admin can download';

		const WHITELIST_SELF          = "<div class='mo2f-custom-notice notice notice-warning whitelistself-notice MOWrn'><p><p class='notice-message'>It looks like you have not whitelisted your IP. Whitelist your IP as you can get blocked from your site.</p><button class='whitelist_self notice-button'><i>WhiteList</i></button></p></div>";
		const ADMIN_IP_WHITELISTED    = "<div class='mo2f-custom-notice notice notice-warning MOWrn'>
                                                       <p class='notice-message'>Your IP has been whitelisted. In the IP Blocking settings, you can remove your IP address from the whitelist if you want to do so.</p>
                                                   </div>";
		const MO_AUTHENTICATOR_NOTICE = "<div class='mo2f-custom-notice notice notice-warning MOWrn mo_authenticator-notice'>
                                                       <p class='notice-message' style='width:100%'>The miniOrange Authenticator method will be removed in the next release. Please configure any other authentication method if you have configured miniOrange Authenticator or ignore if you haven't.</p>
                                                   </div>";
		const LOW_SMS_TRANSACTIONS    = "<div class='mo2f-custom-notice notice notice-warning low_sms-notice MOWrn'><p><p class='notice-message'><img style='width:15px;' src='" . MO2F_PLUGIN_URL . '/includes/images/miniorange_icon.png' . "'>&nbsp;&nbsp;You have left very few SMS transaction. We advise you to recharge or change 2FA method before you have no SMS left.</p><a class='notice-button' href='" . MoWpnsConstants::RECHARGELINK . "' target='_blank' style='margin-right: 15px;'>RECHARGE</a><a class='notice-button' href='admin.php?page=mo_2fa_two_fa' id='setuptwofa_redirect' style='margin-right: 15px;'>SET UP ANOTHER 2FA</a><button class='sms_low_dismiss notice-button' style='margin-right: 15px;'><i>DISMISS</i></button><button class='sms_low_dismiss_always notice-button'><i>NEVER SHOW AGAIN</i></button></p></div>";

		const LOW_EMAIL_TRANSACTIONS = "<div class='mo2f-custom-notice notice notice-warning low_email-notice MOWrn'><p><p class='notice-message'><img style='width:15px;' src='" . MO2F_PLUGIN_URL . '/includes/images/miniorange_icon.png' . "'>&nbsp;&nbsp;You have left very few Email transaction. We advise you to recharge or change 2FA method before you have no Email left.</p><a class='notice-button' href='" . MoWpnsConstants::RECHARGELINK . "' target='_blank' style='margin-right: 15px;'>RECHARGE</a><a class='notice-button' href='admin.php?page=mo_2fa_two_fa' id='setuptwofa_redirect' style='margin-right: 15px;'>SET UP ANOTHER 2FA</a><button class='email_low_dismiss notice-button' style='margin-right: 15px;'><i>DISMISS</i></button><button class='email_low_dismiss_always notice-button'><i>NEVER SHOW AGAIN</i></button></p></div>";

		// Two FA Settings.
		const ENABLE        = ' has been enabled';
		const DISABLE       = ' has been disabled';
		const PLUGIN_LOG    = 'Plugin log';
		const TWO_FA_PROMPT = '2FA prompt on WP login';
		const TWO_FA        = '2-factor authentication';
		const MULTI_FA      = 'Login with any configured method';
		const INLINE_2FA    = 'User enrollment for 2FA';

		const FILE_NOT_EXISTS                  = 'File does not exist.';
		const QUERY_SUBMISSION_ERROR           = 'Error while submitting the query.';
		const FEEDBACK_APPRECIATION            = 'Thank you for the feedback.';
		const TEMPLATE_SAVED                   = 'Email template saved.';
		const NOTIFY_ON_UNUSUAL_ACTIVITY       = 'Email notification is enabled for user for unusual activities.';
		const DONOT_NOTIFY_ON_UNUSUAL_ACTIVITY = 'Email notification is disabled for user for unusual activities.';
		const DONOT_NOTIFY_ON_IP_BLOCKED       = 'Email notification is disabled for Admin.';
		const NOTIFY_ON_IP_BLOCKED             = 'Email notification is enabled for Admin.';
		const INVALID_EMAIL                    = 'Please enter valid Email ID.';
		const ADV_BLOCK_DISABLE                = 'Advanced blocking features are disabled.';
		const ADV_BLOCK_ENABLE                 = 'Advanced blocking features are available. Configure it in the Advanced blocking tab.';
		const PASS_LENGTH                      = 'Please Choose a password with minimum length 6.';
		const REQUIRED_FIELDS                  = 'Please enter all the required fields.';
		const INVALID_IP                       = 'The IP address you entered is not valid or the IP Range is not valid.';
		const PASS_MISMATCH                    = 'Password and Confirm Password do not match.';
		const RESET_PASS                       = 'You password has been reset successfully and sent to your registered email. Please check your mailbox.';
		const SUPPORT_FORM_VALUES              = 'Please submit your query along with email.';
		const SUPPORT_FORM_SENT                = 'Thanks for getting in touch! We shall get back to you shortly.';
		const QUERY_SUBMITTED                  = 'Your query is already submitted.';
		const SUPPORT_FORM_ERROR               = 'Your query could not be submitted. Please try again.';
		const DUO_INVALID_REQ                  = 'Invalid or missing parameters, or a user with this name already exists.';
		const VERIFY_CHAT_ID                   = 'An Error has occured while sending the OTP. Please verify your chat ID.';
		const DUO_ACCOUNT_INACTIVE             = 'Your account is inactive from duo side, please contact to your administrator.';
		const DUO_USER_EXISTS                  = 'This user is already available on duo, please send push notification to setup the 2FA.';
		const DUO_SERVER_NOT_RESPONDING        = 'Duo server is not responding right now, please try after some time.';
		const INVALID_CREDENTIALS              = 'Not the valid credential, please enter valid keys.';
		const FIELD_MISSING                    = 'Some field is missing, please fill all required details.';
		const UNIQUE_QUESTION                  = 'The questions you select must be unique.';
		const OTP_EXPIRED                      = 'OTP has been expired please initiate another transaction for verification.';
		const SETUP_2FA                        = 'Please set up the second-factor by clicking on Configure button.';
		const INVALID_REQ                      = 'Invalid request. Please try again.';
		const INVALID_ENTRY                    = 'All the fields are required. Please enter valid entries.';
		const INVALID_EMAIL_VER_REQ            = 'Invalid request. Test case failed.';
		const COMPLETED_TEST                   = 'You have successfully completed the test.';
		const SET_AS_2ND_FACTOR                = 'is set as your 2 factor authentication method.';
		const VALIDATE_DUO                     = 'Duo push notification validate successfully.';
		const ERROR_DURING_USER_REGISTRATION   = 'Error occurred while registering the user. Please try again.';
		const ERROR_DURING_PROCESS_EMAIL       = 'An error occured while processing your request. Please check if your SMTP server is configured or check if email transactions are exhausted.';
		const ERROR_DURING_PROCESS             = 'An error occured while processing your request. Please try again or contact site administrator.';
		const ERROR_IN_SENDING_PN              = 'An error occured while sending push notification to your app. You can click on <b>Phone is Offline</b> button to enter soft token from app or <b>Forgot your phone</b> button to receive OTP to your registered email.';
		const SCAN_QR_CODE                     = 'Please scan the QR code.';
		const OTP_SENT                         = 'One Time Passcode has been sent for verification to ';
		const ENTER_YOUR_EMAIL_PASSWORD        = 'Please enter your registered email and password.';
		const INTERNET_CONNECTIVITY_ERROR      = 'Unable to generate backup codes. Please check your internet and try again.';
		const USED_ALL_BACKUP_CODES            = 'You have used all of the backup codes.';
		const BACKUP_CODE_INVALID_REQUEST      = 'Invalid request.';
		const BACKUP_CODE_DOMAIN_LIMIT_REACH   = 'User Limit is reached for your domain.';
		const BACKUP_CODE_LIMIT_REACH          = 'You have already downloaded the backup codes for this domain.';
		const ANSWER_SECURITY_QUESTIONS        = 'Please answer the following security questions.';
		const ERROR_WHILE_SAVING_KBA           = 'Error occured while saving your kba details. Please try again.';
		const SETTINGS_SAVED                   = 'Your settings are saved successfully.';
		const ERROR_IN_SENDING_EMAIL           = 'There was an error in sending email. Please click on Resend OTP to try again.';
		const RESENT_OTP                       = 'Another One Time Passcode has been sent';
		const INVALID_ANSWERS                  = 'Invalid Answers. Please try again.';
		const ERROR_FETCHING_QUESTIONS         = 'There was an error fetching security questions. Please try again.';
		const RESET_DUO_CONFIGURATON           = 'Your Duo configuration has been reset successfully.';
		const TRANSIENT_ACTIVE                 = 'Please try again after some time.';
		const TEST_GAUTH_METHOD                = 'to test Google Authenticator method.';
		const ERROR_WHILE_VALIDATING_OTP       = 'Error occurred while validating the OTP. Please try again.';
		const PUSH_NOTIFICATION_SENT           = 'A Push notification has been sent to your miniOrange Authenticator App.';
		const ERROR_IN_SENDING_OTP_ONPREM      = 'There was an error in sending one-time passcode. Please check your SMTP Setup and remaining transactions.';
		const ERROR_IN_SENDING_OTP             = 'There was an error in sending one-time passcode. Your transaction limit might have exceeded.';
		const ENTER_OTP                        = 'Please enter the 2FA code below.';
		const ENTER_VALUE                      = 'Please enter a value to test your authentication.';
		const REGISTER_WITH_MO                 = 'Invalid request. Please register with miniOrange before configuring your mobile.';
		const AUTHENTICATION_FAILED            = 'Authentication failed. Please try again to test the configuration.';
		const ERROR_IN_SENDING_OTP_CAUSES      = 'Error occurred while validating the OTP. Please try again. Possible causes:';
		const ACCOUNT_CREATED                  = 'Your account has been created successfully.';
		const ACCEPT_LINK_TO_VERIFY_EMAIL      = 'Please click on accept link to verify your email.';
		const VERIFICATION_EMAIL_SENT          = 'A verification email is sent to';
		const SET_2FA_OTP                      = 'is set as your Two-Factor method.';
		const SET_2FA                          = 'is set as your Two-Factor method.';
		const TEST_AUTHY_2FA                   = 'to test Authy 2-Factor Authentication method.';
		const ONLY_DIGITS_ALLOWED              = 'Only digits are allowed. Please enter again.';
		const ERROR_WHILE_VALIDATING_USER      = 'Error occurred while validating the user. Please try again.';
		const SERVER_TIME_SYNC                 = 'Please make sure your System and device have the same time as the displayed Server time.';
		const APP_TIME_SYNC                    = 'Your App Time is not in sync.Go to settings and tap on tap on Sync Time now.';
		const ENTER_VALID_ENTRY                = 'All the fields are required. Please enter valid entries.';
		const ERROR_WHILE_SAVING_SETTINGS      = 'Error occurred while saving the settings.Please try again.';
		const DISABLED_2FA                     = 'Two-Factor plugin has been disabled.';
		const DENIED_DUO_REQUEST               = 'You have denied the request or you have not set duo push notification yet';
		const DENIED_REQUEST                   = 'You have denied the request.';
		const REGISTRATION_SUCCESS             = 'You are registered successfully.';
		const ACCOUNT_REMOVED                  = 'Your account has been removed. Please contact your administrator.';
		const LOGIN_WITH_2ND_FACTOR            = 'Please disable 2FA prompt on WP login page to enable Login with 2nd facor only.';
		const ERROR_CREATE_ACC_OTP             = 'An error occured while creating your account. Please try again by sending OTP again.';
		const CLICK_HERE                       = 'Click Here';
		const PHONE_NOT_CONFIGURED             = 'Your phone number is not configured. Please configure it before selecting OTP Over SMS as your 2-factor method.';
		const CONFIGURE_2FA                    = 'to configure another 2 Factor authentication method.';
		const ACCOUNT_ALREADY_EXISTS           = 'You already have an account with miniOrange, please sign in.';
		const INVALID_REQUEST                  = 'Invalid request. Please register with miniOrange and configure 2-Factor to save your login settings.';
		const PASS_LENGTH_LIMIT                = 'Password length between 6 - 15 characters. Only following symbols (!@#.$%^&*-_) should be present.';
		const SENT_OTP                         = 'The OTP has been sent to';
		const SOMETHING_WENT_WRONG             = 'Something went wrong';
		const ENTER_SENT_OTP                   = '. Please enter the OTP you received to Validate.';
		const USER_LIMIT_EXCEEDED              = 'Your limit of 3 users has exceeded. Please upgrade to premium plans for more users.';

		/**
		 * Return actual messages according to the key.
		 *
		 * @param string $message key of the message to be shown.
		 * @return string
		 */
		public static function show_message( $message ) {
			$message = constant( 'self::' . $message );
			return $message;
		}

		/**
		 * Show nofications on admin dashboard depend on type i.e. sucess, error and notice.
		 *
		 * @param string $content - message to be shown on dashboard.
		 * @param string $type - type of message to be shown.
		 * @param array  $allowed_protocols - List of tags and attributes that needs to ignore while escaping.
		 * @return void
		 */
		public function mo2f_show_message( $content, $type, $allowed_protocols = array() ) {
			$notification_type = array(
				'CUSTOM_MESSAGE' => 'success',
				'NOTICE'         => 'error',
				'ERROR'          => 'error',
				'SUCCESS'        => 'success',
			);
			echo '<div class="overlay_not_JQ_' . esc_attr( $notification_type[ $type ] ) . '" id = "pop_up_' . esc_attr( $notification_type[ $type ] ) . '" ><p class ="popup_text_not_JQ">' . wp_kses_post( $content ) . '</p></div>';
			?>
			<script type="text/javascript">
				setTimeout(function() {
					var element = document.getElementById( "pop_up_" + "<?php echo esc_js( $notification_type[ $type ] ); ?>" );
					element.classList.toggle("overlay_not_JQ_" + "<?php echo esc_js( $notification_type[ $type ] ); ?>" );
					element.innerHTML = "";
				}, 7000);
			</script>
				<?php
		}

		/**
		 * Translates the strings.
		 *
		 * @param string $text The string to be translated.
		 * @return string
		 */
		public static function lang_translate( $text ) {
			return __( $text, 'miniorange-2-factor-authentication' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is a string literal.
		}
	}
}
