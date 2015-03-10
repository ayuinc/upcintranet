<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * Securit:ee - Updater
 *
 * Handles the updating of the module and extension
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/upd.securitee.php
 */
class Securitee_upd { 

    public $version = '1.3.7'; 
    
    public $name = '';
    
    public $class = '';

    public $settings_table = '';

    public $hashes_table = '';
         
    public function __construct() 
    { 
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		$path = dirname(realpath(__FILE__));
		include $path.'/config'.EXT;
		$this->class = $this->name = $config['class_name'];
		$this->settings_table = $config['settings_table'];
		$this->hashes_table = $config['hashes_table'];
		$this->version = $config['version'];
		$this->ext_class_name = $config['ext_class_name'];		
    } 
    
	public function install() 
	{
		$this->EE->load->dbforge();
	
		$data = array(
			'module_name' => $this->name,
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'
		);
	
		$this->EE->db->insert('modules', $data);
		
		$db_prefix = $this->EE->db->dbprefix;
		$sql = "INSERT INTO ".$db_prefix."actions (class, method) VALUES ('".$this->name."', 'file_monitor')";
		$this->EE->db->query($sql);
		
		$sql = "INSERT INTO ".$db_prefix."actions (class, method) VALUES ('".$this->name."', 'allow_ip_access')";
		$this->EE->db->query($sql);		

		$this->add_settings_table();
		$this->add_hashes_table();
		
		$this->activate_extension();
		
		return TRUE;
	} 
	
	private function add_settings_table()
	{
		$this->EE->load->dbforge();
		$fields = array(
						'id'	=> array(
											'type'			=> 'int',
											'constraint'	=> 10,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'auto_increment'=> TRUE
										),
						'setting_key'	=> array(
											'type' 			=> 'varchar',
											'constraint'	=> '30',
											'null'			=> FALSE,
											'default'		=> ''
										),
						'setting_value'  => array(
											'type' 			=> 'longtext',
											'null'			=> TRUE
										),
						'serialized' => array(
											'type' => 'int',
											'constraint' => 1,
											'null' => TRUE,
											'default' => '0'
						)										
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->create_table($this->settings_table, TRUE);		
	}
	
	private function add_hashes_table()
	{
		$this->EE->load->dbforge();
		$fields = array(
				'member_id'	=> array(
						'type'			=> 'int',
						'constraint'	=> 10,
						'unsigned'		=> TRUE,
						'null'			=> FALSE
				),
				'hash'	=> array(
						'type' 			=> 'varchar',
						'constraint'	=> '100',
						'null'			=> FALSE,
						'default'		=> ''
				),
				'allow_ip'	=> array(
						'type' 			=> 'int',
						'constraint'	=> '1',
						'null'			=> FALSE,
						'default'		=> '0'
				),
				'last_changed'	=> array(
						'type' 			=> 'datetime'
				),
				'forgotten_stamp'	=> array(
						'type' 			=> 'datetime'
				)								
		);
	
		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('member_id', TRUE);
		$this->EE->dbforge->create_table($this->hashes_table, TRUE);
	}	

	public function uninstall()
	{
		$this->EE->load->dbforge();
	
		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => $this->class));
	
		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');
	
		$this->EE->db->where('module_name', $this->class);
		$this->EE->db->delete('modules');
	
		$this->EE->db->where('class', $this->class);
		$this->EE->db->delete('actions');
		
		//$this->EE->dbforge->drop_table($this->settings_table);
		//$this->EE->dbforge->drop_table($this->hashes_table);
		
		$this->disable_extension();
	
		return TRUE;
	}

	public function update($current = '')
	{
		if ($current == $this->version)
		{
			return TRUE;
		}
		
		if(version_compare($current, '1.3', '<'))
		{
			$this->add_hashes_table();
		}

		$db_prefix = $this->EE->db->dbprefix;
		if(version_compare($current, '1.3.2', '<'))
		{
			$sql = "INSERT INTO ".$db_prefix."actions (class, method) VALUES ('".$this->name."', 'allow_ip_access')";
			$this->EE->db->query($sql);
			
			$sql = "ALTER TABLE `".$db_prefix."securitee_hashes` ADD `allow_ip` int(1) NOT NULL DEFAULT '0' AFTER `hash` ";
			$this->EE->db->query($sql);			
		}
			
		$this->update_extension();
		
		return TRUE;
	}

	public function activate_extension() 
	{
	
		$data = array();	
		$data[] = array(
					'class'      => 'Securitee_ext',
					'method'    => 'check_ip',
					'hook'  => 'sessions_start',
				
					'settings'    => '',
					'priority'    => 20,
					'version'    => $this->version,
					'enabled'    => 'y'
		);
		
		$data[] = array(
					'class'      => 'Securitee_ext',
					'method'    => 'cp_quick_deny',
					'hook'  => 'cp_member_login',
				
					'settings'    => '',
					'priority'    => 3,
					'version'    => $this->version,
					'enabled'    => 'y'
		);		
		
		$data[] = array(
					'class'      => 'Securitee_ext',
					'method'    => 'alert_cp_login',
					'hook'  => 'cp_member_login',
				
					'settings'    => '',
					'priority'    => 5,
					'version'    => $this->version,
					'enabled'    => 'y'
		);	

		$data[] = array(
					'class'      => 'Securitee_ext',
					'method'    => 'remove_hash_data',
					'hook'  => 'cp_members_member_delete_end',
					'settings'    => '',
					'priority'    => 19,
					'version'    => $this->version,
					'enabled'    => 'y'
		);	
		
		$data[] = array(
				'class'      => 'Securitee_ext',
				'method'    => 'check_member_password_expire',
				'hook'  => 'sessions_end',
		
				'settings'    => '',
				'priority'    => 2,
				'version'    => $this->version,
				'enabled'    => 'y'
		);	
		
		$data[] = array(
				'class'      => 'Securitee_ext',
				'method'    => 'send_cp_member_email',
				'hook'  => 'cp_members_member_create',
		
				'settings'    => '',
				'priority'    => 45,
				'version'    => $this->version,
				'enabled'    => 'y'
		);	

		$data[] = array(
				'class'      => 'Securitee_ext',
				'method'    => 'check_member_expire',
				'hook'  => 'sessions_end',
		
				'settings'    => '',
				'priority'    => 4,
				'version'    => $this->version,
				'enabled'    => 'y'
		);

		$data[] = array(
				'class'      => 'Securitee_ext',
				'method'    => 'send_cp_activate_member_email',
				'hook'  => 'cp_members_validate_members',
		
				'settings'    => '',
				'priority'    => 20,
				'version'    => $this->version,
				'enabled'    => 'y'
		);		
		
		foreach($data AS $ex)
		{
			$this->EE->db->insert('extensions', $ex);	
		}
	}
	
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			//return FALSE;
		}
		
		if(version_compare($current, '1.2', '<='))
		{
			$data = array(
						'class'      => 'Securitee_ext',
						'method'    => 'cp_quick_deny',
						'hook'  => 'cp_member_login',
					
						'settings'    => '',
						'priority'    => 3,
						'version'    => $this->version,
						'enabled'    => 'y'
			);
			$this->EE->db->insert('extensions', $data);			
		}
		
		if(version_compare($current, '1.3.1', '<='))
		{
			$data = array();
			$data[] = array(
						'class'      => 'Securitee_ext',
						'method'    => 'remove_hash_data',
						'hook'  => 'cp_members_member_delete_end',
						'settings'    => '',
						'priority'    => 19,
						'version'    => $this->version,
						'enabled'    => 'y'
			);	
			
			$data[] = array(
					'class'      => 'Securitee_ext',
					'method'    => 'check_member_password_expire',
					'hook'  => 'sessions_end',
			
					'settings'    => '',
					'priority'    => 45,
					'version'    => $this->version,
					'enabled'    => 'y'
			);
			
			$data[] = array(
					'class'      => 'Securitee_ext',
					'method'    => 'send_cp_member_email',
					'hook'  => 'cp_members_member_create',
			
					'settings'    => '',
					'priority'    => 99,
					'version'    => $this->version,
					'enabled'    => 'y'
			);

			$data[] = array(
					'class'      => 'Securitee_ext',
					'method'    => 'check_member_expire',
					'hook'  => 'sessions_end',
			
					'settings'    => '',
					'priority'    => 100,
					'version'    => $this->version,
					'enabled'    => 'y'
			);			
			
			foreach($data AS $ex)
			{
				$this->EE->db->insert('extensions', $ex);
			}
		}
		
		if(version_compare($current, '1.3.4', '<='))
		{
			$data = array();		
			$data[] = array(
					'class'      => 'Securitee_ext',
					'method'    => 'send_cp_activate_member_email',
					'hook'  => 'cp_members_validate_members',
			
					'settings'    => '',
					'priority'    => 20,
					'version'    => $this->version,
					'enabled'    => 'y'
			);
			foreach($data AS $ex)
			{
				$this->EE->db->insert('extensions', $ex);
			}			
		}
				
			
		$this->EE->db->where('class', 'Securitee_ext');
		$this->EE->db->update(
					'extensions', 
					array('version' => $this->version)
		);
	}

	public function disable_extension()
	{
		$this->EE->db->where('class', 'Securitee_ext');
		$this->EE->db->delete('extensions');
	}	
    
}