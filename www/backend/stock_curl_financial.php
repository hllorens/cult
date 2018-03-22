<?php

require_once("email_config.php");
require_once 'stock_helper_functions.php';

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}
if( !isset($_REQUEST['symbol']) ){
    echo "symbol parameter required"; exit (1);
}


$stock_financials_old=array(); // to store stocks.financials, typo "financials"
if(file_exists ( 'stocks.financials.json' )){
    echo "stocks.financials.json exists -> reading...<br />";
    $stock_financials_old = json_decode(file_get_contents('stocks.financials.json'), true);
}else{
    echo "stocks.financials.json does NOT exist -> using an empty array<br />";
}
$stock_financial=array();
//TODO better just put in a var and on debug print it, then other scripts can do whatever with that var...
//The name can be guessed from $name $market
//We can also calculate the "clean" in that var so the wrapper script can update both stocks.formatted.json and financials...

if(substr($_REQUEST['symbol'],0,5)=="INDEX"){echo "index (no financials), nothing to be done"; return;} continue; // skip indexes

// google: does not have financials for BME/MCE/MC
// yahoo:  https://finance.yahoo.com/quote/SGRE.MC/financials?p=SGRE.MC // prob multiple pages: balance-sheet?p cash-flow?p ...
// yahoo-statistics: https://finance.yahoo.com/quote/SGRE.MC/key-statistics?p=SGRE.MC
// msn: https://www.msn.com/en-us/money/stockdetails/financials/ //fi-199.1.SGRE.MCE (the number depends on the country)
$the_url="https://www.msn.com/en-us/money/stockdetails/financials/"; //fi-199.1.SGRE.MCE (the number depends on the country)
$url_and_query=$the_url.get_msn_quote($_REQUEST['symbol']); //get_msn_quote($_REQUEST['symbol']);
echo "stock $url_and_query<br />";
$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $url_and_query );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl ); //utf8_decode( not necessary
curl_close( $curl );
$response=preg_replace("/(\n|&nbsp;)/", " ", $response);
//if($debug) echo "base .<pre>".htmlspecialchars($response)."</pre>";
$response=preg_replace("/<title>/", "\ntd <title>", $response);
$response=preg_replace("/<\/title>/", "\n", $response);
$response=preg_replace("/<td/", "\ntd", $response);
//if($debug)  echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
$response=preg_replace("/<\/(td|table|ul)>/", "\n", $response);
//$response=preg_replace("/^[^t][^d].*$/m", "", $response);
$response = preg_replace('/^[ \t]*[\r\n]+/m', '', $response); // remove blank lines
$response = preg_replace('/\n(.*=\"val\".*)[\r\n]+/m', '${1}', $response); // remove blank lines
$response = preg_replace('/title=\'(Period End Date|Total Revenue|Operating Income|Net Income|Total Current Assets|Total Assets|Total Current Liabilities|Total Liabilities|Total Equity|Diluted EPS|Basic EPS|Cash Flow from Operating Activities|Cash Flow from Investing Activities|Cash Flow from Financing Activities|Free Cash Flow)\'[^>]*>\s*/', "\n", $response);
//$response = preg_replace('/\ntd class="lft name">Return on average equity\s*\ntd class=period>/',"\ntd class=\"lft name\">Return on average equity td class=period>",$response);
//    if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
echo "----------end----------";
preg_match("/^Period End Date(.*)$/m", $response, $xxxx);
preg_match_all("/title='([^\/]*\/[^\/]*\/[^']*)'/", $xxxx[1], $period_arr);
//var_dump($period_arr[1]);
echo "<br />";
    
    
$vars2get=['Total Revenue','Operating Income','Net Income']; //,'Basic EPS'
$results=array();
foreach($vars2get as $var2get){
    if($debug) echo "getting results for: $var2get<br />";
    preg_match("/^".$var2get."(.*)$/m", $response, $xxxx);
    preg_match_all("/title='([^']*)'/", $xxxx[1], $xxxx_arr);
    $results[$var2get]=str_replace(",","",$xxxx_arr[1]);
}

$query_arr=explode(":",$_REQUEST['symbol']);
$name=$query_arr[1];
$market=$query_arr[0];

$first_time_financials=false;
if(!array_key_exists($_REQUEST['symbol'],$stock_financials_old)){
    echo "first time getting financials for ".$_REQUEST['symbol'];
    $first_time_financials=true;
    send_mail('First time financials '.$name,"<br />Yay, first time<br /><br />","hectorlm1983@gmail.com");
    $stock_financial['name']=$name;
    $stock_financial['market']=$market;
}else{
    $stock_financial=$stock_financials_old[$_REQUEST['symbol']];
}

for ($period=0;$period<count($period_arr[1]);$period++){
    // Here we could detect if past is being changed...
    $period_arr_arr=explode("/",$period_arr[1][$period]);
    if(count($period_arr_arr)!=3 || strlen($period_arr_arr[2])!=4){
        echo "ERROR (financials): incorrect format ".$period_arr[1][$period];
        send_mail('Error financials '.$name,"<br />"."ERROR (MSN INCOME): incorrect format period ".$period_arr[1][$period]."<br /><br />","hectorlm1983@gmail.com");
        continue;
    }
    $period_arr[1][$period]=$period_arr_arr[2]."-".str_pad($period_arr_arr[0],2,"0",STR_PAD_LEFT)."-".str_pad($period_arr_arr[1],2,"0",STR_PAD_LEFT);
    $new_period=true;
    foreach(array_keys($stock_financial) as $stock_financial_period){
        if($period_arr[1][$period]==$stock_financial_period){
            $new_period=false;
            break;
        }
        if(substr($period_arr[1][$period],0,4)==substr($stock_financial_period,0,4)){
            echo "<br />ERROR: financial period change same year new ".$period_arr[1][$period]." vs existing ".$stock_financial_period." destroying existing one<br />";
            send_mail('Error financials '.$name,"<br />financial period change same year new ".$period_arr[1][$period]." vs existing, destroying existing one ".$stock_financial_period."<br /><br />","hectorlm1983@gmail.com");
            unset($stock_financial[$stock_financial_period]);
            $stock_financial[$period_arr[1][$period]]=array();
            break;
        }
    }
    $new_period_report="";
    foreach($vars2get as $var2get){
        if($debug) echo "updating vars: $var2get<br />";
        if(!array_key_exists($var2get,$stock_financial[$period_arr[1][$period]])){
            if($results[$var2get][$period]=="-" || $results[$var2get][$period]==""){
                $results[$var2get][$period]=0;
                send_mail('Error financials '.$name,"<br />Empty - in $var2get (stock_curl_financial.php), setting 0<br /><br />","hectorlm1983@gmail.com");
            }
            $stock_financial[$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
            $new_period_report.="<br />".$var2get.":".$stock_financial[$period_arr[1][$period]][$var2get]."<br />";
        }else{
            if($stock_financial[$period_arr[1][$period]][$var2get]!=$results[$var2get][$period]){
                echo "ERROR changing the past!!! (keeping new value)";
                send_mail('financials change past '.$name,"<br />In ".$_REQUEST['symbol']." period:".$period_arr[1][$period]." var:".$var2get."  old:".$stock_financial[$period_arr[1][$period]][$var2get]." != new:".$results[$var2get][$period]."<br /><br />","hectorlm1983@gmail.com");
                $stock_financial[$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
            }
        }
    }
    if($new_period){
        send_mail('new financials '.$name,"<br />In ".$_REQUEST['symbol']." period:".$period_arr[1][$period]." ".$new_period_report."<br /><br />","hectorlm1983@gmail.com");
    }
}

if($debug) var_dump($stock_financial);



?>