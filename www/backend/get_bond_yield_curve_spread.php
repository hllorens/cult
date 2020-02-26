<?php

require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)
require_once 'stock_list.php';
require_once("email_config.php");

echo date('Y-m-d H:i:s')." starting<br />";

$timestamp_date=date("Y-m-d H:i:s");


$list="T10Y3M";
$list="$list,T10Y2Y";



$cookieFile = "cookies.txt";
if(!file_exists($cookieFile)) {
    $fh = fopen($cookieFile, "w");
    fwrite($fh, "");
    fclose($fh);
}

function get_details($symbol,$debug=false){
	$details=array();
	$the_url="https://fred.stlouisfed.org/series/";
    $url_and_query=$the_url.$symbol;
    echo "stock $url_and_query<br />";
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url_and_query );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );
    preg_match("/^.*moved to <a href=\".*stockdetails\/([^\"]*)\">.*$/m", $response, $redirect);
	if($debug) var_dump($redirect);
    if(count($redirect)>0){
		$url_and_query=$the_url.$redirect[1];
		echo "stock $url_and_query<br />";
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $url_and_query );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $curl ); //utf8_decode( not necessary
		curl_close( $curl );
	}
    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
    //if($debug) echo "base .<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<td/", "\ntd", $response);
    $response=preg_replace("/<\/(td|table)>/", "\n", $response);
    if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";

    preg_match("/class=\"series-meta-observation-value\"[^>]*>\s*([^<]*)</m", $response, $value);
    if(count($value)<2){
        echo "<br />Empty value skipping, email sent...<br />";
        send_mail('bond_yield_curve_spread Bad crawl '.$symbol,'<br />Empty value, skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    $value=str_replace(",","",trim($value[1]));
    if(!isset($value) || $value=="" || $value=="-"){
        echo "<br />Empty value skipping, email sent...<br />";
        send_mail('bond_yield_curve_spread Error '.$symbol,'<br />Empty value(!isset, "" or "-"), skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    if($debug) echo "value: (".$value.")<br />";
	$details['value']=$value;

    //preg_match("/class=\"charttimestamp\"[^>]*>.*Currency .. ...[^ ]* ([^ ]*)</m", $response, $timestamp);
    preg_match("/class=\"series-meta-value\"[^>]*>\s*([^<]*)</m", $response, $timestamp);
    if(count($timestamp)<2){
        echo "<br />Empty timestamp skipping, email sent...1<br />";
        send_mail('sma Bad crawl '.$symbol,'<br />Empty timestamp, skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    $timestamp=substr(trim($timestamp[1]),0,10);
    if(!isset($timestamp) || $timestamp=="" || $timestamp=="-"){
        echo "<br />Empty timestamp skipping, email sent...2<br />";
        send_mail('bond_yield_curve_spread Error '.$symbol,'<br />Empty timestamp(!isset, "" or "-"), skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    if($debug) echo "timestamp: (".$timestamp.")<br />";
    //preg_match("/([0-9][^\/]*\/[0-9][^\/]*\/[0-9][0-9][0-9][0-9])/m", $timestamp, $value);
	if($debug) echo "timestamp: (".$value[1].")<br />";
	//$date=date_create($value[1]);
	$details['timestamp']=$timestamp;
	//date_format($date, 'Y-m-d');


	
	
	
	

	return $details;
}

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
	$debug=true;
}
	
if(isset($_REQUEST['symbol'])){
	$result=array();
	echo "individual value for: (".$_REQUEST['symbol'].")<br />";
	$result=get_details($_REQUEST['symbol'],$debug);
	//echo "<br />arr <pre>".print_r($result,true)."</pre><br />";
	echo "<br />arr <pre>".json_encode($result, JSON_PRETTY_PRINT)."</pre><br />";
}else{
	$the_url_query_arr = explode(",", $list);
	$alerts="";
	$alertsb="";
	foreach($the_url_query_arr as $key){
		$arr=array(); 
		$filename="bond_yield_curve_spread.".$key.".json";
		if(file_exists ( $filename )){
			echo "<br /><br />$filename exists -> reading...<br />";
			$arr = json_decode(file_get_contents($filename), true);
		}else{
			echo "<br/><br/><span style=\"color:red\">ERROR:</span>$filename does NOT exist -> using an empty array<br />";
		}
		$temp_result=get_details($key,$debug);
		if(!empty($temp_result)){
			if(!array_key_exists($temp_result['timestamp'],$arr)){
				$arr[$temp_result['timestamp']]=$temp_result['value'];
			}else{
				echo "<br />ALREADY EXISTS, existing ".$arr[$temp_result['timestamp']]." new ".$temp_result['value'];
			}
			$value=floatval($temp_result["value"]);
			echo "<br />bond_yield_curve_spread $key=$value";
			echo "<br />";
			if($value<=0){
				$alerts.=$key." bond_yield_curve_spread <0";
				$alertsb.=$key."<br /> value=".$value."<br />";
				echo $key."=$value   <0<br />";
			}
		}

		$arr_json_str=json_encode( $arr );

		// update 
		echo date('Y-m-d H:i:s')." updating json\n";
		$json_file = fopen($filename, "w") or die("Unable to open file $filename!");
		fwrite($json_file, $arr_json_str);
		fclose($json_file);

		// backup history (monthly)
		if(!file_exists( date("Y-m").'.'.$filename )){
			echo "creating backup: ".date("Y-m").".".$filename."<br />";
			$json_fileb = fopen(date("Y-m").".".$filename, "w") or die("Unable to open file ".$filename."!");
			fwrite($json_fileb, $arr_json_str);
			fclose($json_fileb);
		}

		//to avoid server ban
		sleep(0.30);
	}

	if($alerts!=""){
		send_mail(''.$alerts,
					'<br />'.$alertsb.'</pre><br /><br />',"hectorlm1983@gmail.com");
	}



}




echo date('Y-m-d H:i:s')." ending<br />";


?>