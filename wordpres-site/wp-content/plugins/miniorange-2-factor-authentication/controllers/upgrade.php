<?php
/**
 * To show the difference between free and premium feature.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

// Needed in both.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

		/**
		 * This file used to render upgrade page UI.
		 *
		 * @package miniorange-2-factor-authentication/controllers
		 */

		require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'upgrade.php';
		update_site_option( 'mo_2fa_pnp', time() );
		update_site_option( 'mo2fa_visit', intval( get_site_option( 'mo2fa_visit', 0 ) ) + 1 );
