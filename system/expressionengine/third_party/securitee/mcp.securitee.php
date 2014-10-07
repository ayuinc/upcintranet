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
 * Securit:ee - CP 
 *
 * Controller for the Control Panel
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/mcp.securitee.php
 */
class Securitee_mcp 
{
	public $url_base = '';
	
	/**
	 * The amount of pagination items per page
	 * @var int
	 */
	public $perpage = 10;
	
	/**
	 * The delimiter for the datatables jquery
	 * @var stirng
	 */
	public $pipe_length = 1;
	
	/**
	 * The name of the module; used for links and whatnots
	 * @var string
	 */
	private $mod_name = 'securitee';
	
	/**
	 * The name of the class for the module references
	 * @var string
	 */
	public $class = 'Securitee';
	
	public function __construct()
	{
		$this->EE =& get_instance();
		$path = dirname(realpath(__FILE__));
		include $path.'/config'.EXT;
		$this->class = $config['class_name'];
		$this->settings_table = $config['settings_table'];
		$this->version = $config['version'];
		
		$this->mod_name = $config['mod_url_name'];		

		//load EE stuff
		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');
		$this->EE->load->library('logger');
		$this->EE->load->helper('file');

		$this->EE->load->model('securitee_settings_model', 'securitee_settings', TRUE);	
		$this->EE->load->library('securitee_js');
		$this->EE->load->library('securitee_lib');
		$this->EE->load->library('securitee_scanner');
		$this->EE->load->library('exploit_scanner');
		$this->EE->load->library('file_monitor');

		$this->settings = $this->EE->securitee_lib->settings;
		$this->query_base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->mod_name.AMP.'method=';
		$this->url_base = BASE.AMP.$this->query_base;
		
		$this->EE->securitee_lib->set_url_base($this->url_base);
		//$this->file_scan_location = $this->settings['file_scan_location'];
		$this->progress_tmp = $this->EE->securitee_lib->progress_log_file = APPPATH.'cache/securitee/progress_data';
		
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->mod_name, $this->EE->lang->line('securitee_module_name'));
		$this->EE->cp->set_right_nav($this->EE->securitee_lib->get_right_menu());
		
		$this->errors = $this->EE->securitee_lib->error_check();
		$this->EE->load->vars(
				array(
						'url_base' => $this->url_base,
						'query_base' => $this->query_base,
						'errors' => $this->errors,
						'disable_accordions' => $this->settings['disable_accordions']
				)
		);		
		
		$ignore_methods = array();
		$method = $this->EE->input->get('method', TRUE);
		if($this->settings['disable_accordions'] === FALSE && !in_array($method, $ignore_methods))
		{
			$this->EE->javascript->output($this->EE->securitee_js->get_accordian_css());
		}		
	}
	
	public function index()
	{
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=securitee'.AMP.'method=settings');
	}
	
	public function exploit_scan()
	{
		$this->EE->functions->redirect($this->url_base.'index');
		exit;
		$proc_url = $this->url_base.'run_exploit_scan';
		$this->EE->securitee_lib->write_progress_log('Exploit Scan Started', 1, 0);
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('exploit_scan_in_progress'));
		
		$this->EE->cp->add_js_script('ui', 'progressbar'); 
		$this->EE->javascript->output('$("#progressbar").progressbar({ value: 0 });'); 
		$this->EE->javascript->output($this->EE->securitee_js->get_scan_progressbar($proc_url, $this->url_base));
		
		$this->EE->javascript->compile();

		$vars = array('proc_url' => $proc_url);
		return $this->EE->load->view('exploit_scan', $vars, TRUE);		
	}

	public function run_exploit_scan()
	{
		$this->EE->functions->redirect($this->url_base.'index');
		exit;		
		$this->EE->exploit_scanner->run();
		exit;
	}
	
	public function security_scan()
	{	
		$scan_data = $this->EE->securitee_scanner->get_scan();
		$count = 1;
		$passed = 0;
		foreach($scan_data AS $type => $scan)
		{
			foreach($scan AS $key => $value)
			{
				if($value == '1')
				{
					$passed++;
				}
				$count++;
			}
		}
		
		//$passed = 
		$progress = round($passed/$count*100);
		
		$vars = array();
		$vars['errors'] = $this->errors;
		$vars['passed_tests'] = $passed;
		$vars['total_tests'] = $count;
		$vars['test_ratio'] = $progress;
		$this->EE->view->cp_page_title = $this->EE->lang->line('security_scan');
		
		$this->EE->cp->add_js_script('ui', 'progressbar'); 
		$this->EE->javascript->output('$("#progressbar").progressbar({ value: 0 });'); 
		$this->EE->javascript->output($this->EE->securitee_js->get_security_scan_progressbar($progress));
				
		$this->EE->javascript->output($this->EE->securitee_js->get_check_toggle());
		$this->EE->cp->add_js_script('ui', 'accordion'); 
		//$this->EE->javascript->output($this->EE->securitee_js->get_accordian_css()); 

		$this->EE->jquery->tablesorter('#database_backups table', '{headers: {3: {sorter: false}}, widgets: ["zebra"], sortList: [[0,1]]}');  
		$this->EE->jquery->tablesorter('#file_backups table', '{headers: {3: {sorter: false}}, widgets: ["zebra"], sortList: [[0,1]]}');  
		
		$this->EE->javascript->compile();
		
		$vars['security_scan'] = $scan_data;
		$vars['cvs_file_locations'] = $this->EE->securitee_scanner->vcs_scan_locations;
		return $this->EE->load->view('index', $vars, TRUE); 
	}

	public function clear_file_monitor()
	{
		if($this->EE->file_monitor->remove_alert())
		{
			$this->EE->logger->log_action($this->EE->lang->line('log_file_monitor_cleared'));
			
			if(AJAX_REQUEST)
			{
				$this->EE->output->send_ajax_response(array('success'), FALSE);
			}
			else
			{			
				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('log_file_monitor_cleared'));
				$this->EE->functions->redirect($this->url_base.'index');
				exit;
			}
		}
		else
		{
			if(AJAX_REQUEST)
			{
				$this->EE->output->send_ajax_response(array('failue'), TRUE);
			}
			else	
			{
				$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('log_file_monitor_clear_fail'));
				$this->EE->functions->redirect($this->url_base.'index');
				exit;				
			}		
		}
	}
	
	public function progress()
	{
		die(file_get_contents($this->progress_tmp));
	}
	
	public function settings()
	{
		if(isset($_POST['go_settings']))
		{	
			if($this->EE->securitee_settings->update_settings($_POST))
			{	
				$this->EE->logger->log_action($this->EE->lang->line('log_settings_updated'));
				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('settings_updated'));
				$this->EE->functions->redirect($this->url_base.'settings');		
				exit;			
			}
			else
			{
				$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('settings_update_fail'));
				$this->EE->functions->redirect($this->url_base.'settings');	
				exit;					
			}
		}
		
		$member_groups = $this->EE->securitee_settings->get_member_groups();
		$pw_member_groups = $member_groups;
		//remove current group so self lockout doesn't happen
		foreach($member_groups AS $key => $value)
		{
			if($this->EE->session->userdata('group_id') == $key)
			{
				unset($member_groups[$key]);
				continue;
			}
			
			if($value == '1')
			{
				unset($member_groups[$key]);
			}
		}
		
		$this->EE->view->cp_page_title = $this->EE->lang->line('settings');
		
		$this->EE->cp->add_js_script('ui', 'accordion'); 
		//$this->EE->javascript->output($this->EE->securitee_js->get_accordian_css()); 		
		$this->EE->javascript->output($this->EE->securitee_js->get_settings_form()); 	
		$this->EE->javascript->compile();	

		$cron_action_id = $this->EE->cp->fetch_action_id($this->class, 'file_monitor');
		$vars = array();
		$vars['errors'] = $this->errors;
		$vars['settings'] = $this->settings;
		$vars['member_groups'] = $member_groups;
		$vars['pw_member_groups'] = $pw_member_groups;
		$vars['template_options'] = $this->EE->securitee_lib->get_template_options();
		$vars['cron_url'] = $this->EE->config->config['site_url'].'?ACT='.$cron_action_id;	
		$vars['ttl_options'] = $this->EE->securitee_settings->ttl_options;
		$vars['email_format_options'] = $this->EE->securitee_settings->email_format_options;

		$vars['settings_disable'] = FALSE;
		if(isset($this->EE->config->config['securitee']))
		{
			$vars['settings_disable'] = 'disabled="disabled"';
		}		
		return $this->EE->load->view('settings', $vars, TRUE);
	}
	
	public function ip_allow_access_form()
	{
		
	}
}