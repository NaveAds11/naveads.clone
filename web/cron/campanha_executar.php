<?php
header("Access-Control-Allow-Origin: *");

set_time_limit(0);

include('../config.php'); 
include(ABSPATH .'/funcoes.php');

$pasta    = ABSPATH . 'cron/arquivos/campanhas_facebook/';
$arquivos = glob($pasta . '*.txt');

$arrArquivos = array();

foreach ($arquivos as $arquivoValor) {
    preg_match('/campanha-(\d+)-(\d+)\.txt/', $arquivoValor, $match);
    if (isset($match[2])) {
        $arrArquivos[] = $arquivoValor;
    }
}
    
$total = count($arrArquivos);
if ($total == 0) {
    echo 'parar';
} else {
    
    $portaNome = '';
    if (isset($_GET['link']))
        $portaNome = $_GET['link'];
    
    if (!empty($portaNome)) {
        
        $portas = mysqli_query($con, "SELECT *
            FROM cron_campanhas 
            WHERE 
                cronNome = '$portaNome' 
            LIMIT 1");
            
        if ($portas) {
            $portaValor = mysqli_fetch_array($portas);
            if (isset($portaValor['cronID'])) {
                $link = $portaValor['cronLink'];
                $link = rtrim($link, '/') . '/api/anuncios';
                
                foreach ($arrArquivos as $arquivoValor) {
                    if (is_file($arquivoValor)) {
                        $html = file_get_contents($arquivoValor);
                        $json = (array) json_decode($html, true);
                        
                        $data = json_encode($json['post']);
                
                        $cadastroID = $json['cadastroID'];
                        
                        unlink($arquivoValor);
                        
                        $ch = curl_init(); 
                        
                        $header = array(
                            'AccessToken: ' . $json['token'],
                            'CodigoAct: ' . $json['conta'],
                            'Content-Type: application/json',
                            'ProxyHost: ' . $json['host'],
                            'ProxyUsuario: ' . $json['usuario'],
                            'ProxySenha: ' . $json['senha'],
                            'Content-Length: ' . strlen($data),
                            'CadastroID: ' . $cadastroID,
                        );
                        
                        curl_setopt($ch, CURLOPT_URL, $link);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                        curl_setopt($ch, CURLOPT_HEADER, FALSE);
                        curl_setopt($ch, CURLOPT_POST, TRUE);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                         
                        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
                        
                        $output = curl_exec($ch);
                        curl_close($ch);
                        
                        $json = (array) json_decode($output, true);
                        $json = array_filter($json);
                        
                        $campanhaID = 0;
                        if (isset($json['campanhaId']))
                            $campanhaID = $json['campanhaId'];
                            
                        $data = array(
                            'cadastroFila'        => 2,
                            'cadastroRetorno'     => $output,
                            'cadastroRetornoData' => date('Y-m-d H:i:s'),
                            '_campanhaID'         => $campanhaID
                        );
                        
                        $retorno = update('cliente_campanhas', $data, 'cadastroID = ' . $cadastroID);
                        
                        if ($retorno) {
                            $arquivo = 'campanhas/' . $cadastroID . '.txt';
                            if (!is_file($arquivo))
                                file_put_contents($arquivo, $output);
                        }
                        
                        break;
                    }
                }
            }
        }
    }
}