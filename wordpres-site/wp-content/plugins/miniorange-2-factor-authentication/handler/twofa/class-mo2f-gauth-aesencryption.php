<?php
/**
 * File contains functions for google authenticator data encryption and decryption.
 *
 * @package    miniOrange-2-factor-authentication/handler/twofa
 * @author     miniOrange Security Software Pvt. Ltd.
 * @license    GNU/GPLv3
 * @copyright  Copyright 2015 miniOrange. All Rights Reserved.
 */

namespace TwoFA\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2f_GAuth_AESEncryption' ) ) {
	/**
	 * Class mo2f_GAuth_AESEncryption
	 */
	class Mo2f_GAuth_AESEncryption {

		/**
		 * Encrypts data.
		 *
		 * @param string $data Google authenticator secret.
		 * @param string $key Encryption key.
		 * @return string
		 */
		public static function encrypt_data_ga( $data, $key ) {
			$plaintext      = $data;
			$cipher         = 'AES-128-CBC';
			$ivlen          = openssl_cipher_iv_length( $cipher );
			$iv             = openssl_random_pseudo_bytes( $ivlen );
			$ciphertext_raw = openssl_encrypt( $plaintext, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv );
			$hmac           = hash_hmac( 'sha256', $ciphertext_raw, $key, $as_binary = true );
			$ciphertext     = base64_encode( $iv . $hmac . $ciphertext_raw ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Obfuscation is necessary here.
			return $ciphertext;
		}

		/**
		 * Decrypts data.
		 *
		 * @param string $data Google authenticator secret.
		 * @param string $key Google authenticator key.
		 * @return string
		 */
		public static function decrypt_data( $data, $key ) {
			$c                  = base64_decode( $data ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Obfuscation is necessary here.
			$cipher             = 'AES-128-CBC';
			$ivlen              = openssl_cipher_iv_length( $cipher );
			$iv                 = substr( $c, 0, $ivlen );
			$ciphertext_raw     = substr( $c, $ivlen + 32 );
			$original_plaintext = openssl_decrypt( $ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv );
			return $original_plaintext;
		}
	}
}
