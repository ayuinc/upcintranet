<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calendar - Updater
 *
 * In charge of the install, uninstall, and updating of the module.
 *
 * @package		Solspace:Calendar
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2010-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/calendar
 * @license		http://www.solspace.com/license_agreement
 * @version		1.8.9
 * @filesource	calendar/upd.calendar.php
 */

if ( ! class_exists('Module_builder_calendar'))
{
	require_once 'addon_builder/module_builder.php';
}

class Calendar_upd extends Module_builder_calendar
{

	public $module_actions	= array();
	public $hooks			= array();
	public $channel;
	public $blog;
	public $settings		= array();

	// --------------------------------------------------------------------

	/**
	 * Contructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct( )
	{
		parent::__construct();

		// --------------------------------------------
		//  Module Actions
		// --------------------------------------------

		$this->module_actions = array('permissions_json');

		// --------------------------------------------
		//  Extension Hooks
		// --------------------------------------------

		$this->default_settings = array();

		$default = array(
			'class'			=> $this->extension_name,
			'settings'		=> '',
			'priority'		=> 10,
			'version'		=> CALENDAR_VERSION,
			'enabled'		=> 'y'
		);

		$this->hooks = array(
			array_merge($default,
				array(
					'method'	=> 'delete_entries_loop',
					'hook'		=> 'delete_entries_loop'
				)
			),
			array_merge($default,
				array(
					'method'	=> 'entry_submission_end',
					'hook'		=> 'entry_submission_end'
				)
			),
			array_merge($default,
				array(
					'method'	=> 'cp_js_end',
					'hook'		=> 'cp_js_end'
				)
			),
			array_merge($default,
				array(
					'method'	=> 'edit_entries_additional_where',
					'hook'		=> 'edit_entries_additional_where'
				)
			),
			array_merge($default,
				array(
					'method'	=> 'update_multi_entries_start',
					'hook'		=> 'update_multi_entries_start'
				)
			),
			array_merge($default,
				array(
					'method'	=> 'delete_entries_start',
					'hook'		=> 'delete_entries_start'
				)
			),
		);
	}
	// END __construct


	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */

	public function install()
	{
		// Already installed, let's not install again. That would be silly.
		if ($this->database_version() !== FALSE)
		{
			return FALSE;
		}

		// -------------------------------------
		//  Do any field groups already exist?
		// -------------------------------------

		$calendars_fields		= array();
		$calendars_field_group	= '';
		$events_fields			= array();
		$events_field_group		= '';
		$calendars_weblogs		= array();
		$events_weblogs			= array();

		// -------------------------------------
		//  calendars
		// -------------------------------------

		$sql = "SELECT		wf.field_id, wf.field_name, wf.group_id
				FROM		exp_field_groups fg
				LEFT JOIN	{$this->sc->db->channel_fields} AS wf
				ON			fg.group_id = wf.group_id
				WHERE		fg.group_name = '" . CALENDAR_CALENDARS_FIELD_GROUP . "'
				AND			fg.site_id = '" . ee()->db->escape_str($this->data->get_site_id()) . "'";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if ($calendars_field_group == '')
				{
					$calendars_field_group = $row['group_id'];
				}
				$calendars_fields[$row['field_name']] = $row['field_id'];
			}
		}

		// -------------------------------------
		//  events
		// -------------------------------------

		$sql = "SELECT 		wf.field_id, wf.field_name, wf.group_id
				FROM 		exp_field_groups AS fg
				LEFT JOIN 	{$this->sc->db->channel_fields} AS wf
				ON 			fg.group_id = wf.group_id
				WHERE 		fg.group_name = '" . CALENDAR_EVENTS_FIELD_GROUP . "'
				AND 		fg.site_id = '" . ee()->db->escape_str($this->data->get_site_id()) . "'";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if ($events_field_group == '')
				{
					$events_field_group = $row['group_id'];
				}
				$events_fields[$row['field_name']] = $row['field_id'];
			}
		}

		// -------------------------------------
		//  default names
		// -------------------------------------

		$sql = "SELECT 	w.*
				FROM 	{$this->sc->db->channels} AS w
				WHERE 	{$this->sc->db->channel_name} = '" .
						CALENDAR_CALENDARS_CHANNEL_NAME_DEFAULT . "'
				AND 	w.site_id = '" .
						ee()->db->escape_str($this->data->get_site_id()) . "'";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$calendars_weblogs[$row[$this->sc->db->channel_name]] = $row;
			}
		}

		// -------------------------------------
		//  default event names
		// -------------------------------------

		$sql = "SELECT 	w.*
				FROM 	{$this->sc->db->channels} AS w
				WHERE 	{$this->sc->db->channel_name} = '" .
						CALENDAR_EVENTS_CHANNEL_NAME_DEFAULT . "'
				AND 	w.site_id = '" .
						ee()->db->escape_str($this->data->get_site_id()) . "'";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$events_weblogs[$row[$this->sc->db->channel_name]] = $row;
			}
		}

		// --------------------------------------------
		//  Our Default Install
		// --------------------------------------------

		if ($this->default_module_install() == FALSE)
		{
			return FALSE;
		}

		$sql = array();

		// --------------------------------------------
		//  Module Install
		// --------------------------------------------
		$insert_data	= array(
			'module_name'		=> $this->class_name,
			'module_version'	=> CALENDAR_VERSION,
			'has_cp_backend'	=> 'y'
		);

		$data['has_publish_fields'] = 'n';

		$sql[] = ee()->db->insert_string('exp_modules', $insert_data);

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		// -------------------------------------
		//  Create the calendars field group
		// -------------------------------------

		ee()->load->library('api');
		ee()->api->instantiate('channel_fields');
		ee()->api->instantiate('channel_structure');

		$old_POST = $_POST;

		if ($calendars_field_group == '')
		{
			$data = array(
				'site_id'		=>	$this->data->get_site_id(),
				'group_name'	=>	CALENDAR_CALENDARS_FIELD_GROUP
			);

			ee()->db->insert('exp_field_groups', $data);
			$calendars_field_group = ee()->db->insert_id();
		}

		// -------------------------------------
		// Create the calendars fields
		// -------------------------------------

		$fields = array();

		$order_count = 0;

		if ( ! isset($calendars_fields[CALENDAR_CALENDARS_FIELD_PREFIX . 'summary']))
		{
			$fields[] = array(
				'site_id'				=>	$this->data->get_site_id(),
				'group_id'				=>	$calendars_field_group,
				'field_name'			=>	CALENDAR_CALENDARS_FIELD_PREFIX . 'summary',
				'field_label'			=>	lang('summary'),
				'field_instructions'	=>	'',
				'field_type'			=>	'textarea',
				'field_list_items'		=>	'',
				'field_pre_populate'	=>	'n',
				'field_pre_blog_id'		=>	'0',
				'field_pre_field_id'	=>	'0',
				'field_ta_rows'			=>	'4',
				'field_maxl'			=>	'0',
				'field_required'		=>	'n',
				'field_text_direction'	=>	'ltr',
				'field_search'			=>	'y',
				'field_is_hidden'		=>	'n',
				'field_fmt'				=>	'xhtml',
				'field_show_fmt'		=>	'y',
				'field_order'			=>	++$order_count
			);
		}

		if (! isset($calendars_fields[CALENDAR_CALENDARS_FIELD_PREFIX.'tz_offset']))
		{
			$fields[] = array(
				'site_id'				=>	$this->data->get_site_id(),
				'group_id'				=>	$calendars_field_group,
				'field_name'			=>	CALENDAR_CALENDARS_FIELD_PREFIX.'tz_offset',
				'field_label'			=>	lang('timezone'),
				'field_instructions'	=>	'',
				'field_type'			=>	'calendar',
				'field_list_items'		=>	'',
				'field_pre_populate'	=>	'n',
				'field_pre_blog_id'		=>	'0',
				'field_pre_field_id'	=>	'0',
				'field_ta_rows'			=>	'0',
				'field_maxl'			=>	'100',
				'field_required'		=>	'n',
				'field_text_direction'	=>	'ltr',
				'field_search'			=>	'n',
				'field_is_hidden'		=>	'n',
				'field_fmt'				=>	'none',
				'field_show_fmt'		=>	'n',
				'field_order'			=>	++$order_count
			);
		}

		if ( ! isset($calendars_fields[CALENDAR_CALENDARS_FIELD_PREFIX.'time_format']))
		{
			$fields[] = array(
				'site_id'				=>	$this->data->get_site_id(),
				'group_id'				=>	$calendars_field_group,
				'field_name'			=>	CALENDAR_CALENDARS_FIELD_PREFIX.'time_format',
				'field_label'			=>	lang('time_format_label'),
				'field_instructions'	=>	lang('time_format_desc'),
				'field_type'			=>	'text',
				'field_list_items'		=>	'',
				'field_pre_populate'	=>	'n',
				'field_pre_blog_id'		=>	'0',
				'field_pre_field_id'	=>	'0',
				'field_ta_rows'			=>	'0',
				'field_maxl'			=>	'20',
				'field_required'		=>	'n',
				'field_text_direction'	=>	'ltr',
				'field_search'			=>	'n',
				'field_is_hidden'		=>	'y',
				'field_fmt'				=>	'none',
				'field_show_fmt'		=>	'n',
				'field_order'			=>	++$order_count
			);
		}

		if ( ! isset($calendars_fields[CALENDAR_CALENDARS_FIELD_PREFIX.'ics_url']))
		{
			$fields[] = array(
				'site_id'				=>	$this->data->get_site_id(),
				'group_id'				=>	$calendars_field_group,
				'field_name'			=>	CALENDAR_CALENDARS_FIELD_PREFIX.'ics_url',
				'field_label'			=>	lang('ics_url_label'),
				'field_instructions'	=>	lang('ics_url_desc'),
				'field_type'			=>	'textarea',
				'field_list_items'		=>	'',
				'field_pre_populate'	=>	'n',
				'field_pre_blog_id'		=>	'0',
				'field_pre_field_id'	=>	'0',
				'field_ta_rows'			=>	'3',
				'field_maxl'			=>	'20',
				'field_required'		=>	'n',
				'field_text_direction'	=>	'ltr',
				'field_search'			=>	'n',
				'field_is_hidden'		=>	'y',
				'field_fmt'				=>	'none',
				'field_show_fmt'		=>	'n',
				'field_order'			=>	++$order_count
			);
		}


		foreach ($fields as $field)
		{
			// -------------------------------------
			//	legacy relation stuff
			// -------------------------------------

			if (version_compare($this->ee_version, '2.6.0', '<'))
			{
				$field = array_merge($field, array(
					'field_related_to'		=>	'channel',
					'field_related_id'		=>	'0',
					'field_related_orderby'	=>	'date',
					'field_related_sort'	=>	'desc',
					'field_related_max'		=>	'0',
				));
			}

			$_POST = $field;

			$this->update_channel_fields();
		}

		// -------------------------------------
		//  Create the events field group
		// -------------------------------------

		if ($events_field_group == '')
		{
			$data = array(
				'site_id'		=>	$this->data->get_site_id(),
				'group_name'	=>	CALENDAR_EVENTS_FIELD_GROUP
			);

			ee()->db->insert('exp_field_groups', $data);
			$events_field_group = ee()->db->insert_id();
		}

		// -------------------------------------
		// Create the events fields
		// -------------------------------------

		$fields = array();

		$order_count = 0;

		if ( ! isset($events_fields[CALENDAR_EVENTS_FIELD_PREFIX . 'dates_and_options']))
		{
			$fields[] = array(
				'site_id'				=>	$this->data->get_site_id(),
				'group_id'				=>	$events_field_group,
				'field_name'			=>	CALENDAR_EVENTS_FIELD_PREFIX . 'dates_and_options',
				'field_label'			=>	lang('dates_and_options'),
				'field_instructions'	=>	'',
				'field_type'			=>	'calendar',
				'field_list_items'		=>	'',
				'field_pre_populate'	=>	'n',
				'field_pre_blog_id'		=>	'0',
				'field_pre_field_id'	=>	'0',
				'field_ta_rows'			=>	'0',
				'field_maxl'			=>	'0',
				'field_required'		=>	'n',
				'field_text_direction'	=>	'ltr',
				'field_search'			=>	'n',
				'field_is_hidden'		=>	'n',
				'field_fmt'				=>	'none',
				'field_show_fmt'		=>	'n',
				'field_order'			=>	++$order_count
			);
		}

		if (! isset($events_fields[CALENDAR_EVENTS_FIELD_PREFIX.'summary']))
		{
			$fields[] = array(
				'site_id'				=>	$this->data->get_site_id(),
				'group_id'				=>	$events_field_group,
				'field_name'			=>	CALENDAR_EVENTS_FIELD_PREFIX.'summary',
				'field_label'			=>	lang('summary'),
				'field_instructions'	=>	'',
				'field_type'			=>	'textarea',
				'field_list_items'		=>	'',
				'field_pre_populate'	=>	'n',
				'field_pre_blog_id'		=>	'0',
				'field_pre_field_id'	=>	'0',
				'field_ta_rows'			=>	'4',
				'field_maxl'			=>	'0',
				'field_required'		=>	'n',
				'field_text_direction'	=>	'ltr',
				'field_search'			=>	'y',
				'field_is_hidden'		=>	'n',
				'field_fmt'				=>	'xhtml',
				'field_show_fmt'		=>	'y',
				'field_order'			=>	++$order_count
			);
		}

		if (! isset($events_fields[CALENDAR_EVENTS_FIELD_PREFIX.'location']))
		{
			$fields[] = array(
				'site_id'				=>	$this->data->get_site_id(),
				'group_id'				=>	$events_field_group,
				'field_name'			=>	CALENDAR_EVENTS_FIELD_PREFIX.'location',
				'field_label'			=>	lang('location'),
				'field_instructions'	=>	'',
				'field_type'			=>	'text',
				'field_list_items'		=>	'',
				'field_pre_populate'	=>	'n',
				'field_pre_blog_id'		=>	'0',
				'field_pre_field_id'	=>	'0',
				'field_ta_rows'			=>	'0',
				'field_maxl'			=>	'200',
				'field_required'		=>	'n',
				'field_text_direction'	=>	'ltr',
				'field_search'			=>	'y',
				'field_is_hidden'		=>	'n',
				'field_fmt'				=>	'none',
				'field_show_fmt'		=>	'n',
				'field_order'			=>	++$order_count
			);
		}

		foreach ($fields as $field)
		{
			// -------------------------------------
			//	legacy relation stuff
			// -------------------------------------

			if (version_compare($this->ee_version, '2.6.0', '<'))
			{
				$field = array_merge($field, array(
					'field_related_to'		=>	'channel',
					'field_related_id'		=>	'0',
					'field_related_orderby'	=>	'date',
					'field_related_sort'	=>	'desc',
					'field_related_max'		=>	'0',
				));
			}

			$_POST = $field;

			$this->update_channel_fields();
		}

		// -------------------------------------
		//	Find an avaialble status group
		// -------------------------------------

		$status_q = ee()->db->query(
			"SELECT MIN(group_id) as group_id
			 FROM exp_status_groups"
		);

		$status_group = 0;

		if ($status_q->num_rows() > 0)
		{
			$status_group = $status_q->row('group_id');
		}

		// -------------------------------------
		//  Create the calendars weblog
		// -------------------------------------
		if (empty($calendars_weblogs))
		{
			$_POST = array(
				'edit_group_prefs'					=> 'y',
				'status_group'						=> $status_group,
				'field_group'						=> $calendars_field_group,
				'create_templates'					=> 'no',
				'template_theme'					=> 'default',
				'old_group_id'						=> '',
				'group_name'						=> '',
				//have to have this or liveUrlTitle (js) barfs on publish area
				'default_entry_title'				=> ''
			);

			$_POST['duplicate_channel_prefs']	= '';
			$_POST['channel_title']				= CALENDAR_CALENDARS_CHANNEL_TITLE;
			$_POST['channel_name']				= CALENDAR_CALENDARS_CHANNEL_NAME_DEFAULT;

			// If there are no channels, EE pitches a fit. Kill the error, Part 1.
			$errors = array();

			if (isset(ee()->api_channel_structure->errors) AND
				! empty(ee()->api_channel_structure->errors))
			{
				$errors = ee()->api_channel_structure->errors;
				ee()->api_channel_structure->errors = array();
			}

			ee()->api_channel_structure->create_channel($_POST);

			// Part 2 of the fix
			ee()->api_channel_structure->errors = $errors;
		}

		// -------------------------------------
		//  Create the events weblog
		// -------------------------------------

		if (empty($events_weblogs))
		{
			$_POST = array(
				'edit_group_prefs'			=> 'y',
				'status_group'				=> $status_group,
				'field_group'				=> $events_field_group,
				'create_templates'			=> 'no',
				'template_theme'			=> 'default',
				'old_group_id'				=> '',
				'group_name'				=> '',
				//have to have this or liveUrlTitle (js) barfs on publish area
				'default_entry_title'		=> ''
			);

			$_POST['duplicate_channel_prefs']	= '';
			$_POST['channel_title']				= CALENDAR_EVENTS_CHANNEL_TITLE;
			$_POST['channel_name']				= CALENDAR_EVENTS_CHANNEL_NAME_DEFAULT;

			// If there are no channels, EE pitches a fit. Kill the error, Part 1.
			$errors = array();
			if (isset(ee()->api_channel_structure->errors) AND
				! empty(ee()->api_channel_structure->errors))
			{
				$errors = ee()->api_channel_structure->errors;
				ee()->api_channel_structure->errors = array();
			}

			ee()->api_channel_structure->create_channel($_POST);

			// Part 2 of the fix
			ee()->api_channel_structure->errors = $errors;
		}

		// -------------------------------------
		//  Preferences
		// -------------------------------------

		$query = ee()->db->query(
			"SELECT {$this->sc->db->channel_id},
					{$this->sc->db->channel_name} AS blog_name
			 FROM 	{$this->sc->db->channels}
			 WHERE 	{$this->sc->db->channel_name}
			 IN 	('" .
			 			CALENDAR_CALENDARS_CHANNEL_NAME_DEFAULT . "', '" .
			 			CALENDAR_EVENTS_CHANNEL_NAME_DEFAULT .
			 		"')"
		);

		$channels = array();

		foreach ($query->result_array() as $row)
		{
			$which 		= (
				$row['blog_name'] == CALENDAR_CALENDARS_CHANNEL_NAME_DEFAULT
			) ? 'calendar' : 'event';

			$channels[] = $row;
		}

		$sql = ee()->db->insert_string(
			'exp_calendar_preferences',
			array(
				'site_id' 		=> $this->data->get_site_id(),
				'preferences' 	=> $this->_default_preferences($channels)
			)
		);

		ee()->db->query( $sql );

		// -------------------------------------
		//  Retore $_POST
		// -------------------------------------

		$_POST = $old_POST;

		return TRUE;
	}
	// END install()


	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */

	public function uninstall()
	{
		// Cannot uninstall what does not exist, right?
		if ($this->database_version() === FALSE)
		{
			return FALSE;
		}

		// --------------------------------------------
		//  Default Module Uninstall
		// --------------------------------------------

		if ($this->default_module_uninstall() == FALSE)
		{
			return FALSE;
		}

		return TRUE;
	}
	// END uninstall


	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */

	public function update($current = '')
	{
		if ($current == $this->version AND $this->extensions_enabled())
		{
			return FALSE;
		}

		// --------------------------------------------
		//  Default Module Update
		// --------------------------------------------

		$this->default_module_update();

		// --------------------------------------------
		//  Do DB work
		// --------------------------------------------

		if ( ! file_exists($this->addon_path.strtolower($this->lower_name).'.sql'))
		{
			return FALSE;
		}

		$sql = preg_split("/;;\s*(\n+|$)/",
			file_get_contents($this->addon_path.strtolower($this->lower_name).'.sql'),
			-1,
			PREG_SPLIT_NO_EMPTY
		);

		if (count($sql) == 0)
		{
			return FALSE;
		}

		foreach($sql as $i => $query)
		{
			$sql[$i] = trim($query);
		}

		// --------------------------------------------
		//  Database Change
		//  - Added: 1.0.0.a2
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '1.0.0.a2'))
		{
			$sql[] = '	ALTER TABLE '. CALENDAR_TABLE_CALENDARS .'
						ADD COLUMN `tz_offset` CHAR(5) NOT NULL DEFAULT "+0000",
						ADD COLUMN `timezone` VARCHAR(100) NOT NULL DEFAULT "Europe/London",
						ADD COLUMN `time_format` VARCHAR(10) NOT NULL DEFAULT "H:i a"
						';
		}

		// --------------------------------------------
		//  Database Change
		//  - Added: 1.0.0.b2
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '1.0.0.b2'))
		{
			// -------------------------------------
			//  Add a new field
			// -------------------------------------

			$query = ee()->db->query(
				'SELECT	group_id
				 FROM	exp_field_groups
				 WHERE	group_name = "'.CALENDAR_CALENDARS_FIELD_GROUP .'"'
			);

			$calendars_field_group = $query->row['group_id'];

			$fields[] = array(
				//'field_id'				=>	'',
				'site_id'				=>	$this->data->get_site_id(),
				'group_id'				=>	$calendars_field_group,
				'field_name'			=>	CALENDAR_CALENDARS_FIELD_PREFIX.'ics_url',
				'field_label'			=>	'.ics URL',
				'field_instructions'	=>	'Add one or more URLs to .ics files - separated by newlines - to import to this calendar.',
				'field_type'			=>	'textarea',
				'field_list_items'		=>	'',
				'field_pre_populate'	=>	'n',
				'field_pre_blog_id'		=>	'0',
				'field_pre_field_id'	=>	'0',
				'field_ta_rows'			=>	'3',
				'field_maxl'			=>	'20',
				'field_required'		=>	'n',
				'field_text_direction'	=>	'ltr',
				'field_search'			=>	'n',
				'field_is_hidden'		=>	'y',
				'field_fmt'				=>	'none',
				'field_show_fmt'		=>	'n',
				'field_order'			=>	''
			);

			// -------------------------------------
			//  Create new fields
			// -------------------------------------

			foreach ($fields as $field)
			{
				// -------------------------------------
				//	legacy relation stuff
				// -------------------------------------

				if (version_compare($this->ee_version, '2.6.0', '<'))
				{
					$field = array_merge($field, array(
						'field_related_to'		=>	'channel',
						'field_related_id'		=>	'0',
						'field_related_orderby'	=>	'date',
						'field_related_sort'	=>	'desc',
						'field_related_max'		=>	'0',
					));
				}

				$_POST = $field;

				$this->update_channel_fields();
			}

			// -------------------------------------
			//  Add a new column
			// -------------------------------------

			$sql[] = '	ALTER TABLE exp_calendar_calendars
						ADD COLUMN ics_url TEXT DEFAULT ""
						';

			// -------------------------------------
			//  Modify a column
			// -------------------------------------

			$sql[] = '	UPDATE exp_calendar_preferences
						SET preferences = "'. ee()->db->escape_str($this->_default_preferences()) .'"
						WHERE preferences = ""
						';
		}

		// --------------------------------------------
		//  Database Change
		//  - Added: 1.0.0.b3
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '1.0.0.b3'))
		{
			// -------------------------------------
			// Add a new column
			// -------------------------------------

			$sql[] = '	ALTER TABLE exp_calendar_calendars
						ADD COLUMN ics_updated DATETIME DEFAULT "0000-00-00"
						';

			$sql[] = '	UPDATE exp_calendar_calendars
						SET ics_updated = "0000-00-00"
						WHERE ics_updated IS NULL';
		}

		// --------------------------------------------
		//  Run module SQL - dependent on CREATE TABLE IF NOT EXISTS syntax
		// --------------------------------------------

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		// --------------------------------------------
		//  Database Change
		//  - Added: 1.0.2.d3
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '1.0.2.d3'))
		{
			$asql = '	SELECT cp.*
						FROM exp_calendar_preferences cp';

			$query = ee()->db->query($asql);

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$data	= unserialize($row['preferences']);
					if (! array_key_exists('date_format', $data))
					{
						$data['date_format']	= 'mm/dd/yy';
					}
					ee()->db->query(
						ee()->db->update_string(
							'exp_calendar_preferences',
							array('preferences' => serialize($data)),
							'site_id = '. ee()->db->escape_str($row['site_id'])
						)
					);
				}
			}
		}

		// --------------------------------------------
		//  Preferences go wacko before this
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '1.5.1'))
		{
			$prefs = $this->data->get_module_preferences(TRUE);

			if ( ! isset($prefs['calendar_weblog']) OR $prefs['calendar_weblog'] === '' )
			{
				$query = ee()->db->query(
					"SELECT {$this->sc->db->channel_id}
					 FROM 	{$this->sc->db->channels}
					 WHERE 	{$this->sc->db->channel_name} = '" .
								ee()->db->escape_str(CALENDAR_CALENDARS_CHANNEL_NAME_DEFAULT) . "'"
				);

				$prefs['calendar_weblog'] 	= ($query->num_rows() > 0) ? $query->row($this->sc->db->channel_id) : '';
			}

			if ( ! isset($prefs['event_weblog']) OR $prefs['event_weblog'] === '')
			{
				$query = ee()->db->query(
					"SELECT {$this->sc->db->channel_id}
					 FROM 	{$this->sc->db->channels}
					 WHERE 	{$this->sc->db->channel_name} = '" .
								ee()->db->escape_str(CALENDAR_EVENTS_CHANNEL_NAME_DEFAULT) . "'"
				);

				$prefs['event_weblog'] 		= ($query->num_rows() > 0) ? $query->row($this->sc->db->channel_id) : '';
			}

			$this->data->update_preferences($prefs);
		}

		//seems that we did not properly set default_entry_title and that causes all heck with
		//liveUrlTitle() in the publish area.
		if ($this->version_compare($this->database_version(), '<', '1.5.2'))
		{
			$prefs = $this->data->get_module_preferences(TRUE);

			$where = implode(',', ee()->db->escape_str(array($prefs['calendar_weblog'], $prefs['event_weblog'])));

			ee()->db->query(
				"UPDATE {$this->sc->db->channels}
				 SET 	default_entry_title = ''
				 WHERE 	{$this->sc->db->channel_id}
				 IN		($where)"
			);
		}

		//Update the tables to have a default status_group of one
		//the default for the table is 0, and everything _should_
		//work fine, but it seems like this might come to a head
		//at some point, so extra protection.
		if ($this->version_compare($this->database_version(), '<', '1.5.5'))
		{
			$prefs = $this->data->get_module_preferences(TRUE);

			$where = implode(',', ee()->db->escape_str(array($prefs['calendar_weblog'], $prefs['event_weblog'])));

			ee()->db->query(
				"UPDATE {$this->sc->db->channels}
				 SET 	status_group = 1
				 WHERE 	{$this->sc->db->channel_id}
				 IN		($where)
				 AND	( status_group IS NULL OR status_group = 0 )"
			);
		}

		if ($this->version_compare($this->database_version(), '<', '1.6.4'))
		{
			ee()->db->query(
				"UPDATE exp_extensions
				 SET 	hook = 'calendar_calendars_channel_query'
				 WHERE 	hook = 'calendar_calendars_weblog_query'"
			);
		}


		if ($this->version_compare($this->database_version(), '<', '1.7.0'))
		{
			if (ee()->db->table_exists('exp_calendar_permissions_preferences') === FALSE)
			{
				$newest_prefs = TRUE;

				$module_install_sql = file_get_contents(
					$this->addon_path . strtolower($this->lower_name) . '.sql'
				);

				//gets JUST the tag prefs table from the sql
				$prefs_table = stristr(
					$module_install_sql,
					"CREATE TABLE IF NOT EXISTS `exp_calendar_permissions_preferences`"
				);

				$prefs_table = substr($prefs_table, 0, stripos($prefs_table, ';;'));

				//install it
				ee()->db->query($prefs_table);
			}
		}

		if ($this->version_compare($this->database_version(), '<', '1.7.1.b1'))
		{
			$fqiu = ee()->db
						->where('field_name', CALENDAR_CALENDARS_FIELD_PREFIX . 'ics_url')
						->where('site_id', $this->data->get_site_id())
						->limit(1)
						->get('exp_channel_fields');

			if ($fqiu->num_rows() > 0)
			{
				ee()->db->query(
					ee()->db->update_string(
						'exp_channel_fields',
						array(
							'field_instructions' => $fqiu->row('field_instructions') . ' ' . lang('ics_url_stub')
						),
						array(
							'site_id'	=> $fqiu->row('site_id'),
							'field_id'	=> $fqiu->row('field_id')
						)
					)
				);
			}
		}

		// -------------------------------------
		//	If the calendar fieldtype isn't installed
		//	we can assume that this is EE 1.x -> 2.x
		// -------------------------------------

		$ft_query = ee()->db
						->where('name', 'calendar')
						->get('exp_fieldtypes');

		if ($ft_query->num_rows() == 0)
		{
			//install calendar fieldtype
			ee()->load->library('addons/addons_installer');
			ee()->load->model('addons_model');

			if ( ! ee()->addons_model->fieldtype_installed($this->lower_name))
			{
				ee()->addons_installer->install($this->lower_name, 'fieldtype', FALSE);
			}

			//update field_related_to
			ee()->db
				->where_in('field_name', array(
					CALENDAR_CALENDARS_FIELD_PREFIX . 'summary',
					CALENDAR_CALENDARS_FIELD_PREFIX . 'tz_offset',
					CALENDAR_CALENDARS_FIELD_PREFIX . 'time_format',
					CALENDAR_CALENDARS_FIELD_PREFIX . 'ics_url',

					CALENDAR_EVENTS_FIELD_PREFIX .'dates_and_options',
					CALENDAR_EVENTS_FIELD_PREFIX .'summary',
					CALENDAR_EVENTS_FIELD_PREFIX . 'location',
				))
				->update(
					'exp_channel_fields',
					array(
						'field_related_to' => 'channel'
					)
				);

			//update fieldtypes for
			ee()->db
				->where_in('field_name', array(
					CALENDAR_CALENDARS_FIELD_PREFIX . 'tz_offset',
					CALENDAR_EVENTS_FIELD_PREFIX .'dates_and_options',
				))
				->update(
					'exp_channel_fields',
					array(
						'field_type' => 'calendar'
					)
				);

		}


		// --------------------------------------------
		//  Version Number Update - LAST!
		// --------------------------------------------

		ee()->db->update(
			'exp_modules',
			array(
				'module_version'		=> CALENDAR_VERSION,
				'has_publish_fields'	=> 'n'
			),
			array(
				'module_name'		=> $this->class_name
			)
		);

		return TRUE;
	}
	// END update


	// --------------------------------------------------------------------

	public function _default_preferences($channels = array())
	{
		$cal_query = ee()->db->query(
			"SELECT {$this->sc->db->channel_id} AS channel_id
			 FROM 	{$this->sc->db->channels}
			 WHERE 	{$this->sc->db->channel_name} = '" .
			 	ee()->db->escape_str(CALENDAR_CALENDARS_CHANNEL_NAME_DEFAULT) . "'"
		);

		$event_query = ee()->db->query(
			"SELECT {$this->sc->db->channel_id} AS channel_id
			 FROM 	{$this->sc->db->channels}
			 WHERE 	{$this->sc->db->channel_name} = '" .
			 	ee()->db->escape_str(CALENDAR_EVENTS_CHANNEL_NAME_DEFAULT) . "'"
		);

		$array = array(
			'first_day_of_week'			=> '0',
			'clock_type'				=> '12',
			'tz_offset'					=> '+0000',
			'date_format'				=> 'mm/dd/yy',
			'time_format'				=> 'g:i a',
			'calendar_weblog'			=> (isset($channels['calendar'])) ?
												$channels['calendar_weblog']['weblog_id'] :
												$cal_query->row('channel_id'),
			'event_weblog'				=> (isset($channels['event'])) ?
											$channels['event_weblog']['weblog_id'] :
											$event_query->row('channel_id'),
			'ics_update_delete_default' => 'n'
		);

		return serialize($array);
	}
	/* END _default_preferences() */

	// --------------------------------------------------------------------

	/**
	 * Update weblog fields
	 *
	 * EE 1.x has an update_weblog_fields() method that automagically takes
	 * $_POST data and creates a new field. Set some $_POST data, run something
	 * like $PA->update_weblog_fields(), and we're done. Not so with EE 2.x.
	 * This method hides inside a controller (Admin_content::field_update()), so
	 * we can't get at it. The below is adapted from that method. Hopefully
	 * EllisLab provides a better way to do this in the future.
	 */

	public function update_channel_fields()
	{
		$field_type = $_POST['field_type'];
		$group_id = $_POST['group_id'];

		ee()->load->library('api');
		ee()->api->instantiate('channel_fields');
		ee()->load->model('field_model');

		$native_int = array(
			'field_id',
			'site_id',
			'group_id',
			'field_pre_channel_id',
			'field_pre_field_id',
			'field_ta_rows',
			'field_maxl',
			'field_order',
		);

		$native = array(
			'field_id',
			'site_id',
			'group_id',
			'field_name',
			'field_label',
			'field_instructions',
			'field_type',
			'field_list_items',
			'field_pre_populate',
			'field_pre_channel_id',
			'field_ta_rows',
			'field_maxl',
			'field_required',
			'field_text_direction',
			'field_search',
			'field_is_hidden',
			'field_fmt',
			'field_show_fmt',
			'field_order',
			'field_content_type'
		);

		// -------------------------------------
		//	legacy relation stuff
		// -------------------------------------

		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			$native_int = array_merge($native_int, array(
				'field_related_id',
				'field_related_max',
			));

			$native = array_merge($native, array(
				'field_related_to',
				'field_related_orderby',
				'field_related_sort',
			));
		}

		// Get the field type settings
		ee()->api_channel_fields->fetch_all_fieldtypes();
		ee()->api_channel_fields->setup_handler($field_type);

		$ft_settings = ee()->api_channel_fields->apply('save_settings', array($_POST));

		// Default display options
		$default_display_options = array(
			'smileys',
			'glossary',
			'spellcheck',
			'formatting_btns',
			'file_selector',
			'writemode'
		);

		foreach($default_display_options as $key)
		{
			$ft_settings['field_show_'.$key] = 'n';
		}

		// Now that they've had a chance to mess with the POST array,
		// grab post values for the native fields (and check namespaced fields)
		foreach($native as $key)
		{
			if (isset($_POST[$key]))
			{
				$native_settings[$key] = $_POST[$key];
			}
			else if ($key == 'site_id')
			{
				$native_settings[$key] = ee()->config->item('site_id');
			}
			else if (in_array($key, $native_int))
			{
				$native_settings[$key] = 0;
			}
			else
			{
				$native_settings[$key] = '';
			}
		}

		$native_settings['field_content_type']	= 'any';
		$native_settings['field_settings']		= base64_encode(serialize($ft_settings));

		// Make us a field
		if ($_POST['field_order'] == 0 OR
			$_POST['field_order'] == '')
		{
			$query = ee()->db->query(
				"SELECT	count(*) AS count
				 FROM	exp_channel_fields
				 WHERE	group_id = '" . ee()->db->escape_str($group_id) . "'"
			);

			$_POST['field_order'] = $query->row('count') + 1;
		}

		if ( ! $native_settings['field_ta_rows'])
		{
			$native_settings['field_ta_rows'] = 0;
		}

		// as its new, there will be no field id, unset
		// it to prevent an empty string from attempting to pass
		unset($native_settings['field_id']);

		ee()->db->query(ee()->db->insert_string('channel_fields', $native_settings));

		$insert_id = ee()->db->insert_id();
		$native_settings['field_id'] = $insert_id;

		switch ($native_settings['field_content_type'])
		{
			case 'numeric':
				$type = 'FLOAT DEFAULT 0';
				break;
			case 'integer':
				$type = 'INT DEFAULT 0';
				break;
			default:
				$type = 'text';
		}

		ee()->db->query(
			"ALTER TABLE exp_channel_data
			 ADD COLUMN field_id_".$insert_id.' '.$type
		);

		ee()->db->query(
			"ALTER TABLE exp_channel_data
			 ADD COLUMN field_ft_".$insert_id." tinytext NULL"
		);

		ee()->db->query(
			"UPDATE exp_channel_data
			 SET field_ft_".$insert_id." = '".ee()->db->escape_str($native_settings['field_fmt'])."'"
		);

		foreach (array('none', 'br', 'xhtml') as $val)
		{
			ee()->db->query(
				"INSERT INTO exp_field_formatting (field_id, field_fmt)
				 VALUES ('$insert_id', '$val')"
			);
		}

		$collapse = ($native_settings['field_is_hidden'] == 'y') ? 'true' : 'false';
		$buttons = ($ft_settings['field_show_formatting_btns'] == 'y') ? 'true' : 'false';

		$field_info['publish'][$insert_id] = array(
			'visible'		=> 'true',
			'collapse'		=> $collapse,
			'htmlbuttons'	=> $buttons,
			'width'			=> '100%'
		);

		$query = ee()->field_model->get_assigned_channels($group_id);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$channel_ids[] = $row->channel_id;
			}

			ee()->load->library('layout');
			ee()->layout->add_layout_fields($field_info, $channel_ids);
		}

		$_final_settings = array_merge($native_settings, $ft_settings);
		unset($_final_settings['field_settings']);

		ee()->api_channel_fields->set_settings($native_settings['field_id'], $_final_settings);
		ee()->api_channel_fields->setup_handler($native_settings['field_id']);
		ee()->api_channel_fields->apply('post_save_settings', array($_POST));

		ee()->functions->clear_caching('all', '', TRUE);
	}
	// END update_channel_fields
}
// END Class