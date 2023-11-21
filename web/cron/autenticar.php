<?php
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$query = mysqli_query($con, "SELECT *
    FROM contas
    LIMIT 100;");

if ($query) {
    while ($itemValor = mysqli_fetch_array($query)) { 
        $contaID       = $itemValor['contaID'];
        $client_id     = $itemValor['contaClienteID'];
        $client_secret = $itemValor['contaClienteSecret'];
        $access_token  = $itemValor['contaAccessToken'];
        $refresh_token = $itemValor['contaRefreshToken'];
      
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $access_token); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $error_response = curl_exec($ch);
        $array = json_decode($error_response);

        if (isset($array->issued_to)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"https://accounts.google.com/o/oauth2/token");
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type'    => 'refresh_token',
            ]));

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close ($ch);

            $json = (array) json_decode($response, true);
            $json = array_filter($json);
            
            if (isset($json['access_token'])) {
                $data = array(
                    'contaAccessToken'  => $json['access_token'],
                    'contaTokenTempo'   => $json['expires_in'], 
                    'contaTokenTipo'    => $json['token_type'],
                    'contaTempoExpira'  => strtotime('now') + $json['expires_in']
                );

                update('contas', $data, 'contaID = ' . $contaID);
            }
        }
    }
}