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

// helper functions
function toFixed($number, $decimals=2) {
  if(!is_numeric($number)){
        echo "lev book, not numeric: $number";
        $number=0; 
  } 
  return number_format($number, $decimals, ".", "");
}
$stocks_formatted_arr=array(); // to store stocks.formatted, typo "formatted"
if(file_exists ( 'stocks.formatted.json' )){
    echo "stocks.formatted.json exists -> reading...<br />";
    $stocks_formatted_arr = json_decode(file_get_contents('stocks.formatted.json'), true);
}else{
    echo "stocks.formatted.json does NOT exist -> using an empty array<br />";
}

echo date('Y-m-d H:i:s')." start stock_cron_value_hist_just_once.php<br />";

// fopen with w overwrites existing file
$stock_cron_value_hist_just_once_log = fopen("stock_cron_value_hist_just_once.log", "w") or die("Unable to open/create stock_cron_value_hist_just_once.log!");
fwrite($stock_cron_value_hist_just_once_log, date('Y-m-d H:i:s')." starting stock_cron_value_hist_just_once.php\n");

fwrite($stock_cron_value_hist_just_once_log, date('Y-m-d H:i:s')." starting stock_list.php\n");
require_once 'stock_list.php';

$num_stocks_to_curl=3;
$stock_last_value_updated=0;
if(file_exists ( 'stock_last_value_updated.txt' )){
    $stock_last_value_updated=intval(fgets(fopen('stock_last_value_updated.txt', 'r')));
}
echo " curr_stock_num_to_curl=$stock_last_value_updated num_stocks_to_curl=$num_stocks_to_curl<br />";


$the_url=""; //fi-199.1.SGRE.MCE (the number depends on the country)

// IMPORTANT: Price\/Sales does NOT appear in the first page but surprisingly it can be crawled!!

//$vals=",";
$the_url_query_arr = explode(",", $stock_list);
$num_stocks_to_curl=min($num_stocks_to_curl,count($the_url_query_arr)); // make sure we do not duplicate...


for ($i=0;$i<$num_stocks_to_curl;$i++){
    $current_num_to_curl=($stock_last_value_updated+$i) % count($the_url_query_arr);
    $url_and_query="https://finance.yahoo.com/quote/".get_yahoo_quote($the_url_query_arr[$current_num_to_curl])."/history?period1=1354316400&period2=1519426800&interval=1mo&filter=history&frequency=1mo"; //get_msn_quote($the_url_query_arr[$current_num_to_curl]);
    echo "<br />stock $url_and_query<br />";
    if(substr($the_url_query_arr[$current_num_to_curl],0,5)!="INDEX"){
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
            echo "bbb.<pre>".htmlspecialchars($xxxx[1])."</pre>";
            if(trim($xxxx[1])!=""){
                $results[]=array($var2get."-12-01",str_replace(",","",$xxxx[1]));
            }
        }
        var_dump($results);
        $query_arr=explode(":",$the_url_query_arr[$current_num_to_curl]);
        $name=$query_arr[1];
        $market=$query_arr[0];
        if(count($results)>0){
            $stocks_formatted_arr[$name.":".$market]['value_hist']=$results;
        }
    }
}

// update last updated number
$stock_last_value_updated=($stock_last_value_updated+$num_stocks_to_curl) % count($the_url_query_arr); // modulo to avoid big nums...
$stock_last_value_updated_f = fopen("stock_last_value_updated.txt", "w") or die("Unable to open file!");
fwrite($stock_last_value_updated_f, $stock_last_value_updated);
fclose($stock_last_value_updated_f);

$stocks_formatted_arr_json_str=json_encode( $stocks_formatted_arr );


// update stocks.formatted.json
echo date('Y-m-d H:i:s')." updating stocks.formatted.json\n";
fwrite($stock_cron_value_hist_just_once_log, date('Y-m-d H:i:s')." updating stocks.formatted.json\n");
$stocks_formatted_json_file = fopen("stocks.formatted.json", "w") or die("Unable to open file stocks.formatted.json!");
fwrite($stocks_formatted_json_file, $stocks_formatted_arr_json_str);
fclose($stocks_formatted_json_file);


fwrite($stock_cron_value_hist_just_once_log, date('Y-m-d H:i:s')." done with stock_cron_value_hist_just_once.php\n");
echo "<br />".date('Y-m-d H:i:s')." done with stock_cron_value_hist_just_once.php, see stock_cron_value_hist_just_once.log<br />";
fclose($stock_cron_value_hist_just_once_log);



?>