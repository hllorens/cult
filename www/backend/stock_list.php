<?php

echo date('Y-m-d H:i:s')." starting stock_list.php<br />";

// current markets: grep "^.stock_list" cult/www/backend/stock_list.php | sed "s/\";.*\$//" | sed "s/^.*stock_list,//" | tr "," "\n" | cut -f 1 -d : | sort | uniq -c
// [INDEX], BME, EPA, FRA, NASDAQ, NYSE, OTCMKTS, VTX

$stock_list="INDEXBME:IB";

$stock_list="$stock_list,BME:ACS,BME:ACX,BME:AENA,BME:AMS,BME:ANA,BME:BBVA,BME:BKIA,BME:BKT,BME:CABK,BME:DIA";
$stock_list="$stock_list,BME:ELE,BME:ENG,BME:FCC,BME:FER,BME:SGRE,BME:GAS,BME:GRF,BME:IBE,BME:IDR"; // BME:GAM -> BME:SGRE, BME:IAG (NO MUCH INFO)
$stock_list="$stock_list,BME:ITX,BME:MAP,BME:MEL,BME:MTS,BME:OHL,BME:REE,BME:REP,BME:SAB,BME:SAN,BME:SCYR";
$stock_list="$stock_list,BME:TEF,BME:TL5,BME:TRE,BME:VID,BME:R4"; 
// ojo masmovil... consider adding it grows interestingly
// IBEX quebrados o quitados: ,BME:POP
// otras interesantes españa o farmaceuticas (society is aging... pharmacy will play a role)
$stock_list="$stock_list,BME:FAE,OTCMKTS:RHHBY,NASDAQ:SUPN,NASDAQ:REGN,NASDAQ:ALXN";

$stock_list="$stock_list,INDEXSTOXX:SX5E";
$stock_list="$stock_list,INDEXNASDAQ:NDX";
$stock_list="$stock_list,INDEXSP:.INX";

$stock_list="$stock_list,NASDAQ:GOOG,NASDAQ:MSFT,NASDAQ:EBAY,NASDAQ:AMZN,NASDAQ:NVDA"; // ,NASDAQ:YHOO no longer a company but a fund (AABA), ,NASDAQ:GOOGL same as goog no need to have 2
$stock_list="$stock_list,NASDAQ:FB,NYSE:TWTR,NYSE:SNAP";
$stock_list="$stock_list,NYSE:YRD,NYSE:FIT,NYSE:BABA,NYSE:BLK,NYSE:EL"; //new
$stock_list="$stock_list,NASDAQ:NUAN,NASDAQ:CMPR,NYSE:PSX,NASDAQ:AAPL,NASDAQ:INTC,NASDAQ:AMD,NASDAQ:TEAM,NASDAQ:BKCC,NASDAQ:IRBT,NASDAQ:NTES,NYSE:ICE,NASDAQ:CME,NASDAQ:MCHP,NASDAQ:MU,NYSE:TSM,NASDAQ:AVGO,NASDAQ:BIDU";
$stock_list="$stock_list,NASDAQ:PCLN,NASDAQ:TRIP,NASDAQ:EXPE";
$stock_list="$stock_list,NYSE:ING,NYSE:MMM,NYSE:JNJ,NYSE:GE,NYSE:WMT,NYSE:IBM,NYSE:SSI,NYSE:O";
$stock_list="$stock_list,NYSE:KO,NYSE:DPS,VTX:NESN,NYSE:PEP,EPA:BN";
$stock_list="$stock_list,NYSE:VZ,NYSE:T,NASDAQ:VOD";
$stock_list="$stock_list,NYSE:XOM,NYSE:DIS";
$stock_list="$stock_list,NYSE:BP,NYSE:HSBC,OTCMKTS:ZURVY";
$stock_list="$stock_list,NYSE:SNE,OTCMKTS:NTDOY";
$stock_list="$stock_list,NASDAQ:NFLX,NYSE:TWX,NASDAQ:CMCSA,NASDAQ:FOXA"; // HBO is part of time Warner
$stock_list="$stock_list,NYSE:TM,ETR:VOW,NYSE:GM,EPA:UG,NYSE:F";
// solar power and machines -- semiconductor producers (review)
$stock_list="$stock_list,NYSE:ED,NASDAQ:SPWR,NASDAQ:TSLA";  // ,NASDAQ:SCTY acquired by TESLA 2016/2017?

// FUTURE:
// Uber is not yet in stock, IPO estimated 2017
// MagicLeap virutal reality (GOOG will buy it?)
// MCD (McDonald's), ...
// Nokia -20% Ericsson -5% in Oct-2017 because ISPs still don't want 5G
// Nokia sold mobile biz to Microsoft and Here data to Mercedes, BMW, Audi
// Nokia is in trouble now, only focusing on new network techs

// Indexes
$market_currencies['INDEXBME']="EUR";
$market_currencies['INDEXSTOXX']="EUR";
$market_currencies['INDEXNASDAQ']="USD";
$market_currencies['INDEXSP']="USD";

// Markets
$market_currencies['BME']="EUR";
$market_currencies['EPA']="EUR";
$market_currencies['FRA']="EUR";
$market_currencies['VTX']="EUR";

$market_currencies['NASDAQ']="USD";
$market_currencies['NYSE']="USD";


function get_msn_quote($quote){
    $quote_arr=explode(":",$quote);
    $prefix="fi-126.1";
    // replacements
    if($quote_arr[0]=="BME"){
        $quote_arr[0]="MCE";
        $prefix="fi-199.1";
    }
    if($quote_arr[0]=="EPA"){
        $quote_arr[0]="PAR";
        $prefix="fi-160.1";
    }
    if($quote_arr[0]=="FRA"){
        $prefix="fi-200.1";
    }
    if($quote_arr[0]=="NASDAQ" || $quote_arr[0]=="NYSE"){
        $quote_arr[0]=substr($quote_arr[0],0,3);
        $prefix="fi-126.1";
    }
    if($quote_arr[0]=="OTCMKTS"){
        $quote_arr[0]="PINX";
        $prefix="fi-125.1";
    }
    if($quote_arr[0]=="VTX"){
        $quote_arr[0]="SWX";
        $prefix="fi-185.1";
    }
    if($quote_arr[0]=="ETR"){
        $quote_arr[0]="ETR";
        $prefix="fi-213.1";
    }
    
    // substrings not needed since we need prefix anyway
    //$quote_arr[0]=substr($quote_arr[0],0,3);
    
    /* msn prefix numbers
    fi-199.1.SGRE.MCE
    fi-160.1.BN.PAR
    fi-200.1.VOW.FRA
    fi-126.1.NUAN.NAS
    fi-126.1.GM.NYS
    fi-125.1.NTDOY.PINX
    fi-185.1.NESN.SWX
    fi-213.1.VOW.ETR
    */
    return $prefix.".".$quote_arr[1].".".$quote_arr[0];
}

function get_yahoo_quote($quote){
    $quote_arr=explode(":",$quote);
    // replacements
    if($quote_arr[0]=="BME"){
        $quote_arr[0]="MC";
    }
    if($quote_arr[0]=="EPA"){
        $quote_arr[0]="PA";
    }
    if($quote_arr[0]=="FRA"){
        $quote_arr[0]="F";
    }
    if($quote_arr[0]=="NASDAQ" || $quote_arr[0]=="NYSE" || $quote_arr[0]=="OTCMKTS"){
        return $quote_arr[1];
    }
    if($quote_arr[0]=="VTX"){
        $quote_arr[0]="VX";
    }

    // substrings not needed since we need prefix anyway
    //$quote_arr[0]=substr($quote_arr[0],0,3);
    
    return $quote_arr[1].".".$quote_arr[0];
}

function format_millions($number){
    $number=str_replace(",","",trim($number));
    $number_last=substr($number, -1);
    if($number_last=="B"){
        $number=str_replace("B","",$number);
        $number=number_format(floatval($number)*1000.00, 2, ".", "");
    }else if($number_last=="M"){
        $number=str_replace("M","",$number);
    }else{
        $number=number_format(floatval($number)/1000000.00, 2, ".", "");
    }
    return $number;
}

function format_billions($number){
    $number=str_replace(",","",trim($number));
    $number_last=substr($number, -1);
    if($number_last=="B"){
        $number=str_replace("B","",$number);
    }else if($number_last=="M"){
        $number=str_replace("M","",$number);
        $number=number_format(floatval($number)/1000.00, 2, ".", "");
    }else{
        $number=number_format(floatval($number)/1000000000.00, 2, ".", "");
    }
    return $number;
}

echo date('Y-m-d H:i:s')." ending stock_list.php<br />";

?>