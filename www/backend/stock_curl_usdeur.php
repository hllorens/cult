<?php

// USAGE: provides $usdeur variable for other scripts to use

echo date('Y-m-d H:i:s')." starting stock_curl_usdeur.php<br />";
//$url_and_query='https://finance.google.com/finance?q=usdeur';
$url_and_query='https://finance.yahoo.com/quote/USDeur=X?ltr=1';
$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $url_and_query );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl ); //utf8_decode( not necessary
curl_close( $curl );
    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
    $response=preg_replace("/react-text: 36 -->([^<]*)</", "\n1USD=$1\n", $response);
    $response=preg_replace("/react-text: 38 -->[-]?[0-9.]*\s*\(([^%]*)%/", "\n1curr_change=$1\n", $response);
    //$response=preg_replace("/1\s+USD\s+=\s+([^E]+)EUR[^\(]*\(([^%]*)%/", "\n1USD=$1\n1curr_change=$2\n", $response);
    //$response=preg_replace("/^[^1].*$/m", "", $response); // needs m to work
    //$response=preg_replace("/<[^>]*>/", "", $response);
    //echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    preg_match("/^1USD=([0-9,.]*)\s*/m", $response, $usdeurval);
    $usdeurval=str_replace(",","",$usdeurval[1]);
    preg_match("/^1curr_change=([0-9,.-]*)\s*/m", $response, $change);
    $change=str_replace(",","",$change[1]);

$usdeur=floatval($usdeurval);
$usdeur_change=floatval($change)/100;
echo "<br />usdeur=$usdeurval<br />";
echo "<br />usdeur change=$change<br />";

echo date('Y-m-d H:i:s')." ending stock_curl_usdeur.php<br />";

?>