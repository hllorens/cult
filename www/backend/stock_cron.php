<?php

echo "start stock_cron.php<br />";

// fopen with w overwrites existing file
$stock_cron_log = fopen("stock_cron.log", "w") or die("Unable to open/create stock_cron.log!");
fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_cron.php\n");

fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_list.php\n");
require_once 'stock_list.php';

fwrite($stock_cron_log, date('Y-m-d H:i:s')." starting stock_curl_all_basic.php\n");
require_once 'stock_curl_all_basic.php';

require_once 'stock_curl_usdeur.php';
// handle adding eurval to nasdaq & nyse
// handling adding details
// handling epshist...


fwrite($stock_cron_log, date('Y-m-d H:i:s')." done with stock_cron.php\n");
echo "done with stock_cron.php, see stock_cron.log<br />";
fclose($stock_cron_log);


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