<?php
/**
 * Compatibility for third party plugins
 */

namespace WPO\WC\Menu_Cart_Pro\Compatibility;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\WPO\\WC\\Menu_Cart_Pro\\Compatibility\\Third_Party_Plugins' ) ) :


class Third_Party_Plugins {

	public function __construct()
	{
		add_filter( 'wpo_wpmenucart_cart_item_visible', array( $this, 'woocommerce_composite_products' ), 10, 3 );
		add_filter( 'wpo_wpmenucart_cart_item_quantity', array( $this, 'woocommerce_composite_products_mini_cart_item_quantity' ), 10, 3 );
		add_filter( 'wpo_wpmenucart_cart_item_name', array( $this, 'woocommerce_composite_products_mini_cart_item_name' ), 10, 3 );
		add_filter( 'wpo_wpmenucart_cart_item_visible', array( $this, 'woocommerce_product_bundles' ), 10, 3 );
	}

	public function woocommerce_composite_products( $visible, $cart_item, $cart_item_key )
	{
		// Composite Products visibility
		if ( function_exists('WC_CP') && is_callable( array( WC_CP()->display, 'cart_widget_item_visible' ) ) ) {
			$visible = WC_CP()->display->cart_widget_item_visible( $visible, $cart_item, $cart_item_key );
		}

		return $visible;
	}

	public function woocommerce_composite_products_mini_cart_item_quantity( $quantity, $cart_item, $cart_item_key )
	{
		if ( function_exists('WC_CP') && is_callable( array( WC_CP()->cart, 'container_cart_item_contains' ) ) && is_callable( array( WC_CP()->cart, 'get_product_subtotal' ) ) ) {
			if ( wc_cp_is_composite_container_cart_item( $cart_item ) ) {

				if ( WC_CP()->cart->container_cart_item_contains( $cart_item, 'sold_individually' ) ) {
					$quantity = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $cart_item[ 'data' ], $cart_item[ 'quantity' ] ), $cart_item, $cart_item_key );
				}
	
			} elseif ( wc_cp_is_composited_cart_item( $cart_item ) ) {
				$quantity = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $cart_item[ 'data' ], $cart_item[ 'quantity' ] ), $cart_item, $cart_item_key );
			}
		}

		return $quantity;
	}

	public function woocommerce_composite_products_mini_cart_item_name( $name, $cart_item, $cart_item_key )
	{
		if ( function_exists('WC_CP') && is_callable( array( WC_CP()->cart, 'container_cart_item_contains' ) ) && class_exists('WC_CP_Product') ) {
			if ( wc_cp_is_composite_container_cart_item( $cart_item ) ) {
				if ( $cart_item[ 'quantity' ] > 1 && WC_CP()->cart->container_cart_item_contains( $cart_item, 'sold_individually' ) ) {
					$name = WC_CP_Product::get_title_string( $name, $cart_item[ 'quantity' ] );
				}
	
			} elseif ( wc_cp_is_composited_cart_item( $cart_item ) ) {
				$name = WC_CP_Product::get_title_string( $name, $cart_item[ 'quantity' ] );
			}
		}

		return $name;
	}

	public function woocommerce_product_bundles( $visible, $cart_item, $cart_item_key )
	{
		// Product bundles visibility
		// taken from woocommerce-product-bundles/includes/class-wc-pb-display.php
		if ( function_exists('wc_pb_get_bundled_cart_item_container')) {
			if ( $bundle_container_item = wc_pb_get_bundled_cart_item_container( $cart_item ) ) {

				$bundle          = $bundle_container_item[ 'data' ];
				$bundled_item_id = $cart_item[ 'bundled_item_id' ];

				if ( $bundled_item = $bundle->get_bundled_item( $bundled_item_id ) ) {
					if ( $bundled_item->is_visible( 'cart' ) === false ) {
						return false;
					}
				}
			}
		} elseif ( ! empty( $cart_item[ 'bundled_by' ] ) && ! empty( $cart_item[ 'stamp' ] ) ) {
			// Old version of Product Bundles
			if ( ! empty( $cart_item[ 'bundled_item_id' ] ) ) {

				$bundled_item_id = $cart_item[ 'bundled_item_id' ];
				$hidden          = isset( $cart_item[ 'stamp' ][ $bundled_item_id ][ 'secret' ] ) ? $cart_item[ 'stamp' ][ $bundled_item_id ][ 'secret' ] : 'no';

				if ( $hidden === 'yes' ) {
					return false;
				}

			}
		}

		return $visible;
	}

}


endif; // Class exists check


return new Third_Party_Plugins();