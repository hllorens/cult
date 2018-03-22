<?php

// USAGE: provides $stock_details_arr variable for other scripts to use
//        DEPENDS ON:
//            - $num_stocks_to_curl (tune it to avoid server timeout or google ban), e.g., set it to 5 stocks at a time
//            - stock_last_detail_updated.txt to indicate the last stock for which details were retrieved

require_once 'stock_list.php';

echo date('Y-m-d H:i:s')." starting stock_curl_details.php<br />";
require_once("email_config.php");


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
//$the_url="https://finance.google.com/finance?q="; deprecated too 2018-03
//$the_url="https://finance.google.com/search?q=";  quite not...
$the_url="https://www.msn.com/en-us/money/stockdetails/";

//$vals=",";
$the_url_query_arr = explode(",", $stock_list);
$num_stocks_to_curl=min($num_stocks_to_curl,count($the_url_query_arr)); // make sure we do not duplicate...
for ($i=0;$i<$num_stocks_to_curl;$i++){
    $current_num_to_curl=($stock_last_detail_updated+$i) % count($the_url_query_arr);
    $url_and_query=$the_url.get_msn_quote($the_url_query_arr[$current_num_to_curl]);
    echo "stock $url_and_query<br />";
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url_and_query );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );
    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
    //if($debug) echo "base .<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<td/", "\ntd", $response);
    $response=preg_replace("/<\/(td|table)>/", "\n", $response);
    if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    preg_match("/^.* class=\"header-companyname[^>]*>\s*<[^>]*>\s*([^<]*)<.*$/m", $response, $title);
    if(count($title)<2){
        echo "<br />Empty value title, email sent...<br />";
        send_mail('Error '.$the_url_query_arr[$current_num_to_curl],'<br />Empty title, skipping...<br /><br />',"hectorlm1983@gmail.com");
        continue;
    }
    $title=substr(preg_replace('/( S\.?A\.?| [Ii][Nn][Cc]\.?)\s*$/m', '', $title[1]),0,20); // remove ending and reduce to 20 chars
    if($debug) echo "<br />title: ".$title."<br />";

    preg_match("/data-role=\"currentvalue\"[^>]*>\s*([^<]*)</m", $response, $value);
    if(count($value)<2){
        echo "<br />Empty value skipping, email sent...<br />";
        send_mail('Error '.$the_url_query_arr[$current_num_to_curl],'<br />Empty value, skipping...<br /><br />',"hectorlm1983@gmail.com");
        continue;
    }
    $value=trim($value[1]);
    if($debug) echo "value: (".$value.")<br />";

    preg_match("/data-role=\"percentchange\"[^>]*>\s*([^<]*)</m", $response, $changep);
    if(count($changep)<2){
        echo "<br />Empty changep skipping, email sent...<br />";
        send_mail('Error '.$the_url_query_arr[$current_num_to_curl],'<br />Empty changep, skipping...<br /><br />',"hectorlm1983@gmail.com");
        continue;
    }
    $changep=$changep[1];
    $changep=str_replace("%","",trim($changep));
    $changep=str_replace("+","",trim($changep));
    if($debug) echo "change: (".$changep.")<br />";

    preg_match("/52Wk Range<[^<]*<[^<]*<[^<]*<p [^>]*>\s*([^<]*)\s*</m", $response, $range_52week);
    if(count($range_52week)>1){
        $range_52week=str_replace(",","",trim($range_52week[1]));
        if($debug) echo "52weeks: (".$range_52week.")<br />";
        if(strpos($range_52week, '-') !== false){
            $parts = explode('-', $range_52week);
            $range_52week_low=trim($parts[0]);
            $range_52week_high=trim($parts[1]);
        }else{
            $range_52week="";
            $range_52week_high=0;
            $range_52week_low=0;
        }
    }else{
        echo "<br />Empty range_52week skipping, email sent...<br />";
        send_mail('Error '.$the_url_query_arr[$current_num_to_curl],'<br />Empty range_52week, skipping...<br /><br />',"hectorlm1983@gmail.com");
        continue;
    }

    $dividend_yield=0;
    $divval=0;
    $yieldval=0;
    $shares=0;
    $mktcap=0;
    $shares_source="direct";

    if(substr($the_url_query_arr[$current_num_to_curl],0,5)!="INDEX"){
        preg_match("/>\s*Dividend\s*Rate[^<]*<[^<]*<[^<]*<[^<]*<p [^>]*>\s*([^<]*)\s*</m", $response, $dividend_yield);
        if(count($dividend_yield)>1 && strpos($dividend_yield[1], '(') !== FALSE){
            $divval=explode('(',$dividend_yield[1])[0];
            $yieldval=str_replace(")","",explode('(',$dividend_yield[1])[1]);
            $yieldval=str_replace("%","",trim($yieldval));
            if($yieldval=='-' || $yieldval==''){$divval=0;$yieldval=0;}
            if($debug) echo "divyield: ".$dividend_yield[1]."<br />";
            if($debug) echo "div and yield: (".$divval.")   y=(".$yieldval.") <br />";
        }else{
            // no error, optional, we can continue if it does not exist
            $divval="0";
            $yieldval="0";
        }
        
        preg_match("/>\s*Market Cap[^<]*<[^<]*<[^<]*<[^<]*<p [^>]*>\s*([^<]*)\s*</m", $response, $mktcap);
        if(count($mktcap)>1){
            $mktcap=trim($mktcap[1]);
            if($mktcap=="-" || $mktcap==""){
                echo "<br />Empty mktcap skipping, email sent...<br />";
                send_mail('Error '.$the_url_query_arr[$current_num_to_curl],'<br />Empty mktcap, skipping...<br /><br />',"hectorlm1983@gmail.com");
                continue;
            }
            $mktcap=format_billions($mktcap);
        }else{
            echo "<br />Empty mktcap skipping, email sent...<br />";
            send_mail('Error '.$the_url_query_arr[$current_num_to_curl],'<br />Empty mktcap, skipping...<br /><br />',"hectorlm1983@gmail.com");
            continue;
        }
        preg_match("/>\s*Shares Outstanding[^<]*<[^<]*<[^<]*<[^<]*<p [^>]*>\s*([^<]*)\s*</m", $response, $shares);
        if(count($shares)>1){
            $shares=trim($shares[1]);
            $shares_source="direct";
            if($shares=="-" || $shares==""){
                // shares in billions guessed from market cap in billions (often shares appear as -, while mktcap is often available)
                $shares_source="from_mktcap_found_empty";
                $shares=toFixed(floatval($mktcap)/floatval($value),2,"cap and shares");
                if(floatval($shares)<0.05){ // if shares are as little the calculations for eps, etc can be wrong, consider using other calculations
                    echo "<br />Too few shares..., email sent...<br />";
                    send_mail('Error '.$the_url_query_arr[$current_num_to_curl],'<br />Too few shares $mktcap/$value=$shares, skipping...<br /><br />',"hectorlm1983@gmail.com");
                    continue;
                }
            }else{
                $shares=format_billions($shares);
            }
        }else{
            // shares in billions guessed from market cap in billions (often shares appear as -, while mktcap is often available)
            $shares_source="from_mktcap_not_found";
            $shares=toFixed(floatval($mktcap)/floatval($value),3,"cap and shares");
            if(floatval($shares)<0.050){ // if shares are as little the calculations for eps, etc can be wrong, consider using other calculations
                echo "<br />Too few shares..., email sent...<br />";
                send_mail('Error '.$the_url_query_arr[$current_num_to_curl],'<br />Too few shares $mktcap/$value=$shares, skipping (consider removing this stock, too small)...<br /><br />',"hectorlm1983@gmail.com");
                continue;
            }
        }
    }


    
    $query_arr=explode(":",$the_url_query_arr[$current_num_to_curl]);
    $name=$query_arr[1];
    $market=$query_arr[0];
    // assignment to the array
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]=array();
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['name']=$name;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['market']=$market;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['title']=$title;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['value']=$value;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['session_change_percentage']=$changep;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['yield']=$yieldval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['dividend']=$divval;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['mktcap']=$mktcap;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['shares']=$shares;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['shares_source']=$shares_source;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['range_52week_high']=$range_52week_high;
    $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]['range_52week_low']=$range_52week_low;
    

    // to avoid server ban
    sleep(0.15);
}

if($debug) echo "<br />arr ".print_r($stock_details_arr)."<br />";

// update last updated number
$stock_last_detail_updated=($stock_last_detail_updated+$num_stocks_to_curl) % count($the_url_query_arr); // modulo to avoid big nums...
$stock_last_detail_updated_f = fopen("stock_last_detail_updated.txt", "w") or die("Unable to open file!");
fwrite($stock_last_detail_updated_f, $stock_last_detail_updated);
fclose($stock_last_detail_updated_f);
echo date('Y-m-d H:i:s')." ending stock_curl_details.php<br />";


?>