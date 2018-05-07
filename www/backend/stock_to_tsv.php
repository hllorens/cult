<?php

require_once 'stock_helper_functions.php';


function get_anualized_data($param,$stock_data,&$tsv_arr){
	if(array_key_exists($param.'_hist',$stock_data)){
		$i=0;
		$val_g=hist_growth_array($param.'_hist',$stock_data);
		$val_a=acceleration_array($val_g);
		$tsv_arr[$param[0].'gl']=end($val_g);
		$tsv_arr[$param[0].'ga']=avg_weighted($val_g);
		$tsv_arr[$param[0].'aa']=avg_weighted($val_a);
		$seen_years=array();
		foreach ($stock_data[$param.'_hist'] as $valdata){
			//echo $valdata[0]."<br />";
			if(array_key_exists(substr($valdata[0],0,4),$seen_years)){echo "ERROR duplicated year in $param_hist ".substr($valdata[0],0,4)."<br />"; exit(1);}
			$seen_years[substr($valdata[0],0,4)]=true;
			$tsv_arr[substr($valdata[0],0,4)][$param]=$valdata[1];
			$tsv_arr[substr($valdata[0],0,4)][$param.'_ps']=floatval(toFixed(floatval($valdata[1])/floatval($stock_data['shares']),2));
			$tsv_arr[substr($valdata[0],0,4)][$param.'_psp']=toFixed($tsv_arr[substr($valdata[0],0,4)][$param.'_ps']/floatval($tsv_arr[substr($valdata[0],0,4)]['value']),2);
			if(array_key_exists(substr($valdata[0],0,4),$tsv_arr)){
				if($i==0){
					$tsv_arr[substr($valdata[0],0,4)][$param.'_g']=0;
					$tsv_arr[substr($valdata[0],0,4)][$param.'_a']=0;
				}else if ($i==1){
					$tsv_arr[substr($valdata[0],0,4)][$param.'_g']=$val_g[($i-1)];
					$tsv_arr[substr($valdata[0],0,4)][$param.'_a']=0;
				}else{
					$tsv_arr[substr($valdata[0],0,4)][$param.'_g']=$val_g[($i-1)];
					$tsv_arr[substr($valdata[0],0,4)][$param.'_a']=$val_a[($i-2)];
				}
			}else{
				echo "ERROR: in $param, year ".substr($valdata[0],0,4)." is not in tsv_arr<br />"; exit(1);
			}
			$i++;
		}
	}else{
		echo "<br />ERR: no $param in stock_data<br />";
		exit(1);
	}
}


function get_tsv($symbol,$debug=false){
    $stocks_formatted=array();
    $stock_data=array();

	$tsv_arr=array();
    if(!isset($symbol)){echo "ERROR: empty sybmol";exit(1);}
    if(file_exists ( 'stocks.formatted.json' )){
        $stocks_formatted = json_decode(file_get_contents('stocks.formatted.json'), true);
    }else{
        echo "stocks.formatted.json does NOT exist<br />";
        exit(1);
    }

    if(substr($symbol,0,5)=="INDEX"){echo "<br />index (no data), nothing to be done<br />"; return;} // skip indexes

    if(!array_key_exists($symbol,$stocks_formatted) || !array_key_exists('value_hist',$stocks_formatted[$symbol])){
        echo "Not found data for ".$symbol;
        exit(1);
    }else{
        $stock_data=$stocks_formatted[$symbol];
    }
	
	$last_year=intval(date("Y"))-1;
	$last_rev_year=substr(end($stock_data['revenue_hist'])[0],0,4);
	$last_oi_year=substr(end($stock_data['operating_income_hist'])[0],0,4);
	$last_ni_year=substr(end($stock_data['net_income_hist'])[0],0,4);
	$last_eq_year=substr(end($stock_data['equity_hist'])[0],0,4);
	$first_rev_year=substr($stock_data['revenue_hist'][0][0],0,4);
	$first_oi_year=substr($stock_data['operating_income_hist'][0][0],0,4);
	$first_ni_year=substr($stock_data['net_income_hist'][0][0],0,4);
	$first_eq_year=substr($stock_data['equity_hist'][0][0],0,4);

	if($last_rev_year!=$last_oi_year || $last_rev_year!=$last_ni_year || $last_rev_year!=$last_eq_year){
		echo "ERROR: Last years not aligned manually add data<br />rev:$last_rev_year oi:$last_oi_year ni:$last_ni_year eq:$last_eq_year <br />";
	}
	if($first_rev_year!=$first_oi_year || $first_rev_year!=$first_ni_year || $first_rev_year!=$first_eq_year){
		echo "ERROR: first years not aligned manually add data<br />rev:$first_rev_year oi:$first_oi_year ni:$first_ni_year eq:$first_eq_year <br />";
	}
	
	if($last_rev_year!=$last_year){
		echo "WARNING: data might be outdated $last_rev_year vs $last_year<br />";
	}
	
	echo "TODO: create a function to validate/check stocks.formatted.json to see that there are no missing years or empty values, if they are, we have to manually fill them or get rid of the stock in the list";
	echo "<br />--------------------------<br />";
	$val_g=hist_growth_array('value_hist',$stock_data);
	$val_a=acceleration_array($val_g);
	$i=0;
    foreach ($stock_data['value_hist'] as $valdata){
		if(array_key_exists(substr($valdata[0],0,4),$tsv_arr)){echo "ERROR duplicated year in value_hist ".substr($valdata[0],0,4)."<br />"; exit(1);}
		if(substr($valdata[0],0,4)==date("Y") || floatval(substr($valdata[0],0,4))>floatval($last_rev_year) || floatval(substr($valdata[0],0,4))<floatval($first_rev_year)){$i++;continue;}
		
		$tsv_arr[substr($valdata[0],0,4)]['year']=substr($valdata[0],0,4);
		$tsv_arr[substr($valdata[0],0,4)]['value']=$valdata[1];
		if($i==0){
			$tsv_arr[substr($valdata[0],0,4)]['value_g']=0;
			$tsv_arr[substr($valdata[0],0,4)]['value_a']=0;
		}else if ($i==1){
			$tsv_arr[substr($valdata[0],0,4)]['value_g']=$val_g[($i-1)];
			$tsv_arr[substr($valdata[0],0,4)]['value_a']=0;
		}else{
			$tsv_arr[substr($valdata[0],0,4)]['value_g']=$val_g[($i-1)];
			$tsv_arr[substr($valdata[0],0,4)]['value_a']=$val_a[($i-2)];
		}
		$i++;
    }
	
	get_anualized_data('revenue',$stock_data,$tsv_arr);
	get_anualized_data('operating_income',$stock_data,$tsv_arr);
	get_anualized_data('net_income',$stock_data,$tsv_arr);
	get_anualized_data('equity',$stock_data,$tsv_arr);

	
	$tsv="shares	".$stock_data['shares']."\n";
	$tsv.="avg rev g/a	".$tsv_arr['rga']."	".$tsv_arr['raa'];
	$tsv.="\navg oi g/a	".$tsv_arr['oga']."	".$tsv_arr['oaa']."	om	".$stock_data['operating_margin'];
	$tsv.="\navg ni g/a	".$tsv_arr['nga']."	".$tsv_arr['naa'];
	$tsv.="\navg eq g/a	".$tsv_arr['ega']."	".$tsv_arr['eaa'];

	$om_max=floatval($stock_data['operating_margin']);
    $tsv.="\nperiod	value	g	a	revenue	g	a	op_inc	g	a	om	oip	net_inc	g	a	nip	eq	g	a\n";
	foreach ($tsv_arr as $key => $value){
		if(strval($key)[0]=="2"){
			//echo "key=$key<br />";
			$tsv.=$value['year'];
			$om=floatval(toFixed(floatval($value['operating_income'])/floatval($value['revenue']),2));
			if($om>$om_max) $om_max=$om;
			$tsv.="	".$value['value']."	".$value['value_g']."	".$value['value_a'];
			$tsv.="	".$value['revenue']."	".$value['revenue_g']."	".$value['revenue_a'];
			$tsv.="	".$value['operating_income']."	".$value['operating_income_g']."	".$value['operating_income_a']."	".$om."	".$value['operating_income_psp'];
			$tsv.="	".$value['net_income']."	".$value['net_income_g']."	".$value['net_income_a']."	".$value['net_income_psp'];
			$tsv.="	".$value['equity']."	".$value['equity_g']."	".$value['equity_a'];
			$tsv.="\n";
		}
	}
	// TUNINGS
	$acceleration_factor=2; // NO NEED TO INFLATE FURTHER
	$max_eq_percent=0.5; // 0.66 was before..., 0.5 is sensible too

	$guess_revenue=floatval(end($stock_data['revenue_hist'])[1])*(1+$tsv_arr['rga']+($acceleration_factor*$tsv_arr['raa']));
	$guess_revenueg=($guess_revenue/floatval(end($stock_data['revenue_hist'])[1])) - 1;
	$guess_oi=floatval(end($stock_data['operating_income_hist'])[1])*(1+$tsv_arr['oga']+($acceleration_factor*$tsv_arr['oaa']));
	$guess_ni=floatval(end($stock_data['net_income_hist'])[1])*(1+$tsv_arr['nga']+($acceleration_factor*$tsv_arr['naa']));
	$guess_nig=($guess_ni/floatval(end($stock_data['net_income_hist'])[1])) - 1;
	$guess_eq=floatval(end($stock_data['equity_hist'])[1])*(1+$tsv_arr['ega']+($acceleration_factor*$tsv_arr['eaa']));
	$guess_eqg=($guess_eq/floatval(end($stock_data['equity_hist'])[1])) - 1;
	$guess_oi=max($guess_oi,$guess_revenue*$om_max,$guess_ni*1.10);  // ALREADY A VERY GOOD GUESS
	$guess_oig=($guess_oi/floatval(end($stock_data['operating_income_hist'])[1])) - 1;
	$tsv.="\ncurrval	".end($stock_data['value_hist'])[1]."		guess	".toFixed($guess_revenue,2)."	".toFixed($guess_revenueg,2)."	".toFixed(($guess_revenueg-$tsv_arr['rgl']),2);
	$tsv.="	".toFixed($guess_oi,2)."	".toFixed($guess_oig,2)."	".toFixed($guess_oig-$tsv_arr['ogl'],2)."	".floatval(toFixed($guess_oi/$guess_revenue,2))."	";
	$tsv.="	".toFixed($guess_ni,2)."	".toFixed($guess_nig,2)."	".toFixed($guess_nig-$tsv_arr['ngl'],2)."	";
	$tsv.="	".toFixed($guess_eq,2)."	".toFixed($guess_eqg,2)."	".toFixed($guess_eqg-$tsv_arr['egl'],2);
	$tsv.="\n";
	
	
	$revp=(floatval(end($stock_data['revenue_hist'])[1])/floatval($stock_data['shares']))/floatval($tsv_arr[$last_rev_year]['value']);
	$oip=(floatval(end($stock_data['operating_income_hist'])[1])/floatval($stock_data['shares']))/floatval($tsv_arr[$last_rev_year]['value']);
	$epsp=(floatval(end($stock_data['net_income_hist'])[1])/floatval($stock_data['shares']))/floatval($tsv_arr[$last_rev_year]['value']);
	$eqp=(floatval(end($stock_data['equity_hist'])[1])/floatval($stock_data['shares']))/floatval($tsv_arr[$last_rev_year]['value']);
	$eqpc=min($eqp,$max_eq_percent);
	$tsv.="\n	revp	oip	epsp	eqp	eqpc	oi+eq\n";
	$tsv.="current	".toFixed($revp,2)."	".toFixed($oip,2)."	".toFixed($epsp,2)."	".toFixed($eqp,2)."	".toFixed($eqpc,2)."	".toFixed($eqpc+$oip,2)."\n";
	$revp=($guess_revenue/floatval($stock_data['shares']))/floatval($tsv_arr[$last_rev_year]['value']);
	$oip=($guess_oi/floatval($stock_data['shares']))/floatval($tsv_arr[$last_rev_year]['value']);
	$epsp=($guess_ni/floatval($stock_data['shares']))/floatval($tsv_arr[$last_rev_year]['value']);
	$eqp=($guess_eq/floatval($stock_data['shares']))/floatval($tsv_arr[$last_rev_year]['value']);
	$eqpc=min($eqp,$max_eq_percent);
	$tsv.="guessG	".toFixed($revp,2)."	".toFixed($oip,2)."	".toFixed($epsp,2)."	".toFixed($eqp,2)."	".toFixed($eqpc,2)."	".toFixed($eqpc+$oip,2)."\n";

	$tsv.="\n	eqc max	$max_eq_percent%\n";
	$revps=(floatval(end($stock_data['revenue_hist'])[1])/floatval($stock_data['shares']));
	$oips=(floatval(end($stock_data['operating_income_hist'])[1])/floatval($stock_data['shares']));
	$epsps=(floatval(end($stock_data['net_income_hist'])[1])/floatval($stock_data['shares']));
	$eqps=(floatval(end($stock_data['equity_hist'])[1])/floatval($stock_data['shares']));
	$eqpsc=min($eqps,$max_eq_percent*floatval($tsv_arr[$last_rev_year]['value']));
	$tsv.="\n	revps	oips	epsps	eqps	eqpsc	oi+eq\n";
	$tsv.="current	".toFixed($revps,2)."	".toFixed($oips,2)."	".toFixed($epsps,2)."	".toFixed($eqps,2)."	".toFixed($eqpsc,2)."	".toFixed($eqpsc+$oips,2)."\n";
	$revps=($guess_revenue/floatval($stock_data['shares']));
	$oips=($guess_oi/floatval($stock_data['shares']));
	$epsps=($guess_ni/floatval($stock_data['shares']));
	$eqps=($guess_eq/floatval($stock_data['shares']));
	$eqpsc=min($eqps,$max_eq_percent*floatval($tsv_arr[$last_rev_year]['value']));
	$tsv.="guessG	".toFixed($revps,2)."	".toFixed($oips,2)."	".toFixed($epsps,2)."	".toFixed($eqps,2)."	".toFixed($eqpsc,2)."	".toFixed($eqpsc+$oips,2)."\n";
	
	#$revps=(floatval(end($stock_data['revenue_hist'])[1])/floatval($stock_data['shares']));
	#$oips=(floatval(end($stock_data['operating_income_hist'])[1])/floatval($stock_data['shares']));
	#$epsps=(floatval(end($stock_data['net_income_hist'])[1])/floatval($stock_data['shares']));
	$tsv.="\nom_max	".$om_max;
	$prod=$oips; // $guess_oi alredy uses the max ---> max($revps*$om_max,$oips,$epsps*1.10);
	$tsv.="\nPROD/y	max(revps*om_max,oips,nips*1.1)";
	$tsv.="\n	prod/y	revg	reva	eq+1y";
	$tsv.="\n	".toFixed($prod,2)."	".$tsv_arr['rga']."	".$tsv_arr['raa']."	".toFixed($eqpsc,2);
	$tsv.="\n";
	$tsv.="\n1y	1*".compound_interest_4($prod,floatval($tsv_arr['rga']),1)."	=".compound_interest_4($prod,floatval($tsv_arr['rga']),1)."	".toFixed( $eqpsc,2);
	$tsv.="\n3y	3*".compound_interest_4($prod,floatval($tsv_arr['rga']),3)."	=".(3*compound_interest_4($prod,floatval($tsv_arr['rga']),3))."	".toFixed( $eqpsc,2);
	$tsv.="\n5y	5*".compound_interest_4($prod,floatval($tsv_arr['rga']),5)."	=".(5*compound_interest_4($prod,floatval($tsv_arr['rga']),5))."	".toFixed( $eqpsc,2);
	$tsv.="\n10y	10*".compound_interest_4($prod,floatval($tsv_arr['rga']),10)."	=".(10*compound_interest_4($prod,floatval($tsv_arr['rga']),10))."	".toFixed( $eqpsc,2);


	$tsv.="\nBased on rev with 3*om, like if managing a lot of money gave you advantage";
	$prod=max($revps*max(min($om_max*3,0.30),$om_max),$oips,$epsps*1.10); // max optimistic om 30% unless om_max is higher
	// assuming 20%om is just too optimistic for big revenue companies...
	// it is true that for some reason the more you sell the more expensive the share is... 
	$tsv.="\nPROD/y	max(revps*om_max,oips,nips*1.1)";
	$tsv.="\n	prod/y	revg	reva	eq+1y";
	$tsv.="\n	".toFixed($prod,2)."	".$tsv_arr['rga']."	".$tsv_arr['raa']."	".toFixed($eqpsc,2);
	$tsv.="\n";
	$tsv.="\n1y	1*".compound_interest_4($prod,floatval($tsv_arr['rga']),1)."	=".compound_interest_4($prod,floatval($tsv_arr['rga']),1)."	".toFixed( $eqpsc,2);
	$tsv.="\n3y	3*".compound_interest_4($prod,floatval($tsv_arr['rga']),3)."	=".(3*compound_interest_4($prod,floatval($tsv_arr['rga']),3))."	".toFixed( $eqpsc,2);
	$tsv.="\n5y	5*".compound_interest_4($prod,floatval($tsv_arr['rga']),5)."	=".(5*compound_interest_4($prod,floatval($tsv_arr['rga']),5))."	".toFixed( $eqpsc,2);
	$tsv.="\n10y	10*".compound_interest_4($prod,floatval($tsv_arr['rga']),10)."	=".(10*compound_interest_4($prod,floatval($tsv_arr['rga']),10))."	".toFixed( $eqpsc,2);


	
	$last_value=floatval($stock_data['value_hist'][count($stock_data['value_hist'])-2][1]);
	$last_ni=floatval($stock_data['net_income_hist'][count($stock_data['net_income_hist'])-1][1]);
	$last_eq=floatval($stock_data['equity_hist'][count($stock_data['equity_hist'])-1][1]);
	$tsv.="\n\n\n	last	avg	";
	$tsv.="\npb	".$stock_data['price_to_book'];
	$tsv.="\nb(p/pb)	".toFixed(floatval(end($stock_data['value_hist'])[1])/floatval($stock_data['price_to_book']),2)."	".toFixed($last_value/floatval($stock_data['price_to_book']),2)."	with last year price";
	$tsv.="\nb(eq/s)	".toFixed($last_eq/floatval($stock_data['shares']),2);
	$last_eps=floatval(toFixed($last_ni/floatval($stock_data['shares']),2));
	$tsv.="\neps	".$last_eps."	".$stock_data['eps'];
	$tsv.="\nepsp	".toFixed($last_eps/$last_value,3)."	".$stock_data['epsp'];

    return $tsv;
}



if( isset($_REQUEST['symbol']) ){
    $debug=false;
    if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
        $debug=true;
    }
    echo "symbol found, manual mode<br /><br />";
    $tsv=get_tsv($_REQUEST['symbol'],$debug);
    echo("<pre>$tsv</pre>");
}else{
    echo "symbol GET parm needed<br /><br />";	
}


?>