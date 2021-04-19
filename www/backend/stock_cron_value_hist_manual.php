<?php


date_default_timezone_set('Europe/Madrid');
$timestamp_date=date("Y-m-d");
$timestamp_simplif=date("d H:i");
$timestamp_quarter=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 3) % 4) + 1 );
$timestamp_half=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 6) % 2) + 1 );

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}
if( !isset($_REQUEST['symbol']) ){
    echo "symbol parameter required"; exit (1);
}


// helper functions
function toFixed($number, $decimals=2) {
  if(!is_numeric($number)){
        echo "lev book, not numeric: $number";
        $number=0; 
  } 
  return number_format($number, $decimals, ".", "");
}

echo date('Y-m-d H:i:s')." start<br />";

require_once 'stock_list.php';

$the_url=""; //fi-199.1.SGRE.MCE (the number depends on the country)

// since 2012 monthly, reduced to yearly... (with high-low)
$url_and_query="https://finance.yahoo.com/quote/".get_yahoo_quote($_REQUEST['symbol'])."/history?period1=1354316400&period2=".time()."&interval=1mo&filter=history&frequency=1mo"; //get_msn_quote($the_url_query_arr[$current_num_to_curl]);
echo "<br />stock $url_and_query<br />";
$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $url_and_query );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl ); //utf8_decode( not necessary
curl_close( $curl );
$response=preg_replace("/(\n|&nbsp;)/", " ", $response);
$response=preg_replace("/<tr/", "\nul", $response);
$response=preg_replace("/<\/(tr)>/", "\n", $response);
$response = preg_replace('/^[ \t]*[\r\n]+/m', '', $response); // remove blank lines
$response = preg_replace('/Dec 01, /', "\n", $response);
if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
echo "----------end----------";
echo "<br />";
$vars2get=['2012','2013','2014','2015','2016','2017'];
$results=array();
foreach($vars2get as $var2get){
    preg_match("/^".$var2get."[^>]*>[^>]*>[^>]*>[^>]*>([^<]*)<.*$/m", $response, $xxxx);
    if($debug) echo "bbb.<pre>".htmlspecialchars($xxxx[1])."</pre>";
    if(count($xxxx)>1 && trim($xxxx[1])!=""){
        $results[]=array($var2get."-12-01",str_replace(",","",$xxxx[1]));
    }
}
echo "".json_encode($results);

echo "<br />".date('Y-m-d H:i:s')." done<br />";



?>