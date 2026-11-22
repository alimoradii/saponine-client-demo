<?php
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	const MIN_ELEMENTOR = '3.28.0';
	private static $rendering = array();

	public static function boot() {
		Admin::boot();
		Slider_Admin::boot();
		Slider_Companions::boot();
		UI_Strings::boot();
		Layout::boot();
		Portal::boot();
		Company_Profile::boot();
		Service_Pages::boot();
		add_filter( 'elementor/frontend/builder_content_data', array( Brand_Strip::class, 'upgrade_stock_layout' ), 15, 2 );
		Section_Scroll::boot();
		Auto_Translate::boot();
		add_action( 'admin_init', array( __CLASS__, 'refresh_layout_assets' ), 30 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_base' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_mobile_layout' ), 45 );
		add_action( 'wp_footer', array( __CLASS__, 'mobile_footer_template' ), 12 );
		add_filter( 'plugin_action_links_' . plugin_basename( SAPONINE_STUDIO_PATH . 'saponine-studio.php' ), array( __CLASS__, 'action_links' ) );
		if ( ! self::elementor_ready() ) {
			add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
			return;
		}
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( __CLASS__, 'register_assets' ) );
		add_action( 'elementor/preview/enqueue_styles', array( __CLASS__, 'enqueue_base' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( __CLASS__, 'enqueue_measure_editor' ) );
		add_filter( 'elementor/fonts/additional_fonts', array( __CLASS__, 'local_fonts' ) );
		add_filter( 'elementor/frontend/print_google_fonts', array( __CLASS__, 'allow_google_fonts' ) );
		Motion::boot();
	}

	public static function elementor_ready() {
		return did_action( 'elementor/loaded' ) && defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, self::MIN_ELEMENTOR, '>=' );
	}

	public static function enqueue_measure_editor() {
		wp_enqueue_script( 'saponine-measure-editor', SAPONINE_STUDIO_URL . 'assets/js/measure-editor.js', array(), SAPONINE_STUDIO_VERSION, true );
	}

	public static function dependency_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'ساپونین استودیو به افزونهٔ رایگان Elementor نسخهٔ ۳٫۲۸ یا جدیدتر نیاز دارد. المنتور را نصب و فعال کنید؛ سپس از نمایش ← ساپونین استودیو، طرح را وارد کنید.', 'saponine-studio' ) . '</p></div>';
	}

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'themes.php?page=saponine-studio' ) ) . '">' . esc_html__( 'راه‌اندازی و ویرایش', 'saponine-studio' ) . '</a>' );
		return $links;
	}

	public static function register_category( $manager ) {
		$manager->add_category( 'saponine-studio', array( 'title' => __( 'ساپونین استودیو', 'saponine-studio' ), 'icon' => 'eicon-animation' ) );
	}

	public static function local_fonts( $fonts ) {
		$fonts['Vazirmatn'] = 'system';
		$fonts['Outfit'] = 'system';
		return $fonts;
	}

	public static function allow_google_fonts( $enabled ) {
		if ( 'saponine-flex' !== get_template() ) {
			return $enabled;
		}
		$s = self::settings();
		return $enabled && 'yes' === $s['allow_google_fonts'];
	}

	public static function register_widgets( $manager ) {
		$widgets = array(
			'class-product-showcase.php' => 'Product_Showcase',
			'class-hero-slider.php'      => 'Hero_Slider',
			'class-navigation.php'       => 'Navigation',
			'class-search.php'           => 'Search',
			'class-marquee.php'          => 'Marquee',
			'class-section-rail.php'     => 'Section_Rail',
			'class-language-switcher.php' => 'Language_Switcher',
		);
		foreach ( $widgets as $file => $class ) {
			$path = SAPONINE_STUDIO_PATH . 'widgets/' . $file;
			if ( ! is_readable( $path ) ) {
				continue;
			}
			require_once $path;
			$class = __NAMESPACE__ . '\\Widgets\\' . $class;
			if ( class_exists( $class ) ) {
				$manager->register( new $class() );
			}
		}
	}

	public static function register_assets() {
		$styles = array(
			'saponine-studio-base'     => 'assets/css/site.css',
			'saponine-responsive'     => 'assets/css/responsive.css',
			'saponine-compact-header' => 'assets/css/compact-header.css',
			'saponine-front-layout'   => 'assets/css/front-layout.css',
			'saponine-sections'       => 'assets/css/reference-sections.css',
			'saponine-motion'          => 'assets/css/motion.css',
			'saponine-product-showcase' => 'assets/css/product-showcase.css',
			'saponine-support-widgets' => 'assets/css/support-widgets.css',
		);
		foreach ( $styles as $handle => $file ) {
			if ( ! wp_style_is( $handle, 'registered' ) && is_readable( SAPONINE_STUDIO_PATH . $file ) ) {
				wp_register_style( $handle, SAPONINE_STUDIO_URL . $file, array(), SAPONINE_STUDIO_VERSION );
			}
		}
		$scripts = array(
			'saponine-motion'           => 'assets/js/motion.js',
			'saponine-product-showcase' => 'assets/js/product-showcase.js',
			'saponine-support-widgets'  => 'assets/js/support-widgets.js',
		);
		foreach ( $scripts as $handle => $file ) {
			if ( ! wp_script_is( $handle, 'registered' ) && is_readable( SAPONINE_STUDIO_PATH . $file ) ) {
				wp_register_script( $handle, SAPONINE_STUDIO_URL . $file, array( 'jquery', 'elementor-frontend' ), SAPONINE_STUDIO_VERSION, true );
			}
		}
	}

	public static function defaults() {
		return array(
			'header_id' => 0, 'footer_id' => 0,
			'primary' => '#7d4697', 'secondary' => '#f1e9f5',
			'background' => '#fbf9fc', 'text' => '#3c3c3a', 'muted' => '#777078',
			'border' => '#e7ddeb', 'accent' => '#e5afd1',
			'font_body' => 'Vazirmatn', 'font_latin' => 'Outfit', 'allow_google_fonts' => 'no',
			'section_scroll_enabled' => 'yes', 'section_scroll_duration' => 650,
			'section_scroll_threshold' => 36, 'section_scroll_mobile' => 'native',
		);
	}

	public static function settings() {
		return wp_parse_args( get_option( 'saponine_studio_settings', array() ), self::defaults() );
	}

	public static function enqueue_base() {
		self::register_assets();
		wp_enqueue_style( 'saponine-studio-base' );
		wp_enqueue_style( 'saponine-responsive' );
		wp_enqueue_style( 'saponine-compact-header' );
		wp_enqueue_style( 'saponine-front-layout' );
		wp_enqueue_style( 'saponine-sections' );
		wp_enqueue_script( 'saponine-sections', SAPONINE_STUDIO_URL . 'assets/js/reference-sections.js', array(), SAPONINE_STUDIO_VERSION, true );
		wp_enqueue_script( 'saponine-responsive', SAPONINE_STUDIO_URL . 'assets/js/responsive.js', array(), SAPONINE_STUDIO_VERSION, true );
		if ( ! wp_style_is( 'saponine-studio-base', 'registered' ) ) {
			return;
		}
		$s = self::settings();
		$css = ':root{';
		foreach ( array( 'primary' => 'primary', 'secondary' => 'secondary', 'background' => 'bg', 'text' => 'text', 'muted' => 'muted', 'border' => 'border', 'accent' => 'accent' ) as $key => $name ) {
			$color = sanitize_hex_color( $s[ $key ] );
			$css .= '--sn-' . $name . ':' . ( $color ? $color : self::defaults()[ $key ] ) . ';';
		}
		$body = in_array( $s['font_body'], array( 'Vazirmatn', 'Tahoma', 'Arial' ), true ) ? $s['font_body'] : 'Vazirmatn';
		$latin = in_array( $s['font_latin'], array( 'Outfit', 'Arial', 'Georgia' ), true ) ? $s['font_latin'] : 'Outfit';
		$css .= '--sn-font-body:"' . $body . '",Tahoma,sans-serif;--sn-font-latin:"' . $latin . '",Arial,sans-serif;}';
		wp_add_inline_style( 'saponine-studio-base', $css );
		if ( self::elementor_ready() ) {
			wp_enqueue_style( 'saponine-motion' );
			wp_enqueue_script( 'saponine-motion' );
		}
	}

	public static function enqueue_mobile_layout() {
		if ( 'saponine-flex' !== get_template() || isset( $_GET['elementor-preview'] ) ) { return; }
		$url = SAPONINE_STUDIO_URL . 'assets/';
		wp_enqueue_style( 'saponine-mobile-chrome', $url . 'css/mobile-chrome.css', array( 'saponine-portal' ), SAPONINE_STUDIO_VERSION );
		if ( ! is_front_page() || Portal::view() ) { return; }
		wp_enqueue_style( 'saponine-mobile-story', $url . 'css/mobile-story.css', array( 'saponine-sections' ), SAPONINE_STUDIO_VERSION );
		wp_enqueue_script( 'saponine-mobile-story', $url . 'js/mobile-story.js', array( 'saponine-sections' ), SAPONINE_STUDIO_VERSION, true );
		wp_enqueue_style( 'saponine-mobile-home', $url . 'css/mobile-home.css', array( 'saponine-sections', 'saponine-product-showcase' ), SAPONINE_STUDIO_VERSION );
		wp_enqueue_script( 'saponine-mobile-footer', $url . 'js/mobile-footer.js', array(), SAPONINE_STUDIO_VERSION, true );
	}

	public static function mobile_footer_template() {
		if ( 'saponine-flex' === get_template() && is_front_page() && ! Portal::view() && ! isset( $_GET['elementor-preview'] ) ) {
			include SAPONINE_STUDIO_PATH . 'templates/mobile-footer.php';
		}
	}

	/** Refresh generated styles and Elementor element markup once after an update. */
	public static function refresh_layout_assets() {
		if ( ! current_user_can( 'manage_options' ) || ! self::elementor_ready() || SAPONINE_STUDIO_VERSION === get_option( 'saponine_layout_assets_version' ) ) { return; }
		$manager = \Elementor\Plugin::instance()->files_manager;
		if ( ! $manager || ! is_callable( array( $manager, 'clear_cache' ) ) ) { return; }
		$manager->clear_cache();
		update_option( 'saponine_layout_assets_version', SAPONINE_STUDIO_VERSION, false );
	}

	/** Render a Free-compatible Elementor section selected by the site administrator. */
	public static function render_location( $location ) {
		if ( ! in_array( $location, array( 'header', 'footer' ), true ) || ! self::elementor_ready() ) {
			return false;
		}
		// Section documents need a clean editing canvas without duplicate site chrome.
		if ( 'elementor_library' === get_post_type( get_queried_object_id() ) ) {
			return true;
		}
		$s = self::settings();
		$id = Multilingual::translated_post_id( absint( $s[ $location . '_id' ] ) );
		if ( ! self::valid_section( $id ) || isset( self::$rendering[ $id ] ) || $id === get_queried_object_id() ) {
			return false;
		}
		self::$rendering[ $id ] = true;
		try {
			$html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $id, true );
		} finally {
			unset( self::$rendering[ $id ] );
		}
		if ( '' === trim( (string) $html ) ) {
			return false;
		}
		$tag = 'header' === $location ? 'header' : 'footer';
		echo '<' . $tag . ' class="sn-theme-' . esc_attr( $location ) . '">';
		// Elementor renders authorized, stored document data with its own renderer.
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</' . $tag . '>';
		return true;
	}

	public static function valid_section( $id ) {
		return $id > 0 && 'elementor_library' === get_post_type( $id ) && 'publish' === get_post_status( $id ) && 'section' === get_post_meta( $id, '_elementor_template_type', true );
	}
}
