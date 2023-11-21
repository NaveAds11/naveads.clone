<?php 
header("Access-Control-Allow-Origin: *");

include('../../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$aplicados = array();

$arrDias = array(
    date('Y-m-d', strtotime('-1 day')),
    //date('Y-m-d', strtotime('-2 day')),
    //date('Y-m-d', strtotime('-3 day')),
    //date('Y-m-d', strtotime('-4 day'))
);

$arrItens = array();
        
$query = mysqli_query($con, "SELECT *
    FROM analytics
        INNER JOIN contas ON contaID = _contaID
    LIMIT 100;");

if ($query) {
    while ($itemValor = mysqli_fetch_array($query)) { 
        
        $arrItens[] = array(
            'analyticID'       => $itemValor['analyticID'],
            'analyticContaID'  => $itemValor['analyticContaID'],
            'contaAccessToken' => $itemValor['contaAccessToken'],
            'datas'            => $arrDias
        );
    }
}



foreach ($arrItens as $itemIndex => $itemValor) {

    $analyticID       = $itemValor['analyticID'];
    $analyticContaID  = $itemValor['analyticContaID'];
    $contaAccessToken = $itemValor['contaAccessToken'];
    $arrDias          = $itemValor['datas'];
    
    $arrDias = (array) $arrDias;
    
    if (count($arrDias) == 0)
        continue;    
    
    foreach ($arrDias as $diaIndex => $diaValor) {
        $aplicados[$analyticID][] = $diaValor;
        
        unset($arrDias[$diaIndex]);
        
        $dados = analyticsPais($analyticContaID, $contaAccessToken, $diaValor, $diaValor);
        $json  = $dados['lista'];
        
        mysqli_query($con, "DELETE FROM analytics_pais
            WHERE 
                pais_date   = '$diaValor' AND
                _analyticID = $analyticID;");
                
        $arrTopo = array();
        
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
                        $campoNome = $arrTopo[$pos];
                        
                        $arrLinhas[$linhaIndex][$campoNome] = $_itemValor['value'];
                        
                        $pos++;
                    }
                } 
          
                if (isset($itemValor['metricValues'])) {
                    foreach ($itemValor['metricValues'] as $_itemIndex => $_itemValor) {
                        $campoNome = $arrTopo[$pos];
                        
                        $arrLinhas[$linhaIndex][$campoNome] = $_itemValor['value'];
                        
                        $pos++;
                    }
                }
            }
        } 

        $posicao      = 1;
        $tabelaCampos = '';
        $arrInserts   = array();
        
        foreach ($arrLinhas as $arrItens) { 
            $dados = array();
            
            $paisID   = 0;
            $paisData = '';
            $paisNome = '';

            foreach ($arrItens as $linhaIndex => $linhaValor) {
                $firstUserCampaignName = $linhaValor['firstUserCampaignName'];
                
                if ($linhaIndex == 'date') {
                    $paisData = $linhaValor;
                    
                    $linhaValor = substr($linhaValor, 0, 4) . '-' . substr($linhaValor, 4, 2) . '-' . substr($linhaValor, 6, 2);
                }
                
                if ($linhaIndex == 'firstUserCampaignName')
                    $paisNome = $linhaValor;
                    
                if ($linhaIndex == 'screenPageViewsPerSession')
                    $linhaValor = round($linhaValor, 2);
                    
                if ($linhaIndex == 'bounceRate')
                    $linhaValor = str_replace('0.', '', round($linhaValor, 2));
                    
                if ($linhaIndex == 'averageSessionDuration') {
                    $linhaValor = round(($linhaValor / 60), 2);
                    $arrTempo   = explode('.', $linhaValor);
                    
                    $linhaValor = $arrTempo[0] .'m ' . $arrTempo[1] .'s';
                }
                
                if ($linhaIndex == 'advertiserAdCostPerClick')
                    $linhaValor = round($linhaValor, 2);
                    
                if ($linhaIndex == 'totalAdRevenue')
                    $linhaValor = round($linhaValor, 2);
                    
                $dados['pais_' . $linhaIndex] = $linhaValor;
            }
            
            $dados['paisCriadoEm'] = date('Y-m-d');
            $dados['_analyticID']  = $analyticID;
            
            $arrInserts[$posicao][] =  "('" . implode("', '", $dados) . "')";

            if (count($arrInserts[$posicao]) > 300)
                $posicao++;

            if (count($arrCampos) == 0)
                $tabelaCampos = '(' . implode(', ', array_keys($dados)) . ')';
        }
        
        foreach ($arrInserts as $insertValor) {
            $sql = "INSERT INTO 
                analytics_pais " . $tabelaCampos . "
            VALUES
                " . implode(', ', $insertValor);

            mysqli_query($con, $sql);
        }
    }
}

echo 'Finalizado';