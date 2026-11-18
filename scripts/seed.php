<?php
/**
 * Dữ liệu khởi tạo cho cửa hàng. Chạy bằng: wp eval-file /scripts/seed.php
 * An toàn khi chạy lại: chỉ tạo những gì chưa tồn tại.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'Cần kích hoạt WooCommerce trước.' );
}

$env = static function ( $key, $default = '' ) {
	$v = getenv( $key );
	return ( false === $v || '' === $v ) ? $default : $v;
};

/* ---------------------------------------------------------------- Thanh toán */

update_option( 'woocommerce_cod_settings', array(
	'enabled'            => 'yes',
	'title'              => 'Thanh toán khi nhận hàng (COD)',
	'description'        => 'Bạn trả tiền mặt cho nhân viên giao hàng khi nhận được sản phẩm.',
	'instructions'       => 'Vui lòng chuẩn bị đúng số tiền khi nhận hàng.',
	'enable_for_methods' => array(),
	'enable_for_virtual' => 'yes',
) );

update_option( 'woocommerce_bacs_settings', array(
	'enabled'      => 'yes',
	'title'        => 'Chuyển khoản ngân hàng',
	'description'  => 'Chuyển khoản với nội dung là mã đơn hàng. Đơn được giao sau khi chúng tôi nhận được tiền.',
	'instructions' => 'Vui lòng chuyển khoản với nội dung: mã đơn hàng + số điện thoại.',
) );
update_option( 'woocommerce_bacs_accounts', array(
	array(
		'account_name'   => $env( 'BANK_HOLDER', 'NGUYEN VAN A' ),
		'account_number' => $env( 'BANK_ACCOUNT', '0123456789' ),
		'bank_name'      => $env( 'BANK_NAME', 'Vietcombank' ),
		'sort_code'      => '',
		'iban'           => '',
		'bic'            => '',
	),
) );
WP_CLI::log( 'Đã bật COD + chuyển khoản.' );

/* ---------------------------------------------------------------- Vận chuyển */

$zone_exists = false;
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	if ( 'Việt Nam' === $z['zone_name'] ) {
		$zone_exists = true;
	}
}
if ( ! $zone_exists ) {
	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( 'Việt Nam' );
	$zone->set_zone_order( 0 );
	$zone->add_location( 'VN', 'country' );
	$zone->save();

	$flat_id = $zone->add_shipping_method( 'flat_rate' );
	update_option( "woocommerce_flat_rate_{$flat_id}_settings", array(
		'title'      => 'Giao hàng tiêu chuẩn (2–4 ngày)',
		'tax_status' => 'none',
		'cost'       => '30000',
	) );

	$free_id = $zone->add_shipping_method( 'free_shipping' );
	update_option( "woocommerce_free_shipping_{$free_id}_settings", array(
		'title'      => 'Miễn phí vận chuyển',
		'requires'   => 'min_amount',
		'min_amount' => '1000000',
	) );
	WP_CLI::log( 'Đã tạo vùng vận chuyển Việt Nam (30.000đ, free từ 1.000.000đ).' );
}

/* ---------------------------------------------------------------- Danh mục */

$categories = array(
	'esc'             => array( 'ESC', 'Bộ điều tốc chạy BLHeli_S, BLHeli_32, Bluejay, AM32.' ),
	'motor'           => array( 'Motor', 'Motor brushless cho FPV, cinewhoop, long range.' ),
	'flight-controller' => array( 'Flight Controller', 'Mạch bay Betaflight / INAV, stack FC + ESC.' ),
	'canh-quat'       => array( 'Cánh quạt', 'Cánh 2–7 inch các loại.' ),
	'pin-lipo'        => array( 'Pin LiPo', 'Pin LiPo / Li-ion 1S–6S.' ),
	'phu-kien'        => array( 'Phụ kiện', 'Dây, jack, tụ, linh kiện hàn và dụng cụ.' ),
);
$cat_ids = array();
foreach ( $categories as $slug => $info ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term ) {
		$res = wp_insert_term( $info[0], 'product_cat', array( 'slug' => $slug, 'description' => $info[1] ) );
		$cat_ids[ $slug ] = is_wp_error( $res ) ? 0 : $res['term_id'];
	} else {
		$cat_ids[ $slug ] = $term->term_id;
	}
}

/* ---------------------------------------------------------------- Ảnh mẫu */

/**
 * Tạo ảnh PNG đơn giản (nền màu + tên sản phẩm) để site mẫu không bị trống ảnh.
 */
function mon_esc_seed_image( $title, $color, $label ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$w   = 800;
	$img = imagecreatetruecolor( $w, $w );
	list( $r, $g, $b ) = sscanf( $color, '#%02x%02x%02x' );
	$bg    = imagecolorallocate( $img, $r, $g, $b );
	$dark  = imagecolorallocatealpha( $img, 0, 0, 0, 80 );
	$white = imagecolorallocate( $img, 255, 255, 255 );
	imagefilledrectangle( $img, 0, 0, $w, $w, $bg );
	// Hoạ tiết: vòng tròn + mạch.
	imagefilledellipse( $img, 400, 360, 420, 420, $dark );
	imagesetthickness( $img, 6 );
	for ( $i = 0; $i < 6; $i++ ) {
		imageline( $img, 150 + $i * 100, 620, 150 + $i * 100, 700, $white );
	}
	$scale = 6;
	$tw    = imagefontwidth( 5 ) * strlen( $label );
	$small = imagecreatetruecolor( $tw, imagefontheight( 5 ) );
	imagefill( $small, 0, 0, imagecolorallocatealpha( $small, 0, 0, 0, 127 ) );
	imagesavealpha( $small, true );
	imagealphablending( $small, false );
	imagefilledrectangle( $small, 0, 0, $tw, imagefontheight( 5 ), imagecolorallocatealpha( $small, 0, 0, 0, 127 ) );
	imagestring( $small, 5, 0, 0, $label, imagecolorallocate( $small, 255, 255, 255 ) );
	imagealphablending( $img, true );
	$dw = min( 700, $tw * $scale );
	$dh = (int) ( imagefontheight( 5 ) * $dw / $tw );
	imagecopyresampled( $img, $small, (int) ( ( $w - $dw ) / 2 ), (int) ( 360 - $dh / 2 ), 0, 0, $dw, $dh, $tw, imagefontheight( 5 ) );

	$upload = wp_upload_dir();
	$file   = trailingslashit( $upload['path'] ) . sanitize_file_name( remove_accents( $label ) ) . '-' . wp_generate_password( 4, false ) . '.png';
	imagepng( $img, $file );
	imagedestroy( $img );
	imagedestroy( $small );

	$attach_id = wp_insert_attachment( array(
		'post_title'     => $title,
		'post_mime_type' => 'image/png',
		'post_status'    => 'inherit',
	), $file );
	wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $file ) );
	return $attach_id;
}

/* ---------------------------------------------------------------- Sản phẩm mẫu */

if ( '1' === $env( 'SEED_DEMO', '1' ) ) {
	$products = array(
		array( 'ESC-BJ-45A4', 'ESC 4in1 45A Bluejay 3–6S', 'esc', 890000, 790000, 25, '#ff5a1f', 'ESC 45A',
			'ESC 4in1 30x30 chạy Bluejay 48kHz, hỗ trợ bidirectional DShot cho RPM filter.',
			array( 'firmware' => 'Bluejay', 'current' => '45A (burst 55A)', 'cells' => '3–6S', 'mcu' => 'EFM8BB21', 'size' => '30.5x30.5mm' ), true ),
		array( 'ESC-B32-55A4', 'ESC 4in1 55A BLHeli_32 6S', 'esc', 1450000, 0, 12, '#e11d48', 'ESC 55A',
			'ESC 4in1 BLHeli_32 128kHz PWM, đo dòng tích hợp, tụ low-ESR đi kèm.',
			array( 'firmware' => 'BLHeli_32', 'current' => '55A (burst 65A)', 'cells' => '3–6S', 'mcu' => 'STM32G071', 'size' => '30.5x30.5mm' ), true ),
		array( 'ESC-AM32-35A', 'ESC đơn 35A AM32 2–6S', 'esc', 320000, 0, 40, '#7c3aed', 'AM32 35A',
			'ESC đơn mã nguồn mở AM32, cập nhật firmware qua Betaflight passthrough.',
			array( 'firmware' => 'AM32', 'current' => '35A', 'cells' => '2–6S', 'mcu' => 'AT32F421', 'size' => '12x30mm' ), false ),
		array( 'ESC-BS-20A4', 'ESC 4in1 20A BLHeli_S Whoop 1–2S', 'esc', 450000, 399000, 30, '#0ea5e9', 'ESC 20A',
			'ESC 4in1 nhỏ gọn 20x20 cho whoop / toothpick, có thể flash Bluejay.',
			array( 'firmware' => 'BLHeli_S', 'current' => '20A', 'cells' => '1–2S', 'mcu' => 'EFM8BB21', 'size' => '20x20mm' ), false ),
		array( 'MOT-2207-1950', 'Motor 2207 1950KV', 'motor', 290000, 0, 60, '#16a34a', '2207 1950KV',
			'Motor freestyle 5 inch 6S, nam châm N52H, trục titan 5mm.',
			array( 'cells' => '6S', 'size' => '2207', 'current' => '42A (max)' ), true ),
		array( 'MOT-1404-3800', 'Motor 1404 3800KV', 'motor', 220000, 0, 40, '#15803d', '1404 3800KV',
			'Motor cho cinewhoop 3 inch / long range 4S.',
			array( 'cells' => '4S', 'size' => '1404' ), false ),
		array( 'FC-F722-STK', 'Stack F722 + ESC 50A BLHeli_32', 'flight-controller', 2350000, 2190000, 8, '#0f172a', 'F722 STACK',
			'Stack FC F722 + ESC 4in1 50A, OSD, Blackbox 16MB, 5 UART.',
			array( 'firmware' => 'BLHeli_32', 'current' => '50A', 'cells' => '3–6S', 'mcu' => 'STM32F722', 'size' => '30.5x30.5mm' ), true ),
		array( 'FC-F405-AIO', 'FC F405 AIO 20A (Whoop)', 'flight-controller', 990000, 0, 15, '#334155', 'F405 AIO',
			'FC tích hợp ESC 20A cho whoop 1–2S, ELRS onboard.',
			array( 'firmware' => 'Bluejay', 'current' => '20A', 'cells' => '1–2S', 'mcu' => 'STM32F405', 'size' => '25.5x25.5mm' ), false ),
		array( 'PROP-5143-3', 'Cánh 5143 3 lá (2 cặp)', 'canh-quat', 65000, 0, 200, '#f59e0b', 'PROP 5143',
			'Cánh 5 inch 3 lá PC bền, phù hợp freestyle.', array( 'size' => '5.1 inch' ), false ),
		array( 'BAT-6S-1300', 'Pin LiPo 6S 1300mAh 120C', 'pin-lipo', 750000, 690000, 20, '#2563eb', '6S 1300',
			'Pin 6S cho freestyle 5 inch, jack XT60.', array( 'cells' => '6S' ), true ),
		array( 'ACC-XT60-5', 'Jack XT60 đực/cái (5 cặp)', 'phu-kien', 45000, 0, 300, '#64748b', 'XT60',
			'Jack nguồn XT60 chính hãng, vỏ nylon chịu nhiệt.', array(), false ),
		array( 'ACC-CAP-35V', 'Tụ low-ESR 1000µF 35V', 'phu-kien', 25000, 0, 500, '#475569', 'CAP 35V',
			'Tụ lọc nhiễu cho ESC, bắt buộc với setup 6S.', array(), false ),
	);

	$created = 0;
	foreach ( $products as $p ) {
		list( $sku, $name, $cat, $price, $sale, $stock, $color, $label, $short, $specs, $featured ) = $p;
		if ( wc_get_product_id_by_sku( $sku ) ) {
			continue;
		}
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_sku( $sku );
		$product->set_regular_price( (string) $price );
		if ( $sale ) {
			$product->set_sale_price( (string) $sale );
		}
		$product->set_manage_stock( true );
		$product->set_stock_quantity( $stock );
		$product->set_short_description( $short );
		$product->set_description(
			"<p>{$short}</p>\n<h3>Trong hộp gồm</h3>\n<ul><li>1 x {$name}</li><li>Phụ kiện đi kèm theo nhà sản xuất</li></ul>\n" .
			'<p>Bảo hành 30 ngày lỗi nhà sản xuất. Hỗ trợ cấu hình / flash firmware miễn phí.</p>'
		);
		$product->set_category_ids( array( $cat_ids[ $cat ] ) );
		$product->set_featured( $featured );
		$product->set_status( 'publish' );
		foreach ( $specs as $k => $v ) {
			$product->update_meta_data( "_mon_esc_{$k}", $v );
		}
		$img = mon_esc_seed_image( $name, $color, $label );
		if ( $img ) {
			$product->set_image_id( $img );
		}
		$product->save();
		$created++;
	}
	WP_CLI::log( "Đã tạo {$created} sản phẩm mẫu." );
}

/* ---------------------------------------------------------------- Trang */

// Xoá nội dung mẫu mặc định của WordPress.
foreach ( array( 'sample-page' => 'page', 'hello-world' => 'post' ) as $slug => $type ) {
	$p = get_page_by_path( $slug, OBJECT, $type );
	if ( $p ) {
		wp_delete_post( $p->ID, true );
	}
}

$pages = array(
	'gioi-thieu'      => array( 'Giới thiệu', '<p>Mon ESC chuyên cung cấp ESC, motor và linh kiện FPV đã được kiểm tra và cấu hình tối ưu cho BLHeli / Bluejay / AM32.</p><p>Mỗi ESC xuất xưởng đều được flash firmware mới nhất, test motor và cài thông số phù hợp với setup của bạn.</p>' ),
	'huong-dan-blheli' => array( 'Hướng dẫn cấu hình BLHeli', '<h2>1. Chuẩn bị</h2><p>Cài <strong>Betaflight Configurator</strong> và <strong>ESC Configurator</strong> (esc-configurator.com). Tháo cánh quạt trước khi thao tác.</p><h2>2. Kết nối</h2><p>Cắm pin cho drone, cắm USB vào FC, mở ESC Configurator và chọn cổng COM → <em>Connect</em> → <em>Read Settings</em>.</p><h2>3. Thông số khuyến nghị</h2><ul><li><strong>Motor timing:</strong> 16–22° cho freestyle 5 inch.</li><li><strong>PWM frequency:</strong> 48kHz (Bluejay) để motor êm hơn.</li><li><strong>Bidirectional DShot:</strong> bật để dùng RPM filter trong Betaflight.</li><li><strong>Startup power:</strong> giữ mặc định trừ khi motor khó khởi động.</li></ul><h2>4. Flash firmware</h2><p>Chọn <em>Flash All</em>, chọn phiên bản firmware phù hợp với MCU của ESC (xem tab Thông số trên trang sản phẩm). Không rút pin trong lúc flash.</p><p>Cần hỗ trợ? Gọi hotline hoặc nhắn Zalo, chúng tôi hỗ trợ miễn phí cho khách đã mua hàng.</p>' ),
	'lien-he'         => array( 'Liên hệ', '<p><strong>Hotline / Zalo:</strong> ' . esc_html( $env( 'STORE_PHONE' ) ) . '</p><p><strong>Địa chỉ:</strong> ' . esc_html( $env( 'STORE_ADDRESS' ) . ', ' . $env( 'STORE_CITY' ) ) . '</p><p>Giờ làm việc: 8:30 – 21:00 hằng ngày.</p>' ),
	'chinh-sach-doi-tra' => array( 'Chính sách đổi trả & bảo hành', '<ul><li>Đổi mới trong 7 ngày nếu lỗi do nhà sản xuất.</li><li>Bảo hành 30 ngày cho ESC, FC, motor.</li><li>Không bảo hành khi cháy do chập nguồn, ngược cực, rơi vỡ.</li></ul>' ),
	'chinh-sach-van-chuyen' => array( 'Chính sách vận chuyển', '<ul><li>Phí ship đồng giá 30.000đ toàn quốc.</li><li>Miễn phí vận chuyển cho đơn từ 1.000.000đ.</li><li>Thời gian giao 2–4 ngày làm việc.</li></ul>' ),
);
$page_ids = array();
foreach ( $pages as $slug => $info ) {
	$existing = get_page_by_path( $slug );
	$page_ids[ $slug ] = $existing ? $existing->ID : wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $info[0],
		'post_content' => $info[1],
	) );
}
update_option( 'woocommerce_terms_page_id', $page_ids['chinh-sach-doi-tra'] );
update_option( 'wp_page_for_privacy_policy', $page_ids['chinh-sach-doi-tra'] );

// Dùng checkout/giỏ hàng dạng shortcode (classic) để các tuỳ biến form địa chỉ Việt Nam trong plugin có hiệu lực.
foreach ( array( 'cart' => '[woocommerce_cart]', 'checkout' => '[woocommerce_checkout]' ) as $wc_page => $shortcode ) {
	$pid = wc_get_page_id( $wc_page );
	if ( $pid > 0 && false === strpos( get_post_field( 'post_content', $pid ), $shortcode ) ) {
		wp_update_post( array( 'ID' => $pid, 'post_content' => $shortcode ) );
	}
}

/* ---------------------------------------------------------------- Menu */

$add_menu = static function ( $name, $location, $items ) {
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu ) {
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

$cat_item = static function ( $slug ) use ( $cat_ids ) {
	return array( 'menu-item-type' => 'taxonomy', 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $cat_ids[ $slug ] );
};
$page_item = static function ( $id ) {
	return array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $id );
};

$add_menu( 'Menu chính', 'primary', array(
	$page_item( wc_get_page_id( 'shop' ) ),
	$cat_item( 'esc' ),
	$cat_item( 'motor' ),
	$cat_item( 'flight-controller' ),
	$cat_item( 'pin-lipo' ),
	$page_item( $page_ids['huong-dan-blheli'] ),
	$page_item( $page_ids['lien-he'] ),
) );
$add_menu( 'Menu chân trang', 'footer', array(
	$page_item( $page_ids['gioi-thieu'] ),
	$page_item( $page_ids['chinh-sach-doi-tra'] ),
	$page_item( $page_ids['chinh-sach-van-chuyen'] ),
	$page_item( $page_ids['lien-he'] ),
) );

WP_CLI::success( 'Đã khởi tạo dữ liệu cửa hàng.' );
