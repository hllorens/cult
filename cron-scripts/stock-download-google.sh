#!/bin/bash

SCRIPT_PATH=$(dirname "$(readlink -e "${BASH_SOURCE[0]}")")
destination="$SCRIPT_PATH/../../cult-data-stock-google";

if [ ! -d $destination ];then echo "ERROR $destination does not exist"; exit -1; fi
echo "$SCRIPT_PATH and $destination"

rm -rf $destination/*.log $destination/dividend_yield.json $destination/stocks.json


timestamp=`date +'%Y-%m-%d_%H-%M-%S'`
current_year=`date +'%Y'`
current_date=`date +'%Y-%m-%d'`

stock_query="INDEXBME:IB";
stock_query="$stock_query,BME:ACS,BME:ACX,BME:AENA,BME:AMS,BME:ANA,BME:BBVA,BME:BKIA,BME:BKT,BME:CBK,BME:DIA";
stock_query="$stock_query,BME:ELE,BME:ENAG,BME:FCC,BME:FER,BME:GAM,BME:GAS,BME:GRLS,BME:IAG,BME:IBE,BME:IDR";
stock_query="$stock_query,BME:ITX,BME:MAP,BME:MTS,BME:OHL,BME:POP,BME:REE,BME:REP,BME:SABE,BME:SAN,BME:SCYR";
stock_query="$stock_query,BME:TEF,BME:TL5,BME:TRE";
stock_query="$stock_query,INDEXSTOXX:SX5E";
stock_query="$stock_query,INDEXNASDAQ:NDX";
stock_query="$stock_query,INDEXSP:.INX";
stock_query="$stock_query,NASDAQ:GOOG,NASDAQ:GOOGL,NASDAQ:MSFT,NASDAQ:YHOO,NASDAQ:EBAY,NASDAQ:FB,NASDAQ:TRIP,NASDAQ:AMZN";
stock_query="$stock_query,NASDAQ:NUAN,NASDAQ:CMPR,NASDAQ:PCLN,NASDAQ:TSLA,NYSE:PSX,NASDAQ:AAPL,NASDAQ:FOXA,NASDAQ:BKCC";
stock_query="$stock_query,NYSE:ING,NYSE:MMM,NYSE:JNJ,NYSE:KO,NYSE:GE,NYSE:WMT,NYSE:IBM,NYSE:VZ,NYSE:GM,NYSE:SSI";
stock_query="$stock_query,NYSE:TM,FRA:VOW";


sendemail="false"
echo "$timestamp Downloading to $destination (timestamp=${timestamp})" | tee $destination/ERROR.log

# GETTING THE YELD
vals=","
for i in $(echo ${stock_query} | sed "s/,/\n/g");do
    echo "Getting div/yield for $i" | tee -a $destination/ERROR.log; 
    theinfo=`echo "https://www.google.com/finance?q=$i" | wget -O- -i- | tr "\n" " " |  sed "s/<td/\ntd/g" | sed "s/<\/td>/\n/g" | sed "s/<\/table>/\n/g" | grep "^td " | sed "s/&nbsp;//g"`
    yieldval=`echo "$theinfo"  | grep -A 1 dividend_yield | grep '="val"' | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/" | sed "s/^[^\/]*\/\([^[:blank:]]*\)[[:blank:]]*/\1/"`
    divval=`echo "$theinfo"  | grep -A 1 dividend_yield | grep '="val"' | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/" | sed "s/^\([^\/]*\)\/.*\$/\1/"`
    perval=`echo "$theinfo"  | grep -A 1 pe_ratio | grep '="val"' | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/"`
    betaval=`echo "$theinfo"  | grep -A 1 "\"beta\"" | grep '="val"' | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/"`
    epsval=`echo "$theinfo"  | grep -A 1 "\"eps\"" | tail -n 1 | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/"`
    roeval=`echo "$theinfo"  | grep -A 1 "Return on average equity" | grep '="val"' | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/"`
    range_52week=`echo "$theinfo"  | grep -A 1 range_52week | grep '="val"' | sed "s/^[^>]*>\([^[:blank:]]*\)[[:blank:]]*/\1/" | sed "s/,//g"`
    vals="${vals},\"$i\": {\"yield\": \"$yieldval\",\"dividend\": \"$divval\",\"eps\": \"$epsval\",\"beta\": \"$betaval\",\"per\": \"$perval\",\"roe\": \"$roeval\",\"range_52week\": \"$range_52week\" }"
    sleep 1; # to avoid overloading google
done
echo "{ ${vals} }" | sed "s/,,//g" > $destination/dividend_yield.new.json
if [ `cat "$destination/dividend_yield.new.json" | json_pp -f json  > /dev/null;echo $?` -ne 0 -o `cat $destination/dividend_yield.new.json | wc -c` -le 2000 ];then
    echo "ERROR: Dividend/yield info is not valid json or too small... < 2000 chars " >> $destination/ERROR.log;
    echo "START $destination/dividend_yield.new.json" >> $destination/ERROR.log;
    cat "$destination/dividend_yield.new.json" >> $destination/ERROR.log;
    echo "END $destination/dividend_yield.new.json" >> $destination/ERROR.log;
    cp $destination/ERROR.log ${destination}-errors/ERROR-$timestamp.log
    cat $destination/ERROR.log | mail -s "ERROR in stock download" hectorlm1983@gmail.com
    exit 1;
else
    mv $destination/dividend_yield.new.json $destination/dividend_yield.json
fi

echo 'Getting div/yield finished SUCCESS' | tee -a $destination/ERROR.log;

# GETTING STOCK INFO
echo "  wget -O $destination/stocks.json \"http://www.google.com/finance/info?q=${stock_query}\"" | tee -a $destination/ERROR.log;
wget -O $destination/stocks.json "http://www.google.com/finance/info?q=${stock_query}" 2> /dev/null
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

echo 'wget --timeout=180 -q -O $destination/stocks.formated.json "http://www.cognitionis.com/cult/www/backend/format_data_for_stock_alerts.php"' | tee -a $destination/ERROR.log;

# use new here ... keep the log somewhere of the wget...

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
fi


#process alerts...
#wget --timeout=180 -q -O $destination/stock.formated.json "http://www.cognitionis.com/cult/www/backend/format_data_for_stock_alerts.php" > $SCRIPT_PATH/data-generation-stock.log;

if [ "$sendemail" == "true" ];then 
	echo "sending email errors!" | tee -a $destination/ERROR.log; 
	wget --timeout=180 -q -O $destination/data-download.log http://www.cognitionis.com/cult/www/backend/send-data-download-errors.php?autosecret=1secret > $destination/last-download-data-errors.log; 
fi
echo "sending email alerts if any!" | tee -a $destination/ERROR.log; 
wget --timeout=180 -q -O $destination/stock-alerts.log http://www.cognitionis.com/cult/www/backend/send-stock-alerts-fire.php?autosecret=1secret&gendate=$current_date > $destination/last-stock-alerts-errors.log; 

cp $destination/ERROR.log ${destination}/SUCCESS-$timestamp.log

