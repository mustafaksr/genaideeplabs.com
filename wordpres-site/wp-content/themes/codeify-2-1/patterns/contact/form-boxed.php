<?php
/**
 * Title: Contact Form Boxed
 * Slug: form-boxed
 * Categories: contact
 */
?>
<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|lg","bottom":"var:preset|spacing|lg"},"blockGap":"var:preset|spacing|sm"},"width":{"all":"100%"},"border":{"radius":"30px"},"elements":{"link":{"color":{"text":"var:preset|color|primary-200"}}},"typography":{"fontStyle":"normal","fontWeight":"500"}},"backgroundColor":"neutral-950","textColor":"primary-200","className":"is-style-default","layout":{"type":"default"},"fontSize":"16","animation":{"name":"fade-in-up","duration":1},"shadowPreset":"xxl"} -->
<div class="wp-block-group is-style-default has-primary-200-color has-neutral-950-background-color has-text-color has-background has-link-color has-16-font-size has-animation  has-shadow has-xxl-shadow" style="border-radius:30px;margin-top:var(--wp--preset--spacing--lg);margin-bottom:var(--wp--preset--spacing--lg);font-style:normal;font-weight:500;animation-name:fade-in-up;animation-duration:1s;animation-iteration-count:1"><!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|lg","bottom":"var:preset|spacing|lg","left":"var:preset|spacing|lg","right":"var:preset|spacing|lg"},"blockGap":"var:preset|spacing|sm"},"border":{"radius":"30px"}},"gradient":"light-bulb","layout":{"type":"default"},"animation":{}} -->
<div class="wp-block-group has-light-bulb-gradient-background has-background" style="border-radius:30px;padding-top:var(--wp--preset--spacing--lg);padding-right:var(--wp--preset--spacing--lg);padding-bottom:var(--wp--preset--spacing--lg);padding-left:var(--wp--preset--spacing--lg)"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|xxs"}}}} -->
<p style="margin-bottom:var(--wp--preset--spacing--xxs)">First Name</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<input type="text" id="name" name="name" required>
<!-- /wp:html --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|xxs"}}}} -->
<p style="margin-bottom:var(--wp--preset--spacing--xxs)">Last name</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<input type="email" id="email" name="email" required>
<!-- /wp:html --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|xxs"}}}} -->
<p style="margin-bottom:var(--wp--preset--spacing--xxs)">Email</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<input type="email" id="email" name="email" required>
<!-- /wp:html --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|xxs"}}}} -->
<p style="margin-bottom:var(--wp--preset--spacing--xxs)">Phone</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<input type="phone" id="phone" name="phone" required>
<!-- /wp:html --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|xxs"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p>Message</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<textarea id="message" name="message" rows="6" required></textarea>
<!-- /wp:html --></div>
<!-- /wp:group -->
<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|md"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--md)"><!-- wp:button {"textColor":"primary-950","gradient":"primary-background","width":100,"style":{"elements":{"link":{"color":{"text":"var:preset|color|primary-950"}}}},"size":"large"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-primary-950-color has-primary-background-gradient-background has-text-color has-background has-link-color wp-element-button">Send message</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->