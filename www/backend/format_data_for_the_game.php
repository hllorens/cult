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

function country_translation($country_name){
    if($country_name=="Egypt, Arab Rep.") return "Egypt";
    if($country_name=="Korea, Rep.") return "South Korea";
    return $country_name;
}

function get_frist_last($arr,$n){
    return array_merge(array_slice($arr,0,$n,true),array_slice($arr, -$n, $n,true));
}

function get_sorted_countries_indicator_report($data_map,$indicator,$direction){
    $ret=array();
    if(!isset($direction)) $direction='desc';
    $curr_period='last_year';
    if($data_map[$indicator]['data'][$curr_period]['World']==null){
        $curr_period='previous_year';
    }
    if($data_map[$indicator]['data'][$curr_period]['World']==null){
        $curr_period='previous_year2';
    }
    if($data_map[$indicator]['data'][$curr_period]['World']==null){
        $curr_period='previous_year3';
    }
    if($data_map[$indicator]['data'][$curr_period]['World']==null){
        return "No data for $indicator any year after $curr_period_str";
    }
    $curr_period_str=$data_map[$indicator][$curr_period];
    $ret['year']=$curr_period_str;
    //echo $curr_period;
    if($direction=='desc') arsort($data_map[$indicator]['data'][$curr_period]);
    if($direction=='asc')  asort($data_map[$indicator]['data'][$curr_period]);
    
    $countries_to_del=array('World');
    foreach($data_map[$indicator]['data'][$curr_period] as $country){
        if($data_map[$indicator]['data'][$curr_period][$country]==null) $countries_to_del[]=$country;
    }
    foreach($countries_to_del as $country){
        array_slice($data_map[$indicator]['data'][$curr_period],array_search($country,array_keys($data_map[$indicator]['data'][$curr_period])),1,true);
    }
    //$sortedKeys=array_keys($data_map[$indicator]['data'][$curr_period]);
    //return get_frist_last($sortedKeys,4);
    // format the string as you want
    $ret['list']=get_frist_last($data_map[$indicator]['data'][$curr_period],4);
    return $ret;
}

$last_year=intval(date("Y"))-1;
$previous_year=$last_year-1;
$previous_year2=$last_year-2;
$previous_year3=$last_year-3;
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

if($indicator=='all'){
    foreach(array_filter(glob($data_directory.'-game/*_'.$data_source.'.json'), 'is_file') as $file) {
        $string = json_decode(file_get_contents($file),true);
        $indicator=basename ( $file, '_'.$data_source.'.json');
        $data_arr[$indicator]=$string;
    }
    $string = json_decode(file_get_contents($data_directory.'-game-unified/history.tsv.json'),true);
    $data_arr['history']=$string;
    
    // calculate country scoring (based on config) and add to analysis
    $score_config = json_decode(file_get_contents('country_scoring.json'), true);
    // TODO mimic js functionality and store in the json so it is pre-calculated
    $periods=array_keys($data_arr['population']['data']);
    $countries=array_keys($data_arr['population']['data']['previous_year']);    
    
    $data_arr['health']=$data_arr['population'];
    $data_arr['health']['data']['data_source']='cult';
    $data_arr['health']['data']['indicator']='health';
    $data_arr['health']['data']['indicator_sf']='health(cult score)';
    foreach($periods as $period){
        // sort gdppcap descending
        arsort($data_arr['gdppcap']['data'][$period]);
        foreach($countries as $country){
            #echo "<br /><br />$period $country<br />";
            $data_arr['health']['data'][$period][$country]=0;
            foreach($score_config['health']['scoring'] as $criterion){
                if(!array_key_exists($criterion['indicator'],$data_arr)){echo "ERROR: indicator ".$criterion['indicator']." does not exist.";exit();}
                if($data_arr[$criterion['indicator']]['data'][$period][$country]==null){
                    $data_arr['health']['data'][$period][$country]=null;
                    break;
                }else{
                    $score=0;
                    switch($criterion['type']){
                        case 'binary-range-val':
                            if(!array_key_exists("min",$criterion) || !is_numeric($criterion['min'])){echo "ERROR: incorrect min in binary-range-val";exit();}
                            if(!array_key_exists("max",$criterion) || !is_numeric($criterion['max'])){echo "ERROR: incorrect max in binary-range-val";exit();}
                            if($data_arr[$criterion['indicator']]['data'][$period][$country]>=$criterion['min'] 
                                    && $data_arr[$criterion['indicator']]['data'][$period][$country]<=$criterion['max'])
                                $score=1;
                            break;
                        case 'binary-min-val':
                            if(!array_key_exists("min",$criterion)){echo "ERROR: min is required in binary-min-val";exit();}
                            if($criterion['min']=='World'){$criterion['min']=$data_arr[$criterion['indicator']]['data'][$period][$criterion['min']];}
                            if(!is_numeric($criterion['min'])){echo "ERROR: non-numeric or World min in binary-min-val";exit();}
                            if($data_arr[$criterion['indicator']]['data'][$period][$country]>=$criterion['min'])
                                $score=1;
                            break;
                        case 'binary-max-val':
                            if(!array_key_exists("max",$criterion)){echo "ERROR: max is required in binary-max-val";exit();}
                            if($criterion['max']=='World'){$criterion['max']=$data_arr[$criterion['indicator']]['data'][$period][$criterion['max']];}
                            if(!is_numeric($criterion['max'])){echo "ERROR: non-numeric or World max in binary-max-val";exit();}
                            if($data_arr[$criterion['indicator']]['data'][$period][$country]<=$criterion['max'])
                                $score=1;
                            break;
                    }
                    if(array_key_exists("weight",$criterion)) $score=$score*$criterion['weight'];
                    #echo $criterion['name']."=".$score;
                    $data_arr['health']['data'][$period][$country]+=$score;
                }
            }
            // untie
            if($data_arr['gdppcap']['data'][$period][$country]!=null && 
                $data_arr['gdppcap']['data'][$period][current(array_keys($data_arr['gdppcap']['data'][$period]))]!=null){
                $data_arr['health']['data'][$period][$country]+=($data_arr['gdppcap']['data'][$period][$country]-1)/$data_arr['gdppcap']['data'][$period][current(array_keys($data_arr['gdppcap']['data'][$period]))];
            }
        }
    }
}else if($indicator=='analysis'){
    $data_arr['analysis_report']=array(); // this is limited to the n-best-health, n-worst-health, n-best/worst-eco-perf, n-best/worst-eco-oportunity
    $datamap = json_decode(file_get_contents($data_directory.'-game-unified/all_wb.json'), true);
    $data_arr['analysis_report']['list_health']=get_sorted_countries_indicator_report($datamap,'health','desc');
    // store in a separate file called $year.analysis-report.json
    // if it does not exist -- send email
    // if it exist but it is different -- send email
}else{
    $data_arr['indicator']=$indicator;
    $data_arr['indicator_sf']=$indicator_sf;
    $data_arr['last_year']=$last_year;
    $data_arr['previous_year']=$previous_year;
    $data_arr['previous_year2']=$previous_year2;
    $data_arr['previous_year3']=$previous_year3;
    $data_arr['last_lustrum']=$last_lustrum;
    $data_arr['last_decade']=$last_decade;
    $data_arr['last_2decade']=$last_2decade;
    $data_arr['data_source']=$data_source;
    $data_arr['data']=array();
    $data_arr['data']['last_year']=array();
    $data_arr['data']['previous_year']=array();
    $data_arr['data']['previous_year2']=array();
    $data_arr['data']['previous_year3']=array();
    $data_arr['data']['last_lustrum']=array();
    $data_arr['data']['last_decade']=array();
    $data_arr['data']['last_2decade']=array();
    foreach(array_filter(glob($data_directory.'/*_'.$indicator.'_'.$data_source.'.json'), 'is_file') as $file) {
        $string = file_get_contents($file);
        $json_a = json_decode($string, true);
        //print_r($file);
        //print_r($json_a);
        if(count($json_a>1)){
            foreach ($json_a[1] as $item) {
                $item['country']['value']=country_translation($item['country']['value']);
                if($item['date'] == $last_year ) $data_arr['data']['last_year'][$item['country']['value']]=$item["value"];
                else if($item['date'] == $previous_year ) $data_arr['data']['previous_year'][$item['country']['value']]=$item["value"];
                else if($item['date'] == $previous_year2 ) $data_arr['data']['previous_year2'][$item['country']['value']]=$item["value"];
                else if($item['date'] == $previous_year3 ) $data_arr['data']['previous_year3'][$item['country']['value']]=$item["value"];
                else if($item['date'] == $last_lustrum ) $data_arr['data']['last_lustrum'][$item['country']['value']]=$item["value"];
                else if($item['date'] == $last_decade ) $data_arr['data']['last_decade'][$item['country']['value']]=$item["value"];
                else if($item['date'] == $last_2decade ) $data_arr['data']['last_2decade'][$item['country']['value']]=$item["value"];
            }
        }else{
            require("phpmailer/class.phpmailer.php");
            error_reporting(E_STRICT);
            date_default_timezone_set('Europe/Madrid');
            $mail = new PHPMailer();

            $mail_credentials = json_decode(file_get_contents("/home/hector/secrets/mail-cognitionis.json"));
            $mail->IsSMTP(); // enable SMTP
            $mail->SMTPDebug = 1;  // debugging: 1 = errors and messages, 2 = messages only
            $mail->SMTPAuth   = true;                  // enable SMTP authentication
            $mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
            $mail->Host       = $mail_credentials->smtp_host;      // sets GMAIL as the SMTP server
            $mail->Port       = 465;                   // set the SMTP port for the GMAIL server
            $mail->Username   = $mail_credentials->user;  // GMAIL username
            $mail->Password   = $mail_credentials->pass;  // GMAIL password


            $mail->charSet = "UTF-8";
            $mail->SetFrom('info@cognitionis.com');
            $mail->From="info@cognitionis.com";
            $mail->FromName="cognitionis.com";
            $mail->Sender="info@cognitionis.com"; // indicates ReturnPath header
            $mail->AddReplyTo("info@cognitionis.com"); // indicates ReplyTo headers
            $mail->IsHTML(true);

            $subject="cognitionis.com/cult: ERROR FATAL descarga datos world bank";
            $body=$file." is problematic...";
            $mail->Subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
            $mail->Body = '<html><head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8"></head><body><br />'.$body.'<br /><br /></body></html>';
            $mail->AddAddress('hectorlm1983@gmail.com');
            $mail->AddBCC("info@cognitionis.com");
            if(!$mail->Send()){   $log.="<br />Error: " . $mail->ErrorInfo;}else{$log.="<br /><b>Email enviado a: hectorlm1983@gmail.com</b>";}
            exit(1);
        }
    }
}

header('Content-type: application/json');
echo json_encode( $data_arr );


?>
