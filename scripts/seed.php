<?php
/**
 * Cấu hình WooCommerce + dữ liệu khởi tạo cho shop nhôm định hình.
 * Chạy bằng: wp eval-file scripts/seed.php   (được gọi từ scripts/install-windows.ps1)
 * An toàn khi chạy lại: chỉ tạo những gì chưa tồn tại.
 *
 * Đọc biến môi trường (từ file .env): STORE_PHONE, STORE_ZALO, STORE_ADDRESS, STORE_CITY,
 * BANK_NAME, BANK_ACCOUNT, BANK_HOLDER, SITE_TAGLINE, SEED_DEMO.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'Cần kích hoạt WooCommerce trước.' );
}

$env = static function ( $key, $default = '' ) {
	$v = getenv( $key );
	return ( false === $v || '' === $v ) ? $default : $v;
};

/* ---------------------------------------------------------------- Cài đặt chung */

$options = array(
	'blogdescription'                          => $env( 'SITE_TAGLINE', 'Nhôm định hình cắt – khoan – taro theo yêu cầu' ),
	'timezone_string'                          => 'Asia/Ho_Chi_Minh',
	'date_format'                              => 'd/m/Y',
	'time_format'                              => 'H:i',
	'start_of_week'                            => 1,
	'ndh_phone'                                => preg_replace( '/[^0-9+]/', '', $env( 'STORE_PHONE' ) ),
	'ndh_zalo'                                 => preg_replace( '/[^0-9+]/', '', $env( 'STORE_ZALO' ) ),
	// WooCommerce — Việt Nam, VNĐ.
	'woocommerce_default_country'              => 'VN',
	'woocommerce_store_address'                => $env( 'STORE_ADDRESS' ),
	'woocommerce_store_city'                   => $env( 'STORE_CITY' ),
	'woocommerce_currency'                     => 'VND',
	'woocommerce_currency_pos'                 => 'right_space',
	'woocommerce_price_thousand_sep'           => '.',
	'woocommerce_price_decimal_sep'            => ',',
	'woocommerce_price_num_decimals'           => 0,
	'woocommerce_weight_unit'                  => 'kg',
	'woocommerce_dimension_unit'               => 'mm',
	'woocommerce_allowed_countries'            => 'specific',
	'woocommerce_specific_allowed_countries'   => array( 'VN' ),
	'woocommerce_ship_to_countries'            => '',
	'woocommerce_calc_taxes'                   => 'no',
	'woocommerce_enable_guest_checkout'        => 'yes',
	'woocommerce_enable_reviews'               => 'yes',
	'woocommerce_coming_soon'                  => 'no',
	'woocommerce_checkout_phone_field'         => 'required',
	'woocommerce_checkout_company_field'       => 'optional',
	'woocommerce_checkout_address_2_field'     => 'hidden',
	'woocommerce_onboarding_profile'           => array( 'skipped' => true ),
	'woocommerce_task_list_hidden'             => 'yes',
	'woocommerce_cod_settings'                 => array(
		'enabled'            => 'yes',
		'title'              => 'Thanh toán khi nhận hàng (COD)',
		'description'        => 'Trả tiền mặt khi nhận hàng. Đơn gia công có giá trị lớn có thể được yêu cầu đặt cọc.',
		'instructions'       => 'Chúng tôi sẽ gọi xác nhận đơn và thông số gia công trước khi cắt.',
		'enable_for_methods' => array(),
		'enable_for_virtual' => 'yes',
	),
	'woocommerce_bacs_settings'                => array(
		'enabled'      => 'yes',
		'title'        => 'Chuyển khoản ngân hàng',
		'description'  => 'Chuyển khoản với nội dung: mã đơn hàng + số điện thoại. Xưởng bắt đầu gia công khi nhận được tiền.',
		'instructions' => 'Nội dung chuyển khoản: mã đơn hàng + số điện thoại.',
	),
	'woocommerce_bacs_accounts'                => array(
		array(
			'account_name'   => $env( 'BANK_HOLDER', 'NGUYEN VAN A' ),
			'account_number' => $env( 'BANK_ACCOUNT', '0123456789' ),
			'bank_name'      => $env( 'BANK_NAME', 'Vietcombank' ),
			'sort_code'      => '',
			'iban'           => '',
			'bic'            => '',
		),
	),
);
foreach ( $options as $k => $v ) {
	update_option( $k, $v );
}
WP_CLI::log( 'Đã cấu hình WooCommerce (VNĐ, Việt Nam, COD + chuyển khoản).' );

/* ---------------------------------------------------------------- Trang WooCommerce */

WC_Install::create_pages();
// Giỏ hàng / thanh toán dạng shortcode để các tuỳ biến (thông số gia công, form địa chỉ VN) hiển thị đầy đủ.
foreach ( array( 'cart' => '[woocommerce_cart]', 'checkout' => '[woocommerce_checkout]' ) as $wc_page => $shortcode ) {
	$pid = wc_get_page_id( $wc_page );
	if ( $pid > 0 && false === strpos( get_post_field( 'post_content', $pid ), $shortcode ) ) {
		wp_update_post( array( 'ID' => $pid, 'post_content' => $shortcode ) );
	}
}

/* ---------------------------------------------------------------- Vận chuyển */

$zone_exists = false;
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	$zone_exists = $zone_exists || 'Việt Nam' === $z['zone_name'];
}
if ( ! $zone_exists ) {
	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( 'Việt Nam' );
	$zone->add_location( 'VN', 'country' );
	$zone->save();

	$id = $zone->add_shipping_method( 'local_pickup' );
	update_option( "woocommerce_local_pickup_{$id}_settings", array( 'title' => 'Nhận tại xưởng (miễn phí)', 'tax_status' => 'none', 'cost' => '0' ) );

	$id = $zone->add_shipping_method( 'flat_rate' );
	update_option( "woocommerce_flat_rate_{$id}_settings", array( 'title' => 'Giao hàng tận nơi', 'tax_status' => 'none', 'cost' => '50000' ) );

	$id = $zone->add_shipping_method( 'free_shipping' );
	update_option( "woocommerce_free_shipping_{$id}_settings", array( 'title' => 'Miễn phí giao hàng', 'requires' => 'min_amount', 'min_amount' => '2000000' ) );
	WP_CLI::log( 'Đã tạo vận chuyển: nhận tại xưởng, giao 50.000đ, miễn phí từ 2.000.000đ.' );
}

/* ---------------------------------------------------------------- Danh mục */

$cat_defs = array(
	'nhom-dinh-hinh'   => array( 'Nhôm định hình', 'Thanh nhôm định hình cắt theo chiều dài, khoan lỗ, taro ren theo yêu cầu.', '' ),
	'phu-kien'         => array( 'Phụ kiện', 'Phụ kiện lắp ráp khung nhôm định hình.', '' ),
	'ke-goc'           => array( 'Ke góc & liên kết', 'Ke góc, ke ẩn, tấm nối.', 'phu-kien' ),
	'oc-bulong'        => array( 'Ốc T & bulong', 'Ốc T-nut, ốc trượt, bulong lục giác chìm.', 'phu-kien' ),
	'ban-le-tay-nam'   => array( 'Bản lề & tay nắm', 'Bản lề, tay nắm, khoá cửa tủ.', 'phu-kien' ),
	'chan-banh-xe'     => array( 'Chân tăng chỉnh & bánh xe', 'Chân đế, chân tăng chỉnh, bánh xe có khoá.', 'phu-kien' ),
	'nap-bit-che-ranh' => array( 'Nắp bịt & che rãnh', 'Nắp bịt đầu thanh, nẹp che rãnh.', 'phu-kien' ),
);
$cat_ids = array();
foreach ( $cat_defs as $slug => $c ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term ) {
		$cat_ids[ $slug ] = $term->term_id;
		continue;
	}
	$res              = wp_insert_term( $c[0], 'product_cat', array(
		'slug'        => $slug,
		'description' => $c[1],
		'parent'      => $c[2] ? $cat_ids[ $c[2] ] : 0,
	) );
	$cat_ids[ $slug ] = is_wp_error( $res ) ? 0 : $res['term_id'];
}

/* ---------------------------------------------------------------- Ảnh mẫu (GD) */

/** Lưu ảnh GD thành tệp đính kèm trong thư viện media. */
function ndh_seed_save_image( $img, $title, $name ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$upload = wp_upload_dir();
	$file   = trailingslashit( $upload['path'] ) . sanitize_file_name( $name ) . '-' . wp_generate_password( 4, false ) . '.png';
	imagepng( $img, $file );
	imagedestroy( $img );
	$id = wp_insert_attachment( array( 'post_title' => $title, 'post_mime_type' => 'image/png', 'post_status' => 'inherit' ), $file );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	return $id;
}

/** Vẽ chữ to bằng font bitmap có sẵn của GD (không cần file font). */
function ndh_seed_text( $img, $text, $cx, $cy, $max_w, $rgb ) {
	$tw    = imagefontwidth( 5 ) * strlen( $text );
	$th    = imagefontheight( 5 );
	$small = imagecreatetruecolor( $tw, $th );
	imagealphablending( $small, false );
	imagefilledrectangle( $small, 0, 0, $tw, $th, imagecolorallocatealpha( $small, 0, 0, 0, 127 ) );
	imagesavealpha( $small, true );
	imagestring( $small, 5, 0, 0, $text, imagecolorallocate( $small, $rgb[0], $rgb[1], $rgb[2] ) );
	imagealphablending( $img, true );
	$dw = min( $max_w, $tw * 6 );
	$dh = (int) ( $th * $dw / $tw );
	imagecopyresampled( $img, $small, (int) ( $cx - $dw / 2 ), (int) ( $cy - $dh / 2 ), 0, 0, (int) $dw, $dh, $tw, $th );
	imagedestroy( $small );
}

/** Ảnh mặt cắt thanh nhôm (1 hoặc 2 module 40x40 ghép ngang). */
function ndh_seed_profile_image( $title, $label, $modules, $black ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return 0;
	}
	$w   = 800;
	$img = imagecreatetruecolor( $w, $w );
	imagefilledrectangle( $img, 0, 0, $w, $w, imagecolorallocate( $img, 241, 245, 249 ) );
	$metal = $black ? imagecolorallocate( $img, 38, 42, 48 ) : imagecolorallocate( $img, 176, 186, 197 );
	$edge  = $black ? imagecolorallocate( $img, 15, 17, 20 ) : imagecolorallocate( $img, 120, 132, 146 );
	$bg    = imagecolorallocate( $img, 241, 245, 249 );
	$s     = 1 === $modules ? 11 : 7.5; // px mỗi đơn vị (module 40 đơn vị).
	$ox    = ( $w - 40 * $modules * $s ) / 2;
	$oy    = 330 - 20 * $s;
	$pt    = static function ( $x, $y, $deg, $mx ) use ( $s, $ox, $oy ) {
		$r  = deg2rad( $deg );
		$dx = $x - 20;
		$dy = $y - 20;
		return array( $ox + ( $mx * 40 + 20 + $dx * cos( $r ) - $dy * sin( $r ) ) * $s, $oy + ( 20 + $dx * sin( $r ) + $dy * cos( $r ) ) * $s );
	};
	imagefilledrectangle( $img, (int) $ox, (int) $oy, (int) ( $ox + 40 * $modules * $s ), (int) ( $oy + 40 * $s ), $metal );
	imagerectangle( $img, (int) $ox, (int) $oy, (int) ( $ox + 40 * $modules * $s ), (int) ( $oy + 40 * $s ), $edge );
	$slot = array( array( 16, -1 ), array( 16, 3.5 ), array( 11, 3.5 ), array( 14.5, 11 ), array( 25.5, 11 ), array( 29, 3.5 ), array( 24, 3.5 ), array( 24, -1 ) );
	for ( $m = 0; $m < $modules; $m++ ) {
		$sides = array( 0, 180 );
		if ( 0 === $m ) {
			$sides[] = 270;
		}
		if ( $modules - 1 === $m ) {
			$sides[] = 90;
		}
		foreach ( $sides as $deg ) {
			$poly = array();
			foreach ( $slot as $p ) {
				list( $x, $y ) = $pt( $p[0], $p[1], $deg, $m );
				$poly[]        = (int) round( $x );
				$poly[]        = (int) round( $y );
			}
			imagefilledpolygon( $img, $poly, $bg );
		}
		list( $cx, $cy ) = $pt( 20, 20, 0, $m );
		imagefilledellipse( $img, (int) $cx, (int) $cy, (int) ( 8.4 * $s ), (int) ( 8.4 * $s ), $bg );
		foreach ( array( array( 5.5, 5.5 ), array( 34.5, 5.5 ), array( 5.5, 34.5 ), array( 34.5, 34.5 ) ) as $c ) {
			list( $cx, $cy ) = $pt( $c[0], $c[1], 0, $m );
			imagefilledellipse( $img, (int) $cx, (int) $cy, (int) ( 4.4 * $s ), (int) ( 4.4 * $s ), $bg );
		}
	}
	ndh_seed_text( $img, $label, 400, 690, 520, array( 15, 23, 42 ) );
	return ndh_seed_save_image( $img, $title, $label );
}

/** Ảnh ô màu có nhãn cho phụ kiện. */
function ndh_seed_tile_image( $title, $label, $hex ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return 0;
	}
	$img = imagecreatetruecolor( 800, 800 );
	list( $r, $g, $b ) = sscanf( $hex, '#%02x%02x%02x' );
	imagefilledrectangle( $img, 0, 0, 800, 800, imagecolorallocate( $img, $r, $g, $b ) );
	imagefilledellipse( $img, 400, 400, 460, 460, imagecolorallocatealpha( $img, 255, 255, 255, 100 ) );
	ndh_seed_text( $img, $label, 400, 400, 640, array( 255, 255, 255 ) );
	return ndh_seed_save_image( $img, $title, $label );
}

/* ---------------------------------------------------------------- Sản phẩm mẫu */

if ( '1' === $env( 'SEED_DEMO', '1' ) ) {
	$created = 0;

	// [SKU, tên, nhãn ảnh, số module, đen?, giá/m, phí cắt, phí khoan, cỡ khoan, phí taro, cỡ taro, hệ, rãnh, lỗ tâm, kg/m, nổi bật]
	$profiles = array(
		array( 'NDH-2020-B', 'Nhôm định hình 2020 bạc', '2020', 1, false, 45000, 5000, 4000, '5, 7', 5000, 'M5', '20 x 20 mm', '6 mm', 'Ø4,2 (taro M5)', '0,50', true ),
		array( 'NDH-2020-D', 'Nhôm định hình 2020 đen', '2020', 1, true, 55000, 5000, 4000, '5, 7', 5000, 'M5', '20 x 20 mm', '6 mm', 'Ø4,2 (taro M5)', '0,50', false ),
		array( 'NDH-3030-B', 'Nhôm định hình 3030 bạc', '3030', 1, false, 75000, 6000, 5000, '6.8, 9', 7000, 'M8', '30 x 30 mm', '8 mm', 'Ø6,8 (taro M8)', '0,85', true ),
		array( 'NDH-4040-B', 'Nhôm định hình 4040 bạc', '4040', 1, false, 120000, 8000, 5000, '7, 9', 8000, 'M8, M10', '40 x 40 mm', '8 mm', 'Ø8,5 (taro M10)', '1,45', true ),
		array( 'NDH-4040-D', 'Nhôm định hình 4040 đen', '4040', 1, true, 140000, 8000, 5000, '7, 9', 8000, 'M8, M10', '40 x 40 mm', '8 mm', 'Ø8,5 (taro M10)', '1,45', false ),
		array( 'NDH-4080-B', 'Nhôm định hình 4080 bạc', '4080', 2, false, 220000, 10000, 5000, '7, 9', 8000, 'M8, M10', '40 x 80 mm', '8 mm', '2 x Ø8,5 (taro M10)', '2,60', true ),
		array( 'NDH-4545-B', 'Nhôm định hình 4545 bạc', '4545', 1, false, 160000, 8000, 5000, '7, 9', 8000, 'M10, M12', '45 x 45 mm', '10 mm', 'Ø10,2 (taro M12)', '1,90', false ),
	);
	foreach ( $profiles as $i => $p ) {
		if ( wc_get_product_id_by_sku( $p[0] ) ) {
			continue;
		}
		$product = new WC_Product_Simple();
		$product->set_name( $p[1] );
		$product->set_sku( $p[0] );
		$product->set_regular_price( (string) $p[5] );
		$product->set_menu_order( $i );
		$product->set_featured( $p[15] );
		$product->set_category_ids( array( $cat_ids['nhom-dinh-hinh'] ) );
		$product->set_short_description( "Thanh nhôm định hình {$p[2]} hợp kim 6063-T5, anod " . ( $p[4] ? 'đen' : 'bạc' ) . '. Cắt theo chiều dài bạn nhập (mm), khoan lỗ và taro ren hai đầu theo yêu cầu.' );
		$product->set_description(
			'<p>Nhôm định hình hệ ' . esc_html( $p[11] ) . ', rãnh ' . esc_html( $p[12] ) . ', dùng dựng khung bàn thao tác, tủ điện, vỏ máy, giá kệ, cơ cấu tự động hoá.</p>' .
			'<h3>Gia công</h3><ul><li>Cắt bằng máy cưa CNC, dung sai ±0,5 mm, mặt cắt vuông.</li><li>Khoan lỗ lắp lục giác / lỗ xuyên theo vị trí bạn nhập.</li><li>Taro ren lỗ tâm hai đầu để bắt bulong liên kết đầu thanh.</li></ul>' .
			'<p>Mỗi thanh được dán nhãn thông số để dễ lắp ráp.</p>'
		);
		$meta = array(
			'cut' => 'yes', 'price_m' => $p[5], 'cut_fee' => $p[6], 'min_len' => 20, 'max_len' => 6000,
			'drill_fee' => $p[7], 'drill_sizes' => $p[8], 'tap_fee' => $p[9], 'tap_sizes' => $p[10],
			'series' => $p[11], 'slot' => $p[12], 'center_hole' => $p[13], 'weight' => $p[14], 'finish' => $p[4] ? 'Anod đen' : 'Anod bạc',
		);
		foreach ( $meta as $k => $v ) {
			$product->update_meta_data( "_ndh_{$k}", (string) $v );
		}
		$img = ndh_seed_profile_image( $p[1], $p[2], $p[3], $p[4] );
		if ( $img ) {
			$product->set_image_id( $img );
		}
		$product->save();
		$created++;
	}

	// [SKU, tên, danh mục, giá, giá KM, tồn kho, màu ảnh, nhãn ảnh, mô tả, nổi bật]
	$accessories = array(
		array( 'PK-KE-2020', 'Ke góc L 2020 (kèm ốc)', 'ke-goc', 6000, 0, 500, '#0b63ce', 'KE 2020', 'Ke góc nhôm đúc cho hệ 20, kèm 2 bộ bulong M5 + ốc T.', true ),
		array( 'PK-KE-4040', 'Ke góc L 4040 (kèm ốc)', 'ke-goc', 12000, 0, 400, '#1d4ed8', 'KE 4040', 'Ke góc nhôm đúc cho hệ 40, kèm 2 bộ bulong M8 + ốc T.', true ),
		array( 'PK-KEAN-2020', 'Ke ẩn liên kết trong 2020', 'ke-goc', 15000, 12000, 200, '#1e40af', 'KE AN', 'Liên kết ẩn trong rãnh, mối nối gọn đẹp cho mặt bàn, tủ.', false ),
		array( 'PK-TNUT-M5', 'Ốc T-nut M5 rãnh 6 (gói 50)', 'oc-bulong', 45000, 0, 100, '#475569', 'T-NUT M5', 'Ốc T thả rãnh cho hệ 20, thép mạ kẽm.', true ),
		array( 'PK-TNUT-M8', 'Ốc T trượt M8 rãnh 8 (gói 50)', 'oc-bulong', 90000, 0, 100, '#334155', 'T-NUT M8', 'Ốc T trượt có bi chặn cho hệ 30/40.', true ),
		array( 'PK-BL-M5', 'Bulong lục giác chìm M5x10 (gói 100)', 'oc-bulong', 50000, 0, 100, '#1f2937', 'M5x10', 'Bulong đầu trụ lục giác chìm inox 304.', false ),
		array( 'PK-BANLE-40', 'Bản lề nhôm 4040', 'ban-le-tay-nam', 45000, 39000, 120, '#0f766e', 'BAN LE', 'Bản lề nhôm đúc cho cửa tủ khung 40, kèm ốc.', true ),
		array( 'PK-TAYNAM-120', 'Tay nắm nhôm 120 mm', 'ban-le-tay-nam', 35000, 0, 150, '#0d9488', 'TAY NAM', 'Tay nắm cửa tủ / vỏ máy, khoảng cách lỗ 120 mm.', false ),
		array( 'PK-CHAN-M8', 'Chân tăng chỉnh M8 đế Ø40', 'chan-banh-xe', 25000, 0, 300, '#b45309', 'CHAN M8', 'Chân tăng chỉnh cao độ, bắt vào lỗ tâm đã taro M8.', true ),
		array( 'PK-BANHXE-25', 'Bánh xe 2,5" có khoá, ren M8', 'chan-banh-xe', 45000, 0, 200, '#c2410c', 'BANH XE', 'Bánh xe PU chịu tải 50 kg/bánh, có khoá.', false ),
		array( 'PK-NAP-2020', 'Nắp bịt đầu 2020 (gói 10)', 'nap-bit-che-ranh', 20000, 0, 200, '#7c3aed', 'NAP 2020', 'Nắp nhựa đen bịt đầu thanh 2020.', false ),
		array( 'PK-CHERANH-8', 'Nẹp che rãnh 8 (thanh 1 m)', 'nap-bit-che-ranh', 8000, 0, 500, '#6d28d9', 'CHE RANH', 'Nẹp nhựa che rãnh hệ 30/40, chống bụi, tăng thẩm mỹ.', false ),
	);
	foreach ( $accessories as $a ) {
		if ( wc_get_product_id_by_sku( $a[0] ) ) {
			continue;
		}
		$product = new WC_Product_Simple();
		$product->set_name( $a[1] );
		$product->set_sku( $a[0] );
		$product->set_regular_price( (string) $a[3] );
		if ( $a[4] ) {
			$product->set_sale_price( (string) $a[4] );
		}
		$product->set_manage_stock( true );
		$product->set_stock_quantity( $a[5] );
		$product->set_short_description( $a[8] );
		$product->set_category_ids( array( $cat_ids['phu-kien'], $cat_ids[ $a[2] ] ) );
		$product->set_featured( $a[9] );
		$img = ndh_seed_tile_image( $a[1], $a[7], $a[6] );
		if ( $img ) {
			$product->set_image_id( $img );
		}
		$product->save();
		$created++;
	}
	WP_CLI::log( "Đã tạo {$created} sản phẩm mẫu." );
}

/* ---------------------------------------------------------------- Trang */

foreach ( array( 'sample-page' => 'page', 'hello-world' => 'post' ) as $slug => $type ) {
	$p = get_page_by_path( $slug, OBJECT, $type );
	if ( $p ) {
		wp_delete_post( $p->ID, true );
	}
}

$phone   = esc_html( $env( 'STORE_PHONE' ) );
$address = esc_html( trim( $env( 'STORE_ADDRESS' ) . ', ' . $env( 'STORE_CITY' ), ', ' ) );
$pages   = array(
	'gioi-thieu'         => array( 'Giới thiệu', '<p>Chúng tôi chuyên cung cấp nhôm định hình công nghiệp và phụ kiện lắp ráp, nhận cắt – khoan – taro theo bản vẽ cho khung bàn thao tác, tủ điện, vỏ máy, giá kệ và cơ cấu tự động hoá.</p><p>Xưởng trang bị máy cưa CNC, máy khoan và taro chuyên dụng, cam kết đúng kích thước – đúng hẹn.</p>' ),
	'huong-dan-dat-hang' => array( 'Hướng dẫn đặt hàng gia công', '<h2>1. Chiều dài</h2><p>Nhập chiều dài thực tế của thanh (mm). Dung sai cắt ±0,5 mm.</p><h2>2. Đầu A, đầu B và các mặt</h2><p><strong>Đầu A</strong> là đầu bạn dùng làm gốc đo. Mọi vị trí lỗ khoan được tính <strong>từ đầu A tới tâm lỗ</strong>. Nhìn từ đầu A, các mặt được đánh số 1–4 theo chiều kim đồng hồ, bắt đầu từ mặt trên.</p><h2>3. Khoan lỗ</h2><ul><li>Lỗ Ø5 / Ø7 (hệ 20), Ø7 / Ø9 (hệ 40): lỗ đưa lục giác vào siết bulong liên kết đầu thanh.</li><li>Tâm lỗ phải cách mỗi đầu thanh ít nhất 5 mm.</li><li>Dùng nút <em>Chia đều…</em> để chia nhanh nhiều lỗ cách đều.</li></ul><h2>4. Taro ren</h2><p>Taro ren lỗ tâm ở đầu A và/hoặc đầu B để bắt bulong liên kết, chân tăng chỉnh, bánh xe.</p><h2>5. Giá</h2><p>Giá 1 thanh = đơn giá/mét × chiều dài + phí cắt + phí khoan × số lỗ + phí taro × số đầu (làm tròn lên 100đ). Giá hiện ngay khi bạn nhập.</p><h2>Có bản vẽ?</h2><p>Gửi bản vẽ qua Zalo ' . $phone . ' để được bóc tách vật tư và báo giá trọn bộ.</p>' ),
	'lien-he'            => array( 'Liên hệ', '<p><strong>Hotline / Zalo:</strong> ' . $phone . '</p><p><strong>Xưởng:</strong> ' . $address . '</p><p>Giờ làm việc: 7:30 – 17:30, thứ 2 – thứ 7.</p><p>Gửi bản vẽ (ảnh, PDF, DWG, STEP) qua Zalo để được báo giá trong ngày.</p>' ),
	'chinh-sach-doi-tra' => array( 'Chính sách đổi trả & bảo hành', '<ul><li>Hàng gia công theo yêu cầu (cắt, khoan, taro) không áp dụng đổi trả, trừ khi sai thông số so với đơn hàng — xưởng làm lại miễn phí.</li><li>Phụ kiện còn nguyên bao bì được đổi trong 7 ngày.</li><li>Kiểm tra hàng khi nhận; báo lỗi trong 48 giờ kèm hình ảnh.</li></ul>' ),
	'chinh-sach-van-chuyen' => array( 'Chính sách vận chuyển', '<ul><li>Nhận tại xưởng: miễn phí.</li><li>Giao tận nơi: 50.000đ; miễn phí cho đơn từ 2.000.000đ.</li><li>Thanh dài trên 1,5 m giao bằng xe tải nhỏ hoặc nhà xe — chúng tôi sẽ gọi báo phí nếu ngoài khu vực.</li><li>Thời gian gia công 24–48 giờ trước khi giao.</li></ul>' ),
);
$page_ids = array();
foreach ( $pages as $slug => $info ) {
	$existing          = get_page_by_path( $slug );
	$page_ids[ $slug ] = $existing ? $existing->ID : wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $info[0],
		'post_content' => $info[1],
	) );
}
update_option( 'woocommerce_terms_page_id', $page_ids['chinh-sach-doi-tra'] );

/* ---------------------------------------------------------------- Menu */

$add_menu = static function ( $name, $location, $items ) {
	if ( wp_get_nav_menu_object( $name ) ) {
		return;
	}
	$menu_id = wp_create_nav_menu( $name );
	foreach ( $items as $item ) {
		wp_update_nav_menu_item( $menu_id, 0, array_merge( array( 'menu-item-status' => 'publish' ), $item ) );
	}
	$locations              = get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
};
$cat_item  = static function ( $slug ) use ( $cat_ids ) {
	return array( 'menu-item-type' => 'taxonomy', 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $cat_ids[ $slug ] );
};
$page_item = static function ( $id ) {
	return array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $id );
};

$add_menu( 'Menu chính', 'primary', array(
	$cat_item( 'nhom-dinh-hinh' ),
	$cat_item( 'ke-goc' ),
	$cat_item( 'oc-bulong' ),
	$cat_item( 'ban-le-tay-nam' ),
	$cat_item( 'chan-banh-xe' ),
	$page_item( $page_ids['huong-dan-dat-hang'] ),
	$page_item( $page_ids['lien-he'] ),
) );
$add_menu( 'Menu chân trang', 'footer', array(
	$page_item( $page_ids['gioi-thieu'] ),
	$page_item( $page_ids['huong-dan-dat-hang'] ),
	$page_item( $page_ids['chinh-sach-doi-tra'] ),
	$page_item( $page_ids['chinh-sach-van-chuyen'] ),
	$page_item( $page_ids['lien-he'] ),
) );

WP_CLI::success( 'Đã khởi tạo dữ liệu cửa hàng.' );
