<?php
if ( ! class_exists( 'WPMenuCart_EDD' ) ) {
	class WPMenuCart_EDD {     
	
	    /**
	     * Construct.
	     */
	    public function __construct() {
	    }
	
		public function menu_item() {
			global $post;
	
			$menu_item = array(
				'cart_url'				=> $this->cart_url(),
				'shop_page_url'			=> $this->shop_url(),
				'cart_contents_count'	=> edd_get_cart_quantity(),
				'cart_total'			=> edd_currency_filter( edd_format_amount( edd_get_cart_total() ) ),
			);
		
			return apply_filters( 'wpmenucart_menu_item_data', $menu_item );
		}
		
		public function cart_url() {
			return edd_get_checkout_uri();
		}

		public function shop_url() {
			return get_home_url();
		}
	}
}