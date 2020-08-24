( function( $ ) {
    
    $( document ).on( "click",".gform_delete", function() {
		$('.tingle-modal-box .image-preview').hide();
	} );

	$( document ).on( "ready", function() {
		if ($('.gform_wrapper').length === 0) {
			return;
		}
		setupImages();
	} );
	
	function setupImages() {
		$('.image-preview').each(function(index) {
			$(this).closest('.gfield').find('.gfield_description').hide();
			$(this).closest('.gfield').find('.ginput_container').hide();
		});
	}
	
	window.gformDeleteUploadedFileEventIntake = function(form_id, field_id, el) {
		var $field = $('#field_'+form_id+'_'+field_id);
		if ($field.length === 0) {
			return;
		}

		$field.find('.gfield_description').show();
		$field.find('.ginput_container').show();
		$field.find('.ginput_preview').hide();
		$field.find('.image-preview').hide();
	}
    
} )( jQuery )