<?php 

/*
	sets up a custom button in the tinymce editor
	inserts a shortcode for idxpro
*/
$idxpro_account;
idxpro_get_account();


// wrapper - sets filters for when to add tinymce plugin and register new button
function idxpro_add_tinymce_button() {

	// Don't bother doing this stuff if the current user lacks permissions
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
		return;
	
	// Add only in Rich Editor mode
	if ( get_user_option('rich_editing') == 'true') {
		
		add_filter("mce_external_plugins", "idxpro_add_tinymce_plugin");
		add_filter('mce_buttons', 'idxpro_register_tinymce_button');
	}
}
add_action('init', 'idxpro_add_tinymce_button');


// registers the button
function idxpro_register_tinymce_button($buttons) {
	
	array_push($buttons, "|", "idxpro_embed");
	//array_unshift($buttons, 'idxpro_embed', '|');
	
	return $buttons;
}
 
// loads the tinymce plugin [editor_plugin.js]
function idxpro_add_tinymce_plugin($plugin_array) {
	global $idxpro_account;
	// setup global javascript var for idxpro_account
	
	if ( ! empty($idxpro_account) ) {
		echo "<script type='text/javascript'>idxpro_account_id = '" . $idxpro_account['id'] . "';</script>";
	}
	
	//get widgets
	echo '<script type="text/javascript">
			var idx_widget_buttons = [];';
	if( ! empty( $idxpro_account ) && ! empty( $idxpro_account['widgets'] ) ) {
	    $i = 0;
	    foreach( $idxpro_account['widgets'] as $k => $w ) {
	        echo "idx_widget_buttons[" . $i . "] = { title: '" . addslashes( $w ) . "', value : \"" . $k . "\" };";
	        $i++;
	    }
	} else {
	    echo "idx_widget_buttons[0] = { title: 'Embed IDXPro Application', value : \"[idxpro]\"};";
	    echo "idx_widget_buttons[1] = { title: 'Embed IDXPro Widget', value : \"[idxpro widget='quick search']\"};";
	}
	echo '</script>';
	
	//echo $idxpro_account['id'];
	
	$plugin_array['idxpro_embed'] = IDXPRO_PLUGIN_URL . 'tinymce/editor_plugin.js';
	
	return $plugin_array;
}
 
// and last, TinyMCE defaults to caching everything, so in order for our changes to show up, 
// we have to trick it into thinking its version number has changed
function idxpro_change_tinymce_version($version) {

	return ++$version;
}
add_filter('tiny_mce_version', 'idxpro_change_tinymce_version');





?>