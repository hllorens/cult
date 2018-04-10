<?php

require_once 'stock_list.php';
require_once("email_config.php");
require_once 'stock_helper_functions.php';
require_once 'stock_curl_financial.php';
require_once 'stock_curl_asset.php';

echo date('Y-m-d H:i:s')." starting stock_curl_assets.php<br />";

$num_stocks_to_curl=2;
$stock_last_financial_updated=0;
if(file_exists ( 'stock_last_financiala_updated.txt' )){
    $stock_last_financial_updated=intval(fgets(fopen('stock_last_financiala_updated.txt', 'r')));
}
echo " curr_stock_num_to_curl=$stock_last_financial_updated num_stocks_to_curl=$num_stocks_to_curl<br />";

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

$force=false;
if( isset($_REQUEST['force']) && ($_REQUEST['force']=="true" || $_REQUEST['force']=="1")){
    $force=true;
}

$stock_financials_arr=array(); // to store stocks_financialsa, typo "financials"
if(file_exists ( 'stocks_financialsa.json' )){
    if($debug) echo "stocks_financialsa.json exists -> reading...<br />";
    $stock_financials_arr = json_decode(file_get_contents('stocks_financialsa.json'), true);
}else{
    echo "stocks_financialsa.json does NOT exist -> using an empty array<br />";
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
    $current_num_to_curl=($stock_last_financial_updated+$i) % count($the_url_query_arr);
    if(substr($the_url_query_arr[$current_num_to_curl],0,5)=="INDEX"){echo "<br /><br />Index (".$the_url_query_arr[$current_num_to_curl]."), nothing to be done<br /><br />"; continue;} // skip indexes
    $query_arr=explode(":",$the_url_query_arr[$current_num_to_curl]);
    $name=$query_arr[1];
    $market=$query_arr[0];
    
    $updated_financial=get_asset($the_url_query_arr[$current_num_to_curl]);
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
            echo "<br />new:<br />";
            print_r($updated_financial);
            echo "<br />";
        }
        $stock_financials_arr[$the_url_query_arr[$current_num_to_curl]]=$updated_financial;
    }else{
        echo "$name equal, no update<br />";
        $updated=false;
		if($force || (array_key_exists(($name.":".$market),$stocks_formatted_arr) && !array_key_exists('equity_hist',$stocks_formatted_arr[$name.":".$market]))){
			$updated=true;
		}
    }
    if($updated && array_key_exists(($name.":".$market),$stocks_formatted_arr)){
        echo "has stock formatted (updating) <br />";
        $stocks_formatted_arr[$name.":".$market]['equity_hist']=array();
        foreach ($stock_financials_arr[$market.":".$name] as $key2 => $item2) {
            if($key2[0]=="2" && array_key_exists('Total Assets',$item2) && array_key_exists('Total Liabilities',$item2)){
                $stocks_formatted_arr[$name.":".$market]['equity_hist'][]=[$key2,toFixed(  ((floatval($item2['Total Assets'])/1000000)  -  (floatval($item2['Total Liabilities'])/1000000))  ,2,'equity')]; // PB can be calculated
            }else if($key2[0]=="2"){
                echo "FATAL ERROR, financials but not var for ".$name." ".$key2;
                var_dump($item2);
                send_mail('ERROR financialsa '.$name,"<br />FATAL ERROR, financials but not var for ".$name." ".$key2." ".implode(",",array_keys($item2))."<br /><br />","hectorlm1983@gmail.com");
                exit(1);
            }
        }
    }
    
    // to avoid ban
    sleep(0.1);
    
}

//if($debug) echo "<br />arr ".print_r($stock_financials_arr)."<br />";

// update last updated number
$stock_last_financial_updated=($stock_last_financial_updated+$num_stocks_to_curl) % count($the_url_query_arr); // modulo to avoid big nums...
$stock_last_financial_updated_f = fopen("stock_last_financiala_updated.txt", "w") or die("Unable to open file!");
fwrite($stock_last_financial_updated_f, $stock_last_financial_updated);
fclose($stock_last_financial_updated_f);


// update stocks_financialsa.json
echo date('Y-m-d H:i:s')." updating stocks_financialsa.json\n";
$stocks_financials_arr_json_str=json_encode( $stock_financials_arr );
$stocks_financials_json_file = fopen("stocks_financialsa.json", "w") or die("Unable to open file stocks_financialsa.json!");
fwrite($stocks_financials_json_file, $stocks_financials_arr_json_str);
fclose($stocks_financials_json_file);




// update stocks.formatted.json
echo date('Y-m-d H:i:s')." updating stocks.formatted.json\n";
$stocks_formatted_arr_json_str=json_encode( $stocks_formatted_arr );
$stocks_formatted_json_file = fopen("stocks.formatted.json", "w") or die("Unable to open file stocks.formatted.json!");
fwrite($stocks_formatted_json_file, $stocks_formatted_arr_json_str);
fclose($stocks_formatted_json_file);


// backup history (monthly)
if(!file_exists( date("Y").'.stocks_financialsa.json' )){
    echo "creating backup: ".date("Y").".stocks_financialsa.json<br />";
    echo date('Y-m-d H:i:s')." creating backup: ".date("Y").".stocks_financialsa.json\n";
    $fileb = fopen(date("Y").".stocks_financialsa.json", "w") or die("Unable to open file Y.stocks_financialsa.json!");
    fwrite($fileb, $stocks_financials_arr_json_str);
    fclose($fileb);
}


echo date('Y-m-d H:i:s')." ending stock_curl_assets.php<br />";


?>