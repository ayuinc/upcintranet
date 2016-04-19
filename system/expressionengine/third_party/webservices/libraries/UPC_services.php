<?php

/**
 * Created by PhpStorm.
 * User: eportillaf
 * Date: 4/19/16
 * Time: 12:21 PM
 */
class UPC_services
{
    var $curl_quicks;
    var $user_data;
    public function __construct()
    {

        $this->CI =& get_instance();
        $this->EE =& get_instance();

        $this->curl_quicks = new Webservices_functions;
        $this->user_data = new UPC_user_data;
    }


    /**
     * @return bool
     */
    public function verify_token(){

        if(ee()->config->item('verification_enabled') == TRUE){
            $codigo = $this->user_data->get_user_code();
            $token = $this->user_data->get_user_token();
            if($token !== $_COOKIE[$this->curl_quicks->get_fuzzy_name('Token')]){
                return false;
            }
            $result = $this->curl_quicks->curl_full_url(ee()->config->item('verification_services_url') . '/' . $codigo . '/' . $token, ee()->config->item('verification_user'), ee()->config->item('verification_pwd'));
            $verification_result = json_decode($result, true);
            if ($verification_result['DTOHeader']['CodigoRetorno'] == "Correcto" && count($verification_result['ListaDTOUsuarioToken']) > 0) {
                return true;
            } else {
                return false;
            }
        }else{
            return true;
        }
    }

}
/* End of file UPC_services.php */
/* Location: ./system/expressionengine/third_party/webservices/libraries/UPC_services.php */