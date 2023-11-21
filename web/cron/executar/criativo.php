<?php 
header("Access-Control-Allow-Origin: *");

include('../../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$pasta    = ABSPATH . 'cron/arquivos/';
$arquivos = glob($pasta . '*.txt');

$tempoInicio = strtotime('now');

$horaAtual = date('Y');

$arrArquivos = array();

foreach ($arquivos as $arquivoValor) {
    if (preg_match('/criativo-(.*?).txt/', $arquivoValor)) {
        $arrArquivos[] = $arquivoValor;
    }    
}

$total = count($arrArquivos);
if ($total == 0) {
    echo 'parar';
    
} else {
    shuffle($arrArquivos);
    
    $contador = file_get_contents('contador.txt');
    $contador = (int) $contador;
    
    foreach ($arrArquivos as $arquivoIndex => $arquivoValor) {
        $html = file_get_contents($arquivoValor);
        $json = (array) json_decode($html, true);
        $json = array_filter($json);
        
        foreach ($json as $listaValor) { 
        
            unlink($arquivoValor);
            
            $contaID         = $listaValor['contaID'];
            $token           = $listaValor['token'];
            $itens           = array();
            if (isset($listaValor['itens']))
                $itens = (array) $listaValor['itens'];
            
            if (count($itens) > 0) {
                
                $arrInserir = array();
                $posicao    = 1;
                
                foreach ($itens as $itemValor) {
                    $previaLink = $itemValor['preview_shareable_link'];
                    $adID       = $itemValor['id'];
                    $adNome     = $itemValor['name'];
                    $campanhaID = $itemValor['campaign_id'];
                    $status     = $itemValor['status'];
                    $adsetID    = $itemValor['adset_id'];
                    
                    $arrData = array(
                        date('Y-m-d')
                    );
                    
                    if ($horaAtual < 5)
                        $arrData[] = date('Y-m-d', strtotime('-1 day'));
                    
                    foreach ($arrData as $dataSelecionada) {
        
                        $_link = 'https://graph.facebook.com/v17.0/' . $adID . '/insights?fields=account_name,campaign_name,spend,impressions,clicks,ctr,cpc,actions{action_type,value}&filtering=[{%22field%22:%22action_type%22,%22operator%22:%22IN%22,%22value%22:[%22landing_page_view,%22]}]&time_range={%22since%22:%22' . $dataSelecionada . '%22,%22until%22:%22' . $dataSelecionada . '%22}&sort=reach_descending&level=campaign&breakdowns=country&access_token=' . $token . '&limit=1000';
                        
                        echo '$_link ' . $_link .'<br />';
                         
                        $contador = $contador + 1;
                        
                        $continua = true;
                        
                        do {
                            
                            $_html = file_get_contents($_link);
                            $adJson = (array) json_decode($_html, true);
                            $adJson = array_filter($adJson);
                            
                            pre($adJson);
                            
                            echo '$_html ' . $_html ;
                            
                            if (isset($adJson['data'])) {
                            
                                if (isset($adJson['data'][0])) {
                                    $data = $adJson['data'][0];
                                    
                                    $contaNome = '';
                                    if (isset($data['account_name']))
                                        $contaNome = $data['account_name'];
                                    
                                    $campanhaNome = '';
                                    if (isset($data['campaign_name']))
                                        $campanhaNome = $data['campaign_name'];
                                    
                                    $custo = '';
                                    if (isset($data['spend']))
                                        $custo = $data['spend'];
                                    
                                    $impressoes = '';
                                    if (isset($data['impressions']))
                                        $impressoes = $data['impressions'];
                                    
                                    $cliques = '';
                                    if (isset($data['clicks']))
                                        $cliques = $data['clicks'];
                                    
                                    $ctr = '';
                                    if (isset($data['ctr']))
                                        $ctr = $data['ctr'];
                                    
                                    $cpc = '';   
                                    if (isset($data['cpc']))
                                        $cpc = $data['cpc'];   
                                    
                                    $visualizacoesPagina = 0;
                                    
                                    $paisSigla = '';
                                    if (isset($data['country']))
                                        $paisSigla = $data['country'];
                                    
                                    $paisNome = arrPaisSiglaNome($paisSigla, true);
                                    
                                    if (isset($data['actions'][0])) {
                                        if (isset($data['actions'][0]['action_type'])) {
                                            if ($data['actions'][0]['action_type'] == 'landing_page_view') {
                                                $visualizacoesPagina = $data['actions'][0]['value'];
                                            }
                                        }
                                    }
                                    
                                    $paisNome = urldecode($paisNome);
                                    $adNome   = urldecode($adNome);
                                        
                                    $data = array(
                                        'criativoData'                 => $dataSelecionada,
                                        'criativoPaisNome'             => $paisNome,
                                        'criativoPaisSigla'            => $paisSigla,
                                        'criativoCTR'                  => $ctr,
                                        'criativoCPC'                  => number_format($cpc, 2, '.', ''),
                                        'criativoImpressoes'           => $impressoes,
                                        'criativoCliques'              => $cliques,
                                        'criativoCampanhaNome'         => $campanhaNome,
                                        'criativoContaNome'            => $contaNome,
                                        'criativoCusto'                => $custo,
                                        'criativoVisualizacoesPaginas' => $visualizacoesPagina,
                                        'criativoPreviaLink'           => $previaLink,
                                        'criativoStatus'               => $status,
                                        'criativoAdname'               => $adNome,
                                        'criativoAdid'                 => $adID,
                                        'criativoAdsetID'              => $adsetID,
                                        '_contaID'                     => $contaID,
                                        '_campanhaID'                  => $campanhaID
                                    );
                                    
                                    $cadastrado = mysqli_query($con, "SELECT *
                                        FROM facebook_criativos 
                                        WHERE 
                                            criativoData = '$dataSelecionada' AND 
                                            criativoAdid = '$adID'
                                        LIMIT 1");
                                        
                                    if ($cadastrado) {
                                        $cadastradoValor = mysqli_fetch_array($cadastrado);
                                        if (isset($cadastradoValor['criativoID'])) {
                                            $criativoID = $cadastradoValor['criativoID'];
                                            
                                            $retorno = update('facebook_criativos', $data, 'criativoID = ' . $criativoID);
                                            if (!$retorno) {
                                                echo 'ERRO 1: '. mysqli_error($con) . '<br />';     
                                            }
                                            
                                        } else {
                                            
                                            $arrInserir[$posicao][] = $data;
                                            if (count($arrInserir[$posicao]) == 300)
                                                $posicao++;
                                        }
                                    }
                                }
                                
                                if (isset($adJson['paging']['next'])) {
                                    $_link = $adJson['paging']['next'];
                                } else {
                                    $continua = false;
                                }
                                
                            } else {
                                $continua = false;
                            }
                            
                        } while ($continua);
                    }
                }
                
                foreach ($arrInserir as $arrDados) {
                    $arrCampos = array();
                    foreach ($arrDados as $itemValor) {
                        $criativoData                 = $itemValor['criativoData'];
                        $criativoPaisNome             = $itemValor['criativoPaisNome'];
                        $criativoPaisSigla            = $itemValor['criativoPaisSigla'];
                        $criativoCTR                  = (float) number_format($itemValor['criativoCTR'], 2, '.', '');
                        $criativoCPC                  = (float) number_format($itemValor['criativoCPC'], 2, '.', '');
                        $criativoImpressoes           = (int) $itemValor['criativoImpressoes'];
                        $criativoCliques              = (int) $itemValor['criativoCliques'];
                        $criativoCampanhaNome         = $itemValor['criativoCampanhaNome'];
                        $criativoContaNome            = $itemValor['criativoContaNome'];
                        $criativoCusto                = (float) number_format($itemValor['criativoCusto'], 2, '.', '');
                        $criativoVisualizacoesPaginas = (int) $itemValor['criativoVisualizacoesPaginas'];
                        $criativoPreviaLink           = $itemValor['criativoPreviaLink'];
                        $criativoStatus               = $itemValor['criativoStatus'];
                        $criativoAdname               = $itemValor['criativoAdname'];
                        $criativoAdid                 = $itemValor['criativoAdid'];
                        $criativoAdsetID              = $itemValor['criativoAdsetID'];
                        $_contaID                     = $itemValor['_contaID'];
                        $_campanhaID                  = $itemValor['_campanhaID'];
                        
                        $arrCampos[] = "('$criativoData', '$criativoPaisNome', '$criativoPaisSigla', '$criativoCTR', '$criativoCPC', '$criativoImpressoes', '$criativoCliques', '$criativoCampanhaNome', '$criativoContaNome', '$criativoCusto', '$criativoVisualizacoesPaginas', '$criativoPreviaLink', '$criativoStatus', '$criativoAdname', '$criativoAdid', '$criativoAdsetID', '$_contaID', '$_campanhaID')";
                    }
                    
                    if (count($arrCampos) > 0) {
                        pre($arrCampos);
                        
                        $_sql = "INSERT INTO facebook_criativos (criativoData, criativoPaisNome, criativoPaisSigla, criativoCTR, criativoCPC, criativoImpressoes, criativoCliques, criativoCampanhaNome, criativoContaNome, criativoCusto, criativoVisualizacoesPaginas, criativoPreviaLink, criativoStatus, criativoAdname, criativoAdid, criativoAdsetID, _contaID, _campanhaID) VALUES " . implode(', ', $arrCampos);
                    
                        echo 'SQL ' . $_sql . '<br />';
                    
                        $_retorno = mysqli_query($con, $_sql);       
                        if (!$_retorno)
                            echo 'ERRO 2: ' . mysqli_error($con) . '<br />';
                                                
                    } else {
                        echo 'Nenhuma informação para inserir';
                    }
                }
            }
        }
        
        $tempoFinal = strtotime('now');
        
        echo 'Tempo: ' . ($tempoFinal - $tempoInicio) . 's';
        
        break;
    }
    
    file_put_contents('contador.txt', $contador);
}