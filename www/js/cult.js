
// DATA vars, TODO all data will be already in the same format...
var data_source="wb";
var indicator="population";
var data_not_loaded_yet=9999;
var json_data_files;
var data_map={};
var indicator_list=[];
var country_list=[];
var period_list=[];
var period_map={};
/* encoded in the data file
var period_correspondence={
	previous_year:-1,
	last_lustrum:-4,
	last_decade:-9,
	last_2decade:-19
};*/

// MEDIA
var images = [];
var sounds = [];

var header_zone=document.getElementById('header');
var canvas_zone=document.getElementById('zone_canvas');

// LOAD

//date=1960:2014 
// Pop density per km2			EN.POP.DNST
// Pop growth %					SP.POP.GROW
// Total Surface km2			AG.SRF.TOTL.K2 (does not vary)
// Life expectancy				SP.DYN.LE00.IN
// All in USD:
// GDP                          NY.GDP.MKTP.CD
// GDP per capita               NY.GDP.PCAP.CD
// GDP growth                   NY.GDP.MKTP.KD.ZG
// Cash surplus/deficit % GDP   GC.BAL.CASH.GD.ZS
// Gov debt % GDP				GC.DOD.TOTL.GD.ZS
// External debt total			DT.DOD.DECT.CD
// Inflation, CPI %				FP.CPI.TOTL.ZG
// Reserves (inc gold)			FI.RES.TOTL.CD
// Labor Force (employable %)	SL.TLF.CACT.ZS
// 15-64 (employable %)			SP.POP.1564.TO.ZS
// employed to 15+ pop			SL.EMP.TOTL.SP.ZS
// +65                         	SP.POP.65UP.TO.ZS

	// global dashboard variables 
	var dashboard;
	var data;
	// at least we will have a table
	var table;
	// Dynamic elements and controls depending on the number of columns in the SPARQL results
	var elements=[];
	var controls=[];


function menu_screen(){
	allowBackExit();
	media_objects=ResourceLoader.ret_media; // in theory all are loaded at this point
	var splash=document.getElementById("splash_screen");
	if(splash!=null && (ResourceLoader.lazy_audio==true || ResourceLoader.not_loaded['sounds'].length==0)){ splash.parentNode.removeChild(splash); }
	header_zone.innerHTML='<h1>CULT</h1>';
	if(debug){
		console.log('userAgent: '+navigator.userAgent+' is_app: '+is_app+' Device info: '+device_info);
		console.log('not_loaded sounds: '+ResourceLoader.not_loaded['sounds'].length);
	}

	//'+JSON.stringify(es_population)+'\
	
	canvas_zone.innerHTML=' \
	<div id="menu-content" class="text-center">\
	<div id="menu-logo-div"></div> \
	<nav id="responsive_menu">\
	<br /><button id="exit" class="button exit" onclick="exit_app()">Salir</button> \
	</nav>\
	</div>\
	';
	
	ajax_request_json("backend/search_game_data_files.php?data_source="+data_source,function(json_filenames){
		//console.log(json)
		json_data_files=json_filenames;
		data_not_loaded_yet=json_filenames.length;
		for(var i=0;i<json_filenames.length;i++){
			console.log('requesting '+json_filenames[i]);
			ajax_request_json(json_filenames[i],function(json){
				data_map[json.indicator]=json; 
				indicator_list.push(json.indicator);
				console.log(json.indicator);
				data_not_loaded_yet--;
				if(data_not_loaded_yet==0){continue_app();}
			});
		}
	});



}

var continue_app=function(){
	// load countries and periods (only once)
	load_country_list_from_indicator('population');
	load_period_list_from_indicator_ignore_last_year('population');
	console.log("let's go, there are "+json_data_files.length+" indicator files. Countries in 'population' = "+country_list.length);
	same_country_question(indicator);
}

var same_country_question=function(indicator){
	var country=random_item(country_list);
	var period=random_item(period_list);
	//data_map[indicator].data.last_year.hasOwnProperty(country)
	canvas_zone.innerHTML=' \
	<div id="menu-content" class="text-center">\
	When was '+country+' bigger in '+indicator+'?\
	<div id="answer-'+period_map[period]+'" class="answer aleft">'+period_map[period]+'</div>\
	<div id="answer-'+period_map['last_year']+'" class="answer aright">'+period_map['last_year']+'</div>\
	</div>\
	';	
}

var diff_country_question=function(indicator){
	var country1=random_item(country_list);
	var country2=random_item(country_list,country1);
	canvas_zone.innerHTML=' \
	<div id="menu-content" class="text-center">\
	Which is bigger in '+indicator+'?\
	<div>'+country1+'</div>\
	<div>'+country2+'</div>\
	</div>\
	';
}


var load_country_list_from_indicator=function(indicator){
	for (country in data_map[indicator].data.last_year) {
		country_list.push(country);
	}
}

var load_period_list_from_indicator_ignore_last_year=function(indicator){
	period_map['last_year']=data_map[indicator].last_year;
	for (period in data_map[indicator].data) {
		if(period!='last_year')	period_list.push(period);
		period_map[period]= data_map[indicator][period];
		//last_year+period_correspondence[period];
	}
}

