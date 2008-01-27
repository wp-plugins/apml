jQuery(document).ready( function() {
	// setup action
	jQuery('#apml_tags').hide();
	jQuery('#apml_more_link').click( function() {
		jQuery('#apml_tags').toggle(200); 
		return false;
	});
});