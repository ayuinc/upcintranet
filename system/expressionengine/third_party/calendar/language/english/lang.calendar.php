<?php

/**
 * Calendar - Language
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/language/english/lang.calendar.php
 */

$lang = array(

//----------------------------------------
// Required for MODULES page
//----------------------------------------

'calendar_module_name' =>
'Calendar',

'calendar_module_description' =>
'A full-featured calendar module for ExpressionEngine',

'calendar_module_version' =>
'Calendar',

//----------------------------------------
//  Installation
//----------------------------------------

'calendars_field_group_already_exists' =>
'The field group "Calendar: Calendars" already exists.',

'events_field_group_already_exists' =>
'The field group "Calendar: Events" already exists.',

'calendars_weblog_already_exists' =>
'The channel "Calendar: Calendars" already exists.',

'events_weblog_already_exists' =>
'The channel "Calendar: Events" already exists.',

'cannot_install' =>
'Calendar cannot be installed until these errors are corrected:',

//----------------------------------------
//  Upgradeification
//----------------------------------------

'update_calendar' =>
'Update Calendar',

"update_successful"	=>
"The module was successfully updated.",

'calendar_update_message' =>
'A new version of Calendar is ready. Would you like to update?',

'update_failure' =>
'The Calendar module update was unsuccessful.',

'update_successful' =>
'The Calendar module update was successful.',

//----------------------------------------
//  Main Menu
//----------------------------------------

'calendars' =>
'Calendars',

'events' =>
'Events',

'occurrences' =>
'Occurrences',

'reminders' =>
'Reminders',

'permissions' =>
'Permissions',

'preferences' =>
'Preferences',

'documentation' =>
'Online Documentation',

'online_documentation' =>
'Online Documentation',

//----------------------------------------
//  Publish/Edit
//----------------------------------------

'filter' =>
'Filter',

'remove_edited_occurrences' =>
'Remove Edited Occurrences',

'remove_edited_occurrences_desc' =>
'Uncheck this if you are only editing details about  this event. It is recommended that you LEAVE THIS CHECKED if you are changing event TIMES, DATES, OR ENDING DATES, as not doing so will create orphan events.',

'create_calendar_first' =>
'You must first create a calendar before creating an event.',

'select_a_calendar' =>
'Select a calendar',

'new_date' =>
'New Date',

'add_rule' =>
'Add Rule',

'editing_occurrence' =>
'Editing the <strong>%date%</strong> occurrence of <strong>%title%</strong>',

'type' =>
'Type',

'include' =>
'Include',

'exclude' =>
'Exclude',

'repeat' =>
'Repeat',

'none' =>
'None',

'daily' =>
'Daily',

'weekly' =>
'Weekly',

'monthly' =>
'Monthly',

'yearly' =>
'Yearly',

'select_dates' =>
'Select Dates',

'all_day_event' =>
'All Day Event',

'from' =>
'From',

'to' =>
'To',

'every' =>
'Every',

'day_s' =>
'day(s)',

'week_s_on' =>
'week(s) on',

'at' =>
'at',

'month_s_by_day_of' =>
'month(s) by day of',

'1st' =>
'1st',

'2nd' =>
'2nd',

'3rd' =>
'3rd',

'4th' =>
'4th',

'5th' =>
'5th',

'only_on' =>
'Only on',

'year_s' =>
'year(s)',

'end' =>
'End',

'never' =>
'Never',

'by_date' =>
'by Date',

'after' =>
'After',

'time_s' =>
'time(s)',

//----------------------------------------
//  CP Calendars
//----------------------------------------

'calendar_id' =>
'Calendar ID',

'calendar_name' =>
'Calendar Name',

'status' =>
'Status',

'total_events' =>
'Total Events',

//----------------------------------------
//  CP Events
//----------------------------------------

'event_id' =>
'Event ID',

'event_name' =>
'Event Name',

'recurs' =>
'Recurs',

'first_date' =>
'First Date',

'last_date' =>
'Last Date',

'filter_events' =>
"Filter Events",

'filter_by_calendar' =>
'Filter by Calendar',

'filter_by_status' =>
'Filter by Status',

'filter_by_recurs' =>
'Filter by Recurs',

'order_by' =>
'Order by',

'date_is' =>
'Date is',

'event_id' =>
'Event ID',

'event_title' =>
'Event Title',

'calendar_name' =>
'Calendar Name',

'status' =>
'Status',

'recurs' =>
'Recurs',

'first_date_is' =>
'First Date Is',

'last_date' =>
'Last Date',

'ascending' =>
'Ascending',

'descending' =>
'Descending',

'or_later' =>
'Or Later',

'or_earlier' =>
'Or Earlier',

'this_date' =>
'This Date',

'time' =>
'Time',

'all_day' =>
'All Day',

//----------------------------------------
//  CP Events Delete
//----------------------------------------

'delete' =>
'Delete',

'delete_events' =>
'Delete Events',

'delete_events_title' =>
'Delete event(s)?',

'delete_events_question' =>
'Really delete {COUNT} event(s)?',

'events_deleted' =>
'Event(s) were deleted',

//----------------------------------------
//  CP Occurrences
//----------------------------------------

'occurrence_id' =>
'Occurrence ID',

'event_date' =>
'Event Date',

'limit' =>
'Limit',

'page_limit' =>
'Page Limit',

'occurrences_limit' =>
'Occurrences Limit',

// -------------------------------------
//	Permissions
// -------------------------------------

'calendar_permissions_desc' =>
"Permissions can be set at calendar creation time, or changed here. Super Admins and the groups selected in 'Allow All Full Access For Groups' have access to all calendars. Groups selected in 'Deny All Access For Groups' will not have access to any calendars.",

'allowed_groups' =>
"Allowed Groups",

'allow_full_access' =>
'Allow All Full Access For Groups',

'permissions_enabled' =>
'Permissions Enabled',

'save_permissions' =>
'Save Permissions',

'all_groups' =>
'All Groups',


'allow_all' =>
'Allow All',

'deny_all_access' =>
'Deny All Access For Groups',

'deny_takes_precedence' =>
"Takes precedence over calendar Allow All",

'permissions_saved' =>
"Permissions Saved",

'group_permissions' =>
'Group Permissions',

'permissions_instructions' =>
"Choose the groups that you want to have editing access to calendar and its events. (Super Admins always have access to all calendars). If a group is not shown, it is either in the Allow All or Deny All list in the permissions tab in the Calendar control panel.",

'disallowed_behavior_for_edit_page' =>
"Disallowed Behavior for Edit Page",

'none' =>
"None",

'search_filter' =>
"Search Filter",

'disable_link' =>
"Disable Link",

'permission_dialog_title' =>
"Permission Error",

'ok' => "OK",

//----------------------------------------
//  CP Preferences
//----------------------------------------

'preference' =>
'Preference',

'setting' =>
'Setting',

'description' =>
'Description',

'first_day_of_week' =>
'First Day of the Week',

'first_day_of_week_description' =>
'Sunday and Monday are the most likely choices.',

'clock_type' =>
'Clock Type',

'clock_type_description' =>
'Use 12-hour or 24-hour clock in control panel?',

'12_hour' =>
'12-hour',

'24_hour' =>
'24-hour',

'default_timezone' =>
'Default Timezone',

'default_timezone_description' =>
'Default timezone for new calendars.',

'preferences_updated' =>
'Preferences updated.',

'default_date_format' =>
'Datepicker Date Format',

'default_date_format_description' =>
'Date format for datepicker.',

'default_time_format' =>
'Default Time Format',

'default_time_format_description' =>
'Default time format for new calendars.',

'calendar_weblog' =>
'Calendar Channels(s)',

'calendar_weblog_description' =>
'Channels(s) to designate as Calendar channel(s)',

'event_weblog' =>
'Event Channels(s)',

'event_weblog_description' =>
'Channels(s) to designate as channel(s)',

'ics_update_delete_default' =>
"ICS Update Default Delete Behavior",

'ics_update_delete_default_description' =>
"By default, the <a href='http://www.solspace.com/docs/calendar/ics_update/' onclick='window.open(this.href); return false;'>{exp:calendar:ics_update}</a> tag does not delete entries that it does not find when downloading the ICS file for updates. <br/><br/>Setting this to 'yes' will make the <strong>{exp:calendar:ics_update}</strong> tag delete previously imported entries not found in the current ICS file download.",

// -------------------------------------
//	demo install (code pack)
// -------------------------------------

'demo_description' =>
'These demonstration templates will help you understand better how the Solspace Calendar Addon works.',

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

//----------------------------------------
//  Buttons
//----------------------------------------

'save' =>
'Save',

'delete_selected_items' =>
'Delete Selected Items',

//----------------------------------------
//  Errors
//----------------------------------------

'no_results' =>
'No results.',

'no_title' =>
'No Title',

'invalid_request' =>
"Invalid Request",

'calendar_module_disabled' =>
"The Calendar module is currently disabled. Please insure it is installed and up to date by going
to the module's control panel in the ExpressionEngine Control Panel",

'disable_module_to_disable_extension' =>
"To disable this extension, you must disable its corresponding <a href='%url%'>module</a>.",

'enable_module_to_enable_extension' =>
"To enable this extension, you must install its corresponding <a href='%url%'>module</a>.",

'cp_jquery_requred' =>
"The 'jQuery for the Control Panel' extension must be <a href='%extensions_url%'>enabled</a> to use this module.",

'invalid_weblog_id' =>
'Invalid channel ID',

'invalid_entry_id' =>
'Invalid entry ID',

'invalid_site_id' =>
'Invalid site ID',

'invalid_calendar_id' =>
'Invalid calendar ID',

'invalid_ymd' =>
'Invalid date',

'invalid_start_date' =>
'Invalid start date',

'invalid_end_date' =>
'Invalid end date',

'invalid_year' =>
'Invalid year',

'invalid_month' =>
'Invalid month',

'invalid_day' =>
'Invalid day',

'invalid_date' =>
'Invalid date',

'invalid_time' =>
'Invalid time',

'invalid_start_time' =>
'Invalid start time',

'invalid_end_time' =>
'Invalid end time',

'invalid_hour' =>
'Invalid hour',

'invalid_minute' =>
'Invalid minute',

'invalid_repeat_dates' =>
'Invalid repeat dates',

'invalid_calendar_permissions' =>
'You are not permitted to edit or add events to this calendar',

'no_permissions_for_any_calendars' =>
"You do not have permission to add or edit events on any calendars",

'invalid_permissions_json_request' =>
"In valid JSON request. Requires group_id and EE 2.x+.",

'cannot_update_extensions_disabled' =>
'This module cannot update while extensions are disabled.',

//----------------------------------------
//  Days
//----------------------------------------

'day_1_full' =>
'Monday',

'day_2_full' =>
'Tuesday',

'day_3_full' =>
'Wednesday',

'day_4_full' =>
'Thursday',

'day_5_full' =>
'Friday',

'day_6_full' =>
'Saturday',

'day_0_full' =>
'Sunday',

//----------------------------------------
//  Days - 2 Letters
//----------------------------------------

'day_1_2' =>
'Mo',

'day_2_2' =>
'Tu',

'day_3_2' =>
'We',

'day_4_2' =>
'Th',

'day_5_2' =>
'Fr',

'day_6_2' =>
'Sa',

'day_0_2' =>
'Su',

//----------------------------------------
//  Days - 3 Letters
//----------------------------------------

'day_1_3' =>
'Mon',

'day_2_3' =>
'Tue',

'day_3_3' =>
'Wed',

'day_4_3' =>
'Thu',

'day_5_3' =>
'Fri',

'day_6_3' =>
'Sat',

'day_0_3' =>
'Sun',

//----------------------------------------
//  Days - Short
//----------------------------------------

'day_1_short' =>
'Mon',

'day_2_short' =>
'Tues',

'day_3_short' =>
'Weds',

'day_4_short' =>
'Thurs',

'day_5_short' =>
'Fri',

'day_6_short' =>
'Sat',

'day_0_short' =>
'Sun',

//----------------------------------------
//  Days - 1 letter
//----------------------------------------

'day_1_1' =>
'M',

'day_2_1' =>
'T',

'day_3_1' =>
'W',

'day_4_1' =>
'T',

'day_5_1' =>
'F',

'day_6_1' =>
'S',

'day_0_1' =>
'S',

//----------------------------------------
//  Ordinal suffixes
//----------------------------------------

'suffix_0' =>
'th',

'suffix_1' =>
'st',

'suffix_2' =>
'nd',

'suffix_3' =>
'rd',

'suffix_4' =>
'th',

'suffix_5' =>
'th',

'suffix_6' =>
'th',

'suffix_7' =>
'th',

'suffix_8' =>
'th',

'suffix_9' =>
'th',

'suffix_10' =>
'th',

'suffix_11' =>
'th',

'suffix_12' =>
'th',

'suffix_13' =>
'th',

'suffix_14' =>
'th',

'suffix_15' =>
'th',

'suffix_16' =>
'th',

'suffix_17' =>
'th',

'suffix_18' =>
'th',

'suffix_19' =>
'th',

//----------------------------------------
//  Months
//----------------------------------------

'month_1_full' =>
'January',

'month_2_full' =>
'February',

'month_3_full' =>
'March',

'month_4_full' =>
'April',

'month_5_full' =>
'May',

'month_6_full' =>
'June',

'month_7_full' =>
'July',

'month_8_full' =>
'August',

'month_9_full' =>
'September',

'month_10_full' =>
'October',

'month_11_full' =>
'November',

'month_12_full' =>
'December',

//----------------------------------------
//  Months - 3 letters
//----------------------------------------

'month_1_3' =>
'Jan',

'month_2_3' =>
'Feb',

'month_3_3' =>
'Mar',

'month_4_3' =>
'Apr',

'month_5_3' =>
'May',

'month_6_3' =>
'Jun',

'month_7_3' =>
'Jul',

'month_8_3' =>
'Aug',

'month_9_3' =>
'Sep',

'month_10_3' =>
'Oct',

'month_11_3' =>
'Nov',

'month_12_3' =>
'Dec',

//----------------------------------------
//  am/pm
//----------------------------------------

'am' =>
'am',

'pm' =>
'pm',

'AM' =>
'AM',

'PM' =>
'PM',

'am_dot' =>
'a.m.',

'pm_dot' =>
'p.m.',

//----------------------------------------
//  Date parameters
//----------------------------------------

'today' =>
'today',

'yesterday' =>
'yesterday',

'tomorrow' =>
'tomorrow',

'day' =>
'day',

'week' =>
'week',

'month' =>
'month',

'year' =>
'year',

'ago' =>
'ago',

'begin' =>
'begin',

'last' =>
'last',

//----------------------------------------
//  Time parameters
//----------------------------------------

'now' =>
'now',

'noon' =>
'noon',

'midnight' =>
'midnight',

//----------------------------------------
//  field verbage
//----------------------------------------

'summary' =>
'Summary',

'location' =>
'Location',

'dates_and_options' =>
'Dates & Options',

'ics_url_label' =>
'URL to iCalendar (.ics) file',

'ics_url_desc' =>
"Add one or more URLs to .ics files - separated by newlines - to import to this calendar. All imported times will be adjusted to this calendar's timezone settings.",

'ics_url_stub' =>
"All imported times will be adjusted to this calendar's timezone settings.",

'time_format_label' =>
'Time Format',

'time_format_desc' =>
'Default time format to use for this calendar.',

'timezone' =>
'Timezone',

/* END */
''=>''
);

$lang['calendar_UM12']	= '(UTC -12:00) Baker/Howland Island';
$lang['calendar_UM11']	= '(UTC -11:00) Samoa Time Zone, Niue';
$lang['calendar_UM10']	= '(UTC -10:00) Hawaii-Aleutian Standard Time, Cook Islands, Tahiti';
$lang['calendar_UM95']	= '(UTC -9:30) Marquesas Islands';
$lang['calendar_UM9']	= '(UTC -9:00) Alaska Standard Time, Gambier Islands';
$lang['calendar_UM8']	= '(UTC -8:00) Pacific Standard Time, Clipperton Island';
$lang['calendar_UM7']	= '(UTC -7:00) Mountain Standard Time';
$lang['calendar_UM6']	= '(UTC -6:00) Central Standard Time';
$lang['calendar_UM5']	= '(UTC -5:00) Eastern Standard Time, Western Caribbean Standard Time';
$lang['calendar_UM45']	= '(UTC -4:30) Venezuelan Standard Time';
$lang['calendar_UM4']	= '(UTC -4:00) Atlantic Standard Time, Eastern Caribbean Standard Time';
$lang['calendar_UM35']	= '(UTC -3:30) Newfoundland Standard Time';
$lang['calendar_UM3']	= '(UTC -3:00) Argentina, Brazil, French Guiana, Uruguay';
$lang['calendar_UM2']	= '(UTC -2:00) South Georgia/South Sandwich Islands';
$lang['calendar_UM1']	= '(UTC -1:00) Azores, Cape Verde Islands';
$lang['calendar_UTC']	= '(UTC) Greenwich Mean Time, Western European Time';
$lang['calendar_UP1']	= '(UTC +1:00) Central European Time, West Africa Time';
$lang['calendar_UP2']	= '(UTC +2:00) Central Africa Time, Eastern European Time, Kaliningrad Time';
$lang['calendar_UP3']	= '(UTC +3:00) Moscow Time, East Africa Time';
$lang['calendar_UP35']	= '(UTC +3:30) Iran Standard Time';
$lang['calendar_UP4']	= '(UTC +4:00) Azerbaijan Standard Time, Samara Time';
$lang['calendar_UP45']	= '(UTC +4:30) Afghanistan';
$lang['calendar_UP5']	= '(UTC +5:00) Pakistan Standard Time, Yekaterinburg Time';
$lang['calendar_UP55']	= '(UTC +5:30) Indian Standard Time, Sri Lanka Time';
$lang['calendar_UP575']	= '(UTC +5:45) Nepal Time';
$lang['calendar_UP6']	= '(UTC +6:00) Bangladesh Standard Time, Bhutan Time, Omsk Time';
$lang['calendar_UP65']	= '(UTC +6:30) Cocos Islands, Myanmar';
$lang['calendar_UP7']	= '(UTC +7:00) Krasnoyarsk Time, Cambodia, Laos, Thailand, Vietnam';
$lang['calendar_UP8']	= '(UTC +8:00) Australian Western Standard Time, Beijing Time, Irkutsk Time';
$lang['calendar_UP875']	= '(UTC +8:45) Australian Central Western Standard Time';
$lang['calendar_UP9']	= '(UTC +9:00) Japan Standard Time, Korea Standard Time, Yakutsk Time';
$lang['calendar_UP95']	= '(UTC +9:30) Australian Central Standard Time';
$lang['calendar_UP10']	= '(UTC +10:00) Australian Eastern Standard Time, Vladivostok Time';
$lang['calendar_UP105']	= '(UTC +10:30) Lord Howe Island';
$lang['calendar_UP11']	= '(UTC +11:00) Magadan Time, Solomon Islands, Vanuatu';
$lang['calendar_UP115']	= '(UTC +11:30) Norfolk Island';
$lang['calendar_UP12']	= '(UTC +12:00) Fiji, Gilbert Islands, Kamchatka Time, New Zealand Standard Time';
$lang['calendar_UP1275']	= '(UTC +12:45) Chatham Islands Standard Time';
$lang['calendar_UP13']	= '(UTC +13:00) Phoenix Islands Time, Tonga';
$lang['calendar_UP14']	= '(UTC +14:00) Line Islands';