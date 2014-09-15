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
    // END

    public function token_alumno_soap(){
        include_once 'nusoap/lib/nusoap.php';
        $username= ee()->TMPL->fetch_param('username');
        //$username= "pellanoire";
        $id_curse= ee()->TMPL->fetch_param('id_curse');
        //instantiate the NuSOAP class and define the web service URL:
        $client = new nusoap_client('http://miscursosucb.belcorp.biz/auth/belcorpws/belcorpws_server.php?wsdl', 'WSDL');
        //check if there were any instantiation errors, and if so stop execution with an error message:
        $error = $client->getError();
        if ($error) {
          die("client construction error: {$error}\n");
        }
        $param = array('username' => $username);
        //perform a function call without parameters:
        $answer = $client->call('login_usuario', $param);
        //check if there were any call errors, and if so stop execution with some error messages:
        $error = $client->getError();
        if ($error) {
          print_r($client->response);
          print_r($client->getDebug());
          die();
        }
        $url='http://miscursosucb.belcorp.biz/auth/belcorpws/client/client.php?usuario='.$username.'&token='.$answer.'&curso='.$id_curse;
        //output the response (in the form of a multidimensional array) from the function call:
        return '{exp:redirecturl url="'.$url.'"}';

    }

    public function token_alumno_curl(){
        $form = '';
        $url = 'https://upcmovil.upc.edu.pe/upcmovil1/UPCMobile.svc/Autenticar2/?Codigo=u201421481&Contrasena=u201421481&Plataforma=C';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
        return $result;
        //$data = json_decode($result, true);
    }

/* End of file pi.webservices.php */
/* Location: ./system/expressionengine/third_party/infhotel/pi.webservices.php */