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
$last_lustrum=$last_year-4;
$last_decade=$last_year-9;
$last_2decade=$last_year-19;

//echo "dates considered: $last_year $previous_year $last_lustrum $last_decade <br />";

$data_directory='../../../cult-data';
$indicator='population';
$indicator_sf='population';
$data_source='wb';

if(isset($_REQUEST['indicator']) ){$indicator=$_REQUEST['indicator'];}
if(isset($_REQUEST['indicator_sf']) ){$indicator_sf=$_REQUEST['indicator_sf'];}
if(isset($_REQUEST['data_source']) ){$data_source=$_REQUEST['data_source'];}

$data_arr=array();
$data_arr['indicator']=$indicator;
$data_arr['indicator_sf']=$indicator_sf;
$data_arr['last_year']=$last_year;
$data_arr['previous_year']=$previous_year;
$data_arr['last_lustrum']=$last_lustrum;
$data_arr['last_decade']=$last_decade;
$data_arr['last_2decade']=$last_2decade;
$data_arr['data_source']=$data_source;
$data_arr['data']=array();
$data_arr['data']['last_year']=array();
$data_arr['data']['previous_year']=array();
$data_arr['data']['last_lustrum']=array();
$data_arr['data']['last_decade']=array();
$data_arr['data']['last_2decade']=array();
foreach(array_filter(glob($data_directory.'/*_'.$indicator.'_'.$data_source.'.json'), 'is_file') as $file) {
	$string = file_get_contents($file);
	$json_a = json_decode($string, true);
	foreach ($json_a[1] as $item) {
		if($item['date'] == $last_year ) $data_arr['data']['last_year'][$item['country']['value']]=$item["value"];
		else if($item['date'] == $previous_year ) $data_arr['data']['previous_year'][$item['country']['value']]=$item["value"];
		else if($item['date'] == $last_lustrum ) $data_arr['data']['last_lustrum'][$item['country']['value']]=$item["value"];
		else if($item['date'] == $last_decade ) $data_arr['data']['last_decade'][$item['country']['value']]=$item["value"];
		else if($item['date'] == $last_2decade ) $data_arr['data']['last_2decade'][$item['country']['value']]=$item["value"];
	}
}

// TODO guardar esto en un fichero? llamar a este php desde otro q genera los ficheros

header('Content-type: application/json');
echo json_encode( $data_arr );


?>
