<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Bỏ qua đến nội dung</a>

<div class="topbar">
	<div class="container topbar__inner">
		<span>📏 Cắt – khoan – taro nhôm định hình theo yêu cầu • Giao 24–48h</span>
		<?php if ( ndh_phone() ) : ?>
			<a href="tel:<?php echo esc_attr( ndh_phone() ); ?>">Hotline: <?php echo esc_html( ndh_phone() ); ?></a>
		<?php endif; ?>
	</div>
</div>

<header class="site-header">
	<div class="container site-header__inner">
		<button class="nav-toggle" aria-controls="primary-nav" aria-expanded="false">
			<span class="screen-reader-text">Mở menu</span><span class="nav-toggle__bar"></span>
		</button>

		<div class="site-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<span class="logo-mark"><?php echo ndh_profile_svg(); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php bloginfo( 'name' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( function_exists( 'get_product_search_form' ) ) : ?>
			<div class="site-search"><?php get_product_search_form(); ?></div>
		<?php endif; ?>

		<div class="site-actions">
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="action-link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" title="Tài khoản">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
					<span class="screen-reader-text">Tài khoản</span>
				</a>
				<a class="action-link cart-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>" title="Giỏ hàng">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/><circle cx="10" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/></svg>
					<?php ndh_cart_count(); ?>
					<span class="screen-reader-text">Giỏ hàng</span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<nav id="primary-nav" class="primary-nav" aria-label="Menu chính">
		<div class="container">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'menu',
				'fallback_cb'    => false,
			) );
			?>
		</div>
	</nav>
</header>

<main id="main" class="site-main">
