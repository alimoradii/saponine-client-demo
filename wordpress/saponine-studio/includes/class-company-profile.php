<?php
namespace Saponine\Studio;

defined( 'ABSPATH' ) || exit;

final class Company_Profile {
	const PROFILE = 'saponine_company_profile';
	const CATALOG = 'saponine_catalog_content';
	private const FIELDS = array(
		'intro' => 'معرفی شرکت', 'mission' => 'مأموریت', 'vision' => 'چشم‌انداز', 'values' => 'ارزش‌های کلیدی',
		'story' => 'داستان برند', 'portfolio' => 'معرفی خانوادهٔ برندها', 'hero_image' => 'نشانی عکس سربرگ دربارهٔ ما',
		'pharmacy_image' => 'نشانی عکس داروخانه و تیم', 'story_image' => 'نشانی عکس داستان برند', 'logo_image' => 'نشانی لوگوی شرکت در صفحات داخلی',
	);
	public static function boot() {
		add_action( 'admin_menu', static function() {
			add_theme_page( 'معرفی و برندهای ساپونین', 'معرفی و برندهای ساپونین', 'manage_options', 'saponine-company-profile', array( __CLASS__, 'settings_page' ) );
		} );
		add_action( 'admin_init', static function() {
			register_setting( 'sn_company_content', self::PROFILE, array( 'sanitize_callback' => array( __CLASS__, 'clean_profile' ) ) );
			register_setting( 'sn_company_content', self::CATALOG, array( 'sanitize_callback' => array( __CLASS__, 'clean_catalog' ) ) );
		} );
	}
	public static function content() {
		$image = SAPONINE_STUDIO_URL . 'assets/images/';
		return wp_parse_args( get_option( self::PROFILE, array() ), array(
			'intro' => 'ساپونین یک برند داروسازی است که مکمل‌های گیاهی و محصولات مراقبت از پوست را در یک مجموعه گرد هم آورده است.',
			'mission' => 'اطلاعات هر محصول را روشن و در دسترس می‌گذاریم تا انتخاب آن در کنار راهنمایی داروساز آسان‌تر شود.',
			'vision' => 'می‌خواهیم بین شناخت ترکیبات گیاهی و نیازهای روزمرهٔ مراقبت از سلامت، ارتباطی روشن ایجاد کنیم.',
			'values' => 'شفافیت در معرفی محصول، توجه به کیفیت و احترام به انتخاب مصرف‌کننده.',
			'story' => "نام ساپونین از دنیای ترکیبات گیاهی می‌آید. این نگاه در معرفی خانواده‌های مختلف مجموعه، از مکمل‌های روزانه تا مراقبت از پوست، دیده می‌شود.\n\nهر خانواده با نام و بسته‌بندی خودش معرفی می‌شود. در صفحهٔ هر محصول می‌توانید توضیحات، شکل عرضه و راه دسترسی به داروخانه‌ها را یک‌جا ببینید.",
			'portfolio' => 'هر خانوادهٔ ساپونین روی نیاز مشخصی تمرکز دارد. با انتخاب هر برند می‌توانید معرفی آن و محصولات مرتبط را ببینید.',
			'hero_image' => $image . 'about-editorial.jpg', 'pharmacy_image' => $image . 'pharmacy-editorial.jpg',
			'story_image' => $image . 'hero.jpg', 'logo_image' => $image . 'logo.png',
		) );
	}
	public static function clean_profile( $input ) {
		$out = array();
		foreach ( self::FIELDS as $key => $name ) {
			if ( ! isset( $input[ $key ] ) || ! is_string( $input[ $key ] ) ) { continue; }
			$out[ $key ] = false !== strpos( $key, '_image' ) ? esc_url_raw( $input[ $key ], array( 'http', 'https' ) ) : sanitize_textarea_field( $input[ $key ] );
		}
		return $out;
	}
	public static function clean_catalog( $input ) {
		$out = array( 'brands'=>array(), 'products'=>array() );
		foreach ( $out as $group => $_ ) {
			$fields = 'brands' === $group ? array( 'description','features','banner_image' ) : array( 'brand','category_label','directions','contraindications' );
			foreach ( (array) ( $input[ $group ] ?? array() ) as $slug => $row ) {
				if ( ! is_array( $row ) ) { continue; }
				$values = array();
				foreach ( $fields as $f ) {
					if ( ! isset( $row[ $f ] ) || ! is_string( $row[ $f ] ) || '' === trim( $row[ $f ] ) ) { continue; }
					$values[ $f ] = 'banner_image' === $f ? esc_url_raw( $row[ $f ], array( 'http', 'https' ) ) : ( 'brand' === $f ? sanitize_title( $row[ $f ] ) : sanitize_textarea_field( $row[ $f ] ) );
				}
				if ( $values ) { $out[ $group ][ sanitize_title( $slug ) ] = $values; }
			}
		}
		return $out;
	}
	private static function field( $name, $label, $value, $url = false ) {
		$id = sanitize_html_class( str_replace( array( '[',']' ), array( '-', '' ), $name ) );
		echo '<p><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
		if ( $url ) { echo '<input dir="ltr" type="url" class="large-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">'; }
		else { echo '<textarea class="large-text" rows="3" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>'; }
		echo '</p>';
	}
	public static function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$profile = self::content();
		$catalog = get_option( self::CATALOG, array() );
		echo '<div class="wrap" style="max-width:900px"><h1>معرفی شرکت و برندهای ساپونین</h1><p>متن‌های شروع و عکس‌های معرفی نمونه‌اند. برای استفاده از عکس خودتان، نشانی فایل را از کتابخانهٔ رسانه کپی کنید.</p><form action="options.php" method="post">';
		settings_fields( 'sn_company_content' );
		echo '<h2>دربارهٔ شرکت</h2>';
		foreach ( self::FIELDS as $key => $label ) { self::field( self::PROFILE . '[' . $key . ']', $label, $profile[ $key ], false !== strpos( $key, '_image' ) ); }
		echo '<h2>صفحات برندها</h2><p>اگر یک فیلد را خالی بگذارید، از محتوای همان برند یا محصول در صفحهٔ اصلی استفاده می‌شود.</p>';
		foreach ( Portal::brands() as $slug => $brand ) {
			echo '<details><summary style="cursor:pointer;padding:10px 0"><strong>' . esc_html( $brand['title'] ) . '</strong></summary>';
			foreach ( array( 'description'=>'معرفی برند','features'=>'ویژگی‌ها، هر سطر یک مورد','banner_image'=>'نشانی عکس کمپین' ) as $key => $label ) { self::field( self::CATALOG . '[brands][' . $slug . '][' . $key . ']', $label, $catalog['brands'][ $slug ][ $key ] ?? '', 'banner_image' === $key ); }
			echo '</details>';
		}
		echo '<h2>ارتباط محصولات و برندها</h2><p>نام، تصویر، توضیحات و لینک خرید هر محصول از ابزار «محصولات متحرک ساپونین» در المنتور خوانده می‌شود.</p>';
		foreach ( Portal::products() as $slug => $product ) {
			echo '<p><strong>' . esc_html( $product['title'] ) . '</strong> &nbsp; <label>برند <select name="' . esc_attr( self::CATALOG . '[products][' . $slug . '][brand]' ) . '">';
			foreach ( Portal::brands() as $key => $brand ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $product['brand'], false ) . '>' . esc_html( $brand['title'] ) . '</option>'; }
			echo '</select></label> &nbsp; <label>دستهٔ محصول <input name="' . esc_attr( self::CATALOG . '[products][' . $slug . '][category_label]' ) . '" value="' . esc_attr( $product['category_label'] ) . '"></label></p>';
			foreach ( array( 'directions'=>'روش مصرف طبق برچسب محصول', 'contraindications'=>'موارد منع مصرف طبق برچسب محصول' ) as $key=>$label ) { self::field( self::CATALOG . '[products][' . $slug . '][' . $key . ']', $label, $catalog['products'][ $slug ][ $key ] ?? '' ); }
		}
		submit_button();
		echo '</form></div>';
	}
	public static function render() { $profile = self::content(); require SAPONINE_STUDIO_PATH . 'templates/about.php'; }
	public static function footer() {
		$profile = self::content();
		$contact = Service_Pages::contact_values();
		$slug = 'brand' === Portal::view() ? Portal::item() : ( 'product' === Portal::view() ? ( Portal::products()[ Portal::item() ]['brand'] ?? '' ) : '' );
		$brand = $slug ? ( Portal::brands()[ $slug ] ?? array() ) : array();
		$map = get_option( Importer::MAP_OPTION, array() );
		$credit = array( 'title'=>'طراحی،توسعه و پشتیبانی با هلدینگ کلاسیکا', 'link'=>array( 'url'=>'https://alimoradicoach.ir/' ) );
		$footer_id = Multilingual::translated_post_id( absint( $map['footer'] ?? 0 ) );
		if ( $footer_id ) {
			$stored = get_post_meta( $footer_id, '_elementor_data', true );
			$nodes = is_array( $stored ) ? $stored : json_decode( (string) $stored, true );
			foreach ( $nodes[0]['elements'] ?? array() as $child ) { if ( 'e668f17' === ( $child['id'] ?? '' ) ) { $credit = $child['settings']; break; } }
		}
		require SAPONINE_STUDIO_PATH . 'templates/company-footer.php';
	}
}
