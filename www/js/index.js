// Check for media needs
if(typeof images === 'undefined'){var images = [];}
if(typeof sounds === 'undefined'){var sounds = [];}

// Check for debug mode
var QueryString=get_query_string();
var debug=false;
if(QueryString.hasOwnProperty('debug') && QueryString.debug=='true') debug=true;

// variables
var exit_url="http://www.cognitionis.com";
var backend_url='backend/' //../backend


var exit_app=function(){
		if(is_app){
			navigator.app.exitApp();
		}else{
			location.href = exit_url;
		}
}

var is_app=is_cordova();
if(is_app){
	document.addEventListener('deviceready', onDeviceReady, false);
}else{
	onDeviceReady();
}


function onDeviceReady() {
	device_info="browser"
	if(is_app){
		device_info = 'name='     + device.name     + '-' + 
                        'PhoneGap=' + device.phonegap + '-' + 
                        'Platform=' + device.platform + '-' + 
                        'UUID='     + device.uuid     + '-' + 
                        'Ver='  + device.version
		
	}
	splash_screen();
}

function splash_screen(){
	// the default index.html might contain splash screen directly (more efficient)
	ResourceLoader.load_media(images,sounds,menu_screen,false,debug);
}

// this will prevent things not being loaded
// this will run after all resources are loaded (even those loaded from js)
// NOTE: even ajax calls in menu_screen are waited for...
window.onload = function () { 
	if(debug) console.log("win.onload");
	var splash=document.getElementById("splash_screen");
	if(splash!=null && (ResourceLoader.lazy_audio==false || ResourceLoader.not_loaded['sounds'].length==0)){ splash.parentNode.removeChild(splash); }
}


