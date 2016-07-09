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

    public function complete_data_from_senthorario(){
        $codlinea = $this->user_data->get_user_linea();
        $codmodal = $this->user_data->get_user_modalidad();
        $periodo = $this->user_data->get_ciclo();
        $quiz_service = ee()->config->item('quiz_services_url');
        $quiz_services_url = $quiz_service;
        $quiz_services_url .= $codlinea;
        $quiz_services_url .= '/'.$codmodal;
        $quiz_services_url .= '/'.$periodo;
        $quiz_services_url .= '/'.$this->user_data->get_full_user_code();
        $day = date('w');
        $week_start = date('Y-m-d', strtotime('-'.$day.' days'));
        $week_end = date('Y-m-d', strtotime('+'.(6-$day).' days'));
        $quiz_services_url .= '/'.$week_start.'T00:00:00Z';
        $quiz_services_url .= '/'.$week_end.'T00:00:00Z';
        $this->curl_quicks->upc_log("WFSENTHORARIO;".$this->user_data->get_full_user_code().";".$quiz_services_url.";".date('ddmmyyyy - H:i:s')."\n", "logs.txt");
        $quiz_result = $this->curl_quicks->curl_full_url($quiz_services_url,  ee()->config->item('quiz_user'),  ee()->config->item('quiz_pwd'));
        return $this->curl_quicks->parse_json($quiz_result, true);
        
    }

    /**
     *  Reglamento de Actualización de datos
     */
    public function get_data_update_reglamento(){
        $codlinea = $this->user_data->get_user_linea();
        $codmodal = $this->user_data->get_user_modalidad();
        $url = ee()->config->item('user_update_services_url');
        $url .= ee()->config->item('user_update_services_reglamento_path');
        $url .= '?';
        $url .= 'CodLineaNegocio='.$codlinea;
        $url .= '&CodModalEst='.$codmodal;
        $result = $this->curl_quicks->curl_full_url($url,  ee()->config->item('user_update_services_user'),  ee()->config->item('user_update_services_pwd'));
        return  $this->curl_quicks->parse_json($result, false);
    }

    /**
     *  Consulta de Parentesco
     */
    public function get_data_update_parentesco($tipo){

        $codlinea = $this->user_data->get_user_linea();
        $coduser = $this->user_data->get_user_code();
        $url = ee()->config->item('user_update_services_url');
        $url .= ee()->config->item('user_update_services_parentesco_path');
        $url .= '?';
        $url .= 'CodLineaNegocio='.$codlinea;
        $url .= '&CodUsuario='.$coduser;
        $url .= '&CodTipoPariente='.$tipo;
        $result = $this->curl_quicks->curl_full_url($url,  ee()->config->item('user_update_services_user'),  ee()->config->item('user_update_services_pwd'));
        return  $this->curl_quicks->parse_json($result, false);
    }

    /**
     *  Consulta de Alumno Registrado
     */
    public function get_data_update_registered_user(){
        $codlinea = $this->user_data->get_user_linea();
        $codalumno = $this->user_data->get_full_user_code();
        $codmodal = $this->user_data->get_user_modalidad();
        $url = ee()->config->item('user_update_services_url');
        $url .= ee()->config->item('user_update_services_alumno_path');
        $url .= '?';
        $url .= 'CodLineaNegocio='.$codlinea;
        $url .= '&CodModalEst='.$codmodal;
        $url .= '&CodAlumno='.$codalumno;
        $result = $this->curl_quicks->curl_full_url($url,  ee()->config->item('user_update_services_user'),  ee()->config->item('user_update_services_pwd'));
        return  $this->curl_quicks->parse_json($result, false);
    }

    /**
     *  Registro de Datos del alumno en el formulario
     */
    public function set_data_update_registered_user($phone, $email, $apNombres, $apApPatern, $apApMatern, $apphone, $apemail, $tipo){
        $codlinea = $this->user_data->get_user_linea();
        $codalumno = $this->user_data->get_full_user_code();
        $codmodal = $this->user_data->get_user_modalidad();
        $url = ee()->config->item('user_update_services_url');
        $url .= ee()->config->item('user_update_services_alumno_path');
        $url .= '?';
        $url .= 'CodLineaNegocio='.$codlinea;
        $url .= '&CodModalEst='.$codmodal;
        $url .= '&CodAlumno='.$codalumno;

        $params = array (
            'CodLineaNegocio' => $codlinea,
            'CodModalEst' => $codmodal,
            'CodAlumno' => $codalumno,
            'CodPersona' => $this->user_data->get_codigo_persona(),
            'ApellidoPatern' => $this->user_data->get_user_apellido_paterno(),
            'ApellidoMatern' => $this->user_data->get_user_apellido_materno(),
            'Nombres' => $this->user_data->get_user_nombres(),
            'TelefonoMovil' => $phone,
            'DireccionEmail' => $email,
            'UsuarioCreacion' => $this->user_data->get_user_code(),
            'ApodNombres' => $apNombres,
            'ApodApellidoPatern' => $apApPatern,
            'ApodApellidoMatern' => $apApMatern,
            'ApodTelefMovil' => $apphone,
            'ApodEmail' => $apemail,
            'TipoApoderado' => $tipo
        );
        $result = $this->curl_quicks->curl_post_full_url_authenticate($url,  $params, ee()->config->item('user_update_services_user'),  ee()->config->item('user_update_services_pwd'));
        return  $this->curl_quicks->parse_json($result, false);
    }

    /**
     *  Registro de Datos del alumno en el formulario
     */
    public function get_sentAlumno_data(){

        $codlinea = $this->user_data->get_user_linea();
        $codalumno = $this->user_data->get_full_user_code();
        $url = ee()->config->item('sentAlumno_services_url');
        $url .= ee()->config->item('sentAlumno_services_alumno_path');
        $url .= '/'.$codlinea;
        $url .= '/'.$codalumno;
        $result = $this->curl_quicks->curl_full_url($url,  ee()->config->item('sentAlumno_services_user'),  ee()->config->item('sentAlumno_services_pwd'));
        return  $this->curl_quicks->parse_json($result, false);
    }
}
/* End of file UPC_services.php */
/* Location: ./system/expressionengine/third_party/webservices/libraries/UPC_services.php */