<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - Constants
 *
 * Central location for various values we need throughout the module.
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/constants.calendar.php
 */

if ( ! defined('CALENDAR_VERSION'))
{
	$path = rtrim(realpath(dirname(__FILE__)), '/') . '/';

	define('CALENDAR_VERSION',	'1.8.9');
	define('CALENDAR_DOCS_URL',	'http://solspace.com/docs/calendar');

	// -------------------------------------
	// Paths to enlightenment
	// -------------------------------------

	define('CALENDAR_PATH', $path);

	// -------------------------------------
	// Default weblogs and fields
	// -------------------------------------

	define('CALENDAR_CALENDARS_CHANNEL_NAME_DEFAULT', 'calendar_calendars');
	define('CALENDAR_CALENDARS_CHANNEL_TITLE', 'Calendar: Calendars');
	define('CALENDAR_CALENDARS_FIELD_GROUP', 'Calendar: Calendars');
	define('CALENDAR_CALENDARS_FIELD_PREFIX', 'calendar_');
	define('CALENDAR_EVENTS_CHANNEL_NAME_DEFAULT', 'calendar_events');
	define('CALENDAR_EVENTS_CHANNEL_TITLE', 'Calendar: Events');
	define('CALENDAR_EVENTS_FIELD_GROUP', 'Calendar: Events');
	define('CALENDAR_EVENTS_FIELD_PREFIX', 'event_');
}