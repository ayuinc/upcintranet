<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Channel Files API File
 *
 * @package			DevDemon_ChannelFiles
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com
 */
class Channel_Files_API
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
		$this->EE->load->library('channel_files_helper');
		$this->EE->load->model('channel_files_model');
		$this->EE->lang->loadfile('channel_files');
		$this->EE->config->load('cf_config');

		if ($this->EE->input->get_post('site_id')) $this->site_id = $this->EE->input->get_post('site_id');
		else $this->site_id = $this->EE->config->item('site_id');
	}

	// ********************************************************************************* //

	public function delete_file($file)
	{
		if (isset($file->field_id) == FALSE) return FALSE;

		// Grab the field settings
		$settings = $this->EE->channel_files_helper->grab_field_settings($file->field_id);

		// Location
		$location_type = $settings['upload_location'];
		$location_class = 'CF_Location_'.$location_type;
		$location_settings = $settings['locations'][$location_type];
		$location_file = PATH_THIRD.'channel_files/locations/'.$location_type.'/'.$location_type.'.php';

		// Load Main Class
		if (class_exists('Cfile_location') == FALSE) require PATH_THIRD.'channel_files/locations/cfile_location.php';
		if (class_exists($location_class) == FALSE) require $location_file;
		$LOC = new $location_class($location_settings);

		// Delete From DB
		$this->EE->db->where('file_id', $file->file_id);
		$this->EE->db->or_where('link_file_id', $file->file_id);
		$this->EE->db->delete('exp_channel_files');

		// Is there another instance of the image still there?
		$this->EE->db->select('file_id');
		$this->EE->db->from('exp_channel_files');
		$this->EE->db->where('entry_id', $file->entry_id);
		$this->EE->db->where('field_id', $file->field_id);
		$this->EE->db->where('filename', $file->filename);
		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			// Delete original file from system
			$res = $LOC->delete_file($file->entry_id, $file->filename);
		}

		return TRUE;
	}

	// ********************************************************************************* //

	public function clean_temp_dirs($field_id)
	{
		$temp_path = APPPATH.'cache/channel_files/field_'.$field_id.'/';

		if (file_exists($temp_path) !== TRUE) return;

		$this->EE->load->helper('file');

		// Loop over all files
		$tempdirs = @scandir($temp_path);

		foreach ($tempdirs as $tempdir)
		{
			if ($tempdir == '.' OR $tempdir == '..') continue;
			if ( ($this->EE->localize->now - $tempdir) < 7200) continue;

			@chmod($temp_path.$tempdir, 0777);
			@delete_files($temp_path.$tempdir, TRUE);
			@rmdir($temp_path.$tempdir);
		}
	}

	// ********************************************************************************* //

	public function process_field_string($string)
	{
		$string = htmlentities($string, ENT_QUOTES, "UTF-8");
		$string = $this->EE->security->xss_clean($string);

		return $string;
	}

	// ********************************************************************************* //


} // END CLASS

/* End of file api.channel_files.php  */
/* Location: ./system/expressionengine/third_party/channel_files/api.channel_files.php */