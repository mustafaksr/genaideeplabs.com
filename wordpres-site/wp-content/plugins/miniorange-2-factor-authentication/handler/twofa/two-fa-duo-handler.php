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
 * @package        miniorange-2-factor-authentication/handler/twofa
 * @license        http://www.gnu.org/copyleft/gpl.html MIT/Expat, see LICENSE.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 **/
const INITIAL_BACKOFF_SECONDS = 1;
const MAX_BACKOFF_SECONDS     = 32;
const BACKOFF_FACTOR          = 2;
const RATE_LIMIT_HTTP_CODE    = 429;

/**
 * It is for ping .
 *
 * @param string $skey .
 * @param string $ikey .
 * @param string $host .
 * @return array
 */
function ping( $skey, $ikey, $host ) {
		$method   = 'GET';
		$endpoint = '/auth/v2/ping';
		$params   = array();

		return json_api_call( $method, $endpoint, $params, $skey, $ikey, $host );

}
/**
 * It will help to check the values
 *
 * @param string $skey .
 * @param string $ikey .
 * @param string $host .
 * @return array
 */
function check( $skey, $ikey, $host ) {
		$method   = 'GET';
		$endpoint = '/auth/v2/check';
		$params   = array();

		return json_api_call( $method, $endpoint, $params, $skey, $ikey, $host );
}

/**
 * It will delete the data
 *
 * @param string $skey .
 * @param string $ikey .
 * @param string $host .
 * @param int    $user_id .
 * @return array
 */
function delete( $skey, $ikey, $host, $user_id ) {
		$method   = 'DELETE';
		$endpoint = '/admin/v1/users/' . $user_id;
		$params   = array();
		return json_api_call( $method, $endpoint, $params, $skey, $ikey, $host );
}
/**
 * It will invoke to enroll the user
 *
 * @param string $username .
 * @param string $valid_secs .
 * @return array .
 */
function enroll( $username = null, $valid_secs = null ) {
		$ikey = get_site_option( 'mo2f_d_integration_key' );
		$skey = get_site_option( 'mo2f_d_secret_key' );
		$host = get_site_option( 'mo2f_d_api_hostname' );
		assert( is_string( $username ) || is_null( $username ) );
		assert( is_int( $valid_secs ) || is_null( $valid_secs ) );

		$method   = 'POST';
		$endpoint = '/auth/v2/enroll';
		$params   = array();

	if ( $username ) {
		$params['username'] = $username;
	}
	if ( $valid_secs ) {
		$params['valid_secs'] = $valid_secs;
	}

		return json_api_call( $method, $endpoint, $params, $skey, $ikey, $host );
}
/**
 * It will enroll the status
 *
 * @param string $user_id .
 * @param string $activation_code .
 * @param string $skey .
 * @param string $ikey .
 * @param string $host .
 * @return string
 */
function enroll_status( $user_id, $activation_code, $skey, $ikey, $host ) {
	assert( is_string( $user_id ) );
	assert( is_string( $activation_code ) );
	$method   = 'POST';
	$endpoint = '/auth/v2/enroll_status';
	$params   = array(
		'user_id'         => $user_id,
		'activation_code' => $activation_code,
	);

	return json_api_call( $method, $endpoint, $params, $skey, $ikey, $host );
}

/**
 * It will preauth the value
 *
 * @param string $user_identifier .
 * @param string $username .
 * @param string $skey .
 * @param string $ikey .
 * @param string $host .
 * @param string $ipaddr .
 * @param string $trusted_device_token .
 * @return string
 */
function preauth(
		$user_identifier,
		$username,
		$skey,
		$ikey,
		$host,
		$ipaddr = null,
		$trusted_device_token = null

	) {

	assert( is_string( $ipaddr ) || is_null( $ipaddr ) );
	assert( is_string( $trusted_device_token ) || is_null( $trusted_device_token ) );
	$method   = 'POST';
	$endpoint = '/auth/v2/preauth';
	$params   = array();

	if ( $username ) {
		$params['username'] = $user_identifier;
	} else {
		$params['user_id'] = $user_identifier;
	}
	if ( $ipaddr ) {
		$params['ipaddr'] = $ipaddr;
	}
	if ( $trusted_device_token ) {
		$params['trusted_device_token'] = $trusted_device_token;
	}

	return json_api_call( $method, $endpoint, $params, $skey, $ikey, $host );
}

/**
 * This function will set the duo as a authenticator
 *
 * @param string  $user_identifier .
 * @param string  $factor .
 * @param string  $factor_params .
 * @param string  $skey .
 * @param string  $ikey .
 * @param string  $host .
 * @param boolean $username .
 * @param string  $ipaddr .
 * @param boolean $async .
 * @param integer $timeout .
 * @return string
 */
function mo2f_duo_auth(
		$user_identifier,
		$factor,
		$factor_params,
		$skey,
		$ikey,
		$host,
		$username = true,
		$ipaddr = null,
		$async = false,
		$timeout = 60
	) {
		assert( is_string( $user_identifier ) );
		assert(
			is_string( $factor ) &&
			in_array( $factor, array( 'auto', 'push', 'passcode', 'sms', 'phone' ), true )
		);
		assert( is_array( $factor_params ) );
		assert( is_string( $ipaddr ) || is_null( $ipaddr ) );
		assert( is_bool( $async ) );
		assert( is_bool( $username ) );

		$method   = 'POST';
		$endpoint = '/auth/v2/auth';
		$params   = array();

	if ( $username ) {
		$params['username'] = $user_identifier;
	} else {
		$params['user_id'] = $user_identifier;
	}
	if ( $ipaddr ) {
		$params['ipaddr'] = $ipaddr;
	}
	if ( $async ) {
		$params['async'] = '1';
	}

		$params['factor'] = $factor;

	if ( 'push' === $factor ) {
		assert( array_key_exists( 'device', $factor_params ) && is_string( $factor_params['device'] ) );
		$params['device'] = $factor_params['device'];

		if ( array_key_exists( 'type', $factor_params ) ) {
			$params['type'] = $factor_params['type'];
		}
		if ( array_key_exists( 'display_username', $factor_params ) ) {
			$params['display_username'] = $factor_params['display_username'];
		}
		if ( array_key_exists( 'pushinfo', $factor_params ) ) {
			$params['pushinfo'] = $factor_params['pushinfo'];
		}
	} elseif ( 'passcode' === $factor ) {
		assert( array_key_exists( 'passcode', $factor_params ) && is_string( $factor_params['passcode'] ) );
		$params['passcode'] = $factor_params['passcode'];
	} elseif ( 'phone' === $factor ) {
		assert( array_key_exists( 'device', $factor_params ) && is_string( $factor_params['device'] ) );
		$params['device'] = $factor_params['device'];
	} elseif ( 'sms' === $factor ) {
		assert( array_key_exists( 'device', $factor_params ) && is_string( $factor_params['device'] ) );
		$params['device'] = $factor_params['device'];
	} elseif ( 'auto' === $factor ) {
		assert( array_key_exists( 'device', $factor_params ) && is_string( $factor_params['device'] ) );
		$params['device'] = $factor_params['device'];
	}

		$options           = array(
			'timeout' => $timeout,
		);
		$requester_timeout = array_key_exists( 'timeout', $options ) ? $options['timeout'] : null;
		if ( ! $requester_timeout || $requester_timeout < $timeout ) {
			set_requester_option( 'timeout', $timeout );
		}

		try {
			$result = json_api_call( $method, $endpoint, $params, $skey, $ikey, $host );
		} finally {

			if ( $requester_timeout ) {
				set_requester_option( 'timeout', $requester_timeout );
			} else {
				unset( $options['timeout'] );
			}
		}
		return $result;
}
/**
 * It will set the request handler
 *
 * @param string $option .
 * @param string $value .
 * @return string
 */
function set_requester_option( $option, $value ) {
		$options[ $option ] = $value;
		return $options;
}
/**
 * It will help to sent .
 *
 * @param string $seconds .
 * @return void
 */
function mo2f_sleep( $seconds ) {
	usleep( $seconds * 1000000 );
}

/**
 * It will help to execute the function
 *
 * @param string $url .
 * @param string $method .
 * @param string $headers .
 * @param string $body .
 * @return string
 */
function execute( $url, $method, $headers, $body = null ) {
	assert( is_string( $url ) );
	assert( is_string( $method ) );
	assert( is_array( $headers ) );
	assert( is_string( $body ) || is_null( $body ) );

	$headers = array_map(
		function ( $key, $value ) {
			return sprintf( '%s: %s', $key, $value );
		},
		array_keys( $headers ),
		array_values( $headers )
	);

	$args = array(
		'method'      => $method,
		'timeout'     => '5',
		'redirection' => '5',
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => $headers,
	);

	if ( 'POST' === $method ) {
		$args['body'] = $body;
	}

	$result = wp_remote_post( $url, $args );

	if ( is_wp_error( $result ) ) {
		return array(
			'response'         => '',
			'success'          => '',
			'http_status_code' => '',
		);
	}

	$status_code = wp_remote_retrieve_response_code( $result );

	$http_status_code = null;
	$success          = true;
	if ( false === $result ) {

		$result  = wp_json_encode(
			array(
				'stat' => 'FAIL',
			)
		);
		$success = false;
	} else {
		$http_status_code = isset( $status_code ) ? $status_code : '404';
	}

	return array(
		'response'         => $result['body'],
		'success'          => $success,
		'http_status_code' => $http_status_code,
	);
}

/**
 * It will help to json api call .
 *
 * @param string $method .
 * @param string $path .
 * @param string $params .
 * @param string $skey .
 * @param string $ikey .
 * @param string $host .
 * @return string
 */
function json_api_call( $method, $path, $params, $skey, $ikey, $host ) {
	assert( is_string( $method ) );
	assert( is_string( $path ) );
	assert( is_array( $params ) );

	$result = api_call( $method, $path, $params, $skey, $ikey, $host );

	$result['response'] = json_decode( $result['response'], true );
	return $result;
}
/**
 * It will encode the url
 *
 * @param string $params .
 * @return array
 */
function url_encode_parameters( $params ) {
	assert( is_array( $params ) );

	ksort( $params );
	$args = array_map(
		function ( $key, $value ) {
			return sprintf( '%s=%s', rawurlencode( $key ), rawurlencode( $value ) );
		},
		array_keys( $params ),
		array_values( $params )
	);
	return implode( '&', $args );
}
/**
 * It will help to canonical
 *
 * @param string $method .
 * @param string $host .
 * @param string $path .
 * @param string $params .
 * @param string $now .
 * @return string
 */
function canonicalize( $method, $host, $path, $params, $now ) {
	assert( is_string( $method ) );
	assert( is_string( $host ) );
	assert( is_string( $path ) );
	assert( is_array( $params ) );
	assert( is_string( $now ) );

	$args  = url_encode_parameters( $params );
	$canon = array( $now, strtoupper( $method ), strtolower( $host ), $path, $args );

	$canon = implode( "\n", $canon );

	return $canon;
}
/**
 * It will sign the data
 *
 * @param string $msg .
 * @param string $key .
 * @return string
 */
function sign( $msg, $key ) {
	assert( is_string( $msg ) );
	assert( is_string( $key ) );

	return hash_hmac( 'sha1', $msg, $key );
}
/**
 * It will sign the parameter
 *
 * @param string $method .
 * @param string $host .
 * @param string $path .
 * @param string $params .
 * @param string $skey .
 * @param string $ikey .
 * @param string $now .
 * @return string
 */
function sign_parameters( $method, $host, $path, $params, $skey, $ikey, $now ) {
	assert( is_string( $method ) );
	assert( is_string( $host ) );
	assert( is_string( $path ) );
	assert( is_array( $params ) );
	assert( is_string( $skey ) );
	assert( is_string( $ikey ) );
	assert( is_string( $now ) );

	$canon = canonicalize( $method, $host, $path, $params, $now );

	$signature = sign( $canon, $skey );
	$auth      = sprintf( '%s:%s', $ikey, $signature );
	$b64auth   = base64_encode( $auth ); //phpcs:ignore -- Bse64 is needed for the authorization header

	return sprintf( 'Basic %s', $b64auth );
}

/**
 * It will make the Request
 *
 * @param string $method .
 * @param string $uri .
 * @param object $body .
 * @param string $headers .
 * @param string $host .
 * @return string
 */
function make_request( $method, $uri, $body, $headers, $host ) {
	assert( is_string( $method ) );
	assert( is_string( $uri ) );
	assert( is_string( $body ) || is_null( $body ) );
	assert( is_array( $headers ) );
	$url = 'https://' . $host . $uri;

	$backoff_seconds = INITIAL_BACKOFF_SECONDS;
	while ( true ) {
		$result = execute( $url, $method, $headers, $body );

		if ( RATE_LIMIT_HTTP_CODE !== $result['http_status_code'] || $backoff_seconds > MAX_BACKOFF_SECONDS ) {
			return $result;
		}

		mo2f_sleep( $backoff_seconds + ( wp_rand( 0, 1000 ) / 1000.0 ) );
		$backoff_seconds *= BACKOFF_FACTOR;
	}
}
/**
 * It will call an api
 *
 * @param string $method .
 * @param string $path .
 * @param string $params .
 * @param string $skey .
 * @param string $ikey .
 * @param string $host .
 * @return string
 */
function api_call( $method, $path, $params, $skey, $ikey, $host ) {
	assert( is_string( $method ) );
	assert( is_string( $path ) );
	assert( is_array( $params ) );

	$now = gmdate( DateTime::RFC2822 );

	$headers                  = array();
	$headers['Date']          = $now;
	$headers['Host']          = $host;
	$headers['Authorization'] = sign_parameters(
		$method,
		$host,
		$path,
		$params,
		$skey,
		$ikey,
		$now
	);

	if ( in_array( $method, array( 'POST', 'PUT' ), true ) ) {

		$body                      = http_build_query( $params );
		$headers['Content-Type']   = 'application/x-www-form-urlencoded';
		$headers['Content-Length'] = strval( strlen( $body ) );
		$uri                       = $path;
	} else {
		$body = null;
		$uri  = $path . ( ! empty( $params ) ? '?' . url_encode_parameters( $params ) : '' );
	}

	return make_request( $method, $uri, $body, $headers, $host );
}
