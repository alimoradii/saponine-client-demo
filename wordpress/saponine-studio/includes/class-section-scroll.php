<?php
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Progressive full-section navigation for existing and freshly imported pages. */
final class Section_Scroll {

	public static function boot() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 30 );
	}

	public static function enqueue() {
		if ( is_admin() || Portal::view() || ! Plugin::elementor_ready() ) { return; }
		$settings = Plugin::settings();
		if ( isset( $settings['section_scroll_enabled'] ) && 'yes' !== $settings['section_scroll_enabled'] ) { return; }
		$config = array(
			'enabled'   => true,
			'duration'  => isset( $settings['section_scroll_duration'] ) ? max( 0, min( 1500, absint( $settings['section_scroll_duration'] ) ) ) : 650,
			'threshold' => isset( $settings['section_scroll_threshold'] ) ? max( 8, min( 160, absint( $settings['section_scroll_threshold'] ) ) ) : 36,
			'mobile'    => isset( $settings['section_scroll_mobile'] ) && 'sections' === $settings['section_scroll_mobile'] ? 'sections' : 'native',
		);
		wp_enqueue_style( 'saponine-section-scroll', SAPONINE_STUDIO_URL . 'assets/css/section-scroll.css', array( 'saponine-studio-base' ), SAPONINE_STUDIO_VERSION );
		wp_enqueue_script( 'saponine-section-scroll', SAPONINE_STUDIO_URL . 'assets/js/section-scroll.js', array(), SAPONINE_STUDIO_VERSION, true );
		wp_add_inline_script( 'saponine-section-scroll', 'window.SaponineSectionScrollConfig = ' . wp_json_encode( $config ) . ';', 'before' );
	}
}
