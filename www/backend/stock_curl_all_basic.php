<?php

require_once 'stock_list.php';

echo date('Y-m-d H:i:s')." starting stock_curl_all_basic.php<br />";
$the_url='http://www.google.com/finance/info?q=';
$url_and_query=$the_url.$stock_list;
$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $url_and_query );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl );
curl_close( $curl );
$response=preg_replace('/\n/', '', $response);
$response=preg_replace('/\/\/\s*/', '', $response);
// --- debug ---
//$json_out=json_decode($response,true);
//echo "$url_and_query<br />$response<br /><pre>$json_out</pre><br /><br />";
//print_r($json_out);
//echo "----------------";
//var_dump($json_out);
// -------------
$stocks_json = fopen("stocks.json", "w") or die("Unable to open file!");
fwrite($stocks_json, $response);
fclose($stocks_json);
echo date('Y-m-d H:i:s')." ending stock_curl_all_basic.php<br />";

?>