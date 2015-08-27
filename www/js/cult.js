



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

var session_state="unset";
var header_zone=document.getElementById('header');
var canvas_zone=document.getElementById('zone_canvas');
var canvas_zone_vcentered=document.getElementById('zone_canvas_vcentered');
var canvas_zone_answers;

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
						alert("logged! "+result.email+" level:"+result.access_level);
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
	alert("under construction");
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

	if(user_data.email==null){
		login_screen();
	}else{
		var sign='<li><a href="#" onclick="hamburguer_close();show_profile()">profile</a></li>\
                  <li><a href="#" onclick="hamburguer_close();gdisconnect()">sign out</a></li>';
		if(user_data.email=='invitee'){
			sign='<li><a href="#" onclick="hamburguer_close();login_screen()">sign in</a></li>';
		}
		// TODO if admin administrar... lo de sujetos puede ir aquí tb...
		hamburger_menu_content.innerHTML=''+user_data.email.substr(0,10)+'<ul>\
		'+sign+'\
	      <li><a href="#" onclick="exit_app()">exit app</a></li>\
		</ul>';
		header_zone.innerHTML='<a id="hamburger_icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
		<path d="M2 6h20v3H2zm0 5h20v3H2zm0 5h20v3H2z"/></svg></a> CULT';
		var hamburger_icon=document.getElementById('hamburger_icon');
		hamburger_icon.addEventListener('click', function(e) {
			hamburger_menu.classList.toggle('open');
			e.stopPropagation();
		});
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
		            console.log('requesting '+json_filenames[i]);
		            ajax_request_json(json_filenames[i],function(json){
		                data_map[json.indicator]=json; 
		                indicator_list.push(json.indicator);
		                console.log(json.indicator);
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

var play_game=function(){
	canvas_zone_vcentered.innerHTML=' \
		<div id="zone_score" class="cf">\
		  <div class="col_left">\
		    <div id="activity_timer_div">\
		      Tiempo : <span id="activity_timer_span">00:00:00</span>\
		    </div>\
			<div id="current_score">\
		Correct : <span id="current_score_num">0</span>\
		</div>\
		  </div>\
		  <div class="col_right">\
		    <div id="remaining_activities">\
		      remaining activs : <span id="remaining_activities_num">0</span>\
		    </div>\
		    <div id="current_answered">\
		      Answered : <span id="current_answered_num">0</span>\
		    </div>\
		  </div>\
		</div> <!-- /#zone_score -->\
	<div id="answers"></div>\
	';
	//get elements
	dom_score_correct=document.getElementById('current_score_num');
	dom_score_answered=document.getElementById('current_answered_num');
	canvas_zone_answers=document.getElementById('answers');
	activity_timer.anchor_to_dom(document.getElementById('activity_timer_span'));
	console.log("let's go, there are "+json_data_files.length+" indicator files. Countries in 'population' = "+country_list.length);
	activity_timer.reset();
	same_country_question(random_item(indicator_list));
}

function check_correct(clicked_answer,correct_answer){
	activity_timer.stop();
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
			//dom_score_correct.innerHTML=session_data.num_correct;
			open_js_modal_content('<div class="js-modal-img"><img src="'+media_objects.images['correct.png'].src+'"/></div>');
		}
	}else{
		activity_results.result="incorrect";
		if(session_data.mode!="test"){
			//audio_sprite.playSpriteRange("zfx_wrong"); // add a callback to move forward after the sound plays...
			open_js_modal_content('<div class="js-modal-img"><img src="'+media_objects.images['wrong.png'].src+'"/></div>');
		}
	}
	//session_data.details.push(activity_results);
	session_data.num_answered++;
	
	//dom_score_answered.innerHTML=session_data.num_answered;
	var waiting_time=500;
	if(session_data.mode!="test") waiting_time=1000; // fire next activity after 2 seconds (time for displaying img and playing the sound)
	setTimeout(function(){nextActivity()}, waiting_time);
}

function nextActivity(){
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
	var country=random_item(country_list);
	var period=random_item(period_list,"last_year");
	//data_map[indicator].data.last_year.hasOwnProperty(country)
	console.log("question for indicator="+indicator);
	correct_answer=period_map['last_year'];
	if(data_map[indicator].data.last_year[country]==data_map[indicator].data[period][country]){
		same_country_question(indicator);
		console.log("Equal value "+country+" last_year and "+data[period]);
	}
	if(data_map[indicator].data.last_year[country]<data_map[indicator].data[period][country]){
		correct_answer=period_map[period];		
	}
	activity_timer.start();
	canvas_zone_answers.innerHTML=' \
	When was <b>'+country+' '+indicator+'</b> bigger?\
	<div id="answer-'+period_map[period]+'" onclick="check_correct(this.innerHTML,correct_answer)" class="answer aleft">'+period_map[period]+'</div>\
	<div id="answer-'+period_map['last_year']+'" onclick="check_correct(this.innerHTML,correct_answer)" class="answer aright">'+period_map['last_year']+'</div>\
	NEED A BUTTON TO STOP GAME, TIMER, ETC...\
	';	
}

var diff_country_question=function(indicator){
	var country1=random_item(country_list);
	var country2=random_item(country_list,country1);
	correct_answer=country1;
	if(data_map[indicator].data.last_year[country1]==data_map[indicator].data.last_year[country2]){
		diff_country_question(indicator);
		console.log("Equal value "+country1+" and "+country2);
	}
	if(data_map[indicator].data.last_year[country1]<data_map[indicator].data.last_year[country2]){
		correct_answer=country2;
	}
	activity_timer.start();
	canvas_zone_answers.innerHTML=' \
	Which is bigger in '+indicator+'?\
	<div id="answer-'+country1+'" onclick="check_correct(this.innerHTML,correct_answer)" class="answer aleft">'+country1+'</div>\
	<div id="answer-'+country2+'" onclick="check_correct(this.innerHTML,correct_answer)" class="answer aright">'+country2+'</div>\
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



