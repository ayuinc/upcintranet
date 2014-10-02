<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Detour Pro Module Control Panel File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Mike Hughes - City Zen
 * @link		http://cityzen.com
 */
 
class Detour_pro_mcp {
	
	public $return_data;
	public $return_array = array();
	
	private $_base_url;
	private $_data = array();
	private $_module = 'detour_pro';
	private $_detour_methods = array(
      '301'  => '301',
      '302'    => '302'
    );
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->_base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=detour_pro';
		
		$this->EE->cp->set_right_nav(array(
			'module_home'	=> $this->_cp_url(),
			'Add Detour'		=> $this->_cp_url('advanced'),  
			// 'Import'		=> $this->_cp_url('import'), 
			// 'Settings' 		=> $this->_cp_url('settings'),
			'Purge Hit Counter' => $this->_cp_url('purge_hits'), 
			'Documentation'			=> 'http://www.cityzen.com/addons/detour-pro' 
			// Add more right nav items here.
		));

		$this->EE->view->cp_page_title = lang('detour_pro_module_name');
	}
	
	// ----------------------------------------------------------------

	//! Index View and Save

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
		$ext = $this->EE->db->get_where('extensions', array('class' => 'Detour_pro_ext'))->row_array();
		
		if(!empty($ext) && $ext['enabled'] == 'n')
		{
			$this->EE->db->where('class', 'Detour_pro_ext');
			$this->EE->db->update('extensions', array('enabled'=>'y'));
		}
		
		$this->EE->load->library('table');
		
		$this->EE->cp->add_to_head("<link rel='stylesheet' href='".$this->EE->config->item('theme_folder_url') ."third_party/detour_pro/css/detour.css'>");
		
		$this->EE->cp->load_package_js('jquery.dataTables.min'); 
		$this->EE->cp->add_js_script(
			array(
				'ui' => array(
					'datepicker'
				)
			)
		);	
		$this->EE->javascript->output(array(
			'$(".padTable").dataTable( {
				"iDisplayLength": 100
			} );', 
			'$(".datepicker").datepicker({ dateFormat: \'yy-mm-dd\' });', 
			'$("#original_url").focus();'
			)
		);
		
		$this->_data['action_url'] = $this->_form_url('save_detour');
		
		$this->_data['detour_options'] = array(
			'detour' => $this->EE->lang->line('option_detour'),
			'ignore' => $this->EE->lang->line('option_ignore'),
		);
		
		$this->_data['detour_methods'] = $this->_detour_methods;
		$this->_data['current_detours'] = $this->get_detours();
		
		return $this->EE->load->view('index', $this->_data, TRUE);
	}
	
	public function save_detour()
	{	
		if( ($_POST['original_url']) && ($_POST['new_url']) ){
			
			// clean original url
			$original_url = trim($_POST['original_url'], '/');
			$new_url = trim($_POST['new_url'], '/');
		
			$start_date = ($_POST['start_date'] && !array_key_exists('clear_start_date', $_POST)) ? $_POST['start_date'] : null;
			$end_date = ($_POST['end_date'] && !array_key_exists('clear_end_date', $_POST)) ? $_POST['end_date'] : null;
		
			$data = array(
			'original_url' => $original_url,
			'new_url' => $new_url, 
			'detour_method' => $_POST['detour_method'], 
			'site_id' => $this->EE->config->item('site_id'), 
			'start_date' => $start_date, 
			'end_date' => $end_date
			);
	
			$this->EE->db->insert('exp_detours', $data);
			
		}
		
		// If anything is set for deletion, delete it
		if(!empty($_POST['detour_delete'])){
			
			foreach($_POST['detour_delete'] as $detour_id)
			{
				$this->EE->db->delete('detours', array('detour_id' => $detour_id));
				$this->EE->db->where('detour_id', $detour_id);
				$this->EE->db->delete('detours_hits');
			}			
		}
		
		// Redirect back to Detour Pro landing page
		$this->EE->functions->redirect($this->_base_url);
	}

	//! Advanced View and Save
	
	public function advanced()
	{
		$this->EE->cp->add_js_script(
			array(
				'ui' => array(
					'core', 'datepicker'
				)
			)
		);

		$this->EE->javascript->output(array(
				'$( ".datepicker" ).datepicker({ dateFormat: \'yy-mm-dd\' });'
			)
		);
		
		$this->_data['id'] = $this->EE->input->get_post('id') ? $this->EE->input->get_post('id') : null;
		
		if($this->EE->input->get_post('id'))
		{
			$detour = $this->get_detours($this->EE->input->get_post('id'));
			$this->EE->db->select('COUNT(*) as total');
			$hits = $this->EE->db->get_where('detours_hits', array('detour_id' => $detour['detour_id']))->result_array();
		}

		
		$this->_data['original_url'] = (isset($detour)) ? $detour['original_url'] : '';
		$this->_data['new_url'] = (isset($detour)) ? $detour['new_url'] : '';
		$this->_data['detour_method'] = (isset($detour)) ? $detour['detour_method'] : '';
		$this->_data['detour_hits'] = (isset($detour)) ? $hits[0]['total'] : '';
		$this->_data['start_date'] = (isset($detour)) ? $detour['start_date'] : '';
		$this->_data['end_date'] = (isset($detour)) ? $detour['end_date'] : '';
		$this->_data['detour_methods'] = $this->_detour_methods;

		$this->EE->load->library('table');
		$this->_data['action_url'] = $this->_form_url('save_detour_advanced');	
		return $this->EE->load->view('advanced', $this->_data, TRUE);
	}
	
	public function save_detour_advanced()
	{		
	
		// clean original url
		$original_url = trim($_POST['original_url'], '/');
		$new_url = trim($_POST['new_url'], '/');
	
		$start_date = ($_POST['start_date'] && !array_key_exists('clear_start_date', $_POST)) ? $_POST['start_date'] : null;
		$end_date = ($_POST['end_date'] && !array_key_exists('clear_end_date', $_POST)) ? $_POST['end_date'] : null;
	
		$data = array(
			'original_url' => $original_url,
			'new_url' => $new_url, 
			'detour_method' => $_POST['detour_method'], 
			'site_id' => $this->EE->config->item('site_id'), 
			'start_date' => $start_date, 
			'end_date' => $end_date
		);
			
		if($_POST['original_url'] && $_POST['new_url'] && !array_key_exists('id', $_POST))
		{
			$this->EE->db->insert('detours', $data);
		}
		elseif($_POST['original_url'] && $_POST['new_url'] && array_key_exists('id', $_POST) && $_POST['id'])
		{
			$this->EE->db->update('detours', $data, 'detour_id = ' . $_POST['id']);	
		}
		
		// Redirect back to Detour Pro landing page
		$this->EE->functions->redirect($this->_base_url);
	}

	public function purge_hits()
	{
		$this->_data['total_detour_hits'] = $this->EE->db->count_all_results('detours_hits');
		$this->_data['action_url'] = $this->_form_url('do_purge_hits');
		return $this->EE->load->view('purge_hits', $this->_data, TRUE);
	}

	public function do_purge_hits()
	{
		$this->EE->db->empty_table('detours_hits');
		$this->EE->functions->redirect($this->_base_url);
	}
	
	//! Settings View and Save

/*	
	Might add later...

	public function settings()
	{
		$this->EE->load->library('table');
		$this->_data['action_url'] = $this->_form_url('save_settings');
		return $this->EE->load->view('settings', $this->_data, TRUE);
	}
	
	public function save_settings()
	{
		
	}
*/	
	//! Private Methods
	
	private function get_detours($id='')
	{
		$vars = array(
			'site_id' => $this->EE->config->item('site_id'), 
		);
		
		if($id)
		{
			$vars['detour_id'] = $id; 
		}
		
		if(!array_key_exists('detour_id', $vars))
		{
			
			$this->EE->db->select('*');
			$this->EE->db->select('DATE_FORMAT(start_date, \'%m/%d/%Y\') AS start_date', FALSE); 
			$this->EE->db->select('DATE_FORMAT(end_date, \'%m/%d/%Y\') AS end_date', FALSE); 
			$current_detours = $this->EE->db->get_where('detours', $vars)->result_array();
			
			foreach($current_detours as $value)
			{
				extract($value);
				
				$this->return_array[] = array(
					'original_url' => $original_url, 
					'new_url' => $new_url, 
					'start_date' => $start_date, 
					'end_date' => $end_date, 
					'detour_id' => $detour_id, 
					'detour_method' => $detour_method, 
					'advanced_link' => $this->_cp_url('advanced', array('id'=>$detour_id))
				);
			}		
		}
		else
		{
			$this->return_array = $this->EE->db->get_where('detours', $vars)->row_array();
		}
		
		return $this->return_array;
	}
	
	//! Linking Methods 
	
	private function _cp_url ($method = 'index', $variables = array()) {
		$url = BASE . AMP . 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=' . $this->_module . AMP . 'method=' . $method;
		
		foreach ($variables as $variable => $value) {
			$url .= AMP . $variable . '=' . $value;
		}
		
		return $url;
	}
	
	private function _form_url ($method = 'index', $variables = array()) {
		$url = 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=' . $this->_module . AMP . 'method=' . $method;
		
		foreach ($variables as $variable => $value) {
			$url .= AMP . $variable . '=' . $value;
		}
		
		return $url;
	}
	
	private function _member_link ($member_id) {
		// if they are anonymous, they don't have a member link
		if (strpos($member_id,'anon') !== FALSE) {
			return FALSE;
		}
	
		$url = BASE . AMP . 'D=cp' . AMP . 'C=myaccount' . AMP . 'id='. $member_id;
		
		return $url;
	}

	/**
	 * Start on your custom code here...
	**/


	
}
/* End of file mcp.detour_pro.php */
/* Location: /system/expressionengine/third_party/detour_pro/mcp.detour_pro.php */