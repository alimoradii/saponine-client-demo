<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main-content" class="sn-site-main sn-prose">
	<?php if ( is_home() && ! is_front_page() ) : ?><h1><?php single_post_title(); ?></h1><?php elseif ( is_archive() ) : ?><h1><?php the_archive_title(); ?></h1><?php the_archive_description(); ?><?php endif; ?>
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'sn-entry' ); ?>>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<p class="sn-entry-meta"><?php echo esc_html( get_the_date() ); ?></p>
				<?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large' ); ?></a><?php endif; ?>
				<?php the_excerpt(); ?>
			</article>
		<?php endwhile; ?>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<div class="sn-empty"><h1><?php esc_html_e( 'هنوز محتوایی منتشر نشده است.', 'saponine-flex' ); ?></h1></div>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
