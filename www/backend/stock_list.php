<?php

echo "starting stock_list.php<br />";
$stock_list="INDEXBME:IB";
$stock_list="$stock_list,BME:ACS,BME:ACX,BME:AENA,BME:AMS,BME:ANA,BME:BBVA,BME:BKIA,BME:BKT,BME:CBK,BME:DIA";
$stock_list="$stock_list,BME:ELE,BME:ENAG,BME:FCC,BME:FER,BME:SGRE,BME:GAS,BME:GRLS,BME:IAG,BME:IBE,BME:IDR"; # BME:GAM -> BME:SGRE
$stock_list="$stock_list,BME:ITX,BME:MAP,BME:MTS,BME:OHL,BME:REE,BME:REP,BME:SABE,BME:SAN,BME:SCYR";
$stock_list="$stock_list,BME:TEF,BME:TL5,BME:TRE";
# IBEX quebrados o quitados: ,BME:POP

$stock_list="$stock_list,INDEXSTOXX:SX5E";
$stock_list="$stock_list,INDEXNASDAQ:NDX";
$stock_list="$stock_list,INDEXSP:.INX";
$stock_list="$stock_list,NASDAQ:GOOG,NASDAQ:GOOGL,NASDAQ:MSFT,NASDAQ:EBAY,NASDAQ:AMZN"; # ,NASDAQ:YHOO no longer a company but a fund (AABA)
$stock_list="$stock_list,NASDAQ:FB,NYSE:TWTR,NYSE:SNAP";
$stock_list="$stock_list,NASDAQ:NUAN,NASDAQ:CMPR,NYSE:PSX,NASDAQ:AAPL,NASDAQ:INTC,NASDAQ:BKCC";
$stock_list="$stock_list,NASDAQ:PCLN,NASDAQ:TRIP,NASDAQ:EXPE";
$stock_list="$stock_list,NYSE:ING,NYSE:MMM,NYSE:JNJ,NYSE:GE,NYSE:WMT,NYSE:IBM,NYSE:SSI";
$stock_list="$stock_list,NYSE:KO,NYSE:DPS,VTX:NESN,NYSE:PEP,EPA:BN";
$stock_list="$stock_list,NYSE:VZ,NYSE:T,NASDAQ:VOD";
$stock_list="$stock_list,NYSE:XOM,NYSE:DIS";
$stock_list="$stock_list,NYSE:SNE,OTCMKTS:NTDOY";
$stock_list="$stock_list,NASDAQ:NFLX,NYSE:TWX,NASDAQ:CMCSA,NASDAQ:FOXA"; # HBO is part of time Warner
$stock_list="$stock_list,NYSE:TM,FRA:VOW,NYSE:GM,EPA:UG,NYSE:F";
$stock_list="$stock_list,NASDAQ:SPWR,NASDAQ:TSLA";  # ,NASDAQ:SCTY acquired by TESLA 2016/2017?

# FUTURE:
# Uber is not yet in stock, IPO estimated 2017
# MagicLeap virutal reality (GOOG will buy it?)
# MCD (McDonald's), ...
echo "ending stock_list.php<br />";

?>