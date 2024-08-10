<?php
/**
 * Title: Blog Featured Post
 * Slug: featured-post
 * Categories: blog
 */
?>
<!-- wp:query {"queryId":3,"query":{"perPage":"1","pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|xl"}}}} -->
<div class="wp-block-query alignwide" style="margin-top:var(--wp--preset--spacing--xl)"><!-- wp:post-template -->
<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"padding":{"top":"var:preset|spacing|lg","bottom":"var:preset|spacing|lg","left":"var:preset|spacing|lg","right":"var:preset|spacing|lg"},"blockGap":{"top":"var:preset|spacing|lg","left":"var:preset|spacing|lg"}}},"backgroundColor":"neutral-900","animation":{"name":"fade-in-up","duration":1}} -->
<div class="wp-block-columns are-vertically-aligned-center has-neutral-900-background-color has-background has-animation" style="padding-top:var(--wp--preset--spacing--lg);padding-right:var(--wp--preset--spacing--lg);padding-bottom:var(--wp--preset--spacing--lg);padding-left:var(--wp--preset--spacing--lg);animation-name:fade-in-up;animation-duration:1s;animation-iteration-count:1"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:group {"style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"spacing":{"blockGap":"var:preset|spacing|xs"}},"layout":{"type":"flex","flexWrap":"nowrap"},"fontSize":"14"} -->
<div class="wp-block-group has-14-font-size" style="font-style:normal;font-weight:500"><!-- wp:post-terms {"term":"category","className":"is-style-badges","fontSize":"inherit"} /-->
<!-- wp:post-date {"style":{"elements":{"link":{"color":{"text":"var:preset|color|primary-200"}}}},"textColor":"primary-200","fontSize":"inherit"} /-->
<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|primary-200"}}}},"textColor":"primary-200"} -->
<p class="has-primary-200-color has-text-color has-link-color">{read_time} min read</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:post-title {"isLink":true,"gradient":"primary-background"} /--></div>
<!-- /wp:group -->
<!-- wp:post-excerpt {"moreText":"","excerptLength":20,"hideReadMore":true} /-->
<!-- wp:post-author {"avatarSize":24,"showBio":false,"byline":"","style":{"spacing":{"blockGap":"var:preset|spacing|xs"}},"fontSize":"16"} /--></div>
<!-- /wp:column -->
<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3","style":{"border":{"radius":"20px"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->