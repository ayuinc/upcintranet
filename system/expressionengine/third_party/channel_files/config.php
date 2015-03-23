<?php

/**
 * Config file for Channel Files
 *
 * @package			DevDemon_ChannelFiles
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com/channel_files/
 * @see				http://ee-garage.com/nsm-addon-updater/developers
 */

if ( ! defined('CHANNEL_FILES_NAME'))
{
	define('CHANNEL_FILES_NAME',         'Channel Files');
	define('CHANNEL_FILES_CLASS_NAME',   'channel_files');
	define('CHANNEL_FILES_VERSION',      '5.2.9');
}

$config['name'] 	= CHANNEL_FILES_NAME;
$config['version'] 	= CHANNEL_FILES_VERSION;
$config['nsm_addon_updater']['versions_xml'] = 'http://www.devdemon.com/'.CHANNEL_FILES_CLASS_NAME.'/versions_feed/';

/* End of file config.php */
/* Location: ./system/expressionengine/third_party/channel_files/config.php */
