<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /**
 * mithra62 - Securit:ee
 *
 * @package		mithra62:Securitee
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2011, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/securit-ee/
 * @version		1.3.7
 * @filesource 	./system/expressionengine/third_party/securitee/
 */
 
 /**
 * Securit:ee - Security Scanner Library
 *
 * Contains the logic for the Security Scanner
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/libraries/Securitee_scanner.php
 */
class Securitee_scanner
{	
	/**
	 * The latest, stable, version of PHP we know about
	 * @var string
	 */
	private $latest_php_version = '5.4.17';
	
	/**
	 * The URL query parts to access the EE CP sections
	 * @var array
	 */
	public $ee_urls = array(
		
	);
	
	/**
	 * The defaults for the EE config settings
	 * @var array
	 */
	public $_expected_config_scan = array(
		'encryption_key_length' => 32,
		'max_comment_chars' => 1000,
		'comment_submit_interval' => 10,
		'admin_session_type' => 'cs',
		'user_session_type' => 'c',
		'secure_forms' => 'y',
		'deny_duplicate_data' => 'y',
		'allow_username_change' => 'n',
		'allow_username_change' => 'n',
		'allow_multi_logins' => 'n',
		'require_ip_for_login' => 'y',
		'require_ip_for_posting' => 'y',
		'xss_clean_uploads' => 'y',
		'password_lockout' => 'y',
		'password_lockout_interval' => 15,
		'require_secure_passwords' => 'y',
		'allow_dictionary_pw' => 'n',
		'pw_min_len' => 8,
		'enable_throttling' => 'y',
		'max_page_loads' => 20,
		'time_interval' => 5,
		'lockout_time' => 1800,
		'debug' => '0',
		//'template_debugging' => 'n',
		'db_prefix' => 'fdsafdsa',
		'cookie_prefix' => 'fdsafdsafdsa', //giberish because we don't want a blank
		'profile_trigger' => 'fdsafdsafdsa' //giberish because we don't want "member"
	);
	
	/**
	 * The default scan results. Everything is marked as failed and must be proven 
	 * @var array
	 */
	private $_defaults = array(
		'cp_scan' => array(
			'cp_https' => FALSE,
			'cp_outside_root' => FALSE,
			'cp_not_named_system' => FALSE,
			'devotee_monitor_installed' => FALSE,
			'vz_bb_installed' => FALSE,
			'cp_ip_locker' => FALSE,
			'cp_login_alert' => FALSE,
			'file_monitor' => FALSE,
			'admin_not_named_admin' => FALSE
		),
		'php_scan' => array(
			'register_globals' => FALSE,
			'disable_url_fopen' => FALSE,
			'disable_passthru' => FALSE,
			'disable_shell_exec' => FALSE,
			'disable_system' => FALSE,
			'disable_proc_open' => FALSE,
			'disable_popen' => FALSE,
			//'disable_curl_exec' => FALSE,
			//'disable_curl_multi_exec' => FALSE,
			'disable_passthru' => FALSE,
			'disable_show_source' => FALSE,
			'disable_exec' => FALSE,
			'disable_dl' => FALSE,
			'disable_apache_note' => FALSE,
			'disable_apache_setenv' => FALSE,
			'disable_pcntl_exec' => FALSE,
			'disable_proc_close' => FALSE,
			'disable_proc_get_status' => FALSE,
			'disable_proc_terminate' => FALSE,
			'disable_putenv' => FALSE,
			'disable_virtual' => FALSE,
			'disable_expose_php' => FALSE,
			'disable_openlog' => FALSE,
			'disable_proc_nice' => FALSE,
			'disable_syslog' => FALSE,
			'disable_splFileObject' => FALSE,
			'enable_open_basedir' => FALSE,
			'disable_phpinfo' => FALSE,
			'disable_get_loaded_extensions' => FALSE
		),
		'check_config' => array(
			'encrypt_key' => FALSE,
			'encrypt_key_length' => FALSE,
			'deny_duplicate_data' => FALSE,
			//'config_not_writable' => FALSE,
			'allow_username_change' => FALSE,
			'allow_multi_logins' => FALSE,
			'require_ip_for_login' => FALSE,
			'require_ip_for_posting' => FALSE,
			'xss_clean_uploads' => FALSE,
			'password_lockout' => FALSE,
			'password_lockout_interval' => FALSE,
			'require_secure_passwords' => FALSE,
			'allow_dictionary_pw' => FALSE,
			'pw_min_len' => FALSE,
			'enable_throttling' => FALSE,
			'max_page_loads' => FALSE,
			'time_interval' => FALSE,
			'lockout_time' => FALSE,
			'debug' => FALSE,
			//'template_debugging' => FALSE,
			'cookie_prefix' => FALSE,
			'db_prefix' => FALSE,
			'profile_trigger' => FALSE
		
		)
	);
	
	/**
	 * Where the file scanner looks for version control files
	 * @var array
	 */
	public $vcs_scan_locations = array();
	
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->helper('directory');
		$this->settings = $this->EE->securitee_lib->get_settings();
		$this->scan = $this->_defaults;
	}
	
	public function get_scan()
	{
		$this->cp_scan();
		$this->version_control_scan();
		$this->check_config();
		$this->php_scan();
		return $this->scan;
	}
	
	public function cp_scan()
	{
		$cp_url = $this->EE->config->config['cp_url'];
		if(!$cp_url)
		{
			return FALSE;
		}
		
		$parts = parse_url($cp_url);
		if($parts['scheme'] == 'https')
		{
			$this->scan[__FUNCTION__]['cp_https'] = TRUE;
		}
		
		$pieces = explode('/', $parts['path']);
		if(count($pieces) < '3')
		{
			if(end($pieces) != 'admin.php')
			{
				$this->scan[__FUNCTION__]['admin_not_named_admin'] = TRUE;
			}			
		}
		else
		{
			$this->scan[__FUNCTION__]['admin_not_named_admin'] = TRUE;
		}

		if(strpos(BASEPATH, $_SERVER['DOCUMENT_ROOT']) === FALSE)
		{
			$this->scan[__FUNCTION__]['cp_outside_root'] = TRUE;
		}
		
		if(SYSDIR != 'system')
		{
			$this->scan[__FUNCTION__]['cp_not_named_system'] = TRUE;
		}	
		
		if($this->settings['enable_file_monitor'] == '1')
		{
			$this->scan[__FUNCTION__]['file_monitor'] = TRUE;
		}
		
		if($this->settings['enable_cp_login_alert'] == '1')
		{
			$this->scan[__FUNCTION__]['cp_login_alert'] = TRUE;
		}
		
		if($this->settings['enable_cp_ip_locker'] == '1')
		{
			$this->scan[__FUNCTION__]['cp_ip_locker'] = TRUE;
		}

		if($this->EE->addons_model->extension_installed('Vz_bad_behavior'))
		{
			$this->scan[__FUNCTION__]['vz_bb_installed'] = TRUE;
		}
		
		if($this->EE->addons_model->accessory_installed('Devotee'))
		{
			$this->scan[__FUNCTION__]['devotee_monitor_installed'] = TRUE;
		}		
		
	}
	
	public function version_control_scan()
	{
		$this->scan[__FUNCTION__]['version_control_free'] = TRUE;
		$map = $this->EE->file_monitor->directory_to_array($this->settings['file_scan_path'], TRUE);
		foreach($map AS $file)
		{
			$found_svn = strpos($file, '.svn');
			$found_git = strpos($file, '.git');
			if($found_svn !== false || $found_git !== false)
			{
				//set the scan to show it's found something
				$this->scan[__FUNCTION__]['version_control_free'] = FALSE;
				
				//sort of janky but it works for now
				//we want to create a list of locations for everything. REFACTOR
				if($found_git)
				{
					$type = '.git';
				}
				else
				{
					$type = '.svn';
				}
				
				$parts = explode($type, $file);
				$loc = $parts['0'].$type;
				$this->vcs_scan_locations[$loc] = $loc;
				////return;
			}
		}
		
		//$this->scan[__FUNCTION__]['version_control_free'] = TRUE;
	}
	
	public function check_config()
	{
		foreach($this->_expected_config_scan AS $key => $value)
		{
			if(is_string($value) && isset($this->EE->config->config[$key]) && ($this->EE->config->config[$key] == $this->_expected_config_scan[$key]))
			{
				$this->scan[__FUNCTION__][$key] = TRUE;
			}
		}
		
		if($this->EE->db->dbprefix != 'exp_')
		{
			$this->scan[__FUNCTION__]['db_prefix'] = TRUE;
		}
		
		if($this->EE->config->config['require_secure_passwords'] == 'y')
		{
			$this->scan[__FUNCTION__]['allow_dictionary_pw'] = TRUE; //dictionary passwords are moot if secure passwords are required
		}
		
		if (version_compare(APP_VER, '2.2', '>') && $this->EE->config->config['debug'] == '1')
		{
			$this->scan[__FUNCTION__]['debug'] = TRUE;
		}
		
		if($this->EE->config->config['encryption_key'] != '')
		{
			$this->scan[__FUNCTION__]['encrypt_key'] = TRUE;
		}
		
		if($this->EE->config->config['cookie_prefix'] != '')
		{
			$this->scan[__FUNCTION__]['cookie_prefix'] = TRUE;
		}
		
		if($this->EE->config->config['encryption_key'] != '' && strlen($this->EE->config->config['encryption_key']) >= $this->_expected_config_scan['encryption_key_length'])
		{
			$this->scan[__FUNCTION__]['encrypt_key_length'] = TRUE;
		}

		if($this->EE->config->config['password_lockout_interval'] >= $this->_expected_config_scan['password_lockout_interval'])
		{
			$this->scan[__FUNCTION__]['password_lockout_interval'] = TRUE;
		}
		
		if($this->EE->config->config['pw_min_len'] >= $this->_expected_config_scan['pw_min_len'])
		{
			$this->scan[__FUNCTION__]['pw_min_len'] = TRUE;
		}	

		if($this->EE->config->config['max_page_loads'] >= $this->_expected_config_scan['max_page_loads'])
		{
			$this->scan[__FUNCTION__]['max_page_loads'] = TRUE;
		}	

		if($this->EE->config->config['time_interval'] >= $this->_expected_config_scan['time_interval'])
		{
			$this->scan[__FUNCTION__]['time_interval'] = TRUE;
		}

		if($this->EE->config->config['lockout_time'] >= $this->_expected_config_scan['lockout_time'])
		{
			$this->scan[__FUNCTION__]['lockout_time'] = TRUE;
		}		

		if(!is_writable($this->EE->config->config_path))
		{
			//$this->scan['config_not_writable'] = TRUE;
		}	
			
		if($this->EE->config->config['profile_trigger'] != 'member')
		{
			$this->scan[__FUNCTION__]['profile_trigger'] = TRUE;
		}
	}	
	
	public function php_scan()
	{
		$check = ini_get('register_globals');
		if($check != 'y')
		{
			$this->scan[__FUNCTION__]['register_globals'] = TRUE;
		}
		
		$check = ini_get('allow_url_fopen');
		if($check != '1')
		{
			$this->scan[__FUNCTION__]['disable_url_fopen'] = TRUE;
		}
		
		$check = ini_get('expose_php');
		if($check != '1')
		{
			$this->scan[__FUNCTION__]['disable_expose_php'] = TRUE;
		}		

		if(version_compare(PHP_VERSION, $this->latest_php_version, '>=') == '1')
		{
			$this->scan[__FUNCTION__]['php_version'] = TRUE;
		}
		
		if(!function_exists('exec'))
		{
			$this->scan[__FUNCTION__]['disable_exec'] = TRUE;
		}
		
		if(!function_exists('shell_exec'))
		{
			$this->scan[__FUNCTION__]['disable_shell_exec'] = TRUE;
		}

		if(!function_exists('system'))
		{
			$this->scan[__FUNCTION__]['disable_system'] = TRUE;
		}

		if(!function_exists('proc_open'))
		{
			$this->scan[__FUNCTION__]['disable_proc_open'] = TRUE;
		}

		if(!function_exists('popen'))
		{
			$this->scan[__FUNCTION__]['disable_popen'] = TRUE;
		}		
		
		if(!function_exists('show_source'))
		{
			$this->scan[__FUNCTION__]['disable_show_source'] = TRUE;
		}
			
		if(!function_exists('dl'))
		{
			$this->scan[__FUNCTION__]['disable_dl'] = TRUE;
		}

		if(!function_exists('curl_exec'))
		{
			$this->scan[__FUNCTION__]['disable_curl_exec'] = TRUE;
		}

		if(!function_exists('curl_multi_exec'))
		{
			$this->scan[__FUNCTION__]['disable_curl_multi_exec'] = TRUE;
		}

		if(!function_exists('apache_note'))
		{
			$this->scan[__FUNCTION__]['disable_apache_note'] = TRUE;
		}

		if(!function_exists('apache_setenv'))
		{
			$this->scan[__FUNCTION__]['disable_apache_setenv'] = TRUE;
		}

		if(!function_exists('pcntl_exec'))
		{
			$this->scan[__FUNCTION__]['disable_pcntl_exec'] = TRUE;
		}

		if(!function_exists('proc_close'))
		{
			$this->scan[__FUNCTION__]['disable_proc_close'] = TRUE;
		}

		if(!function_exists('proc_get_status'))
		{
			$this->scan[__FUNCTION__]['disable_proc_get_status'] = TRUE;
		}

		if(!function_exists('proc_terminate'))
		{
			$this->scan[__FUNCTION__]['disable_proc_terminate'] = TRUE;
		}

		if(!function_exists('putenv'))
		{
			$this->scan[__FUNCTION__]['disable_putenv'] = TRUE;
		}

		if(!function_exists('virtual'))
		{
			$this->scan[__FUNCTION__]['disable_virtual'] = TRUE;
		}

		if(!function_exists('openlog'))
		{
			$this->scan[__FUNCTION__]['disable_openlog'] = TRUE;
		}

		if(!function_exists('proc_nice'))
		{
			$this->scan[__FUNCTION__]['disable_proc_nice'] = TRUE;
		}

		if(!function_exists('syslog'))
		{
			$this->scan[__FUNCTION__]['disable_syslog'] = TRUE;
		}

		if(!function_exists('phpinfo'))
		{
			$this->scan[__FUNCTION__]['disable_phpinfo'] = TRUE;
		}

		if(!function_exists('get_loaded_extensions'))
		{
			$this->scan[__FUNCTION__]['disable_get_loaded_extensions'] = TRUE;
		}

		if(!class_exists('splFileObject'))
		{
			$this->scan[__FUNCTION__]['disable_splFileObject'] = TRUE;
		}		
		
	}
}