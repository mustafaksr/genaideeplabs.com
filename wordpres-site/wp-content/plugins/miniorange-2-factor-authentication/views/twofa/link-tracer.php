<?php
/**
 * This file contains the link for videos and plugin setup guide.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

// Needed in both.

use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$two_factor_premium_doc = array(
	'Enble 2fa'                              => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-disable-two-factor-for-all-users-on-wordpress',

	'Custom url'                             => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-enable-role-based-2fa-for-wordpress-two-factor-authentication#stepd',

	'Woocommerce'                            => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-woocommerce-login-form',

	'Ultimate Member'                        => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-ultimate-member-login-form',

	'Restrict Content Pro'                   => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-restrict-content-pro-login-form',

	'Theme My Login'                         => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-theme-my-login-form',

	'User Registration'                      => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-user-registration-login-form',

	'LoginPress'                             => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-login-press-login-form',

	'Admin Custom Login'                     => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-admin-custom-login-form',

	'RegistrationMagic'                      => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-registrationmagic-form',

	'BuddyPress'                             => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-buddypress-login-form',

	'Profile Builder'                        => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-profile-builder-login-form',

	'Elementor Pro'                          => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-elementor-pro-login-form',

	'Login with Ajax'                        => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/two-factor-authentication-2fa-mfa-for-login-with-ajax-form',

	'Setup SMTP'                             => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/setup-smtp-for-miniorange-two-factor-authentication',

	'Remember Device'                        => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-set-remember-device-with-two-factor-authentication-2fa',

	'Custom plugin logo'                     => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-customize-wordpress-login-screen-powered-by-logo-2fa-mfa',

	'Custom plugin name'                     => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-setup-customize-plugin-name-wordpress-2fa',

	'Custom email template'                  => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/customize-2fa-login-pop-up-and-email-notifications#step2',

	'custom login popup'                     => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/customize-2fa-login-pop-up-and-email-notifications#step1',

	'Shortcode'                              => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-setup-custom-shortcode-wordpress-2fa',

	'Specific set of authentication methods' => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-enable-role-based-2fa-for-wordpress-two-factor-authentication#stepc',

	'Invoke Inline Registration to setup 2nd factor for users' => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/enforce-2fa-users',

	'Email verification of Users during Inline Registration' => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-enable-email-verification-for-users-during-inline-registration-wordpress-2fa',

	'Select login screen option'             => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-set-passwordless-login-as-a-login-screen-options-wordpress-2fa',

	'What happens if my phone is lost, discharged or not with me' => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-setup-what-happens-if-phone-is-lost-discharged-not-with-me-wordpress-2fa',

	'Enable/disable 2-factor Authentication' => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-disable-two-factor-for-all-users-on-wordpress',

	'Plugin level waf'                       => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/web-firewall/#firewall-level',

	'htaccess level waf'                     => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/web-firewall/#firewall-level',

	'Rate Limiting'                          => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/web-firewall#dos-proctection',

	'Brute Force Protection'                 => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/brute-force#Brute-Force-protection',

	'Google reCAPTCHA'                       => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/brute-force#Google-recaptcha',

	'Enforce Strong Passwords'               => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-set-miniorange-password-policy-settings-configuration#',

	'Scheduled database'                     => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/backup-restore/#schedule-backup',

	'Scan Modes'                             => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/scanner#wp-malware-scanner',

	'Custom Scan Settings'                   => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/scanner#custom-scan',

	'Manual IP Blocking'                     => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/IP-blocking-whitelisting-lookup#wordpress-ip-blocking',

	'IP Whitelisting'                        => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/IP-blocking-whitelisting-lookup#wp-ip-whitelisting',

	'IP LookUp'                              => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/IP-blocking-whitelisting-lookup#wp-ip-lookup',

	'IP Address Range Blocking'              => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/range-blocking#ip-range-blocking',

	'Browser Blocking'                       => 'https://developers.miniorange.com/docs/security/wordpress/wp-security/browser-blocking#wp-browser-blocking',

	'Enable 2FA Role Based'                  => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/how-to-enable-role-based-2fa-for-wordpress-two-factor-authentication',

	'Session Control'                        => MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/set-two-factor-from-session-addon',
);
