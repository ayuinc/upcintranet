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
 * Securit:ee - Password library
 *
 * Password methods and funcitons
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/libraries/Password_lib.php
 */
class Password_lib
{	
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->settings = $this->EE->securitee_lib->get_settings();
		$this->EE->load->model('securitee_hashes_model', 'securitee_hashes');
		$this->EE->load->library('email');
		$this->EE->load->model('template_model');
	}
	
	/**
	 * Wrapper to handle sending notification 
	 * @param array $member_data
	 * @param string $confirm_url
	 */
	public function send_email(array $member_data, $confirm_url, $subject = 'forgot_password_email_subject', $message = 'pw_email_message', $mailtype = 'pw_email_mailtype')
	{
		if(!isset($this->EE->TMPL))
		{
			$this->EE->load->library('Template', null, 'TMPL');
		}
		
		$this->EE->load->library('email');
		$guid = $this->guidish();
		$this->update_hash($member_data['member_id'], $guid);
		
		//send email
		$this->EE->email->clear();
		$url = $this->EE->functions->create_url($confirm_url.'/'.$guid);
		$vars = array_merge($member_data, array('change_url' => $url), $this->EE->config->config);
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

	/**
	 * Updates a hash with the forgot password stuff
	 * @param int $member_id
	 * @param string $hash
	 */
	public function update_hash($member_id, $hash, $allow_ip = '0')
	{
		$hash_data = $this->EE->securitee_hashes->get_hash(array('member_id' => $member_id));
		if(!$hash_data)
		{
			$this->EE->securitee_hashes->add_hash(array('member_id' => $member_id, 'hash' => $hash, 'allow_ip' => $allow_ip));
		}
		else
		{
			$this->EE->securitee_hashes->update_hash(array('hash' => $hash, 'forgotten_stamp' => date('Y-m-d H:i:s'), 'allow_ip' => $allow_ip), array('member_id' => $member_id), FALSE);
		}
	}
	
	/**
	 * Checks whether a given hash is valid
	 * @param string $hash
	 * @param int $ttl
	 * @return mixed
	 */
	public function check_hash($hash, $ttl)
	{
		$max_stamp = date('Y-m-d H:i:s', mktime(date("H"), date("i"), date("s")-$ttl, date("m")  , date("d"), date("Y")));
		if($ttl != '0')
		{
			$where = array('hash' => $hash, 'forgotten_stamp >' => $max_stamp, 'forgotten_stamp <' => date('Y-m-d H:i:s'));
		}
		else
		{
			$where = array('hash' => $hash);
		}
		
		$hash_data = $this->EE->securitee_hashes->get_hash($where);
		return $hash_data;
	}
	
	/**
	 * Wrapper for forgot hash check
	 * @param stirng $hash
	 * @param int $ttl
	 * @return mixed
	 */
	public function check_forgot_hash($hash, $ttl)
	{
		return $this->check_hash($hash, $ttl);
	}
	
	/**
	 * Determines if a member's password has expired 
	 * @param int $member_id
	 * @return boolean
	 */
	public function should_member_change_pw($member_id)
	{
		$expire_pw = (int)$this->settings['pw_expire_ttl'];
		if($expire_pw == '0')
		{
			return FALSE;
		}
		
		$hash_data = $this->EE->securitee_hashes->get_hash(array('member_id' => $member_id));
		if(!$hash_data)
		{
			$this->EE->securitee_hashes->add_hash(array('member_id' => $member_id, 'last_changed' => date('Y-m-d H:i:s'), 'hash' => ''));
			$hash_data = $this->EE->securitee_hashes->get_hash(array('member_id' => $member_id));
		}
		
		$last_changed = strtotime($hash_data['last_changed']);
		$max_stamp = mktime(date("H"), date("i"), date("s")-$expire_pw, date("m")  , date("d"), date("Y"));
		
		if($last_changed < $max_stamp)
		{
			return TRUE;
		}
	}
	
	/**
	 * Removes a hash from the data leaving the initial row behind
	 * @param string $hash
	 */
	public function remove_hash($hash)
	{
		$data = array('hash' => '', 'allow_ip' => '0');
		$where = array('hash' => $hash);
		return $this->EE->securitee_hashes->update_hash($data, $where, FALSe);
	}
	
	/**
	 * Deletes the hash belonging to a user from the database
	 * @param unknown_type $member_id
	 */
	public function delete_member_hash($member_id)
	{
		return $this->EE->securitee_hashes->delete_hash(array('member_id' => $member_id));
	}
	
	/**
	 * Updates the hash table's last changed time for password
	 * @param int $member_id
	 * @param string $date
	 */
	public function update_password_change($member_id, $date = FALSE)
	{
		if(!$date)
		{
			$date = date('Y-m-d H:i:s');
		}
		
		$data = array('last_changed' => $date);
		$where = array('member_id' => $member_id);
		return $this->EE->securitee_hashes->update_hash($data, $where, FALSE);
	}	
	
	/**
	 * Takes a hash and determines what the TTL is for it
	 * Basically, if a hash is attached to a user that's visited the site then the Forgot Password TTL is used
	 * If the hash is attached to a user that's NEVER visited the site then the CP Registration TTL is used
	 * @param string $hash
	 */
	public function get_ttl($hash)
	{
		$ttl = $this->settings['pw_ttl'];
		$hash_data = $this->EE->securitee_hashes->get_hash(array('hash' => $hash));
		$data = $this->EE->db->select('last_visit')->from('members')->where('member_id', $hash_data['member_id'])->get()->row_array();
		
		//users never visited the site so use cp_reg_email_expire_ttl
		if(!isset($data['last_visit']) || $data['last_visit'] == '0')
		{
			$ttl = $this->settings['cp_reg_email_expire_ttl'];
		}
		
		return $ttl;
	}
	
	public function validate_change_form(array $val_rules)
	{
		if(isset($val_rules['cur_password']) && isset($val_rules['password_confirm']) && isset($val_rules['password']))
		{
			if(($val_rules['cur_password'] == $val_rules['password_confirm']) && $val_rules['password_confirm'] == $val_rules['password'])
			{
				return array('validate_must_change_password');
			}
		}
		
		return array();
	}
	
	public function guid()
	{
		if (function_exists('com_create_guid'))
		{
			return com_create_guid();
		}
		else
		{
			mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
			$charid = strtoupper(md5(uniqid(rand(), true).$this->EE->config->config['encryption_key']));
			$hyphen = chr(45);// "-"
			$uuid = chr(123)// "{"
			.substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid,12, 4).$hyphen
			.substr($charid,16, 4).$hyphen
			.substr($charid,20,12)
			.chr(125);// "}"
			return $uuid;
		}
	}
	
	public function guidish()
	{
		return strtolower(str_replace(array('}', '{'), '', $this->guid()));
	}
}