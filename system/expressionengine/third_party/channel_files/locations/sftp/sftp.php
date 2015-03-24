<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Channel Files SFTP location
 *
 * @package			DevDemon_ChannelFiles
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com/channel_files/
 */
class CF_Location_sftp extends Cfile_Location
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

		require_once(PATH_THIRD.'channel_files/locations/sftp/phpseclib/Net/SFTP.php');
	}

	// ********************************************************************************* //

	public function create_dir($dir)
	{
		$this->init();

		// SFTP MKDIR
		if ($this->SFTP->chdir($this->lsettings['path'].$dir) === FALSE)
		{
			if ($this->SFTP->mkdir($this->lsettings['path'].$dir) != TRUE)
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

		if ($dir !== FALSE) $dir .= '/';
		else $dir = '';

		$contents = $this->SFTP->nlist($this->lsettings['path'].$dir);

		if ($contents != FALSE)
		{
			foreach ($contents as $file)
			{
				if ($file == '.' OR $file == '..') continue;
				$this->SFTP->delete($this->lsettings['path'].$dir . $file);
			}
		}

		// SFTP RMDIR
		if ($this->SFTP->rmdir($this->lsettings['path'].$dir) != TRUE)
		{
			return FALSE;
		}

		return TRUE;
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
			if ($this->SFTP->size($this->lsettings['path'].$dest_folder.$dest_filename) > 0)
			{
				for ($i=2; $i < 30; $i++)
				{
					if ($this->SFTP->size($this->lsettings['path'].$dest_folder."{$filename_no_ext}_{$i}{$extension}") == FALSE)
					{
						$dest_filename = "{$filename_no_ext}_{$i}{$extension}";
						break;
					}
				}
			}
		}

    	// SFTP UPLOAD
    	if ($this->SFTP->put($this->lsettings['path'].$dest_folder.$dest_filename, $source_file, NET_SFTP_LOCAL_FILE) == FALSE)
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

		// SFTP Download
		if ($this->SFTP->download($this->lsettings['path'].$dir . $filename, $dest_folder.$filename) == FALSE)
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

		// SFTP Download
		if ($this->SFTP->delete($this->lsettings['path'].$dir . $filename) == FALSE)
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
		$o = '';

		define('NET_SFTP_LOGGING', NET_SFTP_LOG_COMPLEX);

		$temp_dir = $this->EE->localize->now.'_dir';
		$temp_file = $this->EE->localize->now.'_file';

		$this->init(FALSE);

		// Check for Safe Mode?
		$safemode = strtolower(@ini_get('safe_mode'));
		if ($safemode == 'on' || $safemode == 'yes' || $safemode == 'true' ||  $safemode == 1)	$o .= "PHP Safe Mode (OFF): <span style='color:red'>Failed</span> <br>";
		else $o .= "PHP Safe Mode (OFF): <span style='color:green'>Passed</span> <br>";

		// SFTP CONNECT
		if ($this->SFTP->login($this->lsettings['username'], $this->lsettings['password']) != FALSE)
		{
			$o .= "SFTP CONNECT: <span style='color:green'>Passed</span> <br>";
		}
		else
		{
			$o .= "SFTP CONNECT: <span style='color:red'>Failed</span> <br>";
			//$o .= 'MESSAGE: ' . $this->DDFTP->error;
			return $o;
		}

		// SFTP MKDIR
		if ($this->SFTP->chdir($this->lsettings['path'].$temp_dir) === FALSE)
		{
			if ($this->SFTP->mkdir($this->lsettings['path'].$temp_dir) == TRUE)
			{
				$o .= "SFTP MKDIR: <span style='color:green'>Passed</span><br>";
			}
			else
			{
				$o .= "SFTP MKDIR: <span style='color:red'>Failed</span><br />";
			}
		}
		else
		{
			$o .= "SFTP MKDIR: <span style='color:green'>Passed</span> (Directory Existed)<br>";
		}

		// SFTP UPLOAD
		if ($this->SFTP->put($this->lsettings['path'].$temp_dir.'/'.$temp_file, PATH_THIRD.'channel_files/locations/sftp/phpseclib/test_file.txt', NET_SFTP_LOCAL_FILE) != FALSE)
		{
			$o .= "SFTP UPLOAD: <span style='color:green'>Passed</span><br>";
		}
		else
		{
			$o .= "SFTP UPLOAD: <span style='color:red'>Failed</span><br />";
		}

		// SFTP RENAME
		if ($this->SFTP->rename($this->lsettings['path'].$temp_dir, $this->lsettings['path'].$temp_dir.'-renamed') != FALSE)
		{
			$o .= "SFTP RENAME (Dir): <span style='color:green'>Passed</span><br>";
			$temp_dir = $temp_dir.'-renamed';
		}
		else
		{
			$o .= "SFTP RENAME (Dir): <span style='color:red'>Failed</span><br />";
		}

		// SFTP DELETE FILE
		if ($this->SFTP->delete($this->lsettings['path'].$temp_dir.'/'.$temp_file) != FALSE)
		{
			$o .= "SFTP DELETE (File): <span style='color:green'>Passed</span><br>";
		}
		else
		{
			$o .= "SFTP DELETE (File): <span style='color:red'>Failed</span><br />";
		}

		// SFTP DELETE DIR
		if ($this->SFTP->rmdir($this->lsettings['path'].$temp_dir) != FALSE)
		{
			$o .= "SFTP DELETE (Dir): <span style='color:green'>Passed</span><br>";
		}
		else
		{
			$o .= "SFTP DELETE (Dir): <span style='color:red'>Failed</span><br>";
		}

		//print_r($this->SFTP->getSFTPLog());

		$o .= "<br /> Even if all tests PASS, uploading can still<br /> fail due Apache/htaccess misconfiguration";

		$this->SFTP->disconnect();

		return $o;
	}

	// ********************************************************************************* //

	private function init($connect=TRUE)
	{

		$this->SFTP = new Net_SFTP($this->lsettings['hostname'], $this->lsettings['port']);

		if ($connect == TRUE)
		{
			if ( !($this->SFTP->bitmap & NET_SSH2_MASK_LOGIN) )
			{
				if ($this->SFTP->login($this->lsettings['username'], $this->lsettings['password']) != TRUE)
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