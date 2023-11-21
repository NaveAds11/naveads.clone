<?php
header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

if (isset($_GET['manual'])) {
    
    if (isset($_GET['iniciar'])) {
        file_put_contents(ABSPATH . '/data/cron_links.txt', '');
        
        echo '<p>Iniciando...</p>'; 
        
        if (!isset($_GET['geral'])) { ?>
            <script>
                window.location = '<?php echo site_url('cron/analytics_links.php?manual&executar' . (isset($_GET['ontem']) ? '&ontem' : '')); ?>';
            </script>
            <?php 
        }
        
    } else if (isset($_GET['executar'])) {

        $aplicados = file_get_contents(ABSPATH . '/data/cron_links.txt');
        $aplicados = (array) json_decode($aplicados, true);
        
        $totalAplicados = 0;

        $totalDatas = 6;
        if (isset($_GET['ontem'])) {
            $totalDatas = 1;
        }

        foreach ($aplicados as $itemValor) {
            if (count($itemValor) == $totalDatas)
                $totalAplicados++;
        }

        if (isset($_GET['ontem'])) {
            $arrDias = array(
                date('Y-m-d', strtotime('-1 day'))
            );

        } else {
            $arrDias = array(
                //date('Y-m-d', strtotime('-6 day')),
                //date('Y-m-d', strtotime('-5 day')),
                //date('Y-m-d', strtotime('-4 day')),
                date('Y-m-d', strtotime('-3 day')),
                date('Y-m-d', strtotime('-2 day')),
                date('Y-m-d', strtotime('-1 day'))
            );
        }

        $query = mysqli_query($con, "SELECT *
            FROM analytics
                INNER JOIN contas ON contaID = _contaID
            LIMIT 100;");

        if ($query) {
            $total = mysqli_num_rows($query);
            
            echo 'TOTAL DE SITES: ' . $total . '<br />';
            echo 'APLICADOS: ' . $totalAplicados . '<br /><br />';
            
            if ($totalAplicados == $total) {
                echo 'Finalizado<br />';
                
            } else {

                while ($itemValor = mysqli_fetch_array($query)) { 
                    $analyticID       = $itemValor['analyticID'];
                    $analyticContaID  = $itemValor['analyticContaID'];
                    $contaAccessToken = $itemValor['contaAccessToken'];

                    echo 'CONTA: ' . $itemValor['analyticNome'] . '<br />';

                    foreach ($arrDias as $diaValor) {
                        $dados = analyticsLinks($analyticContaID, $contaAccessToken, $diaValor, $diaValor);
                        $json  = $dados['lista'];

                        if (isset($aplicados[$analyticID])) {
                            if (in_array($diaValor, $aplicados[$analyticID]))
                                continue;
                                
                            if (count($aplicados[$analyticID]) == $totalDatas)
                                break 2;
                        }
                        
                        mysqli_query($con, "DELETE FROM analytics_links 
                            WHERE 
                                link_date   = '$diaValor' AND
                                _analyticID = $analyticID;");

                        $aplicados[$analyticID][] = $diaValor;
                        
                        file_put_contents(ABSPATH . '/data/cron_links.txt', json_encode($aplicados));

                        echo 'Inserindo para o dia: ' . $diaValor . '<br />';
                        
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
                        $arrCampos    = array();

                        foreach ($arrLinhas as $arrItens) { 
                            $dados = array();
                            
                            $linkID   = 0;
                            $linkData = '';
                            $linkNome = '';

                            foreach ($arrItens as $linhaIndex => $linhaValor) {
                                
                                if ($linhaIndex == 'date') {
                                    $linhaValor = substr($linhaValor, 0, 4) . '-' . substr($linhaValor, 4, 2) . '-' . substr($linhaValor, 6, 2);
                                    $linkData   = $linhaValor;
                                }
                                
                                if ($linhaIndex == 'sessionCampaignName')
                                    $linkNome = $linhaValor;
                                    
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
                                        $linhaValor = $arrTempo[0] . 's';
                                    }
                                }
                                
                                if ($linhaIndex == 'advertiserAdCostPerClick')
                                    $linhaValor = round($linhaValor, 2);
                                    
                                if ($linhaIndex == 'totalAdRevenue')
                                    $linhaValor = round($linhaValor, 2);
                                    
                                $dados['link_' . $linhaIndex] = $linhaValor;
                            }
                            
                            $dados['linkCriadoEm'] = date('Y-m-d');
                            $dados['_analyticID'] = $analyticID;
                            
                            $arrInserts[$posicao][] =  "('" . implode("', '", $dados) . "')";

                            if (count($arrInserts[$posicao]) > 300)
                                $posicao++;

                            if (count($arrCampos) == 0)
                                $tabelaCampos = '(' . implode(', ', array_keys($dados)) . ')';
                        }

                        foreach ($arrInserts as $insertValor) {
                            $sql = "INSERT INTO 
                                analytics_links " . $tabelaCampos . "
                            VALUES
                                " . implode(', ', $insertValor);

                            mysqli_query($con, $sql);
                        }

                        break 2;
                    }
                } ?>
                        
                <p>Atualizando página em 2 segundos.</p>
                
                <?php 
                if (!isset($_GET['geral'])) { ?>
                    <script>
                        setTimeout(function(){
                           window.location.reload(1);
                        }, 2000);
                    </script>
                    
                    <?php
                }
            }
        }

    } else { ?>
        <p><a href="https://ads.plusbem.com/cron/analytics_links.php?manual&iniciar">Iniciar processo (7 dias)</a></p>
        <p><a href="https://ads.plusbem.com/cron/analytics_links.php?manual&iniciar&ontem">Iniciar processo (Ontem)</a></p>
        <?php
    }
    
    exit;

} else {
    $arrDias = array(
        date('Y-m-d', strtotime('-1 day'))
    );

    $query = mysqli_query($con, "SELECT *
        FROM analytics
            INNER JOIN contas ON contaID = _contaID
        LIMIT 100;");

    if ($query) {
        while ($itemValor = mysqli_fetch_array($query)) { 
            $analyticID       = $itemValor['analyticID'];
            $analyticContaID  = $itemValor['analyticContaID'];
            $contaAccessToken = $itemValor['contaAccessToken'];
          
            foreach ($arrDias as $diaValor) {
                $dados = analyticsLinks($analyticContaID, $contaAccessToken, $diaValor, $diaValor);
                $json  = $dados['lista'];
                
                mysqli_query($con, "DELETE FROM analytics_links 
                    WHERE 
                        link_date   = '$diaValor' AND
                        _analyticID = $analyticID;");
                
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
                $arrCampos    = array();

                foreach ($arrLinhas as $arrItens) { 
                    $dados = array();
                    
                    $linkID   = 0;
                    $linkData = '';
                    $linkNome = '';

                    foreach ($arrItens as $linhaIndex => $linhaValor) {
                        
                        if ($linhaIndex == 'date') {
                            $linhaValor = substr($linhaValor, 0, 4) . '-' . substr($linhaValor, 4, 2) . '-' . substr($linhaValor, 6, 2);
                            $linkData   = $linhaValor;
                        }
                        
                        if ($linhaIndex == 'sessionCampaignName')
                            $linkNome = $linhaValor;
                            
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
                            
                        if ($linhaIndex == 'totalAdRevenue')
                            $linhaValor = round($linhaValor, 2);
                            
                        $dados['link_' . $linhaIndex] = $linhaValor;
                    }
                    
                    $dados['linkCriadoEm'] = date('Y-m-d');
                    $dados['_analyticID'] = $analyticID;
                    
                    $arrInserts[$posicao][] =  "('" . implode("', '", $dados) . "')";

                    if (count($arrInserts[$posicao]) > 300)
                        $posicao++;

                    if (count($arrCampos) == 0)
                        $tabelaCampos = '(' . implode(', ', array_keys($dados)) . ')';
                }
              
                foreach ($arrInserts as $insertValor) {
                    $sql = "INSERT INTO 
                        analytics_links " . $tabelaCampos . "
                    VALUES
                        " . implode(', ', $insertValor);

                    $retorno = mysqli_query($con, $sql);
                    if (!$retorno) {
                        /*
                        echo '<pre>';
                        print_r($insertValor);
                        echo '</pre>'; 
                        */
                        
                        echo 'ERRO: ' . mysqli_error($con) . '<br />';
                    }
                }
            }
        }
    }
}