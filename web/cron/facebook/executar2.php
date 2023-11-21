<?php 
header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$tipo = '';
if (isset($_GET['tipo'])) 
    $tipo = $_GET['tipo'];
    
$pasta    = ABSPATH . 'cron/arquivos/';
$arquivos = glob($pasta . '*.txt');
    
$dataAtual = date('Y-m-d');
$horaAtual = date('H');
    
if ($tipo == 'admanager') {
    
    /*
    if (!isset($_GET['teste']))
        exit; */
    
    $impostoPorcentagem = getConfig('imposto_porcentagem');
    if (empty($impostoPorcentagem))
        $impostoPorcentagem = 10;
        
    $dolarHoje = getConfig('dolar_valor');

    $campos = array(
        'Dimension.DATE'                                        => 'relatorioData',
        'Dimension.CUSTOM_CRITERIA'                             => 'relatorioUtm',
        'Dimension.CUSTOM_TARGETING_VALUE_ID'                   => 'relatorioTargetID',
        'Column.TOTAL_INVENTORY_LEVEL_UNFILLED_IMPRESSIONS'     => 'relatorioImpressoesNaoPreenchidas',
        'Column.TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'              => 'relatorioImpressoes',
        'Column.TOTAL_LINE_ITEM_LEVEL_CLICKS'                   => 'relatorioCliques',
        'Column.TOTAL_LINE_ITEM_LEVEL_CTR'                      => 'relatorioCTR',
        'Column.TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE'      => 'relatorioReceitaTotal',
        'Column.TOTAL_LINE_ITEM_LEVEL_WITHOUT_CPD_AVERAGE_ECPM' => 'relatorioEcpm'
    );
    
    $arquivoCampos = '../arquivos/campos.txt';  

    $arrArquivos = array();

    $contaNome = '';
    if (isset($_GET['conta']))
        $contaNome = $_GET['conta'];

    foreach ($arquivos as $arquivoValor) {
        preg_match('/admanager-(\d+)-(\d+)\.txt/', $arquivoValor, $match);
        if (isset($match[1])) {
            $_contaNome = $match[2];
            if (empty($contaNome)) {
                $arrArquivos[] = $arquivoValor;
            } else {
                if (preg_match('/' . $contaNome . '/i', $arquivoValor))
                    $arrArquivos[] = $arquivoValor;
            }
        }    
    }
    
    if (count($arrArquivos) == 0) {
        echo 'parar';
    } else {
        
        shuffle($arrArquivos);
        
        foreach ($arrArquivos as $arquivoIndex => $arquivoValor) {
            if (is_file($arquivoValor)) {
                $contaCodigo = '';
                preg_match('/admanager-(\d+)-(\d+)/', $arquivoValor, $match);
                if (isset($match[2])) {
                    $contaCodigo = $match[2];
                }
                
                $arquivoInserir = $pasta . 'admanager_inserir_' . $contaCodigo . '.txt';
                
                if (is_file($arquivoInserir)) {
                    $arquivoInserir = file_get_contents($arquivoInserir);
                    $arquivoInserir = (array) json_decode($arquivoInserir, true);
                    $arquivoInserir = array_filter($arquivoInserir);
                } else {
                    $arquivoInserir = array();
                }
                
                $html = file_get_contents($arquivoValor);
                $json = (array) json_decode($html, true);
                $json = array_filter($json);
                
                unlink($arquivoValor);
                
                $html    = file_get_contents($arquivoCampos);
                $_campos = (array) json_decode($html, true);
                $_campos = array_filter($_campos);
                
                $totalCampos = count($_campos);
                if ($totalCampos > 0) {
        
                    $_arrUtm = array(); 
                
                    $query = mysqli_query($con, "SELECT *
                        FROM clientes
                        LIMIT 100;");
                    
                    if ($query) {
                        while ($lista = mysqli_fetch_array($query)) { 
                            $clienteUtmTerm       = $lista['clienteUtmTerm'];
                            $clienteID            = $lista['clienteID'];
                            
                            $arrUtm = explode(',', $clienteUtmTerm);
                            $arrUtm = array_filter($arrUtm);
                            
                            foreach ($arrUtm as $utmValor) {
                                $_arrUtm[$clienteID][] = trim($utmValor);
                            }
                        }
                    }
        
                    $arr = array();
                    
                    foreach ($json as $itemIndex => $itemCampos) {
                        $_arr = array();
                        
                        foreach ($itemCampos as $campoIndex => $campoValor) {
                            if (isset($_campos[$campoIndex])) {
                                $campoNome = $_campos[$campoIndex];
                                
                                if ($campoNome == 'relatorioUtm') {
                                    $urmValor = explode('=', $campoValor);
                                    
                                	$_arr['relatorioUtmValor'] = $urmValor[1];
                                	$_arr['relatorioUtmTipo']  = $urmValor[0];
                                }
                                
                                if ($campoNome == 'relatorioReceitaTotal') { 
                                    if (strlen($campoValor) > 4) {
                                        $decimal     = substr($campoValor, -6);
                                        $campoValor = number_format((str_replace($decimal, '', $campoValor) . '.' . $decimal), 2, '.', '');
                                        
                                        if ($campoValor > 0)
                                            $campoValor = $campoValor * $dolarHoje;
                                            
                                    } else {
                                        $campoValor = 0;
                                    }
                                }
                                
                                if ($campoNome == 'relatorioEcpm') { 
                                    if (strlen($campoValor) > 4) {
                                        $decimal    = substr($campoValor, -6);
                                        $campoValor = number_format((str_replace($decimal, '', $campoValor) . '.' . $decimal), 2, '.', '');
                                            
                                        if ($campoValor > 0)
                                            $campoValor = $campoValor * $dolarHoje;
                                            
                                    } else {
                                        $campoValor = 0;
                                    }
                                }
                                
                                if ($campoNome == 'relatorioCTR') {
                                    $campoValor = str_replace('0.', '', $campoValor);
                                    $campoValor = substr($campoValor, 0, 2);
                                }
                	
                                $_arr[$campoNome] = $campoValor;
                            }
                        }
                        
                        if (count($_arr) > 0)
                            $arr[] = $_arr;
                    }
                    
                    foreach ($arr as $itemIndex => $itemValor) {
                        $relatorioData = $itemValor['relatorioData'];
                        $relatorioUtm  = $itemValor['relatorioUtm'];
                        
                        $cadastrado = mysqli_query($con, "SELECT *
                            FROM adx_relatorios 
                            WHERE 
                                relatorioData = '$relatorioData' AND 
                                relatorioUtm  = '$relatorioUtm'
                            LIMIT 1");
                            
                        if ($cadastrado) {
                            if (mysqli_num_rows($cadastrado) == 0) {
                                insert('adx_relatorios', $itemValor);
                            } else {
                                $relatorioValor = mysqli_fetch_array($cadastrado);
                                if (isset($relatorioValor['relatorioID'])) {
                                    $relatorioID = $relatorioValor['relatorioID'];
                                    
                                    update('adx_relatorios', $itemValor, 'relatorioID = ' . $relatorioID);
                                }
                            }
                        }
                    }
                }
        
                break;
            }
        }
    }
    
    exit;
    
} else { 
    
    $contador = file_get_contents('criativos_contador.txt');
    $contador = (int) $contador;
    
    $execucoes = (int) getConfig('cron_facebook_execucoes');
    if ($execucoes == 0)
        $execucoes = 4;
        
    $arrDatas = array(
        date('Y-m-d'),
        date('Y-m-d', strtotime('-1 day')),
    );
    
    //if ($horaAtual < 5)
    //    $arrDatas[] = date('Y-m-d', strtotime('-1 day'));
    
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
    
    $pasta         = ABSPATH . 'cron/facebook/data/';
    $arquivos      = glob($pasta . '*.txt');
    
    $contaNome = '';
    if (isset($_GET['conta']))
        $contaNome = $_GET['conta'];
        
    $arrArquivos = array();
    foreach ($arquivos as $arquivo) { 
        if (empty($contaNome)) {
            $arrArquivos[] = $arquivo;
        } else {
            if (preg_match('/' . $contaNome . '/', $arquivo))
                $arrArquivos[] = $arquivo;
        }
    }
    
    $totalArquivos = count($arrArquivos);
    if ($totalArquivos == 0) {
        echo 'parar';
        
    } else {
        
        shuffle($arrArquivos);
        
        foreach ($arrArquivos as $arquivo) {
            if (is_file($arquivo)) {
                $html = file_get_contents($arquivo);
                $json = (array) json_decode($html, true);
                $json = array_filter($json);
                
                unlink($arquivo);
            
                $nome = '';
                if (isset($_GET['nome']))   
                    $nome = $_GET['nome'];
                    
                $logTexto  = 'Cron: ' . $nome . PHP_EOL;
                $logTexto .= 'Data: ' . date('d/m/Y H:i:s') . PHP_EOL;
                    
                $arr = array();
                
                foreach ($json as $itemIndex => $itemValor) {
                    $arr[] = $itemValor;
                
                    $logTexto .= 'Conta: ' . $itemValor['contaNome'] . PHP_EOL;
                }
                
                file_put_contents('dados.txt', json_encode($json));
                
                $tempoInicio = strtotime('now');
                
                foreach ($arr as $contaValor) {
                    
                    $contaID     = $contaValor['conta_id'];
                    $contaToken  = $contaValor['token'];
                    $contaStatus = $contaValor['itemSituacao'];
                    
                    echo 'CONTA ATUAL: ' . $contaID . '<br />';
                        
                    $_arrCampanhas = array();
                        
                    foreach ($arrDatas as $dataValor) {
                        $campos = 'account_name,account_id,campaign_id,campaign_name,spend,impressions,clicks,ctr,cpc,actions{action_type,value}';
                        
                        $topo = 'Conta Nome,Conta ID, Campanha ID, Campanha Nome, Custo, Impressões, Cliques, CTR, CPC, Pagina Views, Data final,  Pais, Status';
                        
                        $url = "https://graph.facebook.com/v17.0/act_" . $contaID . "/insights?access_token=" . $contaToken . "&limit=500&sort=reach_descending&level=campaign&breakdowns=country&fields=" . $campos . "&filtering=[{%22field%22:%22action_type%22,%22operator%22:%22IN%22,%22value%22:[%22landing_page_view%22]}]";
                        
                        $url .= "&time_range={%27since%27:%27" . $dataValor . "%27,%27until%27:%27" . $dataValor . "%27}";
                        
                        $response = file_get_contents($url);
                        
                        $contador = $contador + 1;
                        
                        // echo '$url ' . $url . '<br />';
                        
                        $arrStatus = array();
                            
                        $linkStatus     = 'https://graph.facebook.com/v17.0/act_' . $contaID . '/campaigns?fields=name,status&limit=500&access_token=' . $contaToken;
                        $responseStatus = file_get_contents($linkStatus);
                        
                        $contador = $contador + 1;
                        
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
                                
                                //pre($itemValor);
                                //exit;
                            
                                $itemValor['status'] = $status;
                                    
                                $campanhaNome         = $itemValor['campaign_name'];
                                $paisSigla            = $itemValor['country'];
                                $campanhaData         = $dataValor;
                                $paisNome             = arrPais($paisSigla, true);
                                $itemValorGasto       = $itemValor['spend'];
                                $itemCPR              = $itemValor['cpc'];
                                $itemImpressoes       = $itemValor['impressions'];
                                $campanhaIDdaConta    = $itemValor['account_id'];
                                $campanhaIDdaCampanha = $itemValor['campaign_id'];
                                $nomeDaConta          = $itemValor['account_name'];
                                
                                $itemResultados = 0;
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
                                        
                                        $__retorno = update('facebook_itens', $data, 'itemID = ' . $itemID);
                                        if (!$__retorno) 
                                          	echo 'ERROR ' . mysqli_error($con) . '<br />';
                                      
                                    } else {
                                        echo 'Cadastrado<br />';
                                        
                                        $data['itemCadastroData'] = date('Y-m-d H:i:s');
                                        
                                        $__retorno = insert('facebook_itens', $data);
                                      	if (!$__retorno) 
                                          	echo 'ERROR ' . mysqli_error($con) . '<br />';
                                    }
                                }
                                
                                $__sql = "SELECT * 
                                    FROM `analytics_gestor_pais` 
                                    WHERE 
                                        gestorPais_sessionCampaignName = '$campanhaNome' AND 
                                        gestorPais_country             = '$paisNome' AND 
                                        gestorPais_date                = '$campanhaData'
                                    LIMIT 1;";
                                    
                                $query = mysqli_query($con, $__sql);
                                    
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
                                '_clienteID'                   => $clienteID
                            );
                            
                            $retorno = update('analytics_campanhas', $data, 'campanhaID = ' . $campanhaID);
                            
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
                    
                }
                
                $tempoFinal = strtotime('now');
                
                $logTexto .= 'Tempo de execucao: ' . ($tempoFinal - $tempoInicio) . ' segundos ' . PHP_EOL . PHP_EOL;
                
                file_put_contents('log.txt', $logTexto, FILE_APPEND);
                
                break;
            }
        }
    }
    
    file_put_contents('criativos_contador.txt', $contador);
}