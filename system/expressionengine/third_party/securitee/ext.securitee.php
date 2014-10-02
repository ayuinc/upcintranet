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
 * Securit:ee - Extension
 *
 * Wrapper for the extension methods
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/ext.securitee.php
 */
class Securitee_ext 
{
	/**
	 * Settings
	 * Contains an array of the global Securit:ee settings ...
	 * @var array
	 */
	public $settings = array();

	public $name = 'Securit:ee';
	
	public $version = '1.2.1';
	
	public $description	= 'Securit:ee is a security suite for ExpressionEngine. Includes a file monitor, Control Panel and Client Side IP Locker, Control Panel Login Alert and a Security scanner.';
	
	public $settings_exist	= 'y';
	
	public $docs_url = 'http://mithra62.com/docs/view/securitee-installation'; 
	
	public $required_by = array('module');
		

	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		include PATH_THIRD.'securitee/config'.EXT;
		$this->version = $config['version'];
		$this->name = $config['name'];
				
		$this->EE->load->library('email');	
		$this->EE->lang->loadfile('securitee');
		//$this->EE->load->language('securitee');
	}
	
	/**
	 * Simple redirect to module settings page
	 */
	public function settings_form()
	{
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=securitee'.AMP.'method=settings');
	}
	
	/**
	 * EE Settings object
	 * @param array $session
	 * @return array
	 */
	public function check_ip($session)
	{
		$session = ($this->EE->extensions->last_call != '' ? $this->EE->extensions->last_call : $session);
		$this->EE->load->model('securitee_settings_model', 'securitee_settings', TRUE);	
		$this->EE->load->library('securitee_lib');
		
		//we have to test for the ACT which we pass to the module for IP access processing
		$action_id = $this->EE->input->get_post('ACT', FALSE);
		if($action_id)
		{
			$allow_ip_access_id = $this->EE->securitee_lib->get_module_action('Securitee', 'allow_ip_access');
			if($allow_ip_access_id == $action_id)
			{
				return $session;
			}
		}
		
		//check if within admin
		$this->settings = $this->EE->securitee_lib->settings;
		if(REQ != 'CP')
		{
			if($this->settings['enable_client_ip_locker'] == '1')
			{
				if(!in_array($this->EE->input->ip_address(), $this->settings['allowed_ips']))
				{	
					$this->_proc_ip_block();
				}			
			}
			return $session;	
		}
		
		
		if($this->settings['enable_cp_ip_locker'] != '1')
		{
			return $session;
		}
		
		if(!in_array($this->EE->input->ip_address(), $this->settings['allowed_ips']))
		{	
			$this->_proc_ip_block();
		}
		
		return $session;
	}
	
	/**
	 * Processes the IP Blocking routine
	 */
	private function _proc_ip_block()
	{
		//no template setup so we'll just exit right out of here
		if($this->settings['allow_ip_template'] == '0')
		{
			show_error(lang('unauthorized_ip_access'));
			exit;
		}
		else
		{		
			//check that they're not on the template
			$template_data = $this->EE->securitee_lib->get_template_data($this->settings['allow_ip_template']);
			$change_template = $template_data['group_name'].'/'.$template_data['template_name'];
			$length = strlen($change_template);
			if(substr($this->EE->uri->uri_string, 0, $length) != $change_template)
			{
				$this->EE->load->helper('url');
				$sep = ($this->EE->config->config['word_separator'] == 'dash' ? '-' : '_');
				$url = $change_template;
				redirect('/'.$url);
				exit;
			}
		}
	}	
	
	/**
	 * Logic for CP Login Alert
	 * @param array $user_data
	 * @return array
	 */
	public function alert_cp_login($user_data)
	{
		$user_data = ($this->EE->extensions->last_call != '' ? $this->EE->extensions->last_call : $user_data);
		$this->EE->load->model('securitee_settings_model', 'securitee_settings', TRUE);	
		$this->EE->load->library('securitee_lib');		
		if($this->EE->securitee_lib->settings['enable_cp_login_alert'] == '1')
		{
			$this->send_login_alert($user_data);
		}
		
		return $user_data;
	}
	
	/**
	 * Sends the Login Alert emails
	 * @param array $user_data
	 * @return boolean
	 */
	private function send_login_alert($user_data)
	{
		$this->settings = $this->EE->securitee_lib->settings;
		$to = array();
		foreach($this->settings['login_alert_emails'] AS $email)
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
		$this->EE->email->subject($this->EE->config->config['site_name'].' '.lang('login_alert'));	

		$message = lang('login_alert_notify_message').' <br /><br /><a href="'.$this->EE->config->config['site_url'].'">'.$this->EE->config->config['site_name']."</a>!<br /><br />";
		$message .= "<br />".lang('file_monitor_notify_message_footer');
		
		$user_name = $this->EE->member_model->get_username($user_data->member_id, 'username');
		$replace = array('##username##', '##ip##', '##date##');
		$with = array($user_name, '<a href="http://whatismyipaddress.com/ip/'.$this->EE->input->ip_address().'">'.$this->EE->input->ip_address().'</a>', date('Y-m-d H:i:s'));
		$message = str_replace($replace, $with, $message);
		$this->EE->email->message($message);
		$this->EE->email->send();
	}
	
	public function cp_quick_deny($member_data)
	{
		//return;
		$member_data = ($this->EE->extensions->last_call != '' ? $this->EE->extensions->last_call : $member_data);
		$this->settings = $this->EE->securitee_lib->settings;
		if($this->settings['enable_quick_deny_cp_login'] == '1')
		{			
			//verify the user is either a super admin or is on the allowed groups list
			$this->EE->db->select('group_id')->where('member_id = ', $this->EE->session->userdata('member_id'));
			$query = $this->EE->db->get('members');	
			$_group = $query->row();
			if($_group->group_id != '1' && isset($this->settings['cp_quick_deny_exclude_groups']) && is_array($this->settings['cp_quick_deny_exclude_groups']))
			{			
				if(in_array($_group->group_id, $this->settings['cp_quick_deny_exclude_groups']))
				{
					$this->EE->securitee_lib->proc_logout();				
					show_error($this->EE->lang->line('cp_quick_deny_message_body'), '200', $this->EE->lang->line('cp_quick_deny_message_header'));
				}		
			}			
		}

		return $member_data;
	}
	
	public function check_member_password_expire($session)
	{
		$session = ($this->EE->extensions->last_call != '' ? $this->EE->extensions->last_call : $session);
		
		//sometimes the $session object gets munged up from other addons so let's verify things here
		if(!is_object($session))
		{
			return $session;
		}
		
		$member_id = $session->userdata('member_id');
		if($member_id >= '1')
		{
			//check if we're even enabled first...
			$this->settings = $this->EE->securitee_lib->settings;
			if($this->settings['enable_expiring_passwords'] != '1' || !in_array($session->userdata('group_id'), $this->settings['pw_expire_member_groups']))
			{
				return $session;
			}
			
			$this->EE->load->library('password_lib');
			$this->EE->load->helper('url');
			if(REQ == 'CP')
			{
				$ignore_controllers = array('myaccount', 'javascript', 'login', 'css');
				$ignore_models = array('update_username_password', 'username_password');
				$model = $this->EE->input->get('M', FALSE);
				$controller = $this->EE->input->get('C', FALSE);
				
				//we need to make sure we're not forcing a looping redirect so check certain pages are bypassed from checking
				$check_password = TRUE;
				if(in_array($controller, $ignore_controllers))
				{
					$check_password = FALSE;
					if($controller == 'myaccount' && !in_array($model, $ignore_models))
					{
						$check_password = TRUE;
					}
				}
				
				if($check_password)
				{
					if($this->EE->password_lib->should_member_change_pw($member_id))
					{
						//send member to change password in CP
						$session->set_flashdata('message_failure', $this->EE->lang->line('password_expired'));
						$url = $this->EE->config->config['cp_url'].'?S='.$this->EE->input->get_post('S', '0').'&D=cp&C=myaccount&M=username_password';
						redirect($url);
						exit;	
					}				
				}
				elseif($model == 'update_username_password')
				{
					//member is on update password method so we have to check if their password validates
					$password = $this->EE->input->post('password', FALSE);
					$confirm_password = $this->EE->input->post('password_confirm', FALSE);
					if($password && $confirm_password && ($confirm_password == $password))
					{
						if (!class_exists('EE_Validate'))
						{
							require APPPATH.'libraries/Validate.php';
						}
									
						$val = new EE_Validate(array(
							'member_id'			=> $member_id,
							'val_type'			=> 'update', // new or update
							'require_cpw'		=> FALSE,
							'password'			=> $password,
							'password_confirm'	=> $confirm_password
						));
						
						$val->validate_password();
						if(count($val->errors) == '0')	
						{
							$this->EE->password_lib->update_password_change($member_id);
						}					
					}
				}
			}	
			else
			{
				if($this->EE->password_lib->should_member_change_pw($member_id) && $this->settings['pw_change_template'] != '0')
				{
					$template_data = $this->EE->securitee_lib->get_template_data($this->settings['pw_change_template']);
					$change_template = $template_data['group_name'].'/'.$template_data['template_name'];
					$length = strlen($change_template);
					if(substr($this->EE->uri->uri_string, 0, $length) != $change_template)
					{
						$sep = ($this->EE->config->config['word_separator'] == 'dash' ? '-' : '_');
						//send member to change password on front site
						//$session->set_flashdata('message_failure', $this->EE->lang->line('must_change_password'));
						$url = $change_template.'/expired'.$sep.'password';
						redirect('/'.$url);
						exit;						
					}
				}				
			}
		}
		
		return $session;
	}
	
	public function send_cp_member_email($member_id, $data)
	{
		$this->settings = $this->EE->securitee_lib->settings;
		if($this->settings['enable_cp_member_reg_email'] != '1')
		{
			return;
		}

		$data['member_id'] = $member_id;
		$this->EE->load->library('password_lib');
		$template_data = $this->EE->securitee_lib->get_template_data($this->settings['pw_change_template']);
		$change_template = $template_data['group_name'].'/'.$template_data['template_name'];		
		$this->EE->password_lib->send_email($data, $change_template, 'cp_reg_email_subject', 'cp_reg_email_message_body', 'cp_reg_email_mailtype');
	}
	
	public function send_cp_activate_member_email()
	{
		$members = $this->EE->input->post('toggle');
		$action = $this->EE->input->post('action');
		if($action == 'activate')
		{
			$this->settings = $this->EE->securitee_lib->settings;
			if($this->settings['enable_cp_email_activate'] == '1')
			{
				$this->EE->load->library('password_lib');
				foreach($members AS $member_id)
				{
					$where = array('member_id' => $member_id);
					$this->EE->db->select('*');
					$data = $this->EE->db->from('members')->where($where)->limit('1')->get()->row_array();		
					if(!$data)
					{
						return;
					}

					$data['member_id'] = $member_id;
					$this->EE->load->library('password_lib');
					$template_data = $this->EE->securitee_lib->get_template_data($this->settings['pw_change_template']);
					$change_template = $template_data['group_name'].'/'.$template_data['template_name'];
					$this->EE->password_lib->send_email($data, $change_template, 'cp_reg_email_subject', 'cp_reg_email_message_body', 'cp_reg_email_mailtype');					
				}			
			}		
		}
	}
	
	public function remove_hash_data($member_ids)
	{
		$this->EE->load->library('password_lib');
		foreach($member_ids AS $member_id)
		{
			$this->EE->password_lib->delete_member_hash($member_id);
		}
	}
	
	public function check_member_expire($session)
	{
		$session = ($this->EE->extensions->last_call != '' ? $this->EE->extensions->last_call : $session);
		$member_id = $session->userdata('member_id');
		if($member_id >= '1')
		{
			$group_id = $session->userdata('group_id');
			$this->settings = $this->EE->securitee_lib->settings;
			if($this->settings['enable_expiring_members'] == '1' 
				&& in_array($group_id, $this->settings['member_expire_member_groups'])
				&& $this->settings['member_expire_ttl'] != '0'
			)
			{
				$expire_date = $session->userdata('join_date')+$this->settings['member_expire_ttl'];
				if(time() >= $expire_date)
				{
					show_error(lang('member_account_expired_error'));
					exit;
				}
			}
		}
		
		return $session;
	}
	
	public function activate_extension() 
	{
		return TRUE;
	}
	
	public function update_extension($current = '')
	{
		return TRUE;
	}

	public function disable_extension()
	{
		return TRUE;
	}		
	
	
	
	// END
}