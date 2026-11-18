<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="container content-area notfound">
	<p class="notfound__code">404</p>
	<h1>Không tìm thấy trang</h1>
	<p>Trang bạn tìm có thể đã bị xoá hoặc đổi địa chỉ.</p>
	<p>
		<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>">Về trang chủ</a>
		<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<a class="button button--ghost" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Xem sản phẩm</a>
		<?php endif; ?>
	</p>
</div>
<?php
get_footer();
