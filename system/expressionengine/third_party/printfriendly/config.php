<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Default config
 *
 * @package		Default module
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @link		http://reinos.nl
 * @copyright 	Copyright (c) 2013 Reinos.nl Internet Media
 */

//contants
if ( ! defined('PF_NAME'))
{
	define('PF_NAME', 'PrintFriendly');
	define('PF_CLASS', 'Printfriendly');
	define('PF_MAP', 'printfriendly');
	define('PF_VERSION', '1.0');
	define('PF_DESCRIPTION', 'Add PrintFriendly buttons to the page');
	define('PF_DOCS', 'http://reinos.nl/add-ons/printfriendly/docs');
	define('PF_DEVOTEE', '');
	define('PF_AUTHOR', 'Rein de Vries');
	define('PF_DEBUG', true);
}

//configs
$config['name'] = PF_NAME;
$config['version'] = PF_VERSION;

//load compat file
require_once(PATH_THIRD.PF_MAP.'/compat.php');

/* End of file config.php */
/* Location: /system/expressionengine/third_party/default/config.php */