<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - Event Object
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/calendar.event.php
 */

class Calendar_event extends Calendar
{

	public $default_data	= array();
	public $occurrences		= array();
	public $exceptions		= array();
	public $rules			= array();
	public $dates			= array();
	public $event_start		= array();
	public $event_end		= array();
	public $range_start		= array();
	public $range_end		= array();
	public $limit			= 100;
	public $add_start		= FALSE;
	public $add_end			= FALSE;
	public $add_rule		= FALSE;


	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	$data	Array of event data
	 * @param	array	$start	Start date info [optional]
	 * @param	array	$end	End date info [optional]
	 * @param	int		$limit	Limit the number of occurrences to calculate [optional]
	 * @return	null
	 */

	public function __construct($data, $start = array(), $end = array(), $limit = 0)
	{
		parent::__construct();
		parent::load_calendar_datetime();

		// -------------------------------------
		//  Collect the default data
		// -------------------------------------

		$this->default_data	= $data;
		$this->rules		= (isset($this->default_data['rules'])) 		?
									 $this->default_data['rules'] 		: array();
		$this->occurrences	= (isset($this->default_data['occurrences'])) 	?
									 $this->default_data['occurrences'] : array();
		$this->exceptions	= (isset($this->default_data['exceptions'])) 	?
									 $this->default_data['exceptions'] 	: array();
		unset(
			$data,
			$this->default_data['rules'],
			$this->default_data['occurrences'],
			$this->default_data['exceptions']
		);

		$this->limit		= ($limit == 0) ? $this->limit : $limit;

		// -------------------------------------
		//  If we're only given dates, add the other keys we need
		// -------------------------------------

		if ( ! isset($data['start_year']))
		{
			$dates = $this->CDT->ymd_to_array($this->default_data['start_date']);
			foreach ($dates as $k => $v)
			{
				$this->default_data['start_'.$k] = $v;
			}
		}

		if ( ! isset($this->default_data['end_year']))
		{
			$dates = $this->CDT->ymd_to_array($this->default_data['end_date']);
			foreach ($dates as $k => $v)
			{
				$this->default_data['end_'.$k] = $v;
			}
		}

		// -------------------------------------
		//  Make sure we have a start time
		// -------------------------------------

		if ( ! isset($this->default_data['start_time']))
		{
			$this->default_data['start_time'] = '0000';
		}

		// -------------------------------------
		//  Make sure it's zero-padded
		// -------------------------------------

		else
		{
			$this->default_data['start_time'] = str_pad(
				$this->default_data['start_time'],
				4,
				0,
				STR_PAD_LEFT
			);
		}

		// -------------------------------------
		//  Make sure we have an end time
		// -------------------------------------

		if (! isset($this->default_data['end_time']))
		{
			$this->default_data['end_time'] = '2359';
		}

		// -------------------------------------
		//  Make sure it's zero-padded
		// -------------------------------------

		else
		{
			$this->default_data['end_time'] = str_pad(
				$this->default_data['end_time'],
				4,
				0,
				STR_PAD_LEFT
			);
		}

		if (isset($this->default_data['start_year']))
		{
			$this->event_start = array(
				'year'	=> $this->default_data['start_year'],
				'month'	=> $this->default_data['start_month'],
				'day'	=> $this->default_data['start_day'],
				'ymd'	=> (isset($this->default_data['start_ymd'])) ?
								$this->default_data['start_ymd'] :
								$this->CDT->make_ymd(
									$this->default_data['start_year'],
									$this->default_data['start_month'],
									$this->default_data['start_day']
								)
			);
		}
		else
		{
			$this->event_start = $this->CDT->ymd_to_array(
				$this->default_data['start_date']
			);
		}

		// -------------------------------------
		//  Use the start data if the equivalent
		//  end data doesn't exist and the event
		//  doesn't recur
		// -------------------------------------

		if ( isset($this->default_data['recurs']) AND
			 $this->default_data['recurs'] == 'n' AND
			 $this->default_data['end_year'] == 0)
		{
			$this->event_end = array(
				'year'	=> $this->default_data['start_year'],
				'month'	=> $this->default_data['start_month'],
				'day'	=> $this->default_data['start_day'],
				'ymd'	=> $this->default_data['start_ymd']
			);
		}
		else
		{
			$this->event_end = array(
				'year'	=> $this->default_data['end_year'],
				'month'	=> $this->default_data['end_month'],
				'day'	=> $this->default_data['end_day'],
				'ymd'	=> (isset($this->default_data['end_ymd'])) ?
								$this->default_data['end_ymd'] :
								$this->CDT->make_ymd(
									$this->default_data['end_year'],
									$this->default_data['end_month'],
									$this->default_data['end_day']
								)
			);
		}

		// -------------------------------------
		//  If $start/$end were given as YMD, convert them
		// -------------------------------------

		if (! is_array($start))
		{
			$start = $this->CDT->ymd_to_array($start);
		}

		if (! is_array($end))
		{
			$end = $this->CDT->ymd_to_array($end);
		}

		// -------------------------------------
		//  Set the date range
		// -------------------------------------

		if (empty($start) OR (isset($start['ymd']) AND $start['ymd'] == ''))
		{
			$this->range_start = array(
				'year'		=> $this->event_start['year'],
				'month'		=> $this->event_start['month'],
				'day'		=> $this->event_start['day'],
				'ymd'		=> $this->event_start['ymd'],
				'time'		=> '0000',
				'hour'		=> '00',
				'minute'	=> '00'
			);
		}
		else
		{
			$this->range_start = array(
				'year'		=> $start['year'],
				'month'		=> $start['month'],
				'day'		=> $start['day'],
				'ymd'		=> (array_key_exists('ymd', $start)) ?
									$start['ymd'] :
									$this->CDT->make_ymd(
										$start['year'],
										$start['month'],
										$start['day']
									),
				'time'		=> isset($start['time']) ? $start['time'] : '0000',
				'hour'		=> isset($start['hour']) ? $start['hour'] : '00',
				'minute'	=> isset($start['minute']) ? $start['minute'] : '00'

			);
		}

		//default to event end
		if (empty($end))
		{
			$this->range_end = array(
				'year'		=> $this->event_end['year'],
				'month'		=> $this->event_end['month'],
				'day'		=> $this->event_end['day'],
				'ymd'		=> $this->event_end['ymd'],
				'time'		=> '2359',
				'hour'		=> '23',
				'minute'	=> '59'
			);
		}
		//never ending
		elseif ($end['year'] == 0)
		{
			$this->range_end = array(
				'year'		=> 9999,
				'month'		=> 12,
				'day'		=> 31,
				'ymd'		=> 99991231,
				'time'		=> '2359',
				'hour'		=> '23',
				'minute'	=> '59'
			);
		}
		//range end
		else
		{
			$this->range_end = array(
				'year'		=> $end['year'],
				'month'		=> $end['month'],
				'day'		=> $end['day'],
				'ymd'		=> (array_key_exists('ymd', $end)) ?
								$end['ymd'] :
								$this->CDT->make_ymd(
									$end['year'],
									$end['month'],
									$end['day']
								),
				'time'		=> isset($end['time']) ? $end['time'] : '2359',
				'hour'		=> isset($end['hour']) ? $end['hour'] : '23',
				'minute'	=> isset($end['minute']) ? $end['minute'] : '59'
			);
		}



		// -------------------------------------
		//  Flip-flop if end is smaller than start
		// -------------------------------------

		if ($this->range_end < $this->range_start)
		{
			$temp				= $this->range_end;
			$this->range_end	= $this->range_start;
			$this->range_start	= $temp;
		}

		// -------------------------------------
		//  Don't bother continuing if we're out of range
		// -------------------------------------

		if ($this->range_end['ymd'] < $this->event_start['ymd'])
		{
			return;
		}

		// -------------------------------------
		//  All day
		// -------------------------------------

		$this->default_data['all_day'] = (
			isset($this->default_data['all_day']) AND
			$this->default_data['all_day'] == 'y'
		);

		// -------------------------------------
		//  If the event is all day, the end time
		//  will be 0000. While calculating
		//  event duration we'll come up a whole
		//  ay short as a result. Thus,
		//  we adjust end time to 2359
		// -------------------------------------

		if ($this->default_data['all_day'] === TRUE)
		{
			$this->default_data['end_time'] = 2400;
		}

		// -------------------------------------
		//  Calculate the event's duration
		// -------------------------------------

		$this->default_data['duration'] = $this->calculate_duration();

		// -------------------------------------
		//  Multi-day
		// -------------------------------------

		//$this->default_data['multi_day'] = (
		//	$this->default_data['start_date'] == $this->default_data['end_date'] OR
		// ($this->default_data['duration']['days'] == 0 AND
			//$this->default_data['end_time'] == '0000')) ? FALSE : TRUE;

		$this->default_data['multi_day'] = (
			$this->default_data['start_date'] == $this->default_data['end_date'] OR
			$this->default_data['duration']['days'] == 0
		) ? FALSE : TRUE;

		// -------------------------------------
		//  Add date arrays
		// -------------------------------------

		$this->CDT->change_datetime(
			$this->default_data['start_year'],
			$this->default_data['start_month'],
			$this->default_data['start_day'],
			substr($this->default_data['start_time'], 0, 2),
			substr($this->default_data['start_time'], 2)
		);

		$this->default_data['date']		= $this->CDT->datetime_array();

		$this->CDT->change_datetime(
			$this->default_data['end_year'],
			$this->default_data['end_month'],
			$this->default_data['end_day'],
			substr($this->default_data['end_time'], 0, 2),
			substr($this->default_data['end_time'], 2)
		);

		$this->default_data['end_date']	= $this->CDT->datetime_array();

		// -------------------------------------
		//  Set the date, if appropriate
		// -------------------------------------

		if ($this->event_start['ymd'] >= $this->range_start['ymd'] OR
			$this->event_end['ymd'] >= $this->range_start['ymd'])
		{
			if (empty($this->rules) OR
				! isset($this->rules['add'][0]) OR
				$this->rules['add'][0]['start_date'] != $this->default_data['start_date'])
			{
				$this->dates[$this->default_data['start_date']][
					$this->default_data['start_time'] . $this->default_data['end_time']
				] = $this->default_data;
			}
		}

		// -------------------------------------
		//  Calculate dates using repetition rules
		// -------------------------------------

		if ( ! empty($this->rules))
		{
			// -------------------------------------
			//  First deal with additive rules
			// -------------------------------------

			if (isset($this->rules['add']))
			{
				foreach ($this->rules['add'] as $rule)
				{
					$new_dates = $this->add_dates_using_rule($rule);

					// -------------------------------------
					//  We can't just += or array_merge() here. Nope,
					//  gotta do it the long way.
					// -------------------------------------

					foreach ($new_dates as $ymd => $times)
					{
						foreach ($times as $time => $data)
						{
							$this->dates[$ymd][$time]	= $data;
						}
					}
				}
			}

			// -------------------------------------
			//  He giveth, and He taketh away
			// -------------------------------------

			if (isset($this->rules['sub']))
			{
				foreach ($this->rules['sub'] as $rule)
				{
					$this->dates = array_diff_key(
						$this->dates,
						$this->remove_dates_using_rule($rule)
					);
				}
			}
		}

		// -------------------------------------
		//  Add occurrences
		// -------------------------------------

		if (! empty($this->occurrences))
		{
			$this->add_occurrences();
		}

		// -------------------------------------
		//  Death to the exceptions!
		// -------------------------------------

		if (! empty($this->exceptions))
		{
			$this->remove_exceptions();
		}

		// -------------------------------------------
		// 'calendar_event_end' hook.
		//  - Add additional processing after an event has been created
		if (ee()->extensions->active_hook('calendar_event_end') === TRUE)
		{
			ee()->extensions->call('calendar_event_end', $this);
			if (ee()->extensions->end_script === TRUE) return;
		}
		//
		// -------------------------------------------
	}
	/* END Calendar_event() */

	// --------------------------------------------------------------------

	/**
	 * Fetch occurrences
	 *
	 * @return	null
	 */

	public function fetch_occurrences()
	{
		$this->load_data();
		$this->occurrences = Calendar_data::fetch_occurrences_by_event_id(
			$this->default_data['event_id'],
			$this->range_start,
			$this->range_end
		);
	}
	/* END fetch_occurrences() */

	// --------------------------------------------------------------------

	/**
	 * Fetch rules
	 *
	 * @return	null
	 */

	public function fetch_rules()
	{
		$this->load_data();
		$this->rules = Calendar_data::fetch_rules_by_event_id(
			$this->default_data['event_id'],
			$this->range_start,
			$this->range_end
		);
	}
	/* END fetch_rules() */


	// --------------------------------------------------------------------

	/**
	 * Add dates using rule
	 *
	 * @param	array	$rule	Array of rules
	 * @return	array
	 */

	public function add_dates_using_rule($rule)
	{
		//--------------------------------------------
		//	dates
		//--------------------------------------------

		$dates 				= array();

		$s_year				= substr($rule['start_date'], 0, 4);
		$s_month			= substr($rule['start_date'], 4, 2);
		$s_day				= substr($rule['start_date'], 6, 2);
		$s_ymd				= $rule['start_date'];
		$e_year				= substr($rule['end_date'], 0, 4);
		$e_month			= substr($rule['end_date'], 4, 2);
		$e_day				= substr($rule['end_date'], 6, 2);
		$e_ymd				= $rule['end_date'];
		$all_day			= (isset($rule['all_day']) AND $rule['all_day'] == 'y');

		$rule['start_time']	= ($all_day === TRUE) ?
								'0000' : str_pad($rule['start_time'], 4, 0, STR_PAD_LEFT);

		$rule['end_time']	= ($all_day === TRUE) ?
								'2400' : str_pad($rule['end_time'], 4, 0, STR_PAD_LEFT);

		$s_hour				= substr($rule['start_time'], 0, strlen($rule['start_time']) - 2);
		$s_minute			= substr($rule['start_time'], -2, 2);
		$e_hour				= substr($rule['end_time'], 0, strlen($rule['end_time']) - 2);
		$e_minute			= substr($rule['end_time'], -2, 2);
		$time				= $rule['start_time'] . $rule['end_time'];
		$count				= 0;
		$interval_count		= 0;
		$limit				= (isset($rule['stop_after']) AND $rule['stop_after'] != FALSE) ?
								$rule['stop_after'] : $this->limit;

		//--------------------------------------------
		//	Never ending date? needs some fixes
		//--------------------------------------------

		if ( ! isset($rule['last_date']) OR $rule['last_date'] == FALSE)
		{
			$rule['last_date'] = ($rule['stop_by'] != FALSE) ? $rule['stop_by'] : 99991231;
		}

		//--------------------------------------------
		//	endless event?
		//--------------------------------------------

		$endless = ( ! $rule['stop_after'] AND $rule['last_date'] == 99991231);

		// -------------------------------------
		//  End date arrays
		// -------------------------------------

		$e_date	= $this->CDT->change_datetime($e_year, $e_month, $e_day, $e_hour, $e_minute);

		// -------------------------------------
		//  Start date array - Do this last!
		// -------------------------------------

		$s_date	= $this->CDT->change_datetime($s_year, $s_month, $s_day, $s_hour, $s_minute);

		// -------------------------------------
		//	store for outside function usage
		// -------------------------------------

		$this->add_start			= $s_date;
		$this->add_end				= $e_date;
		$this->add_rule				= $rule;

		// -------------------------------------
		//  Calculate the "day difference" for multi-day events
		// -------------------------------------

		$day_difference = 0;

		if ($s_ymd < $rule['end_date'])
		{
			while ($this->CDT->ymd < $rule['end_date'] AND $day_difference < 1000)
			{
				$day_difference++;
				$this->CDT->add_day(1);
			}

			// -------------------------------------
			//  Reset CDT
			// -------------------------------------

			$this->CDT->change_date($s_year, $s_month, $s_day, $s_hour, $s_minute);
		}

		// -------------------------------------
		//  If the start date is valid for this rule, add it
		// -------------------------------------

		if ($s_ymd <= $rule['last_date'] 		AND
				$s_ymd <= $this->range_end['ymd'] 	AND
				(
					//WAT?
					//Removed because i suspect that this was falsly allowing items
					//but the later checks were removing them properly. Stupid.
					$s_ymd >= $this->range_start['ymd']			OR
					//does it fall in between?
					( 	$s_ymd <= $this->range_start['ymd'] AND
						$e_ymd >= $this->range_end['ymd'] ) 	OR
					//
					( 	$e_ymd <= $this->range_end['ymd'] AND
						$e_ymd >= $this->range_start['ymd'] )
				) AND
				$this->check_rule_days_of_week($rule['days_of_week']) === TRUE 		AND
				$this->check_rule_relative_dow($rule['relative_dow']) === TRUE 		AND
				$this->check_rule_days_of_month($rule['days_of_month']) === TRUE 	AND
				$this->check_rule_months_of_year($rule['months_of_year']) === TRUE
		)
		{
			$dates[$s_ymd][$time] = array(
				'all_day' 	=> $all_day,
				'date' 		=> $s_date,
				'end_date' 	=> $e_date,
				'duration'	=> $this->calculate_duration($s_date, $e_date)
			);
			//$dates[$s_ymd][$time]['multi_day'] = (
			// $dates[$s_ymd][$time]['date']['ymd'] == $dates[$s_ymd][$time]['end_date']['ymd'] OR
			// ($dates[$s_ymd][$time]['duration']['days'] == 0 AND
			//  $dates[$s_ymd][$time]['end_date']['hour'] == '00' AND
			//	$dates[$s_ymd][$time]['end_date']['minute'] == '00')
			// ) ? FALSE : TRUE;
			$dates[$s_ymd][$time]['multi_day'] = (
				$dates[$s_ymd][$time]['date']['ymd'] == $dates[$s_ymd][$time]['end_date']['ymd'] OR
				$dates[$s_ymd][$time]['duration']['days'] == 0
			) ? FALSE : TRUE;

			$count++;
		}

		// -------------------------------------
		//  Ensure we have been provided with at least one rule
		// -------------------------------------

		if ( ! $rule['repeat_years'] 	AND
			 ! $rule['repeat_months'] 	AND
			 ! $rule['repeat_weeks'] 	AND
			 ! $rule['repeat_days'] 	AND
			 ! $rule['days_of_week'] 	AND
			 ! $rule['relative_dow'] 	AND
			 ! $rule['days_of_month'] 	AND
			 ! $rule['months_of_year']
		)
		{
			$this->add_start	= FALSE;
			$this->add_end		= FALSE;
			$this->add_rules	= FALSE;

			return $dates;
		}

		//--------------------------------------------
		//	occurrences limit?
		//--------------------------------------------

		$prior_occurrences_limit = FALSE;

		if (isset(ee()->TMPL) AND
			is_object(ee()->TMPL) AND
			! in_array(ee()->TMPL->fetch_param('prior_occurrences_limit'), array(FALSE, ''), TRUE) AND
			is_numeric(ee()->TMPL->fetch_param('prior_occurrences_limit')) AND
			ee()->TMPL->fetch_param('prior_occurrences_limit') > 0)
		{
			$prior_occurrences_limit = ee()->TMPL->fetch_param('prior_occurrences_limit');
		}

		// -------------------------------------
		//  Determine "simple" versus "complex"
		// -------------------------------------

		$fields = array(
			'days_of_week',
			'relative_dow',
			'days_of_month',
			'months_of_year'
		);

		$simple = TRUE;

		foreach ($fields as $field)
		{
			if ($rule[$field] != '')
			{
				$simple = FALSE;
				break;
			}
		}

		//--------------------------------------------
		//	if things are simple, advance to closer
		//	so we arent doing useless loops
		//	this also helps with never ending items
		//	to make them show up past certain dates
		//--------------------------------------------
		//	we set everything on repeat amount before
		//	the current date minus the amount of days
		//	off from the current date it would fall
		//	this gives the intial addition of the rule
		//	room to work
		//--------------------------------------------

		//lets not start this before this event even begins
		$diff_start_date = ($this->range_start['ymd'] <= $s_ymd) ? array(
			'ymd' 	=> $s_ymd,
			'year'	=> $s_year,
			'month' => $s_month,
			'day'	=> $s_day
		) : $this->range_start;

		//lets not loop through every single day
		if ($simple === TRUE AND $rule['repeat_weeks'] > 0)
		{
			//get the difference
			$diff 		= $this->calculate_duration(
				$this->CDT->datetime_array(),
				$diff_start_date
			);

			//calculation includes current day :/
			$diff['days'] = $diff['days'] - 1;

			$diff_days 	= (
				$diff['days'] -
				($diff['days'] % ($rule['repeat_weeks'] * 7)) -
				($rule['repeat_weeks'] * 7)
			);

			$this->CDT->add_day($diff_days);
		}

		//lets not loop through every single day
		if ($simple === TRUE AND $rule['repeat_days'] > 0)
		{
			//get the difference
			$diff 		= $this->calculate_duration(
				$this->CDT->datetime_array(),
				$diff_start_date
			);

			//calculation includes current day :/
			$diff['days'] = $diff['days'] - 1;

			$diff_days 	= (
				$diff['days'] - ($diff['days'] % $rule['repeat_days'])
			) - $rule['repeat_days'];

			$this->CDT->add_day($diff_days);
		}

		//--------------------------------------------
		//	loop through everything
		//--------------------------------------------
		while ( $this->CDT->ymd <= $rule['last_date'] 		AND
				$this->CDT->ymd <= $this->range_end['ymd'] 	AND
				//this should be <, but it produces too
				//few results at times :(
				//however, this produces too many other
				//times. Removed extra at bottom
				$count <= $limit
		)
		{
			//$count++;
			$increment_day = 0;
			$add_it = TRUE;

			// -------------------------------------
			//  Simple stuff
			// -------------------------------------

			if ($simple === TRUE)
			{
				// -------------------------------------
				//  Add years
				// -------------------------------------

				if ($rule['repeat_years'] > 0)
				{
					$this->CDT->add_year($rule['repeat_years']);
				}

				// -------------------------------------
				//  Add months
				// -------------------------------------

				if ($rule['repeat_months'] > 0)
				{
					$this->CDT->add_month($rule['repeat_months']);
				}

				// -------------------------------------
				//  Add weeks
				// -------------------------------------

				if ($rule['repeat_weeks'] > 0)
				{
					$this->CDT->add_day($rule['repeat_weeks'] * 7);
				}

				// -------------------------------------
				//  Add days
				// -------------------------------------

				if ($rule['repeat_days'] > 0)
				{
					$this->CDT->add_day($rule['repeat_days']);
				}

				// -------------------------------------
				//  Add it?
				// -------------------------------------

				$add_it = (
					$this->CDT->ymd >= $rule['start_date']			AND
					$this->CDT->ymd <= $rule['last_date']			AND
					$this->CDT->ymd >= $this->range_start['ymd']	AND
					$this->CDT->ymd <= $this->range_end['ymd']
				) ? TRUE : FALSE;

				//added checking for time in range
				$add_it = $this->check_range_hours($add_it);

				//--------------------------------------------
				//	if this is a never ending event,
				//	we need to start from now
				//	so we can can get more results
				//	unless we need to see some prior results
				//	then we need to check occurrences limit
				//--------------------------------------------

				if ($endless AND $add_it AND
					$this->CDT->ymd < $this->range_start['ymd'] AND
					$prior_occurrences_limit)
				{
					//if we add the limit, and its over or at the start date
					//when we can add these previous dates properly
					$this->CDT->add_day($prior_occurrences_limit);

					$add_it = $this->CDT->ymd >= $this->range_start['ymd'];

					$this->CDT->add_day(-1 * $prior_occurrences_limit);
				}
				else if ($endless AND $add_it)
				{
					$add_it = $this->CDT->ymd >= $this->range_start['ymd'];
				}

				// -------------------------------------
				//  Add this date
				// -------------------------------------

				if ($add_it === TRUE)
				{
					$count++;
					$date							= $this->CDT->datetime_array();
					$this->CDT->add_day($day_difference);
					$this->CDT->change_time($e_hour, $e_minute);
					$end							= $this->CDT->datetime_array();
					$dates[$date['ymd']][$time] 	= array(
						'data' 		=> array(),
						'all_day' 	=> $all_day,
						'date' 		=> $date,
						'end_date' 	=> $end,
						'duration'	=> $this->calculate_duration($date, $end)
					);

					//$dates[$date['ymd']][$time]['multi_day'] =  (
					//$dates[$date['ymd']][$time]['date']['ymd'] ==
					//$dates[$date['ymd']][$time]['end_date']['ymd'] OR
					// ($dates[$date['ymd']][$time]['duration']['days'] == 0 AND
					//  $dates[$date['ymd']][$time]['end_date']['hour'] == '00' AND
					//  $dates[$date['ymd']][$time]['end_date']['minute'] == '00')
					// ) ? FALSE : TRUE;
					$dates[$date['ymd']][$time]['multi_day'] = (
						$dates[$date['ymd']][$time]['date']['ymd'] ==
							$dates[$date['ymd']][$time]['end_date']['ymd'] OR
						$dates[$date['ymd']][$time]['duration']['days'] == 0
					) ? FALSE : TRUE;

					// -------------------------------------
					//  Reset CDT to the start datetime
					// -------------------------------------

					$this->CDT->change_datetime(
						$date['year'],
						$date['month'],
						$date['day'],
						$date['hour'],
						$date['minute']
					);
				}
			}
			elseif ($simple === FALSE AND
					($rule['repeat_years'] 	> 0 OR
					 $rule['repeat_months'] > 0 OR
					 $rule['repeat_weeks'] 	> 0 OR
					 $rule['repeat_days'] 	> 0 )
			)
			{
				if ($rule['repeat_years'] > 0)
				{
					// TODO: Not currently implemented in the UI
				}
				elseif ($rule['repeat_months'] > 0)
				{
					// -------------------------------------
					//  Start at the first day of the month,
					//	and iterate through each day
					// -------------------------------------

					for ($d = 1; $d <= $this->CDT->days_in_month(); $d++)
					{
						$this->CDT->change_date($this->CDT->year, $this->CDT->month, $d);

						// -------------------------------------
						//  Skip if we haven't reached our starting point
						// -------------------------------------

						if ($this->CDT->ymd < $this->range_start['ymd'] OR
							$this->CDT->ymd < $rule['start_date'])
						{
							continue;
						}

						// -------------------------------------
						//  Bail if we're beyond our limits
						// -------------------------------------

						if ($this->CDT->ymd > $rule['last_date'] OR
							$this->CDT->ymd > $this->range_end['ymd'])
						{
							break(2);
						}

						$add_it = $this->check_complex_rules($rule);

						//added checking for time in range
						$add_it = $this->check_range_hours($add_it);

						// -------------------------------------
						//  Add this date
						// -------------------------------------

						if ($add_it === TRUE)
						{
							$count++;
							$date							= $this->CDT->datetime_array();
							$this->CDT->add_day($day_difference);
							$this->CDT->change_time($e_hour, $e_minute);
							$end							= $this->CDT->datetime_array();
							$dates[$date['ymd']][$time] 	= array(
								'data' => array(),
								'all_day' => $all_day,
								'date' => $date,
								'end_date' => $end,
								'duration'	=> $this->calculate_duration($date, $end)
							);

							//$dates[$date['ymd']][$time]['multi_day'] = (
							//	$dates[$date['ymd']][$time]['date']['ymd'] ==
							//	$dates[$date['ymd']][$time]['end_date']['ymd'] OR
							//($dates[$date['ymd']][$time]['duration']['days'] == 0 AND
							// $dates[$date['ymd']][$time]['end_date']['hour'] == '00' AND
							// $dates[$date['ymd']][$time]['end_date']['minute'] == '00')) ? FALSE : TRUE;
							$dates[$date['ymd']][$time]['multi_day'] = (
								$dates[$date['ymd']][$time]['date']['ymd'] == $dates[
														$date['ymd']][$time]['end_date']['ymd'] OR
								$dates[$date['ymd']][$time]['duration']['days'] == 0
							) ? FALSE : TRUE;

							// -------------------------------------
							//  Reset CDT to the start datetime
							// -------------------------------------

							$this->CDT->change_datetime(
								$date['year'],
								$date['month'],
								$date['day'],
								$date['hour'],
								$date['minute']
							);
						}
					}

					// -------------------------------------
					//  On to the next month!
					// -------------------------------------

					$this->CDT->change_date($this->CDT->year, $this->CDT->month, 1);
					$this->CDT->add_month($rule['repeat_months']);
				}
				elseif ($rule['repeat_weeks'] > 0)
				{
					// -------------------------------------
					//  Start at the first day of the week, then iterate through each day
					// -------------------------------------

					if ($this->CDT->day_of_week > $this->CDT->first_day_of_week)
					{
						$this->CDT->add_day(-($this->CDT->day_of_week - $this->CDT->first_day_of_week));
					}
					elseif ($this->CDT->day_of_week < $this->CDT->first_day_of_week)
					{
						$this->CDT->add_day(-($this->CDT->first_day_of_week - $this->CDT->day_of_week));
					}

					for ($i = 0; $i < 7; $i++)
					{
						if ($i > 0)
						{
							$this->CDT->add_day();
						}

						// -------------------------------------
						//  Skip if we haven't reached our starting point
						// -------------------------------------

						if ($this->CDT->ymd < $this->range_start['ymd'] OR $this->CDT->ymd < $rule['start_date'])
						{
							continue;
						}

						// -------------------------------------
						//  Bail if we're beyond our limits
						// -------------------------------------

						if ($this->CDT->ymd > $rule['last_date'] OR
							$this->CDT->ymd > $this->range_end['ymd'])
						{
							break(2);
						}

						$add_it = $this->check_complex_rules($rule);

						//added checking for time in range
						$add_it = $this->check_range_hours($add_it);

						if ($add_it === TRUE)
						{
							// -------------------------------------
							//  Add this date
							// -------------------------------------

							$count++;
							$date						= $this->CDT->datetime_array();
							$this->CDT->add_day($day_difference);
							$this->CDT->change_time($e_hour, $e_minute);
							$end						= $this->CDT->datetime_array();
							$dates[$date['ymd']][$time] = array(
								'data' 		=> array(),
								'all_day' 	=> $all_day,
								'date' 		=> $date,
								'end_date' 	=> $end,
								'duration'	=> $this->calculate_duration($date, $end)

							);
							//$dates[$date['ymd']][$time]['multi_day'] = (
							//	$dates[$date['ymd']][$time]['date']['ymd'] ==
							//  $dates[$date['ymd']][$time]['end_date']['ymd'] OR
							//	($dates[$date['ymd']][$time]['duration']['days'] == 0 AND
							//   $dates[$date['ymd']][$time]['end_date']['hour'] == '00' AND
							//   $dates[$date['ymd']][$time]['end_date']['minute'] == '00')
							//	) ? FALSE : TRUE;
							$dates[$date['ymd']][$time]['multi_day'] = (
								$dates[$date['ymd']][$time]['date']['ymd'] == $dates[$date['ymd']
																				][$time]['end_date']['ymd'] OR
								$dates[$date['ymd']][$time]['duration']['days'] == 0
							) ? FALSE : TRUE;

							// -------------------------------------
							//  Reset CDT to the start datetime
							// -------------------------------------

							$this->CDT->change_datetime(
								$date['year'],
								$date['month'],
								$date['day'],
								$date['hour'],
								$date['minute']
							);
						}
					}

					// -------------------------------------
					//  On to the next week!
					// -------------------------------------

					$this->CDT->add_day($rule['repeat_weeks'] * 7 - 6);
				}
				elseif ($rule['repeat_days'] > 0)
				{
					$add_it = $this->check_complex_rules($rule);

					//added checking for time in range
					$add_it = $this->check_range_hours($add_it);

					if ($add_it === TRUE)
					{
						// -------------------------------------
						//  Add this date
						// -------------------------------------

						$count++;
						$date						= $this->CDT->datetime_array();
						$this->CDT->add_day($day_difference);
						$this->CDT->change_time($e_hour, $e_minute);
						$end						= $this->CDT->datetime_array();
						$dates[$date['ymd']][$time] = array(
							'data' 		=> array(),
							'all_day' 	=> $all_day,
							'date' 		=> $date,
							'end_date' 	=> $end,
							'duration'	=> $this->calculate_duration($date, $end)
						);

						//$dates[$date['ymd']][$time]['multi_day'] = (
						//	$dates[$date['ymd']][$time]['date']['ymd'] ==
						//	$dates[$date['ymd']][$time]['end_date']['ymd'] OR
						// ($dates[$date['ymd']][$time]['duration']['days'] == 0 AND
						//  $dates[$date['ymd']][$time]['end_date']['hour'] == '00' AND
						//  $dates[$date['ymd']][$time]['end_date']['minute'] == '00')
						// ) ? FALSE : TRUE;
						$dates[$date['ymd']][$time]['multi_day'] = (
							$dates[$date['ymd']][$time]['date']['ymd'] == $dates[
													$date['ymd']][$time]['end_date']['ymd'] OR
							$dates[$date['ymd']][$time]['duration']['days'] == 0
						) ? FALSE : TRUE;

						// -------------------------------------
						//  Reset CDT to the start datetime
						// -------------------------------------

						$this->CDT->change_datetime(
							$date['year'],
							$date['month'],
							$date['day'],
							$date['hour'],
							$date['minute']
						);
					}

					// -------------------------------------
					//  Onward!
					// -------------------------------------

					$this->CDT->add_day($rule['repeat_days']);
				}

				$add_it = FALSE;

			}
			else
			{
				$add_it = $this->check_complex_rules($rule);

				//added checking for time in range
				$add_it = $this->check_range_hours($add_it);

				// -------------------------------------
				//  Don't add anything if we haven't reached the range start
				// -------------------------------------

				if ($this->CDT->ymd < $this->range_start['ymd'])
				{
					$add_it = FALSE;
				}

				// -------------------------------------
				//  Don't add anything if we went past one of our limtis
				// -------------------------------------

				if ($this->CDT->ymd > $rule['last_date'] OR $this->CDT->ymd > $this->range_end['ymd'])
				{
					$add_it = FALSE;
				}

				// -------------------------------------
				//  Add this date
				// -------------------------------------

				if ($add_it === TRUE)
				{
					$count++;
					$date	= $this->CDT->datetime_array();
					$this->CDT->add_day($day_difference);
					$this->CDT->change_time($e_hour, $e_minute);
					$end	= $this->CDT->datetime_array();
					$dates[$date['ymd']][$time] = array(
						'data' 		=> array(),
						'all_day' 	=> $all_day,
						'date' 		=> $date,
						'end_date' 	=> $end,
						'duration'	=> $this->calculate_duration($date, $end)
					);

					//$dates[$date['ymd']][$time]['multi_day'] = (
					//	$dates[$date['ymd']][$time]['date']['ymd'] ==
					//	$dates[$date['ymd']][$time]['end_date']['ymd'] OR
					//  ($dates[$date['ymd']][$time]['duration']['days'] == 0 AND
					//   $dates[$date['ymd']][$time]['end_date']['hour'] == '00' AND
					//   $dates[$date['ymd']][$time]['end_date']['minute'] == '00')
					//  ) ? FALSE : TRUE;

					$dates[$date['ymd']][$time]['multi_day'] = (
						$dates[$date['ymd']][$time]['date']['ymd'] ==
							$dates[$date['ymd']][$time]['end_date']['ymd'] OR
						$dates[$date['ymd']][$time]['duration']['days'] == 0
					) ? FALSE : TRUE;

					// -------------------------------------
					// Reset CDT to the start datetime
					// -------------------------------------

					$this->CDT->change_datetime(
						$date['year'],
						$date['month'],
						$date['day'],
						$date['hour'],
						$date['minute']
					);
				}

				// -------------------------------------
				//  Add 1 to the day
				// -------------------------------------

				$increment_day = 1;
			}

			// -------------------------------------
			//  Increment the day, if we've been told to
			// -------------------------------------

			if ($increment_day > 0)
			{
				$this->CDT->add_day($increment_day);
			}

		}

		//this takes care of the above issue of having too many dates
		//because we are using $count <= $limit instead of <, but otherwise
		//its too FEW dates, which is harder to remed :(

		//going to reset this in case any janky crap gets added in
		reset($dates);

		if (count($dates) > $limit)
		{
			$dates = array_slice($dates, 0, (-1 * (count($dates) - $limit)), TRUE);
		}

		$this->add_start	= FALSE;
		$this->add_end		= FALSE;
		$this->add_rule	= FALSE;

		return $dates;
	}
	// END add_dates_using_rule()


	// --------------------------------------------------------------------

	/**
	 * Check range hours against current CDT
	 *
	 * @access	public
	 * @param	boolean $add_it	starting add bool
	 * @return	boolean			in range or original bool
	 */

	public function check_range_hours ($add_it = TRUE)
	{
		if ( ! $add_it)
		{
			return FALSE;
		}

		$has_tmpl = isset(ee()->TMPL) AND is_object(ee()->TMPL);

		//added checking for time in range, but it _could_
		//confuse people used ot the old way, so
		//if legacy_add_recurring='yes' we dont do this
		if ( ! $has_tmpl OR ! $this->check_yes(
				ee()->TMPL->fetch_param('legacy_add_recurring')
			)
		)
		{
			if ($has_tmpl AND $this->check_yes(
					ee()->TMPL->fetch_param('inclusive_time_range')
				))
			{
				return (
					$this->CDT->ymd . $this->CDT->time <=
					$this->range_end['ymd'] . $this->range_end['time']
					AND
					$this->CDT->ymd . $this->CDT->time >=
					$this->range_start['ymd'] . $this->range_start['time']
				);
			}
			else
			{
				return (
					$this->CDT->ymd . $this->add_start['time'] <=
					$this->range_end['ymd'] . $this->range_end['time']
					AND
					$this->CDT->ymd . $this->add_end['time'] >=
					$this->range_start['ymd'] . $this->range_start['time']
				);
			}
		}

		return $add_it;
	}
	//END check_range_hours


	// --------------------------------------------------------------------

	/**
	 * Check complex rules
	 *
	 * @param	array	$rule	Array of rules
	 * @return	bool
	 */

	protected function check_complex_rules($rule)
	{
		// -------------------------------------
		//  Simple stuff
		// -------------------------------------

		if (($this->CDT->ymd >= $this->range_start['ymd'] AND
			 $this->CDT->ymd <= $rule['last_date'] AND
			 $this->CDT->ymd <= $this->range_end['ymd']) !== TRUE)
		{
			//echo '1';
			return FALSE;
		}

		// -------------------------------------
		//  Check days of the week
		// -------------------------------------

		if ($this->check_rule_days_of_week($rule['days_of_week']) !== TRUE)
		{
			//echo '2';
			return FALSE;
		}

		// -------------------------------------
		//  Relativity
		// -------------------------------------

		if ($this->check_rule_relative_dow($rule['relative_dow']) !== TRUE)
		{
			//echo '3';
			return FALSE;
		}

		// -------------------------------------
		//  Check days of the month
		// -------------------------------------

		if ($this->check_rule_days_of_month($rule['days_of_month']) !== TRUE)
		{
			//echo '4';
			return FALSE;
		}

		// -------------------------------------
		//  Check months of the year
		// -------------------------------------

		if ($this->check_rule_months_of_year($rule['months_of_year']) !== TRUE)
		{
			//echo '5';
			return FALSE;
		}

		//echo 'true';
		return TRUE;
	}
	/* END check_complex_rules() */

	// --------------------------------------------------------------------

	/**
	 * Remove dates using rule
	 *
	 * @param	array	$rule	Array of rules
	 * @return	array
	 */

	public function remove_dates_using_rule($rule)
	{
		return $this->add_dates_using_rule($rule);
	}
	/* END remove_dates_using_rule() */

	// --------------------------------------------------------------------

	/**
	 * Add occurrences
	 *
	 * @return	null
	 */

	public function add_occurrences()
	{
		foreach ($this->occurrences as $start_date => $times)
		{
			if ($start_date < $this->range_start['ymd'] OR $start_date > $this->range_end['ymd'])
			{
				continue;
			}

			foreach ($times as $time => $occurrence)
			{
				$occurrence['start_time']	= str_pad($occurrence['start_time'], 4, '0', STR_PAD_LEFT);
				$occurrence['end_time']		= str_pad($occurrence['end_time'], 4, '0', STR_PAD_LEFT);
				if ($occurrence['all_day'] == 1 OR $occurrence['all_day'] == 'y')
				{
					$time = '00002400';
				}
				elseif (strlen($time) != 8)
				{
					$time = $occurrence['start_time'].$occurrence['end_time'];
				}

				if (! isset($this->dates[$start_date][$time]))
				{
					$this->CDT->change_datetime($occurrence['start_year'], $occurrence['start_month'], $occurrence['start_day'], substr($time, 0, 2), substr($time, 2, 2));
					$date = $this->CDT->datetime_array();

					if ($occurrence['end_date'] == 0)
					{
						$duration = $this->calculate_duration($date, array());

						if ($duration['years'] > 0)
						{
							$this->CDT->add_year($duration['years']);
						}
						if ($duration['months'] > 0)
						{
							$this->CDT->add_month($duration['months']);
						}
						if ($duration['days'] > 0)
						{
							$this->CDT->add_day($duration['days']);
						}
						if ($duration['hours'] > 0 OR $duration['minutes'] > 0)
						{
							$this->CDT->add_time(str_pad($duration['hours'], 2, '0', STR_PAD_LEFT).str_pad($duration['minutes'], 2, '0', STR_PAD_LEFT));
						}

						$end = $this->CDT->datetime_array();
					}
					else
					{
						$this->CDT->change_datetime($occurrence['end_year'], $occurrence['end_month'], $occurrence['end_day'], substr($occurrence['end_time'], 0, 2), substr($occurrence['end_time'], 2));
						$end		= $this->CDT->datetime_array();
						$duration	= $this->calculate_duration($date, $end);
					}

					if (($start_date >= $this->range_start['ymd'] AND $start_date <= $this->range_end['ymd']) OR ($end['ymd'] >= $this->range_start['ymd'] AND $end['ymd'] <= $this->range_end['ymd']))
					{
						$this->dates[$start_date][$time] = array(	'data' => array(),
																	'all_day'	=> (isset($occurrence['all_day']) AND $occurrence['all_day'] == 'y') ? TRUE : FALSE,
																	'date' => $date,
																	'end_date' => $end,
																	'duration'	=> $duration
																	);
						//$this->dates[$start_date][$time]['multi_day'] = ($start_date == $occurrence['end_date'] OR ($this->dates[$start_date][$time]['duration']['days'] == 0 AND $this->dates[$start_date][$time]['end_date']['hour'] == 0 AND $this->dates[$start_date][$time]['end_date']['minute'] == 0)) ? FALSE : TRUE;
						$this->dates[$start_date][$time]['multi_day'] = ($start_date == $occurrence['end_date'] OR $this->dates[$start_date][$time]['duration']['days'] == 0) ? FALSE : TRUE;
					}
				}
			}
		}
	}
	/* END add_occurrences() */

	// --------------------------------------------------------------------

	/**
	 * Remove exceptions
	 *
	 * @return	null
	 */

	public function remove_exceptions()
	{
		foreach ($this->exceptions as $start_date => $exception)
		{
			if (isset($this->dates[$start_date]))
			{
				// -------------------------------------
				//  ZAP!
				// -------------------------------------

				unset($this->dates[$start_date]);
			}
		}
	}
	/* ENd remove_exceptions() */

	// --------------------------------------------------------------------

	/**
	 * Check rule: days of the week
	 *
	 * @param	string	$dow	Day of the week
	 * @return	bool
	 */

	public function check_rule_days_of_week($dow)
	{
		if ($dow == '')
		{
			return TRUE;
		}

		$dows = array('U', 'M', 'T', 'W', 'R', 'F', 'S');

		return (strpos($dow, $dows[$this->CDT->day_of_week]) === FALSE) ? FALSE : TRUE;
	}
	/* END check_rule_days_of_week() */

	// --------------------------------------------------------------------

	/**
	 * Check rule: relative day of the week
	 *
	 * @param	string	$dow	Relative day of the week
	 * @return	bool
	 */

	public function check_rule_relative_dow($dow)
	{
		if ($dow == '' OR $dow == 0)
		{
			return TRUE;
		}

		$negatives = array('A','B','C','D','E');

		// -------------------------------------
		//  There can be multiple values. Put them into an array.
		// -------------------------------------

		// NOTE: Requires PHP >= 5
		$dow = str_split($dow, 1);

		// -------------------------------------
		//  Iterate among the values
		// -------------------------------------

		foreach ($dow as $day)
		{
			if (in_array($day, $negatives))
			{
				$day = -(array_search($day, $negatives) + 1);
				$min = $this->CDT->days_in_month() - (7 * $day * -1);
				$max = $min + 6;
				if ($this->CDT->day >= $min AND $this->CDT->day <= $max)
				{
					return TRUE;
				}
			}
			elseif ($day < 5)
			{
				$max = 7 * $day;
				$min = $max - 6;
				if ($this->CDT->day >= $min AND $this->CDT->day <= $max)
				{
					return TRUE;
				}
			}
			elseif ($day == 5)
			{
				if ($this->CDT->day >= 29)
				{
					return TRUE;
				}
			}
			else
			{
				if ($this->CDT->day > $this->CDT->days_in_month() - 7)
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}
	/* END check_rule_relative_dow() */

	// --------------------------------------------------------------------

	/**
	 * Check rule: days of the month
	 *
	 * @param	string	$days	Days of the month
	 * @return	bool
	 */

	public function check_rule_days_of_month($days)
	{
		if ($days == '')
		{
			return TRUE;
		}

		$compare = array('1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V');

		return (strpos($days, $compare[$this->CDT->day - 1]) === FALSE) ? FALSE : TRUE;
	}
	/* END check_rule_days_of_month() */

	// --------------------------------------------------------------------

	/**
	 * Check rule: months of the year
	 *
	 * @param	string	$months	Months of the year
	 * @return	bool
	 */

	public function check_rule_months_of_year($months)
	{
		if ($months == '')
		{
			return TRUE;
		}

		$compare = array('1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C');

		return (strpos($months, $compare[$this->CDT->month - 1]) === FALSE) ? FALSE : TRUE;
	}
	/* END check_rule_months_of_year() */

	// --------------------------------------------------------------------

	/**
	 * Prepare for output
	 *
	 * @return	null
	 */

	public function prepare_for_output()
	{
		// -------------------------------------
		//  Modify rules
		// -------------------------------------

		$new_rules = array();
		foreach ($this->rules as $type => $rules)
		{
			foreach ($rules as $rule)
			{
				$new_rules[] = $rule;
			}
		}
		$this->rules = $new_rules;
	}
	/* END prepare_for_output() */

	// --------------------------------------------------------------------

	/**
	 * Load data
	 *
	 * @return	null
	 */

	protected function load_data()
	{
		if ( ! class_exists('Calendar_data'))
		{
			require_once CALENDAR_PATH.'data.calendar.php';
		}
	}
	/* END load_data() */

	// --------------------------------------------------------------------

	/**
	 * Calculate the duration between two CDT objects
	 *
	 * @return	array
	 */

	public function calculate_duration($start = array(), $end = array())
	{
		$start = array(
			'year'		=> isset($start['year'	]) ? $start['year'	] : $this->default_data['start_year'],
			'month'		=> isset($start['month'	]) ? $start['month'	] : $this->default_data['start_month'],
			'day'		=> isset($start['day'	]) ? $start['day'	] : $this->default_data['start_day'],
			'ymd'		=> isset($start['ymd'	]) ? $start['ymd'	] : $this->default_data['start_date'],
			'minute'	=> isset($start['minute']) ? $start['minute'] : substr($this->default_data['start_time'], 2),
			'hour'		=> isset($start['hour'	]) ? $start['hour'	] : substr($this->default_data['start_time'], 0, 2)
		);

		$end = array(
			'year'		=>  isset($end['year'	 ]) ? $end['year' ] : $this->default_data['end_year'],
			'month'		=>  isset($end['month' ])   ? $end['month'] : $this->default_data['end_month'],
			'day'		=>  isset($end['day'	 ]) ? $end['day'  ] : $this->default_data['end_day'],
			'ymd'		=>  isset($end['ymd'	 ]) ? $end['ymd'  ] : $this->default_data['end_date'],
			'minute'	=>  isset($end['minute'])   ? $end['minute'] : substr($this->default_data['end_time'], 2),
			'hour'		=>  isset($end['hour'	 ]) ? $end['hour'  ] : substr($this->default_data['end_time'], 0, 2)
		);


		// -------------------------------------
		//  Sometimes occurrences arrive with end values set to zero.
		//  We need to fix that.
		// -------------------------------------

		foreach ($end as $key => $val)
		{
			if ($val == 0)
			{
				$end[$key] = $start[$key];
				$which = substr($key, strpos($key, '_')) . 's';
				if (isset($this->default_data['duration'][$which]))
				{
					$end[$key] += $this->default_data['duration'][$which];
				}
			}
		}

		$array = array(	'years'		=> 0,
						'months'	=> 0,
						'days'		=> 0,
						'hours'		=> 0,
						'minutes'	=> 0
						);

		if ($start == $end) return $array;

		// -------------------------------------
		//  Find the day difference
		// -------------------------------------

		$sj = $this->g_to_jd($start['month'], $start['day'], $start['year']);
		$ej = $this->g_to_jd($end['month'], $end['day'], $end['year']);

		$array['days'] = $ej - $sj;

		// -------------------------------------
		//  For all day events, cheat a little and add 1 minute
		// -------------------------------------

		if ($this->default_data['all_day'] === TRUE)
		{
			$end['minute']	= '00';
			$end['hour']	= '24';
		}

		// -------------------------------------
		//  Find the time difference
		// -------------------------------------

		$array['minutes']	= $end['minute'] - $start['minute'];
		$array['hours']		= $end['hour'] - $start['hour'];

		// -------------------------------------
		//  Now adjust
		// -------------------------------------

		while ($array['minutes'] < 0)
		{
			$array['minutes']	+= 60;
			$array['hours']		-= 1;
		}

		while ($array['minutes'] > 59)
		{
			$array['minutes']	-= 60;
			$array['hours']		+= 1;
		}

		while ($array['hours'] < 0)
		{
			$array['hours']	+= 24;
			$array['days']	-= 1;
		}

		while ($array['hours'] > 23)
		{
			$array['hours']	-= 24;
			$array['days']	+= 1;
		}

		// TODO: Months and years

		// -------------------------------------
		//  Away!
		// -------------------------------------

		return $array;
	}
	/* END calculate_duration() */


	// --------------------------------------------------------------------

	/**
	 * checks for the gregoriantojd function (present if PHP5-calendar module is installed)
	 * if native isn't present, we use the helper function
	 *
	 * @access	public
	 * @param	int		month
	 * @param	int		day
	 * @param	int		year
	 * @return	string
	 */

	public function g_to_jd($month, $day, $year)
	{
		if (function_exists('gregoriantojd'))
		{
			return gregoriantojd($month, $day, $year);
		}
		else
		{
			return $this->g_to_jd_replacement($month, $day, $year);
		}
	}
	// END g_to_jd


	// --------------------------------------------------------------------

	/**
	 * replacement function for gregoriantojd if the php-calendar module isn't installed
	 *
	 * @access	private
	 * @param	int		month
	 * @param	int		day
	 * @param	int		year
	 * @return	string
	 */

	private function g_to_jd_replacement($month, $day, $year)
	{
		if ($month > 2)
		{
		   $month 	= $month - 3;
		}
		else
		{
		   $month 	= $month + 9;
		   $year 	= $year - 1;
		}

		$c 	= floor($year / 100);
		$ya = $year - (100 * $c);
		$j 	= floor((146097 * $c) / 4);
		$j 	+= floor((1461 * $ya)/4);
		$j 	+= floor(((153 * $month) + 2) / 5);
		$j 	+= $day + 1721119;

		return $j;
	}
	//END g_to_jd_replacement

}
// END CLASS Calendar_event