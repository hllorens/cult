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

// if among files has "*.x10k.htm" then it is a 10k
// fb-12312019x10k.htm and that is indeed the only page that serves (3mb?) because the txt alternative is way bigger.. (x5 or so)
// the xml version is 1.9MB...
// hay tb un xlsx 
// hay hojas R Q PARECEN ÚTILES...
// R2 y R4 SON de lo más útiles a falta del número de acciones... habría q ver si están en todos... sino el xml será lo más práctico...
function is_10k($filing){
	foreach($filing as $item){
        //echo " ".$item['name']."<br />";
		if(substr($item['name'], -8) === "x10k.htm"){return true;}
	}	
	return false;
}
function get_10k_name($filing){
	foreach($filing as $item){
        echo " ".$item['name']."<br />";
		if(substr($item['name'], -8) === "x10k.htm"){return str_replace("x10k.htm", "", $item['name']);}
	}	
	return false;
}


// XML size is ok, html might be as well, thing is we need to see how to parse that, 
Edit:
0

try item->children('edgar', true)->... to parse them. I think that will allow you to use simplexml. The edgar: is called namespacing, and is used quite a bit with xml files. I had the same issue a while back and this fixed it for me,
Do I need to register all the namespace in the file? How could I get startDate and endDate infor?

No you only have to register the namespaces you need i.e. http://www.xbrl.org/2003/instance

$xmldoc = new DOMDocument();
$xmldoc->load("http://www.sec.gov/Archives/edgar/data/27419/000110465911031717/tgt-20110430.xml");
$xpath = new DOMXPath($xmldoc);
$xpath->registerNamespace("xbrli", "http://www.xbrl.org/2003/instance");
$nodelist = $xpath->query("/xbrli:xbrl/xbrli:context[@id='D2010Q1']/xbrli:period"); // much faster than //xbrli:context and //xbrli:startDate
if($nodelist->length === 1)
{
    $period = $nodelist->item(0);
    $nodelist = $xpath->query("xbrli:startDate", $period);
    $startDate = $nodelist->length === 1 ? $nodelist->item(0)->nodeValue : null;
    $nodelist = $xpath->query("xbrli:endDate", $period);
    $endDate = $nodelist->length === 1 ? $nodelist->item(0)->nodeValue : null;
    printf("%s<br>%s", $startDate, $endDate);
}
else
    ; // not found or more than one <xbrli:context id='D2010Q1'><xbrli:period>

You'll need to register the xbrli namespace as well, similar to what you've done with us-gaap. From WikiPedia I found this xmlns:xbrli="http://www.xbrl.org/2003/instance"



function get_revenue($url,$debug=false){
	$details=array();
	$url_and_query="https://www.sec.gov/Archives/edgar/data/".$url;
    echo "stock $url_and_query<br />";
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url_and_query );
	curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13'); // help needed for investing
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ); // save in variable, no stdout
    $response = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );
	$xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
	$json = json_encode($xml);
	$response_arr = json_decode($json,true);
	//if($debug) 
		echo "aaa.<pre>".$json."</pre>";
    //$response_arr=$response_arr['directory']['item'];
}

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
	$debug=true;
}
	
if(isset($_REQUEST['cik'])){
	$result=array();
	echo "individual details for: (".$_REQUEST['cik'].")<br />";
	$result=get_details($_REQUEST['cik'], $debug);
	if($debug) 
		echo "<br />result:<br /><pre>".json_encode($result, JSON_PRETTY_PRINT)."</pre><br />";
	foreach($result as $item){
        //echo " ".$item['name']."<br />";
		$filing=get_details($_REQUEST['cik']."/".$item['name'],$debug);
		if(is_10k($filing)){
			echo "it is a 10k!!<br />";
			$basename=get_10k_name($filing);
			echo "revenue=".get_revenue($_REQUEST['cik']."/".$item['name']."/".$basename."x10k_htm.xml",$debug);
			break;
		}else{
			echo "    not a 10k<br />";
		}
		sleep(0.10);
	}


}else{
  echo "indicate cik=cik";
}




echo date('Y-m-d H:i:s')." ending<br />";


?>