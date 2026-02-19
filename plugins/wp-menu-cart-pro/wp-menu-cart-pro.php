<?php
/**
 * Plugin Name: WP Menu Cart Pro
 * Plugin URI: www.wpovernight.com/plugins
 * Description: Extension for your e-commerce plugin (WooCommerce, WP-Ecommerce, Easy Digital Downloads, Eshop or Jigoshop) that places a cart icon with number of items and total cost in the menu bar. Activate the plugin, set your options and you're ready to go! Will automatically conform to your theme styles.
 * Version: 3.4.1
 * Author: Jeremiah Prummer, Ewout Fernhout
 * Author URI: www.wpovernight.com/about
 * License: GPL2
 * Text Domain: wp-menu-cart-pro
 * WC requires at least: 2.0.0
 * WC tested up to: 4.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WPO_Menu_Cart_Pro' ) ) :

class WPO_Menu_Cart_Pro {

	public $version = '3.4.1';
	public $plugin_basename;

	protected static $_instance = null;

	/**
	 * Main Plugin Instance
	 *
	 * Ensures only one instance of plugin is loaded or can be loaded.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->main_settings = get_option( 'wpo_wpmenucart_main_settings' );
		$this->texts_links_settings = get_option( 'wpo_wpmenucart_texts_links_settings' );
		$this->geek_settings = get_option( 'wpo_wpmenucart_geek_settings' );
		$this->plugin_basename = plugin_basename(__FILE__);

		$this->define( 'WPO_MENU_CART_PRO_VERSION', $this->version );

		// load the localisation & classes
		add_action( 'plugins_loaded', array( $this, 'translations' ) );
		add_action( 'init', array( $this, 'load_classes' ) );

		// Load the updater
		add_action( 'init', array( $this, 'load_updater' ), 0 );

		// run lifecycle methods
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_action( 'wp_loaded', array( $this, 'do_install' ) );
		}
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Run the updater scripts from the WPO Sidekick
	 * @return void
	 */
	public function load_updater() {
		// Init updater data
		$item_name		= 'Menu Cart Pro';
		$file			= __FILE__;
		$license_slug	= 'wpmenucart_pro_license';
		$version		= WPO_MENU_CART_PRO_VERSION;
		$author			= 'Jeremiah Prummer, Ewout Fernhout';

		// Check if sidekick is loaded
		if (class_exists('WPO_Updater')) {
			$this->updater = new WPO_Updater( $item_name, $file, $license_slug, $version, $author );
		}
	}

	/**
	 * Load the translation / textdomain files
	 * 
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 */
	public function translations() {
		if ( function_exists( 'determine_locale' ) ) { // WP5.0+
			$locale = determine_locale();
		} else {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		}
		$locale = apply_filters( 'plugin_locale', $locale, 'wp-menu-cart-pro' );
		$dir    = trailingslashit( WP_LANG_DIR );

		/**
		 * Frontend/global Locale. Looks in:
		 *
		 * 		- WP_LANG_DIR/wp-menu-cart-pro/wp-menu-cart-pro-LOCALE.mo
		 * 	 	- WP_LANG_DIR/plugins/wp-menu-cart-pro-LOCALE.mo
		 * 	 	- wp-menu-cart-pro-pro/languages/wp-menu-cart-pro-LOCALE.mo (which if not found falls back to:)
		 * 	 	- WP_LANG_DIR/plugins/wp-menu-cart-pro-LOCALE.mo
		 */
		load_textdomain( 'wp-menu-cart-pro', $dir . 'wp-menu-cart-pro/wp-menu-cart-pro-' . $locale . '.mo' );
		load_textdomain( 'wp-menu-cart-pro', $dir . 'plugins/wp-menu-cart-pro-' . $locale . '.mo' );
		load_plugin_textdomain( 'wp-menu-cart-pro', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
	}

	/**
	 * Load the main plugin classes and functions
	 */
	public function includes() {
		include_once( 'includes/class-wpmenucart-assets.php' );
		include_once( 'includes/class-wpmenucart-settings.php' );
		include_once( 'includes/class-wpmenucart-template-parser.php' );
		include_once( 'includes/class-wpmenucart-main.php' );
		include_once( 'includes/class-wpmenucart-data.php' );

		if (isset($this->main_settings['shop_plugin'])) {
			if ( false === $this->is_shop_active( array(), $this->main_settings['shop_plugin'] ) ) {
				return;
			}
			switch ($this->main_settings['shop_plugin']) {
				case 'WooCommerce':
					include_once( 'includes/shops/wpmenucart-woocommerce.php' );
					include_once( 'includes/shops/wpmenucart-woocommerce-pro.php' );

					// include compatibility classes
					include_once( 'includes/compatibility/abstract-wc-data-compatibility.php' );
					include_once( 'includes/compatibility/class-wc-date-compatibility.php' );
					include_once( 'includes/compatibility/class-wc-core-compatibility.php' );
					include_once( 'includes/compatibility/class-wc-product-compatibility.php' );
					// include_once( 'includes/compatibility/class-wc-order-compatibility.php' );
					include_once( 'includes/compatibility/class-wpmenucart-compatibility-third-party-plugins.php' );

					$this->shop = new WPMenuCart_WooCommerce_Pro();
					break;
				case 'Jigoshop':
					include_once( 'includes/shops/wpmenucart-jigoshop.php' );
					include_once( 'includes/shops/wpmenucart-jigoshop-pro.php' );
					$this->shop = new WPMenuCart_Jigoshop_Pro();
					break;
				case 'WP e-Commerce':
					include_once( 'includes/shops/wpmenucart-wpec.php' );
					include_once( 'includes/shops/wpmenucart-wpec-pro.php' );
					$this->shop = new WPMenuCart_WPEC_Pro();
					break;
				case 'eShop':
					include_once( 'includes/shops/wpmenucart-eshop.php' );
					include_once( 'includes/shops/wpmenucart-eshop-pro.php' );
					$this->shop = new WPMenuCart_eShop_Pro();
					break;
				case 'Easy Digital Downloads':
					include_once( 'includes/shops/wpmenucart-edd.php' );
					include_once( 'includes/shops/wpmenucart-edd-pro.php' );
					$this->shop = new WPMenuCart_EDD_Pro();
					break;
			}
		}
	}
	

	/**
	 * Instantiate classes when woocommerce is activated
	 */
	public function load_classes() {
		if ( $this->good_to_go() ) {
			$this->includes();
		}
	}

	/**
	 * Check if a shop is active or if conflicting old versions of the plugin are active
	 * @return boolean
	 */
	public function good_to_go() {
		$wpmenucart_shop_check = get_option( 'wpmenucart_shop_check' );
		$active_plugins = $this->get_active_plugins();

		if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
			add_action( 'admin_notices', array ( $this, 'required_php_version' ) );
			return FALSE;
		}

		// check for shop plugins
		if ( !$this->is_shop_active( $active_plugins ) && $wpmenucart_shop_check != 'hide' ) {
			add_action( 'admin_notices', array ( $this, 'need_shop' ) );
			return FALSE;
		}

		// check for old versions
		if ( count( $this->get_active_old_versions( $active_plugins ) ) > 0 ) {
			// add_action( 'admin_notices', array ( $this, 'woocommerce_version_active' ) );
			return FALSE;
		}

		// check for free version
		if ( count( $this->find_free_versions( $active_plugins ) ) > 0 ) {
			// add_action( 'admin_notices', array ( $this, 'free_version_active' ) );
			return FALSE;
		}

		// we made it! good to go :o)
		return TRUE;
	}

	/**
	 * Return true if one ore more shops are activated.
	 * @return boolean
	 */
	public function is_shop_active( $active_plugins = array(), $shop = '' ) {
		if ( empty($shop) ) {
			if ( count( $this->get_active_shops( $active_plugins ) ) > 0 ) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			switch ( $shop ) {
				case 'WooCommerce':
					return function_exists('WC');
					break;
				case 'Easy Digital Downloads':
					return function_exists('EDD');
					break;
				case 'Jigoshop':
					return class_exists('jigoshop_cart');
					break;
				case 'WP e-Commerce':
					return function_exists('wpsc_cart_item_count');
					break;
				case 'eShop':
					return !empty($GLOBALS['eshopoptions']);
					break;
				default:
					return false;
					break;
			}
		}
	}

	/**
	 * Get an array of all active plugins, including multisite
	 * @return array active plugin paths
	 */
	public function get_active_plugins() {
		$active_plugins = (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		if (is_multisite()) {
			// get_site_option( 'active_sitewide_plugins', array() ) returns a 'reversed list'
			// like [hello-dolly/hello.php] => 1369572703 so we do array_keys to make the array
			// compatible with $active_plugins
			$active_sitewide_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			// merge arrays and remove doubles
			$active_plugins = (array) array_unique( array_merge( $active_plugins, $active_sitewide_plugins ) );
		}

		return $active_plugins;
	}
	
	/**
	 * Get array of active shop plugins
	 * 
	 * @return array plugin name => plugin path
	 */
	public function get_active_shops( $active_plugins = array() ) {
		if ( empty( $active_plugins ) ) {
			$active_plugins = $this->get_active_plugins();
		}

		$shop_plugins = array (
			'WooCommerce'				=> 'woocommerce/woocommerce.php',
			'Jigoshop'					=> 'jigoshop/jigoshop.php',
			'WP e-Commerce'				=> 'wp-e-commerce/wp-shopping-cart.php',
			'eShop'						=> 'eshop/eshop.php',
			'Easy Digital Downloads'	=> 'easy-digital-downloads/easy-digital-downloads.php',
		);
		
		// filter shop plugins & add shop names as keys
		$active_shop_plugins = array_intersect( $shop_plugins, $active_plugins );

		return $active_shop_plugins;
	}

	/**
	 * Get array of active old WooCommerce Menu Cart plugins
	 * 
	 * @return array plugin paths
	 */
	public function get_active_old_versions( $active_plugins = array() ) {
		if ( empty( $active_plugins ) ) {
			$active_plugins = $this->get_active_plugins();
		}
		
		$old_versions = array (
			'woocommerce-menu-bar-cart/wc_cart_nav.php',				//first version
			'woocommerce-menu-bar-cart/woocommerce-menu-cart.php',		//last free version
			'woocommerce-menu-cart/woocommerce-menu-cart.php',			//never actually released? just in case...
			'woocommerce-menu-cart-pro/woocommerce-menu-cart-pro.php',	//old pro version
		);
			
		$active_old_plugins = array_intersect( $old_versions, $active_plugins );
				
		return $active_old_plugins;
	}

	/**
	 * Get array of installed free WP Menu Cart plugins (most likely one of course :)
	 * 
	 * @return array plugin paths
	 */
	public function find_free_versions( $active_plugins = array() ) {
		if ( empty( $active_plugins ) ) {
			$active_plugins = $this->get_active_plugins();
		}
		
		$free_versions = array();
		// find free versions regardless of plugin folder name
		foreach ($active_plugins as $plugin) {
			if ( strpos($plugin, 'wp-menu-cart.php') !== false ) {
				$free_versions[] = $plugin;
			}
		}			
				
		return $free_versions;
	}

	/**
	 * Fallback admin notices
	 *
	 * @return string Fallack notice.
	 */
	public function need_shop() {
		$error = __( 'WP Menu Cart Pro could not detect an active shop plugin. Make sure you have activated at least one of the supported plugins.' , 'wp-menu-cart-pro' );
		$message = sprintf('<div class="error"><p>%1$s <a href="%2$s">%3$s</a></p></div>', $error, add_query_arg( 'hide_wpmenucart_shop_check', 'true' ), __( 'Hide this notice', 'wp-menu-cart-pro' ) );
		echo $message;
	}
	public function woocommerce_version_active() {
		$error = __( 'An old version of WooCommerce Menu Cart is currently activated, you need to disable or uninstall it for WP Menu Cart to function properly' , 'wp-menu-cart-pro' );
		$message = '<div class="error"><p>' . $error . '</p></div>';
		echo $message;
	}

	public function free_version_active() {
		$error = __( 'The free version of WP Menu Cart is currently activated, you need to disable or uninstall it for WP Menu Cart Pro to function properly' , 'wp-menu-cart-pro' );
		$message = '<div class="error"><p>' . $error . '</p></div>';
		echo $message;
	}

	/**
	 * PHP version requirement notice
	 */
	
	public function required_php_version() {
		$error = __( 'WP Menu Cart Pro requires PHP 5.3 or higher (5.6 or higher recommended).', 'wp-menu-cart-pro' );
		$how_to_update = __( 'How to update your PHP version', 'wp-menu-cart-pro' );
		$message = sprintf('<div class="error"><p>%s</p><p><a href="%s">%s</a></p></div>', $error, 'http://docs.wpovernight.com/general/how-to-update-your-php-version/', $how_to_update);
	
		echo $message;
	}


	/** Lifecycle methods *******************************************************
	 * Because register_activation_hook only runs when the plugin is manually
	 * activated by the user, we're checking the current version against the
	 * version stored in the database
	****************************************************************************/

	/**
	 * Handles version checking
	 */
	public function do_install() {
		$version_setting = 'wpo_wpmenucart_version';
		$installed_version = get_option( $version_setting );

		// installed version lower than plugin version?
		if ( version_compare( $installed_version, $this->version, '<' ) ) {

			if ( ! $installed_version ) {
				$this->install();
			} else {
				$this->upgrade( $installed_version );
			}

			// new version number
			update_option( $version_setting, $this->version );
		}
	}


	/**
	 * Plugin install method. Perform any installation tasks here
	 */
	protected function install() {
		// deactivate free version
		add_action( 'admin_init', array( $this, 'deactivate_free_version') );

		// check if we're actually upgrading from an old version (by checking old settings existence)
		$old_settings = get_option('wpmenucart');
		$main_settings = array();
		if ( $old_settings !== false ) {
			// old version - copy settings
			$shop_plugins = array( // old version used slugs
				'woocommerce'				=> 'WooCommerce',
				'jigoshop'					=> 'Jigoshop',
				'wp-e-commerce'				=> 'WP e-Commerce',
				'eshop'						=> 'eShop',
				'easy-digital-downloads'	=> 'Easy Digital Downloads',
			);

			$dont_copy = array( 'custom_class', 'wpml_string_translation' );
			foreach ($old_settings as $key => $value) {
				if (in_array($key, $dont_copy)) {
					continue;
				}

				if ($key == 'shop_plugin' && !empty($shop_plugins[$value]) ) {
					$value = $shop_plugins[$value];
				}

				$main_settings[$key] = $value;
			}
			update_option( 'wpo_wpmenucart_main_settings', $main_settings );

			// custom_class moved to geek settings
			if (isset($old_settings['custom_class'])) {
				$geek_settings = array( 'main_li_class' => $old_settings['custom_class'] );
				update_option( 'wpo_wpmenucart_geek_settings', $geek_settings );
			}

		}
	}

	/**
	 * Plugin upgrade method.  Perform any required upgrades here
	 *
	 * @param string $installed_version the currently installed ('old') version
	 */
	protected function upgrade( $installed_version ) {
		// stub
	}		

	/**
	 * Automatically deactivate free versions to avoid conflicts
	 */
	public function deactivate_free_version() {
		// get all active plugins
		$active_plugins = $this->get_active_plugins();

		// misc free versions
		$free_versions = array (
			'woocommerce-menu-bar-cart/wc_cart_nav.php',			// first version
			'woocommerce-menu-bar-cart/woocommerce-menu-cart.php',	// second version
			'woocommerce-menu-bar-cart/wp-menu-cart.php',			// CURRENT	
			'wp-menu-cart/wp-menu-cart.php'							// CURRENT
		);
		// find free versions installed in other folders
		$free_versions = array_merge( $free_versions, $this->find_free_versions( $active_plugins ) );

		// deactivate all free versions (probably one)
		deactivate_plugins( $free_versions );
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	public function log( $message, $level = 'debug' ) {
		$logger = wc_get_logger();
		$context = array( 'source' => 'wp-menu-cart-pro' );
		$logger->log( $level, $message, $context );
	}

} // class WPO_Menu_Cart_Pro

endif; // class_exists

/**
 * Returns the main instance of WP Menu Cart to prevent the need to use globals.
 *
 * @since  3.0
 * @return WPO_Menu_Cart_Pro
 */
function WPO_Menu_Cart_Pro() {
	return WPO_Menu_Cart_Pro::instance();
}

WPO_Menu_Cart_Pro(); // load plugin

/**
 * WPOvernight updater admin notice
 */
if ( ! class_exists( 'WPO_Updater' ) && ! function_exists( 'wpo_updater_notice' ) ) {

	if ( ! empty( $_GET['hide_wpo_updater_notice'] ) ) {
		update_option( 'wpo_updater_notice', 'hide' );
	}

	/**
	 * Display a notice if the "WP Overnight Sidekick" plugin hasn't been installed.
	 * @return void
	 */
	function wpo_updater_notice() {
		$wpo_updater_notice = get_option( 'wpo_updater_notice' );

		$blog_plugins = get_option( 'active_plugins', array() );
		$site_plugins = get_site_option( 'active_sitewide_plugins', array() );
		$plugin = 'wpovernight-sidekick/wpovernight-sidekick.php';

		if ( in_array( $plugin, $blog_plugins ) || isset( $site_plugins[$plugin] ) || $wpo_updater_notice == 'hide' ) {
			return;
		}

		echo '<div class="updated fade"><p>Install the <strong>WP Overnight Sidekick</strong> plugin to receive updates for your WP Overnight plugins - check your order confirmation email for more information. <a href="'.add_query_arg( 'hide_wpo_updater_notice', 'true' ).'">Hide this notice</a></p></div>' . "\n";
	}

	add_action( 'admin_notices', 'wpo_updater_notice' );
}