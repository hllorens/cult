<?php

$data_directory='../../../cult-data-stock-google';

$data_object=array();
$file2=$data_directory.'/dividend_yield.json';
$string2 = file_get_contents($file2);
$json_a2 = json_decode($string2, true);
$file=$data_directory.'/stocks.json';
$string = file_get_contents($file);
$json_a = json_decode($string, true);

foreach ($json_a as $item) {
	$symbol_object=array();
	$symbol_object['name']=$item['t'];
	$symbol_object['market']=$item['e'];
	$symbol_object['value']=$item['l'];
	$symbol_object['session_change']=$item['c'];
	$symbol_object['session_change_percentage']=$item['cp'];
	$symbol_object['yield']=$json_a2[$item['e'].':'.$item['t']]['yield'];
	$symbol_object['dividend']=$json_a2[$item['e'].':'.$item['t']]['dividend'];
	$symbol_object['eps']=$json_a2[$item['e'].':'.$item['t']]['eps'];
	$symbol_object['per']=$json_a2[$item['e'].':'.$item['t']]['per'];
	$symbol_object['roe']=$json_a2[$item['e'].':'.$item['t']]['roe'];
	$data_object[$item['t'].':'.$item['e']]=$symbol_object;
}

header('Content-type: application/json');
echo json_encode( $data_object );


?>
