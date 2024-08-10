<?php
/**
 * Title: Blog Single Column
 * Slug: single-column
 * Categories: blog
 */
?>
<!-- wp:group {"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><!-- wp:query {"queryId":0,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"layout":{"type":"default"}} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"default"}} -->
<!-- wp:group {"style":{"border":{"radius":"30px"},"spacing":{"blockGap":"0","margin":{"bottom":"var:preset|spacing|lg"}}},"backgroundColor":"neutral-900","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group has-neutral-900-background-color has-background" style="border-radius:30px;margin-bottom:var(--wp--preset--spacing--lg)"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","height":"","style":{"border":{"radius":{"bottomLeft":"0px","bottomRight":"0px"}},"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|lg","bottom":"var:preset|spacing|lg","left":"var:preset|spacing|lg","right":"var:preset|spacing|lg"},"margin":{"top":"0","bottom":"0"},"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--lg);padding-right:var(--wp--preset--spacing--lg);padding-bottom:var(--wp--preset--spacing--lg);padding-left:var(--wp--preset--spacing--lg)"><!-- wp:post-date /-->
<!-- wp:post-title {"level":3,"isLink":true,"style":{"spacing":{"margin":{"top":"var:preset|spacing|xxs","bottom":"var:preset|spacing|xs"}}},"gradient":"primary-background"} /-->
<!-- wp:post-excerpt {"moreText":"Read more","hideReadMore":true} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template -->
<!-- wp:query-pagination {"style":{"spacing":{"padding":{"top":"var:preset|spacing|lg","bottom":"var:preset|spacing|lg","left":"var:preset|spacing|lg","right":"var:preset|spacing|lg"},"margin":{"top":"var:preset|spacing|lg"}},"border":{"radius":"30px"},"typography":{"textDecoration":"none"}},"backgroundColor":"neutral-900","layout":{"type":"flex","justifyContent":"space-between"}} -->
<!-- wp:query-pagination-previous /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->
<!-- wp:query-no-results -->
<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when the query returns no results."} -->
<p></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->