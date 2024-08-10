<?php
/**
 * File contains trait Instance.
 *
 * @package miniOrange-2-factor-authentication/traits
 */

namespace TwoFA\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
trait Instance {
	/**
	 * Instantiating variable
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Instance class function
	 *
	 * @return mixed
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}
