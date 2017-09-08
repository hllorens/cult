
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
 
require("phpmailer/class.phpmailer.php");
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
$log="BEGIN ";
$timestamp_date=date("Y-m-d");
$timestamp=date("Y-m-d H:i");



//access like http://www.cognitionis.com/cult/www/backend/send-stock-alerts-fire.php?autosecret=1secret


// NOW We need a loop that goes over each user and sends email to him if alerts halt...
// Ideally keep an accessible node to write last time alerts were send...


$mail = new PHPMailer();

$mail_credentials = json_decode(file_get_contents("../../../secrets/exposed_gmail_cursos_psico.json"));
$mail->IsSMTP(); // enable SMTP
$mail->SMTPDebug = 1;  // debugging: 1 = errors and messages, 2 = messages only
$mail->SMTPAuth   = true;                  // enable SMTP authentication
$mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
$mail->Host       = $mail_credentials->smtp_host;      //  SMTP server "mail.cognitionis.com" now "sub3.mail.dreamhost.com"
$mail->Port       = 465;                   // set the SMTP port for the GMAIL server
$mail->Username   = $mail_credentials->user;  //  username
$mail->Password   = $mail_credentials->pass;  //  password
// para arreglar en hotmail usar php mail sin phpmailer



$mail->charSet = "UTF-8";
$mail->SetFrom('info@cognitionis.com');
$mail->From="info@cognitionis.com";
$mail->FromName="cognitionis.com";
$mail->Sender="info@cognitionis.com"; // indicates ReturnPath header
$mail->AddReplyTo("info@cognitionis.com"); // indicates ReplyTo headers
$mail->IsHTML(true);


foreach ($alerts as $usr => $ualerts) {
    $facts="";
    $body="";
    $usr_decoded=str_replace("__p__", ".", $usr).'@gmail.com';
    // IMPORTANT--------------------------------------------------------------------------------
    if($usr_decoded!="hectorlm1983@gmail.com") continue; // disabled for other users for now
    //------------------------------------------------------------------------------------------
    echo '\n<br />processing alerts for user: '.$usr_decoded.'\n<br />';
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
        if(array_key_exists("low_per",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['per'])) <= floatval($alert['low_per'])){
            $fact.="-per ";//.$stocks[$alert['symbol']]['per'];
        }else if(array_key_exists("high_per",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['per'])) >= floatval($alert['high_per'])){
            $fact.="+per ";//.$stocks[$alert['symbol']]['per'];
        }
        if(array_key_exists("low_eps",$alert) && $stocks[$alert['symbol']]['eps']!="" && floatval(str_replace(",","",$stocks[$alert['symbol']]['eps'])) <= floatval($alert['low_eps'])){
            $fact.="-eps ";//.$stocks[$alert['symbol']]['eps'];
        }else if(array_key_exists("high_eps",$alert) && $stocks[$alert['symbol']]['eps']!="" && floatval(str_replace(",","",$stocks[$alert['symbol']]['eps'])) >= floatval($alert['high_eps'])){
            $fact.="+eps ";//.$stocks[$alert['symbol']]['eps'];
        }
        if(array_key_exists("low_sell",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['value'])) <= floatval($alert['low_sell'])){
            $fact.="Sold (stop-loss) ";//.$stocks[$alert['symbol']]['value'];
        }
        if($fact!=""){
            if(array_key_exists("eps_hist",$stocks[$alert['symbol']])){
                $last_n_eps=array_slice($stocks[$alert['symbol']]['eps_hist'],-3);
                $extra="EPS Hist:";
                for ($i = 0; $i < count($last_n_eps); $i++){
                    $extra.=" [".$last_n_eps[$i][0].",".$last_n_eps[$i][1]."]";
                }
                $extra.="<br />";
            }
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
            
            // calculate usdeurvalue if nyse or nasdaq
            $usdeurvalue="";
            if($stocks[$alert['symbol']]['market']=="NYSE" || $stocks[$alert['symbol']]['market']=="NASDAQ"){
                $usdeurvalue="<b>Euros: ".number_format((floatval($stocks[$alert['symbol']]['value'])*floatval($stocks['GOOG:NASDAQ']['usdeur'])), 2, ".", "")."</b>";
            }
            $body.=" <br /><b>".$alert['symbol']." (".$stocks[$alert['symbol']]['title'].") ".$fact."</b><br />usr: ".$usr_decoded." ranges:<br />
                  Value:  <b>".$stocks[$alert['symbol']]['value']."</b> [".$alert['low']." -to- ".$alert['high']."],<br/>
                  $usdeurvalue
                  &nbsp;&nbsp; low sell (stoploss): ".$alert['low_sell']."<br />
                  Range52w:  ".$stocks[$alert['symbol']]['range_52week_low']." -- ".$stocks[$alert['symbol']]['range_52week_high']." current %: ".$stocks[$alert['symbol']]['range_52week_heat']." volat: ".$stocks[$alert['symbol']]['range_52week_volatility']."<br />
                  Change(%):  ".$stocks[$alert['symbol']]['session_change_percentage']." [".$alert['low_change_percentage']." -to- ".$alert['high_change_percentage']."]<br />
                  EPS:    ".$stocks[$alert['symbol']]['eps']." [".$alert['low_eps']." -to- ".$alert['high_eps']."]<br />
                  Per:    ".$stocks[$alert['symbol']]['per']." [".$alert['low_per']." -to- ".$alert['high_per']."]<br />
                  Yield   ".$stocks[$alert['symbol']]['yield']." [".$alert['low_yield']." -to- ".$alert['high_yield']."]<br />
                  ".$extra."
                  <br /><br />
                  Go to <a href=\"http://www.cognitionis.com/stockionic/\">stockionic</a> to change it.<br /><br />
                  Date: ".$timestamp."<br />
                  ";
            //if($usr_decoded=="hectorlm1983@gmail.com"){
            //    $body.="                  ----<br />Json string debug:<br />turned off for now...."; //.$string;
            //}
            $facts.=$stocks[$alert['symbol']]['name']." ".$fact."|";
        }

    }
    if($facts!=""){
        if($debug) echo '  '.$body.'<br />';
        send_alert($facts,$body,$usr_decoded, $mail);
    }
}


function send_alert($subject, $body, $user, $mail){
	$subject="cult: ".$subject;
	$mail->Subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
	$mail->Body = '<html><head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8"></head><body><br />'.$body.'<br /><br /></body></html>';
	$mail->AddAddress($user);
	$mail->AddBCC("info@cognitionis.com");
	if(!$mail->Send()){   
		$log.="<br />Error: " . $mail->ErrorInfo;
        echo "<br />Error: " . $mail->ErrorInfo;
	}else{
		$log.="<br /><b>Email enviado a: $user</b>";
        echo "Mail enviado. ";
	}
	$mail->ClearAllRecipients();
	$mail->ClearAttachments();
	if($debug) echo $log;
	sleep(0.1);
}



?>