<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="container content-area">
	<?php if ( is_home() || is_archive() || is_search() ) : ?>
		<header class="page-header">
			<h1 class="page-title">
				<?php
				if ( is_search() ) {
					printf( 'Kết quả tìm kiếm: “%s”', esc_html( get_search_query() ) );
				} elseif ( is_archive() ) {
					echo wp_kses_post( get_the_archive_title() );
				} else {
					echo 'Tin tức';
				}
				?>
			</h1>
		</header>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( is_singular() ? 'entry' : 'entry entry--summary' ); ?>>
				<?php if ( is_singular() ) : ?>
					<h1 class="entry-title"><?php the_title(); ?></h1>
					<?php if ( 'post' === get_post_type() ) : ?>
						<p class="entry-meta"><?php echo esc_html( get_the_date() ); ?></p>
					<?php endif; ?>
					<?php the_post_thumbnail( 'large' ); ?>
					<div class="entry-content"><?php the_content(); ?></div>
					<?php
					wp_link_pages();
					if ( comments_open() || get_comments_number() ) {
						comments_template();
					}
					?>
				<?php else : ?>
					<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<p class="entry-meta"><?php echo esc_html( get_the_date() ); ?></p>
					<div class="entry-content"><?php the_excerpt(); ?></div>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
		<?php the_posts_pagination( array( 'prev_text' => '‹', 'next_text' => '›' ) ); ?>
	<?php else : ?>
		<p>Không tìm thấy nội dung phù hợp.</p>
		<?php get_search_form(); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
