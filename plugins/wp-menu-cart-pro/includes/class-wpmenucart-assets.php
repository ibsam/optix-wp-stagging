<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WPO_Menu_Cart_Pro_Assets' ) ) :

class WPO_Menu_Cart_Pro_Assets {
	
	function __construct()	{
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'backend_scripts_styles' ) );
	}

	/**
	 * Load styles & scripts
	 */
	public function frontend_scripts_styles ( $hook ) {
		if (isset(WPO_Menu_Cart_Pro()->main_settings['icon_display'])) {
			wp_enqueue_style(
				'wpmenucart-icons',
				WPO_Menu_Cart_Pro()->plugin_url() . '/assets/css/wpmenucart-icons-pro.css',
				array(),
				WPO_MENU_CART_PRO_VERSION
			);
			wp_enqueue_style(
				'wpmenucart-font',
				WPO_Menu_Cart_Pro()->plugin_url() . '/assets/css/wpmenucart-font.css',
				array(),
				WPO_MENU_CART_PRO_VERSION
			);
		}

		wp_enqueue_style(
			'wpmenucart',
			WPO_Menu_Cart_Pro()->plugin_url() . '/assets/css/wpmenucart-main.css',
			array(),
			WPO_MENU_CART_PRO_VERSION
		);

		// add custom styles when entered in geek settings
		if ( !empty(WPO_Menu_Cart_Pro()->geek_settings['custom_styles']) ) {
			wp_add_inline_style( 'wpmenucart', WPO_Menu_Cart_Pro()->geek_settings['custom_styles'] );
		}

		// Hide built-in theme carts
		if ( isset(WPO_Menu_Cart_Pro()->main_settings['hide_theme_cart']) ) {
			wp_add_inline_style( 'wpmenucart', '.et-cart-info { display:none !important; } .site-header-cart { display:none !important; }' );
		}

		//Load Stylesheet if twentytwelve is active
		if ( wp_get_theme() == 'Twenty Twelve' ) {
			wp_enqueue_style(
				'wpmenucart-twentytwelve',
				WPO_Menu_Cart_Pro()->plugin_url() . '/assets/css/wpmenucart-twentytwelve.css',
				array(),
				WPO_MENU_CART_PRO_VERSION
			);
		}

		//Load Stylesheet if twentyfourteen is active
		if ( wp_get_theme() == 'Twenty Fourteen' ) {
			wp_enqueue_style(
				'wpmenucart-twentyfourteen',
				WPO_Menu_Cart_Pro()->plugin_url() . '/assets/css/wpmenucart-twentyfourteen.css',
				array(),
				WPO_MENU_CART_PRO_VERSION
			);
		}

		// load builtin ajax if enabled or required
		if ( isset( WPO_Menu_Cart_Pro()->main_settings['builtin_ajax'] ) || in_array( WPO_Menu_Cart_Pro()->main_settings['shop_plugin'], array( 'WP e-Commerce', 'eShop' ) ) ) {
			wp_enqueue_script(
				'wpmenucart',
				WPO_Menu_Cart_Pro()->plugin_url() . '/assets/js/wpmenucart.js',
				array( 'jquery' ),
				WPO_MENU_CART_PRO_VERSION
			);

			// get URL to WordPress ajax handling page  
			if ( WPO_Menu_Cart_Pro()->main_settings['shop_plugin'] == 'Easy Digital Downloads' && function_exists( 'edd_get_ajax_url' ) ) {
				// use EDD function to prevent SSL issues http://git.io/V7w76A
				$ajax_url = edd_get_ajax_url();
			} else {
				$ajax_url = admin_url( 'admin-ajax.php' );
			}

			wp_localize_script(
				'wpmenucart',
				'wpmenucart_ajax',
				array(  
					'ajaxurl'        => $ajax_url,
					'nonce'          => wp_create_nonce('wpmenucart'),
				)
			);
		}

		if ( !isset( WPO_Menu_Cart_Pro()->main_settings['builtin_ajax'] ) && WPO_Menu_Cart_Pro()->main_settings['shop_plugin'] == 'Easy Digital Downloads' ) {
			wp_enqueue_script(
				'wpmenucart-edd-ajax',
				WPO_Menu_Cart_Pro()->plugin_url() . '/assets/js/wpmenucart-edd-ajax.js',
				array( 'jquery' ),
				WPO_MENU_CART_PRO_VERSION
			);
			wp_localize_script(
				'wpmenucart-edd-ajax',
				'wpmenucart_ajax',
				array(  
					'ajaxurl'        => function_exists( 'edd_get_ajax_url' ) ? edd_get_ajax_url() : admin_url( 'admin-ajax.php' ),
					'nonce'          => wp_create_nonce('wpmenucart'),
					'always_display' => isset(WPO_Menu_Cart_Pro()->main_settings['always_display']) ? WPO_Menu_Cart_Pro()->main_settings['always_display'] : '',
				)
			);
		}

		// extra script that improves AJAX behavior when 'Always display cart' is disabled
		wp_enqueue_script(
			'wpmenucart-ajax-assist',
			WPO_Menu_Cart_Pro()->plugin_url() . '/assets/js/wpmenucart-ajax-assist.js',
			array( 'jquery' ),
			WPO_MENU_CART_PRO_VERSION
		);
		wp_localize_script(
			'wpmenucart-ajax-assist',
			'wpmenucart_ajax_assist',
			array(  
				'shop_plugin' => WPO_Menu_Cart_Pro()->main_settings['shop_plugin'],
				'always_display' => isset(WPO_Menu_Cart_Pro()->main_settings['always_display']) ? WPO_Menu_Cart_Pro()->main_settings['always_display'] : '',
			)
		);
	}

	/**
	 * Load styles & scripts
	 */
	public function backend_scripts_styles ( $hook ) {
		// only load on our own settings page
		// maybe find a way to refer directly to WPO_Menu_Cart_Pro_Settings::$options_page_hook ?
		if ( $hook == 'woocommerce_page_wpo_wpmenucart_options_page' || $hook == 'settings_page_wpo_wpmenucart_options_page' ) {
			wp_enqueue_style(
				'wpmenucart-icons',
				WPO_Menu_Cart_Pro()->plugin_url() . '/assets/css/wpmenucart-icons-pro.css',
				array(),
				WPO_MENU_CART_PRO_VERSION
			);
			
			wp_enqueue_style(
				'wpmenucart-font',
				WPO_Menu_Cart_Pro()->plugin_url() . '/assets/css/wpmenucart-font.css',
				array(),
				WPO_MENU_CART_PRO_VERSION
			);

			wp_enqueue_style(
				'wpmenucart-admin',
				WPO_Menu_Cart_Pro()->plugin_url() . '/assets/css/wpmenucart-admin.css',
				array(),
				WPO_MENU_CART_PRO_VERSION
			);
			
			wp_enqueue_script(
				'wpmenucart-admin',
				WPO_Menu_Cart_Pro()->plugin_url() . '/assets/js/wpmenucart-admin.js',
				array( 'common', 'jquery', 'jquery-ui-tabs' ),
				WPO_MENU_CART_PRO_VERSION
			);

			wp_enqueue_script(
				'wpmenucart-upload-js',
				WPO_Menu_Cart_Pro()->plugin_url() . '/assets/js/media-upload.js',
				array( 'jquery' ),
				WPO_MENU_CART_PRO_VERSION
			);

			wp_enqueue_media();
		}


	}
}

endif; // class_exists

return new WPO_Menu_Cart_Pro_Assets();