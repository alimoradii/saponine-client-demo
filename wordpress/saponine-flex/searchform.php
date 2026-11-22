<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<form role="search" method="get" class="sn-search-form" action="<?php echo esc_url( saponine_flex_home_url() ); ?>">
	<?php if ( $sn_language = saponine_flex_language() ) : ?><input type="hidden" name="lang" value="<?php echo esc_attr( $sn_language ); ?>"><?php endif; ?>
	<label class="screen-reader-text" for="<?php echo esc_attr( $sn_search_id = wp_unique_id( 'sn-search-' ) ); ?>"><?php esc_html_e( 'جستجو', 'saponine-flex' ); ?></label>
	<input id="<?php echo esc_attr( $sn_search_id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'جستجو در سایت…', 'saponine-flex' ); ?>">
	<button type="submit"><?php esc_html_e( 'جستجو', 'saponine-flex' ); ?></button>
</form>
