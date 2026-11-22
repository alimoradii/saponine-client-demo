<?php
/** Editable Saponine hero carousel. */
namespace Saponine\Studio\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Hero_Slider extends Widget_Base {

	public function get_name() { return 'saponine-hero-slider'; }
	public function get_title() { return esc_html__( 'اسلایدر ساپونین', 'saponine-studio' ); }
	public function get_icon() { return 'eicon-slider-push'; }
	public function get_categories() { return array( 'saponine-studio' ); }
	public function get_keywords() { return array( 'hero', 'slider', 'saponine', 'اسلایدر' ); }
	public function get_style_depends() { return array( 'saponine-support-widgets' ); }
	public function get_script_depends() { return array( 'saponine-support-widgets' ); }

	protected function register_controls() {
		$this->start_controls_section( 'slides_section', array( 'label' => esc_html__( 'اسلایدها', 'saponine-studio' ) ) );
		$repeater = new Repeater();
		$repeater->add_control( 'title', array( 'label' => esc_html__( 'خط اول عنوان', 'saponine-studio' ), 'type' => Controls_Manager::TEXTAREA, 'default' => 'SAPONINE', 'rows' => 2 ) );
		$repeater->add_control( 'highlight', array( 'label' => esc_html__( 'عنوان رنگی', 'saponine-studio' ), 'type' => Controls_Manager::TEXTAREA, 'default' => 'BALANCE', 'rows' => 2 ) );
		$repeater->add_control( 'eyebrow', array( 'label' => esc_html__( 'زیرعنوان', 'saponine-studio' ), 'type' => Controls_Manager::TEXT, 'label_block' => true, 'default' => 'PLANT BASED WELLNESS' ) );
		$repeater->add_control( 'caption', array( 'label' => esc_html__( 'توضیح', 'saponine-studio' ), 'type' => Controls_Manager::TEXTAREA, 'default' => 'Your Daily Dose of Wellness', 'rows' => 2 ) );
		$repeater->add_control( 'image', array( 'label' => esc_html__( 'تصویر', 'saponine-studio' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => SAPONINE_STUDIO_URL . 'assets/images/hero.jpg' ) ) );
		$repeater->add_control( 'mobile_image', array( 'label' => esc_html__( 'تصویر اختصاصی موبایل (اختیاری)', 'saponine-studio' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => '', 'id' => 0 ) ) );
		$repeater->add_control( 'image_alt', array( 'label' => esc_html__( 'توضیح تصویر برای دسترس‌پذیری', 'saponine-studio' ), 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$repeater->add_control( 'layout', array( 'label' => esc_html__( 'جای تصویر', 'saponine-studio' ), 'type' => Controls_Manager::SELECT, 'default' => 'cover', 'options' => array( 'cover' => 'تصویر تمام پس‌زمینه', 'split' => 'تصویر مستقل در کنار متن' ) ) );
		$repeater->add_control( 'text_direction', array( 'label' => esc_html__( 'جهت متن', 'saponine-studio' ), 'type' => Controls_Manager::SELECT, 'default' => 'ltr', 'options' => array( 'auto' => 'مطابق زبان صفحه', 'ltr' => 'چپ به راست', 'rtl' => 'راست به چپ' ) ) );
		$repeater->add_control( 'background_color', array( 'label' => esc_html__( 'رنگ پس‌زمینه اسلاید', 'saponine-studio' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} {{CURRENT_ITEM}}' => 'background-color: {{VALUE}};' ) ) );
		$repeater->add_control( 'button_label', array( 'label' => esc_html__( 'متن دکمه (اختیاری)', 'saponine-studio' ), 'type' => Controls_Manager::TEXT ) );
		$repeater->add_control( 'button_link', array( 'label' => esc_html__( 'پیوند دکمه', 'saponine-studio' ), 'type' => Controls_Manager::URL, 'condition' => array( 'button_label!' => '' ) ) );
		$this->add_control( 'slides', array(
			'label' => esc_html__( 'اسلایدها؛ برای مرتب‌سازی بکشید', 'saponine-studio' ),
			'type' => Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'title_field' => '{{{ title }}} — {{{ highlight }}}',
			'default' => array(
				array( 'title' => 'SAPONINE', 'highlight' => 'BALANCE', 'eyebrow' => 'PLANT BASED WELLNESS', 'caption' => 'Your Daily Dose of Wellness', 'image' => array( 'url' => SAPONINE_STUDIO_URL . 'assets/images/hero.jpg' ), 'layout' => 'cover', 'text_direction' => 'ltr' ),
				array( 'title' => 'BOTANICAL', 'highlight' => 'SERUM', 'eyebrow' => 'SOOTHE · HYDRATE · RENEW', 'caption' => 'Science Inspired by Nature', 'image' => array( 'url' => SAPONINE_STUDIO_URL . 'assets/images/serum.jpg' ), 'layout' => 'split', 'text_direction' => 'ltr' ),
				array( 'title' => 'SAPONINE', 'highlight' => 'SACHET', 'eyebrow' => 'EFFERVESCENT FORMULA', 'caption' => 'Cleanse · Balance · Renew', 'image' => array( 'url' => SAPONINE_STUDIO_URL . 'assets/images/sachet.jpg' ), 'layout' => 'split', 'text_direction' => 'ltr' ),
			),
		) );
		$this->add_control( 'carousel_label', array( 'label' => esc_html__( 'نام دسترس‌پذیر اسلایدر', 'saponine-studio' ), 'type' => Controls_Manager::TEXT, 'default' => 'محصولات ساپونین' ) );
		$this->add_control( 'show_dots', array( 'label' => 'نشانگرهای اسلاید', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'show_arrows', array( 'label' => 'فلش‌های قبلی و بعدی', 'type' => Controls_Manager::SWITCHER, 'default' => '' ) );
		$this->add_control( 'show_wave', array( 'label' => 'موج پایین اسلایدر', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'hero_motion', array( 'label' => esc_html__( 'حرکت و انیمیشن اسلایدر', 'saponine-studio' ) ) );
		$this->add_control( 'autoplay', array( 'label' => 'پخش خودکار', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'interval', array( 'label' => 'فاصله تعویض (میلی‌ثانیه)', 'type' => Controls_Manager::NUMBER, 'default' => 6500, 'min' => 2000, 'max' => 60000, 'step' => 500, 'condition' => array( 'autoplay' => 'yes' ) ) );
		$this->add_control( 'pause_hover', array( 'label' => 'توقف هنگام قرار گرفتن نشانگر', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'condition' => array( 'autoplay' => 'yes' ) ) );
		$this->add_control( 'transition', array( 'label' => 'نوع تعویض', 'type' => Controls_Manager::SELECT, 'default' => 'zoom', 'options' => array( 'zoom' => 'محو و بزرگ‌نمایی', 'fade' => 'محو', 'slide' => 'حرکت افقی', 'none' => 'بدون انیمیشن' ) ) );
		$this->add_control( 'duration', array( 'label' => 'مدت تعویض (میلی‌ثانیه)', 'type' => Controls_Manager::NUMBER, 'default' => 1200, 'min' => 100, 'max' => 5000, 'step' => 100 ) );
		$this->add_control( 'text_delay', array( 'label' => 'تأخیر حرکت نوشته (میلی‌ثانیه)', 'type' => Controls_Manager::NUMBER, 'default' => 300, 'min' => 0, 'max' => 3000, 'step' => 50, 'selectors' => array( '{{WRAPPER}} .sp-hero' => '--sp-hero-text-delay: {{VALUE}}ms;' ) ) );
		$this->add_control( 'image_float', array( 'label' => 'شناوری تصویر مستقل', 'type' => Controls_Manager::SWITCHER, 'default' => '' ) );
		$this->add_control( 'float_distance', array( 'label' => 'دامنه شناوری (پیکسل)', 'type' => Controls_Manager::NUMBER, 'default' => 16, 'min' => 0, 'max' => 80, 'selectors' => array( '{{WRAPPER}} .sp-hero' => '--sp-hero-float-distance: {{VALUE}}px;' ), 'condition' => array( 'image_float' => 'yes' ) ) );
		$this->add_control( 'float_duration', array( 'label' => 'مدت چرخه شناوری (ثانیه)', 'type' => Controls_Manager::NUMBER, 'default' => 6, 'min' => 1, 'max' => 40, 'step' => .5, 'selectors' => array( '{{WRAPPER}} .sp-hero' => '--sp-hero-float-duration: {{VALUE}}s;' ), 'condition' => array( 'image_float' => 'yes' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'hero_layout_style', array( 'label' => 'اندازه و چیدمان', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'hero_height', array( 'label' => 'ارتفاع', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', 'vh', 'svh', 'dvh' ), 'range' => array( 'px' => array( 'min' => 200, 'max' => 1400 ), 'vh' => array( 'min' => 20, 'max' => 140 ), 'svh' => array( 'min' => 20, 'max' => 140 ), 'dvh' => array( 'min' => 20, 'max' => 140 ) ), 'default' => array( 'size' => 100, 'unit' => 'svh' ), 'selectors' => array( '{{WRAPPER}} .sp-hero' => '--sp-hero-height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'min_height', array( 'label' => 'حداقل ارتفاع', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 1200 ) ), 'default' => array( 'size' => 640, 'unit' => 'px' ), 'mobile_default' => array( 'size' => 720, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sp-hero' => '--sp-hero-min-height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'text_width', array( 'label' => 'عرض بخش متن', 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 15, 'max' => 100 ) ), 'selectors' => array( '{{WRAPPER}} #sp-hero-{{ID}} .sp-hero__copy' => 'width: {{SIZE}}%;' ) ) );
		$this->add_responsive_control( 'text_inset', array( 'label' => 'فاصله متن از لبه', 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 0, 'max' => 80 ) ), 'selectors' => array( '{{WRAPPER}} .sp-hero' => '--sp-hero-text-inset: {{SIZE}}%; --sp-hero-custom-inset: {{SIZE}}%;' ) ) );
		$this->add_responsive_control( 'text_align', array( 'label' => 'تراز متن', 'type' => Controls_Manager::SELECT, 'options' => array( 'start' => 'مطابق زبان متن', 'left' => 'چپ', 'center' => 'وسط', 'right' => 'راست' ), 'selectors' => array( '{{WRAPPER}} #sp-hero-{{ID}} .sp-hero__copy' => 'text-align: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'image_width', array( 'label' => 'عرض تصویر مستقل', 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 10, 'max' => 100 ) ), 'selectors' => array( '{{WRAPPER}} #sp-hero-{{ID}} .sp-hero__slide--split .sp-hero__media' => 'width: {{SIZE}}%;' ) ) );
		$this->add_responsive_control( 'image_height', array( 'label' => 'ارتفاع تصویر مستقل', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', 'vh' ), 'range' => array( 'px' => array( 'min' => 100, 'max' => 1200 ), 'vh' => array( 'min' => 10, 'max' => 100 ) ), 'selectors' => array( '{{WRAPPER}} #sp-hero-{{ID}} .sp-hero__slide--split .sp-hero__image' => 'height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'image_radius', array( 'label' => 'گردی گوشه تصویر مستقل', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 200 ) ), 'selectors' => array( '{{WRAPPER}} #sp-hero-{{ID}} .sp-hero__slide--split .sp-hero__image' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'cover_position', array( 'label' => 'جای تصویر پس‌زمینه', 'type' => Controls_Manager::SELECT, 'default' => 'left center', 'mobile_default' => '25% bottom', 'options' => array( 'left center' => 'چپ', 'center center' => 'وسط', 'right center' => 'راست', '25% bottom' => 'چپ و پایین (موبایل)', '8% center' => 'قاب سه محصول ساپونین (موبایل)' ), 'selectors' => array( '{{WRAPPER}} #sp-hero-{{ID}} .sp-hero__slide--cover .sp-hero__image' => '--sp-hero-cover-position: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'wave_height', array( 'label' => 'ارتفاع موج', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 20, 'max' => 300 ) ), 'selectors' => array( '{{WRAPPER}} #sp-hero-{{ID}} .sp-hero__wave' => 'height: {{SIZE}}{{UNIT}};' ), 'condition' => array( 'show_wave' => 'yes' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'hero_type_style', array( 'label' => 'رنگ و تایپوگرافی', 'tab' => Controls_Manager::TAB_STYLE ) );
		foreach ( array( 'heading_color' => array( 'رنگ عنوان', '.sp-hero__heading', 'color' ), 'accent_color' => array( 'رنگ عنوان برجسته و دکمه‌ها', '.sp-hero', '--sp-hero-accent' ), 'caption_color' => array( 'رنگ توضیح', '.sp-hero__caption', 'color' ), 'eyebrow_color' => array( 'رنگ زیرعنوان', '.sp-hero__eyebrow', 'color' ), 'background_color' => array( 'رنگ زمینه', '.sp-hero', 'background' ), 'wave_color' => array( 'رنگ موج', '.sp-hero__wave', 'fill' ), 'button_text_color' => array( 'رنگ نوشته دکمه', '.sp-hero__link', 'color' ) ) as $name => $control ) {
			$this->add_control( $name, array( 'label' => $control[0], 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} #sp-hero-{{ID}}' . ( '.sp-hero' === $control[1] ? '' : ' ' . $control[1] ) => $control[2] . ': {{VALUE}};' ) ) );
		}
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'heading_typography', 'label' => 'عنوان', 'selector' => '{{WRAPPER}} #sp-hero-{{ID}} .sp-hero__heading' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'eyebrow_typography', 'label' => 'زیرعنوان', 'selector' => '{{WRAPPER}} #sp-hero-{{ID}} .sp-hero__eyebrow' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'caption_typography', 'label' => 'توضیح', 'selector' => '{{WRAPPER}} #sp-hero-{{ID}} .sp-hero__caption' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'button_typography', 'label' => 'دکمه', 'selector' => '{{WRAPPER}} #sp-hero-{{ID}} .sp-hero__link' ) );
		$this->end_controls_section();
	}

	/** Old demo documents stored editor defaults that the mobile home layout superseded. */
	private function mobile_preset_classes() {
		$raw = (array) $this->get_data( 'settings' );
		$edited = (array) ( $raw['sn_slider_overrides'] ?? array() );
		$classes = array();
		$height = $raw['hero_height'] ?? array();
		$desktop_default = ! is_numeric( $height['size'] ?? '' ) || ( 100.0 === (float) $height['size'] && 'svh' === ( $height['unit'] ?? '' ) );
		if ( $desktop_default && ! is_numeric( $raw['hero_height_tablet']['size'] ?? '' ) && ! is_numeric( $raw['hero_height_mobile']['size'] ?? '' ) ) {
			$classes[] = 'sp-hero--mobile-height-preset';
		}
		$minimum = $raw['min_height_mobile'] ?? array();
		if ( ! in_array( 'min_height_mobile', $edited, true ) && ( ! is_numeric( $minimum['size'] ?? '' ) || ( 720.0 === (float) $minimum['size'] && 'px' === ( $minimum['unit'] ?? '' ) ) ) ) {
			$classes[] = 'sp-hero--mobile-min-preset';
		}
		if ( ! in_array( 'cover_position_mobile', $edited, true ) && in_array( $raw['cover_position_mobile'] ?? '', array( '', '25% bottom' ), true ) ) {
			$classes[] = 'sp-hero--mobile-position-preset';
		}
		return implode( ' ', $classes );
	}

	private function image_url( $media ) {
		if ( ! is_array( $media ) ) { return ''; }
		if ( ! empty( $media['id'] ) ) {
			$url = wp_get_attachment_image_url( absint( $media['id'] ), 'full' );
			if ( $url ) { return $url; }
		}
		return $media['url'] ?? '';
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$slides = isset( $s['slides'] ) && is_array( $s['slides'] ) ? $s['slides'] : array();
		if ( ! $slides ) { return; }
		$transition = in_array( $s['transition'], array( 'fade', 'slide', 'zoom', 'none' ), true ) ? $s['transition'] : 'fade';
		$config = array( 'autoplay' => 'yes' === $s['autoplay'], 'interval' => max( 2000, min( 60000, (int) $s['interval'] ) ), 'duration' => max( 100, min( 5000, (int) $s['duration'] ) ), 'pauseHover' => 'yes' === $s['pause_hover'], 'transition' => $transition );
		$config['labels'] = array( 'play' => __( 'پخش خودکار اسلایدها', 'saponine-studio' ), 'pause' => __( 'توقف پخش خودکار', 'saponine-studio' ), 'reduced' => __( 'پخش خودکار مطابق تنظیم کاهش حرکت دستگاه غیرفعال است', 'saponine-studio' ), 'editor' => __( 'پخش خودکار در ویرایشگر متوقف است؛ از نشانگرها استفاده کنید', 'saponine-studio' ) );
		$direction = class_exists( '\\Saponine\\Studio\\Multilingual' ) ? \Saponine\Studio\Multilingual::direction() : ( is_rtl() ? 'rtl' : 'ltr' );
		$id = 'sp-hero-' . $this->get_id();
		?>
		<section class="sp-hero <?php echo esc_attr( $this->mobile_preset_classes() ); ?><?php echo 'yes' === $s['image_float'] ? ' sp-hero--float' : ''; ?>" id="<?php echo esc_attr( $id ); ?>" dir="<?php echo esc_attr( $direction ); ?>" data-sp-hero="<?php echo esc_attr( wp_json_encode( $config ) ); ?>" data-transition="<?php echo esc_attr( $transition ); ?>" role="region" aria-roledescription="<?php esc_attr_e( 'اسلایدر', 'saponine-studio' ); ?>" aria-label="<?php echo esc_attr( $s['carousel_label'] ); ?>" <?php if ( count( $slides ) > 1 ) { echo 'tabindex="0"'; } ?>>
			<div class="sp-hero__slides" aria-live="off">
			<?php foreach ( $slides as $i => $slide ) :
				$layout = isset( $slide['layout'] ) && 'split' === $slide['layout'] ? 'split' : 'cover';
				$dir = in_array( $slide['text_direction'] ?? '', array( 'rtl', 'ltr' ), true ) ? $slide['text_direction'] : $direction;
				$url = $this->image_url( $slide['image'] ?? array() );
				$mobile_url = $this->image_url( $slide['mobile_image'] ?? array() );
				$alt = ! empty( $slide['image_alt'] ) ? $slide['image_alt'] : '';
				if ( ! $alt && ! empty( $slide['image']['id'] ) ) { $alt = get_post_meta( absint( $slide['image']['id'] ), '_wp_attachment_image_alt', true ); }
				$rep_id = ! empty( $slide['_id'] ) ? $slide['_id'] : 'slide-' . $i;
				?>
				<article id="<?php echo esc_attr( $id . '-slide-' . $i ); ?>" class="sp-hero__slide sp-hero__slide--<?php echo esc_attr( $layout ); ?> elementor-repeater-item-<?php echo esc_attr( $rep_id ); ?><?php echo 0 === $i ? ' is-active' : ''; ?>" aria-roledescription="<?php esc_attr_e( 'اسلاید', 'saponine-studio' ); ?>" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' / ' . count( $slides ) ); ?>" aria-hidden="<?php echo 0 === $i ? 'false' : 'true'; ?>" <?php echo 0 === $i ? '' : 'inert'; ?>>
					<?php if ( $url || $mobile_url ) : ?>
						<div class="sp-hero__media<?php echo $mobile_url ? ' sp-hero__media--mobile-art' : ''; ?>">
							<?php if ( $mobile_url ) : ?><picture><source media="(max-width: 767px)" srcset="<?php echo esc_url( $mobile_url ); ?>"><?php endif; ?>
							<img class="sp-hero__image" src="<?php echo esc_url( $url ?: $mobile_url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" decoding="async" <?php echo 0 === $i ? 'fetchpriority="high"' : 'loading="lazy"'; ?> />
							<?php if ( $mobile_url ) : ?></picture><?php endif; ?>
						</div>
					<?php endif; ?>
					<div class="sp-hero__copy" dir="<?php echo esc_attr( $dir ); ?>"><div class="sp-hero__copy-inner">
						<h2 class="sp-hero__heading"><?php echo nl2br( esc_html( $slide['title'] ?? '' ) ); ?><?php if ( ! empty( $slide['highlight'] ) ) : ?><span><?php echo nl2br( esc_html( $slide['highlight'] ) ); ?></span><?php endif; ?></h2>
						<?php if ( ! empty( $slide['eyebrow'] ) ) : ?><div class="sp-hero__eyebrow"><?php echo esc_html( $slide['eyebrow'] ); ?></div><?php endif; ?>
						<?php if ( ! empty( $slide['caption'] ) ) : ?><p class="sp-hero__caption"><?php echo nl2br( esc_html( $slide['caption'] ) ); ?></p><?php endif; ?>
						<?php if ( ! empty( $slide['button_label'] ) && ! empty( $slide['button_link']['url'] ) ) :
							$rel = array();
							if ( ! empty( $slide['button_link']['is_external'] ) ) { $rel[] = 'noopener'; $rel[] = 'noreferrer'; }
							if ( ! empty( $slide['button_link']['nofollow'] ) ) { $rel[] = 'nofollow'; }
							?><a class="sp-hero__link" href="<?php echo esc_url( class_exists( '\\Saponine\\Studio\\Multilingual' ) ? \Saponine\Studio\Multilingual::translate_home_link( $slide['button_link']['url'] ) : $slide['button_link']['url'] ); ?>"<?php echo ! empty( $slide['button_link']['is_external'] ) ? ' target="_blank"' : ''; ?><?php echo $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : ''; ?>><?php echo esc_html( $slide['button_label'] ); ?></a>
						<?php endif; ?>
					</div></div>
				</article>
			<?php endforeach; ?>
			</div>
			<?php if ( count( $slides ) > 1 && ( 'yes' === $s['show_arrows'] || 'yes' === $s['show_dots'] || 'yes' === $s['autoplay'] ) ) : ?>
			<div class="sp-hero__controls">
				<?php if ( 'yes' === $s['show_arrows'] ) : ?><button type="button" class="sp-hero__arrow" data-sp-prev aria-label="<?php esc_attr_e( 'اسلاید قبلی', 'saponine-studio' ); ?>">‹</button><?php endif; ?>
				<?php if ( 'yes' === $s['show_dots'] ) : ?><div class="sp-hero__dots" role="group" aria-label="<?php esc_attr_e( 'انتخاب اسلاید', 'saponine-studio' ); ?>"><?php foreach ( $slides as $i => $slide ) : ?><button type="button" class="sp-hero__dot" data-sp-slide="<?php echo esc_attr( $i ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'اسلاید %d', 'saponine-studio' ), $i + 1 ) ); ?>" aria-controls="<?php echo esc_attr( $id . '-slide-' . $i ); ?>" aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>"></button><?php endforeach; ?></div><?php endif; ?>
				<?php if ( 'yes' === $s['show_arrows'] ) : ?><button type="button" class="sp-hero__arrow" data-sp-next aria-label="<?php esc_attr_e( 'اسلاید بعدی', 'saponine-studio' ); ?>">›</button><?php endif; ?>
				<?php if ( 'yes' === $s['autoplay'] ) : ?><button type="button" class="sp-hero__pause" data-sp-pause aria-label="<?php esc_attr_e( 'توقف پخش خودکار', 'saponine-studio' ); ?>" aria-pressed="false"><span aria-hidden="true">Ⅱ</span></button><?php endif; ?>
			</div>
			<?php endif; ?>
			<?php if ( 'yes' === $s['show_wave'] ) : ?><svg class="sp-hero__wave" viewBox="0 0 1440 200" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 202 H80 C135 202 132 24 198 24 S264 202 314 202 H1440 V205 H0 Z" /></svg><?php endif; ?>
		</section>
		<?php
	}
}
