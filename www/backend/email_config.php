
<?php


require("phpmailer/class.phpmailer.php");
date_default_timezone_set('Europe/Madrid');

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

function send_mail($subject, $body, $user){
	$subject="cult: ".$subject;
	$mail->Subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
	$mail->Body = '<html><head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8"></head><body><br />'.$body.'<br /><br />Go to <a href=\"http://www.cognitionis.com/stockionic/\">stockionic</a> to change it.<br /><br /></body></html>';
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