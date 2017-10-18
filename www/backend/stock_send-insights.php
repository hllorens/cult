
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


$timestamp_date=date("Y-m-d");
$timestamp=date("Y-m-d H:i");


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



$facts="";
$body="";
$usr_decoded="hectorlm1983@gmail.com";


$fact="";



if(array_key_exists('usdeur_change',$stocks['GOOG:NASDAQ']) && (abs(floatval($stocks['GOOG:NASDAQ']['usdeur_change']))>0.02 || abs(floatval($stocks['GOOG:NASDAQ']['usdeur_hist_last_diff']))>4)){
    $facts.="usdeur ";
    $body.=" <br /><b>usdeur</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['usdeur']), 2, ".", "");
    $body.=" <br /><b>usdeur_c</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['usdeur_change'])*100, 2, ".", "")."%";
    $body.=" <br /><b>lastQdiff</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['usdeur_hist_last_diff']), 2, ".", "")."%";
    
    
}

if(array_key_exists('btcusd_change',$stocks['GOOG:NASDAQ']) && (abs(floatval($stocks['GOOG:NASDAQ']['btcusd_change']))>0.04 || abs(floatval($stocks['GOOG:NASDAQ']['btcusd_hist_last_diff']))>20)){
    $facts.="btcusd ";
    $body.=" <br /><b>btcusd</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['btcusd']), 2, ".", "");
    $body.=" <br /><b>btcusd_c</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['btcusd_change'])*100, 2, ".", "")."%";
    $body.=" <br /><b>lastQdiff</b>: ".number_format(floatval($stocks['GOOG:NASDAQ']['btcusd_hist_last_diff']), 2, ".", "")."%";
}



if($facts!=""){
    if($debug) echo '  '.$body.'<br />';
    send_alert($facts,$body,$usr_decoded, $mail);
}



function send_alert($subject, $body, $user, $mail){
	$subject="".$subject;
	$mail->Subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
	$mail->Body = '<html><head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8"></head><body><br />'.$body.'<br /><br /></body></html>';
	$mail->AddAddress($user);
	$mail->AddBCC("info@cognitionis.com");
	if(!$mail->Send()){   
        echo "<br />Error: " . $mail->ErrorInfo;
	}else{
        echo "Mail enviado. ";
	}
	$mail->ClearAllRecipients();
	$mail->ClearAttachments();
	sleep(0.1);
}



?>