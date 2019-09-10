<?php

require_once("email_config.php");
require_once("stock_list.php");
require_once 'stock_helper_functions.php';


function get_asset($symbol,$debug=false){
    $query_arr=explode(":",$symbol);
    $name=$query_arr[1];
    $market=$query_arr[0];
    $stock_financials_old=array(); // to store stocks.assets, typo "assets"
    if(!isset($symbol)){echo "ERROR: empty sybmol";exit(1);}
    if(file_exists ( 'stocks_financialsa.json' )){
        if($debug) echo "stocks_financialsa.json exists -> reading...<br />";
        $stock_financials_old = json_decode(file_get_contents('stocks_financialsa.json'), true);
    }else{
        echo "stocks_financialsa.json does NOT exist -> using an empty array<br />";
    }
    $stock_financial=array();
    if(substr($symbol,0,5)=="INDEX"){echo "<br />index (no data), nothing to be done<br />"; return;} // skip indexes
    $url_and_query="https://finance.yahoo.com/quote/".get_yahoo_quote($symbol)."/balance-sheet?p=".get_yahoo_quote($symbol); //get_msn_quote($the_url_query_arr[$current_num_to_curl]);
    echo "asset $url_and_query<br />";
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url_and_query );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );
    preg_match("/^.*moved to <a href=\".*stockdetails\/([^\"]*)\">.*$/m", $response, $redirect);
	if($debug) var_dump($redirect);
    if(count($redirect)>0){
		$url_and_query=$the_url.$redirect[1];
		echo "stock $url_and_query<br />";
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $url_and_query );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $curl ); //utf8_decode( not necessary
		curl_close( $curl );
	}
    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
    //if($debug) echo "base .<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<title>/", "\ntd <title>", $response);
    $response=preg_replace("/<\/title>/", "\n", $response);
    $response=preg_replace("/<tr/", "\ntr", $response);
    if($debug)  echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<\/(tr|table|ul)>/", "\n", $response);
    //$response=preg_replace("/^[^t][^d].*$/m", "", $response);
    $response = preg_replace('/^[ \t]*[\r\n]+/m', '', $response); // remove blank lines
    if($debug) echo "----------end----------";
    
    $manual_update=false;
    preg_match("/^.*data-reactid=\"26\"[^C]*Currency in (...)\./m", $response, $currency);
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
    
    preg_match("/^.*Period Ending(.*)$/m", $response, $xxxx);
    preg_match_all("/<span[^>]*>([^\/]*\/[^\/]*\/[^<]*)<\/span>/", $xxxx[1], $period_arr);
    if($debug) echo "<br />";
    
	
	
    if(count($xxxx)==0){
		if(!array_key_exists($symbol,$stock_financials_old)){
			echo "ERROR: trying to get assets first time for symbol with no data ".$symbol;
			send_mail('ERROR financial '.$name,"$url_and_query<br />first time assets and count periods is 0, no data<br /><br />","hectorlm1983@gmail.com");
			return $stock_financial;
		}else{
			$stock_financial=$stock_financials_old[$symbol];
		    ksort($stock_financial);
			$keys=array_keys($stock_financial);
			$last_fin_year=intval(substr($keys[count($keys)-3],0,4));
			$curr_year=intval(date("Y"));
			$curr_month=intval(date("m"));
			if($last_fin_year<($curr_year-3) || ($last_fin_year<($curr_year-2)) && $curr_month>4 ){
				send_mail('ERROR asset OLD '.$name,"$url_and_query<br />No data (periods) found and the last asset is older than 2 periods, manual update needed...<br /><br />","hectorlm1983@gmail.com");
			}
			echo "<br />ERROR no data but old data exists... $last_fin_year<br />";
			return $stock_financial;
		}
	}else{
		
		
		
		// EQUITY=TOTAL ASSETS - TOTAL LIABILITES
		$vars2get=['Total Assets','Total Liabilities']; // ,'Total Stockholder Equity''Total Current Assets','Total Current Liabilities'

		$results=array();
		foreach($vars2get as $var2get){
			if($debug) echo "getting results for: $var2get<br />";
			preg_match("/".$var2get."\s*<(.*)$/m", $response, $xxxx);
			preg_match_all("/<td[^>]*>(?:<span[^>]*>)?([^<]*)(?:<\/span>)?<\/td>/", $xxxx[1], $xxxx_arr);
			if(count($period_arr[1])!=count($xxxx_arr[1])){
				echo "<br />count periods and count data is not the same...<br /><br />";
				send_mail('ERROR assets '.$name,"$url_and_query<br />count periods and count data is not the same...<br /><br />","hectorlm1983@gmail.com");
				exit(1);
			}
			$results[$var2get]=$xxxx_arr[1];
		}
		
		


		$first_time_financials=false;
		if(!array_key_exists($symbol,$stock_financials_old)){
			echo "first time getting assets for ".$symbol;
			$first_time_financials=true;
			send_mail('First time assets '.$name,"$url_and_query<br />Yay, first time<br /><br />","hectorlm1983@gmail.com");
			$stock_financial['name']=$name;
			$stock_financial['market']=$market;
		}else{
			$stock_financial=$stock_financials_old[$symbol];
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
					if($new_month>=$original_month){
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
					}else{
						$stock_financial[$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
						echo "<br />New period ".$period_arr[1][$period]." var:".$var2get.":".$stock_financial[$period_arr[1][$period]][$var2get]."<br />";
						$new_period_report.="<br />".$period_arr[1][$period]." var:".$var2get.":".$stock_financial[$period_arr[1][$period]][$var2get]."<br />";
					}
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
			if(count($stock_financial[$period_arr[1][$period]])==0){
				unset($stock_financial[$period_arr[1][$period]]); 
			}
		}
		if($new_period_report!="" && !$first_time_financials){
			if(!$manual_update){
				echo 'new financials '.$name,"$url_and_query<br />In ".$symbol." ".$new_period_report."<br /><br />";
				//send_mail('new financials '.$name,"$url_and_query<br />In ".$symbol." ".$new_period_report."<br /><br />","hectorlm1983@gmail.com");
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
}

if( isset($_REQUEST['symbol']) ){
    $debug=false;
    if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
        $debug=true;
    }
    echo "symbol found, manual mode<br /><br />";
    $stock_financial=get_asset($_REQUEST['symbol'],$debug);
    echo "Result:<br />---<pre>".json_encode($stock_financial, JSON_PRETTY_PRINT)."</pre>---<br />";
}


?>