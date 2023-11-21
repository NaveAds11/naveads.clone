<?php
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$query = mysqli_query($con, "SELECT *
    FROM contas
    LIMIT 100;");

if ($query) {
    while ($itemValor = mysqli_fetch_array($query)) { 
        $contaID          = $itemValor['contaID'];
        $contaNome        = $itemValor['contaNome'];
        $contaAccessToken = $itemValor['contaAccessToken'];
        $contaPub         = $itemValor['contaPub'];

        echo 'Verificando conta: ' . $contaNome . ' - ' . $contaPub . '<br />';
        
        $link = 'https://adsense.googleapis.com/v2/accounts/' . $contaPub . '/reports:generate?dateRange=MONTH_TO_DATE&dimensions=URL_CHANNEL_NAME&dimensions=DATE&metrics=ESTIMATED_EARNINGS&metrics=COST_PER_CLICK&metrics=AD_REQUESTS_CTR&metrics=CLICKS&metrics=IMPRESSIONS&metrics=IMPRESSIONS_RPM&metrics=ACTIVE_VIEW_VIEWABILITY';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $contaAccessToken
        ));
    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $info   = curl_getinfo($ch);
        $result = curl_exec($ch);
        curl_close($ch);
    
        $arr = (array) json_decode($result, true);
        $arr = array_filter($arr);
        
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
                    
                    if ($linhaCampo == 'URL_CHANNEL_NAME') {
                        $data['relatorioDominioNome'] = $linhaValor['value'];
                        
                        $siteDominioNome = $linhaValor['value'];
                    }

                    if ($linhaCampo == 'ESTIMATED_EARNINGS')
                        $data['relatorioGanhosEstimatimados'] = $linhaValor['value'];

                    if ($linhaCampo == 'COST_PER_CLICK')
                        $data['relatorioCPC'] = $linhaValor['value'];

                    if ($linhaCampo == 'AD_REQUESTS_CTR')
                        $data['relatorioCTR'] = $linhaValor['value'];

                    if ($linhaCampo == 'CLICKS')
                        $data['relatorioCliques'] = $linhaValor['value'];

                    if ($linhaCampo == 'PAGE_VIEWS_RPM')
                        $data['relatorioRPMPagina'] = $linhaValor['value'];

                    if ($linhaCampo == 'PAGE_VIEWS')
                        $data['relatorioViewsPagina'] = $linhaValor['value'];

                    if ($linhaCampo == 'IMPRESSIONS_RPM')
                        $data['relatorioRPMImpressoes'] = $linhaValor['value'];

                    if ($linhaCampo == 'IMPRESSIONS')
                        $data['relatorioImpressoes'] = $linhaValor['value'];

                    if ($linhaCampo == 'ACTIVE_VIEW_VIEWABILITY')
                        $data['relatorioActiveView'] = $linhaValor['value'];
                        
                    if ($linhaCampo == 'DATE') {
                        $data['relatorioData'] = $linhaValor['value'];
                        
                        $siteData = $linhaValor['value'];
                    }
                }
    
                $relatorioQuery = mysqli_query($con, "SELECT *
                    FROM contas_url_relatorios
                    WHERE 
                        relatorioDominioNome = '$siteDominioNome' AND 
                        relatorioData        = '$siteData' AND
                        _contaID             = $contaID;");

                if ($relatorioQuery) {
                    $relatorioValor = mysqli_fetch_array($relatorioQuery);
                    if (isset($relatorioValor['relatorioID'])) {
                        $relatorioID = $relatorioValor['relatorioID'];

                        $retorno = update('contas_url_relatorios', $data, 'relatorioID = ' . $relatorioID);

                        continue;
                    }
                }

                $data['relatorioCriadoEm'] = date('Y-m-d');
                $data['_contaID']     = $contaID;

                $retorno = insert('contas_url_relatorios', $data);
            }
        }
        
    }
}