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

class Pdf_press_ext {
	var $name	     	= PDF_PRESS_FULL_NAME;
	var $version 		= PDF_PRESS_VERSION;
	var $description	= "Saves a template as a PDF, uses the dompdf library (https://github.com/dompdf/dompdf)";
	var $settings_exist	= 'n';
	var $docs_url		= '';

    var $settings 		= array();
    var $site_id		= 1;

	function __construct($settings = '')
	{
	}

	function activate_extension() {
		$this->_add_pdf_template_hook();

        $this->_add_generate_pdf_hook();
	}

	function _add_pdf_template_hook() {
		$query = ee()->db->get_where('extensions', array('method' => "add_pdf_template_type"));

		if ($query->num_rows() == 0) {
			$hooks = array(
	    		//validate invitation
	    		array(
	    			'hook'		=> 'template_types',
	    			'method'	=> 'add_pdf_template_type',
	    			'priority'	=> 1
	    		),
			);

			foreach ($hooks AS $hook)
	    	{
	    		$data = array(
	        		'class'		=> __CLASS__,
	        		'method'	=> $hook['method'],
	        		'hook'		=> $hook['hook'],
	        		'settings'	=> '',
	        		'priority'	=> $hook['priority'],
	        		'version'	=> $this->version,
	        		'enabled'	=> 'y'
	        	);
	            ee()->db->insert('extensions', $data);
	    	}
		}
	}

	function add_pdf_template_type() {
		$custom_templates = ee()->extensions->last_call;
		//var_dump($custom_templates);

		$custom_templates['pdf'] = array(             // Short name for database
		    'template_name'           => 'PDF',  // Display name for Template Type dropdown
		    'template_file_extension' => '.pdf',       // File extension for saving templates as files
		    'template_headers'        => array(        // Custom headers for file type
		        'Content-Type: application/pdf'
		    )
		);

		return $custom_templates;
	}

    function _add_generate_pdf_hook()
    {
        $data = array(
            'class'		=> __CLASS__,
            'method'	=> 'create_pdf',
            'hook'		=> 'pdf_press_generate_pdf',
            'settings'	=> '',
            'priority'	=> 1,
            'version'	=> $this->version,
            'enabled'	=> 'y'
        );
        ee()->db->insert('extensions', $data);
    }

    function create_pdf($path, $settings) {
        require_once 'mod.pdf_press.php';

        $pdfpress = new Pdf_press();
        return $pdfpress->create_pdf_ext($path, $settings);
    }

	function update_extension($current = '')
    {
    	if ($current == '' OR $current == $this->version)
    	{
    		return FALSE;
    	}

		if( version_compare($current, '2.1', '<') ) {
			$this->_add_pdf_template_hook();
			return TRUE;
		}

        if( version_compare($current, '2.2.4', '<') ) {
            $this->_add_generate_pdf_hook();
            return TRUE;
        }

		return TRUE;
    }

	function disable_extension()
    {
		$data = array(
			'template_type'	=> 'webpage'
		);

		ee()->db->where('template_type', 'pdf');
		ee()->db->update('templates', $data);

    	ee()->db->where('class', __CLASS__);
    	ee()->db->delete('extensions');

        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');
    }

}
