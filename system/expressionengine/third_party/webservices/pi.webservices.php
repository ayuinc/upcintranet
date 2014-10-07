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
    'pi_author'       => 'Herman Marin',
    'pi_author_url'   => 'http://www.ayuinc.com/',
    'pi_description'  => 'Permite consumir los servicios generados por UPC',
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

		//CONSTRUCTOR DE SESIONES DE ACURDO AL USUARIO
    public function consultar_alumno(){
      $codigo = ee()->TMPL->fetch_param('codigo');
      $contrasena = ee()->TMPL->fetch_param('contrasena');
      $plataforma = ee()->TMPL->fetch_param('plataforma');
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Autenticar2/?Codigo='.$codigo.'&Contrasena='.$contrasena.'&Plataforma='.$plataforma.'';
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      
      session_start();
      $_SESSION["Codigo"] = $json['Codigo'];
      $_SESSION["TipoUser"] = $json['TipoUser'];
      $_SESSION["Nombres"] = $json['Nombres'];
      $_SESSION["Token"] = $json['Token'];
      $_SESSION["CodError"] = $json['CodError'];
      $_SESSION["MsgError"] = $json['MsgError'];
      
      if (strval($_SESSION["TipoUser"])=='ALUMNO') {
      	redirect('/dashboard/estudiante');
      }
			
      if (strval($_SESSION["TipoUser"])=='DOCENTE') {
      	redirect('/dashboard/docente');
      }
      
      if (strval($_SESSION["TipoUser"])=='PADRE') {
      	redirect('/dashboard/padre');
      }  
      
      if (strval($_SESSION["CodError"])=='00001') {
      	redirect('/login/error');
      }                        
    }
    
		//HORARIO DEL ALUMNO
    public function horario_alumno(){
      session_start();
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Horario/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
      return $result;
                      
    }    
    
		//INASISTENCIAS ALUMNO
    public function inasistencias_alumno(){
      session_start();
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
      return $result;
                      
    }  
    
		//CURSOS QUE LLEVA UN ALUMNO
    public function curos_que_lleva_un_alumno(){
      session_start();
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/CursoAlumno/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
      return $result;
                      
    }         
    
    //NOMBRE DEL USUARIO
    public function nombre_alumno(){
    	session_start();
    	return $_SESSION["Nombres"];
    }    
    
    //MUESTRA EL TIPO DE USUARIO
    public function tipo_usuario(){
    	session_start();
    	return $_SESSION["TipoUser"];
    }    
    
    //MENSAJE DE ERROR
    public function mensaje_error(){
    	session_start();
    	return $_SESSION["MsgError"];
    }    
    
    //DESTRUYE LA SESSION
    public function destruir_session () {
			@session_write_close();
			redirect('/');
    }
    
        
}

/* End of file pi.webservices.php */
/* Location: ./system/expressionengine/third_party/infhotel/pi.webservices.php */
?>