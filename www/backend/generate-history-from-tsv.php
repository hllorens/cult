<?php


date_default_timezone_set('Europe/Madrid');

$timestamp=date("Y-m-d H:i:s");


function get_year($date_str){
    return preg_replace('/((-)?\d+)\D.*/', '${1}', $date_str);
}

$activity_arr=array();
$activity_asoc_arr=array();
$pop_arr=array();

$data_directory='../../../cult-data-game-unified/';

$tsvfile="pagerank_en_2015-10.plus6.105k.sorted.tsv"; // lacks renaissance

if( isset($_GET['file']) ){
	$tsvfile=$_GET['file'];
}

$file = fopen($data_directory.$tsvfile,"r");
$num_words=0;
if($file!=false){
	while(! feof($file)){
	  $linearr=fgetcsv($file,0,"\t");
	  if(count($linearr)<2 || strlen($linearr[0])==0 || strlen($linearr[1])==0){
          echo "ignoring '".$line."'<br />";
          continue;
      }
	  $wiki=trim($linearr[1]);
	  $p=$linearr[0];
	  $pop_arr[$wiki]=(int) $p;
      //echo "$wiki <br />";
	}
	fclose($file);
}else{
	echo "file not found $data_directory.$tsvfile";exit();
}


$tsvfile="pageinlinkCounts_en_2015_plus99.170k.sorted.norm.tsv"; // lacks others... why?
$file = fopen($data_directory.$tsvfile,"r");
$num_words=0;
if($file!=false){
	while(! feof($file)){
	  $linearr=fgetcsv($file,0,"\t");
	  if(count($linearr)<2 || strlen($linearr[0])==0 || strlen($linearr[1])==0){
          echo "ignoring '".$line."'<br />";
          continue;
      }
	  $wiki=trim($linearr[1]);
	  $p=$linearr[0];
      if (!array_key_exists($wiki,$pop_arr)){
        $pop_arr[$wiki]=(int) $p;
      }
      //echo "$wiki <br />";
	}
	fclose($file);
}else{
	echo "file not found $data_directory.$tsvfile";exit();
}


$tsvfile="history.tsv";
if( isset($_GET['file']) ){
	$tsvfile=$_GET['file'];
}

$file = fopen($data_directory.$tsvfile,"r");
$num_words=0;

if($file!=false){
	while(! feof($file)){
	  $linearr=fgetcsv($file,0,"\t");
	  if(count($linearr)<4 || strlen($linearr[1])==0 || strlen($linearr[2])==0 || $linearr[3]=="wiki link") continue;
	  $fact=trim($linearr[0]);
	  $begin=get_year($linearr[1]);
	  $end=get_year($linearr[2]);
	  $wiki=trim($linearr[3]);
	  $p=0;
      //echo "search $wiki among ".count($pop_arr)."<br />";
      if (array_key_exists($wiki,$pop_arr)){
		$p=$pop_arr[$wiki];
	  }else{
        echo "Not found '$wiki'    ('$fact') <br />";
      }
	  $activity=array(
	  		"fact" => $fact,
	  		"begin" => $begin,
	  		"end" => $end,
	  		"wiki" => $wiki,
	  		"p" => $p,
	  	);
	  $activity_arr[]=$activity;
	  $activity_asoc_arr[$wiki]=$activity;
	}
	fclose($file);
}else{
	echo "file not found $data_directory.$tsvfile";exit();
}





$fp = fopen($data_directory.$tsvfile.'.json', 'w');
fwrite($fp, json_encode($activity_arr));
fclose($fp);

$fp = fopen($data_directory.$tsvfile.'-asoc.json', 'w');
fwrite($fp, json_encode($activity_asoc_arr));
fclose($fp);

$fp = fopen($data_directory.$tsvfile.'-pop-only.json', 'w'); //array_slice($pop_arr, 0, 25000, true) to make it 500kb
fwrite($fp, json_encode($pop_arr));
fclose($fp);



?>



