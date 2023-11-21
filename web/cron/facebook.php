<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php');

$limite  = 2;
$arquivo = ABSPATH . '/data/cron_facebook.txt';

if (isset($_GET['iniciar'])) {
    file_put_contents($arquivo, '');
    
    ob_start();
    
    echo 'Iniciando...';
 
    $arrContas = array();
    
    $query = mysqli_query($con, "SELECT *
    	FROM facebook_contas A
    	WHERE 
    	    contaStatus = 1
    	ORDER BY contaID DESC;");
    
    if ($query) {
        while ($contaValor = mysqli_fetch_array($query)) { 
            $cadastroID = $contaValor['contaID'];
            $contaToken = $contaValor['contaToken'];
            
            if (empty($contaToken))
                continue;
            
            $campos = 'account_name,account_id';
            
            $url      = "https://graph.facebook.com/v17.0/me/adaccounts?access_token=" . $contaToken .'&fields=account_name,account_id,balance,account_status,business_name,amount_spent,name,funding_source_details,age,timezone_name';
            $continua = true;
            
            do {
                $response = file_get_contents($url);
            
                $data = (array) json_decode($response, true);
                $data = array_filter($data);
            
                if (isset($data['data'])) {
                    foreach ($data['data'] as $itemValor) { 
                        $contaID = $itemValor['account_id'];
                        
                        // if ($itemValor['account_id'] <> '647316537309560')
                        //    continue;
                        
                        $balancoValor = $itemValor['balance'];
                        $balancoValor = substr($balancoValor, 0, strlen($balancoValor) - 2) . '.' . substr($balancoValor, strlen($balancoValor) - 2, 2);
                        
                        $custoValor = $itemValor['amount_spent'];
                        $custoValor = substr($custoValor, 0, strlen($custoValor) - 2) . '.' . substr($custoValor, strlen($custoValor) - 2, 2);
                        
                        $arrContas[] = array(
                            'conta_id'           => $itemValor['account_id'],
                            'contaNome'          => $itemValor['name'],
                            'itemSaldoPagar'     => $balancoValor,
                            'itemSituacao'       => $itemValor['account_status'],
                            'itemTotalGasto'     => $custoValor,
                            'itemTimezoneName'   => $itemValor['timezone_name'],
                            'itemFormaPagamento' => $itemValor['funding_source_details']['display_string'],
                            'itemTempoAtivo'     => $itemValor['age'],
                            'token'              => $contaToken,
                            'registroID'         => $cadastroID
                        );
                        
                        /*
                        echo '<pre>';
                        print_r($itemValor);
                        print_r($arrContas);
                        echo '</pre>';
                        
                        exit; */
                        
                        $link = 'https://graph.facebook.com/v17.0/act_' . $contaID . '/ads_volume?access_token=' . $contaToken;
                    }
                    
                    if (isset($data['paging']['next'])) {
                        $url = $data['paging']['next'];
                    } else {
                        $continua = false;
                    }
                    
                } else {
                    $continua = false;
                }
                
            } while ($continua);
        }
    }
    
    // $retorno = mysqli_query($con, "DELETE FROM facebook_conta_itens;"); 
    
    foreach ($arrContas as $itemValor) {
        $itemTotalGasto = $itemValor['itemTotalGasto'];
        if ($itemTotalGasto < 1)
            continue;
            
        insert('facebook_conta_itens', array(
            'itemNome'           => $itemValor['contaNome'],
            'itemValor'          => $itemValor['conta_id'],
            '_contaID'           => $itemValor['registroID'],
            'itemSaldoPagar'     => $itemValor['itemSaldoPagar'],
            'itemSituacao'       => $itemValor['itemSituacao'],
            'itemTotalGasto'     => $itemValor['itemTotalGasto'],
            'itemFormaPagamento' => $itemValor['itemFormaPagamento'],
            'itemTimezoneName'   => $itemValor['itemTimezoneName'],
            'itemTempoAtivo'     => $itemValor['itemTempoAtivo']
        ));
    }
    
    echo 'Total de contas: ' . count($arrContas) . '<br />';
    echo 'Redirecionando para aplicar...<br />';
    
    $resultado = ob_get_contents();
    ob_end_clean();
    
    file_put_contents($arquivo, json_encode($arrContas)); 
    
    header("Location: " . site_url('cron/facebook.php?executar'));
    
    echo $resultado;
    
} else if (isset($_GET['executar'])) { 

    if (isset($_GET['teste'])) { ?>
    
        <style>
            .pendente {
                color: #FF4500
            }
            
            .feito {
                color: #006400;
            }
        </style>
        
        <?php
        $html = file_get_contents($arquivo);
        $json = (array) json_decode($html, true);
        $json = array_filter($json);
        
        $total = count($json);
        
        foreach ($json as $contaValor) {
            $contaID     = $contaValor['conta_id'];
            $contaToken  = $contaValor['token'];
            $contaStatus = $contaValor['itemSituacao'];
            
            echo 'CONTA: <strong id="conta-' . $contaID . '" data-id="' . $contaID . '" data-token="' . $contaToken . '" data-status="' . $contaStatus . '" class="pendente">[Pendente]</strong> ' . $contaID . '<br />';
        } ?>
        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
        <script>
            $(function(){
                $(".pendente").each(function() { 
                    var _this = $(this);
                    var id     = _this.data('id');
                    var status = _this.data('status');
                    var token  = _this.data('token');
                    
                    $.ajax({
                        type: 'post',
                        url:  '<?php echo site_url('ajax.php?cronFacebook'); ?>',
                        data: {
                            'id'     : id,
                            'status' : status,
                            'token'  : token
                        },
                        success: function(resp) {
                            if (resp == 'feito') {
                               _this
                                    .removeClass('pendente')
                                    .addClass('feito')
                                    .text('[Feito]'); 
                            }
                        }
                    });
                });
            });
        </script>
        
        <?php
        exit;
    }
    
    $clienteComissaoValor = 10;
                            
    $impostoPorcentagem = getConfig('imposto_porcentagem');
    if (empty($impostoPorcentagem))
        $impostoPorcentagem = 10;
    
    $arrAnunciantes = array();
                
    $anunciantes = mysqli_query($con, "SELECT *
    	FROM clientes;");

    if ($anunciantes) {
        while ($anuncianteValor = mysqli_fetch_array($anunciantes)) { 
            $clienteID      = $anuncianteValor['clienteID'];
            $clienteUtmTerm = $anuncianteValor['clienteUtmTerm'];
            
            if (!empty($clienteUtmTerm))
                $arrAnunciantes[$clienteID] = explode(',', $clienteUtmTerm);
        }
    }
    
    $html = file_get_contents($arquivo);
    $json = (array) json_decode($html, true);
    $json = array_filter($json);
    
    $total = count($json);
    
    echo 'CONTAS PENDENTES: ' . $total . '<br />';
    
    if ($total == 0) {
        echo 'FINALIZADO<br />';
    } else {
    
        $arrDatas = array(
            'today',
            'yesterday'
        );
        
        $timeRange = true;
        
        if ($timeRange) {
            $arrDatas = array(
                date('Y-m-d'),
                // date('Y-m-d', strtotime('-1 day')),
                //date('Y-m-d', strtotime('-6 day')),
                //date('Y-m-d', strtotime('-7 day')),
                //date('Y-m-d', strtotime('-8 day')),
                //date('Y-m-d', strtotime('-9 day'))

             
            );
        }
        
        $x = 1;
        
        foreach ($json as $contaIndex => $contaValor) {
            
            $contaID     = $contaValor['conta_id'];
            $contaToken  = $contaValor['token'];
            $contaStatus = $contaValor['itemSituacao'];
            
            echo 'CONTA ATUAL: ' . $contaID . '<br />';
                
            $_arrCampanhas = array();
                
            foreach ($arrDatas as $dataValor) {
                $campos = 'account_name,account_id,campaign_id,campaign_name,spend,impressions,clicks,ctr,cpc,actions{action_type,value}';
                
                $topo = 'Conta Nome,Conta ID, Campanha ID, Campanha Nome, Custo, Impressões, Cliques, CTR, CPC, Pagina Views, Data final,  Pais, Status';
                
                $url = "https://graph.facebook.com/v17.0/act_" . $contaID . "/insights?access_token=" . $contaToken . "&sort=reach_descending&level=campaign&breakdowns=country&fields=" . $campos . "&filtering=[{%22field%22:%22action_type%22,%22operator%22:%22IN%22,%22value%22:[%22landing_page_view%22]}]";
                
                if ($timeRange) {
                    $url .= "&time_range={%27since%27:%27" . $dataValor . "%27,%27until%27:%27" . $dataValor . "%27}";
                } else {
                    $url .= '&date_preset=' . $dataValor;
                }
                
                $response = file_get_contents($url);
                
                $arrStatus = array();
                    
                $linkStatus     = 'https://graph.facebook.com/v17.0/act_' . $contaID . '/campaigns?fields=name,status&access_token=' . $contaToken;
                $responseStatus = file_get_contents($linkStatus);
                
                $jsonStatus = (array) json_decode($responseStatus, true);
                $jsonStatus = array_filter($jsonStatus); 
                
                
                if (isset($jsonStatus['data'])) {
                    $arrStatus = $jsonStatus['data'];
                }
                
                $data = (array) json_decode($response, true);
                $data = array_filter($data); 
                
                if (isset($data['data'])) {
                    $total = count($data['data']);
                    
                    $arrItens = array();
                    
                    foreach ($data['data'] as $itemValor) { 
                        $campanhaID = $itemValor['campaign_id'];
                        
                        if (isset($itemValor['actions'][0]['value']))
                            $itemValor['actions'] = $itemValor['actions'][0]['value']; 
                            
                        $status = '';
                        foreach ($arrStatus as $statusValor) {
                            if ($statusValor['id'] ==  $campanhaID) {
                                $status =  $statusValor['status'];
                            }
                        }        
                    
                        $itemValor['status'] = $status;
                            
                        $campanhaNome         = $itemValor['campaign_name'];
                        $paisSigla            = $itemValor['country'];
                        $campanhaData         = $itemValor['date_start'];
                        $paisNome             = arrPais($paisSigla, true);
                        $itemValorGasto       = $itemValor['spend'];
                        $itemCPR              = $itemValor['cpc'];
                        $itemImpressoes       = $itemValor['impressions'];
                        $campanhaIDdaConta    = $itemValor['account_id'];
                        $campanhaIDdaCampanha = $itemValor['campaign_id'];
                        $nomeDaConta          = $itemValor['account_name'];
                      
                      	$itemResultados = '';
                      	if (isset($itemValor['actions']))
                          	$itemResultados = $itemValor['actions'];
                      
                        $clienteID                    = 0;
                        $campanha_firstUserManualTerm = '';
                        $campanhaID                   = '';
                        
                        $campanhas = mysqli_query($con, "SELECT * 
                            FROM `analytics_campanhas` 
                            WHERE 
                                campanha_sessionCampaignName  = '$campanhaNome' AND 
                                campanha_date                 = '$campanhaData' 
                            LIMIT 1;");
                            
                        if ($campanhas) {
                            $_campanhaValor = mysqli_fetch_array($campanhas);
                            if (isset($_campanhaValor['campanhaID'])) {
                                $campanha_firstUserManualTerm = $_campanhaValor['campanha_firstUserManualTerm'];
                                $campanhaID                   = $_campanhaValor['campanhaID'];
                            }   
                        }
                        
                        foreach ($arrAnunciantes as $anuncianteID => $anuncianteValor) {
                            
                            $_anuncianteValor = array();
                            foreach ($anuncianteValor as $_itemValor) {
                                $_anuncianteValor[] = trim($_itemValor);
                            }
                            
                            if (in_array($campanha_firstUserManualTerm, $_anuncianteValor)) {
                                $clienteID = $anuncianteID;
                                
                                break;
                            }
                        }
                        
                        $campanhaStatus = 1;
                        if ($status == 'PAUSED')
                            $campanhaStatus = 2;
                            
                        if ($status == 'NOT_DELIVERING')
                            $campanhaStatus = 3;
                                
                        $data = array(
                            'itemContaNome'     => $itemValor['account_name'],
                            'itemContaID'       => $itemValor['account_id'],
                            'itemCampanhaID'    => $itemValor['campaign_id'],
                            'itemCampanhaNome'  => $itemValor['campaign_name'],
                            'itemCustoValor'    => $itemValor['spend'],
                            'itemVisualizacoes' => $itemResultados,
                            'itemImpressoes'    => $itemValor['impressions'],
                            'itemCliques'       => $itemValor['clicks'],
                            'itemCTR'           => $itemValor['ctr'],
                            'itemCPC'           => $itemValor['cpc'],
                            'itemAcoes'         => $itemResultados,
                            'itemData'          => $itemValor['date_start'],
                            'itemPaisSingla'    => $itemValor['country'],
                            'itemPaisNome'      => $paisNome,
                            'itemStatus'        => $itemValor['status'],
                            '_clienteID'	    => $clienteID
                        );
                        
                        $cadastrado = mysqli_query($con, "SELECT * 
                            FROM `facebook_itens` 
                            WHERE 
                                itemCampanhaNome = '$campanhaNome' AND 
                                itemPaisSingla   = '$paisSigla' AND 
                                itemData         = '$campanhaData' 
                            LIMIT 1;");
                            
                        if ($cadastrado) {
                            $cadastradoValor = mysqli_fetch_array($cadastrado);
                            if (isset($cadastradoValor['itemID'])) {
                                $itemID = $cadastradoValor['itemID'];
                                
                                $data['itemCadastroData'] = date('Y-m-d H:i:s');
                                
                                $retorno = update('facebook_itens', $data, 'itemID = ' . $itemID);
                                if ($retorno) {
                                    
                                    mysqli_query($con, "UPDATE facebook_itens 
                                        SET itemStatus = '$status' 
                                        WHERE 
                                            itemCampanhaID = '$campanhaIDdaCampanha' ");
                                }

                            } else {
                                insert('facebook_itens', $data);
                            }
                        }

                        $query = mysqli_query($con, "SELECT * 
                            FROM `analytics_gestor_pais` 
                            WHERE 
                                gestorPais_sessionCampaignName = '$campanhaNome' AND 
                                gestorPais_country             = '$paisNome' AND 
                                gestorPais_date                = '$campanhaData'
                            LIMIT 1;");
                            
                        if ($query) {
                            $campanhaValor = mysqli_fetch_array($query);
                            if (isset($campanhaValor['gestorPaisID'])) {
                                
                                $gestorPaisID   = $campanhaValor['gestorPaisID']; 
                                $totalAdRevenue = $campanhaValor['gestorPais_totalAdRevenue']; 
                                
                                $_sql = "UPDATE analytics_gestor_pais SET 
                                        gestorPaisComissaoValor = '$gestorPaisComissaoValor',
                                        gestorPaisImpostoValor  = '$campanhaImpostoValor',
                                        gestorPaisImposto       = '$impostoPorcentagem',
                                        gestorPaisCustoValor    = '$itemValorGasto',
                                        gestorPaisCustoCliques  = '$itemCPR',
                                        gestorPaisLucroFinal    = '$lucroFinal',
                                        gestorPaisImpressoes    = '$itemImpressoes',
                                        gestorPaisCustoStatus   = '$campanhaStatus',
                                        gestorPaisResultados    = '$itemResultados',
                                        gestorPaisCPR           = '$itemCPR'
                                    WHERE 
                                        gestorPaisID = '$gestorPaisID' ";
                                        
                                $retorno = mysqli_query($con, $_sql);
                                
                                /*
                                if ($retorno) {
                                    
                                    $_arrCampanhas[$campanhaID][$campanhaData][] = array(
                                        'campanhaNome'            => $campanhaNome,
                                        'clienteID'               => $clienteID,
                                        'campanhaCustoValor'	  => $itemValorGasto,
                                        'campanhaCustoResultados' => $itemResultados,
                                        'campanhaCustoCPR'	      => $itemCPR,
                                        'campanhaCustoViews'      => $itemImpressoes,
                                        'receitaTotal'            => $campanhaValor['gestorPais_totalAdRevenue'],
                                        'clienteID'               => $clienteID,
                                        'campanhaIDdaConta'       => $campanhaIDdaConta,
                                        'campanhaIDdaCampanha'    => $campanhaIDdaCampanha,
                                        'nomeDaConta'             => $nomeDaConta,
                                        'campanhaStatus'          => $campanhaStatus,
                                        'status'                  => $contaStatus
                                    ); 
                                } */
                                
                            }
                        }
                    }
                }
            }
            
            /*
            foreach ($_arrCampanhas as $campanhaID => $arrData) { 
                
                foreach ($arrData as $dataID => $dataItens) { 

                    $totalCustoComissao   = 0;
                    $totalCustoValor      = 0;
                    $totalCustoResultados = 0;
                    $totalCustoCPR        = 0;
                    $totalCustoViews      = 0;
                    $receitaTotal         = 0;
                    $clienteID            = 0;
                    $nomeDaConta          = '';
                    $campanhaIDdaConta    = '';
                    $campanhaIDdaCampanha = '';
                    $campanhaStatus       = 2;
                    
                    foreach ($dataItens as $itemValor) {
                        
                        $nomeDaConta          = $itemValor['nomeDaConta'];
                        $campanhaIDdaConta    = $itemValor['campanhaIDdaConta'];
                        $campanhaIDdaCampanha = $itemValor['campanhaIDdaCampanha'];
                        $clienteID            = $itemValor['clienteID'];
                        $campanhaStatus       = $itemValor['campanhaStatus'];
                        $receitaTotal         = $receitaTotal         + $itemValor['receitaTotal'];
                        $totalCustoValor      = $totalCustoValor      + $itemValor['campanhaCustoValor'];
                        $totalCustoResultados = $totalCustoResultados + $itemValor['campanhaCustoResultados'];
                        $totalCustoCPR        = $totalCustoCPR        + $itemValor['campanhaCustoCPR'];
                        $totalCustoViews      = $totalCustoViews      + $itemValor['campanhaCustoViews'];
                    }
                    
                    $campanhaImpostoValor    = ($receitaTotal / 100) * $impostoPorcentagem;
                    $receitaTotal            = ($receitaTotal - $campanhaImpostoValor) - $totalCustoValor;
                    
                    $gestorPaisComissaoValor = $receitaTotal - ($receitaTotal - (($receitaTotal / 100) * $clienteComissaoValor));
                    $gestorPaisLucroFinal    = $receitaTotal - $gestorPaisComissaoValor;
                    
                    // Atualiza campanha
                
                    $data = array(
                        'campanhas_totalResultados'    => $totalCustoResultados,
                        'campanhas_totalCustoValor'    => $totalCustoValor,
                        'campanhas_totalImpressoes'    => $totalCustoViews,
                        'campanhas_totalCPR'           => $totalCustoCPR,
                        'campanhas_totalComissaoValor' => $gestorPaisComissaoValor,
                        'campanhaNomeDaConta'          => $nomeDaConta,
                        'campanhaIDdaConta'            => $campanhaIDdaConta,
                        'campanhaIDdaCampanha'         => $campanhaIDdaCampanha,
                        'campanha_cacheCustoStatus'    => $campanhaStatus,
                        'campanhaTipo'                 => 'facebook',
                        'campanha_sessionSourceMedium' => 'facebook',
                    );
                    
                    $retorno = update('analytics_campanhas', $data, 'campanhaID = ' . $campanhaID);
                    
                    // Atualiza custo
                    
                    $sql = "SELECT * 
                        FROM `cliente_campanhas_custo` 
                        WHERE 
                            _campanhaID = '$campanhaID' ";
                            
                    $custoQuery = mysqli_query($con, $sql);
                    if ($custoQuery) {
                        $custoValor = mysqli_fetch_array($custoQuery);
                        
                        if (isset($custoValor['campanhaCustoID'])) {
                            $campanhaCustoID = $custoValor['campanhaCustoID'];
                            
                            $data = array(
                                'campanhaCustoLucro'	  => $gestorPaisLucroFinal,
                                'campanhaCustoComissao'	  => $gestorPaisComissaoValor,
                                'campanhaCustoValor'	  => $totalCustoValor,
                                'campanhaCustoResultados' => $totalCustoResultados,
                                'campanhaCustoCPR'	      => $totalCustoCPR,
                                'campanhaCustoViews'      => $totalCustoViews,
                                'campanhaData'            => $dataID,
                                '_clienteID'              => $clienteID
                            );
                            
                            $retorno = update('cliente_campanhas_custo', $data, 'campanhaCustoID = ' . $campanhaCustoID);
                              
                        } else {
                            
                            $data = array(
                                'campanhaCustoLucro'	  => $gestorPaisLucroFinal,
                                'campanhaCustoComissao'	  => $gestorPaisComissaoValor,
                                'campanhaCustoValor'	  => $totalCustoValor,
                                'campanhaCustoResultados' => $totalCustoResultados,
                                'campanhaCustoCPR'	      => $totalCustoCPR,
                                'campanhaCustoViews'      => $totalCustoViews,
                                'campanhaData'            => $dataID,
                                '_clienteID'              => $clienteID,
                                '_campanhaID'	          => $campanhaID
                            );
                            
                            $retorno = insert('cliente_campanhas_custo', $data);
                        }
                    }
                }
            } */
            
            unset($json[$contaIndex]);
            
            if ($x == $limite)
                break;
                
            $x++;
        } 
        
        //exit;
        
        file_put_contents($arquivo, json_encode($json)); ?>
        
        <script>
            window.location = '<?php echo site_url('cron/facebook.php?executar'); ?>';
        </script>
        <?php
    }
    

} else { ?>
    <a href="https://gestor.naveads.com/cron/facebook.php?iniciar">Iniciar</a>
    <?php 
}