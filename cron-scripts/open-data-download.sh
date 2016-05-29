#!/bin/bash

SCRIPT_PATH=$(dirname "$(readlink -e "${BASH_SOURCE[0]}")")
destination="$SCRIPT_PATH/../../cult-data";

if [ ! -d $destination ];then echo "ERROR $destination does not exist"; exit -1; fi
echo "$SCRIPT_PATH and $destination"

timestamp=`date +'%Y-%m-%d_%H-%M-%S'`
curr_date=`date +'%Y-%m-%d'`
current_year=`date +'%Y'`
last_year=$((current_year - 1))
countries=( wld us eu gb fr de it jp ca br ru ind cn zaf aus kr sau ar mx tur idn es pt gr be nld dk fi swe nor pl che afg pak egy );
# ind is india and idn is indonesia
#countries=( es );
declare -A INDICATORMAP     # Create an associative array
INDICATORMAP[population]=SP.POP.TOTL
INDICATORMAP[popdensity]=EN.POP.DNST
INDICATORMAP[popgrowth]=SP.POP.GROW

INDICATORMAP[surfacekm]=AG.SRF.TOTL.K2

INDICATORMAP[lifeexpect]=SP.DYN.LE00.IN

INDICATORMAP[gdp]=NY.GDP.MKTP.CD
INDICATORMAP[gdppcap]=NY.GDP.PCAP.CD
INDICATORMAP[gdpgrowth]=NY.GDP.MKTP.KD.ZG

INDICATORMAP[surpdeficitgdp]=GC.BAL.CASH.GD.ZS
INDICATORMAP[debtgdp]=GC.DOD.TOTL.GD.ZS
INDICATORMAP[extdebt]=DT.DOD.DECT.CD
INDICATORMAP[inflation]=FP.CPI.TOTL.ZG
INDICATORMAP[reserves]=FI.RES.TOTL.CD

INDICATORMAP[laborforce]=SL.TLF.CACT.ZS
INDICATORMAP[p15to64]=SP.POP.1564.TO.ZS
INDICATORMAP[employed]=SL.EMP.TOTL.SP.ZS
INDICATORMAP[unemployed]=SL.UEM.TOTL.ZS
INDICATORMAP[pop65]=SP.POP.65UP.TO.ZS


declare -A INDICATORSFMAP     # Create an associative array
INDICATORSFMAP[population]="population"
INDICATORSFMAP[popdensity]="population density (inhabitants per square km)"
INDICATORSFMAP[popgrowth]="population growth (%)"

INDICATORSFMAP[surfacekm]="surface (square km)"

INDICATORSFMAP[lifeexpect]="life expectancy (in years)"

INDICATORSFMAP[gdp]="GDP (total)"
INDICATORSFMAP[gdppcap]="GDP per capita"
INDICATORSFMAP[gdpgrowth]="GDP growth (%)"

INDICATORSFMAP[surpdeficitgdp]="surplus-or-deficit/GDP (%)"
INDICATORSFMAP[debtgdp]="debt/GDP (%)"
INDICATORSFMAP[extdebt]="external debt"
INDICATORSFMAP[inflation]="inflation"
INDICATORSFMAP[reserves]="gold/silver reserves"

INDICATORSFMAP[laborforce]="labor force (% of population)"
INDICATORSFMAP[p15to64]="population aged 15-64 (% of population)"
INDICATORSFMAP[employed]="employed (as % of population aged 15-64)"
INDICATORSFMAP[unemployed]="unemployed (as % of population aged 15-64)" #ILO estimate
INDICATORSFMAP[pop65]="population aged >65 (% of population)"


sendemail="false"
new_data=""

echo "$timestamp Downloading from WB to $destination (last_year=${last_year})" | tee $destination/ERROR.log

for K in "${!INDICATORMAP[@]}";do
	echo $K;
	for c in "${countries[@]}";do
        echo "  ${c}:   wget -O $destination/${c}_${K}_wb.json \"http://api.worldbank.org/countries/$c/indicators/"${INDICATORMAP[$K]}"?format=json&per_page=500\"";
		wget -O $destination/${c}_${K}_wb_new.json "http://api.worldbank.org/countries/$c/indicators/"${INDICATORMAP[$K]}"?format=json&per_page=500" 2> /dev/null
		if [ -f $destination/${c}_${K}_wb.json ];then
			#echo "           exists compare";
			cat $destination/${c}_${K}_wb.json     | sed "s/{\"indicator\"/\n{indicator/g" | sort > $destination/${c}_${K}_wb.json.sort
			cat $destination/${c}_${K}_wb_new.json | sed "s/{\"indicator\"/\n{indicator/g" | sort > $destination/${c}_${K}_wb_new.json.sort
			difference=`diff $destination/${c}_${K}_wb.json.sort $destination/${c}_${K}_wb_new.json.sort | grep -v "per_page"` 
			if [[ `echo $difference | wc -w` -gt 0 ]];then
				if [[ `echo $difference | grep "\"\(${last_year}\|${current_year}\)\"" | wc -w` -gt 0 && `echo $difference | grep -o "\"date\"" | wc -l` -eq 1 ]];then
					echo "\nINFO: $K $c    new data for $last_year (updating the file). $difference\n<br />\n" | tee -a $destination/ERROR.log;
					new_data=$new_data"\n$K $c    new data $difference<br />\n";
					mv $destination/${c}_${K}_wb_new.json $destination/${c}_${K}_wb.json
					rm -rf $destination/${c}_${K}_wb.json.sort $destination/${c}_${K}_wb_new.json $destination/${c}_${K}_wb_new.json.sort
				else
					echo -e "\nERROR: $K $c different! ($destination/${c}_${K}_wb_new.json)\n$difference \n<br />\n" | tee -a $destination/ERROR.log;
   					mv $destination/${c}_${K}_wb*.json $destination-$curr_date/
   					mv $destination/${c}_${K}_wb_new.json $destination/${c}_${K}_wb.json
					rm -rf $destination/${c}_${K}_wb.json.sort $destination/${c}_${K}_wb_new.json $destination/${c}_${K}_wb_new.json.sort
				fi
				sendemail="true"
			else
				echo "equal, cleaning"
				rm -rf $destination/${c}_${K}_wb.json.sort $destination/${c}_${K}_wb_new.json $destination/${c}_${K}_wb_new.json.sort
			fi
		else
			echo "\n $K $c     new file, downloading first time <br />\n" | tee -a $destination/ERROR.log;
			mv $destination/${c}_${K}_wb_new.json $destination/${c}_${K}_wb.json
		fi
	done
	echo "Generating data for the game!\n\n"
    rm -rf $destination-game/${K}_wb.json
	wget --timeout=180 -q -O $destination-game/${K}_wb.json "http://www.cognitionis.com/cult/www/backend/format_data_for_the_game.php?indicator=${K}&indicator_sf=${INDICATORSFMAP[$K]}" > $SCRIPT_PATH/data-generation.log;
done
rm $destination-game-unified/all_wb.json
wget --timeout=180 -q -O $destination-game-unified/all_wb.json "http://www.cognitionis.com/cult/www/backend/format_data_for_the_game.php?indicator=all" > $SCRIPT_PATH/data-generation-game.log;


#TODO ANALYSIS REPORT, sent by email on novelties and showable in the game
rm $destination-game-unified/analysis-report${current_year}.new.json
wget --timeout=180 -q -O $destination-game-unified/analysis-report${current_year}.new.json "http://www.cognitionis.com/cult/www/backend/format_data_for_the_game.php?indicator=analysis" > $SCRIPT_PATH/data-generation-game.log;

# if -e $destination-game-unified/analysis-report${current_year}.json then send email
# else diff $destination-game-unified/analysis-report${current_year}.new.json with !new and if diff then send email too
cp $destination-game-unified/analysis-report${current_year}.new.json $destination-game-unified/analysis-report${current_year}.json



if [ "$sendemail" == "true" ];then 
	echo "sending email errors!"
	wget --timeout=180 -q -O $SCRIPT_PATH/data-download.log http://www.cognitionis.com/cult/www/backend/send-data-download-errors.php?autosecret=1secret > $SCRIPT_PATH/last-download-data-errors.log; 
fi

if [ "$new_data" != "" ];then 
	echo "sending email new_data!"
	echo "$new_data" | mail -s "CULT: new data" hectorlm1983@gmail.com 
fi


#wget -O proveta.json http://api.worldbank.org/countries/es/indicators/SP.POP.TOTL?format=json&per_page=500


