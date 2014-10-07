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
 * Securit:ee - Generic library
 *
 * Generic methods and funcitons
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/libraries/Securitee_lib.php
 */
class Securitee_lib
{
	/**
	 * Preceeds URLs 
	 * @var mixed
	 */
	private $url_base = FALSE;
	
	/**
	 * The full path to the log file for the progress bar
	 * @var string
	 */
	public $progress_log_file;
	
	/**
	 * The name of the Accessory class
	 * @var string
	 */
	public $acc_class = 'Securitee_acc';
	
	
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->settings = $this->get_settings();
	}
	
	public function get_settings()
	{
		if ( ! isset($this->EE->session->cache[__CLASS__]['settings'])) 
		{	
			$this->EE->session->cache[__CLASS__]['settings'] = $this->EE->securitee_settings->get_settings();
		}
		
		return $this->EE->session->cache[__CLASS__]['settings'];
	}
	
	/**
	 * Sets up the right menu options
	 * @return multitype:string
	 */
	public function get_right_menu()
	{
		return array(
				'settings'		=> $this->url_base.'settings',
				'security_scan'	=> $this->url_base.'security_scan'
		);
	}

	/**
	 * Wrapper that runs all the tests to ensure system stability
	 * @return array;
	 */
	public function error_check()
	{
		$errors = array();
		if($this->settings['license_number'] == '')
		{
			$errors['license_number'] = 'missing_license_number';
		}
		return $errors;
	}
	
	/**
	 * Wrapper to handle CP URL creation
	 * @param string $method
	 */
	public function _create_url($method)
	{
		return $this->url_base.$method;
	}

	/**
	 * Creates the value for $url_base
	 * @param string $url_base
	 */
	public function set_url_base($url_base)
	{
		$this->url_base = $url_base;
	}
	
	/**
	 * Creates a manageable array of the installed plugins. 
	 */
	public function get_installed_plugins()
	{
		$plugins = $this->EE->addons_model->get_plugins();
		$arr = array('' => '');
		foreach($plugins AS $plugin_id => $plugin)
		{
			$arr[$plugin_id] = $plugin['pi_name'];
		}
		
		asort($arr);
		return $arr;
	}
	
	public function get_installed_modules()
	{
		$modules = $this->EE->addons->get_installed();
		$arr = array('' => '');
		foreach($modules AS $module_id => $module)
		{
			$arr[$module_id] = $module['name'];
		}
		
		asort($arr);
		return $arr;
	}

	/**
	 * Takes a cron formatted string and formats it for people
	 * @param array $str
	 */
	public function parse_cron_string($str)
	{
		$this->EE->cronparser->calcLastRan($str);
		$arr = array();
		$arr['last_run_unix'] = $this->EE->cronparser->getLastRanUnix();
		$arr['last_run_array'] = $this->EE->cronparser->getLastRan();
		return $arr;	
	}
	
	
	public function get_module_actions($module)
	{
		$this->EE->load->dbforge();
		return $this->EE->db->get_where('actions', array('class' => $module))->result_array();		
	}

	public function get_module_action($module, $action)
	{
		$this->EE->load->dbforge();
		$this->EE->db->select('action_id');
		$data = $this->EE->db->get_where('actions', array('class' => $module, 'method' => $action), '1')->result_array();
		if($data)
		{
			return $data['0']['action_id'];
		}
	}
	
	public function install_acc()
	{
		$data = $this->EE->db->get_where('accessories', array('class' => $this->acc_class), '1')->result_array();
		if(count($data) == '0')
		{
			$this->EE->db->insert('accessories', array(
				'class' => $this->acc_class,
				'accessory_version'	=> '1.0',
				'controllers' => 'addons|addons_accessories|addons_extensions|addons_fieldtypes|addons_modules|addons_plugins|admin_content|admin_system|content|content_edit|content_files|content_publish|design|homepage|members|myaccount|tools|tools_communicate|tools_data|tools_logs|tools_utilities',
				'member_groups' => '1|5'
			));	
		}	
	}
	
	public function uninstall_acc()
	{
		return $this->EE->db->delete('accessories', array('class' => $this->acc_class));
	}

	public function proc_logout()
	{
		$this->EE->db->where('ip_address', $this->EE->input->ip_address());
		$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
		$this->EE->db->delete('online_users');

		$this->EE->session->destroy();
		
		$this->EE->functions->set_cookie('read_topics'); 		
	}
	
	/**
	 * Writes out the progress log for the progress bar status updates
	 * @param string $msg
	 * @param int $total_items
	 * @param int $item_number
	 */
	public function write_progress_log($msg, $total_items = 0, $item_number = 0)
	{
		if($item_number > $total_items)
		{
			$item_number = $total_items;
		}
		
		$log = array('total_items' => $total_items, 'item_number' => $item_number, 'msg' => $msg);
		write_file($this->progress_log_file, $this->EE->javascript->generate_json($log));
	}
	
	/**
	 * Removes the progress log
	 */
	public function remove_progress_log()
	{	
		delete_files($this->progress_log_file);
	}

	/**
	 * Returns a member 
	 * @param array $where
	 */
	public function get_member(array $where)
	{
		$this->EE->db->select('email, username, member_id, group_id');
		$data = $this->EE->db->from('members')->where($where)->limit('1')->get()->result_array();
		return $data;
	}
	
	/**
	 * Checks if an email is valid
	 * @param string $email
	 * @return mixed
	 */
	public function check_email($email)
	{
		if(function_exists('filter_var'))
		{
			return filter_var($email, FILTER_VALIDATE_EMAIL);
		}
		else
		{
			return $this->valid_email($email);
		}
	}	
	
	public function get_template_options()
	{
		if ( ! isset($this->EE->session->cache[__CLASS__]['template_options']))
		{
			$this->EE->load->model('template_model');
			$query = $this->EE->template_model->get_templates();
			$this->EE->session->cache[__CLASS__]['template_options'][] = '';
				
			if ($query->num_rows() > 0)
			{
				foreach ($query->result() as $template)
				{
					$this->EE->session->cache[__CLASS__]['template_options'][$template->template_id] = $template->group_name.'/'.$template->template_name;
				}
			}
		}
	
		return $this->EE->session->cache[__CLASS__]['template_options'];
	}

	public function get_template_data($template_id)
	{
		$site_id = $this->EE->config->item('site_id');
		$this->EE->db->select("template_id, template_name, group_name");
		$this->EE->db->from("templates");
		$this->EE->db->join("template_groups", "templates.group_id = template_groups.group_id");
		$this->EE->db->where('templates.site_id', $site_id);
		$this->EE->db->where('template_id', $template_id);
		$results = $this->EE->db->get();
		if($results->num_rows == '1')
		{
			return $results->row_array();
		}
	}
	
	public function generate_xid()
	{
		if (defined('XID_SECURE_HASH') == TRUE)
		{ 
			return XID_SECURE_HASH;
		}
	
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			if (isset($this->EE->session->cache['XID']) == TRUE && $this->EE->session->cache['XID'] != FALSE)
			{
				$XID = $this->EE->session->cache['XID'];
			}
			else
			{
	
				$this->EE->db->select('hash')->from('security_hashes');
				$this->EE->db->where('ip_address', $this->EE->input->ip_address());
				$this->EE->db->where('`date` > UNIX_TIMESTAMP()-3600');
				$this->EE->db->limit(1);
				$query = $this->EE->db->get();
		
				if ($query->num_rows() > 0)
				{
					$row = $query->row();
					$this->EE->session->cache['XID'] = $row->hash;
					$XID = $this->EE->session->cache['XID'];
				}
				else
				{
					$XID = $this->EE->functions->random('encrypt');
					$this->EE->db->insert('security_hashes', array('date' => $this->EE->localize->now, 'ip_address' => $this->EE->input->ip_address(), 'hash' => $XID));
					$this->EE->session->cache['XID'] = $XID;
				}
			}
			
			return $XID;
		}
	}	
}