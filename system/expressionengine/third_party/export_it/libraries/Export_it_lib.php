<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /**
 * mithra62 - Export It
 *
 * @package		mithra62:Export_it
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2011, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/export-it/
 * @version		1.0
 * @filesource 	./system/expressionengine/third_party/export_it/
 */
 
 /**
 * Export It - General Library Class
 *
 * Contains all the generic methods for Export It
 *
 * @package 	mithra62:Export_it
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/export_it/libraries/Export_it_lib.php
 */
class Export_it_lib
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
	 * The memory limits to attempt setting
	 * @var array
	 */
	public $memory_limits = array(
		'64MB',
		'96MB',
		'128MB',
		'160MB',
		'192MB',
		'256MB'
	);

	/**
	 * A list of valid SQL operators template tags can use in "where:XX" parameters
	 * @var array
	 */
	private $valid_operators = array(
		'>', 
		'>=', 
		'<=', 
		'<', 
		'=', 
		'!=', 
		'LIKE'
	);	
	
	/**
	 * @ignore
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->model('export_it_settings_model', 'export_it_settings');
		$this->settings = $this->get_settings();
	}
	
	public function get_settings()
	{
		if (!isset($this->EE->session->cache['export_it']['settings'])) 
		{	
			$this->EE->session->cache['export_it']['settings'] = $this->EE->export_it_settings->get_settings();
		}
		
		return $this->EE->session->cache['export_it']['settings'];
	}
	
	/**
	 * Sets up the right menu options
	 * @return multitype:string
	 */
	public function get_right_menu()
	{
		$menu = array(
				'members'		=> $this->url_base.'members',
				'channel_entries'	=> $this->url_base.'channel_entries',
				'comments'	=> $this->url_base.'comments'
		);
		
		if($this->is_installed_module('Mailinglist'))
		{
			$menu['mailing_list'] = $this->url_base.'mailing_list';
		}
		
		$menu['settings'] = $this->url_base.'settings';
		
		return $menu;
		
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
		else
		{
			if(!$this->valid_license($this->settings['license_number']))
			{
				$errors['license_number'] = 'invalid_license_number';
			}
			elseif($this->settings['license_status'] != '1')
			{
				$errors['license_number'] = 'invalid_license_number';
			}
		}
		
		return $errors;
	}
	
	public function export_formats($type = 'channel_entries')
	{
		switch($type)
		{
			case 'members':
				return array('xls' => 'Excel', 'xml' => 'XML', 'json' => 'JSON', 'ee_xml' => 'EE Member XML');
			break; 
			
			case 'mailing_list':
				return array('xls' => 'Excel', 'xml' => 'XML', 'json' => 'JSON');
			break;
			
			case 'comments':
				return array('disqus' => 'Disqus', 'xml' => 'XML', 'json' => 'JSON');
			break;	

			case 'channel_entries':
				return array('xls' => 'Excel', 'xml' => 'XML', 'json' => 'JSON');
			break;
		}
	}
	
	public function get_comment_channels()
	{
		if (!$this->EE->cp->allowed_group('can_moderate_comments') && !$this->EE->cp->allowed_group('can_edit_all_comments'))
		{
			$query = $this->EE->channel_model->get_channels(
									(int) $this->EE->config->item('site_id'), 
									array('channel_title', 'channel_id', 'cat_group'));
		}
		else
		{
			$this->EE->db->select('channel_title, channel_id, cat_group');
			$this->EE->db->where('site_id', (int) $this->EE->config->item('site_id'));
			$this->EE->db->order_by('channel_title');
		
			$query = $this->EE->db->get('channels'); 
		}
		
		if ( ! $query)
		{
			return array();
		}

		$options = array();
		$options['0'] = lang('select_channel');
		foreach ($query->result() as $row)
		{
			$options[$row->channel_id] = $row->channel_title;
		}

		return $options;		
	}
	
	 public function get_date_select()
	 {
	 	$data = array(
	 		'' => lang('all'),
	 		1 => lang('past_day'),
	 		7 => lang('past_week'),
	 		31 => lang('past_month'),
	 		182 => lang('past_six_months'),
	 		365 => lang('past_year')
		);
		
		return $data;
	 }

	public function get_status_select()
	{
		$data = array(
			'' => lang('all'),
			'p' => lang('pending'),
			'o' => lang('open'),
			'c' => lang('closed')
		);
		
		return $data;
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
	
	public function perpage_select_options()
	{
		return array(
			   '10' => '10 '.lang('results'),
			   '25' => '25 '.lang('results'),
			   '75' => '75 '.lang('results'),
			   '100' => '100 '.lang('results'),
			   '150' => '150 '.lang('results')
		);		
	}
	
	public function date_select_options()
	{
		return array(
			   '' => lang('date_range'),
			   '1' => lang('past_day'),
			   '7' => lang('past_week'),
			   '31' => lang('past_month'),
			   '182' => lang('past_six_months'),
			   '365' => lang('past_year'),
			   'custom_date' => lang('any_date')
		);				
	}	
	
	/**
	 * Validates a license number is valid
	 * @param string $license
	 * @return number
	 */
	public function valid_license($license)
	{
		return preg_match("/^([a-z0-9]{8})-([a-z0-9]{4})-([a-z0-9]{4})-([a-z0-9]{4})-([a-z0-9]{12})$/", $license);
	}	
	
	/**
	 * Performs the license check
	 *
	 * Yes, if you wanted to disable license checks in Backup Pro 2, you'd mess with this.
	 * But.. c'mon... I've worked hard and it's just me...
	 *
	 * @param string $force
	 */	
	public function l($force = false)
	{
		$valid = false;
		if( $this->settings['license_number'] && $this->valid_license($this->settings['license_number']))
		{
			$license_check = $this->settings['license_check'];
			$next_notified = mktime(date('G', $license_check)+24, date('i', $license_check), 0, date('n', $license_check), date('j', $license_check), date('Y', $license_check));
	
			if(time() > $next_notified || $force)
			{
				//license_check
				$get = array(
						'ip' => ($this->EE->input->ip_address()),
						'key' => ($this->settings['license_number']),
						'site_url' => ($this->EE->config->config['site_url']),
						'webmaster_email' => ($this->EE->config->config['webmaster_email']),
						'add_on' => ('export-it'),
						'version' => ('1.4.3')
				);
	
				$url = 'https://mithra62.com/license-check/'.base64_encode(json_encode($get));
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
				$response = urldecode(curl_exec($ch));
	
				$json = json_decode($response, true);
				if($json && isset($json['valid']))
				{
					$this->EE->export_it_settings->update_setting('license_status', $json['valid']);
				}
				else
				{
					$this->EE->export_it_settings->update_setting('license_status', '0');
				}
	
				$this->EE->export_it_settings->update_setting('license_check', time());
			}
		}
	}	
	
	/**
	 * Wrapper to update the settings
	 * @param array $settings
	 * @return bool
	 */
	public function update_settings(array $settings = array())
	{
		if(isset($settings['license_number']) && $this->valid_license($settings['license_number']) && $this->settings['license_number'] != $settings['license_number'])
		{
			$settings['license_status'] = 1;
			$settings['license_check'] = 0;
		}
	
		return $this->EE->export_it_settings->update_settings($settings);
	}	
	
	public function create_pagination($method, $total, $per_page)
	{
		$config = array();
		$config['page_query_string'] = TRUE;
		$config['base_url'] = $this->url_base.$method;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['page_query_string'] = TRUE;
		$config['full_tag_open'] = '<p id="paginationLinks">';
		$config['full_tag_close'] = '</p>';
		$config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';

		$this->EE->pagination->initialize($config);
		return $this->EE->pagination->create_links();		
	}
	
	public function is_installed_module($module_name)
	{
		$data = $this->EE->db->select('module_name')->from('modules')->like('module_name', $module_name)->get();
		if($data->num_rows == '1')
		{
			return TRUE;
		}
	}	
	
	/**
	 * Returns a valid SQL operator from a formatted string
	 * @param string $str
	 */
	public function sql_operator($string)
	{
		preg_match('/.*\s/', $string, $matches);
	
		if(isset($matches[0]))
		{
			$match = trim($matches[0]);
				
			if(in_array($match, $this->valid_operators))
			{
				return ' '.$matches[0];
			}
		}
	
		return NULL;
	}
	
	/**
	 * Returns a valid string strips of SQL operators
	 * @param string $str
	 */
	public function strip_operators($string)
	{
		preg_match('/.*\s/', $string, $matches);
	
		if(isset($matches[0]))
		{
			$match = trim($matches[0]);
				
			if(in_array($match, $this->valid_operators))
			{
				$string = preg_replace('/^'.preg_quote($match).'/', '', $string);
			}
		}
	
		return trim($string);
	}

	public function setup_memory_limits()
	{
		if(function_exists('ini_set'))
		{
			foreach($this->memory_limits AS $limit)
			{
				//ini_set('memory_limit', $limit);	
			}
		}	
	}
	
}