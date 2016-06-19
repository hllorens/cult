"use strict";

var app_name='CULT';

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
/* encoded in the data file var period_correspondence={ previous_year:-1, last_lustrum:-4,	last_decade:-9,	last_2decade:-19};*/

var backend_url='backend/' //../backend
if(is_local()){backend_url='http://www.centroafan.com/dev-afan-app/www/backend/';}
// MEDIA
var images = [
//	"../../cult-media/img/clock.png or svg",
	"../../cult-media/img/correct.png",
	"../../cult-media/img/wrong.png"
];
var sounds = [];
// More efficient for offline scenario to use require or dirctly iclude the data
var jsons=[]; // "../../cult-data-game-unified/all_wb.json"
var offline_jsons=[];
offline_jsons["all_wb.json"]={"debtgdp":{"indicator":"debtgdp","indicator_sf":"debt\/GDP (%)","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":null,"World":null,"South Africa":null},"previous_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":"97.1502383249555","World":null,"South Africa":null},"previous_year2":{"Afghanistan":null,"Argentina":null,"Australia":"38.3391837878014","Belgium":"101.545211554842","Brazil":null,"Canada":"48.8438602800039","Switzerland":null,"China":null,"Germany":"52.3337185641055","Denmark":"46.5686630516547","Egypt":null,"Spain":"96.4723345683963","European Union":"83.4110019234184","Finland":"53.6881667924417","France":"88.5245669280178","United Kingdom":"98.2639835522543","Greece":"181.929564213094","Indonesia":null,"India":null,"Italy":"134.653501652462","Japan":"201.129282191416","South Korea":null,"Mexico":null,"Netherlands":"68.2729078737726","Norway":"20.9531344652195","Pakistan":null,"Poland":"55.5915720253257","Portugal":"138.046002848182","Russian Federation":"13.6024832877687","Saudi Arabia":null,"Sweden":"41.9805889213771","Turkey":"38.01021442591","United States":"96.1572103809548","World":null,"South Africa":null},"previous_year3":{"Afghanistan":null,"Argentina":null,"Australia":"40.4096305062822","Belgium":"103.813920679618","Brazil":null,"Canada":"52.1085850587693","Switzerland":null,"China":null,"Germany":"55.2278518690605","Denmark":"49.3711360584193","Egypt":null,"Spain":"83.4509462112741","European Union":"61.8821120081487","Finland":"52.4653015871427","France":"88.9239643514465","United Kingdom":"101.950921593814","Greece":"165.477789379429","Indonesia":"25.0343541103907","India":"50.3113681181844","Italy":"126.925184181358","Japan":"195.563178579126","South Korea":null,"Mexico":null,"Netherlands":"68.536372147237","Norway":"20.774933832635","Pakistan":null,"Poland":"55.155028385652","Portugal":"133.881608750281","Russian Federation":"9.35240881935417","Saudi Arabia":null,"Sweden":"42.0213308727746","Turkey":"43.5257380946991","United States":"94.3288124758788","World":null,"South Africa":null},"last_lustrum":{"Afghanistan":null,"Argentina":null,"Australia":"30.5135780024755","Belgium":"94.9144792790299","Brazil":null,"Canada":"51.8874426981933","Switzerland":"24.1914956028948","China":null,"Germany":"53.3842374737341","Denmark":"49.9151850874112","Egypt":null,"Spain":"61.8320218457735","European Union":"57.6081296597538","Finland":"47.8912373202485","France":"79.483451529755","United Kingdom":"97.506607059056","Greece":"111.113002795702","Indonesia":"24.8651867947499","India":"44.5965450599918","Italy":"108.257073843767","Japan":"189.527377687929","South Korea":null,"Mexico":null,"Netherlands":"62.4229113945708","Norway":"20.2795657407862","Pakistan":null,"Poland":"51.5774402080486","Portugal":"102.410174533787","Russian Federation":"9.26863863028677","Saudi Arabia":null,"Sweden":"42.8164646881496","Turkey":"45.5186859009073","United States":"90.1621260469988","World":null,"South Africa":null},"last_decade":{"Afghanistan":null,"Argentina":null,"Australia":"21.6548331898462","Belgium":null,"Brazil":null,"Canada":null,"Switzerland":"33.707064070497","China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":"33.7246779841544","European Union":null,"Finland":null,"France":null,"United Kingdom":"45.8053366168546","Greece":null,"Indonesia":null,"India":"59.1099377543981","Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":"42.6931774396939","Norway":"48.1881559415559","Pakistan":null,"Poland":"46.793914424681","Portugal":null,"Russian Federation":"9.89107334166946","Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":"55.2941842181172","World":null,"South Africa":null},"last_2decade":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":"21.7024877945298","China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":"61.0846090985098","European Union":null,"Finland":null,"France":null,"United Kingdom":"47.2607666403587","Greece":null,"Indonesia":"23.923142554795","India":"44.9352806704411","Italy":null,"Japan":null,"South Korea":"6.93982497887893","Mexico":"26.0254438773894","Netherlands":"67.7728443354724","Norway":"23.5714553368536","Pakistan":null,"Poland":"43.8258430717803","Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":"37.9207517149446","United States":"46.5855092779056","World":null,"South Africa":"48.9129342676567"}}},"employed":{"indicator":"employed","indicator_sf":"employed %","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":null,"World":null,"South Africa":null},"previous_year":{"Afghanistan":"43.4000015258789","Argentina":"55.9000015258789","Australia":"61.2000007629395","Belgium":"48.7999992370605","Brazil":"65","Canada":"61.5","Switzerland":"65","China":"68","Germany":"56.9000015258789","Denmark":"58.2999992370605","Egypt":"42.7999992370605","Spain":"44.4000015258789","European Union":"51.660333363443","Finland":"54.2999992370605","France":"50.2000007629395","United Kingdom":"58.2000007629395","Greece":"39.0999984741211","Indonesia":"63.5","India":"52.2000007629395","Italy":"43.0999984741211","Japan":"56.9000015258789","South Korea":"58.7999992370605","Mexico":"58.5999984741211","Netherlands":"59.7000007629395","Norway":"62.5999984741211","Pakistan":"51.7000007629395","Poland":"51.2999992370605","Portugal":"51.7000007629395","Russian Federation":"60.5","Saudi Arabia":"52.0999984741211","Sweden":"58.9000015258789","Turkey":"44.7999992370605","United States":"58.5","World":"59.7128230542405","South Africa":"39.4000015258789"},"previous_year2":{"Afghanistan":"43.5","Argentina":"56.5","Australia":"61.5","Belgium":"48.7999992370605","Brazil":"65.3000030517578","Canada":"61.5","Switzerland":"65.1999969482422","China":"68","Germany":"56.7000007629395","Denmark":"58.0999984741211","Egypt":"42.5999984741211","Spain":"43.5","European Union":"51.2888563070402","Finland":"54.9000015258789","France":"50.0999984741211","United Kingdom":"57.4000015258789","Greece":"38.7000007629395","Indonesia":"63.5","India":"52.2000007629395","Italy":"43.0999984741211","Japan":"56.7999992370605","South Korea":"59.0999984741211","Mexico":"58.5","Netherlands":"60.0999984741211","Norway":"62.5999984741211","Pakistan":"51.5999984741211","Poland":"50.7000007629395","Portugal":"50.4000015258789","Russian Federation":"60.2000007629395","Saudi Arabia":"51.7999992370605","Sweden":"58.9000015258789","Turkey":"45.0999984741211","United States":"57.7999992370605","World":"59.6403013584504","South Africa":"39.2999992370605"},"previous_year3":{"Afghanistan":"43.7999992370605","Argentina":"56.2999992370605","Australia":"61.9000015258789","Belgium":"49","Brazil":"65.5999984741211","Canada":"61.5","Switzerland":"65.3000030517578","China":"68","Germany":"56.5999984741211","Denmark":"58.5","Egypt":"42.7999992370605","Spain":"44.5","European Union":"51.5460345489712","Finland":"55.5","France":"50.5999984741211","United Kingdom":"57.0999984741211","Greece":"40.2999992370605","Indonesia":"63.7000007629395","India":"52.2000007629395","Italy":"43.7999992370605","Japan":"56.2999992370605","South Korea":"58.9000015258789","Mexico":"58.5","Netherlands":"61.2999992370605","Norway":"63.4000015258789","Pakistan":"51.5","Poland":"50.7999992370605","Portugal":"51.5","Russian Federation":"60","Saudi Arabia":"49.2999992370605","Sweden":"58.9000015258789","Turkey":"44.9000015258789","United States":"57.7000007629395","World":"59.6100031141377","South Africa":"38.7999992370605"},"last_lustrum":{"Afghanistan":"43.7000007629395","Argentina":"56.2000007629395","Australia":"62.2999992370605","Belgium":"49.2000007629395","Brazil":"65.3000030517578","Canada":"61.5","Switzerland":"65.3000030517578","China":"67.9000015258789","Germany":"56.2999992370605","Denmark":"59.0999984741211","Egypt":"42.9000015258789","Spain":"46.4000015258789","European Union":"51.81573704329","Finland":"55.5999984741211","France":"50.7999992370605","United Kingdom":"57","Greece":"44","Indonesia":"63.2999992370605","India":"52.7999992370605","Italy":"44.0999984741211","Japan":"56.5","South Korea":"58.5","Mexico":"57.0999984741211","Netherlands":"61.5","Norway":"63.2000007629395","Pakistan":"51.2999992370605","Poland":"50.7999992370605","Portugal":"53.5999984741211","Russian Federation":"59.2999992370605","Saudi Arabia":"48.7999992370605","Sweden":"58.7999992370605","Turkey":"44.5999984741211","United States":"57.2999992370605","World":"59.5780409318811","South Africa":"38.5999984741211"},"last_decade":{"Afghanistan":"43.7000007629395","Argentina":"55.9000015258789","Australia":"61.7999992370605","Belgium":"48.7000007629395","Brazil":"64","Canada":"62.4000015258789","Switzerland":"64.6999969482422","China":"69.6999969482422","Germany":"52.7000007629395","Denmark":"63.7000007629395","Egypt":"41.7999992370605","Spain":"52.5999984741211","European Union":"52.3549904604233","Finland":"56.7999992370605","France":"50.9000015258789","United Kingdom":"59","Greece":"48.7000007629395","Indonesia":"60.7000007629395","India":"57.0999984741211","Italy":"45.7000007629395","Japan":"57.9000015258789","South Korea":"59.2999992370605","Mexico":"58.7999992370605","Netherlands":"62.2999992370605","Norway":"63.0999984741211","Pakistan":"50.2999992370605","Poland":"46.5999984741211","Portugal":"57.5999984741211","Russian Federation":"57.7000007629395","Saudi Arabia":"47.7000007629395","Sweden":"59.4000015258789","Turkey":"41.2000007629395","United States":"62.0999984741211","World":"60.7096938637737","South Africa":"42.4000015258789"},"last_2decade":{"Afghanistan":"44.2999992370605","Argentina":"48","Australia":"58.2000007629395","Belgium":"45.5999984741211","Brazil":"62.5999984741211","Canada":"58.0999984741211","Switzerland":"65.0999984741211","China":"74.8000030517578","Germany":"53.0999984741211","Denmark":"61","Egypt":"41.9000015258789","Spain":"39.5","European Union":"50.2739511951336","Finland":"52","France":"48.7000007629395","United Kingdom":"56.4000015258789","Greece":"46.7000007629395","Indonesia":"64.5999984741211","India":"57.9000015258789","Italy":"41.5999984741211","Japan":"61.4000015258789","South Korea":"60.7999992370605","Mexico":"56.2999992370605","Netherlands":"55.2999992370605","Norway":"61","Pakistan":"47.0999984741211","Poland":"50.5999984741211","Portugal":"54.5999984741211","Russian Federation":"53.5999984741211","Saudi Arabia":"48.5999984741211","Sweden":"56.7000007629395","Turkey":"49.5","United States":"62.2000007629395","World":"61.7049005654536","South Africa":"43.7999992370605"}}},"extdebt":{"indicator":"extdebt","indicator_sf":"external debt","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":null,"World":null,"South Africa":null},"previous_year":{"Afghanistan":"2554678000","Argentina":null,"Australia":null,"Belgium":null,"Brazil":"556871157000","Canada":null,"Switzerland":null,"China":"959509815000","Germany":null,"Denmark":null,"Egypt":"39623992000","Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":"293397401000","India":"463230464350.4","Italy":null,"Japan":null,"South Korea":null,"Mexico":"432602236000","Netherlands":null,"Norway":null,"Pakistan":"62184234000","Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":"408202751000","United States":null,"World":null,"South Africa":"144005733000"},"previous_year2":{"Afghanistan":"2576820000","Argentina":null,"Australia":null,"Belgium":null,"Brazil":"483814083000","Canada":null,"Switzerland":null,"China":"870848286000","Germany":null,"Denmark":null,"Egypt":"44444146000","Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":"266133750000","India":"429742425138.5","Italy":null,"Japan":null,"South Korea":null,"Mexico":"406042143000","Netherlands":null,"Norway":null,"Pakistan":"60045292000","Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":"389385574000","United States":null,"World":null,"South Africa":"139244973000"},"previous_year3":{"Afghanistan":"2708631000","Argentina":null,"Australia":null,"Belgium":null,"Brazil":"440512741000","Canada":null,"Switzerland":null,"China":"750745640000","Germany":null,"Denmark":null,"Egypt":"39996714000","Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":"252555372000","India":"395071134090.6","Italy":null,"Japan":null,"South Korea":null,"Mexico":"348945146000","Netherlands":null,"Norway":null,"Pakistan":"62143613000","Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":"336961273000","United States":null,"World":null,"South Africa":"144958642000"},"last_lustrum":{"Afghanistan":"2627442000","Argentina":null,"Australia":null,"Belgium":null,"Brazil":"404046105000","Canada":null,"Switzerland":null,"China":"710233988000","Germany":null,"Denmark":null,"Egypt":"35145102000","Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":"219619132000","India":"336845285775.1","Italy":null,"Japan":null,"South Korea":null,"Mexico":"291833290000","Netherlands":null,"Norway":null,"Pakistan":"65520332000","Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":"305135381000","United States":null,"World":null,"South Africa":"116929482000"},"last_decade":{"Afghanistan":"969197000","Argentina":null,"Australia":null,"Belgium":null,"Brazil":"194303020000","Canada":null,"Switzerland":null,"China":"320800407000","Germany":null,"Denmark":null,"Egypt":"30648521000","Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":"135959441000","India":"159525527066","Italy":null,"Japan":null,"South Korea":null,"Mexico":"170608370000","Netherlands":null,"Norway":null,"Pakistan":"37174387000","Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":"209593623000","United States":null,"World":null,"South Africa":"59381328000"},"last_2decade":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":"181381552000","Canada":null,"Switzerland":null,"China":"128817086000","Germany":null,"Denmark":null,"Egypt":"31484019000","Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":"128988714000","India":"93966072270","Italy":null,"Japan":null,"South Korea":null,"Mexico":"156339589000","Netherlands":null,"Norway":null,"Pakistan":"29768436000","Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":"79829428000","United States":null,"World":null,"South Africa":"26050001000"}}},"gdp":{"indicator":"gdp","indicator_sf":"GDP (total)","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":null,"World":null,"South Africa":null},"previous_year":{"Afghanistan":"20038215159.3873","Argentina":"537659972702.092","Australia":"1454675479665.84","Belgium":"531546586178.579","Brazil":"2416635506076.31","Canada":"1785386649602.19","Switzerland":"701037135966.049","China":"10354831729340.4","Germany":"3868291231823.77","Denmark":"342362478767.505","Egypt":"301498960051.639","Spain":"1381342101735.68","European Union":"18514155872554.5","Finland":"272216575502.251","France":"2829192039171.84","United Kingdom":"2988893283565.2","Greece":"235574074998.314","Indonesia":"888538201025.345","India":"2048517438873.54","Italy":"2141161325367.43","Japan":"4601461206885.08","South Korea":"1410382988616.48","Mexico":"1294689733233.03","Netherlands":"879319321494.639","Norway":"499817138323.195","Pakistan":"243631917866.478","Poland":"544966555714.059","Portugal":"230116912513.587","Russian Federation":"1860597922763.44","Saudi Arabia":"753831733333.333","Sweden":"571090480171.001","Turkey":"798429233036.326","United States":"17419000000000","World":"77960606594141.6","South Africa":"350140810003.32"},"previous_year2":{"Afghanistan":"20458939155.2669","Argentina":"614383517369.503","Australia":"1563950959269.52","Belgium":"521402393365.011","Brazil":"2465773850934.56","Canada":"1838964175409.41","Switzerland":"684919206141.128","China":"9490602600148.49","Germany":"3745317149399.13","Denmark":"335877548363.831","Egypt":"286011230726.274","Spain":"1369261671179","European Union":"17987465273840.3","Finland":"269190106004.86","France":"2810249215589.07","United Kingdom":"2712296271989.99","Greece":"239509850570.447","Indonesia":"910478729099.036","India":"1861801615477.86","Italy":"2133539300229.7","Japan":"4919563108372.5","South Korea":"1305604981271.91","Mexico":"1258773797056.06","Netherlands":"864169242952.925","Norway":"522349106382.979","Pakistan":"231086513914.872","Poland":"524059039422.894","Portugal":"226073492966.495","Russian Federation":"2079024782973.32","Saudi Arabia":"744335733333.333","Sweden":"578742001487.571","Turkey":"823242587404.139","United States":"16768053000000","World":"76338906961137.8","South Africa":"366243783486.353"},"previous_year3":{"Afghanistan":"20536542736.7297","Argentina":"604378456915.58","Australia":"1537477830480.51","Belgium":"497780014247.465","Brazil":"2460658440428.04","Canada":"1832715597431.65","Switzerland":"665408300271.743","China":"8461623162714.07","Germany":"3539615377794.51","Denmark":"322276544469.312","Egypt":"276353323880.224","Spain":"1339946773437.24","European Union":"17248798723694.4","Finland":"256706466091.089","France":"2681416108537.39","United Kingdom":"2630472981169.65","Greece":"245670666639.047","Indonesia":"917869913364.916","India":"1831781515472.09","Italy":"2074631555455.23","Japan":"5954476603961.52","South Korea":"1222807195712.49","Mexico":"1184499844413.23","Netherlands":"828946812396.788","Norway":"509704856037.817","Pakistan":"224646134571.4","Poland":"500227851988.331","Portugal":"216368178659.447","Russian Federation":"2016112133645.48","Saudi Arabia":"733955733333.333","Sweden":"543880647757.404","Turkey":"788863301670.379","United States":"16163158000000","World":"74221375713047.3","South Africa":"397386418270.402"},"last_lustrum":{"Afghanistan":"17930239399.8149","Argentina":"557890203658.125","Australia":"1389919156068.22","Belgium":"526975257158.743","Brazil":"2614573170731.71","Canada":"1788796361798.89","Switzerland":"696311671959.459","China":"7492432097810.11","Germany":"3757464553794.83","Denmark":"341498686832.939","Egypt":"236001858960.015","Spain":"1487924659438.42","European Union":"18323191387490","Finland":"273657214345.288","France":"2862502085070.89","United Kingdom":"2594904662714.31","Greece":"287779921184.32","Indonesia":"892969104529.574","India":"1835814449585.35","Italy":"2278089156658.33","Japan":"5905632338015.46","South Korea":"1202463682633.85","Mexico":"1169362160456.56","Netherlands":"893701695857.659","Norway":"498157406416.158","Pakistan":"213755282058.719","Poland":"528742068313.757","Portugal":"244879869335.557","Russian Federation":"1904793932483.16","Saudi Arabia":"669506666666.667","Sweden":"563113421113.421","Turkey":"774754155283.582","United States":"15517926000000","World":"72660255490971.2","South Africa":"416596716626.957"},"last_decade":{"Afghanistan":"7057598406.61553","Argentina":"262666517346.674","Australia":"746880802635.52","Belgium":"409813072387.404","Brazil":"1107640325472.35","Canada":"1310752820874.47","Switzerland":"429195591242.622","China":"2729784031906.09","Germany":"3002446368084.31","Denmark":"282961088316.405","Egypt":"107484034870.974","Spain":"1264551499184.54","European Union":"15296026866695.6","Finland":"216552502822.732","France":"2325011918203.49","United Kingdom":"2588077276908.92","Greece":"273317737046.795","Indonesia":"364570515631.492","India":"949116769619.215","Italy":"1943530341613.35","Japan":"4356750212598.01","South Korea":"1011797457138.5","Mexico":"965281191371.844","Netherlands":"726649102998.369","Norway":"345424664369.357","Pakistan":"137264061106.043","Poland":"343261472028.873","Portugal":"208566948939.907","Russian Federation":"989930542278.695","Saudi Arabia":"376900133511.348","Sweden":"420032121655.688","Turkey":"530900094644.732","United States":"13855888000000","World":"51034732180263.8","South Africa":"271638630111.497"},"last_2decade":{"Afghanistan":null,"Argentina":"272149750000","Australia":"401335356600.91","Belgium":"281357654723.127","Brazil":"850425828275.793","Canada":"626950495049.505","Switzerland":"329619351051.78","China":"860844098049.121","Germany":"2503665193657.4","Denmark":"187632400365.599","Egypt":"67629716981.1321","Spain":"640998292394.588","European Union":"9734072986476.57","Finland":"132099404607.818","France":"1614245416078.98","United Kingdom":"1306575663026.52","Greece":"145861612825.595","Indonesia":"227369671349.161","India":"399787263892.645","Italy":"1309407282846.03","Japan":"4706187126019.61","South Korea":"603413139412.021","Mexico":"397404138184.313","Netherlands":"445704575163.399","Norway":"163517783497.163","Pakistan":"63320122807.1223","Poland":"157079211268.128","Portugal":"122629812841.175","Russian Federation":"391719993756.828","Saudi Arabia":"157743124165.554","Sweden":"288103936773.039","Turkey":"181475555282.555","United States":"8100201000000","World":"31294714994480.6","South Africa":"147608050636.15"}}},"gdpgrowth":{"indicator":"gdpgrowth","indicator_sf":"GDP growth (%)","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":null,"World":null,"South Africa":null},"previous_year":{"Afghanistan":"1.31253090783336","Argentina":"0.45360547903708","Australia":"2.4998512224341","Belgium":"1.34836095620594","Brazil":"0.103371355621704","Canada":"2.43930610758747","Switzerland":"1.88947694383224","China":"7.26846092873832","Germany":"1.59977039232734","Denmark":"1.08841956497687","Egypt":"2.22879101389299","Spain":"1.36077061211743","European Union":"1.39506611446387","Finland":"-0.404700297243537","France":"0.179566376852165","United Kingdom":"2.94025323447028","Greece":"0.653982658187189","Indonesia":"5.02466495739111","India":"7.28625323936163","Italy":"-0.443878226180374","Japan":"-0.104499999999959","South Korea":"3.31014756530605","Mexico":"2.23079466200883","Netherlands":"1.01112840751658","Norway":"2.2085956650366","Pakistan":"4.73835494419686","Poland":"3.33404885709557","Portugal":"0.905797853815812","Russian Federation":"0.640485764962648","Saudi Arabia":"3.63869904904455","Sweden":"2.33183303363889","Turkey":"2.91414289451455","United States":"2.38819999999969","World":"2.4935898003192","South Africa":"1.54870063532459"},"previous_year2":{"Afghanistan":"1.95912289917285","Argentina":"2.88535188297459","Australia":"2.44004906206246","Belgium":"0.0165548679284626","Brazil":"3.01514051431029","Canada":"2.00350823345686","Switzerland":"1.7688305613223","China":"7.68380996955","Germany":"0.29784758571904","Denmark":"-0.48620958465817","Egypt":"2.10672461668477","Spain":"-1.67197184815122","European Union":"0.242754987765565","Finland":"-1.12119594233849","France":"0.656485763257052","United Kingdom":"2.15990387108103","Greece":"-3.19823963560829","Indonesia":"5.57921116782603","India":"6.89921723326125","Italy":"-1.74741298505894","Japan":"1.61336690630259","South Korea":"2.89622005439183","Mexico":"1.39489958858312","Netherlands":"-0.495377914664218","Norway":"0.742155986206512","Pakistan":"4.36724945081146","Poland":"1.26471806004203","Portugal":"-1.13015523300638","Russian Federation":"1.3407976142551","Saudi Arabia":"2.66991139463894","Sweden":"1.24120491730943","Turkey":"4.19250925855945","United States":"2.21930802533575","World":"2.36800398855215","South Africa":"2.21235443137809"},"previous_year3":{"Afghanistan":"14.4347412879524","Argentina":"0.801760179229973","Australia":"3.63272030319823","Belgium":"0.151259665907872","Brazil":"1.91545861810047","Canada":"1.92286357507182","Switzerland":"1.1246293105261","China":"7.75029759317401","Germany":"0.405170675144007","Denmark":"-0.655226089002142","Egypt":"2.19387835353777","Spain":"-2.62030851235816","European Union":"-0.435807259531757","Finland":"-1.42618935959564","France":"0.18269303354748","United Kingdom":"1.17905612305687","Greece":"-7.30049393532073","Indonesia":"6.03005065305615","India":"5.08141792467411","Italy":"-2.81793859650334","Japan":"1.75368977535797","South Korea":"2.29238242636897","Mexico":"4.03888599848953","Netherlands":"-1.05703740393571","Norway":"2.74876878288389","Pakistan":"3.50703342009689","Poland":"1.56171330939314","Portugal":"-4.0282567482528","Russian Federation":"3.40554680352253","Saudi Arabia":"5.3844659496954","Sweden":"-0.286320615444041","Turkey":"2.12746061865543","United States":"2.32108445977606","World":"2.26013394384749","South Africa":"2.21982400625758"},"last_lustrum":{"Afghanistan":"6.11368516942299","Argentina":"8.38645145563733","Australia":"2.37956133560841","Belgium":"1.7968378886938","Brazil":"3.91025535209002","Canada":"2.96010247438441","Switzerland":"1.80455391510743","China":"9.4845062015219","Germany":"3.66000015503516","Denmark":"1.15214252475052","Egypt":"1.81664664517112","Spain":"-1.0000804875138","European Union":"1.78821607956468","Finland":"2.57081774452163","France":"2.07922917455807","United Kingdom":"1.97239856307874","Greece":"-9.13249415322949","Indonesia":"6.1697842077098","India":"6.63835345019071","Italy":"0.586819402557765","Japan":"-0.452724839090976","South Korea":"3.68170466650844","Mexico":"3.9195839325612","Netherlands":"1.6636263443925","Norway":"0.968779709886419","Pakistan":"2.74840254954","Poland":"5.00851176058328","Portugal":"-1.82685235062658","Russian Federation":"4.26417656571128","Saudi Arabia":"9.95893280986544","Sweden":"2.66440795069538","Turkey":"8.77274761512783","United States":"1.60145467247139","World":"2.84730173293717","South Africa":"3.21245175505393"},"last_decade":{"Afghanistan":"5.55413762257501","Argentina":"8.40286856609245","Australia":"2.9828702853226","Belgium":"2.49946444651674","Brazil":"3.96050202907195","Canada":"2.6217816877294","Switzerland":"4.01278550769901","China":"12.6882251044697","Germany":"3.70015957205484","Denmark":"3.79674260857645","Egypt":"6.84296058053815","Spain":"4.17411911565355","European Union":"3.34481800446864","Finland":"4.05519744386183","France":"2.37494689958575","United Kingdom":"2.66182255294851","Greece":"5.6524337201578","Indonesia":"5.50095178520269","India":"9.26395889780731","Italy":"2.00640492621345","Japan":"1.69290424492254","South Korea":"5.176133981787","Mexico":"4.94451434826912","Netherlands":"3.51863696135297","Norway":"2.3950924655358","Pakistan":"6.17754203617736","Poland":"6.19272709735522","Portugal":"1.55305210138437","Russian Federation":"8.15343197288394","Saudi Arabia":"5.57674743458874","Sweden":"4.68812715248721","Turkey":"6.89348933743625","United States":"2.666625826122","World":"4.08584426407003","South Africa":"5.58504596151144"},"last_2decade":{"Afghanistan":null,"Argentina":"5.52668982269249","Australia":"3.94914072165129","Belgium":"1.5931570573082","Brazil":"2.20753552431761","Canada":"1.67960446104939","Switzerland":"0.601093571481528","China":"9.92472266262089","Germany":"0.817897617014211","Denmark":"2.90013212887818","Egypt":"4.98873054399816","Spain":"2.67463906729668","European Union":"2.01791237748883","Finland":"3.65883306320907","France":"1.3880040284493","United Kingdom":"2.66781264553228","Greece":"2.86212892093837","Indonesia":"7.64278628425994","India":"7.54952224818398","Italy":"1.28684172613217","Japan":"2.61005462852573","South Korea":"7.18591656580236","Mexico":"5.87476668289335","Netherlands":"3.56671923564109","Norway":"5.02799544492025","Pakistan":"4.84658128374571","Poland":"6.2389167858055","Portugal":"3.49668438515253","Russian Federation":"-3.60000000025506","Saudi Arabia":"3.38381343115093","Sweden":"1.51786123912999","Turkey":"7.37966447593668","United States":"3.79588122942587","World":"3.30266724733028","South Africa":"4.29999999677185"}}},"gdppcap":{"indicator":"gdppcap","indicator_sf":"GDP per capita","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Netherlands":null,"Norway":null,"Pakistan":null,"Mexico":null,"South Korea":null,"Italy":null,"Japan":null,"Poland":null,"Portugal":null,"United States":null,"World":null,"South Africa":null,"Turkey":null,"Sweden":null,"Russian Federation":null,"Saudi Arabia":null,"India":null,"Indonesia":null,"Canada":null,"Switzerland":null,"China":null,"Brazil":null,"Belgium":null,"Argentina":null,"Australia":null,"Germany":null,"Denmark":null,"France":null,"United Kingdom":null,"Greece":null,"Finland":null,"European Union":null,"Egypt":null,"Spain":null,"Afghanistan":null},"previous_year":{"Norway":"97299.636068076","Switzerland":"85616.5611964835","Australia":"61979.8962947626","Denmark":"60718.3926958809","Sweden":"58898.9275256779","United States":"54629.4951678912","Netherlands":"52138.6839244096","Canada":"50230.8076901423","Finland":"49842.7130622895","Germany":"47773.9441928693","Belgium":"47327.6204608156","United Kingdom":"46296.9846725052","France":"42725.7394894127","European Union":"36447.8550869944","Japan":"36194.4156134427","Italy":"35222.7606011111","Spain":"29721.6014855933","South Korea":"27970.4951469293","Saudi Arabia":"24406.4764554706","Portugal":"22124.3669649875","Greece":"21672.6717735205","Poland":"14336.7977208633","Russian Federation":"12735.9184024927","Argentina":"12509.5311180615","Brazil":"11726.8058803488","World":"10738.831492412","Turkey":"10515.007820334","Mexico":"10325.6460658759","China":"7590.01644054361","South Africa":"6483.85457472843","Indonesia":"3491.92971737141","Egypt":"3365.70742057477","India":"1581.51070308774","Pakistan":"1316.61410969738","Afghanistan":"633.56924695192"},"previous_year2":{"Norway":"102832.258689863","Switzerland":"84669.2929367996","Australia":"67652.6832146189","Sweden":"60283.24522267","Denmark":"59818.6315281878","United States":"52980.0436263119","Canada":"52309.4317452131","Netherlands":"51425.0789882648","Finland":"49492.8280573718","Belgium":"46625.3175174923","Germany":"45600.7745095204","France":"42627.6524386524","United Kingdom":"42294.8901157814","Japan":"38633.7080591795","Italy":"35420.8776125666","European Union":"35408.0972608488","Spain":"29370.6638674201","South Korea":"25997.8810547699","Saudi Arabia":"24646.0208730264","Greece":"21842.7033068901","Portugal":"21618.7353389663","Russian Federation":"14487.2798702588","Argentina":"14443.0656513599","Poland":"13776.4547643996","Brazil":"12071.7779871352","Turkey":"10975.0749291962","World":"10638.9882644136","Mexico":"10172.7225491296","China":"6991.85386564447","South Africa":"6889.78699871556","Indonesia":"3623.53235988707","Egypt":"3264.45006267526","India":"1455.10219142081","Pakistan":"1275.36364756698","Afghanistan":"666.795051096452"},"previous_year3":{"Norway":"101563.702677597","Switzerland":"83208.68654235","Australia":"67646.1038529626","Denmark":"57636.1253095393","Sweden":"57134.0770682404","Canada":"52737.7771646779","United States":"51456.6587280353","Netherlands":"49474.705606422","Finland":"47415.5598711351","Japan":"46679.2654322303","Belgium":"44731.2194794638","Germany":"44010.9313869814","United Kingdom":"41294.5148008666","France":"40850.3523734948","Italy":"34844.4980928484","European Union":"34150.7109182227","Spain":"28647.8352426892","Saudi Arabia":"24883.1897146534","South Korea":"24453.9719124644","Greece":"22242.681934771","Portugal":"20577.4026375899","Argentina":"14357.4115893903","Russian Federation":"14078.8305693118","Poland":"13142.0459946086","Brazil":"12157.3082176473","Turkey":"10646.0355326161","World":"10470.3415127663","Mexico":"9703.37101717816","South Africa":"7592.15799699268","China":"6264.64387793993","Indonesia":"3700.52353809447","Egypt":"3226.13137881998","India":"1449.66487452505","Pakistan":"1266.38075811451","Afghanistan":"690.842629014956"},"last_lustrum":{"Norway":"100575.117263444","Switzerland":"88002.6095703805","Australia":"62216.5471294133","Denmark":"61304.0612046553","Sweden":"59593.6847982389","Netherlands":"53537.2751512189","Canada":"52086.533524627","Finland":"50787.5649828531","United States":"49781.357490134","Belgium":"47699.8070518961","Japan":"46203.7095189836","Germany":"45936.0812598523","France":"43807.4759032413","United Kingdom":"41020.3769643089","Italy":"38364.9426699518","European Union":"36245.7525996838","Spain":"31832.2380807085","Greece":"25914.6815458943","South Korea":"24155.8298493082","Saudi Arabia":"23256.0956126438","Portugal":"23194.7409567701","Poland":"13891.14168806","Argentina":"13392.9169036445","Russian Federation":"13323.8833754364","Brazil":"13039.1216499582","Turkey":"10584.1639636414","World":"10369.8674733486","Mexico":"9715.11259636147","South Africa":"8080.86524339041","China":"5574.18709336902","Indonesia":"3647.62662181143","Egypt":"2816.66694347778","India":"1471.65843924074","Pakistan":"1230.81542756809","Afghanistan":"622.379654358451"},"last_decade":{"Norway":"74114.697150083","Switzerland":"57348.9278823975","Denmark":"52041.0029728889","United States":"46437.0671173065","Sweden":"46256.4716010495","Netherlands":"44453.9711946212","United Kingdom":"42534.3062613449","Finland":"41120.6765061581","Canada":"40243.5522837141","Belgium":"38852.3610339939","France":"36544.5085344191","Germany":"36447.8723183195","Australia":"36084.8589777475","Japan":"34075.9789494111","Italy":"33426.1668196693","European Union":"30710.3198507635","Spain":"28482.6094833461","Greece":"24801.1578065035","South Korea":"20917.0302377023","Portugal":"19821.4446268632","Saudi Arabia":"14826.9166983811","Poland":"8999.73962660636","Mexico":"8666.33535331064","World":"7738.31179458083","Turkey":"7727.27240453727","Russian Federation":"6920.19439783608","Argentina":"6639.90943461746","Brazil":"5808.34054715979","South Africa":"5660.11697380201","China":"2082.18336250102","Indonesia":"1590.17790597325","Egypt":"1409.17787522982","Pakistan":"876.95110885413","India":"816.733776198888","Afghanistan":"280.245644106914"},"last_2decade":{"Switzerland":"46610.0597512362","Japan":"37422.8641429074","Norway":"37321.4433901355","Denmark":"35650.7243420099","Sweden":"32587.2641044641","Germany":"30564.2478058387","United States":"30068.2309182833","Netherlands":"28698.6660159512","Belgium":"27701.8519735545","France":"27015.2589591084","Finland":"25777.6412996396","Italy":"23028.5053084073","United Kingdom":"22462.5094323584","Australia":"21917.7192180061","Canada":"21129.4354271046","European Union":"20087.5805474967","Spain":"16236.7716792911","Greece":"13749.1151520996","South Korea":"13254.6374001395","Portugal":"12185.0638930534","Saudi Arabia":"8159.9806741278","Argentina":"7683.57384790824","World":"5406.31997621019","Brazil":"5144.64365955867","Mexico":"4131.80570593217","Poland":"4066.84202921958","South Africa":"3690.17847905164","Turkey":"3052.49811884532","Russian Federation":"2643.89769649787","Indonesia":"1137.26564794368","Egypt":"1063.43341585838","China":"707.029771302305","Pakistan":"503.749451985561","India":"408.241774685944","Afghanistan":null}}},"inflation":{"indicator":"inflation","indicator_sf":"inflation","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":"-1.53384658328173","Argentina":null,"Australia":"1.5083667216592","Belgium":"0.560593980466415","Brazil":"9.02723977356148","Canada":"1.12524136094334","Switzerland":"-1.14391506884691","China":"1.44255538382248","Germany":"0.234429944519196","Denmark":"0.452034153691609","Egypt":"10.3574896480911","Spain":"-0.501190518268432","European Union":"-0.0453857351842024","Finland":"-0.2071643707866","France":"0.0378037334443199","United Kingdom":"0.0500208420171881","Greece":"-1.73590236566741","Indonesia":"6.3631211311561","India":"5.87242659466756","Italy":"0.0387867504463516","Japan":"0.786380218889009","South Korea":"0.7061628760088","Mexico":"2.72064126256974","Netherlands":"0.600248147278815","Norway":"2.1736483195324","Pakistan":"2.53951590878071","Poland":"-0.991300366300363","Portugal":"0.487271927236811","Russian Federation":"15.5253283302064","Saudi Arabia":"2.18463706835827","Sweden":"-0.0467847449833748","Turkey":"7.6708536484588","United States":"0.118627135552317","World":"1.52852915194218","South Africa":"4.58827104223609"},"previous_year":{"Afghanistan":"4.60433400900534","Argentina":null,"Australia":"2.48792270531402","Belgium":"0.340000000000007","Brazil":"6.33209234205231","Canada":"1.90663590717818","Switzerland":"-0.0131860440706863","China":"1.99684708355259","Germany":"0.906797035167657","Denmark":"0.564020540449505","Egypt":"10.1458005507475","Spain":"-0.147031810368282","European Union":"0.220566200354005","Finland":"1.04120000617985","France":"0.507700672785821","United Kingdom":"1.46019160885392","Greece":"-1.31224241067529","Indonesia":"6.39492540819923","India":"6.35319454414932","Italy":"0.241057542767966","Japan":"2.74885464389837","South Korea":"1.27240642704561","Mexico":"4.01861720191058","Netherlands":"0.976035079699608","Norway":"2.02509628525307","Pakistan":"7.19167116470147","Poland":"0.106951871658061","Portugal":"-0.278153367467859","Russian Federation":"7.81289506953224","Saudi Arabia":"2.67052555416665","Sweden":"-0.179638494114759","Turkey":"8.85457271364333","United States":"1.62222297740851","World":"2.59878905796066","South Africa":"6.37525900888742"},"previous_year2":{"Afghanistan":"7.6543165673232","Argentina":"10.6194330100695","Australia":"2.44988864142539","Belgium":"1.11393855643004","Brazil":"6.20189961255349","Canada":"0.938291897815499","Switzerland":"-0.2173196618342","China":"2.62711864406717","Germany":"1.50472226668734","Denmark":"0.789071780078061","Egypt":"9.42157654022033","Spain":"1.4086282854704","European Union":"1.38768994636635","Finland":"1.47828813293557","France":"0.863606929999857","United Kingdom":"2.55454668654317","Greece":"-0.921271918055054","Indonesia":"6.41338677822315","India":"10.9076433121022","Italy":"1.21999212908335","Japan":"0.359471660257854","South Korea":"1.30786601430184","Mexico":"3.80638982343198","Netherlands":"2.50689852657885","Norway":"2.13170917396269","Pakistan":"7.6895036551002","Poland":"1.03426983636865","Portugal":"0.274416666667","Russian Federation":"6.77645788336934","Saudi Arabia":"3.50626361655801","Sweden":"-0.0442929701484368","Turkey":"7.49309030547683","United States":"1.46483265562683","World":"2.68385738718509","South Africa":"5.44527948193558"},"previous_year3":{"Afghanistan":"7.21825776057512","Argentina":"10.0302590249304","Australia":"1.76278015613196","Belgium":"2.83966343445893","Brazil":"5.40196474982358","Canada":"1.5156782312455","Switzerland":"-0.692544620825857","China":"2.62492093611669","Germany":"2.00849118223384","Denmark":"2.39791485664639","Egypt":"7.11815561959625","Spain":"2.44591462840482","European Union":"2.71876904402473","Finland":"2.80833232604085","France":"1.95568550044072","United Kingdom":"2.82170974709121","Greece":"1.50151979452967","Indonesia":"4.2795119590945","India":"9.31244560487363","Italy":"3.04136253041364","Japan":"-0.033428046130773","South Korea":"2.19230769230769","Mexico":"4.11150854557474","Netherlands":"2.45554765291609","Norway":"0.709219858156017","Pakistan":"9.68505340962508","Poland":"3.55686995444071","Portugal":"2.77333854051581","Russian Federation":"5.07801418439715","Saudi Arabia":"2.88596245446876","Sweden":"0.888377506923492","Turkey":"8.89156996512183","United States":"2.0693372652606","World":"3.78250023985417","South Africa":"5.65358300324086"},"last_lustrum":{"Afghanistan":"10.2016601415825","Argentina":"9.46568627451001","Australia":"3.30385015608742","Belgium":"3.53208210722741","Brazil":"6.63619865692984","Canada":"2.91213508872295","Switzerland":"0.231346209987862","China":"5.41085005784499","Germany":"2.07517293107789","Denmark":"2.75868226051246","Egypt":"10.0539169045357","Spain":"3.19624297191142","European Union":"3.3096597846384","Finland":"3.41680903370978","France":"2.11748680870384","United Kingdom":"4.48423964475475","Greece":"3.32987017363469","Indonesia":"5.35749960388808","India":"8.85784529680106","Italy":"2.74143821348254","Japan":"-0.283333333333303","South Korea":"4","Mexico":"3.40737961671249","Netherlands":"2.34107017751366","Norway":"1.30097087378643","Pakistan":"11.9167694652057","Poland":"4.25833333333301","Portugal":"3.6530110043073","Russian Federation":"8.4281759458628","Saudi Arabia":"5.82359105588522","Sweden":"2.96115073822149","Turkey":"6.47187967115079","United States":"3.15684156862221","World":"4.99551018479139","South Africa":"4.99551018479139"},"last_decade":{"Afghanistan":"7.25489556090272","Argentina":"10.9011245348844","Australia":"3.53848733858798","Belgium":"1.79094071005309","Brazil":"4.18368053152467","Canada":"2.00202539534157","Switzerland":"1.05877758998626","China":"1.46318904320046","Germany":"1.57742924103114","Denmark":"1.89007333484537","Egypt":"7.64452644526446","Spain":"3.51580473658775","European Union":"2.60196916518754","Finland":"1.56666666666664","France":"1.6837264500819","United Kingdom":"2.3335277939828","Greece":"3.1959459698068","Indonesia":"13.1094152835923","India":"6.1455223880597","Italy":"2.06978661049542","Japan":"0.240663900414279","South Korea":"2.24172558306922","Mexico":"3.62946322575935","Netherlands":"1.16765305950397","Norway":"2.3321503585138","Pakistan":"7.92108440058785","Poland":"1.11494394480717","Portugal":"2.74331509593894","Russian Federation":"9.68710888610764","Saudi Arabia":"2.207346665551","Sweden":"1.36021468627677","Turkey":"9.59724212288414","United States":"3.22594410070408","World":"4.45662303701559","South Africa":"4.64162489421264"},"last_2decade":{"Afghanistan":null,"Argentina":"0.155695900742301","Australia":"2.61241970021413","Belgium":"2.05891932722997","Brazil":"15.7574360967243","Canada":"1.57053112507139","Switzerland":"0.818824340630962","China":"8.32401506091792","Germany":"1.44606103619587","Denmark":"2.11136023916288","Egypt":"7.18710369720018","Spain":"3.5585065811347","European Union":"3.33960207441314","Finland":"0.616615282060184","France":"2.00477822790072","United Kingdom":"2.48110098856377","Greece":"8.1962194841968","Indonesia":"7.96848016949892","India":"8.9771490750816","Italy":"3.97452428385338","Japan":"0.131871754719204","South Korea":"4.92342922570487","Mexico":"34.3776581888577","Netherlands":"2.01666666666699","Norway":"1.25869418891632","Pakistan":"10.3738085885004","Poland":"19.8172212329473","Portugal":"3.12069756769158","Russian Federation":"47.7416666666667","Saudi Arabia":"1.22206956395951","Sweden":"0.470973017170886","Turkey":"80.3469027803627","United States":"2.93120419993459","World":"6.95008597032137","South Africa":"7.35412590631834"}}},"laborforce":{"indicator":"laborforce","indicator_sf":"labor force (% of population)","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":null,"World":null,"South Africa":null},"previous_year":{"Afghanistan":"47.7999992370605","Argentina":"60.9000015258789","Australia":"65.0999984741211","Belgium":"53.2999992370605","Brazil":"69.6999969482422","Canada":"66.0999984741211","Switzerland":"68.0999984741211","China":"71.4000015258789","Germany":"59.9000015258789","Denmark":"62.4000015258789","Egypt":"49.2999992370605","Spain":"59","European Union":"57.5278613361361","Finland":"59.4000015258789","France":"55.7000007629395","United Kingdom":"62.0999984741211","Greece":"53.0999984741211","Indonesia":"67.6999969482422","India":"54.2000007629395","Italy":"49.2000007629395","Japan":"59","South Korea":"61","Mexico":"61.5999984741211","Netherlands":"64.1999969482422","Norway":"64.8000030517578","Pakistan":"54.5","Poland":"56.5","Portugal":"60.2999992370605","Russian Federation":"63.7999992370605","Saudi Arabia":"55.2000007629395","Sweden":"64","Turkey":"49.4000015258789","United States":"62.4000015258789","World":"63.4956879140316","South Africa":"52.5"},"previous_year2":{"Afghanistan":"47.9000015258789","Argentina":"60.7999992370605","Australia":"65.1999969482422","Belgium":"53.2999992370605","Brazil":"69.8000030517578","Canada":"66.1999969482422","Switzerland":"68.1999969482422","China":"71.3000030517578","Germany":"59.9000015258789","Denmark":"62.5","Egypt":"49.0999984741211","Spain":"59","European Union":"57.5513532862469","Finland":"59.7999992370605","France":"55.9000015258789","United Kingdom":"62.0999984741211","Greece":"53.2000007629395","Indonesia":"67.6999969482422","India":"54.2000007629395","Italy":"49.0999984741211","Japan":"59.2000007629395","South Korea":"61","Mexico":"61.5999984741211","Netherlands":"64.4000015258789","Norway":"64.9000015258789","Pakistan":"54.4000015258789","Poland":"56.5","Portugal":"60.2999992370605","Russian Federation":"63.7000007629395","Saudi Arabia":"54.9000015258789","Sweden":"64.0999984741211","Turkey":"49.4000015258789","United States":"62.5","World":"63.4582773590027","South Africa":"52.0999984741211"},"previous_year3":{"Afghanistan":"47.9000015258789","Argentina":"60.7000007629395","Australia":"65.3000030517578","Belgium":"53","Brazil":"69.9000015258789","Canada":"66.3000030517578","Switzerland":"68.0999984741211","China":"71.0999984741211","Germany":"59.7999992370605","Denmark":"63.2999992370605","Egypt":"49","Spain":"59.4000015258789","European Union":"57.5791696946649","Finland":"60.0999984741211","France":"56.0999984741211","United Kingdom":"62.0999984741211","Greece":"53.2000007629395","Indonesia":"67.8000030517578","India":"54.0999984741211","Italy":"49","Japan":"58.9000015258789","South Korea":"60.7999992370605","Mexico":"61.5999984741211","Netherlands":"64.6999969482422","Norway":"65.5","Pakistan":"54.2000007629395","Poland":"56.5","Portugal":"61.0999984741211","Russian Federation":"63.5","Saudi Arabia":"52.2000007629395","Sweden":"64.0999984741211","Turkey":"49.4000015258789","United States":"62.9000015258789","World":"63.3788291143562","South Africa":"51.7999992370605"},"last_lustrum":{"Afghanistan":"48","Argentina":"60.5999984741211","Australia":"65.5999984741211","Belgium":"53","Brazil":"69.9000015258789","Canada":"66.4000015258789","Switzerland":"68.0999984741211","China":"71","Germany":"59.9000015258789","Denmark":"64","Egypt":"48.7999992370605","Spain":"59.2999992370605","European Union":"57.3422861039762","Finland":"60.2999992370605","France":"56","United Kingdom":"61.9000015258789","Greece":"53.4000015258789","Indonesia":"67.8000030517578","India":"54.7999992370605","Italy":"48.0999984741211","Japan":"59.0999984741211","South Korea":"60.5","Mexico":"60.2999992370605","Netherlands":"64.3000030517578","Norway":"65.4000015258789","Pakistan":"54.0999984741211","Poland":"56.0999984741211","Portugal":"61.2999992370605","Russian Federation":"63.4000015258789","Saudi Arabia":"51.7999992370605","Sweden":"63.9000015258789","Turkey":"49.5","United States":"63","World":"63.4007089233942","South Africa":"51.2999992370605"},"last_decade":{"Afghanistan":"47.9000015258789","Argentina":"62.0999984741211","Australia":"64.9000015258789","Belgium":"53","Brazil":"69.9000015258789","Canada":"66.5999984741211","Switzerland":"67.4000015258789","China":"72.5999984741211","Germany":"58.7999992370605","Denmark":"66.3000030517578","Egypt":"46.7000007629395","Spain":"57.5999984741211","European Union":"57.0503289815813","Finland":"61.5","France":"55.7999992370605","United Kingdom":"62.4000015258789","Greece":"53.5","Indonesia":"67.6999969482422","India":"59.7000007629395","Italy":"49","Japan":"60.4000015258789","South Korea":"61.4000015258789","Mexico":"60.7000007629395","Netherlands":"64.8000030517578","Norway":"65.4000015258789","Pakistan":"53.5999984741211","Poland":"54.0999984741211","Portugal":"62.4000015258789","Russian Federation":"62.0999984741211","Saudi Arabia":"51","Sweden":"63.9000015258789","Turkey":"45.9000015258789","United States":"65.0999984741211","World":"64.518237180675","South Africa":"54.7999992370605"},"last_2decade":{"Afghanistan":"48.4000015258789","Argentina":"57.9000015258789","Australia":"63.5999984741211","Belgium":"50.4000015258789","Brazil":"67.1999969482422","Canada":"64.1999969482422","Switzerland":"67.5999984741211","China":"78.4000015258789","Germany":"58.2999992370605","Denmark":"65.4000015258789","Egypt":"46.0999984741211","Spain":"50.9000015258789","European Union":"56.2942312986894","Finland":"60.7000007629395","France":"55.5999984741211","United Kingdom":"61.5","Greece":"51.7000007629395","Indonesia":"67.5999984741211","India":"60.2999992370605","Italy":"47.2000007629395","Japan":"63.5999984741211","South Korea":"62.0999984741211","Mexico":"59.4000015258789","Netherlands":"59.0999984741211","Norway":"64.0999984741211","Pakistan":"49.7000007629395","Poland":"57.7999992370605","Portugal":"58.5999984741211","Russian Federation":"59.4000015258789","Saudi Arabia":"51.7000007629395","Sweden":"63.0999984741211","Turkey":"53","United States":"65.9000015258789","World":"65.7758346617467","South Africa":"55.4000015258789"}}},"lifeexpect":{"indicator":"lifeexpect","indicator_sf":"life expectancy (years)","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":"60.7171707317073","Argentina":"76.3342195121951","Australia":"82.5000975609756","Belgium":"80.8941219512195","Brazil":"74.6758780487805","Canada":"82.1376341463415","Switzerland":"83.0193902439025","China":"75.9863414634146","Germany":"81.0168536585366","Denmark":"80.3502195121951","Egypt":"71.3169512195122","Spain":"82.6592682926829","European Union":"80.671346510145","Finland":"80.9225365853659","France":"82.2557804878049","United Kingdom":"80.7821219512195","Greece":"81.0118780487805","Indonesia":"69.0716829268293","India":"68.3485609756098","Italy":"83.1988780487805","Japan":"83.560756097561","South Korea":"81.9199024390244","Mexico":"76.9206829268293","Netherlands":"81.6163414634146","Norway":"81.6550243902439","Pakistan":"66.3769756097561","Poland":"77.484756097561","Portugal":"81.0190731707317","Russian Federation":"70.1384390243902","Saudi Arabia":"74.4933414634146","Sweden":"82.2832195121951","Turkey":"75.4262195121951","United States":"79.1583658536586","World":"71.6002114751043","South Africa":"57.4409024390244"},"previous_year":{"Afghanistan":"60.3744634146342","Argentina":"76.1586097560976","Australia":"82.2512195121951","Belgium":"80.5878048780488","Brazil":"74.4018780487805","Canada":"81.9566097560976","Switzerland":"82.8487804878049","China":"75.7822682926829","Germany":"80.8439024390244","Denmark":"80.5487804878049","Egypt":"71.1217073170732","Spain":"83.0780487804878","European Union":"80.6728746791272","Finland":"81.1292682926829","France":"82.3731707317073","United Kingdom":"81.0560975609756","Greece":"81.2853658536585","Indonesia":"68.8884878048781","India":"68.0138048780488","Italy":"82.690243902439","Japan":"83.5878048780488","South Korea":"82.1558536585366","Mexico":"76.7218536585366","Netherlands":"81.3048780487805","Norway":"81.7512195121951","Pakistan":"66.1833658536585","Poland":"77.2536585365854","Portugal":"80.7219512195122","Russian Federation":"70.3658536585366","Saudi Arabia":"74.3372195121951","Sweden":"81.9560975609756","Turkey":"75.163512195122","United States":"78.9414634146341","World":"71.451745029252","South Africa":"57.1821219512195"},"previous_year2":{"Afghanistan":"60.0282682926829","Argentina":"75.9860975609756","Australia":"82.1487804878049","Belgium":"80.5878048780488","Brazil":"74.1224390243903","Canada":"81.7650487804878","Switzerland":"82.7975609756098","China":"75.5851463414634","Germany":"80.8439024390244","Denmark":"80.3","Egypt":"70.9257804878049","Spain":"83.0780487804878","European Union":"80.5893841257885","Finland":"80.9756097560976","France":"82.219512195122","United Kingdom":"81.0048780487805","Greece":"81.2853658536585","Indonesia":"68.7046097560976","India":"67.6604146341463","Italy":"82.690243902439","Japan":"83.3319512195122","South Korea":"81.7051219512195","Mexico":"76.5326585365854","Netherlands":"81.3048780487805","Norway":"81.7512195121951","Pakistan":"65.9636829268293","Poland":"77","Portugal":"80.7219512195122","Russian Federation":"70.3658536585366","Saudi Arabia":"74.1776341463415","Sweden":"81.9560975609756","Turkey":"74.9007073170732","United States":"78.8414634146341","World":"71.2413271509867","South Africa":"56.7365853658537"},"previous_year3":{"Afghanistan":"59.6796097560976","Argentina":"75.816243902439","Australia":"82.0463414634146","Belgium":"80.3853658536585","Brazil":"73.8395853658537","Canada":"81.5624390243903","Switzerland":"82.6975609756098","China":"75.3929268292683","Germany":"80.8926829268293","Denmark":"80.0512195121951","Egypt":"70.7291463414634","Spain":"82.4268292682927","European Union":"80.3077951552454","Finland":"80.6268292682927","France":"81.9682926829268","United Kingdom":"80.9048780487805","Greece":"80.6341463414634","Indonesia":"68.5195609756098","India":"67.2898780487805","Italy":"82.2390243902439","Japan":"83.0960975609756","South Korea":"81.2134146341464","Mexico":"76.3540975609756","Netherlands":"81.1048780487805","Norway":"81.4512195121951","Pakistan":"65.7168780487805","Poland":"76.7463414634146","Portugal":"80.3731707317073","Russian Federation":"70.3658536585366","Saudi Arabia":"74.0160243902439","Sweden":"81.7048780487805","Turkey":"74.6368048780488","United States":"78.7414634146342","World":"71.0038175222626","South Africa":"56.0983170731707"},"last_lustrum":{"Afghanistan":"59.3279512195122","Argentina":"75.6490487804878","Australia":"81.8951219512195","Belgium":"80.5853658536585","Brazil":"73.5523414634146","Canada":"81.3492926829268","Switzerland":"82.6951219512195","China":"75.2021707317073","Germany":"80.7414634146342","Denmark":"79.8","Egypt":"70.5333170731707","Spain":"82.4756097560976","European Union":"80.2896981674923","Finland":"80.4707317073171","France":"82.1146341463415","United Kingdom":"80.9512195121951","Greece":"80.7317073170732","Indonesia":"68.3343902439024","India":"66.9041707317073","Italy":"82.1878048780488","Japan":"82.5912195121951","South Korea":"80.9670731707317","Mexico":"76.1856585365854","Netherlands":"81.2048780487805","Norway":"81.2951219512195","Pakistan":"65.447","Poland":"76.6951219512195","Portugal":"80.4707317073171","Russian Federation":"69.6585365853659","Saudi Arabia":"73.8548536585366","Sweden":"81.8024390243903","Turkey":"74.3673414634146","United States":"78.6414634146341","World":"70.7646840545267","South Africa":"55.2956585365854"},"last_decade":{"Afghanistan":"57.4325609756098","Argentina":"74.8499024390244","Australia":"81.0414634146342","Belgium":"79.380487804878","Brazil":"72.1317317073171","Canada":"80.4187804878049","Switzerland":"81.490243902439","China":"74.0717073170732","Germany":"79.1317073170732","Denmark":"78.0951219512195","Egypt":"69.6031951219512","Spain":"80.8219512195122","European Union":"78.7377506814038","Finland":"79.2146341463415","France":"80.8121951219512","United Kingdom":"79.2487804878049","Greece":"79.4390243902439","Indonesia":"67.3674878048781","India":"64.9080975609756","Italy":"81.2829268292683","Japan":"82.3219512195122","South Korea":"78.9692682926829","Mexico":"75.4387804878049","Netherlands":"79.6975609756098","Norway":"80.3439024390244","Pakistan":"64.0993902439024","Poland":"75.1439024390244","Portugal":"78.419512195122","Russian Federation":"66.6431707317073","Saudi Arabia":"73.2581707317073","Sweden":"80.7487804878049","Turkey":"72.8285853658537","United States":"77.6878048780488","World":"69.3391314288129","South Africa":"51.6137073170732"},"last_2decade":{"Afghanistan":"53.6020487804878","Argentina":"72.8873414634146","Australia":"78.0780487804878","Belgium":"77.1873170731707","Brazil":"68.1097317073171","Canada":"78.2304878048781","Switzerland":"78.8960975609756","China":"70.1998292682927","Germany":"76.6731707317073","Denmark":"75.5914634146342","Egypt":"67.2320975609756","Spain":"78.1204878048781","European Union":"76.0655494161561","Finland":"76.6934146341464","France":"77.9536585365854","United Kingdom":"77.0878048780488","Greece":"77.6853658536585","Indonesia":"65.3058292682927","India":"60.9156097560976","Italy":"78.5219512195122","Japan":"80.200243902439","South Korea":"73.8312195121951","Mexico":"73.0989024390244","Netherlands":"77.4356097560976","Norway":"78.1504878048781","Pakistan":"61.7479512195122","Poland":"72.2463414634146","Portugal":"75.2609756097561","Russian Federation":"66.1941463414634","Saudi Arabia":"71.4432926829268","Sweden":"78.9590243902439","Turkey":"67.5862195121951","United States":"76.0268292682927","World":"66.5646009352431","South Africa":"60.6054634146341"}}},"p15to64":{"indicator":"p15to64","indicator_sf":"population % aged 15-64","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":"53.488960806863","Argentina":"63.8792489212978","Australia":"66.257035710291","Belgium":"64.8307418795963","Brazil":"69.1276790167069","Canada":"67.8921634982731","Switzerland":"67.1827980001116","China":"73.2171929730555","Germany":"65.8735239308132","Denmark":"64.1595524918413","Egypt":"61.6194586699029","Spain":"66.3288553181877","European Union":"65.3055076682496","Finland":"63.1844856787288","France":"62.3959557946308","United Kingdom":"64.4654096116544","Greece":"64.001114781101","Indonesia":"67.1338836940274","India":"65.5957625041251","Italy":"63.8797421672762","Japan":"60.8024906891831","South Korea":"72.8803810771421","Mexico":"65.9275745154059","Netherlands":"65.2462589355619","Norway":"65.6985354157875","Pakistan":"60.4977812503398","Poland":"69.5203957630148","Portugal":"65.1552111668212","Russian Federation":"69.8822387917186","Saudi Arabia":"68.5556815880295","Sweden":"62.7731014069742","Turkey":"66.790794681757","United States":"66.2635851599661","World":"65.617165917161","South Africa":"65.7274089681035"},"previous_year":{"Afghanistan":"52.6943193054805","Argentina":"63.8445891121611","Australia":"66.5641056164049","Belgium":"65.1067642634872","Brazil":"68.8976228785098","Canada":"68.2721516335672","Switzerland":"67.3992553994863","China":"73.6124934587106","Germany":"65.9524789878048","Denmark":"64.4331871350984","Egypt":"61.8467281694608","Spain":"66.6855434218403","European Union":"65.6485744783625","Finland":"63.8685976867178","France":"62.824437496531","United Kingdom":"64.7990587730262","Greece":"64.4928444599868","Indonesia":"66.908614307883","India":"65.2988987360354","Italy":"64.2001334466948","Japan":"61.3553653609314","South Korea":"73.0110081596383","Mexico":"65.6272403597622","Netherlands":"65.5935017862203","Norway":"65.8898750381218","Pakistan":"60.3201441194461","Poland":"70.0983951982982","Portugal":"65.4010255189624","Russian Federation":"70.4181334884293","Saudi Arabia":"68.3805747777875","Sweden":"63.3042836073327","Turkey":"66.666769430823","United States":"66.5529106629393","World":"65.6700388716765","South Africa":"65.4458256763218"},"previous_year2":{"Afghanistan":"51.9203291778701","Argentina":"63.8344255567876","Australia":"66.8385483487331","Belgium":"65.3976796810794","Brazil":"68.691447149572","Canada":"68.6122880862813","Switzerland":"67.6099394498073","China":"73.9564105552921","Germany":"65.9543364155197","Denmark":"64.7100355546911","Egypt":"62.2403687067541","Spain":"67.0554917652533","European Union":"65.9853601485424","Finland":"64.6166620912548","France":"63.3063359273901","United Kingdom":"65.1381846552613","Greece":"65.0488405237589","Indonesia":"66.7306974319353","India":"64.970467570728","Italy":"64.5465331452692","Japan":"61.9575243569782","South Korea":"73.0162279768015","Mexico":"65.2878049428581","Netherlands":"66.0065106179923","Norway":"66.0604904149741","Pakistan":"60.0907445217175","Poland":"70.5839409707702","Portugal":"65.6437803856242","Russian Federation":"70.9462052448891","Saudi Arabia":"68.1098647858315","Sweden":"63.9237542427995","Turkey":"66.5474643633847","United States":"66.8130988341311","World":"65.7076732560018","South Africa":"65.0497469803821"},"previous_year3":{"Afghanistan":"51.19994571902","Argentina":"63.8314645861013","Australia":"67.0735082464496","Belgium":"65.6663316040468","Brazil":"68.4941363897626","Canada":"68.9030083642806","Switzerland":"67.7896444905584","China":"74.218505240561","Germany":"65.905668176049","Denmark":"64.9511549066841","Egypt":"62.7065834539076","Spain":"67.4371115242587","European Union":"66.2998260950993","Finland":"65.3398822116253","France":"63.7852676854736","United Kingdom":"65.4679526142906","Greece":"65.5925985867077","Indonesia":"66.5848861383266","India":"64.6270058566063","Italy":"64.895842939562","Japan":"62.5821275932109","South Korea":"72.9154514419328","Mexico":"64.918155843499","Netherlands":"66.4400663955392","Norway":"66.1681180352095","Pakistan":"59.8265729215727","Poland":"70.9702610389582","Portugal":"65.873546935164","Russian Federation":"71.4359537873553","Saudi Arabia":"67.7324897129436","Sweden":"64.52983441954","Turkey":"66.4144728786433","United States":"67.0184563369572","World":"65.7202284749013","South Africa":"64.598506728413"},"last_lustrum":{"Afghanistan":"50.5899007770686","Argentina":"63.8010682641207","Australia":"67.2846702771416","Belgium":"65.8826779219776","Brazil":"68.2574013060121","Canada":"69.1502020078026","Switzerland":"67.9283879142745","China":"74.3531397721653","Germany":"65.8641064902167","Denmark":"65.1583399637448","Egypt":"63.0530455126588","Spain":"67.8311825337671","European Union":"66.5836000025252","Finland":"65.9344573647434","France":"64.2006812088909","United Kingdom":"65.7932028093619","Greece":"66.0398544003266","Indonesia":"66.4171842016405","India":"64.2951738133379","Italy":"65.2270892037337","Japan":"63.1931618061357","South Korea":"72.7749683872655","Mexico":"64.5346006822848","Netherlands":"66.8128971460101","Norway":"66.2002908792891","Pakistan":"59.581008651552","Poland":"71.2824229598958","Portugal":"66.0838349871185","Russian Federation":"71.8150988123326","Saudi Arabia":"67.2858979010949","Sweden":"64.9983957476957","Turkey":"66.2603964726418","United States":"67.1536572472043","World":"65.6992936483233","South Africa":"64.2226492991441"},"last_decade":{"Afghanistan":"50.1066586349895","Argentina":"63.0979290296079","Australia":"67.4508211789174","Belgium":"65.8412145486394","Brazil":"66.8753473190138","Canada":"69.347513553221","Switzerland":"68.0121310366927","China":"72.9661127599566","Germany":"66.6187684489954","Denmark":"65.9801429817067","Egypt":"62.266525343371","Spain":"68.9351520320008","European Union":"67.2194569038955","Finland":"66.6319826833251","France":"64.8916293462857","United Kingdom":"66.0989680760641","Greece":"66.6647924908985","Indonesia":"65.5933356822995","India":"62.7184169967187","Italy":"66.2110970259225","Japan":"65.9261580239506","South Korea":"72.2130721069422","Mexico":"62.6235139578077","Netherlands":"67.5536434892649","Norway":"65.8368731794121","Pakistan":"58.0509540285815","Poland":"70.7846377493996","Portugal":"67.0798024334766","Russian Federation":"71.2698418645046","Saudi Arabia":"64.5431544948437","Sweden":"65.2886334932313","Turkey":"65.130273944348","United States":"67.2417182009913","World":"64.8896064147916","South Africa":"63.1909566418484"},"last_2decade":{"Afghanistan":"49.6939045178414","Argentina":"61.4846016549612","Australia":"66.5649180002118","Belgium":"65.8525507465481","Brazil":"63.4443819296734","Canada":"67.6748047849221","Switzerland":"67.4913979239431","China":"66.7880338961644","Germany":"68.463059075588","Denmark":"67.2115260726141","Egypt":"56.1184071314083","Spain":"68.3915629428017","European Union":"67.0167784126715","Finland":"66.659890780783","France":"65.3407648758067","United Kingdom":"64.6778848416998","Greece":"67.912227845925","Indonesia":"62.6350718737473","India":"59.5463833756726","Italy":"68.3506222405677","Japan":"69.3538786951384","South Korea":"71.1074587323877","Mexico":"59.513807770729","Netherlands":"68.4288901651912","Norway":"64.560183184901","Pakistan":"53.5091168887476","Poland":"66.1187220408383","Portugal":"67.3592994456019","Russian Federation":"66.9794083004231","Saudi Arabia":"56.771074657068","Sweden":"63.6656413640668","Turkey":"61.9329073815612","United States":"65.7121183172426","World":"61.8701973051154","South Africa":"60.7978717758753"}}},"pop65":{"indicator":"pop65","indicator_sf":"population % aged >65","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":"2.46908972426905","Argentina":"10.9265535851309","Australia":"15.0448748888824","Belgium":"18.2248164293518","Brazil":"7.84467496770036","Canada":"16.1357784616535","Switzerland":"18.0418460178465","China":"9.55120656634957","Germany":"21.2406519413629","Denmark":"18.9594398104384","Egypt":"5.22026119572125","Spain":"18.7894661035796","European Union":"19.1917127880655","Finland":"20.4771837047151","France":"19.1205109623995","United Kingdom":"17.7603695294859","Greece":"21.396558181815","Indonesia":"5.17372908147055","India":"5.61611017147319","Italy":"22.4098759007142","Japan":"26.3420131425476","South Korea":"13.1269190003094","Mexico":"6.46698907543437","Netherlands":"18.2304693863117","Norway":"16.3329186310334","Pakistan":"4.49238092385866","Poland":"15.5333238336452","Portugal":"20.7914682047571","Russian Federation":"13.3658426985027","Saudi Arabia":"2.86230612625622","Sweden":"19.9420190919181","Turkey":"7.53869755140193","United States":"14.7860692786228","World":"8.26311335750851","South Africa":"5.03336128565458"},"previous_year":{"Afghanistan":"2.43468454325801","Argentina":"10.8089836893072","Australia":"14.7242740805711","Belgium":"18.0048461107743","Brazil":"7.57532620019252","Canada":"15.6909477359273","Switzerland":"17.8373971838849","China":"9.18268084838188","Germany":"21.070280975949","Denmark":"18.4990735623216","Egypt":"5.17090205846929","Spain":"18.4415918160846","European Union":"18.841450494669","Finland":"19.8080902829738","France":"18.6690889318142","United Kingdom":"17.4985778317594","Greece":"20.8689440754957","Indonesia":"5.10861501684987","India":"5.48830449671205","Italy":"22.0141920876075","Japan":"25.7054221977529","South Korea":"12.6763034070043","Mexico":"6.30848223499061","Netherlands":"17.7076918334221","Norway":"16.033698720078","Pakistan":"4.4909308899168","Poland":"15.018130255603","Portugal":"20.3677383066488","Russian Federation":"13.2313586817099","Saudi Arabia":"2.79464731325567","Sweden":"19.6316243418311","Turkey":"7.38828835350513","United States":"14.3897106161988","World":"8.10133815832382","South Africa":"5.02803328737243"},"previous_year2":{"Afghanistan":"2.39441375376844","Argentina":"10.6998318503718","Australia":"14.37815703296","Belgium":"17.7501241356456","Brazil":"7.32897515887361","Canada":"15.2468171713849","Switzerland":"17.5987123091709","China":"8.88928751468627","Germany":"20.951761441487","Denmark":"17.9746147649136","Egypt":"5.11109714326295","Spain":"18.0898192091157","European Union":"18.4817152247465","Finland":"19.0499427752596","France":"18.1830928692418","United Kingdom":"17.1551822454978","Greece":"20.3184140913694","Indonesia":"5.06172295304005","India":"5.38140684600555","Italy":"21.5880087454983","Japan":"25.0093263010257","South Korea":"12.2470076086797","Mexico":"6.18056349053321","Netherlands":"17.1415199465387","Norway":"15.6913120026754","Pakistan":"4.48262949921268","Poland":"14.5366112549936","Portugal":"19.9485339754923","Russian Federation":"13.1618650861356","Saudi Arabia":"2.76258597755422","Sweden":"19.2511372578031","Turkey":"7.2744230959637","United States":"13.9970054921467","World":"7.96203200945811","South Africa":"5.02704130844397"},"previous_year3":{"Afghanistan":"2.35081115180802","Argentina":"10.5980692726567","Australia":"14.034997899515","Belgium":"17.5019930915786","Brazil":"7.10177594262306","Canada":"14.8268085680827","Switzerland":"17.3485795427633","China":"8.65420482519149","Germany":"20.864925837079","Denmark":"17.4565057636425","Egypt":"5.0433615560107","Spain":"17.7489792350216","European Union":"18.118613846273","Finland":"18.2850524384642","France":"17.7193241135186","United Kingdom":"16.7742949190709","Greece":"19.7955167312921","Indonesia":"5.02539142684806","India":"5.28980200034704","Italy":"21.1636862520206","Japan":"24.2867873787552","South Korea":"11.8371847570891","Mexico":"6.07584950402988","Netherlands":"16.564387875375","Norway":"15.3701393301845","Pakistan":"4.46714831716551","Poland":"14.1078127794812","Portugal":"19.5393330832782","Russian Federation":"13.1264564421011","Saudi Arabia":"2.7575627337453","Sweden":"18.8530424562085","Turkey":"7.18740071284942","United States":"13.6235403068426","World":"7.83646890538976","South Africa":"5.02899903579431"},"last_lustrum":{"Afghanistan":"2.30968149825366","Argentina":"10.5005529146418","Australia":"13.734007837951","Belgium":"17.3036957613123","Brazil":"6.88606142391981","Canada":"14.4583760448036","Switzerland":"17.114143369267","China":"8.44642884568832","Germany":"20.7626105747534","Denmark":"17.0138958002373","Egypt":"4.97568650762952","Spain":"17.4380088569144","European Union":"17.8106646909573","Finland":"17.6256566198699","France":"17.3274843897203","United Kingdom":"16.4316114622368","Greece":"19.3551681437369","Indonesia":"4.98642296595114","India":"5.20313628226433","Italy":"20.7772624447601","Japan":"23.5872887769159","South Korea":"11.4500603079315","Mexico":"5.97907182047553","Netherlands":"16.0247810302577","Norway":"15.1374510617296","Pakistan":"4.44771788792939","Poland":"13.7557422139177","Portugal":"19.1496488889146","Russian Federation":"13.1013697533569","Saudi Arabia":"2.76416872634771","Sweden":"18.4955178162892","Turkey":"7.1108285944522","United States":"13.2907766221524","World":"7.72931342260012","South Africa":"5.03631008908735"},"last_decade":{"Afghanistan":"2.19700785610009","Argentina":"10.1577425980346","Australia":"12.9793963261981","Belgium":"17.1744592190844","Brazil":"6.01326626814559","Canada":"13.2538505817407","Switzerland":"15.9762480654229","China":"7.6323937272518","Germany":"19.2988212185012","Denmark":"15.3771870363645","Egypt":"5.00347528659233","Spain":"16.6823372062722","European Union":"16.8789404155846","Finland":"16.1467360346333","France":"16.6684247689632","United Kingdom":"16.0094016621915","Greece":"18.642885899098","Indonesia":"4.86275689709304","India":"4.85475301293907","Italy":"19.6731191479833","Japan":"20.3935742299682","South Korea":"9.56603513578855","Mexico":"5.43499167021726","Netherlands":"14.265508330079","Norway":"14.7110896369377","Pakistan":"4.30890077954659","Poland":"13.316231085703","Portugal":"17.453360932982","Russian Federation":"13.8106076482349","Saudi Arabia":"2.82523276756084","Sweden":"17.3592431858656","Turkey":"6.645499659332","United States":"12.3905071582908","World":"7.3494736310751","South Africa":"4.74500337104628"},"last_2decade":{"Afghanistan":"2.31721561852898","Argentina":"9.64780232505187","Australia":"12.0014804834936","Belgium":"16.2090379876362","Brazil":"4.6031262984666","Canada":"12.0574086295027","Switzerland":"14.7988712727701","China":"6.02369436942944","Germany":"15.5856866721618","Denmark":"15.2075895495247","Egypt":"4.97243607732852","Spain":"15.3976286345647","European Union":"14.9372087384481","Finland":"14.3671670482817","France":"15.3294239728141","United Kingdom":"15.861664156479","Greece":"15.6780941807263","Indonesia":"4.26625139429284","India":"4.14385637538834","Italy":"16.819484008819","Japan":"14.9406389209257","South Korea":"6.1397263694415","Mexico":"4.7078763727532","Netherlands":"13.2044034961365","Norway":"15.8167749708102","Pakistan":"4.01478385622337","Poland":"11.3276368480945","Portugal":"15.2312182280902","Russian Federation":"12.3222425046523","Saudi Arabia":"2.89872735480797","Sweden":"17.4144294202332","Turkey":"5.2964477524936","United States":"12.54821524623","World":"6.54211356844834","South Africa":"3.50908415157127"}}},"popdensity":{"indicator":"popdensity","indicator_sf":"population per km2","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":null,"World":null,"South Africa":null},"previous_year":{"Afghanistan":"48.4445455380939","Argentina":"15.7051131110941","Australia":"3.05509001210575","Belgium":"370.911922060766","Brazil":"24.6559519223176","Canada":"3.90868410547742","Switzerland":"207.209788440126","China":"145.317355990401","Germany":"232.31402995352","Denmark":"132.890172048079","Egypt":"89.9891204982671","Spain":"92.9130405229804","European Union":"119.857368027501","Finland":"17.9720030274112","France":"120.932631671223","United Kingdom":"266.850473277394","Greece":"84.3261210240497","Indonesia":"140.460914013811","India":"435.657170581093","Italy":"206.667369279935","Japan":"348.726684222076","South Korea":"517.349178174953","Mexico":"64.5005442526814","Netherlands":"500.891238491238","Norway":"14.0642199071856","Pakistan":"240.042919779992","Poland":"124.136164723556","Portugal":"113.548711790393","Russian Federation":"8.78187156642264","Saudi Arabia":"14.3679065353609","Sweden":"23.8034811214219","Turkey":"98.6608474201889","United States":"34.8575943818038","World":"55.9573087250944","South Africa":"44.5160317865946"},"previous_year2":{"Afghanistan":"46.9970590938333","Argentina":"15.5437057174908","Australia":"3.00917082123843","Belgium":"369.313639365918","Brazil":"24.4383770791109","Canada":"3.86599882773538","Switzerland":"204.710648851098","China":"144.583456848168","Germany":"235.647997360418","Denmark":"132.334008955927","Egypt":"88.0143744035361","Spain":"93.2009456028468","European Union":"119.867148915506","Finland":"17.8978314521702","France":"120.399333767991","United Kingdom":"265.06934237176","Greece":"85.067579519007","Indonesia":"138.701941409937","India":"430.345478761869","Italy":"204.779859930645","Japan":"349.294000987492","South Korea":"515.253206246281","Mexico":"63.6539566346871","Netherlands":"499.09212949213","Norway":"13.907440211365","Pakistan":"235.046500103778","Poland":"124.229110740995","Portugal":"114.162609170306","Russian Federation":"8.76278012831512","Saudi Arabia":"14.0490261386526","Sweden":"23.5684661462169","Turkey":"97.4626794693554","United States":"34.5996500652643","World":"55.3075275679663","South Africa":"43.8199061899777"},"previous_year3":{"Afghanistan":"45.5331970100787","Argentina":"15.3818021040015","Australia":"2.95852205719641","Belgium":"367.51142668428","Brazil":"24.2161035828546","Canada":"3.82156900910649","Switzerland":"202.370204474137","China":"143.87139360204","Germany":"230.750625466231","Denmark":"131.783455102522","Egypt":"86.0524406047516","Spain":"93.5068371284061","European Union":"119.17566892546","Finland":"17.8155615518773","France":"119.877009136882","United Kingdom":"263.30054147894","Greece":"85.6866640806827","Indonesia":"136.918724090154","India":"424.994581241024","Italy":"202.419653906303","Japan":"349.905335198596","South Korea":"513.656302003082","Mexico":"62.7953203528897","Netherlands":"496.884994068802","Norway":"13.7402921326781","Pakistan":"230.116557700291","Poland":"124.300058781268","Portugal":"114.803406485424","Russian Federation":"8.74414195142295","Saudi Arabia":"13.7210700147463","Sweden":"23.3696027888251","Turkey":"96.2790626664761","United States":"34.3388712882977","World":"54.6395202990571","South Africa":"43.1474128053154"},"last_lustrum":{"Afghanistan":"44.1276337959134","Argentina":"15.2211671764065","Australia":"2.90798641031983","Belgium":"364.85284015852","Brazil":"23.9906945803731","Canada":"3.77662530749952","Switzerland":"200.23276647434","China":"143.172112343875","Germany":"234.673149529493","Denmark":"131.288522271977","Egypt":"84.1706102767592","Spain":"93.5078358806114","European Union":"119.200050789992","Finland":"17.7309947678436","France":"119.335112143576","United Kingdom":"261.47612119208","Greece":"86.1512723041117","Indonesia":"135.135961624447","India":"419.564848193355","Italy":"201.874784116407","Japan":"350.611778743948","South Korea":"511.976139051733","Mexico":"61.9178842048407","Netherlands":"495.049644128114","Norway":"13.561001519528","Pakistan":"225.287525944375","Poland":"124.296296901022","Portugal":"115.269789278306","Russian Federation":"8.72943779855369","Saudi Arabia":"13.3919020882081","Sweden":"23.027764780426","Turkey":"95.1098216025883","United States":"34.0775466743628","World":"54.0073409998763","South Africa":"42.4976539251004"},"last_decade":{"Afghanistan":"38.5742961737585","Argentina":"14.4549620161582","Australia":"2.69423219608711","Belgium":"348.347357992074","Brazil":"22.8158706362899","Canada":"3.58173081681331","Switzerland":"189.385176000202","China":"139.645342881819","Germany":"236.225197866483","Denmark":"128.146877209522","Egypt":"76.6229192827364","Spain":"88.9761493446631","European Union":"117.484474319093","Finland":"17.3169839860577","France":"116.19154205232","United Kingdom":"251.50589013351","Greece":"85.4954383242824","Indonesia":"126.555407740247","India":"390.855715578217","Italy":"197.674505337594","Japan":"350.765432098765","South Korea":"499.297543352601","Mexico":"57.2971820262867","Netherlands":"484.185456161137","Norway":"12.7604128735506","Pakistan":"203.046114829805","Poland":"124.514452206843","Portugal":"115.035399584563","Russian Federation":"8.73438752843799","Saudi Arabia":"11.8249580172025","Sweden":"22.1292221084954","Turkey":"89.2698062705456","United States":"32.5673998463204","World":"50.8273107709721","South Africa":"39.5615321204527"},"last_2decade":{"Afghanistan":"26.7772569923108","Argentina":"12.9425265558028","Australia":"2.38353097379691","Belgium":null,"Brazil":"19.7775049233442","Canada":"3.26297546272012","Switzerland":"178.907356810362","China":"129.688982907321","Germany":"234.652470709559","Denmark":"124.041338675465","Egypt":"63.8863117183183","Spain":"79.0449022905654","European Union":"115.109584625376","Finland":"16.8244952230868","France":"109.124923753484","United Kingdom":"240.428843053776","Greece":"82.3025601241272","Indonesia":"110.360965902504","India":"329.373646487443","Italy":"193.329981979531","Japan":"345.012345679012","South Korea":"471.953980924736","Mexico":"49.4774608400422","Netherlands":"460.026599526066","Norway":"11.9956084272201","Pakistan":"163.05735133873","Poland":"126.103921120507","Portugal":"109.988469945355","Russian Federation":"9.04630625547992","Saudi Arabia":"8.99260404988626","Sweden":"21.5455427206707","Turkey":"77.2468432883334","United States":"29.4131648134723","World":"44.630315842192","South Africa":"32.9738494258464"}}},"popgrowth":{"indicator":"popgrowth","indicator_sf":"population growth (%)","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":"0.106929672888654","Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":null,"World":"1.14934949933134","South Africa":null},"previous_year":{"Afghanistan":"3.03347255254667","Argentina":"1.03305555285726","Australia":"1.51444899148602","Belgium":"0.431837337674369","Brazil":"0.886360083057745","Canada":"1.09806938773565","Switzerland":"1.21342377172249","China":"0.506311591779847","Germany":"-1.42491211770523","Denmark":"0.41939155414779","Egypt":"2.2188633037184","Spain":"-0.309386001032001","European Union":"-0.00815977362742615","Finland":"0.413560207519414","France":"0.441962812793398","United Kingdom":"0.669701526510699","Greece":"-0.875431881947159","Indonesia":"1.26019335881402","India":"1.22673029572789","Italy":"0.917504095962437","Japan":"-0.162550165733791","South Korea":"0.40595970701192","Mexico":"1.32121763533464","Netherlands":"0.359828172726548","Norway":"1.12100130599905","Pakistan":"2.10343724180588","Poland":"-0.0748462292920506","Portugal":"-0.539190466792077","Russian Federation":"0.217632654954767","Saudi Arabia":"2.24439270805474","Sweden":"0.992219728591882","Turkey":"1.22186550135945","United States":"0.742746143848123","World":"1.17485121049687","South Africa":"1.57611957984501"},"previous_year2":{"Afghanistan":"3.16433627387256","Argentina":"1.04706505456002","Australia":"1.69747274985435","Belgium":"0.489184301296706","Brazil":"0.913687864138812","Canada":"1.15590039039348","Switzerland":"1.14987975781229","China":"0.49370963351136","Germany":"2.10015710103447","Denmark":"0.416901360752097","Egypt":"2.25432617430608","Spain":"-0.327669039579687","European Union":"0.579222337522168","Finland":"0.460723772785295","France":"0.434040089300732","United Kingdom":"0.669533807456598","Greece":"-0.725120806658085","Indonesia":"1.29398294440884","India":"1.25119062623581","Italy":"1.1592511168095","Japan":"-0.174866975766575","South Korea":"0.429494122423308","Mexico":"1.35809314149312","Netherlands":"0.294820793441482","Norway":"1.20914158930136","Pakistan":"2.11974141846723","Poland":"-0.0603600174744649","Portugal":"-0.548815210952228","Russian Federation":"0.212923595008292","Saudi Arabia":"2.36204707328032","Sweden":"0.847348652246305","Turkey":"1.2218652681796","United States":"0.756558207855926","World":"1.22259221854588","South Africa":"1.54657357912275"},"previous_year3":{"Afghanistan":"3.13554616440454","Argentina":"1.04980926143269","Australia":"1.72289521851643","Belgium":"0.726031655132031","Brazil":"0.935181982037551","Canada":"1.18302385819388","Switzerland":"1.06181932886789","China":"0.487231117971201","Germany":"-1.69134895621081","Denmark":"0.376272242619955","Egypt":"2.21110711674198","Spain":"0.0649259625617232","European Union":"-0.0885850550359066","Finland":"0.475809486683209","France":"0.453799462538199","United Kingdom":"0.695315842711388","Greece":"-0.540752950543684","Indonesia":"1.31061001922409","India":"1.28583203721824","Italy":"0.269541239520996","Japan":"-0.200320558786153","South Korea":"0.450977410550375","Mexico":"1.4071495144712","Netherlands":"0.370055034770235","Norway":"1.31344098868985","Pakistan":"2.12084736871807","Poland":"-0.000239076003298365","Portugal":"-0.405421787747567","Russian Federation":"0.168301590750173","Saudi Arabia":"2.42824061643758","Sweden":"0.739763272434863","Turkey":"1.22186365749016","United States":"0.763927409799426","World":"1.16829667358662","South Africa":"1.51735849256521"},"last_lustrum":{"Afghanistan":"2.98397866645826","Argentina":"1.04428767328593","Australia":"1.38952731561803","Belgium":"1.38684911331611","Brazil":"0.953765381161696","Canada":"0.987617711325488","Switzerland":"1.11187894263206","China":"0.47915045424996","Germany":"0.0253621280208064","Denmark":"0.411737855195019","Egypt":"2.1066381794963","Spain":"0.355338396471428","European Union":"0.220924866630256","Finland":"0.463558707499131","France":"0.483644884690839","United Kingdom":"0.781677289298684","Greece":"-0.147951277402184","Indonesia":"1.31374733349501","India":"1.32840109681551","Italy":"0.171978290995717","Japan":"-0.197526883816496","South Korea":"0.744180714074818","Mexico":"1.46266598582924","Netherlands":"0.466428782200821","Norway":"1.297189390693","Pakistan":"2.10981750620738","Poland":"0.0537697088781007","Portugal":"-0.147084878575482","Russian Federation":"0.0779670984684485","Saudi Arabia":"2.45371742438343","Sweden":"0.755150133665177","Turkey":"1.22186565172967","United States":"0.764677599367843","World":"1.20116502112077","South Africa":"1.48846143234634"},"last_decade":{"Afghanistan":"3.16125834846767","Argentina":"1.0501665291824","Australia":"1.47522794532757","Belgium":"0.659558214980699","Brazil":"1.17044204549015","Canada":"0.796844597283984","Switzerland":"0.627558473346134","China":"0.558374367373002","Germany":"-0.112797497644931","Denmark":"0.328645158919929","Egypt":"1.76198410376974","Spain":"1.69035255626114","European Union":"0.377593455514869","Finland":"0.383777136305104","France":"0.697191228293622","United Kingdom":"0.73504867842129","Greece":"0.30033180096664","Indonesia":"1.32127216342416","India":"1.54025760745955","Italy":"0.30055968851778","Japan":"0.0633735894181335","South Korea":"0.484653200790179","Mexico":"1.47874567106962","Netherlands":"0.160613668857538","Norway":"0.805392739160981","Pakistan":"2.04460469064385","Poland":"-0.0633705742926632","Portugal":"0.18033244147767","Russian Federation":"-0.327318706386192","Saudi Arabia":"2.6903286558977","Sweden":"0.562483906481893","Turkey":"1.23620643721729","United States":"0.964253917136075","World":"1.23952713515654","South Africa":"1.34820838106126"},"last_2decade":{"Afghanistan":"4.14183876292854","Argentina":"1.20676891837163","Australia":"1.31381929938745","Belgium":"0.1953931762382","Brazil":"1.55347567842489","Canada":"1.0771646811962","Switzerland":"0.441636406706345","China":"1.04814151412165","Germany":"0.289474899455448","Denmark":"0.565926341934592","Egypt":"1.84263019097166","Spain":"0.231202196009241","European Union":"0.135210796763204","Finland":"0.328037913627481","France":"0.354077244127682","United Kingdom":"0.254626384215086","Greece":"0.44067053086068","Indonesia":"1.49606531461805","India":"1.89839514985975","Italy":"0.0281044080072756","Japan":"0.253188880298405","South Korea":"0.952779421852704","Mexico":"1.84127375204761","Netherlands":"0.461395747607711","Norway":"0.506881682762136","Pakistan":"2.49544518790602","Poland":"0.0760741823645528","Portugal":"0.375996187340096","Russian Federation":"-0.145469155029265","Saudi Arabia":"2.50185230550836","Sweden":"0.159147080582875","Turkey":"1.57524323280021","United States":"1.16341161998189","World":"1.45150131455284","South Africa":"2.22517839282546"}}},"population":{"indicator":"population","indicator_sf":"population","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":"32527000","Argentina":"43417000","Australia":"23802000","Belgium":"11301000","Brazil":"207848000","Canada":"35883000","Switzerland":"8265000","China":"1370840000","Germany":"80930000","Denmark":"5660000","Egypt":"91508000","Spain":"46460000","European Union":"508506000","Finland":"5480000","France":"66477000","United Kingdom":"64955000","Greece":"10840000","Indonesia":"257564000","India":"1311051000","Italy":"60772000","Japan":"126820000","South Korea":"50633000","Mexico":"127017000","Netherlands":"16917000","Norway":"5197000","Pakistan":"188925000","Poland":"37992000","Portugal":"10358000","Russian Federation":"143814000","Saudi Arabia":"31540000","Sweden":"9766000","Turkey":"76819000","United States":"321191000","World":"7343131000","South Africa":"54767000"},"previous_year":{"Afghanistan":"31627506","Argentina":"42980026","Australia":"23470118","Belgium":"11231213","Brazil":"206077898","Canada":"35543658","Switzerland":"8188102","China":"1364270000","Germany":"80970732","Denmark":"5638530","Egypt":"89579670","Spain":"46476032","European Union":"507962837","Finland":"5461512","France":"66217509","United Kingdom":"64559135","Greece":"10869637","Indonesia":"254454778","India":"1295291543","Italy":"60789140","Japan":"127131800","South Korea":"50423955","Mexico":"125385833","Netherlands":"16865008","Norway":"5136886","Pakistan":"185044286","Poland":"38011735","Portugal":"10401062","Russian Federation":"143819569","Saudi Arabia":"30886545","Sweden":"9696110","Turkey":"75932348","United States":"318857056","World":"7259691769","South Africa":"54001953"},"previous_year2":{"Afghanistan":"30682500","Argentina":"42538304","Australia":"23117353","Belgium":"11182817","Brazil":"204259377","Canada":"35155499","Switzerland":"8089346","China":"1357380000","Germany":"82132753","Denmark":"5614932","Egypt":"87613909","Spain":"46620045","European Union":"508004289","Finland":"5438972","France":"65925498","United Kingdom":"64128226","Greece":"10965211","Indonesia":"251268276","India":"1279498874","Italy":"60233948","Japan":"127338621","South Korea":"50219669","Mexico":"123740109","Netherlands":"16804432","Norway":"5079623","Pakistan":"181192646","Poland":"38040196","Portugal":"10457295","Russian Federation":"143506911","Saudi Arabia":"30201051","Sweden":"9600379","Turkey":"75010202","United States":"316497531","World":"7175391594","South Africa":"53157490"},"previous_year3":{"Afghanistan":"29726803","Argentina":"42095224","Australia":"22728254","Belgium":"11128246","Brazil":"202401584","Canada":"34751476","Switzerland":"7996861","China":"1350695000","Germany":"80425823","Denmark":"5591572","Egypt":"85660902","Spain":"46773055","European Union":"505078760","Finland":"5413971","France":"65639975","United Kingdom":"63700300","Greece":"11045011","Indonesia":"248037853","India":"1263589639","Italy":"59539717","Japan":"127561489","South Korea":"50004441","Mexico":"122070963","Netherlands":"16754962","Norway":"5018573","Pakistan":"177392252","Poland":"38063164","Portugal":"10514844","Russian Federation":"143201676","Saudi Arabia":"29496047","Sweden":"9519374","Turkey":"74099255","United States":"314112078","World":"7088725389","South Africa":"52341695"},"last_lustrum":{"Afghanistan":"28809167","Argentina":"41655616","Australia":"22340024","Belgium":"11047744","Brazil":"200517584","Canada":"34342780","Switzerland":"7912398","China":"1344130000","Germany":"81797673","Denmark":"5570572","Egypt":"83787634","Spain":"46742697","European Union":"505526581","Finland":"5388272","France":"65342776","United Kingdom":"63258918","Greece":"11104899","Indonesia":"244808254","India":"1247446011","Italy":"59379449","Japan":"127817277","South Korea":"49779440","Mexico":"120365271","Netherlands":"16693074","Norway":"4953088","Pakistan":"173669648","Poland":"38063255","Portugal":"10557560","Russian Federation":"142960868","Saudi Arabia":"28788438","Sweden":"9449213","Turkey":"73199372","United States":"311721632","World":"7006864425","South Africa":"51553479"},"last_decade":{"Afghanistan":"25183615","Argentina":"39558750","Australia":"20697900","Belgium":"10547958","Brazil":"190698241","Canada":"32570505","Switzerland":"7483934","China":"1311020000","Germany":"82376451","Denmark":"5437272","Egypt":"76274285","Spain":"44397319","European Union":"498074489","Finland":"5266268","France":"63621376","United Kingdom":"60846820","Greece":"11020362","Indonesia":"229263980","India":"1162088305","Italy":"58143979","Japan":"127854000","South Korea":"48371946","Mexico":"111382857","Netherlands":"16346101","Norway":"4660677","Pakistan":"156524189","Poland":"38141267","Portugal":"10522288","Russian Federation":"143049528","Saudi Arabia":"25419994","Sweden":"9080505","Turkey":"68704721","United States":"298379912","World":"6595073129","South Africa":"47991699"},"last_2decade":{"Afghanistan":"17481800","Argentina":"35419683","Australia":"18311000","Belgium":"10156637","Brazil":"165303155","Canada":"29671900","Switzerland":"7071850","China":"1217550000","Germany":"81914831","Denmark":"5263074","Egypt":"63595629","Spain":"39478186","European Union":"484581653","Finland":"5124573","France":"59753098","United Kingdom":"58166950","Greece":"10608800","Indonesia":"199926615","India":"979290432","Italy":"56860281","Japan":"125757000","South Korea":"45524681","Mexico":"96181710","Netherlands":"15530498","Norway":"4381336","Pakistan":"125697651","Poland":"38624370","Portugal":"10063945","Russian Federation":"148160042","Saudi Arabia":"19331311","Sweden":"8840998","Turkey":"59451488","United States":"269394000","World":"5788542878","South Africa":"40000247"}}},"reserves":{"indicator":"reserves","indicator_sf":"gold\/silver reserves","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":"23416509698.2036","Australia":"46539781333.3604","Belgium":"16352187947.8291","Brazil":"354174890702.195","Canada":"79695441441.2641","Switzerland":null,"China":null,"Germany":"58506801329.72","Denmark":null,"Egypt":null,"Spain":"44377659996.4031","European Union":null,"Finland":"8341474780.09938","France":"55192497176.8912","United Kingdom":"118758554234.281","Greece":"2189346492.33105","Indonesia":"103268245986.863","India":null,"Italy":"47033688257.8375","Japan":"1207019192150.55","South Korea":null,"Mexico":"173445075846.526","Netherlands":"17341287836.009","Norway":null,"Pakistan":"17829731112.226","Poland":"91394817607.2976","Portugal":"6366743213.60793","Russian Federation":null,"Saudi Arabia":null,"Sweden":"53813898597.7049","Turkey":"94408052235.1512","United States":"106539607698.653","World":null,"South Africa":"41619504632.0386"},"previous_year":{"Afghanistan":"7528550402.40523","Argentina":"31410823483.4143","Australia":"53910346084.4978","Belgium":"25444420303.4322","Brazil":"363570247226.591","Canada":"74700002863.9467","Switzerland":"545787304073.241","China":"3900039358441.22","Germany":"193484844885.189","Denmark":"75391898442.9736","Egypt":"14927002166.4156","Spain":"50411867872.7529","European Union":null,"Finland":"10679258852.7593","France":"143977170862.924","United Kingdom":"107727629479.029","Greece":"6236288773.2901","Indonesia":"111862604046.204","India":"325081060905.901","Italy":"142756539021.191","Japan":"1260680415997.38","South Korea":"362834710646.116","Mexico":"195681644250.838","Netherlands":"43054424385.9934","Norway":"64800734876.8257","Pakistan":"14306813743.5247","Poland":"100452428565.274","Portugal":"19700742158.7829","Russian Federation":"386216377124.801","Saudi Arabia":"744440558276.383","Sweden":"62579183852.2533","Turkey":"127421926598.114","United States":"434416453479.958","World":null,"South Africa":"49121577905.7977"},"previous_year2":{"Afghanistan":"7288702808.98632","Argentina":"30533921358.1328","Australia":"52837031644.0728","Belgium":"26946180324.04","Brazil":"358816426346.517","Canada":"71937092647.18","Switzerland":"536235292234.498","China":"3880368275098.62","Germany":"198535181129.2","Denmark":"88676585995.6962","Egypt":"16536237509.56","Spain":"46335468959.02","European Union":null,"Finland":"11272358255.18","France":"145161120115.66","United Kingdom":"104418669118.24","Greece":"5763072025.66","Indonesia":"99386827824.74","India":"298092483487.487","Italy":"145724517428.74","Japan":"1266851419538.87","South Korea":"345694101316.376","Mexico":"180200037031.892","Netherlands":"46309218934.02","Norway":"58283144150.96","Pakistan":"7651260560.95194","Poland":"106221492600.971","Portugal":"17589254769.98","Russian Federation":"509692081493.02","Saudi Arabia":"737796506890.133","Sweden":"65363362010.6772","Turkey":"131053848307.023","United States":"448508967142.092","World":null,"South Africa":"49708176470.6712"},"previous_year3":{"Afghanistan":"7152304411.66686","Argentina":"43223271090.5489","Australia":"49138172510.564","Belgium":"30768940132.8315","Brazil":"373160978076.306","Canada":"68546344304.6514","Switzerland":"531302253382.241","China":"3387512975176.91","Germany":"248856492402.007","Denmark":"89697829949.2626","Egypt":"15672468061.2584","Spain":"50588476002.0462","European Union":null,"Finland":"11082345110.0045","France":"184521827260.365","United Kingdom":"105194404563.51","Greece":"7255024442.61224","Indonesia":"112797628043.608","India":"300425518088.108","Italy":"181670319827.779","Japan":"1268085526649.9","South Korea":"327724416296.583","Mexico":"167075785814.637","Netherlands":"54816126997.2448","Norway":"51856399864.7373","Pakistan":"13688479842.4827","Poland":"108902203765.188","Portugal":"22658232543.4348","Russian Federation":"537816373774.557","Saudi Arabia":"673739617131.103","Sweden":"52245310987.8558","Turkey":"119182966094.83","United States":"574268090541.4","World":null,"South Africa":"50688078607.1102"},"last_lustrum":{"Afghanistan":"6344642495.25739","Argentina":"46265809101.0066","Australia":"46714035204.1131","Belgium":"29114293149.0739","Brazil":"352010241721.417","Canada":"65819020598.9898","Switzerland":"330585901719.472","China":"3254674122432.29","Germany":"234104163353.968","Denmark":"84955231791.2683","Egypt":"18637544531.7575","Spain":"46704854402.6405","European Union":null,"Finland":"10276064353.5009","France":"168490352253.516","United Kingdom":"94544038250.0148","Greece":"6743420207.16311","Indonesia":"110136605627.115","India":"298739485811.368","Italy":"169872401864.175","Japan":"1295838776760.22","South Korea":"306934543258.158","Mexico":"149208131604.958","Netherlands":"50411074277.4324","Norway":"49397097548.673","Pakistan":"17697928678.7905","Poland":"97712443396.8254","Portugal":"20801308751.5782","Russian Federation":"497410247572.556","Saudi Arabia":"556570991484.125","Sweden":"50213905874.5147","Turkey":"87937258383.412","United States":"537267272427.958","World":null,"South Africa":"48748267721.6273"},"last_decade":{"Afghanistan":null,"Argentina":"32022296510.0172","Australia":"55078715673.8752","Belgium":"13436679649.158","Brazil":"85842861104.6689","Canada":"35063089703.1612","Switzerland":"64461047917.6955","China":"1080755680184.47","Germany":"111637054560.357","Denmark":"31083834394.0808","Egypt":"26006844917.5056","Spain":"19339947149.8916","European Union":null,"Finland":"7498638909.048","France":"98239160427.6836","United Kingdom":"47038783448.4777","Greece":"2849953362.47","Indonesia":"42597039985.2572","India":"178049789377.443","Italy":"75773324256.0656","Japan":"895321272119.849","South Korea":"239148088903.109","Mexico":"76329366176.0868","Netherlands":"23902292619.8384","Norway":"56841568021.0892","Pakistan":"12878021658.794","Poland":"48473947848.8856","Portugal":"9882746970.764","Russian Federation":"303773185537.126","Saudi Arabia":"228956883077.038","Sweden":"28017218814.5915","Turkey":"63264840946.3625","United States":"221088707676.236","World":null,"South Africa":"25593361010.2582"},"last_2decade":{"Afghanistan":null,"Argentina":"19719018706.8724","Australia":"17402143062.4941","Belgium":"22609781415.8045","Brazil":"59685476090.79","Canada":"21562306887.7352","Switzerland":"69182554318.1445","China":"111728906872.272","Germany":"118323154082.742","Denmark":"14754073287.3858","Egypt":"18296476741.5344","Spain":"63699015677.2589","European Union":null,"Finland":"7507186996.38048","France":"57020085575.061","United Kingdom":"46700030785.2408","Greece":"18782300969.9747","Indonesia":"19396150431.2003","India":"24889366112.5963","Italy":"70566444441.5457","Japan":"225593999841.827","South Korea":"34157943424.5348","Mexico":"19526913909.7629","Netherlands":"39606556277.5619","Norway":"26953900582.2874","Pakistan":"1307463660.42632","Poland":"18018686049.5498","Portugal":"21850468030.7761","Russian Federation":"16257617339.0512","Saudi Arabia":"16017719884.4657","Sweden":"20843374470.724","Turkey":"17819437214.5468","United States":"160660248052.918","World":null,"South Africa":"2341014437.17181"}}},"surfacekm":{"indicator":"surfacekm","indicator_sf":"surface (km2)","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":"652860","Argentina":"2780400","Australia":"7741220","Belgium":"30530","Brazil":"8515770","Canada":"9984670","Switzerland":"41285","China":"9562911","Germany":"357170","Denmark":"43090","Egypt":"1001450","Spain":"505940","European Union":"4383492","Finland":"338420","France":"549087","United Kingdom":"243610","Greece":"131960","Indonesia":"1910930","India":"3287260","Italy":"301340","Japan":"377962","South Korea":"100266","Mexico":"1964380","Netherlands":"41500","Norway":"385178","Pakistan":"796100","Poland":"312680","Portugal":"92220","Russian Federation":"17098250","Saudi Arabia":"2149690","Sweden":"447420","Turkey":"783560","United States":"9831510","World":"134325255.2","South Africa":"1219090"},"previous_year":{"Afghanistan":"652860","Argentina":"2780400","Australia":"7741220","Belgium":"30530","Brazil":"8515770","Canada":"9984670","Switzerland":"41285","China":"9562911","Germany":"357170","Denmark":"43090","Egypt":"1001450","Spain":"505940","European Union":"4383492","Finland":"338420","France":"549087","United Kingdom":"243610","Greece":"131960","Indonesia":"1910930","India":"3287260","Italy":"301340","Japan":"377962","South Korea":"100266","Mexico":"1964380","Netherlands":"41500","Norway":"385178","Pakistan":"796100","Poland":"312680","Portugal":"92220","Russian Federation":"17098250","Saudi Arabia":"2149690","Sweden":"447420","Turkey":"783560","United States":"9831510","World":"134325255.2","South Africa":"1219090"},"previous_year2":{"Afghanistan":"652860","Argentina":"2780400","Australia":"7741220","Belgium":"30530","Brazil":"8515770","Canada":"9984670","Switzerland":"41285","China":"9562911","Germany":"357170","Denmark":"43090","Egypt":"1001450","Spain":"505940","European Union":"4383492","Finland":"338420","France":"549087","United Kingdom":"243610","Greece":"131960","Indonesia":"1910930","India":"3287260","Italy":"301340","Japan":"377962","South Korea":"100266","Mexico":"1964380","Netherlands":"41500","Norway":"385178","Pakistan":"796100","Poland":"312680","Portugal":"92220","Russian Federation":"17098250","Saudi Arabia":"2149690","Sweden":"447420","Turkey":"783560","United States":"9831510","World":"134325255.2","South Africa":"1219090"},"previous_year3":{"Afghanistan":"652860","Argentina":"2780400","Australia":"7741220","Belgium":"30530","Brazil":"8515770","Canada":"9984670","Switzerland":"41285","China":"9562911","Germany":"357170","Denmark":"43090","Egypt":"1001450","Spain":"505940","European Union":"4383476","Finland":"338420","France":"549091","United Kingdom":"243610","Greece":"131960","Indonesia":"1910930","India":"3287260","Italy":"301340","Japan":"377960","South Korea":"100150","Mexico":"1964380","Netherlands":"41500","Norway":"385178","Pakistan":"796100","Poland":"312680","Portugal":"92210","Russian Federation":"17098250","Saudi Arabia":"2149690","Sweden":"447420","Turkey":"783560","United States":"9831510","World":"134325123.8","South Africa":"1219090"},"last_lustrum":{"Afghanistan":"652860","Argentina":"2780400","Australia":"7741220","Belgium":"30530","Brazil":"8515770","Canada":"9984670","Switzerland":"41285","China":"9562911","Germany":"357140","Denmark":"43090","Egypt":"1001450","Spain":"505600","European Union":"4385982","Finland":"338420","France":"549087","United Kingdom":"243610","Greece":"131960","Indonesia":"1910930","India":"3287260","Italy":"301340","Japan":"377955","South Korea":"100030","Mexico":"1964380","Netherlands":"41500","Norway":"385178","Pakistan":"796100","Poland":"312680","Portugal":"92210","Russian Federation":"17098250","Saudi Arabia":"2149690","Sweden":"450300","Turkey":"783560","United States":"9831510","World":"134327480.8","South Africa":"1219090"},"last_decade":{"Afghanistan":"652860","Argentina":"2780400","Australia":"7741220","Belgium":"30530","Brazil":"8515770","Canada":"9984670","Switzerland":"41285","China":"9562911.4","Germany":"357100","Denmark":"43090","Egypt":"1001450","Spain":"505370","European Union":"4385715","Finland":"338440","France":"549086","United Kingdom":"243610","Greece":"131960","Indonesia":"1910930","India":"3287260","Italy":"301340","Japan":"377920","South Korea":"99680","Mexico":"1964380","Netherlands":"41540","Norway":"385178","Pakistan":"796100","Poland":"312680","Portugal":"92090","Russian Federation":"17098240","Saudi Arabia":"2149690","Sweden":"450300","Turkey":"783560","United States":"9632030","World":"134111982.4","South Africa":"1219090"},"last_2decade":{"Afghanistan":"652860","Argentina":"2780400","Australia":"7741220","Belgium":null,"Brazil":"8515770","Canada":"9984670","Switzerland":"41285","China":"9562930","Germany":"357030","Denmark":"43090","Egypt":"1001450","Spain":"505990","European Union":"4352895","Finland":"338150","France":"549086","United Kingdom":"243610","Greece":"131960","Indonesia":"1910930","India":"3287260","Italy":"301340","Japan":"377800","South Korea":"99260","Mexico":"1964380","Netherlands":"41530","Norway":"385178","Pakistan":"796100","Poland":"312690","Portugal":"92120","Russian Federation":"17098240","Saudi Arabia":"2149690","Sweden":"450300","Turkey":"783560","United States":"9629090","World":"134035649.4","South Africa":"1219090"}}},"surpdeficitgdp":{"indicator":"surpdeficitgdp","indicator_sf":"surplus-or-deficit\/GDP (%)","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":null,"World":null,"South Africa":null},"previous_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":"-5.06502118780642","World":null,"South Africa":null},"previous_year2":{"Afghanistan":null,"Argentina":null,"Australia":"-1.46964378374726","Belgium":"-2.43718471399214","Brazil":null,"Canada":"-0.618980556660061","Switzerland":null,"China":null,"Germany":"-0.157932799682362","Denmark":"-1.18983690037018","Egypt":null,"Spain":"-4.77148608708469","European Union":"-3.03777485311997","Finland":"-2.58751226922754","France":"-3.20646897213173","United Kingdom":"-5.47018961364282","Greece":"-14.615632716331","Indonesia":null,"India":null,"Italy":"-2.97100983592768","Japan":"-7.17750266595575","South Korea":null,"Mexico":null,"Netherlands":"-0.755772773435639","Norway":"11.6413543921551","Pakistan":"-5.23482376063698","Poland":"-3.53496049424605","Portugal":"-5.35039408477841","Russian Federation":"0.744068753176662","Saudi Arabia":null,"Sweden":"-1.15111531869867","Turkey":"-0.812526019706678","United States":"-1.41966989250332","World":"-2.70413103371911","South Africa":"-4.27337568732318"},"previous_year3":{"Afghanistan":"-0.62110794913849","Argentina":null,"Australia":"-3.03196547926757","Belgium":"-3.45739367454874","Brazil":"-1.80409842703882","Canada":"-0.929321744752701","Switzerland":"0.0694175603208296","China":null,"Germany":"-0.534655118590418","Denmark":"-3.67504669808263","Egypt":"-10.1145116503682","Spain":"-7.91966799377105","European Union":"-4.03561495360285","Finland":"-2.76035696946339","France":"-4.08124473808165","United Kingdom":"-7.80362632287881","Greece":"-8.24930838142142","Indonesia":"-1.76022225518813","India":"-3.80571144291755","Italy":"-3.28766366888455","Japan":"-7.77103595290694","South Korea":null,"Mexico":null,"Netherlands":"-2.91088777427135","Norway":"14.3838138842199","Pakistan":"-8.00655974858454","Poland":"-3.65403881664244","Portugal":"-6.17107798966388","Russian Federation":"1.76787064045298","Saudi Arabia":null,"Sweden":"-1.10423903603995","Turkey":"-1.66347966811617","United States":"-3.78090098481992","World":"-3.85682253614143","South Africa":"-5.33572558583202"},"last_lustrum":{"Afghanistan":"-0.626409395980503","Argentina":null,"Australia":"-3.67251976351172","Belgium":"-3.49614619657827","Brazil":"-2.44605507233028","Canada":"-1.77213287578516","Switzerland":"0.0825790367749121","China":null,"Germany":"-1.00265618988428","Denmark":"-2.21942354221983","Egypt":"-10.0824593392167","Spain":"-3.44334383083912","European Union":"-3.90129004858217","Finland":"-2.35740517806257","France":"-4.43925995637319","United Kingdom":"-7.34229505767283","Greece":"-9.21755478425284","Indonesia":"-1.07061071989681","India":"-3.00863511342525","Italy":"-3.49383674996732","Japan":"-8.20912654664395","South Korea":"1.69081025391673","Mexico":null,"Netherlands":"-2.64383781101801","Norway":"14.2401090554959","Pakistan":"-6.39570397736102","Poland":"-4.0025354966337","Portugal":"-7.47164992896666","Russian Federation":"2.7585787063461","Saudi Arabia":null,"Sweden":"-0.318768071887998","Turkey":"-1.22102798296852","United States":"-5.33501061933148","World":"-4.23896962131899","South Africa":"-4.47448337316122"},"last_decade":{"Afghanistan":"-2.0278600964814","Argentina":null,"Australia":"1.24887972460087","Belgium":"-0.18572649650097","Brazil":"-2.83731092805499","Canada":null,"Switzerland":"0.0390772357852004","China":null,"Germany":"-1.8067071772694","Denmark":"4.10812518118063","Egypt":"-7.1684960336733","Spain":null,"European Union":"-2.71429883522641","Finland":"3.24017924849665","France":"-3.68466436838297","United Kingdom":"-3.63410103197736","Greece":"-8.03905140346823","Indonesia":null,"India":"-2.24290094931539","Italy":"-3.85802138088589","Japan":null,"South Korea":"1.0748253773648","Mexico":null,"Netherlands":"-0.974254113519748","Norway":"17.2810895859364","Pakistan":"-3.92427849506339","Poland":"-4.39528829215675","Portugal":"-5.71379862996234","Russian Federation":"8.02609360344013","Saudi Arabia":null,"Sweden":"0.125811186993822","Turkey":null,"United States":"-3.51122233643921","World":"-2.12770857778115","South Africa":"0.613785572337585"},"last_2decade":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":"-4.18857709570162","Brazil":null,"Canada":null,"Switzerland":"-0.858594703930946","China":null,"Germany":"-2.88623026537647","Denmark":"-3.71894422328919","Egypt":"-1.40061028770706","Spain":"-5.4480383219397","European Union":"-4.77823140015999","Finland":"-5.46443187144817","France":"-5.62662195639223","United Kingdom":"-4.80887072255422","Greece":"-7.9350381244417","Indonesia":"2.31594841940272","India":"-1.98389775688133","Italy":"-7.63943193789642","Japan":null,"South Korea":"2.40453697727535","Mexico":"-0.110727265970875","Netherlands":"-3.47367967996621","Norway":"4.8868964647274","Pakistan":"-6.59875396960531","Poland":"-2.02715126977255","Portugal":"-5.79728698821972","Russian Federation":null,"Saudi Arabia":null,"Sweden":"-4.82622911980953","Turkey":"-8.38058909146237","United States":"-1.37626146827715","World":"-2.62851457888944","South Africa":"-5.16016093247201"}}},"unemployed":{"indicator":"unemployed","indicator_sf":"unemployed %","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":null,"World":null,"South Africa":null},"previous_year":{"Afghanistan":"9.10000038146973","Argentina":"8.19999980926514","Australia":"6","Belgium":"8.5","Brazil":"6.80000019073486","Canada":"6.90000009536743","Switzerland":"4.5","China":"4.69999980926514","Germany":"5","Denmark":"6.59999990463257","Egypt":"13.1999998092651","Spain":"24.7000007629395","European Union":"10.2073359679361","Finland":"8.60000038146973","France":"9.89999961853027","United Kingdom":"6.30000019073486","Greece":"26.2999992370605","Indonesia":"6.19999980926514","India":"3.59999990463257","Italy":"12.5","Japan":"3.70000004768372","South Korea":"3.5","Mexico":"4.90000009536743","Netherlands":"6.90000009536743","Norway":"3.40000009536743","Pakistan":"5.19999980926514","Poland":"9.19999980926514","Portugal":"14.1999998092651","Russian Federation":"5.09999990463257","Saudi Arabia":"5.59999990463257","Sweden":"8","Turkey":"9.19999980926514","United States":"6.19999980926514","World":"5.93199801073195","South Africa":"25.1000003814697"},"previous_year2":{"Afghanistan":"9.19999980926514","Argentina":"7.09999990463257","Australia":"5.69999980926514","Belgium":"8.39999961853027","Brazil":"6.5","Canada":"7.09999990463257","Switzerland":"4.40000009536743","China":"4.59999990463257","Germany":"5.30000019073486","Denmark":"7","Egypt":"13.1999998092651","Spain":"26.2999992370605","European Union":"10.8820553569451","Finland":"8.19999980926514","France":"10.3999996185303","United Kingdom":"7.5","Greece":"27.2000007629395","Indonesia":"6.30000019073486","India":"3.59999990463257","Italy":"12.1999998092651","Japan":"4","South Korea":"3.09999990463257","Mexico":"4.90000009536743","Netherlands":"6.69999980926514","Norway":"3.5","Pakistan":"5.09999990463257","Poland":"10.3999996185303","Portugal":"16.5","Russian Federation":"5.5","Saudi Arabia":"5.69999980926514","Sweden":"8.10000038146973","Turkey":"8.69999980926514","United States":"7.40000009536743","World":"5.99342779513148","South Africa":"24.6000003814697"},"previous_year3":{"Afghanistan":"8.5","Argentina":"7.19999980926514","Australia":"5.19999980926514","Belgium":"7.5","Brazil":"6.09999990463257","Canada":"7.19999980926514","Switzerland":"4.19999980926514","China":"4.5","Germany":"5.40000009536743","Denmark":"7.5","Egypt":"12.6999998092651","Spain":"25.2000007629395","European Union":"10.5091542766612","Finland":"7.59999990463257","France":"9.89999961853027","United Kingdom":"8","Greece":"24.2000007629395","Indonesia":"6.09999990463257","India":"3.59999990463257","Italy":"10.6999998092651","Japan":"4.30000019073486","South Korea":"3.20000004768372","Mexico":"4.90000009536743","Netherlands":"5.30000019073486","Norway":"3.20000004768372","Pakistan":"5.09999990463257","Poland":"10.1000003814697","Portugal":"15.6000003814697","Russian Federation":"5.5","Saudi Arabia":"5.59999990463257","Sweden":"8.10000038146973","Turkey":"9.19999980926514","United States":"8.19999980926514","World":"5.99471158481525","South Africa":"25"},"last_lustrum":{"Afghanistan":"8.89999961853027","Argentina":"7.19999980926514","Australia":"5.09999990463257","Belgium":"7.09999990463257","Brazil":"6.69999980926514","Canada":"7.40000009536743","Switzerland":"4","China":"4.30000019073486","Germany":"5.90000009536743","Denmark":"7.59999990463257","Egypt":"12","Spain":"21.7000007629395","European Union":"9.61118501626309","Finland":"7.69999980926514","France":"9.19999980926514","United Kingdom":"7.80000019073486","Greece":"17.7000007629395","Indonesia":"6.59999990463257","India":"3.5","Italy":"8.39999961853027","Japan":"4.5","South Korea":"3.40000009536743","Mexico":"5.30000019073486","Netherlands":"4.40000009536743","Norway":"3.29999995231628","Pakistan":"5.09999990463257","Poland":"9.60000038146973","Portugal":"12.6999998092651","Russian Federation":"6.5","Saudi Arabia":"5.80000019073486","Sweden":"7.80000019073486","Turkey":"9.80000019073486","United States":"9","World":"5.9943318066803","South Africa":"24.7000007629395"},"last_decade":{"Afghanistan":"8.80000019073486","Argentina":"10.1000003814697","Australia":"4.80000019073486","Belgium":"8.19999980926514","Brazil":"8.39999961853027","Canada":"6.30000019073486","Switzerland":"4","China":"4","Germany":"10.3000001907349","Denmark":"3.90000009536743","Egypt":"10.6000003814697","Spain":"8.60000038146973","European Union":"8.22169439831428","Finland":"7.59999990463257","France":"8.80000019073486","United Kingdom":"5.5","Greece":"8.89999961853027","Indonesia":"10.3000001907349","India":"4.30000019073486","Italy":"6.80000019073486","Japan":"4.09999990463257","South Korea":"3.40000009536743","Mexico":"3.20000004768372","Netherlands":"3.90000009536743","Norway":"3.40000009536743","Pakistan":"6.09999990463257","Poland":"13.8000001907349","Portugal":"7.69999980926514","Russian Federation":"7.09999990463257","Saudi Arabia":"6.30000019073486","Sweden":"7.09999990463257","Turkey":"10.1999998092651","United States":"4.69999980926514","World":"5.90015115132642","South Africa":"22.6000003814697"},"last_2decade":{"Afghanistan":"8.39999961853027","Argentina":"17.2000007629395","Australia":"8.5","Belgium":"9.5","Brazil":"6.80000019073486","Canada":"9.60000038146973","Switzerland":"3.70000004768372","China":"4.59999990463257","Germany":"8.89999961853027","Denmark":"6.80000019073486","Egypt":"9","Spain":"22.5","European Union":"10.693881403155","Finland":"14.3999996185303","France":"12.3999996185303","United Kingdom":"8.30000019073486","Greece":"9.69999980926514","Indonesia":"4.40000009536743","India":"4","Italy":"11.8999996185303","Japan":"3.40000009536743","South Korea":"2","Mexico":"5.19999980926514","Netherlands":"6.40000009536743","Norway":"4.80000019073486","Pakistan":"5.30000019073486","Poland":"12.3999996185303","Portugal":"6.80000019073486","Russian Federation":"9.69999980926514","Saudi Arabia":"6","Sweden":"10.1000003814697","Turkey":"6.59999990463257","United States":"5.5","World":"6.18759901611452","South Africa":"21"}}},"history":[{"fact":"Neolithic Era","begin":"-10000","end":"-2000"},{"fact":"Writing and Civilization","begin":"-3500","end":"-600"},{"fact":"Ancient Greece (Greek Empire)","begin":"-1200","end":"146"},{"fact":"Rome foundation","begin":"-753","end":"-753"},{"fact":"Rome Republic","begin":"-509","end":"-45"},{"fact":"Alexander the great (of Macedonia)","begin":"-356","end":"-323"},{"fact":"Pre-Roman Europe (Greeks, Celts, Iberians, Berbers,...)","begin":"-300","end":"-270"},{"fact":"Julious Caesar Dictatorship","begin":"-45","end":"-44"},{"fact":"Roman Britain","begin":"-43","end":"410"},{"fact":"Roman Empire","begin":"-27","end":"476"},{"fact":"Western Roman Empire","begin":"395","end":"476"},{"fact":"Eastern Roman Empire (Bizantine Empire)","begin":"395","end":"1453"},{"fact":"European Tribes: Visigoths, Franks, Vandals, Goths, Angles, Saxons","begin":"418","end":"720"},{"fact":"Visigoths in Spain","begin":"477","end":"711"},{"fact":"Muslims in Spain","begin":"711","end":"1492"},{"fact":"Charles I (carlomagno)","begin":"742","end":"814"},{"fact":"House of Habsburg\/Austria","begin":"1100","end":"2000"},{"fact":"Ramon Berenguer I (Count of Barcelona)","begin":"1023","end":"1076"},{"fact":"Ramon Berenguer IV de Barcelona i Petronila d Arago","begin":"1137","end":"1137"},{"fact":"James I of Aragon Reign","begin":"1213","end":"1276"},{"fact":"Alfonso X the wise of Castile Reign","begin":"1252","end":"1284"},{"fact":"House of Bourbon","begin":"1268","end":"present"},{"fact":"Renaissance (art)","begin":"1300","end":"1602"},{"fact":"black death (bubonic)","begin":"1320","end":"1352"},{"fact":"Printing Press (difusion)","begin":"1440","end":"1440"},{"fact":"Colombus","begin":"1451","end":"1506"},{"fact":"Catholic Monarchs","begin":"1475","end":"1516"},{"fact":"America Discovery","begin":"1492","end":"1492"},{"fact":"Magellan","begin":"1480","end":"1521"},{"fact":"Elcano","begin":"1476","end":"1526"},{"fact":"Philip IV of France \/ Philip I of Spain","begin":"1478","end":"1506"},{"fact":"Charles I of Spain (V of Germany) Reign","begin":"1516","end":"1556"},{"fact":"First trip around the world","begin":"1519","end":"1522"},{"fact":"Protestant Reformation","begin":"1517","end":"1555"},{"fact":"Church of England","begin":"1530","end":"1530"},{"fact":"Council of Trent","begin":"1545","end":"1563"},{"fact":"Philip II of Spain Reign","begin":"1556","end":"1598"},{"fact":"Philip III Reign","begin":"1598","end":"1621"},{"fact":"Baroque-NeoClassicism","begin":"1600","end":"1800"},{"fact":"30 year war","begin":"1618","end":"1648"},{"fact":"Philip IV Reign","begin":"1621","end":"1665"},{"fact":"Charles II Reign","begin":"1665","end":"1700"},{"fact":"glorious revolution","begin":"1688","end":"1689"},{"fact":"[Central] Bank of England Creation","begin":"1694","end":"1694"},{"fact":"Spanish succession war","begin":"1701","end":"1715"},{"fact":"Almansa battle","begin":"1707","end":"1707"},{"fact":"Utrech Threaty","begin":"1715","end":"1715"},{"fact":"Adam Smith","begin":"1723","end":"1790"},{"fact":"Age of Enlightment\/Reason","begin":"1650","end":"1780"},{"fact":"Kant","begin":"1724","end":"1804"},{"fact":"Thomas Jefferson (US president)","begin":"1743","end":"1826"},{"fact":"seven years' war","begin":"1756","end":"1763"},{"fact":"Industrial Revolution","begin":"1760","end":"1820"},{"fact":"Andrew Jackson (US president)","begin":"1767","end":"1845"},{"fact":"Steam engine (Watt)","begin":"1778","end":"1778"},{"fact":"American Revolutionary War (Independence War)","begin":"1775","end":"1783"},{"fact":"US Independence","begin":"1776","end":"1776"},{"fact":"US Constitution","begin":"1787","end":"1787"},{"fact":"French Revolution","begin":"1789","end":"1799"},{"fact":"Napoleon","begin":"1769","end":"1821"},{"fact":"Simon Bolivar","begin":"1783","end":"1830"},{"fact":"First [Central] Bank of the US","begin":"1791","end":"1811"},{"fact":"Romanticism (art)","begin":"1800","end":"1850"},{"fact":"Ferdinand VII of Spain Reign","begin":"1808","end":"1833"},{"fact":"Mexican_War_of_Independence","begin":"1810","end":"1821"},{"fact":"Waterloo Battle","begin":"1815","end":"1815"},{"fact":"Lenin","begin":"1870","end":"1924"},{"fact":"Darwin","begin":"1809","end":"1882"},{"fact":"Karl Marx","begin":"1818","end":"1883"},{"fact":"Friedrich_Nietzsche","begin":"1844","end":"1900"},{"fact":"labor movement: socialism anarchism","begin":"1830","end":"1914"},{"fact":"Abraham Lincoln (US president)","begin":"1809","end":"1865"},{"fact":"Second [Central] Bank of US","begin":"1816","end":"1836"},{"fact":"US Free Banking Era","begin":"1837","end":"1862"},{"fact":"J.P. Morgan","begin":"1837","end":"1913"},{"fact":"J.D. Rockefeller","begin":"1839","end":"1937"},{"fact":"American Civil War","begin":"1861","end":"1865"},{"fact":"Internal Revenue Service (IRS)","begin":"1862","end":"1862"},{"fact":"US National [Central] Banks","begin":"1963","end":"1913"},{"fact":"henry ford","begin":"1863","end":"1947"},{"fact":"Gandhi","begin":"1869","end":"1948"},{"fact":"Freud","begin":"1856","end":"1939"},{"fact":"Ku Klux Klan","begin":"1865","end":"2015"},{"fact":"Realpolitik","begin":"1853","end":"1900"},{"fact":"Otto von Bismark","begin":"1815","end":"1940"},{"fact":"Germany/German-lang unification","begin":"1871","end":"1871"},{"fact":"Italy/Italian-lang unification","begin":"1870","end":"1931"},{"fact":"Alfonso XIII of Spain reign","begin":"1886","end":"1931"},{"fact":"Keynes","begin":"1883","end":"1946"},{"fact":"Hayek","begin":"1899","end":"1992"},{"fact":"European Imperialism. Colonization of Africa and Asia","begin":"1880","end":"1914"},{"fact":"Berlin conference","begin":"1885","end":"1885"},{"fact":"Suez Canal","begin":"1865","end":"1865"},{"fact":"First Spanish Republic","begin":"1873","end":"1874"},{"fact":"International Alliance of Women","begin":"1904","end":"1904"},{"fact":"US Bank Panic","begin":"1907","end":"1907"},{"fact":"Federal Reserve (fed, American Central Bank) creation","begin":"1913","end":"1913"},{"fact":"WWI","begin":"1914","end":"1918"},{"fact":"John F. Kennedy (US President)","begin":"1917","end":"1963"},{"fact":"influenza epidemic (flu pandemic)","begin":"1918","end":"1920"},{"fact":"Versailles","begin":"1919","end":"1919"},{"fact":"League of Nations","begin":"1920","end":"1946"},{"fact":"Ottoman Killing of ChristiansHitler","begin":"1889","end":"1945"},{"fact":"Musolini","begin":"1905","end":"1905"},{"fact":"Stalin","begin":"1878","end":"1953"},{"fact":"Churchill","begin":"1874","end":"1965"},{"fact":"Roosvelt","begin":"1882","end":"1945"},{"fact":"John Von Newman","begin":"1903","end":"1957"},{"fact":"Alan Turing","begin":"1912","end":"1954"},{"fact":"Great Depression","begin":"1929","end":"1939"},{"fact":"Second Spanish Republic","begin":"1931","end":"1939"},{"fact":"Spanish Civil War","begin":"1936","end":"1939"},{"fact":"Franco","begin":"1892","end":"1975"},{"fact":"Spain Franco's Dictatorship","begin":"1939","end":"1975"},{"fact":"WWII","begin":"1939","end":"1945"},{"fact":"Bretton Woods system","begin":"1944","end":"1971"},{"fact":"Hiroshima - Enola Gay and Nagasaki - Attomic Bombs","begin":"1945","end":"1945"},{"fact":"IMF creation","begin":"1945","end":"1945"},{"fact":"world bank creation","begin":"1947","end":"1947"},{"fact":"OECD","begin":"1948","end":"1948"},{"fact":"NATO","begin":"1949","end":"1949"},{"fact":"Warsaw Pact","begin":"1955","end":"1991"},{"fact":"Berlin Wall","begin":"1961","end":"1989"},{"fact":"Marshall Plan","begin":"1947","end":"1952"},{"fact":"Cold War","begin":"1947","end":"1991"},{"fact":"USSR","begin":"1922","end":"1991"},{"fact":"Ronald Regan","begin":"1911","end":"2004"},{"fact":"Margaret Thatcher","begin":"1925","end":"2013"},{"fact":"Vietnam War","begin":"1955","end":"1975"},{"fact":"European Union Formation","begin":"1958","end":"1958"},{"fact":"John F. Kennedy (US President) in office","begin":"1961","end":"1963"},{"fact":"Exhorbitant privilege (Gaulle predicting USD crisis)","begin":"1965","end":"1965"},{"fact":"US Black Civil Rigths","begin":"1968","end":"1968"},{"fact":"Richard Nixon (US President) in office","begin":"1969","end":"1974"},{"fact":"fiat currency (0% reserve ratio)","begin":"1971","end":"1971"},{"fact":"Spanish transition","begin":"1975","end":"1977"},{"fact":"Spanish Constitution of 1978","begin":"1978","end":"1978"},{"fact":"Maastricht Treaty","begin":"1992","end":"1992"},{"fact":"NAFTA","begin":"1994","end":"1994"},{"fact":"WTO Creation","begin":"1995","end":"1995"},{"fact":"ECB creation","begin":"1998","end":"1998"},{"fact":"NATO bombing of Yugoslavia","begin":"1999","end":"1999"},{"fact":"George W Bush (US presidnet) in office","begin":"2001","end":"2009"},{"fact":"US 9\/11 attacks","begin":"2001","end":"2001"},{"fact":"Euro Currency","begin":"2002","end":"2002"},{"fact":"Madrid 11M","begin":"2004","end":"2004"},{"fact":"7_July_2005_London_bombings","begin":"2005","end":"2005"},{"fact":"Lisbon Treaty","begin":"2007","end":"2007"},{"fact":"Lehman Brothers Bank crash","begin":"2008","end":"2008"}],"health":{"indicator":"population","indicator_sf":"population","last_year":2015,"previous_year":2014,"previous_year2":2013,"previous_year3":2012,"last_lustrum":2011,"last_decade":2006,"last_2decade":1996,"data_source":"wb","data":{"last_year":{"Afghanistan":null,"Argentina":null,"Australia":null,"Belgium":null,"Brazil":null,"Canada":null,"Switzerland":null,"China":null,"Germany":null,"Denmark":null,"Egypt":null,"Spain":null,"European Union":null,"Finland":null,"France":null,"United Kingdom":null,"Greece":null,"Indonesia":null,"India":null,"Italy":null,"Japan":null,"South Korea":null,"Mexico":null,"Netherlands":null,"Norway":null,"Pakistan":null,"Poland":null,"Portugal":null,"Russian Federation":null,"Saudi Arabia":null,"Sweden":null,"Turkey":null,"United States":null,"World":null,"South Africa":null},"previous_year":{"Afghanistan":0.0065012498763031,"Argentina":0.12855681299065,"Australia":4.6369900114672,"Belgium":3.4864007962754,"Brazil":4.1205123303046,"Canada":5.5162383922484,"Switzerland":4.8799165614205,"China":3.077996349701,"Germany":5.4909879021484,"Denmark":5.6240248694599,"Egypt":0.034580883922532,"Spain":2.3054543951716,"European Union":4.374583673278,"Finland":4.5122497377834,"France":4.4391048231621,"United Kingdom":5.4758084052865,"Greece":2.222731272688,"Indonesia":2.0358781374571,"India":2.0162437473248,"Italy":3.3619927270485,"Japan":4.3719789412996,"South Korea":5.2874573459592,"Mexico":2.1061118672495,"Netherlands":5.5358466488809,"Norway":5.9999897224693,"Pakistan":2.0135212644452,"Poland":4.1473366016583,"Portugal":3.2273735839002,"Russian Federation":4.1308835152639,"Saudi Arabia":4.2508280343248,"Sweden":4.6053252602555,"Turkey":0.10805803850055,"United States":5.5614460379859,"World":4.1103583931691,"South Africa":0.066627737129383},"previous_year2":{"Afghanistan":0.0064745738310043,"Argentina":4.1404429488894,"Australia":4.65788385937,"Belgium":3.4534016670596,"Brazil":4.1173831844299,"Canada":5.5086772615097,"Switzerland":4.8233631548652,"China":2.0679830819114,"Germany":5.4434384218579,"Denmark":5.5817010371093,"Egypt":0.031735664510858,"Spain":3.2856074955622,"European Union":4.3443189686967,"Finland":4.481286988032,"France":4.414526073644,"United Kingdom":4.4112901015171,"Greece":2.2124012793764,"Indonesia":2.0352275871992,"India":2.0141405256478,"Italy":3.344443252184,"Japan":5.375686662448,"South Korea":5.2528086165371,"Mexico":2.0989156776164,"Netherlands":4.5000773069019,"Norway":4.9999902754251,"Pakistan":2.0123926447187,"Poland":4.1339604414014,"Portugal":4.210223286101,"Russian Federation":4.1408729133719,"Saudi Arabia":4.2396623509686,"Sweden":3.5862192077729,"Turkey":2.1067182134187,"United States":5.5151986769647,"World":4.1034499134799,"South Africa":0.066990525020867},"previous_year3":{"Afghanistan":0.0067922162231992,"Argentina":4.1413537633121,"Australia":5.6660362124419,"Belgium":3.4404154072785,"Brazil":4.119691463556,"Canada":5.5192482725062,"Switzerland":4.8192659813367,"China":2.0616720709546,"Germany":5.4333234238878,"Denmark":4.5674775908131,"Egypt":0.03175476369799,"Spain":2.2820578069473,"European Union":3.3362393258409,"Finland":4.4668455227715,"France":4.4022042451836,"United Kingdom":4.4065774849894,"Greece":3.2189924288737,"Indonesia":2.0364256465702,"India":2.01426360832,"Italy":2.3430703802071,"Japan":4.4595959402977,"South Korea":4.2407648723687,"Mexico":2.0955299064665,"Netherlands":4.4871199483882,"Norway":5.9999901539627,"Pakistan":2.0124589860822,"Poland":3.1293872283913,"Portugal":3.2025960268789,"Russian Federation":4.1386108442108,"Saudi Arabia":3.2449909668382,"Sweden":4.562534405127,"Turkey":2.1048114164015,"United States":4.506634332655,"World":4.1030815265371,"South Africa":0.074742824423111},"last_lustrum":{"Afghanistan":0.006178264279134,"Argentina":4.1331533809557,"Australia":4.6185978085061,"Belgium":3.4742605164154,"Brazil":4.1296356594425,"Canada":4.5178769355863,"Switzerland":5.8749839121725,"China":2.0554131801683,"Germany":5.4567241133762,"Denmark":4.6095251278115,"Egypt":0.027995661552176,"Spain":2.3164921796445,"European Union":3.3603749474609,"Finland":4.5049615289021,"France":3.4355597795476,"United Kingdom":4.4078481644408,"Greece":2.2576549970905,"Indonesia":2.0362577416863,"India":2.0146224879399,"Italy":2.381445666819,"Japan":4.4593850922188,"South Korea":4.2401670562912,"Mexico":2.0965856452438,"Netherlands":4.5323013943,"Norway":5.9999900571829,"Pakistan":2.0122278299149,"Poland":3.1381071388828,"Portugal":3.2306111251754,"Russian Federation":4.1324669932081,"Saudi Arabia":3.2312211632996,"Sweden":4.5925191679582,"Turkey":2.1052264640758,"United States":3.4949569917949,"World":4.1030957532586,"South Africa":0.080336622648187},"last_decade":{"Afghanistan":0.0037677499179608,"Argentina":1.0895761527727,"Australia":4.4868650937705,"Belgium":3.5242058934049,"Brazil":1.078356125984,"Canada":5.5429766811598,"Switzerland":5.7737726805558,"China":3.0280805756824,"Germany":4.4917630877519,"Denmark":5.7021549702552,"Egypt":0.018999981506748,"Spain":3.3842909784232,"European Union":3.4143485844458,"Finland":5.5548113678842,"France":4.4930669616098,"United Kingdom":4.5738849094292,"Greece":2.3346186216788,"Indonesia":1.0214421426125,"India":2.0110063699585,"Italy":4.4509924226228,"Japan":5.459760078091,"South Korea":4.2822116400927,"Mexico":4.1169179081413,"Netherlands":5.5997861814723,"Norway":4.9999865073995,"Pakistan":2.0118188583714,"Poland":3.1214163988066,"Portugal":4.2674293411296,"Russian Federation":2.0933579258082,"Saudi Arabia":3.2000401710926,"Sweden":5.6241065993616,"Turkey":0.10424750692689,"United States":4.6265433025149,"World":4.1043964570065,"South Africa":0.076356204523676},"last_2decade":{"Afghanistan":null,"Argentina":3.1648265179,"Australia":3.4702143557631,"Belgium":3.5943105870578,"Brazil":2.1103547965184,"Canada":4.4533020455213,"Switzerland":5.999978545404,"China":2.0151475834846,"Germany":4.6557221331395,"Denmark":4.764850432123,"Egypt":0.022794079679982,"Spain":2.3483319216054,"European Union":3.4309494700222,"Finland":4.5530274244919,"France":3.5795800113385,"United Kingdom":3.4819026096992,"Greece":2.2949602559077,"Indonesia":2.0243781203888,"India":2.0087372077371,"Italy":2.4940458225393,"Japan":5.8028709755498,"South Korea":4.2843514355243,"Mexico":2.0886247674425,"Netherlands":5.6156968295925,"Norway":5.8006950342763,"Pakistan":1.0107862863654,"Poland":1.087230998006,"Portugal":4.2614041680719,"Russian Federation":1.0567023022627,"Saudi Arabia":4.1750476338729,"Sweden":4.6991251304628,"Turkey":1.0654686592365,"United States":4.6450802912237,"World":4.1159689561665,"South Africa":0.079149833721331},"data_source":"cult","indicator":"health","indicator_sf":"health(cult score)"}}};


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


function login_screen(){
	if(debug){alert('login_screen called');}
	header_zone.innerHTML='<h1>CULT Sign in</h1>';
	var invitee_access="";
	if(debug){
		invitee_access='<br /><button id="exit" class="button exit" onclick="invitee_access();">Invitee (offline)</button>';
	}
	canvas_zone_vcentered.innerHTML='\
	<div id="signinButton" class="button">login\
   <span class="icon"></span>\
    <span class="buttonText"></span>\
	</div>'+invitee_access+'\
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
		  <br /><button id="op_difficult" class="coolbutton">Difficult</button>\
		  <br /><button id="op_normal" class="coolbutton">Normal</button>\
		  <br /><button id="op_easy" class="coolbutton">Easy</button>\
		  <br /><br /><button id="go-back" class="minibutton fixed-bottom-right go-back">&lt;</button> \
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
    <br /><button id="go-back" class="minibutton fixed-bottom-right go-back">&lt;</button> \
    ';
    document.getElementById("go-back").addEventListener(clickOrTouch,function(){menu_screen();});
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
				  <br /><button id="go-back" class="minibutton fixed-bottom-right go-back">&lt;</button> \
				  ';
                document.getElementById("go-back").addEventListener(clickOrTouch,function(){menu_screen();});
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
		  <br /><button id="go-back" class="minibutton fixed-bottom-right go-back">&lt;</button> \
          ';
    document.getElementById("go-back").addEventListener(clickOrTouch,function(){menu_screen();});
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
		</ul>';
        header_zone.innerHTML='<div id="header_basic"><a id="hamburger_icon" onclick="hamburger_toggle(event)"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
        <path d="M2 6h20v3H2zm0 5h20v3H2zm0 5h20v3H2z"/></svg></a> <span id="header_text" onclick="menu_screen()">'+app_name+'</span></div> <div id="header_status"> </div>';
        header_text=document.getElementById('header_text');
		// Optionally if(is_app) we could completely remove header...
		canvas_zone_vcentered.innerHTML=' \
		<div id="menu-logo-div"></div> \
		<nav id="responsive_menu">\
		<br /><button id="start-button" class="coolbutton">Play</button> \
		<br /><button id="learn_menu" class="coolbutton">Learn</button> \
		<br /><button id="options" class="coolbutton">Options</button> \
		<br /><button id="top_scores" class="coolbutton">Top Scores</button> \
		</nav>\
		';
        document.getElementById("start-button").addEventListener(clickOrTouch,function(){play_game();});
        document.getElementById("learn_menu").addEventListener(clickOrTouch,function(){learn_menu();});
        document.getElementById("options").addEventListener(clickOrTouch,function(){options();});
        document.getElementById("top_scores").addEventListener(clickOrTouch,function(){top_scores();});
        data_map=offline_jsons["all_wb.json"];
		if(indicator_list.length==0){
            indicator_list=Object.keys(data_map);
            indicator_list.splice(indicator_list.indexOf('history'));
        } 
        if(country_list.length==0) load_country_list_from_indicator('population');
        if(period_list.length==0) load_period_list_from_indicator_ignore_last_year('population');
	}
}

function learn_menu(){
    canvas_zone_vcentered.innerHTML=' \
    <div id="menu-logo-div"></div> \
    <nav id="responsive_menu">\
    <br /><button id="show_geo" class="coolbutton">Learn Geo</button> \
    <br /><button id="show_geo_analysis" class="coolbutton">Geo Analysis</button> \
    <br /><button id="show_history" class="coolbutton">Learn Hist</button> \
    <br /><button id="go-back" class="minibutton fixed-bottom-right go-back">&lt;</button> \
    </nav>\
    ';
    document.getElementById("show_geo").addEventListener(clickOrTouch,function(){show_geo();});
    document.getElementById("show_geo_analysis").addEventListener(clickOrTouch,function(){show_geo_analysis();});
    document.getElementById("show_history").addEventListener(clickOrTouch,function(){show_history();});
    document.getElementById("go-back").addEventListener(clickOrTouch,function(){menu_screen();});
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

var show_geo=function(country){
    if(typeof(country)=='undefined') country='World';
    //make a function to show a colored string of curr vs last lustrum
	canvas_zone_vcentered.innerHTML=' \
     <form id="my-form" action="javascript:void(0);"> \
		<ul class="errorMessages"></ul>\
		<input id="new-country" autofocus type="text" name="q" placeholder="'+country+'" required="required" />\
			<input id="my-form-submit" type="submit" style="visibility:hidden;display:none" />\
            <button onclick="show_data_country()" class="coolbutton">&gt;</button>\
			</form>\
     <table style="width:90%;margin:0 auto;padding:0;font-size:12px;">\
     '+get_colored_indicator_row('population',country)+'\
     '+get_colored_indicator_row('surfacekm',country)+'\
     '+get_colored_indicator_row('popdensity',country)+'\
     '+get_colored_indicator_row('pop65',country)+'\
     '+get_colored_indicator_row('gdppcap',country)+'\
     '+get_colored_indicator_row('employed',country)+'\
     '+get_colored_indicator_row('unemployed',country,'inversed')+'\
     '+get_colored_indicator_row('inflation',country)+'\
     '+get_colored_indicator_row('health',country)+'\
     '+get_colored_indicator_row('debtgdp',country)+'\
     '+get_colored_indicator_row('surpdeficitgdp',country)+'\
     </table>\
     <button id="go-back" class="minibutton fixed-bottom-right go-back">&lt;</button>\
	';

/*
     '+get_colored_indicator_row('debtgdp',country)+'\
     '+get_colored_indicator_row('surpdeficitgdp',country)+'
*/
    var search_select = new autoComplete({
        selector: '#new-country',
        minChars: 1,
        source: function(term, suggest){
            term = term.toLowerCase();
            var choices = country_list;
            var suggestions = [];
            for (var i=0;i<choices.length;i++)
                if (~choices[i].toLowerCase().indexOf(term)) suggestions.push(choices[i]);
            suggest(suggestions);
        }
    });
    document.getElementById("go-back").addEventListener(clickOrTouch,function(){learn_menu();});
    document.getElementById('new-country').focus();
    document.getElementById('new-country').onkeypress = function(e){
        if (!e) e = window.event;
        var keyCode = e.keyCode || e.which;
        if (keyCode == '13'){
            search_select.destroy();
            show_data_country();
            return false;
        }
    }
}

var show_data_country=function(){
    var country=document.getElementById('new-country').value;
    show_geo(country.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();})); // title for multi token e.g., United States
    //   country.charAt(0).toUpperCase()+country.slice(1).toLowerCase() // only works for mono-token
}

var get_colored_indicator_row=function(indicator, country){
    var indic=indicator;
    var curr_period='last_year';
    var is_percentage=true;
    var num_decimals=0;
    if(data_map[indicator].data[curr_period][country]==null || data_map['health'].data[curr_period][country]==null){
        indic=indicator+' (-1y)';
        curr_period='previous_year';
    }
    if(data_map[indicator].data[curr_period][country]==null){
        indic=indicator+' (-2y)';
        curr_period='previous_year2';
    }
    if(indicator=='health' || indicator=='inflation' || indicator=='debtgdp' || indicator=='surpdeficitgdp'){
        num_decimals=1;
    }
    if(indicator=='gdppcap' || indicator=='population' || indicator=='surfacekm') is_percentage=false;
    if (data_map[indicator].data[curr_period][country]==null)
        return '<tr><td style="text-align:right;width:45%;">'+indic+':</td><td style="text-align:left;width:45%;">-</td></tr>';
    var curr_val=Number(data_map[indicator].data[curr_period][country]);
    var last_lustrum_val=Number(data_map[indicator].data.last_lustrum[country]);
    var last_decade_val=Number(data_map[indicator].data.last_decade[country]);
    var percentage_diff=num_representation(get_relative_diff_safe(last_lustrum_val,curr_val,is_percentage),0);
    var percentage_diff2=num_representation(get_relative_diff_safe(last_decade_val,curr_val,is_percentage),0);
    var val_diff="";
    var val_diff2="";
    if(!is_percentage){
        val_diff+= ' ['+num_representation(curr_val-last_lustrum_val,0)+']';
        val_diff2+=' ['+num_representation(curr_val-last_decade_val,0)+']';
    }
    var extra='';
    var lower_better=false;
    if(indicator=='gdppcap' || indicator=='employed' || indicator=='health' || indicator=='surpdeficitgdp'){
        if(percentage_diff.charAt(0)!='-' && percentage_diff2.charAt(0)=='-') extra='+tc';
        if(percentage_diff.charAt(0)=='-' && percentage_diff2.charAt(0)!='-') extra='-tc';
    }
    if(indicator=='unemployed' || indicator=='debtgdp'){
        lower_better=true;
        if(percentage_diff.charAt(0)!='-' && percentage_diff2.charAt(0)=='-') extra='-tc';
        if(percentage_diff.charAt(0)=='-' && percentage_diff2.charAt(0)!='-') extra='+tc';
    }
    return '<tr><td style="text-align:right;width:45%;">'+indic+':</td><td style="text-align:left;width:45%;">'+num_representation(curr_val,num_decimals)+' ('+get_formatted_diff_percentage(percentage_diff,lower_better)+val_diff+', '+get_formatted_diff_percentage(percentage_diff2,lower_better)+val_diff2+') '+extra+'</td></tr>';
}



// not so useful for percentages...
var get_relative_diff_safe=function(refval,newval,is_percentage){
    // if you are diffing 2 percentages it is clearer to just diff them
    // otherwise relative diff is problematic if ref in range [1,-1]
    // specially if 0
    // this is not perfect, there is no mathematical solution for this
    // only if you know all the values in the range you could normalize
    // and scale to 100% and then just add or subtract
    if(is_percentage) return (newval-refval);
    if(refval<1 && refval>-1){
            var norm=Math.abs(1-refval); // aproximatelly keep the magn
            refval+=norm;newval+=norm;
    }
    return 100*(newval-refval)/Math.abs(refval);
}

var get_formatted_diff_percentage=function(percentage_diff,lower_better){
    if(typeof(lower_better)=='undefined') lower_better=false;
    var pos_color='green';
    var neg_color='red';
    if(lower_better){
        pos_color='red';
        neg_color='green';
    }
    if(percentage_diff=="0" || percentage_diff=="-0"){percentage_diff="="}
    else{
        if((''+percentage_diff).indexOf('-')!=0){percentage_diff='<span style="color:'+pos_color+'">+'+percentage_diff+'%</span>';}
        else{percentage_diff='<span style="color:'+neg_color+'">'+percentage_diff+'%</span>';}
    }
    return percentage_diff;
}

var show_history=function(){
    alert('under construction');
}

var show_geo_analysis=function(){
    var keysSorted=[];
    keysSorted['health']=get_sorted_countries_indicator('health');
    keysSorted['gdppcap']=get_sorted_countries_indicator('gdppcap');
    keysSorted['unemployed']=get_sorted_countries_indicator('unemployed','asc');
    canvas_zone_vcentered.innerHTML=' \
    Analysis<br />\
    health: '+keysSorted['health'].slice(0,4)+' ... '+keysSorted['health'].slice(-4)+'<br />\
    gdppcap: '+keysSorted['gdppcap'].slice(0,4)+' ... '+keysSorted['gdppcap'].slice(-4)+'<br />\
    unemployment: '+keysSorted['unemployed'].slice(0,4)+' ... '+keysSorted['unemployed'].slice(-4)+'<br />\
     <button id="go-back" class="minibutton fixed-bottom-right go-back">&lt;</button>\
    ';
    document.getElementById("go-back").addEventListener(clickOrTouch,function(){learn_menu();});
}

var get_sorted_countries_indicator=function(indicator,direction){
    // pre-do the sortings when formatting the data new property sorted countries...
    if(typeof(direction)=='undefined') direction='desc';
    var curr_period='last_year';
    if(data_map[indicator].data[curr_period]['World']==null){
        curr_period='previous_year';
    }
    if(data_map[indicator].data[curr_period]['World']==null){
        curr_period='previous_year2';
    }
    var list=data_map[indicator].data[curr_period];
    var keysSorted = Object.keys(list).sort(function(a,b){return Number(list[b])-Number(list[a])});
    if(direction=='asc') keysSorted = Object.keys(list).sort(function(a,b){return Number(list[a])-Number(list[b])});
    var countries_to_del=['World'];
    for(var i=0;i<keysSorted.length;i++){
        if(list[keysSorted[i]]==null) countries_to_del.push(keysSorted[i]);
    }
    for(var i=0;i<countries_to_del.length;i++){
        keysSorted.splice(keysSorted.indexOf(countries_to_del[i]),1);
    }
    return keysSorted;
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
	  <progress id="time_left" value="0" max="'+countdown_limit_end_secs+'"></progress>\
        </div>\
        <button id="go-back" class="minibutton fixed-bottom-right go-back">&lt;</button> \
	';
    document.getElementById("go-back").addEventListener(clickOrTouch,function(){end_game();});
	//get elements
	dom_score_correct=document.getElementById('current_score_num');
	canvas_zone_question=document.getElementById('question');
	canvas_zone_answers=document.getElementById('answers');
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
			open_js_modal_content('<div class="js-modal-correct"><h1>CORRECT</h1>'+optional_msg+'<br /><button class="coolbutton" onclick="nextActivity()">OK</button></div>');
		}
	}else{
		activity_results.result="incorrect";
		lifes--;
		update_lifes_representation();
		if(session_data.mode!="test"){
			//audio_sprite.playSpriteRange("zfx_wrong"); // add a callback to move forward after the sound plays... <br />Correct answer: <b>'+correct_answer+'</b>
			open_js_modal_content('<div class="js-modal-incorrect"><h1>INCORRECT</h1> <br />'+optional_msg+'<br /><button class="coolbutton" onclick="nextActivity()">OK</button></div>');
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
		answer_msg='<br /><span>'+fact2.fact+'</span> (<b>'+fact2.begin+'</b> <--> '+fact2.end+')<br />was before<br /><span>'+fact1.fact+'</span> (<b>'+fact1.begin+'</b> <--> '+fact1.end+')<br />';
    }else{
		answer_msg='<br /><span>'+fact1.fact+'</span> (<b>'+fact1.begin+'</b> <--> '+fact1.end+')<br />was before<br /><span>'+fact2.fact+'</span> (<b>'+fact2.begin+'</b> <--> '+fact2.end+')<br />';
    }
	//if(!match_level_times_bigger_margin(session_data.level,times_bigger)){nextActivity();return;}
    activity_timer.start();
    canvas_zone_question.innerHTML='What was before?';
    canvas_zone_answers.innerHTML=' \
    <div id="answer1" class="answer aleft coolbutton">'+fact1.fact+'</div>\
    <div id="answer2" class="answer aright coolbutton">'+fact2.fact+'</div>\
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
		answer_msg='<br /><span>'+period_map[period1]+'</span> <b>'+times_bigger+' times bigger</b> than <span>'+period_map[period2]+'</span><br />';
    }else if(Number(data_map[indicator].data[period1][country])<Number(data_map[indicator].data[period2][country])){
        correct_answer=period_map[period2];
		times_bigger=calculate_times_bigger(data_map[indicator].data[period2][country],data_map[indicator].data[period1][country]);
		answer_msg='<br /><span>'+period_map[period2]+'</span> <b>'+times_bigger+' times bigger</b> than <span>'+period_map[period1]+'</span><br />';
    }
	if(!match_level_times_bigger_margin(session_data.level,times_bigger)){nextActivity();return;}
	answer_msg+=add_answer_details(indicator,period1,period2,country,country);

    activity_timer.start();
    canvas_zone_question.innerHTML='When was <b>'+country+' '+data_map[indicator].indicator_sf+'</b> bigger?';
    canvas_zone_answers.innerHTML=' \
    <div id="answer-'+period_map[period2]+'" class="answer aleft coolbutton">'+period_map[period2]+'</div>\
    <div id="answer-'+period_map[period1]+'" class="answer aright coolbutton">'+period_map[period1]+'</div>\
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
    console.log(indicator);
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
		answer_msg='<br /><span>'+country1+'</span> <b>'+times_bigger+' times bigger</b> than '+country2+'<br />';
    }else if(Number(data_map[indicator].data[period][country1])<Number(data_map[indicator].data[period][country2])){
        correct_answer=country2;
		times_bigger=calculate_times_bigger(data_map[indicator].data[period][country2],data_map[indicator].data[period][country1]);
		answer_msg='<br /><span>'+country2+'</span> <b>'+times_bigger+' times bigger</b> than <span>'+country1+'</span><br />';
    }
	if(!match_level_times_bigger_margin(session_data.level,times_bigger)){nextActivity();return;}
	answer_msg+=add_answer_details(indicator,period,period,country1,country2);

    activity_timer.start();
    canvas_zone_question.innerHTML='Which is bigger in '+data_map[indicator].indicator_sf+' ('+period_map[period]+')?';
    canvas_zone_answers.innerHTML='\
    <div id="answer-'+country1+'" class="answer aleft coolbutton">'+country1+'</div>\
    <div id="answer-'+country2+'" class="answer aright coolbutton">'+country2+'</div>\
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

var xllions=function(int_num,decimals,decimal_symbol,magnitude){
    var ret=int_num.substr(0,int_num.toString().length-magnitude);
    if(decimals>0) ret=ret+decimal_symbol+int_num.substr(int_num.length-magnitude,1);
    return ret;
}

var num_representation=function(num, decimals, decimal_symbol,thousand_symbol){
	// standard solution toLocaleString... but not supported in saffary
    decimals = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
    decimal_symbol = decimal_symbol == undefined ? "." : decimal_symbol;
    thousand_symbol = thousand_symbol == undefined ? "," : thousand_symbol; 
    var sign = num < 0 ? "-" : "";
    if(decimals==0) sign = num <-0.5 ? "-" : "";
	var integer_part=parseInt(num = Math.abs(+num || 0).toFixed(decimals)) + "";
	var thousand_rest=(thousand_rest = integer_part.length) > 3 ? thousand_rest % 3 : 0;
	var result=sign + (thousand_rest ? integer_part.substr(0,thousand_rest) + thousand_symbol : "")
           + integer_part.substr(thousand_rest).replace(/(\d{3})(?=\d)/g, "$1" + thousand_symbol)
           + (decimals ? decimal_symbol + Math.abs(num - integer_part).toFixed(decimals).slice(2):"")
	if (integer_part.toString().length>12) result=xllions(integer_part,1,decimal_symbol,12)+" Trillions";
	else if (integer_part.toString().length>9)  result=xllions(integer_part,1,decimal_symbol,9)+" Billions";
	else if (integer_part.toString().length>6)  result=xllions(integer_part,1,decimal_symbol,6)+" Millions";
	
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
		<br /><button id="go-back" class="minibutton fixed-bottom-right go-back">&lt;</button>';
        document.getElementById("go-back").addEventListener(clickOrTouch,function(){menu_screen();});
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
		canvas_zone_vcentered.innerHTML+='Sent!<br /><button id="go-back" class="minibutton fixed-bottom-right go-back">&lt;</button> ';
        document.getElementById("go-back").addEventListener(clickOrTouch,function(){menu_screen();});
	};

}





