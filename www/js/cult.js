"use strict";

var app_name='CULT';

// DATA vars, TODO all data will be already in the same format...
var data_source="wb";
var data_not_loaded_yet=9999;
var json_data_files=undefined;
var data_map={};
var indicator_list=[];
var country_list=[];
var period_list=[];
var period_map={};
var lifes=3;
var dificulty='normal'; // easy, difficult
/* encoded in the data file
var period_correspondence={
	previous_year:-1, last_lustrum:-4,	last_decade:-9,	last_2decade:-19
};*/

// MEDIA
var images = [
	"../../cult-media/img/clock.png",
	"../../cult-media/img/clock.svg",
	"../../cult-media/img/correct.png",
	"../../cult-media/img/wrong.png"
];
var sounds = [];
var jsons=[];

var activity_timer=new ActivityTimer();
var user_data={};
var session_data={
	user: null,
    type: "qa",
	level: "normal",
	timestamp: "0000-00-00 00:00",
	num_correct: 0,
	num_answered: 0,
	result: 0,
    action: 'send_session_data_post',
	details: []
};

var media_objects;
var session_state="unset";
var header_zone=document.getElementById('header');
var header_text=undefined;
var canvas_zone=document.getElementById('zone_canvas');
var canvas_zone_vcentered=document.getElementById('zone_canvas_vcentered');
var canvas_zone_answers;
var canvas_zone_question;

var dom_score_correct;
var dom_score_answered;
var correct_answer='undefined';
var answer_msg="";
var show_answer_timeout;

// LOAD DATA
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


var EASY_FORBIDDEN_INDICATORS=['laborforce','p15to64'];
var NORMAL_FORBIDDEN_INDICATORS=['surpdeficitgdp','reserves','inflation','gdp','gdppcap','gdpgrowth','extdebt','debtgdp'];
var DIFFICULT_FORBIDDEN_INDICATORS=[];

var YEAR_DIFF_RANGE={
    easy: {min:50,max:200},
    normal: {min:5,max:100},
    difficult: {min:1,max:50}
};

var TIMES_BIGGER_MIN={
    easy: 1.51,
    normal: 1.26,
    difficult: 1.01
};

var match_level_forbidden_indicators=function(level,indicator){
	if(level=='easy' && (EASY_FORBIDDEN_INDICATORS.indexOf(indicator)!=-1
		|| !match_level_forbidden_indicators('normal',indicator)))
		return false;
	if(level=='normal' && (NORMAL_FORBIDDEN_INDICATORS.indexOf(indicator)!=-1
		|| !match_level_forbidden_indicators('difficult',indicator)))
		return false;
	if(level=='difficult' && DIFFICULT_FORBIDDEN_INDICATORS.indexOf(indicator)!=-1)
		return false;
	return true;
}

var match_level_times_bigger_margin=function(level, times_bigger){
	//console.log(level+"  "+times_bigger);
	if(times_bigger<TIMES_BIGGER_MIN[level]) return false;
	return true;
}

var match_level_year_diff_range=function(level, year_diff){
	if(year_diff>=YEAR_DIFF_RANGE[level].min && year_diff<=YEAR_DIFF_RANGE[level].max) return true;
	return false;
}


ajax_request('backend/ajaxdb.php?action=gen_session_state',function(text) {
	session_state=text;	//console.log(session_state);
});




function login_screen(){
	if(debug){alert('login_screen called');}
	header_zone.innerHTML='<h1>CULT Sign in</h1>';
	var invitee_access="";
	if(debug){
		invitee_access='<br /><button id="exit" class="button exit" onclick="invitee_access();">Invitee (offline)</button>';
	}
	canvas_zone_vcentered.innerHTML='\
	<div id="signinButton" class="button">Google+\
   <span class="icon"></span>\
    <span class="buttonText"></span>\
	</div>'+invitee_access+'\
	<br /><button id="exit" class="button exit" onclick="exit_app();">Exit</button> \
		';
	gapi.signin.render('signinButton', {
	  'callback': 'signInCallback',
	  'clientid': '718126583517-g98bubkmq93kb0mtlsn6saffqh0ctnug.apps.googleusercontent.com',
	  'cookiepolicy': 'single_host_origin',
	  'redirecturi': 'postmessage',
	  'accesstype': 'offline',
	  'scope': 'openid email'
	}); 
}




function invitee_access(){
	session_data.user='invitee';
	session_data.user_access_level='invitee';
	user_data.email='invitee';
	user_data.display_name='invitee';
	user_data.access_level='invitee';
	menu_screen();
}


function signInCallback(authResult) {
	//console.log(authResult);
	if (authResult['code']) {
		canvas_zone_vcentered.innerHTML='<div class="loader">Loading...</div>';
		// Send one-time-code to server, if responds -> success
		if(debug) console.log(authResult);
		ajax_request_json(
			backend_url+'ajaxdb.php?action=gconnect&state='+session_state+'&code='+authResult['code'],
			function(result) {
                if (result) {
                    if(result.hasOwnProperty('error') && result.error!=""){alert("LOGIN ERROR: "+result.error); return;}
                    if(result.hasOwnProperty('info') && result.info=="new user"){
                        open_js_modal_content_accept("<p>New user created successfully for: "+result.email+". The challenge begins!</p>");
                    }
                    
                    
                    if(debug){
                        console.log(result);
                        console.log("logged! "+result.email+" level:"+result.access_level);
                        alert("logged! "+result.email+" level:"+result.access_level);
                    }
                    user_data.info=result.info;
                    user_data.display_name=result.display_name;
                    user_data.user_id=result.user_id;
                    user_data.picture=result.picture;
                    user_data.email=result.email;
					user_data.access_level=result.access_level;
					session_data.user=result.email;
					session_data.user_access_level=result.access_level;
					menu_screen();
				} else if (authResult['error']) {
					alert('There was an error: ' + authResult['error']);
					login_screen();
				} else {
					alert('Failed to make a server-side call. Check your configuration and console.</br>Result:'+ result);
					login_screen();
				}
			}
		);
	}
}

function gdisconnect(){
	hamburger_close();
	if(user_data.email=='invitee'){ login_screen(); return;}
	ajax_request_json(
		backend_url+'ajaxdb.php?action=gdisconnect', 
		function(result) {
			if (result.hasOwnProperty('success')) {
				if(debug) console.log(result.success);
			} else {
				if(!result.hasOwnProperty('error')) result.error="NO JSON RETURNED";
				alert('Failed to disconnect.</br>Result:'+ result.error);
			}
            login_screen();
        }
	);
}

function admin_screen(){
	ajax_request_json(
		backend_url+'ajaxdb.php?action=get_users', 
		function(data) {
			var users=data;
			canvas_zone_vcentered.innerHTML=' \
				User:  <select id="users-select"></select> \
				<br /><button onclick="set_user()" class="button">Acceder</button> \
				';
			users_select_elem=document.getElementById('users-select');
			select_fill_with_json(users,users_select_elem);
		}
	);
}


function set_user(){
	if(session_data.user!=users_select_elem.options[users_select_elem.selectedIndex].value){
		session_data.user=users_select_elem.options[users_select_elem.selectedIndex].value;
		user_subjects=null;
	}
	menu_screen();
}

function options(){
	canvas_zone_vcentered.innerHTML='\
		  <br /><button id="op_difficult" class="button">Difficult</button>\
		  <br /><button id="op_normal" class="button">Normal</button>\
		  <br /><button id="op_easy" class="button">Easy</button>\
		  <br /><br /><button class="button" id="go-back">Back</button>\
          ';
	if(session_data.level=="difficult") document.getElementById('op_difficult').classList.add('selectedOption');
	if(session_data.level=="normal") document.getElementById('op_normal').classList.add('selectedOption');
	if(session_data.level=="easy") document.getElementById('op_easy').classList.add('selectedOption');
    document.getElementById("op_difficult").addEventListener(clickOrTouch,function(){session_data.level='difficult';options();});
    document.getElementById("op_normal").addEventListener(clickOrTouch,function(){session_data.level='nomral';options();});
    document.getElementById("op_easy").addEventListener(clickOrTouch,function(){session_data.level='easy';options();});
    document.getElementById("go-back").addEventListener(clickOrTouch,function(){menu_screen();});

}

function top_scores(){
    canvas_zone_vcentered.innerHTML=' \
    <div id="results-div">cargando resultados...</div> \
    <br /><button id="go-back" onclick="menu_screen()">Volver</button> \
    ';
	ajax_request_json(
		backend_url+'ajaxdb.php?action=get_top_scores&user='+user_data.email+'&type='+session_data.type+'&level='+session_data.level, 
		function(data) {
			if(debug) console.log(data);
			var rtable='<table class="table-wo-border table-small"><tr><td><b>Rank</b></td><td><b>Name</b></td><td><b>Score</b></td><td><b>Date</b></td></tr>';
			for (var i=0;i<data.absolute_elements.length;i++){
				var d=new Date(data.absolute_elements[i].timestamp.substr(0,10));
				rtable+='<tr><td>'+(i+1)+'</td><td>'+data.absolute_elements[i].user.substr(0,8)+'</td><td style="text-align:right;">'+data.absolute_elements[i].num_correct+'</td><td>'+monthNames[d.getMonth()]+'-'+d.getFullYear()+'</td></tr>';
			}
			rtable+='<tr><td colspan="4"></td></tr>\
					 <tr><td colspan="4"><b>User rank</b></td></tr>';
			for (var i=0;i<data.usr_elements.length;i++){
				var d=new Date(data.usr_elements[i].timestamp.substr(0,10));
				rtable+='<tr><td>'+data.usr_elements[i].rank+'</td><td>'+user_data.email.substr(0,8)+'</td><td style="text-align:right;">'+data.usr_elements[i].num_correct+'</td><td>'+monthNames[d.getMonth()]+'-'+d.getFullYear()+'</td></tr>';
			}
			rtable+="</table>";
			canvas_zone_vcentered.innerHTML='\
				  TOP SCORES<br />Hall of Fame<br/>\
				  <div class="small-font">type: '+session_data.type+'  level: '+session_data.level+'</div><br/>\
				  '+rtable+'\
				  <br /><button class="button" onclick="menu_screen()">Back</button>\
				  ';
                  
/*            
            IMPROVE THE THING BELOW so it can take id (order), and that it can convert a date into month-year
            document.getElementById("results-div").innerHTML="Resultados user: "+cache_user_subject_results[session_data.subject].general.user+" - subject: <b>"+cache_user_subject_results[session_data.subject].general.subject+"</b><br /><table id=\"results-table\"></table>";
            var results_table=document.getElementById("results-table");
            DataTableSimple.call(results_table, {
                data: data.absolute_elements,
                row_id: 'id',
                pagination: 5,
                columns: [
                    { data: 'timestamp', col_header: 'Id', link_function_id: 'explore_result_detail' },
                    { data: 'type', col_header: 'Tipo',  format: 'first_4'},
                    { data: 'mode', col_header: 'Modo',  format: 'first_4'},
                    { data: 'age', col_header: 'Edad' },
                    { data: 'duration', col_header: 'Tiempo',  format: 'time_from_seconds_up_to_mins'}, 
                    { data: 'result', col_header: '%', format: 'percentage_int' } 
                ]
            } );                  */
        }
	);

}


function show_profile(){
	canvas_zone_vcentered.innerHTML='\
		  name:'+user_data.display_name+'<br />\
		  email:'+user_data.email+'<br />\
		  type: '+user_data.access_level+'<br />\
		  <br /><button class="button" onclick="menu_screen()">Back</button>\
          ';
}

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

	if(is_app){
		session_data.user='app...'; // find a way to set the usr, by google account
	}
	if(debug) console.log('user.email: '+user_data.email);
	if(session_state=="unset"){
        canvas_zone_vcentered.innerHTML='...waiting for session state...';
        setTimeout(function() {menu_screen()}, 2000); // add a counter and if it reaches something fail gracefully
	}else if(user_data.email==null){
		login_screen();
	}else{
		var sign='<li><a href="#" onclick="hamburger_close();show_profile()">profile</a></li>\
                  <li><a href="#" onclick="hamburger_close();gdisconnect()">sign out</a></li>';
		if(user_data.email=='invitee'){
			sign='<li><a href="#" onclick="hamburger_close();login_screen()">sign in</a></li>';
		}
		// TODO if admin administrar... lo de sujetos puede ir aquí tb...
		hamburger_menu_content.innerHTML=''+get_reduced_display_name(user_data.display_name)+'<ul>\
		'+sign+'\
	      <li><a href="#" onclick="exit_app()">exit app</a></li>\
		</ul>';
        header_zone.innerHTML='<div id="header_basic"><a id="hamburger_icon" onclick="hamburger_toggle(event)"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
        <path d="M2 6h20v3H2zm0 5h20v3H2zm0 5h20v3H2z"/></svg></a> <span id="header_text" onclick="menu_screen()">'+app_name+'</span></div> <div id="header_status"> </div>';
        header_text=document.getElementById('header_text');
		// Optionally if(is_app) we could completely remove header...
		canvas_zone_vcentered.innerHTML=' \
		<div id="menu-logo-div"></div> \
		<nav id="responsive_menu">\
		<br /><button id="start-button" class="button" disabled="true">Play</button> \
		<br /><button id="options" class="button">Options</button> \
		<br /><button id="top_scores" class="button">Top Scores</button> \
		<br /><button id="exit_app" class="button exit">Exit</button> \
		</nav>\
		';
        document.getElementById("start-button").addEventListener(clickOrTouch,function(){play_game();});
        document.getElementById("options").addEventListener(clickOrTouch,function(){options();});
        document.getElementById("top_scores").addEventListener(clickOrTouch,function(){top_scores();});
        document.getElementById("exit_app").addEventListener(clickOrTouch,function(){exit_app();});
		
		if(json_data_files==undefined){
		    ajax_request_json("backend/search_game_data_files.php?data_source="+data_source,function(json_filenames){
		        //console.log(json_filenames)
		        json_data_files=json_filenames;
		        data_not_loaded_yet=json_filenames.length+1;
		        for(var i=0;i<=json_filenames.length;i++){
		            if(debug) console.log('requesting '+json_filenames[i]);
                    if(i<json_filenames.length){
                        ajax_request_json(json_filenames[i],function(json){
                            data_map[json.indicator]=json; 
                            indicator_list.push(json.indicator);
                            if(debug) console.log(json.indicator);
                            data_not_loaded_yet--;
                            if(data_not_loaded_yet==0){
                                // load countries and periods (only once)
                                load_country_list_from_indicator('population');
                                load_period_list_from_indicator_ignore_last_year('population');
                                document.getElementById("start-button").disabled=false;
                            }
                        });
                    }else{
                        ajax_request_json('./backend/history.tsv.json',function(json){
                            data_map['history']=json; 
                            if(debug) console.log('history loaded');
                            data_not_loaded_yet--;
                            if(data_not_loaded_yet==0){
                                // load countries and periods (only once)
                                load_country_list_from_indicator('population');
                                load_period_list_from_indicator_ignore_last_year('population');
                                document.getElementById("start-button").disabled=false;
                            }
                        });                        
                    }
		        }
		    });
		}else{
		    document.getElementById("start-button").disabled=false;
		    if(user_data.access_level!='invitee'){console.log('logged as admin');}
		}
	}
}

var countdown_limit_end_secs=30;
var silly_cb=function(){
	activity_timer.stop();
	if(debug) console.log("question timeout!!!");
	check_correct("timeout","incorrect","Timeout! You have not answered");
}
var tricker_cb=function(){
	if(debug) console.log("tricker progress "+activity_timer.seconds);
	document.getElementById("time_left").value=activity_timer.seconds;
	if(activity_timer.seconds==countdown_limit_end_secs-3){
		// blink background
		// change progress color to red
		console.log("progress-red...");
		document.getElementById("time_left").classList.add("progress-red");
	}
}
activity_timer.set_tricker_callback(tricker_cb);
activity_timer.set_limit_end_seconds(countdown_limit_end_secs); 
activity_timer.set_end_callback(silly_cb);

var end_game=function(){
    alert('this will ask if you want to store your session (save game to continue later or exit?)');
	activity_timer.stop();
	activity_timer.reset();
	menu_screen();
}

var play_game=function(){
    session_data.type="qa";
	session_data.num_correct=0;
	var timestamp=new Date();
	session_data.timestamp=timestamp.getFullYear()+"-"+
		pad_string((timestamp.getMonth()+1),2,"0") + "-" + pad_string(timestamp.getDate(),2,"0") + " " +
		 pad_string(timestamp.getHours(),2,"0") + ":"  + pad_string(timestamp.getMinutes(),2,"0");

    var header_status=document.getElementById('header_status');
    header_status.innerHTML=' Life: <span id="current_lifes">&#9825; &#9825; &#9825;</span>   Score: <span id="current_score_num">0</span>';
    lifes=3;
	update_lifes_representation();
	canvas_zone_vcentered.innerHTML=' \
	<div id="question"></div>\
	<div id="answers"></div>\
	<div id="game-panel">\
        <img src="'+media_objects.images['clock.png'].src+'" style="width:30px;" /> &nbsp; <progress id="time_left" value="0" max="'+countdown_limit_end_secs+'"></progress>\
        <button id="exit_game_button" class="button">exit</button>\
    </div>\
	';
    document.getElementById("exit_game_button").addEventListener(clickOrTouch,function(){end_game();});
	//get elements
	dom_score_correct=document.getElementById('current_score_num');
	canvas_zone_question=document.getElementById('question');
	canvas_zone_answers=document.getElementById('answers');
	console.log("let's go, there are "+json_data_files.length+" indicator files. Countries in 'population' = "+country_list.length);
	activity_timer.reset();
	same_country_question(random_item(indicator_list));
	// TODO to avoid recursion this should probably be a game status checker
	// e.g., in a timeout or time_set...
}

function check_correct(clicked_answer,correct_answer,optional_msg){
	activity_timer.stop();
	if(typeof(optional_msg)==='undefined') optional_msg="";
	document.getElementById("time_left").value=0;
	document.getElementById("time_left").classList.remove("progress-red");
	var activity_results={};
	var timestamp=new Date();
	var timestamp_str=timestamp.getFullYear()+"-"+
		pad_string((timestamp.getMonth()+1),2,"0") + "-" + pad_string(timestamp.getDate(),2,"0") + " " +
		 pad_string(timestamp.getHours(),2,"0") + ":"  + pad_string(timestamp.getMinutes(),2,"0") + 
			":"  + pad_string(timestamp.getSeconds(),2,"0");
	if(typeof clicked_answer != "string"){ 
		alert("ERROR: Unexpected answer... non-string");
	}
	/*activity_results.choice=clicked_answer;*/
	if (clicked_answer==correct_answer){
		session_data.num_correct++;
		//activity_results.result="correct";
		if(session_data.mode!="test"){
			//audio_sprite.playSpriteRange("zfx_correct");
			dom_score_correct.innerHTML=session_data.num_correct;
			open_js_modal_content('<div class="js-modal-correct"><h1>CORRECT</h1>'+optional_msg+'<br /><button onclick="nextActivity()">OK</button></div>');
		}
	}else{
		activity_results.result="incorrect";
		lifes--;
		update_lifes_representation();
		if(session_data.mode!="test"){
			//audio_sprite.playSpriteRange("zfx_wrong"); // add a callback to move forward after the sound plays... <br />Correct answer: <b>'+correct_answer+'</b>
			open_js_modal_content('<div class="js-modal-incorrect"><h1>INCORRECT</h1> <br />'+optional_msg+'<br /><button onclick="nextActivity()">OK</button></div>');
		}
	}
	//session_data.details.push(activity_results);
	session_data.num_answered++;
	
	//dom_score_answered.innerHTML=session_data.num_answered;
	var waiting_time=1000;
	if(session_data.mode!="test") waiting_time=120000; 
	show_answer_timeout=setTimeout(function(){nextActivity()}, waiting_time);
}

function update_lifes_representation(){
	var elem_lifes=document.getElementById('current_lifes');
	var lifes_representation='';
	for (var i=0;i<lifes;i++){
		lifes_representation+="&#9825; ";
	}
	elem_lifes.innerHTML=lifes_representation;
} 

function nextActivity(){
	 clearTimeout(show_answer_timeout);
	activity_timer.reset();
	if(session_data.mode!="test") remove_modal();
	if(lifes==0){
		send_session_data();
	}else{
        var randnum=Math.floor((Math.random() * 10));
		if(randnum<2)
			same_country_question(random_item(indicator_list));
		else if (randnum < 4)
			diff_country_question(random_item(indicator_list));
        else
            history_question();
	}
}

var calculate_times_bigger=function(a,b){
	if (Number(a)*Number(b)<0){
		return ((Number(a)+-2*Number(b))/(-1*Number(b))).toFixed(2);		
	}else{
		return (Number(a)/Number(b)).toFixed(2);
	}
}




var history_question=function(){
	console.log("history question");
	var fact1=random_item(data_map.history);
    var fact2=random_item(data_map.history,fact1.fact);
    // Makes sense to discard overlaps since the question is "what was before"
    // and not "what satrted before" or "what ended before"
    if(! (fact1.end<fact2.begin || fact2.end<fact1.begin)){
        console.log("USLESS: overlapping facts "+fact1.fact+" "+fact2.fact+"");
        nextActivity(); return;
    }
    var year_diff=Math.abs(Number(fact1.begin) - Number(fact2.begin));
    if(!match_level_year_diff_range(session_data.level,year_diff)){
        console.log(year_diff+" does not match level "+session_data.level);
        nextActivity();return;
    }

    correct_answer=fact1.fact;
    if(Number(fact2.end) < Number(fact1.begin)){
		correct_answer=fact2.fact;
		answer_msg='<br /><b>'+fact2.fact+'</b> (<b>'+fact2.begin+'</b> <--> '+fact2.end+')<br />was before<br /><b>'+fact1.fact+'</b> (<b>'+fact1.begin+'</b> <--> '+fact1.end+')<br />';
    }else{
		answer_msg='<br /><b>'+fact1.fact+'</b> (<b>'+fact1.begin+'</b> <--> '+fact1.end+')<br />was before<br /><b>'+fact2.fact+'</b> (<b>'+fact2.begin+'</b> <--> '+fact2.end+')<br />';
    }
	//if(!match_level_times_bigger_margin(session_data.level,times_bigger)){nextActivity();return;}
    activity_timer.start();
    canvas_zone_question.innerHTML='What was before?';
    canvas_zone_answers.innerHTML=' \
    <div id="answer1" class="answer aleft">'+fact1.fact+'</div>\
    <div id="answer2" class="answer aright">'+fact2.fact+'</div>\
    ';
    var boxes=document.getElementsByClassName("answer");
    for(var i=0;i<boxes.length;i++){
        boxes[i].addEventListener(clickOrTouch,function(){
            check_correct(this.innerHTML,correct_answer,answer_msg)
            });
    }
}



var same_country_question=function(indicator){
	// create another class called countdown with 2 callbacks tricker and end
	// setTimeout(function(){nextActivity()}, waiting_time);
	// So that each trick increases a progress bar and on end it stops and fails
	// even in the tricker callback we can set red blink when there are 3 seconds left...
	console.log("same country question for indicator="+indicator);
	if(!match_level_forbidden_indicators(session_data.level,indicator)){
		console.log(indicator+" not allowed in "+session_data.level);
		nextActivity();return;
	}
	var country=random_item(country_list);
    var period1="last_year";
	var period2=random_item(period_list); // last_year is already not in period_list
    if(data_map[indicator].data[period1][country]==null){
        period1="previous_year";
        period2=random_item(period_list,"previous_year");
        if(data_map[indicator].data[period1][country]==null){
            console.log("USLESS: NULL value "+country+" "+indicator+" -- last_year and "+period1);
            nextActivity(); return;
        }
    }

	var times_bigger;
	if(data_map[indicator].data[period1][country]==data_map[indicator].data[period2][country]){
		console.log("USLESS: Equal value "+country+" "+indicator+" -- "+period_map[period1]+" ("+data_map[indicator].data[period1][country]+") and "+period_map[period2]+" ("+data_map[indicator].data[period2][country]+")");
        nextActivity(); return;
	}else if(Number(data_map[indicator].data[period1][country])>Number(data_map[indicator].data[period2][country])){
		correct_answer=period_map[period1];
		times_bigger=calculate_times_bigger(data_map[indicator].data[period1][country],data_map[indicator].data[period2][country]);
		answer_msg='<br />'+period_map[period1]+' <b>'+times_bigger+' times bigger</b> than '+period_map[period2]+'<br />';
    }else if(Number(data_map[indicator].data[period1][country])<Number(data_map[indicator].data[period2][country])){
        correct_answer=period_map[period2];
		times_bigger=calculate_times_bigger(data_map[indicator].data[period2][country],data_map[indicator].data[period1][country]);
		answer_msg='<br />'+period_map[period2]+' <b>'+times_bigger+' times bigger</b> than '+period_map[period1]+'<br />';
    }
	if(!match_level_times_bigger_margin(session_data.level,times_bigger)){nextActivity();return;}
	answer_msg+=add_answer_details(indicator,period1,period2,country,country);

    activity_timer.start();
    canvas_zone_question.innerHTML='When was <b>'+country+' '+data_map[indicator].indicator_sf+'</b> bigger?';
    canvas_zone_answers.innerHTML=' \
    <div id="answer-'+period_map[period2]+'" class="answer aleft">'+period_map[period2]+'</div>\
    <div id="answer-'+period_map[period1]+'" class="answer aright">'+period_map[period1]+'</div>\
    ';
    var boxes=document.getElementsByClassName("answer");
    for(var i=0;i<boxes.length;i++){
        boxes[i].addEventListener(clickOrTouch,function(){
            check_correct(this.innerHTML,correct_answer,answer_msg)
            });
    }    
}

var diff_country_question=function(indicator){
	console.log("diff country question for indicator="+indicator);
	if(!match_level_forbidden_indicators(session_data.level,indicator)){
		console.log(indicator+" not allowed in "+session_data.level);
		nextActivity();return;
	}
    var period="last_year";
	var country1=random_item(country_list);
	var country2=random_item(country_list,country1);
	var times_bigger;
    
    // ----can give more oportunities by trying alternatives:----
    // previous year, try a country diffenrent than country2... again
    // previous_year is a good to try because some indicators do not have 
    // data for last_year....
    if(data_map[indicator].data[period][country1]==null){
        period="previous_year";
        if(data_map[indicator].data[period][country1]==null){
            console.log("USLESS: NULL value "+country1+" "+indicator+" "+period);
            nextActivity();return;
        }
    }
    if(data_map[indicator].data[period][country2]==null){
        console.log("USLESS: NULL value "+country2+" "+indicator+" "+period);
        nextActivity(); return;
    }
    // ------------------------------------------------------------
	if(data_map[indicator].data[period][country1]==data_map[indicator].data[period][country2]){
		console.log("USLESS: Equal value "+indicator+"  -- "+country1+" ("+data_map[indicator].data[period][country1]+") and "+country2+" ("+data_map[indicator].data[period][country2]+")");
        nextActivity(); return;
	}else if(Number(data_map[indicator].data[period][country1])>Number(data_map[indicator].data[period][country2])){
		correct_answer=country1;
		times_bigger=calculate_times_bigger(data_map[indicator].data[period][country1],data_map[indicator].data[period][country2]);
		answer_msg='<br />'+country1+' <b>'+times_bigger+' times bigger</b> than '+country2+'<br />';
    }else if(Number(data_map[indicator].data[period][country1])<Number(data_map[indicator].data[period][country2])){
        correct_answer=country2;
		times_bigger=calculate_times_bigger(data_map[indicator].data[period][country2],data_map[indicator].data[period][country1]);
		answer_msg='<br />'+country2+' <b>'+times_bigger+' times bigger</b> than '+country1+'<br />';
    }
	if(!match_level_times_bigger_margin(session_data.level,times_bigger)){nextActivity();return;}
	answer_msg+=add_answer_details(indicator,period,period,country1,country2);

    activity_timer.start();
    canvas_zone_question.innerHTML='Which is bigger in '+data_map[indicator].indicator_sf+' ('+period_map[period]+')?';
    canvas_zone_answers.innerHTML='\
    <div id="answer-'+country1+'" class="answer aleft">'+country1+'</div>\
    <div id="answer-'+country2+'" class="answer aright">'+country2+'</div>\
    ';
    var boxes=document.getElementsByClassName("answer");
    for(var i=0;i<boxes.length;i++){
        boxes[i].addEventListener(clickOrTouch,function(){
            check_correct(this.innerHTML,correct_answer,answer_msg)
            });
    }    
}

var add_answer_details=function(indicator,period1,period2,country1,country2){
	var ret='<table class="table-wo-border">';
	ret+='<tr><td>'+period_map[period1]+' '+indicator+'<b> '+country1+'</b> </td><td><b>'+
			num_representation(Number(data_map[indicator].data[period1][country1]))+'</b></td></tr>';
	ret+='<tr><td>'+period_map[period2]+' '+indicator+'<b> '+country2+'</b> </td><td><b>'+
            num_representation(Number(data_map[indicator].data[period2][country2]))+'</b></td></tr>';
	ret+="</table>"
	return ret;
}

var num_representation=function(num, decimals, decimal_symbol,thousand_symbol){
	// standard solution toLocaleString... but not supported in saffary
    decimals = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
    decimal_symbol = decimal_symbol == undefined ? "." : decimal_symbol;
    thousand_symbol = thousand_symbol == undefined ? "," : thousand_symbol; 
    var sign = num < 0 ? "-" : "";
	var integer_part=parseInt(num = Math.abs(+num || 0).toFixed(decimals)) + "";
	var thousand_rest=(thousand_rest = integer_part.length) > 3 ? thousand_rest % 3 : 0;
	var result=sign + (thousand_rest ? integer_part.substr(0,thousand_rest) + thousand_symbol : "")
           + integer_part.substr(thousand_rest).replace(/(\d{3})(?=\d)/g, "$1" + thousand_symbol)
           + (decimals ? decimal_symbol + Math.abs(num - integer_part).toFixed(decimals).slice(2):"")
	if (integer_part.toString().length>12) result=integer_part.substr(0,integer_part.toString().length-12)+" Trillions";
	else if (integer_part.toString().length>9)  result=integer_part.substr(0,integer_part.toString().length-9)+" Billions";
	else if (integer_part.toString().length>6)  result=integer_part.substr(0,integer_part.toString().length-6)+" Millions";
	
	return result;
}

/*var num_easy_representation = function(c, d, t){
var n = this, 
    i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
    j = (j = i.length) > 3 ? j % 3 : 0;
   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
 };*/

var load_country_list_from_indicator=function(indicator){
	for (var country in data_map[indicator].data.last_year) {
		country_list.push(country);
	}
}

var load_period_list_from_indicator_ignore_last_year=function(indicator){
	period_map['last_year']=data_map[indicator].last_year;
	for (var period in data_map[indicator].data) {
		if(period!='last_year')	period_list.push(period);
		period_map[period]= data_map[indicator][period];
	}
}


function send_session_data(){
    remove_modal();
    canvas_zone_vcentered.innerHTML='<h1>GAME OVER</h1>Your <b>score</b> is <b>'+session_data.num_correct+'</b>.';
	if(user_data.access_level=='invitee'){
		canvas_zone_vcentered.innerHTML+='<br />Invitees cannot sent scores to the server<br /><br />\
		<br /><button id="go-back" onclick="menu_screen()">Back</button>';
		return;
	}
	
	if(debug) console.log(JSON.stringify(session_data));
	var xhr = new XMLHttpRequest();
	xhr.open("POST", "http://www.cognitionis.com/cult/www/"+backend_url+'ajaxdb.php',true);
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.responsetype="json";
	xhr.send("action=send_session_data_post&json_string="+(JSON.stringify(session_data))); 
	canvas_zone_vcentered.innerHTML+='<br />...Sending results to the server...<br />';

	xhr.onload = function () {
		var data=JSON.parse(this.responseText);
        if(debug) console.log(data.msg);
		canvas_zone_vcentered.innerHTML+='<br /><button id="go-back" onclick="menu_screen()">Back</button>';
	};

}





