<?php

/**
 * User - Language
 *
 * @package		Solspace:User
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/user
 * @license		http://www.solspace.com/license_agreement
 * @version		3.5.0
 * @filesource	user/language/english/lang.user.php
 */

$lang = array(

//----------------------------------------
//	Required for MODULES page
//----------------------------------------

"user_module_name" =>
"User",

"user_module_description"		=>
"Create powerful and flexible member management areas using regular EE templates.",

//----------------------------------------
//	Language for Content Wrapper
//----------------------------------------

"user"			 =>
"User",

"documentation"	 =>
"Online Documentation",

"online_documentation"	 =>
"Online Documentation",

'user_preference' =>
"Preference",

'user_setting' =>
"Setting",

//----------------------------------------
//	Language for home
//----------------------------------------

"homepage"		 =>
"Homepage",

//----------------------------------------
//	Language for Upgrade
//----------------------------------------

"upgrade"		 =>
"Upgrade",

"upgrade_now"	 =>
"Upgrade now!",

"upgrade_message" =>
"It looks like you have installed a new version of User. We recommend that you run the upgrade script",

"update_successful"			=>
"The module was successfully updated.",

"update_failure"			=>
"There was an error while trying to update your module to the latest version.",

//----------------------------------------
//	Language for find member
//----------------------------------------

"reassign_ownership"			=>
"Reassign Ownership",

"find_member"	 =>
"Find Member",

'member_search' =>
"Member Search",

"find_members_text" =>
"Enter a username, screen name or email address below to find a member.",

'find_entries_text_channel'	=>
"Search for entries, NOT assigned to the above member, based on title and channel to reassign to selected member.",

"name"			 =>
"Name",

'member_keywords' =>
"Member Search",

'entry_title_keywords' =>
"Entry Title",

'choose_member' =>
"Choose Member",

'assign_to_member' =>
"Assign to Member",

'channels' =>
"Channels",

"missing_name"	 =>
"Please submit a username, screen name or email address.",

"no_results"	 =>
"Your search returned no results. Please try again.",

'choose_entries' =>
"Choose Entries",

"submit"		 =>
"Submit",

"no_results_channel" =>
"Your search returned no results. Please try again.",

//----------------------------------------
//	Language for find entries
//----------------------------------------

"select"		 =>
"Select",

"username"		 =>
"Username",

"screen_name"	 =>
"Screen Name",

"email"			 =>
"Email",

"entries"		 =>
"Entries",

"find_entries"	 =>
"Find Entries",

"title"			 =>
"Title",

"entry_id"		 =>
"Entry ID",

"entry_date"	 =>
"Entry Date",

"missing_title"	 =>
"Please provide a title for your search.",

"missing_member" =>
"Please select a member from the list.",

"submit_name"	 =>
"Assign selected entries to %name.",

"no_entries_selected"			=>
"No entries selected.",

"reassign_ownership_confirm"	=>
"Confirm Ownership Change",

"reassign_ownership_question_entry"	=>
"Are you sure that you want to reassign the ownership of %i% entries to %name%?",

"reassign_ownership_question_entries"	=>
"Are you sure that you want to reassign the ownership of %i% entries to %name%?",

"reassign"		 =>
"Reassign",

"missing_member_id" =>
"The member to whom entries will be reassigned is missing.",

"entry_reassigned" =>
"1 entry was successfully reassigned.",

"entries_reassigned"			=>
"%i% entries were successfully reassigned.",

//----------------------------------------
//	Language for prefs
//----------------------------------------

"preferences"	 =>
"Preferences",

"general_preferences"	 =>
"General Preferences",

"multiple_authors"	 =>
"Multiple Authors",

"email_notifications"	 =>
"Email Notifications",

"email_is_username" =>
"Email is Username",

"email_is_username_subtext"		=>
"Set this field to yes in order to force usernames to inherit from the user's email address.",

"category_groups" =>
"Category Groups",

"category_groups_subtext"			=>
"Select the category groups on the right to indicate which categories users can be assigned to when using the User Categories feature.",

"missing_username" =>
"Please provide a username.",

"username_change_not_allowed"	=>
"Your site is not set to allow username changes. The 'email is username' rule is not compatible with your current settings. Users must be allowed to change their usernames if their email address and username are considered the same on the site.",

"screen_name_override"			=>
"Screen Name Override",

"screen_name_override_subtext"		=>
"Indicate which custom member fields should be used to create the screen name. Separate fields with the pipe '|' character. Leaving this field blank will cause the screen name override function to be ignored.",

"user_preferences_updated" =>
"Your preferences have been updated.",

'welcome_email_subject'	 =>
"Welcome Email Subject",

'welcome_email_content'	 =>
"Welcome Email",

'welcome_email_content_subtext'	 =>
"Sends out a customized welcome email to the registered user when their registration is complete. If you do not want to have welcome emails sent, leave this area blank.<br /><br />Available variables are: <b>{site_name}</b>, <b>{site_url}</b>, <b>{screen_name}</b>, <b>{email}</b>, <b>{username}</b>, <b>{member_id}</b>.",

'member_update'	 =>
"Member Update",

'member_update_admin_notification_emails' =>
"Email(s) for Admin Notification on Profile Update",

'member_update_admin_notification_template' =>
"Admin Notify of Profile Update Email Message",

'member_update_admin_notification_template_subtext' =>
"Sends out a customized notification email to the email address(es) specified in the field above. All member variables are available, plus the <b>{changed}{/changed}</b> variable pair which enables the <b>{name}</b> (of the field) and <b>{value}</b> (the new value) variables. The {changed} variable pair will display the new values of the fields modified. Also available is the <b>{update_date}</b> variable and date formatting.",

//----------------------------------------
//	Language for Edit
//----------------------------------------

'user_successful_submission' =>
"Successful Submission!",

"not_authorized" =>
"You are not authorized to perform this action.",

"cant_find_member" =>
"We were unable to find the specified member.",

"incorrect_language"			=>
"An incorrect language was selected.",

"super_admin_group_id"			=>
"You're kidding right? You really want to change your member group status from Super Admin to something else? We just can't let you do that here. You'll have to go into the EE CP.",

"current_password_required"		=>
"In order to make a password change, your current password is required.",

"current_password_required_email"		=>
"In order to make an email address change, your current password is required.",

"username_change_not_allowed"	=>
"This website does not permit username changes.",

"missing_fields" =>
"The following fields are required: <ul><li>%fields%</li></ul>",

"avatar_uploads_not_enabled"	=>
"Avatar uploads are not currently enabled.",

"photo_uploads_not_enabled"		=>
"Member photo uploads are not currently enabled.",

"sig_uploads_not_enabled"		=>
"Signature image uploads are not currently enabled.",

"gd_required"	 =>
"The GD image library is required for this action. Please contact customer support.",

"image_max_size_exceeded"		=>
"The maximum filesize for image uploads is %s. Please go back and choose a different file to upload.",

"missing_upload_path"			=>
"The upload path for this type of image is missing or incorrect. Please contact customer support.",

"invalid_image_type"			=>
"The type of image you are attempting to upload is not an allowed type.",

"password_changed" =>
"You have changed your password. You will need to log back in with your new password.",

"us"			 =>
"United States",

"eu"			 =>
"European",

"success"		 =>
"Success!",

"return"		 =>
"Return",

//----------------------------------------
//	Language for group edit
//----------------------------------------

"incorrect_editable_group"		=>
"You incorrectly indicated the editable groups for this function.",

"no_data"		 =>
"No data was sent.",

"member_list_error" =>
"There was an error in the list of members provided.",

'member_group_updated' =>
"The Member Group was updated successfully.",

//----------------------------------------
//	Language for Register
//----------------------------------------

"registration_not_enabled"		=>
"Registrations are currently not allowed.",

"mbr_you_are_registered"		=>
"You are already registered and logged in.",

"prohibited_username" =>
"The username you provided is prohibited in the system. Please choose a different username.",

"email_required" =>
"An email address is required in order to register.",

"user_field_required"			=>
"The following are required member fields:<ul>%s</ul>",

"captcha_required" =>
"A captcha image is required in order to register.",

"mbr_terms_of_service_required"	=>
"Acceptance of the Terms of Service is required for registration.",

"banned_screen_name"			=>
"A screen name was created for you using these values: %s. That screen name is banned on this site. You will either need to alter the contents of one of the values you provided or contact the site administrator for help.",

"bad_screen_name" =>
"A screen name was created for you using these values: %s. Screen names must be unique. You will either need to alter the contents of one of the values you provided or contact the site administrator for help.",

"banned_username" =>
"The username you submitted has been banned from the site.",

"invalid_member_group"			=>
"The indicated member group is invalid. Please contact customer support.",

"wrong_reg_mode" =>
"The form you submitted allows you to register for the site at the time of your submission. However, the site does not allow for immediate registrations. Please contact the webmaster and let them know about this error message.",



//----------------------------------------
//	Language for Keys
//----------------------------------------

"back"			 =>
"Back",

"key_created"	 =>
"Key Created",

"key_expiration"	 =>
"Key Expiration",

"key_expiration_subtext"	 =>
"Number of days after sending a person a key/inviation that it expires, default is 7 days.",

"key_success"	 =>
"The Registration Keys were successfully created and emailed.",

"key_required"	 =>
"A valid invitation key is required before joining this website. Please obtain a key.",

"key_incorrect"	 =>
"The invitation key that you entered is not valid. Invitation keys expire after %s day(s). Please try again.",

"key_email_match_required"		=>
"The email address submitted did not match the key to which it was matched. Please go back and try again.",

"template_not_found"			=>
"The specified invitation template could not be found.",

"you_are_invited_to_join"=>
"You are invited to join",

//----------------------------------------
//	Language for Search
//----------------------------------------

"search_path_error" =>
"There was template specified to show search results.",

"search_not_allowed"			=>
"You are not currently allowed to search.",

"search_time_not_expired"		=>
"You are only allowed to search every %x seconds.",

"page"			 =>
"Page",

"of"			 =>
"of",


//----------------------------------------
//	Language for Delete Account
//----------------------------------------

'user_delete_confirm'			=>
"Please confirm that you want to permanently delete this account and all associated content.",

//----------------------------------------
//	Forgotten Username Email
//----------------------------------------

'forgotten_username_sent' =>
"An email with your forgotten username has been sent.",

'forgotten_username_subject' =>
"Forgotten Username",

'user_forgot_username_message' =>
"Forgot Username Email Message",

'user_forgot_username_message_subtext' =>
"Sends out a customized email with username details to the user that requested their username details to be sent via the User:Forgot_Username function.<br /><br />Available variables are: <b>{site_name}</b>, <b>{site_url}</b>, <b>{screen_name}</b>, <b>{email}</b>, <b>{username}</b>, <b>{member_id}</b>.",

//----------------------------------------
//	Language for Tab
//----------------------------------------

"no_matching_authors"			=>
"No matching authors were found.",

"no_author_id"	 =>
"No author id was provided.",

"author_not_assigned"			=>
"The author was not assigned to this entry.",

"successful_add" =>
"The author was added successfully.",

'remove' =>
"Remove",

'add' =>
"Add",

'loading_users' =>
'Loading User List',

/** --------------------------------------------
/**  Errors
/** --------------------------------------------*/

'disable_module_to_disable_extension' =>
"To disable this extension, you must disable its corresponding <a href='%url%'>module</a>.",

'enable_module_to_enable_extension' =>
"To enable this extension, you must install its corresponding <a href='%url%'>module</a>.",

'cp_jquery_requred' =>
"The 'jQuery for the Control Panel' extension must be <a href='%extensions_url%'>enabled</a> to use this module.",

'could_not_send_reset_email' =>
"Could Not Send Reset Email: Unknown Error.",

/** --------------------------------------------
/**  Update Routine
/** --------------------------------------------*/

'update_user_module' =>
"Update the User Module",

'user_update_message' =>
"You have recently uploaded a new version of User, please click here to run the update script.",

'user_authors_publish_tab_label' =>
'Publish Tab Label',

'user_authors_instructions'		=>
'You can customize the label of the user authors tab per channel using the fields below.
If you prefer not to show the tab for a channel, leave the respective field blank.',

//----------------------------------------
//	Language for Tab
//----------------------------------------

"browse_authors_instructions"	=>
"Browse for authors using the field. Click an author's name to add them to the entry.",

"choose_author_instructions"	=>
"Choose a authors from the user list.",

"assigned_authors_instructions"	=>
"Indicate the primary author using the radio button.",

"browse_authors" =>
"Browse Authors",

"assigned_authors" =>
"Assigned Authors",

"primary_author" =>
"Primary Author",

'choose_a_primary_author' =>
"Choose a Primary Author",

"user_authors_confirm_delete" =>
"Are you sure that you want to remove this person as one of the authors of the entry?",

'remove' =>
"Remove",

'add' =>
"Add",

'user_module_version' =>
"User",

// -------------------------------------
//	demo install (code pack)
// -------------------------------------

'demo_description' =>
'These demonstration templates will help you understand better how the Solspace User Addon works.',

'template_group_prefix' =>
'Template Group Prefix',

'template_group_prefix_desc' =>
'Each Template group and global variable installed will be prefixed with this variable in order to prevent colission.',

'groups_and_templates' =>
"Groups and Templates to be installed",

'groups_and_templates_desc' =>
"These template groups and their accompanying templates will be installed into your ExpressionEngine installation.",

'screenshot' =>
'Screenshot',

'install_demo_templates' =>
'Install Demo Templates',

'prefix_error' =>
'Prefixes, which are used for template groups, may only contain alpha-numeric characters, underscores, and dashes.',

'demo_templates' =>
'Demo Templates',

//errors
'ee_not_running'				=>
'ExpressionEngine 2.x does not appear to be running.',

'invalid_code_pack_path'		=>
'Invalid Code Pack Path',

'invalid_code_pack_path_exp'	=>
'No valid codepack found at \'%path%\'.',

'missing_code_pack'				=>
'Code Pack missing',

'missing_code_pack_exp'			=>
'You have chosen no code pack to install.',

'missing_prefix'				=>
'Prefix needed',

'missing_prefix_exp'			=>
'Please provide a prefix for the sample templates and data that will be created.',

'invalid_prefix'				=>
'Invalid prefix',

'invalid_prefix_exp'			=>
'The prefix you provided was not valid.',

'missing_theme_html'			=>
'Missing folder',

'missing_theme_html_exp'		=>
'There should be a folder called \'html\' inside your site\'s \'/themes/solspace_themes/code_pack/%code_pack_name%\' folder. Make sure that it is in place and that it contains additional folders that represent the template groups that will be created by this code pack.',

'missing_codepack_legacy'		=>
'Missing the CodePackLegacy library needed to install this legacy codepack.',

//@deprecated
'missing_code_pack_theme'		=>
'Code Pack Theme missing',

'missing_code_pack_theme_exp'	=>
'There should be at least one theme folder inside the folder \'%code_pack_name%\' located inside \'/themes/code_pack/\'. A theme is required to proceed.',

//conflicts
'conflicting_group_names'		=>
'Conflicting template group names',

'conflicting_group_names_exp'	=>
'The following template group names already exist. Please choose a different prefix in order to avoid conflicts. %conflicting_groups%',

'conflicting_global_var_names'	=>
'Conflicting global variable names.',

'conflicting_global_var_names_exp' =>
'There were conflicts between global variables on your site and global variables in this code pack. Consider changing your prefix to resolve the following conflicts. %conflicting_global_vars%',

//success messages
'global_vars_added'				=>
'Global variables added',

'global_vars_added_exp'			=>
'The following global template variables were successfully added. %global_vars%',

'templates_added'				=>
'Templates were added',

'templates_added_exp'			=>
'%template_count% templates were successfully added to your site as part of this code pack.',

"home_page"						=>"Home Page",
"home_page_exp"					=> "View the home page for this code pack here: %link%",


// END
''=>''
);

//legacy timezones
$lang['UM12']	= '(UTC -12:00) Baker/Howland Island';
$lang['UM11']	= '(UTC -11:00) Samoa Time Zone, Niue';
$lang['UM10']	= '(UTC -10:00) Hawaii-Aleutian Standard Time, Cook Islands, Tahiti';
$lang['UM95']	= '(UTC -9:30) Marquesas Islands';
$lang['UM9']	= '(UTC -9:00) Alaska Standard Time, Gambier Islands';
$lang['UM8']	= '(UTC -8:00) Pacific Standard Time, Clipperton Island';
$lang['UM7']	= '(UTC -7:00) Mountain Standard Time';
$lang['UM6']	= '(UTC -6:00) Central Standard Time';
$lang['UM5']	= '(UTC -5:00) Eastern Standard Time, Western Caribbean Standard Time';
$lang['UM45']	= '(UTC -4:30) Venezuelan Standard Time';
$lang['UM4']	= '(UTC -4:00) Atlantic Standard Time, Eastern Caribbean Standard Time';
$lang['UM35']	= '(UTC -3:30) Newfoundland Standard Time';
$lang['UM3']	= '(UTC -3:00) Argentina, Brazil, French Guiana, Uruguay';
$lang['UM2']	= '(UTC -2:00) South Georgia/South Sandwich Islands';
$lang['UM1']	= '(UTC -1:00) Azores, Cape Verde Islands';
$lang['UTC']	= '(UTC) Greenwich Mean Time, Western European Time';
$lang['UP1']	= '(UTC +1:00) Central European Time, West Africa Time';
$lang['UP2']	= '(UTC +2:00) Central Africa Time, Eastern European Time, Kaliningrad Time';
$lang['UP3']	= '(UTC +3:00) Moscow Time, East Africa Time';
$lang['UP35']	= '(UTC +3:30) Iran Standard Time';
$lang['UP4']	= '(UTC +4:00) Azerbaijan Standard Time, Samara Time';
$lang['UP45']	= '(UTC +4:30) Afghanistan';
$lang['UP5']	= '(UTC +5:00) Pakistan Standard Time, Yekaterinburg Time';
$lang['UP55']	= '(UTC +5:30) Indian Standard Time, Sri Lanka Time';
$lang['UP575']	= '(UTC +5:45) Nepal Time';
$lang['UP6']	= '(UTC +6:00) Bangladesh Standard Time, Bhutan Time, Omsk Time';
$lang['UP65']	= '(UTC +6:30) Cocos Islands, Myanmar';
$lang['UP7']	= '(UTC +7:00) Krasnoyarsk Time, Cambodia, Laos, Thailand, Vietnam';
$lang['UP8']	= '(UTC +8:00) Australian Western Standard Time, Beijing Time, Irkutsk Time';
$lang['UP875']	= '(UTC +8:45) Australian Central Western Standard Time';
$lang['UP9']	= '(UTC +9:00) Japan Standard Time, Korea Standard Time, Yakutsk Time';
$lang['UP95']	= '(UTC +9:30) Australian Central Standard Time';
$lang['UP10']	= '(UTC +10:00) Australian Eastern Standard Time, Vladivostok Time';
$lang['UP105']	= '(UTC +10:30) Lord Howe Island';
$lang['UP11']	= '(UTC +11:00) Magadan Time, Solomon Islands, Vanuatu';
$lang['UP115']	= '(UTC +11:30) Norfolk Island';
$lang['UP12']	= '(UTC +12:00) Fiji, Gilbert Islands, Kamchatka Time, New Zealand Standard Time';
$lang['UP1275']	= '(UTC +12:45) Chatham Islands Standard Time';
$lang['UP13']	= '(UTC +13:00) Phoenix Islands Time, Tonga';
$lang['UP14']	= '(UTC +14:00) Line Islands';