<?php
/**
 * Editable Saponine product presentation.
 *
 * @package Saponine_Studio
 */

namespace Saponine\Studio\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Product_Showcase extends Widget_Base {

	public function get_name() { return 'saponine-product-showcase'; }
	public function get_title() { return esc_html__( 'محصولات متحرک ساپونین', 'saponine-studio' ); }
	public function get_icon() { return 'eicon-product-images'; }
	public function get_categories() { return array( 'saponine-studio' ); }
	public function get_keywords() { return array( 'product', 'motion', 'slider', 'محصول', 'موشن' ); }
	public function get_script_depends() { return array( 'saponine-product-showcase' ); }
	public function get_style_depends() { return array( 'saponine-product-showcase' ); }

	/** Defaults mirror the approved product section; image URLs are local. */
	public static function default_products() {
		$base = SAPONINE_STUDIO_URL . 'assets/images/';
		return array(
			array(
				'_id' => 'balance',
				'title' => 'ساپونین بالانس', 'en_title' => 'Saponine Balance',
				'image' => array( 'url' => $base . 'bottle.jpg', 'id' => 0 ), 'image_alt' => 'ساپونین بالانس',
				'description' => 'مکمل گیاهی ساپونین بالانس با ترکیبات طبیعی، برای حمایت از تعادل روزانه بدن و تقویت سیستم ایمنی طراحی شده است.',
				'features' => "تقویت سیستم ایمنی\nخاصیت آنتی‌اکسیدانی\nترکیبات کاملاً گیاهی", 'usage' => 'سه ماه',
			),
			array(
				'_id' => 'serum',
				'title' => 'سرم بوتانیکال', 'en_title' => 'Botanical Serum',
				'image' => array( 'url' => $base . 'serum.jpg', 'id' => 0 ), 'image_alt' => 'سرم بوتانیکال',
				'description' => 'سرم بوتانیکال ساپونین با عصاره‌های گیاهی، به آبرسانی عمقی، تسکین و بازسازی پوست کمک می‌کند.',
				'features' => "آبرسانی عمقی پوست\nتسکین و کاهش التهاب\nبازسازی و شادابی پوست", 'usage' => '۶۰ تا ۹۰ روز',
			),
			array(
				'_id' => 'sachet',
				'title' => 'ساشه جوشان', 'en_title' => 'Effervescent Sachet',
				'image' => array( 'url' => $base . 'sachet.jpg', 'id' => 0 ), 'image_alt' => 'ساشه جوشان',
				'description' => 'ساشه جوشان ساپونین فرمولی سریع‌الجذب برای حمایت روزانه از سلامت و پاکسازی طبیعی بدن است.',
				'features' => "جذب سریع و آسان\nحمایت آنتی‌اکسیدانی\nسلامت روزانه", 'usage' => '۳ تا ۶ ماه',
			),
		);
	}

	protected function register_controls() {
		$this->start_controls_section( 'sps_products', array( 'label' => esc_html__( 'محصولات', 'saponine-studio' ) ) );
		$repeater = new Repeater();
		$repeater->add_control( 'title', array( 'label' => 'نام فارسی', 'type' => Controls_Manager::TEXT, 'default' => 'نام محصول', 'label_block' => true ) );
		$repeater->add_control( 'en_title', array( 'label' => 'نام لاتین / عنوان زبانه', 'type' => Controls_Manager::TEXT, 'default' => 'Product name', 'label_block' => true ) );
		$repeater->add_control( 'image', array( 'label' => 'تصویر محصول', 'type' => Controls_Manager::MEDIA, 'media_types' => array( 'image' ), 'default' => array( 'url' => SAPONINE_STUDIO_URL . 'assets/images/bottle.jpg' ) ) );
		$repeater->add_control( 'ring_image', array( 'label' => 'تصویر بدون پس‌زمینه برای حلقه', 'type' => Controls_Manager::MEDIA, 'media_types' => array( 'image' ), 'default' => array( 'url' => '' ), 'description' => 'برای نمایش داخل حلقه می‌توانید فایل شفاف PNG یا WebP انتخاب کنید.' ) );
		$repeater->add_control( 'image_alt', array( 'label' => 'متن جایگزین تصویر', 'type' => Controls_Manager::TEXT ) );
		$repeater->add_control( 'description', array( 'label' => 'توضیح کوتاه', 'type' => Controls_Manager::TEXTAREA, 'rows' => 5 ) );
		$repeater->add_control( 'features', array( 'label' => 'ویژگی‌ها؛ هر خط یک مورد', 'type' => Controls_Manager::TEXTAREA, 'rows' => 4 ) );
		$repeater->add_control( 'usage', array( 'label' => 'مقدار یا توضیح مصرف', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$repeater->add_control( 'detail_description', array( 'label' => 'توضیح پنجره جزئیات', 'type' => Controls_Manager::TEXTAREA, 'rows' => 6, 'description' => 'اگر خالی باشد، توضیح کوتاه نمایش داده می‌شود.' ) );
		$repeater->add_control( 'modal_note', array( 'label' => 'یادداشت اختصاصی محصول', 'type' => Controls_Manager::TEXTAREA, 'description' => 'اگر خالی باشد، یادداشت عمومی نمایش داده می‌شود.' ) );
		$repeater->add_control( 'cta_label', array( 'label' => 'عنوان پیوند اختیاری در جزئیات', 'type' => Controls_Manager::TEXT ) );
		$repeater->add_control( 'cta_link', array( 'label' => 'پیوند اختیاری محصول', 'type' => Controls_Manager::URL, 'options' => array( 'url', 'is_external', 'nofollow' ), 'label_block' => true ) );
		$this->add_control( 'products', array( 'label' => 'فهرست محصولات', 'type' => Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'default' => self::default_products(), 'title_field' => '{{{ title }}}', 'prevent_empty' => false ) );
		$this->end_controls_section();

		$this->start_controls_section( 'sps_labels', array( 'label' => 'متن‌ها و نمایش' ) );
		foreach ( array(
			'details_label' => array( 'متن دکمه جزئیات', 'جزییات محصول' ),
			'view_label' => array( 'راهنمای روی تصویر', 'مشاهده محصول' ),
			'usage_label' => array( 'عنوان توضیح مصرف', 'حداقل مدت زمان مصرف' ),
			'modal_kicker' => array( 'عنوان کوچک پنجره', 'SAPONINE PRODUCT' ),
			'close_label' => array( 'برچسب بستن', 'بستن' ),
			'previous_label' => array( 'برچسب قبلی', 'محصول قبلی' ),
			'next_label' => array( 'برچسب بعدی', 'محصول بعدی' ),
			'section_label' => array( 'عنوان دسترس‌پذیری', 'محصولات ساپونین' ),
		) as $key => $value ) {
			$this->add_control( $key, array( 'label' => $value[0], 'type' => Controls_Manager::TEXT, 'default' => $value[1], 'label_block' => true ) );
		}
		$this->add_control( 'modal_note', array( 'label' => 'یادداشت عمومی جزئیات', 'type' => Controls_Manager::TEXTAREA, 'default' => 'برای مشخصات و شیوه مصرف، اطلاعات رسمی درج‌شده روی بسته‌بندی محصول را بررسی کنید.' ) );
		$this->add_control( 'ring_text', array( 'label' => 'متن حلقه', 'type' => Controls_Manager::TEXTAREA, 'default' => 'SAPONINE • PHARMA • SCIENTIFIC WELLNESS • SAPONINE • PHARMA • SCIENTIFIC WELLNESS •' ) );
		$this->add_control( 'tabs_direction', array( 'label' => 'جهت زبانه‌ها', 'type' => Controls_Manager::SELECT, 'default' => 'ltr', 'options' => array( 'auto' => 'مطابق زبان صفحه', 'ltr' => 'چپ به راست', 'rtl' => 'راست به چپ' ) ) );
		$this->add_control( 'title_tag', array( 'label' => 'تگ عنوان', 'type' => Controls_Manager::SELECT, 'default' => 'h2', 'options' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'P' ) ) );
		foreach ( array( 'show_tabs' => 'نمایش زبانه‌ها', 'show_arrows' => 'نمایش فلش‌ها', 'show_ring' => 'نمایش حلقه', 'enable_modal' => 'باز شدن پنجره جزئیات', 'show_hint' => 'نمایش راهنمای تصویر' ) as $key => $label ) {
			$this->add_control( $key, array( 'label' => $label, 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		}
		$this->end_controls_section();

		$this->start_controls_section( 'sps_layout', array( 'label' => 'چیدمان و فاصله‌ها', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'content_side', array( 'label' => 'جای متن؛ در حالت ستونی ترتیب بالا/پایین', 'type' => Controls_Manager::SELECT, 'default' => 'right', 'options' => array( 'right' => 'راست / بالا', 'left' => 'چپ / پایین' ), 'selectors' => array( '{{WRAPPER}} .sps-copy' => 'order: {{VALUE}};' ), 'selectors_dictionary' => array( 'right' => '0', 'left' => '2' ) ) );
		$this->add_responsive_control( 'columns_layout', array( 'label' => 'نسبت ستون‌های متن و تصویر', 'type' => Controls_Manager::SELECT, 'default' => 'equal', 'mobile_default' => 'stack', 'options' => array( 'equal' => 'دو ستون برابر', 'first_wide' => 'ستون راست عریض‌تر', 'last_wide' => 'ستون چپ عریض‌تر', 'stack' => 'زیر هم' ), 'selectors' => array( '{{WRAPPER}} .sps-panel' => 'grid-template-columns: {{VALUE}};' ), 'selectors_dictionary' => array( 'equal' => 'minmax(0,1fr) minmax(0,1fr)', 'first_wide' => 'minmax(0,1.3fr) minmax(0,1fr)', 'last_wide' => 'minmax(0,1fr) minmax(0,1.3fr)', 'stack' => 'minmax(0,1fr)' ) ) );
		$this->add_responsive_control( 'layout_gap', array( 'label' => 'فاصله متن و تصویر', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 160 ) ), 'default' => array( 'size' => 48, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sps-panel' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'tabs_gap', array( 'label' => 'فاصله زبانه‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ), 'default' => array( 'size' => 12, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sps-tabs' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'tabs_bottom', array( 'label' => 'فاصله زیر زبانه‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 120 ) ), 'default' => array( 'size' => 40, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sps-tabs' => 'margin-bottom: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'content_align', array( 'label' => 'تراز متن', 'type' => Controls_Manager::CHOOSE, 'options' => array( 'start' => array( 'title' => 'مطابق زبان صفحه', 'icon' => 'eicon-text-align-right' ), 'right' => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ), 'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ), 'left' => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ) ), 'default' => 'start', 'selectors' => array( '{{WRAPPER}} .sps-copy' => 'text-align: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'features_columns', array( 'label' => 'ستون‌های ویژگی‌ها', 'type' => Controls_Manager::SELECT, 'default' => '3', 'mobile_default' => '1', 'options' => array( '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴' ), 'selectors' => array( '{{WRAPPER}} .sps-features' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ) ) );
		$this->add_responsive_control( 'copy_padding', array( 'label' => 'فاصله داخلی متن', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%', 'em' ), 'selectors' => array( '{{WRAPPER}} .sps-copy' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'section_padding', array( 'label' => 'فاصله داخلی کل ابزارک', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%', 'em' ), 'selectors' => array( '{{WRAPPER}} .sps-root' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'sps_colors', array( 'label' => 'رنگ‌ها و تایپوگرافی', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'palette_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => 'رنگ خالی از پالت عمومی سایت استفاده می‌کند. برای تغییر فقط این ابزارک، رنگ دلخواه را انتخاب کنید.', 'content_classes' => 'elementor-descriptor' ) );
		$colors = array(
			'accent_color' => array( 'رنگ اصلی', '#7d4697', '--sps-accent' ),
			'soft_color' => array( 'رنگ روشن حلقه و نور', '#e5afd1', '--sps-soft' ),
			'text_color' => array( 'رنگ عنوان', '#3c3c3a', '--sps-ink' ),
			'muted_color' => array( 'رنگ توضیحات', '#736b77', '--sps-muted' ),
			'card_color' => array( 'پس‌زمینه کارت و زبانه', '#ffffff', '--sps-card' ),
			'button_color' => array( 'پس‌زمینه دکمه', '#3c3c3a', '--sps-button' ),
			'button_text_color' => array( 'متن دکمه', '#ffffff', '--sps-button-text' ),
			'active_tab_text' => array( 'متن زبانه فعال', '#ffffff', '--sps-active-text' ),
			'ring_color' => array( 'رنگ متن و خط حلقه', '#7d4697', '--sps-ring-color' ),
		);
		foreach ( $colors as $key => $value ) {
			$this->add_control( $key, array( 'label' => $value[0], 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .sps-root' => $value[2] . ': {{VALUE}};' ) ) );
		}
		$this->add_control( 'background_color', array( 'label' => 'پس‌زمینه ابزارک', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sps-root' => 'background-color: {{VALUE}};' ) ) );
		foreach ( array( 'title' => array( 'تایپوگرافی عنوان', '.sps-title' ), 'english' => array( 'تایپوگرافی نام لاتین', '.sps-en' ), 'description' => array( 'تایپوگرافی توضیح', '.sps-description' ), 'tabs' => array( 'تایپوگرافی زبانه‌ها', '.sps-tab' ), 'features' => array( 'تایپوگرافی ویژگی‌ها', '.sps-features, .sps-usage' ), 'button' => array( 'تایپوگرافی دکمه', '.sps-button' ) ) as $key => $value ) {
			$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $key . '_typography', 'label' => $value[0], 'selector' => '{{WRAPPER}} ' . str_replace( ', ', ', {{WRAPPER}} ', $value[1] ) ) );
		}
		$this->end_controls_section();

		$this->start_controls_section( 'sps_image_style', array( 'label' => 'تصویر و حلقه', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'image_width', array( 'label' => 'عرض تصویر', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%', 'vw' ), 'range' => array( 'px' => array( 'min' => 80, 'max' => 900 ), '%' => array( 'min' => 10, 'max' => 100 ), 'vw' => array( 'min' => 10, 'max' => 80 ) ), 'default' => array( 'size' => 420, 'unit' => 'px' ), 'mobile_default' => array( 'size' => 280, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sps-image-button' => 'width: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'stage_height', array( 'label' => 'حداقل ارتفاع صحنه', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 180, 'max' => 1000 ) ), 'default' => array( 'size' => 480, 'unit' => 'px' ), 'mobile_default' => array( 'size' => 340, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sps-stage' => 'min-height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'image_offset_x', array( 'label' => 'جابجایی افقی تصویر', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => -150, 'max' => 150 ) ), 'selectors' => array( '{{WRAPPER}} .sps-image-float' => 'left: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'image_offset_y', array( 'label' => 'جابجایی عمودی تصویر', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => -150, 'max' => 150 ) ), 'selectors' => array( '{{WRAPPER}} .sps-image-float' => 'top: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'image_radius', array( 'label' => 'گردی تصویر', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 160 ) ), 'default' => array( 'size' => 40, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sps-product-image, {{WRAPPER}} .sps-modal-image, {{WRAPPER}} .sps-flip' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'image_border', 'selector' => '{{WRAPPER}} .sps-product-image' ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'image_shadow', 'selector' => '{{WRAPPER}} .sps-product-image' ) );
		$this->add_responsive_control( 'ring_size', array( 'label' => 'اندازه حلقه نسبت به صحنه', 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 30, 'max' => 140 ) ), 'default' => array( 'size' => 112, 'unit' => '%' ), 'selectors' => array( '{{WRAPPER}} .sps-stage .sps-ring' => 'width: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'ring_opacity', array( 'label' => 'شفافیت حلقه', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ), 'default' => array( 'size' => .9 ), 'selectors' => array( '{{WRAPPER}} .sps-ring' => 'opacity: {{SIZE}};' ) ) );
		$this->add_control( 'ring_font_size', array( 'label' => 'اندازه نوشته روی حلقه', 'type' => Controls_Manager::NUMBER, 'min' => 5, 'max' => 22, 'default' => 10, 'selectors' => array( '{{WRAPPER}} .sps-ring text' => 'font-size: {{VALUE}}px;' ) ) );
		$this->add_control( 'ring_letter_spacing', array( 'label' => 'فاصله حروف حلقه', 'type' => Controls_Manager::NUMBER, 'min' => 0, 'max' => 8, 'step' => .1, 'default' => 3, 'selectors' => array( '{{WRAPPER}} .sps-ring text' => 'letter-spacing: {{VALUE}}px;' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'sps_details_style', array( 'label' => 'دکمه‌ها، کارت‌ها و پنجره', 'tab' => Controls_Manager::TAB_STYLE ) );
		foreach ( array( 'button' => array( 'دکمه', '.sps-button' ), 'tabs' => array( 'زبانه', '.sps-tab' ), 'features' => array( 'کارت ویژگی', '.sps-features li, .sps-usage' ) ) as $key => $value ) {
			$selector = '{{WRAPPER}} ' . str_replace( ', ', ', {{WRAPPER}} ', $value[1] );
			$this->add_responsive_control( $key . '_padding', array( 'label' => 'فاصله داخلی ' . $value[0], 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
			$this->add_responsive_control( $key . '_radius', array( 'label' => 'گردی ' . $value[0], 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 100 ) ), 'selectors' => array( $selector => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		}
		$this->add_control( 'modal_bg', array( 'label' => 'پس‌زمینه پنجره', 'type' => Controls_Manager::COLOR, 'default' => '#fbf8fc', 'selectors' => array( '{{WRAPPER}} .sps-modal-shell' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'modal_overlay', array( 'label' => 'رنگ پرده پشت پنجره', 'type' => Controls_Manager::COLOR, 'default' => 'rgba(37,27,42,.73)', 'selectors' => array( '{{WRAPPER}} .sps-modal::backdrop' => 'background: {{VALUE}};', '{{WRAPPER}} .sps-modal.sps-modal-fallback' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'modal_content_side', array( 'label' => 'جای متن پنجره', 'type' => Controls_Manager::SELECT, 'default' => 'left', 'options' => array( 'right' => 'راست / بالا', 'left' => 'چپ / پایین' ), 'selectors' => array( '{{WRAPPER}} .sps-modal-copy' => 'order: {{VALUE}};' ), 'selectors_dictionary' => array( 'right' => '-1', 'left' => '1' ) ) );
		$this->add_responsive_control( 'modal_columns', array( 'label' => 'ستون‌های پنجره', 'type' => Controls_Manager::SELECT, 'default' => 'media_wide', 'mobile_default' => 'stack', 'options' => array( 'equal' => 'دو ستون برابر', 'media_wide' => 'ستون راست عریض‌تر', 'copy_wide' => 'ستون چپ عریض‌تر', 'stack' => 'زیر هم' ), 'selectors' => array( '{{WRAPPER}} .sps-modal-shell' => 'grid-template-columns: {{VALUE}};' ), 'selectors_dictionary' => array( 'equal' => 'minmax(0,1fr) minmax(0,1fr)', 'media_wide' => 'minmax(0,1.08fr) minmax(0,.92fr)', 'copy_wide' => 'minmax(0,.92fr) minmax(0,1.08fr)', 'stack' => 'minmax(0,1fr)' ) ) );
		$this->add_responsive_control( 'modal_image_width', array( 'label' => 'عرض تصویر پنجره', 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 20, 'max' => 100 ) ), 'default' => array( 'size' => 80, 'unit' => '%' ), 'mobile_default' => array( 'size' => 55, 'unit' => '%' ), 'selectors' => array( '{{WRAPPER}} .sps-modal-image' => 'width: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'modal_image_max_height', array( 'label' => 'حداکثر ارتفاع تصویر پنجره', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'vh', 'px' ), 'range' => array( 'vh' => array( 'min' => 20, 'max' => 90 ), 'px' => array( 'min' => 150, 'max' => 1000 ) ), 'default' => array( 'size' => 62, 'unit' => 'vh' ), 'mobile_default' => array( 'size' => 40, 'unit' => 'vh' ), 'selectors' => array( '{{WRAPPER}} .sps-modal-image' => 'max-height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'modal_width', array( 'label' => 'حداکثر عرض پنجره', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 400, 'max' => 1500 ) ), 'default' => array( 'size' => 1160, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sps-modal-shell' => 'max-width: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'modal_radius', array( 'label' => 'گردی پنجره', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 100 ) ), 'default' => array( 'size' => 36, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .sps-modal-shell' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'modal_padding', array( 'label' => 'فاصله داخلی متن پنجره', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em', '%' ), 'selectors' => array( '{{WRAPPER}} .sps-modal-copy' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'modal_title_typography', 'label' => 'تایپوگرافی عنوان پنجره', 'selector' => '{{WRAPPER}} .sps-modal-title' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'modal_body_typography', 'label' => 'تایپوگرافی متن پنجره', 'selector' => '{{WRAPPER}} .sps-modal-description' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'sps_motion', array( 'label' => 'موشن محصولات', 'tab' => Controls_Manager::TAB_STYLE ) );
		foreach ( array( 'float_enabled' => 'حرکت شناور تصویر', 'tilt_enabled' => 'چرخش با حرکت نشانگر', 'ring_spin' => 'چرخش حلقه' ) as $key => $label ) {
			$this->add_control( $key, array( 'label' => $label, 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'frontend_available' => true ) );
		}
		foreach ( array(
			'float_amplitude' => array( 'دامنه شناوری (پیکسل)', 13, 0, 80, 1 ),
			'float_duration' => array( 'زمان یک دور شناوری (ثانیه)', 6.5, .5, 60, .1 ),
			'tilt_amount' => array( 'شدت چرخش نشانگر (درجه)', 11, 0, 40, 1 ),
			'ring_duration' => array( 'زمان یک دور حلقه (ثانیه)', 34, 2, 180, 1 ),
			'transition_duration' => array( 'زمان تعویض محصول (میلی‌ثانیه)', 720, 0, 3000, 50 ),
			'modal_duration' => array( 'زمان باز شدن جزئیات (میلی‌ثانیه)', 850, 0, 3000, 50 ),
		) as $key => $value ) {
			$this->add_control( $key, array( 'label' => $value[0], 'type' => Controls_Manager::NUMBER, 'default' => $value[1], 'min' => $value[2], 'max' => $value[3], 'step' => $value[4], 'frontend_available' => true ) );
		}
		$this->add_control( 'transition_type', array( 'label' => 'نوع تعویض محصول', 'type' => Controls_Manager::SELECT, 'default' => 'rotate', 'options' => array( 'none' => 'بدون حرکت', 'fade' => 'محو شدن', 'slide' => 'لغزش', 'scale' => 'بزرگ‌نمایی', 'rotate' => 'چرخش سه‌بعدی' ), 'frontend_available' => true ) );
		$this->add_control( 'modal_animation', array( 'label' => 'انیمیشن پنجره جزئیات', 'type' => Controls_Manager::SELECT, 'default' => 'flip', 'options' => array( 'none' => 'بدون حرکت', 'fade' => 'محو شدن', 'slide' => 'حرکت از پایین', 'scale' => 'بزرگ‌نمایی', 'flip' => 'انتقال تصویر از صحنه به پنجره' ), 'frontend_available' => true ) );
		$this->add_control( 'motion_easing', array( 'label' => 'منحنی حرکت', 'type' => Controls_Manager::SELECT, 'default' => 'smooth', 'options' => array( 'smooth' => 'نرم', 'ease-in-out' => 'شتاب و توقف تدریجی', 'ease-out' => 'توقف تدریجی', 'linear' => 'سرعت ثابت' ), 'frontend_available' => true ) );
		$this->add_control( 'motion_mobile', array( 'label' => 'اجرای موشن در موبایل', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'frontend_available' => true ) );
		$this->add_control( 'motion_notice', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => 'اگر کاربر کاهش حرکت را در دستگاه خود فعال کرده باشد، انیمیشن‌ها متوقف می‌شوند. همه محتوا همچنان قابل استفاده است.', 'content_classes' => 'elementor-descriptor' ) );
		$this->end_controls_section();
	}

	private function value( $settings, $key, $fallback = '' ) {
		return isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ? (string) $settings[ $key ] : $fallback;
	}

	private function ring_image( $product, $photo ) {
		$custom = isset( $product['ring_image'] ) && is_array( $product['ring_image'] ) ? $product['ring_image'] : array();
		if ( ! empty( $custom['url'] ) || ! empty( $custom['id'] ) ) { return $custom; }

		$directory = SAPONINE_STUDIO_URL . 'assets/images/';
		$attachment_id = absint( $photo['id'] ?? 0 );
		$stock = $attachment_id ? get_post_meta( $attachment_id, '_saponine_demo_asset', true ) : '';
		foreach ( array( 'bottle', 'serum', 'sachet' ) as $name ) {
			$filename = $name . '.jpg';
			if ( $stock === $filename || ( ! $attachment_id && ( $photo['url'] ?? '' ) === $directory . $filename ) ) {
				return array( 'url' => $directory . $name . '-cutout.webp', 'width' => 1024, 'height' => 1024 );
			}
		}
		return $photo;
	}

	private function image_markup( $image, $alt, $class, $loading = 'lazy' ) {
		if ( ! empty( $image['id'] ) && wp_attachment_is_image( absint( $image['id'] ) ) ) {
			return wp_get_attachment_image( absint( $image['id'] ), 'large', false, array( 'class' => $class, 'alt' => $alt, 'loading' => $loading, 'decoding' => 'async' ) );
		}
		$url = isset( $image['url'] ) ? esc_url( $image['url'] ) : '';
		if ( ! $url ) { return ''; }
		$size = ! empty( $image['width'] ) && ! empty( $image['height'] ) ? sprintf( ' width="%d" height="%d"', absint( $image['width'] ), absint( $image['height'] ) ) : '';
		return '<img class="' . esc_attr( $class ) . '" src="' . $url . '" alt="' . esc_attr( $alt ) . '"' . $size . ' loading="' . esc_attr( $loading ) . '" decoding="async">';
	}

	private function ring( $text ) {
		$id = wp_unique_id( 'sps-ring-' );
		?>
		<div class="sps-ring" aria-hidden="true"><svg viewBox="0 0 300 300" focusable="false"><defs><path id="<?php echo esc_attr( $id ); ?>" d="M150,150 m-118,0 a118,118 0 1,1 236,0 a118,118 0 1,1 -236,0"/></defs><circle cx="150" cy="150" r="118"/><circle cx="150" cy="150" r="104" opacity=".42"/><text><textPath href="#<?php echo esc_attr( $id ); ?>" startOffset="1%"><?php echo esc_html( $text ); ?></textPath></text></svg></div>
		<?php
	}

	private function features( $product, $settings, $modal = false ) {
		$features = array_filter( array_map( 'trim', preg_split( '/\R/u', $this->value( $product, 'features' ) ) ?: array() ) );
		if ( $features ) {
			echo '<ul class="sps-features' . ( $modal ? ' sps-modal-features' : '' ) . '">';
			foreach ( $features as $feature ) { echo '<li>' . esc_html( $feature ) . '</li>'; }
			echo '</ul>';
		}
		if ( '' !== $this->value( $product, 'usage' ) ) {
			echo '<div class="sps-usage"><span>' . esc_html( $this->value( $settings, 'usage_label' ) ) . '</span><b>' . esc_html( $this->value( $product, 'usage' ) ) . '</b></div>';
		}
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$products = isset( $s['products'] ) && is_array( $s['products'] ) ? $s['products'] : array();
		if ( ! $products ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) { echo '<p>' . esc_html__( 'از بخش «محصولات»، یک محصول اضافه کنید.', 'saponine-studio' ) . '</p>'; }
			return;
		}
		$id = wp_unique_id( 'sps-' );
		$config = array();
		foreach ( array( 'float_enabled', 'tilt_enabled', 'ring_spin', 'float_amplitude', 'float_duration', 'tilt_amount', 'ring_duration', 'transition_type', 'transition_duration', 'modal_animation', 'modal_duration', 'motion_easing', 'motion_mobile' ) as $key ) {
			$config[ $key ] = $s[ $key ] ?? '';
		}
		$modal_enabled = 'yes' === ( $s['enable_modal'] ?? '' );
		$show_ring = 'yes' === ( $s['show_ring'] ?? '' );
		$tag = in_array( $s['title_tag'] ?? '', array( 'h2', 'h3', 'h4', 'p' ), true ) ? $s['title_tag'] : 'h2';
		$direction = class_exists( '\Saponine\Studio\Multilingual' ) ? \Saponine\Studio\Multilingual::direction() : ( is_rtl() ? 'rtl' : 'ltr' );
		$tabs_direction = in_array( $s['tabs_direction'] ?? '', array( 'rtl', 'ltr' ), true ) ? $s['tabs_direction'] : $direction;
		?>
		<div class="sps-root" dir="<?php echo esc_attr( $direction ); ?>" data-sps-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>" aria-label="<?php echo esc_attr( $this->value( $s, 'section_label' ) ); ?>">
			<?php foreach ( $products as $i => $product ) :
				if ( empty( $product['_id'] ) ) { continue; }
				$anchor = 'sp-item-' . $this->get_id() . '-' . $product['_id'];
				?><span class="sps-jump" id="<?php echo esc_attr( $anchor ); ?>" data-sps-jump="<?php echo esc_attr( $i ); ?>" aria-hidden="true"></span><?php
			endforeach; ?>
			<?php if ( 'yes' === ( $s['show_tabs'] ?? '' ) && count( $products ) > 1 ) : ?>
			<div class="sps-tabs" role="tablist" aria-label="<?php echo esc_attr( $this->value( $s, 'section_label' ) ); ?>" dir="<?php echo esc_attr( $tabs_direction ); ?>">
				<?php foreach ( $products as $i => $product ) : ?>
				<button class="sps-tab" type="button" role="tab" id="<?php echo esc_attr( $id . '-tab-' . $i ); ?>" aria-controls="<?php echo esc_attr( $id . '-panel-' . $i ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>" data-sps-select="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $this->value( $product, 'en_title', $this->value( $product, 'title' ) ) ); ?></button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<div class="sps-panels">
				<?php foreach ( $products as $i => $product ) :
					$photo   = isset( $product['image'] ) && is_array( $product['image'] ) ? $product['image'] : array();
					$display = $this->ring_image( $product, $photo );
					$alt     = ! empty( $product['image_alt'] ) ? $product['image_alt'] : ( $product['title'] ?? '' );
				?>
				<article class="sps-panel" id="<?php echo esc_attr( $id . '-panel-' . $i ); ?>" data-sps-panel="<?php echo esc_attr( $i ); ?>" aria-label="<?php echo esc_attr( $this->value( $product, 'title' ) ); ?>" <?php echo 0 !== $i ? 'hidden' : ''; ?>>
					<?php if ( $photo !== $display ) : ?>
						<template data-sps-original-photo><?php echo $this->image_markup( $photo, $alt, 'sps-gallery-photo' ); ?></template>
					<?php endif; ?>
					<div class="sps-copy">
						<<?php echo esc_html( $tag ); ?> class="sps-title"><?php echo esc_html( $this->value( $product, 'title' ) ); ?></<?php echo esc_html( $tag ); ?>>
						<p class="sps-en" dir="auto"><?php echo esc_html( $this->value( $product, 'en_title' ) ); ?></p>
						<p class="sps-description"><?php echo esc_html( $this->value( $product, 'description' ) ); ?></p>
						<?php
						$row_id = $product['_id'] ?? '';
						$slug   = \Saponine\Studio\Portal::ROWS[ $row_id ] ?? sanitize_title( $row_id );
						if ( $slug && get_template() === 'saponine-flex' && isset( \Saponine\Studio\Portal::products()[ $slug ] ) ) : ?>
							<a class="sps-button sps-details" href="<?php echo esc_url( \Saponine\Studio\Portal::url( 'product', $slug ) ); ?>"><?php echo esc_html( $this->value( $s, 'details_label' ) ); ?></a>
						<?php elseif ( $modal_enabled ) : ?>
							<button type="button" class="sps-button sps-details" data-sps-open aria-haspopup="dialog"><?php echo esc_html( $this->value( $s, 'details_label' ) ); ?></button>
						<?php endif; ?>
						<?php $this->features( $product, $s ); ?>
					</div>
					<div class="sps-stage<?php echo $display !== $photo ? ' sps-cutout-stage' : ''; ?>">
						<?php if ( $show_ring ) { $this->ring( $this->value( $s, 'ring_text' ) ); } ?>
						<div class="sps-image-float"><div class="sps-image-tilt">
							<?php if ( $modal_enabled ) : ?><button type="button" class="sps-image-button" data-sps-open aria-haspopup="dialog" aria-label="<?php echo esc_attr( $this->value( $s, 'view_label' ) . ' ' . $this->value( $product, 'title' ) ); ?>"><?php else : ?><div class="sps-image-button"><?php endif; ?>
								<?php echo $this->image_markup( $display, $alt, 'sps-product-image' ); ?>
							<?php echo $modal_enabled ? '</button>' : '</div>'; ?>
						</div></div>
						<?php if ( 'yes' === ( $s['show_arrows'] ?? '' ) && count( $products ) > 1 ) : ?>
						<button type="button" class="sps-arrow sps-prev" data-sps-step="-1" aria-label="<?php echo esc_attr( $this->value( $s, 'previous_label' ) ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 4l-8 8 8 8"/></svg></button>
						<button type="button" class="sps-arrow sps-next" data-sps-step="1" aria-label="<?php echo esc_attr( $this->value( $s, 'next_label' ) ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4l8 8-8 8"/></svg></button>
						<?php endif; ?>
						<?php if ( $modal_enabled && 'yes' === ( $s['show_hint'] ?? '' ) ) : ?><span class="sps-hint" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4 4M11 8v6M8 11h6"/></svg><?php echo esc_html( $this->value( $s, 'view_label' ) ); ?></span><?php endif; ?>
					</div>
					<?php if ( $modal_enabled ) : ?>
					<template data-sps-modal-template>
						<div class="sps-modal-shell">
							<button type="button" class="sps-close" data-sps-close aria-label="<?php echo esc_attr( $this->value( $s, 'close_label' ) ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
							<div class="sps-modal-media<?php echo $display !== $photo ? ' sps-cutout-stage' : ''; ?>"><?php if ( $show_ring ) { $this->ring( $this->value( $s, 'ring_text' ) ); } ?><?php echo $this->image_markup( $display, $alt, 'sps-modal-image', 'eager' ); ?></div>
							<div class="sps-modal-copy">
								<p class="sps-modal-kicker" dir="auto"><?php echo esc_html( $this->value( $s, 'modal_kicker' ) ); ?></p>
								<h2 class="sps-modal-title" data-sps-modal-title><?php echo esc_html( $this->value( $product, 'title' ) ); ?></h2>
								<p class="sps-en" dir="auto"><?php echo esc_html( $this->value( $product, 'en_title' ) ); ?></p>
								<p class="sps-modal-description"><?php echo esc_html( ! empty( $product['detail_description'] ) ? $product['detail_description'] : $this->value( $product, 'description' ) ); ?></p>
								<?php $this->features( $product, $s, true ); ?>
								<div class="sps-modal-divider" aria-hidden="true"></div>
								<p class="sps-modal-note"><?php echo esc_html( ! empty( $product['modal_note'] ) ? $product['modal_note'] : $this->value( $s, 'modal_note' ) ); ?></p>
								<?php if ( ! empty( $product['cta_label'] ) && ! empty( $product['cta_link']['url'] ) ) :
									$link_key = 'sps_cta_' . $i;
									$cta_link = $product['cta_link'];
									if ( class_exists( '\Saponine\Studio\Multilingual' ) ) { $cta_link['url'] = \Saponine\Studio\Multilingual::translate_home_link( $cta_link['url'] ); }
									$this->add_link_attributes( $link_key, $cta_link );
									$this->add_render_attribute( $link_key, 'class', 'sps-button sps-modal-cta' );
									?>
									<a <?php $this->print_render_attribute_string( $link_key ); ?>><?php echo esc_html( $product['cta_label'] ); ?></a>
								<?php endif; ?>
							</div>
						</div>
					</template>
					<?php endif; ?>
				</article>
				<?php endforeach; ?>
			</div>
			<p class="sps-sr-only" data-sps-status role="status" aria-live="polite" aria-atomic="true"></p>
			<?php if ( $modal_enabled ) : ?><dialog class="sps-modal" aria-modal="true" tabindex="-1" aria-labelledby="<?php echo esc_attr( $id . '-modal-title' ); ?>" data-sps-dialog data-title-id="<?php echo esc_attr( $id . '-modal-title' ); ?>"></dialog><?php endif; ?>
		</div>
		<?php
	}
}
