<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - Parameters
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/calendar.parameters.php
 */

class Calendar_parameters extends Addon_builder_calendar {

	public $params		= array();
	public $dynamic		= array();

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct ()
	{
		parent::__construct();
		$this->actions();
		$this->fetch_dynamic_parameters();
	}
	//END Calendar_parameters()


	// --------------------------------------------------------------------

	/**
	 * Fetch dynamic parameters
	 *
	 * @return	bool
	 */

	public function fetch_dynamic_parameters ()
	{
		if (! ee()->TMPL->fetch_param('dynamic_parameters'))
		{
			return FALSE;
		}

		foreach (explode('|', ee()->TMPL->fetch_param('dynamic_parameters')) as $val)
		{
			$this->dynamic[$val]	= $val;
		}

		return TRUE;
	}
	//END fetch_dynamic_parameters()


	// --------------------------------------------------------------------

	/**
	 * Choose method
	 *
	 * @param	string	$param				Name of parameter
	 * @param	mixed	$dynamic_methods	Dynamic methods
	 * @param	mixed	$static_methods		Static methods
	 * @return	bool
	 */

	public function choose_method ($param, $dynamic_methods = array('tmpl', 'POST', 'GET'), $static_methods = 'tmpl')
	{
		if (isset($this->dynamic[$param]))
		{
			return $dynamic_methods;
		}

		return $static_methods;
	}
	//END choose_method()


	// --------------------------------------------------------------------

	/**
	 * Add a parameter
	 *
	 * @param	string	$name		Parameter name
	 * @param	array	$details	Array of parameter details
	 * @return	bool
	 */

	public function add_parameter ($name, $details = array())
	{
		// -------------------------------------
		//  Initialize
		// -------------------------------------

		$valid_values = array(
			'required'			=> array(FALSE, TRUE),
			'type'				=> array(
				'string',
				'integer',
				'number',
				'date',
				'time',
				'bool'
			),
			'multi'				=> array(FALSE, TRUE),
			'method'			=> array(
				'tmpl',
				'GET',
				'POST',
				'cookie'
			),
			'case_sensitive'	=> array(FALSE, TRUE)
		);

		// -------------------------------------------
		// 'calendar_parameters_valid_values' hook.
		//  - Modify the valid values
		// -------------------------------------------

		$hook = 'calendar_parameters_valid_values';

		if (ee()->extensions->active_hook($hook) === TRUE)
		{
			$valid_values = ee()->extensions->call($hook, $valid_values);
			if (ee()->extensions->end_script === TRUE) return;
		}

		$default = array(
			'name'				=> $name,
			'required'			=> FALSE,
			'type'				=> array('string'),
			'default'			=> '',
			'multi'				=> FALSE,
			'min_value'			=> FALSE,
			'max_value'			=> FALSE,
			'allowed_values'	=> array(),
			'method'			=> $this->choose_method($name),
			'case_sensitive'	=> FALSE,
			'not'				=> FALSE
		);

		// -------------------------------------------
		// 'calendar_parameters_default_values' hook.
		//  - Modify the default values
		// -------------------------------------------

		$hook = 'calendar_parameters_default_values';

		if (ee()->extensions->active_hook($hook) === TRUE)
		{
			$default = ee()->extensions->call($hook, $default);
			if (ee()->extensions->end_script === TRUE) return;
		}

		$param = array();

		// -------------------------------------
		//  Replace defaults with supplied values, where necessary
		// -------------------------------------

		foreach ($default as $k => $v)
		{
			if (! isset($details[$k]))
			{
				$param[$k] = $v;
				continue;
			}
			elseif (! isset($valid_values[$k]))
			{
				$param[$k] = $details[$k];
				continue;
			}
			elseif (is_array($details[$k]))
			{
				$good = array();

				foreach ($details[$k] as $pv)
				{
					if (in_array($pv, $valid_values[$k]))
					{
						$good[] = $pv;
					}
				}

				if (! empty($good))
				{
					$param[$k] = $good;
				}
				else
				{
					$param[$k] = $v;
				}
			}
			else
			{
				if (in_array($details[$k], $valid_values[$k]))
				{
					$param[$k] = $details[$k];
				}
				else
				{
					$param[$k] = $v;
				}
			}
		}

		// -------------------------------------
		//  Get the value using the approved method(s)
		// -------------------------------------

		$value = $this->fetch_value($name, $param['method']);

		// -------------------------------------
		//  If the value is empty, use the default
		// -------------------------------------

		if ($value == '')
		{
			$value = $param['default'];
		}

		// -------------------------------------
		//  You're so not-y
		// -------------------------------------

		if ($param['not'] === TRUE AND substr($value, 0, 4) == 'not ')
		{
			$value	= substr($value, 4);
		}
		else
		{
			$param['not']	= FALSE;
		}

		if (($new_value = $this->validate_value($value, $param)) === FALSE)
		{
			$value = FALSE;
		}
		elseif ($new_value !== TRUE)
		{
			$value = $new_value;
		}

		// -------------------------------------
		//  Set the value
		// -------------------------------------

		$this->params[$name] = array(
			'value'		=> $value,
			'details'	=> $param
		);
	}
	//END add_parameter()


	// --------------------------------------------------------------------

	/**
	 * Set
	 *
	 * @param	string	$which	Name of the parameter
	 * @param	mixed	$value	Value
	 * @return	null
	 */

	public function set ($which, $value)
	{
		$this->params[$which]['value'] = $value;
	}
	//END set()


	// --------------------------------------------------------------------

	/**
	 * Fetch value
	 *
	 * @param	string	$name		Parameter name
	 * @param	mixed	$methods	String method or array of methods
	 * @return	string
	 */

	public function fetch_value ($name, $methods = array('tmpl'))
	{
		$value = FALSE;

		if (! is_array($methods))
		{
			$methods = array($methods);
		}

		foreach ($methods as $method)
		{
			switch ($method)
			{
				case 'tmpl' :
					$value = ee()->TMPL->fetch_param($name);
					break;

				case 'GET' :
					$value = ee()->input->get($name);
					break;

				case 'POST' :
					$value = ee()->input->post($name);
					break;

				case 'cookie' :
					$value = ee()->input->cookie($name);
					break;

				default :
					// -------------------------------------------
					// 'calendar_parameters_additional_method' hook.
					//  - Use other methods to fetch a value
					// -------------------------------------------

					$hook = 'calendar_parameters_additional_method';
					if (ee()->extensions->active_hook($hook) === TRUE)
					{
						$value = ee()->extensions->call($hook, $method, $name);
						if (ee()->extensions->end_script === TRUE) return;
					}

					break;
			}

			if ($value !== FALSE)
			{
				break;
			}
		}

		return $value;
	}
	//END fetch_value()


	// --------------------------------------------------------------------

	/**
	 * Return the value of a specific parameter
	 *
	 * @param	string	$which	Name of the parameter
	 * @param	string	$key	Array key [optional]
	 * @return	mixed
	 */

	public function value ($which, $key = FALSE)
	{
		if ($key === FALSE)
		{
			if (isset($this->params[$which]) AND
				$this->params[$which]['value'] !== FALSE AND
				$this->params[$which]['value'] !== '')
			{
				return $this->params[$which]['value'];
			}
		}
		else
		{
			if (isset($this->params[$which]['value'][$key]))
			{
				return $this->params[$which]['value'][$key];
			}
		}

		return FALSE;
	}
	//END value()


	// --------------------------------------------------------------------

	/**
	 * Validate value
	 *
	 * @param	string	$value		Value name
	 * @param	array	$details	Array of details
	 * @return	bool
	 */

	public function validate_value ($value, $details)
	{
		// -------------------------------------
		//  Required?
		// -------------------------------------

		if ($details['required'] === TRUE)
		{
			if ($value == '')
			{
				return FALSE;
			}
		}
		elseif ($value == '')
		{
			return TRUE;
		}

		// -------------------------------------
		//  Check the type
		// -------------------------------------

		if ($details['multi'] !== TRUE)
		{
			if (strstr($value, '|'))
			{
				return FALSE;
			}

			return $this->validate_type($value, $details);
		}
		else
		{
			$values = explode('|', $value);
			foreach ($values as $val)
			{
				if (($valid = $this->validate_type($val, $details)) === FALSE)
				{
					return FALSE;
				}
			}
		}

		return $valid;
	}
	//END validate_value()


	// --------------------------------------------------------------------

	/**
	 * Validate type
	 *
	 * @param	mixed	$value		The value to use when validating the type
	 * @param	array	$details	Parameter details
	 * @return	bool
	 */

	public function validate_type ($value, $details)
	{
		switch($details['type'])
		{
			case 'string' :
				if ($this->validate_string($value, $details) === TRUE)
				{
					return TRUE;
				}
				break;

			case 'number' :
				if ($this->validate_number($value, $details) === TRUE)
				{
					return TRUE;
				}
				break;

			case 'integer' :
				if ($this->validate_integer($value, $details) === TRUE)
				{
					return TRUE;
				}
				break;

			case 'bool' :
				if ($this->validate_boolean($value, $details) === TRUE)
				{
					return TRUE;
				}
				break;

			case 'date' :
				$valid = $this->validate_date($value, $details);
				if ($valid !== FALSE)
				{
					return $valid;
				}
				break;
			case 'time' :
				$valid = $this->validate_time($value, $details);
				if ($valid !== FALSE)
				{
					return $valid;
				}
				break;

			default :

				// -------------------------------------------
				// 'calendar_parameters_additional_type_validation' hook.
				//  - Validate additional types
				// -------------------------------------------

				$hook = 'calendar_parameters_additional_type_validation';

				if (ee()->extensions->active_hook($hook) === TRUE)
				{
					return ee()->extensions->call($hook, $value, $details);
				}

				break;
		}

		return FALSE;
	}
	//END validate_type()


	// --------------------------------------------------------------------

	/**
	 * Validate a string type
	 *
	 * @param	string	$value		Value to check
	 * @param	array	$details	Parameter details
	 * @return	bool
	 */

	public function validate_string ($value, $details)
	{
		return $this->actions->is_allowed_value(
			$value,
			$details['allowed_values'],
			$details['case_sensitive']
		);
	}
	//END validate_string()


	// --------------------------------------------------------------------

	/**
	 * Validate a number type
	 *
	 * @param	int		$value			Value to check
	 * @param	array	$details	Parameter details
	 * @return	bool
	 */

	public function validate_number ($value, $details)
	{
		return (
			is_numeric($value) AND
			$this->actions->is_in_range(
					$value,
					$details['min_value'],
					$details['max_value']
			) AND
			$this->actions->is_allowed_value(
				$value,
				$details['allowed_values'],
				$details['case_sensitive']
			)
		);
	}
	//END validate_number()


	// --------------------------------------------------------------------

	/**
	 * Validate an integer type
	 *
	 * @param	int		$value		Value to check
	 * @param	array	$details	Parameter details
	 * @return	bool
	 */

	public function validate_integer ($value, $details)
	{

		return (
			$this->actions->is_integer($value) AND
			$this->actions->is_in_range(
				$value,
				$details['min_value'],
				$details['max_value']
			) AND
			$this->actions->is_allowed_value(
				$value,
				$details['allowed_values'],
				$details['case_sensitive']
			)
		);
	}
	//END validate_integer()


	// --------------------------------------------------------------------

	/**
	 * Validate a boolean type
	 *
	 * @param	string	$value		Value to check
	 * @param	array	$details	Parameter details
	 * @return	bool
	 */

	public function validate_boolean ($value, $details)
	{
		$yn = array('y', 'yes', 'no', 'n');
		return in_array(strtolower($value), $yn);
	}
	//END validate_boolean


	// --------------------------------------------------------------------

	/**
	 * Sometimes we all need a pick-me-up
	 *
	 * @param	string	$value		Value to check
	 * @param	array	$details	Parameter details
	 * @return	validation
	 */

	public function validate_me ($value, $details)
	{
		return 'You are a good and special person, like Low.';
	}
	//END validate_me()


	// --------------------------------------------------------------------

	/**
	 * Validate a date type
	 *
	 * @param	string	$value		Value to check
	 * @param	array	$details	Parameter details
	 * @return	bool
	 */

	public function validate_date ($value, $details)
	{
		if ( ! class_exists('Calendar_datetime'))
		{
			require_once CALENDAR_PATH.'calendar.datetime.php';
		}

		$CDT = new Calendar_datetime();

		$end = (substr($details['name'], -4) == '_end') ? TRUE : FALSE;

		return $this->actions->parse_text_date($value, $CDT, $end);
	}
	//END validate_date()


	// --------------------------------------------------------------------

	/**
	 * Validate a time type
	 *
	 * @param	string	$value		Value
	 * @param	array	$details	Parameter details
	 * @return	int
	 */

	public function validate_time ($value, $details)
	{
		return $this->actions->parse_text_time($value);
	}
	//END validate_time()
}
// END CLASS Calendar_parameters