<?php
namespace Saponine\Studio;
defined( 'ABSPATH' ) || exit;
?>
<footer class="sn-company-footer">
	<div class="sn-company-footer-panel">
		<?php if ( $brand ) : ?>
			<a class="sn-footer-brand" href="<?php echo esc_url( Portal::url( 'brand', $slug ) ); ?>"><img src="<?php echo esc_url( $brand['image'] ); ?>" alt=""><span><?php echo esc_html( $brand['title'] ); ?><small dir="ltr"><?php echo esc_html( $brand['en_title'] ); ?></small></span></a>
			<p><?php echo esc_html( $brand['description'] ); ?></p>
			<a class="sn-footer-parent" href="<?php echo esc_url( Portal::url() ); ?>">داروسازی ساپونین</a>
		<?php else : ?>
			<a class="sn-company-footer-logo" href="<?php echo esc_url( Portal::url() ); ?>"><?php if ( $profile['logo_image'] ) : ?><img src="<?php echo esc_url( $profile['logo_image'] ); ?>" alt="ساپونین"><?php else : ?>ساپونین<?php endif; ?></a>
		<?php endif; ?>
		<?php Portal::utility_nav(); ?>
		<?php if ( ! empty( $contact['sample'] ) ) : ?><small>اطلاعات تماس نمونه</small><?php endif; ?>
		<div class="sn-footer-contact">
		<?php foreach ( array( 'phone'=>'تلفن','email'=>'ایمیل','address'=>'نشانی' ) as $key => $label ) : if ( empty( $contact[$key] ) ) { continue; } ?>
			<div><small><?php echo esc_html( $label ); ?></small><span dir="auto"><?php echo esc_html( $contact[$key] ); ?></span></div>
		<?php endforeach; ?></div>
	</div>
	<div class="sn-inner-credit"><span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> ساپونین</span><a href="<?php echo esc_url( $credit['link']['url'] ?? '' ); ?>"><?php echo esc_html( $credit['title'] ?? '' ); ?></a></div>
</footer>
