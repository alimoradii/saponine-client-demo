<?php
/** Dashboard editor for the existing home-page hero widget. */
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Slider_Admin {
	const PAGE = 'saponine-slider';

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_saponine_save_slider', array( __CLASS__, 'save' ) );
	}

	public static function menu() {
		add_theme_page( 'تنظیمات اسلایدر ساپونین', 'اسلایدر ساپونین', 'manage_options', self::PAGE, array( __CLASS__, 'page' ) );
	}

	public static function assets( $hook ) {
		if ( 'appearance_page_' . self::PAGE !== $hook ) { return; }
		wp_enqueue_media();
		wp_enqueue_style( 'saponine-admin', SAPONINE_STUDIO_URL . 'assets/css/admin.css', array(), SAPONINE_STUDIO_VERSION );
		wp_enqueue_style( 'saponine-slider-admin', SAPONINE_STUDIO_URL . 'assets/css/slider-admin.css', array( 'saponine-admin' ), SAPONINE_STUDIO_VERSION );
		wp_enqueue_script( 'saponine-slider-admin', SAPONINE_STUDIO_URL . 'assets/js/slider-admin.js', array( 'media-editor' ), SAPONINE_STUDIO_VERSION, true );
	}

	private static function schema() {
		$widget = \Elementor\Plugin::instance()->widgets_manager->get_widget_types( 'saponine-hero-slider' );
		if ( ! $widget ) { return array(); }
		$controls = $widget->get_controls();
		$fields = array();
		$sections = array( 'slides_section', 'hero_motion', 'hero_layout_style', 'hero_type_style' );
		foreach ( $controls as $key => $field ) {
			if ( ! in_array( $field['section'] ?? '', $sections, true ) || 'slides' === $key ) { continue; }
			$fields[ $key ] = $field;
			// Elementor can materialize device variants lazily in the dashboard.
			if ( ! empty( $field['is_responsive'] ) && ! preg_match( '/_(tablet|mobile)$/', $key ) ) {
				foreach ( array( 'tablet', 'mobile' ) as $device ) {
					$name = $key . '_' . $device;
					$fields[ $name ] = $controls[ $name ] ?? $field;
					$fields[ $name ]['name'] = $name;
					$fields[ $name ]['default'] = $field[ $device . '_default' ] ?? '';
				}
			}
		}
		unset( $controls['slides']['fields']['_id'] );
		return array( 'global' => $fields, 'row' => $controls['slides']['fields'], 'slides' => $controls['slides']['default'] );
	}

	private static function value( $settings, $key, $field ) {
		$value = $settings[ $key ] ?? ( $field['default'] ?? '' );
		$overrides = (array) ( $settings['sn_slider_overrides'] ?? array() );
		if ( 'min_height_mobile' === $key && ! in_array( $key, $overrides, true ) &&
			( ! is_numeric( $value['size'] ?? '' ) || ( 720.0 === (float) ( $value['size'] ?? 0 ) && 'px' === ( $value['unit'] ?? 'px' ) ) ) ) {
			return array( 'size' => 400, 'unit' => 'px' );
		}
		if ( 'cover_position_mobile' === $key && ! in_array( $key, $overrides, true ) && in_array( $value, array( '', '25% bottom' ), true ) ) { return '8% center'; }
		if ( 'hero_height_mobile' === $key && '' === (string) ( $value['size'] ?? '' ) && '' === (string) ( $settings['hero_height_tablet']['size'] ?? '' ) ) {
			$height = $settings['hero_height'] ?? array( 'unit' => 'svh', 'size' => 100 );
			if ( 'svh' === ( $height['unit'] ?? '' ) && 100.0 === (float) ( $height['size'] ?? 0 ) ) { return array( 'size' => 94, 'unit' => 'svh' ); }
		}
		return $value;
	}

	private static function group( $key, $field ) {
		$device = preg_match( '/_(tablet|mobile)$/', $key, $match ) ? $match[1] : 'desktop';
		$device_label = array( 'desktop' => 'دسکتاپ', 'tablet' => 'تبلت', 'mobile' => 'موبایل' )[ $device ];
		if ( ! empty( $field['groupPrefix'] ) ) {
			$names = array( 'heading_typography_' => 'عنوان', 'eyebrow_typography_' => 'زیرعنوان', 'caption_typography_' => 'توضیح', 'button_typography_' => 'دکمه' );
			return 'قلم ' . ( $names[ $field['groupPrefix'] ] ?? '' ) . ' · ' . $device_label;
		}
		$labels = array( 'slides_section' => 'نشانگرها و دکمه‌های اسلایدر', 'hero_motion' => 'پخش و حرکت', 'hero_layout_style' => 'چیدمان · ' . $device_label, 'hero_type_style' => 'رنگ‌ها' );
		return $labels[ $field['section'] ];
	}

	private static function render_field( $key, $field, $value ) {
		$type = $field['type'];
		$label = $field['label'] ?? $key;
		if ( ! empty( $field['groupPrefix'] ) ) {
			$typography_labels = array( 'typography' => 'قلم سفارشی', 'font_family' => 'خانوادهٔ قلم', 'font_size' => 'اندازهٔ قلم', 'font_weight' => 'وزن قلم', 'text_transform' => 'حالت حروف لاتین', 'font_style' => 'سبک قلم', 'text_decoration' => 'خط متن', 'line_height' => 'ارتفاع خط', 'letter_spacing' => 'فاصلهٔ حروف', 'word_spacing' => 'فاصلهٔ کلمه‌ها' );
			$part = preg_replace( '/_(tablet|mobile)$/', '', substr( $key, strlen( $field['groupPrefix'] ) ) );
			$label = $typography_labels[ $part ] ?? $label;
		}
		$id = wp_unique_id( 'sn-slider-input-' );
		?>
		<div class="sn-slider-field" data-sn-field="<?php echo esc_attr( $key ); ?>" data-sn-kind="<?php echo esc_attr( $type ); ?>">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<?php if ( 'media' === $type ) :
			$url = is_array( $value ) ? ( $value['url'] ?? '' ) : ''; ?>
			<div class="sn-slider-media"><img src="<?php echo $url ? esc_url( $url ) : 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs='; ?>" alt="" <?php echo $url ? '' : 'hidden'; ?>>
			<input type="hidden" data-sn-part="id" value="<?php echo absint( $value['id'] ?? 0 ); ?>">
			<input id="<?php echo esc_attr( $id ); ?>" type="text" dir="ltr" data-sn-part="url" placeholder="https://…" value="<?php echo esc_attr( $url ); ?>">
			<div><button class="button" type="button" data-sn-pick>انتخاب از رسانه‌ها</button> <button class="button-link" type="button" data-sn-clear>پاک کردن تصویر</button></div></div>
		<?php elseif ( 'url' === $type ) : ?>
			<input id="<?php echo esc_attr( $id ); ?>" type="text" dir="ltr" data-sn-part="url" value="<?php echo esc_attr( $value['url'] ?? '' ); ?>">
			<label><input type="checkbox" data-sn-part="is_external" <?php checked( ! empty( $value['is_external'] ) ); ?>> باز کردن در زبانهٔ جدید</label>
			<label><input type="checkbox" data-sn-part="nofollow" <?php checked( ! empty( $value['nofollow'] ) ); ?>> پیوند nofollow</label>
		<?php elseif ( 'slider' === $type ) :
			$value = is_array( $value ) ? $value : array();
			$units = self::units( $field );
			$unit  = $value['unit'] ?? reset( $units );
			?>
			<div class="sn-slider-measure"><input id="<?php echo esc_attr( $id ); ?>" type="number" step="any" <?php if ( ! self::allows_negative( $key ) ) { echo 'min="0"'; } ?> data-sn-part="size" placeholder="پیش‌فرض" value="<?php echo esc_attr( $value['size'] ?? '' ); ?>">
			<select aria-label="<?php echo esc_attr( 'واحد ' . $label ); ?>" data-sn-part="unit"><?php foreach ( $units as $item ) : ?>
				<option <?php selected( $unit, $item ); ?>><?php echo esc_html( $item ); ?></option><?php endforeach; ?></select></div>
		<?php elseif ( in_array( $type, array( 'switcher', 'popover_toggle' ), true ) ) : ?>
			<input id="<?php echo esc_attr( $id ); ?>" type="checkbox" value="<?php echo esc_attr( $field['return_value'] ?? 'yes' ); ?>" <?php checked( ! empty( $value ) ); ?>>
		<?php elseif ( 'select' === $type ) : ?>
			<select id="<?php echo esc_attr( $id ); ?>"><option value="">پیش‌فرض</option><?php foreach ( self::options( $key, $field ) as $item => $name ) :
				if ( '' === (string) $item ) { continue; } ?>
				<option value="<?php echo esc_attr( $item ); ?>" <?php selected( $value, $item ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select>
		<?php elseif ( 'textarea' === $type ) : ?>
			<textarea id="<?php echo esc_attr( $id ); ?>" dir="auto" rows="2"><?php echo esc_textarea( $value ); ?></textarea>
		<?php elseif ( 'number' === $type ) : ?>
			<input id="<?php echo esc_attr( $id ); ?>" type="number" step="any" <?php if ( ! self::allows_negative( $key ) ) { echo 'min="0"'; } ?> value="<?php echo esc_attr( $value ); ?>">
			<?php if ( ! empty( $field['description'] ) ) : ?><small class="description"><?php echo esc_html( $field['description'] ); ?></small><?php endif; ?>
		<?php else : ?>
			<div class="sn-slider-inline"><input id="<?php echo esc_attr( $id ); ?>" type="text" dir="auto" value="<?php echo esc_attr( is_scalar( $value ) ? $value : '' ); ?>">
			<?php if ( 'color' === $type ) : ?><input type="color" data-sn-color aria-label="<?php echo esc_attr( 'انتخاب ' . $label ); ?>" value="<?php echo esc_attr( preg_match( '/^#[0-9a-f]{6}$/i', (string) $value ) ? $value : '#7d4697' ); ?>"><?php endif; ?></div>
		<?php endif; ?>
		</div>
		<?php
	}

	private static function units( $field ) {
		$units = array_values( array_diff( $field['size_units'] ?? array( 'px' ), array( 'custom' ) ) );
		return $units ?: array( 'px' );
	}

	private static function options( $key, $field ) {
		$options = $field['options'] ?? array();
		if ( 'cover_position_mobile' === $key ) { $options['8% center'] = 'قاب موبایل ساپونین'; }
		return $options;
	}

	private static function slide_card( $row, $index, $fields ) {
		?>
		<details class="sn-slider-card" data-sn-slide data-sn-index="<?php echo esc_attr( $index ); ?>" <?php echo 0 === $index ? 'open' : ''; ?>>
			<summary><span data-sn-count></span><span data-sn-title><?php echo esc_html( trim( ( $row['title'] ?? '' ) . ' ' . ( $row['highlight'] ?? '' ) ) ); ?></span></summary>
			<div class="sn-slider-order"><button type="button" class="button" data-sn-move="up">انتقال به بالا</button> <button type="button" class="button" data-sn-move="down">انتقال به پایین</button> <button class="button-link-delete" type="button" data-sn-remove>حذف اسلاید</button></div>
			<div class="sn-slider-grid"><?php foreach ( $fields as $key => $field ) { self::render_field( $key, $field, $row[ $key ] ?? ( $field['default'] ?? '' ) ); } ?></div>
		</details>
		<?php
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$targets = Slider_Document::targets();
		$post_id = isset( $_GET['slider_post'] ) ? absint( $_GET['slider_post'] ) : (int) array_key_first( $targets );
		$widget_id = isset( $_GET['widget'] ) ? sanitize_key( wp_unslash( $_GET['widget'] ) ) : '';
		?>
		<div class="wrap sn-admin sn-slider-admin" dir="rtl">
		<h1>تنظیمات اسلایدر ساپونین</h1>
		<?php if ( ! Plugin::elementor_ready() || ! $targets ) : ?>
			<p>برای مدیریت اسلایدر، المنتور را فعال کنید و صفحهٔ اصلی ساپونین را وارد کنید.</p><a class="button" href="<?php echo esc_url( admin_url( 'themes.php?page=saponine-studio' ) ); ?>">راه‌اندازی ساپونین استودیو</a></div>
			<?php return;
		endif;
		$state = Slider_Document::read( $post_id, $widget_id );
		self::target_picker( $targets, $post_id, $state );
		if ( is_wp_error( $state ) ) { echo '<div class="notice notice-warning"><p>' . esc_html( $state->get_error_message() ) . '</p></div></div>'; return; }
		$schema = self::schema();
		if ( ! $schema ) { echo '<p>ابزار اسلایدر در المنتور در دسترس نیست.</p></div>'; return; }
		$settings = $state['settings'];
		$groups = array();
		foreach ( $schema['global'] as $key => $field ) { $groups[ self::group( $key, $field ) ][ $key ] = $field; }
		?>
		<?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success"><p>تنظیمات اسلایدر ذخیره شد.</p></div><?php endif; ?>
		<p><a href="<?php echo esc_url( get_preview_post_link( $post_id ) ); ?>" target="_blank" rel="noopener">پیش‌نمایش صفحه</a> · <a href="<?php echo esc_url( Admin::editor_url( $post_id ) ); ?>">ویرایش صفحه با المنتور</a></p>
		<form id="sn-slider-form" method="post" novalidate action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="saponine_save_slider">
			<input type="hidden" name="slider_post" value="<?php echo absint( $post_id ); ?>">
			<input type="hidden" name="widget" value="<?php echo esc_attr( $state['id'] ); ?>">
			<input type="hidden" name="revision" value="<?php echo esc_attr( $state['revision'] ); ?>">
			<input type="hidden" name="sn_payload" value="">
			<?php wp_nonce_field( 'saponine_save_slider_' . $post_id ); ?>
			<noscript><p>برای ویرایش اسلایدها، جاوااسکریپت مرورگر را فعال کنید.</p></noscript>
			<section class="sn-admin-card"><h2>اسلایدها</h2>
			<div data-sn-slides><?php foreach ( $settings['slides'] ?? $schema['slides'] as $index => $row ) { self::slide_card( $row, $index, $schema['row'] ); } ?></div>
			<template data-sn-template><?php self::slide_card( array(), '', $schema['row'] ); ?></template>
			<button class="button" type="button" data-sn-add>افزودن اسلاید</button></section>
			<section class="sn-admin-card"><h2>پخش و ظاهر اسلایدر</h2>
			<?php foreach ( $groups as $name => $fields ) : ?><details class="sn-slider-card" data-sn-group>
				<summary><?php echo esc_html( $name ); ?></summary><div class="sn-slider-grid">
				<?php foreach ( $fields as $key => $field ) { self::render_field( $key, $field, self::value( $settings, $key, $field ) ); } ?>
				</div></details><?php endforeach; ?></section>
			<div class="sn-slider-save"><?php submit_button( 'ذخیره تنظیمات اسلایدر', 'primary', 'submit', false ); ?></div>
		</form>
		<?php do_action( 'saponine_slider_companions', $post_id ); ?>
		</div>
		<?php
	}

	private static function target_picker( $targets, $post_id, $state ) {
		$widgets = is_wp_error( $state ) ? array() : ( $state['widgets'] ?? array() );
		if ( count( $targets ) > 1 ) { ?>
			<form method="get" class="sn-slider-picker"><input type="hidden" name="page" value="saponine-slider"><label>صفحهٔ اصلی <select name="slider_post">
			<?php foreach ( $targets as $id => $label ) : ?><option value="<?php echo absint( $id ); ?>" <?php selected( $id, $post_id ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
			</select></label> <button class="button">انتخاب صفحه</button></form>
		<?php }
		if ( count( $widgets ) > 1 ) { ?>
			<form method="get" class="sn-slider-picker"><input type="hidden" name="page" value="saponine-slider"><input type="hidden" name="slider_post" value="<?php echo absint( $post_id ); ?>"><label>اسلایدر <select name="widget">
			<?php foreach ( $widgets as $id => $label ) : ?><option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $state['id'] ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
			</select></label> <button class="button">انتخاب اسلایدر</button></form>
		<?php }
	}

	private static function invalid( $field ) {
		return new \WP_Error( 'sn_slider_value', sprintf( 'مقدار «%s» معتبر نیست.', wp_strip_all_tags( $field['label'] ?? $field['name'] ) ) );
	}

	private static function allows_negative( $key ) {
		return in_array( $key, array( 'float_distance', 'text_delay' ), true ) ||
			(bool) preg_match( '/(?:^text_inset|_letter_spacing|_word_spacing)(?:_[a-z0-9_]+)?$/', $key );
	}

	private static function valid_number( $key, $value ) {
		return is_numeric( $value ) && is_finite( (float) $value ) &&
			( (float) $value >= 0 || self::allows_negative( $key ) );
	}

	/** Validate the few modified fields against the widget's own controls. */
	private static function clean( $key, $field, $value, $old ) {
		$type = $field['type'];
		if ( in_array( $type, array( 'url', 'media', 'slider' ), true ) ) {
			if ( ! is_array( $value ) ) { return self::invalid( $field ); }
			$old = is_array( $old ) ? $old : array();
			if ( 'slider' === $type ) {
				$unit = $value['unit'] ?? '';
				$size = $value['size'] ?? '';
				if ( ! in_array( $unit, self::units( $field ), true ) || ( '' !== $size && ! self::valid_number( $key, $size ) ) ) { return self::invalid( $field ); }
				return '' === $size ? null : array_merge( $old, array( 'size' => (float) $size, 'unit' => $unit ) );
			}
			$raw = $value['url'] ?? '';
			if ( ! is_string( $raw ) ) { return self::invalid( $field ); }
			$protocols = 'media' === $type ? array( 'https', 'http' ) : array( 'https', 'http', 'mailto', 'tel' );
			$url = esc_url_raw( trim( $raw ), $protocols );
			if ( '' !== trim( $raw ) && '' === $url ) { return self::invalid( $field ); }
			if ( 'media' === $type ) {
				$id = absint( $value['id'] ?? 0 );
				if ( $id ) {
					if ( ! wp_attachment_is_image( $id ) ) { return self::invalid( $field ); }
					$url = wp_get_attachment_url( $id );
				}
				return array_merge( $old, array( 'id' => $id, 'url' => $url ) );
			}
			return array_merge( $old, array( 'url' => $url, 'is_external' => ! empty( $value['is_external'] ) ? 'on' : '', 'nofollow' => ! empty( $value['nofollow'] ) ? 'on' : '' ) );
		}
		if ( ! is_scalar( $value ) ) { return self::invalid( $field ); }
		$value = (string) $value;
		if ( 'select' === $type ) {
			if ( '' === $value ) { return null; }
			return array_key_exists( $value, self::options( $key, $field ) ) ? $value : self::invalid( $field );
		}
		if ( 'switcher' === $type || 'popover_toggle' === $type ) {
			$on = $field['return_value'] ?? 'yes';
			return in_array( $value, array( '', $on ), true ) ? $value : self::invalid( $field );
		}
		if ( 'number' === $type ) {
			if ( '' === $value ) { return null; }
			return self::valid_number( $key, $value ) ? (float) $value : self::invalid( $field );
		}
		if ( 'color' === $type ) {
			$value = trim( $value );
			if ( '' === $value ) { return null; }
			if ( preg_match( '/\Avar\(--sn-(?:primary|secondary|bg|text|muted|border|accent)\)\z/', $value ) ) { return $value; }
			return preg_match( '/\A(?:#(?:[a-f\d]{3,4}|[a-f\d]{6}|[a-f\d]{8})|(?:rgb|hsl)a?\([\d.%,\s\/+-]+\)|transparent|currentColor)\z/i', $value ) ? $value : self::invalid( $field );
		}
		if ( 'font' === $type ) {
			return preg_match( '/\A[\p{L}\p{N} _-]*\z/u', $value ) ? $value : self::invalid( $field );
		}
		return 'textarea' === $type ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
	}

	private static function patch( $existing, $delta, $schema ) {
		if ( ! is_array( $delta ) ) { return new \WP_Error( 'sn_bad_slider', 'اطلاعات فرم قابل خواندن نیست.' ); }
		foreach ( $delta as $key => $input ) {
			if ( ! isset( $schema[ $key ] ) ) { return new \WP_Error( 'sn_bad_field', 'تنظیم اسلایدر شناخته نشد.' ); }
			$value = self::clean( $key, $schema[ $key ], $input, $existing[ $key ] ?? null );
			if ( is_wp_error( $value ) ) { return $value; }
			if ( null === $value ) { unset( $existing[ $key ] ); }
			else { $existing[ $key ] = $value; }
			unset( $existing['__globals__'][ $key ], $existing['__dynamic__'][ $key ] );
			// Elementor enables individual font controls through their group toggle.
			$prefix = $schema[ $key ]['groupPrefix'] ?? '';
			if ( $prefix && $prefix . 'typography' !== $key && ! array_key_exists( $prefix . 'typography', $delta ) && null !== $value ) {
				$existing[ $prefix . 'typography' ] = 'custom';
				unset( $existing['__globals__'][ $prefix . 'typography' ] );
			}
			if ( in_array( $key, array( 'min_height_mobile', 'cover_position_mobile' ), true ) ) {
				$flags = array_diff( (array) ( $existing['sn_slider_overrides'] ?? array() ), array( $key ) );
				if ( null !== $value ) { $flags[] = $key; }
				$existing['sn_slider_overrides'] = array_values( $flags );
			}
		}
		return $existing;
	}

	public static function save() {
		$post_id = absint( $_POST['slider_post'] ?? 0 );
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) ) { wp_die( 'اجازهٔ ویرایش این صفحه را ندارید.', '', array( 'response' => 403 ) ); }
		check_admin_referer( 'saponine_save_slider_' . $post_id );
		if ( ! Plugin::elementor_ready() ) { wp_die( 'المنتور در دسترس نیست.' ); }
		$id = sanitize_key( wp_unslash( $_POST['widget'] ?? '' ) );
		$state = Slider_Document::read( $post_id, $id );
		if ( is_wp_error( $state ) ) { wp_die( $state ); }
		$revision = sanitize_text_field( wp_unslash( $_POST['revision'] ?? '' ) );
		if ( ! hash_equals( $state['revision'], $revision ) ) { wp_die( 'اسلایدر از زمان باز کردن فرم تغییر کرده است. صفحهٔ تنظیمات را دوباره باز کنید.', '', array( 'response' => 409, 'back_link' => true ) ); }
		$input = isset( $_POST['sn_payload'] ) && is_string( $_POST['sn_payload'] ) ? json_decode( wp_unslash( $_POST['sn_payload'] ), true ) : null;
		$schema = self::schema();
		if ( ! $schema || ! is_array( $input ) || ! is_array( $input['settings'] ?? null ) ) { wp_die( 'اطلاعات فرم اسلایدر کامل دریافت نشد.', '', array( 'response' => 400, 'back_link' => true ) ); }
		$settings = self::patch( $state['settings'], $input['settings'], $schema['global'] );
		if ( is_wp_error( $settings ) ) { wp_die( $settings, '', array( 'response' => 400, 'back_link' => true ) ); }
		if ( isset( $input['slides'] ) ) {
			if ( ! is_array( $input['slides'] ) ) { wp_die( 'فهرست اسلایدها معتبر نیست.' ); }
			$current = array_values( $settings['slides'] ?? $schema['slides'] );
			$slides = array();
			$seen = array();
			foreach ( $input['slides'] as $item ) {
				if ( ! is_array( $item ) || ! array_key_exists( 'index', $item ) ) { wp_die( 'اسلاید معتبر نیست.' ); }
				$index = $item['index'];
				if ( null === $index ) {
					$row = array( '_id' => substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 8 ) );
				} elseif ( is_int( $index ) && isset( $current[ $index ] ) && ! isset( $seen[ $index ] ) ) {
					$row = $current[ $index ];
					$seen[ $index ] = true;
				} else { wp_die( 'ترتیب اسلایدها معتبر نیست.' ); }
				$row = self::patch( $row, $item['set'] ?? array(), $schema['row'] );
				if ( is_wp_error( $row ) ) { wp_die( $row, '', array( 'response' => 400, 'back_link' => true ) ); }
				$slides[] = $row;
			}
			$settings['slides'] = $slides;
		}
		$result = Slider_Document::save( $post_id, $id, $revision, $settings );
		if ( is_wp_error( $result ) ) { wp_die( $result, '', array( 'back_link' => true ) ); }
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'slider_post' => $post_id, 'widget' => $id, 'saved' => '1' ), admin_url( 'themes.php' ) ) );
		exit;
	}
}
