<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - User Side
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/mod.calendar.php
 */

if ( ! class_exists('Module_builder_calendar'))
{
	require_once 'addon_builder/module_builder.php';
}

class Calendar extends Module_builder_calendar
{

	public $return_data				= '';
	public $P;
	public $CDT;
	public $disabled				= FALSE;
	public $first_day_of_week		= 0; // Sunday = 0, Saturday = 6
	public $time_format				= 'H:i';
	private $parent_method			= '';
	private $uid_counter			= 0;

	//--------------------------------------------
	//	pagination defaults
	//--------------------------------------------

	public $basepath				= '';
	public $cur_page				= 0;
	public $current_page			= 0;
	public $limit					= 500;
	public $total_pages				= 0;
	public $total_results			= 0;
	public $page_count				= '';
	public $page_next				= '';
	public $page_previous			= '';
	public $pager					= '';
	public $paginate				= FALSE;
	public $paginate_match			= array();
	public $paginate_data			= '';
	public $res_page				= '';
	public $paginate_tagpair_data	= '';

	private $counted				= array(
		'year' 		=> array(),
		'month'		=> array(),
		'week'		=> array(),
		'day'		=> array()
	);

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct($updated = FALSE)
	{
		parent::__construct();

		// -------------------------------------
		//  First day of the week
		// -------------------------------------

		if ($this->preference('first_day_of_week') !== FALSE)
		{
			$this->first_day_of_week = $this->preference('first_day_of_week');
		}

		$this->actions();

		if ( ! defined('CALENDAR_CALENDARS_CHANNEL_NAME'))
		{
			define('CALENDAR_CALENDARS_CHANNEL_NAME', $this->actions->calendar_channel_shortname());
			define('CALENDAR_EVENTS_CHANNEL_NAME', $this->actions->event_channel_shortname());
		}
	}
	// END __construct()


	// --------------------------------------------------------------------

	/**
	 * Theme Folder URL
	 *
	 * Mainly used for codepack
	 *
	 * @access	public
	 * @return	string	theme folder url with ending slash
	 */

	public function theme_folder_url()
	{
		return $this->sc->addon_theme_url;
	}
	//END theme_folder_url


	// --------------------------------------------------------------------

	/**
	 * Calendars
	 * Show information about each calendar
	 *
	 * @return	string
	 */

	public function calendars()
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$params = array(
			array(
				'name' => 'category',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE
			),
			array(
				'name' => 'site_id',
				'required' => FALSE,
				'type' => 'integer',
				'min_value' => 1,
				'multi' => TRUE,
				'default' => $this->data->get_site_id()
			),
			array(
				'name' => 'calendar_id',
				'required' => FALSE,
				'type' => 'integer',
				'min_value' => 1,
				'multi' => TRUE,
				'not' => TRUE
			),
			array(
				'name' => 'calendar_name',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE,
				'not' => TRUE
			),
			array(
				'name' => 'status',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE,
				'default' => 'open'
			),
			array(
				'name' => 'date_range_start',
				'required' => FALSE,
				'type' => 'date'
			),
			array(
				'name' => 'date_range_end',
				'required' => FALSE,
				'type' => 'date'
			)
		);

		//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		// -------------------------------------
		//  Convert calendar_name to calendar_id
		// -------------------------------------

		if ($this->P->value('calendar_id') == '' AND
			$this->P->value('calendar_name') != '')
		{
			$ids = $this->data->get_calendar_id_from_name(
				$this->P->value('calendar_name'),
				NULL,
				$this->P->params['calendar_name']['details']['not']
			);

			if ( empty( $ids ) )
			{
				//ee()->TMPL->log_item('Calendar: No results for
				//calendar name provided, bailing');
				return $this->no_results();
			}

			$this->P->set('calendar_id', implode('|', $ids));
		}

		// -------------------------------------
		//  Determine which calendars this user has permission to view
		//  and modify the parameters accordingly.
		// -------------------------------------

		// TODO

		// -------------------------------------
		//  Fetch the basics
		// -------------------------------------

		$data = $this->data->fetch_calendars_basics(
			$this->P->value('site_id'),
			$this->P->value('calendar_id'),
			$this->P->params['calendar_id']['details']['not']
		);

		// -------------------------------------
		//  If no data, then give 'em no_results
		// -------------------------------------

		if (($total_results = count($data)) == 0)
		{
			//ee()->TMPL->log_item('Calendar: No results, bailing');
			return $this->no_results();
		}

		// -------------------------------------
		//  Ensure date_range_start <= date_range_end
		// -------------------------------------

		if ($this->P->value('date_range_start') !== FALSE AND
			$this->P->value('date_range_end') !== FALSE)
		{
			if ($this->P->value('date_range_start', 'ymd') > $this->P->value('date_range_end', 'ymd'))
			{
				$temp = $this->P->params['date_range_start']['value'];
				$this->P->set('date_range_start', $this->P->params['date_range_end']['value']);
				$this->P->set('date_range_end', $temp);
				unset($temp);
			}
		}

		// -------------------------------------
		//  This will come in handy later
		// -------------------------------------

		$calendar_array = array();
		foreach ($data as $k => $arr)
		{
			$calendar_array[] = $arr['calendar_id'];
		}

		// -------------------------------------
		//  Date range params? Then we need to do a lot more work.
		// -------------------------------------

		if ($this->P->value('date_range_start') !== FALSE OR
			$this->P->value('date_range_end') !== FALSE)
		{
			//ee()->TMPL->log_item('Calendar: Calculating date ranges');
			$min = ($this->P->value('date_range_start') !== FALSE) ?
						$this->P->value('date_range_start', 'ymd') :
						0;

			$max = ($this->P->value('date_range_end') !== FALSE) ?
				$this->P->value('date_range_end', 'ymd') :
				0;

			$calendar_array = $this->data->fetch_calendars_with_events_in_date_range(
				$min,
				$max,
				$calendar_array,
				$this->P->value('status')
			);
		}

		// -------------------------------------
		//  No calendars? No results.
		// -------------------------------------

		if (empty($calendar_array))
		{
//ee()->TMPL->log_item('Calendar: No calendars, bailing');
			return $this->no_results();
		}

		//	----------------------------------------
		//	Invoke Channel class
		//	----------------------------------------

		if ( ! class_exists('Channel') )
		{
			require PATH_MOD.'/channel/mod.channel.php';
		}

		$channel = new Channel();

		//need to remove limit here so huge amounts of events work
		$channel->limit = 1000000;

		// --------------------------------------------
		//  Invoke Pagination for EE 2.4 and Above
		// --------------------------------------------

		$channel = $this->add_pag_to_channel($channel);

		// -------------------------------------
		//  Prepare parameters
		// -------------------------------------

		ee()->TMPL->tagparams['entry_id'] = implode('|', $calendar_array);
		ee()->TMPL->tagparams['channel'] = CALENDAR_CALENDARS_CHANNEL_NAME;

		// -------------------------------------
		//  Pre-process related data
		// -------------------------------------

		if (version_compare($this->ee_version, '2.6.0', '<'))
		{

			ee()->TMPL->tagdata = ee()->TMPL->assign_relationship_data(
				ee()->TMPL->tagdata
			);
			ee()->TMPL->var_single = array_merge(
				ee()->TMPL->var_single,
				ee()->TMPL->related_markers
			);
		}

		// -------------------------------------
		//  Execute needed methods
		// -------------------------------------

		$channel->fetch_custom_channel_fields();

		$channel->fetch_custom_member_fields();

		// --------------------------------------------
		//  Pagination Tags Parsed Out
		// --------------------------------------------

		$this->fetch_pagination_data($channel);

		// -------------------------------------
		//  Querification
		// -------------------------------------

		$channel->build_sql_query();

		if ($channel->sql == '')
		{
			return $this->no_results();
		}

		$channel->query = ee()->db->query($channel->sql);

		if ($channel->query->num_rows() == 0)
		{
//ee()->TMPL->log_item('Calendar: Channel module says no results, bailing');
			return $this->no_results();
		}

		$channel->query->result	= $channel->query->result_array();

		// -------------------------------------------
		// 'calendar_calendars_channel_query' hook.
		//  - Do something with the channel query

		if (ee()->extensions->active_hook('calendar_calendars_channel_query') === TRUE)
		{
			$channel->query = ee()->extensions->call('calendar_calendars_channel_query', $channel->query, $calendar_array);
			if (ee()->extensions->end_script === TRUE) return;
		}
		//
		// -------------------------------------------

		// -------------------------------------
		//  Inject Calendar-specific variables
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Adding Calendar variables');

		$aliases = array(
			'title'			=> 'calendar_title',
			'url_title'		=> 'calendar_url_title',
			'entry_id'		=> 'calendar_id',
			'author_id'		=> 'calendar_author_id',
			'author'		=> 'calendar_author',
			'status'		=> 'calendar_status'
		);

		//custom variables with the letters 'url' are borked in
		//EE 2.6. Bug reported, but this should fix.
		//https://support.ellislab.com/bugs/detail/19337
		if (version_compare($this->ee_version, '2.6.0', '>='))
		{
			$aliases['url_title'] = 'calendar_borked_title';

			ee()->TMPL->var_single['calendar_borked_title'] = 'calendar_borked_title';

			unset(ee()->TMPL->var_single['calendar_url_title']);

			ee()->TMPL->tagdata = str_replace(
				array(
					LD . 'calendar_url_title' . RD,
					'"calendar_url_title"',
					"'calendar_url_title'"
				),
				array(
					LD . 'calendar_borked_title' . RD,
					'"calendar_borked_title"',
					"'calendar_borked_title'"

				),
				ee()->TMPL->tagdata
			);
		}

		foreach ($channel->query->result as $k => $row)
		{
			$channel->query->result[$k]['author'] = ($row['screen_name'] != '') ?
										$row['screen_name'] : $row['username'];

			foreach ($aliases as $old => $new)
			{
				$channel->query->result[$k][$new] = $channel->query->result[$k][$old];
			}
		}

		//	----------------------------------------
		//	Redeclare
		//	----------------------------------------
		//	We will reassign the $channel->query->result with our
		//	reordered array of values.
		//	----------------------------------------

		$channel->query->result_array = $channel->query->result;

		// --------------------------------------------
		//  Typography
		// --------------------------------------------

		ee()->load->library('typography');
		ee()->typography->initialize();
		ee()->typography->convert_curly = FALSE;

		$channel->fetch_categories();

		// -------------------------------------
		//  Parse
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Parsing, via channel module');



		$channel->parse_channel_entries();

		// -------------------------------------
		//  Paginate
		// -------------------------------------

		$channel = $this->add_pagination_data($channel);

		// -------------------------------------
		//  Related entries
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Parsing related entries, via Weblog module');

		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			if (count(ee()->TMPL->related_data) > 0 AND
				count($channel->related_entries) > 0)
			{
				$channel->parse_related_entries();
			}

			if (count(ee()->TMPL->reverse_related_data) > 0 AND
				count($channel->reverse_related_entries) > 0)
			{
				$channel->parse_reverse_related_entries();
			}
		}

		// -------------------------------------
		//  Send 'em home
		// -------------------------------------

		$tagdata = $channel->return_data;

		//ee()->TMPL->log_item('Calendar: Done!');

		//on the off chance someone just wrote the word
		//'calendar_url_title', we should fix that before
		//output. (See above calendar_url_title fix)
		if (version_compare($this->ee_version, '2.6.0', '>='))
		{
			$tagdata = str_replace(
				array(
					LD . 'calendar_borked_title' . RD,
					'"calendar_borked_title"',
					"'calendar_borked_title'"
				),
				array(
					LD . 'calendar_url_title' . RD,
					'"calendar_url_title"',
					"'calendar_url_title'"
				),
				$tagdata
			);
		}

		return $tagdata;

	}
	// END calendars()


	// --------------------------------------------------------------------

	/**
	 * Events
	 *
	 * Show information about events
	 * @return	string
	 */

	public function events()
	{
		// -------------------------------------
		//  Set dynamic="off", lest Weblog get uppity and try
		//  to think that it's in charge here.
		// -------------------------------------

		//default off.
		if ( $this->check_yes( ee()->TMPL->fetch_param('dynamic') ) )
		{
			ee()->TMPL->tagparams['dynamic'] 	='yes';
		}
		else
		{
			ee()->TMPL->tagparams['dynamic'] 	= 'no';
		}

		// -------------------------------------
		//	category url titles?
		// -------------------------------------

		if (isset(ee()->TMPL->tagparams['category']))
		{
			$this->convert_category_titles();
		}

		//--------------------------------------------
		//	detect special cases
		//--------------------------------------------

		if ( ! $this->parent_method)
		{
			$this->parent_method = __FUNCTION__;
		}

		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$params = array(
			array(	'name' => 'category',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'site_id',
					'required' => FALSE,
					'type' => 'integer',
					'min_value' => 1,
					'multi' => TRUE,
					'default' => $this->data->get_site_id()
					),
			array(	'name' => 'calendar_id',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'calendar_name',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'event_id',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'event_name',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'status',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE,
					'default' => 'open'
					),
			array(	'name' => 'date_range_start',
					'required' => FALSE,
					'type' => 'date'
					),
			array(	'name' => 'date_range_end',
					'required' => FALSE,
					'type' => 'date'
					),
			array(	'name' => 'show_days',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'show_weeks',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'show_months',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'show_years',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'time_range_start',
					'required' => FALSE,
					'type' => 'time',
					'default' => '0000'
					),
			array(	'name' => 'time_range_end',
					'required' => FALSE,
					'type' => 'time',
					'default' => '2400'
					),
			array(	'name' => 'prior_occurrences_limit',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'prior_exceptions_limit',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'upcoming_occurrences_limit',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'upcoming_exceptions_limit',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'event_limit',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'event_offset',
					'required' => FALSE,
					'type' => 'integer',
					'default' => 0
					),
			array(	'name' => 'orderby',
					'required' => FALSE,
					'type' => 'string',
					'default' => 'event_start_date'
					),
			array(	'name' => 'sort',
					'required' => FALSE,
					'type' => 'string',
					'default' => 'ASC'
					)
			);

		//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		// -------------------------------------
		//  Do some voodoo on P
		// -------------------------------------

		$this->process_events_params();

		// -------------------------------------
		//  For the purposes of this method, if an event_id or event_name
		//  has been specified, we want to ignore date range parameters.
		// -------------------------------------
		/*
		if ($this->P->value('event_id') !== FALSE OR $this->P->value('event_name') !== FALSE)
		{
			$this->P->set('date_range_start', FALSE);
			$this->P->set('date_range_end', FALSE);
		}
		*/

		// -------------------------------------
		//  Let's go fetch some events
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Fetching events');

		$ids = $this->data->fetch_event_ids($this->P);

		/*$category = FALSE;

		if (FALSE AND isset(ee()->TMPL) AND
			 is_object(ee()->TMPL) AND
			 ee()->TMPL->fetch_param('category') !== FALSE AND
			 ee()->TMPL->fetch_param('category') != ''
		)
		{
			$category = ee()->TMPL->fetch_param('category');

			unset(ee()->TMPL->tagparams['category']);
		}

		$ids = $this->data->fetch_event_ids($this->P, $category);*/

		// -------------------------------------
		//  No events?
		// -------------------------------------

		if (empty($ids))
		{
			//ee()->TMPL->log_item('Calendar: No events, bailing');
			return $this->no_results();
		}

		// -------------------------------------
		//  We also need the "parent" entry id, if it hasn't been provided
		// -------------------------------------

		foreach ($ids as $k => $v)
		{
			if (! isset($ids[$v]))
			{
				$ids[$v] = $v;
			}
		}

		// -------------------------------------------
		// 'calendar_events_event_ids' hook.
		//  - Do something with the event IDs

		if (ee()->extensions->active_hook('calendar_events_event_ids') === TRUE)
		{
			$ids = ee()->extensions->call('calendar_events_event_ids', $ids);
			if (ee()->extensions->end_script === TRUE) return;
		}
		//
		// -------------------------------------------


		//--------------------------------------------
		//	remove pagination before we start
		//--------------------------------------------

		//has tags?
		if (preg_match(
				"/" . LD . "calendar_paginate" . RD . "(.+?)" .
					  LD . preg_quote(T_SLASH, '/') . "calendar_paginate" . RD . "/s",
				ee()->TMPL->tagdata	,
				$match
			))
		{
			$this->paginate_tagpair_data	= $match[0];
			ee()->TMPL->tagdata				= str_replace( $match[0], '', ee()->TMPL->tagdata );
		}
		//prefix comes first
		else if (preg_match(
				"/" . LD . "paginate" . RD . "(.+?)" . LD . preg_quote(T_SLASH, '/') . "paginate" . RD . "/s",
				ee()->TMPL->tagdata	,
				$match
			))
		{
			$this->paginate_tagpair_data	= $match[0];
			ee()->TMPL->tagdata				= str_replace( $match[0], '', ee()->TMPL->tagdata );
		}


		// -------------------------------------
		//  Prepare tagdata for Calendar-specific variable pairs, which
		//  we will process later.
		// -------------------------------------

		ee()->TMPL->var_single['entry_id'] = 'entry_id';

		$var_pairs = array(
			'occurrences',
			'exceptions',
			'rules'
		);

		foreach (ee()->TMPL->var_pair as $name => $params)
		{
			if (in_array($name, $var_pairs))
			{
				ee()->TMPL->tagdata = str_replace(
					LD.$name.RD,
					LD.$name.' id="'.LD.'entry_id'.RD.'"'.RD,
					ee()->TMPL->tagdata
				);

				ee()->TMPL->var_pair[$name]['id'] = '';
				continue;
			}

			foreach ($var_pairs as $pair)
			{
				if (strpos($name.' ', $pair) !== 0)
				{
					continue;
				}

				$new_name = $name.' id=""';

				ee()->TMPL->tagdata = str_replace(
					LD.$name.RD,
					LD.$name.' id="'.LD.'entry_id'.RD.'"'.RD,
					ee()->TMPL->tagdata
				);

				ee()->TMPL->var_pair[] 					= ee()->TMPL->var_pair[$name];
				ee()->TMPL->var_pair[$new_name]['id'] 	= '';
				// Leave the old name behind so we can pick it up later and use it
				ee()->TMPL->var_pair[$pair] 			= $name;

				unset(ee()->TMPL->var_pair[$name]);
			}
		}

		// -------------------------------------
		//  Prepare tagdata for Calendar-specific date variables, which
		//  we will process later.
		// -------------------------------------

		$var_dates = array(
			'event_start_date'		=> FALSE,
			'event_start_time'		=> FALSE,
			'event_end_date'		=> FALSE,
			'event_end_time'		=> FALSE,
			'event_first_date'		=> FALSE,
			'event_last_date'		=> FALSE,
			//
			//'occurrence_start_date'	=> FALSE,
			//'occurrence_start_time'	=> FALSE,
			//'occurrence_end_date'	=> FALSE,
			//'occurrence_end_time'	=> FALSE,
			//'occurrence_first_date'	=> FALSE,
			//'occurrence_last_date'	=> FALSE,
		);

		foreach (ee()->TMPL->var_single as $k => $v)
		{
			if (($pos = strpos($k, ' format')) !== FALSE)
			{
				$name = substr($k, 0, $pos);

				if (array_key_exists($name, $var_dates))
				{
					// -------------------------------------
					//	hash fix for EE 2.8.2
					// -------------------------------------
					//	Due to the new conditionals parser
					//	everything is just whack, so we have
					//	to hash and replace formats because
					//	EE is just barfing on it now :/.
					// -------------------------------------

					//EE 2.9+ converting quotes and escaping on its conditional
					//tokenizer so we now have to match escaped quotes
					//This should be backward compatible.
					preg_match("/format=(\\\'|\'|\\\"|\")(.*)?\\1/i", $k, $matches);
//var_dump($k);
					if ( ! empty($matches))
					{
//var_dump($matches);
						$old_k = $k;
						$k = str_replace($matches[0], md5($matches[0]), $k);
						ee()->TMPL->tagdata = str_replace($old_k, $k, ee()->TMPL->tagdata);

						//EE 2.9's new conditional parser also screws with how
						//template variables were pulling out the formats for
						//us due to the quote conversion and escaping
						//so we are fixing it here with this since we are
						//capturing it ourselves and converting to hashes
						//anyway.
						if ($v == false)
						{
							$v = $matches[2];
						}
					}
//var_dump(ee()->TMPL->tagdata);
					$var_dates[$name][$k] = $v;
					ee()->TMPL->var_single[$k] = $k;
				}
			}
		}

		//	----------------------------------------
		//	Invoke Channel class
		//	----------------------------------------

		if ( ! class_exists('Channel') )
		{
			require PATH_MOD.'/channel/mod.channel.php';
		}

		$channel = new Channel;


		//need to remove limit here so huge amounts of events work
		$channel->limit = 1000000;

		// --------------------------------------------
		//  Invoke Pagination for EE 2.4 and Above
		// --------------------------------------------

		$channel = $this->add_pag_to_channel($channel);

		// -------------------------------------
		//  Prepare parameters
		// -------------------------------------

		ee()->TMPL->tagparams['entry_id'] 			= implode('|', array_keys($ids));

		//if we have event names, lets set them for the URL title
		if ( ! in_array(ee()->TMPL->fetch_param('event_name'), array(FALSE, ''), TRUE))
		{
			ee()->TMPL->tagparams['url_title'] = ee()->TMPL->fetch_param('event_name');
		}

		ee()->TMPL->tagparams[$this->sc->channel] 	= CALENDAR_EVENTS_CHANNEL_NAME;

		// -------------------------------------
		//  Pre-process related data
		// -------------------------------------

		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			ee()->TMPL->tagdata = ee()->TMPL->assign_relationship_data(
				ee()->TMPL->tagdata
			);
		}

		ee()->TMPL->var_single 		= array_merge( ee()->TMPL->var_single, ee()->TMPL->related_markers );

		// -------------------------------------
		//  Execute needed methods
		// -------------------------------------

		$channel->fetch_custom_channel_fields();

		$channel->fetch_custom_member_fields();

		// --------------------------------------------
		//  Pagination Tags Parsed Out
		// --------------------------------------------

		$channel = $this->fetch_pagination_data($channel);

		// -------------------------------------
		//  Querification
		// -------------------------------------

		$channel->build_sql_query();

		if ($channel->sql == '')
		{
			return $this->no_results();
		}

		$channel->query = ee()->db->query($channel->sql);

		if ($channel->query->num_rows() == 0)
		{
//ee()->TMPL->log_item('Calendar: Channel module says no results, bailing');
			return $this->no_results();
		}

		$channel->query->result	= $channel->query->result_array();

		// -------------------------------------------
		// 'calendar_events_channel_query' hook.
		//  - Do something with the channel query

		if (ee()->extensions->active_hook('calendar_events_channel_query') === TRUE)
		{
			$channel->query = ee()->extensions->call('calendar_events_channel_query', $channel->query, $ids);
			if (ee()->extensions->end_script === TRUE) return;
		}
		//
		// -------------------------------------------

		// -------------------------------------
		//  Trim IDs and build events
		// -------------------------------------

		$new_ids = array();

		foreach ($channel->query->result as $k => $row)
		{
			$new_ids[$row['entry_id']] = $ids[$row['entry_id']];
		}

		$event_data = $this->data->fetch_all_event_data($new_ids);

		// -------------------------------------
		//  Turn these IDs into events
		// -------------------------------------

		$events = array();

		//ee()->TMPL->log_item('Calendar: Fetching Calendar_event class');

		if ( ! class_exists('Calendar_event'))
		{
			require_once CALENDAR_PATH.'calendar.event.php';
		}

		$calendars = array();

		//ee()->TMPL->log_item('Calendar: Creating events');

		foreach ($event_data as $k => $edata)
		{
			$start_ymd	= ($this->P->value('date_range_start') !== FALSE) ?
							$this->P->value('date_range_start', 'ymd') : '';
			$end_ymd	= ($this->P->value('date_range_end') !== FALSE) ?
							$this->P->value('date_range_end', 'ymd') :
							$this->P->value('date_range_start', 'ymd');

			$temp		= new Calendar_event($edata, $start_ymd, $end_ymd);

			if (! empty($temp->dates))
			{
				$temp->prepare_for_output();

				// -------------------------------------
				//  Eliminate times we don't care about
				// -------------------------------------

				if ($this->P->value('date_range_start', 'ymd') != '')
				{
					foreach ($temp->dates as $ymd => $times)
					{
						foreach ($times as $range => $data)
						{
							if ($data['end_date']['ymd'].$data['end_date']['time'] <
								$this->P->value('date_range_start', 'ymd') .
								$this->P->value('date_range_start', 'time'))
							{
								unset($temp->dates[$ymd][$range]);
							}

							elseif ($data['date']['ymd'].$data['date']['time'] >
									$this->P->value('date_range_end', 'ymd') .
									$this->P->value('date_range_end', 'time'))
							{
								unset($temp->dates[$ymd][$range]);
							}
						}

						if (empty($temp->dates[$ymd]))
						{
							unset($temp->dates[$ymd]);
						}
					}
				}

				// -------------------------------------
				//  Recheck to ensure our dates array isn't empty now
				// -------------------------------------

				if ( ! empty($temp->dates))
				{
					$events[$edata['entry_id']] = $temp;
					$calendars[$events[$edata['entry_id']]->default_data['calendar_id']] = array();
				}
			}
		}

		// -------------------------------------
		//  No point in stressing ourselves out if $calendars is empty
		// -------------------------------------

		if (empty($calendars))
		{
			//ee()->TMPL->log_item('Calendar: No calendars, bailing');
			return $this->no_results();
		}

		// -------------------------------------
		//  Fetch information about the calendars
		// -------------------------------------

		$calendars = $this->data->fetch_calendar_data_by_id(array_keys($calendars));

		// -------------------------------------
		//  Prep variable aliases
		// -------------------------------------

		$variables = array(
			'title'			=> 'event_title',
			'url_title'		=> 'event_url_title',
			'entry_id'		=> 'event_id',
			'author_id'		=> 'event_author_id',
			'author'		=> 'event_author',
			'status'		=> 'event_status'
		);

		//custom variables with the letters 'url' are borked in
		//EE 2.6. Bug reported, but this should fix.
		//https://support.ellislab.com/bugs/detail/19337
		if (version_compare($this->ee_version, '2.6.0', '>='))
		{
			$variables['url_title'] = 'event_borked_title';

			ee()->TMPL->var_single['event_borked_title'] = 'event_borked_title';

			unset(ee()->TMPL->var_single['event_url_title']);

			ee()->TMPL->tagdata = str_replace(
				array(
					LD . 'event_url_title' . RD,
					'"event_url_title"',
					"'event_url_title'"
				),
				array(
					LD . 'event_borked_title' . RD,
					'"event_borked_title"',
					"'event_borked_title'"

				),
				ee()->TMPL->tagdata
			);

			ee()->TMPL->var_single['event_calendar_borked_title'] = 'event_calendar_borked_title';

			unset(ee()->TMPL->var_single['event_calendar_url_title']);

			ee()->TMPL->tagdata = str_replace(
				array(
					LD . 'event_calendar_url_title' . RD,
					'"event_calendar_url_title"',
					"'event_calendar_url_title'"
				),
				array(
					LD . 'event_calendar_borked_title' . RD,
					'"event_calendar_borked_title"',
					"'event_calendar_borked_title'"

				),
				ee()->TMPL->tagdata
			);
		}

		// -------------------------------------
		//  Prepare to reorder based on Calendar parameters
		// -------------------------------------

		$calendar_orderby_params = array(
			'event_title',
			'event_start_date',
			'event_start_hour',
			'event_start_time',
			'occurrence_start_date'
		);

		$orders 				= explode('|', $this->P->value('orderby'));
		$sorts 					= explode('|', $this->P->value('sort'));
		$calendar_orders 		= array();
		$calendar_order_data 	= array();

		foreach ($orders as $k => $order)
		{
			if (in_array($order, $calendar_orderby_params))
			{
				$sort = (isset($sorts[$k])) ? $sorts[$k] : 'desc';
				$calendar_orders[$order] = $sort;
				$calendar_order_data[$order] = array();
			}
		}

		//--------------------------------------------
		//	remove non-existant entry ids first
		//--------------------------------------------

		foreach ($channel->query->result as $k => $row)
		{
			if ( ! isset($events[$row['entry_id']]))
			{
				unset($channel->query->result[$k]);
			}
		}

		//--------------------------------------------
		//	calculate offset and event timeframe
		//	total before we parse tags
		//--------------------------------------------

		$offset = ($this->P->value('event_offset') > 0) ? $this->P->value('event_offset') : 0;

		$this->event_timeframe_total = count($channel->query->result) - $offset;

		// -------------------------------------
		//  Add variables to the query result
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Adding variables to channel query result');

		foreach ($channel->query->result as $k => $row)
		{

			$channel->query->result[$k]['author'] = ($row['screen_name'] != '') ?
														$row['screen_name'] :
														$row['username'];

			$channel->query->result[$k]['event_timeframe_total'] = $this->event_timeframe_total;

			$entry_id = $row['entry_id'];

			// -------------------------------------
			//  Alias
			// -------------------------------------

			foreach ($variables as $old => $new)
			{
				$channel->query->result[$k][$new] = $channel->query->result[$k][$old];
			}

			// -------------------------------------
			//  Event variables
			// -------------------------------------

			foreach ($events[$entry_id]->default_data as $key => $val)
			{
				if (! is_array($val))
				{
					if ($val === 'y' OR $val === 'n')
					{
						$channel->query->result[$k]['event_'.$key] = ($val == 'y') ? TRUE : FALSE;
					}
					else
					{
						$channel->query->result[$k]['event_'.$key] = $val;
					}
				}
				else
				{
					foreach ($val as $vkey => $vval)
					{
						if ($vval === 'y' OR $vval === 'n')
						{
							$channel->query->result[$k][
								'event_'.$key.'_'.$vkey
							] = ($vval == 'y') ? TRUE : FALSE;
						}
						else
						{
							$channel->query->result[$k]['event_'.$key.'_'.$vkey] = $vval;
						}
					}
				}
			}

			// -------------------------------------
			//  Prepare to orderby event_start_date
			// -------------------------------------

			if (isset($calendar_orders['event_start_date']))
			{
				$calendar_order_data['event_start_date'][$k] =
					$events[$entry_id]->default_data['start_date'] .
					$events[$entry_id]->default_data['start_time'];
			}

			// -------------------------------------
			//  Prepare to orderby occurrence_start_date
			// -------------------------------------

			if (isset($calendar_orders['occurrence_start_date']))
			{
				$date = reset(reset($events[$entry_id]->dates));
				$time = (isset($date['start_time'])) ? $date['start_time'] : $date['date']['time'];
				$date = $date['date']['ymd'];
				$calendar_order_data['occurrence_start_date'][$k] = $date.$time;
			}

			//--------------------------------------------
			//	sort by occurrence start time
			//--------------------------------------------

			if (isset($calendar_orders['event_start_time']))
			{
				if ($events[$entry_id]->default_data['start_time'] == 0)
				{
					$calendar_order_data['event_start_time'][$k] = '0000';
				}
				else
				{
					$calendar_order_data['event_start_time'][$k] = $events[$entry_id]->default_data['start_time'];
				}
			}

			//--------------------------------------------
			//	sort by occurrence start hour
			//--------------------------------------------

			if (isset($calendar_orders['event_start_hour']))
			{
				if ($events[$entry_id]->default_data['start_time'] == 0)
				{
					$calendar_order_data['event_start_hour'][$k] = '00';
				}
				else
				{
					$calendar_order_data['event_start_hour'][$k] 	= substr($events[$entry_id]->default_data['start_time'], 0, 2);
				}
			}

			//--------------------------------------------
			//	sort by event title
			//--------------------------------------------

			if (isset($calendar_orders['event_title']))
			{
				$calendar_order_data['event_title'][$k] = $channel->query->result[$k]['title'];
			}

			// -------------------------------------
			//  Occurrence variables
			// -------------------------------------

			$channel->query->result[$k]['event_occurrence_total'] 	= count($events[$entry_id]->occurrences);
			$channel->query->result[$k]['event_has_occurrences'] 	= (
				$channel->query->result[$k]['event_occurrence_total'] > 0
			) ? TRUE : FALSE;

			// -------------------------------------
			//  Exception variables
			// -------------------------------------

			$channel->query->result[$k]['event_exception_total'] 	= count($events[$entry_id]->exceptions);
			$channel->query->result[$k]['event_has_exceptions'] 	= (
				$channel->query->result[$k]['event_exception_total'] > 0
			) ? TRUE : FALSE;

			// -------------------------------------
			//  Rule variables
			// -------------------------------------

			$channel->query->result[$k]['event_rule_total'] 		= count($events[$entry_id]->rules);
			$channel->query->result[$k]['event_has_rules'] 			= (
				$channel->query->result[$k]['event_rule_total'] > 0
			) ? TRUE : FALSE;

			//-------------------------------------
			// Does the event end?
			// we have to check all rules to make sure
			// in case this is a complex rule
			//-------------------------------------

			$never_ending = $channel->query->result[$k]['event_never_ends'] = FALSE;

			if ($channel->query->result[$k]['event_recurs'] == 'y')
			{
				foreach ($events[$entry_id]->rules as $event_rule)
				{
					if ($event_rule['rule_type'] == '+' AND
						($event_rule['repeat_years']  != 0 OR
						 $event_rule['repeat_months'] != 0 OR
						 $event_rule['repeat_days']   != 0 OR
						 $event_rule['repeat_weeks']  != 0)  AND
						$event_rule['last_date'] == 0 	AND
						$event_rule['stop_after'] == 0
					 )
					{
						$never_ending = $channel->query->result[$k]['event_never_ends'] = TRUE;
						break;
					}

				}
			}

			/*$never_ending = $channel->query->result[$k]['event_never_ends'] = (
				$channel->query->result[$k]['event_last_date'] == 0 AND
				$channel->query->result[$k]['event_recurs'] == 'y'
			);*/

			// -------------------------------------
			//  Calendar variables
			// -------------------------------------

			if (isset($calendars[$events[$entry_id]->default_data['calendar_id']]))
			{
				foreach ($calendars[$events[$entry_id]->default_data['calendar_id']] as $key => $val)
				{
					if (substr($key, 0, 9) != 'calendar_')
					{
						$key = 'calendar_'.$key;
					}

					//add in calendar data
					$channel->query->result[$k][$key] = $val;

					if ($key == 'calendar_url_title' AND
						version_compare($this->ee_version, '2.6.0', '>='))
					{
						$channel->query->result[$k]['event_calendar_borked_title'] = $val;
					}
					else
					{
						//really?
						$channel->query->result[$k]['event_'.$key] = $val;
					}
				}
			}

			// -------------------------------------
			//  Date variables
			// -------------------------------------

			foreach ($var_dates as $name => $vals)
			{
				$which = '';

				if ($vals === FALSE)
				{
					$vals = array();
				}

				if ($name == 'event_last_date')
				{
					if ( ! empty($vals))
					{
						$this->CDT->change_ymd($channel->query->result[$k]['event_last_date']);
					}
				}
				elseif ($name == 'event_first_date')
				{
					$channel->query->result[$k]['event_first_date']	= $channel->query->result[$k]['event_start_ymd'];

					if ( ! empty($vals))
					{
						$this->CDT->change_ymd($channel->query->result[$k]['event_first_date']);
					}
				}

				$which	= ($name == 'event_first_date' OR strpos($name, '_start_') !== FALSE) ? 'start' : 'end';
				$year	= $events[$entry_id]->default_data[$which.'_year'];
				$month	= $events[$entry_id]->default_data[$which.'_month'];
				$day	= $events[$entry_id]->default_data[$which.'_day'];

				$time 	= $events[$entry_id]->default_data[$which.'_time'];

				if ($time == 0)
				{
					if ($which == 'start')
					{
						$hour 	= '00';
						$minute = '00';
					}
					else
					{
						$hour	= '23';
						$minute = '59';
					}
				}
				else
				{
					$minute = substr($time, -2, 2);
					$hour 	= substr($time, 0, strlen($time) - 2);
				}

				//year month and day are a tad fubar when
				//we are looking at a repeat occurence with an enddate
				//because the end time is indeed correct, but end date
				//is expected to be the end of all of the occurrences
				//however, if this is never ending, thats different

				$end_time_dta 	= $this->CDT->datetime_array();

				if ( ! $never_ending AND
					 $name = "event_last_date")
				{
					$real_end_date  = ($channel->query->result[$k]['event_last_date'] != 0) ?
										$channel->query->result[$k]['event_last_date'] :
										$events[$entry_id]->default_data['end_date']['ymd'];


					$end_time_year 	= substr($real_end_date, 0, 4);
					$end_time_month = substr($real_end_date, 4, 2);
					$end_time_day 	= substr($real_end_date, 6, 2);

					$this->CDT->change_datetime(
						$end_time_year,
						$end_time_month,
						$end_time_day,
						$hour,
						$minute
					);

					$end_time_dta 	= $this->CDT->datetime_array();
				}

				$this->CDT->change_datetime($year, $month, $day, $hour, $minute);

				foreach ($vals as $key => $format)
				{
					//special treatment on end dates of occurrences if its not infinite
					if ( ! $never_ending AND stristr($key, 'event_last_date'))
					{
						$channel->query->result[$k][$key] = $this->cdt_format_date_string(
							$end_time_dta,
							$format,
							'%'
						);
					}
					//else, parse as normal
					else
					{
						$channel->query->result[$k][$key] = $this->cdt_format_date_string(
							$this->CDT->datetime_array(),
							$format,
							'%'
						);
					}
				}


				/*
				$this->CDT->change_datetime($year, $month, $day, $hour, $minute);

				foreach ($vals as $key => $format)
				{

					$channel->query->result[$k][$key] = $this->cdt_format_date_string(
						$this->CDT->datetime_array(),
						$format,
						'%'
					);
				}
				*/

				// -------------------------------------
				//  Shorthand date/time variables
				// -------------------------------------

				if ($which)
				{
					$channel->query->result[$k]['event_'.$which.'_hour']	= $this->cdt_format_date_string($this->CDT->datetime_array(), 'H');
					$channel->query->result[$k]['event_'.$which.'_minute']	= $this->cdt_format_date_string($this->CDT->datetime_array(), 'i');
				}
			}
		}

		// -------------------------------------
		//  The foreach may have emptied the query results
		// -------------------------------------

		if (empty($channel->query->result))
		{
//ee()->TMPL->log_item('Calendar: Weblog query is empty, bailing');
			return $this->no_results();
		}

		unset($CDT);

		// -------------------------------------
		//  Reorder based on Calendar parameters
		// -------------------------------------

		if ( ! empty($calendar_order_data))
		{
			ee()->TMPL->log_item('Calendar: Reordering');

			$args 	= array();
			$temps 	= array();

			foreach ($calendar_orders as $k => $v)
			{
				//add order array
				$args[] =& $calendar_order_data[$k];

				//constant for order type

				//contants cannot be passed by ref because its not a variable
				$temps[$k] = constant('SORT_'.strtoupper($v));

				$args[] =& $temps[$k];
			}

			//this will order the result
			$args[] =& $channel->query->result;

			call_user_func_array('array_multisort', $args);

			//cleanup
			unset($args);
			unset($temps);
		}

		//--------------------------------------------
		//	pagination for the events tag
		//--------------------------------------------
		//	any tags using this might have different needs
		//	so we only want to paginate for original events
		//	tag usage
		//--------------------------------------------

		$this->paginate = FALSE;

		//$this->event_timeframe_total = count($channel->query->result);

		if ($this->parent_method === 'events' AND
			$this->P->value('event_limit') > 0 AND
			$this->event_timeframe_total > $this->P->value('event_limit'))
		{
			//get pagination info
			$pagination_data = $this->universal_pagination(array(
				'total_results'			=> $this->event_timeframe_total,
				//had to remove this jazz before so it didn't get iterated over
				'tagdata'				=> ee()->TMPL->tagdata . $this->paginate_tagpair_data,
				'limit'					=> $this->P->value('event_limit'),
				'uri_string'			=> ee()->uri->uri_string,
				'paginate_prefix'		=> 'calendar_'
			));

			// -------------------------------------------
			// 'calendar_events_create_pagination' hook.
			//  - Let devs maniuplate the pagination display

			if (ee()->extensions->active_hook('calendar_events_create_pagination') === TRUE)
			{
				$pagination_data = ee()->extensions->call(
					'calendar_events_create_pagination',
					$this,
					$pagination_data
				);
			}
			//
			// -------------------------------------------

			//if we paginated, sort the data
			if ($pagination_data['paginate'] === TRUE)
			{
				$this->paginate			= $pagination_data['paginate'];
				$this->page_next		= $pagination_data['page_next'];
				$this->page_previous	= $pagination_data['page_previous'];
				$this->p_page			= $pagination_data['pagination_page'];
				$this->current_page  	= $pagination_data['current_page'];
				$this->pager 			= $pagination_data['pagination_links'];
				$this->basepath			= $pagination_data['base_url'];
				$this->total_pages		= $pagination_data['total_pages'];
				$this->paginate_data	= $pagination_data['paginate_tagpair_data'];
				$this->page_count		= $pagination_data['page_count'];
				//ee()->TMPL->tagdata		= $pagination_data['tagdata'];
			}
		}

		//--------------------------------------------
		//	event limiter
		//--------------------------------------------

		$page 	= (($this->current_page -1) * $this->P->value('event_limit'));

		if ($page > 0)
		{
			$offset += $page;
		}


		// -------------------------------------
		//  Apply event_limit="" parameter
		// -------------------------------------

		if ($this->P->value('event_limit'))
		{
			$channel->query->result	= array_slice(
				$channel->query->result,
				$offset,
				$this->P->value('event_limit')
			);
		}
		else if ($offset > 0)
		{
			$channel->query->result	= array_slice(
				$channel->query->result,
				$offset
			);
		}

		//--------------------------------------------
		//	offset too much? buh bye
		//--------------------------------------------

		if (empty($channel->query->result))
		{
			return $this->no_results();
		}

		//	----------------------------------------
		//	Redeclare
		//	----------------------------------------
		// 	We will reassign the $channel->query->result with our
		// 	reordered array of values.
		//	----------------------------------------

		$channel->query->result_array = $channel->query->result;

		// --------------------------------------------
		//  Typography
		// --------------------------------------------

		ee()->load->library('typography');
		ee()->typography->initialize();
		ee()->typography->convert_curly = FALSE;

		$channel->fetch_categories();

		// -------------------------------------
		//  Parse
		// -------------------------------------

		$occurrence_hash = '9b8b2cde1a14e29a8791ceccdaab6cf9d92b37a4';

		//we have to prevent occurrenct count from being borked
		ee()->TMPL->tagdata = str_replace('occurrence_count', $occurrence_hash, ee()->TMPL->tagdata);

		//ee()->TMPL->log_item('Calendar: Parsing, via channel module');

		$channel->parse_channel_entries();

		$channel->return_data = str_replace($occurrence_hash, 'occurrence_count', $channel->return_data);

		// -------------------------------------
		//  Parse Calendar variable pairs
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Parsing variable pairs');

		foreach ($var_pairs as $var)
		{
			if (isset(ee()->TMPL->var_pair[$var]))
			{
				// -------------------------------------
				//  Iterate through the events
				// -------------------------------------

				foreach ($events as $k => $data)
				{
					// -------------------------------------
					//  Does this event have this var pair?
					// -------------------------------------

					if (strpos($channel->return_data, LD.$var.' ') !== FALSE)
					{
						$var_tag = (! is_array(ee()->TMPL->var_pair[$var])) ? ee()->TMPL->var_pair[$var] : $var;

						if (preg_match_all("/".LD.$var_tag.' id="'.$k.'"'.RD.'(.*?)'.LD.preg_quote(T_SLASH, '/').$var.RD.'/s', $channel->return_data, $matches))
						{
							// -------------------------------------
							//  Iterate through the variables associated with this pair
							// -------------------------------------

							foreach ($matches[1] as $key => $match)
							{
								$method = "prep_{$var}_output";
								$match = $this->$method($match, $data, FALSE);
								$channel->return_data = str_replace($matches[0][$key], $match, $channel->return_data);
							}
						}
					}
				}
			}
		}


		// -------------------------------------
		//  Paginate
		// -------------------------------------

		//$channel->add_pagination_data();

		// -------------------------------------
		//  Related entries
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Parsing related entries, via channel module');

		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			if (count(ee()->TMPL->related_data) > 0 AND
				count($channel->related_entries) > 0)
			{
				$channel->parse_related_entries();
			}

			if (count(ee()->TMPL->reverse_related_data) > 0 AND
				count($channel->reverse_related_entries) > 0)
			{
				$channel->parse_reverse_related_entries();
			}
		}

		// -------------------------------------
		//  Send 'em home
		// -------------------------------------

		$tagdata = $this->parse_pagination($channel->return_data);

		//ee()->TMPL->log_item('Calendar: Done!');

		// -------------------------------------
		//	lets reverse any unparsed items
		//	in case someone is actually writing
		//	out the phrase 'event_url_title'
		// -------------------------------------

		//custom variables with the letters 'url' are borked in
		//EE 2.6. Bug reported, but this should fix.
		//https://support.ellislab.com/bugs/detail/19337
		if (version_compare($this->ee_version, '2.6.0', '>='))
		{
			$tagdata = str_replace(
				array(
					LD . 'event_borked_title' . RD,
					'"event_borked_title"',
					"'event_borked_title'"

				),
				array(
					LD . 'event_url_title' . RD,
					'"event_url_title"',
					"'event_url_title'"
				),
				$tagdata
			);


			$tagdata = str_replace(
				array(
					LD . 'event_calendar_borked_title' . RD,
					'"event_calendar_borked_title"',
					"'event_calendar_borked_title'"

				),
				array(
					LD . 'event_calendar_url_title' . RD,
					'"event_calendar_url_title"',
					"'event_calendar_url_title'"
				),
				$tagdata
			);
		}

		return $tagdata;
	}
	/* END events() */

	// --------------------------------------------------------------------

	/**
	 * Show information about an event's occurrences
	 * @return	string
	 */

	public function occurrences()
	{
		//used for special cases *sigh*
		$this->parent_method = __FUNCTION__;

		ee()->TMPL->tagdata = LD.'occurrences'.RD.ee()->TMPL->tagdata.LD.T_SLASH.'occurrences'.RD;
		ee()->TMPL->var_pair['occurrences'] = FALSE;

		//ee()->TMPL->log_item('Calendar: occurrences() method handing off to events() method');

		return $this->events();
	}
	/* END occurrences() */

	// --------------------------------------------------------------------

	/**
	 * Output a calendar in a variety of formats
	 *
	 * @return	string
	 */

	public function cal()
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Start up
		// -------------------------------------

		$this->get_first_day_of_week();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$disable = array(
			'categories',
			'category_fields',
			'custom_fields',
			'member_data',
			'pagination',
			'trackbacks'
		);

		$params = array(
			array(
				'name' => 'category',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE
			),
			array(
				'name' => 'site_id',
				'required' => FALSE,
				'type' => 'integer',
				'min_value' => 1,
				'multi' => TRUE,
				'default' => $this->data->get_site_id()
			),
			array(
				'name' => 'calendar_id',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE
			),
			array(
				'name' => 'calendar_name',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE
			),
			array(
				'name' => 'event_id',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE
			),
			array(
				'name' => 'event_name',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE
			),
			array(
				'name' => 'status',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE,
				'default' => 'open'
			),
			array(
				'name' => 'date_range_start',
				'required' => FALSE,
				'type' => 'date'
			),
			array(
				'name' => 'date_range_end',
				'required' => FALSE,
				'type' => 'date',
				//'default' => 'today'
			),
			array(
				'name' => 'show_days',
				'required' => FALSE,
				'type' => 'integer',
				'default' => 1
				),
			array(
				'name' => 'show_weeks',
				'required' => FALSE,
				'type' => 'integer'
			),
			array(
				'name' => 'show_months',
				'required' => FALSE,
				'type' => 'integer'
			),
			array(
				'name' => 'show_years',
				'required' => FALSE,
				'type' => 'integer'
			),
			array(
				'name' => 'time_range_start',
				'required' => FALSE,
				'type' => 'time',
				'default' => '0000'
			),
			array(
				'name' => 'time_range_end',
				'required' => FALSE,
				'type' => 'time',
				'default' => '2359'
			),
			array(
				'name' => 'day_limit',
				'required' => FALSE,
				'type' => 'integer',
				'default' => '10'
			),
			array(
				'name' => 'event_limit',
				'required' => FALSE,
				'type' => 'integer',
				'default' => '0'
			),
			array(
				'name' => 'first_day_of_week',
				'required' => FALSE,
				'type' => 'integer',
				'default' => $this->first_day_of_week
			),
			array(
				'name' => 'pad_short_weeks',
				'required' => FALSE,
				'type' => 'bool',
				'default' => 'yes',
				'allowed_values' => array('yes', 'no')
			),
			array(
				'name' => 'enable',
				'required' => FALSE,
				'type' => 'string',
				'default' => '',
				'multi' => TRUE,
				'allowed_values' => $disable
			)
		);

		//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		// -------------------------------------
		//  Do some voodoo on P
		// -------------------------------------

		$this->process_events_params();

		// -------------------------------------
		//  Let's go build us a gosh darn calendar!
		// -------------------------------------

		$this->parent_method = __FUNCTION__;

		return $this->build_calendar();
	}
	/* END cal() */


	// --------------------------------------------------------------------

	/**
	 * Output a calendar of a day's events
	 *
	 * @return	string
	 */

	public function day()
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$disable = array(
			'categories',
			'category_fields',
			'custom_fields',
			'member_data',
			'pagination',
			'trackbacks'
		);

		$params = array(
			array(
				'name'				=> 'category',
				'required'			=> FALSE,
				'type'				=> 'string',
				'multi'				=> TRUE
			),
			array(
				'name'				=> 'site_id',
				'required'			=> FALSE,
				'type'				=> 'integer',
				'min_value'			=> 1,
				'multi'				=> TRUE,
				'default'			=> $this->data->get_site_id()
			),
			array(
				'name'				=> 'calendar_id',
				'required'			=> FALSE,
				'type'				=> 'string',
				'multi'				=> TRUE
			),
			array(
				'name'				=> 'calendar_name',
				'required'			=> FALSE,
				'type'				=> 'string',
				'multi'				=> TRUE
			),
			array(
				'name'				=> 'event_id',
				'required'			=> FALSE,
				'type'				=> 'string',
				'multi'				=> TRUE
			),
			array(
				'name'				=> 'event_name',
				'required'			=> FALSE,
				'type'				=> 'string',
				'multi'				=> TRUE
			),
			array(
				'name'				=> 'event_limit',
				'required'			=> FALSE,
				'type'				=> 'integer'
			),
			array(
				'name'				=> 'status',
				'required'			=> FALSE,
				'type'				=> 'string',
				'multi'				=> TRUE,
				'default'			=> 'open'
			),
			array(
				'name'				=> 'date_range_start',
				'required'			=> FALSE,
				'type'				=> 'date'
			),
			array(
				'name'				=> 'time_range_start',
				'required'			=> FALSE,
				'type'				=> 'time',
				'default'			=> '0000'
			),
			array(
				'name'				=> 'time_range_end',
				'required'			=> FALSE,
				'type'				=> 'time',
				'default'			=> '2400'
			),
			array(
				'name'				=> 'enable',
				'required'			=> FALSE,
				'type'				=> 'string',
				'default'			=> '',
				'multi'				=> TRUE,
				'allowed_values' 	=> $disable
			)
		);

		//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		// -------------------------------------
		//  Define parameters specific to this calendar view
		// -------------------------------------

		$this->P->set('date_range_end', $this->P->params['date_range_start']['value']);
		$this->P->set('pad_short_weeks', FALSE);
		$this->P->set('first_day_of_week', $this->first_day_of_week);

		// -------------------------------------
		//  Define our tagdata
		// -------------------------------------

		$find = array(
			'EVENTS_PLACEHOLDER',
			'{/',
			'{',
			'}'
		);

		$replace = array(
			ee()->TMPL->tagdata,
			LD . T_SLASH,
			LD,
			RD
		);

		ee()->TMPL->tagdata = str_replace(
			$find,
			$replace,
			$this->view(
				'day.html',
				array(),
				TRUE,
				$this->sc->addon_theme_path . 'templates/day.html'
			)
		);

		// -------------------------------------
		//  Tell TMPL what we're up to
		// -------------------------------------

		if (strpos(ee()->TMPL->tagdata, LD.'events'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['events'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_hour'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_hour'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_day'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_day'] = TRUE;
		}

		// -------------------------------------
		//  If you build it, they will know what's going on.
		// -------------------------------------

		$this->parent_method = __FUNCTION__;

		return $this->build_calendar();
	}
	/* END day() */


	// --------------------------------------------------------------------

	/**
	 * Output a calendar of today's events
	 *
	 * @return	string
	 */

	public function today()
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$disable = array('categories', 'category_fields', 'custom_fields', 'member_data', 'pagination', 'trackbacks');

		$params = array(
			array(	'name' => 'category',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'site_id',
					'required' => FALSE,
					'type' => 'integer',
					'min_value' => 1,
					'multi' => TRUE,
					'default' => $this->data->get_site_id()
					),
			array(	'name' => 'calendar_id',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'calendar_name',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'event_id',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'event_name',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'event_limit',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'status',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE,
					'default' => 'open'
					),
			array(	'name' => 'enable',
					'required' => FALSE,
					'type' => 'string',
					'default' => '',
					'multi' => TRUE,
					'allowed_values' => $disable
					)
			);

//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		// -------------------------------------
		//  Define parameters specific to this calendar view
		// -------------------------------------

		$this->P->set('date_range_start', $this->CDT->datetime_array());
		$this->P->set('date_range_end', $this->CDT->datetime_array());
		$this->P->set('pad_short_weeks', FALSE);
		$this->P->set('first_day_of_week', $this->first_day_of_week);

		// -------------------------------------
		//  Define our tagdata
		// -------------------------------------

		$find = array(	'EVENTS_PLACEHOLDER',
						'{/',
						'{',
						'}'
						);
		$replace = array(	ee()->TMPL->tagdata,
							LD.T_SLASH,
							LD,
							RD
							);
		ee()->TMPL->tagdata = str_replace($find, $replace, $this->view('day.html', array(), TRUE, $this->sc->addon_theme_path.'templates/day.html'));

		// -------------------------------------
		//  Tell TMPL what we're up to
		// -------------------------------------

		if (strpos(ee()->TMPL->tagdata, LD.'events'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['events'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_hour'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_hour'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_day'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_day'] = TRUE;
		}

		// -------------------------------------
		//  If you build it, they will know what's going on.
		// -------------------------------------

		$this->parent_method = __FUNCTION__;

		return $this->build_calendar();
	}
	/* END today() */

	// --------------------------------------------------------------------

	/**
	 * Output a calendar of this week's events
	 *
	 * @return	string
	 */

	public function week()
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$disable = array(
			'categories',
			'category_fields',
			'custom_fields',
			'member_data',
			'pagination',
			'trackbacks'
		);

		$params = array(
			array(
				'name' => 'category',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE
			),
			array(
				'name' => 'site_id',
				'required' => FALSE,
				'type' => 'integer',
				'min_value' => 1,
				'multi' => TRUE,
				'default' => $this->data->get_site_id()
			),
			array(
				'name' => 'calendar_id',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE
			),
			array(
				'name' => 'calendar_name',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE
			),
			array(
				'name' => 'event_id',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE
			),
			array(
				'name' => 'event_name',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE
			),
			array(
				'name' => 'event_limit',
				'required' => FALSE,
				'type' => 'integer'
			),
			array(
				'name' => 'status',
				'required' => FALSE,
				'type' => 'string',
				'multi' => TRUE,
				'default' => 'open'
			),
			array(
				'name' => 'date_range_start',
				'required' => FALSE,
				'type' => 'date'
			),
			array(
				'name' => 'time_range_start',
				'required' => FALSE,
				'type' => 'time',
				'default' => '0000'
			),
			array(
				'name' => 'time_range_end',
				'required' => FALSE,
				'type' => 'time',
				'default' => '2400'
			),
			array(
				'name' => 'enable',
				'required' => FALSE,
				'type' => 'string',
				'default' => '',
				'multi' => TRUE,
				'allowed_values' => $disable
			),
			array(
				'name' => 'first_day_of_week',
				'required' => FALSE,
				'type' => 'integer',
				'default' => $this->first_day_of_week
			)
		);

		//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		// -------------------------------------
		//  Need to modify the starting date?
		// -------------------------------------

		$this->first_day_of_week = $this->P->value('first_day_of_week');

		if ($this->P->value('date_range_start') === FALSE)
		{
			$this->P->set('date_range_start', $this->CDT->datetime_array());
		}
		else
		{
			$this->CDT->change_date(
				$this->P->value('date_range_start', 'year'),
				$this->P->value('date_range_start', 'month'),
				$this->P->value('date_range_start', 'day')
			);
		}

		$drs_dow = $this->P->value('date_range_start', 'day_of_week');

		if ($drs_dow != $this->first_day_of_week)
		{
			if ($drs_dow > $this->first_day_of_week)
			{
				$offset = ($drs_dow - $this->first_day_of_week);
			}
			else
			{
				$offset = (7 - ($this->first_day_of_week - $drs_dow));
			}

			$this->P->set('date_range_start', $this->CDT->add_day(-$offset));
		}

		// -------------------------------------
		//  Define parameters specific to this calendar view
		// -------------------------------------

		$this->CDT->change_ymd($this->P->value('date_range_start', 'ymd'));
		$this->P->set('date_range_end', $this->CDT->add_day(6));
		$this->P->set('pad_short_weeks', FALSE);

		// -------------------------------------
		//  Define our tagdata
		// -------------------------------------

		$find = array(
			'EVENTS_PLACEHOLDER',
			'{/',
			'{',
			'}'
		);

		$replace = array(
			ee()->TMPL->tagdata,
			LD.T_SLASH,
			LD,
			RD
		);

		ee()->TMPL->tagdata = str_replace(
			$find,
			$replace,
			$this->view(
				'week.html',
				array(),
				TRUE,
				$this->sc->addon_theme_path . 'templates/week.html'
			)
		);

		// -------------------------------------
		//  Tell TMPL what we're up to
		// -------------------------------------

		if (strpos(ee()->TMPL->tagdata, LD.'events'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['events'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_hour'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_hour'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_day'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_day'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_week'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_week'] = TRUE;
		}

		// -------------------------------------
		//  If you build it, they will know what's going on.
		// -------------------------------------

		$this->parent_method = __FUNCTION__;

		return $this->build_calendar();
	}
	// END week()


	// --------------------------------------------------------------------

	/**
	 * Output a calendar of this month's events
	 *
	 * @return	string
	 */

	public function month()
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$disable = array('categories', 'category_fields', 'custom_fields', 'member_data', 'pagination', 'trackbacks');

		$params = array(
			array(	'name' => 'category',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'site_id',
					'required' => FALSE,
					'type' => 'integer',
					'min_value' => 1,
					'multi' => TRUE,
					'default' => $this->data->get_site_id()
					),
			array(	'name' => 'calendar_id',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'calendar_name',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'event_id',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'event_name',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'event_limit',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'status',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE,
					'default' => 'open'
					),
			array(	'name' => 'date_range_start',
					'required' => FALSE,
					'type' => 'date'
					),
			array(	'name' => 'time_range_start',
					'required' => FALSE,
					'type' => 'time',
					'default' => '0000'
					),
			array(	'name' => 'time_range_end',
					'required' => FALSE,
					'type' => 'time',
					'default' => '2400'
					),
			array(	'name' => 'enable',
					'required' => FALSE,
					'type' => 'string',
					'default' => '',
					'multi' => TRUE,
					'allowed_values' => $disable
					),
			array(	'name' => 'first_day_of_week',
					'required' => FALSE,
					'type' => 'integer',
					'default' => $this->first_day_of_week
					),
			array(	'name' => 'start_date_segment',
					'required' => FALSE,
					'type' => 'integer',
					'default' => 3
					)
			);

		//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		// -------------------------------------
		//  Need to modify the starting date?
		// -------------------------------------

		$this->first_day_of_week = $this->P->value('first_day_of_week');

		if ($this->P->value('date_range_start') === FALSE)
		{
			if ($this->P->value('start_date_segment') AND ee()->uri->segment($this->P->value('start_date_segment')) AND strstr(ee()->uri->segment($this->P->value('start_date_segment')), '-'))
			{
				list($year, $month) = explode('-', ee()->uri->segment($this->P->value('start_date_segment')));
				$this->P->params['date_range_start']['value'] = $this->CDT->change_date($year, $month, 1);
			}
			else
			{
				$this->P->params['date_range_start']['value'] = $this->CDT->change_date($this->CDT->year, $this->CDT->month, 1);
			}
		}
		elseif ($this->P->value('date_range_start', 'day') != 1)
		{
			$this->P->params['date_range_start']['value'] = $this->CDT->change_date($this->P->value('date_range_start', 'year'), $this->P->value('date_range_start', 'month'), 1);
		}

		// -------------------------------------
		//  Define parameters specific to this calendar view
		// -------------------------------------

		$this->CDT->change_ymd($this->P->value('date_range_start', 'ymd'));
		$this->P->set('date_range_end', $this->CDT->add_day($this->CDT->days_in_month($this->P->value('date_range_start', 'month'), $this->P->value('date_range_start', 'year')) - 1));
		$this->P->set('pad_short_weeks', TRUE);

		// -------------------------------------
		//  Define our tagdata
		// -------------------------------------

		$find = array(	'EVENTS_PLACEHOLDER',
						'{/',
						'{',
						'}'
						);
		$replace = array(	ee()->TMPL->tagdata,
							LD.T_SLASH,
							LD,
							RD
							);
		ee()->TMPL->tagdata = str_replace($find, $replace, $this->view('month.html', array(), TRUE, $this->sc->addon_theme_path.'templates/month.html'));

		// -------------------------------------
		//  Tell TMPL what we're up to
		// -------------------------------------

		if (strpos(ee()->TMPL->tagdata, LD.'events'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['events'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_hour'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_hour'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_day'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_day'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_week'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_week'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_month'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_month'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_day_of_week'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_day_of_week'] = TRUE;
		}

		// -------------------------------------
		//  If you build it, they will know what's going on.
		// -------------------------------------

		$this->parent_method = __FUNCTION__;

		return $this->build_calendar();
	}
	/* END month() */

	// --------------------------------------------------------------------

	/**
	 * Output a mini calendar of this month's events
	 *
	 * @return	string
	 */

	public function mini()
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$disable = array('categories', 'category_fields', 'custom_fields', 'member_data', 'pagination', 'trackbacks');

		$params = array(
			array(	'name' => 'category',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'site_id',
					'required' => FALSE,
					'type' => 'integer',
					'min_value' => 1,
					'multi' => TRUE,
					'default' => $this->data->get_site_id()
					),
			array(	'name' => 'calendar_id',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'calendar_name',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'event_id',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'event_name',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'event_limit',
					'required' => FALSE,
					'type' => 'integer'
					),
			array(	'name' => 'status',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE,
					'default' => 'open'
					),
			array(	'name' => 'enable',
					'required' => FALSE,
					'type' => 'string',
					'default' => '',
					'multi' => TRUE,
					'allowed_values' => $disable
					),
			array(	'name' => 'date_range_start',
					'required' => FALSE,
					'type' => 'date'
					),
			array(	'name' => 'time_range_start',
					'required' => FALSE,
					'type' => 'time',
					'default' => '0000'
					),
			array(	'name' => 'time_range_end',
					'required' => FALSE,
					'type' => 'time',
					'default' => '2400'
					),
			array(	'name' => 'first_day_of_week',
					'required' => FALSE,
					'type' => 'integer',
					'default' => $this->first_day_of_week
					),
			array(	'name' => 'start_date_segment',
					'required' => FALSE,
					'type' => 'integer',
					'default' => 3
					)
			);

		//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		// -------------------------------------
		//  Need to modify the starting date?
		// -------------------------------------

		$this->first_day_of_week = $this->get_first_day_of_week();

		if ($this->P->value('date_range_start') === FALSE)
		{
			if ($this->P->value('start_date_segment') AND
				ee()->uri->segment($this->P->value('start_date_segment')) AND
				strstr(ee()->uri->segment($this->P->value('start_date_segment')), '-'))
			{
				list($year, $month) = explode('-', ee()->uri->segment($this->P->value('start_date_segment')));
				$this->P->params['date_range_start']['value'] = $this->CDT->change_date($year, $month, 1);
			}
			else
			{
				$this->P->params['date_range_start']['value'] = $this->CDT->change_date($this->CDT->year, $this->CDT->month, 1);
			}
		}
		elseif ($this->P->value('date_range_start', 'day') > 1)
		{
			$this->P->set(
				'date_range_start',
				$this->CDT->change_date($this->P->value('date_range_start', 'year'),
				$this->P->value('date_range_start', 'month'), 1)
			);
		}

		// -------------------------------------
		//  Define parameters specific to this calendar view
		// -------------------------------------

		$this->CDT->change_ymd($this->P->value('date_range_start', 'ymd'));
		$this->P->set('date_range_end', $this->CDT->add_day($this->CDT->days_in_month() - 1));

		$this->P->set('pad_short_weeks', TRUE);

		// -------------------------------------
		//  Define our tagdata
		// -------------------------------------

		$find = array(
			'EVENTS_PLACEHOLDER',
			'{/',
			'{',
			'}'
		);

		$replace = array(
			ee()->TMPL->tagdata,
			LD.T_SLASH,
			LD,
			RD
		);

		ee()->TMPL->tagdata = str_replace(
			$find,
			$replace,
			$this->view('mini.html', array(), TRUE, $this->sc->addon_theme_path.'templates/mini.html')
		);

		// -------------------------------------
		//  Tell TMPL what we're up to
		// -------------------------------------

		if (strpos(ee()->TMPL->tagdata, LD.'events'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['events'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_hour'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_hour'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_day'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_day'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_week'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_week'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_month'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_month'] = TRUE;
		}

		if (strpos(ee()->TMPL->tagdata, LD.'display_each_day_of_week'.RD) !== FALSE)
		{
			ee()->TMPL->var_pair['display_each_day_of_week'] = TRUE;
		}

		// -------------------------------------
		//  If you build it, they will know what's going on.
		// -------------------------------------

		$this->parent_method = __FUNCTION__;

		return $this->build_calendar();
	}
	/* END mini() */

	// --------------------------------------------------------------------

	/**
	 * For stand-alone entry forms
	 *
	 * @return	string
	 */

	public function form($return_form = FALSE, $captcha = '')
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		$output = '';
		$event_id = '';
		$event_data = array();
		$edit = FALSE;

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$params = array(
			array(	'name'		=> 'event_name',
					'required'	=> FALSE,
					'type'		=> 'bool',
					'multi'		=> FALSE
					),
			array(	'name'		=> 'event_id',
					'required'	=> FALSE,
					'type'		=> 'integer',
					'multi'		=> FALSE
					),
			array(	'name'		=> 'occurrence_id',
					'required'	=> FALSE,
					'type'		=> 'integer',
					'multi'		=> FALSE
					),
			array(	'name'		=> 'occurrence_date',
					'required'	=> FALSE,
					'type'		=> 'date'
					),
			array(	'name'		=> 'ignore_field',
					'required'	=> FALSE,
					'type'		=> 'string',
					'multi'		=> TRUE
					)
			);

		//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		// -------------------------------------
		//  Has an event_name been provided?
		// -------------------------------------

		if ($this->P->value('event_name') !== FALSE)
		{
			$ids = $this->data->get_event_id_from_name($this->P->value('event_name'));
			if (! empty($ids))
			{
				$event_id = $ids[0];
				$event_data = $this->data->fetch_event_data_for_view($event_id);
				if (! empty($event_data))
				{
					$edit = TRUE;
				}
			}
		}
		elseif ($this->P->value('event_id') !== FALSE)
		{
			$event_data = $this->data->fetch_event_data_for_view($this->P->value('event_id'));
			$event_id = $this->P->value('event_id');
			if (! empty($event_data))
			{
				$edit = TRUE;
			}
		}
		elseif ($this->P->value('occurrence_id') !== FALSE)
		{
			$event_id = $this->data->fetch_entry_id_by_occurrence_id($this->P->value('occurrence_id'));
			if (! empty($event_id))
			{
				$event_id = $event_id[0];
				$edit = TRUE;
			}
		}

		// -------------------------------------
		//  Ignore fields?
		// -------------------------------------

		$ignore_fields = array();

		if ($this->P->value('ignore_field') !== FALSE)
		{
			$ignore_fields = explode('|', $this->P->value('ignore_field'));
		}

		// -------------------------------------
		//  Add some hidden values if we're creating a new edited occurrence
		// -------------------------------------

		$event_data['edit_occurrence'] = FALSE;

		if ($this->P->value('occurrence_date') !== FALSE AND
			($this->P->value('event_name') !== FALSE OR
				$this->P->value('event_id') !== FALSE))
		{
			//ee()->TMPL->log_item('Calendar: This is an edited occurrence');

			ee()->TMPL->tagdata .= '<input type="hidden" name="entry_id" value="" />'."\n";
			ee()->TMPL->tagdata .= '<input type="hidden" name="calendar_parent_entry_id" value="{entry_id}" />'."\n";
			ee()->TMPL->tagdata .= '<input type="hidden" name="event_id" value="'.$event_data['event_id'].'" />'."\n";
			ee()->TMPL->tagdata .= '<input type="hidden" name="start_date" value="'.$this->P->value('occurrence_date', 'ymd').'" />'."\n";
			ee()->TMPL->tagdata .= '<input type="hidden" name="start_time" value="'.$event_data['start_time'].'" />'."\n";
			ee()->TMPL->tagdata .= '<input type="hidden" name="end_time" value="'.$event_data['end_time'].'" />'."\n";
			ee()->TMPL->var_single['entry_id'] = 'entry_id';
			$event_data['edit_occurrence'] = TRUE;
			$event_data['new_occurrence'] = TRUE;
			$event_data['occurrence_id'] = '';
			$event_data['ymd'] = $this->P->value('occurrence_date', 'ymd');
		}

		// -------------------------------------
		//  Add date widget
		// -------------------------------------

		if (strpos(ee()->TMPL->tagdata, LD.'calendar_date_widget'.RD) !== FALSE)
		{
			ee()->TMPL->tagdata = str_replace(LD.'calendar_date_widget'.RD, $this->date_widget($event_data), ee()->TMPL->tagdata);
		}

		//	----------------------------------------
		//	Invoke Channel class
		//	----------------------------------------

		if ( ! class_exists('Channel') )
		{
			require PATH_MOD.'/channel/mod.channel.php';
		}

		$channel = new Channel;

		//need to remove limit here so huge amounts of events work
		$channel->limit = 1000000;

		// --------------------------------------------
		//  Invoke Pagination for EE 2.4 and Above
		// --------------------------------------------

		$channel = $this->add_pag_to_channel($channel);

		// -------------------------------------
		//  Prepare parameters
		// -------------------------------------

		ee()->TMPL->tagparams['entry_id']	= $event_id;
		ee()->TMPL->tagparams['channel']	= CALENDAR_EVENTS_CHANNEL_NAME;

		// -------------------------------------
		//  Editing?
		// -------------------------------------

		if ($edit === TRUE)
		{
			//ee()->TMPL->log_item('Calendar: Editing, so doing Weblog module tasks');

			// -------------------------------------
			//  Pre-process related data
			// -------------------------------------

		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			ee()->TMPL->tagdata = ee()->TMPL->assign_relationship_data( ee()->TMPL->tagdata );
		}

			ee()->TMPL->var_single = array_merge( ee()->TMPL->var_single, ee()->TMPL->related_markers );

			// -------------------------------------
			//  Execute needed methods
			// -------------------------------------

			$channel->fetch_custom_channel_fields();

			$channel->fetch_custom_member_fields();

			// --------------------------------------------
			//  Pagination Tags Parsed Out
			// --------------------------------------------

			$channel = $this->fetch_pagination_data($channel);

			// -------------------------------------
			//  Querification
			// -------------------------------------

			$channel->build_sql_query();

			if ($channel->sql == '')
			{
				return $this->no_results();
			}

			$channel->query = ee()->db->query($channel->sql);

			if ($channel->query->num_rows() == 0)
			{
	//ee()->TMPL->log_item('Calendar: Channel module says no results, bailing');
				return $this->no_results();
			}

			$channel->query->result	= $channel->query->result_array();

			// --------------------------------------------
			//  Typography
			// --------------------------------------------

			ee()->load->library('typography');
			ee()->typography->initialize();
			ee()->typography->text_format = 'none';
			ee()->typography->convert_curly = FALSE;

			$no_parse = array('xhtml', 'br', 'none', 'lite');

			// -------------------------------------
			//  Add _field_name and _field_format variables
			// -------------------------------------

			foreach ($channel->query->result as $r => $row)
			{
				$channel->query->result[$r]['author'] = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];

				foreach ($channel->cfields[$row['site_id']] as $k => $v)
				{
					if (in_array($k, $ignore_fields))
					{
						continue;
					}

					$channel->query->result[$r][$k.'_field_name'] = 'field_id_'.$v;
					$channel->query->result[$r][$k.'_format_name'] = 'field_ft_'.$v;
					$channel->query->result[$r][$k.'_format_value'] = ($row['field_ft_'.$v] === NULL) ? '' : $row['field_ft_'.$v];

					// -------------------------------------
					//  Don't apply any text formatting
					// -------------------------------------

					if (in_array($channel->query->result[$r]['field_ft_'.$v], $no_parse))
					{
						$channel->query->result[$r]['field_ft_'.$v] = '';
					}
				}
			}

			//	----------------------------------------
			//	Redeclare
			//	----------------------------------------
			//	We will reassign the $channel->query->result with our
			//	reordered array of values. Thank you PHP for being so fast with array loops.
			//	----------------------------------------

			$super_temp_fake = $channel->query->result_array = $channel->query->result;
			$super_temp_fake	= $channel->query->row_array();

			$channel->fetch_categories();

			// -------------------------------------
			//  Prep ignored fields to be ignored
			// -------------------------------------

			$gibberish = 'e46b98f8a2a06d1ac6069e8980693dc0';

			foreach ($ignore_fields as $field)
			{
				ee()->TMPL->tagdata = str_replace(LD.$field, LD.$gibberish.$field, ee()->TMPL->tagdata);
			}

			// -------------------------------------
			//  Parse
			// -------------------------------------

			//ee()->TMPL->log_item('Calendar: Parsing, via channel module');

			$channel->parse_channel_entries();

			// -------------------------------------
			//  De-prep ignored fields
			// -------------------------------------

			foreach ($ignore_fields as $field)
			{
				$channel->return_data = str_replace(LD.$gibberish.$field, LD.$field, $channel->return_data);
			}

			// -------------------------------------
			//  Paginate
			// -------------------------------------

			$channel = $this->add_pagination_data($channel);

			// -------------------------------------
			//  Related entries
			// -------------------------------------

			if (version_compare($this->ee_version, '2.6.0', '<'))
			{
				if (count(ee()->TMPL->related_data) > 0 AND
					count($channel->related_entries) > 0)
				{
					$channel->parse_related_entries();
				}

				if (count(ee()->TMPL->reverse_related_data) > 0 AND
					count($channel->reverse_related_entries) > 0)
				{
					$channel->parse_reverse_related_entries();
				}
			}

			// -------------------------------------
			//  Grab the goods
			// -------------------------------------

			ee()->TMPL->tagdata = $channel->return_data;

			// -------------------------------------
			//  Add some hidden variables
			// -------------------------------------

			//EE 2.6 compat
			$func = (is_callable(array(ee()->localize, 'format_date'))) ?
						'format_date' :
						'decode_date';

			$date = ee()->localize->$func('%Y-%m-%d %g:%i %A', $super_temp_fake['entry_date']);
			$more = "<input type='hidden' name='entry_date' value='{$date}' />\n";
			if ($this->P->value('occurrence_date') === FALSE OR $this->P->value('event_name') === FALSE OR $this->P->value('event_id') === FALSE)
			{
				$more .= "<input type='hidden' name='entry_id' value='{$event_id}' />\n";
			}

			ee()->TMPL->tagdata .= $more;
		}
		else
		{
			// -------------------------------------
			//  Add _field_name and _field_format variables
			// -------------------------------------

			$channel->fetch_custom_channel_fields();

			$fields = array();

			foreach ($channel->cfields[$this->data->get_site_id()] as $k => $v)
			{
				$fields[LD.$k.'_field_name'.RD] = 'field_id_'.$v;
				$fields[LD.$k.'_format_name'.RD] = 'field_ft_'.$v;
				$fields[LD.$k.'_format_value'.RD] = '';
			}

			ee()->TMPL->tagdata = str_replace(array_keys($fields), $fields, ee()->TMPL->tagdata);
		}

		// -------------------------------------
		//  Remove any leftover {event_ or {calendar_ variables
		// -------------------------------------

		if (strpos(ee()->TMPL->tagdata, LD.'calendar_') !== FALSE)
		{
			preg_match_all('#'.LD.'calendar_(.*?)'.RD.'#', ee()->TMPL->tagdata, $matches);
			if (! empty($matches))
			{
				foreach ($matches[0] as $k => $match)
				{
					ee()->TMPL->tagdata = str_replace($match, '', ee()->TMPL->tagdata);
				}
			}
		}

		if (strpos(ee()->TMPL->tagdata, LD.'event_') !== FALSE)
		{
			preg_match_all('#'.LD.'(event_.*?)'.RD.'#', ee()->TMPL->tagdata, $matches);
			if (! empty($matches))
			{
				foreach ($matches[0] as $k => $match)
				{
					if (! in_array($matches[1][$k], $ignore_fields))
					{
						ee()->TMPL->tagdata = str_replace($match, '', ee()->TMPL->tagdata);
					}
				}
			}
		}

		//	----------------------------------------
		//	Invoke Channel standalone class
		//	----------------------------------------

		if ( ! class_exists('Channel_standalone'))
		{
			require_once PATH_MOD.'channel/mod.channel_standalone.php';
		}

		$CS = new Channel_standalone();
		$output = $CS->entry_form($return_form, $captcha);

		//ee()->TMPL->log_item('Calendar: Done!');

		return $output;
	}
	/* END form() */

	// --------------------------------------------------------------------

	/**
	 * Date widget
	 *
	 * @param	array	$data	Array of data for the view
	 * @return	string
	 */

	public function date_widget($data = array())
	{
		$this->actions();

		$id = ee()->TMPL->fetch_param('event_id');

		if ($id AND is_numeric($id) AND $id > 0)
		{
			$data = $this->data->fetch_event_data_for_view($id);

			$eoid = $this->data->get_event_entry_id_by_channel_entry_id($id);

			if ($eoid != FALSE AND $eoid != $id)
			{
				$data['edit_occurrence'] = TRUE;
			}
		}

		// -------------------------------------
		//	calendar permissions
		// -------------------------------------

		ee()->load->library('calendar_permissions');

		if ($id > 0)
		{
			$temp = $this->data->get_calendar_id_by_event_entry_id($id);
			$parent_calendar_id =  (isset($temp[$id]) ? $temp[$id] : 0);
		}

		if ( $id != 0 AND
			 $parent_calendar_id > 0 AND
			 ! ee()->calendar_permissions->group_has_permission(
				ee()->session->userdata['group_id'],
				$parent_calendar_id
		))
		{
			return $this->show_error(lang('invalid_calendar_permissions'));
		}

		if ( ! isset($data['rules'])) 			$data['rules'] 				= array();
		if ( ! isset($data['occurrences'])) 	$data['occurrences'] 		= array();
		if ( ! isset($data['exceptions'])) 		$data['exceptions'] 		= array();
		if ( ! isset($data['edit_occurrence'])) $data['edit_occurrence'] 	= FALSE;

		return $this->actions->date_widget($data);
	}

	// --------------------------------------------------------------------
	/**
	* Takes a number and converts it to a-z,aa-zz,aaa-zzz, etc with uppercase option
	*
	* @access private
	* @param int number to convert
	* @param bool upper case the letter on return?
	* @return string letters from number input
	*/

	private function num_to_letter($num, $uppercase = FALSE)
	{
		$letter = chr((($num - 1) % 26) + 97);
		$letter .= (floor($num/26) > 0) ? str_repeat($letter, floor($num/26)) : '';
		return ($uppercase ? strtoupper($letter) : $letter);
	}
	//END num_to_letter

	/**
	 * Make a unique id string
	 *
	 * @access	protected
	 * @return	string	long string of 'unique' characters
	 */
	protected function make_uid()
	{
		$date   = date('Ymd\THisT');
		$unique = substr(microtime(), 2, 4);
		$base   = 'aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPrRsStTuUvVxXuUvVwWzZ1234567890';
		$start  = 0;
		$end    = strlen( $base ) - 1;
		$length = 6;
		$str    = null;
		for( $p = 0; $p < $length; $p++ )
		{
			$unique .= $base{mt_rand( $start, $end )};
		}
		return $date . '-' . $unique . ($this->num_to_letter(++$this->uid_counter));
	}
	//END makeUid


	// --------------------------------------------------------------------

	/**
	 * check dates to see if something is all day
	 * because this equation could change a bit
	 * @access	private
	 * @param	string		start hour
	 * @param	string		start minute
	 * @param	string		end hour
	 * @param	string		end minute
	 * @return	bool
	 */

	private function _is_all_day($start_hour, $start_minute, $end_hour, $end_minute)
	{
		return ($start_hour == 0 AND $start_minute == 0 AND
				(($end_hour == '23' AND $end_minute == '59') OR
				 ($end_hour == '24' AND $end_minute == 0)));
	}
	//END _is_all_day


	// --------------------------------------------------------------------

	/**
	 * Prepare output in icalendar format a la RFC2445
	 * http://www.ietf.org/rfc/rfc2445.txt
	 *
	 * @return	string
	 */

	public function icalendar()
	{
		$s = 'EJURI3ia8aj#912IKa';
		$r = '#';
		$e = 'aAEah38a;a33';

		// -------------------------------------
		//  Some dummy tagdata we'll hand off to events()
		// -------------------------------------

		$vars = array(
			'event_title'					=> 'title',
			'event_id'						=> 'id',
			'event_summary'					=> 'summary',
			'event_location'				=> 'location',
			'event_start_date format="%Y"'	=> 'start_year',
			'event_start_date format="%m"'	=> 'start_month',
			'event_start_date format="%d"'	=> 'start_day',
			'event_start_date format="%H"'	=> 'start_hour',
			'event_start_date format="%i"'	=> 'start_minute',
			'event_end_date format="%Y"'	=> 'end_year',
			'event_end_date format="%m"'	=> 'end_month',
			'event_end_date format="%d"'	=> 'end_day',
			'event_end_date format="%H"'	=> 'end_hour',
			'event_end_date format="%i"'	=> 'end_minute',
			'event_calendar_tz_offset'		=> 'tz_offset',
			'event_calendar_timezone'		=> 'timezone'
		);

		$rvars = array(
			'rule_type',
			'rule_start_date',
			'rule_repeat_years',
			'rule_repeat_months',
			'rule_repeat_days',
			'rule_repeat_weeks',
			'rule_days_of_week',
			'rule_relative_dow',
			'rule_days_of_month',
			'rule_months_of_year',
			'rule_stop_by',
			'rule_stop_after'
		);

		$evars = array(
			'exception_start_date format="%Y%m%dT%H%i00"'
		);

		$ovars = array(
			'occurrence_start_date format="%Y%m%dT%H%i00"',
			'occurrence_end_date format="%Y%m%dT%H%i00"'
		);

		//ee()->TMPL->log_item('Calendar: Preparing tagdata');

		$summary_field = ee()->TMPL->fetch_param('summary_field', 'event_title');

		ee()->TMPL->tagdata =	implode($s, array(
			LD . $summary_field . RD,
			LD . 'event_id' . RD,
			LD . 'if event_summary' . RD .
				LD . 'event_summary' . RD .
				LD . '/if' . RD,
			LD . 'if event_location' . RD .
				LD . 'event_location' . RD .
				LD . '/if' . RD,
			LD . 'event_start_date format="%Y"' . RD,
			LD . 'event_start_date format="%m"' . RD,
			LD . 'event_start_date format="%d"' . RD,
			LD . 'event_start_date format="%H"' . RD,
			LD . 'event_start_date format="%i"' . RD,
			LD . 'event_end_date format="%Y"' . RD,
			LD . 'event_end_date format="%m"' . RD,
			LD . 'event_end_date format="%d"' . RD,
			LD . 'event_end_date format="%H"' . RD,
			LD . 'event_end_date format="%i"' . RD,
			LD . 'event_calendar_tz_offset' . RD,
			LD . 'event_calendar_timezone' . RD,
			'RULES' .
				LD . 'if event_has_rules' . RD .
				LD . 'rules' . RD .
				LD . implode(RD . $r . LD, $rvars) . RD . '|' .
				LD . T_SLASH . 'rules' . RD .
				LD . '/if' . RD,
			'OCCURRENCES'.
				LD . 'if event_has_occurrences' . RD .
				LD . 'occurrences' . RD .
				LD . implode(RD . $r . LD, $ovars) . RD . '|' .
				LD . T_SLASH . 'occurrences' . RD .
				LD . '/if' . RD,
			'EXCEPTIONS'.
				LD . 'if event_has_exceptions' . RD .
				LD . 'exceptions' . RD .
				LD . implode(RD . $r . LD, $evars) . RD . '|' .
				LD . T_SLASH . 'exceptions' . RD .
				LD . '/if' . RD,
			$e
		));

		$tvars 					= ee()->functions->assign_variables(
			ee()->TMPL->tagdata
		);
		ee()->TMPL->var_single 	= $tvars['var_single'];
		ee()->TMPL->var_pair 	= $tvars['var_pair'];
		ee()->TMPL->tagdata 	= ee()->functions->prep_conditionals(
			ee()->TMPL->tagdata,
			array_keys($vars)
		);

		// -------------------------------------
		//  Fire up events()
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Firing up Events()');

		$tagdata = ee()->TMPL->advanced_conditionals($this->events());

		// -------------------------------------
		//  Collect the events
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Collecting events');

		$events = explode($e, $tagdata);

		// -------------------------------------
		//  Fire up iCalCreator
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Starting iCalCreator');

		if ( ! class_exists('vcalendar'))
		{
			require_once 'libraries/icalcreator/iCalcreator.class.php';
		}

		$ICAL = new vcalendar();

		//we are setting this manually because we need individual ones for each event for this to work
		//$ICAL->setConfig('unique_id', parse_url(ee()->config->item('site_url'), PHP_URL_HOST));
		$host = parse_url(ee()->config->item('site_url'), PHP_URL_HOST);


		$vars = array_values($vars);

		//ee()->TMPL->log_item('Calendar: Iterating through the events');

		foreach ($events as $key => $event)
		{
			if (trim($event) == '') continue;

			$E 				= new vevent();

			$event 			= explode($s, $event);
			$rules 			= '';
			$occurrences 	= '';
			$exceptions 	= '';

			foreach ($event as $k => $v)
			{
				if (isset($vars[$k]))
				{
					//--------------------------------------------
					//	Makes the local vars from above, if available:
					// 	$title, $summary, $location,
					//  $start_year, $start_month, $start_day,
					//  $start_hour, $start_minute, $end_year,
					//  $end_month, $end_day, $end_hour,
					// 	$end_minute, $tz_offset, $timezone
					//--------------------------------------------

					$$vars[$k] = $v;
				}
				elseif (substr($v, 0, 5) == 'RULES')
				{
					$rules = substr($v, 5);
				}
				elseif (substr($v, 0, 11) == 'OCCURRENCES')
				{
					$occurrences = substr($v, 11);
				}
				elseif (substr($v, 0, 10) == 'EXCEPTIONS')
				{
					$exceptions = substr($v, 10);
				}
			}

			// -------------------------------------
			//  Set the timezone for this calendar based on the first event's info
			// -------------------------------------

			if ($key == 0)
			{
				// -------------------------------------
				//  Convert calendar_name to calendar_id
				// -------------------------------------

				if ($this->P->value('calendar_id') == '' AND
					$this->P->value('calendar_name') != '')
				{
					$ids = $this->data->get_calendar_id_from_name(
						$this->P->value('calendar_name'),
						NULL,
						$this->P->params['calendar_name']['details']['not']
					);

					$this->P->set('calendar_id', implode('|', $ids));
				}

				//--------------------------------------------
				//	lets try to get the timezone from the
				//	passed calendar ID if there is one
				//--------------------------------------------

				$cal_timezone 	= FALSE;
				$cal_tz_offset 	= FALSE;

				if ($this->P->value('calendar_id') != '')
				{
					$sql = "SELECT 	tz_offset, timezone
							FROM	exp_calendar_calendars
							WHERE 	calendar_id
							IN 		(" . ee()->db->escape_str(
											implode(',',
												explode('|',
													$this->P->value('calendar_id')
												)
											)
										) .
									")
							LIMIT	1";

					$cal_tz_query = ee()->db->query($sql);

					if ($cal_tz_query->num_rows() > 0)
					{
						$cal_timezone 	= $cal_tz_query->row('timezone');
						$cal_tz_offset 	= $cal_tz_query->row('tz_offset');
					}
				}

				//last resort, we get it from the current event

				$T = new vtimezone();
				$T->setProperty('tzid', ($cal_timezone ? $cal_timezone : $timezone));
				$T->setProperty('tzoffsetfrom', '+0000');

				$tzoffsetto = ($cal_tz_offset ? $cal_tz_offset : $tz_offset);

				if ($tzoffsetto === '0000')
				{
					$tzoffsetto = '+0000';
				}

				$T->setProperty('tzoffsetto', $tzoffsetto);
				$ICAL->setComponent($T);
			}

			$title			= strip_tags($title);
			$description	= strip_tags(trim($summary));
			$location		= strip_tags(trim($location));

			// -------------------------------------
			//  Occurrences?
			// -------------------------------------

			$occurrences	= explode('|', rtrim($occurrences, '|'));
			$odata			= array();

			foreach ($occurrences as $k => $occ)
			{
				$occ = trim($occ);
				if ($occ == '') continue;

				$occ = explode($r, $occ);
				$odata[$k][] = $occ[0];
				$odata[$k][] = $occ[1];
			}

			// -------------------------------------
			//  Exceptions?
			// -------------------------------------

			$exceptions	= explode('|', rtrim($exceptions, '|'));
			$exdata		= array();

			foreach ($exceptions as $k => $exc)
			{
				$exc = trim($exc);

				if ($exc == '') continue;

				$exdata[] = $exc;
			}

			// -------------------------------------
			//  Rules?
			// -------------------------------------

			$add_rules 	= FALSE;
			$erules 	= array();
			$rules 		= explode('|', rtrim($rules, '|'));

			foreach ($rules as $rule)
			{
				$temp = explode($r, $rule);
				$rule = array();

				foreach ($temp as $k => $v)
				{
					if ($v != FALSE) $add_rules = TRUE;
					$rule[substr($rvars[$k], 5)] = $v;
				}

				if ($add_rules === TRUE)
				{
					$temp = array();

					if ($rule['repeat_years'] > 0)
					{
						$temp['FREQ'] = 'YEARLY';

						if ($rule['repeat_years'] > 1)
						{
							$temp['INTERVAL'] = $rule['repeat_years'];
						}
					}
					elseif ($rule['repeat_months'] > 0)
					{
						$temp['FREQ'] = 'MONTHLY';

						if ($rule['repeat_months'] > 1)
						{
							$temp['INTERVAL'] = $rule['repeat_months'];
						}
					}
					elseif ($rule['repeat_weeks'] > 0)
					{
						$temp['FREQ'] = 'WEEKLY';

						if ($rule['repeat_weeks'] > 1)
						{
							$temp['INTERVAL'] = $rule['repeat_weeks'];
						}
					}
					elseif ($rule['repeat_days'] > 0)
					{
						$temp['FREQ'] = 'DAILY';

						if ($rule['repeat_days'] > 1)
						{
							$temp['INTERVAL'] = $rule['repeat_days'];
						}
					}

					if ($rule['months_of_year'] > 0)
					{
						//this flips keys to make 'c' => 12, etc
						$m = array_flip(array(
							1, 2, 3, 4, 5, 6, 7, 8, 9, 'A', 'B', 'C'
						));

						if (strlen($rule['months_of_year'] > 1))
						{
							$months = str_split($rule['months_of_year']);
							foreach ($months as $month)
							{
								$temp['BYMONTH'][] = $m[$month] + 1;
							}
						}
						else
						{
							$temp['BYMONTH'] = $m[$month] + 1;
						}
					}

					if ($rule['days_of_month'] > '')
					{
						//this flips keys to make 'v' => 30, etc
						$d = array_flip(array(
							1, 2, 3, 4, 5, 6, 7, 8, 9,
							'A', 'B', 'C', 'D', 'E', 'F',
							'G', 'H', 'I', 'J', 'K', 'L',
							'M', 'N', 'O', 'P', 'Q', 'R',
							'S', 'T', 'U', 'V'
						));

						if (strlen($rule['days_of_month']) > 1)
						{
							$days = str_split($rule['days_of_month']);
							foreach ($days as $day)
							{
								$temp['BYMONTHDAY'][] = $d[$day] + 1;
							}
						}
						else
						{
							$temp['BYMONTHDAY'] = $d[$rule['days_of_month']] + 1;
						}
					}

					if ($rule['days_of_week'] != '' OR $rule['days_of_week'] > 0)
					{
						$d 			= array('SU','MO','TU','WE','TH','FR','SA');
						$d_letter 	= array('U','M','T','W','R','F','S');

						$dows 		= str_split($rule['days_of_week']);

						if ($rule['relative_dow'] > 0)
						{
							$rels = str_split($rule['relative_dow']);
							foreach ($dows as $dow)
							{
								foreach ($rels as $rel)
								{
									if ($rel == 6) $rel = -1;
									$temp['BYDAY'][] = $rel.$d[array_search($dow, $d_letter)];
								}
							}
						}
						else
						{
							foreach ($dows as $dow)
							{
								$temp['BYDAY'][] = $d[array_search($dow, $d_letter)];
							}
						}
					}

					if ($rule['stop_after'] > 0)
					{
						$temp['COUNT'] = $rule['stop_after'];
					}
					elseif ($rule['stop_by'] > 0)
					{
						// TODO: Add time
						// TODO: The "+1" below is because the ical standard treats
						// 	UNTIL as "less than", not "less than or equal to" (which
						// 	is how Calendar treats stop_by). Double check that a simple
						// 	"+1" accurately addresses this difference.
						$temp['UNTIL'] = $rule['stop_by'] + 1;
					}

					$erules[] = $temp;
				}
			}

			// -------------------------------------
			//  Put it together
			// -------------------------------------

			//if this is all day we need to add the dates as params to the dstart and end items
			if ($this->_is_all_day($start_hour, $start_minute, $end_hour, $end_minute))
			{
				$E->setProperty(
					"dtstart" ,
					array(
						'year' 	=> $start_year,
						'month' => $start_month,
						'day'	=> $start_day
					),
					array(
						'VALUE' => 'DATE'
					)
				);

				//--------------------------------------------
				//	we need CDT so we can add a day
				//	gcal, and ical are ok with this being the same day
				//	stupid damned outlook barfs, hence the +1
				//	the +1 doesnt affect g/ical
				//--------------------------------------------

				if ( ! isset($this->CDT) OR ! is_object($this->CDT) )
				{
					$this->load_calendar_datetime();
				}

				$this->CDT->change_date(
					$end_year,
					$end_month,
					$end_day
				);

				$this->CDT->add_day();

				$E->setProperty(
					"dtend" ,
					array(
						'year' 	=> $this->CDT->year,
						'month' => $this->CDT->month,
						'day'	=> $this->CDT->day
					),
					array(
						'VALUE' => 'DATE'
					)
				);
			}
			else
			{
				$E->setProperty('dtstart', $start_year, $start_month, $start_day, $start_hour, $start_minute, 00);
				$E->setProperty('dtend', $end_year, $end_month, $end_day, $end_hour, $end_minute, 00);
			}

			$E->setProperty('summary', $title);

			if ( ! empty($erules))
			{
				foreach ($erules as $rule)
				{
					$E->setProperty('rrule', $rule);
				}
			}

			$extras = array();
			$edits	= array();

			if ( ! empty($odata))
			{
				$query = ee()->db->query(
					"SELECT *
					 FROM	exp_calendar_events_occurrences
					 WHERE	event_id = " . ee()->db->escape_str($id)
				);

				foreach ($query->result_array() as $row)
				{
					//fix blank times
					$row['start_time'] 	= ($row['start_time'] == 0) ? '0000' 	: $row['start_time'];
					$row['end_time'] 	= ($row['end_time'] == 0) ? '2400' 		: $row['end_time'];

					//looks like an edited occurrence
					//edits without rules arent really edits.
					if ($row['event_id'] != $row['entry_id'] AND empty($rules))
					{
						$edits[] = $row;
					}
					//probably entered with the date picker or something
					//these loose occurences
					else
					{
						$extras[] = $row;
					}
				}
			}

			if ( ! empty($exdata))
			{
				$E->setProperty('exdate', $exdata);
			}

			if ($description != '') $E->setProperty('description', $description);
			if ($location != '') $E->setProperty('location', $location);


			$E->setProperty( "uid", $this->make_uid() . '@' . $host);
			$ICAL->setComponent($E);

			//--------------------------------------------
			//	remove rules for subsequent items
			//--------------------------------------------

			while( $E->deleteProperty( "RRULE" )) continue;

			//edits must come right after
			if ( ! empty($edits))
			{
				foreach ($edits as $edit)
				{
					$edit_date = array(
						"year" 	=> $edit['start_year'],
						"month" => $edit['start_month'],
						"day" 	=> $edit['start_day'] ,
						"hour" 	=> substr($edit['start_time'], 0, 2) ,
						"min" 	=> substr($edit['start_time'], 2, 2)
					);

					//if this is all day we need to add the dates as params to the dstart and end items
					if ($this->_is_all_day(
						substr($edit['start_time'], 0, 2),
						substr($edit['start_time'], 2, 2),
						substr($edit['end_time'], 0, 2),
						substr($edit['end_time'], 2, 2)
					  ))
					{
						$E->setProperty(
							"dtstart" ,
							array(
								'year' 	=> $edit['start_year'],
								'month' => $edit['start_month'],
								'day'	=> $edit['start_day']
							),
							array(
								'VALUE' => 'DATE'
							)
						);

						//--------------------------------------------
						//	we need CDT so we can add a day
						//	gcal, and ical are ok with this being the same day
						//	stupid damned outlook barfs, hence the +1
						//	the +1 doesnt affect g/ical
						//--------------------------------------------

						if ( ! isset($this->CDT) OR ! is_object($this->CDT) )
						{
							$this->load_calendar_datetime();
						}

						$this->CDT->change_date(
							$edit['end_year'],
							$edit['end_month'],
							$edit['end_day']
						);

						$this->CDT->add_day();

						$E->setProperty(
							"dtend" ,
							array(
								'year' 	=> $this->CDT->year,
								'month' => $this->CDT->month,
								'day'	=> $this->CDT->day
							),
							array(
								'VALUE' => 'DATE'
							)
						);
					}
					else
					{
						$E->setProperty(
							'dtstart',
							$edit_date['year'],
							$edit_date['month'],
							$edit_date['day'],
							$edit_date['hour'],
							$edit_date['min'],
							00
						);

						$E->setProperty(
							'dtend',
							$edit['end_year'],
							$edit['end_month'],
							$edit['end_day'] ,
							substr($edit['end_time'], 0, 2),
							substr($edit['end_time'], 2, 2),
							00
						);
					}

					$E->setProperty( "RECURRENCE-ID", $edit_date);
					$E->setProperty( "uid", $this->make_uid() . '@' . $host);

					$ICAL->setComponent($E);
				}

				//cleanup
				$E->deleteProperty("RECURRENCE-ID");

				$E->setProperty('dtstart', $start_year, $start_month, $start_day, $start_hour, $start_minute, 00);
				$E->setProperty('dtend', $end_year, $end_month, $end_day, $end_hour, $end_minute, 00);
			}

			// these random ass add-in dates are non-standard to most cal creation
			// and need to be treated seperately as lumping don't work, dog
			if ( ! empty($extras))
			{
				foreach ($extras as $extra)
				{

					//if this is all day we need to add the dates as params to the dstart and end items
					if ($this->_is_all_day(
						substr($extra['start_time'], 0, 2),
						substr($extra['start_time'], 2, 2),
						substr($extra['end_time'], 0, 2),
						substr($extra['end_time'], 2, 2)
					  ))
					{
						$E->setProperty(
							"dtstart" ,
							array(
								'year' 	=> $extra['start_year'],
								'month' => $extra['start_month'],
								'day'	=> $extra['start_day']
							),
							array(
								'VALUE' => 'DATE'
							)
						);

						//--------------------------------------------
						//	we need CDT so we can add a day
						//	gcal, and ical are ok with this being the same day
						//	stupid damned outlook barfs, hence the +1
						//	the +1 doesnt affect g/ical
						//--------------------------------------------

						if ( ! isset($this->CDT) OR ! is_object($this->CDT) )
						{
							$this->load_calendar_datetime();
						}

						$this->CDT->change_date(
							$extra['end_year'],
							$extra['end_month'],
							$extra['end_day']
						);

						$this->CDT->add_day();

						$E->setProperty(
							"dtend" ,
							array(
								'year' 	=> $this->CDT->year,
								'month' => $this->CDT->month,
								'day'	=> $this->CDT->day
							),
							array(
								'VALUE' => 'DATE'
							)
						);
					}
					else
					{
						$E->setProperty(
							'dtstart',
							$extra['start_year'],
							$extra['start_month'],
							$extra['start_day'] ,
							substr($extra['start_time'], 0, 2),
							substr($extra['start_time'], 2, 2),
							00
						);

						$E->setProperty(
							'dtend',
							$extra['end_year'],
							$extra['end_month'],
							$extra['end_day'] ,
							substr($extra['end_time'], 0, 2),
							substr($extra['end_time'], 2, 2),
							00
						);
					}

					$E->setProperty( "uid", $this->make_uid() . '@' . $host);
					$ICAL->setComponent($E);
				}

				//clean in case we need to add more later
				$E->setProperty('dtstart', $start_year, $start_month, $start_day, $start_hour, $start_minute, 00);
				$E->setProperty('dtend', $end_year, $end_month, $end_day, $end_hour, $end_minute, 00);
			}
		}
		//return $ICAL->createCalendar();
		return $ICAL->returnCalendar();
	}
	/* END icalendar() */

	// --------------------------------------------------------------------

	/**
	 * Update imported event data
	 *
	 * @return
	 */

	public function ics_update()
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$params = array(
			array(	'name' => 'category',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'site_id',
					'required' => FALSE,
					'type' => 'integer',
					'min_value' => 1,
					'multi' => TRUE,
					'default' => $this->data->get_site_id()
					),
			array(	'name' => 'calendar_id',
					'required' => FALSE,
					'type' => 'integer',
					'min_value' => 1,
					'multi' => TRUE
					),
			array(	'name' => 'calendar_name',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE
					),
			array(	'name' => 'time_range_start',
					'required' => FALSE,
					'type' => 'time'
					),
			array(	'name' => 'time_range_end',
					'required' => FALSE,
					'type' => 'time'
					),
			array(	'name' => 'minute_interval',
					'required' => FALSE,
					'type' => 'integer',
					'default' => 60
					),
			array(	'name' => 'status',
					'required' => FALSE,
					'type' => 'string',
					'multi' => TRUE,
					'default' => 'open'
					)
			);

		//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		// -------------------------------------
		//  If we are not currently in the time range, scram
		// -------------------------------------

		$time = date('Hi', time());

		if ($this->P->value('time_range_start', 'time') !== FALSE AND
			$this->P->value('time_range_start', 'time') > $time)
		{
			//ee()->TMPL->log_item('Calendar: It is not time to update icalendar data yet.');
			return;
		}
		else if ($this->P->value('time_range_end', 'time') !== FALSE AND
				$this->P->value('time_range_end', 'time') < $time)
		{
			//ee()->TMPL->log_item('Calendar: The time to update icalendar data has passed.');
			return;
		}

		if ($this->P->value('calendar_id') == '')
		{
			// -------------------------------------
			//  For those who prefer names over numbers
			// -------------------------------------

			if ($this->P->value('calendar_name') != '')
			{
				$ids = $this->data->get_calendar_id_from_name($this->P->value('calendar_name'));
			}

			// -------------------------------------
			//  Just a site ID -- get all calendars
			// -------------------------------------

			else
			{
				$ids = $this->data->get_calendars_by_site_id($this->P->value('site_id'));
			}
		}
		else
		{
			$ids = explode('|', $this->P->value('calendar_id'));
		}

		// -------------------------------------
		//  Only look at calendars that are due for an update
		// -------------------------------------

		if ( ! empty($ids) AND $this->P->value('minute_interval') !== FALSE)
		{
			$ids = $this->data->get_calendars_needing_update(
				$ids,
				$this->P->value('minute_interval')
			);
		}

		// -------------------------------------
		//  Leave if empty
		// -------------------------------------

		if (empty($ids))
		{
			//ee()->TMPL->log_item('Calendar: Nobody is due for an update');
			return;
		}

		// -------------------------------------
		//  Go get those data!
		// -------------------------------------

		$this->actions();

//ee()->TMPL->log_item('Calendar: Fetching data');

		foreach ($ids as $id)
		{
			$this->actions->import_ics_data($id);
		}
	}
	/* END ics_update() */

	// --------------------------------------------------------------------

	/**
	 * Output the JS for the date picker
	 *
	 * @return	string
	 */

	public function datepicker_js()
	{
		$this->actions();
		return $this->actions->datepicker_js( ! $this->check_no(ee()->TMPL->fetch_param('include_jqui')));
	}
	/* END datepicker_js() */

	// --------------------------------------------------------------------

	/**
	 * Output the CSS for the date picker
	 *
	 * @return	string
	 */

	public function datepicker_css()
	{
		$this->actions();
		return $this->actions->datepicker_css( ! $this->check_no(ee()->TMPL->fetch_param('include_jqui')));
	}
	/* END datepicker_css() */

	// --------------------------------------------------------------------

	/**
	 * Output a list of years
	 *
	 * @return	string
	 */

	public function year_list()
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$params = array(
			array(	'name' => 'date_range_start',
					'required' => FALSE,
					'type' => 'date',
					'default' => 'year-01-01'
					),
			array(	'name' => 'date_range_end',
					'required' => FALSE,
					'type' => 'date'
					),
			array(	'name' => 'limit',
					'required' => FALSE,
					'type' => 'integer',
					'default' => 100
					)
			);

//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		$today = $this->CDT->date_array();

		$this->CDT->change_ymd($this->P->value('date_range_start', 'ymd'));
		$this->CDT->set_default($this->CDT->datetime_array());
		if ($this->P->value('date_range_end') === FALSE)
		{
			$this->P->set('date_range_end', $this->CDT->add_year($this->P->value('limit')));
			$this->CDT->reset();
		}
		else
		{
			$this->P->set('limit', 9999);
		}

		$dir = ($this->P->value('date_range_end', 'ymd') > $this->P->value('date_range_start', 'ymd')) ? 1 : -1;
		$output = '';
		$count = 0;

//ee()->TMPL->log_item('Calendar: Looping');

		do {
			$vars['conditional']	= array	(	'is_current_year'		=>	($this->CDT->year == $today['year']) ? TRUE : FALSE,
												'is_not_current_year'	=>	($this->CDT->year == $today['year']) ? FALSE : TRUE
											);
			$vars['single']	= array('year'	=> $this->CDT->year);
			$vars['date']	= array(
				'year'		=> $this->CDT->datetime_array(),
				'date'		=> $this->CDT->datetime_array(),
			);
			$output .= $this->swap_vars($vars, ee()->TMPL->tagdata);
			$this->CDT->add_year($dir);
			$count++;
		} while ($count < $this->P->value('limit') AND $this->CDT->year < $this->P->value('date_range_end', 'year'));

		return $output;
	}
	/* END year_list() */

	// --------------------------------------------------------------------

	/**
	 * Output a list of months
	 *
	 * @return	string
	 */

	public function month_list()
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$params = array(
			array(
				'name'		=> 'date_range_start',
				'required'	=> FALSE,
				'type'		=> 'date',
				'default'	=> 'year-01-01'
			),
			array(
				'name'		=> 'date_range_end',
				'required'	=> FALSE,
				'type'		=> 'date'
			),
			array(
				'name'		=> 'limit',
				'required'	=> FALSE,
				'type'		=> 'integer',
				'default'	=> 12
			)
		);

//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		$today = $this->CDT->date_array();

		$this->CDT->change_ymd($this->P->value('date_range_start', 'ymd'));
		$this->CDT->set_default($this->CDT->datetime_array());

		if ($this->P->value('date_range_end') === FALSE)
		{
			$this->P->set(
				'date_range_end',
				$this->CDT->add_month($this->P->value('limit'))
			);

			$this->CDT->reset();
		}
		else
		{
			$this->P->set('limit', 9999);
		}

		$dir = (
			$this->P->value('date_range_end', 'ymd') >
				$this->P->value('date_range_start', 'ymd')
		) ? 1 : -1;

		$output = '';
		$count = 0;

//ee()->TMPL->log_item('Calendar: Looping');

		do
		{
			$vars['conditional']	= array(
				'is_current_month'		=>	(
					$this->CDT->year == $today['year'] AND
					$this->CDT->month == $today['month']
				),
				'is_not_current_month'	=>	(
					$this->CDT->year == $today['year'] AND
					$this->CDT->month == $today['month']
				) ? FALSE : TRUE,
				'is_current_year'		=>	(
					$this->CDT->year == $today['year']
				) ? TRUE : FALSE,
				'is_not_current_year'	=>	(
					$this->CDT->year == $today['year']
				) ? FALSE : TRUE
			);

			$vars['single']	= array(
				'year'	=> $this->CDT->year,
				'month'	=> $this->CDT->month
			);

			$vars['date']	= array(
				'month'			=> $this->CDT->datetime_array(),
				'date'			=> $this->CDT->datetime_array(),
			);

			$output .= $this->swap_vars($vars, ee()->TMPL->tagdata);
			$this->CDT->add_month($dir);
			$count++;
		}
		while (
			$count < $this->P->value('limit') AND
			$this->CDT->ymd < $this->P->value('date_range_end', 'ymd')
		);

		return $output;
	}
	// END month_list()


	// --------------------------------------------------------------------

	/**
	 * Date
	 *
	 * @return	string
	 */

	public function date()
	{
		// -------------------------------------
		//  Load 'em up
		// -------------------------------------

		$this->load_calendar_datetime();
		$this->load_calendar_parameters();

		// -------------------------------------
		//  Prep the parameters
		// -------------------------------------

		$params = array(
			array(
				'name'		=> 'base_date',
				'required'	=> FALSE,
				'type'		=> 'date',
				'default'	=> 'today @ now'
			)
		);

		//ee()->TMPL->log_item('Calendar: Processing parameters');

		$this->add_parameters($params);

		// -------------------------------------
		//  Set different default depending on whether or not base_date is set
		// -------------------------------------

		if ($this->P->value('base_date') !== FALSE)
		{
			$param = array(
				'name'		=> 'output_date',
				'required'	=> FALSE,
				'type'		=> 'string',
				'default'	=>	$this->P->value('base_date', 'ymd') . ' @ ' .
								$this->P->value('base_date', 'time')
			);

			$this->P->add_parameter('output_date', $param);

			$this->CDT->set_default(
				$this->P->value('base_date'),
				$this->P->value('base_date')
			);

			if ($this->P->value('output_date') !== FALSE)
			{
				if (strpos($this->P->value('output_date'), ' @ ') === FALSE)
				{
					$this->P->set(
						'output_date',
						$this->P->value('output_date') . ' @ ' .
						$this->P->value('base_date', 'time')
					);
				}

				$this->P->set(
					'output_date',
					$this->actions->parse_text_date(
						$this->P->value('output_date'),
						$this->CDT
					)
				);
			}
			else
			{
				$this->P->set('output_date', $this->P->value('base_date'));
			}
		}
		else
		{
			$param = array(
				'name'		=> 'output_date',
				'required'	=> FALSE,
				'type'		=> 'date',
				'default'	=> 'today @ now'
			);

			$this->P->add_parameter('output_date', $param);
		}

//ee()->TMPL->log_item('Calendar: Output time');

		$vars	= array(
			'date'	=> array(
				'date'		=> $this->P->value('output_date'),
				'base_date'	=> $this->P->value('base_date')
			)
		);

		return $this->swap_vars($vars, ee()->TMPL->tagdata);
	}
	// END date()


	// --------------------------------------------------------------------

	/**
	 * The workhorse for building a calendar
	 *
	 * @access	private
	 * @return	string
	 */

	protected function build_calendar()
	{
		//ee()->TMPL->log_item('Calendar: Building calendar output');

		$disable	= array(
			'categories',
			'category_fields',
			'custom_fields',
			'member_data',
			'pagination',
			'trackbacks'
		);

		$this->CDT->reset();
		$today_ymd	= $this->CDT->ymd;

		// -------------------------------------
		//  Set dynamic="off", lest Channel get uppity and try
		//  to think that it's in charge here.
		// -------------------------------------

		//default off.
		if ( $this->check_yes( ee()->TMPL->fetch_param('dynamic') ) )
		{
			ee()->TMPL->tagparams['dynamic'] 	= 'yes';
		}
		else
		{
			ee()->TMPL->tagparams['dynamic'] 	= 'no';
		}

		if (isset(ee()->TMPL->tagparams['category']))
		{
			$this->convert_category_titles();
		}

		// -------------------------------------
		//  Collect important bits of tagdata
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Collecting tagdata');

		$output_at	= '';
		$tagdata	= ee()->TMPL->tagdata;
		$each_year	= $each_month = $each_week = $each_day = $each_hour = $each_event = '';
		$hash_event	= 'd38bf16a9a74c63fa5eb6d1ac082d539'."\n";
		$hash_hour	= 'fe16402ccfad7e120a7ca3a31df3a019'."\n";
		$hash_day	= '2aa2a1a0724182b1d876232c137a6d4f'."\n";
		$hash_week	= 'b657210371b3e2a6f955ef6a404689de'."\n";
		$hash_month	= 'd03207661c36a3bfd43b9dd239e41676'."\n";
		$hash_year	= '97a92770ab082652cf662bdacc311dff'."\n";

		//--------------------------------------------
		//	remove pagination before we start
		//--------------------------------------------

		//has tags?
		if (preg_match(
				"/" . LD . "calendar_paginate" . RD .
					"(.+?)" .
				LD . preg_quote(T_SLASH, '/') . "calendar_paginate" . RD . "/s",
				$tagdata,
				$match
			))
		{
			$this->paginate_tagpair_data	= $match[0];
			$tagdata 						= str_replace( $match[0], '', $tagdata );
		}
		//prefix comes first
		else if (preg_match(
				"/" . LD . "paginate" . RD .
					"(.+?)" .
				LD . preg_quote(T_SLASH, '/') . "paginate" . RD . "/s",
				$tagdata,
				$match
			))
		{
			$this->paginate_tagpair_data	= $match[0];
			$tagdata 						= str_replace( $match[0], '', $tagdata );
		}

		// -------------------------------------
		//  Replace days of the week first, cuz they're easy
		// -------------------------------------


		if (isset(ee()->TMPL->var_pair['display_each_day_of_week']))
		{
			preg_match(
				'/' . LD . 'display_each_day_of_week' . RD .
					'(.*?)' .
				LD . preg_quote(T_SLASH, '/') . 'display_each_day_of_week' . RD . '/s',
				$tagdata,
				$match
			);

			if (isset($match[1]))
			{
				$dow_output		= '';
				$vars			= array();
				$current_dow	= $this->CDT->day_of_week;

				$this->CDT->change_ymd($this->P->value('date_range_start', 'ymd'));

				if ($this->CDT->day_of_week != $this->first_day_of_week)
				{
					$this->CDT->add_day(
						$this->first_day_of_week - $this->CDT->day_of_week
					);
				}

				for ($i = 0; $i < 7; $i++)
				{
					if ($i > 0)
					{
						$this->CDT->add_day();
					}

					$vars['conditional'] = array(
						'day_of_week_is_weekend'	=> (
							$this->CDT->day_of_week == 0 OR
							$this->CDT->day_of_week == 6
						),
						'day_of_week_is_current'	=> (
							$this->CDT->day_of_week == $current_dow
						)
					);

					$vars['single'] = array(
						'day_of_week'			=> $this->cdt_format_date_string($this->CDT->datetime_array(), 'l'),
						'day_of_week_one'		=> $this->cdt_format_date_string($this->CDT->datetime_array(), 'b'),
						'day_of_week_short'		=> $this->cdt_format_date_string($this->CDT->datetime_array(), 'D'),
						'day_of_week_N'			=> $this->cdt_format_date_string($this->CDT->datetime_array(), 'N'),
						'day_of_week_number'	=> $this->cdt_format_date_string($this->CDT->datetime_array(), 'w')
					);

					$dow_output .= $this->swap_vars($vars, $match[1]);
				}
				$tagdata = str_replace($match[0], $dow_output, $tagdata);
			}
		}

		$tagdata = trim($tagdata)."\n";

		// -------------------------------------
		//  Now the rest
		// -------------------------------------

		if (isset(ee()->TMPL->var_pair['events']))
		{
			preg_match(
				'/'.LD.'events'.RD.'(.*?)'.LD.preg_quote(T_SLASH, '/').'events'.RD.'/s',
				$tagdata,
				$match
			);

			if (isset($match[1]))
			{
				$each_event 		= trim($match[1])."\n";
				$tagdata 			= str_replace($match[0], $hash_event, $tagdata);
				ee()->TMPL->tagdata = $each_event;
				$output_at 			= 'event';
			}
		}

		if (isset(ee()->TMPL->var_pair['display_each_hour']))
		{
			preg_match(
				'/'.LD.'display_each_hour'.RD.'(.*?)'.LD.preg_quote(T_SLASH, '/').'display_each_hour'.RD.'/s',
				$tagdata,
				$match
			);

			if (isset($match[1]))
			{
				$each_hour 			= trim($match[1])."\n";
				$tagdata 			= str_replace($match[0], $hash_hour, $tagdata);
				$output_at 			= 'hour';
			}
		}

		if (isset(ee()->TMPL->var_pair['display_each_day']))
		{
			preg_match(
				'/'.LD.'display_each_day'.RD.'(.*?)'.LD.preg_quote(T_SLASH, '/').'display_each_day'.RD.'/s',
				$tagdata,
				$match
			);

			if (isset($match[1]))
			{
				$each_day 			= trim($match[1])."\n";
				$tagdata 			= str_replace($match[0], $hash_day, $tagdata);
				$output_at 			= 'day';
			}
		}

		if (isset(ee()->TMPL->var_pair['display_each_week']))
		{
			preg_match(
				'/'.LD.'display_each_week'.RD.'(.*?)'.LD.preg_quote(T_SLASH, '/').'display_each_week'.RD.'/s',
				$tagdata,
				$match
			);

			if (isset($match[1]))
			{
				$each_week 			= trim($match[1])."\n";
				$tagdata 			= str_replace($match[0], $hash_week, $tagdata);
				$output_at 			= 'week';
			}
		}

		if (isset(ee()->TMPL->var_pair['display_each_month']))
		{
			preg_match(
				'/'.LD.'display_each_month'.RD.'(.*?)'.LD.preg_quote(T_SLASH, '/').'display_each_month'.RD.'/s',
				$tagdata,
				$match
			);

			if (isset($match[1]))
			{
				$each_month 		= trim($match[1])."\n";
				$tagdata 			= str_replace($match[0], $hash_month, $tagdata);
				$output_at 			= 'month';
			}
		}

		if (isset(ee()->TMPL->var_pair['display_each_year']))
		{
			preg_match(
				'/'.LD.'display_each_year'.RD.'(.*?)'.LD.preg_quote(T_SLASH, '/').'display_each_year'.RD.'/s',
				$tagdata,
				$match
			);

			if (isset($match[1]))
			{
				$each_year 			= trim($match[1])."\n";
				$tagdata 			= str_replace($match[0], $hash_year, $tagdata);
				$output_at 			= 'year';
			}
		}

		// -------------------------------------
		//  If there aren't any display_each_X var pairs, default to event
		// -------------------------------------

		if ($output_at == '')
		{
			$each_event 	= $tagdata;
			$tagdata 		= $hash_event;
			$output_at 		= 'event';
		}

		// -------------------------------------
		//  Set the default date to the start of our range
		// -------------------------------------

		$start_blank = (
			$this->P->value('date_range_start') === FALSE AND
			$this->P->value('date_range_end') === FALSE
		);

		$start	= $this->CDT->change_datetime(
			$this->P->value('date_range_start', 'year'),
			$this->P->value('date_range_start', 'month'),
			$this->P->value('date_range_start', 'day'),
			$this->P->value('date_range_start', 'hour'),
			$this->P->value('date_range_start', 'minute')
		);

		$end	= $this->CDT->change_datetime(
			$this->P->value('date_range_end', 'year'),
			$this->P->value('date_range_end', 'month'),
			$this->P->value('date_range_end', 'day'),
			($start_blank ? '23' : $this->P->value('date_range_end', 'hour')),
			($start_blank ? '59' : $this->P->value('date_range_end', 'minute'))
		);

		$current_period_start	= $start;
		$current_period_end		= $end;

		$this->CDT->set_default($start);
		$this->CDT->reset();

		// -------------------------------------
		//  If we are "padding" short weeks, modify our dates
		// -------------------------------------

		if ($this->P->value('pad_short_weeks') === TRUE)
		{
			// -------------------------------------
			//  Adjust the start date backward to the first day of the week, if necessary
			// -------------------------------------

			$old_end = $end;

			if ($start['day_of_week'] != $this->first_day_of_week)
			{
				$offset = ($start['day_of_week'] > $this->first_day_of_week) ?
					$start['day_of_week'] - $this->first_day_of_week :
					7 - ($this->first_day_of_week - $start['day_of_week']);

				$start 	= $this->CDT->add_day(-$offset);
				$this->CDT->reset();
			}

			// -------------------------------------
			//  Adjust the end date forward to the last day of the week, if necessary
			// -------------------------------------

			$last_dow = ($this->first_day_of_week > 0) ? $this->first_day_of_week - 1 : 6;

			if ($end['day_of_week'] != $last_dow)
			{
				$this->CDT->change_ymd($end['ymd']);
				$offset = ($end['day_of_week'] > $last_dow) ?
					7 - ($end['day_of_week'] - $last_dow) :
					$last_dow - $end['day_of_week'];

				$end 	= $this->CDT->add_day($offset);
				$this->CDT->reset();
			}

			$end['time']	= $old_end['time'];
			$end['hour']	= $old_end['hour'];
			$end['minute']	= $old_end['minute'];

			$this->CDT->set_default($start);
			$this->P->set('date_range_start', $start);
			$this->P->set('date_range_end', $end);
		}

		//ee()->TMPL->log_item('Calendar: Date range start: '. $this->P->value('date_range_start', 'ymd'));

		//ee()->TMPL->log_item('Calendar: Date range end: '. $this->P->value('date_range_end', 'ymd'));

		// -------------------------------------
		//  Let's go fetch some events
		// -------------------------------------

		/*$category = FALSE;

		if (isset(ee()->TMPL) AND
			 is_object(ee()->TMPL) AND
			 ee()->TMPL->fetch_param('category') !== FALSE AND
			 ee()->TMPL->fetch_param('category') != ''
		)
		{
			$category = ee()->TMPL->fetch_param('category');

			unset(ee()->TMPL->tagparams['category']);
		}*/

		$ids 			= $this->data->fetch_event_ids($this->P /*, $category*/);

		//ee()->TMPL->log_item('Calendar:build_calendar() Fetching events. ' . count( $ids ) . ' events were found.');

		$entry_data 	= array();
		$events 		= array();

		// -------------------------------------
		//  No events? You really need to work on your social calendar...
		// -------------------------------------

		if (empty($ids))
		{
			// -------------------------------------
			//  If they used a no_results tag, let 'em have it
			// -------------------------------------

			if (ee()->TMPL->no_results != '')
			{
				//ee()->TMPL->log_item('Calendar:build_calendar() No results, going home');
				return $this->no_results();
			}

			//ee()->TMPL->log_item('Calendar:build_calendar() No results, but staying around for the show');
		}
		else
		{
			// -------------------------------------
			//  We only care about this stuff if:
			// 	* there's an {event}{/event} tag pair
			// 	* thar be one or more xxx_has_events variables
			// -------------------------------------

			if ($each_event != '' OR strpos(ee()->TMPL->tagdata, '_event_total') !== FALSE)
			{
				while (TRUE)
				{
					//ee()->TMPL->log_item('Calendar:build_calendar() Firing up the ol Channel Module to try and process ' . count( $ids ) . ' events.');

					// -------------------------------------
					//  Fetch occurrence info
					// -------------------------------------

					$occurrence_ids = $this->data->fetch_occurrence_entry_ids($ids);

					//ee()->TMPL->log_item('Calendar:build_calendar() ' . count( $occurrence_ids ) . ' occurrences were found.');

					// -------------------------------------
					//  If the entry_id of the occurrence doesn't match the entry_id
					//  of the entry, we need to fetch the occurrence data separately
					// -------------------------------------

					foreach ($occurrence_ids as $id => $data)
					{
						foreach ($data as $oid => $o_entry_id)
						{
							if ($id != $o_entry_id)
							{
								$ids[$o_entry_id] = $id; //$o_entry_id;
							}
						}
					}

					// -------------------------------------
					//  Prepare tagdata for Calendar-specific variable pairs, which
					//  we will process later.
					// -------------------------------------

					ee()->TMPL->var_single['entry_id'] = 'entry_id';

					// -------------------------------------
					//  Prepare tagdata for Calendar-specific date variables, which
					//  we will process later.
					// -------------------------------------

					$var_dates = array(
						'event_start_date' 	=> FALSE,
						'event_start_time' 	=> FALSE,
						'event_end_date' 	=> FALSE,
						'event_end_time' 	=> FALSE
					);

					foreach (ee()->TMPL->var_single as $k => $v)
					{
						if (($pos = strpos($k, ' format')) !== FALSE)
						{
							$name = substr($k, 0, $pos);

							if (array_key_exists($name, $var_dates))
							{
								$var_dates[$name][$k] 		= $v;
								ee()->TMPL->var_single[$k] 	= $k;
							}
						}
					}

					//	----------------------------------------
					//	Invoke Channel class
					//	----------------------------------------

					if ( ! class_exists('Channel') )
					{
						require PATH_MOD.'/channel/mod.channel.php';
					}

					$channel = new Channel();

					//need to remove limit here so huge amounts of events work
					$channel->limit = 1000000;

					// --------------------------------------------
					//  Invoke Pagination for EE 2.4 and Above
					// --------------------------------------------

					$channel = $this->add_pag_to_channel($channel);

					// -------------------------------------
					//  Prepare parameters
					// -------------------------------------

					ee()->TMPL->tagparams['entry_id'] = implode('|', array_keys($ids));

					if ($this->P->value('enable') != FALSE)
					{
						if (is_array($this->P->value('enable')))
						{
							ee()->TMPL->tagparams['disable'] = implode('|', array_diff($disable, $this->P->value('enable')));
						}
						else
						{
							ee()->TMPL->tagparams['disable'] = implode('|', array_diff($disable, array($this->P->value('enable'))));
						}
					}
					else
					{
						ee()->TMPL->tagparams['disable'] = implode('|', $disable);
					}

					// -------------------------------------
					//  Pre-process related data
					// -------------------------------------

					//ee()->TMPL->tagdata = ee()->TMPL->assign_relationship_data( ee()->TMPL->tagdata );

					if (version_compare($this->ee_version, '2.6.0', '<'))
					{
						ee()->TMPL->tagdata = ee()->TMPL->assign_relationship_data(
							$each_event
						);
					}
					ee()->TMPL->var_single 	= array_merge( ee()->TMPL->var_single, ee()->TMPL->related_markers );

					// -------------------------------------
					//  Execute needed methods
					// -------------------------------------

					$channel->fetch_custom_channel_fields();

					$channel->fetch_custom_member_fields();

					// --------------------------------------------
					//  Pagination Tags Parsed Out
					// --------------------------------------------

					$channel = $this->fetch_pagination_data($channel);

					// -------------------------------------
					//  Querification
					// -------------------------------------

					//for some reason without this in EE 2.8.x
					//pagination has some sort of dynamic detection
					//that it didn't in EE 2.7 and below and hoses our
					//pagination setup because we are doing it
					//manually later as this is just data gathering
					//for events.
					if (
						version_compare($this->ee_version, '2.8.0', '>=') &&
						isset($channel->pagination)
					)
					{
						$channel->pagination->paginate = false;
					}

					$channel->build_sql_query();

					if ($channel->sql == '')
					{
						//ee()->TMPL->log_item('Calendar: Channel query empty');

						if (ee()->TMPL->no_results != '')
						{
							return $this->no_results();
						}

						break;
					}

					$channel->query = ee()->db->query($channel->sql);

					if ($channel->query->num_rows == 0)
					{
						//ee()->TMPL->log_item('Calendar: Channel Module returned no results');

						if (ee()->TMPL->no_results != '')
						{
							return $this->no_results();
						}

						break;
					}
					else
					{
						//ee()->TMPL->log_item('Calendar:build_calendar() Channel module found ' . $channel->query->num_rows . ' results.');
					}

					$channel->query->result	= $channel->query->result_array();

					// -------------------------------------
					//  Trim IDs and build events
					// -------------------------------------

					$new_ids = array();

					foreach ($channel->query->result as $k => $row)
					{
						$new_ids[$row['entry_id']] = $ids[$row['entry_id']];
					}

					$event_data = $this->data->fetch_all_event_data($new_ids);

					// -------------------------------------
					//  Turn these IDs into events
					// -------------------------------------

					$events = array();

					//ee()->TMPL->log_item('Calendar:build_calendar() Beginning event creation process.');

					if ( ! class_exists('Calendar_event'))
					{
						require_once CALENDAR_PATH.'calendar.event.php';
					}

					$calendars = array();

					foreach ($event_data as $k => $edata)
					{
						$temp = new Calendar_event(
							$edata,
							$this->P->params['date_range_start']['value'],
							$this->P->params['date_range_end']['value']
						);

						if ( ! empty($temp->dates))
						{
							$temp->prepare_for_output();
							$events[$edata['entry_id']] = $temp;
							$calendars[$events[$edata['entry_id']]->default_data['calendar_id']] = array();
						}
					}

					//ee()->TMPL->log_item('Calendar:build_calendar() Event creation resulted in the creation of ' . count( $events ) . ' events.');

					//ee()->TMPL->log_item('Calendar:build_calendar() Event creation process finished.');

					// -------------------------------------
					//  Leaving so soon?
					//  There's no point in continuing if we're just interested
					//  in whether or not there are events on this day.
					// -------------------------------------

					if ($each_event == '')
					{
						foreach (array_keys($events) as $id)
						{
							$entry_data[$id] = $id;
						}

						//ee()->TMPL->log_item('Calendar:build_calendar() Skipping further channel module processing because $each_event variable was empty string. ' . count( $entry_data ) . ' events were found and logged into the $entry_data array.');
						break;
					}

					// -------------------------------------
					//  Nor should we stay around if there's nothing to process
					// -------------------------------------

					elseif (empty($calendars))
					{
						//ee()->TMPL->log_item('Calendar:build_calendar() Skipping further channel module processing because there were no calendars connected to the events found.');
						break;
					}

					// -------------------------------------
					//  Fetch information about the calendars
					// -------------------------------------

					$calendars = $this->data->fetch_calendar_data_by_id(array_keys($calendars));

					// -------------------------------------
					//  Prepare the tagdata that will be parsed by the channel module
					// -------------------------------------

					$blargle = '3138ad2081984be5dea40e593fd61f87';
					$bleegle = '6f8de301e6e6f2cd80ad99aa3a765b31';
					//ee()->TMPL->tagdata = $blargle . '[' . LD . "entry_id" . RD . ']' . $each_event . $bleegle;
					ee()->TMPL->tagdata = $blargle .
						'[' . LD . "entry_id" . RD . ']' .
						ee()->TMPL->tagdata .
						$bleegle;

					// -------------------------------------
					//  Prep variable aliases
					// -------------------------------------

					$variables = array(
						'title'			=> 'event_title',
						'url_title'		=> 'event_url_title',
						'entry_id'		=> 'event_id',
						'author_id'		=> 'event_author_id',
						'author'		=> 'event_author',
						'status'		=> 'event_status'
					);

					//custom variables with the letters 'url' are borked in
					//EE 2.6. Bug reported, but this should fix.
					//https://support.ellislab.com/bugs/detail/19337
					if (version_compare($this->ee_version, '2.6.0', '>='))
					{
						$variables['url_title'] = 'event_borked_title';

						ee()->TMPL->var_single['event_borked_title'] = 'event_borked_title';

						unset(ee()->TMPL->var_single['event_url_title']);

						ee()->TMPL->tagdata = str_replace(
							array(
								LD . 'event_url_title' . RD,
								'"event_url_title"',
								"'event_url_title'"
							),
							array(
								LD . 'event_borked_title' . RD,
								'"event_borked_title"',
								"'event_borked_title'"

							),
							ee()->TMPL->tagdata
						);

						ee()->TMPL->var_single['event_calendar_borked_title'] = 'event_calendar_borked_title';

						unset(ee()->TMPL->var_single['event_calendar_url_title']);

						ee()->TMPL->tagdata = str_replace(
							array(
								LD . 'event_calendar_url_title' . RD,
								'"event_calendar_url_title"',
								"'event_calendar_url_title'"
							),
							array(
								LD . 'event_calendar_borked_title' . RD,
								'"event_calendar_borked_title"',
								"'event_calendar_borked_title'"

							),
							ee()->TMPL->tagdata
						);

					}

					// --------------------------------------------
					//  Typography
					// --------------------------------------------

					ee()->load->library('typography');
					ee()->typography->initialize();
					ee()->typography->convert_curly = FALSE;

					$channel->fetch_categories();

					// -------------------------------------
					//  Add variables to the query result
					// -------------------------------------

					foreach ($channel->query->result as $k => $row)
					{
						$entry_id = $row['entry_id'];

						$channel->query->result[$k]['author'] = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];

						//ee()->TMPL->log_item('Calendar:build_calendar() We\'re in the channel query result loop. Entry id is ' . $entry_id );

						// -------------------------------------
						//  Skip this result if the event data doesn't exist
						// -------------------------------------

						if (! isset($events[$ids[$entry_id]]))
						{
							unset($channel->query->result[$k]);
							continue;
						}

						$channel->query->result[$k]['edited_occurrence'] 	= FALSE;
						$channel->query->result[$k]['event_parent_id']		= ($ids[$entry_id] == $entry_id) ? 0 : $ids[$entry_id];

						// -------------------------------------
						//  If this entry_id is not in the $events array, this is an edited occurrence
						// -------------------------------------

						if (! isset($events[$entry_id]))
						{
							// -------------------------------------
							//  Add this info to the $events array
							// -------------------------------------

							$events[$entry_id] = clone $events[$ids[$entry_id]];

							$channel->query->result[$k]['edited_occurrence'] = TRUE;

							// -------------------------------------
							//  Correct the info
							// -------------------------------------

							foreach ($events[$entry_id]->occurrences as $ymd => $times)
							{
								foreach ($times as $time => $data)
								{
									if ($data['entry_id'] != $entry_id)
									{
										unset($events[$entry_id]->occurrences[$ymd][$time], $events[$entry_id]->dates[$ymd][$time]);
									}
									else
									{
										unset($events[$data['event_id']]->occurrences[$ymd][$time], $events[$data['event_id']]->dates[$ymd][$time]);
									}
								}
							}

							// NOTE: Requires PHP >= 5.1.0
							$events[$entry_id]->dates = array_intersect_key($events[$entry_id]->dates, $events[$entry_id]->occurrences);
						}

						// -------------------------------------
						//  Alias
						// -------------------------------------

						foreach ($variables as $old => $new)
						{
							if ($old == 'title')
							{

								$channel->query->result[$k][$old]	= ee()->typography->parse_type(
									$channel->query->result[$k][$old],
									array(
										'text_format' 	=> 'lite',
										'html_format' 	=> 'none',
										'auto_links' 	=> 'n',
										'allow_img_url' => 'no'
									)
								);

								$channel->query->result[$k][$new]	= $channel->query->result[$k][$old];
							}
							else
							{
								$channel->query->result[$k][$new]	= $channel->query->result[$k][$old];
							}
						}

						// -------------------------------------
						//  Calendar variables
						// -------------------------------------

						foreach ($calendars[$events[$entry_id]->default_data['calendar_id']] as $key => $val)
						{
							$channel->query->result[$k][$key] = $val;

							if ($key == 'calendar_url_title' AND
								version_compare($this->ee_version, '2.6.0', '>='))
							{
								$channel->query->result[$k]['event_calendar_borked_title'] = $val;
							}
							else
							{
								$channel->query->result[$k]['event_'.$key] = $val;
							}

						}

					}


					//	----------------------------------------
					//	Redeclare
					//	----------------------------------------
					//	We will reassign the $channel->query->result with our
					//	reordered array of values. Thank you PHP for being so fast with array loops.
					//	----------------------------------------

					$super_temp_fake = $channel->query->result_array = $channel->query->result;

					$channel->fetch_categories();

					// -------------------------------------
					//  Handle {title} and {event_title} differently
					// -------------------------------------

					ee()->TMPL->tagdata	= str_replace(
						LD . 'title' . RD,
						'6c21bdf1bfdab13bc8df8fcfeb2763a6' . LD . 'entry_id' . RD,
						ee()->TMPL->tagdata
					);

					ee()->TMPL->tagdata	= str_replace(
						LD . 'event_title' . RD,
						'6c21bdf1bfdab13bc8df8fcfeb2763a6' . LD . 'entry_id' . RD,
						ee()->TMPL->tagdata
					);

					// -------------------------------------
					//  Remove "ignore" prefixes
					// -------------------------------------

					ee()->TMPL->tagdata = str_replace(
						'calendar_ignore_',
						'',
						ee()->TMPL->tagdata
					);

					// -------------------------------------
					//  Parse Weblog stuff
					// -------------------------------------

					//ee()->TMPL->log_item('Calendar: Parsing Weblog stuff');

					$channel->parse_channel_entries();

					foreach ($super_temp_fake as $k => $data)
					{
						$channel->return_data = str_replace(
							'6c21bdf1bfdab13bc8df8fcfeb2763a6' . $data['entry_id'],
							$super_temp_fake[$k]['title'],
							$channel->return_data
						);
					}


					// -------------------------------------
					//  Related entries
					// -------------------------------------

					//ee()->TMPL->log_item('Calendar: Parsing related entries');

					if (version_compare($this->ee_version, '2.6.0', '<'))
					{
						if (count(ee()->TMPL->related_data) > 0 AND
							count($channel->related_entries) > 0)
						{
							$channel->parse_related_entries();
						}

						if (count(ee()->TMPL->reverse_related_data) > 0 AND
							count($channel->reverse_related_entries) > 0)
						{
							$channel->parse_reverse_related_entries();
						}
					}

					// -------------------------------------
					//  Collect the parsed data for use later
					// -------------------------------------

					preg_match_all('/' . $blargle . '\[(\d+)\](.*?)' . $bleegle . '/s', $channel->return_data, $matches);
					foreach ($matches[0] as $k => $match)
					{
						$entry_data[$matches[1][$k]] = $matches[2][$k];
					}

					unset($channel);

					// -------------------------------------
					//  Stop the insanity!
					// -------------------------------------

					break;
				}
			}
		}

		//ee()->TMPL->log_item('Calendar:build_calendar() Channel module stuff done');

		// -------------------------------------
		//  Build the calendar
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Starting to build the calendar');

		$start_year		= $start['year'];
		$start_month	= $start['month'];
		$start_day		= $start['day'];
		$end_year		= $end['year'];
		$end_month		= $end['month'];
		$end_day		= $end['day'];
		$w				= 0;

		$this->CDT->reset();

		if ($this->P->value('pad_short_weeks') === TRUE)
		{
			$week_counter = 0;
		}
		else
		{
			$week_counter = ($this->CDT->day_of_week >= $this->first_day_of_week) ?
								$this->CDT->day_of_week - $this->first_day_of_week :
								7 + $this->CDT->day_of_week - $this->first_day_of_week;
		}

		$output = '';
		$week_temp = $month_temp = $year_temp = '';
		$day_count = 0;

		$next_CDT = $prev_CDT = $start;

		$all_day = array();
		$event_array = array();

		// -------------------------------------
		//  Prepare the events
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Preparing events');

		foreach ($events as $id => $event)
		{
			//prevent missing entry data from attempting to display and causing errors
			if ( ! isset($entry_data[$event->default_data['entry_id']]))
			{
				unset($events[$id]);
				continue;
			}

			if (empty($event->dates))
			{
				continue;
			}

			foreach ($event->dates as $ymd => $items)
			{
				foreach ($items as $time => $ddata)
				{
					// -------------------------------------
					//  It was over before it started
					// -------------------------------------

					if ($ddata['end_date']['ymd'].$ddata['end_date']['time'] <
						$this->P->value('date_range_start', 'ymd') .
						$this->P->value('date_range_start', 'time'))
					{
						continue;
					}

					// -------------------------------------
					//  ...or the inverse
					// -------------------------------------

					if ($ddata['date']['ymd'].$ddata['date']['time'] >
						 $this->P->value('date_range_end', 'ymd') .
						 $this->P->value('time_range_end', 'time'))
					{
						continue;
					}

					// -------------------------------------
					//  If the start date of the event is less than the first
					//  day of the calendar, we've got ourselves a hangover.
					// -------------------------------------

					if ($ddata['date']['ymd'] < $this->P->value('date_range_start', 'ymd'))
					{
						$event_array[$this->P->value('date_range_start', 'ymd')]['all_day'][$id]	= $id;
						$events[$id]->dates[$this->P->value('date_range_start', 'ymd')]				= $events[$id]->dates[$ymd];
					}
					elseif ($ddata['all_day'] === TRUE)
					{
						$event_array[$ymd]['all_day'][$id] = $id;
					}
					else
					{
						if ($ddata['multi_day'])
						{
							$event_array[$ymd]['all_day'][$id] = $id;
						}
						else
						{
							$start_hour	= str_pad($ddata['date']['hour'], 2, 0, STR_PAD_LEFT);
							$start_min	= str_pad($ddata['date']['minute'], 2, 0, STR_PAD_LEFT);
							$end_hour	= str_pad($ddata['end_date']['hour'], 2, 0, STR_PAD_LEFT);
							$end_min	= str_pad($ddata['end_date']['minute'], 2, 0, STR_PAD_LEFT);
							$event_array[$ymd][$start_hour][$start_min][$end_hour][$end_min][$id] = $id;
						}
					}
				}
			}



			// -------------------------------------
			//  Aliases
			// -------------------------------------

			$events[$id]->default_data['first_date']	= $events[$id]->default_data['start_date'];
		}

		$this->CDT->reset();

		// -------------------------------------
		//  Prune the event array, if there's an event limit.
		//  NOTE: apply_event_limit() also sorts the array for us.
		// -------------------------------------

		//--------------------------------------------
		//	count all events for tag and pagination
		//--------------------------------------------

		$this->event_timeframe_total = $this->count_event_results($event_array);

		//--------------------------------------------
		//	pagination
		//--------------------------------------------

		$this->paginate = FALSE;

		if ($this->P->value('event_limit') > 0 AND
			$this->event_timeframe_total > $this->P->value('event_limit'))
		{
			//get pagination info
			$pagination_data = $this->universal_pagination(array(
				'total_results'			=> $this->event_timeframe_total,
				//had to remove this jazz before so it didn't get iterated over
				'tagdata'				=> $tagdata . $this->paginate_tagpair_data,
				'limit'					=> $this->P->value('event_limit'),
				'uri_string'			=> ee()->uri->uri_string,
				'paginate_prefix'		=> 'calendar_'
			));

			// -------------------------------------------
			// 'calendar_events_create_pagination' hook.
			//  - Let devs maniuplate the pagination display

			if (ee()->extensions->active_hook('calendar_build_calendar_create_pagination') === TRUE)
			{
				$pagination_data = ee()->extensions->call(
					'calendar_build_calendar_create_pagination',
					$this,
					$pagination_data
				);
			}
			//
			// -------------------------------------------

			//if we paginated, sort the data
			if ($pagination_data['paginate'] === TRUE)
			{
				$this->paginate			= $pagination_data['paginate'];
				$this->page_next		= $pagination_data['page_next'];
				$this->page_previous	= $pagination_data['page_previous'];
				$this->p_page			= $pagination_data['pagination_page'];
				$this->current_page  	= $pagination_data['current_page'];
				$this->pager 			= $pagination_data['pagination_links'];
				$this->basepath			= $pagination_data['base_url'];
				$this->total_pages		= $pagination_data['total_pages'];
				$this->paginate_data	= $pagination_data['paginate_tagpair_data'];
				$this->page_count		= $pagination_data['page_count'];
				//$tagdata				= $pagination_data['tagdata'];
			}
		}

		//--------------------------------------------
		//	event limiter
		//--------------------------------------------

		$offset = (
			ee()->TMPL->fetch_param('event_offset') ?
				ee()->TMPL->fetch_param('event_offset') :
				0
		);

		$page 	= (($this->current_page -1) * $this->P->value('event_limit'));

		if ($page > 0)
		{
			$offset += $page;
		}

		$event_array = $this->apply_event_limit(
			$event_array,
			$this->P->value('event_limit'),
			$offset
		);

		// -------------------------------------
		//  Loopage
		// -------------------------------------

		//ee()->TMPL->log_item('Calendar: Beginning date loops');

		$week_event_count	= 0;
		$event_count		= 0;
		$year_tick			= 0;
		$month_tick			= 0;

		for ($y = $start_year; $y <= $end_year; $y++)
		{
			$year_event_count = 0;

			//blank out
			$this->counted['year'] = array();

			$mx = ($y == $start_year) ? $start_month : 1;
			$my = ($y == $end_year) ? $end_month : 12;

			$year_ymd_cache = $this->CDT->ymd;


			for ($m = $mx; $m <= $my; $m++)
			{
				$month_ymd_cache 	= $this->CDT->ymd;


				$month_event_count = 0;

				//blank out
				$this->counted['month'] = array();

				$dx = ($y == $start_year AND $m == $start_month) ?
						$start_day :
						1;

				$dy = ($y == $end_year AND $m == $end_month) ?
						$end_day :
						$this->CDT->days_in_month($m, $y);

				for ($d = $dx; $d <= $dy; $d++)
				{
					$day_ymd_cache 	= $this->CDT->ymd;
					$day_tick		= 0;

					$this->CDT->change_date($y, $m, $d);
					$this->CDT->set_default($this->CDT->date_array());

					$ymd = $this->CDT->ymd;

					// -------------------------------------
					//  Prepare counts and totals, also
					//  altered later in the loop in some cases
					// -------------------------------------

					$day_event_count = 0;
					$day_event_total = 0;

					//blank out
					$this->counted['day'] = array();

					if (! isset($event_array[$ymd])) $event_array[$ymd] = array();

					$day_event_total	= $this->count_events(
						$ymd,
						$event_array,
						$all_day,
						'day',
						$events
					);

					// -------------------------------------
					//  Start events for the day
					// -------------------------------------

					//ee()->TMPL->log_item('Calendar: Beginning ' . $ymd . count( $event_array[$ymd] ) . ' events for this day.');

					// -------------------------------------
					//  This looks a little goofy, but it
					//  prevents extra years/months
					//  showing up thanks to padded weeks
					// -------------------------------------

					$day_count++;

					// -------------------------------------
					//  Week stuff
					// -------------------------------------

					if ($week_counter % 7 == 0)
					{
						//blank out
						$this->counted['week'] = array();

						$w = str_pad($this->CDT->week_number, 2, 0, STR_PAD_LEFT);
					}

					$week_counter++;

					// -------------------------------------
					//  "next" stuff
					// -------------------------------------

					$next_y = ($m == 12 AND $d == 31) ? $y+1 : $y;
					$next_m = ($d == $this->CDT->days_in_month()) ? $m + 1 : $m;
					$next_w = ($week_counter % 7 == 0) ? $w + 1 : $w;

					// -------------------------------------
					//  Yodelers of Mass Destruction
					// -------------------------------------

					$find			= '';
					$replace		= '';
					$hour_events	= array();
					$last_day		= (
						$y == $end_year AND
						$m == $end_month AND
						$d == $end_day
					) ? TRUE : FALSE;

					// -------------------------------------
					//  Remove "all day" stragglers
					// -------------------------------------

					foreach ($all_day as $i => $stuff)
					{
						if ($ymd > $stuff['end_ymd'])
						{
							unset($all_day[$i]);
						}
					}

					// -------------------------------------
					//  Each event
					// -------------------------------------
					// 	$each_event contains the formatting
					// 	to use for displaying the contents
					// 	of an event on a given day in the calendar.
					// -------------------------------------

					if ($each_event != '')
					{
						$event_output = '';

						if ( ! empty($event_array[$ymd]))
						{
							foreach ($event_array[$ymd] as $start_hour => $sh_data)
							{
								$hour_event_count	= 0;
								$hour_event_total	= count($sh_data);

								if ($start_hour == 'all_day')
								{
									$prev_index = 0;
									$index = 0;

									foreach ($sh_data as $i => $id)
									{
										// -------------------------------------
										//  Multi-day events get one time key, regular
										//  all day events get another
										// -------------------------------------

										$time_key = '00002400';

										if ( ! isset( $events[$id]->dates[$ymd][$time_key]))
										{
											//so in some rare situations this
											//can come to us without being reset?
											//I hate moving pointers :p
											reset($events[$id]->dates[$ymd]);

											$time_key = key($events[$id]->dates[$ymd]);
										}

										$event_count++;
										$year_event_count++;
										$month_event_count++;
										$week_event_count++;
										$day_event_count++;
										$hour_event_count++;

										$vars = $this->get_occurrence_vars($id, $events[$id], $ymd, $time_key);

										$vars['conditional']['event_all_day']	= TRUE;

										$vars['single']	+= array(
											'event_count'				=> $this->create_count_hash('event_count'),
											'year_event_count'			=> $this->create_count_hash('year_event_count'),
											'month_event_count'			=> $this->create_count_hash('month_event_count'),
											'week_event_count'			=> $this->create_count_hash('week_event_count'),
											'day_event_count'			=> $this->create_count_hash('day_event_count'),
											'hour_event_count'			=> $this->create_count_hash('hour_event_count'),
											'all_day_event_index_difference' => $index - $prev_index,
											'all_day_event_index'		=> $index++,

											'event_duration_minutes'	=> (isset($events[$id]->dates[$ymd][$time_key])) ?
													$events[$id]->dates[$ymd][$time_key]['duration']['minutes'] :
													$events[$id]->default_data['duration']['minutes'],

											'event_duration_hours'		=> (isset($events[$id]->dates[$ymd][$time_key])) ?
													$events[$id]->dates[$ymd][$time_key]['duration']['hours'] :
													$events[$id]->default_data['duration']['hours'],

											'event_duration_days'		=> (isset($events[$id]->dates[$ymd][$time_key])) ?
													$events[$id]->dates[$ymd][$time_key]['duration']['days'] :
													$events[$id]->default_data['duration']['days']
										);

										//we have to remove conditionals for these items as well
										//because the dummy hashes screw with them
										$output_data = $this->remove_post_parse_conditionals(array(
											'event_count'	=> $vars['single']['event_count'],
											'year_event_count'		=> $vars['single']['year_event_count'],
											'month_event_count' 	=> $vars['single']['month_event_count'],
											'week_event_count'		=> $vars['single']['week_event_count'],
											'day_event_count'		=> $vars['single']['day_event_count'],
											'hour_event_count'		=> $vars['single']['hour_event_count']
										), $entry_data[$id]);

										// -------------------------------------
										//  If we're outputting at the event level, then we have
										//  to spit out the all day stuff here.
										// -------------------------------------

										if ($output_at == 'event')
										{
											$event_output .= $this->swap_vars($vars, $output_data);
										}

										//shortcut
										$evtk =& $events[$id]->dates[$ymd][$time_key];

										$array = array(
											'output'					=> $output_data,
											'vars'						=> $vars,
											'first_day'					=> $evtk['date']['ymd'],
											'last_day'					=> $evtk['end_date']['ymd'],
											'start_ymd'					=> $evtk['date']['ymd'],
											'end_ymd'					=> $evtk['end_date']['ymd'],
											'time'						=> $time_key,
											'id'						=> $id,
											'event_duration_minutes'	=> $evtk['duration']['minutes'],
											'event_duration_hours'		=> $evtk['duration']['hours'],
											'event_duration_days'		=> $evtk['duration']['days']
										);

										//unset($event_array[$ymd]['all_day'][$id]);

										$count		= (empty($all_day)) ? 0 : max(array_keys($all_day));
										$inserted	= FALSE;
										$prev_index	= $index + 1;

										if (! in_array($array, $all_day))
										{
											for ($i = 0; $i <= $count; $i++)
											{
												// -------------------------------------
												//  Find a spot for this event
												// -------------------------------------

												if (! isset($all_day[$i]))
												{
													$all_day[$i] = $array;
													$inserted = TRUE;
													break;
												}
											}

											if ($inserted === FALSE)
											{
												$all_day[$i] = $array;
											}
										}
									}

									ksort($all_day);

									continue;
								}

								if ($each_hour != '')
								{
									$hour_events[$start_hour] = $sh_data;
								}
								else
								{
									foreach ($sh_data as $start_minute => $sm_data)
									{
										foreach ($sm_data as $end_hour => $eh_data)
										{
											foreach ($eh_data as $end_minute => $event_ids)
											{
												foreach ($event_ids as $id)
												{
													if (isset($entry_data[$id]))
													{
														$start_hour	= str_pad($start_hour, 2, 0, STR_PAD_LEFT);
														$start_min	= str_pad($start_min, 2, 0, STR_PAD_LEFT);
														$end_hour	= str_pad($end_hour, 2, 0, STR_PAD_LEFT);
														$end_min	= str_pad($end_min, 2, 0, STR_PAD_LEFT);

														$time_key = $start_hour.$start_minute.$end_hour.$end_minute;

														if ( ! isset($events[$id]->dates[$ymd][$time_key]))
														{
															continue;
														}

														$event_count++;
														$year_event_count++;
														$month_event_count++;
														$week_event_count++;
														$day_event_count++;
														$hour_event_count++;

														$vars = $this->get_occurrence_vars(
															$id,
															$events[$id],
															$ymd,
															$start_hour . $start_minute . $end_hour . $end_minute
														);

														$vars['single']['event_duration_minutes']	= $events[$id]->dates[$ymd][$time_key]['duration']['minutes'];
														$vars['single']['event_duration_hours']		= $events[$id]->dates[$ymd][$time_key]['duration']['hours'];
														$vars['single']['event_duration_days']		= $events[$id]->dates[$ymd][$time_key]['duration']['days'];
														$vars['single']['event_count']				= $this->create_count_hash('event_count');
														$vars['single']['year_event_count']			= $this->create_count_hash('year_event_count');
														$vars['single']['month_event_count']		= $this->create_count_hash('month_event_count');
														$vars['single']['week_event_count']			= $this->create_count_hash('week_event_count');
														$vars['single']['day_event_count']			= $this->create_count_hash('day_event_count');
														$vars['single']['hour_event_count']			= $this->create_count_hash('hour_event_count');
														$vars['single']['day_event_total']			= $day_event_total;
														$vars['single']['hour_event_total']			= $hour_event_total;
														$vars['single']['all_day_event_index']		= 0;
														$vars['single']['all_day_event_index_difference'] = 0;

														//we have to remove conditionals for these items as well
														//because the dummy hashes screw with them
														$output_data = $this->remove_post_parse_conditionals(array(
															'event_count'			=> $vars['single']['event_count'],
															'year_event_count'		=> $vars['single']['year_event_count'],
															'month_event_count'		=> $vars['single']['month_event_count'],
															'week_event_count'		=> $vars['single']['week_event_count'],
															'day_event_count'		=> $vars['single']['day_event_count'],
															'hour_event_count'		=> $vars['single']['hour_event_count']
														), $entry_data[$id]);

														$event_output .= $this->swap_vars($vars, $output_data);

													}
												}
											}
										}
									}
								}
							}

							//parse day event counts
							$event_output = $this->parse_count_hashes('hour_event_count', $event_output);
						}

						$find = $hash_event;
						$replace = $event_output;

						if ($output_at == 'event')
						{
							$output .= $replace;
							$replace = '';
						}
					}

					// -------------------------------------
					//  If $each_event is empty, $all_day never gets filled. Let's fix that.
					// -------------------------------------

					if ($each_event == '' AND isset($event_array[$ymd]['all_day']))
					{
						foreach ($event_array[$ymd]['all_day'] as $id)
						{
							$time_key			= (! isset( $events[$id]->dates[$ymd]['00002400'])) ?
													key($events[$id]->dates[$ymd]) :
													'00002400';

							$data['end_ymd']	= $events[$id]->dates[$ymd][$time_key]['end_date']['ymd'];

							// -------------------------------------
							//  This stuff can be gibberish since it'll never show, but we
							//  provide it because other parts of the code expect it to exist
							// -------------------------------------

							$data['time']		= '';
							$data['vars']		= array();
							$data['first_day']	= FALSE;
							$data['last_day']	= FALSE;
							$data['output']		= '';

							// -------------------------------------
							//  Add the all day event
							// -------------------------------------

							$all_day[]			= $data;
						}
					}

					$all_day_event_total	= count($all_day);

					// -------------------------------------
					//  "All day" output
					// -------------------------------------

					$all_day_output = '';

					if ( ! empty($all_day))
					{
						$prev_index = 0;
						$hour_event_count = 0;

						foreach ($all_day as $all_day_data)
						{
							$time_key	= $all_day_data['time'];
							$vars		= $all_day_data['vars'];
							$vars['single']['event_first_day']	= ($ymd == $all_day_data['first_day']) ? TRUE : FALSE;
							$vars['single']['event_last_day']	= ($ymd == $all_day_data['last_day']) ? TRUE : FALSE;

							// -------------------------------------
							//  Process all day variables
							// -------------------------------------

							$all_day_output	.= $this->swap_vars($vars, $all_day_data['output']);
						}
						$this->CDT->reset();
					}

					// -------------------------------------
					//  Each hour
					// -------------------------------------

					if ($each_hour != '')
					{
						$hour_output = '';
						$hour_temp = '';

						if ($all_day_output != '')
						{
							$hour_output = $all_day_output;
							$all_day_output = '';
						}

						for ($i = 0; $i < 24; $i++)
						{
							$hour_temp			= '';
							$hour_count			= 0;
							$hour_event_count	= 0;
							$h					= str_pad($i, 2, '0', STR_PAD_LEFT);
							//$this->cdt_format_date_string($this->CDT->datetime_array(), 'H');
							$minute				= '00';
							//$this->cdt_format_date_string($this->CDT->datetime_array(), 'i');

							if (isset($hour_events[$h]))
							{
								foreach ($hour_events[$h] as $start_minute => $sm_data)
								{
									foreach ($sm_data as $end_hour => $eh_data)
									{
										foreach ($eh_data as $end_minute => $event_ids)
										{
											$hour_count += count($event_ids);

											foreach ($event_ids as $id)
											{
												if (isset($entry_data[$id]))
												{
													$event_count++;
													$year_event_count++;
													$month_event_count++;
													$week_event_count++;
													$day_event_count++;
													$hour_event_count++;

													$vars = $this->get_occurrence_vars(
														$id,
														$events[$id],
														$ymd,
														$h . $start_minute . $end_hour . $end_minute
													);

													$vars['single']['event_count']			= $this->create_count_hash('event_count');
													$vars['single']['hour_event_count']		= $this->create_count_hash('hour_event_count');
													$vars['single']['year_event_count']		= $this->create_count_hash('year_event_count');
													$vars['single']['month_event_count']	= $this->create_count_hash('month_event_count');
													$vars['single']['week_event_count']		= $this->create_count_hash('week_event_count');
													$vars['single']['day_event_count']		= $this->create_count_hash('day_event_count');

													//we have to remove conditionals for these items as well
													//because the dummy hashes screw with them
													$output_data = $this->remove_post_parse_conditionals(array(
														'event_count'			=> $vars['single']['event_count'],
														'year_event_count'		=> $vars['single']['year_event_count'],
														'month_event_count'		=> $vars['single']['month_event_count'],
														'week_event_count'		=> $vars['single']['week_event_count'],
														'day_event_count'		=> $vars['single']['day_event_count'],
														'hour_event_count'		=> $vars['single']['hour_event_count']
													), $entry_data[$id]);

													$hour_temp .= $this->swap_vars($vars, $output_data);
												}
											}
										}
									}
								}

								$hour_temp = str_replace($find, $hour_temp, $each_hour);
							}
							else
							{
								$hour_temp = str_replace($find, $replace, $each_hour);
							}

							$vars = array();
							$total_events = $hour_count;
							$this->CDT->change_time($i, 0);

							$vars['date'] = array(
								'time'		=> $this->CDT->datetime_array(),
								'date'		=> $this->CDT->datetime_array()
							);

							$vars['single'] = array(
								'hour'				=> $h,
								'minute'			=> $minute,
								'time'				=> $h.':'.$minute,
								'hour_event_total' 	=> $total_events
							);

							$hour_output .= $this->swap_vars($vars, $hour_temp);

							//parse day event counts
							$hour_output = $this->parse_count_hashes('hour_event_count', $hour_output);
						}

						$find = $hash_hour;
						$replace = $hour_output;

						if ($output_at == 'hour')
						{
							$output .= $replace;
							$replace = '';

							//ee()->TMPL->log_item('Calendar: Outputting hour data for '.$ymd);
						}
					}

					// -------------------------------------
					//  Each day
					// -------------------------------------

					//var_dump($this->CDT->ymd . ' OUTSIDE DAY');

					if ($each_day != '')
					{
						$prefix = $suffix = '';

						// -------------------------------------
						//  Prep day variables
						// -------------------------------------

						if ($all_day_output != '')
						{
							$replace = $all_day_output . $replace;
							$all_day_output = '';
						}

						//var_dump($this->CDT->ymd . ' DAY');

						$day_output = str_replace($find, $replace, $each_day);

						$next_CDT = $this->CDT->add_day(1);
						$prev_CDT = $this->CDT->add_day(-2);
						$this->CDT->reset();

						//var_dump($this->CDT->ymd . ' DAY 2');

						$vars = array();

						$vars['conditional'] = array(
							'day_is_today'			=> ($today_ymd == $ymd) ? TRUE : FALSE,
							'day_is_weekend'		=> ($this->CDT->day_of_week == 0 OR
														$this->CDT->day_of_week == 6) ? TRUE : FALSE,
							'day_is_weekday'		=> ($this->CDT->day_of_week == 0 OR
														$this->CDT->day_of_week == 6) ? FALSE : TRUE,
							'day_in_current_month'	=> ($this->CDT->month == $current_period_start['month']) ? TRUE : FALSE,
							'day_in_previous_month'	=> ($this->CDT->month < $current_period_start['month'] OR
														$this->CDT->year < $current_period_start['year']) ? TRUE : FALSE,
							'day_in_next_month'		=> ($this->CDT->month > $current_period_start['month'] OR
														$this->CDT->year > $current_period_start['year']) ? TRUE : FALSE
						);

						$vars['single'] = array(
							'day'					=> $d,
							'prev_day'				=> $prev_CDT['day'],
							'next_day'				=> $next_CDT['day'],
							'day_event_total'		=> $day_event_total,
							'all_day_event_total'	=> $all_day_event_total
						);

						$vars['date'] = array(
							'day' 		=> $this->CDT->datetime_array(),
							'date'		=> $this->CDT->datetime_array(),
							'prev_day' 	=> $prev_CDT,
							'next_day'	=> $next_CDT
						);

						$day_output = $this->parse_count_hashes('day_event_count', $this->swap_vars($vars, $day_output));


						$find = $hash_day;
						$replace = $prefix.$day_output.$suffix;

						if ($output_at == 'day')
						{
							$output .= $replace;
							$replace = '';
//ee()->TMPL->log_item('Calendar: Outputting day data for '.$ymd);
						}

						$this->CDT->reset();

						//var_dump($this->CDT->ymd . ' DAY 3');
					}

					// -------------------------------------
					//  Each week
					// -------------------------------------

//var_dump($this->CDT->ymd . ' OUTSIDE WEEK');

					if ($each_week != '')
					{
						if ($all_day_output != '')
						{
							$replace = $all_day_output . $replace;
							$all_day_output = '';
							if ($each_day == '')
							{
								$all_day = array();
							}
						}

						if ($w != $next_w OR
							($each_month != '' AND
								(($m != $next_m OR $last_day === TRUE) AND
									($ymd >= $current_period_start['ymd'] AND
										(	($last_day === TRUE AND $ymd == $current_period_end['ymd']) OR
										($ymd != $current_period_end['ymd']))))) OR
							$last_day === TRUE)
						{
							//var_dump($this->CDT->ymd . ' WEEK');

							$week_temp .= $replace;
							$week_output = str_replace($find, $week_temp, $each_week);

							// -------------------------------------
							//  Prep week variables
							// -------------------------------------

							$offset = ($this->CDT->day_of_week > $this->first_day_of_week) ?
										$this->CDT->day_of_week - $this->first_day_of_week :
										7 - ($this->first_day_of_week - $this->CDT->day_of_week);

							$this->CDT->add_day(-$offset);
							$this->CDT->set_default($this->CDT->datetime_array());
							$next_CDT = $this->CDT->add_day(7);
							$prev_CDT = $this->CDT->add_day(-14);
							$this->CDT->reset();

							//var_dump($this->CDT->ymd . ' WEEK 2');

							// -------------------------------------
							//  Calculate the number of events this week
							// -------------------------------------

							$week_event_total 	= 0;

							//$week_count_id		= 'week_event_total_' . uniqid();

							if (strpos($each_week, 'week_event_total') !== FALSE)
							{
								$low	= $this->CDT->year .
											str_pad($this->CDT->month, 2, '0', STR_PAD_LEFT) .
											str_pad($this->CDT->day, 2, '0', STR_PAD_LEFT);

								$this->CDT->add_day(6);

								$high	= $this->CDT->year .
											str_pad($this->CDT->month, 2, '0', STR_PAD_LEFT) .
											str_pad($this->CDT->day, 2, '0', STR_PAD_LEFT);

								$this->CDT->reset();

								foreach ($event_array as $k => $v)
								{
									if ($k < $low OR $k > $high)
									{
										continue;
									}

									$week_event_total += $this->count_events($k, $event_array, $all_day, 'week', $events);
								}
							}

							$vars = array();

							$vars['single'] = array(
								'week'				=> $w,
								'prev_week'			=> $prev_CDT['week_number'],
								'next_week'			=> $next_CDT['week_number'],
								'week_event_total' 	=> $week_event_total
							);

							$vars['date'] = array(
								'week' 		   		=> $this->CDT->datetime_array(),
								'date' 		   		=> $this->CDT->datetime_array(),
								'prev_week'    		=> $prev_CDT,
								'next_week'	   		=> $next_CDT
							);

							$week_output = $this->parse_count_hashes('week_event_count', $this->swap_vars($vars, $week_output));

							$find = $hash_week;
							$replace = $week_output;
							$week_temp = '';

							if ($output_at == 'week')
							{
								$output .= $replace;
								$replace = '';
							}

							$this->CDT->reset();
							$week_event_count = 0;
						}
						elseif ($end['day'] == $d AND
								$end['month'] == $m AND
								$d == $this->CDT->days_in_month($m, $y) AND
								$this->P->value('pad_short_weeks') === TRUE)
						{
							$week_temp .= $replace;
							$week_output = str_replace($find, $week_temp, $each_week);

							// -------------------------------------
							//  Prep week variables
							// -------------------------------------

							$this->CDT->add_day(-6);
							$next_CDT = $this->CDT->add_day(7);
							$prev_CDT = $this->CDT->add_day(-14);
							$this->CDT->reset();

							// -------------------------------------
							//  Calculate the number of events this week
							// -------------------------------------

							$week_event_total = 0;

							if (strpos($each_week, 'week_event_total') !== FALSE)
							{
								$low	= $this->CDT->year .
											str_pad($this->CDT->month, 2, '0', STR_PAD_LEFT) .
											str_pad($this->CDT->day, 2, '0', STR_PAD_LEFT);

								$this->CDT->add_day(6);

								$high	= $this->CDT->year .
											str_pad($this->CDT->month, 2, '0', STR_PAD_LEFT) .
											str_pad($this->CDT->day, 2, '0', STR_PAD_LEFT);

								$this->CDT->reset();

								foreach ($event_array as $k => $v)
								{
									if ($k < $low OR $k > $high)
									{
										continue;
									}

									$week_event_total += $this->count_events($k, $event_array, $all_day, 'week', $events);
								}
							}

							$vars = array();

							$vars['single'] = array(
								'week'				=> $w,
								'prev_week'			=> $prev_CDT['week_number'],
								'next_week'			=> $next_CDT['week_number'],
								'week_event_total' 	=> $week_event_total
							);

							$vars['date'] = array(
								'week' 				=> $this->CDT->datetime_array(),
								'date'				=> $this->CDT->datetime_array(),
								'prev_week' 		=> $prev_CDT,
								'next_week'			=> $next_CDT
							);

							$week_output = $this->parse_count_hashes('week_event_count', $this->swap_vars($vars, $week_output));

							$find 		= $hash_week;
							$replace 	= $week_output;
							$week_temp 	= '';

							if ($output_at == 'week')
							{
								$output .= $replace;
								$replace = '';
//ee()->TMPL->log_item('Calendar: Outputting week data for '.$ymd);
							}

							$this->CDT->reset();
							$week_event_count = 0;
						}
						else
						{
							$week_temp .= $replace;
							$replace = '';
						}
					}

					// -------------------------------------
					//  Each month
					// -------------------------------------

//var_dump($this->CDT->ymd . ' OUTSIDE MONTH');

					if ($each_month != '')
					{
						if ($all_day_output != '')
						{
							$replace = $all_day_output . $replace;
							$all_day_output = '';
							if ($each_day == '')
							{
								$all_day = array();
							}
						}

						if (($m != $next_m OR $last_day === TRUE) AND
							(	$ymd >= $current_period_start['ymd'] AND
								(	($last_day === TRUE AND $ymd == $current_period_end['ymd']) OR
									($ymd != $current_period_end['ymd'])
								)
							)
							)
						{

							$month_temp .= $replace;
							$month_output = str_replace($find, $month_temp, $each_month);

							//--------------------------------------------
							//	reset and add month because
							//	this gets 'off' some places
							//--------------------------------------------
							$this->CDT->set_default($current_period_start);
							$this->CDT->reset();

							//first month is correct
							if ($month_tick > 0)
							{
								$this->CDT->add_month($month_tick);
							}

							$month_tick++;

							// -------------------------------------
							//  Prep month variables
							// -------------------------------------

							//add a month
							$next_CDT = $this->CDT->add_month();

							//subtract 2
							$prev_CDT = $this->CDT->add_month(-2);

							//add 1, now we are back where we started!
							$this->CDT->add_month();
							$this->CDT->change_date($this->CDT->year, $this->CDT->month, 1);

							$vars = array();

							//var_dump($this->CDT->ymd . ' MONTH 2');

							// -------------------------------------
							//  Calculate the number of events this month
							// -------------------------------------

							$month_event_total = 0;

							if (strpos($each_month, 'month_event_total') !== FALSE)
							{
								$low	= $this->CDT->year.str_pad($this->CDT->month, 2, '0', STR_PAD_LEFT).'01';
								$high	= $this->CDT->year.str_pad($this->CDT->month, 2, '0', STR_PAD_LEFT).$this->CDT->days_in_month();

								foreach ($event_array as $k => $v)
								{
									if ($k < $low OR $k > $high)
									{
										continue;
									}

									$month_event_total += $this->count_events($k, $event_array, $all_day, 'month', $events);
								}
							}

							//$vars['conditional'] = array( 'day_in_current_month' => TRUE );
							$vars['single'] = array(
								'month'				=> $m,
								'prev_month'		=> $prev_CDT['month'],
								'next_month'		=> $next_CDT['month'],
								'month_event_total' => $month_event_total
							);

							$vars['date'] = array(
								'month' 			=> $this->CDT->datetime_array(),
								'date'				=> $this->CDT->datetime_array(),
								'prev_month' 		=> $prev_CDT,
								'next_month'		=> $next_CDT
							);

							$month_output 	= $this->parse_count_hashes(
								'month_event_count',
								$this->swap_vars($vars, $month_output)
							);

							$replace 		= $month_output;

							$find = $hash_month;
							$month_temp = '';

							if ($output_at == 'month')
							{
								$output .= $replace;
								$replace = '';
//ee()->TMPL->log_item('Calendar: Outputting month data for '.$ymd);
							}

							$this->CDT->reset();
						}
						else
						{
							$month_temp .= $replace;
							$replace = '';
						}
					}

					// -------------------------------------
					//  Each year
					// -------------------------------------

					if ($each_year != '')
					{
						if ($all_day_output != '')
						{
							$replace = $all_day_output . $replace;
							$all_day_output = '';
							if ($each_day == '')
							{
								$all_day = array();
							}
						}

						if ($y != $next_y OR $last_day === TRUE)
						{
							//if ($last_day !== TRUE)
							if ($last_day == TRUE)
							{
								$year_temp .= $replace;

								$year_output = str_replace($find, $year_temp, $each_year);

								// -------------------------------------
								//  Calculate the number of events this year
								// -------------------------------------

								$year_event_total = 0;

								if (strpos(ee()->TMPL->tagdata, 'year_event_total') !== FALSE)
								{
									$low	= $this->CDT->year.'0101';
									$high	= $this->CDT->year.'1231';
									foreach ($event_array as $k => $v)
									{
										if ($k < $low OR $k > $high)
										{
											continue;
										}

										$year_event_total += $this->count_events($k, $event_array, array(), 'year', $events);
									}
								}

								// -------------------------------------
								//  Prep year variables
								// -------------------------------------

								$this->CDT->set_default($current_period_start);
								$this->CDT->reset();
								if ($year_tick > 1)
								{
									$this->CDT->add_year($year_tick);
								}
								$year_tick++;

								$next_CDT = $this->CDT->add_year(1);
								$prev_CDT = $this->CDT->add_year(-2);
								$this->CDT->reset();

								$vars = array();

								$vars['conditional']		= array(
									'year_is_leap_year'	=> $this->CDT->is_leap_year()
								);

								$vars['single']				= array(
									'year'				=> $y,
									'prev_year'			=> $prev_CDT['year'],
									'next_year'			=> $next_CDT['year'],
									'year_event_total'	=> $year_event_total
								);

								$vars['date']				= array(
									'year' 				=> $this->CDT->datetime_array(),
									'date'				=> $this->CDT->datetime_array(),
									'prev_year' 		=> $prev_CDT,
									'next_year' 		=> $next_CDT
								);

								$year_output = $this->parse_count_hashes(
									'year_event_count',
									$this->swap_vars($vars, $year_output)
								);

								$replace = $year_output;

								$this->CDT->reset();
							}
							else
							{
								//$replace = '';
							}

							$find = $hash_year;
							$year_temp = '';

							if ($output_at == 'year')
							{
								$output .= $replace;
								$replace = '';
//ee()->TMPL->log_item('Calendar: Outputting year data for '.$ymd);
							}
						}
						else
						{
							$year_temp .= $replace;
							$replace = '';
						}
					}

					//parse day event counts
					//$output = $this->parse_count_hashes('day_event_count', $output);

					//parse week event counts
					//$output = $this->parse_count_hashes('week_event_count', $output);
				}

				//parse month event counts
				//$output = $this->parse_count_hashes('month_event_count', $output);
			}

			//parse year event count
			//$output = $this->parse_count_hashes('year_event_count', $output);
		}

		// -------------------------------------
		//	running all of these again in case
		//	the didn't fire. This means we
		//	are in a straight event loop in
		//	cal and it's probably errorsome :/
		// -------------------------------------

		//parse hour event counts
		$output = $this->parse_count_hashes('hour_event_count', $output);

		//parse day event counts
		$output = $this->parse_count_hashes('day_event_count', $output);

		//parse week event counts
		$output = $this->parse_count_hashes('week_event_count', $output);

		//parse month event counts
		$output = $this->parse_count_hashes('month_event_count', $output);

		//parse year event count
		$output = $this->parse_count_hashes('year_event_count', $output);

		//parse year event count
		$output = $this->parse_count_hashes('event_count', $output);

		$output = $this->swap_vars(array('single'=>array('event_total' => $event_count)), $output);

		$hash = 'hash_'.$output_at;

		$tagdata = isset($$hash) ? str_replace($$hash, $output, $tagdata) : $output;

		$tagdata = $this->parse_pagination($tagdata);

		//ee()->TMPL->log_item('Calendar: All done!');

		// -------------------------------------
		//	setting everything back thats not parsed
		//	in case people were writing it in plain text
		// -------------------------------------

		//custom variables with the letters 'url' are borked in
		//EE 2.6. Bug reported, but this should fix.
		//https://support.ellislab.com/bugs/detail/19337
		if (version_compare($this->ee_version, '2.6.0', '>='))
		{
			$tagdata = str_replace(
				array(
					LD . 'event_borked_title' . RD,
					'"event_borked_title"',
					"'event_borked_title'"

				),
				array(
					LD . 'event_url_title' . RD,
					'"event_url_title"',
					"'event_url_title'"
				),
				$tagdata
			);


			$tagdata = str_replace(
				array(
					LD . 'event_calendar_borked_title' . RD,
					'"event_calendar_borked_title"',
					"'event_calendar_borked_title'"

				),
				array(
					LD . 'event_calendar_url_title' . RD,
					'"event_calendar_url_title"',
					"'event_calendar_url_title'"
				),
				$tagdata
			);
		}

		//--------------------------------------------
		//	this shouldn't ever be needed, but here we are
		//--------------------------------------------

		if (trim($tagdata) === '')
		{
			return $this->no_results();
		}

		// -------------------------------------
		//  Send 'em home
		// -------------------------------------

		return $tagdata;
	}
	// END build_calendar


	// --------------------------------------------------------------------

	/**
	 * Parse pagination
	 *
	 * @access	private
	 * @return	string
	 */

	public function parse_pagination( $return = '' )
	{
		// ----------------------------------------
		//	Capture pagination format
		// ----------------------------------------

		if ( $this->paginate === FALSE )
		{
			$return = preg_replace(
				"/" . LD . "if calendar_paginate" . RD . ".*?" . LD . "&#47;if" . RD . "/s",
				'',
				$return
			);

			$return = preg_replace(
				"/" . LD . "if paginate" . RD . ".*?" . LD . "&#47;if" . RD . "/s",
				'',
				$return
			);
		}
		else
		{
			$return = preg_replace(
				"/" . LD . "if calendar_paginate" . RD . "(.*?)" . LD . "&#47;if" . RD . "/s",
				"\\1",
				$return
			);

			$return = preg_replace(
				"/" . LD . "if paginate" . RD . "(.*?)" . LD . "&#47;if" . RD . "/s",
				"\\1",
				$return
			);

			$pagination_array	= array(
				'calendar_pagination_links'	=> $this->pager,
				'calendar_current_page'		=> $this->current_page,
				'calendar_total_pages'		=> $this->total_pages,
				'calendar_page_count'		=> $this->page_count,
				'pagination_links'			=> $this->pager,
				'current_page'				=> $this->current_page,
				'total_pages'				=> $this->total_pages,
				'page_count'				=> $this->page_count
			);

			$this->paginate_data	= ee()->functions->prep_conditionals( $this->paginate_data, $pagination_array );

			foreach ( $pagination_array as $key => $val )
			{
				$this->paginate_data	= str_replace( LD.$key.RD, $val, $this->paginate_data);
			}

			// ----------------------------------------
			//	Previous link
			// ----------------------------------------

			if (preg_match("/".LD."if calendar_previous_page".RD."(.+?)".LD.preg_quote(T_SLASH, '/')."if".RD."/s", $this->paginate_data, $match))
			{
				if ($this->page_previous == '')
				{
					 $this->paginate_data = preg_replace(
						"/" . LD . "if calendar_previous_page" . RD . ".+?" . LD . preg_quote(T_SLASH, '/') . "if" . RD . "/s",
						'',
						$this->paginate_data
					);
				}
				else
				{
					$match['1'] = preg_replace("/".LD.'calendar_path.*?'.RD."/", 	$this->page_previous, $match['1']);
					$match['1'] = preg_replace("/".LD.'calendar_auto_path'.RD."/",	$this->page_previous, $match['1']);

					$this->paginate_data = str_replace($match['0'],	$match['1'], $this->paginate_data);
				}
			}
			else if (preg_match("/".LD."if previous_page".RD."(.+?)".LD.preg_quote(T_SLASH, '/')."if".RD."/s", $this->paginate_data, $match))
			{
				if ($this->page_previous == '')
				{
					 $this->paginate_data = preg_replace(
						"/" . LD . "if previous_page" . RD . ".+?" . LD . preg_quote(T_SLASH, '/') . "if" . RD . "/s",
						'',
						$this->paginate_data
					);
				}
				else
				{
					$match['1'] = preg_replace("/".LD.'path.*?'.RD."/", 	$this->page_previous, $match['1']);
					$match['1'] = preg_replace("/".LD.'auto_path'.RD."/",	$this->page_previous, $match['1']);

					$this->paginate_data = str_replace($match['0'],	$match['1'], $this->paginate_data);
				}
			}

			// ----------------------------------------
			//	Next link
			// ----------------------------------------

			if (preg_match("/".LD."if calendar_next_page".RD."(.+?)".LD.preg_quote(T_SLASH, '/')."if".RD."/s", $this->paginate_data, $match))
			{
				if ($this->page_next == '')
				{
					 $this->paginate_data = preg_replace("/".LD."if calendar_next_page".RD.".+?".LD.preg_quote(T_SLASH, '/')."if".RD."/s", '', $this->paginate_data);
				}
				else
				{
					$match['1'] = preg_replace("/".LD.'calendar_path.*?'.RD."/", 	$this->page_next, $match['1']);
					$match['1'] = preg_replace("/".LD.'calendar_auto_path'.RD."/",	$this->page_next, $match['1']);

					$this->paginate_data = str_replace( $match['0'],	$match['1'], $this->paginate_data );
				}
			}
			else if (preg_match("/".LD."if next_page".RD."(.+?)".LD.preg_quote(T_SLASH, '/')."if".RD."/s", $this->paginate_data, $match))
			{
				if ($this->page_next == '')
				{
					 $this->paginate_data = preg_replace("/".LD."if next_page".RD.".+?".LD.preg_quote(T_SLASH, '/')."if".RD."/s", '', $this->paginate_data);
				}
				else
				{
					$match['1'] = preg_replace("/".LD.'path.*?'.RD."/", 	$this->page_next, $match['1']);
					$match['1'] = preg_replace("/".LD.'auto_path'.RD."/",	$this->page_next, $match['1']);

					$this->paginate_data = str_replace( $match['0'],	$match['1'], $this->paginate_data );
				}
			}
		}

		// ----------------------------------------
		//	Add pagination
		// ----------------------------------------

		if ( ee()->TMPL->fetch_param('paginate') == 'both' )
		{
			$return	= $this->paginate_data.$return.$this->paginate_data;
		}
		elseif ( ee()->TMPL->fetch_param('paginate') == 'top' )
		{
			$return	= $this->paginate_data.$return;
		}
		else
		{
			$return	= $return.$this->paginate_data;
		}

		// ----------------------------------------
		//	Return
		// ----------------------------------------

		return $return;
	}

	// End parse pagination


	// --------------------------------------------------------------------

	/**
	 * create_count_hash
	 *
	 * events are not parsed in order in build_calender so this helps with counts
	 *
	 * @access private
	 * @param	string	$type	type of event to place
	 * @return	string	tag item
	 */

	private function create_count_hash($type)
	{
		$hash = md5(uniqid());

		return 	LD . 'count_hash_placeholder type="' . $type . '"' . RD .
					$hash .
				LD . T_SLASH . 'count_hash_placeholder' . RD;
	}
	//END create_count_hash


	// --------------------------------------------------------------------

	/**
	 * equal_delimiters
	 *
	 * @access	private
	 * @param	string 	$str	- string to check matches of delimters against
	 * @param	string 	$open	- opening delimiter
	 * @param	string 	$close	- closing delimiter
	 * @return	string	returns bool TRUE of there are equal sets of openers and closers
	 */

	private function equal_delimiters($str, $open = LD, $close = RD)
	{
		return substr_count($str, $open) === substr_count($str, $close);
	}
	// END equal_delimiters


	// --------------------------------------------------------------------

	/**
	 * remove_post_parse_conditionals
	 *
	 * removes conditional variables and replaces with tag pairs from
	 * create_count_hash (intended to be passed in)
	 * this is needed due to events being parsed out of order :/
	 *
	 * @access private
	 * @param	array	$types		array of tags and hashes to remove from conditionals
	 * @param	string	$tagdata	tagdata to remove conditionals from
	 * @return	tagdata
	 */

	private function remove_post_parse_conditionals($tags, $tagdata)
	{
		//--------------------------------------------
		//	first find all if openers
		//--------------------------------------------

		//need a temp to work with
		$temp 				= $tagdata;

		$if_openers 		= array();

		$count = 0;

		while ($temp = stristr($temp, LD . 'if'))
		{
			$count++;

			//we first have to find the proper end of the {if} tag
			$if_tag_pos 	= strpos($temp, RD, 0);
			$if_tag 		= substr($temp, 0, $if_tag_pos + 1);

			$inner_count = 0;

			//if we dont have an equal number of delimters...
			while ( ! $this->equal_delimiters($if_tag))
			{
				$inner_count++;

				//we keep checking for the next right delmiter in line
				$if_tag_pos = strpos($temp, RD, $if_tag_pos + 1);
				$if_tag 	= substr($temp, 0, $if_tag_pos + 1);

				if ($inner_count > 1000) break;
			}

			$if_openers[] 	= $if_tag;

			//remove from temp so we can move on in the while loop
			$temp 			= substr($temp, strlen($if_tag));

			if ($count > 1000) break;
		}

		//--------------------------------------------
		//	replace all items from if tags with
		//--------------------------------------------

		//keep a copy of old ones
		$original_ifs = $if_openers;

		//replace all tag names as the passed in hash (with pair tags)
		foreach ($tags as $tag => $hash)
		{
			foreach ($if_openers as $key => $value)
			{
				//--------------------------------------------
				//	this is complicated, but we have
				//	to make sure no previous
				//	replacements get double wrapped
				//--------------------------------------------

				$matches = array();
				$holders = array();

				if (preg_match_all(
					"/" . LD . 'count_hash_placeholder type="' . $tag . '"' . RD . "(.+?)" .
						  LD . preg_quote(T_SLASH, '/') . 'count_hash_placeholder' . RD . "/s",
					$if_openers[$key],
					$matches
				))
				{
					for ($i = 0, $l = count($matches[0]); $i < $l; $i++)
					{
						$holder_hash 			= md5($matches[0][$i]);
						$holders[$holder_hash] 	= $matches[0][$i];
						$if_openers[$key] 		= str_replace(
							$matches[0][$i],
							$holder_hash,
							$if_openers[$key]
						);
					}
				}

				//fix any remaining
				$if_openers[$key] = str_replace($tag, $hash, $if_openers[$key]);

				//put any holders back in
				if ( ! empty($holders))
				{
					foreach($holders as $holder_hash => $held_data)
					{
						$if_openers[$key] = str_replace(
							$holder_hash,
							$held_data,
							$if_openers[$key]
						);
					}
				}
			}
		}

		//replace old if blocks with new ones
		foreach ($original_ifs as $key => $value)
		{
			$tagdata = str_replace($original_ifs[$key], $if_openers[$key], $tagdata);
		}

		return $tagdata;
	}
	//END remove_post_parse_conditionals


	// --------------------------------------------------------------------

	/**
	 * parse_count_hashes
	 *
	 * this parses the outputs of create_count_hash
	 *
	 * @access private
	 * @param	string	$type		type of event to place
	 * @param	string	$tagdata	tagdata to be parsed
	 * @return	string	parsed tagdata
	 */

	private function parse_count_hashes($type, $tagdata)
	{
		$matches 		= array();
		$current_hash 	= '';
		$count			= 0;

		//no match, no work
		if ( ! preg_match_all(
			"/" . LD . 'count_hash_placeholder type="' . $type . '"' . RD . "(.+?)" .
				  LD . preg_quote(T_SLASH, '/') . 'count_hash_placeholder' . RD . "/s",
			$tagdata,
			$matches
		))
		{
			return $tagdata;
		}

		//replace each hash with a sucessive number
		for ($i = 0, $l = count($matches[0]); $i < $l; $i++)
		{
			//we are going to hit all of the same hashes at once
			//in case they are using the hashes in the same place
			if ($current_hash != $matches[1][$i])
			{
				$current_hash = $matches[1][$i];

				$tagdata = str_replace($matches[0][$i], ++$count, $tagdata);
			}
		}

		return $tagdata;
	}
	//END parse_count_hashes


	// --------------------------------------------------------------------

	/**
	 * Apply event limit
	 *
	 * @param	array	$array	Event array
	 * @param	int		$limit	Limit
	 * @return	array
	 */

	public function apply_event_limit($array, $limit = 0, $offset = 0)
	{
		$new_array	= array();
		$count		= -$offset;

		// Sort the array
		ksort($array);

		foreach ($array as $ymd => $ymd_data)
		{
			// Sort by start hour
			if (isset($ymd_data['all_day']))
			{
				$all_day = $ymd_data['all_day'];
				unset($ymd_data['all_day']);
				ksort($ymd_data);
				$ymd_data['all_day'] = $all_day;
			}
			else
			{
				ksort($ymd_data);
			}

			foreach ($ymd_data as $sh => $sh_data)
			{
				if ($sh == 'all_day')
				{
					foreach ($sh_data as $k => $v)
					{
						if ($count >= 0)
						{
							$new_array[$ymd][$sh][$k]	= $v;
						}

						$count++;

						if ($limit != 0 AND $count == $limit)
						{
							break(3);
						}
					}

					continue;
				}
				else
				{
					// Sort by start minute
					ksort($sh_data);

					foreach ($sh_data as $sm => $sm_data)
					{
						// Sort by end hour
						ksort($sm_data);

						foreach ($sm_data as $eh => $eh_data)
						{
							// Sort by end minute
							ksort($eh_data);

							foreach ($eh_data as $em => $em_data)
							{
								foreach ($em_data as $k => $v)
								{
									if ($count >= 0)
									{
										$new_array[$ymd][$sh][$sm][$eh][$em][$k]	= $v;
									}

									$count++;

									if ($limit != 0 AND $count == $limit)
									{
										break(6);
									}
								}
							}
						}
					}
				}
			}
		}

		return $new_array;
	}
	/* END apply_event_limit */


	// --------------------------------------------------------------------

	/**
	 * Apply occurrences limit
	 *
	 * @param	array	$array		Event array
	 * @param	int		$limit		Limit
	 * @param	int		$offset		count offset
	 * @param	string	$direction	direction to remove from
	 * @return	array
	 */

	public function apply_occurrences_limit($array, $limit = 0, $offset = 0, $direction = 'upcoming')
	{
		$count 			= 0;
		$offset_skip	= 0;
		$return 		= array();

		if (empty($array))
		{
			return $return;
		}

		//sort so we remove proper dates
		if ($direction == 'prior')
		{
			krsort($array);
		}
		else
		{
			ksort($array);
		}

		foreach ($array as $key => $value)
		{
			//if we have sub items in the same day
			if (is_array($value))
			{
				if ($direction == 'prior')
				{
					krsort($value);
				}
				else
				{
					ksort($value);
				}

				foreach ($value as $sub_key => $sub_item)
				{
					if ($offset != 0 AND $offset >= $offset_skip)
					{
						$offset_skip++;
						continue;
					}

					$return[$key][$sub_key] = $sub_item;

					//if we have enough, end
					if (++$count >= $limit)
					{
						break(2);
					}
				}
			}
			else
			{
				if ($offset != 0 AND $offset >= $offset_skip)
				{
					$offset_skip++;
					continue;
				}

				$return[$key] = $value;

				if (++$count >= $limit)
				{
					break;
				}
			}
		}

		//set back to the correct order because we will reverse later if needed
		if ($direction == 'prior')
		{
			foreach ($return as $key => $value)
			{
				if (is_array($value))
				{
					ksort($value);
				}
			}

			ksort($return);
		}

		return $return;
	}
	// END apply_occurrences_limit


	// --------------------------------------------------------------------

	/**
	 * count_event_results
	 *
	 * @param	array	$array	Event array
	 * @return	int
	 */

	public function count_event_results($array)
	{
		$count		= 0;

		foreach ($array as $ymd => $ymd_data)
		{
			// Sort by start hour
			if (isset($ymd_data['all_day']))
			{
				$all_day = $ymd_data['all_day'];
				unset($ymd_data['all_day']);
				ksort($ymd_data);
				$ymd_data['all_day'] = $all_day;
			}

			foreach ($ymd_data as $sh => $sh_data)
			{
				if ($sh == 'all_day')
				{
					$count += count($sh_data);

					continue;
				}
				else
				{
					foreach ($sh_data as $sm => $sm_data)
					{
						foreach ($sm_data as $eh => $eh_data)
						{
							foreach ($eh_data as $em => $em_data)
							{
								$count += count($em_data);
							}
						}
					}
				}
			}
		}

		return $count;
	}
	//END count_event_results

	// --------------------------------------------------------------------

	/**
	 * Count the number of events
	 *
	 * @param	array	$arr		Event data
	 * @param	array	$all_day	All day events [optional]
	 * @return	int
	 */

	protected function count_events($ymd, $event_array, $all_day = array(), $counted_type = 'day', &$event_data = NULL)
	{
		$arr 			= $event_array[$ymd];

		$count 			= 0;

		$all_day_count 	= 0;

		//adds seperate all day events to the internal ones
		foreach ($all_day as $day_array)
		{
			if ($ymd <= $day_array['end_ymd'])
			{
				if ( isset($day_array['id']) AND ! array_key_exists($day_array['id'], $this->counted[$counted_type]))
				{
					$count++;
					$this->counted[$counted_type][$day_array['id']] = $day_array['id'];
				}
			}
		}

		foreach ($arr as $day => $events)
		{
			if ($day == 'all_day')
			{
				// -------------------------------------
				//  If we are here, it means the $all_day array didn't get used,
				//  which probably means there was no {events}{/events} tag pair,
				//  which means we're not actually showing any events, but the
				//  user may still want to know how many events there are on a
				//  given day. Hence the following.
				// -------------------------------------

				//all day events only get counted once per day
				foreach($events as $event_id)
				{
					$count++;
				}
			}
			else
			{
				foreach ($events as $key => $value)
				{
					if (is_array($value))
					{
						foreach($value as $event_ids)
						{
							if (is_array($event_ids))
							{
								foreach($event_ids as $event_id)
								{
									if (is_numeric($event_id))
									{

										if ($counted_type != 'day' OR
											! array_key_exists($event_id, $this->counted[$counted_type]))
										{
											$count++;
											$this->counted[$counted_type][$event_id] = $event_id;
										}
										//multiple occurrences of the SAME event in the same day that ARENT all day
										//get counted more than once
										else if ($counted_type == 'day' )
										{
											$count++;
										}
									}
									//this is more likely
									else if (is_array($event_id))
									{
										foreach ($event_id as $sub_event_id)
										{
											$count++;
										}
									}
								}
							}
						}
					}
				}
			}
		}

		return $count;
	}
	/* END count_events() */

	// --------------------------------------------------------------------

	/**
	 * Swap out variables
	 *
	 * @param	array	$vars		Array of variable data
	 * @param	string	$tagdata	The affected tagdata
	 * @return	string
	 */

	protected function swap_vars($vars, $tagdata)
	{
		if (isset($this->event_timeframe_total))
		{
			$vars['single']['event_timeframe_total'] = $this->event_timeframe_total;
		}

		if (isset($vars['date']))
		{
			// -------------------------------------
			//  As single vars, date variables use the YMD format
			// -------------------------------------

			foreach ($vars['date'] as $k => $date)
			{
				// No data? Dump the date.
				if ($date == '')
				{
					unset($vars['date'][$k]);
				}

				// Don't overwrite something that already exists
				elseif (! isset($vars['single'][$k]))
				{
					if (is_array($date))
					{
						if (substr($k, -4) == 'time')
						{
							$vars['single'][$k] = $date['time'];
						}
						elseif(isset($date['ymd']))
						{
							$vars['single'][$k] = $date['ymd'];
						}
						else
						{
							$vars['single'][$k] = $date;
						}
					}
					else
					{
						$vars['single'][$k] = $date;
					}
				}
			}

			if ( ! empty($vars['date']) AND
				 strpos($tagdata, ' format=') !== FALSE)
			{
				preg_match_all(
					'#'.LD.'([a-zA-Z_]+)\s+format\s*=\s*([\'|\"|]|&\#39;|&\#34;)(.*?)\\2'.RD.'#s',
					$tagdata,
					$matches
				);

				foreach ($matches[0] as $k => $match)
				{
					if (isset($vars['date'][$matches[1][$k]]))
					{
						// -------------------------------------
						//  The date is a YMD
						// -------------------------------------

						if ( ! is_array($vars['date'][$matches[1][$k]]))
						{
							$this->CDT->change_ymd($vars['date'][$matches[1][$k]]);
						}

						// -------------------------------------
						//  Nope, it's already a date
						// -------------------------------------

						else
						{
							$this->CDT->change_datetime(
								$vars['date'][$matches[1][$k]]['year'],
								$vars['date'][$matches[1][$k]]['month'],
								$vars['date'][$matches[1][$k]]['day'],
								$vars['date'][$matches[1][$k]]['hour'],
								$vars['date'][$matches[1][$k]]['minute']
							);
						}

						$tagdata = str_replace(
							$match,
							$this->cdt_format_date_string(
								$this->CDT->datetime_array(),
								$matches[3][$k],
								'%'
							),
							$tagdata
						);
					}
				}
			}
		}

		if (isset($vars['single']))
		{
			// -------------------------------------
			//  Convert y and n to true and false
			// -------------------------------------

			foreach ($vars['single'] as $k => $v)
			{
				if ($v === 'n' OR $v === 'y')
				{
					$vars['single'][$k] = ($v === 'n') ? FALSE : TRUE;
				}
			}

			if (isset($vars['conditional']))
			{
				$tagdata = ee()->functions->prep_conditionals($tagdata, array_merge($vars['conditional'], $vars['single']));
			}
			else
			{
				$tagdata = ee()->functions->prep_conditionals($tagdata, $vars['single']);
			}

			foreach ($vars['single'] as $name => $val)
			{
				if (strpos($tagdata, LD.$name) !== FALSE)
				{
					if (is_array($val))
					{
						foreach ($val as $vk => $vv)
						{
							$tagdata = str_replace(LD.$name.'_'.$vk.RD, $vv, $tagdata);
						}
					}
					else
					{
						$tagdata = str_replace(LD.$name.RD, $val, $tagdata);
					}
				}
			}
		}
		elseif (isset($vars['conditional']))
		{
			$tagdata = ee()->functions->prep_conditionals($tagdata, $vars['conditional']);
		}

		if (isset($vars['pair']))
		{
			foreach ($vars['pair'] as $name => $data)
			{
				if (strpos($tagdata, LD.$name) !== FALSE AND preg_match_all("/".LD.$name.'(.*?)'.RD."(.+?)".LD.preg_quote(T_SLASH, '/').$name.RD."/s", $tagdata, $matches, PREG_SET_ORDER))
				{
					foreach ($matches as $match)
					{
						$str = '';
						$tdata = $match['2'];

						// Fetch parameters attached to this variable pair.
						$params = ee()->functions->assign_parameters($match['1']);

						foreach($data as $key => $array)
						{
							$out = '';

							if (! is_array($array)) continue;

							// -------------------------------------
							//  Special handling for day_of_week
							// -------------------------------------

							if ($key == 'day_of_week')
							{
								$days = array('U','M','T','W','R','F','S');

								foreach ($array as $k => $v)
								{
									if (in_array($v, $days))
									{
										$out .= str_replace(LD.$key.RD, lang('day_'.array_search($v, $days).'_full'), $tdata);
									}
								}
							}

							// -------------------------------------
							//  Special handling for day_of_month
							// -------------------------------------

							elseif ($key == 'day_of_month')
							{
								$days = array(1,2,3,4,5,6,7,8,9,'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V');

								foreach ($array as $k => $v)
								{
									if (in_array($v, $days))
									{
										$out .= str_replace(LD.$key.RD, array_search($v, $days)+1, $tdata);
									}
								}
							}

							// -------------------------------------
							//  Special handling for month_of_year
							// -------------------------------------

							elseif ($key == 'month_of_year')
							{
								$months = array(1,2,3,4,5,6,7,8,9,'A','B','C');

								foreach ($array as $k => $v)
								{
									if (in_array($v, $months))
									{
										$out .= str_replace(LD.$key.RD, lang('month_'.array_search($v, $months).'_full'), $tdata);
									}
								}
							}

							else
							{
								foreach ($array as $k => $v)
								{
									// -------------------------------------
									//  Convert y and n to true and false
									// -------------------------------------

									if ($v === 'n' OR $v === 'y')
									{
										$v = ($v === 'n') ? FALSE : TRUE;
									}

									$out .= str_replace(LD.$key.RD, $v, $tdata);
								}
							}
						}

						$str = $out;

						// Backspace
						if (isset($params['backspace']))
						{
							$str = substr($out, 0, -$params['backspace']);
						}

						$tagdata = preg_replace("/".LD.$name.$match['1'].RD.".+?".LD.preg_quote(T_SLASH, '/').$name.RD."/s", $str, $tagdata);
					}
				}
			}
		}

		return $tagdata;
	}
	/* END swap_vars() */

	// --------------------------------------------------------------------

	/**
	 * Shared processing for events() and cal() methods
	 *
	 * @return	null
	 */

	protected function process_events_params()
	{
		// -------------------------------------
		//  Convert calendar_name to calendar_id
		// -------------------------------------

		if ($this->P->value('calendar_id') == '' AND $this->P->value('calendar_name') != '')
		{
			$ids = $this->data->get_calendar_id_from_name($this->P->value('calendar_name'));
			if (! empty($ids))
			{
				$this->P->set('calendar_id', implode('|', $ids));
			}
			else
			{
				$this->P->set('calendar_id', NULL);
			}
		}

		// -------------------------------------
		//  Convert event_name to event_id
		// -------------------------------------

		if ($this->P->value('event_id') == '' AND $this->P->value('event_name') != '')
		{
			$ids = $this->data->get_event_id_from_name($this->P->value('event_name'));
			$this->P->set('event_id', implode('|', $ids));
		}

		// -------------------------------------
		//  Determine which calendars this user has permission to view
		//  and modify the parameters accordingly.
		// -------------------------------------

		// TODO

		// -------------------------------------
		//  Set the first day of the week
		// -------------------------------------

		$this->first_day_of_week = ($this->P->value('first_day_of_week') !== FALSE) ? $this->P->value('first_day_of_week') : $this->first_day_of_week;

		// -------------------------------------
		//  Set the date range based on the show_x parameters
		// -------------------------------------

		$adjust = ($this->P->value('date_range_start') === FALSE AND $this->P->value('date_range_end') === FALSE) ? FALSE : TRUE;

		$this->set_date_range_parameters($adjust);

		// -------------------------------------
		//  Prepare pad_short_weeks
		// -------------------------------------

		$this->P->set( 'pad_short_weeks', $this->check_yes( $this->P->value('pad_short_weeks') ) );

	}
	/* END process_events_params() */

	// --------------------------------------------------------------------

	/**
	 * Add parameters
	 *
	 * @param	array	$params [optional]	Array of parameters
	 * @return	null
	 */

	protected function add_parameters($params = array())
	{
		foreach ($params as $param)
		{
			$this->add_parameter($param['name'], $param);
		}
	}
	/* END add_parameters() */

	// --------------------------------------------------------------------

	/**
	 * Add parameter
	 *
	 * @access	private
	 * @param	string	$name	Parameter name
	 * @param	array	$details	Parameter details
	 * @return	null
	 */

	protected function add_parameter($name, $details)
	{
		$this->P->add_parameter($name, $details);
	}
	/* END add_parameter() */

	// --------------------------------------------------------------------

	/**
	 * Set date range parameters based on show_x values.
	 *
	 * @return	null
	 */

	protected function set_date_range_parameters($adjust_empty_vals = TRUE)
	{
		if ($this->P->value('date_range_start') !== FALSE AND
			$this->P->value('date_range_end') === FALSE)
		{
			$this->CDT->change_ymd($this->P->value('date_range_start', 'ymd'));

			if ($this->P->value('show_years') > 0 OR
				$this->P->value('show_months') > 0 OR
				$this->P->value('show_weeks') > 0 OR
				$this->P->value('show_days') > 0)
			{
				$this->P->set('date_range_end', $this->P->params['date_range_start']['value']);

				if ($this->P->value('show_years') > 0)
				{
					 $this->CDT->add_year($this->P->value('show_years') -1, TRUE, 'forward');
				}
				else if ($this->P->value('show_months') > 0)
				{
					$this->CDT->add_month(($this->P->value('show_months') - 1), TRUE, 'forward');
				}
				else if ($this->P->value('show_weeks') > 0)
				{
					//get the current day of week (1-7)
					$dow		= $this->CDT->get_day_of_week(
						$this->CDT->year,
						$this->CDT->month,
						$this->CDT->day
					) + 1;

					//first day of week
					$fdow 		= $this->get_first_day_of_week();

					//we need to calculate 2 weeks backwards, counting today (-1) removing the remaining
					//days of the week, MINUS the first day of the week...
					//this will give is first day of the week, x weeks forward, counting this week as a full
					//week regardless of the day

					$this->CDT->add_day(( 7 * $this->P->value('show_weeks') ) - ($dow - $fdow));
				}
				else if ($this->P->value('show_days') > 0)
				{
					$this->CDT->add_day($this->P->value('show_days') - 1);
				}

				$end = $this->CDT->datetime_array();

				$end["time"]	= "2359";
				$end["hour"]	= '23';
				$end["minute"]	= '59';

				$this->P->set('date_range_end', $end);
			}
		}
		elseif ($this->P->value('date_range_start') === FALSE AND
				$this->P->value('date_range_end') !== FALSE)
		{
			$this->CDT->change_ymd($this->P->value('date_range_end', 'ymd'));

			if ($this->P->value('show_years') > 0 OR
				$this->P->value('show_months') > 0 OR
				$this->P->value('show_weeks') > 0 OR
				$this->P->value('show_days') > 0)
			{
				if ($this->P->value('show_years') > 0)
				{
					$this->CDT->add_year(-$this->P->value('show_years') + 1, TRUE, 'backward');
				}
				else if ($this->P->value('show_months') > 0)
				{
					$this->CDT->add_month(-$this->P->value('show_months') + 1, TRUE, 'backward');
				}
				else if ($this->P->value('show_weeks') > 0)
				{
					//get the current day of week (1-7)
					$dow		= $this->CDT->get_day_of_week(
						$this->CDT->year,
						$this->CDT->month,
						$this->CDT->day
					) + 1;

					//first day of week
					$fdow 		= $this->get_first_day_of_week();

					//we need to calculate 2 weeks backwards, counting today (+1) removing the remaining
					//days of the week, MINUS the first day of the week...
					//this will give is first day of the week, x weeks back, counting this week as a full
					//week regardless of the day
					$this->CDT->add_day(( -7 * $this->P->value('show_weeks') ) + 1 + (7 - ($dow - $fdow)));
				}
				else if ($this->P->value('show_days') > 0)
				{
					$this->CDT->add_day(-$this->P->value('show_days') + 1);
				}

				$this->P->set('date_range_start', $this->CDT->datetime_array());
			}
		}
		elseif ($adjust_empty_vals === TRUE AND
				$this->P->value('date_range_start') === FALSE AND
				$this->P->value('date_range_end') === FALSE)
		{
			$this->P->set('date_range_start', $this->CDT->datetime_array());

			if ($this->P->value('show_years') > 0 OR
				$this->P->value('show_months') > 0 OR
				$this->P->value('show_weeks') > 0 OR
				$this->P->value('show_days') > 0)
			{
				if ($this->P->value('show_years') > 0)
				{
					 $this->CDT->add_year($this->P->value('show_years') -1, TRUE, 'forward');
				}
				else if ($this->P->value('show_months') > 0)
				{
					$this->CDT->add_month(($this->P->value('show_months') - 1), TRUE, 'forward');
				}
				else if ($this->P->value('show_weeks') > 0)
				{
					//get the current day of week (1-7)
					$dow		= $this->CDT->get_day_of_week(
						$this->CDT->year,
						$this->CDT->month,
						$this->CDT->day
					) + 1;

					//first day of week
					$fdow 		= $this->get_first_day_of_week();

					//we need to calculate 2 weeks backwards, counting today (-1) removing the remaining
					//days of the week, MINUS the first day of the week...
					//this will give is first day of the week, x weeks forward, counting this week as a full
					//week regardless of the day

					$this->CDT->add_day(( 7 * $this->P->value('show_weeks') ) - ($dow - $fdow));
				}
				else if ($this->P->value('show_days') > 0)
				{
					$this->CDT->add_day($this->P->value('show_days') - 1);
				}

				$end = $this->CDT->datetime_array();

				$end["time"]	= "2359";
				$end["hour"]	= '23';
				$end["minute"]	= '59';

				$this->P->set('date_range_end', $end);
			}
			else
			{
				$end = $this->P->params['date_range_start']['value'];

				$end["time"]	= "2359";
				$end["hour"]	= '23';
				$end["minute"]	= '59';

				$this->P->set('date_range_end', $end);

				//$this->P->set('date_range_end', $this->P->value('date_range_start'));
			}
		}
	}
	/* END set_date_range_parameters() */

	// --------------------------------------------------------------------

	/**
	 * Get a Calendar_datetime instance
	 *
	 * @access	private
	 * @return
	 */

	public function load_calendar_datetime()
	{
		if ( ! class_exists('Calendar_datetime'))
		{
			require_once CALENDAR_PATH.'calendar.datetime.php';
		}

		if (! isset($this->CDT))
		{
			$this->CDT = new Calendar_datetime();
			$this->first_day_of_week = $this->get_first_day_of_week();
		}
	}
	/* END load_calendar_datetime() */

	// --------------------------------------------------------------------

	public function load_calendar_parameters()
	{
		if ( ! class_exists('Calendar_parameters'))
		{
			require_once CALENDAR_PATH.'calendar.parameters.php';
		}

		if (! isset($this->P))
		{
			$this->load_calendar_datetime();

			$this->P = new Calendar_parameters();
		}
	}
	/* END load_calendar_parameters() */

	// --------------------------------------------------------------------

	/**
	 * Get the first day of the week
	 *
	 * @access	private
	 * @return	int
	 */

	public function get_first_day_of_week()
	{
		$this->first_day_of_week = $this->data->get_first_day_of_week($this->first_day_of_week);
		return $this->first_day_of_week;
	}
	/* END get_first_day_of_week() */

	// --------------------------------------------------------------------

	/**
	 * Get occurrence variables
	 *
	 * @access	private
	 * @param	int		$id		ID
	 * @param	object	$event	Calendar_event object
	 * @param	int		$ymd	YMD
	 * @return	array
	 */

	protected function get_occurrence_vars($id, $event, $ymd, $time = '00000000')
	{
		$vars 								= array();
		$vars['single'] 					= (isset($event->occurrences[$ymd][$time])) ?
												array_merge($event->default_data, $event->occurrences[$ymd][$time]) :
												$event->default_data;

		// -------------------------------------
		//  Time time tickin'...
		// -------------------------------------

		$vtime['hour']						= ($vars['single']['start_time'] == 0) ?
												00 : substr($vars['single']['start_time'], 0, strlen($vars['single']['start_time']) - 2);
		$vtime['minute']					= ($vars['single']['start_time'] == 0) ?
												00 : substr($vars['single']['start_time'], -2);

		$cal_data							= $this->data->fetch_calendars_basics(
													$this->data->get_site_id(),
													$event->default_data['calendar_id']
											  );

		$time_format						= (/*isset($cal_data[0]) AND*/
												$cal_data[0]['time_format'] != '') ?
													$cal_data[0]['time_format'] :
												$this->time_format;

		$vars['single']['start_time']		= $this->cdt_format_date_string($event->dates[$ymd][$time]['date'], $time_format);
		$vars['single']['start_hour']		= $this->cdt_format_date_string($event->dates[$ymd][$time]['date'], 'H');
		$vars['single']['start_minute']		= $this->cdt_format_date_string($event->dates[$ymd][$time]['date'], 'i');
		$vars['single']['end_time']			= $this->cdt_format_date_string($event->dates[$ymd][$time]['end_date'], $time_format);
		$vars['single']['end_hour']			= $this->cdt_format_date_string($event->dates[$ymd][$time]['end_date'], 'H');
		$vars['single']['end_minute']		= $this->cdt_format_date_string($event->dates[$ymd][$time]['end_date'], 'i');
		$vars['single']['first_day']		= ($ymd == $event->default_data['start_date']) ? TRUE : FALSE;
		$vars['single']['last_day']			= ($ymd == $event->default_data['end_date']) ? TRUE : FALSE;
		$vars['single']['duration_minutes']	= (isset($event->dates[$ymd])) ?
												$event->dates[$ymd][$time]['duration']['minutes'] :
												$event->default_data['duration']['minutes'];
		$vars['single']['duration_hours']	= (isset($event->dates[$ymd])) ?
												$event->dates[$ymd][$time]['duration']['hours'] :
												$event->default_data['duration']['hours'];
		$vars['single']['duration_days']	= (isset($event->dates[$ymd])) ?
												$event->dates[$ymd][$time]['duration']['days'] :
												$event->default_data['duration']['days'];
		$vars['single']['all_day']			= $event->dates[$ymd][$time]['all_day'];
		$vars['single']['multi_day']		= $event->dates[$ymd][$time]['multi_day'];

		foreach ($vars['single'] as $k => $v)
		{
			$key = 'event_'.$k;
			if (strpos($k, '_date') !== FALSE)
			{
				if ($k == 'start_date')
				{
					$vars['date'][$key] = $event->dates[$ymd][$time]['date'];
				}
				elseif ($k == 'end_date')
				{
					$vars['date'][$key] = $event->dates[$ymd][$time]['end_date'];
				}
				else
				{
					$vdate 				= $this->CDT->ymd_to_array($v);

					$vars['date'][$key] = $this->CDT->change_datetime(
						$vdate['year'],
						$vdate['month'],
						$vdate['day'],
						$vtime['hour'],
						$vtime['minute']
					);

					$this->CDT->reset();
				}
			}
			else
			{
				$vars['single'][$key] = $v;
			}
			unset($vars['single'][$k]);
		}

		return $vars;
	}
	/* END get_occurrence_vars() */


	// --------------------------------------------------------------------

	/**
	 * Fix Conditional Date Escaping (EE 2.9)
	 *
	 * EE 2.9 did all sorts of stuff with conditionals and it breaks a lot.
	 * This undoes the weird nested tag escaping and fixes parsing for us.
	 * With tags and date formats.
	 *
	 * @access	protected
	 * @param	string	$tag		starting word for date variable tags (occurrence_start_date = 'occurrence')
	 * @param	string	$tagdata	incoming tagdata to fix
	 * @return	string				fixed tagdata or passthrough if no fixes found
	 */

	protected function fix_conditional_date_escaping($tag, $tagdata)
	{
		preg_match_all(
			"/\{(" . preg_quote($tag, '/') . "[\w\_]+) format=(\\\')([^\}]+)?\\2\}/i",
			$tagdata,
			$matches,
			PREG_SET_ORDER
		);

		if ( ! empty($matches))
		{
			foreach ($matches as $match_set)
			{
				$tagdata = str_replace(
					$match_set[0],
					"{" . $match_set[1] . " format='" . $match_set[3] . "'}",
					$tagdata
				);
			}
		}

		return $tagdata;
	}
	//END fix_conditional_date_escaping


	// --------------------------------------------------------------------

	/**
	 * Prepare occurrences output
	 *
	 * @param	string	$tagdata			Tagdata
	 * @param	array	$data				Array of data
	 * @param	bool	$include_default	Include the default entry_id when fetching weblog data?
	 * @return	string
	 */

	protected function prep_occurrences_output($tagdata, $data, $include_default = TRUE)
	{
		$tagdata = $this->fix_conditional_date_escaping('occurrence', $tagdata);

		// -------------------------------------
		//  Ensure $this->CDT is ready
		// -------------------------------------

		$this->load_calendar_datetime();

		if (empty($data->dates)) return $tagdata;

		$output = '';

		// -------------------------------------
		//  Put the items in order
		// -------------------------------------

		foreach ($data->dates as $ymd => $times)
		{
			ksort($data->dates[$ymd]);
		}

		ksort($data->dates);

		//--------------------------------------------
		//	order arrays for later multi sorting
		//--------------------------------------------

		$calendar_orderby_params = array(
			'event_start_date',
			'occurrence_start_date'
		);

		$orders 				= explode('|', $this->P->value('orderby'));
		$sorts 					= explode('|', $this->P->value('sort'));
		$calendar_orders 		= array();
		$calendar_order_data 	= array();

		foreach ($orders as $k => $order)
		{
			if (in_array($order, $calendar_orderby_params))
			{
				$sort = (isset($sorts[$k])) ? $sorts[$k] : 'desc';
				$calendar_orders[$order] = $sort;
				$calendar_order_data[$order] = array();
			}
		}

		// -------------------------------------
		//  Are prior_ or upcoming_ limits in effect?
		// -------------------------------------

		$prior_limit	= ($this->P->value('prior_occurrences_limit') !== FALSE) ?
							$this->P->value('prior_occurrences_limit') : FALSE;
		$upcoming_limit	= ($this->P->value('upcoming_occurrences_limit') !== FALSE) ?
							$this->P->value('upcoming_occurrences_limit') : FALSE;

		if ( $prior_limit !== FALSE OR $upcoming_limit !== FALSE)
		{
			$priors			= array();
			$upcoming		= array();

			//set date to current
			$this->CDT->set_date_to_now();

			foreach ($data->dates as $ymd => $info)
			{
				//yeterday or before
				if ($ymd < $this->CDT->ymd)
				{
					$priors[$ymd] = $info;
				}
				//today
				elseif ($ymd == $this->CDT->ymd)
				{
					//we need to check all hours and minutes :/
					foreach($info as $time => $time_data)
					{
						$temp_end_hour 		= ($time_data['end_date']['hour'] + (($time_data['end_date']['pm']) ? 12 : 0));
						$temp_end_minute 	= $time_data['end_date']['minute'];

						//if this is an all day item and we are still in this day, its current
						if ($time_data['all_day'] == TRUE)
						{
							$upcoming[$ymd][$time] = $time_data;
						}
						//is it still up coming in hours and minutes?
						else if ($temp_end_hour > $this->CDT->hour() OR
								 ( $temp_end_hour == $this->CDT->hour() AND
								   $temp_end_minute > $this->CDT->minute()
								 )
								)
						{
							$upcoming[$ymd][$time] = $time_data;
						}
						//it must have already passed
						else
						{
							$priors[$ymd][$time] = $time_data;
						}
					}
				}
				//tomorrow or later
				else
				{
					$upcoming[$ymd] = $info;
				}
			}

			//slice out our limits
			if ($prior_limit !== FALSE)
			{
				$priors = $this->apply_occurrences_limit($priors, $prior_limit, 0, 'prior');

				//$priors = array_slice(array_reverse($priors, TRUE), 0, $prior_limit, TRUE);
			}

			if ($upcoming_limit !== FALSE)
			{
				$upcoming = $this->apply_occurrences_limit($upcoming, $upcoming_limit, 0, 'upcoming');

				//$upcoming = array_slice($upcoming, 0, $upcoming_limit, TRUE);
			}

			//this has to be recursive because we can have the same $YMD happening if its today's date
			$data->dates = array_merge_recursive($priors, $upcoming);
		}

		//--------------------------------------------
		//	get default data from parent entry_id
		//--------------------------------------------

		if ( ! isset($this->cache['entry_info']))
		{
			$this->cache['entry_info'] = array();
		}

		//we need some extra data about this entry so we can parse occurrences items
		if ( ! isset($this->cache['entry_info'][$data->default_data['entry_id']]))
		{
			$entry_info_query = ee()->db->query(
				"SELECT 	ct.title, ct.url_title, ct.author_id, ct.status, m.screen_name, m.username
				 FROM		{$this->sc->db->channel_titles} as ct
				 LEFT JOIN	exp_members as m
				 ON			ct.author_id = m.member_id
				 WHERE		ct.entry_id = " . ee()->db->escape_str($data->default_data['entry_id'])
			);

			if ($entry_info_query->num_rows() > 0)
			{
				$this->cache['entry_info'][$data->default_data['entry_id']] = $entry_info_query->row_array();
			}
			else
			{
				$this->cache['entry_info'][$data->default_data['entry_id']] = array();
			}
		}

		$entry_info = $this->cache['entry_info'][$data->default_data['entry_id']];

		// -------------------------------------
		//  Grab entry IDs from the occurrences
		// -------------------------------------

		foreach ($data->occurrences as $ymd => $times)
		{
			foreach ($times as $time => $info)
			{
				if ($info['entry_id'] != $data->default_data['entry_id'] AND ! isset($ids[$info['entry_id']]))
				{
					$ids[$ymd] = $info['entry_id'];
				}
			}
		}

		$tagdatas = array();
		$tagdatas[$data->default_data['entry_id']] = $tagdata;

		//this will probably only happen for edited occurrences

		if (! empty($ids))
		{
			// -------------------------------------
			//  Add the "parent" entry id for default data
			// -------------------------------------

			$ids[0] = $data->default_data['entry_id'];

			//ee()->TMPL_orig = clone ee()->TMPL;

			$tagdata = LD.'occurrences id="'.LD.'entry_id'.RD.'"'.RD.$tagdata.LD.T_SLASH.'occurrences'.RD;

			//	----------------------------------------
			//	Invoke Channel class
			//	----------------------------------------

			if ( ! class_exists('Channel') )
			{
				require PATH_MOD.'/channel/mod.channel.php';
			}

			$channel = new Channel();

			//need to remove limit here so huge amounts of events work
			$channel->limit = 1000000;

			// --------------------------------------------
			//  Invoke Pagination for EE 2.4 and Above
			// --------------------------------------------

			$channel = $this->add_pag_to_channel($channel);

			// -------------------------------------
			//  Prepare parameters
			// -------------------------------------

			ee()->TMPL->tagparams['entry_id']	= implode('|', $ids);
			ee()->TMPL->tagparams['weblog']		= CALENDAR_EVENTS_CHANNEL_NAME;
			ee()->TMPL->tagparams['channel']	= CALENDAR_EVENTS_CHANNEL_NAME;

			// -------------------------------------
			//  Pre-process related data
			// -------------------------------------

			if (version_compare($this->ee_version, '2.6.0', '<'))
			{
				ee()->TMPL->tagdata = ee()->TMPL->assign_relationship_data( $tagdata );
			}

			ee()->TMPL->var_single = array_merge( ee()->TMPL->var_single, ee()->TMPL->related_markers );
			ee()->TMPL->var_single['entry_id'] = 'entry_id';

			// -------------------------------------
			//  Execute needed methods
			// -------------------------------------

			$channel->fetch_custom_channel_fields();

			$channel->fetch_custom_member_fields();

			// --------------------------------------------
			//  Pagination Tags Parsed Out
			// --------------------------------------------

			$channel = $this->fetch_pagination_data($channel);

			// -------------------------------------
			//  Add occurrence_ prefix to custom fields
			// -------------------------------------

			foreach ($channel->cfields as $sid => $fields)
			{
				foreach ($fields as $name => $fid)
				{
					$channel->cfields[$sid]['occurrence_'.$name] = $fid;
					unset($channel->cfields[$sid][$name]);
				}
			}

			// -------------------------------------
			//  Querification
			// -------------------------------------

			$channel->build_sql_query();

			if ($channel->sql == '')
			{
				return $this->no_results();
			}

			$channel->query = ee()->db->query($channel->sql);

			if ($channel->query->num_rows() == 0)
			{
				//ee()->TMPL->log_item('Calendar: Channel module says no results, bailing');

				return $this->no_results();
			}

			$channel->query->result	= $channel->query->result_array();

			// -------------------------------------
			//  Trim IDs and build events
			// -------------------------------------

			$new_ids = array();

			foreach ($channel->query->result as $k => $row)
			{
				$new_ids[$row['entry_id']] = $row['entry_id'];
			}

			if (empty($new_ids))
			{
				return $this->no_results();
			}

			$occurrence_data = $this->data->fetch_event_occurrences($data->default_data['entry_id']);

			// -------------------------------------
			//  Turn these IDs into events
			// -------------------------------------

			$events = array();

			if ( ! class_exists('Calendar_event'))
			{
				require_once CALENDAR_PATH.'calendar.event.php';
			}

			$calendars = array();

			$start_ymd = ($this->P->value('date_range_start') !== FALSE) ? $this->P->value('date_range_start', 'ymd') : '';
			$end_ymd = ($this->P->value('date_range_end') !== FALSE) ? $this->P->value('date_range_end', 'ymd') : '';

			foreach ($occurrence_data[$data->default_data['entry_id']] as $times)
			{
				foreach ($times as $k => $odata)
				{
					$temp = new Calendar_event($odata, $start_ymd, $end_ymd);
					if (! empty($temp->dates))
					{
						$temp->prepare_for_output();
						$events[$odata['entry_id']] = $temp;
						$calendars[$events[$odata['entry_id']]->default_data['calendar_id']] = array();
					}
				}
			}

			// -------------------------------------
			//  Fetch information about the calendars
			// -------------------------------------

			$calendars = $this->data->fetch_calendar_data_by_id(array_keys($calendars));

			// -------------------------------------
			//  Prep variable aliases
			// -------------------------------------

			$variables = array(
				'title'			=> 'occurrence_title',
				'url_title'		=> 'occurrence_url_title',
				'entry_id'		=> 'occurrence_id',
				'author_id'		=> 'occurrence_author_id',
				'author'		=> 'occurrence_author',
				'status'		=> 'occurrence_status'
			);

			//custom variables with the letters 'url' are borked in
			//EE 2.6. Bug reported, but this should fix.
			//https://support.ellislab.com/bugs/detail/19337
			if (version_compare($this->ee_version, '2.6.0', '>='))
			{
				$variables['url_title'] = 'occurrence_borked_title';

				ee()->TMPL->var_single['occurrence_borked_title'] = 'occurrence_borked_title';

				unset(ee()->TMPL->var_single['occurrence_url_title']);

				ee()->TMPL->tagdata = str_replace(
					array(
						LD . 'occurrence_url_title' . RD,
						'"occurrence_url_title"',
						"'occurrence_url_title'"
					),
					array(
						LD . 'occurrence_borked_title' . RD,
						'"occurrence_borked_title"',
						"'occurrence_borked_title'"

					),
					ee()->TMPL->tagdata
				);
			}

			// -------------------------------------
			//  Add variables to the query result
			// -------------------------------------

			foreach ($channel->query->result as $k => $row)
			{
				$channel->query->result[$k]['author'] = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];

				$entry_id = $row['entry_id'];

				if (isset($ids[0]) AND $entry_id == $ids[0])
				{
					$events[$entry_id] = $data;
				}
				elseif (! isset($events[$entry_id]))
				{
					unset($channel->query->result[$k]);
					continue;
				}

				// -------------------------------------
				//  Alias
				// -------------------------------------

				foreach ($variables as $old => $new)
				{
					$channel->query->result[$k][$new] = $channel->query->result[$k][$old];
				}

				// -------------------------------------
				//  Occurrence variables
				// -------------------------------------

				foreach ($events[$entry_id]->default_data as $key => $val)
				{
					if (! is_array($val))
					{
						if ($val == 'y' OR $val == 'n')
						{
							$channel->query->result[$k]['occurrence_'.$key] = ($val == 'y') ? TRUE : FALSE;
						}
						else
						{
							$channel->query->result[$k]['occurrence_'.$key] = $val;
						}
					}
					else
					{
						foreach ($val as $vkey => $vval)
						{
							if ($vval === 'y' OR $vval === 'n')
							{
								$channel->query->result[$k]['occurrence_'.$key.'_'.$vkey] = ($vval == 'y') ? TRUE : FALSE;
							}
							else
							{
								$channel->query->result[$k]['occurrence_'.$key.'_'.$vkey] = $vval;
							}
						}
					}
				}
			}

			unset($CDT);

			//	----------------------------------------
			//	Redeclare
			//	----------------------------------------
			//	We will reassign the $channel->query->result with our
			//	reordered array of values. Thank you PHP for being so fast with array loops.
			//	----------------------------------------

			$channel->query->result_array = $channel->query->result;

			// --------------------------------------------
			//  Typography
			// --------------------------------------------

			ee()->load->library('typography');
			ee()->typography->initialize();
			ee()->typography->convert_curly = FALSE;


			$channel->fetch_categories();

			// -------------------------------------
			//  Parse
			// -------------------------------------

			//ee()->TMPL->log_item('Calendar: Parsing, via channel module');

			$channel->parse_channel_entries();

			// -------------------------------------
			//  Paginate
			// -------------------------------------

			//$channel->add_pagination_data();

			// -------------------------------------
			//  Related entries
			// -------------------------------------

			if (version_compare($this->ee_version, '2.6.0', '<'))
			{
				if (count(ee()->TMPL->related_data) > 0 AND
					count($channel->related_entries) > 0)
				{
					$channel->parse_related_entries();
				}

				if (count(ee()->TMPL->reverse_related_data) > 0 AND
					count($channel->reverse_related_entries) > 0)
				{
					$channel->parse_reverse_related_entries();
				}
			}


			// -------------------------------------
			//  Send 'em home
			// -------------------------------------

			$tagdata = $channel->return_data;

			// -------------------------------------
			//	put these back in case someone needs
			//	them
			// -------------------------------------
			//	url title is manually parsed later
			//	in the code here, so we don't
			//	want to hose it
			// -------------------------------------

			//custom variables with the letters 'url' are borked in
			//EE 2.6. Bug reported, but this should fix.
			//https://support.ellislab.com/bugs/detail/19337
			if (version_compare($this->ee_version, '2.6.0', '>='))
			{
				$tagdata = str_replace(
					array(
						LD . 'occurrence_borked_title' . RD,
						'"occurrence_borked_title"',
						"'occurrence_borked_title'"

					),
					array(
						LD . 'occurrence_url_title' . RD,
						'"occurrence_url_title"',
						"'occurrence_url_title'"
					),
					$tagdata
				);
			}

			// -------------------------------------
			//  Collect the tagdata
			// -------------------------------------

			preg_match_all("/".LD.'occurrences id="(\d+)"'.RD.'(.*?)'.LD.preg_quote(T_SLASH, '/').'occurrences'.RD.'/s', $tagdata, $matches);
			foreach ($matches[1] as $k => $id)
			{
				$tagdatas[$id] = $matches[2][$k];
			}

			//ee()->TMPL = ee()->TMPL_orig;
		}

		// -------------------------------------
		//  Date and time variables
		// -------------------------------------

		$dt_vars = array(
			'start_date',
			'end_date',
			'start_time',
			'end_time'
		);

		$count = 1;
		$total = 0;

		foreach ($data->dates as $date)
		{
			$total += count($date);
		}

		if (empty($data->dates))
		{
			return $this->no_results();
		}

		//--------------------------------------------
		//	reverse sorting
		//--------------------------------------------

		if ($this->parent_method == 'occurrences' AND
			ee()->TMPL->fetch_param('reverse') AND
			strtolower(ee()->TMPL->fetch_param('reverse')) === 'true' )
		{
			krsort($data->dates);
		}

		//--------------------------------------------
		//	orderby sorting
		//--------------------------------------------

		/*if ($this->parent_method == 'occurrences' AND
			ee()->TMPL->fetch_param('orderby'))
		{
			$sort = (ee()->TMPL->fetch_param('sort') AND
					 strtolower(ee()->TMPL->fetch_param('sort')) === 'DESC') ? 'DESC' : 'ASC';


		}*/

		//--------------------------------------------
		//	pagination
		//--------------------------------------------

		if ($this->parent_method === 'occurrences')
		{
			$this->paginate = FALSE;
		}

		$limit = ee()->TMPL->fetch_param('occurrences_limit') ?
					ee()->TMPL->fetch_param('occurrences_limit') :
					$this->limit;

		if ($limit > 0 AND $this->parent_method === 'occurrences' AND $total > $limit)
		{
			//get pagination info
			$pagination_data = $this->universal_pagination(array(
				'total_results'			=> $total,
				//had to remove this jazz before so it didn't get iterated over
				'tagdata'				=> $tagdata . $this->paginate_tagpair_data,
				'limit'					=> $limit,
				'uri_string'			=> ee()->uri->uri_string,
				'paginate_prefix'		=> 'calendar_'
			));

			//if we paginated, sort the data
			if ($pagination_data['paginate'] === TRUE)
			{
				$this->paginate			= $pagination_data['paginate'];
				$this->page_next		= $pagination_data['page_next'];
				$this->page_previous	= $pagination_data['page_previous'];
				$this->p_page			= $pagination_data['pagination_page'];
				$this->current_page  	= $pagination_data['current_page'];
				$this->pager 			= $pagination_data['pagination_links'];
				$this->basepath			= $pagination_data['base_url'];
				$this->total_pages		= $pagination_data['total_pages'];
				$this->paginate_data	= $pagination_data['paginate_tagpair_data'];
				$this->page_count		= $pagination_data['page_count'];
				//$tagdata				= $pagination_data['tagdata'];
			}
		}

		//--------------------------------------------
		//	event limiter
		//--------------------------------------------

		$offset = (ee()->TMPL->fetch_param('occurrences_offset') ?
					ee()->TMPL->fetch_param('occurrences_offset') :
					0);
		$page 	= (($this->current_page -1) * $limit);

		if ($this->parent_method === 'occurrences' AND $page > 0)
		{
			$offset += $page;
		}

		//--------------------------------------------
		//	Add final variables and swap out,
		//	then add tagdata to return variable
		//--------------------------------------------

		$offset_counter = 0;

		foreach ($data->dates as $ymd => $times)
		{
			foreach ($times as $time => $info)
			{
				//offset and limiting
				if ($this->parent_method === 'occurrences' AND ++$offset_counter <= $offset) continue;
				if ($this->parent_method === 'occurrences' AND $offset_counter > ($limit + $offset)) break (2);

				//--------------------------------------------
				//	output variables
				//--------------------------------------------

				$tdata = (isset($data->occurrences[$ymd][$time]['entry_id'])) ?
							$tagdatas[$data->occurrences[$ymd][$time]['entry_id']] :
							$tagdatas[$data->default_data['entry_id']];

				if ($info['all_day'] === TRUE OR $info['all_day'] == 'y')
				{
					$info['date']['time']		= '0000';
					$info['date']['hour']		= '00';
					$info['date']['minute']		= '00';
					$info['end_date']['time']	= '2400';
					$info['end_date']['hour']	= '24';
					$info['end_date']['minute']	= '00';
					$info['duration']['hours']  = '24';
				}

				$vars = array(
					'single' => array(
						'occurrence_duration_minutes'	=> $info['duration']['minutes'],
						'occurrence_duration_hours'		=> $info['duration']['hours'],
						'occurrence_duration_days'		=> $info['duration']['days'],
						'occurrence_id'					=> (isset($data->occurrences[$ymd][$time]['entry_id'])) ?
															$data->occurrences[$ymd][$time]['entry_id'] :
															$data->default_data['entry_id'],
						'occurrence_start_year'			=> $info['date']['year'],
						'occurrence_start_month'		=> $info['date']['month'],
						'occurrence_start_day'			=> $info['date']['day'],
						'occurence_start_hour'			=> $info['date']['hour'],
						'occurrence_start_minute'		=> $info['date']['minute'],
						'occurrence_end_year'			=> $info['end_date']['year'],
						'occurrence_end_month'			=> $info['end_date']['month'],
						'occurrence_end_day'			=> $info['end_date']['day'],
						'occurrence_end_hour'			=> $info['end_date']['hour'],
						'occurrence_end_minute'			=> $info['end_date']['minute'],
						'occurrence_count'				=> $count,
						'occurrence_total'				=> $total,
						'occurrence_author_id'			=> (isset($entry_info['author_id']) ? $entry_info['author_id'] : ''),
						'occurrence_author'				=> (isset($entry_info['screen_name']) ? $entry_info['screen_name'] : (isset($entry_info['username']) ? $entry_info['username'] : '')),
						'occurrence_title'				=> (isset($entry_info['title']) ? $entry_info['title'] : ''),
						'occurrence_url_title'			=> (isset($entry_info['url_title']) ? $entry_info['url_title'] : ''),
						'occurrence_status'				=> (isset($entry_info['status']) ? $entry_info['status'] : ''),
					),
					'conditional' => array(
						'occurrence_all_day'			=> $info['all_day'],
						'occurrence_multi_day'			=> $info['multi_day'],
						'occurrence_status'				=> (isset($entry_info['status']) ? $entry_info['status'] : ''),
						'occurrence_author_id'			=> (isset($entry_info['author_id']) ? $entry_info['author_id'] : ''),
					),
					'date' => array(
						'occurrence_start_date'			=> $info['date'],
						'occurrence_start_time'			=> $info['date'],
						'occurrence_end_date'			=> $info['end_date'],
						'occurrence_end_time'			=> $info['end_date']
					)
				);

				$output .= $this->swap_vars($vars, $tdata);

				$count++;
			}
		}

		//--------------------------------------------
		//	offset too much? buh bye
		//--------------------------------------------

		if (trim($output) === '')
		{
			return $this->no_results();
		}

		return $output;
	}
	/* END prep_occurrences_output() */

	// --------------------------------------------------------------------

	/**
	 * Prepare exceptions output
	 *
	 * @param	string	$tagdata	Tagdata
	 * @param	array	$data		Array of data
	 * @return	string
	 */

	protected function prep_exceptions_output($tagdata, $data)
	{
		$tagdata = $this->fix_conditional_date_escaping('exception', $tagdata);

		$data = $data->exceptions;
		if (empty($data)) return $tagdata;

		$output = '';

		// -------------------------------------
		//  Date and time variables
		// -------------------------------------

		$dt_vars = array(
			'start_date',
			'end_date',
			'start_time',
			'end_time'
		);

		ksort($data);

		foreach ($data as $ymd => $info)
		{
			$tdata = $tagdata;

			foreach ($dt_vars as $var)
			{
				if (! isset($info[$var])) continue;

				if (strpos($tdata, LD.'exception_'.$var) !== FALSE)
				{
					if (preg_match_all('/'.LD.'exception_'.$var.'\s+format=[\'"]?(.*?)[\'"]?'.RD.'/s', $tdata, $matches))
					{
						foreach ($matches[1] as $k => $format)
						{
							//--------------------------------------------
							//	this is a quick and dirty fix for now
							//	so that icalendar export can work
							//--------------------------------------------

							//$this->cdt_format_date_string($info[$var], $format, '%');
							$format = str_replace(array('%', 'T'), array('', '~~~~'), $format);
							$format = str_replace('~~~~', 'T', date(
								$format,
								mktime(
									0, 0, 0,
									substr($info[$var], 4, 2), //month
									substr($info[$var], 6, 2), //day
									substr($info[$var], 0, 4)  //year
								)
							));

							$tdata = str_replace($matches[0][$k], $format, $tdata);
						}
					}
				}
			}

			$output .= $tdata;
		}

		return $output;
	}
	/* END prep_exceptions_output() */

	// --------------------------------------------------------------------

	/**
	 * Prepare rules output
	 *
	 * @param	string	$tagdata	Tagdata
	 * @param	array	$data		Array of data
	 * @return	string
	 */

	protected function prep_rules_output($tagdata, $data)
	{
		$tagdata = $this->fix_conditional_date_escaping('rule', $tagdata);

		$data = $data->rules;
		$output = '';

		$variables = array();
		$variables['single'] = array(
			'rule_id'				=> '',
			'rule_type'				=> '',
			'rule_all_day'			=> '',
			'rule_start_time'		=> '',
			'rule_repeat_years'		=> '',
			'rule_repeat_months'	=> '',
			'rule_repeat_days'		=> '',
			'rule_repeat_weeks'		=> '',
			'rule_stop_after'		=> '',
			'rule_start_date'		=> '',
			'rule_end_date'			=> '',
			'rule_stop_by'			=> '',
			'rule_last_date'		=> ''
		);

		$variables['pair'] = array(
			'rule_days_of_week'		=> array('day_of_week' => ''),
			'rule_relative_dow'		=> array('relative_dow' => ''),
			'rule_days_of_month'	=> array('day_of_month' => ''),
			'rule_months_of_year'	=> array('month_of_year' => '')
		);

		$variables['date'] = array(
			'rule_start_date'		=> '',
			'rule_end_date'			=> '',
			'rule_stop_by'			=> '',
			'rule_last_date'		=> ''
		);

		//if we have no rules, return all blank
		if (empty($data))
		{
			return $this->swap_vars($variables, $tagdata);
		}

		foreach ($data as $k => $info)
		{
			$vars = $variables;
			foreach ($info as $var => $val)
			{
				$var = (substr($var, 0, 5) == 'rule_') ? $var : 'rule_'.$var;

				if (isset($variables['date'][$var]))
				{
					//fix for incorrect start and end date formatting
					if ($var == 'rule_start_date' OR
						$var == 'rule_end_date')
					{
						$which = ($var == 'rule_start_date') ? 'start' : 'end';

						//this catches non prefixed 0's
						$hour = substr($info[$which. '_time'], 0, -2);
						$minute = substr($info[$which. '_time'], -2);

						$this->CDT->change_ymd($val);
						$this->CDT->change_time($hour, $minute);

						$val = $this->CDT->datetime_array();
					}

					$vars['date'][$var] = $val;
					$vars['single'][$var] = $val;
				}
				elseif (isset($variables['pair'][$var]))
				{
					$key = array_keys($variables['pair'][$var]);
					$vars['pair'][$var][$key[0]] = str_split($val);
					$vars['single'][$var] = $val;
				}
				else
				{
					$vars['single'][$var] = $val;
				}
			}

			$output .= $this->swap_vars($vars, $tagdata);
		}

		return $output;
	}
	/* END prep_rules_output() */

	// --------------------------------------------------------------------

	/**
	 * Format date string
	 *
	 * @access  public
	 * @param	array	$date	Date array
	 * @param	string	$format	Desired output format
	 * @param	string	$prefix	Prefix (usually %)
	 * @return	string
	 */

	public function cdt_format_date_string($date, $format, $prefix = '')
	{
		$this->CDT->change_datetime(
			$date['year'],
			$date['month'],
			$date['day'],
			$date['hour'],
			$date['minute']
		);

		return $this->CDT->format_date_string($format, $prefix);
	}
	// END format_date_string()


	// --------------------------------------------------------------------

	/**
	 * convert category URLS to numbers
	 *
	 * @access  private
	 * @return	null
	 */

	public function convert_category_titles()
	{
		$category 	= $split_cat = ee()->TMPL->fetch_param('category');

		if (strtolower(substr($split_cat, 0, 3)) == 'not')
		{
			$split_cat = substr($split_cat, 3);
		}

		$categories = preg_split(
			'/' . preg_quote('&') . '|' . preg_quote('|') . '/',
			$split_cat,
			-1,
			PREG_SPLIT_NO_EMPTY
		);

		$to_fix = array();

		foreach ($categories as $cat)
		{
			if (preg_match('/\w+/', trim($cat)))
			{
				$to_fix[trim($cat)] = 0;
			}
		}

		if ( ! empty ($to_fix))
		{
			$cats = ee()->db->query(
				"SELECT cat_id, cat_url_title
				 FROM 	exp_categories
				 WHERE 	cat_url_title
				 IN 	('" . implode("','", ee()->db->escape_str(array_keys($to_fix))) . "')"
			);

			foreach ($cats->result_array() as $row)
			{
				$to_fix[$row['cat_url_title']] = $row['cat_id'];
			}

			krsort($to_fix);

			foreach ($to_fix as $cat => $id)
			{
				if ($id != 0)
				{
					$category = str_replace($cat, $id, $category);
				}
			}

			ee()->TMPL->tagparams['category'] = $category;
		}
	}
	// END convert_category_titles()

	// --------------------------------------------------------------------

	/**
	 * permissions json
	 *
	 * outputs a json response with a list of allowed entry ids for cal
	 *
	 * @access  public
	 * @return	null
	 */

	public function permissions_json ()
	{
		$group_id = ee()->input->get_post('group_id');

		//valid n' stuff
		if (REQ != 'ACTION' OR
			trim((string) $group_id) === '' OR
			! is_numeric($group_id) OR
			! ($group_id > 0) )
		{
			return $this->send_ajax_response(array(
				'success' => FALSE,
				'message' => lang('invalid_permissions_json_request')
			));
		}

		ee()->load->library('calendar_permissions');

		return ee()->calendar_permissions->permissions_json($group_id);
	}
	//END permissions_json
}
// END CLASS Calendar