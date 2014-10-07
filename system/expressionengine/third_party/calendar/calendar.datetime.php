<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - Date Library
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/calendar.datetime.php
 */

class Calendar_datetime extends Calendar
{

	public $max_year			= 3000;
	public $min_year			= 1600;
	public $calendar_type		= 'Gregorian';		// Alt 'Julian' is experimental
	public $first_day_of_week	= 0;

	public $default_date		= array();
	public $default_time		= array();
	public $ymd;
	public $year;
	public $month;
	public $day;
	public $day_of_week;
	public $day_of_year;
	public $week_number;
	public $week_number_W;
	public $hour;
	public $minute;
	public $time;
	public $pm;
	public $ee_date				= 0;
	public $lang_loaded			= FALSE;
	public $date_chars			= 'bdDjlNSwWFmMntLoUYyz';
	public $time_chars			= 'aAgGhHiIs';

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct()
	{
		parent::__construct();

		// -------------------------------------
		//  Grab cached data
		// -------------------------------------

		if (isset($this->cache['CDT']))
		{
			foreach ($this->cache['CDT']['default'] as $k => $v)
			{
				$this->$k	= $v;
			}
		}
		else
		{
			$this->change_date($this->year(), $this->month(), $this->day());
			$this->change_time($this->hour(), $this->minute());

			// -------------------------------------
			//  Cache
			// -------------------------------------

			$this->cache['CDT']['default']	= $this->datetime_array();
		}
	}
	/* END __construct() */


	// --------------------------------------------------------------------

	/**
	 * Set cal date to current date and time
	 *
	 * @access	public
	 * @return	null
	 */

	public function set_date_to_now()
	{
		//set date to current
		$this->change_date($this->year(), $this->month(), $this->day());
		$this->change_time($this->hour(), $this->minute());
	}
	//end set_date_to_now


	// --------------------------------------------------------------------

	/**
	 * Get cached date
	 *
	 * @param	int	$year	Year (zero-padded to 4 digits)
	 * @param	int	$month	Month (zero-padded to 2 digits)
	 * @param	int	$day	Day	(zero-padded to 2 digits)
	 * @return	array
	 */

	public function get_cached_date($year, $month, $day)
	{
		return $this->get_cached_ymd($year . $month . $day);
	}
	/* END get_cached_date() */


	// --------------------------------------------------------------------

	public function get_cached_ymd($ymd)
	{
		if (! isset($this->cache['CDT']['dates'][$ymd]))
		{
			return FALSE;
		}

		return $this->cache['CDT']['dates'][$ymd];
	}

	// --------------------------------------------------------------------

	/**
	 * Set cached date
	 *
	 * @param	array	$data	Array of date data
	 * @return	null
	 */

	public function set_cached_date($data)
	{
		$this->cache['CDT']['dates'][$data['ymd']]	= $data;
	}
	/* END set_cached_date() */

	// --------------------------------------------------------------------

	/**
	 * Get cached date format
	 *
	 * @param	int	$year	Year (zero-padded to 4 digits)
	 * @param	int	$month	Month (zero-padded to 2 digits)
	 * @param	int	$day	Day	(zero-padded to 2 digits)
	 * @return	array
	 */

	public function get_cached_date_format($year, $month, $day, $format)
	{
		return $this->get_cached_format_ymd($year . $month . $day, $format);
	}
	/* END get_cached_date_format() */

	// --------------------------------------------------------------------

	public function get_cached_format_ymd($ymd, $format)
	{
		if (! isset($this->cache['CDT']['formats'][$ymd][$format]))
		{
			return FALSE;
		}

		return $this->cache['CDT']['formats'][$ymd][$format];
	}
	/* END get_cached_format_ymd() */

	// --------------------------------------------------------------------

	/**
	 * Set cached date format
	 *
	 * @param	array	$data	Array of date data
	 * @return	null
	 */

	public function set_cached_date_format($ymd, $format, $value)
	{
		$this->cache['CDT']['formats'][$ymd][$format]	= $value;
	}
	/* END set_cached_date_format() */

	// --------------------------------------------------------------------

	/**
	 * Get cached time
	 *
	 * @param	int	$hour	Hour (zero-padded to 2 digits), or HourMinute (zero-padded to 4 digits)
	 * @param	int	$minute	Minute (zero-padded to 2 digits) [optional]
	 * @return	array
	 */

	public function get_cached_time($hour, $minute = FALSE)
	{
		if ($minute !== FALSE)
		{
			$hm	= $hour.$minute;
		}
		else
		{
			$hm	= $hour;
		}

		if (! isset($this->cache['CDT']['times'][$hm]))
		{
			return FALSE;
		}

		return $this->cache['CDT']['times'][$hm];
	}
	/* END get_cached_time() */

	// --------------------------------------------------------------------

	/**
	 * Set cached time
	 *
	 * @param	array	$data	Array of time data
	 * @return	null
	 */

	public function set_cached_time($data)
	{
		if (isset($data['time']))
		{
			$this->cache['CDT']['times'][$data['time']]	= $data;
		}
	}
	/* END set_cached_time() */


	// --------------------------------------------------------------------

	/**
	 * Get cached time format
	 *
	 * @param	int	$time	Time (zero-padded to 4 digits)
	 * @return	array
	 */

	public function get_cached_time_format($time, $format)
	{
		if (! isset($this->cache['CDT']['formats'][$time][$format]))
		{
			return FALSE;
		}

		return $this->cache['CDT']['formats'][$time][$format];
	}
	/* END get_cached_time_format() */

	// --------------------------------------------------------------------

	/**
	 * Change date and run methods that need to run when that happens
	 *
	 * @param	int		$year	Year
	 * @param	int		$month	Month
	 * @param	int		$day	Day
	 * @return	array
	 */

	public function change_date($year = '', $month = '', $day = '')
	{
		$date_data = array();

		if ($year == $this->year AND
			$month == $this->month AND
			$day == $this->day)
		{
			return $this->date_array();
		}

		// -------------------------------------
		//  Check the cache
		// -------------------------------------

		if ($date = $this->get_cached_date(
				$year,
				str_pad($month, 2, '0', STR_PAD_LEFT),
				str_pad($day, 2, '0', STR_PAD_LEFT)
			))
		{
			// -------------------------------------
			//  Set default, if necessary
			// -------------------------------------

			if (! $this->default_date)
			{
				$this->set_default($date['ymd']);
			}

			foreach ($date as $k => $v)
			{
				$this->$k	= $v;
			}

			return $this->datetime_array();
		}

		// -------------------------------------
		//  Validate our dates
		// -------------------------------------

		if (! $this->is_valid_year($year))
		{
			$year = $this->year();
		}
		$date_data['year'] = $year;

		$date_data['is_leap_year'] = $this->is_leap_year($year);

		if ( ! $this->is_valid_month($month))
		{
			$month = $this->month();
		}

		$date_data['month'] = $this->trim_leading_zeros($month);

		if ( ! $this->is_valid_day($day, $month, $year))
		{
			$day = $this->day();
		}
		$date_data['day'] = $this->trim_leading_zeros($day);

		$date_data['ymd'] = $year.str_pad($date_data['month'], 2, '0', STR_PAD_LEFT).str_pad($date_data['day'], 2, '0', STR_PAD_LEFT);

		// -------------------------------------
		//  Calculate the day of the week
		// -------------------------------------

		$date_data['day_of_week'] = $this->get_day_of_week($date_data['year'], $date_data['month'], $date_data['day']);

		// -------------------------------------
		//  Calculate the day of year
		// -------------------------------------

		$date_data['day_of_year'] = $this->get_day_of_year($date_data['year'], $date_data['month'], $date_data['day']);

		// -------------------------------------
		//  Calculate the week number
		// -------------------------------------

		$date_data['week_number'] = $this->get_week_number($date_data['year'], $date_data['month'], $date_data['day']);
		$date_data['week_number_W'] = $this->get_week_number_W($date_data['year'], $date_data['month'], $date_data['day']);

		// -------------------------------------
		//  $this is it
		// -------------------------------------

		foreach ($date_data as $k => $v)
		{
			$this->$k = $v;
		}

		// -------------------------------------
		//  Set default, if necessary
		// -------------------------------------

		if (! $this->default_date)
		{
			$this->set_default($date_data);
		}

		// -------------------------------------
		//  Add these data to the cache and return
		// -------------------------------------

		$this->set_cached_date($date_data);

		return $date_data;
	}
	/* END change_date() */

	// --------------------------------------------------------------------

	/**
	 * Change time and run methods that need to run when that happens
	 *
	 * @param	int		$hour	Hour
	 * @param	int		$minute	Minute
	 * @return	array
	 */

	public function change_time($hour, $minute)
	{
		// -------------------------------------
		//  If this date already exists, don't repeat the stuff below
		// -------------------------------------

		$hour	= str_pad($hour, 2, '0', STR_PAD_LEFT);
		$minute	= str_pad($minute, 2, '0', STR_PAD_LEFT);
		$time	= $hour.$minute;

		if ($this->get_cached_time($time) !== FALSE)
		{
			foreach ($this->get_cached_time($time) as $k => $v)
			{
				$this->$k = $v;
			}

			return $this->time_array();
		}

		// -------------------------------------
		//  Validate the time
		// -------------------------------------

		if (! $this->is_valid_hour($hour))
		{
			$hour = $this->hour();
		}
		$this->hour = $hour;

		if (! $this->is_valid_minute($minute))
		{
			$minute = $this->minute();
		}
		$this->minute = $minute;

		// -------------------------------------
		//  Set am/pm
		// -------------------------------------

		$this->pm = ($this->hour >= 12) ? TRUE : FALSE;

		// -------------------------------------
		//  Set time
		// -------------------------------------

		$this->time = $this->hour.$this->minute;

		// -------------------------------------
		//  Add these data to the cache and return
		// -------------------------------------

		$time_data = array(	'time'		=> $this->time,
							'hour'		=> $this->hour,
							'minute'	=> $this->minute,
							'pm'		=> $this->pm
							);

		$this->set_cached_time($time_data['time']);

		return $time_data;
	}
	/* END change_time() */

	// --------------------------------------------------------------------

	/**
	 * A two-for-one! Change the date and time
	 *
	 * @param	int		$year	Year
	 * @param	int		$month	Month
	 * @param	int		$day	Day
	 * @param	int		$hour	Hour
	 * @param	int		$minute	Minute
	 * @return	array
	 */

	public function change_datetime($year, $month, $day, $hour, $minute)
	{
		if ($year != $this->year OR $month != $this->month OR $day != $this->day)
		{
			$date = $this->change_date($year, $month, $day);
		}
		else
		{
			$date = $this->date_array();
		}

		if ($hour != $this->hour OR $minute != $this->minute)
		{
			$time = $this->change_time($hour, $minute);
		}
		else
		{
			$time = $this->time_array();
		}

		return array_merge($date, $time);
	}
	/* END change_datetime() */

	// --------------------------------------------------------------------

	/**
	 * Change date by passing in a YMD
	 *
	 * @param	int	$ymd	YearMonthDay (e.g. 20101225)
	 * @return	mixed
	 */

	public function change_ymd($ymd)
	{
		if ($ymd == $this->ymd)
		{
			return $this->datetime_array();
		}

		if ($this->is_valid_ymd($ymd))
		{
			$ymd = $this->ymd_to_array($ymd);
			$this->change_date($ymd['year'], $ymd['month'], $ymd['day']);
			return $this->datetime_array();
		}

		return FALSE;
	}
	/* END change_ymd() */

	// --------------------------------------------------------------------

	/**
	 * Reset the datetime info to the current default
	 *
	 * @return	null
	 */

	public function reset()
	{
		if (! is_array($this->default_date))
		{
			$this->default_date = $this->ymd_to_array($this->default_date);
		}

		foreach ($this->default_date as $k => $v)
		{
			$this->$k = $v;
		}
	}
	/* END reset() */

	// --------------------------------------------------------------------

	/**
	 * Return the current datetime array
	 *
	 * @return	array
	 */

	public function datetime_array()
	{
		return array_merge($this->date_array(), $this->time_array());
	}
	/* END datetime_array() */

	// --------------------------------------------------------------------

	/**
	 * Return the current date array
	 *
	 * @return	array
	 */

	public function date_array()
	{
		return array(
			'ymd'			=> $this->ymd,
			'year'			=> $this->year,
			'month'			=> $this->month,
			'day'			=> $this->day,
			'day_of_week'	=> $this->day_of_week,
			'day_of_year'	=> $this->day_of_year,
			'week_number'	=> $this->week_number,
			'week_number_W'	=> $this->week_number_W
		);
	}
	/* END date_array() */


	// --------------------------------------------------------------------

	/**
	 * Return the current time array
	 *
	 * @return	array
	 */

	public function time_array()
	{
		return array(
			'time'		=> $this->time,
			'hour'		=> $this->hour,
			'minute'	=> $this->minute,
			'pm'		=> $this->pm
		);
	}
	/* END time_array() */


	// --------------------------------------------------------------------

	/**
	 * Set the current default date or datetime
	 *
	 * @param	array	$date_data	Date data
	 * @param	array	$time_data	Time data [optional]
	 * @return	null
	 */

	public function set_default($date_data, $time_data = array())
	{
		$this->default_date = $date_data;

		if ( ! empty($time_data))
		{
			$this->default_time	= $time_data;
		}
	}
	/* END set_default() */


	// --------------------------------------------------------------------

	/**
	 * Is valid YMD?
	 *
	 * @param	int	$ymd	YearMonthDay
	 * @return	bool
	 */

	public function is_valid_ymd($ymd)
	{
		$old_year	= $this->year;
		$old_month	= $this->month;
		$old_day	= $this->day;

		$year		= substr($ymd, 0, strlen($ymd) - 4);
		$month		= substr($ymd, -4, 2);
		$day		= substr($ymd, -2);

		$this->change_date($year, $month, $day);

		if ($this->get_cached_ymd($ymd) !== FALSE OR
			($this->is_valid_year($year) === TRUE AND
			 $this->is_valid_month($month) === TRUE AND
			 $this->is_valid_day($day) === TRUE))
		{
			$this->change_date($old_year, $old_month, $old_day);
			return TRUE;
		}

		$this->change_date($old_year, $old_month, $old_day);
		return FALSE;
	}
	/* END is_valid_ymd() */

	// --------------------------------------------------------------------

	/**
	 * Is valid year?
	 *
	 * @param	int	$year	Year
	 * @return	bool
	 */

	public function is_valid_year($year)
	{
		$year = $this->floor($year);

		// -------------------------------------
		//  If the year falls within our bounds, declare success
		// -------------------------------------

		if ($year >= $this->min_year AND $year <= $this->max_year)
		{
			return TRUE;
		}

		// -------------------------------------
		//  Splat!
		// -------------------------------------

		return FALSE;
	}
	/* END is_valid_year() */

	// --------------------------------------------------------------------

	/**
	 * Is valid month?
	 *
	 * @param	int	$month	Month
	 * @return	bool
	 */

	public function is_valid_month($month)
	{
		$month = $this->floor($month);

		if ($month >= 1 AND $month <= 12)
		{
			return TRUE;
		}
		return FALSE;
	}
	/* END is_valid_month() */

	// --------------------------------------------------------------------

	/**
	 * Is valid day?
	 *
	 * @param	int	$day				Day
	 * @param	int	$month [optional]	Month
	 * @param	int	$year [optional]	Year
	 * @return	bool
	 */

	public function is_valid_day($day, $month = 0, $year = 0)
	{
		$day = $this->floor($day);

		// -------------------------------------
		//  No reason to mess around if the day is out of this range
		// -------------------------------------

		if ($day < 1 OR $day > 31)
		{
			return FALSE;
		}

		// -------------------------------------
		//  If $day > 28, we may need to worry about February
		// -------------------------------------

		if ($day > 28)
		{

			$month = ($this->is_valid_month($month)) ? $month : $this->month;

			if ($month == 2)
			{
				if ($day > 29)
				{
					return FALSE;
				}

				$year = ($this->is_valid_year($year)) ? $year : $this->year;

				// -------------------------------------
				//  Is this a leap year?
				// -------------------------------------

				if ($this->is_leap_year($year) === TRUE)
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}

			// -------------------------------------
			//  Not February? Let's make sure you're in range
			// -------------------------------------

			else
			{
				$days_in_months = $this->days_in_months();

				if ($day <= $days_in_months[$month-1])
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
		}

		// -------------------------------------
		//  $day is >= 1 and <= 28, so it's legit
		// -------------------------------------

		return TRUE;
	}
	/* END is_valid_day() */

	// --------------------------------------------------------------------

	/**
	 * Is valid time?
	 *
	 * @param	int	$time	Time (HHMM or HH:MM)
	 * @return	bool
	 */

	public function is_valid_time($time)
	{
		$time	= preg_replace('/\D/', '', $time);
		$hour	= substr($time, 0, strlen($time) - 2);
		$minute	= substr($time, -2);

		if ($this->is_valid_hour($hour) === TRUE AND $this->is_valid_minute($minute) === TRUE)
		{
			return TRUE;
		}

		return FALSE;
	}
	/* END is_valid_time() */

	// --------------------------------------------------------------------

	/**
	 * Is valid hour?
	 *
	 * @param	int	$hour	Hour
	 * @return	bool
	 */

	public function is_valid_hour($hour)
	{
		$hour = $this->floor($hour);

		if ($hour >= 0 AND $hour <= 24)
		{
			return TRUE;
		}
		return FALSE;
	}
	/* END is_valid_hour() */

	// --------------------------------------------------------------------

	/**
	 * Is valid minute?
	 *
	 * @param	int	$minute	Minute
	 * @return	bool
	 */

	public function is_valid_minute($minute)
	{
		$minute = $this->floor($minute);

		if ($minute >= 0 AND $minute <= 59)
		{
			return TRUE;
		}
		return FALSE;
	}
	/* END is_valid_minute() */

	// --------------------------------------------------------------------

	/**
	 * Is leap year?
	 * Determines if a given year is a leap year in Julian and Gregorian calendars
	 *
	 * @param	int	$year [optional]	Year
	 * @return	bool
	 */

	public function is_leap_year($year = NULL)
	{
		if (is_null($year))
		{
			$year = $this->year;
		}

		if ($this->calendar_type == 'Julian')
		{
			return ($year % 4 == 0) ? TRUE : FALSE;
		}
		else
		{
			return ($year % 4 == 0 AND ($year % 400 == 0 OR $year % 100 != 0)) ? TRUE : FALSE;
		}
	}
	/* END is_leap_year() */

	// --------------------------------------------------------------------

	/**
	 * Days in months
	 * Returns an array of the number of days in each non-leap year month
	 *
	 * @param	int	$year	Year [optional]
	 *
	 * @return	array
	 */

	public function days_in_months($year = NULL)
	{
		$days = array(31,28,31,30,31,30,31,31,30,31,30,31);
		if (is_null($year))
		{
			$year = $this->year;
		}
		if ($this->is_leap_year($year))
		{
			$days[1] = 29;
		}

		return $days;
	}
	/* END days_in_months() */

	// --------------------------------------------------------------------

	/**
	 * Days in month
	 * Returns the number of days in the month
	 *
	 * @param	int	$month	Month [optional]
	 * @param	int	$year	Year [optional]
	 * @return	array
	 */

	public function days_in_month($month = NULL, $year = NULL)
	{
		$days = $this->days_in_months($year);

		if (is_null($month))
		{
			$month = $this->month;
		}

		return $days[$month-1];
	}
	/* END days_in_months() */

	// --------------------------------------------------------------------

	/**
	 * Get the year from EE
	 *
	 * @return	int
	 */

	public function year()
	{
		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			return gmdate('Y', $this->ee_date());
		}
		else
		{
			return ee()->localize->format_date('%Y');
		}
	}
	/* END year() */

	// --------------------------------------------------------------------

	/**
	 * Get the month from EE
	 *
	 * @return	int
	 */

	public function month()
	{
		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			return gmdate('n', $this->ee_date());
		}
		else
		{
			return ee()->localize->format_date('%n');
		}
	}
	/* END month() */

	// --------------------------------------------------------------------

	/**
	 * Get the day from EE
	 *
	 * @return	int
	 */

	public function day()
	{
		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			return gmdate('j', $this->ee_date());
		}
		else
		{
			return ee()->localize->format_date('%j');
		}
	}
	/* END day() */

	// --------------------------------------------------------------------

	/**
	 * Get the hour from EE
	 *
	 * @return	int
	 */

	public function hour()
	{
		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			return gmdate('G', $this->ee_date());
		}
		else
		{
			return ee()->localize->format_date('%G');
		}
	}
	/* END hour() */

	// --------------------------------------------------------------------

	/**
	 * Get the minute from EE
	 *
	 * @return	int
	 */

	public function minute()
	{
		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			return gmdate('i', $this->ee_date());
		}
		else
		{
			return ee()->localize->format_date('%i');
		}
	}
	/* END minute() */

	// --------------------------------------------------------------------

	/**
	 * Get the localized unix timestamp from EE
	 *
	 * @deprecated do not use after EE 2.5.5
	 * @return	int
	 */

	public function ee_date()
	{
		$this->ee_date = (($this->ee_date > 0) ?
							$this->ee_date :
							ee()->localize->set_localized_time());

		return $this->ee_date;
	}
	/* END ee_date() */

	// --------------------------------------------------------------------

	/**
	 * Format date
	 *
	 * @param	string	$format	Date format string
	 * @param	string	$prefix	String prefix to indicate formattable characters (e.g. %)
	 * @return	string
	 */

	public function format_date_string($format, $prefix = '')
	{
		// -------------------------------------
		//  No point in going to work if there's nothing to translate
		// -------------------------------------

		if ($prefix > '' AND strpos($format, $prefix) === FALSE)
		{
			return $format;
		}

		// -------------------------------------
		//  Fetch all of the formattable "nuggets"
		// -------------------------------------

		$output = $format;

		preg_match_all('#'.$prefix.'([a-zA-Z])#', $format, $matches);

		if ( ! empty($matches))
		{
			foreach ($matches[1] as $k =>$char)
			{
				// -------------------------------------
				//  Date character or time character?
				// -------------------------------------

				if (strpos($this->date_chars, $char) !== FALSE)
				{
					if ( ! $this->get_cached_format_ymd(
							$this->ymd . $this->time,
							$char
						)
					)
					{
						$this->format_date_character($char);
					}

					$output = str_replace(
						$matches[0][$k],
						$this->get_cached_format_ymd(
							$this->ymd . $this->time,
							$char
						),
						$output
					);
				}
				elseif (strpos($this->time_chars, $char) !== FALSE)
				{
					if ( ! $this->get_cached_time_format(
							$this->ymd . $this->time,
							$char
						)
					)
					{
						$this->format_date_character($char);
					}

					$output = str_replace(
						$matches[0][$k],
						$this->get_cached_time_format(
							$this->ymd . $this->time,
							$char
						),
						$output
					);
				}
			}
		}

		return $output;
	}
	// END format_date


	// --------------------------------------------------------------------

	/**
	 * Format date character
	 *
	 * @param	string	$format	Single character to format
	 * @return	string
	 */

	public function format_date_character($format)
	{
		$output	= '';
		$type	= FALSE;

		if (strpos($this->date_chars, $format) !== FALSE)
		{
			$type	= 'date';
		}
		elseif (strpos($this->time_chars, $format) !== FALSE)
		{
			$type	= 'time';
		}
		else
		{
			return '';
		}

		// One letter day of the week - Proprietary
		if ($format == 'b')
		{
			$output = lang('day_'.$this->day_of_week.'_1');
		}

		// Day of the month, 2 digits
		else if ($format == 'd')
		{
			$output = ($this->day >= 10) ? $this->day : '0'.$this->day;
		}

		// Three letter textual representation of the day
		else if ($format == 'D')
		{
			$output = lang('day_'.$this->day_of_week.'_3');
		}

		// Day of the month, no leading zeros
		else if ($format == 'j')
		{
			$output = (strpos($this->day, "0") === 0) ?
						substr($this->day, -1) :
						$this->day;
		}

		// Fulltext day of the week
		else if ($format == 'l')
		{
			$output = lang('day_'.$this->day_of_week.'_full');
		}

		// Numeric representation of day of week (1 = Monday, 7 = Sunday)
		else if ($format == 'N')
		{
			$output = ($this->day_of_week == 0) ? 7 : $this->day_of_week;
		}

		// Ordinal suffix for day of month, 2 characters
		// For numbers below 20 we use the ee()->lang value
		else if ($format == 'S')
		{
			$output_day = (strpos($this->day, "0") === 0) ?
							substr($this->day, -1) :
							$this->day;

			if ($this->day < 20)
			{
				$output = lang('suffix_' . $output_day);
			}
			// For numbers 20+ we look at the last digit
			else
			{
				$output = lang('suffix_' . substr($output_day, -1));
			}
		}

		// Numeric representation for day of the week (0 = Sunday)
		else if ($format == 'w')
		{
			$output			= $this->day_of_week;
		}

		// Week number of year, week starts on Monday
		else if ($format == 'W')
		{
			$output			= $this->get_week_number_W();
		}

		// Fulltext month
		else if ($format == 'F')
		{
			$output_month	= (strpos($this->month, "0") === 0) ?
								substr($this->month, -1) :
								$this->month;

			$output			= lang('month_'.$output_month.'_full');
		}

		// Month number, 2 characters
		else if ($format == 'm')
		{
			$output			= ($this->month >= 10) ?
								$this->month :
								'0' . $this->month;
		}

		// Three letter text representation of the month
		else if ($format == 'M')
		{
			$output_month	= (strpos($this->month, "0") === 0) ?
								substr($this->month, -1) :
								$this->month;

			$output			= lang('month_'.$this->month.'_3');
		}

		// Month number, no leading zeros
		else if ($format == 'n')
		{
			$output			= (strpos($this->month, "0") === 0) ?
								substr($this->month, -1) :
								$this->month;
		}

		// Number of days in the month
		else if ($format == 't')
		{
			$days			= $this->days_in_months();
			$output			= $days[$this->month - 1];
		}

		// Leap year (1 or 0)
		else if ($format == 'L')
		{
			$output = ($this->is_leap_year === TRUE) ? 1 : 0;
		}

		// ISO-8601 year number. This has the same value as Y,
		// except that if the ISO week number (W) belongs
		// to the previous or next year, that year is used instead.
		else if ($format == 'o')
		{
			// TODO
		}

		// Unix timestamp
		else if ($format == 'U')
		{
			$output = gmmktime(
				$this->hour,
				$this->minute,
				0,
				$this->month,
				$this->day,
				$this->year
			);
		}

		// Year
		else if ($format == 'Y')
		{
			$output = $this->year;
		}

		// 2-digit year
		else if ($format == 'y')
		{
			$output = ($this->year >= 10) ?
						substr($this->year, -2) :
						'0' . $this->year;
		}

		// Day of the year, 0 to 365
		else if ($format == 'z')
		{
			$output = $this->day_of_year;
		}

		// -------------------------------------
		//	Time formats
		// -------------------------------------

		// am/pm
		else if ($format == 'a')
		{
			$output = ($this->pm === TRUE) ? lang('pm') : lang('am');
		}

		// AM/PM
		else if ($format == 'A')
		{
			$output = ($this->pm === TRUE) ? lang('PM') : lang('AM');
		}

		// Hour, 12-hour format, no leading zeros
		else if ($format == 'g')
		{
			if ($this->hour == 0 OR $this->hour == 12)
			{
				$output = 12;
			}
			elseif ($this->hour < 12)
			{
				$output = $this->trim_leading_zeros($this->hour, '0');
			}
			else
			{
				$output = $this->hour - 12;
			}
		}

		// Hour, 24-hour format, no leading zeros
		else if ($format == 'G')
		{
			$output = (int) $this->hour;
		}

		// Hour, 12-hour format, leading zero
		else if ($format == 'h')
		{
			if ($this->hour == 0)
			{
				$output = 12;
			}
			elseif ($this->hour < 10)
			{
				$output = str_pad($this->hour, 2, '0', STR_PAD_LEFT);
			}
			elseif ($this->hour <= 12)
			{
				$output = $this->hour;
			}
			else
			{
				$output = str_pad(($this->hour - 12), 2, '0', STR_PAD_LEFT);
			}
		}

		// Hour, 24-hour format, leading zero
		else if ($format == 'H')
		{
			if ($this->hour < 10)
			{
				$output = str_pad($this->hour, 2, '0', STR_PAD_LEFT);
			}
			else
			{
				$output = $this->hour;
			}
		}

		// Minutes
		else if ($format == 'i')
		{
			$output = str_pad($this->minute, 2, '0', STR_PAD_LEFT);
		}

		//	1 if DST, 0 if not
		else if ($format == 'I')
		{
			$output = (ee()->config->item('daylight_savings') == 'y') ? 1 : 0;
		}

		// Seconds
		else if ($format == 's')
		{
			$output = '00';
		}

		if ($type == 'time')
		{
			$this->set_cached_date_format($this->ymd . $this->time, $format, $output);
		}
		else
		{
			$this->set_cached_date_format($this->ymd . $this->time, $format, $output);
		}

		return $output;
	}
	// END format_date_character()


	// --------------------------------------------------------------------

	/**
	 * Given a date, calculate the day of week.
	 * Uses Babwani's Congruence: http://www.babwani-congruence.blogspot.com/
	 *
	 * @param	int	$year	Year [optional]
	 * @param	int	$month	Month [optional]
	 * @param	int	$day	Day [optional]
	 * @return	int	Integer representing the day of week (0-6 => Sun - Sat)
	 */

	public function get_day_of_week($year = NULL, $month = NULL, $day = NULL)
	{
		$dow = 0;

		if (is_null($year) OR ! $this->is_valid_year($year))
		{
			$year = $this->year;
		}

		if (is_null($month) OR ! $this->is_valid_month($month))
		{
			$month = $this->month;
		}

		if (is_null($day) OR ! $this->is_valid_day($day, $month, $year))
		{
			$day = $this->day;
		}

		// -------------------------------------
		//  Assign the variables
		// -------------------------------------

		$year_decimal	= $year / 100;
		$d				= $day;
		$L				= ($this->is_leap_year($year) === TRUE) ? 1 : 0;
		$m				= $month;
		$c				= $this->floor($year_decimal);
		$y				= ($year_decimal - $c) * 100;

		// -------------------------------------
		//  Do the voodoo
		// -------------------------------------

		if ($this->calendar_type == 'Gregorian')
		{
			$dow =
				$d +
				$this->floor(
					3 +
					((2 - $L) * $this->floor(.5 + (1/$m))) +
					(((5 * $m) + $this->floor($m /9)) / 2)
				) +
				$this->floor((5 * $y) / 4) +
				(5 * ($c - (4 * $this->floor($c / 4))));
		}
		elseif ($this->calendar_type == 'Julian')
		{
			$dow =
				$d +
				$this->floor(
					3 +
					((2 - $L) * $this->floor(.5 + (1/$m))) +
					(((5 * $m) + $this->floor($m /9)) / 2)
				) +
				$this->floor((5 * $y) / 4) -
				$c - 2;
		}

		// -------------------------------------
		//  If $dow < 0, += 7 until we're >= 0
		// -------------------------------------

		while ($dow < 0)
		{
			$dow += 7;
		}

		// -------------------------------------
		//  Modulation
		// -------------------------------------

		$dow = $dow % 7;

		// -------------------------------------
		//  The algorithm gives us 0 = Saturday,
		//  6 = Friday. Let's make it 0 = Sunday
		// -------------------------------------

		$dow = ($dow == 0) ? 6 : $dow - 1;

		return $dow;
	}
	/* END get_day_of_week() */


	// --------------------------------------------------------------------

	/**
	 * Get the day of the year
	 *
	 * @param	int	$year [optional]	Year
	 * @param	int	$month [optional]	Month
	 * @param	int	$day [optional]		Day
	 * @return	int
	 */

	public function get_day_of_year($year = NULL, $month = NULL, $day = NULL)
	{
		$year			= (is_null($year)) ? $this->year : $year;
		$month			= (is_null($month)) ? $this->month : $month;
		$day			= (is_null($day)) ? $this->day : $day;
		$days_in_months	= $this->days_in_months($year);
		$day_of_year	= 0;

		for ($i = 1; $i < $month; $i++)
		{
			$day_of_year += $days_in_months[$i-1];
		}

		$day_of_year += $day;

		return $day_of_year;
	}
	/* END get_day_of_year */

	// --------------------------------------------------------------------

	/**
	 * Get the week number - Week starts on Sunday
	 *
	 * @param	int	$day	Day [optional]
	 * @param	int	$year	Year [optional]
	 * @return	int
	 */

	public function get_week_number($day = NULL, $year = NULL)
	{
		$year	= (is_null($year)) ? $this->year : $year;
		$day	= (is_null($day)) ? $this->day_of_year : $day;

		$week	= 0;
		for ($i = $day + $this->get_day_of_week($year, 1, 1); $i > 0; $i -= 7)
		{
			$week++;
		}

		return $week;
	}
	/* END get_week_number() */

	// --------------------------------------------------------------------

	/**
	 * Get the week number - Week starts on Monday
	 *
	 * @param	int	$day 	Day [optional]
	 * @param	int	$year 	Year [optional]
	 * @return	int
	 */

	public function get_week_number_W($day = NULL, $year = NULL)
	{
		$year	= (is_null($year)) ? $this->year : $year;
		$day	= (is_null($day)) ? $this->day_of_year() : $day;

		$week	= 0;

		for ($i = $this->day_of_year + $this->get_day_of_week($this->year, 1, 1) - 1; $i > 0; $i -= 7)
		{
			$week++;
		}
		return $week;
	}
	/* END get_week_number_W() */

	// --------------------------------------------------------------------

	/**
	 * Add year(s)
	 *
	 * @param	int		$count 			Number of years to add. Can be negative. [optional]
	 * @param	bool	$change_date	Run the change_date() method? [optional]
	 * @return	null
	 */

	public function add_year($count = 1, $change_date = TRUE, $end_on_last_day = FALSE)
	{
		$year			= $this->year + $count;

		if ($change_date !== FALSE)
		{
			//--------------------------------------------
			//	when changing the date, we have some
			//	special rules that can be used..
			//--------------------------------------------

			switch ($end_on_last_day)
			{
				case 'forward':
					$month 	= 12;
					$day	= $this->days_in_month($month);
					$this->change_time(23, 59);
					break;

				case 'backward':
					$month 	= 1;
					$day	= 1;
					$this->change_time(23, 59);
					break;

				case FALSE:
				default:
					$month 	= $this->month;
					$day 	= $this->day;
					break;

			}

			$this->change_date($year, $month, $day);
		}
		else
		{
			$this->year			= $year;
			$this->is_leap_year	= $this->is_leap_year();
		}

		return $this->datetime_array();
	}
	/* END add_year() */

	// --------------------------------------------------------------------

	/**
	 * Add month(s)
	 *
	 * @param	int		$count 	Number of months to add. Can be negative. [optional]
	 * @param	bool	$change_date Run the change_date() method? [optional]
	 * @return	null
	 */

	public function add_month($count = 1, $change_date = TRUE, $end_on_last_day = FALSE)
	{
		$month	= $this->month + $count;
		$year	= $this->year;

		// -------------------------------------
		//  Has our addition affected the year?
		// -------------------------------------

		if ($month > 12)
		{
			while ($month > 12)
			{
				$year++;
				$month -= 12;
			}
		}
		elseif ($month < 1)
		{
			while ($month < 1)
			{
				$year--;
				$month += 12;
			}
		}

		//if this is from show_months we want to end on the last day of the last month
		if ($change_date !== FALSE)
		{
			//--------------------------------------------
			//	when changing the date, we have some
			//	special rules that can be used..
			//--------------------------------------------

			switch ($end_on_last_day)
			{
				case 'forward':
					$day	= $this->days_in_month($month);
					$this->change_time(23, 59);
					break;

				case 'backward':
					$day	= 1;
					$this->change_time(23, 59);
					break;

				case FALSE:
				default:
					$day 	= $this->day;
					break;
			}

			$this->change_date($year, $month, $day);
		}

		return $this->datetime_array();
	}
	/* END add_month() */

	// --------------------------------------------------------------------

	/**
	 * Add day(s)
	 *
	 * @param	int		$count			Number of days to add. Can be negative. [optional]
	 * @param	bool	$change_date	Run the change_date() method? [optional]
	 * @return	null
	 */

	public function add_day($count = 1)
	{
		$day = $this->day + $count;

		// -------------------------------------
		//  Have we affected the month and year?
		// -------------------------------------

		$days_in_months = $this->days_in_months();

		if ($day > $days_in_months[$this->month - 1])
		{
			while ($day > $days_in_months[$this->month - 1])
			{
				$day -= $days_in_months[$this->month - 1];
				$this->add_month(1);

				// -------------------------------------
				//  Gotta do this because of leap years
				// -------------------------------------

				$days_in_months = $this->days_in_months();
			}
		}
		elseif ($day < 1)
		{
			while ($day < 1)
			{
				$this->add_month(-1);
				$day += $days_in_months[$this->month - 1];

				// -------------------------------------
				//  Gotta do this because of leap years
				// -------------------------------------

				$days_in_months = $this->days_in_months();
			}
		}

		$this->change_date($this->year, $this->month, $day);

		return $this->datetime_array();
	}
	/* END add_day() */

	// --------------------------------------------------------------------

	/**
	 * Add time to this instance
	 *
	 * @param	int		$time	Time in the format 0100 or 100, optionally preceded by + or -
	 * @param	bool	$change_date	Run the change_date() method? [optional]
	 * @return
	 */

	public function add_time($time, $change_date = TRUE)
	{
		$dir = 1;

		// -------------------------------------
		//  Plus or minus
		// -------------------------------------

		if (strpos($time, '+') === 0)
		{
			$time	= str_replace('+', '', $time);
		}
		elseif (strpos($time, '-') === 0)
		{
			$dir	= -1;
			$time	= str_replace('-', '', $time);
		}

		// -------------------------------------
		//  I don't understand!
		// -------------------------------------

		if (strlen($time) < 3 OR strlen($time) > 4)
		{
			return;
		}

		// -------------------------------------
		//  Get hours and minutes
		// -------------------------------------

		$minutes	= substr($time, -2);
		$hours		= substr($time, 0, strlen($time) - 2);

		// -------------------------------------
		//  Add minutes
		// -------------------------------------

		$this->minute += ($minutes * $dir);

		if ($this->minute > 59)
		{
			while ($this->minute > 59)
			{
				$hours++;
				$this->minute -= 60;
			}
		}
		elseif ($this->minute < 0)
		{
			while ($this->minute < 0)
			{
				$hours--;
				$this->minute += 60;
			}
		}

		// -------------------------------------
		//  Add hours
		// -------------------------------------

		$days = 0;
		$this->hour += ($hours * $dir);

		if ($this->hour > 23)
		{
			while ($this->hour > 23)
			{
				$days++;
				$this->hour -= 24;
			}
		}
		elseif ($this->hour < 0)
		{
			while ($this->hour < 0)
			{
				$days--;
				$this->hour += 24;
			}
		}

		// -------------------------------------
		//  Adjust days?
		// -------------------------------------

		if ($days != 0)
		{
			$this->add_day($days, $change_date);
		}

		$this->change_time($this->hour, $this->minute);

		return $this->datetime_array();
	}
	/* END add_time() */

	// --------------------------------------------------------------------

	/**
	 * Trim leading zeroes
	 *
	 * @param	str	$str	String to trim
	 * @return
	 */

	public function trim_leading_zeros($str)
	{
		return ltrim($str, '0');
	}
	/* END trim_leading_zeros() */

	// --------------------------------------------------------------------

	/**
	 * Make a YMD
	 *
	 * @param	int	$year	Year
	 * @param	int	$month	Month
	 * @param	int	$day	Day
	 * @return	int
	 */

	public function make_ymd($year, $month, $day)
	{
		if ($month < 10)
		{
			$month	= '0'.$month;
		}
		if ($day < 10)
		{
			$day	= '0'.$day;
		}

		return $year.$month.$day;
	}
	/* END make_ymd() */

	// --------------------------------------------------------------------

	/**
	 * Turn a YMD into an array
	 *
	 * @param	int	$ymd	YMD
	 * @return	array
	 */

	public function ymd_to_array($ymd)
	{
		$array = array();

		$array['year']	= substr($ymd, 0, strlen($ymd) - 4);
		$array['month']	= substr($ymd, -4, 2);
		$array['day']	= substr($ymd, -2);
		$array['ymd']	= $ymd;

		return $array;
	}
	/* END ymd_to_array() */

	// --------------------------------------------------------------------

	/**
	 * Get
	 *
	 * @param	mixed	$var	Variable
	 * @return	mixed
	 */

	public function get($var)
	{
		return (isset($this->$var)) ? $this->$var : NULL;
	}
	/* END get() */

	// --------------------------------------------------------------------

	/**
	 * Floor that helps correct for rounding errors
	 *
	 * @param	float	$n	Number
	 * @return	int
	 */

	protected function floor($n)
	{
		return floor($n+0.000000000001);
	}
	/* END floor() */

	// --------------------------------------------------------------------

}
// END CLASS Calendar_datetime