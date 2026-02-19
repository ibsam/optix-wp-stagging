<?php
/**
 * Admin: Theme CSS Remover
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to remove theme CSS from AffiliateWP admin pages.
 *
 * @since 2.29.0
 */
class AffiliateWP_Theme_CSS_Remover {

	/**
	 * Constructor.
	 *
	 * @since 2.29.0
	 */
	public function __construct() {
		// Hook into multiple places to catch theme styles loaded at different times.
		add_action( 'admin_enqueue_scripts', [ $this, 'remove_theme_styles' ], 999 );
		add_action( 'admin_print_styles', [ $this, 'remove_theme_styles' ], 999 );
		add_action( 'admin_head', [ $this, 'remove_theme_styles' ], 1 );

		// Also filter style loader tags to prevent output.
		add_filter( 'style_loader_tag', [ $this, 'filter_style_tags' ], 999, 2 );
	}

	/**
	 * Remove theme styles from AffiliateWP admin pages.
	 *
	 * @since 2.29.0
	 *
	 * @return void
	 */
	public function remove_theme_styles() {
		// Only run on AffiliateWP admin pages.
		if ( ! affwp_is_admin_page() ) {
			return;
		}

		global $wp_styles;

		if ( ! is_object( $wp_styles ) ) {
			return;
		}

		// Get theme directories.
		$theme_url = get_theme_root_uri();

		// Loop through all registered styles.
		foreach ( $wp_styles->registered as $handle => $style ) {
			if ( ! isset( $style->src ) || empty( $style->src ) ) {
				continue;
			}

			// Check if the style is from the themes directory.
			if ( strpos( $style->src, $theme_url ) !== false ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}

	/**
	 * Filter style tags to prevent theme styles from being output.
	 *
	 * @since 2.29.0
	 *
	 * @param string $tag    The style tag HTML.
	 * @param string $handle The style handle.
	 * @return string The filtered tag (empty string for theme styles).
	 */
	public function filter_style_tags( $tag, $handle ) {
		// Only run on AffiliateWP admin pages.
		if ( ! affwp_is_admin_page() ) {
			return $tag;
		}

		// Get theme URL.
		$theme_url = get_theme_root_uri();

		// If the tag contains the theme URL, block it.
		if ( strpos( $tag, $theme_url ) !== false ) {
			return '';
		}

		return $tag;
	}
}

// Initialize the class.
new AffiliateWP_Theme_CSS_Remover();
