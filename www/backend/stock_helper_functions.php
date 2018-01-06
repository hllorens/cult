<?php

// freq can be 3 for quarters or 6 for halves, more sophisticated do it manually
function hist($param_id,$freq, &$symbol_object, $max_elems_to_avg=8, $max_avg="no", $min_avg="no"){
    $timestamp_date=date("Y-m-d"); // refresh date
    $timestamp_freq=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / $freq) % (12/$freq)) + 1 );
    if(!array_key_exists($param_id,$symbol_object)){die('In hist() the param_id ('.$param_id.') does not exist');}
    if(!array_key_exists($param_id.'_hist',$symbol_object)){$symbol_object[$param_id.'_hist']=array();}
    if(!array_key_exists($param_id.'_hist_last_diff',$symbol_object)){$symbol_object[$param_id.'_hist_last_diff']=0;}
    
    if(count($symbol_object[$param_id.'_hist'])==0){
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
    if(count($symbol_object[$param_id.'_hist'])>1){
        $value_hist_last_diff=((floatval(end($symbol_object[$param_id.'_hist'])[1])-floatval($symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist'])-2][1]))/max(0.01,abs(floatval($symbol_object[$param_id.'_hist'][count($symbol_object[$param_id.'_hist'])-2][1]))));
        $symbol_object[$param_id.'_hist_last_diff']=toFixed($value_hist_last_diff*100,0);
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
        $symbol_object['avg'.$param_id]="".toFixed($avgelems);
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

?>