<?php 
/**
 * Web services functions - Library
 *
 * Handles how code packs are read and installed
 *
 * @package		Webservices
 * @author		Laboratoria.
 * @copyright	Copyright (c) 2015, Laboratoria.
 * @version		1.0
 */

class Webservices_functions {

		protected $_base_url; // Base url for services from EE config 'web_services_url'
		protected $_site_url; // Base url for site

		protected $_cookies_prefix; // Get the cookie prefix from config, for stuff. No questions ask.

		public function __construct()
		{
			$this->CI =& get_instance();
		 	$this->EE =& get_instance();
			$this->_base_url = $this->EE->config->item('web_services_url');
			$this->_site_url = $this->EE->config->item('site_url');
			$this->_cookies_prefix = $this->EE->config->item('cookie_prefix');
   			$this->EE->load->library('curl');
   			$this->EE->load->helper('cookie');
			
		}
		
		public function curl_url($service_url) 
		{
			// Standard call to service.
			$this->EE->curl->create($this->_base_url.$service_url);
			$this->EE->curl->option(CURLOPT_SSL_VERIFYPEER, false);
			$this->EE->curl->option(CURLOPT_RETURNTRANSFER, true);
			$this->EE->curl->option(CURLOPT_URL, $this->_base_url.$service_url);
			return $this->EE->curl->execute();
		}

		public function curl_url_not_reuse( $service_url ) 
		{
			// Initial call to service. 
			$this->EE->curl->create($this->_base_url.$service_url);
			$this->EE->curl->option(CURLOPT_SSL_VERIFYPEER, false);
			$this->EE->curl->option(CURLOPT_RETURNTRANSFER, true);
			$this->EE->curl->option(CURLOPT_URL, $this->_base_url.$service_url);
			$this->EE->curl->option(CURLOPT_FORBID_REUSE, 1);
			$this->EE->curl->option(CURLOPT_FRESH_CONNECT, 1);
			return $this->EE->curl->execute();
		}

		public function set_cookie ( $name,  $value,  $expire = 0 ,  $path = "/" ,   $domain = ".upc.edu.pe" ,  $prefix = "",  $secure = TRUE )
		{
			// validate domain, just in case
			if($domain == "" OR is_null($domain)){
				$domain  = $this->_site_url;
			}
			$this->EE->input->set_cookie($name, $value, $expire, $domain, $path, "", $secure);
		}

		public function get_cookie ($cookie)
		{
			// Adding the prefix so original code stays "clean"
			$this->EE->input->get_cookie($this->_cookies_prefix.$cookie); 
		}

		public function delete_cookie($cookie)
		{	
			// Adding the prefix so original code stays "clean"
			$this->EE->input->delete_cookie($cookie);
		}

}

?>