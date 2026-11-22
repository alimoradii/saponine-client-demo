<?php
namespace Saponine\Studio;

defined( 'ABSPATH' ) || exit;

/** The overlaid desktop strip and seal already live in Elementor. The mobile dock belongs to its home page. */
final class Slider_Companions {
	const DOCK_META = '_saponine_slider_mobile_dock';

	public static function boot() {
		add_action( 'saponine_slider_companions', array( __CLASS__, 'render' ) );
		add_action( 'admin_post_saponine_slider_companion', array( __CLASS__, 'save' ) );
	}

	private static function header_id( $page_id ) {
		$id = absint( Plugin::settings()['header_id'] );
		if ( $id && function_exists( 'pll_get_post_language' ) && function_exists( 'pll_get_post' ) ) {
			$lang = pll_get_post_language( $page_id );
			$id = $lang ? Multilingual::translated_post_id( $id, $lang ) : $id;
		}
		return $id;
	}

	private static function widget( $nodes, $class ) {
		$found = Slider_Document::find( $nodes, static function( $node ) use ( $class ) {
			$classes = ( $node->settings->css_classes ?? '' ) . ' ' . ( $node->settings->_css_classes ?? '' );
			return in_array( $class, preg_split( '/\s+/', $classes ), true );
		} );
		return reset( $found );
	}

	private static function strip_rows( $strip ) {
		$rows = array();
		foreach ( $strip->elements ?? array() as $item ) {
			$head = null;
			$photo = null;
			foreach ( $item->elements ?? array() as $part ) {
				if ( 'heading' === ( $part->widgetType ?? '' ) && ! $head ) { $head = $part; }
				if ( 'image' === ( $part->widgetType ?? '' ) && ! $photo ) { $photo = $part; }
			}
			if ( $head ) { $rows[ $item->id ] = array( 'item' => $item, 'heading' => $head, 'photo' => $photo ); }
		}
		return $rows;
	}

	private static function upgraded_strip( $strip, $header ) {
		if ( count( $strip->elements ?? array() ) !== 4 ) { return $strip; }
		$data = json_decode( wp_json_encode( array( $strip ) ), true );
		$updated = Brand_Strip::upgrade_stock_layout( $data, $header );
		return $updated === $data ? $strip : json_decode( wp_json_encode( $updated[0] ) );
	}

	private static function displayed_links( $strip, $header ) {
		$data = json_decode( wp_json_encode( array( $strip ) ), true );
		$data = Portal::link_documents( $data, $header );
		$rows = self::strip_rows( json_decode( wp_json_encode( $data[0] ) ) );
		return array_map( static function( $row ) { return $row['heading']->settings->link->url ?? ''; }, $rows );
	}

	public static function mobile_rows( $id ) {
		$saved = (array) get_post_meta( $id, self::DOCK_META, true );
		$rows = array();
		foreach ( Portal::dock_defaults() as $key => $value ) {
			$base = array( 'label' => $value[0], 'url' => $value[1], 'icon' => SAPONINE_STUDIO_URL . 'assets/icons/' . $key . '.svg', 'hidden' => false, 'order' => count( $rows ) + 1 );
			$rows[ $key ] = array_merge( $base, (array) ( $saved[ $key ] ?? array() ) );
		}
		uasort( $rows, static function ( $a, $b ) { return $a['order'] <=> $b['order']; } );
		return $rows;
	}

	private static function form( $page, $group, $revision, $title ) {
		?>
		<details class="sn-slider-related postbox"><summary><?php echo esc_html( $title ); ?></summary>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="saponine_slider_companion">
			<input type="hidden" name="page_id" value="<?php echo absint( $page ); ?>">
			<input type="hidden" name="group" value="<?php echo esc_attr( $group ); ?>">
			<input type="hidden" name="revision" value="<?php echo esc_attr( $revision ); ?>">
			<?php wp_nonce_field( 'slider_companion_' . $group . '_' . $page ); ?>
		<?php
	}

	private static function end_form() {
		submit_button( 'ذخیرهٔ این بخش', 'secondary' );
		echo '</form></details>';
	}

	private static function field( $name, $label, $value, $type = 'text' ) {
		echo '<span class="sn-companion-field"><label>' . esc_html( $label );
		printf( '<input type="%s" name="%s" value="%s" %s></label>', 'number' === $type ? 'number' : 'text', esc_attr( $name ), esc_attr( $value ),
			'number' === $type ? ( 'duration' === $name ? 'min="0" step="any"' : 'step="any"' ) : ( in_array( $type, array( 'url', 'media' ), true ) ? 'dir="ltr"' : 'dir="auto"' ) );
		if ( 'media' === $type ) { echo '<button class="button-link" type="button" data-sn-companion-media>انتخاب از رسانه‌ها</button>'; }
		echo '</span>';
	}

	private static function editor_link( $id, $text ) {
		printf( '<p><a href="%s">%s</a></p>', esc_url( Admin::editor_url( $id ) ), esc_html( $text ) );
	}

	public static function render( $page ) {
		$home = Multilingual::source_documents()['home'];
		$homes = array_filter( array( $home, absint( get_option( 'page_on_front' ) ) ) );
		if ( function_exists( 'pll_get_post_translations' ) ) {
			foreach ( $homes as $id ) { $homes = array_merge( $homes, array_values( (array) pll_get_post_translations( $id ) ) ); }
		}
		$home_chrome = in_array( (int) $page, array_map( 'intval', $homes ), true );
		$header = self::header_id( $page );
		$seal = self::widget( Slider_Document::nodes( $page ), 'sn-wave-seal' );
		if ( ! $home_chrome && ! $seal ) { return; }
		echo '<section dir="rtl"><h2>اجزای روی اسلایدر</h2>';
		if ( isset( $_GET['sn_component_saved'] ) ) { echo '<div class="notice notice-success"><p>تغییرات این بخش ذخیره شد.</p></div>'; }
		if ( $home_chrome && $header && current_user_can( 'edit_post', $header ) ) {
			$strip = self::widget( Slider_Document::nodes( $header ), 'sn-brand-strip' );
			if ( $strip ) {
				self::form( $page, 'strip', Slider_Document::token( $strip ), 'دکمه‌های برند روی اسلایدر دسکتاپ' );
				$strip = self::upgraded_strip( $strip, $header );
				$links = self::displayed_links( $strip, $header );
				echo '<p>این دکمه‌ها در سربرگ مشترک سایت ذخیره می‌شوند. شمارهٔ ترتیب از راست به چپ است.</p>';
				$position = 0;
				foreach ( self::strip_rows( $strip ) as $key => $row ) {
					$s = $row['heading']->settings;
					echo '<fieldset><legend class="screen-reader-text">' . esc_html( $s->title ?? '' ) . '</legend>';
					self::field( "rows[$key][order]", 'ترتیب', ++$position, 'number' );
					self::field( "rows[$key][label]", 'عنوان', $s->title ?? '' );
					self::field( "rows[$key][url]", 'پیوند', $links[ $key ], 'url' );
					if ( $row['photo'] ) { self::field( "rows[$key][image]", 'تصویر دکمه', $row['photo']->settings->image->url ?? '', 'media' ); }
					echo '</fieldset>';
				}
				self::editor_link( $header, 'ویرایش آیکون، رنگ و چیدمان دکمه‌های سربرگ در المنتور' );
				self::end_form();
			}
		}
		if ( $seal ) {
			$s = $seal->settings;
			self::form( $page, 'seal', Slider_Document::token( $seal ), 'نشان گردان پایین اسلایدر' );
			echo '<p><label>متن وسط نشان<br><textarea rows="2" name="center" dir="auto">' . esc_textarea( $s->center_text ?? "SAPONINE\nPHARMA" ) . '</textarea></label></p>';
			foreach ( $s->items ?? array() as $index => $item ) { self::field( "items[$index]", 'متن دور نشان', $item->text ?? '' ); }
			self::field( 'duration', 'زمان هر دور (ثانیه)', $s->duration ?? 28, 'number' );
			echo '<label><input type="checkbox" name="animate" value="1" ' . checked( $s->animate ?? 'yes', 'yes', false ) . '> چرخش نشان</label>';
			self::editor_link( $page, 'ویرایش اندازه، جای نشان و رنگ‌ها در المنتور' );
			self::end_form();
		}
		if ( $home_chrome ) {
			self::form( $page, 'dock', Slider_Document::token( get_post_meta( $page, self::DOCK_META, true ) ), 'نوار پنج‌دکمه‌ای موبایل' );
			echo '<p>پیوند خالیِ دکمهٔ جستجو، جستجوی داخل سایت را باز می‌کند.</p>';
			foreach ( self::mobile_rows( $page ) as $key => $row ) {
				echo '<fieldset><legend class="screen-reader-text">' . esc_html( $row['label'] ) . '</legend>';
				self::field( "rows[$key][order]", 'ترتیب', $row['order'], 'number' );
				self::field( "rows[$key][label]", 'عنوان', $row['label'] );
				self::field( "rows[$key][url]", 'پیوند', $row['url'], 'url' );
				self::field( "rows[$key][icon]", 'آیکون', $row['icon'], 'media' );
				echo '<label><input type="checkbox" name="rows[' . esc_attr( $key ) . '][hidden]" value="1" ' . checked( $row['hidden'], true, false ) . '> پنهان</label></fieldset>';
			}
			self::end_form();
		}
		echo '</section>';
	}

	private static function url( $value ) {
		if ( ! is_string( $value ) ) { return ''; }
		return esc_url_raw( trim( $value ), array( 'http', 'https', 'mailto', 'tel' ) );
	}

	private static function number( $input ) {
		if ( ! is_numeric( $input ) || ! is_finite( (float) $input ) ) { return null; }
		return $input + 0;
	}

	private static function save_strip( $strip, $posted, $links ) {
		$rows = self::strip_rows( $strip );
		if ( array_diff_key( $rows, $posted ) ) { return new \WP_Error( 'rows', 'اطلاعات دکمه‌ها کامل نرسید.' ); }
		$rank = array();
		foreach ( $rows as $key => $row ) {
			$r = $posted[ $key ];
			if ( ! isset( $r['label'], $r['url'], $r['order'] ) ) { return new \WP_Error( 'row', 'عنوان، پیوند و ترتیب هر دکمه لازم است.' ); }
			$s = $row['heading']->settings;
			if ( $r['label'] !== ( $s->title ?? '' ) ) { $s->title = sanitize_text_field( $r['label'] ); }
			if ( $r['url'] !== $links[ $key ] ) {
				$s->link = (object) ( $s->link ?? array() );
				$s->link->url = self::url( $r['url'] );
			}
			if ( $row['photo'] && isset( $r['image'] ) ) {
				$media = (object) ( $row['photo']->settings->image ?? array() );
				$url = self::url( $r['image'] );
				if ( $url !== ( $media->url ?? '' ) ) {
					$media->id = $url ? attachment_url_to_postid( $url ) : 0;
					$media->url = $url;
					$row['photo']->settings->image = $media;
				}
			}
			$rank[ $key ] = self::number( $r['order'] );
			if ( null === $rank[ $key ] ) { return new \WP_Error( 'row_order', 'ترتیب دکمه باید یک عدد باشد.' ); }
		}
		asort( $rank, SORT_NUMERIC );
		$keys = array_keys( $rank );
		foreach ( $strip->elements as &$node ) {
			if ( isset( $rows[ $node->id ] ) ) { $node = $rows[ array_shift( $keys ) ]['item']; }
		}
		unset( $node );
		return true;
	}

	private static function save_dock( $page, $revision, $posted ) {
		$old = get_post_meta( $page, self::DOCK_META, true );
		if ( ! hash_equals( Slider_Document::token( $old ), $revision ) ) { return Slider_Document::conflict(); }
		$defaults = Portal::dock_defaults();
		if ( array_diff_key( $defaults, $posted ) ) { return new \WP_Error( 'rows', 'اطلاعات دکمه‌ها کامل نرسید.' ); }
		$new = array();
		$position = 0;
		foreach ( $defaults as $key => $base ) {
			$r = $posted[ $key ];
			if ( ! isset( $r['label'], $r['url'], $r['icon'], $r['order'] ) ) { return new \WP_Error( 'row', 'اطلاعات دکمه کامل نیست.' ); }
			$default = array( 'label' => $base[0], 'url' => $base[1], 'icon' => SAPONINE_STUDIO_URL . 'assets/icons/' . $key . '.svg', 'order' => ++$position, 'hidden' => false );
			$order = self::number( $r['order'] );
			if ( null === $order ) { return new \WP_Error( 'dock_order', 'ترتیب دکمه باید یک عدد باشد.' ); }
			$values = array( 'label' => sanitize_text_field( $r['label'] ), 'url' => self::url( $r['url'] ), 'icon' => self::url( $r['icon'] ), 'order' => $order, 'hidden' => ! empty( $r['hidden'] ) );
			if ( '' === $values['label'] || ( 'search' !== $key && '' === $values['url'] ) ) { return new \WP_Error( 'dock_value', 'عنوان و پیوند دکمه را تکمیل کنید.' ); }
			foreach ( $default as $field => $fallback ) {
				if ( $values[ $field ] !== $fallback ) { $new[ $key ][ $field ] = $values[ $field ]; }
			}
		}
		if ( $old === $new || ( '' === $old && ! $new ) ) { return true; }
		if ( '' === $old ) { $ok = add_post_meta( $page, self::DOCK_META, wp_slash( $new ), true ); }
		elseif ( ! $new ) { $ok = delete_post_meta( $page, self::DOCK_META, $old ); }
		else { $ok = update_post_meta( $page, self::DOCK_META, wp_slash( $new ), $old ); }
		return $ok ? true : Slider_Document::conflict();
	}

	public static function save() {
		$in = wp_unslash( $_POST );
		$page = absint( $in['page_id'] ?? 0 );
		$group = sanitize_key( $in['group'] ?? '' );
		check_admin_referer( 'slider_companion_' . $group . '_' . $page );
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $page ) || ! in_array( $group, array( 'seal', 'strip', 'dock' ), true ) ) {
			wp_die( 'اجازهٔ ویرایش این بخش را ندارید.', '', array( 'response' => 403 ) );
		}
		$revision = sanitize_text_field( $in['revision'] ?? '' );
		if ( 'dock' === $group ) {
			$result = self::save_dock( $page, $revision, (array) ( $in['rows'] ?? array() ) );
		} else {
			$id = 'strip' === $group ? self::header_id( $page ) : $page;
			$result = Slider_Document::change( $id, static function ( $nodes ) use ( $id, $group, $revision, $in ) {
				$node = self::widget( $nodes, 'strip' === $group ? 'sn-brand-strip' : 'sn-wave-seal' );
				if ( ! $node || ! hash_equals( Slider_Document::token( $node ), $revision ) ) { return Slider_Document::conflict(); }
				if ( 'strip' === $group ) {
					$strip = self::upgraded_strip( $node, $id );
					$before = Slider_Document::token( $strip );
					$status = self::save_strip( $strip, (array) ( $in['rows'] ?? array() ), self::displayed_links( $strip, $id ) );
					if ( is_wp_error( $status ) ) { return $status; }
					if ( $before === Slider_Document::token( $strip ) ) { return false; }
					$node->elements = $strip->elements;
				} else {
					if ( ! isset( $in['center'], $in['duration'] ) ) { return new \WP_Error( 'seal_fields', 'اطلاعات نشان کامل نیست.' ); }
					$node->settings->center_text = sanitize_textarea_field( $in['center'] );
					$duration = self::number( $in['duration'] );
					if ( null === $duration || $duration < 0 ) { return new \WP_Error( 'seal_duration', 'زمان هر دور باید صفر یا عددی مثبت باشد.' ); }
					$node->settings->duration = $duration;
					$node->settings->animate = isset( $in['animate'] ) ? 'yes' : '';
					foreach ( $node->settings->items ?? array() as $i => $item ) {
						if ( isset( $in['items'][ $i ] ) ) { $item->text = sanitize_text_field( $in['items'][ $i ] ); }
					}
				}
				return ! hash_equals( Slider_Document::token( $node ), $revision );
			} );
		}
		if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'back_link' => true, 'response' => 409 ) ); }
		wp_safe_redirect( add_query_arg( array( 'page' => 'saponine-slider', 'slider_post' => $page, 'sn_component_saved' => 1 ), admin_url( 'themes.php' ) ) );
		exit;
	}
}
