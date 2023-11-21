<?php 
header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$datas = array(
    date('Y-m-d', strtotime('-1 day')),
    date('Y-m-d', strtotime('-2 day'))
);

$arquivo = ABSPATH . 'data/geral/info.txt';
$html    = file_get_contents($arquivo);
$arrInfo = (array) json_decode($html, true);
$arrInfo = array_filter($arrInfo);

if (isset($arrInfo['finalizado'])) {
    if ($arrInfo['finalizado'] == 'true') {     
        $calcularCache = true;
        
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
            
        $query = mysqli_query($con, $sql);
        if ($query) {    
            while ($itemValor = mysqli_fetch_array($query)) { 
                $analyticID       = $itemValor['analyticID'];
                $analyticContaID  = $itemValor['analyticContaID'];
                $analyticNome     = $itemValor['analyticNome'];
                $contaAccessToken = $itemValor['contaAccessToken'];
                
                echo 'CONTA VERIFICANDO: ' . $analyticNome . '<br />';
                
                foreach ($arquivos as $arquivoValor) {
                    $arquivo = ABSPATH . 'data/geral/fila_' . $analyticID . '_' . $arquivoValor . '.txt';
                    
                    if (is_file($arquivo)) {
                        $calcularCache = false;
                        
                        echo '$arquivo ' . $arquivo . '<br />';
                        
                        if ($arquivoValor == 'analytics_pais') {
                            $html = file_get_contents($arquivo);
                            $json = (array) json_decode($html, true);
                            $json = array_filter($json);
                            
                            if (count($json) == 0) {
                                unlink($arquivo);
                                
                            } else {
                                
                                foreach ($json as $itemData => $arrItens) {
                                    unset($json[$itemData]);
                                    
                                    mysqli_query($con, "DELETE FROM analytics_pais
                                        WHERE 
                                            pais_date   = '$itemData' AND
                                            _analyticID = $analyticID;");
                                    
                                    $arrInserts   = array();
                                    $arrCampos    = array();
                                    $posicao      = 1;
                                    $tabelaCampos = '';
                                    
                                    foreach ($arrItens as $itemValor) {
                                        
                                        $itemValor['pais_city']                  = addslashes($itemValor['pais_city']);
                                        $itemValor['pais_firstUserCampaignName'] = addslashes($itemValor['pais_firstUserCampaignName']);
                                        
                                        $dados = $itemValor;
                                        $dados['paisCriadoEm'] = date('Y-m-d');
                                        
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
                                        
                                        $retorno = mysqli_query($con, $sql);
                                        if (!$retorno) {
                                            echo '$sql ' . $sql . '<br /><br />';
                                            
                                            echo 'ERRO AO ATUALIZAR: ' . mysqli_error($con) . '<br />';
                                            exit;
                                        }
                                    }
                                    
                                    file_put_contents($arquivo, json_encode($json));
                                    
                                    break;
                                }
                            }
                        }
                        
                        if ($arquivoValor == 'analytics') {
                            $html = file_get_contents($arquivo);
                            $json = (array) json_decode($html, true);
                            $json = array_filter($json);
                            
                            if (count($json) == 0) {
                                unlink($arquivo);
                                
                            } else {
                                
                                foreach ($json as $itemData => $arrItens) {
                                    unset($json[$itemData]);
                                    
                                    mysqli_query($con, "DELETE FROM analytics_dados 
                                        WHERE 
                                            item_date   = '$itemData' AND 
                                            _analyticID = $analyticID;");
                                            
                                    foreach ($arrItens as $linhaValor) {
                                        $dados = $linhaValor;
                                        $dados['itemCriadoEm'] = date('Y-m-d');
                                        
                                        $retorno = insert('analytics_dados', $dados);
                                        if (!$retorno) {
                                            echo 'ERRO AO ATUALIZAR: ' . mysqli_error($con) . '<br />';
                                            exit;
                                        }
                                    }
                                    
                                    file_put_contents($arquivo, json_encode($json));
                                    
                                    break;
                                }
                            }
                        }
                
                        if ($arquivoValor == 'analytics_links') {
                            $html = file_get_contents($arquivo);
                            $json = (array) json_decode($html, true);
                            $json = array_filter($json);
                            
                            if (count($json) == 0) {
                                unlink($arquivo);
                                
                            } else {
                                
                                foreach ($json as $itemData => $arrItens) {
                                    unset($json[$itemData]); 
                                    
                                    mysqli_query($con, "DELETE FROM analytics_links 
                                        WHERE 
                                            link_date   = '$itemData' AND
                                            _analyticID = $analyticID;");
                                            
                                    $posicao      = 1;
                                    $tabelaCampos = '';
                                    $arrInserts   = array();
                                    $arrCampos    = array();
                                    
                                    foreach ($arrItens as $linhaValor) {
                                        $dados = $linhaValor;
                                        $dados['linkCriadoEm'] = date('Y-m-d');
                                        
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
                                            echo '$sql ' . $sql;
                                            
                                            echo 'ERRO AO ATUALIZAR: ' . mysqli_error($con) . '<br />';
                                            exit;
                                        }
                                    }
                                    
                                    file_put_contents($arquivo, json_encode($json));
                                    
                                    break;
                                }
                            }
                        }
                        
                        if ($arquivoValor == 'analytics_campanhas') {
                            $html = file_get_contents($arquivo);
                            $json = (array) json_decode($html, true);
                            $json = array_filter($json);
                            
                            if (count($json) == 0) {
                                unlink($arquivo);
                                
                            } else {
                                
                                foreach ($json as $itemData => $arrItens) {
                                    if (count($arrItens) == 0) {
                                        unset($json[$itemData]);

                                    } else {
                                        
                                        $posicao = 1;
                                        foreach ($arrItens as $linhaIndex => $linhaValor) {
                                            unset($json[$itemData][$linhaIndex]);
                                            
                                            $dados        = $linhaValor;
                                            $campanhaNome = $dados['campanha_sessionCampaignName'];
                                            $campanhaID   = 0;
                                            
                                            $_query = mysqli_query($con, "SELECT *
                                                FROM analytics_campanhas
                                                WHERE 
                                                    campanha_date = '$itemData' AND 
                                                    BINARY campanha_sessionCampaignName = '$campanhaNome' AND 
                                                    _analyticID  = $analyticID
                                                LIMIT 1;");
                                                    
                                            if ($_query) {
                                                $dataValor = mysqli_fetch_array($_query);
                                                if (isset($dataValor['campanhaID'])) {
                                                    $campanhaID = $dataValor['campanhaID'];
                                                }
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

                                            if ($posicao > 59)
                                                break;

                                            $posicao++;
                                        }
                                    }
                                                                        
                                    file_put_contents($arquivo, json_encode($json));
                                    
                                    break;
                                }
                            }
                        }
                        
                        if ($arquivoValor == 'analytics_gestor_pais') {
                            $html = file_get_contents($arquivo);
                            $json = (array) json_decode($html, true);
                            $json = array_filter($json);
                            
                            if (count($json) == 0) {
                                unlink($arquivo);
                                
                            } else {
                                
                                foreach ($json as $itemData => $arrItens) {
                                    $posicao = 1;
                                    
                                    if (count($json[$itemData]) == 0) {
                                        unset($json[$itemData]); 
                                        
                                    } else {
                                        foreach ($arrItens as $itemIndex => $itemValor) {
                                            unset($json[$itemData][$itemIndex]); 
                                            
                                            $campanhaData = $itemValor['gestorPais_date'];
                                            $campanhaPais = $itemValor['gestorPais_country'];
                                            $campanhaNome = $itemValor['gestorPais_sessionCampaignName'];
                                            $_analyticID  = $itemValor['_analyticID'];
                                            
                                            if (!empty($campanhaNome)) {
                                                $gestorPaisID = 0;
                                                
                                                $cadastrado = mysqli_query($con, "SELECT *
                                                    FROM analytics_gestor_pais 
                                                    WHERE
                                                        gestorPais_date                = '$campanhaData' AND
                                                        gestorPais_country             = '$campanhaPais' AND
                                                        gestorPais_sessionCampaignName = '$campanhaNome' 
                                                    LIMIT 1;");
                                        
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
                                                
                                                if ($posicao > 299)
                                                    break;
                                                
                                                $posicao++;
                                            }
                                        }
                                    }
                                    
                                    file_put_contents($arquivo, json_encode($json));
                                    
                                    break;
                                }
                            }
                        }
                        
                    }
                }
                
                break;
                
                /*
                if ($calcularCache || isset($_GET['cache_valores'])) {
                    if (!isset($arrInfo['cache_valores']) || isset($_GET['cache_valores'])) {
                        
                        foreach ($datas as $campanhaData) {
                            $itens = mysqli_query($con, "SELECT *
                                FROM analytics_campanhas
                                WHERE 
                                    campanha_date = '$campanhaData' AND 
                                    _analyticID   = $analyticID
                                GROUP BY campanha_sessionCampaignName
                                ORDER BY campanha_date DESC;");
                            
                            if ($itens) {
                                while ($listaValor = mysqli_fetch_array($itens)) { 
                                    $campanhaID   = $listaValor['campanhaID'];
                                    $campanhaNome = $listaValor['campanha_sessionCampaignName'];
                                    
                                    $dados           = array();
                                    $totalGeral      = 0;
                                    $totalCusto      = 0;
                                    $totalReceitaAD  = 0;
                                    $totalImposto    = 0;
                                    $totalCusto      = 0;
                                    $totalComissao   = 0;
                                    $totalLucroFinal = 0;
                                                  
                                    $campanhas = mysqli_query($con, "SELECT *,
                                        SUM(IFNULL(gestorPaisResultados, 0)) AS gestorPaisResultados,
                                        SUM(IFNULL(gestorPaisImpressoes, 0)) AS gestorPaisImpressoes,
                                        SUM(IFNULL(gestorPaisCPR, 0)) AS gestorPaisCPR,
                                        SUM(IFNULL(gestorPaisImpostoValor, 0)) AS gestorPaisImpostoValor,
                                        SUM(IFNULL(gestorPaisCustoValor, 0)) AS gestorPaisCustoValor,
                                        SUM(IFNULL(gestorPais_totalAdRevenue, 0)) AS gestorPais_totalAdRevenue,
                                        SUM(IFNULL(gestorPaisCustoCliques, 0)) AS gestorPaisCustoCliques,
                                        SUM(IFNULL(gestorPaisComissaoValor, 0)) AS gestorPaisComissaoValor,
                                        SUM(IFNULL(gestorPaisImposto, 0)) AS gestorPaisImposto,
                                        SUM(IFNULL(gestorPaisLucroFinal, 0)) AS gestorPaisLucroFinal
                                    FROM analytics_gestor_pais A
                                        INNER JOIN analytics ON analyticID = A._analyticID
                                    WHERE 
                                        gestorPais_sessionCampaignName = '$campanhaNome' AND
                                        gestorPais_date                = '$campanhaData' AND 
                                        _analyticID                    = $analyticID
                                    LIMIT 1;");
                                    
                                    if ($campanhas) {
                                        $campanhaValor = mysqli_fetch_array($campanhas);
                        
                                        if (isset($campanhaValor['gestorPaisResultados'])) {
                                            
                                            $dados['campanhas_totalResultados']    = $campanhaValor['gestorPaisResultados'];
                                            $dados['campanhas_totalImpressoes']    = $campanhaValor['gestorPaisImpressoes'];
                                            $dados['campanhas_totalCPR']           = $campanhaValor['gestorPaisCPR'];
                                            $dados['campanhas_totalImpostoValor']  = $campanhaValor['gestorPaisImpostoValor'];
                                            $dados['campanhas_totalCustoValor']    = $campanhaValor['gestorPaisCustoValor'];
                                            $dados['campanhas_totalAdRevenue']     = $campanhaValor['gestorPais_totalAdRevenue'];
                                            $dados['campanhas_totalCustoCliques']  = $campanhaValor['gestorPaisCustoCliques'];
                                            $dados['campanhas_totalComissaoValor'] = $campanhaValor['gestorPaisComissaoValor'];
                                            $dados['campanhas_totalImposto']       = $campanhaValor['gestorPaisImposto'];
                                            $dados['campanhas_totalLucroFinal']    = $campanhaValor['gestorPaisLucroFinal'];
                                            $dados['campanha_cacheCustoStatus']    = $campanhaValor['gestorPaisCustoStatus'];
                                            $dados['campanha_cacheAnalyticNome']   = $campanhaValor['analyticNome'];
                                        }
                                    }
                                    
                                    $campanhaLinksTotal     = '';
                                    $campanhaGoogleadsTotal = '';
                                    $campanhaPaisTotal      = '';
                                    
                                    $roiPaises = '';
                                    
                                    $rois = mysqli_query($con, "SELECT *
                                        FROM analytics_gestor_pais  A
                                            INNER JOIN analytics_campanhas ON campanha_sessionCampaignName = gestorPais_sessionCampaignName
                                            INNER JOIN analytics ON analyticID = A._analyticID
                                        WHERE
                                            gestorPais_sessionCampaignName = '$campanhaNome' AND
                                            gestorPais_date                = '$campanhaData' AND 
                                            _analyticID                    = $analyticID
                                        GROUP BY gestorPais_country
                                        ORDER BY gestorPais_country ASC;");
                                        
                                    if ($rois) {
                                        if (mysqli_num_rows($rois) > 0) { 
                                            while ($roiItem = mysqli_fetch_array($rois)) { 
                                                $gestorPais_date           = $roiItem['gestorPais_date']; 
                                                $gestorPais_country        = $roiItem['gestorPais_country']; 
                                                $gestorPaisCustoValor      = $roiItem['gestorPaisCustoValor'];
                                                $gestorPais_totalAdRevenue = $roiItem['gestorPais_totalAdRevenue'];
                                                
                                                if ($gestorPaisCustoValor > 0) {
                                                    $roiValor = $gestorPais_totalAdRevenue / $gestorPaisCustoValor;
                                                    
                                                    if ($roiValor >= 2.00) { 
                                                        $roiPaises .= '<span class="label label-success"><span>' . $gestorPais_country . '</span> ' . fmoney($roiValor) . '</span>';
                                                    } else if ($roiValor < 0.90) {
                                                        $roiPaises .= '<span class="label label-danger"><span>' . $gestorPais_country . '</span> ' . fmoney($roiValor) . '</span>';
                                                    } else if ($roiValor < 1.11) { 
                                                        $roiPaises .= '<span class="label label-warning"><span>' . $gestorPais_country . '</span> ' . fmoney($roiValor) . '</span>';
                                                    } else if ($roiValor < 2.00) { 
                                                        $roiPaises .= '<span class="label label-info"><span>' . $gestorPais_country . '</span> ' . fmoney($roiValor) . '</span>';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    $dados['campanha_roiPaises'] = $roiPaises;
                                    
                                    echo '<pre>';
                                    print_r($dados);
                                    echo '</pre>';
                                    
                                    $retorno = update('analytics_campanhas', $dados, 'campanhaID = ' . $campanhaID);
                                    if (!$retorno) {
                                        echo 'ERRO AO ATUALIZAR: ' . mysqli_error($con) . '<br />';
                                        exit;
                                    }
                                }
                            }
                        }
                        
                        echo 'CACHE APLICADO';
                        exit;
                        
                        $arrInfo['cache_valores'] = 'true';
                        
                        $arquivo = ABSPATH . 'data/geral/info.txt';
                        file_put_contents($arquivo, json_encode($arrInfo));
                    }
                } */
                
            }
        }
        
        echo 'Finalizado';
    }
}