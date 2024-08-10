<?php
/**
 * This file contains functions related to login flow.
 *
 * @package miniorange-2-factor-authentication/api
 */

namespace TwoFA\Onprem;

use TwoFA\Traits\Instance;

if ( ! class_exists( 'Mo2f_Api' ) ) {
	/**
	 *  Class contains methods for remote calls.
	 */
	class Mo2f_Api {

		use Instance;

		/**
		 * This function perform remote calls using 'wp_remote_post'.
		 *
		 * @param string $url url.
		 * @param array  $args arguments.
		 * @return mixed
		 */
		public function mo2f_wp_remote_post( $url, $args = array() ) {
			$response = wp_remote_post( $url, $args );
			if ( ! is_wp_error( $response ) ) {
				return $response['body'];
			} else {
				$message = 'Please enable curl extension. <a href="admin.php?page=mo_2fa_troubleshooting">Click here</a> for the steps to enable curl.';
				return wp_json_encode(
					array(
						'status'  => 'ERROR',
						'message' => $message,
					)
				);
			}
		}

		/**
		 * This function returns current timestamp
		 *
		 * @return string
		 */
		public function get_timestamp() {

			$current_time_in_millis = round( microtime( true ) * 1000 );
			$current_time_in_millis = number_format( $current_time_in_millis, 0, '', '' );

			return $current_time_in_millis;
		}

		/**
		 * This function perform remote calls.
		 *
		 * @param string $url url.
		 * @param object $fields fields.
		 * @param array  $http_header_array header.
		 * @return mixed
		 */
		public function mo2f_http_request( $url, $fields, $http_header_array = array(
			'Content-Type'  => 'application/json',
			'charset'       => 'UTF-8',
			'Authorization' => 'Basic',
		) ) {

			if ( gettype( $fields ) !== 'string' ) {
				$fields = wp_json_encode( $fields );
			}

			$args = array(
				'method'      => 'POST',
				'body'        => $fields,
				'timeout'     => '10',
				'redirection' => '5',
				'sslverify'   => true,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => $http_header_array,
			);

			$response = self::mo2f_wp_remote_post( $url, $args );
			return $response;

		}

		/**
		 * This function returns headers.
		 *
		 * @return object
		 */
		public function get_http_header_array() {

			$customer_key = get_option( 'mo2f_customerKey' );
			$api_key      = get_option( 'mo2f_api_key' );

			/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
			$current_time_in_millis = self::get_timestamp();

			/* Creating the Hash using SHA-512 algorithm */
			$string_to_hash = $customer_key . $current_time_in_millis . $api_key;

			$hash_value = hash( 'sha512', $string_to_hash );

			$headers = array(
				'Content-Type'  => 'application/json',
				'Customer-Key'  => $customer_key,
				'Timestamp'     => $current_time_in_millis,
				'Authorization' => $hash_value,
			);

			return $headers;
		}

	}
}
