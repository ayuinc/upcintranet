<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - Config
 *
 * NSM Addon Updater config file.
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/config.php
 */

if ( ! defined('CALENDAR_VERSION'))
{
	require_once 'constants.calendar.php';
}

$config['name']									= 'Calendar';
$config['version']								= CALENDAR_VERSION;
$config['nsm_addon_updater']['versions_xml']	= 'http://www.solspace.com/software/nsm_addon_updater/calendar';
