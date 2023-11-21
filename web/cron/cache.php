<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$horaAtual = date('H');

$impostoPorcentagem = getConfig('imposto_porcentagem');
if (empty($impostoPorcentagem))
    $impostoPorcentagem = 10;
    
$dolarHoje = getConfig('dolar_valor');

$arrDatas = array(
    date('Y-m-d'),
  	date('Y-m-d', strtotime('-1 day'))
);

if ($horaAtual < 5)
    $arrDatas[] = date('Y-m-d', strtotime('-1 day'));
    
$pasta    = ABSPATH . 'cron/arquivos/cache/';
$arquivos = glob($pasta . '*.txt');

$total         = 0;
$totalArquivos = 100;

$_totalArquivos = (int) getConfig('cron_cache_execucoes');
if ($_totalArquivos > 0)
    $totalArquivos = $_totalArquivos;

foreach ($arquivos as $arquivoValor) {
    if (preg_match('/campanha-(\d+)\.txt/', $arquivoValor)) {
        $total = $total + 1;
    }
}
 
if ($total > 0) {
    echo 'parar';
    
} else {
 
    /*   
    for ($x = 1; $x < 31; $x++) {
        $arrDatas[] = '2023-09-' . str_pad($x, 2, '0', STR_PAD_LEFT);;
    } */
        
    $_arrUtm = array(); 
                
    $query = mysqli_query($con, "SELECT *
        FROM clientes
        LIMIT 100;");
    
    if ($query) {
        while ($lista = mysqli_fetch_array($query)) { 
            $clienteUtmTerm = $lista['clienteUtmTerm'];
            $clienteID      = $lista['clienteID'];
            
            $arrUtm = explode(',', $clienteUtmTerm);
            $arrUtm = array_filter($arrUtm);
            
            foreach ($arrUtm as $utmValor) {
                $_arrUtm[$clienteID][] = trim($utmValor);
            }
        }
    }
    
    file_put_contents($pasta . 'utms.txt', json_encode($_arrUtm));
    
    $arrAplicar = array();
    
    foreach ($arrDatas as $dataValor) {
    
        $itens = mysqli_query($con, "SELECT *
            FROM adx_relatorios 
            WHERE 
                relatorioData    = '$dataValor' AND 
                (relatorioUtmTipo = 'campaign_id' OR relatorioUtmTipo = 'adset_id' OR relatorioUtmTipo = 'ad_id') 
            LIMIT 10000");
                
        if ($itens) {
            $total = mysqli_num_rows($itens);
            
            $posicao = 1;
            
            while ($itemValor = mysqli_fetch_array($itens)) {
                $relatorioID       = $itemValor['relatorioID'];
                $relatorioUtmValor = $itemValor['relatorioUtmValor'];
                $relatorioUtmTipo  = $itemValor['relatorioUtmTipo'];
                
                $arrAplicar[$posicao][] = array(
                    'dataValor'   => $dataValor,
                    'relatorioID' => $relatorioID
                );
                    
                if (count($arrAplicar[$posicao]) == $totalArquivos)
                    $posicao++;
            }
        }
    }
    
    foreach ($arrAplicar as $aplicarValor) {
        file_put_contents($pasta . 'campanha-' . rand(10000, 99999) . '.txt', json_encode($aplicarValor));  
    }
}