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
 * Securit:ee - File Monitor library
 *
 * Library class for the File Monitor
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/libraries/File_monitor.php
 */
class File_monitor
{
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->library('email');	
		$this->settings = $this->EE->securitee_settings->get_settings();
	}
	
	public function cron()
	{
		
	}
	
	public function scan_site()
	{
		$scan_paths = $this->directory_to_array($this->settings['file_scan_path'], TRUE);
		$scan_data = array();
		$diff = array();
		$count = 0;
		foreach($scan_paths AS $path)
		{
			if(!is_dir($path))
			{
				$scan_data[$path] = md5_file($path);
			}
		}
		
		$this->settings['file_scan_data'] = $this->EE->securitee_settings->get_setting('file_scan_data');
		$this->settings['file_scan_alerts'] = $this->EE->securitee_settings->get_setting('file_scan_alerts');
		if(is_array($this->settings['file_scan_data']))
		{
			$diff['added_files'] = array_diff_key($scan_data, $this->settings['file_scan_data']); 		
			$diff['removed_files'] = array_diff_key($this->settings['file_scan_data'], $scan_data); 				
			$diff['changed_files'] = array_diff($scan_data, $this->settings['file_scan_data']);
			
			foreach ($diff['added_files'] AS $file => $value) 
			{ 
				unset($diff['changed_files'][$file]);
			}
			foreach ($diff['removed_files'] AS $file => $value) 
			{ 
				unset($diff['changed_files'][$file]);
			}
			
			//check for updates to notify about
			if(count($diff['added_files']) >= '1' || count($diff['removed_files']) >= '1' || count($diff['changed_files']) >= '1')
			{
				//save diff
				$this->settings['file_scan_alerts'][time()] = $diff;
				
				//update alert with new diff added
				$this->EE->securitee_settings->update_setting('file_scan_alerts', $this->settings['file_scan_alerts']);	
				
				//install the file monitor acc
				$this->EE->securitee_lib->install_acc();
				
				//send notification
				$this->notify();
			}	
		}
		
		$this->EE->securitee_settings->update_setting('file_scan_data', $scan_data);		
		return;
	}
	
	public function remove_alert()
	{
		if($this->EE->securitee_settings->update_setting('file_scan_alerts', array()))
		{
			return $this->EE->securitee_lib->uninstall_acc();
		}
		
	}
	
	public function notify()
	{
		$to = array();
		$this->EE->lang->loadfile('securitee');
		foreach($this->settings['file_monitor_notify_emails'] AS $email)
		{
			if(filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$to[] = $email;
			}
		}
		
		if(count($to) == '0')
		{
			return FALSE;
		}

		$this->EE->email->mailtype = 'html';
		$this->EE->email->from($this->EE->config->config['webmaster_email'], $this->EE->config->config['site_name']);
		$this->EE->email->to($to);
		$this->EE->email->subject($this->EE->config->config['site_name'].' '.lang('file_monitor_notify'));	

		$message = lang('file_monitor_notify_message').'<a href="'.$this->EE->config->config['site_url'].'">'.$this->EE->config->config['site_name']."</a>!<br /><br />";
		$scans = $this->EE->securitee_settings->get_setting('file_scan_alerts');
		krsort($scans);	
		foreach($scans AS $timestamp => $scan)
		{
			$message .= $this->EE->localize->set_human_time($timestamp)."<br />";
			foreach($scan AS $key => $value)
			{
				$count = count($value);
				if($count >= '1')
				{
					$message .= $this->EE->lang->line($key).": ".$count."<br />";
					$value = array_keys($value);
					$message .= implode("<br />", $value)."<br />";
				}
			}	

			$message .= "<br />";
		}

		$message .= "<br />".lang('file_monitor_notify_message_footer');
		
		$this->EE->email->message($message);
		$this->EE->email->send();			
	}
	
	public function directory_to_array($directory, $recursive) 
	{
		$array_items = array();
		$directory = preg_replace("/\/\//si", "/", $directory);
		$ignore = (is_array($this->settings['file_scan_exclude_paths']) ? $this->settings['file_scan_exclude_paths'] : array());
		
		if(!is_readable($directory))
		{
			return $array_items;
		}
		
		if ($handle = opendir($directory)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					if(in_array($file, $ignore) || in_array($directory. '/' . $file, $ignore)  || in_array($directory, $ignore))
					{
						continue;
					}
					if (is_dir($directory. '/' . $file)) {
						if($recursive) {
							$array_items = array_merge($array_items, $this->directory_to_array($directory. '/' . $file, $recursive));
						}
						$file = $directory . '/' . $file;
						$array_items[] = preg_replace("/\/\//si", "/", $file);
					} 
					else
					{
						$array_items[] = $directory . '/' . $file;
					}
				}
			}
			closedir($handle);
		}
		return $array_items;
	}	
	
}
