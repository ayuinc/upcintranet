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
    'pi_author'       => 'Gianfranco Montoya ',
    'pi_author_url'   => 'http://www.ayuinc.com/',
    'pi_description'  => 'Allow sign_in on the LMS',
    'pi_usage'        => Webservices::usage()
);
            
class Webservices
{

    var $return_data = "";
    // --------------------------------------------------------------------

        /**
         * Memberlist
         *
         * This function returns a list of members
         *
         * @access  public
         * @return  string
         */
    public function __construct(){
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
        ob_start();  ?>
        The Memberlist Plugin simply outputs a
        list of 15 members of your site.

            {exp:webservices}

        This is an incredibly simple Plugin.
        <?php
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }

    public function generar_token(){
    		$codigo = ee()->TMPL->fetch_param('codigo');
        $contrasena = ee()->TMPL->fetch_param('contrasena');
        $plataforma = ee()->TMPL->fetch_param('plataforma');
   
        //$url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Autenticar2/?Codigo='.$codigo.'&Contrasena='.$contrasena.'&Plataforma='.$plataforma;
        $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Autenticar2/?Codigo=u201121382&Contrasena=julito1615&Plataforma=C';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result = curl_exec($ch);
				
				$obj = json_decode($result);
				
				//Creo una variable de session para guardar el token
				//session_start();
				
				//$_SESSION['token']=$obj->{'Token'};
				//$_SESSION['codigo']=$obj->{'Codigo'};  
				
				return $result; 
    }
    
    /*public function alumno_horario(){
    		session_start();
        $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Horario/?CodAlumno='.$_SESSION['codigo'].'&Token='.$_SESSION['token'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
				
				$obj = json_decode($result);       
        return $result; // 12345
    }   
    
    public function alumno_inasistencias(){
    		session_start();
        $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Inasistencia/?CodAlumno='.$_SESSION['codigo'].'&Token='.$_SESSION['token'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
				
				$obj = json_decode($result);       
        return $result; // 12345
    } 
    
    public function alumno_cursos_que_lleva(){
    		session_start();
        $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/CursoAlumno/?CodAlumno='.$_SESSION['codigo'].'&Token='.$_SESSION['token'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
				
				$obj = json_decode($result);       
        return $result; // 12345
    } */        
    
    /*public function alumno_notas_por_curso(){
    		$codcurso = ee()->TMPL->fetch_param('codcurso');
    		session_start();
        $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Nota/?CodAlumno='.$_SESSION['codigo'].'&CodCurso='.$codcurso.'&Token='.$_SESSION['token'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
				
				$obj = json_decode($result);       
        return $result; // 12345
    } */     
     
    //public function destructor_session(){
    		//session_start();
    		//unset($_SESSION['token']);
    		//unset($_SESSION['codigo']);
				//session_destroy();
    //}           
}

/* End of file pi.webservices.php */
/* Location: ./system/expressionengine/third_party/webservices/pi.webservices.php */
