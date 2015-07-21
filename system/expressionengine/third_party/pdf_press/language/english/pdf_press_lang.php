<?php

$lang = array(

/* ----------------------------------------
/*  Required for MODULES page
/* ----------------------------------------*/

"pdf_press_module_name"			=> 	"PDF Press",

"pdf_press_module_description" 	=>	"Saves a template as a PDF, uses the dompdf library (https://github.com/dompdf/dompdf)",
//-----------------------------------------//

"requirement"					=> 	"Requirement",

"required"						=> 	"Required",

"present"						=> 	"Current",

"config_name"					=> 	"Configuration",

"value"							=> 	"Value",

"desc"							=> 	"Description",

"setting_override"				=> 	"You can override these settings by modifying 'dompdf_config.custom.inc.php' in the /third_party/pdf_press folder.",

"preview"						=> "PDF Preview",

"index"							=> "Configuration",

"settings"						=> "Setting Presets",

"preview_desc"					=> "Enter a template path (ex. 'site/test') to test how your template will look as a PDF.",

"error_check_markup"			=> "PDF Press: There was an error in the DOMPDF Library, usually this means your HTML/CSS is not compliant in some way, please check your HTML/CSS markup using the W3C Validator (<a href='http://validator.w3.org' target='_blank'>http://validator.w3.org</a>).",

"dompdf_error"					=> "Error Message: ",

"error_curl_fopen"				=> "Both allow_url_fopen and CURL are disabled on the server. Please contact your server administrator to enabled either CURL or set 'allow_url_fopen = true' in php.ini.",

"encrypt_description"			=> "You can set encryption settings & permissions for your PDFs below",

"key"							=> "Preset Short Name",

"data"							=> "Preset Setting Data",

"attachment"					=> "Render Type",

"size"							=> "Size",

"orientation"					=> "Orientation",

"filename"						=> "Filename",

"encrypt"						=> "Encryption",

"userpass"						=> "User password",

"ownerpass"						=> "Owner password",

"can_print"						=> "Print Enabled?",

"can_modify"					=> "Editable?",

"can_copy"						=> "Can Copy?",

"can_add"						=> "Can Add to PDF?",

"no_setting_found"				=> "PDF Press: No setting presets found for the provided 'key' parameter.",

"setting_form_error"			=> "PDF Press<br/>Could not save the setting presets. There are errors in the form.",

"fonts"							=> "Fonts / Unicode",

"font_install_help"				=> "In order to use Unicode, you must upload a Unicode font before you set the font-family in the CSS. You can also upload other fonts to use in your PDF document.",

"setting_delete_success"		=> "Setting deleted!",

/* END */
''=>''
);
?>