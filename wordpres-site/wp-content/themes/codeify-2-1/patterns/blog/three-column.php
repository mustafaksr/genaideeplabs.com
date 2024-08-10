<?php
/**
 * Title: Blog Three Column
 * Slug: three-column
 * Categories: blog
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|xl","top":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:0;padding-bottom:var(--wp--preset--spacing--xl)"><!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"0","top":"0"},"padding":{"bottom":"var:preset|spacing|sm"}}},"gradient":"primary-background","className":"wp-block-heading"} -->
<h2 class="wp-block-heading has-text-align-center has-primary-background-gradient-background has-background" style="margin-top:0;margin-bottom:0;padding-bottom:var(--wp--preset--spacing--sm)">From the blog</h2>
<!-- /wp:heading -->
<!-- wp:query {"queryId":0,"query":{"perPage":3,"pages":"","offset":"","postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide","layout":{"inherit":false},"style":{"spacing":{"blockGap":"1.5em"}}} -->
<div class="wp-block-query alignwide"><!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|md"}},"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:pattern {"slug":"blog-card","preview":true} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->