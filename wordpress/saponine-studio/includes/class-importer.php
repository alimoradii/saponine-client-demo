<?php
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Imports only bundled, trusted demo data; never downloads remote content. */
final class Importer {
	const MAP_OPTION = 'saponine_studio_import_map';
	const LOCK_OPTION = 'saponine_studio_import_lock';

	public static function run() {
		if ( ! current_user_can( 'manage_options' ) || ! Plugin::elementor_ready() ) {
			return new \WP_Error( 'saponine_permissions', __( 'اجازه یا پیش‌نیاز درون‌ریزی فراهم نیست.', 'saponine-studio' ) );
		}
		$lock = absint( get_option( self::LOCK_OPTION, 0 ) );
		if ( $lock && time() - $lock > 600 ) {
			delete_option( self::LOCK_OPTION );
		}
		if ( ! add_option( self::LOCK_OPTION, time(), '', false ) ) {
			return new \WP_Error( 'saponine_busy', __( 'درون‌ریزی دیگری در حال اجراست. کمی بعد دوباره تلاش کنید.', 'saponine-studio' ) );
		}
		try {
			return self::perform();
		} catch ( \Throwable $error ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Saponine import: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			}
			return new \WP_Error( 'saponine_import_failed', __( 'درون‌ریزی کامل نشد. فایل‌های بسته و گزارش خطای وردپرس را بررسی کنید؛ موارد واردشده حفظ شده‌اند و اجرای دوباره، آن‌ها را بازنویسی نمی‌کند.', 'saponine-studio' ) );
		} finally {
			delete_option( self::LOCK_OPTION );
		}
	}

	private static function perform() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$map = get_option( self::MAP_OPTION, array() );
		$map = is_array( $map ) ? $map : array();
		$map['media'] = isset( $map['media'] ) && is_array( $map['media'] ) ? $map['media'] : array();
		$assets = array(
			'logo.png' => __( 'نشان ساپونین', 'saponine-studio' ),
			'hero.jpg' => __( 'مجموعه محصولات ساپونین', 'saponine-studio' ),
			'bottle.jpg' => __( 'ساپونین بالانس', 'saponine-studio' ),
			'serum.jpg' => __( 'سرم بوتانیکال', 'saponine-studio' ),
			'sachet.jpg' => __( 'ساشه جوشان ساپونین', 'saponine-studio' ),
			'favicon.png' => __( 'آیکون ساپونین', 'saponine-studio' ),
		);
		foreach ( $assets as $filename => $title ) {
			$id = isset( $map['media'][ $filename ] ) ? absint( $map['media'][ $filename ] ) : 0;
			if ( $id && 'attachment' === get_post_type( $id ) && wp_get_attachment_url( $id ) ) {
				continue;
			}
			$file = SAPONINE_STUDIO_PATH . 'assets/images/' . $filename;
			if ( ! is_readable( $file ) ) {
				return new \WP_Error( 'saponine_missing_asset', sprintf( __( 'تصویر %s در بسته پیدا نشد.', 'saponine-studio' ), $filename ) );
			}
			$tmp = wp_tempnam( $filename );
			if ( ! $tmp || ! copy( $file, $tmp ) ) {
				if ( $tmp && is_file( $tmp ) ) {
					unlink( $tmp );
				}
				return new \WP_Error( 'saponine_temp', __( 'امکان ساخت فایل موقت وجود ندارد. فضای خالی و دسترسی پوشهٔ uploads را بررسی کنید.', 'saponine-studio' ) );
			}
			$id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp ), 0, $title );
			if ( is_file( $tmp ) ) {
				unlink( $tmp );
			}
			if ( is_wp_error( $id ) ) {
				return $id;
			}
			update_post_meta( $id, '_wp_attachment_image_alt', $title );
			update_post_meta( $id, '_saponine_demo_asset', $filename );
			$map['media'][ $filename ] = absint( $id );
			update_option( self::MAP_OPTION, $map, false );
		}
		$roles = array(
			'home' => array( 'title' => __( 'صفحهٔ اصلی ساپونین', 'saponine-studio' ), 'type' => 'page', 'status' => 'draft', 'document' => 'wp-page' ),
			'header' => array( 'title' => __( 'ساپونین — سربرگ', 'saponine-studio' ), 'type' => 'elementor_library', 'status' => 'publish', 'document' => 'section' ),
			'footer' => array( 'title' => __( 'ساپونین — پابرگ', 'saponine-studio' ), 'type' => 'elementor_library', 'status' => 'publish', 'document' => 'section' ),
		);
		foreach ( $roles as $role => $info ) {
			$id = isset( $map[ $role ] ) ? absint( $map[ $role ] ) : 0;
			$existing = $id && get_post_type( $id ) === $info['type'] && 'trash' !== get_post_status( $id );
			if ( $existing && get_post_meta( $id, '_saponine_import_complete', true ) ) {
				continue;
			}
			// A partially imported document may have been edited manually in the meantime.
			if ( $existing && get_post_meta( $id, '_elementor_data', true ) ) {
				update_post_meta( $id, '_saponine_import_complete', '1' );
				continue;
			}
			$file = SAPONINE_STUDIO_PATH . 'demo/' . $role . '.json';
			if ( ! is_readable( $file ) ) {
				return new \WP_Error( 'saponine_template_missing', sprintf( __( 'فایل طرح %s پیدا نشد.', 'saponine-studio' ), $role ) );
			}
			$template = json_decode( file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( ! is_array( $template ) || empty( $template['content'] ) || ! is_array( $template['content'] ) ) {
				return new \WP_Error( 'saponine_template_invalid', sprintf( __( 'ساختار طرح %s معتبر نیست.', 'saponine-studio' ), $role ) );
			}
			if ( ! $existing ) {
				$id = wp_insert_post( array( 'post_title' => $info['title'], 'post_type' => $info['type'], 'post_status' => $info['status'], 'post_content' => '', 'post_author' => get_current_user_id() ), true );
				if ( is_wp_error( $id ) ) {
					return $id;
				}
				$map[ $role ] = absint( $id );
				update_option( self::MAP_OPTION, $map, false );
			}
			$home_url = ! empty( $map['home'] ) ? get_permalink( $map['home'] ) : home_url( '/' );
			$data = self::resolve_assets( $template['content'], $map['media'], $home_url );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			update_post_meta( $id, '_saponine_demo_role', $role );
			update_post_meta( $id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $id, '_elementor_template_type', $info['document'] );
			if ( 'home' === $role ) {
				update_post_meta( $id, '_wp_page_template', 'default' );
			} else {
				wp_set_object_terms( $id, 'section', 'elementor_library_type' );
			}
			$document = \Elementor\Plugin::instance()->documents->get( $id );
			if ( ! $document ) {
				return new \WP_Error( 'saponine_document', __( 'المنتور نتوانست سند جدید را باز کند.', 'saponine-studio' ) );
			}
			$settings = isset( $template['page_settings'] ) && is_array( $template['page_settings'] ) ? self::resolve_assets( $template['page_settings'], $map['media'], $home_url ) : array();
			if ( is_wp_error( $settings ) ) { return $settings; }
			$settings['post_status'] = get_post_status( $id );
			if ( 'home' === $role ) {
				$settings['hide_title'] = 'yes';
			}
			$saved = $document->save( array( 'elements' => $data, 'settings' => $settings ) );
			if ( ! $saved ) {
				return new \WP_Error( 'saponine_save', __( 'ذخیرهٔ سند المنتور کامل نشد؛ سطح دسترسی و گزارش وردپرس را بررسی کنید.', 'saponine-studio' ) );
			}
			update_post_meta( $id, '_saponine_import_complete', '1' );
			update_post_meta( $id, '_saponine_demo_version', SAPONINE_STUDIO_VERSION );
		}
		return $map;
	}

	/** Replace a known bundled media token and preserve every unrelated setting. */
	private static function resolve_assets( $value, $media, $home_url = '' ) {
		if ( is_array( $value ) ) {
			$media_token = isset( $value['url'] ) && is_string( $value['url'] ) && preg_match( '/\A\{\{asset:([a-z0-9._-]+)\}\}\z/i', $value['url'], $match );
			foreach ( $value as $key => $child ) {
				$value[ $key ] = self::resolve_assets( $child, $media, $home_url );
				if ( is_wp_error( $value[ $key ] ) ) {
					return $value[ $key ];
				}
			}
			if ( $media_token && isset( $media[ $match[1] ] ) ) {
				$value['id'] = absint( $media[ $match[1] ] );
			}
			return $value;
		}
		if ( is_string( $value ) && preg_match( '/\A\{\{asset:([a-z0-9._-]+)\}\}\z/i', $value, $match ) ) {
			if ( ! isset( $media[ $match[1] ] ) || ! wp_get_attachment_url( $media[ $match[1] ] ) ) {
				return new \WP_Error( 'saponine_unknown_asset', sprintf( __( 'تصویر مرجع %s وارد نشده است.', 'saponine-studio' ), $match[1] ) );
			}
			return wp_get_attachment_url( $media[ $match[1] ] );
		}
		if ( is_string( $value ) && 0 === strpos( $value, '{{home}}' ) ) {
			return esc_url_raw( ( $home_url ? $home_url : home_url( '/' ) ) . substr( $value, 8 ) );
		}
		return $value;
	}
}
