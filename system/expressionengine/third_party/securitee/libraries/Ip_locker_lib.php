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
 * Securit:ee - IP Locker library
 *
 * IP Locker methods and funcitons
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/libraries/Ip_locker_lib.php
 */
class Ip_locker_lib
{	
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->settings = $this->EE->securitee_lib->get_settings();
		$this->EE->load->model('securitee_hashes_model', 'securitee_hashes');
		$this->EE->load->library('email');
		$this->EE->load->library('password_lib');
	}
	
	/**
	 * Wrapper to handle sending notification 
	 * @param array $member_data
	 * @param string $confirm_url
	 */
	public function send_email(array $member_data, $subject = 'allow_ip_email_subject', $message = 'allow_ip_email_message', $mailtype = 'allow_ip_email_mailtype')
	{
		if(!isset($this->EE->TMPL))
		{
			$this->EE->load->library('Template', null, 'TMPL');
		}
		
		$this->EE->load->library('email');
		$guid = $this->EE->password_lib->guidish();
		$this->EE->password_lib->update_hash($member_data['member_id'], $guid, '1');
		
		$action_id = $this->EE->securitee_lib->get_module_action('Securitee', 'allow_ip_access');
		$action_url = $this->EE->config->config['site_url'].'?ACT='.$action_id;
		
		//send email
		$this->EE->email->clear();
		$url = $action_url.'&g='.$guid;
		$vars = array_merge($member_data, array('allow_url' => $url), $this->EE->config->config);
		$this->EE->email->mailtype = $this->settings[$mailtype];
		$this->EE->email->from($this->EE->config->config['webmaster_email'], $this->EE->config->config['site_name']);
		$this->EE->email->to($member_data['email']);
		
		$subject = $this->EE->TMPL->parse_variables($this->settings[$subject], array($vars));
		$this->EE->email->subject($subject);
		
		$message = $this->settings[$message];
		$message = $this->EE->TMPL->parse_variables($message, array($vars));
		
		$this->EE->email->message($message);
		$this->EE->email->send();
		$this->EE->email->clear();
		return TRUE;
	}
	
	public function add_ip($ip)
	{
		if(!in_array($ip, $this->settings['allowed_ips']))
		{
			$this->settings['allowed_ips'][] = $ip;
			return $this->EE->securitee_settings->update_setting('allowed_ips', $this->settings['allowed_ips']);			
		}
	}
}