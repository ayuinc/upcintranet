<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Channel Files AJAX File
 *
 * @package			DevDemon_ChannelFiles
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com
 */
class Channel_Files_AJAX
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
		$this->site_id = $this->EE->config->item('site_id');
		$this->EE->load->library('channel_files_helper');
		$this->EE->load->model('channel_files_model');
		$this->EE->lang->loadfile('channel_files');
		$this->EE->config->load('cf_config');

		if ($this->EE->input->get_post('site_id')) $this->site_id = $this->EE->input->get_post('site_id');
		else if ($this->EE->input->cookie('cp_last_site_id')) $this->site_id = $this->EE->input->cookie('cp_last_site_id');
		else $this->site_id = $this->EE->config->item('site_id');
	}

	// ********************************************************************************* //

	function upload_file()
	{
		$this->EE->config->load('cf_config');
		$this->EE->load->helper('url');

		// -----------------------------------------
		// EE 2.7 requires XID, flash based stuff breaks..
		// -----------------------------------------
		if ($this->EE->input->post('flash_upload') == 'yes') {
			if (version_compare(APP_VER, '2.7.0') >= 0) {
				$this->EE->security->restore_xid($this->EE->input->post('XID'));
			}
		}

		// -----------------------------------------
		// Increase all types of limits!
		// -----------------------------------------
		@set_time_limit(0);
		@ini_set('memory_limit', '64M');
		@ini_set('memory_limit', '96M');
		@ini_set('memory_limit', '128M');
		@ini_set('memory_limit', '160M');
		@ini_set('memory_limit', '192M');

		error_reporting(E_ALL);
		@ini_set('display_errors', 1);

		$dbfile = FALSE;
		if ($this->EE->input->get_post('file_id') != FALSE) {
			$file_id = $this->EE->input->get_post('file_id');

			$query = $this->EE->db->select('*')->from('exp_channel_files')->where('file_id', $file_id)->get();
			if ($query->num_rows() == 0)
			{
				exit('FILE DOES NOT EXISTS');
			}

			$dbfile = $query->row();
		}

		// -----------------------------------------
		// Standard Vars
		// -----------------------------------------
		$o = array('success' => 'no', 'body' => '');
		$field_id = $this->EE->input->get_post('field_id');
		$field_name = $this->EE->input->get_post('field_name');
		$key = $this->EE->input->get_post('key');

		if ($dbfile == true) {
			$field_id = $dbfile->field_id;
			$key = time();
		}

		// -----------------------------------------
		// Is our $_FILES empty? Commonly when EE does not like the mime-type
		// -----------------------------------------
		if (isset($_FILES['channel_files_file']) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('cf:file_arr_empty');
			exit( $this->EE->channel_files_helper->generate_json($o) );
		}

		// -----------------------------------------
		// Lets check for the key first
		// -----------------------------------------
		if ($key == FALSE)
		{
			$o['body'] = $this->EE->lang->line('cf:tempkey_missing');
			exit( $this->EE->channel_files_helper->generate_json($o) );
		}

		// -----------------------------------------
		// Upload file too big (PHP.INI)
		// -----------------------------------------
		if ($_FILES['channel_files_file']['error'] > 0)
		{
			$o['body'] = $this->EE->lang->line('cf:file_upload_error' . ' CODE:'.$_FILES['channel_files_file']['error']);
			exit( $this->EE->channel_files_helper->generate_json($o) );
		}

		// -----------------------------------------
		// Load Settings
		// -----------------------------------------
		$settings = $this->EE->channel_files_helper->grab_field_settings($field_id);;
		if (isset($settings['upload_location']) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('cf:no_settings');
			exit( $this->EE->channel_files_helper->generate_json($o) );
		}

		// -----------------------------------------
		// Temp Dir to run Actions
		// -----------------------------------------
		$temp_dir = APPPATH.'cache/channel_files/field_'.$field_id.'/'.$key.'/';

		if (@is_dir($temp_dir) === FALSE)
   		{
   			@mkdir($temp_dir, 0777, true);
   			@chmod($temp_dir, 0777);
   		}

		// Last check, does the target dir exist, and is writable
		if (is_really_writable($temp_dir) !== TRUE)
		{
			$o['body'] = $this->EE->lang->line('cf:tempdir_error');
			exit( $this->EE->channel_files_helper->generate_json($o) );
		}


		// -----------------------------------------
		// File Name & Extension
		// -----------------------------------------
		$original_filename = strtolower($this->EE->security->sanitize_filename($_FILES['channel_files_file']['name']));
    	$original_filename = str_replace(array(' ', '+', '%'), array('_', '', ''), $original_filename);

    	// Extension
    	$extension = '.' . substr( strrchr($original_filename, '.'), 1);

    	// Remove Accents and such
    	if (function_exists('iconv') == TRUE) $original_filename = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $original_filename);

    	// The original file stays with the same name
    	$filename = $original_filename;

    	// IOS6 !
    	if ($filename == 'image.jpg')
    	{
    		$filename = 'image_'.time().'.jpg';
    	}

    	// Replace Image? Lets overwrite!
    	if ($dbfile == true) {
    		$filename = $dbfile->filename;
    		$extension = '.'.$dbfile->extension;
    	}

    	// Filesize
    	$filesize = $_FILES['channel_files_file']['size'];

		// -----------------------------------------
		// Move File
		// -----------------------------------------
		if (@move_uploaded_file($_FILES['channel_files_file']['tmp_name'], $temp_dir.$filename) === FALSE)
    	{
    		$o['body'] = $this->EE->lang->line('cf:file_move_error');
	   		exit( $this->EE->channel_files_helper->generate_json($o) );
    	}

		// -----------------------------------------
		// Return Data
		// -----------------------------------------
		@chmod($temp_dir.$filename, 0777);

		$file = array();
		$file['success'] = 'yes';
		$file['title'] = ucfirst(str_replace('_', ' ', str_replace($extension, '', $filename)));
		$file['url_title'] = url_title(trim(strtolower($file['title'])));
		$file['filename'] = $filename;
		$file['extension'] = trim($extension, '.');
		$file['file_id'] = (string)0;
		$file['filesize'] = (string)$filesize;
		$file['md5'] = @md5_file($temp_dir.$filename);

		if (isset($settings['locked_url_fieldtype']) === TRUE && $settings['locked_url_fieldtype'] == 'yes')
		{
			$locked = array();
			$locked['time'] = $this->EE->localize->now + 3600;
			$locked['file_id'] = $file['file_id'];
			$locked['ip'] = $this->EE->input->ip_address();
			$locked['fid'] = $field_id;
			$locked['d'] = $key;
			$locked['temp_dir'] = 'yes';
			$locked['f'] = $filename;

			$file['fileurl'] = $this->EE->channel_files_helper->get_router_url('url', 'simple_file_url').'&amp;key=' . base64_encode($this->EE->channel_files_helper->encrypt_string(serialize($locked)));
		}
		else
		{
			$file['fileurl'] = $this->EE->channel_files_helper->get_router_url('url', 'simple_file_url').'&fid='.$field_id.'&d='.$key.'&temp_dir=yes&f='.$filename;
		}

		if ($dbfile) {
			$this->replace_file($dbfile, $file, $settings, $temp_dir);
		}

		exit($this->EE->channel_files_helper->generate_json($file));
	}

	// ********************************************************************************* //

	private function replace_file($dbfile, $file, $settings, $temp_dir)
	{
		$entry_id = $dbfile->entry_id;
		$field_id = $dbfile->field_id;
		$extension = $dbfile->extension;

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CF_Location_'.$location_type;
		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Cfile_Location') == FALSE) require PATH_THIRD.'channel_files/locations/cfile_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_files/locations/'.$location_type.'/'.$location_type.'.php';
			require $location_file;
		}

		// Init!
		$LOC = new $location_class($location_settings);

		// Create the DIR!
		$LOC->create_dir($entry_id);


		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_files/locations/'.$location_type.'/'.$location_type.'.php';
			require $location_file;
		}

		// -----------------------------------------
		// Upload all Files!
		// -----------------------------------------
		$filepath = $temp_dir . $file['filename'];
		$res = $LOC->upload_file($filepath, $file['filename'], $entry_id, false);


		// -----------------------------------------
		// Old File
		// -----------------------------------------
		$data = array(
						'filesize'	=>	$file['filesize'],
						'md5' => $file['md5'],
					);

		$this->EE->db->update('exp_channel_files', $data, array('file_id' => $dbfile->file_id));

		exit($this->EE->load->view('replace_file_ui_done', $data, TRUE));
	}

	// ********************************************************************************* //

	public function delete_file()
	{
		$out = array('success' => 'no', 'linked' => 'no');

		// Check for Field_id
		if ($this->EE->input->post('field_id') == false) exit('Missing Field_ID');
		$field_id = $this->EE->input->post('field_id');

		// Grab settings
		$settings = $this->EE->channel_files_helper->grab_field_settings($field_id);

		// Store some vars
		$entry_id = $this->EE->input->post('entry_id');
		$key = $this->EE->input->post('key');
		$filename = $this->EE->input->post('filename');
		$file_id = $this->EE->input->post('file_id');

		// -----------------------------------------
		// Check for linked first!
		// -----------------------------------------
		if ($file_id > 0 && $this->EE->input->post('force_delete') != 'yes')
		{
			$query = $this->EE->db->select('file_id')->from('exp_channel_files')->where('link_file_id', $file_id)->get();

			if ($query->num_rows() > 0)
			{
				$out['linked'] ='yes';
				exit($this->EE->channel_files_helper->generate_json($out));
			}
		}

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CF_Location_'.$location_type;
		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Cfile_Location') == FALSE) require PATH_THIRD.'channel_files/locations/cfile_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_files/locations/'.$location_type.'/'.$location_type.'.php';
			require $location_file;
		}

		// Init!
		$LOC = new $location_class($location_settings);

		// -----------------------------------------
		// Delete!
		// -----------------------------------------

		// Entry_id FOLDER?
		if (isset($settings['entry_id_folder']) && $settings['entry_id_folder'] == 'no')
		{
			$entry_id = FALSE;
		}

		if ($this->EE->input->post('entry_id') > 0) $res = $LOC->delete_file($entry_id, $filename);
		else @unlink(APPPATH.'cache/channel_files/field_'.$field_id.'/'.$key.'/'.$filename);

		// Delete from DB
		if ($file_id > 0)
		{
			$this->EE->db->from('exp_channel_files');
			$this->EE->db->where('file_id', $file_id);
			$this->EE->db->or_where('link_file_id', $file_id);
			$this->EE->db->delete();
		}

		$out['success'] = 'yes';
		exit($this->EE->channel_files_helper->generate_json($out));
	}

	// ********************************************************************************* //

	public function test_location()
	{
		$settings = $_POST['channel_files'];

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CF_Location_'.$location_type;
		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Cfile_Location') == FALSE) require PATH_THIRD.'channel_files/locations/cfile_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_files/locations/'.$location_type.'/'.$location_type.'.php';

			require $location_file;
		}

		// Init!
		$LOC = new $location_class($location_settings);

		// Test Location!
		$res = $LOC->test_location();

		exit($res);
	}

	// ********************************************************************************* //

	public function search_files()
	{
		//----------------------------------------
		// Some vars
		//----------------------------------------
		$entry_id = $this->EE->input->get_post('entry_id') ? $this->EE->input->get_post('entry_id') : 0;
		$field_id = $this->EE->input->get_post('field_id');
		$keywords = $this->EE->channel_files_helper->parse_keywords( $this->EE->input->post('keywords') );
		$limit = ($this->EE->input->post('limit') > 1) ? $this->EE->input->post('limit') : 20;

		//----------------------------------------
		// Get Field Settings
		//----------------------------------------
		$settings = $this->EE->channel_files_helper->grab_field_settings($field_id);

		$limit_by_author = (isset($settings['stored_files_by_author']) == TRUE && $settings['stored_files_by_author'] == 'yes') ? TRUE : FALSE;
		if ($limit_by_author == TRUE)
		{
			$limit_by_author = ' AND member_id = ' . $this->EE->session->userdata('member_id') . ' ';
		}


		if ($keywords == FALSE)
		{
			exit('<p><strong style="color:red">' . $this->EE->lang->line('cf:no_keywords') . '</strong></p>');
		}

		if ($keywords == 'JUST_OPEN')
		{
			$keywords = '';
		}

		//----------------------------------------
		// Grab all files
		//----------------------------------------
		$query = $this->EE->db->query("	SELECT	* FROM exp_channel_files
											WHERE	(entry_id != {$entry_id} AND link_file_id < 2)
											{$limit_by_author}
											AND		(title LIKE '%{$keywords}%' OR description LIKE '%{$keywords}%' OR filename LIKE '%{$keywords}%')
											ORDER BY title
											LIMIT	{$limit}
										");

		if ($query->num_rows() == 0)
		{
			exit('<p><strong style="color:red">' . $this->EE->lang->line('cf:no_results') . '</strong></p>');
		}

		$files = $this->EE->channel_files_helper->array_split($query->result());


		exit($this->EE->load->view('pbf/search_results', array('files' => $files), TRUE));

	}

	// ********************************************************************************* //

	public function add_linked_file()
	{
		$this->EE->load->helper('form');

		$file_id = $this->EE->input->get_post('file_id');
		$field_id = $this->EE->input->get_post('field_id');

		// Get File Info
		$query = $this->EE->db->select('*')->from('exp_channel_files')->where('file_id', $file_id)->get();

		$file = $query->row();

		// -----------------------------------------
		// Settings
		// -----------------------------------------
		$settings = $this->EE->channel_files_helper->grab_field_settings($file->field_id);

		$file->linked = TRUE; // Display Unlink icon ;)


		$file->link_file_id = $file->file_id;
		$file->link_entry_id = $file->entry_id;
		$file->file_id = 0;
		$file->primary = 0;

		exit( $this->EE->channel_files_helper->generate_json($file) );
	}

	// ********************************************************************************* //

	public function import_files_ui()
	{
		// Check for Field_id
		if ($this->EE->input->get_post('field_id') == false) exit('Missing Field_ID');
		$field_id = $this->EE->input->get_post('field_id');
		$remaining = $this->EE->input->get_post('remaining');

		if ($remaining < 1) exit($this->EE->lang->line('cf:import:remain_limit'));

		// Grab settings
		$settings = $this->EE->channel_files_helper->grab_field_settings($field_id);

		// Double check
		if ($settings['show_import_files'] != 'yes') exit('IMPORT FILES IS DISABLED');

		// Check the path
		if (@is_dir($settings['import_path']) == FALSE) exit($this->EE->lang->line('cf:import:bad_path'));

		// Grab file extension!
		if ($settings['file_extensions'] != '*.*')
		{
			$settings['file_extensions'] = str_replace('*.', '', $settings['file_extensions']);
			$settings['file_extensions'] = explode(';', $settings['file_extensions']);
		}

		// Grab the files!
		$dirfiles = @scandir($settings['import_path']);

		$files = array();

		// Make the array!
		foreach ($dirfiles as $file)
		{
			if ($file == '.' OR $file == '..' OR strtolower($file) == '.ds_store') continue;
			if (is_dir($settings['import_path'].$file) === TRUE) continue;
			if ($remaining == 0) break;
			$extension = substr( strrchr($file, '.'), 1);

			if (is_array($settings['file_extensions']) && in_array($extension, $settings['file_extensions']) != TRUE) continue;
			$files[$file] = $this->EE->channel_files_helper->format_bytes(@filesize($settings['import_path'].$file));
			$remaining--;
		}

		exit($this->EE->load->view('pbf/import_files', array('files' => $files, 'field_id' => $field_id), TRUE));
	}

	// ********************************************************************************* //

	public function import_files()
	{
		$out = array('files' => array());
		$this->EE->load->helper('url');

		// Check for Field_id
		if ($this->EE->input->get_post('field_id') == false) exit('Missing Field_ID');
		$field_id = $this->EE->input->get_post('field_id');
		$key = $this->EE->input->get_post('key');

		if (isset($_POST['files']) == FALSE OR empty($_POST['files']) == TRUE) exit( $this->EE->channel_files_helper->generate_json($out) );

		// Grab settings
		$settings = $this->EE->channel_files_helper->grab_field_settings($field_id);

		// Temp Dir
		$temp_dir = APPPATH.'cache/channel_files/field_'.$field_id.'/'.$key.'/';

		if (@is_dir($temp_dir) === FALSE)
		{
			@mkdir($temp_dir, 0777, true);
			@chmod($temp_dir, 0777);
		}

		foreach ($_POST['files'] as $filename)
		{
			$original_filename = $filename;
			$filename = strtolower($this->EE->security->sanitize_filename(str_replace(' ', '_', $filename)));

			// Extension
			$extension = substr( strrchr($filename, '.'), 1);

			// Copy the file
			@copy($settings['import_path'].$original_filename, $temp_dir.$filename);

			// Return Data
			@chmod($temp_dir.$filename, 0777);

			$file = array();
			$file['success'] = 'yes';
			$file['title'] = ucfirst(str_replace('_', ' ', str_replace($extension, '', $filename)));
			$file['url_title'] = url_title(trim(strtolower($file['title'])));
			$file['filename'] = $filename;
			$file['extension'] = $extension;
			$file['file_id'] = (string)0;
			$file['filesize'] = (string) @filesize($temp_dir.$filename);
			$file['md5'] = @md5_file($temp_dir.$filename);
			$out['files'][] = $file;
		}

		exit( $this->EE->channel_files_helper->generate_json($out) );
	}

	// ********************************************************************************* //

	public function refresh_files()
	{
		$out = array('success' => 'no', 'files'=>array());

		$field_id = $this->EE->input->post('field_id');
		$entry_id = $this->EE->input->post('entry_id');

		if ($field_id == FALSE OR $entry_id == FALSE)
		{
			exit( $this->EE->channel_files_helper->generate_json($out) );
		}

		$settings = $this->EE->channel_files_helper->grab_field_settings($field_id);

		$this->EE->db->select('*');
		$this->EE->db->from('exp_channel_files');
		$this->EE->db->where('field_id', $field_id);
		$this->EE->db->where('entry_id', $entry_id);
		$this->EE->db->where('is_draft', (($this->EE->input->post('draft') == 'yes') ? 1 : 0)  );
		$this->EE->db->order_by('file_order', 'ASC');
		$query = $this->EE->db->get();

		// Preview URL
		$preview_url = $this->EE->channel_files_helper->get_router_url('url', 'simple_file_url');

		foreach ($query->result() as $file)
		{
			$file->linked = FALSE;

			// Is it a linked image?
			// Then we need to "fake" the channel_id/field_id
			if ($file->link_file_id > 0)
			{
				$file->entry_id = $file->link_entry_id;
				$file->field_id = $file->link_field_id;
				$file->channel_id = $file->link_channel_id;
				$file->linked = TRUE; // Display the break link icon
			}

			// ReAssign Field ID
			$file->field_id = $field_id;
			$file->primary = $file->file_primary;

			// URL
			$file->fileurl = $preview_url . '&amp;fid=' . $file->field_id . '&amp;d=' . $file->entry_id . '&amp;f=' . $file->filename;

			$out['files'][] = $file;

			unset($files);
		}

		$out['success'] = 'yes';

		exit( $this->EE->channel_files_helper->generate_json($out) );
	}

	// ********************************************************************************* //

	public function ajax_download_log()
	{
		$data = array();
		$data['aaData'] = array();
		$data['iTotalRecords'] = 0; // Total records, before filtering (i.e. the total number of records in the database)
		$data['iTotalDisplayRecords'] = 0; // Total records, after filtering (i.e. the total number of records after filtering has been applied - not just the number of records being returned in this result set)
		$data['sEcho'] = $this->EE->input->get_post('sEcho');

		/** ----------------------------------------
		/** Total Records in the DB
		/** ----------------------------------------*/
		$this->EE->db->select('COUNT(*) as total_records', FALSE);
		$this->EE->db->from('exp_channel_files_download_log');
		$query = $this->EE->db->get();

		$data['iTotalRecords'] = $query->row('total_records');
		$query->free_result();

		//----------------------------------------
		// Global Search
		//----------------------------------------
		$search = FALSE;
		if ($this->EE->input->get_post('sSearch') != FALSE)
		{
			$search = $this->EE->input->get_post('sSearch');
		}

		//----------------------------------------
		// Column Search
		//----------------------------------------
		$file_search = FALSE;
		if ($this->EE->input->get_post('sSearch_0') != FALSE)
		{
			$file_search = $this->EE->input->get_post('sSearch_0');
		}

		$entry_search = FALSE;
		if ($this->EE->input->get_post('sSearch_1') != FALSE)
		{
			$entry_search = $this->EE->input->get_post('sSearch_1');
		}

		$member_search = FALSE;
		if ($this->EE->input->get_post('sSearch_2') != FALSE)
		{
			$member_search = $this->EE->input->get_post('sSearch_2');
		}

		//----------------------------------------
		// Date Ranges
		//----------------------------------------
		$date_from = FALSE;
		if ($this->EE->input->get_post('sSearch_3') != FALSE)
		{
			$date_from = strtotime($this->EE->input->get_post('sSearch_3'));
		}

		$date_to = FALSE;
		if ($this->EE->input->get_post('sSearch_4') != FALSE)
		{
			$date_to = strtotime($this->EE->input->get_post('sSearch_4') . ' 23:59 PM');
		}

		/** ----------------------------------------
		/** Total after filter
		/** ----------------------------------------*/
		$this->EE->db->select('COUNT(*) as total_records', FALSE);
		$this->EE->db->from('exp_channel_files_download_log cdl');
		$this->EE->db->join('exp_channel_files cf', 'cdl.file_id = cf.file_id', 'left');
		$this->EE->db->join('exp_channel_titles ct', 'cdl.entry_id = ct.entry_id', 'left');
		$this->EE->db->join('exp_members mb', 'cdl.member_id = mb.member_id', 'left');
		if ($search !== FALSE)
		{
			$this->EE->db->like('ct.title', $search, 'both');
			$this->EE->db->or_like('cf.title', $search, 'both');
			$this->EE->db->or_like('mb.screen_name', $search, 'both');
		}
		if ($file_search) $this->EE->db->like('cf.title', $file_search, 'both');
		if ($entry_search) $this->EE->db->like('ct.title', $entry_search, 'both');
		if ($member_search) $this->EE->db->like('mb.screen_name', $member_search, 'both');
		if ($date_from) $this->EE->db->where('cdl.date >=', $date_from);
		if ($date_to) $this->EE->db->where('cdl.date <=', $date_to);
		$query = $this->EE->db->get();
		$data['iTotalDisplayRecords'] = $query->row('total_records');
		$query->free_result();


		/** ----------------------------------------
		/** Real query
		/** ----------------------------------------*/
		$this->EE->db->select('cdl.ip_address, cdl.date, ct.title, mb.screen_name, cf.title AS filetitle');
		$this->EE->db->from('exp_channel_files_download_log cdl');
		$this->EE->db->join('exp_channel_files cf', 'cdl.file_id = cf.file_id', 'left');
		$this->EE->db->join('exp_channel_titles ct', 'cdl.entry_id = ct.entry_id', 'left');
		$this->EE->db->join('exp_members mb', 'cdl.member_id = mb.member_id', 'left');

		//----------------------------------------
		// Sort By
		//----------------------------------------
		$sort_cols = $this->EE->input->get_post('iSortingCols');

		for ($i = 0; $i < $sort_cols; $i++)
		{
			$col = $this->EE->input->get_post('iSortCol_'.$i);
			$sort =  $this->EE->input->get_post('sSortDir_'.$i);

			switch ($col)
			{
				case 0: // File
					$this->EE->db->order_by('cf.title', $sort);
					break;
				case 1: // Entry
					$this->EE->db->order_by('ct.title', $sort);
					break;
				case 2: // Member
					$this->EE->db->order_by('mb.screen_name', $sort);
					break;
				case 3: // IP
					$this->EE->db->order_by('cdl.ip_address', $sort);
					break;
				case 4: // Date
					$this->EE->db->order_by('cdl.date', $sort);
					break;
			}
		}

		//----------------------------------------
		// Limit
		//----------------------------------------
		$limit = 10;
		if ($this->EE->input->get_post('iDisplayLength') !== FALSE)
		{
			$limit = $this->EE->input->get_post('iDisplayLength');
		}

		//----------------------------------------
		// Offset
		//----------------------------------------
		$offset = 10;
		if ($this->EE->input->get_post('iDisplayStart') !== FALSE)
		{
			$offset = $this->EE->input->get_post('iDisplayStart');
		}


		if ($search !== FALSE)
		{
			$this->EE->db->like('ct.title', $search, 'both');
			$this->EE->db->or_like('cf.title', $search, 'both');
			$this->EE->db->or_like('mb.screen_name', $search, 'both');
		}
		if ($file_search) $this->EE->db->like('cf.title', $file_search, 'both');
		if ($entry_search) $this->EE->db->like('ct.title', $entry_search, 'both');
		if ($member_search) $this->EE->db->like('mb.screen_name', $member_search, 'both');
		if ($date_from) $this->EE->db->where('cdl.date >=', $date_from);
		if ($date_to) $this->EE->db->where('cdl.date <=', $date_to);
		$this->EE->db->limit($limit, $offset);
		$query = $this->EE->db->get();


		//$this->EE->firephp->fb($this->EE->db->last_query());

		foreach ($query->result() as $row)
		{
			$data['aaData'][] = array($row->filetitle, $row->title, $row->screen_name, long2ip($row->ip_address), $this->EE->channel_files_helper->formatDate('%d-%M-%Y %g:%i %A', $row->date));
		}

		exit( $this->EE->channel_files_helper->generate_json($data) );

	}

	// ********************************************************************************* //

	public function display_replace_file_ui()
	{
		$this->EE->load->helper('form');

		$data=array();
		$data['ajax_url'] = $this->EE->channel_files_helper->get_router_url();
		$data['file_id'] = $this->EE->input->get_post('file_id');

		exit($this->EE->load->view('replace_file_ui', $data, TRUE));
	}

	// ********************************************************************************* //

} // END CLASS

/* End of file channel_files_ajax.php  */
/* Location: ./system/expressionengine/third_party/channel_files/libraries/channel_files_ajax.php */
