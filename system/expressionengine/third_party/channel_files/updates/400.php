<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ChannelFilesUpdate_400
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
		// Grab all channel images fields!
		// -----------------------------------------
		$query = $this->EE->db->query("SELECT field_id, field_settings FROM exp_channel_fields WHERE field_type = 'channel_files'");

		// -----------------------------------------
		// Loop over all fields
		// -----------------------------------------
		foreach ($query->result() as $field)
		{
			// New Settings Array
			$settings = array();

			// Parse Old Settings
			$oldsettings = unserialize(base64_decode($field->field_settings));

			// Simple check
			if (isset($oldsettings['channel_files']) == TRUE) continue;

			// -----------------------------------------
			// Simple Settings
			// -----------------------------------------
			$settings = $this->EE->config->item('cf_defaults');

			// Categories
			if (isset($oldsettings['cf_categories'])) $settings['categories'] = $oldsettings['cf_categories'];
			unset($oldsettings['cf_categories']);

			// File Extensions
			if (isset($oldsettings['cf_file_extensions'])) $settings['file_extensions'] = $oldsettings['cf_file_extensions'];
			unset($oldsettings['cf_file_extensions']);

			// -----------------------------------------
			// Locations :O
			// -----------------------------------------
			$settings['upload_location'] = $oldsettings['cf_locations']['type'];
			unset($oldsettings['cf_locations']['type']);

			// Local
			if (isset($oldsettings['cf_locations']['local']['location_id'])) $settings['locations']['local']['location'] = $oldsettings['cf_locations']['local']['location_id'];
			unset($oldsettings['cf_locations']['local']);

			// S3
			if (isset($oldsettings['cf_locations']['s3'])) $settings['locations']['s3'] = $oldsettings['cf_locations']['s3'];
			unset($oldsettings['cf_locations']['s3']);

			// Cloudfiles
			if (isset($oldsettings['cf_locations']['cloudfiles'])) $settings['locations']['cloudfiles'] = $oldsettings['cf_locations']['cloudfiles'];
			unset($oldsettings['cf_locations']['cloudfiles']);

			unset($oldsettings['cf_locations']);

			// -----------------------------------------
			// Put It Back
			// -----------------------------------------
			$oldsettings['channel_files'] = $settings;
			$oldsettings = base64_encode(serialize($oldsettings));

			$this->EE->db->set('field_settings', $oldsettings);
			$this->EE->db->where('field_id', $field->field_id);
			$this->EE->db->update('exp_channel_fields');

		}

		// -----------------------------------------
		// Add Member ID Column
		// -----------------------------------------
		if ($this->EE->db->field_exists('member_id', 'channel_files') == FALSE)
		{
			$fields = array( 'member_id'	=> array('type' => 'INT',	'unsigned' => TRUE, 'default' => 0) );
			$this->EE->dbforge->add_column('channel_files', $fields, 'channel_id');
		}

		// Grab all files!
		$query = $this->EE->db->select('cf.file_id, ct.author_id')->from('exp_channel_files cf')->join('exp_channel_titles ct', 'ct.entry_id = cf.entry_id', 'left')->get();

		foreach ($query->result() as $row)
		{
			$this->EE->db->where('file_id', $row->file_id);
			$this->EE->db->update('exp_channel_files', array('member_id' => $row->author_id));
		}

		$query->free_result();

		// Add cffield_1
		if ($this->EE->db->field_exists('cffield_1', 'channel_files') == FALSE)
		{
			$fields = array( 'cffield_1'=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => '') );
			$this->EE->dbforge->add_column('channel_files', $fields);
		}

		// Add cffield_2
		if ($this->EE->db->field_exists('cffield_2', 'channel_files') == FALSE)
		{
			$fields = array( 'cffield_2'=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => '') );
			$this->EE->dbforge->add_column('channel_files', $fields);
		}

		// Add cffield_3
		if ($this->EE->db->field_exists('cffield_3', 'channel_files') == FALSE)
		{
			$fields = array( 'cffield_3'=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => '') );
			$this->EE->dbforge->add_column('channel_files', $fields);
		}

		// Add cffield_4
		if ($this->EE->db->field_exists('cffield_4', 'channel_files') == FALSE)
		{
			$fields = array( 'cffield_4'=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => '') );
			$this->EE->dbforge->add_column('channel_files', $fields);
		}

		// Add cffield_5
		if ($this->EE->db->field_exists('cffield_5', 'channel_files') == FALSE)
		{
			$fields = array( 'cffield_5'=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => '') );
			$this->EE->dbforge->add_column('channel_files', $fields);
		}

		// Add URL TITLE
		if ($this->EE->db->field_exists('url_title', 'channel_files') == FALSE)
		{
			$fields = array( 'url_title'=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => '') );
			$this->EE->dbforge->add_column('channel_files', $fields, 'title');
		}

		// -----------------------------------------
		// Add new Action!
		// -----------------------------------------
		$query = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class = 'Channel_files' AND method = 'simple_file_url'");
		if ($query->num_rows() == 0)
		{
			$module = array('class' => ucfirst('Channel_files'), 'method' => 'simple_file_url' );
			$this->EE->db->insert('exp_actions', $module);
		}

		$query = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class = 'Channel_files' AND method = 'locked_file_url'");
		if ($query->num_rows() == 0)
		{
			$module = array('class' => ucfirst('Channel_files'), 'method' => 'locked_file_url' );
			$this->EE->db->insert('exp_actions', $module);
		}

	}

	// ********************************************************************************* //

}

/* End of file 400.php */
/* Location: ./system/expressionengine/third_party/channel_files/updates/400.php */