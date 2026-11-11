<?php
/**
 * Title: Native WooCommerce product showcase
 * Slug: anotter-demo/product-showcase
 * Categories: featured
 * Inserter: yes
 * @package AnotterDemo
 */
defined( 'ABSPATH' ) || exit;
?>
<!-- wp:woocommerce/product-collection {"queryId":21,"query":{"perPage":3,"pages":1,"offset":0,"postType":"product","order":"asc","orderBy":"menu_order","exclude":[],"inherit":false,"isProductCollectionBlock":true,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":{}},"displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"className":"aot-product-grid"} -->
<div class="wp-block-woocommerce-product-collection aot-product-grid">
<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfQueryLoop":true,"aspectRatio":"3/2"} /-->
<!-- wp:post-title {"level":3,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true} /-->
<!-- wp:woocommerce/product-stock-indicator /-->
<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-collection-no-results -->
<!-- wp:pattern {"slug":"anotter-demo/empty-catalog"} /-->
<!-- /wp:woocommerce/product-collection-no-results -->
</div><!-- /wp:woocommerce/product-collection -->
