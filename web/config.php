<?php
session_start();

// if (!isset($_GET['error'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
// }

date_default_timezone_set('America/Sao_Paulo');

define('ABSPATH',       dirname(__FILE__) . '/');
define('PASTA_ADMIN',   'minha-conta');
define('PAINEL_WP',     'https://cron.naveads.com/');
define('PAINEL_VERSAO', '1.0.' . time());

$site_url = 'https://cron.naveads.com/';
$base_url = 'https://cron.naveads.com/';

$admin_url = $site_url . PASTA_ADMIN .'/';

$host  = '91.134.91.241';
$login = 'root';
$senha = 'sa9/a9da)*jgndms,dsa+5s}sa'; 
$banco = 'nave_gestor'; 

$con = new mysqli($host, $login, $senha, $banco) or print(mysqli_error());

$con->set_charset("utf8mb3");