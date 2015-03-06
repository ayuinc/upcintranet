<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - Extension
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/ext.calendar.php
 */

if ( ! class_exists('Extension_builder_calendar'))
{
	require_once 'addon_builder/extension_builder.php';
}

class Calendar_ext extends Extension_builder_calendar
{
	public $settings		= array();
	public $name			= '';
	public $version			= '';
	public $description		= '';
	public $settings_exist	= 'n';
	public $docs_url		= '';
	public $required_by 	= array('module');
	public $CDT				= FALSE;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct($settings = array())
	{
		parent::__construct();

		// --------------------------------------------
		//  Settings
		// --------------------------------------------

		$this->settings = $settings;

		// --------------------------------------------
		//  Set Required Extension Variables
		// --------------------------------------------

		if (is_object(ee()->lang))
		{
			ee()->lang->loadfile('calendar');

			$this->name			= lang('calendar_module_name');
			$this->description	= lang('calendar_module_description');
		}

		$this->docs_url		= CALENDAR_DOCS_URL;
		$this->version		= CALENDAR_VERSION;

		// -------------------------------------
		//  Grab the MOD file and related goodies
		// -------------------------------------

		if ( ! class_exists('Calendar'))
		{
			require_once CALENDAR_PATH.'mod.calendar.php';
		}

		$this->actions();

		if ( ! defined('CALENDAR_CALENDARS_CHANNEL_NAME'))
		{
			define('CALENDAR_CALENDARS_CHANNEL_NAME', $this->actions->calendar_channel_shortname());
			define('CALENDAR_EVENTS_CHANNEL_NAME', $this->actions->event_channel_shortname());
		}
	}
	/* END Calendar_extension() */


	// --------------------------------------------------------------------

	/**
	 * Submit new entry end
	 * Create a new calendar or event
	 *
	 * @param	int		$id
	 * @param	array	$data
	 * @param	str		$ping
	 * @return
	 */

	public function submit_new_entry_end($id, $data, $ping)
	{
		// -------------------------------------
		//  Bah! Some extensions can, in certain circumstances,
		//  cause us to arrive here with no $id (either '' or 0).
		//  If that happens, just bail out.
		// -------------------------------------

		if ( ! $id)
		{
			return;
		}

		$errors 			= array();

		// -------------------------------------
		//  weblog_id/calendar_id
		// -------------------------------------

		$channel_id 		= $this->sc->db->channel_id;

		// -------------------------------------
		//  EE 2 does not include entry_id in $data.
		//	EE 1 includes it, but it can be empty
		//	string. Mimic this.
		// -------------------------------------

		$data['entry_id']	= ( isset( $data['entry_id'] ) === FALSE ) ? '' : $data['entry_id'];

		// -------------------------------------
		//  EE 2 does not always include site_id in $data
		// -------------------------------------

		//doing this in case someone mucks around with the channel and its site location
		//and we have a permanant static site id in place
		$data['site_id'] 	= ($this->data->static_site_id !== 0) ?
								$this->data->static_site_id :
								(( isset( $data['site_id'] ) === FALSE ) ?
									$this->data->get_site_id() :
									$data['site_id']);

		$site_id 			= $data['site_id'];

		// -------------------------------------
		//  calendars channel?
		// -------------------------------------

		if ($this->data->channel_is_calendars_channel($data[$channel_id]) === TRUE)
		{
			$tz_field	= 'field_id_' . $this->data->get_tz_offset_field_id(
				CALENDAR_CALENDARS_FIELD_PREFIX . 'tz_offset'
			);

			$tf_field	= 'field_id_' . $this->data->get_time_format_field_id(
				CALENDAR_CALENDARS_FIELD_PREFIX . 'time_format'
			);

			$ics_field	= 'field_id_' . $this->data->get_ics_url_field_id(
				CALENDAR_CALENDARS_FIELD_PREFIX . 'ics_url'
			);

			$fields		= array(
				'tz_offset'		=> $tz_field,
				'time_format'	=> $tf_field,
				'ics_url'		=> $ics_field
			);

			if ($this->data->channel_is_valid_calendar($id) === FALSE)
			{
				// -------------------------------------
				// Add this calendar to the calendars table
				// -------------------------------------

				$id = $this->data->add_calendar($id, $site_id, $fields);
			}
			else
			{
				// -------------------------------------
				// Update
				// -------------------------------------

				$id = $this->data->update_calendar($id, $fields);
			}

			// -------------------------------------
			//  If the time format field was left blank,
			//  we'll update the data with the site default
			// -------------------------------------

			if ( ! isset($_POST[$tf_field]) OR $_POST[$tf_field] == '')
			{
				$this->data->update_time_format_field($id, $tf_field);
			}

			//--------------------------------------------
			//	Update imported events
			//--------------------------------------------

			$this->actions->import_ics_data($id);

			// -------------------------------------
			//	calendar permissions
			// -------------------------------------

			ee()->load->library('calendar_permissions');

			if (ee()->calendar_permissions->enabled())
			{
				ee()->calendar_permissions->save_calendar_permissions(
					$id,
					ee()->security->xss_clean($_POST)
				);
			}
		}

		// -------------------------------------
		//  events channel?
		// -------------------------------------

		elseif ($this->data->channel_is_events_channel($data[$channel_id]) === TRUE)
		{

			// -------------------------------------
			//  Are we creating or editing a modified occurrence?
			// -------------------------------------

			if (ee()->input->get_post('calendar_parent_entry_id') OR
				ee()->input->get_post('calendar_occurrence_id'))
			{
				return $this->edit_modified_occurrence($id);
			}

			// -------------------------------------
			//  Carry on.
			// -------------------------------------

			$event_id	= $this->data->event_exists($id);

			$edit		= ($event_id === FALSE) ? FALSE : TRUE;

			// -------------------------------------
			//  Grab the basics
			// -------------------------------------

			$basics = array(
				$channel_id,
				'entry_id',
				//'site_id', //we dont want to be setting this twice
				'calendar_id'
			);

			foreach ($basics as $var)
			{
				if (isset($data[$var]))
				{
					$$var		= $data[$var];
				}
				elseif (ee()->input->post($var) !== FALSE)
				{
					$$var		= ee()->input->post($var);
					$data[$var]	= ee()->input->post($var);
				}
				else
				{
					$$var		= FALSE;
					$errors[]	= 'invalid_'.$var;
				}
			}

			// -------------------------------------
			//  If calendar_id is blank, dump all existing event information
			// -------------------------------------

			if ($edit === TRUE AND $calendar_id == '')
			{
				return $this->remove_event_data($entry_id);
			}

			// -------------------------------------
			//  Grab the rest
			// -------------------------------------

			$vars = array(
				'rule_id',
				'start_date',
				'end_date',
				'all_day',
				'start_time',
				'end_time',
				'rule_type',
				'repeat_years',
				'repeat_months',
				'repeat_days',
				'repeat_weeks',
				'days_of_week',
				'relative_dow',
				'days_of_month',
				'months_of_year',
				'end_by',
				'end_after',
				'occurrences'
			);

			foreach ($vars as $var)
			{
				$data[$var] = ee()->input->post($var);
			}

			// -------------------------------------
			//  Is the calendar ID valid?
			// -------------------------------------

			if ($this->data->channel_is_valid_calendar($calendar_id) !== TRUE AND
				( ! isset($this->cache['ical']) OR
				 $this->cache['ical'] !== TRUE))
			{
				return;
				//$errors[] = 'invalid_calendar_id';
			}

			// -------------------------------------
			//  Errors?
			// -------------------------------------

			if ( ! empty($errors))
			{
				$message = '';

				foreach ($errors as $error)
				{
					$message .= '<p>' . lang($error) . '</p>';
				}

				return $this->show_error($message);
			}

			// -------------------------------------
			//  Load Calendar_datetime
			// -------------------------------------

			$this->_load_cdt();

			// -------------------------------------
			//  Validate the primary start and end dates
			// -------------------------------------

			if (isset($data['start_date'][0]))
			{
				$start_date	= $data['start_date'][0];
			}

			if (isset($data['end_date'][0]) AND $data['end_date'][0])
			{
				$end_date	= $data['end_date'][0];
			}
			else
			{
				$end_date	= $start_date;
			}

			if ($this->CDT->is_valid_ymd($start_date) === FALSE)
			{
				if (empty($data['occurrences']['date']))
				{
					$errors[] = 'invalid_start_date';
				}
				else
				{
					$dates			= array();
					$start_times	= array();
					$end_times		= array();
					$rule_types		= array();

					asort($data['occurrences']['date']);

					foreach ($data['occurrences']['date'] as $k => $v)
					{
						$dates[]		= $v;
						$all_day		= ((isset($data['occurrences']['all_day'][$k]) AND
											$data['occurrences']['all_day'][$k] == 'y') OR
											 ($data['occurrences']['start_time'][$k] == '' AND
											 $data['occurrences']['end_time'][$k] == '')) ? 'y' : 'n';
						$all_days[]		= $all_day;
						$start_times[]	= $data['occurrences']['start_time'][$k];
						$end_times[]	= $data['occurrences']['end_time'][$k];
						$rule_types[]	= $data['occurrences']['rule_type'][$k];
					}

					$data['occurrences']['date']			= $dates;
					$data['occurrences']['start_time']		= $start_times;
					$data['occurrences']['end_time']		= $end_times;
					$data['occurrences']['rule_type']		= $rule_types;
					$data['occurrences']['all_day']			= $all_days;

					$start_date	= $data['start_date'][0]	= $data['occurrences']['date'][0];
					$end_date	= $data['end_date'][0]		= $data['occurrences']['date'][0];
					$start_time	= $data['start_time'][0]	= $data['occurrences']['start_time'][0];
					$end_time	= $data['end_time'][0]		= $data['occurrences']['end_time'][0];
					$rule_type	= $data['rule_type'][0]		= $data['occurrences']['rule_type'][0];
					$all_day	= $data['all_day'][0]		= $data['occurrences']['all_day'][0];

					if ($this->CDT->is_valid_ymd($start_date) === FALSE)
					{
						$errors[] = 'invalid_start_date';
					}
				}
			}

			// -------------------------------------
			//  $end_date isn't required, but if it's provided it must be valid
			// -------------------------------------

			if ($end_date != '' AND $this->CDT->is_valid_ymd($end_date) === FALSE)
			{
				$errors[] = 'invalid_end_date';
			}

			// -------------------------------------
			//  All day?
			// -------------------------------------

			$all_day = (isset($data['all_day'][0]) AND
						$data['all_day'][0] == 'y') ?
						'y' : 'n';

			// -------------------------------------
			//  Validate the start and end times,
			//	neither of which is required
			// -------------------------------------

			$start_time	= (isset($data['start_time'][0]) AND $all_day != 'y') ?
							$data['start_time'][0] : '';
			$end_time	= (isset($data['end_time'][0]) AND $all_day != 'y')	?
							$data['end_time'][0] : '';

			if ($start_time != '' AND
				$this->CDT->is_valid_time($start_time) === FALSE)
			{
				$errors[] = 'invalid_start_time';
			}

			if ($end_time != '' AND
				$this->CDT->is_valid_time($end_time) === FALSE)
			{
				$errors[] = 'invalid_end_time';
			}

			// -------------------------------------
			//  Does it recur?
			// -------------------------------------

			$recurs = $this->event_recurs($data);

			// -------------------------------------
			//  Errors?
			// -------------------------------------

			if ( ! empty($errors))
			{
				$message = '';

				foreach ($errors as $error)
				{
					$message .= '<p>' . lang($error) . '</p>';
				}

				return $this->show_error($message);
			}

			// -------------------------------------
			//  Save entry ID for quick save
			// -------------------------------------

			$this->cache['entry_id']	= $id;

			// -------------------------------------
			// Add this event to the events table, or update
			// -------------------------------------

			$start_array	= $this->CDT->ymd_to_array($start_date);
			$end_array		= $this->CDT->ymd_to_array($end_date);

			$info = array(
				'site_id'		=> $site_id,
				'calendar_id'	=> $calendar_id,
				'entry_id'		=> $id,
				'start_date'	=> $start_date,
				'start_year'	=> $this->CDT->trim_leading_zeros($start_array['year']),
				'start_month'	=> $this->CDT->trim_leading_zeros($start_array['month']),
				'start_day'		=> $this->CDT->trim_leading_zeros($start_array['day']),
				'all_day'		=> $all_day,
				'start_time'	=> str_pad($start_time, 4, '0', STR_PAD_LEFT),
				'end_date'		=> $end_date,
				'end_year'		=> $this->CDT->trim_leading_zeros($end_array['year']),
				'end_month'		=> $this->CDT->trim_leading_zeros($end_array['month']),
				'end_day'		=> $this->CDT->trim_leading_zeros($end_array['day']),
				'end_time'		=> str_pad($end_time, 4, '0', STR_PAD_LEFT),
				'recurs'		=> $recurs
			);

			if ($edit AND $event_id)
			{
				$info['event_id'] = $event_id;
			}

			$event_id = $id;

			if ($edit === TRUE)
			{
				$this->data->update_event($info);
			}
			else
			{
				$this->data->add_event($info);
			}

			// -------------------------------------
			//  Fetch existing rules, occurrences and exceptions,
			//	so we know what has been changed
			// -------------------------------------

			$rule_ids	= array();
			foreach ($data['rule_id'] as $r)
			{
				if ($r)
				{
					$rule_ids[] = $r;
				}
			}

			// -------------------------------------
			//  Drop existing rules, occurrences, and exceptions
			// -------------------------------------

			if ($edit === TRUE)
			{
				$this->data->remove_rules_by_event_id($id, $rule_ids);
				$this->data->remove_exceptions_by_event_id($id);

				//this only fires if the checkbox has been selected for this
				//or if there is no checkbox at all,
				//	which means we should delete
				//the event in the edit area
				if ( ! $this->check_yes(ee()->input->get_post('has_edited_occurrences')) OR
					 $this->check_yes(ee()->input->get_post('remove_edited_occurrences')))
				{
					$this->data->remove_occurrences_by_event_id($id);
				}
			}

			// -------------------------------------
			//  Deal with recurrences
			// -------------------------------------

			if ($recurs == 'y')
			{
				$number_of_rules	= count($data['start_date']);
				$rules_data			= $this->clean_recurrence_rules($data);

				foreach ($rules_data as $k => $rule)
				{
					if ($rule['single_date'] == 'y')
					{
						// -------------------------------------
						//  Skip this item if it matches the "main" item
						// -------------------------------------

						if ($start_date == $rule['start_date'] AND
							$start_time == $rule['start_time'] AND
							$end_time   == $rule['end_time'])
						{
							continue;
						}

						$info = array(
							//'occurrence_id'		=> '0',
							//'exception_id'		=> '0',
							'event_id'			=> $event_id,
							'calendar_id'		=> $data['calendar_id'],
							'site_id'			=> $data['site_id'],
							'entry_id'			=> $id,
							'start_date'		=> $this->is_positive_intlike($rule['start_date']) ? $rule['start_date'] : 0,
							'start_year'		=> $this->is_positive_intlike($rule['start_year']) ? $rule['start_year'] : 0,
							'start_month'		=> $this->is_positive_intlike($rule['start_month']) ? $rule['start_month'] : 0,
							'start_day'			=> $this->is_positive_intlike($rule['start_day']) ? $rule['start_day'] : 0,
							'all_day'			=> $rule['all_day'],
							'start_time'		=> $this->is_positive_intlike($rule['start_time']) ? $rule['start_time'] : 0,
							'end_date'			=> ($rule['end_date'] != 0) ?
													$rule['end_date'] :
													$rule['start_date'],
							'end_year'			=> ($rule['end_year'] != 0) ?
													$rule['end_year'] :
													$rule['start_year'],
							'end_month'			=> ($rule['end_month'] != 0) ?
													$rule['end_month'] :
													$rule['start_month'],
							'end_day'			=> ($rule['end_day'] != 0) ?
													$rule['end_day'] :
													$rule['start_day'],
							'end_time'			=> $rule['end_time']
						);

						if ($rule['rule_type'] != '-')
						{
							unset($info['exception_id']);
							$this->data->add_occurrence($info);
						}
						else
						{
							unset($info['occurrence_id'],
								  $info['all_day'],
								  $info['end_date'],
								  $info['end_year'],
								  $info['end_month'],
								  $info['end_day'],
								  $info['end_time']);

							$this->data->add_exception($info);
						}
					}
					else
					{
						$info = array(
							'event_id'			=> $event_id,
							'calendar_id'		=> $calendar_id,
							'entry_id'			=> $id,
							'rule_type'			=> ($rule['rule_type'] == '-') ?
													'-' : '+',
							'start_date'		=> $rule['start_date'],
							'all_day'			=> (isset($rule['all_day'])) ?
													$rule['all_day'] : 'n',
							'start_time'		=> $this->is_positive_intlike($rule['start_time']) ? $rule['start_time'] : 0,
							'end_date'			=> $this->is_positive_intlike($rule['end_date']) ? $rule['end_date'] : 0,
							'end_time'			=> $this->is_positive_intlike($rule['end_time']) ? $rule['end_time'] : 0,
							'repeat_years'		=> $this->is_positive_intlike($rule['repeat_years']) ? $rule['repeat_years'] : 0,
							'repeat_months'		=> $this->is_positive_intlike($rule['repeat_months']) ? $rule['repeat_months'] : 0,
							'repeat_days'		=> $this->is_positive_intlike($rule['repeat_days']) ? $rule['repeat_days'] : 0,
							'repeat_weeks'		=> $this->is_positive_intlike($rule['repeat_weeks']) ? $rule['repeat_weeks'] : 0,
							'days_of_week'		=> $rule['days_of_week'],
							'relative_dow'		=> $rule['relative_dow'],
							'days_of_month'		=> $rule['days_of_month'],
							'months_of_year'	=> $rule['months_of_year'],
							'stop_after'		=> $this->is_positive_intlike($rule['stop_after']) ? $rule['stop_after'] : 0,
							'stop_by'			=> $this->is_positive_intlike($rule['stop_by']) ? $rule['stop_by'] : 0,
							'last_date'			=> $this->is_positive_intlike($rule['last_date']) ? $rule['last_date'] : 0
						);

						if ($edit == TRUE AND
							isset($rule['rule_id']) AND
							$rule['rule_id'] != FALSE)
						{
							$info['rule_id'] = $rule['rule_id'];
							$this->data->update_rule($info);
						}
						else
						{
							$this->data->add_rule($info);
						}

					}
				}

				// -------------------------------------
				//  Leftover rules, occurrences or exceptions? Then dump 'em.
				// -------------------------------------

				/*
				foreach ($rules as $rule_id)
				{
					$this->data->remove_rule($rule_id);
				}

				foreach ($occurrences as $date => $info)
				{
					$this->data->remove_occurrence($info);
				}

				foreach ($exceptions as $date => $info)
				{
					$this->data->remove_exception($info);
				}
				*/

				// -------------------------------------
				//  Update the last_date column in the events table
				// -------------------------------------

				$this->data->update_last_date($id);
			}

			// -------------------------------------
			//  Bail out early if we're importing icalendar stuff
			// -------------------------------------

			if (isset($this->cache['ical']) AND
				$this->cache['ical'] === TRUE)
			{
				ee()->extensions->end_script = TRUE;

				// -------------------------------------
				//  Tell the icalendar import method what ID we played with
				// -------------------------------------

				$this->cache['ical_event_id'] = $event_id;
				$this->cache['ical_entry_id'] = $id;
			}
		}
	}
	/* END submit_new_entry_end() */


	// --------------------------------------------------------------------

	/**
	 * Entry Submission Redirect
	 *
	 */

	public function entry_submission_end($id, $meta, $data)
	{
		$this->submit_new_entry_end($id, array_merge($data, $meta), '');
	}
	/* END entry_submission_redirect */


	// --------------------------------------------------------------------

	/**
	 * Create/Edit a modified occurrence
	 *
	 * @param	int		$entry_id	Entry ID
	 * @return	null
	 */

	public function edit_modified_occurrence($entry_id)
	{
		$data = array(
			'occurrence_id'	=> ee()->input->post('calendar_occurrence_id'),
			'event_id'		=> ee()->input->post('calendar_parent_entry_id'),
			'calendar_id'	=> ee()->input->post('calendar_id'),
			'site_id'		=> ee()->input->post('site_id'),
			'entry_id'		=> $entry_id,
			'start_date'	=> ee()->input->post('start_date'),
			'start_year'	=> substr(
				ee()->input->post('start_date'),
				0,
				strlen(ee()->input->post('start_date')) - 4
			),
			'start_month'	=> substr(ee()->input->post('start_date'), -4, 2),
			'start_day'		=> substr(ee()->input->post('start_date'), -2),
			'all_day' 		=> ee()->input->post('all_day'),
			'start_time'	=> ee()->input->post('start_time'),
			'end_date'		=> ee()->input->post('end_date'),
			'end_year'		=> substr(
				ee()->input->post('end_date'),
				0,
				strlen(ee()->input->post('end_date')) - 4
			),
			'end_month'		=> substr(ee()->input->post('end_date'), -4, 2),
			'end_day'		=> substr(ee()->input->post('end_date'), -2),
			'end_time'		=> ee()->input->post('end_time')
		);

		if ($data['occurrence_id'])
		{
			$this->data->update_occurrence($data);
		}
		else
		{
			unset($data['occurrence_id']);
			$this->data->add_occurrence($data);
		}

		return;
	}
	/* END edit_modified_occurrence() */

	// --------------------------------------------------------------------

	/**
	 * Delete entries loop
	 * Deleting a calendar? Also delete the entries.
	 * Deleting an entry? Remove the event and its occurrences.
	 *
	 * @param	int	$entry_id
	 * @param	int	$channel_id
	 * @return
	 */

	public function delete_entries_loop($entry_id, $channel_id)
	{
		// -------------------------------------
		//  Is this weblog the calendar weblog?
		// -------------------------------------

		if ($this->data->channel_is_calendars_channel($channel_id) === TRUE)
		{
			// -------------------------------------
			// Remove this calendar and its events
			// -------------------------------------

			$this->data->delete_calendar($entry_id);
		}
		elseif ($this->data->channel_is_events_channel($channel_id) === TRUE)
		{
			// -------------------------------------
			// Remove this event
			// -------------------------------------

			$this->data->delete_event($entry_id);
		}
	}
	/* END delete_entries_loop() */


	// --------------------------------------------------------------------

	/**
	 * Publish form headers (1.x only)
	 *
	 * @param	str	$a
	 * @param	str	$b
	 * @param	int	$c
	 * @param	int	$weblog_id
	 * @return
	 */

	public function publish_form_headers($a, $b, $c, $channel_id)
	{
		$output = '';

		if (ee()->extensions->last_call !== FALSE)
		{
			$output = ee()->extensions->last_call;
		}

		if ($this->data->channel_is_events_channel($channel_id) OR
			$this->data->channel_is_calendars_channel($channel_id))
		{
			$this->actions();
			$output .= $this->actions->datepicker_js();
			$output .= $this->actions->datepicker_css();
			//ee()->dsp->extra_header .= $output;
		}

		return $output;
	}
	// END publish_form_headers()


	// --------------------------------------------------------------------

	/**
	 * Publish form start (1.x only)
	 *
	 * @param	string	$which		Type of call
	 * @param	string	$error		Not relevant to us
	 * @param	int		$entry_id	Entry ID
	 * @param	string	$hidden		Not relevant to us
	 * @return	null
	 */

	public function publish_form_start($which, $error, $entry_id, $hidden)
	{
		if ( ! isset($this->cache['quicksave']) OR
			 $this->cache['quicksave'] !== TRUE)
		{
			return ee()->extensions->last_call;
		}

		// -------------------------------------
		//  If we're here it means they used
		//	quick save or preview
		// -------------------------------------

		$data	= array(
			'weblog_id'		=> ee()->input->post('weblog_id'),
			'channel_id'	=> ee()->input->post('channel_id'),
			'site_id'		=> (ee()->input->post('site_id')) ?
									ee()->input->post('site_id') :
									$this->data->get_site_id()
		);

		return $this->submit_new_entry_end($entry_id, $data, FALSE);
	}
	// END publish_form_start()


	// --------------------------------------------------------------------

	/**
	 * Submit new entry start
	 *
	 * @return	null
	 */

	public function submit_new_entry_start()
	{
		// -------------------------------------
		//  This is really obnoxious, but EE makes us do this for Quick Save/Preview to work
		// -------------------------------------

		if ($this->data->channel_is_calendars_channel(ee()->input->post('weblog_id')) === TRUE OR
			$this->data->channel_is_events_channel(ee()->input->post('weblog_id')) === TRUE)
		{
			$this->cache['quicksave']	= TRUE;
		}
	}
	/* END submit_new_entry_start() */


	// --------------------------------------------------------------------

	/**
	 * Publish form end
	 *
	 * @param	int	$channel_id
	 * @return
	 */

	public function publish_form_end($channel_id, $is_ee2_ft = FALSE, $ft_data = array())
	{
		$output = '';

		if (ee()->extensions->last_call !== FALSE AND
			is_string(ee()->extensions->last_call))
		{
			$output = ee()->extensions->last_call;
		}

		$this->actions();

		if ($this->data->channel_is_calendars_channel($channel_id) === TRUE)
		{
			if (ee()->input->get('entry_id'))
			{
				$cbasics 						= $this->data->calendar_basics();
				$this->cached_data['tz_offset'] = $cbasics[
					ee()->input->get('entry_id')
				]['tz_offset'];
			}
			else
			{
				$this->cached_data['tz_offset'] = FALSE;
				$output .= '<div id="calendar_time_format" title="field_id_' .
								$this->data->get_time_format_field_id().'">'.
								$this->data->preference('time_format').'</div>';
			}

			$field_title 	= 'field_id_' . $this->data->get_tz_offset_field_id();
			$tz_data		= $this->cached_data['tz_offset'];

			if ( $is_ee2_ft )
			{
				$field_title 	= $ft_data['name'];
				$tz_data		= $ft_data['value'];
			}

			$output 		.= '<div id="calendar_timezone_menu" title="' .
									$field_title . '">'.
								$this->actions()->timezone_menu($tz_data) .
								"</div>\n";

			// -------------------------------------
			//	calendar permissions
			// -------------------------------------

			ee()->load->library('calendar_permissions');

			if (ee()->calendar_permissions->enabled())
			{
				$calendar_id 	= ee()->input->get('entry_id');
				$calendar_id 	= (
					(string) $calendar_id !== '' AND
					is_numeric($calendar_id) AND
					$calendar_id > 0
				) ? $calendar_id : 0;

				$output .= $this->view(
					'publish_form/group_permissions.html',
					array(
						'permission_data' 		=> ee()->calendar_permissions->get_group_permissions($calendar_id),
						'groups_allowed_all' 	=> ee()->calendar_permissions->get_groups_allowed_all(),
						'groups_denied_all' 	=> ee()->calendar_permissions->get_groups_denied_all(),
						'member_groups' 		=> $this->data->get_member_groups(),
						'lang_allow_all'		=> lang('allow_all'),
						'lang_permissions_instructions'	=> lang('permissions_instructions'),
						'lang_group_permissions' => lang('group_permissions'),
						'lang_instructions'		=> lang('instructions')
					),
					TRUE
				);
			}
		}
		elseif ($this->data->channel_is_events_channel($channel_id) === TRUE)
		{
			$data = array();

			if ($id = ee()->input->get_post('entry_id') OR
				(isset($this->cache['entry_id']) AND
				 $id = $this->cache['entry_id']))
			{
				$data = $this->data->fetch_event_data_for_view($id);
			}

			if (! isset($data['rules'])) $data['rules'] = array();
			if (! isset($data['occurrences'])) $data['occurrences'] = array();
			if (! isset($data['exceptions'])) $data['exceptions'] = array();

			if (ee()->input->get('entry_id') AND
				$this->data->get_event_entry_id_by_channel_entry_id(
					ee()->input->get('entry_id')
				) != FALSE AND
				$this->data->get_event_entry_id_by_channel_entry_id(
					ee()->input->get('entry_id')
				) != ee()->input->get('entry_id')
			)
			{
				$data['edit_occurrence'] = TRUE;
			}
			else
			{
				$data['edit_occurrence'] = FALSE;
			}

			$output .= $this->actions->date_widget($data);
		}

		return $output;
	}
	// END publish_form_end()


	// --------------------------------------------------------------------

	/**
	 * Clean recurrence rules
	 *
	 * @param	array	$data	Array of data
	 * @return	array
	 */

	public function clean_recurrence_rules($data)
	{
		$array = array();
		$number_of_rules = count($data['start_date']);
		$number_of_occurrences = count($data['occurrences']['date']);

		$this->_load_cdt();

		for ($i = 0; $i < $number_of_occurrences; $i++)
		{
			if (isset($data['occurrences']['date'][$i]) AND
				! empty($data['occurrences']['date'][$i]))
			{
				// -------------------------------------
				//  We need this to verify the date
				// -------------------------------------

				if ($this->CDT->is_valid_ymd($data['occurrences']['date'][$i]) === TRUE)
				{
					// -------------------------------------
					//  Stuff we want
					// -------------------------------------

					$date_data					= $this->CDT->ymd_to_array(
						$data['occurrences']['date'][$i]
					);

					$rule_data['start_year']	= $date_data['year'];
					$rule_data['start_month']	= $date_data['month'];
					$rule_data['start_day']		= $date_data['day'];

					$rule_data['all_day']		= (
						isset($data['occurrences']['all_day'][$i]) AND
						$data['occurrences']['all_day'][$i] == 'y'
					) ?	'y' : 'n';

					$rule_data['end_year']		= $date_data['year'];
					$rule_data['end_month']		= $date_data['month'];
					$rule_data['end_day']		= $date_data['day'];
					$rule_data['start_time']	= $data['occurrences']['start_time'][$i];
					$rule_data['end_time']		= $data['occurrences']['end_time'][$i];

					$rule_data['rule_type']		= (
						in_array(
							$data['occurrences']['rule_type'][$i],
							array('-', '+')
						)
					) ? $data['occurrences']['rule_type'][$i] : '+';

					// -------------------------------------
					//  All other rules must be squarshed
					// -------------------------------------

					$rule_data['repeat_years'] 	= '0';
					$rule_data['repeat_months'] = '0';
					$rule_data['repeat_days'] 	= '0';
					$rule_data['repeat_weeks'] 	= '0';
					$rule_data['days_of_week'] 	= '';
					$rule_data['relative_dow'] 	= '';
					$rule_data['days_of_month'] = '';
					$rule_data['months_of_year'] = '';
					$rule_data['stop_after'] 	= '0';
					$rule_data['stop_by'] 		= '0';
					$rule_data['last_date'] 	= '0';
					$rule_data['single_date']	= 'y';
					$rule_data['recurs']		= 'n';
					$rule_data['start_date']	= $data['occurrences']['date'][$i];
					$rule_data['end_date'] 		= $data['occurrences']['date'][$i];

					// -------------------------------------
					//  Add it to the array
					// -------------------------------------

					$array[] = $rule_data;
				}

				// -------------------------------------
				//  Continue on to the next iteration
				// -------------------------------------

				continue;
			}
		}

		for ($i = 0; $i < $number_of_rules; $i++)
		{
			// -------------------------------------
			//  Are rule fields specified?
			// -------------------------------------

			$fields = array(
				'repeat_years',
				'repeat_months',
				'repeat_days',
				'repeat_weeks',
				'days_of_week',
				'relative_dow',
				'days_of_month',
				'months_of_year'
			);

			$occurrence = TRUE;

			foreach ($fields as $item)
			{
				if (isset($data[$item][$i]) AND $data[$item][$i] != FALSE)
				{
					$occurrence = FALSE;
					break;
				}
			}

			if ($occurrence === TRUE)
			{
				// -------------------------------------
				//  We don't actually want to handle the primary date this way
				// -------------------------------------

				if ($i == 0) continue;

				// -------------------------------------
				//  We need this to verify the date
				// -------------------------------------

				if ($this->CDT->is_valid_ymd($data['start_date'][$i]) === TRUE)
				{
					// -------------------------------------
					//  Stuff we want
					// -------------------------------------

					$start_date_data			= $this->CDT->ymd_to_array($data['start_date'][$i]);
					$rule_data['start_year']	= $start_date_data['year'];
					$rule_data['start_month']	= $start_date_data['month'];
					$rule_data['start_day']		= $start_date_data['day'];
					$rule_data['start_time']	= $data['start_time'][$i];
					$end_date_data				= $this->CDT->ymd_to_array($data['end_date'][$i]);
					$rule_data['end_year']		= $end_date_data['year'];
					$rule_data['end_month']		= $end_date_data['month'];
					$rule_data['end_day']		= $end_date_data['day'];
					$rule_data['end_time']		= $data['end_time'][$i];
					$rule_data['rule_type']		= (in_array($data['rule_type'][$i], array('-', '+'))) ? $data['rule_type'][$i] : '+';
					$rule_data['all_day']		= ($data['all_day'][$i] == 'y') ? 'y' : 'n';

					// -------------------------------------
					//  All other rules must be squarshed
					// -------------------------------------

					$rule_data['repeat_years']		= '';
					$rule_data['repeat_months']		= '';
					$rule_data['repeat_days']		= '';
					$rule_data['repeat_weeks']		= '';
					$rule_data['days_of_week']		= '';
					$rule_data['relative_dow']		= '';
					$rule_data['days_of_month']		= '';
					$rule_data['months_of_year']	= '';
					$rule_data['stop_after']		= '';
					$rule_data['stop_by']			= '';
					$rule_data['last_date']			= '';
					$rule_data['single_date']		= 'y';

					//if we are ever to allow exclude _rules_ this needs to happen
					//($start_date_data['ymd'] === $end_date_data['ymd']) ? 'y' : 'n';
					$rule_data['recurs']			= 'n';

					$rule_data['start_date']		= $data['start_date'][$i];
					$rule_data['end_date']			= $data['end_date'][$i];

					// -------------------------------------
					//  Add it to the array
					// -------------------------------------

					$array[] = $rule_data;
				}

				// -------------------------------------
				//  Continue on to the next iteration
				// -------------------------------------

				continue;
			}

			// -------------------------------------
			//  Carry on...
			// -------------------------------------

			$rule_data = array();

			// -------------------------------------
			//  Rule ID
			// -------------------------------------

			$rule_data['rule_id']	= (isset($data['rule_id'][$i])) ? $data['rule_id'][$i] : '';

			// -------------------------------------
			//  Rule type
			// -------------------------------------

			$rule_data['rule_type']	= (isset($data['rule_type'][$i])) ? $data['rule_type'][$i] : '+';

			// -------------------------------------
			//  Validate the start and end dates
			// -------------------------------------

			$start_date	= (isset($data['start_date'][$i])) ? $data['start_date'][$i] : '';
			$end_date	= (isset($data['end_date'][$i])) ? $data['end_date'][$i] : '';

			if ($this->CDT->is_valid_ymd($start_date) === FALSE)
			{
				$errors[] = 'invalid_start_date';
			}

			// -------------------------------------
			//  $end_date isn't required, but if it's provided it must be valid
			// -------------------------------------

			if ($end_date != '' AND $this->CDT->is_valid_ymd($end_date) === FALSE)
			{
				$errors[] = 'invalid_end_date';
			}

			// -------------------------------------
			//  Validate the start and end times, neither of which is required
			// -------------------------------------

			$start_time	= (isset($data['start_time'][$i])) ? $data['start_time'][$i] : '';
			$end_time	= (isset($data['end_time'][$i])) ? $data['end_time'][$i] : '';

			if ($start_time != '' AND $this->CDT->is_valid_time($start_time) === FALSE)
			{
				$errors[] = 'invalid_start_time';
			}

			if ($end_time != '' AND $this->CDT->is_valid_time($end_time) === FALSE)
			{
				$errors[] = 'invalid_end_time';
			}

			// -------------------------------------
			//  Errors?
			// -------------------------------------

			if ( ! empty($errors))
			{
				// TODO
				// For now we just ignore errors and skip the whole durn thing.
				// We should really be more helpful than that.

				continue;
			}

			// -------------------------------------
			//  Add some basics to the array
			// -------------------------------------

			$start_array 				= $this->CDT->ymd_to_array($start_date);
			$end_array 					= $this->CDT->ymd_to_array($end_date);

			$rule_data['start_date']	= $start_date;
			$rule_data['start_year']	= $this->CDT->trim_leading_zeros($start_array['year']);
			$rule_data['start_month']	= $this->CDT->trim_leading_zeros($start_array['month']);
			$rule_data['start_day']		= $this->CDT->trim_leading_zeros($start_array['day']);
			$rule_data['start_time']	= $start_time;
			$rule_data['end_date']		= $end_date;
			$rule_data['end_year']		= $this->CDT->trim_leading_zeros($start_array['year']);
			$rule_data['end_month']		= $this->CDT->trim_leading_zeros($start_array['month']);
			$rule_data['end_day']		= $this->CDT->trim_leading_zeros($start_array['day']);
			$rule_data['end_time']		= $end_time;
			$rule_data['all_day']		= $data['all_day'][$i];
			$rule_data['single_date']	= 'n';
			$rule_data['recurs']		= 'y';
			$rule_data['stop_by']		= (isset($data['end_by'][$i])) ? $data['end_by'][$i] : '';
			$rule_data['stop_after']	= (isset($data['end_after'][$i])) ? $data['end_after'][$i] : '';

			// -------------------------------------
			//  Numbers
			// -------------------------------------

			$rule_data['repeat_years']	= (! is_numeric($data['repeat_years'][$i])) ?
											0 : floor($data['repeat_years'][$i]);
			$rule_data['repeat_months']	= (! is_numeric($data['repeat_months'][$i])) ?
											0 : floor($data['repeat_months'][$i]);
			$rule_data['repeat_days']	= (! is_numeric($data['repeat_days'][$i])) ?
											0 : floor($data['repeat_days'][$i]);
			$rule_data['repeat_weeks']	= (! is_numeric($data['repeat_weeks'][$i])) ?
											0 : floor($data['repeat_weeks'][$i]);

			// -------------------------------------
			//  Days of week
			// -------------------------------------

			$rule_data['days_of_week'] = '';

			if ( ! empty($data['days_of_week'][$i]))
			{
				$valid 	= array('U', 'M', 'T', 'W', 'R', 'F', 'S');
				$days 	= explode('|', $data['days_of_week'][$i]);

				foreach ($days as $day)
				{
					if (in_array($day, $valid) AND ! strstr($rule_data['days_of_week'], $day))
					{
						$rule_data['days_of_week'] .= $day;
					}
				}
			}

			// -------------------------------------
			//  Relative days of week
			// -------------------------------------

			$rule_data['relative_dow'] = '';

			if ( ! empty($data['relative_dow'][$i]))
			{
				$valid 	= array(
					'1', '2', '3', '4', '5',
					'6', 'A', 'B', 'C', 'D',
					'E'
				);

				$dows 	= explode('|', $data['relative_dow'][$i]);

				foreach ($dows as $dow)
				{
					if (in_array($dow, $valid) AND ! strstr($rule_data['relative_dow'], $dow))
					{
						$rule_data['relative_dow'] .= $dow;
					}
				}
			}

			// -------------------------------------
			//  Days of month
			// -------------------------------------

			$rule_data['days_of_month'] = '';

			if ( ! empty($data['days_of_month'][$i]))
			{
				$valid 	= array(
					'1', '2', '3', '4', '5',
					'6', '7', '8', '9', 'A',
					'B', 'C', 'D', 'E', 'F',
					'G', 'H', 'I', 'J', 'K',
					'L', 'M', 'N', 'O', 'P',
					'Q', 'R', 'S', 'T', 'U',
					'V'
				);

				$days 	= explode('|', $data['days_of_month'][$i]);

				foreach ($days as $day)
				{
					if (in_array($day, $valid) AND ! strstr($rule_data['days_of_month'], $day))
					{
						$rule_data['days_of_month'] .= $day;
					}
				}
			}

			// -------------------------------------
			//  Months of year
			// -------------------------------------

			$rule_data['months_of_year'] = '';

			if ( ! empty($data['months_of_year'][$i]))
			{
				$valid 	= array(
					'1', '2', '3', '4', '5',
					'6', '7', '8', '9', 'A',
					'B', 'C'
				);

				$months = explode('|', $data['months_of_year'][$i]);

				foreach ($months as $month)
				{
					if (in_array($month, $valid) AND ! strstr($rule_data['months_of_year'], $month))
					{
						$rule_data['months_of_year'] .= $month;
					}
				}
			}

			// -------------------------------------
			//  Calculate last date
			// -------------------------------------

			if ($rule_data['stop_by'] == '' AND $rule_data['stop_after'] == '')
			{
				$rule_data['last_date'] = '';
			}
			else
			{
				// -------------------------------------
				//  Load the Calendar_event class
				// -------------------------------------

				if ( ! class_exists('Calendar_event'))
				{
					require_once CALENDAR_PATH.'calendar.event.php';
				}

				// -------------------------------------
				//  For the purposes of getting an accurate last_date, we need to
				//  treat all rules as though they are additive. That means
				//  temporarily changing - to + where appropriate.
				// -------------------------------------

				$sub = ($rule_data['rule_type'] == '-') ? TRUE : FALSE;

				if ($sub === TRUE)
				{
					$rule_data['rule_type'] = '+';
				}

				if ($rule_data['stop_by'] != '')
				{
					$EVENT = new Calendar_event(
						$this->prep_rule_data_for_calendar_event($rule_data),
						$rule_data['start_date'],
						$rule_data['stop_by'],
						10000
					);
				}
				elseif ($rule_data['stop_after'] != '')
				{
					$EVENT = new Calendar_event(
						$this->prep_rule_data_for_calendar_event($rule_data),
						$rule_data['start_date'],
						array(
							'year' 	=> 9999,
							'month' => 12,
							'day' 	=> 31
						),
						$rule_data['stop_after']
					);
				}

				$rule_data['last_date'] = array_pop(array_keys($EVENT->dates));

				// -------------------------------------
				//  Restore subtractive rules
				// -------------------------------------

				if ($sub === TRUE)
				{
					$rule_data['rule_type'] = '-';
				}
			}

			$array[] = $rule_data;
		}

		return $array;
	}
	/* END clean_recurrence_rules() */

	// --------------------------------------------------------------------

	/**
	 * Remove event data
	 *
	 * @param	int	$entry_id	Entry ID
	 * @return	null
	 */

	public function remove_event_data($entry_id)
	{
		$this->data->delete_event($entry_id);
	}
	/* END remove_event_data() */

	// --------------------------------------------------------------------

	/**
	 * Load Calendar_datetime
	 *
	 * @return	null
	 */

	protected function _load_cdt()
	{
		if ( ! class_exists('Calendar'))
		{
			require_once CALENDAR_PATH.'mod.calendar.php';
		}

		if ( ! class_exists('Calendar_datetime'))
		{
			require_once CALENDAR_PATH.'calendar.datetime.php';
		}

		if ($this->CDT === FALSE)
		{
			$this->CDT = new Calendar_datetime;
		}
	}
	/* END _load_cdt() */

	// --------------------------------------------------------------------

	/**
	 * Does the event recur?
	 *
	 * @param	array	$data	Array of rule data
	 * @return	str
	 */

	public function event_recurs($data)
	{
		$recurs = 'n';

		if (count($data['start_date']) > 1 OR
			(isset($data['occurrences']['date']) AND
			 is_array($data['occurrences']['date']) AND
			 ! empty($data['occurrences']['date'])
			)
		)
		{
			$recurs = 'y';
		}
		else
		{
			$fields = array(
				'repeat_years',
				'repeat_months',
				'repeat_weeks',
				'repeat_days',
				'relative_dow',
				'days_of_month',
				'months_of_year',
				'occurrences'
			);

			foreach ($fields as $field)
			{
				if (isset($data[$field][0]) AND
					$data[$field][0] != '' AND
					$data[$field][0] != array())
				{
					$recurs = 'y';
					break;
				}
			}
		}

		return $recurs;
	}
	/* END event_recurs() */

	// --------------------------------------------------------------------

	/**
	 * Prepare rule data for the Calendar_event class
	 *
	 * @param	array	$rule_data	Array of rule data
	 * @return	array
	 */

	protected function prep_rule_data_for_calendar_event($rule_data)
	{
		$new					= $rule_data;
		$which					= ($rule_data['rule_type'] == '-') ? 'sub' : 'add';
		$new['rules'][$which][]	= $rule_data;

		return $new;
	}
	/* END prep_rule_data_for_calendar_event() */

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 *
	 * A required method that we actually ignore because this extension is installed by its module
	 * and no other place.  If they want the extension enabled, they have to install the module.
	 *
	 * @access	public
	 * @return	null
	 */

	public function activate_extension(){}
	/* END activate_extension() */

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * A required method that we actually ignore because this extension is installed by its module
	 * and no other place.  If they want the extension disabled, they have to uninstall the module.
	 *
	 * @access	public
	 * @return	null
	 */

	public function disable_extension(){}
	/* END disable_extension() */

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * A required method that we actually ignore because this extension is updated by its module
	 * and no other place.  We cannot redirect to the module upgrade script because we require a
	 * confirmation dialog, whereas extensions were designed to update automatically as they will try
	 * to call the update script on both the User and CP side.
	 *
	 * @access	public
	 * @return	null
	 */

	public function update_extension(){}
	// END update_extension()


	// --------------------------------------------------------------------

	/**
	 * Error Page
	 *
	 * @access	public
	 * @param	string	$error	Error message to display
	 * @return	null
	 */

	public function error_page($error = '')
	{
		$this->cached_vars['error_message'] = $error;

		$this->cached_vars['page_title'] = lang('error');

		// -------------------------------------
		//  Output
		// -------------------------------------

		$this->ee_cp_view('error_page.html');
	}
	// END error_page()


	// --------------------------------------------------------------------

	/**
	 * cp_js_end
	 *
	 * @access	public
	 * @return	string
	 */

	public function cp_js_end ()
	{
		$js = (ee()->extensions->last_call) ?
				ee()->extensions->last_call : '';

		//not combined with the above to avoid loading this lib every page
		//load for no reason
		ee()->load->library('calendar_permissions');

		if ( ee()->calendar_permissions->filter_on() != 'disable_link')
		{
			return $js;
		}

		$this->load_session();

		$group_id = ee()->session->userdata['group_id'];

		if ( $group_id != 1)
		{
			$js .= $this->view('cp_js_end.js', array(
				'act' 			=> $this->get_action_url('permissions_json') . "&group_id=" . $group_id,
				'src' 			=> $this->sc->theme_url . 'calendar/js/edit_content_permissions.js',
				'css_src'		=> $this->sc->theme_url . 'calendar/css/edit_content_permissions.css',
				'lang_dialog_title' 	=> lang('permission_dialog_title'),
				'lang_dialog_message' 	=> lang('invalid_calendar_permissions'),
				'lang_ok' 		=> lang('ok')
			), TRUE);
		}

		return $js;
	}
	//END cp_js_end


	// --------------------------------------------------------------------

	/**
	 * calendar_edit_entries_search_where
	 * this only works when a special hook is added manually to the search_model
	 * see Calendar documention for details
	 *
	 * @access	public
	 * @return	string
	 */

	public function edit_entries_additional_where ()
	{
		$where = (ee()->extensions->last_call) ?
					ee()->extensions->last_call : array();

		$group_id = ee()->session->userdata['group_id'];

		if ($group_id == 1)
		{
			return $where;
		}

		ee()->load->library('calendar_permissions');

		$prefs = $this->data->get_module_preferences();

		if ( ee()->calendar_permissions->filter_on() != 'search_filter'
			 OR in_array($group_id, ee()->calendar_permissions->get_groups_allowed_all()))
		{
			return $where;
		}

		//if they arent allowed to see anything, lets skip the processing and just block the channel
		$allowed_calendars_for_group = ee()->calendar_permissions->get_allowed_calendars_for_group($group_id);

		if (empty($allowed_calendars_for_group))
		{
			$not_ch_id = 'channel_id !=';

			if ( ! isset($where[$not_ch_id]))
			{
				$where[$not_ch_id] = array();
			}
			else
			{
				if ( ! is_array($where[$not_ch_id]))
				{
					$where[$not_ch_id] = array($where[$not_ch_id]);
				}
			}

			$where[$not_ch_id][] = $prefs['event_weblog'];
		}
		else
		{
			$not_allowed_ids = ee()->calendar_permissions->get_denied_entry_ids($group_id);

			//if they are allowed to all calendars by checkboxes,
			//but not by the master allow all, this will be empty
			if ( ! empty($not_allowed_ids))
			{
				$not_entry_id = 'entry_id !=';

				if ( ! isset($where[$not_entry_id]))
				{
					$where[$not_entry_id] = array();
				}
				else
				{
					if ( ! is_array($where[$not_entry_id]))
					{
						$where[$not_entry_id] = array($where[$not_entry_id]);
					}
				}

				$where[$not_entry_id] = array_merge(
					$where[$not_entry_id],
					$not_allowed_ids
				);
			}
		}

		return $where;
	}
	//END edit_entries_additional_where


	// --------------------------------------------------------------------

	/**
	 * delete_entries_start
	 *
	 * makes sure that the entries the user is trying to delete,
	 * the user has permissions to
	 *
	 * @access	public
	 * @return	string
	 */

	public function delete_entries_start ()
	{
		$return = (ee()->extensions->last_call) ?
					ee()->extensions->last_call : '';

		ee()->load->library('calendar_permissions');

		$group_id = ee()->session->userdata['group_id'];

		if ($group_id != 1 AND ee()->calendar_permissions->enabled())
		{
			$count = count($_POST['delete']);

			//remove all the ones they are denied permission to
			foreach ($_POST['delete'] as $key => $delete_id)
			{
				//if there are only calendar IDs, lets alert
				if ( ! ee()->calendar_permissions->can_edit_entry($group_id, $delete_id))
				{
					unset($_POST['delete'][$key]);
				}
			}

			//if we've removed everything, they were all
			//denied and we need to alert
			if ($count > 0 and count($_POST['delete']) == 0)
			{
				ee()->extensions->end_script = TRUE;

				return $this->show_error(lang('invalid_calendar_permissions'));
			}
		}

		return $return;
	}
	//END delete_entries_start


	// --------------------------------------------------------------------

	/**
	 * update_multi_entries_start
	 *
	 * makes sure that the entries the user is trying to update,
	 * the user has permissions to
	 *
	 * @access	public
	 * @return	string
	 */

	public function update_multi_entries_start ()
	{
		$return = (ee()->extensions->last_call) ?
					ee()->extensions->last_call : '';

		ee()->load->library('calendar_permissions');

		$group_id = ee()->session->userdata['group_id'];

		if ($group_id != 1 AND ee()->calendar_permissions->enabled())
		{
			$count = count($_POST['entry_id']);

			//remove all the ones they are denied permission to
			foreach ($_POST['entry_id'] as $key => $entry_id)
			{
				//if there are only calendar IDs, lets alert
				if ( ! ee()->calendar_permissions->can_edit_entry($group_id, $entry_id))
				{
					foreach ($_POST as $post_key => $post_data)
					{
						if (is_array($_POST[$post_key]))
						{
							unset($_POST[$post_key][$key]);
						}
					}
				}
			}

			//if we've removed everything, they were all
			//denied and we need to alert
			if ($count > 0 and count($_POST['entry_id']) == 0)
			{
				ee()->extensions->end_script = TRUE;

				return $this->show_error(lang('invalid_calendar_permissions'));
			}
		}

		return $return;
	}
	//END update_multi_entries_start
}
/* END Class Calendar_extension */