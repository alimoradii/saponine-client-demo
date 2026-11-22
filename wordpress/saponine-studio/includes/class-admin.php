<?php
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {
	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_saponine_save_settings', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_saponine_import_demo', array( __CLASS__, 'import' ) );
		add_action( 'admin_post_saponine_create_translation', array( __CLASS__, 'create_translation' ) );
	}

	public static function menu() {
		add_theme_page( __( 'ساپونین استودیو', 'saponine-studio' ), __( 'ساپونین استودیو', 'saponine-studio' ), 'manage_options', 'saponine-studio', array( __CLASS__, 'page' ) );
	}

	public static function assets( $hook ) {
		if ( 'appearance_page_saponine-studio' === $hook ) {
			wp_enqueue_style( 'saponine-admin', SAPONINE_STUDIO_URL . 'assets/css/admin.css', array(), SAPONINE_STUDIO_VERSION );
		}
	}

	private static function guard( $nonce ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'اجازهٔ تغییر تنظیمات این سایت را ندارید.', 'saponine-studio' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $nonce );
	}

	private static function finish( $message, $type = 'success' ) {
		set_transient( 'saponine_notice_' . get_current_user_id(), array( 'text' => $message, 'type' => $type ), 120 );
		wp_safe_redirect( admin_url( 'themes.php?page=saponine-studio' ) );
		exit;
	}

	public static function save() {
		self::guard( 'saponine_save_settings' );
		$input = isset( $_POST['saponine'] ) && is_array( $_POST['saponine'] ) ? wp_unslash( $_POST['saponine'] ) : array();
		$s = Plugin::settings();
		foreach ( array( 'header_id', 'footer_id' ) as $key ) {
			$id = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : 0;
			if ( 0 !== $id && ! Plugin::valid_section( $id ) ) {
				self::finish( __( 'سربرگ و پابرگ باید از الگوهای منتشرشدهٔ نوع Section المنتور انتخاب شوند.', 'saponine-studio' ), 'error' );
			}
			$s[ $key ] = $id;
		}
		foreach ( array( 'primary', 'secondary', 'background', 'text', 'muted', 'border', 'accent' ) as $key ) {
			$value = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			if ( ! $value ) {
				self::finish( __( 'برای هر رنگ، یک مقدار معتبر انتخاب کنید.', 'saponine-studio' ), 'error' );
			}
			$s[ $key ] = $value;
		}
		foreach ( array( 'font_body' => array( 'Vazirmatn', 'Tahoma', 'Arial' ), 'font_latin' => array( 'Outfit', 'Arial', 'Georgia' ) ) as $key => $allowed ) {
			$s[ $key ] = isset( $input[ $key ] ) && in_array( $input[ $key ], $allowed, true ) ? $input[ $key ] : $allowed[0];
		}
		$s['allow_google_fonts'] = isset( $input['allow_google_fonts'] ) && 'yes' === $input['allow_google_fonts'] ? 'yes' : 'no';
		$s['section_scroll_enabled'] = isset( $input['section_scroll_enabled'] ) && 'yes' === $input['section_scroll_enabled'] ? 'yes' : 'no';
		$s['section_scroll_duration'] = isset( $input['section_scroll_duration'] ) && is_numeric( $input['section_scroll_duration'] ) ? max( 0, min( 1500, (int) $input['section_scroll_duration'] ) ) : 650;
		$s['section_scroll_threshold'] = isset( $input['section_scroll_threshold'] ) && is_numeric( $input['section_scroll_threshold'] ) ? max( 8, min( 160, (int) $input['section_scroll_threshold'] ) ) : 36;
		$s['section_scroll_mobile'] = isset( $input['section_scroll_mobile'] ) && 'sections' === $input['section_scroll_mobile'] ? 'sections' : 'native';
		update_option( 'saponine_studio_settings', $s, false );
		self::finish( __( 'تنظیمات ذخیره شد. تنظیمات اختصاصی هر ابزار در المنتور، بر رنگ و قلم پیش‌فرض اولویت دارد.', 'saponine-studio' ) );
	}

	public static function import() {
		self::guard( 'saponine_import_demo' );
		if ( ! Plugin::elementor_ready() ) {
			self::finish( __( 'ابتدا نسخهٔ پشتیبانی‌شدهٔ المنتور رایگان را فعال کنید.', 'saponine-studio' ), 'error' );
		}
		$result = Importer::run();
		if ( is_wp_error( $result ) ) {
			self::finish( $result->get_error_message(), 'error' );
		}
		if ( isset( $_POST['make_homepage'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['make_homepage'] ) ) ) {
			$published = wp_update_post( array( 'ID' => $result['home'], 'post_status' => 'publish' ), true );
			if ( is_wp_error( $published ) ) {
				self::finish( __( 'طرح وارد شد، اما انتشار صفحه ممکن نشد: ', 'saponine-studio' ) . $published->get_error_message(), 'error' );
			}
			update_option( 'page_on_front', $result['home'] );
			update_option( 'show_on_front', 'page' );
		}
		if ( isset( $_POST['use_chrome'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['use_chrome'] ) ) ) {
			$s = Plugin::settings();
			$s['header_id'] = $result['header'];
			$s['footer_id'] = $result['footer'];
			update_option( 'saponine_studio_settings', $s, false );
		}
		self::finish( __( 'طرح و تصاویر محلی آماده‌اند. درون‌ریزی دوباره، ویرایش‌های قبلی شما را بازنویسی نمی‌کند. برای ویرایش، دکمه‌های زیر را بزنید.', 'saponine-studio' ) );
	}

	public static function create_translation() {
		self::guard( 'saponine_create_translation' );
		$source = isset( $_POST['source_language'] ) && is_scalar( $_POST['source_language'] ) ? sanitize_key( wp_unslash( $_POST['source_language'] ) ) : '';
		$target = isset( $_POST['target_language'] ) && is_scalar( $_POST['target_language'] ) ? sanitize_key( wp_unslash( $_POST['target_language'] ) ) : '';
		$result = Multilingual::create_translation( $source, $target );
		if ( is_wp_error( $result ) ) { self::finish( $result->get_error_message(), 'error' ); }
		self::finish( __( 'نسخهٔ زبانی آماده شد. متن‌ها فعلاً کپی نسخهٔ اصلی هستند و خودکار ترجمه نشده‌اند؛ هر سه سند را با المنتور ترجمه و ویرایش کنید. برگهٔ تازه پیش‌نویس است. وضعیت هر سه سند را در جدول بررسی کنید؛ برگه‌ها و الگوهای پیش‌نویس را پس از بازبینی منتشر کنید. ویرایش‌های ترجمه‌های موجود حفظ شدند.', 'saponine-studio' ) );
	}

	private static function multilingual_panel() {
		?>
		<section class="sn-admin-card" id="saponine-languages">
			<h2><?php esc_html_e( '۳. زبان‌های سایت', 'saponine-studio' ); ?></h2>
			<p><?php esc_html_e( 'برای هر زبان، صفحهٔ اصلی، سربرگ و پابرگ مستقل دارید؛ متن، تصویر، رنگ، چیدمان و موشن هر نسخه را جداگانه با المنتور تغییر دهید.', 'saponine-studio' ); ?></p>
			<?php if ( ! Multilingual::ready() ) : ?>
				<p><?php esc_html_e( 'برای فعال‌سازی چندزبانه، افزونهٔ رایگان Polylang را از مخزن رسمی وردپرس نصب و فعال کنید؛ سپس در «زبان‌ها»، زبان نسخهٔ فعلی و زبان‌های مقصد را اضافه کنید. برای این امکانات به نسخهٔ پولی نیازی نیست.', 'saponine-studio' ); ?></p>
				<a class="button" href="<?php echo esc_url( admin_url( 'plugin-install.php?s=polylang&tab=search&type=term' ) ); ?>"><?php esc_html_e( 'پیدا کردن Polylang در مخزن', 'saponine-studio' ); ?></a>
			<?php else : ?>
				<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mlang' ) ); ?>"><?php esc_html_e( 'افزودن و تنظیم زبان‌ها', 'saponine-studio' ); ?></a> <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mlang_strings' ) ); ?>"><?php esc_html_e( 'ترجمهٔ عبارت‌های عمومی رابط', 'saponine-studio' ); ?></a></p>
				<?php $languages = Multilingual::languages(); $sources = Multilingual::source_documents(); $assigned = $sources['home'] ? pll_get_post_language( $sources['home'], 'slug' ) : ''; ?>
				<?php if ( count( $languages ) < 2 ) : ?>
					<p><?php esc_html_e( 'دست‌کم دو زبان تعریف کنید؛ برای نمونه فارسی و انگلیسی. جهت هر زبان را در تنظیمات Polylang مشخص کنید.', 'saponine-studio' ); ?></p>
				<?php else : ?>
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<input type="hidden" name="action" value="saponine_create_translation">
						<?php wp_nonce_field( 'saponine_create_translation' ); ?>
						<div class="sn-admin-grid">
							<label class="sn-admin-field"><span><?php esc_html_e( 'زبان نسخهٔ فعلی', 'saponine-studio' ); ?></span><select name="source_language" required>
								<option value=""><?php esc_html_e( 'زبان را انتخاب کنید', 'saponine-studio' ); ?></option>
								<?php foreach ( $languages as $language ) : ?><option value="<?php echo esc_attr( $language['slug'] ); ?>" <?php selected( $assigned, $language['slug'] ); ?>><?php echo esc_html( $language['name'] . ' (' . $language['slug'] . ')' ); ?></option><?php endforeach; ?>
							</select></label>
							<label class="sn-admin-field"><span><?php esc_html_e( 'زبان مقصد', 'saponine-studio' ); ?></span><select name="target_language" required>
								<option value=""><?php esc_html_e( 'زبان را انتخاب کنید', 'saponine-studio' ); ?></option>
								<?php foreach ( $languages as $language ) : ?><option value="<?php echo esc_attr( $language['slug'] ); ?>"><?php echo esc_html( $language['name'] . ' (' . $language['slug'] . ')' ); ?></option><?php endforeach; ?>
							</select></label>
						</div>
						<p class="description"><?php esc_html_e( 'این کار نسخه‌ای مستقل از طرح و محتوای فعلی می‌سازد؛ ترجمهٔ خودکار متن انجام نمی‌دهد. زبان اسناد قبلاً برچسب‌خورده عوض نمی‌شود و ترجمهٔ موجود بازنویسی نمی‌شود.', 'saponine-studio' ); ?></p>
						<p><button class="button button-primary" type="submit" <?php disabled( ! Plugin::elementor_ready() || ! $sources['home'] ); ?>><?php esc_html_e( 'ساخت / تکمیل نسخهٔ زبانی', 'saponine-studio' ); ?></button></p>
					</form>
				<?php endif; ?>
				<?php if ( $sources['home'] && $languages ) : ?>
					<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'زبان و جهت', 'saponine-studio' ); ?></th><th><?php esc_html_e( 'صفحهٔ اصلی', 'saponine-studio' ); ?></th><th><?php esc_html_e( 'سربرگ', 'saponine-studio' ); ?></th><th><?php esc_html_e( 'پابرگ', 'saponine-studio' ); ?></th></tr></thead><tbody>
					<?php foreach ( $languages as $language ) : ?><tr>
						<td><?php echo esc_html( $language['name'] . ' — ' . ( $language['rtl'] ? 'RTL' : 'LTR' ) ); ?></td>
						<?php foreach ( $sources as $role => $source_id ) : $id = $source_id ? absint( pll_get_post( $source_id, $language['slug'] ) ) : 0; ?>
						<td><?php if ( $id && get_post( $id ) && 'trash' !== get_post_status( $id ) && current_user_can( 'edit_post', $id ) ) : ?>
							<a href="<?php echo esc_url( self::editor_url( $id ) ); ?>"><?php esc_html_e( 'ویرایش با المنتور', 'saponine-studio' ); ?></a>
							<?php $status = get_post_status_object( get_post_status( $id ) ); echo ' — ' . esc_html( $status ? $status->label : get_post_status( $id ) ); ?>
						<?php else : ?><?php esc_html_e( 'ساخته نشده', 'saponine-studio' ); ?><?php endif; ?></td>
						<?php endforeach; ?>
					</tr><?php endforeach; ?>
					</tbody></table>
				<?php endif; ?>
				<p><?php esc_html_e( 'در سربرگ، ابزار «انتخاب زبان ساپونین» را ویرایش یا اضافه کنید. زبان‌هایی که ترجمهٔ منتشرشدهٔ این صفحه را ندارند نمایش داده نمی‌شوند. با تنها یک زبان، ابزار در سایت مخفی است؛ در ویرایشگر قابل تنظیم می‌ماند.', 'saponine-studio' ); ?></p>
				<p><?php esc_html_e( 'پس از انتشار ترجمهٔ صفحهٔ اصلی، Polylang نشانی‌ها و جهت زبان را مدیریت می‌کند. سربرگ و پابرگِ ترجمه‌شده خودکار انتخاب می‌شوند؛ اگر ترجمهٔ آن‌ها آماده و منتشر نشده باشد، الگوی اصلی نمایش داده می‌شود.', 'saponine-studio' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
	}

	private static function select_section( $key, $label, $selected, $templates ) {
		echo '<label class="sn-admin-field"><span>' . esc_html( $label ) . '</span><select name="saponine[' . esc_attr( $key ) . ']">';
		echo '<option value="0">' . esc_html__( 'پوستهٔ پیش‌فرض (بدون الگوی المنتور)', 'saponine-studio' ) . '</option>';
		foreach ( $templates as $template ) {
			echo '<option value="' . esc_attr( $template->ID ) . '" ' . selected( $selected, $template->ID, false ) . '>' . esc_html( $template->post_title ) . ' — #' . esc_html( $template->ID ) . '</option>';
		}
		echo '</select></label>';
		if ( Plugin::valid_section( $selected ) ) {
			echo '<a class="button" href="' . esc_url( self::editor_url( $selected ) ) . '">' . esc_html( sprintf( __( 'ویرایش %s با المنتور', 'saponine-studio' ), $label ) ) . '</a>';
		}
	}

	public static function editor_url( $id ) {
		return add_query_arg( array( 'post' => absint( $id ), 'action' => 'elementor' ), admin_url( 'post.php' ) );
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = Plugin::settings();
		$map = get_option( 'saponine_studio_import_map', array() );
		$notice = get_transient( 'saponine_notice_' . get_current_user_id() );
		delete_transient( 'saponine_notice_' . get_current_user_id() );
		$templates = get_posts( array( 'lang' => '', 'post_type' => 'elementor_library', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'meta_key' => '_elementor_template_type', 'meta_value' => 'section' ) );
		?>
		<div class="wrap sn-admin" dir="rtl">
			<h1><?php esc_html_e( 'ساپونین استودیو', 'saponine-studio' ); ?></h1>
			<p class="sn-admin-lead"><?php esc_html_e( 'متن‌ها، تصاویر، چیدمان، رنگ و حرکت را با المنتور تغییر دهید. صفحهٔ اصلی، سربرگ و پابرگ سه سند مستقل و قابل ویرایش‌اند.', 'saponine-studio' ); ?></p>
			<?php if ( is_array( $notice ) && isset( $notice['text'] ) ) : ?>
				<div class="notice notice-<?php echo 'error' === $notice['type'] ? 'error' : 'success'; ?>"><p><?php echo esc_html( $notice['text'] ); ?></p></div>
			<?php endif; ?>
			<div class="sn-admin-grid">
				<section class="sn-admin-card">
					<h2><?php esc_html_e( '۱. وارد کردن طرح تأییدشده', 'saponine-studio' ); ?></h2>
					<p><?php esc_html_e( 'تصاویر از همین بسته وارد کتابخانهٔ رسانه می‌شوند. یک برگهٔ پیش‌نویس و دو الگوی Section ساخته می‌شود. هیچ صفحه یا نوشتهٔ موجودی حذف یا بازنویسی نمی‌شود.', 'saponine-studio' ); ?></p>
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<input type="hidden" name="action" value="saponine_import_demo">
						<?php wp_nonce_field( 'saponine_import_demo' ); ?>
						<label class="sn-admin-check"><input type="checkbox" name="make_homepage" value="yes"> <?php esc_html_e( 'برگهٔ واردشده را منتشر و به‌عنوان صفحهٔ اصلی سایت انتخاب کن.', 'saponine-studio' ); ?></label>
						<label class="sn-admin-check"><input type="checkbox" name="use_chrome" value="yes"> <?php esc_html_e( 'سربرگ و پابرگ واردشده را برای این سایت فعال کن.', 'saponine-studio' ); ?></label>
						<p><button class="button button-primary" type="submit" <?php disabled( ! Plugin::elementor_ready() ); ?>><?php esc_html_e( 'وارد کردن / تکمیل طرح', 'saponine-studio' ); ?></button></p>
					</form>
					<?php if ( ! Plugin::elementor_ready() ) : ?><p><?php esc_html_e( 'المنتور رایگان نسخهٔ ۳٫۲۸ یا جدیدتر باید فعال باشد.', 'saponine-studio' ); ?></p><?php endif; ?>
					<div class="sn-admin-links">
					<?php foreach ( array( 'home' => __( 'صفحهٔ اصلی', 'saponine-studio' ), 'header' => __( 'سربرگ', 'saponine-studio' ), 'footer' => __( 'پابرگ', 'saponine-studio' ) ) as $role => $label ) : ?>
						<?php if ( ! empty( $map[ $role ] ) && get_post( $map[ $role ] ) && 'trash' !== get_post_status( $map[ $role ] ) ) : ?>
							<a class="button" href="<?php echo esc_url( self::editor_url( $map[ $role ] ) ); ?>"><?php echo esc_html( sprintf( __( 'ویرایش %s', 'saponine-studio' ), $label ) ); ?></a>
						<?php endif; ?>
					<?php endforeach; ?>
					</div>
				</section>
				<section class="sn-admin-card">
					<h2><?php esc_html_e( 'راهنمای ویرایش', 'saponine-studio' ); ?></h2>
					<ol>
						<li><?php esc_html_e( 'روی «ویرایش صفحهٔ اصلی» بزنید. از ساختار/ناوبر المنتور برای انتخاب و جابه‌جایی بخش‌ها استفاده کنید.', 'saponine-studio' ); ?></li>
						<li><?php esc_html_e( 'محتوا و تصاویر در تب محتوا؛ رنگ و اندازه‌ها در تب استایل؛ فاصله‌ها و ترتیب موبایل در تب پیشرفته قابل تغییرند.', 'saponine-studio' ); ?></li>
						<li><?php esc_html_e( 'برای حرکت هر ابزار یا کانتینر، پیشرفته ← حرکت ساپونین را باز کنید. نمایش محصول، تنظیمات حرکت اختصاصی هم دارد.', 'saponine-studio' ); ?></li>
						<li><?php esc_html_e( 'برای افزودن بخش تازه، از ابزارهای استاندارد المنتور یا دستهٔ ساپونین استودیو استفاده کنید؛ سپس دکمهٔ انتشار/به‌روزرسانی را بزنید.', 'saponine-studio' ); ?></li>
					</ol>
					<p><?php esc_html_e( 'این بسته ویترین معرفی محصولات است. فروشگاه، پرداخت و موجودی کالا به راه‌اندازی جداگانهٔ ووکامرس نیاز دارد.', 'saponine-studio' ); ?></p>
				</section>
			</div>
			<form class="sn-admin-card" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<h2><?php esc_html_e( '۲. سربرگ، پابرگ و پیش‌فرض‌های سایت', 'saponine-studio' ); ?></h2>
				<input type="hidden" name="action" value="saponine_save_settings">
				<?php wp_nonce_field( 'saponine_save_settings' ); ?>
				<div class="sn-admin-grid">
					<div><?php self::select_section( 'header_id', __( 'سربرگ', 'saponine-studio' ), $s['header_id'], $templates ); ?></div>
					<div><?php self::select_section( 'footer_id', __( 'پابرگ', 'saponine-studio' ), $s['footer_id'], $templates ); ?></div>
				</div>
				<p><?php esc_html_e( 'نمایش خودکار این الگوها در پوستهٔ Saponine Flex انجام می‌شود. در پوسته‌های دیگر باید مکان‌های نمایش سازگار تعریف شود.', 'saponine-studio' ); ?></p>
				<div class="sn-admin-colors">
				<?php foreach ( array( 'primary' => __( 'رنگ اصلی', 'saponine-studio' ), 'secondary' => __( 'رنگ مکمل', 'saponine-studio' ), 'background' => __( 'زمینه', 'saponine-studio' ), 'text' => __( 'متن', 'saponine-studio' ), 'muted' => __( 'متن ثانویه', 'saponine-studio' ), 'border' => __( 'مرز و خطوط', 'saponine-studio' ), 'accent' => __( 'رنگ تأکیدی / هاور پابرگ', 'saponine-studio' ) ) as $key => $label ) : ?>
					<label class="sn-admin-field"><span><?php echo esc_html( $label ); ?></span><input type="color" name="saponine[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $s[ $key ] ); ?>"></label>
				<?php endforeach; ?>
				</div>
				<div class="sn-admin-grid">
				<?php foreach ( array( 'font_body' => array( 'label' => __( 'قلم فارسی', 'saponine-studio' ), 'options' => array( 'Vazirmatn', 'Tahoma', 'Arial' ) ), 'font_latin' => array( 'label' => __( 'قلم لاتین', 'saponine-studio' ), 'options' => array( 'Outfit', 'Arial', 'Georgia' ) ) ) as $key => $font ) : ?>
					<label class="sn-admin-field"><span><?php echo esc_html( $font['label'] ); ?></span><select name="saponine[<?php echo esc_attr( $key ); ?>]">
					<?php foreach ( $font['options'] as $option ) : ?><option value="<?php echo esc_attr( $option ); ?>" <?php selected( $s[ $key ], $option ); ?>><?php echo esc_html( $option ); ?></option><?php endforeach; ?>
					</select></label>
				<?php endforeach; ?>
				</div>
				<p class="description"><?php esc_html_e( 'این‌ها پیش‌فرض‌های طرح‌اند. انتخاب رنگ و قلم اختصاصی در تنظیمات هر ابزار المنتور، بر این پیش‌فرض‌ها اولویت دارد.', 'saponine-studio' ); ?></p>
				<label class="sn-admin-check"><input type="checkbox" name="saponine[allow_google_fonts]" value="yes" <?php checked( $s['allow_google_fonts'], 'yes' ); ?>> <?php esc_html_e( 'بارگیری فونت‌های آنلاین المنتور (نیازمند دسترسی به Google Fonts)', 'saponine-studio' ); ?></label>
				<p class="description"><?php esc_html_e( 'فونت‌های محلی Vazirmatn و Outfit بدون این گزینه در دسترس‌اند. این تنظیم فقط در پوستهٔ Saponine Flex اعمال می‌شود؛ برای قلم آنلاین دیگری که در المنتور انتخاب می‌کنید، این گزینه را فعال کنید.', 'saponine-studio' ); ?></p>
				<h3><?php esc_html_e( 'اسکرول بخش‌به‌بخش', 'saponine-studio' ); ?></h3>
				<label class="sn-admin-check"><input type="checkbox" name="saponine[section_scroll_enabled]" value="yes" <?php checked( $s['section_scroll_enabled'], 'yes' ); ?>> <?php esc_html_e( 'هر حرکت اسکرول روی ابتدای بخش بعدی یا قبلی متوقف شود', 'saponine-studio' ); ?></label>
				<div class="sn-admin-grid">
					<label class="sn-admin-field"><span><?php esc_html_e( 'مدت جابه‌جایی (میلی‌ثانیه)', 'saponine-studio' ); ?></span><input type="number" name="saponine[section_scroll_duration]" min="0" max="1500" step="50" value="<?php echo esc_attr( $s['section_scroll_duration'] ); ?>"></label>
					<label class="sn-admin-field"><span><?php esc_html_e( 'آستانهٔ شروع حرکت', 'saponine-studio' ); ?></span><input type="number" name="saponine[section_scroll_threshold]" min="8" max="160" step="1" value="<?php echo esc_attr( $s['section_scroll_threshold'] ); ?>"></label>
					<label class="sn-admin-field"><span><?php esc_html_e( 'رفتار در موبایل', 'saponine-studio' ); ?></span><select name="saponine[section_scroll_mobile]"><option value="native" <?php selected( $s['section_scroll_mobile'], 'native' ); ?>><?php esc_html_e( 'اسکرول معمولی برای محتوای بلند', 'saponine-studio' ); ?></option><option value="sections" <?php selected( $s['section_scroll_mobile'], 'sections' ); ?>><?php esc_html_e( 'توقف روی بخش‌ها', 'saponine-studio' ); ?></option></select></label>
				</div>
				<p class="description"><?php esc_html_e( 'این تنظیم روی بخش‌های طرح ساپونین اعمال می‌شود و در ویرایشگر المنتور غیرفعال است. محتوای بلند هر بخش و پنجرهٔ محصول همچنان قابل اسکرول می‌ماند. مقدار کمتر آستانه، حرکت را حساس‌تر می‌کند.', 'saponine-studio' ); ?></p>
				<?php submit_button( __( 'ذخیرهٔ تنظیمات', 'saponine-studio' ) ); ?>
			</form>
			<?php self::multilingual_panel(); ?>
			<?php Auto_Translate::render_panel(); ?>
		</div>
		<?php
	}
}
