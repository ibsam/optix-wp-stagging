<?php
/**
 * Plugin specific settings callbacks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WPO_Settings_Callbacks_2' ) ) {
	include( 'class-wpo-settings-callbacks.php' );
}

if ( !class_exists( 'WPO_Menu_Cart_Pro_Settings_Callbacks' ) ) :

class WPO_Menu_Cart_Pro_Settings_Callbacks extends WPO_Settings_Callbacks_2 {
	function __construct()	{
	}

	# add plugin specific callback functions here

	/**
	 * Media upload callback.
	 *
	 * @param  array $args Field arguments.
	 *
	 * @return string	  Media upload button & preview.
	 */
	public function media_upload_callback( $args ) {
		extract( $this->normalize_settings_args( $args ) );

		if( !empty($current) ) {
			$attachment = wp_get_attachment_image_src( $current, 'thumbnail', false );
			
			$attachment_src = $attachment[0];
			$attachment_width = $attachment[1];
			$attachment_height = $attachment[2];
			
			printf('<img src="%1$s" style="display:block;margin-bottom:10px;max-width:150px;max-height:150px" id="img-%4$s"/>', $attachment_src, $attachment_width, $attachment_height, $id );
			printf('<span class="button wpo_remove_image_button" data-input_id="%1$s">%2$s</span>', $id, $remove_button_text );
		}

		printf( '<input id="%1$s" name="%2$s" type="hidden" value="%3$s" />', $id, $setting_name, $current );
		
		printf( '<span class="button wpo_upload_image_button %4$s" data-uploader_title="%1$s" data-uploader_button_text="%2$s" data-remove_button_text="%3$s" data-input_id="%4$s">%2$s</span>', $uploader_title, $uploader_button_text, $remove_button_text, $id );
	
		// Displays option description.
		if ( isset( $description ) ) {
			printf( '<p class="description">%s</p>', $description );
		}
	
	}

	/**
	 * Select element callback.
	 *
	 * @param  array $args Field arguments.
	 *
	 * @return string	  Select field.
	 */
	public function shop_select( $args ) {
		extract( $this->normalize_settings_args( $args ) );
	
		printf( '<select id="%1$s" name="%2$s">', $id, $setting_name );

		foreach ( $options as $key => $label ) {
			printf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $key );
		}

		echo '</select>';

		if (isset($custom)) {
			printf( '<div class="%1$s_custom custom">', $id );

			switch ($custom['type']) {
				case 'text_element_callback':
					$this->text_input( $custom['args'] );
					break;		
				case 'multiple_text_element_callback':
					$this->multiple_text_input( $custom['args'] );
					break;		
				default:
					break;
			}
			echo '</div>';
		}
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', $args['description'] );
		}

	}


	/**
	 * Displays a multiple selectbox for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function menus_select( $args ) {
		extract( $this->normalize_settings_args( $args ) );
		// echo "<pre>";var_dump($this->normalize_settings_args( $args ));die();
		$menu_array = $this->get_menu_array();

		$menu_count = apply_filters( 'wpmenucart_menu_count', count( (array) $current ) );
		if ($menu_count < 3) {
			$menu_count = 3;
		}

		$disabled = isset( $disabled ) ? ' disabled' : '';

		for ( $x = 1; $x <= $menu_count; $x++ ) {
			$current_menu = isset( $current[$x] ) ? $current[$x] : '';
			
			printf( '<select id="%1$s[%3$s]" name="%2$s[%3$s]" %4$s>', $id, $setting_name, $x, $disabled);
			printf( '<option value="%s"%s>%s</option>', '0', selected( $current_menu, '0', false ), '' );
			
			foreach ( (array) $menu_array as $key => $label ) {
				printf( '<option value="%s"%s>%s</option>', $key, selected( $current_menu, $key, false ), $label );
			}
			echo '</select>';
	
			if ( isset( $description ) ) {
				printf( '<p class="description">%s</p>', $description );
			}
			echo '<br />';
		}

		echo '<button class="button" id="add_wpmenucart_menu" style="margin-top:5px">'.__('Add menu', 'wp-menu-cart-pro').'</button>';
	}

	/**
	 * Displays a multicheckbox a settings field
	 *
	 * @param array   $args settings field args
	 *
	 * @return string icon select
	 */
	public function icons_radio_element_callback( $args ) {
		extract( $this->normalize_settings_args( $args ) );

		$icons = '';
		$radios = '';
		
		foreach ( $options as $key => $iconnumber ) {
			$icons .= sprintf( '<td style="padding-bottom:0;font-size:16pt;" align="center"><label for="%1$s[%2$s]"><i class="wpmenucart-icon-shopping-cart-%3$s"></i></label></td>', $id, $key, $iconnumber);
			$radios .= sprintf( '<td style="padding-top:0" align="center"><input type="radio" class="radio" id="%1$s[%2$s]" name="%3$s" value="%2$s"%4$s /></td>', $id, $key, $setting_name, checked( $current, $key, false ) );
		}

		$html = '<table><tr>'.$icons.'</tr><tr>'.$radios.'</tr></table>';
		$html .= '<p class="description"><i>'. __('<strong>Please note:</strong> you need to open your website in a new tab/browser window after updating the cart icon for the change to be visible!','wp-menu-cart-pro').'</p>';
		
		echo $html;
	}

	/**
	 * Get menu array.
	 * 
	 * @return array menu slug => menu name
	 */
	public function get_menu_array() {
		$menus = get_terms( 'nav_menu', array( 'hide_empty' => false ) );
		$menu_list = array();
	
		foreach ( $menus as $menu ) {
			$menu_list[$menu->slug] = $menu->name;
		}
		
		if (!empty($menu_list)) return $menu_list;
	}

}


endif; // class_exists

return new WPO_Menu_Cart_Pro_Settings_Callbacks();