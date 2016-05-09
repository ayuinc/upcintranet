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

        $this->curl_quicks = new ws_helper;
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
            $token = $this->user_data->get_user_token();
            if($token !== $_COOKIE[$this->curl_quicks->get_fuzzy_name('Token')]){
                return false;
            }else{
                return true;
            }
            
        }
    }

    public function courses_by_student(){
        $codigo = $this->user_data->get_user_code();
        $token = $this->user_data->get_user_token();
        $url = 'Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;
        $result=$this->curl_quicks->curl_url($url);
        return $this->curl_quicks->parse_json($result, false);
    }

    public function student_grades_by_course($codcurso){
        $codigo = $this->user_data->get_user_code();
        $token = $this->user_data->get_user_token();
        $url = 'Nota/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;
        $result=$this->curl_quicks->curl_url($url);
        return $this->curl_quicks->parse_json($result, false);
    }

    public function activate_reserved_resources($code, $student2=""){
        $codigo = $this->user_data->get_user_code();
        $token = $this->user_data->get_user_token();
        $url = 'ActivarReserva/?CodReserva='.$code.'&CodAlumno='.$codigo.'&CodAlumno2='.$student2.'&Token='.$token;
        $result=$this->curl_quicks->curl_url($url);
         return $this->curl_quicks->parse_json($result, false);

    }

    public function verify_reserved_resources($code, $codeResource,  $student2=""){
        $codigo = $this->user_data->get_user_code();
        $token = $this->user_data->get_user_token();
        $url = 'VerificaReserva/?CodReserva='.$code.'&CodRecurso='.$codeResource.'&CodAlumno='.$codigo.'&CodAlumno2='.$student2.'&Token='.$token;
        $result=$this->curl_quicks->curl_url($url);
        return $this->curl_quicks->parse_json($result, false);

    }

    public function list_reserved_resources(){
        $codigo = $this->user_data->get_user_code();
        $token = $this->user_data->get_user_token();
        $url = 'ReservaAlumno/?FecIni='.date('dmY').'&FechaFin='.date('dmY',strtotime('+1 week')).'&CodAlumno='.$codigo.'&Token='.$token;
        $result=$this->curl_quicks->curl_url($url);
        return $this->curl_quicks->parse_json($result, false);
    }
}
/* End of file UPC_services.php */
/* Location: ./system/expressionengine/third_party/webservices/libraries/UPC_services.php */