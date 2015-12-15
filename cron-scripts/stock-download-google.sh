#!/bin/bash

SCRIPT_PATH=$(dirname "$(readlink -e "${BASH_SOURCE[0]}")")
destination="$SCRIPT_PATH/../../cult-data-stock-google";

if [ ! -d $destination ];then echo "ERROR $destination does not exist"; exit -1; fi
echo "$SCRIPT_PATH and $destination"

rm -rf $destination/*

timestamp=`date +'%Y-%m-%d_%H-%M-%S'`
current_year=`date +'%Y'`
stock_query="INDEXBME:IB,BME:SAN,BME:BKIA,BME:BBVA,NUAN";
sendemail="false"
echo "$timestamp Downloading to $destination (timestamp=${timestamp})" | tee $destination/ERROR.log

echo "  wget -O $destination/stocks.json \"http://www.google.com/finance/info?q=${stock_query}\"";
wget -O $destination/stocks.json "http://www.google.com/finance/info?q=${stock_query}" 2> /dev/null
cat  $destination/stocks.json | tr -d "\n" | sed "s/^\/\/ //" > $destination/stocks.json2
mv $destination/stocks.json2 $destination/stocks.json
echo 'wget --timeout=180 -q -O $destination/stocks.formated.json "http://www.cognitionis.com/cult/www/backend/format_data_for_stock_alerts.php" > $SCRIPT_PATH/data-generation-stocks.log;'
wget --timeout=180 -q -O $destination/stocks.formated.json "http://www.cognitionis.com/cult/www/backend/format_data_for_stock_alerts.php" >> $destination/ERROR.log;

#process alerts...
#wget --timeout=180 -q -O $destination/stock.formated.json "http://www.cognitionis.com/cult/www/backend/format_data_for_stock_alerts.php" > $SCRIPT_PATH/data-generation-stock.log;

if [ "$sendemail" == "true" ];then 
	echo "sending email errors!"
	wget --timeout=180 -q -O $destination/data-download.log http://www.cognitionis.com/cult/www/backend/send-data-download-errors.php?autosecret=1secret > $destination/last-download-data-errors.log; 
fi
echo "sending email alerts if any!"
wget --timeout=180 -q -O $destination/stock-alerts.log http://www.cognitionis.com/cult/www/backend/send-stock-alerts.php?autosecret=1secret > $destination/last-stock-alerts-errors.log; 



