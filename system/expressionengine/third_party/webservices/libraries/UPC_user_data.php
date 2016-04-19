<?php

/**
 * Created by PhpStorm.
 * User: eportillaf
 * Date: 4/19/16
 * Time: 12:31 PM
 */
class UPC_user_data
{
    var $curl_quicks;
    public function __construct(){
        $this->curl_quicks = new Webservices_functions;
        $this->CI =& get_instance();
        $this->EE =& get_instance();
    }

    /**
     * @return mixed
     */
    public function get_user_code(){
        return $_COOKIE[$this->curl_quicks->get_fuzzy_name("Codigo")];
    }

    /**
     * @return string
     */
    public function get_user_token(){
        return $this->get_user_data_field('token');
    }

    /**
     * @return string
     */
    public function get_user_terminos(){
        return $this->get_user_data_field('terminos_condiciones');
    }

    /**
     * @return string
     */
    public function get_user_perfil_type(){
        return $this->get_user_data_field('tipouser');
    }

    /**
     * @param $key
     * @param $code
     * @return string
     */
    private function get_from_db_from_user($key, $code){
        $this->EE->db->select($key);
        $this->EE->db->where('codigo', $code);
        $query_model_result = $this->EE->db->get('exp_user_upc_data');
        $result;
        foreach($query_model_result->result_array() as $row){
            $result = $row[$key];
        }
        return $result;
    }

    /**
     * @return string
     */
    private function get_user_data_field($field)
    {
        $code = $this->get_user_code();
        $data_field = $this->get_from_db_from_user($field, $code);
        return $data_field;
    }

}
/* End of file UPC_user_data.php */
/* Location: ./system/expressionengine/third_party/webservices/libraries/UPC_user_data.php */