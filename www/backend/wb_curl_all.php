<?php

require_once 'wb_curl.php';
require_once("email_config.php");
require_once 'stock_helper_functions.php';

echo date('Y-m-d H:i:s')." starting wb_curl_all.php<br />";

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

// eu europoean union (including uk, sweden, switzerland)
// xc euro area (only countries with EURO currency)
$countries=[ 'wld', 'us', 'xc', 'cn', 'es', 'gb', 'de', 'in', 'ru' ];
// discarded: eu, fr, de, it, jp ca br ru ind cn zaf aus kr sau ar mx tur idn es pt gr be nld dk fi swe nor pl che afg pak egy );

$indicators=array();
$indicators['population']='SP.POP.TOTL';
$indicators['surfacekm']='AG.SRF.TOTL.K2';
$indicators['gdp']='NY.GDP.MKTP.CD';
$indicators['inflation']='FP.CPI.TOTL.ZG';
//$indicators['employed']='SL.EMP.TOTL.SP.ZS';
$indicators['unemployed']='SL.UEM.TOTL.ZS';
//$indicators['pop65']='SP.POP.65UP.TO.ZS';

$wb_data=array();
foreach ($countries as $country){
	foreach ($indicators as $key => $value){
		$wb_data=get_wb($value,$country);
		// to avoid ban
		sleep(0.1);
	}
}

// show something wb_data?

// backup history (yearly)
if(!file_exists( date("Y").'.wb.json' )){
    echo "creating backup: ".date("Y").".wb.json<br />";
    $fileb = fopen(date("Y").".wb.json", "w") or die("Unable to open file Y.wb.json!");
    fwrite($fileb, json_encode($wb_data));
    fclose($fileb);
}

echo date('Y-m-d H:i:s')." ending wb_curl_all.php<br />";
?>