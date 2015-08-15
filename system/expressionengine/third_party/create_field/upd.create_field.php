<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Create_field_upd {

  public $version = '1.0';

  private $module_name = "Create field";
  private $EE;
  
  // Constructor
  public function __construct()
  {
      $this->EE =& get_instance();
  }

  public function install()
	{
    // ee()->load->dbforge();
    $this->EE->load->dbforge();

    $data = array(
        'module_name' => $this->module_name,
        'module_version' => $this->version,
        'has_cp_backend' => 'n',
        'has_publish_fields' => 'n'
    );

    $this->EE->db->insert('modules', $data);

    $fields = array(
      'terminos_condiciones' => array(
          'type' => 'varchar',
          'constraint' => '2'
      )
    );

    $this->EE->dbforge->add_column('user_upc_data', $fields);
    
    return TRUE;
  } 

  public function uninstall()
  {

    $this->EE->db->select('module_id');
    $query = $this->EE->db->get_where('modules', 
        array( 'module_name' => $this->module_name )
    );
    
    $this->EE->db->where('module_id', $query->row('module_id'));
    $this->EE->db->delete('module_member_groups');
    
    $this->EE->db->where('module_name', $this->module_name);
    $this->EE->db->delete('modules');
    
    $this->EE->load->dbforge();
    $this->EE->dbforge->drop_column('user_upc_data', 'terminos_condiciones');
    
    return TRUE;
  }

 /**
   * Update the module
   *
   * @return boolean
   */
  public function update($current = '')
  {
    if ($current == $this->version) {
        // No updates
        return FALSE;
    }
    
    return TRUE;
  } 
}

