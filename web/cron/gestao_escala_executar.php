<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'funcoes.php'); 

set_time_limit(0);

$dataAtual = date('Y-m-d');
$horaAtual = date('H');

$pasta    = ABSPATH . 'cron/arquivos/gestao_escala/';
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
            
            $escalaLimiteGasto    = getConfig('escala_limite_gasto');
          	$facebookApiPrincipal = getConfig('facebook_api_principal');
          	$facebookApiPrincipal = rtrim($facebookApiPrincipal, '/') . '/';
                
            $relatorioID        = $json['relatorioID'];
            $campanhaID         = $json['campanhaID'];
            $contaID            = $json['contaID'];
            $relatorioUtmSource = $json['relatorioUtmSource'];
            $relatorioUtmValor  = $json['relatorioUtmValor'];
            $token              = $json['token'];
            $aplicarValor       = $json['valor'];
            $proxyHost          = $json['proxyHost'];
            $proxyUsuario       = $json['proxyUsuario'];
            $proxySenha         = $json['proxySenha'];  
            $escalaID           = $json['escalaID'];
            $aplicarTipo        = $json['aplicarTipo'];
            $campanhaNome       = $json['campanhaNome'];
            
            $escalaNome = '';
            if (isset($json['escalaNome']))
                $escalaNome = $json['escalaNome'];
                
            $escalaDiasInicio = '';
            if (isset($json['escalaDiasInicio']))
                $escalaDiasInicio = $json['escalaDiasInicio'];
                
            $escalaDiasFinal = '';
            if (isset($json['escalaDiasFinal']))
                $escalaDiasFinal = $json['escalaDiasFinal'];
                
            $escalaRoiInicio = '';
            if (isset($json['escalaRoiInicio']))
                $escalaRoiInicio = $json['escalaRoiInicio'];
                
            $escalaRoiFinal = '';
            if (isset($json['escalaRoiFinal']))
                $escalaRoiFinal = $json['escalaRoiFinal'];
                
            $escalaCustoInicio = '';
            if (isset($json['escalaCustoInicio']))
                $escalaCustoInicio = $json['escalaCustoInicio'];
                
            $escalaCustoFinal = '';
            if (isset($json['escalaCustoFinal']))
                $escalaCustoFinal = $json['escalaCustoFinal'];
                
            $escalaHoraInicio = '';
            if (isset($json['escalaHoraInicio']))
                $escalaHoraInicio = $json['escalaHoraInicio'];
                
            $escalaHoraFinal = '';
            if (isset($json['escalaHoraFinal']))
                $escalaHoraFinal = $json['escalaHoraFinal'];
                
            $escalaHistorico = '';
            if (isset($json['escalaHistorico']))
                $escalaHistorico = $json['escalaHistorico'];
                
            $escalaTimezone = '';
            if (isset($json['escalaTimezone']))
                $escalaTimezone = $json['escalaTimezone'];
                
            $historicoGastoSuperior = 2;
            
            $accessToken = $token;
                                                
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
            
            $header = array(
                'AccessToken:' . $accessToken,
                'CodigoAct:' . $contaID,
                'Content-Type:application/json',
                'ProxyHost:' . $proxyHost,
                'ProxyUsuario:' . $proxyUsuario,
                'ProxySenha:' . $proxySenha,
                'CadastroID:' . rand(100000, 999999)
            );
            
            
            $link = $facebookApiPrincipal . 'api/conjuntos/orcamento/' . $relatorioUtmValor;
            
            $ch = curl_init(); 
            
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
            
            $output = curl_exec($ch);
            curl_close($ch);
            
            if (!empty($output)) {
                $valorAtual = preg_replace('/[^0-9]/', '', $output);
                $valorAtual = substr($valorAtual, 0, (strlen($valorAtual) - 2)) . '.' . substr($valorAtual, (strlen($valorAtual) - 2), strlen($valorAtual));
                $valorAtual = (float) $valorAtual;
                
                if ($aplicarTipo == 1) {
                    $novoValor = $valorAtual + ($valorAtual / 100 * $aplicarValor);
                } else {
                    $novoValor = $valorAtual - ($valorAtual / 100 * $aplicarValor);
                }
                
                $aplicarOrcamento = true;
                
                if ($escalaLimiteGasto > 0) {
                    if ($novoValor > $escalaLimiteGasto) { 
                        $novoValor = $escalaLimiteGasto;
                        
                        $aplicarOrcamento = false;
                    }
                }
                
                if (!$aplicarOrcamento)
                    $historicoGastoSuperior = 1;
                    
                $novoValor = number_format($novoValor, 2, '.', '');
                
                $_novoValor  = preg_replace('/[^0-9]/', '', $novoValor);
                $linkAplicar = $facebookApiPrincipal . 'api/conjuntos/orcamento/' . $relatorioUtmValor . '/' . $_novoValor;
                
                $ch = curl_init(); 
            
                curl_setopt($ch, CURLOPT_URL, $linkAplicar);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
                
                $output = curl_exec($ch);
                curl_close($ch);
                
                $data = array(
                    'relatorioOrcamentoValor'         => $novoValor,
                    'relatorioOrcamentoValorAnterior' => $valorAtual,
                    'relatorioEscalaStatus'           => 1,
                    'relatorioEscalaData'             => date('Y-m-d'),
                    'relatorioEscalaAtualizadoEm'     => date('Y-m-d H:i:s')
                );
                
                update('adx_relatorios', $data, 'relatorioID = ' . $relatorioID);
                
                /* Salva historico */
                $data = array(
                    'historicoData'                    => date('Y-m-d H:i:s'),
                    'historicoCampanhaID'              => $relatorioUtmValor,
                    'historicoCampanhaConta'           => $contaID,
                    'historicoEscalaTimezone'          => $escalaTimezone,
                    'historicoValor'                   => $aplicarValor,
                    'historicoEscalaNome'              => $escalaNome,
                    'historicoEscalaID'                => $escalaID,
                    'historicoCampanhaNome'            => $campanhaNome,
                    'historicoGastoSuperior'           => $historicoGastoSuperior
                ); 
                
                insert('cliente_gestao_escala_historico', $data);
                
                // Remove
                mysqli_query($con, "DELETE FROM cliente_gestao_escala_historico WHERE DATE(historicoData) < DATE_SUB(NOW(), INTERVAL 7 DAY)");
            }
            
            break;
        }
    }
}