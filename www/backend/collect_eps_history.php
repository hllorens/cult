<?php


//header('Content-type: application/json');
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


echo "<br />".((ceil(date('n') / 3) % 4) + 1 )."  ".date("Y-m-d")."<br />";
echo "<br />".((ceil(DateTime::createFromFormat('Y-m-d', '2016-12-01')->format('n') / 3) % 4) + 1 )."  2016-12-01<br />";
echo "<br />".((ceil(DateTime::createFromFormat('Y-m-d', '2016-02-01')->format('n') / 3) % 4) + 1 )."  2016-02-01<br />";
echo "<br />".((ceil(DateTime::createFromFormat('Y-m-d', '2016-05-01')->format('n') / 3) % 4) + 1 )."  2016-05-01<br />";



foreach(array_filter(glob($data_directory2.'/*.stocks.formated.json'), 'is_file') as $file) {
        $json_a = json_decode(file_get_contents($file),true);
        $fdate=basename ( $file, '.stocks.formated.json');
        echo "$file<br />\n";
        $fquarter=substr($fdate,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $fdate)->format('n') / 3) % 4) + 1 );
        $fhalf=substr($fdate,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $fdate)->format('n') / 6) % 2) + 1 );
        //process
        foreach ($json_a as $item) {
            if(!array_key_exists($item['name'].':'.$item['market'],$data_object)){
                $symbol_object=array();
                $symbol_object['name']=$item['name'];
                $symbol_object['market']=$item['market'];
                $symbol_object['eps-hist']=array();
                $symbol_object['per-hist']=array();
                $symbol_object['yield-hist']=array();
                $data_object[$item['name'].':'.$item['market']]=$symbol_object;
            }
            if(array_key_exists('eps',$item) && $item['eps']!="" && $item['eps']!="-"){
                echo "eps val".$item['name']."<br />";
                if(count($data_object[$item['name'].':'.$item['market']]['eps-hist'])==0){
                    echo "initial eps".$item['name']."<br />";
                    $data_object[$item['name'].':'.$item['market']]['eps-hist'][]=[$fdate,$item['eps']];
                }else{
                    echo "non initial eps".$item['name']."<br />";
                    //print_r($data_object[$item['name'].':'.$item['market']]);
                    $last_eps=end($data_object[$item['name'].':'.$item['market']]['eps-hist'])[1];
                    $last_eps_date=end($data_object[$item['name'].':'.$item['market']]['eps-hist'])[0];
                    $last_eps_quarter=substr($last_eps_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $last_eps_date)->format('n') / 3) % 4) + 1 );
                    //echo (floatval($item['eps'])."-".floatval($last_eps))."=".abs(abs(floatval($item['eps']))-abs(floatval($last_eps)))." and 10%=".(abs(floatval($last_eps))*0.1)."<br />\n";
                    if($item['eps']!=$last_eps && abs(floatval($item['eps'])-floatval($last_eps))>(abs(floatval($last_eps))*0.005)){ //
                        //echo $item['name']."new=".$item['eps']."  last=".$last_eps."<br />\n";
                        // not only equal but a diff greater than the 1% of the old value
                        // and not possibly next day...
                        if($fquarter!=$last_eps_quarter){
                            $data_object[$item['name'].':'.$item['market']]['eps-hist'][]=[$fdate,$item['eps']];
                        }else{
                            echo $item['name'].':'.$item['market'].": actualizado eps hist mismo quarter $last_eps_quarter";
                            $data_object[$item['name'].':'.$item['market']]['eps-hist'][count($data_object[$item['name'].':'.$item['market']]['eps-hist']) - 1]=[$fdate,$item['eps']];
                        }
                    }
                }
            }
            if(array_key_exists('yield',$item) && $item['yield']!="" && $item['yield']!="-"){
                if(count($data_object[$item['name'].':'.$item['market']]['yield-hist'])==0){
                    $data_object[$item['name'].':'.$item['market']]['yield-hist'][]=[$fdate,$item['yield']];
                }else{
                    $last_yield=end($data_object[$item['name'].':'.$item['market']]['yield-hist'])[1];
                    $last_yield_date=end($data_object[$item['name'].':'.$item['market']]['yield-hist'])[0];
                    $last_yield_half=substr($last_yield_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $last_yield_date)->format('n') / 6) % 2) + 1 );
                    //echo "$last_yield_date half $last_yield_half current half $fhalf<br />";
                    if($fhalf!=$last_yield_half){
                        $data_object[$item['name'].':'.$item['market']]['yield-hist'][]=[$fdate,$item['yield']];
                    }
                }
            }
            if(array_key_exists('per',$item) && $item['per']!="" && $item['per']!="-" && $item['per']!="999"){
                if(count($data_object[$item['name'].':'.$item['market']]['per-hist'])==0){
                    $data_object[$item['name'].':'.$item['market']]['per-hist'][]=[$fdate,$item['per']];
                }else{
                    $last_per=end($data_object[$item['name'].':'.$item['market']]['per-hist'])[1];
                    $last_per_date=end($data_object[$item['name'].':'.$item['market']]['per-hist'])[0];
                    $last_per_half=substr($last_per_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $last_per_date)->format('n') / 6) % 2) + 1 );
                    //echo "$last_per_date half $last_per_half current half $fhalf<br />";
                    if($fhalf!=$last_per_half){
                        $data_object[$item['name'].':'.$item['market']]['per-hist'][]=[$fdate,$item['per']];
                    }
                }
            }
        }
}


echo json_encode( $data_object ); 


?>
