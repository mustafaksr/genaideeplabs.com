<?php
/**
 * Title: Hero Archive
 * Slug: archive
 * Categories: hero
 */
?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|md","bottom":"var:preset|spacing|md"},"blockGap":"var:preset|spacing|md"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--md);padding-bottom:var(--wp--preset--spacing--md)"><!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|xs"}}},"className":"is-style-sub-heading"} -->
<p class="aligncenter has-text-align-center is-style-sub-heading aligncenter" style="margin-top:var(--wp--preset--spacing--xs)">{archive_name}</p>
<!-- /wp:paragraph -->
<!-- wp:query-title {"type":"archive","textAlign":"center"} /-->
<!-- wp:post-terms {"term":"category","textAlign":"center","style":{"spacing":{"blockGap":"var:preset|spacing|xs","padding":{"right":"var:preset|spacing|xs","left":"var:preset|spacing|xs"}}},"align":"center","showAll":true} /--></div>
<!-- /wp:group -->