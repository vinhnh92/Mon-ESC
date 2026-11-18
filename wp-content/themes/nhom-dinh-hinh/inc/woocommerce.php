<?php
/**
 * Tích hợp WooCommerce.
 */

defined( 'ABSPATH' ) || exit;

// Bỏ wrapper mặc định — theme tự bọc trong woocommerce.php.
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

add_filter( 'woocommerce_breadcrumb_defaults', function ( $args ) {
	$args['home']      = 'Trang chủ';
	$args['delimiter'] = '<span class="sep">/</span>';
	return $args;
} );

add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
} );

// Cập nhật số lượng giỏ hàng trên header qua AJAX.
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	ob_start();
	ndh_cart_count();
	$fragments['.cart-count'] = ob_get_clean();
	return $fragments;
} );

function ndh_cart_count() {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	printf( '<span class="cart-count" data-count="%1$d">%1$d</span>', (int) $count );
}

// Hiển thị "% giảm" thay cho chữ "Giảm giá!".
add_filter( 'woocommerce_sale_flash', function ( $html, $post, $product ) {
	$regular = (float) $product->get_regular_price();
	$sale    = (float) $product->get_sale_price();
	if ( $regular > 0 && $sale > 0 ) {
		return '<span class="onsale">-' . round( ( $regular - $sale ) / $regular * 100 ) . '%</span>';
	}
	return $html;
}, 10, 3 );

// Ghi chú cam kết dưới nút thêm vào giỏ.
add_action( 'woocommerce_after_add_to_cart_form', function () {
	echo '<ul class="trust-list">'
		. '<li>✓ Cắt chính xác ±0,5 mm, mặt cắt vuông, sạch bavia</li>'
		. '<li>✓ Gia công & giao trong 24–48h</li>'
		. '<li>✓ Freeship nội thành cho đơn từ 2.000.000đ</li>'
		. '</ul>';
} );
