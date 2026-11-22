<?php
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Service_Pages {
	const OPTION = 'saponine_services';
	const APPLICATION = 'sn_application';
	const MAX_PDF = 2097152;

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'register_applications' ) );
		add_action( 'admin_menu', static function () {
			add_options_page( 'خدمات ساپونین', 'خدمات ساپونین', 'manage_options', 'saponine-services', array( __CLASS__, 'admin' ) );
		} );
		add_action( 'admin_init', static function () {
			register_setting( 'saponine_services', self::OPTION, array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) ) );
		} );
		add_action( 'admin_post_nopriv_sn_apply', array( __CLASS__, 'submit_application' ) );
		add_action( 'admin_post_sn_apply', array( __CLASS__, 'submit_application' ) );
		add_action( 'admin_post_sn_download_cv', array( __CLASS__, 'download_cv' ) );
		add_action( 'add_meta_boxes_' . self::APPLICATION, static function () {
			add_meta_box( 'sn-application-details', 'اطلاعات درخواست', array( __CLASS__, 'application_box' ), self::APPLICATION );
		} );
	}

	private static function defaults() {
		$pharmacies = array();
		foreach ( array(
			array( 'تهران', 'تهران', 'بهار' ), array( 'اصفهان', 'اصفهان', 'نقش جهان' ),
			array( 'فارس', 'شیراز', 'نارنج' ), array( 'خراسان رضوی', 'مشهد', 'سپیده' ),
			array( 'آذربایجان شرقی', 'تبریز', 'سهند' ), array( 'گیلان', 'رشت', 'باران' ),
			array( 'یزد', 'یزد', 'ترمه' ), array( 'البرز', 'کرج', 'سرو' ), array( 'تهران', 'تهران', 'نیلوفر' ),
		) as $index => $row ) {
			$pharmacies[] = array( 'name' => 'داروخانه ' . $row[2], 'province' => $row[0], 'city' => $row[1],
				'address' => 'خیابان نمونه، گذر ' . $row[2] . '، ساختمان ' . ( $index + 1 ), 'phone' => 'XXXX–' . ( 1000 + $index ), 'sample' => '1' );
		}
		return array(
			'pharmacies' => $pharmacies,
			'contact' => array( 'phone' => '۰۲۱–XXXX–۱۰۰۰', 'fax' => '۰۲۱–XXXX–۱۰۰۱', 'email' => 'info@saponine.example',
				'address' => 'تهران، خیابان نمونه، کوچه بهار، ساختمان سپید، طبقه دوم', 'lat' => '35.70', 'lon' => '51.40', 'neshan' => '', 'sample' => '1' ),
			'faq' => array(
				array( 'topic' => 'محصولات', 'question' => 'محصولات ساپونین را از کجا ببینم؟', 'answer' => 'از فهرست بالای صفحه وارد «محصولات» شوید. با انتخاب تصویر یا نام محصول، صفحه معرفی آن باز می‌شود.' ),
				array( 'topic' => 'محصولات', 'question' => 'چطور محصولات یک برند را پیدا کنم؟', 'answer' => 'در آرشیو محصولات، برند را انتخاب کنید تا فهرست همان برند نمایش داده شود.' ),
				array( 'topic' => 'داروخانه‌ها', 'question' => 'چگونه داروخانه‌های استان خود را پیدا کنم؟', 'answer' => 'در صفحه داروخانه‌ها، نام استان را جستجو کنید یا روی آن در نقشه بزنید. فهرست زیر نقشه با انتخاب شما تغییر می‌کند.' ),
				array( 'topic' => 'داروخانه‌ها', 'question' => 'برای اطلاع از موجودی چه کنم؟', 'answer' => 'پس از انتخاب داروخانه، از شماره‌ای که در کارت آن درج شده است برای استعلام موجودی استفاده کنید. کارت‌های دارای برچسب نمونه صرفاً برای نمایش قالب هستند.' ),
				array( 'topic' => 'ارتباط', 'question' => 'راه‌های ارتباط با ساپونین کدام‌اند؟', 'answer' => 'نشانی، تلفن و ایمیل در صفحه «تماس با ما» درج شده‌اند. اگر اطلاعات نمایشی باشند با برچسب نمونه مشخص می‌شوند.' ),
				array( 'topic' => 'همکاری', 'question' => 'چگونه درخواست همکاری ارسال کنم؟', 'answer' => 'در «فرم همکاری» مشخصات و موقعیت شغلی مورد نظر خود را وارد کنید و رزومه را در قالب PDF بفرستید.' ),
			),
			'process' => array(
				array( 'title' => 'ارسال رزومه', 'latin' => 'Submit resume', 'text' => 'مشخصات، موقعیت مورد نظر و رزومه خود را در فرم ثبت کنید.' ),
				array( 'title' => 'گفت‌وگوی مقدماتی', 'latin' => 'First conversation', 'text' => 'در صورت تطابق اولیه، درباره علایق حرفه‌ای و انتظارات دو طرف گفت‌وگو می‌کنیم.' ),
				array( 'title' => 'مصاحبه تخصصی', 'latin' => 'Technical interview', 'text' => 'در این مرحله، تجربه‌ها و مهارت‌های مرتبط با فرصت شغلی بررسی می‌شوند.' ),
				array( 'title' => 'مصاحبه نهایی', 'latin' => 'Final interview', 'text' => 'درباره شرح وظایف، شیوه همکاری و پرسش‌های باقی‌مانده صحبت می‌کنیم.' ),
				array( 'title' => 'آغاز همکاری', 'latin' => 'Onboarding', 'text' => 'پس از توافق، زمان شروع و برنامه آشنایی با تیم مشخص می‌شود.' ),
			),
			'careerfaq' => array(
				array( 'question' => 'رزومه را در چه قالبی ارسال کنم؟', 'answer' => 'یک فایل PDF با حجم حداکثر دو مگابایت انتخاب کنید.' ),
				array( 'question' => 'می‌توانم موقعیت شغلی دلخواهم را معرفی کنم؟', 'answer' => 'بله. عنوان نقش یا حوزه مورد علاقه خود را در کادر «فرصت شغلی» بنویسید.' ),
				array( 'question' => 'چه کسی رزومه‌ام را می‌بیند؟', 'answer' => 'درخواست و فایل رزومه به‌صورت خصوصی در اختیار مدیران مجاز سایت قرار می‌گیرد.' ),
				array( 'question' => 'چطور از ثبت درخواست مطمئن شوم؟', 'answer' => 'پس از ثبت در سایت، پیام دریافت درخواست در همین صفحه نمایش داده می‌شود.' ),
			),
		);
	}

	private static function data() { return array_replace( self::defaults(), (array) get_option( self::OPTION, array() ) ); }
	public static function contact_values() { return self::data()['contact']; }
	private static function provinces() {
		static $rows = null;
		if ( null === $rows ) {
			// Simplified Natural Earth 1:10m admin-1 geometry, public domain.
			$file = SAPONINE_STUDIO_PATH . 'assets/images/province-paths.json';
			$rows = is_readable( $file ) ? (array) json_decode( file_get_contents( $file ), true ) : array();
		}
		return $rows;
	}
	private static function groups() {
		return array(
			'pharmacies' => array( 'داروخانه‌ها', array( 'name'=>'نام داروخانه', 'province'=>'استان', 'city'=>'شهر', 'address'=>'نشانی', 'phone'=>'تلفن', 'sample'=>'اطلاعات نمونه' ) ),
			'faq' => array( 'سوالات متداول', array( 'topic'=>'موضوع', 'question'=>'پرسش', 'answer'=>'پاسخ' ) ),
			'process' => array( 'مراحل همکاری', array( 'title'=>'عنوان', 'latin'=>'عنوان لاتین', 'text'=>'توضیح' ) ),
			'careerfaq' => array( 'پرسش‌های همکاری', array( 'question'=>'پرسش', 'answer'=>'پاسخ' ) ),
		);
	}
	private static function contact_fields() {
		return array( 'phone'=>'تلفن', 'fax'=>'فکس', 'email'=>'ایمیل', 'address'=>'نشانی', 'lat'=>'عرض جغرافیایی', 'lon'=>'طول جغرافیایی', 'neshan'=>'پیوند نشان (اختیاری)', 'sample'=>'اطلاعات نمونه' );
	}
	public static function sanitize( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		$out = array( 'contact' => array() );
		foreach ( self::contact_fields() as $key => $label ) {
			$value = isset( $raw['contact'][ $key ] ) && is_scalar( $raw['contact'][ $key ] ) ? trim( (string) $raw['contact'][ $key ] ) : '';
			switch ( $key ) {
				case 'sample': $value = '1' === $value ? '1' : ''; break;
				case 'email': $value = sanitize_email( $value ); break;
				case 'neshan': $value = esc_url_raw( $value, array( 'https' ) ); break;
				case 'lat':
				case 'lon':
					$limit = 'lat' === $key ? 90 : 180;
					$value = is_numeric( $value ) && abs( (float) $value ) <= $limit ? strval( (float) $value ) : '';
					break;
				default: $value = sanitize_text_field( $value );
			}
			$out['contact'][ $key ] = $value;
		}
		$provinces = array_column( self::provinces(), 'name' );
		foreach ( self::groups() as $key => $group ) {
			$out[ $key ] = array();
			foreach ( (array) ( $raw[ $key ] ?? array() ) as $row ) {
				if ( ! is_array( $row ) ) { continue; }
				$clean = array();
				foreach ( $group[1] as $field => $label ) {
					$value = isset( $row[ $field ] ) && is_scalar( $row[ $field ] ) ? (string) $row[ $field ] : '';
					$clean[ $field ] = 'sample' === $field ? ( '1' === $value ? '1' : '' ) : sanitize_textarea_field( $value );
				}
				if ( '' === reset( $clean ) || ( 'pharmacies' === $key && ! in_array( $clean['province'], $provinces, true ) ) ) { continue; }
				$out[ $key ][] = $clean;
			}
		}
		return $out;
	}
	private static function admin_field( $name, $field, $label, $value ) {
		echo '<label style="display:inline-grid;gap:3px;margin:5px 8px;vertical-align:middle">' . esc_html( $label );
		if ( 'sample' === $field ) {
			echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( $value, '1', false ) . '>';
		} elseif ( 'province' === $field ) {
			echo '<select name="' . esc_attr( $name ) . '"><option value="">انتخاب استان</option>';
			foreach ( self::provinces() as $p ) { echo '<option ' . selected( $value, $p['name'], false ) . '>' . esc_html( $p['name'] ) . '</option>'; }
			echo '</select>';
		} elseif ( in_array( $field, array( 'text', 'answer', 'address' ), true ) ) {
			echo '<textarea name="' . esc_attr( $name ) . '" rows="2" cols="34">' . esc_textarea( $value ) . '</textarea>';
		} else {
			echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		}
		echo '</label>';
	}
	public static function admin() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$data = self::data();
		echo '<div class="wrap" dir="rtl"><h1>خدمات ساپونین</h1><form method="post" action="options.php">';
		settings_fields( 'saponine_services' );
		echo '<h2>تماس و موقعیت روی نقشه</h2>';
		foreach ( self::contact_fields() as $key=>$label ) { self::admin_field( self::OPTION . '[contact][' . $key . ']', $key, $label, $data['contact'][ $key ] ?? '' ); }
		foreach ( self::groups() as $key => $group ) {
			echo '<hr><h2>' . esc_html( $group[0] ) . '</h2><div data-sns-edit-list>';
			foreach ( array_merge( $data[ $key ], array( array() ) ) as $i => $row ) {
				echo '<div style="background:#fff;margin:8px 0;padding:8px" data-sns-edit-row>';
				foreach ( $group[1] as $field=>$label ) {
					self::admin_field( self::OPTION . '[' . $key . '][' . $i . '][' . $field . ']', $field, $label, $row[ $field ] ?? '' );
				}
				echo '<button type="button" class="button" data-sns-remove>حذف این ردیف</button></div>';
			}
			echo '<button type="button" class="button" data-sns-add>افزودن ردیف</button></div>';
		}
		submit_button();
		echo '</form></div>';
		?>
		<script>document.querySelectorAll('[data-sns-edit-list]').forEach(list=>{
			const source=list.querySelector('[data-sns-edit-row]:last-of-type').cloneNode(true);
			let next=list.querySelectorAll('[data-sns-edit-row]').length;
			list.addEventListener('click',event=>{
				if(event.target.matches('[data-sns-remove]'))event.target.closest('[data-sns-edit-row]').remove();
				if(!event.target.matches('[data-sns-add]'))return;
				const row=source.cloneNode(true);
				row.querySelectorAll('[name]').forEach(field=>{field.name=field.name.replace(/\[\d+\]/,'['+next+']');if(field.type==='checkbox')field.checked=false;else field.value='';});
				next++;list.insertBefore(row,event.target);
			});
		});</script>
		<?php
	}
	private static function banner( $title, $description='' ) {
		echo '<header class="sns-banner"><h1>' . esc_html( $title ) . '</h1>';
		if ( $description ) { echo '<p>' . esc_html( $description ) . '</p>'; }
		echo '</header>';
	}
	private static function icon( $name ) {
		$paths = array(
			'resume'=>'<rect x="3" y="5" width="18" height="15" rx="3"/><circle cx="8.5" cy="10.5" r="1.8"/><path d="M6 15c.8-2 4.1-2 5 0m3-5h4m-4 4h4"/>',
			'chat'=>'<path d="M4 16a7 7 0 1 1 3 2l-4 1 1-3Zm9 3a6 6 0 0 0 6 0l3 1-1-3a6 6 0 0 0-2-8"/><path d="M7 11h.1m3 0h.1m3 0h.1"/>',
			'award'=>'<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M7 8h10M7 11h4"/><circle cx="15" cy="15" r="3"/><path d="m13 18-1 4 3-1 3 1-1-4"/>',
			'person'=>'<circle cx="10" cy="7" r="3.5"/><path d="M3 20v-2c0-3.5 3-5.5 7-5.5 2.3 0 4 .4 5.3 1.3M15 17l2.3 2.3 4.5-5"/>',
			'start'=>'<path d="M5 21V4c4-3 8 3 14 0v10c-6 3-10-3-14 0"/>',
			'phone'=>'<path d="m7 3 3 4-2 3c1 3 3 5 6 6l3-2 4 3-1 3c-1 2-5 1-9-2C6 15 2 9 3 6l1-3z"/>',
			'fax'=>'<path d="M7 8V3h10v5M7 17H4V9h16v8h-3"/><path d="M7 14h10v7H7zM16 11h1"/>',
			'email'=>'<rect x="2.5" y="4.5" width="19" height="15" rx="3"/><path d="m3.5 6 8.5 7L20.5 6"/>',
			'address'=>'<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/>',
		);
		return '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? $paths['start'] ) . '</svg>';
	}
	public static function render( $section, $args=array() ) {
		$data = self::data();
		echo '<div class="sns-service" dir="rtl" data-sns-page="' . esc_attr( $section ) . '">';
		switch ( $section ) {
			case 'pharmacies': self::pharmacies( $data['pharmacies'] ); break;
			case 'faq': self::faq( $data['faq'] ); break;
			case 'contact': self::contact( $data['contact'] ); break;
			case 'careers': self::careers( $data ); break;
		}
		echo '</div>';
	}
	private static function search( $placeholder, $key ) {
		echo '<label class="sns-search"><svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="10.8" cy="10.8" r="7.6"/><path d="m16.5 16.5 4.5 4.5"/></svg><input type="search" aria-label="' . esc_attr( $placeholder ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-sns-search="' . esc_attr( $key ) . '"></label>';
	}
	private static function pharmacies( $rows ) {
		$provinces = self::provinces();
		self::banner( 'آرشیو داروخانه‌ها', 'داروخانه‌های عرضه‌کننده محصولات ساپونین را در استان خود پیدا کنید.' );
		echo '<div class="sns-wrap"><div class="sns-directory" id="pharmacy-directory"><div class="sns-directory-search"><h2>مشاهده داروخانه‌ها براساس استان</h2>';
		self::search( 'نام استان، شهر یا داروخانه را وارد کنید…', 'pharmacy' );
		echo '</div><div class="sns-map"><svg viewBox="0 0 820 775" aria-label="نقشه استان‌های ایران" role="group">';
		foreach ( $provinces as $p ) {
			echo '<path role="button" tabindex="0" aria-pressed="false" aria-label="' . esc_attr( $p['name'] ) . '" data-sns-region="' . esc_attr( $p['name'] ) . '" data-label-x="' . intval( $p['label'][0] ) . '" data-label-y="' . intval( $p['label'][1] ) . '" d="' . esc_attr( $p['d'] ) . '"></path>';
		}
		echo '</svg><span data-sns-map-label aria-hidden="true" hidden></span></div><div class="sns-provinces">';
		foreach ( $provinces as $p ) {
			echo '<button type="button" aria-pressed="false" data-sns-province="' . esc_attr( $p['name'] ) . '">' . esc_html( $p['name'] ) . '</button>';
		}
		echo '</div></div><section class="sns-results"><h2>داروخانه‌ها</h2><div class="sns-summary"><span role="status" data-sns-count></span> <button type="button" data-sns-clear hidden>نمایش همه</button></div><div class="sns-cards">';
		foreach ( $rows as $row ) {
			echo '<article class="sns-place" data-sns-place="' . esc_attr( $row['province'] ) . '"><header><span aria-hidden="true">✚</span><h3>' . esc_html( $row['name'] ) . '</h3></header>';
			echo '<p>' . esc_html( $row['province'] . '، ' . $row['city'] ) . '</p><p>' . esc_html( $row['address'] ) . '</p>';
			if ( $row['phone'] ) { echo '<p><bdi>' . esc_html( $row['phone'] ) . '</bdi></p>'; }
			if ( $row['sample'] ) { echo '<small>اطلاعات نمونه</small>'; }
			echo '</article>';
		}
		echo '</div><p class="sns-empty" data-sns-empty ' . ( $rows ? 'hidden' : '' ) . '>داروخانه‌ای برای این انتخاب پیدا نشد.</p></section></div>';
	}
	private static function accordion( $rows ) {
		foreach ( $rows as $row ) {
			echo '<details class="sns-question" data-sns-topic="' . esc_attr( $row['topic'] ?? '' ) . '"><summary>' . esc_html( $row['question'] ) . '</summary><p>' . nl2br( esc_html( $row['answer'] ) ) . '</p></details>';
		}
	}
	private static function faq( $rows ) {
		self::banner( 'سوالات متداول', 'پاسخ پرسش‌های خود درباره محصولات ساپونین، داروخانه‌ها و همکاری با ما را پیدا کنید.' );
		echo '<label class="sns-topic">انتخاب موضوع<select data-sns-topic-filter><option value="">همه موضوعات</option>';
		foreach ( array_unique( array_column( $rows, 'topic' ) ) as $topic ) { echo '<option>' . esc_html( $topic ) . '</option>'; }
		echo '</select></label><div class="sns-wrap sns-narrow">';
		self::search( 'جستجو در میان سوالات…', 'faq' );
		self::accordion( $rows );
		echo '<p class="sns-empty" role="status" data-sns-empty ' . ( $rows ? 'hidden' : '' ) . '>پرسشی با این مشخصات پیدا نشد.</p></div>';
	}
	private static function contact( $c ) {
		self::banner( 'تماس با ما' );
		echo '<div class="sns-wrap sns-contact"><div class="sns-contact-map">';
		$located = '' !== $c['lat'] && '' !== $c['lon'];
		if ( $located && empty( $c['sample'] ) ) {
			$x = (float) $c['lon']; $y = (float) $c['lat'];
			$bbox = implode( ',', array( $x-.015, $y-.01, $x+.015, $y+.01 ) );
			$url = add_query_arg( array( 'bbox'=>$bbox, 'layer'=>'mapnik', 'marker'=>$y . ',' . $x ), 'https://www.openstreetmap.org/export/embed.html' );
			echo '<iframe title="موقعیت روی نقشه" loading="lazy" referrerpolicy="no-referrer" src="' . esc_url( $url ) . '"></iframe>';
		} else {
			echo '<div class="sns-street-grid" aria-hidden="true"></div>' . ( $located ? '<div class="sns-map-pin" aria-hidden="true"></div>' : '' ) . '<p class="sns-map-caption">' . ( $located ? 'موقعیت نمونه · نقشه شماتیک' : 'موقعیت روی نقشه ثبت نشده است.' ) . '</p>';
		}
		echo '<div class="sns-directions">';
		if ( $located ) {
			$coords = $c['lat'] . ',' . $c['lon'];
			$links = array( 'گوگل مپ'=>'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $coords ), 'ویز'=>'https://www.waze.com/ul?ll=' . rawurlencode( $coords ) . '&navigate=yes' );
			if ( ! empty( $c['neshan'] ) ) { $links['نشان'] = $c['neshan']; }
			foreach ( $links as $label=>$url ) { echo '<a target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>'; }
		}
		echo '</div></div><section class="sns-contact-info"><h2>راه‌های ارتباط</h2>';
		self::contact_details( $c );
		echo '</section></div>';
	}

	public static function contact_details( $c ) {
		if ( $c['sample'] ) { echo '<small>اطلاعات نمونه</small>'; }
		echo '<dl>';
		foreach ( array( 'phone'=>'شماره تماس', 'fax'=>'فکس', 'email'=>'ایمیل', 'address'=>'آدرس' ) as $field=>$label ) {
			if ( empty( $c[ $field ] ) ) { continue; }
			$value = esc_html( $c[ $field ] );
			if ( empty( $c['sample'] ) && 'phone' === $field ) { $value = '<a href="tel:' . esc_attr( preg_replace( '/[^+0-9۰-۹٠-٩]/u', '', $c[ $field ] ) ) . '">' . $value . '</a>'; }
			if ( empty( $c['sample'] ) && 'email' === $field ) { $value = '<a href="mailto:' . esc_attr( $c[ $field ] ) . '">' . $value . '</a>'; }
			echo '<dt><span aria-hidden="true">' . self::icon( $field ) . '</span>' . esc_html( $label ) . '</dt><dd><bdi>' . $value . '</bdi></dd>';
		}
		echo '</dl>';
	}

	private static function application_fields() {
		return array(
			'name'=>array( 'نام و نام خانوادگی', 'text', true ),
			'job'=>array( 'فرصت شغلی مورد نظر', 'text', true ),
			'city'=>array( 'محل سکونت', 'text', true ),
			'birth'=>array( 'تاریخ تولد', 'text', false ),
			'phone'=>array( 'شماره موبایل', 'tel', true ),
			'gender'=>array( 'جنسیت', array( 'زن', 'مرد', 'ترجیح می‌دهم نگویم' ), false ),
			'degree'=>array( 'مقطع تحصیلی', array( 'دیپلم', 'کاردانی', 'کارشناسی', 'کارشناسی ارشد', 'دکتری', 'سایر' ), false ),
			'major'=>array( 'رشته تحصیلی', 'text', false ),
			'work'=>array( 'تمایل به کار', array( 'حضوری', 'دورکاری', 'ترکیبی' ), false ),
			'email'=>array( 'ایمیل', 'email', true ),
		);
	}
	private static function careers( $data ) {
		self::banner( 'فرم همکاری با ما', 'برای معرفی خود به تیم ساپونین، موقعیت شغلی مورد نظرتان را بنویسید و رزومه خود را پیوست کنید.' );
		echo '<div class="sns-wrap sns-narrow">';
		if ( isset( $_GET['sn_application'] ) && 'received' === $_GET['sn_application'] ) { echo '<p class="sns-notice" role="status">درخواست همکاری شما دریافت شد.</p>'; }
		echo '<form class="sns-career-form" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" enctype="multipart/form-data" data-sns-apply><input type="hidden" name="action" value="sn_apply">';
		wp_nonce_field( 'saponine_apply', 'sn_apply_nonce', false );
		foreach ( self::application_fields() as $key=>$field ) {
			$id = 'sns-apply-' . $key;
			echo '<label class="sns-field" for="' . esc_attr( $id ) . '"><span>' . esc_html( $field[0] ) . ( $field[2] ? ' <b aria-hidden="true">*</b>' : '' ) . '</span>';
			$attributes = ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '"' . ( $field[2] ? ' required' : '' );
			if ( is_array( $field[1] ) ) {
				echo '<select' . $attributes . '><option value="">انتخاب کنید</option>';
				foreach ( $field[1] as $option ) { echo '<option>' . esc_html( $option ) . '</option>'; }
				echo '</select>';
			} else {
				echo '<input type="' . esc_attr( $field[1] ) . '"' . $attributes . ' maxlength="160" placeholder="' . esc_attr( $field[0] ) . '">';
			}
			echo '</label>';
		}
		echo '<label class="sns-trap" aria-hidden="true">نشانی وب<input type="text" name="website" tabindex="-1" autocomplete="off"></label>';
		echo '<label class="sns-upload"><span aria-hidden="true">↑</span><strong>بارگذاری رزومه</strong><small>فایل PDF، حداکثر ۲ مگابایت</small><input type="file" name="cv" accept="application/pdf,.pdf" required></label>';
		echo '<button type="submit" class="sns-send">ارسال درخواست</button><p class="sns-form-note" role="status" data-sns-form-note></p></form></div>';
		echo '<section class="sns-wrap sns-hiring"><h2>فرآیند استخدام در ساپونین</h2><div class="sns-process" data-sns-process>';
		$icons = array( 'resume', 'chat', 'award', 'person', 'start' );
		foreach ( $data['process'] as $index => $step ) {
			echo '<article><span class="sns-process-mark" aria-hidden="true">' . self::icon( $icons[ $index ] ?? 'start' ) . '</span><h3>' . esc_html( $step['title'] ) . '</h3><small lang="en" dir="ltr">' . esc_html( $step['latin'] ) . '</small><p>' . esc_html( $step['text'] ) . '</p></article>';
		}
		echo '</div><div class="sns-process-nav"><button type="button" aria-label="مرحله قبلی" data-sns-step="-1">→</button><button type="button" aria-label="مرحله بعدی" data-sns-step="1">←</button></div></section><section class="sns-wrap sns-narrow"><h2>پاسخ به پرسش‌های شما</h2>';
		self::accordion( $data['careerfaq'] );
		echo '</section>';
	}
	public static function register_applications() {
		$caps = array();
		foreach ( array( 'read', 'read_post', 'edit_posts', 'edit_post', 'edit_others_posts', 'delete_post', 'delete_posts', 'delete_others_posts', 'delete_private_posts', 'edit_private_posts', 'read_private_posts', 'publish_posts' ) as $cap ) { $caps[ $cap ] = 'manage_options'; }
		$caps['create_posts'] = 'do_not_allow';
		register_post_type( self::APPLICATION, array(
			'labels' => array( 'name'=>'درخواست‌های همکاری', 'singular_name'=>'درخواست همکاری', 'edit_item'=>'مشاهده درخواست همکاری' ),
			'public'=>false, 'publicly_queryable'=>false, 'exclude_from_search'=>true, 'show_in_rest'=>false,
			'show_ui'=>true, 'show_in_nav_menus'=>false, 'show_in_admin_bar'=>false, 'can_export'=>false,
			'capabilities'=>$caps, 'map_meta_cap'=>false, 'rewrite'=>false, 'query_var'=>false, 'supports'=>array( 'title' ), 'menu_icon'=>'dashicons-portfolio',
		) );
	}
	private static function refuse( $message, $status=400 ) {
		wp_die( esc_html( $message ), 'فرم همکاری ساپونین', array( 'response'=>$status, 'back_link'=>true ) );
	}
	public static function submit_application() {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { self::refuse( 'درخواست را از فرم همکاری بفرستید.', 405 ); }
		check_admin_referer( 'saponine_apply', 'sn_apply_nonce' );
		if ( ! empty( $_POST['website'] ) ) { self::refuse( 'ثبت این درخواست ممکن نیست.' ); }
		$throttle = 'sns_apply_' . substr( hash_hmac( 'sha256', $_SERVER['REMOTE_ADDR'] ?? 'local', wp_salt( 'nonce' ) ), 0, 32 );
		if ( get_transient( $throttle ) ) { self::refuse( 'برای درخواست بعدی یک دقیقه صبر کنید.', 429 ); }
		$values = array();
		foreach ( self::application_fields() as $key=>$field ) {
			$value = isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			if ( strlen( $value ) > 640 || ( $field[2] && '' === $value ) || ( is_array( $field[1] ) && $value && ! in_array( $value, $field[1], true ) ) ) { self::refuse( 'لطفاً فیلد «' . $field[0] . '» را بررسی کنید.' ); }
			$values[ $key ] = $value;
		}
		$digits = preg_replace( '/[\s()\-]/u', '', $values['phone'] );
		if ( ! is_email( $values['email'] ) || ! preg_match( '/^\+?\p{Nd}{7,15}$/u', $digits ) ) { self::refuse( 'ایمیل و شماره تماس را بررسی کنید.' ); }
		$file = $_FILES['cv'] ?? array();
		if ( ! isset( $file['tmp_name'], $file['error'], $file['name'] ) || ! is_string( $file['tmp_name'] ) || ! is_string( $file['name'] ) ||
			UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ||
			filesize( $file['tmp_name'] ) > self::MAX_PDF || 'pdf' !== strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) ) {
			self::refuse( 'رزومه را به‌صورت یک فایل PDF تا دو مگابایت انتخاب کنید.' );
		}
		$bytes = file_get_contents( $file['tmp_name'] );
		if ( ! is_string( $bytes ) || 0 !== strpos( $bytes, '%PDF-' ) || false === strpos( substr( $bytes, -2048 ), '%%EOF' ) ) { self::refuse( 'فایل انتخاب‌شده PDF معتبر نیست.' ); }
		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$type = $finfo ? finfo_file( $finfo, $file['tmp_name'] ) : '';
			if ( $finfo ) { finfo_close( $finfo ); }
			if ( $type && 'application/pdf' !== $type ) { self::refuse( 'فایل انتخاب‌شده PDF معتبر نیست.' ); }
		}
		$id = wp_insert_post( wp_slash( array( 'post_type'=>self::APPLICATION, 'post_status'=>'private', 'post_title'=>$values['name'] . ' — ' . $values['job'], 'comment_status'=>'closed', 'ping_status'=>'closed' ) ), true );
		if ( is_wp_error( $id ) ) { self::refuse( 'ذخیره درخواست ممکن نشد. دوباره تلاش کنید.', 500 ); }
		// No attachment or URL is created. Only the capability-checked admin
		// endpoint below can deliver the PDF; post deletion removes its metadata.
		if ( ! add_post_meta( $id, '_sns_application', wp_slash( $values ), true ) || ! add_post_meta( $id, '_sns_cv', base64_encode( $bytes ), true ) ) {
			wp_delete_post( $id, true );
			self::refuse( 'ذخیره رزومه ممکن نشد. دوباره تلاش کنید.', 500 );
		}
		set_transient( $throttle, 1, MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'sn_application', 'received', Portal::url( 'careers' ) ), 303 );
		exit;
	}
	public static function application_box( $post ) {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$values = (array) get_post_meta( $post->ID, '_sns_application', true );
		echo '<dl>';
		foreach ( self::application_fields() as $key=>$field ) {
			if ( ! empty( $values[ $key ] ) ) { echo '<dt><strong>' . esc_html( $field[0] ) . '</strong></dt><dd>' . esc_html( $values[ $key ] ) . '</dd>'; }
		}
		$url = wp_nonce_url( add_query_arg( array( 'action'=>'sn_download_cv', 'application'=>$post->ID ), admin_url( 'admin-post.php' ) ), 'sn-cv-' . $post->ID );
		echo '</dl><a class="button button-primary" href="' . esc_url( $url ) . '">دریافت رزومه PDF</a>';
	}
	public static function download_cv() {
		$id = absint( $_GET['application'] ?? 0 );
		if ( ! current_user_can( 'manage_options' ) || self::APPLICATION !== get_post_type( $id ) ) { self::refuse( 'دسترسی مجاز نیست.', 403 ); }
		check_admin_referer( 'sn-cv-' . $id );
		$data = base64_decode( get_post_meta( $id, '_sns_cv', true ), true );
		if ( ! $data ) { self::refuse( 'فایل موجود نیست.', 404 ); }
		nocache_headers();
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="resume-' . $id . '.pdf"' );
		header( 'Content-Length: ' . strlen( $data ) );
		echo $data;
		exit;
	}
}
