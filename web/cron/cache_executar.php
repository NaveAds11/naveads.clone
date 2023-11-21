<?php 
header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$horaAtual = date('H');

$impostoPorcentagem = getConfig('imposto_porcentagem');
if (empty($impostoPorcentagem))
    $impostoPorcentagem = 10;
    
$dolarHoje = getConfig('dolar_valor');

$pasta    = ABSPATH . 'cron/arquivos/cache/';
$arquivos = glob($pasta . '*.txt');

$arrArquivos = array();
foreach ($arquivos as $arquivoValor) {
    if (preg_match('/campanha-(\d+)\.txt/', $arquivoValor)) {
        $arrArquivos[] = $arquivoValor;
    }
}

shuffle($arrArquivos);

$total = count($arrArquivos);
if ($total == 0) {
    echo 'parar';
} else {
    
    $utms    = file_get_contents($pasta . 'utms.txt');
    $utms    = (array) json_decode($utms, true);
    $_arrUtm = array_filter($utms);
    
    foreach ($arrArquivos as $arquivo) {
        if (is_file($arquivo)) {
            $html = file_get_contents($arquivo);
            $json = (array) json_decode($html, true);
            $json = array_filter($json);
            
            unlink($arquivo);
            
            foreach ($json as $jsonValor) {
                $dataValor   = $jsonValor['dataValor'];
                $relatorioID = $jsonValor['relatorioID'];
                
                $relatorios = mysqli_query($con, "SELECT * 
                    FROM adx_relatorios 
                    WHERE 
                        relatorioID = $relatorioID 
                    LIMIT 1");
                    
                if ($relatorios) {
                    $itemValor = mysqli_fetch_array($relatorios);
                    if (isset($itemValor['relatorioID'])) {
                        
                        $campanhaID                      = $itemValor['relatorioID'];
                        $relatorioUtmValor               = $itemValor['relatorioUtmValor'];
                        $relatorioUtmTipo                = $itemValor['relatorioUtmTipo'];
                        $relatorioReceitaTotal           = $itemValor['relatorioReceitaTotal'];
                        $relatorioClienteComissaoValor   = $itemValor['relatorioClienteComissaoValor'];
                        $relatorioCampanhaNome           = $itemValor['relatorioCampanhaNome'];
                        $clienteID                       = $itemValor['_clienteID'];
                        $analyticID                      = 0;
                        $campanhaNome                    = 0; 
                        $clienteTerm                     = 0; 
                        $custoValor                      = 0;
                        $facebookVisualizacoes           = 0;
                        $facebookImpressoes              = 0;
                        $facebookCliques                 = 0;
                        $comissaoValor                   = 0;
                        $campanhaStatus                  = '';
                        $contaID                         = 0;
                        $contaNome                       = '';
                        $lucroFinal                      = 0;
                        $clienteComissao                 = 0;
                        $impostoValor                    = 0;
                        $campanhaID                      = 0;
                        $custoValor                      = 0;
                        $receitaValor                    = 0;
                        $adname                          = '';
                        $paisNome                        = '';
                        $paisSigla                       = '';
                        $relatorioUtmSource              = '';
                        $relatorioRoiFinalValor          = '';
                        $relatorioRoiFinalClass          = '';
                        $relatorioRoiGeralValor          = '';
                        $relatorioRoiGeralClass          = '';
                        $relatorioOrcamentoValor         = 0.00;
                        $relatorioOrcamentoValorRestante = 0.00;
                        $labelNome                       = '';
                        $valor                           = 0.00;
                        
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
                            
                            $lista = mysqli_query($con, "SELECT *,
                                    SUM(itemCustoValor) AS itemCustoValor,
                                    SUM(itemVisualizacoes) AS itemVisualizacoes,
                                    SUM(itemImpressoes) AS itemImpressoes,
                                    SUM(itemCliques) AS itemCliques,
                                    SUM(itemOrcamentoRestante) AS itemOrcamentoRestante,
                                    SUM(itemOrcamentoDiario) AS itemOrcamentoDiario
                                FROM facebook_itens 
                                WHERE 
                                    itemAdset_id = '$relatorioUtmValor' AND 
                                    itemData     = '$dataValor'
                                LIMIT 1; ");
                                
                            if ($lista) {
                                $facebookValor = mysqli_fetch_array($lista);
                                if (isset($facebookValor['itemID'])) {
                                    $itemOrcamentoRestante = $facebookValor['itemOrcamentoRestante'];
                                    $itemOrcamentoDiario   = $facebookValor['itemOrcamentoDiario'];

                                    $campanhaStatus                  = $facebookValor['itemAdset_status']; 
                                    $contaID                         = $facebookValor['itemContaID'];  
                                    $contaNome                       = $facebookValor['itemContaNome']; 
                                    $custoValor                      = (float) $facebookValor['itemCustoValor'];
                                    $facebookVisualizacoes           = $facebookValor['itemVisualizacoes'];
                                    $facebookImpressoes              = $facebookValor['itemImpressoes']; 
                                    $facebookCliques                 = $facebookValor['itemCliques'];
                                    $paisNome                        = $facebookValor['itemPaisNome'];
                                    $paisSigla                       = $facebookValor['itemPaisSingla']; 
                                    $relatorioOrcamentoValorRestante = $itemOrcamentoRestante; 
                                    $relatorioOrcamentoValor         = $itemOrcamentoDiario;
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
                            
                            $lista = mysqli_query($con, "SELECT *,
                                    SUM(itemCustoValor) AS itemCustoValor,
                                    SUM(itemVisualizacoes) AS itemVisualizacoes,
                                    SUM(itemImpressoes) AS itemImpressoes,
                                    SUM(itemCliques) AS itemCliques
                                FROM facebook_itens 
                                WHERE 
                                    itemAd_id = '$relatorioUtmValor' AND 
                                    itemData  = '$dataValor'
                                LIMIT 1; ");
                                
                            if ($lista) {
                                $facebookValor = mysqli_fetch_array($lista);
                                if (isset($facebookValor['itemID'])) {
                                    $campanhaStatus        = $facebookValor['itemAd_status']; 
                                    $contaID               = $facebookValor['itemContaID'];  
                                    $contaNome             = $facebookValor['itemContaNome']; 
                                    $custoValor            = (float) $facebookValor['itemCustoValor'];
                                    $facebookVisualizacoes = $facebookValor['itemVisualizacoes'];
                                    $facebookImpressoes    = $facebookValor['itemImpressoes']; 
                                    $facebookCliques       = $facebookValor['itemCliques'];
                                    $paisNome              = $facebookValor['itemPaisNome'];
                                    $paisSigla             = $facebookValor['itemPaisSingla']; 
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
                            
                            $lista = mysqli_query($con, "SELECT *,
                                    SUM(itemCustoValor) AS itemCustoValor,
                                    SUM(itemVisualizacoes) AS itemVisualizacoes,
                                    SUM(itemImpressoes) AS itemImpressoes,
                                    SUM(itemCliques) AS itemCliques,
                                    SUM(itemOrcamentoRestante) AS itemOrcamentoRestante,
                                    SUM(itemOrcamentoDiario) AS itemOrcamentoDiario,
                                    SUM(itemOrcamentoRestante) AS itemOrcamentoRestante,
                                    SUM(itemOrcamentoDiario) AS itemOrcamentoDiario
                                FROM facebook_itens 
                                WHERE 
                                    itemCampanhaID = '$relatorioUtmValor' AND 
                                    itemData       = '$dataValor'
                                LIMIT 1; ");
                                
                            if ($lista) {
                                $facebookValor = mysqli_fetch_array($lista);
                                if (isset($facebookValor['itemID'])) {
                                    $itemOrcamentoRestante = $facebookValor['itemOrcamentoRestante'];
                                    $itemOrcamentoDiario   = $facebookValor['itemOrcamentoDiario'];
                                    
                                    $campanhaStatus                  = $facebookValor['itemStatus']; 
                                    $contaID                         = $facebookValor['itemContaID'];  
                                    $contaNome                       = $facebookValor['itemContaNome']; 
                                    $custoValor                      = (float) $facebookValor['itemCustoValor'];
                                    $facebookVisualizacoes           = $facebookValor['itemVisualizacoes'];
                                    $facebookImpressoes              = $facebookValor['itemImpressoes']; 
                                    $facebookCliques                 = $facebookValor['itemCliques'];
                                    $paisNome                        = $facebookValor['itemPaisNome'];
                                    $paisSigla                       = $facebookValor['itemPaisSingla']; 
                                    $relatorioOrcamentoValorRestante = $itemOrcamentoRestante; 
                                    $relatorioOrcamentoValor         = $itemOrcamentoDiario;
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
                            }
                            
                            if ($custoValor > 0) {
                                $_totalAdRevenue  = $relatorioReceitaTotal; 
                                $impostoValor     = ($_totalAdRevenue / 100) * $impostoPorcentagem;
                                $_totalAdRevenue  = ($_totalAdRevenue - $impostoValor) - $custoValor;
                                $comissaoValor    = $_totalAdRevenue - ($_totalAdRevenue - (($_totalAdRevenue / 100) * $clienteComissao));
                                
                                $lucroFinal       = $_totalAdRevenue - $comissaoValor;
                            }
                            
                            $receitaValor = $relatorioReceitaTotal;
                            
                            // Roi final
                            
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
                            
                            // Roi historico
                            
                            $_roiReceitaTotal    = 0;
                            $_roiCustoValor      = 0;
                            $_roiLucroFinalValor = 0;
                            $_roiComissaoValor   = 0;
                            
                            $roiSql = "SELECT *
                                FROM adx_relatorios 
                                WHERE 
                                    relatorioUtmValor = '$relatorioUtmValor'
                                ORDER BY relatorioData DESC
                                LIMIT 30";
                            
                            $roiItens = mysqli_query($con, $roiSql);
                            if ($roiItens) {
                                while ($roiItemValor = mysqli_fetch_array($roiItens)) {
                                    $roiCustoValor            = $roiItemValor['relatorioCustoValor'];
                                    $roiLucroFinalValor       = $roiItemValor['relatorioLucroFinal'];
                                    $roiComissaoValor         = $roiItemValor['relatorioComissaoValor'];
                                    $roiRelatorioReceitaTotal = $roiItemValor['relatorioReceitaTotal'];
                                    
                                    $_roiReceitaTotal    = $_roiReceitaTotal    + $roiRelatorioReceitaTotal;
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
                                if (empty($custoValor) || ($custoValor == 0)) {
                                    $custoValor = '0.00';
                                } else {
                                    $custoValor = (float) $custoValor;
                                }
                                
                                if (empty($receitaValor) || ($receitaValor == 0)) {
                                    $receitaValor = '0.00';
                                } else {
                                    $receitaValor = (float) $receitaValor;
                                }
                                
                                if (empty($valor)) {
                                    $valor = '0.00';
                                } else {
                                    $valor = (float) $valor;
                                }
                                
                                $campanhaID = (int) $campanhaID;
                                
                                $data = array(
                                    'roiValor'        => $valor,
                                    'roiLabel'        => $labelNome,
                                    'roiData'         => date('Y-m-d H:i:s'),
                                    'roiTipo'         => $relatorioUtmTipo,
                                    'roiCodigo'       => $relatorioUtmValor,
                                    'roiCampanhaNome' => $relatorioCampanhaNome,
                                    'roiCustoValor'   => $custoValor,
                                    'roiReceitaValor' => $receitaValor,
                                    'roiCampanhaID'   => $campanhaID,
                                    'roiAdName'       => $adname,
                                    'roiPaisNome'     => $paisNome,
                                    'roiPaisSigla'    => $paisSigla,
                                    '_relatorioID'    => $relatorioID
                                );
                                
                                insert('adx_roi_historico', $data);
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
                        
                        $relatorioRoiGeralValor = (float) $relatorioRoiGeralValor;
                      
                      	$relatorioDiasAtivo = (int) $relatorioDiasAtivo;
                        
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
                            'relatorioOrcamentoValor'	        => $relatorioOrcamentoValor,
                            'relatorioOrcamentoValorRestante'	=> $relatorioOrcamentoValorRestante,
                            '_clienteID'                        => $clienteID,
                            '_analyticID'                       => $analyticID, 
                            '_contaID'                          => $contaID
                        );
                        
                        echo 'Verificar<br />';
                      	echo 'ITEM ID ' . $relatorioUtmValor . '<br />';
                      
                        pre($data);

                        $__retorno = update('adx_relatorios', $data, 'relatorioID = ' . $relatorioID);
                      	if (!$__retorno) 
                          	echo 'ERRROR ' . mysqli_error($con) . '<br />';
                    }
                }
            }
            
            break;
        }
    }
}