<?php

// only keep the json properties that we currently use

date_default_timezone_set('Europe/Madrid');
$timestamp_date=date("Y-m-d");
$timestamp_simplif=date("d H:i");
$timestamp_quarter=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 3) % 4) + 1 );
$timestamp_half=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 6) % 2) + 1 );

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

$stocks_formatted_arr=array(); 
if(file_exists ( 'stocks.formatted.json' )){
    echo "stocks.formatted.json exists -> reading...<br />";
    $stocks_formatted_arr = json_decode(file_get_contents('stocks.formatted.json'), true);
}else{
    echo "stocks.formatted.json does NOT exist -> using an empty array<br />";
}
$stocks_financials_arr=array(); 
if(file_exists ( 'stocks.financials.json' )){
    echo "stocks.financials.json exists -> reading...<br />";
    $stocks_financials_arr = json_decode(file_get_contents('stocks.financials.json'), true);
}else{
    echo "stocks.financials.json does NOT exist -> using an empty array<br />";
}



echo date('Y-m-d H:i:s')." start stock_cron.php<br />";

require_once 'stock_list.php';
require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)

$stocks_clean_arr=array();
foreach ($stocks_formatted_arr as $key => $item) {
	$symbol_object=array();
    echo "cleaning ".$item['name'].":".$item['market']."<br />";
    $props=['name','market','date','title','mktcap','session_change_percentage',
            // shares are guessed (only stored in the consumed json, not in the operations one)
            'value','value_hist',
            'eps_hist', // temporary to remove
            'range_52week_high','range_52week_low',
            'yield', 'yield_hist',
            'price_to_book',
            'avg_revenue_growth_5y', 'revenue_growth_qq_last_year',
            'leverage','leverage_industry'];
    // refresh basic info
    foreach($props as $prop){
        if($prop=='yield_hist' && substr($item['market'],0,5)=="INDEX") continue;
        $symbol_object[$prop]=$item[$prop];
    }
    if(($item['market'].":".$item['name'])=='NASDAQ:GOOG'){
        $symbol_object['usdeur']=$item['usdeur'];
        $symbol_object['usdeur_change']=$item['usdeur_change'];
    }
    if(array_key_exists($item['market'].":".$item['name'],$stocks_financials_arr)){
        echo "has financials <br />";
        $symbol_object['revenue_hist']=array();
        $symbol_object['operating_income_hist']=array();
        $symbol_object['net_income_hist']=array();
        foreach ($stocks_financials_arr[$item['market'].":".$item['name']] as $key2 => $item2) {
            if($key2[0]=="2" && array_key_exists('Total Revenue',$item2)){
                $symbol_object['revenue_hist'][]=[$key2,toFixed(floatval(($item2['Total Revenue'])/1000),2,'revenue')]; // PS can be calculated
                $symbol_object['operating_income_hist'][]=[$key2,toFixed(floatval(($item2['Operating Income'])/1000),2,'operating income')]; // OM can be calculated
                $symbol_object['net_income_hist'][]=[$key2,toFixed(floatval(($item2['Net Income'])/1000),2,'net income')]; // EPS can be calculated
            }
        }
    }
    $stocks_clean_arr[$item['name'].':'.$item['market']]=$symbol_object;
    //if($item['name']=='CMPR') break;
}
// --------------------------------------------- 

$stocks_formatted_arr_json_str=json_encode( $stocks_clean_arr );
$stocks_formatted_json_file = fopen("stocks_clean.json", "w") or die("Unable to open file stocks_clean.json!");
fwrite($stocks_formatted_json_file, $stocks_formatted_arr_json_str);
fclose($stocks_formatted_json_file);

echo "<br />".date('Y-m-d H:i:s')." done with stocks_clean.json <br />";

?>