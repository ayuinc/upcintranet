<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /**
 * mithra62 - Export It
 *
 * @package		mithra62:Export_it
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2011, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/export-it/
 * @version		1.3.2
 * @filesource 	./system/expressionengine/third_party/export_it/
 */
 
 /**
 * Export It - Helper Functions
 *
 * Helper Functions
 *
 * @package 	mithra62:export_it
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/export_it/helpers/utilities_helper.php
 */
if ( ! function_exists('m62_convert_timestamp'))
{
	function m62_convert_timestamp($date, $format = FALSE)
	{
		$EE =& get_instance();
		$EE->load->helper('date');
		if(!$format)
		{
			$format = $EE->export_it_lib->settings['export_it_date_format'];
		}
		
		return $EE->localize->format_date($format, $date);		
	}
}

if( !function_exists('m62_status_color'))
{
	/**
	 * Returns the status color based on $status
	 * @param string $status
	 * @param array $statuses
	 * @return boolean|array
	 */
	function m62_status_color($status, array $statuses = array())
	{
		if(!is_array($statuses))
		{
			return FALSE;
		}
	
		foreach($statuses AS $color)
		{
			if($status == $color['status'])
				return $color['highlight'];
		}
	}	
}

if( !function_exists('m62_create_mailinglist_links'))
{
	function m62_create_mailinglist_links($str, $mailinglists)
	{
		$mailinglists = array_flip($mailinglists);
		$lists = explode(',',$str);	
		$links = array();
		foreach($lists AS $list)
		{
			if(isset($mailinglists[$list]))
			{
				$links[] = '<a href="javascript:;" rel="'.$mailinglists[$list].'" class="mailinglist_filter_id">'.$list.'</a>';		
			}
		}
		
		return implode(', ', $links);
	}	
}


if(!function_exists('m62_theme_url'))
{
	/**
	 * Sets up the third party theme URL
	 * @return string
	 */
	function m62_theme_url()
	{
		$url = '';
		if(defined('URL_THIRD_THEMES'))
		{
			$url = URL_THIRD_THEMES;
		}
		else
		{
			$EE =& get_instance();
			$url = rtrim($EE->config->config['theme_folder_url'], '/') .'/third_party/';
		}

		return $url;
	}
}

if(!function_exists('m62_theme_path'))
{
	/**
	 * Sets up the third party themes path
	 * @return string
	 */
	function m62_theme_path()
	{
		$path = '';
		if(defined('PATH_THIRD_THEMES'))
		{
			$path = PATH_THIRD_THEMES;
		}
		else
		{
			$EE =& get_instance();
			$path = rtrim($EE->config->config['theme_folder_path'], '/') .'/third_party/';
		}

		return $path;
	}
}

if(!function_exists('m62_third_party_path'))
{
	/**
	 * Sets up the third party add-ons path
	 * @return string
	 */
	function m62_third_party_path()
	{
		$path = '';
		if(defined('PATH_THIRD'))
		{
			$path = PATH_THIRD;
		}
		else
		{
			$path = APPPATH.'third_party/';
		}

		return $path;
	}
}
