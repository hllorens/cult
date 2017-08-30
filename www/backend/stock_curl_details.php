<?php

// USAGE: provides $stock_details_arr variable for other scripts to use
//        DEPENDS ON:
//            - $num_stocks_to_curl (tune it to avoid server timeout or google ban), e.g., set it to 5 stocks at a time
//            - stock_last_detail_updated.txt to indicate the last stock for which details were retrieved

require_once 'stock_list.php';

echo date('Y-m-d H:i:s')." starting stock_curl_details.php<br />";



$num_stocks_to_curl=5;
$stock_last_detail_updated=0;
if(file_exists ( 'stock_last_detail_updated.txt' )){
    $stock_last_detail_updated=intval(fgets(fopen('stock_last_detail_updated.txt', 'r')));
}
echo " curr_stock_num_to_curl=$stock_last_detail_updated num_stocks_to_curl=$num_stocks_to_curl<br />";




$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

$stock_details_arr=array();

$the_url="https://www.google.com/finance?q=";
//$vals=",";
$the_url_query_arr = explode(",", $stock_list);
$num_stocks_to_curl=min($num_stocks_to_curl,count($the_url_query_arr)); // make sure we do not duplicate...
for ($i=0;$i<$num_stocks_to_curl;$i++){
    $current_num_to_curl=($stock_last_detail_updated+$i) % count($the_url_query_arr);
    $url_and_query=$the_url.$the_url_query_arr[$current_num_to_curl];
    echo "stock $url_and_query<br />";
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url_and_query );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );
    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
    if($debug) echo "base .<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<title>/", "\ntd <title>", $response);
    $response=preg_replace("/<\/title>/", "\n", $response);
    $response=preg_replace("/<td/", "\ntd", $response);
    //if($debug)  echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<\/(td|table)>/", "\n", $response);
    //$response=preg_replace("/^[^t][^d].*$/m", "", $response);
    $response = preg_replace('/^[ \t]*[\r\n]+/m', '', $response); // remove blank lines
    $response = preg_replace('/\n(.*=\"val\".*)[\r\n]+/m', '${1}', $response); // remove blank lines
    $response = preg_replace('/\ntd class="lft name">Return on average equity\s*\ntd class=period>/',"\ntd class=\"lft name\">Return on average equity td class=period>",$response);
    if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    //$response_arr=explode("\n",$response);
    preg_match("/^.*<title>([^:]*):.*$/m", $response, $title);
    $title=preg_replace('/( S\.?A\.?| [Ii][Nn][Cc]\.?)\s*$/m', '', $title[1]); 
    //$title = preg_grep("/<title>/", $response_arr);
    echo "<br /><br />title: ".$title."<br />";

    preg_match("/^.*dividend_yield.*=\"val\"[^>]*>([^< ]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $dividend_yield);
    $divval=explode('/',$dividend_yield[1])[0];
    $yieldval=explode('/',$dividend_yield[1])[1];

    echo "divyield: ".$dividend_yield[1]."<br />";
    echo "div and yield: (".$divval.")   y=(".$yieldval.") <br />";
    
    preg_match("/^.*pe_ratio.*=\"val\"[^>]*>([^<]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $perval);
    $perval=trim($perval[1]);
    echo "per: (".$perval.")<br />";

    preg_match("/^.*\"beta\".*=\"val\"[^>]*>([^<]+)(\s*<[\/]?[^>]*>)*\s*/m", $response, $betaval);
    $betaval=trim($betaval[1]);
    echo "beta: (".$betaval.")<br />";

    
    preg_match("/^.*\"eps\".*=\"val\"[^>]*>([^<]+)(\s*<[\/]?[^>]*>)*\s*/m", $response, $epsval);
    $epsval=trim($epsval[1]);
    echo "eps: (".$epsval.")<br />";

    preg_match("/^.*Return on average equity.*=period[^>]*>\s*([^<% ]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $roeval);
    $roeval=trim($roeval[1]);
    echo "roe: (".$roeval.")<br />";

    preg_match("/^.*range_52week.*=\"val\"[^>]*>([^<]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $range_52week);
    $range_52week=str_replace(",","",trim($range_52week[1]));
    echo "52weeks: (".$range_52week.")<br />";

    $query_arr=explode(":",$the_url_query_arr[$current_num_to_curl]);
    $name=$query_arr[1];
    $market=$query_arr[0];
    
    // assignement to the array
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]=array();
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['name']=$name;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['market']=$market;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['title']=$title;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['yield']=$yieldval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['dividend']=$divval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['eps']=$epsval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['beta']=$betaval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['per']=$perval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['roe']=$roeval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['range_52week']=$range_52week;
    // to avoid google ban
    sleep(0.1);
}

echo "<br />arr ".print_r($stock_details_arr)."<br />";

// update last updated number
$stock_last_detail_updated=$stock_last_detail_updated+$num_stocks_to_curl;
$stock_last_detail_updated_f = fopen("stock_last_detail_updated.txt", "w") or die("Unable to open file!");
fwrite($stock_last_detail_updated_f, $stock_last_detail_updated);
fclose($stock_last_detail_updated_f);




echo date('Y-m-d H:i:s')." ending stock_curl_details.php<br />";


?>