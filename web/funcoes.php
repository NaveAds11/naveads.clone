<?php
function gerstaoRoiItens($data = array()) {
  	global $con;
  
  	$retorno          = array();
    $arrGestaoRoiHora = arrGestaoRoiHora();

    /* Pegar fuso horarios */
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
    
    /* Campos */
  	$campoStatus = '';
  	if (isset($data['campoStatus']))
  		$campoStatus = $data['campoStatus'];
  
    $campoFusoHorario = '';
    if (isset($data['campoFusoHorario']))
        $campoFusoHorario = $data['campoFusoHorario'];
  
  	$campoTempo = '';
  	if (isset($data['campoTempo']))
  		$campoTempo = (int) $data['campoTempo'];
  
  	$campoTipo = '';
  	if (isset($data['campoTipo']))
  		$campoTipo = $data['campoTipo'];
  
  	$campoRoi = '';
  	if (isset($data['campoRoi']))
  		$campoRoi = (float) $data['campoRoi'];
  
  	$campoRoiFinal = '';
  	if (isset($data['campoRoiFinal']))
  		$campoRoiFinal = (float) $data['campoRoiFinal'];
  
  	$campoCustoInicio = '';
  	if (isset($data['campoCustoInicio']))
  		$campoCustoInicio = (float) $data['campoCustoInicio'];
  
  	$campoCustoFinal = '';
  	if (isset($data['campoCustoFinal']))
  		$campoCustoFinal = (float) $data['campoCustoFinal'];
  
  	$campoDias = 0;
  	if (isset($data['campoDias']))
  		$campoDias = (int) $data['campoDias'];
  
  	$campoDiasFinal = 0;
  	if (isset($data['campoDiasFinal']))
  		$campoDiasFinal = (int) $data['campoDiasFinal'];
  
  	$campoGestorID = 0;
  	if (isset($data['campoGestorID']))
  		$campoGestorID = (int) $data['campoGestorID'];
  
  	$campoRoiGeralInicio = '';
  	if (isset($data['campoRoiGeralInicio']))
  		$campoRoiGeralInicio = $data['campoRoiGeralInicio'];
  
  	$campoRoiGeralFinal = '';
  	if (isset($data['campoRoiGeralFinal']))
  		$campoRoiGeralFinal = $data['campoRoiGeralFinal'];
  
  	$campoHoraInicio = '';
  	if (isset($data['campoHoraInicio']))
  		$campoHoraInicio = (int) $data['campoHoraInicio'];
  
  	$campoHoraFinal = '';
  	if (isset($data['campoHoraFinal']))
  		$campoHoraFinal = (int) $data['campoHoraFinal'];
  
    $cadastroRoiHistorico = 0;
    if (isset($data['cadastroRoiHistorico']))
        $cadastroRoiHistorico = (int) $data['cadastroRoiHistorico'];

    $where = array();

    /* Custo */
    if (($campoCustoInicio > 0) && ($campoCustoFinal > 0)) {
        $where[] = "relatorioCustoValor >= '$campoCustoInicio' ";
        $where[] = "relatorioCustoValor <= '$campoCustoFinal' ";
        
    } else if (($campoCustoInicio == '0.00') && ($campoCustoFinal > 0)) {
        $where[] = "relatorioCustoValor >= '$campoCustoInicio' ";
        $where[] = "relatorioCustoValor <= '$campoCustoFinal' ";
        
    } else if ($campoCustoInicio > 0) {
        $where[] = "relatorioCustoValor >= '$campoCustoInicio' ";
    }
    
    /* Roi Geral */
    if (($campoRoiGeralInicio > 0) && ($campoRoiGeralFinal > 0)) {
        $where[] = "relatorioRoiGeralValor >= '$campoRoiGeralInicio' ";
        $where[] = "relatorioRoiGeralValor <= '$campoRoiGeralFinal' ";
        
    } else if (($campoRoiGeralInicio == '0.00') && ($campoRoiGeralFinal > 0)) {
        $where[] = "relatorioRoiGeralValor >= '$campoRoiGeralInicio' ";
        $where[] = "relatorioRoiGeralValor <= '$campoRoiGeralFinal' ";
        
    } else if ($campoRoiGeralInicio > 0) {
        $where[] = "relatorioRoiGeralValor >= '$campoRoiGeralInicio' ";
      
    } else if ($campoRoiGeralFinal > 0) {
        $where[] = "relatorioRoiGeralValor <= '$campoRoiGeralFinal' ";
      
    }
    
    /* Dias */
    if (!empty($campoDias) && !empty($campoDiasFinal)) {
        $where[] = "relatorioDiasAtivo >= $campoDias ";
        $where[] = "relatorioDiasAtivo <= $campoDiasFinal ";
        
    } else if (!empty($campoDias)) {
        $where[] = "relatorioDiasAtivo >= $campoDias ";
    
    } else if (!empty($campoDiasFinal)) {
        $where[] = "relatorioDiasAtivo <= $campoDiasFinal ";
    }
    
    /* Tipo */       
    if ($campoTipo == 'facebook') {
        $where[] = "(relatorioTipo = '$campoTipo' OR relatorioTipo IS NULL)";
    } else if ($campoTipo == 'tiktok') {
        $where[] = "relatorioTipo = '$campoTipo' ";
    }
        
    /* Gestor */
    if ($campoGestorID > 0)
        $where[] = "_clienteID = '$campoGestorID' ";
    
    /* Data de busca */
    $campoDataFormat = date('Y-m-d');

    if ($cadastroRoiHistorico > 0) {
        $campoDataFormat = date('Y-m-d', strtotime('-1 day')); 
      
    } else {
        if ($campoTempo == 1) {
            $campoDataFormat = date('Y-m-d');
        } else if ($campoTempo == 2) {
            $campoDataFormat = date('Y-m-d', strtotime('-1 day'));
        } else {
            $campoDataFormat = date('Y-m-d');
        }
    }
  
    $where[] = "relatorioData = '" . $campoDataFormat . "' ";

    /* Roi Valor Final */
    $aplicarRoi = false;
    if ($campoStatus == 'ACTIVE')
        $aplicarRoi = true;
        
    if (($campoStatus == 'PAUSED') && ($cadastroRoiHistorico == 0) )
        $aplicarRoi = true;
        
    if ($aplicarRoi) {                    
        if (($campoRoi > 0) && ($campoRoiFinal > 0)) {
            $where[] = "relatorioRoiFinalValor >= $campoRoi ";
            $where[] = "relatorioRoiFinalValor <= $campoRoiFinal ";
        
        } else if (($campoRoi == '0.00') && ($campoRoiFinal > 0)) {
            $where[] = "relatorioRoiFinalValor >= $campoRoi ";
            $where[] = "relatorioRoiFinalValor <= $campoRoiFinal ";
            
        } else if ($campoRoi > 0) {
            $where[] = "relatorioRoiFinalValor >= $campoRoi ";   
          
        } else if ($campoRoiFinal > 0) {
            $where[] = "relatorioRoiFinalValor <= $campoRoiFinal ";  
        }
    }

    $sql = "SELECT *
        FROM adx_relatorios 
        WHERE
            relatorioUtmTipo = 'campaign_id' AND 
             " . (count($where) > 0 ? implode(' AND ', $where) : '') . "
        ORDER BY relatorioID DESC;";

    $itens = mysqli_query($con, $sql);

    while ($itemValor = mysqli_fetch_array($itens)) { 

        $relatorioID              = $itemValor['relatorioID']; 
        $relatorioUtmValor        = $itemValor['relatorioUtmValor']; 
        $relatorioCampanhaNome    = $itemValor['relatorioCampanhaNome']; 
        $relatorioGestaoRoiStatus = $itemValor['relatorioGestaoRoiStatus']; 
        $relatorioCampanhaStatus  = $itemValor['relatorioCampanhaStatus']; 
        $relatorioGestaoRoiData   = $itemValor['relatorioGestaoRoiData']; 
        $relatorioRoiFinalValor   = $itemValor['relatorioRoiFinalValor']; 
        $relatorioDiasAtivo       = $itemValor['relatorioDiasAtivo']; 
        $relatorioRoboData        = $itemValor['relatorioRoboData']; 
        $relatorioUtmSource       = $itemValor['relatorioUtmSource']; 
        $relatorioCustoValor      = $itemValor['relatorioCustoValor']; 
        $relatorioRoboStatus      = $itemValor['relatorioRoboStatus']; 
        $relatorioRoiFinalClass   = $itemValor['relatorioRoiFinalClass']; 
        $relatorioRoiGeralValor   = $itemValor['relatorioRoiGeralValor']; 
        $relatorioRoiGeralClass   = $itemValor['relatorioRoiGeralClass']; 
        $relatorioTipo            = $itemValor['relatorioTipo']; 
      	$relatorioData            = $itemValor['relatorioData']; 
      	$_clienteID               = $itemValor['_clienteID'];
		$contaID                  = $itemValor['_contaID'];
      
      	$itemValor['dataBusca'] = $campoDataFormat;
                          
        if (empty($relatorioTipo))
            $relatorioTipo = 'facebook';
                          
        $campanhaReativar = true;
        
        /* Verifica Status */
        $campanhaTiktokStatus   = '';
        $campanhaFacebookStatus = '';

        $itemValor['campanhaTiktokStatus']   = $campanhaTiktokStatus;
        $itemValor['campanhaFacebookStatus'] = $campanhaFacebookStatus;
      
        if ($relatorioTipo == 'tiktok') {
            $custos = mysqli_query($con, "SELECT *
                FROM tiktok_custos 
                WHERE 
                    custoCampanhaID = '$relatorioUtmValor' AND 
                    custoData       = '$campoDataFormat'
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
                    itemData       = '$campoDataFormat'
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
      
        if (!empty($campoStatus)) {
            if ($relatorioTipo == 'tiktok') {                                                
                if ($campoStatus <> $campanhaTiktokStatus)
                    continue;
            } else {
                if ($campoStatus <> $campanhaFacebookStatus)
                    continue;
            }
        }

        $statusAtual = '';
        if ($relatorioTipo == 'tiktok') {                                                
            $statusAtual = $campanhaTiktokStatus;
        } else {
            $statusAtual = $campanhaFacebookStatus;
        }
        
        /* Historio do roi */

        if ($cadastroRoiHistorico > 0) {
            if ($campoStatus == 'ACTIVE' || $campoStatus == 'PAUSED') {
                $campanhaReativar = false;
                
                if ($campoStatus == 'ACTIVE') { 
                    $historico = mysqli_query($con, "SELECT * 
                        FROM adx_relatorios 
                        WHERE 
                            relatorioUtmValor = '$relatorioUtmValor' AND 
                            relatorioUtmTipo  = 'campaign_id' AND 
                            relatorioData    <= '$campoDataFormat'
                        ORDER BY relatorioData DESC
                        LIMIT $cadastroRoiHistorico");
                            
                    if ($historico) {
                        $historicoTotal = mysqli_num_rows($historico);
                        if (($historicoTotal > 0) && ($historicoTotal <= $cadastroRoiHistorico)) {
                            $_campanhaReativar = false;
                            
                            while ($historicoValor = mysqli_fetch_array($historico)) { 
                                $historicoRoiFinalValor = $historicoValor['relatorioRoiFinalValor']; 
                                
                                if ($campoRoi > 0) {
                                    if ($historicoRoiFinalValor > $campoRoi)
                                        $_campanhaReativar = true; 
                                }
                            }
                            
                            if (!$_campanhaReativar)
                                $campanhaReativar = false;
                        }
                    }
                }
                
                if ($campoStatus == 'PAUSED') { 
                  
                    $historico = mysqli_query($con, "SELECT * 
                        FROM adx_relatorios 
                        WHERE 
                            relatorioUtmValor = '$relatorioUtmValor' AND 
                            relatorioUtmTipo  = 'campaign_id' AND 
                            relatorioData    <= '$campoDataFormat'
                        ORDER BY relatorioData DESC
                        LIMIT $cadastroRoiHistorico");
                        
                    if ($historico) {
                        $historicoTotal = mysqli_num_rows($historico);
                      
                        if (($historicoTotal > 0) && ($historicoTotal <= $cadastroRoiHistorico)) {
                            
                            if ($campoRoi > 0) {
                                while ($historicoValor = mysqli_fetch_array($historico)) { 
                                    $historicoRoiFinalValor = $historicoValor['relatorioRoiFinalValor']; 
                                    
                                    if ($historicoRoiFinalValor > $campoRoi)
                                        $campanhaReativar = true; 
                                }
                            }
                        }
                    }
                }

                if (!$campanhaReativar)
                    continue;
              
              	$itemValor['campanhaReativar'] = $campanhaReativar;
            }
        }

        /* Verifica horario */

        $horaAtual = date('H');
        $dataAtual = date('d-m-Y H:i:s');

        if (isset($arrGestaoRoiHora[$campoHoraInicio]) || isset($arrGestaoRoiHora[$campoHoraFinal])) {

            $_campoHoraInicio = '';
            if (isset($arrGestaoRoiHora[$campoHoraInicio]))
                $_campoHoraInicio = $arrGestaoRoiHora[$campoHoraInicio];

            $_campoHoraFinal = '';
            if (isset($arrGestaoRoiHora[$campoHoraFinal]))
                $_campoHoraFinal = $arrGestaoRoiHora[$campoHoraFinal];

            if ($relatorioTipo == 'tiktok') {
                $custoAdvertiserID = 0;
                
                $custoLista = mysqli_query($con, "SELECT 
                        custoAdvertiserID
                    FROM tiktok_custos 
                    WHERE  
                        custoCampanhaID = '$relatorioUtmValor' AND 
                        custoData       = '$campoDataFormat' ");
                        
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
        
            if ($relatorioTipo == 'facebook') {
                
                $contaItens = mysqli_query($con, "SELECT * 
                    FROM `facebook_conta_itens` 
                    WHERE 
                        itemValor = '$contaID' AND 
                        itemData  = '$campoDataFormat'
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
            
            if (isset($arrGestaoRoiHora[$campoHoraInicio]) && isset($arrGestaoRoiHora[$campoHoraFinal])) {
                if ($horaAtual < $_campoHoraInicio || $horaAtual > $_campoHoraFinal)
                    continue;
                    
            } else if (isset($arrGestaoRoiHora[$campoHoraInicio])) {
                if ($horaAtual < $_campoHoraInicio)
                    continue;
            } else if (isset($arrGestaoRoiHora[$campoHoraFinal])) { 
                if ($horaAtual > $_campoHoraFinal)
                    continue;
            }
        }

        $retorno[] = $itemValor;
    }
  	
  	return $retorno;
}   

function arrGestaoRoiHora() {
  	$arr = array(
      	1  => '0',
      	2  => '1',
      	3  => '2',
      	4  => '3',
      	5  => '4',
      	6  => '5',
      	7  => '6',
      	8  => '7',
      	9  => '8',
      	10 => '9',
      	11 => '10',
      	12 => '11',
      	13 => '12',
      	14 => '13',
      	15 => '14',
      	16 => '15',
      	17 => '16',
      	18 => '17',
      	19 => '18',
      	20 => '19',
      	21 => '20',
      	22 => '21',
      	23 => '22',
      	24 => '23'
    );
  
  	return $arr;
}

function divide($dividend, $divisor) {
    try {
        // Perform the operation.
        $result = $dividend / $divisor;
    } catch (DivisionByZeroError $error) {
        // Output expected ArithmeticError.
    } catch (Error $error) {
        // Output any unexpected errors.
    }
}

function getClienteImagem($_clienteID = 0, $session = false) {
    global $con;
    
    $pastaUpload = ABSPATH . 'uploads/clientes/'; 
    
    $clienteID     = getClienteID();
    $retorno       = false;
    
    if ($session) {
        $clienteImagem = $_SESSION['cliente_imagem'];
    }
    
    if (empty($clienteImagem)) {
        if ($_clienteID > 0)
            $clienteID = $_clienteID;
        
        $clientes = mysqli_query($con, "SELECT *
            FROM clientes
            WHERE 
                clienteID = $clienteID
            LIMIT 1");
            
        if ($clientes) {
            $clienteValor = mysqli_fetch_array($clientes);
            if (isset($clienteValor['clienteID'])) {
                if (is_file($pastaUpload . $clienteValor['clienteFoto'])) {
                    $clienteImagem = base_url('uploads/clientes/' . $clienteValor['clienteFoto']);
                }
            }
        }
    }
    
    if (empty($clienteImagem))
        $clienteImagem = base_url('assets/img/default-user.png');
    
    if ($session) {
        $_SESSION['cliente_imagem'] = $clienteImagem;
    }
    
    return $clienteImagem;
}

function getClienteCargo($_clienteID = 0, $session = false) {
    global $con;
    
    $cargoNome = ''; 
    
    if ($session)
        $cargoNome = $_SESSION['cliente_cargo'];        

    if (empty($cargoNome)) {
        $clienteID = getClienteID();
        $retorno   = false;
        
        if ($_clienteID > 0)
            $clienteID = $_clienteID;
        
        $clientes = mysqli_query($con, "SELECT *
            FROM clientes
                INNER JOIN cargos ON cargoID = _cargoID
            WHERE 
                clienteID = $clienteID
            LIMIT 1");
            
        if ($clientes) {
            $clienteValor = mysqli_fetch_array($clientes);
            if (isset($clienteValor['clienteID'])) {
                $cargoNome = $clienteValor['cargoNome'];
                
                if ($session) { 
                    $_SESSION['cliente_cargo'] = $cargoNome;
                }
            }
        }
    }
    
    return $cargoNome;
}

function getCampanhaComissao($tipo = 'facebook', $dataInicio = '', $dataFinal = '', $clienteID = 0) {
    global $con;
    
    $impostoPorcentagem = getConfig('imposto_porcentagem');
    if (empty($impostoPorcentagem))
        $impostoPorcentagem = 10;
    
    $comissaoValor   = 0;
    $clienteComissao = 0;
    
    $clientes = mysqli_query($con, "SELECT *
        FROM clientes
        WHERE 
            clienteID = $clienteID
        LIMIT 1;");
        
    if ($clientes) {
        $clienteValor = mysqli_fetch_array($clientes);
        if (isset($clienteValor['clienteID'])) {
            $clienteComissao = (int) $clienteValor['clienteComissao'];
        }
    }
    
    $clienteComissao = (int) $clienteComissao;
    if ($clienteComissao == 0)
        $clienteComissao = 10;
    
    $where = '';
    
    if ($dataInicio == 'mes_atual') {
        $where = ' MONTH(relatorioData) = MONTH(CURRENT_DATE()) AND 
            YEAR(relatorioData)  = YEAR(CURRENT_DATE()) AND ';
        
    } else if ($dataInicio == 'mes_anterior') {                            
        $where = ' YEAR(relatorioData) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND 
        MONTH(relatorioData) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) AND ';
        
    } else {
        if (empty($dataFinal)) {
            $where = " DATE(relatorioData) = '$dataInicio' AND ";
        } else {
            $where = " DATE(relatorioData) >= '$dataInicio' AND DATE(relatorioData) <= '$dataFinal' AND ";
        }
    }
    
    $sql = "SELECT 
        IFNULL((SELECT 
            SUM(relatorioReceitaTotal)
        FROM adx_relatorios
        WHERE
            relatorioUtmTipo = 'campaign_id' AND 
            relatorioUtmSource = '$tipo' AND 
            $where
            _clienteID = $clienteID), 0) AS receita,
            
        IFNULL((SELECT 
            SUM(relatorioCustoValor)
        FROM adx_relatorios
        WHERE
            relatorioUtmTipo = 'campaign_id' AND 
            relatorioUtmSource = '$tipo' AND 
            $where
            _clienteID = $clienteID), 0) AS custos";
            
    $itens = mysqli_query($con, $sql);
    if ($itens) {
        $itemValor = mysqli_fetch_array($itens);
        if (isset($itemValor['receita'])) {
            $custos  = $itemValor['custos'];
            $receita = $itemValor['receita'];
            
            if ($custos > 0) {
                $totalAdRevenue  = $receita; 
                $impostoValor    = ($totalAdRevenue / 100) * $impostoPorcentagem;
                $totalAdRevenue  = ($totalAdRevenue - $impostoValor) - $custos;
                $comissaoValor   = $totalAdRevenue - ($totalAdRevenue - (($totalAdRevenue / 100) * $clienteComissao));
            }
        }
    }
    
    return $comissaoValor;
}

function titktokIdade() {
    $arr = array(
        '18-24'  => '18 - 24',
        '25-34'  => '25 - 34',
        '35-44'  => '35 - 44',
        '45-54'  => '45 - 54',
        '55-100' => '55+'
    );
    
    return $arr;
}

function titktokCache($campanhaID = '', $data = '') {
    global $con;
    
    $impostoPorcentagem = getConfig('imposto_porcentagem');
    if (empty($impostoPorcentagem))
        $impostoPorcentagem = 10;
    
    $itens = mysqli_query($con, "SELECT *
        FROM adx_relatorios A
            INNER JOIN analytics ON analyticID = A._analyticID
            INNER JOIN gestao_utms ON gestaoUtm_campaign_id = relatorioUtmValor AND gestaoUtm_utm_source = 'tiktok'
        WHERE 
            relatorioUtmTipo = 'campaign_id' AND
            relatorioUtmValor = '$campanhaID' AND
            relatorioData     = '$data'
        LIMIT 1;"); 
        
    if ($itens) {
        $itemValor = mysqli_fetch_array($itens);
        if (isset($itemValor['relatorioID'])) {
            
            $relatorioID           = $itemValor['relatorioID'];
            $relatorioReceitaTotal = $itemValor['relatorioReceitaTotal'];
            $clienteID             = $itemValor['_clienteID'];
            
            $custoValor = 0;
            $views      = 0;
            $cliques    = 0;
            $impressoes = 0;
            $pageViews  = 0;
            $ctr        = 0;
            $cpc        = 0;
            $cpa        = 0;
            $conversao  = 0;
            $status     = '';
            
            $custoLista = mysqli_query($con, "SELECT 
                    SUM(custoValor) AS custo,
                    SUM(custoCliques) AS cliques,
                    SUM(custoImpressoes) AS impressoes,
                    SUM(custoTotalLandingPageView) AS pageViews,
                    custoStatusCampanha
                FROM tiktok_custos 
                WHERE  
                    custoCampanhaID = '$campanhaID' AND 
                    custoData       = '$data' ");
                    
            if ($custoLista) {
                $custoItemValor = mysqli_fetch_array($custoLista);
                if (isset($custoItemValor['custo'])) {
                    $custoValor = $custoItemValor['custo'];
                    $views      = $custoItemValor['pageViews'];
                    $cliques    = $custoItemValor['cliques'];
                    $impressoes = $custoItemValor['impressoes'];
                    $status     = $custoItemValor['custoStatusCampanha'];
                }
            }
            
            $campanhaStatus = 'PAUSED';
            if ($status == 'ativo')
                $campanhaStatus = 'ACTIVE';
                
            $relatorioTiktokComissao = 0;
            $clienteComissao         = 0;
            
            $clientes = mysqli_query($con, "SELECT *
                FROM clientes
                WHERE 
                    clienteID = $clienteID
                LIMIT 1;");
                
            if ($clientes) {
                $clienteValor = mysqli_fetch_array($clientes);
                if (isset($clienteValor['clienteID'])) {
                    $clienteComissao = (int) $clienteValor['clienteComissao'];
                }
            }
            
            if ($clienteComissao == 0)
                $clienteComissao = 10;
            
            if ($relatorioReceitaTotal > 0) {
                $_totalAdRevenue         = $relatorioReceitaTotal; 
                $impostoValor            = ($_totalAdRevenue / 100) * $impostoPorcentagem;
                $_totalAdRevenue         = ($_totalAdRevenue - $impostoValor) - $custoValor;
                $relatorioTiktokComissao = $_totalAdRevenue - ($_totalAdRevenue - (($_totalAdRevenue / 100) * $clienteComissao));
            }
                                    
            $dados = array(
                'relatorioCampanhaStatus'   => $campanhaStatus,
                'relatorioTipo'             => 'tiktok',
                'relatorioTiktokComissao'   => $relatorioTiktokComissao,
                'relatorioComissaoValor'    => $relatorioTiktokComissao,
                'relatorioTiktokCusto'      => $custoValor,
                'relatorioTiktokCliques'    => $cliques,
                'relatorioTiktokImpressoes' => $impressoes,
                'relatorioTiktokPageViews'  => $pageViews,
                'relatorioTiktokCTR'        => $ctr,
                'relatorioTiktokCPA'        => $cpa,
                'relatorioTiktokCPC'        => $cpc,
                'relatorioTiktokConversao'  => $conversao,
                'relatorioTiktokStatus'     => $status
            );                        
            
            update('adx_relatorios', $dados, 'relatorioID = ' . $relatorioID);
        }
    }
}

function permissaoTiktokContasTotal($contaID = '') {
    global $con;
    
    $clienteID = getClienteID();
    $retorno   = false;
    
    $clientes = mysqli_query($con, "SELECT *
        FROM clientes
        WHERE 
            clienteID = $clienteID
        LIMIT 1");
        
    if ($clientes) {
        $clienteValor = mysqli_fetch_array($clientes);
        if (isset($clienteValor['clienteID'])) {
            $clienteContaTiktok = $clienteValor['clienteContaTiktok'];
            $clienteContaTiktok = (array) json_decode($clienteContaTiktok, true);
            $clienteContaTiktok = array_filter($clienteContaTiktok);
            
            if (isset($clienteContaTiktok[$contaID])) {
                if (count($clienteContaTiktok[$contaID]) > 0)
                    $retorno = true;
            }
        }
    }
    
    return $retorno;
}

function permissaoContasTiktok($contaID = '', $contaCodigo = '') {
    global $con;
    
    $clienteID = getClienteID();
    $retorno   = false;
    
    $clientes = mysqli_query($con, "SELECT *
        FROM clientes
        WHERE 
            clienteID = $clienteID
        LIMIT 1");
        
    if ($clientes) {
        $clienteValor = mysqli_fetch_array($clientes);
        if (isset($clienteValor['clienteID'])) {
            $clienteContaTiktok = $clienteValor['clienteContaTiktok'];
            $clienteContaTiktok = (array) json_decode($clienteContaTiktok, true);
            $clienteContaTiktok = array_filter($clienteContaTiktok);
            
            if (isset($clienteContaTiktok[$contaID])) {
                if (in_array($contaCodigo, $clienteContaTiktok[$contaID]))
                    $retorno = true;
            }
        }
    }
    
    return $retorno;
}

function criativoArquivoPermissao($galeriaID = '', $logadoID = 0) {
    global $con;
    
    $arquivo = mysqli_query($con, "SELECT *
        FROM galeria 
            INNER JOIN clientes ON clienteID = _clienteID
        WHERE 
            galeriaID = $galeriaID
        LIMIT 1");
        
    if ($arquivo) {
        $itemValor = mysqli_fetch_array($arquivo);
        if (isset($itemValor['galeriaID'])) {
            $clienteID         = $itemValor['clienteID'];
            $permissaoCriativo = $itemValor['clientePermissoesVisualizarCriativo'];
            
            $permissaoCriativo = (array) json_decode($permissaoCriativo, true);
            $permissaoCriativo = array_filter($permissaoCriativo);
            
            if ($logadoID == $clienteID) {
                return true;
            } else {
                if (in_array($logadoID, $permissaoCriativo))
                    return true;
            }
        }
    }
    
    return false;
}

function removeAccents($str = '') {
    static $map = [
        // single letters
        'à' => 'a',
        'á' => 'a',
        'â' => 'a',
        'ã' => 'a',
        'ä' => 'a',
        'ą' => 'a',
        'å' => 'a',
        'ā' => 'a',
        'ă' => 'a',
        'ǎ' => 'a',
        'ǻ' => 'a',
        'À' => 'A',
        'Á' => 'A',
        'Â' => 'A',
        '' => 'A',
        'Ä' => 'A',
        'Ą' => 'A',
        'Å' => 'A',
        '' => 'A',
        '' => 'A',
        'Ǎ' => 'A',
        'Ǻ' => 'A',


        'ç' => 'c',
        'ć' => 'c',
        'ĉ' => 'c',
        '' => 'c',
        'č' => 'c',
        'Ç' => 'C',
        'Ć' => 'C',
        'Ĉ' => 'C',
        'Ċ' => 'C',
        'Č' => 'C',

        'ď' => 'd',
        'đ' => 'd',
        'Ð' => 'D',
        'Ď' => 'D',
        'Đ' => 'D',


        'è' => 'e',
        'é' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ę' => 'e',
        'ē' => 'e',
        'ĕ' => 'e',
        'ė' => 'e',
        'ě' => 'e',
        'È' => 'E',
        '' => 'E',
        'Ê' => 'E',
        '' => 'E',
        'Ę' => 'E',
        'Ē' => 'E',
        'Ĕ' => 'E',
        'Ė' => 'E',
        '' => 'E',

        'ƒ' => 'f',


        'ĝ' => 'g',
        '' => 'g',
        'ġ' => 'g',
        'ģ' => 'g',
        'Ĝ' => 'G',
        'Ğ' => 'G',
        'Ġ' => 'G',
        '' => 'G',


        'ĥ' => 'h',
        'ħ' => 'h',
        'Ĥ' => 'H',
        'Ħ' => 'H',

        'ì' => 'i',
        'í' => 'i',
        'î' => 'i',
        'ï' => 'i',
        '' => 'i',
        '' => 'i',
        'ĭ' => 'i',
        '' => 'i',
        'ſ' => 'i',
        'ǐ' => 'i',
        'Ì' => 'I',
        'Í' => 'I',
        '' => 'I',
        '' => 'I',
        'Ĩ' => 'I',
        'Ī' => 'I',
        '' => 'I',
        'Į' => 'I',
        'İ' => 'I',
        '' => 'I',

        'ĵ' => 'j',
        'Ĵ' => 'J',

        '' => 'k',
        'Ķ' => 'K',


        'ł' => 'l',
        '' => 'l',
        'ļ' => 'l',
        'ľ' => 'l',
        '' => 'l',
        'Ł' => 'L',
        '' => 'L',
        'Ļ' => 'L',
        '' => 'L',
        'Ŀ' => 'L',


        '' => 'n',
        'ń' => 'n',
        'ņ' => 'n',
        'ň' => 'n',
        '' => 'n',
        'Ñ' => 'N',
        'Ń' => 'N',
        '' => 'N',
        'Ň' => 'N',

        'ò' => 'o',
        '' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ö' => 'o',
        'ð' => 'o',
        'ø' => 'o',
        'ō' => 'o',
        'ŏ' => 'o',
        'ő' => 'o',
        'ơ' => 'o',
        'ǒ' => 'o',
        'ǿ' => 'o',
        '' => 'O',
        'Ó' => 'O',
        '' => 'O',
        'Õ' => 'O',
        'Ö' => 'O',
        '' => 'O',
        'Ō' => 'O',
        'Ŏ' => 'O',
        'Ő' => 'O',
        '' => 'O',
        'Ǒ' => 'O',
        '' => 'O',


        'ŕ' => 'r',
        'ŗ' => 'r',
        'ř' => 'r',
        '' => 'R',
        '' => 'R',
        'Ř' => 'R',


        'ś' => 's',
        'š' => 's',
        '' => 's',
        '' => 's',
        'Ś' => 'S',
        'Š' => 'S',
        'Ŝ' => 'S',
        '' => 'S',

        'ţ' => 't',
        'ť' => 't',
        '' => 't',
        'Ţ' => 'T',
        'Ť' => 'T',
        'Ŧ' => 'T',


        'ù' => 'u',
        'ú' => 'u',
        '' => 'u',
        'ü' => 'u',
        'ũ' => 'u',
        '' => 'u',
        'ŭ' => 'u',
        'ů' => 'u',
        'ű' => 'u',
        'ų' => 'u',
        'ư' => 'u',
        'ǔ' => 'u',
        '' => 'u',
        'ǘ' => 'u',
        'ǚ' => 'u',
        'ǜ' => 'u',
        'Ù' => 'U',
        'Ú' => 'U',
        'Û' => 'U',
        '' => 'U',
        'Ũ' => 'U',
        'Ū' => 'U',
        'Ŭ' => 'U',
        'Ů' => 'U',
        'Ű' => 'U',
        '' => 'U',
        'Ư' => 'U',
        'Ǔ' => 'U',
        '' => 'U',
        'Ǘ' => 'U',
        'Ǚ' => 'U',
        'Ǜ' => 'U',


        'ŵ' => 'w',
        'Ŵ' => 'W',

        'ý' => 'y',
        'ÿ' => 'y',
        'ŷ' => 'y',
        '' => 'Y',
        '' => 'Y',
        '' => 'Y',

        '' => 'z',
        'ź' => 'z',
        'ž' => 'z',
        'Ż' => 'Z',
        'Ź' => 'Z',
        '' => 'Z',


        // accentuated ligatures
        'Ǽ' => 'A',
        'ǽ' => 'a',
    ];
    
    return strtr($str, $map);
}

function getGestoradxReceita($campanhaNome = '', $data = '', $pais = '') {
    global $con;
    
    $retorno = '';
    
    if (empty($pais)) {
        $itens = mysqli_query($con, "SELECT gestaoUtm_campaign_name, adxReceitaTotalCpmCpc, adxPaisNome
            FROM `adx_campanhas` 
                INNER JOIN `gestao_utms` ON gestaoUtm_campaign_id = adxChaveValor
            WHERE 
                adxData                 = '$data' AND 
                adxTipo                 = 'campaign_id' AND 
                gestaoUtm_campaign_name = '$campanhaNome'
            GROUP BY adxPaisNome;");
    } else {
    
        $sql = "SELECT gestaoUtm_campaign_name, adxReceitaTotalCpmCpc, adxPaisNome
            FROM `adx_campanhas` 
                INNER JOIN `gestao_utms` ON gestaoUtm_campaign_id = adxChaveValor
            WHERE 
                adxData                 = '$data' AND 
                adxTipo                 = 'campaign_id' AND 
                gestaoUtm_campaign_name = '$campanhaNome' AND 
                adxPaisNome             = '$pais' 
            LIMIT 1;";
    
        $itens = mysqli_query($con, $sql);        
    }
        
    if ($itens) {
        while ($itemValor = mysqli_fetch_array($itens)) {
            $retorno = $retorno + $itemValor['adxReceitaTotalCpmCpc'];
        }        
    }
    
    return $retorno;
}

function gestaoUtmsCopiado($valor = '', $tipo = '') {
    global $con;
    
    $cadastrado = mysqli_query($con, "SELECT *
            FROM gestao_utms_copiados 
            WHERE 
                copiadoValor = '$valor' AND 
                copiadoTipo  = '$tipo' ");    
                
        if ($cadastrado) {
            if (mysqli_num_rows($cadastrado))
                return true;
        }
        
        return false;
}

function gestorAdxCopiado($valor = '', $tipo = '') {
    global $con;
    
    $cadastrado = mysqli_query($con, "SELECT *
            FROM facebook_criativos_copiados 
            WHERE 
                copiadoValor = '$valor' AND 
                copiadoTipo  = '$tipo' ");    
                
        if ($cadastrado) {
            if (mysqli_num_rows($cadastrado))
                return true;
        }
        
        return false;
}

function gestorCampanhaRoiGeral($campanhaNome = '', $dias = 14) {
    global $con;
    
    $roiRetorno = array();
    
    $dias = (int) $dias;
    if ($dias == 0)
        $dias = 6;
        
    $dataFinal  = date('Y-m-d', strtotime('-1 days'));
    $dataInicio = date('Y-m-d', strtotime('-' . $dias . ' days'));
   
    $sql = "SELECT 
            gestorPais_country,
            SUM(gestorPais_totalAdRevenue) AS gestorPais_totalAdRevenue, 
            SUM(gestorPaisCustoValor) AS gestorPaisCustoValor 
        FROM analytics_gestor_pais
        WHERE
            gestorPais_sessionCampaignName = '$campanhaNome' AND
            (gestorPais_date >= '$dataInicio' AND gestorPais_date <= '$dataFinal')
        GROUP BY gestorPais_country
        ORDER BY gestorPais_country ASC;";
        
    $pais = mysqli_query($con, $sql);
    
    if ($pais) {
        if (mysqli_num_rows($pais) > 0) { 
            while ($paisValor = mysqli_fetch_array($pais)) { 
                $paisNome = $paisValor['gestorPais_country'];
                
                $itemTotalAdRevenueTotal = $paisValor['gestorPais_totalAdRevenue'];
                $itemTotalCustoTotal     = $paisValor['gestorPaisCustoValor'];
             
                $roiValor = 0;
                if ($itemTotalCustoTotal > 0)
                    $roiValor = $itemTotalAdRevenueTotal / $itemTotalCustoTotal;
                
                $_roiRetorno = array();
                
                if ($roiValor >= 2.00) { 
                    $_roiRetorno = array(
                        'label' => 'label-success',
                        'valor' => $roiValor
                    );
                    
                } else if ($roiValor < 0.90) {
                    $_roiRetorno = array(
                        'label' => 'label-danger',
                        'valor' => $roiValor
                    );
                    
                } else if ($roiValor < 1.11) { 
                    $_roiRetorno = array(
                        'label' => 'label-warning',
                        'valor' => $roiValor
                    );
                    
                } else if ($roiValor < 2.00) { 
                    $_roiRetorno = array(
                        'label' => 'label-info',
                        'valor' => $roiValor
                    );
                }
                
                if (!empty($_roiRetorno))
                    $roiRetorno[$paisNome] = $_roiRetorno;
            }
        }
    }
    
    return $roiRetorno;
}

function gestorContaStatus($str = '', $retorno = false) {
    
    $arr = array(
        1   => 'Ativo', 
        2   => 'Desabilitado',
        3   => 'Incerto',
        7   => 'Pendente de revisão de Risco',
        8   => 'Liquidação pendente',
        9   => 'Em período de graça',
        100 => 'Encerramento pendente',
        101 => 'Fechado',
        201 => 'Sem atividade',
        202 => 'Fechado'
    );
    
    if ($retorno) {
        if (array_key_exists($str, $arr))
            return $arr[$str];
        return;
    }
    
    return $arr;
}

function gestorComissaoValor($_clienteID = 0, $dataSelecionada = '', $socialNome = '') {
    global $con;
    
    $clienteID = getClienteID();
    $valor     = 0;
    
    $clienteComissaoValor = (int) getClienteComissao();
    if ($clienteComissaoValor == 0)
        $clienteComissaoValor = 10;
    
    if (empty($socialNome))
        $socialNome = 'facebook';
        
    $impostoPorcentagem = getConfig('imposto_porcentagem');
    if (empty($impostoPorcentagem))
        $impostoPorcentagem = 10;
    
    if ($_clienteID > 0)
        $clienteID = $_clienteID;
        
    $query = mysqli_query($con, "SELECT *
        FROM clientes
        WHERE 
            clienteID = $clienteID
        LIMIT 1;");

    if ($query) {
        $itemValor = mysqli_fetch_array($query);
        if (isset($itemValor['clienteID'])) {
            $clienteUtmTerm = $itemValor['clienteUtmTerm'];
            
            $arrUtm = explode(',', $clienteUtmTerm);
            $arrUtm = array_filter($arrUtm);
            
            $_arrUtm = array();
            foreach ($arrUtm as $utmValor) {
                $_arrUtm[] = trim($utmValor);
            }
            
            $campanhasLista = mysqli_query($con, "SELECT 
                    campanhaManualTerm AS sessionCampaignName, campanha_sessionCampaignName
                FROM `analytics_campanhas` 
                WHERE 
                   campanha_sessionSourceMedium LIKE '%$socialNome%' AND
                   campanha_firstUserManualTerm IN ('" . implode("','", $_arrUtm) . "') AND 
                   campanha_date                  = '$dataSelecionada'
                GROUP BY campanhaManualTerm 
                ORDER BY campanhaManualTerm ASC;");

            if ($campanhasLista) {
                while ($campanhasListaValor = mysqli_fetch_array($campanhasLista)) { 
                    $sessionCampaignName = $campanhasListaValor['sessionCampaignName'];
                    
                    $campanhas = mysqli_query($con, "SELECT
                        campanhas_totalCustoValor AS gestorPaisCustoValor,
                        campanhas_totalAdRevenue AS gestorPais_totalAdRevenue
                    FROM analytics_campanhas
                    WHERE
                        campanha_sessionCampaignName LIKE '$sessionCampaignName%' AND
                        campanha_date = '$dataSelecionada'
                    LIMIT 100;");
                    
                    if ($campanhas) {
                        while ($campanhaValor = mysqli_fetch_array($campanhas)) {
                            $totalAdRevenue     = (float) $campanhaValor['gestorPais_totalAdRevenue'];
                            $campanhaCustoValor = (float) $campanhaValor['gestorPaisCustoValor'];
                            
                            if ($campanhaCustoValor > 0) {
                                $campanhaImpostoValor = ($totalAdRevenue / 100) * $impostoPorcentagem;
                                $totalAdRevenue       = ($totalAdRevenue - $campanhaImpostoValor) - $campanhaCustoValor;
                                
                                $gestorPaisComissaoValor = $totalAdRevenue - ($totalAdRevenue - (($totalAdRevenue / 100) * $clienteComissaoValor));
                                
                                $valor = $valor + $gestorPaisComissaoValor;
                            }
                        }
                    }
                }
            }
        }
    }
    
    return $valor;
}

function roiLanceValor($__valor = '', $custo = 0) {
    $lanceArquivo = ABSPATH . 'data/lance.txt';
    
    $html  = @file_get_contents($lanceArquivo);
    $itens = (array) json_decode($html, true);
    $itens = array_filter($itens); 
    
    $_itens = array();
    foreach ($itens as $itemValor) {
        $posicao = (int) $itemValor['posicao'];
        
        $_itens[$posicao] = array(
            'valor'      => $itemValor['valor'],
            'tipo'       => $itemValor['tipo'],
            'valor_1'    => $itemValor['valor_1'],
            'condicao_1' => $itemValor['condicao_1'],
            'operador'   => $itemValor['operador'],
            'valor_2'    => $itemValor['valor_2'],
            'condicao_2' => $itemValor['condicao_2']
        );
    }
    
    krsort($_itens);
    
    $_retorno = '';
    
    foreach ($_itens as $itemIndex => $itemValor) {
        $valor    = $itemValor['valor'];
        $operador = $itemValor['operador'];
        $tipo     = $itemValor['tipo'];
        
        $_itemvalor    = $itemValor['valor_1'];
        $_itemCondicao = $itemValor['condicao_1'];
        
        $arr = array();
        if (!empty($_itemvalor)) {
            
            $_condicao = '';
            if ($_itemCondicao == 'menor')
                $_condicao = '<';
                
            if ($_itemCondicao == 'maior')
                $_condicao = '>';
                
            if ($_itemCondicao == 'maior_igual')
                $_condicao = '>=';
                
            if ($_itemCondicao == 'menor_igual')
                $_condicao = '<=';
                
            if ($_itemCondicao == 'igual')
                $_condicao = '==';
                
            if ($_itemCondicao == 'diferente')
                $_condicao = '!=';
                
            if ($_itemCondicao == 'identico')
                $_condicao = '===';
                
            if ($_itemCondicao == 'nao_identico')
                $_condicao = '!===';
            
            $arr[] = '(' . $__valor . ' ' . $_condicao . ' ' . $_itemvalor  . ')';
            
            $_itemvalor    = $itemValor['valor_2'];
            $_itemCondicao = $itemValor['condicao_2'];
            
            $_condicao = '';
            if ($_itemCondicao == 'menor')
                $_condicao = '<';
                
            if ($_itemCondicao == 'maior')
                $_condicao = '>';
                
            if ($_itemCondicao == 'maior_igual')
                $_condicao = '>=';
                
            if ($_itemCondicao == 'menor_igual')
                $_condicao = '<=';
                
            if ($_itemCondicao == 'igual')
                $_condicao = '==';
                
            if ($_itemCondicao == 'diferente')
                $_condicao = '!=';
                
            if ($_itemCondicao == 'identico')
                $_condicao = '===';
                
            if ($_itemCondicao == 'nao_identico')
                $_condicao = '!===';
            
            if (!empty($_itemvalor))
                $arr[] = '(' . $__valor . ' ' . $_condicao . ' ' . $_itemvalor  . ')';
                
            $_operador = '';
            if ($operador == 'e')
                $_operador = '&&';
                
            if ($operador == 'or')
                $_operador = '||';
                
            if (count($arr) > 1) {
                $str = $arr[0] . ' ' . $_operador . ' ' . $arr[1];
            } else {
                $str = $arr[0];  
            }
            
            $retorno = (bool) eval('return ' . $str . ' ? true : false;');
            if ($retorno) {
                
                if ($tipo == 'porcentagem_add') {
                    $_retorno = $custo + (($custo / 100) * $valor);
                } else if ($tipo == 'porcentagem_remover') { 
                    $_retorno = $custo - (($custo / 100) * $valor);
                } else if ($tipo == 'adicionar') { 
                    $_retorno = $custo + $valor;
                } else if ($tipo == 'remover') { 
                    $_retorno = $custo - $valor;
                }
                
                break;
            }
        }
    }
    
    if (empty($_retorno))
        $_retorno = $custo;

    return $_retorno;
}

if (!function_exists('ocultarValor')) {
    function ocultarValor($str = '') {
        return '<span class="ocultaValor"><span class="ocultaValorItem"><span></span>' . $str . '</span> <span class="ocultaValorBotao"><i class="fa fa-eye"></i></span> </span>';
    }
}

if (!function_exists('getGestorRoi')) {
    function getGestorRoi($clienteID = '', $tipo = '', $dataInicio = '', $dataFinal = '') {
        global $con;
        
        $retorno = array();
        
        $query = mysqli_query($con, "SELECT *
            FROM clientes 
            WHERE 
                clienteID = $clienteID 
            LIMIT 1");
                
        if ($query) {
            $itemValor = mysqli_fetch_array($query);
            if (isset($itemValor['clienteID'])) {
                $clienteUtmTerm = $itemValor['clienteUtmTerm'];
                $clienteNome    = $itemValor['clienteNome'];
                
                $arrUtm = explode(',', $clienteUtmTerm);
                $arrUtm = array_filter($arrUtm);
                
                $_arrUtm = array();
                foreach ($arrUtm as $utmValor) {
                    $_arrUtm[] = trim($utmValor);
                }
                                
                $campanhasLista = mysqli_query($con, "SELECT 
                        SUM(relatorioCustoValor) AS gestorPaisCustoValor,
                        SUM(relatorioReceitaTotal) AS gestorPais_totalAdRevenue
                    FROM `adx_relatorios` 
                    WHERE 
                        relatorioUtmTipo = 'campaign_id' AND 
                       relatorioUtmSource = '$tipo' AND
                       relatorioData BETWEEN '$dataInicio' AND '$dataFinal' AND 
                       _clienteID = $clienteID;");
                    
                if ($campanhasLista) {
                    $campanhasListaValor = mysqli_fetch_array($campanhasLista);
                    if (isset($campanhasListaValor['gestorPaisCustoValor'])) {
                        $gestorPaisCustoValor = $campanhasListaValor['gestorPaisCustoValor'];
                        $totalAdRevenue       = $campanhasListaValor['gestorPais_totalAdRevenue'];
                        
                        $roiNome  = '';
                        $roiValor = 0;
                        if ($gestorPaisCustoValor > 0)
                            $roiValor = $totalAdRevenue / $gestorPaisCustoValor;
                        
                        if ($roiValor >= 2.00) { 
                            $roiNome = 'label-success';
                        } else if ($roiValor < 0.90) {
                            $roiNome = 'label-danger';
                        } else if ($roiValor < 1.11) { 
                            $roiNome = 'label-warning';
                        } else if ($roiValor < 2.00) {
                            $roiNome = 'label-info';
                        }
                        
                        $retorno = array(
                            'nome'  => $roiNome,
                            'valor' => fmoney($roiValor)
                        );
                    }
                }
            }
        }
        
        return $retorno;
    }
}

if (!function_exists('verificaGestorPermissao')) {
    function verificaGestorPermissao($valor = '', $tipo = '') {
        global $con;
        
        $clienteID = getClienteID();
        
        $query = mysqli_query($con, "SELECT *
            FROM clientes 
            WHERE 
                clienteID = $clienteID 
            LIMIT 1");
                
        $arrPermissoes = array();
        
        if ($query) {
            $itemValor = mysqli_fetch_array($query);
            if (isset($itemValor['clienteGestorPermissoes'])) {
                $arrPermissoes = (array) json_decode($itemValor['clienteGestorPermissoes'], true);        
                $arrPermissoes = array_filter($arrPermissoes);
                
                if ($valor == 'ganhos_usuario') {
                    if (isset($arrPermissoes['ganhos_usuario'])) {
                        if (in_array($tipo, $arrPermissoes['ganhos_usuario']))
                            return true;
                    }
                } else {
                    if (isset($arrPermissoes[$valor])) {
                        if ($arrPermissoes[$valor] == 1)
                            return true;
                    }
                }   
            }
        }
        
        return false;
    }
}

if (!function_exists('divulgacaoTipos')) {
    function divulgacaoTipos() {
        $arr = array(
            'tiktok'   => 'Tiktok',
            'facebook' => 'Facebook'
        );
        
        return $arr;
    }
}

if (!function_exists('linguaOpcoes')) {
    function linguaOpcoes($str = '', $tipo = '') {
        global $con;
        
        $retorno = '';
        
        $query = mysqli_query($con, "SELECT *
            FROM linguas
            WHERE
                linguaNome = '$str'
            ORDER BY linguaNome ASC;");
            
        if ($query) { 
            while ($linguaIem = mysqli_fetch_array($query)) { 
                $linguaID   = $linguaIem['linguaID'];
                $linguaNome = $linguaIem['linguaNome'];
                
                $pais = mysqli_query($con, "SELECT *
                    FROM lingua_pais
                    WHERE 
                        _linguaID = $linguaID
                    ORDER BY paisNome ASC;");
                    
                if ($pais) { 
                    while ($paisItem = mysqli_fetch_array($pais)) { 
                        $paisID   = $paisItem['paisID']; 
                        $paisNome = $paisItem['paisNome']; 
                        
                        ob_start(); ?>
                        
                        <div class="linguaOpcao">
                            <div class="linguaOpcaoNome">
                                <span id="copaIdioma-<?php echo $paisNome; ?>"><?php echo $paisNome; ?></span>
                                
                                <button type="button" onclick="copiarTexto('copaIdioma-<?php echo $paisNome; ?>')"><i class="fa fa-copy"></i> Copiar</button>
                            </div>
                               
                            <?php
                            $itens = mysqli_query($con, "SELECT *
                                FROM lingua_pais_itens
                                WHERE 
                                    _paisID = $paisID
                                ORDER BY itemID DESC;");
                                
                            if ($itens) { 
                                while ($itemValor = mysqli_fetch_array($itens)) { 
                                    $itemID     = $itemValor['itemID'];
                                    $itemNome   = $itemValor['itemNome'];
                                    $itemTier   = $itemValor['itemTier'];
                                    $itemGeral  = $itemValor['itemGeral'];
                                    $itemHomem  = $itemValor['itemHomem'];
                                    $itemMulher = $itemValor['itemMulher']; 
                                    
                                    $_itemNome = strtolower($itemNome);
                                    
                                    $tierCor   = arrTierCor($itemTier); 
                                    $socialCor = arrSocialCor($itemNome); 
                                    
                                    $mostraFacebook = true;
                                    $mostraTiktok   = true;
                                    
                                    if (!empty($tipo)) {
                                        if ($tipo == 'facebook') {
                                            if ($_itemNome ==  'tiktok')
                                                continue;
                                        }
                                        
                                        if ($tipo == 'tiktok') {
                                            if ($_itemNome ==  'facebook')
                                                continue;
                                        }
                                    }
                                    
                                    if ($itemNome == 'Facebook') {
                                        if (!validaPermissao('ver', 'gestor'))    
                                            continue;
                                    }
                                    
                                    if ($itemNome == 'Tiktok') {
                                        if (!validaPermissao('ver', 'gestor_tiktok'))    
                                            continue;
                                    } ?>
                                    
                                    <ul>
                                        <li style="<?php echo empty($socialCor) ? '' : 'background-color: ' . $socialCor; ?>"><?php echo $itemNome; ?></li>
                                        <li style="<?php echo empty($tierCor)   ? '' : 'background-color: ' . $tierCor; ?>"><?php echo $itemTier; ?></li>
                                        <li class="linguaOpcaoInfoHomem">H: <?php echo $itemHomem; ?></li>
                                        <li class="linguaOpcaoInfoMulher">M: <?php echo $itemMulher; ?></li>
                                        <li class="linguaOpcaoInfoGeral">G: <?php echo $itemGeral; ?></li>
                                    </ul>
                                    <?php 
                                }
                            } ?>
                        </div>
                        <?php 
                        $resultado = ob_get_contents();
                        ob_end_clean();
                        
                        $retorno .= $resultado;
                    }
                }
            }
        }
        
        return $retorno;
    }
}

if (!function_exists('arrTierCor')) {
    function arrTierCor($str = '') {
        $arr = array(
            'Tier 1' => getConfig('tier_1_cor'),
            'Tier 2' => getConfig('tier_2_cor'),
            'Tier 3' => getConfig('tier_3_cor'),
            'Tier 4' => getConfig('tier_4_cor')
        );
        
        if (isset($arr[$str]))
            return $arr[$str];
        return;
    }
}

if (!function_exists('arrTierResumoCor')) {
    function arrTierResumoCor($str = '') {
        $arr = array(
            'Tier 1' => getConfig('tier_resumo_1_cor'),
            'Tier 2' => getConfig('tier_resumo_2_cor'),
            'Tier 3' => getConfig('tier_resumo_3_cor'),
            'Tier 4' => getConfig('tier_resumo_4_cor')
        );
        
        if (isset($arr[$str]))
            return $arr[$str];
        return;
    }
}

if (!function_exists('arrSocialCor')) {
    function arrSocialCor($str = '') {
        $arr = array(
            'Facebook' => getConfig('facebook_cor'),
            'Tiktok'   => getConfig('tiktok_cor')
        );
        
        if (isset($arr[$str]))
            return $arr[$str];
        return;
    }
}

if (!function_exists('isMobile')) {
    function isMobile() {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
    }
}

/* -------------------------------------- */

if (!function_exists('campoData')) {
    function campoData($nome = '', $valor = '') {
        return '<span class="campoData">
            <span class="campoDataIcone"><i class="fa fa-calendar"></i></span>
            <input class="form-control date_picker maskData" placeholder="99/99/9999" type="text" name="' . $nome . '" value="' . $valor . '" />
        </span>';
    }
}

/* -------------------------------------- */

if (!function_exists('clientePermissaoCriativo')) {
    function clientePermissaoCriativo() {
        global $con;
        
        $clienteID = getClienteID();
        
        $query = mysqli_query($con, "SELECT *
            FROM clientes 
            WHERE 
                clienteID = $clienteID 
            LIMIT 1");
                
        $clientePermissoesCriativo = '';
        
        $retorno = '';
        
        if ($query) {
            $itemValor = mysqli_fetch_array($query);
            if (isset($itemValor['clientePermissoesCriativo']))
                $retorno = $itemValor['clientePermissoesCriativo'];        
        }
            
        $retorno = (array) json_decode($retorno, true);
        $retorno = array_filter($retorno);
            
        return $retorno;
    }
}

/* -------------------------------------- */

if (!function_exists('cronLista')) {
    function cronLista() {
        $retorno = array(
             array(
                'nome' => '1 - Cron Analytics Pais',
                'link' => site_url('cron/analytics_pais.php')
            ),
             
             array(
                'nome' => '2 - Cron Analytics Campanhas Origem',
                'link' => site_url('cron/analytics.php')
            ),
            
             array(
                'nome' => '3 - Cron Analytics Campanhas links',
                'link' => site_url('cron/analytics_links.php')
            ),
            
             
            array(
                'nome' => '4 - Campanhas Geral Referncia',
                'link' => site_url('cron/analytics_campanhas.php')
            ),
            
            array(
                'nome' => '5 - Cron Analytics Campanhas Google Ads e Palavras Chaves',
                'link' => site_url('cron/analytics_googleads.php')
            ),
            
            array(
                'nome' => '6 - Cron Gestor Pais',
                'link' => site_url('cron/analytics_gestor_pais.php')
            ),
            
            array(
                'nome' => '7 - Cron Facebook',
                'link' => site_url('cron/facebook.php')
            ),
            
            array(
                'nome' => '8 - Cron Campanhas Cache',
                'link' => site_url('cron/analytics_campanhas_cache.php')
            )
        );
        
        return $retorno;
    }
}

/* -------------------------------------- */

if (!function_exists('date2str')) {
    function date2str($data = '') {
        if (empty($data) || ($data == '0000-00-00') || ($data == '0000-00-00 00:00:00'))
            return;

        return date('d/m/Y', strtotime($data));
    }
}

/* -------------------------------------- */

if (!function_exists('validaData')) {
    function validaData($data = '') {

        if (!preg_match("/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/", $data)) {
            return false;
        }

        $arr = explode('/', $data);
        if (count($arr) > 0) {

            $d = isset($arr[0]) ? $arr[0] : 0;
            $m = isset($arr[1]) ? $arr[1] : 0;
            $y = isset($arr[2]) ? $arr[2] : 0;
         
            $retorno = checkdate($m, $d, $y);
            if ($retorno)
               return true;
        }
        
        return false;
    }
}

/* -------------------------------------- */

if (!function_exists('getConfig')) {
    function getConfig($str = '') {
        $retorno = file_get_contents('https://gestor.naveads.com/data/config.txt');
        $json    = (array) json_decode($retorno, true);
        $json    = array_filter($json);

        if (array_key_exists($str, $json))
            return $json[$str];
        return;
    }
}

/* -------------------------------------- */

if (!function_exists('validaDataDb')) {
    function validaDataDb($data = '') {
        if (empty($data) || ($data == '0000-00-00'))
            return false;

        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $data))
            return false;

        $d = explode('-', $data);

        if (checkdate($d[1], $d[2], $d[0]))
            return true;
        
        return false;
    }
}

/* -------------------------------------- */

if ( ! function_exists('data_db')) {
    function data_db($data = '') {
        if (empty($data))
            return;

        $arr = explode('/', $data);
        if (count($arr) > 0) {
            
            $d = isset($arr[0]) ? $arr[0] : 0;
            $m = isset($arr[1]) ? $arr[1] : 1;
            $y = isset($arr[2]) ? $arr[2] : 2;

            return $y .'-'. $m .'-'. $d;
        }

        return;
    }   
}

/* -------------------------------------- */

if (!function_exists('envioEmail')) {
    function envioEmail($email = '', $assunto = '', $mensagem = '') {
        $envio = false;
        $tipo  = (int) getInfo('envio_tipo');

        if ($tipo == 2) {
            $smtpHost  = getInfo('smtp_hostname');
            $smtpLogin = getInfo('smtp_login');
            $smtpSenha = getInfo('smtp_senha');
            $smtpPorta = getInfo('smtp_porta');
            $smtpEmail = getInfo('smtp_email');
            
            if (!class_exists('PHPMailer'))
                include($home . '/phpmailer/class.phpmailer.php');

            $mail = new PHPMailer(true);
             
            $mail->IsSMTP();
             
            try {
                $mail->Host     = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Port     = $smtpPorta;
                $mail->Username = $smtpLogin;
                $mail->Password = $smtpSenha;
                $mail->CharSet  = 'UTF-8';
             
                $mail->SetFrom($smtpEmail, get_bloginfo('name'));
                $mail->Subject  = $assunto;
             
                $mail->AddAddress($email, get_bloginfo('name'));
             
                $mail->MsgHTML($mensagem); 
                
                $envio = $mail->Send();
            } catch (phpmailerException $e) {
            }
        } else {

            $headers  = "MIME-Version: 1.1\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            
            $envio = mail($email, $assunto, $mensagem, $headers);
        }

        return $envio;
    }
}

/* -------------------------------------- */

if (!function_exists('getClienteID')) {
    function getClienteID() {
        return $_SESSION['cliente_id'];
    }
}

if (!function_exists('getClienteComissao')) {
    function getClienteComissao() {
        return $_SESSION['cliente_comissao'];
    }
}

if (!function_exists('getClienteContaFacebook')) {
    function getClienteContaFacebook() {
        global $con;
        
        $clienteID   = getClienteID();
        $arrContasID = array();
        
        $query = mysqli_query($con, "SELECT *
            FROM clientes
            WHERE 
                clienteID = $clienteID
            LIMIT 1;");
            
        if ($query) {
            $lista = mysqli_fetch_array($query);
            if (isset($lista['clienteID'])) {
                $clienteContaFacebook = $lista['clienteContaFacebook'];
                $clienteContaFacebook = (array) json_decode($clienteContaFacebook, true);
                $clienteContaFacebook = array_filter($clienteContaFacebook);
                
                if ($lista['clienteTipo'] == 'anunciante')
                    $arrContasID = $clienteContaFacebook;
            }
        }
            
        return $arrContasID;
    }
}

if (!function_exists('getClienteTipo')) {
    function getClienteTipo() {
        if (isset($_SESSION['cliente_tipo']))
            return $_SESSION['cliente_tipo'];
        return;
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        if (getClienteTipo() == 'administrador')
            return true;
        return false;
    }
}

/* -------------------------------------- */

if (!function_exists('fmoney')) {
    function fmoney($str) {
        if (empty($str) || ($str == '0.00'))
            return '0,00';
        
        return @number_format($str, 2, ',', '.');
    }
}

/* -------------------------------------- */

if (!function_exists('get_post')) {
    function get_post() {
        $post = array();

        if (isset($_POST)) {
            foreach ($_POST as $k => $v) {
                if (empty($v)) {
                    $post[$k] = '';
                } else {
                    if (is_array($v)) {
                        $post[$k] = $v;
                    } else {
                        $post[$k] = anti_injection($v);
                    }
                }
            }
        }

        return $post;
    }
}
/* -------------------------------------- */

if (!function_exists('get_row')) {
    function get_row($tabela = '', $where = array()) {
        global $con;

        $campos = array();
        foreach($where as $k => $v) {
            preg_match('/(<>|>=|<=|=|<|>)$/i', $k, $match);
            if (isset($match[1])) {
                $campos[] = "`". str_replace($match[1], '', $k) ."` ". $match[1] ." '$v'";
            } else {
                $campos[] = "`$k`= '$v'";
            }
        }

        $query = "SELECT * 
            FROM $tabela
            WHERE
                ". implode(' AND ', $campos) ."
            LIMIT 1;";

        $lista = mysqli_query($con, $query);

        if ($lista && mysqli_num_rows($lista) > 0) {
            $item = mysqli_fetch_array($lista);

            return $item;
        }

        return;
    }
}

/* -------------------------------------- */

if (!function_exists('textoTipo')) {
    function textoTipo($str = '', $retorno = false) {
        $arr = array(
            'disparo'          => 'Disparo',
            'primeiro_contato' => 'Primeiro contato'
        );

        if ($retorno) {
            if (array_key_exists($str, $arr))
                return $arr[$str];
            return;
        }

        return $arr;
    }
}

/* -------------------------------------- */

if (!function_exists('anti_injection')) {
    function anti_injection($sql = '') {

        $sql = @trim($sql); 
        $sql = @strip_tags($sql);
        $sql = @addslashes($sql);

        return $sql;
    }
}

/* -------------------------------------- */

if (!function_exists('admin_url')) {
    function admin_url($str = '') {
        global $admin_url;

        return $admin_url . $str;
    }
}

/* -------------------------------------- */

if (!function_exists('site_url')) {
    function site_url($str = '') {
        global $site_url;

        return $site_url . $str;
    }
}

/* -------------------------------------- */

if (!function_exists('base_url')) {
    function base_url($str = '') {
        global $base_url;

        return $base_url . $str;
    }
}

/* -------------------------------------- */

if (!function_exists('is_valid_email')) {
    function is_valid_email($mail = '') {
        $regexp = '/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,8})$/';
        return preg_match($regexp, $mail);
    }
}

/* -------------------------------------- */

if (!function_exists('insert')) {
    function insert($table = '', $data = array()) {
        global $con;

        ksort($data);
        
        $arrValores = array();
        foreach ($data as $itemID => $itemValor) {
            if (!empty($itemValor))
                $itemValor = stripslashes($itemValor);

            $arrValores[$itemID] = str_replace("'", "\'", $itemValor);
        }
        
        $names  = implode('`, `', array_keys($arrValores));
        $values = implode("','",  $arrValores);

        $sql = "INSERT INTO $table (`". $names ."`) 
                VALUES ('". $values ."') ";
        
        mysqli_query($con, $sql);

        return @mysqli_insert_id($con);
    }
}

/* -------------------------------------- */

if (!function_exists('update')) {
    function update($table = '', $data = array(), $where = '') {
        global $con;

        ksort($data);
        
        $arrValores = array();
        foreach ($data as $itemID => $itemValor) {
            if (!empty($itemValor))
                $itemValor = stripslashes($itemValor);
            
            $arrValores[$itemID] = str_replace("'", "\'", $itemValor);
        }
        
        $campos = NULL;
        foreach($arrValores as $k => $v) {
            $campos .= "`$k`= '$v',";
        }

        $campos = rtrim($campos, ',');

        return mysqli_query($con, "UPDATE $table SET $campos WHERE $where LIMIT 1;");
    }
}

/* -------------------------------------- */

if (!function_exists('getInfo')) {
    function getInfo($str = '') {
        $html  = @file_get_contents(ABSPATH . 'data/config.txt');
        $lista = (array) json_decode($html, true);
        $lista = array_filter($lista);

        return isset($lista[$str]) ? $lista[$str] : '';
    }
}

/* -------------------------------------- */

if (!function_exists('validaHora')) {
    function validaHora($str = '') {
        return preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $str);
    }
}

/* -------------------------------------- */

function diasEntreDatas($date2 = '', $date1 = '') {
    $now        = strtotime($date2);
    $your_date  = strtotime($date1);
    $datediff   = $now - $your_date;
    
    return floor($datediff / (60 * 60 * 24));
}

/* -------------------------------------- */

if (!function_exists('diasSemana')) {
    function diasSemana() {
        $diasemana = array(
            'Domingo', 
            'Segunda', 
            'Terça', 
            'Quarta', 
            'Quinta', 
            'Sexta', 
            'Sabado'
        );

        return $diasemana;
    }
}

/* -------------------------------------- */

function getLoginNome() {
    return isset($_SESSION['admin_nome']) ? $_SESSION['admin_nome'] : '';
}

function getLoginTelefone() {
    return isset($_SESSION['admin_telefone']) ? $_SESSION['admin_telefone'] : '';
}

function getLoginEmail() {
    return isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : '';
}

function getLoginID() {
    return isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : '';
}

function getLoginTipo() {
    return isset($_SESSION['admin_tipo']) ? $_SESSION['admin_tipo'] : '';
}

function ValidaMd5($md5 ='') {
    return preg_match('/^[a-f0-9]{32}$/', $md5);
}

function soNumero($str = '') {
    return preg_replace('/[^0-9]/', '', $str);
}

function getIP() {
    return $_SERVER['REMOTE_ADDR'];
}

/* -------------------------------------- */

if (!function_exists('adminMenu')) {
    function adminMenu($local = '', $tipo = '') {
        $arr = array (
            'home'          => 'Painel',
            'contas'        => 'Contas (Adsense)',
            'adx_contas'    => 'Contas (Adx)',
            'analytics'     => 'Google Analytics',
            'facebook'      => 'Facebook',
            'sites'         => 'Meus sites',
            'pagamentos'    => 'Pagamentos',
            'importar'      => 'Importar',
            'gestao_utms'   => 'Gesto (UTMS)',
            'gestor'        => 'Gestor (Facebook)',
            'gestor_adx'    => 'Gestor (ADX)',
            'gestor_tiktok' => 'Gestor (Tiktok)',
            'divulgacao2'   => 'Divulgação',
            'ajuda'         => 'Ajuda',
            'logins'        => 'Logins'
        ); 

        return $arr;
    }
}

/* -------------------------------------- */

if (!function_exists('validaPermissao')) {
    function validaPermissao($tipo = '', $local = '') {
        global $con;
        
        $clienteID     = getClienteID();
        $arrPermissoes = array();
        
        $query = mysqli_query($con, "SELECT SQL_CACHE clientePermissoes
            FROM clientes
            WHERE 
                clienteID = $clienteID
            LIMIT 1;");
            
        if ($query) {
            $lista = mysqli_fetch_array($query);
            if (isset($lista['clientePermissoes'])) {
                $arrPermissoes = (array) json_decode($lista['clientePermissoes'], true);
                $arrPermissoes = array_filter($arrPermissoes);
            }
        }
        
        if (isset($arrPermissoes[$local])) {
            $arrTipos = $arrPermissoes[$local];
            if (array_key_exists($tipo, $arrTipos))
                return true;
        }

        return false;
    }
}

/* -------------------------------------- */

if (!function_exists('getTopo')) {
    function getTopo() {
        global $con;

        ob_start();

        include('topo.php');

        $resultado = ob_get_contents();
        ob_end_clean();

        echo $resultado;
    }
}

/* -------------------------------------- */

if (!function_exists('getRodape')) {
    function getRodape() {
        global $con;
        
        ob_start();

        include('rodape.php');

        $resultado = ob_get_contents();
        ob_end_clean();

        echo $resultado;
    }
}

/* -------------------------------------- */

function minimizeCSSsimple($css){
    $css = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $css); // negative look ahead
    $css = preg_replace('/\s{2,}/', ' ', $css);
    $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
    $css = preg_replace('/;}/', '}', $css);
    $css = str_replace(array("\r", "\n"), '', $css);

    return $css;
}

/* -------------------------------------- */

function adsenseAlerts($pub = '', $access_token = '') {
    $link = 'https://adsense.googleapis.com/v2/accounts/' . $pub . '/alerts/' ;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info   = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);

    return $json;
}

/* -------------------------------------- */

function adsensePayments($pub = '', $access_token = '') {
    $link = 'https://adsense.googleapis.com/v2/accounts/' . $pub . '/payments' ;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info   = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);

    return $json;
}

/* -------------------------------------- */

function adsenseContas($access_token = '') {
    $link = 'https://adsense.googleapis.com/v2/accounts/';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info   = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);

    return $json;
}

/* -------------------------------------- */

function adsenseAdClients($pub = '', $access_token = '') {
    $link = 'https://adsense.googleapis.com/v2/accounts/' . $pub . '/adclients/';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info   = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);

    return $json;
}

/* -------------------------------------- */

function adsenseReportsSaved($pub = '', $access_token = '') {
    $link = 'https://adsense.googleapis.com/v2/' . $pub . '/reports/saved';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info   = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);

    return $json;
}

/* -------------------------------------- */

function adsenseAccountsSites($access_token = '', $pub = '') {
    $link = 'https://adsense.googleapis.com/v2/accounts/' . $pub . '/sites';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info   = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);

    return $json;
}

/* -------------------------------------- */

function adsenseReportingSites($access_token = '', $pub = '', $dataRange = '') {

    $dimensao = '';
    if (isset($_GET['dimensao'])) {
        $_dimensao = (array) $_GET['dimensao'];
        $_dimensao = array_filter($_dimensao);

        if (count($_dimensao) > 0) {
            foreach ($_dimensao as $dimensaoValor) {
                $dimensao .= 'dimensions=' . $dimensaoValor . '&';
            }
        }
    }

    if (empty($dimensao))
        $dimensao = 'dimensions=DOMAIN_NAME&';

    $link = 'https://adsense.googleapis.com/v2/accounts/' . $pub . '/reports:generate?' . $dimensao . 'metrics=ESTIMATED_EARNINGS&metrics=COST_PER_CLICK&metrics=AD_REQUESTS_CTR&metrics=CLICKS&metrics=IMPRESSIONS&metrics=IMPRESSIONS_RPM&metrics=ACTIVE_VIEW_VIEWABILITY&dimensions=DATE&';

    if ($dimensao <> 'AD_UNIT_NAME') {
        $link .= '&metrics=PAGE_VIEWS_RPM&metrics=PAGE_VIEWS';
    }

    if (empty($dataRange))
        $dataRange = 'TODAY';
        
    if (isset($_GET['data'])) {
        $_dataRange = $_GET['data'];
        if (!empty($_dataRange))
            $dataRange = $_dataRange;
    }

    $dataInicioDia = '';
    if (isset($_GET['data_inicio_dia'])) 
        $dataInicioDia = str_pad($_GET['data_inicio_dia'], 2, '0', STR_PAD_LEFT);
    
    $dataInicioMes = '';
    if (isset($_GET['data_inicio_mes'])) 
        $dataInicioMes = str_pad($_GET['data_inicio_mes'], 2, '0', STR_PAD_LEFT);

    $dataInicioAno = '';
    if (isset($_GET['data_inicio_ano'])) 
        $dataInicioAno = $_GET['data_inicio_ano'];
    
    $dataFinalDia = '';
    if (isset($_GET['data_final_dia'])) 
        $dataFinalDia = str_pad($_GET['data_final_dia'], 2, '0', STR_PAD_LEFT);

    $dataFinalMes = '';
    if (isset($_GET['data_final_mes'])) 
        $dataFinalMes = str_pad($_GET['data_final_mes'], 2, '0', STR_PAD_LEFT);
    
    $dataFinalAno = '';
    if (isset($_GET['data_final_ano'])) 
        $dataFinalAno = $_GET['data_final_ano'];

    $dimensaoFiltro = '';
    if (isset($_GET['dimensao_filtro'])) 
        $dimensaoFiltro = $_GET['dimensao_filtro'];

    $dataInicio = $dataInicioAno .'-'. $dataInicioMes .'-'. $dataInicioDia;
    $dataFinal  = $dataFinalAno  .'-'. $dataFinalMes  .'-'. $dataFinalDia;

    if ($dataRange == 'CUSTOM') {
        if (validaDataDb($dataFinal) && validaDataDb($dataInicio)) {
            $link .= '&dateRange=CUSTOM&startDate.year=' . $dataInicioAno . '&startDate.month=' . $dataInicioMes . '&startDate.day=' . $dataInicioDia . '&endDate.year=' . $dataFinalAno . '&endDate.month=' . $dataFinalMes . '&endDate.day=' . $dataFinalDia;
        } else {
            $link .= '&dateRange=' . $dataRange;
        }
    } else {
        if (empty($dataRange)) {
            $link .= '&dateRange=TODAY';
        } else {
            $link .= '&dateRange=' . $dataRange;
        }
    }

    if (isset($_GET['termo'])) {
        $termo = $_GET['termo'];
        $tipo  = $_GET['tipo'];

        if (empty($dimensaoFiltro))
            $dimensaoFiltro = 'DOMAIN_NAME';

        if (!empty($termo)) {
            if ($tipo == 2) {
                $link .= '&filters=' . $dimensaoFiltro . '%3D@' . $termo;
            } else {
                $link .= '&filters=' . $dimensaoFiltro . '%3D%3D' . $termo;
            }
        }
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info   = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);

    return $json;
}

/* -------------------------------------- */

function adsenseContaListaGanhos($apiKey = '', $access_token = '', $pub = '', $dateRange = '') {

    if (empty($dateRange))
        $dateRange = 'TODAY';



    $link = 'https://adsense.googleapis.com/v2/accounts/' . $pub . '/reports:generate?dateRange=' . $dateRange . '&reportingTimeZone=ACCOUNT_TIME_ZONE&metrics=ESTIMATED_EARNINGS&key=' . $apiKey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info   = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);

    return $json;
}

/* -------------------------------------- */

function adsenseReportsSavedGenerate($account = '', $access_token = '', $dateRange = '', $dataInicioDia = '', $dataInicioMes = '', $dataInicioAno = '', $dataFinalDia = '', $dataFinalMes = '', $dataFinalAno = '') {

    if (empty($dateRange))
        $dateRange = 'CUSTOM';

    $dataInicioDia = str_pad($dataInicioDia, 2, '0', STR_PAD_LEFT);
    $dataInicioMes = str_pad($dataInicioMes, 2, '0', STR_PAD_LEFT);
    $dataFinalDia  = str_pad($dataFinalDia,  2, '0', STR_PAD_LEFT);
    $dataFinalMes  = str_pad($dataFinalMes,  2, '0', STR_PAD_LEFT);

    $link = 'https://adsense.googleapis.com/v2/' . $account . '/saved:generate?dateRange=' . $dateRange;

    if ($dateRange == 'CUSTOM')
        $link = $link . '&startDate.year=' . $dataInicioAno . '&startDate.month=' . $dataInicioMes . '&startDate.day=' . $dataInicioDia . '&endDate.year=' . $dataFinalAno . '&endDate.month=' . $dataFinalMes . '&endDate.day=' . $dataFinalDia;


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info   = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);

    return $json;
}

function arrContas() {
    global $con;

    $arr = array();

    $query = mysqli_query($con, "SELECT *
        FROM contas
        ORDER BY contaNome ASC;");

    if ($query) {
        while ($v = mysqli_fetch_array($query)) {
            $contaID = $v['contaID'];

            $arr[$contaID] = $v['contaNome'];
        }
    }

    return $arr;
}


/* -------------------------------------- */

function analyticsDados($contaID = '', $access_token = '', $dataInicio = '', $dataFim = '') {
    $totalRevenue = 0;
    $publisherAdImpressions = 0;
  
    $totalRevenueIndex = 0;
    $publisherAdImpressionsIndex = 0;
    
    if (empty($dataInicio))
        $dataInicio = date('Y-m-d', strtotime('-15 days'));
        
    if (empty($dataFim))
        $dataFim = date('Y-m-d');
        
    $data = '
        {
            "dateRanges": [{ "startDate": "' . $dataInicio . '", "endDate": "' . $dataFim . '" }],
            "dimensions": [    
                 {"name": "sessionSource"}, 
                 {"name": "sessionMedium"},
                 {"name": "date"}
            ],
            "metrics": [
                {"name": "totalUsers" }, 
                {"name": "newUsers"},
                {"name": "screenPageViewsPerSession"},
                {"name": "averageSessionDuration"},
                {"name": "bounceRate"},
                {"name": "publisherAdImpressions"},
                {"name": "publisherAdClicks"},
                {"name": "totalAdRevenue"}
            ]
        }';
  
    $link = 'https://analyticsdata.googleapis.com/v1beta/properties/' . $contaID . ':runReport' ;
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);
    
    $json = (array) json_decode($result, true);
    $json = array_filter($json); 
    
    if (isset($json['rows'])) {
        foreach ($json['rows'] as $itemValor) { 
          
            if (isset($itemValor['metricValues'])) {
                foreach ($itemValor['metricValues'] as $_itemIndex => $_itemValor) {
                    if ($_itemIndex == $totalRevenueIndex)
                        $totalRevenue += $_itemValor['value'];
                  
                    if ($_itemIndex == $publisherAdImpressionsIndex)
                        $publisherAdImpressions += $_itemValor['value'];
                }
            }
        }
    }
    
    return array(
        'lista'                  => $json,
        'totalRevenue'           => $totalRevenue,
        'publisherAdImpressions' => $publisherAdImpressions
    );
}

/* -------------------------------------- */

function analyticsCampanhasManualTerm($contaID = '', $access_token = '', $dataInicio = '', $dataFim = '') {
    $totalRevenue = 0;
    $publisherAdImpressions = 0;
  
    $totalRevenueIndex = 0;
    $publisherAdImpressionsIndex = 0;
    
    if (empty($dataInicio))
        $dataInicio = date('Y-m-d', strtotime('-4 days'));
        
    if (empty($dataFim))
        $dataFim = date('Y-m-d', strtotime('-1 days'));
        
    $data = '
        {
            "dateRanges": [{ "startDate": "' . $dataInicio . '", "endDate": "' . $dataFim . '" }],
            "dimensions": [
                {"name": "date"},
                {"name": "firstUserManualTerm"},
                {"name": "firstUserCampaignName"}
            ],
            "metrics": [
                {"name": "totalUsers"}
            ]
        }';
  
    $link = 'https://analyticsdata.googleapis.com/v1beta/properties/' . $contaID . ':runReport' ;
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);
    
    return $json;
}

/* -------------------------------------- */

function analyticsCampanhas($contaID = '', $access_token = '', $dataInicio = '', $dataFim = '') {
    $totalRevenue = 0;
    $publisherAdImpressions = 0;
  
    $totalRevenueIndex = 0;
    $publisherAdImpressionsIndex = 0;
    
    if (empty($dataInicio))
        $dataInicio = date('Y-m-d', strtotime('-4 days'));
        
    if (empty($dataFim))
        $dataFim = date('Y-m-d', strtotime('-1 days'));
        
        // , {"name": "firstUserSource"}
        
    $data = '
        {
            "dateRanges": [{ "startDate": "' . $dataInicio . '", "endDate": "' . $dataFim . '" }],
            "dimensions": [
                {"name": "date"},
                {"name": "firstUserCampaignId"},
                {"name": "firstUserCampaignName"},
                {"name": "firstUserMedium"}],
            "metrics": [
                {"name": "totalUsers"}, 
                {"name": "screenPageViewsPerSession"},
                {"name": "averageSessionDuration"},
                {"name": "bounceRate"},
                {"name": "advertiserAdCostPerClick"},
                {"name": "publisherAdClicks"},
                {"name": "publisherAdImpressions"},
                {"name": "advertiserAdCost"},
                {"name": "totalAdRevenue"},
                {"name": "returnOnAdSpend"}
               
            ]
        }';
  
    $link = 'https://analyticsdata.googleapis.com/v1beta/properties/' . $contaID . ':runReport' ;
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);
    
    return array(
        'lista' => $json
    );
}

/* -------------------------------------- */

function analyticsLinks($contaID = '', $access_token = '', $dataInicio = '', $dataFim = '') {
    $totalRevenue = 0;
    $publisherAdImpressions = 0;
  
    $totalRevenueIndex = 0;
    $publisherAdImpressionsIndex = 0;
    
    if (empty($dataInicio))
        $dataInicio = date('Y-m-d', strtotime('-7 days'));
        
    if (empty($dataFim))
        $dataFim = date('Y-m-d', strtotime('-1 day'));
        
    $data = '
        {
            "dateRanges": [{ "startDate": "' . $dataInicio . '", "endDate": "' . $dataFim . '" }],
            "dimensions": [
                {"name": "date"},
                {"name": "pagePath"},
                {"name": "sessionCampaignName"}
            ],
            "metrics": [
                {"name": "totalUsers" }, 
                {"name": "screenPageViewsPerSession"},
                {"name": "averageSessionDuration"},
                {"name": "bounceRate"},
                {"name": "publisherAdClicks"},
                {"name": "publisherAdImpressions"},
                {"name": "totalAdRevenue"},
               
            ]
        }';
  
    $link = 'https://analyticsdata.googleapis.com/v1beta/properties/' . $contaID . ':runReport' ;
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);
    
    $json = (array) json_decode($result, true);
    $json = array_filter($json); 
    
    return array(
        'lista' => $json
    );
}

/* -------------------------------------- */

function analyticsPais($contaID = '', $access_token = '', $dataInicio = '', $dataFim = '') {
    $totalRevenue = 0;
    $publisherAdImpressions = 0;
  
    $totalRevenueIndex = 0;
    $publisherAdImpressionsIndex = 0;
    
    if (empty($dataInicio))
        $dataInicio = date('Y-m-d', strtotime('-3 days'));
        
    if (empty($dataFim))
        $dataFim = date('Y-m-d');
        
    $data = '
        {
            "dateRanges": [{ "startDate": "' . $dataInicio . '", "endDate": "' . $dataFim . '" }],
            "dimensions": [     
                {"name": "date"},
                {"name": "firstUserCampaignName"},
                {"name": "firstUserCampaignId"},
                {"name": "firstUserGoogleAdsAdGroupId"},
                {"name": "city"},
                {"name": "country"},
                {"name": "cityId"}

            ],
            "metrics": [
                {"name": "totalUsers" }, 
                {"name": "userEngagementDuration"},
                {"name": "screenPageViewsPerSession"},
                {"name": "bounceRate"},
                {"name": "publisherAdImpressions"},
                {"name": "publisherAdClicks"},
                {"name": "totalAdRevenue"},
            ]
        }';
  
    $link = 'https://analyticsdata.googleapis.com/v1beta/properties/' . $contaID . ':runReport' ;
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);
    
    $json = (array) json_decode($result, true);
    $json = array_filter($json); 
    
    return array(
        'lista' => $json
    );
}

/* -------------------------------------- */

function analyticsGoogleadsTotal($campanhaNome = '') {
    global $con;
    
    $total = 0;
    
    $query = mysqli_query($con, "SELECT COUNT(*) AS total
        FROM analytics_googleads
        WHERE
            googleads_firstUserGoogleAdsCampaignName = '$campanhaNome' 
        LIMIT 1;");
    
    if ($query) {
        $itemValor = mysqli_fetch_array($query);
        if (isset($itemValor['total'])) {
            $total = $itemValor['total'];
        }
    }
    
    return $total;
}

/* -------------------------------------- */

function analyticsLinksTotal($campanhaNome = '') {
    global $con;
    
    $total = 0;
    
    $query = mysqli_query($con, "SELECT COUNT(*) AS total
        FROM analytics_links
        WHERE
            link_sessionCampaignName = '$campanhaNome' 
        LIMIT 1;");
    
    if ($query) {
        $itemValor = mysqli_fetch_array($query);
        if (isset($itemValor['total'])) {
            $total = $itemValor['total'];
        }
    }
    
    return $total;
}

/* -------------------------------------- */

function numberPrecision($number, $decimals = 0) {
    $negation = ($number < 0) ? (-1) : 1;
    $coefficient = 10 ** $decimals;
    return $negation * floor((string)(abs($number) * $coefficient)) / $coefficient;
}

/* -------------------------------------- */

function analyticsGoogleadsLista($itemID = '', $data = '', $dataInicio = '', $dataFinal = '', $firstUserGoogleAdsadgroupName = '', $firstUserGoogleAdsKeyword = '', $roiInicio = '', $roiFinal = '', $extra = array()) {
    global $con;
    
    $dataAtual = date('Y-m-d');

    $where   = array();
    
    if ($itemID > 0)
        $where[] = "_analyticID = " . $itemID;
    
    if (!empty($firstUserGoogleAdsadgroupName))
        $where[] = "googleads_firstUserGoogleAdsCampaignName = '" . $firstUserGoogleAdsadgroupName . "'";
    
    if (!empty($firstUserGoogleAdsKeyword))
        $where[] = "googleads_firstUserGoogleAdsKeyword = '" . $firstUserGoogleAdsKeyword . "'";
        
    if ($data == 'CUSTOM') {
        if (validaDataDb($dataInicio) && validaDataDb($dataFinal)) {
            $where[] = "googleads_date between '$dataInicio' and '$dataFinal'";
        } else {
            $where[] = "googleads_date between '" . date('Y-m-d', strtotime('-1 day')) . "' and '" . date('Y-m-d') . "'";
        }

    } else {
        if ($data == 'HOJE') {
            $where[] = "googleads_date between '" . date('Y-m-d', strtotime('-1 day')) . "' and '" . date('Y-m-d') . "'";
        } else if ($data == 'YESTERDAY') {
            $where[] = "googleads_date = '" . date('Y-m-d', strtotime('-1 day')) . "'";
        } else if ($data == 'LAST_7_DAYS') {
            $where[] = "googleads_date >= '" . date('Y-m-d', strtotime('-7 days')) . "' AND googleads_date < '$dataAtual'";
        } else if ($data == 'MONTH_TO_DATE') {
            $where[] = "MONTH(googleads_date) = MONTH(CURRENT_DATE()) AND YEAR(googleads_date) = YEAR(CURRENT_DATE())";
        } else {
            $where[] = "googleads_date between '" . date('Y-m-d', strtotime('-1 day')) . "' and '" . date('Y-m-d') . "'";
        }
    }

    if ($data == 'YESTERDAY') {
        $dataInicio = date('Y-m-d', strtotime('-1 day'));
        $dataFinal  = date('Y-m-d', strtotime('-1 day'));
    }
    
    if ($data == 'LAST_7_DAYS') {
        $dataInicio = date('Y-m-d', strtotime('-7 day'));
        $dataFinal  = date('Y-m-d', strtotime('-1 day'));
    }
    
    if ($data == 'MONTH_TO_DATE') {
        $dataInicio = date('Y-m-01');
        $dataFinal  = date('Y-m-d', strtotime('-1 day'));
    }
    
    $arrNomes = array(
        'date'                           => 'Data',
        'firstUserGoogleAdsCampaignName' => 'Campanha',
        'firstUserGoogleAdsadgroupName'  => 'Grupo',
        'firstUserGoogleAdsadgroupId'    => 'Id do Grupo',
        'firstUserGoogleAdsKeyword'      => 'Palavras Chaves',
        'totalUsers'                     => 'Usuários',
        'userEngagementDuration'         => 'Duração/sesso',
        'screenPageViewsPerSession'      => 'Exibições/sessão',
        'bounceRate'                     => 'Rejeiço',
        'advertiserAdCostPerClick'       => 'Cpc GoogleAds',
        'publisherAdImpressions'         => 'Impressões Ad',
        'publisherAdClicks'              => 'Cliques Ad',
        'advertiserAdCost'               => 'Custo GoogleAds',
        'totalAdRevenue'                 => 'Receita Ad',
        'returnOnAdSpend'                => 'Roi',
        'advertiserAdImpressions'        => 'Impressões GoogleAds',
        'advertiserAdClicks'             => 'Cliques GoogleAds',
        'advertiserAdCostPerConversion'  => 'Custo/Conversão',
        'conversions'                    => 'Conversão'
    );
    
    if (isset($_GET['campanhaGoogleads']) ||
        isset($_GET['analyticsCampanhasCodigos']) ||
        isset($_GET['campanhaGoogleadsFiltro']) ||
        isset($_GET['bounceRate'])) {
            
        unset($arrNomes['firstUserGoogleAdsCampaignName']);
        unset($arrNomes['firstUserGoogleAdsadgroupName']);
        unset($arrNomes['firstUserGoogleAdsadgroupId']);
    }

    if (isset($_GET['analyticGoogleadsListaKeys']))
        unset($arrNomes['firstUserGoogleAdsKeyword']);
    
    ob_start();

    if (!floatVazio($roiFinal) || isset($_GET['analyticsCampanhasCodigos'])) { 
        if (empty($roiInicio))
            $roiInicio = 'vazio';
            
        if (empty($roiFinal))
            $roiFinal = 2000.00;
            
        unset($arrNomes['date']); 
        unset($arrNomes['screenPageViewsPerSession']); 
        unset($arrNomes['date']); 
        unset($arrNomes['userEngagementDuration']); 
        unset($arrNomes['bounceRate']); 
        
        $arrNomes['valor_gasto'] = 'Gasto/1000';
        $arrNomes['valor_ganho'] = 'Ganho/1000';
        $arrNomes['lance'] = 'Sugestão de Lance';

        $where = array();
        $where[] = "googleads_date >= '" . $dataInicio . "'";
        $where[] = "googleads_date <= '" . $dataFinal . "'";
        
        if ($itemID > 0)
            $where[] = '_analyticID = ' . $itemID;
        
        if (!empty($firstUserGoogleAdsadgroupName))
            $where[] = "googleads_firstUserGoogleAdsCampaignName = '" . $firstUserGoogleAdsadgroupName . "'";

        if (!empty($firstUserGoogleAdsKeyword))
            $where[] = "googleads_firstUserGoogleAdsKeyword = '" . $firstUserGoogleAdsKeyword . "'";

        $tabelaItens = '';

        $sql = "SELECT *,
                SUM(googleads_totalUsers) AS googleads_totalUsers,
                SUM(googleads_screenPageViewsPerSession) AS googleads_screenPageViewsPerSession,
                SUM(googleads_advertiserAdCost) AS googleads_advertiserAdCost,
                SUM(googleads_publisherAdClicks) AS googleads_publisherAdClicks,
                SUM(googleads_advertiserAdImpressions) AS googleads_advertiserAdImpressions,
                SUM(googleads_advertiserAdClicks) AS googleads_advertiserAdClicks,
                SUM(googleads_advertiserAdCostPerConversion) AS googleads_advertiserAdCostPerConversion,
                SUM(googleads_totalAdRevenue) AS googleads_totalAdRevenue,
                SUM(googleads_conversions) AS googleads_conversions
            FROM analytics_googleads
            WHERE
                " . implode(' AND ', $where) . "
            GROUP BY googleads_firstUserGoogleAdsKeyword
            ORDER BY CAST(googleads_advertiserAdCost AS FLOAT) DESC
            LIMIT 1000;";
            
        $query = mysqli_query($con, $sql);
        
        if ($query) {
            if (mysqli_num_rows($query) > 0) {

                $totalUsersTotal = 0;
                $publisherAdImpressionsTotal = 0;
                $publisherAdClicksTotal = 0;
                $totalAdRevenueTotal = 0;
                $newUsersTotal = 0;
                $advertiserAdClicksTotal = 0;
                $advertiserAdCostTotal = 0;
                $advertiserAdImpressionsTotal = 0;
                $conversionsTotal = 0;
            
                $posicao = 1;
            
                while ($itemValor = mysqli_fetch_array($query)) { 
                    $totalItens = 0; 
                    
                    $_firstUserGoogleAdsKeyword     = $itemValor['googleads_firstUserGoogleAdsKeyword'];
                    $_firstUserGoogleAdsadgroupName = $itemValor['googleads_firstUserGoogleAdsadgroupName'];
                    $_firstUserGoogleAdsadgroupId   = $itemValor['googleads_firstUserGoogleAdsadgroupId']; 
                    
                    if (isset($extra['not_set'])) {
                        if ($_firstUserGoogleAdsKeyword == '(direct)' ||
                            $_firstUserGoogleAdsKeyword == '(organic)' ||
                            $_firstUserGoogleAdsKeyword == '(not set)')
                            continue;
                    }
                    
                    $_query = mysqli_query($con, "SELECT COUNT(*) AS total
                        FROM analytics_googleads
                        WHERE
                            googleads_firstUserGoogleAdsadgroupName = '$_firstUserGoogleAdsadgroupName' AND
                            googleads_firstUserGoogleAdsadgroupId   = '$_firstUserGoogleAdsadgroupId' ;");
                    
                    if ($_query) {
                        $_itemValor = mysqli_fetch_array($_query);
                        if (isset($_itemValor['total']))
                            $totalItens = $_itemValor['total'];
                    } 
                    
                    $palavraNome = ''; 
                    if (isset($itemValor['googleads_firstUserGoogleAdsKeyword'])) 
                        $palavraNome = $itemValor['googleads_firstUserGoogleAdsKeyword']; 
                        
                    $mostraConteudo = true; 
                    
                    ob_start(); ?>
                    
                    <tr data-palavra="<?php echo $palavraNome; ?>">
                        
                        <?php 
                        foreach ($arrNomes as $itemIndex => $itemNome) {
                            $_itemValor  = '';
                            $style       = '';
                            $extra       = '';
                            
                            if (isset($itemValor['googleads_' . $itemIndex]))
                                $_itemValor = $itemValor['googleads_' . $itemIndex];
                                             
                            if ($itemIndex == 'userEngagementDuration')
                                $_itemValor = '<i class="fa fa-clock-o"></i> ' . $_itemValor;
                            
                            if ($itemIndex == 'totalUsers') {
                                if (empty($_itemValor))
                                    $_itemValor = 0;
                                    
                                $totalUsersTotal = $totalUsersTotal + $_itemValor;
                            }
                            
                            if ($itemIndex == 'advertiserAdCostPerConversion') {
                                $googleads_advertiserAdCost = $itemValor['googleads_advertiserAdCost'];
                                
                                $_itemValor = getConfig('real_simbolo') . ' ' . ($googleads_advertiserAdCost > 0 ? @numberPrecision($itemValor['googleads_advertiserAdCost'] / $itemValor['googleads_conversions'], 3) : '');
                            }
                            
                            if ($itemIndex == 'advertiserAdImpressions') {
                                if (empty($_itemValor))
                                    $_itemValor = 0;
                                    
                                $advertiserAdImpressionsTotal = $advertiserAdImpressionsTotal + $_itemValor;
                            }
                            
                            if ($itemIndex == 'publisherAdClicks')
                                $publisherAdClicksTotal = $publisherAdClicksTotal + $_itemValor;
                            
                            if ($itemIndex == 'totalAdRevenue'){
                                $_itemValor = round($_itemValor, 2);

                                $totalAdRevenueTotal = round($totalAdRevenueTotal + $_itemValor, 2);
                            }
                            
                            if ($itemIndex == 'advertiserAdClicks') {
                                if (empty($_itemValor))
                                    $_itemValor = 0;
                                    
                                $advertiserAdClicksTotal = $advertiserAdClicksTotal + $_itemValor;
                            }
                            
                            if ($itemIndex == 'bounceRate')
                                $_itemValor = $_itemValor . '%';
                            
                            if ($itemIndex == 'publisherAdImpressions') {
                                if (empty($_itemValor))
                                    $_itemValor = 0;
                                    
                                $publisherAdImpressionsTotal = $publisherAdImpressionsTotal + $_itemValor;
                            }
                            
                            if ($itemIndex == 'advertiserAdCost') {
                                if (empty($_itemValor))
                                    $_itemValor = 0;
                                    
                                $advertiserAdCostTotal = $advertiserAdCostTotal + $_itemValor;
                                
                                $_itemValor = getConfig('real_simbolo') . ' ' . fmoney($_itemValor);
                            }
                            
                            if ($itemIndex == 'returnOnAdSpend') {
                                $googleads_totalAdRevenue = (float) $itemValor['googleads_totalAdRevenue'];
                                
                                $_itemValor = '';
                                if ($googleads_totalAdRevenue > 0) {
                                    $_itemValor = $itemValor['googleads_totalAdRevenue'] / $itemValor['googleads_advertiserAdCost'];
                                    $_itemValor = round($_itemValor, 2);
                                }
                                
                                if ($roiInicio == 'vazio') {
                                    if ($_itemValor > $roiFinal)
                                        $mostraConteudo = false;
                                } else {
                                    if ($_itemValor < $roiInicio || $_itemValor > $roiFinal)
                                        $mostraConteudo = false;
                                }

                                if ($_itemValor >= 2.00) { 
                                    $linhaTipo = 'destaqueLinha1';
                                    
                                    $_itemValor = '<span class="label label-success">' . $_itemValor . '</span>';
                                } else if ($_itemValor < 0.90) {
                                    $linhaTipo = 'destaqueLinha2';
                                    
                                    $_itemValor = '<span class="label label-danger">' . $_itemValor . '</span>';
                                } else if ($_itemValor < 1.11) { 
                                    $linhaTipo = 'destaqueLinha3';
                                    
                                    $_itemValor = '<span class="label label-warning">' . $_itemValor . '</span>';
                                } else if ($_itemValor < 2.00) { 
                                    $linhaTipo = 'destaqueLinha4';
                                    
                                    $_itemValor = '<span class="label label-info">' . $_itemValor . '</span>';
                                }
                            }
                            
                            if ($itemIndex == 'valor_gasto') {
                                $_itemValor = ($itemValor['googleads_advertiserAdCost'] / $itemValor['googleads_advertiserAdClicks']) * 1000;
                                $_itemValor = fmoney($_itemValor);
                            }
                            
                            if ($itemIndex == 'valor_ganho') {
                                $_itemValor = ($itemValor['googleads_totalAdRevenue'] / $itemValor['googleads_totalUsers']) * 1000;
                                $_itemValor = fmoney($_itemValor);
                            }
                            
                            if ($itemIndex == 'lance') {
                                $_itemValor = (float) $itemValor['googleads_totalAdRevenue'] / $itemValor['googleads_advertiserAdCost'];
                                $_itemValor = round($_itemValor, 2);
                                
                                if ($_itemValor > 2.00) {
                                    $_itemValor = $itemValor['googleads_advertiserAdCostPerClick'] + (($itemValor['googleads_advertiserAdCostPerClick'] / 100) * 10) ;
                                } else if ($_itemValor == '0' || $_itemValor == '0.00') {
                                    $_itemValor = $itemValor['googleads_advertiserAdCostPerClick'] - (($itemValor['googleads_advertiserAdCostPerClick'] / 100) * 50) ;
                                } else if ($_itemValor > 1.00 && $_itemValor < 1.50) {
                                    $_itemValor = $itemValor['googleads_advertiserAdCostPerClick'] - (($itemValor['googleads_advertiserAdCostPerClick'] / 100) * 20) ;
                                } else if ($_itemValor < 1.00) {
                                    $_itemValor = $itemValor['googleads_advertiserAdCostPerClick'] - (($itemValor['googleads_advertiserAdCostPerClick'] / 100) * 30) ;
                                } else {
                                    $_itemValor = $itemValor['googleads_advertiserAdCostPerClick'];
                                }
                                
                                $_itemValor = fmoney($_itemValor);
                            }
                             
                            echo '<td>' . $_itemValor . '</td>'; 
                        } ?>
                    </tr>
                    
                    <?php 
                    $tabelaRetorno = ob_get_contents();
                    ob_end_clean();

                    $tabelaItens .= $tabelaRetorno;
                    
                    if (!isset($_GET['analyticsCampanhasCodigos'])) {
                        if ($mostraConteudo)
                            echo $tabelaRetorno;
                    }
                    
                    $posicao++;
                } 
            }
        }

        if (!empty($tabelaItens)) { 

            if (!isset($_GET['analyticsCampanhasCodigos'])) { ?>
                <div class="form-group">
                    <a target="_blank" href="<?php echo site_url('baixar.php?analyticsGoogleads&analyticID=' . $itemID . '&googleads_firstUserGoogleAdsCampaignName=' . $firstUserGoogleAdsadgroupName . '&googleads_firstUserGoogleAdsKeyword=' . $firstUserGoogleAdsKeyword . '&data_inicio=' . $dataInicio . '&data_final=' . $dataFinal . '&roi_inicio=' . $roiInicio . '&roi_final=' . $roiFinal); ?>" class="btn btn-block btn-primary"><i class="fa fa-download"></i> Baixar Keyword</a>
                </div>
                
                <div class="form-group">
                    <a target="_blank" href="<?php echo site_url('baixar.php?analyticsGoogleadsCodigo&analyticID=' . $itemID . '&googleads_firstUserGoogleAdsCampaignName=' . $firstUserGoogleAdsadgroupName . '&googleads_firstUserGoogleAdsKeyword=' . $firstUserGoogleAdsKeyword . '&data_inicio=' . $dataInicio . '&data_final=' . $dataFinal . '&roi_inicio=' . $roiInicio . '&roi_final=' . $roiFinal); ?>" class="btn btn-block btn-primary"><i class="fa fa-download"></i> Baixar Código</a>
                </div>
                <?php 
            } ?>

            <table class="table table-bordered tabelaDados" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <?php 
                        foreach ($arrNomes as $itemNome) {
                            echo '<th><small>' . $itemNome . '</small></th>'; 
                        } ?>
                    </tr>
                </thead>
                <tbody>

                    <?php echo $tabelaItens; ?>

                    <tr style="background-color: #ededed; color: #000;">
                        <?php 
                        foreach ($arrNomes as $itemIndex => $itemNome) {
                            $itemValor = '';
                            
                            if ($itemIndex == 'totalUsers') {
                                $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $totalUsersTotal . '</strong>';
                                
                            } else if ($itemIndex == 'conversions') {
                                $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $conversionsTotal . '</strong>';
                                
                            } else if ($itemIndex == 'advertiserAdImpressions') {
                                $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $advertiserAdImpressionsTotal . '</strong>';
                                
                            } else if ($itemIndex == 'publisherAdImpressions') {
                                $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $publisherAdImpressionsTotal . '</strong>';
                                
                            } else if ($itemIndex == 'publisherAdClicks') {
                                $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $publisherAdClicksTotal . '</strong>';
                                
                            } else if ($itemIndex == 'advertiserAdClicks') {
                                $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $advertiserAdClicksTotal . '</strong>';
                            
                            } else if ($itemIndex == 'advertiserAdCost') {
                                $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . getConfig('moeda_simbolo') . ' ' . fmoney($advertiserAdCostTotal) . '</strong>';
                            
                            } else if ($itemIndex == 'totalAdRevenue') {
                                $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . getConfig('moeda_simbolo') . ' ' . fmoney($totalAdRevenueTotal) . '</strong>';
                                
                            } else if ($itemIndex == 'newUsers') {
                                $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $newUsersTotal . '</strong>';
                            }
                                
                            echo '<td>' . $itemValor . '</td>';
                        } ?>
                    </tr>

                </tbody>
            </table>
            <?php
        } 

    } else {
        
        $sql = "SELECT googleads_date
            FROM analytics_googleads
            WHERE 
                " . implode(' AND ', $where) . "
            GROUP BY googleads_date
            ORDER BY googleads_date DESC;";
            
        $queryData = mysqli_query($con, $sql);
        
        if ($queryData) {
            while ($dataValor = mysqli_fetch_array($queryData)) { 
                $data = $dataValor['googleads_date'];  
                
                $where = array();
                $where[] = "googleads_date = '" . $data . "'";
                
                if ($itemID > 0)
                    $where[] = '_analyticID = ' . $itemID;
                
                if (!empty($firstUserGoogleAdsadgroupName))
                    $where[] = "googleads_firstUserGoogleAdsCampaignName = '" . $firstUserGoogleAdsadgroupName . "'";

                if (!empty($firstUserGoogleAdsKeyword))
                    $where[] = "googleads_firstUserGoogleAdsKeyword = '" . $firstUserGoogleAdsKeyword . "'";
                    
                $query = mysqli_query($con, "SELECT *
                    FROM analytics_googleads
                    WHERE
                        " . implode(' AND ', $where) . "
                    ORDER BY CAST(googleads_advertiserAdCost AS FLOAT) DESC
                    LIMIT 1000;");
                    
                if ($query) {
                    if (mysqli_num_rows($query) > 0) {
                        
                        if (!isset($_GET['analyticGoogleadsListaKeys'])) { ?>
                            <div class="">
                                <table class="table table-bordered tabelaDados" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <?php 
                                            foreach ($arrNomes as $itemNome) {
                                                echo '<th><small>' . $itemNome . '</small></th>'; 
                                            } ?>
                                        </tr>
                                    </thead>
                                <tbody>

                                <?php 
                            } 

                                    $totalUsersTotal = 0;
                                    $publisherAdImpressionsTotal = 0;
                                    $publisherAdClicksTotal = 0;
                                    $totalAdRevenueTotal = 0;
                                    $newUsersTotal = 0;
                                    $advertiserAdClicksTotal = 0;
                                    $advertiserAdCostTotal = 0;
                                    $advertiserAdImpressionsTotal = 0;
                                    $conversionsTotal = 0;
                                
                                    $posicao = 1;
                                
                                    while ($itemValor = mysqli_fetch_array($query)) { 
                                        $totalItens = 0; 
                                        
                                        $_firstUserGoogleAdsKeyword     = $itemValor['googleads_firstUserGoogleAdsKeyword'];
                                        $_firstUserGoogleAdsadgroupName = $itemValor['googleads_firstUserGoogleAdsadgroupName'];
                                        $_firstUserGoogleAdsadgroupId   = $itemValor['googleads_firstUserGoogleAdsadgroupId']; 
                                        
                                        if (isset($extra['not_set'])) {
                                            if ($_firstUserGoogleAdsKeyword == '(direct)' ||
                                                $_firstUserGoogleAdsKeyword == '(organic)' ||
                                                $_firstUserGoogleAdsKeyword == '(not set)')
                                                continue;
                                        }
                                        
                                        $_query = mysqli_query($con, "SELECT COUNT(*) AS total
                                            FROM analytics_googleads
                                            WHERE
                                                googleads_firstUserGoogleAdsadgroupName = '$_firstUserGoogleAdsadgroupName' AND
                                                googleads_firstUserGoogleAdsadgroupId   = '$_firstUserGoogleAdsadgroupId' 
                                            LIMIT 1;");
                                        
                                        if ($_query) {
                                            $_itemValor = mysqli_fetch_array($_query);
                                            if (isset($_itemValor['total']))
                                                $totalItens = $_itemValor['total'];
                                        } 
                                        
                                        $palavraNome = ''; 
                                        if (isset($itemValor['googleads_firstUserGoogleAdsKeyword'])) 
                                            $palavraNome = $itemValor['googleads_firstUserGoogleAdsKeyword']; ?>
                                        
                                        <tr data-palavra="<?php echo $palavraNome; ?>">
                                            
                                            <?php 
                                            foreach ($arrNomes as $itemIndex => $itemNome) {
                                                $_itemValor  = $itemValor['googleads_' . $itemIndex];
                                                $style       = '';
                                                $extra       = '';
                                                
                                                if ($itemIndex == 'date')
                                                    $_itemValor = '<i class="fa fa-calendar"></i> ' . date('d/m/Y', strtotime($_itemValor));
                                                    
                                                if ($itemIndex == 'firstUserGoogleAdsCampaignName') {
                                                    $_itemValor = '
                                                        <a href="javascript:;" onclick="modalGoogleads(\'' . $_firstUserGoogleAdsadgroupName . '\', \'' . $_firstUserGoogleAdsadgroupId . '\')">
                                                            <span class="campanhaNome">
                                                                ' . $_itemValor . ' <span>' . $totalItens . '</span>
                                                            </span>
                                                        </a>';
                                                }
                                                
                                                if ($itemIndex == 'firstUserGoogleAdsKeyword') {
                                                    $_itemValor = '<span class="label botaoKey" onclick="googleAnalyticsKeys('. $posicao .', \'' . $_itemValor . '\', ' . $itemID . ')"><i class="fa fa-key"></i> ' . $_itemValor . '</span>';
                                                }
                                                
                                                if ($itemIndex == 'userEngagementDuration')
                                                    $_itemValor = '<i class="fa fa-clock-o"></i> ' . $_itemValor;
                                                
                                                if ($itemIndex == 'totalUsers') {
                                                    if (empty($_itemValor))
                                                        $_itemValor = 0;
                                                        
                                                    $totalUsersTotal = $totalUsersTotal + $_itemValor;
                                                }
                                                
                                                if ($itemIndex == 'conversions') {
                                                    if (empty($_itemValor))
                                                        $_itemValor = 0;
                                                        
                                                    $conversionsTotal = $conversionsTotal + $_itemValor;
                                                }
                                                
                                                if ($itemIndex == 'advertiserAdImpressions') {
                                                    if (empty($_itemValor))
                                                        $_itemValor = 0;
                                                        
                                                    $advertiserAdImpressionsTotal = $advertiserAdImpressionsTotal + $_itemValor;
                                                }
                                                
                                                if ($itemIndex == 'publisherAdClicks')
                                                    $publisherAdClicksTotal = $publisherAdClicksTotal + $_itemValor;
                                                
                                                if ($itemIndex == 'totalAdRevenue')
                                                    $totalAdRevenueTotal = $totalAdRevenueTotal + $_itemValor;
                                                
                                                if ($itemIndex == 'advertiserAdClicks') {
                                                    if (empty($_itemValor))
                                                        $_itemValor = 0;
                                                        
                                                    $advertiserAdClicksTotal = $advertiserAdClicksTotal + $_itemValor;
                                                }
                                                
                                                if ($itemIndex == 'bounceRate')
                                                    $_itemValor = $_itemValor . '%';
                                                
                                                if ($itemIndex == 'publisherAdImpressions') {
                                                    if (empty($_itemValor))
                                                        $_itemValor = 0;
                                                        
                                                    $publisherAdImpressionsTotal = $publisherAdImpressionsTotal + $_itemValor;
                                                }
                                                
                                                if ($itemIndex == 'advertiserAdCost') {
                                                    if (empty($_itemValor))
                                                        $_itemValor = 0;
                                                        
                                                    $advertiserAdCostTotal = $advertiserAdCostTotal + $_itemValor;
                                                    
                                                    $_itemValor = getConfig('real_simbolo') . ' ' . fmoney($_itemValor);
                                                }
                                                
                                                if ($itemIndex == 'advertiserAdCostPerConversion') {
                                                    $_itemValor = getConfig('real_simbolo') . ' ' . fmoney($_itemValor);
                                                }
                                                
                                                if ($itemIndex == 'returnOnAdSpend') {
                                                    $_itemValor = (float) $_itemValor;
                                                    
                                                    if (!floatVazio($roiInicio) && !floatVazio($roiFinal)) {
                                                        if ($_itemValor < $roiInicio || $_itemValor > $roiFinal) {
                                                            $mostraConteudo = false;
                                                        }
                                                    }
                                                    
                                                    if ($_itemValor >= 2.00) { 
                                                        $linhaTipo = 'destaqueLinha1';
                                                        
                                                        $_itemValor = '<span class="label label-success">' . $_itemValor . '</span>';
                                                    } else if ($_itemValor < 0.90) {
                                                        $linhaTipo = 'destaqueLinha2';
                                                        
                                                        $_itemValor = '<span class="label label-danger">' . $_itemValor . '</span>';
                                                    } else if ($_itemValor < 1.11) { 
                                                        $linhaTipo = 'destaqueLinha3';
                                                        
                                                        $_itemValor = '<span class="label label-warning">' . $_itemValor . '</span>';
                                                    } else if ($_itemValor < 2.00) { 
                                                        $linhaTipo = 'destaqueLinha4';
                                                        
                                                        $_itemValor = '<span class="label label-info">' . $_itemValor . '</span>';
                                                    }
                                                    
                                                    $extra = 'onclick="destacarLinha(\'' . $palavraNome . '\', \'' . $linhaTipo . '\')"';
                                                }
                                                 
                                                echo '<td class="' . $itemIndex . '" class="' . ($itemIndex == 'firstUserGoogleAdsKeyword' ? 'tdFix' : '') . '" style="' . $style . '" ' . $extra . '>' . $_itemValor . '</td>'; 
                                            } ?>
                                        </tr>
                                        
                                        <?php 
                                        if (!isset($_GET['analyticGoogleadsListaKeys'])) { ?>
                                            <tr style="background-color: #8b8b8b;">
                                                <td colspan="<?php echo count($arrNomes); ?>" id="<?php echo 'lista-' . $posicao; ?>" style="display: none;"></td>
                                            </tr>
                                            <?php
                                        }
                                        
                                        $posicao++;
                                    } 
                                    
                                    if (!isset($_GET['analyticGoogleadsListaKeys'])) { ?>
                                
                                        <tr style="background-color: #ededed; color: #000;">
                                            <?php 
                                            foreach ($arrNomes as $itemIndex => $itemNome) {
                                                $itemValor = '';
                                                
                                                if ($itemIndex == 'totalUsers') {
                                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $totalUsersTotal . '</strong>';
                                                    
                                                } else if ($itemIndex == 'conversions') {
                                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $conversionsTotal . '</strong>';
                                                    
                                                } else if ($itemIndex == 'advertiserAdImpressions') {
                                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $advertiserAdImpressionsTotal . '</strong>';
                                                    
                                                } else if ($itemIndex == 'publisherAdImpressions') {
                                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $publisherAdImpressionsTotal . '</strong>';
                                                    
                                                } else if ($itemIndex == 'publisherAdClicks') {
                                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $publisherAdClicksTotal . '</strong>';
                                                    
                                                } else if ($itemIndex == 'advertiserAdClicks') {
                                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $advertiserAdClicksTotal . '</strong>';
                                                
                                                } else if ($itemIndex == 'advertiserAdCost') {
                                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . getConfig('moeda_simbolo') . ' ' . fmoney($advertiserAdCostTotal) . '</strong>';
                                                
                                                } else if ($itemIndex == 'totalAdRevenue') {
                                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . getConfig('moeda_simbolo') . ' ' . fmoney($totalAdRevenueTotal) . '</strong>';
                                                    
                                                } else if ($itemIndex == 'newUsers') {
                                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $newUsersTotal . '</strong>';
                                                }
                                                    
                                                echo '<td>' . $itemValor . '</td>';
                                            } ?>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>

                            <?php 
                        }

                    }
                }
            }
        } 
    }
    
    $retorno = ob_get_contents();
    ob_end_clean();
    
    return $retorno;
}

/* -------------------------------------- */

function analyticsGoogleadsCron() {
    global $con;

    set_time_limit(0);

    if (isset($_GET['manual'])) {
        
        if (isset($_GET['iniciar'])) {
            file_put_contents(ABSPATH . '/data/cron_googleads.txt', '');
            
            echo 'Iniciando...';
            
            if (!isset($_GET['geral']))
                header('location: ' . site_url('cron/analytics_googleads.php?manual&executar' . (isset($_GET['ontem']) ? '&ontem' : '')));
                
            exit;

        } else if (isset($_GET['executar'])) {
            
            $aplicados = file_get_contents(ABSPATH . '/data/cron_googleads.txt');
            $aplicados = (array) json_decode($aplicados, true);
            
            $totalAplicados = 0;
            
            foreach ($aplicados as $itemValor) {
                if (isset($_GET['ontem'])) {
                    if (count($itemValor) == 1)
                        $totalAplicados++;
                } else {
                    if (count($itemValor) == 6)
                        $totalAplicados++;
                }
            }
            
            if (isset($_GET['ontem'])) {
                $arrDias = array(
                    date('Y-m-d', strtotime('-1 day'))
                );

            } else {
                $arrDias = array(
                    date('Y-m-d', strtotime('-1 day')),
                    date('Y-m-d', strtotime('-2 day')),
                    date('Y-m-d', strtotime('-3 day')),
                    date('Y-m-d', strtotime('-4 day')),
                    
                );
            }
            
            $query = mysqli_query($con, "SELECT *
                FROM analytics
                    INNER JOIN contas ON contaID = _contaID
                LIMIT 100;");
            
            if ($query) {
                $total = mysqli_num_rows($query);
                
                echo 'TOTAL DE SITES: ' . $total . '<br />';
                echo 'APLICADOS: ' . $totalAplicados . '<br /><br />';
                
                if ($totalAplicados == $total) {
                    echo 'Finalizado<br />';
                    
                } else {
                    
                    while ($itemValor = mysqli_fetch_array($query)) { 
                        $analyticID       = $itemValor['analyticID'];
                        $analyticContaID  = $itemValor['analyticContaID'];
                        $contaAccessToken = $itemValor['contaAccessToken'];

                        $tempoInicio = microtime(true); 
                        
                        echo 'CONTA: ' . $itemValor['analyticNome'] . '<br />';
                        
                        foreach ($arrDias as $diaValor) {
                            
                            if (isset($aplicados[$analyticID])) {
                                if (in_array($diaValor, $aplicados[$analyticID]))
                                    continue;
                                    
                                if (isset($_GET['ontem'])) {
                                    if (count($aplicados[$analyticID]) == 1)
                                        break 1;

                                } else {
                                    if (count($aplicados[$analyticID]) == 6)
                                        break 2;
                                }
                            }

                            mysqli_query($con, "DELETE FROM analytics_googleads 
                                WHERE 
                                    _analyticID    = $analyticID AND 
                                    googleads_date = '$diaValor';"); 
                    
                            mysqli_query($con, "DELETE FROM analytics_googleads_itens 
                                WHERE 
                                    _analyticID    = $analyticID AND 
                                    item_date = '" . date('Ymd', strtotime($diaValor)) . "';");
                            
                            $aplicados[$analyticID][] = $diaValor;
                            
                            file_put_contents(ABSPATH . '/data/cron_googleads.txt', json_encode($aplicados));
    
                            echo 'Inserindo para o dia: ' . $diaValor . '<br />';
                                
                            /* continue; */
                            
                            $itens = analyticsGoogleads($analyticContaID, $contaAccessToken, $diaValor, $diaValor);
                            
                            $json  = (array) $itens['lista2'];

                            if (count($json) > 0) {
                                $arrTopo  = array();
                    
                                if (isset($json['dimensionHeaders'] )) {
                                    foreach ($json['dimensionHeaders'] as $itemIndex => $itemValor) {
                                        $arrTopo[] = $itemValor['name'];
                                    }
                                } 
                    
                                if (isset($json['metricHeaders']    )) {
                                    foreach ($json['metricHeaders'] as $itemIndex => $itemValor) {
                                        $arrTopo[] = $itemValor['name'];
                                    }
                                } 
                                
                                $arrLinhas = array();
                                
                                if (isset($json['rows'])) {
                                    foreach ($json['rows'] as $linhaIndex => $itemValor) { 
                                        $pos = 0;
                                        if (isset($itemValor['dimensionValues'] )) {
                                            foreach ($itemValor['dimensionValues'] as $itemIndex => $_itemValor) {
                                                $campoNome = $arrTopo[$pos];
                                                
                                                $arrLinhas[$linhaIndex][$campoNome] = $_itemValor['value'];
                                                
                                                $pos++;
                                            }
                                        } 
                                  
                                        if (isset($itemValor['metricValues'])) {
                                            foreach ($itemValor['metricValues'] as $_itemIndex => $_itemValor) {
                                                $campoNome = $arrTopo[$pos];
                                                
                                                $arrLinhas[$linhaIndex][$campoNome] = $_itemValor['value'];
                                                
                                                $pos++;
                                            }
                                        }
                                    }
                                } 
                                
                                $posicao      = 1;
                                $tabelaCampos = '';
                                $arrInserts   = array();
                                $arrCampos    = array();

                                foreach ($arrLinhas as $arrItens) { 
                                    $dados = array();
                                    
                                    $itemID   = '';
                                    $itemDate = '';
                                    $itemNome = '';
                    
                                    foreach ($arrItens as $linhaIndex => $linhaValor) {
                                        $dados['item_' . $linhaIndex] = $linhaValor;
                                        
                                        if ($linhaIndex == 'date')
                                            $itemDate = $linhaValor;
                                        
                                        if ($linhaIndex == 'firstUserGoogleAdsCampaignName')
                                            $itemNome = $linhaValor;
                                    }
                                    
                                    $dados['itemData']    = date('Y-m-d');
                                    $dados['_analyticID'] = $analyticID;
                                    
                                    $arrInserts[$posicao][] =  "('" . implode("', '", $dados) . "')";

                                    if (count($arrInserts[$posicao]) > 300)
                                        $posicao++;

                                    if (count($arrCampos) == 0)
                                        $tabelaCampos = '(' . implode(', ', array_keys($dados)) . ')';
                                }

                                foreach ($arrInserts as $insertValor) {
                                    $sql = "INSERT INTO 
                                        analytics_googleads_itens " . $tabelaCampos . "
                                    VALUES
                                        " . implode(', ', $insertValor);

                                    mysqli_query($con, $sql);
                                }
                            }
                           
                            $json  = (array) $itens['lista1'];
                            
                            if (count($json) > 0) {
                                $arrTopo  = array();
                    
                                if (isset($json['dimensionHeaders'] )) {
                                    foreach ($json['dimensionHeaders'] as $itemIndex => $itemValor) {
                                        $arrTopo[] = $itemValor['name'];
                                    }
                                } 
                    
                                if (isset($json['metricHeaders']    )) {
                                    foreach ($json['metricHeaders'] as $itemIndex => $itemValor) {
                                        $arrTopo[] = $itemValor['name'];
                                    }
                                } 
                                
                                $arrLinhas = array();
                                
                                if (isset($json['rows'])) {
                                    foreach ($json['rows'] as $linhaIndex => $itemValor) { 
                                        $pos = 0;
                                        if (isset($itemValor['dimensionValues'] )) {
                                            foreach ($itemValor['dimensionValues'] as $itemIndex => $_itemValor) {
                                                $campoNome = $arrTopo[$pos];
                                                
                                                $arrLinhas[$linhaIndex][$campoNome] = $_itemValor['value'];
                                                
                                                $pos++;
                                            }
                                        } 
                                  
                                        if (isset($itemValor['metricValues'])) {
                                            foreach ($itemValor['metricValues'] as $_itemIndex => $_itemValor) {
                                                $campoNome = $arrTopo[$pos];
                                                
                                                $arrLinhas[$linhaIndex][$campoNome] = $_itemValor['value'];
                                                
                                                $pos++;
                                            }
                                        }
                                    }
                                } 
                                
                                $posicao      = 1;
                                $tabelaCampos = '';
                                $arrInserts   = array();

                                foreach ($arrLinhas as $itemIndex => $arrItens) { 
                                    $dados    = array();
                                    
                                    $itemID    = 0;
                                    $itemData  = '';
                                    $itemData2 = '';
                                    $itemNome  = '';
                                    
                                    foreach ($arrItens as $linhaIndex => $linhaValor) {
                                        
                                        if ($linhaIndex == 'date') {
                                            $itemData   = $linhaValor;
                                            $linhaValor = substr($linhaValor, 0, 4) . '-' . substr($linhaValor, 4, 2) . '-' . substr($linhaValor, 6, 2);
                                            $itemData2  = $linhaValor;
                                        }
                                            
                                        if ($linhaIndex == 'firstUserGoogleAdsCampaignName')
                                            $itemNome = $linhaValor;
                                            
                                        if ($linhaIndex == 'screenPageViewsPerSession')
                                            $linhaValor = round($linhaValor, 2);
                                            
                                        if ($linhaIndex == 'bounceRate')
                                            $linhaValor = str_replace('0.', '', round($linhaValor, 2));
                                            
                                        
                                        if ($linhaIndex == 'advertiserAdCostPerClick')
                                            $linhaValor = round($linhaValor, 2);
                                            
                                        if ($linhaIndex == 'publisherAdImpressions')
                                            $linhaValor = round($linhaValor, 2);
                                            
                                        if ($linhaIndex == 'totalAdRevenue')
                                            $linhaValor = round($linhaValor, 2);
                                            
                                        if ($linhaIndex == 'advertiserAdCost')
                                            $linhaValor = round($linhaValor, 2);
                                            
                                        if ($linhaIndex == 'returnOnAdSpend') {
                                            $linhaValor = round($linhaValor, 2);
                                        }
                                            
                                        if ($linhaIndex == 'advertiserAdCostPerConversion')
                                            $linhaValor = round($linhaValor, 2);
                                            
                                        if ($linhaIndex == 'bounceRate') {
                                            $linhaValor = str_replace('0.', '', round($linhaValor, 2));
                                        }
                                        
                                        $dados['googleads_' . $linhaIndex] = $linhaValor;
                                    }
                                    
                                    $googleads_advertiserAdImpressions       = '';
                                    $googleads_advertiserAdClicks            = '';
                                    $googleads_advertiserAdCostPerConversion = '';
                                    $googleads_conversions                   = '';
                                    
                                    $firstUserGoogleAdsKeyword   = $dados['googleads_firstUserGoogleAdsKeyword'];
                                    $firstUserGoogleAdsadgroupId = $dados['googleads_firstUserGoogleAdsadgroupId'];
                                    
                                    $sql = "SELECT *
                                        FROM analytics_googleads_itens
                                        WHERE 
                                            item_firstUserGoogleAdsKeyword   = '$firstUserGoogleAdsKeyword' AND
                                            item_firstUserGoogleAdsadgroupId = '$firstUserGoogleAdsadgroupId' AND 
                                            item_date                        = '$itemData'
                                        LIMIT 1;";
                                        
                                    $itens = mysqli_query($con, $sql);
                                    
                                    if ($itens) {
                                        $linkValor = mysqli_fetch_array($itens);
                                        if (isset($linkValor['itemID'])) {
                                            $googleads_advertiserAdImpressions       = $linkValor['item_advertiserAdImpressions'];
                                            $googleads_advertiserAdClicks            = $linkValor['item_advertiserAdClicks'];
                                            $googleads_advertiserAdCostPerConversion = $linkValor['item_advertiserAdCostPerConversion'];
                                            $googleads_conversions                   = $linkValor['item_conversions'];
                                        }
                                    }
                                    
                                    $dados['googleads_advertiserAdImpressions']       = $googleads_advertiserAdImpressions;
                                    $dados['googleads_advertiserAdClicks']            = $googleads_advertiserAdClicks;
                                    $dados['googleads_advertiserAdCostPerConversion'] = $googleads_advertiserAdCostPerConversion;
                                    $dados['googleads_conversions']                   = $googleads_conversions;
                                    
                                    $dados['googleadsCriadoEm'] = date('Y-m-d');
                                    $dados['_analyticID']       = $analyticID;
                                
                                    $arrInserts[$posicao][] =  "('" . implode("', '", $dados) . "')";

                                    if (count($arrInserts[$posicao]) > 300)
                                        $posicao++;

                                    if (count($arrCampos) == 0)
                                        $tabelaCampos = '(' . implode(', ', array_keys($dados)) . ')';
                                }
                            }

                            foreach ($arrInserts as $insertValor) {
                                $sql = "INSERT INTO 
                                    analytics_googleads " . $tabelaCampos . "
                                VALUES
                                    " . implode(', ', $insertValor);

                                mysqli_query($con, $sql);
                            }

                            break 2;
                        }
                    }

                    $tempoFinal = microtime(true); 
                    $tempoSoma  = ($tempoFinal - $tempoInicio); 

                    if ($tempoSoma > 60) {
                        $tempoSoma = (int) ($tempoSoma / 60) . ' mins';
                    } else {
                        $tempoSoma = (int) $tempoSoma . ' segs';
                    } ?>
                        
                    <p>Tempo de execução do site: <?php echo $tempoSoma; ?></p>
                    <p>Atualizando página em 2 segundos.</p>
                    
                    <?php 
                    if (!isset($_GET['geral'])) { ?>
                        <script>
                            setTimeout(function(){
                               window.location.reload(1);
                            }, 2000);
                        </script>
                        <?php
                    }
                }
            }
            
        } else { ?>
            <p><a href="<?php echo site_url('cron/analytics_googleads.php?manual&iniciar'); ?>">Iniciar processo (7 dias)</a></p>
            <p><a href="<?php echo site_url('cron/analytics_googleads.php?manual&iniciar&ontem'); ?>">Iniciar processo (Ontem)</a></p>
            <?php
        }
        
        exit;
        
    } else {
        
        $dataOntem = date('Y-m-d', strtotime('-1 day'));
        
        $query = mysqli_query($con, "SELECT *
            FROM analytics
                INNER JOIN contas ON contaID = _contaID
            LIMIT 100;");
        
        if ($query) {
            while ($itemValor = mysqli_fetch_array($query)) { 

                $analyticID       = $itemValor['analyticID'];
                $analyticContaID  = $itemValor['analyticContaID'];
                $contaAccessToken = $itemValor['contaAccessToken'];
                
                mysqli_query($con, "DELETE FROM analytics_googleads 
                    WHERE 
                        _analyticID    = $analyticID AND 
                        googleads_date = '$dataOntem';"); 
        
                mysqli_query($con, "DELETE FROM analytics_googleads_itens 
                    WHERE 
                        _analyticID    = $analyticID AND 
                        googleads_date = '" . date('Ymd', strtotime($dataOntem)) . "';");
                        
                /* continue; */
                
                $itens = analyticsGoogleads($analyticContaID, $contaAccessToken, $dataOntem, $dataOntem);
                
                $json  = (array) $itens['lista2'];
                
                if (count($json) > 0) {
                    $arrTopo  = array();
        
                    if (isset($json['dimensionHeaders'] )) {
                        foreach ($json['dimensionHeaders'] as $itemIndex => $itemValor) {
                            $arrTopo[] = $itemValor['name'];
                        }
                    } 
        
                    if (isset($json['metricHeaders']    )) {
                        foreach ($json['metricHeaders'] as $itemIndex => $itemValor) {
                            $arrTopo[] = $itemValor['name'];
                        }
                    } 
                    
                    $arrLinhas = array();
                    
                    if (isset($json['rows'])) {
                        foreach ($json['rows'] as $linhaIndex => $itemValor) { 
                            $pos = 0;
                            if (isset($itemValor['dimensionValues'] )) {
                                foreach ($itemValor['dimensionValues'] as $itemIndex => $_itemValor) {
                                    $campoNome = $arrTopo[$pos];
                                    
                                    $arrLinhas[$linhaIndex][$campoNome] = $_itemValor['value'];
                                    
                                    $pos++;
                                }
                            } 
                      
                            if (isset($itemValor['metricValues'])) {
                                foreach ($itemValor['metricValues'] as $_itemIndex => $_itemValor) {
                                    $campoNome = $arrTopo[$pos];
                                    
                                    $arrLinhas[$linhaIndex][$campoNome] = $_itemValor['value'];
                                    
                                    $pos++;
                                }
                            }
                        }
                    } 
                    
                    foreach ($arrLinhas as $arrItens) { 
                        $dados = array();
                        
                        $itemID   = '';
                        $itemDate = '';
                        $itemNome = '';
        
                        foreach ($arrItens as $linhaIndex => $linhaValor) {
                            $dados['item_' . $linhaIndex] = $linhaValor;
                            
                            if ($linhaIndex == 'date')
                                $itemDate = $linhaValor;
                            
                            if ($linhaIndex == 'firstUserGoogleAdsCampaignName')
                                $itemNome = $linhaValor;
                        }
                        
                        $dados['itemData']    = date('Y-m-d');
                        $dados['_analyticID'] = $analyticID;
                        
                        insert('analytics_googleads_itens', $dados);
                    }
                }
               
                $json  = (array) $itens['lista1'];
                
                if (count($json) > 0) {
                    $arrTopo  = array();
        
                    if (isset($json['dimensionHeaders'] )) {
                        foreach ($json['dimensionHeaders'] as $itemIndex => $itemValor) {
                            $arrTopo[] = $itemValor['name'];
                        }
                    } 
        
                    if (isset($json['metricHeaders']    )) {
                        foreach ($json['metricHeaders'] as $itemIndex => $itemValor) {
                            $arrTopo[] = $itemValor['name'];
                        }
                    } 
                    
                    $arrLinhas = array();
                    
                    if (isset($json['rows'])) {
                        foreach ($json['rows'] as $linhaIndex => $itemValor) { 
                            $pos = 0;
                            if (isset($itemValor['dimensionValues'] )) {
                                foreach ($itemValor['dimensionValues'] as $itemIndex => $_itemValor) {
                                    $campoNome = $arrTopo[$pos];
                                    
                                    $arrLinhas[$linhaIndex][$campoNome] = $_itemValor['value'];
                                    
                                    $pos++;
                                }
                            } 
                      
                            if (isset($itemValor['metricValues'])) {
                                foreach ($itemValor['metricValues'] as $_itemIndex => $_itemValor) {
                                    $campoNome = $arrTopo[$pos];
                                    
                                    $arrLinhas[$linhaIndex][$campoNome] = $_itemValor['value'];
                                    
                                    $pos++;
                                }
                            }
                        }
                    } 
                    
                    foreach ($arrLinhas as $itemIndex => $arrItens) { 
                        $dados    = array();
                        
                        $itemID    = 0;
                        $itemData  = '';
                        $itemData2 = '';
                        $itemNome  = '';
                        
                        foreach ($arrItens as $linhaIndex => $linhaValor) {
                            
                            if ($linhaIndex == 'date') {
                                $itemData   = $linhaValor;
                                $linhaValor = substr($linhaValor, 0, 4) . '-' . substr($linhaValor, 4, 2) . '-' . substr($linhaValor, 6, 2);
                                $itemData2  = $linhaValor;
                            }
                                
                            if ($linhaIndex == 'firstUserGoogleAdsCampaignName')
                                $itemNome = $linhaValor;
                                
                            if ($linhaIndex == 'screenPageViewsPerSession')
                                $linhaValor = round($linhaValor, 2);
                                
                            if ($linhaIndex == 'bounceRate')
                                $linhaValor = str_replace('0.', '', round($linhaValor, 2));
                                
                            if ($linhaIndex == 'advertiserAdCostPerClick')
                                $linhaValor = round($linhaValor, 2);
                                
                            if ($linhaIndex == 'publisherAdImpressions')
                                $linhaValor = round($linhaValor, 2);
                                
                            if ($linhaIndex == 'totalAdRevenue')
                                $linhaValor = round($linhaValor, 2);
                                
                            if ($linhaIndex == 'advertiserAdCost')
                                $linhaValor = round($linhaValor, 2);
                                
                            if ($linhaIndex == 'returnOnAdSpend') {
                                $linhaValor = round($linhaValor, 2);
                            }
                                
                            if ($linhaIndex == 'advertiserAdCostPerConversion')
                                $linhaValor = round($linhaValor, 2);
                                
                            if ($linhaIndex == 'bounceRate')
                                $linhaValor = str_replace('0.', '', round($linhaValor, 2));
                                
                            $dados['googleads_' . $linhaIndex] = $linhaValor;
                        }
                        
                        $googleads_advertiserAdImpressions       = '';
                        $googleads_advertiserAdClicks            = '';
                        $googleads_advertiserAdCostPerConversion = '';
                        $googleads_conversions                   = '';
                        
                        $firstUserGoogleAdsKeyword   = $dados['googleads_firstUserGoogleAdsKeyword'];
                        $firstUserGoogleAdsadgroupId = $dados['googleads_firstUserGoogleAdsadgroupId'];
                        
                        $sql = "SELECT *
                            FROM analytics_googleads_itens
                            WHERE 
                                item_firstUserGoogleAdsKeyword   = '$firstUserGoogleAdsKeyword' AND
                                item_firstUserGoogleAdsadgroupId = '$firstUserGoogleAdsadgroupId' AND 
                                item_date                        = '$itemData'
                            LIMIT 1;";
                            
                        $itens = mysqli_query($con, $sql);
                        
                        if ($itens) {
                            $linkValor = mysqli_fetch_array($itens);
                            if (isset($linkValor['itemID'])) {
                                $googleads_advertiserAdImpressions       = $linkValor['item_advertiserAdImpressions'];
                                $googleads_advertiserAdClicks            = $linkValor['item_advertiserAdClicks'];
                                $googleads_advertiserAdCostPerConversion = $linkValor['item_advertiserAdCostPerConversion'];
                                $googleads_conversions                   = $linkValor['item_conversions'];
                            }
                        }
                        
                        $dados['googleads_advertiserAdImpressions']       = $googleads_advertiserAdImpressions;
                        $dados['googleads_advertiserAdClicks']            = $googleads_advertiserAdClicks;
                        $dados['googleads_advertiserAdCostPerConversion'] = $googleads_advertiserAdCostPerConversion;
                        $dados['googleads_conversions']                   = $googleads_conversions;
                        
                        $dados['googleadsCriadoEm'] = date('Y-m-d');
                        $dados['_analyticID']       = $analyticID;
                        
                        $retorno = insert('analytics_googleads', $dados);
                        if (!$retorno) {
                            /*
                            echo '<pre>';
                            print_r($insertValor);
                            echo '</pre>'; 
                            */
                            
                            echo 'ERRO: ' . mysqli_error($con) . '<br />';
                        }
                    }
                }
            }
        }
    }
}

/* -------------------------------------- */

function analyticsGoogleads($contaID = '', $access_token = '', $dataInicio = '', $dataFim = '') {
    
    if (empty($dataInicio))
        $dataInicio = date('Y-m-d', strtotime('-5 days'));
        
    if (empty($dataFim))
        $dataFim = date('Y-m-d');
    
    $retorno = array();

    $data = '
        {
          "dateRanges": [{ "startDate": "' . $dataInicio . '", "endDate": "' . $dataFim . '" }],
            "dimensions": [
                {"name": "date"},
                {"name": "firstUserGoogleAdsCampaignName"},
                {"name": "firstUserGoogleAdsadgroupId"},
                {"name": "firstUserGoogleAdsadgroupName"},
                {"name": "firstUserGoogleAdsKeyword"}
            ],
            "metrics": [
                {"name": "totalUsers" }, 
                {"name": "userEngagementDuration"},
                {"name": "screenPageViewsPerSession"},
                {"name": "bounceRate"},
                {"name": "advertiserAdCostPerClick"},
                {"name": "publisherAdImpressions"},
                {"name": "publisherAdClicks"},
                {"name": "advertiserAdCost"},
                {"name": "totalAdRevenue"},
                {"name": "returnOnAdSpend"},  
            ]
        }';
  
    $link = 'https://analyticsdata.googleapis.com/v1beta/properties/' . $contaID . ':runReport' ;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $json = (array) json_decode($result, true);
    $json = array_filter($json);
    
    $retorno_1 = $json;
    
    $data = '
        {
            "dateRanges": [{ "startDate": "' . $dataInicio . '", "endDate": "' . $dataFim . '" }],
            "dimensions": [     
                {"name": "date"},
                {"name": "firstUserGoogleAdsCampaignName"},
                {"name": "firstUserGoogleAdsadgroupId"},
                {"name": "firstUserGoogleAdsadgroupName"},
                {"name": "firstUserGoogleAdsKeyword"}
            ],
            "metrics": [
                {"name": "advertiserAdImpressions"},
                {"name": "advertiserAdClicks"},
                {"name": "advertiserAdCostPerConversion"},
                {"name": "conversions"}   
            ]
        }';
  
    $link = 'https://analyticsdata.googleapis.com/v1beta/properties/' . $contaID . ':runReport' ;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $json = (array) json_decode($result, true);
    $json = array_filter($json); 
    
    $retorno_2 = $json;
    
    return array(
        'lista1' => $retorno_1,
        'lista2' => $retorno_2
    );
}

/* -------------------------------------- */

function analyticsCampanhaLista($itemID = '', $data = '', $dataInicio = '', $dataFinal = '', $_where = array()) {
    global $con;
    
    $where     = array();
    $where[]   = "_analyticID = " . $itemID;
    $dataAtual = date('Y-m-d');

    if ($data == 'CUSTOM') {
        if (validaDataDb($dataInicio) && validaDataDb($dataFinal)) {
            $where[] = "campanha_date between '$dataInicio' and '$dataFinal'";
        } else {
            $where[] = "campanha_date between '" . date('Y-m-d', strtotime('-1 day')) . "' and '" . date('Y-m-d') . "'";
        }

    } else {
        if ($data == 'HOJE') {
            $where[] = "campanha_date between '" . date('Y-m-d', strtotime('-1 day')) . "' and '" . date('Y-m-d') . "'";
        } else if ($data == 'YESTERDAY') {
            $where[] = "campanha_date = '" . date('Y-m-d', strtotime('-1 day')) . "'";
        } else if ($data == 'LAST_7_DAYS') {
            $where[] = "campanha_date >= '" . date('Y-m-d', strtotime('-7 days')) . "' AND campanha_date < '$dataAtual'";
        } else if ($data == 'MONTH_TO_DATE') {
            $where[] = "MONTH(campanha_date) = MONTH(CURRENT_DATE()) AND YEAR(campanha_date) = YEAR(CURRENT_DATE())";
        } else {
            $where[] = "campanha_date between '" . date('Y-m-d', strtotime('-1 day')) . "' and '" . date('Y-m-d') . "'";
        }
    }
    
    $arrNomes = array(
        'date'                         => 'Data',   
        'sessionCampaignName'          => 'Campanha',
        'sessionSourceMedium'          => 'Origem',
        'totalUsers'                   => 'Usuários',       
        'advertiserAdCostPerClick'     => 'Cpc GoogleAds',      
        'publisherAdClicks'            => 'Cliques Ad',     
        'publisherAdImpressions'       => 'Impressões Ad',      
        'advertiserAdCost'             => 'Custo GoogleAds',        
        'totalAdRevenue'               => 'Receita Ad',     
        'returnOnAdSpend'              => 'Roi'
    );

    ob_start();
    
    $where = array_merge($_where, $where);

    $sql = "SELECT campanha_date
        FROM analytics_campanhas
        WHERE 
            " . implode(' AND ', $where) . "
        GROUP BY campanha_date
        ORDER BY campanha_date DESC;";
        
    $queryData = mysqli_query($con, $sql);
    
    if ($queryData) {
        while ($dataValor = mysqli_fetch_array($queryData)) { 
            $data = $dataValor['campanha_date'];  ?>
            
            <div class="">
                <table class="table table-bordered tabelaDados" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <?php 
                            foreach ($arrNomes as $itemNome) {
                                echo '<th><small>' . $itemNome . '</small></th>'; 
                            } ?>
                        </tr>
                    </thead>
                
                    <tbody>
                        <?php 
                        $advertiserAdCostTotal = 0;
                        $totalUsersTotal = 0;
                        $publisherAdImpressionsTotal = 0;
                        $publisherAdClicksTotal = 0;
                        $totalAdRevenueTotal = 0;
                        $newUsersTotal = 0;
                        
                        $__where   = array();
                        $__where[] = "campanha_date   = '$data'";
                        $__where[] = "_analyticID = " . $itemID;
                        
                        $__where = array_merge($_where, $__where);
    
                        $query = mysqli_query($con, "SELECT *
                            FROM analytics_campanhas
                            WHERE
                                " . implode(' AND ', $__where) . "
                            LIMIT 1000;");
                        
                        if ($query) {
                            while ($itemValor = mysqli_fetch_array($query)) { 
                            
                                $total          = $itemValor['campanhaLinksTotal'];
                                $googleadsTotal = $itemValor['campanhaGoogleadsTotal'];
                                $paisTotal      = $itemValor['campanhaPaisTotal']; ?>
                            
                                <tr>
                                    <?php 
                                    foreach ($arrNomes as $itemIndex => $itemNome) {
                                        $_itemValor = $itemValor['campanha_' . $itemIndex];
                                        
                                        if ($itemIndex == 'date')
                                            $_itemValor = '<i class="fa fa-calendar"></i> ' . date('d/m/Y', strtotime($_itemValor));
                                            
                                        if ($itemIndex == 'sessionCampaignName') {
                                            $campanhaNome = $_itemValor;
                                            
                                            $_itemValor = $campanhaNome . '<br /><a href="javascript:;" onclick="modalLinks(\'' . $campanhaNome . '\')"><span class="badge badge-danger">Links</span></a>';
                                            
                                            if ($_GET['pg'] != 'gestor')
                                                $_itemValor .= $googleadsTotal > 0 ? ' <a href="javascript:;" onclick="modalCampanhaGoogleads(\'' . $campanhaNome . '\')"><span class="badge badge-success">GoogleAds</span></a>' : '';
                                            
                                            if ($paisTotal > 0)
                                                $_itemValor .= ' <a href="javascript:;" onclick="modalCampanhaPais(\'' . $campanhaNome . '\', ' . $itemID . ')"><span class="badge badge-warning">País</span></a>';
                                        }
                                            
                                        if ($itemIndex == 'totalUsers') {
                                            $totalUsersTotal = $totalUsersTotal + $_itemValor;
                                            
                                            $_itemValor = '<i class="fa fa-users"></i> ' . $_itemValor;
                                        }
                                        
                                        if ($itemIndex == 'screenPageViewsPerSession') {
                                            $_itemValor = round($_itemValor, 2);
                                        }
                                        
                                        if ($itemIndex == 'publisherAdImpressions')
                                            $publisherAdImpressionsTotal = $publisherAdImpressionsTotal + $_itemValor;
                                            
                                        if ($itemIndex == 'publisherAdClicks')
                                            $publisherAdClicksTotal = $publisherAdClicksTotal + $_itemValor;
                                            
                                        if ($itemIndex == 'advertiserAdCost') {
                                            if (empty($_itemValor))
                                                $_itemValor = 0;

                                            $advertiserAdCostTotal = $advertiserAdCostTotal + $_itemValor;

                                            $_itemValor = getConfig('moeda_simbolo') . ' ' . fmoney($_itemValor);
                                        }
                                            
                                        if ($itemIndex == 'advertiserAdCostPerClick')
                                            $_itemValor = getConfig('moeda_simbolo') . ' ' . fmoney($_itemValor);
                                            
                                        if ($itemIndex == 'bounceRate')
                                            $_itemValor = $_itemValor . '%';
                                        
                                        if ($itemIndex == 'totalAdRevenue') {
                                            $totalAdRevenueTotal = $totalAdRevenueTotal + $_itemValor;
                                            
                                            $_itemValor = getConfig('moeda_simbolo') . ' ' . fmoney($_itemValor);
                                        }
                                            
                                        if ($itemIndex == 'averageSessionDuration')
                                            $_itemValor = '<i class="fa fa-clock-o"></i> ' . $_itemValor;
                                            
                                        if ($itemIndex == 'newUsers') {
                                            $newUsersTotal = $newUsersTotal + $_itemValor;
                                            
                                            $_itemValor = '<i class="fa fa-user-plus"></i> ' . $_itemValor;
                                        }
                                        
                                        if ($itemIndex == 'returnOnAdSpend') {
                                            $_itemValor = (float) $_itemValor;
                                                
                                            if ($_itemValor > 2.00) { 
                                                $linhaTipo = 'destaqueLinha1';
                                                
                                                $_itemValor = '<span class="label label-success">' . $_itemValor . '</span>';
                                            } else if ($_itemValor < 0.90) {
                                                $linhaTipo = 'destaqueLinha2';
                                                
                                                $_itemValor = '<span class="label label-danger">' . $_itemValor . '</span>';
                                            } else if ($_itemValor < 1.11) { 
                                                $linhaTipo = 'destaqueLinha3';
                                                
                                                $_itemValor = '<span class="label label-warning">' . $_itemValor . '</span>';
                                            } else if ($_itemValor < 2.00) { 
                                                $linhaTipo = 'destaqueLinha4';
                                                
                                                $_itemValor = '<span class="label label-info">' . $_itemValor . '</span>';
                                            }
                                        }
                                        
                                        echo '<td>' . $_itemValor . '</td>'; 
                                    } ?>
                                </tr>
                                <?php 
                            }
                        }
                        
                        if ($_GET['pg'] != 'gestor') { ?>
                            <tr style="background-color: #ededed;">
                                <?php 
                                foreach ($arrNomes as $itemIndex => $itemNome) {
                                    $itemValor = '';
                                    
                                    if ($itemIndex == 'totalUsers') {
                                        $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $totalUsersTotal . '</strong>';
                                        
                                    } else if ($itemIndex == 'publisherAdImpressions') {
                                        $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $publisherAdImpressionsTotal . '</strong>';
                                        
                                    } else if ($itemIndex == 'advertiserAdCost') {
                                        $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . getConfig('moeda_simbolo') . ' ' . fmoney($advertiserAdCostTotal) . '</strong>';
                                        
                                    } else if ($itemIndex == 'publisherAdClicks') {
                                        $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $publisherAdClicksTotal . '</strong>';
                                    
                                    } else if ($itemIndex == 'totalAdRevenue') {
                                        $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . getConfig('moeda_simbolo') . ' ' . fmoney($totalAdRevenueTotal) . '</strong>';
                                        
                                    } else if ($itemIndex == 'newUsers') {
                                        $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $newUsersTotal . '</strong>';
                                    }
                                        
                                    echo '<td>' . $itemValor . '</td>';
                                } ?>
                            </tr>
                            <?php 
                        } ?>
                        
                    </tbody>
                </table>
            </div>
            <?php 
        }
    }

    $retorno = ob_get_contents();
    ob_end_clean();
    
    return $retorno;
}

function analyticsLinksLista($itemID = '', $data = '', $dataInicio = '', $dataFinal = '') {
    global $con;

    $where   = array();
    $where[] = "_analyticID = " . $itemID;

    if ($data == 'CUSTOM') {
        if (validaDataDb($dataInicio) && validaDataDb($dataFinal)) {
            $where[] = "link_date between '$dataInicio' and '$dataFinal'";
        } else {
            $where[] = "link_date between '" . date('Y-m-d', strtotime('-1 day')) . "' and '" . date('Y-m-d') . "'";
        }

    } else {
        if ($data == 'HOJE') {
            $where[] = "link_date between '" . date('Y-m-d', strtotime('-1 day')) . "' and '" . date('Y-m-d') . "'";
        } else if ($data == 'YESTERDAY') {
            $where[] = "link_date = '" . date('Y-m-d', strtotime('-1 day')) . "'";
        } else if ($data == 'LAST_7_DAYS') {
            $where[] = "link_date >= '" . date('Y-m-d', strtotime('-7 days')) . "' AND item_date < '$dataAtual'";
        } else if ($data == 'MONTH_TO_DATE') {
            $where[] = "MONTH(link_date) = MONTH(CURRENT_DATE()) AND YEAR(link_date) = YEAR(CURRENT_DATE())";
        } else {
            $where[] = "link_date between '" . date('Y-m-d', strtotime('-1 day')) . "' and '" . date('Y-m-d') . "'";
        }
    }
    
    $arrNomes = array(
        'date'                      => 'Data',  
        'pagePath'                  => 'Url',
        'sessionCampaignName'       => 'Campanha',
        'totalUsers'                => 'Usurios',
        'screenPageViewsPerSession' => 'Exibiões por sessão',
        'averageSessionDuration'    => 'Duração da sessão',
        'bounceRate'                => 'Rejeição',
        'publisherAdClicks'         => 'Cliques Ad',
        'publisherAdImpressions'    => 'Impressões Ad',
        'totalAdRevenue'            => 'Receita Ad'
    );

    ob_start();

    $queryData = mysqli_query($con, "SELECT link_date
        FROM analytics_links
        WHERE 
            " . implode(' AND ', $where) . "
        GROUP BY link_date
        ORDER BY link_date DESC;");

    if ($queryData) {
        while ($dataValor = mysqli_fetch_array($queryData)) { 
            $data = $dataValor['link_date'];  ?>
            
            <div class="">
                <table class="table table-bordered tabelaDados" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <?php 
                            foreach ($arrNomes as $itemNome) {
                                echo '<th><small>' . $itemNome . '</small></th>'; 
                            } ?>
                        </tr>
                    </thead>
                
                    <tbody>
                        <?php 
                        $totalUsersTotal = 0;
                        $publisherAdImpressionsTotal = 0;
                        $publisherAdClicksTotal = 0;
                        $totalAdRevenueTotal = 0;
                        $newUsersTotal = 0;
    
                        $query = mysqli_query($con, "SELECT *
                            FROM analytics_links
                            WHERE
                                link_date   = '$data' AND
                                _analyticID = " . $itemID . "
                            LIMIT 1000;");
                        
                        if ($query) {
                            while ($itemValor = mysqli_fetch_array($query)) { ?>
                                <tr>
                                    <?php 
                                    foreach ($arrNomes as $itemIndex => $itemNome) {
                                        $_itemValor = $itemValor['link_' . $itemIndex];
                                        
                                        if ($itemIndex == 'date')
                                            $_itemValor = '<i class="fa fa-calendar"></i> ' . date('d/m/Y', strtotime($_itemValor));
                                            
                                        if ($itemIndex == 'totalUsers') {
                                            $totalUsersTotal = $totalUsersTotal + $_itemValor;
                                            
                                            $_itemValor = '<i class="fa fa-users"></i> ' . $_itemValor;
                                        }
                                        
                                        if ($itemIndex == 'screenPageViewsPerSession') {
                                            $_itemValor = round($_itemValor, 2);
                                        }
                                        
                                        if ($itemIndex == 'publisherAdImpressions')
                                            $publisherAdImpressionsTotal = $publisherAdImpressionsTotal + $_itemValor;
                                            
                                        if ($itemIndex == 'publisherAdClicks')
                                            $publisherAdClicksTotal = $publisherAdClicksTotal + $_itemValor;
                                        
                                        if ($itemIndex == 'totalAdRevenue') {
                                            $totalAdRevenueTotal = $totalAdRevenueTotal + $_itemValor;
                                            
                                            $_itemValor = getConfig('moeda_simbolo') . ' ' . fmoney($_itemValor);
                                        }
                                            
                                        if ($itemIndex == 'averageSessionDuration')
                                            $_itemValor = '<i class="fa fa-clock-o"></i> ' . $_itemValor;
                                            
                                        if ($itemIndex == 'newUsers') {
                                            $newUsersTotal = $newUsersTotal + $_itemValor;
                                            
                                            $_itemValor = '<i class="fa fa-user-plus"></i> ' . $_itemValor;
                                        }
                                        
                                        echo '<td>' . $_itemValor . '</td>'; 
                                    } ?>
                                </tr>
                                <?php 
                            }
                        } ?>
                        
                        <tr style="background-color: #ededed;">
                            <?php 
                            foreach ($arrNomes as $itemIndex => $itemNome) {
                                $itemValor = '';
                                
                                if ($itemIndex == 'totalUsers') {
                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $totalUsersTotal . '</strong>';
                                    
                                } else if ($itemIndex == 'publisherAdImpressions') {
                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $publisherAdImpressionsTotal . '</strong>';
                                    
                                } else if ($itemIndex == 'publisherAdClicks') {
                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $publisherAdClicksTotal . '</strong>';
                                
                                } else if ($itemIndex == 'totalAdRevenue') {
                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . getConfig('moeda_simbolo') . ' ' . fmoney($totalAdRevenueTotal) . '</strong>';
                                    
                                } else if ($itemIndex == 'newUsers') {
                                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $newUsersTotal . '</strong>';
                                }
                                    
                                echo '<td>' . $itemValor . '</td>';
                            } ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php 
        }
    }

    $retorno = ob_get_contents();
    ob_end_clean();
    
    return $retorno;
}

/* -------------------------------------- */

function floatVazio($var = '') {
    return (empty($var) || (is_numeric($var) && (float) $var == 0));
}


/* -------------------------------------- */

function analyticsGoogleadsPaisLista($itemID = '', $data = '', $dataInicio = '', $dataFinal = '', $firstUserCampaignName = '', $etapa = '', $where = array(), $_conteudoID = '', $agrupar = true, $roiInicio = '', $roiFinal = '') {
    global $con;
    
    $analyticID = $itemID;
    $etapa      = (int) $etapa;
    $dataAtual  = date('Y-m-d');
    $roiInicio  = (float) $roiInicio;
    $roiFinal   = (float) $roiFinal;
    
    $where   = (array) $where;
    $where[] = "_analyticID = " . $itemID;
    
    if (!empty($firstUserCampaignName))
        $where[] = "pais_firstUserCampaignName = '$firstUserCampaignName'";

    if ($data == 'CUSTOM') {
        if (validaDataDb($dataInicio) && validaDataDb($dataFinal)) {
            $where[] = "pais_date between '$dataInicio' and '$dataFinal'";
        } else {
            $where[] = "pais_date between '" . date('Y-m-d', strtotime('-1 day')) . "' and '" . date('Y-m-d') . "'";
        }

    } else {
        if ($data == 'HOJE') {
            $where[] = "pais_date = '" . date('Y-m-d') . "'";
        } else if ($data == 'YESTERDAY') {
            $where[] = "pais_date = '" . date('Y-m-d', strtotime('-1 day')) . "'";
        } else if ($data == 'LAST_7_DAYS') {
            $where[] = "pais_date >= '" . date('Y-m-d', strtotime('-7 days')) . "' AND pais_date < '$dataAtual'";
        } else if ($data == 'MONTH_TO_DATE') {
            $where[] = "MONTH(pais_date) = MONTH(CURRENT_DATE()) AND YEAR(pais_date) = YEAR(CURRENT_DATE())";
        } else {
            $where[] = "pais_date = '" . date('Y-m-d', strtotime('-1 day')) . "' ";
        }
    }
    
    if ($data == 'YESTERDAY') {
        $dataInicio = date('Y-m-d', strtotime('-1 day'));
        $dataFinal  = date('Y-m-d', strtotime('-1 day'));
    }
    
    if ($data == 'LAST_7_DAYS') {
        $dataInicio = date('Y-m-d', strtotime('-7 day'));
        $dataFinal  = date('Y-m-d', strtotime('-1 day'));
    }
    
    if ($data == 'MONTH_TO_DATE') {
        $dataInicio = date('Y-m-01');
        $dataFinal  = date('Y-m-d', strtotime('-1 day'));
    }
    
    $arrNomes = analyticsGoogleadsPaisTopo();
    
    ob_start();
    
    if ($etapa == 0 || $etapa == 1) {
        unset($arrNomes['date']);
        unset($arrNomes['city']);
        unset($arrNomes['cityId']);
        unset($arrNomes['firstUserGoogleAdsKeyword']);
        unset($arrNomes['region']);
        unset($arrNomes['firstUserGoogleAdsAdGroupId']);
        unset($arrNomes['firstUserCampaignId']);
        unset($arrNomes['firstUserCampaignName']);
    }
    
    if ($etapa == 2) {

        unset($arrNomes['date']);
        unset($arrNomes['language']);
        unset($arrNomes['firstUserCampaignName']);
        unset($arrNomes['firstUserCampaignId']);
        unset($arrNomes['firstUserGoogleAdsAdGroupId']);
        unset($arrNomes['firstUserGoogleAdsKeyword']);
        unset($arrNomes['country']);
        unset($arrNomes['region']);
        
        if (!floatVazio($roiFinal))
            $arrNomes['lance'] = 'Lance';
        
        $arrNomes['custo'] = 'Custo';
        $arrNomes['roi']   = 'Roi';
    }
    
    if ($etapa == 4) {
        unset($arrNomes['date']);
        unset($arrNomes['cityId']);
        unset($arrNomes['firstUserCampaignName']);
        unset($arrNomes['firstUserCampaignId']);
        unset($arrNomes['firstUserGoogleAdsAdGroupId']);
        unset($arrNomes['country']);
    }
    
    if ($etapa == 10) {
        unset($arrNomes['date']);
        unset($arrNomes['cityId']);
        unset($arrNomes['country']);
        unset($arrNomes['city']);
        unset($arrNomes['firstUserGoogleAdsAdGroupId']);
        unset($arrNomes['firstUserCampaignId']);
        unset($arrNomes['firstUserCampaignName']);
    }
    
    if ($etapa == 11) {
        unset($arrNomes['date']);
        unset($arrNomes['cityId']);
        unset($arrNomes['country']);
        unset($arrNomes['region']);
        unset($arrNomes['firstUserGoogleAdsAdGroupId']);
        unset($arrNomes['firstUserCampaignId']);
        unset($arrNomes['firstUserCampaignName']);
    }

    $groupBy = 'pais_country';
    if ($etapa == 2) {
        $groupBy = 'pais_city';
    }
        
    if ($etapa == 4)
        $groupBy = 'pais_firstUserGoogleAdsKeyword';
        
    if ($etapa == 10 || $etapa == 11)
        $groupBy = 'pais_firstUserGoogleAdsKeyword';
        
    $sql = "SELECT *, 
            SUM(pais_totalUsers) AS pais_totalUsers,
            SUM(pais_userEngagementDuration) AS pais_userEngagementDuration,
            SUM(pais_screenPageViewsPerSession) AS pais_screenPageViewsPerSession,
            SUM(pais_bounceRate) AS pais_bounceRate,
            SUM(pais_publisherAdImpressions) AS pais_publisherAdImpressions,
            SUM(pais_publisherAdClicks) AS pais_publisherAdClicks,
            SUM(pais_totalAdRevenue) AS pais_totalAdRevenue
        FROM analytics_pais
        WHERE
            " . implode(' AND ', $where) . " 
        GROUP BY $groupBy
        ORDER BY pais_totalAdRevenue DESC
        LIMIT 100000;";
        
    $query = mysqli_query($con, $sql);
    
    if ($query) {
        while ($itemValor = mysqli_fetch_array($query)) { 
            $conteudoListaID = rand(10000, 99999);

            if (empty($_conteudoID)) {
                $conteudoID = rand(10000, 99999); 
            } else {
                $conteudoID = $_conteudoID; 
            }
            
            $custoValor      = 0;
            $mostraContneudo = true;
            
            ob_start();
                
            foreach ($arrNomes as $itemIndex => $itemNome) {
                $_itemValor = '';
                if (isset($itemValor['pais_' . $itemIndex]))
                    $_itemValor = $itemValor['pais_' . $itemIndex];

                if ($etapa == 2) {
                    if ($itemIndex == 'custo') {
                        
                        $custoValor = getCustoCidade($analyticID, $itemValor['pais_firstUserCampaignName'], $itemValor['pais_city'], $dataInicio, $dataFinal);
                        $_itemValor = getConfig('moeda_simbolo') . ' ' . fmoney($custoValor);
                    }
                    
                    if ($itemIndex == 'roi') { 
                        if ($itemValor['pais_totalAdRevenue'] > 0) {
                            if ($custoValor > 0) {
                                $_itemValor = $itemValor['pais_totalAdRevenue'] / $custoValor; 
                            } else {
                                $_itemValor = $itemValor['pais_totalAdRevenue']; 
                            }
                        } else {
                            $_itemValor = '';
                        }
                        
                        $_itemValor = (float) $_itemValor;
                        
                        if (!floatVazio($roiInicio) && !floatVazio($roiFinal)) {
                            if ($_itemValor < $roiInicio || $_itemValor > $roiFinal) {
                                $mostraContneudo = false;
                            }
                        }
                        
                        if ($_itemValor >= 2.00) { 
                            $linhaTipo = 'destaqueLinha1';
                            
                            $_itemValor = '<span class="label label-success">' . fmoney($_itemValor) . '</span>';
                        } else if ($_itemValor < 0.90) {
                            $linhaTipo = 'destaqueLinha2';
                            
                            $_itemValor = '<span class="label label-danger">' . fmoney($_itemValor) . '</span>';
                        } else if ($_itemValor < 1.11) { 
                            $linhaTipo = 'destaqueLinha3';
                            
                            $_itemValor = '<span class="label label-warning">' . fmoney($_itemValor) . '</span>';
                        } else if ($_itemValor < 2.00) { 
                            $linhaTipo = 'destaqueLinha4';
                            
                            $_itemValor = '<span class="label label-info">' . fmoney($_itemValor) . '</span>';
                        }
                    }
                }

                if ($itemIndex == 'date')
                    $_itemValor = '<i class="fa fa-calendar"></i> ' . date('d/m/Y', strtotime($_itemValor));
                    
                if ($itemIndex == 'totalUsers') {
                    $totalUsersTotal = $totalUsersTotal + $_itemValor;
                    
                    $_itemValor = '<i class="fa fa-users"></i> ' . $_itemValor;
                }
                
                if ($itemIndex == 'screenPageViewsPerSession') {
                    $_itemValor = round($_itemValor, 2);
                }
                
                if ($itemIndex == 'city') {
                    if ($etapa == 2) {
                        $_itemValor = '
                            <i class="fa fa-list"></i> ' . $_itemValor . ' <a class="tabelaBataoListaCompleta" href="javascript:;" onclick="mostraPaisItensListaCidade(\'' . $firstUserCampaignName . '\', \'' . $itemValor['pais_country'] . '\', \'' . $itemValor['pais_region'] . '\', \'' . $_itemValor . '\', \'' . $analyticID . '\', \'' . $conteudoListaID . '\', \'' . $dataInicio . '\', \'' . $dataFinal . '\', ' . $roiInicio . ', ' . $roiFinal . ')" title="' . $_itemValor . '" title="Ver mais"><i class="fa fa-plus"></i></a>';
                    }
                }
                
                if ($itemIndex == 'country') {
                    if ($etapa == 0 || $etapa == 1) {
                        $_itemValor = '
                            <a href="javascript:;" onclick="mostraPaisItensEtapa1(\'' . $firstUserCampaignName . '\', \'' . $_itemValor . '\', \'' . $conteudoID . '\', \'' . $analyticID . '\', \'' . $dataInicio . '\', \'' . $dataFinal . '\', ' . $roiInicio . ', ' . $roiFinal . ')" title="Ver mais" class="tabelaBataoLista">
                                <i class="fa fa-list"></i> ' . $_itemValor . '
                            </a>';
                    }
                }
                
                if ($itemIndex == 'region') {
                    $_itemValor = str_replace('State of ', '', $_itemValor);
                    
                    if ($etapa == 2) {
                        $_itemValor = '
                            <a href="javascript:;" onclick="mostraPaisItensEtapa2(\'' . $firstUserCampaignName . '\', \'' . $itemValor['pais_country'] . '\', \'' . $_itemValor . '\', \'' . $analyticID . '\', \'' . $conteudoID . '\', \'' . $dataInicio . '\', \'' . $dataFinal . '\', ' . $roiInicio . ', ' . $roiFinal . ')" title="' . $_itemValor . '" class="tabelaBataoLista">
                                <i class="fa fa-list"></i> ' . $_itemValor . '
                            </a> <a class="tabelaBataoListaCompleta" href="javascript:;" onclick="mostraPaisItensListaEstado(\'' . $firstUserCampaignName . '\', \'' . $itemValor['pais_country'] . '\', \'' . $_itemValor . '\', \'' . $analyticID . '\', \'' . $conteudoListaID . '\', \'' . $dataInicio . '\', \'' . $dataFinal . '\', ' . $roiInicio . ', ' . $roiFinal . ')" title="' . $_itemValor . '" title="Ver mais"><i class="fa fa-plus"></i></a>';
                    }
                }
                
                if ($itemIndex == 'firstUserGoogleAdsKeyword') {
                    if ($etapa == 4) {
                       $_itemValor = '
                            <a href="javascript:;" onclick="mostraPaisItensEtapa4(\'' . $firstUserCampaignName . '\', \'' . $itemValor['pais_country'] . '\', \'' . $itemValor['pais_region'] . '\', \'' . $itemValor['city'] . '\',\'' . $_itemValor . '\', \'' . $analyticID . '\', \'' . $conteudoID . '\', ' . $roiInicio . ', ' . $roiFinal . ')" title="' . $_itemValor . '" class="tabelaBataoLista">
                                <i class="fa fa-list"></i> ' . $_itemValor . '
                            </a> <a href="" title=""><i class="fa fa-plus"></i></a>';
                    }
                }
                
                if ($itemIndex == 'publisherAdImpressions')
                    $publisherAdImpressionsTotal = $publisherAdImpressionsTotal + $_itemValor;
                    
                if ($itemIndex == 'publisherAdClicks')
                    $publisherAdClicksTotal = $publisherAdClicksTotal + $_itemValor;
                    
                if ($itemIndex == 'advertiserAdCost') {
                    if (empty($_itemValor))
                        $_itemValor = 0;

                    $advertiserAdCostTotal = $advertiserAdCostTotal + $_itemValor;

                    $_itemValor = getConfig('moeda_simbolo') . ' ' . fmoney($_itemValor);
                }
                    
                if ($itemIndex == 'advertiserAdCostPerClick')
                    $_itemValor = getConfig('moeda_simbolo') . ' ' . fmoney($_itemValor);
                    
                if ($itemIndex == 'bounceRate')
                    $_itemValor = $_itemValor . '%';
                    
                if ($itemIndex == 'totalAdRevenue') {
                    $totalAdRevenueTotal = $totalAdRevenueTotal + $_itemValor;
                    
                    $_itemValor = getConfig('moeda_simbolo') . ' ' . fmoney($_itemValor);
                }
                    
                if ($itemIndex == 'averageSessionDuration')
                    $_itemValor = '<i class="fa fa-clock-o"></i> ' . $_itemValor;
                    
                if ($itemIndex == 'newUsers') {
                    $newUsersTotal = $newUsersTotal + $_itemValor;
                    
                    $_itemValor = '<i class="fa fa-user-plus"></i> ' . $_itemValor;
                }
                
                if ($itemIndex == 'returnOnAdSpend') {
                    $_itemValor = (float) $_itemValor;
                        
                    if ($_itemValor >= 2.00) { 
                        $linhaTipo = 'destaqueLinha1';
                        
                        $_itemValor = '<span class="label label-success">' . $_itemValor . '</span>';
                    } else if ($_itemValor < 0.90) {
                        $linhaTipo = 'destaqueLinha2';
                        
                        $_itemValor = '<span class="label label-danger">' . $_itemValor . '</span>';
                    } else if ($_itemValor < 1.11) { 
                        $linhaTipo = 'destaqueLinha3';
                        
                        $_itemValor = '<span class="label label-warning">' . $_itemValor . '</span>';
                    } else if ($_itemValor < 2.00) { 
                        $linhaTipo = 'destaqueLinha4';
                        
                        $_itemValor = '<span class="label label-info">' . $_itemValor . '</span>';
                    }
                }
                
                if (!floatVazio($roiFinal)) {
                    if ($etapa == 2) {
                        if ($itemIndex == 'lance') {
                            $custoValor = getCustoCidade($analyticID, $itemValor['pais_firstUserCampaignName'], $itemValor['pais_city'], $dataInicio, $dataFinal);
                            
                            $cidadeID = $itemValor['pais_cityId'];
                            
                            $valor = 0;
                            if ($itemValor['pais_totalAdRevenue'] > 0) {
                                if ($custoValor > 0) {
                                    $valor = $itemValor['pais_totalAdRevenue'] / $custoValor; 
                                } else {
                                    $valor = $itemValor['pais_totalAdRevenue']; 
                                }
                            }
                            
                           if ($valor >= 2.00) { 
                                $lance = '1.15';
                            } else if ($valor < 0.51) {
                                $lance = '0.50';
                            } else if ($valor < 0.91) {
                                $lance = '0.70';
                            } else if ($valor < 1.12) { 
                                $lance = '0.80';
                            } else if ($valor < 2.00) { 
                                $lance = '0.90';
                            }


                            if ($lance > 0.30) {
                                $camposClass = 'campoDanger';
                            } else {
                                $camposClass = 'campoSuccess';
                            }
                            
                            $_itemValor = '<input type="text" class="form-control ' . $camposClass . '" name="campanha[' . $cidadeID . ']" value="' . $lance . '" style="width: 80px;" />';
                        }
                    }
                }
                
                echo '<td>' . $_itemValor . '</td>'; 
            }
            
            $resultado = ob_get_contents();
            ob_end_clean();
            
            if ($mostraContneudo) { ?>
            
                <tr><?php echo $resultado ?></tr>
                
                <?php 
                if ($etapa > 1)
                    echo '<tr style="background-color: #8b8b8b;"><td id="conteudo-' . $conteudoListaID . '" colspan="' . count($arrNomes) . '" style="display: none;"></td></tr>';
    
                if (($etapa == 1) || ($etapa == ''))
                    echo '<tr style="background-color: #8b8b8b;"><td id="conteudo-' . $conteudoID . '" colspan="' . count($arrNomes) . '" style="display: none;"></td></tr>';
            }
        }
    }
    
    if (($etapa == 10) || ($etapa == 11)) { ?>
        <tr style="background-color: #ededed;">
            <?php 
            foreach ($arrNomes as $itemIndex => $itemNome) {
                $itemValor = '';
                
                if ($itemIndex == 'totalUsers') {
                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $totalUsersTotal . '</strong>';
                    
                } else if ($itemIndex == 'publisherAdImpressions') {
                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $publisherAdImpressionsTotal . '</strong>';
                    
                } else if ($itemIndex == 'publisherAdClicks') {
                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $publisherAdClicksTotal . '</strong>';
                
                } else if ($itemIndex == 'totalAdRevenue') {
                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . getConfig('moeda_simbolo') . ' ' . fmoney($totalAdRevenueTotal) . '</strong>';
                    
                } else if ($itemIndex == 'newUsers') {
                    $itemValor = '<small>' . $arrNomes[$itemIndex] . '</small><br /><strong>' . $newUsersTotal . '</strong>';
                }
                    
                echo '<td>' . $itemValor . '</td>';
            } ?>
        </tr>
        <?php
    }

    $retorno = ob_get_contents();
    ob_end_clean();
    
    return $retorno;
}

/* -------------------------------------- */

function analyticsPaisTotal($campanhaNome = '', $analyticID = 0) {
    global $con;
    
    $total = 0;
    
    $query = mysqli_query($con, "SELECT COUNT(*) AS total
        FROM analytics_pais
        WHERE
            pais_firstUserCampaignName = '$campanhaNome' AND 
            _analyticID = $analyticID 
        LIMIT 1;");
    
    if ($query) {
        $itemValor = mysqli_fetch_array($query);
        if (isset($itemValor['total'])) {
            $total = $itemValor['total'];
        }
    }
    
    return $total;
}

function analyticsGestorPaisTopo() {
    return array(
        'date'                      => 'Data',  
        'sessionCampaignName'       => 'Campanha',  
        'country'                   => 'País',  
        'sessionSource'             => 'Origem',    
        'sessionMedium'             => 'Session',   
        'sessionManualTerm'         => 'Termo', 
        'totalUsers'                => 'Usurios',   
        'averageSessionDuration'    => 'Duração/sesso', 
        'screenPageViewsPerSession' => 'Exibições/sessão',  
        'bounceRate'                => 'Rejeição',  
        'publisherAdClicks'         => 'Cliques Ad',    
        'publisherAdImpressions'    => 'Impressões Ad',
        'totalAdRevenue'            => 'Receita Ad',
    );
}

function analyticsGoogleadsPaisTopo() {
    return array(
        'date'                        => 'Data',
        'firstUserCampaignName'       => 'Campanha',
        'firstUserCampaignId'         => 'Campanha Id',
        'firstUserGoogleAdsAdGroupId' => 'Id do Grupo',
        'firstUserGoogleAdsKeyword'   => 'Palavras Chaves',
        'city'                        => 'Cidade',
        'country'                     => 'País',
        'region'                      => 'Estado',
        'cityId'                      => 'Cidade ID',   
        'totalUsers'                  => 'totalUsers', 
        'userEngagementDuration'      => 'Duração/sessão', 
        'screenPageViewsPerSession'   => 'Exibições/sessão',  
        'bounceRate'                  => 'Rejeição',  
        'publisherAdImpressions'      => 'Impresses Ad', 
        'publisherAdClicks'           => 'Cliques Ad', 
        'totalAdRevenue'              => 'Receita Ad'
    ); 
}

function getCustoCidade($analyticID = 0, $campanhaNome = '', $custoCidade = '', $dataInicio = '', $dataFinal = '') {
    global $con;
    
    $custoCidade = trim(str_replace('Department', '', $custoCidade));
    
    $retorno = '';
    
    $sql = "SELECT *
        FROM analytics_googleads_custo_cidades
        WHERE
            custoCampanha    = '$campanhaNome' AND 
            custoCidade      = '$custoCidade' AND
            custoDataInicio >= '$dataInicio' AND 
            custoDataInicio <= '$dataFinal' AND 
            _analyticID      = $analyticID
        LIMIT 1;";
        
    $query = mysqli_query($con, $sql);
    
    if ($query) {
        $itemValor = mysqli_fetch_array($query);
        if (isset($itemValor['custoValor'])) {
            $retorno = $itemValor['custoValor'];
        }
    }
    
    return $retorno;
}

function saudacao() {
    $hora = date('H');
    if ($hora >= 6 && $hora <= 12)
        return 'Bom dia';
    else if ( $hora > 12 && $hora <=18  )
        return 'Boa tarde';
    else
        return 'Boa noite';
}

/* -------------------------------------- */

function analyticsGestorPais($contaID = '', $access_token = '', $dataInicio = '', $dataFim = '') {
    $totalRevenueIndex = 0;
    $publisherAdImpressionsIndex = 0;
    
    if (empty($dataInicio))
        $dataInicio = date('Y-m-d', strtotime('-3 days'));
        
    if (empty($dataFim))
        $dataFim = date('Y-m-d');
        
    // {"name": "sessionSource"},
        
    $data = '
        {
            "dateRanges": [{ "startDate": "' . $dataInicio . '", "endDate": "' . $dataFim . '" }],
            "dimensions": [     
                {"name": "date"},
                {"name": "sessionCampaignName"},
                {"name": "country"},
                {"name": "sessionMedium"},
                {"name": "sessionManualTerm"},
                {"name": "sessionSource"}
            ],
            "metrics": [
                {"name": "totalUsers" }, 
                {"name": "averageSessionDuration"},
                {"name": "screenPageViewsPerSession"},
                {"name": "bounceRate"},
                {"name": "publisherAdClicks"},
                {"name": "publisherAdImpressions"},
                {"name": "totalAdRevenue"},
            ]
        }';
        
    $link = 'https://analyticsdata.googleapis.com/v1beta/properties/' . $contaID . ':runReport' ;
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info = curl_getinfo($ch);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = (array) json_decode($result, true);
    $json = array_filter($json);
    
    return array(
        'lista' => $json
    );
}

function analyticsGestorPaisLista($itemID = '', $data = '', $dataInicio = '', $dataFinal = '', $firstUserCampaignName = '', $tipo = '', $formato = '') {
    global $con;
    
    $analyticID = $itemID;
    
    $clienteComissaoValor = (int) getClienteComissao();
    if ($clienteComissaoValor == 0)
        $clienteComissaoValor = 10;
                        
    $dataTime = strtotime($dataInicio);
                        
    $impostoPorcentagem = getConfig('imposto_porcentagem');
    if (empty($impostoPorcentagem))
        $impostoPorcentagem = 10;
    
    $where   = (array) $where;
    $where[] = "A._analyticID = " . $itemID;
    
    if ($tipo == 'tiktok') {
        $where[] = "campanhaTipo LIKE '%tiktok%'";
    } else {
        $where[] = "campanhaTipo LIKE '%facebook%'";
    }
    
    if (!empty($firstUserCampaignName))
        $where[] = "gestorPais_sessionCampaignName = '$firstUserCampaignName'";

    if ($data == 'CUSTOM') {
        if (validaDataDb($dataInicio) && validaDataDb($dataFinal)) {
            $where[] = "gestorPais_date between '$dataInicio' and '$dataFinal'";
        } else {
            $where[] = "gestorPais_date between '" . date('Y-m-d', strtotime('-1 day')) . "' AND '" . date('Y-m-d') . "'";
        }

    } else {
        if ($data == 'HOJE') {
            $where[] = "gestorPais_date = '" . date('Y-m-d') . "'";
        } else if ($data == 'YESTERDAY') {
            $where[] = "gestorPais_date = '" . date('Y-m-d', strtotime('-1 day')) . "'";
        } else if ($data == 'LAST_7_DAYS') {
            $where[] = "gestorPais_date >= '" . date('Y-m-d', strtotime('-7 days')) . "' AND gestorPais_date < '$dataAtual'";
        } else if ($data == 'MONTH_TO_DATE') {
            $where[] = "MONTH(gestorPais_date) = MONTH(CURRENT_DATE()) AND YEAR(gestorPais_date) = YEAR(CURRENT_DATE())";
        } else {
            $where[] = "gestorPais_date = '" . date('Y-m-d', strtotime('-1 day')) . "' ";
        }
    }
    
    $dias = 0;
    if (isset($_GET['modalGestorPaisFiltro']))
        $dias = diasEntreDatas();
    
    if ($data == 'YESTERDAY') {
        $dataInicio = date('Y-m-d', strtotime('-1 day'));
        $dataFinal  = date('Y-m-d', strtotime('-1 day'));
    }
    
    if ($data == 'LAST_7_DAYS') {
        $dataInicio = date('Y-m-d', strtotime('-7 day'));
        $dataFinal  = date('Y-m-d', strtotime('-1 day'));
    }
    
    if ($data == 'MONTH_TO_DATE') {
        $dataInicio = date('Y-m-01');
        $dataFinal  = date('Y-m-d', strtotime('-1 day'));
    }
    
    $arrNomes = analyticsGestorPaisTopo();
    
    unset($arrNomes['country']);
    unset($arrNomes['sessionCampaignName']);
    
    $arrNomes['imposto']     = '<i class="fa fa-money"></i> Imposto';
    $arrNomes['custo']       = 'Custo';
    $arrNomes['lucro']       = 'Lucro';
    $arrNomes['lucro_final'] = 'Lucro Final';
    $arrNomes['comissao']    = '<i class="fa fa-money"></i> Comissão';
    $arrNomes['roi']         = '<i class="fa fa-money"></i> Roi';
        
    $arrPais = arrPais();
        
    ob_start(); ?>
    
    <div id="gestorPaisListaConteudo">
        
        <?php
        $sql = "SELECT *, 
                SUM(gestorPais_totalAdRevenue) AS gestorPais_totalAdRevenue
            FROM analytics_gestor_pais A
                INNER JOIN analytics_campanhas ON campanha_sessionCampaignName = gestorPais_sessionCampaignName AND campanha_date = gestorPais_date
            WHERE " . implode(' AND ', $where) . " 
            GROUP BY gestorPais_country
            LIMIT 100;";
            
        $query = mysqli_query($con, $sql);
        
        if ($query) { 
            
            $_totalAdRevenue  = 0;
            $_totalComissao   = 0;
            $_totalCusto      = 0;
            $_totalLucro      = 0;
            $_totalLucroFinal = 0;
            $_totalImposto    = 0;
            $_totalRoi        = 0;
            
            $totalGeral_cliques    = 0;
            $totalGeral_impressoes = 0;
            $totalGeral_usuarios   = 0;
            $totalGeral_AdRevenue  = 0;
            $totalGeral_Custo      = 0;
            $totalGeral_Imposto    = 0;
            
            $totalGeral_facebookImpressoes = 0;
            $totalGeral_facebookImpressoes = 0;
            $totalGeral_facebookCliques = 0;
            $totalGeral_facebookValorGasto = 0; 
            
            $totalGeral_tiktokConversions = 0;
                
            while ($itemValor = mysqli_fetch_array($query)) { 
                
                $totalAdRevenue  = 0;
                $totalComissao   = 0;
                $totalCusto      = 0;
                $totalLucro      = 0;
                $totalLucroFinal = 0;
                $totalImposto    = 0;
                $totalRoi        = 0;
                $totalUsuarios   = 0;
                
                $total_tiktokImpressoes                 = 0;
                $total_tiktokConversions                = 0;         
                $total_tiktoktiktokTotalLandingPageView = 0;
                $total_tiktokCusto                      = 0;
                $total_tiktokCliques                    = 0;
                
                $gestorPais_date                = $itemValor['gestorPais_date']; 
                $gestorPais_country             = $itemValor['gestorPais_country']; 
                $gestorPaisImpostoValor         = $itemValor['gestorPaisImpostoValor'];
                $gestorPaisCustoValor           = $itemValor['gestorPaisCustoValor'];
                $gestorPais_totalAdRevenue      = $itemValor['gestorPais_totalAdRevenue'];
                $gestorPais_sessionCampaignName = $itemValor['gestorPais_sessionCampaignName'];
                $gestorPaisCustoClique          = $itemValor['gestorPaisCustoClique'];
                $gestorPaisComissaoValor        = $itemValor['gestorPaisComissaoValor'];
                $gestorPaisImposto              = $itemValor['gestorPaisImposto'];
                $gestorPaisLucroFinal           = $itemValor['gestorPaisLucroFinal'];
                $gestorPaisTotalUsers           = $itemValor['gestorPais_totalUsers'];
                $campanhaNomeDaConta            = $itemValor['campanhaNomeDaConta'];
                
                if ($formato == 'adx')
                    $gestorPais_totalAdRevenue = getGestoradxReceita($gestorPais_sessionCampaignName, $gestorPais_date, $gestorPais_country);
                    
                $rpmAD = ($gestorPais_totalAdRevenue / $gestorPaisTotalUsers) * 1000;
                
                $total_tiktokCusto    = $total_tiktokCusto    + $gestorPaisCustoValor;
                $totalGeral_AdRevenue = $totalGeral_AdRevenue + $gestorPais_totalAdRevenue;
                $totalGeral_Custo     = $totalGeral_Custo     + 
                
                $campanha_tiktokCliques                = $itemValor['gestorPais_tiktokCliques'];
                $campanha_tiktokImpressoes             = $itemValor['gestorPaisImpressoes'];
                $campanha_tiktokCTR                    = $itemValor['gestorPais_tiktokCTR'];
                $campanha_tiktokConversions            = $itemValor['gestorPais_tiktokConversions'];     
                $campanha_tiktokCPA                    = $itemValor['gestorPais_tiktokCPA'];
                $campanha_tiktokTotalLandingPageView   = $itemValor['gestorPais_tiktokTotalLandingPageView'];
                $campanha_tiktokCostperLandingPageView = $itemValor['gestorPais_tiktokCostperLandingPageView'];
                $campanha_tiktokLandingPageViewRate    = $itemValor['gestorPais_tiktokLandingPageViewRate'];
                
                $campanha_tiktokTotalLandingPageView = (int) $campanha_tiktokTotalLandingPageView;
                $campanha_tiktokCliques              = (int) $campanha_tiktokCliques;
                
                $total_tiktokImpressoes                 = $total_tiktokImpressoes                 + $campanha_tiktokImpressoes;
                $total_tiktokConversions                = $total_tiktokConversions                + $campanha_tiktokConversions;         
                $total_tiktokCliques                    = $total_tiktokCliques                    + $campanha_tiktokCliques;         
                $total_tiktoktiktokTotalLandingPageView = $total_tiktoktiktokTotalLandingPageView + $campanha_tiktokTotalLandingPageView;
                          
                $campanhaImpostoValor = ($gestorPais_totalAdRevenue / 100) * $impostoPorcentagem;
                $__totalAdRevenue       = ($gestorPais_totalAdRevenue - $campanhaImpostoValor) - $gestorPaisCustoValor;
                
                $gestorPaisComissaoValor = $__totalAdRevenue - ($__totalAdRevenue - (($__totalAdRevenue / 100) * $clienteComissaoValor));
                
                $gestorPaisLucroFinal = $__totalAdRevenue - $gestorPaisComissaoValor;
                
                $lucro = 0.00;
                if (!empty($gestorPaisCustoValor))
                    $lucro = $itemValor['gestorPais_totalAdRevenue'] - $gestorPaisCustoValor; 
                    
                $origem  = '';
                $termo   = '';
                $session = '';
                    
                ob_start(); ?>
                
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>               
                            <?php 
                            foreach ($arrNomes as $itemIndex => $itemNome) {
                                
                                if ($itemIndex == 'sessionSource' ||
                                    $itemIndex == 'sessionMedium' ||
                                    $itemIndex == 'sessionManualTerm' )
                                    continue;
                                
                                if ($itemIndex == 'totalAdRevenue') { 
                                    if ($tipo == 'facebook') { ?> 
                                        <th><small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Rpm AD</small></th>
                                        <?php 
                                    }
                                    
                                    if ($tipo == 'tiktok') { ?> 
                                        <th><small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Rpm AD</small></th>
                                        <?php 
                                    }
                                }
                                
                                if ($itemIndex == 'lucro') {
                                    echo '<th style="background-color: #cd81ff; color: #FFF;"><i class="fa fa-money"></i> Lucro</th>';
                                } else if ($itemIndex == 'lucro_final') {
                                    echo '<th style="background-color: #5cb85c; color: #FFF;"><i class="fa fa-money"></i> Lucro Final</th>';
                                } else if ($itemIndex == 'custo') {
                                    if ($tipo == 'facebook') {
                                        echo '<th style="background-color: #d9534f; color: #FFF;"><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Custo</th>';
                                    }
                                    
                                    if ($tipo == 'tiktok') {
                                        echo '<th style="background-color: #d9534f; color: #FFF;"><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Custo</th>';
                                    }
                                    
                                } else if ($itemIndex == 'comissao') {
                                    echo '<th style="background-color: #6db7f2; color: #FFF;"><i class="fa fa-money"></i> Comisso</th>';
                                } else {
                                    echo '<th><small>' . $itemNome . '</small></th>'; 
                                }
                                
                                if ($tipo == 'facebook') {
                                    if ($itemIndex == 'totalUsers') { ?>
                                        <th><small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Impressões</small></th>
                                        <th><small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> CTR</small></th>
                                        <th><small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Cliques</small></th>
                                        <th><small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Visualizações</small></th>
                                        <th><small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> CPC</small></th>
                                        <th><small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> CPV</small></th>
                                        <?php
                                    }
                                }
                                
                                if ($tipo == 'tiktok') {
                                    if ($itemIndex == 'totalUsers') { ?>
                                        <th><small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Impresses</small></th>
                                        <th><small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> CTR</small></th>
                                        <th><small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Cliques</small></th>
                                        <th><small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Conversão</small></th>
                                        <th><small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> CPA</small></th>
                                        <th><small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Visualizaes</small></th>
                                        <th><small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> CPV</small></th>
                                        <th><small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> View Rate</small></th>
                                        <?php
                                    }
                                }
                            } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            
                            <?php
                            $facebookImpressoes = 0;
                            $facebookCTR        = 0;
                            $facebookCliques    = 0;
                            $facebookViews      = 0;
                            $facebookCPC        = 0;
                            $facebookValorGasto = 0.00;
                            
                            $total_facebookImpressoes = 0;
                            $total_facebookCTR        = 0;
                            $total_facebookCliques    = 0;
                            $total_facebookViews      = 0;
                            $total_facebookCPC        = 0;
                            $total_facebookValorGasto = 0.00;
                            
                            $total_cliques    = 0;
                            $total_impressoes = 0;
                            $total_usuarios   = 0;
                            
                            $_where   = array();
                            $_where[] = "itemCampanhaNome = '$gestorPais_sessionCampaignName'";
                            $_where[] = "itemPaisNome = '$gestorPais_country'";
                            
                            if ($data == 'CUSTOM') {
                                if (validaDataDb($dataInicio))
                                    $_where[] = "itemData >= '$dataInicio'";
                                
                                if (validaDataDb($dataFinal))
                                    $_where[] = "itemData <= '$dataFinal'";
                            } else {
                                $_where[] = "itemData = '$gestorPais_date'";
                            }
                            
                            $facebookQuery = mysqli_query($con, "SELECT *
                                FROM facebook_itens
                                WHERE " . implode(' AND ', $_where) . "
                                LIMIT 100;");
                                
                            
                            if ($facebookQuery) {
                                $facebookItem = mysqli_fetch_array($facebookQuery);
                                if (isset($facebookItem['itemID'])) {
                                    $facebookImpressoes = $facebookItem['itemImpressoes'];
                                    $facebookCTR        = number_format((float) $facebookItem['itemCTR'], 2, '.', '');
                                    $facebookCliques    = $facebookItem['itemCliques'];
                                    $facebookViews      = $facebookItem['itemVisualizacoes'];
                                    $facebookCPC        = number_format((float) $facebookItem['itemCPC'], 2, '.', '');
                                    $facebookValorGasto = $facebookItem['itemCustoValor'];
                                    
                                    $total_facebookImpressoes = $total_facebookImpressoes + $facebookImpressoes;
                                    $total_facebookCTR        = $total_facebookCTR + $facebookCTR;
                                    $total_facebookCliques    = $total_facebookCliques + $facebookCliques;
                                    $total_facebookViews      = $total_facebookViews + $facebookViews;
                                    $total_facebookCPC        = $total_facebookCPC + $facebookCPC;
                                    $total_facebookValorGasto = $total_facebookValorGasto + $facebookValorGasto;
                                }
                            } 
                            
                            $totalGeral_facebookImpressoes = $totalGeral_facebookImpressoes + $facebookImpressoes;
                            $totalGeral_facebookCliques    = $totalGeral_facebookCliques    + $facebookCliques;
                            $totalGeral_facebookViews      = $totalGeral_facebookViews      + $facebookViews;
                            $totalGeral_facebookValorGasto = $totalGeral_facebookValorGasto + $facebookValorGasto;
                            
                            if ($tipo == 'tiktok') { 
                                $totalGeral_facebookImpressoes = $totalGeral_facebookImpressoes + $campanha_tiktokImpressoes;
                                $totalGeral_facebookCliques    = $totalGeral_facebookCliques    + $campanha_tiktokCliques;
                                $totalGeral_facebookViews      = $totalGeral_facebookViews      + $campanha_tiktokTotalLandingPageView;
                                $totalGeral_facebookValorGasto = $totalGeral_facebookValorGasto + $campanha_tiktokCostperLandingPageView;
                                $totalGeral_tiktokConversions  = $totalGeral_tiktokConversions  + $campanha_tiktokConversions;
                            }
                            
                            $total_cliques    = $total_cliques    + $itemValor['gestorPais_publisherAdClicks'];
                            $total_impressoes = $total_impressoes + $itemValor['gestorPais_publisherAdImpressions'];
                            $total_usuarios   = $total_usuarios   + $itemValor['gestorPais_totalUsers'];
                            
                            $totalGeral_cliques    = $totalGeral_cliques    + $itemValor['gestorPais_publisherAdClicks'];
                            $totalGeral_impressoes = $totalGeral_impressoes + $itemValor['gestorPais_publisherAdImpressions'];
                            $totalGeral_usuarios   = $totalGeral_usuarios   + $itemValor['gestorPais_totalUsers'];
                            
                            foreach ($arrNomes as $itemIndex => $itemNome) { 
                                
                                if ($itemIndex == 'sessionSource')
                                    $origem = $itemValor['gestorPais_' . $itemIndex];
                                    
                                if ($itemIndex == 'sessionManualTerm')
                                    $termo = $itemValor['gestorPais_' . $itemIndex];
                                    
                                if ($itemIndex == 'sessionMedium')
                                    $session = $itemValor['gestorPais_' . $itemIndex];
                                
                                if ($itemIndex == 'sessionSource' ||
                                    $itemIndex == 'sessionMedium' ||
                                    $itemIndex == 'sessionManualTerm' )
                                    continue;
                                
                                if ($itemIndex == 'averageSessionDuration') {
                                    $linhaValor = round(($itemValor['gestorPais_' . $itemIndex] / 60), 2);
                                    $arrTempo   = explode('.', $linhaValor);
                                    
                                    $linhaValor = $arrTempo[0] .'m ' . $arrTempo[1] .'s';
                                    
                                    $itemValor['gestorPais_' . $itemIndex] = $linhaValor;
                                }
                                    
                                if ($itemIndex == 'lucro') {
                                    $totalLucro = $totalLucro + $lucro;
                                    
                                    $itemValor['gestorPais_lucro'] = 'R$ ' . fmoney($lucro);     
                                }
                                    
                                if ($itemIndex == 'imposto') {
                                    $totalImposto  = $totalImposto  + $campanhaImpostoValor;
                                    
                                    $itemValor['gestorPais_imposto'] = 'R$ ' . fmoney($campanhaImpostoValor);     
                                }
                                    
                                if ($itemIndex == 'custo') {
                                    $totalCusto = $totalCusto + (float) $gestorPaisCustoValor;
                                    
                                    $itemValor['gestorPais_custo'] = 'R$ ' . (empty($gestorPaisCustoValor) ? '0,00' : fmoney($gestorPaisCustoValor));     
                                }
                                
                                if ($itemIndex == 'date')
                                    $itemValor['gestorPais_' . $itemIndex] = '<span class="label label-primary"><i class="fa fa-calendar"></i> ' . date('d/m/Y', strtotime($itemValor['gestorPais_' . $itemIndex])) . '</span>';
                                    
                                if ($itemIndex == 'country')
                                    $itemValor['gestorPais_' . $itemIndex] = '<span class="label label-danger">' . $itemValor['gestorPais_' . $itemIndex] . '</span>';
                                    
                                if ($itemIndex == 'totalAdRevenue') {
                                    $totalAdRevenue  = $totalAdRevenue + $gestorPais_totalAdRevenue;
                                
                                    
                                    $itemValor['gestorPais_' . $itemIndex] = 'R$ ' . fmoney($gestorPais_totalAdRevenue);
                                }
                                
                                if ($itemIndex == 'lucro_final') {
                                    $totalLucroFinal = $totalLucroFinal + $gestorPaisLucroFinal;
                                    
                                    $itemValor['gestorPais_' . $itemIndex] = 'R$ ' . fmoney($gestorPaisLucroFinal);
                                }
                                
                                if ($itemIndex == 'comissao') {
                                    $totalComissao = $totalComissao + $gestorPaisComissaoValor;
                                    
                                    $itemValor['gestorPais_' . $itemIndex] = 'R$ ' . fmoney($gestorPaisComissaoValor);
                                }
                                
                                if ($itemIndex == 'bounceRate')
                                    $itemValor['gestorPais_' . $itemIndex] = $itemValor['gestorPais_' . $itemIndex] . '%';
                                
                                if ($itemIndex == 'roi') {
                                    $roiValor = 0;
                                    if ($gestorPaisCustoValor > 0)
                                        $roiValor = $gestorPais_totalAdRevenue / $gestorPaisCustoValor;
                                        
                                    $roiRetorno = '';
                                    
                                    if ($roiValor >= 2.00) { 
                                        $roiRetorno = '<span class="label label-success labelRoi" style="font-size: 14px;"><span>' . date('d/m', strtotime($gestorPais_date)) . '</span>' . fmoney($roiValor) . '</span>';
                                    } else if ($roiValor < 0.90) {
                                        $roiRetorno = '<span class="label label-danger labelRoi" style="font-size: 14px;"><span>' . date('d/m', strtotime($gestorPais_date)) . '</span>' . fmoney($roiValor) . '</span>';
                                    } else if ($roiValor < 1.11) { 
                                        $roiRetorno = '<span class="label label-warning labelRoi" style="font-size: 14px;"><span>' . date('d/m', strtotime($gestorPais_date)) . '</span>' . fmoney($roiValor) . '</span>';
                                    } else if ($roiValor < 2.00) { 
                                        $roiRetorno = '<span class="label label-info labelRoi" style="font-size: 14px;"><span>' . date('d/m', strtotime($gestorPais_date)) . '</span>' . fmoney($roiValor) . '</span>';
                                    } 
                                    
                                    $itemValor['gestorPais_roi'] = $roiRetorno;
                                }
                                
                                $colunaClass = 'tabelaTdSimples';
                                if ($itemIndex == 'roi')
                                    $colunaClass = 'tabelaRioCalculo';
                                
                                if ($itemIndex == 'custo') {
                                    if ($tipo == 'facebook')
                                        $colunaClass = 'tabelaTdFacebook';
                                        
                                    if ($tipo == 'tiktok')
                                        $colunaClass = 'tabelaTdTiktok';
                                }
                                    
                                if ($itemIndex == 'totalUsers') {
                                    $itemValor['gestorPais_' . $itemIndex] = $itemValor['gestorPais_' . $itemIndex];
                                }
                                
                                if ($itemIndex == 'totalAdRevenue') { ?>
                                    <td class="tabelaTdSimples">R$ <?php echo fmoney($rpmAD); ?></td>
                                    <?php
                                }
                                
                                echo '<td class="' . $colunaClass . '">' . $itemValor['gestorPais_' . $itemIndex] . '</td>'; 
                                
                                if ($tipo == 'facebook') {
                                    
                                    if ($itemIndex == 'totalUsers') { ?>
                                        <td class="tabelaTdFacebook"><?php echo $facebookImpressoes; ?></td>
                                        <td class="tabelaTdFacebook"><?php echo $facebookCTR; ?>%</td>
                                        <td class="tabelaTdFacebook"><?php echo $facebookCliques; ?></td>
                                        <td class="tabelaTdFacebook"><?php echo $facebookViews; ?></td>
                                        <td class="tabelaTdFacebook">R$ <?php echo $facebookCPC; ?></td>
                                        <td class="tabelaTdFacebook">R$ <?php echo fmoney($facebookValorGasto / $facebookViews); ?></td>
                                        <?php
                                    }
                                }
                                
                                if ($tipo == 'tiktok') { 
                                    if ($itemIndex == 'totalUsers') { ?>
                                    
                                        <td class="tabelaTdTiktok"><?php echo $campanha_tiktokImpressoes; ?></td>
                                        <td class="tabelaTdTiktok"><?php echo $campanha_tiktokCTR; ?>%</td>
                                        <td class="tabelaTdTiktok"><?php echo $campanha_tiktokCliques; ?></td>
                                        <td class="tabelaTdTiktok"><?php echo $campanha_tiktokConversions; ?></td>
                                        <td class="tabelaTdTiktok">R$ <?php echo fmoney($campanha_tiktokCPA); ?></td>
                                        <td class="tabelaTdTiktok"><?php echo $campanha_tiktokTotalLandingPageView; ?></td>
                                        <td class="tabelaTdTiktok">R$ <?php echo fmoney($campanha_tiktokCostperLandingPageView); ?></td>
                                        <td class="tabelaTdTiktok"><?php echo $campanha_tiktokLandingPageViewRate; ?>%</td>
                                        <?php
                                    }
                                }
                            } 
                            
                            $_totalAdRevenue    = $_totalAdRevenue  + $totalAdRevenue;
                            $_totalComissao     = $_totalComissao   + $totalComissao;
                            $_totalCusto        = $_totalCusto      + $totalCusto;
                            $_totalLucro        = $_totalLucro      + $lucro;
                            $_totalLucroFinal   = $_totalLucroFinal + $totalLucroFinal;
                            $totalGeral_Imposto = $totalGeral_Imposto    + $gestorPaisImpostoValor; ?> 
                        </tr>
                        
                        <?php 
                        $arrDias = array(
                            date('Y-m-d', strtotime('-1 day', $dataTime)),
                            date('Y-m-d', strtotime('-2 day', $dataTime)),
                            date('Y-m-d', strtotime('-3 day', $dataTime)),
                            date('Y-m-d', strtotime('-4 day', $dataTime)),
                            date('Y-m-d', strtotime('-5 day', $dataTime)),
                            date('Y-m-d', strtotime('-6 day', $dataTime)),
                            date('Y-m-d', strtotime('-7 day', $dataTime)),
                            date('Y-m-d', strtotime('-8 day', $dataTime)),
                            date('Y-m-d', strtotime('-9 day', $dataTime)),
                            date('Y-m-d', strtotime('-10 day', $dataTime)),
                            date('Y-m-d', strtotime('-11 day', $dataTime)),
                            date('Y-m-d', strtotime('-12 day', $dataTime)),
                            date('Y-m-d', strtotime('-13 day', $dataTime)),
                            date('Y-m-d', strtotime('-14 day', $dataTime))
                        ); 
                        
                        foreach ($arrDias as $diaValor) { 
                            
                            $sql = "SELECT *,
                                    SUM(gestorPais_totalAdRevenue) AS gestorPais_totalAdRevenue
                                FROM analytics_gestor_pais  A
                                    INNER JOIN analytics_campanhas ON campanha_sessionCampaignName = gestorPais_sessionCampaignName AND campanha_date = gestorPais_date
                                WHERE
                                    gestorPais_sessionCampaignName = '$firstUserCampaignName' AND 
                                    gestorPais_date                = '$diaValor' AND
                                    gestorPais_country             = '$gestorPais_country' AND
                                    A._analyticID                  = $itemID
                                GROUP BY gestorPais_date
                                ORDER BY gestorPais_totalAdRevenue DESC
                                LIMIT 1;";
                                
                            $dias = mysqli_query($con, $sql);
                            
                            $diaItem = mysqli_fetch_array($dias);
                            if (isset($diaItem['gestorPais_date'])) {
                                $gestorPais_date                = $diaItem['gestorPais_date']; 
                                $gestorPais_country             = $diaItem['gestorPais_country']; 
                                $gestorPaisImpostoValor         = $diaItem['gestorPaisImpostoValor'];
                                $gestorPaisCustoValor           = $diaItem['gestorPaisCustoValor'];
                                $gestorPais_totalAdRevenue      = $diaItem['gestorPais_totalAdRevenue'];
                                $gestorPaisCustoClique          = $diaItem['gestorPaisCustoClique'];
                                $gestorPaisComissaoValor        = $diaItem['gestorPaisComissaoValor'];
                                $gestorPaisImposto              = $diaItem['gestorPaisImposto'];
                                $gestorPaisLucroFinal           = $diaItem['gestorPaisLucroFinal'];
                                $gestorPaisTotalUsers           = $diaItem['gestorPais_totalUsers'];
                                $gestorPais_sessionCampaignName = $diaItem['gestorPais_sessionCampaignName'];
                            
                                $campanha_tiktokCliques                = $diaItem['gestorPais_tiktokCliques'];
                                $campanha_tiktokImpressoes             = $diaItem['gestorPaisImpressoes'];
                                $campanha_tiktokCTR                    = $diaItem['gestorPais_tiktokCTR'];
                                $campanha_tiktokConversions            = $diaItem['gestorPais_tiktokConversions'];     
                                $campanha_tiktokCPA                    = $diaItem['gestorPais_tiktokCPA'];
                                $campanha_tiktokTotalLandingPageView   = $diaItem['gestorPais_tiktokTotalLandingPageView'];
                                $campanha_tiktokCostperLandingPageView = $diaItem['gestorPais_tiktokCostperLandingPageView'];
                                $campanha_tiktokLandingPageViewRate    = $diaItem['gestorPais_tiktokLandingPageViewRate'];
                                
                                if ($formato == 'adx')
                                    $gestorPais_totalAdRevenue = getGestoradxReceita($gestorPais_sessionCampaignName, $gestorPais_date, $gestorPais_country);
                                
                                $total_tiktokCusto    = $total_tiktokCusto    + $gestorPaisCustoValor;
                                
                                $rpmAD = ($gestorPais_totalAdRevenue / $gestorPaisTotalUsers) * 1000;
                                
                                $total_tiktokImpressoes                 = $total_tiktokImpressoes                 + $campanha_tiktokImpressoes;
                                $total_tiktokConversions                = $total_tiktokConversions                + $campanha_tiktokConversions;         
                                $total_tiktoktiktokTotalLandingPageView = $total_tiktoktiktokTotalLandingPageView + $campanha_tiktokTotalLandingPageView;
                                $total_tiktokCliques                    = $total_tiktokCliques                    + $campanha_tiktokCliques;       
                                 
                                $total_cliques    = $total_cliques    + $diaItem['gestorPais_publisherAdClicks'];
                                $total_impressoes = $total_impressoes + $diaItem['gestorPais_publisherAdImpressions'];
                                $total_usuarios   = $total_usuarios   + $diaItem['gestorPais_totalUsers'];
                                
                                $lucro = 0.00;
                                if (!empty($gestorPaisCustoValor))
                                    $lucro = $gestorPais_totalAdRevenue - $gestorPaisCustoValor; 
                                                   
                                $campanhaImpostoValor = ($gestorPais_totalAdRevenue / 100) * $impostoPorcentagem;
                                $__totalAdRevenue       = ($gestorPais_totalAdRevenue - $campanhaImpostoValor) - $gestorPaisCustoValor;
                            
                                $gestorPaisComissaoValor = $__totalAdRevenue - ($__totalAdRevenue - (($__totalAdRevenue / 100) * $clienteComissaoValor));
                        
                                $gestorPaisLucroFinal = $__totalAdRevenue - $gestorPaisComissaoValor; ?>
                                
                                <tr>
                                    <?php 
                                    $facebookImpressoes = 0;
                                    $facebookCTR        = 0;
                                    $facebookCliques    = 0;
                                    $facebookViews      = 0;
                                    $facebookCPC        = 0;
                                    $facebookValorGasto = 0.00;
                                    
                                    $facebookQuery = mysqli_query($con, "SELECT *
                                        FROM facebook_itens
                                        WHERE 
                                            itemCampanhaNome = '$firstUserCampaignName' AND
                                            itemPaisNome     = '$gestorPais_country' AND 
                                            itemData         = '$diaValor'
                                        LIMIT 100;");
                                        
                                    if ($facebookQuery) {
                                        $facebookItem = mysqli_fetch_array($facebookQuery);
                                        if (isset($facebookItem['itemID'])) {
                                            $facebookImpressoes = $facebookItem['itemImpressoes'];
                                            $facebookCTR        = number_format((float) $facebookItem['itemCTR'], 2, '.', '');
                                            $facebookCliques    = $facebookItem['itemCliques'];
                                            $facebookViews      = $facebookItem['itemVisualizacoes'];
                                            $facebookCPC        = number_format((float) $facebookItem['itemCPC'], 2, '.', '');
                                            $facebookValorGasto = $facebookItem['itemCustoValor'];
                                            
                                            $total_facebookImpressoes = $total_facebookImpressoes + $facebookImpressoes;
                                            $total_facebookCTR        = $total_facebookCTR + $facebookCTR;
                                            $total_facebookCliques    = $total_facebookCliques + $facebookCliques;
                                            $total_facebookViews      = $total_facebookViews + $facebookViews;
                                            $total_facebookCPC        = $total_facebookCPC + $facebookCPC;
                                            $total_facebookValorGasto = $total_facebookValorGasto + $facebookValorGasto;
                                        }
                                    }
                                    
                                    foreach ($arrNomes as $itemIndex => $itemNome) {
                                        
                                        if ($itemIndex == 'sessionSource' ||
                                            $itemIndex == 'sessionMedium' ||
                                            $itemIndex == 'sessionManualTerm' )
                                            continue;
                                        
                                        if ($itemIndex == 'averageSessionDuration') {
                                            $linhaValor = round(($diaItem['gestorPais_' . $itemIndex] / 60), 2);
                                            $arrTempo   = explode('.', $linhaValor);
                                            
                                            $linhaValor = $arrTempo[0] .'m ' . $arrTempo[1] .'s';
                                            
                                            $diaItem['gestorPais_' . $itemIndex] = $linhaValor;
                                        }
                                            
                                        if ($itemIndex == 'lucro') {
                                            $totalLucro = $totalLucro + $lucro;
                                            
                                            $diaItem['gestorPais_lucro'] = 'R$ ' . fmoney($lucro);     
                                        }
                                            
                                        if ($itemIndex == 'imposto') {
                                            $totalImposto = $totalImposto + $campanhaImpostoValor;
                                            
                                            $diaItem['gestorPais_imposto'] = 'R$ ' . fmoney($campanhaImpostoValor);     
                                        }
                                            
                                        if ($itemIndex == 'custo') {
                                            $totalCusto = $totalCusto + (float) $gestorPaisCustoValor;
                                            
                                            $diaItem['gestorPais_custo'] = 'R$ ' . (empty($gestorPaisCustoValor) ? '0,00' : fmoney($gestorPaisCustoValor));     
                                        }
                                        
                                        if ($itemIndex == 'date')
                                            $diaItem['gestorPais_' . $itemIndex] = '<span class="label label-primary"><i class="fa fa-calendar"></i> ' . date('d/m/Y', strtotime($gestorPais_date)) . '</span>';
                                            
                                        if ($itemIndex == 'country')
                                            $diaItem['gestorPais_' . $itemIndex] = '<span class="label label-danger">' . $arrNomes['gestorPais_' . $itemIndex] . '</span>';
                                            
                                        if ($itemIndex == 'totalAdRevenue') {
                                            $totalAdRevenue = $totalAdRevenue + $gestorPais_totalAdRevenue;
                                            
                                            $diaItem['gestorPais_' . $itemIndex] = 'R$ ' . fmoney($gestorPais_totalAdRevenue);
                                        }
                                        
                                        if ($itemIndex == 'lucro_final') {
                                            $totalLucroFinal = $totalLucroFinal + $gestorPaisLucroFinal;
                                            
                                            $diaItem['gestorPais_' . $itemIndex] = 'R$ ' . fmoney($gestorPaisLucroFinal);
                                        }
                                        
                                        if ($itemIndex == 'comissao') {
                                            $totalComissao = $totalComissao + $gestorPaisComissaoValor;
                                            
                                            $diaItem['gestorPais_' . $itemIndex] = 'R$ ' . fmoney($gestorPaisComissaoValor);
                                        }
                                        
                                        if ($itemIndex == 'bounceRate')
                                            $diaItem['gestorPais_' . $itemIndex] = $diaItem['gestorPais_' . $itemIndex] . '%';
                                        
                                        if ($itemIndex == 'roi') {
                                            $roiValor = 0;
                                            if ($gestorPaisCustoValor > 0)
                                                $roiValor = $gestorPais_totalAdRevenue / $gestorPaisCustoValor;
                                                
                                            $roiRetorno = '';
                                            
                                            if ($roiValor >= 2.00) { 
                                                $roiRetorno = '<span class="label label-success labelRoi" style="font-size: 14px;"><span>' . date('d/m', strtotime($gestorPais_date)) . '</span>' . fmoney($roiValor) . '</span>';
                                            } else if ($roiValor < 0.90) {
                                                $roiRetorno = '<span class="label label-danger labelRoi" style="font-size: 14px;"><span>' . date('d/m', strtotime($gestorPais_date)) . '</span>' . fmoney($roiValor) . '</span>';
                                            } else if ($roiValor < 1.11) { 
                                                $roiRetorno = '<span class="label label-warning labelRoi" style="font-size: 14px;"><span>' . date('d/m', strtotime($gestorPais_date)) . '</span>' . fmoney($roiValor) . '</span>';
                                            } else if ($roiValor < 2.00) { 
                                                $roiRetorno = '<span class="label label-info labelRoi" style="font-size: 14px;"><span>' . date('d/m', strtotime($gestorPais_date)) . '</span>' . fmoney($roiValor) . '</span>';
                                            } 
                                            
                                            $diaItem['gestorPais_roi'] = $roiRetorno;
                                        }
                                        
                                        $colunaClass = 'tabelaTdSimples';
                                        if ($itemIndex == 'roi')
                                            $colunaClass = 'tabelaRioCalculo';
                                            
                                        if ($itemIndex == 'custo') {
                                            if ($tipo == 'facebook')
                                                $colunaClass = 'tabelaTdFacebook';
                                                
                                            if ($tipo == 'tiktok')
                                                $colunaClass = 'tabelaTdTiktok';
                                        }
                                            
                                        if ($itemIndex == 'totalUsers') {
                                            $diaItem['gestorPais_' . $itemIndex] = $diaItem['gestorPais_' . $itemIndex];
                                        }
                                        
                                        if ($itemIndex == 'totalAdRevenue') { ?>
                                            <td class="tabelaTdSimples">R$ <?php echo fmoney($rpmAD); ?></td>
                                            <?php
                                        }
                                        
                                        echo '<td class="' . $colunaClass . '">' . $diaItem['gestorPais_' . $itemIndex] . '</td>'; 
                                        
                                        if ($tipo == 'facebook') {
                                            if ($itemIndex == 'totalUsers') { ?>
                                                <td class="tabelaTdFacebook"><?php echo $facebookImpressoes; ?></td>
                                                <td class="tabelaTdFacebook"><?php echo $facebookCTR; ?>%</td>
                                                <td class="tabelaTdFacebook"><?php echo $facebookCliques; ?></td>
                                                <td class="tabelaTdFacebook"><?php echo $facebookViews; ?></td>
                                                <td class="tabelaTdFacebook">R$ <?php echo $facebookCPC; ?></td>
                                                <td class="tabelaTdFacebook">R$ <?php echo fmoney($facebookValorGasto / $facebookViews); ?></td>
                                                <?php
                                            } 
                                        }
                                        
                                        if ($tipo == 'tiktok') {
                                            if ($itemIndex == 'totalUsers') { ?>
                                                <td class="tabelaTdTiktok"><?php echo $campanha_tiktokImpressoes; ?></td>
                                                <td class="tabelaTdTiktok"><?php echo $campanha_tiktokCTR; ?>%</td>
                                                <td class="tabelaTdTiktok"><?php echo $campanha_tiktokCliques; ?></td>
                                                <td class="tabelaTdTiktok"><?php echo $campanha_tiktokConversions; ?></td>
                                                <td class="tabelaTdTiktok">R$ <?php echo fmoney($campanha_tiktokCPA); ?></td>
                                                <td class="tabelaTdTiktok"><?php echo $campanha_tiktokTotalLandingPageView; ?></td>
                                                <td class="tabelaTdTiktok">R$ <?php echo fmoney($campanha_tiktokCostperLandingPageView); ?></td>
                                                <td class="tabelaTdTiktok"><?php echo $campanha_tiktokLandingPageViewRate; ?>%</td>
                                                <?php
                                            } 
                                        }
                                    } ?>
                                </tr>
                                <?php 
                            }
                        } ?>
                       
                        <tr class="tabelaTotal">
                          
                            <?php
                            $roiValor = 0;
                            if ($totalCusto > 0)
                                $roiValor = $totalAdRevenue / $totalCusto;
                                
                            $roiRetorno = '';
                            
                            if ($roiValor >= 2.00) { 
                                $roiRetorno = '<span class="label label-success" style="font-size: 14px;">' . fmoney($roiValor) . '</span>';
                            } else if ($roiValor < 0.90) {
                                $roiRetorno = '<span class="label label-danger" style="font-size: 14px;">' . fmoney($roiValor) . '</span>';
                            } else if ($roiValor < 1.11) { 
                                $roiRetorno = '<span class="label label-warning" style="font-size: 14px;">' . fmoney($roiValor) . '</span>';
                            } else if ($roiValor < 2.00) { 
                                $roiRetorno = '<span class="label label-info" style="font-size: 14px;">' . fmoney($roiValor) . '</span>';
                            }
                            
                            foreach ($arrNomes as $itemIndex => $itemNome) {
                                
                                if ($itemIndex == 'sessionSource' ||
                                    $itemIndex == 'sessionMedium' ||
                                    $itemIndex == 'sessionManualTerm' )
                                    continue;
                                
                                if ($itemIndex == 'totalAdRevenue') { 
                                    if ($tipo == 'facebook') { ?>
                                        <td class="tabelaTdFacebook">
                                            <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Rpm AD</small><br>
                                            <strong>R$ <?php echo fmoney(($totalAdRevenue / $total_usuarios) * 1000); ?></strong>
                                        </td>
                                        <?php
                                    }
                                    
                                    if ($tipo == 'tiktok') { ?>
                                        <td class="tabelaTdTiktok">
                                            <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Rpm AD</small><br>
                                            <strong>R$ <?php echo fmoney(($totalAdRevenue / $total_usuarios) * 1000); ?></strong>
                                        </td>
                                        <?php
                                    }
                                }
                                
                                if ($itemIndex == 'comissao') { ?>
                                    <td class="">
                                        <small><?php echo $itemNome; ?></small><br>
                                        <strong>R$ <?php echo fmoney($totalComissao); ?></strong>
                                    </td>
                                    
                                    <?php
                                } else if ($itemIndex == 'roi') { ?>
                                    <td class="">
                                        <small><?php echo $itemNome; ?></small><br>
                                        <?php echo $roiRetorno; ?>
                                    </td>
                                    
                                    <?php
                                } else if ($itemIndex == 'custo') { 
                                    if ($tipo == 'facebook') { ?>
                                        <td class="tabelaTdFacebook">
                                            <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> <?php echo $itemNome; ?></small><br>
                                            <strong>R$ <?php echo fmoney($totalCusto); ?></strong>
                                        </td>    
                                        <?php
                                    }
                                    
                                    if ($tipo == 'tiktok') { ?>
                                        <td class="tabelaTdTiktok">
                                            <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> <?php echo $itemNome; ?></small><br>
                                            <strong>R$ <?php echo fmoney($totalCusto); ?></strong>
                                        </td>    
                                        <?php
                                    }
                                    
                                } else if ($itemIndex == 'imposto') { ?>
                                    <td class="">
                                        <small><?php echo $itemNome; ?></small><br>
                                        <strong>R$ <?php echo fmoney($totalImposto); ?></strong>
                                    </td>
                                    
                                    <?php
                                } else if ($itemIndex == 'lucro') { ?>
                                    <td class="">
                                        <small><?php echo $itemNome; ?></small><br>
                                        <strong>R$ <?php echo fmoney($totalLucro); ?></strong>
                                    </td>
                                    
                                    <?php
                                } else if ($itemIndex == 'lucro_final') { ?>
                                    <td class="">
                                        <small><?php echo $itemNome; ?></small><br>
                                        <strong>R$ <?php echo fmoney($totalLucroFinal); ?></strong>
                                    </td>
                                    
                                    <?php
                                } else if ($itemIndex == 'totalAdRevenue') { ?>
                                    <td class="">
                                        <small><?php echo $itemNome; ?></small><br>
                                        <strong>R$ <?php echo fmoney($totalAdRevenue); ?></strong>
                                    </td>
                                    
                                    <?php
                                } else if ($itemIndex == 'totalUsers') { ?>
                                    <td class="">
                                        <small><?php echo $itemNome; ?></small><br>
                                        <strong><?php echo $total_usuarios; ?></strong>
                                    </td>
                                    
                                    <?php
                                } else if ($itemIndex == 'publisherAdImpressions') { ?>
                                    <td class="">
                                        <small><?php echo $itemNome; ?></small><br>
                                        <strong><?php echo $total_impressoes; ?></strong>
                                    </td>
                                    
                                    <?php
                                } else if ($itemIndex == 'publisherAdClicks') { ?>
                                    <td class="">
                                        <small><?php echo $itemNome; ?></small><br>
                                        <strong><?php echo $total_cliques; ?></strong>
                                    </td>
                                    
                                    <?php
                                } else {
                                    echo '<td></td>';     
                                }
                                
                                if ($tipo == 'facebook') {
                                    if ($itemIndex == 'totalUsers') { 
                                    
                                        $ctrFinal = str_replace('0.0', '', ($total_facebookCliques / $total_facebookImpressoes));
                                        $ctrFinal = substr($ctrFinal, 0, 1) . '.' . substr($ctrFinal, 1, strlen($ctrFinal)); ?>
                                        
                                        <td class="tabelaTdFacebook">
                                            <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Impressões</small><br>
                                            <?php echo $total_facebookImpressoes; ?>
                                        </td>
                                        <td class="tabelaTdFacebook">
                                            <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> CTR</small><br>
                                            <?php echo number_format((float) $ctrFinal, 2, '.', ''); ?>%
                                        </td>
                                        <td class="tabelaTdFacebook">
                                            <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Cliques</small><br>
                                            <?php echo $total_facebookCliques; ?>
                                        </td>
                                        <td class="tabelaTdFacebook">
                                            <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Visualizaões</small><br>
                                            <?php echo $total_facebookViews; ?>
                                        </td>
                                        <td class="tabelaTdFacebook">
                                            <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> CPC</small><br>
                                            R$ <?php echo fmoney($total_facebookValorGasto / $total_facebookCliques); ?>
                                        </td>
                                        <td class="tabelaTdFacebook">
                                            <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> CPV</small><br>
                                            R$ <?php echo fmoney($total_facebookValorGasto / $total_facebookViews); ?>
                                        </td>
                                        <?php
                                    }
                                }
                                
                                if ($tipo == 'tiktok') { 
                                    if ($itemIndex == 'totalUsers') {
                                    
                                        $ctrFinal = str_replace('0.0', '', ($total_tiktokCliques / $total_tiktokImpressoes));
                                        $ctrFinal = substr($ctrFinal, 0, 1) . '.' . substr($ctrFinal, 1, strlen($ctrFinal)); ?>
                                    
                                        <td class="tabelaTdTiktok">
                                            <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Impressões</small><br>
                                            <?php echo $total_tiktokImpressoes; ?>
                                        </td>
                                        <td class="tabelaTdTiktok">
                                            <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> CTR</small><br>
                                            <?php echo number_format((float) $ctrFinal, 2, '.', ''); ?>%
                                        </td>
                                        <td class="tabelaTdTiktok">
                                            <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Cliques</small><br>
                                            <?php echo $total_tiktokCliques; ?>
                                        </td>
                                        <td class="tabelaTdTiktok">
                                            <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Conversão</small><br>
                                            <?php echo $total_tiktokConversions; ?>
                                        </td>
                                        <td class="tabelaTdTiktok">
                                            <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> CPA</small><br>
                                            R$ <?php echo fmoney($total_tiktokCusto / $total_tiktokConversions); ?>
                                        </td>
                                        <td class="tabelaTdTiktok">
                                            <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Visualizações</small><br>
                                            <?php echo $total_tiktoktiktokTotalLandingPageView; ?>
                                        </td>
                                        <td class="tabelaTdTiktok">
                                            <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> CPV</small><br>
                                            R$ <?php echo fmoney($total_tiktokCusto / $total_tiktoktiktokTotalLandingPageView); ?>
                                        </td>
                                        <td class="tabelaTdTiktok">
                                            
                                        </td>
                                        <?php
                                    }
                                }
                            } ?>
                        </tr>
                    </tbody>
                </table>
                
                <?php 
                $resultado = ob_get_contents();
                ob_end_clean(); 
                
                $bandeira = '';
                if (isset($arrPais[$gestorPais_country]))
                    $bandeira = strtolower($arrPais[$gestorPais_country]);
                    
                $paisBandeira = '<img src="' . base_url('assets/img/flags/' . $bandeira . '_16.png') . '">'; ?>
                
                <div class="form-group">
                    País: <span class="label label-danger" style="font-size: 14px;"><?php echo $paisBandeira . ' ' . $gestorPais_country; ?></span>
                    
                    <?php
                    if (!empty($termo))
                        echo '<span class="label label-default" style="font-size: 14px;">Termo: ' . $termo . '</span> ';
                        
                    if (!empty($origem))
                        echo '<span class="label label-default" style="font-size: 14px;">Origem: ' . $origem . '</span> ';
                        
                    if (!empty($session))
                        echo '<span class="label label-default" style="font-size: 14px;">Session: ' . $session . '</span> '; ?>
                </div>
                
                <?php
                echo $resultado;
            } ?>
            
            <table class="table table-bordered" width="100%" cellspacing="0">
                <tbody>
                    <tr class="tabelaTotal">
                        <td></td>
                        <td></td>
                        
                        <?php
                        $campanhaImpostoValor    = ($_totalAdRevenue / 100) * $impostoPorcentagem;
                        $__totalAdRevenue          = ($_totalAdRevenue - $campanhaImpostoValor) - $_totalCusto;
                        
                        $gestorPaisComissaoValor = $__totalAdRevenue - ($__totalAdRevenue - (($__totalAdRevenue / 100) * $clienteComissaoValor));
                        
                        $gestorPaisLucroFinal    = $__totalAdRevenue - $gestorPaisComissaoValor;
                                    
                        $roiValor = 0;
                        if ($_totalCusto > 0)
                            $roiValor = $_totalAdRevenue / $_totalCusto;
                            
                        $roiRetorno = '';
                        
                        if ($roiValor >= 2.00) { 
                            $roiRetorno = '<span class="label label-success" style="font-size: 14px;">' . fmoney($roiValor) . '</span>';
                        } else if ($roiValor < 0.90) {
                            $roiRetorno = '<span class="label label-danger" style="font-size: 14px;">' . fmoney($roiValor) . '</span>';
                        } else if ($roiValor < 1.11) { 
                            $roiRetorno = '<span class="label label-warning" style="font-size: 14px;">' . fmoney($roiValor) . '</span>';
                        } else if ($roiValor < 2.00) { 
                            $roiRetorno = '<span class="label label-info" style="font-size: 14px;">' . fmoney($roiValor) . '</span>';
                        }
                            
                        foreach ($arrNomes as $itemIndex => $itemNome) {
                            
                            if ($itemIndex == 'sessionSource' ||
                                $itemIndex == 'sessionMedium' ||
                                $itemIndex == 'sessionManualTerm' )
                                continue;
                            
                            if ($itemIndex == 'totalAdRevenue') { 
                                if ($tipo == 'facebook') { ?>
                                    <td class="tabelaTdFacebook">
                                        <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Rpm AD</small><br>
                                        <strong>R$ <?php echo fmoney(($totalGeral_AdRevenue / $totalGeral_usuarios) * 1000); ?></strong>
                                    </td>
                                    <?php
                                }
                                
                                if ($tipo == 'tiktok') { ?>
                                    <td class="tabelaTdTiktok">
                                        <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Rpm AD</small><br>
                                        <strong>R$ <?php echo fmoney(($totalGeral_AdRevenue / $totalGeral_usuarios) * 1000); ?></strong>
                                    </td>
                                    <?php
                                }
                            }
                            
                            if ($itemIndex == 'comissao') { ?>
                                <td width="140">
                                    <small><?php echo $itemNome; ?></small><br>
                                    <strong>R$ <?php echo fmoney($gestorPaisComissaoValor); ?></strong>
                                </td>
                                
                                <?php
                            } else if ($itemIndex == 'roi') { ?>
                                <td width="140">
                                    <small><?php echo $itemNome; ?></small><br>
                                    <?php echo $roiRetorno; ?>
                                </td>
                                
                                <?php
                            } else if ($itemIndex == 'custo') { 
                                if ($tipo == 'tiktok') { ?>
                                    <td width="140" class="tabelaTdTiktok">
                                        <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> <?php echo $itemNome; ?></small><br>
                                        <strong>R$ <?php echo fmoney($_totalCusto); ?></strong>
                                    </td>
                                    <?php
                                }
                                
                                if ($tipo == 'facebook') { ?>
                                    <td width="140" class="tabelaTdFacebook">
                                        <small><i class="fa-brands fa-tiktok tabelaTopoFacebook"></i> <?php echo $itemNome; ?></small><br>
                                        <strong>R$ <?php echo fmoney($_totalCusto); ?></strong>
                                    </td>
                                    <?php
                                }
                                
                            } else if ($itemIndex == 'imposto') { ?>
                                <td width="140">
                                    <small><?php echo $itemNome; ?></small><br>
                                    <strong>R$ <?php echo fmoney($totalGeral_Imposto); ?></strong>
                                </td>
                                
                                <?php
                            } else if ($itemIndex == 'lucro') { ?>
                                <td width="140">
                                    <small><?php echo $itemNome; ?></small><br>
                                    <strong>R$ <?php echo fmoney($totalGeral_AdRevenue - $_totalCusto); ?></strong>
                                </td>
                                
                                <?php
                            } else if ($itemIndex == 'lucro_final') { ?>
                                <td width="140">
                                    <small><?php echo $itemNome; ?></small><br>
                                    <strong>R$ <?php echo fmoney($gestorPaisLucroFinal); ?></strong>
                                </td>
                                
                                <?php
                            } else if ($itemIndex == 'totalAdRevenue') { ?>
                                <td width="140">
                                    <small><?php echo $itemNome; ?></small><br>
                                    <strong>R$ <?php echo fmoney($totalGeral_AdRevenue); ?></strong>
                                </td>
                                
                                <?php
                            } else if ($itemIndex == 'totalUsers') { ?>
                                <td class="">
                                    <small><?php echo $itemNome; ?></small><br>
                                    <strong><?php echo $totalGeral_usuarios; ?></strong>
                                </td>
                                
                                <?php
                            } else if ($itemIndex == 'publisherAdImpressions') { ?>
                                <td class="">
                                    <small><?php echo $itemNome; ?></small><br>
                                    <strong><?php echo $totalGeral_impressoes; ?></strong>
                                </td>
                                
                                <?php
                            } else if ($itemIndex == 'publisherAdClicks') { ?>
                                <td class="">
                                    <small><?php echo $itemNome; ?></small><br>
                                    <strong><?php echo $totalGeral_cliques; ?></strong>
                                </td>
                                    
                                <?php
                            } else {
                                echo '<td></td>';     
                            }
                            
                            if ($itemIndex == 'totalUsers') {
                            
                                if ($tipo == 'tiktok') { ?>

                                    <td class="tabelaTdTiktok">
                                        <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Impressões</small><br>
                                        <?php echo $totalGeral_facebookImpressoes; ?>
                                    </td>
                                    <td class="tabelaTdTiktok">
                                        <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> CTR</small><br>
                                        <?php echo number_format((float) $ctrFinal, 2, '.', ''); ?>%
                                    </td>
                                    <td class="tabelaTdTiktok">
                                        <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Cliques</small><br>
                                        <?php echo $totalGeral_facebookCliques; ?>
                                    </td>
                                    <td class="tabelaTdTiktok">
                                        <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Conversão</small><br>
                                        <?php echo $totalGeral_tiktokConversions; ?>
                                    </td>
                                    
                                    <td class="tabelaTdTiktok">
                                        <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> CPA</small><br>
                                        R$ <?php echo fmoney($total_tiktokCusto / $totalGeral_tiktokConversions); ?>
                                    </td>
                                    <td class="tabelaTdTiktok">
                                        <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> Visualizaões</small><br>
                                        <?php echo $totalGeral_facebookViews; ?>
                                    </td>
                                    <td class="tabelaTdTiktok">
                                        <small><i class="fa-brands fa-tiktok tabelaTopoTiktok"></i> CPV</small><br>
                                        R$ <?php echo fmoney($total_tiktokCusto / $totalGeral_facebookViews); ?>
                                    </td>
                                    <?php
                                }
                                
                                if ($tipo == 'facebook') { ?>
                                
                                    <td class="tabelaTdFacebook">
                                        <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Impressões</small><br>
                                        <?php echo $totalGeral_facebookImpressoes; ?>
                                    </td>
                                    <td class="tabelaTdFacebook">
                                        <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> CTR</small><br>
                                        <?php echo number_format((float) $ctrFinal, 2, '.', ''); ?>%
                                    </td>
                                    <td class="tabelaTdFacebook">
                                        <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Cliques</small><br>
                                        <?php echo $totalGeral_facebookCliques; ?>
                                    </td>
                                    <td class="tabelaTdFacebook">
                                        <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> Visualizaçes</small><br>
                                        <?php echo $totalGeral_facebookViews; ?>
                                    </td>
                                    <td class="tabelaTdFacebook">
                                        <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> CPC</small><br>
                                        R$ <?php echo fmoney($totalGeral_facebookValorGasto / $totalGeral_facebookCliques); ?>
                                    </td>
                                    <td class="tabelaTdFacebook">
                                        <small><i class="fa-brands fa-facebook tabelaTopoFacebook"></i> CPV</small><br>
                                        R$ <?php echo fmoney($totalGeral_facebookValorGasto / $totalGeral_facebookViews); ?>
                                    </td>
                                    <?php
                                }
                            }
                        } ?>
                    </tr>
                </tbody>
            </table>
            <?php
        } ?>
        
    </div>
        
    <?php
    $retorno = ob_get_contents();
    ob_end_clean();
    
    if (!isset($_GET['modalGestorPaisFiltro'])) { 
        ob_start(); ?>
        
        <form action="<?php echo site_url('ajax.php?modalGestorPaisFiltro'); ?>" method="POST" class="frmAjax">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Data Inicio</label>
                        <span class="campoData">
                            <span class="campoDataIcone"><i class="fa fa-calendar"></i></span>
                            <input class="form-control date_picker maskData hasDatepicker" placeholder="99/99/9999" type="text" name="data_inicio" value="10/07/2023" maxlength="10">
                        </span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Data final</label>
                        <span class="campoData">
                            <span class="campoDataIcone"><i class="fa fa-calendar"></i></span>
                            <input class="form-control date_picker maskData hasDatepicker" placeholder="99/99/9999" type="text" name="data_final" value="10/07/2023" maxlength="10">
                        </span>
                    </div>
                </div>
                
                <div class="clearfix"></div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-success btn-block"><i class="fa fa-search"></i> Filtrar</button>
            </div>
            
            <input type="hidden" name="itemID" value="<?php echo $itemID; ?>" />
            <input type="hidden" name="data" value="CUSTOM" />
            <input type="hidden" name="firstUserCampaignName" value="<?php echo $firstUserCampaignName; ?>" />
            <input type="hidden" name="tipo" value="<?php echo $tipo; ?>" />
        </form>
        
        <?php
        $filtro = ob_get_contents();
        ob_end_clean();
    
        $retorno = $filtro . $retorno;
    }
    
    return $retorno;
}

function pre($str = '', $parar = false) {
    echo '<pre>';
    print_r($str);
    echo '</pre>';
    
    if ($parar)
        exit;
}

function arrPaisSiglaNome($str = '', $retorno = false) {
    $arr = array();
    $str = strtoupper($str);
    
    foreach (arrPais() as $itemIndex => $itemValor) {
        $arr[$itemValor] = $itemIndex;
    }
    
    if ($retorno) {
        if (isset($arr[$str]))
            return $arr[$str];
            
        return;
    }
    
    
    return $arr;
}

function arrPais($str = '', $retorno = false) {
    global $con;
  	
  	if ($retorno) {
      	$retornoSigla = '';
      
      	if (!empty($str)) {
            $paises = mysqli_query($con, "SELECT *
                FROM paises
                WHERE 
                    paisNome = '$str' OR paisNomeIngles = '$str'
                LIMIT 1");

            if ($paises) {
                $paisValor = mysqli_fetch_array($paises);
              	if (isset($paisValor['paisSigla'])) {
                  	$retornoSigla = $paisValor['paisSigla'];
                }
            }
        }
      
      	return $retornoSigla;
    } else {
      
    	$arr = array();  	
  	
      	$paises = mysqli_query($con, "SELECT *
            FROM paises
            ORDER BY paisNome ASC");

        if ($paises) {
            while ($paisValor = mysqli_fetch_array($paises)) {
                $paisNome  = $paisValor['paisNome'];
                $paisSigla = $paisValor['paisSigla'];

                $arr[$paisNome] = $paisSigla;
            }
        }
      
     	return $arr; 
    }
}

function paisTrocaNome($str = '') {
    $arr = array(
        'Turkey' => 'Türkiye'
    );
    
    if (array_key_exists($str, $arr))
        return $arr[$str];
    return $str;
}