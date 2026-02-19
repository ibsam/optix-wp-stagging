<?php
/**
 * Create & render settings page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WPO_Menu_Cart_Pro_Settings' ) ) :

class WPO_Menu_Cart_Pro_Settings {

	public $options_page_hook;
	
	function __construct()	{
		$this->callbacks = include( 'class-wpmenucart-settings-callbacks.php' );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_filter( 'plugin_action_links_'.WPO_Menu_Cart_Pro()->plugin_basename, array( $this, 'add_settings_link' ) );

		add_action( 'admin_init', array( $this, 'main_settings' ) );
		add_action( 'admin_init', array( $this, 'texts_links_settings' ) );
		add_action( 'admin_init', array( $this, 'geek_settings' ) );
	}

	/**
	 * Add settings item to WooCommerce menu
	 */
	public function menu() {
		if (class_exists('WooCommerce')) {
			$parent_slug = 'woocommerce';
		} else {
			$parent_slug = 'options-general.php';
		}

		$this->options_page_hook = add_submenu_page(
			$parent_slug,
			__( 'Menu Cart Pro', 'wp-menu-cart-pro' ),
			__( 'Menu Cart Pro', 'wp-menu-cart-pro' ),
			'manage_options',
			'wpo_wpmenucart_options_page',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Add settings link to plugins page
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=wpo_wpmenucart_options_page">'. __( 'Settings', 'wp-menu-cart-pro' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}


	public function settings_page() {
		$settings_tabs = apply_filters( 'wpo_wpmenucart_settings_tabs', array (
				'main'			=> __( 'Main', 'wp-menu-cart-pro' ),
				'texts_links'	=> __( 'Texts & Links', 'wp-menu-cart-pro' ),
				'geek'			=> __( 'Advanced', 'wp-menu-cart-pro' ),
			)
		);

		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'main';
		?>
		<div class="wrap">
			<h1><?php _e( 'WP Menu Cart', 'wp-menu-cart-pro' ); ?></h1>
			<h2 class="nav-tab-wrapper">
			<?php
			foreach ($settings_tabs as $tab_slug => $tab_title ) {
				printf('<a href="?page=wpo_wpmenucart_options_page&tab=%1$s" class="nav-tab nav-tab-%1$s %2$s">%3$s</a>', $tab_slug, (($active_tab == $tab_slug) ? 'nav-tab-active' : ''), $tab_title);
			}
			?>
			</h2>

			<?php do_action( 'wpo_wpmenucart_before_settings_page', $active_tab ); ?>
				
			<form method="post" action="options.php" id="wpo-wpmenucart-settings">
				<?php
					do_action( 'wpo_wpmenucart_before_settings', $active_tab );
					settings_fields( 'wpo_wpmenucart_'.$active_tab.'_settings' );
					do_settings_sections( 'wpo_wpmenucart_'.$active_tab.'_settings' );
					do_action( 'wpo_wpmenucart_after_settings', $active_tab );

					submit_button();
				?>
			</form>

			<?php do_action( 'wpo_wpmenucart_after_settings_page', $active_tab ); ?>

		</div>
		<?php
	}

	/**
	 * Register Main settings
	 */
	public function main_settings() {
		$option_group = 'wpo_wpmenucart_main_settings';

		// Register settings.
		$option_name = 'wpo_wpmenucart_main_settings';
		register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );

		// Create option in wp_options.
		if ( false === get_option( $option_name ) ) {
			$this->default_settings( $option_name );
		}
		$option_values = get_option( $option_name );

		// Section.
		add_settings_section(
			'main_settings',
			__( 'Main settings', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'shop_plugin',
			__( 'E-commerce Plugin', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'shop_select' ),
			$option_group,
			'main_settings',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'shop_plugin',
				'options'		=> WPO_Menu_Cart_Pro()->get_active_shops(),
				'description'	=> __( 'Select which e-commerce plugin you would like Menu Cart to work with.', 'wp-menu-cart-pro' ),
			)
		);

		if( $parent_theme = wp_get_theme(get_template()) ) {
			if (in_array($parent_theme->get('Name'), array('Storefront','Divi'))) {
				add_settings_field(
					'hide_theme_cart',
					__( 'Hide theme shopping cart icon', 'wp-menu-cart-pro' ),
					array( $this->callbacks, 'checkbox' ),
					$option_group,
					'main_settings',
					array(
						'option_name'	=> $option_name,
						'id'			=> 'hide_theme_cart',
					)
				);
			}
		}

		add_settings_field(
			'menu_slugs',
			__( 'Select the menu(s) in which you want to display the Menu Cart', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'menus_select' ),
			$option_group,
			'main_settings',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'menu_slugs',
			)
		);

		add_settings_field(
			'always_display',
			__( 'Always display cart', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'main_settings',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'always_display',
				'description'	=> __( 'Always display cart, even if it\'s empty.', 'wp-menu-cart-pro' ),
			)
		);

		if ( function_exists('WC') ) {
			add_settings_field(
				'show_on_cart_checkout_page',
				__( 'Show on cart & checkout page', 'wp-menu-cart-pro' ),
				array( $this->callbacks, 'checkbox' ),
				$option_group,
				'main_settings',
				array(
					'option_name'	=> $option_name,
					'id'			=> 'show_on_cart_checkout_page',
					'description'	=> __( 'To avoid distracting your customers with duplicate information we do not display the menu cart item on the cart & checkout pages by default', 'wp-menu-cart-pro' ),
				)
			);
		}

		add_settings_field(
			'flyout_display',
			__( 'Use Flyout', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'main_settings',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'flyout_display',
				'description'	=> __( 'Select to display cart contents in menu fly-out.', 'wp-menu-cart-pro' ),
			)
		);

		add_settings_field(
			'flyout_item_truncation',
			__( 'Flyout item truncation', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'main_settings',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'flyout_item_truncation',
				'type'			=> 'number',
				'size'			=> 3,
				'description'	=> __( 'Maximum number of characters for product names in the flyout. To use full product names, enter 0.', 'wp-menu-cart-pro' ),
			)
		);
		
		add_settings_field(
			'flyout_itemnumber',
			__( 'Flyout item number', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'select' ),
			$option_group,
			'main_settings',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'flyout_itemnumber',
				'options'		=> array(
						'0'			=> __( 'Unlimited', 'wp-menu-cart-pro' ),
						'1'			=> '1',
						'2'			=> '2',
						'3'			=> '3',
						'4'			=> '4',
						'5'			=> '5',
						'6'			=> '6',
						'7'			=> '7',
						'8'			=> '8',
						'9'			=> '9',
						'10'		=> '10',
				),
				'description'	=> __( 'Set maximum number of products to display in fly-out.', 'wp-menu-cart-pro' ),
			)
		);			

		add_settings_field(
			'icon_display',
			__( 'Display shopping cart icon.', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'main_settings',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'icon_display',
			)
		);

		add_settings_field(
			'cart_icon',
			__( 'Choose a cart icon.', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'icons_radio_element_callback' ),
			$option_group,
			'main_settings',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'cart_icon',
				'options' 		=> array(
					'0'			=> '0',
					'1'			=> '1',
					'2'			=> '2',
					'3'			=> '3',
					'4'			=> '4',
					'5'			=> '5',
					'6'			=> '6',
					'7'			=> '7',
					'8'			=> '8',
					'9'			=> '9',
					'10'		=> '10',
					'11'		=> '11',
					'12'		=> '12',
					'13'		=> '13',
				),
			)
		);

		add_settings_field(
			'custom_icon',
			__( 'Custom Icon', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'media_upload_callback' ),
			$option_group,
			'main_settings',
			array(
				'option_name'			=> $option_name,
				'id'					=> 'custom_icon',
				'description'			=> __( 'Upload a custom menu cart icon here if you do not want to use one of the icons above. Make sure you resize the icon before uploading. Icon should usually be 15-30px tall.', 'wp-menu-cart-pro' ),
				'uploader_title'		=> __( 'Select or upload a custom menu cart icon.', 'wp-menu-cart-pro' ),
				'uploader_button_text'	=> __( 'Set image', 'wp-menu-cart-pro' ),
				'remove_button_text'	=> __( 'Remove image', 'wp-menu-cart-pro' ),
			)
		);

		add_settings_field(
			'items_display',
			__( 'Contents of the menu cart item', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'select' ),
			$option_group,
			'main_settings',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'items_display',
				'options' 		=> array(
					'1'			=> __( 'Items Only.' , 'wp-menu-cart-pro' ),
					'2'			=> __( 'Price Only.' , 'wp-menu-cart-pro' ),
					'3'			=> __( 'Both price and items.' , 'wp-menu-cart-pro' ),
					'custom'	=> __( 'Custom:' , 'wp-menu-cart-pro' ),
				),
				'custom'		=> array(
					'type'		=> 'i18n_wrap',
					'args'		=> array(
						'option_name'	=> $option_name,
						'callback'		=> 'text_input',
						'id'			=> 'custom_items_display',
						'size'			=> 40,
						'description'	=> __( 'You can use the following placeholders: {{icon}}, {{# items}}, {{#}}, {{items}}, {{price}}', 'wp-menu-cart-pro' ),
					),
				),
			)
		);

		add_settings_field(
			'items_alignment',
			__( 'Select the alignment that looks best with your menu.', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'radio_button' ),
			$option_group,
			'main_settings',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'items_alignment',
				'options' 		=> array(
					'left'			=> __( 'Align Left.' , 'wp-menu-cart-pro' ),
					'right'			=> __( 'Align Right.' , 'wp-menu-cart-pro' ),
					'standard'		=> __( 'Default Menu Alignment.' , 'wp-menu-cart-pro' ),
				),	
			)
		);

		if ( class_exists( 'WooCommerce' ) ) {
			add_settings_field(
				'total_price_type',
				__( 'Price to display', 'wp-menu-cart-pro' ),
				array( $this->callbacks, 'select' ),
				$option_group,
				'main_settings',
				array(
					'option_name'	=> $option_name,
					'id'			=> 'total_price_type',
					'options' 		=> array(
						'total'			 => __( 'Cart total (including discounts)' , 'wp-menu-cart-pro' ),
						'subtotal'		 => __( 'Subtotal (total of products)' , 'wp-menu-cart-pro' ),
						'checkout_total' => __( 'Checkout total (including discounts, fees & shipping)' , 'wp-menu-cart-ro' ),
					),
				)
			);
		}
		
		/* no string translation
		if ( function_exists( 'icl_register_string' ) ) {
			add_settings_field(
				'wpml_string_translation',
				__( "Use WPML String Translation", 'wp-menu-cart-pro' ),
				array( $this->callbacks, 'checkbox' ),
				$option_group,
				'main_settings',
				array(
					'option_name'	=> $option_name,
					'id'			=> 'wpml_string_translation',
				)
			);
		}
		*/

		if ( apply_filters( 'wpo_wpmenucart_enable_builtin_ajax_setting', ( class_exists( 'WooCommerce' ) && isset( $option_values['builtin_ajax'] ) ) || defined('JIGOSHOP_VERSION') || class_exists( 'Easy_Digital_Downloads' ) ) ) {
			add_settings_field(
				'builtin_ajax',
				__( 'Use custom AJAX', 'wp-menu-cart-pro' ),
				array( $this->callbacks, 'checkbox' ),
				$option_group,
				'main_settings',
				array(
					'option_name'	=> $option_name,
					'id'			=> 'builtin_ajax',
					'description'	=> __( 'Enable this option to use the custom AJAX / live update functions instead of the default ones from your shop plugin. Only use when you have issues with AJAX!', 'wp-menu-cart-pro' ),
				)
			);
		}

	}

	/**
	 * Register Texts & Links settings
	 */
	public function texts_links_settings() {
		$option_group = 'wpo_wpmenucart_texts_links_settings';

		// Register settings.
		$option_name = 'wpo_wpmenucart_texts_links_settings';
		register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );

		// Create option in wp_options.
		if ( false === get_option( $option_name ) ) {
			$this->default_settings( $option_name );
		}

		// Section.
		add_settings_section(
			'texts',
			__( 'Texts', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'start_shopping_text',
			__( 'Empty cart (main item hover)', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'i18n_wrap' ),
			$option_group,
			'texts',
			array(
				'callback'			=> 'text_input',
				'option_name'		=> $option_name,
				'id'				=> 'start_shopping_text',
				'size'				=> 25,
				'placeholder'		=> __('Start shopping', 'wp-menu-cart-pro'),
				'i18n_placeholder'	=> true,
				'i18n_description'	=> __( 'Leave empty for default', 'wp-menu-cart-pro' ),
			)
		);

		add_settings_field(
			'empty_cart_text',
			__( 'Empty cart (flyout)', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'i18n_wrap' ),
			$option_group,
			'texts',
			array(
				'callback'			=> 'text_input',
				'option_name'		=> $option_name,
				'id'				=> 'empty_cart_text',
				'size'				=> 25,
				'placeholder'		=> __('your cart is currently empty', 'wp-menu-cart-pro'),
				'i18n_placeholder'	=> true,
				'i18n_description'	=> __( 'Leave empty for default', 'wp-menu-cart-pro' ),
			)
		);

		add_settings_field(
			'view_cart_text',
			__( 'View cart (main item hover & flyout)', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'i18n_wrap' ),
			$option_group,
			'texts',
			array(
				'callback'			=> 'text_input',
				'option_name'		=> $option_name,
				'id'				=> 'view_cart_text',
				'size'				=> 25,
				'placeholder'		=> __('View your shopping cart', 'wp-menu-cart-pro'),
				'i18n_placeholder'	=> true,
				'i18n_description'	=> __( 'Leave empty for default', 'wp-menu-cart-pro' ),
			)
		);

		add_settings_field(
			'item_text',
			__( 'Items', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'i18n_wrap' ),
			$option_group,
			'texts',
			array(
				'callback'		=> 'multiple_text_input',
				'option_name'	=> $option_name,
				'id'			=> 'item_text',
				'fields'		=> array(
					'single'		=> array(
						'label'				=> __( 'Single' , 'wp-menu-cart-pro' ),
						'label_width'		=> '150px',
						'placeholder'		=> str_replace( '%d ', '', _n('%d item', '%d items', 1, 'wp-menu-cart-pro') ),
						'i18n_placeholder'	=> true,
						'size'				=> '15',
					),
					'plural'	=> array(
						'label'				=> __( 'Plural & zero' , 'wp-menu-cart-pro' ),
						'label_width'		=> '150px',
						'placeholder'		=> str_replace( '%d ', '', _n('%d item', '%d items', 2, 'wp-menu-cart-pro') ),
						'i18n_placeholder'	=> true,
						'size'				=> '15',
					),
				),
				'i18n_description'	=> __( 'Leave empty for default', 'wp-menu-cart-pro' ),
			)
		);

		if (isset(WPO_Menu_Cart_Pro()->shop)) {
			// Section.
			add_settings_section(
				'links',
				__( 'Links', 'wp-menu-cart-pro' ),
				array( $this->callbacks, 'section' ),
				$option_group
			);

			add_settings_field(
				'cart_url',
				__( 'Cart URL', 'wp-menu-cart-pro' ),
				array( $this->callbacks, 'i18n_wrap' ),
				$option_group,
				'links',
				array(
					'callback'			=> 'text_input',
					'option_name'		=> $option_name,
					'id'				=> 'cart_url',
					'size'				=> 80,
					'placeholder'		=> WPO_Menu_Cart_Pro()->shop->cart_url(),
					'i18n_placeholder'	=> true,
					'i18n_description'	=> __( 'Leave empty for default', 'wp-menu-cart-pro' ),
				)
			);

			add_settings_field(
				'shop_url',
				__( 'Shop URL', 'wp-menu-cart-pro' ),
				array( $this->callbacks, 'i18n_wrap' ),
				$option_group,
				'links',
				array(
					'callback'			=> 'text_input',
					'option_name'		=> $option_name,
					'id'				=> 'shop_url',
					'size'				=> 80,
					'placeholder'		=> WPO_Menu_Cart_Pro()->shop->shop_url(),
					'i18n_placeholder'	=> true,
					'i18n_description'	=> __( 'Leave empty for default', 'wp-menu-cart-pro' ),
				)
			);
		}

	}

	/**
	 * Register Geek settings
	 */
	public function geek_settings() {
		$option_group = 'wpo_wpmenucart_geek_settings';

		// Register settings.
		$option_name = 'wpo_wpmenucart_geek_settings';
		register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );

		// Create option in wp_options.
		if ( false === get_option( $option_name ) ) {
			$this->default_settings( $option_name );
		}

		// Section.
		add_settings_section(
			'classes',
			__( 'Additional CSS classes', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'main_li_class',
			__( 'Main menu item (li)', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'classes',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'main_li_class',
				'size'			=> 80,
			)
		);

		add_settings_field(
			'main_a_class',
			__( 'Main menu item (a)', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'classes',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'main_a_class',
				'size'			=> 80,
			)
		);

		add_settings_field(
			'submenu_ul_class',
			__( 'Flyout (ul)', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'classes',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'submenu_ul_class',
				'size'			=> 80,
			)
		);

		add_settings_field(
			'submenu_li_class',
			__( 'Flyout item (li)', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'classes',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'submenu_li_class',
				'size'			=> 80,
			)
		);

		add_settings_field(
			'submenu_a_class',
			__( 'Flyout item (a)', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'classes',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'submenu_a_class',
				'size'			=> 80,
			)
		);

		// Section.
		add_settings_section(
			'misc',
			__( 'Misc', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'custom_styles',
			__( 'Custom styles', 'wp-menu-cart-pro' ),
			array( $this->callbacks, 'textarea' ),
			$option_group,
			'misc',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'custom_styles',
				'width'			=> '80',
				'height'		=> '8',
			)
		);

	}

	/**
	 * Set default settings.
	 */
	public function default_settings( $option ) {
		switch ( $option ) {
			case 'wpo_wpmenucart_main_settings':
				$active_shop_plugins = WPO_Menu_Cart_Pro()->get_active_shops();
				$active_shop_plugins = array_keys ( $active_shop_plugins );
				$first_active_shop_plugin = array_shift( $active_shop_plugins );
				$default = array(
					'shop_plugin'		=> $first_active_shop_plugin,
					'icon_display'		=> '1', // Enabled
					'hide_theme_cart'	=> '1', // Enabled
					'items_display'		=> '3', // Both price and items
					'items_alignment'	=> 'standard', // Default Menu Alignment
					'flyout_itemnumber'	=> '5',
					'cart_icon'			=> '0',
				);
				break;
			case 'wpo_wpmenucart_texts_links_settings':
			case 'wpo_wpmenucart_geek_settings':
			default:
				$default = array();
				break;
		}

		if ( false === get_option( $option ) ) {
			add_option( $option, $default );
		} else {
			update_option( $option, $default );
		}
	}
}

endif; // class_exists

return new WPO_Menu_Cart_Pro_Settings();