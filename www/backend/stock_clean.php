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


foreach ($stocks_formatted_arr as $key => $item) {
	$symbol_object=array();
    echo "cleaning ".$item['name'].":".$item['market']."<br />";    
    $props=['name','market','date','session_change_percentage','title','mktcap',
            'value','value_hist',
            'range_52week_high','range_52week_low',
            'h_souce', 
            'yield', 'yield_hist',
            'eps','eps_hist',
            'computable_val_growth',
            'epsp',
            'price_to_book',
            'avg_revenue_growth_5y', 'revenue_growth_qq_last_year',
            'leverage','leverage_hist',
            'leverage_industry'];
    // refresh basic info
    foreach($props as $prop){
        $symbol_object[$prop]=$item[$prop];
    }
    if(array_key_exists($item['market'].":".$item['name'],$stocks_financials_arr)){
        echo "has financials";
        $symbol_object['revenue_hist']=array();
        $symbol_object['operating_income_hist']=array();
        foreach ($stocks_financials_arr[$item['market'].":".$item['name']] as $key => $item) {
            if($key[0]=="2"){
                $symbol_object['revenue_hist'][]=[$key,$item['Total Revenue']];
                $symbol_object['operating_income_hist'][]=[$key,$item['Operating Income']];
            }
        }
    }
    $stocks_formatted_arr[$item['name'].':'.$item['market']]=$symbol_object;
}
// --------------------------------------------- 

$stocks_formatted_arr_json_str=json_encode( $stocks_formatted_arr );
$stocks_formatted_json_file = fopen("stocks_clean.json", "w") or die("Unable to open file stocks_clean.json!");
fwrite($stocks_formatted_json_file, $stocks_formatted_arr_json_str);
fclose($stocks_formatted_json_file);

echo "<br />".date('Y-m-d H:i:s')." done with stocks_clean.json <br />";

?>