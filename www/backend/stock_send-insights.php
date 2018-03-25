
<?php

// extra security
//if(!isset($_GET['autosecret']) || $_GET['autosecret']!='1secret'){
//	exit("Permission denied");
//}


$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

if(!file_exists(substr(dirname(__FILE__), 0,strpos(dirname(__FILE__), '/')).'stocks.formatted.json')){
	exit("Error: ERROR.log not found ".substr(dirname(__FILE__), 0,strpos(dirname(__FILE__), '/')).'stocks.formated.json'); //dirname(__FILE__).'/../data/ERROR.log');
}
$data_object=array();
$string = file_get_contents('stocks.formatted.json');
$stocks = json_decode($string, true);
 
#error_reporting(E_STRICT);
date_default_timezone_set('Europe/Madrid');


$timestamp_date=date("Y-m-d");
$timestamp=date("Y-m-d H:i");



$facts="";
$body="";
$usr_decoded="hectorlm1983@gmail.com";


$fact="";

$usdeur_last_q_diff=0;
if(array_key_exists('usdeur_hist',$stocks['GOOG:NASDAQ']) && count($stocks['GOOG:NASDAQ']['usdeur_hist'])>1){
    $usdeur_last_q_diff=(floatval(end($stocks['GOOG:NASDAQ']['usdeur_hist'])[1])/floatval($stocks['GOOG:NASDAQ']['usdeur_hist'][count($stocks['GOOG:NASDAQ']['usdeur_hist'])-2][1]))-1;
    $usdeur_last_q_diff=$usdeur_last_q_diff*100;
}
if(array_key_exists('usdeur_change',$stocks['GOOG:NASDAQ']) && (abs(floatval($stocks['GOOG:NASDAQ']['usdeur_change']))>0.02 || abs($usdeur_last_q_diff)>4)){
    $facts.="usdeur ";
    $body.=" <br /><b>usdeur</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['usdeur']), 2, ".", "");
    $body.=" <br /><b>usdeur_c</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['usdeur_change'])*100, 2, ".", "")."%";
    $body.=" <br /><b>lastQdiff</b>: ".number_format($usdeur_last_q_diff, 2, ".", "")."%";
}

/*if(array_key_exists('btcusd_change',$stocks['GOOG:NASDAQ']) && (abs(floatval($stocks['GOOG:NASDAQ']['btcusd_change']))>0.10 || abs(floatval($stocks['GOOG:NASDAQ']['btcusd_hist_last_diff']))>400)){
    $facts.="btcusd ";
    $body.=" <br /><b>btcusd</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['btcusd']), 2, ".", "");
    $body.=" <br /><b>btceur</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['btcusd'])*floatval($stocks['GOOG:NASDAQ']['usdeur']), 2, ".", "");
    $body.=" <br /><b>btcusd_c</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['btcusd_change'])*100, 2, ".", "")."%";
    $body.=" <br /><b>lastQdiff</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['btcusd_hist_last_diff']), 2, ".", "")."%";
}*/



if($facts!=""){
    if($debug) echo '  '.$body.'<br />';
    send_mail($facts,$body,$usr_decoded);
}





?>