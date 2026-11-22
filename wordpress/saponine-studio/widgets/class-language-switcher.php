<?php
/** Accessible language navigation for the optional, free Polylang integration. */
namespace Saponine\Studio\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use Saponine\Studio\Multilingual;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Language_Switcher extends Widget_Base {

	public function get_name() { return 'saponine-language-switcher'; }
	public function get_title() { return esc_html__( 'انتخاب زبان ساپونین', 'saponine-studio' ); }
	public function get_icon() { return 'eicon-globe'; }
	public function get_categories() { return array( 'saponine-studio' ); }
	public function get_keywords() { return array( 'language', 'polylang', 'translation', 'زبان', 'ترجمه' ); }
	public function get_style_depends() { return array( 'saponine-support-widgets' ); }
	protected function is_dynamic_content(): bool { return true; }

	protected function register_controls() {
		$this->start_controls_section( 'language_content', array( 'label' => 'زبان‌ها' ) );
		$this->add_control( 'language_help', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => 'زبان‌ها از تنظیمات Polylang خوانده می‌شوند. فقط نسخه‌های منتشرشدهٔ همین صفحه نمایش داده می‌شوند. متن، تصویر و چیدمان هر زبان را در سند المنتور همان زبان ویرایش کنید.', 'content_classes' => 'elementor-descriptor' ) );
		$this->add_control( 'label_mode', array( 'label' => 'نوشتهٔ دکمه زبان', 'type' => Controls_Manager::SELECT, 'default' => 'name', 'options' => array( 'name' => 'نام زبان (فارسی، English…)', 'code' => 'کد کوتاه (FA، EN…)' ) ) );
		$this->add_control( 'show_current', array( 'label' => 'نمایش زبان فعلی', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'hide_single', array( 'label' => 'پنهان وقتی فقط یک زبان منتشر شده', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'switcher_label', array( 'label' => 'نام دسترس‌پذیر انتخاب زبان', 'type' => Controls_Manager::TEXT, 'default' => __( 'انتخاب زبان', 'saponine-studio' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'language_style', array( 'label' => 'ظاهر انتخاب زبان', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'orientation', array( 'label' => 'چیدمان', 'type' => Controls_Manager::SELECT, 'default' => 'row', 'options' => array( 'row' => 'افقی', 'column' => 'عمودی' ), 'selectors' => array( '{{WRAPPER}} .sp-languages__list' => 'flex-direction: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'alignment', array( 'label' => 'تراز', 'type' => Controls_Manager::SELECT, 'options' => array( 'flex-start' => 'ابتدا', 'center' => 'وسط', 'flex-end' => 'انتها' ), 'selectors' => array( '{{WRAPPER}} .sp-languages__list' => 'justify-content: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'gap', array( 'label' => 'فاصله زبان‌ها', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', 'em' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 80 ), 'em' => array( 'min' => 0, 'max' => 5, 'step' => .1 ) ), 'selectors' => array( '{{WRAPPER}} .sp-languages__list' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typography', 'selector' => '{{WRAPPER}} .sp-languages__link' ) );
		foreach ( array( 'text_color' => array( 'رنگ متن', '--sp-language-text' ), 'background' => array( 'پس‌زمینه', '--sp-language-bg' ), 'active_color' => array( 'رنگ زبان فعلی و هاور', '--sp-language-active-text' ), 'active_background' => array( 'پس‌زمینه زبان فعلی و هاور', '--sp-language-active-bg' ) ) as $key => $control ) {
			$this->add_control( $key, array( 'label' => $control[0], 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sp-languages' => $control[1] . ': {{VALUE}};' ) ) );
		}
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'border', 'selector' => '{{WRAPPER}} .sp-languages__link' ) );
		$this->add_responsive_control( 'radius', array( 'label' => 'گردی گوشه‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 80 ) ), 'selectors' => array( '{{WRAPPER}} .sp-languages__link' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'padding', array( 'label' => 'فاصله داخلی دکمه', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .sp-languages__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$languages = class_exists( Multilingual::class ) ? Multilingual::switcher_languages( array( 'hide_missing' => true ) ) : array();
		$languages = array_values( array_filter( $languages, static function ( $item ) { return ! empty( $item['url'] ) && empty( $item['no_translation'] ); } ) );
		$hidden = ! $languages || ( 'yes' === ( $s['hide_single'] ?? 'yes' ) && count( $languages ) < 2 );
		if ( 'yes' !== ( $s['show_current'] ?? 'yes' ) ) {
			$languages = array_values( array_filter( $languages, static function ( $item ) { return empty( $item['current'] ); } ) );
		}
		if ( $hidden || ! $languages ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
				echo '<div class="sp-languages__placeholder">' . esc_html__( 'انتخاب زبان پس از تنظیم Polylang و انتشار ترجمه‌ها نمایش داده می‌شود.', 'saponine-studio' ) . '</div>';
			}
			return;
		}
		$code = 'code' === ( $s['label_mode'] ?? 'name' );
		?>
		<nav class="sp-languages" aria-label="<?php echo esc_attr( $s['switcher_label'] ?? __( 'انتخاب زبان', 'saponine-studio' ) ); ?>">
			<ul class="sp-languages__list">
				<?php foreach ( $languages as $language ) :
					$locale = str_replace( '_', '-', $language['locale'] ?? $language['slug'] );
					$label = $code ? strtoupper( $language['slug'] ) : $language['name'];
					$dir = $code || empty( $language['rtl'] ) ? 'ltr' : 'rtl';
					?>
					<li class="sp-languages__item<?php echo ! empty( $language['current'] ) ? ' is-current' : ''; ?>"><a class="sp-languages__link" href="<?php echo esc_url( $language['url'] ); ?>" hreflang="<?php echo esc_attr( $locale ); ?>" lang="<?php echo esc_attr( $locale ); ?>" aria-label="<?php echo esc_attr( $language['name'] ); ?>"<?php echo ! empty( $language['current'] ) ? ' aria-current="page"' : ''; ?>><bdi dir="<?php echo esc_attr( $dir ); ?>"><?php echo esc_html( $label ); ?></bdi></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}
}
