<?php

// USAGE: provides $stock_all_basic_arr (json) and $stock_all_basic_json_str (string) variables and saves stocks.json in a file for other scripts to use

require_once 'stock_list.php';

echo date('Y-m-d H:i:s')." starting stock_curl_all_basic.php<br />";
$the_url='http://www.google.com/finance/info?q=';
$url_and_query=$the_url.$stock_list;
$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $url_and_query );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$stock_all_basic_json_str = curl_exec( $curl );
curl_close( $curl );
$stock_all_basic_json_str=preg_replace('/\n/', '', $stock_all_basic_json_str);
$stock_all_basic_json_str=preg_replace('/\/\/\s*/', '', $stock_all_basic_json_str);
if(strlen($stock_all_basic_json_str)<50) die("Error downloading stock info (".$url_and_query."): ".$stock_all_basic_json_str);
// --- debug ---
//$json_out=json_decode($stock_all_basic_json_str,true);
//echo "$url_and_query<br />$stock_all_basic_json_str<br /><pre>$json_out</pre><br /><br />";
//print_r($json_out);
//echo "----------------";
//var_dump($json_out);
// -------------

$stock_all_basic_arr=json_decode($stock_all_basic_json_str,true);
$stocks_json_file = fopen("stocks.json", "w") or die("Unable to open file stocks.json!");
fwrite($stocks_json_file, $stock_all_basic_json_str);
fclose($stocks_json_file);

echo date('Y-m-d H:i:s')." ending stock_curl_all_basic.php<br />";

?>