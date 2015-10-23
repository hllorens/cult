#!/bin/bash

SCRIPT_PATH=$(dirname "$(readlink -e "${BASH_SOURCE[0]}")")
destination="$SCRIPT_PATH/../../cult-data";

if [ ! -d $destination ];then echo "ERROR $destination does not exist"; exit -1; fi
echo "$SCRIPT_PATH and $destination"

timestamp=`date +'%Y-%m-%d_%H-%M-%S'`
current_year=`date +'%Y'`
last_year=$((current_year - 1))
countries=( wld us eu gb fr de it jp ca br ru id cn zaf aus kr sau ar mx tur idn es pt gr be nld dk fi swe nor pl che afg pak egy );
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
INDICATORMAP[pop65]=SP.POP.65UP.TO.ZS

sendemail="false"

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
				if [[ `echo $difference | grep "\"${last_year}\"" | wc -w` -gt 0 && `echo $difference | grep -o "\"date\"" | wc -l` -eq 1 ]];then
					echo "\nWARNING: $K $c    new data for $last_year (updating the file). $difference\n<br />\n" | tee -a $destination/ERROR.log;
					mv $destination/${c}_${K}_wb_new.json $destination/${c}_${K}_wb.json
					rm -rf $destination/${c}_${K}_wb.json.sort $destination/${c}_${K}_wb_new.json $destination/${c}_${K}_wb_new.json.sort
				else
					echo -e "\nERROR: $K $c different! ($destination/${c}_${K}_wb_new.json)\n$difference \n<br />\n" | tee -a $destination/ERROR.log;
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
	wget --timeout=180 -q -O $destination-game/${K}_wb.json http://www.cognitionis.com/cult/www/backend/format_data_for_the_game.php?indicator=$K > /home/hector/cron-scripts/data-generation.log;

done

if [ "$sendemail" == "true" ];then 
	echo "sending email errors!"
	wget --timeout=180 -q -O /home/hector/cron-scripts/data-download.log http://www.cognitionis.com/cult/www/backend/send-data-download-errors.php?autosecret=1secret > /home/hector/cron-scripts/last-download-data-errors.log; 
fi

#wget -O proveta.json http://api.worldbank.org/countries/es/indicators/SP.POP.TOTL?format=json&per_page=500


