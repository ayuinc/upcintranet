<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Create_user_db_field_upd {

  var $version = '1.0';

  function install()
	{
    ee()->load->dbforge();

    $data = array(
        'module_name' => 'User database field' ,
        'module_version' => $this->version,
        'has_cp_backend' => 'n',
        'has_publish_fields' => 'n'
    );

    ee()->db->insert('modules', $data);

    $fields = array(
      'terminos_condiciones' => array(
          'type' => 'varchar',
          'constraint' => '5',
          'unsigned' => TRUE,
          'default' => '0',
          'null' => FALSE
      )
    );

    $this->dbforge->add_column('exp_user_upc_data', $fields);
    return TRUE;
  }  
}

