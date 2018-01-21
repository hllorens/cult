<?php

// alternative version to be run with less frequency for less important stocks or coins
// the aim of this php is to get stocks.formatted.json,
//     if a file like that exists it will be updated incrementally 
//     otherwise it will be created from the scratch

//if(!isset($_GET['autosecret']) || $_GET['autosecret']!='1secret'){
//	exit("Permission denied");
//}


date_default_timezone_set('Europe/Madrid');
$timestamp_date=date("Y-m-d");
$timestamp_simplif=date("d H:i");
$timestamp_quarter=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 3) % 4) + 1 );
$timestamp_half=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 6) % 2) + 1 );

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

// helper functions
function toFixed($number, $decimals=2) {
  return number_format($number, $decimals, ".", "");
}

$stocks_formatted_arr=array(); // to store stocks.formatted, typo "formatted"
if(file_exists ( 'stocks.formatted.json' )){
    echo "stocks.formatted.json exists -> reading...<br />";
    $stocks_formatted_arr = json_decode(file_get_contents('stocks.formatted.json'), true);
}else{
    echo "stocks.formatted.json does NOT exist -> using an empty array<br />";
}

echo date('Y-m-d H:i:s')." start stock_cron_leverage_book.php<br />";

// fopen with w overwrites existing file
$stock_cron_leverage_book_log = fopen("stock_cron_leverage_book.log", "w") or die("Unable to open/create stock_cron_leverage_book.log!");
fwrite($stock_cron_leverage_book_log, date('Y-m-d H:i:s')." starting stock_cron_leverage_book.php\n");

fwrite($stock_cron_leverage_book_log, date('Y-m-d H:i:s')." starting stock_list.php\n");
require_once 'stock_list.php';

$num_stocks_to_curl=3;
$stock_last_leverage_book_updated=0;
if(file_exists ( 'stock_last_leverage_book_updated.txt' )){
    $stock_last_leverage_book_updated=intval(fgets(fopen('stock_last_leverage_book_updated.txt', 'r')));
}
echo " curr_stock_num_to_curl=$stock_last_leverage_book_updated num_stocks_to_curl=$num_stocks_to_curl<br />";


$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

$the_url="https://www.msn.com/en-us/money/stockdetails/analysis/"; //fi-199.1.SGRE.MCE (the number depends on the country)

// IMPORTANT: Price\/Sales does NOT appear in the first page but surprisingly it can be crawled!!

//$vals=",";
$the_url_query_arr = explode(",", $stock_list);
$num_stocks_to_curl=min($num_stocks_to_curl,count($the_url_query_arr)); // make sure we do not duplicate...
for ($i=0;$i<$num_stocks_to_curl;$i++){
    $current_num_to_curl=($stock_last_leverage_book_updated+$i) % count($the_url_query_arr);
    
    if(substr($the_url_query_arr[$current_num_to_curl],0,5)=="INDEX"){
        $query_arr=explode(":",$the_url_query_arr[$current_num_to_curl]);
        echo "<br />stock ".$the_url_query_arr[$current_num_to_curl].": INDEX set to 0 just for sorting...<br />";
        $name=$query_arr[1];
        $market=$query_arr[0];
        $stocks_formatted_arr[$name.":".$market]['revenue']=0;
        $stocks_formatted_arr[$name.":".$market]['price_to_book']=0;
        $stocks_formatted_arr[$name.":".$market]['price_to_sales']=99;
        $stocks_formatted_arr[$name.":".$market]['leverage']=99; // mrq in this case equivalent to ttm (current moment), in balance sheet
        $stocks_formatted_arr[$name.":".$market]['leverage_industry']=2.5;
        $stocks_formatted_arr[$name.":".$market]['avg_revenue_growth_5y']=0;
        $stocks_formatted_arr[$name.":".$market]['revenue_growth_qq_last_year']=0;
    }else{
        $url_and_query=$the_url.get_msn_quote($the_url_query_arr[$current_num_to_curl]); //get_msn_quote($the_url_query_arr[$current_num_to_curl]);
        echo "<br />stock $url_and_query<br />";
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
        $response = preg_replace('/title=\'(Revenue|Price\/Book Value|Leverage Ratio|Price\/Sales)\'[^>]*>\s*/', "\n", $response);
        $response = preg_replace('/title=\'Sales \(Revenue\)\'[^5]*5-Year Annual Average[^>]*>\s*/', "\navg_revenue_growth_5y", $response);
        $response = preg_replace('/title=\'Sales \(Revenue\)\'[^Q]*Q\/Q \(Last Year\)[^>]*>\s*/', "\nrevenue_growth_qq_last_year", $response);
        if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
        echo "----------end----------";
        echo "<br />";
        $vars2get=['Revenue','Price\/Book Value','Leverage Ratio','Price\/Sales','avg_revenue_growth_5y','revenue_growth_qq_last_year'];
        $results=array();
        foreach($vars2get as $var2get){
            preg_match("/^".$var2get."(.*)$/m", $response, $xxxx);
            preg_match_all("/title='([^']*)'/", $xxxx[1], $xxxx_arr);
            $results[$var2get]=str_replace(",","",$xxxx_arr[1]);
        }
        
        if($debug)  var_dump($results);
        $query_arr=explode(":",$the_url_query_arr[$current_num_to_curl]);
        $name=$query_arr[1];
        $market=$query_arr[0];
        $stocks_formatted_arr[$name.":".$market]['revenue']=format_billions($results['Revenue'][0]); // current ttm which is equal to mrq in balance sheet
        $stocks_formatted_arr[$name.":".$market]['price_to_book']=$results['Price\/Book Value'][0];
        if($results['Price\/Sales'][0]=="-" || $results['Price\/Sales'][0]=="") $results['Price\/Sales'][0]=99;
        $stocks_formatted_arr[$name.":".$market]['price_to_sales']=$results['Price\/Sales'][0];
        if($results['avg_revenue_growth_5y'][0]=="-" || $results['avg_revenue_growth_5y'][0]=="") $results['avg_revenue_growth_5y'][0]=0;
        $stocks_formatted_arr[$name.":".$market]['avg_revenue_growth_5y']=$results['avg_revenue_growth_5y'][0];
        if($results['revenue_growth_qq_last_year'][0]=="-" || $results['revenue_growth_qq_last_year'][0]=="") $results['revenue_growth_qq_last_year'][0]=0;
        $stocks_formatted_arr[$name.":".$market]['revenue_growth_qq_last_year']=$results['revenue_growth_qq_last_year'][0];
        if($results['Leverage Ratio'][0]=="-" || $results['Leverage Ratio'][0]=="") $results['Leverage Ratio'][0]=99;
        $stocks_formatted_arr[$name.":".$market]['leverage']=$results['Leverage Ratio'][0];
        $stocks_formatted_arr[$name.":".$market]['leverage_industry']=0;
        if(count($results['Leverage Ratio'])>1){
            $stocks_formatted_arr[$name.":".$market]['leverage_industry']=$results['Leverage Ratio'][1];
        }
    }
    

    // add hist but do it with a function...
    require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)
    hist('revenue',6,$stocks_formatted_arr[$name.":".$market]); // in msn this is last year, the ttm maybe use yahoo or do it manually for companies you care about
    hist('leverage',3,$stocks_formatted_arr[$name.":".$market]);
    hist('price_to_sales',3,$stocks_formatted_arr[$name.":".$market]);
    hist('avg_revenue_growth_5y',12,$stocks_formatted_arr[$name.":".$market]);
    hist('revenue_growth_qq_last_year',3,$stocks_formatted_arr[$name.":".$market]);
}
// -----------update stocks formatted ----------------------------------




// update last updated number
$stock_last_leverage_book_updated=($stock_last_leverage_book_updated+$num_stocks_to_curl) % count($the_url_query_arr); // modulo to avoid big nums...
$stock_last_leverage_book_updated_f = fopen("stock_last_leverage_book_updated.txt", "w") or die("Unable to open file!");
fwrite($stock_last_leverage_book_updated_f, $stock_last_leverage_book_updated);
fclose($stock_last_leverage_book_updated_f);

$stocks_formatted_arr_json_str=json_encode( $stocks_formatted_arr );


// update stocks.formatted.json
echo date('Y-m-d H:i:s')." updating stocks.formatted.json\n";
fwrite($stock_cron_leverage_book_log, date('Y-m-d H:i:s')." updating stocks.formatted.json\n");
$stocks_formatted_json_file = fopen("stocks.formatted.json", "w") or die("Unable to open file stocks.formatted.json!");
fwrite($stocks_formatted_json_file, $stocks_formatted_arr_json_str);
fclose($stocks_formatted_json_file);


fwrite($stock_cron_leverage_book_log, date('Y-m-d H:i:s')." done with stock_cron_leverage_book.php\n");
echo "<br />".date('Y-m-d H:i:s')." done with stock_cron_leverage_book.php, see stock_cron_leverage_book.log<br />";
fclose($stock_cron_leverage_book_log);

?>