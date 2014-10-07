<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - Permissions
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @filesource	calendar/libraries/calendar_permissions.php
 */

if ( ! class_exists('Addon_builder_calendar'))
{
	require_once '../addon_builder/addon_builder.php';
}

class Calendar_permissions extends Addon_builder_calendar
{
	private $prefs 					= array();
	private $calendar_permissions 	= array();
	private $calendar_ids 			= array();
	private $filter_types 		 	= array('none', 'disable_link', 'search_filter');

	// --------------------------------------------------------------------

	/**
	 * __construct
	 *
	 * @access	public
	 * @return	object
	 */

	public function __construct()
	{
		parent::__construct('calendar');

		$this->prefs 		= $this->get_permissions_preferences();
		$this->cal_prefs 	= $this->data->get_module_preferences();
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * permissions_enabled
	 *
	 * @access	public
	 * @return	bool
	 */

	public function enabled()
	{
		return (
			isset($this->prefs['enabled']) AND
			$this->prefs['enabled'] == TRUE
		);
	}
	//END permissions_enabled


	// --------------------------------------------------------------------

	/**
	 * filter on
	 *
	 * @access	public
	 * @return	string
	 */

	public function filter_on()
	{
		return (($this->enabled() AND
				isset($this->prefs['filter_on'])) ?
					$this->prefs['filter_on'] :
					'none'
		);
	}
	//END filter_on


	// --------------------------------------------------------------------

	/**
	 * group_has_permission
	 *
	 * @access	public
	 * @param 	int 	$group_id
	 * @param 	int  	$calendar_id
	 * @return	bool 	does group have permission to access that cal?
	 */

	public function group_has_permission ($group_id, $calendar_id)
	{
		if (//permissions enabled?
			! $this->enabled() OR
			//does this calendar allow everyone?
			(
				isset($this->prefs['calendar'][$calendar_id]['allow_all']) AND
				$this->prefs['calendar'][$calendar_id]['allow_all']
			) OR
			//group allowed all?
			in_array($group_id, $this->get_groups_allowed_all()) OR
			(
				! in_array($group_id, $this->get_groups_denied_all()) AND
				isset($this->prefs['calendar'][$calendar_id][$group_id]) AND
				$this->prefs['calendar'][$calendar_id][$group_id]
			)
		)
		{
			return TRUE;
		}

		//none of those met? GEH OUT
		return FALSE;
	}
	//END group_has_permission


	// --------------------------------------------------------------------

	/**
	 * get_groups_allowed_all
	 *
	 * @access	public
	 * @return	array 	array of groups that have permission to all
	 */

	public function get_groups_allowed_all ()
	{
		return array_merge(
			//super admins always allowed
			array(1),
			(
				isset($this->prefs['groups_allowed_all']) ?
				$this->prefs['groups_allowed_all'] :
				array()
			)
		);
	}
	//END get_groups_allowed_all


	// --------------------------------------------------------------------

	/**
	 * get_groups_denied_all
	 *
	 * @access	public
	 * @return	bool 	is the group denied from all?
	 */

	public function get_groups_denied_all()
	{
		return (
			isset($this->prefs['groups_denied_all']) ?
			$this->prefs['groups_denied_all'] :
			array()
		);
	}


	// --------------------------------------------------------------------

	/**
	 * get_group_permissions
	 *
	 * @access	public
	 * @return	bool 	array
	 */

	public function get_group_permissions ($calendar_id = -1)
	{
		//-1 is only if nothing was passed
		if ( $calendar_id !== -1)
		{
			$calendar_id = (is_numeric($calendar_id) AND $calendar_id >= 0) ? $calendar_id : 0;
		}

		$member_groups = $this->data->get_member_groups();

		if (empty($this->calendar_permissions))
		{
			//we want 0 as its going to return blanks for us
			$calendar_list = array_merge(array(0), array_keys($this->data->get_calendar_list()));

			//not quite redundant
			$this->calendar_permissions = isset($this->prefs['calendar']) ?
											$this->prefs['calendar'] :
											array();

			//for every cal, and every member, if the default isn't set, do false
			foreach ($calendar_list as $cal_id)
			{
				if ( ! isset($this->calendar_permissions[$cal_id]['allow_all']))
				{
					$this->calendar_permissions[$cal_id]['allow_all'] = FALSE;
				}

				foreach($member_groups as $group_id => $group_name)
				{
					if ( ! isset($this->calendar_permissions[$cal_id][$group_id]))
					{
						$this->calendar_permissions[$cal_id][$group_id] = FALSE;
					}
				}
			}
		}

		if ($calendar_id > -1)
		{
			if (isset($this->calendar_permissions[$calendar_id]))
			{
				return $this->calendar_permissions[$calendar_id];
			}
			//return blanks if not set
			else
			{
				return $this->calendar_permissions[0];
			}
		}
		else
		{
			return $this->calendar_permissions;
		}
	}
	//END get_group_permissions


	// --------------------------------------------------------------------

	/**
	 * save_group_permissions
	 *
	 * @access	public
	 * @param 	int 	calendar_id to save group for
	 * @param 	array 	group data to save
	 * @return	null
	 */

	public function save_calendar_permissions ($calendar_id, $data)
	{
		//because we are calling on susbmission end
		//most of the time this shouldn't be much of a problem
		//but needs this failsafe. Don't want any crap data.
		if ( ! $this->valid_calendar_id($calendar_id))
		{
			return FALSE;
		}

		$prefs 			= $this->prefs;
		$member_groups  = $this->data->get_member_groups();

		// -------------------------------------
		//	allow all?
		// -------------------------------------

		$prefs['calendar'][$calendar_id]['allow_all'] = (
			isset($data['calendar_allow_all']) AND
			$this->check_yes($data['calendar_allow_all'])
		);

		// -------------------------------------
		//	groups
		// -------------------------------------

		foreach ($member_groups as $group_id => $group_name)
		{
			$prefs['calendar'][$calendar_id][$group_id] = (
				isset($data['calendar_group_' . $group_id]) AND
				$this->check_yes($data['calendar_group_' . $group_id])
			);
		}

		// -------------------------------------
		//	save
		// -------------------------------------

		$this->set_permissions_preferences($prefs);

		return TRUE;
	}
	//END save_calendar_permissions


	// --------------------------------------------------------------------

	/**
	 * save_group_permissions
	 *
	 * @access	public
	 * @param 	int 	calendar_id to save group for
	 * @return	bool 	is the group denied from all?
	 */

	public function save_permissions ($data)
	{
		$prefs 			= $this->prefs;
		$calendar_list 	= $this->data->get_calendar_list();
		$member_groups  = $this->data->get_member_groups();

		$prefs 							= array();
		$prefs['calendar'] 				= array();
		$prefs['groups_allowed_all'] 	= array();
		$prefs['groups_denied_all'] 	= array();

		// -------------------------------------
		//	enabled
		// -------------------------------------

		$prefs['enabled'] = (
			isset($data['permissions_enabled']) AND
			$this->check_yes($data['permissions_enabled'])
		);

		// -------------------------------------
		//	filter
		// -------------------------------------

		$prefs['filter_on'] = (
			(isset($data['filter_on']) AND
			in_array($data['filter_on'], $this->filter_types)) ?
				$data['filter_on'] :
				'none'
		);

		// -------------------------------------
		//	allow all by cal
		// -------------------------------------

		foreach ( $calendar_list as $calendar_id => $somedata)
		{
			$prefs['calendar'][$calendar_id]['allow_all'] = (
				isset($data['cal_' . $calendar_id . '_allow_all']) AND
				$this->check_yes($data['cal_' . $calendar_id . '_allow_all'])
			);

			// -------------------------------------
			//	individual
			// -------------------------------------
			foreach ($member_groups as $group_id => $group_name)
			{
				$prefs['calendar'][$calendar_id][$group_id] = (
					isset($data['cal_' . $calendar_id . '_group_' . $group_id]) AND
					$this->check_yes($data['cal_' . $calendar_id . '_group_' . $group_id])
				);
			}
		}

		// -------------------------------------
		//	collect by group
		// -------------------------------------

		foreach ($member_groups as $group_id => $group_name)
		{
			// -------------------------------------
			//	allowed all
			// -------------------------------------

			if (isset($data['allowed_all_group_' . $group_id]) AND
				$this->check_yes($data['allowed_all_group_' . $group_id]))
			{
				$prefs['groups_allowed_all'][] = $group_id;
			}

			// -------------------------------------
			//	deny all
			// -------------------------------------

			if (isset($data['denied_all_group_' . $group_id]) AND
				$this->check_yes($data['denied_all_group_' . $group_id]))
			{
				$prefs['groups_denied_all'][] = $group_id;
			}
		}

		// -------------------------------------
		//	save
		// -------------------------------------

		$this->set_permissions_preferences($prefs);

		return TRUE;
	}
	//END save_permissions


	// --------------------------------------------------------------------

	/**
	 * valid_calendar_id
	 *
	 * @access	private
	 * @param 	int 	calendar_id to check
	 * @return	bool 	valid calendar ID
	 */

	private function valid_calendar_id ($calendar_id)
	{
		//its cached, don't fuss :p
		return array_key_exists($calendar_id, $this->data->get_calendar_list());
	}
	//END valid_calendar_id


	// --------------------------------------------------------------------

	/**
	 * get_allowed_calendars_for_group
	 *
	 * @access	public
	 * @param 	int 	group_id
	 * @return	array 	array of allowed calendar ids
	 */

	public function get_allowed_calendars_for_group ($group_id)
	{
		if (isset($this->cache['allowed_calendars'][$group_id]))
		{
			return $this->cache['allowed_calendars'][$group_id];
		}

		if (in_array($group_id, ee()->calendar_permissions->get_groups_denied_all()))
		{
			return array();
		}

		$this->get_group_permissions();

		if (in_array($group_id, ee()->calendar_permissions->get_groups_allowed_all()))
		{
			return array_keys($this->calendar_permissions);
		}

		$allowed_calendars = array();

		foreach ($this->calendar_permissions as $calendar_id => $data)
		{
			if ($data['allow_all'] OR (isset($data[$group_id]) AND $data[$group_id]))
			{
				$allowed_calendars[] = $calendar_id;
			}
		}

		$this->cache['allowed_calendars'][$group_id] = $allowed_calendars;

		return $allowed_calendars;
	}
	//END get_allowed_calendars_for_group


	// --------------------------------------------------------------------

	/**
	 * permissions_json
	 * does a json output for ajax calling of allowed entry ids for a group
	 *
	 * @access	public
	 * @param 	int 	group_id
	 * @return	null
	 */

	public function permissions_json($group_id)
	{
		$all_allowed 		= TRUE;
		$all_denied 		= FALSE;
		$channelIds 		= array(
			'calendars' => array($this->cal_prefs['calendar_weblog']),
			'events'	=> array($this->cal_prefs['event_weblog']),
		);
		$allowed_entry_ids 	= array();
		$allowed_calendars  = array();

		//if they are allowed everything, just send it through and don't bother
		if ($this->enabled() AND
			! in_array($group_id, $this->get_groups_allowed_all()))
		{
			$all_allowed 		= FALSE;
			$all_denied 		= in_array($group_id, $this->get_groups_denied_all());

			//if all is denied, just send on the bools
			if ( ! $all_denied)
			{
				$allowed_entry_ids = $this->get_allowed_entry_ids($group_id);
			}
		}

		return $this->send_ajax_response(array(
			'success' 			=> TRUE,
			'message' 			=> lang('success'),
			'allAllowed'		=> $all_allowed,
			'allDenied'			=> $all_denied,
			'channelIds'		=> $channelIds,
			'allowedEntryIds'	=> $allowed_entry_ids,
			'allowedCalendars'  => $allowed_calendars
		));
	}
	//END permissions_json


	// --------------------------------------------------------------------

	/**
	 * get_allowed_entry_ids
	 *
	 * @access	public
	 * @param 	int 	group_id
	 * @param 	bool 	get opposite? (this is will be used with get_denied_entry_ids)
	 * @return	array 	array of allowed entry ids
	 */

	public function get_allowed_entry_ids ($group_id, $not = FALSE)
	{
		if ( ! $not AND isset($this->cache['allowed_entry_ids'][$group_id]))
		{
			return $this->cache['allowed_entry_ids'][$group_id];
		}

		$allowed_entry_ids = array();

		$not = ($not) ? ' NOT ' : '';

		//since this needs to be very fast,
		//lets just get the permissions per group directly
		$calendar_permissions = $this->prefs['calendar'];

		$calendar_get_entries = $this->get_allowed_calendars_for_group($group_id);

		if (empty($calendar_get_entries))
		{
			return array();
		}

		//calendar_events seems to be a common table name that some people
		//were using to import google calendar items before this module
		//was created, so its changable where others arent *shrug*
		$events_table = ee()->db->escape_str($this->data->events_table);

		//Get all of the entry IDs possible.
		//Yes, this will be stupid big, but its supposed to be ajax
		//requested, so no slow down for users.
		//Has to be this way because there is just one events channel :/.
		//Thinking the group concat here might have better performance than
		//returning a large object with lots of rows to iterate over.
		$id_query = ee()->db->query(
			"SELECT GROUP_CONCAT(entry_id separator '|') as entry_ids
			 FROM 	{$events_table}
			 WHERE 	calendar_id {$not}
			 IN 	(" . implode(',', ee()->db->escape_str($calendar_get_entries)) . ")"
		);

		//have to have null here because group_concat always returns something
		if ($id_query->num_rows() > 0 AND $id_query->row('entry_ids') != NULL)
		{
			$allowed_entry_ids = preg_split("/\|/", $id_query->row('entry_ids'), -1, PREG_SPLIT_NO_EMPTY);

			//could implode here, but would be slower on larger arrays, which will be common
			$search = ee()->db->escape_str(
				trim(str_replace('||', '|', $id_query->row('entry_ids')), '|')
			);

			//occurrence IDs are different
			$oc_query = ee()->db->query(
				"SELECT GROUP_CONCAT(entry_id separator '|') as entry_ids
				 FROM 	exp_calendar_events_occurrences
				 WHERE 	event_id
				 IN 	(" . str_replace('|', ',', $search) . ")"
			);

			//have to have null here because group_concat always returns something
			if ($oc_query->num_rows() > 0 AND $oc_query->row('entry_ids') != NULL)
			{
				$allowed_entry_ids = array_merge(
					$allowed_entry_ids,
					preg_split("/\|/", $oc_query->row('entry_ids'), -1, PREG_SPLIT_NO_EMPTY)
				);
			}
		}

		//get rid of any blanks (but at this point there should be none)
		//not running array unique here because it could be a performance hit
		//and the in_array calls in PHP and JS will work fine with duplicates
		//because they stop on the first match anyway
		$allowed_entry_ids = array_filter($allowed_entry_ids);

		//not does its own caching
		if ( ! $not)
		{
			$this->cache['allowed_entry_ids'][$group_id] = $allowed_entry_ids;
		}

		return $allowed_entry_ids;
	}
	//END get_allowed_entry_ids


	// --------------------------------------------------------------------

	/**
	 * get_denied_entry_ids
	 *
	 * Like get allowed, but a blacklist instead for things that need to allow
	 * non calendar entry ids through and the entry id is all we have.
	 * This may appear redundant, but is needed for search filtering where we
	 * cannot specify a parent calendar id.
	 *
	 * @access	public
	 * @param 	int 	group_id
	 * @return	array 	array of denied entry ids
	 */

	public function get_denied_entry_ids ($group_id)
	{
		if (isset($this->cache['denied_entry_ids'][$group_id]))
		{
			return $this->cache['denied_entry_ids'][$group_id];
		}

		$this->cache['denied_entry_ids'][$group_id] = $this->get_allowed_entry_ids($group_id, TRUE);

		return $this->cache['denied_entry_ids'][$group_id];
	}
	//END get_denied_entry_ids


	// --------------------------------------------------------------------

	/**
	 * get_denied_entry_ids
	 *
	 * @access	public
	 * @param 	int 	group_id
	 * @return	array 	array of denied entry ids
	 */

	public function can_edit_entry( $group_id, $entry_id )
	{
		return ! in_array($entry_id, $this->get_denied_entry_ids($group_id));
	}
	//END can_edit_entry

	public function set_permissions_preferences ($prefs = array())
	{
		ee()->db->delete(
			'calendar_permissions_preferences',
			array('site_id' => $this->data->get_site_id())
		);

		ee()->db->query(ee()->db->insert_string(
			'calendar_permissions_preferences',
			array('preferences' => $this->json_encode($prefs)),
			array('site_id' => $this->data->get_site_id())
		));
	}

	public function get_permissions_preferences ()
	{
		$return = array();

		$query = ee()->db->query(
			"SELECT preferences
			 FROM 	exp_calendar_permissions_preferences
			 WHERE  site_id = " . ee()->db->escape_str($this->data->get_site_id())
		);

		if ($query->num_rows() > 0)
		{
			$return = $this->json_decode($query->row('preferences'), TRUE);
		}

		return $return;
	}
}
//END Calendar_permissions