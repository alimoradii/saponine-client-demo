<?php
namespace Saponine\Studio;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Supply the new stock navigation when displaying an earlier demo import. */
final class Brand_Strip {
	private const TARGETS = array(
		'7349a2c' => 'sp-item-7bc932b-0a45269',
		'46fb40e' => 'sp-item-7bc932b-67e0726',
		'50d2f99' => 'sp-item-7bc932b-f59497b',
		'be91c87' => 'sp-brand-radiance',
	);

	public static function upgrade_stock_layout( $nodes, $post_id ) {
		$version = get_post_meta( $post_id, '_saponine_demo_version', true );
		if ( $version && version_compare( $version, '1.3.0', '>=' ) ) { return $nodes; }
		return self::upgrade_nodes( $nodes );
	}

	private static function upgrade_nodes( $nodes ) {
		if ( ! is_array( $nodes ) ) { return $nodes; }
		foreach ( $nodes as &$node ) {
			$id = $node['id'] ?? '';
			if ( 'af84950' === $id && empty( $node['settings']['_element_id'] ) ) {
				$node['settings']['_element_id'] = 'sp-brand-radiance';
			}
			if ( empty( $node['elements'] ) ) { continue; }
			if ( '4719af1' === $id && 'sn-brand-strip' === ( $node['settings']['css_classes'] ?? '' ) ) {
				$node['elements'] = self::upgrade_items( $node['elements'] );
			} else {
				$node['elements'] = self::upgrade_nodes( $node['elements'] );
			}
		}
		unset( $node );
		return $nodes;
	}

	private static function upgrade_items( $items ) {
		$ids = array_column( $items, 'id' );
		if ( 4 !== count( $ids ) || array_diff( array_keys( self::TARGETS ), $ids ) ) { return $items; }
		$map = get_option( Importer::MAP_OPTION, array() );
		$home = ! empty( $map['home'] ) ? get_permalink( $map['home'] ) : home_url( '/' );
		$stock_links = array( '#sec-2', $home . '#sec-2' );
		if ( ! empty( $map['home'] ) ) {
			$stock_links[] = add_query_arg( 'page_id', absint( $map['home'] ), home_url( '/' ) ) . '#sec-2';
		}
		foreach ( $items as &$item ) {
			foreach ( $item['elements'] as &$part ) {
				$url = $part['settings']['link']['url'] ?? '';
				// Preserve links the owner has pointed at another page or product.
				if ( 'heading' === ( $part['widgetType'] ?? '' ) && in_array( $url, $stock_links, true ) ) {
					$part['settings']['link']['url'] = $home . '#' . self::TARGETS[ $item['id'] ];
				}
			}
			unset( $part );
		}
		unset( $item );
		// Reuse the editable fifth item from the shipping Elementor document.
		$template = json_decode( file_get_contents( SAPONINE_STUDIO_PATH . 'demo/header.json' ), true );
		foreach ( $template['content'] ?? array() as $node ) {
			if ( '4719af1' !== $node['id'] || empty( $node['elements'][4] ) ) { continue; }
			$more = $node['elements'][4];
			$more['elements'][1]['settings']['title'] = __( 'همه محصولات', 'saponine-studio' );
			$more['elements'][1]['settings']['link']['url'] = $home . '#sec-4';
			$items[] = $more;
			break;
		}
		return $items;
	}
}
