<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main-content" class="sn-site-main">
<?php while ( have_posts() ) : the_post(); ?>
	<article <?php post_class( saponine_flex_is_builder_page() ? '' : 'sn-prose' ); ?>>
	<?php if ( ! saponine_flex_is_builder_page() ) : ?>
		<h1><?php the_title(); ?></h1><p class="sn-entry-meta"><?php echo esc_html( get_the_date() ); ?></p>
		<?php the_post_thumbnail( 'large' ); ?>
	<?php endif; ?>
	<?php the_content(); wp_link_pages( array( 'before' => '<nav class="sn-page-links">', 'after' => '</nav>' ) ); ?>
	</article>
	<div class="sn-prose"><?php the_post_navigation(); if ( comments_open() || get_comments_number() ) { comments_template(); } ?></div>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
