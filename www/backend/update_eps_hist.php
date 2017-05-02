<?php
header('Content-type: application/json');
$data_directory='../../../cult-data-stock-google';
$data_directory2='../../../cult-data-stock-eps-hist';

date_default_timezone_set('Europe/Madrid');
$timestamp_date=date("Y-m-d");
$timestamp_simplif=date("d H:i");

function toFixed($number, $decimals=2) {
  return number_format($number, $decimals, ".", "");
}



$file=$data_directory.'/dividend_yield.json';
$json_a = json_decode(file_get_contents($file),true);
$file=$data_directory2.'/eps-hist.json';
$data_object = json_decode(file_get_contents($file),true);
//$data_object=array();

foreach ($json_a as $item) {
    if(array_key_exists('eps',$item) && $item['eps']!="" && $item['eps']!="-"){
        if(!array_key_exists($item['name'].':'.$item['market'],$data_object)){
            $symbol_object=array();
            $symbol_object['name']=$item['name'];
            $symbol_object['market']=$item['market'];
            $symbol_object['eps-hist']=[[$timestamp_date,$item['eps']]];
            $data_object[$item['name'].':'.$item['market']]=$symbol_object;
        }else{
            //if($item['name']=='EBAY'){
                $last_eps=end($data_object[$item['name'].':'.$item['market']]['eps-hist'])[1];
                //echo (floatval($item['eps'])."-".floatval($last_eps))."=".abs(abs(floatval($item['eps']))-abs(floatval($last_eps)))." and 1%=".(abs(floatval($last_eps))*0.1)."<br />\n";
                if($item['eps']!=$last_eps && abs(floatval($item['eps'])-floatval($last_eps))>(abs(floatval($last_eps))*0.01)){ //
                    //echo $item['name']."new=".$item['eps']."  last=".$last_eps."<br />\n";
                    // not only equal but a diff greater than the 1% of the old value
                    // and not possibly next day...
                    $data_object[$item['name'].':'.$item['market']]['eps-hist'][]=[$timestamp_date,$item['eps']];
                }
            //}
        }
    }
}



echo json_encode( $data_object );


?>
