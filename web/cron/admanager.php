<?php
/*
if (!isset($_GET['teste'])) {
    echo 'parado';
    exit;
} */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$dataAtual = date('Y-m-d');

$pasta    = ABSPATH . 'cron/arquivos/';
$arquivos = glob($pasta . '*.txt');

pre($arquivos);

$arquivoCampos = $pasta . 'campos.txt';

$arrArquivos = array();
$tempoInicio = strtotime('now');

$dataAnterior = date('Y-m-d', strtotime('-1 day ' . $dataAtual));
        
$impostoPorcentagem = getConfig('imposto_porcentagem');
if (empty($impostoPorcentagem))
    $impostoPorcentagem = 10;
    
$dolarHoje = getConfig('dolar_valor');

$cronAdmanagerExecucoes = (int) getConfig('cron_admanager_execucoes');
if (empty($cronAdmanagerExecucoes))
    $cronAdmanagerExecucoes = 200;
    
$campos = array(
    'Dimension.DATE'                                        => 'relatorioData',
    'Dimension.CUSTOM_CRITERIA'                             => 'relatorioUtm',
    'Dimension.CUSTOM_TARGETING_VALUE_ID'                   => 'relatorioTargetID',
    'Column.TOTAL_INVENTORY_LEVEL_UNFILLED_IMPRESSIONS'     => 'relatorioImpressoesNaoPreenchidas',
    'Column.TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'              => 'relatorioImpressoes',
    'Column.TOTAL_LINE_ITEM_LEVEL_CLICKS'                   => 'relatorioCliques',
    'Column.TOTAL_LINE_ITEM_LEVEL_CTR'                      => 'relatorioCTR',
    'Column.TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE'      => 'relatorioReceitaTotal',
    'Column.TOTAL_LINE_ITEM_LEVEL_WITHOUT_CPD_AVERAGE_ECPM' => 'relatorioEcpm'
);

$contaCodigo = '';
if (isset($_GET['conta']))
    $contaCodigo = $_GET['conta'];
    
if (!empty($contaCodigo)) {
    $arrContas = array();
    
    $arquivoInserir = $pasta . 'admanager_inserir_' . $contaCodigo . '.txt';
    if (is_file($arquivoInserir)) {
        file_put_contents($arquivoInserir, '');
    }
    
    $query = mysqli_query($con, "SELECT * FROM 
        adx_contas
        WHERE 
            contaCodigo = '$contaCodigo' ");
            
    if ($query) {
        while ($itemValor = mysqli_fetch_array($query)) {
            $contaNome   = $itemValor['contaNome'];
            $contaCodigo = $itemValor['contaCodigo'];
            
            $arrContas[] = array(
                'nome'   => $contaNome,
                'codigo' => $contaCodigo,
                'total'  => 0
            );
        }
    }
  
    foreach ($arrContas as $itemIndex => $itemValor) {
        $codigo = $itemValor['codigo'];
        
        $total = 0;
        foreach ($arquivos as $arquivoValor) {
            if (preg_match('/admanager-(\d+)-' . $codigo . '\.txt/', $arquivoValor)) {
                $total = $total + 1;
            }    
        }
        
        $arrContas[$itemIndex]['total'] = $total;
    }
  
    $arrDatas = array(
        date('Y-m-d'),
        date('Y-m-d', strtotime('-1 day'))
    );
    
    foreach ($arrContas as $contaValor) {
        $contaNome   = $contaValor['nome'];
        $contaCodigo = $contaValor['codigo'];
        $contaTotal  = $contaValor['total'];
        
        if ($contaTotal == 0) {  
            
            $lista = array();
            
            foreach ($arrDatas as $dataValor) {
            
                $link = 'https://adx.naveads.com/adx/navee.php?data_inicio=' . $dataValor . '&data_final=' . $dataValor . '&conta_id=' . $contaCodigo;
                
                $ch = curl_init();
            
                curl_setopt($ch, CURLOPT_URL, $link);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $retorno = curl_exec($ch);
              
                curl_close($ch);
                
                if (preg_match('/Dimension.DATE/', $retorno)) {
                
                    $json = (array) json_decode($retorno, true);
                    $json = array_filter($json);
                    
                    $htmlCampos = file_get_contents($arquivoCampos);
                    
                    $_campos = array();
                    
                    if (isset($json[0])) {
                        foreach ($json[0] as $itemIndex => $itemValor) {
                            $_campos[$itemIndex] = $campos[$itemValor];
                        }
                        
                        unset($json[0]);
                    }
                    
                    if (empty($htmlCampos) || ($contaCodigo == '22379248166'))
                        file_put_contents($arquivoCampos, json_encode($_campos));
                        
                    foreach ($json as $itemValor) {
                        $lista[] = $itemValor;
                    }
                }
            }
            
            $arr = array();
                
            foreach ($lista as $itemIndex => $itemValor) {
                $arr[] = $itemValor;
                
                unset($lista[$itemIndex]);
                
                if (count($arr) == $cronAdmanagerExecucoes) {
                    file_put_contents($pasta . 'admanager-' . rand(1000, 9999) . '-' . $contaCodigo . '.txt', json_encode($arr));
                    
                    $arr = array();   
                }
            }
        }
        
        break;
    }
    
    $tempoFinal = strtotime('now');
    
    echo 'Tempo de execucao: ' . ($tempoFinal - $tempoInicio) . 's';
}