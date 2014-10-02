<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /**
 * mithra62 - Securit:ee
 *
 * @package		mithra62:Securitee
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2011, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/securit-ee/
 * @version		1.2.1
 * @filesource 	./system/expressionengine/third_party/securitee/
 */
 
 /**
 * Securit:ee - Settings Model
 *
 * Wrapper for the extension methods
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/models/securitee_settings_model.php
 */
class Securitee_settings_model extends CI_Model
{
	private $_table = '';
	
	public $_defaults = array(
						'allowed_access_levels' => '',
						'license_number' => '',
						'file_monitor_notify_emails' => array(),
						'file_scan_data' => '',
						'file_scan_alerts' => array(),
						'file_scan_path' => '',
						'enable_file_monitor' => '1',
						'enable_expiring_members' => '0',
						'enable_cp_login_alert' => '0',
						'enable_cp_ip_locker' => '0',
						'enable_client_ip_locker' => '0',
						'enable_quick_deny_cp_login' => '0',
						'enable_expiring_passwords' => '0',
						'enable_cp_member_reg_email' => '0',
						'enable_cp_email_activate' => '0',
						'disable_accordions' => FALSE,
						'allowed_ips' => array(),
						'cp_quick_deny_exclude_groups' => array(),
						'file_scan_exclude_paths' => array(),
						'login_alert_emails' => array(),
						'enable_exploit_scanner' => '0',
						'exploit_scan_path' => '',
						'exploit_scan_data' => '',
						'pw_ttl' => '86400',
						'forgot_password_email_subject' => '',
						'member_expire_ttl' => '0',
						'pw_expire_ttl' => '15552000',
						'pw_expire_member_groups' => array('5'),
						'cp_reg_email_expire_ttl' => '86400',
						'cp_reg_email_message_body' => 'cp_reg_email_message_body',
						'cp_reg_email_subject' => 'cp_reg_email_subject',
						'cp_reg_email_mailtype' => 'text',
						'change_pw_template' => 'cp_reg_email_template',
						'member_expire_groups' => array(),
						'member_expire_member_groups' => array(),
						'pw_email_message' => 'forgot_password_email_message',
						'pw_email_mailtype' => 'text',
						'exploit_scan_exclude_paths' => array(),
						'exploit_scan_notify_emails' => array(),
						'pw_change_template' => '',
						'allow_ip_template' => '',
						'allow_ip_email_subject' => '',
						'allow_ip_email_message' => '',
						'allow_ip_email_mailtype' => 'text',
						'allow_ip_template' => '0',
						'allow_ip_ttl' => '0',
						'allow_ip_add_member_groups' => array('1')
	);
	
	private $_serialized = array(
						'cron_notify_emails',
						'file_scan_data',
						'file_scan_exclude_paths',
						'file_scan_alerts',
						'file_monitor_notify_emails',
						'file_scan_exclude_paths',
						'allowed_ips',
						'login_alert_emails',
						'exploit_scan_data',
						'exploit_scan_notify_emails',
						'exploit_scan_exclude_paths',
						'member_expire_groups'
	);
	
	private $_encrypted = array(
						'cron_command'
	);	
	
	public $checkboxes = array(
			'enable_file_monitor',
			'enable_cp_login_alert',
			'enable_cp_ip_locker',
			'enable_client_ip_locker',
			'enable_quick_deny_cp_login',
			'enable_expiring_passwords',
			'enable_cp_member_reg_email',
			'enable_expiring_members',
			'enable_cp_email_activate'
	);
	
	public $custom_options = array(

			'cp_reg_email_expire_ttl',
			'pw_ttl',
			'pw_expire_ttl',
			'member_expire_ttl'
	);
	
	/**
	 * The options available for scheduling
	 * @var array
	 */
	public $ttl_options = array(
			'0' => 'Never',
			'1800' => '30 Minutes',
			'3600' => '1 Hour',
			'7200' => '2 Hours',
			'43200' => '12 Hours',
			'86400' => '1 Day',
			'172800' => '2 Days',
			'432000' => '5 Days',
			'604800' => '1 Week',
			'2592000' => '30 Days',
			'15552000' => '6 Months',
			'31104000' => '1 Year',
			'custom' => 'Custom'
	);	
	
	public $email_format_options = array(
			'text' => 'Text',
			'html' => 'HTML'
	);
	
	public function __construct()
	{
		parent::__construct();
		$this->_defaults['file_scan_path'] = $_SERVER['DOCUMENT_ROOT'];
		$this->_defaults['pw_email_message'] = lang($this->_defaults['pw_email_message']);
		$this->_defaults['cp_reg_email_message_body'] = lang('cp_reg_email_message_body');
		$this->_defaults['cp_reg_email_subject'] = lang('cp_reg_member_email_subject');
		$this->_defaults['forgot_password_email_subject'] = lang('forgot_password_email_subject');
		$this->_defaults['allow_ip_email_subject'] = lang('allow_ip_email_subject_copy');
		$this->_defaults['allow_ip_email_message'] = lang('allow_ip_email_message_body');

		$this->ttl_options = $this->set_lang($this->ttl_options);
		

		$path = dirname(realpath(__FILE__));
		include $path.'/../config'.EXT;
		$this->_table = $config['settings_table'];		
	}
	
	/**
	 * Adds a setting to the databse
	 * @param string $setting
	 */
	public function add_setting($setting)
	{
		$data = array(
		   'setting_key' => $setting
		);
		
		return $this->db->insert($this->_table, $data); 
	}	
	
	public function get_settings()
	{	
		$this->db->select('setting_key, setting_value, serialized')
				 ->from($this->_table)
				 ->where('setting_key !=', 'file_scan_data')
				 ->where('setting_key !=', 'file_scan_alerts');
		$query = $this->db->get();	
		$_settings = $query->result_array();
		$settings = array();	
		foreach($_settings AS $setting)
		{
			$settings[$setting['setting_key']] = ($setting['serialized'] == '1' ? unserialize($setting['setting_value']) : $setting['setting_value']);
		}
		
		//now check to make sure they're all there and set default values if not
		foreach ($this->_defaults as $key => $value)
		{	
			//setup the override check
			if(isset($this->config->config['securitee'][$key]))
			{
				$settings[$key] = $this->config->config['securitee'][$key];
			}
						
			if(!isset($settings[$key]))
			{
				$settings[$key] = $value;
			}
		}		

		return $settings;
	}
	
	/**
	 * Returns the value straigt from the database
	 * @param string $setting
	 */
	public function get_setting($setting)
	{
		$data = $this->db->get_where($this->_table, array('setting_key' => $setting))->result_array();
		if(isset($data['0']))
		{
			$data = $data['0'];
			if($data['serialized'] == '1')
			{
				$data['setting_value'] = unserialize($data['setting_value']);
				if(!$data['setting_value'])
				{
					$data['setting_value'] = array();
				}
			}
			return $data['setting_value'];
		}
	}	
	
	public function update_settings(array $data)
	{
		
		foreach($this->checkboxes As $key => $value)
		{
			if(!isset($data[$value]))
			{
				$data[$value] = '0';	
			}
		}	
		
		foreach($this->custom_options As $key => $value)
		{
			if(isset($data[$value]) && $data[$value] == 'custom' && $data[$value.'_custom'] != '')
			{
				$data[$value] = $data[$value.'_custom'];
			}
		}
		
		foreach($data AS $key => $value)
		{
			
			if(in_array($key, $this->_serialized))
			{
				$value = explode("\n", $value);
				//hack to remove bad email addresses from list
				if($key == 'file_monitor_notify_emails' || $key == 'login_alert_emails')
				{
					$temp = array();
					foreach($value AS $email)
					{
						if(filter_var($email, FILTER_VALIDATE_EMAIL))
						{
							$temp[] = $email;
						}						
					}
					$value = $temp;
				}

				if($key == 'allowed_ips')
				{
					$temp = array();
					foreach($value AS $ip)
					{
						if(!$this->input->valid_ip($ip))
			        	{
							continue;       		
			        	}        	
	
						$temp[] = $ip;					
					}
					
					if(((isset($data['enable_cp_ip_locker']) && $data['enable_cp_ip_locker'] == '1') || (isset($data['enable_client_ip_locker']) && $data['enable_client_ip_locker'] == '1')) && !in_array($this->input->ip_address(), $temp))
					{
						$temp[] = $this->input->ip_address();
					}
					$value = $temp;
				}
			}
			
			if(in_array($key, $this->_encrypted) && $value != '')
			{
				$value = $this->encrypt->encode($value);
			}
			
			$this->update_setting($key, $value);
		}
		
		return TRUE;
	}
	
	/**
	 * Updates the value of a setting
	 * @param string $key
	 * @param string $value
	 */
	public function update_setting($key, $value)
	{
		if(!$this->_check_setting($key))
		{
			return FALSE;
		}

		$data = array();
		if(is_array($value))
		{
			$value = serialize($value);
			$data['serialized '] = '1';
		}
		
		$data['setting_value'] = $value;
		$this->db->where('setting_key', $key);
		return $this->db->update($this->_table, $data);
	}

	/**
	 * Verifies that a submitted setting is valid and exists. If it's valid but doesn't exist it is created.
	 * @param string $setting
	 */
	private function _check_setting($setting)
	{
		if(array_key_exists($setting, $this->_defaults))
		{
			$value = $this->get_setting($setting);
			if(!$value && $value !== '0' && !is_array($value))
			{
				$this->add_setting($setting);
			}
			
			return TRUE;
		}		
	}	
	
	public function get_member_groups()
	{
		$this->db->select('group_title , group_id');
		$query = $this->db->get('member_groups');	
		$_groups = $query->result_array();	
		$groups = array();
		$groups[''] = '';
		foreach($_groups AS $group)
		{
			$groups[$group['group_id']] = $group['group_title'];
		}
		return $groups;
	}
	
	public function set_lang($arr)
	{
		foreach($arr AS $key => $value)
		{
			$arr[$key] = lang($value);
		}
		return $arr;
	}	
	
}