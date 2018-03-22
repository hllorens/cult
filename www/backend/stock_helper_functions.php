<?php


function toFixed($number, $decimals=2, $tracking="tracking unset") {
  if(!is_numeric($number)){
        echo "not numeric: $number ($tracking)";
        $number=0; 
  } 
  return number_format($number, $decimals, ".", "");
}

// freq can be 3 for quarters or 6 for halves, more sophisticated do it manually
function hist($param_id,$freq, &$symbol_object, $max_elems_to_avg=8, $max_avg="no", $min_avg="no"){
    $timestamp_date=date("Y-m-d"); // refresh date
    $timestamp_freq=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / $freq) % (12/$freq)) + 1 );
    if(!array_key_exists($param_id,$symbol_object)){die('In hist() the param_id ('.$param_id.') does not exist');}
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
    if(!array_key_exists($param_id,$symbol_object)){die('In hist_min() the param_id ('.$param_id.') does not exist');}
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
    if(!array_key_exists($param_id,$symbol_object)){die('In hist_year_last_day() the param_id ('.$param_id.') does not exist');}
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
    if($from==$to){ echo "<br />cag: from=to"; return 0;} // no diff no calc
    if($from==0){echo "<br />cag: from=0"; $from=0.001;} // protection against 0 division
    $cag=$to/$from;
    //echo "<br />from=$from,to=$to,cag=$cag";
    if($periods>1){$cag=pow($cag,(1.0/$periods));}
    //echo "<br />cag=$cag";
    $cag=$cag-1;
    return $cag;
}


// if there are not enough periods the oldest will be used
// e.g., existing   5 6
// required 5 periods
// then             5 5 5 5 6

function hist_compound_average_growth($param_id, $symbol_object,$num_periods=5){
    $cag=0;
    if(!array_key_exists($param_id,$symbol_object)){die('In growth_and_acceleration() the param_id ('.$param_id.') does not exist');}
    $curr_val=floatval(end($symbol_object[$param_id])[1]);
    $orig_val=floatval($symbol_object[$param_id][0][1]);
    if(count($symbol_object[$param_id])>$num_periods){
        $orig_val=floatval($symbol_object[$param_id][count($symbol_object[$param_id])-($num_periods+1)][1]);
    }
    // annualized with compound
    $cag=compound_average_growth($orig_val,$curr_val,$num_periods);
    return $cag;
}

function hist_growth_array($param_id, $symbol_object,$num_periods=-1){
    $growth_array=array();
    if(!array_key_exists($param_id,$symbol_object)){die('In growth_and_acceleration() the param_id ('.$param_id.') does not exist');}
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
        $growth_array[]=compound_average_growth(floatval(toFixed($from_val,3)),floatval(toFixed($to_val,3)));
    }
    return $growth_array;
}


function acceleration_array($growth_array){
    $acceleration_array=array();
    if(count($growth_array)<=1){$acceleration_array[0]=0;return $acceleration_array;}
    for($i=0;$i<(count($growth_array)-1);$i++){
        $acceleration_array[]=compound_average_growth($growth_array[$i],$growth_array[($i+1)]);
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
        }else if($arr2[0]<-$threshold && $arr2[1] > $arr2[0] ){
            $trend="v";
        }else if($arr2[0]>$threshold && $arr2[1] < (-1*$arr2[0])){
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


?>