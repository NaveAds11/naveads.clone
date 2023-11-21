
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_time_limit(0);

header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$arquivo      = ABSPATH . '/data/cron_campanhas.txt';
$arquivoItens = ABSPATH . '/data/cron_campanhas_itens.txt';
$arquivoLista = ABSPATH . '/data/cron_campanhas_lista.txt';

if (isset($_GET['iniciar'])) {
    file_put_contents($arquivo,      '');
    file_put_contents($arquivoItens, '');
    file_put_contents($arquivoLista, '');

    $arrLista = array();
    
    $sql = "SELECT *
        FROM analytics
            INNER JOIN contas ON contaID = _contaID
        LIMIT 100;";
        
    $query = mysqli_query($con, $sql);
    if ($query) {    
        while ($itemValor = mysqli_fetch_array($query)) { 
            
            $analyticID       = $itemValor['analyticID'];
            $analyticContaID  = $itemValor['analyticContaID'];
            $analyticNome     = $itemValor['analyticNome'];
            $contaAccessToken = $itemValor['contaAccessToken'];
            
            $arrPais = array();
            
            $dataInicio = date('Y-m-d', strtotime('-1 days'));
            $dataFim    = date('Y-m-d', strtotime('-1 day'));
            
            $pais = mysqli_query($con, "SELECT 
                    pais_firstUserCampaignName, pais_country, pais_date
                FROM analytics_pais 
                WHERE 
                    _analyticID = $analyticID AND
                    pais_date >= '$dataInicio' AND 
                    pais_date <= '$dataFim';");
                
            if ($pais) { 
                while ($paisValor = mysqli_fetch_array($pais)) {  
                    $paisData = $paisValor['pais_date'];
                    
                    $arrPais[$paisData][] = array(
                        'campanha' => $paisValor['pais_firstUserCampaignName'],
                        'nome'     => $paisValor['pais_country']
                    );
                }
            }
            
            $arrTerm = array();
            
            $retornoTerm = analyticsCampanhasManualTerm($analyticContaID, $contaAccessToken, $dataInicio, $dataFim);
            if (isset($retornoTerm['rows'])) {
                if (isset($retornoTerm['rows'])) {
                    foreach ($retornoTerm['rows'] as $linhaIndex => $_itemValor) { 
                        $arrTerm[] = array(
                            'data'     => $_itemValor['dimensionValues'][0]['value'],
                            'term'     => $_itemValor['dimensionValues'][1]['value'],
                            'campanha' => $_itemValor['dimensionValues'][2]['value']
                        );
                    }
                }
            }
            
            $dados = analyticsCampanhas($analyticContaID, $contaAccessToken, $dataInicio, $dataFim);
            $json  = $dados['lista'];
            
            $arrTopo  = array();
            
            if (isset($json['dimensionHeaders'] )) {
                foreach ($json['dimensionHeaders'] as $itemIndex => $_itemValor) {
                    $arrTopo[] = $_itemValor['name'];
                }
            } 
    
            if (isset($json['metricHeaders']    )) {
                foreach ($json['metricHeaders'] as $itemIndex => $_itemValor) {
                    $arrTopo[] = $_itemValor['name'];
                }
            } 
            
            $arrLinhas = array();
            
            if (isset($json['rows'])) {
                foreach ($json['rows'] as $linhaIndex => $_itemValor) { 
                    $pos = 0;
                    if (isset($_itemValor['dimensionValues'] )) {
                        foreach ($_itemValor['dimensionValues'] as $itemIndex => $__itemValor) {
                            $campoNome = $arrTopo[$pos];
                            
                            $arrLinhas[$linhaIndex][$campoNome] = $__itemValor['value'];
                            
                            $pos++;
                        }
                    } 
              
                    if (isset($_itemValor['metricValues'])) {
                        foreach ($_itemValor['metricValues'] as $_itemIndex => $__itemValor) {
                            $campoNome = $arrTopo[$pos];
                            
                            $arrLinhas[$linhaIndex][$campoNome] = $__itemValor['value'];
                            
                            $pos++;
                        }
                    }
                }
            } 
            
            $arrLista[$analyticID]['analyticID']       = $analyticID;
            $arrLista[$analyticID]['analyticNome']     = $analyticNome;
            $arrLista[$analyticID]['analyticContaID']  = $analyticContaID;
            $arrLista[$analyticID]['contaAccessToken'] = $contaAccessToken;

            foreach ($arrLinhas as $arrItens) { 
                $campanhaData                  = $arrItens['date'];                     
                $campanhafirstUserCampaignName = $arrItens['firstUserCampaignName'];                     
                $campanha_firstUserManualTerm  = '';
                $campanhaPaisNome              = '';
                
                $_campanhaData = substr($campanhaData, 0, 4) . '-' . substr($campanhaData, 4, 2) . '-' . substr($campanhaData, 6, 2);
                
                if (isset($arrPais[$_campanhaData])) {
                    foreach ($arrPais[$_campanhaData] as $paisValor) {
                        if ($paisValor['campanha'] == $arrItens['firstUserCampaignName']) {
                            $campanhaPaisNome = $paisValor['nome'];
                            
                            break;
                        }
                    }
                }
                
                foreach ($arrTerm as $termValor) {
                    if ($termValor['data']     == $arrItens['date'] &&
                        $termValor['campanha'] == $arrItens['firstUserCampaignName']) {
                        
                        $campanha_firstUserManualTerm = $termValor['term'];
                        break;        
                    }
                }
                
                $arrItens['campanhaPaisNome']    = $campanhaPaisNome;
                $arrItens['firstUserManualTerm'] = $campanha_firstUserManualTerm;
                $arrItens['hash'] = md5(uniqid(mt_rand(), true)) . rand(1000, 9999);
                
                $arrLista[$analyticID]['itens'][] = $arrItens;
            }
        }
    }
    
    file_put_contents($arquivoLista, json_encode($arrLista));
    
    echo 'Iniciando...'; ?>
    
    <?php 
    if (!isset($_GET['geral'])) { ?>
        <script>
            window.location = '<?php echo site_url('cron/analytics_campanhas.php?executar'); ?>';
        </script>
        <?php 
    }
    
    exit;
    
} else if (isset($_GET['executar'])) {
    
    $aplicados = file_get_contents($arquivo);
    $aplicados = (array) json_decode($aplicados, true);
    $aplicados = array_filter($aplicados);
    
    $aplicadosCampanhas = file_get_contents($arquivoItens);
    $aplicadosCampanhas = (array) json_decode($aplicadosCampanhas, true);
    $aplicadosCampanhas = array_filter($aplicadosCampanhas);
    
    $contas = file_get_contents($arquivoLista);
    $contas = (array) json_decode($contas, true);
    $contas = array_filter($contas);

    $aplicadosTotal = count($aplicados);
    $contasTotal    = count($contas);
        
    echo 'APLICADOS: ' . $aplicadosTotal . '<br />';
    echo 'CONTAS:    ' . $contasTotal . '<br />';
    
    if ($aplicadosTotal == $contasTotal) {
        echo 'Finalizado';

    } else {

        foreach ($contas as $contaValor) {
            $analyticID       = $contaValor['analyticID'];
            $analyticNome     = $contaValor['analyticNome'];
            $analyticContaID  = $contaValor['analyticContaID'];
            $contaAccessToken = $contaValor['contaAccessToken'];
            $contaItens       = (array) $contaValor['itens'];
            $contaTotalItens  = count($contaItens);

            $totalAplicados = 0;
            if (isset($aplicadosCampanhas[$analyticID]))
                $totalAplicados = count($aplicadosCampanhas[$analyticID]);

            echo 'CONTA: ' . $analyticNome . '<br />';
            echo 'CONTA ITENS: ' . $contaTotalItens . '<br />';
            echo 'CONTA ITENS APLICADOS: ' . $totalAplicados . '<br /><br />';

            if (in_array($analyticID, $aplicados))
                continue;

            if (($totalAplicados == $contaTotalItens) || 
                ($contaTotalItens == 0)) {

                $aplicados[] = $analyticID;

                file_put_contents($arquivo, json_encode($aplicados)); 

                continue;
            }

            $posicao = 1;
            
            
            foreach ($contaItens as $arrItens) { 
                $dados = array();
                
                $campanhaID   = 0;
                $campanhaData = '';
                $campanhaNome = '';
                
                $firstUserSource = '';
                if (isset($arrItens['firstUserSource']))
                    $firstUserSource = $arrItens['firstUserSource'];
                
                $firstUserCampaignId = '';
                if (isset($arrItens['firstUserCampaignId']))
                    $firstUserCampaignId = $arrItens['firstUserCampaignId'];
                    
                $firstUserCampaignName = '';
                if (isset($arrItens['firstUserCampaignName']))
                    $firstUserCampaignName = $arrItens['firstUserCampaignName'];
                
                $arrItens['sessionCampaignName'] = $firstUserCampaignName;
                $arrItens['sessionCampaignId']   = $firstUserCampaignId;
                $arrItens['sessionSourceMedium'] = $firstUserSource;
                
                unset($arrItens['firstUserCampaignName']);
                unset($arrItens['firstUserCampaignId']);
                unset($arrItens['firstUserSource']);
                
                $campanhaHash      = $arrItens['hash'];
                $campanhaData      = $arrItens['date'];
                $campanhaNome      = $arrItens['sessionCampaignName'];
                $sessionCampaignId = $arrItens['sessionCampaignId'];
                       
                if (isset($aplicadosCampanhas[$analyticID])) {
                    if (in_array($campanhaHash, $aplicadosCampanhas[$analyticID])) {
                        continue;
                    }
                }
                
                $aplicadosCampanhas[$analyticID][] = $campanhaHash;
    
                if (empty($arrItens['sessionCampaignName']))
                    continue;
                    
                if ($arrItens['sessionSourceMedium'] == 'copy_link / cpc')
                    continue;
                    
                if ($arrItens['sessionSourceMedium'] == '(not set)')
                    continue;
                    
                if ($arrItens['sessionSourceMedium'] == 'not set')
                    continue;
                    
                if ($arrItens['sessionSourceMedium'] == '(direct)')
                    continue;
                    
                if ($arrItens['sessionSourceMedium'] == 'direct')
                    continue;
                
                $campanha_date = '';
                foreach ($arrItens as $linhaIndex => $linhaValor) {
                    
                    if ($linhaIndex == 'campanhaPaisNome') {
                        $dados['campanhaPaisNome'] = $linhaValor;
                        
                        continue;
                    }
                    
                    if ($linhaIndex == 'date') {
                        $linhaValor    = substr($linhaValor, 0, 4) . '-' . substr($linhaValor, 4, 2) . '-' . substr($linhaValor, 6, 2);
                        $campanha_date = $linhaValor;
                    }
                    
                    if ($linhaIndex == 'screenPageViewsPerSession')
                        $linhaValor = round($linhaValor, 2);
                        
                    if ($linhaIndex == 'bounceRate')
                        $linhaValor = str_replace('0.', '', round($linhaValor, 2));
                        
                    if ($linhaIndex == 'averageSessionDuration') {
                        $linhaValor = round(($linhaValor / 60), 2);
                        $arrTempo   = explode('.', $linhaValor);
                        
                        if (isset($arrTempo[1])) {
                            $linhaValor = $arrTempo[0] .'m ' . $arrTempo[1] .'s';
                        } else {
                            $linhaValor = $arrTempo[0] .'s';
                        }
                    }
                    
                    if ($linhaIndex == 'advertiserAdCostPerClick')
                        $linhaValor = round($linhaValor, 2);
                        
                    if ($linhaIndex == 'returnOnAdSpend')
                        $linhaValor = round($linhaValor, 2);
                        
                    if ($linhaIndex == 'advertiserAdCost')
                        $linhaValor = round($linhaValor, 2);
                    
                    $dados['campanha_' . $linhaIndex] = $linhaValor;
                }
                
                $_query = mysqli_query($con, "SELECT *
                    FROM analytics_campanhas
                    WHERE 
                        campanha_date = '$campanha_date' AND 
                        BINARY campanha_sessionCampaignName = '$campanhaNome' AND 
                        _analyticID  = $analyticID
                    LIMIT 1;");
                        
                if ($_query) {
                    $dataValor = mysqli_fetch_array($_query);
                    if (isset($dataValor['campanhaID'])) {
                        $campanhaID = $dataValor['campanhaID'];
                    }
                }

              	if ($dados['campanha_firstUserMedium'] == 'email') {
                	$dados['campanhaManualTerm'] = '';
                } else {
                  	$dados['campanhaManualTerm'] = substr($campanhaNome, 0, 7);
                }
              
                if ($arrItens['advertiserAdCost'] > 0) {
                    $dados['campanhaTipo']                 = 'google';
                    $dados['campanha_sessionSourceMedium'] = 'google';
                }
                
                if ($campanhaID > 0) {
                    $retorno = update('analytics_campanhas', $dados, 'campanhaID = ' . $campanhaID);
                    if (!$retorno) {
                        echo 'ERRO AO ATUALIZAR: ' . mysqli_error($con) . '<br />';
                        exit;
                    }
                    
                } else {
                    
                    $dados['campanhaCriadoEm'] = date('Y-m-d');
                    $dados['_analyticID'] = $analyticID;
                    
                    $retorno = insert('analytics_campanhas', $dados);
                    if (!$retorno) {
                        echo 'ERRO AO CADASTRAR: ' . mysqli_error($con) . '<br />';
                        exit;
                    }
                }
                
                if ($posicao > 38)
                    break;
                
                $posicao++;
            }
        }
    
        file_put_contents($arquivoItens, json_encode($aplicadosCampanhas)); 
        
        $link = site_url('cron/analytics_campanhas.php?executar'); ?>
        
        <p>Redirecionando...</p>
        
        <?php 
        if (!isset($_GET['geral'])) { ?>
            <script>
                window.location = '<?php echo $link; ?>';
            </script>
            <?php
        }
        
        exit;
    }
    
} else { ?>

    <p><a href="<?php echo site_url('cron/analytics_campanhas.php?iniciar'); ?>">Iniciar</a></p>

    <?php
}