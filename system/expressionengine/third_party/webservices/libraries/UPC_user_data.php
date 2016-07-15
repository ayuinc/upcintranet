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
        $this->curl_quicks = new ws_helper;
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
     * @return mixed
     */
    public function get_full_user_code(){
        return $_COOKIE[$this->curl_quicks->get_fuzzy_name("CodigoAlumno")];
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
     * @return string
     */
    public function get_user_linea(){
        return $_COOKIE[$this->curl_quicks->get_fuzzy_name("CodLinea")];
    }

    /**
     * @return string
     */
    public function get_user_modalidad(){
        return $_COOKIE[$this->curl_quicks->get_fuzzy_name("CodModal")];
    }

    /**
     * @return string
     */
    public function get_ciclo(){
        return  $_COOKIE[$this->curl_quicks->get_fuzzy_name("Ciclo")];
    }

    /**
     * @return string
     */
    public function get_codigo_persona(){
        return $this->get_user_data_field('codigopersona');
    }
    /**
     * @return string
     */
    public function get_user_apellido_paterno(){
        return $this->get_user_data_field('apellidoPaterno');
    }

    /**
     * @return string
     */
    public function get_user_apellido_materno(){
        return $this->get_user_data_field('apellidoMaterno');
    }

    /**
     * @return string
     */
    public function get_user_nombres(){
        return $this->get_user_data_field('nombres');
    }
    /**
     * @param string
     */
    public function set_codigo_persona($codigopersona){
        if($codigopersona != null){
            $this->set_to_db_from_user('codigopersona', $codigopersona, $this->get_user_code());
        }
    }

    /**
     * @param string apellido
     */
    public function set_apellido_paterno($apellido){
        if($apellido != null && trim($apellido)!= ""){
            $this->set_to_db_from_user('apellidoPaterno',  ucwords(strtolower($apellido)), $this->get_user_code());
        }
    }

    /**
     * @param string apellido
     */
    public function set_apellido_materno($apellido){
        if($apellido != null && trim($apellido) != ""){
            $this->set_to_db_from_user('apellidoMaterno',  ucwords(strtolower($apellido)), $this->get_user_code());
        }
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
        $result = "";
        foreach($query_model_result->result_array() as $row){
            $result = $row[$key];
        }
        return $result;
    }

    /**
     * @param $field - field to be retrieved
     * @return string
     */
    private function get_user_data_field($field)
    {
        $code = $this->get_user_code();
        $data_field = $this->get_from_db_from_user($field, $code);
        return $data_field;
    }

    /**
     * @param $key
     * @param $code
     * @param $value
     * @return string
     */
    private function set_to_db_from_user($key, $value,  $code){

        $user_upc_update = array(
            $key => $value
        );
        ee()->db->where('codigo', $code);
        ee()->db->update('exp_user_upc_data', $user_upc_update);

    }

}
/* End of file UPC_user_data.php */
/* Location: ./system/expressionengine/third_party/webservices/libraries/UPC_user_data.php */