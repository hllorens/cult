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
function toFixed($number, $decimals=2) {
  if(!is_numeric($number)){
        echo "not numeric: $number";
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
            $symbol_object['value_hist_last_diff']=toFixed(((floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-1][1])-floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-3][1]))/max(0.5,abs(floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-3][1]))))*100,0);
        }
        // anualized 3y
        $symbol_object['val_change_3y']=0;
        if(count($symbol_object['value_hist'])>3){
            $symbol_object['val_change_3y']=toFixed(((floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-1][1])-floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-4][1]))/max(0.5,abs(floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-4][1]))))*(100/3),0);
        }
        // anualized 5y
        $symbol_object['val_change_5y']=$symbol_object['val_change_3y']; // by default use 3y val change
        if(count($symbol_object['value_hist'])>5){
            $symbol_object['val_change_5y']=toFixed(((floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-1][1])-floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-6][1]))/max(0.5,abs(floatval($symbol_object['value_hist'][count($symbol_object['value_hist'])-6][1]))))*(100/5),0);
        }
        $symbol_object['val_yy_drops']=0;
        // % of y-y declines >5% (e.g., if 3 out of 6 years it declined then 0.5)
        for( $i= 0 ; $i < (count($symbol_object['value_hist'])-1) ; $i++ ){
            if(((floatval($symbol_object['value_hist'][($i+1)][1])-floatval($symbol_object['value_hist'][$i][1]))/max(0.5,abs(floatval($symbol_object['value_hist'][$i][1]))))*100 < -5){
                $symbol_object['val_yy_drops']+=1/(count($symbol_object['value_hist'])-1);
            }
        }
        $symbol_object['val_yy_drops']=toFixed($symbol_object['val_yy_drops']);
        
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
            $symbol_object['range_52week_heat']="".toFixed((floatval($symbol_object['value'])-floatval($symbol_object['range_52week_low']))/(floatval($symbol_object['range_52week_high'])-floatval($symbol_object['range_52week_low'])));
            // you could use "low" or "high" but using "low" is what if val 3 to 6 then volat = 100% == 2x
            // NOTE: value is always positive so no problem with subtractions...
            $symbol_object['range_52week_volatility']="".toFixed((floatval($symbol_object['range_52week_high'])-floatval($symbol_object['range_52week_low']))/(floatval($symbol_object['range_52week_low'])));
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

        // ONLY IF IT IS NOT AN INDEX
        if(substr($item['market'],0,5)=="INDEX"){
            echo "idx"; 
            $symbol_object['operating_margin']=0;
            $symbol_object['operating_margin_prev']=0;
            $symbol_object['operating_margin_avg']=0;
            $symbol_object['price_to_sales']=99;
            $symbol_object['leverage']=99;
            $symbol_object['inst_own']=0;
        }else{
            echo "!idx"; 
            $symbol_object['yield']=$stock_details_arr[$item['market'].':'.$item['name']]['yield'];
            $symbol_object['dividend']=$stock_details_arr[$item['market'].':'.$item['name']]['dividend'];
            $symbol_object['divs_per_year']="0";
            $symbol_object['dividend_total_year']="0";
            $symbol_object['eps']=$stock_details_arr[$item['market'].':'.$item['name']]['eps'];
            $symbol_object['per']=$stock_details_arr[$item['market'].':'.$item['name']]['per'];
            $symbol_object['shares']=$stock_details_arr[$item['market'].':'.$item['name']]['shares'];
            $symbol_object['mktcap']=toFixed(floatval($symbol_object['shares'])*floatval($symbol_object['value']));
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
            $symbol_object['operating_margin_prev']=$stock_details_arr[$item['market'].':'.$item['name']]['operating_margin_prev']; // ttm is not possible... since the avg om might not be equal to the yearly revenue - yearly op expenses...
            if(floatval($symbol_object['operating_margin_prev'])==0 && count($symbol_object['operating_margin_prev_hist'])>1){
                $symbol_object['operating_margin_prev']=$symbol_object['operating_margin_prev_hist'][count($symbol_object['operating_margin_prev_hist'])-2][1];
            } 
            $symbol_object['operating_margin_avg']="".number_format((floatval($symbol_object['operating_margin'])+floatval($symbol_object['operating_margin_prev']))/2, 2, ".", "");
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
            $symbol_object['epsp']=toFixed(floatval($symbol_object['eps']/max(0.001,floatval($symbol_object['value']))),3);
            $symbol_object['avgyield']=$symbol_object['yield'];
            if(floatval($symbol_object['dividend'])!=0){
                $symbol_object['divs_per_year']="".round(((floatval($symbol_object['yield'])/100)*floatval($symbol_object['value']))/floatval($symbol_object['dividend']));
                $symbol_object['dividend_total_year']="".toFixed(floatval($symbol_object['dividend'])*floatval($symbol_object['divs_per_year']));
                if(floatval($symbol_object['per'])>0){
                    $symbol_object['yield_per_ratio']="".toFixed((floatval($symbol_object['yield'])/floatval($symbol_object['per'])));
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
            // trend eps
            if(count($symbol_object['eps_hist'])>1){
                //echo $symbol_object['name']."<br />\n"."<br />\n";
                // The symbol (+ or -) is not an issue since only the numerator counts, the abs divisor is an issue if we do not do translation or paliation
                // e.g., hist 1 then 2 is a clear +100% (2x), hist -1 then 1 would be 200% but -5 then 5 would be 10/5 200% and -1 then 5 would be 6/1 600%
                // the case would be even more problematic if we go below 1 -0.5 then 5 would be 5.5/0.5=+1100%
                // the diff has a minimum divisor of 0.5 so value is at most amplified 2x in case of small eps values
                // other alternatives already testsed and complicated and not better (e.g., trying some translation or symbol change)
                // diff -6 to -3 is +50% while 3 to 6 is +200% but I think that is acceptable
                $eps_hist_last_diff=((floatval(end($symbol_object['eps_hist'])[1])-floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-2][1]))/max(0.5,abs(floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-2][1]))));
                if ($eps_hist_last_diff<-0.04){ // more than 5% annual which is about 20% quarterly
                    $symbol_object['eps_hist_last_down']=toFixed($eps_hist_last_diff*100,0); // FOR BACKWARDS COMPATIBILITY
                }
                if ($eps_hist_last_diff<-0.001 || $eps_hist_last_diff>0.001){ // more than 5% annual which is about 20% quarterly  
                    $symbol_object['eps_hist_last_diff']=toFixed($eps_hist_last_diff*100,0);
                }else{
                    $symbol_object['eps_hist_last_diff']=0;
                }
                if(count($symbol_object['eps_hist'])>2){
                    // 4 possibilities down-down down-up up-down up-up
                    // same minimum 0.5 divisor see above for the reason
                    $eps_hist_penultimate_diff=((floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-2][1])-floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-3][1]))/max(0.5,abs(floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-3][1]))));
                    $symbol_object['eps_hist_trend']="--";
                    if      ($eps_hist_penultimate_diff>0.1 && $eps_hist_last_diff>0.1){
                        $symbol_object['eps_hist_trend']="/";
                    }else if($eps_hist_penultimate_diff>0.1 && $eps_hist_last_diff <0.1 && $eps_hist_last_diff >-0.1){
                        $symbol_object['eps_hist_trend']="/-";
                    }else if($eps_hist_penultimate_diff<-0.1 && $eps_hist_last_diff >=$eps_hist_penultimate_diff ){
                        $symbol_object['eps_hist_trend']="v";
                    }else if($eps_hist_penultimate_diff>0.1 && $eps_hist_last_diff <=(-1*eps_hist_penultimate_diff)){
                        $symbol_object['eps_hist_trend']="^";
                    }else if($eps_hist_penultimate_diff<0.1 && $eps_hist_last_diff <0.1 && $eps_hist_last_diff >-0.1){
                        $symbol_object['eps_hist_trend']="\_";
                    }else if($eps_hist_penultimate_diff<0.1 && $eps_hist_last_diff <0.1){
                        $symbol_object['eps_hist_trend']="\\";
                    }
                }
            }

            hist('yield',6,$symbol_object,8,7); // 6=every half year, avgelems=8 (default), max in avg is 7% yield
            hist('per',6,$symbol_object); // 6=every half year avgelems=8 (default)
            hist('operating_margin',3,$symbol_object); // 3=every quarter, but not useful for ttm calculation since om average might not be equal to anual revenue - operating expenses
            hist('operating_margin_prev',12,$symbol_object); // yearly
            hist('shares',6,$symbol_object); // 6=every half year
            hist('employees',6,$symbol_object); // 6=every half year
            hist('inst_own',3,$symbol_object); // 
            
            // in addition to avg yield with max 6% elements (and min 0.25%), per min is also 6 to avoid odd low pers when stock is plunging (so we use max)
            // yield we use the minimum between the current and the average so if it is suddenly big we pick avg, if it is suddenly low, we pick current
            // we use max PER of 100 to avoid using 999 on losses and penalize too much even if other measures in the improved measure are strong
            $avgyield_per_ratio=max(min(floatval($symbol_object['avgyield']),floatval($symbol_object['yield'])),1.5)/min(max((floatval($symbol_object['per'])+floatval($symbol_object['avgper']))/2,6.0),100);
            // store original and still provide that sort to see the difference
            $symbol_object['avgyield_per_ratio_original']="".toFixed($avgyield_per_ratio);
            
            // improved with operating_margin and price to sales 
            $om_to_ps=0; // if no info, no gain
            if(array_key_exists('operating_margin',$symbol_object) && floatval($symbol_object['operating_margin'])!=0
               && array_key_exists('price_to_sales',$symbol_object) && floatval($symbol_object['price_to_sales'])!=0){
                $om_to_ps=max(min(((floatval($symbol_object['operating_margin'])+floatval($symbol_object['avgoperating_margin']))/2),55)/8,0.2)/min(max(((floatval($symbol_object['price_to_sales'])+floatval($symbol_object['avgprice_to_sales']))/2)*4,6.0),100);
                // if exists use the avg for the calculation
                if(array_key_exists('operating_margin_avg',$symbol_object) && floatval($symbol_object['operating_margin_avg'])!=0){
                    $om_to_ps=max(min(((floatval($symbol_object['operating_margin_avg'])+floatval($symbol_object['avgoperating_margin']))/2),55)/8,0.2)/min(max(((floatval($symbol_object['price_to_sales'])+floatval($symbol_object['avgprice_to_sales']))/2)*4,6.0),100);
                } 
            }
            $avgyield_per_ratio+=$om_to_ps; 
            
            // revenue growth (max 25% which is higher than google and apple recently that are about 15%-20%)
            if(array_key_exists('avg_revenue_growth_5y',$symbol_object) && floatval($symbol_object['avg_revenue_growth_5y'])!=0){
                            $avgyield_per_ratio+=max(min(floatval($symbol_object['avg_revenue_growth_5y']),20),-20)/100;
            }
            if(array_key_exists('revenue_growth_qq_last_year',$symbol_object) && floatval($symbol_object['revenue_growth_qq_last_year'])!=0){
                            $avgyield_per_ratio+=max(min(((floatval($symbol_object['revenue_growth_qq_last_year'])+floatval($symbol_object['avgrevenue_growth_qq_last_year']))/2),10),-10)/100;
            }
            
            // take value growth into account
            $avgyield_per_ratio+=(max(min(floatval($symbol_object['val_change_5y']),10),-20)+max(min(floatval($symbol_object['val_change_3y']),15),-20))/200;

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
                $avgyield_per_ratio=$avgyield_per_ratio/min(max($leverage_industry_ratio,1.0),2.0);
            }
            $symbol_object['leverage_industry_ratio']="".toFixed($leverage_industry_ratio);

            // EXTRA BONUS: if we also have the industry leverage average we can 
            /*if(array_key_exists('leverage_industry',$symbol_object) && floatval($symbol_object['leverage_industry'])!=0){
                // only punish for now if(floatval($symbol_object['leverage'])<(floatval($symbol_object['leverage_industry']-1))){$avgyield_per_ratio+=0.1;}
                if(floatval($symbol_object['leverage'])>(floatval($symbol_object['leverage_industry'])*1.8)){
                    $avgyield_per_ratio-=0.1;
                }
            }*/
            // we could consider the history (direction of the leverage) but that is not always a good indicator and it is already accounted in the above division (e.g., if it is growing the ypr will be lower)
            // TODO: next year we could consider revenue diff trying to get the sum of the last 4 Q (or price to sales... see if this is updated in some site to crawl)
            // TODO: Take into account manual analysis as well..., suscribe to news, show manual estimates maybe alert on them if they are lower...
            
            if($debug) echo "avgyield_per_ratio=$avgyield_per_ratio, avgyield=".max(floatval($symbol_object['avgyield']),0.75)." per=".max(floatval($symbol_object['per']),6.0)." leverage=".floatval($symbol_object['leverage'])." leverage_industry=".floatval($symbol_object['leverage_industry']);
            
            $symbol_object['avgyield_per_ratio']="".toFixed($avgyield_per_ratio);
            
            // heat divided by 15 times volatility so max is around 0.1 (+0.06 times the volatility [among 0.8-3] (instead of adding 1 we add 0.8 since the min volatility is around 0.2 so that the min is 1)
            $heat_opportunity=((1-floatval($symbol_object['range_52week_heat']))/15)*(floatval($symbol_object['range_52week_volatility'])+0.8);
            $eps_opportunity=max(-0.025,min(0.025,floatval($symbol_object['eps_hist_last_diff'])/100)); // max 0.0025
            $eps_trend=0.0;
            if(array_key_exists('eps_hist_trend',$symbol_object) && floatval($symbol_object['eps_hist_last_diff'])!=0){
                if($symbol_object['eps_hist_trend']=='v') $eps_trend=0.03;
                if($symbol_object['eps_hist_trend']=='/') $eps_trend=0.03;
                if($symbol_object['eps_hist_trend']=='/-') $eps_trend=0.01; 
                if($symbol_object['eps_hist_trend']=='\-') $eps_trend=0.01;
                if($symbol_object['eps_hist_trend']=='^') $eps_trend=-0.03;
                if($symbol_object['eps_hist_trend']=='\\') $eps_trend=-0.03;
                //simplified we ignore $eps_opportunity=min(0.3,(floatval($symbol_object['eps_hist_last_diff'])/100)+($eps_hist_penultimate_diff/2)); // max 0.3 so uppwards it can only add 0.3 (0.8 eps almost doubled)
            }
            $negative_growth_penalty=0.0;
            if(floatval($symbol_object['avg_revenue_growth_5y'])<0){
                // max -0.15 to be a bad groinging company
                $negative_growth_penalty=-0.05+max(floatval($symbol_object['avg_revenue_growth_5y']),-20)/400;
                if(floatval($symbol_object['revenue_growth_qq_last_year'])< 0){
                    //subtratct the average with max of -0.1
                    $negative_growth_penalty+=max(floatval($symbol_object['revenue_growth_qq_last_year'])+floatval($symbol_object['avg_revenue_growth_5y']),-20)/200;
                }
            }
            $high_yld_low_volatility_bonus=0.0;
            if(floatval($symbol_object['avgyield'])>3 && floatval($symbol_object['range_52week_volatility'])<0.4){
                $high_yld_low_volatility_bonus=0.05;
            }
            $revenue_growth_bonus=0.0;
            if(floatval($symbol_object['revenue_growth_qq_last_year']) > floatval($symbol_object['avg_revenue_growth_5y'])) $revenue_growth_bonus=max(0,min(0.05,floatval($symbol_object['revenue_growth_qq_last_year'])));

            //directly subtract $val_decline_penalty=-1*$symbol_object['val_yy_drops']; // min 0 (evergreen) max 1 (everdecline 5%)

            $symbol_object['heat_opportunity']="".toFixed($heat_opportunity);
            $symbol_object['eps_opportunity']="".toFixed($eps_opportunity);
            $symbol_object['eps_trend']="".toFixed($eps_trend);
            $symbol_object['high_yld_low_volatility_bonus']="".toFixed($high_yld_low_volatility_bonus);
            $symbol_object['revenue_growth_bonus']="".toFixed($revenue_growth_bonus);
            $symbol_object['negative_growth_penalty']="".toFixed($negative_growth_penalty);  
            
            $symbol_object['h_souce']="".toFixed($avgyield_per_ratio+$heat_opportunity+$eps_opportunity+$eps_trend+$high_yld_low_volatility_bonus+$revenue_growth_bonus+$negative_growth_penalty-(floatval($symbol_object['val_yy_drops'])/2));
            if(floatval($symbol_object['h_souce'])<0.1){echo "ypr=$avgyield_per_ratio heat=".$symbol_object['range_52week_heat']." eps_hist_last_diff=".$symbol_object['eps_hist_last_diff']." -> $heat_opportunity $eps_opportunity $eps_trend $negative_growth_penalty h_souce=".$symbol_object['h_souce'];}
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