<?php

// the aim of this php is to get stocks_clean.json and from it stocks.formatted.json (clean + calculated stuff)


require_once("email_config.php");
require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)


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
    echo "<br/><br/><span style=\"color:red\">ERROR:</span>stocks.formatted.json does NOT exist -> using an empty array<br />";
}



echo date('Y-m-d H:i:s')." start stock_cron.php<br />";

// fopen with w overwrites existing file
$stock_cron_log = fopen("stock_cron.log", "w") or die("Unable to open/create stock_cron.log!");
fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_cron.php\n");

fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_list.php\n");
require_once 'stock_list.php';



fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_curl_details.php\n");
require_once 'stock_curl_details.php';


// only add in GOOG latest date and usdeur
if(!array_key_exists('GOOG:NASDAQ',$stocks_formatted_arr)){$stocks_formatted_arr['GOOG:NASDAQ']=array();}
$stocks_formatted_arr['GOOG:NASDAQ']['date']=$timestamp_simplif;

foreach ($stock_details_arr as $key => $item) {
	$symbol_object=array();
    $symbol_formatted=array();
	// stock_curl_details should avoid this already by not adding the key/item pair, but just to avoid failures we re-check
	if(!isset($item['name']) || $item['name']==""){
		echo "ERROR: empty name in the details...<br />";
		send_mail('ERROR:'.$item['name'].' details name !exist','<br />ERROR: empty name in the details...<br /><br />',"hectorlm1983@gmail.com");
		continue;
	}
    if($debug) echo "encoding ".$item['name'].":".$item['market']."<br />";
    
    // load info if exists
    if(array_key_exists($item['name'].":".$item['market'],$stocks_formatted_arr)){
        if($debug) echo "loading existing info for ".$item['name'].":".$item['market']."<br />";
        $symbol_object=$stocks_formatted_arr[$item['name'].":".$item['market']];
        if(!array_key_exists('title',$symbol_object) || !array_key_exists('shares',$symbol_object)){
            echo "title or shares not exist";
            send_mail('ERROR:'.$item['name'].' title or shares !exist','<br />This stock was in stocks.formatted.json but without title or shares, fix manually.<br /><br />',"hectorlm1983@gmail.com");
            exit(1);
        }
    }else{
        echo "<br />No old info... first time?<br />";
    }

    // update new gathered details
    if(array_key_exists($item['market'].":".$item['name'],$stock_details_arr)){
        echo "<br /> >details for ".$item['market'].":".$item['name']."<br/>";
        // refresh basic info
        if(!isset($item['value']) || $item['title']=="" || $item['value']==""){
            echo "ERROR: empty title or value in the details...<br />";
            send_mail('ERROR:'.$item['name'].' details title or value !exist','<br />ERROR: empty title or value in the details...<br /><br />',"hectorlm1983@gmail.com");
            continue;
        }
        $symbol_object['name']=$item['name'];
        $symbol_object['market']=$item['market'];
        $symbol_object['date']=$timestamp_simplif;
        $symbol_object['title']=$item['title'];
        if(!$symbol_object['title']){$symbol_object['title']="ERROR: No title found";}
        $symbol_object['session_change_percentage']=$item['session_change_percentage'];
        $symbol_object['value']=str_replace(",","",$item['value']);
        #echo $item['value']." xx ".$symbol_object['value']."<br />";
        $symbol_object['range_52week_high']=trim($item['range_52week_high']);
        $symbol_object['range_52week_low']=trim($item['range_52week_low']);
        $symbol_object['yield']=$stock_details_arr[$item['market'].':'.$item['name']]['yield'];   // already 0 if index in details
        if(array_key_exists('shares',$symbol_object) && abs(floatval($symbol_object['shares'])-floatval($stock_details_arr[$item['market'].':'.$item['name']]['shares']))>max(0.04,floatval($stock_details_arr[$item['market'].':'.$item['name']]['shares'])/20)){
            // if the diff is bigger than 5% or 0.04 whatever is bigger
            send_mail($item['name'].' sharenum change','<br />original('.$symbol_object['shares_source'].'):'.$symbol_object['shares'].' != new ('.$stock_details_arr[$item['market'].':'.$item['name']]['shares_source'].'):'.$stock_details_arr[$item['market'].':'.$item['name']]['shares'].
                                                       '<br />title:'.$symbol_object['title'].
                                                       '<br />value:'.$symbol_object['value'].
                                                       '<br />change:'.$symbol_object['session_change_percentage'].
                                                       '<br /><br />',"hectorlm1983@gmail.com");
        }
        $symbol_object['shares']=$stock_details_arr[$item['market'].':'.$item['name']]['shares']; // already 0 if index in details
        $symbol_object['shares_source']=$stock_details_arr[$item['market'].':'.$item['name']]['shares_source'];
        $symbol_object['mktcap']=$stock_details_arr[$item['market'].':'.$item['name']]['mktcap'];// already 0 if index in details

        // hist of basic stuff needs to be done here since it is used below, and if it is the first time a new value is added, it would be needed
        hist_year_last_day('value',$symbol_object); // every year
        if(substr($symbol_object['market'],0,5)!="INDEX"){
            // only store yield, mktcap history if no index
            hist('yield',6,$symbol_object,8,7); // 6=every half year, avgelems=8 (default), max in avg is 7% yield
            hist_year_last_day('mktcap',$symbol_object); // 6=every half year
        }
        
        
        // refresh processed info
        $symbol_formatted=$symbol_object;
        
        // replace last diff by penultimate diff if exists and it is still early in the year
        if(count($symbol_formatted['value_hist'])>2 && intval(date("n"))<4){ // only set it if it is early in the year so it makes more sense to diff with the previous year
            $symbol_formatted['value_hist_last_diff']=toFixed(compound_average_growth(floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-3][1]),floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-1][1]))*100,1,"value_hist_last_diff");
        }
        // annualized 3y
        $symbol_formatted['val_change_3y']=0;
        if(count($symbol_formatted['value_hist'])>3){
            $symbol_formatted['val_change_3y']=toFixed(compound_average_growth(floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-4][1]),floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-1][1]),3)*100,1,"val_change_3y");
        }
        // annualized 3y prev (without the current)
        $symbol_formatted['val_change_3yp']=min(floatval($symbol_formatted['val_change_3y']),2); // by default 2% inflation
        if(count($symbol_formatted['value_hist'])>4){
            $symbol_formatted['val_change_3yp']=toFixed(compound_average_growth(floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-5][1]),floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-2][1]),3)*100,1,"val_change_3yp");
        }
        // annualized 3y prev prev
        $symbol_formatted['val_change_3ypp']=min(floatval($symbol_formatted['val_change_3yp']),2); // by default use 3y val change
        if(count($symbol_formatted['value_hist'])>5){
            $symbol_formatted['val_change_3ypp']=toFixed(compound_average_growth(floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-6][1]),floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-3][1]),3)*100,1,"val_change_3ypp");
        }
        // annualized 5y
        $symbol_formatted['val_change_5y']=min(floatval($symbol_formatted['val_change_3y']),1); // by default use 3y val change
        if(count($symbol_formatted['value_hist'])>5){
            $symbol_formatted['val_change_5y']=toFixed(compound_average_growth(floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-6][1]),floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-1][1]),5)*100,1,"val_change_5y");
        }
        // annualized 5y prev (without the current)
        $symbol_formatted['val_change_5yp']=min(floatval($symbol_formatted['val_change_5y']),1); // by default use 5y val change
        if(count($symbol_formatted['value_hist'])>6){
            $symbol_formatted['val_change_5yp']=toFixed(compound_average_growth(floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-7][1]),floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-2][1]),5)*100,1,"val_change_5yp");
        }

        $symbol_formatted['val_yy_drops']=0;
        // % of y-y declines >5% (e.g., if 3 out of 6 years it declined then 0.5)
        for( $i= 0 ; $i < (count($symbol_formatted['value_hist'])-1) ; $i++ ){
            if(((floatval($symbol_formatted['value_hist'][($i+1)][1])-floatval($symbol_formatted['value_hist'][$i][1]))/max(0.5,abs(floatval($symbol_formatted['value_hist'][$i][1]))))*100 < -5){
                $symbol_formatted['val_yy_drops']+=1/(count($symbol_formatted['value_hist'])-1);
            }
        }
        $symbol_formatted['val_yy_drops']=toFixed($symbol_formatted['val_yy_drops'],2,"val_yy_drops");

        $symbol_formatted['range_52week_heat']="".toFixed((floatval($symbol_formatted['value'])-floatval($symbol_formatted['range_52week_low']))/(floatval($symbol_formatted['range_52week_high'])-floatval($symbol_formatted['range_52week_low'])),2,"heat");
        $symbol_formatted['range_52week_volatility']="".toFixed((floatval($symbol_formatted['range_52week_high'])-floatval($symbol_formatted['range_52week_low']))/(floatval($symbol_formatted['range_52week_low'])),2,"volat");

        // Here because we want indexes to have this property
        $computable_val_growth=((max(min(floatval($symbol_formatted['val_change_5y']),20),-20)+
                                 max(min(floatval($symbol_formatted['val_change_5yp']),20),-20)+
                                 max(min(floatval($symbol_formatted['val_change_3y']),20),-20)+
                                 max(min(floatval($symbol_formatted['val_change_3yp']),20),-20)+
                                 max(min(floatval($symbol_formatted['val_change_3ypp']),20),-20))
                                 /5)/100;
        $symbol_formatted['computable_val_growth']="".toFixed($computable_val_growth);
        

        // ONLY IF IT IS NOT AN INDEX
        if(substr($symbol_formatted['market'],0,5)=="INDEX"){
            echo "idx"; 
            // only minimal things used for sorting (those shown in the details page can be unset)
            $symbol_formatted['h_souce']=0;
            $symbol_formatted['avgyield']=0;
            $symbol_formatted['eps']=0;
            $symbol_formatted['epsp']=0;
            //$symbol_formatted['eps_hist_last_diff']=0; TODO unused in favor of epsp last diff
            $symbol_formatted['om_to_ps']=0;
            $symbol_formatted['leverage']=99;
        }else{
            echo "!idx";
            // set defaults if no leverage-book and send email
            if(!array_key_exists('leverage',$symbol_formatted) || !array_key_exists('price_to_book',$symbol_formatted) || !array_key_exists('avg_revenue_growth_5y',$symbol_formatted)){
                send_mail('NOTE:'.$item['name'].' !revenue-book','<br />This stock is running stock_cron.php without revenue-book, fix manually (run leverage-book for it).<br /><br />',"hectorlm1983@gmail.com");
                $symbol_formatted['avg_revenue_growth_5y']=0;
                $symbol_formatted['revenue_growth_qq_last_year']=0;
                $symbol_formatted['leverage']=99;
                $symbol_formatted['price_to_book']=99;
                
            }
            // reset things that are going to be calculated
            //$symbol_formatted['last_financials_year']=0000;
            $symbol_formatted['operating_margin']=0;
            $om_to_ps=0;
            $revenue=0;
            $symbol_formatted['epsp']=0;
            //$symbol_formatted['revp']=0;
            $symbol_formatted['oip']=0;
            $symbol_formatted['eqp']=0;
            $symbol_formatted['levp']=0;
            $symbol_formatted['eps_hist_trend']="--";
            $symbol_formatted['price_to_sales']=99;
            $symbol_formatted['h_souce']=0;
            $symbol_formatted['guessed_value']=0;
            $symbol_formatted['eps']=0;
            // leverage, price to book  are not calculated
            //$symbol_formatted['eps_hist_last_diff']=0;
            //---------------------------------------------
            $ref_value=floatval($symbol_formatted['value']);
            if(count($symbol_formatted['value_hist'])>1){
                $ref_value=floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-2][1]); // last year's value (less volatility in scorings)
            }
            if(array_key_exists('revenue_hist',$symbol_formatted)){
                $last_revenue_year=floatval(substr(end($symbol_formatted['revenue_hist'])[0],0,4));
                $last_value_year=floatval(substr(end($symbol_formatted['value_hist'])[0],0,4));
                if(($last_value_year-$last_revenue_year)>2){
                    echo "ERROR: too old financials $last_revenue_year<br />";
                    send_mail(''.$item['name'].' too old financials',"<br />ERROR: too old financials $last_revenue_year<br /><br />","hectorlm1983@gmail.com");
                    exit(1);
                }
                // if 2 year old revenue
                if(count($symbol_formatted['value_hist'])>2 && ($last_value_year-$last_revenue_year)==2){
                    $ref_value=floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-3][1]); // 2 years old value (more accurate, less volatility in scorings)
                }
            
                //$symbol_formatted['last_financials_year']=substr($symbol_formatted['revenue_hist'][0][0],0,4); can be calculated directly in js
                $revenue=floatval(end($symbol_formatted['revenue_hist'])[1]);
                $operating_income=floatval(end($symbol_formatted['operating_income_hist'])[1]);
                $symbol_formatted['oip']=($operating_income/floatval($symbol_formatted['shares']))/max(0.01,$ref_value);
                $equity=floatval(end($symbol_formatted['equity_hist'])[1]);
                $symbol_formatted['eqp']=($equity/floatval($symbol_formatted['shares']))/max(0.01,$ref_value);
                if(end($symbol_formatted['operating_income_hist'])[0]!=end($symbol_formatted['revenue_hist'])[0] || end($symbol_formatted['net_income_hist'])[0]!=end($symbol_formatted['revenue_hist'])[0]){
                    echo "ERROR: Last operating income year (".end($symbol_formatted['operating_income_hist'])[0].") != last revenue year (".end($symbol_formatted['revenue_hist'])[0].")<br />";
                    send_mail(''.$item['name'].' last hist year revenue!=op.inc!=net.inc',"<br />ERROR: Last operating income year (".end($symbol_formatted['operating_income_hist'])[0].") != last revenue year (".end($symbol_formatted['revenue_hist'])[0]." != last net inc year (".end($symbol_formatted['net_income_hist'])[0].")<br /><br /><br />","hectorlm1983@gmail.com");
                    exit(1);
                }
                if($revenue==0){ 
                    echo "revenue 0"; 
                    send_mail('ERROR:'.$item['name'].' revenue 0','<br />This stock has financials but revenue is 0, fix manually.<br /><br />',"hectorlm1983@gmail.com");
                    exit(1);
                }
                $symbol_formatted['operating_margin']=toFixed($operating_income/$revenue,2,'operating margin');
                // average it if possible
                if(count($symbol_formatted['operating_income_hist'])>1 && count($symbol_formatted['revenue_hist'])>1){
                    if($symbol_formatted['operating_income_hist'][count($symbol_formatted['operating_income_hist'])-2][0]!=$symbol_formatted['operating_income_hist'][count($symbol_formatted['revenue_hist'])-2][0]){
                        echo "ERROR: Last operating income year (".$symbol_formatted['operating_income_hist'][count($symbol_formatted['operating_income_hist'])-2][0].") != last revenue year (".$symbol_formatted['operating_income_hist'][count($symbol_formatted['revenue_hist'])-2][0].")<br />";
                        send_mail(''.$item['name'].' last hist year revenue!=op.inc',"<br />ERROR: Last operating income year (".$symbol_formatted['operating_income_hist'][count($symbol_formatted['operating_income_hist'])-2][0].") != last revenue year (".$symbol_formatted['operating_income_hist'][count($symbol_formatted['revenue_hist'])-2][0].")<br /><br /><br />","hectorlm1983@gmail.com");
                        exit(1);
                    }
                    $symbol_formatted['operating_margin']=toFixed(
                                                            (
                                                                ($operating_income/$revenue)+
                                                                (floatval($symbol_formatted['operating_income_hist'][count($symbol_formatted['operating_income_hist'])-2][1])/floatval($symbol_formatted['revenue_hist'][count($symbol_formatted['revenue_hist'])-2][1]))
                                                            )/2
                                                            ,2,'operating margin 2x avg');
                }
                // note we use the current value with last year's revenue, we could also use last year's price to make this more stable
                // but it is more accurate to use the current price since if it goes up a lot then it is more expensive... what if the quarterly sales go up too?
                $symbol_formatted['price_to_sales']=toFixed($ref_value/($revenue/floatval($symbol_formatted['shares'])),2,'operating margin');
                $om_to_ps=min(max(floatval($symbol_formatted['operating_margin'])*300,0)/max(floatval($symbol_formatted['price_to_sales'])*10,0.1),1);
                $symbol_formatted['eps']=toFixed(floatval(end($symbol_formatted['net_income_hist'])[1])/floatval($symbol_formatted['shares']),2,"stock_cron eps");
                // average it if possible
                if(count($symbol_formatted['net_income_hist'])>1){
                    $symbol_formatted['eps']=toFixed(
                                                    (
                                                        floatval($symbol_formatted['eps'])+
                                                        (floatval($symbol_formatted['net_income_hist'][count($symbol_formatted['net_income_hist'])-2][1])/floatval($symbol_formatted['shares']))
                                                    )/2,2,"stock_cron eps with 2 avg");
                }
                // EPSP (inverse of PER, 1/PER), it is also eps/price
                // since price and num shares change, if we want to use averages, it is safer to calculate as PER inverse
                // however, PER does not account for negative EPS, so we are forced to calculate as eps/price
                $symbol_formatted['epsp']=toFixed(floatval($symbol_formatted['eps'])/max(0.01,$ref_value),3,"epsp");
                if(count($symbol_formatted['net_income_hist'])>1){
                    $symbol_formatted['epsp']=toFixed(
                                                    (
                                                        floatval($symbol_formatted['eps'])+
                                                        (floatval($symbol_formatted['net_income_hist'][count($symbol_formatted['net_income_hist'])-2][1])/floatval($symbol_formatted['shares']))
                                                    )/2
                                                    /max(0.01,$ref_value),3,"epsp");
                }

                // growths, trends and accelerations
                $symbol_formatted['revenue_growth_arr']=hist_growth_array('revenue_hist',$symbol_formatted,5);
                $symbol_formatted['revenue_growth']=avg_weighted($symbol_formatted['revenue_growth_arr']);
                $revenue_acceleration=acceleration_array($symbol_formatted['revenue_growth_arr']);
                $symbol_formatted['revenue_acceleration']=avg_weighted($revenue_acceleration);
                $symbol_formatted['operating_income_growth_arr']=hist_growth_array('operating_income_hist',$symbol_formatted,5);
                $operating_income_acceleration=acceleration_array($symbol_formatted['operating_income_growth_arr']);
                $symbol_formatted['operating_income_acceleration']=avg_weighted($operating_income_acceleration);

                $symbol_formatted['net_income_growth_arr']=hist_growth_array('net_income_hist',$symbol_formatted,5);
                
                if(array_key_exists('equity_hist',$symbol_formatted)){
                    $symbol_formatted['equity_growth_arr']=hist_growth_array('equity_hist',$symbol_formatted,5);
                    $symbol_formatted['equity_growth']=avg_weighted($symbol_formatted['equity_growth_arr']);
                }
                // we can also do the operating trend... maybe directly in js if no operations...
                $symbol_formatted['eps_hist_trend']=trend($symbol_formatted['net_income_growth_arr']);
            }else{
                echo "!financials (no revenue), consider running financials and leverage-book manually";
                send_mail('NOTE:'.$item['name'].' !financials','<br />From stock_cron.php, there is no revenue_hist for this stock. !financials? fix manually (run financials).<br /><br />',"hectorlm1983@gmail.com");
            }


            
            // THE SCORE (all sub-scores go 0-1 and are merged at the end)
            $score_val_growth=0;
            $computable_yield=min(floatval($symbol_formatted['avgyield']),floatval($symbol_formatted['yield']))/100;  // max avg is 7 already by definition (see hist above)
            // the computable_val_growth is calculated above to include it in indexes, but yield is only for !indexes
            $computable_val_growth+=$computable_yield;
            $symbol_formatted['avgvalg']=toFixed($computable_val_growth,1,"avgvalg");
            if($computable_val_growth>0){
                $score_val_growth=min($computable_val_growth*5,1);
            }
            
            $score_rev_growth=0;
            if(array_key_exists('avg_revenue_growth_5y',$symbol_formatted) && floatval($symbol_formatted['avg_revenue_growth_5y'])>0){
                $score_rev_growth=min(min(floatval($symbol_formatted['avg_revenue_growth_5y']),floatval($symbol_formatted['revenue_growth']))*100,20)*5/100;
            }
            $score_rev_growth+=$om_to_ps*0.3; // max 0.1 (can be penalizing -0.1)
            // good quarter only +0.1 (cannot penalize), and only if good means om_to_ps>0.2
            if(array_key_exists('revenue_growth_qq_last_year',$symbol_formatted) && floatval($symbol_formatted['revenue_growth_qq_last_year'])>0 && $om_to_ps>0.2){
                $score_rev_growth+=min(floatval($symbol_formatted['revenue_growth_qq_last_year']),10)/100;
            }
            $score_rev_growth=max(min($score_rev_growth,1),0);  // min 0 max 1

            $score_epsp=0;
            $epsp=floatval($symbol_formatted['epsp']);
            if($epsp>=0){
                // the distribution of positive cases goes from 0 to 10%
                // we decided that 6.67% (per=15) is good enough to get 100% score
                $score_epsp=($epsp)*15;
                if($computable_yield>($epsp+0.006)) $score_epsp-=($epsp-$computable_yield)*15; // penalized if yield > $epsp
                if(array_key_exists('eps_hist_trend',$symbol_formatted)){
                    if($symbol_formatted['eps_hist_trend']=='/-') $score_epsp+=0.10; 
                    if($symbol_formatted['eps_hist_trend']=='_/') $score_epsp+=0.20; 
                    if($symbol_formatted['eps_hist_trend']=='/') $score_epsp+=0.30;
                }
                $score_epsp=max(min($score_epsp,1),0);  // min 0 max 1
            }

            $score_leverage=0;
            // improved ypr with leverage (if lower or equal to 2.5 it makes no difference)
            $acceptable_leverage=2.5; // 2 would be liabilities==equity i.e., liabilities/assets=0.5 perfect balance
            $leverage_industry_ratio=99;
            if(array_key_exists('leverage',$symbol_formatted) && floatval($symbol_formatted['leverage'])!=0){
                // Most industries have 3 auto, teleco, energy
                // tech has 2
                // 2.5 is a good compromise
                if(in_array($symbol_formatted['name'], ['SAN','BBVA','ING','BKIA','BKT','SAB','CABK','MAP','ZURVY','HSBC','R4'])){ 
                    $acceptable_leverage=10; // finance/insurance industry lives on this so we cannot penalize as much
                }
                if(array_key_exists('leverage_industry',$symbol_formatted) && floatval($symbol_formatted['leverage_industry'])!=0 && floatval($symbol_formatted['leverage_industry'])!=0.01){
                    $acceptable_leverage=max(floatval($symbol_formatted['leverage_industry']),2.5);
                }
                $leverage_industry_ratio=floatval($symbol_formatted['leverage'])/$acceptable_leverage;
                $score_leverage=(-1*min(max($leverage_industry_ratio,1.0),2.0))+2;
            }
            $symbol_formatted['leverage_industry_ratio']="".toFixed($leverage_industry_ratio,2,"leverage_industry_ratio");

            
            
            $negative_val_growth_penalty=0.0;
            if($computable_val_growth<0){
                $negative_val_growth_penalty=$computable_val_growth*5;
            }
            if(floatval($symbol_formatted['val_yy_drops'])>0.3) $negative_val_growth_penalty-=0.15;
            if(floatval($symbol_formatted['val_yy_drops'])>0.67) $negative_val_growth_penalty-=0.25;
            $negative_val_growth_penalty=max(min($negative_val_growth_penalty,0),-1);
                
            $negative_rev_growth_penalty=0.0;
            if(floatval($symbol_formatted['avg_revenue_growth_5y'])<0 && $epsp<0.03){
                // max -0.15 to be a bad groinging company
                $negative_rev_growth_penalty=-0.25+max(floatval($symbol_formatted['avg_revenue_growth_5y']),-50)/100;
                if(floatval($symbol_formatted['revenue_growth_qq_last_year'])< 0){
                    //subtratct the average with max of -0.1
                    $negative_rev_growth_penalty+=max(floatval($symbol_formatted['revenue_growth_qq_last_year'])+floatval($symbol_formatted['avg_revenue_growth_5y']),-20)/200;
                }
            }
            if(floatval($symbol_formatted['revenue_growth_qq_last_year'])< -0.15 && $epsp<0.03){
                $negative_rev_growth_penalty+=min(max(floatval($symbol_formatted['revenue_growth_qq_last_year'])+floatval($symbol_formatted['avg_revenue_growth_5y']),-50),0)/200;
            }
            $negative_rev_growth_penalty=max(min($negative_rev_growth_penalty,0),-1);  // min 0 max 1

            $negative_eps_growth_penalty=0.0;
            if($epsp<0.03 && array_key_exists('eps_hist_trend',$symbol_formatted)){
                if($symbol_formatted['eps_hist_trend']=='\\') $negative_eps_growth_penalty=-1;
                if($symbol_formatted['eps_hist_trend']=='-\\') $negative_eps_growth_penalty=-0.5;
                if($symbol_formatted['eps_hist_trend']=='\_') $negative_eps_growth_penalty=-0.5;
                if($symbol_formatted['eps_hist_trend']=='^') $negative_eps_growth_penalty=-0.25;
            }
            if($epsp<-0.015){
                $negative_eps_growth_penalty=-1;
            }

            $symbol_formatted['om_to_ps']="".toFixed($om_to_ps,2,"setting om_to_ps in symbol_formatted");
            $symbol_formatted['computable_val_growth']="".toFixed($computable_val_growth,2,"setting computable_val_growth in symbol_formatted");
            

            $calc_value_sell_share_raw=((floatval($revenue)/max(0.0001,floatval($symbol_formatted['shares']))));
            $calc_value_sell_share=($calc_value_sell_share_raw*min((floatval($symbol_formatted['operating_margin'])*100)/33,1));
            $calc_value_asset_share=(floatval($symbol_formatted['value'])/max(0.0001,floatval($symbol_formatted['price_to_book'])));
            $calc_value_mult_factor=                          ((
                                    max(min(
                                    min(floatval($symbol_formatted['avg_revenue_growth_5y']),40)   // max 40
                                    +(min(floatval($epsp),0.06)*400)           // max 24
                                    ,60),1)   // max 60, min 1                
                                  )/7); // ideally 7.5 but accounting for optimism
                                  // TODO improve calculations with acceleration and better revenue hist
            
            $symbol_formatted['guessed_value']="".toFixed(((floatval($calc_value_sell_share)
                                  *
                                  floatval($calc_value_mult_factor))
                                  +floatval($calc_value_asset_share)
                                  ),1,"calc_value guessed_value");


            $symbol_formatted['h_souce']="".toFixed(
                                                 ($score_val_growth*5)+
                                                 ($score_rev_growth*2)+
                                                 ($score_epsp*2)+
                                                 ($score_leverage*1)+
                                                 ($negative_val_growth_penalty*3)+
                                                 ($negative_rev_growth_penalty*2)+
                                                 ($negative_eps_growth_penalty*2)
                                                 ,1,"h_souce div");
            
            if(floatval($symbol_formatted['h_souce'])<1){echo " h_souce=".$symbol_formatted['h_souce'];}
            //hist_year_last_day('h_souce',$symbol_formatted); // yearly  TODO add when we close the app (slow, yearly)
        }
        $stocks_formatted_arr[$symbol_formatted['name'].':'.$symbol_formatted['market']]=$symbol_formatted;
    }else{
        echo "ERROR: ".$item['market'].":".$item['name']." not found in details<br />";
    }
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