<?php
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Optional, administrator-started Google Cloud Translation Basic integration. */
final class Auto_Translate {
	const OPTION = 'saponine_auto_translation_settings';
	const JOB = 'saponine_auto_translation_job';
	const LOCK = 'saponine_auto_translation_lock';
	const ENDPOINT = 'https://translation.googleapis.com/language/translate/v2';
	const MAX_BATCH = 20;
	const MAX_BYTES = 12000;

	public static function boot() {
		add_action( 'admin_post_saponine_auto_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_saponine_auto_translate', array( __CLASS__, 'ajax' ) );
		add_action( 'elementor/element/image/section_image/before_section_end', array( __CLASS__, 'image_alt_control' ) );
		add_filter( 'elementor/widget/render_content', array( __CLASS__, 'image_alt_html' ), 20, 2 );
	}

	public static function key() {
		if ( defined( 'SAPONINE_GOOGLE_TRANSLATE_API_KEY' ) && is_string( SAPONINE_GOOGLE_TRANSLATE_API_KEY ) ) { return trim( SAPONINE_GOOGLE_TRANSLATE_API_KEY ); }
		$env = getenv( 'SAPONINE_GOOGLE_TRANSLATE_API_KEY' );
		if ( is_string( $env ) && '' !== trim( $env ) ) { return trim( $env ); }
		$settings = get_option( self::OPTION, array() );
		return isset( $settings['api_key'] ) && is_string( $settings['api_key'] ) ? $settings['api_key'] : '';
	}

	private static function guard() {
		return current_user_can( 'manage_options' ) ? true : new \WP_Error( 'saponine_auto_permission', 'برای ترجمهٔ خودکار، دسترسی مدیر سایت لازم است.' );
	}

	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'دسترسی کافی ندارید.', 'saponine-studio' ), '', array( 'response' => 403 ) ); }
		check_admin_referer( 'saponine_auto_settings' );
		$s = get_option( self::OPTION, array() );
		$s = is_array( $s ) ? $s : array();
		$key = isset( $_POST['api_key'] ) && is_string( $_POST['api_key'] ) ? trim( wp_unslash( $_POST['api_key'] ) ) : '';
		$notice = 'تنظیمات ترجمه ذخیره شد. کلید ذخیره‌شده در صفحه نمایش داده نمی‌شود.';
		if ( ! empty( $_POST['clear_key'] ) ) { unset( $s['api_key'] ); }
		elseif ( '' !== $key ) {
			if ( strlen( $key ) > 256 || ! preg_match( '/\A[A-Za-z0-9_-]{10,256}\z/', $key ) ) { $notice = 'قالب کلید معتبر نیست؛ کلید API گوگل را بدون فاصله وارد کنید.'; }
			else { $s['api_key'] = $key; }
		}
		update_option( self::OPTION, $s, false );
		set_transient( 'saponine_auto_notice_' . get_current_user_id(), $notice, 60 );
		wp_safe_redirect( admin_url( 'themes.php?page=saponine-studio#saponine-auto-translation' ) );
		exit;
	}

	public static function enqueue( $hook ) {
		if ( 'appearance_page_saponine-studio' !== $hook || ! current_user_can( 'manage_options' ) ) { return; }
		wp_enqueue_script( 'saponine-auto-translate-admin', SAPONINE_STUDIO_URL . 'assets/js/auto-translate-admin.js', array(), SAPONINE_STUDIO_VERSION, true );
		wp_localize_script( 'saponine-auto-translate-admin', 'SaponineAutoTranslate', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'saponine_auto_translate' ) ) );
	}

	public static function render_panel() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$languages = Multilingual::languages();
		$notice = get_transient( 'saponine_auto_notice_' . get_current_user_id() );
		if ( $notice ) { delete_transient( 'saponine_auto_notice_' . get_current_user_id() ); }
		$external_key = defined( 'SAPONINE_GOOGLE_TRANSLATE_API_KEY' ) || getenv( 'SAPONINE_GOOGLE_TRANSLATE_API_KEY' );
		?>
		<section class="sn-admin-card" id="saponine-auto-translation">
			<h2>ترجمهٔ خودکار محتوا</h2>
			<p>سرویس: <strong>Google Cloud Translation — Basic</strong>. متن‌های صفحه، محصولات، منو، سربرگ و پابرگ با حفظ طرح المنتور ترجمه می‌شوند. فارسی، انگلیسی و عربی پشتیبانی می‌شوند؛ زبان‌های دیگر تابع پشتیبانی سرویس‌اند.</p>
			<p>برای اجرا، Cloud Translation API را در حساب گوگل فعال و کلید API همان پروژه را وارد کنید. مصرف ترجمه تابع اعتبار، سهمیه و هزینهٔ حساب گوگل شماست. فقط متن‌های انتخاب‌شده برای ترجمه به این سرویس ارسال می‌شوند؛ تصاویر، کلیدهای وردپرس و تنظیمات طراحی ارسال نمی‌شوند.</p>
			<p><a href="https://docs.cloud.google.com/translate/docs/setup" target="_blank" rel="noopener noreferrer">راهنمای رسمی راه‌اندازی</a> · <a href="https://cloud.google.com/translate/pricing" target="_blank" rel="noopener noreferrer">تعرفه و سهمیهٔ سرویس</a></p>
			<?php if ( $notice ) : ?><p role="status"><?php echo esc_html( $notice ); ?></p><?php endif; ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="saponine_auto_settings"><?php wp_nonce_field( 'saponine_auto_settings' ); ?>
				<p><label>کلید API گوگل <input type="password" name="api_key" value="" autocomplete="new-password" maxlength="256" dir="ltr" class="regular-text" <?php disabled( $external_key ); ?>></label></p>
				<p class="description"><?php echo self::key() ? 'کلید تنظیم شده است. خالی گذاشتن فیلد، کلید فعلی را حفظ می‌کند.' : 'هنوز کلیدی تنظیم نشده؛ پیش از اجرای ترجمه، کلید را ذخیره کنید.'; ?> <?php if ( $external_key ) { echo 'کلید از تنظیمات سرور خوانده می‌شود.'; } ?></p>
				<?php if ( ! $external_key ) : ?><p><label><input type="checkbox" name="clear_key" value="1"> حذف کلید ذخیره‌شده</label></p><?php endif; ?>
				<p><button class="button" type="submit">ذخیرهٔ تنظیمات ترجمه</button></p>
			</form>
			<?php if ( ! Multilingual::ready() || count( $languages ) < 2 || ! Plugin::elementor_ready() ) : ?>
				<p>ابتدا Elementor و Polylang رایگان را فعال و دست‌کم دو زبان را در بخش «زبان‌ها» تعریف کنید.</p>
			<?php else : ?>
			<form data-sn-auto-form>
				<div class="sn-admin-grid">
					<label class="sn-admin-field">زبان نسخهٔ فعلی<select name="source_language" required><option value="">انتخاب زبان</option><?php foreach ( $languages as $lang ) : ?><option value="<?php echo esc_attr( $lang['slug'] ); ?>"><?php echo esc_html( $lang['name'] ); ?></option><?php endforeach; ?></select></label>
					<label class="sn-admin-field">زبان مقصد<select name="target_language" required><option value="">انتخاب زبان</option><?php foreach ( $languages as $lang ) : ?><option value="<?php echo esc_attr( $lang['slug'] ); ?>"><?php echo esc_html( $lang['name'] ); ?></option><?php endforeach; ?></select></label>
				</div>
				<p class="description">نسخه‌های تازه برای بازبینی پیش‌نویس می‌مانند. سند مقصدی که قبلاً منتشر شده باشد ترجمه یا لغو انتشار نمی‌شود. در پیش‌نویس‌های موجود، متن‌های ویرایش‌شدهٔ دستی حفظ می‌شوند. متن‌های فارسی و انگلیسیِ داخل یک صفحه، جداگانه تشخیص داده می‌شوند.</p>
				<p><button class="button button-primary" type="submit" <?php disabled( ! self::key() ); ?>>ساخت و ترجمهٔ خودکار این زبان</button> <button class="button" type="button" data-sn-auto-resume>ادامهٔ ترجمهٔ متوقف‌شده</button> <button class="button" type="button" data-sn-auto-cancel>توقف کار فعلی</button></p>
			</form>
			<progress data-sn-auto-progress max="100" value="0" style="width:100%;max-width:520px" aria-label="پیشرفت ترجمه"></progress>
			<div data-sn-auto-status role="status" aria-live="polite"></div><ul data-sn-auto-links></ul>
			<p class="description">این صفحه را تا پایان کار باز نگه دارید. پس از قطع اتصال، «ادامه» از مرحلهٔ ذخیره‌شده ادامه می‌دهد. پس از اتمام، ترجمه را در المنتور بازبینی و صفحه، سربرگ و پابرگِ آماده را منتشر کنید.</p>
			<?php endif; ?>
		</section>
		<?php
	}

	public static function ajax() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'دسترسی کافی ندارید.' ), 403 ); }
		check_ajax_referer( 'saponine_auto_translate', 'nonce' );
		$op = isset( $_POST['operation'] ) && is_string( $_POST['operation'] ) ? sanitize_key( wp_unslash( $_POST['operation'] ) ) : 'status';
		$id = isset( $_POST['job_id'] ) && is_string( $_POST['job_id'] ) ? sanitize_text_field( wp_unslash( $_POST['job_id'] ) ) : '';
		if ( 'start' === $op ) {
			$source = isset( $_POST['source_language'] ) && is_string( $_POST['source_language'] ) ? sanitize_key( wp_unslash( $_POST['source_language'] ) ) : '';
			$target = isset( $_POST['target_language'] ) && is_string( $_POST['target_language'] ) ? sanitize_key( wp_unslash( $_POST['target_language'] ) ) : '';
			$result = self::start( $source, $target );
		} elseif ( 'step' === $op ) { $result = self::step( $id ); }
		elseif ( 'cancel' === $op ) { $result = self::cancel( $id ); }
		else { $result = self::status(); }
		if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message(), 'code' => $result->get_error_code() ), 400 ); }
		wp_send_json_success( $result );
	}

	private static function acquire() {
		$old = absint( get_option( self::LOCK, 0 ) );
		if ( $old && time() - $old > 180 ) { delete_option( self::LOCK ); }
		return add_option( self::LOCK, time(), '', false );
	}

	private static function fingerprint( $id ) {
		return hash( 'sha256', wp_json_encode( array( get_post_meta( $id, '_elementor_data', true ), get_post_meta( $id, '_elementor_page_settings', true ), get_post_field( 'post_title', $id ), get_post_status( $id ) ) ) );
	}

	public static function start( $source, $target ) {
		if ( is_wp_error( $guard = self::guard() ) ) { return $guard; }
		if ( ! self::key() ) { return new \WP_Error( 'saponine_auto_key', 'ابتدا کلید Cloud Translation API گوگل را در تنظیمات ترجمه ذخیره کنید.' ); }
		if ( ! Multilingual::ready() || ! Plugin::elementor_ready() ) { return new \WP_Error( 'saponine_auto_dependency', 'Elementor و Polylang باید فعال باشند.' ); }
		if ( ! self::acquire() ) { return new \WP_Error( 'saponine_auto_busy', 'مرحلهٔ دیگری در حال اجراست؛ کمی بعد دوباره تلاش کنید.' ); }
		try {
			$previous = get_option( self::JOB, array() );
			if ( ! empty( $previous['status'] ) && in_array( $previous['status'], array( 'running', 'error' ), true ) ) { return new \WP_Error( 'saponine_auto_active', 'یک ترجمهٔ ناتمام وجود دارد؛ آن را ادامه دهید یا ابتدا متوقف کنید.' ); }
			$clones = Multilingual::create_translation( $source, $target );
			if ( is_wp_error( $clones ) ) { return $clones; }
			$langs = Multilingual::languages();
			$sources = Multilingual::source_documents();
			$job = array( 'id' => wp_generate_uuid4(), 'status' => 'running', 'source' => $source, 'target' => $target, 'target_code' => self::language_code( $langs[ $target ] ), 'created' => time(), 'docs' => array(), 'queue' => array(), 'results' => array(), 'ui' => array(), 'skipped' => array(), 'preserved_fields' => 0, 'error' => '', 'ui_saved' => false );
			foreach ( $sources as $role => $source_id ) {
				$id = absint( $clones[ $role ] );
				$fresh = in_array( $role, $clones['created'], true );
				if ( ! $fresh && 'draft' !== get_post_status( $id ) ) { $job['skipped'][] = $role . ': نسخهٔ موجود منتشرشده یا غیرپیش‌نویس حفظ شد.'; continue; }
				if ( $fresh && 'draft' !== get_post_status( $id ) ) { wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) ); }
				$data = json_decode( get_post_meta( $id, '_elementor_data', true ), true );
				$source_data = json_decode( get_post_meta( $source_id, '_elementor_data', true ), true );
				if ( ! is_array( $data ) || ! is_array( $source_data ) ) { return new \WP_Error( 'saponine_auto_data', 'ساختار یکی از اسناد المنتور معتبر نیست.' ); }
				self::materialize_repeaters( $data ); self::materialize_repeaters( $source_data );
				$from = self::fields( $source_data ); $to = self::fields( $data );
				$state = get_post_meta( $id, '_saponine_auto_translation_state', true );
				$state = is_array( $state ) ? $state : array();
				$doc = array( 'id' => $id, 'source_id' => $source_id, 'role' => $role, 'fresh' => $fresh, 'data' => $data, 'fields' => array(), 'baseline' => self::fingerprint( $id ), 'source_baseline' => self::fingerprint( $source_id ), 'saved' => false, 'state' => $state );
				foreach ( $from as $key => $field ) {
					if ( ! isset( $to[ $key ] ) ) { continue; }
					$current = $to[ $key ]['value'];
					$unchanged_machine = isset( $state[ $key ]['target_hash'] ) && hash_equals( $state[ $key ]['target_hash'], hash( 'sha256', $current ) );
					if ( $current !== $field['value'] && ! $unchanged_machine ) { $job['preserved_fields']++; continue; }
					$field['path'] = $to[ $key ]['path'];
					$field['parts'] = self::segments( $field['value'], $field['html'] );
					if ( ! $field['parts'] ) { continue; }
					$doc['fields'][ $key ] = $field;
					self::enqueue_parts( $job, $field['parts'] );
				}
				$source_title = (string) get_post_field( 'post_title', $source_id );
				$current_title = (string) get_post_field( 'post_title', $id );
				$original_copy_title = $source_title . ' — ' . $langs[ $target ]['name'];
				if ( $fresh || $current_title === $source_title || $current_title === $original_copy_title || ( isset( $state['@title']['target_hash'] ) && hash_equals( $state['@title']['target_hash'], hash( 'sha256', $current_title ) ) ) ) {
					$doc['fields']['@title'] = array( 'path' => array(), 'value' => $source_title, 'html' => false, 'parts' => self::segments( $source_title, false ) );
					self::enqueue_parts( $job, $doc['fields']['@title']['parts'] );
				}
				if ( $doc['fields'] ) { $job['docs'][] = $doc; }
				else { $job['skipped'][] = $role . ': متن کپی‌شدهٔ قابل ترجمه پیدا نشد؛ ویرایش‌های دستی حفظ شدند.'; }
			}
			if ( ! $job['docs'] ) { return new \WP_Error( 'saponine_auto_no_drafts', 'سند پیش‌نویسِ قابل ترجمه وجود ندارد. نسخه‌های منتشرشده و ویرایش‌های دستی حفظ شدند؛ برای ترجمهٔ آن‌ها ابتدا وضعیت و محتوای نسخهٔ مقصد را در وردپرس بررسی کنید.' ); }
			self::queue_interface( $job );
			update_option( self::JOB, $job, false );
			return self::summary( $job );
		} finally { delete_option( self::LOCK ); }
	}

	private static function language_code( $language ) {
		$locale = strtolower( str_replace( '_', '-', $language['locale'] ) );
		if ( 0 === strpos( $locale, 'zh' ) ) { return preg_match( '/(?:tw|hk|mo|hant)/', $locale ) ? 'zh-TW' : 'zh-CN'; }
		if ( 'pt-pt' === $locale ) { return 'pt-PT'; }
		return explode( '-', $locale )[0];
	}

	/** Narrow allowlist: no URLs, CSS, dimensions, IDs, animation values or arbitrary widget data. */
	private static function field_map() {
		return array(
			'heading' => array( 'title' => true ), 'text-editor' => array( 'editor' => true ), 'button' => array( 'text' => false ),
			'image' => array( 'caption' => false, 'sn_image_alt' => false ),
			'saponine-product-showcase' => array( 'section_label' => false, 'details_label' => false, 'view_label' => false, 'usage_label' => false, 'close_label' => false, 'previous_label' => false, 'next_label' => false, 'modal_kicker' => false, 'modal_note' => false, 'ring_text' => false ),
			'saponine-hero-slider' => array( 'carousel_label' => false ),
			'saponine-navigation' => array( 'menu_label' => false, 'toggle_label' => false ),
			'saponine-search' => array( 'toggle_label' => false, 'field_label' => false, 'placeholder' => false, 'submit_label' => false ),
			'saponine-marquee' => array( 'center_text' => false ),
			'saponine-section-rail' => array( 'rail_label' => false ),
			'saponine-language-switcher' => array( 'switcher_label' => false ),
		);
	}

	private static function repeaters() {
		return array(
			'saponine-product-showcase' => array( 'products', array( 'title', 'en_title', 'image_alt', 'description', 'features', 'usage', 'detail_description', 'modal_note', 'cta_label' ) ),
			'saponine-hero-slider' => array( 'slides', array( 'title', 'highlight', 'eyebrow', 'caption', 'image_alt', 'button_label' ) ),
			'saponine-navigation' => array( 'items', array( 'label' ) ),
			'saponine-marquee' => array( 'items', array( 'text' ) ),
			'saponine-section-rail' => array( 'items', array( 'label' ) ),
		);
	}

	/** New widgets may rely on repeater defaults; retain complete items when adding translations. */
	private static function materialize_repeaters( &$elements ) {
		$repeaters = self::repeaters();
		foreach ( $elements as &$element ) {
			$type = $element['widgetType'] ?? '';
			if ( isset( $repeaters[ $type ] ) ) {
				$name = $repeaters[ $type ][0];
				if ( ! isset( $element['settings'][ $name ] ) ) {
					$instance = \Elementor\Plugin::instance()->elements_manager->create_element_instance( $element );
					$default = $instance ? $instance->get_settings( $name ) : null;
					if ( is_array( $default ) ) { $element['settings'][ $name ] = $default; }
				}
			}
			if ( ! empty( $element['elements'] ) ) { self::materialize_repeaters( $element['elements'] ); }
		}
		unset( $element );
	}

	public static function fields( $elements, $prefix = array() ) {
		$out = array(); $map = self::field_map(); $repeaters = self::repeaters();
		foreach ( $elements as $index => $element ) {
			$path = array_merge( $prefix, array( $index ) );
			$type = isset( $element['widgetType'] ) ? $element['widgetType'] : '';
			$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();
			$defaults = array();
			if ( isset( $map[ $type ] ) ) {
				$instance = \Elementor\Plugin::instance()->elements_manager->create_element_instance( $element );
				if ( $instance ) { foreach ( $instance->get_controls() as $key => $control ) { if ( isset( $control['default'] ) && is_string( $control['default'] ) ) { $defaults[ $key ] = $control['default']; } } }
				if ( 'image' === $type && empty( $settings['sn_image_alt'] ) && ! empty( $settings['image']['id'] ) ) { $defaults['sn_image_alt'] = (string) get_post_meta( absint( $settings['image']['id'] ), '_wp_attachment_image_alt', true ); }
				foreach ( $map[ $type ] as $key => $html ) {
					$value = isset( $settings[ $key ] ) && is_string( $settings[ $key ] ) ? $settings[ $key ] : ( $defaults[ $key ] ?? '' );
					if ( 'image' === $type && 'caption' === $key && 'custom' !== ( $settings['caption_source'] ?? '' ) ) { continue; }
					if ( '' !== trim( $value ) ) { $out[ $element['id'] . '/' . $key ] = array( 'value' => $value, 'html' => $html, 'path' => array_merge( $path, array( 'settings', $key ) ) ); }
				}
			}
			if ( isset( $repeaters[ $type ] ) ) {
				list( $repeater, $keys ) = $repeaters[ $type ];
				foreach ( isset( $settings[ $repeater ] ) && is_array( $settings[ $repeater ] ) ? $settings[ $repeater ] : array() as $i => $item ) {
					foreach ( $keys as $key ) {
						if ( isset( $item[ $key ] ) && is_string( $item[ $key ] ) && '' !== trim( $item[ $key ] ) ) {
							$stable = $element['id'] . '/' . $repeater . '/' . ( $item['_id'] ?? $i ) . '/' . $key;
							$out[ $stable ] = array( 'value' => $item[ $key ], 'html' => false, 'path' => array_merge( $path, array( 'settings', $repeater, $i, $key ) ) );
						}
					}
				}
			}
			if ( ! empty( $element['elements'] ) ) { $out += self::fields( $element['elements'], array_merge( $path, array( 'elements' ) ) ); }
		}
		return $out;
	}

	/** Text-token extraction keeps original markup, attributes and links on the WordPress server. */
	private static function segments( $value, $html ) {
		$parts = array();
		if ( ! $html ) {
			foreach ( preg_split( '/(\r\n|\r|\n)/', $value, -1, PREG_SPLIT_DELIM_CAPTURE ) as $index => $line ) { if ( self::translatable( $line ) ) { $parts[ $index ] = $line; } }
			return $parts;
		}
		$processor = new \WP_HTML_Tag_Processor( $value ); $index = 0; $stack = array();
		$void = array( 'AREA', 'BASE', 'BR', 'COL', 'EMBED', 'HR', 'IMG', 'INPUT', 'LINK', 'META', 'PARAM', 'SOURCE', 'TRACK', 'WBR' );
		while ( $processor->next_token() ) {
			if ( '#tag' === $processor->get_token_type() ) {
				$tag = $processor->get_tag();
				if ( $processor->is_tag_closer() ) {
					for ( $n = count( $stack ) - 1; $n >= 0; $n-- ) { if ( $stack[ $n ]['tag'] === $tag ) { $stack = array_slice( $stack, 0, $n ); break; } }
				} elseif ( ! in_array( $tag, $void, true ) ) {
					$parent = $stack ? end( $stack ) : array( 'protected' => false );
					$blocked = $parent['protected'] || in_array( $tag, array( 'CODE', 'PRE', 'SCRIPT', 'STYLE', 'SVG' ), true ) || 'no' === strtolower( (string) $processor->get_attribute( 'translate' ) ) || $processor->has_class( 'notranslate' );
					$stack[] = array( 'tag' => $tag, 'protected' => $blocked );
				}
			}
			if ( '#text' !== $processor->get_token_type() ) { continue; }
			$text = $processor->get_modifiable_text();
			$parent = $stack ? end( $stack ) : array( 'protected' => false );
			if ( ! $parent['protected'] && self::translatable( $text ) ) { $parts[ $index ] = $text; }
			$index++;
		}
		return $parts;
	}

	private static function translatable( $text ) {
		return is_string( $text ) && preg_match( '/\p{L}/u', $text ) && ! preg_match( '~\A\s*(?:https?://\S+|[^\s@]+@[^\s@]+\.[^\s@]+)\s*\z~u', $text );
	}

	private static function enqueue_parts( &$job, $parts ) {
		foreach ( $parts as $text ) {
			foreach ( self::literal_parts( $text ) as $part ) {
				if ( $part['literal'] || ! self::translatable( $part['text'] ) ) { continue; }
				$key = hash( 'sha256', $job['target_code'] . "\n" . $part['text'] );
				$job['queue'][ $key ] = $part['text'];
				$cached = get_transient( 'sn_mt_' . $key );
				if ( is_string( $cached ) ) { $job['results'][ $key ] = $cached; }
			}
		}
	}

	/** Addresses and sprintf placeholders stay local; the worker never asks a provider to preserve them. */
	private static function literal_parts( $text ) {
		$pattern = '~(https?://[^\s<>"\x27]+|[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}|%(?:[0-9]+\$)?[-+0 #]*(?:[0-9]+|\*)?(?:\.(?:[0-9]+|\*))?[bcdeEfFgGosuxX])~u';
		$parts = preg_split( $pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out = array();
		foreach ( is_array( $parts ) ? $parts : array( $text ) as $i => $part ) { $out[] = array( 'text' => $part, 'literal' => 1 === $i % 2 ); }
		return $out;
	}

	private static function queue_interface( &$job ) {
		if ( ! class_exists( '\PLL_MO' ) || ! function_exists( 'PLL' ) || ! method_exists( UI_Strings::class, 'catalog' ) ) { return; }
		$lang = PLL()->model->get_language( $job['target'] );
		if ( ! $lang ) { return; }
		$mo = new \PLL_MO(); $mo->import_from_db( $lang );
		foreach ( UI_Strings::catalog() as $strings ) {
			foreach ( $strings as $source ) {
				$current = $mo->translate( $source );
				if ( $current !== $source ) { continue; }
				$parts = self::segments( $source, false );
				$job['ui'][] = array( 'source' => $source, 'parts' => $parts );
				self::enqueue_parts( $job, $parts );
			}
		}
	}

	public static function step( $job_id ) {
		if ( is_wp_error( $guard = self::guard() ) ) { return $guard; }
		if ( ! self::acquire() ) { return new \WP_Error( 'saponine_auto_busy', 'مرحلهٔ دیگری در حال اجراست؛ کمی بعد دوباره تلاش کنید.' ); }
		try {
			$job = get_option( self::JOB, array() );
			if ( empty( $job['id'] ) || ! is_string( $job_id ) || ! hash_equals( $job['id'], $job_id ) ) { return new \WP_Error( 'saponine_auto_job', 'کار ترجمه پیدا نشد؛ صفحه را تازه کنید.' ); }
			if ( in_array( $job['status'], array( 'complete', 'cancelled' ), true ) ) { return self::summary( $job ); }
			$batch = array(); $bytes = 0;
			foreach ( $job['queue'] as $key => $text ) {
				if ( array_key_exists( $key, $job['results'] ) ) { continue; }
				if ( strlen( $text ) > self::MAX_BYTES ) { return self::fail( $job, new \WP_Error( 'saponine_auto_length', 'یکی از متن‌ها بسیار طولانی است؛ آن را در المنتور به چند بند یا ابزار کوتاه‌تر تقسیم کنید و کار تازه‌ای بسازید.' ) ); }
				if ( count( $batch ) >= self::MAX_BATCH || ( $batch && $bytes + strlen( $text ) > self::MAX_BYTES ) ) { break; }
				$batch[ $key ] = $text; $bytes += strlen( $text );
			}
			if ( $batch ) {
				$translated = self::translate_batch( array_values( $batch ), $job['target_code'] );
				if ( is_wp_error( $translated ) ) { return self::fail( $job, $translated ); }
				foreach ( array_keys( $batch ) as $i => $key ) { $job['results'][ $key ] = $translated[ $i ]; set_transient( 'sn_mt_' . $key, $translated[ $i ], 30 * DAY_IN_SECONDS ); }
			} else {
				foreach ( $job['docs'] as $i => $doc ) {
					if ( $doc['saved'] ) { continue; }
					$result = self::save_document( $job, $i );
					if ( is_wp_error( $result ) ) { return self::fail( $job, $result ); }
					$job['status'] = 'running'; $job['error'] = ''; update_option( self::JOB, $job, false );
					return self::summary( $job );
				}
				if ( ! $job['ui_saved'] ) { self::save_interface( $job ); }
				$job['status'] = 'complete';
			}
			if ( 'complete' !== $job['status'] ) { $job['status'] = 'running'; }
			$job['error'] = ''; update_option( self::JOB, $job, false );
			return self::summary( $job );
		} finally { delete_option( self::LOCK ); }
	}

	private static function fail( &$job, $error ) {
		$job['status'] = 'error'; $job['error'] = $error->get_error_message();
		update_option( self::JOB, $job, false );
		return $error;
	}

	/** Provider receives only plain text; omitted source allows mixed-language content. */
	public static function translate_batch( $texts, $target ) {
		if ( is_wp_error( $guard = self::guard() ) ) { return $guard; }
		$key = self::key();
		if ( ! $key ) { return new \WP_Error( 'saponine_auto_key', 'کلید API تنظیم نشده است.' ); }
		if ( ! is_array( $texts ) || ! $texts || count( $texts ) > self::MAX_BATCH || ! preg_match( '/\A[a-z]{2,3}(?:-[A-Za-z]{2,4})?\z/', $target ) ) { return new \WP_Error( 'saponine_auto_request', 'متن یا کد زبان مقصد معتبر نیست.' ); }
		$protected = array(); $payload = array();
		foreach ( $texts as $i => $text ) {
			if ( ! is_string( $text ) || strlen( $text ) > self::MAX_BYTES ) { return new \WP_Error( 'saponine_auto_request', 'متن ترجمه از اندازهٔ مجاز بیشتر است.' ); }
			$protected[ $i ] = array();
			$payload[] = preg_replace_callback( '~https?://[^\s<>"\x27]+|[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}|%(?:[0-9]+\$)?[-+0 #]*(?:[0-9]+|\*)?(?:\.(?:[0-9]+|\*))?[bcdeEfFgGosuxX]~u', static function( $m ) use ( &$protected, $i ) { $token = '__SNTOKEN' . count( $protected[ $i ] ) . '__'; $protected[ $i ][ $token ] = $m[0]; return $token; }, $text );
		}
		$response = wp_remote_post( self::ENDPOINT, array( 'timeout' => 20, 'redirection' => 0, 'sslverify' => true, 'reject_unsafe_urls' => true, 'limit_response_size' => 1048576, 'headers' => array( 'Content-Type' => 'application/json; charset=utf-8', 'X-Goog-Api-Key' => $key ), 'body' => wp_json_encode( array( 'q' => $payload, 'target' => $target, 'format' => 'text', 'model' => 'nmt' ) ), 'data_format' => 'body' ) );
		if ( is_wp_error( $response ) ) { return new \WP_Error( 'saponine_auto_network', 'ارتباط سرور با Google Cloud Translation برقرار نشد. اتصال خروجی هاست را بررسی و سپس ادامه را بزنید.' ); }
		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$messages = array( 400 => 'درخواست ترجمه پذیرفته نشد؛ زبان مقصد و فعال‌بودن Cloud Translation API را بررسی کنید.', 401 => 'کلید ترجمه پذیرفته نشد؛ کلید API را بررسی کنید.', 403 => 'دسترسی Google Cloud Translation رد شد؛ کلید، محدودیت کلید، فعال‌بودن API و وضعیت حساب/صورتحساب را بررسی کنید.', 429 => 'سهمیه یا محدودیت سرعت ترجمه پر شده است؛ سهمیهٔ حساب گوگل را بررسی و بعداً ادامه دهید.' );
			return new \WP_Error( 'saponine_auto_http_' . $code, $messages[ $code ] ?? 'سرویس ترجمه فعلاً پاسخ موفق نداد. پیشرفت ذخیره شده است؛ بعداً ادامه دهید.' );
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$items = $body['data']['translations'] ?? null;
		if ( ! is_array( $items ) || count( $items ) !== count( $texts ) ) { return new \WP_Error( 'saponine_auto_response', 'پاسخ سرویس ترجمه کامل یا معتبر نبود؛ هیچ متن جدیدی ذخیره نشد.' ); }
		$out = array();
		foreach ( $items as $i => $item ) {
			if ( ! isset( $item['translatedText'] ) || ! is_string( $item['translatedText'] ) || '' === trim( $item['translatedText'] ) || strlen( $item['translatedText'] ) > 120000 ) { return new \WP_Error( 'saponine_auto_response', 'پاسخ سرویس ترجمه کامل یا معتبر نبود؛ هیچ متن جدیدی ذخیره نشد.' ); }
			$value = wp_strip_all_tags( html_entity_decode( $item['translatedText'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
			foreach ( $protected[ $i ] as $token => $original ) {
				if ( 1 !== substr_count( $value, $token ) ) { return new \WP_Error( 'saponine_auto_tokens', 'سرویس ترجمه یک نشانی یا جای‌نگهدار را تغییر داده است؛ برای حفظ صحت متن، ذخیره متوقف شد.' ); }
				$value = str_replace( $token, $original, $value );
			}
			if ( preg_match( '/\A(\s*)(.*?)(\s*)\z/us', $texts[ $i ], $whitespace ) ) { $value = $whitespace[1] . trim( $value ) . $whitespace[3]; }
			$out[] = $value;
		}
		return $out;
	}

	private static function translated_value( $field, $job ) {
		$parts = array();
		foreach ( $field['parts'] as $index => $text ) {
			$value = '';
			foreach ( self::literal_parts( $text ) as $part ) {
				$value .= $part['literal'] || ! self::translatable( $part['text'] ) ? $part['text'] : $job['results'][ hash( 'sha256', $job['target_code'] . "\n" . $part['text'] ) ];
			}
			$parts[ $index ] = $value;
		}
		if ( ! $field['html'] ) {
			$lines = preg_split( '/(\r\n|\r|\n)/', $field['value'], -1, PREG_SPLIT_DELIM_CAPTURE );
			foreach ( $parts as $index => $translation ) { $lines[ $index ] = $translation; }
			return implode( '', $lines );
		}
		$processor = new \WP_HTML_Tag_Processor( $field['value'] ); $index = 0;
		while ( $processor->next_token() ) {
			if ( '#text' !== $processor->get_token_type() ) { continue; }
			if ( array_key_exists( $index, $parts ) ) { $processor->set_modifiable_text( $parts[ $index ] ); }
			$index++;
		}
		return $processor->get_updated_html();
	}

	private static function set_path( &$data, $path, $value ) {
		$ref =& $data;
		foreach ( $path as $key ) { if ( ! isset( $ref[ $key ] ) ) { $ref[ $key ] = array(); } $ref =& $ref[ $key ]; }
		$ref = $value;
	}

	private static function save_document( &$job, $index ) {
		$doc =& $job['docs'][ $index ]; $id = $doc['id'];
		if ( ! current_user_can( 'edit_post', $id ) || 'draft' !== get_post_status( $id ) || ! hash_equals( $doc['baseline'], self::fingerprint( $id ) ) || ! hash_equals( $doc['source_baseline'], self::fingerprint( $doc['source_id'] ) ) ) { return new \WP_Error( 'saponine_auto_conflict', 'یکی از اسناد مبدأ یا مقصد هنگام ترجمه ویرایش یا منتشر شده است. تغییرات شما حفظ شد؛ این کار را متوقف و یک ترجمهٔ تازه شروع کنید.' ); }
		$data = $doc['data']; $title = (string) get_post_field( 'post_title', $id ); $state = $doc['state'];
		foreach ( $doc['fields'] as $key => $field ) {
			$value = self::translated_value( $field, $job );
			if ( '@title' === $key ) { $title = $value; } else { self::set_path( $data, $field['path'], $value ); }
			$state[ $key ] = array( 'source_hash' => hash( 'sha256', $field['value'] ), 'target_hash' => hash( 'sha256', $value ) );
		}
		if ( $doc['fresh'] ) { self::auto_directions( $data ); }
		$settings = get_post_meta( $id, '_elementor_page_settings', true ); $settings = is_array( $settings ) ? $settings : array();
		$settings['post_status'] = 'draft'; $settings['post_title'] = $title;
		$document = \Elementor\Plugin::instance()->documents->get( $id );
		if ( ! $document || ! $document->save( array( 'elements' => $data, 'settings' => $settings ) ) ) { return new \WP_Error( 'saponine_auto_save', 'ذخیرهٔ سند المنتور کامل نشد؛ پیشرفت ترجمه نگه داشته شده است.' ); }
		update_post_meta( $id, '_saponine_auto_translation_state', $state );
		update_post_meta( $id, '_saponine_auto_translated_language', $job['target'] );
		$doc['saved'] = true; $doc['baseline'] = self::fingerprint( $id );
		return true;
	}

	private static function auto_directions( &$elements ) {
		foreach ( $elements as &$element ) {
			if ( 'saponine-hero-slider' === ( $element['widgetType'] ?? '' ) && ! empty( $element['settings']['slides'] ) ) { foreach ( $element['settings']['slides'] as &$slide ) { $slide['text_direction'] = 'auto'; } unset( $slide ); }
			if ( 'saponine-marquee' === ( $element['widgetType'] ?? '' ) ) { $element['settings']['text_direction'] = 'auto'; }
			if ( ! empty( $element['elements'] ) ) { self::auto_directions( $element['elements'] ); }
		}
		unset( $element );
	}

	private static function save_interface( &$job ) {
		if ( class_exists( '\PLL_MO' ) && function_exists( 'PLL' ) && ( $lang = PLL()->model->get_language( $job['target'] ) ) ) {
			$mo = new \PLL_MO(); $mo->import_from_db( $lang );
			foreach ( $job['ui'] as $entry ) {
				if ( $mo->translate( $entry['source'] ) !== $entry['source'] ) { continue; }
				$field = array( 'value' => $entry['source'], 'html' => false, 'parts' => $entry['parts'] );
				$value = self::translated_value( $field, $job );
				$mo->add_entry( $mo->make_entry( $entry['source'], $value ) );
			}
			$mo->export_to_db( $lang );
		}
		$job['ui_saved'] = true;
	}

	public static function cancel( $id ) {
		if ( is_wp_error( $guard = self::guard() ) ) { return $guard; }
		if ( ! self::acquire() ) { return new \WP_Error( 'saponine_auto_busy', 'مرحله‌ای در حال اجراست؛ پس از پایان آن توقف را بزنید.' ); }
		try {
			$job = get_option( self::JOB, array() );
			if ( empty( $job['id'] ) || ! hash_equals( $job['id'], (string) $id ) ) { return new \WP_Error( 'saponine_auto_job', 'کار ترجمه پیدا نشد.' ); }
			if ( 'complete' === $job['status'] ) { return self::summary( $job ); }
			$job['status'] = 'cancelled'; $job['error'] = ''; update_option( self::JOB, $job, false );
			return self::summary( $job );
		} finally { delete_option( self::LOCK ); }
	}

	public static function status() {
		if ( is_wp_error( $guard = self::guard() ) ) { return $guard; }
		return self::summary( get_option( self::JOB, array() ) );
	}

	private static function summary( $job ) {
		if ( empty( $job['id'] ) ) { return array( 'status' => 'idle', 'message' => 'ترجمه‌ای در حال اجرا نیست.', 'progress' => 0 ); }
		$total = count( $job['queue'] ); $done = count( $job['results'] ); $saved = 0; $documents = array();
		foreach ( $job['docs'] as $doc ) { if ( $doc['saved'] ) { $saved++; } $documents[] = array( 'role' => $doc['role'], 'id' => $doc['id'], 'url' => admin_url( 'post.php?post=' . $doc['id'] . '&action=elementor' ), 'status' => get_post_status( $doc['id'] ), 'saved' => $doc['saved'] ); }
		$progress = $total ? floor( 90 * $done / $total ) : 90;
		$progress += count( $job['docs'] ) ? floor( 9 * $saved / count( $job['docs'] ) ) : 0;
		if ( 'complete' === $job['status'] ) { $progress = 100; }
		$messages = array( 'running' => 'ترجمه در حال انجام است؛ این صفحه را باز نگه دارید.', 'complete' => 'ترجمه آمادهٔ بازبینی است؛ اسناد پیش‌نویس را با المنتور بررسی و پس از آماده‌شدن منتشر کنید.', 'cancelled' => 'کار متوقف شد؛ اسناد و ترجمه‌های ذخیره‌شده حفظ شدند.', 'error' => $job['error'] );
		return array( 'id' => $job['id'], 'status' => $job['status'], 'message' => $messages[ $job['status'] ] ?? '', 'progress' => $progress, 'translated' => $done, 'total' => $total, 'documents' => $documents, 'skipped' => $job['skipped'], 'preserved_fields' => $job['preserved_fields'] );
	}

	public static function image_alt_control( $element ) {
		$element->add_control( 'sn_image_alt', array( 'label' => 'متن جایگزین تصویر در این زبان', 'type' => \Elementor\Controls_Manager::TEXT, 'description' => 'اختیاری؛ بدون تغییر مشخصات مشترک رسانه، متن جایگزین همین تصویر در این صفحه را تعیین کنید.' ) );
	}

	public static function image_alt_html( $html, $widget ) {
		if ( 'image' !== $widget->get_name() ) { return $html; }
		$alt = $widget->get_settings_for_display( 'sn_image_alt' );
		if ( ! is_string( $alt ) || '' === $alt ) { return $html; }
		$processor = new \WP_HTML_Tag_Processor( $html );
		while ( $processor->next_tag( 'IMG' ) ) { $processor->set_attribute( 'alt', $alt ); }
		return $processor->get_updated_html();
	}
}
