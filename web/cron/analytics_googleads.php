<?php
header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

analyticsGoogleadsCron();