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
    // --------------------------------------------------------------------
        /**
         * Webservices
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
    public function generador_token(){
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
      //INICIAR SESSON
      $_SESSION["Codigo"] = $json['Codigo'];
      $_SESSION["TipoUser"] = $json['TipoUser'];
      $_SESSION["Nombres"] = $json['Nombres'];
      $_SESSION["Apellidos"] = $json['Apellidos'];
      $_SESSION["Estado"] = $json['Estado'];
      $_SESSION["DscModal"] = $json['Datos']['DscModal'];
      $_SESSION["DscSede"] = $json['Datos']['DscSede'];
      $_SESSION["Ciclo"] = $json['Datos']['Ciclo'];
      $_SESSION["Token"] = $json['Token'];
      $_SESSION["CodError"] = $json['CodError'];
      $_SESSION["MsgError"] = $json['MsgError'];
      
      if (strval($_SESSION["CodError"])=='00001') {
        redirect('/login/error');
      }    
                    
    }

    //CONSTRUCTOR DE SESIONES DE ACUERDO AL USUARIO
    public function consultar_alumno(){
      $codigo = $_SESSION["Codigo"];
      $TipoUser = $_SESSION["TipoUser"];
      $token = $_SESSION["Token"];
      
      $result = '';
      
      if (strval($TipoUser)=='ALUMNO') {
        //header('Location: '.'{site_url}/dashboard/estudiante');
        /*$result .= '<ul class="tr pb-7">';
        $result .= '<li class="col-sm-2"></li>';
        $result .= '<li class="col-sm-8 bg-muted">';
        $result .= '<div>';
        $result .= '<span class="zizou-14">';
        $result .= '<a href="/dashboard/estudiante">';
        $result .= '<img class="pr-7" src="{site_url}assets/img/black_arrow.png">Ingrese como Alumno';
        $result .= '</a>';
        $result .= '</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul> ';*/
        $result .= '{redirect="dashboard/estudiante" status_code="301"}';
        return $result;     
      }
      
      if (strval($TipoUser)=='PROFESOR') {
        $result .= '<ul class="tr pb-7">';
        $result .= '<li class="col-sm-2"></li>';
        $result .= '<li class="col-sm-8 bg-muted">';
        $result .= '<div>';
        $result .= '<span class="zizou-14">';
        $result .= '<a href="/dashboard/docente">';
        $result .= '<img class="pr-7" src="{site_url}assets/img/black_arrow.png">Ingrese como Profesor';
        $result .= '</a>';
        $result .= '</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul> ';   
        
        return $result;             
      }
      
      if (strval($TipoUser)=='PADRE') {

        $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/ListadoHijos/?Codigo='.$codigo.'&Token='.$token.'';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $hijosWebService=curl_exec($ch);
        $json = json_decode($hijosWebService, true);

        $result .= '<ul class="tr pb-7">';
        $result .= '<li class="col-sm-2"></li>';
        $result .= '<li class="col-sm-8 bg-muted">';
        $result .= '<div>';
        $result .= '<span class="zizou-14">';
        $result .= '<a href="/dashboard/padre">';
        $result .= '<img class="pr-7" src="{site_url}assets/img/black_arrow.png">Ingrese como Padre';
        $result .= '</a>';
        $result .= '<ul> ';
        for ($i=0; $i < count($json["hijos"])  ; $i++) { 
          $result .= '<li><a href="http://104.131.41.84/dashboard/padre/hijos/'.$json["hijos"][$i]["codigo"].'">'.$json["hijos"][$i]["nombres"].' '.$json["hijos"][$i]["apellidos"].'</a></li>';
        }
        $result .= '</ul> ';
        $result .= '</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul> ';  
        
        return $result;             
      }  
                    
    }
    
    //CONSTRUCTOR DE SESIONES DE ACUERDO AL USUARIO Y LA REDIRECCION QUE LLEGA
    public function consultar_alumno_redireccion(){
      $codigo = $_SESSION["Codigo"];
      $TipoUser = $_SESSION["TipoUser"];
      $redireccion = $_SESSION["Redireccion"];
      
      $result = '';
      
      if (strval($TipoUser)=='ALUMNO') {
        $result .= '<ul class="tr pb-7">';
        $result .= '<li class="col-sm-2"></li>';
        $result .= '<li class="col-sm-8 bg-muted">';
        $result .= '<div>';
        $result .= '<span class="zizou-14">';
        $result .= '<a href="'.$redireccion.'">';
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
        $result .= '<a href="'.$redireccion.'">';
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
        $result .= '<a href="'.$redireccion.'">';
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
    public function horario_alumno(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Horario/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError']; 
      
      //limpio la variable para reutilizarla
      $result = '';      
      
      //genera el tamano del array
      $tamano = count($json['HorarioDia']);
      
      //Loop basado en el HorarioDia
      for ($i=0; $i<$tamano; $i++) {
        $result.= '<div class="panel-table">';
        
        //genera el tamano del array
        $tamano_1 = count($json['HorarioDia'][$i]['Clases']);
			 	
        //Despliega solo las clases del dia
         if ($json['HorarioDia'][$i]['CodDia']==date('w')) {

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
	            $result .= '<ul class="tr">';
	            $result .= '<li class="col-sm-2 helvetica-bold-14">';
	            $result .= '<div class="text-center"><span>'.$HoraInicio[$disponibles].':00</span></div>';
	            $result .= '</li>';
	            $result .= '<li class="col-sm-2 helvetica-bold-14">';
	            $result .= '<div class="text-center"><span>'.$Sede[$disponibles].'</span></div>';
	            $result .= '</li>';
	            $result .= '<li class="col-sm-6 helvetica-12">';
	            $result .= '<div><span>'.$CursoNombre[$disponibles].'</span></div>';
	            $result .= '</li>';
	            $result .= '<li class="col-sm-2 helvetica-bold-14">';
	            $result .= '<div class="text-center"><span>'.$Salon[$disponibles].'</span></div>';
	            $result .= '</li>';
	            $result .= '</ul>';    
	            
	            //Controla que ya no recorra mas el arreglo 
	            if ($disponibles != $tamano_2-1) {
	              $disponibles++;
	            } 
            } else {
              $result .= '<ul class="tr">';
              $result .= '<li class="col-sm-2 helvetica-bold-14">';
              $result .= '<div class="text-center"><span>'.$b.':00</span></div>';
              $result .= '</li>';                
              $result .= '<li class="col-sm-10">';
              $result .= '<div class="text-center"></div>';
              $result .= '</li>';
              $result .= '</ul>';
            }   
          } 
        } 
        $result .= '</div>'; 
      }
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-12">';
        $result .= '<div>'.$error_mensaje.'</div>';
        $result .= '</li>';                
        $result .= '</ul>';	 
        $result .= '</div>';     
      } 
      
      return $result;             
    }    
    
    
    //HORARIO CICLO ACTUAL DEL ALUMNO
    public function horario_ciclo_actual_alumno(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Horario/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];      
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['HorarioDia']);  
      
      for ($i=0; $i<$tamano; $i++) {
	      $result .= '<div>';
	      $result .= '<span class="zizou-14">';
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
	      $result .= '<div class="panel-body mb-35">';
        $result .= '<div class="panel-body-head-table">'; 
        $result .= '<ul class="tr">'; 
        $result .= '<li class="col-sm-1">'; 
        $result .= '<div><span>Inicio</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-1">'; 
        $result .= '<div><span>Fin</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-7">'; 
        $result .= '<div><span>Clase</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-1">'; 
        $result .= '<div><span>Sede</span></div>'; 
        $result .= '</li>';  
        $result .= '<li class="col-sm-1">'; 
        $result .= '<div><span>Sección</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-1">'; 
        $result .= '<div><span>Salón</span></div>'; 
        $result .= '</li>';                                                                                                                                                  
        $result .= '</ul>'; 
        $result .= '</div>'; 	      
	      
	      //genera el tamano del array
	      $tamano_int = count($json['HorarioDia'][$i]['Clases']);	
	      
				for ($b=0; $b<$tamano_int; $b++) {
	          $result .= '<div class="panel-table">'; 
            $result .= '<ul class="tr mis-cursos-row">'; 
            $result .= '<li class="col-xs-1 whole-cell-height">'; 
            $result .= '<span class="helvetica-12">';
            $HoraInicio = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraInicio'], 0, 2);
						$HoraInicio = ltrim($HoraInicio,'0');
						$result .= $HoraInicio.':00';
            $result .='</span>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1 whole-cell-height">'; 
            $result .= '<span class="helvetica-12">';
            $HoraFin = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraFin'], 0, 2);
						$HoraFin = ltrim($HoraFin,'0');
						$result .= $HoraFin.':00';	                
            $result .='</span>'; 	                
            $result .= '</li>';                        
            $result .= '<li class="col-xs-7 whole-cell-height">'; 
            $result .= '<span class="helvetica-12">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['CursoNombre'];
            $result .= '</span>'; 	                
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1 whole-cell-height">'; 
            $result .= '<span class="helvetica-12">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Sede'];
            $result .= '</span>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1 whole-cell-height">'; 
            $result .= '<span class="helvetica-12">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Seccion'];
            $result .= '</span>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1 whole-cell-height">'; 
            $result .= '<span class="helvetica-12">';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Salon'];
            $result .= '</span>';  
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
        $result .= '<li class="col-xs-12 whole-cell-height">';
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
    public function inasistencias_alumno(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];       
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Inasistencias']);
      
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<ul class="tr bg-muted">';
        $result .= '<li class="col-sm-8 helvetica-12 pb-0">';
        $result .= '<div>';
        $result .= '<span>'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
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
        $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Nota/?CodAlumno='.$codigo.'&Token=1'.$token.'&CodCurso='.$codcurso;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result_int=curl_exec($ch);
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
        $result .= '<li class="col-sm-4 show-curso-detail"><div class="text-center"><span><img src="/assets/img/ojo.png"></span></div></li>';
        $result .= '</ul>';
      }     
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-12">';
        $result .= '<div>'.$error_mensaje.'</div>';
        $result .= '</li>';                
        $result .= '</ul>';	 
        $result .= '</div>';     
      }       
      
      
      return $result;               
    }  
    

    //CURSOS QUE LLEVA UN ALUMNO
    public function buscar_curos_que_lleva_un_alumno(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"]; 
      $codalumno = ee()->TMPL->fetch_param('codalumno');  
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/InasistenciaProfesor/?Codigo='.$codigo.'&CodAlumno='.$codalumno.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];        
      
      $result = '';
      
	    //genera el tamano del array
	    $tamano = count($json['Inasistencias']);

			$result .= '<div class="panel-body">';
			$result .= '<div class="panel-body-head-table">';
			$result .= '<ul class="tr">';
			$result .= '<li class="col-sm-2"><div><span>Código Curso</span></div></li>';
			$result .= '<li class="col-sm-7"><div><span>Nombre</span></div></li>';
			$result .= '<li class="col-sm-3"><div><span>Inasistencias</span></div></li>';
			$result .= '</ul>';
			$result .= '</div>';
			$result .= '<div class="panel-table">';
			$result .= '<ul class="tr mis-cursos-row">';

			for ($i=0; $i<$tamano; $i++) {

	      $result .= '<li class="col-sm-2 helvetica-12">';
	      $result .= '<div>';
	      $result .= '<span class="text-left">'.$json['Inasistencias'][$i]['CodCurso'].'</span>';
	      $result .= '</div>';
	      $result .= '</li>';
	      $result .= '<li class="col-sm-7 text-center helvetica-bold-14">';
	      $result .= '<div>';	      
	      $result .= '<span class="text-left">'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
	      $result .= '</div>';
	      $result .= '</li>';
	      $result .= '<li class="col-sm-3 text-center helvetica-bold-14">';
	      $result .= '<div>';
	      $result .= '<span class="text-left">'.$json['Inasistencias'][$i]['Total'].'/'.$json['Inasistencias'][$i]['Maximo'].'</span>';
	      $result .= '</div>';
	      $result .= '</li>';

      } 
      
      $result .= '</ul>'; 
      $result .= '</div>';
      $result .= '</div>';
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-12">';
        $result .= '<div>'.$error_mensaje.'</div>';
        $result .= '</li>';                
        $result .= '</ul>';	 
        $result .= '</div>';     
      }  
      
      return $result;           
    }       



    //CURSOS QUE LLEVA UN ALUMNO
    public function curos_que_lleva_un_alumno(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];     
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];        
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Inasistencias']);
      
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-9 helvetica-12">';
        $result .= '<div>';
        $result .= '<span>'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-3 text-center helvetica-bold-14">';

          $codcurso = $json['Inasistencias'][$i]['CodCurso'];
          
          //Loop interno para calcular notas segun curso
          $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Nota/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;
          $ch = curl_init($url);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_URL,$url);
          $result_int=curl_exec($ch);
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
        $result .= '<li class="col-sm-12">';
        $result .= '<div>'.$error_mensaje.'</div>';
        $result .= '</li>';                
        $result .= '</ul>';	 
        $result .= '</div>';     
      }       
      
      return $result;           
    }   
    
    //TODOS LOS CURSOS QUE LLEVA UN ALUMNO
    public function todos_los_curos_que_lleva_un_alumno(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];       
      
      $tamano = count($json['Inasistencias']);
      $result = '';
      
      $result .= '<ul class="tr">';
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<a href="#curso-'.$i.'" class="curso-link">';
        $result .= '<li class="bg-muted pl-7 col-sm-12 mb-5">';
        $result .= '<span class="zizou-14">';
        $result .= '<img class="pr-7" src="{site_url}assets/img/black_arrow_tiny.png">';
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
        $result .= '<span class="zizou-14">';
        $result .= $error_mensaje;
        $result .= '</span>';
        $result .= '</li>';                
        $result .= '</ul>';	    
      }       
      
      return $result;
    
    } 
    
    
    //DETALLE DE CURSOS POR ALUMNO
    public function detalle_de_curos_por_alumno(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['Inasistencias']);
      
      for ($i=0; $i<$tamano; $i++) {
        $result .= '<ul class="tr bg-muted">';
        $result .= '<li class="col-sm-8 helvetica-12 pb-0">';
        $result .= '<div>';
        $result .= '<span>'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
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
          $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Nota/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;
          $ch = curl_init($url);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_URL,$url);
          $result_int=curl_exec($ch);
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
        $result .= '<li class="col-sm-4 show-curso-detail"><div class="text-center"><span><img src="/assets/img/ojo.png"></span></div></li>';
        $result .= '</ul>';
      }     
      
      return $result;               
    }  
    
    
    //NOTAS DE UN ALUMNO POR CURSO
    public function notas_alumno_por_curso(){
     $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Inasistencia/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
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
        $result .= '<div class="panel-body-head left">';
        $result .= '<ul class="tr">';
        $result .= '<span>'.$json['Inasistencias'][$i]['CursoNombre'].'</span>';
        $result .= '</ul>';
        $result .= '</div>';
        
        $codcurso = $json['Inasistencias'][$i]['CodCurso'];
        
        //Loop interno para calcular notas segun curso
        $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Nota/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result_int=curl_exec($ch);
        $json_int = json_decode($result_int, true);           
      
        //genera el tamano del array
        $tamano_int = count($json_int['Notas']);
        $nota = 0;
        $porcentaje = 0;

        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-12">';
        $result .= '<div>';
        $result .= '<span class="helvetica-14 ml-7">FÓRMULA: '.$json_int['Formula'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';    
        $result .= '<div class="panel-body-head-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-1">';
        $result .= '<div><span>Tipo</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div><span>Número</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-5">';
        $result .= '<div><span>Evaluación</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div><span>Peso</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div><span>Nota</span></div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>'; 
        $result .= '<div class="panel-table">'; 
        $result .= '<ul class="tr">';          

        for ($b=0; $b<$tamano_int; $b++) {
          
          $porcentaje = rtrim($json_int['Notas'][$b]['Peso'],"%");
          $nota = ($json_int['Notas'][$b]['Valor']*$porcentaje)/100 + $nota; 
            
          $result .= '<li class="col-sm-1">';
          $result .= '<div class="text-center">';
          $result .= '<span class="helvetica-14">'.$json_int['Notas'][$b]['NombreCorto'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-2">';
          $result .= '<div class="text-center">';
          $result .= '<span class="helvetica-14">'.$json_int['Notas'][$b]['NroEvaluacion'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-5">';
          $result .= '<div>';
          $result .= '<span class="helvetica-14">'.$json_int['Notas'][$b]['NombreEvaluacion'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-2">';
          $result .= '<div class="text-center">';
          $result .= '<span class="helvetica-14">'.$json_int['Notas'][$b]['Peso'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-2">';
          $result .= '<div class="text-center">';
          $result .= '<span class="helvetica-bold-14">'.$json_int['Notas'][$b]['Valor'].'</span>';
          $result .= '</div>';
          $result .= '</li>';
          
        }
          
        //Cambia el formato a 2 decimales
        $nota = number_format($nota, 2, '.', '');
          
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '<div class="panel-table observaciones">';
        $result .= ' <ul class="tr">';
        $result .= '<li class="col-sm-8">';
        $result .= '<div>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div class="text-center">';
        $result .= '<span class="helvetica-14 uppercase">Nota al '.$json_int['PorcentajeAvance'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div class="text-center">';
        $result .= '<span class="helvetica-bold-14">'.$nota.'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '<a class="black curso-link text-right" href="#top">';
        $result .= '<div class="zizou-14 pt-14 mb-35">';
        $result .= 'Regresar a lista de cursos';
        $result .= '<img src="{site_url}assets/img/black_arrow_tiny_up.png" alt="">';
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
        $result .= '<li class="col-xs-12 whole-cell-height">';
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
    public function tramites_realizados_por_alumno(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/TramiteRealizado/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
      $json = json_decode($result, true);
      
      //limpio la variable para reutilizarla
      $result = '';
      
      $CodError = $json['CodError'];
      $MsgError = $json['MsgError'];
      
      if ($CodError == '00051') {
        $result .= '<span class="zizou-18">';
        $result .= '<img class="mt--7" src="{site_url}assets/img/check.png" alt="">';
        $result .= $MsgError;
        $result .= '</span>';
      } else {
	      
	      $tamano = count($json['TramitesRealizados']);
				
        $result .= '<div class="panel-body-head-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-2">';
        $result .= '<div class="fecha"><span>No. Solicitud</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-6">';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div class=""><span>Fecha de Inicio</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div class=""><span>Estado</span></div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';
        
	      for ($i=0; $i<$tamano; $i++) {  
	        $result .= '<div class="panel-table"> ';
	        $result .= '<ul class="tr">';
	        $result .= '<li class="col-sm-2 solano-bold-18 uppercase">';
	        $result .= '<div class="text-center">';
	        $result .= '<span>'.$json['TramitesRealizados'][$i]['NroSolicitud'].'</span>';
	        $result .= '</div>';
	        $result .= '</li>';
	        $result .= '<li class="col-sm-6 helvetica-14">';
	        $result .= '<div>';
	        $result .= '<span>'.$json['TramitesRealizados'][$i]['Nombre'].'</span>';
	        $result .= '</div>';
	        $result .= '</li>';
	        $result .= '<li class="col-sm-2 solano-bold-18 uppercase">';
	        $result .= '<div class="text-center nrb">';
	        $result .= '<span>'.$json['TramitesRealizados'][$i]['Fecha'].'</span>';
	        $result .= '</div>';
	        $result .= '</li>';
	        
	        if ($json['TramitesRealizados'][$i]['Estado']=='NO PROCEDE') {
		        $result .= '<li class="col-sm-2 pdte-tr solano-bold-18">';
		        $result .= '<div class="text-center">';
		        $result .= '<span>'.$json['TramitesRealizados'][$i]['Estado'].'</span>';
		        $result .= '</div>';
		        $result .= '</li>';
	        } 
	        if ($json['TramitesRealizados'][$i]['Estado']=='PROCEDE') {
		        $result .= '<li class="col-sm-2 apr-tr solano-bold-18">';
		        $result .= '<div class="text-center">';
		        $result .= '<span>'.$json['TramitesRealizados'][$i]['Estado'].'</span>';
		        $result .= '</div>';
		        $result .= '</li>';
	        }
	        if ($json['TramitesRealizados'][$i]['Estado']=='RESPONDIDA') {
		        $result .= '<li class="col-sm-2 apr-tr solano-bold-18">';
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
    public function companeros_clase_por_curso(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $codcurso = ee()->TMPL->fetch_param('codcurso');
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Companeros/?CodAlumno='.$codigo.'&Token='.$token.'&CodCurso='.$codcurso;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
      return $result;         
    }
    
    //PROXIMA BOLETA DEL ALUMNO   
    public function proxima_boleta_alumno(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/PagoPendiente/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
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
    public function boletas_pendientes_alumno(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/PagoPendiente/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      if (($json['CodError']=='00041') || ($json['CodError']=='00003')) {
        
        $result = '<span class="zizou-18"><img class="mt--7" src="{site_url}assets/img/check.png" alt="">'.$json['MsgError'].'</span>';
        return $result;
        
      } else {
        
        $result = '<div class="panel-body-head left">';
        $result = '<ul class="tr">';
        $result = '<span>Cuota 3</span>';
        $result = '</ul>';
        $result = '</div>';
        $result = '<div class="panel-table">';
        $result = '<ul class="tr">';
        $result = '<li class="col-sm-4 pl-7">';
        $result = '<div class="nrb helvetica-14">';
        $result = '<div>';
        $result = '<strong>DOCUMENTO: </strong>200-0116467';
        $result = '</div> ';
        $result = '</div>';
        $result = '</li>';
        $result = '<li class="col-sm-3">';
        $result = '<div class="nrb helvetica-14">';
        $result = '<div>';
        $result = '<strong>EMITIDA: </strong>20/02/2014';
        $result = '</div>';
        $result = '</div>';
        $result = '</li>';
        $result = '<li class="col-sm-3">';
        $result = '<div class="nrb helvetica-14">';
        $result = '<div>';
        $result = '<strong>VENCE: </strong>20/02/2014';
        $result = '</div>';
        $result = '</div>';
        $result = '</li>';
        $result = '<li class="col-sm-2 apr-tr">';
        $result = '<div class="text-center">';
        $result = '<span class="helvetica-bold-12">A TIEMPO</span>';
        $result = '</div>';
        $result = '</li>';
        $result = '</ul>';
        $result = '</div>';
        $result = '<div class="panel-body-head-table">';
        $result = '<ul class="tr">';
        $result = '<li class="col-sm-10">';
        $result = '<div><span>Detalle</span></div>';
        $result = '</li>';
        $result = '<li class="col-sm-2">';
        $result = '<div><span>Monto (S/.)</span></div>';
        $result = '</li>';
        $result = '</ul>';
        $result = '</div>';
        $result = '<div class="panel-table pl-7">';
        $result = '<ul class="tr">';
        $result = '<li class="col-sm-10">';
        $result = '<div>';
        $result = '<span class="helvetica-14">Importe</span>';
        $result = '</div>';
        $result = '</li>';
        $result = '<li class="col-sm-2">';
        $result = '<div class="text-center">';
        $result = '<span class="helvetica-14">1432.00</span>';
        $result = '</div>';
        $result = '</li>';
        $result = '</ul>';
        $result = '<ul class="tr">';
        $result = '<li class="col-sm-10">';
        $result = '<div>';
				$result = '<span class="helvetica-14">Descuento</span>';
        $result = '</div>';
        $result = '</li>';
        $result = '<li class="col-sm-2">';
        $result = '<div class="text-center">';
        $result = '<span class="helvetica-14">0</span>';
        $result = '</div>';
        $result = '</li>';
        $result = '</ul>';
        $result = '</div>';
        $result = '<div class="panel-table observaciones">';
        $result = '<ul class="tr">';
        $result = '<li class="col-sm-10 pl-7">';
        $result = '<div>';
        $result = '<span class="uppercase helvetica-14">TOTAL</span>';
        $result = '</div>';
        $result = '</li>';
        $result = '<li class="col-sm-2">';
        $result = '<div class="text-center">';
        $result = '<span class="helvetica-14 uppercase">1432.00</span>';
        $result = '</div>';
        $result = '</li>';
        $result = '</ul>';
        $result = '</div>';  

        return $result;

      } 
           
    }                 
    
    //POBLAR ESPACIOS DEPORTIVOS - SEDE  
    public function poblar_espacios_deportivos_sede(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/PoblarED/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $result = '';
      
      $tamano = count($json['Sedes']);
      
      $result .= '<select name="CodSede" id="CodSede" class="reservas-select form-control">';
			$result .= '<option>Selecciona una sede</option>';      
      for ($i=0; $i<$tamano; $i++) {
      	$result .= '<option value="'.$json['Sedes'][$i]['key'].'">';
      	$result .= $json['Sedes'][$i]['sede'];
      	$result .= '</option>';
      }
      $result .= '</select>';
      
      return $result;          
    } 
    
    //POBLAR ESPACIOS DEPORTIVOS - Espacios  
    public function poblar_espacios_deportivos_espacios(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/PoblarED/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $result = '';
      
      $tamano = count($json['Sedes']);
      
      for ($i=0; $i<$tamano; $i++) {

				$tamano_int = count($json['Sedes'][$i]['espacios']);
				$result .= '<select name="CodED" class="reservas-select form-control" id="sede-'.$json['Sedes'][$i]['key'].'">';
				$result .= '<option>Seleccionar espacio</option>'; 
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
    public function poblar_espacios_deportivos_actividad(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/PoblarED/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $result = '';
      
      $tamano = count($json['Sedes']);
      
      for ($i=0; $i<$tamano; $i++) {

				$tamano_int = count($json['Sedes'][$i]['espacios']);

				for ($a=0; $a<$tamano_int; $a++) {

					$tamano_fin = count($json['Sedes'][$i]['espacios'][$a]['actividades']);
					$result .= '<select class="reservas-select form-control" name="CodActiv" id="actividad-'.$json['Sedes'][$i]['espacios'][$a]['codigo'].'">';
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
    public function disponibilidad_espacios_deportivos(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $codsede = ee()->TMPL->fetch_param('codsede');
      $coded = ee()->TMPL->fetch_param('coded');
      $codactiv = ee()->TMPL->fetch_param('codactiv');
      $numhoras = ee()->TMPL->fetch_param('numhoras');
      $fechaini = ee()->TMPL->fetch_param('fechaini');
      $fechaini = substr($fechaini, 9,10).substr($fechaini, 6,7).substr($fechaini, 0,4);
      $fechafin = ee()->TMPL->fetch_param('fechafin');
      $fechafin = substr($fechafin, 9,10).substr($fechafin, 6,7).substr($fechafin, 0,4);
      $segmento = ee()->TMPL->fetch_param('segmento');
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/DisponibilidadED/?CodSede='.$codsede.'&CodED='.$coded.'&NumHoras='.$numhoras.'&CodAlumno='.$codigo.'&FechaIni='.$fechaini.'&FechaFin='.$fechafin.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];
      
      $result = '';
      $tamano = count($json['HorarioDia']);
      
      for ($i=0; $i<$tamano; $i++) {
	      
	      if ($json['HorarioDia'][$i]['CodDia']==1) {
	      	$result .= '<div class="block"><span class="helvetica-14">Lunes</span></div>';
	      }
	      
	      if ($json['HorarioDia'][$i]['CodDia']==2) {
	      	$result .= '<div class="block"><span class="helvetica-14">Martes</span></div>';
	      }	
	        
	      if ($json['HorarioDia'][$i]['CodDia']==3) {
	      	$result .= '<div class="block"><span class="helvetica-14">Miércoles</span></div>';
	      }
	      
	      if ($json['HorarioDia'][$i]['CodDia']==4) {
	      	$result .= '<div class="block"><span class="helvetica-14">Jueves</span></div>';
	      }	
	       
	      if ($json['HorarioDia'][$i]['CodDia']==5) {
	      	$result .= '<div class="block"><span class="helvetica-14">Viernes</span></div>';
	      }
	      
	      if ($json['HorarioDia'][$i]['CodDia']==6) {
	      	$result .= '<div class="block"><span class="helvetica-14">Sábado</span></div>';
	      }	
	       	      
	      $tamano_int = count($json['HorarioDia'][$i]['Disponibles']);
	      
	      $result .='<div class="row">';
	      
	      for ($a=0; $a<$tamano_int; $a++) {
		      $result .= '<form action="{site_url}index.php/'.$segmento.'/resultados-reservas-deportivos" method="post" name="form-'.$a.'">';
		      $result .= '<input type="hidden" name="XID" value="{XID_HASH}" />';
		      $result .= '<input type="hidden" value="1" name="Flag">';
		      $result .= '<input type="hidden" value="'.$codsede.'" name="CodSede">';
		      $result .= '<input type="hidden" value="'.$coded.'" name="CodED">';
		      $result .= '<input type="hidden" value="'.$codactiv.'" name="CodActiv">';
		      $result .= '<input type="hidden" value="'.$numhoras.'" name="NumHoras">';
		      $result .= '<input type="hidden" value="Ninguno" name="Detalles">';
		      $result .= '<input type="hidden" value="'.$json['HorarioDia'][$i]['Disponibles'][$a]['Fecha'].'" name="Fecha">';
		      $result .= '<div class="col-sm-4 helvetica-12 mb-7">';
		      $result .= 'Fecha: '.$json['HorarioDia'][$i]['Disponibles'][$a]['Fecha'].'<br>';
		      $HoraInicio = substr($json['HorarioDia'][$i]['Disponibles'][$a]['HoraInicio'], 0, 2);
          $HoraInicio = ltrim($HoraInicio,'0');
		      $HoraFin = substr($json['HorarioDia'][$i]['Disponibles'][$a]['HoraFin'], 0, 2);
          $HoraFin = ltrim($HoraFin,'0');
          $result .= '<input type="hidden" value="'.$json['HorarioDia'][$i]['Disponibles'][$a]['HoraInicio'].'" name="HoraIni">';
		      $result .= '<input type="hidden" value="'.$json['HorarioDia'][$i]['Disponibles'][$a]['HoraFin'].'" name="HoraFin">';
          $result .= 'Hora: '.$HoraInicio.':00 - '.$HoraFin.':00<br>';		      
		      if ($json['HorarioDia'][$i]['Disponibles'][$a]['Sede']=='L') {
			    	$result .= 'Sede: Complejo Alamos';  
		      } else {
			      $result .= 'Sede: Campus Villa';
		      }
		      $result .= '<input type="submit" value="Reservar" name="submit">';
		      $result .= '</div>';
		      $result .= '</form>';
		    }  
		    
        $result .= '</div>';              
	    }        
	    
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= $error_mensaje;   
      }     	   
       
      return $result;          
    } 
    
    //RESERVA DE ESPACIOS DEPORTIVOS   
    public function reserva_espacios_deportivos(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $codsede = ee()->TMPL->fetch_param('codsede');
      $coded = ee()->TMPL->fetch_param('coded');
      $codactiv = ee()->TMPL->fetch_param('codactiv');
      $numhoras = ee()->TMPL->fetch_param('numhoras');
      $fecha = ee()->TMPL->fetch_param('fecha');
      $fecha = substr($fecha, 0,1).substr($fecha, 3,4).substr($fecha, 6,9);
      $horaini = ee()->TMPL->fetch_param('horaini');
      $horafin = ee()->TMPL->fetch_param('horafin');
      $detalles = ee()->TMPL->fetch_param('detalles');

      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/ReservarED/?CodSede='.$codsede.'&CodED='.$coded.'&CodActiv='.$codactiv.'&NumHoras='.$numhoras.'&CodAlumno='.$codigo.'&Fecha='.$fecha.'&HoraIni='.$horaini.'&HoraFin='.$horafin.'&Detalles='.$detalles.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];       

			$result = '';
			
      //Control de errores
      $result .= '<div class="mt-7 zizou-14">'.$error_mensaje.'</div>';

      
      return $result;          
    } 
    
    
    //LISTA DE RECURSOS DISPONIBLES
    public function listado_recursos_disponibles(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $tiporecurso = ee()->TMPL->fetch_param('TipoRecurso');
      
      $fecini = ee()->TMPL->fetch_param('FecIni');
      $fecini = substr($fecini, 0,1).substr($fecini, 3,4).substr($fecini, 6,9);
      $fechafin= ee()->TMPL->fetch_param('FechaFin');
      $fechafin = substr($fechafin, 0,1).substr($fechafin, 3,4).substr($fechafin, 6,9);
      $canhoras= ee()->TMPL->fetch_param('CanHoras');
			$segmento= ee()->TMPL->fetch_param('segmento');
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/RecursosDisponible/?TipoRecurso='.$tiporecurso.'&Local=A&FecIni='.$fecini.'&CanHoras='.$canhoras.'&FechaFin='.$fechafin.'&CodAlumno='.$codigo.'&Token='.$token;
      //https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/RecursosDisponible/?TipoRecurso=CO&Local=A&FecIni=19122014&CanHoras=1&FechaFin=19122014&CodAlumno=U201121382&Token=52143ef2a545456cbbe6eff148b0812820141219120128      
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];      
      //$result = ''; 
      
      $tamano = count($json['Recursos']); 
      
      if($error=='00000'){
        $result .= '<div class="panel-table no-bg">';
  			for ($i=0; $i<$tamano; $i++) { 
  	      $result .= '<form action="{site_url}index.php/'.$segmento.'/resultados-reserva-recursos" method="post" name="formrecurso-'.$i.'">';
  	      $result .= '<input type="hidden" name="XID" value="{XID_HASH}" />';	
  	      $result .= '<input type="hidden" name="'.$json['Recursos'][$i]['CodRecurso'].'" value="CodRecurso" />';
  	      $result .= '<input type="hidden" name="'.$json['Recursos'][$i]['NomRecurso'].'" value="NomRecurso" />';
  	      $result .= '<input type="hidden" name="'.$canhoras.'" value="CanHoras" />';
  	      $result .= '<input type="hidden" name="'.$fecini.'" value="fecIni" />';
  	      $result .= '<input type="hidden" name="'.$fechafin.'" value="fecFin" />';
  	      $result .= '<input type="hidden" name="1" value="Flag" />';				
  	      $result .= '<ul class="tr">';
  	      $result .= '<li class="col-sm-4 helvetica-12">';
  	      $result .= '<div class="text-center">';    
  	      $result .= '<span>'.$json['Recursos'][$i]['NomRecurso'].'</span>';
  	      $result .= '</div>';
  	      $result .= '</li>';
  	      $result .= '<li class="col-sm-4 helvetica-10">';
  	      $result .= '<div class="text-center">';
  	      $result .= '<span>'.$json['Recursos'][$i]['Local'].'</span>';
  	      $result .= '</div>';
  	      $result .= '</li>';
  	      $result .= '<li class="col-sm-4 helvetica-12">';
  	      $result .= '<div class="text-center">';	      
  	      $result .= '<input type="submit" value="Reservar" name="submit">';
  	      $result .= '</div>';
  	      $result .= '</li>';	      
  	      $result .= '</ul>';
  	      $result .= '</form>';
        }
        $result .= "</div>";
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
    
    
    //RESERVA DE RECURSOS
    public function reserva_recursos(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $codrecurso = ee()->TMPL->fetch_param('CodRecurso');
      $nomrecurso = ee()->TMPL->fetch_param('NomRecurso');
      $fecini = ee()->TMPL->fetch_param('FecIni');
      $fecini = substr($fecini, 9,10).substr($fecini, 6,7).substr($fecini, 0,4);
      $horaini = ee()->TMPL->fetch_param('HoraIni');
      $fechafin= ee()->TMPL->fetch_param('FechaFin');
      $fechafin = substr($fechafin, 9,10).substr($fechafin, 6,7).substr($fechafin, 0,4);
      $horafin = ee()->TMPL->fetch_param('HoraFin');
      $canhoras= ee()->TMPL->fetch_param('CanHoras');
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Reservar/?CodRecurso='.$codrecurso.'&NomRecurso='.$nomrecurso.'&CodAlumno='.$codigo.'&CanHoras='.$canhoras.'&fecIni='.$fecini.' '.$horaini.'&fecFin='.$fechafin.' '.$horafin.'&Token='.$token;

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];      
      $result = ''; 
      
      $result .= '<div class="panel-table no-bg">';
      $result .= '<ul class="tr">';
      $result .= '<li class="col-sm-3 helvetica-12">';
      $result .= '<div class="text-center">';
      $result .= '<span>'.$json['CodRecurso'].'</span>';
      $result .= '</div>';
      $result .= '</li>';
      $result .= '<li class="col-sm-3 helvetica-12">';
      $result .= '<div class="text-center">';    
      $result .= '<span>'.$json['CodReserva'].'</span>';
      $result .= '</div>';
      $result .= '</li>';
      $result .= '<li class="col-sm-6 helvetica-12">';
      $result .= '<div class="text-center">';
      $result .= '<span>'.$json['Mensaje'].'</span>';
      $result .= '</div>';
      $result .= '</li>';
      $result .= '</ul>';
      $result .= "</div>";
       
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
    
    
    //LISTADO DE RECURSOS RESERVADOS POR EL ALUMNO     
    public function listado_recursos_reservados_alumno(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $hoy = date('dmY');
      $unasemana = date('dmY',strtotime('+1 week'));
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/ReservaAlumno/?FecIni='.$hoy.'&FechaFin='.$unasemana.'&CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $result = '';
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];      
       
      $tamano = count($json['Reservas']); 
      
      $result .= '<div class="panel-table no-bg">';
      for ($i=0; $i<$tamano; $i++) { 
	      $result .= '<ul class="tr">';
	      $result .= '<li class="col-sm-2 helvetica-12">';
	      $result .= '<div class="text-center">';
	      $result .= '<span>'.$json['Reservas'][$i]['FecReserva'].'</span>';
	      $result .= '</div>';
	      $result .= '</li>';
	      $result .= '<li class="col-sm-2 helvetica-12">';
	      $result .= '<div class="text-center">';
	      $HoraInicio = substr($json['Reservas'][$i]['HoraIni'], 0, 2);
        $HoraInicio = ltrim($HoraInicio,'0');
	      $HoraFin = substr($json['Reservas'][$i]['HoraFin'], 0, 2);
        $HoraFin = ltrim($HoraFin,'0');	      
	      $result .= '<span>'.$HoraInicio.'-'.$HoraFin.'</span>';
	      $result .= '</div>';
	      $result .= '</li>';
	      $result .= '<li class="col-sm-6 helvetica-12">';
	      $result .= '<div class="text-center">';
	      $result .= '<span>'.$json['Reservas'][$i]['DesTipoRecurso'].'<br>Código Reserva: '.$json['Reservas'][$i]['CodReserva'].'</span>';
	      $result .= '</div>';
	      $result .= '</li>';
	      $result .= '<li class="col-sm-2 helvetica-12">';
	      $result .= '<div class="text-center">';
	      $result .= '<span>'.$json['Reservas'][$i]['NomRecurso'].'</span>';
	      $result .= '</div>';
	      $result .= '</li>';
	      $result .= '</ul>';
      }
      $result .= '</div>';     
       
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-12 helvetica-12"><div class="text-center"><span>'.$error_mensaje.'</span></div></li>'; 
        $result .= '</ul>';
        $result .= '</div>';
      }
      return $result;          
    } 
    
    
    //LISTADO DE HIJOS DEL PADRE DE FAMILIA  
    public function lista_hijos_padre(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/ListadoHijos/?Codigo='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
      return $result;          
    }  
    
    //LISTADOS DE CURSOS DICTADOS POR EL PROFESOR 
    public function lista_cursos_dictados_profesor(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/ListadoCursosProfesor/?Codigo='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];       

      //limpio la variable para reutilizarla
      $result = '';

      //genera el tamano del array
      $tamano = count($json['modalidades']);

      for ($i=0; $i<$tamano; $i++) {
        $result .= '<h4>'.$json['modalidades'][$i]['descripcion'].'</h4>';
        
        $result .= '<div class="panel-body-head-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-8">';
        $result .= '<div><span>Curso</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div class=""><span>Sección</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div class=""><span>Grupo</span></div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';  
        $result .= '<div class="panel-table no-bg">';              
	      
	      //genera el tamano del array
	      $tamano_int = count($json['modalidades'][$i]['cursos']);	      
	      
	      for ($a=0; $a<$tamano; $a++) {
	        $result .= '<ul class="tr bg-muted">';
	        $result .= '<li class="col-sm-8 helvetica-10 pb-0">';
	        $result .= '<div>';
	        $result .= '<span>'.$json['modalidades'][$i]['cursos'][$a]['curso'].'</span>';
	        $result .= '</div>';
	        $result .= '</li>';
	        $result .= '<li class="col-sm-2 helvetica-10">';
	        $result .= '<div class="text-center">';
	        $result .= '<span>'.$json['modalidades'][$i]['cursos'][$a]['seccion'].'</span>';
	        $result .= '</div>';
	        $result .= '</li>';
	        $result .= '<li class="col-sm-2 helvetica-10">';
	        $result .= '<div class="text-center">';
	        $result .= '<span>'.$json['modalidades'][$i]['cursos'][$a]['grupo'].'</span>';
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
        $result .= '<li class="col-sm-12 helvetica-bold-14"><div class="text-center"><span>'.$error_mensaje.'</span></div></li>'; 
        $result .= '</ul>';
        $result .= '</div>';
      }      

      return $result;          
    }
    
     //LISTADOS DE CURSOS DICTADOS POR EL PROFESOR LINK ALUMNOS 
    public function lista_cursos_dictados_link_alumnos_profesor(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/ListadoCursosProfesor/?Codigo='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];       

      //limpio la variable para reutilizarla
      $result = '';

      //genera el tamano del array
      $tamano = count($json['modalidades']);

      for ($i=0; $i<$tamano; $i++) {
        $result .= '<h4>'.$json['modalidades'][$i]['descripcion'].'</h4>';
        
        $result .= '<div class="panel-body-head-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-12">';
        $result .= '<div><span>Curso</span></div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';  
        $result .= '<div class="panel-table no-bg">';              
	      
	      //genera el tamano del array
	      $tamano_int = count($json['modalidades'][$i]['cursos']);	      
	      
	      for ($a=0; $a<$tamano; $a++) {
	        $result .= '<ul class="tr bg-muted">';
	        $result .= '<li class="col-sm-9 helvetica-10 pb-0">';
	        $result .= '<div>';
	        $result .= '<span>'.$json['modalidades'][$i]['cursos'][$a]['curso'].'</span>';
	        $result .= '</div>';
	        $result .= '</li>';
	        $result .= '<li class="col-sm-3 helvetica-10">';
	        $result .= '<div class="text-center">';
	        $result .= '<span>';
	        $result .= '<form action="{site_url}index.php/mi-docencia/cursos-detallados" method="post" id="form-'.$i.'">';
	        $result .= '<input type="hidden" name="XID" value="{XID_HASH}" />';
	        $result .= '<input type="hidden" name="Flag" value="buscar-curso">';
	        $result .= '<input type="hidden" name="Modalidad" value="'.$json['modalidades'][$i]['codigo'].'">';
	        $result .= '<input type="hidden" name="Periodo" value="'.$json['modalidades'][$i]['periodo'].'">';
	        $result .= '<input type="hidden" name="Curso" value="'.$json['modalidades'][$i]['cursos'][$a]['cursoId'].'">';
	        $result .= '<input type="hidden" name="Seccion" value="'.$json['modalidades'][$i]['cursos'][$a]['seccion'].'">';
	        $result .= '<input type="hidden" name="Grupo" value="'.$json['modalidades'][$i]['cursos'][$a]['grupo'].'">';
	        $result .= '<input type="submit" value="Alumnos" name="submit">';
	        $result .= '</form>';
	        $result .= '</span>';
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
        $result .= '<li class="col-sm-12 helvetica-bold-14"><div class="text-center"><span>'.$error_mensaje.'</span></div></li>'; 
        $result .= '</ul>';
        $result .= '</div>';
      }      

      return $result;          
    }      
    
    //LISTADOS DE ALUMNOS MATRICULADOS EN UN CURSO DICTADO POR EL PROFESOR
    public function lista_alumnos_matriculados_en_curso_por_profesor(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $modalidad = ee()->TMPL->fetch_param('modalidad');
      $periodo = ee()->TMPL->fetch_param('periodo');
      $curso = ee()->TMPL->fetch_param('curso');
      $seccion = ee()->TMPL->fetch_param('seccion');
      $grupo = ee()->TMPL->fetch_param('grupo');
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/ListadoAlumnosProfesor/?Codigo='.$codigo.'&Token='.$token.'&Modalidad='.$modalidad.'&Periodo='.$periodo.'&Curso='.$curso.'&Seccion='.$seccion.'&Grupo='.$grupo;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];
      
      $result = ''; 
      
      //genera el tamano del array
	    $tamano = count($json['Cursos']);  
      
      for ($i=0; $i<$tamano; $i++) {
	      
        $result .= '<div class="panel-head">';
        $result .= '<div class="panel-head-left">';
        $result .= '<div class="panel-title">';
        $result .= '<h2>Lista de Alumnos: '.$json['Cursos'][$i]['curso'].'</h2>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '<div class="panel-head-right pt-14">';
        $result .= '<div class="panel-scroll"></div>';
        $result .= '<div class="panel-display"></div>';
        $result .= '</div>';
        $result .= '</div>';
	      
				$result .= '<div class="panel-body mb-35">';
				$result .= '<div class="panel-body-head-table">';
				$result .= '<ul class="tr">';
				$result .= '<li class="col-sm-2">';
				$result .= '<div>';
				$result .= '<span>Foto</span>';
				$result .= '</div>';
				$result .= '</li>';
				$result .= '<li class="col-sm-6">';
				$result .= '<div>';
				$result .= '<span>Nombre</span>';
				$result .= '</div>';
				$result .= '</li>';
				$result .= '<li class="col-sm-2">';
				$result .= '<div>';
				$result .= '<span>Nombre</span>';
				$result .= '</div>';
				$result .= '</li>';				
				$result .= '<li class="col-sm-2">';
				$result .= '<div>';
				$result .= '<span>Inasistencias</span>';
				$result .= '</div>';
				$result .= '</li>';				
				$result .= '</ul>';
				$result .= '</div>';
      
	      //genera el tamano del array
		    $tamano_int = count($json['Cursos'][$i]['alumnos']); 				
				
				for ($a=0; $a<$tamano_int; $a++) {
					$result .= '<div class="panel-table">';
		      $result .= '<ul class="tr">';
          $result .= '<li class="col-sm-2 helvetica-14">';
          $result .= '<div class="text-center">';
          $result .= '<span class=" text-center">';
          $result .= '<img src="'.$json['Cursos'][$i]['alumnos'][$a]['url_foto'].'" width="55" class="pt-7 pb-7">';
          $result .= '</span>';
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-6 helvetica-14">';
          $result .= '<div class="text-center">';
          $result .= '<span class="pt-35 pb-35">';
          $result .= $json['Cursos'][$i]['alumnos'][$a]['nombre_completo'];       
          $result .= '</span>';                 
          $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-2 helvetica-14">';
          $result .= '<div class="text-center">';
          $result .= '<span class="pt-35 pb-35">';          
          $result .= '<form method="post" action="{site_url}index.php/mi-docencia/cursos-detallados" id="form-alumno'.$a.'">';
          $result .= '<input type="hidden" name="XID" value="{XID_HASH}" />';
          $result .= '<input type="hidden" name="codalumno" value="'.$json['Cursos'][$i]['alumnos'][$a]['codigo'].'">';
          $result .= '<input type="hidden" name="cursoid" value="'.$json['Cursos'][$i]['cursoId'].'">';
          $result .= '<input type="hidden" name="nombrealumno" value="'.$json['Cursos'][$i]['alumnos'][$a]['nombre_completo'].'">';
          $result .= '<input type="hidden" name="Flag" value="notas">';
          $result .= '<input type="submit" name="submit" value="Ver Notas">';
          $result .= '</form>'; 
          $result .= '</span>';                 
          $result .= '</div>';
          $result .= '</li>';         
          $result .= '<li class="col-sm-2 helvetica-14">';
          $result .= '<div class="text-center">';
	  			$result .= '<span class="pt-35 pb-35">';
	  			
		      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/InasistenciaProfesor/?Codigo='.$codigo.'&CodAlumno='.$json['Cursos'][$i]['alumnos'][$a]['codigo'].'&Token='.$token;
		      $ch = curl_init($url);
		      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		      curl_setopt($ch, CURLOPT_URL,$url);
		      $result_int=curl_exec($ch);
		      
		      $json_b = json_decode($result_int, true);
		      
		      $result_int = '';
		      
		      //genera el tamano del array
			    $tamano_inas = count($json_b['Inasistencias']); 		      
	  			
	  			for ($b=0; $b<$tamano_inas; $b++) {
		  			if ($json['Cursos'][$i]['cursoId'] == $json_b['Inasistencias'][$b]['CodCurso']) {
			  			$result .= 'Total: '.$json_b['Inasistencias'][$b]['Total'].' Máximo: '.$json_b['Inasistencias'][$b]['Maximo'];
		  			}	
		  		}	
	  			
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
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-12 helvetica-bold-14"><div class="text-center"><span>'.$error_mensaje.'</span></div></li>'; 
        $result .= '</ul>';
        $result .= '</div>';
      }      

      return $result;  
         
    } 
    
    //HORARIOS DE UN PROFESOR 
    public function horario_profesor(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/HorarioProfesor/?Codigo='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);

      $error = $json['CodError'];
      $error_mensaje = $json['MsgError']; 
      
      //limpio la variable para reutilizarla
      $result = '';      
      
      //genera el tamano del array
      $tamano = count($json['HorarioDia']);
      
      //Loop basado en el HorarioDia
      for ($i=0; $i<$tamano; $i++) {
        $result.= '<div class="panel-table">';
        
        //genera el tamano del array
        $tamano_1 = count($json['HorarioDia'][$i]['Clases']);
			 	
        //Despliega solo las clases del dia
         if ($json['HorarioDia'][$i]['CodDia']==date('w')) {

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
	            $result .= '<ul class="tr">';
	            $result .= '<li class="col-sm-2 helvetica-bold-14">';
	            $result .= '<div class="text-center"><span>'.$HoraInicio[$disponibles].':00</span></div>';
	            $result .= '</li>';
	            $result .= '<li class="col-sm-2 helvetica-bold-14">';
	            $result .= '<div class="text-center"><span>'.$Sede[$disponibles].'</span></div>';
	            $result .= '</li>';
	            $result .= '<li class="col-sm-6 helvetica-12">';
	            $result .= '<div><span>'.$CursoNombre[$disponibles].'</span></div>';
	            $result .= '</li>';
	            $result .= '<li class="col-sm-2 helvetica-bold-14">';
	            $result .= '<div class="text-center"><span>'.$Salon[$disponibles].'</span></div>';
	            $result .= '</li>';
	            $result .= '</ul>';    
	            
	            //Controla que ya no recorra mas el arreglo 
	            if ($disponibles != $tamano_2-1) {
	              $disponibles++;
	            } 
            } else {
              $result .= '<ul class="tr">';
              $result .= '<li class="col-sm-2">';
              $result .= '<div class="text-center"><span>'.$b.':00</span></div>';
              $result .= '</li>';                
              $result .= '<li class="col-sm-10">';
              $result .= '<div class="text-center"></div>';
              $result .= '</li>';
              $result .= '</ul>';
            }   
          } 
        } 
        $result .= '</div>'; 
      }
      
      //Control de errores
      if ($error!='00000') {
        $result = '';
        $result .= '<div class="panel-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-12">';
        $result .= '<div>'.$error_mensaje.'</div>';
        $result .= '</li>';                
        $result .= '</ul>';	 
        $result .= '</div>';     
      } 
      
      return $result;              
    }    


    //HORARIO PROFESOR CICLO ACTUAL
    public function horario_profesor_ciclo_actual(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/HorarioProfesor/?Codigo='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      $json = json_decode($result, true);
      
      $error = $json['CodError'];
      $error_mensaje = $json['MsgError'];      
      
      //limpio la variable para reutilizarla
      $result = '';
      
      //genera el tamano del array
      $tamano = count($json['HorarioDia']);  
      
      for ($i=0; $i<$tamano; $i++) {
	      $result .= '<div>';
	      $result .= '<h4>';
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
	      $result .= '</h4>';
	      $result .= '</div>'; 
	      $result .= '<div class="panel-body mb-35">';
        $result .= '<div class="panel-body-head-table bold">'; 
        $result .= '<ul class="tr">'; 
        $result .= '<li class="col-sm-1">'; 
        $result .= '<div class=""><span>Inicio</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-1">'; 
        $result .= '<div class=""><span>Fin</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-7">'; 
        $result .= '<div class=""><span>Clase</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-1">'; 
        $result .= '<div class=""><span>Sede</span></div>'; 
        $result .= '</li>';  
        $result .= '<li class="col-sm-1">'; 
        $result .= '<div class=""><span>Sección</span></div>'; 
        $result .= '</li>'; 
        $result .= '<li class="col-sm-1">'; 
        $result .= '<div class=""><span>Salón</span></div>'; 
        $result .= '</li>';                                                                                                                                                  
        $result .= '</ul>'; 
        $result .= '</div>'; 	      
	      
	      //genera el tamano del array
	      $tamano_int = count($json['HorarioDia'][$i]['Clases']);	
	      
				for ($b=0; $b<$tamano_int; $b++) {
	          $result .= '<div class="panel-table">'; 
            $result .= '<ul class="tr mis-cursos-row">'; 
            $result .= '<li class="col-xs-1 whole-cell-height">'; 
            $result .= '<span>';
            $HoraInicio = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraInicio'], 0, 2);
						$HoraInicio = ltrim($HoraInicio,'0');
						$result .= $HoraInicio.':00';
            $result .='</span>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1 whole-cell-height">'; 
            $result .= '<span>';
            $HoraFin = substr($json['HorarioDia'][$i]['Clases'][$b]['HoraFin'], 0, 2);
						$HoraFin = ltrim($HoraFin,'0');
						$result .= $HoraFin.':00';	                
            $result .='</span>'; 	                
            $result .= '</li>';                        
            $result .= '<li class="col-xs-7 whole-cell-height">'; 
            $result .= '<span>';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['CursoNombre'];
            $result .= '</span>'; 	                
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1 whole-cell-height">'; 
            $result .= '<span>';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Sede'];
            $result .= '</span>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1 whole-cell-height">'; 
            $result .= '<span>';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Seccion'];
            $result .= '</span>'; 
            $result .= '</li>'; 
            $result .= '<li class="col-xs-1 whole-cell-height">'; 
            $result .= '<span>';
            $result .= $json['HorarioDia'][$i]['Clases'][$b]['Salon'];
            $result .= '</span>';  
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
        $result .= '<li class="col-xs-12 whole-cell-height">';
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
    public function lista_alumnos_matriculado_curso_profesor(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $codcurso = ee()->TMPL->fetch_param('codcurso');
      $codalumno = ee()->TMPL->fetch_param('codalumno');      
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/ListadoAlumnosProfesor/?Codigo=PCSIJIPE&Token=2628581ed50d48a7bfd965e6c089ab1420140325103552&Modalidad=FC&Periodo=201400&Curso=IS157&Seccion=CB2B&Grupo=00';
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
      return $result;          
    }      


    //CONSULTA DE NOTAS DE UN ALUMNO POR UN PROFESOR
    public function consulta_notas_alumno_por_un_profesor(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $codalumno = ee()->TMPL->fetch_param('codalumno');
      $codcurso = ee()->TMPL->fetch_param('cursoid');
      $nombrealumno = ee()->TMPL->fetch_param('nombrealumno');     
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/NotaProfesor/?Codigo='.$codigo.'&CodAlumno='.$codalumno.'&CodCurso='.$codcurso.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
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
        $result .= '<li class="col-sm-12">';
        $result .= '<div>';
        $result .= '<span class="helvetica-14 ml-7">FÓRMULA: '.$json['Formula'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>';    
        $result .= '<div class="panel-body-head-table">';
        $result .= '<ul class="tr">';
        $result .= '<li class="col-sm-1">';
        $result .= '<div><span>Tipo</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div><span>Número</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-5">';
        $result .= '<div><span>Evaluación</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div><span>Peso</span></div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div><span>Nota</span></div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</div>'; 
        $result .= '<div class="panel-table">'; 
        $result .= '<ul class="tr">';          

				for ($i=0; $i<$tamano; $i++) {
            
        $result .= '<li class="col-sm-1">';
        $result .= '<div class="text-center">';
        $result .= '<span class="helvetica-14">'.$json['Notas'][$i]['NombreCorto'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div class="text-center">';
        $result .= '<span class="helvetica-14">'.$json['Notas'][$i]['NroEvaluacion'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-5">';
        $result .= '<div>';
        $result .= '<span class="helvetica-14">'.$json['Notas'][$i]['NombreEvaluacion'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div class="text-center">';
        $result .= '<span class="helvetica-14">'.$json['Notas'][$i]['Peso'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-2">';
        $result .= '<div class="text-center">';
        $result .= '<span class="helvetica-bold-14">'.$json['Notas'][$i]['Valor'].'</span>';
        $result .= '</div>';
        $result .= '</li>';
          
        } 
          
	      //Cambia el formato a 2 decimales
	      //$nota = number_format($nota, 2, '.', '');
	        
	      $result .= '</ul>';
	      $result .= '</div>';
	      $result .= '<div class="panel-table observaciones">';
	      $result .= ' <ul class="tr">';
	      $result .= '<li class="col-sm-8">';
	      $result .= '<div>';
	      $result .= '</div>';
	      $result .= '</li>';
	      $result .= '<li class="col-sm-2">';
	      $result .= '<div class="text-center">';
	      $result .= '<span class="helvetica-14 uppercase">Nota al '.$json['PorcentajeAvance'].'</span>';
	      $result .= '</div>';
	      $result .= '</li>';
	      $result .= '<li class="col-sm-2">';
	      $result .= '<div class="text-center">';
	      $result .= '<span class="helvetica-bold-14">'.$json['NotaFinal'].'</span>';
	      $result .= '</div>';
	      $result .= '</li>';
	      $result .= '</ul>';
	      $result .= '</div>';
	      $result .= '</div>';
	      $result .= '</div>';
	      $result .= '<a class="black curso-link text-right" href="#top">';
	      $result .= '<div class="zizou-14 pt-14 mb-35">';
	      $result .= 'Regresar a lista de cursos';
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
        $result .= '<li class="col-xs-12 whole-cell-height">';
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
    public function promedio_notas_alumno_por_curso(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $codcurso = ee()->TMPL->fetch_param('codcurso');
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Nota/?CodAlumno='.$codigo.'&CodCurso='.strval ($codcurso).'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
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
    public function nombre_alumno(){
      // return $_SESSION["Nombres"];
      $names = $_SESSION["Nombres"];
      $names = ucwords(strtolower($names));
      return $names;
    }    
    
    //APELLIDO DEL ALUMNO
    public function apellido_alumno(){    
      return $_SESSION["Apellidos"];
    }  
    
    //CODIGO DEL ALUMNO
    public function codigo_alumno(){    
      return $_SESSION["Codigo"];
    }  
    
    //MODALIDAD DEL ALUMNO
    public function modalidad_alumno(){    
      return $_SESSION["DscModal"];
    }      
    
    //ESTADO DEL ALUMNO
    public function estado_alumno(){ 
      if ($_SESSION["Estado"]=='A') {
        $estado = 'Activo';
      } else {
        $estado = 'Inactivo'; 
      }  
      return $estado;
    }               
    
    //SEDE DEL ALUMNO
    public function sede_alumno(){       
      return $_SESSION["DscSede"];
    }
    
    //CICLO DEL ALUMNO
    public function ciclo_alumno(){ 
      $yyyy = substr($_SESSION["Ciclo"],0,4); 
      $dd = substr($_SESSION["Ciclo"],4,6); 
      return $dd.'-'.$yyyy;     
    }
        
    //MUESTRA EL TIPO DE USUARIO
    public function tipo_usuario(){
      return $_SESSION["TipoUser"];
    }          
    
    //CALENDARIO DE CUOTAS TIPO DE USUARIOS
    public function calendario_cuotas_tipo_usuario(){
      $codigo = $_SESSION["Codigo"];
      $TipoUser = $_SESSION["TipoUser"];
      
      $result = '';
      
      if (strval($TipoUser)=='ALUMNO') {
        /*$result .= '{exp:channel:entries channel="calendario_pagos" limit="10" disable="member_data|pagination" category="20" dynamic="off" orderby="numero-cuota" sort="asc" }';
        $result .= '<ul class="tr bg-muted">';
        $result .= '<li class="col-sm-4 text-center helvetica-bold-12">';
        $result .= '<div>';
        $result .= '<span>{numero-cuota}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-4 helvetica-bold-12">';
        $result .= '<div class="text-center">';
        $result .= '<span>{emitida-cuota format="%d/%m/%Y"}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-4 helvetica-bold-12">';
        $result .= '<div class="text-center">';
        $result .= '<span>{vence-cuota format="%d/%m/%Y"}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '{/exp:channel:entries}';  
        return $result; */
        redirect('/dashboard/estudiante');    
      }
      
      if (strval($TipoUser)=='PROFESOR') {
        $result .= '{exp:channel:entries channel="calendario_pagos" limit="10" disable="member_data|pagination" category="19" dynamic="off" orderby="numero-cuota" sort="asc" }';
        $result .= '<ul class="tr bg-muted">';
        $result .= '<li class="col-sm-4 text-center helvetica-bold-12">';
        $result .= '<div>';
        $result .= '<span>{numero-cuota}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-4 helvetica-bold-12">';
        $result .= '<div class="text-center">';
        $result .= '<span>{emitida-cuota format="%d/%m/%Y"}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-4 helvetica-bold-12">';
        $result .= '<div class="text-center">';
        $result .= '<span>{vence-cuota format="%d/%m/%Y"}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '{/exp:channel:entries}'; 
        return $result;             
      }
      
      if (strval($TipoUser)=='PADRE') {
        $result .= '{exp:channel:entries channel="calendario_pagos" limit="10" disable="member_data|pagination" category="21" dynamic="off" orderby="numero-cuota" sort="asc" }';
        $result .= '<ul class="tr bg-muted">';
        $result .= '<li class="col-sm-4 text-center helvetica-bold-12">';
        $result .= '<div>';
        $result .= '<span>{numero-cuota}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-4 helvetica-bold-12">';
        $result .= '<div class="text-center">';
        $result .= '<span>{emitida-cuota format="%d/%m/%Y"}</span>';
        $result .= '</div>';
        $result .= '</li>';
        $result .= '<li class="col-sm-4 helvetica-bold-12">';
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
    public function mensaje_error(){
      return $_SESSION["MsgError"];
    }    
    
    //INICIAR SESION
    public function iniciar_session() {
      session_start();
    }
    
    //INICIAR SESION
    public function verificar_usuario() {
	    $token = $_SESSION["Token"];
	    $redireccion = current_url();
	    $_SESSION["Redireccion"] = $redireccion;
	    
      if ($token=='') {
	      redirect('/login/no-es-usuario');
      } 
    }    
    
    //BOTON INICIO
    public function boton_inicio() {
      if (strval($_SESSION["TipoUser"])=='ALUMNO') {
        return '{site_url}dashboard/estudiante'; 
      } 
      if (strval($_SESSION["TipoUser"])=='PROFESOR') {
        return '{site_url}dashboard/estudiante'; 
      } 
      if (strval($_SESSION["TipoUser"])=='PADRE') {
        return '{site_url}dashboard/estudiante'; 
      }             
    }    
    
    //DESTRUYE LA SESSION
    public function destruir_session () {
      session_start();
      $_SESSION["Token"] = "";
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
      var_dump($_SESSION);
      session_destroy();
      redirect('/');
    }
    
        
}

/* End of file pi.webservices.php */
/* Location: ./system/expressionengine/third_party/infhotel/pi.webservices.php */
?>