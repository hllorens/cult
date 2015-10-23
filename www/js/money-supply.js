"use strict";


var AccountEntry = function (concept, related_account, amount){
    this.concept=concept;
    this.related_account=related_account;
    this.amount=amount; //#positive debit, negative credit
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

// property
var Asset = function (market, units){
    this.market=market;
    this.units=units; // always positive
}

// market element
var Product = function (market, owner, units, unit_price){
    this.market=market;
    this.owner=owner;
    this.units=units; // always positive
    this.unit_price=unit_price; // always positive
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
           product.units>=units && product.unit_price==uprice){
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



var Transaction = function (concept, account1name,account2name, amount){
    this.concept=concept;
    this.transactions={};
    this.debit=0;
    this.credit=0;
}


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
var accounts={
    "add": function(name){
                        if(accounts.hasOwnProperty(name)) throw Error('Account '+name+' already exists');
                        accounts[name]=new Account(name);
                        return accounts[name];
                    },
    "remove": function(name){
                    if(!accounts.hasOwnProperty(name)) throw Error('Account '+name+' does not exist');
                    delete accounts[name];
                    return;
                }
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

var are_accounts_balanced=function(obj){
        var temp_accounts_balance={};
        for(var prop in obj) {
            if (obj.hasOwnProperty(prop)) {
                for(var i=0;i<obj[prop].entries.length;i++){
                    if(obj[prop].entries[i].amount==0){alert(obj[prop].name+" entry"+i+" '"+obj[prop].entries[i].concept+"' is 0");return false;}
                    temp_accounts_balance[obj[prop].name]+=obj[prop].entries[i].amount; // if negative will substract
                    temp_accounts_balance[obj[prop].entries[i].related_account]-=obj[prop].entries[i].amount; // if negative will add
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


var objectLength=function(obj) {
    var result = 0;
    for(var prop in obj) {
        if (obj.hasOwnProperty(prop)) {result++;}
   }
}

var objectProperties=function(obj) {
    var result = [];
    for(var prop in obj) {
        if (obj.hasOwnProperty(prop)) {result.push(prop);}
   }
   return result;
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


var banks=[];
var banks_index={};

var persons=[];
var persons_index={};

var page_div=document.getElementById('page');

/*
var Bank = function (name){
    this.id=name;
	this.customer_accounts={};
}
Bank.prototype.make_deposit=function(customer,credit){
	if(!this.customer_accounts.hasOwnProperty(customer)){
		var account=new Account(customer);
		this.customer_accounts[customer]=account;		
	}
	this.customer_accounts[customer].debit+=credit;
}

var Person = function (name, credit, pbank){
    this.id=name;
    this.debit=0;
    if (typeof(credit)==='undefined'){credit=10;}
    this.credit=credit;
    if (typeof(bank)==='undefined'){ // select random bank
		pbank = banks[Math.floor(Math.random()*banks.length)].id;
	}
	this.bank=pbank;
	banks_index[this.bank].make_deposit(name,credit);
}
Person.prototype.balance=function(){
	return (this.credit - this.debit);
}
*/

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

var show_account=function(name, optional_description){
    if(!accounts.hasOwnProperty(name)){throw Error("Account "+name+" not found.");}
    if (typeof(optional_description)==='undefined'){optional_description="";}
    var account=accounts[name];
    var html_ret='<div class="account_box">';
    html_ret+='<h1>'+account.name+' '+optional_description+'</h1>'; 
    html_ret+='<div class="entries">';   
    for(var i=0;i<account.entries.length;i++){
        html_ret+=account.entries[i].concept+' units='+account.entries[i].amount+' rel_ac='+account.entries[i].related_account+'<br />';
    }
    html_ret+='</div>';
    html_ret+='-------------<br/>';
    var balance=account.balance();
    var color_class='color-green';

    if(balance<0){color_class='color-red';}
    html_ret+='<span class="color:'+color_class+'">'+balance+'</span></div>';
    return html_ret;
}

var show_operations=function(obj){
    var content="";
    var operations=objectOperations(obj);
    for(var i=0;i<operations.length;i++){
        content+='<button onclick="elements.'+obj.name+'.'+operation_prefix+operations[i]+'()">'+operations[i]+'</button>';
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
        html_ret+='<button onclick="sell(\''+buyer+'\',\''+market.name+'\',\''+market.supply[i].owner+'\',\''+market.supply[i].units+'\',\''+market.supply[i].unit_price+'\')">'+market.supply[i].owner+' units='+market.supply[i].units+' uprice='+market.supply[i].unit_price+'</button><br />';
    }
    html_ret+='</div>';    
    return html_ret;
}

//transaction
var sell=function(buyer,market_name,supply_item_owner,units,uprice){
    alert("selling in "+market_name+" from "+supply_item_owner+" to "+buyer);
    // TODO TODO TODO.. model assets, model transactions...
    // remove supply
    markets[market_name].remove_supply(supply_item_owner,units,uprice);
    remove_asset(supply_item_owner,market_name,units);
    add_asset(buyer,market_name,units);
    // update accounts and properties (assets) move bought thing to assets...
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
}

// for both existing an unexisting...
// var purchase_order (for stocks)

var CentralBank=function(name){
    this.name=name;
    this.account=accounts.add(name);
    this.assets=[];
}
CentralBank.prototype.op_buy=function(){
        remove_modal();
        buy_existing(this.name)
}
CentralBank.prototype.op_modify_interest=function(){alert("trying to modify interest");}
CentralBank.prototype.op_modify_reserve_fraction=function(){alert("trying to modify_reserve_fraction");}

// this should be common to all elements...
var remove_asset=function(element,market, units){
    var removed=false;
    for(var i=0;i<elements[element].assets.length;i++){
        var asset=elements[element].assets[i];
        if(asset.market==market && asset.units>=units){
               if(asset.units==units){
                   elements[element].assets.splice(i,1);
               }else{
                   asset.units-=units;
               }
               removed=true;
               break;
           }
    }
    if(!removed) throw Error("Unable to remove asset "+element);    
}

var add_asset=function(element,market,units){
    var added=false;
    for(var i=0;i<elements[element].assets.length;i++){
        var asset=elements[element].assets[i];
        if(asset.market==market){ asset.units+=units; added=true; break}
    }
    if(!added) elements[element].assets.push(new Asset(market,units));
}


var show_assets=function(name){
    if(!elements.hasOwnProperty(name)){throw Error("Assets "+name+" not found.");}
    var element=elements[name];
    var html_ret='<h1>assets</h1>'; 
    html_ret+='<div class="entries">';   
    for(var i=0;i<element.assets.length;i++){
        html_ret+=element.assets[i].market+' units='+element.assets[i].units+'<br />';
    }
    html_ret+='-------------<br/>';
    return html_ret;
}


var GovTreasury=function(name){
    this.name=name;
    this.account=accounts.add(name);
    this.assets=[];
}
GovTreasury.prototype.op_issue_bond=function(){
        var bond=new Product("bonds",this.name, 1, 1000); 
        markets.bonds.supply.push(bond);
        this.assets.push(bond)
        show_situation();
        remove_modal();
    }
GovTreasury.prototype.op_collect_taxes=function(){alert("collecting_taxes");},
GovTreasury.prototype.op_invest=function(){alert("investing");}

// initialize
elements.add("central_bank", new CentralBank("central_bank"));
elements.add("gov_treasury", new GovTreasury("gov_treasury"));
markets["bonds"]=new Market("bonds");

function show_situation(){
    page_div.innerHTML=""; //&lt;ECONOMY&gt;
    page_div.innerHTML+=show_account("central_bank",'<button onclick="show_operations(elements.central_bank)">+</button>'); //,"loan interest ..., checking accounts 0%");
    page_div.innerHTML+=show_assets("central_bank");
    // show central bank operations...

    page_div.innerHTML+=show_account("gov_treasury",'<button onclick="show_operations(elements.gov_treasury)">+</button>'); 
    page_div.innerHTML+=show_assets("gov_treasury");
    
    page_div.innerHTML+="<br />Markets:<br />"; //(loan interest %2, cheking accounts 0%, credit=negative)
    var markets_arr=objectProperties(markets);
    for(var i=0;i<markets_arr.length;i++){
        page_div.innerHTML+=show_market(markets_arr[i]);
    }
    
    page_div.innerHTML+="<br />Banks:<br />"; //(loan interest %2, cheking accounts 0%, credit=negative)
    banks=get_elements_matching(accounts,"^bank[0-9]+$");
    for(var i=0;i<banks.length;i++){
        page_div.innerHTML+=show_account(bank[i],'<button>+</button>');
//		page_div.innerHTML+="&nbsp;&nbsp;&nbsp;"+banks[i].name+": debit(deposits/checking (reserved, excess)): credit(loans): <br />";
//		page_div.innerHTML+="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; debit(initial-reserve/interests/benefits (reserved)): credit(loans from central bank [virtual]): <br />";
	}
    
	page_div.innerHTML+="<br />Persons: (operations include create a bank, if there are no banks... money goes to pocket)<br />";
	for(var i=0;i<persons.length;i++){
		page_div.innerHTML+='&nbsp;&nbsp;&nbsp;'+persons[i].name+' debit: '+persons[i].debit+' credit: '+persons[i].credit+' balance: '+persons[i].balance()+'  - <button onclick="take_out_loan()">take-out loan</button> <button onclick="transfer()">transfer</button> <button onclick="withdraw()">withdraw</button> <button onclick="make_deposit()">make deposit</button> <br />';	
	}

	page_div.innerHTML+="<br />Persons wallet/pocket (hidden, black): Show together with persons... For simplicity, assume money stored in banks (we can model this afterwards and see how central banks know the amount hidden, out of official circulation)<br />";

    // THEN ALSO MODEL PROPERTIES(houses,cars), stocks (company capital, so everyone can be a bank owner)
    
	page_div.innerHTML+="<br /><br />";
	page_div.innerHTML+="M0 (printed/digital 'real/base' money supply):<br />";
	page_div.innerHTML+="M1 (availabe money supply):<br />";
	page_div.innerHTML+="Goods/Services supply:<br />";
	page_div.innerHTML+="Inflation/Deflation: how banks remove money from circulation?<br />";
	page_div.innerHTML+='<br /><br /><br /><button onclick="initial_state(2,2)">restart</button><button onclick="initial_state()">restart(configurable)</button>';
	page_div.innerHTML+='<button onclick="are_accounts_balanced(accounts)">validate_balance</button>';
}

function take_out_loan(){
	var loan_amount = prompt("Loan amount $", "");
}

function transfer(){
	var amount = prompt("Transfer amount $", "");   
	var dest = prompt("Transfer destination $", "");   
}

function make_deposit(){
	//var dep_amount = prompt("Deposit amount $", "");
    alert('forbidden: for simplicity');
}

function withdraw(){
	//var amount = prompt("Withdraw amount $", "");   
    alert('forbidden: for simplicity');
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


initial_state(2,2);
