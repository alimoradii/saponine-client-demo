<?php
namespace Saponine\Studio;
defined( 'ABSPATH' ) || exit;
?>
<dialog class="sn-finder" id="sn-finder" aria-labelledby="sn-finder-title">
	<button type="button" class="sn-finder-close" aria-label="بستن جستجو">×</button>
	<h2 id="sn-finder-title" class="screen-reader-text">جستجو در ساپونین</h2>
	<form class="sn-finder-form" role="search" method="get" action="<?php echo esc_url( self::url() ); ?>">
		<label for="sn-finder-input" class="screen-reader-text">نام محصول، برند یا صفحه</label>
		<input id="sn-finder-input" name="s" type="search" autocomplete="off" placeholder="نام محصول، برند یا صفحه را بنویسید…" />
	</form>
	<div class="sn-finder-suggestions"><h3>جستجوهای پیشنهادی</h3><div>
		<?php foreach ( array_slice( self::products(), 0, 3 ) as $product ) : ?>
			<button type="button"><?php echo esc_html( $product['title'] ); ?></button>
		<?php endforeach; ?>
	</div></div>
	<div class="sn-finder-results" hidden>
		<p class="sn-finder-status" aria-live="polite"></p>
		<?php foreach ( $results as $row ) : ?>
			<a href="<?php echo esc_url( $row[2] ); ?>" data-keywords="<?php echo esc_attr( $row[0] . ' ' . $row[1] . ' ' . $row[3] ); ?>"><span><?php echo esc_html( $row[0] ); ?></span><small><?php echo esc_html( $row[3] ); ?></small></a>
		<?php endforeach; ?>
	</div>
	<a class="sn-finder-banner" href="<?php echo esc_url( self::url( 'products' ) ); ?>"><img src="<?php echo esc_url( SAPONINE_STUDIO_URL . 'assets/images/hero.jpg' ); ?>" alt=""><span>مجموعه محصولات ساپونین</span></a>
</dialog>
