<?php
include('config.php'); 
include('funcoes.php');

if (isset($_GET['sair'])) {
	unset($_SESSION['cliente_logado']);

	header('location: '. site_url('login.php'));
	exit;
}

if (!isset($_SESSION['cliente_logado']))
	header('location: '. site_url('login.php'));

$clienteID = (int) $_SESSION['cliente_id'];

$paginaLink = isset($_GET['pg']) ? anti_injection($_GET['pg']) : '';
$paginaLink = strtolower(trim($paginaLink));

if (!empty($paginaLink)) {
    
    $mostraItem = false;
    if (validaPermissao('ver', $paginaLink) || ($paginaLink == 'tarefas')) {
		$mostraItem = true;
	}
	
	if ($paginaLink == 'gestor_tiktok2') {
	    if (validaPermissao('ver', 'gestor_tiktok')) {
		    $mostraItem = true;
    	}
	}
	
	if (isset($_GET['campanha_tiktok']) || isset($_GET['campanhas'])) {
	    if (validaPermissao('ver', 'gestor_tiktok2') || validaPermissao('ver', 'gestor_tiktok')) {
	        $mostraItem = true;
	    }
	    
	    if (validaPermissao('ver', 'gestor_adx')) {
	        $mostraItem = true;
	    }
	}
	
	if ($paginaLink == 'perfil')
	    $mostraItem = true;
	
	if (!$mostraItem) {
    	header('location: '. site_url());
    	exit;
	}
    
    if ($paginaLink != 'tarefas') {
    	if (isset($_GET['add'])) {
    		if (!validaPermissao('add', $paginaLink)) {
    			header('location: '. site_url());
    			exit;
    		}
    	}
    
    	if (isset($_GET['editar'])) {
    		if (!validaPermissao('alterar', $paginaLink)) {
    			header('location: '. site_url());
    			exit;
    		}
    	}
    }
}

if (empty($paginaLink)) {
	include(ABSPATH . 'paginas/home.php');
} else {
    
	if (is_file(ABSPATH . 'paginas/'. $paginaLink .'.php')) {
		include(ABSPATH . 'paginas/'. $paginaLink .'.php');
	} else if ($paginaLink == 'gestor_tiktok2') {
	    include(ABSPATH . 'paginas/gestor_tiktok2.php');
	} else {
		include(ABSPATH . 'paginas/404.php');
	}
}