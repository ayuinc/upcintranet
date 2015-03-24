<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ChannelFilesUpdate_520
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
		// -----------------------------------------
		// Add sizes_metadata Column
		// -----------------------------------------
		if ($this->EE->db->field_exists('is_draft', 'channel_files') == FALSE)
		{
			$fields = array( 'is_draft'	=> array('type' => 'TINYINT',		'unsigned' => TRUE, 'default' => 0) );
			$this->EE->dbforge->add_column('channel_files', $fields, 'member_id');
		}

		//exit();
	}

	// ********************************************************************************* //

}

/* End of file 400.php */
/* Location: ./system/expressionengine/third_party/channel_files/updates/400.php */