<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ChannelFilesUpdate_320
{

	/**
	 * Constructor
	 *
	 * @access public
	 *
	 * Calls the parent constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		// Load dbforge
		$this->EE->load->dbforge();
	}

	// ********************************************************************************* //

	public function do_update()
	{
		// Add the link_field_id Column
    	if ($this->EE->db->field_exists('date', 'channel_files') == FALSE)
		{
			$fields = array( 'date'	=> array('type' => 'INT',	'unsigned' => TRUE, 'default' => 0) );
			$this->EE->dbforge->add_column('channel_files', $fields, 'file_order');
		}


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
	}

	// ********************************************************************************* //

}

/* End of file 310.php */
/* Location: ./system/expressionengine/third_party/channel_files/updates/320.php */