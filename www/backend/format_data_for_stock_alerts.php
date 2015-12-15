<?php

$data_directory='../../../cult-data-stock-google';

$data_object=array();
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
	$data_object[$item['t'].':'.$item['e']]=$symbol_object;
}

header('Content-type: application/json');
echo json_encode( $data_object );


?>
