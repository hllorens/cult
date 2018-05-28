<?php

// only keep the json properties that we currently use
require_once("email_config.php");

date_default_timezone_set('Europe/Madrid');
$timestamp_date=date("Y-m-d");
$timestamp_simplif=date("d H:i");
$timestamp_quarter=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 3) % 4) + 1 );
$timestamp_half=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 6) % 2) + 1 );

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

$stocks_financials_arr=array(); 
if(file_exists ( 'stocks_financialsr.json' )){
    echo "stocks_financialsr.json exists -> reading...<br />";
    $stocks_financials_arr = json_decode(file_get_contents('stocks_financialsr.json'), true);
    if($stocks_financials_arr==null){echo "ERROR: financials. Exit";
        send_mail('ERROR !financialsR',"stocks_financialsr.json does not exist.<br /><br />","hectorlm1983@gmail.com");
        exit(1);}
}else{
    echo "stocks_financialsr.json does NOT exist -> using an empty array<br />";
}



echo date('Y-m-d H:i:s')." start stock_clean.php<br />";

require_once 'stock_list.php';
require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)

$stocks_clean_arr=array();
foreach ($stocks_financials_arr as $key => $item) {
	$symbol_object=array();
    if($debug) echo "<br />doing ".$item['market'].":".$item['name']."<br />";
	$years_arr=array();
	foreach (array_keys($stocks_financials_arr[$item['market'].":".$item['name']]) as $key){
		if(in_array(substr($key,0,4),$years_arr)){
			echo "<br />ERROR: DUP year ".substr($key,0,4)."<br />";
            send_mail('ERROR check financialsR DUP year'.$item['name'],$item['name']." ERROR dup year ".substr($key,0,4)."<br /><br />","hectorlm1983@gmail.com");
			exit(1);
		}
        if($key[0]=="2"){
            if(count($years_arr)>0 && intval(end($years_arr))!=(intval(substr($key,0,4))-1)){
                echo "<br />ERROR: MISSING year ".(intval(substr($key,0,4))-1)."<br />";
                send_mail('ERROR MISSING year financialsR '.$item['name'],$item['name']." MISSING year ".(intval(substr($key,0,4))-1)."<br /><br />","hectorlm1983@gmail.com");
                exit(1);
            }
            $years_arr[]=substr($key,0,4);
        }
	}
    if($debug) echo "<br />".implode(" ",$years_arr)."<br />";
    foreach ($stocks_financials_arr[$item['market'].":".$item['name']] as $key2 => $item2) {
        if($key2[0]=="2" && array_key_exists('Total Revenue',$item2)){
            if(substr($key2,5,1)!="1"){
                if($debug) echo $item['name']." $key2 ... suspicious<br />";
            }
            $symbol_object[$key2]=array();
            $symbol_object[$key2]['Total Revenue']=$item2['Total Revenue'];
			if(floatval($item2['Operating Income'])==0 || floatval($item2['Total Revenue'])==0){
                echo "&nbsp; ".$item['name']." $key2 revenue=".$item2['Total Revenue']." operating_income=".$item2['Operating Income']."... ERROR, MANUAL REVIEW NEEDED<br />";
                send_mail('ERROR financialsR revenue=0 '.$item['name'],"&nbsp; ".$item['name']." $key2 revenue=".$item2['Total Revenue']." operating_income=".$item2['Operating Income']."... ERROR, MANUAL REVIEW NEEDED<br /><br />","hectorlm1983@gmail.com");
			}
            $symbol_object[$key2]['Operating Income']=$item2['Operating Income'];
            $symbol_object[$key2]['Net Income']=$item2['Net Income'];
        }else if($key2[0]=="2"){
            echo "FATAL ERROR, financials but not revenue for ".$item['name']." $key2 ".implode(" ",array_keys($item2));
            send_mail('ERROR financialsR !revenue '.$item['name'],$item['name']." !revenue ".implode(" ",array_keys($item2))."<br /><br />","hectorlm1983@gmail.com");
            exit(1);
        }
    }
    $symbol_object['name']=$item['name'];
    $symbol_object['market']=$item['market'];
    $stocks_clean_arr[$item['market'].':'.$item['name']]=$symbol_object;
    //if($item['name']=='CMPR') break;
}
// --------------------------------------------- 

if($debug){
	$stocks_clean_arr_json_str=json_encode( $stocks_clean_arr );
	$stocks_clean_json_file = fopen("stocks_financials_cleanr.json", "w") or die("Unable to open file stocks_clean.json!");
	fwrite($stocks_clean_json_file, $stocks_clean_arr_json_str);
	fclose($stocks_clean_json_file);
}
echo "<br />".date('Y-m-d H:i:s')." done with stocks_clean.json <br />";

?>