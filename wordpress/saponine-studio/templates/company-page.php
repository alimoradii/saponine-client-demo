<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main-content" class="sn-company-page" tabindex="-1">
	<?php \Saponine\Studio\Portal::render(); ?>
</main>
<?php get_footer( 'portal' ); ?>
