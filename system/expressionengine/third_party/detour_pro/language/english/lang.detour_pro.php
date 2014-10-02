<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(	
	'detour_pro_module_name' => 'Detour Pro',
	'detour_pro_module_description' => 'Redirect Module',
	'module_home' => 'Detour Pro Home',
	
	// Table Titles
	'title_url' => 'URL string to match',
	'title_redirect' => 'Detour to',
	'title_method' => 'Method', 
	'title_hits' => 'Hits', 
	'title_setting' => 'Setting', 
	'title_value' => 'Value', 
	'title_start' => 'Start Date', 
	'title_end' => 'End Date', 
	
	// Form Labels
	'label_original_url' => 'Original URL', 
	'label_new_url' => 'Detour to', 
	'label_new_detour_method' => 'Redirect Method', 
	'label_start_date' => 'Start Date', 
	'label_end_date' => 'End Date', 
	'label_detour_owner' => 'Owner', 
	'label_detour_category' => 'Category', 
	'label_detour_method' => 'Redirect Method', 
	'label_clear_start_date' => 'Clear Start Date', 
	'label_clear_end_date' => 'Clear End Date', 
	'label_setting_url_detect' => 'Default URL detection',
	'label_setting_default_method' => 'Default Redirect Method',  
	
	// Form Subtext
	'subtext_original_url' => 'Example: path/to/page.html or old/ee/page<br />Does not need leading /', 
	'subtext_new_url' => 'Internal: path/to/new/page<br />External: http://www.example.com', 
	'subtext_new_detour_method' => 'Redirect method', 
	'subtext_start_date' => 'Optional: Date this detour will begin re-routing', 
	'subtext_end_date' => 'Optional: Date this detour will cease re-routing', 
	'subtext_detour_owner' => 'Optional: Detour owner', 
	'subtext_detour_category' => 'Optional: Detour category', 
	'subtext_detour_method' => 'Determines whether the redirect is permanent (301) or temporary (302)', 
	'subtext_clear_start_date' => 'Clears previously set start date', 
	'subtext_clear_end_date' => 'Clears previously set end date', 
	'subtext_setting_url_detect' => 'Recommended to use Expression Engine.  If your redirects are not working, you can try PHP', 
	'subtext_setting_default_method' => 'Typically you will want to use 301', 
	
	// Directions
	'dir_uri' => 'Example: path/to/page.html or old/ee/page<br />Does not need leading /', 
	'dir_detour' => 'Internal: path/to/new/page<br />External: http://www.example.com', 	
	
	// Options
	'option_detour' => 'Detour all urls, even urls with existing pages.',
	'option_ignore' => 'If a page exists, ignore detour.',
	
	// Buttons
	'btn_save_settings' => 'Save Settings', 
	'btn_save_detour' => 'Save Detour', 
	'btn_delete_detours' => 'Delete Selected Detours', 
	'btn_purge_detours' => 'Purge All Detour Hit Data', 
	
	// Settings
	'url_detect' => 'Default URL Detection Method', 
	'hit_counter' => 'Track each time a Detour is triggered?', 
);

/* End of file lang.detour_pro.php */
/* Location: /system/expressionengine/third_party/detour_pro/language/english/lang.detour_pro.php */
