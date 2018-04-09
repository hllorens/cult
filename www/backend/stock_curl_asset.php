<?php

require_once("email_config.php");
require_once("stock_list.php");
require_once 'stock_helper_functions.php';


function get_asset($symbol,$debug=false){
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
    $response2 = curl_exec( $curl ); //utf8_decode( not necessary
    curl_close( $curl );
    $response2=preg_replace("/(\n|&nbsp;)/", " ", $response2);
    //if($debug) echo "base .<pre>".htmlspecialchars($response2)."</pre>";
    $response2=preg_replace("/<title>/", "\ntd <title>", $response2);
    $response2=preg_replace("/<\/title>/", "\n", $response2);
    $response2=preg_replace("/<tr/", "\ntr", $response2);
    //if($debug)  echo "aaa.<pre>".htmlspecialchars($response2)."</pre>";
    $response2=preg_replace("/<\/(tr|table|ul)>/", "\n", $response2);
    //$response2=preg_replace("/^[^t][^d].*$/m", "", $response2);
    $response2 = preg_replace('/^[ \t]*[\r\n]+/m', '', $response2); // remove blank lines
    if($debug) echo "----------end----------";
    preg_match("/^.*Period Ending(.*)$/m", $response2, $xxxx);
    preg_match_all("/<span[^>]*>([^\/]*\/[^\/]*\/[^<]*)<\/span>/", $xxxx[1], $period_arr);
    if($debug) echo "<br />";
    
    // EQUITY=TOTAL ASSETS - TOTAL LIABILITES
    $vars2get=['Total Assets','Total Liabilities']; //'Total Current Assets','Total Current Liabilities'

    $results=array();
    foreach($vars2get as $var2get){
        if($debug) echo "getting results for: $var2get<br />";
        preg_match("/".$var2get."\s*<(.*)$/m", $response2, $xxxx);
        preg_match_all("/<td[^>]*>(?:<span[^>]*>)?([^<]*)(?:<\/span>)?<\/td>/", $xxxx[1], $xxxx_arr);
        if(count($period_arr[1])!=count($xxxx_arr[1])){
            echo "<br />count periods and count data is not the same...<br /><br />";
            send_mail('ERROR assets '.$name,"<br />count periods and count data is not the same...<br /><br />","hectorlm1983@gmail.com");
            exit(1);
        }
        $results[$var2get]=$xxxx_arr[1];
    }
    
    
    $query_arr=explode(":",$symbol);
    $name=$query_arr[1];
    $market=$query_arr[0];

    $first_time_financials=false;
    if(!array_key_exists($symbol,$stock_financials_old)){
        echo "first time getting assets for ".$symbol;
        $first_time_financials=true;
        send_mail('First time assets '.$name,"<br />Yay, first time<br /><br />","hectorlm1983@gmail.com");
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
            send_mail('Error assets '.$name,"<br />"."ERROR (MSN INCOME): incorrect format period ".$period_arr[1][$period]."<br /><br />","hectorlm1983@gmail.com");
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
                    send_mail('Error asset '.$name,"<br />asset period change same year new ".$period_arr[1][$period]." vs existing ".$stock_financial_period.", keeping the one with higher month <br /><br />","hectorlm1983@gmail.com");
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
                    send_mail('Error assets '.$name,"<br />Empty - in $var2get, setting 0<br /><br />","hectorlm1983@gmail.com");
                }
                $stock_financial[$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
                $new_period_report.="<br />".$period_arr[1][$period]." var:".$var2get.":".$stock_financial[$period_arr[1][$period]][$var2get]."<br />";
            }else{
                if($results[$var2get][$period]=="-" || $results[$var2get][$period]==""){
					echo "<br />".$period_arr[1][$period]." var:".$var2get." empty (".$results[$var2get][$period].") using the existing past...$var2get=".$stock_financial[$period_arr[1][$period]][$var2get]."<br />";
                    $change_past_report.="<br />".$period_arr[1][$period]." var:".$var2get." empty (".$results[$var2get][$period].") using the existing past... $var2get=".$stock_financial[$period_arr[1][$period]][$var2get]."<br />";
                }else if($stock_financial[$period_arr[1][$period]][$var2get]!=$results[$var2get][$period]){
					if(abs(floatval($stock_financial[$period_arr[1][$period]][$var2get])-floatval($results[$var2get][$period]))>abs(floatval($results[$var2get][$period])/10)){
						echo "ERROR changing the past for ".$period_arr[1][$period]." $var2get significantly!!! (keeping new value, email sent)<br />";
						$change_past_report.="<br />".$period_arr[1][$period]." var:".$var2get."  old:".$stock_financial[$period_arr[1][$period]][$var2get]." != new:".$results[$var2get][$period]." (greater than 10% diff). Keeping new.<br />";
					}
                    $stock_financial[$period_arr[1][$period]][$var2get]=$results[$var2get][$period];
                }
            }
        }
    }
	if($new_period_report!="" && !$first_time_financials){
		send_mail('new financials '.$name,"<br />In ".$symbol." ".$new_period_report."<br /><br />","hectorlm1983@gmail.com");
	}
	if($change_past_report!=""){
		send_mail('financials change past '.$name,"<br />In ".$symbol." ".$change_past_report."<br /><br />","hectorlm1983@gmail.com");
	}
    return $stock_financial;
}

if( isset($_REQUEST['symbol']) ){
    $debug=false;
    if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
        $debug=true;
    }
    echo "symbol found, manual mode<br /><br />";
    $stock_financial=get_asset($_REQUEST['symbol'],$debug);
    var_dump($stock_financial);
}


?>