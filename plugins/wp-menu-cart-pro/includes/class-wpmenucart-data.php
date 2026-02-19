<?php
/**
 * Functions for creating the data to replace the shortcodes in the menucart-item.html template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WPO_Menu_Cart_Pro_Data' ) ) :

class WPO_Menu_Cart_Pro_Data {

	function __construct( $nav_menu_items, $menu_slug )	{
		$this->nav_menu_items = $nav_menu_items;
		$this->menu_slug = $menu_slug;
		$this->item_data = WPO_Menu_Cart_Pro()->shop->menu_item();
		$this->ubermenu_active_version = $this->ubermenu_active_version();
	}

	public function main_li_class() {
		$classes = 'menu-item wpmenucart wpmenucartli wpmenucart-display-'.WPO_Menu_Cart_Pro()->main_settings['items_alignment'];
		
		$common_li_classes = $this->get_common_li_classes( $this->nav_menu_items, $this->menu_slug );
		if ( !empty($common_li_classes)) {
			$classes .= ' ' . $common_li_classes;
		}

		// Hide when empty
		if ( $this->item_data['cart_contents_count'] == 0 && !isset(WPO_Menu_Cart_Pro()->main_settings['always_display']) ) {
			$classes .= ' empty-wpmenucart';
		}

		// Mega Menu
		if ( $this->mega_menu_active() ) {
			$classes .= ' mega-menu-item';
		}

		// submenu specific classes
		if ( isset(WPO_Menu_Cart_Pro()->main_settings['flyout_display']) ) {
			$classes .= ' menu-item-has-children';
			if ( $this->ubermenu_active_version ) {
				if( $this->ubermenu_active_version >= 3 ){
					$classes .= ' ubermenu-item-has-children ubermenu-has-submenu-drop ubermenu-has-submenu-mega';
				} else {
					$classes .= ' mega-with-sub';
				}
			}

			// Mega Menu
			if ( $this->mega_menu_active() ) {
				$classes .= ' mega-menu-item-has-children';
			}
		}
		
		// geek settings
		if ( !empty(WPO_Menu_Cart_Pro()->geek_settings['main_li_class'])) {
			$classes .= ' '.WPO_Menu_Cart_Pro()->geek_settings['main_li_class'];
		}

		$classes .= (isset(WPO_Menu_Cart_Pro()->main_settings['custom_class']) && WPO_Menu_Cart_Pro()->main_settings['custom_class'] != '') ? ' '. WPO_Menu_Cart_Pro()->main_settings['custom_class'] : '';
	
		if ($this->item_data['cart_contents_count'] == 0) {
			$classes .= ' empty';
		}

		$classes = apply_filters( 'wpmenucart_menu_item_classes', $classes );

		return apply_filters( 'wpmenucart_main_li_class', $classes, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function main_li_style() {
		return apply_filters( 'wpmenucart_main_li_style', '', $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function main_a_class() {
		if ($this->item_data['cart_contents_count'] == 0) {
			$classes = 'wpmenucart-contents empty-wpmenucart-visible';
		} else {
			$classes = 'wpmenucart-contents';
		}

		if ( $this->ubermenu_active_version >= 3 ) {
			$classes .= ' ubermenu-target';
		}

		// Mega Menu
		if ( $this->mega_menu_active() ) {
			$classes .= ' mega-menu-link';
		}

		// geek settings
		if ( !empty(WPO_Menu_Cart_Pro()->geek_settings['main_a_class'])) {
			$classes .= ' '.WPO_Menu_Cart_Pro()->geek_settings['main_a_class'];
		}

		return apply_filters( 'wpmenucart_main_a_class', $classes, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function main_url() {
		if ($this->item_data['cart_contents_count'] == 0) {
			$menu_item_href = $this->shop_url();
		} else {
			$menu_item_href = $this->cart_url();
		}
		
		return apply_filters( 'wpmenucart_main_url', $menu_item_href, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}


	public function main_link_title() {
		$start_shopping = $this->start_shopping_text();
		$viewing_cart = $this->view_cart_text();

		if ($this->item_data['cart_contents_count'] == 0) {
			$menu_item_title = apply_filters ('wpmenucart_emptytitle', $start_shopping );
		} else {
			$menu_item_title = apply_filters ('wpmenucart_fulltitle', $viewing_cart );
		}

		return apply_filters( 'wpmenucart_link_title', $menu_item_title, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function main_item_content() {
		$item_count = $this->item_data['cart_contents_count'];
		$cart_contents = sprintf(_n('%d item', '%d items', $item_count, 'wp-menu-cart-pro'), $item_count);
		$item_text = str_replace($item_count.' ', '', $cart_contents);

		$lang = $this->get_current_language();
		if ( !empty(WPO_Menu_Cart_Pro()->texts_links_settings['item_text'][$lang]['single']) && !empty(WPO_Menu_Cart_Pro()->texts_links_settings['item_text'][$lang]['plural']) ) {
			$item_text_setting = WPO_Menu_Cart_Pro()->texts_links_settings['item_text'][$lang];
			$cart_contents = sprintf( _n( '%d '.$item_text_setting['single'], '%d '.$item_text_setting['plural'], $item_count ), $item_count );
			$item_text = _n( $item_text_setting['single'], $item_text_setting['plural'], $item_count );
		}
			
		if (isset(WPO_Menu_Cart_Pro()->main_settings['icon_display'])) {
			if (!empty(WPO_Menu_Cart_Pro()->main_settings['custom_icon'])) {
				$custom_image = wp_get_attachment_image_src(WPO_Menu_Cart_Pro()->main_settings['custom_icon']);
				$menu_item_icon = '<img src="'.$custom_image[0].'" class="wpmenucart-custom-icon">';
			} else {
				$icon = isset(WPO_Menu_Cart_Pro()->main_settings['cart_icon']) ? WPO_Menu_Cart_Pro()->main_settings['cart_icon'] : '0';
				$menu_item_icon = sprintf('<i class="wpmenucart-icon-shopping-cart-%s" role="img" aria-label="%s"></i>', $icon, __( 'Cart','woocommerce' ) );
			}
		} else {
			$menu_item_icon = '';
		}
		
		switch (WPO_Menu_Cart_Pro()->main_settings['items_display']) {
			case 1: //items only
				$menu_item_a_content = $menu_item_icon.'<span class="cartcontents">'.$cart_contents.'</span>';
				break;
			case 2: //price only
				$menu_item_a_content = $menu_item_icon.'<span class="amount">'.$this->item_data['cart_total'].'</span>';
				break;
			case 3: //items & price
				$menu_item_a_content = $menu_item_icon.'<span class="cartcontents">'.$cart_contents.'</span><span class="amount">'.$this->item_data['cart_total'].'</span>';
				break;
			case 'custom':
				$replacements = array(
					'{{icon}}'		=> $menu_item_icon,
					'{{# items}}'	=> $cart_contents,
					'{{items}}'		=> $item_text,
					'{{#}}'			=> $item_count,
					'{{price}}'		=> $this->item_data['cart_total'],
				);
				$custom_format = $this->get_translated_setting( WPO_Menu_Cart_Pro()->main_settings['custom_items_display'] );
				$menu_item_a_content = str_replace(array_keys($replacements), array_values($replacements), $custom_format );
				break;
		}
		// legacy filter
		$menu_item_a_content = apply_filters ('wpmenucart_menu_item_a_content', $menu_item_a_content, $menu_item_icon, $cart_contents, $this->item_data );

		return apply_filters( 'wpmenucart_main_item_content', $menu_item_a_content, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function submenu_ul_class() {
		$classes = apply_filters( 'wpmenucart_submenu_classes', 'sub-menu wpmenucart' );

		if ( $this->ubermenu_active_version ) {
			if( $this->ubermenu_active_version >= 3 ){
				$classes .= ' ubermenu-submenu ubermenu-submenu-type-auto ubermenu-submenu-type-mega ubermenu-submenu-drop';
			} else {
				$classes .= ' sub-menu-1';
			}
		}

		if ($this->item_data['cart_contents_count'] == 0) {
			$classes .= ' empty';
		}

		// Mega Menu
		if ( $this->mega_menu_active() ) {
			$classes .= ' mega-sub-menu';
		}

		// geek settings
		if ( !empty(WPO_Menu_Cart_Pro()->geek_settings['submenu_ul_class'])) {
			$classes .= ' '.WPO_Menu_Cart_Pro()->geek_settings['submenu_ul_class'];
		}

		return apply_filters( 'wpmenucart_submenu_ul_class', $classes, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function submenu_ul_style(){
		$submenu_style = apply_filters( 'wpmenucart_submenu_style', '' );

		if ( $this->ubermenu_active_version && $this->ubermenu_active_version < 3 ) {
			$submenu_style .= ' display:none;';
		}

		return apply_filters( 'wpmenucart_submenu_ul_style', $submenu_style, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function submenu_li_class( $submenu_item_data = array() ) {
		$classes = 'menu-item wpmenucart-submenu-item clearfix';
		// legacy filter
		$classes = apply_filters( 'wpmenucart_submenu_item_li_classes', $classes, $this->item_data );

		// Mega Menu
		if ( $this->mega_menu_active() ) {
			$classes .= ' mega-menu-item';
		}
		
		// geek settings
		if ( !empty(WPO_Menu_Cart_Pro()->geek_settings['submenu_li_class'])) {
			$classes .= ' '.WPO_Menu_Cart_Pro()->geek_settings['submenu_li_class'];
		}

		return apply_filters( 'wpmenucart_submenu_li_class', $classes, $this->item_data, $submenu_item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function submenu_item_url( $submenu_item_data ) {
		// + legacy filter
		$url = apply_filters( 'wpmenucart_submenu_item_link', $submenu_item_data['item_permalink'], $submenu_item_data );

		return apply_filters( 'wpmenucart_submenu_item_url', $url, $this->item_data, $submenu_item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function submenu_item_a_class( $submenu_item_data = array() ) {
		$classes = 'clearfix';

		if ( $this->ubermenu_active_version >= 3 ) {
			$classes .= ' ubermenu-target';
		}

		// Mega Menu
		if ( $this->mega_menu_active() ) {
			$classes .= ' mega-menu-link';
		}
		
		// geek settings
		if ( !empty(WPO_Menu_Cart_Pro()->geek_settings['submenu_a_class'])) {
			$classes .= ' '.WPO_Menu_Cart_Pro()->geek_settings['submenu_a_class'];
		}
		
		return apply_filters( 'wpmenucart_submenu_item_a_class', $classes, $this->item_data, $submenu_item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function submenu_item_content( $submenu_item_data ) {
		// The thumbnail
		$submenu_item_content = '<span class="wpmenucart-thumbnail">'.$submenu_item_data['item_thumbnail'].'</span>';

		// Item info wrapper
		$submenu_item_content .= '<span class="wpmenucart-order-item-info">';
			// Product Name
			
			// remove any HTML formatting from the item name
			$submenu_item_data['item_name'] = strip_tags($submenu_item_data['item_name']);

			// strip out / truncate $item_name
			// > option empty ('') => default to 20
			// > option 0 => don't trucate
			$truncation_length = isset(WPO_Menu_Cart_Pro()->main_settings['flyout_item_truncation']) && WPO_Menu_Cart_Pro()->main_settings['flyout_item_truncation'] != '' ? WPO_Menu_Cart_Pro()->main_settings['flyout_item_truncation'] : 20;
			$truncation_length = apply_filters( 'wpmenucart_submenu_name_truncate', $truncation_length );
			if ( $truncation_length !== 0 && $truncation_length !== '0' && strlen($submenu_item_data['item_name']) > $truncation_length ) {
				$submenu_item_data['item_name'] = $this->truncate_name( $submenu_item_data['item_name'], $truncation_length );
			}

			$submenu_item_content .= '<span class="wpmenucart-product-name">'.$submenu_item_data['item_name'].'</span>';

			// Quantity x price
			$submenu_item_content .= '<span class="wpmenucart-product-quantity-price">';
			$submenu_item_content .=  $submenu_item_data['item_quantity'] .' x '. $submenu_item_data['item_price'];
			$submenu_item_content .= '</span>';
		$submenu_item_content .= '</span>';

		return apply_filters( 'wpmenucart_submenu_item_content', $submenu_item_content, $this->item_data, $submenu_item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function empty_cart_text() {
		$text = __('your cart is currently empty', 'wp-menu-cart-pro');

		if ( isset( WPO_Menu_Cart_Pro()->texts_links_settings['empty_cart_text'] ) ) {
			$text = $this->get_translated_setting( WPO_Menu_Cart_Pro()->texts_links_settings['empty_cart_text'], $text );
		}

		return apply_filters( 'wpmenucart_empty_cart_text', $text, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function start_shopping_text() {
		$text = __('Start shopping', 'wp-menu-cart-pro');

		if ( isset( WPO_Menu_Cart_Pro()->texts_links_settings['start_shopping_text'] ) ) {
			$text = $this->get_translated_setting( WPO_Menu_Cart_Pro()->texts_links_settings['start_shopping_text'], $text );
		}

		return apply_filters( 'wpmenucart_start_shopping_text', $text, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function view_cart_text() {
		$text = __('View your shopping cart', 'wp-menu-cart-pro');

		// legacy filters
		$text = apply_filters('wpmenucart_viewcarttext', $text);

		if ( isset( WPO_Menu_Cart_Pro()->texts_links_settings['view_cart_text'] ) ) {
			$text = $this->get_translated_setting( WPO_Menu_Cart_Pro()->texts_links_settings['view_cart_text'], $text );
		}

		return apply_filters( 'wpmenucart_view_cart_text', $text, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function cart_url() {
		$url = apply_filters ('wpmenucart_fullurl', $this->item_data['cart_url'] );

		if ( isset( WPO_Menu_Cart_Pro()->texts_links_settings['cart_url'] ) ) {
			$url = $this->get_translated_setting( WPO_Menu_Cart_Pro()->texts_links_settings['cart_url'], $url );
		}

		return apply_filters( 'wpmenucart_cart_url', $url, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}

	public function shop_url() {
		$url = apply_filters ('wpmenucart_emptyurl', $this->item_data['shop_page_url'] );

		if ( isset( WPO_Menu_Cart_Pro()->texts_links_settings['shop_url'] ) ) {
			$url = $this->get_translated_setting( WPO_Menu_Cart_Pro()->texts_links_settings['shop_url'], $url );
		}

		return apply_filters( 'wpmenucart_shop_url', $url, $this->item_data, WPO_Menu_Cart_Pro()->main_settings);
	}	

	/**
	 * Get a flat list of common classes from all menu items in a menu
	 * @param  string $items nav_menu HTML containing all <li> menu items
	 * @return string        flat (imploded) list of common classes
	 */
	public function get_common_li_classes( $items, $menu_slug ) {
		if ( isset( $menu_slug ) && isset( WPO_Menu_Cart_Pro()->main_settings['common_li_casses'][$menu_slug] ) ) {
			return WPO_Menu_Cart_Pro()->main_settings['common_li_casses'][$menu_slug];
		}

		if ( empty($items) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		
		$libxml_previous_state = libxml_use_internal_errors(true); // enable user error handling

		$dom_items = new DOMDocument;
		$dom_items->loadHTML( $items );
		$lis = $dom_items->getElementsByTagName('li');
		
		if (empty($lis)) {
			libxml_clear_errors();
			libxml_use_internal_errors($libxml_previous_state);
			return '';
		}
		
		foreach($lis as $li) {
			if ($li->parentNode->tagName != 'ul')
				$li_classes[] = explode( ' ', $li->getAttribute('class') );
		}
		
		// Uncomment to dump DOM errors / warnings
		//$errors = libxml_get_errors();
		//print_r ($errors);
		
		// clear errors and reset to previous error handling state
		libxml_clear_errors();
		libxml_use_internal_errors($libxml_previous_state);
		
		if ( !empty($li_classes) ) {
			$common_li_classes = array_shift($li_classes);
			foreach ($li_classes as $li_class) {
				$common_li_classes = array_intersect($li_class, $common_li_classes);
			}
			$common_li_classes_flat = implode(' ', $common_li_classes);
		} else {
			$common_li_classes_flat = '';
		}
		return $common_li_classes_flat;
	}

	private function truncate_name($item_name, $truncation_length) {
		// minimum truncation length = 4
		$truncation_length = max($truncation_length, 4);
		$encoding = function_exists('mb_detect_encoding') && (mb_detect_encoding($item_name) != 'ASCII') ? mb_detect_encoding($item_name) : 'utf-8';
		$item_name = html_entity_decode($item_name, ENT_QUOTES, $encoding); // avoiding to truncate html characters into garbled text
		$separator = '...';
		$separatorlength = strlen($separator);
		$lastcharacters = 0; // option to keep last characters, like "WooCommerce Me...art"
		$start = $truncation_length - $separatorlength - $lastcharacters;
		$trunc = strlen($item_name) - $truncation_length + $separatorlength;
		$item_name = substr_replace($item_name, $separator, $start, $trunc);
	
		$item_name = htmlentities($item_name, ENT_QUOTES, $encoding); // restore html characters
		return $item_name;
	}

	public function ubermenu_active_version() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		if ( in_array( 'ubermenu/ubermenu.php', $active_plugins ) ) {
			if(defined('UBERMENU_VERSION') && (version_compare(UBERMENU_VERSION, '3.0.0') >= 0)){
				return 3;
			} else {
				return 1;
			}
		} else {
			return false;
		}
	}

	public function mega_menu_active() {
		return class_exists('Mega_Menu');
	}

	public function get_translated_setting( $setting, $default = '' ) {
		if ( is_string( $setting ) ) {
			return $setting; // not translated (would be array)
		} elseif ( is_array( $setting ) ) {
			$lang = $this->get_current_language();
			if ( !empty( $setting[$lang] ) ) {
				return $setting[$lang];
			} elseif ( !empty( $setting['default'] ) ) {
				return $setting['default'];
			} else {
				return $default;
			}
		} else {
			return $default;
		}
	}

	public function get_current_language() {
		if ( class_exists('SitePress') ) {
			if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '<' ) ) {
				return ICL_LANGUAGE_CODE;
			} else {
				return apply_filters( 'wpml_current_language', 'default' );
			}
		}

		// no WPML: return 'default'
		return 'default';
	}

}

endif; // class_exists

//return new WPO_Menu_Cart_Pro_Data();