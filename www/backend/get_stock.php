<?php

// ----------------------------------------------------------
// Provider for javascript access without cross origin access
// ------------------------------------------------------------

date_default_timezone_set('Europe/Madrid');

//$stock = json_decode(file_get_contents("../../../cult-data-stock-google/stocks.formated.json"));
$stock = json_decode(file_get_contents("stocks.formatted.json"));

$stock_arr = array();

foreach ($stock as $key => $vals) {
        $stock_arr[]=$vals;
}


// to solve CORS
//if(isset($_REQUEST['allow_null_CORS'])){header("Access-Control-Allow-Origin: null");}
//else{header("Access-Control-Allow-Origin: *");}
//header("Access-Control-Allow-Origin: http://localhost:8100");
header("Access-Control-Allow-Origin: *");
// allow cookie passing in CORS (session maintenance)
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST"); // might want to add , PUT, DELETE, OPTIONS
header("Access-Control-Allow-Headers: *"); // Important X-Requested-With
header('Content-type: application/json');
echo json_encode( $stock_arr );


?>

