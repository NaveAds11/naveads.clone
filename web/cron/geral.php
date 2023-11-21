<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$arrLinks = array(
    'https://ads.plusbem.com/cron/analytics_pais.php?geral',
    
    'https://ads.plusbem.com/cron/analytics.php?geral&manual&iniciar',
    'https://ads.plusbem.com/cron/analytics.php?geral&manual&executar',
    
    'https://ads.plusbem.com/cron/analytics_links.php?geral&manual&iniciar',
    'https://ads.plusbem.com/cron/analytics_links.php?geral&manual&executar',
    
    'https://ads.plusbem.com/cron/analytics_campanhas.php?geral&iniciar',
    'https://ads.plusbem.com/cron/analytics_campanhas.php?geral&executar',
    
    'https://ads.plusbem.com/cron/analytics_gestor_pais.php?geral&iniciar',
    'https://ads.plusbem.com/cron/analytics_gestor_pais.php?geral&montar',
    'https://ads.plusbem.com/cron/analytics_gestor_pais.php?geral&executar',
    
    'https://ads.plusbem.com/cron/analytics_campanhas_cache.php?geral&iniciar',
    'https://ads.plusbem.com/cron/analytics_campanhas_cache.php?geral&executar'
);

$totalLinks = count($arrLinks);

$arquivoFeitos = ABSPATH . 'data/geral_feitos.txt';
$arquivoData   = '';

if (isset($_GET['limpar'])) {
    file_put_contents($arquivoFeitos, '');
}

$dataAtual = date('Y-m-d');

$data = '';
if (is_file($arquivoData))
    $data = file_get_contents($arquivoData);
    
$feitos = array();
if (is_file($arquivoFeitos)) {
    $feitos = file_get_contents($arquivoFeitos);
    $feitos = (array) json_decode($feitos, true);
    $feitos = array_filter($feitos);
}

$totalFeitos = count($feitos);

echo 'EXECUTANDO CRON GERAL<br />';
echo 'EXECUTADO EM: <strong>' . (empty($data) || ($data <> $dataAtual) ? '-' : $data) . '</strong><br /><br />';

if (($data == $dataAtual)) {
    echo 'FINALIZADO.';   
    
} else {
    echo 'TOTAL: ' . $totalLinks . '<br />';
    echo 'FEITOS: ' . $totalFeitos . '<br /><br />';

    if ($totalFeitos >= $totalLinks) {
        echo 'FINALIZADO.';  
        
        file_put_contents($arquivoFeitos, '');
        file_put_contents($arquivoData,   $dataAtual);
        
    } else {
        
        foreach ($arrLinks as $linkValor) {
            if (!in_array($linkValor, $feitos))
                continue;
                
            echo 'APLICADO: <strong>' . $linkValor . '</strong> <small><i>(Tempo: 0 sec)</i></small><br />';
        }
        
        echo '<br />';
        
        foreach ($arrLinks as $linkValor) {
            if (in_array($linkValor, $feitos))
                continue;
                
            echo 'CRON ATUAL: <strong>' . $linkValor . '</strong> <small><i>(Tempo: 0 sec)</i></small><br />';
            
            $html = file_get_contents($linkValor);
            
            if (preg_match('/finalizado|Iniciando/i', $html))
                $feitos[] = $linkValor;
            
            break;
        }
        
        file_put_contents($arquivoFeitos, json_encode($feitos));
    }
}
