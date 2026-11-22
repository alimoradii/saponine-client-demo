<?php
namespace Saponine\Studio;

defined( 'ABSPATH' ) || exit;

/** Public company pages and their shared catalogue, sourced from the current Elementor import. */
final class Portal {
	const TITLES = array( 'products' => 'آرشیو محصولات', 'brand' => 'برند', 'product' => 'محصول', 'about' => 'درباره ساپونین', 'pharmacies' => 'داروخانه‌ها', 'faq' => 'پرسش‌های متداول', 'contact' => 'تماس با ما', 'careers' => 'همکاری با ما' );
	const ROWS = array( '0a45269' => 'balance', '67e0726' => 'botanical-serum', 'f59497b' => 'effervescent-sachet' );
	const CARDS = array( '6fcb733' => 'balance', '2ba8293' => 'botanical-serum', '5311b48' => 'effervescent-sachet', 'af84950' => 'radiance' );
	private static $nodes;
	private static $products;
	private static $brands;

	public static function boot() {
		add_filter( 'query_vars', static function( $vars ) { return array_merge( $vars, array( 'saponine_view', 'saponine_item' ) ); } );
		add_filter( 'template_include', array( __CLASS__, 'template' ), 99 );
		add_filter( 'pre_get_document_title', array( __CLASS__, 'title' ), 20 );
		add_filter( 'body_class', static function( $classes ) { if ( self::view() ) { $classes[] = 'sn-interior'; } return $classes; } );
		add_filter( 'elementor/frontend/builder_content_data', array( __CLASS__, 'link_documents' ), 30, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 35 );
		add_action( 'wp_footer', array( __CLASS__, 'search_dialog' ), 8 );
		add_action( 'wp_footer', array( __CLASS__, 'mobile_dock' ), 9 );
	}

	public static function url( $section = '', $item = '' ) {
		$args = array();
		if ( isset( self::TITLES[ $section ] ) ) { $args['saponine_view'] = $section; }
		if ( $item && $args ) { $args['saponine_item'] = sanitize_title( $item ); }
		$home = Multilingual::home_url();
		return $args ? add_query_arg( $args, $home ) : $home;
	}
	public static function view() {
		if ( get_template() !== 'saponine-flex' || is_admin() ) { return ''; }
		$value = get_query_var( 'saponine_view', '' );
		return is_string( $value ) ? sanitize_key( $value ) : '';
	}
	public static function item() {
		$value = get_query_var( 'saponine_item', '' );
		return is_string( $value ) ? sanitize_title( $value ) : '';
	}
	private static function page_title() {
		$view = self::view();
		if ( 'brand' === $view ) { return self::brands()[ self::item() ]['title'] ?? ''; }
		if ( 'product' === $view ) { return self::products()[ self::item() ]['title'] ?? ''; }
		return self::TITLES[ $view ] ?? '';
	}
	public static function title( $title ) {
		$page = self::page_title();
		return $page ? $page . ' | ' . get_bloginfo( 'name' ) : $title;
	}
	public static function template( $template ) {
		$view = self::view();
		if ( ! $view || is_feed() || is_preview() || isset( $_GET['elementor-preview'] ) ) { return $template; }
		if ( ! self::page_title() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return get_404_template() ?: $template;
		}
		remove_action( 'wp_head', 'rel_canonical' );
		add_action( 'wp_head', static function() { echo '<link rel="canonical" href="' . esc_url( self::url( self::view(), self::item() ) ) . '">' . "\n"; } );
		return SAPONINE_STUDIO_PATH . 'templates/company-page.php';
	}
	public static function assets() {
		if ( get_template() !== 'saponine-flex' ) { return; }
		wp_enqueue_style( 'saponine-portal', SAPONINE_STUDIO_URL . 'assets/css/portal.css', array( 'saponine-compact-header' ), SAPONINE_STUDIO_VERSION );
		wp_enqueue_script( 'saponine-portal', SAPONINE_STUDIO_URL . 'assets/js/portal.js', array(), SAPONINE_STUDIO_VERSION, true );
		$view = self::view();
		$group = in_array( $view, array( 'product', 'products', 'brand' ), true ) ? 'catalog-pages' : ( in_array( $view, array( 'pharmacies', 'faq', 'careers', 'contact' ), true ) ? 'service-pages' : '' );
		if ( $group ) {
			wp_enqueue_style( 'saponine-' . $group, SAPONINE_STUDIO_URL . 'assets/css/' . $group . '.css', array( 'saponine-portal' ), SAPONINE_STUDIO_VERSION );
			wp_enqueue_script( 'saponine-' . $group, SAPONINE_STUDIO_URL . 'assets/js/' . $group . '.js', array(), SAPONINE_STUDIO_VERSION, true );
		}
	}
	private static function home_nodes() {
		if ( null !== self::$nodes ) { return self::$nodes; }
		$docs = Multilingual::source_documents();
		$id = Multilingual::translated_post_id( $docs['home'] );
		$stored = $id ? get_post_meta( $id, '_elementor_data', true ) : '';
		$nodes = is_string( $stored ) ? json_decode( $stored, true ) : $stored;
		if ( ! is_array( $nodes ) ) {
			$demo = json_decode( file_get_contents( SAPONINE_STUDIO_PATH . 'demo/home.json' ), true );
			$nodes = $demo['content'] ?? array();
		}
		self::$nodes = array();
		$visit = static function( $items ) use ( &$visit ) {
			foreach ( $items as $node ) {
				self::$nodes[] = $node;
				if ( ! empty( $node['elements'] ) ) { $visit( $node['elements'] ); }
			}
		};
		$visit( $nodes );
		return self::$nodes;
	}
	public static function image( $media ) {
		$url = (string) ( $media['url'] ?? '' );
		if ( preg_match( '/^\{\{asset:([a-z0-9._-]+)\}\}$/i', $url, $match ) ) { return SAPONINE_STUDIO_URL . 'assets/images/' . $match[1]; }
		return esc_url_raw( $url );
	}
	public static function products() {
		if ( null !== self::$products ) { return self::$products; }
		self::$products = array();
		$categories = array( 'balance' => array( 'supplement', 'مکمل‌ها' ), 'botanical-serum' => array( 'skin', 'مراقبت پوست' ), 'effervescent-sachet' => array( 'wellness', 'سلامت روزانه' ) );
		$overrides = get_option( 'saponine_catalog_content', array() );
		foreach ( self::home_nodes() as $node ) {
			if ( 'saponine-product-showcase' !== ( $node['widgetType'] ?? '' ) ) { continue; }
			foreach ( $node['settings']['products'] ?? array() as $r ) {
				$key = (string) ( $r['_id'] ?? '' );
				if ( '' === $key || empty( $r['title'] ) ) { continue; }
				$slug = self::ROWS[ $key ] ?? sanitize_title( $key );
				if ( isset( self::$products[ $slug ] ) ) { continue; }
				$extra = $overrides['products'][ $slug ] ?? array();
				$category = $categories[ $slug ] ?? array( 'other', 'سایر محصولات' );
				$r['slug'] = $slug;
				$r['brand'] = $extra['brand'] ?? $slug;
				$r['category_label'] = ! empty( $extra['category_label'] ) ? $extra['category_label'] : $category[1];
				$r['category'] = $r['category_label'] === $category[1] ? $category[0] : sanitize_title( $r['category_label'] );
				$r['directions'] = $extra['directions'] ?? '';
				$r['contraindications'] = $extra['contraindications'] ?? '';
				$r['image'] = array( 'url' => self::image( $r['image'] ?? array() ), 'id' => absint( $r['image']['id'] ?? 0 ) );
				self::$products[ $slug ] = $r;
			}
		}
		return self::$products;
	}
	public static function brands() {
		if ( null !== self::$brands ) { return self::$brands; }
		self::$brands = array();
		$products = self::products();
		$overrides = get_option( 'saponine_catalog_content', array() );
		foreach ( self::home_nodes() as $node ) {
			if ( ! in_array( 'sn-brand-card', explode( ' ', $node['settings']['css_classes'] ?? '' ), true ) ) { continue; }
			$slug = self::CARDS[ $node['id'] ] ?? sanitize_title( $node['id'] );
			$brand = array( 'slug' => $slug, 'title' => '', 'en_title' => '', 'image' => '', 'description' => '', 'features' => '', 'banner_image' => '' );
			foreach ( $node['elements'] as $child ) {
				if ( 'heading' === ( $child['widgetType'] ?? '' ) && ! $brand['title'] ) { $brand['title'] = wp_strip_all_tags( $child['settings']['title'] ?? '' ); }
				if ( 'text-editor' === ( $child['widgetType'] ?? '' ) ) { $brand['en_title'] = wp_strip_all_tags( $child['settings']['editor'] ?? '' ); }
			}
			if ( ! $brand['title'] ) { continue; }
			$brand['image'] = $products[ $slug ]['image']['url'] ?? SAPONINE_STUDIO_URL . 'assets/images/hero.jpg';
			$brand['description'] = $products[ $slug ]['description'] ?? 'رادیانس یکی از نام‌های مجموعهٔ ساپونین است.';
			$brand['features'] = $products[ $slug ]['features'] ?? '';
			$extra = $overrides['brands'][ $slug ] ?? array();
			foreach ( array( 'description', 'features', 'banner_image' ) as $field ) {
				if ( ! empty( $extra[ $field ] ) ) { $brand[ $field ] = $extra[ $field ]; }
			}
			self::$brands[ $slug ] = $brand;
		}
		return self::$brands;
	}
	/** Rewire only the bundled placeholder anchors. Keep the owner's external and custom destinations. */
	public static function link_documents( $nodes, $post_id ) {
		if ( ! is_array( $nodes ) || get_template() !== 'saponine-flex' ) { return $nodes; }
		$routes = array(
			'44a768a' => array( 'sec-5','pharmacies' ), 'c738288' => array( 'sec-4','products' ), 'e97f373' => array( 'sec-3','about' ), 'ab71584' => array( 'sec-6','contact' ), 'a8c3641' => array( 'sec-6','pharmacies' ),
			'1d391c0' => array( 'sec-3','about' ), 'd8a928a' => array( 'sec-6','contact' ), '59191db' => array( 'sec-5','pharmacies' ),
			'368f5f9' => array( 'sp-item-7bc932b-0a45269','brand','balance' ), '37794f6' => array( 'sp-item-7bc932b-67e0726','brand','botanical-serum' ), 'c4a4231' => array( 'sp-item-7bc932b-f59497b','brand','effervescent-sachet' ), '7d9cd34' => array( 'sp-brand-radiance','brand','radiance' ), '339fee3' => array( 'sec-4','products' ),
			'99b7fea' => array( 'sec-4','brand','balance' ), '9a83f14' => array( 'sec-4','brand','botanical-serum' ), '8cb5109' => array( 'sec-4','brand','effervescent-sachet' ), 'bec6327' => array( 'sec-4','brand','radiance' ),
			'0ec3f0c' => array( 'sec-4','products' ), 'bb586b2' => array( 'sec-4','products' ), '22a0210' => array( 'sec-4','products' ),
		);
		$docs = Multilingual::source_documents();
		$home_id = Multilingual::translated_post_id( $docs['home'] );
		$allowed = array( '', untrailingslashit( Multilingual::home_url() ), untrailingslashit( home_url( '/' ) ) );
		if ( $home_id ) { $allowed[] = untrailingslashit( get_permalink( $home_id ) ); $allowed[] = add_query_arg( 'page_id', $home_id, home_url( '/' ) ); }
		$rewrite = static function( &$link, $old, $view, $slug = '' ) use ( $allowed ) {
			$href = $link['url'] ?? '';
			$parts = explode( '#', $href, 2 );
			if ( ( $parts[1] ?? '' ) === $old && in_array( untrailingslashit( $parts[0] ), $allowed, true ) ) { $link['url'] = self::url( $view, $slug ); }
		};
		$visit = static function( &$items, $card = '' ) use ( &$visit, $rewrite, $routes ) {
			foreach ( $items as &$node ) {
				$id = $node['id'] ?? '';
				$active_card = self::CARDS[ $id ] ?? $card;
				$s = &$node['settings'];
				if ( isset( $routes[ $id ], $s['link'] ) ) { $rewrite( $s['link'], $routes[ $id ][0], $routes[ $id ][1], $routes[ $id ][2] ?? '' ); }
				if ( $active_card && isset( $s['link'] ) ) { $rewrite( $s['link'], 'sec-4', 'brand', $active_card ); }
				if ( '4939506' === $id && isset( $s['items'] ) ) {
					foreach ( $s['items'] as &$row ) {
						foreach ( array( 'sec-4'=>'products', 'sec-3'=>'about', 'sec-6'=>'contact' ) as $anchor=>$view ) { $rewrite( $row['link'], $anchor, $view ); }
					}
					unset( $row );
					if ( in_array( self::view(), array( 'brand','product' ), true ) ) {
						$slug = 'brand' === self::view() ? self::item() : ( self::products()[ self::item() ]['brand'] ?? '' );
						$brand = self::brands()[ $slug ] ?? array();
						if ( $brand ) {
							$sections = array( 'brand-overview'=>'معرفی برند', 'brand-features'=>'ویژگی‌ها', 'brand-products'=>'محصولات برند' );
							if ( empty( $brand['features'] ) ) { unset( $sections['brand-features'] ); }
							$brand_menu = array();
							foreach ( $sections as $anchor => $label ) { $brand_menu[] = array( 'label'=>$label, 'link'=>array( 'url'=>self::url( 'brand', $slug ) . '#' . $anchor ) ); }
							$brand_menu[] = array( 'label'=>'همه محصولات', 'link'=>array( 'url'=>self::url( 'products' ) ) );
							$s['items'] = $brand_menu;
						}
					}
				}
				if ( ! empty( $node['elements'] ) ) { $visit( $node['elements'], $active_card ); }
				unset( $s );
			}
			unset( $node );
		};
		$visit( $nodes );
		return $nodes;
	}
	public static function render() {
		$view = self::view();
		if ( in_array( $view, array( 'products','product','brand' ), true ) ) { Catalog_Pages::render( $view, array( 'item' => self::item() ) ); }
		elseif ( 'about' === $view ) { Company_Profile::render(); }
		else { Service_Pages::render( $view, array( 'item' => self::item() ) ); }
	}
	public static function utility_nav() {
		echo '<nav class="sn-utility-nav" aria-label="دسترسی به صفحات شرکت">';
		foreach ( array( 'about', 'contact', 'faq', 'careers', 'pharmacies' ) as $view ) { echo '<a href="' . esc_url( self::url( $view ) ) . '">' . esc_html( self::TITLES[ $view ] ) . '</a>'; }
		echo '</nav>';
	}
	public static function search_dialog() {
		if ( get_template() !== 'saponine-flex' || isset( $_GET['elementor-preview'] ) ) { return; }
		$results = array();
		foreach ( self::products() as $p ) { $results[] = array( $p['title'], $p['en_title'] ?? '', self::url( 'product', $p['slug'] ), 'محصول' ); }
		foreach ( self::brands() as $b ) { $results[] = array( $b['title'], $b['en_title'], self::url( 'brand', $b['slug'] ), 'برند' ); }
		foreach ( self::TITLES as $key => $label ) { if ( ! in_array( $key, array( 'brand','product' ), true ) ) { $results[] = array( $label, '', self::url( $key ), 'صفحه' ); } }
		require SAPONINE_STUDIO_PATH . 'templates/search-overlay.php';
	}

	public static function dock_defaults() {
		return array(
			'products'   => array( __( 'محصولات', 'saponine-studio' ), self::url( 'products' ) ),
			'brands'     => array( __( 'برندها', 'saponine-studio' ), '#sec-2' ),
			'home'       => array( __( 'صفحه اصلی', 'saponine-studio' ), '#sec-0' ),
			'search'     => array( __( 'جستجو', 'saponine-studio' ), '' ),
			'pharmacies' => array( __( 'داروخانه‌ها', 'saponine-studio' ), self::url( 'pharmacies' ) ),
		);
	}

	public static function mobile_dock() {
		if ( 'saponine-flex' !== get_template() || self::view() || ! is_front_page() || isset( $_GET['elementor-preview'] ) ) { return; }
		$actions = Slider_Companions::mobile_rows( get_queried_object_id() );
		$actions = array_filter( $actions, static function ( $row ) { return empty( $row['hidden'] ); } );
		if ( ! $actions ) { return; }
		?>
		<nav class="sn-mobile-dock" style="--sn-dock-columns:<?php echo count( $actions ); ?>" aria-label="<?php esc_attr_e( 'دسترسی سریع', 'saponine-studio' ); ?>">
		<?php foreach ( $actions as $key => $action ) : ?>
			<?php $search = 'search' === $key && '' === $action['url']; ?>
			<?php if ( $search ) : ?>
				<button type="button" data-sn-mobile-action="search" data-sn-search-open aria-haspopup="dialog" aria-controls="sn-finder">
			<?php else : ?>
				<a href="<?php echo esc_url( $action['url'] ); ?>" data-sn-mobile-action="<?php echo esc_attr( $key ); ?>">
			<?php endif; ?>
				<img alt="" width="22" height="22" src="<?php echo esc_url( $action['icon'] ); ?>">
				<span><?php echo esc_html( $action['label'] ); ?></span>
			<?php echo $search ? '</button>' : '</a>'; ?>
		<?php endforeach; ?>
		</nav>
		<?php
	}

	public static function submenu( $type ) {
		if ( ! $type ) { return; }
		echo '<div class="sn-mega" hidden>';
		if ( 'brands' === $type ) {
			echo '<div class="sn-mega-brands">';
			foreach ( self::brands() as $b ) { echo '<a href="' . esc_url( self::url( 'brand', $b['slug'] ) ) . '"><img src="' . esc_url( $b['image'] ) . '" alt=""><span>' . esc_html( $b['title'] ) . '<small dir="ltr">' . esc_html( $b['en_title'] ) . '</small></span></a>'; }
			echo '</div>';
		} else {
			foreach ( array( 'about','careers','faq','contact' ) as $key ) { echo '<a href="' . esc_url( self::url( $key ) ) . '">' . esc_html( self::TITLES[ $key ] ) . '</a>'; }
		}
		echo '</div>';
	}
}
