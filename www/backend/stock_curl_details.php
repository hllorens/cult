<?php

require_once 'stock_list.php';

echo date('Y-m-d H:i:s')." starting stock_curl_details.php<br />";

$num_stocks_to_curl=1;
$stock_last_detail_updated=0;
if(file_exists ( 'stock_last_detail_updated.txt' )){
    $stock_last_detail_updated=intval(fgets(fopen('stock_last_detail_updated.txt', 'r')));
}
echo " curr num to curl $stock_last_detail_updated num_stocks_to_curl=$num_stocks_to_curl<br />";



$the_url="https://www.google.com/finance?q=";
$vals=",";
$the_url_query_arr = explode(",", $stock_list);
for ($i=0;$i<$num_stocks_to_curl;$i++){
    $current_num_to_curl=($stock_last_detail_updated+$i) % count($the_url_query_arr);
    echo $the_url_query_arr[$current_num_to_curl]." $current_num_to_curl<br />";
    $url_and_query=$the_url.$the_url_query_arr[$current_num_to_curl];
    echo $the_url_query_arr[$current_num_to_curl]." $current_num_to_curl  $url_and_query<br />";
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url_and_query );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );
    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
    echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<title>/", "\ntd <title>", $response);
    $response=preg_replace("/<\/title>/", "\n", $response);
    $response=preg_replace("/<td/", "\ntd", $response);
    //echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<\/(td|table)>/", "\n", $response);
    //$response=preg_replace("/^[^t][^d].*$/m", "", $response);
    $response = preg_replace('/^[ \t]*[\r\n]+/m', '', $response); // remove blank lines
    $response = preg_replace('/\n(.*=\"val\".*)[\r\n]+/m', '${1}', $response); // remove blank lines
    $response = preg_replace('/\ntd class="lft name">Return on average equity\s*\ntd class=period>/',"\ntd class=\"lft name\">Return on average equity td class=period>",$response);
    echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    //$response_arr=explode("\n",$response);
    preg_match("/^.*<title>([^:]*):.*$/m", $response, $title);
    $title=preg_replace('/( S\.?A\.?| [Ii][Nn][Cc]\.?)\s*$/m', '', $title[1]); 
    //$title = preg_grep("/<title>/", $response_arr);
    echo "title: ".$title."<br />";

    preg_match("/^.*dividend_yield.*=\"val\"[^>]*>([^< ]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $dividend_yield);
    $divval=explode('/',$dividend_yield[1])[0];
    $yieldval=explode('/',$dividend_yield[1])[1];

    echo "divyield: ".$dividend_yield[1]."<br />";
    echo "div and yield: (".$divval.")   y=(".$yieldval.") <br />";
    
    preg_match("/^.*pe_ratio.*=\"val\"[^>]*>([^< ]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $perval);
    $perval=$perval[1];
    echo "per: (".$perval.")<br />";

    preg_match("/^.*\"beta\".*=\"val\"[^>]*>([^<]+)(\s*<[\/]?[^>]*>)*\s*/m", $response, $betaval);
    $betaval=trim($betaval[1]);
    echo "beta: (".$betaval.")<br />";

    
    
    preg_match("/^.*\"eps\".*=\"val\"[^>]*>([^< ]+)(\s*<[\/]?[^>]*>)*\s*/m", $response, $epsval);
    $epsval=$epsval[1];
    echo "eps: (".$epsval.")<br />";

    preg_match("/^.*Return on average equity.*=period[^>]*>([^< ]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $roeval);
    $roeval=$roeval[1];
    echo "roe: (".$roeval.")<br />";

    preg_match("/^.*range_52week.*=\"val\"[^>]*>([^<]*)(\s*<[\/]?[^>]*>)*\s*/m", $response, $range_52week);
    $range_52week=trim($range_52week[1]);
    echo "52weeks: (".$range_52week.")<br />";

    $query_arr=explode(":",$the_url_query_arr[$i]);
    $name=$query_arr[1];
    $market=$query_arr[0];
    
    $vals="$vals,\"".$the_url_query_arr[$i]."\": {\"name\": \"$name\",\"market\": \"$market\",\"title\": \"$title\",\"yield\": \"$yieldval\",\"dividend\": \"$divval\",\"eps\": \"$epsval\",\"beta\": \"$betaval\",\"per\": \"$perval\",\"roe\": \"$roeval\",\"range_52week\": \"$range_52week\" }";
    echo $vals."<br />";
    
    // ADD HERE EPS HISTORY UPDATE
    
    //preg_match("/^(td.*)$/m", $response, $td_array);
    //var_dump($td_array);
    sleep(0.1);
}

// update last updated number
$stock_last_detail_updated=$stock_last_detail_updated+$num_stocks_to_curl;
$stock_last_detail_updated_f = fopen("stock_last_detail_updated.txt", "w") or die("Unable to open file!");
fwrite($stock_last_detail_updated_f, $stock_last_detail_updated);
fclose($stock_last_detail_updated_f);

echo date('Y-m-d H:i:s')." ending stock_curl_details.php<br />";




//    echo "Getting div/yield for $i" | tee -a $destination/ERROR.log; 
//    theinfo=`echo "https://www.google.com/finance?q=$i" | wget -O- -i- | tr "\n" " " |  sed "s/<title>/\ntd <title>/g" | 
//          sed "s/<\/title>/\n/g" |  sed "s/<td/\ntd/g" | sed "s/<\/td>/\n/g" | sed "s/<\/table>/\n/g" | grep "^td " | sed "s/&nbsp;//g"`
//    title=`echo "$theinfo"  | grep "<title>" | sed "s/^[^>]*>[[:blank:]]*\([^:]*\):.*\$/\1/" | sed "s/ S\.\?A\.\?\$//" | sed "s/ [Ii][Nn][Cc]\.\?\$//"`
//    yieldval=`echo "$theinfo"  | grep -A 1 dividend_yield | grep '="val"' | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/" | sed "s/^[^\/]*\/\([^[:blank:]]*\)[[:blank:]]*/\1/"`
//    divval=`echo "$theinfo"  | grep -A 1 dividend_yield | grep '="val"'   | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/" | sed "s/^\([^\/]*\)\/.*\$/\1/"`
//    perval=`echo "$theinfo"  | grep -A 1 pe_ratio | grep '="val"' | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/"`
//    betaval=`echo "$theinfo"  | grep -A 1 "\"beta\"" | grep '="val"' | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/"`
//    epsval=`echo "$theinfo"  | grep -A 1 "\"eps\"" | tail -n 1 | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/"`
//    roeval=`echo "$theinfo"  | grep -A 1 "Return on average equity" | grep '="val"' | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/"`

//    range_52week=`echo "$theinfo"  | grep -A 1 range_52week | grep '="val"' | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/" | sed "s/,//g"`
//    name=`echo $i | cut -d : -f 2`;
//    market=`echo $i | cut -d : -f 1`
//    vals="${vals},\"$i\": {\"name\": \"$name\",\"market\": \"$market\",\"title\": \"$title\",\"yield\": \"$yieldval\",\"dividend\": \"$divval\",\"eps\": \"$epsval\",\"beta\": \"$betaval\",\"per\": \"$perval\",\"roe\": \"$roeval\",\"range_52week\": \"$range_52week\" }"
//    sleep 1; # to avoid overloading google*/
//done

?>