<?php
require 'vendor/autoload.php';

use Goutte\Client;
$url_password = 'http://intranet.upc.edu.pe/LoginIntranet/ResetPassword.aspx';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(501);
    die(-1);
}
### Only Dev
#$whoops = new \Whoops\Run;
#$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
#$whoops->register();
###

$client = new Client();

$codigo = filter_var($_POST['codigo'], FILTER_SANITIZE_STRING);
$correo = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);

if (!$codigo) {
    http_response_code(501);
    die(-2);
}

if (!$correo) {
    http_response_code(501);
    die(-3);
}

$entrada = [
    'ctl00$ContentPlaceHolder1$txtCorreo' => $correo,
    'ctl00$ContentPlaceHolder1$txtUsuario' => $codigo
];

$crawler = $client->request('GET', $url_password);

$form = $crawler->selectButton('Continuar')->form();
$data = $form->getValues();
unset($data['ctl00$ContentPlaceHolder1$txtClaveDinamica']);

$data = $entrada + $data;

$crawler = $client->submit($form, $data);

### Posibles impresiones:
# OK: En unos momentos le llegará su nueva contraseña a su correo personal.
# KO: La dirección de correo ingresada no coincide con la registrada.
# KO: La cuenta de usuario no existe.
###
echo $crawler->filter('#ctl00_ContentPlaceHolder1_lblError2')->text();
