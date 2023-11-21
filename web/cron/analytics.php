<?php
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$query = mysqli_query($con, "SELECT *
    FROM analytics
        INNER JOIN contas ON contaID = _contaID
    LIMIT 100;");

if ($query) {
    while ($itemValor = mysqli_fetch_array($query)) { 
        $analyticID       = $itemValor['analyticID'];
        $analyticContaID  = $itemValor['analyticContaID'];
        $contaAccessToken = $itemValor['contaAccessToken'];
        
        mysqli_query($con, "DELETE FROM analytics_dados 
            WHERE 
                _analyticID = $analyticID;");
        
        $dados = analyticsDados($analyticContaID, $contaAccessToken);
        $json  = $dados['lista'];
        
        $arrTopo  = array();

        if (isset($json['dimensionHeaders'] )) {
            foreach ($json['dimensionHeaders'] as $itemIndex => $itemValor) {
                $arrTopo[] = $itemValor['name'];
            }
        } 

        if (isset($json['metricHeaders']    )) {
            foreach ($json['metricHeaders'] as $itemIndex => $itemValor) {
                $arrTopo[] = $itemValor['name'];
            }
        } 
        
        $arrLinhas = array();
        
        if (isset($json['rows'])) {
            foreach ($json['rows'] as $linhaIndex => $itemValor) { 
                $pos = 0;
                if (isset($itemValor['dimensionValues'] )) {
                    foreach ($itemValor['dimensionValues'] as $itemIndex => $_itemValor) {
                        $arrLinhas[$linhaIndex][$pos] = $_itemValor['value'];
                        
                        $pos++;
                    }
                } 
          
                if (isset($itemValor['metricValues'])) {
                    foreach ($itemValor['metricValues'] as $_itemIndex => $_itemValor) {
                        $arrLinhas[$linhaIndex][$pos] = $_itemValor['value'];
                        
                        $pos++;
                    }
                }
            }
        } 
        
        foreach ($arrLinhas as $arrItens) { 
            $dados = array();

            foreach ($arrItens as $linhaIndex => $linhaValor) {
                
                if ($arrTopo[$linhaIndex] == 'sessionSource') {
                    $dados['item_sessionSource'] = $linhaValor;
                }
                
                if ($arrTopo[$linhaIndex] == 'sessionMedium') {
                    $dados['item_sessionMedium'] = $linhaValor;
                }
                
                if ($arrTopo[$linhaIndex] == 'date') {
                    $linhaValor = substr($linhaValor, 0, 4) . '-' . substr($linhaValor, 4, 2) . '-' . substr($linhaValor, 6, 2);
                    
                    $dados['item_date'] = $linhaValor;
                }
                
                if ($arrTopo[$linhaIndex] == 'totalUsers') {
                    $dados['item_totalUsers'] = $linhaValor;
                }
                
                if ($arrTopo[$linhaIndex] == 'newUsers') {
                    $dados['item_newUsers'] = $linhaValor;
                }
                
                if ($arrTopo[$linhaIndex] == 'screenPageViewsPerSession') {
                    $linhaValor = round($linhaValor, 3);
                    
                    $dados['item_screenPageViewsPerSession'] = $linhaValor;
                }
                
                if ($arrTopo[$linhaIndex] == 'averageSessionDuration') {
                    $linhaValor = round(($linhaValor / 60), 2);
                    $arrTempo   = explode('.', $linhaValor);
                  
                  	$minutos = 0;
                  	if (isset($arrTempo[0]))
                    	$minutos = $arrTempo[0];
                  
                  	$segundos = 0;
                  	if (isset($arrTempo[1]))
                    	$segundos = $arrTempo[1];
                  
                    $dados['item_averageSessionDuration'] = $minutos .'m ' . $segundos .'s';
                }
                
                if ($arrTopo[$linhaIndex] == 'bounceRate') {
                    $linhaValor = str_replace('0.', '', round($linhaValor, 2));
                    
                    $dados['item_bounceRate'] = $linhaValor;
                }
                
                if ($arrTopo[$linhaIndex] == 'publisherAdImpressions') {
                    $dados['item_publisherAdImpressions'] = $linhaValor;
                }
                
                if ($arrTopo[$linhaIndex] == 'publisherAdClicks') {
                    $dados['item_publisherAdClicks'] = $linhaValor;
                }
                
                if ($arrTopo[$linhaIndex] == 'totalAdRevenue') {
                    $linhaValor = round($linhaValor, 3);
                    
                    $dados['item_totalAdRevenue'] = $linhaValor;
                }
            }
            
            $dados['itemCriadoEm'] = date('Y-m-d');
            $dados['_analyticID'] = $analyticID;
            
            $retorno = insert('analytics_dados', $dados);
          	if (!$retorno) 
              	echo 'ERRO ' . mysqli_error($con) . '<br />';
        }
    }
}

echo 'Finalizado';