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

//$the_url="https://www.google.com/finance?q="; deprecated
$the_url="https://finance.google.com/finance?q=";
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
    $response = preg_replace('/\ntd class="lft name">\s*Return on average equity\s*\ntd class=period>/',"\ntd class=\"lft name\">Return on average equity td class=period>",$response);
    $response = preg_replace('/\ntd class="lft name">\s*Operating margin\s*\ntd class=period>/',"\ntd class=\"lft name\">Operating margin td class=period>",$response);
    $response = preg_replace('/\ntd class="lft name">\s*Employees\s*\ntd class=period>/',"\ntd class=\"lft name\">Employees td class=period>",$response);
    //$response = preg_replace('/\ntd class="lft name">\s*(Return on average equity|Operating margin|Employees)\s*\ntd class=period>/',"\ntd class=\"lft name\">${1} td class=period>",$response);
    if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    //$response_arr=explode("\n",$response);
    preg_match("/^.*<title>([^:]*):.*$/m", $response, $title);
    $title=preg_replace('/( S\.?A\.?| [Ii][Nn][Cc]\.?)\s*$/m', '', $title[1]); 
    //$title = preg_grep("/<title>/", $response_arr);
    if($debug) echo "<br />title: ".$title."<br />";

    // value or price span class="pr"      <span class="pr"><span id="ref_304466804484872_l">932.24<
    preg_match("/\"pr\">\s*<[^>]*>\s*([^<]*)</m", $response, $value);
    $value=str_replace("%","",trim($value[1]));
    if($debug) echo "value: (".$value.")<br />";

    // div ... price-change .. div <div class="id-price-change nwp"><span class="ch bld"><span class="chr" id="ref_304466804484872_c">-3.71</span><span class="chr" id="ref_304466804484872_cp">(-0.40%)</span></span>
    preg_match("/id-price-change[^>]*>\s*[^>]*>\s*[^>]*>\s*([^<]*)</m", $response, $change);
    $change=$change[1];
    preg_match("/id-price-change[^>]*>\s*[^>]*>\s*[^>]*>\s*[^>]*>\s*[^>]*>\s*\(([^\)]*)\)/m", $response, $changep);
    $changep=str_replace("%","",trim($changep[1]));
    if($debug) echo "change: $change (".$changep.")<br />";
    
    preg_match("/^.*\"dividend_yield.*=\"val\"[^>]*>([^< ]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $dividend_yield);
    if(count($dividend_yield)>1 && strpos($dividend_yield[1], '/') !== FALSE){
        $divval=explode('/',$dividend_yield[1])[0];
        $yieldval=explode('/',$dividend_yield[1])[1];
        if($debug) echo "divyield: ".$dividend_yield[1]."<br />";
        if($debug) echo "div and yield: (".$divval.")   y=(".$yieldval.") <br />";
    }else{
        $dividend_yield="";
        $divval="";
        $yieldval="";
    }

    // guessed from shares and value (in billions)
    /*preg_match("/^.*\"market_cap.*=\"val\"[^>]*>([^<]*)(\s*<[\/]?[^>]*>)*\s* /m", $response, $mktcap);
    if(count($mktcap)>1){
        $mktcap=trim($mktcap[1]);
        if($debug) echo "mktcap: (".$mktcap.")<br />";
    }else{
        $mktcap="";
    }*/
    
    // num shares in billons
    preg_match("/^.*\"shares\".*=\"val\"[^>]*>([^<]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $shares);
    if($debug){echo " shares: ".print_r($shares)."<br />";}
    if(count($shares)>1){
        $shares=format_billions($shares[1]);
        if($debug) echo "shares: (".$shares.")<br />";
    }else{
        $shares=0;
    }
    

    preg_match("/^.*\"pe_ratio.*=\"val\"[^>]*>([^<]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $perval);
    if(count($perval)>1){
        $perval=trim($perval[1]);
        if($debug) echo "per: (".$perval.")<br />";
    }else{
        $perval="";
    }
    
    preg_match("/^.*\"beta\".*=\"val\"[^>]*>([^<]+)(\s*<[\/]?[^>]*>)*\s*/m", $response, $betaval);
    if(count($betaval)>1){
        $betaval=trim($betaval[1]);
        if($debug) echo "beta: (".$betaval.")<br />";
    }else{
        $betaval="";
    }
    preg_match("/^.*\"inst_own\".*=\"val\"[^>]*>([^<]+)(\s*<[\/]?[^>]*>)*\s*/m", $response, $instowned);
    if(count($instowned)>1){
        $instowned=trim($instowned[1]);
        if($debug) echo "inst_own: (".$instowned.")<br />";
    }else{
        $instowned="";
    }

    
    preg_match("/^.*\"eps\".*=\"val\"[^>]*>([^<]+)(\s*<[\/]?[^>]*>)*\s*/m", $response, $epsval);
    if(count($epsval)>1){
        $epsval=trim($epsval[1]);
        if($debug) echo "eps: (".$epsval.")<br />";
    }else{
        $epsval="";
    }

    preg_match("/^.*Return on average equity.*=period[^>]*>\s*([^<% ]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $roeval);
    if(count($roeval)>1){
        $roeval=trim($roeval[1]);
        if($debug) echo "roe: (".$roeval.")<br />";
    }else{
        $roeval="";
    }

    preg_match("/^.*Operating margin.*=period[^>]*>\s*([^<% ]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $om);
    if(count($om)>1){
        $om=trim($om[1]);
        if($debug) echo "om: (".$om.")<br />";
    }else{
        $om="";
    }

    preg_match("/^.*Employees.*=period[^>]*>\s*([^<% ]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $employees);
    if(count($employees)>1){
        $employees=str_replace(",","",trim($employees[1]));
        if($debug) echo "employees: (".$employees.")<br />";
    }else{
        $employees="";
    }

    preg_match("/^.*range_52week.*=\"val\"[^>]*>([^<]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $range_52week);
    if(count($range_52week)>1){
        $range_52week=str_replace(",","",trim($range_52week[1]));
        if($debug) echo "52weeks: (".$range_52week.")<br />";
    }else{
        $range_52week="";
    }

    $query_arr=explode(":",$the_url_query_arr[$current_num_to_curl]);
    $name=$query_arr[1];
    $market=$query_arr[0];
    
    // assignement to the array
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]=array();
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['name']=$name;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['market']=$market;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['title']=$title;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['value']=$value;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['session_change']=$change;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['session_change_percentage']=$changep;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['yield']=$yieldval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['dividend']=$divval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['eps']=$epsval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['beta']=$betaval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['inst_own']=$instowned;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['shares']=$shares;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['per']=$perval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['roe']=$roeval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['operating_margin']=$om;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['employees']=$employees;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['range_52week']=$range_52week;
    
    // to avoid google ban
    sleep(0.1);
}

if($debug) echo "<br />arr ".print_r($stock_details_arr)."<br />";

// update last updated number
$stock_last_detail_updated=($stock_last_detail_updated+$num_stocks_to_curl) % count($the_url_query_arr); // modulo to avoid big nums...
$stock_last_detail_updated_f = fopen("stock_last_detail_updated.txt", "w") or die("Unable to open file!");
fwrite($stock_last_detail_updated_f, $stock_last_detail_updated);
fclose($stock_last_detail_updated_f);




echo date('Y-m-d H:i:s')." ending stock_curl_details.php<br />";


?>