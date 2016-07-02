<?php
/**
 * Web services functions - Library
 *
 * Handles connections and cookies
 *
 * @package        Webservices
 * @author        Laboratoria.
 * @copyright    Copyright (c) 2015, Laboratoria.
 * @version        1.0
 */
// ws_helper


class ws_helper
{

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
        $this->cookie_fuzzynames = array('MsgError' => 'grtMsng',
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
            'onLogin' => 'onLogin',
            'closed-alert' => 'closed-alert');

    }

    /**
     * Get Fuzzy name for Cookie
     *
     * @access  public
     * @param string $key - Name of data as key for $_SESSION and cookie
     * @return string
     */
    public function get_fuzzy_name($key)
    {
        return ($this->cookie_fuzzynames[$key] !== NULL) ? $this->cookie_fuzzynames[$key] : $key;
    }

    /**
     * Get Cookie name from Cookkie fuzzy name
     *
     * @access  public
     * @param string $fuzzy - Name of data as key for $_SESSION and cookie
     * @return string
     */
    public function get_unfuzzy_name($fuzzy)
    {
        return (array_search($fuzzy) !== FALSE) ? array_search($fuzzy) : $fuzzy;
    }

    /**
     * Standard call to services
     *
     * @access  public
     * @param string $service_url - Name last part of the url service to be requested
     * @return string
     */
    public function curl_url($service_url)
    {
        $this->base_request_options($service_url);

        $return = $this->EE->curl->execute();

        return $return;
    }

    public function curl_full_url($service_url, $user, $pwd)
    {
        $this->EE->curl->create($service_url);
        $this->EE->curl->option(CURLOPT_SSL_VERIFYPEER, false);
        $this->EE->curl->option(CURLOPT_RETURNTRANSFER, true);
        $this->EE->curl->option(CURLOPT_URL, $service_url);
        $this->EE->curl->option(CURLOPT_TIMEOUT, 45);
        $this->EE->curl->http_login($user, $pwd);
        $return = $this->EE->curl->execute();

        return $return;
    }

    public function curl_url_not_reuse($service_url)
    {
        $this->base_request_options($service_url);
        $this->EE->curl->option(CURLOPT_FORBID_REUSE, 1);
        $this->EE->curl->option(CURLOPT_FRESH_CONNECT, 1);
        $this->EE->curl->option(CURLOPT_TIMEOUT, 45);
        $return = $this->EE->curl->execute();

        return $return;
    }


	/**
     * Post curl to full service url
     *
     * @access  public
     * @param string $services_url Full Service URL
     * @param array $params Post body parameters
     * @return string
     */

	public function curl_post_full_url( $services_url , $params)
	{
		if( $services_url != NULL && $params != NULL)
		{
			
			$return = $this->EE->curl->simple_post($services_url, $params, array(CURLOPT_SSL_VERIFYPEER => false)); 
			return $return;
		}
		return FALSE;
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

	/**
	 * @param $service_url
	 */
	public function base_request_options($service_url)
	{
		$this->EE->curl->create($this->_base_url . $service_url);
		$this->EE->curl->option(CURLOPT_SSL_VERIFYPEER, false);
		$this->EE->curl->option(CURLOPT_RETURNTRANSFER, true);
		$this->EE->curl->option(CURLOPT_URL, $this->_base_url . $service_url);
	}


    /**
     * @param $string
     * @param bool $as_object
     * @return bool|mixed
     */
    public function parse_json($string, $as_object = true)
    {
        $json = json_decode($string, $as_object);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $json;
        } else {
            log_message('error', 'Error parsing json.' . $this->get_json_error(json_last_error()) . ' string (' . $string . ')');
            return false;
        }

    }

    /**
     * @param $error_code
     * @return string
     */
    private function get_json_error($error_code)
    {
        switch ($error_code) {
            case JSON_ERROR_NONE:
                $msg = ' - No errors';
                break;
            case JSON_ERROR_DEPTH:
                $msg = ' - Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $msg = ' - Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $msg = ' - Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $msg = ' - Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $msg = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $msg = ' - Unknown error';
                break;
        }
        return $msg;
    }

    public function upc_log($text, $file)
    {
//        $current = file_get_contents($file);
//        $current .= $text;
//        file_put_contents($file, $current);
    }

}
/* End of file Webservices_functions.php */
/* Location: ./system/expressionengine/third_party/webservices/libraries/Webservices_functions.php */