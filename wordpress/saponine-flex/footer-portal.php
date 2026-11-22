<?php
defined( 'ABSPATH' ) || exit;
$settings = \Saponine\Studio\Plugin::settings();
$map = get_option( \Saponine\Studio\Importer::MAP_OPTION, array() );
// A site owner can replace the supplied footer with a custom Elementor template.
if ( ! empty( $settings['footer_id'] ) && (int) $settings['footer_id'] !== (int) ( $map['footer'] ?? 0 ) && saponine_flex_location( 'footer' ) ) {
	// The selected template was rendered.
} else {
	\Saponine\Studio\Company_Profile::footer();
}
wp_footer(); ?>
</body></html>
