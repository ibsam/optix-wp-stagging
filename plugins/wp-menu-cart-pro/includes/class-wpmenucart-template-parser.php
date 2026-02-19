<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WPO_Menu_Cart_Pro_Template' ) ) :

class WPO_Menu_Cart_Pro_Template {

	public $html;
	public $template;
	public $template_parts;
	public $nav_menu_items;
	public $menu_slug;
	public $dont_render;

	function __construct( $template_slug = '', $nav_menu_items = '', $args ) {
		$defaults = array(
			'menu_slug'	=> '',
			'part'		=> '',
		);
		$args = wp_parse_args( $args, $defaults );

		$this->nav_menu_items = $nav_menu_items;
		$this->menu_slug = $args['menu_slug'];
		if (isset($args['wc_fragments'])) {
			$this->dont_render = $args['part'];
		}

		if (!empty($template_slug)) {
			$this->load( $template_slug, $args );
		}
	}

	public function load ( $template_slug, $args ) {
		$template_path = $this->get_template_path( $template_slug );
		$this->template = $template = $this->get_template( $template_path );

		// get only part of template if requested
		if (!empty($args['part'])) {
			$this->template = $template = $this->get_template_part( $args['part'], $template );
		}

		$template_parts = array();
		$template_parts['main'] = $template;

		// separate full & empty cart parts (array: can be multiple!)
		$conditional_parts['empty_cart'] = $this->get_template_part( 'empty_cart', $template, 'with_tag', true );
		$conditional_parts['full_cart']  = $this->get_template_part( 'full_cart', $template, 'with_tag', true );

		// insert placeholders for each template part
		foreach ($conditional_parts as $tag => $matches) {
			if (empty($matches)) {
				continue;
			}
			foreach ($matches as $match_key => $match) {
				$match_tag = "{{{$tag}_{$match_key}}}";
				$template_parts[$tag][$match_tag] = $match;
				$template_parts['main'] = str_replace($match, $match_tag, $template_parts['main']);
			}
		}

		// separate submenu and replace with new placeholder {{submenu}} if found
		$template_parts['submenu'] = $this->get_template_part( 'submenu', $template_parts['main'] );
		if ( $template_parts['submenu'] != false ) {
			// echo "<pre>";var_dump($template_parts['submenu']);die();
			$template_parts['main'] = str_replace($template_parts['submenu'], '{{submenu}}', $template_parts['main']);

			// separate submenu item li and replace with new placeholder {{items}} if found
			$template_parts['submenu_items'] = $this->get_template_part( 'submenu_items', $template_parts['submenu'] );
			if ( $template_parts['submenu_items'] != false ) {
				$template_parts['submenu'] = str_replace($template_parts['submenu_items'], '{{items}}', $template_parts['submenu']);
			}
		}

		$this->template_parts = $template_parts;
	}

	/**
	 * Return template contents
	 */
	public function get_template( $file ) {
		ob_start();
		if (file_exists($file)) {
			include($file);
		}
		return ob_get_clean();
	}

	/**
	 * Get the template path for a file. locate by file existience
	 * and then return the corresponding file path.
	 */
	public function get_template_path( $template_slug ) {
		$template_locations = array(
			'child_theme_template_path'	=> get_stylesheet_directory() . '/woocommerce/wp-menu-cart/',
			'theme_template_path'		=> get_template_directory() . '/woocommerce/wp-menu-cart/',
			'plugin_template_path'		=> WPO_Menu_Cart_Pro()->plugin_path() . '/templates/',
		);

		$valid_extensions = array( '.html', '.php' );

		$filepath = '';
		foreach ( $template_locations as $template_path ) {
			foreach ( $valid_extensions as $extension ) {
				if( file_exists( $template_path . $template_slug . $extension ) ) {
					$filepath = $template_path . $template_slug . $extension;
					break 2;
				}
			}
		}
		
		return apply_filters( 'wpmenucart_custom_template_path', $filepath, $template_slug );
	}

	public function get_template_part($tag, $template, $return_type = 'with_tag', $all = false ) {
		// compose regex
		$regex = sprintf('#{{%1$s_start}}(.*?){{%1$s_end}}#s', $tag);

		// perform regex search
		if ($all) {
			$preg_match_return = preg_match_all($regex, $template, $preg_matches);
		} else {
			$preg_match_return = preg_match($regex, $template, $preg_matches);
		}

		// check if we have matches
		if ( $preg_match_return === false || $preg_match_return == 0 ) {
			return false;
		}

		// get match set with or without tags
		switch ($return_type) {
			case 'with_tag':
				$matches = $preg_matches[0];
				break;
			case 'without_tag':
				$matches = $preg_matches[1];
				break;
		}
		
		return $matches;
	}

	public function get_output () {
		// main menu item replacements
		$menu_item = $this->make_replacements($this->template_parts['main']);
		// echo "<pre>";var_dump($this->template_parts['main']);die();
		// echo "<pre>";var_dump($menu_item);die();

		if ( isset(WPO_Menu_Cart_Pro()->main_settings['flyout_display']) && !empty($this->template_parts['submenu']) ) {
			// submenu replacements
			$submenu = $this->make_replacements($this->template_parts['submenu']);

			if ( $submenu_item_data = WPO_Menu_Cart_Pro()->shop->submenu_items() ) {
				// submenu item replacements (the piece the resistence)
				$rendered_submenu_items = array();
				$i = 0;
				foreach ($submenu_item_data as $item_data) {
					$rendered_submenu_items[] = $this->make_replacements($this->template_parts['submenu_items'], $item_data);

					// limit number of items in flyout
					$flyout_itemnumber = apply_filters( 'wpmenucart_flyout_itemnumber', WPO_Menu_Cart_Pro()->main_settings['flyout_itemnumber'] );
					if ( $flyout_itemnumber > 0 ) {
						if (++$i == $flyout_itemnumber ) break; //stop at set number
					}
				}
				// legacy filter
				$rendered_submenu_items = apply_filters('wpmenucart_submenu_items', implode( "\n", $rendered_submenu_items ) );

				// insert items into submenu
				$submenu = str_replace( '{{items}}', $rendered_submenu_items, $submenu );

				// remove empty cart placeholders
				foreach ($this->template_parts['empty_cart'] as $tag => $match) {
					$submenu = str_replace( $tag, '', $submenu );
				}

				// render full cart placeholders
				foreach ($this->template_parts['full_cart'] as $tag => $match) {
					// + legacy filter
					$replacement = apply_filters('wpmenucart_cart_link_item', $this->make_replacements($match) );
					$submenu = str_replace( $tag, $replacement, $submenu );
				}
			} else {
				// no items
				$submenu = str_replace( '{{items}}', '', $submenu );

				// remove full cart placeholders
				foreach ($this->template_parts['full_cart'] as $tag => $match) {
					$submenu = str_replace( $tag, '', $submenu );
				}

				// render empty cart placeholders
				foreach ($this->template_parts['empty_cart'] as $tag => $match) {
					$replacement = $this->make_replacements($match);
					$submenu = str_replace( $tag, $replacement, $submenu );
				}				
			}

			// insert submenu into main menu
			$menu_item = str_replace( '{{submenu}}', $submenu, $menu_item);
		} else {
			// no submenu => remove placeholder
			$menu_item = str_replace( '{{submenu}}', '', $menu_item);
		}

		// replace start and end tags
		$start_and_end_tags = array( 'main_li_content', 'main_a', 'submenu', 'submenu_items', 'empty_cart', 'full_cart' );
		foreach ($start_and_end_tags as $tag) {
			$menu_item = str_replace("{{{$tag}_start}}", apply_filters( "wpmenucart_{$tag}_start", '' ), $menu_item);
			$menu_item = str_replace("{{{$tag}_end}}", apply_filters( "wpmenucart_{$tag}_end", '' ), $menu_item);
		}

		return $menu_item;

	}

	public function make_replacements($template, $data = '') {
		$mcp_data = new WPO_Menu_Cart_Pro_Data( $this->nav_menu_items, $this->menu_slug );

		// get array of placeholders
		preg_match_all('#{{.*?}}#s', $template, $placeholders);
		$placeholders = array_shift($placeholders); // we only need the first match set

		// loop through placeholders and see if we have a matching function (to write data)
		foreach ($placeholders as $placeholder) {
			// dont_render is used for the outer tags when doing ajax (woocommerce fragments)
			if ( $placeholder == "{{{$this->dont_render}_start}}" || $placeholder == "{{{$this->dont_render}_end}}" ) {
				$template = str_replace($placeholder, '', $template);
				continue;
			}

			$replacement_method = trim( $placeholder, '{}' );
			if (method_exists($mcp_data, $replacement_method)) {
				$replacement = call_user_func( array( $mcp_data, $replacement_method ), $data );
				$template = str_replace($placeholder, $replacement, $template);
			}
		}

		return $template;
	}

	public function write_output () {
		echo $this->get_output;
	}
}


endif; // class_exists

