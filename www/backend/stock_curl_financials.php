<?php

require_once 'stock_list.php';
require_once("email_config.php");
require_once 'stock_helper_functions.php';

echo date('Y-m-d H:i:s')." starting stock_curl_financials.php<br />";

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

TODO also load stocks formatted to load it with new stuff when needed.




$num_stocks_to_curl=min($num_stocks_to_curl,count($the_url_query_arr)); // make sure we do not duplicate...
for ($i=0;$i<$num_stocks_to_curl;$i++){
    $current_num_to_curl=($stock_last_financial_updated+$i) % count($the_url_query_arr);
    if(substr($the_url_query_arr[$current_num_to_curl],0,5)=="INDEX") continue; // skip indexes
    $query_arr=explode(":",$the_url_query_arr[$current_num_to_curl]);
    $name=$query_arr[1];
    $market=$query_arr[0];
    
    
    HERE WE GET THE curl_financial with the current stock...    
    TODO, first thing compare the existing and the new to see if there is any update, otherwise do not update... financials or stock formatted    
    
    // assignment to the array
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
            if($debug) echo "updating vars: $var2get<br />";
            if(!array_key_exists($var2get,$stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]])){
                $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
            }else{
                if($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]][$var2get]!=$results[$var2get][$period]){
                    echo "ERROR changing the past!!! (keeping new value)";
                    $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
                    $past_change_log_f = fopen("past-change-log.txt", "a") or die("Unable to open past-change-log.txt!");
                    fwrite($past_change_log_f, "\n".date("Y-m-d")." In ".$the_url_query_arr[$current_num_to_curl]." period:".$period_arr[1][$period]." var:".$var2get."  old:".$stock_financials_arr[$the_url_query_arr[$current_num_to_curl]][$period_arr[1][$period]][$var2get]." != new:".$results[$var2get][$period]);
                    fclose($past_change_log_f);
                }
            }
        }
        
        
    }
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