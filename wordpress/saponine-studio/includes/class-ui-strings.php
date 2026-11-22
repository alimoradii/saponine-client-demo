<?php
/** Optional translation of fixed public interface labels through Polylang Strings. */
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class UI_Strings {
	public static function boot() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_filter( 'gettext', array( __CLASS__, 'translate' ), 20, 3 );
	}

	private static function strings() {
		return array(
			'saponine-flex' => array( 'رفتن به محتوا', 'فهرست اصلی', 'جستجو', 'جستجو در سایت…', 'این صفحه پیدا نشد.', 'بازگشت به صفحهٔ اصلی', 'هنوز محتوایی منتشر نشده است.', 'جستجو: %s', 'موردی پیدا نشد. عبارت دیگری را امتحان کنید.', 'دیدگاه‌ها', 'طراحی،توسعه و پشتیبانی با هلدینگ کلاسیکا' ),
			'saponine-studio' => array( 'انتخاب زبان', 'اسلایدر', 'اسلاید', 'اسلاید قبلی', 'اسلاید بعدی', 'انتخاب اسلاید', 'اسلاید %d', 'توقف پخش خودکار', 'پخش خودکار اسلایدها', 'پخش خودکار مطابق تنظیم کاهش حرکت دستگاه غیرفعال است', 'پخش خودکار در ویرایشگر متوقف است؛ از نشانگرها استفاده کنید', 'توقف چرخش نشان', 'ادامه چرخش نشان', 'توقف حرکت نوشته‌ها', 'ادامه حرکت نوشته‌ها', 'حرکت مطابق تنظیم کاهش حرکت دستگاه غیرفعال است', 'بخش‌های این صفحه', 'همه محصولات' ),
		);
	}

	/** Public catalogue for the optional automatic translation worker. */
	public static function catalog() { return self::strings(); }

	public static function register() {
		if ( ! function_exists( 'pll_register_string' ) || ! current_user_can( 'manage_options' ) ) { return; }
		foreach ( self::strings() as $domain => $strings ) {
			foreach ( $strings as $text ) {
				pll_register_string( $domain . '-' . md5( $text ), $text, 'Saponine — Interface', false );
			}
		}
	}

	public static function translate( $translated, $text, $domain ) {
		if ( 'saponine-flex' !== $domain && 'saponine-studio' !== $domain ) { return $translated; }
		$strings = self::strings();
		if ( ! isset( $strings[ $domain ] ) || ! in_array( $text, $strings[ $domain ], true ) || ! function_exists( 'pll_translate_string' ) || ! class_exists( Multilingual::class ) ) {
			return $translated;
		}
		$language = Multilingual::current_language();
		if ( ! $language ) { return $translated; }
		$result = pll_translate_string( $text, $language );
		return $result !== $text ? $result : $translated;
	}
}
