<?php
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Responsive compatibility for saved Elementor documents, without rewriting them. */
final class Layout {
	private static $active_headers = array();
	private static $active_layouts = array();
	private static $template_nodes = null;
	private static $document_scopes = array();

	public static function boot() {
		add_action( 'elementor/frontend/before_render', array( __CLASS__, 'enter_element' ) );
		add_action( 'elementor/frontend/after_render', array( __CLASS__, 'leave_element' ) );
		add_filter( 'elementor/widget/render_content', array( __CLASS__, 'legacy_header_logo' ), 20, 2 );
	}

	/** Elementor renders children between these public lifecycle hooks. */
	public static function enter_element( $element ) {
		$classes = trim( (string) $element->get_settings( 'css_classes' ) . ' ' . (string) $element->get_settings( '_css_classes' ) );
		$classes = preg_split( '/\s+/', $classes );
		if ( in_array( 'sn-header-bar', $classes, true ) ) {
			self::$active_headers[ spl_object_id( $element ) ] = true;
			self::compact_header_attributes( $element );
		}
		if ( array_intersect( $classes, array( 'sn-header-bar', 'sn-brand-strip', 'sn-panel', 'sn-hero-section', 'sn-footer' ) ) ) {
			self::$active_layouts[ spl_object_id( $element ) ] = true;
		}
		if ( self::$active_layouts || self::in_document_scope( $element ) ) { self::responsive_attributes( $element ); }
	}

	public static function leave_element( $element ) {
		unset( self::$active_headers[ spl_object_id( $element ) ] );
		unset( self::$active_layouts[ spl_object_id( $element ) ] );
	}

	/** Elementor may expand dynamic widgets after cached parents have finished
	 * rendering. The current document supplies their original layout ancestry. */
	private static function in_document_scope( $element, $header = false ) {
		if ( ! Plugin::elementor_ready() ) { return false; }
		$document = \Elementor\Plugin::instance()->documents->get_current();
		if ( ! $document ) { return false; }
		$id = $document->get_id();
		if ( ! isset( self::$document_scopes[ $id ] ) ) {
			self::$document_scopes[ $id ] = array( 'layout' => array(), 'header' => array() );
			self::index_document_scopes( $document->get_elements_data(), self::$document_scopes[ $id ] );
		}
		return ! empty( self::$document_scopes[ $id ][ $header ? 'header' : 'layout' ][ $element->get_id() ] );
	}

	private static function index_document_scopes( $nodes, &$scopes, $layout = false, $header = false ) {
		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) ) { continue; }
			$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
			$classes = preg_split( '/\s+/', trim( ( $settings['css_classes'] ?? '' ) . ' ' . ( $settings['_css_classes'] ?? '' ) ) );
			$in_header = $header || in_array( 'sn-header-bar', $classes, true );
			$in_layout = $layout || (bool) array_intersect( $classes, array( 'sn-header-bar', 'sn-brand-strip', 'sn-panel', 'sn-hero-section', 'sn-footer' ) );
			if ( ! empty( $node['id'] ) ) {
				if ( $in_layout ) { $scopes['layout'][ $node['id'] ] = true; }
				if ( $in_header ) { $scopes['header'][ $node['id'] ] = true; }
			}
			if ( ! empty( $node['elements'] ) ) { self::index_document_scopes( $node['elements'], $scopes, $in_layout, $in_header ); }
		}
	}

	/** The imported IDs stay stable across releases and language copies. Only
	 * layout classes and missing responsive defaults are read from the demo;
	 * text, links, media and saved Elementor settings are never replaced. */
	private static function template_nodes() {
		if ( null !== self::$template_nodes ) { return self::$template_nodes; }
		self::$template_nodes = array();
		foreach ( array( 'home', 'header', 'footer' ) as $name ) {
			$file = SAPONINE_STUDIO_PATH . 'demo/' . $name . '.json';
			if ( ! is_readable( $file ) ) { continue; }
			$data = json_decode( file_get_contents( $file ), true );
			if ( is_array( $data ) && ! empty( $data['content'] ) ) { self::index_nodes( $data['content'] ); }
		}
		return self::$template_nodes;
	}

	private static function index_nodes( $nodes ) {
		$allowed = array(
			'sn-header-main', 'sn-header-actions', 'sn-header-logo', 'sn-header-logo-plate',
			'sn-feature-layout', 'sn-feature-column', 'sn-feature-item', 'sn-feature-icon', 'sn-feature-copy',
			'sn-bottle-column', 'sn-bottle-ring', 'sn-bottle-body', 'sn-bottle-neck', 'sn-bottle-cap', 'sn-bottle-scene',
			'sn-brand-grid', 'sn-brand-logo', 'sn-about-layout', 'sn-about-copy', 'sn-about-media', 'sn-about-image', 'sn-about-actions',
			'sn-pharmacy-card', 'sn-pharmacy-text', 'sn-footer-columns', 'sn-footer-column', 'sn-footer-intro', 'sn-footer-logo-plate',
		);
		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) ) { continue; }
			$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
			$classes = preg_split( '/\s+/', trim( ( $settings['css_classes'] ?? '' ) . ' ' . ( $settings['_css_classes'] ?? '' ) ) );
			if ( ! empty( $node['id'] ) ) {
				self::$template_nodes[ $node['id'] ] = array( 'type' => $node['elType'] ?? '', 'widget' => $node['widgetType'] ?? '', 'classes' => array_values( array_intersect( $classes, $allowed ) ), 'settings' => $settings );
			}
			if ( ! empty( $node['elements'] ) ) { self::index_nodes( $node['elements'] ); }
		}
	}

	private static function has_setting( $settings, $key ) {
		if ( ! array_key_exists( $key, $settings ) ) { return false; }
		$value = $settings[ $key ];
		if ( is_array( $value ) ) {
			foreach ( array( 'size', 'top', 'right', 'bottom', 'left', 'column', 'row' ) as $part ) {
				if ( isset( $value[ $part ] ) && '' !== $value[ $part ] ) { return true; }
			}
			return false;
		}
		return null !== $value && '' !== $value;
	}

	private static function dimension( $value ) {
		if ( ! is_array( $value ) || ! isset( $value['size'], $value['unit'] ) || ! is_numeric( $value['size'] ) || ! in_array( $value['unit'], array( 'px', '%', 'em', 'rem', 'vw', 'vh', 'svh', 'dvh' ), true ) ) { return ''; }
		$size = (float) $value['size'];
		return $size >= 0 && $size <= 10000 ? $size . $value['unit'] : '';
	}

	/** Keep native Elementor controls editable while upgrading the old stock frame. */
	private static function compact_header_attributes( $element ) {
		if ( self::editing() ) { return; }
		$raw = $element->get_data();
		$s = isset( $raw['settings'] ) && is_array( $raw['settings'] ) ? $raw['settings'] : array();
		$element->add_render_attribute( '_wrapper', 'class', 'sn-header-compact' );
		$width = self::dimension( $s['width'] ?? array() );
		$old_frame = '1280px' === $width && '80px' === self::dimension( $s['min_height'] ?? array() ) && '12px' === self::dimension( $s['_offset_y'] ?? array() );
		if ( '' === $width || $old_frame ) { $width = '90%'; }
		$element->add_render_attribute( '_wrapper', 'style', '--sn-header-max-width:' . $width . ';' );
		foreach ( array( 'desktop' => '', 'tablet' => '_tablet', 'mobile' => '_mobile' ) as $device => $suffix ) {
			$height = self::dimension( $s[ 'min_height' . $suffix ] ?? array() );
			if ( '' === $height || '0px' === $height || ( $old_frame && 'desktop' === $device ) ) { $height = 'desktop' === $device ? '75px' : '56px'; }
			$offset = self::dimension( $s[ '_offset_y' . $suffix ] ?? array() );
			if ( '' === $offset || ( $old_frame && ( 'desktop' === $device || '0px' === $offset ) ) ) { $offset = 'desktop' === $device ? '29px' : '10px'; }
			$element->add_render_attribute( '_wrapper', 'style', '--sn-header-height-' . $device . ':' . $height . ';--sn-header-top-' . $device . ':' . $offset . ';' );
		}
	}

	private static function editing() {
		if ( ! Plugin::elementor_ready() ) { return false; }
		$plugin = \Elementor\Plugin::instance();
		return ( $plugin->editor && $plugin->editor->is_edit_mode() ) || ( $plugin->preview && $plugin->preview->is_preview_mode() );
	}

	private static function compact_logo_attributes( $element, $settings ) {
		if ( self::editing() ) { return; }
		foreach ( array( 'desktop' => '', 'tablet' => '_tablet', 'mobile' => '_mobile' ) as $device => $suffix ) {
			$width = self::dimension( $settings[ 'width' . $suffix ] ?? array() );
			$stock = 'desktop' === $device ? array( '112px', '168px', '192px' ) : array( '90px', '112px', '144px', '160px' );
			if ( '' === $width || in_array( $width, $stock, true ) ) { $width = 'desktop' === $device ? '174px' : '124px'; }
			$element->add_render_attribute( '_wrapper', 'style', '--sn-header-logo-width-' . $device . ':' . $width . ';' );
		}
		// Crop the blank canvas only for the known bundled logo, never arbitrary replacements.
		$id = isset( $settings['image']['id'] ) ? absint( $settings['image']['id'] ) : 0;
		$metadata = $id ? wp_get_attachment_metadata( $id ) : false;
		if ( $id && 'logo.png' === get_post_meta( $id, '_saponine_demo_asset', true ) && is_array( $metadata ) && 512 === (int) ( $metadata['width'] ?? 0 ) && 362 === (int) ( $metadata['height'] ?? 0 ) ) {
			$element->add_render_attribute( '_wrapper', 'class', 'sn-header-logo-padded' );
		}
	}

	private static function fallback( $element, $device, $property, $value ) {
		$element->add_render_attribute( '_wrapper', 'class', 'sn-layout-' . $device . '-' . $property );
		$element->add_render_attribute( '_wrapper', 'style', '--sn-layout-' . $device . '-' . $property . ':' . $value . ';' );
	}

	private static function spacing_fallback( $element, $device, $property, $value ) {
		$parts = 'padding' === $property ? array( 'top', 'right', 'bottom', 'left' ) : array( 'row', 'column' );
		if ( ! is_array( $value ) || empty( $value['unit'] ) ) { return; }
		$dimensions = array();
		foreach ( $parts as $part ) {
			$dimensions[ $part ] = self::dimension( array( 'unit' => $value['unit'], 'size' => $value[ $part ] ?? '' ) );
			if ( '' === $dimensions[ $part ] ) { return; }
		}
		$element->add_render_attribute( '_wrapper', 'class', 'sn-layout-' . $device . '-' . $property );
		foreach ( $dimensions as $part => $dimension ) { $element->add_render_attribute( '_wrapper', 'style', '--sn-layout-' . $device . '-' . $property . '-' . $part . ':' . $dimension . ';' ); }
	}

	private static function responsive_attributes( $element ) {
		$raw = $element->get_data();
		$settings = isset( $raw['settings'] ) && is_array( $raw['settings'] ) ? $raw['settings'] : array();
		$map = self::template_nodes();
		$template = $map[ $element->get_id() ] ?? null;
		if ( ! $template || ( $raw['elType'] ?? '' ) !== $template['type'] || ( $raw['widgetType'] ?? '' ) !== $template['widget'] ) { return; }
		if ( $template['classes'] ) { $element->add_render_attribute( '_wrapper', 'class', $template['classes'] ); }
		if ( 'image' === $template['widget'] && in_array( 'sn-header-logo', $template['classes'], true ) ) { self::compact_logo_attributes( $element, $settings ); }
		$defaults = $template['settings'];
		$saved_classes = preg_split( '/\s+/', trim( ( $settings['css_classes'] ?? '' ) . ' ' . ( $settings['_css_classes'] ?? '' ) ) );
		// The original generated header had 58/42 phone columns with too little
		// room for its enlarged logo. Only that recognizable stock pair is upgraded.
		foreach ( array( 'sn-header-main' => array( 76, 58 ), 'sn-header-actions' => array( 24, 42 ) ) as $class => $stock ) {
			if ( in_array( $class, $template['classes'], true ) && ! in_array( $class, $saved_classes, true ) && self::dimension( $settings['width'] ?? array() ) === $stock[0] . '%' && self::dimension( $settings['width_mobile'] ?? array() ) === $stock[1] . '%' ) { self::fallback( $element, 'mobile', 'width', '100%' ); }
		}
		foreach ( array( 'tablet', 'mobile' ) as $device ) {
			if ( 'container' === $template['type'] ) {
				foreach ( array( 'width' => 'width', 'min-height' => 'min_height' ) as $property => $setting ) {
					$key = $setting . '_' . $device;
					if ( ! self::has_setting( $settings, $key ) && self::has_setting( $defaults, $key ) ) {
						$value = self::dimension( $defaults[ $key ] );
						if ( '' !== $value ) { self::fallback( $element, $device, $property, $value ); }
					}
				}
				foreach ( array( 'direction' => 'flex_direction', 'wrap' => 'flex_wrap' ) as $property => $setting ) {
					$key = $setting . '_' . $device;
					$allowed = 'direction' === $property ? array( 'row', 'row-reverse', 'column', 'column-reverse' ) : array( 'wrap', 'nowrap', 'wrap-reverse' );
					if ( ! self::has_setting( $settings, $key ) && isset( $defaults[ $key ] ) && in_array( $defaults[ $key ], $allowed, true ) ) { self::fallback( $element, $device, $property, $defaults[ $key ] ); }
				}
				foreach ( array( 'gap' => 'flex_gap', 'padding' => 'padding' ) as $property => $setting ) {
					$key = $setting . '_' . $device;
					if ( ! self::has_setting( $settings, $key ) && isset( $defaults[ $key ] ) ) { self::spacing_fallback( $element, $device, $property, $defaults[ $key ] ); }
				}
			}
			if ( 'widget' === $template['type'] && ! self::has_setting( $settings, '_element_custom_width_' . $device ) && ! self::has_setting( $settings, '_element_width_' . $device ) && self::has_setting( $defaults, '_element_custom_width_' . $device ) ) {
				$value = self::dimension( $defaults[ '_element_custom_width_' . $device ] );
				if ( '' !== $value ) { self::fallback( $element, $device, 'widget-width', $value ); }
			}
			foreach ( array( 'width', 'height' ) as $property ) {
				$key = $property . '_' . $device;
				if ( 'image' === $template['widget'] && ! self::has_setting( $settings, $key ) && self::has_setting( $defaults, $key ) ) {
					$value = self::dimension( $defaults[ $key ] );
					if ( '' !== $value ) { self::fallback( $element, $device, 'image-' . $property, $value ); }
				}
			}
		}
	}

	public static function legacy_header_logo( $content, $widget ) {
		if ( 'image' !== $widget->get_name() || ! class_exists( '\WP_HTML_Tag_Processor' ) || ( ! self::$active_headers && ! self::in_document_scope( $widget, true ) ) ) { return $content; }
		$settings = $widget->get_settings_for_display();
		$desktop = isset( $settings['width'] ) ? $settings['width'] : array();
		$mobile = isset( $settings['width_mobile'] ) ? $settings['width_mobile'] : array();
		if ( 'px' !== ( $desktop['unit'] ?? '' ) || 112.0 !== (float) ( $desktop['size'] ?? 0 ) || 'px' !== ( $mobile['unit'] ?? '' ) || 90.0 !== (float) ( $mobile['size'] ?? 0 ) ) { return $content; }
		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag( 'IMG' ) ) { $tags->add_class( 'sn-header-logo-compatible' ); }
		return $tags->get_updated_html();
	}
}
