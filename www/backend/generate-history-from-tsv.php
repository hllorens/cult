<?php

date_default_timezone_set('Europe/Madrid');

$timestamp=date("Y-m-d H:i:s");


function get_year($date_str){
    return preg_replace('/((-)?\d+)\D.*/', '${1}', $date_str);
}

$activity_arr=array();

$data_directory='../../../cult-data-game-unified/';
$tsvfile="history.tsv";
if( isset($_GET['file']) ){
	$tsvfile=$_GET['file'];
}

$file = fopen($data_directory.$tsvfile,"r");
$num_words=0;

if($file!=false){
	while(! feof($file)){
	  $linearr=fgetcsv($file,0,"\t");
	  if(count($linearr)<3 || strlen($linearr[1])==0 || strlen($linearr[2])==0) continue;
	  $fact=trim($linearr[0]);
	  $begin=get_year($linearr[1]);
	  $end=get_year($linearr[2]);
	  $activity=array(
	  		"fact" => $fact,
	  		"begin" => $begin,
	  		"end" => $end,
	  	);
	  $activity_arr[]=$activity;
	}
	fclose($file);
}else{
	echo "file not found";exit();
}


header('Content-type: text/plain');


$fp = fopen($data_directory.$tsvfile.'.json', 'w');
fwrite($fp, json_encode($activity_arr));
fclose($fp);




?>



