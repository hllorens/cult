#!/bin/bash

SCRIPT_PATH=$(dirname "$(readlink -e "${BASH_SOURCE[0]}")")
destination="$SCRIPT_PATH/../../cult-data-stock-google";

if [ ! -d $destination ];then echo "ERROR $destination does not exist"; exit -1; fi
echo "$SCRIPT_PATH and $destination"

rm -rf $destination/*

timestamp=`date +'%Y-%m-%d_%H-%M-%S'`
current_year=`date +'%Y'`
stock_query="INDEXBME:IB";
stock_query="$stock_query,BME:ACS,BME:ACX,BME:AENA,BME:AMS,BME:ANA,BME:BBVA,BME:BKIA,BME:BKT,BME:CBK,BME:DIA";
stock_query="$stock_query,BME:ELE,BME:ENAG,BME:FCC,BME:FER,BME:GAM,BME:GAS,BME:GRLS,BME:IAG,BME:IBE,BME:IDR";
stock_query="$stock_query,BME:ITX,BME:MAP,BME:MTS,BME:OHL,BME:POP,BME:REE,BME:REP,BME:SABE,BME:SAN,BME:SCYR";
stock_query="$stock_query,BME:TEF,BME:TL5,BME:TRE";
stock_query="$stock_query,INDEXSTOXX:SX5E";
stock_query="$stock_query,INDEXNASDAQ:NDX";
stock_query="$stock_query,NASDAQ:GOOG,NASDAQ:MSFT,NASDAQ:YHOO,NASDAQ:EBAY,NASDAQ:FB,NASDAQ:TRIP,NASDAQ:AMZN";
stock_query="$stock_query,NASDAQ:NUAN,NASDAQ:CMPR,NASDAQ:PCLN";
stock_query="$stock_query,NYSE:ING";


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



