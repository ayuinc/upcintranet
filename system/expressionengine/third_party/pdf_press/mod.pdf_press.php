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
 File: mod.pdf_press.php
-----------------------------------------------------
 Dependencies: dompdf/
-----------------------------------------------------
 Purpose: Allows an EE template to be saved as a PDF
=====================================================
*/

require_once("dompdf/dompdf_config.inc.php");

if (! defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD."pdf_press/config.php";

class Pdf_press {
	var $site_id = 1;

	function __construct() {
		$this->site_id = ee()->config->item('site_id');
		ee()->lang->loadfile('pdf_press');

		if(! class_exists('EE_Template'))
		{
			ee()->TMPL =& load_class('Template', 'libraries', 'EE_');
		}
	}

	public function save_to_pdf_form() {
		/* this method is deprecated, please use save_to_pdf or parse_pdf instead */
		ee()->output->show_user_error('general', $errors);
	}

	public function save_to_pdf() {
		$path = ee()->TMPL->fetch_param('path', ee()->uri->uri_string());
		$attachment = ee()->TMPL->fetch_param('attachment', '1');
		$compress = ee()->TMPL->fetch_param('compress', '1');
		$size = ee()->TMPL->fetch_param('size', DOMPDF_DEFAULT_PAPER_SIZE);
		$orientation = ee()->TMPL->fetch_param('orientation', 'portrait');
		$filename = ee()->TMPL->fetch_param('filename', '');
		$key = ee()->TMPL->fetch_param('key', '');

		$action_id = ee()->functions->fetch_action_id('Pdf_press', 'create_pdf');

		$add_query = "?";
		if(ee()->config->item('force_query_string') == 'y') $add_query = "";

		return ee()->functions->create_url("").$add_query."ACT=$action_id&path=".urlencode($path)."&size=".urlencode($size)."&orientation=$orientation&key=$key&attachment=$attachment&compress=$compress&filename=".urlencode($filename);
	}

	public function parse_pdf() {
		$settings = array(
			'attachment' 	=> ee()->TMPL->fetch_param('attachment', '1'),
			'compress'		=> ee()->TMPL->fetch_param('compress', '1'),
			'orientation' 	=> ee()->TMPL->fetch_param('orientation', 'portrait'),
			'size'			=> ee()->TMPL->fetch_param('size', DOMPDF_DEFAULT_PAPER_SIZE),
			'filename'		=> ee()->TMPL->fetch_param('filename', ''),
			'encrypt'		=> false,
			'userpass'		=> '',
			'ownerpass'		=> '',
			'can_print'		=> true,
			'can_modify'	=> true,
			'can_copy'		=> true,
			'can_add'		=> true,
		);

		//get the key
		$key = ee()->TMPL->fetch_param('key', '');

		try {

			if($key != "") {
				//lookup the key & pull settings, if key not found then throw user error
				$data_settings = $this->_lookup_settings($key);
				//array merge the key with the user parameter overrides
				foreach($data_settings as $field => $value) {
					if($value != null && $value != "")
						$settings[$field] = $value;
				}
			}

			$html = $this->_render(ee()->TMPL->tagdata);

			require_once("dompdf/dompdf_config.inc.php");

			$this->_generate_pdf($html, $settings);
			exit;

		} catch (Exception $e) {
			$check_markup = ee()->lang->line('error_check_markup');
			$dompdf_error =  ee()->lang->line('dompdf_error');

			$errors = array($check_markup,
					$dompdf_error.$e->getMessage());
			ee()->output->show_user_error('general', $errors);
		}
	}

	public function create_pdf() {

		$settings = array(
			'attachment' 	=> ee()->input->get_post('attachment'),
			'compress'		=> ee()->input->get_post('compress'),
			'orientation' 	=> ee()->input->get_post('orientation'),
			'size'			=> urldecode(ee()->input->get_post('size')),
			'filename'		=> urldecode(ee()->input->get_post('filename')),
			'encrypt'		=> false,
			'userpass'		=> '',
			'ownerpass'		=> '',
			'can_print'		=> true,
			'can_modify'	=> true,
			'can_copy'		=> true,
			'can_add'		=> true,
		);

		//get the key
		$key = ee()->input->get_post('key', '');
		$path = urldecode(ee()->input->get_post('path'));
		$filename = $settings['filename'];

		if($filename == "") {
			$filename = str_replace("/", "_", $path).".pdf";
			$settings['filename'] = $filename;
		}

		$full_url = ee()->functions->create_url($path);

		$html = $this->get_url_contents($full_url);

		require_once("dompdf/dompdf_config.inc.php");

		if($key != "") {
			//lookup the key & pull settings, if key not found then throw user error
			$data_settings = $this->_lookup_settings($key);
			//array merge the key with the user parameter overrides
			foreach($data_settings as $field => $value) {
				if($value != null && $value != "")
					$settings[$field] = $value;
			}
		}

		try {
			$this->_generate_pdf($html, $settings);
			exit;

		} catch (Exception $e) {
			$check_markup = ee()->lang->line('error_check_markup');
			$dompdf_error =  ee()->lang->line('dompdf_error');

			$errors = array($check_markup,
					$dompdf_error.$e->getMessage());
			ee()->output->show_user_error('general', $errors);
		}
	}

	public function create_pdf_ext($path, $pdf_settings = array(), $key = "") {
		$full_url = ee()->functions->create_url($path);
		$html = $this->get_url_contents($full_url);

		if($key != "") {
			$data_settings = $this->_lookup_settings($key);
			//array merge the key with the user parameter overrides
			foreach($data_settings as $field => $value) {
				if($value != null && $value != "")
				$pdf_settings[$field] = $value;
			}
		}

		return $this->_generate_pdf($html, $pdf_settings, TRUE);
	}

	public function physical_path() {
		$to_path = ee()->TMPL->fetch_param('to', '');
		return realpath($to_path);
	}

	private function _lookup_settings($key) {
		ee()->db->select('id, key, data');
		ee()->db->where("key = '$key'");
		$query = ee()->db->get("pdf_press_configs");

		if($query->num_rows() > 0) {
			$row = $query->row();
			$setting_data = json_decode($row->data, true);
			$settings = array(
				'attachment' 	=> $setting_data['attachment'],
				'orientation' 	=> $setting_data['orientation'],
				'size'			=> $setting_data['size'],
				'filename'		=> $setting_data['filename'],
				'encrypt'		=> $setting_data['encrypt'],
				'userpass'		=> $setting_data['userpass'],
				'ownerpass'		=> $setting_data['ownerpass'],
				'can_print'		=> $setting_data['can_print'],
				'can_modify'	=> $setting_data['can_modify'],
				'can_copy'		=> $setting_data['can_copy'],
				'can_add'		=> $setting_data['can_add'],
			);

			return $settings;
		} else {

			$errors = array(lang('no_setting_found'), "missing setting preset: '$key'");
			ee()->output->show_user_error('general', $errors);

			//throw new Exception(lang('no_setting_found'));
		}
	}

	private function _generate_pdf($html, $settings, $return_output = FALSE) {

		$dompdf = new DOMPDF();
		$dompdf->load_html($html);
		$dompdf->set_paper($settings['size'], $settings['orientation']);
		//$dompdf->render();

		if($settings['encrypt']) {
			$permissions = array();

			if($settings['can_print'])
				$permissions[] = 'print';

			if($settings['can_modify'])
				$permissions[] = 'modify';

			if($settings['can_copy'])
				$permissions[] = 'copy';

			if($settings['can_add'])
				$permissions[] = 'add';

			$dompdf->get_canvas()->get_cpdf()->setEncryption($settings['userpass'], $settings['ownerpass'], $permissions);
		}

		$options = array();

		if($settings['attachment'] != '') {
			$options['Attachment'] = $settings['attachment'];
		}

		if($settings['compress'] != '') {
			$options['compress'] = $settings['compress'];
		}

		$dompdf->render();

		if($return_output) {
			return $dompdf->output();
		} else {
			if(sizeof($options) > 0) {
				$dompdf->stream($settings['filename'], $options);
			} else {
				$dompdf->stream($settings['filename']);
			}
		}
	}

	private function get_url_contents($url) {
		$fopen_enabled = ini_get('allow_url_fopen');
		/* gets the data from a URL */
		if($fopen_enabled) {
			return file_get_contents($url);
		} else if($this->curl_installed()) {
			//if fopen isn't enabled, then go get the html using curl
			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$data = curl_exec($ch);
			curl_close($ch);
			return $data;
		} else {
			ee()->output->show_user_error('general', ee()->lang->line('error_curl_fopen'));
		}

	}

	//cool function from Veno Designs to render the template in its own context
	//http://venodesigns.net/tag/expressionengine/
	private function _render($text, $opts = array()) {
        /* Create a new EE Template Instance */
        ee()->TMPL = new EE_Template();

        /* Run through the initial parsing phase, set output type */
        ee()->TMPL->parse($text, FALSE);
		ee()->TMPL->final_template = ee()->TMPL->parse_globals(ee()->TMPL->final_template);
        ee()->output->out_type = ee()->TMPL->template_type;

        /* Return source. If we were given opts to do template replacement, parse them in */
        if(count($opts) > 0) {
            ee()->output->set_output(
                ee()->TMPL->parse_variables(
                    ee()->TMPL->final_template, array($opts)
                )
            );
        } else {
            ee()->output->set_output(ee()->TMPL->final_template);
        }
		return ee()->output->final_output;
    }

	private function curl_installed(){
	    return function_exists('curl_version');
	}
}

?>
