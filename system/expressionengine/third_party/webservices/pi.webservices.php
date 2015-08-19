<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Memberlist Class
 *
 * @package     ExpressionEngine
 * @category    Plugin
 * @author      Gianfranco Montoya
 * @copyright   Copyright (c) 2014, Gianfranco Montoya 
 * @link        http://www.ayuinc.com/
 */

$plugin_info = array(
    'pi_name'         => 'Webservices',
    'pi_version'      => '1.0',
    'pi_author'       => 'Gianfranco Montoya',
    'pi_author_url'   => 'http://www.ayuinc.com/',
    'pi_description'  => 'Permite consumir los servicios generados por UPC',
    'pi_usage'        => Webservices::usage()
);
            
class Webservices
{

    var $return_data = "";
    var $site_url = "";
    var $services;
    var $_cookies_prefix="";
    // --------------------------------------------------------------------
        /**
         *
         *
         * Webservices
         *
         * This function returns a list of members
         *
         * @access  public
         * @return  string
         */
    public function __construct()
    {
        $this->EE =& get_instance();
        require_once 'libraries/Webservices_functions.php';
        $this->site_url = $this->EE->config->item('site_url');
        $this->services = new Webservices_functions;
        $this->_cookies_prefix = $this->EE->config->item('cookie_prefix')."_";
    }

    // --------------------------------------------------------------------

    /**
     * Usage
     *
     * This function describes how the plugin is used.
     *
     * @access  public
     * @return  string
     */
    public static function usage()
    {
        ob_start();  
?>
        
        The Memberlist Plugin simply outputs a
        list of 15 members of your site.

            {exp:webservices}

        This is an incredibly simple Plugin.

<?php
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }

    /**
     * Set object to name in $_SESSION and cookies
     *
     * @access  public
     * @param string $name Name of data as key for $_SESSION and cookie
     * @param string $jsonObj Object to be saved on $_SESSION
     * @return 
     */
    private function set_session_cookie($name, $jsonObj){
      $_SESSION[$name] = $jsonObj;
      $this->services->set_cookie($name, $jsonObj, time()+3600, '/', '.upc.edu.pe', '', 1);
      return;
    }

        /**
     * Unset object to name in $_SESSION and cookies
     *
     * @access  public
     * @param string $name Name of data as key for $_SESSION and cookie
     * @return 
     */
    private function unset_session_cookie($name){
      if(isset($_COOKIE[$this->_cookies_prefix.$name]))
      { 
        unset($_COOKIE[$this->_cookies_prefix.$name]);
      }
      if(isset($_SESSION[$this->_cookies_prefix.$name]))
      {
        unset($_SESSION[$this->_cookies_prefix.$name]);
      }
      $this->services->delete_cookie($this->_cookies_prefix.$name) ;
      $this->services->delete_cookie($name) ;
    }

    /**
     * Buils session according to user
     *
     * @access  public
     * @return  
     */
    public function terminos_condiciones_get()
    {
      $codigo = $_POST['codigo'];
      ee()->db->select('terminos_condiciones');
      ee()->db->select('id');
      ee()->db->where('codigo',$codigo);
      $query = ee()->db->get('exp_user_upc_data');
      $respuesta = $query->result();
      $res = $respuesta[0]->terminos_condiciones;
      // print_r($respuesta);

      if ($res == '') {
        $datos = 'nn';
        echo $datos;
      }
      elseif($res == NULL || $res == 'no'){
        $datos = 'no';
        echo $datos;
      }else
        echo $datos = 'si';  

    }

    public function terminos_condiciones_set()
    {
      $codigo = $_POST['codigo'];
      ee()->db->select('terminos_condiciones');
      ee()->db->where('codigo',$codigo);
      $query = ee()->db->get('exp_user_upc_data');
      ee()->db->update('exp_user_upc_data', array("terminos_condiciones" => 'si'));
    }

    public function generador_token()
    {
      // fetch template parameters
      $codigo = ee()->TMPL->fetch_param('codigo');
      $contrasena = ee()->TMPL->fetch_param('contrasena');
      $plataforma = ee()->TMPL->fetch_param('plataforma');

      // Curl service
      $result =  $this->services->curl_url_not_reuse('Autenticar2/?Codigo='.$codigo.'&Contrasena='.$contrasena.'&Plataforma='.$plataforma);
      $json = json_decode($result, true);
      // die(print_r($result));
      $modalidad = $json['Datos']['CodModal'];
      $tipo_user = $json['TipoUser'];
      // Cookies for Error
      $_SESSION["CodError"] = $json['CodError'];
      $_SESSION["MsgError"] = $json['MsgError'];      
      $cookie_name = "MsgError";
      $cookie_value = $json["MsgError"];
      $this->services->set_cookie($cookie_name, $cookie_value, time()+1800, "/");

        if (strval($json['CodError'])=='null' || strval($json['CodError'])=='00001' || strval($json['CodError'])=='11111') {
        	$site_url = ee()->config->item('site_url');
        	$site_url .= 'login/error_login';
          $this->EE->functions->redirect($site_url);
        }
        else {
          ee()->db->select('id');
          ee()->db->where('codigo',$codigo);
          $query_modelo_result = ee()->db->get('exp_user_upc_data');
          if($query_modelo_result->result() == NULL){
            $user_upc_insert = array(
              "codigo" => $json['Codigo'],
              "tipouser" => $json['TipoUser'],  
              "nombres" => $json['Nombres'],      
              "apellidos" => $json['Apellidos'],
              "estado" => $json['Estado'],  
              "dscmodal" => $json['Datos']['DscModal'],
              "dscsede" => $json['Datos']['DscSede'],
              "ciclo" => $json['Datos']['Ciclo'], 
              "token" => $json['Token']
            );
            ee()->db->insert('exp_user_upc_data', $user_upc_insert);
          } 
          else {
            // die($tipo_user);

            if ($tipo_user == 'ALUMNO') {
              if ($modalidad != 'FC' && $modalidad != 'AC') {
                $site_url = ee()->config->item('site_url');
                $site_url .= 'login/no-es-usuario?message=true';
                $this->EE->functions->redirect($site_url);
                // return $output;
              }
            }
            $user_upc_update = array(
              "token" => $json['Token']
            );
            ee()->db->where('codigo', $codigo);
            ee()->db->update('exp_user_upc_data', $user_upc_update);
          }

          // Saving data to $_SESSION and Cookies
          $user_data = array( 'Codigo' =>  $json['Codigo'],
                              'TipoUser'  =>  $json['TipoUser'],
                              'Nombres' =>  $json['Nombres'],
                              'Apellidos' =>  $json['Apellidos'],
                              'Estado'  =>  $json['Estado'],
                              'CodLinea' =>  $json['Datos']['CodLinea'],
                              'CodModal' =>  $json['Datos']['CodModal'],
                              'DscModal'  => $json['Datos']['DscModal'],
                              'CodSede' =>  $json['Datos']['CodSede'],
                              'DscSede' =>  $json['Datos']['DscSede'],
                              'Ciclo' => $json['Datos']['Ciclo'],
                              'Token' =>  $json['Token']);
          foreach ($user_data as $key => $val)
          {
            $this->set_session_cookie($key, $val);
          }
        }

      return;        
    }

    //Recupera los parametros de la url cuando no es alumno de la modalidad ac o fc
    public function get_message_no_user()
    {

      $message = $_GET['message'];
      if ($message == "true") {
        $message_body = "<p class='red text-justify'>Estimado alumno,</br></br> 
                      Estamos trabajando para brindarte un mejor servicio, por lo cual te pedimos realizar tus operaciones a través de www.intranet.upc.edu.pe</br></br>
                      Pronto te informaremos desde cuándo puedes realizar tus operaciones por este medio.</br></br> Gracias por tu comprensión.<p>";
      }
      return $message_body;
    }

    /**
     * Erase and destroys cookies. 
     *
     * @access  public
     * @return 
     */
    public function eliminar_cookie()
    {
      session_name('upc');
      session_start();
      $_SESSION["Token"] = "";
      // Removing data from $_SESSION and Cookies
      $user_data = array( 'Codigo' ,
                          'TipoUser',
                          'MsgError',
                          'closed-alert',
                          'onLogin',
                          'Nombres',
                          'Apellidos',
                          'Estado',
                          'CodLinea',
                          'CodModal',
                          'DscModal',
                          'CodSede',
                          'DscSede',
                          'Ciclo' ,
                          'Token', 
                          'Redireccion');
      foreach ($user_data as $key)
      {
        $this->unset_session_cookie($key);
      }
      session_destroy();
    }

    // CONSULTAR ORDEN DE MERITO ALUMNO
    public function consultar_orden_de_merito_alumno()
    {
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $_COOKIE[$this->_cookies_prefix."Codigo"] =  $codigo;
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      
      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');
      foreach($query_modelo_result->result() as $row){
        $TipoUser = $row->tipouser;
        $token = $row->token;
      }
    }

    //CONSTRUCTOR DE SESIONES DE ACUERDO AL USUARIO
    
    public function consultar_alumno()
    {

      $codigo = $_SESSION["Codigo"];
      $_COOKIE[$this->_cookies_prefix."Codigo"] =  $codigo;

      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('TipoUser, Token');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');
      $TipoUser = '';
      $token = '';
      foreach($query_modelo_result->result() as $row){
        $TipoUser = $row->TipoUser;
        $token = $row->Token;
      }    
      $result = '';

      $_COOKIE[$this->_cookies_prefix."TipoUser"] =  $TipoUser;
      $this->services->set_cookie("TipoUser",$TipoUser, time() + (1800), "/");
      $_COOKIE[$this->_cookies_prefix."Token"] =  $token;
      $this->services->set_cookie("Token",$token, time() + (1800), "/");
      $site_url = ee()->config->item('site_url');
      
      if (strval($TipoUser) ==='ALUMNO') 
      {
        if (isset($_COOKIE[$this->_cookies_prefix."Redireccion"])) 
        {
          if(strcmp($_COOKIE[$this->_cookies_prefix."Redireccion"], "")!=0 )
          {
            $this->EE->functions->redirect($site_url.($_COOKIE[$this->_cookies_prefix."Redireccion"]));
          }
          else 
          {
            $this->EE->functions->redirect($site_url."dashboard/estudiante");
          }
        }
        else
        {
          $this->EE->functions->redirect($site_url."dashboard/estudiante");
        } 
      }   
      
      if (strval($TipoUser)=='PROFESOR') 
      {
        if (isset($_COOKIE[$this->_cookies_prefix."Redireccion"])) 
        {
          if(strcmp($_COOKIE[$this->_cookies_prefix."Redireccion"], "")!=0)
          {
             $this->EE->functions->redirect($site_url.$_COOKIE[$this->_cookies_prefix."Redireccion"]);
          }
          else{
            $this->EE->functions->redirect($site_url."dashboard/docente");
          }
        }
        else{
          $this->EE->functions->redirect($site_url."dashboard/docente");
        }       
      }
      
      if (strval($TipoUser)=='PADRE') {

        $url = 'ListadoHijos/?Codigo='.$codigo.'&Token='.$token.'';
        $hijosWebService= $this->services->curl_url_not_reuse('ListadoHijos/?Codigo='.$codigo.'&Token='.$token.'');

        $json = json_decode($hijosWebService, true);

        $result .= '<div class="col-sm-3 col-xs-2"></div>';
        $result .= '<div class="col-sm-6 col-xs-8 welcome">';
        $result .= '<div class="usuario-container pb-14 bg-muted"><div class="avatar-circle"><img class="img-center img-responsive" src="{site_url}assets/img/user_ie8_info.png" alt=""></div><div class="zizou-28 mt--28 text-center">Hola {exp:webservices:nombre_alumno}</div>';
        $result .= '<div class="zizou-18 text-center gray-light">Elige con cuál de tus hijos quieres entrar</div>';
        $result .= '<div class="row pt-21">';

        for ($i=0; $i < count($json["hijos"])  ; $i++) { 
          if ($i%2 == 0) {
            $result .= '<div class="col-sm-offset-2 col-xs-offset-1 col-xs-5 col-sm-4">';
          } elseif ($i%2 == 1) {
            $result .= '<div class="col-sm-4 col-xs-5 ">';    
          }
        $result .= '<a href="{site_url}dashboard/padre/hijos/'.$json["hijos"][$i]["codigo"].'">';
        $result .= '<div class="children-avatar text-center">';
        $result .= '</div>';
        $result .= '</a>';
        $result .= '<div class="solano-bold-20 text-center"><a href="{site_url}dashboard/padre/hijos/'.$json["hijos"][$i]["codigo"].'">'.$json["hijos"][$i]["nombres"].'</a></div>';
        $result .= '</div>';
        }
        $result .= '</div>';

        return $result;             
      }  
       return;             
    }

    public function redirect_user()
    {
      $site_url = ee()->config->item('site_url');
      $TipoUser = $_COOKIE[$this->_cookies_prefix."TipoUser"];

      if (strval($TipoUser) ==='ALUMNO')
      {  
        $this->EE->functions->redirect($site_url."dashboard/estudiante");
      }   
      
      if (strval($TipoUser)=='PROFESOR') 
      {  
        $this->EE->functions->redirect($site_url."dashboard/docente");      
      }
      
      if (strval($TipoUser)=='PADRE') 
      {
        $this->EE->functions->redirect($site_url."dashboard/padre");              
      }  
       return;             
    }

    public function padre_lista_de_hijos_ciclo_actual()
    {
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');  
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');
      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      $result = '';
      $url = 'ListadoHijos/?Codigo='.$codigo.'&Token='.$token.'';

      $hijosWebService=$this->services->curl_url($url);
      $json = json_decode($hijosWebService, true);

      $result .= '<ul class="grid-list grid-list-3 grid-list-centered">';
      for ($i=0; $i < count($json["hijos"])  ; $i++) { 
        $result .= '<li>';
        $result .= '<a id="lnk_int_CicloActual" href="{site_url}sus-estudios/ciclo-actual/'.$json["hijos"][$i]["codigo"].'">';
        $result .= '<div class="children-avatar text-center">';
        $result .= '<img class="img-circle img-responsive img-thumbnail" src="{site_url}assets/img/user_gray.png" alt="">';
        $result .= '</div>';
        $result .= '</a>';
        $result .= '<div class="solano-bold-20 text-center"><a href="{site_url}sus-estudios/ciclo-actual/'.$json["hijos"][$i]["codigo"].'">'.$json["hijos"][$i]["nombres"].'</a></div>';
        $result .= '</li>';
        }
        $result .= '</ul>';
      
      return $result;      
    }

    public function padre_lista_de_hijos_notas_detalladas()
    {
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');  
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');
      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      $result = '';
      $url = 'ListadoHijos/?Codigo='.$codigo.'&Token='.$token.'';

      $hijosWebService=$this->services->curl_url($url);
      $json = json_decode($hijosWebService, true);

      $result .= '<ul class="grid-list grid-list-3 grid-list-centered">';
      for ($i=0; $i < count($json["hijos"])  ; $i++) { 
      $result .= '<li>';
      $result .= '<a href="{site_url}sus-estudios/notas-detalladas/'.$json["hijos"][$i]["codigo"].'">';
      $result .= '<div class="children-avatar text-center">';
      $result .= '<img class="img-circle img-responsive img-thumbnail" src="{site_url}assets/img/user_gray.png" alt="">';
      $result .= '</div>';
      $result .= '</a>';
      $result .= '<div class="solano-bold-20 text-center"><a href="{site_url}sus-estudios/notas-detalladas/'.$json["hijos"][$i]["codigo"].'">'.$json["hijos"][$i]["nombres"].'</a></div>';
      $result .= '</li>';
      }
      $result .= '</ul>';
      
      return $result;      
    }

    public function padre_lista_de_hijos_estado_actual()
    {
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');  
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');
      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      $result = '';
      $url = 'ListadoHijos/?Codigo='.$codigo.'&Token='.$token.'';

      $hijosWebService=$this->services->curl_url($url);
      $json = json_decode($hijosWebService, true);

      $result .= '<ul class="grid-list grid-list-3 grid-list-centered">';
      for ($i=0; $i < count($json["hijos"])  ; $i++) { 
      $result .= '<li>';
      $result .= '<a href="{site_url}sus-tramites/estado-actual/'.$json["hijos"][$i]["codigo"].'">';
      $result .= '<div class="children-avatar text-center">';
      $result .= '<img class="img-circle img-responsive img-thumbnail" src="{site_url}assets/img/user_gray.png" alt="">';
      $result .= '</div>';
      $result .= '</a>';
      $result .= '<div class="solano-bold-20 text-center"><a href="{site_url}sus-tramites/estado-actual/'.$json["hijos"][$i]["codigo"].'">'.$json["hijos"][$i]["nombres"].'</a></div>';
      $result .= '</li>';
      }
      $result .= '</ul>';
      
      return $result;      
    }

    public function padre_lista_de_hijos_mis_pagos()
    {
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');  
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');
      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      $result = '';
      $url = 'ListadoHijos/?Codigo='.$codigo.'&Token='.$token.'';

      $hijosWebService=$this->services->curl_url($url);
      $json = json_decode($hijosWebService, true);

      $result .= '<ul class="grid-list grid-list-3 grid-list-centered">';
      for ($i=0; $i < count($json["hijos"])  ; $i++) { 
      $result .= '<li>';
      $result .= '<a href="{site_url}mis-finanzas-padres/mis-pagos/'.$json["hijos"][$i]["codigo"].'">';
      $result .= '<div class="children-avatar text-center">';
      $result .= '<img class="img-circle img-responsive img-thumbnail" src="{site_url}assets/img/user_gray.png" alt="">';
      $result .= '</div>';
      $result .= '</a>';
      $result .= '<div class="solano-bold-20 text-center"><a href="{site_url}mis-finanzas-padres/mis-pagos/'.$json["hijos"][$i]["codigo"].'">'.$json["hijos"][$i]["nombres"].'</a></div>';
      $result .= '</li>';
      }
      $result .= '</ul>';
      
      return $result;      
    }

    public function padre_lista_de_hijos_mis_pagos_hijos()
    {
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');  
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');
      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      $result = '';
      $url = 'ListadoHijos/?Codigo='.$codigo.'&Token='.$token.'';

      $hijosWebService=$this->services->curl_url($url);
      $json = json_decode($hijosWebService, true);

      $result .= '<ul class="grid-list grid-list-3 grid-list-centered">';
      for ($i=0; $i < count($json["hijos"])  ; $i++) { 
      $result .= '<li>';
      $result .= '<a href="{site_url}mis-finanzas-padres/mis-pagos/'.$json["hijos"][$i]["codigo"].'">';
      $result .= '<div class="children-avatar text-center">';
      $result .= '<img class="img-circle img-responsive img-thumbnail" src="{site_url}assets/img/user_gray.png" alt="">';
      $result .= '</div>';
      $result .= '</a>';
      $result .= '<div class="solano-bold-20 text-center"><a href="{site_url}dashboard/padre/hijos/'.$json["hijos"][$i]["codigo"].'">'.$json["hijos"][$i]["nombres"].'</a></div>';
      $result .= '</li>';
      }
      $result .= '</ul>';
      
      return $result;      
    }


    // HEADER PADRES CON LISTA DE HIJOS 
    public function padre_lista_de_hijos()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');  
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');
      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $result = '';
      
      $url = 'ListadoHijos/?Codigo='.$codigo.'&Token='.$token.'';

      $hijosWebService=$this->services->curl_url($url);
      $json = json_decode($hijosWebService, true);
      for ($i=0; $i < count($json["hijos"])  ; $i++) { 
        $nombre_hijo = $json["hijos"][$i]["nombres"];
        if(strlen($nombre_hijo) > 9 && strpos($nombre_hijo," ")!== false){
          $nombre_hijo = explode(" ", $nombre_hijo);
          $nombre_hijo = $nombre_hijo[0];
        }
        //$result .= '<li><a href="{site_url}dashboard/padre/hijos/'.$json["hijos"][$i]["codigo"].'">'.$json["hijos"][$i]["nombres"].' '.$json["hijos"][$i]["apellidos"].'</a></li>';
        $result .=  '<li class="ml-21 mr-21">';
        $result .=  '<div class="dropdown avatar-hijo">';
        $result .=  '<div class="dropdown-toggle" id="dropdownMenuA" data-toggle="dropdown">';
        if($codigo_alumno == $json["hijos"][$i]["codigo"]){
          $result .=  '<div class="avatar-container active">';
        }
        else{
          $result .=  '<div class="avatar-container">';
        }
        // $result .=  '<div class="children-avatar"></div>';
        $result .=  '</div>';
        $result .=  '</div>';
        $result .=  '<span>'.$nombre_hijo.'</span>';
        $result .=  '<ul class="dropdown-menu first-child" role="menu" aria-labelledby="dropdownMenuA">';
        $result .=  '<li class="dditem" role="presentation"><a role="menuitem" tabindex="-1" href="{site_url}dashboard/padre/hijos/'.$json["hijos"][$i]["codigo"].'">Activar su perfil</a></li>';
        $result .=  '</ul>';
        $result .=  '</div>';
        $result .=  '</li>';
      }
      
      return $result;             
                    
    }

    //CONSTRUCTOR DE SESIONES DE ACUERDO AL USUARIO Y LA REDIRECCION QUE LLEGA
    public function consultar_alumno_redireccion()
    {
      //$codigo = $_SESSION["Codigo"];
      //$TipoUser = $_SESSION["TipoUser"];
      //$redireccion = $_SESSION["Redireccion"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $TipoUser = $row->tipouser;
        $redireccion = $row->redireccion;
      }

      $result = '';
      
      if (strval($TipoUser)=='ALUMNO') {
        $result .= '<ul class="tr pb-7">';
        $result .= '<li class="col-sm-2"></li>';
        $result .= '<li class="col-sm-8 bg-muted">';
        $result .= '<div>';
        $result .= '<span class="zizou-14">';
        $result .= '<a href="{site_url}'.$redireccion.'">';
        $result .= '<img class="pr-7" src="{site_url}assets/img/black_arrow.png">Alumno Pregrado';
        $result .= '</a>';
        $result .= '</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul> ';  
        
        return $result;     
      }
      
      if (strval($TipoUser)=='PROFESOR') {
        $result .= '<ul class="tr pb-7">';
        $result .= '<li class="col-sm-2"></li>';
        $result .= '<li class="col-sm-8 bg-muted">';
        $result .= '<div>';
        $result .= '<span class="zizou-14">';
        $result .= '<a href="{site_url}'.$redireccion.'">';
        $result .= '<img class="pr-7" src="{site_url}assets/img/black_arrow.png">Alumno Pregrado';
        $result .= '</a>';
        $result .= '</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul> ';   
        
        return $result;             
      }
      
      if (strval($TipoUser)=='PADRE') {
        $result .= '<ul class="tr pb-7">';
        $result .= '<li class="col-sm-2"></li>';
        $result .= '<li class="col-sm-8 bg-muted">';
        $result .= '<div>';
        $result .= '<span class="zizou-14">';
        $result .= '<a href="{site_url}'.$redireccion.'">';
        $result .= '<img class="pr-7" src="{site_url}assets/img/black_arrow.png">Alumno Pregrado';
        $result .= '</a>';
        $result .= '</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul> ';  
        
        return $result;             
      }  
                    
    }    
    
    //HORARIO DEL ALUMNO
    public function horario_alumno()
    {
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'Horario/?CodAlumno='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError']; 
      
      //limpio la variable para reutilizarla
      $result = '<div class="panel-body">';
      
      //genera el tamano del array
      $tamano = count($json['HorarioDia']);
      $flag = TRUE;
      //Loop basado en el HorarioDia
      for ($i=0; $i<$tamano; $i++) {

        //genera el tamano del array
        $tamano_1 = count($json['HorarioDia'][$i]['Clases']);
        $dia_actual = date('w');
        
        //Despliega solo las clases del dia
         if ($json['HorarioDia'][$i]['CodDia']==date('w')) {
          if($i == 0){
            $result .= '<div class="panel-body-head-table">';
            $result .= '  <ul class="tr">';
            $result .= '    <li class="col-xs-2">';
            $result .= '      <div class="text-center fecha"><span>Hora</span></div>';
            $result .= '    </li>';
            $result .= '    <li class="col-xs-2">';
            $result .= '      <div class="text-center"><span>Campus</span></div>';
            $result .= '    </li>';
            $result .= '    <li class="col-xs-6">';
            $result .= '      <div class="text-center"><span>Curso</span></div>';
            $result .= '    </li>';
            $result .= '    <li class="col-xs-2">';
            $result .= '      <div class="text-center"><span>Aula</span></div>';
            $result .= '    </li>';
            $result .= '  </ul>';
            $result .= '</div>';
          }

            $result.= '<div class="panel-table">';
          //Loop de las clases diponibles
          for ($b=0; $b<$tamano_1; $b++) {
            $HoraInicio[$b] = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraInicio'], 0, 2);
            $HoraInicio[$b] = ltrim($HoraInicio[$b],'0');
            $Sede[$b] = $json['HorarioDia'][$i]['Clases'][$b]['Sede'];
            $CursoNombre[$b] = $json['HorarioDia'][$i]['Clases'][$b]['CursoNombre'];
            $Salon[$b] = $json['HorarioDia'][$i]['Clases'][$b]['Salon'];
          }
          
          $tamano_2 = count($HoraInicio);
          $disponibles = 0;
          
          //Loop generador de horas
          for ($b=7; $b<=22; $b++) {
            
            //Compara si en el arreglo construido la hora es igual al counter del loop
            if ($HoraInicio[$disponibles]==$b) {
              $flag = FALSE;
              $result .= '<ul class="tr">';
              $result .= '<li class="col-xs-2">';
              $result .= '<div class="text-center"><span class="helvetica-bold-16">'.$HoraInicio[$disponibles].':00</span></div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2">';
              $result .= '<div class="text-center"><span class="helvetica-bold-16">'.$Sede[$disponibles].'</span></div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-6">';
              $result .= '<div><span class="helvetica-14">'.$CursoNombre[$disponibles].'</span></div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2">';
              $result .= '<div class="text-center"><span class="solano-bold-18">'.$Salon[$disponibles].'</span></div>';
              $result .= '</li>';
              $result .= '</ul>';    
              //Controla que ya no recorra mas el arreglo 
              if ($disponibles != $tamano_2-1) {
                $disponibles++;
              } 
            } else {
              if($b == 22 && $flag){
                $result = '<div class="panel-body">';
                $result .= '<div class="panel-table pb-7">';
                $result .= '<ul class="tr">';
                $result .= '<li class="col-xs-4 p-7">';
                $result .= '<img class="img-center" src="{site_url}assets/img/brain.png">';
                $result .= '</li>';
                $result .= '<li class="col-xs-8 pt-28 pr-21">';
                $result .= '<p class="zizou-bold-16 m-0">Tiempo de Innovar</p>';                
                $result .= '<p class="helvetica-14">No tienes ningún curso el día de hoy</p>';                
                $result .= '</li>';
                $result .= '</ul>';
                $result .= '</div>';
              }
            }   
          } 
        } 
        
      }
      if($flag){
        $result = '<div class="panel-body">';
        $result .= '<div class="panel-table pb-7">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-4 p-7">';
        $result .= '<img class="img-center" src="{site_url}assets/img/brain.png">';
        $result .= '</li>';
        $result .= '<li class="col-xs-8 pt-28 pr-21">';
        $result .= '<p class="zizou-bold-16 m-0">Tiempo de Innovar</p>';                
        $result .= '<p class="helvetica-14">No tienes ningún curso el día de hoy</p>';                
        $result .= '</li>';
        $result .= '</ul>';
      }
      $result .= '</div>';
      $result .= '</div>'; 
      //Control de errores
      if ($error!='00000') {
        $result = '<div class="panel-body">';
        $result .= '<div class="panel-table pb-7">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-4 p-7">';
        $result .= '<img class="img-center" src="{site_url}assets/img/brain.png">';
        $result .= '</li>';
        $result .= '<li class="col-xs-8 pt-28 pr-21">';
        $result .= '<p class="helvetica-14">'.$error_mensaje.'</p>';                
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '</div>';    
      } 
      

      return $result;             
    }

    public function padre_horario_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      // $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');
      $codigo_alumno =  $_GET['codigo_alumno'];
      $flag = TRUE;
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'HorarioPadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&Token='.$token;

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError']; 
      
      //limpio la variable para reutilizarla
      $result = '';

      
      //genera el tamano del array
      $tamano = count($json['HorarioDia']);
      
      //Loop basado en el HorarioDia
      for ($i=0; $i<$tamano; $i++) {
        
        //genera el tamano del array
        $tamano_1 = count($json['HorarioDia'][$i]['Clases']);
        $dia_actual = date('w');
        //var_dump($dia_actual);
        //Despliega solo las clases del dia
         if ($json['HorarioDia'][$i]['CodDia']== date('w')) { // para provocar el error colocar en lugar de  date('w') -> 7
            $result .= '  <div class="panel-body-head-table">
                          <ul class="tr">
                            <li class="col-xs-2">
                              <div class="fecha"><span>Hora</span></div>
                            </li>
                            <li class="col-xs-2">
                              <div class=""><span>Campus</span></div>
                            </li>
                            <li class="col-xs-6">
                              <div class=""><span>Curso</span></div>
                            </li>
                            <li class="col-xs-2">
                              <div class=""><span>Salón</span></div>
                            </li>
                          </ul>
                        </div>';
          //$result.= '<div class="panel-table">';
          //Loop de las clases diponibles
          for ($b=0; $b<$tamano_1; $b++) {
            $HoraInicio[$b] = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraInicio'], 0, 2);
            $HoraInicio[$b] = ltrim($HoraInicio[$b],'0');
            $Sede[$b] = $json['HorarioDia'][$i]['Clases'][$b]['Sede'];
            $CursoNombre[$b] = $json['HorarioDia'][$i]['Clases'][$b]['CursoNombre'];
            $Salon[$b] = $json['HorarioDia'][$i]['Clases'][$b]['Salon'];
          }
          
          $tamano_2 = count($HoraInicio);
          $disponibles = 0;
          
          //Loop generador de horas
          for ($b=7; $b<=22; $b++) {
            
            //Compara si en el arreglo construido la hora es igual al counter del loop
            if ($HoraInicio[$disponibles]==$b) {
              $flag = FALSE;
              $result .= '<ul class="tr list-unstyled clearfix bg-muted">';
              $result .= '<li class="col-xs-2 br-gl">';
              $result .= '<div class="text-center"><span class="helvetica-bold-16">'.$HoraInicio[$disponibles].':00</span></div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2 br-gl">';
              $result .= '<div class="text-center"><span class="helvetica-bold-16">'.$Sede[$disponibles].'</span></div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-6 br-gl">';
              $result .= '<div><span class="helvetica-14">'.$CursoNombre[$disponibles].'</span></div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2">';
              $result .= '<div class="text-center"><span class="solano-bold-18">'.$Salon[$disponibles].'</span></div>';
              $result .= '</li>';
              $result .= '</ul>';       
              //Controla que ya no recorra mas el arreglo 
              if ($disponibles != $tamano_2-1) {
                $disponibles++;
              } 
            } 
          } 
        }  
      }
     if($flag == TRUE){
        /*$result = '<ul class="tr">';
        $result .= '<li class="col-xs-3">';
        $result .= '<img class="img-center" src="{site_url}assets/img/no_classes.png">';
        $result .= '</li>';
        $result .= '<li class="text-center col-xs-8 pt-21">';
        $result .= '<p>No tienes ninguna clase programada para el día de hoy</p>';                
        $result .= '</li>';
        $result .= '</ul>';*/

        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-3 p-7">';
        $result .= '<img class="img-center" src="{site_url}assets/img/no_classes.png">';
        $result .= '</li>';
        $result .= '<li class="text-center col-xs-9 text-center p-21 test">';
        $result .= '<p class="helvetica-14">No tienes ninguna clase programada para el día de hoy</p>';                
        $result .= '</li>';                       
        $result .= '</ul>';  
        $result .= '</div>';         
      } 
      //Control de errores
      if ($error!='00000') {
        /*$result = '<ul class="tr">';
        $result .= '<li class="col-xs-3">';
        $result .= '<img class="img-center" src="{site_url}assets/img/no_classes.png">';
        $result .= '</li>';
        $result .= '<li class="col-xs-8 pt-21">';
        $result .= '<p>'.$error_mensaje.'</p>';                
        $result .= '</li>';
        $result .= '</ul>';*/
          
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-3 p-7">';
        $result .= '<img class="img-center" src="{site_url}assets/img/no_classes.png">';
        $result .= '</li>';
        $result .= '<li class="text-center col-xs-9 text-center p-21 test-1">';
        $result .= '<p class="helvetica-14">'.$error_mensaje.'</p>';                
        $result .= '</li>';                       
        $result .= '</ul>';  
        $result .= '</div>';         
      } 
      
      
      
      
      return $result;             
    }    
    
    
    //HORARIO CICLO ACTUAL DEL ALUMNO
    public function horario_ciclo_actual_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];

      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      
      $url = 'Horario/?CodAlumno='.$codigo.'&Token='.$token;


      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];      
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['HorarioDia']);  
      
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<div>';
        $result .= '<span class="zizou-16">';
        if ($json['HorarioDia'][$i]['CodDia'] == 1) {
          $result .= 'Lunes';
        }
        if ($json['HorarioDia'][$i]['CodDia'] == 2) {
          $result .= 'Martes';
        }       
        if ($json['HorarioDia'][$i]['CodDia'] == 3) {
          $result .= 'Miércoles';
        }    
        if ($json['HorarioDia'][$i]['CodDia'] == 4) {
          $result .= 'Jueves';
        }  
        if ($json['HorarioDia'][$i]['CodDia'] == 5) {
          $result .= 'Viernes';
        }                    
        if ($json['HorarioDia'][$i]['CodDia'] == 6) {
          $result .= 'Sábado';
        }           
        $result .= '</span>';
        $result .= '</div>'; 
        $result .= '<div class="panel-body red-line mb-7">';
        $result .= '<div class="panel-body-head-table white">'; 
        $result .= '<ul class="tr text-center">'; 
        $result .= '<li class="col-sm-1-5  hidden-xs">'; 
        $result .= '<div><span>Inicio</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-1-5 hidden-xs">'; 
        $result .= '<div><span>Fin</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-1-5 col-xs-2 hidden-sm hidden-md hidden-lg">'; 
        $result .= '<div><span>Inicio - Fin</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-4-5 col-xs-4-5 ">'; 
        $result .= '<div><span>Clase</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-1-5 col-xs-1-5 ">'; 
        $result .= '<div><span>Sede</span></div>'; 
        $result .= '</li>';  
        $result .= '<li class="col-sm-1-5 col-xs-2 ">'; 
        $result .= '<div><span>Sección</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-1-5 col-xs-2 ">'; 
        $result .= '<div><span>Aula</span></div>'; 
        $result .= '</li>';                                                                                                                                                  
        $result .= '</ul>'; 
        $result .= '</div>';        
        
        //genera el tamano del array
        $tamano_int = count($json['HorarioDia'][$i]['Clases']); 
        
        for ($b=0; $b<$tamano_int; $b++) {
            $result .= '<div class="panel-table">'; 
            $result .= '<ul class="tr mis-cursos-row">'; 
            $result .= '<li class="col-sm-1-5 col-xs-1-5 hidden-xs">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $HoraInicio = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraInicio'], 0, 2);
            $HoraInicio = ltrim($HoraInicio,'0');
            $result .= $HoraInicio.':00';
            $result .= '</span></div>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-sm-1-5 col-xs-1-5 hidden-xs">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $HoraFin = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraFin'], 0, 2);
            $HoraFin = ltrim($HoraFin,'0');
            $result .= $HoraFin.':00';                  
            $result .= '</span></div>';                  
            $result .= '</li>';     
            $result .= '<li class="col-xs-2 hidden-md hidden-sm hidden-lg">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $HoraInicio = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraInicio'], 0, 2);
            $HoraInicio = ltrim($HoraInicio,'0');
            $result .= $HoraInicio.':00 - '.$HoraFin.':00';
            $result .= '</span></div>'; 
            $result .= '</li>';                    
            $result .= '<li class="col-sm-4-5 col-xs-4-5">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['CursoNombre'];
            $result .= '</span></div>';                   
            $result .= '</li>'; 
            $result .= '<li class="col-sm-1-5 col-xs-1-5">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Sede'];
            $result .= '</span></div>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-sm-1-5 col-xs-2">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Seccion'];
            $result .= '</span></div>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-sm-1-5 col-xs-2">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Salon'];
            $result .= '</span></div>';  
            $result .= '</li>'; 
            $result .= '</ul>'; 
            $result .= '</div>';                              
        }               
        $result .= '</div>';
      }

      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div>';
        $result .= '<div class="panel-body mb-35">';
        $result .= '<div class="panel-table red-line">';
        $result .= '<ul class="tr mis-cursos-row">';
        $result .= '<li class="col-sm-2 col-xs-12">';
        $result .= '<img src="{site_url}assets/img/brain.png" class="img-center">';
        $result .= '</li>';    
        $result .= '<li class="col-sm-10 col-xs-12">';
        $result .= '<span class="block zizou-bold-18">Tiempo de Innovar</span>';
        if (strpos($error_mensaje,"Ud. no tiene clases programadas para esta semana.") !== false) {
        $result .= '<span class="helvetica-16">No tienes ningún curso</span>';
        } else {
        $result .= '<span class="helvetica-16">'.$error_mensaje.'</span>';
        }
        $result .= '</li>';                
        $result .= '</ul>';  
        $result .= '</div>';
        $result .= '</div>'; 
        $result .= '</div>';     
      }         
      
      return $result;               
    }     
    
     //HORARIO CICLO ACTUAL DEL ALUMNO CONSULTADO POR PADRE
    public function padre_horario_ciclo_actual_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }


      $url = 'HorarioPadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&Token='.$token;
      //$url = 'Horario/?CodAlumno='.$codigo.'&Token='.$token;
      //var_dump($url);

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];      
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['HorarioDia']);  
      
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<div>';
        $result .= '<span class="zizou-16">';
        if ($json['HorarioDia'][$i]['CodDia'] == 1) {
          $result .= 'Lunes';
        }
        if ($json['HorarioDia'][$i]['CodDia'] == 2) {
          $result .= 'Martes';
        }       
        if ($json['HorarioDia'][$i]['CodDia'] == 3) {
          $result .= 'Miércoles';
        }    
        if ($json['HorarioDia'][$i]['CodDia'] == 4) {
          $result .= 'Jueves';
        }  
        if ($json['HorarioDia'][$i]['CodDia'] == 5) {
          $result .= 'Viernes';
        }                    
        if ($json['HorarioDia'][$i]['CodDia'] == 6) {
          $result .= 'Sábado';
        }           
        $result .= '</span>';
        $result .= '</div>'; 
        $result .= '<div class="panel-body red-line mb-7">';
        $result .= '<div class="panel-body-head-table">'; 
        $result .= '<ul class="tr">'; 
        $result .= '<li class="col-xs-1-5">'; 
        $result .= '<div><span>Inicio</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-xs-1-5">'; 
        $result .= '<div><span>Fin</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-xs-4-5">'; 
        $result .= '<div><span>Clase</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-xs-1-5">'; 
        $result .= '<div><span>Sede</span></div>'; 
        $result .= '</li>';  
        $result .= '<li class="col-xs-1-5">'; 
        $result .= '<div><span>Sección</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-xs-1-5">'; 
        $result .= '<div><span>Aula</span></div>'; 
        $result .= '</li>';                                                                                                                                                  
        $result .= '</ul>'; 
        $result .= '</div>';        
        
        //genera el tamano del array
        $tamano_int = count($json['HorarioDia'][$i]['Clases']); 
        
        for ($b=0; $b<$tamano_int; $b++) {
            $result .= '<div class="panel-table">'; 
            $result .= '<ul class="tr">'; 
            $result .= '<li class="col-xs-1-5">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $HoraInicio = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraInicio'], 0, 2);
            $HoraInicio = ltrim($HoraInicio,'0');
            $result .= $HoraInicio.':00';
            $result .='</span></div>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1-5">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $HoraFin = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraFin'], 0, 2);
            $HoraFin = ltrim($HoraFin,'0');
            $result .= $HoraFin.':00';                  
            $result .='</span></div>';                  
            $result .= '</li>';                        
            $result .= '<li class="col-xs-4-5">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['CursoNombre'];
            $result .= '</span></div>';                   
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1-5">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Sede'];
            $result .= '</span></div>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1-5">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Seccion'];
            $result .= '</span></div>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1-5">'; 
            $result .= '<div class="text-center"><span class="helvetica-14">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Salon'];
            $result .= '</span></div';  
            $result .= '</li>'; 
            $result .= '</ul>'; 
            $result .= '</div>';                              
        }               
        $result .= '</div>';
      }

      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div>';
        $result .= '<div class="panel-body mb-35">';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-12">';
        $result .= '<span>'.$error_mensaje.'</span>';
        $result .= '</li>';                
        $result .= '</ul>';  
        $result .= '</div>';
        $result .= '</div>'; 
        $result .= '</div>';     
      }         
      
      return $result;               
    }     
    
    
    //INASISTENCIAS ALUMNO
    public function inasistencias_alumno()
    {
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;
      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];       
      
      //limpio la variable para reutilizarla
      $result = '<div class="panel-body-head-table">';
      $result .= '<ul class="tr">';
      $result .= '<li class="col-xs-8">';
      $result .= '<div class="pl-7"><span class="text-left">Curso</span></div>';
      $result .= '</li>';
      // $result .= '<li class="col-xs-2">';
      // $result .= '<div class=""><span>Faltas</span></div>';
      // $result .= '</li>';
      $result .= '<li class="col-xs-4">';
      $result .= '<div class=""><span>Promedio</span></div>';
      $result .= '</li>';
      $result .= '</ul>';
      $result .= '</div>'; 
      $result .= '<div class="panel-table mis-cursos-content" id="miscursos">';

      //genera el tamano del array
      $tamano = count($json['Inasistencias']);

      if($tamano == 0 || $json['Inasistencias'] == NULL){
         $result = '<div class="panel-body-head-table">';
         $result .= '<div class="panel-table mis-cursos-content" id="miscursos">';
         $result .= '<ul class="tr">';
         $result .= '<li class="col-sm-4">';
         $result .= '<img class="img-center" src="{site_url}assets/img/no_courses_new.png">';
         $result .= '</li>';
         $result .= '<li class="col-sm-8 pt-28 pr-21">';
         $result .= '<p class="zizou-bold-16">¿Ningún curso en este Ciclo?</p>';
         if ($_COOKIE[$this->_cookies_prefix."TipoUser"] =='ALUMNO') {
         $result .= '<p class="helvetica-14">Entérate de <a href="http://www.upc.edu.pe/eventos" target="_blank" class="sb-link">otras</a> actividades que puedes realizar o <a href="" class="sb-link">reincorpórate</a></p>';
         }
         if ($_COOKIE[$this->_cookies_prefix."TipoUser"] =='PROFESOR') {
         $result .= '<p class="helvetica-14">Entérate de <a href="http://www.upc.edu.pe/eventos" target="_blank" class="sb-link">otras</a> actividades que puedes realizar</p>';
         } 
         $result .= '</li>';
         $result .= '</ul>';
         $result .= '</div>';
      }
      else{
        for ($i=0; $i<$tamano; $i++) {
          $result .= '<ul class="tr bg-muted">';
          $result .= '<li class="col-xs-8 helvetica-14 pb-0">';
          $result .= '<div>';
          $result .= '<span>'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          // $result .= '<li class="col-xs-2 ronnia-18 curso-faltas">';
          // $result .= '<div class="text-center">';
          // $result .= '<span>'.$json['Inasistencias'][$i]['Total'].'/'.$json['Inasistencias'][$i]['Maximo'].'</span>';
          // $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-4 ronnia-18 curso-promedio">';

          $codcurso = $json['Inasistencias'][$i]['CodCurso'];
          
          //Loop interno para calcular notas segun curso
          $url = 'Nota/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;

          $result_int=$this->services->curl_url($url);
          $json_int = json_decode($result_int, true);
        
          //genera el tamano del array
          $tamano_int = count($json_int['Notas']);
          $nota = 0;
          $porcentaje = 0;
          
          for ($b=0; $b<$tamano_int; $b++) {
            $porcentaje = rtrim($json_int['Notas'][$b]['Peso'],"%");
            $nota = ($json_int['Notas'][$b]['Valor']*$porcentaje)/100.0 + $nota; 
          }
            
          //Cambia el formato a 2 decimales
          $nota = number_format($nota, 2, '.', '');
          
          $result .= '<div class="borderless text-center"><span>'.$nota.'</span></div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-4 show-curso-detail"><div class="text-center"><span class="red zizou-12"><img class="mr-7" src="{site_url}assets/img/red_eye.png">Mostrar</span></div></li>';
          $result .= '</ul>';
        }
      }     
      $result .= '</div>'; 
      //Control de errores
      if ($error!='00000') {
        $result = '<div class="panel-body-head-table">';
        $result .= '<div class="panel-table mis-cursos-content" id="miscursos">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-4">';
        $result .= '<img class="img-center" src="{site_url}assets/img/no_courses_new.png">';
        $result .= '</li>';
        $result .= '<li class="col-sm-8 pt-28 pr-21">';
        if ($error_mensaje == "Ud. no se encuentra matriculado en el presente ciclo.") {
        $result .= '<p class="zizou-bold-16">¿Ningún curso en este Ciclo?</p>';
          if ($_COOKIE[$this->_cookies_prefix."TipoUser"] =='ALUMNO') {
          $result .= '<p class="helvetica-14">Entérate de <a href="http://www.upc.edu.pe/eventos" target="_blank" class="sb-link">otras</a> actividades que puedes realizar o <a href="" class="sb-link">reincorpórate</a></p>';
          }
          if ($_COOKIE[$this->_cookies_prefix."TipoUser"] =='PROFESOR') {
          $result .= '<p class="helvetica-14">Entérate de <a href="http://www.upc.edu.pe/eventos" target="_blank" class="sb-link">otras</a> actividades que puedes realizar</p>';
          }
        } else {
        $result .= '<p class="helvetica-14">'.$error_mensaje.'</p>';
        }
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '</div>';  
      }       
      
      return $result;               
              
    }  

    //INASISTENCIAS ALUMNO
    public function padre_inasistencias_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      // $codigo_alumno =  ee()->TMPL->fetch_param('codigo_alumno');  
      $codigo_alumno =  $_GET['codigo_alumno'];  
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      $url = 'InasistenciaPadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&Token='.$token;
      //$url = 'Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;
      //var_dump($url);

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];       
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Inasistencias']);
      
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<ul class="tr bg-muted">';
        $result .= '<li class="col-xs-7 helvetica-14 pb-0">';
        $result .= '<div>';
        $result .= '<span>'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2 ronnia-18">';
        $result .= '<div class="text-center">';
        $result .= '<span>'.$json['Inasistencias'][$i]['Total'].'/'.$json['Inasistencias'][$i]['Maximo'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-3 ronnia-18">';

        $codcurso = $json['Inasistencias'][$i]['CodCurso'];
        
        //Loop interno para calcular notas segun curso
        $url = 'NotaPadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&CodCurso='.$codcurso.'&Token='.$token;
        //$url = 'Nota/?CodAlumno='.$codigo.'&Token=1'.$token.'&CodCurso='.$codcurso;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result_int=$this->services->curl_url($url);
        $json_int = json_decode($result_int, true);
      
        //genera el tamano del array
        $tamano_int = count($json_int['Notas']);
        $nota = 0;
        $porcentaje = 0;
        
        for ($b=0; $b<$tamano_int; $b++) {
          $porcentaje = rtrim($json_int['Notas'][$b]['Peso'],"%");
          $nota = ($json_int['Notas'][$b]['Valor']*$porcentaje)/100 + $nota; 
        }
          
        //Cambia el formato a 2 decimales
        $nota = number_format($nota, 2, '.', '');
        
        $result .= '<div class="text-center borderless"><span>'.$nota.'</span></div>';
        $result .= '</li>';
      }     
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-12">';
        $result .= '<div>'.$error_mensaje.'</div>';
        $result .= '</li>';                
        $result .= '</ul>';  
        $result .= '</div>';     
      }       
      
      
      return $result;               
    }  
    

    //CURSOS QUE LLEVA UN ALUMNO
    public function buscar_curos_que_lleva_un_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"]; 
      $codalumno = ee()->TMPL->fetch_param('codalumno');  
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'InasistenciaProfesor/?Codigo='.$codigo.'&CodAlumno='.$codalumno.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];        
      
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Inasistencias']);

      $result .= '<div class="panel-body red-line">';
      $result .= '<div class="panel-body-head-table">';
      $result .= '<ul class="tr">';
      $result .= '<li class="col-xs-2"><div><span class="text-left pl-7">Código Curso</span></div></li>';
      $result .= '<li class="col-xs-7"><div><span class="text-left pl-7">Nombre</span></div></li>';
      $result .= '<li class="col-xs-3"><div><span>Inasistencias</span></div></li>';
      $result .= '</ul>';
      $result .= '</div>';
      $result .= '<div class="panel-table">';
      $result .= '<ul class="tr mis-cursos-row">';

      for ($i=0; $i<$tamano; $i++) {

        $result .= '<li class="col-xs-2 helvetica-14">';
        $result .= '<div>';
        $result .= '<span>'.$json['Inasistencias'][$i]['CodCurso'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-7 solano-bold-16">';
        $result .= '<div>';       
        $result .= '<span>'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-3 text-center helvetica-bold-16">';
        $result .= '<div class="borderless text-center">';
        $result .= '<span>'.$json['Inasistencias'][$i]['Total'].'/'.$json['Inasistencias'][$i]['Maximo'].'</span>';
        $result .= '</div>';
        $result .= '</li>';

      } 
      
      $result .= '</ul>'; 
      $result .= '</div>';
      $result .= '</div>';
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-body red-line">';
        $result .= '<div class="panel-body-content text-left p-28">';
        $result .= '<img class="pr-7" src="{site_url}assets/img/excla_red_1.png">';
        $result .= '<span class="helvetica-16 red">'.$error_mensaje.'</span>';
        $result .= '</div>';     
        $result .= '</div>';     
      }  
      
      return $result;           
    }       



    //CURSOS QUE LLEVA UN ALUMNO
    public function curos_que_lleva_un_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];     
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];        
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Inasistencias']);
      
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-8 helvetica-14 pl-7">';
        $result .= '<div>';
        $result .= '<span class="pr-7">'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-4 text-center helvetica-bold-14">';

          $codcurso = $json['Inasistencias'][$i]['CodCurso'];
          
          //Loop interno para calcular notas segun curso
          $url = 'Nota/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;

          $result_int=$this->services->curl_url($url);
          $json_int = json_decode($result_int, true);
        
          //genera el tamano del array
          $tamano_int = count($json_int['Notas']);
          $nota = 0;
          $porcentaje = 0;
          
          for ($b=0; $b<$tamano_int; $b++) {
            $porcentaje = rtrim($json_int['Notas'][$b]['Peso'],"%");
            $nota = ($json_int['Notas'][$b]['Valor']*$porcentaje)/100 + $nota; 
          }
          
          //Cambia el formato a 2 decimales
          $nota = number_format($nota, 2, '.', '');
        
        $result .= '<div>';
        $result .= '<span>'.$nota.'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
      }     
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-12">';
        $result .= '<div>'.$error_mensaje.'</div>';
        $result .= '</li>';                
        $result .= '</ul>';  
        $result .= '</div>';     
      }       
      
      return $result;           
    }   
    
    public function padre_curos_que_lleva_un_alumno_padres()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];     
      
      $codigo_alumno =  ee()->TMPL->fetch_param('codigo_alumno');
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      //CursoAlumnoPadre/?Codigo=UFSANGAR10&CodAlumno=U201321137&Token=af0b422650d743d5b5e2e24d785ebb5c20140325114353 
      $url = 'CursoAlumnoPadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&Token='.$token;

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];        
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Cursos']);
      
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-8 helvetica-14">';
        $result .= '<div>';
        $result .= '<span>'.$json['Cursos'][$i]['CursoNombre'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-4 text-center helvetica-bold-14">';

          $codcurso = $json['Cursos'][$i]['CodCurso'];
          
          //Loop interno para calcular notas segun curso
          $url = 'NotaPadre/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;

          $result_int=$this->services->curl_url($url);
          $json_int = json_decode($result_int, true);
        
          //genera el tamano del array
          $tamano_int = count($json_int['Notas']);
          $nota = 0;
          $porcentaje = 0;
          
          for ($b=0; $b<$tamano_int; $b++) {
            $porcentaje = rtrim($json_int['Notas'][$b]['Peso'],"%");
            $nota = ($json_int['Notas'][$b]['Valor']*$porcentaje)/100 + $nota; 
          }
          
          //Cambia el formato a 2 decimales
          $nota = number_format($nota, 2, '.', '');
        
        $result .= '<div>';
        $result .= '<span>'.$nota.'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
      }     
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-12">';
        $result .= '<div>'.$error_mensaje.'</div>';
        $result .= '</li>';                
        $result .= '</ul>';  
        $result .= '</div>';     
      }       
      
      return $result;           
    }

    //CURSOS QUE LLEVA UN ALUMNO CONSULTADO POR UN PADRE
    public function padre_cursos_que_lleva_un_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');
      //$token = $_SESSION["Token"];     
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      
      $url = 'InasistenciaPadres/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&Token='.$token;
      //$url = 'Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;


      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];        
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Inasistencias']);
      
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-9 helvetica-12">';
        $result .= '<div>';
        $result .= '<span>'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-3 text-center helvetica-bold-14">';

          $codcurso = $json['Inasistencias'][$i]['CodCurso'];
          
          //Loop interno para calcular notas segun curso
          $url = 'NotaPadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&CodCurso='.$codcurso.'&Token='.$token;
          //$url = 'Nota/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;
          

          $result_int=$this->services->curl_url($url);
          $json_int = json_decode($result_int, true);
        
          //genera el tamano del array
          $tamano_int = count($json_int['Notas']);
          $nota = 0;
          $porcentaje = 0;
          
          for ($b=0; $b<$tamano_int; $b++) {
            $porcentaje = rtrim($json_int['Notas'][$b]['Peso'],"%");
            $nota = ($json_int['Notas'][$b]['Valor']*$porcentaje)/100 + $nota; 
          }
          
          //Cambia el formato a 2 decimales
          $nota = number_format($nota, 2, '.', '');
        
        $result .= '<div>';
        $result .= '<span>'.$nota.'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
      }     
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-12">';
        $result .= '<div>'.$error_mensaje.'</div>';
        $result .= '</li>';                
        $result .= '</ul>';  
        $result .= '</div>';     
      }       
      
      return $result;           
    }   
    

    //TODOS LOS CURSOS QUE LLEVA UN ALUMNO
    public function todos_los_curos_que_lleva_un_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];       
      
      $tamano = count($json['Inasistencias']);
      $result = '';
      
      $result .= '<ul class="tr">';
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<a data-curso-id="'.$i.'" class="curso-link">';
        $result .= '<li class="bg-muted pl-7 col-sm-12 mb-5">';
        $result .= '<span class="zizou-16 col-sm-1 pr-0 pl-0">';
        $result .= '<img  src="{site_url}assets/img/black_arrow_tiny.png">';
        $result .= '</span>';
        $result .= '<span class="zizou-16 col-sm-11 pr-0 pl-7">';
        $result .= $json['Inasistencias'][$i]['CursoNombre'];
        $result .= '</span>';
        $result .= '</li>';
        $result .= '</a>';
      }
      $result .= '</ul>'; 
      
          
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<ul class="tr">';
        $result .= '<li class="bg-muted pl-7 col-sm-12 mb-5 pr-7">';
        $result .= '<span class="zizou-16">';
        $result .= $error_mensaje;
        $result .= '</span>';
        $result .= '</li>';                
        $result .= '</ul>';     
      }       
      
      return $result;
    
    } 
    
    public function padre_todos_los_curos_que_lleva_un_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      // $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');
      $codigo_alumno = $_GET['codigo_alumno'];
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'CursoAlumnoPadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&Token='.$token;

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];       
      
      $tamano = count($json['Cursos']);
      $result = '';
      $result .= '<div class="panel">';
      $result .= '<div class="panel-head no-bg">
        <div class="panel-title left">
          <h3>Saltar a un curso</h3>
        </div>
      </div>';
      $result .= '<div class="panel-body wobg">';
      $result .= '<div class="panel-table otras-acciones">';
      $result .= '<ul class="tr">';
      for ($i=0; $i<$tamano; $i++) {
        $curso = $json['Cursos'][$i]['CursoNombre'];
        $curso_min = str_replace(" ","",mb_convert_case($curso, MB_CASE_LOWER, "UTF-8"));
        $result .= '<a id="lnk_int_'. $json['Cursos'][$i]['CursoNombre'] .'" data-curso-id="'.$curso_min.'" class="curso-link-padres">';
        $result .= '<li class="clickeable bg-muted pl-7 col-sm-12 mb-5">';
        $result .= '<span class="zizou-16">';
        $result .= '<img class="pr-7" src="{site_url}assets/img/black_arrow_tiny.png">';
        $result .= $json['Cursos'][$i]['CursoNombre'];
        $result .= '</span>';
        $result .= '</li>';
        $result .= '</a>';
      }
      $result .= '</ul>'; 
      $result .= '</div>'; 
      $result .= '</div>'; 
      $result .= '</div>'; 
      
          
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<ul class="tr">';
        $result .= '<li class="bg-muted pl-7 col-sm-12 mb-5 pr-7">';
        $result .= '<span class="zizou-16">';
        $result .= $error_mensaje;
        $result .= '</span>';
        $result .= '</li>';                
        $result .= '</ul>';     
      }       
      
      return $result;
    
    } 
    
    //DETALLE DE CURSOS POR ALUMNO
    public function detalle_de_curos_por_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];

      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      
      $url = 'Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Cursos']);
      
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<ul class="tr bg-muted">';
        $result .= '<li class="col-sm-8 helvetica-12 pb-0">';
        $result .= '<div>';
        $result .= '<span>'.$json['Cursos'][$i]['CursoNombre'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2 helvetica-bold-14 curso-faltas">';
        $result .= '<div class="text-center">';
        $result .= '<span>'.$json['Inasistencias'][$i]['Total'].'/'.$json['Inasistencias'][$i]['Maximo'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2 helvetica-bold-14 curso-promedio">';

          $codcurso = $json['Inasistencias'][$i]['CodCurso'];
          
          //Loop interno para calcular notas segun curso
          $url = 'Nota/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;

          $result_int=$this->services->curl_url($url);
          $json_int = json_decode($result_int, true);
        
          //genera el tamano del array
          $tamano_int = count($json_int['Notas']);
          $nota = 0;
          $porcentaje = 0;
          
          for ($b=0; $b<$tamano_int; $b++) {
            $porcentaje = rtrim($json_int['Notas'][$b]['Peso'],"%");
            $nota = ($json_int['Notas'][$b]['Valor']*$porcentaje)/100 + $nota; 
          }
          
          //Cambia el formato a 2 decimales
          $nota = number_format($nota, 2, '.', '');
        
        $result .= '<div class="text-center"><span>'.$nota.'</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-4 show-curso-detail"><div class="text-center"><span><img src="{site_url}assets/img/ojo.png"></span></div></li>';
        $result .= '</ul>';
      }     
      
      return $result;               
    }  
    
    
    //NOTAS DE UN ALUMNO POR CURSO
    public function notas_alumno_por_curso()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      
      $url = 'Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Inasistencias']);
      
      for ($i=0; $i<$tamano; $i++) {

        $result .= '<div class="panel curso-detalle">';
        $result .= '<div class="panel-body" id="curso-'.$i.'">';
        $result .= '<div class="panel-body-head pl-7 left">';
        $result .= '<ul class="tr">';
        $result .= '<span>'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
        $result .= '</ul>';
        $result .= '</div>';
        
        $codcurso = $json['Inasistencias'][$i]['CodCurso'];
        
        //Loop interno para calcular notas segun curso
        $url = 'Nota/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result_int=$this->services->curl_url($url);
        $json_int = json_decode($result_int, true);           
      
        //genera el tamano del array
        $tamano_int = count($json_int['Notas']);
        $nota = 0;
        $porcentaje = 0;
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-2 col-xs-3">';
        $result .= '<div class="black-text borderless">';
        $result .= '<span class="pl-7 helv-neue-16">FÓRMULA:</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-10 col-xs-9">';
        $result .= '<div class="black-text">';
        $result .= '<span class="ronnia-16">'.$json_int['Formula'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';    
        $result .= '<div class="panel-body-head-table pb-7 black-border-top">';

        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-1 hidden-xs">';
        $result .= '<div class="br-gl text-center"><span>Tipo</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2 hidden-xs">';
        $result .= '<div class="br-gl text-center"><span>Número</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-5 col-xs-8">';
        $result .= '<div class="br-gl text-center"><span>Evaluación</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2 col-xs-2">';
        $result .= '<div class="br-gl text-center"><span>Peso</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2 col-xs-2">';
        $result .= '<div class="br-gl text-center"><span>Nota</span></div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>'; 
        $result .= '<div class="panel-table gl-border-top">'; 
        $result .= '<ul class="tr">';          

        for ($b=0; $b<$tamano_int; $b++) {
          
          $porcentaje = rtrim($json_int['Notas'][$b]['Peso'],"%");
          $nota = ($json_int['Notas'][$b]['Valor']*$porcentaje)/100 + $nota; 
            
          $result .= '<li class="col-sm-1 hidden-xs">';
          $result .= '<div class="text-center">';
          $result .= '<span class="helv-neue-16">'.$json_int['Notas'][$b]['NombreCorto'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-2 hidden-xs">';
          $result .= '<div class="text-center">';
          $result .= '<span class="ronnia-16">'.$json_int['Notas'][$b]['NroEvaluacion'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-5 hidden-xs">';
          $result .= '<div>';
          $result .= '<span class="helvetica-16">'.$json_int['Notas'][$b]['NombreEvaluacion'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="hidden-sm hidden-md hidden-lg col-xs-8">';
          $result .= '<div>';
          $result .= '<span class="helvetica-16">'.$json_int['Notas'][$b]['NombreCorto'].$json_int['Notas'][$b]['NroEvaluacion'].' '.$json_int['Notas'][$b]['NombreEvaluacion'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-2 col-xs-2">';
          $result .= '<div class="text-center">';
          $result .= '<span class="ronnia-18">'.$json_int['Notas'][$b]['Peso'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-2 col-xs-2">';
          $result .= '<div class="borderless text-center">';
          $result .= '<span class="ronnia-18">'.$json_int['Notas'][$b]['Valor'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          
        }
          
        //Cambia el formato a 2 decimales
        $nota = number_format($nota, 2, '.', '');
          
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '<div class="panel-table observaciones">';
        $result .= ' <ul class="tr">';
        $result .= '<li class="col-xs-8">';
        $result .= '<div class="text-left borderless">';
        $result .= '<span class="helvetica-bold-16 pl-7 text-muted uppercase">Observaciones:</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="text-center borderless">';
        $result .= '<span class="helv-neue-14 text-muted uppercase">Nota al '.$json_int['PorcentajeAvance'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="text-center">';
        $result .= '<span class="helvetica-bold-16 text-muted">'.$nota.'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '<a class="black-text go-to-top text-right" href="#top">';
        $result .= '<div class="zizou-14 pt-14 mb-35">';
        $result .= 'Regresar a lista de cursos';
        $result .= '<img class="ml-7" src="{site_url}assets/img/black_arrow_tiny_up.png" alt="">';
        $result .= '</div>';
        $result .= '</a>';          

      }     
      
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div>';
        $result .= '<div class="panel-body mb-35">';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr mis-cursos-row">';
        $result .= '<li class="col-xs-12">';
        $result .= '<span>'.$error_mensaje.'</span>';
        $result .= '</li>';                
        $result .= '</ul>';  
        $result .= '</div>';
        $result .= '</div>'; 
        $result .= '</div>';      
      }         
      
      return $result;           
    }      
     
    public function padre_notas_alumno_por_curso()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      // $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');
      $codigo_alumno =  $_GET['codigo_alumno'];
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      
      $url = 'InasistenciaPadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&Token='.$token;
      //InasistenciaPadre/?Codigo=UFSANGAR10&CodAlumno=U201110028&CodCurso=CO18&Token=af0b422650d743d5b5e2e24d785ebb5c20140325114353
      //var_dump($url);

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Inasistencias']);
      
      for ($i=0; $i<$tamano; $i++) {
        $curso = $json['Inasistencias'][$i]['CursoNombre'];
        $curso_min = str_replace(" ","",mb_convert_case($curso, MB_CASE_LOWER, "UTF-8"));
        $result .= '<div class="panel curso-detalle">';
        $result .= '<div class="panel-body" id="'.$curso_min.'">';
        $result .= '<div class="panel-body-head left">';
        $result .= '<ul class="tr">';
        $result .= '<span>'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
        $result .= '</ul>';
        $result .= '</div>';
        
        $codcurso = $json['Inasistencias'][$i]['CodCurso'];
        
        //Loop interno para calcular notas segun curso
        $url = 'NotaPadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&Token='.$token.'&CodCurso='.$codcurso;
        //var_dump($url);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result_int=$this->services->curl_url($url);
        //var_dump($result_int);
        $json_int = json_decode($result_int, true);           
      
        //genera el tamano del array
        $tamano_int = count($json_int['Notas']);
        $nota = 0;
        $porcentaje = 0;

        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="black-text borderless">';
        $result .= '<span class="pl-7 helv-neue-16">FÓRMULA:</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-10">';
        $result .= '<div class="black-text">';
        $result .= '<span class="ronnia-16">'.$json_int['Formula'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';    
        $result .= '<div class="panel-body-head-table pb-7 black-border-top">';

        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-1">';

        $result .= '<div class="br-gl"><span>Tipo</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div><span>Número</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-5">';
        $result .= '<div class="br-gl"><span>Evaluación</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="br-gl"><span>Peso</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div><span>Nota</span></div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>'; 
        $result .= '<div class="panel-table gl-border-top">'; 
        $result .= '<ul class="tr">';          

        for ($b=0; $b<$tamano_int; $b++) {
          
          $porcentaje = rtrim($json_int['Notas'][$b]['Peso'],"%");
          $nota = ($json_int['Notas'][$b]['Valor']*$porcentaje)/100 + $nota; 
            
          $result .= '<li class="col-xs-1">';
          $result .= '<div class="text-center">';
          $result .= '<span class="helv-neue-16">'.$json_int['Notas'][$b]['NombreCorto'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-2">';
          $result .= '<div class="text-center">';
          $result .= '<span class="ronnia-16">'.$json_int['Notas'][$b]['NroEvaluacion'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-5">';
          $result .= '<div>';
          $result .= '<span class="helvetica-16">'.$json_int['Notas'][$b]['NombreEvaluacion'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-2">';
          $result .= '<div class="text-center">';
          $result .= '<span class="ronnia-18">'.$json_int['Notas'][$b]['Peso'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-2">';
          $result .= '<div class="borderless text-center">';
          $result .= '<span class="ronnia-18">'.$json_int['Notas'][$b]['Valor'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          
        }
          
        //Cambia el formato a 2 decimales
        $nota = number_format($nota, 2, '.', '');
          
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '<div class="panel-table observaciones">';
        $result .= ' <ul class="tr">';
        $result .= '<li class="col-xs-8">';
        $result .= '<div class="text-left borderless">';
        $result .= '<span class="helvetica-bold-16 pl-7 text-muted uppercase">Observaciones:</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="text-center borderless">';
        $result .= '<span class="helv-neue-14 text-muted uppercase">Nota al '.$json_int['PorcentajeAvance'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="text-center">';
        $result .= '<span class="helvetica-bold-16 text-muted">'.$nota.'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '<a id="lnk_int_Top" class="black-text go-to-top text-right" href="#top">';
        $result .= '<div class="zizou-14 pt-14 mb-35">';
        $result .= 'Regresar a lista de cursos';
        $result .= '<img class="ml-7" src="{site_url}assets/img/black_arrow_tiny_up.png" alt="">';
        $result .= '</div>';
        $result .= '</a>';          

      }     
      
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div>';
        $result .= '<div class="panel-body mb-35">';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr mis-cursos-row">';
        $result .= '<li class="col-xs-12">';
        $result .= '<span>'.$error_mensaje.'</span>';
        $result .= '</li>';                
        $result .= '</ul>';  
        $result .= '</div>';
        $result .= '</div>'; 
        $result .= '</div>';      
      }         
      
      return $result;           
    }    
    
    //TRAMITES REALIZADOS POR ALUMNO     
    public function tramites_realizados_por_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'TramiteRealizado/?CodAlumno='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      
      $json = json_decode($result, true);
      
      //limpio la variable para reutilizarla
      $result = '';
      
      $CodError = $json['CodError'];
      $MsgError = $json['MsgError'];
      
      if ($CodError == '00051') {
        $result .= '<div class="panel-body bg-muted info-border">';
        $result .= '<img class="m-14 pull-left" src="{site_url}assets/img/check_xl.png" alt="">';
        $result .= '<div class="inline-block p-28"><span class="text-info helvetica-18">';
        $result .= $MsgError;
        $result .= '</span>';
        $result .= '</div>';
        $result .= '</div>';
      } else {
        
        $tamano = count($json['TramitesRealizados']);
        $result .= '<div class="panel-body bg-muted red-line">';
        $result .= '<div class="panel-body-head-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="fecha"><span>No. Solicitud</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-6">';
        $result .= '<div class="fecha"><span>DESCRIPCIóN DEL TRáMITE</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class=""><span>Fecha de Inicio</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class=""><span>Estado</span></div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';
        
        for ($i=0; $i<$tamano; $i++) {  
          $result .= '<div class="panel-table"> ';
          $result .= '<ul class="tr">';
          $result .= '<li class="col-xs-2 solano-bold-18 uppercase">';
          $result .= '<div class="text-center">';
          $result .= '<span>'.$json['TramitesRealizados'][$i]['NroSolicitud'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-6 helvetica-14">';
          $result .= '<div>';
          $result .= '<span>'.$json['TramitesRealizados'][$i]['Nombre'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-2 solano-bold-18 uppercase">';
          $result .= '<div class="text-center nrb">';
          $result .= '<span>'.$json['TramitesRealizados'][$i]['Fecha'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          
          if ($json['TramitesRealizados'][$i]['Estado']=='NO PROCEDE') {
            $result .= '<li class="col-xs-2 pdte-tr solano-bold-18">';
            $result .= '<div class="text-center">';
            $result .= '<span>'.$json['TramitesRealizados'][$i]['Estado'].'</span>';
            $result .= '</div>';
            $result .= '</li>';
          } 
          if ($json['TramitesRealizados'][$i]['Estado']=='PROCEDE') {
            $result .= '<li class="col-xs-2 apr-tr solano-bold-18">';
            $result .= '<div class="text-center">';
            $result .= '<span>'.$json['TramitesRealizados'][$i]['Estado'].'</span>';
            $result .= '</div>';
            $result .= '</li>';
          }
          if ($json['TramitesRealizados'][$i]['Estado']=='RESPONDIDA') {
            $result .= '<li class="col-xs-2 apr-tr solano-bold-18">';
            $result .= '<div class="text-center">';
            $result .= '<span>'.$json['TramitesRealizados'][$i]['Estado'].'</span>';
            $result .= '</div>';
            $result .= '</li>';
          }         
          $result .= '</ul>';
          $result .= '</div>';
        }          
      }
  
      return $result;                 
    }  
    
    public function padre_tramites_realizados_por_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'TramiteRealizadoPadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&Token='.$token;
      //var_dump($url);

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      
      //limpio la variable para reutilizarla
      $result = '';
      
      $CodError = $json['CodError'];
      $MsgError = $json['MsgError'];
      
      if ($CodError == '00051') {
        $result .= '<img class="m-14 pull-left" src="{site_url}assets/img/check_xl.png" alt="">';
        $result .= '<div class="inline-block p-28"><span class="text-info helvetica-18">';
        $result .= $MsgError;
        $result .= '</span>';
        $result .= '</div>';
      } else {
        
        $tamano = count($json['TramitesRealizados']);
        
        $result .= '<div class="panel-body-head-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="fecha"><span>No. Solicitud</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-6">';
        $result .= '<div class="fecha"><span>DESCRIPCIóN DEL TRáMITE</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class=""><span>Fecha de Inicio</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class=""><span>Estado</span></div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';
        
        for ($i=0; $i<$tamano; $i++) {  
          $result .= '<div class="panel-table"> ';
          $result .= '<ul class="tr">';
          $result .= '<li class="col-xs-2 solano-bold-18 uppercase">';
          $result .= '<div class="text-center">';
          $result .= '<span>'.$json['TramitesRealizados'][$i]['NroSolicitud'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-6 helvetica-14">';
          $result .= '<div>';
          $result .= '<span>'.$json['TramitesRealizados'][$i]['Nombre'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-2 solano-bold-18 uppercase">';
          $result .= '<div class="text-center nrb">';
          $result .= '<span>'.$json['TramitesRealizados'][$i]['Fecha'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          
          if ($json['TramitesRealizados'][$i]['Estado']=='NO PROCEDE') {
            $result .= '<li class="col-xs-2 pdte-tr solano-bold-18">';
            $result .= '<div class="text-center">';
            $result .= '<span>'.$json['TramitesRealizados'][$i]['Estado'].'</span>';
            $result .= '</div>';
            $result .= '</li>';
          } 
          if ($json['TramitesRealizados'][$i]['Estado']=='PROCEDE') {
            $result .= '<li class="col-xs-2 apr-tr solano-bold-18">';
            $result .= '<div class="text-center">';
            $result .= '<span>'.$json['TramitesRealizados'][$i]['Estado'].'</span>';
            $result .= '</div>';
            $result .= '</li>';
          }
          if ($json['TramitesRealizados'][$i]['Estado']=='RESPONDIDA') {
            $result .= '<li class="col-xs-2 apr-tr solano-bold-18">';
            $result .= '<div class="text-center">';
            $result .= '<span>'.$json['TramitesRealizados'][$i]['Estado'].'</span>';
            $result .= '</div>';
            $result .= '</li>';
          }         
          $result .= '</ul>';
        }          
      }
  
      return $result;                 
    } 

    //LISTADO DE COMPANEROS DE CLASE POR CURSO    
    public function companeros_clase_por_curso()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      $codcurso = ee()->TMPL->fetch_param('codcurso');
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'Companeros/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;

      $result=$this->services->curl_url($url);
      
      return $result;         
    }
    
    //PROXIMA BOLETA DEL ALUMNO   
    public function proxima_boleta_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'PagoPendiente/?CodAlumno='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      //Control de errores
      if ($json['CodError']!='00000') {
        $result = '<p><span class="solano-14 uppercase">'.$json['MsgError'].'</span></p>'; 
      } else {
        //limpio la variable para reutilizarla
        $result = ''; 
        $result .= '<h3 class="monto">S/.'.$json['PagosPendientes'][0]['Total'].'</h3>';
        $result .= '<span class="uppercase">Vence el '.$json['PagosPendientes'][0]['FecVencimiento'].'</span>';
      }
      
      return $result;         
    } 
    
     //PROXIMA BOLETA DEL ALUMNO CONSULTADA POR EL PADRE 
    public function padre_proxima_boleta_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      // $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');
      $codigo_alumno =  $_GET['codigo_alumno'];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'PagoPendientePadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&Token='.$token;
      //$url = 'PagoPendiente/?CodAlumno='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      //Control de errores
      if ($json['CodError']!='00000') {
        $result = '<p><span class="solano-14 uppercase">'.$json['MsgError'].'</span></p>'; 
      } else {
        //limpio la variable para reutilizarla
        $result = ''; 
        $result .= '<h3 class="monto">S/.'.$json['PagosPendientes'][0]['Total'].'</h3>';
        $result .= '<span class="uppercase">Vence el '.$json['PagosPendientes'][0]['FecVencimiento'].'</span>';
      }
      
      return $result;         
    } 
    //BOLETAS PENDIENTES DEL ALUMNO   
    public function boletas_pendientes_alumno()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'PagoPendiente/?CodAlumno='.$codigo.'&Token='.$token;
      //var_dump($url);

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      
      if (($json['CodError']=='00041') || ($json['CodError']=='00003')) {
        
        $result = '<div class="panel-body info-border">';
        $result .= '<div class="panel-body-content text-left">';
        $result .= '<img class="m-14 pull-left" src="{site_url}assets/img/check_xl.png" alt="">';
        if ($json['MsgError']=="Ud. no presenta deudas pendientes.") {
          $result .= '<div class="inline-block p-28"><span class="text-info helvetica-18">Ud. no presenta deudas.</span>';          
        } else {
          $result .= '<div class="inline-block p-28"><span class="text-info helvetica-18">'.$json['MsgError'].'</span>';          
        }
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        return $result;
        
      } else {
        $result = '';
        for ($i=0; $i < count($json['PagosPendientes']); $i++) { 
           $fecha_actual = strtotime(date("d-m-Y H:i:00",time()));
           $fech_emision_format = substr($json['PagosPendientes'][$i]['FecEmision'], 6,2).'-'.substr($json['PagosPendientes'][$i]['FecEmision'], 4,2).'-'.substr($json['PagosPendientes'][$i]['FecEmision'], 0,4);
           $fech_vencimiento_format = substr($json['PagosPendientes'][$i]['FecVencimiento'], 6,2).'-'.substr($json['PagosPendientes'][$i]['FecVencimiento'], 4,2).'-'.substr($json['PagosPendientes'][$i]['FecVencimiento'], 0,4);
           $fecha_vencimiento_format1 = substr($json['PagosPendientes'][$i]['FecVencimiento'], 0,4).'-'.substr($json['PagosPendientes'][$i]['FecVencimiento'], 4,2).'-'.substr($json['PagosPendientes'][$i]['FecVencimiento'], 6,2);
           $fech_vencimiento = strtotime($fech_vencimiento_format1.' 12:00:00');

           $result = '<div class="panel-body">';
           $result .= '<div class="panel-body-head left">';
           $result .= '<udm_load_ispell_data(agent, var, val1, val2, flag) class="tr">';
           $result .= '<span class="solano-20">Cuota '.$json['PagosPendientes'][$i]['NroCuota'].'</span>';
           $result .= '</ul>';
           $result .= '</div>';
           $result .= '<div class="panel-table">';
           $result .= '<ul class="tr">';
           $result .= '<li class="col-xs-4 pl-7">';
           $result .= '<div class="nrb helvetica-16">';
           $result .= '<div>';
           $result .= '<strong>DOCUMENTO: </strong>'.$json['PagosPendientes'][$i]['NroDocumento'].'';
           $result .= '</div> ';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '<li class="col-xs-3">';
           $result .= '<div class="nrb helvetica-16">';
           $result .= '<div>';
           $result .= '<strong>EMITIDA: </strong>'.$fech_emision_format.'';
           $result .= '</div>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '<li class="col-xs-3">';
           $result .= '<div class="nrb helvetica-16">';
           $result .= '<div>';
           $result .= '<strong>VENCE: </strong>'.$fech_vencimiento_format.'';
           $result .= '</div>';
           $result .= '</div>';
           $result .= '</li>';

           if ($fecha_actual > $fech_vencimiento) {
              $result .= '<li class="col-xs-2 apr-tr">';
              $result .= '<div class="text-center">';
              $result .= '<span class="helvetica-bold-14">A TIEMPO</span>'; /* pdte-tr*/
              $result .= '</div>';
              $result .= '</li>';
           }
           else{
              $result .= '<li class="col-xs-2 pdte-tr">';
              $result .= '<div class="text-center">';
              $result .= '<span class="helvetica-bold-14">A TIEMPO</span>'; /* pdte-tr*/
              $result .= '</div>';
              $result .= '</li>';
           }

           $result .= '</ul>';
           $result .= '</div>';
           $result .= '<div class="panel-body-head-table gm-border-top">';
           $result .= '<ul class="tr">';
           $result .= '<li class="col-xs-10">';
           $result .= '<div><span>Detalle</span></div>';
           $result .= '</li>';
           $result .= '<li class="col-xs-2">';
           $result .= '<div><span>Monto (S/.)</span></div>';
           $result .= '</li>';
           $result .= '</ul>';
           $result .= '</div>';
           $result .= '<div class="panel-table gm-border-top pl-7">';
           $result .= '<ul class="tr">';
           $result .= '<li class="col-xs-10">';
           $result .= '<div>';
           $result .= '<span class="helvetica-16">Importe</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '<li class="col-xs-2">';
           $result .= '<div class="text-center">';
           $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Importe'].'</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '</ul>';
           $result .= '<ul class="tr">';
           $result .= '<li class="col-xs-10">';
           $result .= '<div>';
           $result .= '<span class="helvetica-16">Descuento</span>'; 
           $result .= '</div>';
           $result .= '</li>';
           $result .= '<li class="col-xs-2">';
           $result .= '<div class="text-center">';
           $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Descuento'].'</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '</ul>';
           $result .= '<ul class="tr">';
           $result .= '<li class="col-xs-10">';
           $result .= '<div>';
           $result .= '<span class="helvetica-16">Impuesto</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '<li class="col-xs-2">';
           $result .= '<div class="text-center">';
           $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Impuesto'].'</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '</ul>';
           $result .= '<ul class="tr">';
           $result .= '<li class="col-xs-10">';
           $result .= '<div>';
           $result .= '<span class="helvetica-16">Cancelado</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '<li class="col-xs-2">';
           $result .= '<div class="text-center">';
           $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Cancelado'].'</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '</ul>';
           $result .= '<ul class="tr">';
           $result .= '<li class="col-xs-10">';
           $result .= '<div>';
           $result .= '<span class="helvetica-16">Saldo</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '<li class="col-xs-2">';
           $result .= '<div class="text-center">';
           $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Saldo'].'</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '</ul>';
           $result .= '<ul class="tr">';
           $result .= '<li class="col-xs-10">';
           $result .= '<div>';
           $result .= '<span class="helvetica-16">Mora</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '<li class="col-xs-2">';
           $result .= '<div class="text-center">';
           $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Mora'].'</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '</ul>';
           $result .= '</div>';
           $result .= '<div class="panel-table observaciones">';
           $result .= '<ul class="tr">';
           $result .= '<li class="col-xs-10 pl-7">';
           $result .= '<div>';
           $result .= '<span class="uppercase helvetica-16 text-muted">TOTAL</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '<li class="col-xs-2">';
           $result .= '<div class="text-center">';
           $result .= '<span class="helvetica-16 text-muted uppercase">'.$json['PagosPendientes'][$i]['Total'].'</span>';
           $result .= '</div>';
           $result .= '</li>';
           $result .= '</ul>';
           $result .= '</div>'; 
        }
        return $result;

      } 
           
    } 

   public function padres_boletas_pendientes_alumno()
    {
         //$codigo = $_SESSION["Codigo"];
         //$token = $_SESSION["Token"];
         
         $codigo_alumno = ee()->TMPL->fetch_param('codigo_alumno');
         $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
         $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

         ee()->db->select('*');
         ee()->db->where('codigo',$codigo);
         $query_modelo_result = ee()->db->get('exp_user_upc_data');

         foreach($query_modelo_result->result() as $row){
           $token = $row->token;
         }

         $url = 'PagoPendientePadre/?Codigo='.$codigo.'&CodAlumno='.$codigo_alumno.'&Token='.$token;
         //var_dump($url);
         $ch = curl_init($url);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_URL,$url);
         $result=$this->services->curl_url($url);
         //var_dump($result);
         $json = json_decode($result, true);
         
        
         
         if (($json['CodError']=='00041') || ($json['CodError']=='00003')) {
           
           $result = '<div class="panel-body info-border">';
           $result .= '<div class="panel-body-content text-left">';
           $result .= '<img class="m-14 pull-left" src="{site_url}assets/img/check_xl.png" alt="">';
           $result .= '<div class="inline-block p-28"><span class="text-info helvetica-18">'.$json['MsgError'].'</span>';
           $result .= '</div>';
           $result .= '</div>';
           $result .= '</div>';
           return $result;
           
         } else {
           $result = '';
           for ($i=0; $i < count($json['PagosPendientes']); $i++) { 
           // for ($i=0; $i < 5; $i++) { 
              $fecha_actual = strtotime(date("d-m-Y H:i:00",time()));
              $fech_emision_format = substr($json['PagosPendientes'][$i]['FecEmision'], 6,2).'-'.substr($json['PagosPendientes'][$i]['FecEmision'], 4,2).'-'.substr($json['PagosPendientes'][$i]['FecEmision'], 0,4);
              $fech_vencimiento_format = substr($json['PagosPendientes'][$i]['FecVencimiento'], 6,2).'-'.substr($json['PagosPendientes'][$i]['FecVencimiento'], 4,2).'-'.substr($json['PagosPendientes'][$i]['FecVencimiento'], 0,4);
              $fecha_vencimiento_format1 = substr($json['PagosPendientes'][$i]['FecVencimiento'], 0,4).'-'.substr($json['PagosPendientes'][$i]['FecVencimiento'], 4,2).'-'.substr($json['PagosPendientes'][$i]['FecVencimiento'], 6,2);
              $fech_vencimiento = strtotime($fech_vencimiento_format1.' 12:00:00');

              $result = '<div class="panel-body">';
              $result .= '<div class="panel-body-head left">';
              $result .= '<udm_load_ispell_data(agent, var, val1, val2, flag) class="tr">';
              $result .= '<span class="solano-20">Cuota '.$json['PagosPendientes'][$i]['NroCuota'].'</span>';
              $result .= '</ul>';
              $result .= '</div>';
              $result .= '<div class="panel-table">';
              $result .= '<ul class="tr">';
              $result .= '<li class="col-xs-4 pl-7">';
              $result .= '<div class="nrb helvetica-16">';
              $result .= '<div>';
              $result .= '<strong>DOCUMENTO: </strong>'.$json['PagosPendientes'][$i]['NroDocumento'].'';
              $result .= '</div> ';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-3">';
              $result .= '<div class="nrb helvetica-16">';
              $result .= '<div>';
              $result .= '<strong>EMITIDA: </strong>'.$fech_emision_format.'';
              $result .= '</div>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-3">';
              $result .= '<div class="nrb helvetica-16">';
              $result .= '<div>';
              $result .= '<strong>VENCE: </strong>'.$fech_vencimiento_format.'';
              $result .= '</div>';
              $result .= '</div>';
              $result .= '</li>';

              if ($fecha_actual > $fech_vencimiento) {
                 $result .= '<li class="col-xs-2 apr-tr">';
                 $result .= '<div class="text-center">';
                 $result .= '<span class="helvetica-bold-14">A TIEMPO</span>'; /* pdte-tr*/
                 $result .= '</div>';
                 $result .= '</li>';
              }
              else{
                 $result .= '<li class="col-xs-2 pdte-tr">';
                 $result .= '<div class="text-center">';
                 $result .= '<span class="helvetica-bold-14">A TIEMPO</span>'; /* pdte-tr*/
                 $result .= '</div>';
                 $result .= '</li>';
              }

              $result .= '</ul>';
              $result .= '</div>';
              $result .= '<div class="panel-body-head-table gm-border-top">';
              $result .= '<ul class="tr pb-7">';
              $result .= '<li class="col-xs-10">';
              $result .= '<div><span>Detalle</span></div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2">';
              $result .= '<div><span>Monto (S/.)</span></div>';
              $result .= '</li>';
              $result .= '</ul>';
              $result .= '</div>';
              $result .= '<div class="panel-table gm-border-top pl-7">';
              $result .= '<ul class="tr">';
              $result .= '<li class="col-xs-10">';
              $result .= '<div>';
              $result .= '<span class="helvetica-16">Importe</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2">';
              $result .= '<div class="text-center">';
              $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Importe'].'</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '</ul>';
              $result .= '<ul class="tr">';
              $result .= '<li class="col-xs-10">';
              $result .= '<div>';
              $result .= '<span class="helvetica-16">Descuento</span>'; 
              $result .= '</div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2">';
              $result .= '<div class="text-center">';
              $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Descuento'].'</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '</ul>';
              $result .= '<ul class="tr">';
              $result .= '<li class="col-xs-10">';
              $result .= '<div>';
              $result .= '<span class="helvetica-16">Impuesto</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2">';
              $result .= '<div class="text-center">';
              $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Impuesto'].'</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '</ul>';
              $result .= '<ul class="tr">';
              $result .= '<li class="col-xs-10">';
              $result .= '<div>';
              $result .= '<span class="helvetica-16">Cancelado</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2">';
              $result .= '<div class="text-center">';
              $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Cancelado'].'</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '</ul>';
              $result .= '<ul class="tr">';
              $result .= '<li class="col-xs-10">';
              $result .= '<div>';
              $result .= '<span class="helvetica-16">Saldo</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2">';
              $result .= '<div class="text-center">';
              $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Saldo'].'</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '</ul>';
              $result .= '<ul class="tr">';
              $result .= '<li class="col-xs-10">';
              $result .= '<div>';
              $result .= '<span class="helvetica-16">Mora</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2">';
              $result .= '<div class="text-center">';
              $result .= '<span class="helvetica-16">'.$json['PagosPendientes'][$i]['Mora'].'</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '</ul>';
              $result .= '</div>';
              $result .= '<div class="panel-table mb-14 observaciones">';
              $result .= '<ul class="tr">';
              $result .= '<li class="col-xs-10 pl-7">';
              $result .= '<div>';
              $result .= '<span class="uppercase text-muted helvetica-16">TOTAL</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2">';
              $result .= '<div class="text-center">';
              $result .= '<span class="helvetica-16 text-muted uppercase">'.$json['PagosPendientes'][$i]['Total'].'</span>';
              $result .= '</div>';
              $result .= '</li>';
              $result .= '</ul>';
              $result .= '</div>'; 
           }
           return $result;

         } 
              
       }                  
    
    //POBLAR ESPACIOS DEPORTIVOS - SEDE  
    public function poblar_espacios_deportivos_sede()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      /*
      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      */
      $token = $_COOKIE[$this->_cookies_prefix."Token"];
      $url = 'PoblarED/?CodAlumno='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $sedes = '';
      $espacios = '';
      $actividades = '';
      
      $tamanoSedes = count($json['Sedes']);
    
      for ($i=0; $i<$tamanoSedes; $i++) {
        $tamanoEspacios = count($json['Sedes'][$i]['espacios']);
        $espacios .= '<select name="CodED" class="reservas-select selectpicker relative arrow form-control espacio" id="sede-'.$json['Sedes'][$i]['key'].'" data-sede="'. $json['Sedes'][$i]['key'] .'">';
        $espacios .= '<option value="" disabled selected>Seleccionar espacio</option>'; 
        for ($j=0; $j<$tamanoEspacios; $j++) {
          $espacios .= '<option value="'.$json['Sedes'][$i]['espacios'][$j]['codigo'].'">';
          $espacios .= $json['Sedes'][$i]['espacios'][$j]['nombre'];
          $espacios .= '</option>';

          $tamanoActividades = count($json['Sedes'][$i]['espacios'][$j]['actividades']);
          $actividades .= '<select class="reservas-select selectpicker relative arrow form-control actividad" name="CodActiv" id="actividad-'.$json['Sedes'][$i]['espacios'][$j]['codigo'].'" data-espacio="' . $json['Sedes'][$i]['espacios'][$j]['codigo'] . '">';
          for ($k=0; $k<$tamanoActividades; $k++) {
            $actividades .= '<option value="'.$json['Sedes'][$i]['espacios'][$j]['actividades'][$k]['codigo'].'">';
            $actividades .= $json['Sedes'][$i]['espacios'][$j]['actividades'][$k]['nombre'];
            $actividades .= '</option>';
          }
          $actividades .= '</select>';
        }
        $espacios .= '</select>';
      }
      $sedes .= '</select>';
      
      return $sedes . $espacios . $actividades;
    }
    
    //POBLAR ESPACIOS DEPORTIVOS - Espacios  
    public function poblar_espacios_deportivos_espacios()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'PoblarED/?CodAlumno='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $result = '';
      
      $tamano = count($json['Sedes']);
      
      for ($i=0; $i<$tamano; $i++) {

        $tamano_int = count($json['Sedes'][$i]['espacios']);
        $result .= '<select name="CodED" class="reservas-select selectpicker relative arrow form-control" id="sede-'.$json['Sedes'][$i]['key'].'">';

        $result .= '<option value="" disabled selected>Seleccionar espacio</option>'; 
          for ($a=0; $a<$tamano_int; $a++) {
            $result .= '<option value="'.$json['Sedes'][$i]['espacios'][$a]['codigo'].'">';
            $result .= $json['Sedes'][$i]['espacios'][$a]['nombre'];
            $result .= '</option>';
          }
        $result .= '</select>';

      }
      return $result;          
    } 
    
    //POBLAR ESPACIOS DEPORTIVOS - ACTIVIDAD  
    public function poblar_espacios_deportivos_actividad()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'PoblarED/?CodAlumno='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $result = '';
      
      $tamano = count($json['Sedes']);
      
      for ($i=0; $i<$tamano; $i++) {

        $tamano_int = count($json['Sedes'][$i]['espacios']);

        for ($a=0; $a<$tamano_int; $a++) {

          $tamano_fin = count($json['Sedes'][$i]['espacios'][$a]['actividades']);
          $result .= '<select class="reservas-select selectpicker relative arrow form-control" name="CodActiv" id="actividad-'.$json['Sedes'][$i]['espacios'][$a]['codigo'].'">';
          //$result .= '<option>Selecciona una actividad</option>';   
            for ($b=0; $b<$tamano_fin; $b++) {
              $result .= '<option value="'.$json['Sedes'][$i]['espacios'][$a]['actividades'][$b]['codigo'].'">';
              $result .= $json['Sedes'][$i]['espacios'][$a]['actividades'][$b]['nombre'];
              $result .= '</option>';
            }
          $result .= '</select>';
            
        }

      }
      return $result;          
    }         
    
    //DISPONIBILIDAD DE ESPACIOS DEPORTIVOS   
    public function disponibilidad_espacios_deportivos()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      $codsede = ee()->TMPL->fetch_param('codsede');
      $coded = ee()->TMPL->fetch_param('coded');
      $codactiv = ee()->TMPL->fetch_param('codactiv');
      $numhoras = ee()->TMPL->fetch_param('numhoras');
      $fechaini = ee()->TMPL->fetch_param('fechaini');
      $fechaini = substr($fechaini, 6,4).substr($fechaini, 3,2).substr($fechaini, 0,2);
      $fechafin = $fechaini;
      $segmento = ee()->TMPL->fetch_param('segmento');
      $will_exec = ee()->TMPL->fetch_param('execute');
      $HoraIni = ee()->TMPL->fetch_param('HoraIni');
      $HoraIni = intval($HoraIni);
      $HoraFin = intval($HoraIni) + intval($numhoras);
      
      if (strpos($will_exec, '{post:') !== false)
      { 
        $site_url = ee()->config->item('site_url');
        $this->EE->functions->redirect($site_url."mis-reservas/reserva-espacios-deportivos");
        return;
      }
      else if ($will_exec[0] == '0')
      {
        if($HoraIni < 10)
        {
          $HoraIni = '0'.$HoraIni.'00';
        }
        else
        {
          $HoraIni = $HoraIni.'00';
        }

        if($HoraFin < 10)
        {
          $HoraFin = '0'.$HoraFin.'00';
        }
        else
        {
          $HoraFin = $HoraFin.'00';
        }
        
        $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
        $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
     
        $token = $_COOKIE[$this->_cookies_prefix.'Token'];
        $url = 'DisponibilidadED/?CodSede='.$codsede.'&CodED='.$coded.'&NumHoras='.$numhoras.'&CodAlumno='.$codigo.'&FechaIni='.$fechaini.'&FechaFin='.$fechafin.'&Token='.$token;
        //var_dump($url);

        $result=$this->services->curl_url($url);
        $json = json_decode($result, true);
        $error = $json['CodError'];
        $error_mensaje = $json['MsgError'];
        $result = '';
        $tamano = count($json['HorarioDia']);

        $result .= '<div class="row pt-0 pl-14">'; // apertura
        for ($i=0; $i<$tamano; $i++) {
                  
          $tamano_int = count($json['HorarioDia'][$i]['Disponibles']);
          
          // $result .='<div class="row">';
          for ($a=0; $a< count($json['HorarioDia'][$i]['Disponibles']); $a++) {
            $hora_inicio_disp = substr($json['HorarioDia'][$i]['Disponibles'][$a]["HoraFin"],0,2);
            $hora_inicio_sol = substr($HoraIni,0,2);
            if($hora_inicio_sol <= $hora_inicio_disp){
         

              $fecha = substr($json['HorarioDia'][$i]['Disponibles'][$a]['Fecha'], 6,2).'-'.substr($json['HorarioDia'][$i]['Disponibles'][$a]['Fecha'], 4,2).'-'.substr($json['HorarioDia'][$i]['Disponibles'][$a]['Fecha'], 0,4);
              $result .= '<div class="col-sm-5 mb-21 mr-21 p-14 text-left red-line bg-muted">';
              $result .= '<form action="{site_url}index.php/'.$segmento.'/resultados-reservas-deportivos" method="post" name="form-'.$a.'">';
              $result .= '<input type="hidden" name="XID" value="{XID_HASH}" />';
              $result .= '<input type="hidden" value="1" name="Flag">';
              $result .= '<input type="hidden" value="'.$codsede.'" name="CodSede">';
              $result .= '<input type="hidden" value="'.$coded.'" name="CodED">';
              $result .= '<input type="hidden" value="'.$codactiv.'" name="CodActiv">';
              $result .= '<input type="hidden" value="'.$numhoras.'" name="NumHoras">';
              $result .= '<input type="hidden" value="Ninguno" name="Detalles">';
              $result .= '<input type="hidden" value="'.$json['HorarioDia'][$i]['Disponibles'][$a]['Fecha'].'" name="Fecha">';
              if ($json['HorarioDia'][$i]['Disponibles'][$a]['Sede']=='L') {
              $result .= '<div class="solano-bold-24 black-text">Sede: Complejo Alamos</div>';
              } else {
              $result .= '<div class="solano-bold-24 black-text">Sede: Campus Villa</div>';
              }
              // $a++;
              // $result .= '<div class="solano-bold-24 black-text"> Opción '.$a.'</div>';
              // $a--;
              $result .= '<span class="zizou-16">';
              $result .= 'Fecha: '.$fecha.'<br>';
              $result .= '</span>';
              $HoraInicio = substr($json['HorarioDia'][$i]['Disponibles'][$a]['HoraInicio'], 0, 2);
              $HoraInicio = ltrim($HoraInicio,'0');
              $HoraFin = substr($json['HorarioDia'][$i]['Disponibles'][$a]['HoraFin'], 0, 2);
              $HoraFin = ltrim($HoraFin,'0');
              $result .= '<input type="hidden" value="'.$json['HorarioDia'][$i]['Disponibles'][$a]['HoraInicio'].'" name="HoraIni">';
              $result .= '<input type="hidden" value="'.$json['HorarioDia'][$i]['Disponibles'][$a]['HoraFin'].'" name="HoraFin">';
              $result .= '<span class="zizou-16">Hora: '.$HoraInicio.':00 - '.$HoraFin.':00</span>';
              $result .= '<input type="submit"  class="block mt-14 btn btn-custom black-btn wide" value="Reservar" name="submit">';
              $result .= '</form>';
              $result .= '</div>';

            }  
          }
           $result .= '</div>';              
        }        
        
        //Control de errores
        if ($error!='00000') {
          $result = '';
          // $result .= $error_mensaje;
          //
          $result .= '<div class="panel-table red-line red-error-message">';
          $result .= '<div class="panel-body p-28">';
          $result .= '<img class="pr-7" src="{site_url}assets/img/excla_red_1.png">';
          $result .= '<span class="helvetica-16 red">'.$error_mensaje.'</span>';
          $result .= '</div>';
          $result .= '</div>';
        }          
         
        return $result;      
      }    
    } 
    
    //RESERVA DE ESPACIOS DEPORTIVOS   
    public function reserva_espacios_deportivos()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];


      $codsede = ee()->TMPL->fetch_param('codsede');
      $coded = ee()->TMPL->fetch_param('coded');
      $codactiv = ee()->TMPL->fetch_param('codactiv');
      $numhoras = ee()->TMPL->fetch_param('numhoras');
      $fecha = ee()->TMPL->fetch_param('fecha');
      $fecha = substr($fecha, 0,4).substr($fecha, 4,2).substr($fecha, 6,2);
      $horaini = ee()->TMPL->fetch_param('horaini');
      $horafin = ee()->TMPL->fetch_param('horafin');
      $detalles = ee()->TMPL->fetch_param('detalles');
      $will_exec = ee()->TMPL->fetch_param('execute');

      if (strpos($will_exec, '{post:') !== false)
      { 
        $site_url = ee()->config->item('site_url');
        $this->EE->functions->redirect($site_url."mis-reservas/reserva-espacios-deportivos");
        return;
      }
      else if ($will_exec[0] == '1')
      {
        $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
        $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

        ee()->db->select('*');
        ee()->db->where('codigo',$codigo);
        $query_modelo_result = ee()->db->get('exp_user_upc_data');

        foreach($query_modelo_result->result() as $row){
          $token = $row->token;
        }
        
        $url = 'ReservarED/?CodSede='.$codsede.'&CodED='.$coded.'&CodActiv='.$codactiv.'&NumHoras='.$numhoras.'&CodAlumno='.$codigo.'&Fecha='.$fecha.'&HoraIni='.$horaini.'&HoraFin='.$horafin.'&Detalles='.$detalles.'&Token='.$token;
        $result=$this->services->curl_url($url);
  
        //var_dump($result);
        $json = json_decode($result, true);
        
        $error = $json['CodError'];
        $error_mensaje = $json['MsgError'];
        $estado  = $json['Estado'];
        $result = '';
        
        //Control de errores
        if ( $estado == 'R' ) {
          $result .= '<div class="resultados-busqueda info-border bg-muted blue-message">';
          $result .= '<div class="panel-body p-28">';
          $result .= '<img class="pr-7" src="{site_url}assets/img/check_xl.png">';
          $result .= '<span class="helvetica-16 text-info">'.$error_mensaje.$fechaini.$fechafin.'</span>';
          $result .= '<a href="http://intranet.upc.edu.pe/Loginintermedia/loginupc.aspx?wap=506">';
          $result .= '<div class="bg-muted p-7 mb-7 blue-message">';  
          $result .= '<div class="arrow-icon info"></div>';  
          $result .= '<div class="zizou-18 text-info">Ir a Cancelar Reserva</div>';        
          $result .= '</div>';
          $result .= '</a>';
          $result .= '</div>';
          $result .= '</div>';
        } else {
          $result .= '<div class="panel-table red-line red-error-message">';
          $result .= '<div class="panel-body p-28">';
          $result .= '<img class="pr-7" src="{site_url}assets/img/excla_red_1.png">';
          $result .= '<span class="helvetica-16 red">'.$error_mensaje.$fechaini.$fechafin.'</span>';
          $result .= '</div>';
          $result .= '</div>';
        }  
        return $result;  

      }
        
    } 
    
    
    //LISTA DE RECURSOS DISPONIBLES
    public function listado_recursos_disponibles()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      $tiporecurso = ee()->TMPL->fetch_param('TipoRecurso');
      $CodSede = ee()->TMPL->fetch_param('CodSede');
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){  
        $token = $row->token;
      }

      $canhoras= ee()->TMPL->fetch_param('CanHoras');
      $segmento= ee()->TMPL->fetch_param('segmento');
      $fecini = ee()->TMPL->fetch_param('FecIni');
      $fecini = substr($fecini, 0,2).substr($fecini, 3,2).substr($fecini, 6,4);
      $fechafin = $fecini;
      //$fechafin= ee()->TMPL->fetch_param('FechaFin');
      //$fechafin = substr($fechafin, 0,2).substr($fechafin, 3,2).substr($fechafin, 6,4);
      $HoraIni = ee()->TMPL->fetch_param('HoraIni');
      $HoraIni = intval($HoraIni);
      $HoraFin = intval($HoraIni) + intval($canhoras);
      //$HoraFin = ee()->TMPL->fetch_param('HoraFin');
      //var_dump($HoraIni);
      $HoraIni = intval($HoraIni);  
      
      if($HoraIni < 10){
        $HoraIni = '0'.$HoraIni.'00';
      }
      else{
        $HoraIni = $HoraIni.'00';
      }

      if($HoraFin < 10){
        $HoraFin = '0'.$HoraFin.'00';
      }
      else{
        $HoraFin = $HoraFin.'00';
      }
  
      $url = 'RecursosDisponible/?TipoRecurso='.$tiporecurso.'&Local='.$CodSede.'&FecIni='.$fecini.'%20'.$HoraIni.'&CanHoras='.$canhoras.'&FechaFin='.$fechafin.'%20'.$HoraFin.'&CodAlumno='.$codigo.'&Token='.$token;
      //var_dump($url);
      //$url = 'RecursosDisponible/?TipoRecurso='.$tiporecurso.'&Local=A&FecIni='.$fecini.'&CanHoras='.$canhoras.'&FechaFin='.$fechafin.'&CodAlumno='.$codigo.'&Token='.$token;
  
      // $ch = curl_init($url);
      // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
      // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      // curl_setopt($ch, CURLOPT_URL,$url);
      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];      
      $result = ''; 
      $tamano = count($json['Recursos']); 
      
      if($error=='00000'){
        // $result .= '<div class="panel-body red-line">';
        $result .= '<div class="row pt-0 pl-14">'; // apertura row
        for ($i=0; $i<count($json['Recursos']); $i++) {  //Se desplegarán 4 resultados
          //if($json['Recursos'][$i]['Estado'] == true){
          //if($HoraIni <= intval(substr($json['Recursos'][$i]['HoraIni'], 0,2)) ){
          if( $json['Recursos'][$i]['Estado'] == true) {
            $result .= '<div class="col-sm-5 mb-21 mr-21 p-14 text-left red-line bg-muted">';    
            if($tiporecurso == "CO"){
              $result .= '<form action="{site_url}index.php/'.$segmento.'/resultados-reserva-de-computadoras" method="post" name="formrecurso-'.$i.'">';
            }
            else if($tiporecurso == "CU"){
              $result .= '<form action="{site_url}index.php/'.$segmento.'/resultados-reserva-de-cubiculos" method="post" name="formrecurso-'.$i.'">';
            }
            //var_dump($json['Recursos'][$i]);
            $fecha = substr($json['Recursos'][$i]['FecReserva'], 0,2).'-'.substr($json['Recursos'][$i]['FecReserva'], 2,2).'-'.substr($json['Recursos'][$i]['FecReserva'], 4,4);
            $result .= '<input type="hidden" name="XID" value="{XID_HASH}" />'; 
            $result .= '<input type="hidden" name="CodRecurso" value="'.$json['Recursos'][$i]['CodRecurso'].'" />';
            $result .= '<input type="hidden" name="NomRecurso" value="'.$json['Recursos'][$i]['NomRecurso'].'" />';
            $result .= '<input type="hidden" name="CanHoras" value="'.$canhoras.'" />';
            $result .= '<input type="hidden" name="FecIni" value="'.$fecini.'" />';
            $result .= '<input type="hidden" name="FechaFin" value="'.$fechafin.'" />';
            $result .= '<input type="hidden" name="HoraIni" value="'.$HoraIni.'" />';
            $result .= '<input type="hidden" name="HoraFin" value="'.$HoraFin.'" />';
            $result .= '<input type="hidden" name="Flag" value="1" />';       
            // $i++; 
            // $result .= '<div class="solano-bold-24 black-text"> Opción '.$i.'</div>';
            // $i--;

            $result .= '<div class="solano-bold-24 black-text">'.$json['Recursos'][$i]['NomRecurso'].'</div>';
            $result .= '<div class="zizou-16">'.$json['Recursos'][$i]['Local'].'</div>';
            $result .= '<div class="zizou-16">'.$fecha.'</div>';
            $result .= '<div class="zizou-16">'.substr($json['Recursos'][$i]['HoraIni'], 0,2).':'.substr($json['Recursos'][$i]['HoraIni'], 2,2).' - '.substr($json['Recursos'][$i]['HoraFin'], 0,2).':'.substr($json['Recursos'][$i]['HoraFin'], 2,2).'</div>';
            $result .= '<input type="submit" class="mt-14 btn btn-custom black-btn wide" value="Reservar" name="submit">';
            $result .= '</form>'; 
            $result .= '</div>';
            // $result .= '</div>'; // cierre row
          }
        }
        $result .= "</div>"; //cierre panel-body
      }
      //Control de errores
      if ($error!='00000') {
        $result .= '<div class="panel-body red-line red-error-message">';
        $result .= '<div class="panel-table p-28">';
        $result .= '<img class="pr-7" src="{site_url}assets/img/excla_red_1.png">';
        $result .= '<span class="helvetica-16 red">'.$error_mensaje.'</span>';
        $result .= '</div>';
        $result .= '</div>';
      }
      return $result;
      //return "Respuesta de entorno de alta disponibilidad";          
    } 
    
    
    //RESERVA DE RECURSOS
    public function reserva_recursos()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $codrecurso = ee()->TMPL->fetch_param('CodRecurso');
      $nomrecurso = ee()->TMPL->fetch_param('NomRecurso');
      $fecini = ee()->TMPL->fetch_param('FecIni');
      //$fecini = substr($fecini, 0,2).substr($fecini, 3,2).substr($fecini, 6,4);
      $horaini = ee()->TMPL->fetch_param('HoraIni');
      //var_dump($horaini);
      $horaini = substr($horaini, 0,4);
      //$fechafin= ee()->TMPL->fetch_param('FechaFin');
      $fechafin = $fecini;
      //$fechafin = substr($fechafin, 0,2).substr($fechafin, 3,2).substr($fechafin, 6,4);ere
      $horafin = ee()->TMPL->fetch_param('HoraFin');
      //var_dump($horafin);
      $horafin = substr($horafin, 0,4);
      $canhoras= ee()->TMPL->fetch_param('CanHoras');
      
      if ($codrecurso == "{post:CodRecurso}" || $nomrecurso == "{post:NomRecurso}") {
        return "";
      }
      else { 
        $url = 'Reservar/?CodRecurso='.$codrecurso.'&NomRecurso='.$nomrecurso.'&CodAlumno='.$codigo.'&CanHoras='.$canhoras.'&fecIni='.$fecini.' '.$horaini.'&fecFin='.$fechafin.' '.$horafin.'&Token='.$token;
        $url = str_replace(" ", "%20", $url);
        $result=$this->services->curl_url($url);
        //var_dump($result);
        $json = json_decode($result, true);

        $error = $json['CodError'];
        $error_mensaje = $json['MsgError'];      
        $result = '';

        //mensaje de exito
        if (strpos($error_mensaje, 'realizado') !== false || strpos($error_mensaje, 'reservado') !== false) {
          $result .= '<div class="resultados-busqueda info-border blue-message bg-muted">';
          $result .= '<div class="panel-body p-28">';
          $result .= '<img class="pr-7" src="{site_url}assets/img/check_xl.png">';
          //$result .= '<span class="helvetica-16 text-info">'.$json['MsgError'].'</span>';
          $result .= '<span class="helvetica-16 text-info"> Estimado(a) alumno(a): Se ha reservado el recurso '.$nomrecurso.' para el dia '.substr($fechafin, 0,2).'/'.substr($fechafin, 2,2).'/'.substr($fechafin, 4,4).', a las '.substr($horaini, 0,2).':'.substr($horaini, 2,2).' horas.</span>';
          $result .= '<a href="http://intranet.upc.edu.pe/Loginintermedia/loginupc.aspx?wap=33" target="_blank">';
          $result .= '<div class="bg-muted p-7 mb-7 blue-message">';
          $result .= '<div class="row">';  
          $result .= '<div class="col-xs-10">';  
          $result .= '<span class="arrow-icon info"></span>';  
          $result .= '<div class="zizou-18 text-info">Ir a Cancelar Reserva</div>';                
          $result .= '</div>';
          $result .= '</div>';
          $result .= '</a>';
          $result .= '</div>';
          $result .= '</div>';
        } else {
          $result .= '<div class="panel-body red-line red-error-message>';
          $result .= '<div class="p-28 panel-table">';
          $result .= '<img class="pr-7" src="{site_url}assets/img/excla_red_1.png">';
          $result .= '<span class="helvetica-16 red">'.$json['MsgError'].'</span>';
          $result .= '</div>';
          $result .= "</div>";
        }

        //Control de errores
        // if ($error!='00002') {
        //   $result .= '<div class="red-line panel-table">';
        //   $result .= '<div class="panel-body p-28">';
        //   $result .= '<img class="pr-7" src="{site_url}assets/img/excla_red_1.png">';
        //   $result .= '<span class="helvetica-16 red">'.$error_mensaje.'</span>'; 
        //   $result .= '</div>';
        //   $result .= '</div>';
        // }
        return $result;      
      }    
    }     
    
    
    //LISTADO DE RECURSOS RESERVADOS POR EL ALUMNO     
    public function listado_recursos_reservados_alumno()
    {
      $fecha = ''; 
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $hoy = date('dmY');
      $unasemana = date('dmY',strtotime('+1 week'));
      
      $url = 'ReservaAlumno/?FecIni='.$hoy.'&FechaFin='.$unasemana.'&CodAlumno='.$codigo.'&Token='.$token;
      //var_dump($url);

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      
      $result = '';
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];      
       
      $tamano = count($json['Reservas']);
      $result .= '<div class="panel-body">';
      $result .= ' <div class="panel-body-head-table">';
      $result .= '  <ul class="tr">';
      $result .= '    <li class="col-xs-2">';
      $result .= '      <div class="fecha"><span>Fecha</span></div>';
      $result .= '    </li>';
      $result .= '    <li class="col-xs-2">';
      $result .= '      <div><span>Hora</span></div>';
      $result .= '    </li>';
      $result .= '    <li class="col-xs-3">';
      $result .= '      <div><span>Campus</span></div>';
      $result .= '    </li>';
      $result .= '    <li class="col-xs-5">';
      $result .= '      <div><span>Recurso</span></div>';
      $result .= '    </li>';
      $result .= '  </ul>';
      $result .= '</div>';      
      $result .= '<div class="panel-table">';
      
      $counterReservas = 0;
      if($json['Reservas'] == null){
        $result = '<div class="panel-body">' ;
        $result .= '<div class="panel-table">' ;
        $result .= 'Aqui va el mensaje' ;
        $result .= '</div>' ;
        $result .= '</div>' ;
      }

      else{ 

        for ($i=0; $i<$tamano; $i++) { 
          if($json['Reservas'][$i]['CodEstado']=='R'){
            $counterReservas = $counterReservas + 1;
            $result .= '<ul class="tr">';
            $result .= '<li class="col-xs-2 helvetica-12">';
            $result .= '<div class="text-center">';
            $fecha = substr($json['Reservas'][$i]['FecReserva'],0,2)."/".substr($json['Reservas'][$i]['FecReserva'],2,2);
            $result .= '<span>'.$fecha.'</span>';
            $result .= '</div>';
            $result .= '</li>';
            $result .= '<li class="col-xs-2 helvetica-12">';
            $result .= '<div class="text-center">';
            $HoraInicio = substr($json['Reservas'][$i]['HoraIni'], 0, 2);
            $HoraInicio = ltrim($HoraInicio,'0');
            $HoraFin = substr($json['Reservas'][$i]['HoraFin'], 0, 2);
            $HoraFin = ltrim($HoraFin,'0');       
            $result .= '<span>'.$HoraInicio.':00 - '.$HoraFin.':00</span>';
            $result .= '</div>';
            $result .= '</li>';
            $result .= '<li class="col-xs-3 helvetica-12">';
            $result .= '<div class="text-center">';
            $result .= '<span>'.substr($json['Reservas'][$i]['DesLocal'],7,strlen($json['Reservas'][$i]['DesLocal'])-1).'</span>';
            $result .= '</div>';
            $result .= '</li>';
            $result .= '<li class="col-xs-5 helvetica-12">';
            $result .= '<div class="text-center">';
            $result .= '<span>Código Reserva: '.$json['Reservas'][$i]['CodReserva'].'</span>';
            $result .= '<span>'.$json['Reservas'][$i]['NomRecurso'].'</span>';
            $result .= '</div>';
            $result .= '</li>';
            $result .= '</ul>';
          }
        }
      }
      $result .= '<a class="sb-link" href="http://intranet.upc.edu.pe/Loginintermedia/loginupc.aspx?wap=33" target="_blank">';  
      $result .= '<div class="zizou-18 pl-14 pb-14">Ir a Cancelar Reserva</div></a>';        
      $result .= '</div>';  
      $result .= '</div>'; 
      //Control de errores
      if ($error!='00000' || $counterReservas == 0) {
        $result = '<div class="panel-body">';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-4">';
        $result .= '<img class="img-center" src="{site_url}assets/img/no_bookings_new.png">';
        $result .= '</li>';
        if ($error_mensaje == "No se han registrado reservas durante esta semana." || $counterReservas == 0) {
          if ($_COOKIE[$this->_cookies_prefix."TipoUser"] =='ALUMNO') {
            $result .= '<li class="col-sm-8 pt-21 pr-21 pl-21"><p class="helvetica-14">Reserva de <a href="{site_url}mis-reservas/reserva-de-cubiculos" class="danger-link">cubículos, </a><a href="{site_url}mis-reservas/reserva-de-computadoras" class="danger-link">computadoras</a> o <a href="{site_url}mis-reservas/reserva-espacios-deportivos" class="danger-link">espacios deportivos</a></p></li>';         
          } 
          if ($_COOKIE[$this->_cookies_prefix."TipoUser"] =='PROFESOR') {
            $result .= '<li class="col-sm-8 pt-21 pr-21 pl-21"><p class="helvetica-14">Reserva de <a href="http://intranet.upc.edu.pe/Loginintermedia/loginupc.aspx?wap=32" target="_blank" class="danger-link">cubículos, computadoras </a> o <a href="http://intranet.upc.edu.pe/Loginintermedia/loginupc.aspx?wap=505" target="_blank" class="danger-link">espacios deportivos</a></p></li>';
          } 
        } else {
        $result .= '<li class="col-sm-8 pt-28 pr-21 pl-21"><p class="helvetica-14">'.$error_mensaje.'</p></li>'; 
        }
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '</div>'; 
      }

         
      return $result;          
    } 
    
    
    //LISTADO DE HIJOS DEL PADRE DE FAMILIA  
    public function lista_hijos_padre()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];

      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }
      
      $url = 'ListadoHijos/?Codigo='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      
      return $result;          
    }  
    
    //LISTADOS DE CURSOS DICTADOS POR EL PROFESOR 
    public function lista_cursos_dictados_profesor()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'ListadoCursosProfesor/?Codigo='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];       

      //limpio la variable para reutilizarla
      $result = '';
      $result .= '<div class="panel-body-head-table">';
      $result .= '<ul class="tr">';
      $result .= '<li class="col-xs-6">';
      $result .= '<div><span>Curso</span></div>';
      $result .= '</li>';
      $result .= '<li class="col-xs-3">';
      $result .= '<div class=""><span>Sección</span></div>';
      $result .= '</li>';
      $result .= '<li class="col-xs-3">';
      $result .= '<div class=""><span>Grupo</span></div>';
      $result .= '</li>';
      $result .= '</ul>';
      $result .= '</div>';  
      $result .= '<div class="panel-table">'; 

      //genera el tamano del array
      $tamano = count($json['modalidades']);

      for ($i=0; $i<$tamano; $i++) {
        // $result .= '<h4>'.$json['modalidades'][$i]['descripcion'].'</h4>';
         
        //genera el tamano del array
        $tamano_int = count($json['modalidades'][$i]['cursos']);        
        
        for ($a=0; $a<$tamano_int; $a++) {
          $result .= '<ul class="tr">';
          $result .= '<li class="col-xs-6 helvetica-14">';
          $result .= '<div>';
          $result .= '<span>'.$json['modalidades'][$i]['cursos'][$a]['curso'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-3 helvetica-14">';
          $result .= '<div class="text-center">';
          $result .= '<span>'.$json['modalidades'][$i]['cursos'][$a]['seccion'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-3 helvetica-14">';
          $result .= '<div class="text-center">';
          $result .= '<span>'.$json['modalidades'][$i]['cursos'][$a]['grupo'].'</span>';
          $result .= '</div>';
          $result .= '</li>';         
          $result .= '</ul>';
        }
        
      } 
        $result .= '</div>';
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-12 helvetica-bold-14"><div class="text-center"><span>'.$error_mensaje.'</span></div></li>'; 
        $result .= '</ul>';
        $result .= '</div>';
      }      

      return $result;          
    }
    
     //LISTADOS DE CURSOS DICTADOS POR EL PROFESOR LINK ALUMNOS 
    public function lista_cursos_dictados_link_alumnos_profesor()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'ListadoCursosProfesor/?Codigo='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];       

      //limpio la variable para reutilizarla
      $result = '';

      //genera el tamano del array
      $tamano = count($json['modalidades']);

      for ($i=0; $i<$tamano; $i++) {
        // $result .= '<h4>'.$json['modalidades'][$i]['descripcion'].'</h4>';
        $result .= '<div class="panel-table otras-acciones">';              
        
        //genera el tamano del array
        $tamano_int = count($json['modalidades'][$i]['cursos']);    
        // $result .= '<span>Tamaño Int '.$tamano_int.' Tamaño '.$tamano;    
        // var_dump($json);
        for ($a=0; $a<$tamano_int; $a++) {
          $result .= '<ul class="tr">';
          $result .= '<li class="col-xs-12 bg-muted mb-5">';
          $result .= '<div>';
          $result .= '<form action="{site_url}index.php/mi-docencia/cursos-detallados" method="post" id="form-'.$i.'">';
          $result .= '<input type="hidden" name="XID" value="{XID_HASH}" />';
          $result .= '<input type="hidden" name="Flag" value="buscar-curso">';
          $result .= '<input type="hidden" name="Modalidad" value="'.$json['modalidades'][$i]['codigo'].'">';
          $result .= '<input type="hidden" name="Periodo" value="'.$json['modalidades'][$i]['periodo'].'">';
          $result .= '<input type="hidden" name="Curso" value="'.$json['modalidades'][$i]['cursos'][$a]['cursoId'].'">';
          $result .= '<input type="hidden" name="Seccion" value="'.$json['modalidades'][$i]['cursos'][$a]['seccion'].'">';
          $result .= '<input type="hidden" name="Grupo" value="'.$json['modalidades'][$i]['cursos'][$a]['grupo'].'">';
          $result .= '<div>';
          $result .= '<img class="pr-7" src="{site_url}assets/img/black_arrow_tiny.png">';
          $result .= '<input type="submit" class="zizou-14 btn-anchor curso-detallado-submit" value="'.$json['modalidades'][$i]['cursos'][$a]['curso'].' ('.$json['modalidades'][$i]['cursos'][$a]['seccion'].')" name="submit">';
          $result .= '</div>';
          $result .= '</form>';
          $result .= '</div>';
          $result .= '</li>';         
          $result .= '</ul>';
        }
        
        $result .= '</div>';
      } 
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-12 helvetica-bold-14"><div class="text-center"><span>'.$error_mensaje.'</span></div></li>'; 
        $result .= '</ul>';
        $result .= '</div>';
      }      

      return $result;          
    }      
    
    //LISTADOS DE ALUMNOS MATRICULADOS EN UN CURSO DICTADO POR EL PROFESOR
    public function lista_alumnos_matriculados_en_curso_por_profesor()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $modalidad = ee()->TMPL->fetch_param('modalidad');
      $periodo = ee()->TMPL->fetch_param('periodo');
      $curso = ee()->TMPL->fetch_param('curso');
      $seccion = ee()->TMPL->fetch_param('seccion');
      $grupo = ee()->TMPL->fetch_param('grupo');
      
      $url = 'ListadoAlumnosProfesor/?Codigo='.$codigo.'&Token='.$token.'&Modalidad='.$modalidad.'&Periodo='.$periodo.'&Curso='.$curso.'&Seccion='.$seccion.'&Grupo='.$grupo;

      $result=$this->services->curl_url($url);
      
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];
      
      $result = ''; 
      
      //genera el tamano del array
      $tamano = count($json['Cursos']);  
      // var_dump($json);
      for ($i=0; $i<$tamano; $i++) {
        $nombre_curso_lc = $json['Cursos'][$i]['curso'];
        // $nombre_curso_lc = ucwords(strtolower($nombre_curso));
        // return $nombre_curso_lc;
        $result .= '<div class="panel-head no-bg">';
        $result .= '<div class="panel-title left">';
        $result .= '<h3 class="black-text"><span class="gray-main"> Lista de Alumnos: </span>'.$nombre_curso_lc.'</h3>';
        // $result .= '<h3>Lista de Alumnos: '.$json['Cursos'][$i]['curso'].'</h3>';
        $result .= '</div>';
        $result .= '</div>';
        
        $result .= '<div class="panel-body red-line">';
        // $result .= '<div class="panel-body-head-table white">';
        // $result .= '<ul class="tr">';
        // $result .= '<li class="col-xs-2">';
        // $result .= '<div>';
        // $result .= '<span class="helv-neue-light-14">Foto</span>';
        // $result .= '</div>';
        // $result .= '</li>';
        // $result .= '<li class="col-xs-6">';
        // $result .= '<div>';
        // $result .= '<span class="helv-neue-light-14">Nombre</span>';
        // $result .= '</div>';
        // $result .= '</li>';
        // $result .= '<li class="col-xs-2">';
        // $result .= '<div>';
        // $result .= '<span class="helv-neue-light-14">Notas</span>';
        // $result .= '</div>';
        // $result .= '</li>';       
        // $result .= '<li class="col-xs-2">';
        // $result .= '<div>';
        // $result .= '<span class="helv-neue-light-14">Inasistencias</span>';
        // $result .= '</div>';
        // $result .= '</li>';       
        // $result .= '</ul>';
        // $result .= '</div>';
      
        //genera el tamano del array
        $tamano_int = count($json['Cursos'][$i]['alumnos']);        
        
        for ($a=0; $a<$tamano_int; $a++) {
          $result .= '<div class="panel-table">';
          $result .= '<ul class="tr">';
          $result .= '<li class="col-xs-3 col-sm-2 helvetica-14">';
          $result .= '<div class="text-center borderless">';
          $result .= '<img src="'.$json['Cursos'][$i]['alumnos'][$a]['url_foto'].'" width="55" class="img-center pt-7 pb-7">';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-xs-9 col-sm-10 helvetica-14">';
          $result .= '<span class="helvetica-bold-14">';
          $result .= $json['Cursos'][$i]['alumnos'][$a]['nombre_completo'];       
          $result .= '</span>';
          $result .= '<p class="m-0"><span class="helv-neue-14">CÓDIGO DE ALUMNO:&nbsp;</span>';
          $result .= '<span class="helvetica-bold-14">';
          $result .= $json['Cursos'][$i]['alumnos'][$a]['codigo'];       
          $result .= '</span></p>';                 
          $result .= '<p class="m-0">';
          
          $url = 'InasistenciaProfesor/?Codigo='.$codigo.'&CodAlumno='.$json['Cursos'][$i]['alumnos'][$a]['codigo'].'&Token='.$token;

          $result_int=$this->services->curl_url($url);
          
          $json_b = json_decode($result_int, true);
          
          $result_int = '';
          
          //genera el tamano del array
          $tamano_inas = count($json_b['Inasistencias']);           
          
          $result .= '<span class="helv-neue-14">FALTAS:&nbsp;</span>';
          for ($b=0; $b<$tamano_inas; $b++) {
            if ($json['Cursos'][$i]['cursoId'] == $json_b['Inasistencias'][$b]['CodCurso']) {
              $result .= '<span class="helvetica-bold-14">';
              $result .= $json_b['Inasistencias'][$b]['Total'].'/'.$json_b['Inasistencias'][$b]['Maximo'];
              $result .= '</span>';
            } 
          } 
          
          $result .= '</p>';
          $result .= '<span class="helvetica-bold-14">';          
          $result .= '<form method="post" action="{site_url}index.php/mi-docencia/cursos-detallados" id="form-alumno'.$a.'">';
          $result .= '<input type="hidden" name="XID" value="{XID_HASH}" />';
          $result .= '<input type="hidden" name="codalumno" value="'.$json['Cursos'][$i]['alumnos'][$a]['codigo'].'">';
          $result .= '<input type="hidden" name="cursoid" value="'.$json['Cursos'][$i]['cursoId'].'">';
          $result .= '<input type="hidden" name="nombrealumno" value="'.$json['Cursos'][$i]['alumnos'][$a]['nombre_completo'].'">';
          $result .= '<input type="hidden" name="Flag" value="notas">';
          $result .= '<input type="submit" class="btn-anchor p-0 zizou-14 red" name="submit" value=">>ver notas">';
          $result .= '</form>'; 
          $result .= '</span>';                 
          $result .= '</li>';   
          $result .= '</ul>';   
          $result .= '</div>';

        }
        
        
        
        $result .= '</div>'; 
        //temp
        $result .= '<a class="black-text curso-link text-right" href="{site_url}mi-docencia/cursos-detallados">';
        $result .= '<div class="zizou-14 pt-14 mb-35">';
        $result .= 'Regresar a lista de cursos';
        $result .= '<img class="ml-7" src="{site_url}assets/img/black_arrow_tiny_up.png" alt="">';
        $result .= '</div>';
        $result .= '</a>';  
        //temp
      }    

      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-12 helvetica-bold-14"><div class="text-center"><span>'.$error_mensaje.'</span></div></li>'; 
        $result .= '</ul>';
        $result .= '</div>';
      }      

      return $result;  
         
    } 
    
    //HORARIOS DE UN PROFESOR 
    public function horario_profesor()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'HorarioProfesor/?Codigo='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      //var_dump($result);
      $json = json_decode($result, true);

      $error = $json['CodError'];
      $error_mensaje = $json['MsgError']; 
      
      //limpio la variable para reutilizarla
      $result = '<div class="panel-body-head-table">';
      $result .= '<ul class="tr">';
      $result .= '<li class="col-xs-2">';
      $result .= '<div class="fecha"><span>Hora</span></div>';
      $result .= '</li>';
      $result .= '<li class="col-xs-2">';
      $result .= '<div class=""><span>Campus</span></div>';
      $result .= '</li>';
      $result .= '<li class="col-xs-6">';
      $result .= '<div class=""><span>Curso</span></div>';
      $result .= '</li>';
      $result .= '<li class="col-xs-2">';
      $result .= '<div class=""><span>Salón</span></div>';
      $result .= '</li>';
      $result .= '</ul>';
      $result .= '</div>';      
      
      //genera el tamano del array
      $tamano = count($json['HorarioDia']);
      $flag = TRUE;
      
      //Loop basado en el HorarioDia
      for ($i=0; $i<$tamano; $i++) {
        $result.= '<div class="panel-table">';
        $tamano_1 = count($json['HorarioDia'][$i]['Clases']);
        $dia_actual = date('w');
         if ($json['HorarioDia'][$i]['CodDia']==date('w')) {
          for ($b=0; $b<$tamano_1; $b++) {
            $HoraInicio[$b] = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraInicio'], 0, 2);
            $HoraInicio[$b] = ltrim($HoraInicio[$b],'0');
            $Sede[$b] = $json['HorarioDia'][$i]['Clases'][$b]['Sede'];
            $CursoNombre[$b] = $json['HorarioDia'][$i]['Clases'][$b]['CursoNombre'];
            $Salon[$b] = $json['HorarioDia'][$i]['Clases'][$b]['Salon'];
          }
          
          $tamano_2 = count($HoraInicio);
          $disponibles = 0;
          for ($b=0; $b<=$tamano_1 -1 ; $b++) {
              $flag = TRUE;
              $result .= '<ul class="tr">';
              $result .= '<li class="col-xs-2 helvetica-bold-14">';
              $result .= '<div class="text-center"><span>'.$HoraInicio[$b].':00</span></div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2 helvetica-bold-14">';
              $result .= '<div class="text-center"><span>'.$Sede[$b].'</span></div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-6 helvetica-12">';
              $result .= '<div><span>'.$CursoNombre[$b].'</span></div>';
              $result .= '</li>';
              $result .= '<li class="col-xs-2 helvetica-bold-14">';
              $result .= '<div class="text-center"><span>'.$Salon[$b].'</span></div>';
              $result .= '</li>';
              $result .= '</ul>';    
              $disponibles++;
          } 
        } 
        $result .= '</div>'; 
      }
      
      if($flag){
        $result = '<div class="panel-body">';
        $result .= '<div class="panel-table pb-7">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-4 p-7">';
        $result .= '<img class="img-center" src="{site_url}assets/img/brain.png">';
        $result .= '</li>';
        $result .= '<li class="col-xs-8 pt-28 pr-21">';
        $result .= '<p class="zizou-bold-16 m-0">Tiempo de Innovar</p>';                
        $result .= '<p class="helvetica-14">No tienes ningún curso el día de hoy</p>';                
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '</div>';

      }
      //Control de errores
      if ($error!='00000') {
        $result = '<div class="panel-body">';
        $result .= '<div class="panel-table pb-7">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-4 p-7">';
        $result .= '<img class="img-center" src="{site_url}assets/img/brain.png">';
        $result .= '</li>';
        $result .= '<li class="col-xs-8 pt-28 pr-21">';
        $result .= '<p class="helvetica-14">'.$error_mensaje.'</p>';                
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '</div>'; 
      } 

      
      return $result;              
    }    


    //HORARIO PROFESOR CICLO ACTUAL
    public function horario_profesor_ciclo_actual()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $url = 'HorarioProfesor/?Codigo='.$codigo.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];      
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['HorarioDia']);  
      
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<div>';
        $result .= '<span class="zizou-16">';
        if ($json['HorarioDia'][$i]['CodDia'] == 1) {
          $result .= 'Lunes';
        }
        if ($json['HorarioDia'][$i]['CodDia'] == 2) {
          $result .= 'Martes';
        }       
        if ($json['HorarioDia'][$i]['CodDia'] == 3) {
          $result .= 'Miércoles';
        }    
        if ($json['HorarioDia'][$i]['CodDia'] == 4) {
          $result .= 'Jueves';
        }  
        if ($json['HorarioDia'][$i]['CodDia'] == 5) {
          $result .= 'Viernes';
        }                    
        if ($json['HorarioDia'][$i]['CodDia'] == 6) {
          $result .= 'Sábado';
        }           
        $result .= '</span>';
        $result .= '</div>'; 
        $result .= '<div class="panel-body red-line mb-7">';
        $result .= '<div class="panel-body-head-table white">'; 
        $result .= '<ul class="tr">'; 
        $result .= '<li class="col-xs-1-5">'; 
        $result .= '<div class=""><span>Inicio</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-xs-1-5">'; 
        $result .= '<div class=""><span>Fin</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-xs-5">'; 
        $result .= '<div class=""><span>Clase</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-xs-1">'; 
        $result .= '<div class=""><span>Sede</span></div>'; 
        $result .= '</li>';  
        $result .= '<li class="col-xs-1-5">'; 
        $result .= '<div class=""><span>Sección</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-xs-1-5">'; 
        $result .= '<div class=""><span>Salón</span></div>'; 
        $result .= '</li>';                                                                                                                                                  
        $result .= '</ul>'; 
        $result .= '</div>';        
        
        //genera el tamano del array
        $tamano_int = count($json['HorarioDia'][$i]['Clases']); 
        
        for ($b=0; $b<$tamano_int; $b++) {
            $result .= '<div class="panel-table">'; 
            $result .= '<ul class="tr mis-cursos-row">'; 
            $result .= '<li class="col-xs-1-5">'; 
            $result .= '<div class="text-center">';
            $result .= '<span>';
            $HoraInicio = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraInicio'], 0, 2);
            $HoraInicio = ltrim($HoraInicio,'0');
            $result .= $HoraInicio.':00';
            $result .='</span>'; 
            $result .= '</div>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1-5">';
            $result .= '<div class="text-center">'; 
            $result .= '<span>';
            $HoraFin = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraFin'], 0, 2);
            $HoraFin = ltrim($HoraFin,'0');
            $result .= $HoraFin.':00';                  
            $result .='</span>';                  
            $result .= '</div>'; 
            $result .= '</li>';                        
            $result .= '<li class="col-xs-5">'; 
            $result .= '<div class="text-center">'; 
            $result .= '<span>';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['CursoNombre'];
            $result .= '</span>';                   
            $result .= '</div>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1">'; 
            $result .= '<div class="text-center">'; 
            $result .= '<span>';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Sede'];
            $result .= '</span>'; 
            $result .= '</div>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1-5">'; 
            $result .= '<div class="text-center">'; 
            $result .= '<span>';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Seccion'];
            $result .= '</span>'; 
            $result .= '</div>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1-5">'; 
            $result .= '<div class="text-center">'; 
            $result .= '<span>';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Salon'];
            $result .= '</span>';  
            $result .= '</div>'; 
            $result .= '</li>'; 
            $result .= '</ul>'; 
            $result .= '</div>';                              
        }               
        $result .= '</div>';
      }

      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div>';
        $result .= '<div class="panel-body mb-35">';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr mis-cursos-row">';
        $result .= '<li class="col-xs-12">';
        $result .= '<span>'.$error_mensaje.'</span>';
        $result .= '</li>';                
        $result .= '</ul>';  
        $result .= '</div>';
        $result .= '</div>'; 
        $result .= '</div>';     
      }         
      
      return $result;               
    } 
       
    //LISTA ALUMNOS MATRICULADOS EN UN CURSO DICTADO POR EL PROFESOR
    public function lista_alumnos_matriculado_curso_profesor()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $codcurso = ee()->TMPL->fetch_param('codcurso');
      $codalumno = ee()->TMPL->fetch_param('codalumno');      
      
      $url = 'ListadoAlumnosProfesor/?Codigo='.$codigo.'&Token='.$token.'&Modalidad=FC&Periodo=201400&Curso=IS157&Seccion=CB2B&Grupo=00';

      $result=$this->services->curl_url($url);
      
      return $result;          
    }      


    //CONSULTA DE NOTAS DE UN ALUMNO POR UN PROFESOR
    public function consulta_notas_alumno_por_un_profesor()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];

      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $codalumno = ee()->TMPL->fetch_param('codalumno');
      $codcurso = ee()->TMPL->fetch_param('cursoid');
      $nombrealumno = ee()->TMPL->fetch_param('nombrealumno');     
      $url = 'NotaProfesor/?Codigo='.$codigo.'&CodAlumno='.$codalumno.'&CodCurso='.$codcurso.'&Token='.$token;

      $result=$this->services->curl_url($url);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Notas']);
      
      

        $result .= '<div class="panel curso-detalle">';
        $result .= '<div class="panel-body" >';
        $result .= '<div class="panel-body-head left">';
        $result .= '<ul class="tr">';
        $result .= '<span>'.$json['CursoNombre'].': '.$nombrealumno.'</span>';
        $result .= '</ul>';
        $result .= '</div>';
       
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-12">';
        $result .= '<div>';
        $result .= '<span class="helvetica-14 ml-7">FÓRMULA: '.$json['Formula'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';    
        $result .= '<div class="panel-body-head-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-xs-1">';
        $result .= '<div><span>Tipo</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div><span>Número</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-5">';
        $result .= '<div><span>Evaluación</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div><span>Peso</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div><span>Nota</span></div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>'; 
        $result .= '<div class="panel-table">'; 
        $result .= '<ul class="tr">';          

        for ($i=0; $i<$tamano; $i++) {
            
        $result .= '<li class="col-xs-1">';
        $result .= '<div class="text-center">';
        $result .= '<span class="helvetica-14">'.$json['Notas'][$i]['NombreCorto'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="text-center">';
        $result .= '<span class="helvetica-14">'.$json['Notas'][$i]['NroEvaluacion'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-5">';
        $result .= '<div>';
        $result .= '<span class="helvetica-14">'.$json['Notas'][$i]['NombreEvaluacion'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="text-center">';
        $result .= '<span class="helvetica-14">'.$json['Notas'][$i]['Peso'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="text-center borderless">';
        $result .= '<span class="helvetica-16">'.$json['Notas'][$i]['Valor'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
          
        } 
          
        //Cambia el formato a 2 decimales
        //$nota = number_format($nota, 2, '.', '');
          
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '<div class="panel-table observaciones">';
        $result .= ' <ul class="tr">';
        $result .= '<li class="col-xs-8">';
        $result .= '<div class="borderless pl-14">';
        $result .= '<span class="helvetica-bold-16 text-muted">OBSERVACIONES:</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="text-center borderless">';
        $result .= '<span class="helv-neue-14 uppercase text-muted">Nota al '.$json['PorcentajeAvance'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-2">';
        $result .= '<div class="text-center borderless">';
        $result .= '<span class="helvetica-bold-16 text-muted">'.$json['NotaFinal'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '<a class="black curso-link text-right" href="{site_url}mi-docencia/cursos-detallados">';
        $result .= '<div class="zizou-14 pt-14 mb-35">';
        $result .= 'Regresar a lista de cursos&nbsp;';
        $result .= '<img src="{site_url}assets/img/black_arrow_tiny_up.png" alt="">';
        $result .= '</div>';
        $result .= '</a>';        

          
      
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div>';
        $result .= '<div class="panel-body mb-35">';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr mis-cursos-row">';
        $result .= '<li class="col-xs-12">';
        $result .= '<span>'.$error_mensaje.'</span>';
        $result .= '</li>';                
        $result .= '</ul>';  
        $result .= '</div>';
        $result .= '</div>'; 
        $result .= '</div>';      
      }         
      
      return $result;        
    } 
    
    
          
    
    //PROMEDIO DE NOTAS DE UN ALUMNO POR CURSO
    public function promedio_notas_alumno_por_curso()
    {
      //$codigo = $_SESSION["Codigo"];
      //$token = $_SESSION["Token"];
       
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
      }

      $codcurso = ee()->TMPL->fetch_param('codcurso');
      
      $url = 'Nota/?CodAlumno='.$codigo.'&CodCurso='.strval ($codcurso).'&Token='.$token;

      $result=$this->services->curl_url($url);
      
      $json = json_decode($result, true);
      
      $nota0 = $json['Notas'][0]['Valor'];
      $nota1 = $json['Notas'][1]['Valor'];
      $nota2 = $json['Notas'][2]['Valor'];
      $nota3 = $json['Notas'][3]['Valor'];
      $nota4 = $json['Notas'][4]['Valor'];
      $nota5 = $json['Notas'][5]['Valor'];
      $error = $json['CodError'];
      $formula = $json['Formula'];
      $cursonombre = $json['CursoNombre'];
      
      if ($error == '0021') {
        $nota = 'N/A';
      } else {
        $nota = ($nota0*0.25)+($nota1*0.1)+($nota2*0.15)+($nota3*0.1)+($nota4*0.1)+($nota4*0.30);
      } 
      
      return $nota;            
    }      
         
    
    //NOMBRE DEL USUARIO
    public function nombre_alumno()
    {
      // return $_SESSION["Nombres"];
       
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $nombres = $row->nombres;
      }
      $names = $nombres;
      $names = ucwords(strtolower($names));
      return $names;
    }    
    
    //APELLIDO DEL ALUMNO
    public function apellido_alumno()
    {

      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $apellidos = $row->apellidos;
      }

      $apellidos = ucwords(strtolower($apellidos));
      return $apellidos;
    }  
    
    //CODIGO DEL ALUMNO
    public function codigo_alumno()
    {    
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      
      $codigo_alumno = ucwords(strtolower($_COOKIE[$this->_cookies_prefix."Codigo"]));
      
      return $codigo_alumno;
    }  
    
    //MODALIDAD DEL ALUMNO
    public function modalidad_alumno()
    {    
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];

      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $dscmodal = $row->dscmodal;
      }

			$dscmodal = ucwords(strtolower($dscmodal));
      return $dscmodal;
    }      
    
    //ESTADO DEL ALUMNO
    public function estado_alumno()
    { 
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $estado = $row->estado;
      }

      if ($estado=='A') {
        $estado_result = 'Activo';
      } else {
        $estado_result = 'Inactivo'; 
      }  
      
      $estado = ucwords(strtolower($estado));
      return $estado;
    }               
    
    //SEDE DEL ALUMNO
    public function sede_alumno()
    {       
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $dscsede = $row->dscsede;
      }
			
			$dscsede = ucwords(strtolower($dscsede));
      return $dscsede;
    }
    
    //CICLO DEL ALUMNO
    public function ciclo_alumno()
    { 
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $ciclo = $row->ciclo;
      }

      $yyyy = substr($ciclo,0,4); 
      $dd = substr($ciclo,4,6); 
      return $dd.'-'.$yyyy;     
    }
        
    //MUESTRA EL TIPO DE USUARIO
    public function tipo_usuario()
    {
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $tipouser = $row->tipouser;
      }
      $tipouser = ucwords(strtolower($tipouser));
      return $tipouser;
    }          
    
    //CALENDARIO DE CUOTAS TIPO DE USUARIOS
    public function calendario_cuotas_tipo_usuario()
    {
      //$codigo = $_SESSION["Codigo"];
      //$TipoUser = $_SESSION["TipoUser"];
      
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");

      $tipouser = $_SESSION['TipoUser'];
      $modalidad = $_SESSION['CodModal'];
      $result = '';
      
      if (strval($tipouser)=='ALUMNO') {
        $result .= '{exp:channel:entries channel="calendario_pagos" limit="10" disable="member_data|pagination" category_group="8" category="20" dynamic="off" orderby="numero-cuota" sort="asc" search:modalidad="'.$modalidad.'" }';
        $result .= '<ul class="tr bg-muted">';
        $result .= '<li class="col-xs-4 text-center helvetica-bold-14">';
        $result .= '<div>';
        $result .= '<span>{numero-cuota}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-4 helvetica-bold-14">';
        $result .= '<div class="text-center">';
        $result .= '<span>{emitida-cuota format="%d/%m/%y"}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-4 helvetica-bold-14">';
        $result .= '<div class="text-center">';
        $result .= '<span>{vence-cuota format="%d/%m/%y"}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '{/exp:channel:entries}';  
        return $result;  
      }
      
      if (strval($tipouser)=='PROFESOR') {
        $result .= '{exp:channel:entries channel="calendario_pagos" limit="10" disable="member_data|pagination" category_group="8" category="19" dynamic="off" orderby="numero-cuota" sort="asc" }';
        $result .= '<ul class="tr bg-muted">';
        $result .= '<li class="col-xs-4 text-center helvetica-bold-14">';
        $result .= '<div>';
        $result .= '<span>{numero-cuota}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-4 helvetica-bold-14">';
        $result .= '<div class="text-center">';
        $result .= '<span>{emitida-cuota format="%d/%m/%Y"}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-4 helvetica-bold-14">';
        $result .= '<div class="text-center">';
        $result .= '<span>{vence-cuota format="%d/%m/%Y"}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '{/exp:channel:entries}'; 
        return $result;             
      }
      
      if (strval($tipouser)=='PADRE') {
        $result .= '{exp:channel:entries channel="calendario_pagos" limit="10" disable="member_data|pagination" category_group="8" category="20" dynamic="off" orderby="numero-cuota" sort="asc" search:modalidad="AC"}';
        $result .= '<ul class="tr bg-muted">';
        $result .= '<li class="col-xs-4 text-center helvetica-bold-14">';
        $result .= '<div>';
        $result .= '<span>{numero-cuota}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-4 helvetica-bold-14">';
        $result .= '<div class="text-center">';
        $result .= '<span>{emitida-cuota format="%d/%m/%Y"}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-xs-4 helvetica-bold-14">';
        $result .= '<div class="text-center">';
        $result .= '<span>{vence-cuota format="%d/%m/%Y"}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '{/exp:channel:entries}'; 
        return $result;             
      }  
                    
    }      
    
    //MENSAJE DE ERROR
    public function mensaje_error()
    {
      $MsgError = $_COOKIE[$this->_cookies_prefix."MsgError"];
      $this->services->set_cookie("MsgError", $MsgError, time() + (1800), "/");
      return $MsgError;
    }    
    
    //INICIAR SESION
    public function iniciar_session() {
      session_name('upc');
      session_start();
    }
    
    //INICIAR SESION
    public function verificar_usuario() {
      //$token = $_SESSION["Token"];
      //var_dump($_SESSION);
      $segment_2 = ee()->TMPL->fetch_param('tipo_de_vista');
 
      $codigo =  $_SESSION["Codigo"];


      $_COOKIE[$this->_cookies_prefix."Codigo"] = $codigo;
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      ee()->db->select('*');
      ee()->db->where('codigo', $codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $token = $row->token;
        $tipouser = $row->tipouser;
      }

      if ($codigo == '') {

        $redireccion = uri_string();
        $_COOKIE[$this->_cookies_prefix."Redireccion"] = $redireccion;
        $this->services->set_cookie("Redireccion",$redireccion, time() + (1800), "/");
        $site_url = ee()->config->item('site_url');
        $site_url .= 'login/no-es-usuario';
        redirect($site_url);
      }
      elseif ($segment_2 != $tipouser ) {
         $_COOKIE[$this->_cookies_prefix."Redireccion"]= "/general/permisos";
        if ($tipouser == 'PROFESOR'){
          redirect( $_SESSION["Redireccion"]);
        }
        if ($tipouser == 'ALUMNO'){
          redirect( $_COOKIE[$this->_cookies_prefix."Redireccion"]);
        }
        if ($tipouser == 'PADRE'){
          $url = 'ListadoHijos/?Codigo='.$codigo.'&Token='.$token.'';

          $hijosWebService=$this->services->curl_url($url);
          $json = json_decode($hijosWebService, true);
          
          redirect('/dashboard/padre/hijos/'.$json["hijos"][0]["codigo"]);
        }
      }

    }    
    
    //BOTON INICIO
    public function boton_inicio() {
      $codigo =  $_COOKIE[$this->_cookies_prefix."Codigo"];
      $this->services->set_cookie("Codigo",$codigo, time() + (1800), "/");
      ee()->db->select('*');
      ee()->db->where('codigo',$codigo);
      $query_modelo_result = ee()->db->get('exp_user_upc_data');

      foreach($query_modelo_result->result() as $row){
        $tipouser = $row->tipouser;
      }
      if (strval($tipouser)=='ALUMNO') {
        return '{site_url}dashboard/estudiante'; 
      } 
      if (strval($tipouser)=='PROFESOR') {
        return '{site_url}dashboard/docente'; 
      } 
      if (strval($tipouser)=='PADRE') {
        return '{site_url}dashboard/padre/hijos/{last_segment}'; 
      }             
    }    
    
    //DESTRUYE LA SESSION
    public function destruir_session () {
      session_name('upc');
      session_start();
      $_SESSION["Token"] = "";
      $this->services->set_cookie("Codigo", NULL);
      $this->services->set_cookie("MsgError", NULL);
      if (isset($_COOKIE[$this->_cookies_prefix."Codigo"])) {
        unset($_COOKIE[$this->_cookies_prefix."Codigo"]);
        $this->services->set_cookie("Codigo", null, -1, "/");
      }
      if (isset($_COOKIE[$this->_cookies_prefix."TipoUser"])) {
        unset($_COOKIE[$this->_cookies_prefix."TipoUser"]);
        $this->services->set_cookie("TipoUser", null, -1, "/");
      }
      if (isset($_COOKIE[$this->_cookies_prefix."Token"])) {
        unset($_COOKIE[$this->_cookies_prefix."Token"]);
        $this->services->set_cookie("Token", null, -1, "/");
      }
      unset($_SESSION["Codigo"]);
      unset($_SESSION["TipoUser"]);
      unset($_SESSION["Nombres"]);
      unset($_SESSION["Apellidos"]);
      unset($_SESSION["Estado"]);
      unset($_SESSION["DscModal"]);
      unset($_SESSION["DscSede"]);
      unset($_SESSION["Ciclo"]);
      unset($_SESSION["Token"]);
      unset($_SESSION["CodError"]);
      unset($_SESSION["MsgError"]);
      unset($_SESSION["Redireccion"]);       
      session_destroy();
	    $site_url = ee()->config->item('site_url');
      redirect($site_url);
    }
    
        
}

/* End of file pi.webservices.php */
/* Location: ./system/expressionengine/third_party/infhotel/pi.webservices.php */
?>
