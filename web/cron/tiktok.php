<?php
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

    if (isset($_POST['data'])) {
        $html = $_POST['data'];
        
        if (!empty($html)) {
        
            $custoTipo = 'tiktok';
            
            $arrLinhas = array_map("str_getcsv", explode("\n", $html));
            $arrLinhas = array_filter($arrLinhas);
                                
            $_arrCampanhas = array();
            
            foreach ($arrLinhas as $itemValor) {
                
                $campanhaNome = (string) trim($itemValor[0]);
                
                if (preg_match('/Campaign name/', $campanhaNome))
                    continue;
                    
                    if (empty($campanhaNome))
                        continue;
                        
                    $campanhaPais = '';
                    if (isset($itemValor[1])) {
                        $campanhaPais = $itemValor[1];
                
                    $campanhaData = '';
                    if (isset($itemValor[2]));
                        $campanhaData = $itemValor[2];
                    
                    $campanhaCusto = '';
                    if (isset($itemValor[6]));
                        $campanhaCusto = $itemValor[6];
                        
                    $campanhaCPC = '';
                    if (isset($itemValor[7]));
                        $campanhaCPC = $itemValor[7];
                        
                    $campanhaImpressoes = '';
                    if (isset($itemValor[8]));
                        $campanhaImpressoes = $itemValor[8];
                        
                    $campanhaCliques = '';
                    if (isset($itemValor[9]))
                        $campanhaCliques = $itemValor[9];
                        
                    $campanhaTiktokAdvertiserID = '';
                    if (isset($itemValor[4]));
                        $campanhaTiktokAdvertiserID = $itemValor[4];
                        
                    $campanhaTiktokCampaignID = '';
                    if (isset($itemValor[5]));
                        $campanhaTiktokCampaignID = $itemValor[5];
                        
                    $campanhaTiktokCurrency = '';
                    if (isset($itemValor[16]));
                        $campanhaTiktokCurrency = $itemValor[16];
                        
                    $campanhaTiktokName = '';
                    if (isset($itemValor[3]));
                        $campanhaTiktokName = $itemValor[3];
                        
                    $tiktokCTR = '';
                    if (isset($itemValor[10]));
                        $tiktokCTR = $itemValor[10];
                        
                    $tiktokConversions = '';
                    if (isset($itemValor[11]));
                        $tiktokConversions = $itemValor[11];
                        
                    $tiktokCPA = '';
                    if (isset($itemValor[12]));
                        $tiktokCPA = $itemValor[12];
                        
                    $tiktokTotalLandingPageView = '';
                    if (isset($itemValor[13]));
                        $tiktokTotalLandingPageView = $itemValor[13];
                        
                    $tiktokCostperLandingPageView = '';
                    if (isset($itemValor[14]));
                        $tiktokCostperLandingPageView = $itemValor[14];
                        
                    $tiktokLandingPageViewRate = '';
                    if (isset($itemValor[15]));
                        $tiktokLandingPageViewRate = $itemValor[15];
                        
                    $_campanhaPais = $campanhaPais;
                     
                    if ($campanhaPais == 'Unknown')
                        continue;
                        
                    $campanhaPais = paisTrocaNome($campanhaPais);
                    
                    $_campanhaNome = $campanhaData . '_' . $campanhaNome;
                    if (!in_array($_campanhaNome, $arrCampanhas))
                        $arrCampanhas[] = $_campanhaNome;
                        
                    $sql = "SELECT * 
                        FROM `analytics_gestor_pais` 
                        WHERE 
                            gestorPais_sessionCampaignName = '$campanhaNome' AND 
                            gestorPais_country             = '$campanhaPais' AND 
                            gestorPais_date                = '$campanhaData' ";
                    
                    $pais = mysqli_query($con, $sql);
                           
                    if ($pais) {
                        $gestorValor = mysqli_fetch_array($pais);
                        if (isset($gestorValor['gestorPaisID'])) {
                            $gestorPaisID   = $gestorValor['gestorPaisID']; 
                            $gestorPaisID   = $gestorValor['gestorPaisID']; 
                            $totalAdRevenue = $gestorValor['gestorPais_totalAdRevenue']; 
                            
                            $clienteComissaoValor = 10;
                            $itemResultados       = '';
                    
                            $impostoPorcentagem = getConfig('imposto_porcentagem');
                            if (empty($impostoPorcentagem))
                                $impostoPorcentagem = 10;
                            
                            $campanhaImpostoValor = ($totalAdRevenue / 100) * $impostoPorcentagem;
                            $totalAdRevenue       = ($totalAdRevenue - $campanhaImpostoValor) - $campanhaCusto;
                            
                            $campanhaComissaoValor = $totalAdRevenue - ($totalAdRevenue - (($totalAdRevenue / 100) * $clienteComissaoValor));
                            
                            $lucroFinal = $totalAdRevenue - $campanhaComissaoValor;
                            
                            $retorno = mysqli_query($con, "UPDATE analytics_gestor_pais SET 
                                gestorPaisComissaoValor = '$campanhaComissaoValor',
                                gestorPaisImpostoValor  = '$campanhaImpostoValor',
                                gestorPaisImposto       = '$impostoPorcentagem',
                                gestorPaisCustoValor    = '$campanhaCusto',
                                gestorPaisCustoCliques  = '$campanhaCPC',
                                gestorPaisLucroFinal    = '$lucroFinal',
                                gestorPaisImpressoes    = '$campanhaImpressoes',
                                gestorPaisCustoStatus   = 'active',
                                gestorPaisResultados    = '$itemResultados',
                                gestorPaisCPR           = '$campanhaCPC',
                                gestorPaisAdvertiserID  = '$campanhaTiktokAdvertiserID',
                                gestorPaisCampaignID    = '$campanhaTiktokCampaignID',
                                gestorPaisCurrency      = '$campanhaTiktokCurrency',
                                gestorPaisName          = '$campanhaTiktokName',
                                
                                gestorPais_tiktokCTR                    = '$tiktokCTR',
                                gestorPais_tiktokConversions            = '$tiktokConversions',
                                gestorPais_tiktokCPA                    = '$tiktokCPA',
                                gestorPais_tiktokTotalLandingPageView   = '$tiktokTotalLandingPageView',
                                gestorPais_tiktokCostperLandingPageView = '$tiktokCostperLandingPageView',
                                gestorPais_tiktokCliques                = '$campanhaCliques',
                                gestorPais_tiktokLandingPageViewRate    = '$tiktokLandingPageViewRate'
                                
                            WHERE 
                                gestorPaisID = '$gestorPaisID' ");
                                
                            $_arrCampanhas[$campanhaNome][$campanhaData][] = array(
                                'campanhaPaisNome'            => $_campanhaPais,
                                'campanhaCustoComissao'	      => $campanhaComissaoValor,
                                'campanhaCustoValor'	      => $campanhaCusto,
                                'campanhaCustoResultados'     => $itemResultados,
                                'campanhaCustoCPR'	          => $campanhaCPC,
                                'campanhaCustoViews'          => $campanhaImpressoes,
                                'campanha_tiktokAdvertiserID' => $campanhaTiktokAdvertiserID,
                                'campanha_tiktokCampaignID'   => $campanhaTiktokCampaignID,
                                'campanha_tiktokCurrency'     => $campanhaTiktokCurrency,
                                'campanha_tiktokCliques'      => $campanhaCliques,
                                'campanha_tiktokName'         => $campanhaTiktokName,
                                
                                
                                'campanha_tiktokCliques'                => $campanhaCliques,
                                'campanha_tiktokImpressoes'             => $campanhaImpressoes,
                                'campanha_tiktokCTR'                    => $tiktokCTR,
                    	        'campanha_tiktokConversions'            => $tiktokConversions,
                    	        'campanha_tiktokCPA'                    => $tiktokCPA,
                    	        'campanha_tiktokTotalLandingPageView'   => $tiktokTotalLandingPageView,
                    	        'campanha_tiktokCostperLandingPageView' => $tiktokCostperLandingPageView,
                    	        'campanha_tiktokLandingPageViewRate'    => $tiktokLandingPageViewRate
                            );
                        }
                    }
                }
            }
            
            foreach ($_arrCampanhas as $campanhaNome => $arrData) { 
                foreach ($arrData as $dataValor => $dataItens) { 
                    $campanhaPaisNome = $dataItens[0]['campanhaPaisNome'];
                    
                    $campanha_tiktokImpressoes             = 0;
                    $campanha_tiktokConversions            = 0;
                    $campanha_tiktokTotalLandingPageView   = 0;
                    $campanha_tiktokCostperLandingPageView = 0;
                    $campanha_tiktokCliques                = 0;
                    
                    foreach ($dataItens as $itemValor) { 
                        $campanha_tiktokCliques                = $campanha_tiktokCliques                + $itemValor['campanha_tiktokCliques'];
                        $campanha_tiktokImpressoes             = $campanha_tiktokImpressoes             + $itemValor['campanha_tiktokImpressoes'];
                        $campanha_tiktokConversions            = $campanha_tiktokConversions            + $itemValor['campanha_tiktokConversions'];
                        $campanha_tiktokTotalLandingPageView   = $campanha_tiktokTotalLandingPageView   + $itemValor['campanha_tiktokTotalLandingPageView'];
                        $campanha_tiktokCostperLandingPageView = $campanha_tiktokCostperLandingPageView + $itemValor['campanha_tiktokCostperLandingPageView'];
                    }
                    
                    $campanha = mysqli_query($con, "SELECT *
                        FROM analytics_campanhas
                        WHERE 
                            campanha_sessionCampaignName = '$campanhaNome' AND
                            campanha_date                = '$dataValor'
                        LIMIT 1;");
                        
                    if ($campanha) {
                        $campanhaItem = mysqli_fetch_array($campanha);
                        if (isset($campanhaItem['campanhaID'])) {
                            $campanhaID = $campanhaItem['campanhaID'];
                            
                            $data = array(
                                'campanha_tiktokAdvertiserID'           => $dataItens[0]['campanha_tiktokAdvertiserID'],
                                'campanha_tiktokCampaignID'             => $dataItens[0]['campanha_tiktokCampaignID'],
                                'campanha_tiktokCurrency'               => $dataItens[0]['campanha_tiktokCurrency'],
                                'campanha_tiktokName'                   => $dataItens[0]['campanha_tiktokName'],
                                'campanha_tiktokImpressoes'             => $campanha_tiktokImpressoes,
                                'campanha_tiktokConversions'            => $campanha_tiktokConversions,
                                'campanha_tiktokTotalLandingPageView'   => $campanha_tiktokTotalLandingPageView,
                                'campanha_tiktokCostperLandingPageView' => $campanha_tiktokCostperLandingPageView,
                                'campanha_tiktokCliques'                => $campanha_tiktokCliques,
                                'campanhaTipo'                          => 'tiktok',
                                'campanha_sessionSourceMedium'          => 'tiktok'
                            );
                            
                            update('analytics_campanhas', $data, 'campanhaID = ' . $campanhaID);
                        }
                    }
                }
            }
        }
    }