<?php


session_start(); 

/*set_include_path(get_include_path() . PATH_SEPARATOR . '/home/hector/google-api-php-client/src/Google');
////require_once '/home/hector/google-api-php-client/src/Google/autoload.php';
require_once 'autoload.php';*/

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
$db_user="hector";
$db_pass="homohominilopus";
$db_server="mysql.cognitionis.com";
$db="cult_game";

$db_connection =  mysql_pconnect( $db_server, $db_user, $db_pass  ) or die( 'Could not open connection to server' );
mysql_select_db( $db, $db_connection) or die( 'Could not select database '. $db );

/* SET UTF-8 independently of the MySQL and PHP installation */
mysql_query("SET NAMES 'utf8'");	
mysql_query("set time_zone:='Europe/Madrid'");	

$output=array();
	
if ($action == "get_users"){
	if($_SESSION['access_level']!='admin'){echo "ERROR: no admin";return;}
	$sQuery = "SELECT * FROM users";
	//echo "query: $sQuery ";
	$rResult = mysql_query( $sQuery, $db_connection ) or die(mysql_error());
	while ( $aRow = mysql_fetch_array( $rResult ) )	{
		$output[$aRow['email']] = array();
		$output[$aRow['email']]['email'] = $aRow['email'];
		$output[$aRow['email']]['access_level'] = $aRow['access_level'];
	}
	header('Content-type: application/json');
	echo json_encode( $output );
}else if ($action == "send_session_data_post"){
	$str_json=json_decode($_POST['json_string'],true);
	$output["msg"]="success";
	$type=$str_json["type"];
	$mode=$str_json["mode"];
	$user=$str_json["user"];
	$subject=$str_json["subject"];
	$age=$str_json["age"];
	$num_answered=$str_json["num_answered"];
	$num_correct=$str_json["num_correct"];
	$result=0;
	if(((int) $num_answered)!=0) $result= ((int) $num_correct) / ((int) $num_answered);
	$level=$str_json["level"];
	$duration=$str_json["duration"];
	$timestamp=$str_json["timestamp"];

	if($_SESSION['access_level']!='admin' && $user!=$_SESSION['email']){echo "ERROR: no admin or owner of subject";return;}

	$error=0;
	
	$sQuery = "INSERT INTO sessions(type,mode,user,subject,age,num_answered,num_correct,result,level,duration,timestamp)  VALUES ('$type','$mode','$user','$subject','$age','$num_answered','$num_correct','$result','$level','$duration','$timestamp');"; 
	$rResult = mysql_query( $sQuery, $db_connection );
	if(!$rResult){ $output["msg"]=mysql_error()." -- ".$sQuery; $error=1;}
	else{ 
		$session_id=mysql_insert_id();
		foreach ($str_json["details"] as $detail){
			$sQuery = "INSERT INTO session_activities(type,mode,user,subject,session,activity,choice,result,level,duration,timestamp)  VALUES ('$type','$mode','$user','$subject','$session_id','".$detail["activity"]."','".$detail["choice"]."','".$detail["result"]."','$level','".$detail["duration"]."','".$detail["timestamp"]."')"; 
			$rResult = mysql_query( $sQuery, $db_connection );
			if(!$rResult){ $output["msg"]=mysql_error()." -- ".$sQuery; $error=1; break;}
		}
		if($error==0) $output["msg"]="Success. Data session stored in the server. --"; // -- '.$sQuery.'"}';}
	}


	header('Content-type: application/json');
	echo json_encode( $output );
	//print_r($output);

}else if ($action == "get_results"){
	$user=get_value("user");
	$subject=get_value("subject");

	if($_SESSION['access_level']!='admin' && $user!=$_SESSION['email']){echo "ERROR: no admin or owner of subject";return;}

	$sQuery = "SELECT * FROM sessions WHERE user='$user' AND subject='$subject';";
	//echo "query: $sQuery ";
	$output['general'] = array();
	$output['general']['user'] = $user;
	$output['general']['subject'] = $subject;
	$output['elements'] = array();

	$rResult = mysql_query( $sQuery, $db_connection ) or die(mysql_error());
	$element_count=0;	
	while ( $aRow = mysql_fetch_array( $rResult ) )	{
		$output['elements'][]=array();
		$output['elements'][$element_count]['id'] = $aRow['id'];
		$output['elements'][$element_count]['type']=$aRow['type'];
		$output['elements'][$element_count]['mode']=$aRow['mode'];
		$output['elements'][$element_count]['age']=$aRow['age'];
		$output['elements'][$element_count]['num_answered']=$aRow['num_answered'];
		$output['elements'][$element_count]['num_correct']=$aRow['num_correct'];
		$output['elements'][$element_count]['result']=$aRow['result'];
		$output['elements'][$element_count]['level']=$aRow['level'];
		$output['elements'][$element_count]['duration']=$aRow['duration'];
		$output['elements'][$element_count]['timestamp']=$aRow['timestamp'];
		$element_count++;		
	}

	header('Content-type: application/json');
	echo json_encode( $output );
	//print_r($output);

}else if ($action == "get_result_detail"){
	$session=get_value("session");
	$user=get_value("user");

	if($_SESSION['access_level']!='admin' && $user!=$_SESSION['email']){echo "ERROR: no admin or owner of subject ($user!=".$_SESSION['email'].")";return;}

	$sQuery = "SELECT * FROM session_activities WHERE session='$session' AND user='$user';";
	//echo "query: $sQuery ";
	$output['general'] = array();
	$output['general']['session'] = $session;
	$output['elements'] = array();

	$rResult = mysql_query( $sQuery, $db_connection ) or die(mysql_error());
	$element_count=0;	
	while ( $aRow = mysql_fetch_array( $rResult ) )	{
		$output['elements'][]=array();
		$output['elements'][$element_count]['id'] = $aRow['id'];
		$output['elements'][$element_count]['type']=$aRow['type'];
		$output['elements'][$element_count]['mode']=$aRow['mode'];
		$output['elements'][$element_count]['user']=$aRow['user'];
		$output['elements'][$element_count]['subject']=$aRow['subject'];
		$output['elements'][$element_count]['activity']=$aRow['activity'];
		$output['elements'][$element_count]['choice']=$aRow['choice'];
		$output['elements'][$element_count]['result']=$aRow['result'];
		$output['elements'][$element_count]['level']=$aRow['level'];
		$output['elements'][$element_count]['duration']=$aRow['duration'];
		$output['elements'][$element_count]['timestamp']=$aRow['timestamp'];
		$element_count++;
	}

	header('Content-type: application/json');
	echo json_encode( $output );
	//print_r($output);

}else if ($action == "delete_session"){
	$id=get_value("id");
	$user=get_value("user");
	$subject=get_value("subject");
	if($_SESSION['access_level']!='admin' && $user!=$_SESSION['email']){echo "ERROR: no admin or owner of subject";return;}

	// create a detailed acction log db so that we can recover actions, authors, dates and previous states
	$sQuery = "DELETE FROM sessions WHERE id='$id' AND subject='$subject' AND user='$user';";

	$rResult = mysql_query( $sQuery, $db_connection ) or die(mysql_error());
	$rResult = mysql_query( $sQuery, $db_connection );
	if(!$rResult){ $output["msg"]=mysql_error()." -- ".$sQuery; }
	else{ $output["msg"]="Success. Session $id of $subject deleted. --"; }	

	header('Content-type: application/json');
	echo json_encode( $output );
	//print_r($output);
}else if ($action == "gen_session_state"){
	$state = md5(rand());
	$_SESSION["state"]=$state;
	echo "$state";
/*}else if ($action == "show_secret"){
		$filec=file_get_contents("/home/hector/secrets/gclient_secret.json");
		echo "$filec";
		$gclient_secret = json_decode($filec);
		echo "<br /><br />printr:";
		print_r($gclient_secret);
		$CLIENT_ID = $gclient_secret->client_id;
		$CLIENT_SECRET = $gclient_secret->client_secret;
		echo "<br />AJAX client_id=$CLIENT_ID,secret=$CLIENT_SECRET";*/
}else if ($action == "gconnect"){
		// REQUEST contains the AuthCode
		// you can store this in a json file for more security
		$gclient_secret = json_decode(file_get_contents("/home/hector/secrets/gclient_secret_cult-game.json"));
		$CLIENT_ID = $gclient_secret->client_id;
		$CLIENT_SECRET = $gclient_secret->client_secret;
		
		/*$client = new Google_Client();
		$client->setClientId($CLIENT_ID);
		$client->setClientSecret($CLIENT_SECRET);
		$client->setRedirectUri('postmessage');*/

		if (empty($_SESSION['long_lived_access_token'])) {
			if ( (get_value("state")) != ($_SESSION["state"]) ) {
				header("Incorrect state, forgery attack?",true, 401);
				//echo "FAILURE SESSION STATE INCORRECT ".get_value("state")." - ".$_SESSION["state"].". "; 
				echo 'Invalid state parameter';
			}
			$code = $_REQUEST['code'];
			//$gPlusId = $_GET['gplus_id'];
			//echo "AJAX code=$code,client_id=$CLIENT_ID,secret=$CLIENT_SECRET, gplus_id=no necesario";
			// Exchange OAuth 2.0 authorization code for credentials (token)
			//$client->authenticate($code);
			//$_SESSION['long_lived_access_token'] = json_decode($client->getAccessToken());
			
			$url = 'https://accounts.google.com/o/oauth2/token';
			$params = array(
				"code" => $code,
				"client_id" => $CLIENT_ID,
				"client_secret" => $CLIENT_SECRET,
				"redirect_uri" => "postmessage",
				"grant_type" => "authorization_code"
			); //"redirect_uri" => "https://www.centroafan.com/oauth2callback" or "postmessage", --> this info should be already in the $code
			$curl = curl_init($url);
			
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$response = curl_exec($curl);
			curl_close($curl);
			$_SESSION['long_lived_access_token'] = json_decode($response);

			//See the tutorial on how to store session credntials (token)
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
			/*$tokenInfo = json_decode(
			  $client::getIo()->authenticatedRequest($req)->getResponseBody());*/

			// If there was an error in the token info, abort.
			if ($tokenInfo->error) {
				//return new Response($tokenInfo->error, 500);
				unset($_SESSION['long_lived_access_token']);
				echo "<br />ERROR in the token";
			}
			/*
			// Make sure the token we got is for the intended user. OPTIONAL 
			// YOU NEED TO DECODE id_token JWT (you need the public key...)
			// You can read the Google user ID in the ID token.
			// "sub" represents the ID token subscriber which in our case
			// is the user ID. This sample does not use the user ID.
			$attributes = $client->verifyIdToken($token->id_token, CLIENT_ID)
				->getAttributes();
			$gplus_id = $attributes["payload"]["sub"];
			$gplus_id2 = $_SESSION['long_lived_access_token']->id_token['sub'];
			if ($tokenInfo->userid !=gplus_id2 ) { //$gPlusId
				echo "<br />ERROR: Credentials' user ID doesn't match given user ID"; //, 401);
			//gplus_id = credentials.id_token['sub']
			//if result['user_id'] != gplus_id2:
			}*/

			// Make sure the token we got is for our app.
			//if result['issued_to'] != CLIENT_ID:
			if ($tokenInfo->audience != $CLIENT_ID) {
				echo "<br />ERROR: Token's client ID does not match app's."; //, 401);
			}

			// Store the token in the session for later use.
			//$_SESSION['token']=json_encode($token);
			//echo 'Succesfully connected';
			//print_r($_SESSION['long_lived_access_token'], true);
			
			# Get user info
			$url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token='.
				$_SESSION['long_lived_access_token']->access_token.'&alt=json';
			//$req = new Google_HttpRequest($reqUrl);
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url
			));
			$response = curl_exec($curl);
			curl_close($curl);
			$userInfo = json_decode($response);
			/*$userInfo = json_decode(
			  $client::getIo()->authenticatedRequest($req)->getResponseBody());*/
			//$userinfo_url = "https://www.googleapis.com/oauth2/v2/userinfo"
			//$params = {'access_token': credentials.access_token, 'alt': 'json'}
			//answer = requests.get(userinfo_url, params=params)

			//data = answer.json()
			$_SESSION['user_id'] = $userInfo->id;
			$_SESSION['display_name'] = $userInfo->name;
			$_SESSION['picture'] = $userInfo->picture;
			$_SESSION['email'] = $userInfo->email;
			
			$sQuery = "SELECT * FROM users WHERE email='".$userInfo->email."'";
			//echo "query: $sQuery ";
			$rResult = mysql_query( $sQuery, $db_connection ) or die(mysql_error());
			if ( $aRow = mysql_fetch_array( $rResult ) ){
				//existing user
				$_SESSION['access_level'] = $aRow['access_level'];
				// update the user last_login and last_provider
				$sQuery = "UPDATE users  SET last_login='$timestamp_seconds',last_provider='google' WHERE email='".$_SESSION['email']."';";
				$rResult = mysql_query( $sQuery, $db_connection );
				if(!$rResult){header('HTTP/1.1 500 Internal Server Error');die("Error: Exists. ".mysql_error()." -- ".$sQuery);}
			}else{ //new user
				$_SESSION['access_level'] = 'invitee';
				// insert the user in the db
				mail("hectorlm1983@gmail.com","New afan-app user","NEW USER: ".$_SESSION['email'].". Change from 'invitee' to something else or DELETE");
				$sQuery = "INSERT INTO users (email, display_name, access_level, last_login, last_provider, creation_timestamp) VALUES ('".$_SESSION['email']."', '".$_SESSION['display_name']."', '".$_SESSION['access_level']."', '$timestamp_seconds', 'google', '$timestamp_seconds');";
				$rResult = mysql_query( $sQuery, $db_connection );
				if(!$rResult){header('HTTP/1.1 500 Internal Server Error');die("Error: Exists. ".mysql_error()." -- ".$sQuery);}
			}
			header('Content-type: application/json');
			
	}
	$output['username']=$_SESSION['username'];
	$output['email']=$_SESSION['email'];
	$output['access_level']=$_SESSION['access_level'];
	$output['toksum']=substr($_SESSION['long_lived_access_token']->access_token,0,5);
	header('Content-type: application/json');
	echo json_encode( $output );
}else if ($action == "gdisconnect"){
	//if (!isset($_SESSION['long_lived_access_token'])) echo "No one is logged";
	
	if (empty($_SESSION['long_lived_access_token'])) $output['error']="No one is logged";
	else{
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
			unset($_SESSION['userid']);
			unset($_SESSION['username']);
			unset($_SESSION['email']);
			unset($_SESSION['picture']);
			$output['success']="Succesfully disconnected";
		}else{
			unset($_SESSION['long_lived_access_token']);
			unset($_SESSION['userid']);
			unset($_SESSION['username']);
			unset($_SESSION['email']);
			unset($_SESSION['picture']);
			$output['error']="Failed to revoke token for given user ($httpcode - $response - token=".substr($_SESSION['long_lived_access_token']->access_token,0,5)."...";
		}
	}
	header('Content-type: application/json');
	echo json_encode( $output );
}else{
	$output['msg']="unsupported action";
	header('Content-type: application/json');
	echo json_encode( $output );
}

session_write_close(); // OPTIONAL: makes sure session is stored, may be add it as soon as vars are written..., should happen at the end of the script

?>
