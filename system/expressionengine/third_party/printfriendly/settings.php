<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * the settings for the module
 *
 * @package		Default module
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @link		http://reinos.nl
 * @copyright 	Copyright (c) 2013 Reinos.nl Internet Media
 */

//updates
$this->updates = array(
	//'1.2',
);

//Default Post
$this->default_post = array(
	'license_key'   		=> '',
);

//overrides
$this->overide_settings = array(
	//'gmaps_icon_dir' => '[theme_dir]images/icons/',
	//'gmaps_icon_url' => '[theme_url]images/icons/',
);

// Backwards-compatibility with pre-2.6 Localize class
$this->format_date_fn = (version_compare(APP_VER, '2.6', '>=')) ? 'format_date' : 'decode_date';

/* End of file settings.php  */
/* Location: ./system/expressionengine/third_party/default/settings.php */