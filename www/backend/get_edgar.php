<?php

require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)
require_once("email_config.php");


echo date('Y-m-d H:i:s')." starting<br />";
$timestamp_date=date("Y-m-d H:i:s");

$cookieFile = "cookies.txt";
if(!file_exists($cookieFile)) {
    $fh = fopen($cookieFile, "w");
    fwrite($fh, "");
    fclose($fh);
}

function get_details($cik,$debug=false){
	$details=array();
	$url_and_query="https://www.sec.gov/Archives/edgar/data/".$cik."/index.json";
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
    /*preg_match("/^.*moved to <a href=\".*stockdetails\/([^\"]*)\">.*$/m", $response, $redirect);
	if($debug) var_dump($redirect);
    if(count($redirect)>0){
		$url_and_query=$the_url.$redirect[1];
		echo "stock $url_and_query<br />";
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $url_and_query );
		//curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $curl ); //utf8_decode( not necessary
		curl_close( $curl );
	}*/
    $response_arr = json_decode($response, true);
    if($debug) echo "aaa.<pre>".$response."</pre>";
    $response_arr=$response_arr['directory']['item'];
    


	// investing quotes_summary_current_data and id="last_last"
/*    preg_match("/id=\"last_last\"[^>]*>\s*([^<]*)</m", $response, $value);
    if(count($value)<2){
        echo "<br />Empty value skipping, email sent...<br />";
        send_mail('Bad crawl '.$cik,'<br />Empty value, skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    $value=str_replace(",","",trim($value[1]));
    if(!isset($value) || $value=="" || $value=="-"){
        echo "<br />Empty value skipping, email sent...<br />";
        send_mail('Error '.$cik,'<br />Empty value(!isset, "" or "-"), skipping...<pre>'.htmlspecialchars($response).'</pre><br /><br />',"hectorlm1983@gmail.com");
        return $details;
    }
    //if($debug) 
		echo "value: (".$value.")<br />";


	// TODO: we could ignore this and calculate from day before...
    preg_match("/id=\"fl_header_pair_pch\"[^>]*>\s*([^<]*)</m", $response, $changep);
    if(count($changep)<2){
        echo "<br />Empty changep skipping, email sent...<br />";
        send_mail('Error '.$cik,'<br />Empty changep, skipping...<br /><br />',"hectorlm1983@gmail.com");
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
*/
	
	return $response_arr;
}

function get_filing(){
	// if among files has "*.x10k.htm" then it is a 10k
	// fb-12312019x10k.htm and that is indeed the only page that serves (3mb?) because the txt alternative is way bigger.. (x5 or so)
	// the xml version is 1.9MB...
	// hay tb un xlsx 
	// hay hojas R Q PARECEN ÚTILES...
	// R2 y R4 SON de lo más útiles a falta del número de acciones... habría q ver si están en todos... sino el xml será lo más práctico...
}

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
	$debug=true;
}
	
if(isset($_REQUEST['cik'])){
	$result=array();
	echo "individual details for: (".$_REQUEST['cik'].")<br />";
	$result=get_details($_REQUEST['cik'],$debug);
	if($debug) 
		echo "<br />result:<br /><pre>".json_encode($result, JSON_PRETTY_PRINT)."</pre><br />";
	foreach($result as $item){
        echo " ".$item['name']."<br />";
    }


}else{
  echo "indicate cik=cik";
}




echo date('Y-m-d H:i:s')." ending<br />";


?>