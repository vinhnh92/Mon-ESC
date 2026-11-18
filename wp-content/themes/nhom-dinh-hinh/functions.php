<?php
/**
 * Theme Nhôm Định Hình.
 */

defined( 'ABSPATH' ) || exit;

define( 'NDH_THEME_VERSION', '1.0.0' );

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'nhom-dinh-hinh', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 60, 'width' => 200, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'responsive-embeds' );

	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 400,
		'single_image_width'    => 700,
		'product_grid'          => array( 'default_columns' => 4, 'default_rows' => 3, 'min_columns' => 2, 'max_columns' => 5 ),
	) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus( array(
		'primary' => __( 'Menu chính', 'nhom-dinh-hinh' ),
		'footer'  => __( 'Menu chân trang', 'nhom-dinh-hinh' ),
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'ndh-fonts', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'nhom-dinh-hinh', get_template_directory_uri() . '/assets/css/main.css', array(), NDH_THEME_VERSION );
	wp_enqueue_script( 'nhom-dinh-hinh', get_template_directory_uri() . '/assets/js/main.js', array(), NDH_THEME_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

add_action( 'widgets_init', function () {
	register_sidebar( array(
		'name'          => __( 'Sidebar cửa hàng', 'nhom-dinh-hinh' ),
		'id'            => 'shop',
		'description'   => __( 'Hiển thị bên trái trang danh sách sản phẩm (lọc giá, danh mục…).', 'nhom-dinh-hinh' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
} );

/** Số điện thoại hotline (cài trong Cài đặt > Tổng quan, do plugin Nhôm Gia Công thêm vào). */
function ndh_phone() {
	return (string) get_option( 'ndh_phone', '' );
}

/**
 * Hình mặt cắt thanh nhôm định hình (dùng cho logo và banner).
 */
function ndh_profile_svg( $class = '' ) {
	$slot = '<polygon points="16,-1 16,3.5 11,3.5 14.5,11 25.5,11 29,3.5 24,3.5 24,-1" class="ndh-cut"/>';
	$out  = '<svg class="' . esc_attr( $class ) . '" viewBox="-1 -1 42 42" aria-hidden="true" focusable="false">';
	$out .= '<rect x="0" y="0" width="40" height="40" rx="1.5" class="ndh-metal"/>';
	foreach ( array( 0, 90, 180, 270 ) as $deg ) {
		$out .= '<g transform="rotate(' . $deg . ' 20 20)">' . $slot . '</g>';
	}
	$out .= '<circle cx="20" cy="20" r="4.2" class="ndh-cut"/>';
	foreach ( array( array( 5.5, 5.5 ), array( 34.5, 5.5 ), array( 5.5, 34.5 ), array( 34.5, 34.5 ) ) as $c ) {
		$out .= '<circle cx="' . $c[0] . '" cy="' . $c[1] . '" r="2.2" class="ndh-cut"/>';
	}
	return $out . '</svg>';
}

if ( class_exists( 'WooCommerce' ) ) {
	require get_template_directory() . '/inc/woocommerce.php';
}

// Dùng emoji gốc của hệ điều hành, bỏ script emoji của WordPress (nhẹ hơn, không gọi CDN ngoài).
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
} );
