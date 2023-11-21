<?php
header("Access-Control-Allow-Origin: *");

set_time_limit(0);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../config.php'); 
include(ABSPATH .'/funcoes.php');

$pastaTiktok    = ABSPATH . 'cron/arquivos/campanhas_tiktok/';
$arquivosTiktok = glob($pastaTiktok . '*.txt');

$total = 0;

foreach ($arquivosTiktok as $arquivoValor) {
    if (preg_match('/campanha-(\d+)-(\d+)\.txt/', $arquivoValor)) {
        $total = $total + 1;
    }
}

if ($total == 0) {
    $arrPortas = array();
    
    $portas = mysqli_query($con, "SELECT *
        FROM cron_campanhas ");
        
    if ($portas) {
        
        $posicao = 1;
        while ($portaValor = mysqli_fetch_array($portas)) {
            $arrPortas[$posicao] = $portaValor['cronNome'];
            
            $posicao++;
        }
    }
    
    $totalPortas = count($arrPortas);
    
    $where   = array();
    $where[] = "cadastroGerado = 2";
        
    $lista = mysqli_query($con, "SELECT *
        FROM cliente_campanhas
        WHERE 
            " . implode(' AND ', $where) . "
        ORDER BY cadastroID DESC");
    
    if ($lista) {
        
        $posicao = 1;
        
        while ($itemValor = mysqli_fetch_array($lista)) {
            $portaNome = $arrPortas[$posicao];
            
            $cadastroID             = $itemValor['cadastroID'];
            $nomeConta              = $itemValor['cadastroNomeConta'];
            $contaID                = $itemValor['cadastroContaID'];
            $pixelID                = $itemValor['cadastroPixelID'];
            $paginaID               = $itemValor['cadastroPaginaID'];
            $instagramID            = $itemValor['cadastroInstagramID'];
            $cadastroImagemBusca    = $itemValor['cadastroImagemBusca'];
            $cadastroDescricao      = $itemValor['cadastroDescricao'];
            $cadastroTitulo         = $itemValor['cadastroTitulo'];
            $cadastroTexto          = $itemValor['cadastroTexto'];
            $cadastroPaises         = $itemValor['cadastroPaises'];
            $cadastroLink           = $itemValor['cadastroLink'];
            $cadastroUtmCampaign    = $itemValor['cadastroUtmCampaign'];
            $cadastroUtmContent     = $itemValor['cadastroUtmContent'];
            $cadastroAnexos         = $itemValor['cadastroAnexos'];
            $cadastroAnexoTipo      = $itemValor['cadastroAnexoTipo'];
            $cadastroAnexoHash      = $itemValor['cadastroAnexoHash'];
            $cadastroClienteUtmTerm = $itemValor['cadastroClienteUtmTerm'];
            $cadastroIdade          = $itemValor['cadastroIdade'];
            $cadastroTipo           = $itemValor['cadastroTipo'];
            $cadastroGenero         = $itemValor['cadastroGenero'];
            $galeriaID              = $itemValor['_galeriaID'];
            $clienteID              = $itemValor['_clienteID'];
            $tiktokContaID          = $itemValor['_tiktokContaID'];
            $tiktokContaIndex       = $itemValor['_tiktokContaIndex'];
            $tiktokContaID          = (int) $tiktokContaID;
            
            $arquivos = mysqli_query($con, "SELECT *
                FROM galeria
                WHERE 
                    galeriaID  = $galeriaID
                LIMIT 1;"); 
                
            if ($arquivos) {
                $arquivoValor = mysqli_fetch_array($arquivos);
                if (isset($arquivoValor)) {
                    $galeriaNome        = $arquivoValor['galeriaNome'];
                    $galeriaTitulo      = $arquivoValor['galeriaTitulo'];
                    $galeriaTexto       = $arquivoValor['galeriaTexto'];
                    $galeriaArquivoHash = $arquivoValor['galeriaArquivoHash'];
                    
                    if (empty($cadastroTitulo))
                        $galeriaTitulo = $cadastroTitulo;
                        
                    if (empty($cadastroTexto))
                        $cadastroTexto = $galeriaTexto;
                }
            }
            
            $file = ABSPATH . 'data/config_' . $clienteID . '.txt';
                
            if (is_file($file)) {
                $html = file_get_contents($file);
                $json = (array) json_decode($html, true); 
                $json = array_filter($json); 
            } else {
                $json = array();
            }
            
            $configToken   = (array) $json['config_token'];
            $configHost    = $json['config_host'];
            $configUsuario = $json['config_usuario'];
            $configSenha   = $json['config_senha'];
            
            shuffle($configToken);
            
            $nomeDaCampanha = $cadastroUtmCampaign;
            
            $paisNome = $cadastroPaises;
            if (preg_match('/u00/', $paisNome)) {
                $paisNome = str_replace('u00', '\u00', $paisNome);
                $paisNome = utf8_decode($paisNome);
            }
            
            $paisNome  = trim($paisNome);
            $paisSigla = arrPais($paisNome, true);
            
            $arrVideos = array();
            $arrFotos  = array();
            
            if (isset($configToken[0]['token'])) {
                $configToken = $configToken[0]['token'];
                
                if (!empty($configToken)) {
                    
                    if (empty($configHost)) {
                        $_configHost = getConfig('proxy_host');
                        if (!empty($_configHost))
                            $configHost = $_configHost;
                    }
                    
                    if (empty($configUsuario)) {
                        $_configUsuario = getConfig('proxy_usuario'); 
                        if (!empty($_configUsuario))
                            $configUsuario = $_configUsuario;
                    }
                    
                    if (empty($configSenha)) {
                        $_configSenha   = getConfig('proxy_senha');
                        if (!empty($_configSenha))
                            $configSenha = $_configSenha;
                    }
                    
                    if ($cadastroTipo == 'tiktok') {
                        
                        $pixelId   = '';
                        $porta     = '';
                        $navegador = '';
                        $contaID   = '';
                        
                        if ($tiktokContaID > 0) {
                            $contas = mysqli_query($con, "SELECT * 
                                FROM tiktok_contas 
                                WHERE 
                                    contaID = $tiktokContaID");
                                    
                            if ($contas) {
                                $contaValor = mysqli_fetch_array($contas);
                                if (isset($contaValor['contaID'])) {
                                    $porta         = $contaValor['contaLink'];
                                    $configHost    = $contaValor['contaHost'];
                                    $configUsuario = $contaValor['contaUsuario'];
                                    $configSenha   = $contaValor['contaSenha'];
                                    $navegador     = $contaValor['contaNavegador'];
                                    
                                    $arrContas = (array) json_decode($contaValor['contaContas'], true);
                                    $arrContas = array_filter($arrContas);
                                    
                                    foreach ($arrContas as $contasIndex => $contasValor) {
                                        if ($contasIndex == $tiktokContaIndex) {
                                            $pixelId = $contasValor['pixel'];
                                            $contaID = $contasValor['codigo'];
                                            
                                            break;
                                        }
                                    }
                                    
                                    if (empty($pixelId))
                                        continue;
                                
                                    $linkDivulgacao = $cadastroLink . '?utm_source=tiktok&utm_medium=cpc&utm_term=' . $cadastroClienteUtmTerm . '&utm_content=' . $cadastroUtmContent . '&utm_pixel=c1&campaign_id=__CAMPAIGN_ID__&campaign_name=__CAMPAIGN_NAME__&adset_name=__AID_NAME__&adset_id=__AID__&ad_id=__CID__&ad_name=__CID_NAME__&utm_campaign=' . $cadastroUtmCampaign;
                                    
                                    $genero = '';
                                    if ($cadastroGenero == 'm')
                                        $genero = '"SomenteMasculino": true,';
                                        
                                    if ($cadastroGenero == 'f')
                                        $genero = '"SomenteFeminino": true,';
                                        
                                    if ($cadastroAnexoTipo == 'fotos') {
                                        $arrFotos[] = $cadastroAnexos;
                                    }
                                    
                                    if ($cadastroAnexoTipo == 'videos') {
                                        $arrVideos[] = $cadastroAnexos;
                                    }
                                    
                                    $arrArquivos = array();
                                    
                                    if (count($arrVideos) > 0)
                                        $arrArquivos[] = '"UrlDosVideo": ["' . implode('", "', $arrVideos) . '"]';
                                        
                                    if (count($arrFotos) > 0)
                                        $arrArquivos[] = '"UrlDasImagens": ["' . implode('", "', $arrFotos) . '"]';
                                        
                                    if (empty($cadastroIdade)) {
                                        $itemIdade = '18-100';
                                    } else {
                                        $arrIdade            = explode('-', $cadastroIdade);
                                        $cadastroIdadeInicio = $arrIdade[0];
                                        $cadastroIdadeFinal  = $arrIdade[1];
                                        
                                        $itemIdade = '"IdadeMinima": ' . $cadastroIdadeInicio . ', "IdadeMaxima": ' . $cadastroIdadeFinal . ',';
                                    }
                                        
                                    $data = '
                                        {
                                            "NomeDaCampanha": "' . $nomeDaCampanha . '",
                                            "Status": "PAUSED",
                                            "ConjuntoDeAnuncios": [
                                                {
                                                    "NomeDoConjunto": "' . $paisNome . ' (' . rand(1000, 9999) . ')",
                                                    "OrcamentoDiario": "2000",
                                                    ' . (empty($genero) ? '' : $genero) . '
                                                    "PixelId": "' . $pixelId. '",
                                                    ' . $itemIdade . '
                                                    "Status": "ACTIVE",
                                                    "Pais": "' . $paisSigla . '"
                                                }
                                            ],
                                            "Creativo": {
                                                "Titulo": "' . $cadastroTitulo . '",
                                                "UrlDoSite": "' . $linkDivulgacao . '",
                                                "Arquivos": {' . implode(',', $arrArquivos) . '}
                                            }
                                        }';
                                        
                                    $arr = (array) json_decode($data, true);
                                    
                                    $dados = array(
                                        'cadastroID' => $cadastroID,
                                        'post'       => $arr,
                                        'conta'      => $contaID,
                                        'host'       => $configHost,
                                        'usuario'    => $configUsuario,
                                        'senha'      => $configSenha,
                                        'porta'      => $porta,
                                        'navegador'  => $navegador,
                                    );
                                    
                                    $_retorno = file_put_contents($pastaTiktok . 'campanha-' . rand(1000, 9999) . '-' . $portaNome . '.txt', json_encode($dados));
                                    if ($_retorno) {
                                        
                                        $data = array(
                                            'cadastroGerado' => 1
                                        );
                                        
                                        update('cliente_campanhas', $data, 'cadastroID = ' . $cadastroID);
                                        
                                        if ($totalPortas == $posicao) {
                                            $posicao = 1;
                                        } else {
                                            $posicao++;
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