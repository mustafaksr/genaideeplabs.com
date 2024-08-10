<?php
/**
 * Pricing page offer note.
 *
 * @package miniorange-2-factor-authentication/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="mo2fa-offer-banner" style="background-image:url(<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'includes/images/offer-notice.jpg' ); ?>);">
<i class="mo2fa-gift fa-solid fa-gift"></i> 
<?php
		printf(
			esc_html(
				/* Translators: %s: bold tags and links*/
				__(
					'Get Your License Extended%1$s UPTO 3 EXTRA MONTHS on any of our premium plans.%2$s Hurry Up! offer valid till limited period of time. Contact us at %3$smfasupport@xecurify.com%4$s.',
					'miniorange-2-factor-authentication'
				)
			),
			'<b class="glow">',
			'</b>',
			'<b><a href="mailto:mfasupport@xecurify.com" class="mo2fa-notice-email-link">',
			'</a></b>'
		);
		?>
</div>
<br>
