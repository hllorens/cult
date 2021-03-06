<?php

require_once("email_config.php");
require_once("stock_list.php");
require_once 'stock_helper_functions.php';

function get_financial($symbol,$debug=false){
    if(!isset($symbol)){echo "ERROR: empty sybmol";exit(1);}
	
	// retrieve financials_old and full or initialize to empty
    $stock_financials_old=array(); 
    $stocks_financialsr_full=array();
    if(file_exists ( 'stocks_financialsr.json' )){
        if($debug) echo "stocks_financialsr.json exists -> reading...<br />";
        $stock_financials_old = json_decode(file_get_contents('stocks_financialsr.json'), true);
    }else{
        echo "stocks_financialsr.json does NOT exist -> using an empty array<br />";
    }
    if(file_exists ( 'stocks_financialsr_full.json' )){
        if($debug) echo "stocks_financialsr_full.json exists -> reading...<br />";
        $stocks_financialsr_full = json_decode(file_get_contents('stocks_financialsr_full.json'), true);
    }else{
        echo "stocks_financialsr_full.json does NOT exist -> using an empty array<br />";
    }
	// create a var just for the current symbol
    $stock_financial=array();
    $stock_financial_full=array();

    if(substr($symbol,0,5)=="INDEX"){echo "<br />index (no data), nothing to be done<br />"; return;} // skip indexes

    // google: does not have financials for BME/MCE/MC
    // yahoo:  https://finance.yahoo.com/quote/SGRE.MC/financials?p=SGRE.MC // prob multiple pages: balance-sheet?p cash-flow?p ...
    // yahoo-statistics: https://finance.yahoo.com/quote/SGRE.MC/key-statistics?p=SGRE.MC
    // msn: https://www.msn.com/en-us/money/stockdetails/financials/ //fi-199.1.SGRE.MCE (the number depends on the country)
    $the_url="https://www.msn.com/en-us/money/stockdetails/financials/"; //fi-199.1.SGRE.MCE (the number depends on the country)
    $url_and_query=$the_url.get_msn_quote($symbol); //get_msn_quote($symbol);
    echo "financial $url_and_query<br />";
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url_and_query );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );
    preg_match("/^.*moved to <a href=\".*stockdetails\/([^\"]*)\">.*$/m", $response, $redirect);
	if($debug) var_dump($redirect);
    if(count($redirect)>0){
		$url_and_query=$the_url.$redirect[1];
		$msn_mapping=array(); 
		if(file_exists ( 'msn_mapping.json' )){
			$msn_mapping = json_decode(file_get_contents('msn_mapping.json'), true);
		}
		if(array_key_exists($symbol,$msn_mapping)){
			if($msn_mapping[$symbol]!=$redirect[1]){
				$msn_mapping['aERROR'.$symbol]=$msn_mapping[$symbol];
			}
		}
		$msn_mapping[$symbol]=$redirect[1];
		$json_str=json_encode( $msn_mapping );
		echo date('Y-m-d H:i:s')." updating msn_mapping.json\n";
		$json_file = fopen("msn_mapping.json", "w") or die("Unable to open file msn_mapping.json!");
		fwrite($json_file, $json_str);
		fclose($json_file);
		
		echo "stock $url_and_query<br />";
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $url_and_query );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $curl ); //utf8_decode( not necessary
		curl_close( $curl );
	}
    $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
    if($debug) echo "base .<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<title>/", "\ntd <title>", $response);
    $response=preg_replace("/<\/title>/", "\n", $response);
    $response=preg_replace("/<td/", "\ntd", $response);
    //if($debug)  echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    $response=preg_replace("/<\/(td|table|ul)>/", "\n", $response);
    //$response=preg_replace("/^[^t][^d].*$/m", "", $response);
    $response = preg_replace('/^[ \t]*[\r\n]+/m', '', $response); // remove blank lines
    $response = preg_replace('/\n(.*=\"val\".*)[\r\n]+/m', '${1}', $response); // remove blank lines
    $response = preg_replace('/title=\'(Period End Date|Total Revenue|Operating Income|Net Income|Total Current Liabilities|Total Liabilities|Total Equity|Diluted EPS|Basic EPS|Cash Flow from Operating Activities|Cash Flow from Investing Activities|Cash Flow from Financing Activities|Free Cash Flow)\'[^>]*>\s*/', "\n", $response);
    //$response = preg_replace('/\ntd class="lft name">Return on average equity\s*\ntd class=period>/',"\ntd class=\"lft name\">Return on average equity td class=period>",$response);
    //    if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
    if($debug) echo "----------end----------";
    preg_match("/^Period End Date(.*)$/m", $response, $xxxx);
    
	// HANDLE EMPTY DATA
	if(count($xxxx)==0){
		if(!array_key_exists($symbol,$stock_financials_old)){
			echo "ERROR: trying to get financials first time for symbol with no data ".$symbol;
			send_mail('ERROR financial '.$symbol,"$url_and_query<br />first time financials and count periods is 0, no data<br /><br />","hectorlm1983@gmail.com");
			return $stock_financial;
		}else{
			$stock_financial=$stock_financials_old[$symbol];
		    ksort($stock_financial);
			$keys=array_keys($stock_financial);
			$last_fin_year=intval(substr($keys[count($keys)-3],0,4));
			$curr_year=intval(date("Y"));
			$curr_month=intval(date("m"));
			if($last_fin_year<($curr_year-3) || ($last_fin_year<($curr_year-2)) && $curr_month>4 ){
				send_mail('ERROR financial OLD '.$name,"$url_and_query<br />No data (periods) found and the last financial is older than 2 periods, manual update needed...<br /><br />","hectorlm1983@gmail.com");
			}
			echo "<br />ERROR no data but old data exists... $last_fin_year<br />";
			return $stock_financial;
		}
		
	}
	
		
	// GET PERIODS
	preg_match_all("/title='([^\/]*\/[^\/]*\/[^']*)'/", $xxxx[1], $period_arr);
	if($debug){var_dump($period_arr[1]);echo "<br />";}
	
	// FORMAT PERIODS
	$formatted_periods_years=array();
	for ($period=0;$period<count($period_arr[1]);$period++){
		echo "<br />index:$period, period: ".$period_arr[1][$period]."<br />";
		$period_arr_arr=explode("/",$period_arr[1][$period]);
		if(count($period_arr_arr)!=3 || strlen($period_arr_arr[2])!=4){
			echo "ERROR (financials): incorrect format ".$period_arr[1][$period];
			send_mail('Error financials '.$name,"$url_and_query<br />"."ERROR (MSN INCOME): incorrect format period ".$period_arr[1][$period]."<br /><br />","hectorlm1983@gmail.com");
			continue;
		}
		if(intval($period_arr_arr[2])<2010){
			echo "ERROR (financials): incorrect year ".$period_arr[1][$period];
			send_mail('Error financials '.$name,"$url_and_query<br />"."ERROR (MSN INCOME): incorrect year period ".$period_arr[1][$period]."<br /><br />","hectorlm1983@gmail.com");
			continue;
		}
		// normalize period
		$day=str_pad($period_arr_arr[1],2,"0",STR_PAD_LEFT);
		$month=str_pad($period_arr_arr[0],2,"0",STR_PAD_LEFT);
		$year=$period_arr_arr[2];
		$period_arr[1][$period]=normalize_period($year."-".$month."-".$day);
		$formatted_periods_years[]=substr($period_arr[1][$period],0,4);
	}
	// CHECK PERIODS
	//var_dump($period_arr[1]);
	if(count($formatted_periods_years)!=count(array_unique($formatted_periods_years))){
		echo "<br />PERIODS do not follow consecutive years<br /><br />";
		send_mail('ERROR financial '.$name,"$url_and_query<br />periods do not follow consecutive years...<br /><br />","hectorlm1983@gmail.com");
		exit(1);
	}
	
	// GET VARS
	$vars2get=['Total Revenue','Operating Income','Net Income']; //,'Basic EPS'
	$vars_required=['Total Revenue','Net Income']; //,'Basic EPS'
	$results=array();
	foreach($vars2get as $var2get){
		if($debug) echo "<br />getting results for: $var2get<br />";
		preg_match("/^".$var2get."(.*)$/m", $response, $xxxx);
		preg_match_all("/title='([^']*)'/", $xxxx[1], $xxxx_arr);
		if(count($period_arr[1])!=count($xxxx_arr[1])){
			echo "<br />count periods and count data is not the same...<br /><br />";
			send_mail('ERROR financial '.$name,"$url_and_query<br />count periods and count data is not the same...<br /><br />","hectorlm1983@gmail.com");
			exit(1);
		}
		$results[$var2get]=$xxxx_arr[1];
	}
	if($debug) var_dump($results);
	

	$query_arr=explode(":",$symbol);
	$name=$query_arr[1];
	$market=$query_arr[0];

	// CHECK IF FIRST TIME (NEW SYMBOL) OTHERWISE LOAD PAST
	$first_time_financials=false;
	if(!array_key_exists($symbol,$stock_financials_old)){
		echo "first time getting financials for ".$symbol;
		$first_time_financials=true;
		send_mail('First time financials '.$name,"$url_and_query<br />Yay, first time<br /><br />","hectorlm1983@gmail.com");
		$stock_financial['name']=$name;
		$stock_financial['market']=$market;
	}else{
		$stock_financial=$stock_financials_old[$symbol];
		$stock_financial_full=$stocks_financialsr_full[$symbol];
	}
	
	// MAIN LOOP
	$new_period_report="";
	$change_past_report="";
	for ($period=0;$period<count($period_arr[1]);$period++){
		echo "<br />period=".$period_arr[1][$period]."<br />";
		$new_period=true;
		$new_quarter=false;
		$matching_period="";
		foreach(array_keys($stock_financial) as $stock_financial_period){
			if($period_arr[1][$period]==$stock_financial_period){
				$new_period=false;
				break;
			}
			if(substr($period_arr[1][$period],0,4)==substr($stock_financial_period,0,4)){
				// the period is not the same but the year is so
				// necessarily the difference is in the month since the day is normalized to 31
				echo "new quarter <br />";
				$matching_period=$stock_financial_period; // the one to unset
				$new_quarter=true; 
				break;
			}
		}
		if($first_time_financials || $new_period){
			echo " new period <br />";
			$stock_financial[$period_arr[1][$period]]=array();
			$stock_financial_full[$period_arr[1][$period]]=array();
		}

		$empty_vars=false;
		foreach($vars2get as $var2get){
			$results[$var2get][$period]=str_replace(",","",$results[$var2get][$period]);
			// TWD or other companies (remove?)
			if($symbol=="NYSE:TSM"){
				echo "<br />converting to twd<br />";
				$results[$var2get][$period]=floatval($results[$var2get][$period])*0.03;
			}
			
			if($results[$var2get][$period]=="-" || $results[$var2get][$period]==""){
				if(!array_key_exists($var2get,$stock_financial[$period_arr[1][$period]])){
					echo "<br />".$period_arr[1][$period]." var:".$var2get." empty (".$results[$var2get][$period].") ignoring...<br />";
					$latelog = fopen("late.log", "a") or die("Unable to open/create late.log!");
					fwrite($latelog, date('Y-m-d H:i:s')."  stock_curl_financial.php. Error financials ".$name.". ".$period_arr[1][$period]." var:".$var2get." empty (".$results[$var2get][$period].") ignoring<br />\n");
					fclose($latelog);
				}
				echo "empty vars <br />";
				$empty_vars=true;
				break;
			}
		}
		if(!$empty_vars){
			if($debug) echo "updating vars: $var2get<br />";
			foreach($vars2get as $var2get){
			
				// check past
				if(array_key_exists($var2get,$stock_financial_full[$period_arr[1][$period]])){
					if($stock_financial_full[$period_arr[1][$period]][$var2get]!=$results[$var2get][$period]){
						if(abs(floatval($stock_financial_full[$period_arr[1][$period]][$var2get])-floatval($results[$var2get][$period]))>abs(floatval($results[$var2get][$period])/10)){
							$diff=toFixed(((floatval($results[$var2get][$period])-floatval($stock_financial_full[$period_arr[1][$period]][$var2get]))/max(abs(floatval($results[$var2get][$period])),0.1))*100,2,"financial diff");
							echo "ERROR changing the past for ".$period_arr[1][$period]." $var2get significantly ($diff%) !!! (keeping new value, email sent)<br />";
							$change_past_report.="<br />".$period_arr[1][$period]." var:".$var2get."  old:".$stock_financial_full[$period_arr[1][$period]][$var2get]." != new:".$results[$var2get][$period]." ($diff% greater than 10% diff). Keeping new.<br />";
						}
					}
				}
				if($new_quarter){
					unset($stock_financial[$matching_period]); // reset to the new quarter
					$stock_financial[$period_arr[1][$period]]=array();
				}
				if(!array_key_exists($var2get,$stock_financial[$period_arr[1][$period]])){
					echo "<br />New period ".$period_arr[1][$period]." var:".$var2get.":".$results[$var2get][$period]."<br />";
					$new_period_report.="<br />".$period_arr[1][$period]." var:".$var2get.":".$results[$var2get][$period]."<br />";
					$stock_financial[$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
					$stock_financial_full[$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
				}else if($stock_financial[$period_arr[1][$period]][$var2get]!=$results[$var2get][$period]){
					$stock_financial[$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
					$stock_financial_full[$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
				}
			}
		}
		// if created but empty vars then... unset
		if(count($stock_financial[$period_arr[1][$period]])==0){
			unset($stock_financial[$period_arr[1][$period]]); 
			unset($stocks_financialsr_full[$period_arr[1][$period]]); 
		}
	}
	if($new_period_report!="" && !$first_time_financials){
		echo 'new financials report '.$name,"$url_and_query<br />In ".$symbol." ".$new_period_report."<br /><br />";
		//send_mail('new financials '.$name,"$url_and_query<br />In ".$symbol." ".$new_period_report."<br /><br />","hectorlm1983@gmail.com");
	}
	if($change_past_report!=""){
		send_mail('financials change past '.$name,"$url_and_query<br />In ".$symbol." ".$change_past_report."<br /><br />","hectorlm1983@gmail.com");
	}


	// write financials full
	$stocks_financialsr_full[$symbol]=$stock_financial_full;
	$str=json_encode( $stocks_financialsr_full );
	$stocks_financials_json_file = fopen("stocks_financialsr_full.json", "w") or die("Unable to open file stocks_financialsr_full.json!");
	fwrite($stocks_financials_json_file, $str);
	fclose($stocks_financials_json_file);
	
	
    ksort($stock_financial);
    return $stock_financial;
}

if( isset($_REQUEST['symbol']) ){
    $debug=false;
    if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
        $debug=true;
    }
    echo "symbol found, manual mode<br /><br />";
    $stock_financial=get_financial($_REQUEST['symbol'],$debug);
    echo "Result:<br />---<pre>".json_encode($stock_financial, JSON_PRETTY_PRINT)."</pre>---<br />";
}



?>