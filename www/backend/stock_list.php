<?php

echo date('Y-m-d H:i:s')." starting stock_list.php<br />";

// current markets: grep "^.stock_list" cult/www/backend/stock_list.php | sed "s/\";.*\$//" | sed "s/^.*stock_list,//" | tr "," "\n" | cut -f 1 -d : | sort | uniq -c
// [INDEX], BME, EPA, FRA, NASDAQ, NYSE, OTCMKTS, VTX

$bank_insurance_companies=['SAN','BBVA','ING','BKIA','BKT','SAB','CABK','MAP','ZURVY','HSBC','R4'];

$stock_list="INDEXBME:IB";

$stock_list="$stock_list,BME:ACS,BME:ACX,BME:AENA,BME:AMS,BME:ANA,BME:BBVA,BME:BKIA,BME:BKT,BME:CABK,BME:DIA";  
// index 11
$stock_list="$stock_list,BME:ELE,BME:ENG,BME:FCC,BME:FER,BME:SGRE,BME:GRF,BME:IBE,BME:IDR"; // BME:GAM -> BME:SGRE, BME:IAG (NO MUCH INFO)
// ,BME:GAS cambiar al nuevo symbolo
// index 20
$stock_list="$stock_list,BME:ITX,BME:MAP,BME:MEL,BME:MTS,BME:OHL,BME:REE,BME:REP,BME:SAB,BME:SAN,BME:SCYR";
// index 30
$stock_list="$stock_list,BME:TEF,BME:TL5,BME:TRE,BME:VID,BME:R4"; //,BME:CLNX
// ojo masmovil... consider adding it grows interestingly
// IBEX quebrados o quitados: ,BME:POP
// otras interesantes españa o farmaceuticas (society is aging... pharmacy will play a role)

// index 36
$stock_list="$stock_list,BME:FAE,NASDAQ:SUPN,NASDAQ:REGN,NASDAQ:ALXN,NASDAQ:VRTX,NYSE:ABBV"; //OTCMKTS:RHHBY,

//index 43
$stock_list="$stock_list,INDEXSTOXX:SX5E";
$stock_list="$stock_list,INDEXNASDAQ:NDX";
$stock_list="$stock_list,INDEXSP:.INX";

//index 46
$stock_list="$stock_list,NASDAQ:GOOG,NASDAQ:MSFT,NASDAQ:EBAY,NASDAQ:AMZN,NASDAQ:NVDA"; // ,NASDAQ:YHOO no longer a company but a fund (AABA), ,NASDAQ:GOOGL same as goog no need to have 2
$stock_list="$stock_list,NASDAQ:FB,NYSE:TWTR,NYSE:SNAP";
// index 54
$stock_list="$stock_list,NYSE:MA,NYSE:V,NASDAQ:PYPL";
// index 57
$stock_list="$stock_list,NYSE:YRD,NYSE:FIT,NYSE:BABA,NYSE:BLK,NYSE:EL,NYSE:BA,NASDAQ:ATVI,NASDAQ:ADBE,NYSE:CAT"; //new, at some point we might want EPA:AIR (although Boeing is larger...)
// index 67
$stock_list="$stock_list,NASDAQ:NUAN,NASDAQ:CMPR,NYSE:PSX,NASDAQ:AAPL,NASDAQ:INTC,NASDAQ:AMD,NASDAQ:TEAM,NASDAQ:BKCC,NASDAQ:IRBT";
// index 76
$stock_list="$stock_list,NASDAQ:NTES,NYSE:ICE,NASDAQ:CME,NASDAQ:MCHP,NASDAQ:MU,NYSE:TSM,NASDAQ:AVGO,NASDAQ:BIDU,NASDAQ:WDC,NASDAQ:CAR,EPA:KER"; // KOR y LVMH not so interesting for now...
// index 87
$stock_list="$stock_list,NASDAQ:BKNG,NASDAQ:TRIP,NASDAQ:EXPE";
$stock_list="$stock_list,NYSE:ING,NYSE:MMM,NYSE:JNJ,NYSE:GE,NYSE:WMT,NYSE:IBM,NYSE:SSI,NYSE:O,NYSE:HD";
//index 98
$stock_list="$stock_list,NYSE:KO,NASDAQ:PEP,EPA:BN"; // VTX:NESN, bad data and bad stock... and vtx market, ,NYSE:DPS (now KDPS or similar merger...)
$stock_list="$stock_list,NYSE:VZ,NYSE:T,NASDAQ:VOD,NASDAQ:QCOM";
$stock_list="$stock_list,NYSE:XOM,NYSE:DIS";
$stock_list="$stock_list,NYSE:BP,NYSE:HSBC"; // ,OTCMKTS:ZURVY 
$stock_list="$stock_list,NYSE:SNE"; //,OTCMKTS:NTDOY             avoid otcmkts for now
$stock_list="$stock_list,NASDAQ:NFLX,NASDAQ:CMCSA,NASDAQ:FOXA,NASDAQ:TXN"; // HBO is part of time Warner NYSE:TWX, now bought by T
$stock_list="$stock_list,NYSE:TM,NYSE:GM,EPA:UG,NYSE:F"; // maybe add FRA:VOW, but ETR financials are not good
// solar power and machines -- semiconductor producers (review)
$stock_list="$stock_list,NYSE:ED,NYSE:EIX,NASDAQ:SPWR,NASDAQ:TSLA,NYSE:NEE,NYSE:DUK,NYSE:SO,NYSE:D,NYSE:EXC,NASDAQ:STX";  // ,NASDAQ:SCTY acquired by TESLA 2016/2017?
// lithium
$stock_list="$stock_list,NYSE:SQM,NYSE:ALB,NYSE:FMC,NASDAQ:ARTX";
// Software
$stock_list="$stock_list,EPA:UBI,NYSE:INFY";
$stock_list="$stock_list,NYSE:PFE";
$stock_list="$stock_list,NYSE:NIO";
$stock_list="$stock_list,NASDAQ:SNH"; 


// FUTURE:
// Uber, airbnb, dropbox, spotify is not yet in stock, IPO estimated 2019
// MagicLeap virutal reality (GOOG will buy it?)
// MCD (McDonald's), ...
// Nokia -20% Ericsson -5% in Oct-2017 because ISPs still don't want 5G, and QCOM and HUAWEI also competing
// Nokia sold mobile biz to Microsoft and Here data to Mercedes, BMW, Audi
// Nokia is in trouble now, only focusing on new network techs (graphene)
// Investing in graphene: IBM, Samsung, Nokia, Intel, Sony, graphene-only companies are too small/non-transparent/dangerous...

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
    $prefix="fi-126.1";  // USA: NASDAQ, NYSE
    
    // replacements
    if($quote_arr[0]=="INDEXBME"){
        return "fi-198.10.!IBEX";
    }
    if($quote_arr[0]=="INDEXSTOXX"){
        $prefix="fi-221.10";
        return $prefix.".".$quote_arr[1];
    }
    if($quote_arr[0]=="INDEXNASDAQ"){
        return "fi-29.10.NDX";
    }
    if($quote_arr[0]=="INDEXSP"){
        return "fi-33.10.!SPX";
    }
    
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

//$investing_mppings=...

function get_investing_quote($quote){
    $quote_arr=explode(":",$quote);
	// 0 market 1 name
    // replacements
    if($quote_arr[1]=="GOOG"){
        return "google-inc-c";
    }
	return "$quote";

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