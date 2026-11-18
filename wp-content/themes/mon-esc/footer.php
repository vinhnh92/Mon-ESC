<?php defined( 'ABSPATH' ) || exit; ?>
</main>

<footer class="site-footer">
	<div class="container footer-grid">
		<div>
			<p class="footer-brand"><span class="logo-mark">⚡</span><?php bloginfo( 'name' ); ?></p>
			<p><?php bloginfo( 'description' ); ?></p>
		</div>
		<div>
			<h3>Thông tin</h3>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'footer',
				'container'      => false,
				'menu_class'     => 'footer-menu',
				'fallback_cb'    => false,
				'depth'          => 1,
			) );
			?>
		</div>
		<div>
			<h3>Liên hệ</h3>
			<ul class="footer-menu">
				<?php if ( mon_esc_phone() ) : ?>
					<li><a href="tel:<?php echo esc_attr( mon_esc_phone() ); ?>">📞 <?php echo esc_html( mon_esc_phone() ); ?></a></li>
				<?php endif; ?>
				<?php if ( get_option( 'woocommerce_store_address' ) ) : ?>
					<li>📍 <?php echo esc_html( trim( get_option( 'woocommerce_store_address' ) . ', ' . get_option( 'woocommerce_store_city' ), ', ' ) ); ?></li>
				<?php endif; ?>
				<li>🕘 8:30 – 21:00 hằng ngày</li>
			</ul>
		</div>
		<div>
			<h3>Thanh toán</h3>
			<p>COD • Chuyển khoản ngân hàng</p>
		</div>
	</div>
	<div class="footer-bottom">
		<div class="container">© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. Bảo lưu mọi quyền.</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
