<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main-content" class="sn-site-main sn-prose sn-empty">
	<h1><?php esc_html_e( 'این صفحه پیدا نشد.', 'saponine-flex' ); ?></h1>
	<p><a href="<?php echo esc_url( saponine_flex_home_url() ); ?>"><?php esc_html_e( 'بازگشت به صفحهٔ اصلی', 'saponine-flex' ); ?></a></p>
	<?php get_search_form(); ?>
</main>
<?php get_footer(); ?>
