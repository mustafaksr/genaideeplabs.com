<?php
/**
 * Title: Blog Card
 * Slug: card
 * Categories: blog
 */
?>
<!-- wp:group {"style":{"spacing":{"blockGap":"0"},"maxWidth":{"all":"400px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:post-featured-image {"isLink":true,"style":{"aspectRatio":{"all":"16/9"},"objectFit":{"all":"cover"},"objectPosition":{"all":"center"},"height":{"all":"100%"},"width":{"all":"100%"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->
<!-- wp:group {"style":{"typography":{"textDecoration":"none"},"spacing":{"blockGap":"var:preset|spacing|xxs","margin":{"top":"var:preset|spacing|xs"}}},"layout":{"type":"flex","flexWrap":"nowrap"},"fontSize":"14"} -->
<div class="wp-block-group has-14-font-size" style="margin-top:var(--wp--preset--spacing--xs);text-decoration:none"><!-- wp:post-date {"style":{"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}},"fontSize":"inherit"} /-->
<!-- wp:paragraph -->
<p>·</p>
<!-- /wp:paragraph -->
<!-- wp:post-terms {"term":"category","className":"is-style-default","fontSize":"inherit"} /--></div>
<!-- /wp:group -->
<!-- wp:post-title {"level":3,"isLink":true,"style":{"spacing":{"margin":{"top":"0.5em","right":"0em","bottom":"0.5em","left":"0em"}},"typography":{"lineHeight":"1.4"}},"fontSize":"24"} /-->
<!-- wp:post-excerpt {"moreText":"Read more","style":{"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}},"hideReadMore":true} /--></div>
<!-- /wp:group -->