<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'funcoes.php'); 

set_time_limit(0);

$dataAtual = date('Y-m-d');

$html    = file_get_contents(ABSPATH . 'data/geral/info.txt');
$arrInfo = (array) json_decode($html, true);
$arrInfo = array_filter($arrInfo);

$dataAplicado = '';
if (isset($arrInfo['data']))
    $dataAplicado = $arrInfo['data'];

$horaAplicado = '';
if (isset($arrInfo['hora']))
    $horaAplicado = $arrInfo['hora'];
    
$arrHorarios = array(
    8,
    9,
    10,
    16,
    17,
    18
);

$horaAtual = date('H');
if (in_array($horaAtual, $arrHorarios)) {
    if ($horaAtual <> $horaAplicado) {
        $arrInfo['hora']       = $horaAtual;
        $arrInfo['data']       = $dataAtual;
        $arrInfo['finalizado'] = 'false';
    }
}

$iniciar = false;
foreach ($arrHorarios as $horarioValor) {
    if ($horaAtual == $horarioValor) {
        $iniciar = true;
        
        $horaAplicado = $horarioValor;
        break;
    }
}

if (isset($arrInfo['finalizado'])) {
    if ($arrInfo['finalizado'] == 'true') {
        $iniciar = false;
    }
}
    
if ($iniciar) {
    $datas = array(
        date('Y-m-d', strtotime('-1 day')),
        date('Y-m-d', strtotime('-2 day'))
    );
    
    $arquivos = array(
        'analytics_pais',
        'analytics',
        'analytics_links',
        'analytics_campanhas',
        'analytics_gestor_pais'
    );
    
    $sql = "SELECT *
        FROM analytics
            INNER JOIN contas ON contaID = _contaID
        LIMIT 100;";
        
    $aplicado = false;
        
    $query = mysqli_query($con, $sql);
    if ($query) {    
        while ($itemValor = mysqli_fetch_array($query)) { 
            $analyticID       = $itemValor['analyticID'];
            $analyticContaID  = $itemValor['analyticContaID'];
            $analyticNome     = $itemValor['analyticNome'];
            $contaAccessToken = $itemValor['contaAccessToken'];
            
            $dir = ABSPATH . 'data/geral/fila_' . $analyticID . '_';
            if (is_file($dir . 'analytics_gestor_pais.txt'))
                continue;
                
            $aplicado = true;
    
            echo 'CONTA: ' . $analyticNome . '<br />';
    
            foreach ($arquivos as $filaValor) {
                $arquivo = $dir . $filaValor . '.txt';
                
                if ($filaValor == 'analytics_pais') {
                    if (!is_file($arquivo)) {
                        $arrLista = array();
                        
                        $arrInfo['itens'][] = $analyticNome . ' - Analytics Pais - ' . date('Y-m-d H:i:s');
    
                        foreach ($datas as $diaValor) {
                            
                            $dados = analyticsPais($analyticContaID, $contaAccessToken, $diaValor, $diaValor);
                            $json  = $dados['lista'];
                            
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
    
                            $posicao = 1;
                            
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
                                
                                $dados['_analyticID']  = $analyticID;
                                
                                $arrLista[$diaValor][] = $dados;
                            }
                        }
                        
                        file_put_contents($arquivo, json_encode($arrLista));
                    }
                }
    
                if ($filaValor == 'analytics') {
                    if (!is_file($arquivo)) {
                        $arrLista = array();
                        
                        $arrInfo['itens'][] = $analyticNome . ' - Analytics - ' . date('Y-m-d H:i:s');
    
                        foreach ($datas as $diaValor) {
                            $dados = analyticsDados($analyticContaID, $contaAccessToken, $diaValor, $diaValor);
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
                                        
                                        $dados['item_averageSessionDuration'] = $arrTempo[0] .'m ' . $arrTempo[1] .'s';
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
                                
                                $dados['_analyticID'] = $analyticID;
                                
                                $arrLista[$diaValor][] = $dados;
                            }
                        }
    
                        file_put_contents($arquivo, json_encode($arrLista));
                    }
                }
    
                if ($filaValor == 'analytics_gestor_pais') {
                    if (!is_file($arquivo)) {
                        $arrLista = array();
                        
                        $arrInfo['itens'][] = $analyticNome . ' - Analytics Gestor Pais - ' . date('Y-m-d H:i:s');
    
                        foreach ($datas as $diaValor) {
    
                            $dados = analyticsGestorPais($analyticContaID, $contaAccessToken, $diaValor, $diaValor);
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
                            
                            $posicao = 1;
                            
                            $x = 0;
                            foreach ($arrLinhas as $arrItens) { 
                                $dados = array();
                                
                                foreach ($arrItens as $linhaIndex => $linhaValor) {
                                    
                                    if ($linhaIndex == 'date') {
                                        $paisData = $linhaValor;
                                        
                                        $linhaValor = substr($linhaValor, 0, 4) . '-' . substr($linhaValor, 4, 2) . '-' . substr($linhaValor, 6, 2);
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
                                
                                if ($dados['gestorPais_sessionSource'] == '(not set)')
                                    continue;
                                
                                $dados['_analyticID'] = $analyticID;
    
                                $arrLista[$diaValor][] = $dados;
                            }
                        }
    
                        file_put_contents($arquivo, json_encode($arrLista));
                    }
                }
    
                if ($filaValor == 'analytics_links') {
                    if (!is_file($arquivo)) {
                        $arrLista = array();
    
                        $arrInfo['itens'][] = $analyticNome . ' - Links - ' . date('Y-m-d H:i:s');
    
                        foreach ($datas as $diaValor) {
                            $dados = analyticsLinks($analyticContaID, $contaAccessToken, $diaValor, $diaValor);
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
                            
                            $posicao = 1;
    
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
                                
                                $dados['_analyticID'] = $analyticID;
    
                                $arrLista[$diaValor][] = $dados;
                            }
                        }
    
                        file_put_contents($arquivo, json_encode($arrLista));
                    }
                } 
                
                if ($filaValor == 'analytics_campanhas') {
                    if (!is_file($arquivo)) {
                        $arrInfo['itens'][] = $analyticNome . ' - Campanhas - ' . date('Y-m-d H:i:s');
    
                        $arrLista = array();
    
                        foreach ($datas as $dataValor) {
                            $dataInicio = $dataValor;
                            $dataFim    = $dataValor;
    
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
                            
                            foreach ($arrLinhas as $arrItens) { 
                                $dados = array();

                                $arrItens['sessionCampaignName'] = $arrItens['firstUserCampaignName'];
                                $arrItens['sessionCampaignId']   = $arrItens['firstUserCampaignId'];
                                $arrItens['sessionSourceMedium'] = $arrItens['firstUserSource'];
                                
                                unset($arrItens['firstUserCampaignName']);
                                unset($arrItens['firstUserCampaignId']);
                                unset($arrItens['firstUserSource']);
                                
                                $campanhaData      = $arrItens['date'];
                                $campanhaNome      = $arrItens['sessionCampaignName'];
                                $sessionCampaignId = $arrItens['sessionCampaignId'];
                                       
                                if (empty($campanhaNome))
                                    continue;
                                    
                                if ($arrItens['sessionSourceMedium'] == 'copy_link / cpc')
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
                                
                                $campanha_firstUserManualTerm  = '';
                                
                                if ($arrItens['sessionSourceMedium'] == 'copy_link / cpc')
                                    continue;
                                
                                $_campanhaData = substr($campanhaData, 0, 4) . '-' . substr($campanhaData, 4, 2) . '-' . substr($campanhaData, 6, 2);
                                               
                                foreach ($arrTerm as $termValor) {
                                    if ($termValor['data']     == $arrItens['date'] &&
                                        $termValor['campanha'] == $arrItens['sessionCampaignName']) {
                                        
                                        $campanha_firstUserManualTerm = $termValor['term'];
                                        break;        
                                    }
                                }
                                
                                $dados['campanha_firstUserManualTerm'] = $campanha_firstUserManualTerm;
                                $dados['campanhaManualTerm'] = substr($campanhaNome, 0, 7);
                                
                                $arrLista[$dataValor][] = $dados;
                            }
                        }
                        
                        file_put_contents($arquivo, json_encode($arrLista));
                    }
                }  
            }
    
            break; 
        }
    }
    
    if (!$aplicado)
        $arrInfo['finalizado'] = 'true';
}

file_put_contents(ABSPATH . 'data/geral/info.txt', json_encode($arrInfo));