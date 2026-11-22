<?php
/** Editable scrolling text or circular brand seal. */
namespace Saponine\Studio\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Marquee extends Widget_Base {
	public function get_name() { return 'saponine-marquee'; }
	public function get_title() { return esc_html__( 'نوار نوشته و نشان گرد', 'saponine-studio' ); }
	public function get_icon() { return 'eicon-animation-text'; }
	public function get_categories() { return array( 'saponine-studio' ); }
	public function get_keywords() { return array( 'marquee', 'badge', 'text', 'نوشته', 'نشان' ); }
	public function get_style_depends() { return array( 'saponine-support-widgets' ); }
	public function get_script_depends() { return array( 'saponine-support-widgets' ); }

	protected function register_controls() {
		$this->start_controls_section( 'marquee_content', array( 'label' => 'نوشته‌ها و نمایش' ) );
		$this->add_control( 'mode', array( 'label' => 'نوع نمایش', 'type' => Controls_Manager::SELECT, 'default' => 'line', 'options' => array( 'line' => 'نوار افقی', 'circle' => 'نشان گرد' ) ) );
		$r = new Repeater();
		$r->add_control( 'text', array( 'label' => 'متن', 'type' => Controls_Manager::TEXT, 'default' => 'Scientific Trust', 'label_block' => true ) );
		$this->add_control( 'items', array( 'label' => 'نوشته‌ها؛ برای مرتب‌سازی بکشید', 'type' => Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'title_field' => '{{{ text }}}', 'default' => array( array( 'text' => 'Scientific Trust' ), array( 'text' => 'Premium Quality' ) ) ) );
		$this->add_control( 'repeat_count', array( 'label' => 'تکرار نوشته‌ها در نوار', 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 8, 'default' => 2, 'condition' => array( 'mode' => 'line' ) ) );
		$this->add_control( 'text_direction', array( 'label' => 'جهت نوشته', 'type' => Controls_Manager::SELECT, 'default' => 'ltr', 'options' => array( 'auto' => 'مطابق زبان صفحه', 'ltr' => 'چپ به راست', 'rtl' => 'راست به چپ' ) ) );
		$this->add_control( 'center_text', array( 'label' => 'نوشته مرکز نشان گرد', 'type' => Controls_Manager::TEXTAREA, 'default' => "SAPONINE\nPHARMA", 'rows' => 2, 'condition' => array( 'mode' => 'circle' ) ) );
		$this->add_control( 'animate', array( 'label' => 'فعال بودن حرکت', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'duration', array( 'label' => 'مدت یک دور (ثانیه)', 'type' => Controls_Manager::NUMBER, 'default' => 28, 'min' => 3, 'max' => 180, 'step' => 1, 'selectors' => array( '{{WRAPPER}} .sp-marquee' => '--sp-marquee-duration: {{VALUE}}s;' ) ) );
		$this->add_control( 'direction', array( 'label' => 'جهت حرکت', 'type' => Controls_Manager::SELECT, 'default' => 'left', 'options' => array( 'left' => 'چپ / ساعت‌گرد', 'right' => 'راست / پادساعت‌گرد' ) ) );
		$this->add_control( 'pause_hover', array( 'label' => 'توقف هنگام هاور یا فوکوس', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'show_pause', array( 'label' => 'دکمه توقف حرکت', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'condition' => array( 'animate' => 'yes', 'mode' => 'line' ) ) );
		$this->end_controls_section();
		$this->start_controls_section( 'marquee_style', array( 'label' => 'ظاهر', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'text_color', array( 'label' => 'رنگ نوشته', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sp-marquee' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'background_color', array( 'label' => 'رنگ پس‌زمینه', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sp-marquee' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'center_color', array( 'label' => 'رنگ نوشته مرکز', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sp-marquee__center' => 'color: {{VALUE}};' ), 'condition' => array( 'mode' => 'circle' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'text_typography', 'selector' => '{{WRAPPER}} .sp-marquee__track', 'condition' => array( 'mode' => 'line' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'center_typography', 'selector' => '{{WRAPPER}} .sp-marquee__center', 'condition' => array( 'mode' => 'circle' ) ) );
		$this->add_responsive_control( 'gap', array( 'label' => 'فاصله نوشته‌ها', 'type' => Controls_Manager::SLIDER, 'default' => array( 'size' => 48, 'unit' => 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 200 ) ), 'selectors' => array( '{{WRAPPER}} .sp-marquee' => '--sp-marquee-gap: {{SIZE}}{{UNIT}};' ), 'condition' => array( 'mode' => 'line' ) ) );
		$this->add_responsive_control( 'padding', array( 'label' => 'فاصله داخلی نوار', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .sp-marquee--line' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ), 'condition' => array( 'mode' => 'line' ) ) );
		$this->add_responsive_control( 'circle_size', array( 'label' => 'قطر نشان گرد', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 70, 'max' => 500 ) ), 'default' => array( 'size' => 124, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sp-marquee--circle' => 'width: {{SIZE}}{{UNIT}};' ), 'condition' => array( 'mode' => 'circle' ) ) );
		$this->add_control( 'ring_font_size', array( 'label' => 'اندازه نوشته حلقه', 'type' => Controls_Manager::NUMBER, 'default' => 7.5, 'min' => 4, 'max' => 18, 'step' => .5, 'selectors' => array( '{{WRAPPER}} .sp-marquee__ring-text' => 'font-size: {{VALUE}}px;' ), 'condition' => array( 'mode' => 'circle' ) ) );
		$this->add_control( 'ring_letter_spacing', array( 'label' => 'فاصله حروف حلقه', 'type' => Controls_Manager::NUMBER, 'default' => 1.5, 'min' => 0, 'max' => 5, 'step' => .1, 'selectors' => array( '{{WRAPPER}} .sp-marquee__ring-text' => 'letter-spacing: {{VALUE}}px;' ), 'condition' => array( 'mode' => 'circle' ) ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$mode = 'circle' === $s['mode'] ? 'circle' : 'line';
		$items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
		if ( ! $items ) { return; }
		$reverse = 'right' === $s['direction'];
		$classes = 'sp-marquee sp-marquee--' . $mode;
		$classes .= 'yes' === $s['animate'] ? ' is-animated' : '';
		$classes .= 'yes' === $s['pause_hover'] ? ' sp-marquee--pause-hover' : '';
		$classes .= $reverse ? ' sp-marquee--reverse' : '';
		$page_direction = class_exists( '\\Saponine\\Studio\\Multilingual' ) ? \Saponine\Studio\Multilingual::direction() : ( is_rtl() ? 'rtl' : 'ltr' );
		$direction = in_array( $s['text_direction'] ?? '', array( 'rtl', 'ltr' ), true ) ? $s['text_direction'] : $page_direction;
		$labels = array( 'play' => 'circle' === $mode ? __( 'ادامه چرخش نشان', 'saponine-studio' ) : __( 'ادامه حرکت نوشته‌ها', 'saponine-studio' ), 'pause' => 'circle' === $mode ? __( 'توقف چرخش نشان', 'saponine-studio' ) : __( 'توقف حرکت نوشته‌ها', 'saponine-studio' ), 'reduced' => __( 'حرکت مطابق تنظیم کاهش حرکت دستگاه غیرفعال است', 'saponine-studio' ) );
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" data-sp-marquee data-sp-labels="<?php echo esc_attr( wp_json_encode( $labels ) ); ?>" dir="<?php echo esc_attr( $direction ); ?>">
		<?php if ( 'circle' === $mode ) :
			$path_id = 'sp-ring-' . $this->get_id();
			$text = implode( ' · ', array_map( static function ( $item ) { return isset( $item['text'] ) ? $item['text'] : ''; }, $items ) );
			?>
			<?php if ( 'yes' === $s['animate'] ) : ?><button type="button" class="sp-marquee__circle-toggle" data-sp-marquee-pause aria-pressed="false" aria-label="<?php esc_attr_e( 'توقف چرخش نشان', 'saponine-studio' ); ?>"><?php else : ?><div class="sp-marquee__circle-toggle"><?php endif; ?>
				<svg class="sp-marquee__ring" viewBox="0 0 100 100" aria-hidden="true" focusable="false"><defs><path id="<?php echo esc_attr( $path_id ); ?>" d="M50,50 m-38,0 a38,38 0 1,1 76,0 a38,38 0 1,1 -76,0" /></defs><text class="sp-marquee__ring-text" fill="currentColor" direction="<?php echo esc_attr( $direction ); ?>"><textPath href="#<?php echo esc_attr( $path_id ); ?>"><?php echo esc_html( $text . ' · ' . $text . ' · ' ); ?></textPath></text></svg>
				<span class="sp-marquee__center"><?php echo nl2br( esc_html( $s['center_text'] ) ); ?></span><span class="sp-support-sr-only"><?php echo esc_html( $text ); ?></span>
			<?php echo 'yes' === $s['animate'] ? '</button>' : '</div>'; ?>
		<?php else :
			$repeats = max( 1, min( 8, (int) $s['repeat_count'] ) );
			?>
			<div class="sp-marquee__track" dir="ltr">
			<?php for ( $copy = 0; $copy < 2; $copy++ ) : ?><div class="sp-marquee__group"<?php echo $copy ? ' aria-hidden="true"' : ''; ?>><?php for ( $r = 0; $r < $repeats; $r++ ) : ?><?php foreach ( $items as $item ) : ?><span dir="<?php echo esc_attr( $direction ); ?>"<?php echo $r ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html( $item['text'] ?? '' ); ?></span><?php endforeach; ?><?php endfor; ?></div><?php endfor; ?>
			</div>
			<?php if ( 'yes' === $s['animate'] && 'yes' === $s['show_pause'] ) : ?><button type="button" class="sp-marquee__pause" data-sp-marquee-pause aria-label="<?php esc_attr_e( 'توقف حرکت نوشته‌ها', 'saponine-studio' ); ?>" aria-pressed="false"><span aria-hidden="true">Ⅱ</span></button><?php endif; ?>
		<?php endif; ?>
		</div>
		<?php
	}
}
