<?php
header('Content-type: application/json');
$data_directory='../../../cult-data-stock-google';

date_default_timezone_set('Europe/Madrid');
$timestamp_date=date("Y-m-d");
$timestamp_simplif=date("d H:i");
$data_object=array();
$file3=$data_directory.'/stocks.formated.json';
$string3 = file_get_contents($file3);
$json_a3 = json_decode($string3, true);

$file2=$data_directory.'/dividend_yield.json';
$string2 = file_get_contents($file2);
$json_a2 = json_decode($string2, true);
if ($json_a2 === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode( $json_a3 ); // use the last existing stocks.formated
    exit(0);
}
$file=$data_directory.'/stocks.json';
$string = file_get_contents($file);
$json_a = json_decode($string, true);
if ($json_a === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode( $json_a3 ); // use the last existing stocks.formated
    exit(0);
}

$file=$data_directory.'/eps-hist.json';
$string = file_get_contents($file);
$json_a4 = json_decode($string, true);
if ($json_a4 === null){
    echo "ERROR in ".$file." check it with pretty print...";
    exit(0);
}


function toFixed($number, $decimals=2) {
  return number_format($number, $decimals, ".", "");
}

foreach ($json_a as $item) {
	$symbol_object=array();
	$symbol_object['name']=$item['t'];
    if($symbol_object['name']=='GOOG'){
        $symbol_object['date']=$timestamp_simplif;
    }
	$symbol_object['market']=$item['e'];
	$symbol_object['value']=str_replace(",","",$item['l']);
	$symbol_object['session_change']=$item['c'];
	$symbol_object['session_change_percentage']=$item['cp'];
	$symbol_object['title']=substr($json_a2[$item['e'].':'.$item['t']]['title'],0,30);
    if(!$symbol_object['title']){$symbol_object['title']="ERROR: No title found";}
	$symbol_object['yield']=$json_a2[$item['e'].':'.$item['t']]['yield'];
	$symbol_object['dividend']=$json_a2[$item['e'].':'.$item['t']]['dividend'];
	$symbol_object['range_52week']=trim($json_a2[$item['e'].':'.$item['t']]['range_52week']);
    $symbol_object['range_52week_high']="0";
    $symbol_object['range_52week_low']="0";
    $symbol_object['range_52week_heat']="0";
    $symbol_object['range_52week_volatility']="0";
    if(strpos($symbol_object['range_52week'], '- ') !== false){
        $parts = explode('- ', $symbol_object['range_52week']);
        $symbol_object['range_52week_low']=$parts[0];
        $symbol_object['range_52week_high']=$parts[1];
        $symbol_object['range_52week_heat']="".toFixed((floatval($symbol_object['value'])-floatval($symbol_object['range_52week_low']))/(floatval($symbol_object['range_52week_high'])-floatval($symbol_object['range_52week_low'])));
        // you could use "low" or "high" but using "low" is what if val 3 to 6 then volat = 100% == 2x
        $symbol_object['range_52week_volatility']="".toFixed((floatval($symbol_object['range_52week_high'])-floatval($symbol_object['range_52week_low']))/(floatval($symbol_object['range_52week_low'])));
        // to show the 2x format or 1.3x, we can do it in js to avoid making json bigger
        //$symbol_object['range_52week_volatility_times']="".toFixed(((floatval($symbol_object['range_52week_high'])-floatval($symbol_object['range_52week_low']))/(floatval($symbol_object['range_52week_low'])))+1,1);
    }
    $symbol_object['divs_per_year']="0";
    $symbol_object['dividend_total_year']="0";
    $symbol_object['yield_per_ratio']="0";
	$symbol_object['beta']=$json_a2[$item['e'].':'.$item['t']]['beta'];
	$symbol_object['eps']=$json_a2[$item['e'].':'.$item['t']]['eps'];
	$symbol_object['per']=$json_a2[$item['e'].':'.$item['t']]['per'];
	$symbol_object['roe']=$json_a2[$item['e'].':'.$item['t']]['roe'];
    if(trim($symbol_object['eps'])=='-' || trim($symbol_object['eps'])==''){$symbol_object['eps']='-';}
    if(trim($symbol_object['roe'])=='-' || trim($symbol_object['roe'])==''){$symbol_object['roe']='-';}
    if(trim($symbol_object['per'])=='-' || trim($symbol_object['per'])==''){$symbol_object['per']=999;}
    if(trim($symbol_object['yield'])=='-' || trim($symbol_object['yield'])==''){$symbol_object['yield']=0;}
    if(floatval($symbol_object['dividend'])!=0){
        $symbol_object['divs_per_year']="".round(((floatval($symbol_object['yield'])/100)*floatval($symbol_object['value']))/floatval($symbol_object['dividend']));
        $symbol_object['dividend_total_year']="".toFixed(floatval($symbol_object['dividend'])*floatval($symbol_object['divs_per_year']));
        if(floatval($symbol_object['per'])>0){
            $symbol_object['yield_per_ratio']="".toFixed((floatval($symbol_object['yield'])/floatval($symbol_object['per'])));
        }
    }
    $symbol_object['eps_hist_last_diff']=0;
    $symbol_object['eps_hist']=array();
    $symbol_object['yield_hist_last_diff']=0;
    $symbol_object['yield_hist']=array();
    $symbol_object['per_hist']=array();
    
    if(array_key_exists($item['t'].':'.$item['e'],$json_a4)){
        foreach ($json_a4[$item['t'].':'.$item['e']]['eps-hist'] as $elem) {
            $symbol_object['eps_hist'][]=[$elem[0],$elem[1]];
        }
        if(count($symbol_object['eps_hist'])>1){
            //echo $symbol_object['name']."<br />\n"."<br />\n";
            $eps_hist_last_diff=((floatval(end($symbol_object['eps_hist'])[1])-floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-2][1]))/abs(floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-2][1])));
            if ($eps_hist_last_diff<-0.04){ // more than 5% annual which is about 20% quarterly
                $symbol_object['eps_hist_last_down']=toFixed($eps_hist_last_diff*100,0); // FOR BACKWARDS COMPATIBILITY
            }
            if ($eps_hist_last_diff<-0.04 || $eps_hist_last_diff>0.04){ // more than 5% annual which is about 20% quarterly  
                $symbol_object['eps_hist_last_diff']=toFixed($eps_hist_last_diff*100,0);
            }else{
                $symbol_object['eps_hist_last_diff']=0;
            }
            if(count($symbol_object['eps_hist'])>2){
                // 4 possibilities down-down down-up up-down up-up
                $eps_hist_penultimate_diff=((floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-2][1])-floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-3][1]))/abs(floatval($symbol_object['eps_hist'][count($symbol_object['eps_hist'])-3][1])));
                $symbol_object['eps_hist_trend']="-";
                if      ($eps_hist_penultimate_diff>0 && $eps_hist_last_diff >0){
                    $symbol_object['eps_hist_trend']="/";
                }else if($eps_hist_penultimate_diff<0 && $eps_hist_last_diff >0){
                    $symbol_object['eps_hist_trend']="v";
                }else if($eps_hist_penultimate_diff>0 && $eps_hist_last_diff <0){
                    $symbol_object['eps_hist_trend']="^";
                }else if($eps_hist_penultimate_diff<0 && $eps_hist_last_diff <0){
                    $symbol_object['eps_hist_trend']="\\";
                }
            }
        }

        foreach ($json_a4[$item['t'].':'.$item['e']]['yield-hist'] as $elem) {
            $symbol_object['yield_hist'][]=[$elem[0],$elem[1]];
        }
        if(count($symbol_object['yield_hist'])>1){
            $symbol_object['yield_hist_last_diff']=toFixed($yield_hist_last_diff*100,0);
            if(count($symbol_object['yield_hist'])>2){
                // 4 possibilities down-down down-up up-down up-up
                $yield_hist_penultimate_diff=((floatval($symbol_object['yield_hist'][count($symbol_object['yield_hist'])-2][1])-floatval($symbol_object['yield_hist'][count($symbol_object['yield_hist'])-3][1]))/abs(floatval($symbol_object['yield_hist'][count($symbol_object['yield_hist'])-3][1])));
                $symbol_object['yield_hist_trend']="-";
                if      ($yield_hist_penultimate_diff>0 && $yield_hist_last_diff >0){
                    $symbol_object['yield_hist_trend']="/";
                }else if($yield_hist_penultimate_diff<0 && $yield_hist_last_diff >0){
                    $symbol_object['yield_hist_trend']="v";
                }else if($yield_hist_penultimate_diff>0 && $yield_hist_last_diff <0){
                    $symbol_object['yield_hist_trend']="^";
                }else if($yield_hist_penultimate_diff<0 && $yield_hist_last_diff <0){
                    $symbol_object['yield_hist_trend']="\\";
                }
            }
        }
        
        foreach ($json_a4[$item['t'].':'.$item['e']]['per-hist'] as $elem) {
            $symbol_object['per_hist'][]=[$elem[0],$elem[1]];
        }
    }
    $data_object[$item['t'].':'.$item['e']]=$symbol_object;
}

echo json_encode( $data_object );


?>
