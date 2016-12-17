<?php

session_start(); 

date_default_timezone_set('Europe/Madrid');

function get_value($name){
	if( !isset($_REQUEST[$name]) ){
		$output['msg']="Error: $name not set. REQUEST: ".implode(',',$_REQUEST);
		header('Content-type: application/json');
		echo json_encode( $output );
		exit();
	}
	return 	$_REQUEST[$name];
}


$action=get_value("action");
$timestamp_seconds=date("Y-m-d H:i:s");

// access info (if not WordPress)
$db_credentials = json_decode(file_get_contents("../../../../secrets/db_credentials_cult-game.json"));
$gclient_secret = json_decode(file_get_contents("../../../../secrets/gclient_secret_cult-game.json"));

$db_connection =  mysqli_connect( $db_credentials->db_server, $db_credentials->user, $db_credentials->pass  ) or die( 'Could not open connection to server' );
mysqli_select_db( $db_connection, $db_credentials->db_name) or die( 'Could not select database' );

/* SET UTF-8 independently of the MySQL and PHP installation */
mysqli_query($db_connection, "SET NAMES 'utf8'");	
mysqli_query($db_connection, "set time_zone:='Europe/Madrid'");	


function submit_data($output){
    if(!isset($_REQUEST['jsoncallback'])){
        // to solve CORS
        if(isset($_REQUEST['allow_null_CORS'])){header("Access-Control-Allow-Origin: null");}
        else{header("Access-Control-Allow-Origin: *");}
        // allow cookie passing in CORS (session maintenance)
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST"); // might want to add , PUT, DELETE, OPTIONS
        header("Access-Control-Allow-Headers: *"); // Important X-Requested-With
        header('Content-type: application/json');
        echo json_encode( $output );
    }else{
        echo $_REQUEST['jsoncallback']."(JSON.parse('".json_encode( $output )."'))";
    }
}


$output=array();

if ($action == "get_users"){
	if($_SESSION['access_level']!='admin'){echo "ERROR: no admin";return;}
	$sQuery = "SELECT * FROM users";
	//echo "query: $sQuery ";
	$rResult = mysqli_query( $db_connection, $sQuery ) or die(mysqli_error( $db_connection ));
	while ( $aRow = mysqli_fetch_array( $rResult ) )	{
		$output[$aRow['email']] = array();
		$output[$aRow['email']]['email'] = $aRow['email'];
		$output[$aRow['email']]['access_level'] = $aRow['access_level'];
	}
    submit_data($output);
}else if ($action == "add_fact"){
	$state = md5(rand());
	$output['success']="success";    
    mail("hectorlm1983@gmail.com","add ".get_value("fact")."(".$_SESSION['email'].")","Fact: ".get_value("fact")."\nbegin: ".get_value("begin")."\nend: ".get_value("end")."\n");
    submit_data($output);
}else if ($action == "gen_session_state"){
	$state = md5(rand());
	$_SESSION["state"]=$state;
	$output['state']=$state;
    submit_data($output);
}else if ($action == "login_bypass"){
    $output['error']="";
    $output['info']="bypass";
    unset($_SESSION['long_lived_access_token']);
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['email']);
    unset($_SESSION['picture']);
    if ( (get_value("state")) != ($_SESSION["state"]) && get_value("state")!='offline') {
        $output['error']="FAILURE: Forgery attack? Invalid state parameter ".get_value("state")." expected: ".$_SESSION["state"];
    }else{
        $user = $_REQUEST['user'];
        $sQuery = "SELECT * FROM users WHERE email='".$user."'";
        $rResult = mysqli_query( $db_connection, $sQuery ) or die(mysqli_error( $db_connection ));
        if ( $aRow = mysqli_fetch_array( $rResult ) ){ //existing user
            $_SESSION['access_level'] = $aRow['access_level'];
			$_SESSION['user_id'] = $user;
			$_SESSION['display_name'] = $aRow['display_name'];
			$_SESSION['picture'] = $aRow['picture'];
			$_SESSION['email'] = $user;
            $sQuery = "UPDATE users  SET last_login='$timestamp_seconds',last_provider='bypass' WHERE email='".$_SESSION['email']."';";
            $rResult = mysqli_query( $db_connection, $sQuery );
            if(!$rResult){$output['error']="Error: ".mysqli_error( $db_connection )." -- ".$sQuery;}
            $output=get_user_dump($_SESSION['email']);
        }else{
            $output['error']="Error: empty user? no user info with the token?";
        }
    }
    $output['user_id']=$_SESSION['user_id'];
    $output['display_name']=$_SESSION['display_name'];
    $output['picture']=$_SESSION['picture'];
    $output['email']=$_SESSION['email'];
    $output['access_level']=$_SESSION['access_level'];
    $output['toksum']=substr($_SESSION['long_lived_access_token']->access_token,0,5);    
    submit_data($output);
}else if ($action == "gconnect"){
    $CLIENT_ID = $gclient_secret->client_id;
    $CLIENT_SECRET = $gclient_secret->client_secret;
    $output['error']="";
    $output['info']="";
// if gconnect is called automatically with a code request saved in cookies the session access token might have expired already
// I need to study this better... maybe have a job in the server that renews the token periodically... and in any case always try to use the token before login,
// if it expired, obtain a new one...
//    if (empty($_SESSION['long_lived_access_token'])) { 
        unset($_SESSION['long_lived_access_token']);
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['email']);
        unset($_SESSION['picture']);
        if ( (get_value("state")) != ($_SESSION["state"]) ) {
            $output['error']="FAILURE: Forgery attack? Invalid state parameter ".get_value("state"); //." - ".$_SESSION["state"].". "; do not return this... to much information for a thief
        }else{
            $code = $_REQUEST['code'];
            $url = 'https://accounts.google.com/o/oauth2/token';
            $params = array(
                "code" => $code,
                "client_id" => $CLIENT_ID,
                "client_secret" => $CLIENT_SECRET,
                "redirect_uri" => "postmessage",
                "grant_type" => "authorization_code"
            ); 
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($curl);
            curl_close($curl);
            $_SESSION['long_lived_access_token'] = json_decode($response);
            // Verify the token (OPTIONAL)
            $url = 'https://www.googleapis.com/oauth2/v2/tokeninfo?access_token='.
                $_SESSION['long_lived_access_token']->access_token;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $tokenInfo = json_decode($response);
            if ($tokenInfo->error) {
                unset($_SESSION['long_lived_access_token']);
                $output['error']="ERROR in the token: ".$tokenInfo->error;
            }else if ($tokenInfo->audience != $CLIENT_ID) {
                $output['error']="ERROR: Token's client ID does not match app's."; //, 401);
            }else{
                // Get user info
                $url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token='.
                    $_SESSION['long_lived_access_token']->access_token.'&alt=json';
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => $url
                ));
                $response = curl_exec($curl);
                curl_close($curl); //echo $response;
                $userInfo = json_decode($response);
                $_SESSION['user_id'] = $userInfo->id;
                $_SESSION['display_name'] = $userInfo->name;
                $_SESSION['picture'] = $userInfo->picture;
                $_SESSION['email'] = $userInfo->email;
                $sQuery = "SELECT * FROM users WHERE email='".$userInfo->email."'"; //echo "query: $sQuery ";
                $rResult = mysqli_query( $db_connection, $sQuery ) or die(mysqli_error( $db_connection ));
                if ( $aRow = mysqli_fetch_array( $rResult ) ){ //existing user
                    $_SESSION['access_level'] = $aRow['access_level'];
                    // update the user last_login and last_provider
                    $sQuery = "UPDATE users  SET last_login='$timestamp_seconds',last_provider='google' WHERE email='".$_SESSION['email']."';";
                    $rResult = mysqli_query( $db_connection, $sQuery );
                    if(!$rResult){$output['error']="Error: ".mysqli_error( $db_connection )." -- ".$sQuery;}
                }else if(!empty($_SESSION['email'])){ //new user
                    $_SESSION['access_level'] = 'normal';
                    mail("hectorlm1983@gmail.com","New cult user","NEW USER: ".$_SESSION['email'].".  or DELETE");
                    $sQuery = "INSERT INTO users (email, display_name, access_level, last_login, last_provider, creation_timestamp) VALUES ('".$_SESSION['email']."', '".$_SESSION['display_name']."', '".$_SESSION['access_level']."', '$timestamp_seconds', 'google', '$timestamp_seconds');";
                    $rResult = mysqli_query( $db_connection, $sQuery );
                    if(!$rResult){$output['error']="Error: Exists. ".mysqli_error( $db_connection )." -- ".$sQuery;}
                    $output['info']="new user";
                }else{
                    $output['error']="Error: empty user? no user info with the token?";
                }
            }
        }
//    }
    $output['user_id']=$_SESSION['user_id'];
    $output['display_name']=$_SESSION['display_name'];
    $output['picture']=$_SESSION['picture'];
    $output['email']=$_SESSION['email'];
    $output['access_level']=$_SESSION['access_level'];
    $output['toksum']=substr($_SESSION['long_lived_access_token']->access_token,0,5);    
    submit_data($output);
}else if ($action == "gdisconnect"){
	if (empty($_SESSION['long_lived_access_token'])){
           $output['error']="No one is logged";
	}else{
		$url = 'https://accounts.google.com/o/oauth2/revoke?token='.
				$_SESSION['long_lived_access_token']->access_token;
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url
		));
		$response = curl_exec($curl);
		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		if ($httpcode == 200){
			unset($_SESSION['long_lived_access_token']);
			unset($_SESSION['user_id']);
			unset($_SESSION['username']);
			unset($_SESSION['email']);
			unset($_SESSION['picture']);
			$output['success']="Succesfully disconnected";
		}else{
            $output['error']="Failed to revoke token for given user ($httpcode - $response - token=".substr($_SESSION['long_lived_access_token']->access_token,0,5)."... user: ".$_SESSION['username'];
			unset($_SESSION['long_lived_access_token']);
			unset($_SESSION['user_id']);
			unset($_SESSION['username']);
			unset($_SESSION['email']);
			unset($_SESSION['picture']);
		}
	}
    submit_data($output);
}else if ($action == "get_alerts"){
	$sQuery = "SELECT * FROM stock_alerts where user='".$_SESSION['email']."'";
	//echo "query: $sQuery ";
	$rResult = mysqli_query( $db_connection, $sQuery ) or die(mysqli_error( $db_connection ));
	while ( $aRow = mysqli_fetch_array( $rResult ) ){
		$output[$aRow['symbol']] = array();
		$output[$aRow['symbol']]['id'] = $aRow['id'];
		$output[$aRow['symbol']]['user'] = $aRow['user'];
		$output[$aRow['symbol']]['symbol'] = $aRow['symbol'];
		$output[$aRow['symbol']]['low'] = $aRow['low'];
		$output[$aRow['symbol']]['high'] = $aRow['high'];
		$output[$aRow['symbol']]['low_change_percentage'] = $aRow['low_change_percentage'];
		$output[$aRow['symbol']]['high_change_percentage'] = $aRow['high_change_percentage'];
		$output[$aRow['symbol']]['last_alerted_date'] = $aRow['last_alerted_date'];
    }
    submit_data($output);
}else if ($action == "add_alert"){
	$user=get_value('user');
	$symbol=get_value('symbol');
	$low=get_value('low');
	$high=get_value('high');
	$low_change_percentage=get_value('low_change_percentage');
	$high_change_percentage=get_value('high_change_percentage');

	if($_SESSION['access_level']!='admin' && $user!=$_SESSION['email']){echo "ERROR: no admin or owner of alert";return;}

	$sQuery = "INSERT INTO stock_alerts (user, symbol, low, high, low_change_percentage, high_change_percentage) VALUES ('$user', '$symbol', '$low', '$high', '$low_change_percentage', '$high_change_percentage');";
	$rResult = mysqli_query( $db_connection, $sQuery );
	if(!$rResult){header('HTTP/1.1 500 Internal Server Error');die("Error: Exists. ".mysqli_error( $db_connection )." -- ".$sQuery);}
	$sQuery = "SELECT LAST_INSERT_ID() as lid;";
	$rResult = mysqli_query( $db_connection, $sQuery );
	if(!$rResult){header('HTTP/1.1 500 Internal Server Error');die("Error: ".mysqli_error( $db_connection )." -- ".$sQuery);}
	$aRow = mysqli_fetch_array( $rResult );
	$output["success"]=$symbol;
	$output["data"]=array();
	$output["data"]["id"]=$aRow['lid'];
	$output["data"]["user"]=$user;
	$output["data"]["symbol"]=$symbol;
	$output["data"]["low"]=$low;
	$output["data"]["high"]=$high;
	$output["data"]["low_change_percentage"]=$low_change_percentage;
	$output["data"]["high_change_percentage"]=$high_change_percentage;
	$output["data"]["last_alerted_date"]="";
    submit_data($output);
}else if ($action == "update_alert"){
	$lid=get_value('lid');
	$user=get_value('user');
	$symbol=get_value('symbol');
	$low=get_value('low');
	$high=get_value('high');
	$low_change_percentage=get_value('low_change_percentage');
	$high_change_percentage=get_value('high_change_percentage');

	if($_SESSION['access_level']!='admin' && $user!=$_SESSION['email']){echo "ERROR: no admin or owner of alert";return;}

	
	$sQuery = "UPDATE stock_alerts  SET low='$low',high='$high',low_change_percentage='$low_change_percentage',high_change_percentage='$high_change_percentage' WHERE id=$lid;";
	$rResult = mysqli_query( $db_connection, $sQuery );
	if(!$rResult){header('HTTP/1.1 500 Internal Server Error');die("Error: Exists. ".mysqli_error( $db_connection )." -- ".$sQuery);}
	$output["success"]=$symbol;
	$output["data"]=array();
	$output["data"]["id"]=$lid;
	$output["data"]["user"]=$user;
	$output["data"]["symbol"]=$symbol;
	$output["data"]["low"]=$low;
	$output["data"]["high"]=$high;
	$output["data"]["low_change_percentage"]=$low_change_percentage;
	$output["data"]["high_change_percentage"]=$high_change_percentage;
	$output["data"]["last_alerted_date"]="";
    submit_data($output);
}else if ($action == "delete_alert"){
	$lid=get_value('lid');
	$symbol=get_value('symbol');
	if($_SESSION['access_level']!='admin' && $user!=$_SESSION['email']){echo "ERROR: no admin or owner of alert";return;}

	$sQuery = "DELETE FROM stock_alerts WHERE id=$lid;";
	$rResult = mysqli_query( $db_connection, $sQuery );
	if(!$rResult){header('HTTP/1.1 500 Internal Server Error');die("Error: Exists. ".mysqli_error( $db_connection )." -- ".$sQuery);}
	$output["success"]=$symbol;
    submit_data($output);
}else if ($action == "send_session_data"){
	$output["msg"]="success";
	$reference=get_value("reference");
	$user=get_value("user");
	$subject=get_value("subject");
	$age=get_value("age");
	$num_answered=get_value("num_answered");
	$num_correct=get_value("num_correct");
	$result=0;
	if(((int) $num_answered)!=0) $result= ((int) $num_correct) / ((int) $num_answered);
	$level=get_value("level");
	$duration=get_value("duration");
	$timestamp=get_value("timestamp");

	if($_SESSION['access_level']!='admin' && $user!=$_SESSION['email']){echo "ERROR: no admin or owner of subject";return;}

	$sQuery = "INSERT INTO sessions(reference,user,subject,age,num_answered,num_correct,result,level,duration,timestamp)  VALUES ('$reference','$user','$subject','$age','$num_answered','$num_correct','$result','$level','$duration','$timestamp');"; 
	$rResult = mysqli_query( $db_connection, $sQuery );
	if(!$rResult){ $output["msg"]=mysqli_error( $db_connection )." -- ".$sQuery; }
	else{ $output["msg"]="Success. Data session stored in the server. --"; }
	//else{$output='{"msg":"Success. Data session stored in the server. -- '.$sQuery.'"}';}	

    submit_data($output);
}else if ($action == "send_session_data_post"){
	$str_json=json_decode($_POST['json_string'],true);
	$output["msg"]="success";
	$type=$str_json["type"];
	$level=$str_json["level"];
	$user=$str_json["user"];
	$num_correct=$str_json["num_correct"];
	$timestamp=$str_json["timestamp"];

	if($_SESSION['access_level']!='admin' && $user!=$_SESSION['email']){echo "ERROR: no admin or owner of subject";return;}

	$error=0;
	$sQuery = "INSERT INTO sessions(type,level,user,num_correct,timestamp)  VALUES ('$type','$level','$user','$num_correct','$timestamp');"; 
	$rResult = mysqli_query( $db_connection, $sQuery );
	if(!$rResult){ $output["msg"]=mysqli_error( $db_connection )." -- ".$sQuery; $error=1;}
	else{ 
		$sQuery = "	DELETE FROM sessions WHERE user='$user' AND type='$type' AND level='$level'
         AND id NOT IN (
      	 SELECT id FROM (
		  SELECT id FROM sessions WHERE user='$user' AND type='$type' AND level='$level'
		  ORDER BY num_correct*1 DESC LIMIT 3) s )";
		$rResult = mysqli_query( $db_connection, $sQuery );
		if(!$rResult){ $output["msg"]=mysqli_error( $db_connection )." -- ".$sQuery; $error=1;}
		if($error==0) $output["msg"]="Success. Data session stored in the server. --"; // -- '.$sQuery.'"}';}
	}


    submit_data($output);

}else if ($action == "get_top_scores"){
	$user=get_value("user");
	$type=get_value("type");
	$level=get_value("level");



	$output['general'] = array();
	$output['general']['user'] = $user;
	$output['general']['type'] = $type;
	$output['general']['level'] = $level;
	$output['usr_elements'] = array();
	$output['absolute_elements'] = array();

	$sQuery = "
    SELECT z.rank, z.num_correct, z.timestamp FROM (
    SELECT t.id, t.user, t.type, t.level, t.num_correct, t.timestamp, @rownum := @rownum + 1 AS rank
    FROM sessions t, (SELECT @rownum := 0) r
	WHERE t.type='$type' AND t.level='$level'
    ORDER BY num_correct+0 DESC
    ) as z WHERE z.user='$user' AND z.type='$type' AND z.level='$level' ORDER BY z.num_correct+0 DESC LIMIT 1";
	//echo "query: $sQuery ";
	$rResult = mysqli_query( $db_connection, $sQuery ) or die(mysqli_error( $db_connection ));
	$element_count=0;	
	while ( $aRow = mysqli_fetch_array( $rResult ) )	{
		// TODO find an easy way to gues the rank of the user scores (with the query)
		$output['usr_elements'][]=array();
		$output['usr_elements'][$element_count]['rank'] = $aRow['rank'];
		$output['usr_elements'][$element_count]['num_correct']=$aRow['num_correct'];
		$output['usr_elements'][$element_count]['timestamp']=$aRow['timestamp'];
		$element_count++;
	}

	$sQuery = "SELECT * FROM sessions WHERE type='$type' AND level='$level' ORDER BY num_correct+1 DESC LIMIT 10;";
	//echo "query: $sQuery ";
	$rResult = mysqli_query( $db_connection, $sQuery ) or die(mysqli_error( $db_connection ));
	$element_count=0;	
	while ( $aRow = mysqli_fetch_array( $rResult ) )	{
		$output['absolute_elements'][]=array();
		$output['absolute_elements'][$element_count]['id'] = $aRow['id'];
		$output['absolute_elements'][$element_count]['user']=$aRow['user'];
		$output['absolute_elements'][$element_count]['type']=$aRow['type'];
		$output['absolute_elements'][$element_count]['num_correct']=$aRow['num_correct'];
		$output['absolute_elements'][$element_count]['timestamp']=$aRow['timestamp'];
		$element_count++;
	}

    submit_data($output);

}else if ($action == "delete_session"){
	$id=get_value("id");
	$user=get_value("user");
	$subject=get_value("subject");
	if($_SESSION['access_level']!='admin' && $user!=$_SESSION['email']){echo "ERROR: no admin or owner of subject";return;}

	// create a detailed acction log db so that we can recover actions, authors, dates and previous states
	$sQuery = "DELETE FROM sessions WHERE id='$id' AND subject='$subject' AND user='$user';";

	$rResult = mysqli_query( $db_connection, $sQuery ) or die(mysqli_error( $db_connection ));
	$rResult = mysqli_query( $db_connection, $sQuery );
	if(!$rResult){ $output["msg"]=mysqli_error( $db_connection )." -- ".$sQuery; }
	else{ $output["msg"]="Success. Session $id of $subject deleted. --"; }	

    submit_data($output);
}else{
	$output['msg']="unsupported action";
    submit_data($output);
}

session_write_close(); // OPTIONAL: makes sure session is stored, may be add it as soon as vars are written..., should happen at the end of the script

?>

