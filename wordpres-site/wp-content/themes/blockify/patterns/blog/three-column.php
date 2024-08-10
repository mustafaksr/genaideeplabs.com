<?php
/**
 * Title: Blog Three Column
 * Slug: three-column
 * Categories: blog
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|sm","top":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:0;padding-bottom:var(--wp--preset--spacing--sm)"><!-- wp:query {"queryId":0,"query":{"perPage":6,"pages":"","offset":"","postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"align":"wide","layout":{"inherit":false},"style":{"spacing":{"blockGap":"1.5em","padding":{"bottom":"var:preset|spacing|xl"}}}} -->
<div class="wp-block-query alignwide" style="padding-bottom:var(--wp--preset--spacing--xl)"><!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|md"}},"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:pattern {"slug":"blog-card","preview":true} /-->
<!-- /wp:post-template -->
<!-- wp:query-pagination {"style":{"spacing":{"padding":{"top":"var:preset|spacing|lg","bottom":"0"}},"typography":{"textDecoration":"none"}},"className":"is-style-default","layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->