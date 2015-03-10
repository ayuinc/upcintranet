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
 * Securit:ee - Encryption Fieldtype
 *
 * Contains the Securit:ee Encrypter Fieldtype
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/ft.securitee.php
 */
class Securitee_ft extends EE_Fieldtype 
{
	/**
	 * Details EE needs for the FTP to work
	 * @var array
	 */
	public $info = array(
		'name'		=> 'Securit:ee - Encrypter',
		'version'	=> '1.3.7'
	);
	
	/**
	 * Will the tag be a tagpair
	 * @var bool
	 */
	public $has_array_data = FALSE;
	
	/**
	 * The available "types" of fields the FT will make available
	 * @var array
	 */
	public $field_types = array(
		'password' => 'Password', 
		'input' => 'Input', 
		'textarea' => 'Textarea'
	);
	
	/**
	 * Set everything up
	 */
	public function __construct()
	{
		if (version_compare(APP_VER, '2.1.4', '>')) 
		{ 
			parent::__construct(); 
		} 
		else 
		{ 
			parent::EE_Fieldtype(); 
		}
		
		include PATH_THIRD.'securitee/config'.EXT;
		$this->info['version'] = $config['version'];
		
		$this->EE->load->library('encrypt');
		$this->EE->load->add_package_path(PATH_THIRD.'securitee/');
		$this->EE->load->model('securitee_settings_model', 'securitee_settings', TRUE);
		$this->EE->load->library('encryption_ft');
		$this->EE->encryption_ft->settings = $this->settings;
		$this->EE->lang->loadfile('securitee');
		$this->EE->load->model('field_model');
		$this->EE->load->remove_package_path();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see EE_Fieldtype::display_field()
	 */
	public function display_field($data)
	{
		return $this->EE->encryption_ft->display_field($data, $this->settings, $this->field_name);		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see EE_Fieldtype::replace_tag()
	 */
	public function replace_tag($data)
	{
		if($this->EE->session->userdata('group_id') == '1' || (isset($this->settings['decrypt_access']) && is_array($this->settings['decrypt_access']) && in_array($this->EE->session->userdata('group_id'), $this->settings['decrypt_access'])))
		{
			return $this->EE->encrypt->decode(htmlspecialchars_decode($data));
		}

		return $this->settings['hidden_text'];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see EE_Fieldtype::save()
	 */
	public function save($data)
	{	
		return $this->EE->encryption_ft->save_field($data, $this->settings, $this->field_name);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see EE_Fieldtype::save_settings()
	 */
	public function save_settings()
	{
		return array(
			'decrypt_access'		=> $this->EE->input->post('decrypt_access'),
			'field_max_length'		=> $this->EE->input->post('field_max_length'),
			'display_field_type'	=> $this->EE->input->post('display_field_type'),
			'hidden_text'			=> $this->EE->input->post('hidden_text')
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see EE_Fieldtype::install()
	 */
	public function install()
	{
		return array(
			'decrypt_access' 		=> '',
			'field_max_length' 		=> '128',
			'display_field_type' 	=> 'password',
			'hidden_text'			=> '******'
		);
	}	
	
	/**
	 * (non-PHPdoc)
	 * @see EE_Fieldtype::display_settings()
	 */
	public function display_settings($data)
	{
		$selected = (!isset($data['display_field_type']) || $data['display_field_type'] == '') ? FALSE : $data['display_field_type'];
		$this->EE->table->add_row(
			'<strong>'.lang('display_type').'</strong><div class="subtext">'.lang('display_type_instructions').'</div>',
			form_dropdown('display_field_type', $this->field_types, $selected)
		);	
				
		$field_max_length = (!isset($data['field_max_length']) || $data['field_max_length'] == '') ? 128 : $data['field_max_length'];
		$this->EE->table->add_row(
			'<strong>'.lang('field_max_length').'</strong>',
			form_input(array('id'=>'field_max_length','name'=>'field_max_length', 'size'=>4,'value'=>$field_max_length))
		);

		$member_groups = $this->EE->securitee_settings->get_member_groups();
		$selected = (isset($data['decrypt_access']) && $data['decrypt_access'] != '') ? $data['decrypt_access'] : '******';
		$this->EE->table->add_row(
			'<strong>'.lang('decrypt_access').'</strong><div class="subtext">'.lang('decrypt_access_instructions').'</div>',
			form_multiselect('decrypt_access[]', $member_groups, $selected)
		);

		$hidden_text = (!isset($data['hidden_text']) || $data['hidden_text'] == '') ? '******' : $data['hidden_text'];
		$this->EE->table->add_row(
			'<strong>'.lang('hidden_text').'</strong><div class="subtext">'.lang('hidden_text_instructions').'</div>',
			form_input(array('id'=>'hidden_text','name'=>'hidden_text', 'size'=>4,'value'=>$hidden_text))
		);		
	}	
	
	/**
	 * Matrix display cell
	 * @param string $data
	 */
	public function display_cell($data)
	{
		return $this->EE->encryption_ft->display_field($data, $this->settings, $this->cell_name);
	}
	
	/**
	 * Matrix save cell
	 * @param string $data
	 */
	public function save_cell($data)
	{
		return $this->EE->encryption_ft->save_field($data, $this->settings, $this->cell_name);
	}
	
	/**
	 * Matrix settings wrapper
	 * @param array $data
	 * @return multitype:multitype:NULL string  multitype:string
	 */
	public function display_cell_settings(array $data)
	{
		$settings_data  = array();
		$selected = (!isset($data['display_field_type']) || $data['display_field_type'] == '') ? FALSE : $data['display_field_type'];
		$settings_data[] = array(
			lang('display_type'), form_dropdown('display_field_type', $this->field_types, $selected)
		);
	
		$field_max_length = (!isset($data['field_max_length']) || $data['field_max_length'] == '') ? 128 : $data['field_max_length'];
		$settings_data[] = array(
			lang('field_max_length'), form_input(array('id'=>'field_max_length','name'=>'field_max_length', 'size'=>4,'value'=>$field_max_length))
		);
	
		$member_groups = $this->EE->securitee_settings->get_member_groups();
		$selected = (isset($data['decrypt_access']) && $data['decrypt_access'] != '') ? $data['decrypt_access'] : '******';
		$settings_data[] = array(
			lang('decrypt_access'), form_multiselect('decrypt_access[]', $member_groups, $selected)
		);
	
		$hidden_text = (!isset($data['hidden_text']) || $data['hidden_text'] == '') ? '******' : $data['hidden_text'];
		$settings_data[] = array(
			lang('hidden_text'), form_input(array('id'=>'hidden_text','name'=>'hidden_text', 'size'=>4,'value'=>$hidden_text))
		);
	
		return $settings_data;
	}

	/**
	 * Add the field to the Zenbu display
	 * @param unknown $entry_id
	 * @param unknown $channel_id
	 * @param unknown $data
	 * @param unknown $table_data
	 * @param unknown $field_id
	 * @param unknown $settings
	 * @param unknown $rules
	 * @param unknown $upload_prefs
	 * @param unknown $installed_addons
	 * @return mixed|string
	 */
	public function zenbu_display($entry_id, $channel_id, $data, $table_data, $field_id, $settings, $rules, $upload_prefs, $installed_addons)
	{
		if($data == '')
		{
			$data = '0';
		}
		
		if(isset($table_data[$field_id]['field_data']['field_settings']))
		{
			$settings = unserialize(base64_decode($table_data[$field_id]['field_data']['field_settings']));
			if((isset($settings['decrypt_access']) && is_array($settings['decrypt_access']) && in_array($this->EE->session->userdata('group_id'), $settings['decrypt_access'])))
			{
				return $this->EE->encrypt->decode(htmlspecialchars_decode($data));
			}
	
			return $settings['hidden_text'];
		}		
	
		return 'N/A';
	}
	
	/**
	 * Setup the Zenbu data
	 * @param array $entry_ids
	 * @param array $field_ids
	 * @param int $channel_id
	 * @param bool $output_upload_prefs
	 * @param array $settings
	 * @param array $rel_array
	 * @return array
	 */
	public function zenbu_get_table_data($entry_ids, $field_ids, $channel_id, $output_upload_prefs, $settings, $rel_array)
	{
		$total = count($entry_ids);
		foreach($field_ids AS $key => $value)
		{
			$data[$value]['field_data'] = $this->EE->field_model->get_field($value)->row_array();
		}
	
		return $data;
	}

	/**
	 * (non-PHPdoc)
	 * @see EE_Fieldtype::accepts_content_type()
	 */
	public function accepts_content_type($name)
	{
		return ($name == 'channel' || $name == 'grid');
	}

	/**
	 * Creates the Grid settings panel
	 * @param array $data
	 * @return multitype:string
	 */
	public function grid_display_settings($data)
	{
		$return = array();
		$display_field_type_selected = (!isset($data['display_field_type']) || $data['display_field_type'] == '') ? FALSE : $data['display_field_type'];
		$return[] = $this->grid_dropdown_row(lang('display_type'), 'display_field_type', $this->field_types, $display_field_type_selected);
		
		$field_max_length = (!isset($data['field_max_length']) || $data['field_max_length'] == '') ? 128 : $data['field_max_length'];
		$field_options = array(
			'id'=>'field_max_length',
			'name'=>'field_max_length', 
			'size'=>4,
			'value'=>$field_max_length, 
			'class' => 'grid_input_text_small'
		);
		$return[] = $this->grid_settings_row(lang('field_max_length'), form_input($field_options));
		
		$member_groups = $this->EE->securitee_settings->get_member_groups();
		$decrypt_access_selected = (isset($data['decrypt_access']) && $data['decrypt_access'] != '') ? $data['decrypt_access'] : '******';
		$return[] = $this->grid_dropdown_row(lang('decrypt_access'), 'decrypt_access', $member_groups, $decrypt_access_selected, TRUE);
		
		$hidden_text = (!isset($data['hidden_text']) || $data['hidden_text'] == '') ? '******' : $data['hidden_text'];
		$field_options = array(
				'id'=>'hidden_text',
				'name'=>'hidden_text',
				'size'=>4,
				'value'=>$hidden_text,
				'class' => 'grid_input_text_small'
		);		
		
		$return[] = $this->grid_settings_row(lang('hidden_text'), form_input($field_options));
				
		return $return;
	}	
	
}