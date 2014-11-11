<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  Compatibility file
 *
 * @package		Default
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @license  	http://reinos.nl/add-ons/commercial-license
 * @link        http://reinos.nl/add-ons/gmaps
 * @copyright 	Copyright (c) 2013 Reinos.nl Internet Media
 */

//--------------------------------------------
//	Alias to get_instance() < EE 2.6.0 backward compat
//--------------------------------------------
if ( ! function_exists('ee'))
{
	function ee()
	{
		static $EE;
		if ( ! $EE) $EE = get_instance();
		return $EE;
	}
}