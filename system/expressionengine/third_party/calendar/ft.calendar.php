<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - Field Type
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/ft.calendar.php
 */

if ( ! defined('CALENDAR_VERSION'))
{
	require_once 'constants.calendar.php';
}

class Calendar_ft extends EE_Fieldtype
{
	public $info = array(
		'name' => 'Calendar',
		'version' => CALENDAR_VERSION
	);

	// --------------------------------------------------------------------

	function display_field($data)
	{
		//	This block was previously in the constructor.
		//	It caused interminable hell on EE 2 install and uninstall.
		//	There's only one method in this file.
		//	So move all this garbage here and have pancakes for breakfast.

		require_once 'mod.calendar.php';
		require_once 'ext.calendar.php';
		require_once 'constants.calendar.php';

		$this->cal_mod = new Calendar();
		$this->cal_ext = new Calendar_ext();
		$this->cal_mod->actions();

		if ( ! defined('CALENDAR_CALENDARS_CHANNEL_NAME'))
		{
			define('CALENDAR_CALENDARS_CHANNEL_NAME', 	$this->cal_mod->data->calendar_channel_shortname());
			define('CALENDAR_EVENTS_CHANNEL_NAME', 		$this->cal_mod->data->event_channel_shortname());
		}

		$channel_id = ee()->input->get_post('channel_id');
		$entry_id 	= (
			is_numeric(ee()->input->get_post('entry_id')) AND
			ee()->input->get_post('entry_id') > 0
		) ? ee()->input->get_post('entry_id') : 0;

		$output 	= '';

		$output		.= $this->cal_mod->actions->datepicker_js();

		$output		.= $this->cal_mod->actions->datepicker_css();

		$field_data = array(
			'name'	=> $this->field_name,
			'id'	=> $this->field_id,
			'value'	=> $data
		);

		if ($this->cal_mod->data->channel_is_calendars_channel($channel_id) === TRUE)
		{
			$output .= form_input($field_data);
			$output .= $this->cal_ext->publish_form_end($channel_id, TRUE, $field_data);
		}
		else if ($this->cal_mod->data->channel_is_events_channel($channel_id) === TRUE)
		{
			// -------------------------------------
			//	calendar permissions
			// -------------------------------------

			ee()->load->library('calendar_permissions');

			if ($entry_id > 0)
			{
				$temp = $this->cal_mod->data->get_calendar_id_by_event_entry_id($entry_id);
				$parent_calendar_id =  (isset($temp[$entry_id]) ? $temp[$entry_id] : 0);
			}

			if ( $entry_id != 0 AND
				 $parent_calendar_id > 0 AND
				 ! ee()->calendar_permissions->group_has_permission(
					ee()->session->userdata['group_id'],
					$parent_calendar_id
			))
			{
				return $this->cal_mod->show_error(lang('invalid_calendar_permissions'));
			}

			// -------------------------------------
			//	display data
			// -------------------------------------

			$data = array();

			$output .= form_hidden($this->field_name, $entry_id);
			$output .= '<script type="text/javascript">var CALENDAR_FIELD_INPUT = "' . $this->field_name . '";</script>';

			$output .= '<div id="find_calendar_fields"></div>';

			if ($entry_id > 0)
			{
				$data = $this->cal_mod->data->fetch_event_data_for_view($entry_id);
			}

			if ( ! isset($data['rules'])) 		$data['rules'] 			= array();
			if ( ! isset($data['occurrences'])) $data['occurrences'] 	= array();
			if ( ! isset($data['exceptions'])) 	$data['exceptions'] 	= array();

			$eoid = $this->cal_mod->data->get_event_entry_id_by_channel_entry_id($entry_id);

			if ($entry_id > 0 AND $eoid != FALSE AND $eoid != $entry_id)
			{
				$data['edit_occurrence'] = TRUE;
			}
			else
			{
				$data['edit_occurrence'] = FALSE;
			}

			$output	.= $this->cal_mod->actions->date_widget($data);
		}

		return $output;
	}
	/* END display_field() */
}