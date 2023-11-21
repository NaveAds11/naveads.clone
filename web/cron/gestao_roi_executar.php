<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'funcoes.php'); 

set_time_limit(0);

$dataAtual = date('Y-m-d');
$horaAtual = date('H');

$pasta    = ABSPATH . 'cron/arquivos/gestao_roi/';
$arquivos = glob($pasta . '*.txt');

$arrArquivos = array();

foreach ($arquivos as $arquivoValor) {
    if (preg_match('/\.txt/', $arquivoValor)) {
        $arrArquivos[] = $arquivoValor;
    }    
}

shuffle($arrArquivos);

$total = count($arrArquivos);

if ($total == 0) {
    echo 'parar';
    
} else {
    foreach ($arrArquivos as $arquivoValor) {
        if (is_file($arquivoValor)) {
            $json = file_get_contents($arquivoValor);
            $json = (array) json_decode($json, true);
            $json = array_filter($json);
            
            unlink($arquivoValor);
          
            $link                = $json['link'];
            $relatorioID         = $json['relatorioID'];
            $campanhaID          = $json['campanhaID'];
            $contaID             = $json['contaID'];
            $relatorioUtmSource  = $json['relatorioUtmSource'];
            $token               = $json['token'];
            $proxyHost           = $json['proxyHost'];
            $proxyUsuario        = $json['proxyUsuario'];
            $proxySenha          = $json['proxySenha'];
            $cadastroStatus      = $json['cadastroStatus'];
            $cadastroBuscaStatus = $json['cadastroBuscaStatus'];
            $cadastroBuscaTempo  = $json['cadastroBuscaTempo'];
            $cadastroID          = $json['cadastroID'];
          	$relatorioData       = $json['relatorioData'];
          
            $cookies = '';
          	if (isset($json['cookies']))
              	$cookies = $json['cookies'];
          
            $aadvid = '';
          	if (isset($json['aadvid']))
            	$aadvid = $json['aadvid'];
          
            if ($relatorioUtmSource == 'tiktok') {
                
                $post = array(
                    $campanhaID
                );
                
                $post = json_encode($post);
                
                $header = array(
                    'Cookies:' . $cookies,
                    'ProxyHost:' . $proxyHost,
                    'ProxyUsuario:' . $proxyUsuario,
                    'ProxySenha:' . $proxySenha,
                    'Content-Type:application/json',
                    'Content-Length:' . strlen($post),
                    'Aadvid:' . $aadvid
                );
                
                $ch = curl_init(); 
            
                curl_setopt($ch, CURLOPT_URL, $link);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                
                $output = curl_exec($ch);
                
                curl_close($ch);
                
                $data = array(
                    'relatorioCampanhaStatus' => $cadastroStatus,
                    'relatorioRoboStatus'     => 1,
                    'relatorioRoboData'       => date('Y-m-d H:i:s'),
                    '_gestaoRoiID'            => $cadastroID
                );
                
                if ($cadastroStatus == 'ACTIVE') {
                    $data['relatorioRoboStatus'] = 2;
                    $data['_gestaoRoiID']        = 0;
                }
                
                if ($cadastroStatus == 'ACTIVE') {
                    $where = "relatorioID = $relatorioID AND (relatorioData = CURDATE() OR DATE(NOW() - INTERVAL 1 DAY))";
                } else {
                    $where = "relatorioID = $relatorioID AND relatorioData = CURDATE()";
                }
                
                $_retorno = update('adx_relatorios', $data, $where);
                if ($_retorno) {
                    
                    $campanhaStatus = mysqli_query($con, "SELECT * 
                        FROM cliente_campanhas 
                        WHERE 
                            _campanhaID = '$campanhaID' 
                        LIMIT 1");
                        
                    if ($campanhaStatus) {
                        $campanhaStatusValor = mysqli_fetch_array($campanhaStatus);
                        if (isset($campanhaStatusValor['cadastroID'])) {
                            $cadastroID = $campanhaStatusValor['cadastroID'];
                                 
                            $data = array(
                                'cadastroCampanhaAtivada' => 2
                            );
                            
                            if ($cadastroStatus == 'ACTIVE')
                                $data['cadastroCampanhaAtivada'] = 1;
                                
                            update('cliente_campanhas', $data, 'cadastroID = ' . $cadastroID);
                        }
                    } 
                  
                  	$statusAplicar = 'PAUSED';
                 	if ($cadastroStatus == 'PAUSED')
                   		$statusAplicar = 'ACTIVE';
                  
                  	mysqli_query($con, "UPDATE tiktok_custos 
                      	SET custoStatusCampanha  = '$statusAplicar'
                        WHERE
                        	custoCampanhaID = '$relatorioUtmValor' AND 
                            custoData       = '$relatorioData'
                     	LIMIT 1;");
                } 
                
            } else {
            
                $data = array(
                    $campanhaID
                );
                
                $data = json_encode($data);
                
                $header = array(
                    'AccessToken: ' . $token,
                    'CodigoAct: ' . $contaID,
                    'Content-Type: application/json',
                    'ProxyHost: ' . $proxyHost,
                    'ProxyUsuario: ' . $proxyUsuario,
                    'ProxySenha: ' . $proxySenha,
                    'Content-Length: ' . strlen($data),
                    'CadastroID: ' . rand(100000, 999999)
                );
                
                $ch = curl_init(); 
              
                curl_setopt($ch, CURLOPT_URL, $link);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                 
                curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
                
                $output = curl_exec($ch);
                curl_close($ch);
                
                $data = array(
                    'relatorioCampanhaStatus' => $cadastroStatus,
                    'relatorioRoboStatus'     => 1,
                    'relatorioRoboData'       => date('Y-m-d H:i:s'),
                    '_gestaoRoiID'            => $cadastroID
                );
                
                if ($cadastroStatus == 'ACTIVE') {
                    $data['relatorioRoboStatus'] = 2;
                    $data['_gestaoRoiID']        = 0;
                }
                
                $where = "relatorioID = $relatorioID AND relatorioData = '$relatorioData'";
              
                $_retorno = update('adx_relatorios', $data, $where);
                if ($_retorno) {
                    
                    $campanhaStatus = mysqli_query($con, "SELECT * 
                        FROM cliente_campanhas 
                        WHERE 
                            _campanhaID = '$campanhaID' 
                        LIMIT 1");
                        
                    if ($campanhaStatus) {
                        $campanhaStatusValor = mysqli_fetch_array($campanhaStatus);
                        if (isset($campanhaStatusValor['cadastroID'])) {
                            $cadastroID = $campanhaStatusValor['cadastroID'];
                                 
                            $data = array(
                                'cadastroCampanhaAtivada' => 2
                            );
                            
                            if ($cadastroStatus == 'ACTIVE')
                                $data['cadastroCampanhaAtivada'] = 1;
                                
                            update('cliente_campanhas', $data, 'cadastroID = ' . $cadastroID);
                        }
                    } 
                    
                    $facebookStatus = mysqli_query($con, "SELECT * 
                        FROM facebook_itens 
                        WHERE 
                            itemCampanhaID = '$campanhaID' AND 
                            itemData       = '$relatorioData'
                        LIMIT 1");
                        
                    if ($facebookStatus) {
                        $campanhaStatusValor = mysqli_fetch_array($facebookStatus);
                        if (isset($campanhaStatusValor['itemID'])) {
                          
                            $statusAplicar = 'PAUSED';
                            if ($cadastroStatus == 'PAUSED')
                                $statusAplicar = 'ACTIVE';
                          
                          	mysqli_query($con, "UPDATE facebook_itens
                                SET itemStatus = '$cadastroStatus'
                                WHERE 
                                    itemCampanhaID = '$campanhaID' AND 
                                    itemData       = '$relatorioData' ");
                        }
                    }
                } 
            }
            
            break;
        }
    }
    
    /* Atualiza status */
    
    mysqli_query($con, "UPDATE `adx_relatorios` SET relatorioRoboStatus = 1 WHERE relatorioCampanhaStatus = 'PAUSED';");
    
    mysqli_query($con, "UPDATE `adx_relatorios` SET relatorioRoboStatus = 2, _gestaoRoiID = 0 WHERE relatorioCampanhaStatus = 'ACTIVE';");
}