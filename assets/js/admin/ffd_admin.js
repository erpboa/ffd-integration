( function( $, ffd_admin ) {
	$( function() {

		$('#ffd-admin-settings').on('submit', function(){

			var $form = $(this);
			$form.find('td.forminp-map_fields [type=checkbox]:not(:checked)').each(function(){

					$(this).closest('tr').remove();

			});

		});

		$('#propertybase_toggle_enabled td label').on('click', function(){

			var $label = $(this);
			var $form = $('#ffd-admin-settings');
			if( !$label.hasClass('hide') ){
				$form.find('td.forminp-map_fields [type=checkbox]:not(:checked)').each(function(){

						$(this).closest('tr').hide();

				});
				$label.addClass('hide');
			} else {
				$form.find('td.forminp-map_fields [type=checkbox]:not(:checked)').each(function(){
					$(this).closest('tr').show();
				});
				$label.removeClass('hide');
			}

		});

		/*TRESTLE*/
		$('#trestle_toggle_enabled td label').on('click', function(){

			var $label = $(this);
			var $form = $('#ffd-admin-settings');
			if( !$label.hasClass('hide') ){
				$form.find('td.forminp-map_fields [type=checkbox]:not(:checked)').each(function(){

					$(this).closest('tr').hide();

				});
				$label.addClass('hide');
			} else {
				$form.find('td.forminp-map_fields [type=checkbox]:not(:checked)').each(function(){
					$(this).closest('tr').show();
				});
				$label.removeClass('hide');
			}

		});
		/**/


		$('#ffd_propertybase_edit_keys').on('click', function(){
			var checked = $(this).prop('checked');
			var $form = $('#ffd-admin-settings');

			$form.find('td.forminp-map_fields [type=text]').prop('readonly', !checked);

		});

		/*TRESTLE*/
		$('#ffd_trestle_edit_keys').on('click', function(){
			var checked = $(this).prop('checked');
			var $form = $('#ffd-admin-settings');

			$form.find('td.forminp-map_fields [type=text]').prop('readonly', !checked);

		});
		/**/


		$('#ffd_createupdate_listing_data').on('click', function(e){
			e.preventDefault();
			var $button = $(this);
			if( !$button.hasClass('loading') ){
				$button.addClass('loading');
				$.ajax({
					type: "post",
					url: ajaxurl,
					data: {action:'ffd/integration/createdata'},
					dataType: "json",
					success: function (response) {
						$button.removeClass('loading');
					},
					error:function(){
						$button.removeClass('loading');
					}
				});
			}
		});

		$('#ffd_delete_pbtowpsync_data').on('click', function(e){
			e.preventDefault();
			var $button = $(this);
				$button.prop('disabled', true);
			if( !$button.hasClass('loading') ){
				$button.addClass('loading');
				$.ajax({
					type: "post",
					url: ajaxurl,
					data: {action:'ffd/integration/deletepbtowpsyncdata'},
					dataType: "json",
					success: function (response) {
						$button.removeClass('loading');
						$button.text(response.message);
					},
					error:function(){
						$button.removeClass('loading');
					}
				});
			}
		});

		/*TRESTLE*/
		$('#ffd_delete_trestletowpsync_data').on('click', function(e){
			e.preventDefault();
			var $button = $(this);
			$button.prop('disabled', true);
			if( !$button.hasClass('loading') ){
				$button.addClass('loading');
				$.ajax({
					type: "post",
					url: ajaxurl,
					data: {action:'ffd/integration/deletetrestletowpsyncdata'},
					dataType: "json",
					success: function (response) {
						$button.removeClass('loading');
						$button.text(response.message);
					},
					error:function(){
						$button.removeClass('loading');
					}
				});
			}
		});
		/**/

		

		//allow only one sync to be checked.
		$('#ffd-admin-settings').on('change', '#ffd_propertybasetowp_sync', function(){
			
			if( $(this).prop('checked') ){
				$('#ffd_wptopropertybase_sync').prop('checked', false);
				$('#ffd_propertybase_test_api').closest('tr').show();
			} else {
				$('#ffd_propertybase_test_api').closest('tr').hide();
			}
			
			
			
			


		});

		/*TRESTLE*/
		$('#ffd-admin-settings').on('change', '#ffd_trestletowp_sync', function(){

			if( $(this).prop('checked') ){
				$('#ffd_wptotrestle_sync').prop('checked', false);
				$('#ffd_trestle_test_api').closest('tr').show();
			} else {
				$('#ffd_trestle_test_api').closest('tr').hide();
			}






		});
		/**/

		$('#ffd-admin-settings').on('change', '#ffd_wptopropertybase_sync', function(){
		

			if( $(this).prop('checked') ){
				$('#ffd_propertybasetowp_sync').prop('checked', false);
			}
		});

		/*TRESTLE*/
		$('#ffd-admin-settings').on('change', '#ffd_wptotrestle_sync', function(){


			if( $(this).prop('checked') ){
				$('#ffd_trestletowp_sync').prop('checked', false);
			}
		});
		/**/

		
	});
})( jQuery, ffd_admin );