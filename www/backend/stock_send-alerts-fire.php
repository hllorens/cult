
<?php

// extra security
//if(!isset($_GET['autosecret']) || $_GET['autosecret']!='1secret'){
//	exit("Permission denied");
//}


$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

if(!file_exists(substr(dirname(__FILE__), 0,strpos(dirname(__FILE__), '/')).'stocks.formatted.json')){
	exit("Error: ERROR.log not found ".substr(dirname(__FILE__), 0,strpos(dirname(__FILE__), '/')).'stocks.formated.json'); //dirname(__FILE__).'/../data/ERROR.log');
}
$data_object=array();
$string = file_get_contents('stocks.formatted.json');
$stocks = json_decode($string, true);
 
require_once("email_config.php");
#error_reporting(E_STRICT);
date_default_timezone_set('Europe/Madrid');


$FIREBASE='https://cult-game.firebaseio.com/';


$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $FIREBASE . 'alerts.json' );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl );
curl_close( $curl );
$alerts=json_decode($response,true);

$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $FIREBASE . 'alerts_log.json' );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl );
curl_close( $curl );
$alerts_log=json_decode($response,true);

if($debug) echo json_encode($alerts_log)."<br />";
// to store alerts updated dates
$updates="empty";

$timestamp_date=date("Y-m-d");
$timestamp=date("Y-m-d H:i");
$timestampH=date("H");

if($timestampH>17) $timestamp_date.="pm";
else $timestamp_date.="am";


//access like http://www.cognitionis.com/cult/www/backend/send-stock-alerts-fire.php?autosecret=1secret


// NOW We need a loop that goes over each user and sends email to him if alerts halt...
// Ideally keep an accessible node to write last time alerts were send...



$usdeur=0.0;
$usdeur=floatval($stocks['GOOG:NASDAQ']['usdeur']);

foreach ($alerts as $usr => $ualerts) {
    $facts="";
    $body="";
    $usr_decoded=str_replace("__p__", ".", $usr).'@gmail.com';
    // IMPORTANT--------------------------------------------------------------------------------
    if($usr_decoded!="hectorlm1983@gmail.com") continue; // disabled for other users for now
    //------------------------------------------------------------------------------------------
    echo '<br />processing alerts for user: '.$usr_decoded.'<br />';
    foreach ($ualerts as $symbol => $alert) {
        if($debug) echo "  symbol: ".$symbol.'\n<br />';
        $fact="";
        if(array_key_exists($usr.'_'.$symbol,$alerts_log) && $alerts_log[$usr.'_'.$symbol]==$timestamp_date){
            if($debug) echo '&nbsp;   already sent '.$usr.'_'.$symbol.'=='.$timestamp_date.'\n<br />';
            continue; // check if alerted today already
        } 
        if(array_key_exists("low",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['value'])) <= floatval($alert['low'])){
            $fact.="-val "; //.$stocks[$alert['symbol']]['value'];
        }else if(array_key_exists("high",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['value'])) >= floatval($alert['high'])){
            $fact.="+val ";//.$stocks[$alert['symbol']]['value'];
        }
        $eurval=0.0;
        if(($stocks[$alert['symbol']]['market']=="NYSE" || $stocks[$alert['symbol']]['market']=="NASDAQ") && $usdeur>0.0){
            $eurval=floatval(str_replace(",","",$stocks[$alert['symbol']]['value']))*$usdeur;
        }
        if(array_key_exists("lowe",$alert) && $eurval <= floatval($alert['lowe'])){
            $fact.="-valeur "; //.$stocks[$alert['symbol']]['value'];
        }else if(array_key_exists("highe",$alert) && $eurval >= floatval($alert['highe'])){
            $fact.="+valeur ";//.$stocks[$alert['symbol']]['value'];
        }
        if(array_key_exists("low_change_percentage",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['session_change_percentage'])) <= floatval($alert['low_change_percentage'])){
            $fact.="-% ";//.$stocks[$alert['symbol']]['session_change_percentage'];
        }else if(array_key_exists("high_change_percentage",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['session_change_percentage'])) >= floatval($alert['high_change_percentage'])){
            $fact.="+% ";//.$stocks[$alert['symbol']]['session_change_percentage'];
        }
        if(array_key_exists("low_yield",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['yield'])) <= floatval($alert['low_yield'])){
            $fact.="-y ";//.$stocks[$alert['symbol']]['yield'];
        }else if(array_key_exists("high_yield",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['yield'])) >= floatval($alert['high_yield'])){
            $fact.="+y ";//.$stocks[$alert['symbol']]['yield'];
        }
        if(array_key_exists("low_epsp",$alert) && array_key_exists("epsp",$stocks[$alert['symbol']]) && $stocks[$alert['symbol']]['epsp']!="" && floatval(str_replace(",","",$stocks[$alert['symbol']]['epsp'])) <= floatval($alert['low_epsp'])){
            $fact.="-epsp ";//.$stocks[$alert['symbol']]['eps'];
        }else if(array_key_exists("high_epsp",$alert) && array_key_exists("epsp",$stocks[$alert['symbol']]) && $stocks[$alert['symbol']]['epsp']!="" && floatval(str_replace(",","",$stocks[$alert['symbol']]['epsp'])) >= floatval($alert['high_epsp'])){
            $fact.="+epsp ";//.$stocks[$alert['symbol']]['eps'];
        }
        $stoploss=0.0;
        if(array_key_exists("portf",$alert)){
            $stoploss=floatval($alert['portf'])*0.8;
            if($eurval!=0.0){$stoploss=(floatval($alert['portf'])/$usdeur)*0.8;}
            if(floatval(str_replace(",","",$stocks[$alert['symbol']]['value'])) <= $stoploss ){
                $fact.="sell (stop-loss) ";//.$stocks[$alert['symbol']]['value'];
            }
        }
        if($fact!=""){
            $extra="";
            if(array_key_exists("eps_hist",$stocks[$alert['symbol']])){
                $last_n_eps=array_slice($stocks[$alert['symbol']]['eps_hist'],-3);
                $extra="EPS Hist:";
                for ($i = 0; $i < count($last_n_eps); $i++){
                    $extra.=" [".$last_n_eps[$i][0].",".$last_n_eps[$i][1]."]";
                }
            }
            $extra.="<br />";
            $alerts_log[$usr.'_'.$symbol]=$timestamp_date;
            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $FIREBASE . 'alerts_log.json' ); ///'.$usr.'_'.$symbol.'
            curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "PUT" );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode($alerts_log) );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            $response = curl_exec( $curl );
            curl_close( $curl );
            //echo $response . "\n";
            //update db last_alerted_date
            //$sQuery2 = "UPDATE stock_alerts SET last_alerted_date='$timestamp_date' WHERE id=".$alert['id'].";";
            //echo $sQuery2;
            //$rResult2 = mysqli_query( $db_connection, $sQuery2 );
            //if(!$rResult2){ echo mysqli_error( $db_connection )." -- ".$sQuery2; }
            
            // fill empty values with empty string
            $alert_keys=['low','high','lowe','highe','portf','low_change_percentage','high_change_percentage','revenue_growth_qq_last_year','low_eps','high_eps','low_per','high_per','low_yield','high_yield'];
            foreach ($alert_keys as $value){
                if(!array_key_exists($value,$alert)){
                    $alert[$value]='';
                }
            }
            // calculate usdeurvaluetext if nyse or nasdaq
            $usdeurvaluetext="";
            if($eurval>0.0){
                $usdeurvaluetext="eur: ".number_format($eurval, 2, ".", "")."(".$usdeur.") [".$alert['lowe']." -to- ".$alert['highe']."]";
            }
            $portftext="";
            if($alert['portf']!=''){
                $dollar="";
                if($eurval!=0.0){$dollar="$";}
                $portftext="portf (eur): ".$alert['portf']." (-20% stop: ".$dollar.number_format($stoploss, 1, ".", "").")<br />";
            }
            //usr: ".$usr_decoded." 
            $body.=" <br /><b>".$alert['symbol']." (".$stocks[$alert['symbol']]['title'].") ".$fact."</b><br />
                  Value:&nbsp; ".$stocks[$alert['symbol']]['value']." [".$alert['low']." -to- ".$alert['high']."],".$usdeurvaluetext."<br/>
                  Change: ".$stocks[$alert['symbol']]['session_change_percentage']."% [".$alert['low_change_percentage']." -to- ".$alert['high_change_percentage']."]<br />
                  &nbsp;&nbsp; ".$portftext."
                  Range52w:  ".$stocks[$alert['symbol']]['range_52week_low']." -- ".$stocks[$alert['symbol']]['range_52week_high']."<br />
                  Yield: ".$stocks[$alert['symbol']]['yield']."% [".$alert['low_yield']." -to- ".$alert['high_yield']."]<br />
                  RevenueQQdiff: ".$stocks[$alert['symbol']]['revenue_growth_qq_last_year']."%<br />
                  epsp: ".$stocks[$alert['symbol']]['epsp']."%<br />
                  ".$extra."
                  Last updated: ".$stocks[$alert['symbol']]['date']."<br />
                  <br /><br />
                  ";
                  
            //                  EPS:    ".$stocks[$alert['symbol']]['eps']." [".$alert['low_eps']." -to- ".$alert['high_eps']."]<br />
            //if($usr_decoded=="hectorlm1983@gmail.com"){
            //    $body.="                  ----<br />Json string debug:<br />turned off for now...."; //.$string;
            //}
            $facts.=$stocks[$alert['symbol']]['name']." ".$fact."|";
        }

    }
    if($facts!=""){
        if($debug) echo '  '.$body.'<br />';
        send_mail($facts,'date:'.$timestamp.'<br />'.$body,$usr_decoded);
    }
}





?>