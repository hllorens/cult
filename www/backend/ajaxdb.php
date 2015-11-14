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

$db_connection =  mysql_pconnect( $db_credentials->db_server, $db_credentials->user, $db_credentials->pass  ) or die( 'Could not open connection to server' );
mysql_select_db( $db_credentials->db_name, $db_connection) or die( 'Could not select database' );

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
	$level=$str_json["level"];
	$user=$str_json["user"];
	$num_correct=$str_json["num_correct"];
	$timestamp=$str_json["timestamp"];

	if($_SESSION['access_level']!='admin' && $user!=$_SESSION['email']){echo "ERROR: no admin or owner of subject";return;}

	$error=0;
	$sQuery = "INSERT INTO sessions(type,level,user,num_correct,timestamp)  VALUES ('$type','$level','$user','$num_correct','$timestamp');"; 
	$rResult = mysql_query( $sQuery, $db_connection );
	if(!$rResult){ $output["msg"]=mysql_error()." -- ".$sQuery; $error=1;}
	else{ 
		$sQuery = "	DELETE FROM sessions WHERE user='$user' AND type='$type' AND level='$level'
         AND id NOT IN (
      	 SELECT id FROM (
		  SELECT id FROM sessions WHERE user='$user' AND type='$type' AND level='$level'
		  ORDER BY num_correct*1 DESC LIMIT 3) s )";
		$rResult = mysql_query( $sQuery, $db_connection );
		if(!$rResult){ $output["msg"]=mysql_error()." -- ".$sQuery; $error=1;}
		if($error==0) $output["msg"]="Success. Data session stored in the server. --"; // -- '.$sQuery.'"}';}
	}


	header('Content-type: application/json');
	echo json_encode( $output );
	//print_r($output);

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
	$rResult = mysql_query( $sQuery, $db_connection ) or die(mysql_error());
	$element_count=0;	
	while ( $aRow = mysql_fetch_array( $rResult ) )	{
		// TODO find an easy way to gues the rank of the user scores (with the query)
		$output['usr_elements'][]=array();
		$output['usr_elements'][$element_count]['rank'] = $aRow['rank'];
		$output['usr_elements'][$element_count]['num_correct']=$aRow['num_correct'];
		$output['usr_elements'][$element_count]['timestamp']=$aRow['timestamp'];
		$element_count++;
	}

	$sQuery = "SELECT * FROM sessions WHERE type='$type' AND level='$level' ORDER BY num_correct+1 DESC LIMIT 10;";
	//echo "query: $sQuery ";
	$rResult = mysql_query( $sQuery, $db_connection ) or die(mysql_error());
	$element_count=0;	
	while ( $aRow = mysql_fetch_array( $rResult ) )	{
		$output['absolute_elements'][]=array();
		$output['absolute_elements'][$element_count]['id'] = $aRow['id'];
		$output['absolute_elements'][$element_count]['user']=$aRow['user'];
		$output['absolute_elements'][$element_count]['type']=$aRow['type'];
		$output['absolute_elements'][$element_count]['num_correct']=$aRow['num_correct'];
		$output['absolute_elements'][$element_count]['timestamp']=$aRow['timestamp'];
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
		$CLIENT_ID = $gclient_secret->client_id;
		$CLIENT_SECRET = $gclient_secret->client_secret;
		
		/*$client = new Google_Client();
		$client->setClientId($CLIENT_ID);
		$client->setClientSecret($CLIENT_SECRET);
		$client->setRedirectUri('postmessage');*/
        $output['error']="";
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
			//echo $response;
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
				//echo $sQuery;
				$rResult = mysql_query( $sQuery, $db_connection );
				if(!$rResult){$output['error']="Error: ".mysql_error()." -- ".$sQuery;}
			}else{ //new user
				$_SESSION['access_level'] = 'normal';
				// insert the user in the db
				mail("hectorlm1983@gmail.com","New cult user","NEW USER: ".$_SESSION['email'].". Should be 'normal' already... DELETE?");
				$sQuery = "INSERT INTO users (email, display_name, access_level, last_login, last_provider, creation_timestamp) VALUES ('".$_SESSION['email']."', '".$_SESSION['display_name']."', '".$_SESSION['access_level']."', '$timestamp_seconds', 'google', '$timestamp_seconds');";
				$rResult = mysql_query( $sQuery, $db_connection );
				if(!$rResult){$output['error']="Error: Exists. ".mysql_error()." -- ".$sQuery;}
			}
	}
	$output['display_name']=$_SESSION['display_name'];
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
