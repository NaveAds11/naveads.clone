<?php
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$clienteComissaoValor = 10;
                                
$impostoPorcentagem = getConfig('imposto_porcentagem');
if (empty($impostoPorcentagem))
    $impostoPorcentagem = 10;

$query = mysqli_query($con, "SELECT *
    FROM clientes
    LIMIT 100;");

if ($query) {
    while ($lista = mysqli_fetch_array($query)) { 
        $clienteUtmTerm = $lista['clienteUtmTerm'];
        $clienteNome    = $lista['clienteNome'];
        $clienteID      = $lista['clienteID'];
        
        $arrUtm = explode(',', $clienteUtmTerm);
        $arrUtm = array_filter($arrUtm);
        
        $_arrUtm = array();
        foreach ($arrUtm as $utmValor) {
            $_arrUtm[] = trim($utmValor);
        }

        for ($x = 1; $x < 4; $x++) {
            $dataSelecionada = date('Y-m-d', strtotime('-' . $x . ' day'));
    
            $sql = "SELECT *,
            	campanhas_totalResultados AS gestorPaisResultados,
                campanhas_totalImpressoes AS gestorPaisImpressoes,
                campanhas_totalCPR AS gestorPaisCPR,
                campanhas_totalImpostoValor AS gestorPaisImpostoValor,
                campanhas_totalCustoValor AS gestorPaisCustoValor,
                campanhas_totalAdRevenue AS gestorPais_totalAdRevenue,
                campanhas_totalCustoCliques AS gestorPaisCustoCliques,
                campanhas_totalComissaoValor AS gestorPaisComissaoValor,
                campanhas_totalImposto AS gestorPaisImposto,
                campanhas_totalLucroFinal AS gestorPaisLucroFinal
                        
            FROM `analytics_campanhas` 
            WHERE 
            
               campanha_firstUserManualTerm IN ('" . implode("','", $_arrUtm) . "') AND 
               campanha_date = '$dataSelecionada' AND 
               campanhas_totalCustoValor > 0;";
               
            $campanhasLista = mysqli_query($con, $sql);
            
            if ($campanhasLista) {
                while ($campanhasListaValor = mysqli_fetch_array($campanhasLista)) { 
                    $sessionCampaignName          = $campanhasListaValor['sessionCampaignName'];
                    $campanhaID                   = $campanhasListaValor['campanhaID'];
                    $gestorPais_totalAdRevenue    = $campanhasListaValor['gestorPais_totalAdRevenue'];
                    $gestorPaisComissaoValor      = $campanhasListaValor['gestorPaisComissaoValor'];
                    $campanhaCustoValor           = $campanhasListaValor['gestorPaisCustoValor'];
                    $campanha_sessionCampaignName = $campanhasListaValor['campanha_sessionCampaignName'];
                    
                    $custos = mysqli_query($con, "SELECT *
                        FROM cliente_campanhas_custo
                        WHERE
                            _campanhaID = $campanhaID
                    LIMIT 1;");
                    
                    if ($custos) {
                        $custoItem = mysqli_fetch_array($custos);
                        if (isset($custoItem['campanhaCustoID'])) {
                            $campanhaCustoValor = $custoItem['campanhaCustoValor'];
                        }
                    }

                    echo 'CAMPANHA: ' . $campanha_sessionCampaignName . '<br />';
                    
                    $analiseTotalAdRevenue = (float) $campanhasListaValor['gestorPais_totalAdRevenue'];

                    $lucro = 0.00;
                    if (!empty($campanhaCustoValor))
                        $lucro = $gestorPais_totalAdRevenue - $campanhaCustoValor;
                        
                    $_totalAdRevenue = $gestorPais_totalAdRevenue; 
                    
                    $campanhaImpostoValor = ($_totalAdRevenue / 100) * $impostoPorcentagem;
                    $_totalAdRevenue      = ($_totalAdRevenue - $campanhaImpostoValor) - $campanhaCustoValor;
                    
                    $gestorPaisComissaoValor = $_totalAdRevenue - ($_totalAdRevenue - (($_totalAdRevenue / 100) * $clienteComissaoValor));
                    
                    $gestorPaisLucroFinal = $_totalAdRevenue - $gestorPaisComissaoValor;
                    
                    $data = array(
                    	'campanhas_totalComissaoValor' => $gestorPaisComissaoValor,
                    	'campanhas_totalLucroFinal'    => $gestorPaisLucroFinal,
                    	'campanhas_totalCustoValor'    => $campanhaCustoValor,
                    	'_clienteID'                   => $clienteID
                   	);
                   	
                   	echo '$gestorPaisComissaoValor ' . $gestorPaisComissaoValor . '<br />';

                    update('analytics_campanhas', $data, 'campanhaID = ' . $campanhaID);
                } 
            }
        }
    }
}

// SELECT SUM(`campanhaComissaoValor`) FROM `analytics_campanhas` WHERE campanha_date = '2023-07-20' AND _clienteID = 16;