<?php
namespace Saponine\Studio;
defined( 'ABSPATH' ) || exit;
$brands = Portal::brands();
?>
<article class="sn-profile">
	<header class="sn-profile-hero">
		<?php if ( $profile['hero_image'] ) : ?><img class="sn-profile-portrait" src="<?php echo esc_url( $profile['hero_image'] ); ?>" alt="" fetchpriority="high"><?php endif; ?>
		<div><?php if ( $profile['logo_image'] ) : ?><img class="sn-profile-logo" src="<?php echo esc_url( $profile['logo_image'] ); ?>" alt="ساپونین"><?php endif; ?><h1>دربارهٔ داروسازی ساپونین</h1></div>
	</header>
	<section class="sn-profile-intro sn-page-width">
		<h2>شرکت داروسازی ساپونین</h2><p><?php echo esc_html( $profile['intro'] ); ?></p>
	</section>
	<section class="sn-values sn-page-width" aria-label="رویکرد شرکت">
		<?php foreach ( array( array( 'vision','چشم‌انداز',$profile['hero_image'] ), array( 'mission','مأموریت',$profile['pharmacy_image'] ), array( 'values','ارزش‌های کلیدی',$profile['story_image'] ) ) as $v ) : ?>
			<div class="sn-value"><?php if ( $v[2] ) : ?><img src="<?php echo esc_url( $v[2] ); ?>" alt="" loading="lazy"><?php endif; ?><h3><?php echo esc_html( $v[1] ); ?></h3><p><?php echo esc_html( $profile[ $v[0] ] ); ?></p></div>
		<?php endforeach; ?>
	</section>
	<section class="sn-story sn-page-width"><div><h2>داستان ساپونین</h2><?php echo wpautop( esc_html( $profile['story'] ) ); ?></div><?php if ( $profile['story_image'] ) : ?><img src="<?php echo esc_url( $profile['story_image'] ); ?>" alt="محصولات خانوادهٔ ساپونین" loading="lazy"><?php endif; ?></section>
	<section class="sn-portfolio" id="sn-portfolio"><div class="sn-page-width"><div><h2>خانوادهٔ برندهای ساپونین</h2><p><?php echo esc_html( $profile['portfolio'] ); ?></p><a class="sn-light-link" href="<?php echo esc_url( Portal::url( 'products' ) ); ?>">مشاهدهٔ محصولات</a></div><div class="sn-portfolio-cards">
	<?php foreach ( $brands as $b ) : ?><a href="<?php echo esc_url( Portal::url( 'brand', $b['slug'] ) ); ?>"><img src="<?php echo esc_url( $b['banner_image'] ?: $b['image'] ); ?>" alt="" loading="lazy"><strong><?php echo esc_html( $b['title'] ); ?></strong></a><?php endforeach; ?>
	</div></div></section>
	<div class="sn-company-figures sn-page-width"><a href="<?php echo esc_url( Portal::url( 'products' ) ); ?>"><b><?php echo esc_html( number_format_i18n( count( Portal::products() ) ) ); ?></b> محصول در کاتالوگ</a><a href="#sn-portfolio"><b><?php echo esc_html( number_format_i18n( count( $brands ) ) ); ?></b> خانوادهٔ برند</a><a href="<?php echo esc_url( Portal::url( 'careers' ) ); ?>">فرصت همکاری با ساپونین</a></div>
</article>
