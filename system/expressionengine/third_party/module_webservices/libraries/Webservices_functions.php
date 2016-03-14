<?php 
/**
 * Web services functions - Library
 *
 * Handles connections and cookies
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
	protected $cookie_fuzzynames;
	public function __construct()
	{
		$this->CI =& get_instance();
	 	$this->EE =& get_instance();
		$this->_base_url = $this->EE->config->item('web_services_url');
		$this->_site_url = $this->EE->config->item('site_url');
		$this->_cookies_prefix = $this->EE->config->item('cookie_prefix');
			$this->EE->load->library('curl');
			$this->EE->load->helper('cookie');
		$this->cookie_fuzzynames = array( 'MsgError' => 'grtMsng',
                          'Nombres' => 'fnyNose',
                          'Apellidos' => 'fnyFrom',
                          'Estado' => 'lstStar',
                          'CodLinea' => 'subLim',
                          'CodModal' => 'subMil',
                          'DscModal' => 'sunLis',
                          'CodSede' => 'subSest',
                          'DscSede' => 'sunSort',
                          'Ciclo' => 'lstYear',
                          'Token' => 'Token',
                          'Codigo' => 'Codigo',
                          'Terms' => 'Terms',
                          'onLogin'=> 'onLogin',
                          'closed-alert' => 'closed-alert');
		
	}
	/**
     * Get Fuzzy name for Cookie
     *
     * @access  public
     * @param string $key Name of data as key for $_SESSION and cookie
     * @return 
     */
    public function get_fuzzy_name($key)
    {
      return ($this->cookie_fuzzynames[$key]!== NULL)?$this->cookie_fuzzynames[$key]: $key;
    }

    /**
     * Get Fuzzy name for Cookie
     *
     * @access  public
     * @param string $key Name of data as key for $_SESSION and cookie
     * @return 
     */
    public function get_unfuzzy_name($fuzzy)
    {
      return ($array_search($fuzzy)!= FALSE)?$array_search($fuzzy):$fuzzy;
    }

	public function curl_url($service_url) 
	{
		// var_dump($this->_base_url.$service_url);
		// Standard call to service.
		$this->EE->curl->create($this->_base_url.$service_url);
		$this->EE->curl->option(CURLOPT_SSL_VERIFYPEER, false);
		$this->EE->curl->option(CURLOPT_RETURNTRANSFER, true);
		$this->EE->curl->option(CURLOPT_URL, $this->_base_url.$service_url);
		$return = $this->EE->curl->execute();
		// var_dump($return);
		return $return;
	}

	public function curl_full_url($service_url, $user, $pwd) 
	{
		// var_dump($service_url.' USER '.$user.' PWD '.$pwd);
		// Standard call to service.
		$this->EE->curl->create($service_url);
		$this->EE->curl->option(CURLOPT_SSL_VERIFYPEER, false);
		$this->EE->curl->option(CURLOPT_RETURNTRANSFER, true);
		$this->EE->curl->option(CURLOPT_URL, $service_url);
		$this->EE->curl->http_login($user, $pwd);
		$return = $this->EE->curl->execute();
		// var_dump('Return'.$return);
		return $return;
	}

	public function curl_url_not_reuse( $service_url ) 
	{
		// var_dump($this->_base_url.$service_url);
		// Initial call to service. 
		$this->EE->curl->create($this->_base_url.$service_url);
		$this->EE->curl->option(CURLOPT_SSL_VERIFYPEER, false);
		$this->EE->curl->option(CURLOPT_RETURNTRANSFER, true);
		$this->EE->curl->option(CURLOPT_URL, $this->_base_url.$service_url);
		$this->EE->curl->option(CURLOPT_FORBID_REUSE, 1);
		$this->EE->curl->option(CURLOPT_FRESH_CONNECT, 1);
		$return = $this->EE->curl->execute();
		// var_dump($return);
		return $return;
	}

	public function set_cookie ( $name,  $value,  $expire = 0 ,  $path = "/" ,   $domain = ".upc.edu.pe" ,  $prefix = "",  $secure = TRUE )
	{
		// validate domain, just in case
		if($domain == "" OR is_null($domain)){
			$domain  = $this->_site_url;
		}
		// setcookie($name, $value, time() + (1800), '/');
		// setcookie($this->get_fuzzy_name($name), $value, time() + (1800), '/'); 
		setcookie($this->get_fuzzy_name($name), $value, time() + (1800), '/', '.upc.edu.pe',false);  
	}

	public function get_cookie ($cookie)
	{
		// Adding the prefix so original code stays "clean"
		$this->EE->input->get_cookie($cookie); 
	}

	public function delete_cookie($cookie)
	{	
		// Adding the prefix so original code stays "clean"
		$this->EE->input->delete_cookie($cookie);
	}
}
/* End of file Webservices_functions.php */
/* Location: ./system/expressionengine/third_party/webservices/Webservices_functions.php */