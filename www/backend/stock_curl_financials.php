<?php

// THIS IS TOO MUCH, IT IS PROBABLY BETTER TO NOT MANAGE STORE THIS INFO BUT JUST RELY ON EPS
// THEN EVERY QUARTER, YEAR OR BEFORE BUYING LEARN TO USE THIS DATA
// INCOME: GROSS PROFIT MARGIN (GROSS PROFIT/REVENUES) NET PROFIT MARGIN (NET INCOME/REVENUES)
// CURRENT RATIO >1.5 ...
// know the cap, stocksnum, spain pib, spain budget

// USAGE: provides $stock_financials_arr variable for other scripts to use
//        DEPENDS ON:
//            - $num_stocks_to_curl (tune it to avoid server timeout or google ban), e.g., set it to 5 stocks at a time
//            - stock_last_financial_updated.txt to indicate the last stock for which financials were retrieved

require_once 'stock_list.php';

echo date('Y-m-d H:i:s')." starting stock_curl_financials.php<br />";

// helper functions
function toFixed($number, $decimals=2) {
  return number_format($number, $decimals, ".", "");
}


$num_stocks_to_curl=2;
$stock_last_financial_updated=0;
if(file_exists ( 'stock_last_financial_updated.txt' )){
    $stock_last_financial_updated=intval(fgets(fopen('stock_last_financial_updated.txt', 'r')));
}
echo " curr_stock_num_to_curl=$stock_last_financial_updated num_stocks_to_curl=$num_stocks_to_curl<br />";




$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

$stock_financials_arr=array(); // to store stocks.financials, typo "financials"
if(file_exists ( 'stocks.financials.json' )){
    echo "stocks.financials.json exists -> reading...<br />";
    $stock_financials_arr = json_decode(file_get_contents('stocks.financials.json'), true);
}else{
    echo "stocks.financials.json does NOT exist -> using an empty array<br />";
}


// google: does not have financials for BME/MCE/MC
// yahoo:  https://finance.yahoo.com/quote/SGRE.MC/financials?p=SGRE.MC // prob multiple pages: balance-sheet?p cash-flow?p ...
// yahoo-statistics: https://finance.yahoo.com/quote/SGRE.MC/key-statistics?p=SGRE.MC
// msn: https://www.msn.com/en-us/money/stockdetails/financials/ //fi-199.1.SGRE.MCE (the number depends on the country)
$the_url="https://www.msn.com/en-us/money/stockdetails/financials/"; //fi-199.1.SGRE.MCE (the number depends on the country)
//$vals=",";
$the_url_query_arr = explode(",", $stock_list);
$num_stocks_to_curl=min($num_stocks_to_curl,count($the_url_query_arr)); // make sure we do not duplicate...
for ($i=0;$i<$num_stocks_to_curl;$i++){
    $current_num_to_curl=($stock_last_financial_updated+$i) % count($the_url_query_arr);
    
    if(substr($the_url_query_arr[$current_num_to_curl],0,5)=="INDEX") continue; // skip indexes
    
    $url_and_query=$the_url.get_msn_quote($the_url_query_arr[$current_num_to_curl]); //get_msn_quote($the_url_query_arr[$current_num_to_curl]);
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
    $response = preg_replace('/title=\'(Period End Date|Total Revenue|Operating Income|Net Income|Total Current Assets|Total Assets|Total Current Liabilities|Total Liabilities|Total Equity|Cash Flow from Operating Activities|Cash Flow from Investing Activities|Cash Flow from Financing Activities|Free Cash Flow)\'[^>]*>\s*/', "\n", $response);
    //$response = preg_replace('/\ntd class="lft name">Return on average equity\s*\ntd class=period>/',"\ntd class=\"lft name\">Return on average equity td class=period>",$response);
//    if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    echo "----------end----------";
    preg_match("/^Period End Date(.*)$/m", $response, $xxxx);
    preg_match_all("/title='([^\/]*\/[^\/]*\/[^']*)'/", $xxxx[1], $period_arr);
    //var_dump($period_arr[1]);
    echo "<br />";
    
    
    $vars2get=['Total Revenue','Operating Income','Net Income'];
    $results=array();
    foreach($vars2get as $var2get){
        preg_match("/^".$var2get."(.*)$/m", $response, $xxxx);
        preg_match_all("/title='([^']*)'/", $xxxx[1], $xxxx_arr);
        $results[$var2get]=str_replace(",","",$xxxx_arr[1]);
    }

    $query_arr=explode(":",$the_url_query_arr[$current_num_to_curl]);
    $name=$query_arr[1];
    $market=$query_arr[0];
    // assignement to the array
    if(!array_key_exists($the_url_query_arr[$current_num_to_curl],$stock_financials_arr)){
        $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]]=array();
        $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]]['name']=$name;
        $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]]['market']=$market;
    }
    for ($period=0;$period<count($period_arr[1]);$period++){
        // Here we could detect if past is being changed...
        $period_arr_arr=explode("/",$period_arr[1][$period]);
        if(count($period_arr_arr)!=3 || strlen($period_arr_arr[2])!=4){
            echo "ERROR (MSN INCOME): incorrect format ".$period_arr[1][$period];
            $past_change_log_f = fopen("past-change-log.txt", "a") or die("Unable to open past-change-log.txt!");
            fwrite($past_change_log_f, "\n".date("Y-m-d")." In ".$the_url_query_arr[$current_num_to_curl]." period:".$period_arr[1][$period]." incorrect format.");
            fclose($past_change_log_f);
        }
        $period_arr[1][$period]=$period_arr_arr[2]."-".str_pad($period_arr_arr[0],2,"0",STR_PAD_LEFT)."-".str_pad($period_arr_arr[1],2,"0",STR_PAD_LEFT);
        if(!array_key_exists($period_arr[1][$period],$stock_financials_arr[$the_url_query_arr[$current_num_to_curl]])){$stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]=array();}
        foreach($vars2get as $var2get){
            if(!array_key_exists($var2get,$stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]])){
                $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
            }else{
                if($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]][$var2get]!=$results[$var2get][$period]){
                    echo "ERROR changing the past!!!";
                    $past_change_log_f = fopen("past-change-log.txt", "a") or die("Unable to open past-change-log.txt!");
                    fwrite($past_change_log_f, "\n".date("Y-m-d")." In ".$the_url_query_arr[$current_num_to_curl]." period:".$period_arr[1][$period]." var:".$var2get."  old:".$stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]][$var2get]." != new:".$results[$var2get][$period]);
                    fclose($past_change_log_f);
                }
            }
        }
        $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['operating_margin']=toFixed(floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['Operating Income'])/floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['Total Revenue']));
        $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['net_margin']=toFixed(floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['Net Income'])/floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['Total Revenue']));
    }
    //var_dump($stock_financials_arr);

    // BETTER TO IGNORE JSON AND JUST PARSE TRs... 
    $url_and_query="https://finance.yahoo.com/quote/".get_yahoo_quote($the_url_query_arr[$current_num_to_curl])."/balance-sheet?p=".get_yahoo_quote($the_url_query_arr[$current_num_to_curl]); //get_msn_quote($the_url_query_arr[$current_num_to_curl]);
    echo "stock $url_and_query<br />";
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url_and_query );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    $response2 = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );
    $response2=preg_replace("/(\n|&nbsp;)/", " ", $response2);
    //if($debug) echo "base .<pre>".htmlspecialchars($response2)."</pre>";
    $response2=preg_replace("/<title>/", "\ntd <title>", $response2);
    $response2=preg_replace("/<\/title>/", "\n", $response2);
    $response2=preg_replace("/<tr/", "\ntr", $response2);
    //if($debug)  echo "aaa.<pre>".htmlspecialchars($response2)."</pre>";
    $response2=preg_replace("/<\/(tr|table|ul)>/", "\n", $response2);
    //$response2=preg_replace("/^[^t][^d].*$/m", "", $response2);
    $response2 = preg_replace('/^[ \t]*[\r\n]+/m', '', $response2); // remove blank lines
    //$response2 = preg_replace('/\n(.*=\"val\".*)[\r\n]+/m', '${1}', $response2); // remove blank lines
    

    //if($debug) echo "aaa.<pre>".htmlspecialchars($response2)."</pre>";

    echo "----------end----------";
    preg_match("/^.*Period Ending(.*)$/m", $response2, $xxxx);
    preg_match_all("/<span[^>]*>([^\/]*\/[^\/]*\/[^<]*)<\/span>/", $xxxx[1], $period_arr);
    echo "<br />";
        
    $vars2get=['Total Current Assets','Total Assets','Total Current Liabilities','Total Liabilities'];
    // EQUITY=TOTAL ASSETS - TOTAL LIABILITES
    $results=array();
    foreach($vars2get as $var2get){
        preg_match("/".$var2get."\s*<(.*)$/m", $response2, $xxxx);
        preg_match_all("/<span[^>]*>([^<]*)<\/span>/", $xxxx[1], $xxxx_arr);
        $results[$var2get]=str_replace(",","",$xxxx_arr[1]);
    }
    
    for ($period=0;$period<count($period_arr[1]);$period++){
        // Here we could detect if past is being changed...
        $period_arr_arr=explode("/",$period_arr[1][$period]);
        if(count($period_arr_arr)!=3 || strlen($period_arr_arr[2])!=4){
            echo "ERROR (YAHOO BALANCE): incorrect format ".$period_arr[1][$period];
            $past_change_log_f = fopen("past-change-log.txt", "a") or die("Unable to open past-change-log.txt!");
            fwrite($past_change_log_f, "\n".date("Y-m-d")." In ".$the_url_query_arr[$current_num_to_curl]." period:".$period_arr[1][$period]." incorrect format.");
            fclose($past_change_log_f);
        }
        $period_arr[1][$period]=$period_arr_arr[2]."-".str_pad($period_arr_arr[0],2,"0",STR_PAD_LEFT)."-".str_pad($period_arr_arr[1],2,"0",STR_PAD_LEFT);
        if(!array_key_exists($period_arr[1][$period],$stock_financials_arr[$the_url_query_arr[$current_num_to_curl]])){$stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]=array();}
        foreach($vars2get as $var2get){
            if(!array_key_exists($var2get,$stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]])){
                if($results[$var2get][$period]==0) $results[$var2get][$period]=0; // to avoid 0 division
                $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
            }else{
                if($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]][$var2get]!=$results[$var2get][$period]){
                    echo "ERROR changing the past!!!";
                    $past_change_log_f = fopen("past-change-log.txt", "a") or die("Unable to open past-change-log.txt!");
                    fwrite($past_change_log_f, "\n".date("Y-m-d")." In ".$the_url_query_arr[$current_num_to_curl]." period:".$period_arr[1][$period]." var:".$var2get."  old:".$stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]][$var2get]." != new:".$results[$var2get][$period]);
                    fclose($past_change_log_f);
                }
            }
        }
        // the typical one is debt/assets (excluding other liabilities since they tend to be low but GM for example has A LOT of them so better compute total liabilities)
        if(floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['Total Current Assets'])==0) $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['current_liabilities_to_current_assets']=0;
        else $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['current_liabilities_to_current_assets']=toFixed(floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['Total Current Liabilities'])/floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['Total Current Assets']));
        
        if(floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['Total Assets'])==0) $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['liabilities_to_assets']=0;
        else $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['liabilities_to_assets']=toFixed(floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['Total Liabilities'])/floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]]['Total Assets']));
    }
    
    // just account for last yoy diff in Revenue, net margin, liabilities_to_assets
    $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][0]]['revenue_diff']=toFixed((floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][0]]['Total Revenue'])-floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][1]]['Total Revenue']))/floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][1]]['Total Revenue']));
    $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][0]]['operating_margin_diff']=toFixed((floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][0]]['operating_margin'])-floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][1]]['operating_margin']))/floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][1]]['operating_margin']));
    $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][0]]['liabilities_to_assets_diff']=toFixed((floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][0]]['liabilities_to_assets'])-floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][1]]['liabilities_to_assets']))/floatval($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][1]]['liabilities_to_assets']));
    
    //var_dump($stock_financials_arr);


    
    // to avoid ban
    sleep(0.1);
}

if($debug) echo "<br />arr ".print_r($stock_financials_arr)."<br />";

// update last updated number
$stock_last_financial_updated=($stock_last_financial_updated+$num_stocks_to_curl) % count($the_url_query_arr); // modulo to avoid big nums...
$stock_last_financial_updated_f = fopen("stock_last_financial_updated.txt", "w") or die("Unable to open file!");
fwrite($stock_last_financial_updated_f, $stock_last_financial_updated);
fclose($stock_last_financial_updated_f);

$stocks_financials_arr_json_str=json_encode( $stock_financials_arr );

// update stocks.financials.json
echo date('Y-m-d H:i:s')." updating stocks.financials.json\n";
$stocks_financials_json_file = fopen("stocks.financials.json", "w") or die("Unable to open file stocks.financials.json!");
fwrite($stocks_financials_json_file, $stocks_financials_arr_json_str);
fclose($stocks_financials_json_file);


// backup history (monthly)
if(!file_exists( date("Y").'.stocks.financials.json' )){
    echo "creating backup: ".date("Y").".stocks.financials.json<br />";
    echo date('Y-m-d H:i:s')." creating backup: ".date("Y").".stocks.financials.json\n";
    $fileb = fopen(date("Y").".stocks.financials.json", "w") or die("Unable to open file Y.stocks.financials.json!");
    fwrite($fileb, $stocks_financials_arr_json_str);
    fclose($fileb);
}


echo date('Y-m-d H:i:s')." ending stock_curl_financials.php<br />";


?>