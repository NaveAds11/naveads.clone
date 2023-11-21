<?php 
header("Access-Control-Allow-Origin: *");

set_time_limit(0);

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$pasta    = ABSPATH . 'cron/arquivos/chave_valor/';
$arquivos = glob($pasta . '*.txt');

$total = count($arquivos);
if ($total == 0) {
    echo 'parar';
} else {

    $arrDados = array();
    foreach ($arquivos as $arquivo) {
        if (is_file($arquivo)) {
            $html = file_get_contents($arquivo);
            $json = (array) json_decode($html, true);
            $json = array_filter($json);
        
            $arrDados[] = $json;
        }
    }
    
    echo json_encode($arrDados);
}