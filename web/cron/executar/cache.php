<?php 
header("Access-Control-Allow-Origin: *");

include('../../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$horaAtual = date('H');

$impostoPorcentagem = getConfig('imposto_porcentagem');
if (empty($impostoPorcentagem))
    $impostoPorcentagem = 10;
    
$dolarHoje = getConfig('dolar_valor');

$arrDatas = array(
    date('Y-m-d')
);

if ($horaAtual < 5)
    $arrDatas[] = date('Y-m-d', strtotime('-1 day'));
 
/*   
for ($x = 1; $x < 31; $x++) {
    $arrDatas[] = '2023-09-' . str_pad($x, 2, '0', STR_PAD_LEFT);;
} */
    
$_arrUtm = array(); 
            
$query = mysqli_query($con, "SELECT *
    FROM clientes
    LIMIT 100;");

if ($query) {
    while ($lista = mysqli_fetch_array($query)) { 
        $clienteUtmTerm = $lista['clienteUtmTerm'];
        $clienteID      = $lista['clienteID'];
        
        $arrUtm = explode(',', $clienteUtmTerm);
        $arrUtm = array_filter($arrUtm);
        
        foreach ($arrUtm as $utmValor) {
            $_arrUtm[$clienteID][] = trim($utmValor);
        }
    }
}

foreach ($arrDatas as $dataValor) {

    $itens = mysqli_query($con, "SELECT *
        FROM adx_relatorios 
        WHERE 
            relatorioData    = '$dataValor' AND 
            (relatorioUtmTipo = 'campaign_id' OR relatorioUtmTipo = 'adset_id' OR relatorioUtmTipo = 'ad_id') ");
            
    if ($itens) {
        $total = mysqli_num_rows($itens);
        
        while ($itemValor = mysqli_fetch_array($itens)) {
            $clienteID                     = $itemValor['_clienteID'];
            $relatorioID                   = $itemValor['relatorioID'];
            $relatorioUtmValor             = $itemValor['relatorioUtmValor'];
            $relatorioUtmTipo              = $itemValor['relatorioUtmTipo'];
            $relatorioReceitaTotal         = $itemValor['relatorioReceitaTotal'];
            $relatorioClienteComissaoValor = $itemValor['relatorioClienteComissaoValor'];
            $relatorioCampanhaNome         = $itemValor['relatorioCampanhaNome'];
            $analyticID                    = 0;
            $campanhaNome                  = 0; 
            $clienteTerm                   = 0; 
            $custoValor                    = 0;
            $facebookVisualizacoes         = 0;
            $facebookImpressoes            = 0;
            $facebookCliques               = 0;
            $comissaoValor                 = 0;
            $campanhaStatus                = '';
            $contaID                       = 0;
            $contaNome                     = '';
            $lucroFinal                    = 0;
            $clienteComissao               = 0;
            $impostoValor                  = 0;
            $campanhaID                    = 0;
            $custoValor                    = 0;
            $receitaValor                  = 0;
            $adname                        = '';
            $paisNome                      = '';
            $paisSigla                     = '';
            $relatorioUtmSource            = '';
            $relatorioRoiFinalValor        = '';
            $relatorioRoiFinalClass        = '';
            $relatorioRoiGeralValor        = '';
            $relatorioRoiGeralClass        = '';
            
            /* Cliente comissao */
            if ($relatorioClienteComissaoValor > 0) {
                $clienteComissao = $relatorioClienteComissaoValor;
            } else {
                
                if ($clienteID > 0) {
                    $clientes = mysqli_query($con, "SELECT *
                        FROM clientes
                        WHERE 
                            clienteID = $clienteID
                        LIMIT 1;");
                    
                    if ($clientes) {
                        while ($clienteValor = mysqli_fetch_array($clientes)) { 
                            $clienteComissao = $clienteValor['clienteComissao'];
                        }
                    }
                }
            }
            
            if ($relatorioUtmTipo == 'adset_id') {
                
                $utms = mysqli_query($con, "SELECT *
                    FROM gestao_utms 
                    WHERE 
                        gestaoUtm_adset_id = '$relatorioUtmValor'
                    LIMIT 1");
                        
                if ($utms) {
                    $utmValor = mysqli_fetch_array($utms);
                    if (isset($utmValor['gestaoUtmID'])) {
                        $clienteTerm        = $utmValor['gestaoUtm_utm_term']; 
                        $campanhaNome       = $utmValor['gestaoUtm_campaign_name']; 
                        $campanhaID         = $utmValor['gestaoUtm_campaign_id']; 
                        $adname             = $utmValor['gestaoUtm_ad_name']; 
                        $relatorioUtmSource = $utmValor['gestaoUtm_utm_source']; 
                        $analyticID         = $utmValor['_analyticID']; 
                        
                        foreach ($_arrUtm as $utmIndex => $arrUtms) {
                            if (in_array($clienteTerm, $arrUtms)) {
                                $clienteID = $utmIndex;
                            }
                        }
                    }
                }
                
                $lista = mysqli_query($con, "SELECT
                    criativoPaisNome,
                    criativoPaisSigla,
                    SUM(criativoCusto) AS itemCustoValor,
                    SUM(criativoVisualizacoesPaginas) AS itemVisualizacoes,
                    SUM(criativoImpressoes) AS itemImpressoes,
                    SUM(criativoCliques) AS itemCliques
                FROM `facebook_criativos` 
                WHERE 
                    criativoAdsetID = '$relatorioUtmValor' AND
                    criativoData    = '$dataValor' ");
                    
                if ($lista) {
                    $facebookValor = mysqli_fetch_array($lista);
                    if (isset($facebookValor['itemCustoValor'])) {
                        $custoValor            = (float) $facebookValor['itemCustoValor'];
                        $facebookVisualizacoes = $facebookValor['itemVisualizacoes'];
                        $facebookImpressoes    = $facebookValor['itemImpressoes']; 
                        $facebookCliques       = $facebookValor['itemCliques']; 
                        $paisNome              = $facebookValor['criativoPaisNome'];
                        $paisSigla             = $facebookValor['criativoPaisSigla'];
                    }
                }
                
                $facebook = mysqli_query($con, "SELECT *
                    FROM gestao_utms 
                        INNER JOIN facebook_itens ON itemCampanhaNome = gestaoUtm_campaign_name 
                    WHERE 
                        gestaoUtm_adset_id = '$relatorioUtmValor' 
                    ORDER BY itemID DESC 
                    LIMIT 1; ");
                    
                if ($facebook) {
                    $facebookValor = mysqli_fetch_array($facebook);
                    if (isset($facebookValor['itemID'])) {
                        $campanhaStatus = $facebookValor['itemStatus']; 
                        $contaID        = $facebookValor['itemContaID'];  
                        $contaNome      = $facebookValor['itemContaNome'];  
                    }
                }
                
                $relatorioReceitaTotal = 0;
            
                $lista = mysqli_query($con, "SELECT SUM(relatorioReceitaTotal) AS relatorioReceitaTotal 
                    FROM 
                        `adx_relatorios` 
                    WHERE 
                        `relatorioUtmValor` = '$relatorioUtmValor' AND 
                        `relatorioUtmTipo`  = 'adset_id' AND 
                        `relatorioData`     = '$dataValor';");
                        
                if ($lista) {
                    $facebookValor = mysqli_fetch_array($lista);
                    if (isset($facebookValor['relatorioReceitaTotal'])) {
                        $relatorioReceitaTotal = (float) $facebookValor['relatorioReceitaTotal']; 
                    }
                }
                
                if ($custoValor > 0) {
                    $_totalAdRevenue  = $relatorioReceitaTotal; 
                    $impostoValor     = ($_totalAdRevenue / 100) * $impostoPorcentagem;
                    $_totalAdRevenue  = ($_totalAdRevenue - $impostoValor) - $custoValor;
                    $comissaoValor    = $_totalAdRevenue - ($_totalAdRevenue - (($_totalAdRevenue / 100) * $clienteComissao));
                    
                    $lucroFinal       = $_totalAdRevenue - $comissaoValor;
                }
                
                $custoValor   = $custoValor;
                $receitaValor = $relatorioReceitaTotal;
            }
             
            if ($relatorioUtmTipo == 'ad_id') {
                
                $utms = mysqli_query($con, "SELECT *
                    FROM gestao_utms 
                    WHERE 
                        gestaoUtm_ad_id = '$relatorioUtmValor'
                    LIMIT 1");
                        
                if ($utms) {
                    $utmValor = mysqli_fetch_array($utms);
                    if (isset($utmValor['gestaoUtmID'])) {
                        $clienteTerm        = $utmValor['gestaoUtm_utm_term']; 
                        $campanhaNome       = $utmValor['gestaoUtm_campaign_name']; 
                        $campanhaID         = $utmValor['gestaoUtm_campaign_id']; 
                        $adname             = $utmValor['gestaoUtm_ad_name']; 
                        $relatorioUtmSource = $utmValor['gestaoUtm_utm_source']; 
                        $analyticID         = $utmValor['_analyticID']; 
                        
                        foreach ($_arrUtm as $utmIndex => $arrUtms) {
                            if (in_array($clienteTerm, $arrUtms)) {
                                $clienteID = $utmIndex;
                            }
                        }
                    }
                }
                
                $lista = mysqli_query($con, "SELECT
                    criativoPaisNome,
                    criativoPaisSigla,
                    SUM(criativoCusto) AS itemCustoValor,
                    SUM(criativoVisualizacoesPaginas) AS itemVisualizacoes,
                    SUM(criativoImpressoes) AS itemImpressoes,
                    SUM(criativoCliques) AS itemCliques
                FROM `facebook_criativos` 
                WHERE 
                    criativoAdID = '$relatorioUtmValor' AND
                    criativoData = '$dataValor' ");
                    
                if ($lista) {
                    $facebookValor = mysqli_fetch_array($lista);
                    if (isset($facebookValor['itemCustoValor'])) {
                        $custoValor            = (float) $facebookValor['itemCustoValor'];
                        $facebookVisualizacoes = $facebookValor['itemVisualizacoes'];
                        $facebookImpressoes    = $facebookValor['itemImpressoes']; 
                        $facebookCliques       = $facebookValor['itemCliques']; 
                        $paisNome              = $facebookValor['criativoPaisNome'];
                        $paisSigla             = $facebookValor['criativoPaisSigla'];
                    }
                }
                
                $lista = mysqli_query($con, "SELECT *
                    FROM gestao_utms 
                        INNER JOIN facebook_itens ON itemCampanhaNome = gestaoUtm_campaign_name 
                    WHERE 
                        gestaoUtm_ad_id = '$relatorioUtmValor' 
                    ORDER BY itemID DESC 
                    LIMIT 1; ");
                    
                if ($lista) {
                    $facebookValor = mysqli_fetch_array($lista);
                    if (isset($facebookValor['itemID'])) {
                        $campanhaStatus = $facebookValor['itemStatus']; 
                        $contaID        = $facebookValor['itemContaID'];  
                        $contaNome      = $facebookValor['itemContaNome'];  
                    }
                }
                
                $relatorioReceitaTotal = 0;
            
                $lista = mysqli_query($con, "SELECT SUM(relatorioReceitaTotal) AS relatorioReceitaTotal 
                    FROM 
                        `adx_relatorios` 
                    WHERE 
                        `relatorioUtmValor` = '$relatorioUtmValor' AND 
                        `relatorioUtmTipo`  = 'ad_id' AND 
                        `relatorioData`     = '$dataValor';");
                        
                if ($lista) {
                    $facebookValor = mysqli_fetch_array($lista);
                    if (isset($facebookValor['relatorioReceitaTotal'])) {
                        $relatorioReceitaTotal = (float) $facebookValor['relatorioReceitaTotal']; 
                    }
                }

                if ($custoValor > 0) {
                    $_totalAdRevenue  = $relatorioReceitaTotal; 
                    $impostoValor     = ($_totalAdRevenue / 100) * $impostoPorcentagem;
                    $_totalAdRevenue  = ($_totalAdRevenue - $impostoValor) - $custoValor;
                    $comissaoValor    = $_totalAdRevenue - ($_totalAdRevenue - (($_totalAdRevenue / 100) * $clienteComissao));
                    
                    $lucroFinal       = $_totalAdRevenue - $comissaoValor;
                }
                
                $custoValor   = $custoValor;
                $receitaValor = $relatorioReceitaTotal;
            }
            
            if ($relatorioUtmTipo == 'campaign_id') {
                $campanhaID = $relatorioUtmValor;
                
                $utms = mysqli_query($con, "SELECT *
                    FROM gestao_utms 
                    WHERE 
                        gestaoUtm_campaign_id = '$relatorioUtmValor'
                    LIMIT 1");
                        
                if ($utms) {
                    $utmValor = mysqli_fetch_array($utms);
                    if (isset($utmValor['gestaoUtmID'])) {
                        $clienteTerm        = $utmValor['gestaoUtm_utm_term']; 
                        $campanhaNome       = $utmValor['gestaoUtm_campaign_name']; 
                        $adname             = $utmValor['gestaoUtm_ad_name']; 
                        $relatorioUtmSource = $utmValor['gestaoUtm_utm_source']; 
                        $analyticID         = $utmValor['_analyticID']; 
                        
                        foreach ($_arrUtm as $utmIndex => $arrUtms) {
                            if (in_array($clienteTerm, $arrUtms)) {
                                $clienteID = $utmIndex;
                            }
                        }
                    }
                }
                
                $_sql = "SELECT 
                    itemPaisNome,
                    itemPaisSingla,
                    SUM(itemCustoValor) AS itemCustoValor,
                    SUM(itemVisualizacoes) AS itemVisualizacoes,
                    SUM(itemImpressoes) AS itemImpressoes,
                    SUM(itemCliques) AS itemCliques
                FROM facebook_itens 
                WHERE 
                    itemCampanhaID = '$relatorioUtmValor' AND 
                    itemData       = '$dataValor' ";
                    
                $facebook = mysqli_query($con, $_sql);
                    
                if ($facebook) {
                    $facebookValor = mysqli_fetch_array($facebook);
                    
                    if (isset($facebookValor['itemCustoValor'])) {
                        $custoValor            = (float) $facebookValor['itemCustoValor'];
                        $facebookVisualizacoes = $facebookValor['itemVisualizacoes'];
                        $facebookImpressoes    = $facebookValor['itemImpressoes']; 
                        $facebookCliques       = $facebookValor['itemCliques']; 
                        $paisNome              = $facebookValor['itemPaisNome'];
                        $paisSigla             = $facebookValor['itemPaisSingla'];
                    }
                }
                
                
                if ($relatorioUtmSource == 'tiktok') {   
                    
                    $tiktok = mysqli_query($con, "SELECT SUM(custoValor) AS custo
                        FROM tiktok_custos 
                        WHERE  
                            custoCampanhaID = '$campanhaID' AND 
                            custoData       = '$dataValor' ");
                            
                    if ($tiktok) {
                        $tiktokValor = mysqli_fetch_array($tiktok);
                        if (isset($tiktokValor['custo'])) {
                            $custoValor = (float) $tiktokValor['custo'];
                        }
                    }
                    
                } else {
                
                    $facebook = mysqli_query($con, "SELECT *
                        FROM facebook_itens 
                        WHERE 
                            itemCampanhaID = '$relatorioUtmValor'
                        ORDER BY itemID DESC
                        LIMIT 1");
                        
                    if ($facebook) {
                        $facebookValor = mysqli_fetch_array($facebook);
                        if (isset($facebookValor['itemID'])) {
                            $campanhaStatus = $facebookValor['itemStatus']; 
                            $contaID        = $facebookValor['itemContaID'];  
                            $contaNome      = $facebookValor['itemContaNome'];  
                        }
                    }
                }
                
                if ($custoValor > 0) {
                    $_totalAdRevenue  = $relatorioReceitaTotal; 
                    $impostoValor     = ($_totalAdRevenue / 100) * $impostoPorcentagem;
                    $_totalAdRevenue  = ($_totalAdRevenue - $impostoValor) - $custoValor;
                    $comissaoValor    = $_totalAdRevenue - ($_totalAdRevenue - (($_totalAdRevenue / 100) * $clienteComissao));
                    
                    $lucroFinal       = $_totalAdRevenue - $comissaoValor;
                }
                
                $custoValor   = $custoValor;
                $receitaValor = $relatorioReceitaTotal;
                
                $roiSql = "SELECT *
                    FROM adx_relatorios 
                    WHERE 
                        relatorioUtmValor = '$relatorioUtmValor'
                    ORDER BY relatorioData DESC
                    LIMIT 30";
                
                $roiItens = mysqli_query($con, $roiSql);
                if ($roiItens) {
                    while ($roiItemValor = mysqli_fetch_array($roiItens)) {
                        
                        $roiData               = $roiItemValor['relatorioData'];
                        $roiCustoValor         = $roiItemValor['relatorioCustoValor'];
                        $roiLucroFinalValor    = $roiItemValor['relatorioLucroFinal'];
                        $roiComissaoValor      = $roiItemValor['relatorioComissaoValor'];
                        $relatorioReceitaTotal = $roiItemValor['relatorioReceitaTotal'];
                        
                        $_roiReceitaTotal    = $_roiReceitaTotal    + $relatorioReceitaTotal;
                        $_roiCustoValor      = $_roiCustoValor      + $roiCustoValor;
                        $_roiLucroFinalValor = $_roiLucroFinalValor + $roiLucroFinalValor;
                        $_roiComissaoValor   = $_roiComissaoValor   + $roiComissaoValor;
                    }
                } 
                
                $relatorioRoiGeralValor = 0;
                if ($_roiCustoValor > 0)
                    $relatorioRoiGeralValor = $_roiReceitaTotal / $_roiCustoValor;
                    
            	$relatorioRoiGeralClass = '';
            	
            	if ($relatorioRoiGeralValor >= 2.00) { 
                    $relatorioRoiGeralClass = 'label-success';
                } else if ($relatorioRoiGeralValor < 0.90) {
                    $relatorioRoiGeralClass = 'label-danger';
                } else if ($relatorioRoiGeralValor < 1.11) { 
                    $relatorioRoiGeralClass = 'label-warning';
                } else if ($relatorioRoiGeralValor < 2.00) { 
                    $relatorioRoiGeralClass = 'label-info';
                } 
            }
            
            $valor = 0;
            if ($custoValor > 0)
                $valor = $relatorioReceitaTotal / $custoValor;
            
            $valor = (float) $valor;
            $valor = number_format($valor, 2, '.', '');
                
        	$labelNome = '';
        	
        	if ($valor >= 2.00) { 
                $labelNome = 'label-success';
            } else if ($valor < 0.90) {
                $labelNome = 'label-danger';
            } else if ($valor < 1.11) { 
                $labelNome = 'label-warning';
            } else if ($valor < 2.00) { 
                $labelNome = 'label-info';
            } 
            
            $relatorioRoiFinalValor = $valor;
            $relatorioRoiFinalClass = $labelNome;
            
            // Historico do roi
            
            // Remove anteriores
            
            mysqli_query($con, "DELETE FROM adx_roi_historico 
                WHERE DATE(roiData) < CURDATE()");
            
            // Cadastra atual
            
            $roi = mysqli_query($con, "SELECT * 
                FROM adx_roi_historico 
                WHERE 
                    DATE(roiData) = CURDATE() AND
                    _relatorioID  = $relatorioID 
                ORDER BY roiData DESC");
                    
            if ($roi) {
                $cadastraRoi = false;
                
                if (mysqli_num_rows($roi) == 0) {
                    $cadastraRoi = true;
                } else {
                    $roiValor = mysqli_fetch_array($roi);
                    if (isset($roiValor['roiID'])) {
                        $roiData = $roiValor['roiData'];
                        if (strtotime($roiData) < strtotime('-3 hour')) {
                            $cadastraRoi = true;        
                        }
                    }
                }
                
                if ($cadastraRoi) {
                    insert('adx_roi_historico', array(
                        'roiValor'        => $valor,
                        'roiLabel'        => $labelNome,
                        'roiData'         => date('Y-m-d H:i:s'),
                        'roiTipo'         => $relatorioUtmTipo,
                        'roiCodigo'       => $relatorioUtmValor,
                        'roiCampanhaNome' => $relatorioCampanhaNome,
                        'roiCustoValor'	  => $custoValor,
            	        'roiReceitaValor' => $receitaValor,
            	        'roiCampanhaID'   => $campanhaID,
            	        'roiAdName'       => $adname,
            	        'roiPaisNome'     => $paisNome,
            	        'roiPaisSigla'    => $paisSigla,
                        '_relatorioID'    => $relatorioID
                    ));
                }
            }
            
            $relatorioDiasAtivo = 0;
            
            $diasAtivo = mysqli_query($con, "SELECT *
                FROM adx_relatorios 
                WHERE 
                    relatorioUtmTipo  = 'campaign_id' AND 
                    relatorioUtmValor = '$relatorioUtmValor' ");
                    
            if ($diasAtivo) {
                $relatorioDiasAtivo = mysqli_num_rows($diasAtivo);
            }
            
            $data = array(
                'relatorioDiasAtivo'                => $relatorioDiasAtivo,
                'relatorioRoiFinalValor'            => $relatorioRoiFinalValor,
                'relatorioRoiFinalClass'            => $relatorioRoiFinalClass,
                'relatorioUtmSource'                => $relatorioUtmSource,
                'relatorioCustoValor'               => $custoValor,
                'relatorioClienteTerm'              => $clienteTerm,
                'relatorioCampanhaNome'             => $campanhaNome,
                'relatorioComissaoValor'            => $comissaoValor,
                'relatorioLucroFinal'               => $lucroFinal,
                'relatorioDolarHoje'                => $dolarHoje,
                'relatorioCampanhaStatus'           => $campanhaStatus,
                'relatorioContaNome'                => $contaNome,
                'relatorioFacebookCliques'          => $facebookCliques,
                'relatorioFacebookImpressoes'       => $facebookImpressoes,
                'relatorioFacebookVisualizacoes'    => $facebookVisualizacoes,
                'relatorioClienteComissaoValor'     => $clienteComissao,
                'relatorioImpostoValor'             => $impostoValor,
                'relatorioRoiGeralValor'            => $relatorioRoiGeralValor,
                'relatorioRoiGeralClass'            => $relatorioRoiGeralClass,
                '_clienteID'                        => $clienteID,
                '_analyticID'                       => $analyticID, 
                '_contaID'                          => $contaID
            );
            
            $__retorno = update('adx_relatorios', $data, 'relatorioID = ' . $relatorioID);
        }
    }
}

/* Cache ganhos */

mysqli_query($con, "TRUNCATE TABLE cliente_cache_ganhos;");

$impostoPorcentagem = getConfig('imposto_porcentagem');
if (empty($impostoPorcentagem))
    $impostoPorcentagem = 10;
    
$dolarHoje = getConfig('dolar_valor');

$arrSocial = array(
    'facebook' => 'Facebook',
    'tiktok'   => 'Tiktok'
);

$clientes = mysqli_query($con, "SELECT *
	FROM clientes
	ORDER BY clienteNome DESC;");

if ($clientes) {
    while ($clienteValor = mysqli_fetch_array($clientes)) { 
        $clienteID   = $clienteValor['clienteID']; 
        $clienteTipo = $clienteValor['clienteTipo'];
        
        $clienteComissao = 10;
        
        if ($clienteID > 0) {
            $comissao = mysqli_query($con, "SELECT *
                FROM clientes
                WHERE 
                    clienteID = $clienteID
                LIMIT 1;");
            
            if ($comissao) {
                while ($comissaoItem = mysqli_fetch_array($comissao)) { 
                    $clienteComissao = $comissaoItem['clienteComissao'];
                }
            }
        }
                        
        foreach ($arrSocial as $socialIndex => $socialNome) {
            
            $sql = "SELECT 
                
                IFNULL((SELECT 
                    SUM(relatorioReceitaTotal)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    DATE(relatorioData) = CURDATE() AND 
                    _clienteID = $clienteID), 0) AS receitaHoje,
                        
                IFNULL((SELECT 
                    SUM(relatorioReceitaTotal)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    DATE(relatorioData) = CURDATE() - INTERVAL 1 DAY AND 
                    _clienteID = $clienteID), 0) AS receitaOntem,
                                
                IFNULL((SELECT 
                    SUM(relatorioReceitaTotal)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    MONTH(relatorioData) = MONTH(CURRENT_DATE()) AND 
                    YEAR(relatorioData)  = YEAR(CURRENT_DATE()) AND
                    _clienteID = $clienteID), 0) AS receitaMesAtual,
                                
                IFNULL((SELECT 
                    SUM(relatorioReceitaTotal)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    YEAR(relatorioData) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND 
                    MONTH(relatorioData) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) AND
                    _clienteID = $clienteID), 0) AS receitaMesAnterior,
                     
                IFNULL((SELECT 
                    SUM(relatorioCustoValor)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    DATE(relatorioData) = CURDATE() AND 
                    _clienteID = $clienteID), 0) AS custosHoje,
                                 
                IFNULL((SELECT 
                    SUM(relatorioCustoValor)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    DATE(relatorioData) = CURDATE() - INTERVAL 1 DAY AND 
                    _clienteID = $clienteID), 0) AS custosOntem,
                    
                IFNULL((SELECT 
                    SUM(relatorioCustoValor)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    MONTH(relatorioData) = MONTH(CURRENT_DATE()) AND 
                    YEAR(relatorioData)  = YEAR(CURRENT_DATE()) AND
                    _clienteID = $clienteID), 0) AS custosMesAtual,
                    
                IFNULL((SELECT 
                    SUM(relatorioCustoValor)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    YEAR(relatorioData) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND 
                    MONTH(relatorioData) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) AND
                    _clienteID = $clienteID), 0) AS custosMesAnterior";
                    
            $query = mysqli_query($con, $sql);
            
            if ($query) {
                $itemValor = mysqli_fetch_array($query);
                if (isset($itemValor['receitaHoje'])) {
                    
                    $receitaHoje        = $itemValor['receitaHoje'];
                    $receitaOntem       = $itemValor['receitaOntem'];
                    $receitaMesAtual    = $itemValor['receitaMesAtual'];
                    $receitaMesAnterior = $itemValor['receitaMesAnterior'];
                    
                    $custosHoje         = $itemValor['custosHoje'];
                    $custosOntem        = $itemValor['custosOntem'];
                    $custosMesAtual     = $itemValor['custosMesAtual'];
                    $custosMesAnterior  = $itemValor['custosMesAnterior'];
                    
                    $lucroHoje          = 0;
                    $lucroOntem         = 0;
                    $lucroMesAtual      = 0;
                    $lucroMesAnterior   = 0;
                    
                    $ganhosHoje         = 0;
                    $ganhosOntem        = 0;
                    $ganhosMesAtual     = 0;
                    $ganhosMesAnterior  = 0;
                    
                    if ($custosHoje > 0) {
                        $totalAdRevenue  = $receitaHoje; 
                        $impostoValor    = ($totalAdRevenue / 100) * $impostoPorcentagem;
                        $totalAdRevenue  = ($totalAdRevenue - $impostoValor) - $custosHoje;
                        $ganhosHoje      = $totalAdRevenue - ($totalAdRevenue - (($totalAdRevenue / 100) * $clienteComissao));
                        
                        $lucroHoje       = $totalAdRevenue - $ganhosHoje;
                    }
                
                    if ($custosOntem > 0) {
                        $totalAdRevenue  = $receitaOntem; 
                        $impostoValor    = ($totalAdRevenue / 100) * $impostoPorcentagem;
                        $totalAdRevenue  = ($totalAdRevenue - $impostoValor) - $custosOntem;
                        $ganhosOntem     = $totalAdRevenue - ($totalAdRevenue - (($totalAdRevenue / 100) * $clienteComissao));
                         
                        $lucroOntem      = $totalAdRevenue - $ganhosOntem;
                    }
                
                    if ($custosMesAtual > 0) {
                        $totalAdRevenue  = $receitaMesAtual; 
                        $impostoValor    = ($totalAdRevenue / 100) * $impostoPorcentagem;
                        $totalAdRevenue  = ($totalAdRevenue - $impostoValor) - $custosMesAtual;
                        $ganhosMesAtual  = $totalAdRevenue - ($totalAdRevenue - (($totalAdRevenue / 100) * $clienteComissao));
                         
                        $lucroMesAtual   = $totalAdRevenue - $ganhosMesAtual;
                    }
                    
                    if ($custosMesAnterior > 0) {
                        $totalAdRevenue    = $receitaMesAnterior; 
                        $impostoValor      = ($totalAdRevenue / 100) * $impostoPorcentagem;
                        $totalAdRevenue    = ($totalAdRevenue - $impostoValor) - $custosMesAnterior;
                        $ganhosMesAnterior = $totalAdRevenue - ($totalAdRevenue - (($totalAdRevenue / 100) * $clienteComissao));
                         
                        $lucroMesAnterior = $totalAdRevenue - $ganhosMesAnterior;
                    }
                    
                    $arrInserir = array(
                        'receita' => array(
                            'hoje'        => $itemValor['receitaHoje'],
                            'ontem'       => $itemValor['receitaOntem'],
                            'mesAtual'    => $itemValor['receitaMesAtual'],
                            'mesAnterior' => $itemValor['receitaMesAnterior']
                        ),
                        
                        'lucro' => array(
                            'hoje'        => $lucroHoje,
                            'ontem'       => $lucroOntem,
                            'mesAtual'    => $lucroMesAtual,
                            'mesAnterior' => $lucroMesAnterior
                        ),
                        
                        'custo' => array(
                            'hoje'        => $itemValor['custosHoje'],
                            'ontem'       => $itemValor['custosOntem'],
                            'mesAtual'    => $itemValor['custosMesAtual'],
                            'mesAnterior' => $itemValor['custosMesAnterior']
                        ),
                        
                        'comissao' => array(
                            'hoje'        => $ganhosHoje,
                            'ontem'       => $ganhosOntem,
                            'mesAtual'    => $ganhosMesAtual,
                            'mesAnterior' => $ganhosMesAnterior
                        )
                    );
                    
                    foreach ($arrInserir as $inserirTipo => $inserirValor) {
                        $data = array(
                            'cacheValorHoje'        => $inserirValor['hoje'],
                            'cacheValorOntem'       => $inserirValor['ontem'],	
                            'cacheValorMesAtual'    => $inserirValor['mesAtual'],
                            'cacheValorMesAnterior' => $inserirValor['mesAnterior'],
                            'cacheRede'	            => $socialIndex,
                            'cacheTipo'	            => $inserirTipo,
                            'cacheData'	            => date('Y-m-d H:i:s'),
                            '_clienteID'            => $clienteID
                        );
                        
                        insert('cliente_cache_ganhos', $data);
                    }
                }
            }
        }
    }
}

echo 'parar';