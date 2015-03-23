<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include PATH_THIRD.'channel_files/config'.EXT;

/**
 * Install / Uninstall and updates the modules
 *
 * @package			DevDemon_ChannelFiles
 * @version			3.6
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com
 * @see				http://expressionengine.com/user_guide/development/module_tutorial.html#update_file
 */
class Channel_files_upd
{
	/**
	 * Module version
	 *
	 * @var string
	 * @access public
	 */
	public $version		=	CHANNEL_FILES_VERSION;

	/**
	 * Module Short Name
	 *
	 * @var string
	 * @access private
	 */
	public $module_name	=	CHANNEL_FILES_CLASS_NAME;

	/**
	 * Has Control Panel Backend?
	 *
	 * @var string
	 * @access private
	 */
	public $has_cp_backend = 'y';

	/**
	 * Has Publish Fields?
	 *
	 * @var string
	 * @access private
	 */
	public $has_publish_fields = 'n';


	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->add_package_path(PATH_THIRD . 'channel_files/');
		$this->EE->config->load('cf_config');
	}

	// ********************************************************************************* //

	/**
	 * Installs the module
	 *
	 * Installs the module, adding a record to the exp_modules table,
	 * creates and populates and necessary database tables,
	 * adds any necessary records to the exp_actions table,
	 * and if custom tabs are to be used, adds those fields to any saved publish layouts
	 *
	 * @access public
	 * @return boolean
	 **/
	public function install()
	{
		// Load dbforge
		$this->EE->load->dbforge();

		//----------------------------------------
		// EXP_MODULES
		//----------------------------------------
		$module = array(	'module_name' => ucfirst($this->module_name),
							'module_version' => $this->version,
							'has_cp_backend' => $this->has_cp_backend,
							'has_publish_fields' => $this->has_publish_fields );

		$this->EE->db->insert('modules', $module);

		//----------------------------------------
		// EXP_CHANNEL_FILES
		//----------------------------------------
		$ci = array(
			'file_id' 		=> array('type' => 'INT',		'unsigned' => TRUE,	'auto_increment' => TRUE),
			'site_id'		=> array('type' => 'TINYINT',	'unsigned' => TRUE,	'default' => 1),
			'entry_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'field_id'		=> array('type' => 'MEDIUMINT',	'unsigned' => TRUE, 'default' => 0),
			'channel_id'	=> array('type' => 'TINYINT',	'unsigned' => TRUE, 'default' => 0),
			'member_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'is_draft'		=> array('type' => 'TINYINT',	'unsigned' => TRUE, 'default' => 0),
			'link_file_id'	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'link_entry_id'	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'link_channel_id'=> array('type' => 'TINYINT',		'unsigned' => TRUE, 'default' => 0),
			'link_field_id'	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'filename'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'extension'		=> array('type' => 'VARCHAR',	'constraint' => '20', 'default' => ''),
			'mime'			=> array('type' => 'VARCHAR',	'constraint' => '100', 'default' => ''),
			'upload_service'=> array('type' => 'VARCHAR',	'constraint' => '50', 'default' => ''),
			'title'			=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'url_title'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'description'	=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'category'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'cffield_1'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'cffield_2'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'cffield_3'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'cffield_4'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'cffield_5'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'filesize'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'md5'			=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'file_primary'	=> array('type' => 'TINYINT',	'constraint' => '1', 'unsigned' => TRUE, 'default' => 0),
			'file_order'	=> array('type' => 'SMALLINT',	'unsigned' => TRUE, 'default' => 1),
			'date'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'downloads'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
		);

		$this->EE->dbforge->add_field($ci);
		$this->EE->dbforge->add_key('file_id', TRUE);
		$this->EE->dbforge->add_key('entry_id');
		$this->EE->dbforge->add_key('field_id');
		$this->EE->dbforge->create_table('channel_files', TRUE);


		//----------------------------------------
		// EXP_CHANNEL_FILES_DOWNLOAD_LOG
		//----------------------------------------
		$columns = array(
			'log_id' 	=> array('type' => 'INT',	'unsigned' => TRUE,	'auto_increment' => TRUE),
			'site_id'	=> array('type' => 'TINYINT',	'unsigned' => TRUE, 'default' => 1),
			'file_id'	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'entry_id'	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'member_id'	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'ip_address'=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'date'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
		);

		$this->EE->dbforge->add_field($columns);
		$this->EE->dbforge->add_key('log_id', TRUE);
		$this->EE->dbforge->add_key('file_id');
		$this->EE->dbforge->add_key('member_id');
		$this->EE->dbforge->add_key('entry_id');
		$this->EE->dbforge->create_table('channel_files_download_log', TRUE);

		//----------------------------------------
		// EXP_ACTIONS
		//----------------------------------------
		$module = array('class' => ucfirst($this->module_name), 'method' => $this->module_name . '_router' );
		$this->EE->db->insert('actions', $module);
		$module = array('class' => ucfirst($this->module_name), 'method' => 'upload_file' );
		$this->EE->db->insert('actions', $module);
		$module = array('class' => ucfirst($this->module_name), 'method' => 'simple_file_url' );
		$this->EE->db->insert('actions', $module);
		$module = array('class' => ucfirst($this->module_name), 'method' => 'locked_file_url' );
		$this->EE->db->insert('actions', $module);


		//----------------------------------------
		// EXP_MODULES
		// The settings column, Ellislab should have put this one in long ago.
		// No need for a seperate preferences table for each module.
		//----------------------------------------
		if ($this->EE->db->field_exists('settings', 'modules') == FALSE)
		{
			$this->EE->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
		}

		// Do we need to enable the extension
        //if ($this->uses_extension === TRUE) $this->extension_handler('enable');

		return TRUE;
	}

	// ********************************************************************************* //

	/**
	 * Uninstalls the module
	 *
	 * @access public
	 * @return Boolean FALSE if uninstall failed, TRUE if it was successful
	 **/
	function uninstall()
	{
		// Load dbforge
		$this->EE->load->dbforge();

		// Remove
		$this->EE->dbforge->drop_table('channel_files');
		$this->EE->dbforge->drop_table('channel_files_download_log');

		$this->EE->db->where('module_name', ucfirst($this->module_name));
		$this->EE->db->delete('modules');
		$this->EE->db->where('class', ucfirst($this->module_name));
		$this->EE->db->delete('actions');

		// $this->EE->cp->delete_layout_tabs($this->tabs(), 'points');

		return TRUE;
	}

	// ********************************************************************************* //

	/**
	 * Updates the module
	 *
	 * This function is checked on any visit to the module's control panel,
	 * and compares the current version number in the file to
	 * the recorded version in the database.
	 * This allows you to easily make database or
	 * other changes as new versions of the module come out.
	 *
	 * @access public
	 * @return Boolean FALSE if no update is necessary, TRUE if it is.
	 **/
	public function update($current = '')
	{
		if ($this->EE->db->field_exists('csrf_exempt', 'exp_actions') === true) {
			$this->EE->db->set('csrf_exempt', 1);
			$this->EE->db->where('class', ucfirst($this->module_name));
			$this->EE->db->update('exp_actions');
		}

		// Are they the same?
		if (version_compare($current, $this->version) >= 0) {
			return FALSE;
		}

		$current = str_replace('.', '', $current);

		// Two Digits? (needs to be 3)
		if (strlen($current) == 2) $current .= '0';

		$update_dir = PATH_THIRD.strtolower($this->module_name).'/updates/';

		// Does our folder exist?
		if (@is_dir($update_dir) === TRUE)
		{
			// Loop over all files
			$files = @scandir($update_dir);

			if (is_array($files) == TRUE)
			{
				foreach ($files as $file)
				{
					if ($file == '.' OR $file == '..' OR strtolower($file) == '.ds_store') continue;

					// Get the version number
					$ver = substr($file, 0, -4);

					// We only want greater ones
					if ($current >= $ver) continue;

					require $update_dir . $file;
					$class = 'ChannelFilesUpdate_' . $ver;
					$UPD = new $class();
					$UPD->do_update();
				}
			}
		}

		// Upgrade The Module
		$this->EE->db->set('module_version', $this->version);
		$this->EE->db->where('module_name', ucfirst($this->module_name));
		$this->EE->db->update('exp_modules');

		return TRUE;
	}

} // END CLASS

/* End of file upd.channel_files.php */
/* Location: ./system/expressionengine/third_party/channel_files/upd.channel_files.php */