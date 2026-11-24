<?php
/**
 * Plugin Name: Saponine Studio
 * Description: ویرایش بصری ساپونین؛ ابزارهای متحرک المنتور، سربرگ و پابرگ آزاد و درون‌ریز محلی.
 * Version: 1.5.2
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Requires Plugins: elementor
 * Author: Saponine
 * Text Domain: saponine-studio
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SAPONINE_STUDIO_VERSION', '1.5.2' );
define( 'SAPONINE_STUDIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'SAPONINE_STUDIO_URL', plugin_dir_url( __FILE__ ) );

require_once SAPONINE_STUDIO_PATH . 'includes/class-plugin.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-admin.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-slider-document.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-slider-admin.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-slider-companions.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-importer.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-motion.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-multilingual.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-ui-strings.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-layout.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-brand-strip.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-portal.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-company-profile.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-catalog-pages.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-service-pages.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-section-scroll.php';
require_once SAPONINE_STUDIO_PATH . 'includes/class-auto-translate.php';

\Saponine\Studio\Multilingual::boot();

add_action( 'plugins_loaded', array( '\Saponine\Studio\Plugin', 'boot' ), 20 );
