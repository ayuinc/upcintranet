<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Channel FIles Module Control Panel Class
 *
 * @package			DevDemon_ChannelFiles
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com/channel_files/
 * @see				http://expressionengine.com/user_guide/development/module_tutorial.html#control_panel_file
 */
class Channel_files_mcp
{
	/**
	 * Views Data
	 * @var array
	 * @access private
	 */
	private $vData = array();

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		// Creat EE Instance
		$this->EE =& get_instance();

		// Load Models & Libraries & Helpers
		$this->EE->load->library('channel_files_helper');
		//$this->EE->load->model('tagger_model', 'tagger');

		// Some Globals
		$this->base = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=channel_files';
		$this->vData = array('base_url'	=> $this->base); // Global Views Data Array

		$this->EE->channel_files_helper->define_theme_url();

		$this->mcp_globals();

		// Add Right Top Menu
		$this->EE->cp->set_right_nav(array(
			'cf:download_log' 		=> $this->base.'&method=download_log',
			'cf:docs' 				=> $this->EE->cp->masked_url('http://www.devdemon.com/channel_files/docs/'),
		));

		$this->site_id = $this->EE->config->item('site_id');

		// Debug
		//$this->EE->db->save_queries = TRUE;
		//$this->EE->output->enable_profiler(TRUE);
	}

	// ********************************************************************************* //

	function index()
	{
		if (version_compare(APP_VER, '2.5.5', '>')) {
			ee()->view->cp_page_title = $this->EE->lang->line('cf:home');
		} else {
			$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('cf:home'));
		}

		return $this->EE->load->view('mcp_index', $this->vData, TRUE);
	}

	// ********************************************************************************* //

	function download_log()
	{
		// Page Title & BreadCumbs
		if (version_compare(APP_VER, '2.5.5', '>')) {
			ee()->view->cp_page_title = $this->EE->lang->line('cf:download_log');
		} else {
			$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('cf:download_log'));
		}


		$this->vData['logs'] = array();

		return $this->EE->load->view('mcp_download_log', $this->vData, TRUE);
	}

	// ********************************************************************************* //

	function mcp_globals()
	{
		$this->EE->cp->set_breadcrumb($this->base, $this->EE->lang->line('channel_files'));

		//$this->EE->cp->add_js_script(array('plugin' => 'fancybox'));
		//$this->EE->cp->add_to_head('<link type="text/css" rel="stylesheet" href="'.BASE.AMP.'C=css'.AMP.'M=fancybox" />');
		$this->EE->cp->add_js_script( array( 'ui'=> array('datepicker') ) );


		// Add Global JS & CSS & JS Scripts
		$this->EE->channel_files_helper->mcp_meta_parser('gjs', '', 'ChannelFiles');
		$this->EE->channel_files_helper->mcp_meta_parser('css', CHANNELFILES_THEME_URL . 'channel_files_mcp.css', 'ci-pbf');
		//$this->EE->channel_files_helper->mcp_meta_parser('js', CHANNELFILES_THEME_URL . 'jquery.editable.js', 'jquery.editable', 'jquery');
		$this->EE->channel_files_helper->mcp_meta_parser('js',  CHANNELFILES_THEME_URL . 'jquery.dataTables.min.js', 'jquery.dataTables', 'jquery');
		$this->EE->channel_files_helper->mcp_meta_parser('js',  CHANNELFILES_THEME_URL . 'channel_files_mcp.js', 'ci-pbf');

	}

	// ********************************************************************************* //

} // END CLASS

/* End of file mcp.channel_images.php */
/* Location: ./system/expressionengine/third_party/tagger/mcp.channel_images.php */
