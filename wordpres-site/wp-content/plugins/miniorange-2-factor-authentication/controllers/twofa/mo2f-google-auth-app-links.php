<?php
/**
 * This file includes the app based authenticator download links.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

// Needed in both.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TwoFA\Helper\MoWpnsConstants;

$auth_app_links = array(
	'google_authenticator' => array(
		'Android'  => MoWpnsConstants::AUTH_ANDROID_APP_COMMON_LINK . 'com.google.android.apps.authenticator2',
		'Ios'      => MoWpnsConstants::AUTH_IOS_APP_COMMON_LINK_ITUNES . 'us/app/google-authenticator/id388497605',
		'app_name' => MoWpnsConstants::GOOGLE_AUTHENTICATOR,
	),
	'msft_authenticator'   => array(
		'Android'  => MoWpnsConstants::AUTH_ANDROID_APP_COMMON_LINK . 'com.azure.authenticator',
		'Ios'      => MoWpnsConstants::AUTH_IOS_APP_COMMON_LINK_APPS . 'us/app/microsoft-authenticator/id983156458',
		'app_name' => MoWpnsConstants::MSFT_AUTHENTICATOR,
	),
	'free_otp_auth'        => array(
		'Android'  => MoWpnsConstants::AUTH_ANDROID_APP_COMMON_LINK . 'org.fedorahosted.freeotp',
		'Ios'      => MoWpnsConstants::AUTH_IOS_APP_COMMON_LINK_APPS . 'us/app/freeotp-authenticator/id872559395',
		'app_name' => MoWpnsConstants::FREEOTP_AUTHENTICATOR,
	),
	'duo_auth'             => array(
		'Android'  => MoWpnsConstants::AUTH_ANDROID_APP_COMMON_LINK . 'com.duosecurity.duomobile',
		'Ios'      => MoWpnsConstants::AUTH_IOS_APP_COMMON_LINK_APPS . 'in/app/duo-mobile/id422663827',
		'app_name' => MoWpnsConstants::DUO_AUTHENTICATOR,
	),
	'authy_authenticator'  => array(
		'Android'  => MoWpnsConstants::AUTH_ANDROID_APP_COMMON_LINK . 'com.authy.authy',
		'Ios'      => MoWpnsConstants::AUTH_IOS_APP_COMMON_LINK_ITUNES . 'in/app/authy/id494168017',
		'app_name' => MoWpnsConstants::AUTHY_AUTHENTICATOR,
	),
	'last_pass_auth'       => array(
		'Android'  => MoWpnsConstants::AUTH_ANDROID_APP_COMMON_LINK . 'com.lastpass.authenticator',
		'Ios'      => MoWpnsConstants::AUTH_IOS_APP_COMMON_LINK_ITUNES . 'in/app/lastpass-authenticator/id1079110004',
		'app_name' => MoWpnsConstants::LASTPASS_AUTHENTICATOR,
	),
);

