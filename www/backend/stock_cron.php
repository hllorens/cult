<?php

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
function toFixed($number, $decimals=2, $tracking="stock_cron unset") {
  if(!is_numeric($number)){
        echo "not numeric: $number ($tracking)";
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

echo date('Y-m-d H:i:s')." start stock_cron.php<br />";

// fopen with w overwrites existing file
$stock_cron_log = fopen("stock_cron.log", "w") or die("Unable to open/create stock_cron.log!");
fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_cron.php\n");

fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_list.php\n");
require_once 'stock_list.php';
require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)

//service discontinued
//fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_curl_all_basic.php\n");
//require_once 'stock_curl_all_basic.php';
// update stocks formatfed accordingly (formatting properly)



//fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_curl_usdeur.php\n");
//require_once 'stock_curl_usdeur.php';



fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_curl_details.php\n");
require_once 'stock_curl_details.php';


// -----------update stocks formatted ----------------------------------

// only add in GOOG, date and usdeur
$stocks_formatted_arr['GOOG:NASDAQ']['date']=$timestamp_simplif;
//$stocks_formatted_arr['GOOG:NASDAQ']['usdeur']=$usdeur;

foreach ($stock_details_arr as $key => $item) {
	$symbol_object=array();
    // t=ticker, e=exchange // deprecated since /info?q= was down in google Sept 2017
    if($debug) echo "encoding ".$item['name'].":".$item['market']."<br />";
    
    // load info if exists
    if(array_key_exists($item['name'].":".$item['market'],$stocks_formatted_arr)){
        if($debug) echo "loading existing info for ".$item['name'].":".$item['market']."<br />";
        $symbol_object=$stocks_formatted_arr[$item['name'].":".$item['market']];
    }

    // update new gathered details
    if(array_key_exists($item['market'].":".$item['name'],$stock_details_arr)){
        echo "<br /> >Updating details for ".$item['market'].":".$item['name']."<br/>";
        // refresh basic info
        $symbol_object['name']=$item['name'];
        $symbol_object['market']=$item['market'];
        $symbol_object['date']=$timestamp_simplif;
        $symbol_object['value']=str_replace(",","",$item['value']);                      //$item['l']);
        hist('value',12,$symbol_object); // every year
        // replace last diff by penultimate diff if exists and it is still early in the year
        if(count($symbol_object['value_hist'])>2 && intval(date("n"))<4){ // only set it if it is early in the year so it makes more sense to diff with the previous year
            $symbol_object['value_hist_last_diff']=toFixed(((floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-1][1])-floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-3][1]))/max(0.5,abs(floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-3][1]))))*100,1,"value_hist_last_diff");
        }
        // anualized 3y
        $symbol_object['val_change_3y']=0;
        if(count($symbol_object['value_hist'])>3){
            $symbol_object['val_change_3y']=toFixed(((floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-1][1])-floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-4][1]))/max(0.5,abs(floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-4][1]))))*(100/3),1,"val_change_3y");
        }
        // anualized 3y prev (without the current)
        $symbol_object['val_change_3yp']=$symbol_object['val_change_3y']; // by default use 3y val change
        if(count($symbol_object['value_hist'])>4){
            $symbol_object['val_change_3yp']=toFixed(((floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-2][1])-floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-5][1]))/max(0.5,abs(floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-5][1]))))*(100/3),1,"val_change_3yp");
        }
        // anualized 3y prev prev
        $symbol_object['val_change_3ypp']=$symbol_object['val_change_3y']; // by default use 3y val change
        if(count($symbol_object['value_hist'])>5){
            $symbol_object['val_change_3ypp']=toFixed(((floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-3][1])-floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-6][1]))/max(0.5,abs(floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-6][1]))))*(100/3),1,"val_change_3ypp");
        }
        // anualized 5y
        $symbol_object['val_change_5y']=$symbol_object['val_change_3y']; // by default use 3y val change
        if(count($symbol_object['value_hist'])>5){
            $symbol_object['val_change_5y']=toFixed(((floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-1][1])-floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-6][1]))/max(0.5,abs(floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-6][1]))))*(100/5),1,"val_change_5y");
        }
        // anualized 5y prev (without the current)
        $symbol_object['val_change_5yp']=$symbol_object['val_change_5y']; // by default use 3y val change
        if(count($symbol_object['value_hist'])>6){
            $symbol_object['val_change_5yp']=toFixed(((floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-2][1])-floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-7][1]))/max(0.5,abs(floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-7][1]))))*(100/5),1,"val_change_5yp");
        }

        $symbol_object['val_yy_drops']=0;
        // % of y-y declines >5% (e.g., if 3 out of 6 years it declined then 0.5)
        for( $i= 0 ; $i < (count($symbol_object['value_hist'])-1) ; $i++ ){
            if(((floatval($symbol_object['value_hist'][($i+1)][1])-floatval($symbol_object['value_hist'][$i][1]))/max(0.5,abs(floatval($symbol_object['value_hist'][$i][1]))))*100 < -5){
                $symbol_object['val_yy_drops']+=1/(count($symbol_object['value_hist'])-1);
            }
        }
        $symbol_object['val_yy_drops']=toFixed($symbol_object['val_yy_drops'],2,"val_yy_drops");
        
        $symbol_object['session_change']=$item['session_change'];                        //$item['c'];
        $symbol_object['session_change_percentage']=$item['session_change_percentage'];  //$item['cp'];

        $symbol_object['title']=substr($stock_details_arr[$item['market'].':'.$item['name']]['title'],0,30);
        if(!$symbol_object['title']){$symbol_object['title']="ERROR: No title found";}
        if($debug) echo $symbol_object['title']."<br />";
        $symbol_object['range_52week']=trim($stock_details_arr[$item['market'].':'.$item['name']]['range_52week']);
        $symbol_object['range_52week_high']="0";
        $symbol_object['range_52week_low']="0";
        $symbol_object['range_52week_heat']="0";
        $symbol_object['range_52week_volatility']="0";
        if(strpos($symbol_object['range_52week'], '- ') !== false){
            $parts = explode('- ', $symbol_object['range_52week']);
            $symbol_object['range_52week_low']=trim($parts[0]);
            $symbol_object['range_52week_high']=trim($parts[1]);
            $symbol_object['range_52week_heat']="".toFixed((floatval($symbol_object['value'])-floatval($symbol_object['range_52week_low']))/(floatval($symbol_object['range_52week_high'])-floatval($symbol_object['range_52week_low'])),2,"heat");
            // you could use "low" or "high" but using "low" is what if val 3 to 6 then volat = 100% == 2x
            // NOTE: value is always positive so no problem with subtractions...
            $symbol_object['range_52week_volatility']="".toFixed((floatval($symbol_object['range_52week_high'])-floatval($symbol_object['range_52week_low']))/(floatval($symbol_object['range_52week_low'])),2,"volat");
            // to show the 2x format or 1.3x, we can do it in js to avoid making json bigger
            //$symbol_object['range_52week_volatility_times']="".toFixed(((floatval($symbol_object['range_52week_high'])-floatval($symbol_object['range_52week_low']))/(floatval($symbol_object['range_52week_low'])))+1,1);
        }
        $symbol_object['beta']=$stock_details_arr[$item['market'].':'.$item['name']]['beta'];
        // initialize to avoid stockionic sorting breaks
        $symbol_object['yield_per_ratio']="0";
        $symbol_object['avgyield_per_ratio']="0";
        $symbol_object['h_souce']=0;
        $symbol_object['per']=999;
        $symbol_object['yield']=0;
        $symbol_object['avgyield']=0;
        $symbol_object['eps']=0;
        $symbol_object['epsp']=0;
        $symbol_object['eps_hist_last_diff']=0;
        $computable_val_growth=((max(min(floatval($symbol_object['val_change_5y']),20),-20)+
                                 max(min(floatval($symbol_object['val_change_5yp']),20),-20)+
                                 max(min(floatval($symbol_object['val_change_3y']),20),-20)+
                                 max(min(floatval($symbol_object['val_change_3yp']),20),-20)+
                                 max(min(floatval($symbol_object['val_change_3ypp']),20),-20))
                                 /5)/100;
        $symbol_object['computable_val_growth']="".toFixed($computable_val_growth);;


        // ONLY IF IT IS NOT AN INDEX
        if(substr($item['market'],0,5)=="INDEX"){
            echo "idx"; 
            $symbol_object['operating_margin']=0;
            $symbol_object['operating_margin_prev']=0;     // TODO: remove in 2019
            $symbol_object['operating_margin_avg']=0;      // TODO: remove in 2019
            $symbol_object['price_to_sales']=99;
            $symbol_object['om_to_ps']=0;
            $symbol_object['leverage']=99;
            $symbol_object['inst_own']=0;
            $symbol_object['guessed_value']=0;
        }else{
            echo "!idx"; 
            $symbol_object['yield']=$stock_details_arr[$item['market'].':'.$item['name']]['yield'];
            $symbol_object['dividend']=$stock_details_arr[$item['market'].':'.$item['name']]['dividend'];
            $symbol_object['divs_per_year']="0";
            $symbol_object['dividend_total_year']="0";
            $symbol_object['eps']=$stock_details_arr[$item['market'].':'.$item['name']]['eps'];
            $symbol_object['per']=$stock_details_arr[$item['market'].':'.$item['name']]['per'];
            $symbol_object['shares']=$stock_details_arr[$item['market'].':'.$item['name']]['shares'];
            $symbol_object['mktcap']=toFixed(floatval($symbol_object['shares'])*floatval($symbol_object['value']),2,"cap and shares");
            $symbol_object['roe']=$stock_details_arr[$item['market'].':'.$item['name']]['roe'];
            $symbol_object['operating_margin']=$stock_details_arr[$item['market'].':'.$item['name']]['operating_margin'];
            // BACKUP Strategy of important measures ----------------------------------
            // google sometimes discards operating maring when result publication is close, solution: if 0 and prev !=0 use prev
            // The script also fails sometimes for growth revenue qq etc so here is a backup strategy if 0
            if(floatval($symbol_object['eps'])==0.001 && count($symbol_object['eps_hist'])>1){
                $symbol_object['eps']=$symbol_object['eps_hist'][count($symbol_object['eps_hist'])-2][1];
            }
            if(floatval($symbol_object['operating_margin'])==0 && count($symbol_object['operating_margin_hist'])>1){
                $symbol_object['operating_margin']=$symbol_object['operating_margin_hist'][count($symbol_object['operating_margin_hist'])-2][1];
            }
            // TODO: TEMPORARY UNTIL 2019-----------------
            $symbol_object['operating_margin_prev']=$stock_details_arr[$item['market'].':'.$item['name']]['operating_margin_prev']; // ttm is not possible... since the avg om might not be equal to the yearly revenue - yearly op expenses...
            if(floatval($symbol_object['operating_margin_prev'])==0 && count($symbol_object['operating_margin_prev_hist'])>1){
                $symbol_object['operating_margin_prev']=$symbol_object['operating_margin_prev_hist'][count($symbol_object['operating_margin_prev_hist'])-2][1];
            } 
            $symbol_object['operating_margin_avg']="".number_format((floatval($symbol_object['operating_margin'])+floatval($symbol_object['operating_margin_prev']))/2, 2, ".", "");
            // -------------------------------------------
            if(floatval($symbol_object['revenue'])==0 && count($symbol_object['revenue_hist'])>1){
                $symbol_object['revenue']=$symbol_object['revenue_hist'][count($symbol_object['revenue_hist'])-2][1];
            }
            if(floatval($symbol_object['revenue_growth_qq_last_year'])==0 && count($symbol_object['revenue_growth_qq_last_year_hist'])>1){
                $symbol_object['revenue_growth_qq_last_year']=$symbol_object['revenue_growth_qq_last_year_hist'][count($symbol_object['revenue_growth_qq_last_year_hist'])-2][1];
            }
            if(floatval($symbol_object['avg_revenue_growth_5y'])==0 && count($symbol_object['avg_revenue_growth_5y_hist'])>1){
                $symbol_object['avg_revenue_growth_5y']=$symbol_object['avg_revenue_growth_5y_hist'][count($symbol_object['avg_revenue_growth_5y_hist'])-2][1];
            }
            //-------------------------------------------------
            $symbol_object['key_period']=$stock_details_arr[$item['market'].':'.$item['name']]['key_period'];
            $symbol_object['key_period_prev']=$stock_details_arr[$item['market'].':'.$item['name']]['key_period_prev'];
            $symbol_object['employees']=$stock_details_arr[$item['market'].':'.$item['name']]['employees'];
            $symbol_object['inst_own']=$stock_details_arr[$item['market'].':'.$item['name']]['inst_own'];
            $symbol_object['avgyield']=$symbol_object['yield'];
            if(floatval($symbol_object['dividend'])!=0){
                $symbol_object['divs_per_year']="".round(((floatval($symbol_object['yield'])/100)*floatval($symbol_object['value']))/floatval($symbol_object['dividend']));
                $symbol_object['dividend_total_year']="".toFixed(floatval($symbol_object['dividend'])*floatval($symbol_object['divs_per_year']),2,"div total year");
                if(floatval($symbol_object['per'])>0){
                    $symbol_object['yield_per_ratio']="".toFixed((floatval($symbol_object['yield'])/floatval($symbol_object['per'])),2,"yeld_per_ratio");
                }
            }
            // historical data: eps, yield, per
            
            // not using function for eps because it has some specific tunnings (e.g., give preference to change over quarter, minimum 0.04 diff...)
            if(!array_key_exists('eps_hist',$symbol_object)){$symbol_object['eps_hist']=array();}
            if(!array_key_exists('eps_hist_last_diff',$symbol_object)){$symbol_object['eps_hist_last_diff']=0;}
            $eps_hist_penultimate_diff=0; // externalized for opportunity calculation

            if(array_key_exists('eps',$symbol_object) && $symbol_object['eps']!="" && $symbol_object['eps']!="-"){
                //echo "eps val".$symbol_object['name']."<br />";
                if(count($symbol_object['eps_hist'])==0){
                    //echo "initial eps".$symbol_object['name']."<br />";
                    $symbol_object['eps_hist'][]=[$timestamp_date,$symbol_object['eps']];
                }else{
                    //echo "non initial eps".$symbol_object['name']."<br />";  //print_r($symbol_object);
                    $last_eps=end($symbol_object['eps_hist'])[1];
                    $last_eps_date=end($symbol_object['eps_hist'])[0];
                    $last_eps_quarter=substr($last_eps_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $last_eps_date)->format('n') / 3) % 4) + 1 );
                    // NOTE: We do save it if the quarter is new even if it is the same...
                    //if($symbol_object['eps']!=$last_eps){
                        if($timestamp_quarter!=$last_eps_quarter){
                            $symbol_object['eps_hist'][]=[$timestamp_date,$symbol_object['eps']];
                        }else{
                            //echo $symbol_object['name'].':'.$symbol_object['market'].": actualizado eps hist mismo quarter $last_eps_quarter";
                            $symbol_object['eps_hist'][count($symbol_object['eps_hist']) - 1]=[$timestamp_date,$symbol_object['eps']];
                        }
                    //}
                }
            }


            hist('yield',6,$symbol_object,8,7); // 6=every half year, avgelems=8 (default), max in avg is 7% yield
            hist('operating_margin',3,$symbol_object); // 3=every quarter, but not useful for ttm calculation since om average might not be equal to anual revenue - operating expenses
            hist('operating_margin_prev',12,$symbol_object); // yearly  TODO TEMP remove after 2019
            hist('shares',6,$symbol_object); // 6=every half year
            hist('employees',6,$symbol_object); // 6=every half year
            hist('inst_own',6,$symbol_object); // 
            hist('per',6,$symbol_object); // 6=every half year avgelems=8 (default)
            hist('eps',3,$symbol_object); // avg elems 8
            // trend eps
            if(count($symbol_object['eps_hist'])>2){
                $eps_hist_last_diff=$symbol_object['eps_hist_last_diff'];
                $eps_hist_penultimate_diff=((floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-2][1])-floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-3][1]))/max(0.5,abs(floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-3][1]))));
                $eps_threshold=0.15;
                $symbol_object['eps_hist_trend']="--";
                if      ($eps_hist_penultimate_diff>$eps_threshold && $eps_hist_last_diff>$eps_threshold){
                    $symbol_object['eps_hist_trend']="/";
                }else if($eps_hist_penultimate_diff>$eps_threshold && $eps_hist_last_diff <$eps_threshold && $eps_hist_last_diff >-$eps_threshold){
                    $symbol_object['eps_hist_trend']="/-";
                }else if($eps_hist_penultimate_diff <$eps_threshold && $eps_hist_penultimate_diff >-$eps_threshold && $eps_hist_last_diff>$eps_threshold){
                    $symbol_object['eps_hist_trend']="_/";
                }else if($eps_hist_penultimate_diff<-$eps_threshold && $eps_hist_last_diff > $eps_hist_penultimate_diff ){
                    $symbol_object['eps_hist_trend']="v";
                }else if($eps_hist_penultimate_diff>$eps_threshold && $eps_hist_last_diff < (-1*$eps_hist_penultimate_diff)){
                    $symbol_object['eps_hist_trend']="^";
                }else if($eps_hist_penultimate_diff<-$eps_threshold && $eps_hist_last_diff <$eps_threshold && $eps_hist_last_diff >-$eps_threshold){
                    $symbol_object['eps_hist_trend']="\_";
                }else if($eps_hist_penultimate_diff <$eps_threshold && $eps_hist_penultimate_diff >-$eps_threshold && $eps_hist_last_diff<-$eps_threshold){
                    $symbol_object['eps_hist_trend']="-\\";
                }else if($eps_hist_penultimate_diff<-$eps_threshold && $eps_hist_last_diff <-$eps_threshold){
                    $symbol_object['eps_hist_trend']="\\";
                }
            }
            // EPSP (inverse of PER, 1/PER), it is also eps/price
            // since price and num shares change, if we want to use averages, it is safer to calculate as PER inverse
            // however, PER does not account for negative EPS, so we are forced to calculate as eps/price
            $symbol_object['epsp']=toFixed(floatval($symbol_object['eps']/max(0.01,floatval($symbol_object['value']))),3,"epsp");
            if(count($symbol_object['eps_hist'])>1){
                $symbol_object['epsp']=toFixed(floatval($symbol_object['avgeps']/max(0.01,floatval($symbol_object['value']))),3,"epsp");
            }
            hist('epsp',12,$symbol_object); // yearly
            
            // THE SCORE (all sub-scores go 0-1 and are merged at the end)
            $score_yield=0;
            $computable_yield=min(floatval($symbol_object['avgyield']),floatval($symbol_object['yield']))/100;  // max avg is 7 already by definition (see hist above)
            if($computable_yield>0.029 && $computable_yield<=floatval($symbol_object['epsp'])){ // if it's a big (>3%) healthy viable yield (<=epsp)
                $score_yield=min(0.30+(($computable_yield-0.029)*25),1); // max 1 (if y>6)
            }
            
            $score_val_growth=0;
            $computable_val_growth+=$computable_yield;
            $symbol_object['avgvalg']=toFixed($computable_val_growth,1,"avgvalg");
            if($computable_val_growth>0){
                $score_val_growth=min($computable_val_growth*5,1);
            }
            hist('avgvalg',12,$symbol_object); // yearly
            
            $score_rev_growth=0;
            $om_to_ps=0; // if no info, no gain
            if(array_key_exists('operating_margin',$symbol_object) && floatval($symbol_object['operating_margin'])!=0
               && array_key_exists('price_to_sales',$symbol_object) && floatval($symbol_object['price_to_sales'])!=0){
                $om_to_ps=min(max(floatval($symbol_object['avgoperating_margin'])*3,0)/max(floatval($symbol_object['avgprice_to_sales'])*10,0.1),1);
                //TEMPORARY UNTIL 2019 --------------------------------------------------------------
                // if exists use the avg for the calculation temporary until 2019 where we already get a history in op
                if(array_key_exists('operating_margin_avg',$symbol_object) && floatval($symbol_object['operating_margin_avg'])!=0){
                    $om_to_ps=min(max(((floatval($symbol_object['avgoperating_margin'])+floatval($symbol_object['operating_margin_avg']))/2)*3,0)/max(floatval($symbol_object['avgprice_to_sales'])*10,0.1),1); 
                } 
                // ----------------------------------------------------------------------------------
            }
            if(array_key_exists('avg_revenue_growth_5y',$symbol_object) && floatval($symbol_object['avg_revenue_growth_5y'])>0){
                $score_rev_growth=min(floatval($symbol_object['avg_revenue_growth_5y']),20)*5/100;
            }
            $score_rev_growth+=$om_to_ps*0.1; // max 0.1 (can be penalizing -0.1)
            // good quarter only +0.1 (cannot penalize), and only if good means om_to_ps>0.2
            if(array_key_exists('revenue_growth_qq_last_year',$symbol_object) && floatval($symbol_object['revenue_growth_qq_last_year'])>0 && $om_to_ps>0.2){
                $score_rev_growth+=min(floatval($symbol_object['avgrevenue_growth_qq_last_year']),10)/100;
            }
            $score_rev_growth=max(min($score_rev_growth,1),0);  // min 0 max 1

            $score_epsp=0;
            $epsp=floatval($symbol_object['epsp']);
            if($epsp>=0){
                // the distribution of positive cases goes from 0 to 10%
                // we decided that 6.67% (per=15) is good enough to get 100% score
                $score_epsp=($epsp)*15;
                if($computable_yield>($epsp+0.006)) $score_epsp-=($epsp-$computable_yield)*15; // penalized if yield > $epsp
                if(array_key_exists('eps_hist_trend',$symbol_object)){
                    if($symbol_object['eps_hist_trend']=='/-') $score_epsp+=0.10; 
                    if($symbol_object['eps_hist_trend']=='_/') $score_epsp+=0.20; 
                    if($symbol_object['eps_hist_trend']=='/') $score_epsp+=0.30;
                }
                $score_epsp=max(min($score_epsp,1),0);  // min 0 max 1
            }

            $score_leverage=0;
            // improved ypr with leverage (if lower or equal to 2.5 it makes no difference)
            $acceptable_leverage=2.5; // 2 would be liabilities==equity i.e., liabilities/assets=0.5 perfect balance
            $leverage_industry_ratio=99;
            if(array_key_exists('leverage',$symbol_object) && floatval($symbol_object['leverage'])!=0){
                // Most industries have 3 auto, teleco, energy
                // tech has 2
                // 2.5 is a good compromise
                if(in_array($symbol_object['name'], ['SAN','BBVA','ING','BKIA','BKT','SAB','CABK','MAP','ZURVY','HSBC','R4'])){ 
                    $acceptable_leverage=10; // finance/insurance industry lives on this so we cannot penalize as much
                }
                if(array_key_exists('leverage_industry',$symbol_object) && floatval($symbol_object['leverage_industry'])!=0){
                    $acceptable_leverage=max(floatval($symbol_object['leverage_industry']),2.5);
                }
                $leverage_industry_ratio=floatval($symbol_object['leverage'])/$acceptable_leverage;
                $score_leverage=(-1*min(max($leverage_industry_ratio,1.0),2.0))+2;
            }
            $symbol_object['leverage_industry_ratio']="".toFixed($leverage_industry_ratio,2,"leverage_industry_ratio");

            
            
            $negative_val_growth_penalty=0.0;
            if($computable_val_growth<0){
                $negative_val_growth_penalty=$computable_val_growth*5;
            }
            if(floatval($symbol_object['val_yy_drops'])>0.3) $negative_val_growth_penalty-=0.15;
            if(floatval($symbol_object['val_yy_drops'])>0.67) $negative_val_growth_penalty-=0.25;
            $negative_val_growth_penalty=max(min($negative_val_growth_penalty,0),-1);
                
            $negative_rev_growth_penalty=0.0;
            if(floatval($symbol_object['avg_revenue_growth_5y'])<0 && $epsp<0.03){
                // max -0.15 to be a bad groinging company
                $negative_rev_growth_penalty=-0.25+max(floatval($symbol_object['avg_revenue_growth_5y']),-50)/100;
                if(floatval($symbol_object['revenue_growth_qq_last_year'])< 0){
                    //subtratct the average with max of -0.1
                    $negative_rev_growth_penalty+=max(floatval($symbol_object['revenue_growth_qq_last_year'])+floatval($symbol_object['avg_revenue_growth_5y']),-20)/200;
                }
            }
            if(floatval($symbol_object['revenue_growth_qq_last_year'])< -0.15 && $epsp<0.03){
                $negative_rev_growth_penalty+=min(max(floatval($symbol_object['revenue_growth_qq_last_year'])+floatval($symbol_object['avg_revenue_growth_5y']),-50),0)/200;
            }
            $negative_rev_growth_penalty=max(min($negative_rev_growth_penalty,0),-1);  // min 0 max 1

            $negative_eps_growth_penalty=0.0;
            if($epsp<0.03 && array_key_exists('eps_hist_trend',$symbol_object)){
                if($symbol_object['eps_hist_trend']=='\\') $negative_eps_growth_penalty=-1;
                if($symbol_object['eps_hist_trend']=='-\\') $negative_eps_growth_penalty=-0.5;
                if($symbol_object['eps_hist_trend']=='\_') $negative_eps_growth_penalty=-0.5;
                if($symbol_object['eps_hist_trend']=='^') $negative_eps_growth_penalty=-0.25;
            }
            if($epsp<-0.015){
                $negative_eps_growth_penalty=-1;
            }

            $symbol_object['om_to_ps']="".toFixed($om_to_ps);
            $symbol_object['computable_val_growth']="".toFixed($computable_val_growth);
            

            $calc_value_sell_share_raw=((floatval($symbol_object['revenue'])/max(0.0001,floatval($symbol_object['shares']))));
            $calc_value_sell_share=($calc_value_sell_share_raw*min(floatval($symbol_object['avgoperating_margin'])/33,1));
            $calc_value_asset_share=(floatval($symbol_object['value'])/max(0.0001,floatval($symbol_object['price_to_book'])));
            $calc_value_mult_factor=                          ((
                                    max(min(
                                    min(floatval($symbol_object['avg_revenue_growth_5y']),40)   // max 40
                                    +(min(floatval($epsp),0.06)*400)           // max 24
                                    ,60),1)   // max 60, min 1                
                                  )/7); // ideally 7.5 but accounting for optimism
            
            $symbol_object['guessed_value']="".toFixed(((floatval($calc_value_sell_share)
                                  *
                                  floatval($calc_value_mult_factor))
                                  +floatval($calc_value_asset_share)
                                  ),1,"calc_value guessed_value");


            if($computable_yield>0.029 && ($computable_val_growth-$computable_yield)<0.15){
                $symbol_object['h_souce']="".toFixed(
                                                     ($score_yield*0)+
                                                     ($score_val_growth*5)+
                                                     ($score_rev_growth*2)+
                                                     ($score_epsp*2)+
                                                     ($score_leverage*1)+
                                                     ($negative_val_growth_penalty*3)+
                                                     ($negative_rev_growth_penalty*2)+
                                                     ($negative_eps_growth_penalty*2)
                                                     ,1,"h_souce div");
            }else{
                $symbol_object['h_souce']="".toFixed(
                                                     ($score_yield*0)+
                                                     ($score_val_growth*5)+
                                                     ($score_rev_growth*2)+
                                                     ($score_epsp*2)+
                                                     ($score_leverage*1)+
                                                     ($negative_val_growth_penalty*3)+
                                                     ($negative_rev_growth_penalty*2)+
                                                     ($negative_eps_growth_penalty*2)
                                                     ,1,"h_souce !div");
            }
            
            if(floatval($symbol_object['h_souce'])<1){echo " h_souce=".$symbol_object['h_souce'];}
            hist_min('h_souce',12,$symbol_object); // yearly  TODO TEMP remove after 2019

            
            // old stuff
            $avgyield_per_ratio=max(min(floatval($symbol_object['avgyield']),floatval($symbol_object['yield'])),1.5)/min(max((floatval($symbol_object['per'])+floatval($symbol_object['avgper']))/2,6.0),100);           
            if($debug) echo "avgyield_per_ratio=$avgyield_per_ratio, avgyield=".max(floatval($symbol_object['avgyield']),0.75)." per=".max(floatval($symbol_object['per']),6.0)." leverage=".floatval($symbol_object['leverage'])." leverage_industry=".floatval($symbol_object['leverage_industry']);
            $symbol_object['avgyield_per_ratio']="".toFixed($avgyield_per_ratio,2,"bottom avgyield_per_ratio");
        }
    }
    $stocks_formatted_arr[$item['name'].':'.$item['market']]=$symbol_object;
}
// --------------------------------------------- 


$stocks_formatted_arr_json_str=json_encode( $stocks_formatted_arr );

// update stocks.formatted.json
echo date('Y-m-d H:i:s')." updating stocks.formatted.json\n";
fwrite($stock_cron_log, date('Y-m-d H:i:s')." updating stocks.formatted.json\n");
$stocks_formatted_json_file = fopen("stocks.formatted.json", "w") or die("Unable to open file stocks.formatted.json!");
fwrite($stocks_formatted_json_file, $stocks_formatted_arr_json_str);
fclose($stocks_formatted_json_file);

// backup history (monthly)
if(!file_exists( date("Y-m").'.stocks.formatted.json' )){
    echo "creating backup: ".date("Y-m").".stocks.formatted.json<br />";
    fwrite($stock_cron_log, date('Y-m-d H:i:s')." creating backup: ".date("Y-m").".stocks.formatted.json\n");
    $stocks_formatted_json_fileb = fopen(date("Y-m").".stocks.formatted.json", "w") or die("Unable to open file stocks.formatted.json!");
    fwrite($stocks_formatted_json_fileb, $stocks_formatted_arr_json_str);
    fclose($stocks_formatted_json_fileb);
}

// send alert bulcks only 1 email..., if too long then create another cron for this
fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_send-alert-fire.php\n");
echo "<br />".date('Y-m-d H:i:s')." starting stock_send-alerts-fire.php<br />";
require_once 'stock_send-alerts-fire.php';


fwrite($stock_cron_log, date('Y-m-d H:i:s')." done with stock_cron.php\n");
echo "<br />".date('Y-m-d H:i:s')." done with stock_cron.php, see stock_cron.log<br />";
fclose($stock_cron_log);

?>