<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'funcoes.php'); 

set_time_limit(0);

$pasta    = ABSPATH . 'cron/arquivos/gestao_roi/';
$arquivos = glob($pasta . '*.txt');

$total = 0;
foreach ($arquivos as $arquivoValor) {
    if (preg_match('/\.txt/', $arquivoValor)) {
        $total = $total + 1;
    }    
}

if ($total > 0) {
    echo 'parar';
} else {
    $where   = array();
    $where[] = 'cadastroSituacao = 1';
    
    if (isset($_GET['conta'])) {
        $contaID = (int) $_GET['conta'];
        if ($contaID > 0) {
            $where[] = 'cadastroClienteID = ' . $contaID;
        }
    }
    
    $sql = "SELECT * 
        FROM cliente_robo_roi " . (count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '') ;
        
    $itens = mysqli_query($con, $sql);
     
    if ($itens) {
        while ($itemValor = mysqli_fetch_array($itens)) {
                
            $clienteID              = $itemValor['_clienteID'];
            $cadastroID             = $itemValor['cadastroID'];
            $cadastroNome           = $itemValor['cadastroNome'];
            $cadastroTipo           = $itemValor['cadastroTipo'];
            $cadastroDias           = $itemValor['cadastroDias'];
            $cadastroDiasFinal      = $itemValor['cadastroDiasFinal'];
            $cadastroRoiInicio      = $itemValor['cadastroRoiInicio'];
            $cadastroRoiFinal       = $itemValor['cadastroRoiFinal'];
            $cadastroCustoInicio    = $itemValor['cadastroCustoInicio'];
            $cadastroCustoFinal     = $itemValor['cadastroCustoFinal'];
            $cadastroStatus         = $itemValor['cadastroStatus'];
            $cadastroBuscaStatus    = $itemValor['cadastroBuscaStatus'];
            $cadastroHoraInicio     = $itemValor['cadastroHoraInicio'];
            $cadastroHoraFinal      = $itemValor['cadastroHoraFinal'];
            $cadastroClienteID      = $itemValor['cadastroClienteID'];
            $cadastroBuscaTempo     = $itemValor['cadastroBuscaTempo'];
            $cadastroTimezone       = $itemValor['cadastroTimezone'];
            $cadastroRoiHistorico   = $itemValor['cadastroRoiHistorico'];
            $cadastroRoiGeralInicio = $itemValor['cadastroRoiGeralInicio'];
            $cadastroRoiGeralFinal  = $itemValor['cadastroRoiGeralFinal'];
          
            $cadastroRoiHistorico = (int) $cadastroRoiHistorico;
            
            $cadastroRoiGeralInicio = (float) $cadastroRoiGeralInicio;
            $cadastroRoiGeralFinal  = (float) $cadastroRoiGeralFinal;
            
            $cadastroCustoInicio = (float) $cadastroCustoInicio;
            $cadastroCustoFinal  = (float) $cadastroCustoFinal;
            
            $cadastroRoiInicio = (float) $cadastroRoiInicio;
            $cadastroRoiFinal  = (float) $cadastroRoiFinal;
            
            $cadastroHoraInicio = (int) $cadastroHoraInicio;
            $cadastroHoraFinal  = (int) $cadastroHoraFinal;
            
            if (empty($cadastroRoiInicio) || ($cadastroRoiInicio == 0))
                $cadastroRoiInicio = '0.00';
                
            if (empty($cadastroCustoInicio) || ($cadastroCustoInicio == 0))
                $cadastroCustoInicio = '0.00';
            
            $filtroValido = true;
            
            $data = array(
                'cadastroRodouEm' => date('Y-m-d H:i:s')
            );
            
            update('cliente_robo_roi', $data, 'cadastroID = ' . $cadastroID);
            
            $dataAtual = date('Y-m-d');
            $horaAtual = date('H');
            
            if (empty($cadastroTipo))
                $filtroValido = false;
                
            if (empty($cadastroRoiInicio) && empty($cadastroRoiFinal))
                $filtroValido = false;
                
            if ($filtroValido) {  
                
                $arrFusoHorario = array();
                
                $contas = mysqli_query($con, "SELECT * 
                    FROM tiktok_contas");
                    
                if ($contas) {
                    while ($contaValor = mysqli_fetch_array($contas)) {
                        $contaContas = (array) json_decode($contaValor['contaContas'], true);
                        $contaContas = array_filter($contaContas);
                        
                        //pre($contaContas);
                        
                        foreach ($contaContas as $contaValor) {
                            $codigo                  = $contaValor['codigo'];
                            $arrFusoHorario[$codigo] = $contaValor['fuso_horario'];
                        }
                    }
                }
                
                $_link = getConfig('facebook_api_principal'); 
              	$_link = rtrim($_link, '/') . '/api/';
                    
                $where = array();
                
                // Custo
                
                if (($cadastroCustoInicio > 0) && ($cadastroCustoFinal > 0)) {
                    $where[] = "relatorioCustoValor >= '$cadastroCustoInicio' ";
                    $where[] = "relatorioCustoValor <= '$cadastroCustoFinal' ";
                    
                } else if (($cadastroCustoInicio == '0.00') && ($cadastroCustoFinal > 0)) {
                    $where[] = "relatorioCustoValor >= '$cadastroCustoInicio' ";
                    $where[] = "relatorioCustoValor <= '$cadastroCustoFinal' ";
                    
                } else if ($cadastroCustoInicio > 0) {
                    $where[] = "relatorioCustoValor >= '$cadastroCustoInicio' ";
                }
                    
                // Dias
                
                if (!empty($cadastroDias) && !empty($cadastroDiasFinal)) {
                    $where[] = "relatorioDiasAtivo >= '$cadastroDias' ";
                    $where[] = "relatorioDiasAtivo <= '$cadastroDiasFinal' ";
                
                    
                } else if (!empty($cadastroDiasFinal)) {
                    $where[] = "relatorioDiasAtivo <= '$cadastroDiasFinal' ";
                    
                } else if (!empty($cadastroDias)) {
                    $where[] = "relatorioDiasAtivo >= '$cadastroDias' ";
                }
                
                // Roi
                $aplicarRoi = false;
                if ($cadastroBuscaStatus == 'ACTIVE')
                    $aplicarRoi = true;
                    
                if ( ($cadastroBuscaStatus == 'PAUSED') && ($cadastroRoiHistorico == 0) )
                    $aplicarRoi = true;
                    
                if ($aplicarRoi) {
                    if (($cadastroRoiInicio > 0) && ($cadastroRoiFinal > 0)) {
                        $where[] = "relatorioRoiFinalValor >= '$cadastroRoiInicio' ";
                        $where[] = "relatorioRoiFinalValor <= '$cadastroRoiFinal' ";
                        
                    } else if (($cadastroRoiInicio == '0.00') && ($cadastroRoiFinal > 0)) {
                        $where[] = "relatorioRoiFinalValor >= '$cadastroRoiInicio' ";
                        $where[] = "relatorioRoiFinalValor <= '$cadastroRoiFinal' ";
                        
                    } else if ($cadastroRoiInicio > 0) {
                        $where[] = "relatorioRoiFinalValor >= '$cadastroRoiInicio' ";
                    }
                }
                
                // Roi Geral
                if (($cadastroRoiGeralInicio > 0) && ($cadastroRoiGeralFinal > 0)) {
                    $where[] = "relatorioRoiGeralValor >= '$cadastroRoiGeralInicio' ";
                    $where[] = "relatorioRoiGeralValor <= '$cadastroRoiGeralFinal' ";
                    
                } else if (($cadastroRoiGeralInicio == '0.00') && ($cadastroRoiGeralFinal > 0)) {
                    $where[] = "relatorioRoiGeralValor >= '$cadastroRoiGeralInicio' ";
                    $where[] = "relatorioRoiGeralValor <= '$cadastroRoiGeralFinal' ";
                    
                } else if ($cadastroRoiGeralInicio > 0) {
                    $where[] = "relatorioRoiGeralValor >= '$cadastroRoiGeralInicio' ";
                }
                
                // Cliente 
                
                if ($cadastroClienteID > 0)
                    $where[] = '_clienteID = ' . $cadastroClienteID;
                    
                // Status 
                
              	/*
                if ($cadastroBuscaStatus == 'PAUSED') {
                    $where[] = 'relatorioRoboStatus = 1';
                } else {
                    $where[] = 'relatorioRoboStatus = 2';
                } */
                
                // Data
              
              	/*
                if ($cadastroBuscaStatus == 'PAUSED') { 
                    $where[] = '(relatorioData = CURDATE() OR relatorioData = DATE_ADD(CURDATE(), INTERVAL -1 DAY))';
                    
                } else {
                */
              
                if ($cadastroBuscaTempo == 1 || $cadastroBuscaTempo == 0) {
                  	$where[] = 'relatorioData = CURDATE()';
                } else {
                  	$where[] = 'relatorioData = DATE_ADD(CURDATE(), INTERVAL -1 DAY)';
                }
                
              	if ($cadastroTipo == 'facebook') {
                  	$where[] = "(relatorioTipo = 'facebook' OR relatorioTipo IS NULL)";
                } else {
                  	$where[] = "relatorioTipo = 'tiktok'";
                }
                
                $sql = "SELECT *
                    FROM adx_relatorios 
                    WHERE
                        relatorioUtmTipo = 'campaign_id' " . (count($where) > 0 ? ' AND ' . implode(' AND ', $where) : '');
                                 
                $relatorios = mysqli_query($con, $sql);
                
                if ($relatorios) {
                    while ($relatorioValor = mysqli_fetch_array($relatorios)) {
                        
                        $relatorioID           = $relatorioValor['relatorioID']; 
                        $relatorioUtmValor     = $relatorioValor['relatorioUtmValor']; 
                        $relatorioData         = $relatorioValor['relatorioData']; 
                        $relatorioUtmSource    = $relatorioValor['relatorioUtmSource'];
                        $relatorioCampanhaNome = $relatorioValor['relatorioCampanhaNome'];
                        $relatorioTipo         = $relatorioValor['relatorioTipo'];
                        $_clienteID            = $relatorioValor['_clienteID'];
                        $contaID               = $relatorioValor['_contaID'];
                      	
                      	$dataBusca = $relatorioData;
                      	if ($cadastroRoiHistorico > 0)
                          	$dataBusca = date('Y-m-d', strtotime('-1 day'));
                      
                        $campanhaTiktokStatus   = '';
                        $campanhaFacebookStatus = '';
                      
                        if ($relatorioTipo == 'tiktok') {
                            $custos = mysqli_query($con, "SELECT *
                                FROM tiktok_custos 
                                WHERE 
                                    custoCampanhaID = '$relatorioUtmValor' AND 
                                    custoData       = '$dataBusca'
                                ORDER BY custoID DESC
                                LIMIT 1;");

                            if ($custos) {
                                $custoValor = mysqli_fetch_array($custos);
                                if (isset($custoValor['custoID'])) {
                                    $campanhaTiktokStatus = $custoValor['custoStatusCampanha'];
                                }
                            }
                        }

                        if ( $relatorioTipo == 'facebook' || empty($relatorioTipo) ) { 
                            $contas = mysqli_query($con, "SELECT * 
                                FROM facebook_itens 
                                WHERE 
                                    itemCampanhaID = '$relatorioUtmValor' AND 
                                    itemData       = '$dataBusca'
                                LIMIT 1");

                            if ($contas) {
                                $contaValor = mysqli_fetch_array($contas);
                                if (isset($contaValor['itemStatus'])) {
                                    $campanhaFacebookStatus = $contaValor['itemStatus'];
                                }
                            }

                            if ($campanhaFacebookStatus == 'ativo')
                                $campanhaFacebookStatus = 'ACTIVE';

                            if ($campanhaFacebookStatus == 'inativo')
                                $campanhaFacebookStatus = 'PAUSED';
                        }
                      	
                      	$statusAtual = 'ACTIVE';
                      	if ($relatorioTipo == 'tiktok') {                                                
                          	if ($campanhaTiktokStatus == 'ACTIVE')
                              	$statusAtual = 'PAUSED';
                        } else {
                          	if ($campanhaFacebookStatus == 'ACTIVE')
                              	$statusAtual = 'PAUSED';
                        }
                      
                        if (!empty($cadastroBuscaStatus)) {
                            if ($relatorioTipo == 'tiktok') {                                                
                                if ($cadastroBuscaStatus <> $campanhaTiktokStatus)
                                    continue;
                            } else {
                                if ($cadastroBuscaStatus <> $campanhaFacebookStatus)
                                    continue;
                            }
                        }
                      	
                        $cookies = ''; 
                        $aadvid  = '';
                        
                        $horaAtual = date('H');
                        $dataAtual = date('d-m-Y H:i:s');
                        
                        if ($cadastroTipo == 'tiktok') {
                            $custoAdvertiserID = 0;
                            
                            $custoLista = mysqli_query($con, "SELECT 
                                    custoAdvertiserID
                                FROM tiktok_custos 
                                WHERE  
                                    custoCampanhaID = '$relatorioUtmValor' AND 
                                    custoData       = '$dataBusca' ");
                                    
                            if ($custoLista) {
                                $custoItemValor = mysqli_fetch_array($custoLista);
                                if (isset($custoItemValor['custoAdvertiserID'])) {
                                    $custoAdvertiserID = $custoItemValor['custoAdvertiserID'];   
                                }
                            }
                            
                            if ($custoAdvertiserID > 0) {
                                if (isset($arrFusoHorario[$custoAdvertiserID])) {
                                    $_horaLocal = $arrFusoHorario[$custoAdvertiserID];
                                    if (!empty($_horaLocal))
                                        $horaAtual = date('H', strtotime(str_replace('UTC', '', $arrFusoHorario[$custoAdvertiserID]) .' hours'));
                                }
                            }
                        }
                    
                        if ($cadastroTipo == 'facebook') {
                            
                            $contaItens = mysqli_query($con, "SELECT * 
                       		    FROM `facebook_conta_itens` 
                       		    WHERE 
                       		        itemValor = '$contaID' AND 
                                    itemData  = '$dataBusca'
                       		   LIMIT 1");
                       		   
                       		if ($contaItens) { 
                       		    $contaValor = mysqli_fetch_array($contaItens); 
                       		    if (isset($contaValor['itemID'])) {
                       		        $itemTimezoneName = $contaValor['itemTimezoneName'];
                       		        
                       		        if (!empty($itemTimezoneName)) {
                                        $fuso = new DateTimeZone($itemTimezoneName);
                                        $data = new DateTime($dataAtual);
                                        $data->setTimezone($fuso);
                                        
                                        $horaAtual = $data->format('H');
                       		        }
                       		    }
                       		}
                        }
                   		
                   		if ($cadastroHoraInicio > 0 && $cadastroHoraFinal > 0) {
                            if ($horaAtual < $cadastroHoraInicio || $horaAtual > $cadastroHoraFinal)
                                continue;
                                
                        } else if ($cadastroHoraInicio > 0) {
                            if ($horaAtual < $cadastroHoraInicio)
                                continue;
                        } else if ($cadastroHoraFinal > 0) { 
                            if ($horaAtual > $cadastroHoraFinal)
                                continue;
                        }
                        
                        /* Historico de roi */
                      
                        if ($cadastroRoiHistorico > 0) {
                            $campanhaReativar = false;

                            if ($cadastroBuscaStatus == 'ACTIVE') { 
                              
                                $historico = mysqli_query($con, "SELECT * 
                                    FROM adx_relatorios 
                                    WHERE 
                                        relatorioUtmValor  = '$relatorioUtmValor' AND 
                                        relatorioUtmTipo   = 'campaign_id' AND 
                                        relatorioData     <= '$dataBusca'
                                    ORDER BY relatorioData DESC
                                    LIMIT $cadastroRoiHistorico");
                                        
                                if ($historico) {
                                    $historicoTotal = mysqli_num_rows($historico);
                                    if ( ($historicoTotal > 0) && ($historicoTotal <= $cadastroRoiHistorico) ) {
                                        while ($historicoValor = mysqli_fetch_array($historico)) { 
                                            $historicoRoiFinalValor = $historicoValor['relatorioRoiFinalValor']; 
                                            
                                            if ($cadastroRoiInicio > 0) {
                                                if ($historicoRoiFinalValor > $cadastroRoiInicio)
                                                    $campanhaReativar = true; 
                                            }
                                        }
                                    }
                                }
                            }
                          
                            if ($cadastroBuscaStatus == 'PAUSED') { 
                              
                                $historico = mysqli_query($con, "SELECT * 
                                    FROM adx_relatorios 
                                    WHERE 
                                        relatorioUtmValor  = '$relatorioUtmValor' AND 
                                        relatorioUtmTipo   = 'campaign_id' AND 
                                        relatorioData     <= '$dataBusca'
                                    ORDER BY relatorioData DESC
                                    LIMIT $cadastroRoiHistorico");
                                    
                                if ($historico) {
                                    $historicoTotal = mysqli_num_rows($historico);
                                    if ( ($historicoTotal > 0) && ($historicoTotal <= $cadastroRoiHistorico) ) {
                                        
                                        if ($cadastroRoiInicio > 0) {
                                            while ($historicoValor = mysqli_fetch_array($historico)) { 
                                                $historicoRoiFinalValor = $historicoValor['relatorioRoiFinalValor']; 
                                                
                                                if ($historicoRoiFinalValor > $cadastroRoiInicio)
                                                    $campanhaReativar = true; 
                                            }
                                        }
                                    }
                                }
                            }

                            if (!$campanhaReativar)
                                continue;
                        }
                        
                        if ($cadastroTipo == 'tiktok') {
                            
                            $custos = mysqli_query($con, "SELECT *
                                FROM tiktok_custos 
                                WHERE 
                                    custoCampanhaID = '$relatorioUtmValor' AND 
                                    custoData       = '$dataBusca'
                                LIMIT 1;");
                                
                            if ($custos) {
                                $custoValor = mysqli_fetch_array($custos);
                                if (isset($custoValor['custoID'])) {
                                    $custoID             = $custoValor['custoID'];
                                    $custoCampanhaID     = $custoValor['custoCampanhaID'];
                                    $custoStatusCampanha = $custoValor['custoStatusCampanha'];
                                    $custoAdvertiserID   = $custoValor['custoAdvertiserID'];
                                    
                                  	$linkApi = getConfig('tiktok_api_principal'); 
              						$linkApi = rtrim($linkApi, '/') . '/api/';
                                  
                                    $linkAtivar    = $linkApi . '{TIPO}/status/ativar';
                                    $linkDesativar = $linkApi . '{TIPO}/status/desativar';
                                    
                                    $link = str_replace('{TIPO}', 'campanhas', $linkAtivar);
                                    if ($custoStatusCampanha == 'ativo')
                                        $link = str_replace('{TIPO}', 'campanhas', $linkDesativar);
                                    
                                    $contaNavegador = '';
                                    $contaHost      = '';
                                    $contaUsuario   = '';
                                    $contaSenha     = '';
                                    
                                    $contas = mysqli_query($con, "SELECT * 
                                        FROM tiktok_contas");
                                    
                                    if ($contas) {
                                        while ($contaValor = mysqli_fetch_array($contas)) {
                                            $contaContas = (array) json_decode($contaValor['contaContas'], true);
                                            $contaContas = array_filter($contaContas);
                                            
                                            foreach ($contaContas as $itemConta) {
                                                
                                                if ($itemConta['codigo'] == $custoAdvertiserID) {
                                                    $contaNavegador = $contaValor['contaNavegador'];
                                                    $contaHost      = $contaValor['contaHost'];
                                                    $contaUsuario   = $contaValor['contaUsuario'];
                                                    $contaSenha     = $contaValor['contaSenha'];
                                                
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    
                                    $data = array(
                                        'link'                => $link,
                                        'relatorioID'         => $relatorioID,
                                        'campanhaID'          => $relatorioUtmValor,
                                        'contaID'             => $contaID,
                                        'relatorioUtmSource'  => $relatorioUtmSource,
                                        'token'               => '',
                                        'proxyHost'           => $contaHost,
                                        'proxyUsuario'        => $contaUsuario,
                                        'proxySenha'          => $contaSenha,
                                        'cadastroStatus'      => $cadastroStatus,
                                        'cadastroBuscaStatus' => $cadastroBuscaStatus,
                                        'cadastroBuscaTempo'  => $cadastroBuscaTempo,
                                        'cadastroID'          => $cadastroID,
                                        'cookies'             => $contaNavegador,
                                        'aadvid'              => $custoAdvertiserID
                                    );
                                    
                                    $arquivo = $pasta . $relatorioUtmValor . '.txt';
                                    if (!is_file($arquivo)) {
                                        file_put_contents($arquivo, json_encode($data));
                                    }
                                }
                            }
                        }
                        
                        if ($cadastroTipo == 'facebook') {
                            $file = 'https://gestor.naveads.com/data/config_' . $_clienteID . '.txt';
                          
                          	$html = file_get_contents($file);
                            $json = (array) json_decode($html, true); 
                            $json = array_filter($json); 
                          	
                          	$accessToken  = $json['config_token'];
                            $proxyHost    = $json['config_host'];
                            $proxyUsuario = $json['config_usuario'];
                            $proxySenha   = $json['config_senha'];
                            
                            $accessToken = (array) $accessToken;
                            
                            shuffle($accessToken);
                            
                            if (isset($accessToken[0]['token'])) {
                                $accessToken = $accessToken[0]['token'];
                                
                                if (!empty($accessToken)) { 
                                    
                                    if (empty($proxyHost)) {
                                        $_configHost = getConfig('proxy_host');
                                        if (!empty($_configHost))
                                            $proxyHost = $_configHost;
                                    }
                                    
                                    if (empty($proxyUsuario)) {
                                        $_configUsuario = getConfig('proxy_usuario'); 
                                        if (!empty($_configUsuario))
                                            $proxyUsuario = $_configUsuario;
                                    }
                                    
                                    if (empty($proxySenha)) {
                                        $_configSenha   = getConfig('proxy_senha');
                                        if (!empty($_configSenha))
                                            $proxySenha = $_configSenha;
                                    }
                                    
                                    if ($relatorioUtmSource == 'facebook') {
                                        $contas = mysqli_query($con, "SELECT * 
                                            FROM facebook_itens 
                                            WHERE 
                                                itemCampanhaID = '$relatorioUtmValor' AND 
                                                itemData       = '$dataBusca'
                                            LIMIT 1");
                                            
                                        if ($contas) {
                                            $contaValor = mysqli_fetch_array($contas);
                                            if (isset($contaValor['itemContaID'])) {
                                                $contaID = $contaValor['itemContaID'];
                                            }
                                        }
                                    }
                                    
                                  	$link = $_link . 'campanhas/status/desativar';
                                    if ($cadastroStatus == 'ACTIVE')
                                      	$link = $_link . 'campanhas/status/ativar'; 
                                      	                                    
                                    $data = array(
                                        'link'                => $link,
                                        'relatorioID'         => $relatorioID,
                                        'campanhaID'          => $relatorioUtmValor,
                                        'contaID'             => $contaID,
                                        'relatorioUtmSource'  => $relatorioUtmSource,
                                        'token'               => $accessToken,
                                        'proxyHost'           => $proxyHost,
                                        'proxyUsuario'        => $proxyUsuario,
                                        'proxySenha'          => $proxySenha,
                                        'cadastroStatus'      => $cadastroStatus,
                                        'cadastroBuscaStatus' => $cadastroBuscaStatus,
                                        'cadastroBuscaTempo'  => $cadastroBuscaTempo,
                                        'cadastroID'          => $cadastroID,
                                        'cookies'             => $cookies,
                                        'aadvid'              => $aadvid,
                                      	'relatorioData'       => $relatorioData
                                    );
                                    
                                    $arquivo = $pasta . $relatorioUtmValor . '.txt';
                                    if (!is_file($arquivo)) {
                                        file_put_contents($arquivo, json_encode($data));
                                    }
                                }
                            }
                        }
                    } 
                }
            }
        }
    }
}