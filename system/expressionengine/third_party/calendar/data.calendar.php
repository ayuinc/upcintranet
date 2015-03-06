<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - Data Models
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/data.calendar.php
 */

if ( ! class_exists('Addon_builder_data_calendar'))
{
	require_once 'addon_builder/data.addon_builder.php';
}

class Calendar_data extends Addon_builder_data_calendar
{

	public $cached 			= array();
	public $events_table	= 'exp_calendar_events';
	public $channel;
	public $blog;
	public $static_site_id 	= 0;
	public $date_formats 	= array(
		"mm/dd/yy"	=> array(
			"split"			=> "/",
			"format"		=> "M/D/Y",
			"cdt_format"	=> "m/d/Y",
			"example"		=> "02/29/2012"
		),
		"m/d/yy"	=> array(
			"split"			=> "/",
			"format"		=> "m/d/Y",
			"cdt_format"	=> "n/j/Y",
			"example"		=> "2/29/2012"
		),
		"dd/mm/yy"	=> array(
			"split"			=> "/",
			"format"		=> "D/M/Y",
			"cdt_format"	=> "d/m/Y",
			"example"		=> "29/02/2012"
		),
		"d/m/yy" 	=> array(
			"split"			=> "/",
			"format"		=> "d/m/Y",
			"cdt_format"	=> "j/n/Y",
			"example"		=> "29/2/2012"
		),
		"yy/mm/dd"	=> array(
			"split"			=> "/",
			"format"		=> "Y/M/D",
			"cdt_format"	=> "Y/m/d",
			"example"		=> "2012/02/29"
		),
		"yy/m/d"	=> array(
			"split"			=> "/",
			"format"		=> "Y/m/d",
			"cdt_format"	=> "Y/n/j",
			"example"		=> "2012/2/29"
		),
		"yy/dd/mm" => array(
			"split"			=> "/",
			"format"		=> "Y/D/M",
			"cdt_format"	=> "Y/d/m",
			"example" => "2012/29/02"
		),
		"yy/d/m"	=> array(
			"split"			=> "/",
			"format"		=> "Y/d/m",
			"cdt_format"	=> "Y/j/n",
			"example"		=> "2012/29/2"
		),
		"mm-dd-yy"	=> array(
			"split"			=> "-",
			"format"		=> "M-D-Y",
			"cdt_format"	=> "m-d-Y",
			"example"		=> "02-29-2012"
		),
		"m-d-yy"	=> array(
			"split"			=> "-",
			"format"		=> "m-d-Y",
			"cdt_format"	=> "n-j-Y",
			"example"		=> "2-29-2012"
		),
		"dd-mm-yy"	=> array(
			"split"			=> "-",
			"format"		=> "D-M-Y",
			"cdt_format"	=> "d-m-Y",
			"example"		=> "29-02-2012"
		),
		"d-m-yy"	=> array(
			"split"			=> "-",
			"format"		=> "d-m-Y",
			"cdt_format"	=> "j-n-Y",
			"example"		=> "29-2-2012"
		),
		"yy-mm-dd"	=> array(
			"split"			=> "-",
			"format"		=> "Y-M-D",
			"cdt_format"	=> "Y-m-d",
			"example"		=> "2012-02-29"
		),
		"yy-m-d"	=> array(
			"split"			=> "-",
			"format"		=> "Y-m-d",
			"cdt_format"	=> "Y-n-j",
			"example"		=> "2012-2-29"
		),
		"yy-dd-mm"	=> array(
			"split"			=> "-",
			"format"		=> "Y-D-M",
			"cdt_format"	=> "Y-d-m",
			"example"		=> "2012-29-02"
		),
		"yy-d-m"	=> array(
			"split"			=> "-",
			"format"		=> "Y-d-m",
			"cdt_format"	=> "Y-j-n",
			"example"		=> "2012-29-2"
		),
	);


	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct()
	{
		parent::__construct();

		if ( ! defined('CALENDAR_EVENTS_CHANNEL_NAME_DEFAULT'))
		{
			require_once 'constants.calendar.php';
		}

		$this->events_table = 'exp_' . CALENDAR_EVENTS_CHANNEL_NAME_DEFAULT;
	}
	/* END __construct() */


	// --------------------------------------------------------------------

	/**
	 * custom get site id function that cal needs until its fully MSM compat
	 *
	 * @access 	public
	 * @return  string site_id
	 */

	public function get_site_id ()
	{
		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		//need this in case we are installing on this channel
		$site_id = ee()->config->item('site_id');

		//are we using the static ID option?
		if ($this->static_site_id !== 0)
		{
			$site_id = $this->static_site_id;
		}
		//are we setting from a template?
		else if (	isset(ee()->TMPL) AND
					is_object(ee()->TMPL) AND
					! in_array(ee()->TMPL->fetch_param('site_id'), array(0, '0', FALSE, ''), TRUE) AND
					is_numeric(ee()->TMPL->fetch_param('site_id')) )
		{
			$site_id = ee()->TMPL->fetch_param('site_id');
		}

		//--------------------------------------------
		//	This was too expensive to run on page load
		//--------------------------------------------
		//check and see if we have prefs, but if we are in install mode, we wont, possibly
		else if (ee()->db->table_exists('exp_calendar_preferences'))
		{
			//right now we dont have MSM calendars, so its only going to be installed in one
			$query = ee()->db->query(
			   'SELECT 	preferences
				FROM 	exp_calendar_preferences
				LIMIT	1'
			);

			$data = array();

			//if we have some prefs, check to see which site the channels are already installed in
			if ($query->num_rows() > 0)
			{
				$data = unserialize($query->row('preferences'));
			}
			//if there are no prefs, check default
			else
			{
				if ( ! class_exists('Calendar_upd'))
				{
					require_once CALENDAR_PATH . 'upd.calendar' . EXT;
				}

				$prefs 	= Calendar_upd::_default_preferences();

				$data	= unserialize($prefs);
			}

			//did either give us something we can work with?
			if (isset($data['calendar_weblog']) AND is_numeric($data['calendar_weblog']))
			{
				$query = ee()->db->query(
				   "SELECT 	site_id
					FROM 	{$this->sc->db->channels}
					WHERE	{$this->sc->db->channel_id} = '" .
							ee()->db->escape_str($data['calendar_weblog']) . "'"
				);

				if ($query->num_rows() > 0)
				{
					$site_id = $query->row('site_id');
				}
			}
		}

		$this->cached[$cache_name][$cache_hash] = $site_id;

		return $this->cached[$cache_name][$cache_hash];
	}
	//END get_site_id


	// --------------------------------------------------------------------

	/**
	 * get member groups
	 * the member_model in 2.x can do this for us
	 * but we need to do it ourselves because of the site_id/msm situation
	 *
	 * @access 	public
	 * @param 	bool	force refresh of data?
	 * @return  array 	key value pair of group id=>title
	 */

	public function get_member_groups ($refresh = FALSE)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if ($refresh !== TRUE)
		{
			if (isset($this->cached[$cache_name][$cache_hash]))
			{
				return $this->cached[$cache_name][$cache_hash];
			}
		}
		else
		{
			unset($this->cached[$cache_name]);
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// -------------------------------------
		//	get member groups
		// -------------------------------------

		$m_query = ee()->db->query(
			"SELECT group_id, group_title
			 FROM 	exp_member_groups
			 WHERE 	group_id != 1
			 AND 	site_id = " . ee()->db->escape_str($this->get_site_id())
		);

		if ($m_query->num_rows() > 0)
		{
			$this->cached[$cache_name][$cache_hash] = $this->prepare_keyed_result(
				$m_query,
				'group_id',
				'group_title'
			);
		}

		return $this->cached[$cache_name][$cache_hash];
	}
	//END get_member_groups


	// --------------------------------------------------------------------

	public function get_module_preferences($refresh = FALSE)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if ($refresh !== TRUE)
		{
			if (isset($this->cached[$cache_name][$cache_hash]))
			{
				return $this->cached[$cache_name][$cache_hash];
			}
		}
		else
		{
			unset($this->cached[$cache_name]);
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		if ( ! class_exists('Calendar_upd'))
		{
			require_once CALENDAR_PATH . 'upd.calendar' . EXT;
		}

		$prefs 	= Calendar_upd::_default_preferences();

		$data	= unserialize($prefs);

		//--------------------------------------------
		//	This was too expensive to run on page load
		//--------------------------------------------

		if (ee()->db->table_exists('exp_calendar_preferences'))
		{
			$sql = 'SELECT 	cp.preferences
					FROM 	exp_calendar_preferences cp
					WHERE 	cp.site_id = "' . ee()->db->escape_str($this->get_site_id()) . '"';

			$query = ee()->db->query($sql);

			if ($query->num_rows() > 0)
			{
				$new_data = unserialize($query->row('preferences'));

				foreach ($new_data as $k => $v)
				{
					$data[$k] = $v;
				}

			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	// END module_preferences()


	// --------------------------------------------------------------------

	/**
	 * Return a preference
	 *
	 * @param	string	$which	Which preference
	 * @return
	 */

	public function preference($which)
	{
		$data = $this->get_module_preferences();

		if (isset($data[$which]))
		{
			return $data[$which];
		}

		return FALSE;
	}
	//END preference


	// --------------------------------------------------------------------

	/**
	 * Update preferences
	 *
	 * @param	array	$data	Data to update
	 * @return	null
	 */

	public function update_preferences($data)
	{
		$data = array('preferences' => serialize($data));

		$query = ee()->db
					->where('site_id', $this->get_site_id())
					->get('exp_calendar_preferences');

		if ($query->num_rows() > 0)
		{
			ee()->db->update(
				'exp_calendar_preferences',
				$data,
				array('site_id' => $this->get_site_id())
			);
		}
		else
		{
			$data['site_id'] = $this->get_site_id();
			ee()->db->insert('exp_calendar_preferences', $data);
		}

		$this->get_module_preferences(TRUE);
	}
	// END update_preferences()


	// --------------------------------------------------------------------

	/**
	 * Calendar weblog shortname
	 *
	 * @return string
	 */
	public function calendar_channel_shortname()
	{
		// Fetch the weblog
		$channel 		= $this->preference('calendar_weblog');

		$channel 		= ($channel !== FALSE AND is_string($channel)) ?
								explode('|', $channel) : array();
		$channel_data 	= $this->get_channel_basics();
		$names 			= array();

		foreach ($channel_data as $w)
		{
			if (in_array($w['weblog_id'], $channel))
			{
				$names[] = $w['blog_name'];
			}
		}

		return implode('|', $names);
	}
	// END calendar_channel_shortname()


	// --------------------------------------------------------------------

	/**
	 * Event weblog shortname
	 *
	 * @return string
	 */
	public function event_channel_shortname()
	{
		 // Fetch the weblog
		$channel 		= $this->preference('event_weblog');
		$channel 		= ($channel !== FALSE AND is_string($channel)) ?
							explode('|', $channel) : array();
		$channel_data 	= $this->get_channel_basics();
		$names 			= array();

		foreach ($channel_data as $c)
		{
			if (in_array($c['weblog_id'], $channel))
			{
				$names[] = $c['blog_name'];
			}
		}

		return implode('|', $names);
	}
	/* END event_channel_shortname() */


	// --------------------------------------------------------------------

	/**
	 * Calendars exist?
	 *
	 * @access	public
	 * @return	array
	 */

	public function calendars_exist()
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$sql = 'SELECT 	COUNT(*) AS count
				FROM 	exp_calendar_calendars
				WHERE 	site_id = "' . ee()->db->escape_str($this->get_site_id()) .'"';

		$query = ee()->db->query($sql);

		$this->cached[$cache_name][$cache_hash] = ($query->row('count') == 0) ? FALSE : TRUE;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END calendars_exist() */

	// --------------------------------------------------------------------

	/**
	 * Channel is a Calendars weblog?
	 *
	 * @param	int	$channel_id	Weblog ID
	 * @return	bool
	 */

	public function channel_is_calendars_channel($channel_id)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$blogname_andor = substr(ee()->functions->sql_andor_string(
								ee()->db->escape_str( $this->calendar_channel_shortname() ),
								$this->sc->db->channel_name
							), 4);

		$sql = "SELECT 	{$this->sc->db->channel_id}
				FROM 	{$this->sc->db->channels}
				WHERE 	" . $blogname_andor;

		$query = ee()->db->query($sql);

		$this->cached[$cache_name][$cache_hash] = ($query->num_rows() > 0 AND
				$query->row($this->sc->db->channel_id) == $channel_id) ? TRUE : FALSE;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END weblog_is_calendar() */

	// --------------------------------------------------------------------

	/**
	 * Weblog is a valid calendar
	 *
	 * @param	int	$calendar_id	Weblog/Calendar ID
	 * @return	bool
	 */

	public function channel_is_valid_calendar($calendar_id = '')
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		if ( $calendar_id == '' ) return array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$sql = 'SELECT 	calendar_id
				FROM 	exp_calendar_calendars
				WHERE 	calendar_id = "' . ee()->db->escape_str($calendar_id) .'"';

		$query = ee()->db->query($sql);

		$this->cached[$cache_name][$cache_hash] = ($query->num_rows() > 0) ? TRUE : FALSE;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END weblog_is_calendar() */

	// --------------------------------------------------------------------

	/**
	 * Weblog is events weblog?
	 *
	 * @param	int	$channel_id	Weblog ID
	 * @return	bool
	 */

	public function channel_is_events_channel($channel_id = 0)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$sql = "SELECT 	{$this->sc->db->channel_id}
				FROM 	{$this->sc->db->channels}
				WHERE 	{$this->sc->db->channel_name} = '" . ee()->db->escape_str(CALENDAR_EVENTS_CHANNEL_NAME) . "'";

		$query = ee()->db->query($sql);

		if ($channel_id == 0)
		{
			$this->cached[$cache_name][$cache_hash] = $query->row($this->sc->db->channel_id);
		}
		else
		{
			$this->cached[$cache_name][$cache_hash] = ($query->num_rows() > 0 AND
													   $query->row($this->sc->db->channel_id) == $channel_id) ?
														TRUE : FALSE;
		}

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END weblog_is_calendar() */

	// --------------------------------------------------------------------

	/**
	 * Add a calendar
	 * @param	array	$params	Data to use
	 * @return	int
	 */

	public function add_calendar($id, $site_id, $params)
	{
		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$sql = "SELECT 	*
				FROM	{$this->sc->db->channel_data}
				WHERE 	entry_id = " . ee()->db->escape_str($id);

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$data = array(
			'calendar_id' 	=> $id,
			'site_id' 		=> $site_id
		);

		$row = $query->row_array();

		foreach ($params as $k => $v)
		{
			if (isset($row[$v]))
			{
				$data[$k] = $row[$v];
			}
		}

		ee()->db->query(ee()->db->insert_string('exp_calendar_calendars', $data));

		return $id;
	}
	/* END add_calendar() */


	// --------------------------------------------------------------------

	/**
	 * Update calendar
	 *
	 * @param	array	$params	Array of data
	 * @return	int
	 */

	public function update_calendar($id, $params)
	{
		$sql = "SELECT 	*
				FROM 	{$this->sc->db->channel_data}
				WHERE 	entry_id = " . ee()->db->escape_str($id);

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$data = array();

		$row = $query->row_array();

		foreach ($params as $k => $v)
		{
			if (isset($row[$v]))
			{
				$data[$k] = $row[$v];
			}
			else
			{
				$data[$k] = $v;
			}
		}

		if (! empty($data))
		{
			ee()->db->query(
				ee()->db->update_string(
					'exp_calendar_calendars',
					$data,
					'calendar_id = "'. ee()->db->escape_str($id) . '"'
				)
			);
			return $id;
		}

		return FALSE;
	}
	/* END update_calendar() */


	// --------------------------------------------------------------------

	public function update_time_format_field($id, $tf_field)
	{
		ee()->db->query(
			ee()->db->update_string(
				$this->sc->db->channel_data,
				array(
					$tf_field => $this->preference('time_format')
				),
				'entry_id = '. ee()->db->escape_str($id)
			)
		);
	}
	/* END update_time_format_field() */


	// --------------------------------------------------------------------

	/**
	 * Delete a calendar
	 *
	 * @param	int	$calendar_id	Calendar ID
	 * @return	null
	 */

	public function delete_calendar($calendar_id)
	{
		$clean_cal_id = ee()->db->escape_str($calendar_id);

		//--------------------------------------------
		//	delete event entries
		//--------------------------------------------

		$events_query = ee()->db->query(
			"SELECT 		entry_id
			 FROM 			{$this->events_table}
			 WHERE 			calendar_id = '$clean_cal_id'"
		);

		//remove all events by entry_id
		if ($events_query->num_rows() > 0)
		{
			$ids = array();

			foreach($events_query->result_array() as $row)
			{
				$ids[] = $row['entry_id'];
			}

			//we dont want to delete the parent event on accident here
			ee()->db->query(
				"DELETE FROM 	{$this->sc->db->channel_titles}
				 WHERE 		 	entry_id
				 IN 			(" . implode(',', ee()->db->escape_str($ids)) . ")"
			);

			ee()->db->query(
				"DELETE FROM 	{$this->sc->db->channel_data}
				 WHERE 		 	entry_id
				 IN 			(" . implode(',', ee()->db->escape_str($ids)) . ")"
			);
		}

		// -------------------------------------
		//  Delete all cal data from this cal id
		// -------------------------------------

		$delete_from_table = array(
			'exp_calendar_calendars',
			$this->events_table,
			'exp_calendar_events_rules',
			'exp_calendar_events_occurrences',
			'exp_calendar_events_exceptions',
			'exp_calendar_events_imports'
		);

		foreach ($delete_from_table as $table)
		{
			ee()->db->query(
				"DELETE FROM 	$table
				 WHERE 			calendar_id = '$clean_cal_id'"
			);
		}

	}
	// END delete_calendar()


	// --------------------------------------------------------------------

	/**
	 * Add an event
	 *
	 * @param	array	$params	Data to add
	 * @return	int
	 */

	public function add_event($params)
	{
		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$params['start_time']	= str_pad($params['start_time'], 4, '0', STR_PAD_LEFT);
		$params['end_time']		= str_pad($params['end_time'], 4, '0', STR_PAD_LEFT);

		ee()->db->query(ee()->db->insert_string($this->events_table, $params));

		return ee()->db->insert_id();
	}
	/* END add_event() */

	// --------------------------------------------------------------------

	/**
	 * Update event
	 *
	 * @param	array	$params	Array of data
	 * @return	int
	 */

	public function update_event($params)
	{
		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$entry_id = $params['event_id'];
		unset($params['event_id']);

		$params['start_time']	= str_pad($params['start_time'], 4, '0', STR_PAD_LEFT);
		$params['end_time']		= str_pad($params['end_time'], 4, '0', STR_PAD_LEFT);

		ee()->db->query(ee()->db->update_string($this->events_table, $params, 'entry_id = "'. ee()->db->escape_str($entry_id) .'"'));

		return $params['entry_id'];
	}
	/* END add_event() */

	// --------------------------------------------------------------------

	/**
	 * Add an imported event
	 *
	 * @param	array	$params	Data to use
	 * @return	int
	 */

	public function add_imported_event($params)
	{
		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		ee()->db->query(ee()->db->insert_string('exp_calendar_events_imports', $params));

		return ee()->db->insert_id();
	}
	/* END add_imported_event() */

	// --------------------------------------------------------------------

	/**
	 * Update an imported event
	 *
	 * @param	array	$params	Data to use
	 * @return	int
	 */

	public function update_imported_event($params)
	{
		ee()->db->update(
			'exp_calendar_events_imports',
			$params,
			array('import_id' => $params['import_id'])
		);

		return $params['import_id'];
	}
	/* END add_imported_event() */


	// --------------------------------------------------------------------

	/**
	 * Add a recurrence rule
	 *
	 * @param	array	$params	Array of data
	 * @return	int
	 */

	public function add_rule($params)
	{
		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$params['start_time']	= str_pad($params['start_time'], 4, '0', STR_PAD_LEFT);
		$params['end_time']		= str_pad($params['end_time'], 4, '0', STR_PAD_LEFT);

		ee()->db->query(ee()->db->insert_string('exp_calendar_events_rules', $params));

		return ee()->db->insert_id();
	}
	/* END add_rule() */


	// --------------------------------------------------------------------

	/**
	 * Add an occurrence
	 *
	 * @param	array	$params	Array of data
	 * @return	int
	 */

	public function add_occurrence($params)
	{
		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$params['start_time']	= str_pad($params['start_time'], 4, '0', STR_PAD_LEFT);
		$params['end_time']		= str_pad($params['end_time'], 4, '0', STR_PAD_LEFT);

		ee()->db->query(ee()->db->insert_string('exp_calendar_events_occurrences', $params));

		return ee()->db->insert_id();
	}
	/* END add_occurrence() */

	// --------------------------------------------------------------------

	/**
	 * Add an exception
	 *
	 * @param	array	$params	Array of data
	 * @return	int
	 */

	public function add_exception($params)
	{
		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		ee()->db->query(ee()->db->insert_string('exp_calendar_events_exceptions', $params));

		return ee()->db->insert_id();
	}
	/* END add_exception() */

	// --------------------------------------------------------------------

	/**
	 * Update a recurrence rule
	 *
	 * @param	array	$params	Array of data
	 * @return	mull
	 */

	public function update_rule($params)
	{
		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$params['start_time']	= str_pad($params['start_time'], 4, '0', STR_PAD_LEFT);
		$params['end_time']		= str_pad($params['end_time'], 4, '0', STR_PAD_LEFT);

		ee()->db->query(
			ee()->db->update_string(
				'exp_calendar_events_rules',
				$params,
				'rule_id = "'. ee()->db->escape_str($params['rule_id']) .'"'
			)
		);

		return;
	}
	/* END update_rule() */


	// --------------------------------------------------------------------

	/**
	 * Delete a recurrence rule
	 *
	 * @param	int	$rule_id	Rule ID
	 * @return	null
	 */

	public function delete_rule($rule_id)
	{
		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		ee()->db->query(
			'DELETE FROM 	exp_calendar_events_rules
			 WHERE 			rule_id = "'. ee()->db->escape_str($rule_id) .'"'
		);

		return;
	}
	/* END delete_rule() */

	// --------------------------------------------------------------------

	/**
	 * Update an occurrence
	 *
	 * @param	array	$params	Array of data
	 * @return	null
	 */

	public function update_occurrence($params)
	{
		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$params['start_time']	= str_pad($params['start_time'], 4, '0', STR_PAD_LEFT);
		$params['end_time']		= str_pad($params['end_time'], 4, '0', STR_PAD_LEFT);

		ee()->db->query(
			ee()->db->update_string(
				'exp_calendar_events_occurrences',
				$params,
				'occurrence_id = "'. ee()->db->escape_str($params['occurrence_id']) .'"'
			)
		);

		return;
	}
	/* END update_occurrence() */

	// --------------------------------------------------------------------

	/**
	 * Add an exception
	 *
	 * @param	array	$params	Array of data
	 * @return	null
	 */

	public function update_exception($params)
	{
		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		ee()->db->query(
			ee()->db->update_string(
				'exp_calendar_events_exceptions',
				$params,
				'exception_id = "' . ee()->db->escape_str($params['exception_id']) .'"'
			)
		);

		return;
	}
	/* END update_exception() */

	// --------------------------------------------------------------------

	/**
	 * Update the last date of an event
	 *
	 * @param	int	$event_id	Event ID
	 * @return	null
	 */

	public function update_last_date($event_id)
	{
		$last_date = FALSE;

		// -------------------------------------
		//  First look for rules
		// -------------------------------------

		$sql = "	SELECT 		last_date
					FROM 		exp_calendar_events_rules
					WHERE 		event_id = " . ee()->db->escape_str($event_id) ."
					ORDER BY 	last_date DESC
					LIMIT 		1";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			$last_date = $query->row('last_date');
		}

		if ($last_date === FALSE)
		{
			// -------------------------------------
			//  Look for occurrences
			// -------------------------------------

			$sql = "	SELECT 		end_date
						FROM 		exp_calendar_events_occurrences
						WHERE 		event_id = " . ee()->db->escape_str($event_id) . "
						ORDER BY	end_date DESC
						LIMIT 		1";

			$query = ee()->db->query($sql);

			if ($query->num_rows() > 0)
			{
				$last_date = $query->row('end_date');
			}
		}

		if ($last_date === FALSE)
		{
			// -------------------------------------
			//  Fetch the end date
			// -------------------------------------

			$sql = "	SELECT 	end_date
						FROM 	{$this->events_table}
						WHERE 	event_id = " . ee()->db->escape_str($event_id);

			$query = ee()->db->query($sql);

			if ($query->num_rows() > 0)
			{
				$last_date = $query->row('end_date');
			}
		}

		// -------------------------------------
		//  Update
		// -------------------------------------

		ee()->db->query(
			ee()->db->update_string(
				$this->events_table,
				array(
					'last_date' => ($last_date === FALSE) ? 0 : $last_date
				),
				"entry_id = ". ee()->db->escape_str($event_id)
			)
		);
	}
	/* END update_last_date() */

	// --------------------------------------------------------------------

	/**
	 * Delete event
	 *
	 * @param	int	$entry_id	Entry ID
	 * @return	null
	 */

	public function delete_event($entry_id)
	{
		// -------------------------------------
		//  Delete the event
		// -------------------------------------

		ee()->db->query(
			"DELETE FROM 	{$this->events_table}
			 WHERE 			entry_id = '". ee()->db->escape_str($entry_id) . "'"
		);

		// -------------------------------------
		//  Delete the rules
		// -------------------------------------

		ee()->db->query(
			'DELETE FROM 	exp_calendar_events_rules
			 WHERE 			event_id = "'. ee()->db->escape_str($entry_id) .'"'
		);

		// -------------------------------------
		// Delete the occurrences
		// -------------------------------------

		ee()->db->query(
			'DELETE FROM 	exp_calendar_events_occurrences
			 WHERE 			event_id = "'. ee()->db->escape_str($entry_id) .'"'
		);

		ee()->db->query(
			'UPDATE 		exp_calendar_events_occurrences
			 SET 			entry_id = event_id
			 WHERE 			entry_id = "'. ee()->db->escape_str($entry_id) .'"'
		);

		// -------------------------------------
		// Delete the exceptions
		// -------------------------------------

		ee()->db->query(
			'DELETE FROM 	exp_calendar_events_exceptions
			 WHERE 			event_id = "'. ee()->db->escape_str($entry_id) .'"'
		);

		// -------------------------------------
		// Delete the imports
		// -------------------------------------

		ee()->db->query(
			'DELETE FROM 	exp_calendar_events_imports
			 WHERE 			event_id = "'. ee()->db->escape_str($entry_id) .'"'
		);
	}
	/* END delete_event() */

	// --------------------------------------------------------------------

	/**
	 * Fetch occurrences by event ID
	 *
	 * @param	int		$event_id		Event ID
	 * @param	array	$range_start	Array of start date info [optional]
	 * @param	array	$range_end		Array of end date info [optional]
	 * @return	array
	 */

	public function fetch_occurrences_by_event_id($event_id, $range_start = array(), $range_end = array())
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// -------------------------------------
		//  Prep the > and < strings
		// -------------------------------------

		$start_str = $end_str = '';

		if (! empty($range_start))
		{
			$start_str = ' AND start_date >= ' . ee()->db->escape_str($range_start['year']) .
												 ee()->db->escape_str($range_start['month']) .
												 ee()->db->escape_str($range_start['day']);
		}

		if (! empty($range_end))
		{
			$end_str = ' AND end_date <= ' . ee()->db->escape_str($range_end['year']) .
											 ee()->db->escape_str($range_end['month']) .
											 ee()->db->escape_str($range_end['day']);
		}

		// --------------------------------------------
		//  Go Fetch
		// --------------------------------------------

		$sql = '	SELECT 	*
					FROM 	exp_calendar_events_occurrences
					WHERE 	event_id
					IN 		(' . ee()->db->escape_str(str_replace('|', ', ', $event_id)) .')'
					. $start_str
					. $end_str;

		$query = ee()->db->query($sql);

		$result = array();

		foreach ($query->result_array() as $row)
		{
			$result[$row['event_id']][] = $row;
		}

		$this->cached[$cache_name][$cache_hash] = $result;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_occurrences_by_event_id() */


	// --------------------------------------------------------------------

	/**
	 * Fetch occurrences by event ID
	 *
	 * @param	int		$event_id		Event ID
	 * @param	array	$range_start	Array of start date info [optional]
	 * @param	array	$range_end		Array of end date info [optional]
	 * @return	array
	 */

	public function fetch_rules_by_event_id($event_id, $range_start = array(), $range_end = array())
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// -------------------------------------
		//  Prep the > and < strings
		// -------------------------------------

		$start_str = $end_str = '';

		if (! empty($range_start))
		{
			$start_str = ' AND start_date >= ' . ee()->db->escape_str($range_start['year']) .
												 ee()->db->escape_str($range_start['month']) .
												 ee()->db->escape_str($range_start['day']);
		}

		if (! empty($range_end))
		{
			$end_str = ' AND end_date <= ' . ee()->db->escape_str($range_end['year']) .
											 ee()->db->escape_str($range_end['month']) .
											 ee()->db->escape_str($range_end['day']);
		}

		// --------------------------------------------
		//  Go Fetch
		// --------------------------------------------

		$sql = '	SELECT 	*
					FROM 	exp_calendar_events_rules
					WHERE 	event_id
					IN 		(' . ee()->db->escape_str(str_replace('|', ', ', $event_id)) .')'
					. $start_str
					. $end_str;

		$query = ee()->db->query($sql);

		$result = array();

		foreach ($query->result_array() as $row)
		{
			if (! isset($result[$row['event_id']]))
			{
				$result[$row['event_id']]['add'] = array();
				$result[$row['event_id']]['sub'] = array();
			}
			$type = ($row['rule_type'] == '-') ? 'sub' : 'add';
			$result[$row['event_id']][$type][$row['rule_id']] = $row;
		}

		$this->cached[$cache_name][$cache_hash] = $result;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_rules_by_event_id() */

	// --------------------------------------------------------------------

	/**
	 * Fetch event data
	 *
	 * @param	int		$entry_id	Entry ID [optional]
	 * @param	string	$url_title	URL title [optional]
	 * @return	array
	 */

	public function fetch_full_event_data($entry_id = 0, $url_title = '')
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// -------------------------------------
		//  Construct the query
		// -------------------------------------

		$sql = "SELECT 		wt.*, ce.*
				FROM		{$this->sc->db->channle_titles} AS wt
				LEFT JOIN 	{$this->events_table} AS ce
				ON 			wt.entry_id = ce.entry_id
				WHERE ";

		if ($entry_id > 0)
		{
			$sql .= ' wt.entry_id IN ('. ee()->db->escape_str(str_replace('|', ', ', $entry_id)) .')';
		}
		elseif ($url_title != '')
		{
			$sql .= ' wt.entry_id IN ("'. ee()->db->escape_str(str_replace('|', '", "', $url_title)) .'")';
		}
		else
		{
			// -------------------------------------
			//  We have to have either an entry_id or a url_title
			// -------------------------------------

			return $this->cached[$cache_name][$cache_hash];
		}

		// -------------------------------------
		//  Prepare to continue
		// -------------------------------------

		$query 				= ee()->db->query($sql);
		$data 				= array();
		$event_ids_string 	= '';

		foreach ($query->result_array() as $k => $row)
		{
			$event_ids_string 						.= ($k == 0) ? $row['event_id'] : ',' . $row['event_id'];
			$data[$row['event_id']] 				= $row;
			$data[$row['event_id']]['occurrences'] 	= array();
			$data[$row['event_id']]['rules'] 		= array();
		}

		// -------------------------------------
		//  Grab associated occurrences
		// -------------------------------------

		if ($event_ids_string != '')
		{
			$occurrences = $this->fetch_occurrences_by_event_id($event_ids_string);
			foreach ($occurrences as $event => $event_occurrences)
			{
				$data[$event]['occurrences'] = $event_occurrences;
			}
		}

		// -------------------------------------
		//  Grab associated rules
		// -------------------------------------

		if ($event_ids_string != '')
		{
			$rules = $this->fetch_rules_by_event_id($event_ids_string);

			foreach ($rules as $event => $event_rules)
			{
				$data[$event]['rules'] = $event_rules;
			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_full_event_data() */


	// --------------------------------------------------------------------

	/**
	 * Return a list of calendars
	 *
	 * @return	array
	 */

	public function get_calendar_list()
	{
		// TODO
		// *	Different lists need to be returned depending on whether
		//		the user wants to view the list of calendars or add an
		//		event to the calendars, because of permissions.

		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// -------------------------------------
		//  Construct the query
		// -------------------------------------

		$sql = "SELECT 		cc.calendar_id, wt.title
				FROM 		exp_calendar_calendars cc
				LEFT JOIN 	{$this->sc->db->channel_titles} wt
				ON 			cc.calendar_id = wt.entry_id
				WHERE 		wt.status != 'closed'
				ORDER BY 	wt.title
				ASC ";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			$this->cached[$cache_name][$cache_hash] = $this->prepare_keyed_result(
				$query,
				'calendar_id'
			);
		}

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END get_calendar_list() */


	// --------------------------------------------------------------------

	public function get_calendars_by_site_id($site_id)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// -------------------------------------
		//  Construct the query
		// -------------------------------------

		$data = array();

		if (is_array($site_id))
		{
			$site_id = implode(', ', $site_id);
		}

		$sql = '	SELECT 	calendar_id
					FROM 	exp_calendar_calendars
					WHERE 	site_id
					IN 		('. ee()->db->escape_str($site_id) .')
					AND 	ics_url != ""';

		$query = ee()->db->query($sql);

		foreach ($query->result_array() as $row)
		{
			$data[] = $row['calendar_id'];
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END get_calendars_by_site_id() */

	// --------------------------------------------------------------------

	public function get_calendars_needing_update($ids, $minutes, $status = 'open')
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// -------------------------------------
		//  Construct the query
		// -------------------------------------

		$data 		= array();
		$ids 		= implode(', ', $ids);
		$statuses	 = '"' . str_replace('|', '", "', ee()->db->escape_str($status)) . '"';

		$sql = "SELECT 		cc.calendar_id
				FROM 		exp_calendar_calendars AS cc
				LEFT JOIN 	{$this->sc->db->channel_titles} AS wt
				ON 			cc.calendar_id = wt.entry_id
				WHERE 		cc.calendar_id
				IN 			('" . ee()->db->escape_str($ids) . "')
				AND 		wt.status
				IN 			($statuses)
				AND 		cc.ics_url != ''
				AND 		(NOW() - DATE_SUB(NOW(), INTERVAL " . ee()->db->escape_str($minutes) . " MINUTE)) > 0 ";

		$query = ee()->db->query($sql);

		foreach ($query->result_array() as $row)
		{
			$data[] = $row['calendar_id'];
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END get_calendars_needing_update() */

	// --------------------------------------------------------------------

	public function update_ics_updated($calendar_id)
	{
		ee()->db->query(
			'UPDATE exp_calendar_calendars
			 SET 	ics_updated = NOW()
			 WHERE 	calendar_id = '. ee()->db->escape_str($calendar_id)
		);
	}
	/* END update_ics_updated() */


	// --------------------------------------------------------------------

	/**
	 * Event exists?
	 *
	 * @access	public
	 * @param	id		Entry ID
	 * @return	bool
	 */

	public function event_exists($id)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$sql = "SELECT 	entry_id
				FROM 	{$this->events_table}
				WHERE 	entry_id = '" . ee()->db->escape_str($id) . "'";

		$query = ee()->db->query($sql);

		$this->cached[$cache_name][$cache_hash] = ($query->num_rows() == 0) ? FALSE : $query->row('entry_id');

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END event_exists() */

	// --------------------------------------------------------------------

	/**
	 * Fetch event data for views
	 *
	 * @param	int	$id
	 * @return	array
	 */

	public function fetch_event_data_for_view($id)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();

		$sql = "SELECT 	entry_id,
						event_id,
						calendar_id,
						start_date,
						end_date,
						all_day,
						start_time,
						end_time,
						recurs
				FROM 	{$this->events_table}
				WHERE 	entry_id = '" . ee()->db->escape_str($id) . "'";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			$data = $query->row_array();

			if ($data['recurs'] == 'y')
			{
				$rsql = 'SELECT 	rule_id,
									rule_type,
									start_date,
									all_day,
									start_time,
									end_date,
									end_time,
									repeat_years,
									repeat_months,
									repeat_days,
									repeat_weeks,
									days_of_week,
									relative_dow,
									days_of_month,
									months_of_year,
									stop_by,
									stop_after
						FROM 		exp_calendar_events_rules
						WHERE 		entry_id = ' . ee()->db->escape_str($id) . '
						ORDER BY 	rule_type ASC, rule_id ASC, start_date ASC';

				$rquery = ee()->db->query($rsql);

				foreach ($rquery->result_array() as $row)
				{
					$data['rules'][$row['rule_id']] = $row;
				}

				$osql = 'SELECT 	*
						 FROM 		exp_calendar_events_occurrences
						 WHERE 		entry_id = ' . ee()->db->escape_str($id) . '
						 ORDER BY 	start_date ASC, start_time ASC, end_time ASC';

				$oquery = ee()->db->query($osql);

				foreach ($oquery->result_array() as $row)
				{
					$start_time = $row['start_time']; //($row['all_day'] == 'y') ? '0000' : $row['start_time'];
					$end_time 	= $row['end_time'];   //($row['all_day'] == 'y') ? '2400' : $row['end_time'];

					$data['occurrences'][$start_time][$end_time][] = $row;
				}

				$esql = 'SELECT 	*
						 FROM 		exp_calendar_events_exceptions
						 WHERE 		entry_id = '. ee()->db->escape_str($id) .'
						 ORDER BY 	start_date ASC';

				$equery = ee()->db->query($esql);

				foreach ($equery->result_array() as $row)
				{
					$data['exceptions'][] = $row;
				}
			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_event_data_for_view() */

	// --------------------------------------------------------------------

	/**
	 * Fetch occurrences by event
	 *
	 * @param	int		$id	Event ID
	 * @return	array
	 */

	public function fetch_occurrences_by_event($id)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();

		$query = ee()->db->query('	SELECT occurrence_id, start_date, start_time, end_time
								FROM exp_calendar_events_occurrences
								WHERE event_id = '. ee()->db->escape_str($id));

		foreach ($query->result_array() as $row)
		{
			$data[$row['start_date']] = $row['occurrence_id'];
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_occurrences_by_event() */

	// --------------------------------------------------------------------

	/**
	 * Fetch exceptions by event
	 *
	 * @param	int		$id	Event ID
	 * @return	array
	 */

	public function fetch_exceptions_by_event($id)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();

		$query = ee()->db->query(
			'SELECT exception_id, start_date
			 FROM 	exp_calendar_events_exceptions
			 WHERE 	event_id = ' . ee()->db->escape_str($id)
		);

		foreach ($query->result_array() as $row)
		{
			$data[$row['start_date']] = $row['exception_id'];
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_exceptions_by_event() */

	// --------------------------------------------------------------------

	/**
	 * Remove rule
	 *
	 * @param	int	$id	Rule ID
	 * @return	null
	 */

	public function remove_rule($id)
	{
		ee()->db->query(
			'DELETE FROM 	exp_calendar_events_rules
			 WHERE 			rule_id = '. ee()->db->escape_str($id) . '
			 LIMIT 			1'
		);
	}
	/* END remove_rule() */

	// --------------------------------------------------------------------

	/**
	 * Remove occurrence
	 *
	 * @param	int	$id	Occurrence ID
	 * @return	null
	 */

	public function remove_occurrence($id)
	{
		ee()->db->query(
			'DELETE FROM 	exp_calendar_events_occurrences
			 WHERE 			occurrence_id = '. ee()->db->escape_str($id) . '
			 LIMIT 			1'
		);
	}
	/* END remove_occurrence() */

	// --------------------------------------------------------------------

	/**
	 * Remove exception
	 *
	 * @param	int	$id	Exception ID
	 * @return	null
	 */

	public function remove_exception($id)
	{
		ee()->db->query(
			'DELETE FROM 	exp_calendar_events_exceptions
			 WHERE 			exception_id = '. ee()->db->escape_str($id) . '
			 LIMIT 			1'
		);
	}
	/* END remove_exception() */

	// --------------------------------------------------------------------

	/**
	 * Remove rules by event id
	 *
	 * @param	int	$id	Event ID
	 * @return	null
	 */

	public function remove_rules_by_event_id($id, $not = array())
	{
		$not_sql  = (! empty($not)) ? ' AND rule_id NOT IN ('. ee()->db->escape_str(implode(', ', $not)) .')' : '';

		ee()->db->query(
			'DELETE FROM exp_calendar_events_rules
			 WHERE 		 event_id = '. ee()->db->escape_str($id) . $not_sql
		);
	}
	/* END remove_rules_by_event_id() */


	// --------------------------------------------------------------------

	/**
	 * Remove occurrences by event id
	 *
	 * @access	public
	 * @param	int	$id	Event ID
	 * @return	null
	 */

	public function remove_occurrences_by_event_id($id)
	{
		$query 		= ee()->db->query(
			"SELECT entry_id
			 FROM 	exp_calendar_events_occurrences
			 WHERE  event_id = " . ee()->db->escape_str($id)
		);

		//need to remove all associated event_ids unless its the master event
		if ($query->num_rows() > 0)
		{
			$ids = array();
			foreach($query->result_array() as $row)
			{
				$ids[] = $row['entry_id'];
			}

			//we dont want to delete the parent event on accident here
			ee()->db->query(
				"DELETE FROM 	{$this->sc->db->channel_titles}
				 WHERE 		 	entry_id
				 IN 			(" . implode(',', $ids) . ")
				 AND			entry_id != " . ee()->db->escape_str($id)
			);

			ee()->db->query(
				"DELETE FROM 	{$this->sc->db->channel_data}
				 WHERE 		 	entry_id
				 IN 			(" . implode(',', $ids) . ")
				 AND			entry_id != " . ee()->db->escape_str($id)
			);
		}

		ee()->db->query(
			'DELETE FROM exp_calendar_events_occurrences
			 WHERE 		 event_id = '. ee()->db->escape_str($id)
		);
	}
	/* END remove_occurrences_by_event_id() */

	// --------------------------------------------------------------------

	/**
	 * Remove exceptions by event id
	 *
	 * @param	int	$id	Event ID
	 * @return	null
	 */

	public function remove_exceptions_by_event_id($id, $not = array())
	{
		$not_sql  = (! empty($not)) ? ' AND exception_id NOT IN ('. ee()->db->escape_str(implode(', ', $not)) .')' : '';

		ee()->db->query('DELETE FROM exp_calendar_events_exceptions WHERE event_id = '. ee()->db->escape_str($id) . $not_sql);
	}
	/* END remove_exceptions_by_event_id() */

	// --------------------------------------------------------------------

	/**
	 * Fetch some calendar basics
	 *
	 * @param	int		$site_id		Site ID
	 * @param	string	$calendar_id	Calendar ID [optional]
	 * @return	array
	 */

	public function fetch_calendars_basics($site_id, $calendar_id = '', $not = FALSE)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$sql = '/* Calendar Module fetch_calendars_data */ ';

		$sql .= "	SELECT cc.*
					FROM exp_calendar_calendars cc
					WHERE ";

		// -------------------------------------
		//  Site ID
		// -------------------------------------

		$sql .= "cc.site_id IN (". str_replace('|', ', ', ee()->db->escape_str($site_id)) .")\n";

		// -------------------------------------
		//  Calendar ID
		// -------------------------------------

		if ($calendar_id != '')
		{
			$not = ($not === TRUE) ? 'NOT' : '';
			$sql .= "AND cc.calendar_id {$not} IN (". str_replace('|', ', ', ee()->db->escape_str($calendar_id)) .")\n";
		}

		// -------------------------------------
		//  Do it.
		// -------------------------------------

		$query = ee()->db->query($sql);

		$this->cached[$cache_name][$cache_hash] = $query->result_array();

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_calendars_basics() */

	// --------------------------------------------------------------------

	/**
	 * Fetch calendars with events in date range
	 *
	 * @param	int		$min		Minimum date [optional]
	 * @param	int		$max		Maximum date [optional]
	 * @param	array	$calendars	Array of calendars [optional]
	 * @param	array	$status 	Array of statuses[optional]
	 * @return	array
	 */

	public function fetch_calendars_with_events_in_date_range($min = '', $max = '', $calendars = array(), $status = '')
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();
		$calendar_string	= implode(', ', $calendars);
		$status_string		= str_replace('|', "', ", $status);
		$status_not			= '';

		if (strtolower(substr($status_string, 0, 4)) == 'not ')
		{
			$status_not		= 'NOT';
			$status_string	= substr($status_string, 4);
		}

		$min = ee()->db->escape_str($min);
		$max = ee()->db->escape_str($max);

		// -------------------------------------
		//  First let's try the easy way out
		// -------------------------------------

		if (! empty($calendars))
		{
			$wheres = array();

			if ($calendar_string != '')
			{
				$wheres[] = "calendar_id IN (". ee()->db->escape_str($calendar_string) .")\n";
			}

			if ($status_string != '')
			{
				$wheres[] = "ct.status {$status_not} IN ('". ee()->db->escape_str($status_string) ."')\n";
			}

			if ($min != '' AND $max != '')
			{
				$wheres[] = "((end_date >= {$min} AND start_date <= {$max}) OR
							  (end_date = '' AND start_date >= $min AND start_date <= $max))\n";
			}
			elseif ($min != '')
			{
				$wheres[] = "(end_date >= {$min} OR start_date >= {$min})\n";
			}
			elseif ($max != '')
			{
				$wheres[] = "start_date <= {$max}\n";
			}

			$where = (! empty($wheres)) ? 'WHERE '. implode(" AND ", $wheres) . " " : '';

			$sql = "/* Calendar Module fetch_calendars_with_events_in_date_range */\n";

			$sql .= "SELECT 	ce.calendar_id
					 FROM 		{$this->events_table} ce
					 LEFT JOIN 	{$this->sc->db->channel_titles} AS ct
					 ON 		ce.entry_id = ct.entry_id ";

			$sql .= $where;

			$sql .= "GROUP BY calendar_id ";

			$sql .= "UNION ";

			$sql .= "SELECT 	ceo.calendar_id
					 FROM 		exp_calendar_events_occurrences ceo
					 LEFT JOIN 	{$this->sc->db->channel_titles} AS ct
					 ON 		ceo.entry_id = ct.entry_id ";

			$sql .= $where;

			$sql .= "GROUP BY calendar_id ";

			$query = ee()->db->query($sql);

			// -------------------------------------
			//  Got data?
			// -------------------------------------

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$data[] = $row['calendar_id'];
					$calendars = $this->array_remove_value($calendars, $row['calendar_id']);
				}
			}
		}

		// -------------------------------------
		//  If $calendars is still not empty, we have to go calculate some rules
		// -------------------------------------

		if ( ! empty($calendars))
		{

			$calendars = $this->fetch_calendars_with_events_in_date_range_by_rule($min, $max, $calendars, $status_string);
			foreach ($calendars as $calendar)
			{
				$data[] = $calendar;
			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_calendars_with_events_in_date_range() */

	// --------------------------------------------------------------------

	/**
	 * Fetch calendars with events in date range by rule
	 *
	 * @param	int		$min		Minimum date [optional]
	 * @param	int		$max		Maximum date [optional]
	 * @param	array	$calendars	Array of calendars [optional]
	 * @param	string	$status 	String of statuses[optional]
	 * @return	array
	 */

	public function fetch_calendars_with_events_in_date_range_by_rule($min = '', $max = '', $calendars = array(), $status_string = '')
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();
		$calendar_string	= implode(', ', $calendars);
		$status_not			= '';
		if (strtolower(substr($status_string, 0, 4)) == 'not ')
		{
			$status_not		= 'NOT';
			$status_string	= substr($status_string, 4);
		}
		$count = 0;

		$min = ee()->db->escape_str($min);
		$max = ee()->db->escape_str($max);

		$sql = ""; //"/* Calendar Module fetch_calendars_with_events_in_date_range_by_rule */\n";

		$sql .= "SELECT 	cer.*
				 FROM 		exp_calendar_events_rules cer
				 LEFT JOIN 	{$this->sc->db->channel_titles} AS wt
				 ON 		cer.entry_id = wt.entry_id ";

		$wheres = array();

		if ($calendar_string != '')
		{
			$wheres[] = "calendar_id IN (". ee()->db->escape_str($calendar_string) .")\n";
		}

		if ($status_string != '')
		{
			$wheres[] = "wt.status {$status_not} IN ('". ee()->db->escape_str($status_string) ."')\n";
		}

		if ($min != '' AND $max != '')
		{
			$wheres[] = "((end_date >= {$min} AND start_date <= {$max}) OR
						  (end_date = '' AND start_date >= $min AND start_date <= $max))\n";
		}
		elseif ($min != '')
		{
			$wheres[] = "(end_date >= {$min} OR start_date >= {$min})\n";
		}
		elseif ($max != '')
		{
			$wheres[] = "start_date <= {$max}\n";
		}

		$wheres[] = "rule_type = '+'";

		if (! empty($wheres))
		{
			$sql .= 'WHERE '. implode(" AND ", $wheres);
		}

		$sql .= " ORDER BY start_date DESC, end_date ASC";

		$new_calendars = array();

		do {
			// -------------------------------------
			//  Do it in 500 item chunks
			// -------------------------------------

			$sql_l = $sql . "\n LIMIT {$count}, 500";
			$count += 500;

			$query = ee()->db->query($sql_l);

			if ($query->num_rows() > 0)
			{
				// -------------------------------------
				//  Calculate repetitions for each item until we find one
				//  that occurs within the range. We need to find one for
				//  each calendar. We also need to take into account
				//  exceptions. Sounds like fun, yeah?
				// -------------------------------------

				foreach ($query->result_array() as $row)
				{
					// -------------------------------------
					//  If $calendars is empty, we can leave
					// -------------------------------------

					if (empty($calendars))
					{
						break(2);
					}

					// -------------------------------------
					//  If we already know this calendar has an event, skip the row
					// -------------------------------------

					if (isset($new_calendars[$row['calendar_id']]))
					{
						continue;
					}

					// -------------------------------------
					//  Shortcuts to avoid having to calculate recurrences
					// -------------------------------------

					if ($min != '' AND $max != '' AND $row['start_date'] >= $min AND $row['start_date'] <= $max)
					{
						// -------------------------------------
						//  If start_date is between min and max, it's a keeper
						// -------------------------------------

						$new_calendars[$row['calendar_id']] = $row['calendar_id'];
						$calendars = $this->array_remove_value($calendars, $row['calendar_id']);
						continue;
					}
					elseif ($min != '' AND $max == '' AND $row['start_date'] >= $min)
					{
						// -------------------------------------
						//  If start_date is >= min and max is null, it's a keeper
						// -------------------------------------

						$new_calendars[$row['calendar_id']] = $row['calendar_id'];
						$calendars = $this->array_remove_value($calendars, $row['calendar_id']);
						continue;
					}

					// -------------------------------------
					//  Fire up the Event class
					// -------------------------------------

					if ( ! class_exists('Calendar_event'))
					{
						require_once CALENDAR_PATH.'calendar.event.php';
					}

					$data = array(
						'rules'	=> array('add' => array($row), 'sub' => array()),
						'start_date' => $min,
						'end_date' => $max,
						'recurs' => 'y'
					);

					$EVENT = new Calendar_event($data, $min, $max, 1);

					if (! empty($EVENT->dates))
					{
						$new_calendars[$row['calendar_id']] = $row['calendar_id'];
						$calendars = $this->array_remove_value($calendars, $row['calendar_id']);
						continue;
					}
				}

				// -------------------------------------
				//  If $calendars is empty, we can leave
				// -------------------------------------

				if (empty($calendars))
				{
					break;
				}
			}

		} while ($query->num_rows() == 500);

		$this->cached[$cache_name][$cache_hash] = $new_calendars;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_calendars_with_events_in_date_range_by_rule() */


	// --------------------------------------------------------------------

	/**
	 * Get calendar ID from name
	 *
	 * @param	string	$name		Calendar name
	 * @param	int		$site_id	Site ID [optional]
	 * @return	array
	 */

	public function get_calendar_id_from_name($name = '', $site_id = 0, $not = FALSE)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		if ( $name == '' ) return array();

		$not 		= ($not === TRUE) ? 'NOT' : '';
		$name_str 	= str_replace('|', "', '", ee()->db->escape_str($name));

		if ($site_id == 0)
		{
			$site_id = $this->get_site_id();
		}

		$sql = "SELECT 	entry_id
				FROM 	{$this->sc->db->channel_titles}
				WHERE 	site_id = " . ee()->db->escape_str($site_id) . "
				AND 	url_title
				$not IN ('$name_str')";

		$query = ee()->db->query($sql);

		$ids = array();
		foreach ($query->result_array() as $row)
		{
			$ids[$row['entry_id']] = $row['entry_id'];
		}

		$this->cached[$cache_name][$cache_hash] = $ids;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END get_calendar_id_from_name() */


	// --------------------------------------------------------------------

	/**
	 * Get calendar ID from name
	 *
	 * @param	int		$entry_id	Entry ID
	 * @return	array
	 */

	public function get_event_entry_id_by_channel_entry_id($entry_id)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$sql = "SELECT 	ce.entry_id
				FROM 	{$this->events_table} ce
				WHERE 	entry_id = '" . ee()->db->escape_str($entry_id) . "'";

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0)
		{
			$sql = 'SELECT 	ceo.event_id AS entry_id
					FROM 	exp_calendar_events_occurrences ceo
					WHERE 	entry_id = "'. ee()->db->escape_str($entry_id) .'"';

			$query = ee()->db->query($sql);
		}

		$id = ($query->num_rows() > 0) ? $query->row('entry_id') : '';

		$this->cached[$cache_name][$cache_hash] = $id;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END get_event_entry_id_by_channel_entry_id() */


	// --------------------------------------------------------------------

	/**
	 * Get calendar ID from event entry ID
	 *
	 * @param	mixed	$entry_id	Event entry ID (string or array)
	 * @return	array
	 */

	public function get_calendar_id_by_event_entry_id($entry_id)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$ids = array();

		if (is_array($entry_id))
		{
			$entry_id = implode(', ');
		}
		else
		{
			$entry_id = str_replace('|', ', ', $entry_id);
		}

		if ($entry_id != '')
		{
			$sql = "SELECT 	ce.calendar_id, ce.entry_id
					FROM 	{$this->events_table} ce
					WHERE 	ce.entry_id
					IN 		(" . ee()->db->escape_str($entry_id) .')';

			$query = ee()->db->query($sql);

			foreach ($query->result_array() as $row)
			{
				$ids[$row['entry_id']] = $row['calendar_id'];
			}
		}

		$this->cached[$cache_name][$cache_hash] = $ids;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END get_calendar_id_by_event_entry_id() */


	// --------------------------------------------------------------------

	/**
	 * Get event ID from name
	 *
	 * @param	string	$name		Event name
	 * @param	int		$site_id	Site ID [optional]
	 * @return	array
	 */

	public function get_event_id_from_name($name, $site_id = 0)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$name_str = str_replace('|', '", "', $name);
		if ($site_id == 0)
		{
			$site_id = $this->get_site_id();
		}

		$sql = "SELECT 	wt.entry_id
				FROM 	{$this->sc->db->titles} AS wt
				WHERE 	site_id = " . ee()->db->escape_str($site_id) . "
				AND 	url_title
				IN 		('" . ee()->db->escape_str($name_str) . "')";

		$query = ee()->db->query($sql);

		$ids = array();

		foreach ($query->result_array() as $row)
		{
			$ids[$row['entry_id']] = $row['entry_id'];
		}

		$this->cached[$cache_name][$cache_hash] = $ids;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END get_event_id_from_name() */


	// --------------------------------------------------------------------

	/**
	 * Fetch event IDs
	 *
	 * @param	array	$params	Array of parameters
	 * @return	array
	 */

	public function fetch_event_ids($params, $category = FALSE)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Bail out early if there are no events in the specified calendar(s)
		// --------------------------------------------

		if ($params->value('calendar_id') === NULL)
		{
			return $this->cached[$cache_name][$cache_hash] = array();
		}

		$ids = array();
		$calendar_string	= str_replace('|', ', ', $params->value('calendar_id'));
		$event_string		= str_replace('|', ', ', $params->value('event_id'));
		$status_string		= str_replace('|', "', '", ee()->db->escape_str($params->value('status')));
		$status_not			= '';
		$calendar_not		= '';
		$event_not			= '';

		if (strtolower(substr($status_string, 0, 4)) == 'not ')
		{
			$status_not			= 'NOT';
			$status_string		= substr($status_string, 4);
		}

		if (strtolower(substr($calendar_string, 0, 4)) == 'not ')
		{
			$calendar_not		= 'NOT';
			$calendar_string	= substr($calendar_string, 4);
		}

		if (strtolower(substr($event_string, 0, 4)) == 'not ')
		{
			$event_not			= 'NOT';
			$event_string		= substr($event_string, 4);
		}

		// -------------------------------------
		//	check for validity
		// -------------------------------------

		$calendar_check		= preg_split('/,\s+/s', $calendar_string, -1, PREG_SPLIT_NO_EMPTY);
		$calendar_check		= array_filter($calendar_check, array($this, 'is_positive_intlike'));
		$calendar_string	= implode(', ', $calendar_check);

		$event_check		= preg_split('/,\s+/s', $event_string, -1, PREG_SPLIT_NO_EMPTY);
		$event_check		= array_filter($event_check, array($this, 'is_positive_intlike'));
		$event_string		= implode(', ', $event_check);

		// -------------------------------------
		//	range times
		// -------------------------------------

		$start_time			= $params->value('date_range_start', 'hour') .
								$params->value('date_range_start', 'minute');

		$end_time			= $params->value('date_range_end', 'hour') .
								$params->value('date_range_end', 'minute');

		$dmin				= ($params->value('date_range_start') !== FALSE) ?
									$params->value('date_range_start', 'ymd') : '';

		$dmax				= ($params->value('date_range_end') !== FALSE) ?
									$params->value('date_range_end', 'ymd') : '';

		$dtmin				= ($params->value('date_range_start') !== FALSE AND
							   $start_time > 0 AND
							   $start_time < 2400) ?
									$dmin.$start_time : '';

		$dtmax				= ($params->value('date_range_end') !== FALSE AND
							   $end_time > 0 AND
							   $end_time < 2400) ?
									$dmax.$params->value('date_range_end', 'hour') .
									$params->value('date_range_end', 'minute') :
									'';

		$tmin				= ($params->value('time_range_start') !== FALSE) ?
									$params->value('time_range_start', 'time') : '0000';

		$tmax				= ($params->value('time_range_end') !== FALSE) ?
									$params->value('time_range_end', 'time') : '2400';

		$dmin				= ee()->db->escape_str($dmin);
		$dmax				= ee()->db->escape_str($dmax);
		$dtmin				= ee()->db->escape_str($dtmin);
		$dtmax				= ee()->db->escape_str($dtmax);
		$tmin				= ee()->db->escape_str($tmin);
		$tmax				= ee()->db->escape_str($tmax);

		/*
		//	----------------------------------------
		//	Are we checking for category?
		//	----------------------------------------

		$cat_id = '';

		if ( $category )
		{
			//	----------------------------------------
			//	Get the id
			//	----------------------------------------

			if ( ctype_digit( str_replace( array("not ", "|", "&"), "", $category ) ) === TRUE )
			{
				$cat_id	= $category;
			}
			elseif ( preg_match( "/C(\d+)/s", $category, $match ) )
			{
				$cat_id	= $match['1'];
			}
			else
			{
				$categories = preg_split(
					"/" . preg_quote('|') . "/",
					str_replace(array('not ', 'NOT ', '&'), array('', '', '|'), $category),
					-1,
					PREG_SPLIT_NO_EMPTY
				);

				$cat_q	= ee()->db->query(
					"SELECT cat_id, cat_url_title
					 FROM 	exp_categories
					 WHERE 	site_id
					 IN 	(" . str_replace('|', ',' , $params->value('site_id') ) . ")
					 AND	cat_url_title
					 IN  	('" . implode("','" , ee()->db->escape_str( $categories )) . "')
					 ORDER BY LENGTH (cat_url_title)"
				);

				if ( $cat_q->num_rows() > 0 )
				{
					$cat_id	= $category;

					foreach ( $cat_q->result_array() as $row )
					{
						$cat_id = str_replace($row['cat_url_title'], $row['cat_id'], $cat_id);
					}
				}
			}
		}
		*/

		// -------------------------------------
		//  Grab the easy stuff
		// -------------------------------------

		$wheres = array();

		// ----------------------------------------------
		//  Limit query by category
		// ----------------------------------------------
		/*
		if (FALSE AND $cat_id != '')
		{
			$cat_sql = ' ';

			//inclusive AND
			if (stristr('&', $cat_id))
			{
				$cat_not 	= (substr(strtolower($cat_id), 0, 3) == 'not') ? 'NOT' : '';

				$cat_id 	= ($cat_not == 'NOT') ? substr($cat_id, 4) : $cat_id;

				//if some fool put in some '|' just cut it off
				if (strpos($cat_id, '|') !== FALSE)
				{
					$cat_id = substr($cat_id, 0, strpos($cat_id, '|'));
				}

				$categories = preg_split(
					"/" . preg_quote('&') . "/",
					$cat_id,
					-1,
					PREG_SPLIT_NO_EMPTY
				);

				$cat_sql .= '(';

				foreach ($categories as $category_id)
				{
					$cat_sql .= 'cp.cat_id';
				}

				if ($cat_not == 'NOT' AND
					$this->check_no(ee()->TMPL->fetch_param('uncategorized_entries')) === FALSE)
				{

					$cat_sql .= " ";
				}
				else
				{
					$cat_sql .= " ";
				}

				$cat_sql .= ")";
			}
			//or string
			else
			{
				if (substr($cat_id, 0, 3) == 'not' AND
					$this->check_no(ee()->TMPL->fetch_param('uncategorized_entries')) === FALSE)
				{
					$cat_sql = ee()->functions->sql_andor_string($cat_id, 'cp.cat_id', '', TRUE)." ";
				}
				else
				{
					$cat_sql = ee()->functions->sql_andor_string($cat_id, 'cp.cat_id')." ";
				}
			}

			$wheres[] = (substr($cat_sql, 0, 3) == 'AND') ? substr($cat_sql, 3) : $cat_sql;
		}
		*/

		if ($calendar_string != '')
		{
			$wheres[] = "calendar_id {$calendar_not} IN (". ee()->db->escape_str($calendar_string) .")\n";
		}

		if ($event_string != '')
		{
			$wheres[] = "ce.entry_id {$event_not} IN (". ee()->db->escape_str($event_string) .")\n";
		}

		if ($status_string != '')
		{
			$wheres[] = "wt.status {$status_not} IN ('". $status_string ."')\n";
		}

		if ($dmin != '' AND $dmax != '')
		{
			if ($dtmin != '' AND $dtmax != '')
			{
				$wheres[] = "(
								(
								 CONCAT(end_date, LPAD(end_time, 4, '0')) >= {$dtmin} AND
								 CONCAT(start_date, LPAD(start_time, 4, '0')) <= {$dtmax}
								)
								OR
								(
								 CONCAT(last_date, LPAD(end_time, 4, '0')) >= {$dtmin} AND
								 CONCAT(start_date, LPAD(start_time, 4, '0')) <= {$dtmax}
								)
								OR
								(
									CONCAT(last_date, LPAD(end_time, 4, '0')) = '' AND
									CONCAT(start_date, LPAD(start_time, 4, '0')) >= {$dtmin} AND
									CONCAT(start_date, LPAD(start_time, 4, '0')) <= {$dtmax}
								)
								OR
								(
									recurs = 'y' AND CONCAT(start_date, LPAD(start_time, 4, '0')) <= {$dtmax} AND
									(last_date = '' OR
									CONCAT(last_date, LPAD(end_time, 4, '0')) >= {$dtmax})
								)
							)\n";
			}
			elseif ($dtmin != '')
			{
				$wheres[] = "(
								(CONCAT(end_date, LPAD(end_time, 4, '0')) >= {$dtmin} AND start_date <= {$dmax}) OR
								(CONCAT(last_date, LPAD(end_time, 4, '0')) >= {$dtmin} AND start_date <= {$dmax}) OR
								(
									CONCAT(last_date, LPAD(end_time, 4, '0')) = '' AND
									CONCAT(start_date, LPAD(start_time, 4, '0')) >= $dtmin AND
									start_date <= $dmax
								)
								OR
								(
									recurs = 'y' AND
									start_date <= $dmax AND
									(
										last_date = '' OR
										last_date >= {$dmax}
									)
								)
							)\n";
			}
			elseif ($dtmax != '')
			{
				$wheres[] = "(
								(end_date >= {$dmin} AND CONCAT(start_date, LPAD(start_time, 4, '0')) <= {$dtmax}) OR
								(last_date >= {$dmin} AND CONCAT(start_date, LPAD(start_time, 4, '0')) <= {$dtmax}) OR
								(last_date = '' AND start_date >= $dmin AND CONCAT(start_date, LPAD(start_time, 4, '0')) <= $dtmax) OR
								(
									recurs = 'y' AND
									CONCAT(start_date, LPAD(start_time, 4, '0')) <= $dtmax AND
									(
										last_date = '' OR
										CONCAT(last_date, LPAD(end_time, 4, '0')) >= {$dtmax}
									)
								)
							)\n";
			}
			else
			{
				$wheres[] = "(
								(end_date >= {$dmin} AND start_date <= {$dmax}) OR
								(last_date >= {$dmin} AND start_date <= {$dmax}) OR
								(last_date = '' AND start_date >= $dmin AND start_date <= $dmax) OR
								(
									recurs = 'y' AND
									start_date <= $dmax AND
									(
										last_date = '' OR
										last_date >= {$dmax}
									)
								)
							)\n";
			}
		}
		elseif ($dmin != '')
		{
			if ($dtmin != '')
			{
				$wheres[] = "(
								CONCAT(last_date, '2400') >= {$dtmin} OR
								CONCAT(end_date, LPAD(end_time, 4, '0')) >= {$dtmin} OR
								CONCAT(start_date, LPAD(start_time, 4, '0')) >= {$dtmin}
							)\n";
			}
			else
			{
				$wheres[] = "(last_date >= {$dmin} OR end_date >= {$dmin} OR start_date >= {$dmin})\n";
			}
		}
		elseif ($dmax != '')
		{
			if ($dtmax != '')
			{
				$wheres[] = "CONCAT(start_date, LPAD(start_time, 4, '0')) <= {$dtmax}\n";
			}
			else
			{
				$wheres[] = "start_date <= {$dmax}\n";
			}
		}

		if ($tmin > 0000 AND $tmax < 2400)
		{
			$wheres[] = "(all_day = 'y' OR (start_time >= {$tmin} AND end_time <= {$tmax}))";
		}
		elseif ($tmin > 0000)
		{
			$wheres[] = "(all_day = 'y' OR start_time >= {$tmin})";
		}
		elseif ($tmax < 2400)
		{
			$wheres[] = "(all_day = 'y' OR end_time <= {$tmax})";
		}

		$where = (! empty($wheres)) ? 'WHERE '. implode("\nAND ", $wheres) . "\n" : '';

		$sql = "/* Calendar Module fetch_event_ids() */\n";

		$sql .= "SELECT 	ce.entry_id, ce.entry_id AS event_id
				 FROM 		{$this->events_table} AS ce
				 LEFT JOIN 	{$this->sc->db->channel_titles} AS wt
				 ON 		ce.entry_id = wt.entry_id ";

		//	----------------------------------------
		//	Do we have a Category id?
		//	----------------------------------------
		//  We use LEFT JOIN when there is a 'not' so that we get
		//  entries that are not assigned to a category.
		// --------------------------------
		/*
		if ($cat_id != '')
		{
			if (substr($cat_id, 0, 3) == 'not' AND
				$this->check_no(ee()->TMPL->fetch_param('uncategorized_entries')) === FALSE)
			{
				$sql .= "LEFT JOIN exp_category_posts AS cp ON wt.entry_id = cp.entry_id ";
			}
			else
			{
				$sql .= "INNER JOIN exp_category_posts AS cp ON wt.entry_id = cp.entry_id ";
			}
		}*/

		$sql .= $where;

		$sql .= "UNION\n";

		$sql .= "SELECT 	ceo.entry_id, ceo.event_id
				 FROM 		exp_calendar_events_occurrences AS ceo
				 LEFT JOIN 	{$this->sc->db->channel_titles} AS wt
				 ON 		ceo.entry_id = wt.entry_id ";

		/*if ($cat_id != '')
		{
			if (substr($cat_id, 0, 3) == 'not' AND
				$this->check_no(ee()->TMPL->fetch_param('uncategorized_entries')) === FALSE)
			{
				$sql .= "LEFT JOIN exp_category_posts AS cp ON wt.entry_id = cp.entry_id ";
			}
			else
			{
				$sql .= "INNER JOIN exp_category_posts AS cp ON wt.entry_id = cp.entry_id ";
			}
		}*/

		$wheres = array();

		// ----------------------------------------------
		//  Limit query by category
		// ----------------------------------------------

		/*if ($cat_id != '')
		{
			$cat_sql = '';

			if (substr($cat_id, 0, 3) == 'not' AND
				$this->check_no(ee()->TMPL->fetch_param('uncategorized_entries')) === FALSE)
			{
				$cat_sql = ee()->functions->sql_andor_string($cat_id, 'cp.cat_id', '', TRUE)." ";
			}
			else
			{
				$cat_sql = ee()->functions->sql_andor_string($cat_id, 'cp.cat_id')." ";
			}

			$wheres[] = (substr($cat_sql, 0, 3) == 'AND') ? substr($cat_sql, 3) : $cat_sql;
		}*/

		if ($calendar_string != '')
		{
			$wheres[] = "calendar_id {$calendar_not} IN (". ee()->db->escape_str($calendar_string) .")\n";
		}

		if ($event_string != '')
		{
			$wheres[] = "ceo.event_id {$event_not} IN (". ee()->db->escape_str($event_string) .")\n";
		}

		if ($status_string != '')
		{
			$wheres[] = "wt.status {$status_not} IN ('". ee()->db->escape_str($status_string) ."')\n";
		}

		if ($dmin != '' AND $dmax != '')
		{
			$wheres[] = "(
							(end_date >= {$dmin} AND start_date <= {$dmax}) OR
							(end_date = '' AND start_date >= $dmin AND start_date <= $dmax)
						)\n";
		}
		elseif ($dmin != '')
		{
			$wheres[] = "(end_date >= {$dmin} OR start_date >= {$dmin})\n";
		}
		elseif ($dmax != '')
		{
			$wheres[] = "start_date <= {$dmax}\n";
		}

		if ($tmin > 0000 AND $tmax < 2400)
		{
			$wheres[] = "(all_day = 'y' OR (start_time >= {$tmin} AND end_time <= {$tmax}))";
		}
		elseif ($tmin > 0000)
		{
			$wheres[] = "(all_day = 'y' OR start_time >= {$tmin})";
		}
		elseif ($tmax < 2400)
		{
			$wheres[] = "(all_day = 'y' OR end_time <= {$tmax})";
		}

		$where = (! empty($wheres)) ? 'WHERE '. implode("\nAND ", $wheres) . "\n" : '';

		$sql .= $where;

		$query = ee()->db->query($sql);

		// -------------------------------------
		//  Results?
		// -------------------------------------

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$ids[$row['entry_id']] = $row['event_id'];
			}
		}

		$this->cached[$cache_name][$cache_hash] = $ids;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_event_ids() */

	// --------------------------------------------------------------------

	/**
	 * Fetch occurrence entry IDs
	 *
	 * @param	array	$ids	Array of occurrence IDs
	 * @return	array
	 */

	public function fetch_occurrence_entry_ids($ids)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$sql = "/* fetch_occurrence_entry_ids() */\n";

		$sql .= "	SELECT 	ceo.event_id, ceo.occurrence_id, ceo.entry_id
					FROM 	exp_calendar_events_occurrences ceo
					WHERE 	event_id
					IN 		(". implode(', ', $ids) .")";

		$occurrence_ids = array();
		$query 			= ee()->db->query($sql);
		foreach ($query->result_array() as $row)
		{
			$occurrence_ids[$row['event_id']][$row['occurrence_id']] = $row['entry_id'];
		}

		$this->cached[$cache_name][$cache_hash] = $occurrence_ids;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_occurrence_entry_ids() */

	// --------------------------------------------------------------------

	/**
	 * Fetch all event data
	 *
	 * @param	array	$ids	Array of event IDs
	 * @return	array
	 */

	public function fetch_all_event_data($ids)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();

		$id_string = (is_array($ids)) ? implode(', ', $ids) : str_replace('|', ', ', $ids);

		if ($id_string != '')
		{
			$events = $this->fetch_event_data($id_string);

			if ( ! empty($events))
			{
				$occurrences 	= $this->fetch_event_occurrences($id_string);
				$exceptions 	= $this->fetch_event_exceptions($id_string);
				$rules 			= $this->fetch_event_rules($id_string);
			}

			foreach ($events as $k => $event)
			{
				$data[$k] = $event;
				$data[$k]['occurrences'] 	= (isset($occurrences[$k])) ? $occurrences[$k] 	: array();
				$data[$k]['exceptions'] 	= (isset($exceptions[$k])) 	? $exceptions[$k] 	: array();
				$data[$k]['rules'] 			= (isset($rules[$k])) 		? $rules[$k] 		: array();
			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_all_event_data() */


	// --------------------------------------------------------------------

	/**
	 * Fetch event data
	 *
	 * @param	str	$id_string	ID string
	 * @return	array
	 */

	public function fetch_event_data($id_string)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();

		$sql = "/* fetch_event_data */\n";

		$sql .= "	SELECT 		*
					FROM 		{$this->events_table}
					WHERE 		entry_id
					IN 			(" . ee()->db->escape_str($id_string) . ")
					ORDER BY 	start_date ASC,
								end_date ASC";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$data[$row['entry_id']] = $row;
			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_event_data() */


	// --------------------------------------------------------------------

	/**
	 * Fetch event occurrences
	 *
	 * @param	str	$id_string	ID string
	 * @return	array
	 */

	public function fetch_event_occurrences($id_string)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();

		$sql = ''; //"/* fetch_event_occurrences */\n";

		$sql .= "SELECT *
				 FROM 	exp_calendar_events_occurrences
				 WHERE 	event_id
				 IN 	(" . ee()->db->escape_str($id_string) . ")";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$start 	= ($row['all_day'] == 'y') ? '0000' : str_pad($row['start_time'], 4, '0', STR_PAD_LEFT);
				$end 	= ($row['all_day'] == 'y') ? '2400' : str_pad($row['end_time'], 4, '0', STR_PAD_LEFT);

				$data[$row['event_id']][$row['start_date']][$start . $end] = $row;
			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_event_occurrences() */


	// --------------------------------------------------------------------

	/**
	 * Fetch event exceptions
	 *
	 * @param	str	$id_string	ID string
	 * @return	array
	 */

	public function fetch_event_exceptions($id_string)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();

		$sql = ""; //"/* fetch_event_data */\n";

		$sql .= "SELECT *
				 FROM 	exp_calendar_events_exceptions
				 WHERE 	event_id
				 IN 	(" . ee()->db->escape_str($id_string) . ")";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$data[$row['event_id']][$row['start_date']] = $row;
			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_event_exceptions() */


	// --------------------------------------------------------------------

	/**
	 * Fetch event rules
	 *
	 * @param	str	$id_string	ID string
	 * @return	array
	 */

	public function fetch_event_rules($id_string)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();

		$sql = ""; //"/* fetch_event_data */\n";

		$sql .= "SELECT *
				 FROM 	exp_calendar_events_rules
				 WHERE 	entry_id
				 IN 	(" . ee()->db->escape_str($id_string) . ")";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$which = ($row['rule_type'] == '-') ? 'sub' : 'add';
				$data[$row['entry_id']][$which][] = $row;
			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* ENd fetch_event_rules() */


	// --------------------------------------------------------------------

	/**
	 * Fetch calendar data by ID
	 *
	 * @param	array	$id_string	Array of IDs
	 * @return	array
	 */

	public function fetch_calendar_data_by_id($ids)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data 		= array();

		$id_string 	= implode(', ', $ids);

		if ($id_string != '')
		{
			$sql = "/* fetch_calendar_data_by_id() */\n";

			$sql .= "SELECT 	ct.title 		AS calendar_title,
								ct.url_title 	AS calendar_url_title,
								ct.entry_id 	AS calendar_id,
								ct.status		AS calendar_status,
								ct.author_id	AS calendar_author_id,
								(CASE
									WHEN m.screen_name = ''
									THEN m.username
									ELSE m.screen_name
								END) as calendar_author,
								cc.*
					 FROM 		{$this->sc->db->channel_titles} AS ct
					 LEFT JOIN 	exp_calendar_calendars AS cc
					 ON 		ct.entry_id = cc.calendar_id
					 LEFT JOIN 	exp_members as m
					 ON 		ct.author_id = m.member_id
					 WHERE 		ct.entry_id
					 IN 		(" . ee()->db->escape_str($id_string) . ")";

			$query = ee()->db->query($sql);

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$data[$row['calendar_id']] = $row;
				}
			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_calendar_data_by_id() */


	// --------------------------------------------------------------------

	/**
	 * Get first day of the week
	 *
	 * @param	int	$dow	Day of the week (0 = Sunday) [optional]
	 * @return	int
	 */

	public function get_first_day_of_week($dow = '0')
	{
		if (isset($this->cached['params']['first_day_of_week']))
		{
			return $this->cached['params']['first_day_of_week'];
		}

		if (isset(ee()->TMPL))
		{
			if ( ! class_exists('Calendar_parameters'))
			{
				require_once CALENDAR_PATH.'calendar.parameters.php';
			}

			// This little $param is not actually used by the Calendar_parameters class. But it's nice documentation anyway. We'll delete some time after 2010 10 13. mitchell@solspace.com.
			$param = array(
				'name' 		=> 'first_day_of_week',
				'required' 	=> FALSE,
				'type' 		=> 'text',
				'default' 	=> $dow
			);

			$P = new Calendar_parameters($param);

			if ( ( $new = $P->fetch_value($param['name']) ) !== FALSE )
			{
				$dow = $new;
			}
		}

		return $this->cached['params']['first_day_of_week'] = $dow;
	}
	/* END get_first_day_of_week() */


	// --------------------------------------------------------------------

	/**
	 * Grab some basic info about the calendars
	 *
	 * @return	array
	 */

	public function calendar_basics()
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Get event counts
		// --------------------------------------------

		$events	= array();

		$sql	= "/* calendar_basics() get event counts */\n";

		$sql	= "SELECT		calendar_id,
								COUNT(*) AS count
					FROM		{$this->events_table}
					GROUP BY	calendar_id";

		$query	= ee()->db->query( $sql );

		foreach ( $query->result_array() as $row )
		{
			$events[$row['calendar_id']] 	= $row['count'];
		}

		// --------------------------------------------
		//  Get calendar data
		// --------------------------------------------

		$data = array();

		$sql = "/* calendar_basics() get calendars */\n";

		$sql .= "SELECT 	wt.title AS calendar_name,
							wt.{$this->sc->db->channel_id} AS weblog_id,
							wt.status,
							wt.author_id,
							cc.*
				 FROM 		exp_calendar_calendars cc
				 LEFT JOIN 	{$this->sc->db->channel_titles} AS wt
				 ON 		cc.calendar_id = wt.entry_id
				 GROUP BY 	cc.calendar_id
				 ORDER BY 	wt.title ASC";

		$query = ee()->db->query( $sql );

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$row['total_events'] 		= ( isset( $events[ $row['calendar_id'] ] ) ) ? $events[ $row['calendar_id'] ]: 0;
				$data[$row['calendar_id']] 	= $row;
			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END calendar_basics() */


	// --------------------------------------------------------------------

	/**
	 * converts a user input date into an associative array of ymd
	 * @access	public
	 * @param	string	date input
	 * @return	array
	 */

	public function format_input_date($date)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		//--------------------------------------------
		//	get formatting info and split input
		//--------------------------------------------

		$format 			= $this->date_formats[$this->preference('date_format')];

		//need an array
		$date_data 			= explode($format['split'], $date);

		//need to get the proper order for this format type
		//turns M/d/Y into array('m', 'd', 'y') so we can properly get date info
		//from the above split
		$format_style 		= explode($format['split'], strtolower($format['format']));

		//set ymd from order of format style
		$date_output 		= array(
			'day'	=> str_pad($date_data[array_search('d', $format_style)], 2, '0', STR_PAD_LEFT),
			'month'	=> str_pad($date_data[array_search('m', $format_style)], 2, '0', STR_PAD_LEFT),
			'year'	=> $date_data[array_search('y', $format_style)],
		);

		//--------------------------------------------
		//	extra data
		//--------------------------------------------

		$date_output['y']	= $date_output['year'];
		$date_output['m']	= $date_output['month'];
		$date_output['d']	= $date_output['day'];
		$date_output['ymd'] = $date_output['y'] . $date_output['m'] . $date_output['d'];
		//--------------------------------------------
		//	cache and return
		//--------------------------------------------

		$this->cached[$cache_name][$cache_hash] = $date_output;

		return $this->cached[$cache_name][$cache_hash];
	}
	// END  format_input_date


	// --------------------------------------------------------------------

	/**
	 * Grab some basic info about the events
	 *
	 * @return	array
	 */

	public function event_basics($count_only = FALSE, $params = array())
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$select = ($count_only === TRUE) ?
					'COUNT(*) AS count' :
					"wt.title AS event_name,
					 wt.{$this->sc->db->channel_id} AS weblog_id,
					 wt.{$this->sc->db->channel_id} AS channel_id,
					 wt.status,
					 ce.entry_id AS event_id,
					 ce.calendar_id,
					 ce.recurs,
					 ce.start_date,
					 ce.last_date,
					 wt2.title AS calendar_name,
					 wt2.{$this->sc->db->channel_id} AS calendar_weblog_id,
					 wt2.{$this->sc->db->channel_id} AS calendar_channel_id";

		$wheres = array();

		if (ee()->input->get_post('calendar'))
		{
			$wheres[] = 'ce.calendar_id = ' . ee()->db->escape_str(ee()->input->get_post('calendar'));
		}

		if (ee()->input->get_post('status'))
		{
			$wheres[] = 'wt.status = "' . ee()->db->escape_str(ee()->input->get_post('status')) . '"';
		}

		if (isset($params['recurs']))
		{
			$wheres[] = 'ce.recurs = "' . ee()->db->escape_str($params['recurs']) . '"';
		}
		elseif (ee()->input->get_post('recurs'))
		{
			$wheres[] = 'ce.recurs = "' . ee()->db->escape_str(ee()->input->get_post('recurs')) . '"';
		}

		if (ee()->input->get_post('date'))
		{
			$formatted_date = $this->format_input_date(ee()->input->get_post('date'));
			$date 			= $formatted_date['ymd'];

			if (ee()->input->get_post('date_direction'))
			{
				$dirs 		= array(
					'greater'	=> '>=',
					'less'		=> '<=',
					'equal'		=> '='
				);

				$dir 		= (array_key_exists(ee()->input->get_post('date_direction'), $dirs)) ?
									$dirs[ee()->input->get_post('date_direction')] : '=';

				$wheres[] 	= 'ce.start_date ' . ee()->db->escape_str($dir) . ' ' . ee()->db->escape_str($date);
			}
			else
			{
				$wheres[] 	= 'ce.start_date ' . '>= ' . ee()->db->escape_str($date);
			}
		}

		$orderby = (ee()->input->get_post('orderby')) ?
					ee()->db->escape_str(ee()->input->get_post('orderby')) : 'wt.title';

		if ($orderby == 'title')
		{
			$orderby = 'wt.title';
		}
		elseif ($orderby == 'calendar_name')
		{
			$orderby = 'wt2.title';
		}
		elseif ($orderby == 'status')
		{
			$orderby = 'wt.status';
		}

		$sort 		= (ee()->input->get_post('sort')) ?
						ee()->db->escape_str(ee()->input->get_post('sort')) 	: 'ASC';
		$offset 	= (ee()->input->get_post('offset')) ?
						ee()->db->escape_str(ee()->input->get_post('offset')) 	: 0;
		$limit 		= (is_numeric(ee()->input->get_post('limit'))) ?
						ee()->db->escape_str(ee()->input->get_post('limit')) 	: FALSE;

		$data 		= array();

		//$sql = "/* event_basics() */\n";

		$sql 		= "SELECT 		$select
					   FROM 		{$this->events_table} ce
					   LEFT JOIN 	{$this->sc->db->channel_titles} AS wt
					   ON 			ce.entry_id = wt.entry_id
					   LEFT JOIN 	{$this->sc->db->channel_titles} AS wt2
					   ON 			ce.calendar_id = wt2.entry_id ";

		if ( ! empty($wheres) )
		{
			$sql .= 'WHERE ' . implode(' AND ', $wheres) . "\n";
		}

		$sql .= 'ORDER BY ' . $orderby . ' ' . $sort;

		if ($count_only === FALSE AND $limit != FALSE)
		{
			$sql .= "\nLIMIT {$offset}, {$limit}";
		}

		$query = ee()->db->query($sql);

		if ($count_only === TRUE)
		{
			return $query->row('count');
		}
		else if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$row['first_date'] 	= 	substr($row['start_date'], 0, 4) . '-' .
										substr($row['start_date'], 4, 2) . '-' .
										substr($row['start_date'], 6, 2);

				$row['last_date'] 	= ($row['last_date'] > 0) ?
										substr($row['last_date'], 0, 4) . '-' .
										substr($row['last_date'], 4, 2) . '-' .
										substr($row['last_date'], 6, 2) :
										'--';

				$data[$row['event_id']] = $row;
			}
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END event_basics() */


	// --------------------------------------------------------------------

	/**
	 * Get tz_offset field id
	 *
	 * @return	int
	 */

	public function get_tz_offset_field_id()
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$sql = ""; //"/* get_tz_offset_field_id() */\n";

		$sql .= "SELECT field_id
				 FROM 	{$this->sc->db->channel_fields}
				 WHERE 	site_id = " . ee()->db->escape_str($this->get_site_id()) . "
				 AND 	field_name = '" . ee()->db->escape_str(CALENDAR_CALENDARS_FIELD_PREFIX.'tz_offset') . "'
				 LIMIT 	1 ";

		$query = ee()->db->query($sql);

		$this->cached[$cache_name][$cache_hash] = ($query->num_rows() == 1) ? $query->row('field_id') : 0;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END get_tz_offset_field_id() */


	// --------------------------------------------------------------------

	/**
	 * Get time_format field id
	 *
	 * @return	int
	 */

	public function get_time_format_field_id()
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		//$sql = "/* get_time_format_field_id() */\n";

		$sql = "SELECT 	field_id
				FROM 	{$this->sc->db->channel_fields}
				WHERE 	site_id = " . ee()->db->escape_str($this->get_site_id()) . "
				AND 	field_name = '" . ee()->db->escape_str(CALENDAR_CALENDARS_FIELD_PREFIX . 'time_format') . "'
				LIMIT 	1";

		$query = ee()->db->query($sql);

		$this->cached[$cache_name][$cache_hash] = ($query->num_rows() == 1) ? $query->row('field_id') : 0;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END get_time_format_field_id() */


	// --------------------------------------------------------------------

	/**
	 * Get field id
	 *
	 * @return	int
	 */

	public function get_field_id($field_name)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;

		if (isset($this->cached[$cache_name][$field_name]))
		{
			return $this->cached[$cache_name][$field_name];
		}

		$this->cached[$cache_name][$field_name] = '';

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		//$sql = "/* get_field_id() */\n";

		$sql = "SELECT 	field_id, field_name
				FROM 	{$this->sc->db->channel_fields}
				WHERE 	site_id = " . ee()->db->escape_str($this->get_site_id());

		$query = ee()->db->query($sql);

		foreach ($query->result_array() as $row)
		{
			$this->cached[$cache_name][$row['field_name']] = $row['field_id'];
		}

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return (isset($this->cached[$cache_name][$field_name])) ? $this->cached[$cache_name][$field_name] : FALSE;
	}
	/* END get_field_id() */

	// --------------------------------------------------------------------

	/**
	 * Get ics_url field id
	 *
	 * @return	int
	 */

	public function get_ics_url_field_id()
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		//$sql = "/* get_ics_url_field_id() */\n";

		$sql = "SELECT 	field_id
				FROM 	{$this->sc->db->channel_fields}
				WHERE 	site_id = " . ee()->db->escape_str($this->get_site_id()) . "
				AND 	field_name = '" . ee()->db->escape_str(CALENDAR_CALENDARS_FIELD_PREFIX . 'ics_url') . "'
				LIMIT 	1";

		$query = ee()->db->query($sql);

		$this->cached[$cache_name][$cache_hash] = ($query->num_rows() == 1) ? $query->row('field_id') : 0;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END get_ics_url_field_id() */


	// --------------------------------------------------------------------

	/**
	 * Get events imported via .ics
	 *
	 * @param	int	$calendar_id	Calendar ID
	 * @return	array
	 */

	public function get_imported_events($calendar_id)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();

		$sql = "/* get_imported_events() */\n";

		$sql .= "SELECT	*
				 FROM	exp_calendar_events_imports
				 WHERE	calendar_id = " . ee()->db->escape_str($calendar_id);

		$query = ee()->db->query($sql);

		foreach ($query->result_array() as $row)
		{
			$data[$row['uid']] = $row;
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END get_imported_events() */


	// --------------------------------------------------------------------

	public function get_status_list()
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$data = array();

		//$sql = "/* get_status_list() */\n";

		$sql  = "SELECT 	*
				 FROM 		exp_statuses
				 WHERE 		site_id = " . ee()->db->escape_str($this->get_site_id()) . "
				 ORDER BY 	status_order ASC";

		$query = ee()->db->query($sql);

		foreach ($query->result_array() as $row)
		{
			$data[] = $row;
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}


	// --------------------------------------------------------------------

	public function fetch_occurrence_channel_data($ids = array())
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$ids 	= implode(', ', $ids);

		//$sql = "/* fetch_occurrence_channel_data() */\n";

		$sql 	= "SELECT 	wt.*
				   FROM 	{$this->sc->db->channel_titles} AS wt
				   WHERE 	entry_id
				   IN 		(" . ee()->db->escape_str($ids) . ")";

		$query 	= ee()->db->query($sql);

		$data 	= array();

		foreach ($query->result_array() as $row)
		{
			$data[$row['entry_id']] = $row;
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_occurrence_channel_data() */

	// --------------------------------------------------------------------

	public function fetch_entry_details_by_entry_id($ids)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$ids = implode(', ', $ids);
		$data = array();

		//$sql = "/* fetch_entry_details_by_entry_id() */\n";

		$sql = "SELECT 		ce.event_id, wt.*
				FROM 		{$this->events_table} AS ce
				LEFT JOIN 	{$this->sc->db->channel_titles} AS wt
				ON 			ce.entry_id = wt.entry_id
				WHERE 		ce.entry_id
				IN 			(" . ee()->db->escape_str($ids) . ")";

		$query = ee()->db->query($sql);

		foreach ($query->result_array() as $row)
		{
			$data[$row['entry_id']] = $row;
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_entry_details_by_entry_id() */


	// --------------------------------------------------------------------

	public function fetch_entry_id_by_occurrence_id($ids)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$ids = str_replace('|', ', ', $ids);
		$data = array();

		//$sql = "/* fetch_entry_id_by_occurrence_id() */\n";

		$sql = 'SELECT 	ceo.entry_id
				FROM 	exp_calendar_events_occurrences ceo
				WHERE 	ceo.occurrence_id
				IN 		(' . ee()->db->escape_str($ids) . ')';

		$query = ee()->db->query($sql);

		foreach ($query->result_array() as $row)
		{
			$data[] = $row['entry_id'];
		}

		$this->cached[$cache_name][$cache_hash] = $data;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_entry_id_by_occurrence_id() */

	// --------------------------------------------------------------------

	public function fetch_occurrence_data_by_entry_id(  $entry_id,
														$start_date = NULL,
														$end_date = NULL,
														$start_time = NULL,
														$end_time = NULL)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$sql = '	SELECT 	ceo.*
					FROM 	exp_calendar_events_occurrences ceo
					WHERE 	entry_id = "'. ee()->db->escape_str($entry_id) .'"
					';
		if ($start_date AND $end_date)
		{
			$sql .= 'AND ceo.start_date = "'. ee()->db->escape_str($start_date) . '"
					 AND ceo.end_date = "'. ee()->db->escape_str($end_date) .'" ';
		}

		if ($start_time AND $end_time)
		{
			$sql .= 'AND ceo.start_time = "'. ee()->db->escape_str($start_time) .'"
					 AND ceo.end_time = "'. ee()->db->escape_str($end_time) .'"';
		}

		$query = ee()->db->query($sql);

		$this->cached[$cache_name][$cache_hash] = $query->row_array();

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_occurrence_data_by_entry_id() */


	// --------------------------------------------------------------------

	public function get_channel_basics()
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$sql = "SELECT 		w.{$this->sc->db->channel_id} 		AS weblog_id,
							w.{$this->sc->db->channel_name} 	AS blog_name,
							w.{$this->sc->db->channel_title} 	AS blog_title
				FROM 		{$this->sc->db->channels}			AS w
				WHERE 		site_id = '" . ee()->db->escape_str($this->get_site_id()) . "'
				ORDER BY 	blog_title ASC";

		$query = ee()->db->query($sql);

		$channels = array();
		foreach ($query->result_array() as $row)
		{
			$channels[] = $row;
		}

		$this->cached[$cache_name][$cache_hash] = $channels;

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	/* END fetch_occurrence_data_by_entry_id() */


	// --------------------------------------------------------------------

	/**
	 * http://www.php.net/manual/en/function.unset.php#89881
	 * @return
	 */

	public function array_remove_value ()
	{
		$args = func_get_args();
		return array_diff($args[0], array_slice($args,1));
	}
	/* END array_remove_value() */

	// --------------------------------------------------------------------
}
// END CLASS Calendar_data