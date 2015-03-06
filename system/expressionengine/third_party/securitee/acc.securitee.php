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
 * Securit:ee - Accessory
 *
 * Wrapper for the accessory methods
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/acc.securitee.php
 */
class Securitee_acc 
{

	/**
	 * The name of the acc
	 * @var string
	 */
	public $name		= 'Securit:ee';
	
	/**
	 * The EE identifier
	 * @var string
	 */
	public $id			= 'securitee';
	
	/**
	 * The accessory version
	 * @var string
	 */
	public $version		= '1.2.2';
	
	/**
	 * The accessory description
	 * @var string
	 */
	public $description	= 'Widget for the Securit:ee File Monitor alerts. Don\'t MANUALLY Activate!';
	
	/**
	 * The sections the accessory is going to create
	 * @var array
	 */
	public $sections	= array();
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
		
		$path = dirname(realpath(__FILE__));
		include $path.'/config'.EXT;
		$this->name = $config['name'];
		$this->id = strtolower($config['class_name']);
		$this->version = $config['version'];
		$this->description = $config['description'];
		
		$this->query_base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->id.AMP.'method=';
		$this->url_base = BASE.AMP.$this->query_base;	
	}	
	
	/**
	 * Wrapper for the sections
	 */
	function set_sections()
	{
		$this->EE->load->model('securitee_settings_model', 'securitee_settings', TRUE);
		$this->EE->load->library('securitee_js');
		$this->EE->lang->loadfile('securitee');		
		$scans = $this->EE->securitee_settings->get_setting('file_scan_alerts');
		
		if(!$scans)
		{
			$this->EE->load->library('securitee_lib');
			$this->EE->securitee_lib->uninstall_acc();
			return FALSE;
		}
		
		$this->sections['Securit:ee Options'] = '
			<a class="" href="'.$this->url_base.'clear_file_monitor" id="clear_file_monitor">'.lang('clear_file_monitor_history').'</a><br />
			<a class="" href="'.$this->url_base.'settings">'.lang('settings').'</a><br />
			<a class="" href="'.$this->url_base.'security_scan">'.lang('security_scan').'</a><br /><br />
			<a class="" href="http://mithra62.com/docs/detail/securitee-instructions/file-monitor-instructions" target="_blank">About File Monitor</a><br />
			<a class="" href="http://mithra62.com/docs/securit-ee" target="_blank">About Securit:ee</a>					
		';
		
		krsort($scans);	
		$data = '<div id="file_monitor_clear_success" style="display:none" class="go_notice">File Monitor Cleared :)</div> <div id="securitee_file_monitor_results">';
		foreach($scans AS $timestamp => $scan)
		{
			$data .= "<div class='entry'><a class='entryLink' href='javascript:;'>".$this->EE->localize->human_time($timestamp)."</a>";
			foreach($scan AS $key => $value)
			{
				$count = count($value);
				if($count >= '1')
				{
					$data .= "<div class='entryDate'>".$this->EE->lang->line($key).": ".$count."</div>";
					$data .= "<div class='fullEntry' style='display:none; padding-left:10px;'>";
					$value = array_keys($value);
					$data .= implode('<br />', $value).'</p>';
					$data .= "</div>";				
				}
			}
			$data .= "</div>";
			
		}
		$data .= "</div>";

		$this->sections['Securit:ee File Monitor'] = $data;

		$this->EE->javascript->output($this->EE->securitee_js->get_acc_scripts());
	}	
}