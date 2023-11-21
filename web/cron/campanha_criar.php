<?php
header("Access-Control-Allow-Origin: *");

set_time_limit(0);

include('../config.php'); 
include(ABSPATH .'/funcoes.php');

$pasta    = ABSPATH . 'cron/arquivos/campanhas_facebook/';
$arquivos = glob($pasta . '*.txt');

$total = 0;

foreach ($arquivos as $arquivoValor) {
    if (preg_match('/campanha-(\d+)-(\d+)\.txt/', $arquivoValor)) {
        $total = $total + 1;
    }
}
 
if ($total > 0) {
    echo 'parar';
    
} else {
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
    $where[] = "cadastroTipo   = 'facebook' ";
        
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
            
            $configLink = 'https://gestor.naveads.com/data/config_' . $clienteID . '.txt';
            
            $html = file_get_contents($configLink);
            $json = (array) json_decode($html, true); 
            $json = array_filter($json); 
            
            $configToken   = (array) $json['config_token'];
            $configHost    = $json['config_host'];
            $configUsuario = $json['config_usuario'];
            $configSenha   = $json['config_senha'];
            
            shuffle($configToken);
            
            $nomeDaCampanha = $cadastroUtmCampaign;
            
            $paisNome = $cadastroPaises;
          
          	/*
            if (preg_match('/u00/', $paisNome)) {
                $paisNome = str_replace('u00', '\u00', $paisNome);
                $paisNome = utf8_decode($paisNome);
            } */
            
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
                    
                    if ($cadastroTipo == 'facebook' || empty($cadastroTipo)) {
                        
                        $arrBusca = (array) json_decode($itemValor['cadastroImagemBusca'], true);
                        $arrBusca = array_filter($arrBusca);
                        
                        $imagemBusca = '';
                        if (isset($arrBusca[0]))
                            $imagemBusca = $arrBusca[0];
                        
                        $linkDivulgacao = $cadastroLink . '?utm_source=facebook&utm_medium=cpc&utm_term=' . $cadastroClienteUtmTerm . '&utm_content=' . $cadastroUtmContent . '&utm_pixel=c1&campaign_id={{campaign.id}}&campaign_name={{campaign.name}}&adset_name={{adset.name}}&adset_id={{adset.id}}&ad_id={{ad.id}}&ad_name={{ad.name}}&utm_campaign=' . $cadastroUtmCampaign;
                            
                        $arrAnuncios = array();
                        
                        $arrAnuncios[] = '{
                            "NomeDoConjunto": "' . $paisNome . ' (' . rand(1000, 9999) . ')",
                            "ValorDoLance": "100",
                            "OrcamentoDiario": "700",
                            "PixelId": "' . $pixelID . '",
                            "Status": "ACTIVE",
                            "Pais": "' . $paisSigla . '"
                        }';
                    
                        if (empty($cadastroDescricao))
                            $cadastroDescricao = $galeriaTexto;
                        
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
                        
                        if (!empty($imagemBusca))
                            $arrArquivos[] = '"UrlDaImagemDeBusca": "' . $imagemBusca . '"';
                        
                        $data = '
                        {
                          "NomeDaCampanha": "' . $nomeDaCampanha . '",
                          "StatusDaCampanha": "ACTIVE",
                          "ConjuntoDeAnuncios": [' . implode(',', $arrAnuncios) . '],
                          "Creativo": {
                            "Titulo": "' . $cadastroTitulo . '",
                            "Conteudo": "' . $cadastroTexto . '",
                            "Descricao": "' . $cadastroDescricao . '",
                            "PaginaId": "' . $paginaID . '",
                            "UrlDoSite": "' . $linkDivulgacao . '",
                            "ContaDoInstagramId": "' . $instagramID . '",
                            "Arquivos": {
                                ' . implode(',', $arrArquivos) . '
                            }
                          },
                          "Anuncio": {
                            "NomeDoAnuncio": "' . $nomeConta . '",
                            "StatusDoAnuncio": "ACTIVE"
                          }
                        }';
                        
                        $arr = (array) json_decode($data, true);
                        
                        $dados = array(
                            'cadastroID' => $cadastroID,
                            'post'       => $arr,
                            'conta'      => $contaID,
                            'token'      => $configToken,
                            'host'       => $configHost,
                            'usuario'    => $configUsuario,
                            'senha'      => $configSenha
                        );
                        
                        $retorno = file_put_contents($pasta . 'campanha-' . rand(1000, 9999) . '-' . $portaNome . '.txt', json_encode($dados));
                        if ($retorno) {
                            
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