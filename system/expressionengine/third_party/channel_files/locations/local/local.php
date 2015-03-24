<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Channel Files Local location
 *
 * @package			DevDemon_ChannelFiles
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com/channel_files/
 */
class CF_Location_local extends Cfile_Location
{

	/**
	 * Constructor
	 *
	 * @access public
	 *
	 * Calls the parent constructor
	 */
	public function __construct($settings=array())
	{
		parent::__construct();

		$this->lsettings = $settings;

	}

	// ********************************************************************************* //

	public function create_dir($dir)
	{
		// Did we store a location?
		if (isset($this->lsettings['location']) == FALSE OR $this->lsettings['location'] == FALSE)
		{
			return FALSE;
		}

		$loc = $this->get_location_prefs($this->lsettings['location']);

		// We have a correct location?
		if ($loc == FALSE)
		{
			return FALSE;
		}

		// Mkdir & Chmod
		@mkdir($loc['server_path'] . $dir);
		@chmod($loc['server_path'] . $dir, 0777);

		return TRUE;
	}

	// ********************************************************************************* //

	public function delete_dir($dir)
	{
		$this->EE->load->helper('file');

		// Did we store a location?
		if (isset($this->lsettings['location']) == FALSE OR $this->lsettings['location'] == FALSE)
		{
			return FALSE;
		}

		$loc = $this->get_location_prefs($this->lsettings['location']);

		// We have a correct location?
		if ($loc == FALSE)
		{
			return FALSE;
		}

		// Delete them all!
		@delete_files($loc['server_path'] . $dir, TRUE);
		@rmdir($loc['server_path'] . $dir);

		return TRUE;
	}

	// ********************************************************************************* //

	public function upload_file($source_file, $dest_filename, $dest_folder, $checkExists=true)
	{
		$out = array('success' => FALSE, 'filename' => '');

		$loc = $this->get_location_prefs($this->lsettings['location']);

		if ($dest_folder !== FALSE) $dest_folder .= '/';
		else $dest_folder = '';

		$extension = '.' . substr( strrchr($dest_filename, '.'), 1);
		$filename_no_ext = str_replace($extension, '', $dest_filename);

		if ($checkExists) {
			// Does it already exists?
			if (file_exists($loc['server_path'] . $dest_folder . $dest_filename) == TRUE)
			{
				for ($i=2; $i < 30; $i++)
				{
					if (file_exists($loc['server_path'] . $dest_folder . "{$filename_no_ext}_{$i}{$extension}") == FALSE)
					{
						$dest_filename = "{$filename_no_ext}_{$i}{$extension}";
						break;
					}
				}
			}
		}


		// Move file
		if (copy($source_file, $loc['server_path'] . $dest_folder . $dest_filename) === FALSE)
    	{
    		$o['body'] = $this->EE->lang->line('ci:file_upload_error');
	   		exit( $this->EE->channel_files_helper->generate_json($o) );
    	}
    	else
    	{
    		$out['success'] = TRUE;
    		$out['filename'] = $dest_filename;
    	}

    	return $out;
	}

	// ********************************************************************************* //

	public function download_file($dir, $filename, $dest_folder)
	{
		$loc = $this->get_location_prefs($this->lsettings['location']);

		if ($dir !== FALSE) $dir .= '/';
		else $dir = '';

		copy($loc['server_path'].$dir.$filename, $dest_folder.$filename);
		return TRUE;
	}

	// ********************************************************************************* //

	public function delete_file($dir, $filename)
	{
		$loc = $this->get_location_prefs($this->lsettings['location']);

		if ($dir !== FALSE) $dir .= '/';
		else $dir = '';

		@unlink($loc['server_path'] . $dir . $filename);

		return FALSE;
	}

	// ********************************************************************************* //

	public function parse_file_url($dir, $filename)
	{
		$loc = $this->get_location_prefs($this->lsettings['location']);

		if ($dir !== FALSE) $dir .= '/';
		else $dir = '';

		return $loc['url'] . $dir . $filename;
	}

	// ********************************************************************************* //

	public function test_location()
	{
		// What is our location path?
		$loc = $this->get_location_prefs($this->lsettings['location']);
		$dir = $loc['server_path'];

		$o = '<strong style="color:orange">PATH:</strong> ' . $dir . '<br />';

		// Check for Safe Mode?
		$safemode = strtolower(@ini_get('safe_mode'));
		if ($safemode == 'on' || $safemode == 'yes' || $safemode == 'true' ||  $safemode == 1)	$o .= "PHP Safe Mode (OFF): <span style='color:red'>Failed</span> <br>";
		else $o .= "PHP Safe Mode (OFF): <span style='color:green'>Passed</span> <br>";

		// Is DIR?
		if (is_dir($dir) === TRUE)	$o .= "Is Dir: <span style='color:green'>Passed</span> <br>";
		else $o .= "Is Dir: <span style='color:red'>Failed</span> <br>";

		// Is READABLE?
		if (is_readable($dir) === TRUE) $o .= "Is Readable: <span style='color:green'>Passed</span> <br>";
		else $o .= "Is Readable: Failed<br>";

		// Is WRITABLE
		if (is_writable($dir) === TRUE) $o .= "Is Writable: <span style='color:green'>Passed</span> <br>";
		else $o .= "Is Writable: <span style='color:red'>Failed</span> <br>";

		// CREATE TEST FILE
		$file = uniqid(mt_rand()).'.tmp';
		if (@touch($dir.$file) === TRUE) $o .= "Create Test File: <span style='color:green'>Passed</span> <br>";
		else $o .= "Create Test File: <span style='color:red'>Failed</span> <br>";

		// DELETE TEST FILE
		if (@unlink($dir.$file) === TRUE) $o .= "Delete Test File: <span style='color:green'>Passed</span> <br>";
		else $o .= "Delete Test File: <span style='color:red'>Failed</span> <br>";

		// CREATE TEST DIR
		$tempdir = 'temp_' . $this->EE->localize->now;
		if (@mkdir($dir.$tempdir) === TRUE) $o .= "Create Test DIR: <span style='color:green'>Passed</span> <br>";
		else $o .= "Create Test DIR: <span style='color:red'>Failed</span> <br>";

		// RENAME TEST DIR
		if (@rename($dir.$tempdir, $dir.$tempdir.'temp') === TRUE) $o .= "Rename Test DIR: <span style='color:green'>Passed</span> <br>";
		else $o .= "Rename Test DIR: <span style='color:red'>Failed</span> <br>";

		// DELETE TEST DIR
		if (@rmdir($dir.$tempdir.'temp') === TRUE) $o .= "Delete Test DIR: <span style='color:green'>Passed</span> <br>";
		else $o .= "Delete Test DIR: <span style='color:red'>Failed</span> <br>";

		$o .= "<br /> Even if all tests PASS, uploading can still<br /> fail due Apache/htaccess misconfiguration";

		return $o;
	}

	// ********************************************************************************* //

	/**
	 * Get Upload Prefs
	 *
	 * @param int $location_id
	 * @access public
	 * @return array - Location settings
	 */
	public function get_location_prefs($location_id)
	{
		$location = array();

		if (isset($this->EE->session->cache['upload_prefs'][$location_id]) === FALSE)
		{
			$location = $this->EE->channel_files_helper->get_upload_preferences(1, $location_id, TRUE);
		}
		else
		{
			$location = $this->EE->session->cache['upload_prefs'][$location_id];
		}

		// Relative path?
		if (substr($location['server_path'], 0, 1) != "/")
		{
			// (try) to turn relative path into absolute path.
			if (realpath(FCPATH . SYSDIR . '/' .  $location['server_path']) != NULL)
			{
				$location['server_path'] = realpath(FCPATH . SYSDIR . '/' .  $location['server_path']) . "/";
			}
		}

		// Need last slash!
		if (substr($location['server_path'], -1, 1) != '/')
		{
			$location['server_path'] . '/';
		}

		return $location;
	}

	// ********************************************************************************* //
}

/* End of file local.php */
/* Location: ./system/expressionengine/third_party/channel_files/locations/local/local.php */
