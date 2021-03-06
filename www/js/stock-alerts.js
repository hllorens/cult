"use strict";

var app_name='STOCK';

// MEDIA
var images = [
	"../../cult-media/img/clock.png",
	"../../cult-media/img/clock.svg",
	"../../cult-media/img/correct.png",
	"../../cult-media/img/wrong.png"
];
var sounds = [];
var jsons=[];

var user_data={};
var stocks=undefined;

var media_objects;
var session_state="unset";
var cache_user_alerts=null;
var header_zone=document.getElementById('header');
var header_text=undefined;
var canvas_zone=document.getElementById('zone_canvas');
var canvas_zone_vcentered=document.getElementById('zone_canvas_vcentered');
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

function login_screen(){
	if(debug){alert('login_screen called');}
	header_zone.innerHTML='<h1>Sign in</h1>';
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
					menu_screen();
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
	if(user_data.email!=users_select_elem.options[users_select_elem.selectedIndex].value){
		user_data.email=users_select_elem.options[users_select_elem.selectedIndex].value;
		user_subjects=null;
	}
	menu_screen();
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
	header_zone.innerHTML='<h1>STOCK</h1>';
	if(debug){
		console.log('userAgent: '+navigator.userAgent+' is_app: '+is_app+' Device info: '+device_info);
		console.log('not_loaded sounds: '+ResourceLoader.not_loaded['sounds'].length);
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
		<br /><button id="start-button" class="button" disabled="true">Config alerts</button> \
		<br /><button id="analysis-button" class="button" disabled="true">Analysis</button> \
		<br /><button id="exit_app" class="button exit">Exit</button> \
		</nav>\
		';
        document.getElementById("start-button").addEventListener(clickOrTouch,function(){manage_alerts();});
        document.getElementById("analysis-button").addEventListener(clickOrTouch,function(){show_analysis();});
        document.getElementById("exit_app").addEventListener(clickOrTouch,function(){exit_app();});
		if(cache_user_alerts==null){
			ajax_request_json(
				backend_url+'ajaxdb.php?action=get_alerts&user='+user_data.email, 
				function(data) {
					cache_user_alerts=data;
					menu_screen();
				}
			);
		}else{
			if(stocks==undefined){
				ajax_request_json("../../cult-data-stock-google/stocks.formated.json",function(json_data){
				    if(debug) console.log(json_data)
				    stocks=json_data;
		            document.getElementById("start-button").disabled=false;
		            document.getElementById("analysis-button").disabled=false;
				});
			}else{
				document.getElementById("start-button").disabled=false;
                document.getElementById("analysis-button").disabled=false;
			}
		}
	}
}


var show_analysis=function(){
	preventBackExit();
	header_text.innerHTML=' &larr; '+app_name+' menu';
	canvas_zone_vcentered.innerHTML=' \
	<div id="results-div">loading alerts...</div> \
	<br />\
    <button id="go-back" class="minibutton fixed-bottom-right go-back">&larr;</button> \
	';
    var keysSorted={};
    keysSorted['yield']=get_sorted_stocks('yield');
    //keysSorted['gdppcap']=get_sorted_countries_indicator('gdppcap');
	var user_alerts_data=[];
	for(var i=0;i<keysSorted['yield'].length;i++){
		user_alerts_data.push(stocks[keysSorted['yield'][i]]);
	}    
	if(user_alerts_data.length==0){
		document.getElementById("results-div").innerHTML="nothing...";
	}else{
		document.getElementById("results-div").innerHTML="<table id=\"results-table\"></table>";
		var results_table=document.getElementById("results-table");
		DataTableSimple.call(results_table, {
			data: user_alerts_data,
			pagination: 8,
			row_id: 'id',
			row_id_prefix: 'row-ana',
			columns: [
				{ data: 'name', col_header: 'Sym',  format: 'first_12'},
				{ data: 'value'},
				{ data: 'dividend-total-year', col_header: 'div_y'},
				{ data: 'yield', col_header: 'yield%'},
				{ data: 'per'}
			]
		} );
	}

    document.getElementById("go-back").addEventListener(clickOrTouch,function(){menu_screen();});
}

var get_sorted_stocks=function(indicator,direction){
    if(typeof(direction)=='undefined') direction='desc';
    var keysSorted = Object.keys(stocks).sort(function(a,b){return Number(stocks[b][indicator])-Number(stocks[a][indicator])});
    if(direction=='asc') keysSorted = Object.keys(stocks).sort(function(a,b){return Number(stocks[a][indicator])-Number(stocks[b][indicator])});
    var to_del=['.INX:INDEXSP','SX5E:INDEXSTOXX','NDX:INDEXNASDAQ','IB:INDEXBME'];
    for(var i=0;i<keysSorted.length;i++){
        if(stocks[keysSorted[i]]==null) to_del.push(keysSorted[i]);
    }
    for(var i=0;i<to_del.length;i++){
        keysSorted.splice(keysSorted.indexOf(to_del[i]),1);
    }
    return keysSorted;
}


var manage_alerts=function(){
	preventBackExit();
	header_text.innerHTML=' &larr; '+app_name+' menu';
    var normal_opts='<button id="add-subject" class="button" onclick="add_alert()">Add</button>';
	canvas_zone_vcentered.innerHTML=' \
    '+normal_opts+'\
	<div id="results-div">loading alerts...</div> \
	<br /><select id="symbols" style="display:none;"></select>\
    <button id="go-back" class="minibutton fixed-bottom-right go-back" onclick="menu_screen()">&larr;</button> \
	';
	var sym_select_elem=document.getElementById('symbols');
	select_fill_with_json(stocks,sym_select_elem,'IB:INDEXBME');
	var user_alerts_data=[];
	for(var key in cache_user_alerts){
		if (cache_user_alerts.hasOwnProperty(key)) {
			user_alerts_data.push(cache_user_alerts[key]);
		}
	}    

  
	if(user_alerts_data.length==0){
		document.getElementById("results-div").innerHTML="user: "+user_data.email+"<br />No alerts defined";
	}else{
		document.getElementById("results-div").innerHTML="user: "+user_data.email+"<br /><table id=\"results-table\"></table>";
		var results_table=document.getElementById("results-table");
		DataTableSimple.call(results_table, {
			data: user_alerts_data,
			pagination: 5,
			row_id: 'id',
			row_id_prefix: 'row-alert',
			columns: [
				//{ data: 'id' },
				{ data: 'symbol', col_header: 'Sym',  format: 'first_12', link_function_id: 'edit_alert'},
				{ data: 'low'},
				{ data: 'high'},
				{ data: 'low_change_percentage', col_header: 'lpc'},
				{ data: 'high_change_percentage', col_header: 'hpc'},
				{ data: 'last_alerted_date', col_header: 'last alert'},
				{ data: 'del', col_header: 'Op', link_function_id: 'delete_alert'}
			]
		} );
	}
};



var accept_add_alert=function(){
	var myform=document.getElementById('my-form');
	var myformsubmit=document.getElementById('my-form-submit');
	if (!myform.checkValidity()){
		if(debug) console.log("form error");
		// TODO se puede abstraer merjor...
	    myform.removeEventListener("submit", formValidationSafariSupport);
	    myform.addEventListener("submit", formValidationSafariSupport);
	    myformsubmit.removeEventListener("click", showFormAllErrorMessages);
	    myformsubmit.addEventListener("click", showFormAllErrorMessages);
		myformsubmit.click(); // won't submit (invalid), but show errors
	}else{
		open_js_modal_content('<h1>Adding... '+document.getElementById('new-symbol').value+'</h1>');
        ajax_request_json(
        backend_url+'ajaxdb.php?action=add_alert&user='+user_data.email+'&symbol='+document.getElementById('new-symbol').value+'&low='+document.getElementById('new-low').value+'&high='+document.getElementById('new-high').value+'&low_change_percentage='+document.getElementById('new-low_change_percentage').value+'&high_change_percentage='+document.getElementById('new-high_change_percentage').value+'&low_yield='+document.getElementById('new-low_yield').value+'&high_yield='+document.getElementById('new-high_yield').value,
        function(data) {
            if(data['success']!='undefined'){
                cache_user_alerts[data['success']]=data['data'];
                remove_modal();
                remove_modal("js-modal-window-alert");
                manage_alerts(); // to reload with the new user...
            }else{
                alert("ERROR: "+JSON.stringify(data));
            }
        }
        );
	}
}


var add_alert=function(){
	//var sym_select_elem=document.getElementById('symbols');
    //<select id="new-symbol">'+sym_select_elem.innerHTML+'</select>
	canvas_zone_vcentered.innerHTML='<form id="my-form" action="javascript:void(0);"> \
			<ul class="errorMessages"></ul>\
			<label for="new-symbol">Sym</label> <input id="new-symbol" autofocus type="text" name="q" placeholder="Symbol ..." required="required" /><br /> \
			<label for="new-low">low</label> <input id="new-low" type="text" required="required" /> \
			<label for="new-high">high</label> <input id="new-high" type="text"  required="required"  /><br /> \
			<label for="new-low_change_percentage">change_% low</label> <input id="new-low_change_percentage" type="text" required="required"  /> \
			<label for="new-high_change_percentage">high</label> <input id="new-high_change_percentage" type="text"  required="required" /><br /> \
			<label for="new-low_yield">yield_% low</label> <input id="new-low_yield" type="text" required="required"  /> \
			<label for="new-high_yield">high</label> <input id="new-high_yield" type="text"  required="required" /><br /> \
			<input id="my-form-submit" type="submit" style="visibility:hidden;display:none" />\
			</form><button onclick="accept_add_alert()">Add</button><button onclick="manage_alerts()">Cancel</button>'; //title="Error: yyyy-mm-dd"
        var search_select = new autoComplete({
            selector: '#new-symbol',
            minChars: 1,
            source: function(term, suggest){
                term = term.toLowerCase();
                var choices = objectProperties(stocks);
                var suggestions = [];
                for (var i=0;i<choices.length;i++)
                    if (~choices[i].toLowerCase().indexOf(term)) suggestions.push(choices[i]);
                suggest(suggestions);
            }
        });
}





var edit_alert=function(sid){
	var accept_function=function(){
		var myform=document.getElementById('my-form');
		var myformsubmit=document.getElementById('my-form-submit');
		if (!myform.checkValidity()){
			if(debug) console.log("form error");
			// TODO se puede abstraer merjor...
		    myform.removeEventListener("submit", formValidationSafariSupport);
		    myform.addEventListener("submit", formValidationSafariSupport);
		    myformsubmit.removeEventListener("click", showFormAllErrorMessages);
		    myformsubmit.addEventListener("click", showFormAllErrorMessages);
			myformsubmit.click(); // won't submit (invalid), but show errors
		}else{
			open_js_modal_content('<h1>Updating... '+document.getElementById('new-symbol').value+'</h1>');
            if(user_data.access_level=='invitee'){
                alert('The user not yet activated.');
            }else{
                ajax_request_json(
                backend_url+'ajaxdb.php?action=update_alert&lid='+sid+'&user='+user_data.email+'&symbol='+document.getElementById('new-symbol').value+'&low='+document.getElementById('new-low').value+'&high='+document.getElementById('new-high').value+'&low_change_percentage='+document.getElementById('new-low_change_percentage').value+'&high_change_percentage='+document.getElementById('new-high_change_percentage').value+'&low_yield='+document.getElementById('new-low_yield').value+'&high_yield='+document.getElementById('new-high_yield').value,
                function(data) {
                    if(data['success']!='undefined'){
                        cache_user_alerts[data['success']]=data['data'];
                        remove_modal();
                        remove_modal("js-modal-window-alert");
                        manage_alerts(); // to reload with the new user...
                    }else{
                        alert("ERROR: "+JSON.stringify(data));
                    }
                }
                );
            }
		}
	};
	var cancel_function=function(){ remove_modal("js-modal-window-alert"); };
	var a2edit={}
	for(var key in cache_user_alerts){
		if (cache_user_alerts.hasOwnProperty(key) && cache_user_alerts[key]['id']==sid) {
			a2edit=cache_user_alerts[key];
		}
	}
	var form_html='<form id="my-form" action="javascript:void(0);"> \
			<ul class="errorMessages"></ul>\
			<label>User</label><input type="text" readonly="readonly" value="'+a2edit.user+'" /><br /> \
			<label for="new-symbol">Sym</label><input id="new-symbol" type="text" required="required" readonly="readonly" value="'+a2edit.symbol+'" /><br /> \
			<label for="new-low">low</label><input id="new-low" type="text" required="required" value="'+a2edit.low+'" /> \
			<label for="new-high">high</label><input id="new-high" type="text"  required="required" value="'+a2edit.high+'" /><br /> \
			<label for="new-low_change_percentage">change % low</label><input id="new-low_change_percentage" type="text" required="required" value="'+a2edit.low_change_percentage+'" /> \
			<label for="new-high_change_percentage">high</label><input id="new-high_change_percentage" type="text"  required="required" value="'+a2edit.high_change_percentage+'" /><br /> \
			<label for="new-low_yield">yield_% low</label> <input id="new-low_yield" type="text" required="required"  value="'+a2edit.low_yield+'" /> \
			<label for="new-high_yield">high</label> <input id="new-high_yield" type="text"  required="required"  value="'+a2edit.high_yield+'" /><br /> \
			<input id="my-form-submit" type="submit" style="visibility:hidden;display:none" />\
			</form>'; //title="Error: yyyy-mm-dd"		
	open_js_modal_alert("Editar Participante",form_html,accept_function,cancel_function);
};



var delete_alert=function(sid){
	var a2edit={}
	for(var key in cache_user_alerts){
		if (cache_user_alerts.hasOwnProperty(key) && cache_user_alerts[key]['id']==sid) {
			a2edit=cache_user_alerts[key];
		}
	}
	var accept_function=function(){
        open_js_modal_content('<h1>Deleting... </h1>');
        if(user_data.access_level=='invitee'){
            alert('The user not yet activated.');
        }else{
            ajax_request_json(
            backend_url+'ajaxdb.php?action=delete_alert&lid='+sid+'&symbol='+a2edit.symbol,
            function(data) {
                if(data['success']!='undefined'){
                    delete cache_user_alerts[data['success']];
                    remove_modal();
                    remove_modal("js-modal-window-alert");
                    manage_alerts(); // to reload with the new user...
                }else{
                    alert("ERROR: "+JSON.stringify(data));
                }
            }
            );
        }
	};
	var cancel_function=function(){ remove_modal("js-modal-window-alert"); };

	var form_html='¿Delete '+a2edit.symbol+'?'; //title="Error: yyyy-mm-dd"		
	open_js_modal_alert("Delete Alert",form_html,accept_function,cancel_function);
};




