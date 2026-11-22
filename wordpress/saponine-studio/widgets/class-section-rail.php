<?php
/** Editable section rail. */
namespace Saponine\Studio\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Section_Rail extends Widget_Base {
	public function get_name() { return 'saponine-section-rail'; }
	public function get_title() { return esc_html__( 'راهنمای کناری بخش‌ها', 'saponine-studio' ); }
	public function get_icon() { return 'eicon-navigation-vertical'; }
	public function get_categories() { return array( 'saponine-studio' ); }
	public function get_keywords() { return array( 'navigation', 'rail', 'sections', 'بخش' ); }
	public function get_style_depends() { return array( 'saponine-support-widgets' ); }
	public function get_script_depends() { return array( 'saponine-support-widgets' ); }

	protected function register_controls() {
		$this->start_controls_section( 'rail_content', array( 'label' => 'بخش‌ها' ) );
		$r = new Repeater();
		$r->add_control( 'label', array( 'label' => 'نام بخش', 'type' => Controls_Manager::TEXT, 'default' => 'بخش جدید', 'label_block' => true ) );
		$r->add_control( 'anchor', array( 'label' => 'شناسه CSS بخش', 'type' => Controls_Manager::TEXT, 'default' => 'sec-0', 'description' => 'شناسه بخش را با حروف لاتین و بدون # وارد کنید؛ همان شناسه CSS در زبانه پیشرفته کانتینر.' ) );
		$this->add_control( 'items', array( 'label' => 'آیتم‌ها؛ برای مرتب‌سازی بکشید', 'type' => Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'title_field' => '{{{ label }}}', 'prevent_empty' => false, 'default' => array(
			array( 'label' => 'SLIDER', 'anchor' => 'sec-0' ), array( 'label' => 'Features', 'anchor' => 'sec-1' ), array( 'label' => 'Brands', 'anchor' => 'sec-2' ), array( 'label' => 'About us', 'anchor' => 'sec-3' ), array( 'label' => 'Products', 'anchor' => 'sec-4' ), array( 'label' => 'Pharmacies', 'anchor' => 'sec-5' ), array( 'label' => 'Contact', 'anchor' => 'sec-6' ),
		) ) );
		$this->add_control( 'rail_label', array( 'label' => 'عنوان بالای راهنما', 'type' => Controls_Manager::TEXT, 'default' => 'SLIDER' ) );
		$this->add_control( 'active_label', array( 'label' => 'عنوان همراه بخش فعال تغییر کند', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'placement', array( 'label' => 'جای نمایش', 'type' => Controls_Manager::SELECT, 'default' => 'fixed', 'options' => array( 'fixed' => 'ثابت کنار صفحه', 'inline' => 'داخل چیدمان صفحه' ) ) );
		$this->add_control( 'position', array( 'label' => 'سمت صفحه', 'type' => Controls_Manager::SELECT, 'default' => 'end', 'options' => array( 'start' => 'آغاز صفحه مطابق زبان', 'end' => 'انتهای صفحه مطابق زبان', 'left' => 'چپ', 'right' => 'راست' ), 'condition' => array( 'placement' => 'fixed' ) ) );
		$this->add_control( 'hide_mobile', array( 'label' => 'پنهان در موبایل', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->end_controls_section();
		$this->start_controls_section( 'rail_style', array( 'label' => 'ظاهر راهنما', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'line_color', array( 'label' => 'رنگ خط‌ها', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sp-rail' => '--sp-rail-line: {{VALUE}};' ) ) );
		$this->add_control( 'active_color', array( 'label' => 'رنگ بخش فعال', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sp-rail' => '--sp-rail-active: {{VALUE}};' ) ) );
		$this->add_control( 'label_color', array( 'label' => 'رنگ عنوان', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sp-rail__label' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'label_typography', 'selector' => '{{WRAPPER}} .sp-rail__label' ) );
		$this->add_responsive_control( 'edge_offset', array( 'label' => 'فاصله از لبه', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 200 ) ), 'default' => array( 'size' => 14, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sp-rail' => '--sp-rail-edge: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'gap', array( 'label' => 'فاصله خط‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'selectors' => array( '{{WRAPPER}} .sp-rail' => '--sp-rail-gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'line_height', array( 'label' => 'ارتفاع خط معمولی (پیکسل)', 'type' => Controls_Manager::NUMBER, 'min' => 6, 'max' => 100, 'default' => 20, 'selectors' => array( '{{WRAPPER}} .sp-rail' => '--sp-rail-height: {{VALUE}}px;' ) ) );
		$this->add_control( 'active_height', array( 'label' => 'ارتفاع خط فعال (پیکسل)', 'type' => Controls_Manager::NUMBER, 'min' => 6, 'max' => 160, 'default' => 48, 'selectors' => array( '{{WRAPPER}} .sp-rail' => '--sp-rail-active-height: {{VALUE}}px;' ) ) );
		$this->add_control( 'line_width', array( 'label' => 'ضخامت خط (پیکسل)', 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 12, 'default' => 1, 'selectors' => array( '{{WRAPPER}} .sp-rail' => '--sp-rail-width: {{VALUE}}px;' ) ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
		if ( ! $items ) { return; }
		$fixed = 'fixed' === $s['placement'];
		$position = in_array( $s['position'] ?? '', array( 'start', 'end', 'left', 'right' ), true ) ? $s['position'] : 'end';
		$direction = class_exists( '\Saponine\Studio\Multilingual' ) ? \Saponine\Studio\Multilingual::direction() : ( is_rtl() ? 'rtl' : 'ltr' );
		$class = 'sp-rail sp-rail--' . ( $fixed ? 'fixed' : 'inline' ) . ' sp-rail--' . $position;
		$class .= 'yes' === $s['hide_mobile'] ? ' sp-rail--hide-mobile' : '';
		?>
		<nav class="<?php echo esc_attr( $class ); ?>" data-sp-rail data-active-label="<?php echo 'yes' === $s['active_label'] ? 'yes' : 'no'; ?>" aria-label="<?php esc_attr_e( 'بخش‌های این صفحه', 'saponine-studio' ); ?>" dir="<?php echo esc_attr( $direction ); ?>">
			<?php if ( ! empty( $s['rail_label'] ) ) : ?><span class="sp-rail__label" dir="auto"><?php echo esc_html( $s['rail_label'] ); ?></span><?php endif; ?>
			<?php foreach ( $items as $i => $item ) :
				$anchor = preg_replace( '/[^A-Za-z0-9_-]/', '', ltrim( (string) ( $item['anchor'] ?? '' ), '#' ) );
				if ( ! $anchor ) { continue; }
				$url = '#' . $anchor;
				if ( class_exists( '\Saponine\Studio\Multilingual' ) ) { $url = \Saponine\Studio\Multilingual::translate_home_link( $url ); }
				?><a class="sp-rail__link" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $item['label'] ?? $anchor ); ?>"<?php echo 0 === $i ? ' aria-current="location"' : ''; ?>><span aria-hidden="true"></span></a>
			<?php endforeach; ?>
		</nav>
		<?php
	}
}
