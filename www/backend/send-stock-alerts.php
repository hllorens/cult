
<?php

if(!file_exists(substr(dirname(__FILE__), 0,strpos(dirname(__FILE__), '/')).'../../../cult-data-stock-google/stocks.formated.json')){
	exit("Error: ERROR.log not found ".substr(dirname(__FILE__), 0,strpos(dirname(__FILE__), '/')).'../../../cult-data-stock-google/stocks.formated.json'); //dirname(__FILE__).'/../data/ERROR.log');
}
$data_directory='../../../cult-data-stock-google';
$data_object=array();
$file=$data_directory.'/stocks.formated.json';
$string = file_get_contents($file);
$stocks = json_decode($string, true);

// extra security
if(!isset($_GET['autosecret']) || $_GET['autosecret']!='1secret'){
	exit("Permission denied");
}

require("phpmailer/class.phpmailer.php");
error_reporting(E_STRICT);
date_default_timezone_set('Europe/Madrid');
$mail = new PHPMailer();

$db_credentials = json_decode(file_get_contents("../../../../secrets/db_credentials_cult-game.json"));
$db_connection =  mysql_pconnect( $db_credentials->db_server, $db_credentials->user, $db_credentials->pass  ) or die( 'Could not open connection to server' );
mysql_select_db( $db_credentials->db_name, $db_connection) or die( 'Could not select database' );

/* SET UTF-8 independently of the MySQL and PHP installation */
mysql_query("SET NAMES 'utf8'");	
mysql_query("set time_zone:='Europe/Madrid'");	



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

$sQuery = "SELECT * FROM stock_alerts"; //echo "query: $sQuery ";
$rResult = mysql_query( $sQuery, $db_connection ) or die(mysql_error());
$log="BEGIN ";
$timestamp_date=date("Y-m-d");
while ( $aRow = mysql_fetch_array( $rResult ) ){
    // alert when it happens and once a day while the condition is true unless the owner turns off (or updates) the alert
	if( $aRow['last_alerted_date']!=$timestamp_date && (
            floatval($stocks[$aRow['symbol']]['value']) < floatval($aRow['low']) ||
            floatval($stocks[$aRow['symbol']]['value']) > floatval($aRow['high']) ||
            floatval($stocks[$aRow['symbol']]['session_change_percentage']) < floatval($aRow['low_change_percentage']) ||
            floatval($stocks[$aRow['symbol']]['session_change_percentage']) > floatval($aRow['high_change_percentage'])             
            ) ){
        //update db last_alerted_date
        $sQuery2 = "UPDATE stock_alerts SET last_alerted_date='$timestamp_date' WHERE id=".$aRow['id'].";";
        echo $sQuery2;
        $rResult2 = mysql_query( $sQuery2, $db_connection );
        if(!$rResult2){ echo mysql_error()." -- ".$sQuery2; }
        send_alert($aRow['symbol'],$stocks[$aRow['symbol']]['value'],$stocks[$aRow['symbol']]['session_change_percentage'],$aRow['user'], $mail);
	}
	//print_r($aRow);
	//print_r($stocks);
}

function send_alert($symbol, $value, $change_percentage, $user, $mail){
	$subject="cognitionis.com stock-alert: ".$symbol;
	$body=$symbol." is ".$value." (".$change_percentage.")";
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



