<?php
/** The miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
 * Copyright (C) 2015  miniOrange
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @package        miniorange-2-factor-authentication/handler
 * @license        http://www.gnu.org/copyleft/gpl.html MIT/Expat, see LICENSE.php
 */

namespace TwoFA\Onprem;

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsConstants;
use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'MO2f_Utility' ) ) {
	/**
	 * This library is miniOrange Authentication Service.
	 * Contains Request Calls to Customer service.
	 **/
	class MO2f_Utility {

		/**
		 * This function get hidden phone detail
		 *
		 * @param string $phone carry the phone no.
		 * @return string
		 */
		public static function get_hidden_phone( $phone ) {
			$hidden_phone = 'xxxxxxx' . substr( $phone, strlen( $phone ) - 3 );
			return $hidden_phone;
		}
		/**
		 * Checking empty or not.
		 *
		 * @param string $value It will return the val .
		 * @return boolean
		 */
		public static function mo2f_check_empty_or_null( $value ) {
			if ( ! isset( $value ) || empty( $value ) ) {
				return true;
			}
			return false;
		}
		/**
		 * It is for curl function
		 *
		 * @return boolean
		 */
		public static function is_curl_installed() {
			if ( in_array( 'curl', get_loaded_extensions(), true ) ) {
				return 1;
			} else {
				return 0;
			}
		}
		/**
		 * Return the installed plugin name.
		 *
		 * @return void
		 */
		public static function get_all_plugins_installed() {
			$all_plugins     = get_plugins();
			$plugins         = array();
			$form            = '';
			$plugins['None'] = 'None';
			foreach ( $all_plugins as $plugin_name => $plugin_details ) {
				$plugins[ $plugin_name ] = $plugin_details['Name'];
			}
			unset( $plugins['miniorange-2-factor-authentication/miniorange-2-factor-settings.php'] );
			echo '<div class="mo2f_plugin_select">Please select the plugin<br>
			<select name="mo2f_plugin_selected" id="mo2f-plugin-selected">';
			foreach ( $plugins as $identifier => $name ) {
				echo '<option value="' . esc_attr( $identifier ) . '">' . esc_attr( $name ) . '</option>';
			}
			echo '</select></div>';
		}
		/**
		 * It will check the length of the number.
		 *
		 * @param string $token It will carry the token.
		 * @return boolean
		 */
		public static function mo2f_check_number_length( $token ) {
			if ( is_numeric( $token ) ) {
				if ( strlen( $token ) >= 4 && strlen( $token ) <= 8 ) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		/**
		 * This function will help to check the email pattern is valid or not.
		 *
		 * @param string $email It will carry the email address .
		 * @return string
		 */
		public static function mo2f_get_hidden_email( $email ) {
			if ( ! isset( $email ) || trim( $email ) === '' ) {
				return '';
			}
			$emailsize    = strlen( $email );
			$partialemail = substr( $email, 0, 1 );
			$temp         = strrpos( $email, '@' );
			$endemail     = substr( $email, $temp - 1, $emailsize );
			for ( $i = 1; $i < $temp - 1; $i ++ ) {
				$partialemail = $partialemail . 'x';
			}
			$hiddenemail = $partialemail . $endemail;
			return $hiddenemail;
		}

		/**
		 * Checking the device name
		 *
		 * @param string $useragent It will carry the user agent .
		 * @return boolean
		 */
		public static function check_if_request_is_from_mobile_device( $useragent ) {
			if ( preg_match( '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $useragent ) || preg_match( '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr( $useragent, 0, 4 ) ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * This function will set the user value.
		 *
		 * @param string $user_session_id It will carry the session .
		 * @param string $variable It will carry the variable .
		 * @param string $value It will carry the value .
		 * @return void
		 */
		public static function set_user_values( $user_session_id, $variable, $value ) {
			global $mo2fdb_queries;
			$key         = get_option( 'mo2f_encryption_key' );
			$data_option = null;

			if ( empty( $data_option ) ) {
				// setting session.
				$_SESSION[ $variable ] = $value;

				// setting cookie values.
				if ( is_array( $value ) ) {
					if ( 'mo_2_factor_kba_questions' === $variable ) {
						self::mo2f_set_cookie_values( 'kba_question1', $value[0]['question'] );
						self::mo2f_set_cookie_values( 'kba_question2', $value[1]['question'] );
					}
				} else {
					self::mo2f_set_cookie_values( $variable, $value );
				}

				// setting values in database.
				$user_session_id = self::decrypt_data( $user_session_id, $key );
				$session_id_hash = md5( $user_session_id );
				if ( is_array( $value ) ) {
					$string_value = maybe_serialize( $value );
					$mo2fdb_queries->save_user_login_details( $session_id_hash, array( $variable => $string_value ) );
				} else {
					$mo2fdb_queries->save_user_login_details( $session_id_hash, array( $variable => $value ) );
				}
			} elseif ( ! empty( $data_option ) && 'sessions' === $data_option ) {
				$_SESSION[ $variable ] = $value;
			} elseif ( ! empty( $data_option ) && 'cookies' === $data_option ) {
				if ( is_array( $value ) ) {
					if ( 'mo_2_factor_kba_questions' === $variable ) {
						self::mo2f_set_cookie_values( 'kba_question1', $value[0] );
						self::mo2f_set_cookie_values( 'kba_question2', $value[1] );
					}
				} else {
					self::mo2f_set_cookie_values( $variable, $value );
				}
			} elseif ( ! empty( $data_option ) && 'tables' === $data_option ) {
				$user_session_id = self::decrypt_data( $user_session_id, $key );
				$session_id_hash = md5( $user_session_id );
				if ( is_array( $value ) ) {
					$string_value = maybe_serialize( $value );
					$mo2fdb_queries->save_user_login_details( $session_id_hash, array( $variable => $string_value ) );
				} else {
					$mo2fdb_queries->save_user_login_details( $session_id_hash, array( $variable => $value ) );
				}
			}
		}

		/*
		Returns Random string with length provided in parameter.

		*/
		/**
		 * This function will help to decrypt the data .
		 *
		 * @param string $data It will carry the data .
		 * @param string $key It will carry the key .
		 * @return string
		 */
		public static function decrypt_data( $data, $key ) {
			$c                  = base64_decode( $data ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Not using for obfuscation
			$cipher             = 'AES-128-CBC';
			$ivlen              = openssl_cipher_iv_length( $cipher );
			$iv                 = substr( $c, 0, $ivlen );
			$hmac               = substr( $c, $ivlen, $sha2len = 32 );
			$ciphertext_raw     = substr( $c, $ivlen + $sha2len );
			$original_plaintext = openssl_decrypt( $ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv );
			$calcmac            = hash_hmac( 'sha256', $ciphertext_raw, $key, $as_binary = true );
			$decrypted_text     = '';
			if ( is_string( $hmac ) && is_string( $calcmac ) ) {
				if ( hash_equals( $hmac, $calcmac ) ) {
					$decrypted_text = $original_plaintext;
				}
			}

			return $decrypted_text;
		}
		/**
		 * This function to generate the random string .
		 *
		 * @param string $length It will carry the length .
		 * @param string $keyspace It will carry thye key .
		 * @return string
		 */
		public static function random_str( $length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {
			$random_string     = '';
			$characters_length = strlen( $keyspace );
			$keyspace          = $keyspace . microtime( true );
			$keyspace          = str_shuffle( $keyspace );
			for ( $i = 0; $i < $length; $i ++ ) {
				$random_string .= $keyspace[ wp_rand( 0, $characters_length - 1 ) ];
			}

			return $random_string;
		}
		/**
		 * It is for set transient
		 *
		 * @param string  $session_id It will carry the session id .
		 * @param string  $key It will carry the key data .
		 * @param string  $value It will carry the value data .
		 * @param integer $expiration It will carry the expiration time .
		 * @return void
		 */
		public static function mo2f_set_transient( $session_id, $key, $value, $expiration = 300 ) {
			set_transient( $session_id . $key, $value, $expiration );
			$transient_array         = get_site_option( $session_id, array() );
			$transient_array[ $key ] = $value;
			update_site_option( $session_id, $transient_array );
			self::mo2f_set_session_value( $session_id, $transient_array );
			if ( is_array( $value ) ) {
				$value = wp_json_encode( $value );
			}

			self::mo2f_set_cookie_values( base64_encode( $session_id ), $value ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not using for obfuscation
		}
		/**
		 * This function is called to get the transient data
		 *
		 * @param string $session_id It will carry the session id .
		 * @param string $key It will carry the key key data .
		 * @return string
		 */
		public static function mo2f_get_transient( $session_id, $key ) {
			self::mo2f_start_session();

			if ( isset( $_SESSION[ $session_id ] ) ) {
				$transient_array = $_SESSION[ $session_id ];
				$transient_value = isset( $transient_array[ $key ] ) ? $transient_array[ $key ] : null;
				return $transient_value;
			} elseif ( isset( $_COOKIE[ base64_decode( $session_id ) ] ) ) { //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Not using for obfuscation
				$transient_value = self::mo2f_get_cookie_values( base64_decode( $session_id ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Not using for obfuscation
				return $transient_value;
			} else {
				$transient_value = get_transient( $session_id . $key );
				if ( ! $transient_value ) {
					$transient_array = get_site_option( $session_id );
					$transient_value = isset( $transient_array[ $key ] ) ? $transient_array[ $key ] : null;
				}
				return $transient_value;
			}
		}
		/**
		 * This function is called to get the session value.
		 *
		 * @param string $session_id It will get the session id.
		 * @param string $transient_array .
		 * @return void
		 */
		public static function mo2f_set_session_value( $session_id, $transient_array ) {
			self::mo2f_start_session();
			$_SESSION[ $session_id ] = $transient_array;
		}
		/**
		 * It is to start the session.
		 *
		 * @return void
		 */
		public static function mo2f_start_session() {
			if ( ! session_id() || '' === session_id() || ! isset( $_SESSION ) ) {
				$session_path = ini_get( 'session.save_path' );
				if ( is_writable( $session_path ) && is_readable( $session_path ) && ! headers_sent() ) {
					if ( session_status() !== PHP_SESSION_DISABLED ) {
						session_start();
					}
				}
			}
		}
		/**
		 * It is to retrieve the user temp value.
		 *
		 * @param string $variable .
		 * @param string $session_id It will get the session id .
		 * @return string
		 */
		public static function mo2f_retrieve_user_temp_values( $variable, $session_id = null ) {
			global $mo2fdb_queries;
			$data_option = null;
			if ( empty( $data_option ) ) {
				if ( isset( $_SESSION[ $variable ] ) && ! empty( $_SESSION[ $variable ] ) ) {
					return $_SESSION[ $variable ];
				} else {
					$key          = get_option( 'mo2f_encryption_key' );
					$cookie_value = false;
					if ( 'mo_2_factor_kba_questions' === $variable ) {
						if ( isset( $_COOKIE['kba_question1'] ) && ! empty( $_COOKIE['kba_question1'] ) ) {
							$kba_question1['question'] = self::mo2f_get_cookie_values( 'kba_question1' );
							$kba_question2['question'] = self::mo2f_get_cookie_values( 'kba_question2' );
							$cookie_value              = array( $kba_question1, $kba_question2 );
						}
					} else {
						$cookie_value = self::mo2f_get_cookie_values( $variable );
					}
					if ( $cookie_value ) {
						return $cookie_value;
					} else {
						$session_id      = self::decrypt_data( $session_id, $key );
						$session_id_hash = md5( $session_id );
						$db_value        = $mo2fdb_queries->get_user_login_details( $variable, $session_id_hash );
						if ( 'mo_2_factor_kba_questions' === $variable ) {
							$db_value = maybe_unserialize( $db_value );
						}
						return $db_value;
					}
				}
			} elseif ( ! empty( $data_option ) && 'sessions' === $data_option ) {
				if ( isset( $_SESSION[ $variable ] ) && ! empty( $_SESSION[ $variable ] ) ) {
					return $_SESSION[ $variable ];
				}
			} elseif ( ! empty( $data_option ) && 'cookies' === $data_option ) {
				$key          = get_option( 'mo2f_encryption_key' );
				$cookie_value = false;

				if ( 'mo_2_factor_kba_questions' === $variable ) {
					if ( isset( $_COOKIE['kba_question1'] ) && ! empty( $_COOKIE['kba_question1'] ) ) {
						$kba_question1 = self::mo2f_get_cookie_values( 'kba_question1' );
						$kba_question2 = self::mo2f_get_cookie_values( 'kba_question2' );

						$cookie_value = array( $kba_question1, $kba_question2 );
					}
				} else {
					$cookie_value = self::mo2f_get_cookie_values( $variable );
				}

				if ( $cookie_value ) {
					return $cookie_value;
				}
			} elseif ( ! empty( $data_option ) && 'tables' === $data_option ) {
				$key             = get_option( 'mo2f_encryption_key' );
				$session_id      = self::decrypt_data( $session_id, $key );
				$session_id_hash = md5( $session_id );
				$db_value        = $mo2fdb_queries->get_user_login_details( $variable, $session_id_hash );
				if ( 'mo_2_factor_kba_questions' === $variable ) {
					$db_value = maybe_unserialize( $db_value );
				}
				return $db_value;
			}
		}

		/**
		 * The function gets the cookie value after decoding and decryption.
		 *
		 * @param string $cookiename - It will carry the cookie name .
		 *
		 * @return string
		 */
		public static function mo2f_get_cookie_values( $cookiename ) {
			$key = get_option( 'mo2f_encryption_key' );
			if ( isset( $_COOKIE[ $cookiename ] ) ) {
				$decrypted_data = self::decrypt_data( base64_decode( sanitize_key( wp_unslash( $_COOKIE[ $cookiename ] ) ) ), $key ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Not using for obfuscation

				if ( self::is_json( $decrypted_data ) ) {
					$decrypted_data = json_decode( $decrypted_data );
				}

				if ( $decrypted_data ) {
					$decrypted_data_array = explode( '&', $decrypted_data );

					$cookie_value = $decrypted_data_array[0];
					if ( count( $decrypted_data_array ) === 2 ) {
						$cookie_creation_time = new DateTime( $decrypted_data_array[1] );
					} else {
						$cookie_creation_time = new DateTime( array_pop( $decrypted_data_array ) );
						$cookie_value         = implode( '&', $decrypted_data_array );
					}
					$current_time = new DateTime( 'now' );

					$interval = $cookie_creation_time->diff( $current_time );
					$minutes  = $interval->format( '%i' );

					$is_cookie_valid = $minutes <= 5 ? true : false;

					return $is_cookie_valid ? $cookie_value : false;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		/**
		 * This function is to check wheather it is json.
		 *
		 * @param string $string It will the string message .
		 * @return boolean
		 */
		public static function is_json( $string ) {
			return is_string( $string ) && is_array( json_decode( $string, true ) ) ? true : false;
		}
		/**
		 * The function sets the cookie value after encryption and encoding.
		 *
		 * @param string $cookiename - It will store the cookie name .
		 * @param string $cookievalue - the cookie value to be set .
		 *
		 * @return void
		 */
		public static function mo2f_set_cookie_values( $cookiename, $cookievalue ) {
			$key = get_option( 'mo2f_encryption_key' );

			$current_time = new DateTime( 'now' );
			$current_time = $current_time->format( 'Y-m-d H:i:sP' );
			$cookievalue  = $cookievalue . '&' . $current_time;

			$cookievalue_encrypted  = self::encrypt_data( $cookievalue, $key );
			$_COOKIE[ $cookiename ] = base64_encode( $cookievalue_encrypted ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not using for obfuscation
		}
		/**
		 * It will help to encrypt the data in aes
		 *
		 * @param string $data It will pass the data of the value .
		 * @param string $key  It will pass the key of the value .
		 * @return string .
		 */
		public static function encrypt_data( $data, $key ) {
			$plaintext      = $data;
			$cipher         = 'AES-128-CBC';
			$ivlen          = openssl_cipher_iv_length( $cipher );
			$iv             = openssl_random_pseudo_bytes( $ivlen );
			$ciphertext_raw = openssl_encrypt( $plaintext, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv );
			$hmac           = hash_hmac( 'sha256', $ciphertext_raw, $key, $as_binary = true );
			$ciphertext     = base64_encode( $iv . $hmac . $ciphertext_raw ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not using for obfuscation
			return $ciphertext;
		}

		/**
		 * It will unset the session value
		 *
		 * @param object $variables .
		 * @return void
		 */
		public static function unset_session_variables( $variables ) {
			if ( 'array' === gettype( $variables ) ) {
				foreach ( $variables as $variable ) {
					if ( isset( $_SESSION[ $variable ] ) ) {
						unset( $_SESSION[ $variable ] );
					}
				}
			} else {
				if ( isset( $_SESSION[ $variables ] ) ) {
					unset( $_SESSION[ $variables ] );
				}
			}
		}
		/**
		 * This function is invoke to unset the cookie variable .
		 *
		 * @param mixed $variables .
		 * @return void
		 */
		public static function unset_cookie_variables( $variables ) {
			if ( 'array' === gettype( $variables ) ) {
				foreach ( $variables as $variable ) {
					if ( isset( $_COOKIE[ $variable ] ) ) {
						setcookie( $variable, '', time() - 3600, null, null, null, true );
					}
				}
			} else {
				if ( isset( $_COOKIE[ $variables ] ) ) {
					setcookie( $variables, '', time() - 3600, null, null, null, true );
				}
			}
		}
		/**
		 * This function is invoke to unset the temporaray user detail
		 *
		 * @param string $variables .
		 * @param string $session_id It will carry the session id .
		 * @param string $command It will carry the command message .
		 * @return void
		 */
		public static function unset_temp_user_details_in_table( $variables, $session_id, $command = '' ) {
			global $mo2fdb_queries;
			$key             = get_option( 'mo2f_encryption_key' );
			$session_id      = self::decrypt_data( $session_id, $key );
			$session_id_hash = md5( $session_id );
			if ( 'destroy' === $command ) {
				$mo2fdb_queries->delete_user_login_sessions( $session_id_hash );
			} else {
				$mo2fdb_queries->save_user_login_details( $session_id_hash, array( $variables => '' ) );
			}
		}

		/**
		 * Get plugin name by identifier
		 *
		 * @param string $plugin_identitifier .
		 * @return string .
		 */
		public static function get_plugin_name_by_identifier( $plugin_identitifier ) {
			$all_plugins    = get_plugins();
			$plugin_details = $all_plugins[ $plugin_identitifier ];
			return $plugin_details['Name'] ? $plugin_details['Name'] : 'No Plugin selected';
		}
		/**
		 * It will return the index is exist or not
		 *
		 * @param string $var It will store the variable data .
		 * @param string $index It will carry the index value .
		 * @return boolean .
		 */
		public static function get_index_value( $var, $index ) {
			switch ( $var ) {
				case 'GLOBALS':
					return isset( $GLOBALS[ $index ] ) ? $GLOBALS[ $index ] : false;
				default:
					return false;
			}
		}
		/**
		 * This function is for get codes on email.
		 *
		 * @param array $codes It will carry the code value .
		 * @return string
		 */
		public static function get_codes_email_content( $codes ) {
			global $image_path;
			$message   = '<table cellpadding="25" style="margin:0px auto">
        <tbody>
        <tr>
        <td>
        <table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;border:1px solid #a8adad">
        <tbody>
        <tr>
        <td><img src="' . $image_path . 'includes/images/xecurify-logo.png" alt="Xecurify" style="color:#5fb336;text-decoration:none;display:block;width:auto;height:auto;max-height:35px" class="CToWUd"></td>
        </tr>
        </tbody>
        </table>
        <table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
        <tbody>
        <tr>
        <td>
        <p style="margin-top:0;margin-bottom:20px">Dear Customer,</p>
        <p style="margin-top:0;margin-bottom:10px">You initiated a transaction from <b>WordPress 2 Factor Authentication Plugin</b>:</p>
        <p style="margin-top:0;margin-bottom:10px">Your backup codes are:-
        <table cellspacing="10">
            <tr>';
			$code_size = count( $codes );
			for ( $x = 0; $x < $code_size; $x++ ) {
				$message = $message . '<td>' . $codes[ $x ] . '</td>';
			}
			$message = $message . '</table></p>
        <p style="margin-top:0;margin-bottom:10px">Please use this carefully as each code can only be used once. Please do not share these codes with anyone.</p>
        <p style="margin-top:0;margin-bottom:10px">Also, we would highly recommend you to reconfigure your two-factor after logging in.</p>
        <p style="margin-top:0;margin-bottom:15px">Thank you,<br>miniOrange Team</p>
        <p style="margin-top:0;margin-bottom:0px;font-size:11px">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed.</p>
        </div></div></td>
        </tr>
        </tbody>
        </table>
        </td>
        </tr>
        </tbody>
        </table>';
			return $message;
		}
		/**
		 * This function will show the warning message over email
		 *
		 * @param string $codes_remaining It will show the code remaining.
		 * @return string .
		 */
		public static function get_codes_warning_email_content( $codes_remaining ) {
			global $image_path;
			$message = '<table cellpadding="25" style="margin:0px auto">
        <tbody>
        <tr>
        <td>
        <table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;border:1px solid #a8adad">
        <tbody>
        <tr>
        <td><img src="' . esc_url( $image_path ) . 'includes/images/xecurify-logo.png" alt="Xecurify" style="color:#5fb336;text-decoration:none;display:block;width:auto;height:auto;max-height:35px" class="CToWUd"></td>
        </tr>
        </tbody>
        </table>
        <table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
        <tbody>
        <tr>
        <td>
        <p style="margin-top:0;margin-bottom:20px">Dear Customer,</p>
        <p style="margin-top:0;margin-bottom:10px">You have ' . $codes_remaining . ' backup codes remaining. Kindly reconfigure your two-factor to avoid being locked out.</b></p>
        <p style="margin-top:0;margin-bottom:15px">Thank you,<br>miniOrange Team</p>
        <p style="margin-top:0;margin-bottom:0px;font-size:11px">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed.</p>
        </div></div></td>
        </tr>
        </tbody>
        </table>
        </td>
        </tr>
        </tbody>
        </table>';
			return $message;
		}
		/**
		 * It will invoke to send the back up code over email
		 *
		 * @param string $codes It will carry the back up code .
		 * @param string $mo2f_user_email It will carry the user email .
		 * @return string .
		 */
		public static function mo2f_email_backup_codes( $codes, $mo2f_user_email ) {
			$subject = '2-Factor Authentication(Backup Codes)';
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			$message = self::get_codes_email_content( $codes );
			$result  = wp_mail( $mo2f_user_email, $subject, $message, $headers );
			return $result;
		}
		/**
		 * This function will invoke at the time of download backup code
		 *
		 * @param string   $id It will carry the user id .
		 * @param string[] $codes It will carry the code .
		 * @return void
		 */
		public static function mo2f_download_backup_codes( $id, $codes ) {
			update_user_meta( $id, 'mo_backup_code_downloaded', 1 );
			header( 'Content-Disposition: attachment; filename=miniOrange2-factor-BackupCodes.txt' );
			echo 'Two Factor Backup Codes:' . PHP_EOL . PHP_EOL;
			echo 'These are the codes that can be used in case you lose your phone or cannot access your email. Please reconfigure your authentication method after login.' . PHP_EOL . 'Please use this carefully as each code can only be used once. Please do not share these codes with anyone..' . PHP_EOL . PHP_EOL;
			$size_of = count( $codes );
			for ( $x = 0; $x < $size_of; $x++ ) {
				$str1 = $codes[ $x ];
				echo( intval( $x + 1 ) . '. ' . esc_html( $str1 ) . ' ' );
			}

			exit;
		}
		/**
		 * This function is called when debug log is on
		 *
		 * @param string $text It will carry the text data .
		 * @return void
		 */
		public static function mo2f_debug_file( $text ) {
			if ( MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_debug_log', 'site_option' ) === '1' ) {
				$debug_log_path = wp_upload_dir();
				$debug_log_path = $debug_log_path['basedir'] . DIRECTORY_SEPARATOR;
				$filename       = 'miniorange_debug_log.txt';
				$data           = '[' . gmdate( 'Y/m/d' ) . ' ' . time() . ']:' . $text . "\n";
				$handle         = fopen( $debug_log_path . DIRECTORY_SEPARATOR . $filename, 'a+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen -- fopen
				fwrite( $handle, $data ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite -- fclose
				fclose( $handle ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose -- fclose
			}
		}
		/**
		 * It will call when the backup code is download and sent over email
		 *
		 * @return string
		 */
		public static function mo2f_mail_and_download_codes() {
			global $mo2fdb_queries;

			$id              = get_current_user_id();
			$mo2f_user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $id );
			if ( empty( $mo2f_user_email ) ) {
				$currentuser     = get_user_by( 'id', $id );
				$mo2f_user_email = $currentuser->user_email;
			}
			$generate_backup_code = new MocURL();
			if ( get_transient( 'mo2f_generate_backup_code' ) === '1' ) {
				return 'TransientActive';
			}
			$codes = $generate_backup_code->mo_2f_generate_backup_codes( $mo2f_user_email, site_url() );

			if ( 'LimitReached' === $codes || 'UserLimitReached' === $codes || 'AllUsed' === $codes || 'invalid_request' === $codes ) {
				update_user_meta( $id, 'mo_backup_code_limit_reached', 1 );
				return $codes;
			}
			if ( 'InternetConnectivityError' === $codes ) {
				return $codes;
			}

			$codes  = explode( ' ', $codes );
			$result = self::mo2f_email_backup_codes( $codes, $mo2f_user_email );
			update_user_meta( $id, 'mo_backup_code_generated', 1 );
			update_user_meta( $id, 'mo_backup_code_downloaded', 1 );

			set_transient( 'mo2f_generate_backup_code', '1', 30 );
			self::mo2f_download_backup_codes( $id, $codes );
		}

		/**
		 * Show Invalid User Credentials error on ajax
		 *
		 * @param string $message Message.
		 * @return array
		 */
		public static function mo2f_show_error_on_login( $message ) {
			$data = array( 'notice' => '<div style="border-left:3px solid #dc3232;">&nbsp; ' . $message . '' );
			return $data;
		}
	}
}

