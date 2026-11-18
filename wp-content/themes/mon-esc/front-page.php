<?php
/**
 * Trang chủ cửa hàng.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$has_wc   = class_exists( 'WooCommerce' );
$shop_url = $has_wc ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$guide    = get_page_by_path( 'huong-dan-blheli' );
?>

<section class="hero">
	<div class="container hero__inner">
		<div class="hero__text">
			<p class="eyebrow">BLHeli_S • Bluejay • BLHeli_32 • AM32</p>
			<h1>ESC tối ưu sẵn,<br>cắm là bay.</h1>
			<p class="hero__lead">Mỗi ESC được flash firmware mới nhất, chỉnh timing &amp; PWM theo setup của bạn và test motor trước khi giao.</p>
			<div class="hero__cta">
				<a class="button button--lg" href="<?php echo esc_url( $shop_url ); ?>">Mua ngay</a>
				<?php if ( $guide ) : ?>
					<a class="button button--lg button--ghost-light" href="<?php echo esc_url( get_permalink( $guide ) ); ?>">Hướng dẫn cấu hình</a>
				<?php endif; ?>
			</div>
		</div>
		<div class="hero__visual" aria-hidden="true">
			<div class="esc-board">
				<span class="esc-board__chip">MCU</span>
				<span class="esc-board__fet"></span><span class="esc-board__fet"></span><span class="esc-board__fet"></span>
				<span class="esc-board__fet"></span><span class="esc-board__fet"></span><span class="esc-board__fet"></span>
				<span class="esc-board__label">48kHz • DShot600 • Bidir</span>
			</div>
		</div>
	</div>
</section>

<section class="usp">
	<div class="container usp__grid">
		<div><strong>⚡ Flash sẵn firmware</strong><span>Bluejay / BLHeli_32 / AM32 theo yêu cầu</span></div>
		<div><strong>🚚 Giao toàn quốc</strong><span>Freeship đơn từ 1.000.000đ</span></div>
		<div><strong>🛠️ Hỗ trợ kỹ thuật</strong><span>Tư vấn setup qua Zalo miễn phí</span></div>
		<div><strong>🔁 Đổi trả 7 ngày</strong><span>Lỗi nhà sản xuất đổi mới</span></div>
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

	<section class="section">
		<div class="container">
			<div class="section__head"><h2>Sản phẩm nổi bật</h2><a href="<?php echo esc_url( $shop_url ); ?>">Xem tất cả →</a></div>
			<?php echo do_shortcode( '[products limit="8" columns="4" visibility="featured"]' ); ?>
		</div>
	</section>

	<section class="section section--alt">
		<div class="container">
			<div class="section__head"><h2>Đang giảm giá</h2></div>
			<?php echo do_shortcode( '[products limit="4" columns="4" on_sale="true"]' ); ?>
		</div>
	</section>

	<section class="section">
		<div class="container">
			<div class="section__head"><h2>Hàng mới về</h2></div>
			<?php echo do_shortcode( '[products limit="4" columns="4" orderby="date" order="DESC"]' ); ?>
		</div>
	</section>
<?php endif; ?>

<section class="section">
	<div class="container cta-box">
		<div>
			<h2>Chưa biết chọn ESC nào?</h2>
			<p>Gửi cho chúng tôi khung, motor, pin bạn đang dùng — chúng tôi gợi ý ESC và cấu hình phù hợp.</p>
		</div>
		<?php $contact = get_page_by_path( 'lien-he' ); ?>
		<a class="button button--lg" href="<?php echo esc_url( $contact ? get_permalink( $contact ) : home_url( '/' ) ); ?>">Nhận tư vấn</a>
	</div>
</section>

<?php
get_footer();
