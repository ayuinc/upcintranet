<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /**
 * mithra62 - Securit:ee
 *
 * @package		mithra62:Securitee
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2013, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/securit-ee/
 * @version		1.2.1
 * @filesource 	./system/expressionengine/third_party/securitee/
 */
 
 /**
 * Securit:ee - Encryption FieldType Library
 *
 * Contains the Encryption FieldType methods and logic
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/libraries/Encryption_ft.php
 */
class Encryption_ft
{	
	public $settings = array();
	
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	public function display_field($data, $settings, $ft_name)
	{	
		$field = '';
		if(isset($settings['field_settings']))
		{
			$field_settings = unserialize(base64_decode($settings['field_settings']));
			if(count($field_settings) == '0')
			{
				$field_settings = $settings;
			}
		}
		else
		{
			$field_settings = $settings;
		}
		
		$field_settings = $settings;
		if($data != '')
		{
			if($this->EE->session->userdata('group_id') != '1' && (isset($field_settings['decrypt_access']) || is_array($field_settings['decrypt_access']) || in_array($this->EE->session->userdata('group_id'), $field_settings['decrypt_access'])))
			{
				$field .= form_hidden('_encrypt_orig_'.$ft_name, $data);
			}
		}
		
		$options = array('name'	=> $ft_name,'id' => $ft_name);
		if($this->EE->session->userdata('group_id') == '1' || (isset($field_settings['decrypt_access']) && is_array($field_settings['decrypt_access']) && in_array($this->EE->session->userdata('group_id'), $field_settings['decrypt_access'])))
		{
			$options['value'] = $this->EE->encrypt->decode(htmlspecialchars_decode($data));
		}
		else
		{
			if($data != '')
			{
				$options['value'] = $field_settings['hidden_text'];
			}
		}
		
		switch($field_settings['display_field_type'])
		{
			case 'password':
				$field .= form_password($options);
				break;
					
			case 'textarea':
				$field .= form_textarea($options);
				break;
		
			case 'input':
				$field .= form_input($options);
				break;
		}
		
		return $field;		
	}
	
	public function save_field($data, $settings, $field_name)
	{
		if($settings['hidden_text'] != $data)
		{
			return $this->EE->encrypt->encode($data);
		}
		
		$default = $this->EE->input->post('_encrypt_orig_'.$field_name);
		if(($settings['hidden_text'] == $data) && $default)
		{
			return $default;
		}
		
		return $this->EE->encrypt->encode($data);		
	}
	
}