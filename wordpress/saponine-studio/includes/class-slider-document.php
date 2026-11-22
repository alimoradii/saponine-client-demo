<?php
namespace Saponine\Studio;

defined( 'ABSPATH' ) || exit;

/** Change one widget in the same document that Elementor reads and edits. */
final class Slider_Document {
	public static function targets() {
		if ( ! current_user_can( 'manage_options' ) ) { return array(); }
		$front = 'page' === get_option( 'show_on_front' ) ? absint( get_option( 'page_on_front' ) ) : 0;
		$import = Multilingual::source_documents();
		$pages = get_posts( array(
			'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future' ),
			'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids', 'lang' => '',
			'meta_key' => '_elementor_data', 'meta_value' => '"saponine-hero-slider"', 'meta_compare' => 'LIKE',
		) );
		$pages = array_map( 'intval', $pages );
		$list = array();
		foreach ( array_unique( array_merge( array( $front, $import['home'] ), $pages ) ) as $id ) {
			if ( ! $id || ! in_array( (int) $id, $pages, true ) || ! current_user_can( 'edit_post', $id ) ) { continue; }
			$title = get_the_title( $id ) ?: sprintf( 'برگهٔ %d', $id );
			if ( function_exists( 'pll_get_post_language' ) && ( $lang = pll_get_post_language( $id, 'name' ) ) ) { $title .= ' (' . $lang . ')'; }
			$list[ $id ] = $title;
		}
		return $list;
	}

	private static function decode( $raw ) {
		// Retain object/array distinctions on unrelated Elementor controls.
		return json_decode( is_string( $raw ) ? $raw : wp_json_encode( $raw ) );
	}

	public static function find( $nodes, $predicate ) {
		$found = array();
		foreach ( (array) $nodes as $node ) {
			if ( ! is_object( $node ) || empty( $node->id ) ) { continue; }
			if ( $predicate( $node ) ) { $found[ $node->id ] = $node; }
			if ( ! empty( $node->elements ) ) { $found += self::find( $node->elements, $predicate ); }
		}
		return $found;
	}

	public static function nodes( $post_id ) {
		return self::decode( get_post_meta( $post_id, '_elementor_data', true ) );
	}

	private static function heroes( $nodes ) {
		return self::find( $nodes, static function ( $node ) { return 'saponine-hero-slider' === ( $node->widgetType ?? '' ); } );
	}

	private static function permitted( $id ) {
		return $id && current_user_can( 'manage_options' ) && current_user_can( 'edit_post', $id ) &&
			in_array( get_post_type( $id ), array( 'page', 'elementor_library' ), true ) &&
			! in_array( get_post_status( $id ), array( 'trash', 'auto-draft' ), true );
	}

	public static function token( $value ) {
		return hash( 'sha256', wp_json_encode( $value ) );
	}

	private static function missing() {
		return new \WP_Error( 'slider_missing', 'اسلایدر انتخاب‌شده در این برگه پیدا نشد.' );
	}

	public static function conflict() {
		return new \WP_Error( 'slider_conflict', 'این بخش هم‌زمان تغییر کرده است. صفحهٔ تنظیمات را دوباره باز کنید و تغییرات تازه را بررسی کنید.' );
	}

	public static function read( $post_id, $widget_id = '' ) {
		if ( ! self::permitted( $post_id ) || 'page' !== get_post_type( $post_id ) ) { return self::missing(); }
		$heroes = self::heroes( self::nodes( $post_id ) );
		$widget_id = '' === $widget_id ? array_key_first( $heroes ) : $widget_id;
		if ( ! isset( $heroes[ $widget_id ] ) ) { return self::missing(); }
		$widgets = array();
		foreach ( $heroes as $id => $hero ) {
			$name = $hero->settings->_title ?? $hero->settings->carousel_label ?? 'اسلایدر';
			$widgets[ $id ] = count( $heroes ) > 1 ? $name . ' · ' . $id : $name;
		}
		$settings = $heroes[ $widget_id ]->settings ?? (object) array();
		return array( 'id' => (string) $widget_id, 'widgets' => $widgets, 'settings' => json_decode( wp_json_encode( $settings ), true ), 'revision' => self::token( $settings ) );
	}

	/** Compare-and-swap the whole document; retry an unrelated concurrent edit on its fresh value. */
	public static function change( $post_id, $apply ) {
		if ( ! Plugin::elementor_ready() ) { return new \WP_Error( 'slider_elementor', 'برای ذخیرهٔ اسلایدر، المنتور باید فعال باشد.' ); }
		if ( ! self::permitted( $post_id ) ) { return new \WP_Error( 'slider_permission', 'اجازهٔ ویرایش این سند را ندارید.' ); }
		for ( $attempt = 0; $attempt < 3; $attempt++ ) {
			$raw = get_post_meta( $post_id, '_elementor_data', true );
			$nodes = self::decode( $raw );
			if ( ! is_array( $nodes ) ) { return self::missing(); }
			$changed = $apply( $nodes );
			if ( is_wp_error( $changed ) ) { return $changed; }
			if ( ! $changed ) { return true; }
			$json = wp_json_encode( $nodes );
			if ( false === $json ) { return new \WP_Error( 'slider_encoding', 'ذخیرهٔ داده‌های این سند ممکن نشد.' ); }
			if ( update_post_meta( $post_id, '_elementor_data', wp_slash( $json ), $raw ) ) {
				delete_post_meta( $post_id, '_elementor_element_cache' );
				delete_post_meta( $post_id, '_elementor_page_assets' );
				\Elementor\Core\Files\CSS\Post::create( $post_id )->delete();
				\Elementor\Plugin::$instance->db->save_plain_text( $post_id );
				do_action( 'elementor/editor/after_save', $post_id, json_decode( $json, true ) );
				return true;
			}
			wp_cache_delete( $post_id, 'post_meta' );
		}
		return self::conflict();
	}

	public static function save( $post_id, $widget_id, $revision, $settings ) {
		if ( 'page' !== get_post_type( $post_id ) || ! is_array( $settings ) ) { return self::missing(); }
		return self::change( $post_id, static function ( $nodes ) use ( $widget_id, $revision, $settings ) {
			$heroes = self::heroes( $nodes );
			if ( ! isset( $heroes[ $widget_id ] ) ) { return self::missing(); }
			$node = $heroes[ $widget_id ];
			$before = $node->settings ?? (object) array();
			if ( ! hash_equals( self::token( $before ), (string) $revision ) ) { return self::conflict(); }
			$old = json_decode( wp_json_encode( $before ), true );
			if ( $old === $settings ) { return false; }
			$merged = (object) $before;
			foreach ( array_diff_key( $old, $settings ) as $key => $unused ) { unset( $merged->$key ); }
			foreach ( $settings as $key => $value ) {
				if ( ! array_key_exists( $key, $old ) || $value !== $old[ $key ] ) { $merged->$key = $value; }
			}
			$node->settings = $merged;
			return true;
		} );
	}
}
