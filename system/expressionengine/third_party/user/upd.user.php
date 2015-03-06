<?php if ( ! defined('BASEPATH') ) exit('No direct script access allowed');

/**
 * User - Install/Uninstall/Update class
 *
 * @package		Solspace:User
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/user
 * @license		http://www.solspace.com/license_agreement
 * @version		3.5.0
 * @filesource	user/upd.user.php
 */

require_once 'addon_builder/module_builder.php';

class User_upd extends Module_builder_user
{
	public 	$module_actions		= array('process_reset_password');
	public 	$hooks				= array();
	private $old_layout_data	= array(
		array(
			'user_authors' 	=> array(
				'solspace_user_browse_authors' 		=> array(
					'visible'		=> 'true',
					'collapse'		=> 'false',
					'htmlbuttons'	=> 'false',
					'width'			=> '100%'
				)
			)
		),
		array(
			'user' 			=> array(
				'user__solspace_user_browse_authors' => array(
					'visible'		=> 'true',
					'collapse'		=> 'false',
					'htmlbuttons'	=> 'false',
					'width'			=> '100%'
				)
			)
		)
	);

	// --------------------------------------------------------------------

	/**
	 * Contructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------
		//  Module Actions
		// --------------------------------------------

		$this->module_actions = array(
			'group_edit',
			'edit_profile',
			'reg',
			'reassign_jump',
			'retrieve_password',
			'do_logout',
			'do_search',
			'delete_account',
			'activate_member',
			'retrieve_username',
			'create_key',
			'process_reset_password'
		);

		// --------------------------------------------
		//  Extension Hooks
		// --------------------------------------------

		$default = array(
			'class' 		=> $this->extension_name,
			'settings'		=> '',
			'priority'		=> 5,
			'version'		=> constant(strtoupper($this->class_name).'_VERSION'),
			'enabled'		=> 'y'
		);

		$this->hooks = array(
			array_merge($default,
				array(
					'method'		=> 'insert_comment_start',
					'hook'  		=> 'insert_comment_start'
				)
			),
			array_merge($default,
				array(
					'method'		=> 'insert_rating_start',
					'hook'			=> 'insert_rating_start'
				)
			),
			array_merge($default,
				array(
					'method'		=> 'paypalpro_payment_start',
					'hook'			=> 'paypalpro_payment_start'
				)
			),
			//array_merge($default,
			//	array(
			//		'method'		=> 'freeform_module_insert_begin',
			//		'hook'			=> 'freeform_module_insert_begin'
			//	)
			//),
			array_merge($default,
				array(
					'method'		=> 'cp_members_validate_members',
					'hook'			=> 'cp_members_validate_members',
					'priority'		=> 1
				)
			),
			array_merge($default,
				array(
					'method'		=> 'delete_entries_start',
					'hook'			=> 'delete_entries_start'
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
		// Already installed, let's not install again.
		if ($this->database_version() !== FALSE)
		{
			return FALSE;
		}

		// --------------------------------------------
		//  Our Default Install
		// --------------------------------------------

		if ($this->default_module_install() == FALSE)
		{
			return FALSE;
		}

		// --------------------------------------------
		//  Add Profile Views Field to exp_members
		// --------------------------------------------

		if ( ! $this->column_exists('profile_views', 'exp_members'))
		{
			ee()->db->query(
				"ALTER TABLE exp_members
				 ADD (profile_views int(10) unsigned  NOT NULL DEFAULT '0')"
			);
		}

		// --------------------------------------------
		//  Default Preferences
		// --------------------------------------------

		$forgot_username = <<<EOF
{screen_name},

Per your request, we have emailed you your username for {site_name} located at {site_url}.

Username: {username}
EOF;

		$prefs = array(
			'email_is_username' 						=> 'n',
			'screen_name_override'						=> '',
			'category_groups'							=> '',
			'welcome_email_subject'						=> lang('welcome_email_content'),
			'welcome_email_content'						=> '',
			'user_forgot_username_message'				=> $forgot_username,
			'member_update_admin_notification_template'	=> '',
			'member_update_admin_notification_emails'	=> '',
			'key_expiration'							=> 7
		);

		foreach($prefs as $pref => $default)
		{
			ee()->db->insert(
				'exp_user_preferences',
				array(
					'preference_name'	=> $pref,
					'preference_value'	=> $default
				)
			);
		}

		// --------------------------------------------
		//  Module Install
		// --------------------------------------------

		ee()->db->insert(
			'exp_modules',
			array(
				'module_name'			=> $this->class_name,
				'module_version'		=> constant(strtoupper($this->class_name).'_VERSION'),
				'has_cp_backend'		=> 'y',
				'has_publish_fields'	=> 'y'
			)
		);

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

		//--------------------------------------------
		//	remove tabs
		//--------------------------------------------

		$this->remove_user_tabs();

		//--------------------------------------------
		//	Drop Profile Views Field from exp_members
		//--------------------------------------------

		ee()->db->query("ALTER TABLE `exp_members` DROP `profile_views`");

		//--------------------------------------------
		//	Default Module Uninstall
		//--------------------------------------------

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

	public function update($current = "")
	{
		if ($current == $this->version)
		{
			return FALSE;
		}

		// --------------------------------------------
		//  User 2.0.2 Upgrade
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '2.0.2'))
		{
			ee()->db->query("ALTER TABLE `exp_user_keys` ADD INDEX (`author_id`)");
			ee()->db->query("ALTER TABLE `exp_user_keys` ADD INDEX (`member_id`)");
			ee()->db->query("ALTER TABLE `exp_user_keys` ADD INDEX (`group_id`)");
			ee()->db->query("ALTER TABLE `exp_user_params` ADD INDEX (`hash`)");
		}

		// --------------------------------------------
		// Hermes Conversion
		//  - Added: 3.0.0.d1
		//  - Perform prior to Hermes default Update
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '3.0.0.d1'))
		{
			ee()->db->update(
				'exp_extensions',
				array('class' => 'User_extension'),
				array('class' => 'User_ext')
			);
		}

		// --------------------------------------------
		//  Move Preferences Out of Config.php
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '3.0.0.d25'))
		{
			$prefs = array(
				'user_email_is_username'		=> 'email_is_username',
				'user_screen_name_override'		=> 'screen_name_override',
				'user_category_group'			=> 'category_groups',
				'user_module_key_expiration'	=> 'key_expiration'
			);

			foreach($prefs as $pref => $new_pref)
			{
				if (ee()->config->item($pref) !== FALSE)
				{
					$query = ee()->db
								->select('preference_value')
								->where('preference_name', $new_pref)
								->where("preference_value != ''")
								->limit(1)
								->get('user_preferences');

					if ($query->num_rows() == 0)
					{
						ee()->db->insert(
							'exp_user_preferences',
							array(
								'preference_name'	=> $new_pref,
								'preference_value'	=> ee()->config->item($pref)
							)
						);
					}

					//in case this ever goes private, as it's like to do
					if (is_callable(array(ee()->config, '_update_config')))
					{
						ee()->config->_update_config(array(), array($pref));
					}
				}
			}
		}

		// --------------------------------------------
		//  Welcome Email Subject - 3.0.2.d2
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '3.0.2.d2'))
		{
			ee()->db->insert(
				'exp_user_preferences',
				array(
					'preference_name'	=> 'welcome_email_subject',
					'preference_value'	=> lang('welcome_email_content')
				)
			);
		}

		// --------------------------------------------
		//  Key Expiration - 3.1.0.d2
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '3.1.0.d2'))
		{
			ee()->db->insert(
				'exp_user_preferences',
				array(
					'preference_name'	=> 'key_expiration',
					'preference_value'	=> 7
				)
			);
		}

		//remove old tab style from everything
		if ($this->version_compare($this->database_version(), '<', '3.3.2'))
		{
			ee()->load->library('layout');
			//remove original layout tabs
			$this->remove_user_tabs();

			ee()->load->model('user_model');

			//check and see if we need to install the newest tabs
			//we want a non-cached set
			$tab_channel_ids = ee()->user_model->get_channel_ids(FALSE);

			//if we already have tabs named, we need to reinstall them
			if ($tab_channel_ids !== FALSE AND
				is_array($tab_channel_ids) AND
				! empty($tab_channel_ids))
			{
				ee()->layout->add_layout_tabs(
					$this->tabs(),
					'',
					array_keys($tab_channel_ids)
				);
			}
		}

		// --------------------------------------------
		//  Default Module Update
		// --------------------------------------------

		$this->default_module_update();

		// --------------------------------------------
		//  Version Number Update - LAST!
		// --------------------------------------------

		$data = array(
			'module_version'		=> constant(strtoupper($this->class_name) . '_VERSION'),
			'has_publish_fields'	=> 'y'
		);

		ee()->db->update(
			'exp_modules',
			$data,
			array(
				'module_name'	=> $this->class_name
			)
		);

		return TRUE;
	}
	// END update


	// --------------------------------------------------------------------

	/**
	 *	remove all tabs, old and new, from layouts
	 *
	 *	@access		public
	 *	@return		null
	 */
	public function remove_user_tabs()
	{
		ee()->load->library('layout');

		ee()->layout->delete_layout_tabs(
			array_merge_recursive($this->old_layout_data, $this->tabs())
		);

		ee()->layout->delete_layout_fields(
			array_merge_recursive($this->old_layout_data, $this->tabs())
		);
	}
	//END remove_user_tabs()


	// --------------------------------------------------------------------

	/**
	 *	tabs
	 *
	 *	returns tab for user. we replace the name choice with JS later
	 *
	 *
	 *	@access		public
	 *	@return		array
	 */

	public function tabs()
	{
		return array(
			'user' => array(
				'user__solspace_user_browse_authors' => array(
					'visible'		=> 'true',
					'collapse'		=> 'false',
					'htmlbuttons'	=> 'false',
					'width'			=> '100%'
				),
				'user__solspace_user_primary_author' => array(
					'visible'		=> 'true',
					'collapse'		=> 'false',
					'htmlbuttons'	=> 'false',
					'width'			=> '100%'
				)
			)
		);
	}
	// END tabs()

}
// END Class