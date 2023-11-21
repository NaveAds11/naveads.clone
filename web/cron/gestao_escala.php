<?php 
header("Access-Control-Allow-Origin: *");

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

include('../config.php'); 
include(ABSPATH .'funcoes.php'); 

set_time_limit(0);

$pasta     = ABSPATH . 'cron/arquivos/gestao_escala/';
$arquivos  = glob($pasta . '*.txt');
$limiteDia = 1;
$dataAtual = date('Y-m-d');

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
    $where[] = 'escalaSituacao = 1';
    
    if (isset($_GET['conta'])) {
        $contaID = (int) $_GET['conta'];
        if ($contaID > 0) {
            $where[] = 'escalaClienteID = ' . $contaID;
        }
    }
    
    $sql = "SELECT * 
        FROM cliente_gestao_escala " . (count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '') ;
        
    $itens = mysqli_query($con, $sql);
     
    if ($itens) {
        while ($itemValor = mysqli_fetch_array($itens)) {
                
            $escalaID          = $itemValor['escalaID'];
            $escalaNome        = $itemValor['escalaNome'];
            $escalaTipo        = $itemValor['escalaTipo'];
            $escalaDiasInicio  = $itemValor['escalaDiasInicio'];
            $escalaDiasFinal   = $itemValor['escalaDiasFinal'];
            $escalaRoiInicio   = $itemValor['escalaRoiInicio'];
            $escalaRoiFinal    = $itemValor['escalaRoiFinal'];
            $escalaCustoInicio = $itemValor['escalaCustoInicio'];
            $escalaCustoFinal  = $itemValor['escalaCustoFinal'];
            $escalaHoraInicio  = $itemValor['escalaHoraInicio'];
            $escalaHoraFinal   = $itemValor['escalaHoraFinal'];
            $escalaClienteID   = $itemValor['escalaClienteID'];
            $escalaTimezone    = $itemValor['escalaTimezone'];
            $escalaHistorico   = $itemValor['escalaHistorico'];
            $escalaFormato     = $itemValor['escalaFormato'];
            $escalaValor       = $itemValor['escalaValor'];
            
            $escalaHistorico = (int) $escalaHistorico;
            
            $escalaCustoInicio = (float) $escalaCustoInicio;
            $escalaCustoFinal  = (float) $escalaCustoFinal;
            
            $escalaRoiInicio = (float) $escalaRoiInicio;
            $escalaRoiFinal  = (float) $escalaRoiFinal;
            
            $escalaHoraInicio = (int) $escalaHoraInicio;
            $escalaHoraFinal  = (int) $escalaHoraFinal;
            
            if (empty($escalaRoiInicio) || ($escalaRoiInicio == 0))
                $escalaRoiInicio = '0.00';
                
            if (empty($escalaCustoInicio) || ($escalaCustoInicio == 0))
                $cadastroCustoInicio = '0.00';
            
            $filtroValido = true;
            
            $data = array(
                'escalaRodouEm' => date('Y-m-d H:i:s'),
            );
            
            update('cliente_gestao_escala', $data, 'escalaID = ' . $escalaID);
            
            $dataAtual = date('Y-m-d');
            $horaAtual = date('H');
            
            if (!empty($escalaTimezone)) {
                $dataAtual = date('Y-m-d', strtotime($escalaTimezone . ' hours'));
                $horaAtual = date('H', strtotime($escalaTimezone . ' hours'));
            }
            
            // Tipo Facebook ou Tiktok
            if (empty($escalaTipo))
                $filtroValido = false;
                
            // Se Roi vazio
            if (empty($escalaRoiInicio) && empty($escalaRoiFinal))
                $filtroValido = false;
            
            if ($filtroValido) {    
                    
                $where = array();
                
                // Valida custo
                if (($escalaCustoInicio > 0) && ($escalaCustoFinal > 0)) {
                    $where[] = "relatorioCustoValor >= '$escalaCustoInicio' ";
                    $where[] = "relatorioCustoValor <= '$escalaCustoFinal' ";
                    
                } else if ($escalaCustoInicio > 0) {
                    $where[] = "relatorioCustoValor >= '$escalaCustoInicio' ";
                
                } else if ($escalaCustoFinal > 0) {
                    $where[] = "relatorioCustoValor <= '$escalaCustoFinal' ";
                }
                
                // Tipo
                if (!empty($escalaTipo))
                    $where[] = " relatorioUtmSource = '$escalaTipo' ";
                
                // Roi
                if (($escalaRoiInicio > 0) && ($escalaRoiFinal > 0)) {
                    $where[] = " relatorioRoiFinalValor > $escalaRoiInicio ";
                    $where[] = " relatorioRoiFinalValor < $escalaRoiFinal ";
                    
                } else if ($escalaRoiInicio > 0) {
                    $where[] = "relatorioRoiFinalValor > $escalaRoiInicio ";
                } else if ($escalaRoiFinal > 0) {
                    $where[] = "relatorioRoiFinalValor < $escalaRoiFinal ";
                }
                
                $sql = "SELECT *, 
                        A._clienteID AS _clienteID,
                        A.relatorioEscalaData AS relatorioEscalaData
                    FROM adx_relatorios A 
                        INNER JOIN gestao_utms ON gestaoUtm_adset_id = relatorioUtmValor
                    WHERE
                        relatorioUtmTipo        = 'adset_id' AND 
                        relatorioCampanhaStatus = 'ACTIVE' AND
                        relatorioData           = CURDATE() AND " . (count($where) > 0 ? implode(' AND ', $where) : '') . "
                    GROUP BY relatorioUtmValor
                    ORDER BY relatorioID DESC;";
                
                $relatorios = mysqli_query($con, $sql);
                
                if ($relatorios) {
                    $total = mysqli_num_rows($relatorios);
                    
                    while ($relatorioValor = mysqli_fetch_array($relatorios)) {
                        $relatorioID           = $relatorioValor['relatorioID']; 
                        $relatorioUtmValor     = $relatorioValor['relatorioUtmValor']; 
                        $relatorioUtmSource    = $relatorioValor['relatorioUtmSource'];
                        $relatorioCampanhaNome = $relatorioValor['relatorioCampanhaNome'];
                        $relatorioDiasAtivo    = $relatorioValor['relatorioDiasAtivo'];
                        $relatorioEscalaData   = $relatorioValor['relatorioEscalaData'];
                        $relatorioEscalaQtde   = $relatorioValor['relatorioEscalaQtde'];
                        $relatorioEscalaStatus = $relatorioValor['relatorioEscalaStatus'];
                        $_clienteID            = $relatorioValor['_clienteID'];
                        $contaID               = $relatorioValor['_contaID'];
                        $itemTimezoneName      = '';
                        
                        // Se hora esta aplicado
                        $horaAtual = date('H');
                        $dataAtual = date('Y-m-d');
                        
                        $contaItens = mysqli_query($con, "SELECT * 
                   		    FROM `facebook_conta_itens` 
                   		    WHERE 
                   		        itemValor = '$contaID' 
                   		   LIMIT 1");
                   		   
                   		if ($contaItens) { 
                   		    $contaValor = mysqli_fetch_array($contaItens); 
                   		    if (isset($contaValor['itemID'])) {
                   		        $itemTimezoneName = $contaValor['itemTimezoneName'];
                   		        $_dataAtual       = date('d-m-Y H:i:s');
                   		        
                   		        if (!empty($itemTimezoneName)) {
                                    $fuso = new DateTimeZone($itemTimezoneName);
                                    $data = new DateTime($_dataAtual);
                                    $data->setTimezone($fuso);
                                    
                                    $horaAtual = $data->format('H');
                   		        }
                   		    }
                   		}
                   		
                        if ($escalaHoraInicio > 0 && $escalaHoraFinal > 0) {
                            if ($horaAtual < $escalaHoraInicio || $horaAtual > $escalaHoraFinal)
                                continue;
                                
                        } else if ($escalaHoraInicio > 0) {
                            if ($horaAtual < $escalaHoraInicio)
                                continue;
                        } else if ($escalaHoraFinal > 0) { 
                            if ($horaAtual > $escalaHoraFinal)
                                continue;
                        }
                        
                        $continua = true;
                        
                        // Verifica dias 
                        if (!empty($escalaDiasInicio) && !empty($escalaDiasFinal)) {
                            if ($escalaDiasInicio < $relatorioDiasAtivo || $escalaDiasInicio > $relatorioDiasAtivo )
                                $continua = false;    
                        } else if (!empty($escalaDiasInicio)) {
                            if ($escalaDiasInicio < $relatorioDiasAtivo)
                                $continua = false;    
                        } else if (!empty($escalaDiasFinal)) {
                            if ($escalaDiasFinal > $relatorioDiasAtivo)
                                $continua = false;    
                        }
                        
                        // Verifica data escala 
                        if ($relatorioEscalaData == $dataAtual)
                            continue;
                            
                        // Verifica no historico do banco
                        
                        $historico = mysqli_query($con, "SELECT * 
                            FROM cliente_gestao_escala_historico
                            WHERE 
                                historicoCampanhaNome = '$relatorioCampanhaNome' AND
                                DATE(historicoData)   = CURDATE()
                            LIMIT 1");
                            
                        if ($historico) {
                            $historicoTotal = mysqli_num_rows($historico);
                            if ($historicoTotal > 0)
                                continue;
                        }
                        
                        // Verifica a qtde aplicada
                        if ($dataAtual <> $relatorioEscalaData)
                            $relatorioEscalaQtde = 0;
                            
                        // Verifica limite dia
                        if ($limiteDia > 0) {
                            if ($relatorioEscalaQtde >= $limiteDia)
                                $continua = false;   
                        }
                        
                        if ($continua) {
                            
                            $campanhaAplicar = true;
                            
                            /* Historico de roi */
                            if ($escalaHistorico > 0) {
                                $historico = mysqli_query($con, "SELECT * 
                                    FROM adx_relatorios 
                                    WHERE 
                                        relatorioUtmValor = '$relatorioUtmValor' AND 
                                        relatorioUtmTipo  = 'campaign_id' AND 
                                        relatorioID      < $relatorioID
                                    ORDER BY relatorioData DESC
                                    LIMIT $escalaHistorico");
                                    
                                if ($historico) {
                                    $historicoTotal = mysqli_num_rows($historico);
                                    if ($historicoTotal == $escalaHistorico) {
                                        
                                        if ($escalaRoiInicio > 0) {
                                            $campanhaAplicar  = false;
                                            
                                            while ($historicoValor = mysqli_fetch_array($historico)) { 
                                                $historicoRoiFinalValor = $historicoValor['relatorioRoiFinalValor']; 
                                                if ($historicoRoiFinalValor > $escalaRoiInicio)
                                                    $campanhaAplicar = true; 
                                            }
                                        }
                                    }
                                }
                            }
                            
                            if ($campanhaAplicar) {
                                
                                $file = ABSPATH . 'data/config_' . $_clienteID . '.txt';
                        
                                if (is_file($file)) {
                                    $html = file_get_contents($file);
                                    $json = (array) json_decode($html, true); 
                                    $json = array_filter($json); 
                                } else {
                                    $json = array();
                                }
                                
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
                                        
                                        $data = array(
                                            'relatorioID'         => $relatorioID,
                                            'contaID'             => $contaID,
                                            'relatorioUtmSource'  => $relatorioUtmSource,
                                            'relatorioUtmValor'   => $relatorioUtmValor,
                                            'token'               => $accessToken,
                                            'valor'               => $escalaValor,
                                            'proxyHost'           => $proxyHost,
                                            'proxyUsuario'        => $proxyUsuario,
                                            'proxySenha'          => $proxySenha,
                                            'escalaID'            => $escalaID,
                                            'aplicarTipo'         => $escalaFormato,
                                            'campanhaNome'        => $relatorioCampanhaNome,
                                            'diasAtivo'           => $relatorioDiasAtivo,
                                            'horaAtual'           => $horaAtual,
                                            'dataAtual'           => $dataAtual,
                                            'escalaNome'          => $escalaNome,
                                            'escalaDiasInicio'    => $escalaDiasInicio,
                                            'escalaDiasFinal'     => $escalaDiasFinal,
                                            'escalaRoiInicio'     => $escalaRoiInicio,
                                            'escalaRoiFinal'      => $escalaRoiFinal,
                                            'escalaCustoInicio'   => $escalaCustoInicio,
                                            'escalaCustoFinal'    => $escalaCustoFinal,
                                            'escalaHoraInicio'    => $escalaHoraInicio,
                                            'escalaHoraFinal'     => $escalaHoraFinal,
                                            'escalaHistorico'     => $escalaHistorico,
                                            'escalaTimezone'      => $itemTimezoneName
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
}