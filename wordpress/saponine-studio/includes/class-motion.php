<?php
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Motion {
	private static $registered;

	public static function boot() {
		add_action( 'elementor/element/after_section_end', array( __CLASS__, 'controls' ), 10, 3 );
		add_action( 'elementor/frontend/before_render', array( __CLASS__, 'attributes' ) );
	}

	public static function controls( $element, $section_id, $args ) {
		if ( ! in_array( $section_id, array( '_section_style', 'section_advanced', 'section_layout' ), true ) || ! method_exists( $element, 'get_type' ) || ! in_array( $element->get_type(), array( 'widget', 'container', 'section', 'column' ), true ) ) {
			return;
		}
		if ( ! self::$registered ) { self::$registered = new \SplObjectStorage(); }
		if ( self::$registered->contains( $element ) ) { return; }
		self::$registered->attach( $element );
		$element->start_controls_section( 'sn_motion_section', array( 'label' => __( 'حرکت ساپونین', 'saponine-studio' ), 'tab' => \Elementor\Controls_Manager::TAB_ADVANCED ) );
		$element->add_control( 'sn_motion_effect', array(
			'label' => __( 'نوع حرکت', 'saponine-studio' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'none', 'frontend_available' => true, 'render_type' => 'template',
			'options' => array( 'none' => __( 'بدون حرکت', 'saponine-studio' ), 'reveal' => __( 'ورود آرام از پایین', 'saponine-studio' ), 'float' => __( 'شناوری عمودی', 'saponine-studio' ), 'rotate' => __( 'چرخش رفت و برگشت', 'saponine-studio' ), 'tilt' => __( 'چرخش سه‌بعدی با اشاره‌گر', 'saponine-studio' ), 'parallax' => __( 'جابجایی با اسکرول', 'saponine-studio' ) ),
		) );
		$base = array( 'frontend_available' => true, 'render_type' => 'template', 'condition' => array( 'sn_motion_effect!' => 'none' ) );
		$element->add_control( 'sn_motion_duration', array_merge( $base, array( 'label' => __( 'مدت یک حرکت (میلی‌ثانیه)', 'saponine-studio' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 1200, 'condition' => array( 'sn_motion_effect' => array( 'reveal', 'float', 'rotate' ) ), 'min' => 100, 'max' => 60000, 'step' => 100 ) ) );
		$element->add_control( 'sn_motion_delay', array_merge( $base, array( 'label' => __( 'تأخیر شروع (میلی‌ثانیه)', 'saponine-studio' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 0, 'condition' => array( 'sn_motion_effect' => array( 'reveal', 'float', 'rotate' ) ), 'min' => 0, 'max' => 10000, 'step' => 50 ) ) );
		$element->add_control( 'sn_motion_distance', array_merge( $base, array( 'label' => __( 'مقدار جابجایی (پیکسل)', 'saponine-studio' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 24, 'condition' => array( 'sn_motion_effect' => array( 'reveal', 'float', 'parallax' ) ), 'min' => -300, 'max' => 300, 'step' => 1 ) ) );
		$element->add_control( 'sn_motion_rotation', array_merge( $base, array( 'label' => __( 'زاویهٔ چرخش / شدت Tilt', 'saponine-studio' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 6, 'condition' => array( 'sn_motion_effect' => array( 'rotate', 'tilt' ) ), 'min' => 0, 'max' => 45, 'step' => 1 ) ) );
		$element->add_control( 'sn_motion_trigger', array_merge( $base, array( 'label' => __( 'شروع حرکت', 'saponine-studio' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'viewport', 'condition' => array( 'sn_motion_effect' => array( 'reveal', 'float', 'rotate' ) ), 'options' => array( 'viewport' => __( 'ورود به محدودهٔ دید', 'saponine-studio' ), 'load' => __( 'بارگذاری صفحه', 'saponine-studio' ), 'hover' => __( 'اشاره یا فوکوس', 'saponine-studio' ) ) ) ) );
		$element->add_control( 'sn_motion_easing', array_merge( $base, array( 'label' => __( 'ریتم حرکت', 'saponine-studio' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'ease-in-out', 'condition' => array( 'sn_motion_effect' => array( 'reveal', 'float', 'rotate' ) ), 'options' => array( 'ease' => __( 'ملایم', 'saponine-studio' ), 'ease-in-out' => __( 'شروع و پایان نرم', 'saponine-studio' ), 'linear' => __( 'یکنواخت', 'saponine-studio' ), 'cubic-bezier(.22,.8,.18,1)' => __( 'نرم و سریع', 'saponine-studio' ) ) ) ) );
		$element->add_control( 'sn_motion_repeat', array_merge( $base, array( 'label' => __( 'تکرار ورود در هر بار دیده شدن', 'saponine-studio' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => '', 'condition' => array( 'sn_motion_effect' => 'reveal' ), 'description' => __( 'شناوری و چرخش، هنگام فعال بودن به‌صورت پیوسته تکرار می‌شوند.', 'saponine-studio' ) ) ) );
		$element->add_control( 'sn_motion_mobile', array_merge( $base, array( 'label' => __( 'فعال در موبایل', 'saponine-studio' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) ) );
		$element->add_control( 'sn_motion_note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'حرکت در حالت کاهش حرکت سیستم‌عامل متوقف می‌شود. Tilt با ماوس کار می‌کند؛ در لمس، تصویر ثابت می‌ماند. برای بخش بازشوندهٔ محصول، تنظیمات اختصاصی همان ابزار را به‌کار ببرید.', 'saponine-studio' ), 'content_classes' => 'elementor-panel-alert elementor-panel-alert-info' ) );
		$element->end_controls_section();
	}

	public static function attributes( $element ) {
		$s = $element->get_settings_for_display();
		$effect = isset( $s['sn_motion_effect'] ) ? $s['sn_motion_effect'] : 'none';
		if ( ! in_array( $effect, array( 'reveal', 'float', 'rotate', 'tilt', 'parallax' ), true ) ) { return; }
		$data = array(
			'effect' => $effect,
			'duration' => self::number( $s, 'duration', 1200, 100, 60000 ),
			'delay' => self::number( $s, 'delay', 0, 0, 10000 ),
			'distance' => self::number( $s, 'distance', 24, -300, 300 ),
			'rotation' => self::number( $s, 'rotation', 6, 0, 45 ),
			'trigger' => isset( $s['sn_motion_trigger'] ) && in_array( $s['sn_motion_trigger'], array( 'viewport', 'load', 'hover' ), true ) ? $s['sn_motion_trigger'] : 'viewport',
			'easing' => isset( $s['sn_motion_easing'] ) && in_array( $s['sn_motion_easing'], array( 'ease', 'ease-in-out', 'linear', 'cubic-bezier(.22,.8,.18,1)' ), true ) ? $s['sn_motion_easing'] : 'ease-in-out',
			'repeat' => isset( $s['sn_motion_repeat'] ) && 'yes' === $s['sn_motion_repeat'],
			'mobile' => ! isset( $s['sn_motion_mobile'] ) || 'yes' === $s['sn_motion_mobile'],
		);
		$element->add_render_attribute( '_wrapper', 'data-sn-motion', wp_json_encode( $data ) );
	}

	private static function number( $settings, $key, $default, $min, $max ) {
		$value = isset( $settings[ 'sn_motion_' . $key ] ) && is_numeric( $settings[ 'sn_motion_' . $key ] ) ? (float) $settings[ 'sn_motion_' . $key ] : $default;
		return max( $min, min( $max, $value ) );
	}
}
