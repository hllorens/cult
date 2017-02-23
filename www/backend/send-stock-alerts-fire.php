
<?php

// extra security
if(!isset($_GET['autosecret']) || $_GET['autosecret']!='1secret'){
	exit("Permission denied");
}

if(!file_exists(substr(dirname(__FILE__), 0,strpos(dirname(__FILE__), '/')).'../../../cult-data-stock-google/stocks.formated.json')){
	exit("Error: ERROR.log not found ".substr(dirname(__FILE__), 0,strpos(dirname(__FILE__), '/')).'../../../cult-data-stock-google/stocks.formated.json'); //dirname(__FILE__).'/../data/ERROR.log');
}
$data_directory='../../../cult-data-stock-google';
$data_object=array();
$file=$data_directory.'/stocks.formated.json';
$string = file_get_contents($file);
$stocks = json_decode($string, true);
 
require("phpmailer/class.phpmailer.php");
#error_reporting(E_STRICT);
date_default_timezone_set('Europe/Madrid');


$FIREBASE='https://cult-game.firebaseio.com/';
$NODE='alerts.json';


$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $FIREBASE . $NODE );
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

echo json_encode($alerts_log)."<br />";
// to store alerts updated dates
$updates="empty";
$log="BEGIN ";
$timestamp_date=date("Y-m-d");



//access like http://www.cognitionis.com/cult/www/backend/send-stock-alerts-fire.php?autosecret=1secret


// NOW We need a loop that goes over each user and sends email to him if alerts halt...
// Ideally keep an accessible node to write last time alerts were send...


$mail = new PHPMailer();

$mail_credentials = json_decode(file_get_contents("/home/hector/secrets/mail-cognitionis.json"));
$mail->IsSMTP(); // enable SMTP
$mail->SMTPDebug = 1;  // debugging: 1 = errors and messages, 2 = messages only
$mail->SMTPAuth   = true;                  // enable SMTP authentication
$mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
$mail->Host       = "mail.cognitionis.com";      // sets GMAIL as the SMTP server
$mail->Port       = 465;                   // set the SMTP port for the GMAIL server
$mail->Username   = $mail_credentials->user;  // GMAIL username
$mail->Password   = $mail_credentials->pass;  // GMAIL password
// para arreglar en hotmail usar php mail sin phpmailer



$mail->charSet = "UTF-8";
$mail->SetFrom('info@cognitionis.com');
$mail->From="info@cognitionis.com";
$mail->FromName="cognitionis.com";
$mail->Sender="info@cognitionis.com"; // indicates ReturnPath header
$mail->AddReplyTo("info@cognitionis.com"); // indicates ReplyTo headers
$mail->IsHTML(true);


foreach ($alerts as $usr => $ualerts) {
    $usr_decoded=str_replace("__p__", ".", $usr).'@gmail.com';
    echo $usr_decoded.'<br />';
    foreach ($ualerts as $symbol => $alert) {
        echo $symbol.'<br />';
        $fact="";
        if(array_key_exists($usr.'_'.$symbol,$alerts_log) && $alerts_log[$usr.'_'.$symbol]==$timestamp_date) continue; // check if alerted today already
        if(array_key_exists("low",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['value'])) < floatval($alert['low'])){
            $fact.="-val ".$stocks[$alert['symbol']]['value'];
        }else if(array_key_exists("high",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['value'])) > floatval($alert['high'])){
            $fact.="+val ".$stocks[$alert['symbol']]['value'];
        }
        if(array_key_exists("low_change_percentage",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['session_change_percentage'])) < floatval($alert['low_change_percentage'])){
            $fact.="-% ".$stocks[$alert['symbol']]['session_change_percentage'];
        }else if(array_key_exists("high_change_percentage",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['session_change_percentage'])) > floatval($alert['high_change_percentage'])){
            $fact.="+% ".$stocks[$alert['symbol']]['session_change_percentage'];
        }
        if(array_key_exists("low_yield",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['yield'])) < floatval($alert['low_yield'])){
            $fact.="-yield ".$stocks[$alert['symbol']]['yield'];
        }else if(array_key_exists("high_yield",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['yield'])) > floatval($alert['high_yield'])){
            $fact.="+yield ".$stocks[$alert['symbol']]['yield'];
        }
        if(array_key_exists("low_per",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['per'])) < floatval($alert['low_per'])){
            $fact.="-per ".$stocks[$alert['symbol']]['per'];
        }else if(array_key_exists("high_per",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['per'])) > floatval($alert['high_per'])){
            $fact.="+per ".$stocks[$alert['symbol']]['per'];
        }
        if(array_key_exists("low_eps",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['eps'])) < floatval($alert['low_eps'])){
            $fact.="-eps ".$stocks[$alert['symbol']]['eps'];
        }else if(array_key_exists("high_eps",$alert) && floatval(str_replace(",","",$stocks[$alert['symbol']]['eps'])) > floatval($alert['high_eps'])){
            $fact.="+eps ".$stocks[$alert['symbol']]['eps'];
        }
        if($fact!=""){
            $alerts_log[$usr.'_'.$symbol]=$timestamp_date;
            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $FIREBASE . 'alerts_log.json' ); ///'.$usr.'_'.$symbol.'
            curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "PUT" );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode($alerts_log) );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            $response = curl_exec( $curl );
            curl_close( $curl );
            echo $response . "\n";
            //update db last_alerted_date
            //$sQuery2 = "UPDATE stock_alerts SET last_alerted_date='$timestamp_date' WHERE id=".$alert['id'].";";
            //echo $sQuery2;
            //$rResult2 = mysqli_query( $db_connection, $sQuery2 );
            //if(!$rResult2){ echo mysqli_error( $db_connection )." -- ".$sQuery2; }
            $body=$alert['symbol']." ".$fact."<br />".$alert['symbol']." your ranges:<br />\
                  value  ".$stocks[$alert['symbol']]['value']." [".$alert['low']." -to- ".$alert['high']."] <br />
                  percentage  ".$stocks[$alert['symbol']]['session_change_percentage']."[".$alert['low_change_percentage']." -to- ".$alert['high_change_percentage']."]<br />
                  eps  ".$stocks[$alert['symbol']]['eps']."[".$alert['low_eps']." -to- ".$alert['high_eps']."]<br />
                  per  ".$stocks[$alert['symbol']]['per']."[".$alert['low_per']." -to- ".$alert['high_per']."]<br />
                  yield  ".$stocks[$alert['symbol']]['yield']." [".$alert['low_yield']." -to- ".$alert['high_yield']."]";
            send_alert($alert['symbol']." ".$fact,$body,$usr_decoded, $mail);
            echo '  '.$body.'<br />';
        }

    }
}


function send_alert($symbol, $body, $user, $mail){
	$subject="cult: ".$symbol;
	$mail->Subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
	$mail->Body = '<html><head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8"></head><body><br />'.$body.'<br /><br /></body></html>';
	$mail->AddAddress($user);
	$mail->AddBCC("info@cognitionis.com");
	if(!$mail->Send()){   
		$log.="<br />Error: " . $mail->ErrorInfo;
	}else{
		$log.="<br /><b>Email enviado a: $user</b>";
	}
	$mail->ClearAllRecipients();
	$mail->ClearAttachments();
	echo $log;
	sleep(3);
}



?>