<?php

// USAGE: provides $btcusd variable for other scripts to use

echo date('Y-m-d H:i:s')." starting stock_curl_btcusd.php<br />";
$url_and_query='https://finance.google.com/finance?q=CURRENCY:BTC';
$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $url_and_query );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl ); //utf8_decode( not necessary
curl_close( $curl );
    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
    $response=preg_replace("/1\s+BTC\s+=\s+([^U]+)USD[^\(]*\(([^%]*)%/", "\n1BTC=$1\n1curr_change=$2\n", $response);
    $response=preg_replace("/^[^1].*$/m", "", $response); // needs m to work
    $response=preg_replace("/<[^>]*>/", "", $response);
    //echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    preg_match("/^1BTC=([0-9,.]*)\s*/m", $response, $btcusdval);
    $btcusdval=str_replace(",","",$btcusdval[1]);
    preg_match("/^1curr_change=([0-9,.-]*)\s*/m", $response, $change);
    $change=str_replace(",","",$change[1]);
$btcusd=floatval($btcusdval);
$btcusd_change=floatval($change)/100;
echo "<br />btcusd=$btcusdval<br />";
echo "<br />btcusd change=$change<br />";
echo date('Y-m-d H:i:s')." ending stock_curl_btcusd.php<br />";

?>