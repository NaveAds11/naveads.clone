<?php
header("Access-Control-Allow-Origin: *");

set_time_limit(0);

include('../config.php'); 
include(ABSPATH .'/funcoes.php');

$pasta    = ABSPATH . 'cron/arquivos/campanha_clonar/';
$arquivos = glob($pasta . '*.txt');

if (isset($_POST['accessToken'])) {
	file_put_contents($pasta . 'clonar-' . rand(100000, 999999) . '.txt', json_encode($_POST));	
}