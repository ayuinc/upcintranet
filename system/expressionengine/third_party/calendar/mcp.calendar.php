<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - Control Panel
 *
 * The control panel master class that handles all of the CP requests and displaying.
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/mcp.calendar.php
 */

if ( ! class_exists('Module_builder_calendar'))
{
	require_once 'addon_builder/module_builder.php';
}

class Calendar_mcp extends Module_builder_calendar
{
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	bool		Enable calling of methods based on URI string
	 * @return	string
	 */

	public function __construct( $switch = TRUE )
	{
		parent::__construct();

		if ((bool) $switch === FALSE) return; // Install or Uninstall Request

		// -------------------------------------
		//  We need our actions
		// -------------------------------------

		$this->actions();

		if (! defined('CALENDAR_CALENDARS_CHANNEL_NAME'))
		{
			define('CALENDAR_CALENDARS_CHANNEL_NAME', $this->actions->calendar_channel_shortname());
			define('CALENDAR_EVENTS_CHANNEL_NAME', $this->actions->event_channel_shortname());
		}

		// --------------------------------------------
		//  Module Menu Items
		// --------------------------------------------

		$menu	= array(
			'view_calendars'		=> array(
				'link'  => $this->base . AMP . 'method=view_calendars',
				'title' => lang('calendars')
			),
			'view_events'			=> array(
				'link'  => $this->base . AMP . 'method=view_events',
				'title' => lang('events')
			),
			/*
			'view_reminders'		=> array(	'link'  => $this->base.AMP.'method=view_reminders',
												'title' => lang('reminders')),
			*/
			'permissions'		=> array(
				'link'  => $this->base.AMP.'method=permissions',
				'title' => lang('permissions')
			),
			'preferences'			=> array(
				'link' 	=> $this->base . AMP . 'method=view_preferences',
				'title' => lang('preferences')
			),
			'module_demo_templates'		=> array(
				'link'	=> $this->base.'&method=code_pack',
				'title'	=> lang('demo_templates'),
			),
			'module_documentation'	=> array(
				'link'  => CALENDAR_DOCS_URL,
				'title' => lang('online_documentation'),
				'new_window' => TRUE
			),
		);

		$this->cached_vars['lang_module_version'] 	= lang('calendar_module_version');
		$this->cached_vars['module_version'] 		= CALENDAR_VERSION;
		$this->cached_vars['module_menu_highlight']	= 'view_calendars';
		$this->cached_vars['module_menu']			= $menu;
		//needed for header.html file views
		$this->cached_vars['js_magic_checkboxes']	= $this->js_magic_checkboxes();

		// --------------------------------------------
		//  Sites
		// --------------------------------------------

		$this->cached_vars['sites']	= array();

		foreach($this->data->get_sites() as $site_id => $site_label)
		{
			$this->cached_vars['sites'][$site_id] = $site_label;
		}

		// -------------------------------------
		//  We need our actions
		// -------------------------------------

		$this->actions();

		// -------------------------------------
		//  Grab the MOD file and related goodies
		// -------------------------------------

		if ( ! class_exists('Calendar'))
		{
			require_once CALENDAR_PATH.'mod.calendar.php';
		}

		$this->CAL = new Calendar();
		$this->CAL->load_calendar_datetime();

		// -------------------------------------
		//  need a special case for our beta versions
		//  this shouldn't be hit for normal EE updates
		// -------------------------------------

		if(
			(
				$this->version_compare(
					$this->database_version(),
					'<',
					CALENDAR_VERSION
				)
				AND
				//EE checks like this instead of version_compare
				! (CALENDAR_VERSION > $this->database_version())
			)
			OR
			! $this->extensions_enabled()
		)
		{
			// For EE 2.x, we need to redirect the request to Update Routine
			$_GET['method'] = 'calendar_module_update';

			$updated = TRUE;
		}
	}
	// END Calendar_cp_base()

	// --------------------------------------------------------------------

	public function index($message='')
	{
		return $this->view_calendars($message);
	}
	/* END index() */

	// --------------------------------------------------------------------

	/**
	 * View Calendars
	 *
	 * @access	public
	 * @param	string
	 * @return	null
	 */

	public function view_calendars($message='')
	{
		if ($message == '' AND isset($_GET['msg']))
		{
			$message = lang($_GET['msg']);
		}

		$this->cached_vars['message'] = $message;

		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->add_crumb(lang('calendars'));
		$this->build_crumbs();

		// -------------------------------------
		//  What should we show?
		// -------------------------------------

		$this->cached_vars['calendars'] = $this->data->calendar_basics();

		$this->cached_vars['current_page'] = $this->view('calendars.html', NULL, TRUE);

		// --------------------------------------------
		//  Load Homepage
		// --------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	/* END view_calendars() */

	// --------------------------------------------------------------------

	/**
	 * View Events
	 *
	 * @access	public
	 * @param	string
	 * @return	null
	 */

	public function view_events($message='')
	{
		// -------------------------------------
		//  Delete events?
		// -------------------------------------

		if (ee()->input->post('toggle') !== FALSE AND ee()->input->post('delete_confirm') != '')
		{
			return $this->delete_events_confirm();
		}
		elseif (ee()->input->post('delete') !== FALSE AND is_array(ee()->input->post('delete')) AND count(ee()->input->post('delete')) > 0)
		{
			$this->delete_events();
		}

		if ($message == '' AND isset($_GET['msg']))
		{
			$message = lang($_GET['msg']);
		}

		$this->cached_vars['message'] = $message;
		$this->cached_vars['module_menu_highlight'] = 'view_events';

		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->add_crumb(lang('events'));
		$this->build_crumbs();

		// -------------------------------------
		//  Data for the view(s)
		// -------------------------------------

		$this->cached_vars['calendar']	= (ee()->input->post('calendar')) ? ee()->input->post('calendar') : '';
		$this->cached_vars['status']	= (ee()->input->post('status')) ? ee()->input->post('status') : '';
		$this->cached_vars['recurs']	= (ee()->input->post('recurs')) ? ee()->input->post('recurs') : '';
		$this->cached_vars['date']		= (ee()->input->post('date')) ? ee()->input->post('date') : '';
		$this->cached_vars['direction']	= (ee()->input->post('date_direction')) ? ee()->input->post('date_direction') : '';
		$this->cached_vars['orderby']	= (ee()->input->post('orderby')) ? ee()->input->post('orderby') : 'title';
		$this->cached_vars['sort']		= (ee()->input->post('sort')) ? ee()->input->post('sort') : 'ASC';
		$this->cached_vars['offset']	= (ee()->input->get_post('offset')) ? ee()->input->get_post('offset') : 0;
		$this->cached_vars['limit']		= (ee()->input->get_post('limit')) ? ee()->input->get_post('limit') : 100;

		$this->cached_vars['calendars']	= $this->data->get_calendar_list();
		$this->cached_vars['statuses']	= $this->data->get_status_list();
		$this->cached_vars['recurses']	= array(	'y' => 'Yes',
													'n' => 'No'
													);

		$this->cached_vars['orderbys']	= array(	'event_id'		=> lang('event_id'),
													'title'			=> lang('event_title'),
													'calendar_name'	=> lang('calendar_name'),
													'status'		=> lang('status'),
													'recurs'		=> lang('recurs'),
													'start_date'	=> lang('first_date'),
													'last_date'		=> lang('last_date')
													);

		$this->cached_vars['sorts']		= array(	'ASC' => lang('ascending'),
													'DESC' => lang('descending')
													);

		$this->cached_vars['limits']	= array(	'10'	=> '10',
													'50'	=> '50',
													'100'	=> '100',
													'250'	=> '250',
													'500'	=> '500'
													);

		$this->cached_vars['directions']	= array(	'equal'		=> lang('this_date'),
														'greater'	=> lang('or_later'),
														'less'		=> lang('or_earlier')
														);

		$this->cached_vars['delete']	= lang('delete');

		$this->cached_vars['url']		= preg_replace('#&offset=\d+#', '', $_SERVER['REQUEST_URI']);

		// -------------------------------------
		//  form/link urls
		// -------------------------------------

		$this->cached_vars['form_url']	= $this->base . AMP . 'method=view_events' . AMP . 'limit=' . $this->cached_vars['limit'];

		// -------------------------------------
		//  Pagination
		// -------------------------------------

		ee()->load->library('pagination');
		$config['base_url'] 			= $this->cached_vars['form_url'];
		$config['total_rows'] 			= $this->data->event_basics(TRUE);
		$config['per_page'] 			= $this->cached_vars['limit'];
		$config['page_query_string'] 	= TRUE;
		$config['query_string_segment'] = 'offset';
		ee()->pagination->initialize($config);
		$this->cached_vars['paginate']	= ee()->pagination->create_links();

		$this->cached_vars['data']		= $this->data->event_basics();

		ee()->cp->add_js_script(array('ui' => 'datepicker'));

		// -------------------------------------
		//  Which View
		// -------------------------------------

		$this->cached_vars['current_page'] = $this->view('events.html', $this->cached_vars['data'], TRUE);

		// --------------------------------------------
		//  Load Homepage
		// --------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	/* END view_events() */

	// --------------------------------------------------------------------

	public function delete_events_confirm($message = '')
	{
		$this->cached_vars['message'] = $message;
		$this->cached_vars['module_menu_highlight'] = 'view_events';

		$this->cached_vars['question']	= str_replace('{COUNT}', count(ee()->input->post('toggle')), lang('delete_events_question'));
		$this->cached_vars['delete']	= lang('delete');
		$this->cached_vars['items']		= ee()->input->post('toggle');
		$this->cached_vars['form_url']	= $this->base . AMP . 'method=view_events' . AMP . 'msg=events_deleted';

		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->cached_vars['page_title']	= lang('delete_events_title');
		$this->add_crumb(lang('events'));
		$this->add_crumb(lang('delete_events'));
		$this->build_crumbs();

		// -------------------------------------
		//  Which View
		// -------------------------------------

		$this->cached_vars['current_page'] = $this->view('delete_events.html', NULL, TRUE);

		// --------------------------------------------
		//  Load Homepage
		// --------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	/* END delete_events_confirm() */

	// --------------------------------------------------------------------

	public function delete_events()
	{
		//--------------------------------------------
		//	call cal delete events hook
		//--------------------------------------------

		if (ee()->extensions->active_hook('calendar_delete_events') === TRUE)
		{
			$edata = ee()->extensions->call('calendar_delete_events', $this);
			if (ee()->extensions->end_script === TRUE) return;
		}

			if ( ! ee()->cp->allowed_group('can_access_content') )
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! ee()->cp->allowed_group('can_delete_self_entries') AND
			 ! ee()->cp->allowed_group('can_delete_all_entries'))
		{
			show_error(lang('unauthorized_access'));
		}

		// -------------------------------------------
		// 'delete_entries_start' hook.
		//  - Perform actions prior to entry deletion / take over deletion
		if (ee()->extensions->active_hook('delete_entries_start') === TRUE)
		{
			$edata = ee()->extensions->call('delete_entries_start');
			if (ee()->extensions->end_script === TRUE) return;
		}
		//
		// -------------------------------------------

		ee()->load->library('api');

		ee()->api->instantiate('channel_entries');
		$res = ee()->api_channel_entries->delete_entry(ee()->input->post('delete'));


		//sadly, if the entries were deleted somewhere else, they might not get properly deleted

		$delete = ee()->input->post('delete');

		//this should be an array coming from the delete_confirm, but JUUUST in case
		if ( ! is_array($delete) )
		{
			if ( ! is_numeric($delete))
			{
				return;
			}

			$delete = array($delete);
		}

		foreach($delete as $id)
		{
			$this->data->delete_event($id);
		}

	}
	/* ENd delete_events() */

	// --------------------------------------------------------------------

	/**
	 * Edit Occurrences
	 *
	 * @access	public
	 * @param	string
	 * @return	null
	 */

	public function edit_occurrences($message='')
	{
		if ($message == '' AND isset($_GET['msg']))
		{
			$message = lang($_GET['msg']);
		}

		$this->cached_vars['message'] 				= $message;
		$this->cached_vars['module_menu_highlight'] = 'view_events';

		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->add_crumb(lang('occurrences'));

		//must have an event_id
		if ( ee()->input->get_post('event_id') === FALSE ) return FALSE;

		// -------------------------------------
		//  filtering input data
		// -------------------------------------

		$this->cached_vars['event_id']		= $event_id 	= ee()->input->get_post('event_id');
		$this->cached_vars['status']		= $status 		= (ee()->input->get_post('status')) ?
																ee()->input->get_post('status') : '';
		$this->cached_vars['date']			= $input_date	= (ee()->input->get_post('date')) ?
																ee()->input->get_post('date') : '';
		$this->cached_vars['direction']		= $direction 	= (ee()->input->get_post('date_direction')) ?
																ee()->input->get_post('date_direction') : '';
		$this->cached_vars['orderby']		= $orderby		= (ee()->input->get_post('orderby')) ?
																ee()->input->get_post('orderby') : 'start_date';
		$this->cached_vars['sort']			= $sort			= (ee()->input->get_post('sort')) ?
																ee()->input->get_post('sort') : 'ASC';
		$this->cached_vars['offset']		= $offset 		= is_numeric(ee()->input->get_post('offset')) ?
																ee()->input->get_post('offset') : 0;
		$this->cached_vars['limit']			= $limit 		= (ee()->input->get_post('limit')) ?
																ee()->input->get_post('limit') : 50;
		$this->cached_vars['occurrences_limit']	= $occurrences_limit = (
			ee()->input->get_post('occurrences_limit')
		) ? ee()->input->get_post('occurrences_limit') : 100;

		//--------------------------------------------
		//	filtering options
		//--------------------------------------------

		// date filtering

		if ($input_date)
		{
			$formatted_date = $this->data->format_input_date($input_date);
			$formatted_date	= $formatted_date['ymd'];

			$dirs 			= array(
				'greater'	=> '>=',
				'less'		=> '<=',
				'equal'		=> '='
			);

			$dir 			= ($direction AND array_key_exists($direction, $dirs)) ?
								$dirs[$direction] : '=';
		}

		$this->cached_vars['statuses']		= $this->data->get_status_list();

		$this->cached_vars['orderbys']		= array(
			'title'				=> lang('event_title'),
			'start_date'		=> lang('event_date'),
			'status'			=> lang('status')
		);

		$this->cached_vars['sorts']			= array(
			'ASC' 				=> lang('ascending'),
			'DESC' 				=> lang('descending')
		);

		$this->cached_vars['limits']		= array(
			'10'				=> '10',
			'50'				=> '50',
			'100'				=> '100',
			'250'				=> '250',
			'500'				=> '500'
		);

		$this->cached_vars['directions']	= array(
			'greater'			=> lang('or_later'),
			'less'				=> lang('or_earlier')
		);

		if ($this->cached_vars['date'] != '' AND strpos($this->cached_vars['date'], '/') !== FALSE)
		{
			list($m, $d, $y)					= explode('/', $this->cached_vars['date']);
			$this->cached_vars['range_date']	= array('year' => $y, 'month' => $m, 'day' => $d);
		}
		else
		{
			$this->cached_vars['range_date']	= array();
		}

		$this->cached_vars['start_date']   		= ($this->cached_vars['direction'] != 'less') ?
													$this->cached_vars['range_date'] : array();

		$this->cached_vars['end_date']	   		= ($this->cached_vars['direction'] == 'less') ?
													$this->cached_vars['range_date'] : array();

		//--------------------------------------------
		//  Get time format
		//--------------------------------------------

		$this->cached_vars['clock_type'] 		= $clock_type = $this->data->preference('clock_type');

		//--------------------------------------------
		//	event data
		//--------------------------------------------

		$event_data	= $this->data->fetch_all_event_data(array($event_id));

		$events 	= array();

		if ( ! class_exists('Calendar_event'))
		{
			require_once CALENDAR_PATH.'calendar.event.php';
		}

		$start_ymd	= ($input_date ?
						$formatted_date :
						((isset($this->P['date_range_start']->value->ymd)) ?
							$this->P['date_range_start']->value->ymd :
							''));
		$end_ymd	= (isset($this->P['date_range_end']->value->ymd)) ? $this->P['date_range_end']->value->ymd : '';

		foreach ($event_data as $k => $edata)
		{
			$temp		= new Calendar_event($edata, $start_ymd, $end_ymd, $occurrences_limit);

			if (! empty($temp->dates))
			{
				$temp->prepare_for_output();
				$events[$edata['entry_id']] = $temp;
			}
		}

		//if this event isnt present, bail
		if ( isset( $events[$event_id]->default_data['entry_id'] ) === FALSE ) return FALSE;

		//--------------------------------------------
		//	Occurrence data
		//--------------------------------------------

		$entry_ids		= array();
		$entry_ids[]	= $events[$event_id]->default_data['entry_id'];

		if (isset($events[$event_id]->occurrences) AND ! empty($events[$event_id]->occurrences))
		{
			foreach ($events[$event_id]->occurrences as $ymd => $times)
			{
				foreach ($times as $time => $data)
				{
					if (! in_array($data['entry_id'], $entry_ids))
					{
						$entry_ids[] = $data['entry_id'];
					}
				}
			}
		}

		$odata = $this->data->fetch_occurrence_channel_data($entry_ids);

		//--------------------------------------------
		//	vars
		//--------------------------------------------

		if ( ! empty($events))
		{
			$this->cached_vars['events']		= $events;
			$this->cached_vars['odata']			= $odata;
			$this->cached_vars[$this->sc->db->channel_id]		= $channel_id = $odata[
																	$events[$event_id]->default_data['entry_id']
																   ][$this->sc->db->channel_id];
			$this->cached_vars['calendar_id']	= $calendar_id 	= $events[$event_id]->default_data['calendar_id'];
			$this->cached_vars['site_id']		= $site_id 		= $this->data->get_site_id();
			$this->cached_vars['start_time']	= $start_time	= $events[$event_id]->default_data['start_time'];
			$this->cached_vars['end_time']		= $end_time		= $events[$event_id]->default_data['end_time'];
			$this->cached_vars['all_day']		= $all_day		= ($events[$event_id]->default_data['all_day'] === TRUE) ?
																	'y' : 'n';
		}

		//--------------------------------------------
		//  Sort by date
		//--------------------------------------------

		if ($this->cached_vars['orderby'] == 'start_date')
		{
			foreach ($events as $id => $event)
			{
				if ($this->cached_vars['sort'] == 'DESC')
				{
					krsort($events[$id]->dates);
				}
				else
				{
					ksort($events[$id]->dates);
				}
				foreach ($event->dates as $date => $times)
				{
					if ($this->cached_vars['sort'] == 'DESC')
					{
						krsort($events[$id]->dates[$date]);
					}
					else
					{
						ksort($events[$id]->dates[$date]);
					}
				}
			}
		}

		//--------------------------------------------
		//	data and filtering
		//--------------------------------------------

		$event_views = array();

		$count = 0;

		foreach ($events[$event_id]->dates as $ymd => $times)
		{
			$this->CAL->CDT->change_ymd($ymd);

			//date filtering
			if ($input_date)
			{
				if ($dir == '>=' AND $formatted_date > $ymd)
				{
					continue;
				}

				if ($dir == '<=' AND $formatted_date < $ymd)
				{
					continue;
				}

				if ($dir == '=' AND $formatted_date != $ymd)
				{
					continue;
				}
			}

			foreach ($times as $time => $data)
			{
				$event_view = array();

				//--------------------------------------------
				//	status
				//--------------------------------------------

				$event_view['ostatus'] = (isset($events[$event_id]->occurrences[$ymd][$time]) AND
							isset($odata[$events[$event_id]->occurrences[$ymd][$time]['entry_id']]['status'])) ?
									$odata[$events[$event_id]->occurrences[$ymd][$time]['entry_id']]['status'] :
									$odata[$events[$event_id]->default_data['entry_id']]['status'];

				//--------------------------------------------
				//	status filter
				//--------------------------------------------

				//if the input status is filtering, we need to skip
				if ( ! in_array(ee()->input->get_post('status'), array(FALSE, ''), TRUE) AND
					 $event_view['ostatus'] !== ee()->input->get_post('status'))
				{
					continue;
				}

				//--------------------------------------------
				//	title
				//--------------------------------------------

				$event_view['title'] = (isset($events[$event_id]->occurrences[$ymd][$time])) ?
										$odata[$events[$event_id]->occurrences[$ymd][$time]['entry_id']]['title'] :
										$odata[$events[$event_id]->default_data['entry_id']]['title'];

				//--------------------------------------------
				//	time range
				//--------------------------------------------

				if ($data['all_day'] OR ($start_time == '0000' AND $end_time == '2400'))
				{
					$event_view['time_range'] = lang('all_day');
				}
				else
				{
					$this->CAL->CDT->change_time(substr($time, 0, 2), substr($time, 2, 2));
					$start 		= ($clock_type == '12') ?
									$this->CAL->CDT->format_date_string('h:i a') :
									$this->CAL->CDT->format_date_string('H:i');

					$this->CAL->CDT->change_time(substr($time, 4, 2), substr($time, 6, 2));
					$end 		= ($clock_type == '12') ?
									$this->CAL->CDT->format_date_string('h:i a') :
									$this->CAL->CDT->format_date_string('H:i');

					$event_view['time_range'] = "{$start} &ndash; {$end}";
				}

				//--------------------------------------------
				//	edit link
				//--------------------------------------------

				$start_time	= (isset($data['start_time'])) ? $data['start_time'] : $data['date']['time'];
				$end_time	= (isset($data['end_time'])) ? $data['end_time'] : $data['end_date']['time'];
				$start_time	= str_pad($start_time, 4, '0', STR_PAD_LEFT);
				$end_time	= str_pad($end_time, 4, '0', STR_PAD_LEFT);


				$edit_link = 	BASE .
								AMP . "C=content_publish" .
								AMP . "M=entry_form" .
								AMP . "use_autosave=n";

				if (isset($events[$event_id]->occurrences[$ymd][$time]) AND
					isset($odata[$events[$event_id]->occurrences[$ymd][$time]['entry_id']]['entry_id']) AND
						$odata[
							$events[$event_id]->occurrences[$ymd][$time]['entry_id']
						]['entry_id'] != $events[$event_id]->default_data['entry_id'])
				{
					$edit_link .=	AMP . "{$this->sc->db->channel_id}={$channel_id}" .
									AMP . "entry_id={$events[$event_id]->occurrences[$ymd][$time]['entry_id']}" .
									AMP . "event_id={$events[$event_id]->default_data['event_id']}" .
									AMP . "occurrence_id={$events[$event_id]->occurrences[$ymd][$time]['occurrence_id']}" .
									AMP . "calendar_id={$calendar_id}" .
									AMP . "site_id={$site_id}" .
									AMP . "start_time={$start_time}" .
									AMP . "end_time={$end_time}" .
									AMP . "all_day={$events[$event_id]->occurrences[$ymd][$time]['all_day']}" .
									AMP . "ymd={$ymd}";
				}
				else
				{
					$edit_link .=	AMP . "entry_id={$events[$event_id]->default_data['entry_id']}" .
									AMP . "{$this->sc->db->channel_id}={$channel_id}" .
									AMP . "event_id={$events[$event_id]->default_data['event_id']}" .
									AMP . "calendar_id={$calendar_id}" .
									AMP . "site_id={$site_id}" .
									AMP . "start_time={$start_time}" .
									AMP . "end_time={$end_time}" .
									AMP . "all_day={$all_day}" .
									AMP . "ymd={$ymd}" .
									AMP . "start_date={$ymd}" .
									AMP . "end_date={$data['end_date']['ymd']}" .
									AMP . "new_occurrence=y";
				}

				$event_view['edit_link'] = $edit_link;

				//--------------------------------------------
				//	time
				//--------------------------------------------

				$event_view['time'] = $this->CAL->CDT->format_date_string(
					$this->data->date_formats[$this->data->preference('date_format')]['cdt_format']
				);

				$event_view['count'] = ++$count;

				//add to output array
				$event_views[] = $event_view;
			}
		}

		$total = count($event_views);

		//--------------------------------------------
		//	Pagination
		//--------------------------------------------

		ee()->load->library('pagination');

		$config_base_url						= $this->base . AMP . 'method=edit_occurrences' .
																AMP . 'limit=' . $this->cached_vars['limit'] .
																AMP . 'event_id=' . $event_id;

		//add filtering if present to base url
		if ($status)
		{
			$config_base_url .= AMP . 'status=' . $status;
		}

		if ($sort)
		{
			$config_base_url .= AMP . 'sort=' . $sort;
		}

		if ($limit)
		{
			$config_base_url .= AMP . 'limit=' . $limit;
		}

		if ($occurrences_limit)
		{
			$config_base_url .= AMP . 'occurrences_limit=' . $occurrences_limit;
		}

		if ($orderby)
		{
			$config_base_url .= AMP . 'orderby=' . $orderby;
		}

		if ($input_date)
		{
			$config_base_url .= AMP . 'date=' . $input_date;
		}

		if ($direction)
		{
			$config_base_url .= AMP . 'date_direction=' . $direction;
		}

		$config['base_url']						= $config_base_url;
		$config['total_rows'] 					= $total;
		$config['per_page'] 					= $limit;
		$config['page_query_string'] 			= TRUE;
		$config['query_string_segment'] 		= 'offset';

		ee()->pagination->initialize($config);

		$this->cached_vars['paginate']			= ee()->pagination->create_links();

		//--------------------------------------------
		//	clip if larger than limit
		//--------------------------------------------
		// 	due to the way we are filtering, this is how
		//	we have to limit our shown events instead of
		//	limiting the queries.
		//	The data is just too complex.
		//--------------------------------------------

		if ($total > $limit)
		{
			$event_views = array_slice($event_views, $offset, $limit);
		}

		//--------------------------------------------
		//	now we can finally add to vars
		//--------------------------------------------

		$this->cached_vars['event_views'] = $event_views;

		//--------------------------------------------
		//	output
		//--------------------------------------------

		//need the jqui date picker for 2.x since we arent using our own jquidatepicker there
		ee()->cp->add_js_script(array('ui' => 'datepicker'));

		$this->cached_vars['form_url']		= $this->base .
												AMP . 'method=edit_occurrences' .
												AMP . 'event_id=' . $event_id;

		//--------------------------------------------
		//  Which View
		//--------------------------------------------

		$this->cached_vars['current_page'] 	= $this->view('occurrences_edit.html', NULL, TRUE);

		//--------------------------------------------
		//  Load Homepage
		//--------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	/* END edit_occurrences() */


	// --------------------------------------------------------------------

	/**
	 * permissions
	 *
	 * @access	public
	 * @param	string
	 * @return	null
	 */

	public function permissions ($message='')
	{
		if ($message == '' AND isset($_GET['msg']))
		{
			$message = lang($_GET['msg']);
		}

		$this->cached_vars['message'] 				= $message;
		$this->cached_vars['module_menu_highlight'] = 'permissions';

		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->add_crumb(lang('permissions'));

		ee()->load->library('calendar_permissions');

		// -------------------------------------
		//	get member groups
		// -------------------------------------

		$this->cached_vars['member_groups'] = $member_groups = $this->data->get_member_groups();

		// -------------------------------------
		//	allowed, permissions
		// -------------------------------------

		$this->cached_vars['groups_allowed_all'] 	= ee()->calendar_permissions->get_groups_allowed_all();
		$this->cached_vars['groups_denied_all']  	= ee()->calendar_permissions->get_groups_denied_all();
		$this->cached_vars['permissions_enabled'] 	= ee()->calendar_permissions->enabled();
		$this->cached_vars['filter_on'] 			= ee()->calendar_permissions->filter_on();

		$this->cached_vars['show_search_filter'] 	= ($this->ee_version >= '2.4.0');

		// -------------------------------------
		//	calendar list
		// -------------------------------------

		$calendar_list = $this->data->get_calendar_list();

		// -------------------------------------
		//	sort calendar permissions
		// -------------------------------------

		$calendar_permissions 		= ee()->calendar_permissions->get_group_permissions();

		$calendar_permission_data 	= array();

		foreach ($calendar_list as $calendar_id => $calendar_data)
		{
			$calendar_permission_data[$calendar_id] = array(
				'title' 	 	=> $calendar_data['title'],
				'permissions'	=> $calendar_permissions[$calendar_id]
			);
		}

		$this->cached_vars['calendar_permission_data'] = $calendar_permission_data;

		// -------------------------------------
		//	lang stuff
		// -------------------------------------

		$lang_items = array(
			'calendar_permissions_desc',
			'allowed_groups',
			'allow_full_access',
			'permissions_enabled',
			'save_permissions',
			'calendar_name',
			'allow_all',
			'deny_all_access',
			'deny_takes_precedence',
			'disallowed_behavior_for_edit_page',
			'none',
			'search_filter',
			'disable_link'
		);

		foreach ($lang_items as $item)
		{
			$this->cached_vars['lang_' . $item] = lang($item);
		}

		$this->cached_vars['form_url'] = $this->base . AMP . 'method=save_permissions';

		//--------------------------------------------
		//  Which View
		//--------------------------------------------

		$this->cached_vars['current_page'] 	= $this->view('permissions.html', NULL, TRUE);

		//--------------------------------------------
		//  Load Homepage
		//--------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	//end permissions


	// --------------------------------------------------------------------

	/**
	 * save_permissions
	 *
	 * @access	public
	 * @return	null
	 */

	public function save_permissions ()
	{
		ee()->load->library('calendar_permissions');

		ee()->calendar_permissions->save_permissions(ee()->security->xss_clean($_POST));

		// -------------------------------------
		//	move out!
		// -------------------------------------

		ee()->functions->redirect(
			$this->base .
				AMP . 'method=permissions' .
				AMP . 'msg=permissions_saved'
		);
	}
	//END save_permissions


	// --------------------------------------------------------------------

	/**
	 * View Preferences
	 *
	 * @access	public
	 * @param	string
	 * @return	null
	 */

	public function view_preferences($message='')
	{
		if ($message == '' AND isset($_GET['msg']))
		{
			$message = lang($_GET['msg']);
		}

		$this->cached_vars['message']				= $message;
		$this->cached_vars['module_menu_highlight']	= 'preferences';

		// -------------------------------------
		//  Title and Crumbs
		// -------------------------------------

		$this->add_crumb(lang('preferences'));
		$this->build_crumbs();

		// -------------------------------------
		//  Data for the view(s)
		// -------------------------------------

		$preferences = $this->data->get_module_preferences();

		foreach ($preferences as $k => $v)
		{
			$this->cached_vars[$k] = $v;
		}

		$offset = ee()->config->item(ee()->config->item('site_short_name') . '_timezone');

		if (isset($preferences['tz_offset']))
		{
			$zones = $this->actions()->legacy_zones();

			foreach ($zones as $key => $value)
			{
				if ($preferences['tz_offset'] == $value)
				{
					$offset = $key;
					break;
				}
			}
		}

		$menu = $this->actions()->legacy_timezone_menu(
			$offset
		);

		preg_match_all(
			'#<option value=\'(.+?)\'(?:.+)?\>\(UTC ?(.*?)\).*?</option>#m',
			$menu,
			$matches,
			PREG_SET_ORDER
		);

		foreach ($matches as $match)
		{
			$replace = '';

			if ($match[1] == 'UTC')
			{
				$replace = str_replace("'UTC'", "'0000'", $match[0]);
			}
			else
			{
				$array = explode(':', $match[2]);

				if (abs($array[0]) < 10)
				{
					$array[0] = str_replace(
						array('+', '-'),
						array('+0', '-0'),
						$array[0]
					);
				}

				$val		= $array[0] . $array[1];

				$replace	= str_replace(
					"'" . $match[1] . "'",
					"'" . $val . "'",
					$match[0]
				);
			}

			$menu = str_replace($match[0], $replace, $menu);
		}

		$selected = (isset($this->cached_data['tz_offset'])) ?
						$this->cached_data['tz_offset'] :
						$this->data->preference('tz_offset');

		if ($selected !== FALSE)
		{
			$menu = str_replace("selected='selected'", '', $menu);

			$menu = str_replace(
				"value='{$selected}'",
				"value='{$selected}' selected='selected'",
				$menu
			);
		}

		$this->cached_vars['menu'] = $menu;

		// -------------------------------------
		//  Get installed weblogs
		// -------------------------------------

		$this->cached_vars['weblogs'] = $this->data->get_channel_basics();

		// -------------------------------------
		//  form/link urls
		// -------------------------------------

		$this->cached_vars['form_url']	= $this->base . AMP .
											'method=update_preferences';

		// -------------------------------------
		//  Whatchulookinat?
		// -------------------------------------

		$this->cached_vars['current_page'] = $this->view('preferences.html', NULL, TRUE);

		// --------------------------------------------
		//  Load View
		// --------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	/* END view_preferences() */


	// --------------------------------------------------------------------

	/**
	 * Update Preferences
	 *
	 * @access	public
	 * @param	string
	 * @return	null
	 */

	public function update_preferences($message='')
	{
		$this->actions->update_preferences();

		return ee()->functions->redirect(
			$this->base . AMP . 'method=view_preferences' .
						AMP . 'msg=preferences_updated'
		);
	}
	/* END update_preferences() */


	// --------------------------------------------------------------------

	/**
	 * Code pack installer page
	 *
	 * @access public
	 * @param	string	$message	lang line for update message
	 * @return	string				html output
	 */

	public function code_pack($message = '')
	{
		//--------------------------------------------
		//	message
		//--------------------------------------------

		if ($message == '' AND ee()->input->get_post('msg') !== FALSE)
		{
			$message = lang(ee()->input->get_post('msg'));
		}

		$this->cached_vars['message'] = $message;

		// -------------------------------------
		//	load vars from code pack lib
		// -------------------------------------

		$lib_name = str_replace('_', '', $this->lower_name) . 'codepack';
		$load_name = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->lower_name))) . 'CodePack';

		ee()->load->library($load_name, $lib_name);
		ee()->$lib_name->autoSetLang = true;

		$cpt = ee()->$lib_name->getTemplateDirectoryArray(
			$this->addon_path . 'code_pack/'
		);

		$screenshot = ee()->$lib_name->getCodePackImage(
			$this->sc->addon_theme_path . 'code_pack/',
			$this->sc->addon_theme_url . 'code_pack/'
		);

		$this->cached_vars['screenshot'] = $screenshot;

		$this->cached_vars['prefix'] = $this->lower_name . '_';

		$this->cached_vars['code_pack_templates'] = $cpt;

		$this->cached_vars['form_url'] = $this->base . '&method=code_pack_install';

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['module_menu_highlight'] = 'module_demo_templates';

		$this->add_crumb(lang('demo_templates'));

		$this->cached_vars['current_page'] = $this->view('code_pack.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	//END code_pack


	// --------------------------------------------------------------------

	/**
	 * Code Pack Install
	 *
	 * @access public
	 * @param	string	$message	lang line for update message
	 * @return	string				html output
	 */

	public function code_pack_install()
	{
		$prefix = trim((string) ee()->input->get_post('prefix'));

		if ($prefix === '')
		{
			ee()->functions->redirect($this->base . '&method=code_pack');
		}

		// -------------------------------------
		//	load lib
		// -------------------------------------

		$lib_name = str_replace('_', '', $this->lower_name) . 'codepack';
		$load_name = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->lower_name))) . 'CodePack';

		ee()->load->library($load_name, $lib_name);
		ee()->$lib_name->autoSetLang = true;

		// -------------------------------------
		//	¡Las Variables en vivo! ¡Que divertido!
		// -------------------------------------

		$variables = array();

		$variables['code_pack_name']	= $this->lower_name . '_code_pack';
		$variables['code_pack_path']	= $this->addon_path . 'code_pack/';
		$variables['prefix']			= $prefix;

		// -------------------------------------
		//	install
		// -------------------------------------

		$details = ee()->$lib_name->getCodePackDetails($this->addon_path . 'code_pack/');

		$this->cached_vars['code_pack_name'] = $details['code_pack_name'];
		$this->cached_vars['code_pack_label'] = $details['code_pack_label'];

		$return = ee()->$lib_name->installCodePack($variables);

		$this->cached_vars = array_merge($this->cached_vars, $return);

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['module_menu_highlight'] = 'module_demo_templates';

		$this->add_crumb(lang('demo_templates'), $this->base . '&method=code_pack');
		$this->add_crumb(lang('install_demo_templates'));

		$this->cached_vars['current_page'] = $this->view('code_pack_install.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	//END code_pack_install


	// --------------------------------------------------------------------

	/**
	 * Module Upgrading
	 *
	 * This function is not required by the 1.x branch of ExpressionEngine by default.  However,
	 * as the install and deinstall ones are, we are just going to keep the habit and include it
	 * anyhow.
	 *		- Originally, the $current variable was going to be passed via parameter, but as there might
	 *		  be a further use for such a variable throughout the module at a later date we made it
	 *		  a class variable.
	 *
	 *
	 * @access	public
	 * @return	bool
	 */

	public function calendar_module_update()
	{
		if ( ! isset($_POST['run_update']) OR $_POST['run_update'] != 'y')
		{
			$this->add_crumb(lang('update_calendar'));
			$this->cached_vars['form_url'] = $this->base.'&method=calendar_module_update';

			$this->cached_vars['current_page'] = $this->view('update_module.html', NULL, TRUE);

			return $this->ee_cp_view('index.html');
		}

		if ( ! class_exists('Calendar_updater_base'))
		{
			require_once $this->addon_path . 'upd.calendar.php';
		}

		$U = new Calendar_upd();

		if ($U->update() !== TRUE)
		{
			ee()->functions->redirect($this->base.'&msg=update_failure');
		}
		else
		{
			ee()->functions->redirect($this->base.'&msg=update_successful');
		}
	}
	/* END calendar_module_update() */

	// --------------------------------------------------------------------

}
// END CLASS Calendar