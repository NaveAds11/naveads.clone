<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

    $sites = mysqli_query($con, "SELECT *
        FROM analytics ");

    if ($sites) {
        while ($siteValor = mysqli_fetch_array($sites)) {
            $analyticID       = $siteValor['analyticID'];
            $token            = $siteValor['analyticSiteToken'];
            $analyticEndereco = $siteValor['analyticEndereco'];
            $analyticNome     = $siteValor['analyticNome'];
            
            if (empty($token))
                continue;
          
            $link = 'https://' . $analyticEndereco . '/get_utms?token=' . $token . '&t=' . time();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $retorno = curl_exec($ch);
            curl_close($ch);
          
            $json = (array) json_decode($retorno, true);
            $json = array_filter($json);

            foreach ($json as $itemValor) {
                $gestaoToken = $itemValor['gestaoToken'];
                $gestaoData  = $itemValor['gestaoData'];
                $gestaoData  = date('Y-m-d', strtotime($gestaoData));

                if (empty($gestaoToken))
                  continue;

                $adsetName = urldecode($itemValor['gestao_adset_name']);
                $adsetAdid = $itemValor['gestao_ad_id'];

                $sql = "SELECT *
                            FROM gestao_utms
                            WHERE 
                                gestaoUtm_ad_id = '$adsetAdid' ";

                $cadastrado = mysqli_query($con, $sql);

                if ($cadastrado) {
                  	if (mysqli_num_rows($cadastrado) == 0) {

                    	$data = array(
                      		'gestaoUtm_utm_source'    => $itemValor['gestao_utm_source'],
                      		'gestaoUtm_utm_medium'    => $itemValor['gestao_utm_medium'],
                      		'gestaoUtm_utm_term'      => $itemValor['gestao_utm_term'],
                      		'gestaoUtm_utm_content'   => $itemValor['gestao_utm_content'],
                      		'gestaoUtm_utm_pixel'     => $itemValor['gestao_utm_pixel'],
                      		'gestaoUtm_campaign_id'   => $itemValor['gestao_campaign_id'],
                      		'gestaoUtm_campaign_name' => $itemValor['gestao_campaign_name'],
                      		'gestaoUtm_adset_name'    => $adsetName,
                      		'gestaoUtm_adset_id'      => $itemValor['gestao_adset_id'] ,
                      		'gestaoUtm_ad_id'         => $itemValor['gestao_ad_id'],
                      		'gestaoUtm_ad_name'       => $itemValor['gestao_ad_name'],
                      		'gestaoUtm_utm_campaign'  => $itemValor['gestao_utm_campaign'],
                      		'gestaoUtmSiteEndereco'   => $itemValor['gestaoSiteEndereco'],
                      		'gestaoUtmTipo'           => $itemValor['gestaoTipo'],
                      		'gestaoUtmData'           => $itemValor['gestaoData'],
                      		'gestaoUtmToken'          => $itemValor['gestaoToken'],
                      		'_analyticID'             => $analyticID
                    	);
                    
                    	insert('gestao_utms', $data);
                  	}
                }
           	}
        }
    }