<?php

require_once 'stock_list.php';
require_once("email_config.php");
require_once 'stock_helper_functions.php';
require_once 'stock_curl_financial.php';
require_once 'stock_curl_asset.php';

echo date('Y-m-d H:i:s')." starting stock_curl_financials.php<br />";

$num_stocks_to_curl=2;
$stock_last_financialr_updated=0;
if(file_exists ( 'stock_last_financialr_updated.txt' )){
    $stock_last_financialr_updated=intval(fgets(fopen('stock_last_financialr_updated.txt', 'r')));
}
echo " curr_stock_num_to_curl=$stock_last_financialr_updated num_stocks_to_curl=$num_stocks_to_curl<br />";

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

$stock_financials_arr=array(); // to store stocks_financialsr, typo "financials"
if(file_exists ( 'stocks_financialsr.json' )){
    if($debug) echo "stocks_financialsr.json exists -> reading...<br />";
    $stock_financials_arr = json_decode(file_get_contents('stocks_financialsr.json'), true);
}else{
    echo "stocks_financialsr.json does NOT exist -> using an empty array<br />";
}

$stocks_formatted_arr=array(); // to store stocks.formatted, typo "formatted"
if(file_exists ( 'stocks.formatted.json' )){
    if($debug) echo "stocks.formatted.json exists -> reading...<br />";
    $stocks_formatted_arr = json_decode(file_get_contents('stocks.formatted.json'), true);
}else{
    echo "stocks.formatted.json does NOT exist -> using an empty array<br />";
}

$the_url_query_arr = explode(",", $stock_list);
$num_stocks_to_curl=min($num_stocks_to_curl,count($the_url_query_arr)); // make sure we do not duplicate...
for ($i=0;$i<$num_stocks_to_curl;$i++){
    $current_num_to_curl=($stock_last_financialr_updated+$i) % count($the_url_query_arr);
    if(substr($the_url_query_arr[$current_num_to_curl],0,5)=="INDEX"){echo "<br /><br />Index (".$the_url_query_arr[$current_num_to_curl]."), nothing to be done<br /><br />"; continue;} // skip indexes
    $query_arr=explode(":",$the_url_query_arr[$current_num_to_curl]);
    $name=$query_arr[1];
    $market=$query_arr[0];
    
    $updated_financial=get_financial($the_url_query_arr[$current_num_to_curl]);
    if($updated_financial==null || count($updated_financial) == 0){
        echo "ERROR: not updated<br />";
        continue;
    }

    // assignment to the array
    $updated=true;
    if(!array_key_exists($the_url_query_arr[$current_num_to_curl],$stock_financials_arr)){
        echo "$name first time financials, adding<br />";
        $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]]=$updated_financial;
    }else if($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]]!=$updated_financial){
        echo "$name new values, updating<br />";
        if($debug){
            echo "original:<br />";
            print_r($stock_financials_arr[$the_url_query_arr[$current_num_to_curl]]);
            echo "<br />new:";
            print_r($updated_financial);
        }
        $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]]=$updated_financial;
    }else{
        echo "$name equal, no update<br />";
        $updated=false;
    }
    if($updated && array_key_exists(($name.":".$market),$stocks_formatted_arr)){
        echo "has stock formatted (updating) <br />";
        $stocks_formatted_arr[$name.":".$market]['revenue_hist']=array();
        $stocks_formatted_arr[$name.":".$market]['operating_income_hist']=array();
        $stocks_formatted_arr[$name.":".$market]['net_income_hist']=array();
        

        foreach ($stock_financials_arr[$market.":".$name] as $key2 => $item2) {
            if($key2[0]=="2" && array_key_exists('Total Revenue',$item2)){
                $stocks_formatted_arr[$name.":".$market]['revenue_hist'][]=[$key2,toFixed(floatval(($item2['Total Revenue'])/1000),2,'revenue')]; // PS can be calculated
                $stocks_formatted_arr[$name.":".$market]['operating_income_hist'][]=[$key2,toFixed(floatval(($item2['Operating Income'])/1000),2,'operating income')]; // OM can be calculated
                $stocks_formatted_arr[$name.":".$market]['net_income_hist'][]=[$key2,toFixed(floatval(($item2['Net Income'])/1000),2,'net income')]; // EPS can be calculated
            }else if($key2[0]=="2"){
                echo "FATAL ERROR, financials but not var for ".$name." ".$key2;
                var_dump($item2);
                send_mail('ERROR financialsr '.$name,"<br />FATAL ERROR, financials but not var for ".$name." ".$key2." ".implode(",",array_keys($item2))."<br /><br />","hectorlm1983@gmail.com");
                exit(1);
            }
        }
    }
    
    // to avoid ban
    sleep(0.1);
    
}

if($debug) echo "<br />arr ".print_r($stock_financials_arr)."<br />";

// update last updated number
$stock_last_financialr_updated=($stock_last_financialr_updated+$num_stocks_to_curl) % count($the_url_query_arr); // modulo to avoid big nums...
$stock_last_financialr_updated_f = fopen("stock_last_financialr_updated.txt", "w") or die("Unable to open file!");
fwrite($stock_last_financialr_updated_f, $stock_last_financialr_updated);
fclose($stock_last_financialr_updated_f);


// update stocks_financialsr.json
echo date('Y-m-d H:i:s')." updating stocks_financialsr.json\n";
$stocks_financialsr_arr_json_str=json_encode( $stock_financials_arr );
$stocks_financialsr_json_file = fopen("stocks_financialsr.json", "w") or die("Unable to open file stocks_financialsr.json!");
fwrite($stocks_financialsr_json_file, $stocks_financialsr_arr_json_str);
fclose($stocks_financialsr_json_file);




// update stocks.formatted.json
echo date('Y-m-d H:i:s')." updating stocks.formatted.json\n";
$stocks_formatted_arr_json_str=json_encode( $stocks_formatted_arr );
$stocks_formatted_json_file = fopen("stocks.formatted.json", "w") or die("Unable to open file stocks.formatted.json!");
fwrite($stocks_formatted_json_file, $stocks_formatted_arr_json_str);
fclose($stocks_formatted_json_file);


// backup history (monthly)
if(!file_exists( date("Y").'.stocks_financialsr.json' )){
    echo "creating backup: ".date("Y").".stocks_financialsr.json<br />";
    echo date('Y-m-d H:i:s')." creating backup: ".date("Y").".stocks_financialsr.json\n";
    $fileb = fopen(date("Y").".stocks_financialsr.json", "w") or die("Unable to open file Y.stocks_financialsr.json!");
    fwrite($fileb, $stocks_financialsr_arr_json_str);
    fclose($fileb);
}


echo date('Y-m-d H:i:s')." ending stock_curl_financials.php<br />";


?>