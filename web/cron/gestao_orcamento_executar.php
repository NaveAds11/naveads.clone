<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'funcoes.php'); 

set_time_limit(0);

$pasta    = ABSPATH . 'cron/arquivos/gestao_orcamento/';
$arquivos = glob($pasta . '*.txt');

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
            
            $link = '';
            if (isset($json['link']))
                $link = $json['link'];
                
            $accessToken = '';
            if (isset($json['accessToken']))
                $accessToken = $json['accessToken'];
                
            $contaID = '';
            if (isset($json['contaID']))
                $contaID = $json['contaID'];
                
            $proxyHost = '';
            if (isset($json['proxyHost']))
                $proxyHost = $json['proxyHost'];
                
            $proxyUsuario = '';
            if (isset($json['proxyUsuario']))
                $proxyUsuario = $json['proxyUsuario'];
                
            $proxySenha = '';
            if (isset($json['proxySenha']))
                $proxySenha = $json['proxySenha'];
                
            $relatorioUtmValor  = '';
            if (isset($json['relatorioUtmValor']))
                $relatorioUtmValor = $json['relatorioUtmValor'];
                
            $relatorioID = '';
            if (isset($json['relatorioID']))
                $relatorioID = $json['relatorioID'];
                
            $relatorioID = (int) $relatorioID;
                
            if ($relatorioID > 0) { 
                if (!empty($accessToken)) {
                    $ch = curl_init(); 
                    
                    $header = array(
                        'AccessToken:' . $accessToken,
                        'CodigoAct:' . $contaID,
                        'Content-Type:application/json',
                        'ProxyHost:' . $proxyHost,
                        'ProxyUsuario:' . $proxyUsuario,
                        'ProxySenha:' . $proxySenha,
                        'CadastroID:' . rand(100000, 999999)
                    );
                                                        
                    curl_setopt($ch, CURLOPT_URL, $link);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_HEADER, FALSE);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                     
                    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
                    
                    $output = curl_exec($ch);
                    curl_close($ch);
                    
                    if (!empty($output)) {
                        $valorAtual = preg_replace('/[^0-9]/', '', $output);
                        $valorAtual = substr($valorAtual, 0, (strlen($valorAtual) - 2)) . '.' . substr($valorAtual, (strlen($valorAtual) - 2), strlen($valorAtual));
                        
                        $valorAtual = number_format($valorAtual, 2, '.', '');
                        
                        $data = array(
                            'relatorioOrcamentoValor' => $valorAtual
                        );
                        
                        update('adx_relatorios', $data, 'relatorioID = ' . $relatorioID);
                    }
                }
            }
            
            break;
        }
    }
}