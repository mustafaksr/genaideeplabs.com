<?php
/**
 * File contains user's feedback related functions at the time of deactivation of plugin.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Onprem\MO2f_Utility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'FeedbackHandler' ) ) {
	/**
	 * Class FeedbackHandler
	 */
	class FeedbackHandler {

		/**
		 * FeedbackHandler class constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'mo_wpns_feedback_actions' ) );
		}

		/**
		 * Checks for post option value in the switch case.
		 *
		 * @return mixed
		 */
		public function mo_wpns_feedback_actions() {
			$nonce = isset( $_POST['mo_wpns_feedback_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_wpns_feedback_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mo-wpns-feedback-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username_feedback', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} else {
				if ( current_user_can( 'manage_options' ) && isset( $_POST['option'] ) ) {
					switch ( sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) {
						case 'mo_wpns_skip_feedback':
						case 'mo_wpns_feedback':
							$this->wpns_handle_feedback( $_POST );
							break;
						case 'log_file_download':
							$this->mo2f_download_log_file();
							break;
					}
				}
			}
		}

		/**
		 * Sends the users feedback to miniOrange 2fa support when users deactivate the plugin.
		 *
		 * @param array $postdata The information received in the post request.
		 * @return mixed
		 */
		public function wpns_handle_feedback( $postdata ) {
			if ( MO2F_TEST_MODE ) {
				deactivate_plugins( dirname( dirname( __FILE__ ) ) . '\\miniorange_2_factor_settings.php' );
				return;
			}
			$user              = wp_get_current_user();
			$feedback_option   = isset( $postdata['option'] ) ? sanitize_text_field( wp_unslash( $postdata['option'] ) ) : '';
			$message           = 'Plugin Deactivated : ';
			$deactivate_plugin = isset( $postdata['mo_wpns_deactivate_plugin'] ) ? sanitize_text_field( wp_unslash( $postdata['mo_wpns_deactivate_plugin'] ) ) : '';
			$message          .= $deactivate_plugin;
			if ( 'Conflicts with other plugins' === $deactivate_plugin ) {
				$plugin_selected = isset( $postdata['mo2f_plugin_selected'] ) ? sanitize_text_field( wp_unslash( $postdata['mo2f_plugin_selected'] ) ) : '';
				$plugin          = MO2f_Utility::get_plugin_name_by_identifier( $plugin_selected );
				$message        .= ', Plugin selected - ' . $plugin . '.';
			}
			$send_configuration        = isset( $postdata['mo2f_get_reply'] ) ? sanitize_text_field( wp_unslash( $postdata['mo2f_get_reply'] ) ) : 0;
			$deactivate_reason_message = array_key_exists( 'wpns_query_feedback', $postdata ) ? htmlspecialchars( sanitize_text_field( wp_unslash( $postdata['wpns_query_feedback'] ) ) ) : false;
			$activation_date           = get_site_option( 'mo2f_activated_time' );
			$current_date              = time();
			$diff                      = $activation_date - $current_date;
			if ( false === $activation_date ) {
				$days = 'NA';
			} else {
				$days = abs( round( $diff / 86400 ) );
			}
			update_site_option( 'No_of_days_active_work', $days, 'yes' );
			$message .= '[D:' . $days . ',';
			if ( MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_2fa_with_network_security', 'get_option' ) ) {
				$message .= '2FA+NS]';
			} else {
				$message .= '2FA]';
			}

			$message .= ', Feedback : ' . $deactivate_reason_message . '';

			if ( isset( $postdata['rate'] ) ) {
				$rate_value = htmlspecialchars( sanitize_text_field( wp_unslash( $postdata['rate'] ) ) );
			} else {
				$rate_value = '--';
			}
			$message .= ', [Rating :' . $rate_value . ']';

			if ( $send_configuration ) {
				$message .= MoWpnsUtility::mo_2fa_send_configuration();
			}

			$email = isset( $postdata['query_mail'] ) ? sanitize_email( wp_unslash( $postdata['query_mail'] ) ) : '';
			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$email = get_option( 'mo2f_email' );
				if ( empty( $email ) ) {
					$email = $user->user_email;
				}
			}
			$phone            = get_option( 'mo_wpns_admin_phone' );
			$feedback_reasons = new MocURL();
			$show_message     = new MoWpnsMessages();
			global $mo_wpns_utility;
			if ( ! is_null( $feedback_reasons ) ) {
				if ( ! $mo_wpns_utility->is_curl_installed() ) {
					deactivate_plugins( dirname( dirname( __FILE__ ) ) . '\\miniorange_2_factor_settings.php' );

				} else {
					$submitted = json_decode( $feedback_reasons->send_email_alert( $email, $phone, $message, $feedback_option ), true );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						if ( is_array( $submitted ) && array_key_exists( 'status', $submitted ) && 'ERROR' === $submitted['status'] ) {
							$show_message->mo2f_show_message( __( $submitted['message'], 'miniorange-2-factor-authentication' ), 'ERROR' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is a string literal.
						} else {
							if ( ! $submitted ) {
								$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::QUERY_SUBMISSION_ERROR ), 'ERROR' );
							}
						}
					}

					if ( 'mo_wpns_feedback' === $feedback_option || 'mo_wpns_skip_feedback' === $feedback_option ) {
						deactivate_plugins( dirname( dirname( __FILE__ ) ) . '\\miniorange_2_factor_settings.php' );
					}
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::FEEDBACK_APPRECIATION ), 'ERROR' );
				}
			}
		}

		/**
		 * Downloads the 2fa logs file.
		 *
		 * @return void
		 */
		public function mo2f_download_log_file() {
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}
			ob_start();

			$nonce = isset( $_POST['mo_wpns_feedback_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_wpns_feedback_nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'mo-wpns-feedback-nonce' ) || ! current_user_can( 'manage_options' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
			} else {
				$debug_log_path = wp_upload_dir();
				$debug_log_path = $debug_log_path['basedir'];
				$file_name      = 'miniorange_debug_log.txt';
				$status         = file_exists( $debug_log_path . DIRECTORY_SEPARATOR . $file_name );
				if ( $status ) {
					header( 'Pragma: public' );
					header( 'Expires: 0' );
					header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
					header( 'Content-Type: application/octet-stream' );
					header( 'Content-Disposition: attachment; filename=' . $file_name );
					header( 'Content-Transfer-Encoding: binary' );
					header( 'Content-Length: ' . filesize( $debug_log_path . DIRECTORY_SEPARATOR . $file_name ) );
					while ( ob_get_level() ) {
						ob_end_clean();
						echo esc_html( $wp_filesystem->get_contents( $debug_log_path . DIRECTORY_SEPARATOR . $file_name ) );
						exit;
					}
				} else {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::FILE_NOT_EXISTS ), 'ERROR' );
				}
			}
		}
	}new FeedbackHandler();
}
