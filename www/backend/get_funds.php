<?php

// USAGE: provides $stock_details_arr variable for other scripts to use
//        DEPENDS ON:
//            - $num_stocks_to_curl (tune it to avoid server timeout or google ban), e.g., set it to 5 stocks at a time
//            - stock_last_detail_updated.txt to indicate the last stock for which details were retrieved

require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)
require_once("email_config.php");

$list="sabadell-prudente-base-fi";
$list="$list,amundi-index-msci-europe-aec,amundi-msci-wrld-ae-c,amundi-index-sp-500-aec";

$list_details["sabadell-prudente-base-fi"]=array(
	"isin"=> "-",
	"morningstar-id"=> "-",
	"high"=> "11",
	"low"=> "-"
);
$list_details["amundi-index-msci-europe-aec"]=array(
	"isin"=> "-",
	"morningstar-id"=> "-",
	"high"=> "-",
	"low"=> "-"
);
$list_details["amundi-msci-wrld-ae-c"]=array(
	"isin"=> "LU0996182563",
	"morningstar-id"=> "F00000T66U",
	"high"=> "-",
	"low"=> "-"
);
$list_details["amundi-index-sp-500-aec"]=array(
	"isin"=> "LU0996179007",
	"morningstar-id"=> "F00000T7PZ",
	"high"=> "-",
	"low"=> "-"
);

echo date('Y-m-d H:i:s')." starting<br />";

$timestamp_date=date("Y-m-d H:i:s");

$cookieFile = "cookies.txt";
if(!file_exists($cookieFile)) {
    $fh = fopen($cookieFile, "w");
    fwrite($fh, "");
    fclose($fh);
}

function get_details($symbol,$debug=false){
	$details=array();
	$the_url="https://www.investing.com/funds/";
	//$the_url="https://es.investing.com/funds/"; // probar esta version
    $url_and_query=$the_url.$symbol;
	//$url_and_query="https://www.finect.com/fondos-inversion/LU0996179007-Amundi_is_sp_500_aec"; //alt
	//$url_and_query="https://www.morningstar.es/es/funds/snapshot/snapshot.aspx?id=F00000T7PZ"; // sino esta
	//$url_and_query="https://www.amundi.es/retail/product/view/LU0996179007"; // esta ya he comprobado q sí que va...
    echo "stock $url_and_query<br />";
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url_and_query );
	curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13'); // help needed for investing
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ); // save in variable, no stdout
	//curl_setopt($curl, CURLOPT_REFERER, 'http://www.cognitionis.com/'); // not needed yet
	//curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); // Cookie aware not needed yet
	//curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile); // Cookie aware not needed yet
    $response = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );
    preg_match("/^.*moved to <a href=\".*stockdetails\/([^\"]*)\">.*$/m", $response, $redirect);
	if($debug) var_dump($redirect);
    if(count($redirect)>0){
		$url_and_query=$the_url.$redirect[1];
		echo "stock $url_and_query<br />";
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $url_and_query );
		//curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $curl ); //utf8_decode( not necessary
		curl_close( $curl );
	}
    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
    //if($debug) echo "base .<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<td/", "\ntd", $response);
    $response=preg_replace("/<\/(td|table)>/", "\n", $response);
    if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";

	// investing quotes_summary_current_data and id="last_last"
    preg_match("/id=\"last_last\"[^>]*>\s*([^<]*)</m", $response, $value);
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
    //if($debug) 
		echo "value: (".$value.")<br />";


	// TODO: we could ignore this and calculate from day before...
    preg_match("/id=\"fl_header_pair_pch\"[^>]*>\s*([^<]*)</m", $response, $changep);
    if(count($changep)<2){
        echo "<br />Empty changep skipping, email sent...<br />";
        send_mail('Error '.$symbol,'<br />Empty changep, skipping...<br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    $changep=$changep[1];
    $changep=str_replace("%","",trim($changep));
    $changep=str_replace("+","",trim($changep));
    //if($debug) 
		echo "change: (".$changep.")<br />";



    $details['v']=$value;
    $details['c']=$changep;
	//$details['date']=date("Y-m-d H:i:s");

	
	return $details;
}

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
	$debug=true;
}
	
if(isset($_REQUEST['symbol'])){
	$result=array();
	echo "individual details for: (".$_REQUEST['symbol'].")<br />";
	$result=get_details($_REQUEST['symbol'],$debug);
	//echo "<br />arr <pre>".print_r($result,true)."</pre><br />";
	echo "<br />arr <pre>".json_encode($result, JSON_PRETTY_PRINT)."</pre><br />";
}else{
	/*$num_stocks_to_curl=5;
	$stock_last_detail_updated=0;
	if(file_exists ( 'stock_last_detail_updated.txt' )){
		$stock_last_detail_updated=intval(fgets(fopen('stock_last_detail_updated.txt', 'r')));
	}
	echo " curr_stock_num_to_curl=$stock_last_detail_updated num_stocks_to_curl=$num_stocks_to_curl<br />";*/

	$funds_arr=array(); 
	if(file_exists ( 'funds.json' )){
		echo "funds.json exists -> reading...<br />";
		$funds_arr = json_decode(file_get_contents('funds.json'), true);
	}else{
		echo "<br/><br/><span style=\"color:red\">ERROR:</span>funds.json does NOT exist -> using an empty array<br />";
	}


	$the_url_query_arr = explode(",", $list);
	$alerts="";
	$alertsb="";
	foreach($the_url_query_arr as $key){
		$temp_result=get_details($key,$debug);
		if(!empty($temp_result)){
			if(!array_key_exists($key,$funds_arr)) $funds_arr[$key]=array();
			if(array_key_exists($key,$list_details)){
				if($list_details[$key]['high']!="-" && floatval($temp_result['v'])>floatval($list_details[$key]['high'])){
					echo "<br />&nbsp; alert high<br /><br />";
					$alerts.=" $key +val ";
					$alertsb.="<br /> $key +value ".$temp_result['v']."<br />";
				}else if($list_details[$key]['low']!="-" && floatval($temp_result['v'])<floatval($list_details[$key]['low'])){
					echo "<br />&nbsp; alert low<br /><br />";
					$alerts.=" $key -val";
					$alertsb.="<br /> $key -value ".$temp_result['v']."<br />";
				}
			}
			$funds_arr[$key]['v']=$temp_result['v'];
			$funds_arr[$key]['c']=$temp_result['c'];
			$funds_arr[$key][substr($timestamp_date,0,10)]=$temp_result;
		}else{
			break;
		}
		// to avoid server ban
		sleep(0.30);
	}

	if($alerts!=""){
		send_mail('Fund '.$alerts,'<br />'.$alertsb.'</pre><br /><br />',"hectorlm1983@gmail.com");
	}

	$funds_arr_json_str=json_encode( $funds_arr );

	// update funds.json
	echo date('Y-m-d H:i:s')." updating json\n";
	$json_file = fopen("funds.json", "w") or die("Unable to open file funds.json!");
	fwrite($json_file, $funds_arr_json_str);
	fclose($json_file);

	// backup history (monthly)
	if(!file_exists( date("Y-m").'.funds.json' )){
		echo "creating backup: ".date("Y-m").".funds.json<br />";
		$json_fileb = fopen(date("Y-m").".funds.json", "w") or die("Unable to open file funds.json!");
		fwrite($json_fileb, $funds_arr_json_str);
		fclose($json_fileb);
	}


}




echo date('Y-m-d H:i:s')." ending<br />";


?>