<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#main-content"><?php esc_html_e( 'رفتن به محتوا', 'saponine-flex' ); ?></a>
<?php if ( ! saponine_flex_location( 'header' ) ) : ?>
<header class="sn-fallback-header">
	<div class="sn-site-brand"><?php if ( has_custom_logo() ) { the_custom_logo(); } else { ?><a href="<?php echo esc_url( saponine_flex_home_url() ); ?>"><?php bloginfo( 'name' ); ?></a><?php } ?></div>
	<?php if ( has_nav_menu( 'primary' ) ) { wp_nav_menu( array( 'theme_location' => 'primary', 'container' => 'nav', 'container_class' => 'sn-fallback-nav', 'container_aria_label' => __( 'فهرست اصلی', 'saponine-flex' ), 'fallback_cb' => false ) ); } ?>
</header>
<?php endif; ?>
