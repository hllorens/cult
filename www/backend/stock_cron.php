<?php

// the aim of this php is to get stocks.formated.json,
//     if a file like that exists it will be updated incrementally 
//     otherwise it will be created from the scratch

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


$stocks_formated_arr=array(); // to store stocks.formated, typo "formatted"
if(file_exists ( 'stocks.formated.json' )){
    echo "stocks.formated.json exists -> reading...<br />";
    $stocks_formated_arr = json_decode(file_get_contents('stocks.formated.json'), true);
}else{
    echo "stocks.formated.json does NOT exist -> using an empty array<br />";
}

echo date('Y-m-d H:i:s')." start stock_cron.php<br />";

// fopen with w overwrites existing file
$stock_cron_log = fopen("stock_cron.log", "w") or die("Unable to open/create stock_cron.log!");
fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_cron.php\n");

fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_list.php\n");
require_once 'stock_list.php';

fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_curl_all_basic.php\n");

require_once 'stock_curl_all_basic.php';
// update stocks formatfed accordingly (formatting properly)



fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_curl_usdeur.php\n");
require_once 'stock_curl_usdeur.php';



fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_curl_details.php\n");
require_once 'stock_curl_details.php';


// -----------update stocks formatted ----------------------------------
foreach ($stock_all_basic_arr as $item) {
	$symbol_object=array();
    // t=ticker, e=exchange
    if($debug) echo "encoding ".$item['t'].":".$item['e']."<br />";
    
    // load info if exists
    if(array_key_exists($item['t'].":".$item['e'],$stocks_formated_arr)){
        if($debug) echo "loading existing info for ".$item['t'].":".$item['e']."<br />";
        $symbol_object=$stocks_formated_arr[$item['t'].":".$item['e']];
    }

    // refresh basic info
	$symbol_object['name']=$item['t'];
	$symbol_object['market']=$item['e'];
	$symbol_object['value']=str_replace(",","",$item['l']);
	$symbol_object['session_change']=$item['c'];
	$symbol_object['session_change_percentage']=$item['cp'];

    // only add in GOOG, date and usdeur
    if($symbol_object['name']=='GOOG'){
        $symbol_object['date']=$timestamp_simplif;
        $symbol_object['usdeur']=$usdeur;
    }

    // update new gathered details
    if(array_key_exists($item['e'].":".$item['t'],$stock_details_arr)){
        echo "<br /> >Updating details for ".$item['e'].":".$item['t']."<br/>";
        $symbol_object['title']=substr($stock_details_arr[$item['e'].':'.$item['t']]['title'],0,30);
        if(!$symbol_object['title']){$symbol_object['title']="ERROR: No title found";}
        if($debug) echo $symbol_object['title']."<br />";
        $symbol_object['yield']=$stock_details_arr[$item['e'].':'.$item['t']]['yield'];
        $symbol_object['dividend']=$stock_details_arr[$item['e'].':'.$item['t']]['dividend'];
        $symbol_object['range_52week']=trim($stock_details_arr[$item['e'].':'.$item['t']]['range_52week']);
        $symbol_object['range_52week_high']="0";
        $symbol_object['range_52week_low']="0";
        $symbol_object['range_52week_heat']="0";
        $symbol_object['range_52week_volatility']="0";
        if(strpos($symbol_object['range_52week'], '- ') !== false){
            $parts = explode('- ', $symbol_object['range_52week']);
            $symbol_object['range_52week_low']=$parts[0];
            $symbol_object['range_52week_high']=$parts[1];
            $symbol_object['range_52week_heat']="".toFixed((floatval($symbol_object['value'])-floatval($symbol_object['range_52week_low']))/(floatval($symbol_object['range_52week_high'])-floatval($symbol_object['range_52week_low'])));
            // you could use "low" or "high" but using "low" is what if val 3 to 6 then volat = 100% == 2x
            $symbol_object['range_52week_volatility']="".toFixed((floatval($symbol_object['range_52week_high'])-floatval($symbol_object['range_52week_low']))/(floatval($symbol_object['range_52week_low'])));
            // to show the 2x format or 1.3x, we can do it in js to avoid making json bigger
            //$symbol_object['range_52week_volatility_times']="".toFixed(((floatval($symbol_object['range_52week_high'])-floatval($symbol_object['range_52week_low']))/(floatval($symbol_object['range_52week_low'])))+1,1);
        }
        $symbol_object['divs_per_year']="0";
        $symbol_object['dividend_total_year']="0";
        $symbol_object['yield_per_ratio']="0";
        $symbol_object['beta']=$stock_details_arr[$item['e'].':'.$item['t']]['beta'];
        $symbol_object['eps']=$stock_details_arr[$item['e'].':'.$item['t']]['eps'];
        $symbol_object['per']=$stock_details_arr[$item['e'].':'.$item['t']]['per'];
        $symbol_object['roe']=$stock_details_arr[$item['e'].':'.$item['t']]['roe'];
        if(trim($symbol_object['per'])=='-' || trim($symbol_object['per'])==''){$symbol_object['per']=999;}
        if(trim($symbol_object['yield'])=='-' || trim($symbol_object['yield'])==''){$symbol_object['yield']=0;}
        if(floatval($symbol_object['dividend'])!=0){
            $symbol_object['divs_per_year']="".round(((floatval($symbol_object['yield'])/100)*floatval($symbol_object['value']))/floatval($symbol_object['dividend']));
            $symbol_object['dividend_total_year']="".toFixed(floatval($symbol_object['dividend'])*floatval($symbol_object['divs_per_year']));
            if(floatval($symbol_object['per'])>0){
                $symbol_object['yield_per_ratio']="".toFixed((floatval($symbol_object['yield'])/floatval($symbol_object['per'])));
            }
        }

        // historical data: eps, yield, per
        if(!array_key_exists('eps_hist',$symbol_object)){$symbol_object['eps_hist']=array();}
        if(!array_key_exists('yield_hist',$symbol_object)){$symbol_object['yield_hist']=array();}
        if(!array_key_exists('per_hist',$symbol_object)){$symbol_object['per_hist']=array();}
        if(!array_key_exists('eps_hist_last_diff',$symbol_object)){$symbol_object['per_hist']=$symbol_object['eps_hist_last_diff']=0;}
        if(!array_key_exists('yield_hist_last_diff',$symbol_object)){$symbol_object['per_hist']=$symbol_object['yield_hist_last_diff']=0;}

        // we need the original $stock_details_arr[$item['e'].':'.$item['t']] not $symbol_object because in the later we add 999 or 0 even if it is -
        if(array_key_exists('eps',$stock_details_arr[$item['e'].':'.$item['t']]) && $stock_details_arr[$item['e'].':'.$item['t']]['eps']!="" && $stock_details_arr[$item['e'].':'.$item['t']]['eps']!="-"){
            //echo "eps val".$symbol_object['name']."<br />";
            if(count($symbol_object['eps-hist'])==0){
                //echo "initial eps".$symbol_object['name']."<br />";
                $symbol_object['eps-hist'][]=[$timestamp_date,$symbol_object['eps']];
            }else{
                //echo "non initial eps".$symbol_object['name']."<br />";  //print_r($symbol_object);
                $last_eps=end($symbol_object['eps-hist'])[1];
                $last_eps_date=end($symbol_object['eps-hist'])[0];
                $last_eps_quarter=substr($last_eps_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $last_eps_date)->format('n') / 3) % 4) + 1 );
                // NOTE: Preferred to keep only changes than quarters because if it stays exactly the same we don't know if it is new
                if($symbol_object['eps']!=$last_eps){ // && abs(floatval($symbol_object['eps'])-floatval($last_eps))>(abs(floatval($last_eps))*0.005) // does not matter the diff as long as it is new (different)
                    if($timestamp_quarter!=$last_eps_quarter){
                        $symbol_object['eps-hist'][]=[$timestamp_date,$symbol_object['eps']];
                    }else{
                        //echo $symbol_object['name'].':'.$symbol_object['market'].": actualizado eps hist mismo quarter $last_eps_quarter";
                        $symbol_object['eps-hist'][count($symbol_object['eps-hist']) - 1]=[$timestamp_date,$symbol_object['eps']];
                    }
                }
            }
        }
        // trend eps
        if(count($symbol_object['eps_hist'])>1){
            //echo $symbol_object['name']."<br />\n"."<br />\n";
            $eps_hist_last_diff=((floatval(end($symbol_object['eps_hist'])[1])-floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-2][1]))/abs(floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-2][1])));
            if ($eps_hist_last_diff<-0.04){ // more than 5% annual which is about 20% quarterly
                $symbol_object['eps_hist_last_down']=toFixed($eps_hist_last_diff*100,0); // FOR BACKWARDS COMPATIBILITY
            }
            if ($eps_hist_last_diff<-0.04 || $eps_hist_last_diff>0.04){ // more than 5% annual which is about 20% quarterly  
                $symbol_object['eps_hist_last_diff']=toFixed($eps_hist_last_diff*100,0);
            }else{
                $symbol_object['eps_hist_last_diff']=0;
            }
            if(count($symbol_object['eps_hist'])>2){
                // 4 possibilities down-down down-up up-down up-up
                $eps_hist_penultimate_diff=((floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-2][1])-floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-3][1]))/abs(floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-3][1])));
                $symbol_object['eps_hist_trend']="-";
                if      ($eps_hist_penultimate_diff>0 && $eps_hist_last_diff >0){
                    $symbol_object['eps_hist_trend']="/";
                }else if($eps_hist_penultimate_diff<0 && $eps_hist_last_diff >0){
                    $symbol_object['eps_hist_trend']="v";
                }else if($eps_hist_penultimate_diff>0 && $eps_hist_last_diff <0){
                    $symbol_object['eps_hist_trend']="^";
                }else if($eps_hist_penultimate_diff<0 && $eps_hist_last_diff <0){
                    $symbol_object['eps_hist_trend']="\\";
                }
            }
        }
        
        
        
        if(array_key_exists('yield',$stock_details_arr[$item['e'].':'.$item['t']]) && $stock_details_arr[$item['e'].':'.$item['t']]['yield']!="" && $stock_details_arr[$item['e'].':'.$item['t']]['yield']!="-"){
            if(count($symbol_object['yield-hist'])==0){
                $symbol_object['yield-hist'][]=[$timestamp_date,$symbol_object['yield']];
            }else{
                $last_yield=end($symbol_object['yield-hist'])[1];
                $last_yield_date=end($symbol_object['yield-hist'])[0];
                $last_yield_half=substr($last_yield_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $last_yield_date)->format('n') / 6) % 2) + 1 );
                //echo "$last_yield_date half $last_yield_half current half $timestamp_half<br />";
                if($timestamp_half!=$last_yield_half){
                    $symbol_object['yield-hist'][]=[$timestamp_date,$symbol_object['yield']];
                }
            }
        }
        if(count($symbol_object['yield_hist'])>1){
            $symbol_object['yield_hist_last_diff']=toFixed($yield_hist_last_diff*100,0);
            if(count($symbol_object['yield_hist'])>2){
                // 4 possibilities down-down down-up up-down up-up
                $yield_hist_penultimate_diff=((floatval($symbol_object['yield_hist'][count($symbol_object['yield_hist'])-2][1])-floatval($symbol_object['yield_hist'][count($symbol_object['yield_hist'])-3][1]))/abs(floatval($symbol_object['yield_hist'][count($symbol_object['yield_hist'])-3][1])));
                $symbol_object['yield_hist_trend']="-";
                if      ($yield_hist_penultimate_diff>0 && $yield_hist_last_diff >0){
                    $symbol_object['yield_hist_trend']="/";
                }else if($yield_hist_penultimate_diff<0 && $yield_hist_last_diff >0){
                    $symbol_object['yield_hist_trend']="v";
                }else if($yield_hist_penultimate_diff>0 && $yield_hist_last_diff <0){
                    $symbol_object['yield_hist_trend']="^";
                }else if($yield_hist_penultimate_diff<0 && $yield_hist_last_diff <0){
                    $symbol_object['yield_hist_trend']="\\";
                }
            }
        }
        
        if(array_key_exists('per',$item) && $item['per']!="" && $item['per']!="-"){
            if(count($symbol_object['per-hist'])==0){
                $symbol_object['per-hist'][]=[$timestamp_date,$symbol_object['per']];
            }else{
                $last_per=end($symbol_object['per-hist'])[1];
                $last_per_date=end($symbol_object['per-hist'])[0];
                $last_per_half=substr($last_per_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $last_per_date)->format('n') / 6) % 2) + 1 );
                //echo "$last_per_date half $last_per_half current half $timestamp_half<br />";
                if($timestamp_half!=$last_per_half){
                    $symbol_object['per-hist'][]=[$timestamp_date,$symbol_object['per']];
                }
            }
        }


    }
    $stocks_formated_arr[$item['t'].':'.$item['e']]=$symbol_object;
}
// --------------------------------------------- 


$stocks_formated_arr_json_str=json_encode( $stocks_formated_arr );

// update stocks.formated.json
echo date('Y-m-d H:i:s')." updating stocks.formated.json\n";
fwrite($stock_cron_log, date('Y-m-d H:i:s')." updating stocks.formated.json\n");
$stocks_formated_json_file = fopen("stocks.formated.json", "w") or die("Unable to open file stocks.formated.json!");
fwrite($stocks_formated_json_file, $stocks_formated_arr_json_str);
fclose($stocks_formated_json_file);

// backup history (monthly)
if(!file_exists( date("Y-m").'.stocks.formated.json' )){
    echo "creating backup: ".date("Y-m").".stocks.formated.json<br />";
    fwrite($stock_cron_log, date('Y-m-d H:i:s')." creating backup: ".date("Y-m").".stocks.formated.json\n");
    $stocks_formated_json_fileb = fopen(date("Y-m").".stocks.formated.json", "w") or die("Unable to open file stocks.formated.json!");
    fwrite($stocks_formated_json_fileb, $stocks_formated_arr_json_str);
    fclose($stocks_formated_json_fileb);
}

// send alert bulcks only 1 email...
// TODO TODO
//echo "sending email alerts if any!" | tee -a $destination/ERROR.log; 
//wget --timeout=180 -q -O $destination/stock-alerts.log http://www.cognitionis.com/cult/www/backend/send-stock-alerts-fire.php?autosecret=1secret&gendate=$current_date > $destination/last-stock-alerts-errors.log; 

fwrite($stock_cron_log, date('Y-m-d H:i:s')." done with stock_cron.php\n");
echo date('Y-m-d H:i:s')." done with stock_cron.php, see stock_cron.log<br />";
fclose($stock_cron_log);

?>