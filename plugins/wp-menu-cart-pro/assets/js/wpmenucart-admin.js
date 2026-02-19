jQuery( function( $ ) {
	// tabbed interface for multilanguage
	$( ".translations" ).tabs();

	// 'add menu' button
	$('#add_wpmenucart_menu').click(function( event ) {
		event.preventDefault();
		var last_select = $(this).closest('td').find('select:last');
		var last_select_id_no = Number($(last_select).attr('id').replace(/\D/g,''));
		var new_id = 'menu_slugs['+String(last_select_id_no+1)+']';
		var new_name = 'wpo_wpmenucart_main_settings[menu_slugs]['+String(last_select_id_no+1)+']';
		var clone = $(last_select).clone().attr('id', new_id).attr('name', new_name).val([]).insertBefore('#add_wpmenucart_menu');
		$('<br />').insertBefore('#add_wpmenucart_menu');
	});

	$('#wpo-wpmenucart-settings select').change( function( event ) {
		$custom_wrapper = $(this).next('.custom');
		if ($custom_wrapper.length != 0) {
			if ( $(this).val() == 'custom' ) {
				$custom_wrapper.show().find('input').prop('disabled', false);
			} else {
				$custom_wrapper.find('input').val('').prop('disabled', true);
				$custom_wrapper.hide();
			}
		}
	}).change();
});
