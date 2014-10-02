<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Channel Images AJAX File
 *
 * @package			DevDemon_ChannelFiles
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com
 */
class Channel_Images_AJAX
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
		$this->EE->load->library('image_helper');
		$this->EE->load->library('firephp');
		$this->EE->load->model('channel_images_model');
		$this->EE->lang->loadfile('channel_images');
		$this->EE->config->load('ci_config');

		if ($this->EE->input->get_post('site_id')) $this->site_id = $this->EE->input->get_post('site_id');
		else if ($this->EE->input->cookie('cp_last_site_id')) $this->site_id = $this->EE->input->cookie('cp_last_site_id');
		else $this->site_id = $this->EE->config->item('site_id');

		if (isset($this->EE->channel_images) === FALSE) $this->EE->channel_images = new stdClass();

		// Set the EE Cache Path? (hell you can override that)
		if (!isset($this->EE->channel_images->cache_path)) $this->EE->channel_images->cache_path = $this->EE->config->item('cache_path') ? $this->EE->config->item('cache_path') : APPPATH.'cache/';
	}

	// ********************************************************************************* //

	function upload_file()
	{
		$this->EE->config->load('ci_config');
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

		$conf = $this->EE->config->item('channel_images');
		if (is_array($conf) === false) $conf = array();

		if (isset($conf['infinite_memory']) === FALSE || $conf['infinite_memory'] == 'yes')
		{
			@ini_set('memory_limit', '64M');
			@ini_set('memory_limit', '96M');
			@ini_set('memory_limit', '128M');
			@ini_set('memory_limit', '160M');
			@ini_set('memory_limit', '192M');
			@ini_set('memory_limit', '256M');
			@ini_set('memory_limit', '320M');
			@ini_set('memory_limit', '512M');
			//@ini_set('memory_limit', -1);
		}

		error_reporting(E_ALL);
		@ini_set('display_errors', 1);

		$dbimage = FALSE;
		if ($this->EE->input->get_post('image_id') != FALSE) {
			$image_id = $this->EE->input->get_post('image_id');

			$query = $this->EE->db->select('*')->from('exp_channel_images')->where('image_id', $image_id)->get();
			if ($query->num_rows() == 0)
			{
				exit('IMAGE DOES NOT EXISTS');
			}

			$dbimage = $query->row();
		}

		// -----------------------------------------
		// Standard Vars
		// -----------------------------------------
		$o = array('success' => 'no', 'body' => '');

		if ($dbimage == true)
		{
			$field_id = $dbimage->field_id;
			$key = time();
		}
		else
		{
			$field_id = $this->EE->input->get_post('field_id');
			$key = $this->EE->input->get_post('key');
		}

		// -----------------------------------------
		// Is our $_FILES empty? Commonly when EE does not like the mime-type
		// -----------------------------------------
		if (isset($_FILES['channel_images_file']) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:file_arr_empty');
			exit( $this->EE->image_helper->generate_json($o) );
		}

		// -----------------------------------------
		// Lets check for the key first
		// -----------------------------------------
		if ($key == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:tempkey_missing');
			exit( $this->EE->image_helper->generate_json($o) );
		}

		// -----------------------------------------
		// Upload file too big (PHP.INI)
		// -----------------------------------------
		if ($_FILES['channel_images_file']['error'] > 0)
		{
			$o['body'] = $this->EE->lang->line('ci:file_upload_error') . " ({$_FILES['channel_images_file']['error']})";
			exit( $this->EE->image_helper->generate_json($o) );
		}

		// -----------------------------------------
		// Load Settings
		// -----------------------------------------
		$settings = $this->EE->image_helper->grabFieldSettings($field_id);
		if (isset($settings['upload_location']) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:no_settings');
			exit( $this->EE->image_helper->generate_json($o) );
		}

		// -----------------------------------------
		// Temp Dir to run Actions
		// -----------------------------------------
		$temp_dir = $this->EE->channel_images->cache_path.'channel_images/field_'.$field_id.'/'.$key.'/';

		if (@is_dir($temp_dir) === FALSE)
   		{
   			@mkdir($temp_dir, 0777, true);
   			@chmod($temp_dir, 0777);
   		}

		// Last check, does the target dir exist, and is writable
		if (is_really_writable($temp_dir) !== TRUE)
		{
			$o['body'] = $this->EE->lang->line('ci:tempdir_error');
			exit( $this->EE->image_helper->generate_json($o) );
		}


		// -----------------------------------------
		// File Name & Extension
		// -----------------------------------------
		$original_filename = $_FILES['channel_images_file']['name'];
		//$original_filename = str_replace('@', '123atsign123', $original_filename); // Preserve the @ sign
		$original_filename = strtolower($this->EE->security->sanitize_filename($_FILES['channel_images_file']['name']));
    	$original_filename = str_replace(array(' ', '+', '%'), array('_', '', ''), $original_filename);
    	//$original_filename = str_replace('123atsign123', '@', $original_filename); // Put it back!

    	// Extension
    	$extension = '.' . substr( strrchr($original_filename, '.'), 1);

    	/*
    	// Remove Accents and such
    	if (function_exists('iconv') == TRUE)
    	{
    		try {
    			$original_filename2 = @iconv("UTF-8", "ASCII//IGNORE//TRANSLIT", $original_filename);
    		} catch (Exception $e) {
    			$original_filename2 = $original_filename;
    		}

    	}
    	else
    	{
    		$original_filename2 = $original_filename;
    	}
    	*/

    	// The original file stays with the same name
    	//$filename = $original_filename2;ß

    	// ASCII Filename
    	$filename = $this->ascii_string($original_filename);

    	if (isset($conf['ascii_filename']) === TRUE && $conf['ascii_filename'] == 'no')
    	{
    		$filename = $original_filename;
    	}

    	// IOS6 !
    	if ($filename == 'image.jpg')
    	{
    		$filename = 'image_'.time().'.jpg';
    	}

    	// Replace Image? Lets overwrite!
    	if ($dbimage == true)
    	{
    		$filename = $dbimage->filename;
    		$extension = '.'.$dbimage->extension;
    	}

    	// Filesize
    	$filesize = $_FILES['channel_images_file']['size'];

    	// -----------------------------------------
		// Filsize Limit?
		// -----------------------------------------
		if (isset($settings['max_filesize']) === true && $settings['max_filesize'] != false)
		{
			if ($filesize > ($settings['max_filesize']*1024)) {
				$o['body'] = 'File size limit exceeded. (File: '. ((int) ($filesize/1024) ) . 'KB - Max: ' . $settings['max_filesize'].'KB)';
				exit( $this->EE->image_helper->generate_json($o) );
			}
		}


		$filename_no_ext = str_replace($extension, '', $filename);
    	// -----------------------------------------
		// Unique Filenames!
		// -----------------------------------------
		if (isset($_POST['filenames']) === true) {
			$_POST['filenames'] = explode('||', $_POST['filenames']);
		}

		if (isset($_POST['filenames']) === true && in_array($filename, $_POST['filenames']) === TRUE)
		{
			for ($i=2; $i < 50; $i++) {

				if (in_array("{$filename_no_ext}-{$i}{$extension}", $_POST['filenames']) == false)
				{
					$filename = "{$filename_no_ext}-{$i}{$extension}";
					break;
				}
			}
		}

		// -----------------------------------------
		// Move File
		// -----------------------------------------
		if (@move_uploaded_file($_FILES['channel_images_file']['tmp_name'], $temp_dir.$filename) === FALSE)
    	{
    		$o['body'] = $this->EE->lang->line('ci:file_move_error');
	   		exit( $this->EE->image_helper->generate_json($o) );
    	}

    	// Is it an image!?
    	try {
    		$test = getimagesize($temp_dir.$filename);

    		if ($test == FALSE)
    		{
    			$o['body'] = 'Not an image';
				exit( $this->EE->image_helper->generate_json($o) );
    		}
    	} catch (Exception $e) {
    		$o['body'] = 'Not an image';
			exit( $this->EE->image_helper->generate_json($o) );
    	}

    	// -----------------------------------------
		// IPTC
		// -----------------------------------------
		$iptc = array();
		if ($settings['parse_iptc'] == 'yes')
		{
			getimagesize($temp_dir.$filename, $info);

			if (isset($info['APP13']))
			{
			    $iptc = iptcparse($info['APP13']);
			}

			//$this->EE->firephp->log($iptc);
		}


		// -----------------------------------------
		// EXIF
		// -----------------------------------------
		$exif = array();
		if ($settings['parse_exif'] == 'yes')
		{
			if (function_exists('exif_read_data') === true)
			{
	      		$exif = @read_exif_data($temp_dir.$filename);
	      		//$this->EE->firephp->log($exif);
			}
		}

		// -----------------------------------------
		// XMP
		// -----------------------------------------
		$xmp = '';
		if ($settings['parse_xmp'] == 'yes')
		{
			$xmp = $this->getXmpData($temp_dir.$filename, 102400);
		}

    	// -----------------------------------------
		// Convert to jpg?
		// -----------------------------------------
		if (isset($settings['convert_jpg']) === TRUE && $settings['convert_jpg'] == 'yes' && ($extension == '.png' || $extension == '.gif') )
		{
			$original_path = $temp_dir.$filename;
			$filename = str_replace($extension, '.jpg', $filename);
			$extension = '.jpg';

			if (class_exists('ImageAction') == FALSE) include(PATH_THIRD.'channel_images/actions/imageaction.php');
			$class = new ImageAction();

			$class->open_image($original_path);
			$class->save_image($temp_dir.$filename, false, 'jpg');

			@unlink($original_path);
		}

		// -----------------------------------------
        // Auto Rotate iPhone/Andriod pics (thanks to Stuart Barker)
        // -----------------------------------------
        if (function_exists('exif_read_data') === true) {
            if (exif_imagetype($temp_dir.$filename) == IMAGETYPE_JPEG){
                $this->adjustPicOrientation($temp_dir.$filename);
            }
        }

		// -----------------------------------------
		// Load Actions :O
		// -----------------------------------------
		$actions = &$this->EE->image_helper->get_actions();

		// Just double check for actions groups
		if (isset($settings['action_groups']) == FALSE) $settings['action_groups'] = array();

		// -----------------------------------------
		// Loop over all action groups!
		// -----------------------------------------
		foreach ($settings['action_groups'] as $group)
		{
			$size_name = $group['group_name'];
			$size_filename = str_replace($extension, "__{$size_name}{$extension}", $filename);

			// Make a copy of the file
			@copy($temp_dir.$filename, $temp_dir.$size_filename);
			@chmod($temp_dir.$size_filename, 0777);

			// -----------------------------------------
			// Loop over all Actions and RUN! OMG!
			// -----------------------------------------
			foreach($group['actions'] as $action_name => $action_settings)
			{
				// RUN!
				$actions[$action_name]->settings = $action_settings;
				$actions[$action_name]->settings['field_settings'] = $settings;
				$res = $actions[$action_name]->run($temp_dir.$size_filename, $temp_dir);

				if ($res !== TRUE)
				{
					@unlink($temp_dir.$size_filename);
					$o['body'] = 'ACTION ERROR: ' . $res;
	   				exit( $this->EE->image_helper->generate_json($o) );
				}
			}

			if (is_resource($this->EE->channel_images->image) == TRUE) imagedestroy($this->EE->channel_images->image);
		}


		// -----------------------------------------
		// Keep Original Image?
		// -----------------------------------------
		if (isset($settings['keep_original']) == TRUE && $settings['keep_original'] == 'no')
		{
			@unlink($temp_dir.$filename);
		}

		// -----------------------------------------
		// Which Previews?
		// -----------------------------------------
		if ( empty($settings['action_groups']) == FALSE && (isset($settings['no_sizes']) == FALSE OR $settings['no_sizes'] != 'yes') )
		{
			if (isset($settings['small_preview']) == FALSE OR $settings['small_preview'] == FALSE)
			{
				$settings['small_preview'] = $settings['action_groups'][1]['group_name'];
			}

			if (isset($settings['big_preview']) == FALSE OR $settings['big_preview'] == FALSE)
			{
				$settings['big_preview'] = $settings['action_groups'][1]['group_name'];
			}
		}
		else
		{
			// No sizes? Then lets make it be the the original one!
			$settings['small_preview'] = $filename;
			$settings['big_preview'] = $filename;
		}


		// Lets start our image array
		$image = array();

		// Preview URL
		$preview_url = $this->EE->image_helper->get_router_url('url', 'simple_image_url');


		// -----------------------------------------
		// Generate Image URL's
		// -----------------------------------------

		// Are we using the original file?
		if ($settings['small_preview'] == $filename)
		{
			$small_img_filename = $settings['small_preview'];
			$big_img_filename = $settings['small_preview'];
		}
		else
		{
			$small_img_filename = str_replace($extension, "__{$settings['small_preview']}{$extension}", urlencode($filename) );
			$big_img_filename = str_replace($extension, "__{$settings['big_preview']}{$extension}", urlencode($filename) );
		}

		// -----------------------------------------
		// Output
		// -----------------------------------------
		$image['success'] = 'yes';
    	$image['title'] = ucfirst(str_replace('_', ' ', str_replace($extension, '', $filename)));
    	$image['url_title'] = url_title(trim(strtolower($image['title'])));
    	$image['description'] = '';
    	$image['image_id'] = (string)0;
    	$image['category'] = '';
    	$image['cifield_1'] = '';
    	$image['cifield_2'] = '';
    	$image['cifield_3'] = '';
    	$image['cifield_4'] = '';
    	$image['cifield_5'] = '';
    	$image['filename'] = $filename;
		$image['filesize'] = (string)$filesize;
		$image['small_img_url'] = "{$preview_url}&amp;f={$small_img_filename}&amp;fid={$field_id}&amp;d={$key}&amp;temp_dir=yes";
		$image['big_img_url'] = "{$preview_url}&amp;f={$big_img_filename}&amp;fid={$field_id}&amp;d={$key}&amp;temp_dir=yes";

		// -----------------------------------------
		// Parse output
		// -----------------------------------------

		if (isset($settings['columns_default']) === true && is_array($settings['columns_default'])) {
			$vars = array();
			$vars = $this->EE->channel_images_model->parseExif($vars, $exif);
			$vars = $this->EE->channel_images_model->parseXmp($vars, $xmp);
			$vars = $this->EE->channel_images_model->parseIptc($vars, $iptc);

			foreach ($vars as $k => $v) {
				unset($vars[$k]);
				$vars['{'.$k.'}'] = $v;
			}

			//$this->EE->firephp->log($vars);

			foreach ($settings['columns_default'] as $col => $val) {
				if ($col == 'desc') $col = 'description';
				if ($col == 'row_num') continue;
				if ($col == 'id') continue;
				if ($col == 'image') continue;
				if ($col == 'filename') continue;

				if ($col == 'title' && $val == false) continue;

				$image[$col] = str_replace(array_keys($vars), array_values($vars), $val);
			}
		}

		// -----------------------------------------
		// Finalize
		// -----------------------------------------


		$image['iptc'] = base64_encode(serialize($iptc));
		$image['exif'] = base64_encode(serialize($exif));
		$image['xmp'] = base64_encode($xmp);

		if (isset($settings['default_category']) === TRUE && $settings['default_category'] != FALSE)
		{
			$image['category'] = $settings['default_category'];
		}

		if ($dbimage)
		{
			$this->replace_image($dbimage, $image, $settings, $temp_dir);
		}

    	$out = trim($this->EE->image_helper->generate_json($image));
		exit( $out );
	}

	// ********************************************************************************* //

	private function replace_image($dbimage, $image, $settings, $temp_dir)
	{
		$entry_id = $dbimage->entry_id;
		$field_id = $dbimage->field_id;
		$extension = $dbimage->extension;

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CI_Location_'.$location_type;
		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Image_Location') == FALSE) require PATH_THIRD.'channel_images/locations/image_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

			require $location_file;
		}

		// Init!
		$LOC = new $location_class($location_settings);

		// Create the DIR!
		$LOC->create_dir($entry_id);

		// Image Widths,Height,Filesize
		$metadata = array();

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

			require $location_file;
		}

		// Load the API
		if (class_exists('Channel_Images_API') != TRUE) include 'api.channel_images.php';
		$API = new Channel_Images_API();

		// -----------------------------------------
		// Upload all Images!
		// -----------------------------------------

		// Loop over all files
		$tempfiles = @scandir($temp_dir);

		if (is_array($tempfiles) == TRUE)
		{
			foreach ($tempfiles as $tempfile)
			{
				if ($tempfile == '.' OR $tempfile == '..') continue;

				$file	= $temp_dir . '/' . $tempfile;

				$res = $LOC->upload_file($file, $tempfile, $entry_id);

		    	if ($res == FALSE)
		    	{

		    	}

		    	// Parse Image Size
		    	$imginfo = @getimagesize($file);

				// Metadata!
				$metadata[$tempfile] = array('width' => @$imginfo[0], 'height' => @$imginfo[1], 'size' => @filesize($file));

				@unlink($file);
			}
		}

		@rmdir($temp_dir);

		$width = isset($metadata[$image['filename']]['width']) ? $metadata[$image['filename']]['width'] : 0;
		$height = isset($metadata[$image['filename']]['height']) ? $metadata[$image['filename']]['height'] : 0;
		$filesize = isset($metadata[$image['filename']]['size']) ? $metadata[$image['filename']]['size'] : 0;

		// -----------------------------------------
		// Parse Size Metadata!
		// -----------------------------------------
		$mt = '';
		foreach($settings['action_groups'] as $group)
		{
			$name = strtolower($group['group_name']);
			$size_filename = str_replace('.'.$extension, "__{$name}.{$extension}", $image['filename']);

			$mt .= $name.'|' . implode('|', $metadata[$size_filename]) . '/';
		}


		// -----------------------------------------
		// Old File
		// -----------------------------------------
		$data = array(
						'filesize'	=>	$filesize,
						'width'		=>	$width,
						'height'	=>	$height,
						'sizes_metadata' => $mt,
						'iptc'		=>	$image['iptc'],
						'exif'		=>	$image['exif'],
						'xmp'		=>	$image['xmp'],
					);

		$this->EE->db->update('exp_channel_images', $data, array('image_id' =>$dbimage->image_id));

		exit($this->EE->load->view('replace_image_ui_done', $data, TRUE));
	}

	// ********************************************************************************* //

	public function delete_image()
	{
		//$this->EE->firephp->fb($_POST, 'POST');

		if ($this->EE->input->post('field_id') == false) exit('Missing Field_ID');

		$settings = $this->EE->image_helper->grabFieldSettings($this->EE->input->post('field_id'));

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CI_Location_'.$location_type;

		// Load Settings
		if (isset($settings['locations'][$location_type]) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:location_settings_failure');
			exit( $this->EE->image_helper->generate_json($o) );
		}

		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Image_Location') == FALSE) require PATH_THIRD.'channel_images/locations/image_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

			if (file_exists($location_file) == FALSE)
			{
				$o['body'] = $this->EE->lang->line('ci:location_load_failure');
				exit( $this->EE->image_helper->generate_json($o) );
			}

			require $location_file;
		}

		// Init
		$LOC = new $location_class($location_settings);

		// Delete from DB
		if ($this->EE->input->post('image_id') > 0)
		{
			$this->EE->db->from('exp_channel_images');
			$this->EE->db->where('image_id', $this->EE->input->post('image_id'));
			$this->EE->db->or_where('link_image_id', $this->EE->input->post('image_id'));
			$this->EE->db->delete();
		}

		// -----------------------------------------
		// Delete!
		// -----------------------------------------
		$entry_id = $this->EE->input->post('entry_id');
		$key = $this->EE->input->post('key');
		$filename = $this->EE->input->post('filename');
		$extension = '.' . substr( strrchr($filename, '.'), 1);

		foreach($settings['action_groups'] as $group)
		{
			$name = strtolower($group['group_name']);
			$name = str_replace($extension, "__{$name}{$extension}", $filename);

			if ($entry_id > 0) $res = $LOC->delete_file($entry_id, $name);
			else @unlink($this->EE->channel_images->cache_path.'channel_images/'.$key.'/'.$name);
		}


		// Delete original file from system
		if ($entry_id > 0) $res = $LOC->delete_file($entry_id, $filename);
		else @unlink($this->EE->channel_images->cache_path.'channel_images/'.$key.'/'.$filename);

		exit();
	}

	// ********************************************************************************* //

	function test_location()
	{
		$settings = $_POST['channel_images'];

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CI_Location_'.$location_type;
		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Image_Location') == FALSE) require PATH_THIRD.'channel_images/locations/image_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

			require $location_file;
		}

		// Init!
		$LOC = new $location_class($location_settings);

		// Test Location!
		$res = $LOC->test_location();

		exit($res);
	}

	// ********************************************************************************* //

	public function apply_action()
	{
		@set_time_limit(0);

		$conf = $this->EE->config->item('channel_images');
		if (is_array($conf) === false) $conf = array();

		if (isset($conf['infinite_memory']) === FALSE || $conf['infinite_memory'] == 'yes')
		{
			@ini_set('memory_limit', '64M');
			@ini_set('memory_limit', '96M');
			@ini_set('memory_limit', '128M');
			@ini_set('memory_limit', '160M');
			@ini_set('memory_limit', '192M');
		}

		error_reporting(E_ALL);
		@ini_set('display_errors', 1);

		// -----------------------------------------
		// Vars
		// -----------------------------------------
		$stage = $this->EE->input->post('stage');
		$preview_url = $this->EE->image_helper->get_router_url('url', 'simple_image_url');
		$key = $this->EE->input->post('key');
		$akey = $key + 1;
		$size = $this->EE->input->post('size');
		$filename = $this->EE->input->post('filename');
		$image_id = $this->EE->input->post('image_id');
		$field_id = $this->EE->input->post('field_id');
		$entry_id = $this->EE->input->post('entry_id');
		$action = $this->EE->input->post('action');

		// Extension
    	$extension = '.' . substr( strrchr($filename, '.'), 1);

		// Size?
		if ($size != 'ORIGINAL')
		{
			$filename = str_replace($extension, "__{$size}{$extension}", $filename);
		}

		// Grab Fields Settings
		if ($field_id == false) exit('Missing Field_ID');

		$settings = $this->EE->image_helper->grabFieldSettings($field_id);

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CI_Location_'.$location_type;

		// Load Settings
		if (isset($settings['locations'][$location_type]) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:location_settings_failure');
			exit( $this->EE->image_helper->generate_json($o) );
		}

		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Image_Location') == FALSE) require PATH_THIRD.'channel_images/locations/image_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

			if (file_exists($location_file) == FALSE)
			{
				$o['body'] = $this->EE->lang->line('ci:location_load_failure');
				exit( $this->EE->image_helper->generate_json($o) );
			}

			require $location_file;
		}

		// Init
		$LOC = new $location_class($location_settings);

		$temp_dir = $this->EE->channel_images->cache_path.'channel_images/field_'.$field_id.'/'.$akey.'/';

		// -----------------------------------------
		// Saving?
		// -----------------------------------------
		if ($stage == 'save')
		{
			if (file_exists($temp_dir.$filename) == FALSE) exit('ERROR: MISSING PREVIEW IMAGE FILE');

			if ($image_id > 0)
			{
				if ($size != 'ORIGINAL') {
					$query = $this->EE->db->select('*')->from('exp_channel_images')->where('image_id', $image_id)->get();

					//----------------------------------------
					// Size Metadata!
					//----------------------------------------
					$metadata = array();
					if ($query->row('sizes_metadata') != FALSE)
					{
						$temp = explode('/', $query->row('sizes_metadata'));
						foreach($temp as $row)
						{
							if ($row == FALSE) continue;
							$temp2 = explode('|', $row);

							// In some installs size is not set.
							if (isset($temp2[3]) === FALSE OR $temp2[3] == FALSE) $temp2[3] = 0;
							if (isset($temp2[2]) === FALSE OR $temp2[2] == FALSE) $temp2[2] = 0;
							if (isset($temp2[1]) === FALSE OR $temp2[1] == FALSE) $temp2[1] = 0;

							$metadata[$temp2[0]] = array('width' => $temp2[1], 'height'=>$temp2[2], 'size'=>$temp2[3]);
						}
					}

					// Parse Image Size
				    $imginfo = @getimagesize($temp_dir.$filename);
				    $filesize = @filesize($temp_dir.$filename);

					$metadata[ $size ] = array('width' => @$imginfo[0], 'height' => @$imginfo[1], 'size' => $filesize);

					// -----------------------------------------
					// Parse Size Metadata!
					// -----------------------------------------
					$mt = '';
					foreach($settings['action_groups'] as $group)
					{
						$name = strtolower($group['group_name']);
						$mt .= $name.'|' . implode('|', $metadata[$name]) . '/';
					}

					// -----------------------------------------
					// Update Image
					// -----------------------------------------
					$this->EE->db->set('sizes_metadata', $mt);
					$this->EE->db->where('image_id', $image_id);
					$this->EE->db->update('exp_channel_images');
				}

				$response = $LOC->upload_file($temp_dir.$filename, $filename, $entry_id);
			}
			else
			{
				copy($temp_dir.$filename, $this->EE->channel_images->cache_path.'channel_images/field_'.$field_id.'/'.$key.'/'.$filename);
			}

			@unlink($temp_dir.$filename);
			exit();
		}

		// -----------------------------------------
		// Create Temp Location
		// -----------------------------------------
		if (is_dir($temp_dir) == FALSE)
		{
			@mkdir($temp_dir, 0777, true);
   			@chmod($temp_dir, 0777);
		}

		// -----------------------------------------
		// Copy Image to temp location
		// -----------------------------------------
		if ($image_id > 0)
		{
			$response = $LOC->download_file($entry_id, $filename, $temp_dir);
			if ($response !== TRUE) exit($response);
		}
		else
		{
			copy($this->EE->channel_images->cache_path.'channel_images/field_'.$field_id.'/'.$key.'/'.$filename, $temp_dir.$filename);
		}

		@chmod($temp_dir.$filename, 0777);

		// -----------------------------------------
		// Load Action
		// -----------------------------------------
		$actions = &$this->EE->image_helper->get_actions();
		if (isset($_POST['channel_images'][$action]) == FALSE) $action_settings = array();
		else $action_settings = $_POST['channel_images'][$action];

		$actions[$action]->settings = $action_settings;
		$actions[$action]->settings['field_settings'] = $settings;
		$res = $actions[$action]->run($temp_dir.$filename, $temp_dir);

		if (is_resource($this->EE->channel_images->image) == TRUE) imagedestroy($this->EE->channel_images->image);

		if ($res !== TRUE)
		{
			exit('ACTION PROCESS ERROR: ' . $res);
		}

		// -----------------------------------------
		// Preview Only?
		// -----------------------------------------
		if ($stage == 'preview')
		{
			$img_url = "{$preview_url}&amp;f={$filename}&amp;temp_dir=yes&amp;fid={$field_id}&amp;d={$akey}&amp;random=" . rand(100, 99999);
			echo '<img src="' . $img_url . '" />';
			exit();
		}



	}

	// ********************************************************************************* //

	public function load_entries()
	{
		// Load the API
		if (class_exists('Channel_Images_API') != TRUE) include 'api.channel_images.php';
		$API = new Channel_Images_API();

		$limit = $this->EE->input->get('limit') ? $this->EE->input->get('limit') : 100;
		$field_id = $this->EE->input->get('field_id');
		$entry_id = $this->EE->input->get('entry_id');
		$filter = $this->EE->input->get('filter');
		if ($filter == $this->EE->lang->line('ci:filter_keywords')) $filter = FALSE;

		if ($entry_id == FALSE) $entry_id = 99999999;

		if ($field_id == FALSE) exit('MISSING FIELD ID');

		// Get Field
		$query = $this->EE->db->query("SELECT group_id FROM exp_channel_fields WHERE field_id = {$field_id} LIMIT 1");
		if ($query->num_rows() == 0) exit("FIELD NOT FOUND");
		$field_group_id = $query->row('group_id');

		// Get Channels
		$channels = array();
		$query = $this->EE->db->query("SELECT channel_id FROM exp_channels WHERE field_group = {$field_group_id}");
		foreach($query->result() as $row) $channels[] = $row->channel_id;

		// Get entries
		$this->EE->db->select('title, entry_id');
		$this->EE->db->from('exp_channel_titles');
		if ($filter) $this->EE->db->like('title', $API->process_field_string($filter), 'both');
		$this->EE->db->where('status !=', 'closed');
		$this->EE->db->where('entry_id !=',$entry_id);
		if (isset($settings['stored_images_by_author']) == TRUE && $settings['stored_images_by_author'] == 'yes') $this->EE->db->where('author_id', $this->EE->session->userdata['member_id']);
		$this->EE->db->where_in('channel_id', $channels);
		$this->EE->db->order_by('entry_date', 'DESC');
		$this->EE->db->group_by('entry_id');
		$this->EE->db->limit($limit);
		$query = $this->EE->db->get();

		foreach ($query->result() as $row)
		{
			echo "<a href='#' rel='{$row->entry_id}'>&bull; {$row->title}</a>";
		}

		exit();
	}

	// ********************************************************************************* //

	public function load_images()
	{
		$this->EE->load->helper('form');

		// Load the API
		if (class_exists('Channel_Images_API') != TRUE) include 'api.channel_images.php';
		$API = new Channel_Images_API();

		// -----------------------------------------
		// Vars
		// -----------------------------------------
		$entry_id = $this->EE->input->get('entry_id');
		$field_id = $this->EE->input->get('field_id');
		$limit = $this->EE->input->get('limit') ? $this->EE->input->get('limit') : 50;
		$title = $API->process_field_string($this->EE->input->get('title'));
		$desc = $API->process_field_string($this->EE->input->get('desc'));
		$category = $API->process_field_string($this->EE->input->get('category'));
		$cifield_1 = $API->process_field_string($this->EE->input->get('cifield_1'));
		$cifield_2 = $API->process_field_string($this->EE->input->get('cifield_2'));
		$cifield_3 = $API->process_field_string($this->EE->input->get('cifield_3'));
		$cifield_4 = $API->process_field_string($this->EE->input->get('cifield_4'));
		$cifield_5 = $API->process_field_string($this->EE->input->get('cifield_5'));

		// -----------------------------------------
		// Settings
		// -----------------------------------------
		$settings = $this->EE->image_helper->grabFieldSettings($field_id);

		// -----------------------------------------
		// Start Grab The Images
		// -----------------------------------------
		$this->EE->db->select('*');
		$this->EE->db->from('exp_channel_images');
		$this->EE->db->where('field_id', $field_id);
		$this->EE->db->where('link_image_id', 0);
		if ($entry_id != FALSE) $this->EE->db->where('entry_id', $entry_id);

		// -----------------------------------------
		// Limit By what?
		// -----------------------------------------

		// Limit By Author
		if (isset($settings['stored_images_by_author']) == TRUE && $settings['stored_images_by_author'] == 'yes') $this->EE->db->where('member_id', $this->EE->session->userdata['member_id']);

		if ($title != FALSE && $title != $settings['columns']['title']) $this->EE->db->like('title', $title, 'both');
		if ($desc != FALSE && $desc != $settings['columns']['desc']) $this->EE->db->like('description', $desc, 'both');
		if ($category != FALSE && $category != $settings['columns']['category']) $this->EE->db->like('category', $category, 'both');
		if ($cifield_1 != FALSE && $cifield_1 != $settings['columns']['cifield_1']) $this->EE->db->like('cifield_1', $cifield_1, 'both');
		if ($cifield_2 != FALSE && $cifield_2 != $settings['columns']['cifield_2']) $this->EE->db->like('cifield_2', $cifield_2, 'both');
		if ($cifield_3 != FALSE && $cifield_3 != $settings['columns']['cifield_3']) $this->EE->db->like('cifield_3', $cifield_3, 'both');
		if ($cifield_4 != FALSE && $cifield_4 != $settings['columns']['cifield_4']) $this->EE->db->like('cifield_4', $cifield_4, 'both');
		if ($cifield_5 != FALSE && $cifield_5 != $settings['columns']['cifield_5']) $this->EE->db->like('cifield_5', $cifield_5, 'both');

		// -----------------------------------------
		// Grab it
		// -----------------------------------------
		$this->EE->db->limit($limit);
		//$this->EE->db->save_queries = TRUE;
		$query = $this->EE->db->get();
		//print_r($this->EE->db->queries);

		if ($query->num_rows() == 0) exit('<div><p>' . $this->EE->lang->line('ci:no_images') . '</p></div>');

		// -----------------------------------------
		// Which Previews?
		// -----------------------------------------
		if (isset($settings['small_preview']) == FALSE OR $settings['small_preview'] == FALSE)
		{
			if (isset($settings['action_groups'][1]['group_name']) === FALSE) {
				$temp = reset($settings['action_groups']);
				$settings['small_preview'] = $temp['group_name'];
			} else {
				$settings['small_preview'] = $settings['action_groups'][1]['group_name'];
			}
		}

		if (isset($settings['big_preview']) == FALSE OR $settings['big_preview'] == FALSE)
		{
			if (isset($settings['action_groups'][1]['group_name']) === FALSE) {
				$temp = reset($settings['action_groups']);
				$settings['big_preview'] = $temp['group_name'];
			} else {
				$settings['big_preview'] = $settings['action_groups'][1]['group_name'];
			}
		}


		// Preview URL
		$preview_url = $this->EE->image_helper->get_router_url('url', 'simple_image_url');

		// -----------------------------------------
		// Loop over all images
		// -----------------------------------------
		foreach ($query->result() as $image)
		{
			$image->linked = TRUE; // Display Unlink icon ;)

			// We need a good field_id to continue
			$image->field_id = $this->EE->channel_images_model->get_field_id($image);

			// Get settings for that field..
			$image->settings = $this->EE->channel_images_model->get_field_settings($image->field_id);

			$out = '<div class="img">';

			$filename_small = str_replace('.'.$image->extension, "__{$settings['small_preview']}.{$image->extension}", urlencode($image->filename) );
			$filename_big = str_replace('.'.$image->extension, "__{$settings['big_preview']}.{$image->extension}", urlencode($image->filename) );

			if (empty($settings['action_groups']) === TRUE) {
				$filename_small = urlencode($image->filename);
				$filename_big = urlencode($image->filename);
			}

			$image->small_img_url = "{$preview_url}&amp;f={$filename_small}&amp;fid={$image->field_id}&amp;d={$image->entry_id}";
			$image->big_img_url = "{$preview_url}&amp;f={$filename_big}&amp;fid={$image->field_id}&amp;d={$image->entry_id}";

			$image->title = str_replace('&quot;', '"', $image->title);
			$image->description = str_replace('&quot;', '"', $image->description);
			$image->cifield_1 = str_replace('&quot;', '"', $image->cifield_1);
			$image->cifield_2 = str_replace('&quot;', '"', $image->cifield_2);
			$image->cifield_3 = str_replace('&quot;', '"', $image->cifield_3);
			$image->cifield_4 = str_replace('&quot;', '"', $image->cifield_4);
			$image->cifield_5 = str_replace('&quot;', '"', $image->cifield_5);

			$out .= '<a href="' . $image->big_img_url . '" rel="'.$image->image_id.'" title="'.form_prep($image->title).'">';
			$out .= 	'<img src="' . $image->small_img_url . '" width="'.$this->EE->config->item('ci_image_preview_size').'"/>';
			$out .= 	'<span class="add">&nbsp;</span>';
			$out .= '</a>';

			echo $out.'</div>';
		}

		exit();
	}

	// ********************************************************************************* //

	public function add_linked_image()
	{
		$this->EE->load->helper('form');

		$image_id = $this->EE->input->get('image_id');
		$field_id = $this->EE->input->get('field_id');

		// Get Image Info
		$query = $this->EE->db->select('*')->from('exp_channel_images')->where('image_id', $image_id)->get();

		$image = $query->row();

		// -----------------------------------------
		// Settings
		// -----------------------------------------
		$settings = $this->EE->image_helper->grabFieldSettings($image->field_id);

		// -----------------------------------------
		// Which Previews?
		// -----------------------------------------
		if (isset($settings['small_preview']) == FALSE OR $settings['small_preview'] == FALSE)
		{
			if (isset($settings['action_groups'][1]['group_name'])) $settings['small_preview'] = $settings['action_groups'][1]['group_name'];
		}

		if (isset($settings['big_preview']) == FALSE OR $settings['big_preview'] == FALSE)
		{
			if (isset($settings['action_groups'][1]['group_name'])) $settings['big_preview'] = $settings['action_groups'][1]['group_name'];
		}

		// Preview URL
		$preview_url = $this->EE->image_helper->get_router_url('url', 'simple_image_url');

		if (empty($settings['action_groups']) === TRUE) {
			$filename_small = urlencode($image->filename);
			$filename_big = urlencode($image->filename);
		} else {
			$filename_small = str_replace('.'.$image->extension, "__{$settings['small_preview']}.{$image->extension}", urlencode($image->filename) );
			$filename_big = str_replace('.'.$image->extension, "__{$settings['big_preview']}.{$image->extension}", urlencode($image->filename) );
		}

		$image->small_img_url = "{$preview_url}&amp;f={$filename_small}&amp;fid={$image->field_id}&amp;d={$image->entry_id}";
		$image->big_img_url = "{$preview_url}&amp;f={$filename_big}&amp;fid={$image->field_id}&amp;d={$image->entry_id}";

		$image->link_image_id = $image->image_id;
		$image->image_id = 0;
		$image->cover = 0;
		$image->field_id = $field_id;

		$image->title = str_replace('&quot;', '"', $image->title);
		$image->description = str_replace('&quot;', '"', $image->description);
		$image->cifield_1 = str_replace('&quot;', '"', $image->cifield_1);
		$image->cifield_2 = str_replace('&quot;', '"', $image->cifield_2);
		$image->cifield_3 = str_replace('&quot;', '"', $image->cifield_3);
		$image->cifield_4 = str_replace('&quot;', '"', $image->cifield_4);
		$image->cifield_5 = str_replace('&quot;', '"', $image->cifield_5);

		if (isset($settings['default_category']) === TRUE && $settings['default_category'] != FALSE)
		{
			$image->category = $settings['default_category'];
		}

		exit( $this->EE->image_helper->generate_json($image) );
	}

	// ********************************************************************************* //

	public function refresh_images()
	{
		$out = array('success' => 'no', 'images'=>array());

		$field_id = $this->EE->input->post('field_id');
		$entry_id = $this->EE->input->post('entry_id');

		if ($field_id == FALSE OR $entry_id == FALSE)
		{
			exit( $this->EE->image_helper->generate_json($out) );
		}

		$settings = $this->EE->image_helper->grabFieldSettings($field_id);

		$this->EE->db->select('*');
		$this->EE->db->from('exp_channel_images');
		$this->EE->db->where('field_id', $field_id);
		$this->EE->db->where('entry_id', $entry_id);
		$this->EE->db->where('is_draft', (($this->EE->input->post('draft') == 'yes') ? 1 : 0)  );
		$this->EE->db->order_by('image_order', 'ASC');
		$query = $this->EE->db->get();

		// -----------------------------------------
		// Which Previews?
		// -----------------------------------------
		if (isset($settings['small_preview']) == FALSE OR $settings['small_preview'] == FALSE)
		{
			$temp = reset($settings['action_groups']);
			$settings['small_preview'] = $temp['group_name'];
		}

		if (isset($settings['big_preview']) == FALSE OR $settings['big_preview'] == FALSE)
		{
			$temp = reset($settings['action_groups']);
			$settings['big_preview'] = $temp['group_name'];
		}

		// Preview URL
		$preview_url = $this->EE->image_helper->get_router_url('url', 'simple_image_url');

		foreach ($query->result() as $image)
		{
			// We need a good field_id to continue
			$image->field_id = $this->EE->channel_images_model->get_field_id($image);

			// Is it a linked image?
			// Then we need to "fake" the channel_id/field_id
			if ($image->link_image_id >= 1)
			{
				$image->entry_id = $image->link_entry_id;
				$image->field_id = $image->link_field_id;
				$image->channel_id = $image->link_channel_id;
			}

			// Just in case lets try to get the field_id again
			$image->field_id = $this->EE->channel_images_model->get_field_id($image);

			// Get settings for that field..
			$temp_settings = $this->EE->channel_images_model->get_field_settings($image->field_id);

			$act_url_params = "&amp;fid={$image->field_id}&amp;d={$image->entry_id}";

			if ( empty($settings['action_groups']) == FALSE && (isset($settings['no_sizes']) == FALSE OR $settings['no_sizes'] != 'yes') )
			{
				// Display SIzes URL
				$small_filename = str_replace('.'.$image->extension, "__{$settings['small_preview']}.{$image->extension}", urlencode($image->filename) );
				$big_filename = str_replace('.'.$image->extension, "__{$settings['big_preview']}.{$image->extension}", urlencode($image->filename) );

				$image->small_img_url = "{$preview_url}&amp;f={$small_filename}{$act_url_params}";
				$image->big_img_url = "{$preview_url}&amp;f={$big_filename}{$act_url_params}";
			}
			else
			{
				$small_filename = $image->filename;
				$big_filename = $image->filename;

				// Display SIzes URL
				$image->small_img_url = "{$preview_url}&amp;f={$small_filename}{$act_url_params}";
				$image->big_img_url = "{$preview_url}&amp;f={$big_filename}{$act_url_params}";
			}

			// ReAssign Field ID (WE NEED THIS)
			$image->field_id = $field_id;

			$image->title = str_replace('&quot;', '"', $image->title);
			$image->description = str_replace('&quot;', '"', $image->description);
			$image->cifield_1 = str_replace('&quot;', '"', $image->cifield_1);
			$image->cifield_2 = str_replace('&quot;', '"', $image->cifield_2);
			$image->cifield_3 = str_replace('&quot;', '"', $image->cifield_3);
			$image->cifield_4 = str_replace('&quot;', '"', $image->cifield_4);
			$image->cifield_5 = str_replace('&quot;', '"', $image->cifield_5);

			$out['images'][] = $image;

			unset($image);
		}

		$out['success'] = 'yes';

		exit( $this->EE->image_helper->generate_json($out) );
	}

	// ********************************************************************************* //

	public function grab_image_totals()
	{
		$out = array('success' => 'no', 'total_images' => 0, 'batches' => array());
		$field_id = $this->EE->input->post('field_id');

		// To which group id does this field belong?
		$query = $this->EE->db->select('group_id')->from('exp_channel_fields')->where('field_id', $field_id)->get();
		$group_id = $query->row('group_id');

		$query->free_result();

		// To which channels does this field_group belong to?
		$channels = array();
		$query = $this->EE->db->select('channel_id')->from('exp_channels')->where('field_group', $group_id)->get();

		foreach ($query->result() as $row) $channels[] = $row->channel_id;

		// Check for empty channels
		if (empty($channels) == TRUE)
		{
			exit('NO CHANNELS ASSIGNED TO THIS FIELD!');
		}

		$query->free_result();

		$query = $this->EE->db->select('COUNT(*) AS total_images', FALSE)->from('exp_channel_images')->where_in('channel_id', $channels)->where('link_image_id', 0)->where('field_id', $field_id)->get();
		$total = $query->row('total_images');
		$out['total_images'] = $total;

		$batches = round(($total / 100));

		if ($batches == 0) $batches = 1;

		$count = 0;
		for ($i=0; $i < $batches; $i++) {
			$out['batches'][] = $count;
			$count += 100;
		}

		//$out['batches'] = $batches;

		exit( $this->EE->image_helper->generate_json($out) );
	}

	// ********************************************************************************* //

	public function grab_image_ids()
	{
		$out = array('success' => 'no', 'images' => array());
		$field_id = $this->EE->input->post('field_id');
		$offset = $this->EE->input->post('offset');

		// To which group id does this field belong?
		$query = $this->EE->db->select('group_id')->from('exp_channel_fields')->where('field_id', $field_id)->get();
		$group_id = $query->row('group_id');

		$query->free_result();

		// To which channels does this field_group belong to?
		$channels = array();
		$query = $this->EE->db->select('channel_id')->from('exp_channels')->where('field_group', $group_id)->get();

		foreach ($query->result() as $row) $channels[] = $row->channel_id;

		// Check for empty channels
		if (empty($channels) == TRUE)
		{
			exit('NO CHANNELS ASSIGNED TO THIS FIELD!');
		}

		$query->free_result();

		//Grab all images
		$out['images'] = array();
		$query = $this->EE->db->select('filename, image_id, title, entry_id, channel_id')->from('exp_channel_images')->where_in('channel_id', $channels)->where('link_image_id', 0)->where('field_id', $field_id)
		->limit(100, $offset)->get();

		if ($query->num_rows > 0)
		{
			foreach ($query->result() as $row)
			{
				$out['images'][] = $row;
			}

		}

		exit( $this->EE->image_helper->generate_json($out) );
	}

	// ********************************************************************************* //

	public function import_files_ui()
	{
		// Check for Field_id
		if ($this->EE->input->get_post('field_id') == false) exit('Missing Field_ID');
		$field_id = $this->EE->input->get_post('field_id');
		$remaining = $this->EE->input->get_post('remaining');

		if ($remaining < 1) exit($this->EE->lang->line('ci:import:remain_limit'));

		// Grab settings
		$settings = $this->EE->image_helper->grabFieldSettings($field_id);

		// Double check
		if ($settings['show_import_files'] != 'yes') exit('IMPORT FILES IS DISABLED');

		// Check the path
		if (@is_dir($settings['import_path']) == FALSE) exit($this->EE->lang->line('ci:import:bad_path'));

		// Grab file extension!
		$settings['file_extensions'] = array('jpg', 'jpeg', 'gif', 'png');

		// Grab the files!
		$dirfiles = @scandir($settings['import_path']);

		$files = array();

		$this->EE->load->helper('number');

		// Make the array!
		foreach ($dirfiles as $file)
		{
			if ($file == '.' OR $file == '..' OR strtolower($file) == '.ds_store') continue;
			if (is_dir($settings['import_path'].$file) === TRUE) continue;
			if ($remaining == 0) break;
			$extension = strtolower(substr( strrchr($file, '.'), 1));

			if (is_array($settings['file_extensions']) && in_array($extension, $settings['file_extensions']) != TRUE) continue;
			$files[$file] = byte_format(@filesize($settings['import_path'].$file));
			$remaining--;
		}

		exit($this->EE->load->view('pbf/import_files', array('files' => $files, 'field_id' => $field_id), TRUE));
	}

	// ********************************************************************************* //

	public function import_images()
	{
		$out = array('files' => array());
		$this->EE->load->helper('url');

		// Check for Field_id
		if ($this->EE->input->get_post('field_id') == false) exit('Missing Field_ID');
		$field_id = $this->EE->input->get_post('field_id');
		$key = $this->EE->input->get_post('key');

		if (isset($_POST['files']) == FALSE OR empty($_POST['files']) == TRUE) exit( $this->EE->channel_images_helper->generate_json($out) );

		// Grab settings
		$settings = $this->EE->image_helper->grabFieldSettings($field_id);

		// Temp Dir
		$temp_dir = $this->EE->channel_images->cache_path.'channel_images/field_'.$field_id.'/'.$key.'/';

		if (@is_dir($temp_dir) === FALSE)
		{
			@mkdir($temp_dir, 0777, true);
			@chmod($temp_dir, 0777);
		}

		// -----------------------------------------
		// Load Actions :O
		// -----------------------------------------
		$actions = &$this->EE->image_helper->get_actions();

		// Just double check for actions groups
		if (isset($settings['action_groups']) == FALSE) $settings['action_groups'] = array();


		foreach ($_POST['files'] as $filename)
		{
			$original_filename = $filename;
			$filename = strtolower($this->EE->security->sanitize_filename($filename));
    		$filename= str_replace(array(' ', '+'), array('_', ''), $filename);

			// Extension
			$extension = '.' . substr( strrchr($filename, '.'), 1);

			// Copy the file
			@copy($settings['import_path'].$original_filename, $temp_dir.$filename);

			// Return Data
			@chmod($temp_dir.$filename, 0777);

			$filesize = @filesize($temp_dir.$filename);

			// -----------------------------------------
			// Loop over all action groups!
			// -----------------------------------------
			foreach ($settings['action_groups'] as $group)
			{
				$size_name = $group['group_name'];
				$size_filename = str_replace($extension, "__{$size_name}{$extension}", $filename);

				// Make a copy of the file
				@copy($temp_dir.$filename, $temp_dir.$size_filename);
				@chmod($temp_dir.$size_filename, 0777);

				// -----------------------------------------
				// Loop over all Actions and RUN! OMG!
				// -----------------------------------------
				foreach($group['actions'] as $action_name => $action_settings)
				{
					// RUN!
					$actions[$action_name]->settings = $action_settings;
					$actions[$action_name]->settings['field_settings'] = $settings;
					$res = $actions[$action_name]->run($temp_dir.$size_filename, $temp_dir);

					if ($res !== TRUE)
					{
						@unlink($temp_dir.$size_filename);
						$o['body'] = 'ACTION ERROR: ' . $res;
		   				exit( $this->EE->image_helper->generate_json($o) );
					}
				}

				if (is_resource($this->EE->channel_images->image) == TRUE) imagedestroy($this->EE->channel_images->image);
			}

			// -----------------------------------------
			// Keep Original Image?
			// -----------------------------------------
			if (isset($settings['keep_original']) == TRUE && $settings['keep_original'] == 'no')
			{
				@unlink($temp_dir.$filename);
			}

			// -----------------------------------------
			// Parse Original Image Info
			// -----------------------------------------
			$imginfo = @getimagesize($temp_dir.$filename);
			$filesize = @filesize($temp_dir.$filename);
			$width = @$imginfo[0];
			$height = @$imginfo[1];

			// -----------------------------------------
			// IPTC
			// -----------------------------------------
			$iptc = array();
			if ($settings['parse_iptc'] == 'yes')
			{
				getimagesize($temp_dir.$filename, $info);

				if (isset($info['APP13']))
				{
				    $iptc = iptcparse($info['APP13']);
				}
			}


			// -----------------------------------------
			// EXIF
			// -----------------------------------------
			$exif = array();
			if ($settings['parse_exif'] == 'yes')
			{
				if (function_exists('exif_read_data') === true)
				{
		      		$exif = @read_exif_data($temp_dir.$filename);
				}
			}

			// -----------------------------------------
			// XMP
			// -----------------------------------------
			$xmp = '';
			if ($settings['parse_xmp'] == 'yes')
			{
				$xmp = $this->getXmpData($temp_dir.$filename, 102400);
			}




			// -----------------------------------------
			// Which Previews?
			// -----------------------------------------
			if ( empty($settings['action_groups']) == FALSE && (isset($settings['no_sizes']) == FALSE OR $settings['no_sizes'] != 'yes') )
			{
				if (isset($settings['small_preview']) == FALSE OR $settings['small_preview'] == FALSE)
				{
					$settings['small_preview'] = $settings['action_groups'][1]['group_name'];
				}

				if (isset($settings['big_preview']) == FALSE OR $settings['big_preview'] == FALSE)
				{
					$settings['big_preview'] = $settings['action_groups'][1]['group_name'];
				}
			}
			else
			{
				// No sizes? Then lets make it be the the original one!
				$settings['small_preview'] = $filename;
				$settings['big_preview'] = $filename;
			}

			// Lets start our image array
			$image = array();

			// Preview URL
			$preview_url = $this->EE->image_helper->get_router_url('url', 'simple_image_url');

			// -----------------------------------------
			// Generate Image URL's
			// -----------------------------------------

			// Are we using the original file?
			if ($settings['small_preview'] == $filename)
			{
				$small_img_filename = $settings['small_preview'];
				$big_img_filename = $settings['small_preview'];
			}
			else
			{
				$small_img_filename = str_replace($extension, "__{$settings['small_preview']}{$extension}", urlencode($filename) );
				$big_img_filename = str_replace($extension, "__{$settings['big_preview']}{$extension}", urlencode($filename) );
			}

			$image['title'] = ucfirst(str_replace('_', ' ', str_replace($extension, '', $filename)));
	    	$image['url_title'] = url_title(trim(strtolower($image['title'])));
	    	$image['description'] = '';
	    	$image['image_id'] = (string)0;
	    	$image['category'] = '';
	    	$image['cifield_1'] = '';
	    	$image['cifield_2'] = '';
	    	$image['cifield_3'] = '';
	    	$image['cifield_4'] = '';
	    	$image['cifield_5'] = '';
	    	$image['filename'] = $filename;
			$image['filesize'] = (string)$filesize;
			$image['iptc'] = base64_encode(serialize($iptc));
			$image['exif'] = base64_encode(serialize($exif));
			$image['xmp'] = base64_encode($xmp);
			$image['small_img_url'] = "{$preview_url}&amp;f={$small_img_filename}&amp;fid={$field_id}&amp;d={$key}&amp;temp_dir=yes";
			$image['big_img_url'] = "{$preview_url}&amp;f={$big_img_filename}&amp;fid={$field_id}&amp;d={$key}&amp;temp_dir=yes";
			$out['files'][] = $image;
		}

		exit( $this->EE->image_helper->generate_json($out) );
	}

	// ********************************************************************************* //

	public function edit_image_ui()
	{
		@set_time_limit(0);

		$conf = $this->EE->config->item('channel_images');
		if (is_array($conf) === false) $conf = array();

		if (isset($conf['infinite_memory']) === FALSE || $conf['infinite_memory'] == 'yes')
		{
			@ini_set('memory_limit', '64M');
			@ini_set('memory_limit', '96M');
			@ini_set('memory_limit', '128M');
			@ini_set('memory_limit', '160M');
			@ini_set('memory_limit', '192M');
		}

		error_reporting(E_ALL);
		@ini_set('display_errors', 1);

		// -----------------------------------------
		// Vars
		// -----------------------------------------
		$stage = $this->EE->input->post('stage');
		$preview_url = $this->EE->image_helper->get_router_url('url', 'simple_image_url');
		$key = $this->EE->input->post('key');
		$akey = $key + 1;
		$filename = $this->EE->input->post('filename');
		$image_id = $this->EE->input->post('image_id');
		$field_id = $this->EE->input->post('field_id');
		$entry_id = $this->EE->input->post('entry_id');
		$action = $this->EE->input->post('action');

		if ($image_id > 0)
		{
			$query = $this->EE->db->select('*')->from('exp_channel_images')->where('image_id', $image_id)->get();

			if ($query->row('link_image_id') > 0)
			{
				$field_id = $query->row('link_field_id');
				$entry_id = $query->row('link_entry_id');
			}
		}

		// Extension
    	$extension = '.' . substr( strrchr($filename, '.'), 1);

		// Grab Fields Settings
		if ($field_id == false) exit('Missing Field_ID');

		$settings = $this->EE->image_helper->grabFieldSettings($field_id);

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CI_Location_'.$location_type;

		// Load Settings
		if (isset($settings['locations'][$location_type]) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:location_settings_failure');
			exit( $this->EE->image_helper->generate_json($o) );
		}

		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Image_Location') == FALSE) require PATH_THIRD.'channel_images/locations/image_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

			if (file_exists($location_file) == FALSE)
			{
				$o['body'] = $this->EE->lang->line('ci:location_load_failure');
				exit( $this->EE->image_helper->generate_json($o) );
			}

			require $location_file;
		}

		// Init
		$LOC = new $location_class($location_settings);

		$temp_dir = $this->EE->channel_images->cache_path.'channel_images/field_'.$field_id.'/'.$akey.'/';

		// -----------------------------------------
		// Create Temp Location
		// -----------------------------------------
		if (is_dir($temp_dir) == FALSE)
		{
			@mkdir($temp_dir, 0777, true);
   			@chmod($temp_dir, 0777);
		}

		// -----------------------------------------
		// Copy Image to temp location
		// -----------------------------------------
		if ($image_id > 0)
		{
			$response = $LOC->download_file($entry_id, $filename, $temp_dir);
			if ($response !== TRUE) exit($response);
		}
		else
		{
			copy($this->EE->channel_images->cache_path.'channel_images/field_'.$field_id.'/'.$key.'/'.$filename, $temp_dir.$filename);
		}

		@chmod($temp_dir.$filename, 0777);

		// Parse Image Size
		$imginfo = @getimagesize($temp_dir.$filename);

		// Copy a scaled version
		copy($temp_dir.$filename, $temp_dir.'SCALED_' . $filename);
		$filename = 'SCALED_' . $filename;

		// Load actions
		$actions = &$this->EE->image_helper->get_actions();

		// -----------------------------------------
		// Scale it!
		// -----------------------------------------
		$actions['resize']->settings = array('width' => 0, 'height' => 500, 'quality' => 70);
		$actions['resize']->settings['field_settings'] = $settings;
		$actions['resize']->run($temp_dir.$filename, $temp_dir);

		// -----------------------------------------
		// Black White
		// -----------------------------------------
		$filename_alt = 'BW_' . $filename;
		copy($temp_dir.$filename, $temp_dir.$filename_alt);

		$actions['greyscale']->settings['field_settings'] = $settings;
		$actions['greyscale']->run($temp_dir.$filename_alt, $temp_dir);
		if (is_resource($this->EE->channel_images->image) == TRUE) imagedestroy($this->EE->channel_images->image);

		$data = array();

		// -----------------------------------------
		// Loopo over all actions
		// -----------------------------------------
		$data['sizes'] = array();
		foreach ($settings['action_groups'] as $group)
		{
			if (isset($group['editable']) == FALSE OR $group['editable'] == 'no') continue;

			$size_name = $group['group_name'];
			$size_filename = str_replace($extension, "__{$size_name}{$extension}", $filename);

			if (isset($group['final_size']) === TRUE && $group['final_size'] != FALSE)
			{
				$data['sizes'][ $group['group_name'] ] = array('width'=>$group['final_size']['width'], 'height' => $group['final_size']['height']);
			}
			else
			{
				$data['sizes'][ $group['group_name'] ] = array('width'=>'FALSE', 'height' => 'FALSE');
			}
		}

		$data['img_info'] = $imginfo;
		$data['img_url'] = "{$preview_url}&f={$filename}&temp_dir=yes&fid={$field_id}&d={$akey}&random=" . rand(100, 99999);
		$data['img_url_alt'] = "{$preview_url}&f={$filename_alt}&temp_dir=yes&fid={$field_id}&d={$akey}&random=" . rand(100, 99999);

		if ($this->EE->input->post('refresh_images') == 'yes')
		{
			exit( $this->EE->image_helper->generate_json($data) );
		}

		exit($this->EE->load->view('pbf/edit_image_ui', $data, TRUE));
	}

	// ********************************************************************************* //

	public function apply_edit_image_action()
	{
		@set_time_limit(0);

		$conf = $this->EE->config->item('channel_images');
		if (is_array($conf) === false) $conf = array();

		if (isset($conf['infinite_memory']) === FALSE || $conf['infinite_memory'] == 'yes')
		{
			@ini_set('memory_limit', '64M');
			@ini_set('memory_limit', '96M');
			@ini_set('memory_limit', '128M');
			@ini_set('memory_limit', '160M');
			@ini_set('memory_limit', '192M');
		}

		error_reporting(E_ALL);
		@ini_set('display_errors', 1);

		// -----------------------------------------
		// Vars
		// -----------------------------------------
		$stage = $this->EE->input->post('stage');
		$preview_url = $this->EE->image_helper->get_router_url('url', 'simple_image_url');
		$key = $this->EE->input->post('key');
		$akey = $key + 1;
		$filename = $this->EE->input->post('filename');
		$image_id = $this->EE->input->post('image_id');
		$field_id = $this->EE->input->post('field_id');
		$entry_id = $this->EE->input->post('entry_id');
		$action = $this->EE->input->post('action');
		$size = $this->EE->input->post('size');

		if ($image_id > 0)
		{
			$query = $this->EE->db->select('*')->from('exp_channel_images')->where('image_id', $image_id)->get();

			if ($query->row('link_image_id') > 0)
			{
				$field_id = $query->row('link_field_id');
				$entry_id = $query->row('link_entry_id');
			}
		}

		// Extension
    	$extension = '.' . substr( strrchr($filename, '.'), 1);

		// Grab Fields Settings
		if ($field_id == false) exit('Missing Field_ID');

		$settings = $this->EE->image_helper->grabFieldSettings($field_id);

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CI_Location_'.$location_type;

		// Load Settings
		if (isset($settings['locations'][$location_type]) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:location_settings_failure');
			exit( $this->EE->image_helper->generate_json($o) );
		}

		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Image_Location') == FALSE) require PATH_THIRD.'channel_images/locations/image_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

			if (file_exists($location_file) == FALSE)
			{
				$o['body'] = $this->EE->lang->line('ci:location_load_failure');
				exit( $this->EE->image_helper->generate_json($o) );
			}

			require $location_file;
		}

		// Init
		$LOC = new $location_class($location_settings);

		$temp_dir = $this->EE->channel_images->cache_path.'channel_images/field_'.$field_id.'/'.$akey.'/';

		// -----------------------------------------
		// Create Temp Location
		// -----------------------------------------
		if (is_dir($temp_dir) == FALSE)
		{
			@mkdir($temp_dir, 0777, true);
   			@chmod($temp_dir, 0777);
		}

		//copy($this->EE->channel_images->cache_path.'channel_images/field_'.$field_id.'/'.$key.'/'.$filename, $temp_dir.$filename);

		// Load actions
		$actions = &$this->EE->image_helper->get_actions();


		/*
		// Image Filename
		if ($size != 'ORIGINAL')
		{
			//$filename = str_replace($extension, "__{$size}{$extension}", $filename);
		}
		 */

		// Parse Image Size
		$imginfo = @getimagesize($temp_dir.$filename);

		// Rotate Left?
		if ($action == 'rotate-left' OR $action == 'rotate-right')
		{
			if ($action == 'rotate-right') $actions['rotate']->settings = array('degrees' => 90, 'background_color' => 'ffffff');
			else $actions['rotate']->settings = array('degrees' => -90, 'background_color' => 'ffffff');

			$actions['rotate']->settings['field_settings'] = $settings;
			$actions['rotate']->run($temp_dir.$filename, $temp_dir);
			$actions['rotate']->run($temp_dir.'SCALED_' . $filename, $temp_dir);
			$actions['rotate']->run($temp_dir.'BW_SCALED_' . $filename, $temp_dir);
		}

		// Flip Left?
		if ($action == 'flip-hor' OR $action == 'flip-ver')
		{
			if ($action == 'flip-ver') $actions['flip']->settings = array('axis' => 'vertical');
			else $actions['flip']->settings = array('axis' => 'horizontal');

			$actions['flip']->settings['field_settings'] = $settings;
			$actions['flip']->run($temp_dir.$filename, $temp_dir);
			$actions['flip']->run($temp_dir.'SCALED_' . $filename, $temp_dir);
			$actions['flip']->run($temp_dir.'BW_SCALED_' . $filename, $temp_dir);
		}

		if ($action == 'crop')
		{
			// Converted to JSON because some addons love to parse POST/GET vars, but don't take arrays in to account!
			$_POST['selection'] = $this->EE->image_helper->decode_json($_POST['selection'], true);
			$actions['crop_standard']->settings = array('start_x' => $_POST['selection']['x'], 'start_y' => $_POST['selection']['y'], 'width' => $_POST['selection']['w'], 'height' => $_POST['selection']['h'], 'quality' => 100);
			$actions['crop_standard']->settings['field_settings'] = $settings;
			$actions['crop_standard']->run($temp_dir.$filename, $temp_dir);

			copy($temp_dir.$filename, $temp_dir.'SCALED_'.$filename);

			$imginfo = @getimagesize($temp_dir.$filename);

			if ($imginfo[1] > 500)
			{
				$actions['resize']->settings = array('width' => 0, 'height' => 500, 'quality' => 70);
				$actions['resize']->settings['field_settings'] = $settings;
				$actions['resize']->run($temp_dir.'SCALED_'.$filename, $temp_dir);
			}

			copy($temp_dir.'SCALED_'.$filename, $temp_dir.'BW_SCALED_'.$filename);
			$actions['greyscale']->settings['field_settings'] = $settings;
			$actions['greyscale']->run($temp_dir.'BW_SCALED_'.$filename, $temp_dir);
		}

		// New Names
		$filename = 'SCALED_' . $filename;
		$filename_alt = 'BW_' . $filename;

		$data = array();
		$data['img_info'] = $imginfo;
		$data['img_url'] = "{$preview_url}&f={$filename}&temp_dir=yes&fid={$field_id}&d={$akey}&random=" . rand(100, 99999);
		$data['img_url_alt'] = "{$preview_url}&f={$filename_alt}&temp_dir=yes&fid={$field_id}&d={$akey}&random=" . rand(100, 99999);
		exit( $this->EE->image_helper->generate_json($data) );
	}

	// ********************************************************************************* //

	public function edit_image_save()
	{
		@set_time_limit(0);

		$conf = $this->EE->config->item('channel_images');
		if (is_array($conf) === false) $conf = array();

		if (isset($conf['infinite_memory']) === FALSE || $conf['infinite_memory'] == 'yes')
		{
			@ini_set('memory_limit', '64M');
			@ini_set('memory_limit', '96M');
			@ini_set('memory_limit', '128M');
			@ini_set('memory_limit', '160M');
			@ini_set('memory_limit', '192M');
		}

		error_reporting(E_ALL);
		@ini_set('display_errors', 1);

		// -----------------------------------------
		// Vars
		// -----------------------------------------
		$key = $this->EE->input->post('key');
		$akey = $key + 1;
		$filename = $this->EE->input->post('filename');
		$image_id = $this->EE->input->post('image_id');
		$field_id = $this->EE->input->post('field_id');
		$entry_id = $this->EE->input->post('entry_id');
		$regen_sizes = $this->EE->input->post('regen_sizes');
		$size = $this->EE->input->post('size');

		if ($image_id > 0)
		{
			$query = $this->EE->db->select('*')->from('exp_channel_images')->where('image_id', $image_id)->get();
			if ($query->row('link_image_id') > 0)
			{
				$field_id = $query->row('link_field_id');
				$entry_id = $query->row('link_entry_id');
			}

			//----------------------------------------
			// Size Metadata!
			//----------------------------------------
			$metadata = array();
			if ($query->row('sizes_metadata') != FALSE)
			{
				$temp = explode('/', $query->row('sizes_metadata'));
				foreach($temp as $row)
				{
					if ($row == FALSE) continue;
					$temp2 = explode('|', $row);

					// In some installs size is not set.
					if (isset($temp2[3]) === FALSE OR $temp2[3] == FALSE) $temp2[3] = 0;
					if (isset($temp2[2]) === FALSE OR $temp2[2] == FALSE) $temp2[2] = 0;
					if (isset($temp2[1]) === FALSE OR $temp2[1] == FALSE) $temp2[1] = 0;

					$metadata[$temp2[0]] = array('width' => $temp2[1], 'height'=>$temp2[2], 'size'=>$temp2[3]);
				}
			}
		}

		// Extension
    	$extension = '.' . substr( strrchr($filename, '.'), 1);

		// Grab Fields Settings
		if ($field_id == false) exit('Missing Field_ID');

		$settings = $this->EE->image_helper->grabFieldSettings($field_id);

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CI_Location_'.$location_type;

		// Load Settings
		if (isset($settings['locations'][$location_type]) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:location_settings_failure');
			exit( $this->EE->image_helper->generate_json($o) );
		}

		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Image_Location') == FALSE) require PATH_THIRD.'channel_images/locations/image_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

			if (file_exists($location_file) == FALSE)
			{
				$o['body'] = $this->EE->lang->line('ci:location_load_failure');
				exit( $this->EE->image_helper->generate_json($o) );
			}

			require $location_file;
		}

		// Init
		$LOC = new $location_class($location_settings);

		$temp_dir = $this->EE->channel_images->cache_path.'channel_images/field_'.$field_id.'/'.$akey.'/';

		// Remove the other images
		@unlink($temp_dir.'SCALED_' . $filename);
		@unlink($temp_dir.'BW_' . $filename);
		@unlink($temp_dir.'BW_SCALED_' . $filename);

		// Load the API
		if (class_exists('Channel_Images_API') != TRUE) include 'api.channel_images.php';
		$API = new Channel_Images_API();

		// -----------------------------------------
		// Regenerate All sizes?
		// -----------------------------------------
		if ($size == 'ORIGINAL' && $regen_sizes == 'yes')
		{
			$API->run_actions($filename, $field_id, $temp_dir);
		}

		if ($size != 'ORIGINAL')
		{
			if (isset($this->EE->channel_images->actions) === FALSE)
			{
				$this->EE->channel_images->actions = $this->EE->image_helper->get_actions();
			}

			foreach ($settings['action_groups'] as $group)
			{
				$size_name = $group['group_name'];
				if ($size_name != $size) continue;

				$size_filename = str_replace($extension, "__{$size_name}{$extension}", $filename);

				// Make a copy of the file
				@copy($temp_dir.$filename, $temp_dir.$size_filename);
				@chmod($temp_dir.$size_filename, 0777);

				// -----------------------------------------
				// Loop over all Actions and RUN! OMG!
				// -----------------------------------------
				foreach($group['actions'] as $action_name => $action_settings)
				{
					// RUN!
					$this->EE->channel_images->actions[$action_name]->settings = $action_settings;
					$this->EE->channel_images->actions[$action_name]->settings['field_settings'] = $settings;
					$res = $this->EE->channel_images->actions[$action_name]->run($temp_dir.$size_filename, $temp_dir);

					if ($res !== TRUE)
					{
						@unlink($temp_dir.$size_filename);
						return FALSE;
					}
				}

				if (is_resource($this->EE->channel_images->image) == TRUE) imagedestroy($this->EE->channel_images->image);


				if ($image_id > 0) {
					// Parse Image Size
				    $imginfo = @getimagesize($temp_dir.$size_filename);
				    $filesize = @filesize($temp_dir.$size_filename);

					$metadata[ strtolower($size_name) ] = array('width' => @$imginfo[0], 'height' => @$imginfo[1], 'size' => $filesize);
				}


				// Remove the original image
				@unlink($temp_dir.$filename);
			}
		}

		if ($image_id > 0)
		{
			$API->upload_images($entry_id, $field_id, $temp_dir);
		}
		else
		{
			// Loop over all files
			$tempfiles = @scandir($temp_dir);

			if (is_array($tempfiles) == TRUE)
			{
				foreach ($tempfiles as $tempfile)
				{
					if ($tempfile == '.' OR $tempfile == '..') continue;

					$file	= $temp_dir . '/' . $tempfile;

					//$res = $LOC->upload_file($file, $tempfile, $entry_id);
					copy($file, $this->EE->channel_images->cache_path.'channel_images/field_'.$field_id.'/'.$key.'/'.$tempfile);
					@unlink($file);
				}
			}

			@rmdir($temp_dir);
		}

		if ($image_id > 0)
		{
			// -----------------------------------------
			// Parse Size Metadata!
			// -----------------------------------------
			$mt = '';
			foreach($settings['action_groups'] as $group)
			{
				$name = strtolower($group['group_name']);

				// sometimes it's just not found, odd
				if (isset($metadata[$name]) === false) continue;

				$mt .= $name.'|' . implode('|', $metadata[$name]) . '/';
			}

			// -----------------------------------------
			// Update Image
			// -----------------------------------------
			$this->EE->db->set('sizes_metadata', $mt);
			$this->EE->db->where('image_id', $image_id);
			$this->EE->db->update('exp_channel_images');
		}

		@unlink($temp_dir.$filename);
		exit();
	}

	// ********************************************************************************* //



	public function import_matrix_images()
	{
		$o = array('success' => 'no', 'body' => '');

		$this->EE->load->helper('url');

		$entry_id = $this->EE->input->get_post('entry_id');

		// -----------------------------------------
		// Increase all types of limits!
		// -----------------------------------------
		@set_time_limit(0);

		$conf = $this->EE->config->item('channel_images');
		if (is_array($conf) === false) $conf = array();

		if (isset($conf['infinite_memory']) === FALSE || $conf['infinite_memory'] == 'yes')
		{
			@ini_set('memory_limit', '64M');
			@ini_set('memory_limit', '96M');
			@ini_set('memory_limit', '128M');
			@ini_set('memory_limit', '160M');
			@ini_set('memory_limit', '192M');
			@ini_set('memory_limit', '256M');
			@ini_set('memory_limit', '320M');
			@ini_set('memory_limit', '512M');
		}

		// -----------------------------------------
		// Grab our Field Settings
		// -----------------------------------------
		$field_id = $_POST['field']['field_id'];
		$field_type = $_POST['field']['type'];
		$ci_field = $_POST['field']['ci_field'];
		$channel_id = $_POST['field']['channel_id'];
		$settings = $this->EE->image_helper->grabFieldSettings($ci_field);


		if ($field_type == 'matrix') {
			// -----------------------------------------
			// Find our image field!
			// -----------------------------------------
			if (array_search('image', $_POST['field']['fieldmap']) == FALSE)
			{
				$o['body'] = 'No Image Field Mapping found!';
				exit( $this->EE->image_helper->generate_json($o) );
			}

			// -----------------------------------------
			// Gather the usable cols
			// -----------------------------------------
			$cols = array();
			$col_select = '';

			foreach ($_POST['field']['fieldmap'] as $col_id => $map)
			{
				if ($map == FALSE) continue;

				$cols[$col_id] = $map;
				$col_select .= "col_id_{$col_id}, ";
			}

			// -----------------------------------------
			// Which Col was our image?
			// -----------------------------------------
			$image_col = array_search('image', $cols);
			unset($cols[$image_col]);

			// -----------------------------------------
			// Grab all Col Data
			// -----------------------------------------
			$query = $this->EE->db->select('entry_id, '.$col_select)->from('exp_matrix_data')->where('field_id', $_POST['field']['field_id'])->where('entry_id', $entry_id)->get();

			if ($query->num_rows() == 0)
			{
				$o['body'] = 'No Matrix Data Found!';
				exit( $this->EE->image_helper->generate_json($o) );
			}

			// -----------------------------------------
			// Create our Final Data Array
			// -----------------------------------------
			$matrixData = array();

			foreach ($query->result_array() as $row)
			{
				$entry_id = $row['entry_id'];
				unset($row['entry_id']);

				$matrixData[$entry_id][] = $row;
			}

			$query->free_result(); unset($query);
		}

		if ($field_type == 'file') {
			$query = $this->EE->db->select('*')->from('exp_channel_data')->where('entry_id', $entry_id)->get();

			$field_data = $query->row('field_id_'.$field_id);
		}



		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CI_Location_'.$location_type;
		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Image_Location') == FALSE) require PATH_THIRD.'channel_images/locations/image_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

			require $location_file;
		}

		// Init!
		$LOC = new $location_class($location_settings);

		// -----------------------------------------
		// Load Actions :O
		// -----------------------------------------
		$actions = &$this->EE->image_helper->get_actions();



		// -----------------------------------------
		// Create file dir array
		// -----------------------------------------
		$file_dirs = array();
		$temp = $this->EE->image_helper->get_upload_preferences();

		foreach ($temp as $val)
		{
			$file_dirs["{filedir_{$val['id']}}"] = $val['server_path'];
		}

		$file_dirs_search = array_keys($file_dirs);
		$file_dirs_replace = array_values($file_dirs);

		$images = array();

		if ($field_type == 'matrix') {
			// -----------------------------------------
			// Loop over all entries and BEGIN!
			// -----------------------------------------
			foreach($matrixData as $entry_id => $rows)
			{


				// Loop over all rows in the entry!
				foreach ($rows as $count => $row)
				{
					// -----------------------------------------
					// Create a Temp image array
					// -----------------------------------------
					$img_path = str_replace($file_dirs_search, $file_dirs_replace, $row['col_id_'.$image_col]);

					if (file_exists($img_path) == FALSE) {
						exit($img_path);
						continue;
					}

					$img = array();
					$img['path'] = $img_path;

					// -----------------------------------------
					// Loop through all columns and map
					// -----------------------------------------
					foreach($cols as $col_id => $map)
					{
						if ($map == 'image') continue;

						if (isset($row['col_id_'.$col_id]) === TRUE) $img['map'][$map] = $row['col_id_'.$col_id];
					}

					$images[] = $img;
				}
			}
		}

		if ($field_type == 'file') {
			$img_path = str_replace($file_dirs_search, $file_dirs_replace, $field_data);

			if (file_exists($img_path) == FALSE) {
				exit($img_path);
				continue;
			}

			$img = array();
			$img['path'] = $img_path;
			$images[] = $img;
		}

		// Create the DIR!
		$LOC->create_dir($entry_id);

		// -----------------------------------------
		// Temp Dir to run Actions
		// -----------------------------------------
		$temp_dir = $this->EE->channel_images->cache_path.'channel_images/'.$this->EE->localize->now.'-'.$entry_id.'/';

		if (@is_dir($temp_dir) === FALSE)
		{
			@mkdir($temp_dir, 0777, true);
			@chmod($temp_dir, 0777);
		}

		foreach ($images as $count => $img) {
			$image = array();
			$image['site_id']	= $this->site_id;
			$image['field_id'] = $ci_field;
			$image['image_order'] = $count;
			$image['member_id'] = $this->EE->session->userdata['member_id'];
			$image['entry_id'] = $entry_id;
			$image['channel_id'] = $channel_id;
			$image['filename'] = basename($img['path']);
			$image['extension'] = end(explode('.', $image['filename']));
			$image['upload_date'] = $this->EE->localize->now;
			$image['filesize'] = @filesize($img['path']);
			$image['title'] = 'Untitled';

			// Mime type
			$filemime = 'image/jpeg';
			if ($image['extension'] == 'png') $filemime = 'image/png';
			elseif ($image['extension'] == 'gif') $filemime = 'image/gif';
			$image['mime'] = $filemime;

			if (isset($img['map']) == false) {
				$img['map'] = array();
			}

			// -----------------------------------------
			// Loop through all columns and map
			// -----------------------------------------
			foreach($img['map'] as $key => $val)
			{
				if (isset($row['col_id_'.$col_id]) === TRUE) $image[$key] = $val;
			}

			// -----------------------------------------
			// Copy file to temp dir
			// -----------------------------------------
			copy($img['path'], $temp_dir.$image['filename']);

			// -----------------------------------------
			// Loop over all action groups!
			// -----------------------------------------
			foreach ($settings['action_groups'] as $group)
			{
				$size_name = $group['group_name'];
				$size_filename = str_replace('.'.$image['extension'], "__{$size_name}.{$image['extension']}", $image['filename']);

				// Make a copy of the file
				@copy($temp_dir.$image['filename'], $temp_dir.$size_filename);
				@chmod($temp_dir.$size_filename, 0777);

				// -----------------------------------------
				// Loop over all Actions and RUN! OMG!
				// -----------------------------------------
				foreach($group['actions'] as $action_name => $action_settings)
				{
					// RUN!
					$actions[$action_name]->settings = $action_settings;
					$actions[$action_name]->settings['field_settings'] = $settings;
					$res = $actions[$action_name]->run($temp_dir.$size_filename, $temp_dir);

				}

				if (is_resource($this->EE->channel_images->image) == TRUE) imagedestroy($this->EE->channel_images->image);
			}

			// -----------------------------------------
			// Keep Original Image?
			// -----------------------------------------
			if (isset($settings['keep_original']) == TRUE && $settings['keep_original'] == 'no')
			{
				@unlink($temp_dir.$image['filename']);
			}

			// -----------------------------------------
			// Upload all Images!
			// -----------------------------------------
			$metadata = array();
			$tempfiles = @scandir($temp_dir);

			if (is_array($tempfiles) == TRUE)
			{
				foreach ($tempfiles as $tempfile)
				{
					if ($tempfile == '.' OR $tempfile == '..') continue;

					$file	= $temp_dir . '/' . $tempfile;

					$res = $LOC->upload_file($file, $tempfile, $entry_id);

					if ($res == FALSE)
					{

					}

					// Parse Image Size
					$imginfo = @getimagesize($file);

					// Metadata!
					$metadata[$tempfile] = array('width' => @$imginfo[0], 'height' => @$imginfo[1], 'size' => @filesize($file));

					@unlink($file);
				}
			}

			@unlink($temp_dir);


			$image['width'] = isset($metadata[$image['filename']]['width']) ? $metadata[$image['filename']]['width'] : 0;
			$image['height'] = isset($metadata[$image['filename']]['height']) ? $metadata[$image['filename']]['height'] : 0;
			$image['filesize'] = isset($metadata[$image['filename']]['size']) ? $metadata[$image['filename']]['size'] : 0;

			// -----------------------------------------
			// Parse Size Metadata!
			// -----------------------------------------
			$mt = '';
			foreach($settings['action_groups'] as $group)
			{
				$name = strtolower($group['group_name']);
				$size_filename = str_replace('.'.$image['extension'], "__{$name}.{$image['extension']}", $image['filename']);

				$mt .= $name.'|' . implode('|', $metadata[$size_filename]) . '/';
			}

			// Check URL Title
			if (isset($image['url_title']) == FALSE OR $image['url_title'] == FALSE)
			{
				$image['url_title'] = url_title(trim(strtolower($image['title'])));
			}

			$image['sizes_metadata'] = $mt;

			// -----------------------------------------
			// New File
			// -----------------------------------------
			$this->EE->db->insert('exp_channel_images', $image);
		}

		$o['success'] = 'yes';
		exit( $this->EE->image_helper->generate_json($o) );
	}

	// ********************************************************************************* //

	private function ascii_string($string)
	{
		$string = strtr(utf8_decode($string),
           utf8_decode(	'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
           				'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
		return $string;
	}

	// ********************************************************************************* //

	function getXmpData($filename, $chunkSize)
	{
	    if (!is_int($chunkSize)) {
	        throw new RuntimeException('Expected integer value for argument #2 (chunk_size)');
	    }

	    if (($file_pointer = fopen($filename, 'r')) === FALSE) {
	        throw new RuntimeException('Could not open file for reading');
	    }

	    $startTag = '<x:xmpmeta';
	    $endTag = '</x:xmpmeta>';
	    $buffer = NULL;
	    $hasXmp = FALSE;

	    while (($chunk = fread($file_pointer, $chunkSize)) !== FALSE) {

	        if ($chunk === "") {
	            break;
	        }

	        $buffer .= $chunk;
	        $startPosition = strpos($buffer, $startTag);
	        $endPosition = strpos($buffer, $endTag);

	        if ($startPosition !== FALSE && $endPosition !== FALSE) {
	            $buffer = substr($buffer, $startPosition, $endPosition - $startPosition + 12);
	            $hasXmp = TRUE;
	            break;
	        } elseif ($startPosition !== FALSE) {
	            $buffer = substr($buffer, $startPosition);
	            $hasXmp = TRUE;
	        } elseif (strlen($buffer) > (strlen($startTag) * 2)) {
	            $buffer = substr($buffer, strlen($startTag));
	        }
	    }

	    fclose($file_pointer);
	    return ($hasXmp) ? $buffer : NULL;
	}

	// ********************************************************************************* //

	public function display_replace_image_ui()
	{
		$this->EE->load->helper('form');

		$data=array();
		$data['ajax_url'] = $this->EE->image_helper->get_router_url();
		$data['image_id'] = $this->EE->input->get_post('image_id');

		exit($this->EE->load->view('replace_image_ui', $data, TRUE));
	}

	// ********************************************************************************* //

	public function get_image_details()
	{
		$out = array('success' => 'yes');

		$image_id = $this->EE->input->get_post('id');

		$query = $this->EE->db->select('*')->from('exp_channel_images')->where('image_id', $image_id)->get();
		$image = $query->row();
		$out['image'] = $query->row_array();

		$query = $this->EE->db->select('title')->from('exp_channel_titles')->where('entry_id', $image->entry_id)->get();
		if ($query->num_rows() == 0) $out['entry'] = 'NOT_FOUND';
		else $out['entry'] = $query->row('title');

		$query = $this->EE->db->select('channel_title')->from('exp_channels')->where('channel_id', $image->channel_id)->get();
		if ($query->num_rows() == 0) $out['channel'] = 'NOT_FOUND';
		else $out['channel'] = $query->row('channel_title');

		$query = $this->EE->db->select('field_label')->from('exp_channel_fields')->where('field_id', $image->field_id)->get();
		if ($query->num_rows() == 0) $out['field'] = 'NOT_FOUND';
		else $out['field'] = $query->row('field_label');

		exit($this->EE->image_helper->generate_json($out));
	}

	// ********************************************************************************* //

	public function load_batch_images()
	{
		$out = array();

		$this->EE->db->select('image_id, field_id');
		$this->EE->db->from('exp_channel_images');
		$this->EE->db->where('link_image_id', 0);
		$this->EE->db->where('site_id', $this->EE->input->post('site_id'));

		if (isset($_POST['channels']) === true) {
			$this->EE->db->where_in('channel_id', $_POST['channels']);
		}

		if (isset($_POST['fields']) === true) {
			$this->EE->db->where_in('field_id', $_POST['fields']);
		}

		if (isset($_POST['entry_id']) === true) {
			$temp = trim($_POST['entry_id']);
			$temp = explode(',', $temp);

			$ids = array();
			foreach ($temp as $id) {
				$id = trim($id);
				if ($id != false) $ids[] = $id;
			}

			if (empty($ids) == false) {
				$this->EE->db->where_in('entry_id', $ids);
			}
		}

		if (isset($_POST['offset']) === true) {
			$offset = trim($_POST['offset']);

			if (is_numeric($offset)) {
				$this->EE->db->limit(99999999, $offset);
			}
		}

		$this->EE->db->order_by('image_id', 'ASC');
		$query = $this->EE->db->get();

		//$this->EE->firephp->log($this->EE->db->queries);

		$out['ids'] = array();
		$out['field_ids'] = array();

		foreach ($query->result() as $row) {
			$out['ids'][] = array('id' => $row->image_id);
			$out['field_ids'][$row->field_id] = $row->field_id;
		}

		$out['field_ids'] = array_keys($out['field_ids']);

		exit($this->EE->image_helper->generate_json($out));
	}

	// ********************************************************************************* //

	public function get_image_sizes()
	{
		$out = array('fields' => array());

		if ($this->EE->input->post('field_ids') == false) {
			exit($this->EE->image_helper->generate_json($out));
		}

		$ids = explode(',', $this->EE->input->post('field_ids'));

		foreach ($ids as $key => $id) {
			if ($id == false) continue;
			$sizes = array();

			$this->EE->db->select('cf.field_id, cf.field_settings, cf.field_label, fg.group_name');
			$this->EE->db->from('exp_channel_fields cf');
			$this->EE->db->where('cf.field_id', $id);
			$this->EE->db->join('exp_field_groups fg', 'fg.group_id = cf.group_id', 'left');
			$query = $this->EE->db->get();

			$settings = @unserialize(base64_decode($query->row('field_settings')));
			if (isset($settings['channel_images']['action_groups']) == false) continue;

			foreach ($settings['channel_images']['action_groups'] as $group) {
				$sizes[] = $group['group_name'];
			}

			$out['fields'][] = array(
				'group' => $query->row('group_name'),
				'field' => $query->row('field_label'),
				'field_id' => $query->row('field_id'),
				'sizes' => $sizes,
			);
		}

		exit($this->EE->image_helper->generate_json($out));
	}

	// ********************************************************************************* //

	public function exec_batch()
	{
		$image_id = $this->EE->input->get_post('id');
		$action = $this->EE->input->get_post('action');

		@set_time_limit(0);

		$conf = $this->EE->config->item('channel_images');
		if (is_array($conf) === false) $conf = array();

		if (isset($conf['infinite_memory']) === FALSE || $conf['infinite_memory'] == 'yes')
		{
			@ini_set('memory_limit', '64M');
			@ini_set('memory_limit', '96M');
			@ini_set('memory_limit', '128M');
			@ini_set('memory_limit', '160M');
			@ini_set('memory_limit', '192M');
		}

		error_reporting(E_ALL);
		@ini_set('display_errors', 1);


		if ($action == 'regen') {
			$this->regenerate_image_size($image_id);
		}

		if ($action == 'transfer') {
			$this->transer_dir($image_id);
		}
	}

	// ********************************************************************************* //

	public function regenerate_image_size($image_id=0)
	{
		$o = array('success' => 'no', 'body' => '');

		if ($image_id == FALSE)
		{
			$o['body'] = 'MISSING IMAGE ID';
			exit( $this->EE->image_helper->generate_json($o) );
		}

		// Grab image info
		$query = $this->EE->db->select('field_id, entry_id, filename, extension')->from('exp_channel_images')->where('image_id', $image_id)->limit(1)->get();
		$field_id = $query->row('field_id');

		// Grab settings
		$settings = $this->EE->image_helper->grabFieldSettings($field_id);

		$filename = $query->row('filename');
    	$extension = '.' . substr( strrchr($filename, '.'), 1);
		$entry_id = $query->row('entry_id');

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CI_Location_'.$location_type;

		// Load Settings
		if (isset($settings['locations'][$location_type]) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:location_settings_failure');
			exit( $this->EE->image_helper->generate_json($o) );
		}

		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Image_Location') == FALSE) require PATH_THIRD.'channel_images/locations/image_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

			if (file_exists($location_file) == FALSE)
			{
				$o['body'] = $this->EE->lang->line('ci:location_load_failure');
				exit( $this->EE->image_helper->generate_json($o) );
			}

			require $location_file;
		}

		// Init
		$LOC = new $location_class($location_settings);

		// Temp Dir
		$temp_dir = $this->EE->channel_images->cache_path.'channel_images/';

		// -----------------------------------------
		// Copy Image to temp location
		// -----------------------------------------
		$response = $LOC->download_file($entry_id, $filename, $temp_dir);
		if ($response !== TRUE) exit($response);

		// -----------------------------------------
		// Load Actions :O
		// -----------------------------------------
		$actions = &$this->EE->image_helper->get_actions();

		$limit_sizes = array();
		if (isset($_POST['sizes'][$field_id]) === true) {
			$limit_sizes = $_POST['sizes'][$field_id];
		}

		// -----------------------------------------
		// Loop over all action groups!
		// -----------------------------------------
		$metadata = array();
		foreach ($settings['action_groups'] as $group)
		{
			$size_name = $group['group_name'];
			$size_filename = str_replace($extension, "__{$size_name}{$extension}", $filename);

			if (!in_array($size_name, $limit_sizes) && $limit_sizes != false) {
				$LOC->download_file($entry_id, $size_filename, $temp_dir);
			} else {
				// Make a copy of the file
				@copy($temp_dir.$filename, $temp_dir.$size_filename);
				@chmod($temp_dir.$size_filename, 0777);

				// -----------------------------------------
				// Loop over all Actions and RUN! OMG!
				// -----------------------------------------
				foreach($group['actions'] as $action_name => $action_settings)
				{
					// RUN!
					$actions[$action_name]->settings = $action_settings;
					$actions[$action_name]->settings['field_settings'] = $settings;
					$res = $actions[$action_name]->run($temp_dir.$size_filename, $temp_dir);

					if ($res !== TRUE)
					{
						@unlink($temp_dir.$size_filename);
						$o['body'] = 'ACTION ERROR: ' . $res;
		   				exit( $this->EE->image_helper->generate_json($o) );
					}
				}

				if (is_resource($this->EE->channel_images->image) == TRUE) imagedestroy($this->EE->channel_images->image);
			}

			// Parse Image Size
		    $imginfo = @getimagesize($temp_dir.$size_filename);
		    $filesize = @filesize($temp_dir.$size_filename);

			$metadata[$size_name] = array('width' => @$imginfo[0], 'height' => @$imginfo[1], 'size' => $filesize);

			// -----------------------------------------
			// Upload the file back!
			// -----------------------------------------
			$res = $LOC->upload_file($temp_dir.$size_filename, $size_filename, $entry_id);

	    	if ($res !== TRUE)
	    	{
	    		$o['body'] = $res;
				exit( $this->EE->image_helper->generate_json($o) );
	    	}

	    	// Delete
	    	@unlink($temp_dir.$size_filename);
		}

		// -----------------------------------------
		// Parse Size Metadata!
		// -----------------------------------------
		$mt = '';
		foreach($settings['action_groups'] as $group)
		{
			$name = strtolower($group['group_name']);
			$mt .= $name.'|' . implode('|', $metadata[$name]) . '/';
		}

		// -----------------------------------------
		// Parse Original Image Info
		// -----------------------------------------
		$imginfo = @getimagesize($temp_dir.$filename);
		$filesize = @filesize($temp_dir.$filename);
		$width = @$imginfo[0];
		$height = @$imginfo[1];

		// -----------------------------------------
		// IPTC
		// -----------------------------------------
		$iptc = array();
		if ($settings['parse_iptc'] == 'yes')
		{
			getimagesize($temp_dir.$filename, $info);

			if (isset($info['APP13']))
			{
			    $iptc = iptcparse($info['APP13']);
			}
		}


		// -----------------------------------------
		// EXIF
		// -----------------------------------------
		$exif = array();
		if ($settings['parse_exif'] == 'yes')
		{
			if (function_exists('exif_read_data') === true)
			{
	      		$exif = @read_exif_data($temp_dir.$filename);
			}
		}

		// -----------------------------------------
		// XMP
		// -----------------------------------------
		$xmp = '';
		if ($settings['parse_xmp'] == 'yes')
		{
			$xmp = $this->getXmpData($temp_dir.$filename, 102400);
		}

		// -----------------------------------------
		// Update Image
		// -----------------------------------------
		$this->EE->db->set('sizes_metadata', $mt);
		$this->EE->db->set('filesize', $filesize);
		$this->EE->db->set('width', $width);
		$this->EE->db->set('height', $height);
		$this->EE->db->set('extension', trim($extension, '.') );
		$this->EE->db->set('iptc', base64_encode(serialize($iptc)));
		$this->EE->db->set('exif', base64_encode(serialize($exif)));
		$this->EE->db->set('xmp', base64_encode($xmp));
		$this->EE->db->where('image_id', $image_id);
		$this->EE->db->update('exp_channel_images');

		// Delete Temp File
		@unlink($temp_dir.$filename);

		$o['success'] = 'yes';

		exit( $this->EE->image_helper->generate_json($o) );
	}

	// ********************************************************************************* //

	public function transer_dir($image_id)
	{
		$this->EE->load->helper('file');

		// Grab image info
		$query = $this->EE->db->select('field_id, entry_id, filename, extension')->from('exp_channel_images')->where('image_id', $image_id)->limit(1)->get();
		$field_id = $query->row('field_id');

		// Grab settings
		$settings = $this->EE->image_helper->grabFieldSettings($field_id);

		$filename = $query->row('filename');
    	$extension = '.' . substr( strrchr($filename, '.'), 1);
		$entry_id = $query->row('entry_id');

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CI_Location_'.$location_type;

		// Load Settings
		if (isset($settings['locations'][$location_type]) == FALSE)
		{
			$o['body'] = $this->EE->lang->line('ci:location_settings_failure');
			exit( $this->EE->image_helper->generate_json($o) );
		}

		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Image_Location') == FALSE) require PATH_THIRD.'channel_images/locations/image_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

			if (file_exists($location_file) == FALSE)
			{
				$o['body'] = $this->EE->lang->line('ci:location_load_failure');
				exit( $this->EE->image_helper->generate_json($o) );
			}

			require $location_file;
		}

		// Init
		$LOC = new $location_class($location_settings);

		// Temp Dir
		@mkdir($this->EE->channel_images->cache_path.'channel_images/', 0777);
		@mkdir($this->EE->channel_images->cache_path.'channel_images/transfer/', 0777);
		@mkdir($this->EE->channel_images->cache_path.'channel_images/transfer/'.$image_id.'/', 0777);
		$temp_dir = $this->EE->channel_images->cache_path.'channel_images/transfer/'.$image_id.'/';

		// -----------------------------------------
		// Copy Image to temp location
		// -----------------------------------------
		$LOC->download_file($entry_id, $filename, $temp_dir);
		//if ($response !== TRUE) exit($response);

		foreach ($settings['action_groups'] as $group)
		{
			$size_name = $group['group_name'];
			$size_filename = str_replace($extension, "__{$size_name}{$extension}", $filename);

			$LOC->download_file($entry_id, $size_filename, $temp_dir);
		}

		// -----------------------------------------
		// Init Amazon
		// -----------------------------------------
		if ($_POST['transfer']['to'] == 's3') {
			if (class_exists('CFRuntime') == FALSE)
			{
				// Include the SDK
				require_once PATH_THIRD.'channel_images/locations/s3/sdk/sdk.class.php';
			}

			// Just to be sure
			if (class_exists('AmazonS3') == FALSE)
			{
				include PATH_THIRD.'channel_images/locations/s3/sdk/services/s3.class.php';
			}

			// Instantiate the AmazonS3 class
			$S3 = new AmazonS3(array('key'=>trim($_POST['s3']['key']), 'secret'=>trim($_POST['s3']['secret_key'])));
			$S3->ssl_verification = FALSE;

			// Init Configs
			$temp = $this->EE->config->item('ci_s3_storage');
			$s3_storage = constant('AmazonS3::' . $temp[$_POST['s3']['storage']]);

			$temp = $this->EE->config->item('ci_s3_acl');
			$s3_acl = constant('AmazonS3::' . $temp[$_POST['s3']['acl']]);

			$s3_directory = trim($_POST['s3']['directory']);
			$s3_bucket = $_POST['s3']['bucket'];

			$s3_subdir = '';
			if ($s3_directory) {
				$s3_subdir = $s3_directory . '/';
			}

			$s3_headers = $this->EE->config->item('ci_s3_headers');

			// Test it
			$resp = $S3->get_bucket_headers($s3_bucket);

			if (!$resp->isOK()) {
				if (isset($resp->body->Message)) {
					exit('ERROR_S3: ' . $resp->body->Message);
				} else {
					exit('ERROR_S3: Bucket error');
				}
			}
		}

		// -----------------------------------------
		// Init Cloudfiles
		// -----------------------------------------
		else {
			// Include the SDK
			if (class_exists('CF_Authentication') == FALSE)
			{
				require_once PATH_THIRD.'channel_images/locations/cloudfiles/sdk/cloudfiles.php';
			}

			// Which Region?
			if ($_POST['cloudfiles']['region'] == 'uk') $_POST['cloudfiles']['region'] = constant('UK_AUTHURL');
			else $_POST['cloudfiles']['region'] = constant('US_AUTHURL');

			// Instantiate the Cloudfiles class
			$CF_AUTH = new CF_Authentication($_POST['cloudfiles']['username'], $_POST['cloudfiles']['api'], NULL, $_POST['cloudfiles']['region']);

			try
			{
				$CF_AUTH->ssl_use_cabundle();
				$CF_AUTH->authenticate();
			}
			catch (AuthenticationException $e)
			{
				exit('ERROR_CLOUDFILES:' . $e->getMessage());
			}

        	$CF_CONN = new CF_Connection($CF_AUTH);
        	$CF_CONN->ssl_use_cabundle();

        	$CF_CONT = $CF_CONN->get_container($_POST['cloudfiles']['container']);
		}



		// -----------------------------------------
		// Loop over all dirs
		// -----------------------------------------
		$files = scandir($temp_dir);

		foreach ($files as $file) {
			$full_path = $temp_dir.$file;
			if (is_file($full_path) == false) continue;
			$extension = substr( strrchr($file, '.'), 1);

			// Mime type
			if ($extension == 'jpg') $filemime = 'image/jpeg';
			elseif ($extension == 'jpeg') $filemime = 'image/jpeg';
			elseif ($extension == 'png') $filemime = 'image/png';
			elseif ($extension == 'gif') $filemime = 'image/gif';
			else continue;

			if (isset($S3) == true) {
				$upload_arr = array();
				$upload_arr['fileUpload'] = $full_path;
				$upload_arr['contentType'] = $filemime;
				$upload_arr['acl'] = $s3_acl;
				$upload_arr['storage'] = $s3_storage;
				$upload_arr['headers'] = array();

				if ($s3_headers != FALSE && is_array($s3_headers) === TRUE) {
					$upload_arr['headers'] = $s3_headers;
				}

				$response = $S3->create_object($s3_bucket, $s3_subdir.$entry_id.'/'.$file, $upload_arr);

				// Success?
				if (!$response->isOK()) {
					exit((string) $response->body->Message);
				}
			} else {

				$OBJECT = $CF_CONT->create_object($entry_id.'/'.$file);
				$OBJECT->content_type = $filemime;

				try {
					$OBJECT->load_from_filename($full_path);
				} catch (Exception $e) {
					exit($e->getMessage());
				}
			}

			//@unlink($temp_dir.$file);
		}

		@delete_files($temp_dir, true);
		@rmdir($temp_dir);

		$o = array('success' => 'yes');
		exit( $this->EE->image_helper->generate_json($o) );
	}

	// ********************************************************************************* //

	private function adjustPicOrientation($full_filename){
        $exif = @exif_read_data($full_filename);
        if($exif && isset($exif['Orientation'])) {
            $orientation = $exif['Orientation'];
            if($orientation != 1){
                $img = imagecreatefromjpeg($full_filename);

                $mirror = false;
                $deg    = 0;

                switch ($orientation) {
                    case 2:
                        $mirror = true;
                        break;
                    case 3:
                        $deg = 180;
                        break;
                    case 4:
                        $deg = 180;
                        $mirror = true;
                        break;
                    case 5:
                        $deg = 270;
                        $mirror = true;
                        break;
                    case 6:
                        $deg = 270;
                        break;
                    case 7:
                        $deg = 90;
                        $mirror = true;
                        break;
                    case 8:
                        $deg = 90;
                        break;
                }
                if ($deg) $img = imagerotate($img, $deg, 0);
                if ($mirror) $img = _mirrorImage($img);
                //$full_filename = str_replace('.jpg', "-O$orientation.jpg",  $full_filename);
                imagejpeg($img, $full_filename, 100);
            }
        }
        return $full_filename;
    }

    // ********************************************************************************* //

} // END CLASS

/* End of file ajax.channel_images.php  */
/* Location: ./system/expressionengine/third_party/channel_images/ajax.channel_images.php */
