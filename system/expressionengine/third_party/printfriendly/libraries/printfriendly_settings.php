<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  Default settings
 *
 * @package		Default
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @license  	http://reinos.nl/add-ons/commercial-license
 * @link        http://reinos.nl/add-ons/gmaps
 * @copyright 	Copyright (c) 2012 Reinos.nl Internet Media
 */

/**
 * Include the config file
 */
require_once(PATH_THIRD.'printfriendly/config.php');

/**
 * Include helper
 */
require_once(PATH_THIRD.'printfriendly/libraries/printfriendly_helper.php');

class Printfriendly_settings {

	private $EE;
	private $_config_items = array();

	private $_config_defaults = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		//set the default settings
		$this->default_settings = array(
			'module_dir'   => PATH_THIRD.PF_MAP.'/',
			'theme_dir'   => PATH_THEMES.'third_party/'.PF_MAP.'/',
			'site_id' => ee()->config->item('site_id'),
			'site_url' => ee()->config->item('site_url'),
			'base_dir' => $_SERVER['DOCUMENT_ROOT'].'/',
		);		
		
		//if older than EE 2.4
		if(!defined('URL_THIRD_THEMES'))
		{
			//set the theme url
			$theme_url = ee()->config->slash_item('theme_folder_url') != '' ? ee()->config->slash_item('theme_folder_url').'third_party/'.PF_MAP.'/' : ee()->config->item('theme_folder_url') .'third_party/'.PF_MAP.'/'; 
			
			//lets define the URL_THIRD_THEMES
			$this->default_settings['theme_url'] = $theme_url;
		}
		else
		{
			//set the Theme dir
			$this->default_settings['theme_url'] = URL_THIRD_THEMES.PF_MAP.'/';
		}
		
		// DB and BASE dependend
		if(isset(ee()->db))
		{
			//is the BASE constant defined?
			if(!defined('BASE'))
			{
				$s = '';

				if (ee()->config->item('admin_session_type') != 'c')
				{
					if(isset(ee()->session))
					{
						$s = ee()->session->userdata('session_id', 0);
					}
				}
				
				//lets define the BASE
				define('BASE', SELF.'?S='.$s.'&amp;D=cp');	
			}
			
			$this->default_settings['base_url'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.PF_MAP;		
			$this->default_settings['base_url_js'] = '&C=addons_modules&M=show_module_cp&module='.PF_MAP;
		}

		//require the settings
		require PATH_THIRD.PF_MAP.'/settings.php';

		//Custom (override) Config vars
		if(!empty($this->overide_settings))
		{
			foreach($this->overide_settings as $key=>$val)
			{
				$this->default_settings[$key] = ee()->config->item($key) != '' ? ee()->config->item($key) : str_replace(array('[theme_dir]', '[theme_url]'), array($this->default_settings['theme_dir'], $this->default_settings['theme_url']), $val);
			}
		}

		//get the settings
		$this->settings = $this->load_settings();
	}

	// ----------------------------------------------------------------

	/**
	 * Insert the settings to the database
	 *
	 * @param none
	 * @return void
	 */
	public function first_import_settings()
	{	
		foreach($this->default_post as $key=>$val)
		{
			$data[] = array(
				'site_id' => $this->default_settings['site_id'],
				'var' => $key,
				'value'=> $val,
			);
		}
		
		//insert into db
		ee()->db->insert_batch(PF_MAP.'_settings', $data);
		
		//clear data
		unset($data);
	}

	// ----------------------------------------------------------------------
	
	/**
	 * Get the Settings
	 *
	 * @param $all_sites
	 * @return mixed array
	 */
	public function load_settings($all_sites = TRUE)
	{
		if (ee()->db->table_exists(PF_MAP.'_settings'))
        {
	        //get the settings from the database
			$get_setting = ee()->db->get_where(PF_MAP.'_settings', array(
				'site_id' => $this->default_settings['site_id']
			));

			//load helper
			ee()->load->helper('string');
			
			//set the settings
			$settings = array();
			foreach ($get_setting->result() as $row)
			{
				//is serialized?
                if(call_user_func(array(PF_MAP.'_helper','is_serialized'), $row->value))
                {
                    $settings[$row->var] = @unserialize($row->value);
                }
                //default value
                else
                {
                    $settings[$row->var] = $row->value;   
                }
			}
			
			//clear data
			unset($get_setting);
			
			//return the settings
			return array_merge($this->default_settings, $settings);
		}		
		else
		{
			return $this->default_settings;
		}
	}

	// ----------------------------------------------------------------------
    
    /**
     * Get specific setting
     *
     * @param $all_sites
     * @return mixed array
     */
    public function get_settings($setting_name)
    {
        if(isset($this->settings[$setting_name]))
        {
            return $this->settings[$setting_name];
        }
        return '';
    }
    //alias
    public function get_setting($setting_name)
    {
    	return $this->get_settings($setting_name);
    }
    //alias
    public function item($setting_name)
    {
    	return $this->get_settings($setting_name);
    }

   // ----------------------------------------------------------------

	/**
	 * Prepare settings to save
	 *
	 * @return 	DB object
	 */
	public function save_post_settings()
	{
		if(isset($_POST))
		{
			//remove submit value
			unset($_POST['submit']);
		
			//loop through the post values
			foreach($_POST as $key=>$val)
			{
				$this->save_setting($key, $val);
			}
		}
		
		//set a message
		ee()->session->set_flashdata(
			'message_success',
			ee()->lang->line('preferences_updated')
		);
		
		//redirect
		ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.PF_MAP.AMP.'method=settings');			
	}

	// ----------------------------------------------------------------

	/**
	 * Prepare settings to save
	 *
	 * @return 	DB object
	 */
	public function save_setting($key = '', $val = '')
	{
		if($key != '' && $val != '')
		{
			//set the where clause
			ee()->db->where('var', $key);
			ee()->db->where('site_id', $this->item('site_id'));
			
			//is this a array?
			if(is_array($val))
			{
				$val = serialize($val);
			}

			//update the record
			ee()->db->update(PF_MAP.'_settings', array(
				'value' => $val
			));		
		}
	}

	// ----------------------------------------------------------------

	/**
	 * set a static setting
	 *
	 * @return 	DB object
	 */
	public function set_setting($key = '', $val = '')
	{
		$this->settings[$key] = $val;
	}
	
}

/* End of file ./libraries/printfriendly_settings.php */