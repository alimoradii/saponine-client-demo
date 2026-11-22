<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main-content" class="sn-site-main sn-prose">
	<h1><?php printf( esc_html__( 'جستجو: %s', 'saponine-flex' ), esc_html( get_search_query() ) ); ?></h1>
	<?php get_search_form(); ?>
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'sn-entry' ); ?>><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></article>
	<?php endwhile; the_posts_pagination(); else : ?>
		<p><?php esc_html_e( 'موردی پیدا نشد. عبارت دیگری را امتحان کنید.', 'saponine-flex' ); ?></p>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
