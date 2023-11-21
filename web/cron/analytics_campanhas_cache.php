<?php
set_time_limit(0);

header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$arquivo      = ABSPATH . '/data/cron_campanhas_cache.txt';
$arquivoItens = ABSPATH . '/data/cron_campanhas_cache_itens.txt';

$cronLink = site_url('cron/analytics_campanhas_cache.php');

$clienteComissaoValor = 10;

$arrDatas = array();

for ($x = 1; $x < 7; $x++) {
    $arrDatas[] = date('Y-m-d', strtotime('-' . $x . ' day'));
}

$impostoPorcentagem = getConfig('imposto_porcentagem');
if (empty($impostoPorcentagem))
    $impostoPorcentagem = 10;
        
if (isset($_GET['iniciar'])) {
    file_put_contents($arquivo,      '');
    file_put_contents($arquivoItens, '');
    
    $arrItens = array();

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
            
            $arrItens[$analyticID] = array(
                'analyticID'       => $analyticID,
                'analyticContaID'  => $analyticContaID,
                'analyticNome'     => $analyticNome,
                'contaAccessToken' => $contaAccessToken,
                'datas'            => $arrDatas
            );
            
        }
    }
    
    echo 'Iniciando...';
    
    file_put_contents($arquivoItens, json_encode($arrItens));
    
    if (!isset($_GET['geral']))
        header('location: ' . $cronLink . '?executar');
        
    exit;
    
} else if (isset($_GET['comissoes'])) {
    
    /*
    $arrSocial = array(
        'facebook',
        'tiktok'
    );
    
    echo 'Aplicando comissões.<br /><br />';
    
    foreach ($arrDatas as $dataValor) {
        foreach ($arrSocial as $socialValor) {
            mysqli_query($con, "DELETE FROM cliente_campanhas_custo
                WHERE 
                    campanhaCustoTipo = '$socialValor' AND
                    campanhaData      = '$dataValor' "); 
        }
    }
    
    $arrAnunciantes = array();
    
    $anunciantes = mysqli_query($con, "SELECT *
	    FROM clientes;");
    
    if ($anunciantes) {	
        while ($anuncianteValor = mysqli_fetch_array($anunciantes)) { 
            $clienteID      = $anuncianteValor['clienteID'];
            $clienteNome    = $anuncianteValor['clienteNome'];
            $clienteUtmTerm = $anuncianteValor['clienteUtmTerm'];
            $arrTermos      = array();
            
            if (!empty($clienteUtmTerm))
                $arrTermos = array_map('trim', explode(',', $clienteUtmTerm));
                
            foreach ($arrSocial as $socialNome) {
                
                echo 'TIPO: ' . $socialNome . '<br />';
            
                foreach ($arrDatas as $dataValor) {
                    $comissaoValor = 0;
                    
                    $posicao = 1;
                    
                    $campanhasLista = mysqli_query($con, "SELECT 
                            campanhaManualTerm AS sessionCampaignName, campanha_sessionCampaignName
                        FROM `analytics_campanhas` 
                        WHERE 
                           campanha_sessionSourceMedium LIKE '%$socialNome%' AND
                           campanha_firstUserManualTerm IN ('" . implode("','", $arrTermos) . "') AND 
                           campanha_date                  = '$dataValor' 
                        GROUP BY campanhaManualTerm 
                        ORDER BY campanhaManualTerm ASC; ");
                        
                    if ($campanhasLista) {
                        while ($campanhasListaValor = mysqli_fetch_array($campanhasLista)) {
                            $sessionCampaignName = $campanhasListaValor['sessionCampaignName'];
                 
                            $sql = "SELECT SQL_CACHE *,
                                campanhas_totalResultados AS gestorPaisResultados,
                                campanhas_totalImpressoes AS gestorPaisImpressoes,
                                campanhas_totalCPR AS gestorPaisCPR,
                                campanhas_totalImpostoValor AS gestorPaisImpostoValor,
                                campanhas_totalCustoValor AS gestorPaisCustoValor,
                                campanhas_totalAdRevenue AS gestorPais_totalAdRevenue,
                                campanhas_totalCustoCliques AS gestorPaisCustoCliques,
                                campanhas_totalComissaoValor AS gestorPaisComissaoValor,
                                campanhas_totalImposto AS gestorPaisImposto,
                                campanhas_totalLucroFinal AS gestorPaisLucroFinal,
                                campanha_cacheCustoStatus AS gestorPaisCustoStatus,
                                campanha_cacheAnalyticNome AS analyticNome
                            FROM analytics_campanhas
                            WHERE
                                campanha_sessionCampaignName LIKE '$sessionCampaignName%' AND
                                campanha_date = '$dataValor'
                            LIMIT 100;";
                            
                            $query = mysqli_query($con, $sql);
                            if ($query) {
                                
                                if (mysqli_num_rows($query) > 0) { 
                                    while ($itemValor = mysqli_fetch_array($query)) { 
                                        $campanhaID           = $itemValor['campanhaID'];
                                        $campanhaNome         = $itemValor['campanha_sessionCampaignName'];
                                        $nomeConta            = $itemValor['campanhaNomeDaConta'];
                                        $receitaTotal         = $itemValor['gestorPais_totalAdRevenue'];
                                        $totalCustoViews      = $itemValor['gestorPaisImpressoes'];
                                        $gestorPaisCPR        = $itemValor['gestorPaisCPR'];
                                        $custoValor           = $itemValor['gestorPaisCustoValor'];
                                        $totalCustoResultados = $itemValor['gestorPaisResultados'];
                                        $campanhaStatus       = $itemValor['campanhaStatus'];
                                            
                                        echo $posicao . ' - $campanhaNome ' . $campanhaNome . '<br />';
                                        
                                        if ($receitaTotal < 1)
                                            continue;
                                            
                                        $campanhaImpostoValor = ($receitaTotal / 100) * $impostoPorcentagem;
                                        $receitaTotal       = ($receitaTotal - $campanhaImpostoValor) - $custoValor;
                                        
                                        $gestorPaisComissaoValor = $receitaTotal - ($receitaTotal - (($receitaTotal / 100) * $clienteComissaoValor));
                                        
                                        $gestorPaisLucroFinal = $receitaTotal - $gestorPaisComissaoValor;
                                        
                                        $comissaoValor = $comissaoValor + $gestorPaisComissaoValor;
                                        
                                        echo 'Comissão ' . $gestorPaisComissaoValor . '<br />';
                                        
                                        
                                        $data = array(
                                            'campanhaCustoLucro'	  => $gestorPaisLucroFinal,
                                            'campanhaCustoComissao'	  => $gestorPaisComissaoValor,
                                            'campanhaCustoValor'	  => $custoValor,
                                            'campanhaCustoResultados' => $totalCustoResultados,
                                            'campanhaCustoCPR'	      => $gestorPaisCPR,
                                            'campanhaCustoViews'      => $totalCustoViews,
                                            'campanhaData'            => $dataValor,
                                            'campanhaStatus'          => $campanhaStatus,
                                            'campanhaCustoTipo'       => $socialNome,
                                            '_clienteID'              => $clienteID,
                                            '_campanhaID'	          => $campanhaID
                                        );
                                        
                                        $retorno = insert('cliente_campanhas_custo', $data);
                                    
                                        $posicao++;
                                    }
                                }
                            }
                        }
                    }
                    
                    echo 'Comissão total: ' . $comissaoValor . '<br /><br />';
                }
            }
        }
    }
    
    $query = mysqli_query($con, "SELECT *
        FROM clientes
        LIMIT 100;");
    
    if ($query) {
        while ($lista = mysqli_fetch_array($query)) { 
            $clienteUtmTerm       = $lista['clienteUtmTerm'];
            $clienteNome          = $lista['clienteNome'];
            $clienteID            = $lista['clienteID'];
            $clienteComissaoValor = $lista['clienteComissao'];
            $clienteComissaoValor = (int) $clienteComissaoValor;
            
            if ($clienteComissaoValor == 0)
                $clienteComissaoValor = 10;
            
            $arrUtm = explode(',', $clienteUtmTerm);
            $arrUtm = array_filter($arrUtm);
            
            $_arrUtm = array();
            foreach ($arrUtm as $utmValor) {
                $_arrUtm[] = trim($utmValor);
            }
    
            for ($x = 1; $x < 2; $x++) {
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
                                
                                echo '<pre>';
                                print_r($custoItem);
                                echo '</pre>';
                                
                                exit;
                                
                                $campanhaCustoValor = $custoItem['campanhaCustoValor'];
                            }
                        }
    
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
                       	
                        $_retorno = update('analytics_campanhas', $data, 'campanhaID = ' . $campanhaID);
                    } 
                }
            }
        }
    } */
    
    $arrComissao = array();
    
    $clientes = mysqli_query($con, "SELECT *
        FROM clientes
        LIMIT 100;");
    
    if ($clientes) {
        while ($clienteValor = mysqli_fetch_array($clientes)) { 
            $clienteUtmTerm       = $clienteValor['clienteUtmTerm'];
            $clienteNome          = $clienteValor['clienteNome'];
            $clienteID            = $clienteValor['clienteID'];
            $clienteComissaoValor = $clienteValor['clienteComissao'];
            $clienteComissaoValor = (int) $clienteComissaoValor;
            
            if ($clienteComissaoValor == 0)
                $clienteComissaoValor = 10;
                
            $_urlTermo = explode(',', $clienteUtmTerm);
            foreach ($_urlTermo as $termoValor) {
                $termoValor = trim($termoValor);
                
                $arrComissao[$termoValor] = $clienteComissaoValor;
            }
        }
    }
    
    $arrDatas = array(
        date('Y-m-d', strtotime('-1 day')),
        date('Y-m-d', strtotime('-2 day')),
        date('Y-m-d', strtotime('-3 day')),
        date('Y-m-d', strtotime('-4 day')),
        date('Y-m-d', strtotime('-5 day')),
        date('Y-m-d', strtotime('-6 day')),
        date('Y-m-d', strtotime('-7 day'))
    );
    
    foreach ($arrDatas as $dataValor) {
        
        $sql = "SELECT *
            FROM `analytics_campanhas` 
            WHERE 
               campanha_date = '$dataValor' ;";
           
        $campanhas = mysqli_query($con, $sql);
        
        if ($campanhas) {
            while ($campanhaValor = mysqli_fetch_array($campanhas)) { 
                
                $campanhaID   = $campanhaValor['campanhaID'];
                $campanhaNome = $campanhaValor['campanha_sessionCampaignName'];
                $receitaTotal = $campanhaValor['campanhas_totalAdRevenue'];
                $termo        = $campanhaValor['campanha_firstUserManualTerm'];
                
                $custoValor    = 0;
                $comissaoValor = 0;
                $lucroFinal    = 0;
                $impressoes    = 0;
                $contaNome     = '';
                $contaID       = '';
                $status        = 0;
                
                $itens = mysqli_query($con, "SELECT *,
                        SUM(itemCustoValor) AS custo,
                        SUM(itemImpressoes) AS impressoes, 
                        SUM(itemVisualizacoes) AS visualizacoes, 
                        SUM(itemCliques) AS cliques
                    FROM `facebook_itens` 
                    WHERE 
                        itemCampanhaNome = '$campanhaNome' AND 
                        itemData         = '$dataValor' 
                    LIMIT 1;");
                    
                if ($itens) {
                    $itemValor = mysqli_fetch_array($itens);
                    if (isset($itemValor['itemID'])) {
                        $custoValor = $itemValor['custo'];
                        $impressoes = $itemValor['impressoes'];
                        $contaNome  = $itemValor['itemContaNome'];
                        $contaID    = $itemValor['itemContaID'];
                        $status     = $itemValor['itemStatus'];
                    }
                }
                
                $campanhaStatus = 1;
                if ($status == 'PAUSED')
                    $campanhaStatus = 2;
                            
                if ($status == 'NOT_DELIVERING')
                    $campanhaStatus = 3;
                
                $clienteComissaoValor = 0;
                if (isset($arrComissao[$termo]))
                    $clienteComissaoValor = $arrComissao[$termo];
                    
                if ($custoValor > 0) {
                    
                    $impostoValor = ($receitaTotal / 100) * $impostoPorcentagem;
                    $receitaTotal = ($receitaTotal - $impostoValor) - $custoValor;
                    
                    $comissaoValor = $receitaTotal - ($receitaTotal - (($receitaTotal / 100) * $clienteComissaoValor));
                    
                    $lucroFinal = $receitaTotal - $comissaoValor;
                }
                
                $data = array(
                	'campanha_cacheCustoStatus'    => $campanhaStatus,
                	'campanhas_totalResultados'    => $impressoes,
                	'campanhas_totalComissaoValor' => $comissaoValor,
                	'campanhas_totalLucroFinal'    => $lucroFinal,
                	'campanhas_totalCustoValor'    => $custoValor,
                	'campanhas_totalImpressoes'    => $impressoes,
                	'campanhaNomeDaConta'          => $contaID,
                	'campanhaIDdaConta'            => $contaNome
               	);
               	
               	$_retorno = update('analytics_campanhas', $data, 'campanhaID = ' . $campanhaID);
            }
        }
    } 
         
    echo 'Finalizado';
    
} else if (isset($_GET['executar'])) {
    
    $aplicados = file_get_contents($arquivo);
    $aplicados = (array) json_decode($aplicados, true);
    $aplicados = array_filter($aplicados);

    $lista = file_get_contents($arquivoItens);
    $lista = (array) json_decode($lista, true);
    $lista = array_filter($lista);
    
    $contasTotal = count($lista);
    
    echo 'PENDETES: ' . $contasTotal . '<br /><br />';
        
    if ($contasTotal == 0) { 
        $link = $cronLink . '?comissoes'; ?>
    
        <p>Redirecionando...</p>
    
        <?php 
        if (!isset($_GET['geral'])) { ?>
            <script>
                window.location = '<?php echo $link; ?>';
            </script>
            
            <?php
        }

    } else {
        
        foreach ($lista as $itemIndex => $itemValor) {
                
            $analyticID       = $itemValor['analyticID'];
            $analyticContaID  = $itemValor['analyticContaID'];
            $analyticNome     = $itemValor['analyticNome'];
            $contaAccessToken = $itemValor['contaAccessToken'];
            $arrDatas         = $itemValor['datas'];
            
            if (count($arrDatas) == 0) {
                unset($lista[$itemIndex]);
                
                continue;
            }
                
            foreach ($arrDatas as $dataIndex => $campanhaData) {
                
                unset($lista[$itemIndex]['datas'][$dataIndex]);
                
                /* Definir Source */
                
                $campanhasLista = mysqli_query($con, "SELECT *
                    FROM `analytics_campanhas` 
                    WHERE 
                       campanha_date = '$campanhaData' AND
                        _analyticID  = '$analyticID'
                    LIMIT 10000;");
                    
                if ($campanhasLista) {
                    while ($campanhasListaValor = mysqli_fetch_array($campanhasLista)) { 
                        $campanhaID   = $campanhasListaValor['campanhaID'];
                        $campanhaNome = $campanhasListaValor['campanha_sessionCampaignName'];
                        
                        $origem = mysqli_query($con, "SELECT gestorPais_sessionSource
                            FROM analytics_gestor_pais
                            WHERE
                                gestorPais_date = '$campanhaData' AND 
                                gestorPais_sessionCampaignName = '$campanhaNome'
                        LIMIT 1;");
                        
                        if ($origem) {
                            $origemValor = mysqli_fetch_array($origem);
                            if (isset($origemValor['gestorPais_sessionSource'])) {
                                $dados = array(
                                    'campanha_sessionSourceMedium' => $origemValor['gestorPais_sessionSource']
                                );
                                
                                $retorno = update('analytics_campanhas', $dados, 'campanhaID = ' . $campanhaID);
                            }
                        }
                    }
                }
                
                /* Aplica */
                
                $arrTipos = array(
                    'facebook',
                    'tiktok'
                );
                
                foreach ($arrTipos as $tipoValor) {
                
                    $campanhasLista = mysqli_query($con, "SELECT 
                            campanhaManualTerm AS sessionCampaignName, campanha_sessionCampaignName
                        FROM `analytics_campanhas` 
                        WHERE 
                           campanha_sessionSourceMedium LIKE '%$tipoValor%' AND
                           campanha_date                  = '$campanhaData' AND
                            _analyticID                   = '$analyticID' 
                        GROUP BY campanhaManualTerm 
                        ORDER BY campanhaManualTerm ASC;");
                        
                    if ($campanhasLista) {
                        while ($campanhasListaValor = mysqli_fetch_array($campanhasLista)) { 
                            $sessionCampaignName          = $campanhasListaValor['sessionCampaignName'];
                            $campanha_sessionCampaignName = $campanhasListaValor['campanha_sessionCampaignName'];
                            
                            $sql = "SELECT SQL_CACHE *
                                FROM analytics_campanhas
                                WHERE
                                    campanha_sessionCampaignName LIKE '$sessionCampaignName%' AND
                                    campanha_date = '$campanhaData' AND 
                                    _analyticID   = $analyticID 
                                LIMIT 100;";
                                
                            $query = mysqli_query($con, $sql);
                            if ($query) {
                                while ($listaValor = mysqli_fetch_array($query)) { 
                                    $campanhaID   = $listaValor['campanhaID'];
                                    $campanhaNome = $listaValor['campanha_sessionCampaignName'];
                                    $manualTerm   = $listaValor['campanha_firstUserManualTerm'];
                                    
                                    $totalComissao = 0;
                            
                                    $_roiPaisesGeral = gestorCampanhaRoiGeral($campanhaNome);
                                    
                                    echo 'VERIFICANDO: ' . $campanhaNome . ' em ' . $campanhaData . ' - Tipo: ' . $tipoValor . '<br />';
                                    
                                    $dados           = array();
                                    $totalGeral      = 0;
                                    $totalCusto      = 0;
                                    $totalReceitaAD  = 0;
                                    $totalImposto    = 0;
                                    $totalCusto      = 0;
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
                                        SUM(IFNULL(gestorPaisLucroFinal, 0)) AS gestorPaisLucroFinal,
                                        SUM(IFNULL(gestorPais_tiktokConversions, 0)) AS gestorPais_tiktokConversions,
                                        SUM(IFNULL(gestorPais_tiktokTotalLandingPageView, 0)) AS gestorPais_tiktokTotalLandingPageView,
                                        SUM(IFNULL(gestorPais_tiktokCostperLandingPageView, 0)) AS gestorPais_tiktokCostperLandingPageView,
                                        SUM(IFNULL(gestorPais_tiktokCliques, 0)) AS gestorPais_tiktokCliques
                                        
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
                                          
                                            $dados['campanha_tiktokConversions']            = $campanhaValor['gestorPais_tiktokConversions'];
                                            $dados['campanha_tiktokTotalLandingPageView']   = $campanhaValor['gestorPais_tiktokTotalLandingPageView'];
                                            $dados['campanha_tiktokCostperLandingPageView'] = $campanhaValor['gestorPais_tiktokCostperLandingPageView'];
                                            $dados['campanha_tiktokImpressoes']             = $campanhaValor['gestorPaisImpressoes'];
                                            $dados['campanha_tiktokCliques']                = $campanhaValor['gestorPais_tiktokCliques'];
                                            
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
                                            // $dados['campanha_cacheCustoStatus']    = $campanhaValor['gestorPaisCustoStatus'];
                                            $dados['campanha_cacheAnalyticNome']   = $campanhaValor['analyticNome'];
                                            
                                            $totalComissao = $totalComissao + $campanhaValor['gestorPaisComissaoValor'];
                                            
                                            /*
                                            if ($campanhaNome == 'testegravideztiktokEN6' && $campanhaData == '2023-06-14') {
                                                
                                                echo '<pre>';
                                                print_r($campanhaValor);
                                                print_r($dados);
                                                echo '</pre>';
                                                
                                                exit;
                                            } */
                                        }
                                    }
                                    
                                    $campanhaLinksTotal        = '';
                                    $campanhaGoogleadsTotal    = '';
                                    $campanhaPaisTotal         = '';
                                    $gestorPaisCustoValorGeral = 0;
                                    $totalAdRevenueGeral       = 0;
                                    
                                    $_gestorPaisCustoValorGeral = 0;
                                    $_totalAdRevenueGeral       = 0;
                                    
                                    $roiPaises      = '';
                                    $roiPaisesGeral = array();
                                    
                                    $rois = mysqli_query($con, "SELECT *
                                        FROM analytics_gestor_pais
                                        WHERE
                                            gestorPais_sessionCampaignName = '$campanhaNome' AND
                                            gestorPais_date                = '$campanhaData'
                                        GROUP BY gestorPais_country
                                        ORDER BY gestorPais_country ASC;");
                                        
                                    if ($rois) {
                                        if (mysqli_num_rows($rois) > 0) { 
                                            while ($roiItem = mysqli_fetch_array($rois)) { 
                                                $gestorPais_date           = $roiItem['gestorPais_date']; 
                                                $gestorPais_country        = $roiItem['gestorPais_country']; 
                                                $gestorPaisCustoValor      = $roiItem['gestorPaisCustoValor'];
                                                $gestorPais_totalAdRevenue = $roiItem['gestorPais_totalAdRevenue'];
                                                
                                                $_gestorPaisCustoValorGeral = $_gestorPaisCustoValorGeral + $gestorPaisCustoValor;
                                                $_totalAdRevenueGeral       = $_totalAdRevenueGeral       + $gestorPais_totalAdRevenue;
                                                
                                                $totalAdRevenueGeral = 0;
                                                if (isset($roiPaisesGeral[$gestorPais_country]))
                                                    $totalAdRevenueGeral = $roiPaisesGeral[$gestorPais_country];
                                                    
                                                $paisCustoValorGeral = 0;
                                                if (isset($roiPaisesGeral[$gestorPais_country]))
                                                    $paisCustoValorGeral = $roiPaisesGeral[$gestorPais_country];
                                                    
                                                $totalAdRevenueGeral = $totalAdRevenueGeral + $gestorPais_totalAdRevenue;
                                                $paisCustoValorGeral = $paisCustoValorGeral + $gestorPaisCustoValor;
                                                
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
                                                    
                                                    $roiPaisesGeral[$gestorPais_country] = array(
                                                        'totalAdRevenueGeral' => $totalAdRevenueGeral,
                                                        'custoValorGeral'     => $paisCustoValorGeral
                                                    );
                                                }
                                            }
                                        }
                                    }
                                    
                                    $_campanhaCustoValor = 0;
                                                            
                                    $custos = mysqli_query($con, "SELECT *
                                        FROM cliente_campanhas_custo
                                        WHERE
                                            _campanhaID = $campanhaID
                                    LIMIT 1;");
                                    
                                    if ($custos) {
                                        $custoItem = mysqli_fetch_array($custos);
                                        if (isset($custoItem['campanhaCustoID'])) {
                                            $_campanhaCustoValor = $custoItem['campanhaCustoValor'];
                                        }
                                    }
                                    
                                    $origem = mysqli_query($con, "SELECT gestorPais_sessionSource
                                        FROM analytics_gestor_pais
                                        WHERE
                                            gestorPais_date = '$campanhaData' AND 
                                            gestorPais_sessionCampaignName = '$campanhaNome'
                                    LIMIT 1;");
                                    
                                    if ($origem) {
                                        $origemValor = mysqli_fetch_array($origem);
                                        if (isset($origemValor['gestorPais_sessionSource'])) {
                                            $dados['campanha_sessionSourceMedium'] = $origemValor['gestorPais_sessionSource'];
                                        }
                                    }
                                    
                                    $dados['campanhaCacheCustoValor'] = $_campanhaCustoValor;
                                    $dados['campanha_roiPaises']      = $roiPaises;
                                    $dados['campanha_roiPaisesGeral'] = json_encode($_roiPaisesGeral);
                                    
                                    $retorno = update('analytics_campanhas', $dados, 'campanhaID = ' . $campanhaID);
                                    if (!$retorno) {
                                        echo 'ERRO: '. mysqli_error($con);
                                        exit;
                                    }
                                }
                            }
                        }
                    }
                }
                
                break 2;
            }
        }
    }
    
    file_put_contents($arquivoItens, json_encode($lista));
    
    $link = $cronLink . '?executar'; ?>
    
    <p>Redirecionando...</p>
    
    <?php 
    if (!isset($_GET['geral'])) { ?>
        <script>
            window.location = '<?php echo $link; ?>';
        </script>
        
        <?php
    }
    
    exit;
    
} else { ?>

    <p>
        <a href="<?php echo $cronLink . '?iniciar'; ?>">Iniciar</a>
    </p>

    <?php
}