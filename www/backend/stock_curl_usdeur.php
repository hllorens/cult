<?php

// USAGE: provides $usdeur variable for other scripts to use

echo date('Y-m-d H:i:s')." starting stock_curl_usdeur.php<br />";
$url_and_query='https://finance.google.com/finance?q=usdeur';
$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $url_and_query );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl ); //utf8_decode( not necessary
curl_close( $curl );
    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
    $response=preg_replace("/1\s+USD\s+=\s+([^E]+)EUR/", "\n1USD=$1\n", $response);
    $response=preg_replace("/^[^1].*$/", "", $response);
    $response=preg_replace("/<[^>]*>/", "", $response);
    //echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    preg_match("/^1USD=([0-9.]*)\s*/m", $response, $usdeurval);
    $usdeurval=$usdeurval[1];
echo "<br />usdeur=$usdeurval<br />";
$usdeur=floatval($usdeurval);
echo date('Y-m-d H:i:s')." ending stock_curl_usdeur.php<br />";

?>