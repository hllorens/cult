<?php

require_once("email_config.php");
require_once("stock_list.php");
require_once 'stock_helper_functions.php';


              
function get_earnings($symbol,$debug=false,$force=false){
	$FIREBASE='https://cult-game.firebaseio.com/';
	// https://www.investing.com/equities/google-inc-c-earnings
	$json_file_name="stocks_earnings.json";
	$agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
      
	$query_arr=explode(":",$symbol);
    $name=$query_arr[1];
    $market=$query_arr[0];
	$json_name=$name;
	if($name==".INX"){$json_name="INX";}
    $file_old=array(); // to store stocks.assets, typo "assets"
    if(!isset($symbol)){echo "ERROR: empty sybmol";exit(1);}
    if(file_exists ( $json_file_name )){
        if($debug) echo "$json_file_name exists -> reading...<br />";
        $file_old = json_decode(file_get_contents($json_file_name), true);
    }else{
        echo "$json_file_name does NOT exist -> using an empty array<br />";
    }
    $stock_financial=array();
    if(substr($symbol,0,5)=="INDEX"){echo "<br />index (no data), nothing to be done<br />"; return;} // skip indexes
    $url_and_query="https://www.investing.com/equities/".get_investing_quote($symbol)."-earnings"; 
    echo "$url_and_query<br />";
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url_and_query );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($curl, CURLOPT_USERAGENT, $agent);
    $response2 = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );
    $response2=preg_replace("/(\n|&nbsp;)/", " ", $response2);
				 
    //if($debug) echo "base .<pre>".htmlspecialchars($response2)."</pre>";
    $response2=preg_replace("/<title>/", "\ntd <title>", $response2);
    $response2=preg_replace("/<\/title>/", "\n", $response2);
    $response2=preg_replace("/<tr/", "\ntr", $response2);
				  
    //if($debug)  echo "aaa.<pre>".htmlspecialchars($response2)."</pre>";
    $response2=preg_replace("/<\/(tr|table|ul)>/", "</tr>\n", $response2);
    //$response2=preg_replace("/^[^t][^d].*$/m", "", $response2);
    $response2 = preg_replace('/^[ \t]*[\r\n]+/m', '', $response2); // remove blank lines
    if($debug) echo "----------end----------";
    
    //preg_match("/^.*earningsHistory100160(.*)$/m", $response2, $xxxx);
    preg_match_all("/tr name=.*instrumentEarningsHistory\"[^>]*>(.*)<\/tr>/", $response2, $period_arr);
	//var_dump($period_arr);
	
	if(!array_key_exists($symbol,$file_old)){
		echo "first time getting for ".$symbol;
		$first_time_financials=true;
		$stock_financial['name']=$name;
		$stock_financial['market']=$market;
	}else{
		$stock_financial=$file_old[$symbol]; 
	}
	if(count($period_arr)==0 || count($period_arr[0])==0){
		$first_time="";
		if(!array_key_exists($symbol,$file_old)){
			$first_time="first_time";
		}
		echo "<br />ERROR no data but old data exists... $last_fin_year<br />";
		send_mail('ERROR  '.$name.' '.$url_and_query,"$url_and_query<br /> $first_time no data<br /><br />","hectorlm1983@gmail.com");
		return $stock_financial;
	}else{
		for ($period=0;$period<count($period_arr[0]);$period++){
			//echo "<br />index:$period, period: <pre>".htmlspecialchars($period_arr[0][$period])."</pre><br />";
			$data_line=str_replace("\"","",$period_arr[0][$period]);
			$data_line = preg_replace('!\s\s+!', ' ', $data_line);
			$data_line=preg_replace('/<[\/]*td[^>]*>/', '', $data_line);
			$data_line=preg_replace('/^.*event_timestamp=([^>]*)>\s+(.*)$/', '${1} ${2}', $data_line);
			//echo "aa $data_line<br />"; 
			$data_arr=explode(" ",$data_line);
			if(count($data_arr)<10){
				echo "ERROR: Bogus data line $data_line<br />";
				return;
			}
			//var_dump($data_arr); 
			if($debug) echo "published:".$data_arr[0]." period:".$data_arr[4]." eps_f:".$data_arr[5]." eps:".$data_arr[7]." rev_f:".$data_arr[8]." rev:".$data_arr[10]." "."<br />";
			$quarter_arr=explode("/",$data_arr[4]);
			$quarter=$quarter_arr[1]."-".$quarter_arr[0];
			
			if(!array_key_exists($quarter,$stock_financial)){
				echo "new quarter $quarter<br />";
				$stock_financial[$quarter]=array();
			}else{
				if($stock_financial[$quarter]['rev']!=format_billions(str_replace(",","",$data_arr[8]))){
					echo "past changed rev old ".$stock_financial[$quarter]['rev']." vs new ".format_billions(str_replace(",","",$data_arr[8]))."<br />";
					// STORE CHANGE PAST HISTORY AND SEND IT ONCE A QUARTER...!! SILENT MODE
					
					// TODO
				}
			}
			$stock_financial[$quarter]['published']=$data_arr[0];
			$stock_financial[$quarter]['quarter']=$quarter; 
			$stock_financial[$quarter]['eps_f']=str_replace(",","",$data_arr[7]);
			$stock_financial[$quarter]['eps']=str_replace(",","",$data_arr[5]);
			$stock_financial[$quarter]['rev_f']=format_billions(str_replace(",","",$data_arr[10]));
			$stock_financial[$quarter]['rev']=format_billions(str_replace(",","",$data_arr[8]));
			
			
		}
		//var_dump($stock_financial);
		
		// Update old var
		$file_old[$symbol]=$stock_financial;
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $FIREBASE .'earnings/'. $json_name.':'.$market.'.json' ); ///'.$usr.'_'.$symbol.'
		curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "PUT" );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode($stock_financial) );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $curl );
		curl_close( $curl ); 
		
		echo date('Y-m-d H:i:s')." updating $json_file_name\n";
		$file_old_str=json_encode( $file_old );
		$stocks_financials_json_file = fopen($json_file_name, "w") or die("Unable to open file $json_file_name!");
		fwrite($stocks_financials_json_file, $file_old_str);
		fclose($stocks_financials_json_file);
		
		ksort($stock_financial);
		return $stock_financial;
		

	}

	
}
 
if( isset($_REQUEST['symbol']) ){
    $debug=false;
    if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
        $debug=true;
    }
	$force=false;
    if( isset($_REQUEST['force']) && ($_REQUEST['force']=="true" || $_REQUEST['force']=="1")){
        $force=true;
    }
	
    echo "symbol found, manual mode<br /><br />";
    $stock_data=get_earnings($_REQUEST['symbol'],$debug,$force);
    echo "Result:<br />---<pre>".json_encode($stock_data, JSON_PRETTY_PRINT)."</pre>---<br />";
}


/*

    $manual_update=false;
    preg_match("/^.*data-reactid=\"26\"[^C]*Currency in (...)\./m", $response2, $currency);
    if(count($currency)>1){
        $currency=$currency[1];
    }else{
        $currency="USD";
    }
    //if($currency!="USD"){}
    if($currency!="USD" && $currency!="EUR"){ // maybe only USD should be there... but... to avoid emails
        $manual_update=true;
        echo "<br />currency:$currency<br />";
        //send_mail('Manual '.$name,"$url_and_query<br />currency:$currency<br /><br />","hectorlm1983@gmail.com");
    }
    
    if($debug) echo "<br />";
	instrumentEarningsHistory
    
	
	

		
		
		 
		// EQUITY=TOTAL ASSETS - TOTAL LIABILITES
		$vars2get=['Total Assets','Total Liabilities']; // ,'Total Stockholder Equity''Total Current Assets','Total Current Liabilities'

		$results=array();
		foreach($vars2get as $var2get){
			if($debug) echo "getting results for: $var2get<br />";
			preg_match("/".$var2get."\s*<(.*)$/m", $response2, $xxxx);
			preg_match_all("/<td[^>]*>(?:<span[^>]*>)?([^<]*)(?:<\/span>)?<\/td>/", $xxxx[1], $xxxx_arr);
			if(count($period_arr[1])!=count($xxxx_arr[1])){
				echo "<br />count periods and count data is not the same...<br /><br />";
				send_mail('ERROR assets '.$name,"$url_and_query<br />count periods and count data is not the same...<br /><br />","hectorlm1983@gmail.com");
				exit(1);
			}
			$results[$var2get]=$xxxx_arr[1];
		}
		
		


		$first_time_financials=false;
		if(!array_key_exists($symbol,$file_old)){
			echo "first time getting assets for ".$symbol;
			$first_time_financials=true;
			send_mail('First time assets '.$name,"$url_and_query<br />Yay, first time<br /><br />","hectorlm1983@gmail.com");
			$stock_financial['name']=$name;
			$stock_financial['market']=$market;
		}else{
			$stock_financial=$file_old[$symbol];
		}

		$new_period_report="";
		$change_past_report="";
		for ($period=0;$period<count($period_arr[1]);$period++){
			echo "<br />index:$period, period: ".$period_arr[1][$period]."<br />";
			// Here we could detect if past is being changed...
			$period_arr_arr=explode("/",$period_arr[1][$period]);
			if(count($period_arr_arr)!=3 || strlen($period_arr_arr[2])!=4){
				echo "ERROR (assets): incorrect format ".$period_arr[1][$period];
				send_mail('Error assets '.$name,"$url_and_query<br />"."ERROR (MSN INCOME): incorrect format period ".$period_arr[1][$period]."<br /><br />","hectorlm1983@gmail.com");
				continue;
			}
			if(intval($period_arr_arr[2])<2010){ // Exception for ELE, && $name!="ELE"
				echo "ERROR (assets): incorrect year ".$period_arr[1][$period];
				if($name!="ELE"){
					send_mail('Error assets '.$name,"$url_and_query<br />"."ERROR (MSN INCOME): incorrect year period ".$period_arr[1][$period]."<br /><br />","hectorlm1983@gmail.com");
				}
				continue;
			}
			$period_arr[1][$period]=$period_arr_arr[2]."-".str_pad($period_arr_arr[0],2,"0",STR_PAD_LEFT)."-".str_pad($period_arr_arr[1],2,"0",STR_PAD_LEFT);
			$new_period=true;
			$keep_old=false;
			foreach(array_keys($stock_financial) as $stock_financial_period){
				if($period_arr[1][$period]==$stock_financial_period){
					$new_period=false;
					break;
				}
				if(substr($period_arr[1][$period],0,4)==substr($stock_financial_period,0,4)){
					$new_period=false;
					$original_month=floatval(substr($stock_financial_period,5,2));
					$new_month=floatval($period_arr_arr[0]);
					if($new_month>$original_month){
						$new_period=true;
						echo "<br />ERROR: asset period change same year new ".$period_arr[1][$period]." vs existing ".$stock_financial_period." keeping the new with higher month<br />";
						send_mail('Error asset '.$name,"$url_and_query<br /> asset period change same year new ".$period_arr[1][$period]." vs existing ".$stock_financial_period.", keeping the one with higher month <br /><br />","hectorlm1983@gmail.com");
						unset($stock_financial[$stock_financial_period]);
						break;
					}else{
						echo "<br />ERROR: asset period change same year new ".$period_arr[1][$period]." vs existing ".$stock_financial_period." keeping the old with higher month<br />";
						$keep_old=true;
						break;
					}
				}
			}
			if($keep_old){
				continue;
			}
			if($first_time_financials || $new_period){
				$stock_financial[$period_arr[1][$period]]=array();
			}
			foreach($vars2get as $var2get){
				if($debug) echo "updating vars: $var2get<br />";
				$results[$var2get][$period]=str_replace(",","",$results[$var2get][$period]);
				if(!array_key_exists($var2get,$stock_financial[$period_arr[1][$period]])){
					if($results[$var2get][$period]=="-" || $results[$var2get][$period]==""){
						$results[$var2get][$period]=0;
						$latelog = fopen("late.log", "a") or die("Unable to open/create late.log!");
						fwrite($latelog, date('Y-m-d H:i:s')."  stock_curl_asset.php. Error financials ".$name.". Empty - in $var2get, setting 0<br />\n");
						fclose($latelog);
						//send_mail('Error assets '.$name,"<br />Empty - in $var2get, setting 0<br /><br />","hectorlm1983@gmail.com");
					}
					$stock_financial[$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
					echo "<br />New period ".$period_arr[1][$period]." var:".$var2get.":".$stock_financial[$period_arr[1][$period]][$var2get]."<br />";
					$new_period_report.="<br />".$period_arr[1][$period]." var:".$var2get.":".$stock_financial[$period_arr[1][$period]][$var2get]."<br />";
				}else{
					if(!$manual_update){
						if($results[$var2get][$period]=="-" || $results[$var2get][$period]==""){
							echo "<br />".$period_arr[1][$period]." var:".$var2get." empty (".$results[$var2get][$period].") using the existing past...$var2get=".$stock_financial[$period_arr[1][$period]][$var2get]."<br />";
							$latelog = fopen("late.log", "a") or die("Unable to open/create late.log!");
							fwrite($latelog, date('Y-m-d H:i:s')."  stock_curl_asset.php. Error financials ".$name.". ".$period_arr[1][$period]." var:".$var2get." empty (".$results[$var2get][$period].") using the existing past...$var2get=".$stock_financial[$period_arr[1][$period]][$var2get]."<br />\n");
							fclose($latelog);
							//$change_past_report.="<br />".$period_arr[1][$period]." var:".$var2get." empty (".$results[$var2get][$period].") using the existing past... $var2get=".$stock_financial[$period_arr[1][$period]][$var2get]."<br />";
						}else if($stock_financial[$period_arr[1][$period]][$var2get]!=$results[$var2get][$period]){
							if(abs(floatval($stock_financial[$period_arr[1][$period]][$var2get])-floatval($results[$var2get][$period]))>abs(floatval($results[$var2get][$period])/10)){
								$diff=toFixed(((floatval($results[$var2get][$period])-floatval($stock_financial[$period_arr[1][$period]][$var2get]))/max(abs(floatval($results[$var2get][$period])),0.1))*100,2,"asset diff");
								echo "ERROR changing the past for ".$period_arr[1][$period]." $var2get significantly ($diff%) !!! (keeping new value, email sent)<br />";
								$change_past_report.="<br />".$period_arr[1][$period]." var:".$var2get."  old:".$stock_financial[$period_arr[1][$period]][$var2get]." != new:".$results[$var2get][$period]." ($diff% greater than 10% diff). Keeping new.<br />";
							}
							$stock_financial[$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
						}
					}
				}
			}
		}
		if($new_period_report!="" && !$first_time_financials){
			if(!$manual_update){
				send_mail('new financials '.$name,"$url_and_query<br />In ".$symbol." ".$new_period_report."<br /><br />","hectorlm1983@gmail.com");
			}else{
				send_mail('IMP update manually '.$name,"$url_and_query<br />In ".$symbol." (seek new info in the correct currency) ".$new_period_report."<br /><br />","hectorlm1983@gmail.com");
			}
		}
		if($change_past_report!=""){
			send_mail('financials change past '.$name,"$url_and_query<br />In ".$symbol." ".$change_past_report."<br /><br />","hectorlm1983@gmail.com");
		}
		ksort($stock_financial);
		return $stock_financial;
	}


*/


?>