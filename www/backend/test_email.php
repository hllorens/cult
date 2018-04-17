
<?php

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

 
require_once("email_config.php");
#error_reporting(E_STRICT);
date_default_timezone_set('Europe/Madrid');



send_mail('test','<br />test','hectorlm1983@gmail.com');






?>