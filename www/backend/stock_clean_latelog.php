<?php

require_once("email_config.php");

$latelog = fopen("late.log", "a") or die("Unable to open/create late.log!");


if(file_exists ( 'late.log' )){
    echo "late.log exists -> reading...<br />";
    $content = file_get_contents('late.log');
	if(str_word_count($content)>2){
		echo "content: '$content'";
		send_mail('cult: late errors (crawl)','<br />'.$content.'<br />',"hectorlm1983@gmail.com");
	}else{
		echo "empty: no action";
	}
	unlink('late.log');
}else{
    echo "<br/><br/><span style=\"color:red\">ERROR:</span>late.log does NOT exist<br />";
}

?>