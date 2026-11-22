<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main-content" class="sn-site-main">
<?php while ( have_posts() ) : the_post(); ?>
	<?php if ( saponine_flex_is_builder_page() ) : ?>
		<?php the_content(); ?>
	<?php else : ?>
		<article <?php post_class( 'sn-prose' ); ?>>
			<h1><?php the_title(); ?></h1>
			<?php the_content(); wp_link_pages( array( 'before' => '<nav class="sn-page-links">', 'after' => '</nav>' ) ); ?>
		</article>
	<?php endif; ?>
	<?php if ( comments_open() || get_comments_number() ) : ?><div class="sn-prose"><?php comments_template(); ?></div><?php endif; ?>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
