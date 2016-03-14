<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UPCReservas_upd {

    var $version = '1.0';


    function install()
	{
	    ee()->load->dbforge();

	    $data = array(
	        'module_name' => 'UPC Reservas' ,
	        'module_version' => $this->version,
	        'has_cp_backend' => 'y',
	        'has_publish_fields' => 'y'
	    );

	    ee()->db->insert('modules', $data);
	
	    $fields = array(
		    'id'   => array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
		    'upc_id'    => array('type' => 'varchar', 'constraint'  => '250'),
		    'name'    => array('type' => 'varchar', 'constraint'  => '250'),
		    'isSport' => array('type' => 'tinyint', 'constraint' => '1', 'default' => 0),
		    'days_available'    => array('type' => 'varchar', 'constraint' => '250', 'null' => TRUE, 'default' => NULL),
		    'min_hour' => array('type' => 'int', 'constraint' => '2', 'default' => 7),
		    'max_hour' => array('type' => 'int', 'constraint' => '2', 'default' => 23)
		    );

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('file_id', TRUE);

		ee()->dbforge->create_table('upc_reservas');

		unset($fields);

		$fields = array(
		    'recurso_id'   => array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE),
		    'entry_id'  => array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE)
		    );

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('file_id', TRUE);
		ee()->dbforge->add_key('entry_id', TRUE);

		ee()->dbforge->create_table('download_posts');
	}

	}
