<?php
header("Access-Control-Allow-Origin: *");

set_time_limit(0);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$pasta    = ABSPATH . 'cron/arquivos/';
$arquivos = glob($pasta . '*.txt');
$total    = 0;

$tempoInicio = strtotime('now');

if (isset($_GET['conta'])) {
    $contaNome = $_GET['conta'];
    
    foreach ($arquivos as $arquivoValor) {
        if (preg_match('/criativo-' . $contaNome . '-(\d+)\.txt/', $arquivoValor)) {
            $total = $total + 1;
        }
    }
        
    if ($total == 0) {
        $arrPais = arrPaisSiglaNome();
        
        file_put_contents('executar/contador.txt', 0);
        
        $arr           = array();
        $totalArquivos = 0;
        
        $dataHoje  = date('Y-m-d');
        $dataOntem = date('Y-m-d', strtotime('-1 day'));
        
        $contas = mysqli_query($con, "SELECT *
        	FROM facebook_itens A
        	    INNER JOIN facebook_conta_itens ON itemNome = itemContaNome
        	    INNER JOIN facebook_contas      ON contaID  = _contaID 
            WHERE 
                contaNome = '$contaNome' AND 
                (itemData  = '$dataHoje' OR itemData  = '$dataOntem')
            GROUP BY itemContaID");
        	
        if ($contas) {
            while ($itemValor = mysqli_fetch_array($contas)) { 
                
                $itemContaID = $itemValor['itemContaID']; 
                $contaToken  = $itemValor['contaToken']; 
                $contaTokens = $itemValor['contaTokens']; 
                
                $contaTokens = (array) json_decode($contaTokens, true);
                $contaTokens = array_filter($contaTokens);
                
                $arrTokens = array();
                foreach ($contaTokens as $tokenValor) {
                    $arrTokens[] = $tokenValor['token'];
                }
                
                $arrTokens[] = $contaToken;
                
                shuffle($arrTokens);
                
                $contaToken = $arrTokens[0];
                
                $data = array(
                    'contaID' => $itemContaID,
                    'token'   => $contaToken
                );
                
                $arrItens = array();
                
                $campanhaLink = 'https://graph.facebook.com/v17.0/act_' . $itemContaID . '/ads?fields=preview_shareable_link,id,name,campaign_id,status,adset_id&access_token=' . $contaToken . '&limit=1000';
                
                $continua = true;
                
                do {
                    $campanhaHtml = file_get_contents($campanhaLink); 
                    $campanhaJson = (array) json_decode($campanhaHtml, true);
                    $campanhaJson = array_filter($campanhaJson);
                    
                    if (isset($campanhaJson['data'])) {
                        $itens = $campanhaJson['data'];
                        
                        foreach ($itens as $_itemValor) {
                            $arrItens[] = $_itemValor;
                        }
                        
                        if (isset($campanhaJson['paging']['next'])) {
                            $campanhaLink = $campanhaJson['paging']['next'];
                            
                        } else {
                            $continua = false;
                        }
                        
                    } else {
                        $continua = false;
                    }
                    
                } while ($continua);
                
                if (count($arrItens) > 0)
                    $data['itens'] = $arrItens;
                
                $arr[$totalArquivos][] = $data;
                
                if (count($arr[$totalArquivos]) == 1)
                    $totalArquivos++;
            }
        }
        
        foreach ($arr as $itemValor) {
            file_put_contents($pasta . 'criativo-' . $contaNome . '-' . rand(1000, 9999) . '.txt', json_encode($itemValor));
        }
        
        $tempoFinal = strtotime('now');
    
        echo 'Tempo: ' . ($tempoFinal - $tempoInicio) . 's';
    } else {
        echo 'parar';
    }
}
