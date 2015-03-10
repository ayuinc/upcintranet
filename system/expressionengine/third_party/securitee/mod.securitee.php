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
 * Securit:ee - Module
 *
 * Wrapper for the module methods
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/mod.securitee.php
 */
class Securitee 
{

	public $return_data	= '';
	
	public function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();		
		
		$this->EE->load->library('logger');
		$this->EE->load->library('email');	
		$this->EE->load->helper('file');	
		$this->EE->load->helper('url');
		
		$this->EE->load->model('securitee_settings_model', 'securitee_settings', TRUE);	
		$this->EE->load->library('securitee_lib');
		$this->EE->load->library('password_lib');
		$this->EE->load->library('ip_locker_lib');
		$this->EE->load->library('file_monitor');	
		$this->EE->lang->loadfile('myaccount');
		$this->settings = $this->EE->securitee_settings->get_settings();
	}
	
	public function void()
	{
		
	}
	
	public function file_monitor()
	{
		ini_set('memory_limit', -1);
		set_time_limit(3600); //limit the time to 1 hours
		$this->EE->file_monitor->scan_site();
		return TRUE;
	}
	
	public function forgot_password()
	{
		$class = $this->EE->TMPL->fetch_param('class', FALSE);
		$change_template = $this->EE->TMPL->fetch_param('change_template', FALSE);
		$form_id = $this->EE->TMPL->fetch_param('form_id', 'forgot_password_form');
		$proc_password = $this->EE->input->get_post('proc_password', FALSE);
		$return = $this->EE->TMPL->fetch_param('return', $this->EE->uri->uri_string.'/sent');
		$secure = $this->EE->TMPL->fetch_param('secure', FALSE);
		
		$errors = array();
		
		if(!$change_template)
		{
			return lang('missing_change_template');
		}

		$email = $this->EE->input->post('email', FALSE);
		$username = $this->EE->input->post('username', FALSE);		
		$return_vars = array(array(
				'email' => $email,
				'error:email' => FALSE,
				'username' => $username,
				'error:username' => FALSE
		));
		
		//check XID
		if($proc_password == 'yes' && $this->EE->security->secure_forms_check($this->EE->input->post('XID')) == FALSE)
		{
			$proc_password = 'no';
		}		
	
		//error checking
		if($proc_password == 'yes')
		{	
			$member_data = array();
			if($email)
			{
				if(!$this->EE->securitee_lib->check_email($email))
				{
					$errors['email'] = 'invalid_email';
				}
				else
				{
					$where = array('email' => $email);
					$member_data = $this->EE->securitee_lib->get_member($where);
				}
			}
			elseif($username)
			{
				$where = array('username' => $username);
				$member_data = $this->EE->securitee_lib->get_member($where);
			}
			
			if(count($member_data) == '0')
			{
				if($username)
				{
					$errors['username'] = 'username_not_exist';
				}
				else
				{
					$errors['email'] = 'email_not_exist';
				}
				
			}	
			else
			{
				$member_data = $member_data['0'];
			}
		}
		
		//no errors so process form
		if($proc_password == 'yes' && count($errors) == '0')
		{		
			if($this->EE->password_lib->send_email($member_data, $change_template))
			{
				if(AJAX_REQUEST)
				{
					$this->EE->output->send_ajax_response(array('success'), FALSE);
				}
				else
				{				
					redirect('/'.$return);
					exit;
				}
			}
			
			//$errors['email'] = 'cant_send';
			$return_vars = $this->_display_errors($return_vars, $errors);
		}
		elseif($proc_password == 'yes' && count($errors) >= '1')
		{				
			$return_vars = $this->_display_errors($return_vars, $errors);		
		}
		
		//setup form
		$output = $this->EE->functions->form_declaration(array(
				'id' => $form_id,
				'class' => $class,
				'action' => $this->EE->functions->create_url($this->EE->uri->uri_string),
				'hidden_fields' => array(
						'proc_password' => 'yes'
				),
		));
	
		//exit;
		$output .= $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $return_vars);
		//exit;
		return $output.'</form>';
	}	
	
	public function allow_ip_form()
	{
		$class = $this->EE->TMPL->fetch_param('class', FALSE);
		$form_id = $this->EE->TMPL->fetch_param('form_id', 'forgot_password_form');
		$proc_form = $this->EE->input->get_post('proc_form', FALSE);
		$return = $this->EE->TMPL->fetch_param('return', $this->EE->uri->uri_string.'/sent');
	
		$errors = array();
		$email = $this->EE->input->post('email', FALSE);
		$username = $this->EE->input->post('username', FALSE);
		$return_vars = array(array(
				'email' => $email,
				'error:email' => FALSE,
				'username' => $username,
				'error:username' => FALSE
		));
	
		//check XID
		if($proc_form == 'yes' && $this->EE->security->secure_forms_check($this->EE->input->post('XID')) == FALSE)
		{
			$proc_form = 'no';
		}
	
		//error checking
		if($proc_form == 'yes')
		{
			$member_data = array();
			if($email)
			{
				if(!$this->EE->securitee_lib->check_email($email))
				{
					$errors['email'] = 'invalid_email';
				}
				else
				{
					$where = array('email' => $email);
					$member_data = $this->EE->securitee_lib->get_member($where);
				}
			}
			elseif($username)
			{
				$where = array('username' => $username);
				$member_data = $this->EE->securitee_lib->get_member($where);
			}
				
			if(count($member_data) == '0')
			{
				if($username)
				{
					$errors['username'] = 'username_not_exist';
				}
				else
				{
					$errors['email'] = 'email_not_exist';
				}
	
			}
			else
			{
				$member_data = $member_data['0'];
				if(!in_array($member_data['group_id'], $this->settings['allow_ip_add_member_groups']))
				{
					$errors['email'] = 'email_not_allowed';
				}
			}
		}
	
		//no errors so process form
		if($proc_form == 'yes' && count($errors) == '0')
		{
			$this->EE->load->library('ip_locker_lib');
			if($this->EE->ip_locker_lib->send_email($member_data))
			{
				if(AJAX_REQUEST)
				{
					$this->EE->output->send_ajax_response(array('success'), FALSE);
				}
				else
				{
					redirect($this->EE->uri->uri_string.'/sent');
					exit;
				}
			}
				
			//$errors['email'] = 'cant_send';
			$return_vars = $this->_display_errors($return_vars, $errors);
		}
		elseif($proc_form == 'yes' && count($errors) >= '1')
		{
			$return_vars = $this->_display_errors($return_vars, $errors);
		}
	
		//setup form
		$output = $this->EE->functions->form_declaration(array(
				'id' => $form_id,
				'class' => $class,
				'action' => $this->EE->functions->create_url($this->EE->uri->uri_string),
				'hidden_fields' => array(
						'proc_form' => 'yes'
				),
		));
	
		//exit;
		$output .= $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $return_vars);
		//exit;
		return $output.'</form>';
	}
		
	public function change_password()
	{
		$class = $this->EE->TMPL->fetch_param('class', FALSE);
		$change_template = $this->EE->TMPL->fetch_param('change_template', FALSE);
		$form_id = $this->EE->TMPL->fetch_param('form_id', 'change_password_form');
		$bad_hash_return = $this->EE->TMPL->fetch_param('bad_hash_return', FALSE);
		$proc_password = $this->EE->input->get_post('proc_password', FALSE);
		$return = $this->EE->TMPL->fetch_param('return', '/');
		$secure = $this->EE->TMPL->fetch_param('secure', FALSE);
		$require_password_confirm = $this->EE->TMPL->fetch_param('require_password_confirm', 'yes');

		//check hash
		$parts = explode('/',$this->EE->uri->uri_string);
		$hash = end($parts);
		
		$member_id = $this->EE->session->userdata('member_id');
		
		$ttl = $this->EE->password_lib->get_ttl($hash);
		$hash_data = $this->EE->password_lib->check_forgot_hash($hash, $ttl);
		
		if(!$hash_data && !$member_id)
		{
			$this->EE->password_lib->remove_hash($hash);
			if(!$bad_hash_return)
			{
				return lang('bad_hash');
			}
			else
			{
				redirect('/'.$bad_hash_return);
				exit;				
			}
		}
		
		if($member_id)
		{
			$hash_data['member_id'] = $member_id;
		}
		
		$return_vars = array(array(
				'error:password' => FALSE
		));	

		if($require_password_confirm == 'yes' && $member_id)
		{		
			$return_vars['0']['require_password_confirm'] = 'yes';
		}
		
		//check XID
		if($proc_password == 'yes' && $this->EE->security->secure_forms_check($this->EE->input->post('XID')) == FALSE)
		{
			$proc_password = 'no';
		}
				
		if($proc_password == 'yes')
		{
			if (!class_exists('EE_Validate'))
			{
				require APPPATH.'libraries/Validate.php';
			}
						
			$val_rules = array(
				'member_id'			=> $hash_data['member_id'],
				'val_type'			=> 'update', // new or update
				'require_cpw'		=> FALSE,
				'password'			=> $this->EE->input->post('password'),
				'password_confirm'	=> $this->EE->input->post('confirm_password')
			);
			
			//only do this for logged in members. Forgot folks get a pass
			if($require_password_confirm == 'yes' && $member_id) 
			{
				$val_rules['cur_password'] = $this->EE->input->post('current_password');
				$val_rules['require_cpw'] = TRUE;
				
			}
			
			$val = new EE_Validate($val_rules);
			
			$val->validate_password();
			
			//check our rules now
			$securitee_validate = $this->EE->password_lib->validate_change_form($val_rules);
			$val->errors = array_merge($securitee_validate, $val->errors);
			
			if(count($val->errors) >= '1')
			{
				$errors['password'] = $val->errors['0'];
				$return_vars = $this->_display_errors($return_vars, $errors);
				if(AJAX_REQUEST)
				{
					$this->EE->output->send_ajax_response(array('success' => 'false'), TRUE);
				}			
			}
			else
			{
				//update stuff
				$this->EE->load->library('auth');
				$this->EE->auth->update_password($hash_data['member_id'], $this->EE->input->post('password'));
				
				//remove hash
				if(!$member_id)
				{
					$this->EE->password_lib->remove_hash($hash);
				}
				
				//update last_changed time
				$this->EE->password_lib->update_password_change($hash_data['member_id']);
				if(AJAX_REQUEST)
				{
					$this->EE->output->send_ajax_response(array('success' => 'true'));
				}
				else
				{
					redirect('/'.$return);
				}
				
				exit;				
			}
		}
		
		$output = $this->EE->functions->form_declaration(array(
				'id' => $form_id,
				'class' => $class,
				'action' => $this->EE->functions->create_url($this->EE->uri->uri_string),
				'hidden_fields' => array(
						'proc_password' => 'yes'
				),
		));
		
		$output .= $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $return_vars);
		//exit;
		return $output.'</form>';		
		//$val = $this-
	}
	
	public function allow_ip_access()
	{
		$hash = $this->EE->input->get('g', FALSE);
		$hash_data = $this->EE->password_lib->check_hash($hash, $this->settings['allow_ip_ttl']);
		
		//get rid of the hash before proceeding
		$this->EE->password_lib->remove_hash($hash);
		if(!$hash_data)
		{
			redirect($this->EE->config->config['site_url']);
			exit;
		}
		
		if($hash_data['allow_ip'] == '1')
		{
			$this->EE->ip_locker_lib->add_ip($this->EE->input->ip_address());
		}
		
		redirect($this->EE->config->config['site_url']);
		exit;		
	}
	
	public function saef_decrypt()
	{
		$entry_id = $this->EE->TMPL->fetch_param('entry_id', FALSE);
		$allow_author = $this->EE->TMPL->fetch_param('allow_author', FALSE);
		$string = $this->EE->TMPL->fetch_param('string', FALSE);
		
		$can_view_decrypt = FALSE;
		if($allow_author == 'yes')
		{
			print_r($this->EE->TMPL->var_single);
		}
		
		if($can_view_decrypt)
		{
			$str = '';
		}
		else
		{
			$str = '';
		}
		exit;
	}
	
	private function _display_errors($return_vars, $errors)
	{
		if(!is_array($errors) || !is_array($return_vars))
		{
			return $this->EE->output->show_user_error(FALSE, array($errors));
		}
	
		if ($this->EE->TMPL->fetch_param('error_handling') != 'inline')
		{
			if(AJAX_REQUEST)
			{
				$ajax_vars['hash'] = $this->EE->securitee_lib->generate_xid();
				$this->EE->output->send_ajax_response($ajax_vars, TRUE);				
			}
						
			$this->EE->output->show_user_error(FALSE, $errors);
		}
	
		$delim = explode('|', $this->EE->TMPL->fetch_param('error_delimiters'));
		if (count($delim) != 2)
		{
			$delim = array('', '');
		}
	
		$ajax_vars = array();
		foreach ($errors as $field_name => $message)
		{
			$return_vars[0]['error:'.$field_name] = $delim[0].lang($message).$delim[1];
			$ajax_vars['error_'.$field_name] = lang($message);
		}
	
		if(AJAX_REQUEST)
		{
			$ajax_vars['hash'] = $this->EE->securitee_lib->generate_xid();
			$this->EE->output->send_ajax_response($ajax_vars, TRUE);
		}
				
		return $return_vars;
	}	
}