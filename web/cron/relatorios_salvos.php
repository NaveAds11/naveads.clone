<?php
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$contas = mysqli_query($con, "SELECT *
    FROM contas
    LIMIT 100;");

if ($contas) {
    while ($contaValor = mysqli_fetch_array($contas)) { 
    	$contaID          = $contaValor['contaID'];
    	$contaNome        = $contaValor['contaNome'];
    	$contaAccessToken = $contaValor['contaAccessToken'];

    	echo 'Iniciando: ' . $contaNome . '<br />';
 
		$query = mysqli_query($con, "SELECT *
		    FROM contas_relatorios_salvos
		    WHERE 
		    	relatorioMostrarHome = 1 AND 
		    	_contaID             = $contaID
		    LIMIT 100;");

		if ($query) {
		    while ($relatorioValor = mysqli_fetch_array($query)) { 
		    	$relatorioID          = $relatorioValor['relatorioID'];
		    	$account              = $relatorioValor['relatorioCodigo'];
	    		$relatorioNome        = $relatorioValor['relatorioNome'];
	    		$relatorioMostrarHome = $relatorioValor['relatorioMostrarHome'];

	    		/* Hoje */
	    		$retorno = adsenseReportsSavedGenerate($account, $contaAccessToken, 'TODAY'); 

	    		$listaHoje = array();
	    		if (isset($retorno['headers']))
	    			$listaHoje = $retorno; 

	    		$listaHojeTotal = 0;
	    		if (isset($listaHoje['headers'])) {
	    			$posicaoItem = 0;

					foreach ($listaHoje['headers'] as $topoIndex => $topoValor) {
						$topoNome = $topoValor['name'];
						if ($topoNome == 'ESTIMATED_EARNINGS')
							$posicaoItem = $topoIndex;
					}

					foreach ($listaHoje['rows'] as $listaValor) {
						foreach ($listaValor['cells'] as $celIndex => $celValor) {
							if ($celIndex == $posicaoItem)
								$listaHojeTotal = $listaHojeTotal + (float) $celValor['value'];
						}
					}
				}

	    		/* Ontem */
	    		$retorno = adsenseReportsSavedGenerate($account, $contaAccessToken, 'YESTERDAY'); 

	    		$listaOntem = array();
	    		if (isset($retorno['headers']))
	    			$listaOntem = $retorno;

	    		$listaOntemTotal = 0;
	    		if (isset($listaOntem['headers'])) {
	    			$posicaoItem = 0;

					foreach ($listaOntem['headers'] as $topoIndex => $topoValor) {
						$topoNome = $topoValor['name'];
						if ($topoNome == 'ESTIMATED_EARNINGS')
							$posicaoItem = $topoIndex;
					}

					foreach ($listaOntem['rows'] as $listaValor) {
						foreach ($listaValor['cells'] as $celIndex => $celValor) {
							if ($celIndex == $posicaoItem)
								$listaOntemTotal = $listaOntemTotal + (float) $celValor['value'];
						}
					}
				}

	    		/* 7 dias */
	    		$retorno = adsenseReportsSavedGenerate($account, $contaAccessToken, 'LAST_7_DAYS'); 

	    		$lista7Dias = array();
	    		if (isset($retorno['headers']))
	    			$lista7Dias = $retorno;

	    		$lista7diasTotal = 0;
	    		if (isset($lista7Dias['headers'])) {
	    			$posicaoItem = 0;

					foreach ($lista7Dias['headers'] as $topoIndex => $topoValor) {
						$topoNome = $topoValor['name'];
						if ($topoNome == 'ESTIMATED_EARNINGS')
							$posicaoItem = $topoIndex;
					}

					foreach ($lista7Dias['rows'] as $listaValor) {
						foreach ($listaValor['cells'] as $celIndex => $celValor) {
							if ($celIndex == $posicaoItem)
								$lista7diasTotal = $lista7diasTotal + (float) $celValor['value'];
						}
					}
				}

	    		/* Mês */
	    		$retorno = adsenseReportsSavedGenerate($account, $contaAccessToken, 'MONTH_TO_DATE'); 

	    		$listaMes = array();
	    		if (isset($retorno['headers']))
	    			$listaMes = $retorno; 

	    		$listaMesTotal = 0;
	    		if (isset($listaMes['headers'])) {
	    			$posicaoItem = 0;

					foreach ($listaMes['headers'] as $topoIndex => $topoValor) {
						$topoNome = $topoValor['name'];
						if ($topoNome == 'ESTIMATED_EARNINGS')
							$posicaoItem = $topoIndex;
					}

					foreach ($listaMes['rows'] as $listaValor) {
						foreach ($listaValor['cells'] as $celIndex => $celValor) {
							if ($celIndex == $posicaoItem)
								$listaMesTotal = $listaMesTotal + (float) $celValor['value'];
						}
					}
				}

				$data = array(
					'relatorioDadosHoje'       => json_encode($listaHoje),
					'relatorioDadosOntem'      => json_encode($listaOntem),
					'relatorioDados7Dias'      => json_encode($lista7Dias),
					'relatorioDadosMes'        => json_encode($listaMes),
					'relatorioAtualizadoEm'    => date('Y-m-d H:i:s'),
					'relatorioDadosTotalHoje'  => $listaHojeTotal,
					'relatorioDadosTotalOntem' => $listaOntemTotal,
					'relatorioDadosTotal7Dias' => $lista7diasTotal,
					'relatorioDadosTotalMes'   => $listaMesTotal
				);

				$retorno = update('contas_relatorios_salvos', $data, 'relatorioID = ' . $relatorioID);
				if ($retorno) {
					echo 'Dados foram atualizados em: ' . $relatorioNome . '<br />';
				} else {
					echo 'Erro ao atualizar dados em:  ' . $relatorioNome . '<br />';
				}
		    }
		}
	}
}