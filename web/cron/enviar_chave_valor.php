<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

// https://cron.naveads.com/cron/enviar_chave_valor.php?tipo=campaign_id&data=2023-11-08
    
$tipo = '';
if (isset($_GET['tipo']))
    $tipo = $_GET['tipo'];

$arrDatas = array(
  	date('Y-m-d')
);

if (isset($_GET['data'])) {
    $dataAtual = $_GET['data'];
  	if (!empty($dataAtual))
      	$arrDatas = array($dataAtual);
}

$contas = mysqli_query($con, "SELECT *
    FROM adx_contas ");

if ($contas) {
    while ($contaValor = mysqli_fetch_array($contas)) {
        $contaSites = (array) json_decode($contaValor['contaSites'], true);
        $contaSites = array_filter($contaSites);
        
        $network_code    = $contaValor['contaCodigo'];
        $aplication_code = $contaValor['contaNome'];
        
        if (count($contaSites) > 0) {
            
            $linkCopiado = 'https://gestor.naveads.com/ajax.php?salvaGestaoUtmsCopias';
            $linkAtual   = 'https://adx.naveads.com/adx/nave_chave_valor.php';
            $linkTipo    = '';
            $linkCampo   = '';
          
            if ($tipo == 'campaign_id') {
                $linkCampo = 'gestaoUtm_campaign_id';
                $linkTipo  = 'gestao_campaign_id';
            
                
            } else if ($tipo == 'adset_id') {
                $linkCampo = 'gestaoUtm_adset_id';
                $linkTipo  = 'gestao_adset_id';
            
                
            } else if ($tipo == 'ad_id') {
                $linkCampo = 'gestaoUtm_ad_id';
                $linkTipo  = 'gestao_ad_id';
            }
            
            $arrCodigos = array();
            
          	foreach ($arrDatas as $dataValor) {
                $sql = "SELECT *
                    FROM gestao_utms 
                    WHERE 
                        gestaoUtmSiteEndereco IN ('" . implode("','", $contaSites) . "') AND 
                        DATE(gestaoUtmData)   = '$dataValor'
                    GROUP BY $linkCampo;";
              
                $lista = mysqli_query($con, $sql); 

                if ($lista) { 
                    $totalAd = mysqli_num_rows($lista);

                    while ($listaValor = mysqli_fetch_array($lista)) {
                        $gestaoUtmValor = $listaValor[$linkCampo];
                        $analyticID    = $listaValor['_analyticID'];

                        $arrCodigos[] = $gestaoUtmValor;
                    }

                    foreach ($arrCodigos as $gestaoUtmValor) {
                      	$_sql = "SELECT * 
                            FROM gestao_utms_copiados 
                            WHERE 
                                copiadoValor      = '$gestaoUtmValor' AND 
                                copiadoTipo       = '$linkTipo' AND 
                                DATE(copiadoData) = '$dataValor' 
                            LIMIT 1";
                      
                        $cadastrado = mysqli_query($con, $_sql); 
                        if ($cadastrado) {
                          	$total = mysqli_num_rows($cadastrado);
                            if ($total == 0) {

                                if (!empty($network_code)) {
                                    if (!empty($linkAtual)) {

                                        $params = array(
                                            'network_code'    => $network_code,
                                            'aplication_code' => $aplication_code,
                                            'chave_valor'     => $gestaoUtmValor,
                                            'data'            => $dataValor,
                                            'tipo'            => $tipo
                                        );

                                        $postData = http_build_query($params, '', '&');

                                        $ch = curl_init();

                                        curl_setopt($ch, CURLOPT_URL, $linkAtual);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                                      	curl_setopt($ch, CURLOPT_POST, TRUE);
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                                        $retorno = curl_exec($ch);
                                        curl_close($ch);
                                      
                                        if (preg_match('/VALUE_NAME_DUPLICATE/i', $retorno)) {
                                            // echo 'Chave já cadastrada<br />';
                                        } else {

                                            $ch = curl_init();

                                            $params = array(
                                                'itens'      => $gestaoUtmValor,
                                                'analyticID' => $analyticID,
                                                'tipo'       => $linkTipo,
                                                'data'       => $dataValor
                                            );

                                            $postData = http_build_query($params, '', '&');

                                            $ch = curl_init();

                                            curl_setopt($ch, CURLOPT_URL, $linkCopiado);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                                            curl_setopt($ch, CURLOPT_POST, TRUE);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

                                            $output = curl_exec($ch);
                                            curl_close($ch);
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

echo 'parar';