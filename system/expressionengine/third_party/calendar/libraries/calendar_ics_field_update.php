<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - ICS Field Update
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @filesource	calendar/libraries/calendar_permissions.php
 */

class Calendar_ics_field_update
{
	/**
	 * cache
	 *
	 * @var array
	 */
	public $cache = array();

	/**
	 * EE object instance
	 *
	 * @var object
	 * @see __construct
	 */
	public $EE;

	/**
	 * Supported Third Party Fields
	 *
	 * @var array
	 */
	public $special_fields = array(
		'assets',
		'date',
		//'playa',
		'pt_list'
	);

	/**
	 * Field Type Cache
	 *
	 * @var array
	 */
	public static $field_type_cache = array();


	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 */

	public function	__construct()
	{
		$this->EE =& get_instance();
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Procress Fields
	 *
	 * @access	public
	 * @param	array	$row	row input
	 * @return	array	row with fields prepped for saving
	 */

	public function run_prep_fields($row = array())
	{
		$this->fill_field_cache();

		foreach ($row as $field_name => $data)
		{
			preg_match("/^field_id_(\d+)$/is", $field_name, $match);

			if ($match &&
				array_key_exists($match[1], self::$field_type_cache) &&
				in_array(self::$field_type_cache[$match[1]], $this->special_fields))
			{
				$type = self::$field_type_cache[$match[1]];

				if (is_callable(array($this, $type . '_prep_data')))
				{
					$n_data = $this->{$type . '_prep_data'}($data);

					$row[$field_name] = $n_data;
				}
			}
		}

		return $row;
	}
	//END run_prep_fields


	// --------------------------------------------------------------------

	/**
	 * Fill field cache
	 *
	 * @static
	 * @access	public
	 * @return	array	field id -> type array
	 */
	public static function fill_field_cache()
	{
		if (empty(self::$field_type_cache))
		{
			$query = get_instance()->db
							->select('field_id, field_type')
							->get('channel_fields');

			self::$field_type_cache = array();

			foreach ($query->result_array() as $row)
			{
				self::$field_type_cache[$row['field_id']] = $row['field_type'];
			}
		}

		return self::$field_type_cache;
	}
	//END fill_field_cache


	// --------------------------------------------------------------------

	/**
	 * Pixel & Tonic List Prep
	 *
	 * @access	public
	 * @param	string	$data	input data
	 * @return	array			data split to array
	 */

	public function pt_list_prep_data($data = '')
	{
		return explode(PHP_EOL, $data);
	}
	//END pt_list_prep_data


	// --------------------------------------------------------------------

	/**
	 * Date Field Prep
	 *
	 * @access	public
	 * @param	string	$data	input data
	 * @return	string			prepped data
	 */

	public function date_prep_data($data = '')
	{
		if ( empty($data))
		{
			return FALSE;
		}

		$data = preg_replace('/\040+/', ' ', trim($data));

		if (preg_match("/^[0-9]+$/", $data, $match))
		{
			if (is_callable(array(ee()->localize, 'human_time')))
			{
				$data = ee()->localize->human_time($data);
			}
			else
			{
				$data = ee()->localize->set_human_time($data);
			}


		}
		// Valid human readable
		else if ( ! preg_match('/^[0-9]{2,4}\-[0-9]{1,2}\-[0-9]{1,2}\s[0-9]{1,2}:[0-9]{1,2}(?::[0-9]{1,2})?(?:\s[AP]M)?$/i', $data))
		{
			$data = '';
		}

		return $data;
	}
	//END date_prep_data


	// --------------------------------------------------------------------

	/**
	 * Playa Field Prep
	 *
	 * @access	public
	 * @param	string	$data	input data
	 * @return	string			prepped data
	 */

	public function playa_prep_data($data = '')
	{
		if ( $data === NULL )
		{
			return FALSE;
		}

		if (is_string($data))
		{
			$data = preg_split("/[\r\n,\|]+/", $data, -1, PREG_SPLIT_NO_EMPTY);
		}

		if ( ! is_array($data))
		{
			return array();
		}

		$return['selections'] = array();

		foreach($data as $value)
		{
			if (is_numeric($value))
			{
				$return['selections'][] = $value;
			}
			// [###] Entry Title - entry_url_title
			elseif(preg_match('/\[(\!)?(\d+)\]/', $value, $match))
			{
				$return['selections'][] = $match[2];
			}
		}

		return $return;
	}
	//END playa_prep_data


	// --------------------------------------------------------------------

	/**
	 * Prep Assets Data
	 *
	 * @access	public
	 * @param	string	$data	input data
	 * @return	string			prepped data
	 */

	public function assets_prep_data($data = '')
	{
		if (ee()->db->table_exists('exp_assets_selections') === TRUE)
		{
			$query = ee()->db->get_where(
				'exp_assets_selections',
				array('entry_id' => $entry_id)
			);
		}
		else
		{
			$query = ee()->db->get_where(
				'exp_assets_entries',
				array('entry_id' => $entry_id)
			);
		}

		if ($query->num_rows() == 0){ return ''; }

		$return = array();

		foreach($query->result_array() AS $row)
		{
			$return[] = (isset($row['file_id'])) ? $row['file_id'] : $row['asset_id'];
		}

		return $return;
	}
	//END assets_prep_data
}
//END Calendar_ics_field_update
