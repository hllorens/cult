<?php

require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)
require_once 'stock_list.php';
require_once("email_config.php");

echo date('Y-m-d H:i:s')." starting<br />";

$timestamp_date=date("Y-m-d H:i:s");


$list="INDEXSP:.INX";
$list="$list,INDEXSTOXX:SX5E";
$list="$list,SHA:000300";
$list="$list,INDEXBME:IB";


$cookieFile = "cookies.txt";
if(!file_exists($cookieFile)) {
    $fh = fopen($cookieFile, "w");
    fwrite($fh, "");
    fclose($fh);
}

function get_details($symbol,$debug=false){
	$details=array();
	$the_url="https://www.msn.com/en-us/money/stockdetails/";
    $url_and_query=$the_url.get_msn_quote($symbol);
    $query_arr=explode(":",$symbol);
    $name=$query_arr[1];
    $market=$query_arr[0];
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

    preg_match("/data-role=\"currentvalue\"[^>]*>\s*([^<]*)</m", $response, $value);
    if(count($value)<2){
        echo "<br />Empty value skipping, email sent...<br />";
        send_mail('Bad crawl '.$symbol,'<br />Empty value, skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    $value=str_replace(",","",trim($value[1]));
    if(!isset($value) || $value=="" || $value=="-"){
        echo "<br />Empty value skipping, email sent...<br />";
        send_mail('Error '.$symbol,'<br />Empty value(!isset, "" or "-"), skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    if($debug) echo "value: (".$value.")<br />";
	$details['value']=$value;

    //preg_match("/class=\"charttimestamp\"[^>]*>.*Currency .. ...[^ ]* ([^ ]*)</m", $response, $timestamp);
    preg_match("/class=\"charttimestamp\"[^>]*>\s*([^<]*)</m", $response, $timestamp);
    if(count($timestamp)<2){
        echo "<br />Empty timestamp skipping, email sent...1<br />";
        send_mail('Bad crawl '.$symbol,'<br />Empty timestamp, skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    $timestamp=trim($timestamp[1]);
    if(!isset($timestamp) || $timestamp=="" || $timestamp=="-"){
        echo "<br />Empty timestamp skipping, email sent...2<br />";
        send_mail('Error '.$symbol,'<br />Empty timestamp(!isset, "" or "-"), skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    if($debug) echo "timestamp: (".$timestamp.")<br />";
    preg_match("/([0-9][^\/]*\/[0-9][^\/]*\/[0-9][0-9][0-9][0-9])/m", $timestamp, $value);
	if($debug) echo "timestamp: (".$value[1].")<br />";
	$date=date_create($value[1]);
	$details['timestamp']=date_format($date, 'Y-m-d');


	
	
	
	

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
	$arr=array(); 
	$filename='sp500close.active.json';
	if(file_exists ( $filename )){
		echo "$filename exists -> reading...<br />";
		$arr = json_decode(file_get_contents($filename), true);
	}else{
		echo "<br/><br/><span style=\"color:red\">ERROR:</span>$filename does NOT exist -> using an empty array<br />";
	}
	//$the_url_query_arr = explode(",", $list);
	$alerts="";
	$alertsb="";
	$key="INDEXSP:.INX";
	//foreach($the_url_query_arr as $key){
		$temp_result=get_details($key,$debug);
		if(!empty($temp_result)){
			if(!array_key_exists($temp_result['timestamp'],$arr)){
				$arr[$temp_result['timestamp']]=$temp_result['value'];
			}else{
				echo "ALREADY EXISTS, existing ".$arr[$temp_result['timestamp']]." new ".$temp_result['value'];
			}
			// calculate gold/death and %diff alert
			$last_200=array_slice(array_values($arr),-200,200);
			$last_50=array_slice($last_200,-50,50);
			$sma_200=array_sum($last_200)/count($last_200);
			$sma_50=array_sum($last_50)/count($last_50);
			$diff_percentage=($sma_50/$sma_200)-1;
			echo "<br />sma_200=$sma_200 (".count($last_200).") sma_50=$sma_50(".count($last_50).") diff=$diff_percentage";
			echo "<br />";
			if($diff_percentage<0.019 && $diff_percentage>-0.01){
				$alerts.=" cross-diff";
				$alertsb.="<br /> cross-diff=".$diff_percentage." (>0 risk of deathcross!!)<br />";
				echo "<br /> cross-diff=".$diff_percentage." (>0 risk of deathcross!!)<br />";
			}
			$last_200a=array_slice(array_values($arr),-201,200);
			$last_50a=array_slice($last_200,-51,50);
			$sma_200a=array_sum($last_200a)/count($last_200a);
			$sma_50a=array_sum($last_50a)/count($last_50a);
			echo "<br />sma_200a=$sma_200a (".count($last_200).") sma_50a=$sma_50a(".count($last_50).")";
			echo "<br />";
			if(($sma_50-$sma_200)*($sma_50a-$sma_200a)<0){
				$cross_type="DEATH";
				if(($sma_50-$sma_200)>0){$cross_type="GOLDEN";}
				$alerts.="$cross_type CROSS";
				$alertsb.="<br /> <b>$cross_type CROSS</b>!!";
				echo "<br /> <b>$cross_type CROSS</b>!!";
			}
		}//else{
		//	break;
		//}
		// to avoid server ban
	//	sleep(0.30);
	//}

	if($alerts!=""){
		send_mail(''.$alerts,
					'<br />'.$alertsb.'</pre><br /><br />',"hectorlm1983@gmail.com");
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


}




echo date('Y-m-d H:i:s')." ending<br />";


?>