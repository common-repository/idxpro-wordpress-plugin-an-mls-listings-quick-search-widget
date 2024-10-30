jQuery(document).ready(function () {
	
	// show form for editing existing account id.
	jQuery('a.idxpro-account-edit').click(function(event) {
		jQuery('#idxpro-conf').toggle();
		event.preventDefault();
		
	});
	
	//alert("idxpro script is running!");
	
});
