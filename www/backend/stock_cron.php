<?php

// the aim of this php is to get stocks_clean.json and from it stocks.formatted.json (clean + calculated stuff)


require_once("email_config.php");
require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)


date_default_timezone_set('Europe/Madrid');
$timestamp_date=date("Y-m-d");
$timestamp_simplif=date("d H:i");
$timestamp_quarter=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 3) % 4) + 1 );
$timestamp_half=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 6) % 2) + 1 );

$FIREBASE='https://cult-game.firebaseio.com/';


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
    
	$json_name=$item['name'];
	// HANDLE EXCEPTIONS FOR BAD FIREBASE NAMES including '.' or '[]' or ...
	if($item['name']==".INX"){$json_name="INX";}

    // load info if exists
    if(array_key_exists($json_name.":".$item['market'],$stocks_formatted_arr)){
        if($debug) echo "loading existing info for ".$json_name.":".$item['market']."<br />";
        $symbol_object=$stocks_formatted_arr[$json_name.":".$item['market']];
        if(!array_key_exists('title',$symbol_object) || !array_key_exists('shares',$symbol_object)){
            echo "title or shares not exist";
            send_mail('ERROR:'.$json_name.' title or shares !exist','<br />This stock was in stocks.formatted.json but without title or shares, fix manually.<br /><br />',"hectorlm1983@gmail.com");
            exit(1);
        }
		// minimal hist check
		foreach ($symbol_object as $key => $value) {
			if(substr($key, -strlen('_hist'))==='_hist'){
				if($debug) echo "<br />checking order $key<br />";
				$last_val=0;
				foreach ($symbol_object[$key] as $val){
					if(floatval($val[0])<=$last_val){
						echo " ERROR in year order in $key in $json_name ";
						send_mail('ERROR:'.$json_name.' _hist order','<br />ERROR in year order in $key in $json_name, fix manually.<br /><br />',"hectorlm1983@gmail.com");
						exit(1);
					}
				}
			}
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
        $symbol_object['value']=$item['value'];
        #echo $item['value']." xx ".$symbol_object['value']."<br />";
        $symbol_object['range_52week_high']=trim($item['range_52week_high']);
        $symbol_object['range_52week_low']=trim($item['range_52week_low']);
        $symbol_object['yield']=$stock_details_arr[$item['market'].':'.$item['name']]['yield'];   // already 0 if index in details

		
		
		if(substr($symbol_object['market'],0,5)=="INDEX"){
			$symbol_object['mktcap']=$stock_details_arr[$item['market'].':'.$item['name']]['mktcap'];// already 0 if index in details
			$symbol_object['shares']=$stock_details_arr[$item['market'].':'.$item['name']]['shares']; // already 0 if index in details
			$symbol_object['shares_source']=$stock_details_arr[$item['market'].':'.$item['name']]['shares_source']; // will always be direct
		}else{
			// if not manual nor direct just break (well just email and use the guessed ones... otherwise it is impossible)
			if(
				!array_key_exists('shares_manual',$symbol_object) 
				&& 
				(
					!array_key_exists('shares',$stock_details_arr[$item['market'].':'.$item['name']])
					||
					(
						array_key_exists('shares',$stock_details_arr[$item['market'].':'.$item['name']]) && 
						(
							$stock_details_arr[$item['market'].':'.$item['name']]['shares']==0
							||
							$stock_details_arr[$item['market'].':'.$item['name']]['shares_source']!='direct'
						)
					)
				)
				
			){
				send_mail($item['name'].' ERROR shares manual missing','<br />ERROR shares manual missing in '.$item['name'].' and shares direct missing first time, please ADD<br /><br />',"hectorlm1983@gmail.com");
				//continue;
				$symbol_object['shares_manual']=array();
				$symbol_object['shares_manual'][]=array('2017-12-31',$stock_details_arr[$item['market'].':'.$item['name']]['shares']);
			}
			
			// if !manual means that we have direct
			if(!array_key_exists('shares_manual',$symbol_object)){
				$symbol_object['shares_manual']=array();
				$symbol_object['shares_manual'][]=array('2017-12-31',$stock_details_arr[$item['market'].':'.$item['name']]['shares']);
				send_mail($item['name'].' shares manual missing','<br />shares manual missing in '.$item['name'].', please review the automatically added version<br /><br />',"hectorlm1983@gmail.com");
			}

			if(floatval(substr($timestamp_date,0,4))>(floatval(substr(end($symbol_object['shares_manual'])[0],0,4))+1)){
				send_mail($item['name'].' shares manual old','<br />shares manual old in '.$item['name'].', please review and add updated info<br /><br />',"hectorlm1983@gmail.com");
			}
			// if manual and details direct, check
			if(array_key_exists('shares',$symbol_object) && // avoid this check if it is the first time
				array_key_exists('shares',$stock_details_arr[$item['market'].':'.$item['name']]) &&
					floatval($stock_details_arr[$item['market'].':'.$item['name']]['shares'])!=0 &&
					$stock_details_arr[$item['market'].':'.$item['name']]['shares_source']=='direct' &&
					abs(floatval($stock_details_arr[$item['market'].':'.$item['name']]['shares'])-floatval(end($symbol_object['shares_manual'])[1]))>max(0.04,floatval($stock_details_arr[$item['market'].':'.$item['name']]['shares'])/15)){
				// if the diff is bigger than 6.6% or 0.04 whatever is bigger

				// exceptions
				if(
					($symbol_object['name']=="SGRE" && $stock_details_arr[$item['market'].':'.$item['name']]['shares']=="0.28")
					||
					($symbol_object['name']=="SNAP" && $stock_details_arr[$item['market'].':'.$item['name']]['shares']=="1.22")
					||
					($symbol_object['name']=="TL5" && $stock_details_arr[$item['market'].':'.$item['name']]['shares']=="0.01")
					||
					($symbol_object['name']=="QCOM" && $stock_details_arr[$item['market'].':'.$item['name']]['shares']=="1.21")
					||
					($symbol_object['name']=="FB" && $stock_details_arr[$item['market'].':'.$item['name']]['shares']=="2.40")
				){
					echo "sharenum exception<br />";
				}else{
					send_mail($item['name'].' sharenum change','<br />original('.$symbol_object['shares_source'].'):'.$symbol_object['shares'].
															   ' <br />new ('.$stock_details_arr[$item['market'].':'.$item['name']]['shares_source'].'):'.$stock_details_arr[$item['market'].':'.$item['name']]['shares'].
															   '<br />shares manual:'.end($symbol_object['shares_manual'])[1].
															   '<br />diff%:'.abs(floatval($stock_details_arr[$item['market'].':'.$item['name']]['shares'])-floatval(end($symbol_object['shares_manual'])[1])).
															   '<br /><br />Time to update the manual shares?'.
															   '<br /><br />title:'.$symbol_object['title'].
															   '<br /><br /><br />value:'.$symbol_object['value'].
															   '<br />change:'.$symbol_object['session_change_percentage'].
															   '<br /><br />',"hectorlm1983@gmail.com");
				}
				
				$symbol_object['shares']=end($symbol_object['shares_manual'])[1]; // already 0 if index in details
				$symbol_object['shares_source']='manual';
		   
		   }else{
				if(
					array_key_exists('shares',$stock_details_arr[$item['market'].':'.$item['name']]) &&
						floatval($stock_details_arr[$item['market'].':'.$item['name']]['shares'])!=0 &&
					$stock_details_arr[$item['market'].':'.$item['name']]['shares_source']=='direct'
				){
					$symbol_object['shares']=$stock_details_arr[$item['market'].':'.$item['name']]['shares']; // already 0 if index in details
					$symbol_object['shares_source']=$stock_details_arr[$item['market'].':'.$item['name']]['shares_source']; // will always be direct
					if(floatval($symbol_object['shares'])<0.020){ // if shares are as little the calculations for eps, etc can be wrong, consider using other calculations
						echo "<br />Too few shares ".$symbol_object['shares']." calculated (min 0.02)..., email sent...<br />";
						send_mail('Err. few shares '.$item['name'],"<br />Too few shares calc ".$symbol_object['shares'].", consider removing symbol...<br /><br />","hectorlm1983@gmail.com");
					}
				}else{
					$symbol_object['shares']=end($symbol_object['shares_manual'])[1]; // already 0 if index in details
					$symbol_object['shares_source']='manual';
				}
			}
			// if mktcap exists and !=0 take otherwise calculate from shares that at this point must be good or manual
			if(
				array_key_exists('mktcap',$stock_details_arr[$item['market'].':'.$item['name']]) &&
					floatval($stock_details_arr[$item['market'].':'.$item['name']]['mktcap'])!=0
			){
				$symbol_object['mktcap']=$stock_details_arr[$item['market'].':'.$item['name']]['mktcap'];// already 0 if index in details
			}else{
				$symbol_object['mktcap']=toFixed(floatval($symbol_object['shares'])*floatval($symbol_object['value']),2,'mktcap calc');
			}
		}
		
		

		
		
		
		
		
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
        
		$symbol_formatted['eps']=0;
		$symbol_formatted['epsp']=0;

        // ONLY IF IT IS NOT AN INDEX
        if(substr($symbol_formatted['market'],0,5)=="INDEX"){
            echo "idx"; 
            // only minimal things used for sorting (those shown in the details page can be unset)
            $symbol_formatted['h_souce']=0;
            $symbol_formatted['avgyield']=0;
            //$symbol_formatted['eps_hist_last_diff']=0; TODO unused in favor of epsp last diff
            $symbol_formatted['om_to_ps']=0;
            $symbol_formatted['leverage']=99;
            $symbol_formatted['eps_hist_trend']="--";
            $symbol_formatted['prod']=0;
			$symbol_formatted['last_prod_ps_g']=0;
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
            $symbol_formatted['prod']=0;
			$prod_ps_guess=0;
            $om_to_ps=0;
            $revenue=0;
            $symbol_formatted['revp']=0;
            $symbol_formatted['oip']=0;
            $symbol_formatted['eqp']=0;
            $symbol_formatted['lp']=0;
            $symbol_formatted['eps_hist_trend']="--";
            $symbol_formatted['price_to_sales']=99;
            $symbol_formatted['h_souce']=0;
            $symbol_formatted['guessed_value']=$symbol_formatted['value'];
            $symbol_formatted['guessed_value_5y']=$symbol_formatted['value'];
            $symbol_formatted['guessed_percentage']=1;
            // leverage, price to book  are not calculated
            //$symbol_formatted['eps_hist_last_diff']=0;
            //---------------------------------------------
			
			
            $ref_value=floatval($symbol_formatted['value']);
            if(count($symbol_formatted['value_hist'])>1){
                $ref_value=floatval($symbol_formatted['value_hist'][count($symbol_formatted['value_hist'])-2][1]); // last year's value (less volatility in scorings)
            }
            if(array_key_exists('revenue_hist',$symbol_formatted)){
				$tsv_arr=array();
				get_anualized_data('value',$symbol_formatted,$tsv_arr);
				get_anualized_data('revenue',$symbol_formatted,$tsv_arr);
				get_anualized_data('operating_income',$symbol_formatted,$tsv_arr);
				get_anualized_data('net_income',$symbol_formatted,$tsv_arr);
				//var_dump($tsv_arr);
				clean_no_revenue($tsv_arr);
				$om_obj=get_om_max_avg_pot($tsv_arr);
				$prod_ps_hist_obj=get_prod_ps($tsv_arr);
                $prod_ps_growth_arr=hist_growth_array('prod_ps_hist',$prod_ps_hist_obj,5);
				//var_dump($prod_ps_hist_obj);
				//var_dump($prod_ps_growth_arr);
				$symbol_formatted['last_prod_ps_g']=end($prod_ps_growth_arr);
				$symbol_formatted['prod_ps_trend']=trend($prod_ps_growth_arr,0.05);
				//echo "om_max=".$om_obj['max']." om_avg=".$om_obj['avg']." om_pot=".$om_obj['pot']."<br />";
				$symbol_formatted['om_pot']=floatval(toFixed($om_obj['pot'],3,'om_pot'));
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
                $equity=floatval(end($symbol_formatted['equity_hist'])[1]);
				$equity_per_share=($equity/floatval($symbol_formatted['shares']));
                $symbol_formatted['eqp']=floatval(toFixed($equity_per_share/max(0.01,$ref_value),2,'eqp'));
                $price_to_book_inv=1/max(0.0001,floatval($symbol_formatted['price_to_book']));
				if(floatval($symbol_formatted['price_to_book'])!=99 && $symbol_formatted['eqp']==0) {
					$symbol_formatted['eqp']=$price_to_book_inv;
				}
				if(floatval($symbol_formatted['price_to_book'])!=99 && abs($symbol_formatted['eqp']-$price_to_book_inv)>0.10) {
					/*send_mail(''.$item['name'].' eqp!=pb_inv',
					                           "<br />ERROR: eqp(".$symbol_formatted['eqp'].")!=pb_inv($price_to_book_inv)".
                            				   " using pb_inv...<br />equity=$equity  shares=".$symbol_formatted['shares'].
											   "<br />equity_per_share=$equity_per_share<br /><br />","hectorlm1983@gmail.com");
					*/
					echo "ERROR eqp(".$symbol_formatted['eqp'].")!=pb_inv($price_to_book_inv) TODO better handle...<br />";
					// TODO cambiar email por slow log....
					$symbol_formatted['eqp']=$price_to_book_inv;
				}
                $symbol_formatted['price_to_book_calc']=toFixed($ref_value/max(0.001,$equity_per_share),2,'pbc');
				$assets=floatval($symbol_formatted['leverage'])*$equity;
                $symbol_formatted['ap']=toFixed(($assets/floatval($symbol_formatted['shares']))/max(0.01,$ref_value),2,'ap');
				$liabilities=$assets-$equity;
				$liabilities_ps=$liabilities/floatval($symbol_formatted['shares']);
                $symbol_formatted['lp']=toFixed($liabilities_ps/max(0.01,$ref_value),2,'lp');
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
                // average operating margin if possible
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
                // average eps if possible
                /*if(count($symbol_formatted['net_income_hist'])>1){
                    $symbol_formatted['eps']=toFixed(
                                                    (
                                                        floatval($symbol_formatted['eps'])+
                                                        (floatval($symbol_formatted['net_income_hist'][count($symbol_formatted['net_income_hist'])-2][1])/floatval($symbol_formatted['shares']))
                                                    )/2,2,"stock_cron eps with 2 avg");
                }*/

                // growths, trends and accelerations
                $symbol_formatted['revenue_growth_arr']=hist_growth_array('revenue_hist',$symbol_formatted,5);
                $symbol_formatted['revenue_growth']=avg_weighted($symbol_formatted['revenue_growth_arr'],0.66,0.99); // max 0.99
				// protection against negative growth in most recent year
				if(floatval(end($symbol_formatted['revenue_growth_arr']))<floatval($symbol_formatted['revenue_growth'])){
					$symbol_formatted['revenue_growth']=floatval(end($symbol_formatted['revenue_growth_arr']));
				}
                $revenue_acceleration=acceleration_array($symbol_formatted['revenue_growth_arr']);
				
				// this can be wrong if avg_weighted, e.g., can show positive for ^ when that could be at most 0
                //$symbol_formatted['revenue_acceleration']=avg_weighted($revenue_acceleration,0.66,0.99);
                $symbol_formatted['revenue_acceleration']=0;
				$revenue_acceleration_length=count($revenue_acceleration);
				if($revenue_acceleration_length>2){
					$acc_minus_1=$revenue_acceleration[$revenue_acceleration_length-2];
					$acc_last=$revenue_acceleration[$revenue_acceleration_length-1];
					if( 
						floatval($symbol_formatted['revenue_growth']) > 0 &&
						$acc_minus_1>=0 &&
						$acc_last   >0 
					){
						$symbol_formatted['revenue_acceleration']=max($acc_last,0.99);
					}
					if( 
						$acc_minus_1<=0 &&
						$acc_last   <0 
					){
						$symbol_formatted['revenue_acceleration']=max($acc_last,-0.99);
					}
				}
                //$symbol_formatted['revenue_acceleration']=avg_weighted($revenue_acceleration,0.66,0.99);
                //$symbol_formatted['operating_income_growth_arr']=hist_growth_array('operating_income_hist',$symbol_formatted,5);
                //$operating_income_acceleration=acceleration_array($symbol_formatted['operating_income_growth_arr']);
                //$symbol_formatted['operating_income_acceleration']=avg_weighted($operating_income_acceleration);
				// although maybe this could be better than eps trend...
                $symbol_formatted['net_income_growth_arr']=hist_growth_array('net_income_hist',$symbol_formatted,5);
                if(array_key_exists('equity_hist',$symbol_formatted)){
                    $symbol_formatted['equity_growth_arr']=hist_growth_array('equity_hist',$symbol_formatted,5);
                    $symbol_formatted['equity_growth']=avg_weighted($symbol_formatted['equity_growth_arr']);
                }
                // we can also do the operating trend... maybe directly in js if no operations...
                $symbol_formatted['eps_hist_trend']=trend($symbol_formatted['net_income_growth_arr'],0.03); // default 0.10
                $symbol_formatted['revenue_growth_trend']=trend($symbol_formatted['revenue_growth_arr'],0.02); // default 0.10, 0.02 to make it more sensitive

                $symbol_formatted['revps']=floatval(toFixed(($revenue/floatval($symbol_formatted['shares'])),2,'revps'));
                $symbol_formatted['revp']=toFixed(($symbol_formatted['revps'])/max(0.01,$ref_value),3,'revp');
                $symbol_formatted['oips']=floatval(toFixed(($operating_income/floatval($symbol_formatted['shares'])),2,'oips'));
                $symbol_formatted['oip']=toFixed(($operating_income/floatval($symbol_formatted['shares']))/max(0.01,$ref_value),3,'oip');
                // EPSP (inverse of PER, 1/PER), it is also eps/price
                // since price and num shares change, if we want to use averages, it is safer to calculate as PER inverse
                // however, PER does not account for negative EPS, so we are forced to calculate as eps/price
                $symbol_formatted['epsp']=toFixed(floatval($symbol_formatted['eps'])/max(0.01,$ref_value),3,"epsp");

				$operating_margin_pot=$symbol_formatted['om_pot'];
				
				
				
				
				
				// TODO: this is wrong... should we just use the latest? we are dividing the avg oi by the current shares and current price...
				// EITHER WE DO MORE COMPLEX OR THIS IS WRONG...?
				
				
				
                $symbol_formatted['prod']=toFixed(max(floatval($symbol_formatted['revp'])*$operating_margin_pot,floatval($symbol_formatted['oip']),floatval($symbol_formatted['epsp'])+(0.1*floatval($symbol_formatted['epsp']))),3,'prod');
                $symbol_formatted['prod_ps']=toFixed(max(floatval($symbol_formatted['revps'])*$operating_margin_pot,floatval($symbol_formatted['oips']),floatval($symbol_formatted['eps'])+(0.1*floatval($symbol_formatted['eps']))),3,'prodps');
				$prod_ps_guess=$symbol_formatted['prod_ps'];
				if($operating_margin_pot<0.01 && $symbol_formatted['revenue_growth']>=0.25){
					$prod_ps_guess=toFixed(max(floatval($symbol_formatted['revps'])*0.01,floatval($symbol_formatted['oips']),floatval($symbol_formatted['eps'])*1.1),3,'prod');
				}
				if($prod_ps_guess<0) $prod_ps_guess=0.00001;
			}else{
                echo "!financials (no revenue), consider running financials and fiancials, leverage-book manually";
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
            if(array_key_exists('revenue_growth',$symbol_formatted) && floatval($symbol_formatted['revenue_growth'])>0){
                $score_rev_growth=(min(floatval($symbol_formatted['revenue_growth'])*100,34))/100;
				if(floatval($symbol_formatted['revenue_growth'])>0.01) $score_rev_growth+=0.1;
				if(floatval($symbol_formatted['revenue_growth'])>0.03) $score_rev_growth+=0.1;
				if(floatval($symbol_formatted['revenue_growth'])>0.05) $score_rev_growth+=0.1; // 0.30+0.05x2=0.40 
				if(floatval($symbol_formatted['revenue_growth'])>0.06) $score_rev_growth+=0.1; // 0.40+0.06x2=0.52 
				if(floatval($symbol_formatted['revenue_growth'])>0.10) $score_rev_growth+=0.1; // 0.50+0.1x2=0.70
																							  // 0.5+0.2x2=0.9 y 0.25 10
            }
            if(array_key_exists('revenue_growth_qq_last_year',$symbol_formatted) && floatval($symbol_formatted['revenue_growth_qq_last_year'])>0){ // && $om_to_ps>0.2
                $score_rev_growth+=min(floatval($symbol_formatted['revenue_growth_qq_last_year']),10)/100;
            }
			if($symbol_formatted['revenue_growth_trend']=="/"){ // the good healthy revenue growth pattern
				$score_rev_growth+=0.1;
				if(end($revenue_acceleration)>0){
					$score_rev_growth+=0.1; // expansive bonus
					$symbol_formatted['revenue_acceleration']=end($revenue_acceleration); // use last acceleration to represent
				}
			}
            $score_rev_growth=max(min($score_rev_growth,1),0);  // min 0 max 1

            $score_epsp=0;
            $epsp=floatval($symbol_formatted['epsp']);
            $prod=floatval($symbol_formatted['prod']);
            if($prod>=0){
                // DEPRECATED: the distribution of positive cases goes from 0 to 10%
                // DEPRECATED: we decided that 6.67% (per=15) is good enough to get 100% score
				// avg is aroun 0.07, highest is around 0.20, multiplying by 5 would make it 1
                //$score_epsp=($prod)*5; // 7*5=35, 20*5=100
				// 7 should be at least 5
                $score_epsp=($prod)*7; // 7*7=49, 14*7=98
                if($computable_yield>($epsp+0.006)) $score_epsp-=($epsp-$computable_yield)*15; // penalized if yield > $epsp
                /*if(array_key_exists('eps_hist_trend',$symbol_formatted)){
                    if($symbol_formatted['eps_hist_trend']=='/-') $score_epsp+=0.05; 
                    if($symbol_formatted['eps_hist_trend']=='_/') $score_epsp+=0.08; 
                    if($symbol_formatted['eps_hist_trend']=='/') $score_epsp+=0.10;
                }*/
                if(array_key_exists('prod_ps_trend',$symbol_formatted)){
                    if($symbol_formatted['prod_ps_trend']=='v') $score_epsp+=0.05; 
                    if($symbol_formatted['prod_ps_trend']=='_/') $score_epsp+=0.10; 
                    if($symbol_formatted['prod_ps_trend']=='/') $score_epsp+=0.20;
                }
                $score_epsp=max(min($score_epsp,1),0);  // min 0 max 1
            }

            $score_leverage=0;
            $acceptable_leverage=2; // (used to be 2.5) 2 would be liabilities==equity i.e., liabilities/assets=0.5 perfect balance
			$debt_ralenization_ratio=4;
            $leverage_industry_ratio=99;
            if(array_key_exists('leverage',$symbol_formatted) && floatval($symbol_formatted['leverage'])!=0){
                // Most industries have 3 auto, teleco, energy
                // tech has 2
                // 2.5 is a good compromise
                if(array_key_exists('leverage_industry',$symbol_formatted) && floatval($symbol_formatted['leverage_industry'])!=0 && floatval($symbol_formatted['leverage_industry'])!=0.01){
                    $acceptable_leverage=min(max(floatval($symbol_formatted['leverage_industry']),$acceptable_leverage),3);
                }
                if(in_array($symbol_formatted['name'], $bank_insurance_companies)){ 
                    $acceptable_leverage=9; // finance/insurance industry lives on this so we cannot penalize as much
					$debt_ralenization_ratio=40; // like only pay 5% of debt in 5y
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
			if(floatval($symbol_formatted['revenue_growth_qq_last_year'])< 0){
				$negative_rev_growth_penalty+=max(-0.1, floatval($symbol_formatted['revenue_growth_qq_last_year'])/100);
			}
			if(floatval($symbol_formatted['revenue_growth'])< 0){ // note if last_year < avg, then last_year is used!! (see above)
				$negative_rev_growth_penalty+=floatval($symbol_formatted['revenue_growth'])*3;  // if -0.33 already max penalty
			}
			if($symbol_formatted['revenue_growth_trend']!="/" && $symbol_formatted['revenue_growth_trend']!="_/" && $symbol_formatted['revenue_growth_trend']!="v" ){
				$negative_rev_growth_penalty+=-0.1;
			}
			if($symbol_formatted['revenue_growth_trend']=="\\" || $symbol_formatted['revenue_growth_trend']=="-\\"){
				$negative_rev_growth_penalty+=-0.1;
			}
            $negative_rev_growth_penalty=max(min($negative_rev_growth_penalty,0),-1);  // min 0 max 1

            $negative_eps_growth_penalty=0.0;
            /*if($epsp<0.03 && array_key_exists('eps_hist_trend',$symbol_formatted)){
                if($symbol_formatted['eps_hist_trend']=='\\') $negative_eps_growth_penalty=-0.5;
                if($symbol_formatted['eps_hist_trend']=='-\\') $negative_eps_growth_penalty=-0.25;
                if($symbol_formatted['eps_hist_trend']=='\_') $negative_eps_growth_penalty=-0.15;
                if($symbol_formatted['eps_hist_trend']=='^') $negative_eps_growth_penalty=-0.25;
            }*/
            if(array_key_exists('prod_ps_trend',$symbol_formatted)){
                if($symbol_formatted['prod_ps_trend']=='\\') $negative_eps_growth_penalty=-0.5;
                if($symbol_formatted['prod_ps_trend']=='-\\') $negative_eps_growth_penalty=-0.25;
                if($symbol_formatted['prod_ps_trend']=='\_') $negative_eps_growth_penalty=-0.15;
                if($symbol_formatted['prod_ps_trend']=='^') $negative_eps_growth_penalty=-0.25;
            }
            if($epsp<-0.015 || floatval($symbol_formatted['operating_margin'])<-0.015){
                $negative_eps_growth_penalty=-0.5;
            }

            $symbol_formatted['om_to_ps']="".toFixed($om_to_ps,2,"setting om_to_ps in symbol_formatted");
            $symbol_formatted['computable_val_growth']="".toFixed($computable_val_growth,2,"setting computable_val_growth in symbol_formatted");
            

            $calc_value_sell_share_raw=((floatval($revenue)/max(0.0001,floatval($symbol_formatted['shares']))));
            $calc_value_sell_share=($calc_value_sell_share_raw*min((floatval($symbol_formatted['operating_margin'])*100)/33,1));
            $calc_value_asset_share=floatval($symbol_formatted['value'])/max(0.0001,floatval($symbol_formatted['price_to_book']));
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
                                  +min($calc_value_asset_share,
			                            floatval($symbol_formatted['value'])/2)
                                  ),1,"calc_value guessed_value");
								  
			$tmp_base=5*compound_interest_4($prod_ps_guess,  // we should calculate the pot om and prodps...
										min(
											floatval($symbol_formatted['revenue_growth'])
											+
											max(-0.1,min(0.1,floatval($symbol_formatted['revenue_acceleration'])/2))
											, 0.60
										)
									,5)                                
									+min($calc_value_asset_share,
			                            floatval($symbol_formatted['value'])/2);
            $symbol_formatted['guessed_value_5y']="".toFixed(
									max(floatval($symbol_formatted['value'])/100, 
									$tmp_base
									// remove some debt issue (liabilities/4 means pay 25% in 5y if we had to return in 20y how much pay in 5y)
									// for banks do it 20 like pay 5% in 5y
									-
									min($liabilities_ps/$debt_ralenization_ratio,$tmp_base/2))
                                  ,1,"calc_value guessed_value_5y");
			if(intval(substr(end($symbol_formatted['revenue_hist'])[0],0,4))<(intval(date("Y"))-1) && intval(date("n"))>4){
				$symbol_formatted['guessed_percentage']=1; // with lack of data just neutral valuation...
			}else{
				$symbol_formatted['guessed_percentage']=floatval(toFixed(
															(
															floatval($symbol_formatted['value'])  // cannot be <0
															/
															max(
																floatval($symbol_formatted['guessed_value_5y'])
																,0.001) // avoid 0 or negative divission this small val will make overvalued look OVERVALUED
															)
															,2,"guessed_percentage"));
			}
			
			// risk (eqp) warren buffet
			$score_eqp=1;
			// buffet pb=1.5 -> 0.66, avg pb=4 --> 0.20
			if($symbol_formatted['eqp']<=0.66){$score_eqp=0.9;}  // buffet acceptance
			if($symbol_formatted['eqp']<=0.5){$score_eqp=0.8;}
			if($symbol_formatted['eqp']<=0.4){$score_eqp=0.7;}
			if($symbol_formatted['eqp']<=0.3){$score_eqp=0.6;}
			if($symbol_formatted['eqp']<=0.2){$score_eqp=0.5;}  // average
			if($symbol_formatted['eqp']<=0.15){$score_eqp=0.3;}
			if($symbol_formatted['eqp']<=0.10){$score_eqp=0;}

            $symbol_formatted['h_souce']=
                                                 ($score_eqp*1)+
                                                 ($score_val_growth*2)+
                                                 ($score_rev_growth*2)+
                                                 ($score_epsp*2)+
                                                 ($score_leverage*1)+
                                                 ($negative_val_growth_penalty*2)+
                                                 ($negative_rev_growth_penalty*2)+
                                                 ($negative_eps_growth_penalty*2)
                                                 ;
            
			// TODO: improve this
			if($symbol_formatted['guessed_percentage']<1.1){
				$symbol_formatted['h_souce']+=0.25;
			}
			if($symbol_formatted['guessed_percentage']<0.9){
				$symbol_formatted['h_souce']+=0.25;
			}
			if($symbol_formatted['guessed_percentage']<0.8){
				$symbol_formatted['h_souce']+=0.5;
			}
			if($symbol_formatted['guessed_percentage']<0.7){
				$symbol_formatted['h_souce']+=0.5;
			}
			if($symbol_formatted['guessed_percentage']>1.7){
				$symbol_formatted['h_souce']+=-0.5;
			}
			if($symbol_formatted['guessed_percentage']>2.7){
				$symbol_formatted['h_souce']+=-0.5; 
			}
			if($symbol_formatted['guessed_percentage']>3.7){
				$symbol_formatted['h_souce']+=-0.5; 
			}
			if(!in_array($symbol_formatted['name'], $bank_insurance_companies) && floatval($symbol_formatted['current_ratio'])<1){
				$symbol_formatted['h_souce']-=(1-floatval($symbol_formatted['current_ratio']));
			}

			$symbol_formatted['h_souce']="".toFixed($symbol_formatted['h_souce'],1,"h_souce div2");
			
            if(floatval($symbol_formatted['h_souce'])<1){echo " h_souce=".$symbol_formatted['h_souce'];}
            //hist_year_last_day('h_souce',$symbol_formatted); // yearly  TODO add when we close the app (slow, yearly)
        }
        $stocks_formatted_arr[$json_name.':'.$symbol_formatted['market']]=$symbol_formatted;
		// update firebase for that symbol
		
		// symbol formatted extra
		$symbol_formatted_extra=$symbol_formatted;
		$symbol_formatted_extra["score_val_g"]=$score_val_growth;
		$symbol_formatted_extra["score_rev_g"]=$score_rev_growth;
		$symbol_formatted_extra["score_epsp"]=$score_epsp;
		$symbol_formatted_extra["score_lev"]=$score_leverage;
		$symbol_formatted_extra["score_tmp_base"]=$tmp_base;
		$symbol_formatted_extra["score_liabilities_ps"]=$liabilities_ps;
		
		
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $FIREBASE .'stocks_formatted/'. $json_name.':'.$symbol_formatted['market'].'.json' ); ///'.$usr.'_'.$symbol.'
		curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "PUT" );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode($symbol_formatted_extra) );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $curl );
		curl_close( $curl );
		
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

// 1st time just upload the json, put the stocks.formatted.json in firebase
// then, normal only update the updated stocks and GOOG (completely) since there is the usdeur info
$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $FIREBASE . 'stocks_formatted/GOOG:NASDAQ.json' ); ///'.$usr.'_'.$symbol.'
curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "PUT" );
curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode($stocks_formatted_arr['GOOG:NASDAQ']) );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl );
curl_close( $curl );





fwrite($stock_cron_log, date('Y-m-d H:i:s')." done with stock_cron.php\n");
echo "<br />".date('Y-m-d H:i:s')." done with stock_cron.php, see stock_cron.log<br />";
fclose($stock_cron_log);

?>