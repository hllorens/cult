<?php

require_once("email_config.php");
require_once("stock_list.php");
require_once 'stock_helper_functions.php';

$stock_financials_old=array(); // to store stocks_financialsr, typo "financials"
if(file_exists ( 'stocks_financialsr.json' )){
	$stock_financials_old = json_decode(file_get_contents('stocks_financialsa.json'), true);
}else{
	echo "stocks_financialsr.json does NOT exist -> using an empty array<br />";
}

function normalize_period($period){
	$period_arr_arr=explode("-",$period);
	// normalize period
	$day=str_pad($period_arr_arr[2],2,"0",STR_PAD_LEFT);
	$month=str_pad($period_arr_arr[1],2,"0",STR_PAD_LEFT);
	$year=$period_arr_arr[0];
	if($month=="01" || $month=="02" || ($month=="03" && $day[0]!="3" && $day[0]!="2")){
		$month="12";
		$year="".(intval($year)-1);
	}
	if($month=="04" || $month=="05" || ($month=="06" && $day[0]!="3" && $day[0]!="2")){
		$month="03";
	}
	if($month=="07" || $month=="08" || ($month=="09" && $day[0]!="3" && $day[0]!="2")){
		$month="06";
	}
	if($month=="10" || $month=="11" || ($month=="12"  && $day[0]!="3" && $day[0]!="2")){
		$month="09";
	}
	$period=$year."-".$month."-31";
	return $period;
}


$stocks=array_keys($stock_financials_old);
for($i=0;$i<count($stocks);$i++){
	$periods=array_keys($stock_financials_old[$stocks[$i]]);
	for($j=0;$j<count($periods);$j++){
		if($periods[$j][0]=="2"){
			$new_period=normalize_period($periods[$j]);
			if($new_period!=$periods[$j]){
				echo "normalizing ".$periods[$j]." to ".$new_period."<br />";
				$stock_financials_old[$stocks[$i]][$new_period]=$stock_financials_old[$stocks[$i]][$periods[$j]];
				unset($stock_financials_old[$stocks[$i]][$periods[$j]]);
			}
		}
	}
	ksort($stock_financials_old[$stocks[$i]]);
}

$str=json_encode( $stock_financials_old );
$stocks_financials_json_file = fopen("stocks_financialsa_full.json", "w") or die("Unable to open file stocks_financialsr_full.json!");
fwrite($stocks_financials_json_file, $str);
fclose($stocks_financials_json_file);





?>