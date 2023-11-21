<?php
header("Access-Control-Allow-Origin: *");

set_time_limit(0);

include('../config.php'); 
include(ABSPATH .'/funcoes.php');

$query = mysqli_query($con, "SELECT * 
	FROM cliente_campanha_copias 
	WHERE 
		copiaCriado = 2 
	LIMIT 1");

if ($query) {
	$itemValor = mysqli_fetch_array($query);
	if (isset($itemValor['copiaID'])) {

		$accessToken          = $itemValor['copiaAccessToken'];
		$contaID              = $itemValor['copiaContaID'];
		$proxyHost            = $itemValor['copiaProxyHost'];
		$proxyUsuario         = $itemValor['copiaProxyUsuario'];
		$proxySenha           = $itemValor['copiaProxySenha'];
		$facebookApiPrincipal = $itemValor['copiaFacebookApiPrincipal'];
		$copiaID              = $itemValor['copiaID'];
		$copiaNumero          = $itemValor['copiaNumero'];

		$header = array(
	        'AccessToken:' . $accessToken,
	        'CodigoAct:' . $contaID,
	        'Content-Type:application/json',
	        'ProxyHost:' . $proxyHost,
	        'ProxyUsuario:' . $proxyUsuario,
	        'ProxySenha:' . $proxySenha,
	        'CadastroID:' . rand(100000, 999999)
	    );

	    $data = '{
	        "Prefixo":"Clone-",
	        "Sufixo":"-Cópia' . $copiaNumero . '"}';

	    $ch = curl_init(); 

	    curl_setopt($ch, CURLOPT_URL, $facebookApiPrincipal);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($ch, CURLOPT_HEADER, FALSE);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

	    $output = curl_exec($ch);
	    curl_close($ch);

		if (!empty($output)) {   
	        $retornoJson = (array) json_decode($output, true);
	        $retornoJson = array_filter($retornoJson);
	    
	        $copiadoCampanhaID = '';
	        if (isset($retornoJson['copied_campaign_id']))
	            $copiadoCampanhaID = $retornoJson['copied_campaign_id'];
	        
	        $copiadoCampanhaItens = '';
	        if (isset($retornoJson['ad_object_ids']))
	            $copiadoCampanhaItens = $retornoJson['ad_object_ids'];
	        
	        $data = array(
	            'copiaCampanhaID' => $copiadoCampanhaID,
	            'copiaItens'      => json_encode($copiadoCampanhaItens),
	            'copiaCriado'     => 1
	        );
	    
	        update('cliente_campanha_copias', $data, 'copiaID = ' . $copiaID);
	    }		
	}
}