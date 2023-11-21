<?php 
header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);


set_time_limit(0);

$pasta    = ABSPATH . 'cron/arquivos/tiktok_custos/';
$arquivos = glob($pasta . '*.txt');

$total = 0;
foreach ($arquivos as $arquivoValor) {
    if (preg_match('/\.txt/', $arquivoValor)) {
        $total = $total + 1;
    }    
}

if ($total == 0) {
    echo 'parar';
} else {
    
    foreach ($arquivos as $arquivoValor) {
        
        $html  = file_get_contents($arquivoValor);
        $dados = (array) json_decode($html, true);
        $dados = array_filter($dados);
        
        unlink($arquivoValor);
        
        $link = 'http://54.37.11.148:8016/Relatorio';
        
        echo '$arquivoValor ' . $arquivoValor . '<br />';
        
        pre($dados);
        
        
        if (isset($dados['contaNavegador'])) {
            $contaNavegador = $dados['contaNavegador'];
            $contaHost      = $dados['contaHost'];
            $contaUsuario   = $dados['contaUsuario'];
            $contaSenha     = $dados['contaSenha'];
            $contaNome      = $dados['contaNome'];
            $contaID        = $dados['contaID'];
            $dataInicio     = $dados['data_inicio'];
            $dataFinal      = $dados['data_final'];
                            
            $post = '{
                "DataInicial": "' . $dataInicio . '",
                "DataFinal": "' . $dataFinal . '",
                "QueryList": [
                    "stat_cost",
                    "cpc",
                    "show_cnt",
                    "click_cnt",
                    "ctr",
                    "time_attr_convert_cnt",
                    "time_attr_conversion_cost",
                    "time_attr_web_landing_page_view",
                    "time_attr_cost_per_web_landing_page_view"
                ],
                "Dimensions": [
                    "campaign_name",
                    "country_id",
                    "stat_time_day",
                    "advertiser_name",
                    "advertiser_id",
                    "campaign_id",
                    "ad_name",
                    "ad_id",
                    "creative_name",
                    "creative_id"
                ]
            }';
            
            $header = array(
                'Cookies:' . $contaNavegador,
                'ProxyHost:' . $contaHost,
                'ProxyUsuario:' . $contaUsuario,
                'ProxySenha:' . $contaSenha,
                'Content-Type:application/json',
                'Aadvid:' . $contaID,
                'Content-Length:' . strlen($post),
            );
            
            $ch = curl_init(); 
        
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            
             
            curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
            
            $output = curl_exec($ch);
            curl_close($ch);
            
            if (empty($output)) {
                $avisos = mysqli_query($con, "SELECT * 
                    FROM sistema_avisos 
                    WHERE 
                        avisoTipo = 'tiktok_custos_executar' 
                    LIMIT 1; ");
                        
                if ($avisos) {
                    $avisosTotal = mysqli_num_rows($avisos);
                    if ($avisosTotal == 0) {
                        insert('sistema_avisos', array(
                            'avisoTitulo'   => 'Erro',
                            'avisoTexto'    => 'Tiktok Custos Executar não está retornando dados da API na conta: <strong>' . $contaNome . '</strong> - <strong>' . $contaID . '</strong>.',
                            'avisoTipo'     => 'tiktok_custos_executar',
                            'avisoCriadoEm' => date('Y-m-d H:i:s')
                        ));
                    }
                }
            }
            
            $_arrLinhas = str_getcsv($output, "\n");
            $_arrLinhas = array_filter($_arrLinhas);
            
            $arrLinhas = array();
            foreach ($_arrLinhas as $itemValor) {
                
                pre($itemValor);
                
                $itemValor = str_replace('Korea,Republic of', 'Korea', $itemValor);
                $itemValor = str_replace('Korea-Republic of', 'Korea', $itemValor);
                $itemValor = str_getcsv($itemValor);
                
                $arrLinhas[] = $itemValor;
            }
            
            $total = count($arrLinhas);
            if ($total > 0) {
                $arrTopo = $arrLinhas[0];
                
                unset($arrLinhas[0]);
                
                $importados = 0;
                
                $custoCampanhaNome_index = '';
                $custoPaisNome_index = '';
                $custoData_index = '';
                $custoNome_index = '';
                $custoAdvertiserID_index = '';
                $custoCampanhaID_index = '';
                $custoAdGroupNome_index = '';
                $custoAdGroupID_index = '';
                $custoAdNome_index = '';
                $custoAdID_index = '';
                $custoValor_index = '';
                $custoCPC_index = '';
                $custoImpressoes_index = '';
                $custoCTR_index = '';
                $custoConversao_index = '';
                $custoTotalLandingPageView_index = '';
                $custoCostPerLandingPageView_index = '';
                $custoMoeda_index = '';
                $custoCliques_index = '';
                $custoCPA_index = '';
                
                $custoStatusCampanha_index = '';
                $custoStatusConjunto_index = '';
                $custoStatusAnuncio_index = '';
                
                foreach ($arrTopo as $topoIndex => $topoValor) {
                    $topoValor = trim($topoValor);
                    
                    if (preg_match('/Campaign name/', $topoValor))
                        $custoCampanhaNome_index = $topoIndex;
                    
                    if (preg_match('/Country\/Region/', $topoValor))
                        $custoPaisNome_index = $topoIndex;
                        
                    if ($topoValor == 'Date')
                        $custoData_index = $topoIndex;
                    
                    if ($topoValor == 'Name')
                        $custoNome_index = $topoIndex;
                    
                    if ($topoValor == 'Advertiser ID')
                        $custoAdvertiserID_index = $topoIndex;
                        
                        
                    if ($topoValor == 'StatusCampanha')
                        $custoStatusCampanha_index = $topoIndex;
                        
                    if ($topoValor == 'StatusConjunto')
                        $custoStatusConjunto_index = $topoIndex;
                        
                    if ($topoValor == 'StatusAnuncio')
                        $custoStatusAnuncio_index = $topoIndex;
                        
                        
                    if ($topoValor == 'Campaign ID')
                        $custoCampanhaID_index = $topoIndex;
                        
                    if (preg_match('/Ad Group Name/', $topoValor))
                        $custoAdGroupNome_index = $topoIndex;
                        
                    if ($topoValor == 'Ad group ID')
                        $custoAdGroupID_index = $topoIndex;
                        
                    if ($topoValor == 'Ad Name')
                        $custoAdNome_index = $topoIndex;
                        
                    if ($topoValor == 'Ad ID')
                        $custoAdID_index = $topoIndex;
                        
                    if ($topoValor == 'Cost')
                        $custoValor_index = $topoIndex;
                        
                    if ($topoValor == 'CPC (Destination)')
                        $custoCPC_index = $topoIndex;
                        
                    if ($topoValor == 'Impression')
                        $custoImpressoes_index = $topoIndex;
                        
                    if ($topoValor == 'Clicks (Destination)')
                        $custoCliques_index = $topoIndex;
                        
                    if ($topoValor == 'CTR (Destination)')
                        $custoCTR_index = $topoIndex;
                        
                    if ($topoValor == 'Conversions')
                        $custoConversao_index = $topoIndex;
                        
                    if ($topoValor == 'CPA')
                        $custoCPA_index = $topoIndex;
                        
                    if ($topoValor == 'Total Landing Page View')
                        $custoTotalLandingPageView_index = $topoIndex;
                        
                    if ($topoValor == 'Cost per Landing Page View')
                        $custoCostPerLandingPageView_index = $topoIndex;
                        
                    if ($topoValor == 'Currency')
                        $custoMoeda_index = $topoIndex;
                }
                
                foreach ($arrLinhas as $itemIndex => $itemValor) {
                    
                    $custoCampanhaNome = '';
                    if (isset($itemValor[$custoCampanhaNome_index]))
                        $custoCampanhaNome = $itemValor[$custoCampanhaNome_index];
                        
                    if (preg_match('/Total of/', $custoCampanhaNome))
                        continue;
                        
                    $custoPaisNome = '';
                    if (isset($itemValor[$custoPaisNome_index]))
                        $custoPaisNome = $itemValor[$custoPaisNome_index];
                        
                    $custoData = '';
                    if (isset($itemValor[$custoData_index]))
                        $custoData = $itemValor[$custoData_index];
                        
                    $custoNome = '';
                    if (isset($itemValor[$custoNome_index]))
                        $custoNome = $itemValor[$custoNome_index];
                        
                    $custoAdvertiserID = '';
                    if (isset($itemValor[$custoAdvertiserID_index]))
                        $custoAdvertiserID = $itemValor[$custoAdvertiserID_index];
                        
                    $custoCampanhaID = '';
                    if (isset($itemValor[$custoCampanhaID_index]))
                        $custoCampanhaID = $itemValor[$custoCampanhaID_index];
                        
                    $custoAdGroupNome = '';
                    if (isset($itemValor[$custoAdGroupNome_index]))
                        $custoAdGroupNome = $itemValor[$custoAdGroupNome_index];
                        
                    $custoAdGroupID = '';
                    if (isset($itemValor[$custoAdGroupID_index]))
                        $custoAdGroupID = $itemValor[$custoAdGroupID_index];
                        
                    $custoAdNome = '';
                    if (isset($itemValor[$custoAdNome_index]))
                        $custoAdNome = $itemValor[$custoAdNome_index];
                        
                    $custoAdID = '';
                    if (isset($itemValor[$custoAdID_index]))
                        $custoAdID = $itemValor[$custoAdID_index];
                        
                    $custoValor = '';
                    if (isset($itemValor[$custoValor_index]))
                        $custoValor = $itemValor[$custoValor_index];
                        
                    $custoValor = (float) $custoValor;
                        
                    $custoCPC = '';
                    if (isset($itemValor[$custoCPC_index]))
                        $custoCPC = $itemValor[$custoCPC_index];
                        
                    $custoImpressoes = '';
                    if (isset($itemValor[$custoImpressoes_index]))
                        $custoImpressoes = $itemValor[$custoImpressoes_index];
                        
                    $custoCliques = '';
                    if (isset($itemValor[$custoCliques_index]))
                        $custoCliques = $itemValor[$custoCliques_index];
                        
                    $custoCTR = '';
                    if (isset($itemValor[$custoCTR_index]))
                        $custoCTR = $itemValor[$custoCTR_index];
                        
                    $custoConversao = '';
                    if (isset($itemValor[$custoConversao_index]))
                        $custoConversao = $itemValor[$custoConversao_index];
                        
                    $custoCPA = '';
                    if (isset($itemValor[$custoCPA_index]))
                        $custoCPA = $itemValor[$custoCPA_index];
                        
                    $custoTotalLandingPageView = '';
                    if (isset($itemValor[$custoTotalLandingPageView_index]))
                        $custoTotalLandingPageView = $itemValor[$custoTotalLandingPageView_index];
                        
                    $custoCostPerLandingPageView = '';
                    if (isset($itemValor[$custoCostPerLandingPageView_index]))
                        $custoCostPerLandingPageView = $itemValor[$custoCostPerLandingPageView_index];
                        
                    $custoMoeda = '';
                    if (isset($itemValor[$custoMoeda_index]))
                        $custoMoeda = $itemValor[$custoMoeda_index];
                        
                    
                    $custoStatusCampanha = '';
                    if (isset($itemValor[$custoStatusCampanha_index]))
                        $custoStatusCampanha = $itemValor[$custoStatusCampanha_index];
                        
                    $custoStatusConjunto = '';
                    if (isset($itemValor[$custoStatusConjunto_index]))
                        $custoStatusConjunto = $itemValor[$custoStatusConjunto_index];
                        
                    $custoStatusAnuncio = '';
                    if (isset($itemValor[$custoStatusAnuncio_index]))
                        $custoStatusAnuncio = $itemValor[$custoStatusAnuncio_index];
                        
                    $paisSigla = arrPais($custoPaisNome, true);
                    
                    $cadastrado = mysqli_query($con, "SELECT *
                        FROM tiktok_custos 
                        WHERE 
                            custoAdID       = '$custoAdID' AND 
                            custoData       = '$custoData' AND 
                            custoPaisNome   = '$custoPaisNome' ");
                            
                    if ($cadastrado) {
                        
                        $data = array(
                            'custoCampanhaNome'           => $custoCampanhaNome,
                            'custoPaisNome'               => $custoPaisNome,
                            'custoPaisSigla'              => $paisSigla,
                            'custoData'                   => $custoData,
                            'custoNome'                   => $custoNome,
                            'custoAdvertiserID'           => $custoAdvertiserID,
                            'custoCampanhaID'             => $custoCampanhaID,
                            'custoAdGroupNome'            => $custoAdGroupNome,
                            'custoAdGroupID'              => $custoAdGroupID,
                            'custoAdNome'                 => $custoAdNome,
                            'custoAdID'                   => $custoAdID,
                            'custoValor'                  => $custoValor,
                            'custoCPC'                    => $custoCPC,
                            'custoImpressoes'             => $custoImpressoes,
                            'custoCliques'                => $custoCliques,
                            'custoCTR'                    => $custoCTR,
                            'custoConversao'              => $custoConversao,
                            'custoCPA'                    => $custoCPA,
                            'custoTotalLandingPageView'   => $custoTotalLandingPageView,
                            'custoCostPerLandingPageView' => $custoCostPerLandingPageView,
                            'custoMoeda'                  => $custoMoeda,
                            'custoStatusCampanha'         => $custoStatusCampanha,
                            'custoStatusConjunto'         => $custoStatusConjunto,
                            'custoStatusAnuncio'          => $custoStatusAnuncio
                        );
                        
                        pre($data);
                        
                        if (mysqli_num_rows($cadastrado) == 0) {
                            $data['custoCriadoEm'] = date('Y-m-d H:i:s');
                            $data['custoHash']     = md5(uniqid(rand(), true));
                            
                            $retorno = insert('tiktok_custos', $data);
                            if ($retorno) {
                                $importados++;
                            } else {
                                echo 'Erro: ' . mysqli_error($con);
                                
                                exit;
                            }
                            
                        } else {
                            $itemValor = mysqli_fetch_array($cadastrado);
                            if (isset($itemValor['custoID'])) {
                                $custoID = $itemValor['custoID'];
                                
                                $retorno = update('tiktok_custos', $data, 'custoID = ' . $custoID);
                                if ($retorno) {
                                    $importados++;
                                } else {
                                    echo 'Erro: ' . mysqli_error($con);
                                    
                                    exit;
                                }
                            }
                        }
                        
                        titktokCache($custoCampanhaID, $custoData);
                    }
                }
            }
            
            break;
        }
    }
}