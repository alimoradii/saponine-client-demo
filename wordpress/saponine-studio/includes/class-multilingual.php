<?php
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Optional Polylang bridge. Routing, language cookies and hreflang remain owned by Polylang. */
final class Multilingual {
	const MAP_OPTION = 'saponine_studio_translation_map';
	const LOCK_OPTION = 'saponine_studio_translation_lock';

	public static function boot() {
		add_filter( 'pll_get_post_types', array( __CLASS__, 'post_types' ), 20, 2 );
		add_filter( 'pll_copy_post_metas', array( __CLASS__, 'independent_elementor_meta' ), 100, 2 );
		add_filter( 'language_attributes', array( __CLASS__, 'language_attributes' ), 20 );
		add_filter( 'body_class', array( __CLASS__, 'body_classes' ), 20 );
		add_filter( 'elementor/frontend/builder_content_data', array( __CLASS__, 'frontend_data' ), 20, 2 );
		add_filter( 'elementor/frontend/widget/should_render', array( __CLASS__, 'should_render_switcher' ), 20, 2 );
	}

	public static function ready() {
		foreach ( array( 'pll_languages_list', 'pll_current_language', 'pll_default_language', 'pll_get_post_language', 'pll_get_post', 'pll_set_post_language', 'pll_get_post_translations', 'pll_save_post_translations', 'pll_home_url', 'pll_the_languages' ) as $function ) {
			if ( ! function_exists( $function ) ) { return false; }
		}
		return true;
	}

	public static function post_types( $types, $is_settings = false ) {
		if ( $is_settings ) { unset( $types['elementor_library'] ); }
		else { $types['elementor_library'] = 'elementor_library'; }
		return $types;
	}

	/** Never synchronize one language's Elementor layout or motion over another. */
	public static function independent_elementor_meta( $metas, $sync = false ) {
		$keys = array( '_elementor_data', '_elementor_page_settings', '_elementor_edit_mode', '_elementor_template_type', '_wp_page_template' );
		if ( $sync ) {
			return array_values( array_filter( $metas, static function( $key ) {
				return '_wp_page_template' !== $key && 0 !== strpos( $key, '_elementor_' ) && 0 !== strpos( $key, '_saponine_' );
			} ) );
		}
		return array_values( array_unique( array_merge( $metas, $keys ) ) );
	}

	/** @return array<string,array{slug:string,name:string,locale:string,rtl:bool}> */
	public static function languages() {
		if ( ! self::ready() || ! did_action( 'init' ) ) { return array(); }
		$slugs = array_values( (array) pll_languages_list( array( 'hide_empty' => false, 'fields' => 'slug' ) ) );
		$names = array_values( (array) pll_languages_list( array( 'hide_empty' => false, 'fields' => 'name' ) ) );
		$locales = array_values( (array) pll_languages_list( array( 'hide_empty' => false, 'fields' => 'locale' ) ) );
		$directions = array_values( (array) pll_languages_list( array( 'hide_empty' => false, 'fields' => 'is_rtl' ) ) );
		$out = array();
		foreach ( $slugs as $i => $slug ) {
			if ( ! is_string( $slug ) || '' === $slug ) { continue; }
			$out[ $slug ] = array( 'slug' => $slug, 'name' => isset( $names[ $i ] ) ? (string) $names[ $i ] : $slug, 'locale' => isset( $locales[ $i ] ) ? (string) $locales[ $i ] : $slug, 'rtl' => ! empty( $directions[ $i ] ) );
		}
		return $out;
	}

	public static function current_language() {
		if ( ! self::ready() || ! did_action( 'init' ) ) { return ''; }
		$id = get_queried_object_id();
		if ( isset( $_GET['elementor-preview'] ) && is_scalar( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$preview_id = absint( $_GET['elementor-preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $preview_id && current_user_can( 'edit_post', $preview_id ) ) { $id = $preview_id; }
		}
		if ( $id && ( is_singular() || 'elementor_library' === get_post_type( $id ) ) ) {
			$lang = pll_get_post_language( $id, 'slug' );
			if ( $lang ) { return (string) $lang; }
		}
		$lang = pll_current_language( 'slug' );
		return $lang ? (string) $lang : (string) pll_default_language( 'slug' );
	}

	public static function direction() {
		$languages = self::languages();
		$lang = self::current_language();
		return isset( $languages[ $lang ] ) ? ( $languages[ $lang ]['rtl'] ? 'rtl' : 'ltr' ) : ( is_rtl() ? 'rtl' : 'ltr' );
	}

	public static function home_url( $lang = '' ) {
		if ( self::ready() && did_action( 'init' ) ) {
			$lang = $lang ? $lang : self::current_language();
			if ( isset( self::languages()[ $lang ] ) ) {
				$url = pll_home_url( $lang );
				if ( is_string( $url ) && '' !== $url ) { return $url; }
			}
		}
		return \home_url( '/' );
	}

	/** Header/footer translations must be published; unready translations fall back to the base template. */
	public static function translated_post_id( $id, $lang = '' ) {
		$id = absint( $id );
		if ( ! self::ready() || ! $id ) { return $id; }
		$lang = $lang ? $lang : self::current_language();
		if ( ! $lang ) { return $id; }
		$translated = absint( pll_get_post( $id, $lang ) );
		return $translated && 'publish' === get_post_status( $translated ) && '' === (string) get_post_field( 'post_password', $translated ) ? $translated : $id;
	}

	public static function language_attributes( $attributes ) {
		if ( is_admin() || ! self::ready() ) { return $attributes; }
		$languages = self::languages();
		$lang = self::current_language();
		if ( ! isset( $languages[ $lang ] ) ) { return $attributes; }
		$attributes = preg_replace( '/(?:^|\s)(?:dir|lang)="[^"]*"/', '', $attributes );
		return trim( $attributes . ' lang="' . esc_attr( str_replace( '_', '-', $languages[ $lang ]['locale'] ) ) . '" dir="' . esc_attr( self::direction() ) . '"' );
	}

	public static function body_classes( $classes ) {
		if ( ! self::ready() ) { return $classes; }
		$classes = array_values( array_diff( $classes, array( 'rtl' ) ) );
		if ( 'rtl' === self::direction() ) { $classes[] = 'rtl'; }
		return $classes;
	}

	private static function public_post( $id ) {
		$post = get_post( $id );
		return $post && 'publish' === $post->post_status && '' === $post->post_password && 'elementor_library' !== $post->post_type && is_post_type_viewable( $post->post_type );
	}

	/** Public language links only. Draft/private translations never leak into the visitor's switcher. */
	public static function switcher_languages( $args = array() ) {
		if ( ! self::ready() || is_admin() ) { return array(); }
		$args = wp_parse_args( $args, array( 'hide_missing' => true, 'force_home' => false ) );
		$context_id = is_singular() ? get_queried_object_id() : 0;
		$force_home = ! empty( $args['force_home'] ) || 'elementor_library' === get_post_type( $context_id );
		if ( $force_home ) { $context_id = 0; }
		$modern = defined( 'POLYLANG_VERSION' ) && version_compare( POLYLANG_VERSION, '3.9', '>=' );
		$raw_args = array( 'raw' => true, 'echo' => false, 'hide_current' => false, 'force_home' => $force_home, 'post_id' => $context_id );
		$raw_args[ $modern ? 'hide_if_empty' : 'hide_empty' ] = false;
		$raw_args['hide_if_no_translation'] = false;
		$raw = pll_the_languages( $raw_args );
		$languages = self::languages();
		$out = array();
		foreach ( (array) $raw as $entry ) {
			$slug = isset( $entry['slug'] ) ? (string) $entry['slug'] : '';
			if ( ! isset( $languages[ $slug ] ) ) { continue; }
			$missing = isset( $entry['has_translations'] ) ? ! $entry['has_translations'] : ! empty( $entry['no_translation'] );
			if ( $context_id ) {
				$translated = absint( pll_get_post( $context_id, $slug ) );
				if ( $translated && ! self::public_post( $translated ) ) { continue; }
				if ( ! $translated ) { $missing = true; }
			}
			if ( ! empty( $args['hide_missing'] ) && $missing ) { continue; }
			$url = isset( $entry['url'] ) ? esc_url_raw( $entry['url'] ) : '';
			if ( $force_home || $missing || ! $context_id ) {
				$front_id = absint( get_option( 'page_on_front' ) );
				if ( 'page' === get_option( 'show_on_front' ) && $front_id ) {
					$home_id = absint( pll_get_post( $front_id, $slug ) );
					if ( ! self::public_post( $home_id ) ) { continue; }
				}
				if ( $force_home || $missing ) { $url = self::home_url( $slug ); }
			}
			if ( ! $url ) { continue; }
			$out[] = array_merge( $languages[ $slug ], array( 'url' => $url, 'current' => isset( $entry['is_current'] ) ? (bool) $entry['is_current'] : ! empty( $entry['current_lang'] ), 'no_translation' => $missing ) );
		}
		return $out;
	}

	public static function should_render_switcher( $should_render, $widget ) {
		if ( ! $should_render || 'saponine-language-switcher' !== $widget->get_name() ) { return $should_render; }
		if ( Plugin::elementor_ready() && ( \Elementor\Plugin::instance()->editor->is_edit_mode() || \Elementor\Plugin::instance()->preview->is_preview_mode() ) ) { return true; }
		$settings = $widget->get_settings_for_display();
		$languages = self::switcher_languages();
		if ( ! self::ready() || ! $languages || ( ( ! isset( $settings['hide_single'] ) || 'yes' === $settings['hide_single'] ) && count( $languages ) < 2 ) ) { return false; }
		if ( isset( $settings['show_current'] ) && 'yes' !== $settings['show_current'] ) {
			$languages = array_filter( $languages, static function( $language ) { return ! $language['current']; } );
		}
		return ! empty( $languages );
	}

	public static function source_documents() {
		$map = get_option( Importer::MAP_OPTION, array() );
		$map = is_array( $map ) ? $map : array();
		$settings = Plugin::settings();
		$out = array( 'home' => isset( $map['home'] ) ? absint( $map['home'] ) : 0 );
		foreach ( array( 'header', 'footer' ) as $role ) {
			$out[ $role ] = Plugin::valid_section( absint( $settings[ $role . '_id' ] ) ) ? absint( $settings[ $role . '_id' ] ) : ( isset( $map[ $role ] ) ? absint( $map[ $role ] ) : 0 );
		}
		return $out;
	}

	private static function home_variants( $home_id ) {
		$urls = array( get_permalink( $home_id ), add_query_arg( 'page_id', absint( $home_id ), \home_url( '/' ) ) );
		$family = array( absint( $home_id ) );
		if ( self::ready() ) {
			foreach ( (array) pll_get_post_translations( $home_id ) as $id ) { $family[] = absint( $id ); $urls[] = get_permalink( $id ); $urls[] = add_query_arg( 'page_id', absint( $id ), \home_url( '/' ) ); }
		}
		// An imported draft must never take over links to an unrelated existing front page.
		if ( 'page' === get_option( 'show_on_front' ) && in_array( absint( get_option( 'page_on_front' ) ), $family, true ) ) {
			$urls[] = \home_url( '/' );
			if ( self::ready() ) { foreach ( self::languages() as $language ) { $urls[] = pll_home_url( $language['slug'] ); } }
		}
		return array_values( array_filter( array_unique( $urls ) ) );
	}

	private static function rewrite_home_link( $url, $sources, $destination ) {
		if ( ! is_string( $url ) || ! $destination || false === strpos( $url, '#' ) ) { return $url; }
		$parts = explode( '#', $url, 2 );
		if ( '' === $parts[0] || '' === $parts[1] ) { return $url; }
		foreach ( $sources as $source ) {
			if ( untrailingslashit( $parts[0] ) === untrailingslashit( $source ) ) { return $destination . '#' . $parts[1]; }
		}
		return $url;
	}

	/** Remap only known home-page anchor URLs, never arbitrary links, images, or HTML strings. */
	public static function translate_home_link( $url ) {
		if ( ! self::ready() || ! is_string( $url ) || false === strpos( $url, '#' ) ) { return $url; }
		$source = self::source_documents();
		if ( ! $source['home'] ) { return $url; }
		$lang = self::current_language();
		if ( ! $lang ) { return $url; }
		$translated = absint( pll_get_post( $source['home'], $lang ) );
		if ( ! $translated || ( ! self::public_post( $translated ) && ! current_user_can( 'edit_post', $translated ) ) ) { return $url; }
		return self::rewrite_home_link( $url, self::home_variants( $source['home'] ), get_permalink( $translated ) );
	}

	public static function frontend_data( $data, $post_id ) {
		if ( ! is_array( $data ) ) { return $data; }
		if ( ! self::ready() ) {
			// The importer builds links while the homepage is still a draft.
			// After it is assigned as the front page, resolve its old ?page_id
			// anchors to its public permalink so they scroll in the same document.
			$map = get_option( Importer::MAP_OPTION, array() );
			$home = absint( $map['home'] ?? 0 );
			if ( ! $home || 'page' !== get_option( 'show_on_front' ) || $home !== absint( get_option( 'page_on_front' ) ) ) { return $data; }
			$variants = self::home_variants( $home );
			$destination = get_permalink( $home );
			return self::rewrite_urls( $data, static function( $url ) use ( $variants, $destination ) {
				return self::rewrite_home_link( $url, $variants, $destination );
			} );
		}
		return self::rewrite_urls( $data, array( __CLASS__, 'translate_home_link' ) );
	}

	private static function rewrite_urls( $value, $callback ) {
		if ( ! is_array( $value ) ) { return $value; }
		foreach ( $value as $key => $child ) {
			if ( 'url' === $key && is_string( $child ) ) { $value[ $key ] = call_user_func( $callback, $child ); }
			elseif ( is_array( $child ) ) { $value[ $key ] = self::rewrite_urls( $child, $callback ); }
		}
		return $value;
	}

	/** Build independent, editable copies of the three current documents. Does not translate prose or publish pages. */
	public static function create_translation( $source_lang, $target_lang ) {
		if ( ! current_user_can( 'manage_options' ) || ! Plugin::elementor_ready() || ! self::ready() ) {
			return new \WP_Error( 'saponine_language_dependency', __( 'برای ساخت نسخهٔ زبانی، دسترسی مدیر و افزونه‌های Elementor و Polylang رایگان لازم است.', 'saponine-studio' ) );
		}
		$languages = self::languages();
		if ( ! is_string( $source_lang ) || ! is_string( $target_lang ) || ! isset( $languages[ $source_lang ], $languages[ $target_lang ] ) || $source_lang === $target_lang ) {
			return new \WP_Error( 'saponine_language_invalid', __( 'زبان نسخهٔ فعلی و زبان مقصد را از زبان‌های تنظیم‌شده و متفاوت انتخاب کنید.', 'saponine-studio' ) );
		}
		$lock = absint( get_option( self::LOCK_OPTION, 0 ) );
		if ( $lock && time() - $lock > 600 ) { delete_option( self::LOCK_OPTION ); }
		if ( ! add_option( self::LOCK_OPTION, time(), '', false ) ) { return new \WP_Error( 'saponine_language_busy', __( 'ساخت نسخهٔ زبانی دیگری در حال اجراست؛ کمی بعد دوباره تلاش کنید.', 'saponine-studio' ) ); }
		try { return self::perform_translation( $source_lang, $target_lang, $languages ); }
		catch ( \Throwable $error ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'Saponine translation: ' . $error->getMessage() ); } // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			return new \WP_Error( 'saponine_language_failed', __( 'ساخت نسخهٔ زبانی کامل نشد؛ موارد ساخته‌شده حفظ شده‌اند. گزارش خطای وردپرس را بررسی و دوباره تلاش کنید.', 'saponine-studio' ) );
		} finally { delete_option( self::LOCK_OPTION ); }
	}

	private static function perform_translation( $source_lang, $target_lang, $languages ) {
		$sources = self::source_documents();
		foreach ( $sources as $role => $id ) {
			$type = 'home' === $role ? 'page' : 'elementor_library';
			if ( ! $id || $type !== get_post_type( $id ) || 'trash' === get_post_status( $id ) || ! current_user_can( 'edit_post', $id ) || ( 'home' !== $role && 'section' !== get_post_meta( $id, '_elementor_template_type', true ) ) ) {
				return new \WP_Error( 'saponine_language_source', __( 'ابتدا طرح اصلی را کامل وارد کنید و سربرگ و پابرگ معتبر را انتخاب کنید.', 'saponine-studio' ) );
			}
			$assigned = pll_get_post_language( $id, 'slug' );
			if ( $assigned && $source_lang !== $assigned ) {
				return new \WP_Error( 'saponine_language_conflict', __( 'زبان انتخاب‌شده با زبان ثبت‌شدهٔ یکی از اسناد اصلی متفاوت است. زبان نسخهٔ فعلی را درست انتخاب کنید؛ این ابزار زبان سند موجود را عوض نمی‌کند.', 'saponine-studio' ) );
			}
			$elements = json_decode( get_post_meta( $id, '_elementor_data', true ), true );
			if ( ! is_array( $elements ) || ! $elements ) { return new \WP_Error( 'saponine_language_empty', __( 'یکی از اسناد اصلی، محتوای معتبر المنتور ندارد.', 'saponine-studio' ) ); }
		}
		$map = get_option( self::MAP_OPTION, array() );
		$map = is_array( $map ) ? $map : array();
		$map['source_language'] = $source_lang;
		if ( ! isset( $map['languages'] ) || ! is_array( $map['languages'] ) ) { $map['languages'] = array(); }
		$result = array( 'created' => array(), 'reused' => array() );
		$copy = array();
		foreach ( $sources as $role => $source_id ) {
			if ( ! pll_get_post_language( $source_id, 'slug' ) ) { pll_set_post_language( $source_id, $source_lang ); }
			$target_id = absint( pll_get_post( $source_id, $target_lang ) );
			if ( ! $target_id && isset( $map['languages'][ $target_lang ][ $role ] ) ) {
				$candidate = absint( $map['languages'][ $target_lang ][ $role ] );
				if ( $source_id === absint( get_post_meta( $candidate, '_saponine_translation_of', true ) ) && $target_lang === pll_get_post_language( $candidate, 'slug' ) ) { $target_id = $candidate; }
			}
			if ( $target_id ) {
				if ( get_post_type( $target_id ) !== get_post_type( $source_id ) || 'trash' === get_post_status( $target_id ) || ! current_user_can( 'edit_post', $target_id ) || ( 'home' !== $role && 'section' !== get_post_meta( $target_id, '_elementor_template_type', true ) ) ) {
					return new \WP_Error( 'saponine_language_existing', __( 'ترجمهٔ موجود یکی از اسناد قابل استفاده نیست؛ وضعیت آن را در برگه‌ها یا قالب‌های المنتور بررسی کنید. سند موجود بازنویسی نشد.', 'saponine-studio' ) );
				}
				$group = (array) pll_get_post_translations( $target_id );
				if ( isset( $group[ $source_lang ] ) && absint( $group[ $source_lang ] ) !== $source_id ) { return new \WP_Error( 'saponine_language_group', __( 'این ترجمه به سند دیگری متصل است؛ ارتباط زبان‌ها را در Polylang بررسی کنید.', 'saponine-studio' ) ); }
				$result['reused'][] = $role;
				$ours = $source_id === absint( get_post_meta( $target_id, '_saponine_translation_of', true ) );
				$copy[ $role ] = $ours && ! get_post_meta( $target_id, '_saponine_translation_complete', true ) && ! get_post_meta( $target_id, '_elementor_data', true );
			} else {
				$post = get_post( $source_id );
				$target_id = wp_insert_post( array( 'post_type' => $post->post_type, 'post_status' => 'draft', 'post_title' => $post->post_title . ' — ' . $languages[ $target_lang ]['name'], 'post_name' => $post->post_name . '-' . $target_lang, 'post_content' => '', 'post_author' => get_current_user_id(), 'comment_status' => $post->comment_status ), true );
				if ( is_wp_error( $target_id ) ) { return $target_id; }
				update_post_meta( $target_id, '_saponine_translation_of', $source_id );
				update_post_meta( $target_id, '_saponine_demo_role', $role );
				update_post_meta( $target_id, '_elementor_edit_mode', 'builder' );
				update_post_meta( $target_id, '_elementor_template_type', 'home' === $role ? 'wp-page' : 'section' );
				update_post_meta( $target_id, '_wp_page_template', get_post_meta( $source_id, '_wp_page_template', true ) ?: 'default' );
				if ( 'home' !== $role ) { wp_set_object_terms( $target_id, 'section', 'elementor_library_type' ); }
				pll_set_post_language( $target_id, $target_lang );
				$copy[ $role ] = true;
				$result['created'][] = $role;
			}
			$result[ $role ] = absint( $target_id );
			$map['languages'][ $target_lang ][ $role ] = absint( $target_id );
			update_option( self::MAP_OPTION, $map, false );
			$translations = (array) pll_get_post_translations( $source_id );
			$translations[ $source_lang ] = $source_id;
			$translations[ $target_lang ] = absint( $target_id );
			pll_save_post_translations( $translations );
		}
		$source_urls = self::home_variants( $sources['home'] );
		$target_url = get_permalink( $result['home'] );
		$rewrite = static function( $url ) use ( $source_urls, $target_url ) { return self::rewrite_home_link( $url, $source_urls, $target_url ); };
		foreach ( $sources as $role => $source_id ) {
			if ( empty( $copy[ $role ] ) ) { continue; }
			$target_id = $result[ $role ];
			$elements = json_decode( get_post_meta( $source_id, '_elementor_data', true ), true );
			$elements = self::rewrite_urls( $elements, $rewrite );
			if ( $languages[ $source_lang ]['rtl'] !== $languages[ $target_lang ]['rtl'] ) { $elements = self::adapt_alignment( $elements ); }
			$settings = get_post_meta( $source_id, '_elementor_page_settings', true );
			$settings = is_array( $settings ) ? self::rewrite_urls( $settings, $rewrite ) : array();
			$settings['post_status'] = 'draft';
			$settings['post_title'] = get_the_title( $target_id );
			$document = \Elementor\Plugin::instance()->documents->get( $target_id );
			if ( ! $document || ! $document->save( array( 'elements' => $elements, 'settings' => $settings ) ) ) { return new \WP_Error( 'saponine_language_save', __( 'ذخیرهٔ سند زبانی المنتور کامل نشد؛ موارد ساخته‌شده برای تلاش بعدی حفظ شدند.', 'saponine-studio' ) ); }
			if ( 'home' !== $role ) {
				$published = wp_update_post( array( 'ID' => $target_id, 'post_status' => 'publish' ), true );
				if ( is_wp_error( $published ) ) { return $published; }
			}
			update_post_meta( $target_id, '_saponine_translation_complete', '1' );
		}
		return $result;
	}

	private static function adapt_alignment( $elements ) {
		foreach ( $elements as &$element ) {
			if ( in_array( isset( $element['widgetType'] ) ? $element['widgetType'] : '', array( 'heading', 'text-editor', 'button' ), true ) && ! empty( $element['settings'] ) ) {
				foreach ( array( 'align', 'align_tablet', 'align_mobile', 'text_align', 'text_align_tablet', 'text_align_mobile' ) as $key ) {
					if ( isset( $element['settings'][ $key ] ) && in_array( $element['settings'][ $key ], array( 'right', 'left' ), true ) ) { $element['settings'][ $key ] = 'right' === $element['settings'][ $key ] ? 'left' : 'right'; }
				}
			}
			if ( ! empty( $element['elements'] ) ) { $element['elements'] = self::adapt_alignment( $element['elements'] ); }
		}
		unset( $element );
		return $elements;
	}
}
