<?php
if ( ! class_exists( 'WPMenuCart_EDD_Pro' ) ) {
	class WPMenuCart_EDD_Pro extends WPMenuCart_EDD {
	
		/**
		 * Construct.
		 */
		public function __construct() {
		}
	
		/**
		 * Add Menu Cart to menu
		 * 
		 * @return menu items including cart
		 */
		
		public function submenu_items() {
			$cart_items = edd_get_cart_contents();
			$submenu_items = array();
			
			if ( !empty($cart_items) && count($cart_items) > 0 ) {
				foreach ( $cart_items as $key => $item ) {
					$item_thumbnail = get_the_post_thumbnail( $item['id'], apply_filters( 'edd_checkout_image_size', array( 25,25 ) ) );
					$item_name = function_exists('edd_get_cart_item_name') ? edd_get_cart_item_name( $item ) : get_the_title( $item['id'] );
					$item_name = apply_filters( 'wpmenucart_submenu_item_name', $item_name, $item );
					$item_quantity = edd_get_cart_item_quantity( $item['id'], $item['options'] );
					$item_price = edd_cart_item_price( $item['id'], $item['options'] );
				
					// Item permalink if product visible
					$item_permalink = esc_url( get_permalink( $item['id'] ) );
		
					$submenu_items[] = array(
						'item_thumbnail'	=> $item_thumbnail,
						'item_name'			=> $item_name,
						'item_quantity'		=> $item_quantity,
						'item_price'		=> $item_price,
						'item_permalink'	=> $item_permalink,
						'cart_item'			=> $item,
					);
				}
			} else {
				$submenu_items = array();
			}
	
			return apply_filters( 'wpmenucart_submenu_items_data', $submenu_items );
		}

		public function get_cart_url(){
			$cart_url = edd_get_checkout_uri();
			return $cart_url;
		}
	}
}