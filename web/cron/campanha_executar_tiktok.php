<?php
header("Access-Control-Allow-Origin: *");

set_time_limit(0);

include('../config.php'); 
include(ABSPATH .'/funcoes.php');

$pastaTiktok    = ABSPATH . 'cron/arquivos/campanhas_tiktok/';
$arquivosTiktok = glob($pastaTiktok . '*.txt');

$arrTiktok = array();

foreach ($arquivosTiktok as $arquivoValor) {
    preg_match('/campanha-(\d+)-(\d+)\.txt/', $arquivoValor, $match);
    if (isset($match[2])) {
        $arrTiktok[] = $arquivoValor;
    }
}

if (count($arrTiktok) == 0) {
    echo 'parar';
    
} else {
    
    foreach ($arrTiktok as $tiktokValor) {
        if (is_file($tiktokValor)) {
            $html = file_get_contents($tiktokValor);
            $json = (array) json_decode($html, true);
            $json = array_filter($json);
            
            $cadastroID = $json['cadastroID'];
            
            unlink($tiktokValor);
            
            $porta = $json['porta'];
            $post  = json_encode($json['post']);
            $link  = 'http://54.37.11.148:' . $porta . '/api/anuncios';
            
            $header = array(
                'Cookies:' . $json['navegador'],
                'ProxyHost:' . $json['host'],
                'ProxyUsuario:' . $json['usuario'],
                'ProxySenha:' . $json['senha'],
                'Content-Type:application/json',
                'Aadvid:' . $json['conta'],
                'Content-Length:' . strlen($post),
            );
            
            $ch = curl_init(); 
        
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            
             
            curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
            
            $output = curl_exec($ch);
            curl_close($ch);
            
            $campanhaID = 0;
            if (isset($json['campanhaId']))
                $campanhaID = $json['campanhaId'];
                
            $data = array(
                'cadastroFila'        => 2,
                'cadastroRetorno'     => $output,
                'cadastroRetornoData' => date('Y-m-d H:i:s'),
                '_campanhaID'         => $campanhaID
            );
            
            update('cliente_campanhas', $data, 'cadastroID = ' . $cadastroID);
            
            break;
        }
    }
}