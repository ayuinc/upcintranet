<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Default library helper
 *
 * @package		Module name
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @link		http://reinos.nl
 * @copyright 	Copyright (c) 2013 Reinos.nl Internet Media
 */

/**
 * Include the config file
 */
require_once(PATH_THIRD.'printfriendly/config.php');

/**
 * Include helper
 */
require_once(PATH_THIRD.'printfriendly/libraries/printfriendly_helper.php');

class Printfriendly_lib
{
	private $default_settings;
	private $settings;
	private $EE;

	//debug array
	public $debug = array();

	public function __construct()
	{							
		//load the settings
		ee()->load->library(PF_MAP.'_settings');
		
		//require the default settings
		require PATH_THIRD.PF_MAP.'/settings.php';
	}

	// ----------------------------------------------------------------------
	// CUSTOM FUNCTIONS
	// ----------------------------------------------------------------------

	// ----------------------------------------------------------------------
	// PRIVATE FUNCTIONS
	// ----------------------------------------------------------------------
	
	// ----------------------------------------------------------------------
	// DEFAULT FUNCTIONS
	// ----------------------------------------------------------------------

	// ----------------------------------------------------------------------

	/**
	 * Log all messages
	 *
	 * @param array $logs The debug messages.
	 * @return void
	 */
	public function expose_log()
	{
		if(!empty($this->debug))
		{
			foreach ($this->debug as $log)
			{
				ee()->TMPL->log_item('&nbsp;&nbsp;***&nbsp;&nbsp;'.PF_CLASS.' debug: ' . $log);
			}
		}
	} 
		
	// ----------------------------------------------------------------------
	 
	
	
} // END CLASS

/* End of file printfriendly_library.php  */
/* Location: ./system/expressionengine/third_party/printfriendly/libraries/printfriendly_library.php */