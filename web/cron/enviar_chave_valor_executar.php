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

    $linkAtual   = 'https://adx.naveads.com/adx/nave_chave_valor.php';
    $linkCopiado = 'https://gestor.naveads.com/ajax.php?salvaGestaoUtmsCopias';

    $ch = curl_init();
                                        
    curl_setopt($ch, CURLOPT_URL, $linkAtual);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                                        
    $output = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($output, true);
    $json = array_filter($json);

    if (isset($json['arquivo'])) {
        if (is_file($pasta . $json['arquivo']))
            unlink($pasta . $json['arquivo']);

        $ch = curl_init();
                                
        $params = array(
            'itens'      => $json['chave_valor'],
            'analyticID' => $json['analyticID'],
            'tipo'       => $json['linkTipo']
        );
                                            
        $postData = http_build_query($params, '', '&');
                                            
        $ch = curl_init();
                                            
        curl_setopt($ch, CURLOPT_URL, $linkCopiado);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                                            
        $output = curl_exec($ch);
        curl_close($ch);

        echo 'aplicado<br />';
        
        pre($json);
    }
}