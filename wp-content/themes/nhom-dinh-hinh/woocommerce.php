<?php
/**
 * Khung chung cho mọi trang WooCommerce.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$with_sidebar = ( is_shop() || is_product_taxonomy() ) && is_active_sidebar( 'shop' );
?>
<div class="container shop-wrap <?php echo $with_sidebar ? 'has-sidebar' : ''; ?>">
	<?php if ( $with_sidebar ) : ?>
		<aside class="shop-sidebar"><?php dynamic_sidebar( 'shop' ); ?></aside>
	<?php endif; ?>
	<div class="shop-content">
		<?php woocommerce_content(); ?>
	</div>
</div>
<?php
get_footer();
