<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php if ( ! saponine_flex_location( 'footer' ) ) : ?>
<footer class="sn-fallback-footer">
	<small>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></small>
	<p class="sn-classika-credit"><a href="https://alimoradicoach.ir/"><?php esc_html_e( 'طراحی،توسعه و پشتیبانی با هلدینگ کلاسیکا', 'saponine-flex' ); ?></a></p>
</footer>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
