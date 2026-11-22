<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function saponine_flex_setup() {
	load_theme_textdomain( 'saponine-flex', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	register_nav_menus( array( 'primary' => __( 'فهرست اصلی', 'saponine-flex' ) ) );
	if ( ! isset( $GLOBALS['content_width'] ) ) { $GLOBALS['content_width'] = 1200; }
}
add_action( 'after_setup_theme', 'saponine_flex_setup' );

function saponine_flex_styles() {
	wp_enqueue_style( 'saponine-flex', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
	if ( is_rtl() ) {
		wp_enqueue_style( 'saponine-flex-rtl', get_template_directory_uri() . '/rtl.css', array( 'saponine-flex' ), wp_get_theme()->get( 'Version' ) );
	}
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) { wp_enqueue_script( 'comment-reply' ); }
}
add_action( 'wp_enqueue_scripts', 'saponine_flex_styles', 5 );

function saponine_flex_home_url() {
	if ( class_exists( '\Saponine\Studio\Multilingual' ) ) { return \Saponine\Studio\Multilingual::home_url(); }
	return function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
}

function saponine_flex_language() {
	if ( class_exists( '\Saponine\Studio\Multilingual' ) ) { return \Saponine\Studio\Multilingual::current_language(); }
	return function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : '';
}

function saponine_flex_location( $location ) {
	return class_exists( '\Saponine\Studio\Plugin' ) && \Saponine\Studio\Plugin::render_location( $location );
}

function saponine_flex_is_builder_page( $id = 0 ) {
	$id = $id ? absint( $id ) : get_the_ID();
	return 'builder' === get_post_meta( $id, '_elementor_edit_mode', true ) && class_exists( '\Elementor\Plugin' );
}

function saponine_flex_admin_notice() {
	if ( current_user_can( 'activate_plugins' ) && ! class_exists( '\Saponine\Studio\Plugin' ) ) {
		echo '<div class="notice notice-info"><p>' . esc_html__( 'برای وارد کردن طرح و ویرایش سربرگ، پابرگ و موشن‌ها، افزونهٔ Saponine Studio و Elementor رایگان را فعال کنید.', 'saponine-flex' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'saponine_flex_admin_notice' );
