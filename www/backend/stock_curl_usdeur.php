<?php

// USAGE: provides $usdeur variable for other scripts to use
require_once 'stock_helper_functions.php';

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
	$debug=true;
}

echo date('Y-m-d H:i:s')." starting stock_curl_usdeur.php<br />";
//$url_and_query='https://finance.google.com/finance?q=usdeur';
$url_and_query='https://finance.yahoo.com/quote/USDeur=X?ltr=1';
$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $url_and_query );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl ); //utf8_decode( not necessary
curl_close( $curl ); 
echo $url_and_query."<br />";

    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
//    $response=preg_replace("/react-text: 36 -->([^<]*)</", "\n1USD=$1\n", $response);
    $response=preg_replace("/reactid=\"33\"[^>]*>([0-9][^<]*)</", "\n1USD=$1\n", $response);
//    $response=preg_replace("/react-text: 38 -->[-]?[0-9.]*\s*\(([^%]*)%/", "\n1curr_change=$1\n", $response);
    $response=preg_replace("/reactid=\"34\"[^>]*>[-+]?[0-9.]+\s*\(([^%]*)%/", "\n1curr_change=$1\n", $response);
    //$response=preg_replace("/1\s+USD\s+=\s+([^E]+)EUR[^\(]*\(([^%]*)%/", "\n1USD=$1\n1curr_change=$2\n", $response);
    //$response=preg_replace("/^[^1].*$/m", "", $response); // needs m to work
    //$response=preg_replace("/<[^>]*>/", "", $response);
   // echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/</", "\n<", $response);
	if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    preg_match("/^1USD=([0-9,.]*)\s*/m", $response, $usdeurval);
    $usdeurval=str_replace(",","",$usdeurval[1]);
    preg_match("/^1curr_change=([0-9,.+-]*)\s*/m", $response, $change);
    $change=str_replace("+","",str_replace(",","",$change[1]));

$usdeur=floatval(toFixed(floatval($usdeurval),2));
$usdeur_change=floatval(toFixed(floatval($change)/100,2));
echo "<br />usdeur=$usdeur(orig: $usdeurval)<br />";
echo "<br />usdeur change=$usdeur_change(orig: $change)<br />";

echo date('Y-m-d H:i:s')." ending stock_curl_usdeur.php<br />";

?>