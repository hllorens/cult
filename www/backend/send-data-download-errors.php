
<?php

if(!file_exists(substr(dirname(__FILE__), 0,strpos(dirname(__FILE__), '/')).'../../cult-data/ERROR.log')){
	exit("Error: ERROR.log not found ".substr(dirname(__FILE__), 0,strpos(dirname(__FILE__), '/')).'../../cult-data/ERROR.log'); //dirname(__FILE__).'/../data/ERROR.log');
}

// extra security
if(!isset($_GET['autosecret']) || $_GET['autosecret']!='1secret'){
	exit("Permission denied");
}

require("phpmailer/class.phpmailer.php");
error_reporting(E_STRICT);
date_default_timezone_set('Europe/Madrid');
$mail = new PHPMailer();

//$mail->IsMail();
$mail->IsSMTP(); // enable SMTP
$mail->SMTPDebug = 1;  // debugging: 1 = errors and messages, 2 = messages only
$mail->SMTPAuth   = true;                  // enable SMTP authentication
$mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
$mail->Host       = "mail.cognitionis.com";      // sets GMAIL as the SMTP server
$mail->Port       = 465;                   // set the SMTP port for the GMAIL server
$mail->Username   = "info@cognitionis.com";  // GMAIL username
$mail->Password   = "carpediem";            // GMAIL password
// para arreglar en hotmail usar php mail sin phpmailer

$mail->charSet = "UTF-8";
$mail->SetFrom('info@cognitionis.com');
$mail->From="info@cognitionis.com";
$mail->FromName="cognitionis.com";
$mail->Sender="info@cognitionis.com"; // indicates ReturnPath header
$mail->AddReplyTo("info@cognitionis.com"); // indicates ReplyTo headers
$mail->IsHTML(true);

$subject="cognitionis.com: ERRORES descarga datos world bank";
$body=file_get_contents(substr(dirname(__FILE__), 0,strpos(dirname(__FILE__), '/')).'../../cult-data/ERROR.log');
$mail->Subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
$mail->Body = '<html><head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8"></head><body><br />'.$body.'<br /><br /></body></html>';
$mail->AddAddress('hectorlm1983@gmail.com');
$mail->AddBCC("info@cognitionis.com");
if(!$mail->Send()){   $log.="<br />Error: " . $mail->ErrorInfo;}else{$log.="<br /><b>Email enviado a: hectorlm1983@gmail.com</b>";}
echo $log;
