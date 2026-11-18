<?php
/**
 * Trang chủ cửa hàng.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$has_wc   = class_exists( 'WooCommerce' );
$shop_url = $has_wc ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$guide    = get_page_by_path( 'huong-dan-dat-hang' );
$profiles = get_term_by( 'slug', 'nhom-dinh-hinh', 'product_cat' );
?>

<section class="hero">
	<div class="container hero__inner">
		<div class="hero__text">
			<p class="eyebrow">Hệ 20 • 30 • 40 • 45 — cắt theo từng mm</p>
			<h1>Nhôm định hình<br>cắt, khoan, taro<br>theo bản vẽ của bạn.</h1>
			<p class="hero__lead">Nhập chiều dài, chọn vị trí lỗ khoan và ren taro ngay trên website — giá hiện tức thì. Đủ phụ kiện để dựng khung bàn, tủ, vỏ máy và cơ cấu cơ khí.</p>
			<div class="hero__cta">
				<a class="button button--lg" href="<?php echo esc_url( $profiles ? get_term_link( $profiles ) : $shop_url ); ?>">Đặt cắt nhôm</a>
				<?php if ( $guide ) : ?>
					<a class="button button--lg button--ghost-light" href="<?php echo esc_url( get_permalink( $guide ) ); ?>">Hướng dẫn đặt hàng</a>
				<?php endif; ?>
			</div>
		</div>
		<div class="hero__visual">
			<svg width="0" height="0" style="position:absolute" aria-hidden="true">
				<defs>
					<linearGradient id="ndh-alu" x1="0" y1="0" x2="1" y2="1">
						<stop offset="0" stop-color="#f1f5f9"/><stop offset=".45" stop-color="#94a3b8"/><stop offset=".55" stop-color="#cbd5e1"/><stop offset="1" stop-color="#64748b"/>
					</linearGradient>
				</defs>
			</svg>
			<?php echo ndh_profile_svg( 'hero-profile' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="hero__badge">40×40 • rãnh 8 • lỗ tâm M8</span>
		</div>
	</div>
</section>

<section class="usp">
	<div class="container usp__grid">
		<div><strong>📏 Cắt theo mm</strong><span>Dung sai ±0,5 mm, mặt cắt vuông</span></div>
		<div><strong>🔩 Khoan &amp; taro</strong><span>Khoan lỗ theo vị trí, taro ren hai đầu</span></div>
		<div><strong>🚚 Giao 24–48h</strong><span>Đóng gói chống trầy, giao toàn quốc</span></div>
		<div><strong>📐 Tư vấn bản vẽ</strong><span>Gửi bản vẽ qua Zalo, báo giá trọn bộ</span></div>
	</div>
</section>

<?php if ( $has_wc ) : ?>
	<?php
	$cats = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
	) );
	?>
	<?php if ( $cats && ! is_wp_error( $cats ) ) : ?>
		<section class="section">
			<div class="container">
				<div class="section__head"><h2>Danh mục</h2></div>
				<div class="cat-grid">
					<?php foreach ( $cats as $cat ) : ?>
						<a class="cat-card" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
							<span class="cat-card__name"><?php echo esc_html( $cat->name ); ?></span>
							<span class="cat-card__count"><?php echo (int) $cat->count; ?> sản phẩm</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $profiles ) : ?>
		<section class="section">
			<div class="container">
				<div class="section__head"><h2>Nhôm định hình cắt theo yêu cầu</h2><a href="<?php echo esc_url( get_term_link( $profiles ) ); ?>">Xem tất cả →</a></div>
				<?php echo do_shortcode( '[products limit="8" columns="4" category="nhom-dinh-hinh" orderby="menu_order"]' ); ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="section section--alt">
		<div class="container">
			<div class="section__head"><h2>Phụ kiện bán chạy</h2><a href="<?php echo esc_url( $shop_url ); ?>">Xem tất cả →</a></div>
			<?php echo do_shortcode( '[products limit="8" columns="4" visibility="featured" category="phu-kien"]' ); ?>
		</div>
	</section>

	<section class="section">
		<div class="container steps">
			<div class="section__head"><h2>Đặt hàng gia công trong 3 bước</h2></div>
			<ol class="steps__list">
				<li><strong>Chọn thanh nhôm</strong><span>Theo hệ (20/30/40/45) và màu bạc hoặc đen.</span></li>
				<li><strong>Nhập kích thước</strong><span>Chiều dài (mm), vị trí lỗ khoan, ren taro hai đầu — xem sơ đồ và giá ngay.</span></li>
				<li><strong>Thêm phụ kiện &amp; đặt hàng</strong><span>Ke góc, ốc T, nắp bịt, chân tăng chỉnh… Thanh toán COD hoặc chuyển khoản.</span></li>
			</ol>
		</div>
	</section>
<?php endif; ?>

<section class="section">
	<div class="container cta-box">
		<div>
			<h2>Có bản vẽ khung cần làm trọn bộ?</h2>
			<p>Gửi bản vẽ (ảnh chụp, PDF, DWG, STEP) — chúng tôi bóc tách vật tư, báo giá và cắt sẵn đánh số từng thanh.</p>
		</div>
		<?php $contact = get_page_by_path( 'lien-he' ); ?>
		<a class="button button--lg" href="<?php echo esc_url( $contact ? get_permalink( $contact ) : home_url( '/' ) ); ?>">Gửi bản vẽ báo giá</a>
	</div>
</section>

<?php
get_footer();
