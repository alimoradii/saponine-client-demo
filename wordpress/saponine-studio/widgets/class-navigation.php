<?php
/** Free Elementor navigation with editable links. */
namespace Saponine\Studio\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Navigation extends Widget_Base {
	public function get_name() { return 'saponine-navigation'; }
	public function get_title() { return esc_html__( 'فهرست ساپونین', 'saponine-studio' ); }
	public function get_icon() { return 'eicon-nav-menu'; }
	public function get_categories() { return array( 'saponine-studio' ); }
	public function get_keywords() { return array( 'menu', 'navigation', 'فهرست', 'منو' ); }
	public function get_style_depends() { return array( 'saponine-support-widgets' ); }
	public function get_script_depends() { return array( 'saponine-support-widgets' ); }

	protected function register_controls() {
		$this->start_controls_section( 'menu_section', array( 'label' => 'پیوندها' ) );
		$r = new Repeater();
		$r->add_control( 'label', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'پیوند', 'label_block' => true ) );
		$r->add_control( 'link', array( 'label' => 'پیوند', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#sec-2' ) ) );
		$this->add_control( 'items', array( 'label' => 'آیتم‌ها؛ برای مرتب‌سازی بکشید', 'type' => Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'title_field' => '{{{ label }}}', 'prevent_empty' => false, 'default' => array(
			array( 'label' => 'برندهای ساپونین', 'link' => array( 'url' => '#sec-2' ) ),
			array( 'label' => 'محصولات', 'link' => array( 'url' => '#sec-4' ) ),
			array( 'label' => 'درباره ما', 'link' => array( 'url' => '#sec-3' ) ),
			array( 'label' => 'تماس با ما', 'link' => array( 'url' => '#sec-6' ) ),
		) ) );
		$this->add_control( 'menu_label', array( 'label' => 'نام دسترس‌پذیر فهرست', 'type' => Controls_Manager::TEXT, 'default' => 'فهرست اصلی' ) );
		$this->add_control( 'toggle_label', array( 'label' => 'نوشته دکمه موبایل', 'type' => Controls_Manager::TEXT, 'default' => 'فهرست' ) );
		$this->add_control( 'breakpoint', array( 'label' => 'نمایش دکمه جمع‌شونده', 'type' => Controls_Manager::SELECT, 'default' => 'tablet', 'options' => array( 'tablet' => 'تبلت و موبایل (۱۰۲۴ پیکسل)', 'mobile' => 'فقط موبایل (۷۶۷ پیکسل)', 'none' => 'همیشه فهرست باز' ) ) );
		$this->end_controls_section();
		$this->start_controls_section( 'menu_style', array( 'label' => 'ظاهر فهرست', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typography', 'selector' => '{{WRAPPER}} .sp-nav' ) );
		$this->add_responsive_control( 'gap', array( 'label' => 'فاصله آیتم‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 100 ) ), 'selectors' => array( '{{WRAPPER}} .sp-nav__items' => '--sp-nav-items-gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'alignment', array( 'label' => 'تراز افقی', 'type' => Controls_Manager::SELECT, 'options' => array( 'flex-start' => 'ابتدا', 'center' => 'وسط', 'flex-end' => 'انتها', 'space-between' => 'فاصله برابر' ), 'selectors' => array( '{{WRAPPER}} .sp-nav__items' => '--sp-nav-alignment: {{VALUE}};' ) ) );
		foreach ( array( 'text_color' => array( 'رنگ متن', '.sp-nav', 'color' ), 'hover_color' => array( 'رنگ هاور و فعال', '.sp-nav', '--sp-nav-active' ), 'mobile_background' => array( 'زمینه فهرست موبایل', '.sp-nav', '--sp-nav-mobile-bg' ), 'toggle_color' => array( 'رنگ دکمه موبایل', '.sp-nav__toggle', 'color' ), 'toggle_background' => array( 'زمینه دکمه موبایل', '.sp-nav__toggle', 'background' ) ) as $name => $c ) {
			$this->add_control( $name, array( 'label' => $c[0], 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} ' . $c[1] => $c[2] . ': {{VALUE}};' ) ) );
		}
		$this->add_responsive_control( 'item_padding', array( 'label' => 'فاصله داخلی پیوند', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .sp-nav__link' => '--sp-nav-item-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'dropdown_radius', array( 'label' => 'گردی فهرست موبایل', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 100 ) ), 'selectors' => array( '{{WRAPPER}} .sp-nav' => '--sp-nav-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
		if ( ! $items ) { return; }
		$breakpoint = in_array( $s['breakpoint'], array( 'tablet', 'mobile', 'none' ), true ) ? $s['breakpoint'] : 'tablet';
		$id = 'sp-menu-' . $this->get_id();
		?>
		<nav class="sp-nav sp-nav--<?php echo esc_attr( $breakpoint ); ?>" data-sp-nav aria-label="<?php echo esc_attr( $s['menu_label'] ); ?>">
			<button type="button" class="sp-nav__toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $id ); ?>"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg><span><?php echo esc_html( $s['toggle_label'] ); ?></span></button>
			<ul id="<?php echo esc_attr( $id ); ?>" class="sp-nav__items">
			<?php foreach ( $items as $item ) :
				$url = isset( $item['link']['url'] ) ? $item['link']['url'] : '';
				if ( class_exists( '\Saponine\Studio\Multilingual' ) ) { $url = \Saponine\Studio\Multilingual::translate_home_link( $url ); }
				$label = isset( $item['label'] ) ? $item['label'] : '';
				if ( ! $label ) { continue; }
				$external = ! empty( $item['link']['is_external'] );
				$rel = $external ? array( 'noopener', 'noreferrer' ) : array();
				if ( ! empty( $item['link']['nofollow'] ) ) { $rel[] = 'nofollow'; }
				?>
				<?php $menu = '';
				if ( '4939506' === $this->get_id() ) {
					if ( 'sec-2' === wp_parse_url( $url, PHP_URL_FRAGMENT ) ) { $menu = 'brands'; }
					elseif ( $url === \Saponine\Studio\Portal::url( 'about' ) ) { $menu = 'company'; }
				} ?>
				<li<?php if ( $menu ) { echo ' class="sn-nav-branch"'; } ?>><?php if ( $url ) : ?><a class="sp-nav__link" href="<?php echo esc_url( $url ); ?>"<?php echo $external ? ' target="_blank"' : ''; ?><?php echo $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : ''; ?>><?php echo esc_html( $label ); ?></a><?php else : ?><span class="sp-nav__link"><?php echo esc_html( $label ); ?></span><?php endif; ?>
				<?php if ( $menu ) : ?><button class="sn-branch-toggle" type="button" aria-label="<?php echo esc_attr( 'زیرمنوی ' . $label ); ?>" aria-expanded="false"><svg viewBox="0 0 16 16" aria-hidden="true" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.6"><path d="m3 6 5 5 5-5"/></svg></button><?php \Saponine\Studio\Portal::submenu( $menu ); endif; ?></li>
			<?php endforeach; ?>
			<?php if ( '4939506' === $this->get_id() ) : ?>
				<li class="sn-mobile-menu-tool"><button class="sp-nav__link" type="button" data-sn-search-open aria-haspopup="dialog" aria-controls="sn-finder"><?php esc_html_e( 'جستجو', 'saponine-studio' ); ?></button></li>
				<li class="sn-mobile-menu-tool"><a class="sp-nav__link" href="<?php echo esc_url( \Saponine\Studio\Portal::url( 'pharmacies' ) ); ?>"><?php esc_html_e( 'داروخانه‌ها', 'saponine-studio' ); ?></a></li>
			<?php endif; ?>
			</ul>
		</nav>
		<?php
	}
}
