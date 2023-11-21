<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$dataAtual = date('Y-m-d');

$query = mysqli_query($con, "SELECT *
    FROM contas
    LIMIT 100;");

if ($query) {
    while ($itemValor = mysqli_fetch_array($query)) { 
        $contaID            = $itemValor['contaID'];
    	$contaAccessToken   = $itemValor['contaAccessToken'];
    	$contaClienteID     = $itemValor['contaClienteID'];
    	$contaClienteSecret = $itemValor['contaClienteSecret'];
    	$contaPub           = $itemValor['contaPub'];
    	$contaScope         = $itemValor['contaScope'];
    	$contaApiKey        = $itemValor['contaApiKey'];
    	$contaListaContas   = $itemValor['contaListaContas'];

    	$arr = (array) adsenseReportingSites($contaAccessToken, $contaPub, 'MONTH_TO_DATE'); 
    	
    	$arrTopo = array();
    	if (isset($arr['headers'])) { 
	     	foreach ($arr['headers'] as $topoValor) {
	    		$arrTopo[] = $topoValor['name'];
	     	}
	  	}

		if (isset($arr['rows'])) {
    		foreach ($arr['rows'] as $linhaCells) {
    			$data            = array();
                $siteData        = '';
    			$siteDominioNome = '';
    				
    			foreach ($linhaCells['cells'] as $linhaIndex => $linhaValor) {
    				$linhaCampo = $arrTopo[$linhaIndex];
    				
    				if ($linhaCampo == 'DOMAIN_NAME') {
    					$data['siteDominioNome'] = $linhaValor['value'];
    					
    					$siteDominioNome = $linhaValor['value'];
    				}

    				if ($linhaCampo == 'ESTIMATED_EARNINGS')
                        $data['siteGanhosEstimatimados'] = $linhaValor['value'];

                    if ($linhaCampo == 'COST_PER_CLICK')
                        $data['siteCPC'] = $linhaValor['value'];

                    if ($linhaCampo == 'AD_REQUESTS_CTR')
                        $data['siteCTR'] = $linhaValor['value'];

                    if ($linhaCampo == 'CLICKS')
                        $data['siteCliques'] = $linhaValor['value'];

                    if ($linhaCampo == 'PAGE_VIEWS_RPM')
                        $data['siteRPMPagina'] = $linhaValor['value'];

                    if ($linhaCampo == 'PAGE_VIEWS')
                        $data['siteViewsPagina'] = $linhaValor['value'];

                    if ($linhaCampo == 'IMPRESSIONS_RPM')
                        $data['siteRPMImpressoes'] = $linhaValor['value'];

                    if ($linhaCampo == 'IMPRESSIONS')
                        $data['siteImpressoes'] = $linhaValor['value'];

                    if ($linhaCampo == 'ACTIVE_VIEW_VIEWABILITY')
                        $data['siteActiveView'] = $linhaValor['value'];
                        
                    if ($linhaCampo == 'DATE') {
                        $data['siteData'] = $linhaValor['value'];
                        
                        $siteData = $linhaValor['value'];;
                    }
    			}
    
    			$siteQuery = mysqli_query($con, "SELECT *
	                FROM contas_sites
	                WHERE 
	                    siteDominioNome = '$siteDominioNome' AND 
	                    siteData        = '$siteData' AND
	                	_contaID = $contaID;");

        		if ($siteQuery) {
                	$siteValor = mysqli_fetch_array($siteQuery);
                	if (isset($siteValor['siteID'])) {
                		$siteID = $siteValor['siteID'];

                		$retorno = update('contas_sites', $data, 'siteID = ' . $siteID);

                		continue;
                	}
                }

    			$data['siteCriadoEm'] = date('Y-m-d');
    			$data['_contaID']     = $contaID;

    			$retorno = insert('contas_sites', $data);
    		}
    	}
    	
   	}
}

$dataAtual = date('Y-m-d');

$query = mysqli_query($con, "SELECT *
    FROM contas
    LIMIT 100;");

if ($query) {
    while ($itemValor = mysqli_fetch_array($query)) { 
        $contaID = $itemValor['contaID'];
        
        $contaGanhosHoje  = 0; 
        $contaGanhosOntem = 0;
        $contaGanhos7Dias = 0;
        $contaGanhosMes   = 0;

        $sitesQuery = mysqli_query($con, "SELECT SUM(siteGanhosEstimatimados) AS total
            FROM contas_sites
            WHERE 
                siteData = '$dataAtual' AND 
                _contaID = $contaID;");

        if ($sitesQuery) {
            $siteValor = mysqli_fetch_array($sitesQuery);
            if (isset($siteValor['total'])) {
                $contaGanhosHoje = $siteValor['total']; 
            }
        }

        $sitesQuery = mysqli_query($con, "SELECT SUM(siteGanhosEstimatimados) AS total
            FROM contas_sites
            WHERE 
                siteData = '" . date('Y-m-d', strtotime('-1 day')) . "' AND 
                _contaID = $contaID;");

        if ($sitesQuery) {
            $siteValor = mysqli_fetch_array($sitesQuery);
            if (isset($siteValor['total'])) {
                $contaGanhosOntem = $siteValor['total']; 
            }
        }

        $sitesQuery = mysqli_query($con, "SELECT SUM(siteGanhosEstimatimados) AS total
            FROM contas_sites
            WHERE 
                siteData >= '" . date('Y-m-d', strtotime('-7 days')) . "' AND siteData < '$dataAtual' AND
                _contaID = $contaID;");

        if ($sitesQuery) {
            $siteValor = mysqli_fetch_array($sitesQuery);
            if (isset($siteValor['total'])) {
                $contaGanhos7Dias = $siteValor['total']; 
            }
        }

        $sitesQuery = mysqli_query($con, "SELECT SUM(siteGanhosEstimatimados) AS total
            FROM contas_sites
            WHERE 
                MONTH(siteData) = MONTH(CURRENT_DATE()) AND YEAR(siteData) = YEAR(CURRENT_DATE()) AND
                _contaID = $contaID;");

        if ($sitesQuery) {
            $siteValor = mysqli_fetch_array($sitesQuery);
            if (isset($siteValor['total'])) {
                $contaGanhosMes = $siteValor['total']; 
            }
        }
        
        $data = array(
            'contaGanhosHoje'  => $contaGanhosHoje,
            'contaGanhosOntem' => $contaGanhosOntem,
            'contaGanhos7Dias' => $contaGanhos7Dias,
            'contaGanhosMes'   => $contaGanhosMes
        );

        update('contas', $data, 'contaID = ' . $contaID);
    }
}