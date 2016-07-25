
// DATA vars, TODO all data will be already in the same format...
var data_source="wb";
var indicator="population";
var data_not_loaded_yet=9999;
var data_arr=[];

// MEDIA
var images = [];
var sounds = [];
var jsons = [];

var header_zone=document.getElementById('header');
var canvas_zone=document.getElementById('zone_canvas');
var backend_url='backend/' //../backend


var internet_access=true;
function check_internet_access(){
    check_internet_access_with_img_url('http://www.cognitionis.com/cult-media/img/globe.png',set_internet_access_true,set_internet_access_false);
}
var set_internet_access_true=function(){
    internet_access=true;
    if(is_local()){session_state="offline";menu_screen();}
    else{ajax_CORS_request_json(backend_url+'ajaxdb.php?action=gen_session_state',set_session_state);}
}
var set_internet_access_false=function(){
    internet_access=false;
    session_state="offline";
    menu_screen();
}
var set_session_state=function(result) {
    if(result.hasOwnProperty('error') && result.error!=""){alert("SET SESSION STATE ERROR: "+result.error); return;}
    session_state=result.state; //console.log(session_state);
    menu_screen();
};

// LOAD


//ajax_request_json("http://api.worldbank.org/countries/es/indicators/SP.POP.TOTL?date=1960:2014&format=json&per_page=500",function(json){ ... different source? can be solved...
//date=1960:2014 optional, just set a per_page greater than the number of years computed (e.g., 500 would be from 1960 until 2460, fair enough)
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
	header_zone.innerHTML='<h1>Informationism</h1>';
	if(debug){
		console.log('userAgent: '+navigator.userAgent+' is_app: '+is_app+' Device info: '+device_info);
		console.log('not_loaded sounds: '+ResourceLoader.not_loaded['sounds'].length);
	}

	//'+JSON.stringify(es_population)+'\
	
	canvas_zone.innerHTML=' \
	<div id="menu-content" class="text-center">\
	</div>\
	<div id="dashboard" style="width:90%;text-align:center">\
		<div id="chart_div" style="width: 450px; height: 350px;margin:0 auto;"></div>\
		<div id="control1" style="width: 450px; height: 50px;;margin:0 auto;"></div>\
		<div id="selector" style="width: 450px; height: 100px;margin:10px auto;display:none">\
			<button onclick="changeChartColumns()" >Update</button> items: <select id="selitems" class="chzn-select" multiple style="width: 95%; height: 50px;margin:0 auto;" >\
				<!--<input type="button" onclick="changeChartColumns">-->		\
			</select>\
		</div>\
		<div id="table_div" style="width:450px;margin:0 auto;"></div>\
	</div>\
	<div id="spinner" class="spinner" style="display:none"> 		<img id="img-spinner" src="images/spinner.gif" alt="Loading"/></div>\
	<div id="output"></div>\
	';
    //http://www.lucentia.es/productos/world-bank-visualization/
	
	/*var sorted=sortArray(es_population);
	$('#output').append("processed: <pre>"+JSON.stringify(sorted)+"</pre>");
	var googled=SparqlJSON2GoogleJSON(sorted);
	$('#output').append("processed: <pre>"+JSON.stringify(googled)+"</pre>");*/
	
	if(QueryString.hasOwnProperty('data_source')) data_source=QueryString.data_source;
	if(QueryString.hasOwnProperty('indicator')) indicator=QueryString.indicator;

	ajax_request_json("backend/search_data_files.php?indicator="+indicator+"&data_source="+data_source,function(json_filenames){
		//console.log(json)
		data_not_loaded_yet=json_filenames.length;
		for(var i=0;i<json_filenames.length;i++){
			ajax_request_json(json_filenames[i],function(json){
				data_arr[data_arr.length]=json; //console.log(json);
				data_not_loaded_yet--;
				if(data_not_loaded_yet==0){continue_app();}
			});
		}
	});



}



var continue_app=function(){
	googled_data_arr=wbApiJson2GoogleJsonArr(data_arr);

	// TODO TODO TODO
	// merge results, and display merged_googled
	// also develop a function to parse wbApiJson with more than one country... multi-column, and make wbApiJson2GoogleJson default to value null if not found for columns
	//merged=GoogleJsonMerge([googled,googled2,googled3,googled4,googled5,googled6],'year');
	merged=GoogleJsonMerge(googled_data_arr,'year');
	
	//alert(JSON.stringify(googled));
	//drawChart(googled);
	drawChart(merged);
}


var wbApiJson2GoogleJsonArr=function(wb_data_arr){
	var ret=[];
    for(var i=0;i<wb_data_arr.length;i++){
		//console.log(wb_data_arr[i]);
		ret[i]=wbApiJson2GoogleJson(wb_data_arr[i]);
	}
	return ret;
}

/*
WorldBank API objects
	{
		"indicator" :
		{
			"id" : "SP.POP.TOTL",
			"value" : "Population, total"
		},
		"country" :
		{
			"id" : "ES",
			"value" : "Spain"
		},
		"value" : null,
		"decimal" : "1",
		"date" : "2014"
	},
*/
function wbApiJson2GoogleJson(wbApiJson){
	// indicator must match with the selected
	// ideally I will have downloaded batches per country and indicator [all-years] (a lot of small json files, like es_population.json)
	// cols --> year, indicator-country (e.g., year, population-es) (e.g., 1960, 44444), normally numbers...
	
	var indicator=wbApiJson[1][0]['indicator']['id'];
	var region=wbApiJson[1][0]['country']['value'];
	if(debug){console.log(indicator+" "+region);}
	var cols=[{'id': 'year', 'label': 'year', 'type': 'number'},{'id': indicator+"-"+region, 'label': region, 'type': 'number'}]; //indicator+"-"+
	var rows=[];
	
	for(var i=0;i<wbApiJson[1].length;i++){
		if(wbApiJson[1][i].value!=null){
			rows.push({'c':[{'v': wbApiJson[1][i].date},{'v': wbApiJson[1][i].value}]});
		}
	}
	return {'cols':cols,'rows':rows};
}

/*
Make this robust to different googlejson elements containing different number of rows (FOR NOW LIMITED TO 2 COLUMN GOOGLEJSONs)
From 2 column to n column, key column must be col[0] index 0
*/
function GoogleJsonMerge(googlejson_array, key_col){
	var cols=[{'id': key_col, 'label': key_col, 'type': 'not_set'}];
	var rows=[];
	var col_index=0;
	var rows_construct={}; // key [values]
	for(var i=0;i<googlejson_array.length;i++){
		// columns
		if(googlejson_array[i].cols[0].id==key_col){
			if(cols[0].type=='not_set') cols[0].type=googlejson_array[i].cols[0].type;
			else if (cols[0].type!=googlejson_array[i].cols[0].type) throw new Error("key_col 'type' does not correspond in google_json_array["+i+"]");
		}else{
			throw new Error("key_col not found in google_json_array["+i+"]");
		}
		for(var j=1;j<googlejson_array[i].cols.length;j++){
			cols.push(googlejson_array[i].cols[j]);
			// rows, note the they might not be sorted, or exactly the same
			var used_keys=[];
			for(var k=0;k<googlejson_array[i].rows.length;k++){
				//rows[i]={'c':[{'v': wbApiJson[1][i].date},{'v': wbApiJson[1][i].value}]};
				var key=googlejson_array[i].rows[k].c[0].v;
				used_keys.push(key);
				if(!rows_construct.hasOwnProperty(key)){ // fill with nulls
					rows_construct[key]=[];
					/*for(var fill_null=0;fill_null<col_index;fill_null++){rows_construct[key][fill_null]=undefined;}*/// undefined by default
				}
				rows_construct[key][(j-1)+col_index]=googlejson_array[i].rows[k].c[j].v;
				for(var key in rows_construct) {
					if (rows_construct.hasOwnProperty(key) && used_keys.indexOf(key)==-1) {
						rows_construct[key][(j-1)+col_index]=undefined;
					}
				}
			}
			col_index++;
		} 
		
	}
	for(var key in rows_construct) {
		if (rows_construct.hasOwnProperty(key)) {
			var row=[{'v': key}];
			for(var i=0;i<rows_construct[key].length;i++){
				row.push({'v': rows_construct[key][i]});
			}
			rows.push({'c':row});
		}
	}


	return {'cols':cols,'rows':rows};
}


// re-draw a dashboard elements (tables, charts) according to values selected
function changeChartColumns() {
	var arr = listValuesCheckBox();
	 for(var i=0;i<elements.length;i++){
		elements[i].setView({'columns': arr});
	 }
     // no need to apply this to controls...
	 //for(var i=0;i<controls.length;i++){
		//controls[i].setView({'columns': arr});
	 //}	 
	 dashboard.bind(controls, elements);
	if(arr.length<2){
		alert("Note: No elements are selected and then there is nothing to represent.");
		  dashboard.draw(data);
		//$("#"+elements[1].fg).hide();
	}else{
			$('div[id^=google-visualization-errors]').remove();
		  //$("#"+elements[1].fg).show();
	 }
	dashboard.draw(data);

	return true;
}

// return an array of selected values
function listValuesCheckBox(){
	var myArray = [];
	myArray[0] = 0;
	var pos = 1;
	var col = 1;
$('option', $('#selitems')).each(
	function() {
	if($(this).is(':selected')){
		var val = $(this).val();
			myArray[col] = pos;
		++col;
	}
		++pos;
	}
);

	return myArray;
}

// Creates and populates a data table, instantiates the chart, passes in the data and draws it.
// Key col is suposed to be index 0 (col[0])
function drawChart(raw_data) {
	// check number and type of columns to decide if we add controls and graphs
	var showgraph=true;
	var control1type='CategoryFilter';
	if(raw_data.cols.length<2){
		showgraph=false;
		$("#chart_div").hide();
		$("#selector").hide();
	}else{
		for(var i=1;i<raw_data.cols.length;i++){
			if(raw_data.cols[i].type=="string"){
				showgraph=false;
				$("#chart_div").hide();			
				$('#selector').hide();
				break;				
			}
		}
		// showgraph!
		// check the x-axis type
		$("#chart_div").show();			
		$('#selector').show();
		if(raw_data.cols[0].type=="date" || raw_data.cols[0].type=="number"){
			control1type='ChartRangeFilter';
		}
	}
	
	
	
	data = new google.visualization.DataTable(raw_data);
	// data can also be added cell by cell but we expect the data to be in google data format already (rows/cols)
	//data.addColumn('number', 'Year');data.addRows(years.length); etc...
	// it would be interesting to encode dates as dates not just decimal number...
	
	

	// Initalize element selector
	for (var i = 1; i  < raw_data.cols.length; ++i) {
	  var val=raw_data.cols[i].id;
	  $('#selitems').append(' <option value="'+val+'" onchange="changeChartColumns()"  selected="selected">'+val+'</option>');          
	}

	// Initialize dashboard
	dashboard = new google.visualization.Dashboard(document.getElementById('dashboard'));
	// Define a table
	/*table = new google.visualization.ChartWrapper({
		'chartType': 'Table',
		'containerId': 'table_div',
		'options': {
		'width': '915px'
		}
	});
	elements[elements.length]=table;*/


		// Define controls
		
		
		gcontrol1 = new google.visualization.ControlWrapper({
			'controlType': control1type, //'CategoryFilter',ChartRangeFilter,NumberRangeFilter, this can only be applied to rows
			'containerId': 'control1',
		
			'options': {
			'filterColumnLabel': raw_data.cols[0].label, //raw_data.cols[0] 'refPeriod'
				   'ui': {
				   //'chartType': 'LineChart',
				   'chartOptions': {
				 'chartArea': {'width': '80%','top':0,'left':0}
				   }//,

				   //'minRangeSize': 5
			/*'ui': {
			'labelStacking': 'vertical'
			}*/
			}
			}//,      // Initial range: 2012-02-09 to 2012-03-20. 'state': {'range': {'start': new Date(2012, 1, 9), 'end': new Date(2012, 2, 20)}} 'state': {'range': {'start': 1990, 'end': 2012}}
		});
		controls[controls.length]=gcontrol1;
		

		
	if(showgraph){
		// Define a chart
		chart = new google.visualization.ChartWrapper({
			'chartType': 'LineChart',    //BarChart , LineChart, ColumnChart
			'containerId': 'chart_div',
			'options': {
					 //'width': '90%',
					 //'height': '90%',
				 //'chartArea': {'width': '80%','height':'80%'}, //,'top':0,'left':20
				 'legend': 'right',
				 'vAxis': {'format': 'short'}
			//'hAxis': {'slantedText': false}//,
			//'vAxis': {'viewWindow': {'min': 0, 'max': 2000}},
			//'isStaked': 'true'
			//'chartArea': {top: 10, right: 10, bottom: 0,left:10}
			}//,
			// Configure the barchart to use 
			//'view': {'columns': [0,1,2]}
		});
	
		elements[elements.length]=chart;

		
			
	}
	
		// bind controls to chart and table
		dashboard.bind(controls, elements);
		// draw dashboard
		dashboard.draw(data);	

	if(showgraph){
		// dynamiaclly selecting chart type
		// NOTE: It must be defined after the dashboard.bind function
		google.visualization.events.addListener(gcontrol1, 'statechange', function() {
			//alert(table.getDataTable().getNumberOfRows());
		  if(table.getDataTable().getNumberOfRows()<2){
			chart.setChartType('ColumnChart');
				chart.draw();
		  }else{
			chart.setChartType('LineChart');
				chart.draw();		  
		  }
		});	
		// add fancy seletcts with chosen
		$(".chzn-select").chosen();
		$("#selitems").trigger("liszt:updated");
	}		
}

/*
	// Checks if data is encoded in a string-string-number table and if so translates it to string-number-...-number format.
// This ensures that data can be represened in a bidimensional graph
function sortArray(raw_data){        
                var output={};
                output.head={};
                output.head.vars=[];
                output.results={};
                output.results.bindings=[];	
        
        	var newRows={};
        	var newRowsArr=[];
	        var newColumns={};
	        var newColumnsArr=[];
	        var newColumnTypes={}; // column types by name
		var coln=1;
		var rown=0;
		
                var scols = raw_data.head.vars; // array of column names
                var srows = raw_data.results.bindings; // array of objects (columns are refered by names)

		// Do nothing or convert 3-column structure of 1 decimal and 2 string or date columns into a 2D representable table
		if(scols.length==3 && srows.length>0){
			var intcols=[];
			var string_or_date_cols=[];
			for (var k=0;k<scols.length;k++) {
			    if (scols.hasOwnProperty(k)) {
			    	// Save datatype
			    	if(srows[0][scols[k]].hasOwnProperty("datatype")){
			    		newColumnTypes[scols[k]]=srows[0][scols[k]].datatype;
			    	}else{
			    		newColumnTypes[scols[k]]='http://www.w3.org/2001/XMLSchema#string';
			    	}
			    	
			       if(srows[0][scols[k]].type=='typed-literal' && (srows[0][scols[k]].datatype=='http://www.w3.org/2001/XMLSchema#decimal' || srows[0][scols[k]].datatype=='http://www.w3.org/2001/XMLSchema#integer' || srows[0][scols[k]].datatype=='http://www.w3.org/2001/XMLSchema#float' || srows[0][scols[k]].datatype=='http://www.w3.org/2001/XMLSchema#double')){
			       	intcols[intcols.length]=scols[k];  // you can also use push
			       }else{
				       if(srows[0][scols[k]].type=='literal' || srows[0][scols[k]].datatype=='http://www.w3.org/2001/XMLSchema#string' || srows[0][scols[k]].datatype=='http://www.w3.org/2001/XMLSchema#date'){
				       	string_or_date_cols[string_or_date_cols.length]=scols[k];
				       }else{
				       	return raw_data;
				       }
			       }		       
			    }
			}
			
			// fail
			if(intcols.length!=1 || string_or_date_cols.length!=2){
				return raw_data;
			}

			alert('3-col data (string-string-number) is being transformed to a representable format');

			// put as column names the values of the first original column
			newRowsArr[0]=string_or_date_cols[0]; //"refPeriod";
			newColumnsArr[0]=string_or_date_cols[0]; //"refPeriod";

			// the first of "string or date" column will be the index of rows, the second will be translated to columns
			for (var i = 0; i  < srows.length; ++i) {
			  var val0=srows[i][string_or_date_cols[0]].value;
			  var val1=srows[i][string_or_date_cols[1]].value;
			  
			  if (typeof newRows[val0] == 'undefined') {
				  newRows[val0]=rown;
				  newRowsArr[rown]=val0;
				  rown++;
				  //factTimeDimension[val0]=i;
				  //timeRefs.push(val0);
			  }	  
			  if (typeof newColumns[val1] == 'undefined') {
				  newColumns[val1]=coln;
				  newColumnsArr[coln]=val1;
				  coln++; 
			  }
			}		
		

			// create a new JSON object 
	
			// set headers
			for(var i=0;i<coln;i++){
				output.head.vars.push(newColumnsArr[i]);
			}

			// initialize data (the numeric data is set as decimal by default) --> float/double with mantissa are omitted
			for(var i=0;i<newRowsArr.length;++i){
				var row={};
				// Index column type can be string/date/decimal? depending on the input data
				row[newColumnsArr[0]]={"datatype":newColumnTypes[newColumnsArr[0]],"type":"typed-literal","value":newRowsArr[i]};
				for(var j=1;j<coln;++j){
					row[newColumnsArr[j]]={"datatype":"http://www.w3.org/2001/XMLSchema#decimal","type":"typed-literal","value":0.0};
				}
				output.results.bindings[i]=row;
			}
		
		
			// fill out with data
			for (var i = 0; i < srows.length; ++i) {
				  var val0=srows[i][string_or_date_cols[0]].value;
				  var val1=srows[i][string_or_date_cols[1]].value;		
				  var val2=srows[i][intcols[0]].value;		
				  output.results.bindings[newRows[val0]][val1].value=val2;
			}
		}else{
			output=raw_data;
		}


		
		return output;
}

*/

