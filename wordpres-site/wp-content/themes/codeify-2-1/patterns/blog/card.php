<?php
/**
 * Title: Blog Card
 * Slug: card
 * Categories: blog
 */
?>
<!-- wp:group {"style":{"border":{"radius":"30px"},"position":{"all":"relative"},"overflow":{"all":"hidden"},"spacing":{"blockGap":"0"}},"backgroundColor":"neutral-900","className":"height-100","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group height-100 has-neutral-900-background-color has-background" style="border-radius:30px"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2","style":{"aspectRatio":null,"objectFit":null,"objectPosition":null,"height":null,"width":{"all":"100%"},"spacing":{"margin":{"top":"0","bottom":"0"}},"border":{"radius":{"bottomLeft":"0px","bottomRight":"0px","topLeft":"30px","topRight":"30px"}}}} /-->
<!-- wp:group {"style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|md","bottom":"var:preset|spacing|md","left":"var:preset|spacing|md","right":"var:preset|spacing|md"}},"position":{"all":"relative"},"zIndex":{"all":"1"}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--md);padding-right:var(--wp--preset--spacing--md);padding-bottom:var(--wp--preset--spacing--md);padding-left:var(--wp--preset--spacing--md)"><!-- wp:group {"style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"spacing":{"blockGap":"var:preset|spacing|xs"}},"layout":{"type":"flex","flexWrap":"nowrap"},"fontSize":"12"} -->
<div class="wp-block-group has-12-font-size" style="font-style:normal;font-weight:500"><!-- wp:post-terms {"term":"category","className":"is-style-badges","fontSize":"inherit"} /-->
<!-- wp:post-date {"fontSize":"inherit"} /-->
<!-- wp:paragraph -->
<p>{read_time} min read</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|xxs","bottom":"0"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--xxs);margin-bottom:0"><!-- wp:post-title {"isLink":true,"style":{"spacing":{"margin":{"top":"0.5em","right":"0em","bottom":"0.5em","left":"0em"}},"typography":{"lineHeight":"1.4"}},"gradient":"primary-background","fontSize":"24"} /--></div>
<!-- /wp:group -->
<!-- wp:post-excerpt {"moreText":"Read more","excerptLength":20,"style":{"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}},"hideReadMore":true} /-->
<!-- wp:post-author {"avatarSize":24,"isLink":true,"style":{"typography":{"textDecoration":"none"},"spacing":{"blockGap":"var:preset|spacing|xs"}},"fontSize":"16"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->