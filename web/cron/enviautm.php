<?php 
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST');
include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$dataAtual = date('Y-m-d');
if (isset($_GET['data']))
    $dataAtual = $_GET['data'];
    
$tipo = '';
if (isset($_GET['tipo']))
    $tipo = $_GET['tipo'];

$contas = mysqli_query($con, "SELECT *
    FROM adx_contas ");

if ($contas) {
    while ($contaValor = mysqli_fetch_array($contas)) {
        $contaSites = (array) json_decode($contaValor['contaSites'], true);
        $contaSites = array_filter($contaSites);
        
        $network_code    = $contaValor['contaCodigo'];
        $aplication_code = $contaValor['contaNome'];
        
        if (count($contaSites) > 0) {
            
            $linkCopiado = site_url('ajax.php?salvaGestaoUtmsCopias');
            $linkAtual   = 'https://adx.naveads.com/adx/nave_chave_valor.php';
            $linkTipo    = '';
            
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
            
            $linkAtual = $linkAtual . '?tipo=' . $tipo;
            
            $arrCodigos = array();
            
            $sql = "SELECT *
                FROM gestao_utms 
                WHERE 
                    gestaoUtmSiteEndereco IN ('" . implode("','", $contaSites) . "') AND 
                    DATE(gestaoUtmData)   = '$dataAtual'
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
                    $cadastrado = mysqli_query($con, "SELECT * 
                        FROM gestao_utms_copiados 
                        WHERE 
                            copiadoValor = '$gestaoUtmValor' AND 
                            copiadoTipo  = '$linkTipo' 
                        LIMIT 1"); 
                    
                    if ($cadastrado) {
                        if (mysqli_num_rows($cadastrado) == 0) {
                    
                            if (!empty($network_code)) {
                                if (!empty($linkAtual)) {
                                    $ch = curl_init();
                                    
                                    $_link = $linkAtual . '&network_code=' . $network_code . '&aplication_code=' . $aplication_code . '&chave_valor=' . $gestaoUtmValor;
                                    
                                    curl_setopt($ch, CURLOPT_URL, $_link);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                                    $retorno = curl_exec($ch);
                                    curl_close($ch);
                                    
                                    if (preg_match('/VALUE_NAME_DUPLICATE/i', $retorno)) {
                                        // echo 'Chave já cadastrada<br />';
                                    } else {
                                        
                                        $ch = curl_init();
                            
                                        $params = array(
                                            'itens'      => $gestaoUtmValor,
                                            'analyticID' => $analyticID,
                                            'tipo'       => $linkTipo
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

echo 'parar';