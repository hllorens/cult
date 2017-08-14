<?php

$the_url='http://www.google.com/finance/info?q=';


$the_url_query="INDEXBME:IB";
$the_url_query="$the_url_query,BME:ACS,BME:ACX,BME:AENA,BME:AMS,BME:ANA,BME:BBVA,BME:BKIA,BME:BKT,BME:CBK,BME:DIA";
$the_url_query="$the_url_query,BME:ELE,BME:ENAG,BME:FCC,BME:FER,BME:SGRE,BME:GAS,BME:GRLS,BME:IAG,BME:IBE,BME:IDR"; # BME:GAM -> BME:SGRE
$the_url_query="$the_url_query,BME:ITX,BME:MAP,BME:MTS,BME:OHL,BME:REE,BME:REP,BME:SABE,BME:SAN,BME:SCYR";
# IBEX quebrados o quitados: ,BME:POP
$the_url_query="$the_url_query,BME:TEF,BME:TL5,BME:TRE";
$the_url_query="$the_url_query,INDEXSTOXX:SX5E";
$the_url_query="$the_url_query,INDEXNASDAQ:NDX";
$the_url_query="$the_url_query,INDEXSP:.INX";
$the_url_query="$the_url_query,NASDAQ:GOOG,NASDAQ:GOOGL,NASDAQ:MSFT,NASDAQ:EBAY,NASDAQ:AMZN"; # ,NASDAQ:YHOO no longer a company but a fund (AABA)
$the_url_query="$the_url_query,NASDAQ:FB,NYSE:TWTR,NYSE:SNAP";
$the_url_query="$the_url_query,NASDAQ:NUAN,NASDAQ:CMPR,NYSE:PSX,NASDAQ:AAPL,NASDAQ:INTC,NASDAQ:BKCC";
$the_url_query="$the_url_query,NASDAQ:PCLN,NASDAQ:TRIP,NASDAQ:EXPE";
$the_url_query="$the_url_query,NYSE:ING,NYSE:MMM,NYSE:JNJ,NYSE:GE,NYSE:WMT,NYSE:IBM,NYSE:SSI";
$the_url_query="$the_url_query,NYSE:KO,NYSE:DPS,VTX:NESN,NYSE:PEP,EPA:BN";
$the_url_query="$the_url_query,NYSE:VZ,NYSE:T,NASDAQ:VOD";
$the_url_query="$the_url_query,NYSE:XOM,NYSE:DIS";
$the_url_query="$the_url_query,NYSE:SNE,OTCMKTS:NTDOY";
$the_url_query="$the_url_query,NASDAQ:NFLX,NYSE:TWX,NASDAQ:CMCSA,NASDAQ:FOXA"; # HBO is part of time Warner
$the_url_query="$the_url_query,NYSE:TM,FRA:VOW,NYSE:GM,EPA:UG,NYSE:F";
$the_url_query="$the_url_query,NASDAQ:SPWR,NASDAQ:TSLA";  # ,NASDAQ:SCTY acquired by TESLA 2016/2017?

# FUTURE:
# Uber is not yet in stock, IPO estimated 2017
# MagicLeap virutal reality (GOOG will buy it?)

# SEE HOW WE COULD ADD USDEUR to alert the user when dollar is expensive (close to 1...). Low pri
#https://finance.google.com/finance?q=usdeur


$url_and_query=$the_url.$the_url_query;

$curl = curl_init();
curl_setopt( $curl, CURLOPT_URL, $url_and_query );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
$response = curl_exec( $curl );
curl_close( $curl );
$response=preg_replace('/\n/', '', $response);
$response=preg_replace('/\/\/\s*/', '', $response);
$json_out=json_decode($response,true);

//echo "$url_and_query<br />$response<br /><pre>$json_out</pre><br />hola<br />";
//print_r($json_out);
//echo "----------------";
//var_dump($json_out);
//json_encode( $data_object )

$myfile = fopen("stocks.json", "w") or die("Unable to open file!");
fwrite($myfile, $response);
fclose($myfile);

echo "done";


/*

See curl... how to parse...

$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
$txt = "John Doe\n";
fwrite($myfile, $txt);
$txt = "Jane Doe\n";
fwrite($myfile, $txt);
fclose($myfile);

# GETTING STOCK INFO
echo "  wget -O $destination/stocks.json \"http://www.google.com/finance/info?q=${the_url_query}\"" | tee -a $destination/ERROR.log;
wget -O $destination/stocks.json "http://www.google.com/finance/info?q=${the_url_query}" 2> /dev/null
cat  $destination/stocks.json | tr -d "\n" | sed "s/^\/\/ //" > $destination/stocks.json2
if [ `cat "$destination/stocks.json2" | json_pp -f json  > /dev/null;echo $?` -ne 0 -o `cat $destination/stocks.json2 | wc -c` -le 2000 ];then
    echo "ERROR: stocks.json2 is not valid json or too small... < 2000 chars " | tee -a $destination/ERROR.log;
    echo "START $destination/stocks.json2" >> $destination/ERROR.log;
    cat "$destination/stocks.json2" >> $destination/ERROR.log;
    echo "END $destination/stocks.json2" >> $destination/ERROR.log;
    cp $destination/ERROR.log ${destination}-errors/ERROR-$timestamp.log
    cp $destination/stocks.json ${destination}-errors/stocks-$timestamp.json
    cat $destination/ERROR.log | mail -s "ERROR in stock download" hectorlm1983@gmail.com
    exit 1;
else
    echo " mv $destination/stocks.json2 $destination/stocks.json" | tee -a $destination/ERROR.log; 
    mv $destination/stocks.json2 $destination/stocks.json
fi

# UPDATE eps-hist
echo 'wget --timeout=180 -q -O $destination/eps-hist.json "http://www.cognitionis.com/cult/www/backend/update_eps_hist.php"' | tee -a $destination/ERROR.log;
wget --timeout=180 -q -O $destination/eps-hist.json "http://www.cognitionis.com/cult/www/backend/update_eps_hist.php" 2>&1 >> $destination/ERROR.log;
diff $destination/eps-hist.json $destination_eps_hist/eps-hist.json >> $destination/ERROR.log;
if [ $? -eq 1 ];then
    if [ `sed "s/,/\n/g" $destination/eps-hist.json | wc -l` -gt `sed "s/,/\n/g" $destination_eps_hist/eps-hist.json | wc -l` ];then 
        cp $destination/eps-hist.json $destination_eps_hist/eps-hist.json
        cp $destination/eps-hist.json  ${destination}-historical/${current_date}.eps-hist.json
    fi
fi

echo 'wget --timeout=180 -q -O $destination/stocks.formated.json2 "http://www.cognitionis.com/cult/www/backend/format_data_for_stock_alerts.php"' | tee -a $destination/ERROR.log;
wget --timeout=180 -q -O $destination/stocks.formated.json2 "http://www.cognitionis.com/cult/www/backend/format_data_for_stock_alerts.php" 2>&1 >> $destination/ERROR.log;
if [ `cat "$destination/stocks.formated.json2" | json_pp -f json  > /dev/null;echo $?` -ne 0 -o `cat $destination/stocks.formated.json2 | wc -c` -le 2000 ];then
    echo "ERROR: stocks.formated.json2 is not valid json or too small... < 2000 chars " >> $destination/ERROR.log;
    cat "$destination/stocks.formated.json2" >> $destination/ERROR.log;
    echo "END $destination/stocks.formated.json2" >> $destination/ERROR.log;
    cp $destination/ERROR.log ${destination}-errors/ERROR-$timestamp.log
    cat $destination/ERROR.log | mail -s "ERROR in stock download" hectorlm1983@gmail.com
    exit 1;
else
    echo "mv $destination/stocks.formated.json2 $destination/stocks.formated.json" | tee -a $destination/ERROR.log; 
    mv $destination/stocks.formated.json2 $destination/stocks.formated.json
    cp $destination/stocks.formated.json  ${destination}-historical/${current_date}.stocks.formated.json
    # comment out this line when you finish debug
    #cp $destination/stocks.formated.json  ${destination}-historical/${timestamp}.debug.stocks.formated.json
fi


echo "process alerts..."
if [ "$sendemail" == "true" ];then 
	echo "sending email errors!" | tee -a $destination/ERROR.log; 
	wget --timeout=180 -q -O $destination/data-download.log http://www.cognitionis.com/cult/www/backend/send-data-download-errors.php?autosecret=1secret > $destination/last-download-data-errors.log; 
fi
echo "sending email alerts if any!" | tee -a $destination/ERROR.log; 
wget --timeout=180 -q -O $destination/stock-alerts.log http://www.cognitionis.com/cult/www/backend/send-stock-alerts-fire.php?autosecret=1secret&gendate=$current_date > $destination/last-stock-alerts-errors.log; 

cp $destination/ERROR.log ${destination}/SUCCESS-$timestamp.log

*/

?>