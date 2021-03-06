<?php

// alternative version to be run with less frequency for less important stocks or coins
// the aim of this php is to get stocks.formatted.json,
//     if a file like that exists it will be updated incrementally 
//     otherwise it will be created from the scratch

require_once 'stock_list.php';
require_once("email_config.php");
require_once 'stock_helper_functions.php'; // e.g., hist(param_id,freq)

date_default_timezone_set('Europe/Madrid');
$timestamp_date=date("Y-m-d");
$timestamp_simplif=date("d H:i");
$timestamp_quarter=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 3) % 4) + 1 );
$timestamp_half=substr($timestamp_date,0,4)."-".((ceil(DateTime::createFromFormat('Y-m-d', $timestamp_date)->format('n') / 6) % 2) + 1 );
#echo "<br />$timestamp_date<br />";

$FIREBASE='https://cult-game.firebaseio.com/';

$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

$stocks_formatted_arr=array(); // to store stocks.formatted, typo "formatted"
if(file_exists ( 'stocks.formatted.json' )){
    echo "stocks.formatted.json exists -> reading...<br />";
    $stocks_formatted_arr = json_decode(file_get_contents('stocks.formatted.json'), true);
}else{
    echo "stocks.formatted.json does NOT exist -> using an empty array<br />";
}

echo date('Y-m-d H:i:s')." start stock_cron_leverage_book.php<br />";

// fopen with w overwrites existing file
$stock_cron_leverage_book_log = fopen("stock_cron_leverage_book.log", "w") or die("Unable to open/create stock_cron_leverage_book.log!");
fwrite($stock_cron_leverage_book_log, date('Y-m-d H:i:s')." starting stock_cron_leverage_book.php\n");

fwrite($stock_cron_leverage_book_log, date('Y-m-d H:i:s')." starting stock_list.php\n");




$debug=false;
if( isset($_REQUEST['debug']) && ($_REQUEST['debug']=="true" || $_REQUEST['debug']=="1")){
    $debug=true;
}

$the_url="https://www.msn.com/en-us/money/stockdetails/analysis/"; //fi-199.1.SGRE.MCE (the number depends on the country)

// IMPORTANT: Price\/Sales does NOT appear in the first page but surprisingly it can be crawled!!

//$vals=",";
$url_and_query="url_and_query not set yet";
$the_url_query_arr = explode(",", $stock_list);

$num_stocks_to_curl=3;
$stock_last_leverage_book_updated=0;
if(file_exists ( 'stock_last_leverage_book_updated.txt' )){
    $stock_last_leverage_book_updated=intval(fgets(fopen('stock_last_leverage_book_updated.txt', 'r')));
}
$num_stocks_to_curl=min($num_stocks_to_curl,count($the_url_query_arr)); // make sure we do not duplicate...


// debug
//$the_url_query_arr = array("BME:SGRE");
//$num_stocks_to_curl=1;
//$stock_last_leverage_book_updated=0;

echo " curr_stock_num_to_curl=$stock_last_leverage_book_updated num_stocks_to_curl=$num_stocks_to_curl<br />";

for ($i=0;$i<$num_stocks_to_curl;$i++){
    $current_num_to_curl=($stock_last_leverage_book_updated+$i) % count($the_url_query_arr);
    $query_arr=explode(":",$the_url_query_arr[$current_num_to_curl]);
    echo "<br />stock ".$the_url_query_arr[$current_num_to_curl]."<br />";
    $name=$query_arr[1];
    $market=$query_arr[0];
	// HANDLE EXCEPTIONS FOR BAD FIREBASE NAMES including '.' or '[]' or ...
	if($name==".INX"){$name="INX";}
    if(!array_key_exists($name.":".$market,$stocks_formatted_arr)){
        echo "Stock $name still does not have basic info: skipping... run stock_cron.php first";
        send_mail('Error '.$name,"<br />Stock $name still does not have basic info: skipping... run stock_cron.php first<br /><br />","hectorlm1983@gmail.com");
        continue;
    }
    
	$email_report="";
    if(substr($the_url_query_arr[$current_num_to_curl],0,5)=="INDEX"){
        echo "<br />INDEX ignoring... all 0<br />";
        //$stocks_formatted_arr[$name.":".$market]['revenue']=0;
		$email_report="";
        $stocks_formatted_arr[$name.":".$market]['price_to_book']=0;
        $stocks_formatted_arr[$name.":".$market]['price_to_sales']=99;
        $stocks_formatted_arr[$name.":".$market]['leverage']=99; // mrq in this case equivalent to ttm (current moment), in balance sheet
        $stocks_formatted_arr[$name.":".$market]['current_ratio']=0.01; // mrq 
        $stocks_formatted_arr[$name.":".$market]['quick_ratio']=0.01; // mrq 
        $stocks_formatted_arr[$name.":".$market]['leverage_industry']=2.5;
        $stocks_formatted_arr[$name.":".$market]['avg_revenue_growth_5y']=0;
        $stocks_formatted_arr[$name.":".$market]['revenue_growth_qq_last_year']=0;
    }else{
        $url_and_query=$the_url.get_msn_quote($the_url_query_arr[$current_num_to_curl]); //get_msn_quote($the_url_query_arr[$current_num_to_curl]);
        echo "<br />stock $url_and_query<br />";
        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_URL, $url_and_query );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        $response = curl_exec( $curl ); //utf8_decode( not necessary
        curl_close( $curl );
        $response=preg_replace("/(\n|&nbsp;)/", " ", $response);
        //if($debug) echo "base .<pre>".htmlspecialchars($response)."</pre>";
        $response=preg_replace("/<title>/", "\ntd <title>", $response);
        $response=preg_replace("/<\/title>/", "\n", $response);
        $response=preg_replace("/<td/", "\ntd", $response);
        //if($debug)  echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
        $response=preg_replace("/<\/(td|table|ul)>/", "\n", $response);
        //$response=preg_replace("/^[^t][^d].*$/m", "", $response);
        $response = preg_replace('/^[ \t]*[\r\n]+/m', '', $response); // remove blank lines
        $response = preg_replace('/\n(.*=\"val\".*)[\r\n]+/m', '${1}', $response); // remove blank lines
        $response = preg_replace('/title=\'(Revenue|Price\/Book Value|Leverage Ratio|Current Ratio|Quick Ratio|Price\/Sales)\'[^>]*>\s*/', "\n", $response);
        $response = preg_replace('/title=\'Sales \(Revenue\)\'[^5]*5-Year Annual Average[^>]*>\s*/', "\navg_revenue_growth_5y", $response);
        $response = preg_replace('/title=\'Sales \(Revenue\)\'[^Q]*Q\/Q \(Last Year\)[^>]*>\s*/', "\nrevenue_growth_qq_last_year", $response);
        if($debug) echo "aaa.<pre>".htmlspecialchars($response)."</pre>";
        echo "----------end----------";
        echo "<br />";
        $vars2get=['Price\/Book Value','Leverage Ratio','Current Ratio','Quick Ratio','Price\/Sales','avg_revenue_growth_5y','revenue_growth_qq_last_year']; //'Revenue' from financials
        $results=array();
        foreach($vars2get as $var2get){
            preg_match("/^".$var2get."(.*)$/m", $response, $xxxx);
            if(count($xxxx)<2){
                echo "<br />Empty $var2get skipping, late email sent...<br />";
				$latelog = fopen("late.log", "a") or die("Unable to open/create late.log!");
				fwrite($latelog, date('Y-m-d H:i:s')." $url_and_query<br />Empty $var2get (stock_cron_leverage_book.php step 1), skipping...<br /><br />\n");
				fclose($latelog);
                continue;
            }
            preg_match_all("/title='([^']*)'/", $xxxx[1], $xxxx_arr);
            if(count($xxxx_arr)<2){
                echo "<br />Empty $var2get skipping, email sent...<br />";
				$latelog = fopen("late.log", "a") or die("Unable to open/create late.log!");
				fwrite($latelog, date('Y-m-d H:i:s')." $url_and_query<br />Empty $var2get (stock_cron_leverage_book.php step 1), skipping...<br /><br />\n");
				fclose($latelog);
                continue;
            }
            $results[$var2get]=str_replace(",","",$xxxx_arr[1]);
        }
        
        if($debug)  var_dump($results);

        
		$email_report="";
		
        // In msn I am not sure but in yahoo it is most-recent-quarter (mrq) we should probably ignore this and only use equity (financils)
		$email_report.=handle_new_value($stocks_formatted_arr[$name.":".$market],'price_to_book',$results,'Price\/Book Value',0,$name,99,0.34);
        
		// DISABLED FOR NOW CLEAN JSON AND FIREBASE?
		// If missing could be calculated from financials and just send slow emeail
		$email_report.=handle_new_value($stocks_formatted_arr[$name.":".$market],'price_to_sales',$results,'Price\/Sales',0,$name,99,0.34);
        
		// If missing could be calculated from financials
		$email_report.=handle_new_value($stocks_formatted_arr[$name.":".$market],'avg_revenue_growth_5y',$results,'avg_revenue_growth_5y',0,$name,0,0.34);
        
		// this is the only truly important one
		$email_report.=handle_new_value($stocks_formatted_arr[$name.":".$market],'revenue_growth_qq_last_year',$results,'revenue_growth_qq_last_year',0,$name,0,0.34);

		// probably calculate that from financials (if the val is empty, no need to send email but just slow log)
        $email_report.=handle_new_value($stocks_formatted_arr[$name.":".$market],'leverage',$results,'Leverage Ratio',0,$name,99,0.34);
        $email_report.=handle_new_value($stocks_formatted_arr[$name.":".$market],'current_ratio',$results,'Current Ratio',0,$name,1,0.20);
        $email_report.=handle_new_value($stocks_formatted_arr[$name.":".$market],'quick_ratio',$results,'Quick Ratio',0,$name,1,0.20);
        if(count($results['Leverage Ratio'])>1){
            $email_report.=handle_new_value($stocks_formatted_arr[$name.":".$market],'leverage_industry',$results,'Leverage Ratio',1,$name,99,0.34);
			if($stocks_formatted_arr[$name.":".$market]['leverage_industry']==0){
				$stocks_formatted_arr[$name.":".$market]['leverage_industry']=0.01; // non-0 to avoid 0 division
			}
        }
    }
    
    if($email_report!="" && 
	      !(substr($timestamp_date,0,4)=='2018' && ($name=='REE' || $name=='AMS' || $name=='ACX')) // Excepted 2018
		  ){ 
        send_mail("lev-book ".$name,$email_report,"hectorlm1983@gmail.com");
    }
    //hist_min('revenue',6,$stocks_formatted_arr[$name.":".$market]); // in msn this is last year, the ttm maybe use yahoo or do it manually for companies you care about
    hist_year_last_day('leverage',$stocks_formatted_arr[$name.":".$market]);
    hist_year_last_day('quick_ratio',$stocks_formatted_arr[$name.":".$market]);
    hist_year_last_day('price_to_book',$stocks_formatted_arr[$name.":".$market]); // hist calculated by financials... safer
    //hist_min('price_to_sales',3,$stocks_formatted_arr[$name.":".$market]); //avg of 8 (default) 
    //hist_year_last_day('avg_revenue_growth_5y',$stocks_formatted_arr[$name.":".$market]);
    //hist_min('revenue_growth_qq_last_year',3,$stocks_formatted_arr[$name.":".$market]);
	
	$curl = curl_init();
	curl_setopt( $curl, CURLOPT_URL, $FIREBASE .'stocks_formatted/'. $name.':'.$market.'.json' ); ///'.$usr.'_'.$symbol.'
	curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "PUT" );
	curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode($stocks_formatted_arr[$name.":".$market]) );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
	$response = curl_exec( $curl );
		curl_close( $curl );

}
// -----------update stocks formatted ----------------------------------

function handle_new_value(&$orig,$orig_param,$results,$param_id,$index,$name,$default_val=0,$diff_margin=0){
	$timestamp_date=date("Y-m-d");
	$report="";
    if($results[$param_id][$index]=="-" || $results[$param_id][$index]==""){
        #exceptions stocks that don't have 5y or few data ------
        if($param_id=="avg_revenue_growth_5y"){return "";} // we use financials for this already && ($orig['name']=='SNAP' || $orig['name']=='YRD')
        if($param_id=="revenue_growth_qq_last_year" && (  $orig['name']=='KER'  )){return "";} // the only truly important
        if($param_id=="price_to_book" && (  $orig['name']=='BIDU' || $orig['name']=='ACX' )){return "";}
        if($param_id=="leverage" && (  $orig['name']=='BIDU' || $orig['name']=='ACX' )){return "";}
        #-------------------------------------------
        $results[$param_id][$index]=$default_val;
        //$report_slow="$name<br /><br />Empty - in $param_id (".$results[$param_id][$index].") (index=$index) new:[".implode(" ",$results[$param_id])."] (stock_cron_leverage_book.php), setting $default_val<br /><br />";
    }
    if(!array_key_exists($orig_param,$orig)){
        $report.="$name<br /><br />New $param_id: ".$results[$param_id][$index]."<br />(stock_cron_leverage_book.php)<br />";
        $orig[$orig_param]=$results[$param_id][$index];
		$orig[$orig_param."_date"]=$timestamp_date;
    }else{
		if(!array_key_exists($orig_param."_date",$orig)){
			$orig[$orig_param."_date"]=$timestamp_date;
		}
        if(floatval($orig[$orig_param])==$default_val){
            $orig[$orig_param]=$results[$param_id][$index];
			$orig[$orig_param."_date"]=$timestamp_date;
            $report="";
        }else{
            if(abs(floatval($orig[$orig_param])-floatval($results[$param_id][$index]))/max(abs(floatval($results[$param_id][$index])),0.1) >$diff_margin){
                $diff=toFixed(abs(floatval($orig[$orig_param])-floatval($results[$param_id][$index]))/max(abs(floatval($results[$param_id][$index])),0.1),2,"lev-book diff");
                if(floatval($results[$param_id][$index])==$default_val){
					// only report if the orig is too old (2 years)
					if(floatval(substr($timestamp_date,0,4))>(floatval(substr($orig[$orig_param."_date"],0,4))+1)){
						$report.="$name<br />$param_id<br />Orig (".$orig[$orig_param."_date"]."): ".$orig[$orig_param].'<br />New: '.$results[$param_id][$index]." (default, empty)<br />Keeping original since new is the default (empty)<br />New $param_id (orig: $orig_param)<br />diff=$diff, diff_margin=$diff_margin<br />(stock_cron_leverage_book.php)<br />";
					}
                }else{
					// only email with greater margin, otherwise just update
					if(abs(floatval($orig[$orig_param])-floatval($results[$param_id][$index]))/max(abs(floatval($results[$param_id][$index])),0.1) >($diff_margin+0.3)){
						$report.="$name<br />$param_id<br />Orig: ".$orig[$orig_param].'<br />New: '.$results[$param_id][$index]."<br />Keeping the new<br />New $param_id (orig: $orig_param)<br />diff=$diff, diff_margin=$diff_margin<br />(stock_cron_leverage_book.php)<br />";
					}
					$orig[$orig_param]=$results[$param_id][$index];
                }
            }
			// even if no diff if no default update the date (last date a non-default value was obtained)
			if(floatval($results[$param_id][$index])!=$default_val){
				$orig[$orig_param."_date"]=$timestamp_date;
			}
        }
    }
	return $report;
}


// update last updated number
$stock_last_leverage_book_updated=($stock_last_leverage_book_updated+$num_stocks_to_curl) % count($the_url_query_arr); // modulo to avoid big nums...
$stock_last_leverage_book_updated_f = fopen("stock_last_leverage_book_updated.txt", "w") or die("Unable to open file!");
fwrite($stock_last_leverage_book_updated_f, $stock_last_leverage_book_updated);
fclose($stock_last_leverage_book_updated_f);

// update stocks.formatted.json
echo date('Y-m-d H:i:s')." updating stocks.formatted.json\n";
fwrite($stock_cron_leverage_book_log, date('Y-m-d H:i:s')." updating stocks.formatted.json\n");
$stocks_formatted_arr_json_str=json_encode( $stocks_formatted_arr );
$stocks_formatted_json_file = fopen("stocks.formatted.json", "w") or die("Unable to open file stocks.formatted.json!");
fwrite($stocks_formatted_json_file, $stocks_formatted_arr_json_str);
fclose($stocks_formatted_json_file);


/*$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $FIREBASE .'stocks_formatted.json' ); ///'.$usr.'_'.$symbol.'
curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "PUT" );
curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode($stocks_formatted_arr) );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl );
curl_close( $curl );*/



fwrite($stock_cron_leverage_book_log, date('Y-m-d H:i:s')." done with stock_cron_leverage_book.php\n");
echo "<br />".date('Y-m-d H:i:s')." done with stock_cron_leverage_book.php, see stock_cron_leverage_book.log<br />";
fclose($stock_cron_leverage_book_log);

?>
