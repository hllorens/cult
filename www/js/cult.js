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
/* encoded in the data file
var period_correspondence={
	previous_year:-1,
	last_lustrum:-4,
	last_decade:-9,
	last_2decade:-19
};*/

// MEDIA
var images = [
	"../../cult-media/img/clock.svg",
	"../../cult-media/img/clock.png",
	"../../cult-media/img/correct.png",
	"../../cult-media/img/wrong.png"
];
var sounds = [];


var activity_timer=new ActivityTimer();
var user_data={};
var session_data={
	user: null,
	level: "1",
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
var canvas_zone=document.getElementById('zone_canvas');
var canvas_zone_vcentered=document.getElementById('zone_canvas_vcentered');
var header_text;
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

ajax_request('backend/ajaxdb.php?action=gen_session_state',function(text) {
	session_state=text;	//console.log(session_state);
});

function login_screen(){
	header_zone.innerHTML='<h1>CULT Sign in</h1>';
	canvas_zone_vcentered.innerHTML='\
	<div id="signinButton" class="button">With Google\
   <span class="icon"></span>\
    <span class="buttonText"></span>\
	</div>\
	<br /><button id="exit" class="button exit" onclick="invitee_access();">Play as invitee (offline)</button> \
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
		document.getElementById('signinButton').innerHTML="Loading...";
		// Send one-time-code to server, if responds -> success
		if(debug) console.log(authResult);
		ajax_request_json(
			backend_url+'ajaxdb.php?action=gconnect&state='+session_state+'&code='+authResult['code'],
			function(result) {
				if (result) {
					if(debug){
						console.log(result);
						console.log("logged! "+result.email+" level:"+result.access_level);
					}
                    user_data.username=result.email;
                    user_data.email=result.email;
					user_data.access_level=result.access_level;
					session_data.user=result.email;
					session_data.user_access_level=result.access_level;
					//if(result.access_level=='admin'){ admin_screen();}
					//else{
						menu_screen();//}
				} else if (authResult['error']) {
					alert('There was an error: ' + authResult['error']);
				} else {
					alert('Failed to make a server-side call. Check your configuration and console.</br>Result:'+ result);
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
				login_screen();
			} else {
				if(!result.hasOwnProperty('error')) result.error="NO JSON RETURNED";
				alert('Failed to disconnect.</br>Result:'+ result.error);
			}
		}
	);

	return false;
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

function show_profile(){
	canvas_zone.innerHTML='\
		  name:'+user_data.display_name+'\
		  email:'+user_data.email+'\
		  type: '+user_data.access_level+'\
		  <button class="button" onclick="end_game()">Back</button>\
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
	alert('user.email: '+user_data.email);
	if(user_data.email==null){
		login_screen();
	}else{
		var sign='<li><a href="#" onclick="hamburger_close();show_profile()">profile</a></li>\
                  <li><a href="#" onclick="hamburger_close();gdisconnect()">sign out</a></li>';
		if(user_data.email=='invitee'){
			sign='<li><a href="#" onclick="hamburger_close();login_screen()">sign in</a></li>';
		}
		// TODO if admin administrar... lo de sujetos puede ir aquí tb...
		hamburger_menu_content.innerHTML=''+user_data.email.substr(0,10)+'<ul>\
		'+sign+'\
	      <li><a href="#" onclick="exit_app()">exit app</a></li>\
		</ul>';
		header_zone.innerHTML='<a id="hamburger_icon" onclick="hamburger_toggle(event)"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
		<path d="M2 6h20v3H2zm0 5h20v3H2zm0 5h20v3H2z"/></svg></a> <span id="header_text" onclick="menu_screen()">'+app_name+'</span> <div id="game_status"> Life: <span id="current_lifes">O O O</span>   Score: <span id="current_score_num">0</span></div>';
        header_text=document.getElementById('header_text');
		// Optionally if(is_app) we could completely remove header...
		canvas_zone_vcentered.innerHTML=' \
		<div id="menu-logo-div"></div> \
		<nav id="responsive_menu">\
		<br /><button id="start-button" class="button" onclick="play_game()" disabled="true">Play</button> \
		<br /><button id="exit" class="button" onclick="options()">Options</button> \
		<br /><button id="exit" class="button exit" onclick="exit_app()">Exit</button> \
		</nav>\
		';
		
		if(json_data_files==undefined){
		    ajax_request_json("backend/search_game_data_files.php?data_source="+data_source,function(json_filenames){
		        //console.log(json_filenames)
		        json_data_files=json_filenames;
		        data_not_loaded_yet=json_filenames.length;
		        for(var i=0;i<json_filenames.length;i++){
		            if(debug) console.log('requesting '+json_filenames[i]);
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
		                    if(user_data.access_level!='invitee'){console.log('logged as admin');}
		                }
		            });
		        }
		    });
		}else{
		    document.getElementById("start-button").disabled=false;
		    if(user_data.access_level!='invitee'){console.log('logged as admin');}
		}
	}
}

var countdown_limit_end_secs=1500;
var silly_cb=function(){
	activity_timer.stop();
	if(debug) console.log("question timeout!!!");
	check_correct("timeout","incorrect");
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
	canvas_zone_vcentered.innerHTML=' \
	<div id="question"></div>\
	<div id="answers"></div>\
	<div id="game-panel">\
        <img src="'+media_objects.images['clock.svg'].src+'"/> &nbsp; <progress id="time_left" value="0" max="'+countdown_limit_end_secs+'"></progress>\
        <button class="button" onclick="end_game()">exit</button>\
    </div>\
	';
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
	/*activity_timer.stop();
	activity_results.type=session_data.type;
	activity_results.mode=session_data.mode;
	activity_results.level=session_data.level;
	activity_results.activity=correct_answer;
	activity_results.timestamp=timestamp_str;
	activity_results.duration=activity_timer.seconds;
	session_data.duration+=activity_timer.seconds;*/
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
		if(session_data.mode!="test"){
			//audio_sprite.playSpriteRange("zfx_wrong"); // add a callback to move forward after the sound plays...
			open_js_modal_content('<div class="js-modal-incorrect"><h1>INCORRECT</h1><br />Correct answer: <b>'+correct_answer+'</b> <br />'+optional_msg+'<br /><button onclick="nextActivity()">OK</button></div>');
		}
	}
	//session_data.details.push(activity_results);
	session_data.num_answered++;
	
	//dom_score_answered.innerHTML=session_data.num_answered;
	var waiting_time=1000;
	if(session_data.mode!="test") waiting_time=20000; 
	show_answer_timeout=setTimeout(function(){nextActivity()}, waiting_time);
}

function nextActivity(){
		alert('implementar q haya 3 vidas y que se vaya anotando el score y guardar los 3 highest scores de cada user con sus fechas.... the competition starts');
	 clearTimeout(show_answer_timeout);
	activity_timer.reset();
	if(session_data.mode!="test") remove_modal();
	if(Math.floor((Math.random() * 10))<2)
		same_country_question(random_item(indicator_list));
	else
		diff_country_question(random_item(indicator_list));
}

var same_country_question=function(indicator){
	// create another class called countdown with 2 callbacks tricker and end
	// setTimeout(function(){nextActivity()}, waiting_time);
	// So that each trick increases a progress bar and on end it stops and fails
	// even in the tricker callback we can set red blink when there are 3 seconds left...
	console.log("question for indicator="+indicator);
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

	correct_answer=period_map[period1];
	answer_msg='<br />'+period_map[period1]+' <b>'+( Number(data_map[indicator].data[period1][country])/Number(data_map[indicator].data[period2][country])  ).toFixed(2)+' times bigger</b> than '+period_map[period2]+'<br />';
	if(data_map[indicator].data[period1][country]==data_map[indicator].data[period2][country]){
		console.log("USLESS: Equal value "+country+" "+indicator+" -- "+period_map[period1]+" ("+data_map[indicator].data[period1][country]+") and "+period_map[period2]+" ("+data_map[indicator].data[period2][country]+")");
        nextActivity(); return;
	}
    if(data_map[indicator].data[period1][country]<data_map[indicator].data[period2][country]){
        correct_answer=period_map[period2];
		answer_msg='<br />'+period_map[period2]+' <b>'+( Number(data_map[indicator].data[period2][country])/Number(data_map[indicator].data[period1][country])  ).toFixed(2)+' times bigger</b> than '+period_map[period1]+'<br />';
    }
	answer_msg+='<br />'+country+' '+indicator+'<b> '+period_map[period2]+'</b> ==> '+Number(data_map[indicator].data[period2][country]).toFixed(2);
	answer_msg+='<br />'+country+' '+indicator+'<b> '+period_map[period1]+'</b> ==> '+Number(data_map[indicator].data[period1][country]).toFixed(2);
    activity_timer.start();
    canvas_zone_question.innerHTML='When was <b>'+country+' '+indicator+'</b> bigger?';
    canvas_zone_answers.innerHTML=' \
    <div id="answer-'+period_map[period2]+'" onclick="check_correct(this.innerHTML,correct_answer,answer_msg)" class="answer aleft">'+period_map[period2]+'</div>\
    <div id="answer-'+period_map[period1]+'" onclick="check_correct(this.innerHTML,correct_answer,answer_msg)" class="answer aright">'+period_map[period1]+'</div>\
    ';
}

var diff_country_question=function(indicator){
	console.log("question for indicator="+indicator);
    var period="last_year";
	var country1=random_item(country_list);
	var country2=random_item(country_list,country1);
    
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
	correct_answer=country1;
	answer_msg='<br />'+country1+' <b>'+( Number(data_map[indicator].data[period][country1])/Number(data_map[indicator].data[period][country2])  ).toFixed(2)+' times bigger</b> than '+country2+'<br />';
	if(data_map[indicator].data[period][country1]==data_map[indicator].data[period][country2]){
		console.log("USLESS: Equal value "+indicator+"  -- "+country1+" ("+data_map[indicator].data[period][country1]+") and "+country2+" ("+data_map[indicator].data[period][country2]+")");
        nextActivity(); return;
	}
    if(data_map[indicator].data[period][country1]<data_map[indicator].data[period][country2]){
        correct_answer=country2;
		answer_msg='<br />'+country2+' <b>'+( Number(data_map[indicator].data[period][country2])/Number(data_map[indicator].data[period][country1])  ).toFixed(2)+' times bigger</b> than '+country1+'<br />';

    }
	answer_msg+='<br />'+period_map[period]+' '+indicator+'<b> '+country1+'</b> ==> '+Number(data_map[indicator].data[period][country1]).toFixed(2);
	answer_msg+='<br />'+period_map[period]+' '+indicator+'<b> '+country2+'</b> ==> '+Number(data_map[indicator].data[period][country2]).toFixed(2);

    activity_timer.start();
    canvas_zone_question.innerHTML='Which is bigger in '+indicator+' ('+period_map[period]+')?';
    canvas_zone_answers.innerHTML='\
    <div id="answer-'+country1+'" onclick="check_correct(this.innerHTML,correct_answer,answer_msg)" class="answer aleft">'+country1+'</div>\
    <div id="answer-'+country2+'" onclick="check_correct(this.innerHTML,correct_answer,answer_msg)" class="answer aright">'+country2+'</div>\
    ';
}


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



