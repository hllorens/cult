<?php

require_once 'stock_helper_functions.php';


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
    $tsv="shares	".$stock_data['shares']."\n";
	
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
	
	echo "<br />--------------------------<br />";
	$val_g=hist_growth_array('value_hist',$stock_data);
	$val_a=acceleration_array($val_g);
	$i=0;
    foreach ($stock_data['value_hist'] as $valdata){
		if(array_key_exists(substr($valdata[0],0,4),$tsv_arr)){echo "ERROR duplicated year in value_hist ".substr($valdata[0],0,4)."<br />"; exit(1);}
		if(substr($valdata[0],0,4)==date("Y") || floatval(substr($valdata[0],0,4))>floatval($last_rev_year) || floatval(substr($valdata[0],0,4))<=2012){$i++;continue;}
		$tsv_arr[substr($valdata[0],0,4)]=substr($valdata[0],0,4)."	".$valdata[1];
		if($i==0){
			$tsv_arr[substr($valdata[0],0,4)].="	0";
			$tsv_arr[substr($valdata[0],0,4)].="	0";
		}else if ($i==1){
			$tsv_arr[substr($valdata[0],0,4)].="	".$val_g[($i-1)];
			$tsv_arr[substr($valdata[0],0,4)].="	0";
		}else{
			$tsv_arr[substr($valdata[0],0,4)].="	".$val_g[($i-1)];
			$tsv_arr[substr($valdata[0],0,4)].="	".$val_a[($i-2)];
		}
		$i++;
    }
	if(array_key_exists('revenue_hist',$stock_data)){
		$i=0;
		$val_g=hist_growth_array('revenue_hist',$stock_data);
		$val_a=acceleration_array($val_g);
		$ga=avg_weighted($val_g);
		$aa=avg_weighted($val_a);
		$tsv.="avg rev g/a	".$ga."	".$aa;
		$seen_years=array();
		foreach ($stock_data['revenue_hist'] as $valdata){
			//echo $valdata[0]."<br />";
			if(array_key_exists(substr($valdata[0],0,4),$seen_years)){echo "ERROR duplicated year in revenue_hist ".substr($valdata[0],0,4)."<br />"; exit(1);}
			$seen_years[substr($valdata[0],0,4)]=true;
			if(array_key_exists(substr($valdata[0],0,4),$tsv_arr)){
				if($i==0)
					$tsv_arr[substr($valdata[0],0,4)].="	".$valdata[1]."	0	0";
				else if ($i==1)
					$tsv_arr[substr($valdata[0],0,4)].="	".$valdata[1]."	".$val_g[($i-1)]."	0";
				else
					$tsv_arr[substr($valdata[0],0,4)].="	".$valdata[1]."	".$val_g[($i-1)]."	".$val_a[($i-2)];
			}else{
				echo "ignoring revenue year because no value  ".substr($valdata[0],0,4)."<br />";
			}
			$i++;
		}
	}else{
		echo "<br />ERR: no revenue<br />"; exit(1);
	}
	if(array_key_exists('operating_income_hist',$stock_data)){
		$i=0;
		$val_g=hist_growth_array('operating_income_hist',$stock_data);
		$val_a=acceleration_array($val_g);
		$ga=avg_weighted($val_g);
		$aa=avg_weighted($val_a);
		$tsv.="\navg oi g/a	".$ga."	".$aa;
		$seen_years=array();
		foreach ($stock_data['operating_income_hist'] as $valdata){
			if(array_key_exists(substr($valdata[0],0,4),$seen_years)){echo "ERROR duplicated year in operating_income_hist ".substr($valdata[0],0,4)."<br />"; exit(1);}
			$seen_years[substr($valdata[0],0,4)]=true;
			if(array_key_exists(substr($valdata[0],0,4),$tsv_arr)){
				if($i==0)
					$tsv_arr[substr($valdata[0],0,4)].="	".$valdata[1]."	0	0";
				else if ($i==1)
					$tsv_arr[substr($valdata[0],0,4)].="	".$valdata[1]."	".$val_g[($i-1)]."	0";
				else
					$tsv_arr[substr($valdata[0],0,4)].="	".$valdata[1]."	".$val_g[($i-1)]."	".$val_a[($i-2)];
			}else{
				echo "ignoring op inc year because no value  ".substr($valdata[0],0,4)."<br />";
			}
			$i++;
		}
		
	}else{
		echo "<br />ERR: no op inc<br />"; exit(1);
	}
	if(array_key_exists('net_income_hist',$stock_data)){
		$i=0;
		$val_g=hist_growth_array('net_income_hist',$stock_data);
		$val_a=acceleration_array($val_g);
		$ga=avg_weighted($val_g);
		$aa=avg_weighted($val_a);
		$tsv.="\navg ni g/a	".$ga."	".$aa;
		$seen_years=array();
		foreach ($stock_data['net_income_hist'] as $valdata){
			if(array_key_exists(substr($valdata[0],0,4),$seen_years)){echo "ERROR duplicated year in net_income_hist ".substr($valdata[0],0,4)."<br />"; exit(1);}
			$seen_years[substr($valdata[0],0,4)]=true;
			if(array_key_exists(substr($valdata[0],0,4),$tsv_arr)){
				if($i==0)
					$tsv_arr[substr($valdata[0],0,4)].="	".$valdata[1]."	0	0";
				else if ($i==1)
					$tsv_arr[substr($valdata[0],0,4)].="	".$valdata[1]."	".$val_g[($i-1)]."	0";
				else
					$tsv_arr[substr($valdata[0],0,4)].="	".$valdata[1]."	".$val_g[($i-1)]."	".$val_a[($i-2)];
			}else{
				echo "ignoring op inc year because no value  ".substr($valdata[0],0,4)."<br />";
			}
			$i++;
		}
		
	}else{
		echo "<br />ERR: no net inc<br />"; exit(1);
	}
	if(array_key_exists('equity_hist',$stock_data)){
		$i=0;
		$val_g=hist_growth_array('equity_hist',$stock_data);
		$val_a=acceleration_array($val_g);
		$ga=avg_weighted($val_g);
		$aa=avg_weighted($val_a);
		$tsv.="\navg eq g/a	".$ga."	".$aa;
		$seen_years=array();
		foreach ($stock_data['equity_hist'] as $valdata){
			if(array_key_exists(substr($valdata[0],0,4),$seen_years)){echo "ERROR duplicated year in equity_hist ".substr($valdata[0],0,4)."<br />"; exit(1);}
			$seen_years[substr($valdata[0],0,4)]=true;
			if(array_key_exists(substr($valdata[0],0,4),$tsv_arr)){
				$tsv_arr[substr($valdata[0],0,4)].="	".$valdata[1];
				if($i==0){
					$tsv_arr[substr($valdata[0],0,4)].="	0";
					$tsv_arr[substr($valdata[0],0,4)].="	0";
				}else if ($i==1){
					$tsv_arr[substr($valdata[0],0,4)].="	".$val_g[($i-1)];
					$tsv_arr[substr($valdata[0],0,4)].="	0";
				}else{
					$tsv_arr[substr($valdata[0],0,4)].="	".$val_g[($i-1)];
					$tsv_arr[substr($valdata[0],0,4)].="	".$val_a[($i-2)];
				}
			}else{
				echo "ignoring op inc year because no value  ".substr($valdata[0],0,4)."<br />";
			}
			$i++;
		}
		
	}else{
		echo "<br />ERR: no equity_hist<br />"; exit(1);
	}

    $tsv.="\nperiod	value	g	a	revenue	g	a	op_inc	g	a	net_inc	g	a	eq	g	a\n";
	foreach ($tsv_arr as $tsv_line){
		$tsv.=$tsv_line."\n";
	}
	$tsv.="\n\n\n	last	avg	";
	$tsv.="\npb	".$stock_data['price_to_book'];
	$tsv.="\neps	"."to be calc net inc/shares"."	".$stock_data['eps'];
	

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