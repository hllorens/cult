<?php

// Reduce the json files so that each one has an indicator e.g. game_population.json
// Then the format is very simple like:

/* {
	"last_year": 2014,
	"data_source": "wb",
	"data":{
		"last_year": {  //most important part
			"es": value,
			"...": value
		},
		"previous_year":{
			...
		}
		"last_lustrum":{
			...
		}
		"last_decade":{
			... only if data available...
		}
		"last_2decade":{
			... only if data available...
		}
	}
	
	}
	
	QUESTIONS CAN ONLY BE OF SAME INDICATOR AND EITHER OF:
	    -SAME YEAR FOR DIFFERENT COUNTRIES
		- SAME COUNTRY FOR DIFFERENT YEARS (ONLY PREVIOUS, LAST LUSTRUM, LAST_DECADE)
*/

$last_year=intval(date("Y"))-1;
$previous_year=$last_year-1;
$last_lustrum=$last_year-5;
$last_decade=$last_year-10;
$last_2decade=$last_year-20;

echo "$last_year $previous_year $last_lustrum $last_decade";

$data_directory='/home/hector/cognitionis.com/cult-data';
$indicator='population';
$data_source='wb';

if(isset($_REQUEST['indicator']) ){$indicator=$_REQUEST['indicator'];}
if(isset($_REQUEST['data_source']) ){$data_source=$_REQUEST['data_source'];}

$data_arr=array();
$data_arr['last_year']=$last_year;
$data_arr['data_source']=$data_source;
$data_arr['data']=array();
$data_arr['data']['last_year']=array();
$data_arr['data']['previous_year']=array();
$data_arr['data']['last_lustrum']=array();
$data_arr['data']['last_decade']=array();
$data_arr['data']['last_2decade']=array();

foreach(array_filter(glob($data_directory.'/*_'.$indicator.'_'.$data_source.'.json'), 'is_file') as $file) {
    //echo $file."<br />";
	$string = file_get_contents($file);
	$json_a = json_decode($string, true);

	foreach (array($json_a[1]) as $item) { // ver q falla aquí
		if($item['date'] == $last_year ) $data_arr['data']['last_year'][$item['country']['value']]=$item["value"];
		//echo "<br />".$item['date']."<br />";
	}

}

//$data_arr=array_filter(glob($data_directory.'/*_'.$indicator.'_'.$data_source.'.json'), 'is_file');
//print_r(data_arr);

//header('Content-type: application/json');
echo json_encode( $data_arr );

?>
