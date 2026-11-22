<?php
/** Accessible native WordPress search, editable in Elementor Free. */
namespace Saponine\Studio\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Search extends Widget_Base {
	public function get_name() { return 'saponine-search'; }
	public function get_title() { return esc_html__( 'جستجوی ساپونین', 'saponine-studio' ); }
	public function get_icon() { return 'eicon-search'; }
	public function get_categories() { return array( 'saponine-studio' ); }
	public function get_keywords() { return array( 'search', 'جستجو' ); }
	public function get_style_depends() { return array( 'saponine-support-widgets' ); }

	protected function register_controls() {
		$this->start_controls_section( 'search_content', array( 'label' => 'جستجو' ) );
		$this->add_control( 'toggle_label', array( 'label' => 'نام دکمه باز کردن', 'type' => Controls_Manager::TEXT, 'default' => 'جستجو' ) );
		$this->add_control( 'show_toggle_label', array( 'label' => 'نمایش نوشته کنار آیکون', 'type' => Controls_Manager::SWITCHER, 'default' => '' ) );
		$this->add_control( 'field_label', array( 'label' => 'برچسب کادر جستجو', 'type' => Controls_Manager::TEXT, 'default' => 'عبارت مورد نظر' ) );
		$this->add_control( 'placeholder', array( 'label' => 'راهنمای داخل کادر', 'type' => Controls_Manager::TEXT, 'default' => 'جستجو در سایت…', 'label_block' => true ) );
		$this->add_control( 'submit_label', array( 'label' => 'نوشته دکمه ارسال', 'type' => Controls_Manager::TEXT, 'default' => 'جستجو' ) );
		$this->end_controls_section();
		$this->start_controls_section( 'search_style', array( 'label' => 'ظاهر جستجو', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typography', 'selector' => '{{WRAPPER}} .sp-search' ) );
		foreach ( array( 'toggle_color' => array( 'رنگ آیکون', '.sp-search__toggle', 'color' ), 'toggle_background' => array( 'زمینه آیکون', '.sp-search__toggle', 'background' ), 'panel_background' => array( 'زمینه پنجره جستجو', '.sp-search__form', 'background' ), 'text_color' => array( 'رنگ متن ورودی', '.sp-search__input', 'color' ), 'input_background' => array( 'زمینه ورودی', '.sp-search__input', 'background' ), 'border_color' => array( 'رنگ حاشیه', '.sp-search', '--sp-search-border' ), 'button_background' => array( 'زمینه دکمه ارسال', '.sp-search__submit', 'background' ), 'button_color' => array( 'رنگ دکمه ارسال', '.sp-search__submit', 'color' ) ) as $name => $c ) {
			$this->add_control( $name, array( 'label' => $c[0], 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} ' . $c[1] => $c[2] . ': {{VALUE}};' ) ) );
		}
		$this->add_responsive_control( 'panel_width', array( 'label' => 'عرض پنجره جستجو', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 200, 'max' => 800 ) ), 'default' => array( 'size' => 330, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sp-search__form' => 'width: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'radius', array( 'label' => 'گردی گوشه‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'selectors' => array( '{{WRAPPER}} .sp-search' => '--sp-search-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'icon_size', array( 'label' => 'اندازه آیکون', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 12, 'max' => 48 ) ), 'selectors' => array( '{{WRAPPER}} .sp-search__toggle svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$id = 'sp-search-' . $this->get_id();
		$home = class_exists( '\Saponine\Studio\Multilingual' ) ? \Saponine\Studio\Multilingual::home_url() : home_url( '/' );
		$lang = class_exists( '\Saponine\Studio\Multilingual' ) ? \Saponine\Studio\Multilingual::current_language() : '';
		?>
		<details class="sp-search">
			<summary class="sp-search__toggle" aria-label="<?php echo esc_attr( $s['toggle_label'] ); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg><?php if ( 'yes' === $s['show_toggle_label'] ) : ?><span><?php echo esc_html( $s['toggle_label'] ); ?></span><?php endif; ?></summary>
			<form class="sp-search__form" role="search" method="get" action="<?php echo esc_url( $home ); ?>" aria-label="<?php echo esc_attr( $s['toggle_label'] ); ?>">
				<?php if ( $lang ) : ?><input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>" /><?php endif; ?>
				<label for="<?php echo esc_attr( $id ); ?>" class="sp-support-sr-only"><?php echo esc_html( $s['field_label'] ); ?></label>
				<input class="sp-search__input" type="search" id="<?php echo esc_attr( $id ); ?>" name="s" value="<?php echo esc_attr( get_search_query( false ) ); ?>" placeholder="<?php echo esc_attr( $s['placeholder'] ); ?>" />
				<button class="sp-search__submit" type="submit"><?php echo esc_html( $s['submit_label'] ); ?></button>
			</form>
		</details>
		<?php
	}
}
