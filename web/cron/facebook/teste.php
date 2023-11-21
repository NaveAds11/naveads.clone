<?php 
header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$arrContas = array();
    
$query = mysqli_query($con, "SELECT *
	FROM facebook_contas A
	WHERE 
	    contaStatus = 1
	ORDER BY contaID DESC;");

if ($query) {
    $total = mysqli_num_rows($query);
    
    echo 'TOTAL: ' . $total . '<br />';
    
    while ($contaValor = mysqli_fetch_array($query)) { 
        $cadastroID = $contaValor['contaID'];
        $contaToken = $contaValor['contaToken'];
        
        if (empty($contaToken))
            continue;
        
        $campos = 'account_name,account_id';
        
        $url      = "https://graph.facebook.com/v17.0/me/adaccounts?access_token=" . $contaToken .'&fields=account_name,account_id,balance,account_status,business_name,amount_spent,name,funding_source_details,age,timezone_name';
        $continua = true;
        
        do {
            $response = file_get_contents($url);
        
            $data = (array) json_decode($response, true);
            $data = array_filter($data);
        
            if (isset($data['data'])) {
                foreach ($data['data'] as $itemValor) { 
                    $contaID = $itemValor['account_id'];
                    
                    // if ($itemValor['account_id'] <> '647316537309560')
                    //    continue;
                    
                    $balancoValor = $itemValor['balance'];
                    $balancoValor = substr($balancoValor, 0, strlen($balancoValor) - 2) . '.' . substr($balancoValor, strlen($balancoValor) - 2, 2);
                    
                    $custoValor = $itemValor['amount_spent'];
                    $custoValor = substr($custoValor, 0, strlen($custoValor) - 2) . '.' . substr($custoValor, strlen($custoValor) - 2, 2);
                    
                    $arrContas[] = array(
                        'conta_id'           => $itemValor['account_id'],
                        'contaNome'          => $itemValor['name'],
                        'itemSaldoPagar'     => $balancoValor,
                        'itemSituacao'       => $itemValor['account_status'],
                        'itemTotalGasto'     => $custoValor,
                        'itemTimezoneName'   => $itemValor['timezone_name'],
                        'itemFormaPagamento' => $itemValor['funding_source_details']['display_string'],
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


echo '<pre>';
print_r($arrContas);
echo '</pre>';

