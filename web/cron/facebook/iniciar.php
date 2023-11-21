<?php 
header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$pasta    = ABSPATH . 'cron/facebook/data/';
$arquivos = count(glob($pasta . '*.txt'));

$dataArquivoCriado = date('Y-m-d', filectime('log.txt'));
$dataAtual         = date('Y-m-d');

$execucoes = (int) getConfig('cron_facebook_execucoes');
if ($execucoes == 0)
    $execucoes = 6;
    
if ($arquivos == 0) {
    $arrContas = array();

    $query = mysqli_query($con, "SELECT *
    	FROM facebook_contas A
    	WHERE 
    	    contaStatus = 1
    	ORDER BY contaID DESC;");
    
    if ($query) {
        $total = mysqli_num_rows($query);
        while ($contaValor = mysqli_fetch_array($query)) { 
            $cadastroID  = $contaValor['contaID'];
            $contaToken  = $contaValor['contaToken'];
            $contaTokens = $contaValor['contaTokens']; 
          
            $contaTokens = (array) json_decode($contaTokens, true);
            $contaTokens = array_filter($contaTokens);
            
            $arrTokens = array();
            foreach ($contaTokens as $tokenValor) {
                $arrTokens[] = $tokenValor['token'];
            }
            
            $arrTokens[] = $contaToken;
            
            shuffle($arrTokens);
            
            $contaToken = $arrTokens[0];
            
            if (empty($contaToken))
                continue;
            
            $campos = 'account_name,account_id';
            
            $url      = "https://graph.facebook.com/v17.0/me/adaccounts?access_token=" . $contaToken .'&limit=5000&fields=account_name,account_id,balance,account_status,business_name,amount_spent,name,funding_source_details,age,timezone_name';
            $continua = true;
          	
            do {
                $response = file_get_contents($url);
              
                $data = (array) json_decode($response, true);
                $data = array_filter($data);
              
                if (isset($data['data'])) {
                    foreach ($data['data'] as $itemValor) { 
                        $contaID = $itemValor['account_id'];
                      
                        $balancoValor = 0;
                        if (isset($itemValor['balance'])) {
                            $balancoValor = $itemValor['balance'];
                            $balancoValor = substr($balancoValor, 0, strlen($balancoValor) - 2) . '.' . substr($balancoValor, strlen($balancoValor) - 2, 2);
                        }
                        
                        $custoValor = 0;
                        if (isset($itemValor['amount_spent'])) {
                            $custoValor = $itemValor['amount_spent'];
                            $custoValor = substr($custoValor, 0, strlen($custoValor) - 2) . '.' . substr($custoValor, strlen($custoValor) - 2, 2);
                        }
                        
                        $itemFormaPagamento = '';
                        if (isset($itemValor['funding_source_details']['display_string'])) {
                            $itemFormaPagamento = $itemValor['funding_source_details']['display_string'];
                        }
                        
                        $arrContas[] = array(
                            'conta_id'           => $itemValor['account_id'],
                            'contaNome'          => $itemValor['name'],
                            'itemSaldoPagar'     => $balancoValor,
                            'itemSituacao'       => $itemValor['account_status'],
                            'itemTotalGasto'     => $custoValor,
                            'itemTimezoneName'   => $itemValor['timezone_name'],
                            'itemFormaPagamento' => $itemFormaPagamento,
                            'itemTempoAtivo'     => $itemValor['age'],
                            'token'              => $contaToken,
                            'registroID'         => $cadastroID
                        );
                                              
                        $link = 'https://graph.facebook.com/v17.0/act_' . $contaID . '/ads_volume?access_token=' . $contaToken;
                    }
                    
                    if (isset($data['paging']['next'])) {
                        $url = $data['paging']['next'];
                    } else {
                        $continua = false;
                    }
                    
                } else {
                    $continua = false;
                }
                
            } while ($continua);
        }
    }
    
    $total          = count($arrContas);
    $inserirPosicao = 1;
    $arrInserir     = array();

    foreach ($arrContas as $itemValor) {
        $itemTotalGasto = $itemValor['itemTotalGasto'];
        // if ($itemTotalGasto < 1)
        //    continue;
            
        $contaNome = $itemValor['contaNome'];
        $contaID   = $itemValor['conta_id'];
        
        $data = array(
            'itemNome'           => $itemValor['contaNome'],
            'itemValor'          => $itemValor['conta_id'],
            '_contaID'           => $itemValor['registroID'],
            'itemSaldoPagar'     => $itemValor['itemSaldoPagar'],
            'itemSituacao'       => $itemValor['itemSituacao'],
            'itemTotalGasto'     => $itemValor['itemTotalGasto'],
            'itemFormaPagamento' => $itemValor['itemFormaPagamento'],
            'itemTimezoneName'   => $itemValor['itemTimezoneName'],
            'itemTempoAtivo'     => $itemValor['itemTempoAtivo']
        );
        
      	$__sql = "SELECT *
            FROM facebook_conta_itens 
            WHERE 
                itemNome  = '$contaNome' AND 
                itemValor = '$contaID' ";
      
      	$cadastrar = false;
        $cadastrado = mysqli_query($con, "SELECT *
            FROM facebook_conta_itens 
            WHERE 
                itemNome  = '$contaNome' AND 
                itemValor = '$contaID' ");
                
        if ($cadastrado) {
            if (mysqli_num_rows($cadastrado) == 0)
              	$cadastrar = true;
       	}
              
      	if ($cadastrar) {
          	$arrInserir[$inserirPosicao][] = $data;
          	if (count($arrInserir[$inserirPosicao]) == 300)
            	$inserirPosicao++;

        } else {
          	$contaValor = mysqli_fetch_array($cadastrado);
          	if (isset($contaValor['itemID'])) {
            	$contaID = $contaValor['itemID'];

            	$retorno = update('facebook_conta_itens', $data, 'itemID = ' . $contaID);
            	if (!$retorno)
                  	echo 'ERRO ' . mysqli_error($con) . '<br />';
          	}
        }
    }
    
    foreach ($arrInserir as $arrDados) {
        
        $arrCampos = array();
        foreach ($arrDados as $_arrDados) {
            $itemNome           = $_arrDados['itemNome'];
            $itemValor          = $_arrDados['itemValor'];
            $_contaID           = $_arrDados['_contaID'];
            $itemSaldoPagar     = $_arrDados['itemSaldoPagar'];
            $itemSituacao       = $_arrDados['itemSituacao'];
            $itemTotalGasto     = $_arrDados['itemTotalGasto'];
            $itemFormaPagamento = $_arrDados['itemFormaPagamento'];
            $itemTimezoneName   = $_arrDados['itemTimezoneName'];
            $itemTempoAtivo     = $_arrDados['itemTempoAtivo'];
            
            $arrCampos[] = "('$itemNome', '$itemValor', '$_contaID', '$itemSaldoPagar', '$itemSituacao', '$itemTotalGasto', '$itemFormaPagamento', '$itemTimezoneName', '$itemTempoAtivo')";
        }
        
        $retorno = mysqli_query($con, "INSERT INTO facebook_conta_itens (itemNome, itemValor, _contaID, itemSaldoPagar, itemSituacao, itemTotalGasto, itemFormaPagamento, itemTimezoneName, itemTempoAtivo) 
            VALUES " . implode(', ', $arrCampos));
      
      	if (!$retorno)
          	echo 'ERRO ' . mysqli_error($con) . '<br />';
    }
    
    $html = file_get_contents('log.txt');
    if ($dataArquivoCriado <> $dataAtual)
        $html = '';
        
    if (!empty($html))
        $html .= PHP_EOL . PHP_EOL;
      
    file_put_contents('criativos_contador.txt', 0);  
    file_put_contents('log.txt', $html . 'Iniciado ' . date('d/m/Y H:i')  . ' - Total de Contas: ' . $total . PHP_EOL);
    
    $arr = array();
    
    foreach ($arrContas as $itemIndex => $itemValor) {
        $arr[] = $itemValor;
        
        unset($arrContas[$itemIndex]);
        
        if (count($arr) == $execucoes) {
            file_put_contents($pasta . 'contas-' . rand(1000, 9999) . '.txt', json_encode($arr));
            
            $arr = array();
        }
    }
}