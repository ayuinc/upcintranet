<?php

/*
=====================================================
PDF Press
-----------------------------------------------------
 http://www.anecka.com/pdf_press
-----------------------------------------------------
 Copyright (c) 2013 Patrick Pohler
=====================================================
 This software is based upon and derived from
 ExpressionEngine software protected under
 copyright dated 2004 - 2012. Please see
 http://expressionengine.com/docs/license.html
=====================================================
 File: upd.pdf_press.php
-----------------------------------------------------
 Dependencies: dompdf/
-----------------------------------------------------
 Purpose: Allows an EE template to be saved as a PDF
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}

require PATH_THIRD."pdf_press/config.php";

class Pdf_press_upd {

    var $version = PDF_PRESS_VERSION;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
    }

	function install() {
		ee()->load->dbforge();
		
		$data = array(
			'module_name' => PDF_PRESS_NAME,
			'module_version' => PDF_PRESS_VERSION,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'
		);
		
		ee()->db->insert('modules', $data);
		
		$this->install_actions();
		
		$this->install_configs();
		
		return true;
	}
	
	function uninstall() {
		ee()->load->dbforge();
		ee()->db->select('module_id');
		
		$query = ee()->db->get_where('modules', array('module_name' => PDF_PRESS_NAME));
		
	    ee()->db->where('module_id', $query->row('module_id'));
	    ee()->db->delete('modules');

	    ee()->db->where('class', PDF_PRESS_NAME);
	    ee()->db->delete('actions');
	
		ee()->dbforge->drop_table('pdf_press_configs');
		
		return TRUE;
	}
	
	function install_actions() {
		$actions = array(
			array(
				'class'		=> PDF_PRESS_NAME,
				'method'	=> 'create_pdf'
			),
		);
		
		ee()->db->insert_batch('actions', $actions);
	}
	
	function install_configs() {
		$fields = array(
			'id'		=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'key'		=> array('type' => 'varchar', 'constraint' => '500'),
			'data' 		=> array('type' => 'text', 'null' => true)
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('id', TRUE);

		ee()->dbforge->create_table('pdf_press_configs');
	}
	
	
	function update($current = '')
	{
		ee()->load->dbforge();
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		if( version_compare($current, '1.5', '<') ) {
			$this->install_configs();
			return TRUE;
		}

	    return TRUE;
	}
}
?>