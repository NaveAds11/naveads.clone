<?php 
header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$pasta    = ABSPATH . 'cron/arquivos/tiktok_custos/';
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
    
    $dataInicio = date('Y-m-d', strtotime('-1 days'));
    $dataFinal  = date('Y-m-d');
    
    $itens = mysqli_query($con, "SELECT * 
        FROM tiktok_contas
        WHERE 
            contaStatus = 1
        ORDER BY RAND()");
        
    if ($itens) {
        while ($itemValor = mysqli_fetch_array($itens)) {
            $contaNome      = $itemValor['contaNome'];
            $contaHost      = $itemValor['contaHost'];
            $contaUsuario   = $itemValor['contaUsuario'];
            $contaSenha     = $itemValor['contaSenha'];
            $contaNavegador = $itemValor['contaNavegador'];
            $contaContas    = $itemValor['contaContas'];
            
            $contaContas = (array) json_decode($contaContas, true);
            $contaContas = array_filter($contaContas);
            
            foreach ($contaContas as $contaValor) {
                $contaID = $contaValor['codigo'];
                
                $arquivo = $pasta . $contaID . '.txt';
                if (is_file($arquivo))
                    continue;
                    
                $data = array(
                    'contaNome'      => $contaNome,
                    'contaHost'      => $contaHost,
                    'contaUsuario'   => $contaUsuario,
                    'contaSenha'     => $contaSenha,
                    'contaNavegador' => $contaNavegador,
                    'contaID'        => $contaID,
                    'data_inicio'    => $dataInicio,
                    'data_final'     => $dataFinal
                );
                    
                file_put_contents($arquivo, json_encode($data));    
            }
        }
    }
}