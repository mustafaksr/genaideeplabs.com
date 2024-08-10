<?php
/**
 * This file is contains functions related google authentication method.
 *
 * @package miniOrange-2-factor-authentication/handler/twofa
 */

namespace TwoFA\Onprem;

use TwoFA\Handler\Mo2f_GAuth_AESEncryption;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class-mo2f-gauth-aesencryption.php file included.
 */
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'class-mo2f-gauth-aesencryption.php';

if ( ! class_exists( 'Google_Auth_Onpremise' ) ) {
	/**
	 * Class Google_auth_onpremise
	 */
	class Google_Auth_Onpremise {

		/**
		 * TOTP code length.
		 *
		 * @var integer
		 */
		protected $code_length = 6;

		/**
		 * Class Google_auth_onpremise constructor
		 */
		public function __construct() {
		}

		/**
		 * Gets the google authenticator details.
		 *
		 * @param boolean $setup_wizard Boolean value if GA setup through setupwizard.
		 * @return void
		 */
		public function mo_g_auth_get_details( $setup_wizard = false ) {
			$user = wp_get_current_user();

			$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( isset( $_POST['mo2f_session_id'] ) && isset( $_POST['mo2f_session_id'] ) && true === $setup_wizard && wp_verify_nonce( $nonce, 'mo-two-factor-ajax-nonce' ) ) {
				$session_id_encrypt = sanitize_text_field( wp_unslash( $_POST['mo2f_session_id'] ) );
			} else {
				$session_id_encrypt = MO2f_Utility::random_str( 20 );
			}
			$secret_ga = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'secret_ga' );
			if ( ! $secret_ga ) {
				$secret_ga = $this->mo2f_create_secret();
				MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'secret_ga', $secret_ga );
			}

			$issuer  = get_option( 'mo2f_google_appname', DEFAULT_GOOGLE_APPNAME );
			$email   = $user->user_email;
			$otpcode = $this->mo2f_get_code( $secret_ga );
			$url     = $this->mo2f_geturl( $secret_ga, $issuer, $email );
			if ( ! $setup_wizard ) {
				echo '<div class="mo2f_table_layout mo2f_table_layout1">';
				mo2f_configure_google_authenticator_onprem( $secret_ga, $url, $session_id_encrypt );
				echo '</div>';
			} else {
				mo2f_configure_google_authenticator_setupwizard( $secret_ga, $url, $otpcode, $session_id_encrypt );
			}
		}

		/**
		 * Sets the google authenticator secret.
		 *
		 * @param integer $user_id User id of the user.
		 * @param string  $secret Google authenticator secret.
		 * @return void
		 */
		public function mo_g_auth_set_secret( $user_id, $secret ) {
			$key = $this->random_str( 8 );
			update_user_meta( $user_id, 'mo2f_get_auth_rnd_string', $key );
			$secret = Mo2f_GAuth_AESEncryption::encrypt_data_ga( $secret, $key );
			update_user_meta( $user_id, 'mo2f_gauth_key', $secret );
		}

		/**
		 * Gets the google authenticator secret.
		 *
		 * @param integer $user_id User id of the user.
		 * @return string
		 */
		public function mo_a_auth_get_secret( $user_id ) {
			$key    = get_user_meta( $user_id, 'mo2f_get_auth_rnd_string', true );
			$secret = get_user_meta( $user_id, 'mo2f_gauth_key', true );
			$secret = Mo2f_GAuth_AESEncryption::decrypt_data( $secret, $key );
			return $secret;
		}

		/**
		 * Generates random string.
		 *
		 * @param integer $length Length of the string.
		 * @param string  $keyspace Keyspace.
		 * @return string
		 */
		public function random_str( $length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {
			$random_string     = '';
			$characters_length = strlen( $keyspace );
			for ( $i = 0; $i < $length; $i++ ) {
				$random_string .= $keyspace[ random_int( 0, $characters_length - 1 ) ];
			}
			return $random_string;
		}

		/**
		 * Creates secret according to the given length.
		 *
		 * @param integer $secret_length Length of the secret.
		 * @throws Exception Throws exception.
		 * @return string
		 */
		public function mo2f_create_secret( $secret_length = 16 ) {
			$valid_chars = $this->mo2f_get_base32_lookup_table();
			// Valid secret lengths are 80 to 640 bits.
			if ( $secret_length < 16 || $secret_length > 128 ) {
				throw new Exception( 'Bad secret length' );
			}
			$secret = '';
			$rnd    = false;
			if ( function_exists( 'random_bytes' ) ) {
				$rnd = random_bytes( $secret_length );
			} elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
				$rnd = openssl_random_pseudo_bytes( $secret_length, $crypto_strong );
				if ( ! $crypto_strong ) {
					$rnd = false;
				}
			}
			if ( false !== $rnd ) {
				for ( $i = 0; $i < $secret_length; ++$i ) {
					$secret .= $valid_chars[ ord( $rnd[ $i ] ) & 31 ];
				}
			} else {
				throw new Exception( 'No source of secure random' );
			}
			return $secret;
		}

		/**
		 * Returns the Base32 lookup table.
		 *
		 * @return array
		 */
		public function mo2f_get_base32_lookup_table() {
			return array(
				'A',
				'B',
				'C',
				'D',
				'E',
				'F',
				'G',
				'H',
				'I',
				'J',
				'K',
				'L',
				'M',
				'N',
				'O',
				'P',
				'Q',
				'R',
				'S',
				'T',
				'U',
				'V',
				'W',
				'X',
				'Y',
				'Z',
				'2',
				'3',
				'4',
				'5',
				'6',
				'7',
				'=',  // padding char.
			);
		}

		/**
		 * Verifies the entered google authenticator code with the generated code.
		 *
		 * @param string  $secret Given secret.
		 * @param string  $code The otp code entered by user.
		 * @param integer $discrepancy The discrepancy.
		 * @param string  $current_time_slice Current time.
		 * @return string
		 */
		public function mo2f_verify_code( $secret, $code, $discrepancy = 3, $current_time_slice = null ) {
			$response = array( 'status' => 'false' );
			if ( null === $current_time_slice ) {
				$current_time_slice = floor( time() / 30 );
			}
			if ( strlen( $code ) !== 6 ) {
				return wp_json_encode( $response );
			}
			$secret = strtoupper( $secret );
			for ( $i = -$discrepancy; $i <= $discrepancy; ++$i ) {
				$calculated_code = $this->mo2f_get_code( $secret, $current_time_slice + $i );
				if ( $this->mo2f_timing_safe_equals( $calculated_code, $code ) ) {
					update_option( 'mo2f_time_slice', $i );
					$response['status'] = 'SUCCESS';
					return wp_json_encode( $response );
				}
			}
			return wp_json_encode( $response );
		}

		/**
		 * Returns url according to the secret, issuer and user email id.
		 *
		 * @param string $secret The google authenticator secret.
		 * @param string $issuer The google authenticator name.
		 * @param string $email The email id of user.
		 * @return string
		 */
		public function mo2f_geturl( $secret, $issuer, $email ) {
			// id can be email or name.
			$url  = 'otpauth://totp/';
			$url .= $email . '?secret=' . $secret . '&issuer=' . $issuer;
			return $url;
		}

		/**
		 * Campares the entered otp code with the generated code.
		 *
		 * @param string $safe_string Generated otp.
		 * @param string $user_string Entered otp by user.
		 * @return bool
		 */
		public function mo2f_timing_safe_equals( $safe_string, $user_string ) {
			if ( function_exists( 'hash_equals' ) ) {
				return hash_equals( $safe_string, $user_string );
			}
			$safe_len = strlen( $safe_string );
			$user_len = strlen( $user_string );

			if ( $user_len !== $safe_len ) {
				return false;
			}

			$result = 0;

			for ( $i = 0; $i < $user_len; ++$i ) {
				$result |= ( ord( $safe_string[ $i ] ) ^ ord( $user_string[ $i ] ) );
			}

			// They are only identical strings if $result is exactly 0...
			return 0 === $result;
		}

		/**
		 * Gets the google authenticator generated code.
		 *
		 * @param string $secret Google authenticator secret.
		 * @param string $time_slice Time.
		 * @return string
		 */
		public function mo2f_get_code( $secret, $time_slice = null ) {
			if ( null === $time_slice ) {
				$time_slice = floor( time() / 30 );
			}

			$secretkey = $this->mo2f_base32_decode( $secret );
			// Pack time into binary string.
			$time = chr( 0 ) . chr( 0 ) . chr( 0 ) . chr( 0 ) . pack( 'N*', $time_slice );
			// Hash it with users secret key.
			$hm = hash_hmac( 'SHA1', $time, $secretkey, true );

			// Use last nipple of result as index/offset.
			$offset = ord( substr( $hm, -1 ) ) & 0x0F;

			// grab 4 bytes of the result.
			$hashpart = substr( $hm, $offset, 4 );
			// Unpak binary value.
			$value = unpack( 'N', $hashpart );
			$value = $value[1];
			// Only 32 bits.
			$value  = $value & 0x7FFFFFFF;
			$modulo = pow( 10, $this->code_length );
			return str_pad( $value % $modulo, $this->code_length, '0', STR_PAD_LEFT );
		}

		/**
		 * Decodes the Google authenticator secret.
		 *
		 * @param string $secret The google authenticator secret.
		 * @return string
		 */
		public function mo2f_base32_decode( $secret ) {
			if ( empty( $secret ) ) {
				return '';
			}
			$base32chars         = $this->mo2f_get_base32_lookup_table();
			$base32chars_flipped = array_flip( $base32chars );
			$paddingchar_count   = substr_count( $secret, $base32chars[32] );
			$allowed_values      = array( 6, 4, 3, 1, 0 );
			if ( ! in_array( $paddingchar_count, $allowed_values, true ) ) {
				return false;
			}
			for ( $i = 0; $i < 4; ++$i ) {
				if ( $paddingchar_count === $allowed_values[ $i ] && substr( $secret, -( $allowed_values[ $i ] ) ) !== str_repeat( $base32chars[32], $allowed_values[ $i ] ) ) {
					return false;
				}
			}
			$secret        = str_replace( '=', '', $secret );
			$secret        = str_split( $secret );
			$binary_string = '';
			$secret_count  = count( $secret );
			for ( $i = 0; $i < $secret_count; $i = $i + 8 ) {
				$x = '';
				if ( ! in_array( $secret[ $i ], $base32chars, true ) ) {
					return false;
				}
				for ( $j = 0; $j < 8; ++$j ) {
					$x .= str_pad( base_convert( $base32chars_flipped[ $secret[ $i + $j ] ], 10, 2 ), 5, '0', STR_PAD_LEFT );
				}
				$eight_bits       = str_split( $x, 8 );
				$eight_bits_count = count( $eight_bits );
				for ( $z = 0; $z < $eight_bits_count; ++$z ) {
					$y              = chr( base_convert( $eight_bits[ $z ], 2, 10 ) );
					$binary_string .= ( ( $y ) || ord( $y ) === 48 ) ? $y : '';
				}
			}
			return $binary_string;
		}
	}
}
