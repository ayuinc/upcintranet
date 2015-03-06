<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Default  helper
 *
 * @package		Module name
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @link		http://reinos.nl/add-ons/add-ons
 * @license  	http://reinos.nl/add-ons/commercial-license
 * @copyright 	Copyright (c) 2013 Reinos.nl Internet Media
 */

class Printfriendly_helper
{
	/**
	 * Remove the double slashes
	 */
	public static function remove_double_slashes($str)
    {
        return preg_replace("#(^|[^:])//+#", "\\1/", $str);
    }

	// ----------------------------------------------------------------------

	/**
	 * Check if Submitted String is a Yes value
	 *
	 * If the value is 'y', 'yes', 'true', or 'on', then returns TRUE, otherwise FALSE
	 *
	 */
	public static function check_yes($which, $string = false)
	{
	    if (is_string($which))
	    {
	        $which = strtolower(trim($which));
	    }

	    $result = in_array($which, array('yes', 'y', 'true', 'on'), TRUE);

	    if($string)
	    {
	       return $result ? 'true' : 'false' ; 
	    }

	    return $result;
	}

	// ------------------------------------------------------------------------

	/**
	 * Log an array to a file
	 *
	 */
	public static function log_array($array)
    {
		@file_put_contents(__DIR__.'/print.txt', print_r($array, true));
    }

	// ----------------------------------------------------------------------------------

	/**
	* Log all messages
	*
	* @param array $logs The debug messages.
	* @return void
	*/
	public static function log_to_ee( $logs = array(), $name = '')
    {
        if(!empty($logs))
        {
            foreach ($logs as $log)
            {
                ee()->TMPL->log_item('&nbsp;&nbsp;***&nbsp;&nbsp;'.$name.' debug: ' . $log);
            }
        }
    }

	// ------------------------------------------------------------------------

	/**
	 * Is the string serialized
	 *
	 */
	public static function is_serialized($val)
    {
        if (!is_string($val)){ return false; }
        if (trim($val) == "") { return false; }
        if (preg_match("/^(i|s|a|o|d):(.*);/si",$val)) { return true; }
        return false;
    }

	// ------------------------------------------------------------------------

	/**
	 * Is the string json
	 *
	 */
	public static function is_json($string)
    {
       json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

	// ------------------------------------------------------------------------

	/**
	 * Retrieve site path
	 */
	public static function get_site_path()
    {
        // extract path info
        $site_url_path = parse_url(ee()->functions->fetch_site_index(), PHP_URL_PATH);

        $path_parts = pathinfo($site_url_path);
        $site_path = $path_parts['dirname'];

        $site_path = str_replace("\\", "/", $site_path);

        return $site_path;
    }   

	// ------------------------------------------------------------------------

	/**
	 * remove beginning and ending slashes in a url
	 *
	 * @param  $url
	 * @return void
	 */
	public static function remove_begin_end_slash($url, $slash = '/')
    {
        $url = explode($slash, $url);
        array_pop($url);
        array_shift($url);
        return implode($slash, $url);
    }

	// ----------------------------------------------------------------------

	/**
	 * add slashes for an array
	 *
	 * @param  $arr_r
	 * @return void
	 */
	public static function add_slashes_extended(&$arr_r)
    {
        if(is_array($arr_r))
        {
            foreach ($arr_r as &$val)
                is_array($val) ? self::add_slashes_extended($val):$val=addslashes($val);
            unset($val);
        }
        else
            $arr_r = addslashes($arr_r);
    }

	// ----------------------------------------------------------------

	/**
	 * add a element to a array
	 *
	 * @return  DB object
	 */
	public static function array_unshift_assoc(&$arr, $key, $val)
    {
        $arr = array_reverse($arr, true);
        $arr[$key] = $val;
        $arr = array_reverse($arr, true);
        return $arr;
    }

	// ----------------------------------------------------------------------

	/**
	 * get the memory usage
	 *
	 * @param 
	 * @return void
	 */
	public static function memory_usage()
    {
         $mem_usage = memory_get_usage(true);
       
        if ($mem_usage < 1024)
            return $mem_usage." bytes";
        elseif ($mem_usage < 1048576)
            return round($mem_usage/1024,2)." KB";
        else
            return round($mem_usage/1048576,2)." MB";
    }

    // ----------------------------------------------------------------------
	
	/**
	 * EDT benchmark
	 * https://github.com/mithra62/ee_debug_toolbar/wiki/Benchmarks
	 *
	 * @param none
	 * @return void
	 */
	public static function benchmark($method = '', $start = true)
	{
		if($method != '')
		{
			$prefix = PF_MAP.'_';
			$type = $start ? '_start' : '_end';
			ee()->benchmark->mark($prefix.$method.$type);
		}
	}

	// ----------------------------------------------------------------------
		
	/**
	 * 	Fetch Action IDs
	 *
	 * 	@access public
	 *	@param string
	 * 	@param string
	 *	@return mixed
	 */
	public static function fetch_action_id($class = '', $method)
	{
		ee()->db->select('action_id');
		ee()->db->where('class', $class);
		ee()->db->where('method', $method);
		$query = ee()->db->get('actions');
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		return $query->row('action_id');
	}

	// ----------------------------------------------------------------------

	/**
	 * Parse only a string
	 *
	 * @param none
	 * @return void
	 */
	public static function parse_channel_data($tag = '', $parse = true)
	{
		// do we need to parse, and are there any modules/tags to parse?
		if($parse && (strpos($tag, LD.'exp:') !== FALSE))
		{
			require_once APPPATH.'libraries/Template.php';
			$OLD_TMPL = isset(ee()->TMPL) ? ee()->TMPL : NULL;
			ee()->TMPL = new EE_Template();
			ee()->TMPL->parse($tag, true);
			ee()->TMPL = $OLD_TMPL;
		}

		//return the data
		return trim($tag);		
	}

	// ----------------------------------------------------------------------

    /**
     * set_cache
     *
     * @access private
    */
    public static function set_cache($name = '', $value = '')
    {
    	if (session_id() == "") 
		{
			session_start(); 
		}

		$_SESSION[$name] = $value;
    }

    // ----------------------------------------------------------------------

    /**
     * get_cache
     *
     * @access private
    */
    public static function get_cache($name = '')
    {
    	// if no active session we start a new one
		if (session_id() == "") 
		{
			session_start(); 
		}
		
		if (isset($_SESSION[$name]))
		{
			return $_SESSION[$name];
		}
		
		else
		{
			return '';
		}
    }

    // ----------------------------------------------------------------------

    /**
     * delete_cache
     *
     * @access private
    */
    public static function delete_cache($name = '')
    {
    	// if no active session we start a new one
		if (session_id() == "") 
		{
			session_start(); 
		}
		
		unset($_SESSION[$name]);
    }

    // ----------------------------------------------------------------------

    /**
     * mcp_meta_parser
     *
     * @access private
    */
	public static function mcp_meta_parser($type='', $file)
	{
		// -----------------------------------------
		// CSS
		// -----------------------------------------
		if ($type == 'css')
		{
			if ( isset(ee()->session->cache[PF_MAP]['CSS'][$file]) == FALSE )
			{
				ee()->cp->add_to_head('<link rel="stylesheet" href="' . ee()->printfriendly_settings->get_setting('theme_url') . 'css/' . $file . '" type="text/css" media="print, projection, screen" />');
				ee()->session->cache[PF_MAP]['CSS'][$file] = TRUE;
			}
		}

		// -----------------------------------------
		// CSS Inline
		// -----------------------------------------
		if ($type == 'css_inline')
		{
			ee()->cp->add_to_foot('<style type="text/css">'.$file.'</style>');
			
		}

		// -----------------------------------------
		// Javascript
		// -----------------------------------------
		if ($type == 'js')
		{
			if ( isset(ee()->session->cache[PF_MAP]['JS'][$file]) == FALSE )
			{
				ee()->cp->add_to_foot('<script src="' . ee()->printfriendly_settings->get_setting('theme_url') . 'javascript/' . $file . '" type="text/javascript"></script>');
				ee()->session->cache[PF_MAP]['JS'][$file] = TRUE;
			}
		}

		// -----------------------------------------
		// Javascript Inline
		// -----------------------------------------
		if ($type == 'js_inline')
		{
			ee()->cp->add_to_foot('<script type="text/javascript">'.$file.'</script>');
			
		}
	}

	// ----------------------------------------------------------------------
	 
	
	/**
	 * Anonymously report EE & PHP versions used to improve the product.
	 */
	public static function stats()
	{
		if (function_exists('curl_init'))
		{
			$data = http_build_query(array(
				// anonymous reference generated using one-way hash
				'site' => sha1(ee()->config->item('license_number')),
				'product' => 'store',
				'version' => STORE_VERSION,
				'ee' => APP_VER,
				'php' => PHP_VERSION,
			));
			ee()->load->library('curl');
			ee()->curl->simple_post("http://hello.exp-resso.com/v1", $data);
		}

		// report again in 28 days
		ee()->store_config->set_item('report_date', ee()->localize->now + 28*24*60*60);
		ee()->store_config->save();
		exit('OK');
	}
	
	
} // END CLASS

/* End of file printfriendly_helper.php  */
/* Location: ./system/expressionengine/third_party/printfriendly/libraries/printfriendly_helper.php */