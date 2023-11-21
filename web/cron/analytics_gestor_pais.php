<?php
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$arrContas = array();

$maximoItens = 300;

$query = mysqli_query($con, "SELECT *
    FROM analytics
        INNER JOIN contas ON contaID = _contaID
    LIMIT 100;");

if ($query) {
    while ($itemValor = mysqli_fetch_array($query)) {
        $analyticID       = $itemValor['analyticID'];
        $analyticContaID  = $itemValor['analyticContaID'];
        $analyticNome     = $itemValor['analyticNome'];
        $contaAccessToken = $itemValor['contaAccessToken'];
        
        $arrContas[$analyticID] = array(
            'analyticID'       => $analyticID,
            'analyticContaID'  => $analyticContaID,
            'analyticNome'     => $analyticNome,
            'contaAccessToken' => $contaAccessToken
        );
    }
}

$arquivo            = ABSPATH . '/data/cron_gestor_pais.txt';
$arquivoContas      = ABSPATH . '/data/cron_gestor_contas.txt';
$arquivoItens       = ABSPATH . '/data/cron_gestor_pais_itens.txt';
$arquivoTempoInicio = ABSPATH . '/data/cron_gestor_pais_tempo_inicio.txt';
$arquivoTempoFinal  = ABSPATH . '/data/cron_gestor_pais_tempo_final.txt';

if (isset($_GET['iniciar'])) {
    file_put_contents($arquivo,            '');
    file_put_contents($arquivoItens,       '');
    file_put_contents($arquivoContas,      json_encode($arrContas));
    file_put_contents($arquivoTempoInicio, strtotime('now'));
    
    echo 'Iniciando...';
    
    if (!isset($_GET['geral']))
        header('location: ' . site_url('cron/analytics_gestor_pais.php?montar'));
    
} else if (isset($_GET['montar'])) {
    
    $html     = file_get_contents($arquivoItens);
    $json     = (array) json_decode($html, true);
    $arrLista = array_filter($json);
    
    $arrDias = array(
        //date('Y-m-d', strtotime('-6 day')),
        //date('Y-m-d', strtotime('-5 day')),
        //date('Y-m-d', strtotime('-4 day')),
        date('Y-m-d', strtotime('-3 day')),
        date('Y-m-d', strtotime('-2 day')),
        date('Y-m-d', strtotime('-1 day')),
    );
    
    $html      = file_get_contents($arquivoContas);
    $json      = (array) json_decode($html, true);
    $arrContas = array_filter($json);
    
    $contas = count($arrContas);
    
    $_arrLista = array();
    
    foreach ($arrContas as $contaIndex => $contaValor) {
        $analyticID       = $contaValor['analyticID'];
        $analyticContaID  = $contaValor['analyticContaID'];
        $analyticNome     = $contaValor['analyticNome'];
        $contaAccessToken = $contaValor['contaAccessToken'];
        
        $posicao = 1;
        
        foreach ($arrDias as $dataValor) {    
            echo $contaValor['analyticNome']. ' - Data: ' . $dataValor . '<br />';
            
            $dados = analyticsGestorPais($analyticContaID, $contaAccessToken, $dataValor, $dataValor);
            $json  = $dados['lista'];
            
            $arrTopo = array();
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
                        foreach ($_itemValor['dimensionValues'] as $__itemValor) {
                            $campoNome = $arrTopo[$pos];
                            
                            $arrLinhas[$linhaIndex][$campoNome] = $__itemValor['value'];
                            
                            $pos++;
                        }
                    } 
              
                    if (isset($_itemValor['metricValues'])) {
                        foreach ($_itemValor['metricValues'] as $__itemValor) {
                            $campoNome = $arrTopo[$pos];
                            
                            $arrLinhas[$linhaIndex][$campoNome] = $__itemValor['value'];
                            
                            $pos++;
                        }
                    }
                }
            } 
            
            $posicao      = 1;
            $tabelaCampos = '';
            $arrInserts   = array();
            $arrTotal     = array();
            $arrCidades   = array();
            
            $x = 0;
            
            foreach ($arrLinhas as $arrItens) { 
                $dados = array();
                
                // if ($arrItens['sessionCampaignName'] <> 'C6N14C4ganharcelularPT')
                //     continue;
                
                foreach ($arrItens as $linhaIndex => $linhaValor) {
                    
                    if ($linhaIndex == 'date') {
                        $paisData = $linhaValor;
                        
                        $linhaValor = substr($linhaValor, 0, 4) . '-' . substr($linhaValor, 4, 2) . '-' . substr($linhaValor, 6, 2);
                    }
                    
                    /*
                    if ($linhaIndex == 'sessionSource') {
                        if ($linhaValor == '' || $linhaValor == null)
                            continue;
                    } */
                    
                    if ($linhaIndex == 'sessionManualTerm') {
                        $linhaValor = str_replace('utm_term=', '', $linhaValor);
                    }
                        
                    if ($linhaIndex == 'firstUserCampaignName')
                        $paisNome = $linhaValor;
                    
                    if ($linhaIndex == 'city')
                        $linhaValor = str_replace("'", "\'", $linhaValor);
                        
                    if ($linhaIndex == 'screenPageViewsPerSession')
                        $linhaValor = round($linhaValor, 2);
                        
                    if ($linhaIndex == 'bounceRate')
                        $linhaValor = str_replace('0.', '', round($linhaValor, 2));
                        
                    if ($linhaIndex == 'totalAdRevenue')
                        $linhaValor = round($linhaValor, 2);
                        
                    $dados['gestorPais_' . $linhaIndex] = $linhaValor;
                }
                
                // if ($dados['gestorPais_sessionSource'] == '(not set)' || empty($dados['gestorPais_sessionSource']))
                //    continue;
                
                $dados['_analyticID']  = $analyticID;
                
                $_arrLista[$posicao][] = $dados;
                
                if (count($_arrLista[$posicao]) >= $maximoItens)
                    $posicao++;
            }
        }
        
        unset($arrContas[$contaIndex]);
        
        break;
    }
    
    $arrLista = array_merge($arrLista, $_arrLista);

    file_put_contents($arquivoItens,  json_encode($arrLista));
    file_put_contents($arquivoContas, json_encode($arrContas));
    
    if ($contas == 0) {
        echo 'Iniciando...';
        
        if (!isset($_GET['geral']))
            header('location: ' . site_url('cron/analytics_gestor_pais.php?executar'));
    } else { 
    
        if (!isset($_GET['geral'])) { ?>
            <script>
                window.location = '<?php echo site_url('cron/analytics_gestor_pais.php?montar'); ?>';
            </script>
            <?php 
        }
    }
    
} else if (isset($_GET['executar'])) {
    
    $html     = file_get_contents($arquivoItens);
    $json     = (array) json_decode($html, true);
    $arrLista = array_filter($json);
    
    $total = count($arrLista);
    
    if ($total == 0)  {
        
        $tempoInicio = file_get_contents($arquivoTempoInicio);
        $tempoFinal  = file_get_contents($arquivoTempoFinal);
        
        echo 'Importação <strong>Gestor Pais</strong> finalizada<br />';
        echo 'Tempo total do processo: <strong>' . gmdate("H:i:s", $tempoFinal - $tempoInicio) . '</strong><br />';
        echo 'Finalizado';
        
    } else {
        echo 'Restantes: ' . $total . '<br /><br />';
        
        foreach ($arrLista as $itemIndex => $arrItens) {
            
            foreach ($arrItens as $itemValor) {
                
                $campanhaData   = $itemValor['gestorPais_date'];
                $campanhaPais   = $itemValor['gestorPais_country'];
                $sessionSource  = $itemValor['gestorPais_sessionSource'];
                $campanhaNome   = $itemValor['gestorPais_sessionCampaignName'];
                $totalAdRevenue = $itemValor['gestorPais_totalAdRevenue'];
                $_analyticID    = $itemValor['_analyticID'];
                
                /*
                if ($campanhaNome == 'C1BM10C5testegravidezEN') {
                    echo '<pre>';
                    print_r($itemValor);
                    echo '</pre>';
                } */
                
                /*
                $sessionSource = trim($sessionSource);
                if (($sessionSource == '') || ($sessionSource == 'null') || is_null($sessionSource))
                    continue; */
                
                if (!empty($campanhaNome)) {
                    echo 'DATA: ' . $campanhaData . '<br />';
                    echo 'CAMPANHA: ' . $campanhaNome . '<br />';
                    echo 'PAIS: ' . $campanhaPais . '<br />';
                    echo 'CONTA: ' . $arrContas[$_analyticID]['analyticNome'] . '<br /><br />';
                    
                    $gestorPaisID = 0;
                    
                    $cadastrado = mysqli_query($con, "SELECT *
                        FROM analytics_gestor_pais 
                        WHERE
                            gestorPais_date                = '$campanhaData' AND
                            gestorPais_country             = '$campanhaPais' AND
                            gestorPais_sessionCampaignName = '$campanhaNome'
                        LIMIT 1;");
                        
                    // AND gestorPais_totalAdRevenue      = '$totalAdRevenue' 
            
                    if ($cadastrado) {
                        $cadastradoItem = mysqli_fetch_array($cadastrado);    
                        if (isset($cadastradoItem['gestorPaisID']))
                            $gestorPaisID = $cadastradoItem['gestorPaisID'];
                    }
                    
                    if ($gestorPaisID > 0) {
                        $retorno = update('analytics_gestor_pais', $itemValor, 'gestorPaisID = ' . $gestorPaisID);
                        if (!$retorno) {
                            echo 'ERRO ' . mysqli_error($con);
                            exit;
                        }
                        
                    } else {
                        $itemValor['gestorPaisCriadoEm'] = date('Y-m-d');
                        
                        $retorno = insert('analytics_gestor_pais', $itemValor);
                        if (!$retorno) {
                            echo 'ERRO ' . mysqli_error($con);
                            exit;
                        }
                    }
                }
            }
            
            unset($arrLista[$itemIndex]);
            
            break;
        }
        
        echo 'Redirecionando... <br />';
    
        if ($total == 1)
            file_put_contents($arquivoTempoFinal, strtotime('now'));
    
        file_put_contents($arquivoItens, json_encode($arrLista)); 
        
        if (!isset($_GET['geral'])) { ?>
            <script>
                window.location = '<?php echo site_url('cron/analytics_gestor_pais.php?executar'); ?>';
            </script>
            <?php
        }
    }
   
} else { ?>
    <a href="<?php echo site_url('cron/analytics_gestor_pais.php?iniciar'); ?>" title="Iniciar">Iniciar</a>
    <?php 
}