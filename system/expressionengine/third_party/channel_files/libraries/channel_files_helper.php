<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Channel Files Helper File
 *
 * @package			DevDemon_ChannelFiles
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com
 */
class Channel_files_helper
{
	private $ekey = 'SADFo92jzVnzXj39IUYGvi6eL8h6RvJV8CytUiouV547vCytDyUFl76R';

	/**
	 * Constructor
	 *
	 * @access public
	 */
	function __construct()
	{
		// Creat EE Instance
		$this->EE =& get_instance();
		$this->EE->load->library('firephp');

		$this->site_id = $this->EE->config->item('site_id');

		if ($this->EE->config->item('encryption_key') != FALSE) $this->ekey = $this->EE->config->item('encryption_key');
	}

	// ********************************************************************************* //

	function define_theme_url()
	{
		if (defined('URL_THIRD_THEMES') === TRUE)
		{
			$theme_url = URL_THIRD_THEMES;
		}
		else
		{
			$theme_url = $this->EE->config->item('theme_folder_url').'third_party/';
		}

		$theme_url = trim($theme_url);

		// Are we working on SSL?
		if (isset($_SERVER['HTTP_REFERER']) == TRUE AND strpos($_SERVER['HTTP_REFERER'], 'https://') !== FALSE)
		{
			$theme_url = str_replace('http://', 'https://', $theme_url);
		}

		if (! defined('CHANNELFILES_THEME_URL')) define('CHANNELFILES_THEME_URL', $theme_url . 'channel_files/');
	}

	// ********************************************************************************* //

	function format_bytes($bytes) {
	   if ($bytes < 1024) return $bytes.' B';
	   elseif ($bytes < 1048576) return round($bytes / 1024, 2).' KB';
	   elseif ($bytes < 1073741824) return round($bytes / 1048576, 2).' MB';
	   elseif ($bytes < 1099511627776) return round($bytes / 1073741824, 2).' GB';
	   else return round($bytes / 1099511627776, 2).' TB';
	}

	// ********************************************************************************* //

	/**
	 * Grab File Module Settings
	 * @return array
	 */
	function grab_settings($site_id=FALSE)
	{

		$settings = array();

		if (isset($this->EE->session->cache['channel_files_Settings']) == TRUE)
		{
			$settings = $this->EE->session->cache['channel_files_Settings'];
		}
		else
		{
			$this->EE->db->select('settings');
			$this->EE->db->where('module_name', 'channel_files');
			$query = $this->EE->db->get('exp_modules');
			if ($query->num_rows() > 0) $settings = unserialize($query->row('settings'));
		}

		$this->EE->session->cache['channel_files_Settings'] = $settings;

		if ($site_id)
		{
			$settings = isset($settings['site_id:'.$site_id]) ? $settings['site_id:'.$site_id] : array();
		}

		return $settings;
	}

	// ********************************************************************************* //

	/**
	 * Grab File Module Settings
	 * @return array
	 */
	public function grab_field_settings($field_id)
	{
		if (isset($this->EE->session->cache['Channel_Files']['Field'][$field_id]) == FALSE)
		{
			$query = $this->EE->db->select('field_settings')->from('exp_channel_fields')->where('field_id', $field_id)->get();
			$settings = unserialize(base64_decode($query->row('field_settings')));

			if (isset($settings['channel_files']) === false) $settings['channel_files'] = array();
			$settings = $this->array_extend($this->EE->config->item('cf_defaults'), $settings['channel_files']);
		}
		else
		{
			$settings = $this->EE->session->cache['Channel_Files']['Field'][$field_id];
		}

		// Any overrides?
		$config = $this->EE->config->item('channel_files') ? $this->EE->config->item('channel_files') : array();

		if (isset($config['fields'][$field_id]) === true) {
			$settings = $this->array_extend($settings, $config['fields'][$field_id]);
		}

		$this->EE->session->cache['Channel_Files']['Field'][$field_id] = $settings;

		return $settings;
	}

	// ********************************************************************************* //

	/**
	 * Get Upload Preferences (Cross-compatible between ExpressionEngine 2.0 and 2.4)
	 * @param  int $group_id Member group ID specified when returning allowed upload directories only for that member group
	 * @param  int $id       Specific ID of upload destination to return
	 * @return array         Result array of DB object, possibly merged with custom file upload settings (if on EE 2.4+)
	 */
	function get_upload_preferences($group_id = NULL, $id = NULL, $ignore_site_id = FALSE)
	{
		if (version_compare(APP_VER, '2.4', '>='))
		{
			$this->EE->load->model('file_upload_preferences_model');
			$row = $this->EE->file_upload_preferences_model->get_file_upload_preferences($group_id, $id, $ignore_site_id);
			$this->EE->session->cache['upload_prefs'][$id] = $row;
			return $row;
		}

		if (version_compare(APP_VER, '2.1.5', '>='))
		{
			// for admins, no specific filtering, just give them everything
			if ($group_id == 1)
			{
				// there a specific upload location we're looking for?
				if ($id != '')
				{
					$this->EE->db->where('id', $id);
				}

				$this->EE->db->from('upload_prefs');
				if ($ignore_site_id != TRUE) $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
				$this->EE->db->order_by('name');

				$result = $this->EE->db->get();
			}
			else
			{
				// non admins need to first be checked for restrictions
				// we'll add these into a where_not_in() check below
				$this->EE->db->select('upload_id');
				$no_access = $this->EE->db->get_where('upload_no_access', array('member_group'=>$group_id));

				if ($no_access->num_rows() > 0)
				{
					$denied = array();
					foreach($no_access->result() as $result)
					{
						$denied[] = $result->upload_id;
					}
					$this->EE->db->where_not_in('id', $denied);
				}

				// there a specific upload location we're looking for?
				if ($id)
				{
					$this->EE->db->where('id', $id);
				}

				$this->EE->db->from('upload_prefs');
				if ($ignore_site_id != TRUE) $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
				$this->EE->db->order_by('name');

				$result = $this->EE->db->get();
			}
		}
		else
		{
			$this->EE->load->model('tools_model');
			$result = $this->EE->tools_model->get_upload_preferences($group_id, $id);
		}

		// If an $id was passed, just return that directory's preferences
		if ( ! empty($id))
		{
			$result = $result->row_array();
			$this->EE->session->cache['upload_prefs'][$id] = $result;
			return $result;
		}

		// Use upload destination ID as key for row for easy traversing
		$return_array = array();
		foreach ($result->result_array() as $row)
		{
			$return_array[$row['id']] = $row;
		}

		$this->EE->session->cache['upload_prefs'][$id] = $return_array;

		return $return_array;
	}

	// ********************************************************************************* //

	function get_router_url($type='url', $method='channel_files_router')
	{
		// Do we have a cached version of our ACT_ID?
		if (isset($this->EE->session->cache['Channel_Files']['Router_Url'][$method]['ACT_ID']) == FALSE)
		{
			$this->EE->db->select('action_id');
			$this->EE->db->where('class', 'Channel_files');
			$this->EE->db->where('method', $method);
			$query = $this->EE->db->get('actions');
			$ACT_ID = $query->row('action_id');
		}
		else $ACT_ID = $this->EE->session->cache['Channel_Files']['Router_Url'][$method]['ACT_ID'];

		// RETURN: Full Action URL
		if ($type == 'url')
		{
			// Grab Site URL
			$url = $this->EE->functions->fetch_site_index(0, 0);

			/*
			// Check for INDEX
			$site_index = $this->EE->config->item('site_index');

			if ($site_index != FALSE)
			{
				// Check for index.php
				if (substr($url, -9, 9) != 'index.php')
				{
					$url .= 'index.php';
				}
			}
			*/

			// Check for last slash
			//if (substr($url, -1) != '/') $url .= '/';

			if (defined('MASKED_CP') == FALSE OR MASKED_CP == FALSE)
			{
				// Replace site url domain with current working domain
				$server_host = (isset($_SERVER['HTTP_HOST']) == TRUE && $_SERVER['HTTP_HOST'] != FALSE) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
				$url = preg_replace('#http\://(([\w][\w\-\.]*)\.)?([\w][\w\-]+)(\.([\w][\w\.]*))?\/#', "http://{$server_host}/", $url);
			}

			// Create new URL
			$ajax_url = $url.QUERY_MARKER.'ACT=' . $ACT_ID;

			// Are we working on SSL?
			if (isset($_SERVER['HTTP_REFERER']) == TRUE AND strpos($_SERVER['HTTP_REFERER'], 'https://') !== FALSE)
			{
				$ajax_url = str_replace('http://', 'https://', $ajax_url);
			}

			if (isset($this->EE->session->cache['Channel_Files']['Router_Url'][$method]['URL']) == TRUE) return $this->EE->session->cache['Channel_Files']['Router_Url'][$method]['URL'];
			$this->EE->session->cache['Channel_Files']['Router_Url'][$method]['URL'] = $ajax_url;
			return $this->EE->session->cache['Channel_Files']['Router_Url'][$method]['URL'];
		}

		// RETURN: ACT_ID Only
		if ($type == 'act_id') return $ACT_ID;
	}

	// ********************************************************************************* //

	function parse_keywords($str, $remove=array())
	{
		// Remove all whitespace except single space
		$str = preg_replace("/(\r\n|\r|\n|\t|\s)+/", ' ', $str);

		// Characters that we do not want to allow...ever.
		// In the EE cleaner, we lost too many characters that might be useful in a Custom Field search, especially with Exact Keyword searches
		// The trick, security-wise, is to make sure any keywords output is converted to entities prior to any possible output
		$chars = array(	'{'	,
						'}'	,
						"^"	,
						"~"	,
						"*"	,
						"|"	,
						"["	,
						"]"	,
						'?'.'>'	,
						'<'.'?' ,
					  );

		// Keep as a space, helps prevent string removal security holes
		$str = str_replace(array_merge($chars, $remove), ' ', $str);

		// Only a single single space for spaces
		$str = preg_replace("/\s+/", ' ', $str);

		// Kill naughty stuff
		$str = trim($this->EE->security->xss_clean($str));

		return $str;
	}

	// ********************************************************************************* //

	/**
	 * Generate new XID
	 *
	 * @return string the_xid
	 */
	function xid_generator()
	{
		// Maybe it's already been made by EE
		if (defined('XID_SECURE_HASH') == TRUE) return XID_SECURE_HASH;

		// First is secure_forum enabled?
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			// Did we already cache it?
			if (isset($this->EE->session->cache['XID']) == TRUE && $this->EE->session->cache['XID'] != FALSE)
			{
				return $this->EE->session->cache['XID'];
			}

			// Is there one already made that i can use?
			$this->EE->db->select('hash');
			$this->EE->db->from('exp_security_hashes');
			$this->EE->db->where('ip_address', $this->EE->input->ip_address());
			$this->EE->db->where('`date` > UNIX_TIMESTAMP()-3600');
			$this->EE->db->limit(1);
			$query = $this->EE->db->get();

			if ($query->num_rows() > 0)
			{
				$row = $query->row();
				$this->EE->session->cache['XID'] = $row->hash;
				return $this->EE->session->cache['XID'];
			}

			// Lets make one then!
			$XID	= $this->EE->functions->random('encrypt');
			$this->EE->db->insert('exp_security_hashes', array('date' => $this->EE->localize->now, 'ip_address' => $this->EE->input->ip_address(), 'hash' => $XID));

			// Remove Old
			//$DB->query("DELETE FROM exp_security_hashes WHERE date < UNIX_TIMESTAMP()-7200"); // helps garbage collection for old hashes

			$this->EE->session->cache['XID'] = $XID;
			return $XID;
		}
	}

	// ********************************************************************************* //

	public function generate_json($obj)
	{
		if (function_exists('json_encode') === FALSE)
		{
			if (class_exists('Services_JSON_CUSTOM') === FALSE) include dirname(__FILE__).'/JSON.php';
			$JSON = new Services_JSON_CUSTOM();
			return $JSON->encode($obj);
		}
		else
		{
			return json_encode($obj);
		}
	}

	// ********************************************************************************* //

	public function decode_json($obj)
	{
		if (function_exists('json_decode') === FALSE)
		{
			if (class_exists('Services_JSON_CUSTOM') === FALSE) include dirname(__FILE__).'/JSON.php';
			$JSON = new Services_JSON_CUSTOM();
			return $JSON->decode($obj);
		}
		else
		{
			return json_decode($obj);
		}
	}

	// ********************************************************************************* //

	/**
	 * Delete Files
	 *
	 * Deletes all files contained in the supplied directory path.
	 * Files must be writable or owned by the system in order to be deleted.
	 * If the second parameter is set to TRUE, any directories contained
	 * within the supplied base directory will be nuked as well.
	 *
	 * @access	public
	 * @param	string	path to file
	 * @param	bool	whether to delete any directories found in the path
	 * @return	bool
	 */
	function delete_files($path, $del_dir = FALSE, $level = 0)
	{
		// Trim the trailing slash
		$path = preg_replace("|^(.+?)/*$|", "\\1", $path);

		if ( ! $current_dir = @opendir($path))
			return;

		while(FALSE !== ($filename = @readdir($current_dir)))
		{
			if ($filename != "." and $filename != ".." and strpos($filename, '.nfs') !== 0)
			{
				if (is_dir($path.'/'.$filename))
				{
					// Ignore empty folders
					if (substr($filename, 0, 1) != '.')
					{
						$this->delete_files($path.'/'.$filename, $del_dir, $level + 1);
					}
				}
				else
				{
					@unlink($path.'/'.$filename);
				}
			}
		}
		@closedir($current_dir);

		if ($del_dir == TRUE AND $level > 0)
		{
			@rmdir($path);
		}
	}

	// ********************************************************************************* //

	/**
	 * Array split - Split array in $pieces
	 *
	 * @param array
	 * @param int - Number of pieces
	 * @return array - Split array (multidimensional)
	 */
	function array_split($array, $pieces=2)
	{
		// Less then 2 pieces?
	    if ($pieces < 2)
	    {
	    	return array($array);
	    }

	    $newCount = ceil(count($array)/$pieces);

	    $a = array_slice($array, 0, $newCount);

	    $b = $this->array_split(array_slice($array, $newCount), $pieces-1);

	    return array_merge(array($a),$b);
	}

	// ********************************************************************************* //

	public function encrypt_string($string)
	{
		$this->EE->load->library('encrypt');
		if (function_exists('mcrypt_encrypt')) $this->EE->encrypt->set_cipher(MCRYPT_BLOWFISH);

		$string = $this->EE->encrypt->encode($string, substr(sha1(base64_encode($this->ekey)),0, 56));

		// Set it back
		if (function_exists('mcrypt_encrypt')) $this->EE->encrypt->set_cipher(MCRYPT_RIJNDAEL_256);

		return $string;
	}

	// ********************************************************************************* //

	public function decode_string($string)
	{
		$this->EE->load->library('encrypt');
		if (function_exists('mcrypt_decrypt')) $this->EE->encrypt->set_cipher(MCRYPT_BLOWFISH);

		$string = $this->EE->encrypt->decode($string, substr(sha1(base64_encode($this->ekey)),0, 56));

		// Set it back
		if (function_exists('mcrypt_encrypt')) $this->EE->encrypt->set_cipher(MCRYPT_RIJNDAEL_256);

		return $string;
	}

	// ********************************************************************************* //

	/**
	 * Is a Natural number  (0,1,2,3, etc.)
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function is_natural_number($str)
	{
   		return (bool)preg_match( '/^[0-9]+$/', $str);
	}

	// ********************************************************************************* //

	/**
	 * Array Extend
	 * "Extend" recursively array $a with array $b values (no deletion in $a, just added and updated values)
	 * For Example: $a contains default values and $b contains current values
	 *
	 * @param array $a
	 * @param array $b
	 * @return array
	 */
	function array_extend($a, $b)
	{
		foreach($b as $k => $v)
		{
			if (is_array($v) === TRUE)
			{
				if (isset($a[$k]) === FALSE)
				{
	                $a[$k] = $v;
	            }
	            else
	            {
	                $a[$k] = $this->array_extend($a[$k], $v);
	            }
	        }
	        else
	        {
	            $a[$k] = $v;
	        }
	    }

	    return $a;
	}

	// ********************************************************************************* //

	/**
     * Function for looking for a value in a multi-dimensional array
     *
     * @param string $value
     * @param array $array
     * @return bool
     */
	function in_multi_array($value, $array)
	{
		foreach ($array as $key => $item)
		{
			// Item is not an array
			if (!is_array($item))
			{
				// Is this item our value?
				if ($item == $value) return TRUE;
			}

			// Item is an array
			else
			{
				// See if the array name matches our value
				//if ($key == $value) return true;

				// See if this array matches our value
				if (in_array($value, $item)) return TRUE;

				// Search this array
				else if ($this->in_multi_array($value, $item)) return TRUE;
			}
		}

		// Couldn't find the value in array
		return FALSE;
	}

	// ********************************************************************************* //

	/**
	 * Get Entry_ID from tag paramaters
	 *
	 * Supports: entry_id="", url_title="", channel=""
	 *
	 * @return mixed - INT or BOOL
	 */
	function get_entry_id_from_param($get_channel_id=FALSE)
	{
		$entry_id = FALSE;
		$channel_id = FALSE;

		$this->EE->load->helper('number');

		if ($this->EE->TMPL->fetch_param('entry_id') != FALSE && $this->is_natural_number($this->EE->TMPL->fetch_param('entry_id')) != FALSE)
		{
			$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		}
		elseif ($this->EE->TMPL->fetch_param('url_title') != FALSE)
		{
			$channel = FALSE;
			$channel_id = FALSE;

			if ($this->EE->TMPL->fetch_param('channel') != FALSE)
			{
				$channel = $this->EE->TMPL->fetch_param('channel');
			}

			if ($this->EE->TMPL->fetch_param('channel_id') != FALSE && $this->is_natural_number($this->EE->TMPL->fetch_param('channel_id')))
			{
				$channel_id = $this->EE->TMPL->fetch_param('channel_id');
			}

			$this->EE->db->select('exp_channel_titles.entry_id');
			$this->EE->db->select('exp_channel_titles.channel_id');
			$this->EE->db->from('exp_channel_titles');
			if ($channel) $this->EE->db->join('exp_channels', 'exp_channel_titles.channel_id = exp_channels.channel_id', 'left');
			$this->EE->db->where('exp_channel_titles.url_title', $this->EE->TMPL->fetch_param('url_title'));
			if ($channel) $this->EE->db->where('exp_channels.channel_name', $channel);
			if ($channel_id) $this->EE->db->where('exp_channel_titles.channel_id', $channel_id);
			$this->EE->db->limit(1);
			$query = $this->EE->db->get();

			if ($query->num_rows() > 0)
			{
				$channel_id = $query->row('channel_id');
				$entry_id = $query->row('entry_id');
				$query->free_result();
			}
			else
			{
				return FALSE;
			}
		}

		if ($get_channel_id != FALSE)
		{
			if ($this->EE->TMPL->fetch_param('channel') != FALSE)
			{
				$channel_id = $this->EE->TMPL->fetch_param('channel_id');
			}

			if ($channel_id == FALSE)
			{
				$this->EE->db->select('channel_id');
				$this->EE->db->where('entry_id', $entry_id);
				$this->EE->db->limit(1);
				$query = $this->EE->db->get('exp_channel_titles');
				$channel_id = $query->row('channel_id');

				$query->free_result();
			}

			$entry_id = array( 'entry_id'=>$entry_id, 'channel_id'=>$channel_id );
		}



		return $entry_id;
	}

	// ********************************************************************************* //

	/**
	 * Fetch data between var pairs
	 *
	 * @param string $open - Open var (with optional parameters)
	 * @param string $close - Closing var
	 * @param string $source - Source
	 * @return string
	 */
    function fetch_data_between_var_pairs($varname='', $source = '')
    {
    	if ( ! preg_match('/'.LD.($varname).RD.'(.*?)'.LD.'\/'.$varname.RD.'/s', $source, $match))
               return;

        return $match['1'];
    }

	// ********************************************************************************* //

	/**
	 * Fetch data between var pairs (including optional parameters)
	 *
	 * @param string $open - Open var (with optional parameters)
	 * @param string $close - Closing var
	 * @param string $source - Source
	 * @return string
	 */
    function fetch_data_between_var_pairs_params($open='', $close='', $source = '')
    {
    	if ( ! preg_match('/'.LD.preg_quote($open).'.*?'.RD.'(.*?)'.LD.'\/'.$close.RD.'/s', $source, $match))
               return;

        return $match['1'];
    }

	// ********************************************************************************* //

	/**
	 * Replace var_pair with final value
	 *
	 * @param string $open - Open var (with optional parameters)
	 * @param string $close - Closing var
	 * @param string $replacement - Replacement
	 * @param string $source - Source
	 * @return string
	 */
	function swap_var_pairs($varname = '', $replacement = '\\1', $source = '')
    {
    	return preg_replace("/".LD.$varname.RD."(.*?)".LD.'\/'.$varname.RD."/s", $replacement, $source);
    }

	// ********************************************************************************* //

	/**
	 * Replace var_pair with final value (including optional parameters)
	 *
	 * @param string $open - Open var (with optional parameters)
	 * @param string $close - Closing var
	 * @param string $replacement - Replacement
	 * @param string $source - Source
	 * @return string
	 */
	function swap_var_pairs_params($open = '', $close = '', $replacement = '\\1', $source = '')
    {
    	return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.'\/'.$close.RD."/s", $replacement, $source);
    }

	// ********************************************************************************* //

	/**
	 * Custom No_Result conditional
	 *
	 * Same as {if no_result} but with your own conditional.
	 *
	 * @param string $cond_name
	 * @param string $source
	 * @param string $return_source
	 * @return unknown
	 */
    function custom_no_results_conditional($cond_name, $source, $return_source=FALSE)
    {
   		if (strpos($source, LD."if {$cond_name}".RD) !== FALSE)
		{
			if (preg_match('/'.LD."if {$cond_name}".RD.'(.*?)'. LD.'\/if'.RD.'/s', $source, $cond))
			{
				return $cond[1];
			}

		}


		if ($return_source !== FALSE)
		{
			return $source;
		}

		return;
    }

	// ********************************************************************************* //

	function mcp_meta_parser($type='js', $url, $name, $package='')
	{
		// -----------------------------------------
		// CSS
		// -----------------------------------------
		if ($type == 'css')
		{
			if ( isset($this->EE->session->cache['DevDemon']['CSS'][$name]) == FALSE )
			{
				$this->EE->cp->add_to_foot('<link rel="stylesheet" href="' . $url . '" type="text/css" media="print, projection, screen" />');
				$this->EE->session->cache['DevDemon']['CSS'][$name] = TRUE;
			}
		}

		// -----------------------------------------
		// Javascript
		// -----------------------------------------
		if ($type == 'js')
		{
			if ( isset($this->EE->session->cache['DevDemon']['JS'][$name]) == FALSE )
			{
				$this->EE->cp->add_to_foot('<script src="' . $url . '" type="text/javascript"></script>');
				$this->EE->session->cache['DevDemon']['JS'][$name] = TRUE;
			}
		}

		// -----------------------------------------
		// Global Inline Javascript
		// -----------------------------------------
		if ($type == 'gjs')
		{
			if ( isset($this->EE->session->cache['DevDemon']['GJS'][$name]) == FALSE )
			{
				$AJAX_url = $this->get_router_url();
				$UPLOAD_url = $this->get_router_url('url', 'upload_file');
				$SIMPLE_FILE_url = $this->get_router_url('url', 'simple_file_url');
				$entry_id = $this->EE->input->get_post('entry_id');
				$channel_id = $this->EE->input->get_post('channel_id');

				$js = "
						var ChannelFiles = ChannelFiles ? ChannelFiles : new Object();
						ChannelFiles.Fields = ChannelFiles.Fields ? ChannelFiles.Fields : new Object();
						ChannelFiles.LANG = ChannelFiles.LANG ? ChannelFiles.LANG : new Object();

						ChannelFiles.AJAX_URL = '{$AJAX_url}';
						ChannelFiles.UPLOAD_URL = '{$UPLOAD_url}';
						ChannelFiles.SIMPLE_FILE_URL = '{$SIMPLE_FILE_url}';
						ChannelFiles.ThemeURL = '" . CHANNELFILES_THEME_URL . "';
						ChannelFiles.site_id = '{$this->site_id}';
						ChannelFiles.entry_id = '{$entry_id}';
						ChannelFiles.channel_id = '{$channel_id}';
					";

				$this->EE->cp->add_to_foot('<script type="text/javascript">' . $js . '</script>');
				$this->EE->session->cache['DevDemon']['GJS'][$name] = TRUE;
			}
		}
	}

	// ********************************************************************************* //

	public function formatDate($format='', $date=0, $localize=true)
    {
    	if (method_exists($this->EE->localize, 'format_date') === true) {
    		return $this->EE->localize->format_date($format, $date, $localize);
    	} else {
    		return $this->EE->localize->decode_date($format, $date, $localize);
    	}
    }

	// ********************************************************************************* //

} // END CLASS

/* End of file channel_files_helper.php  */
/* Location: ./system/expressionengine/third_party/channel_files/libraries/channel_files_helper.php */
