<?php

/**
 * User - Model
 *
 * @package		Solspace:User
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/user
 * @license		http://www.solspace.com/license_agreement
 * @filesource	user/models/user_model.php
 */

class User_model extends CI_Model
{
	/**
	 * Framework object
	 * @var object
	 * @see	__construct
	 */
	protected $EE;

	/**
	 * Cache array
	 * @var array
	 */
	protected $cache = array(
		'channel_ids' => array()
	);

	/**
	 * Site Id
	 * @var integer
	 * @see	__construct
	 */
	public $site_id = 1;

	// --------------------------------------------------------------------

	/**
	 * __construct
	 *
	 * @access	public
	 */

	public function __construct()
	{
		parent::__construct();
		$this->EE =& get_instance();
		$this->site_id = ee()->config->item('site_id');
	}
	//END __construct()


	// --------------------------------------------------------------------

	/**
	 * Get the Preference for the Module for the Current Site
	 *
	 * @access	public
	 * @param	array	Array of Channel/Weblog IDs
	 * @return	array
	 */

	public function get_channel_data_by_channel_array( $channels = array() )
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cache[$cache_name][$cache_hash][$this->site_id]))
		{
			return $this->cache[$cache_name][$cache_hash][$this->site_id];
		}

		$this->cache[$cache_name][$cache_hash][$this->site_id] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$extra = '';

		if (is_array($channels) && count($channels) > 0)
		{
			$extra = " AND c.channel_id IN ('" .
						implode("','", ee()->db->escape_str($channels))."')";
		}

		$query = ee()->db->query(
			"SELECT c.channel_title, c.channel_id, s.site_id, s.site_label
			 FROM exp_channels AS c, exp_sites AS s
			 WHERE s.site_id = c.site_id
			 {$extra}"
		);

		foreach($query->result_array() as $row)
		{
			$this->cache[$cache_name][$cache_hash][
				$this->site_id
			][$row['channel_id']] = $row;
		}

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cache[$cache_name][$cache_hash][$this->site_id];
	}
	// END get_channel_data_by_channel_array


	// --------------------------------------------------------------------

	/**
	 * Get Channel Id Preference
	 *
	 * @access	public
	 * @param	int		$id		channel_id to get info for
	 * @return	array			array of pref data for channel_id
	 */

	function get_channel_id_pref($id)
	{
		//cache?
		if (isset($this->cache['channel_ids'][$id]))
		{
			return $this->cache['channel_ids'][$id];
		}

		$channel_data = $this->get_channel_ids();

		if ( isset($channel_data[$id]) )
		{
			$this->cache['channel_ids'][$id] = $channel_data[$id];
			return $this->cache['channel_ids'][$id];
		}
		else
		{
			return array();
		}
	}
	//END get_channel_id_pref

	// --------------------------------------------------------------------

	/**
	 * Gets channel ids' from preferences
	 *
	 * @access	public
	 * @param	boolean	$use_cache	use cached data or get fresh?
	 * @return 	array				array of channel ids
	 */

	public function get_channel_ids($use_cache = TRUE)
	{
		//cache?
		if ($use_cache AND isset($this->cache['channel_ids']['full']))
		{
			return $this->cache['channel_ids']['full'];
		}

		$query = ee()->db
						->select('preference_value')
						->where('preference_name', 'channel_ids')
						->get('user_preferences');

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$this->cache['channel_ids']['full'] = unserialize(
			$query->row('preference_value')
		);

		return $this->cache['channel_ids']['full'];
	}
	//END get_channel_ids


	function get_sites()
	{
		//--------------------------------------------
		// SuperAdmins Alredy Have All Sites
		//--------------------------------------------

		if (isset(ee()->session) AND
			is_object(ee()->session) AND
			isset(ee()->session->userdata['group_id']) AND
			ee()->session->userdata['group_id'] == 1 AND
			isset(ee()->session->userdata['assigned_sites']) AND
			is_array(ee()->session->userdata['assigned_sites']))
		{
			return ee()->session->userdata['assigned_sites'];
		}

		//--------------------------------------------
		// Prep Cache, Return if Set
		//--------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cache[$cache_name][$cache_hash]))
		{
			return $this->cache[$cache_name][$cache_hash];
		}

		$this->cache[$cache_name][$cache_hash] = array();

		//--------------------------------------------
		// Perform the Actual Work
		//--------------------------------------------

		if (ee()->config->item('multiple_sites_enabled') == 'y')
		{
			$sites_query = ee()->db
							->select('site_id, site_label')
							->order_by('site_label')
							->get('sites');

		}
		else
		{
			$sites_query = ee()->db
							->select('site_id, site_label')
							->where('site_id', 1)
							->get('sites');

		}

		foreach($sites_query->result_array() as $row)
		{
			$this->cache[$cache_name][$cache_hash][$row['site_id']] = $row['site_label'];
		}

		//--------------------------------------------
		// Return Data
		//--------------------------------------------

		return $this->cache[$cache_name][$cache_hash];
	}
	//END get_sites()


	// --------------------------------------------------------------------

	/**
	 * Implodes an Array and Hashes It
	 *
	 * @access	public
	 * @param	array	$args	arguments to hash
	 * @return	string			hashed args
	 */

	public function _imploder ($args)
	{
		return md5(serialize($args));
	}
	// END _imploder
}
//END User_model