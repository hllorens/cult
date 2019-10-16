<?php

// USAGE: provides $stock_details_arr variable for other scripts to use
//        DEPENDS ON:
//            - $num_stocks_to_curl (tune it to avoid server timeout or google ban), e.g., set it to 5 stocks at a time
//            - stock_last_detail_updated.txt to indicate the last stock for which details were retrieved

require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)
require_once("email_config.php");

$list="sabadell-prudente-base-fi";
$list="$list,amundi-index-msci-europe-aec,amundi-msci-wrld-ae-c,amundi-index-sp-500-aec";


echo date('Y-m-d H:i:s')." starting<br />";

$timestamp_date=date("Y-m-d");

function get_details($symbol,$debug=false){
	$details=array();
	$the_url="https://www.investing.com/funds/";
	$the_url="https://es.investing.com/funds/";
    $url_and_query=$the_url.$symbol;
	$url_and_query="https://www.amundi.es/retail/product/view/LU0996179007";
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
		//curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $curl ); //utf8_decode( not necessary
		curl_close( $curl );
	}
    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
    //if($debug) echo "base .<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<td/", "\ntd", $response);
    $response=preg_replace("/<\/(td|table)>/", "\n", $response);
    if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";

    preg_match("/if=\"quotes_summary_current_data\"[^>]*>\s*([^<]*)</m", $response, $value);
    if(count($value)<2){
        echo "<br />Empty value skipping, email sent...<br />";
        //send_mail('Bad crawl '.$symbol,'<br />Empty value, skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    $value=str_replace(",","",trim($value[1]));
    if(!isset($value) || $value=="" || $value=="-"){
        echo "<br />Empty value skipping, email sent...<br />";
        //send_mail('Error '.$symbol,'<br />Empty value(!isset, "" or "-"), skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    if($debug) echo "value: (".$value.")<br />";
/*
    preg_match("/data-role=\"percentchange\"[^>]*>\s*([^<]*)</m", $response, $changep);
    if(count($changep)<2){
        echo "<br />Empty changep skipping, email sent...<br />";
        send_mail('Error '.$symbol,'<br />Empty changep, skipping...<br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    $changep=$changep[1];
    $changep=str_replace("%","",trim($changep));
    $changep=str_replace("+","",trim($changep));
    if($debug) echo "change: (".$changep.")<br />";
*/
    /*preg_match("/Expenses<[^<]*<[^<]*<[^<]*<p [^>]*>\s*([^<]*)\s*</m", $response, $expenses);
    if(count($expenses)>1){
        $expenses=str_replace(",","",trim($expenses[1]));
        if($debug) echo "expenses: (".$expenses.")<br />";
    }else{
        echo "<br />Empty range_52week skipping, email sent...<br />";
        send_mail('Error '.$symbol,'<br />Empty range_52week, skipping...<br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }*/

    $mktcap=0;
    $details['value']=$value;
    $details['session_change_percentage']=0;
    $details['expenses']=0;

/*		$mktcap_updated=false;
        preg_match("/>\s*Market Cap[^<]*<[^<]*<[^<]*<[^<]*<p [^>]*>\s*([^<]*)\s*</m", $response, $mktcap);
        if(count($mktcap)>1){
            $mktcap=trim($mktcap[1]);
            if($mktcap=="-" || $mktcap==""){
				$mktcap=0;
            }else{
				$mktcap=format_billions($mktcap);
				$mktcap_updated=true;
			}
        }else{
			echo "<br />Empty mktcap skipping, email sent if old...<br />";
			$mktcap=0;
        }
        
		if($mktcap_updated){
			// alert special
		}
    }*/


    // assignment to the array, will be 0 if went wrong
    //$details['mktcap']=$mktcap;
	
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
	echo " curr_stock_num_to_curl=$stock_last_detail_updated num_stocks_to_curl=$num_stocks_to_curl<br />";
	$stock_details_arr=array();

	$the_url_query_arr = explode(",", $list);
	$num_stocks_to_curl=min($num_stocks_to_curl,count($the_url_query_arr)); // make sure we do not duplicate...
	for ($i=0;$i<$num_stocks_to_curl;$i++){
		$current_num_to_curl=($stock_last_detail_updated+$i) % count($the_url_query_arr);
		$temp_result=get_details($the_url_query_arr[$current_num_to_curl],$debug);
		if(!empty($temp_result)) $stock_details_arr[$the_url_query_arr[$current_num_to_curl]]=$temp_result;
		// to avoid server ban
		sleep(0.15);
	}

	if($debug) echo "<br />arr ".print_r($stock_details_arr)."<br />";

	// update last updated number
	$stock_last_detail_updated=($stock_last_detail_updated+$num_stocks_to_curl) % count($the_url_query_arr); // modulo to avoid big nums...
	$stock_last_detail_updated_f = fopen("stock_last_detail_updated.txt", "w") or die("Unable to open file!");
	fwrite($stock_last_detail_updated_f, $stock_last_detail_updated);
	fclose($stock_last_detail_updated_f);*/
	echo "<br />under construction...<br />";
	$the_url_query_arr = explode(",", $list);
	get_details($the_url_query_arr[0],$debug);
}

echo date('Y-m-d H:i:s')." ending<br />";


?>