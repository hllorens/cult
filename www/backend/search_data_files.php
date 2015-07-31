<?php

$data_directory='/home/hector/cognitionis.com/cult-data';
$indicator='population';
$data_source='wb';

if(isset($_REQUEST['indicator']) ){$indicator=$_REQUEST['indicator'];}
if(isset($_REQUEST['data_source']) ){$data_source=$_REQUEST['data_source'];}

$data_arr=array();
foreach(array_filter(glob($data_directory.'/*_'.$indicator.'_'.$data_source.'.json'), 'is_file') as $file) {
    //echo $file."<br />";
	$data_arr[]=str_replace("/home/hector/cognitionis.com","",$file);
}

//$data_arr=array_filter(glob($data_directory.'/*_'.$indicator.'_'.$data_source.'.json'), 'is_file');
//print_r(data_arr);

header('Content-type: application/json');
echo json_encode( $data_arr );



?>
