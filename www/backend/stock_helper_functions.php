<?php

require_once("email_config.php");


//function print_r_pretty_html($arr){
    // BETTER USE <pre></pre> with either print_r(xxx,true) or json_encode($data, JSON_PRETTY_PRINT) perfect for json 


function toFixed($number, $decimals=2, $tracking="tracking unset") {
  if(!is_numeric($number)){
        echo "not numeric: $number ($tracking)";
        $number=0; 
  } 
  return number_format($number, $decimals, ".", "");
}

function match_year_in_hist($date,$arr){
    $year=substr($date,0,4);
    foreach ($arr as $elem){
        $year_hist=substr($elem[0],0,4);
        if($year==$year_hist){
            return $elem;
        }
    }
    return null;
}

// freq can be 3 for quarters or 6 for halves, more sophisticated do it manually
function hist($param_id,$freq, &$symbol_object, $max_elems_to_avg=8, $max_avg="no", $min_avg="no"){
    $timestamp_date=date("Y-m-d"); // refresh date
    $timestamp_freq=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / $freq) % (12/$freq)) + 1 );
    if(!array_key_exists($param_id,$symbol_object) || $symbol_object[$param_id]==null || $symbol_object[$param_id]=="" || $symbol_object[$param_id]=="-"){
        if($symbol_object[$param_id]!=0){ // non-string issue
            send_mail('ERROR:'.$param_id.' !exist or empty','<br />hist: For '.$symbol_object['name'].' '.implode(" ",array_keys($symbol_object)).'<br /><br />',"hectorlm1983@gmail.com");
            die('In hist() the param_id ('.$param_id.') does not exist or empty or -, For '.$symbol_object['name']);
        }else{
            $symbol_object[$param_id]="0";
        }
    }
    if(!array_key_exists($param_id.'_hist',$symbol_object)){$symbol_object[$param_id.'_hist']=array();}
    if(!array_key_exists($param_id.'_hist_last_diff',$symbol_object)){$symbol_object[$param_id.'_hist_last_diff']=0;}
    
    if(!array_key_exists($param_id.'_hist',$symbol_object) || count($symbol_object[$param_id.'_hist'])==0){
        $symbol_object[$param_id.'_hist'][]=[$timestamp_date,$symbol_object[$param_id]];
    }else{
        $last_elem=end($symbol_object[$param_id.'_hist'])[1];
        $last_elem_date=end($symbol_object[$param_id.'_hist'])[0];
        $last_elem_freq=substr($last_elem_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $last_elem_date)->format('n') / $freq) % (12/$freq)) + 1 );
        //echo "$last_elem_date half $last_elem_freq current half $timestamp_freq<br />";
        if($timestamp_freq!=$last_elem_freq){
            $symbol_object[$param_id.'_hist'][]=[$timestamp_date,$symbol_object[$param_id]];
        }else{ // to keep it fresh
            $symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist']) - 1]=[$timestamp_date,$symbol_object[$param_id]];
        }
    }
    // avg will be the last by default
    $symbol_object['avg'.$param_id]="".toFixed($symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist'])-1][1],2,'helper avg'.$param_id);
    if(count($symbol_object[$param_id.'_hist'])>1){
        // The symbol (+ or -) is not an issue if we use abs in divisor and only the numerator counts
        // A small or 0 divisor is an issue if we do not do translation or min movement
        // e.g., hist 1 then 2 is a clear +100% (2x), hist -1 then 1 would be 200%
        // but -5 then 5 would be 10/5 200% and -1 then 5 would be 6/1 600%
        // the case would be even more problematic if we go below 1 -0.5 then 5 would be 5.5/0.5=+1100%
        // the diff has a minimum divisor of 0.5 so value is at most amplified 2x in case of small values (e.g., EPS)
        // other alternatives already tested and complicated and not better (e.g., trying some translation or symbol change)
        // diff -6 to -3 is +50% while 3 to 6 is +200% but I think that is acceptable
        $value_hist_last_diff=((floatval(end($symbol_object[$param_id.'_hist'])[1])-floatval($symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist'])-2][1]))/max(0.5,abs(floatval($symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist'])-2][1]))));
        $symbol_object[$param_id.'_hist_last_diff']=toFixed($value_hist_last_diff*100,0,'helper '.$param_id.'_hist_last_diff'); 
        // avgelems is an average of max $max_elems_to_avg last values, with max value of 6% using min (so odd macro dividends do not trick the avg so much)
        $num_hist_values=count($symbol_object[$param_id.'_hist']);
        $num_values_to_average=min($num_hist_values,$max_elems_to_avg);
        $avgelems=0.0;
        for ($x = 1; $x <= $num_values_to_average; $x++) {
            //echo "$x $avgelems $num_hist_values $num_values_to_average  - ";
            $val2avg=floatval($symbol_object[$param_id.'_hist'][($num_values_to_average-$x)][1]);
            if($max_avg!="no"){ $val2avg=min($val2avg,floatval($max_avg));}
            if($min_avg!="no"){ $val2avg=max($val2avg,floatval($min_avg));}
            $avgelems+=$val2avg/floatval($num_values_to_average);
        }
        $symbol_object['avg'.$param_id]="".toFixed($avgelems,2,'helper avg'.$param_id.' with hist>1');
        if($num_hist_values>2){
            // 5 possibilities no-strong-trend down-down down-up up-down up-up
            $value_hist_penultimate_diff=((floatval($symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist'])-2][1])-floatval($symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist'])-3][1]))/max(0.01,abs(floatval($symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist'])-3][1]))));
            $symbol_object[$param_id.'_hist_trend']="-";
            if      ($value_hist_penultimate_diff>0 && $value_hist_last_diff >0){
                $symbol_object[$param_id.'_hist_trend']="/";
            }else if($value_hist_penultimate_diff<0 && $value_hist_last_diff >0){
                $symbol_object[$param_id.'_hist_trend']="v";
            }else if($value_hist_penultimate_diff>0 && $value_hist_last_diff <0){
                $symbol_object[$param_id.'_hist_trend']="^";
            }else if($value_hist_penultimate_diff<0 && $value_hist_last_diff <0){
                $symbol_object[$param_id.'_hist_trend']="\\";
            }
        }
    }
}

function hist_min($param_id,$freq, &$symbol_object){
    $timestamp_date=date("Y-m-d"); // refresh date
    $timestamp_freq=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / $freq) % (12/$freq)) + 1 );
    if(!array_key_exists($param_id,$symbol_object) || $symbol_object[$param_id]==null || $symbol_object[$param_id]=="" || $symbol_object[$param_id]=="-"){
        if($symbol_object[$param_id]!=0){ // non-string issue
            send_mail('ERROR:'.$param_id.' !exist or empty','<br />hist_min: For '.$symbol_object['name'].' '.implode(" ",array_keys($symbol_object)).'<br /><br />',"hectorlm1983@gmail.com");
            die('In hist_min() the param_id ('.$param_id.') does not exist or empty or -');
        }else{
            $symbol_object[$param_id]="0";
        }
    }
    if(!array_key_exists($param_id.'_hist',$symbol_object)){$symbol_object[$param_id.'_hist']=array();}    
    if(!array_key_exists($param_id.'_hist',$symbol_object) || count($symbol_object[$param_id.'_hist'])==0){
        $symbol_object[$param_id.'_hist'][]=[$timestamp_date,$symbol_object[$param_id]];
    }else{
        $last_elem_date=end($symbol_object[$param_id.'_hist'])[0];
        $last_elem_freq=substr($last_elem_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $last_elem_date)->format('n') / $freq) % (12/$freq)) + 1 );
        //echo "$last_elem_date half $last_elem_freq current half $timestamp_freq<br />";
        if($timestamp_freq!=$last_elem_freq){
            $symbol_object[$param_id.'_hist'][]=[$timestamp_date,$symbol_object[$param_id]];
        }else{ // to keep it fresh
            $symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist']) - 1]=[$timestamp_date,$symbol_object[$param_id]];
        }
    }
}

function hist_year_last_day($param_id, &$symbol_object){
    $timestamp_date=date("Y-m-d"); // refresh date
    $timestamp_freq=substr($timestamp_date,0,4);
    if(!array_key_exists($param_id,$symbol_object) || $symbol_object[$param_id]==null || $symbol_object[$param_id]=="" || $symbol_object[$param_id]=="-"){
        if($symbol_object[$param_id]!=0){ // non-string issue
            send_mail('ERROR:'.$param_id.' !exist or empty','<br />hist_year_last_day: For '.$symbol_object['name'].' '.implode(" ",array_keys($symbol_object)).'<br /><br />',"hectorlm1983@gmail.com");
            die('In hist_year_last_day() the param_id ('.$param_id.') does not exist or empty or -');
        }else{
            $symbol_object[$param_id]="0";
        }
    }
    if(!array_key_exists($param_id.'_hist',$symbol_object)){$symbol_object[$param_id.'_hist']=array();}
    if(!array_key_exists($param_id.'_hist',$symbol_object) || count($symbol_object[$param_id.'_hist'])==0){
        $symbol_object[$param_id.'_hist'][]=[$timestamp_date,$symbol_object[$param_id]];
    }else{
        $last_elem_date=end($symbol_object[$param_id.'_hist'])[0];
        $last_elem_val=end($symbol_object[$param_id.'_hist'])[1];
        $last_elem_freq=substr($last_elem_date,0,4);
        if($timestamp_freq!=$last_elem_freq){
            // store the new and set the last to last year date
            $symbol_object[$param_id.'_hist'][]=[$timestamp_date,$symbol_object[$param_id]];
            $symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist']) - 1]=[$last_elem_freq."-12-31",$last_elem_val];
        }else{ // to keep it fresh
            $symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist']) - 1]=[$timestamp_date,$symbol_object[$param_id]];
        }
    }
}

// compute the growth of every period
// compute the growth of every period growth
// compute averages
// to weight in favor of the present, average the average with (avg+(penultimate+2xcurrent)/3)/4
// by default min 3 periods (4 data points), otherwise [0,0]
function growth_and_acceleration($param_id, $symbol_object,$min_periods=3){
    //black magic
}

function compound_average_growth($from, $to, $periods=1.0){
    $cag=0.0;
    $from=floatval($from);
    $to=floatval($to);
    $periods=floatval($periods);
    if($from==$to){ 
        //echo "<br />cag: from=to"; 
        return 0;} // no diff no calc
    if($from==0){
         //echo "<br />cag: from=0";
         $from=0.01;} // protection against 0 division
    $cag=$to/$from;
    if($from<0 || $to<0){
        $cag=($to-$from)/max(0.5,abs($from));
        $cag=$cag+1; // to pow it if needed
    }
    //echo "<br />from=$from,to=$to,cag=$cag";
    if($periods>1){$cag=pow($cag,(1.0/$periods));}
    //echo "<br />cag=$cag";
    $cag=$cag-1;
    return $cag;
}

function compound_interest_4($principal,$interest,$years){
	return floatval(toFixed( $principal*pow((1+(floatval($interest)/4)),$years*4) ,2));
}


// if there are not enough periods the oldest will be used
// e.g., existing   5 6
// required 5 periods
// then             5 5 5 5 6

function hist_compound_average_growth($param_id, $symbol_object,$num_periods=5){
    $cag=0;
    if(!array_key_exists($param_id,$symbol_object)){die('In hist_compound_average_growth() the param_id ('.$param_id.') does not exist');}
    $curr_val=floatval(end($symbol_object[$param_id])[1]);
    $orig_val=floatval($symbol_object[$param_id][0][1]);
    if(count($symbol_object[$param_id])>$num_periods){
        $orig_val=floatval($symbol_object[$param_id][count($symbol_object[$param_id])-($num_periods+1)][1]);
    }
    // annualized with compound
    $cag=compound_average_growth($orig_val,$curr_val,$num_periods);
    return $cag;
}


function pad_array_with_first_value($arr,$desired_elems){
    if($desired_elems<=0){echo "ERROR: desired_elems=$desired_elems (<=0)";exit(1);}
    if(count($arr)<1){echo "ERROR: empty array";exit(1);}
    if(count($arr)>=$desired_elems){
        return array_slice($arr, -$desired_elems);
    }else{
        $ret_array=array();
        $diff=count($arr)-$desired_elems;
        for($i=0;$i<$desired_elems;$i++){
            if( ($i+$diff)>=0 ){
                $ret_array[]=$arr[($i+$diff)];
            }else{
                $ret_array[]=$arr[0];
            }
        }
        return $ret_array;
    }
}

function hist_growth_array($param_id, $symbol_object,$num_periods=-1){
    $growth_array=array();
    if(!array_key_exists($param_id,$symbol_object)){die('In hist_growth_array() the param_id ('.$param_id.') does not exist');}
    $hist=$symbol_object[$param_id];
    $hist_count=count($hist)-1;
    if($num_periods==-1) $num_periods=$hist_count; // with 6 elems we can compute 5 periods
    if($hist_count<1){$growth_array[0]=0;return $growth_array;}
    for ($i = $hist_count-$num_periods; $i < $hist_count; $i++) {
        $from_val=floatval($symbol_object[$param_id][0][1]);
        $to_val=floatval($symbol_object[$param_id][0][1]);
        if($i>=0){
            $from_val=floatval($symbol_object[$param_id][$i][1]);
            $to_val=floatval($symbol_object[$param_id][$i+1][1]);
        }
        $growth_array[]=floatval(toFixed(compound_average_growth(floatval(toFixed($from_val,3)),floatval(toFixed($to_val,3))),2));
    }
    return $growth_array;
}


function acceleration_array($growth_array){
    $acceleration_array=array();
    if(count($growth_array)<=1){$acceleration_array[0]=0;return $acceleration_array;}
    for($i=0;$i<(count($growth_array)-1);$i++){
        $acceleration_array[]=floatval(toFixed($growth_array[($i+1)]-$growth_array[$i],2));
    }
    return $acceleration_array;
}



function trend($arr,$threshold=0.10){
    $trend="--";
    if(count($arr)>=2){
        $arr2=array_slice($arr, -2, 2);
        if      ($arr2[0]>$threshold && $arr2[1]>$threshold){
            $trend="/";
        }else if($arr2[0]>$threshold && $arr2[1] <$threshold && $arr2[1] >-$threshold){
            $trend="/-";
        }else if($arr2[0] <$threshold && $arr2[0] >-$threshold && $arr2[1]>$threshold){
            $trend="_/";
        }else if($arr2[0]<-$threshold && $arr2[1] > $threshold){  //$arr2[0] if we want this we need more options `/ 
            $trend="v";
        }else if($arr2[0]>$threshold && $arr2[1] < -$threshold){  //(-1*$arr2[0]) if we want this we need more option ´\
            $trend="^";
        }else if($arr2[0]<-$threshold && $arr2[1] <$threshold && $arr2[1] >-$threshold){
            $trend="\_";
        }else if($arr2[0] <$threshold && $arr2[0] >-$threshold && $arr2[1]<-$threshold){
            $trend="-\\";
        }else if($arr2[0]<-$threshold && $arr2[1] <-$threshold){
            $trend="\\";
        }
    }
    return $trend;
}

function smooth_avg_7($arr){
    $arr=pad_array_with_first_value($arr,7);
    // TODO
}


function facsum($n,$init=3){
    $n=intval($n);
    $f=0;
    if($init<0){echo "ERROR: init=$init (<0)";exit(1);}
    if($n<=0){return 1;}
    for($i=0;$i<$n;$i++){
        $f+=$i+1+$init;
    }
    return $f;
}

// weighted avg towards the most recent elements
// see formula in the code (basically the weight is the position)
function avg_weighted_sum($arr,$init=3){
    $avgw=0;
    if(count($arr)<1){return 0;}
    $tot_elems_weight=facsum(count($arr),$init);
    for($i=0;$i<count($arr);$i++){
        $avgw+=(floatval($arr[$i])/$tot_elems_weight)*($i+1+$init);
    }
    return floatval(toFixed($avgw,2));
}

function avg_weighted($arr,$percent_weight=0.66,$max=1000000000000.0){
    if(count($arr)<1){return 0;}
    $avgw=0;
	$curr_weight=1;
    $total_elems_weight=0;
    for($i=0;$i<count($arr);$i++){
		$total_elems_weight+=$curr_weight;
        $avgw+=min(floatval($arr[$i]),$max)*$curr_weight;
		$curr_weight=$curr_weight+($curr_weight*$percent_weight);
    }
	$avgw=$avgw/$total_elems_weight;
    return floatval(toFixed($avgw,2));
}

function avg_normal($arr){
    if(count($arr)<1){return 0;}
	$avg=0;
    for($i=0;$i<count($arr);$i++){
        $avg+=floatval($arr[$i]);
    }
	$avg=$avg/count($arr);
    return floatval(toFixed($avg,2));	
}

function clean_no_revenue(&$tsv_arr){
	$keys_to_remove=array();
	foreach ($tsv_arr as $key => $value){
		if(strval($key)[0]=="2"){
			if(!array_key_exists('revenue',$value)){
				$keys_to_remove[]=$key;
			}
		}
	}
	foreach($keys_to_remove as $key){
		unset($tsv_arr[$key]);
	}
}


// adds om and returns om_max, om_avg, om_pot
function get_om_max_avg_pot(&$tsv_arr){
	$om_obj=array();
	$om_obj['max']=-0.01;
	$om_obj['avg']=0;
	$om_obj['pot']=0.01;
	$om=0;
	$num_years=0;
	foreach ($tsv_arr as $key => $value){
		if(strval($key)[0]=="2"){
			$num_years++;
			//echo "key=$key<br />";
			//var_dump($value);
			
			//avoid 0 division
			if(floatval($value['revenue'])==0){
				$value['revenue']=0.001;
			}
			if(floatval($value['operating_income'])!=0){
				$om=floatval(toFixed(floatval($value['operating_income'])/floatval($value['revenue']),2));
			}else{  // use net income if om=0 (no data)
				$om=floatval(toFixed((floatval($value['net_income'])+(abs($value['net_income'])*0.1))/floatval($value['revenue']),2));
			}
			$om_obj['avg']+=$om;
			$tsv_arr[$key]['operating_margin']=$om;
			if($om>$om_obj['max']) $om_obj['max']=$om;
		}
	}
	if($num_years>0){
		$om_obj['avg']=floatval(toFixed($om_obj['avg']/$num_years,2));
		$om_obj['avg_no_max']=floatval(toFixed(($om_obj['avg']-$om_obj['max'])/($num_years-1),2));
	}
	
	//$om_obj['pot']=max(($om_obj['avg']+$om_obj['max'])/2,$om);
	$om_obj['pot']=$om;
	if($om>0.01 && $om<$om_obj['avg_no_max']){
		$om_obj['pot']=($om_obj['avg_no_max']+$om)/2;
	}
	
	return $om_obj;
}

// adds om and returns om_max, om_avg, om_pot
function get_prod_ps(&$tsv_arr){
	$hist_obj=array();
	$hist_obj['prod_ps_hist']=array();
	$num_years=0;
	foreach ($tsv_arr as $key => $value){
		if(strval($key)[0]=="2"){
			$num_years++;
			//echo "key=$key<br />";
			//var_dump($value);
			$tsv_arr[$key]['prod_ps']=floatval(toFixed(max(
													//$tsv_arr[$key]['revenue_ps']*$tsv_arr[$key]['operating_margin'],
													$tsv_arr[$key]['operating_income_ps'],
													$tsv_arr[$key]['net_income_ps']+(abs($tsv_arr[$key]['net_income_ps'])*0.1) ),2));
			$hist_obj['prod_ps_hist'][]=[$key, $tsv_arr[$key]['prod_ps']];
			$tsv_arr[$key]['prod_source']='O';
			if($tsv_arr[$key]['prod_ps']==floatval(toFixed($tsv_arr[$key]['net_income_ps']+(abs($tsv_arr[$key]['net_income_ps'])*0.1),2))) $tsv_arr[$key]['prod_source']='N';
		}
	}
	/*if($num_years>0){
		$om_obj['avg']=floatval(toFixed($om_obj['avg']/$num_years,2));
	}*/
	return $hist_obj;
}


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
			if(array_key_exists(substr($valdata[0],0,4),$seen_years)){
			echo "ERROR duplicated year in ${param}_hist ".substr($valdata[0],0,4)."<br />"; exit(1);
			send_mail('ERROR:'.$param.'_hist dup year '.$stock_data['name'],'<br />For '.$stock_data['name']."ERROR duplicated year in ${param}_hist ".substr($valdata[0],0,4).'<br /><br />',"hectorlm1983@gmail.com");
			}
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
		send_mail('ERROR: no '.$param.' in '.$stock_data['name'],'<br />For '.$stock_data['name']."ERROR no ${param}<br /><br />","hectorlm1983@gmail.com");
	}
}



function test_pad_arr(){
    $arr=[2,3];
    $padded_arr=pad_array_with_first_value($arr,5);
	echo "<br />origarr=[".implode(" ",$arr)."] padded(5)=[".implode(" ",$padded_arr)."]<br />";
    $padded_arr=pad_array_with_first_value($arr,3);
	echo "<br />origarr=[".implode(" ",$arr)."] padded(3)=[".implode(" ",$padded_arr)."]<br />";
}

function test_ksort(){
    $test_arr=array("name"=>"ker","market"=>"EPA","2014-12-31"=>"XXX","2017-12-31"=>"XXX","2015-12-31"=>"XXX","2016-12-31"=>"XXX");
    echo "unsorted<br />";
    print_r($test_arr);
    ksort($test_arr);
    echo "sorted<br />";
    print_r($test_arr);
}

function test_avg_weighted(){
	$arr=[1,2,3,4,5];
	echo "<br />arr=[".implode(", ",$arr)."] avg=".avg_normal($arr)." avgs=".avg_weighted_sum($arr)." avgw=".avg_weighted($arr,0.66);
	$arr=[1,2,3,4,1];
	echo "<br />arr=[".implode(", ",$arr)."] avg=".avg_normal($arr)." avgs=".avg_weighted_sum($arr)." avgw=".avg_weighted($arr,0.66);
	$arr=[1,1,3,5,5];
	echo "<br />arr=[".implode(", ",$arr)."] avg=".avg_normal($arr)." avgs=".avg_weighted_sum($arr)." avgw=".avg_weighted($arr,0.66);
	
}

if(isset($_REQUEST['test'])){
    //test_pad_arr();
    // TODO smooth_avg_7
    test_ksort();
    test_avg_weighted();
}


?>