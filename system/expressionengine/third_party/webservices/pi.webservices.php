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

		//CONSTRUCTOR DE SESIONES DE ACURDO AL USUARIO
    public function consultar_alumno(){
      $codigo = $_SESSION["Codigo"];
      $TipoUser = $_SESSION["TipoUser"];
      
      $result = '';
      
      if (strval($TipoUser)=='ALUMNO') {
	      $result .= '<ul class="tr pb-7">';
	        $result .= '<li class="col-sm-2"></li>';
	        $result .= '<li class="col-sm-8 bg-muted">';
	          $result .= '<div>';
	            $result .= '<span class="zizou-14">';
	            $result .= '<a href="/dashboard/estudiante">';
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
	            $result .= '<a href="/dashboard/docente">';
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
	            $result .= '<a href="/dashboard/padre">';
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
      
      //limpio la variable para reutilizarla
      $result = '';      
      
      //genera el tamano del array
      $tamano = count($json['HorarioDia']);
      // var_dump($json);
      
			 // $result.= '<div class="panel-body-head">';
			 // 	if (date('N') == 1) {
			 // 		$result.= '<h5>Lunes</h5>';
			 // 	}
			 // 	if (date('N') == 2) {
			 // 		$result.= '<h5>Martes</h5>';
			 // 	}
			 // 	if (date('N') == 3) {
			 // 		$result.= '<h5>Miércoles</h5>';
			 // 	}
			 // 	if (date('N') == 4) {
			 // 		$result.= '<h5>Jueves</h5>';
			 // 	}
			 // 	if (date('N') == 5) {
			 // 		$result.= '<h5>Viernes</h5>';
			 // 	}
			 // 	if (date('N') == 6) {
			 // 		$result.= '<h5>Sábado</h5>';
			 // 	}
			 // 	if (date('N') == 7) {
			 // 		$result.= '<h5>Domingo</h5>';
			 // 	}																								
			 // $result.= '</div>';
			
			//Loop basado en el HorarioDia
			for ($i=0; $i<$tamano; $i++) {
				$result.= '<div class="panel-table">';
				
				//genera el tamano del array
				$tamano_1 = count($json['HorarioDia'][$i]['Clases']);
				
				//Despliega solo las clases del dia
				// if ($json['HorarioDia'][$i]['CodDia'] == date('N')) {
        if ($json['HorarioDia'][$i]['CodDia'] == 1) {
					
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
									
									$result.= '<ul class="tr">';
										$result.= '<li class="col-sm-2 helvetica-bold-14">';
                    $result.= '<div class="text-center"><span>'.$HoraInicio[$disponibles].':00</span></div>';
										$result.= '</li>';
										$result.= '<li class="col-sm-2 helvetica-bold-14">';
											$result.= '<div class="text-center"><span>'.$Sede[$disponibles].'</span></div>';
										$result.= '</li>';
										$result.= '<li class="col-sm-6 helvetica-12">';
											$result.= '<div><span>'.$CursoNombre[$disponibles].'</span></div>';
										$result.= '</li>';
										$result.= '<li class="col-sm-2 helvetica-bold-14">';
											$result.= '<div class="text-center"><span>'.$Salon[$disponibles].'</span></div>';
										$result.= '</li>';
									$result.= '</ul>';		
									
									//Controla que ya no recorra m[as el arreglo 
									if ($disponibles != $tamano_2-1) {
										$disponibles++;
									}
							
						}	else {
								$result.= '<ul class="tr">';
									$result.= '<li class="col-sm-2">';
										$result.= '<div class="text-center"><span>'.$b.':00</span></div>';
									$result.= '</li>';								
									$result.= '<li class="col-sm-10">';
										$result.= '<div class="text-center"></div>';
									$result.= '</li>';
								$result.= '</ul>';
						}		
					}	
					
				} else {
					$result.= '<ul class="tr">';
						$result.= '<li class="col-sm-12">';
							//$result.= '<div class="text-center">No hay clases</div>';
						$result.= '</li>';								
					$result.= '</ul>';
					$tamano = $i;
				}	
				$result.= '</div>';	
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
                //$result .= '<li class="col-sm-2">';
                  //$result .= '<div class="text-center">';
                    //$result .= '<span class="helvetica-12">03 créditos[falta]</span>';
                  //$result .= '</div>';
                //$result .= '</li>';
                //$result .= '<li class="col-sm-1">';
                  //$result .= '<div class="text-center">';
                    //$result .= '<span class="helvetica-12">11.5[falta]</span>';
                  //$result .= '</div>';
                //$result .= '</li>';
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
                    //$result .= '<span class="uppercase helvetica-14">Observaciones:</span>';
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
          $result .= '<div class="zizou-14 pt-14 mb-35">Regresar a lista de cursos <img src="{site_url}assets/img/black_arrow_tiny_up.png" alt=""></div>';
        $result .= '</a>';          

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
	      $result .= 'Actualmente no tienes trámites en proceso.';
	      $result .= '</span>';
      } else {
        /*
                <div class="panel-body-head-table">
                  <ul class="tr">
                    <li class="col-sm-2">
                      <div class="fecha"><span>Solicitud</span></div>
                    </li>
                    <li class="col-sm-6">
                      <div class=""><span>Descripción del Trámite</span></div>
                    </li>
                    <li class="col-sm-2">
                      <div class=""><span>Fecha de Inicio</span></div>
                    </li>
                    <li class="col-sm-2">
                      <div class=""><span>Estado</span></div>
                    </li>
                  </ul>
                </div>
                <div class="panel-table"> 
                  <ul class="tr">
                    <li class="col-sm-2 solano-bold-18 uppercase">
                      <div class="text-center">
                        <span>PC</span>
                      </div>
                    </li>
                    <li class="col-sm-6 helvetica-14">
                      <div>
                        <span>Solicitud de Servicios</span>
                      </div>
                    </li>
                    <li class="col-sm-2 solano-bold-18 uppercase">
                      <div class="text-center nrb">
                        <span>12/08/1990</span>
                      </div>
                    </li>
                    <li class="col-sm-2 pdte-tr solano-bold-18">
                      <div class="text-center">
                        <span>Pendiente</span>
                      </div>
                    </li>
                  </ul>
                  <ul class="tr">
                    <li class="col-sm-2 solano-bold-18 uppercase">
                      <div class="text-center">
                        <span>PC</span>
                      </div>
                    </li>
                    <li class="col-sm-6 helvetica-14">
                      <div>
                        <span>Solicitud de Servicios</span>
                      </div>
                    </li>
                    <li class="col-sm-2 solano-bold-18 uppercase">
                      <div class="text-center nrb">
                        <span>12/08/1990</span>
                      </div>
                    </li>
                    <li class="col-sm-2 apr-tr solano-bold-18">
                      <div class="text-center">
                        <span>Aprobado</span>
                      </div>
                    </li>
                  </ul>
                </div>	        
	        
        */	      
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
      
      if ($json['CodError']=='00041') {
	      $result = '<p>'.$json['MsgError'].'</p>'; 
      } else {
      	//limpio la variable para reutilizarla
				$result = ''; 
        $result .= '<h3 class="monto">S/.'.$json['PagosPendientes'][0]['Total'].'</h3>';
        $result .= '<p><span class="vcto">Vence el '.$json['PagosPendientes'][0]['FecVencimiento'].'</span></p>';
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
    
    //POBLAR ESPACIOS DEPORTIVOS   
    public function poblar_espacios_deportivos(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/PoblarED/?CodAlumno='.$codigo.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
      return $result;          
    } 
    
    //DISPONIBILIDAD DE ESPACIOS DEPORTIVOS   
    public function disponibilidad_espacios_deportivos(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $codsede = ee()->TMPL->fetch_param('codsede');
      $coded = ee()->TMPL->fetch_param('coded');
      $numhoras = ee()->TMPL->fetch_param('numhoras');
      $fechaini = ee()->TMPL->fetch_param('fechaini');
			$fechafin = ee()->TMPL->fetch_param('fechafin');
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/DisponibilidadED/?CodAlumno='.$codigo.'&Token='.$token.'CodSede='.$codsede.'&CodED='.$coded.'&NumHoras='.$numhoras.'&FechaIni='.$fechaini.'&FechaFin='.$fechafin;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
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
      $horaini = ee()->TMPL->fetch_param('horaini');
      $horafin = ee()->TMPL->fetch_param('horafin');
      $detalles = ee()->TMPL->fetch_param('detalles');

      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/ReservarED/?CodSede='.$codsede.'&CodED='.$coded.'&CodActiv='.$codactiv.'&NumHoras='.$numhoras.'&CodAlumno='.$codigo.'&Fecha='.$fecha.'&HoraIni='.$horaini.'&HoraFin='.$horafin.'&Detalles='.$detalles.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
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
      
      return $result;          
    }    
    
    //CONSULTA DE NOTAS DE UN ALUMNO POR UN PROFESOR
    public function consulta_notas_alumno_por_un_profesor(){
      $codigo = $_SESSION["Codigo"];
      $token = $_SESSION["Token"];
      $codcurso = ee()->TMPL->fetch_param('codcurso');
      $codalumno = ee()->TMPL->fetch_param('codalumno');      
      
      $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/NotaProfesor/?Codigo='.$codigo.'&CodAlumno='.$codalumno.'&CodCurso='.$codcurso.'&Token='.$token;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      
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
        $result .= '{exp:channel:entries channel="calendario_pagos" limit="10" disable="member_data|pagination" category="20" dynamic="off" orderby="numero-cuota" sort="asc" }';
        $result .= '<ul class="tr bg-muted">';
          $result .= '<li class="col-sm-4 text-center helvetica-bold-14">';
            $result .= '<div>';
              $result .= '<span>{numero-cuota}</span>';
            $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-4 helvetica-bold-14">';
            $result .= '<div class="text-center">';
              $result .= '<span>{emitida-cuota format="%d/%m/%Y"}</span>';
            $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-4 helvetica-bold-14">';
            $result .= '<div class="text-center">';
              $result .= '<span>{vence-cuota format="%d/%m/%Y"}</span>';
            $result .= '</div>';
          $result .= '</li>';
        $result .= '</ul>';
        $result .= '{/exp:channel:entries}';  
	      return $result;   	
      }
			
      if (strval($TipoUser)=='PROFESOR') {
        $result .= '{exp:channel:entries channel="calendario_pagos" limit="10" disable="member_data|pagination" category="19" dynamic="off" orderby="numero-cuota" sort="asc" }';
        $result .= '<ul class="tr bg-muted">';
          $result .= '<li class="col-sm-4 text-center helvetica-bold-14">';
            $result .= '<div>';
              $result .= '<span>{numero-cuota}</span>';
            $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-4 helvetica-bold-14">';
            $result .= '<div class="text-center">';
              $result .= '<span>{emitida-cuota format="%d/%m/%Y"}</span>';
            $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-4 helvetica-bold-14">';
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
          $result .= '<li class="col-sm-4 text-center helvetica-bold-14">';
            $result .= '<div>';
              $result .= '<span>{numero-cuota}</span>';
            $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-4 helvetica-bold-14">';
            $result .= '<div class="text-center">';
              $result .= '<span>{emitida-cuota format="%d/%m/%Y"}</span>';
            $result .= '</div>';
          $result .= '</li>';
          $result .= '<li class="col-sm-4 helvetica-bold-14">';
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
    
    public function iniciar_session() {
	    session_start();
    }
    
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
			session_destroy();
			redirect('/');
    }
    
        
}

/* End of file pi.webservices.php */
/* Location: ./system/expressionengine/third_party/infhotel/pi.webservices.php */
?>