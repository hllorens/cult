"use strict";


/**TODO "Economy is build and ruled by persons" (human world)
* FIRST OF ALL CORRECT THE BOXES AND MAKE IT WRAP ASSETS AND ALL
* -Replace show account by show element (elements are the actors of the economy)
* -The element can be a: peson or company (owned by people, if public==all, if private==some)
* -The elements have an account (money: either in banks or poket) and own assets (products [can be consumed], properties)
* -Need to model space/land (e.g., add some properties equally divided e.g., 1000m)
* -Need to model time (e.g., that way births/deaths, payments are controlled)
People working age initialization

Adan and eve. Aged 18 both.
No gov no central bank yet.
They own all the land (square pixels as km2 of 250x250=42500km2)  equally divided.

They are the first farmers and can have children

Basic food is apple and to survive they eat 1kg a day
No... apples are not durable... wheat? Grain? Crop? Nuts?

They can produce superavit and then we can have gov and banks...

*/
function toggle_element_visibility(elemid){
    var elem=document.getElementById(elemid);
    var elemlink=document.getElementById(elemid+'-link');
    if(elem.style.display=="none"){
        elem.style.display="block";
        elemlink.innerHTML=elemlink.innerHTML.replace("+","-");
    }else{
       elem.style.display="none";
       elemlink.innerHTML=elemlink.innerHTML.replace("-","+");
    }
    return false;
}

var AccountEntry = function (concept, related_account, amount){
    this.concept=concept;
    this.related_account=related_account;
    this.amount=Number(amount); //#positive debit, negative credit
}

var Account = function (name){
    this.name=name;
    this.entries=[];
}
Account.prototype.balance=function(){
    var ret=0;
    for(var i=0;i<this.entries.length;i++){
        ret+=this.entries[i].amount;
    }
    return ret; //return (this.debit - this.credit);
}

// property TODO integrate products and assets in just one class
var Asset = function (market,units,uprice,rel_elem){
    if(typeof(uprice)=='undefined') uprice=0;
    if(typeof(rel_elem)=='undefined') rel_elem='';
    this.market=market;
    this.units=Number(units); // always positive
    this.uprice=Number(uprice); // only money assets do have a value (deposits/loans)
    this.rel_elem=rel_elem; // only loans/deposits have a rel_element
}

// market element
// TODO ---> product should probably be just an asset 
var Product = function (market, owner, units, uprice){
    this.market=market;
    this.owner=owner;
    this.units=Number(units); // always positive
    this.uprice=Number(uprice); // always positive
}

var Market = function (name){
    this.name=name;
    this.demand=[]; // current wants
    this.supply=[]; // available production
}
Market.prototype.demand_supply_balance=function(){
    var ret=0;
    for(var i=0;i<this.demand.length;i++){
        ret-=this.demand[i].units;
    }
    for(var i=0;i<this.supply.length;i++){
        ret+=this.supply[i].units;
    }
    return ret; //return (this.debit - this.credit);
}
Market.prototype.remove_supply=function(supply_item_owner,units,uprice){
    var removed=false;
    for(var i=0;i<this.supply.length;i++){
        var product=this.supply[i];
        if(product.owner==supply_item_owner &&
           product.units>=units && product.uprice==uprice){
               if(product.units==units){
                   this.supply.splice(i,1);
               }else{
                   product.units-=units;
               }
               removed=true;
               break;
           }
    }
    if(!removed) throw Error("Unable to remove supply "+this.name);
}



var Transaction = function (concept, account_from,account_to, amount){
    this.concept=concept;
    this.account_from=account_from;
    this.account_to=account_to;
    this.amount=amount;
    this.timestamp=get_timestamp_str();
}

var transactions=[];


/*This below can be simplified...*/
var markets={
/*    "add": function(name){
                        if(markets.hasOwnProperty(name)) throw Error('Market '+name+' already exists');
                        markets[name]=new Market(name);
                        return markets[name];
                    },
    "remove": function(name){
                    if(!markets.hasOwnProperty(name)) throw Error('Market '+name+' does not exist');
                    delete markets[name];
                    return;
                }*/
}

var elements={
    "add": function(name,element){
                        if(elements.hasOwnProperty(name)) throw Error('Element '+name+' already exists');
                        elements[name]=element;
                        return elements[name];
                    },
    "remove": function(name){
                    if(!elements.hasOwnProperty(name)) throw Error('Element '+name+' does not exist');
                    delete elements[name];
                    return;
                }    
}
/****************************************************/

var are_accounts_balanced=function(){
        var temp_accounts_balance={};
        for(var prop in elements) {
            if (elements.hasOwnProperty(prop) && typeof(elements[prop])!='function') {
                console.log(prop);
                for(var i=0;i<elements[prop].account.entries.length;i++){
                    if(elements[prop].account.entries[i].amount==0){alert(elements[prop].name+" entry"+i+" '"+elements[prop].account.entries[i].concept+"' is 0");return false;}
                    if(!temp_accounts_balance.hasOwnProperty(elements[prop].name)) temp_accounts_balance[elements[prop].name]=0;
                    if(!temp_accounts_balance.hasOwnProperty(elements[prop].account.entries[i].related_account)) temp_accounts_balance[elements[prop].account.entries[i].related_account]=0;
                    temp_accounts_balance[elements[prop].name]+=elements[prop].account.entries[i].amount; // add to the temp to count
                    temp_accounts_balance[elements[prop].account.entries[i].related_account]+=elements[prop].account.entries[i].amount; // add to the related account to cancel
                }
            }
       }
        for(var prop in temp_accounts_balance) {
            if (temp_accounts_balance.hasOwnProperty(prop)) {
                if(temp_accounts_balance[prop]!=0){
                    alert(prop+" balanced balance is not 0! it is "+temp_accounts_balance[prop])
                }
            }
       }
       alert("Accounts balances to 0. VALID! SUCCESS");
       return true;
}


var operation_prefix='op_'
var objectOperations=function(obj) {
    var result = [];
    for(var prop in obj.__proto__) {
        if (obj.__proto__.hasOwnProperty(prop) && 
        prop.slice(0,operation_prefix.length)==operation_prefix &&
        typeof(obj[prop])==='function') {
            result.push(prop.slice(operation_prefix.length));
        }
   }
   return result;
}



var page_div=document.getElementById('page');




var get_elements_matching=function(obj, needle){
    var matching=[];
    for(var prop in obj) {
        if (obj.hasOwnProperty(prop)) {
            if(prop.match(needle)){
                matching.push(prop);
            }
        }
    }
    return matching;
}

var get_elements_type=function(obj, type){
    var matching=[];
    for(var prop in obj) {
        if (obj.hasOwnProperty(prop)) {
            if(obj[prop].constructor===type){
                matching.push(prop);
            }
        }
    }
    return matching;
}

var show_element=function(name, optional_description){
    if(!elements.hasOwnProperty(name)){throw Error("Element "+name+" not found.");}
    if (typeof(optional_description)==='undefined'){optional_description="";}
    var element=elements[name];
	var use_class='';
	if(element.constructor===Person){use_class='person_box';}
	if(element.constructor===Bank){use_class='bank_box';}
    var html_ret='<div class="elem_box '+use_class+'">';
    html_ret+='<h1>'+element.name+' '+optional_description+'</h1>'; 
	if(element.constructor===Bank){html_ret+='<span>interest: '+element.interest+'%</span><br /><span>reserv: $'+element.reserves+'</span><br /><span>excess: $'+element.excess+'</span>';}   
	html_ret+=show_assets(name);
	html_ret+=show_account(name);
    html_ret+='</div>';
    return html_ret;
}


var show_account=function(name){
	var account=elements[name].account;
    var html_ret='<h1>account <a id="ac-'+name+'-link" href="#" onclick="toggle_element_visibility(\'ac-'+name+'\')" style="text-decoration:none; color:#fff;">+</a></h1>'; 
    html_ret+='<div id="ac-'+name+'" class="entries" style="display:none;">';
    for(var i=0;i<account.entries.length;i++){
        html_ret+=account.entries[i].concept+' units='+account.entries[i].amount+' rel_ac='+account.entries[i].related_account+'<br />';
    }
    html_ret+='-------------<br/>';
    html_ret+='</div>';
    var balance=account.balance();
    var color_class='color-green';
    if(balance<0){color_class='color-red';}
    html_ret+='<span class="color:'+color_class+'">'+balance+'</span>';
    return html_ret;
}

var get_classified_assets=function(name,class_arr){
    if(!elements.hasOwnProperty(name)){throw Error("Assets "+name+" not found.");}
    var element=elements[name];
    // elements that do not fall in any of the classes in class_arr will be "other"
    var classification={"other":{"assets":[],"value":0,"show_str":""}};
    for(var i=0;i<class_arr.length;i++){
        if(class_arr[i]=="other"){throw Error("get_classified_assets: class arr cannot contain 'other' since that class is added by default.");}
        classification[class_arr[i]]={"assets":[],"value":0,"show_str":""};
    }
    for(var i=0;i<element.assets.length;i++){
        var curr_class="other";
        if(classification.hasOwnProperty(element.assets[i].market)){
            curr_class=element.assets[i].market;
        }
        classification[curr_class].assets.push(element.assets[i]);
        classification[curr_class].value+=element.assets[i].units*element.assets[i].uprice;
        classification[curr_class].show_str+=element.assets[i].market+' '+element.assets[i].units+'('+element.assets[i].uprice+')  '+element.assets[i].rel_elem+'<br />';
    }
    // add div wrappers for classes in the str
    for(var i=0;i<class_arr.length;i++){
        classification[class_arr[i]].show_str='<h1 class="asset_box">'+class_arr[i]+' ('+classification[class_arr[i]].assets.length +', $'+classification[class_arr[i]].value+') <a id="as-'+name+class_arr[i]+'-link" href="#" onclick="toggle_element_visibility(\'as-'+name+class_arr[i]+'\')" style="text-decoration:none; color:#fff;">+</a></h1>\
              <div id="as-'+name+class_arr[i]+'" class="entries" style="display:none;">'+classification[class_arr[i]].show_str+'</div>'; 
    }
        classification['other'].show_str='<h1 class="asset_box">other ('+classification['other'].assets.length +', $'+classification['other'].value+') <a id="as-'+name+'other-link" href="#" onclick="toggle_element_visibility(\'as-'+name+'other\')" style="text-decoration:none; color:#fff;">+</a></h1>\
              <div id="as-'+name+'other" class="entries" style="display:none;">'+classification['other'].show_str+'</div>'; 

    return classification;
}

var show_assets=function(name){
    if(!elements.hasOwnProperty(name)){throw Error("Assets "+name+" not found.");}
    var element=elements[name];
    /*classified in deposits, loans and other*/
    var classified_assets=get_classified_assets(name,['deposits','loans']);  
    var html_ret=classified_assets.deposits.show_str;
    html_ret+=classified_assets.loans.show_str;
    html_ret+=classified_assets.other.show_str;
    return html_ret;
}

var show_operations=function(name){
    var content="";
    var operations=objectOperations(elements[name]);
    for(var i=0;i<operations.length;i++){
        content+='<button onclick="elements.'+name+'.'+operation_prefix+operations[i]+'()">'+operations[i]+'</button>';
    }
    content+='<br />';
    open_js_modal_content_accept(content);
}


var show_market=function(name, optional_description){
    if(!markets.hasOwnProperty(name)){throw Error("Market "+name+" not found.");}
    if (typeof(optional_description)==='undefined'){optional_description="";}
    var market=markets[name];
    var html_ret='<div class="market_box">';
    html_ret+='<h1>'+market.name+' '+optional_description+'</h1>'; 
    html_ret+='<div class="demand">';   
    for(var i=0;i<market.demand.length;i++){
        html_ret+=market.demand[i].owner+' '+market.demand[i].units+' rel '+market.demand[i].unit_price+'<br />';
    }
    html_ret+='</div>';
    html_ret+='<div class="supply">';   
    for(var i=0;i<market.supply.length;i++){
        html_ret+=market.supply[i].owner+' units='+market.supply[i].units+' uprice='+market.supply[i].unit_price+'<br />';
    }
    html_ret+='</div>';    
    html_ret+='-------------<br/>';
    var balance=market.demand_supply_balance();
    var color_class='color-green';

    if(balance<0){color_class='color-red';}
    html_ret+='<span class="color:'+color_class+'">'+balance+'</span></div>';
    return html_ret;
}


var show_buyer_market_supply=function(buyer, name){
    if(!markets.hasOwnProperty(name)){throw Error("Market "+name+" not found.");}
    var market=markets[name];
    var html_ret='<div class="market_box">';
    html_ret+='<h1>'+market.name+'</h1>'; 
    html_ret+='<div class="supply">';   
    for(var i=0;i<market.supply.length;i++){
        html_ret+='<button onclick="exchange(\''+buyer+'\',\''+market.name+'\',\''+market.supply[i].owner+'\',\''+market.supply[i].units+'\',\''+market.supply[i].unit_price+'\')">'+market.supply[i].owner+' units='+market.supply[i].units+' uprice='+market.supply[i].unit_price+'</button><br />';
    }
    html_ret+='</div>';    
    return html_ret;
}

//transaction
var exchange=function(buyer,market_name,seller,units,uprice){
    console.log("exchanging "+units+" "+market_name+" from "+seller+" to "+buyer+" for "+uprice);
    markets[market_name].remove_supply(seller,units,uprice);
    exchange_asset(seller,buyer, market_name,units,uprice);
    var value=units*uprice;
    transactions.push(new Transaction("exchanging "+units+" "+market_name+" from "+seller+" to "+buyer+" for "+uprice,seller,buyer,value));
    elements[seller].account.entries.push(new AccountEntry('selling '+market_name,buyer,value));
    elements[buyer].account.entries.push(new AccountEntry('buying '+market_name,seller,-value));

    show_situation();
}





var consume=function(){
    // make an asset/product disapear (good/service)
    // eg eat an apple
}

var buy_existing=function(buyer){
    page_div.innerHTML=buyer+" ...BUYING... Markets:<br />"; 
    var markets_arr=objectProperties(markets);
    for(var i=0;i<markets_arr.length;i++){
        page_div.innerHTML+=show_buyer_market_supply(buyer,markets_arr[i]);
    }
    page_div.innerHTML+='<br /><button onclick="show_situation()">volver</button>';
}

var sell_asset=function(seller){
    page_div.innerHTML=seller+" ...OFFERING... assets:<br />"; 
    for(var i=0;i<elements[seller].assets.length;i++){
        page_div.innerHTML+='<button>'+elements[seller].assets[i].market+' units='+elements[seller].assets[i].units+'</button>';
    }
    page_div.innerHTML+='<br /><br /><button onclick="show_situation()">volver</button>';
}


var Person = function (name){
    this.name=name;
	this.account=new Account(name);
    this.assets=[];
}
Person.prototype.op_buy=function(){
        remove_modal();
        buy_existing(this.name)
}
Person.prototype.op_sell=function(){
        remove_modal();
        sell_asset(this.name)
}
Person.prototype.op_create_business=function(){
        remove_modal();
        alert("under construction, this will cost 60k anonymous business, next shows a choice of business types, could even create a bank");
}
Person.prototype.op_take_out_loan=function(){
    var amount = prompt("Loan amount $", "");
    var bank = prompt("Select a bank", "");
    if(elements[bank].excess>=amount){ 
        // interest magic here...  this should also check if the customer is solvent (e.g., has enough money to pay interest immediately)
        console.log("new loan $"+amount+" from "+bank+" to "+this.name);
        transactions.push(new Transaction("new loan $"+amount+" from "+bank+" to "+this.name,bank,this.name,amount));
        elements[this.name].account.entries.push(new AccountEntry('borrowing $'+amount+' from '+bank,bank,amount));
        elements[bank].account.entries.push(new AccountEntry(this.name+' borrows $'+amount,this.name,-amount));
        create_asset(this.name,'loans', 1,-Number(amount),bank);
        create_asset(bank,'loans', 1,Number(amount),this.name);
		var interest=Number(amount)*elements[bank].interest;
        create_asset(this.name,'interest', 1,-1*interest,bank);
        create_asset(bank,'interest', 1,interest,this.name);
        elements[bank].update_precalculated();
    }else{
        alert("bank has not enough money (excess)");
    }
    show_situation();
}
Person.prototype.op_pay_debt=function(){
	alert('create a select to pay certain debt');
}



Person.prototype.op_make_deposit=function(amount,bank){ //i.e., transfer
    if(typeof(amount)=='undefined') amount = prompt("Deposit amount $", "");
    if(typeof(bank)=='undefined') bank = prompt("Select a bank", "");
    if(this.account.balance()>=amount){ 
        console.log("new deposit $"+amount+" from "+this.name+" to "+bank);
        transactions.push(new Transaction("new deposit $"+amount+" from "+this.name+" to "+bank,this.name,bank,amount));
        elements[this.name].account.entries.push(new AccountEntry('depositing $'+amount+' in '+bank,bank,-amount));
        elements[bank].account.entries.push(new AccountEntry(this.name+' deposits $'+amount,this.name,amount));
        create_asset(this.name,'deposits', 1,Number(amount),bank);
        create_asset(bank,'deposits', 1,-Number(amount),this.name);
        elements[bank].update_precalculated();
    }else{
        alert("yon don't have so much money to deposit");
    }
    /*SEE THE PAPER SHEET.
    This goes to a special place in a bank entity "deposits" (not the account) and it shows who owns how much in deposits (asset, checkable money)
    from these deposits the bank gets its own reseves and excess (not the account)
    Then we have a special place "loans" which decrease the excess (if no excess, no loans)
    Only the earned interest will go to the account when it is payed (and the account money of the bank as an organization can be itself deposited in their own bank and if that happens, then part of it can be re-lended...)
    think of the account as "bank poket" (owned by the bank owners), THE OPERATING LIQUIDITY, this can be used to:
        be depoisted and create more loans gaining more interest back to the pocket
        to pay salaries
        to pay dividends to bank owners (note that the first capital is the legal business capital and it is used as operating liquid... so normally no dividends are payed if the amount is lower than the initial calpital e.g., 60k)
    
    MUST CREATE AN ASSET IN FORM OF DEPOSIT IN THE PERSON... 
    ...and must create a liability in the bank... somehow these must be linked... (in account entries they seem linked but this is more like a special kind of asset)
    MONEY ASSET/LIABILITY (is like an asset IOU or YOM, with a value, and with an owner (acreedor "creditor" or debtor))
        To simplify we could just place "rel_entity" so that when that money exchanges are created we create 2 of these entries
        This resembles a lot to account entries but for "credit" instead of "base money" created by central bank.
        LETS DESIGN this in a paper sheet...*/
    show_situation();
}
Person.prototype.op_consume=function(asset,units){
    if(typeof(asset)=='undefined') amount = prompt("asset to consume", "");
    if(typeof(units)=='undefined') bank = prompt("units", "");
    console.log(this.name+" consumes "+units+" "+asset);
    destroy_asset(this.name,asset, units); // asset_name == market_name (in the future this could change to add complexity...)
}
Person.prototype.op_withdraw=function(){
	var amount = prompt("Withdraw amount $", "");   
}



var CentralBank=function(name){
    this.name=name;
	this.account=new Account(name);
    this.assets=[];
    this.funds_interest=0.01; // fixed interet for inter-bank borrowings (banks borrow from other bank when they are falling short on reserves but still have loan demands they would like to cover)
    this.discount_interest=0.02; // fixed interest when banks borrow from CB directly (always higher than funds interest since the bank should be in real trouble if no other bank wants to lend them money)
    this.reserve_ratio=0.1;
}
CentralBank.prototype.op_buy=function(){
        remove_modal();
        buy_existing(this.name)
}
CentralBank.prototype.op_modify_interest=function(){alert("trying to modify interest");}
CentralBank.prototype.op_modify_reserve_fraction=function(){alert("trying to modify_reserve_fraction");}


var Bank=function(name){
    this.name=name;
	this.account=new Account(name);
    this.assets=[];
    this.interest=0.03;
    
    /*pre-calculated*/
    this.classified_assets=undefined;
    this.reserves=0;
    this.excess=0;
}
Bank.prototype.update_precalculated=function(){
    this.classified_assets=get_classified_assets(this.name,['deposits','loans']); 
    this.reserves=-1*this.classified_assets.deposits.value*elements.central_bank.reserve_ratio;
    this.excess=-1*this.classified_assets.deposits.value-this.reserves-this.classified_assets.loans.value;
}
Bank.prototype.reserves=function(){
    return this.account.balance()*elements.central_bank.reserve_ratio;
}
Bank.prototype.excess=function(){
    this.account.balance()-this.reserves();
}
Bank.prototype.op_buy=function(){
        remove_modal();
        buy_existing(this.name)
}
Bank.prototype.op_modify_interest=function(){
    this.interest=prompt("New interest %", "");
}
Bank.prototype.op_take_out_loan_from_another_bank=function(){
	// TODO add parameters and handle well
    var amount = prompt("Loan amount $", "");
    var bank = prompt("Select a bank", "");
	if(bank==undefined || bank==this.name){return;}
    if(elements[bank].excess>=amount){ 
        // interest magic here...  this should also check if the customer is solvent (e.g., has enough money to pay interest immediately)
        console.log("new loan $"+amount+" from "+bank+" to "+this.name);
        transactions.push(new Transaction("new loan $"+amount+" from "+bank+" to "+this.name,bank,this.name,amount));
        elements[this.name].account.entries.push(new AccountEntry('borrowing $'+amount+' from '+bank,bank,amount));
        elements[bank].account.entries.push(new AccountEntry(this.name+' borrows $'+amount,this.name,-amount));
        create_asset(this.name,'loans', 1,-Number(amount),bank);
        create_asset(bank,'loans', 1,Number(amount),this.name);
		var interest=Number(amount)*elements.central_bank.funds_interest;
        create_asset(this.name,'interest', 1,-1*interest,bank);
        create_asset(bank,'interest', 1,interest,this.name);
        elements[bank].update_precalculated();
        elements[this.name].update_precalculated();
    }else{
        alert("bank has not enough money (excess)");
    }
    show_situation();
}

Bank.prototype.op_take_out_loan_from_CB=function(){
	// TODO add parameters and handle well
    var amount = prompt("Loan amount $", "");
    console.log("new loan $"+amount+" from CB to "+this.name);
    transactions.push(new Transaction("new loan $"+amount+" from CB to "+this.name,'central_bank',this.name,amount));
    elements[this.name].account.entries.push(new AccountEntry('borrowing $'+amount+' from central_bank','central_bank',amount));
    elements.central_bank.account.entries.push(new AccountEntry(this.name+' borrows $'+amount,this.name,-amount));
    create_asset(this.name,'loans', 1,-Number(amount),'central_bank');
    create_asset('central_bank','loans', 1,Number(amount),this.name);
	var interest=Number(amount)*elements.central_bank.discount_interest;
    create_asset(this.name,'interest', 1,-1*interest,'central_bank');
    create_asset('central_bank','interest', 1,interest,this.name);
    elements[this.name].update_precalculated();
    show_situation();
}



// this should be common to all elements...
var destroy_asset=function(element_from_name,market, units){
    var removed=false;
    for(var i=0;i<elements[element_from_name].assets.length;i++){
        var asset=elements[element_from_name].assets[i];
        if(asset.market==market && asset.units>=units){
            if(asset.units==units){
               elements[element_from_name].assets.splice(i,1);
            }else{
               asset.units-=units;
            }
            removed=true;
            break;
        }
    }
    if(!removed) throw Error("Unable to remove asset from "+element_from_name);
}
var create_asset=function(element_to_name,market, units,uprice,rel){
	if(typeof(rel)=='undefined') rel='';
    var added=false;
    for(var i=0;i<elements[element_to_name].assets.length;i++){
        var asset=elements[element_to_name].assets[i];
        if(asset.market==market && asset.rel_elem==rel){
            if(uprice!=0)
                asset.uprice+=uprice;
            else{
                asset.units+=units;
            }
            added=true; break;
        }
    }
    if(!added) elements[element_to_name].assets.push(new Asset(market,units,uprice,rel));
}

var exchange_asset=function(element_from_name,element_to_name,market, units,uprice){
    destroy_asset(element_from_name,market, units);
    create_asset(element_to_name,market, units, uprice);
}






var GovTreasury=function(name){
    this.name=name;
	this.account=new Account(name);
    this.assets=[];
}
GovTreasury.prototype.op_issue_bond=function(units,uprice){
        remove_modal();
        if (typeof(units)=='undefined'){units=prompt("units", "1");	if(units==undefined) return;}
        if (typeof(uprice)=='undefined'){uprice=prompt("uprice", "1000");if(uprice==undefined) return;}
        var bond=new Product("bonds",this.name, units, uprice); 
        markets.bonds.supply.push(bond);
        this.assets.push(bond);
        show_situation();
    }
GovTreasury.prototype.op_issue_labor=function(units,uprice){
        remove_modal();
        if (typeof(units)=='undefined'){units=prompt("units", "1");if(units==undefined) return;}
        if (typeof(uprice)=='undefined'){uprice=prompt("salary (negative) $", "-60000");if(uprice==undefined) return;}
        var value=-1*units*uprice;
        if(value>this.account.balance()){
            alert("Insufficient money!");
        }else{
            var labor=new Product("labor",this.name, units, uprice); 
            markets.labor.supply.push(labor);
            this.assets.push(labor);
        }
        show_situation();
}
GovTreasury.prototype.op_collect_taxes=function(){alert("collecting_taxes");},
GovTreasury.prototype.op_invest=function(){alert("investing");}

// initialize
elements.add("central_bank", new CentralBank("central_bank"));
elements.add("gov_treasury", new GovTreasury("gov_treasury"));
elements.add("adam", new Person("adam"));
elements.add("eve", new Person("eve"));
markets["bonds"]=new Market("bonds");
markets["labor"]=new Market("labor");
markets["wheat"]=new Market("wheat");
markets["crops"]=new Market("crops");

function show_situation(){
    page_div.innerHTML=""; //&lt;ECONOMY&gt;
    var markets_arr=objectProperties(markets);
    for(var i=0;i<markets_arr.length;i++){
        page_div.innerHTML+=show_market(markets_arr[i]);
    }

    // high entities
    page_div.innerHTML+="<br />";
    page_div.innerHTML+=show_element("central_bank",'<button id="central_bank" onclick="show_operations(this.id)">+</button>'); //,"loan interest ..., checking accounts 0%");
    page_div.innerHTML+=show_element("gov_treasury",'<button id="gov_treasury" onclick="show_operations(this.id)">+</button>');   
    var banks=get_elements_type(elements,Bank);
	for(var i=0;i<banks.length;i++){
	    page_div.innerHTML+=show_element(banks[i],'<button id="'+banks[i]+'" onclick="show_operations(this.id)">+</button>'); 
	}

    // business

    
    // people
	page_div.innerHTML+="<br />";
    var persons=get_elements_type(elements,Person);
	for(var i=0;i<persons.length;i++){
	    page_div.innerHTML+=show_element(persons[i],'<button id="'+persons[i]+'" onclick="show_operations(this.id)">+</button>'); 
	}

    // THEN ALSO MODEL PROPERTIES(houses,cars), stocks (company capital, so everyone can be a bank owner)
    var m0=calc_m0();
    var m1=calc_m1();
	page_div.innerHTML+="<br /><br />";
	page_div.innerHTML+='M0/MB (printed/digital real/base money supply, created by CB):<span id="m0">'+m0+'</span><br />';
	page_div.innerHTML+='M1 (availabe money supply, M0 + created by private banks): <span id="m1">'+m1+'</span><br />';
	page_div.innerHTML+='<br /><br /><br /><button onclick="initial_state(2,2)">restart</button><button onclick="initial_state()">restart(configurable)</button>';
	page_div.innerHTML+='<button onclick="are_accounts_balanced()">validate_balance</button>';
}




function calc_m0(){
    return -1*elements.central_bank.account.balance();
}

function calc_m1(){
    var ret=calc_m0();
    // money created by private banks... under construction
    // m1= m0 + checkable-deposits - reserves
    // m1 = checkable deposits + peoples wallets
    // m1 = m0 + banks loans (based on others deposits which are checkable in this simplifications)
    var banks=get_elements_type(elements,Bank);
    for(var i=0;i<banks.length;i++){
        if(elements[banks[i]].classified_assets!=undefined) ret+=elements[banks[i]].classified_assets.loans.value;
    }
    return ret;
}

function calc_m2(){
    // m1 + savings-deposits ... we don't go that far... yet
}

function initial_state(num_banks,num_persons){
	//alert("Central Bank (bank of banks) interest %1", "");
    /*var central_bank={};
    var banks=[]; var persons=[];*/
    /*if (typeof(num_banks)==='undefined')
        num_banks = prompt("Number of banks (initial reserve $10)", "");
	for(var i=0;i<num_banks;i++){
		var bank=new Bank("bank"+(i+1));
		banks.push(bank);
		banks_index[bank.id]=banks[banks.length - 1];
		var person=new Person("bank"+(i+1)+"-owner",bank.id);
		persons.push(person);
	}
    if (typeof(num_persons)==='undefined')
        num_persons = prompt("Number of persons (initial money in pocket $10)", "");
	for(var i=0;i<num_persons;i++){
		var person=new Person("person"+(i+1));
		persons.push(person);
	}*/
	
	show_situation();
}

function initial_state_empty(){
	show_situation();
}


function initial_state_banks(){
    // rothschild most famous/old german bankers family
    
    // gov-issued-bond and contract anaBotin for 60k
    elements.add("anaBotin", new Person("anaBotin"));
    elements['gov_treasury'].op_issue_bond(60,1000);
    exchange('central_bank','bonds','gov_treasury',60,1000);
    // gov issues labor and anna should take it (and get older...)
    elements['gov_treasury'].op_issue_labor(1,-60000); // the price is force+time
    exchange('anaBotin','labor','gov_treasury',1,-60000);
    // consume the labor...
    elements['anaBotin'].op_consume('labor',1);
    
    // in theory business should have shares and Anna should have all santander shares... but let's keep it simple...
    elements.add("santander", new Bank("santander"));
    elements['anaBotin'].op_make_deposit(30000,'santander');
    //elements.add("bbva", new Bank("bbva"));
	show_situation();
}



//initial_state_empty();
initial_state_banks();
