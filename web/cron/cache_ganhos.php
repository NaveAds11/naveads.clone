<?php 
header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

set_time_limit(0);

$horaAtual = date('H');

/* Cache ganhos */

mysqli_query($con, "TRUNCATE TABLE cliente_cache_ganhos;");

$impostoPorcentagem = getConfig('imposto_porcentagem');
if (empty($impostoPorcentagem))
    $impostoPorcentagem = 10;
    
$dolarHoje = getConfig('dolar_valor');

$arrSocial = array(
    'facebook' => 'Facebook',
    'tiktok'   => 'Tiktok'
);

$clientes = mysqli_query($con, "SELECT *
    FROM clientes
    ORDER BY clienteNome DESC;");

if ($clientes) {
    while ($clienteValor = mysqli_fetch_array($clientes)) { 
        $clienteID   = $clienteValor['clienteID']; 
        $clienteTipo = $clienteValor['clienteTipo'];
        
        $clienteComissao = 10;
        
        if ($clienteID > 0) {
            $comissao = mysqli_query($con, "SELECT *
                FROM clientes
                WHERE 
                    clienteID = $clienteID
                LIMIT 1;");
            
            if ($comissao) {
                while ($comissaoItem = mysqli_fetch_array($comissao)) { 
                    $clienteComissao = $comissaoItem['clienteComissao'];
                }
            }
        }
                        
        foreach ($arrSocial as $socialIndex => $socialNome) {
            
            $sql = "SELECT 
                
                IFNULL((SELECT 
                    SUM(relatorioReceitaTotal)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    DATE(relatorioData) = CURDATE() AND 
                    _clienteID = $clienteID), 0) AS receitaHoje,
                        
                IFNULL((SELECT 
                    SUM(relatorioReceitaTotal)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    DATE(relatorioData) = CURDATE() - INTERVAL 1 DAY AND 
                    _clienteID = $clienteID), 0) AS receitaOntem,
                                
                IFNULL((SELECT 
                    SUM(relatorioReceitaTotal)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    MONTH(relatorioData) = MONTH(CURRENT_DATE()) AND 
                    YEAR(relatorioData)  = YEAR(CURRENT_DATE()) AND
                    _clienteID = $clienteID), 0) AS receitaMesAtual,
                                
                IFNULL((SELECT 
                    SUM(relatorioReceitaTotal)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    YEAR(relatorioData) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND 
                    MONTH(relatorioData) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) AND
                    _clienteID = $clienteID), 0) AS receitaMesAnterior,
                     
                IFNULL((SELECT 
                    SUM(relatorioCustoValor)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    DATE(relatorioData) = CURDATE() AND 
                    _clienteID = $clienteID), 0) AS custosHoje,
                                 
                IFNULL((SELECT 
                    SUM(relatorioCustoValor)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    DATE(relatorioData) = CURDATE() - INTERVAL 1 DAY AND 
                    _clienteID = $clienteID), 0) AS custosOntem,
                    
                IFNULL((SELECT 
                    SUM(relatorioCustoValor)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    MONTH(relatorioData) = MONTH(CURRENT_DATE()) AND 
                    YEAR(relatorioData)  = YEAR(CURRENT_DATE()) AND
                    _clienteID = $clienteID), 0) AS custosMesAtual,
                    
                IFNULL((SELECT 
                    SUM(relatorioCustoValor)
                FROM adx_relatorios
                WHERE
                    relatorioUtmTipo = 'campaign_id' AND 
                    relatorioUtmSource = '$socialIndex' AND 
                    YEAR(relatorioData) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND 
                    MONTH(relatorioData) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) AND
                    _clienteID = $clienteID), 0) AS custosMesAnterior";
                    
            $query = mysqli_query($con, $sql);
            
            if ($query) {
                $itemValor = mysqli_fetch_array($query);
                if (isset($itemValor['receitaHoje'])) {
                    
                    $receitaHoje        = $itemValor['receitaHoje'];
                    $receitaOntem       = $itemValor['receitaOntem'];
                    $receitaMesAtual    = $itemValor['receitaMesAtual'];
                    $receitaMesAnterior = $itemValor['receitaMesAnterior'];
                    
                    $custosHoje         = $itemValor['custosHoje'];
                    $custosOntem        = $itemValor['custosOntem'];
                    $custosMesAtual     = $itemValor['custosMesAtual'];
                    $custosMesAnterior  = $itemValor['custosMesAnterior'];
                    
                    $lucroHoje          = 0;
                    $lucroOntem         = 0;
                    $lucroMesAtual      = 0;
                    $lucroMesAnterior   = 0;
                    
                    $ganhosHoje         = 0;
                    $ganhosOntem        = 0;
                    $ganhosMesAtual     = 0;
                    $ganhosMesAnterior  = 0;
                    
                    if ($custosHoje > 0) {
                        $totalAdRevenue  = $receitaHoje; 
                        $impostoValor    = ($totalAdRevenue / 100) * $impostoPorcentagem;
                        $totalAdRevenue  = ($totalAdRevenue - $impostoValor) - $custosHoje;
                        $ganhosHoje      = $totalAdRevenue - ($totalAdRevenue - (($totalAdRevenue / 100) * $clienteComissao));
                        
                        $lucroHoje       = $totalAdRevenue - $ganhosHoje;
                    }
                
                    if ($custosOntem > 0) {
                        $totalAdRevenue  = $receitaOntem; 
                        $impostoValor    = ($totalAdRevenue / 100) * $impostoPorcentagem;
                        $totalAdRevenue  = ($totalAdRevenue - $impostoValor) - $custosOntem;
                        $ganhosOntem     = $totalAdRevenue - ($totalAdRevenue - (($totalAdRevenue / 100) * $clienteComissao));
                         
                        $lucroOntem      = $totalAdRevenue - $ganhosOntem;
                    }
                
                    if ($custosMesAtual > 0) {
                        $totalAdRevenue  = $receitaMesAtual; 
                        $impostoValor    = ($totalAdRevenue / 100) * $impostoPorcentagem;
                        $totalAdRevenue  = ($totalAdRevenue - $impostoValor) - $custosMesAtual;
                        $ganhosMesAtual  = $totalAdRevenue - ($totalAdRevenue - (($totalAdRevenue / 100) * $clienteComissao));
                         
                        $lucroMesAtual   = $totalAdRevenue - $ganhosMesAtual;
                    }
                    
                    if ($custosMesAnterior > 0) {
                        $totalAdRevenue    = $receitaMesAnterior; 
                        $impostoValor      = ($totalAdRevenue / 100) * $impostoPorcentagem;
                        $totalAdRevenue    = ($totalAdRevenue - $impostoValor) - $custosMesAnterior;
                        $ganhosMesAnterior = $totalAdRevenue - ($totalAdRevenue - (($totalAdRevenue / 100) * $clienteComissao));
                         
                        $lucroMesAnterior = $totalAdRevenue - $ganhosMesAnterior;
                    }
                    
                    $arrInserir = array(
                        'receita' => array(
                            'hoje'        => $itemValor['receitaHoje'],
                            'ontem'       => $itemValor['receitaOntem'],
                            'mesAtual'    => $itemValor['receitaMesAtual'],
                            'mesAnterior' => $itemValor['receitaMesAnterior']
                        ),
                        
                        'lucro' => array(
                            'hoje'        => $lucroHoje,
                            'ontem'       => $lucroOntem,
                            'mesAtual'    => $lucroMesAtual,
                            'mesAnterior' => $lucroMesAnterior
                        ),
                        
                        'custo' => array(
                            'hoje'        => $itemValor['custosHoje'],
                            'ontem'       => $itemValor['custosOntem'],
                            'mesAtual'    => $itemValor['custosMesAtual'],
                            'mesAnterior' => $itemValor['custosMesAnterior']
                        ),
                        
                        'comissao' => array(
                            'hoje'        => $ganhosHoje,
                            'ontem'       => $ganhosOntem,
                            'mesAtual'    => $ganhosMesAtual,
                            'mesAnterior' => $ganhosMesAnterior
                        )
                    );
                    
                    foreach ($arrInserir as $inserirTipo => $inserirValor) {

                        $itemHoje = '0.00';
                        if (isset($inserirValor['hoje']))
                            $itemHoje = number_format((float) $inserirValor['hoje'], 2, '.', '');

                        $itemOntem = '0.00';
                        if (isset($inserirValor['ontem']))
                            $itemOntem = number_format((float) $inserirValor['ontem'], 2, '.', '');

                        $itemMes = '0.00';
                        if (isset($inserirValor['mesAtual']))
                            $itemMes = number_format((float) $inserirValor['mesAtual'], 2, '.', '');

                        $itemMesAnterior = '0.00';
                        if (isset($inserirValor['mesAnterior']))
                            $itemMesAnterior = number_format((float) $inserirValor['mesAnterior'], 2, '.', '');

                        if (empty($itemHoje))
                            $itemHoje = '0.00';

                        if (empty($itemOntem))
                            $itemOntem = '0.00';
                        
                        if (empty($itemMes))
                            $itemMes = '0.00';
                        
                        if (empty($itemMesAnterior))
                            $itemMesAnterior = '0.00';

                        $data = array(
                            'cacheValorHoje'        => $itemHoje,
                            'cacheValorOntem'       => $itemOntem,  
                            'cacheValorMesAtual'    => $itemMes,
                            'cacheValorMesAnterior' => $itemMesAnterior,
                            'cacheRede'             => $socialIndex,
                            'cacheTipo'             => $inserirTipo,
                            'cacheData'             => date('Y-m-d H:i:s'),
                            '_clienteID'            => $clienteID
                        );

                        insert('cliente_cache_ganhos', $data);
                    }
                }
            }
        }
    }
}

echo 'parar';