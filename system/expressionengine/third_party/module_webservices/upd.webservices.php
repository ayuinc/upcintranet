<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UPCReservas_upd {

    var $version = '1.0';


    function install()
	{
	    ee()->load->dbforge();

	    $data = array(
	        'module_name' => 'Webservices' ,
	        'module_version' => $this->version,
	        'has_cp_backend' => 'y',
	        'has_publish_fields' => 'y'
	    );

	    ee()->db->insert('modules', $data);
	


	}

}
