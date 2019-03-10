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
	
	if($curl_data==null || count($curl_data)==0){
		// handle late log
		return $old;
	}
	
	//var_dump($curl_data);
	
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
			echo $curl_data[1][$i]['date']." ".$curl_data[1][$i]['country']['value']." (".$val.")<br />"; //
			if(!array_key_exists($indicator,$old)){
				$old[$indicator]=array();
			}
			if(!array_key_exists($country,$old[$indicator])){
				$old[$indicator][$country]=array();
			}
			if(array_key_exists($curl_data[1][$i]['date'],$old[$indicator][$country])){
				if($old[$indicator][$country][$curl_data[1][$i]['date']]!=$val){
					//late log change past
				}
			}else{
				$old[$indicator][$country][$curl_data[1][$i]['date']]=$val;
				// no.. json exportable to csv is fine, no add it to firebase
			}
		}
	}
	
	/*
		if($new_period_report!="" && !$first_time_financials){
			echo 'new financials '.$name,"$url_and_query<br />In ".$symbol." ".$new_period_report."<br /><br />";
			//send_mail('new financials '.$name,"$url_and_query<br />In ".$symbol." ".$new_period_report."<br /><br />","hectorlm1983@gmail.com");
		}
		if($change_past_report!=""){
			send_mail('financials change past '.$name,"$url_and_query<br />In ".$symbol." ".$change_past_report."<br /><br />","hectorlm1983@gmail.com");
		}*/

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