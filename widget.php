<?php
/**
 * @package IDXPro
 */
function widget_QuickSearch() {

    echo '<script type="text/javascript" src="' . idxpro_get_quick_search_widget_url() . '"></script>';


}
 
function QuickSearch_init()
{
  if( function_exists('wp_register_sidebar_widget') )
  {
     wp_register_sidebar_widget( 'widget_QuickSearch', __('IDXPro Quick Search Widget'), 'widget_QuickSearch');
  }
  else
  {
    register_sidebar_widget(__('IDXPro Quick Search Widget'), 'widget_QuickSearch');
  }
}
add_action("plugins_loaded", "QuickSearch_init");


?>
