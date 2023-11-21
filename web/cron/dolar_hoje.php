<?php 
set_time_limit(0);

header("Access-Control-Allow-Origin: *");

include('../config.php'); 
include(ABSPATH .'/funcoes.php'); 

$link = 'https://economia.awesomeapi.com.br/json/last/USD-BRL';

$curl = curl_init();

$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
$header[] = "Cache-Control: max-age=0";
$header[] = "Connection: keep-alive";
$header[] = "Keep-Alive: 300";
$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
$header[] = "Accept-Language: en-us,en;q=0.5";
$header[] = "Pragma: ";

curl_setopt($curl, CURLOPT_URL, $link);
curl_setopt($curl, CURLOPT_AUTOREFERER, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:7.0.1) Gecko/20100101 Firefox/7.0.12011-10-16 20:23:00");
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

$retorno = curl_exec($curl);

curl_close($curl);

$json = (array) json_decode($retorno, true);
$json = array_filter($json);


if (isset($json['USDBRL'])) {
    if (isset($json['USDBRL']['low'])) {
        $dolarValor = $json['USDBRL']['low'] - 0.07;
        
        $arquivo = ABSPATH . 'data/config.txt';
        
        $html    = file_get_contents($arquivo);
        $json    = (array) json_decode($html, true);
        $json    = array_filter($json);
        
        $json['dolar_valor'] = number_format($dolarValor, 2, '.', '');
        
        file_put_contents($arquivo, json_encode($json));
    }
}