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
  	$_link = getConfig('facebook_api_principal'); 
  	$_link = rtrim($_link, '/') . '/api/';
  
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
          
            $cadastroRoiHistorico   = (int) $cadastroRoiHistorico;
            
            $cadastroRoiGeralInicio = (float) $cadastroRoiGeralInicio;
            $cadastroRoiGeralFinal  = (float) $cadastroRoiGeralFinal;
            
            $cadastroCustoInicio    = (float) $cadastroCustoInicio;
            $cadastroCustoFinal     = (float) $cadastroCustoFinal;
            
            $cadastroRoiInicio      = (float) $cadastroRoiInicio;
            $cadastroRoiFinal       = (float) $cadastroRoiFinal;
            
            $cadastroHoraInicio     = (int) $cadastroHoraInicio;
            $cadastroHoraFinal      = (int) $cadastroHoraFinal;
            
            if (empty($cadastroRoiInicio) || ($cadastroRoiInicio == 0))
                $cadastroRoiInicio = '0.00';
                
            if (empty($cadastroCustoInicio) || ($cadastroCustoInicio == 0))
                $cadastroCustoInicio = '0.00';

            $filtroValido = true;
            
            $data = array(
                'cadastroRodouEm' => date('Y-m-d H:i:s')
            );
            
            update('cliente_robo_roi', $data, 'cadastroID = ' . $cadastroID);

          	$link = 'https://gestor.naveads.com/api/gestaoRoi.php';
          
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          
          	curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "regra=" . $cadastroID);
            
          	$retorno = curl_exec($ch);
            curl_close($ch);
          
            $lista = (array) json_decode($retorno, true);
            $lista = array_filter($lista);
          
            foreach ($lista as $itemIndex => $relatorioValor) {
                        
                $relatorioID           = $relatorioValor['relatorioID']; 
                $relatorioUtmValor     = $relatorioValor['relatorioUtmValor']; 
                $relatorioData         = $relatorioValor['relatorioData']; 
                $relatorioUtmSource    = $relatorioValor['relatorioUtmSource'];
                $relatorioCampanhaNome = $relatorioValor['relatorioCampanhaNome'];
                $relatorioTipo         = $relatorioValor['relatorioTipo'];
                $_clienteID            = $relatorioValor['_clienteID'];
                $contaID               = $relatorioValor['_contaID'];
                $dataBusca             = $relatorioValor['dataBusca'];

                $cookies = ''; 
                $aadvid  = '';
             
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