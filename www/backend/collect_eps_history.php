<?php
header('Content-type: application/json');
$data_directory='../../../cult-data-stock-google';
$data_directory2='../../../cult-data-stock-google-historical';

date_default_timezone_set('Europe/Madrid');
$timestamp_date=date("Y-m-d");
$timestamp_simplif=date("d H:i");
$data_object=array();
function toFixed($number, $decimals=2) {
  return number_format($number, $decimals, ".", "");
}


// ONE TIME SCRIPT
// THEN THIS WILL BE JUST RENEWED EVERY TIME WE DOWNLOAD STOCKS


foreach(array_filter(glob($data_directory2.'/*.stocks.formated.json'), 'is_file') as $file) {
        //echo "$file<br />\n";
        $json_a = json_decode(file_get_contents($file),true);
        $fdate=basename ( $file, '.stocks.formated.json');
        //process
        foreach ($json_a as $item) {
            if(array_key_exists('eps',$item) && $item['eps']!="" && $item['eps']!="-"){
                if(!array_key_exists($item['name'].':'.$item['market'],$data_object)){
                    $symbol_object=array();
                    $symbol_object['name']=$item['name'];
                    $symbol_object['market']=$item['market'];
                    $symbol_object['eps-hist']=[[$fdate,$item['eps']]];
                    $data_object[$item['name'].':'.$item['market']]=$symbol_object;
                }else{
                    //if($item['name']=='GM'){
                        $last_eps=end($data_object[$item['name'].':'.$item['market']]['eps-hist'])[1];
                        //echo (floatval($item['eps'])."-".floatval($last_eps))."=".abs(abs(floatval($item['eps']))-abs(floatval($last_eps)))." and 10%=".(abs(floatval($last_eps))*0.1)."<br />\n";
                        if($item['eps']!=$last_eps && abs(floatval($item['eps'])-floatval($last_eps))>(abs(floatval($last_eps))*0.02)){ //
                            //echo $item['name']."new=".$item['eps']."  last=".$last_eps."<br />\n";
                            // not only equal but a diff greater than the 2% of the old value
                            // and not possibly next day...
                            $data_object[$item['name'].':'.$item['market']]['eps-hist'][]=[$fdate,$item['eps']];
                        }
                    //}
                }
            }
        }
}


echo json_encode( $data_object );


?>
