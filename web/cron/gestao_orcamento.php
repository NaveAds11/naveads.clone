<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'funcoes.php'); 

set_time_limit(0);

$pasta    = ABSPATH . 'cron/arquivos/gestao_orcamento/';
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
    $sql = "SELECT *
        FROM adx_relatorios A
            INNER JOIN gestao_utms ON gestaoUtm_adset_id = relatorioUtmValor
        WHERE
            relatorioUtmTipo = 'adset_id' AND 
            relatorioData    = CURDATE()
        GROUP BY relatorioUtmValor
        ORDER BY relatorioID DESC;";
    
    $itens = mysqli_query($con, $sql);
    if ($itens) {
        while ($itemValor = mysqli_fetch_array($itens)) {
            
            $relatorioClienteID    = (int) $itemValor['_clienteID'];
            $contaID               = $itemValor['_contaID'];
            $relatorioID           = $itemValor['relatorioID'];
            $relatorioUtmValor     = $itemValor['relatorioUtmValor'];
            $relatorioCampanhaNome = $itemValor['relatorioCampanhaNome']; 
            $criativoAdsetName     = $itemValor['gestaoUtm_adset_name']; 
            
            if ($relatorioClienteID > 0) {
                $file = ABSPATH . 'data/config_' . $relatorioClienteID . '.txt';
                
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
                
                $arrToken = array();
                foreach ($accessToken as $tokenValor) {
                    $arrToken[] = $tokenValor['token'];
                }
                
                shuffle($arrToken);
                
                $accessToken = $arrToken[0];
                
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
                
                $link = 'http://54.37.11.148:8015/api/conjuntos/orcamento/' . $relatorioUtmValor;
                
                $data = array(
                    'link'               => $link,
                    'accessToken'        => $accessToken,
                    'contaID'            => $contaID,
                    'proxyHost'          => $proxyHost,
                    'proxyUsuario'       => $proxyUsuario,
                    'proxySenha'         => $proxySenha,
                    'relatorioUtmValor'  => $relatorioUtmValor,
                    'relatorioID'        => $relatorioID,
                    'campanhaNome'       => $relatorioCampanhaNome
                );
                
                $arquivo = $pasta . $relatorioUtmValor . '.txt';
                if (!is_file($arquivo)) {
                    file_put_contents($arquivo, json_encode($data));
                }
            }
        }
    }
}