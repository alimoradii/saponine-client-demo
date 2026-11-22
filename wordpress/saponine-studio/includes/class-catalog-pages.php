<?php
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Catalog and brand pages read the same product rows as the home showcase. */
final class Catalog_Pages {
	public static function render( $section, $args ) {
		$products = Portal::products();
		$brands   = Portal::brands();
		$item     = (string) ( $args['item'] ?? '' );
		echo '<div class="spc">';
		if ( 'products' === $section ) {
			self::archive( $products, $brands );
		} elseif ( 'brand' === $section && isset( $brands[ $item ] ) ) {
			self::brand( $item, $brands[ $item ], $products );
		} else {
			$by_slug = array_column( $products, null, 'slug' );
			if ( 'product' === $section && isset( $by_slug[ $item ] ) ) {
				$product = $by_slug[ $item ];
				self::product( $product, $brands[ $product['brand'] ?? '' ] ?? array() );
			} else {
				echo '<section class="spc-missing spc-width"><h1>' . esc_html__( 'موردی پیدا نشد', 'saponine-studio' ) . '</h1>';
				self::button( Portal::url( 'products' ), __( 'مشاهده همه محصولات', 'saponine-studio' ) );
				echo '</section>';
			}
		}
		echo '</div>';
	}

	private static function image( $src, $alt, $eager = false ) {
		if ( empty( $src ) ) { return; }
		printf( '<img src="%s" alt="%s" decoding="async" %s>', esc_url( $src ), esc_attr( $alt ), $eager ? 'fetchpriority="high"' : 'loading="lazy"' );
	}

	private static function button( $url, $label, $attrs = array() ) {
		if ( ! $url || ! $label ) { return; }
		$external = ! empty( $attrs['is_external'] );
		$rel = $external ? array( 'noopener', 'noreferrer' ) : array();
		if ( ! empty( $attrs['nofollow'] ) ) { $rel[] = 'nofollow'; }
		printf( '<a class="spc-button" href="%s"%s%s>%s</a>', esc_url( $url ), $external ? ' target="_blank"' : '', $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '', esc_html( $label ) );
	}

	private static function lines( $value ) {
		$parts = is_array( $value ) ? $value : preg_split( '/\R/u', (string) $value );
		return array_values( array_unique( array_filter( array_map( 'trim', $parts ?: array() ), 'strlen' ) ) );
	}

	private static function features( $features, $class = '' ) {
		if ( ! $features ) { return; }
		$icons = array( 'seedling', 'leaf', 'spa', 'sun' );
		echo '<ul class="spc-features ' . esc_attr( $class ) . '">';
		foreach ( array_values( $features ) as $index => $feature ) {
			echo '<li><span class="spc-feature-mark" aria-hidden="true">';
			if ( class_exists( '\\Elementor\\Icons_Manager' ) ) {
				\Elementor\Icons_Manager::render_icon( array( 'value' => 'fas fa-' . $icons[ $index % count( $icons ) ], 'library' => 'fa-solid' ), array( 'aria-hidden' => 'true' ) );
			} else {
				self::image( SAPONINE_STUDIO_URL . 'assets/images/favicon.png', '' );
			}
			echo '</span><span>' . esc_html( $feature ) . '</span></li>';
		}
		echo '</ul>';
	}

	private static function cards( $products ) {
		echo '<div class="spc-grid">';
		foreach ( $products as $product ) {
			?>
			<a class="spc-card" href="<?php echo esc_url( Portal::url( 'product', $product['slug'] ) ); ?>" data-brand="<?php echo esc_attr( $product['brand'] ?? '' ); ?>" data-category="<?php echo esc_attr( $product['category'] ?? '' ); ?>">
				<span class="spc-card-media"><?php self::image( $product['image']['url'] ?? '', '' ); ?></span>
				<span class="spc-card-name"><?php echo esc_html( $product['title'] ); ?></span>
				<?php if ( ! empty( $product['en_title'] ) ) : ?><span class="spc-latin" dir="ltr"><?php echo esc_html( $product['en_title'] ); ?></span><?php endif; ?>
			</a>
			<?php
		}
		echo '</div>';
	}

	private static function archive( $products, $brands ) {
		$categories = array();
		foreach ( $products as $row ) {
			if ( ! empty( $row['category'] ) ) { $categories[ $row['category'] ] = $row['category_label'] ?? $row['category']; }
		}
		?>
		<section class="spc-archive" data-spc-archive>
			<div class="spc-archive-head"><h1><?php esc_html_e( 'آرشیو محصولات', 'saponine-studio' ); ?></h1><p><?php esc_html_e( 'محصولات ساپونین', 'saponine-studio' ); ?></p></div>
			<div class="spc-tools" data-spc-filters hidden>
				<label><span class="spc-sr"><?php esc_html_e( 'فیلتر برند', 'saponine-studio' ); ?></span><select data-spc-filter="brand">
					<option value=""><?php esc_html_e( 'همه برندها', 'saponine-studio' ); ?></option>
					<?php foreach ( $brands as $slug => $brand ) : ?><option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $brand['title'] ); ?></option><?php endforeach; ?>
				</select></label>
				<label><span class="spc-sr"><?php esc_html_e( 'فیلتر دسته‌بندی', 'saponine-studio' ); ?></span><select data-spc-filter="category">
					<option value=""><?php esc_html_e( 'همه دسته‌بندی‌ها', 'saponine-studio' ); ?></option>
					<?php foreach ( $categories as $slug => $title ) : ?><option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $title ); ?></option><?php endforeach; ?>
				</select></label>
			</div>
			<div class="spc-width spc-results">
				<?php self::cards( $products ); ?>
				<div class="spc-empty" data-spc-empty <?php echo $products ? 'hidden' : ''; ?>><p role="status"><?php esc_html_e( 'محصولی برای این انتخاب پیدا نشد.', 'saponine-studio' ); ?></p>
				<?php if ( $products ) : ?><button class="spc-button" type="button" data-spc-reset><?php esc_html_e( 'نمایش همه محصولات', 'saponine-studio' ); ?></button><?php endif; ?></div>
				<span class="spc-sr" aria-live="polite" data-spc-count data-label="<?php esc_attr_e( 'تعداد محصولات', 'saponine-studio' ); ?>"></span>
			</div>
		</section>
		<?php
	}

	private static function brand( $slug, $brand, $products ) {
		$rows = array_values( array_filter( $products, static function ( $row ) use ( $slug ) { return $slug === ( $row['brand'] ?? '' ); } ) );
		$lead = $rows[0] ?? array();
		$photo = $brand['image'] ?: ( $lead['image']['url'] ?? '' );
		$banner = $brand['banner_image'] ?? '';
		$description = ! empty( $brand['description'] ) ? $brand['description'] : ( $lead['description'] ?? '' );
		$features = self::lines( $brand['features'] ?? '' );
		if ( ! $features ) {
			foreach ( $rows as $row ) { $features = array_merge( $features, self::lines( $row['features'] ?? '' ) ); }
			$features = array_values( array_unique( $features ) );
		}
		$name = $brand['title'];
		?>
		<section class="spc-brand-hero<?php echo $banner ? ' spc-has-banner' : ''; ?>">
			<div class="spc-brand-hero-media"><?php self::image( $banner ?: $photo, '', true ); ?></div>
			<div class="spc-brand-wordmark">
				<?php if ( ! empty( $brand['en_title'] ) ) : ?><span dir="ltr" class="spc-display"><?php echo esc_html( $brand['en_title'] ); ?></span><?php endif; ?>
				<h1><?php echo esc_html( $name ); ?></h1>
			</div>
		</section>
		<section class="spc-split spc-narrow" id="brand-overview">
			<div class="spc-copy"><h2><?php echo esc_html( $name ); ?></h2>
				<?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
				<?php self::button( '#brand-products', __( 'مشاهده محصولات', 'saponine-studio' ) ); ?>
			</div>
			<div class="spc-editorial-photo"><?php self::image( $lead['image']['url'] ?? $photo, $name ); ?></div>
		</section>
		<section class="spc-brand-features spc-width" id="brand-features">
			<?php if ( $features ) : ?>
				<h2><?php echo esc_html( sprintf( __( 'ویژگی‌های %s', 'saponine-studio' ), $name ) ); ?></h2>
				<?php self::features( $features, 'spc-features-large' ); ?>
			<?php endif; ?>
		</section>
		<section class="spc-brand-banner spc-width<?php echo $banner ? ' spc-has-banner' : ''; ?>" id="brand-story">
			<div class="spc-brand-banner-photo"><?php self::image( $banner ?: $photo, '' ); ?></div>
			<div><span dir="ltr" class="spc-display"><?php echo esc_html( $brand['en_title'] ?? '' ); ?></span><h2><?php echo esc_html( $name ); ?></h2></div>
		</section>
		<section class="spc-brand-products" id="brand-products"><div class="spc-width">
			<h2><?php echo esc_html( sprintf( __( 'محصولات برند %s', 'saponine-studio' ), $name ) ); ?></h2>
			<?php if ( $rows ) { self::cards( $rows ); } else { self::button( Portal::url( 'products' ), __( 'مشاهده همه محصولات ساپونین', 'saponine-studio' ) ); } ?>
		</div></section>
		<?php
	}

	private static function product( $row, $brand ) {
		$name     = $row['title'];
		$src      = $row['image']['url'] ?? '';
		$features = self::lines( $row['features'] ?? '' );
		$summary  = $row['description'] ?? '';
		$detail   = ! empty( $row['detail_description'] ) ? $row['detail_description'] : $summary;
		$brand_url = $brand ? Portal::url( 'brand', $row['brand'] ) : '';
		?>
		<section class="spc-product-hero spc-width">
			<div class="spc-product-photo"><?php self::image( $src, $row['image_alt'] ?? $name, true ); ?></div>
			<div class="spc-product-intro">
				<div class="spc-product-head"><div><h1><?php echo esc_html( $name ); ?></h1><span dir="ltr" class="spc-latin"><?php echo esc_html( $row['en_title'] ?? '' ); ?></span></div>
					<?php if ( $brand ) : ?><a class="spc-brand-chip" href="<?php echo esc_url( $brand_url ); ?>"><?php self::image( $brand['image'] ?? '', '' ); ?><span><?php echo esc_html( $brand['title'] ); ?></span></a><?php endif; ?>
				</div>
				<?php if ( $summary ) : ?><p><?php echo esc_html( $summary ); ?></p><?php endif; ?>
				<?php self::features( $features, 'spc-features-inline' ); ?>
				<div class="spc-actions">
					<?php
					$cta = $row['cta_link'] ?? array();
					if ( ! empty( $cta['url'] ) ) {
						self::button( $cta['url'], ! empty( $row['cta_label'] ) ? $row['cta_label'] : __( 'مشاهده محصول', 'saponine-studio' ), $cta );
					}
					self::button( Portal::url( 'pharmacies' ), __( 'مشاهده داروخانه‌ها', 'saponine-studio' ) );
					?>
				</div>
			</div>
		</section>
		<section class="spc-split spc-narrow">
			<div class="spc-copy"><h2><?php echo esc_html( sprintf( __( '%s چیست؟', 'saponine-studio' ), $name ) ); ?></h2>
				<?php if ( $detail ) : ?><p><?php echo esc_html( $detail ); ?></p><?php endif; ?>
			</div><div class="spc-editorial-photo"><?php self::image( $src, $row['image_alt'] ?? $name ); ?></div>
		</section>
		<?php if ( $features ) : ?><section class="spc-feature-band"><div class="spc-narrow">
			<h2><?php echo esc_html( sprintf( __( 'ویژگی‌های %s', 'saponine-studio' ), $name ) ); ?></h2>
			<?php self::features( $features, 'spc-features-large' ); ?>
		</div></section><?php endif; ?>
		<?php self::specs( $row, $brand ); ?>
		<?php if ( $brand ) : ?><section class="spc-brand-end spc-width">
			<div class="spc-brand-end-image"><?php self::image( $brand['image'] ?? '', '' ); ?></div>
			<h2><?php echo esc_html( $brand['title'] ); ?></h2><p class="spc-latin" dir="ltr"><?php echo esc_html( $brand['en_title'] ?? '' ); ?></p>
			<?php self::button( $brand_url, __( 'مشاهده صفحه برند', 'saponine-studio' ) ); ?>
		</section><?php endif;
	}

	private static function specs( $row, $brand ) {
		$tabs = array(
			'specs' => __( 'مشخصات', 'saponine-studio' ),
			'description' => __( 'توضیحات تکمیلی', 'saponine-studio' ),
			'directions' => __( 'روش مصرف', 'saponine-studio' ),
			'contraindications' => __( 'منع مصرف', 'saponine-studio' ),
		);
		$texts = array(
			'description' => ! empty( $row['detail_description'] ) ? $row['detail_description'] : ( $row['description'] ?? '' ),
			'directions' => ! empty( $row['directions'] ) ? $row['directions'] : __( 'برای آگاهی از روش مصرف این محصول، راهنمای روی بسته‌بندی یا بروشور آن را بخوانید یا از داروساز بپرسید.', 'saponine-studio' ),
			'contraindications' => ! empty( $row['contraindications'] ) ? $row['contraindications'] : __( 'برای اطلاع از موارد منع مصرف این محصول، به بروشور بسته‌بندی مراجعه کنید یا با داروساز مشورت کنید.', 'saponine-studio' ),
		);
		if ( ! empty( $row['modal_note'] ) ) { $texts['description'] = trim( $texts['description'] . "\n\n" . $row['modal_note'] ); }
		if ( '' === $texts['description'] ) { $texts['description'] = __( 'توضیحات تکمیلی در بروشور محصول درج می‌شود.', 'saponine-studio' ); }
		$ident = 'spc-' . sanitize_html_class( $row['slug'] );
		$facts = array(
			__( 'نام محصول', 'saponine-studio' ) => $row['title'],
			__( 'نام لاتین', 'saponine-studio' ) => $row['en_title'] ?? '',
			__( 'برند', 'saponine-studio' ) => $brand['title'] ?? '',
			__( 'دسته‌بندی', 'saponine-studio' ) => $row['category_label'] ?? '',
			__( 'مدت مصرف', 'saponine-studio' ) => $row['usage'] ?? '',
		);
		?>
		<section class="spc-specs spc-narrow" data-spc-tabs>
			<nav class="spc-tablist" aria-label="<?php esc_attr_e( 'اطلاعات محصول', 'saponine-studio' ); ?>">
			<?php foreach ( $tabs as $key => $label ) : ?><a id="<?php echo esc_attr( $ident . '-tab-' . $key ); ?>" href="#<?php echo esc_attr( $ident . '-' . $key ); ?>"><?php echo esc_html( $label ); ?></a><?php endforeach; ?>
			</nav>
			<?php foreach ( $tabs as $key => $label ) : ?>
			<div class="spc-panel" id="<?php echo esc_attr( $ident . '-' . $key ); ?>"><h3><?php echo esc_html( $label ); ?></h3>
				<?php if ( 'specs' === $key ) : ?><dl class="spc-facts"><?php foreach ( array_filter( $facts, 'strlen' ) as $term => $value ) : ?><div><dt><?php echo esc_html( $term ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div><?php endforeach; ?></dl>
				<?php else : ?><p><?php echo esc_html( $texts[ $key ] ); ?></p><?php endif; ?>
			</div><?php endforeach; ?>
		</section>
		<?php
	}
}
