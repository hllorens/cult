<?php

require_once("email_config.php");
require_once 'stock_helper_functions.php';

function get_wb($indicator,$country,$debug=false){
    $old=array();
	$out_json='wb.json';
    if(!isset($indicator) ||!isset($country)){echo "ERROR: indicator and country needed";exit(1);}
    if(file_exists ( $out_json )){
        if($debug) echo "$out_json exists -> reading...<br />";
        $old = json_decode(file_get_contents($out_json), true);
    }else{
        echo "$out_json does NOT exist -> using an empty array<br />";
    }
    $url_and_query="http://api.worldbank.org/countries/$country/indicators/$indicator?format=json&per_page=500";
    echo "financial $url_and_query<br />";
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url_and_query );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );

	$curl_data=json_decode($response,true);
	
	if($curl_data==null || count($curl_data)==0 || count($curl_data)==1){
		$latelog = fopen("late_wb.log", "a") or die("Unable to open/create late_wb.log!");
		fwrite($latelog, date('Y-m-d H:i:s')."  wb_curl.php. Error empty curl $url_and_query<br />\n");
		fclose($latelog);
		return $old;
	}
	
	//var_dump($curl_data);
	$new_report="";
	for($i=0;$i<count($curl_data[1]);$i++){
		if($curl_data[1][$i]['value']!=null && trim($curl_data[1][$i]['value'])!=''){
			$reduction_factor=1; // as-is
			$decimals=2;
			if($indicator=='AG.SRF.TOTL.K2'){
				$reduction_factor=1000000; // milions
			}
			if($indicator=='SP.POP.TOTL'){
				$reduction_factor=1000000000; // bilions
			}
			if($indicator=='NY.GDP.MKTP.CD'){
				$reduction_factor=1000000000000; // trilions
				$decimals=4;
			}
			$val=toFixed((double)$curl_data[1][$i]['value']/$reduction_factor, $decimals, "wb val");
			if($val==null || empty($val) || $val==0){
				$latelog = fopen("late_wb.log", "a") or die("Unable to open/create late_wb.log!");
				fwrite($latelog, date('Y-m-d H:i:s')."  wb_curl.php. Error 0 value curl $url_and_query<br />".$curl_data[1][$i]['date']." $indicator $country $val<br />\n");
				fclose($latelog);
				continue;
			}
			if($debug) echo $curl_data[1][$i]['date']." ".$curl_data[1][$i]['country']['value']." (".$val.")"; //
			if(!array_key_exists($indicator,$old)){
				$old[$indicator]=array();
			}
			if(!array_key_exists($country,$old[$indicator])){
				$old[$indicator][$country]=array();
			}
			if(array_key_exists($curl_data[1][$i]['date'],$old[$indicator][$country])){
				if($old[$indicator][$country][$curl_data[1][$i]['date']]!=$val){
					//late log change past
				}else{
					if($debug) echo "no update";
				}
			}else{
				// avoid crazy email
				$new_report.="$url_and_query<br /> new val ".$curl_data[1][$i]['date']." ".$curl_data[1][$i]['country']['value']." (".$val.")<br /><br />";
				echo "new val ".$curl_data[1][$i]['date']." ".$curl_data[1][$i]['country']['value']." (".$val.")<br />";
				$old[$indicator][$country][$curl_data[1][$i]['date']]=$val;
				// no.. json exportable to csv is fine, no add it to firebase
			}
			if($debug) echo "<br />";
		}
	}
	if($new_report!=""){
		send_mail("wb new $indicator $country","$new_report<br />","hectorlm1983@gmail.com");
	}
	

	echo date('Y-m-d H:i:s')." updating $out_json\n";
	$old_str=json_encode( $old );
	$json_file = fopen($out_json, "w") or die("Unable to open file $out_json!");
	fwrite($json_file, $old_str);
	fclose($json_file);

    ksort($old);
    return $old;
}

if( isset($_REQUEST['indicator']) ){
	$country="us";
	if(isset($_REQUEST['country']) ){
		$country=$_REQUEST['country'];
	}
    $debug=false;
    if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
        $debug=true;
    } 
    echo "manual mode<br /><br />";
    $curl_data=get_wb($_REQUEST['indicator'],$country,$debug);
    echo "Result:<br />---<pre>".json_encode($curl_data, JSON_PRETTY_PRINT)."</pre>---<br />";
}



?>