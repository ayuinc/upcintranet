<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Channel Files FTP location
 *
 * @package			DevDemon_ChannelFiles
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com/channel_files/
 */
class CF_Location_ftp extends Cfile_Location
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

		require_once(PATH_THIRD.'channel_files/locations/ftp/libraries/devdemon_ftp.php');
	}

	// ********************************************************************************* //

	public function create_dir($dir)
	{
		$this->init();

		// FTP MKDIR
		if ($this->DDFTP->changedir($this->lsettings['path'].$dir) === FALSE)
		{
			if ($this->DDFTP->mkdir($this->lsettings['path'].$dir) != TRUE)
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	// ********************************************************************************* //

	public function delete_dir($dir)
	{
		$this->init();

		// FTP Download
		if ($this->DDFTP->delete_dir($this->lsettings['path'].$dir) == FALSE)
		{
			return FALSE;
		}

		return FALSE;
	}

	// ********************************************************************************* //

	public function upload_file($source_file, $dest_filename, $dest_folder, $checkExists=true)
	{
		$out = array('success' => FALSE, 'filename' => '');

		$this->init();

		if ($dest_folder !== FALSE) $dest_folder .= '/';
		else $dest_folder = '';

		$extension = '.' . substr( strrchr($dest_filename, '.'), 1);
		$filename_no_ext = str_replace($extension, '', $dest_filename);

		if ($checkExists) {
			// Does it already exists?
			if ($this->DDFTP->file_size($this->lsettings['path'].$dest_folder.$dest_filename) > 0)
			{
				for ($i=2; $i < 30; $i++)
				{
					if ($this->DDFTP->file_size($this->lsettings['path'].$dest_folder."{$filename_no_ext}_{$i}{$extension}") == FALSE)
					{
						$dest_filename = "{$filename_no_ext}_{$i}{$extension}";
						break;
					}
				}
			}
		}

    	// FTP UPLOAD
    	if ($this->DDFTP->upload($source_file, $this->lsettings['path'].$dest_folder.$dest_filename, 0775) == FALSE)
    	{
    		return FALSE;
    	}

    	$out['success'] = TRUE;
    	$out['filename'] = $dest_filename;
    	return $out;
	}

	// ********************************************************************************* //

	public function download_file($dir, $filename, $dest_folder)
	{
		$this->init();

		if ($dir !== FALSE) $dir .= '/';
		else $dir = '';

		// FTP Download
		if ($this->DDFTP->download($this->lsettings['path'].$dir. $filename, $dest_folder.$filename) == FALSE)
		{
			return FALSE;
		}

		return TRUE;
	}

	// ********************************************************************************* //

	public function delete_file($dir, $filename)
	{
		$this->init();

		if ($dir !== FALSE) $dir .= '/';
		else $dir = '';

		// FTP Download
		if ($this->DDFTP->delete_file($this->lsettings['path'].$dir . $filename) == FALSE)
		{
			return FALSE;
		}

		return FALSE;
	}

	// ********************************************************************************* //

	public function parse_file_url($dir, $filename)
	{
		if ($dir !== FALSE) $dir .= '/';
		else $dir = '';

		return $this->lsettings['url'] . $dir . $filename;
	}

	// ********************************************************************************* //

	public function test_location()
	{
		$this->init(FALSE);

		$o = '';
		$temp_dir = $this->EE->localize->now.'_dir';
		$temp_file = $this->EE->localize->now.'_file';
		$this->DDFTP->debug = TRUE;

		// Check for Safe Mode?
		$safemode = strtolower(@ini_get('safe_mode'));
		if ($safemode == 'on' || $safemode == 'yes' || $safemode == 'true' ||  $safemode == 1)	$o .= "PHP Safe Mode (OFF): <span style='color:red'>Failed</span> <br>";
		else $o .= "PHP Safe Mode (OFF): <span style='color:green'>Passed</span> <br>";

		// FTP CONNECT
		if ($this->DDFTP->connect() === TRUE)
		{
			$o .= "FTP CONNECT: <span style='color:green'>Passed</span> <br>";
		}
		else
		{
			$o .= "FTP CONNECT: <span style='color:red'>Failed</span> <br>";
			$o .= 'MESSAGE: ' . $this->DDFTP->error;
			return $o;
		}

		// FTP MKDIR
		if ($this->DDFTP->changedir($this->lsettings['path'].$temp_dir) === FALSE)
		{
			if ($this->DDFTP->mkdir($this->lsettings['path'].$temp_dir) == TRUE)
			{
				$o .= "FTP MKDIR: <span style='color:green'>Passed</span><br>";
			}
			else
			{
				$o .= "FTP MKDIR: <span style='color:red'>Failed</span>&nbsp;";
			}
		}
		else
		{
			$o .= "FTP MKDIR: <span style='color:green'>Passed</span> (Directory Existed!)<br>";
		}

		// FTP UPLOAD
		if ($this->DDFTP->upload(PATH_THIRD.'channel_files/locations/ftp/libraries/test_file.txt', $this->lsettings['path'].$temp_dir.'/'.$temp_file, 0775) != FALSE)
		{
			$o .= "FTP UPLOAD: <span style='color:green'>Passed</span><br>";
		}
		else
		{
			$o .= "FTP UPLOAD: <span style='color:red'>Failed</span>&nbsp;";
			$o .= "(MESSAGE: {$this->DDFTP->error}) <br />";
		}

		// FTP RENAME
		if ($this->DDFTP->rename($this->lsettings['path'].$temp_dir, $this->lsettings['path'].$temp_dir.'-renamed') != FALSE)
		{
			$o .= "FTP RENAME (Dir): <span style='color:green'>Passed</span><br>";
			$temp_dir = $temp_dir.'-renamed';
		}
		else
		{
			$o .= "FTP RENAME (Dir): <span style='color:red'>Failed</span>&nbsp;";
			$o .= "(MESSAGE: {$this->DDFTP->error}) <br />";
		}

		// FTP DELETE FILE
		if ($this->DDFTP->delete_file($this->lsettings['path'].$temp_dir.'/'.$temp_file) != FALSE)
		{
			$o .= "FTP DELETE (File): <span style='color:green'>Passed</span><br>";
		}
		else
		{
			$o .= "FTP DELETE (File): <span style='color:red'>Failed</span>&nbsp;";
			$o .= "(MESSAGE: {$this->DDFTP->error}) <br />";
		}

		// FTP DELETE DIR
		if ($this->DDFTP->delete_dir($this->lsettings['path'].$temp_dir) != FALSE)
		{
			$o .= "FTP DELETE (Dir): <span style='color:green'>Passed</span><br>";
		}
		else
		{
			$o .= "FTP DELETE (Dir): <span style='color:red'>Failed</span>&nbsp;";
			$o .= "(MESSAGE: {$this->DDFTP->error}) <br />";
		}

		$o .= "<br /> Even if all tests PASS, uploading can still<br /> fail due Apache/htaccess misconfiguration";

		$this->DDFTP->close();

		return $o;
	}

	// ********************************************************************************* //

	private function init($connect=TRUE)
	{
		if ($this->lsettings['passive'] == 'yes') $this->lsettings['passive'] = TRUE;
		else $this->lsettings['passive'] = FALSE;

		if ($this->lsettings['ssl'] == 'yes') $this->lsettings['ssl'] = TRUE;
		else $this->lsettings['ssl'] = FALSE;

		$this->DDFTP = new Devdemon_ftp($this->lsettings);

		// Just in case
		if ($this->lsettings['ssl'] == TRUE) $this->DDFTP->ssl = TRUE;

		if ($connect == TRUE)
		{
			if ( ! is_resource($this->DDFTP->conn_id))
			{
				// FTP CONNECT
				if ($this->DDFTP->connect() != TRUE)
				{
					return FALSE;
				}
			}
		}

	}

	// ********************************************************************************* //
}

/* End of file ftp.php */
/* Location: ./system/expressionengine/third_party/channel_files/locations/ftp/ftp.php */