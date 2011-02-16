<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require(GV_RootPath.'lib/countries.php');

$session = session::getInstance();
$nbmodel = 0 ;
$jsQuota = null;
$firstcreation = false;
$myRightsMax = null;
$canEditOne = null;
$adminOfOthersBases = false;
$usrcoord = null ;
$myModels = null ;
$seepwd = false;
$out = "";

$sbascoll_order = null;

$request = httpRequest::getInstance();
$parm = $request->get_parms("srt", "ord", "act", "p0", "p1","p2","p3","p4","p5","p6"); 

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	$user = user::getInstance($usr_id);
	if(!$user->_global_rights['manageusers'])
		phrasea::headers(403);
}
else
	phrasea::headers(403);	

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	phrasea::headers(403);	

phrasea::headers();

$countries = getCountries($lng);
	
$hauteur = "25px";
$Wleft  = 120;
$Wright = 443;
$Wtotal = 3 + $Wleft + $Wright ;	
	

?>
<html lang="<?php echo $session->usr_i18n;?>">
<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
	<title>Edition des utilisateurs</title>
<style type="text/css">
BODY
{
	margin:10px;
	font-size:12px;
}
.divTop
{ 
	background-color : #ffffff; 
	MARGIN-LEFT: <?php echo $Wleft."px"?>;
	OVERFLOW: hidden; 
	WIDTH: 427px;
	WIDTH: 440px;
}
.tableTop
{
	WIDTH: 720px;
} 
.divCenter
{ 
	background-color : #ffffff; 
	RIGHT: 3px; 
	OVERFLOW: auto; 
	WIDTH: <?php echo $Wright."px"?>; 
	POSITION: relative; 
	HEIGHT: 100%;
	
	text-align:left
}
.classdivtable
{ 
	background-color : #FFFFFF; 
	WIDTH: <?php echo $Wtotal."px"?>;
	height:500px;
	text-align:left
}
.divLeft
{
	background-color : #FFFFFF;
	DISPLAY: inline; 
	FLOAT: left; 
	OVERFLOW: hidden; 
	WIDTH: <?php echo $Wleft."px"?>; 
	HEIGHT: 118px;
	HEIGHT: 100%;
}
.tableLeft
{
	table-layout:fixed; 
	WIDTH: <?php echo $Wleft."px"?>; 
}
.trLeft
{
	HEIGHT: <?php echo $hauteur?>;
	OVERFLOW: hidden;
}
.tdLeft
{
	WIDTH: <?php echo $Wleft."px"?>; 
	HEIGHT: <?php echo $hauteur?>;
	OVERFLOW: hidden;
}
.divTdLeft
{
	background-color:#ffffff;
	WIDTH: <?php echo ($Wleft-3)."px"?>; 
	OVERFLOW: hidden;
	font-size:10px
}
.tableCenter
{
	WIDTH: 720px;
	background-color:#ffffff;
	position:relative; 
	top:0px;
	left:0px;
	
}
.tdTableCenter
{
	OVERFLOW: hidden; 
	HEIGHT: <?php echo $hauteur?>;
	text-align:center;
}
.divTdTableCenter
{
	OVERFLOW: hidden; 
	WIDTH: 18px; 
	text-align:center;
	align:center;
	
}
* 
{
	margin:0; 
	padding:0;
}
/*
#tableau
{
	margin-left:auto;
	margin-right:auto;
}
*/
#tableau table
{
}
#tableau td, #tableau th
{
	border-right:#CCCCCC 1px solid ;
}
.iptIdt
{
width:190px;
border:#cccccc 1px solid;
font-size:11px;
}



.desktopMenu
{
    BORDER-RIGHT: 2px outset;
    BORDER-TOP: 2px outset;
    DISPLAY: none;
    FONT-SIZE: 8pt;
    Z-INDEX: 100;
    LEFT: 500px;
    VISIBILITY: visible;
    OVERFLOW: hidden;
    BORDER-LEFT: 2px outset;
    WIDTH: 150px;
    COLOR: #000000;
    BORDER-BOTTOM: 2px outset;
    POSITION: absolute;
    TOP: 300px;
    BACKGROUND-COLOR: #d4d0c9;
    TEXT-DECORATION: none
}
.desktopMenu A
{
    DISPLAY: block;
    PADDING-LEFT: 5px;
    LEFT: 0px;
    WIDTH: 100%;
    COLOR: #000000;
    PADDING-TOP: 2px;
    WHITE-SPACE: nowrap;
    POSITION: relative;
    TOP: 0px;
    HEIGHT: 16px;
    BACKGROUND-COLOR: #d4d0c9;
    TEXT-DECORATION: none
}
.desktopMenu A:hover
{
    COLOR: #ffffff;
    BACKGROUND-COLOR: #191970;
    TEXT-DECORATION: none
}


.desktopMenu2
{
    BORDER-RIGHT: 2px outset;
    BORDER-TOP: 2px outset;
    DISPLAY: none;
    FONT-SIZE: 8pt;
    Z-INDEX: 100;
    LEFT: 500px;
    VISIBILITY: visible;
    OVERFLOW: hidden;
    BORDER-LEFT: 2px outset;
    WIDTH: 150px;
    COLOR: #000000;
    BORDER-BOTTOM: 2px outset;
    POSITION: absolute;
    TOP: 300px;
    BACKGROUND-COLOR: #d4d0c9;
    TEXT-DECORATION: none
}
.desktopMenu2 A
{
    DISPLAY: block;
    PADDING-LEFT: 5px;
    LEFT: 0px;
    WIDTH: 100%;
    COLOR: #000000;
    PADDING-TOP: 2px;
    WHITE-SPACE: nowrap;
    POSITION: relative;
    TOP: 0px;
    HEIGHT: 30px;
    BACKGROUND-COLOR: #d4d0c9;
    TEXT-DECORATION: none
}
.desktopMenu2 A:hover
{
    COLOR: #ffffff;
    BACKGROUND-COLOR: #191970;
    TEXT-DECORATION: none
}


</style>

<script type="text/javascript">
var allgetID = new Array ;
var total = 0;

function createpwd()
{
	possible = "123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	newpwd = "";
	for( k=0; k<8; k++)
		newpwd += possible.charAt(Math.floor(Math.random()*1000)%possible.length);
	return(newpwd);
}

function tableScroll(theTable) 
{
	var TableId = theTable.id.replace("_center", "");	
	var tableTop = document.getElementById(TableId+"_top");
	var tableLeft = document.getElementById(TableId+"_left");
 		
	document.getElementById("imgtopinclin").style.width = "720px";

	tableTop.scrollLeft = theTable.scrollLeft;	
	tableLeft.scrollTop = theTable.scrollTop;
}
function addEvent(obj, evType, fn, useCapture)
{
	if (obj.addEventListener)
	{
		obj.addEventListener(evType, fn, useCapture);
		return true;
	} 
	else 
	{
		if (obj.attachEvent)
		{
			var r = obj.attachEvent("on"+evType, fn);
			return r;
		} 
		else 
		{
			alert("Handler could not be attached");
		}
	}	
}

function applyModel(obj)
{
	if(obj.selectedIndex>0)
	{
		if(confirm('<?php echo p4string::MakeString(_('admin::user: etes vous sur de vouloir appliquer le modele suivant ?'),'JS')?> - ' + obj.options[obj.selectedIndex].getAttribute('caption')))
		{
			ret ="";
			document.forms[0].action = "./users.php";
			document.forms[0].act.value = "UPD";	
			if(typeof(modif["usr_password"])!="undefined")
				ret += ('<usr_password>' +  modif["usr_password"] + '<\/usr_password>\n');		
			ret += ('<applymodel>' + obj.options[obj.selectedIndex].value + '<\/applymodel>\n');		
			for(cc in modif)
			{
				if( cc=="usr_nom" || cc=="usr_prenom" || cc=="usr_mail"	|| cc=="fonction" || cc=="societe" || cc=="activite" || cc=="tel"
					|| cc=="fax" || cc=="adresse" || cc=="cpostal" || cc=="ville" || cc=="pays" || cc=="addrFTP" || cc=="loginFTP" || cc=="pwdFTP"
					|| cc=="destFTP" || cc=="prefixFTPfolder" || cc=="activeFTP" || cc=="passifFTP" || cc=="retryFTP" || cc=="defaultftpdatasent" )
					ret += ('<' + cc + '>' +  modif[cc] + '<\/'+ cc + '>\n');
			}	
			document.forms[0].p3.value = ret; 
			document.forms[0].submit();
		}
		else
			obj.selectedIndex = 0;
	}
	
	
}
/* Chargement de tt les elements dans un tableau pour un acces plus rapide */
function scandom(node, depth)
{
	var n;
	if(!node)
		return;
	if(node.id)
	{
		allgetID[node.id] = node;
		//node.style.visibility = "hidden";
		total++;
	}
	for(n=node.firstChild; n; n=n.nextSibling)
	{
		if(n.nodeType && n.nodeType == 1)
			scandom(n, depth+1);
	}
}

window.onload=function()
{
	redrawme();
	scan(); 
	document.getElementById("iddivloading").style.visibility = "hidden";
}
function scan()
{
	if(document.all)
		scandom(document.documentElement, 0);
	else
	{
		allccuser = document.getElementsByName("ccuser");
		for (var i=0; i<allccuser.length;i++) 
		{
			allgetID[allccuser[i].id] = allccuser[i];
			total++ ;
		}
	}

}

function view(typeDiv)
{
	
	switch (typeDiv)
	{
		case "RIGHTS":
			if( document.getElementById( "divRights") )
			{
				document.getElementById( "divRights").style.visibility = "visible";
				document.getElementById( "divRights").style.display = "";			
			}
			if( document.getElementById( "divIdt") )
			{
				document.getElementById( "divIdt").style.visibility = "hidden";
				document.getElementById( "divIdt").style.display = "none";			
			}  
			
			if( document.getElementById( "divSpecial") )
			{
				document.getElementById( "divSpecial").style.visibility =  "hidden";
				document.getElementById( "divSpecial").style.display = "none";			
			} 
			if( oo=returnElement("genecancel") )	
				oo.style.visibility = "visible" ;
			if( oo=returnElement("genevalid") )	
				oo.style.visibility = "visible" ;
		break;
		
		case "IDT":
		
			if( document.getElementById( "divRights") )
			{
				document.getElementById( "divRights").style.visibility ="hidden";
				document.getElementById( "divRights").style.display = "none";			
			}
			if( document.getElementById( "divIdt") )
			{
				document.getElementById( "divIdt").style.visibility =  "visible";
				document.getElementById( "divIdt").style.display = "";			
			} 
			
			if( document.getElementById( "divSpecial") )
			{
				document.getElementById( "divSpecial").style.visibility =  "hidden";
				document.getElementById( "divSpecial").style.display = "none";			
			} 
			if( oo=returnElement("genecancel") )	
				oo.style.visibility = "visible" ;
			if( oo=returnElement("genevalid") )	
				oo.style.visibility = "visible" ;
		break;
		
		case "SPECIAL":
		
			if( document.getElementById( "divRights") )
			{
				document.getElementById( "divRights").style.visibility ="hidden";
				document.getElementById( "divRights").style.display = "none";			
			}
			if( document.getElementById( "divRights") )
			{
				document.getElementById( "divIdt").style.visibility =  "hidden";
				document.getElementById( "divIdt").style.display = "none";			
			} 
			
			if( document.getElementById( "divSpecial") )
			{
				document.getElementById( "divSpecial").style.visibility =  "visible";
				document.getElementById( "divSpecial").style.display = "";			
			} 
		break;
		
	}
}

function clk_cc_quata(bool)
{
	if(bool)
	{
		if( oo=document.getElementById("ccochquotayes") )
		{
			oo.src = "/skins/icons/ccoch1.gif" ;					
			oo.setAttribute('state', "1");
		}
		if( oo=document.getElementById("ccochquotano") )
		{
			oo.src = "/skins/icons/ccoch0.gif" ;
			oo.setAttribute('state', "0");
		}
		if( oo=document.getElementById("remainquota") )
			oo.disabled=false;
		if( oo=document.getElementById("maxquota") )
			oo.disabled=false;
	}
	else
	{
		if( oo=document.getElementById("ccochquotayes") )
		{
			oo.src = "/skins/icons/ccoch0.gif" ;		
			oo.setAttribute('state', "0");
		}
		if( oo=document.getElementById("ccochquotano") )
		{
			oo.src = "/skins/icons/ccoch1.gif" ;
			oo.setAttribute('state', "1");
		}	
		if( oo=document.getElementById("remainquota") )
			oo.disabled=true;
		if( oo=document.getElementById("maxquota") )
			oo.disabled=true;
	}
	
}

function clkQuotaBas()
{

	closeMenuMask();	
	sbas =  curMaskSbas ;
	sbasname = curSbasName ;
	if( oo=document.getElementById("idspantitle") )
		oo.innerHTML = "<?php echo p4string::MakeString(_('admin::user: acces aux quotas'),'JS')?><br>";
	if( oo=document.getElementById("idspanbase") )
		oo.innerHTML = "<?php echo p4string::MakeString(_('phraseanet:: base'),'JS')?> "+sbasname;
	if( oo=document.getElementById("idspancoll") )
		oo.innerHTML = "<i><?php echo p4string::MakeString(_('admin::user: recapitulatif'),'JS')?><\/i>" ;
	if( oo=document.getElementById("idspanline") )
		oo.innerHTML = "<u><?php echo _('admin::user: restrictions de telechargement')?> :<\/u>" ;

	lastval=null;
	lastremain = null;
	lastmax=null;
	
	for(cc2 in relsbas[sbas])
	{
		if( current = returnElement("quota_"+sbas+"_"+ relsbas[sbas][cc2]))
		{
			if( current.style.visibility=="visible" )
			{
				
				if(lastval==null)
					lastval = allquotas[sbas][cc2].restrict_dwnld;
				if(lastremain==null)
					lastremain = 	allquotas[sbas][cc2].remain_dwnld;
				if(lastmax==null)
					lastmax = 	allquotas[sbas][cc2].month_dwnld_max;
					
					
				if(allquotas[sbas][cc2].restrict_dwnld!=lastval)
					lastval=2;				
				if(allquotas[sbas][cc2].remain_dwnld!=lastremain && allquotas[sbas][cc2].restrict_dwnld!=0)	
					lastremain = 0;
				if(allquotas[sbas][cc2].month_dwnld_max!=lastmax && allquotas[sbas][cc2].restrict_dwnld!=0)	
					lastmax = 0;	
					
			}
		}
	}		
	
	if( oo=document.getElementById("spacetabmiddle") )
	{
		
		newhtml ="";
		newhtml+="<table>";
		
	    newhtml+="<tr>";
		newhtml+="<td colspan=\"2\">";
		
	
		ccckdyes = "0";
		ccckdno  = "1";
		activ 	 = "disabled";
		
		if( lastval==1 )
		{
			ccckdyes = "1";
			ccckdno  = "0";			
			activ 	 = "";
		}
		else if( lastval==2 )
		{
			ccckdyes = "2";
			ccckdno  = "2";			
			activ 	 = "disabled";
		}
		newhtml+="<img src=\"/skins/icons/ccoch"+ ccckdyes +".gif\" state=\""+ccckdyes+"\" id=\"ccochquotayes\" sbas=\""+sbas+"\" onClick=\"clk_cc_quata(true);return(false);\" > <?php echo _('phraseanet::oui')?>";
		newhtml+="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";		
		newhtml+="<img src=\"/skins/icons/ccoch"+ ccckdno +".gif\"  state=\""+ccckdno+"\" id=\"ccochquotano\" onClick=\"clk_cc_quata(false);return(false);\"> <?php echo _('phraseanet::non')?>";		
		
		newhtml+="<\/td>";
		newhtml+="<\/tr>";
			
		newhtml+="<tr>";
		newhtml+="<td colspan=\"2\"><br>";
		newhtml+="<\/td>";
		newhtml+="<\/tr>";
		
		newhtml+="<tr>";
		newhtml+="<td style=\"text-align:right\"><?php echo _('admin::user:quota: droit')?> :<\/td>";
		newhtml+="<td style=\"text-align:left\"><input type=\"text\" style=\"width:60px\" "+activ+" id=\"maxquota\" value=\""+lastmax+"\" > <?php echo _('admin::user:quota: par mois')?><\/td>";
		newhtml+="<\/tr>";
		
		newhtml+="<tr>";
		newhtml+="<td style=\"text-align:right\"><?php echo _('admin::user:quota: reste')?> :<\/td>";
		newhtml+="<td style=\"text-align:left\"><input type=\"text\" style=\"width:60px\" "+activ+" id=\"remainquota\" value=\""+lastremain+"\"><\/td>";
		newhtml+="<\/tr>";
 
		newhtml+="<\/table>";
		 
		oo.innerHTML = "<center>"+ newhtml + "<\/center>";
	}
	if( oo=document.getElementById("idspanapply") )		
		oo.innerHTML = '<a href="javascript:void();" onClick="applyNewQuotaBas(true);return(false);" style="color:#000000"><?php echo _('boutton::valider')?><\/a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void();" onClick="applyNewQuotaBas(false);return(false);" style="color:#000000"><?php echo _('boutton::annuler')?><\/a>';

	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "hidden" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "hidden" ;
		
	view('SPECIAL');
}


function clkQuota(sbas,bas, sbasname, basname)
{
	if( oo=document.getElementById("idspantitle") )
		oo.innerHTML = "Quota<br>";
	if( oo=document.getElementById("idspanbase") )
		oo.innerHTML = "Base "+sbasname;
	if( oo=document.getElementById("idspancoll") )
		oo.innerHTML = " Collection "+basname;
	if( oo=document.getElementById("idspanline") )
		oo.innerHTML = "<u><?php echo _('admin::user: restrictions de telechargement')?> :<\/u>" ;
	if( oo=document.getElementById("spacetabmiddle") )
	{
		
		newhtml ="";
		newhtml+="<table>";
		
		newhtml+="<tr>";
		newhtml+="<td colspan=\"2\">";
		
	
		ccckdyes = "0";
		ccckdno  = "1";
		reste 	 = allquotas[sbas][bas].remain_dwnld;
		max 	 = allquotas[sbas][bas].month_dwnld_max;
		activ 	 = "disabled";
		
		if( allquotas[sbas][bas].restrict_dwnld==1 )
		{
			ccckdyes = "1";
			ccckdno  = "0";			
			activ 	 = "";
		}
		else if( allquotas[sbas][bas].restrict_dwnld==2 )
		{
			ccckdyes = "2";
			ccckdno  = "2";			
			activ 	 = "disabled";
		}
		newhtml+="<img src=\"/skins/icons/ccoch"+ ccckdyes +".gif\" state=\""+ccckdyes+"\" id=\"ccochquotayes\" sbas=\""+sbas+"\" bas=\""+bas+"\" onClick=\"clk_cc_quata(true);return(false);\" > <?php echo _('phraseanet::oui')?>";
		newhtml+="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";		
		newhtml+="<img src=\"/skins/icons/ccoch"+ ccckdno +".gif\"  state=\""+ccckdno+"\" id=\"ccochquotano\" onClick=\"clk_cc_quata(false);return(false);\"> <?php echo _('phraseanet::non')?>";		
		
		newhtml+="<\/td>";
		newhtml+="<\/tr>";
		
		newhtml+="<tr>";
		newhtml+="<td colspan=\"2\"><br>";
		newhtml+="<\/td>";
		newhtml+="<\/tr>";
		
		newhtml+="<tr>";
		newhtml+="<td style=\"text-align:right\"><?php echo _('admin::user:quota: droit')?> :<\/td>";
		newhtml+="<td style=\"text-align:left\"><input type=\"text\" style=\"width:60px\" "+activ+" id=\"maxquota\" value=\""+max+"\" > <?php echo _('admin::user:quota: par mois')?><\/td>";
		newhtml+="<\/tr>";
		
		newhtml+="<tr>";
		newhtml+="<td style=\"text-align:right\"><?php echo _('admin::user:quota: reste')?> :<\/td>";
		newhtml+="<td style=\"text-align:left\"><input type=\"text\" style=\"width:60px\" "+activ+" id=\"remainquota\" value=\""+reste+"\"><\/td>";
		newhtml+="<\/tr>";
		
		newhtml+="<\/table>";
		 
		oo.innerHTML = "<center>"+ newhtml + "<\/center>";
	}
	if( oo=document.getElementById("idspanapply") )		
		oo.innerHTML = '<a href="javascript:void();" onClick="applyNewQuota(true);return(false);" style="color:#000000"><?php echo _('boutton::valider')?><\/a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void();" onClick="applyNewQuota(false);return(false);" style="color:#000000"><?php echo _('boutton::annuler')?><\/a>';

	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "hidden" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "hidden" ;
		
	view('SPECIAL');
}
function applyNewQuotaBas(bool)
{
	if(bool)
	{
		oo=document.getElementById("ccochquotayes");
		cursbas = oo.getAttribute('sbas');
		if( oo.getAttribute('state')=="0") 
		{			 
			
			for(cc2 in relsbas[sbas])
			{
				if( current = returnElement("quota_"+sbas+"_"+ relsbas[sbas][cc2]))
				{
					if( current.style.visibility=="visible" )
					{
						if(allquotas[cursbas][cc2].restrict_dwnld==0)
						{	
						}
						else
						{
/** SAVE MODIF  **/			modif["restrictdwnld_"+cursbas+'_'+ cc2 +""]=0;		
							allquotas[cursbas][cc2].restrict_dwnld=0;
						}	
					}
				}
			}
			
		}	
		else if( oo.getAttribute('state')=="1") 
		{
			tmpmodifs = "";
			oo=document.getElementById("ccochquotayes");			
			
			for(cc2 in relsbas[sbas])
			{
				if( current = returnElement("quota_"+sbas+"_"+ relsbas[sbas][cc2]))
				{
					if( current.style.visibility=="visible" )
					{
						if(allquotas[cursbas][cc2].restrict_dwnld==1)
						{	
						}
						else
						{
							tmpmodifs+= 'modif["restrictdwnld_'+cursbas+'_'+cc2+'"]="1";';
							tmpmodifs+= 'allquotas["'+cursbas+'"]["'+cc2+'"].restrict_dwnld=1;';
						}	
					}
				}
			}
			 
	
			/** verif max si chgmt */
			if( oo=document.getElementById("maxquota") ) 
			{
				if( (isFinite(oo.value)) && (oo.value)>=0 )
				{
					for(cc2 in relsbas[sbas])
					{
						if( current = returnElement("quota_"+sbas+"_"+ relsbas[sbas][cc2]))
						{
							if( current.style.visibility=="visible" )
							{
								tmpmodifs+= 'modif["monthdwnldmax_'+cursbas+'_'+cc2+'"]="'+oo.value+'";';
								tmpmodifs+= 'allquotas["'+cursbas+'"]["'+cc2+'"].month_dwnld_max = ' + oo.value+ ";";
							}
						}
					}	
				}
				else
				{
					alert("<?php echo _('admin::user: erreur dans les restrictions de telechargement')?>");
					return;
				}
			}
			
			/** verif remain si chgmt */
			if( oo=document.getElementById("remainquota") ) 
			{
				if( (isFinite(oo.value)) && (oo.value)>=0 )
				{
					
					for(cc2 in relsbas[sbas])
					{
						if( current = returnElement("quota_"+sbas+"_"+ relsbas[sbas][cc2]))
						{
							if( current.style.visibility=="visible" )
							{
								tmpmodifs+= 'modif["remaindwnld_'+cursbas+'_'+cc2+'"]="'+oo.value+'";';
								tmpmodifs+= 'allquotas["'+cursbas+'"]["'+cc2+'"].remain_dwnld = ' + oo.value+ ";";
							}
						}
					}	
					
						
				}
				else
				{
					alert("<?php echo _('admin::user: erreur dans les restrictions de telechargement')?>");
					return;
				}
			}
			if(tmpmodifs!='')
				eval(tmpmodifs);						
		}
	}
	
	view('RIGHTS');
	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "visible" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "visible" ;
}

function applyNewQuota(bool)
{
	if(bool)
	{
		oo=document.getElementById("ccochquotayes");
		cursbas = oo.getAttribute('sbas');
		curbas  = oo.getAttribute('bas');
		if( oo.getAttribute('state')=="0") 
		{			 
			if(allquotas[cursbas][curbas].restrict_dwnld==0)
			{	
			}
			else
			{
/** SAVE MODIF  **/	
				modif["restrictdwnld_"+cursbas+'_'+curbas+""]=0;		
				allquotas[cursbas][curbas].restrict_dwnld=0;
			}
		}	
		else if( oo.getAttribute('state')=="1") 
		{
			tmpmodifs = "";
			oo=document.getElementById("ccochquotayes");			
			if(allquotas[cursbas][curbas].restrict_dwnld==1)
			{	
			}
			else
			{
				tmpmodifs+= 'modif["restrictdwnld_'+cursbas+'_'+curbas+'"]="1";';
				tmpmodifs+= 'allquotas["'+cursbas+'"]["'+curbas+'"].restrict_dwnld=1;';
			}
	
			/** verif max si chgmt */
			if( oo=document.getElementById("maxquota") ) 
			{
				if( (isFinite(oo.value)) && (oo.value)>=0 )
				{
				//	if( oo.value!=allquotas[cursbas][curbas].month_dwnld_max || allquotas[cursbas][curbas].restrict_dwnld==2 )
				//	{
						tmpmodifs+= 'modif["monthdwnldmax_'+cursbas+'_'+curbas+'"]="'+oo.value+'";';
						tmpmodifs+= 'allquotas["'+cursbas+'"]["'+curbas+'"].month_dwnld_max = ' + oo.value+ ";";
				//	}
				}
				else
				{
					alert("<?php echo _('admin::user: erreur dans les restrictions de telechargement')?>");
					return;
				}
			}
			
			/** verif remain si chgmt */
			if( oo=document.getElementById("remainquota") ) 
			{
				if( (isFinite(oo.value)) && (oo.value)>=0 )
				{
				//	if( oo.value!=allquotas[cursbas][curbas].remain_dwnld || allquotas[cursbas][curbas].restrict_dwnld==2 )
				//	{
						tmpmodifs+= 'modif["remaindwnld_'+cursbas+'_'+curbas+'"]="'+oo.value+'";';
						tmpmodifs+= 'allquotas["'+cursbas+'"]["'+curbas+'"].remain_dwnld = ' + oo.value+ ";";
				//	}
				}
				else
				{
					alert("<?php echo _('admin::user: erreur dans les restrictions de telechargement')?>");
					return;
				}
			}
/********** SAVE MODIF  **/	
			if(tmpmodifs!='')
				eval(tmpmodifs);						
		}
	}
	
	view('RIGHTS');
	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "visible" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "visible" ;
}






function clk_cc_time(bool)
{
	list = new Array("timeRestdayfrom","timeRestmonthfrom","timeRestyearfrom","timeRestdayto","timeRestmonthto","timeRestyearto");
		
	if(bool)
	{
		if( oo=document.getElementById("ccochtimeyes") )
		{
			oo.src = "/skins/icons/ccoch1.gif" ;					
			oo.setAttribute('state', "1");
		}
		if( oo=document.getElementById("ccochtimeno") )
		{
			oo.src = "/skins/icons/ccoch0.gif" ;
			oo.setAttribute('state', "0");
		}
		for(cc in list)
			if( oo=document.getElementById(list[cc]) )
				oo.disabled=false;
			
	}
	else
	{
		if( oo=document.getElementById("ccochtimeyes") )
		{
			oo.src = "/skins/icons/ccoch0.gif" ;		
			oo.setAttribute('state', "0");
		}
		if( oo=document.getElementById("ccochtimeno") )
		{
			oo.src = "/skins/icons/ccoch1.gif" ;
			oo.setAttribute('state', "1");
		}	
		for(cc in list)
			if( oo=document.getElementById(list[cc]) )
				oo.disabled=true;
	}
	
}
function clkTime(sbas,bas, sbasname, basname)
{
	if( oo=returnElement("idspantitle") )
		oo.innerHTML = "Time limit<br>";
	if( oo=returnElement("idspanbase") )
		oo.innerHTML = "Base "+sbasname;
	if( oo=returnElement("idspancoll") )
		oo.innerHTML = " Collection "+basname;
	if( oo=returnElement("idspanline") )
		oo.innerHTML = "<u><?php echo _('admin::user:quota: limite temporelle')?> :<\/u>" ;
	if( oo=returnElement("spacetabmiddle") )
	{
		
		newhtml ="";
		newhtml+="<table>";
		
		newhtml+="<tr>";
		newhtml+="<td colspan=\"2\">";
		
		
		limited		= alltimelimit[sbas][bas].limited;
		limitedfrom = ""+alltimelimit[sbas][bas].limitedfrom;
		limitedto 	= ""+alltimelimit[sbas][bas].limitedto;
		
		ccckdyes = "0";
		ccckdno  = "1";
		activ 	 = "disabled";
		
		if( limited==1 )
		{
			ccckdyes = "1";
			ccckdno  = "0";			
			activ 	 = "";
		}
		else if( limited==2 )
		{
			ccckdyes = "2";
			ccckdno  = "2";			
			activ 	 = "disabled";
		}
		
		newhtml+="<img src=\"/skins/icons/ccoch"+ ccckdyes +".gif\" state=\""+ccckdyes+"\" id=\"ccochtimeyes\" sbas=\""+sbas+"\" bas=\""+bas+"\"  onClick=\"clk_cc_time(true);return(false);\" > <?php echo _('phraseanet::oui')?>";
		newhtml+="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";		
		newhtml+="<img src=\"/skins/icons/ccoch"+ ccckdno +".gif\"  state=\""+ccckdno+"\"  id=\"ccochtimeno\" onClick=\"clk_cc_time(false);return(false);\"> <?php echo _('phraseanet::non')?>";		
		
		yfrom = limitedfrom.substr(0,4);
		mfrom = limitedfrom.substr(4,2) ;
		dfrom = limitedfrom.substr(6,2) ;
		
		yto = limitedto.substr(0,4);
		mto = limitedto.substr(4,2) ;
		dto = limitedto.substr(6,2) ;
			
		newhtml+="<\/td>";
		newhtml+="<\/tr>";
		
		newhtml+="<tr>";
		newhtml+="<td colspan=\"2\"><br>";
		newhtml+="<\/td>";
		newhtml+="<\/tr>";
		
		newhtml+="<tr>";
		
		newhtml+="<td style=\"text-align:right\"><?php echo _('admin::user:time: de (date)')?> :<\/td>";
		newhtml+="<td style=\"text-align:left\">";		
		newhtml+="<select id=\"timeRestdayfrom\" "+activ+" style=\"FONT-SIZE: 9px\" >";			              
		for(i=1;i<=31;i++)
		{
			chk ="";
			if(dfrom==i)chk="selected";			
			if(i<10)	newhtml+="<option "+chk+" value='0"+i+"'>0"+i+"<\/option>";
			else		newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}
		newhtml+="<\/select>";
		newhtml+="<select id=\"timeRestmonthfrom\" "+activ+" style=\"FONT-SIZE: 9px;\" >";		
		for(i=1;i<=12;i++)
		{
			chk ="";
			if(mfrom==i)chk="selected";			
			if(i<10)	newhtml+="<option "+chk+" value='0"+i+"'>0"+i+"<\/option>";
			else		newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}	
		newhtml+="<\/select>";
		newhtml+="<select id=\"timeRestyearfrom\" "+activ+" style=\"FONT-SIZE: 9px;\" >";		
		for(i=<?php echo (date("Y")-2)?>;i<=<?php echo (date("Y")+5)?>;i++)
		{
			chk ="";
			if(yfrom==i)chk="selected";
			newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}	
		newhtml+="<\/select>";		
		newhtml+="<\/td>";
		
		newhtml+="<\/tr>";;
		
		newhtml+="<tr><td>&nbsp;<\/td><\/tr>"; 
		
		newhtml+="<tr>";
		newhtml+="<td style=\"text-align:right\"><?php echo _('phraseanet::time:: a')?> :<\/td>";
		
		newhtml+="<td style=\"text-align:left\">";		
		newhtml+="<select id=\"timeRestdayto\" "+activ+" style=\"FONT-SIZE: 9px\" >";			              
		for(i=1;i<=31;i++)
		{
			chk ="";
			if(dto==i)chk="selected";			
			if(i<10)	newhtml+="<option "+chk+" value='0"+i+"'>0"+i+"<\/option>";
			else		newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}
		newhtml+="<\/select>";
		newhtml+="<select id=\"timeRestmonthto\" "+activ+" style=\"FONT-SIZE: 9px;\" >";		
		for(i=1;i<=12;i++)
		{
			chk ="";
			if(mto==i)chk="selected";			
			if(i<10)	newhtml+="<option "+chk+" value='0"+i+"'>0"+i+"<\/option>";
			else		newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}	
		newhtml+="<\/select>";
		newhtml+="<select id=\"timeRestyearto\" "+activ+" style=\"FONT-SIZE: 9px;\" >";		
		for(i=<?php echo (date("Y")-2)?>;i<=<?php echo (date("Y")+5)?>;i++)
		{
			chk ="";
			if(yto==i)chk="selected";
			newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}	
		newhtml+="<\/select>";		
		newhtml+="<\/td>";
		
		newhtml+="<\/tr>";
		
		newhtml+="<\/table>";
		 
		 
		oo.innerHTML = "<center>"+ newhtml + "<\/center>";
	}
	if( oo=returnElement("idspanapply") )		
		oo.innerHTML = '<a href="javascript:void();" onClick="applyTime(true);return(false);" style="color:#000000"><?php echo _('boutton::valider')?><\/a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void();" onClick="applyTime(false);return(false);" style="color:#000000"><?php echo _('boutton::annuler')?><\/a>';

	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "hidden" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "hidden" ;
		
	view('SPECIAL');
}
function applyTimeBas(bool)
{
	if(bool)
	{
		oo=document.getElementById("ccochtimeyes");
		cursbas = oo.getAttribute('sbas');
		
		if(oo.getAttribute('state')==0)
  		{
  			for(cc2 in relsbas[cursbas])
			{
				if( current = returnElement("limit_"+cursbas+"_"+ relsbas[cursbas][cc2]))
				{
					if( current.style.visibility=="visible" )
					{
						modif["timelimited_"+cursbas+"_"+cc2] ='0';
						alltimelimit[cursbas][cc2].limited = 0;
						
					}
				}
			}	
  		}
  		else if(oo.getAttribute('state')==1)
  		{
  			
  			if( (yfrom = document.getElementById("timeRestyearfrom"))
			 && (mfrom = document.getElementById("timeRestmonthfrom"))
			 && (dfrom = document.getElementById("timeRestdayfrom"))
			 && (yto = document.getElementById("timeRestyearto"))
			 && (mto = document.getElementById("timeRestmonthto"))
			 && (dto = document.getElementById("timeRestdayto"))
			  
			  )
			{	
				fromtmp = yfrom.value + mfrom.value + dfrom.value ;
				totmp = yto.value + mto.value + dto.value ;
				fromtmp = fromtmp *1;
				totmp = totmp *1;
				if( fromtmp>totmp)
				{
					alert("<?php echo _('admin::user:time: erreur : la date de fin doit etre posterieur a celle de debut')?>");
					return; 
				}
				else
				{
					for(cc2 in relsbas[cursbas])
					{
						if( current = returnElement("limit_"+cursbas+"_"+ relsbas[cursbas][cc2]))
						{
							if( current.style.visibility=="visible" )
							{
								// on mem
								modif["timelimited_"+cursbas+"_"+cc2] ='1';
								modif["limitedfrom_"+cursbas+"_"+cc2] =''+fromtmp;
								modif["limitedto_"+cursbas+"_"+cc2] = ''+totmp;
						
								alltimelimit[cursbas][cc2].limited = 1 ;
								alltimelimit[cursbas][cc2].limitedfrom = fromtmp ;
								alltimelimit[cursbas][cc2].limitedto   = totmp   ;	
						
							}
						}
					}
			
								
				}
			}
  		}
	}
	view('RIGHTS');
	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "visible" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "visible" ;
}
function applyTime(bool)
{
	if(bool)
	{
		oo=document.getElementById("ccochtimeyes");
		cursbas = oo.getAttribute('sbas');
		curbas  = oo.getAttribute('bas');
		if(oo.getAttribute('state')==0)
  		{
  			modif["timelimited_"+cursbas+"_"+curbas] ='0';
			alltimelimit[cursbas][curbas].limited = 0;
  		}
  		else if(oo.getAttribute('state')==1)
  		{
  			
  				// list = new Array("timeRestdayfrom","timeRestmonthfrom","timeRestyearfrom","","","timeRestyearto");
			if( (yfrom = document.getElementById("timeRestyearfrom"))
			 && (mfrom = document.getElementById("timeRestmonthfrom"))
			 && (dfrom = document.getElementById("timeRestdayfrom"))
			 && (yto = document.getElementById("timeRestyearto"))
			 && (mto = document.getElementById("timeRestmonthto"))
			 && (dto = document.getElementById("timeRestdayto"))
			  
			  )
			{	
				fromtmp = yfrom.value + mfrom.value + dfrom.value ;
				totmp = yto.value + mto.value + dto.value ;
				fromtmp = fromtmp *1;
				totmp = totmp *1;
				if( fromtmp>totmp)
				{
					alert("<?php echo _('admin::user:time: erreur : la date de fin doit etre posterieur a celle de debut')?>");
					return; 
				}
				else
				{
					// on mem
					modif["timelimited_"+cursbas+"_"+curbas] ='1';
					modif["limitedfrom_"+cursbas+"_"+curbas] =''+fromtmp;
					modif["limitedto_"+cursbas+"_"+curbas] = ''+totmp;
			
					alltimelimit[cursbas][curbas].limited = 1 ;
					alltimelimit[cursbas][curbas].limitedfrom = fromtmp ;
					alltimelimit[cursbas][curbas].limitedto   = totmp   ;			
				}
			}
  		}
	}
	view('RIGHTS');
	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "visible" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "visible" ;
}
function calcul_etat_statusBAS(sbas )
{
	
	tmpvand_and = new Array();
	tmpvand_or  = new Array();
	tmpvxor_and = new Array();
	tmpvxor_or  = new Array();	
	for(i=0; i<64; i++)
	{
		tmpvand_and[i]=0;
		tmpvand_or[i] =0;
		tmpvxor_and[i]=0;
		tmpvxor_or[i] =0;
	}
	
	
	totAccColl = 0;
	for(cc2 in relsbas[sbas])
	{
		if( current = returnElement("mask_"+sbas+"_"+ relsbas[sbas][cc2]))
		{
			if( current.style.visibility=="visible" )
			{
				totAccColl++;
				
				for(i=0; i<64; i++)
				{		
					tmpvand_and[i]+=  parseInt((allmask[sbas][cc2].vand_and).substr(63-i,1));
					tmpvand_or[i] +=  parseInt((allmask[sbas][cc2].vand_or).substr(63-i,1));
					tmpvxor_and[i]+=  parseInt((allmask[sbas][cc2].vxor_and).substr(63-i,1));
					tmpvxor_or[i] +=  parseInt((allmask[sbas][cc2].vxor_or).substr(63-i,1));
				}
			}
		}
	}		
	tmpstateGD  = new Array();	
	tmpstateGD["vand_and"] = "" ;
	tmpstateGD["vand_or"]  = "" ;
	tmpstateGD["vxor_and"] = "" ;
	tmpstateGD["vxor_or"]  = "" ;
	
	tmpstateGD["tmpvand_and"] = "" ;
	tmpstateGD["tmpvand_or"]  = "" ;
	tmpstateGD["tmpvxor_and"] = "" ;
	tmpstateGD["tmpvxor_or"]  = "" ;
			
	
	for(i=0; i<64; i++)
	{
		tmpstateGD[i] = new Array();			
			
		if(tmpvand_and[i]==totAccColl && tmpvand_or[i]==0 && tmpvxor_and[i]==totAccColl && tmpvxor_or[i]==0 )//(tmpvand_and[i]=="1" && tmpvand_or[i]=="0")
		{
			tmpstateGD[i]["L"]=2;
			tmpstateGD[i]["R"]=2;
			tmpstateGD["vand_and"] = "1" + tmpstateGD["vand_and"] ;
			tmpstateGD["vand_or"]  = "0" + tmpstateGD["vand_or"]  ;
			tmpstateGD["vxor_and"] = "1" + tmpstateGD["vxor_and"] ;
			tmpstateGD["vxor_or"]  = "0" + tmpstateGD["vxor_or"]  ;
			 
					
		}
		else if(tmpvand_and[i]==0 && tmpvand_or[i]==0 && tmpvxor_and[i]==0 && tmpvxor_or[i]==0 )//(tmpvand_and[i]=="0" && tmpvand_or[i]=="0")
		{
			tmpstateGD[i]["L"]=1;
			tmpstateGD[i]["R"]=1;	
			tmpstateGD["vand_and"] = "0" + tmpstateGD["vand_and"] ;
			tmpstateGD["vand_or"]  = "0" + tmpstateGD["vand_or"]  ;
			tmpstateGD["vxor_and"] = "0" + tmpstateGD["vxor_and"] ;
			tmpstateGD["vxor_or"]  = "0" + tmpstateGD["vxor_or"]  ;
			 
		}
		else if(tmpvand_and[i]==totAccColl && tmpvand_or[i]==totAccColl && tmpvxor_and[i]==totAccColl && tmpvxor_or[i]==totAccColl )//(tmpvxor_and[i]=="1" && tmpvxor_or[i]=="1")
		{
			tmpstateGD[i]["L"]=0;
			tmpstateGD[i]["R"]=1;	
			tmpstateGD["vand_and"] = "1" + tmpstateGD["vand_and"] ;
			tmpstateGD["vand_or"]  = "1" + tmpstateGD["vand_or"]  ;
			tmpstateGD["vxor_and"] = "1" + tmpstateGD["vxor_and"] ;
			tmpstateGD["vxor_or"]  = "1" + tmpstateGD["vxor_or"]  ;
			 
		}
		else if( tmpvand_and[i]==totAccColl && tmpvand_or[i]==totAccColl && tmpvxor_and[i]==0 && tmpvxor_or[i]==0)//(tmpvxor_and[i]=="0" && tmpvxor_or[i]=="0")
		{
			tmpstateGD[i]["L"]=1;
			tmpstateGD[i]["R"]=0;	
			tmpstateGD["vand_and"] = "1" + tmpstateGD["vand_and"] ;
			tmpstateGD["vand_or"]  = "1" + tmpstateGD["vand_or"]  ;
			tmpstateGD["vxor_and"] = "0" + tmpstateGD["vxor_and"] ;
			tmpstateGD["vxor_or"]  = "0" + tmpstateGD["vxor_or"]  ;
		}	
		else
		{
			tmpstateGD[i]["L"]=2;
			tmpstateGD[i]["R"]=2;
			tmpstateGD["vand_and"] = "1" + tmpstateGD["vand_and"] ;
			tmpstateGD["vand_or"]  = "0" + tmpstateGD["vand_or"]  ;
			tmpstateGD["vxor_and"] = "1" + tmpstateGD["vxor_and"] ;
			tmpstateGD["vxor_or"]  = "0" + tmpstateGD["vxor_or"]  ;
		}
	}
	for(i=63; i>=0; i--)
	{		
		tmpstateGD["tmpvand_and"]+= "" + tmpvand_and[i];
		tmpstateGD["tmpvand_or"] += "" + tmpvand_or[i];
		tmpstateGD["tmpvxor_and"]+= "" + tmpvxor_and[i];
		tmpstateGD["tmpvxor_or"] += "" + tmpvxor_or[i];
	}
	
					
	return tmpstateGD;
}
function clkTimeSbas()
{
	closeMenuMask();	
	sbas =  curMaskSbas ;
	sbasname = curSbasName ;
	
	if( oo=returnElement("idspantitle") )
		oo.innerHTML = "<?php echo _('admin::user:quota: limite de duree')?><br>";
	if( oo=returnElement("idspanbase") )
		oo.innerHTML = "<?php echo _('phraseanet:: base')?> "+sbasname;
	if( oo=returnElement("idspancoll") )
		oo.innerHTML = "<i><?php echo _('admin::user: recapitulatif')?><\/i>";
	if( oo=returnElement("idspanline") )
		oo.innerHTML = "<u><?php echo _('admin::user:quota: limite temporelle')?> :<\/u>" ;
		 
	if( oo=returnElement("spacetabmiddle") )
	{
		newhtml ="";
		newhtml+="<table>";
		newhtml+="<tr>";
		newhtml+="<td colspan=\"2\">";
		
		
		
		
		var ladate=new Date();
		mois = (ladate.getMonth()+1);
		jour = ladate.getDate();
		if(mois.length<2)
			mois = "0"+ mois ;
		if(jour.length<2)
			jour = "0"+ jour ;
		today = "" + ladate.getFullYear() + "" + mois + "" + jour ;
		
		lastlimited = null;
		lastlimitedfrom = null;
		lastlimitedto = null ; 
		
		for(cc2 in relsbas[sbas])
		{
			if( current = returnElement("limit_"+sbas+"_"+ relsbas[sbas][cc2]))
			{
				if( current.style.visibility=="visible" )
				{ 
					if(lastlimited==null)
						lastlimited = alltimelimit[sbas][cc2].limited;
						
					if(alltimelimit[sbas][cc2].limited!=lastlimited)
						lastlimited=2;		
						
					if(alltimelimit[sbas][cc2].limited!=0)
					{
						if(lastlimitedfrom==null)
							lastlimitedfrom = alltimelimit[sbas][cc2].limitedfrom;
						
						if(lastlimitedfrom != alltimelimit[sbas][cc2].limitedfrom)
							lastlimitedfrom=today;
							
						if(lastlimitedto==null)
							lastlimitedto = alltimelimit[sbas][cc2].limitedto;
						 
						if(lastlimitedto != alltimelimit[sbas][cc2].limitedto)
							lastlimitedto = today;
					}	
				}
			}
		}		

		if(lastlimitedfrom==null)
			lastlimitedfrom = today;
			
		if(lastlimitedto==null)
			lastlimitedto  = today;
		
		ccckdyes = "0";
		ccckdno  = "1";
		activ 	 = "disabled";
		
		if( lastlimited==1 )
		{
			ccckdyes = "1";
			ccckdno  = "0";			
			activ 	 = "";
		}
		else if( lastlimited==2 )
		{
			ccckdyes = "2";
			ccckdno  = "2";			
			activ 	 = "disabled";
		}
		newhtml+="<img src=\"/skins/icons/ccoch"+ ccckdyes +".gif\" state=\""+ccckdyes+"\" id=\"ccochtimeyes\" sbas=\""+sbas+"\" onClick=\"clk_cc_time(true);return(false);\" > <?php echo _('phraseanet::oui')?>";
		newhtml+="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";		
		newhtml+="<img src=\"/skins/icons/ccoch"+ ccckdno +".gif\"  state=\""+ccckdno+"\"  id=\"ccochtimeno\" onClick=\"clk_cc_time(false);return(false);\"> <?php echo _('phraseanet::non')?>";		
		
 
		lastlimitedfrom = ""+lastlimitedfrom+"";
		yfrom = lastlimitedfrom.substr(0,4);
		mfrom = lastlimitedfrom.substr(4,2) ;
		dfrom = lastlimitedfrom.substr(6,2) ;
		lastlimitedto = ""+lastlimitedto+"";
		yto = lastlimitedto.substr(0,4);
		mto = lastlimitedto.substr(4,2) ;  
		dto = lastlimitedto.substr(6,2) ; 
  
		newhtml+="<\/td>";
		newhtml+="<\/tr>";
		
		newhtml+="<tr>";
		newhtml+="<td colspan=\"2\"><br>";
		newhtml+="<\/td>";
		newhtml+="<\/tr>";
		
		newhtml+="<tr>";
		
		newhtml+="<td style=\"text-align:right\"><?php echo _('admin::user:time: de (date)')?> :<\/td>";
		newhtml+="<td style=\"text-align:left\">";		
		newhtml+="<select id=\"timeRestdayfrom\" "+activ+" style=\"FONT-SIZE: 9px\" >";			              
		for(i=1;i<=31;i++)
		{
			chk ="";
			if(dfrom==i)chk="selected";			
			if(i<10)	newhtml+="<option "+chk+" value='0"+i+"'>0"+i+"<\/option>";
			else		newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}
		newhtml+="<\/select>";
		newhtml+="<select id=\"timeRestmonthfrom\" "+activ+" style=\"FONT-SIZE: 9px;\" >";		
		for(i=1;i<=12;i++)
		{
			chk ="";
			if(mfrom==i)chk="selected";			
			if(i<10)	newhtml+="<option "+chk+" value='0"+i+"'>0"+i+"<\/option>";
			else		newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}	
		newhtml+="<\/select>";
		newhtml+="<select id=\"timeRestyearfrom\" "+activ+" style=\"FONT-SIZE: 9px;\" >";		
		for(i=<?php echo (date("Y")-2)?>;i<=<?php echo (date("Y")+5)?>;i++)
		{
			chk ="";
			if(yfrom==i)chk="selected";
			newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}	
		newhtml+="<\/select>";		
		newhtml+="<\/td>";
		
		newhtml+="<\/tr>";;
		
		newhtml+="<tr><td>&nbsp;<\/td><\/tr>"; 
		
		newhtml+="<tr>";
		newhtml+="<td style=\"text-align:right\"><?php echo _('phraseanet::time:: a')?> :<\/td>";
		
		newhtml+="<td style=\"text-align:left\">";		
		newhtml+="<select id=\"timeRestdayto\" "+activ+" style=\"FONT-SIZE: 9px\" >";			              
		for(i=1;i<=31;i++)
		{
			chk ="";
			if(dto==i)chk="selected";			
			if(i<10)	newhtml+="<option "+chk+" value='0"+i+"'>0"+i+"<\/option>";
			else		newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}
		newhtml+="<\/select>";
		newhtml+="<select id=\"timeRestmonthto\" "+activ+" style=\"FONT-SIZE: 9px;\" >";		
		for(i=1;i<=12;i++)
		{
			chk ="";
			if(mto==i)chk="selected";			
			if(i<10)	newhtml+="<option "+chk+" value='0"+i+"'>0"+i+"<\/option>";
			else		newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}	
		newhtml+="<\/select>";
		newhtml+="<select id=\"timeRestyearto\" "+activ+" style=\"FONT-SIZE: 9px;\" >";		
		for(i=<?php echo (date("Y")-2)?>;i<=<?php echo (date("Y")+5)?>;i++)
		{
			chk ="";
			if(yto==i)chk="selected";
			newhtml+="<option "+chk+" value='"+i+"'>"+i+"<\/option>";
		}	
		newhtml+="<\/select>";		
 		newhtml+="<\/td>";
		
		newhtml+="<\/tr>";
		
		newhtml+="<\/table>";
		 
		 
		oo.innerHTML = "<center>"+ newhtml + "<\/center>";
	}
	
	if( oo=returnElement("idspanapply") )		
		oo.innerHTML = '<a href="javascript:void();" onClick="applyTimeBas(true);return(false);" style="color:#000000"><?php echo _('boutton::valider')?><\/a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void();" onClick="applyTimeBas(false);return(false);" style="color:#000000"><?php echo _('boutton::annuler')?><\/a>';

	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "hidden" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "hidden" ;
		
	view('SPECIAL');
}
function clkMaskBas()
{
	closeMenuMask();	
	sbas =  curMaskSbas ;
	sbasname = curSbasName ;

	if( oo=returnElement("idspantitle") )
		oo.innerHTML = "<?php echo _('admin::user: droits sur les status')?><br>";
	if( oo=returnElement("idspanbase") )
		oo.innerHTML = "<?php echo _('phraseanet:: base')?> "+sbasname;
	
	if( oo=returnElement("idspancoll") )
		oo.innerHTML = "<i><?php echo _('admin::user: recapitulatif')?><\/i>";
		 
	if( oo=returnElement("idspanline") )
		oo.innerHTML = "<u><?php echo _('admin::user: l\'utilisateur peut voir les documents')?> :<\/u>" ;
	if( oo=returnElement("spacetabmiddle") )
	{
		
		newhtml ="";
		 
		newhtml+="<table>";
		newhtml+="<tr>";
		newhtml+="<td style=\"text-align:left;width:170px\"><\/td>";
		newhtml+="<td style=\"text-align:left;width:170px\"><\/td>";
		newhtml+="<\/tr>";
		
		stateLR = calcul_etat_statusBAS(sbas);
		
		for( cc in statusname[sbas] )
		{
			newhtml+="<tr>";
			newhtml+="<td style=\"text-align:left\">";	
			
			newhtml+="<img src=\"/skins/icons/ccoch"+ stateLR[cc]["L"] +".gif\"  onClick=\"clkbit(this,"+cc+");\" state=\"" + stateLR[cc]["L"] +"\" sbas=\""+sbas+"\" id=\"bitnum"+cc+"-1\" comp=\"bitnum"+cc+"-2\" >";				
			newhtml+= statusname[sbas][cc].off;
			newhtml+="<\/td>";
			newhtml+="<td style=\"text-align:left; \">";
			
			newhtml+="<img src=\"/skins/icons/ccoch"+ stateLR[cc]["R"] +".gif\" onClick=\"clkbit(this,"+cc+");\" state=\"" + stateLR[cc]["R"] +"\" id=\"bitnum"+cc+"-2\" comp=\"bitnum"+cc+"-1\">";				
			newhtml+= statusname[sbas][cc].on;
			newhtml+="<\/td>";
			newhtml+="<\/tr>";
		}
		newhtml+="<\/table>";		 
		
		newhtml+="<input type=\"hidden\" name=\"vand_and\" value=\""+stateLR["vand_and"]+"\" id=\"vand_andtmp\">";		
		newhtml+="<input type=\"hidden\" name=\"vand_or\"  value=\""+stateLR["vand_or"]+"\"  id=\"vand_ortmp\" >";				
		newhtml+="<input type=\"hidden\" name=\"vxor_and\" value=\""+stateLR["vxor_and"]+"\"  id=\"vxor_andtmp\">";		
		newhtml+="<input type=\"hidden\" name=\"vxor_or\"  value=\""+stateLR["vxor_or"]+"\"  id=\"vxor_ortmp\" >";
		/*
		newhtml+="<table><tr><td style='align:left;text-align:left'>"
		newhtml+="<br><input type=\"text\" name=\"vand_and\" value=\""+ stateLR["tmpvand_and"] +"\"  xid=\"vand_andtmp\" style=\"width:500px\"> <== tmpvand_and";		
		newhtml+="<br><input type=\"text\" name=\"vand_or\"  value=\""+ stateLR["tmpvand_or"]  +"\"  xid=\"vand_ortmp\"  style=\"width:500px\"> <== tmpvand_or";				
		newhtml+="<br><input type=\"text\" name=\"vxor_and\" value=\""+ stateLR["tmpvxor_and"] +"\"  xid=\"vxor_andtmp\" style=\"width:500px\"> <== tmpvxor_and";		
		newhtml+="<br><input type=\"text\" name=\"vxor_or\"  value=\""+ stateLR["tmpvxor_or"]  +"\"  xid=\"vxor_ortmp\"  style=\"width:500px\"> <== tmpvxor_or<br>";
		
		newhtml+="<br><input type=\"text\" name=\"vand_and\" value=\""+ stateLR["vand_and"] +"\"  id=\"vand_andtmp\" style=\"width:500px\"> <== vand_and";		
		newhtml+="<br><input type=\"text\" name=\"vand_or\"  value=\""+ stateLR["vand_or"]  +"\"  id=\"vand_ortmp\"  style=\"width:500px\"> <== vand_or";				
		newhtml+="<br><input type=\"text\" name=\"vxor_and\" value=\""+ stateLR["vxor_and"] +"\"  id=\"vxor_andtmp\" style=\"width:500px\"> <== vxor_and";		
		newhtml+="<br><input type=\"text\" name=\"vxor_or\"  value=\""+ stateLR["vxor_or"]  +"\"  id=\"vxor_ortmp\"  style=\"width:500px\"> <== vxor_or";
		newhtml+="<br>";
		for(i=63; i>=0; i--)
			newhtml+=stateLR[i]["L"];
		
		newhtml+=" <== left<br>";
		for(i=63; i>=0; i--)
			newhtml+=stateLR[i]["R"];
		newhtml+=" <== right<br>";
		newhtml+="<\/td><\/tr><\/table>"
		*/
		oo.innerHTML = "<center>"+ newhtml + "<\/center>";

	}
	if( oo=returnElement("idspanapply") )		
		oo.innerHTML = '<a href="javascript:void();" onClick="applyMaskBas(true);return(false);" style="color:#000000"><?php echo _('boutton::valider')?><\/a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void();" onClick="applyMask(false);return(false);" style="color:#000000"><?php echo _('boutton::annuler')?><\/a>';

	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "hidden" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "hidden" ;
		
	view('SPECIAL');
}
function applyMaskBas(bool)
{
	if(bool)
	{
			if( (tmpvandand = document.getElementById("vand_andtmp"))
			  &&(tmpvandor  = document.getElementById("vand_ortmp") ) 
			  &&(tmpvxorand = document.getElementById("vxor_andtmp"))
			  &&(tmpvxoror  = document.getElementById("vxor_ortmp") )
			  &&(bit0       = document.getElementById("bitnum0-1")  )
				 )
			{		
				var b0sbas = bit0.getAttribute('sbas');
				for(cc2 in relsbas[b0sbas])
				{
					if( current = returnElement("mask_"+b0sbas+"_"+ relsbas[b0sbas][cc2]))
					{
						if( current.style.visibility=="visible" )
						{
							for(i=0; i<64; i++)
							{
								tmpstateGD[i] = new Array();
								
								updvand_and =  (tmpvandand.value).substr(63-i,1);
								updvand_or  =  (tmpvandor.value).substr(63-i,1);
								updvxor_and =  (tmpvxorand.value).substr(63-i,1);
								updvxor_or  =  (tmpvxoror.value).substr(63-i,1);
								
								if(updvand_and==1 && updvand_or==0 && updvxor_and==1 && updvxor_or==0)
								{
									//grisee, on change rien
								}
								else
								{
									allmask[b0sbas][cc2].vand_and = (allmask[b0sbas][cc2].vand_and).substr(0,63-i) + updvand_and + (allmask[b0sbas][cc2].vand_and).substring(64-i);
									allmask[b0sbas][cc2].vand_or  = (allmask[b0sbas][cc2].vand_or).substr(0,63-i)  + updvand_or  + (allmask[b0sbas][cc2].vand_or).substring(64-i) ;
									allmask[b0sbas][cc2].vxor_and = (allmask[b0sbas][cc2].vxor_and).substr(0,63-i) + updvxor_and + (allmask[b0sbas][cc2].vxor_and).substring(64-i);
									allmask[b0sbas][cc2].vxor_or  = (allmask[b0sbas][cc2].vxor_or).substr(0,63-i)  + updvxor_or  + (allmask[b0sbas][cc2].vxor_or).substring(64-i) ;
									 
									modif["vandand_"+b0sbas+"_"+cc2] = allmask[b0sbas][cc2].vand_and;
									modif["vandor_"+b0sbas+"_"+cc2]  = allmask[b0sbas][cc2].vand_or;
									modif["vxorand_"+b0sbas+"_"+cc2] = allmask[b0sbas][cc2].vxor_and ;
									modif["vxoror_"+b0sbas+"_"+cc2]  = allmask[b0sbas][cc2].vxor_or;
									
								}
							}
						}
					}
				}			
			}
	}
	
	view('RIGHTS');
	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "visible" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "visible" ;
}
function calcul_etat_status(sbas,bas,cc)
{
	tmpstateGD = new Array();
	for(i=0; i<64; i++)
	{
		tmpstateGD[i] = new Array();
		
		tmpvand_and =  (allmask[sbas][bas].vand_and).substr(63-i,1);
		tmpvand_or  =  (allmask[sbas][bas].vand_or).substr(63-i,1);
		tmpvxor_and =  (allmask[sbas][bas].vxor_and).substr(63-i,1);
		tmpvxor_or  =  (allmask[sbas][bas].vxor_or).substr(63-i,1);
	
		tmpstateGD[i]["L"]=2;
		tmpstateGD[i]["R"]=2;
			
		if(tmpvand_and=="1" && tmpvand_or=="0")
		{
			tmpstateGD[i]["L"]=2;
			tmpstateGD[i]["R"]=2;			
		}
		else if(tmpvand_and=="0" && tmpvand_or=="0")
		{
			tmpstateGD[i]["L"]=1;
			tmpstateGD[i]["R"]=1;	
		}
		else if(tmpvxor_and=="1" && tmpvxor_or=="1")
		{
			tmpstateGD[i]["L"]=0;
			tmpstateGD[i]["R"]=1;	
		}
		else if(tmpvxor_and=="0" && tmpvxor_or=="0")
		{
			tmpstateGD[i]["L"]=1;
			tmpstateGD[i]["R"]=0;	
		}
		
	}
	return tmpstateGD;
}
function rewritebit(obj,bitnum)
{
	// normalement ici, le bit change ne peux avoir que 2 etats
	// Coche ou decoche
	// mais pas gris
	// et idem pour son inverse
	if( (bitleft=document.getElementById("bitnum"+bitnum+"-1")) && (bitright=document.getElementById("bitnum"+bitnum+"-2")) )
	{
		if( bitleft.getAttribute('state')=="1" && bitright.getAttribute('state')=="0" )
		{
			if( oo=document.getElementById("vand_andtmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"1"+((oo.value).substr(63+1-bitnum));
			if( oo=document.getElementById("vand_ortmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"1"+((oo.value).substr(63+1-bitnum));
			if( oo=document.getElementById("vxor_andtmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"0"+((oo.value).substr(63+1-bitnum));
			if( oo=document.getElementById("vxor_ortmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"0"+((oo.value).substr(63+1-bitnum));
		}
		else if( bitleft.getAttribute('state')=="0" && bitright.getAttribute('state')=="1" )
		{
			if( oo=document.getElementById("vand_andtmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"1"+((oo.value).substr(63+1-bitnum));
			if( oo=document.getElementById("vand_ortmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"1"+((oo.value).substr(63+1-bitnum));
			if( oo=document.getElementById("vxor_andtmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"1"+((oo.value).substr(63+1-bitnum));
			if( oo=document.getElementById("vxor_ortmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"1"+((oo.value).substr(63+1-bitnum));
		}
		else if( bitleft.getAttribute('state')=="1" && bitright.getAttribute('state')=="1" )
		{
			if( oo=document.getElementById("vand_andtmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"0"+((oo.value).substr(63+1-bitnum));
			if( oo=document.getElementById("vand_ortmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"0"+((oo.value).substr(63+1-bitnum));
			if( oo=document.getElementById("vxor_andtmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"0"+((oo.value).substr(63+1-bitnum));
			if( oo=document.getElementById("vxor_ortmp") )
				oo.value = ( (oo.value).substr(0,63-bitnum) )+"0"+((oo.value).substr(63+1-bitnum));
		}		
	}	
}
function clkbit(obj,bitnum)
{
//	alert(bitnum + "  " + obj.getAttribute('state'));
	switch(obj.getAttribute('state'))
	{
		case "2": // gris
			// on coche les 2 !
			obj.src = "/skins/icons/ccoch1.gif";
			obj.setAttribute('state', "1");
			if(obj = document.getElementById(obj.getAttribute('comp')))
			{
				obj.src = "/skins/icons/ccoch1.gif";
				obj.setAttribute('state', "1");
			}
			break;
		case "1": // coche
			// on decoche
			// attention on verifie que son contraire n'est pas decoche
			if(document.getElementById(obj.getAttribute('comp')).getAttribute('state')=="1")
			{
				obj.src = "/skins/icons/ccoch0.gif";
				obj.setAttribute('state', "0");
			}
			else
			{
				alert("<?php echo _('admin::user:mask: vous devez cocher au moins une case pour chaque status')?>");			
				return;
			}
			break;
		case "0":	// decoche
			// on coche	
			obj.src = "/skins/icons/ccoch1.gif";
			obj.setAttribute('state', "1");			
			break;
	}
	rewritebit(obj,bitnum);
}

function clkMask(sbas,bas, sbasname, basname)
{
	if( oo=returnElement("idspantitle") )
		oo.innerHTML = "<?php echo _('admin::user: droits sur les status')?><br>";
	if( oo=returnElement("idspanbase") )
		oo.innerHTML = "<?php echo _('phraseanet:: base')?> "+sbasname;
	if( oo=returnElement("idspancoll") )
		oo.innerHTML = " <?php echo _('phraseanet:: collection')?> "+basname;
	if( oo=returnElement("idspanline") )
		oo.innerHTML = "<u><?php echo _('admin::user: l\'utilisateur peut voir les documents')?> :<\/u>" ;
	if( oo=returnElement("spacetabmiddle") )
	{
		
		newhtml ="";
		 
		newhtml+="<table>";
		newhtml+="<tr>";
		newhtml+="<td style=\"text-align:left;width:170px\"><\/td>";
		newhtml+="<td style=\"text-align:left;width:170px\"><\/td>";
		newhtml+="<\/tr>";
		
		stateLR = calcul_etat_status(sbas,bas);
		
		for( cc in statusname[sbas] )
		{
			newhtml+="<tr>";
			newhtml+="<td style=\"text-align:left\">";	
			
			newhtml+="<img src=\"/skins/icons/ccoch"+ stateLR[cc]["L"] +".gif\"  onClick=\"clkbit(this,"+cc+");\" state=\"" + stateLR[cc]["L"] +"\" sbas=\""+sbas+"\" bas=\""+bas+"\" id=\"bitnum"+cc+"-1\" comp=\"bitnum"+cc+"-2\" >";				
			newhtml+= statusname[sbas][cc].off;
			newhtml+="<\/td>";
			newhtml+="<td style=\"text-align:left; \">";
			
			newhtml+="<img src=\"/skins/icons/ccoch"+ stateLR[cc]["R"] +".gif\" onClick=\"clkbit(this,"+cc+");\" state=\"" + stateLR[cc]["R"] +"\" id=\"bitnum"+cc+"-2\" comp=\"bitnum"+cc+"-1\">";				
			newhtml+= statusname[sbas][cc].on;
			newhtml+="<\/td>";
			newhtml+="<\/tr>";
		}
		newhtml+="<\/table>";		 
		
		newhtml+="<input type=\"hidden\" name=\"vand_and\" value=\""+allmask[sbas][bas].vand_and+"\" id=\"vand_andtmp\">";		
		newhtml+="<input type=\"hidden\" name=\"vand_or\"  value=\""+allmask[sbas][bas].vand_or+"\"  id=\"vand_ortmp\" >";				
		newhtml+="<input type=\"hidden\" name=\"vxor_and\" value=\""+allmask[sbas][bas].vxor_and+"\"  id=\"vxor_andtmp\">";		
		newhtml+="<input type=\"hidden\" name=\"vxor_or\"  value=\""+allmask[sbas][bas].vxor_or+"\"  id=\"vxor_ortmp\" >";
		/*
		newhtml+="<br><input type=\"text\" name=\"vand_and\" value=\""+allmask[sbas][bas].vand_and+"\" id=\"vand_andtmp\" style=\"width:500px\"> <== vand_and";		
		newhtml+="<br><input type=\"text\" name=\"vand_or\"  value=\""+allmask[sbas][bas].vand_or+"\"  id=\"vand_ortmp\"  style=\"width:500px\"> <== vand_or";				
		newhtml+="<br><input type=\"text\" name=\"vxor_and\" value=\""+allmask[sbas][bas].vxor_and+"\"  id=\"vxor_andtmp\" style=\"width:500px\"> <== vxor_and";		
		newhtml+="<br><input type=\"text\" name=\"vxor_or\"  value=\""+allmask[sbas][bas].vxor_or+"\"  id=\"vxor_ortmp\"  style=\"width:500px\"> <== vxor_or";
		*/
		
		
		
		// oo.innerHTML = newhtml;
		oo.innerHTML = "<center>"+ newhtml + "<\/center>";
		
	}
	if( oo=returnElement("idspanapply") )		
		oo.innerHTML = '<a href="javascript:void();" onClick="applyMask(true);return(false);" style="color:#000000"><?php echo _('boutton::valider')?><\/a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void();" onClick="applyMask(false);return(false);" style="color:#000000"><?php echo _('boutton::annuler')?><\/a>';

	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "hidden" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "hidden" ;
		
	view('SPECIAL');
}

function applyMask(bool)
{
	if(bool)
	{
			if( (tmpvandand = document.getElementById("vand_andtmp"))
			  &&(tmpvandor  = document.getElementById("vand_ortmp") ) 
			  &&(tmpvxorand = document.getElementById("vxor_andtmp"))
			  &&(tmpvxoror  = document.getElementById("vxor_ortmp") )
			  &&(bit0       = document.getElementById("bitnum0-1")  )
				 )
			{
				var b0sbas = bit0.getAttribute('sbas');
				var b0bas  = bit0.getAttribute('bas');
				allmask[b0sbas][b0bas].vand_and = tmpvandand.value ;
				allmask[b0sbas][b0bas].vand_or  = tmpvandor.value ;
				allmask[b0sbas][b0bas].vxor_and = tmpvxorand.value ;
				allmask[b0sbas][b0bas].vxor_or  = tmpvxoror.value ;
				 
				modif["vandand_"+b0sbas+"_"+b0bas] = tmpvandand.value;
				modif["vandor_"+b0sbas+"_"+b0bas]  = tmpvandor.value;
				modif["vxorand_"+b0sbas+"_"+b0bas] = tmpvxorand.value;
				modif["vxoror_"+b0sbas+"_"+b0bas]  = tmpvxoror.value;
				
			}
	}
	view('RIGHTS');
	if( oo=returnElement("genecancel") )	
		oo.style.visibility = "visible" ;
	if( oo=returnElement("genevalid") )	
		oo.style.visibility = "visible" ;
}
function chkseepwd(obj)
{
	if(obj.getAttribute('chk')=="1")
	{
		// on decheck
		obj.setAttribute('chk', "0");
		obj.src="/skins/icons/ccoch0.gif";
		modif["seepwd"]="0";
	}
	else
	{
		obj.setAttribute('chk', "1");
		obj.src="/skins/icons/ccoch1.gif";
		modif["seepwd"]="1";
	}
}
var pass=false;

function verify()
{
	wb = document.getElementById("divref").offsetWidth;
	if(wb<150)
		wb= 150;
	document.getElementById("tableau").style.width = (wb-18)+"px";
	document.getElementById("tableau_center").style.width = (wb-18-123)+"px";
	document.getElementById("tableau_top").style.width = (wb-18-123)+"px";
	document.getElementById("presentUser").style.width = (wb-10)+"px";
	/***************************************************/
	if(document.all)
	{ 
		if(document.documentElement.clientHeight)
			bodyH = document.documentElement.clientHeight - 5 ;			
		else
			bodyH = document.body.clientHeight - 5 ;
		scrollLeft = null;
		if(document.documentElement.scrollLeft)
			scrollLeft = document.documentElement.scrollLeft;
		else
			scrollLeft = document.body.scrollLeft;	
		scrolltop = null;
		if(document.documentElement.scrollTop)
		{
			scrolltop = document.documentElement.scrollTop;
		 document.documentElement.scrollTop = 0 ;
		}
		else
		{
			scrolltop = document.body.scrollTop;
			document.body.scrollTop=0;
		}
	}
	else
	{
		bodyH =  parent.window.document.clientHeight;	
		if(!bodyH)
			if(document.documentElement.clientHeight)
				bodyH = document.documentElement.clientHeight - 5 ;			
			else
				bodyH = document.body.clientHeight - 5 ;
		scrollLeft = null;
		if(document.documentElement.scrollLeft)
			scrollLeft = document.documentElement.scrollLeft;
		else
			scrollLeft = document.body.scrollLeft;
		scrolltop = null;		
		if(document.documentElement.scrollTop)
		{
			scrolltop = document.documentElement.scrollTop;
			 document.documentElement.scrollTop = 0;
		}
		else
		{
			scrolltop = document.body.scrollTop;
			document.body.scrollTop=0;
		}
	}
	/***************************************************/
	hauteur =  document.getElementById("spanref").offsetTop;
	hauteur =  bodyH-10;
	hauteur =  bodyH-30;
	if(hauteur<10)
		hauteur = document.getElementById("spanref").clientHeight;
	if(hauteur<250)
		hauteur= 250;
//	document.getElementById("tableau_left").style.height = (hauteur-220)+"px";
//	document.getElementById("tableau_center").style.height = (hauteur-220)+"px";
	document.getElementById("tableau_left").style.height = (hauteur-228)+"px";
	document.getElementById("tableau_center").style.height = (hauteur-228)+"px";

	document.getElementById("tableau").style.height = (hauteur-160)+"px";
	document.getElementById("divIdt").style.height = (hauteur-160)+"px";
	document.getElementById("divSpecial").style.height = (hauteur-160)+"px";
	
	hspacetabmiddle = (hauteur-160-150);
	if(hspacetabmiddle<120)
		hspacetabmiddle=120;
	document.getElementById("spacetabmiddle").style.height = (hspacetabmiddle)+"px";
	
	
	if (o = returnElement("iddivloading") )
	{
//		o.style.width = (wb)+"px";
//		o.style.left = "0px";
//		o.style.top = "0px";
//		o.style.height = (hauteur)+"px";
	}

	tableScroll(document.getElementById("tableau_center") );
}	

function redrawme()
{
//	return;
	
	wb = document.getElementById("divref").offsetWidth;
	
	
	if(wb<150)
		wb= 150;
	
		
	document.getElementById("tableau").style.width = (wb-18)+"px";
	document.getElementById("tableau_center").style.width = (wb-18-123)+"px";
	document.getElementById("tableau_top").style.width = (wb-18-123)+"px";
	document.getElementById("presentUser").style.width = (wb-10)+"px";
	
	
	
	/***************************************************/
	if(document.all)
	{ 
		if(document.documentElement.clientHeight)
			bodyH = document.documentElement.clientHeight - 5 ;			
		else
			bodyH = document.body.clientHeight - 5 ;
		scrollLeft = null;
		if(document.documentElement.scrollLeft)
			scrollLeft = document.documentElement.scrollLeft;
		else
			scrollLeft = document.body.scrollLeft;	
		scrolltop = null;
		if(document.documentElement.scrollTop)
		{
			scrolltop = document.documentElement.scrollTop;
		 document.documentElement.scrollTop = 0 ;
		}
		else
		{
			scrolltop = document.body.scrollTop;
			document.body.scrollTop=0;
			
		}
//		window.status = "bodyH:" + bodyH + "   scrollLeft:" + scrollLeft+ "   scrolltop:" +   scrolltop;	 	
	}
	else
	{
	
		bodyH =  parent.window.document.clientHeight;	
		if(!bodyH)
			if(document.documentElement.clientHeight)
				bodyH = document.documentElement.clientHeight - 5 ;			
			else
				bodyH = document.body.clientHeight - 5 ;
		scrollLeft = null;
		if(document.documentElement.scrollLeft)
			scrollLeft = document.documentElement.scrollLeft;
		else
			scrollLeft = document.body.scrollLeft;
		scrolltop = null;		
		if(document.documentElement.scrollTop)
		{
			scrolltop = document.documentElement.scrollTop;
			 document.documentElement.scrollTop = 0;
		}
		else
		{
			scrolltop = document.body.scrollTop;
			document.body.scrollTop=0;
		}
//		window.status = "bodyH:" + bodyH + "   scrollLeft:" + scrollLeft+ "   scrolltop:" +   scrolltop;
	}
	/***************************************************/
	hauteur =  document.getElementById("spanref").offsetTop;
	hauteur =  bodyH-10;
	hauteur =  bodyH-30;
	if(hauteur<10)
	{
	
		hauteur = document.getElementById("spanref").clientHeight;
	}
	if(hauteur<250)
		hauteur= 250;

//	document.getElementById("tableau_left").style.height = (hauteur-220)+"px";
//	document.getElementById("tableau_center").style.height = (hauteur-220)+"px";
	 
	document.getElementById("tableau_left").style.height = (hauteur-228)+"px";
	document.getElementById("tableau_center").style.height = (hauteur-228)+"px";

	document.getElementById("tableau").style.height = (hauteur-160)+"px";
	document.getElementById("divIdt").style.height = (hauteur-160)+"px";
	document.getElementById("divSpecial").style.height = (hauteur-160)+"px";
	
	hspacetabmiddle = (hauteur-160-150);
	if(hspacetabmiddle<120)
		hspacetabmiddle=120;
	document.getElementById("spacetabmiddle").style.height = (hspacetabmiddle)+"px";
	
	
	if (o = returnElement("iddivloading") )
	{
//		o.style.width = (w)+"px";
//		o.style.left = "0px";
//		o.style.top = "0px";
//		o.style.height = (hauteur)+"px";
	}
	tableScroll(document.getElementById("tableau_center") );
	self.setTimeout("verify();",1000);
}	


function returnElement(unId)
{
	  if(! allgetID[unId] )
	  { 
	  	 if( document.getElementById(unId) )
	  	 {
	  	 	allgetID[unId] = document.getElementById(unId);
	  	 }
	  }
	  
	  return allgetID[unId];
}



function clk_chbx(obj, sbas, bas, name )
{
	if(0+obj.getAttribute('state') > 2)
		return;
		
	
		
	oldstate = 	obj.getAttribute('state');
	if(obj.getAttribute('state')=="0")
		obj.setAttribute('state', "1");
	else
		obj.setAttribute('state', "0");
	obj.src="/skins/icons/ccoch" + obj.getAttribute('state') + ".gif";
	
	

	/** cas specail de sbas */
	if( name=="basmodifstruct" || name=="basmanage" || name=="basmodifth" || name=="baschupub")	
/** modif **/modif[name+"_"+sbas] = obj.getAttribute('state');
	else
/** modif **/modif[name+"_"+sbas+"_"+bas] = obj.getAttribute('state');

	if(name=="acces")
	{
		list = new Array("actif","album", "canprev","water","canhd","dlprev","dlhd","cmd","quota","limit","mask",
						"addrec","modifrec","chgstat","delrec","imgtools","admin","report","push","manage","modifstruct" );
		for(cc in list)
		{
			if(objtmp = returnElement(list[cc]+"_"+sbas+"_"+bas ))
			{
				if(obj.getAttribute('state')=="1")
					objtmp.style.visibility = "visible";
				else
					objtmp.style.visibility = "hidden";
			}
			
		}
		if(oldstate=="0" && obj.getAttribute('state')=="1" )
		{
			if(totaccpersbas[sbas]==0)
			{
				if(objtmp = returnElement("basmodifth_"+sbas))
					objtmp.style.visibility = "visible";
				if(objtmp = returnElement("basmanage_"+sbas))
					objtmp.style.visibility = "visible";
				if(objtmp = returnElement("basmodifstruct_"+sbas))
					objtmp.style.visibility = "visible";
				if(objtmp = returnElement("baschupub_"+sbas))
					objtmp.style.visibility = "visible";
			}
			totaccpersbas[sbas]++;	
		}		
		else if(oldstate=="1" && obj.getAttribute('state')=="0" )
		{
			totaccpersbas[sbas]--;
			if(totaccpersbas[sbas]==0)
			{
				if(objtmp = returnElement("basmodifth_"+sbas))
					objtmp.style.visibility = "hidden";
				if(objtmp = returnElement("basmanage_"+sbas))
					objtmp.style.visibility = "hidden";
				if(objtmp = returnElement("basmodifstruct_"+sbas))
					objtmp.style.visibility = "hidden";
				if(objtmp = returnElement("baschupub_"+sbas))
					objtmp.style.visibility = "hidden";
			}
		}
						
	}
}



var desktopMenuOutTimer = null;
var MenuMaskOutTimer = null;
var desktopMenuXPos = null;
var desktopMenuYPos = null;
var desktopMenuJustOpened = null;	// corrige safari qui declenche l'option qui se trouve sous la souris des l'ouverture du menu
var MenuMaskJustOpened = null;	// corrige safari qui declenche l'option qui se trouve sous la souris des l'ouverture du menu
var objcur = null;

var curMaskSbas = null ;
var curSbasName = null;


function is_ctrl_key(event)
{
	if(event.altKey)
		return true;
	if(event.ctrlKey)
		return true;
	if(event.metaKey)	// apple key opera
		return true;
	if(event.keyCode == '17')	// apple key opera
		return true;
	if(event.keyCode == '224')	// apple key mozilla
		return true;
	if(event.keyCode == '91')	// apple key safari
		return true;
	
	return false;
}

function is_shift_key(event)
{
	if(event.shiftKey)
		return true;
	return false;
}

function viewmenu(event)
{
	inmenu = false;
	inmask = false;
	if(typeof(event)=='undefined')
	{
		event = window.event;
	}
	if ( (!document.all && event.which == 3) ||  (!document.all && is_ctrl_key(event)) || (document.all && event.button && event.button==2))
	{
		var srcElement = (event.target) ? event.target : event.srcElement;
		for(obj=srcElement; obj && (!obj.tagName || obj.tagName!="TD"); obj=obj.parentNode)
			;
		
		 if(obj && obj.getAttribute('colname') && obj.getAttribute('colsbas') && (totaccpersbas[obj.getAttribute('colsbas')]>0 || obj.getAttribute('colname')=="acces") )
		 {
			
			if( (fparnt=obj.parentNode) && (sparnt=fparnt.parentNode) && (tparnt=sparnt.parentNode) &&  (tparnt.tagName=="TABLE") && (tparnt.id=="tableau") )
			{
				
				if( obj.getAttribute('colname')=="mask" || obj.getAttribute('colname')=="quota" || obj.getAttribute('colname')=="time" )
				{
					if( obj.getAttribute('colname')=="mask" )
					{
						if(o = returnElement("MenuMask"))
							o.innerHTML = '<div style="height:30px; " ><A href="javascript:void();" onclick="clkMaskBas();return(false);"><center><?php echo _('admin::user: editer les recapitulatif des acces par status de la base')?><\/center><\/A><\/div>';
					}
					if( obj.getAttribute('colname')=="quota" )
					{
						if(o = returnElement("MenuMask"))
							o.innerHTML = '<div style="height:30px; " ><A href="javascript:void();" onclick="clkQuotaBas();return(false);"><center><?php echo _('admin::user: editer les recapitulatif des quotas de la base')?><\/center><\/A><\/div>';
					}
					if( obj.getAttribute('colname')=="time" )
					{
						if(o = returnElement("MenuMask"))
							o.innerHTML = '<div style="height:30px; " ><A href="javascript:void();" onclick="clkTimeSbas();return(false);"><center><?php echo _('admin::user: editer les recapitulatif des limites de duree de la base')?><\/center><\/A><\/div>';
					}
				 	closeMenu();
					curMaskSbas = obj.getAttribute('colsbas');
				 	curSbasName = obj.getAttribute('sbasname');
					inmask = true;
					if(o = returnElement("MenuMask"))
					{
						o.style.left = (desktopMenuXPos = event.clientX-5)+"px";
						o.style.top  = (desktopMenuYPos = event.clientY-5)+"px";
				
						o.style.display = "block";
						desktopMenuJustOpened = true;
				
						bodyW = returnElement("idBody").clientWidth;
						bodyH = returnElement("idBody").clientHeight;
				
						if(bodyW<(o.scrollWidth+ event.clientX-5) )
							o.style.left = (desktopMenuXPos = (bodyW-o.scrollWidth-5))+"px";
				
						if(bodyH<(o.scrollHeight+ event.clientY-5) )
							o.style.top = (desktopMenuYPos = (bodyH-o.scrollHeight-5))+"px";
						MenuMaskJustOpened = true;	
					}
				}
				else
				{	
					inmenu = true;
					if(o = returnElement("desktopMenu"))
					{
						o.style.left = (desktopMenuXPos = event.clientX-5)+"px";
						o.style.top = (desktopMenuYPos = event.clientY-5)+"px";
				
						o.style.display = "block";
						desktopMenuJustOpened = true;
				
						bodyW = returnElement("idBody").clientWidth;
						bodyH = returnElement("idBody").clientHeight;
				
						if(bodyW<(o.scrollWidth+ event.clientX-5) )
							o.style.left = (desktopMenuXPos = (bodyW-o.scrollWidth-5))+"px";
				
						if(bodyH<(o.scrollHeight+ event.clientY-5) )
							o.style.top = (desktopMenuYPos = (bodyH-o.scrollHeight-5))+"px";
						desktopMenuJustOpened = true;	
						objcur = obj;
					}
				}
			}	
		 }
	}
	else
	{
		if(!over)
			closeMenu();
		if(!overMask)
			closeMenuMask();
	}
	if(inmenu)
		self.setTimeout("desktopMenuJustOpened = false;",500);	
	if(inmask)
		self.setTimeout("MenuMaskJustOpened = false;",500);	
		
		
		
	if( obj=event.target)
	{
	
	//	pour safari :
		if(obj.id=="ismodel" 
			|| obj.id=="selectidmodel" 
			|| obj.id=="tableau_center" 
			|| obj.id=="usr_password" 
			|| obj.id=="usr_password_first" 
			|| obj.id=="divSpecial" 
			|| obj.id=="spacetabmiddle"
			|| obj.id=="timeRestyearfrom"
			|| obj.id=="timeRestmonthfrom"
			|| obj.id=="timeRestdayfrom"
			|| obj.id=="timeRestyearto"
			|| obj.id=="timeRestmonthto"
			|| obj.id=="timeRestdayto"
			|| obj.id=="maxquota"
			|| obj.id=="remainquota"
			|| obj.id=="idusr_sexe"
			
			|| obj.name=="usr_nom"
			|| obj.name=="usr_prenom"
			|| obj.name=="usr_mail"
			|| obj.name=="fonction"
			|| obj.name=="societe"
			|| obj.name=="activite"
			|| obj.name=="tel"
			|| obj.name=="fax"
			|| obj.name=="adresse"
			|| obj.name=="cpostal"
			|| obj.name=="ville"
			|| obj.name=="pays"
			
			|| obj.name=="addrFTP"
			|| obj.name=="loginFTP"
			|| obj.name=="pwdFTP"
			|| obj.name=="destFTP"
			|| obj.name=="prefixFTPfolder"
			|| obj.name=="activeFTP"
			|| obj.name=="passifFTP"
			|| obj.name=="retryFTP"
			|| obj.name=="ccsentftphd"
			|| obj.name=="ccsentftpprev"
			|| obj.name=="ccsentftpcaption"
			|| obj.name=="canchgprofil"
			|| obj.name=="canchgftpprofil"
			)
			return true;
	}
	return false;
}
function closeMenu()
{
	if(desktopMenuJustOpened)
		return;
	if(o = returnElement("desktopMenu"))
		o.style.display = "none";
	//objcur = null ;	
}	

function closeMenuMask()
{
	if(MenuMaskJustOpened)
		return;
	if(o = returnElement("MenuMask"))
		o.style.display = "none";
	//objcur = null ;	
}

var over=false;
function evt_overMenu()
{
	over=true;
	self.clearTimeout(desktopMenuOutTimer);
}
function evt_outMenu()
{
	over=false;
	desktopMenuOutTimer = self.setTimeout("closeMenu();",1200);
}

var overMask=false;
function evt_overMenuMask()
{
	overMask=true;
	self.clearTimeout(MenuMaskOutTimer);
}
function evt_outMenuMask()
{
	overMask=false;
	MenuMaskOutTimer = self.setTimeout("closeMenuMask();",1000);

}




function uncheckall()
{
	closeMenu();	
	if(objcur.getAttribute('colname')=="acces")
	{
		// unchckAllAcces();
		if (o = returnElement("iddivloading") )
			o.style.visibility = "visible";
		 self.setTimeout("unchckAllAcces();",50);		 
	}
	else
	{
		uncheckAll2();
	}
}
function checkall()
{
	closeMenu();	
	if(objcur.getAttribute('colname')=="acces")
	{
		if (o = returnElement("iddivloading") )
		{
			o.innerHTML = "<table style='width:100%;height:100%; text-align:center;valign:middle:; color:#FF0000; font-size:20px'><tr><td><div style='background-color:#FFFFFF'><b><?php echo _('phraseanet::chargement')?><\/b><\/div><\/td><\/tr><\/table>";
			o.style.visibility = "visible";
		}
		 self.setTimeout("chckAllAcces();",50);
		// chckAllAcces();
	}
	else
		checkAll2();
}

function chckAllAcces()
{
	// coche une colone d'acces	
	closeMenu();
	for(cc2 in relsbas[objcur.getAttribute('colsbas')])
	{
			
		if( current = returnElement(""+ objcur.getAttribute('colname') +"_"+objcur.getAttribute('colsbas')+"_"+ relsbas[objcur.getAttribute('colsbas')][cc2]))
		{
			// clk_chbx(current, objcur.colsbas, relsbas[objcur.colsbas][cc2], objcur.colname );
			if( (0+current.getAttribute('state')) != 1)
			{
				current.setAttribute('state', 1);
				current.src="/skins/icons/ccoch" + current.getAttribute('state') + ".gif";
				
/** modif **/   modif[objcur.getAttribute('colname') +"_"+objcur.getAttribute('colsbas')+"_"+ relsbas[objcur.getAttribute('colsbas')][cc2]] = 1;

				if(totaccpersbas[objcur.getAttribute('colsbas')]==0)
				{
					if(objtmp = returnElement("basmodifth_"+objcur.getAttribute('colsbas')))
						objtmp.style.visibility = "visible";
					if(objtmp = returnElement("basmanage_"+objcur.getAttribute('colsbas')))
						objtmp.style.visibility = "visible";
					if(objtmp = returnElement("basmodifstruct_"+objcur.getAttribute('colsbas')))
						objtmp.style.visibility = "visible";
					if(objtmp = returnElement("baschupub_"+objcur.getAttribute('colsbas')))
						objtmp.style.visibility = "visible";
				}
				totaccpersbas[objcur.getAttribute('colsbas')]++;
				
				
				list = new Array("actif","album", "canprev","water","canhd","dlprev","dlhd","cmd","quota","limit","mask",
						"addrec","modifrec","chgstat","delrec","imgtools","admin","report","push","manage","modifstruct" );
				for(cc in list)
				{
					if(objtmp = returnElement(list[cc]+"_"+objcur.getAttribute('colsbas')+"_"+relsbas[objcur.getAttribute('colsbas')][cc2] ))
					{
						objtmp.style.visibility = "visible";
					}
				}
			}
		}
	}
	if (o=returnElement("iddivloading") )
		o.style.visibility = "hidden";
}

function unchckAllAcces()
{
	closeMenu();
	// coche une colone d'acces	
	for(cc2 in relsbas[objcur.getAttribute('colsbas')])
	{
		if( current = returnElement(""+ objcur.getAttribute('colname') +"_"+objcur.getAttribute('colsbas')+"_"+ relsbas[objcur.getAttribute('colsbas')][cc2]))
		{
			// clk_chbx(current, objcur.colsbas, relsbas[objcur.colsbas][cc2], objcur.colname );
			if( (0+current.getAttribute('state')) != 0)
			{
				oldstate = current.getAttribute('state');
				current.setAttribute('state', 0);
				current.src="/skins/icons/ccoch" + current.getAttribute('state') + ".gif";

/** modif **/   modif[objcur.getAttribute('colname') +"_"+objcur.getAttribute('colsbas')+"_"+ relsbas[objcur.getAttribute('colsbas')][cc2]] = 0;

				
				if(oldstate==1)
				{
					totaccpersbas[objcur.getAttribute('colsbas')]--;
					if(totaccpersbas[objcur.getAttribute('colsbas')]==0)
					{
						if(objtmp = returnElement("basmodifth_"+objcur.getAttribute('colsbas')))
							objtmp.style.visibility = "hidden";
						if(objtmp = returnElement("basmanage_"+objcur.getAttribute('colsbas')))
							objtmp.style.visibility = "hidden";
						if(objtmp = returnElement("basmodifstruct_"+objcur.getAttribute('colsbas')))
							objtmp.style.visibility = "hidden";
						if(objtmp = returnElement("baschupub_"+objcur.getAttribute('colsbas')))
							objtmp.style.visibility = "hidden";
					}
					
					list = new Array("actif","album", "canprev","water","canhd","dlprev","dlhd","cmd","quota","limit","mask",
							"addrec","modifrec","chgstat","delrec","imgtools","admin","report","push","manage","modifstruct" );
					for(cc in list)
					{
						if(objtmp = returnElement(list[cc]+"_"+objcur.getAttribute('colsbas')+"_"+relsbas[objcur.getAttribute('colsbas')][cc2] ))
						{
							objtmp.style.visibility = "hidden";
						}
					}
				}
				
			}
		}
	}
	if (o=returnElement("iddivloading") )
		o.style.visibility = "hidden";
}


function checkAll2()
{
	// coche une colone (autre que celle d'acces)
	closeMenu();
	for(cc2 in relsbas[objcur.getAttribute('colsbas')])
	{
		if( current = returnElement(""+ objcur.getAttribute('colname') +"_"+objcur.getAttribute('colsbas')+"_"+ relsbas[objcur.getAttribute('colsbas')][cc2]))
		{
			// clk_chbx(current, objcur.colsbas, relsbas[objcur.colsbas][cc2], objcur.colname );
			if(current.style.visibility=="visible" && (current.getAttribute('state')==0 || current.getAttribute('state')==2) )
			{
				oldstate = current.getAttribute('state');
				current.setAttribute('state', 1);
				current.src="/skins/icons/ccoch" + current.getAttribute('state') + ".gif";
/** modif **/   modif[objcur.getAttribute('colname') +"_"+objcur.getAttribute('colsbas')+"_"+ relsbas[objcur.getAttribute('colsbas')][cc2]] = 1;
				
			}
		}
	}
	
}

function uncheckAll2()
{
	// decoche une colone (autre que celle d'acces)
	closeMenu();
	for(cc2 in relsbas[objcur.getAttribute('colsbas')])
	{
		if( current = returnElement(""+ objcur.getAttribute('colname') +"_"+objcur.getAttribute('colsbas')+"_"+ relsbas[objcur.getAttribute('colsbas')][cc2]))
		{
			// clk_chbx(current, objcur.colsbas, relsbas[objcur.colsbas][cc2], objcur.colname );
			if(current.style.visibility=="visible" && (current.getAttribute('state')==1 || current.getAttribute('state')==2) )
			{
				oldstate = current.getAttribute('state');
				current.setAttribute('state', 0);
				current.src="/skins/icons/ccoch" + current.getAttribute('state') + ".gif";
/** modif **/   modif[objcur.getAttribute('colname') +"_"+objcur.getAttribute('colsbas')+"_"+ relsbas[objcur.getAttribute('colsbas')][cc2]] = 0;
				
			}
		}
	}
	
}


function mycancel()
{
	document.forms[0].action = "./users.php";
	document.forms[0].act.value = "UPD";
	document.forms[0].p3.value = "";
	document.forms[0].submit();
}
function valid()
{
	
	document.getElementById('iddivloading').style.visibility = 'visible';
	
	document.getElementById('fakefocus').style.visibility="visible";
	document.getElementById('fakefocus').focus();
	ret ="";
	for(cc in modif)
		ret += ('<' + cc + '>' +  modif[cc] + '<\/'+ cc + '>\n');
	// alert (ret);return;
	document.forms[0].action = "./users.php";
	document.forms[0].act.value = "UPD";
	document.forms[0].p3.value = ret;
	if(document.getElementById("usr_password") && document.getElementById("usr_password_first") && returnElement("ismodel") )
	{
		pwd = document.getElementById("usr_password").value ;	
		pwdC = document.getElementById("usr_password_first").value ;		
		if((!(pwd.length >0 ) || !(pwdC.length >0 ) || (pwd != pwdC)) && !(returnElement("ismodel").checked)  )
		{
			alert("password error !");
			return;
		}
	}
	modif = null ;
	modif = new Array();	
	document.forms[0].submit();
}
function quotabase(restrict,remain, max)
{
     this.restrict_dwnld  = restrict;
     this.remain_dwnld 	  = remain;
     this.month_dwnld_max = max;
}

function chkmodel(obj)
{ 
	if(obj.checked)	
	{
		if(confirm("<?php echo _('admin::user: attention, un modele n\'est plus un utilisateur et ne sera plus moifiable que par vous meme, continuer ?')?>"))
		{
			modif["model_of"] = "<?php echo $usr_id?>" ;			
			returnElement("linkIDT").style.display = "none";
			
			if( oo =returnElement( "divIdt") )
			{
				if(oo.style.visibility!="hidden" )
				{
					view('RIGHTS')	;
				}					
			}  
			
		}
		else
		{
		//	modif["model_of"] = "0" ;
			obj.checked = false;
		}
	}
	else
	{
		modif["model_of"] = "0" ;
		returnElement("linkIDT").style.display = "";
	}
}


function setcoord(obj)
{
	var zname = obj.getAttribute('name');
	switch(obj.name)
	{
		case "canchgprofil":
		case "canchgftpprofil":
		case "passifFTP":
		case "activeFTP":
			if(obj.checked)
				modif[zname] = 1 ;
			else
				modif[zname] = 0 ;
			break;
		
		case "ccsentftphd":
		case "ccsentftpprev":
		case "ccsentftpcaption":
			tmpnewval = 0;
			if( document.getElementById("ccsentftphd") )
				if( document.getElementById("ccsentftphd").checked )
					tmpnewval += 4 ;
			if( document.getElementById("ccsentftpprev") )
				if( document.getElementById("ccsentftpprev").checked )
					tmpnewval += 2 ;
			if( document.getElementById("ccsentftpcaption") )
				if( document.getElementById("ccsentftpcaption").checked )
					tmpnewval += 1 ;
			modif["defaultftpdatasent"] = tmpnewval ;	
			break;
		
		case "usr_sexe":
			modif["usr_sexe"] = escape(obj[obj.selectedIndex].value) ;
			break;
		
		default :
			modif[zname] = escape(obj.value) ;
			break;
	}
}

document.onmousedown = viewmenu;
document.oncontextmenu = viewmenu;

</script>
</head>
	<body id="idBody"  onResize="redrawme();">

<?php

######################################## 
######## reprise ancien admin
######################################## 

$ccvalueNoMaxRight = 3; // cc invisible
$ccvalueNoMaxRight = 4; // cc rouge pour debug
$ccvalueNoMaxRight = 5; // point rouge pour debug


$adminrights = NULL; 

 
#########
$bas2name = NULL ;			// relation bas vers sbas pour la suite
$speedAccesParm = NULL;		// selation sbas vers properties de connexion
$allstatusname=null;
$allmask = array();
foreach($ph_session["bases"] as $onebase)
{			
	$speedAccesParm[$onebase["sbas_id"]] = $onebase;
	foreach($onebase["collections"] as $oneColl)
	{
		$allmask[$onebase["sbas_id"]][$oneColl["base_id"]]["AND"] =null;
		$allmask[$onebase["sbas_id"]][$oneColl["base_id"]]["XOR"] = null;
	}
	
 	$allstatusname[$onebase["sbas_id"]][0]["off"] = _('admin::user:mask : non-indexes')  ;
 	$allstatusname[$onebase["sbas_id"]][0]["on"] = _('admin::user:mask : indexes') ;
 	
	// on recup les noms
	if( ($sxe = simplexml_load_string($onebase["xmlstruct"])) )
	{
		if($sxe->statbits->bit)
		{
			foreach($sxe->statbits->bit as $sb)
			{
				$bit = (int)($sb["n"]);
				if($bit>=0 && $bit<=63)
				{					
					if(isset( $sb["labelOff"] ) && trim($sb["labelOff"])!="")
						$allstatusname[$onebase["sbas_id"]][$bit]["off"]  = (string)($sb["labelOff"]);
					else
						$allstatusname[$onebase["sbas_id"]][$bit]["off"]  = "non-".(string)($sb);
					
					if(isset( $sb["labelOn"] ) && trim($sb["labelOn"])!="" )
						$allstatusname[$onebase["sbas_id"]][$bit]["on"]  = (string)($sb["labelOn"]);
					else
						$allstatusname[$onebase["sbas_id"]][$bit]["on"] = (string)($sb);
						
				}					
			}
		}
	}
}
$lb = phrasea::bases() ;
foreach($lb["bases"] as $onebase)
{
	if( !isset($speedAccesParm[$onebase["sbas_id"]]) )
		$speedAccesParm[$onebase["sbas_id"]] = $onebase;
}
##################### 

	 
	
$conn = connection::getInstance();

	$sql = "SELECT
usr.seepwd,

basusr.base_id,
sbasusr.sbas_id,

basusr.actif as actif,
basusr.canputinalbum as album,
basusr.canpreview as canprev,
basusr.candwnldpreview as dlprev,
basusr.canhd,
basusr.candwnldhd as dlhd,
basusr.cancmd as cmd,
basusr.needwatermark as water,
basusr.canaddrecord as addrec,
basusr.canmodifrecord as modifrec,
basusr.chgstatus as chgstat,
basusr.candeleterecord as delrec,
basusr.imgtools,
basusr.canadmin as admin,
basusr.canreport as report,
basusr.canpush as push,
basusr.manage,
basusr.modify_struct as modifstruct,

sbasusr.bas_chupub as baschupub,
sbasusr.bas_modif_th as basmodifth,
sbasusr.bas_manage as basmanage,
sbasusr.bas_modify_struct as basmodifstruct

FROM (((usr INNER JOIN basusr ON (usr.usr_id='".$conn->escape_string($usr_id)."' AND usr.usr_id=basusr.usr_id AND basusr.canadmin=1 AND basusr.actif=1) )
INNER JOIN bas ON basusr.base_id=bas.base_id )
INNER JOIN sbasusr ON bas.sbas_id=sbasusr.sbas_id AND sbasusr.usr_id=basusr.usr_id)
INNER JOIN sbas ON sbas.sbas_id=sbasusr.sbas_id
ORDER BY sbas.ord,sbas.sbas_id, bas.ord,bas.base_id";

	if( ($rs = $conn->query($sql)) )
	{	
		while(($row = $conn->fetch_assoc($rs)) )
		{
			if(!$seepwd && $row["seepwd"]=="1")
				$seepwd = TRUE;
				
			$myRightsMax[$row["sbas_id"]][$row["base_id"]] = $row ;			
			$myRightsMax[$row["sbas_id"]]["basmodifth"] = $row["basmodifth"];			
			$myRightsMax[$row["sbas_id"]]["basmanage"] = $row["basmanage"];			
			$myRightsMax[$row["sbas_id"]]["basmodifstruct"] = $row["basmodifstruct"] ;			
			$myRightsMax[$row["sbas_id"]]["baschupub"] = $row["baschupub"] ;			
		}
	}
	$lb = phrasea::bases();
	foreach($lb["bases"] as $onebase)	 
		foreach($onebase["collections"] as $oneColl)
			$bas2name[$oneColl["base_id"]]=$oneColl["name"];
	
	$sql = "SELECT sbas.*,bas.base_id
			FROM
			(
			  (
			    (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id AND usr.usr_id='".$conn->escape_string($usr_id)."' AND basusr.canadmin=1 AND basusr.actif='1')
			       INNER JOIN bas ON basusr.base_id=bas.base_id
			  )
			  INNER JOIN sbas ON bas.sbas_id=sbas.sbas_id
			)
			ORDER BY sbas.ord,sbas_id,bas.ord,bas.base_id";
			
	$list = array();
	if( ($rs = $conn->query($sql)) )
	{	
		 
		while(($row = $conn->fetch_assoc($rs)) )
		{
			if( isset($bas2name[$row["base_id"]]) )
			{
				$list[] =  $row["base_id"];
				
				$sbascoll_order[$row["sbas_id"]][] = $row["base_id"];
				
			}
			
		}
		$conn->free_result($rs);
	}
	
	if($parm["act"]=="NEW")
	{
		// on verif si le user existe
		$sql = "SELECT usr_id,model_of,issuperu from usr WHERE usr.usr_login='".$conn->escape_string($parm["p2"])."'";
		$newid = null;
		if( ($rs = $conn->query($sql)) )
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$newid = $row["usr_id"];
				if($row["issuperu"]=="1")
				{
					?>
					<script type="text/javascript">
					alert("<?php echo _('forms::un utilisateur utilisant ce login existe deja')?>");
					</script>
					<?php
					exit();
				}
				if($row["model_of"]==null || $row["model_of"]=="" || $row["model_of"]=="0")
				{ 
					# cas normal 
				}
				else
				{
					// c'est un model
					// on verif si il est a moi
					// sinon on jette l'admin
					if($row["model_of"]==$usr_id)
					{ 
						# c'est Ok c'est bien un de mes models
					} 
					else
					{
						?>
						<script type="text/javascript">
						alert("<?php echo _('forms::un utilisateur utilisant ce login existe deja')?>");
						</script>
						<?php
						exit();
					}
				}
			}			
			$conn->free_result($rs);
		}
		// sinon on le cree
		if($newid == null)
		{
			$firstcreation = true;
			$newid = $conn->getId("USR");
			$login = $parm["p2"];
			$sql = "insert into usr (usr_id, usr_login,model_of,usr_creationdate) values ('" . $conn->escape_string($newid) . "', '" . $conn->escape_string($login) . "','0',now())"; 		
			$conn->query($sql);		
			
 			$sql = "UPDATE usr SET canchgprofil='1' ,canchgftpprofil='1' WHERE usr_id='" . $conn->escape_string($newid)."'" ; 		
			$conn->query($sql);			
		}		
		// puis on met son ID dans p2
		$parm["p2"] = $newid ;	
	}	
			
?>	
	<form method="post" action="./users.php" target="_self" style="visibility:hidden; display:none" >
		<input type="hidden" name="ord" value="<?php echo $parm["ord"]?>" />
		<input type="hidden" name="srt" value="<?php echo $parm["srt"]?>" />
		<input type="hidden" name="act" value="<?php echo $parm["act"]?>" />
		<input type="hidden" name="p0" value="<?php echo $parm["p0"]?>" />
		<input type="hidden" name="p1" value="<?php echo $parm["p1"]?>" />
		<input type="hidden" name="p2" value="<?php echo $parm["p2"]?>" />
		<input type="hidden" name="p3" value="<?php echo $parm["p3"]?>" />
		<input type="hidden" name="p4" value="<?php echo $parm["p4"]?>" />
		<input type="hidden" name="p5" value="<?php echo $parm["p5"]?>" />
		<input type="hidden" name="p6" value="<?php echo $parm["p6"]?>" />
	</form>
	
<?php	
	### Calcul du nombre de users
	$nbusr = 0;	
	$sql = "SELECT usr_id,model_of from usr WHERE usr.usr_id in (".$conn->escape_string($parm["p2"]).")";
	if($rs = $conn->query($sql))
		while($row = $conn->fetch_assoc($rs))
		{
			$nbusr++;
			if( $row["model_of"]>0)
				$nbmodel++;
		}
			
	### recup fiche idt user			
	if($nbusr==1)
	{
		$coord = null; 	// l'identite des usr select
		$sql = "SELECT usr.*,bin(defaultftpdatasent) as bindefaultftpdatasent from usr WHERE usr.usr_id='".$conn->escape_string($parm["p2"])."'";
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				
				while( strlen($row["bindefaultftpdatasent"])<3) 
					$row["bindefaultftpdatasent"] = "0".$row["bindefaultftpdatasent"];
			
				$coord = $row;
			}
			$conn->free_result($rs);
		}
	}
	else
	{
		
	}
	
	$sql= "SELECT
			sbasusr.sbas_id,
			bas.base_id,
			sum(1) as acces,
			sum(actif) as actif,
			sum(canputinalbum) as album,
			sum(canpreview) as canprev,		
			sum(candwnldpreview) as dlprev,
			sum(canHD) as canhd,	
			sum(candwnldhd) as dlhd,
			sum(cancmd) as cmd,
			sum(needwatermark) as water,
			
			sum(canaddrecord) as addrec,
			sum(canmodifrecord) as modifrec,
			sum(chgstatus) as chgstat,
			sum(candeleterecord) as delrec,		
			sum(imgtools) as imgtools,
			
			sum(canadmin) as admin,	
			sum(canreport) as report,
			sum(canpush) as push,
			sum(manage) as manage,
			sum(modify_struct) as modifstruct,
			
			sum(sbasusr.bas_modif_th) as basmodifth,
			sum(sbasusr.bas_manage) as basmanage,
			sum(sbasusr.bas_modify_struct) as basmodifstruct,
			sum(sbasusr.bas_chupub) as baschupub,
			
			sum(time_limited) as time_limited,
			DATE_FORMAT(limited_from,'%Y%m%d') as limited_from,
			DATE_FORMAT(limited_to,'%Y%m%d') as limited_to,
			
			sum(restrict_dwnld) as restrict_dwnld,
			sum(remain_dwnld) as remain_dwnld,
			sum(month_dwnld_max) as month_dwnld_max,
			
			mask_xor as maskxordec,
			bin(mask_xor) as maskxorbin,
			mask_and as maskanddec,
			bin(mask_and) as maskandbin

			FROM (usr INNER JOIN basusr ON (usr.usr_id=basusr.usr_id AND usr.usr_id in (".$conn->escape_string($parm["p2"]).") and (basusr.base_id='".implode("' OR basusr.base_id='",$list)."') ) )
			INNER JOIN  bas on  basusr.base_id=bas.base_id
			inner join  sbasusr on (sbasusr.sbas_id=bas.sbas_id AND usr.usr_id=sbasusr.usr_id)
			GROUP BY bas.base_id";
	$droitusr = null ; 	// les droits des usr	
	$droitusrsbas = null ; 	// les droits des usr par sbas	
	if($rs = $conn->query($sql))
	{
		while(($row = $conn->fetch_assoc($rs)) )
		{
			if($nbusr>1)
			{
				foreach($row as $name=>$value)
				{
					
					if(	$name!="maskxordec" 
						&& $name!="maskxorbin" 
						&& $name!="maskanddec" 
						&& $name!="maskandbin" 						
						&& $name!="limited_to" 
						&& $name!="limited_from" 
						&& $name!="remain_dwnld" 
						&& $name!="month_dwnld_max"						
						&& $name!="sbas_id" 
						&& $name!="base_id" )
					{
						if($value!=$nbusr && $value!=0)
							$row[$name]="2";
						else if($value==$nbusr)
							$row[$name]="1";
					}
				}
			}	
			else 
			{
				$allmask[$row["sbas_id"]][$row["base_id"]]["AND"][] = $row["maskandbin"];
				$allmask[$row["sbas_id"]][$row["base_id"]]["XOR"][] = $row["maskxorbin"];
			}
			
			$droitusr[ $row["sbas_id"] ] [ $row["base_id"] ] = $row;
		
			if( !isset($droitusrsbas[ $row["sbas_id"]]["visible"] ) )
				$droitusrsbas[ $row["sbas_id"] ]["visible"]=0;
		
			$droitusrsbas[ $row["sbas_id"] ] ["visible"] += (($row["acces"]*1)==1?1:0);
			$droitusrsbas[ $row["sbas_id"] ] ["basmodifth"] = $row["basmodifth"];
			$droitusrsbas[ $row["sbas_id"] ] ["basmanage"] = $row["basmanage"];
			$droitusrsbas[ $row["sbas_id"] ] ["basmodifstruct"] = $row["basmodifstruct"];
			$droitusrsbas[ $row["sbas_id"] ] ["baschupub"] = $row["baschupub"];
			
			
			$jsQuota[ $row["sbas_id"] ] [ $row["base_id"] ]= "new quotabase(".$row["restrict_dwnld"].",".$row["remain_dwnld"].",".$row["month_dwnld_max"].");";
			
			$from = $row["limited_from"];
			$to   = $row["limited_to"];
			if($row["time_limited"]!="1")
			{
				$from = $to = date("Ymd");
			}
			$jsTime[ $row["sbas_id"] ] [ $row["base_id"] ]= "new timelimit(".$row["time_limited"].",".$from.",".$to.");";
			 
		}
	}
	
	
	// MES MODELS
	$sql = "SELECT usr.usr_login,usr.usr_id FROM usr WHERE model_of='".$conn->escape_string($usr_id)."' and usr_login not like '(#deleted_%'";
	if($rs = $conn->query($sql))
		while(($row = $conn->fetch_assoc($rs)) )
			$myModels[] = $row;			
	
	
	if($nbusr>1)
	{
		$sql = "SELECT BIN(mask_and) AS mask_and, BIN(mask_xor) AS mask_xor FROM basusr WHERE usr_id IN  (".$parm["p2"].") and (basusr.base_id='".implode("' OR basusr.base_id='",$list)."')" ;
		$sql = "SELECT
					bas.sbas_id,
					bas.base_id,					
					BIN(mask_and) AS mask_and, 
					BIN(mask_xor) AS mask_xor 
					FROM					
						basusr INNER JOIN  bas on  basusr.base_id=bas.base_id
					WHERE usr_id IN  (".$conn->escape_string($parm["p2"]).") and (basusr.base_id='".implode("' OR basusr.base_id='",$list)."')" ;
		
	
		if($rs = $conn->query($sql))
		{
			while(($row = $conn->fetch_assoc($rs)) )
			{
				$allmask[$row["sbas_id"]][$row["base_id"]]["AND"][] = $row["mask_and"];
				$allmask[$row["sbas_id"]][$row["base_id"]]["XOR"][] = $row["mask_xor"];
	
				
			}
		}
		
		##** verification que le user n'est pas admin d'une autre base 
		$sql="SELECT sum(canadmin) as adminother  FROM basusr WHERE usr_id='".$conn->escape_string($parm["p2"])."' AND base_id!='".implode("' AND base_id!='",$list)."' GROUP BY usr_id";
		if($rs = $conn->query($sql))
		{
			if( $row = $conn->fetch_assoc($rs) ) 
			{
				if( ($row["adminother"]+0)>0 )
					$adminOfOthersBases = true ;
			}
		}
	
	}
	
	foreach( $allmask as $onesbas=>$arraycoll)
	{
		foreach($arraycoll as $oneColl=>$someMask)
		{
			 
			$tbits_and = array();
			$tbits_xor = array();
			$nrows = 0;
			for($bit=0; $bit<64; $bit++)
				$tbits_and[$bit] = $tbits_xor[$bit] = array( "nset"=>0 );
					
			for( $i=0; $i<count($someMask["XOR"]); $i++ )
			{
				$sta_xor = strrev( $someMask["XOR"][$i]  );
				for($bit=0; $bit<strlen($sta_xor); $bit++)
					$tbits_xor[$bit]["nset"] += substr($sta_xor, $bit, 1)!="0" ? 1 : 0;		
			 	
				$sta_and = strrev( $someMask["AND"][$i] );
				for($bit=0; $bit<strlen($sta_and); $bit++)
					$tbits_and[$bit]["nset"] += substr($sta_and, $bit, 1)!="0" ? 1 : 0;

			}
			$affand = "" ;
			$affxor = "";
			for($bit=0; $bit<64; $bit++)
			{
				$affand .= $tbits_and[$bit] ["nset"];
				$affxor .= $tbits_xor[$bit] ["nset"];	
			}
			
			$vand_and = $vand_or = $vxor_and = $vxor_or = "";
			for($bit=0; $bit<64; $bit++)
			{
				if(($tbits_and[$bit]["nset"]!=0 && $tbits_and[$bit]["nset"]!=$nbusr) || ($tbits_xor[$bit]["nset"]!=0 && $tbits_xor[$bit]["nset"]!=$nbusr))
				{
					$vand_and = "1" . $vand_and;
					$vand_or  = "0" . $vand_or;
					$vxor_and = "1" . $vxor_and;
					$vxor_or  = "0" . $vxor_or;
				}
				else
				{
					$vand_and = ($tbits_and[$bit]["nset"]==0?"0":"1") . $vand_and;
					$vand_or  = ($tbits_and[$bit]["nset"]==$nbusr?"1":"0") . $vand_or;
					$vxor_and = ($tbits_xor[$bit]["nset"]==0?"0":"1") . $vxor_and;
					$vxor_or  = ($tbits_xor[$bit]["nset"]==$nbusr?"1":"0") . $vxor_or;
				}
			}	
			$allmask[$onesbas][$oneColl]["vand_and"] = $vand_and;
			$allmask[$onesbas][$oneColl]["vand_or"]  = $vand_or;
			$allmask[$onesbas][$oneColl]["vxor_and"] = $vxor_and;
			$allmask[$onesbas][$oneColl]["vxor_or"]  = $vxor_or;				 
		}
	}
	

?>

	<div id="iddivloading" style="background-image:url(./trans.gif);BACKGROUND-POSITION: top bottom; BACKGROUND-REPEAT: repeat; border:#ff0000 3px solid;position:absolute; width:98%;height:98%; top:0px; left:0px;z-index:99;text-align:center">
		<table style='width:100%;height:100%; text-align:center;valign:middle:; color:#FF0000; font-size:16px'>
			<tr>
				<td>
					<br><div style='background-color:#FFFFFF'><b><?php echo _('phraseanet::chargement')?>...</b></div><br/>
				</td>
			</tr>
		</table>
	</div>
	
		<span id="spanref" style="position:absolute; bottom:0px; left:5px;  background-color:#0f00cc; visibility:hidden">  
			<img src="./pixel.gif" name="test_longueur" width="1" height="100%" align="left">
		</span> 
	 
		<div id="divref" >&nbsp;</div>
	
		<table id="presentUser" style="table-layout:fixed; width:100%;" border="0" cellpadding="0" cellspacing="0">
		
			<tr style="height:30px; " >
				<td style="height:30px; width:20px;  font-size:12px; border:0px; BACKGROUND-POSITION: left top;BACKGROUND-REPEAT: no-repeat">
					<input type="text" id="fakefocus" value="" style="width:1px;height:1px;visibility:hidden" />
				</td>
				
				<td style="height:30px;font-size:12px; border:0px;BACKGROUND-POSITION:0px 0px;BACKGROUND-REPEAT: repeat-x" nowrap>
					<table style="width:100%;">
						<tr>
							<td style="text-align:left" nowrap>
								&nbsp; 
							  <b><a href="javascript:void();return(false);" onclick="view('RIGHTS');return(false);" style="color:#000000; text-decoration:none"><?php echo _('admin::user: droits de l\'utilisateur')?></a></b>
								<?php
									if($nbusr==1  && !in_array($coord["usr_login"],array('admin','autoregister','invite')))
									{
	
								?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
													 <b><a href="javascript:void();return(false);" id="linkIDT" onclick="view('IDT');return(false);" style="color:#000000;<?php echo ($nbmodel==0)?"":"display:none"?>"><?php echo _('admin::user: informations utilisateur')?></a></b>
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								<?php
									}
								
								?>
								</td>
								<td  style="text-align:right" nowrap>
								<?php
									if($myModels!=null)
									{
								?>
								<select style="WIDTH: 200px;FONT-SIZE: 9px" onChange="applyModel(this);" id="selectidmodel" >
									<option value='000' selected  style="" >&nbsp;<?php echo _('admin::user: appliquer le modele')?></option> 
								<?php
											foreach($myModels as $unmodel)
											{
								?>
												<option value='<?php echo $unmodel["usr_id"]?>' caption='<?php echo $unmodel["usr_login"]?>'>&nbsp;<?php echo $unmodel["usr_login"]?></option>
								<?php
											}	
								?>
											</select>
								<?php
									}
	
								if($nbusr==1)
								{			
									// Verification que l'utilisateur m'appartient totalement
									$sql="SELECT base_id FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) WHERE usr.usr_id=".$parm["p2"]." and base_id!='".implode("' AND base_id!='",$list)."' group by base_id";
									
									// on ne prend pas en compte les coll. desactivees
									$sql="SELECT basusr.base_id FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) inner join bas on basusr.base_id=bas.base_id  WHERE usr.usr_id=".$parm["p2"]." and basusr.base_id!='".implode("' AND basusr.base_id!='",$list)."' and bas.active=1 group by basusr.base_id";
									if($rs = $conn->query($sql))
									{
										$nbrep = $conn->num_rows($rs);
										$conn->free_result($rs);
										if($nbrep>0)
										{
											// c'est pas bon, il a acces a d'autres coll que les miennes
										}
										elseif(!in_array($coord["usr_login"],array('admin','autoregister','invite')))
										{
											$chck="";
											if($coord["model_of"]==$usr_id)
												$chck="checked";	
								?>	
											<input type="checkbox" <?php echo $chck?> name="ismodel" id="ismodel" onClick="chkmodel(this);" ><?php echo _('admin::user: transformer en modele utilisateur')?>	
								<?php
										}		
									}
								}
							?>
							</td>
						</tr>
					</table>
				</td>
				
				<td style="height:30px;width:20px;font-size:12px;border:0px;BACKGROUND-POSITION: top right; BACKGROUND-REPEAT: no-repeat">
				</td>
			 
			</tr>
			
			<tr style="height:30px; " >
				<td colspan="3" style=" border:#aaaaaa 3px solid;padding:3px;background-color:#aaaaaa;font-size:10px;">
				
					<table style="width:100%; color:#ffffff;font-size:12px;">
						<tr>
							<td style="width:10px"></td>
							<td style="text-align:left" nowrap>
								<?php echo ($nbusr==1?(_('admin::compte-utilisateur identifiant')." : ".$coord["usr_login"]):(_('phraseanet::utilisateurs selectionnes').$nbusr).($nbmodel==0?"":"<font color=\"#ff0000\"><small>("._('admin:user: nombre de modeles : ').$nbmodel.")</small></font>"))?></td>
							<td style="text-align:right" nowrap>
								<?php echo (( $nbusr==1 && !in_array($coord["usr_login"],array('admin','autoregister','invite')) && ((!$adminOfOthersBases  ) ) )?( _('admin::compte-utilisateur mot de passe')." : <input class=\"iptIdt\" style=\"width:110px\" type=\"password\"  value=\"password\" id=\"usr_password_first\" name=\"usr_password_first\" onchange=\"document.getElementById('usr_password').value='';\" >"):"")?></td>
							<td style="text-align:right" nowrap>
								<?php echo (( $nbusr==1 && !in_array($coord["usr_login"],array('admin','autoregister','invite')) && ((!$adminOfOthersBases  ) ) )?( _('admin::compte-utilisateur confirmer le mot de passe')." : <input class=\"iptIdt\" style=\"width:110px\" type=\"password\"  value=\"password\" id=\"usr_password\" name=\"usr_password\" onchange=\"javascript:setcoord(this);\" >"):"")?></td>
							<td style="width:10px"></td>
						</tr>
						
						<tr style="height:18px;font-size:10px;" >
							<td style="width:10px"></td>
							<td style="height:18px;font-size:10px;text-align:left">
								<?php
								if( $nbusr==1 && $seepwd && !$adminOfOthersBases && !in_array($coord["usr_login"],array('admin','autoregister','invite')))
								{ 
								?>
									<img src="/skins/icons/ccoch<?php echo $coord["seepwd"]?>.gif" chk="<?php echo $coord["seepwd"]?>" name="idseepwd" id="idseepwd" onClick="chkseepwd(this);return(false);" ><?php echo _('admin::user: l\'utilisateur peut changer les mots de passe')?>
								<?php
								}
								?>
							</td>
							<td style="text-align:right" nowrap>
							</td>
							<td style="width:10px"></td>
						</tr>
					</table>			
				</td>			 
			</tr>


			<tr style="background-color:#aaaaaa; border:#cccccc 1px solid;"  >
				<td colspan="3" style=" border:#aaaaaa 1px solid;padding-left:3p;text-align:center">
				<center>
					<div id="divSpecial" style="background-color:#FFFFFF; visibility:hidden; display:none;  width:100%; overflow:auto;text-align:center" >
						
						<span id="idspantitle" style="font-size:20px"><u><?php echo _('admin::user: acces aux quotas')?></u></span>
						<br>
						<span id="idspanbase" style="font-size:16px"><?php echo _('phraseanet:: base')?></span>
						<br>
						<span id="idspancoll" style="font-size:12px"><?php echo _('phraseanet:: collection')?></span>
						<br><br><br>
						<span id="idspanline" style="font-size:12px">line</span>	
						
						<div id="spacetabmiddle" style="overflow:auto;font-size:12px">a<br>b<br>c<br>d<br>e<br>f<br>g<br>h<br>i<br>j<br>k<br>l<br>m<br></div>

						<div id="idspanapply" style="overflow:auto;font-size:12px"><?php echo _('boutton::valider')?> &nbsp;&nbsp;&nbsp;&nbsp; <?php echo _('boutton::annuler')?></div>

					</div>	
				
					<div id="divIdt" style="background-color:#ffffff; visibility:hidden; display:none;  width:100%; overflow:auto;text-align:center" >
					<?php
						if($nbusr==1)
						{
						
					?>						
					<br>
						<center>
						<TABLE style="table-layout:fixed;font-size:11px; color:#777777; ">
							<tr>
								<td style="width:140px;height:1px;" nowrap ></td>
								<td style="width:200px;height:1px;" nowrap ></td>
							</tr>
							<?php
									if(isset($coord["canchgprofil"]))
									{
							?>
							<tr>
								<td colspan="2" style="text-align:center;padding-bottom:3px" nowrap ><input  style=""  type="checkbox"   <?php echo ($coord["canchgprofil"]=="1"?"checked":"")?>   name="canchgprofil"  onchange="setcoord(this);">&nbsp;<?php echo _('admin::user:l\'utilisateur peut modifier son profil')?></td>
							</tr>
							<?php
									}
							?>			
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur sexe')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<select class="iptIdt" name="usr_sexe" id="idusr_sexe" onchange="setcoord(this);" >
	   							 <option <?php echo ($coord["usr_sexe"]=="0"?"selected":"")?> value="0" ><?php echo _('admin::compte-utilisateur:sexe: mademoiselle')?></option>
	   							 <option <?php echo ($coord["usr_sexe"]=="1"?"selected":"")?> value="1" ><?php echo _('admin::compte-utilisateur:sexe: madame')?></option>
	   							 <option <?php echo ($coord["usr_sexe"]=="2"?"selected":"")?> value="2" ><?php echo _('admin::compte-utilisateur:sexe: monsieur')?></option>
	   						</select>	 
								</td>
							</tr>
						 	
							<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur nom')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input class="iptIdt" type="text" name="usr_nom" value="<?php echo htmlentities($coord["usr_nom"],ENT_QUOTES,'UTF-8')?>" onchange="setcoord(this);">
								</td>
							</tr>
						 	
							
							<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur prenom')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt"  type="text" name="usr_prenom" value="<?php echo htmlentities($coord["usr_prenom"],ENT_QUOTES,'UTF-8')?>" onchange="setcoord(this);">
								</td>
							</tr>
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur email')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="text" name="usr_mail"  value="<?php echo htmlentities($coord["usr_mail"],ENT_QUOTES,'UTF-8')?>" onchange="setcoord(this);">
								</td>
							</tr>
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur poste')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="text" name="fonction"  value="<?php echo htmlentities($coord["fonction"],ENT_QUOTES,'UTF-8')?>" onchange="setcoord(this);">
								</td>
							</tr>
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur societe')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="text" name="societe"  value="<?php echo htmlentities($coord["societe"],ENT_QUOTES,'UTF-8')?>" onchange="setcoord(this);">
								</td>
							</tr>
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur activite')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="text" name="activite"  value="<?php echo htmlentities($coord["activite"],ENT_QUOTES,'UTF-8')?>" onchange="setcoord(this);">
								</td>
							</tr>
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur telephone')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="text" name="tel"  value="<?php echo htmlentities($coord["tel"],ENT_QUOTES,'UTF-8')?>" onchange="setcoord(this);">
								</td>
							</tr>
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur fax')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="text" name="fax"  value="<?php echo htmlentities($coord["fax"],ENT_QUOTES,'UTF-8')?>" onchange="setcoord(this);">
								</td>
							</tr>
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur adresse')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="text" name="adresse"  value="<?php echo htmlentities($coord["adresse"],ENT_QUOTES,'UTF-8')?>" onchange="setcoord(this);">
								</td>
							</tr>
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur code postal')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="text" name="cpostal"  value="<?php echo htmlentities($coord["cpostal"],ENT_QUOTES,'UTF-8')?>" onchange="setcoord(this);">
								</td>
							</tr>
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur ville')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="text" name="ville"  value="<?php echo htmlentities($coord["ville"],ENT_QUOTES,'UTF-8')?>" onchange="setcoord(this);">
								</td>
							</tr>
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur pays')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
								<?php
								echo "<select class=\"iptIdt\" name=\"pays\" onchange=\"javascript:setcoord(this);\">";
								
								echo "<option class=\"pays_switch\" value=\"\">Undefined</option>";
								
								foreach($countries as $k=>$c)
								{
									$selected='';
									if(trim($coord["pays"]) == $k)
										$selected = 'selected="selected"';
									echo "<option class=\"pays_switch\" ".$selected." value=\"".$k."\">".$c."</option>";
									
								}

								echo "</select>";
								?>
								
								</td>
							</tr>
						 	<tr>
								<td colspan="2" style="text-align:left" nowrap><br><hr /><br></td>
							</tr>
							
							
						 	<tr>
								<td colspan="2" style="width:340px;text-align:center;padding-bottom:10px" nowrap>
									<input  style=""  type="checkbox"  <?php echo ($coord["activeFTP"]=="1"?"checked":"")?> name="activeFTP"  onchange="setcoord(this);">&nbsp;<?php echo _('admin::compte-utilisateur:ftp: Activer le compte FTP') ?>
								</td>
							</tr>
							
							<?php
								if(isset($coord["canchgftpprofil"]))
								{
							?>	
							<tr>
								<td colspan="2" style="text-align:center;padding-bottom:3px" nowrap ><input type="checkbox" <?php echo ($coord["canchgftpprofil"]=="1"?"checked":"")?> name="canchgftpprofil" onchange="setcoord(this);">&nbsp;<?php echo _('admin::user: l\'utilisateur peut modifier son profil ftp')?></td>
							</tr>
							<?php
								}
							?>				
							<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('phraseanet:: adresse') ?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									&nbsp;ftp://<input  class="iptIdt" style="width:157px" type="text" name="addrFTP" value="<?php echo $coord["addrFTP"]?>" onchange="setcoord(this);">
								</td>
							</tr>
							
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur identifiant')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="text" name="loginFTP"  value="<?php echo $coord["loginFTP"]?>"  onchange="setcoord(this);">
								</td>
							</tr>
							
						 	<tr>
								<td style="width:140px;text-align:left;" nowrap><?php echo _('admin::compte-utilisateur mot de passe')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="password" name="pwdFTP"   value="<?php echo htmlentities($coord["pwdFTP"],ENT_QUOTES,'UTF-8')?>"  onchange="setcoord(this);">
								</td>
							</tr>
							
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur:ftp:  repertoire de destination ftp')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  style="width:190px;border:#cccccc 1px solid; font-size:11px;;"  type="text" name="destFTP"   value="<?php echo htmlentities($coord["destFTP"],ENT_QUOTES,'UTF-8')?>"  onchange="setcoord(this);">
								</td>
							</tr>
							
							<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur:ftp: prefixe des noms de dossier ftp')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  style="width:190px;border:#cccccc 1px solid;font-size:11px;;"  type="text" name="prefixFTPfolder"   value="<?php echo htmlentities($coord["prefixFTPfolder"],ENT_QUOTES,'UTF-8')?>"  onchange="setcoord(this);">
								</td>
							</tr>
							
							
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur:ftp: Utiliser le mode passif')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  style=""  type="checkbox" <?php echo ($coord["passifFTP"]=="1"?"checked":"")?> name="passifFTP" onchange="setcoord(this);">
								</td>
							</tr>
							
						 	<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur:ftp: Nombre d\'essais max')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  class="iptIdt" type="text" name="retryFTP" value="<?php echo $coord["retryFTP"]?>" onchange="setcoord(this);">
								</td>
							</tr>
							
							
							
							<tr>
								<td style="width:140px;text-align:left" nowrap><?php echo _('admin::compte-utilisateur:ftp: Donnees envoyees automatiquement par ftp')?>
								</td>
								<td style="width:200px; text-align:left;padding-bottom:3px" nowrap>
									<input  style=""  type="checkbox" <?php echo (substr($coord["bindefaultftpdatasent"],0,1)=="1"?"checked":"")?> name="ccsentftphd" id="ccsentftphd"  onchange="setcoord(this);">&nbsp;HD&nbsp;&nbsp;<input  style=""  type="checkbox" <?php echo (substr($coord["bindefaultftpdatasent"],1,1)=="1"?"checked":"")?> name="ccsentftpprev" id="ccsentftpprev"  onchange="setcoord(this);">&nbsp;Preview&nbsp;&nbsp;<input  style=""  type="checkbox" <?php echo (substr($coord["bindefaultftpdatasent"],2,1)=="1"?"checked":"")?> name="ccsentftpcaption" id="ccsentftpcaption"  onchange="setcoord(this);">&nbsp;Caption
								</td>
							</tr>
							
							<tr>
								<td colspan="2" style="width:140px;text-align:left" nowrap>
								<br><br>
								</td>
							</tr>
							
						</TABLE>
						</center>
<?php
	}						
?>						
					</div>
					
					<div id="divRights" style="background-color:#aaaaaa; width:100%; height:100%;overflow:hidden;" >
						
						<div class="classdivtable" id="tableau" style="text-align:left; align:left" >
						
							<div class="divTop"" id="tableau_top" >
								<TABLE cellSpacing="0" id="imgtopinclin"  >
									<THEAD>
										<TR>
											<TH style="TEXT-ALIGN: left;border:0px;background-color:#ffffff">
												<IMG border="0" src="/skins/lng/inclin-<?php echo $session->usr_i18n?>.gif" >
											</TH>
										</TR>
									</THEAD>
								</TABLE>
							</div>
						
							<div class="divLeft" id="tableau_left" >
								<TABLE cellSpacing=0 class="tableLeft" >
									<TBODY>

										<?php
										
										$compt_base_cour = 0;
										foreach($sbascoll_order as $sbas=>$arraycoll)
										{	
											if($compt_base_cour%2==0)
												$bground="#dddddd";
											else
												$bground="#eeeeee";
										?>
										<TR class="trLeft" style="background-color:<?php echo $bground?>" >
											<TD class="tdLeft" noWrap style="background-color:<?php echo $bground?>" >
												<div  class="divTdLeft" style="padding-left:2px;font-size:11px;background-color:<?php echo $bground?>">
													<u><i><?php echo $speedAccesParm[$sbas]["viewname"]?></i></u>
												</div>
											</TD>
										</TR>
										<?php
											foreach($arraycoll as $coll_id)
											{
												if( !isset($jsQuota[ $sbas ] [ $coll_id ]) )
													$jsQuota[ $sbas ] [ $coll_id ]= "new quotabase(0,0,0);";
										
												if( !isset($jsTime[ $sbas ] [ $coll_id ]) )
													$jsTime[ $sbas ] [ $coll_id ]= "new timelimit(0,". date("Ymd").",". date("Ymd")."0);";
													
													
										?>
										<TR class="trLeft" style="background-color:<?php echo $bground?>" >
											<TD class="tdLeft" noWrap style="background-color:<?php echo $bground?>;" >
												<div  class="divTdLeft" style="padding-left:10px;background-color:<?php echo $bground?>;height:25px;overflow:hidden">
													<?php echo $bas2name[$coll_id]?>
												</div>
											</TD>
										</TR>
										<?php
											}
											$compt_base_cour++;
										}
										?>			
										<TR class="trLeft" >
											<TD class="tdLeft" noWrap>
												<div  class="divTdLeft">
													 
												</div>
											</TD>
										</TR>
									</TBODY>
								</TABLE>
							</DIV>
							
							<DIV class="divCenter" id="tableau_center"  onscroll="tableScroll(this);" style="text-align:left; align:left; ">
						 
								<TABLE id="tableau" class="tableCenter" style="width:698px;TABLE-LAYOUT: fixed;TEXT-ALIGN: center; align:center; border:0px" width="700px" cellpadding="0" cellSpacing="0">
									<TBODY>
										<?php
										$compt_base_cour = 0;
										$totaccpersbas = null;
										$relationsbas = "";
										$relationsbastmp = "";
										
										foreach($sbascoll_order as $sbas=>$arraycoll)
										{
											
											$totaccpersbas[$sbas]=0;
											
											if($relationsbastmp!="")
												$relationsbas.=$relationsbastmp."},";
												
												
											$relationsbas .= " \"$sbas\" : {";	
											$relationsbastmp = "";
											
											if($compt_base_cour%2==0)
												$bground="#dddddd";
											else
												$bground="#eeeeee";
												
												
												
											$theclient = browser::getInstance();
											if($theclient->getPlatform()=="Apple" )
											{
										?>
										<TR  style="text-align:left;align:left;background-color:<?php echo $bground?>;"   >
											<TD class="tdTableCenter" style="width:25px;cursor:default" colname="acces"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="actif"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:22px;cursor:default" colname="album"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="canprev"  colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="water"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="canhd"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:32px;cursor:default" colname="dlprev"   colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:28px;cursor:default" colname="dlhd"     colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:23px;cursor:default" colname="cmd"      colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:23px;cursor:default" colname="quota"    colsbas="<?php echo $sbas?>" sbasname="<?php echo $speedAccesParm[$sbas]["dbname"]?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="time"     colsbas="<?php echo $sbas?>" sbasname="<?php echo $speedAccesParm[$sbas]["dbname"]?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:22px;cursor:default" colname="mask"     colsbas="<?php echo $sbas?>" sbasname="<?php echo $speedAccesParm[$sbas]["dbname"]?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:64px;cursor:default" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="addrec"   colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="modifrec" colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:25px;cursor:default" colname="chgstat"  colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:20px;cursor:default" colname="delrec"   colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:22px;cursor:default" colname="imgtools" colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:24px;cursor:default" colname="admin"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:20px;cursor:default" colname="report"   colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:22px;cursor:default" colname="push"     colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:23px;cursor:default" colname="manage"   colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:22px;cursor:default" colname="modifstruct" colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											
											<TD class="tdTableCenter" style="width:23px; border-right:0px;cursor:default;" >
<?php
	
	$visibility = "hidden";
	if( isset($droitusrsbas[$sbas]["visible"]) && $droitusrsbas[$sbas]["visible"]>0 )
		$visibility = "visible";
		
	$cc = "0";
	if(isset($droitusrsbas[$sbas]["baschupub"]))
		$cc = $droitusrsbas[$sbas]["baschupub"];
		
	if($myRightsMax[$sbas]["baschupub"]!="1" && $myRightsMax[$sbas]["baschupub"]!="1")
			$cc="5";	
?>												<div class="divTdTableCenter" >
													<img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="baschupub_<?php echo $sbas?>" state="<?php echo $cc?>" onClick="clk_chbx(this,'<?php echo $sbas?>','','baschupub');" border="0">
												</div>
											</TD>
											
											<TD class="tdTableCenter" style="width:23px; border-right:0px;cursor:default;" >
<?php
	
	$visibility = "hidden";
	if( isset($droitusrsbas[$sbas]["visible"]) && $droitusrsbas[$sbas]["visible"]>0 )
		$visibility = "visible";
		
	$cc = "0";
	if(isset($droitusrsbas[$sbas]["basmodifth"]))
		$cc = $droitusrsbas[$sbas]["basmodifth"];
		
	if($myRightsMax[$sbas]["basmodifth"]!="1" && $myRightsMax[$sbas]["basmanage"]!="1")
			$cc="5";	
?>												<div class="divTdTableCenter" >
													<img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="basmodifth_<?php echo $sbas?>" state="<?php echo $cc?>" onClick="clk_chbx(this,'<?php echo $sbas?>','','basmodifth');" border="0">
												</div>
											</TD>
											<TD class="tdTableCenter" style="width:23px; border-right:0px;cursor:default" >
<?php
		$visibility = "hidden";
		if( isset($droitusrsbas[$sbas]["visible"]) && $droitusrsbas[$sbas]["visible"]>0 )
			$visibility = "visible";	
					
		$cc = "0";
		if(isset($droitusrsbas[$sbas]["basmanage"]))
			$cc = $droitusrsbas[$sbas]["basmanage"];			
		if($myRightsMax[$sbas]["basmanage"]!="1")
				$cc="5";	
?>												<div class="divTdTableCenter" >
													<img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="basmanage_<?php echo $sbas?>" state="<?php echo $cc?>" onClick="clk_chbx(this,'<?php echo $sbas?>','','basmanage');" border="0">
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusrsbas[$sbas]["basmodifstruct"]))
			$cc = $droitusrsbas[$sbas]["basmodifstruct"];
		if($myRightsMax[$sbas]["basmodifstruct"]!="1")
				$cc="5";
?>
											<TD class="tdTableCenter" style="width:21px;cursor:default" >
												<div class="divTdTableCenter" >
													<img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="basmodifstruct_<?php echo $sbas?>" state="<?php echo $cc?>" onClick="clk_chbx(this,'<?php echo $sbas?>','','basmodifstruct');" border="0">
												</div>
											</TD>
											<TD style="width:52px;background-color:#ffffff;cursor:default;border-right:#ffffff 1px solid" >
												&nbsp;
											</TD>											
										</TR>	
<?php
	}
	else
	{	
?>
										<TR style="text-align:left;align:left;background-color:<?php echo $bground?>;"   >
											<TD class="tdTableCenter" style="width:26px;cursor:default" colname="acces"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:20px;cursor:default" colname="actif"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="album"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:20px;cursor:default" colname="canprev"  colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:20px;cursor:default" colname="water"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:20px;cursor:default" colname="canhd"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:31px;cursor:default" colname="dlprev"   colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:26px;cursor:default" colname="dlhd"     colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:22px;cursor:default" colname="cmd"      colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:22px;cursor:default" colname="quota"    colsbas="<?php echo $sbas?>" sbasname="<?php echo $speedAccesParm[$sbas]["dbname"]?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:19px;cursor:default" colname="time"     colsbas="<?php echo $sbas?>" sbasname="<?php echo $speedAccesParm[$sbas]["dbname"]?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="mask"     colsbas="<?php echo $sbas?>" sbasname="<?php echo $speedAccesParm[$sbas]["dbname"]?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:63px;cursor:default" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:20px;cursor:default" colname="addrec"   colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:20px;cursor:default" colname="modifrec" colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:24px;cursor:default" colname="chgstat"  colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:19px;cursor:default" colname="delrec"   colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="imgtools" colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:23px;cursor:default" colname="admin"    colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:19px;cursor:default" colname="report"   colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="push"     colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:22px;cursor:default" colname="manage"   colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											<TD class="tdTableCenter" style="width:21px;cursor:default" colname="modifstruct" colsbas="<?php echo $sbas?>" >&nbsp;</TD>
											
											<TD class="tdTableCenter" style="width:23px; border-right:0px;cursor:default;" >
<?php
	$visibility = "hidden";
	if( isset($droitusrsbas[$sbas]["visible"]) && $droitusrsbas[$sbas]["visible"]>0 )
		$visibility = "visible";
		
	$cc = "0";
	if(isset($droitusrsbas[$sbas]["baschupub"]))
		$cc = $droitusrsbas[$sbas]["baschupub"];
		
	if($myRightsMax[$sbas]["baschupub"]!="1" && $myRightsMax[$sbas]["baschupub"]!="1")
			$cc="5";	
?>												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: gestion des publications')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="baschupub_<?php echo $sbas?>" state="<?php echo $cc?>" onClick="clk_chbx(this,'<?php echo $sbas?>','','baschupub');" border="0"></acronym>
												</div>
											</TD>
											
											<TD class="tdTableCenter" style="width:23px; border-right:0px;cursor:default;" >
<?php
	$visibility = "hidden";
	if( isset($droitusrsbas[$sbas]["visible"]) && $droitusrsbas[$sbas]["visible"]>0 )
		$visibility = "visible";
		
	$cc = "0";
	if(isset($droitusrsbas[$sbas]["basmodifth"]))
		$cc = $droitusrsbas[$sbas]["basmodifth"];
		
	if($myRightsMax[$sbas]["basmodifth"]!="1" && $myRightsMax[$sbas]["basmanage"]!="1")
			$cc="5";	
?>												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: gestion du thesaurus')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="basmodifth_<?php echo $sbas?>" state="<?php echo $cc?>" onClick="clk_chbx(this,'<?php echo $sbas?>','','basmodifth');" border="0"></acronym>
												</div>
											</TD>
											<TD class="tdTableCenter" style="width:23px; border-right:0px;cursor:default" >
<?php
	$visibility = "hidden";
	if( isset($droitusrsbas[$sbas]["visible"]) && $droitusrsbas[$sbas]["visible"]>0 )
		$visibility = "visible";
		
	$cc = "0";
	if(isset($droitusrsbas[$sbas]["basmanage"]))
		$cc = $droitusrsbas[$sbas]["basmanage"];
		
	if($myRightsMax[$sbas]["basmanage"]!="1")
			$cc="5";	
?>												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: gestion de la base')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="basmanage_<?php echo $sbas?>" state="<?php echo $cc?>" onClick="clk_chbx(this,'<?php echo $sbas?>','','basmanage');" border="0"></acronym>
												</div>
											</TD>
<?php
	$cc = "0";
	if(isset($droitusrsbas[$sbas]["basmodifstruct"]))
		$cc = $droitusrsbas[$sbas]["basmodifstruct"];
	if($myRightsMax[$sbas]["basmodifstruct"]!="1")
			$cc="5";		
?>
											<TD class="tdTableCenter" style="width:20px;cursor:default" >
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: structure de la base')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="basmodifstruct_<?php echo $sbas?>" state="<?php echo $cc?>" onClick="clk_chbx(this,'<?php echo $sbas?>','','basmodifstruct');" border="0"></acronym>
												</div>
											</TD>
											<TD style="width:57px;background-color:#ffffff;cursor:default;border-right:#ffffff 1px solid" >
												&nbsp;
											</TD>											
										</TR>	
										
										
										
<?php
	}
	 
	foreach($arraycoll as $coll_id)
	{ 
		
		// $droitusr[ $row["sbas_id"] ] [ $row["base_id"] ] = $row;
		// $droitusr[$sbas][$coll_id] = $row;
		if($relationsbastmp!="")
			$relationsbastmp.=",";
		$relationsbastmp .= "\"$coll_id\":\"$coll_id\"";
		// $relationsbastmp .= "$coll_id:$coll_id";
			
?>
										<TR  style="text-align:left;align:left;background-color:<?php echo $bground?>;"   >
<?php
		$visibility = "hidden";
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["acces"]))
			$cc = $droitusr[$sbas][$coll_id]["acces"];
		if($cc=="1")
		{
			$visibility = "visible";	
			$totaccpersbas[$sbas]++;
		}
		
?>
											<!-- ACCEDER -->
											<TD class="tdTableCenter" colname="acces" colsbas="<?php echo $sbas?>">
												<div class="divTdTableCenter"  >
													<acronym title="<?php echo _('admin::user: acceder a la collection')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" id="acces_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','acces');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["actif"]))
			$cc = $droitusr[$sbas][$coll_id]["actif"];
		
		if($myRightsMax[$sbas][$coll_id]["actif"]!="1")
			$cc="5";
?>
											<!-- actif -->
											<TD class="tdTableCenter" colname="actif" colsbas="<?php echo $sbas?>">
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: actif sur la collection')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="actif_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','actif');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["album"]))
			$cc = $droitusr[$sbas][$coll_id]["album"];
		
		if($myRightsMax[$sbas][$coll_id]["album"]!="1")
			$cc="5";
?>
											<!-- selection -->
											<TD class="tdTableCenter" colname="album" colsbas="<?php echo $sbas?>">
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: construction de paniers personnels')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="album_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','album');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["canprev"]))
			$cc = $droitusr[$sbas][$coll_id]["canprev"];
		
		if($myRightsMax[$sbas][$coll_id]["canprev"]!="1")
			$cc="5";
?>
											<!-- voir preview -->
											<TD class="tdTableCenter" colname="canprev" colsbas="<?php echo $sbas?>">
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: voir les previews')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="canprev_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','canprev');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["water"]))
			$cc = $droitusr[$sbas][$coll_id]["water"];
?>
											<!--  Watermark -->
											<TD class="tdTableCenter" colname="water" colsbas="<?php echo $sbas?>">
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('phraseanet::watermark')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="water_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','water');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["canhd"]))
			$cc = $droitusr[$sbas][$coll_id]["canhd"];
		
		if($myRightsMax[$sbas][$coll_id]["canhd"]!="1")
			$cc="5";
?>
											<!-- voir HD  -->
											<TD class="tdTableCenter" colname="canhd" colsbas="<?php echo $sbas?>">
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: voir les originaux')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="canhd_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','canhd');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["dlprev"]))
			$cc = $droitusr[$sbas][$coll_id]["dlprev"];
		
		if($myRightsMax[$sbas][$coll_id]["dlprev"]!="1")
			$cc="5";
?>
											<!-- dl preview -->
											<TD class="tdTableCenter"  colname="dlprev" colsbas="<?php echo $sbas?>" style="text-align:center;" >
												<center><div class="divTdTableCenter" style="text-align:center;">
													<acronym title="<?php echo _('admin::user: telecharger les previews')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="dlprev_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','dlprev');" state="<?php echo $cc?>" border="0"></acronym>
												</div></center>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["dlhd"]))
			$cc = $droitusr[$sbas][$coll_id]["dlhd"];
		
		if($myRightsMax[$sbas][$coll_id]["dlhd"]!="1")
			$cc="5";
?>
											<!-- dl hd -->
											<TD class="tdTableCenter" colname="dlhd" colsbas="<?php echo $sbas?>"  style="text-align:center;">
												<center><div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: telecharger les originaux')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="dlhd_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','dlhd');" state="<?php echo $cc?>" border="0"></acronym>
												</div></center>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["cmd"]))
			$cc = $droitusr[$sbas][$coll_id]["cmd"];
		
		if($myRightsMax[$sbas][$coll_id]["cmd"]!="1")
			$cc="5";
?>
											<!-- commander -->
											<TD class="tdTableCenter" colname="cmd" colsbas="<?php echo $sbas?>">
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: commander les documents')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="cmd_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','cmd');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>

											<!-- quota -->
											<TD class="tdTableCenter" style="width:23px">
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: acces aux quotas')?>"><img name="ccuser" src="/skins/icons/buttoninfo2.gif" style="visibility:<?php echo $visibility?>" id="quota_<?php echo $sbas."_".$coll_id?>" border="0" onClick="clkQuota('<?php echo $sbas?>','<?php echo $coll_id?>','<?php echo $speedAccesParm[$sbas]["dbname"]?>','<?php echo p4string::MakeString($bas2name[$coll_id],"js")?>')"></acronym>
												</div>
											</TD>

											<!-- limit -->
											<TD class="tdTableCenter" style="width:20px">
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: restrictions de telechargement')?>"><img name="ccuser" src="/skins/icons/buttoninfo2.gif" style="visibility:<?php echo $visibility?>" id="limit_<?php echo $sbas."_".$coll_id?>" border="0" onClick="clkTime('<?php echo $sbas?>','<?php echo $coll_id?>','<?php echo $speedAccesParm[$sbas]["dbname"]?>','<?php echo p4string::MakeString($bas2name[$coll_id],"js")?>')" ></acronym>
												</div>
											</TD>

											<!-- mask -->
											<TD class="tdTableCenter" style="width:21px">
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: acces au restrictions par status')?>"><img name="ccuser" src="/skins/icons/buttoninfo2.gif" style="visibility:<?php echo $visibility?>" id="mask_<?php echo $sbas."_".$coll_id?>" border="0" onClick="clkMask('<?php echo $sbas?>','<?php echo $coll_id?>','<?php echo $speedAccesParm[$sbas]["dbname"]?>','<?php echo p4string::MakeString($bas2name[$coll_id],"js")?>')" ></acronym>
												</div>
											</TD>
						
											
											<!-- space -->
											<TD class="tdTableCenter">
												 &nbsp;
											</TD>
					
					
					
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["addrec"]))
			$cc = $droitusr[$sbas][$coll_id]["addrec"];
		
		if($myRightsMax[$sbas][$coll_id]["addrec"]!="1")
			$cc="5";
?>					
											<!-- add  -->
											<TD class="tdTableCenter" colname="addrec" colsbas="<?php echo $sbas?>">
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: ajouts de documents ')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="addrec_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','addrec');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["modifrec"]))
			$cc = $droitusr[$sbas][$coll_id]["modifrec"];
		
		if($myRightsMax[$sbas][$coll_id]["modifrec"]!="1")
			$cc="5";
?>			
											<!--  edit -->
											<TD class="tdTableCenter" colname="modifrec" colsbas="<?php echo $sbas?>" >
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: edition de documents')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="modifrec_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','modifrec');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["chgstat"]))
			$cc = $droitusr[$sbas][$coll_id]["chgstat"];
		
		if($myRightsMax[$sbas][$coll_id]["chgstat"]!="1")
			$cc="5";
?>			
											<!-- change  -->
											<TD class="tdTableCenter" colname="chgstat" colsbas="<?php echo $sbas?>" >
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: gestion des status')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="chgstat_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','chgstat');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["delrec"]))
			$cc = $droitusr[$sbas][$coll_id]["delrec"];
		
		if($myRightsMax[$sbas][$coll_id]["delrec"]!="1")
			$cc="5";
?>			
											<!-- delete  -->
											<TD class="tdTableCenter" colname="delrec" colsbas="<?php echo $sbas?>" >
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: suppression de document')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="delrec_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','delrec');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["imgtools"]))
			$cc = $droitusr[$sbas][$coll_id]["imgtools"];
		
		if($myRightsMax[$sbas][$coll_id]["imgtools"]!="1")
			$cc="5";
?>				
											<!-- img tools -->
											<TD class="tdTableCenter" colname="imgtools" colsbas="<?php echo $sbas?>" >
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: outils documents')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="imgtools_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','imgtools');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["admin"]))
			$cc = $droitusr[$sbas][$coll_id]["admin"];
		
		if($myRightsMax[$sbas][$coll_id]["admin"]!="1")
			$cc="5";
?>						
											<!-- manage user -->
											<TD class="tdTableCenter" colname="admin" colsbas="<?php echo $sbas?>" >
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: gestion des utilisateurs')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="admin_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','admin');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["report"]))
			$cc = $droitusr[$sbas][$coll_id]["report"];
		
		if($myRightsMax[$sbas][$coll_id]["report"]!="1")
			$cc="5";
?>						
											<!-- reports -->
											<TD class="tdTableCenter" colname="report" colsbas="<?php echo $sbas?>" >
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::monitor: module report')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="report_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','report');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["push"]))
			$cc = $droitusr[$sbas][$coll_id]["push"];
		
		if($myRightsMax[$sbas][$coll_id]["push"]!="1")
			$cc="5";
?>						
											<!-- push -->
											<TD class="tdTableCenter" colname="push" colsbas="<?php echo $sbas?>" >
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: acces au push')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="push_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','push');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["manage"]))
			$cc = $droitusr[$sbas][$coll_id]["manage"];
		
		if($myRightsMax[$sbas][$coll_id]["manage"]!="1")
			$cc="5";
?>						
											<!-- manage coll -->
											<TD class="tdTableCenter" colname="manage" colsbas="<?php echo $sbas?>" >
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: gestion des collections')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="manage_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','manage');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["modifstruct"]))
			$cc = $droitusr[$sbas][$coll_id]["modifstruct"];
		
		if($myRightsMax[$sbas][$coll_id]["modifstruct"]!="1")
			$cc="5";
?>						
											<!-- struct coll -->
											<TD class="tdTableCenter" colname="modifstruct" colsbas="<?php echo $sbas?>">
												<div class="divTdTableCenter" >
													<acronym title="<?php echo _('admin::user: gestion des preferences de collection')?>"><img name="ccuser" src="/skins/icons/ccoch<?php echo $cc?>.gif" style="visibility:<?php echo $visibility?>" id="modifstruct_<?php echo $sbas."_".$coll_id?>" onClick="clk_chbx(this,'<?php echo $sbas?>','<?php echo $coll_id?>','modifstruct');" state="<?php echo $cc?>" border="0"></acronym>
												</div>
											</TD>
<?php
		$cc = "0";
		if(isset($droitusr[$sbas][$coll_id]["basmanage"]))
			$cc = $droitusr[$sbas][$coll_id]["basmanage"];
		
		if($myRightsMax[$sbas][$coll_id]["basmanage"]!="1")
			$cc="5";
?>						
											<!-- publi paniers -->
											<TD class="tdTableCenter" style="border-right:0px;">
												<div class="divTdTableCenter" >
												</div>
											</TD>
											
											<!-- modif th -->
											<TD class="tdTableCenter" style="border-right:0px;">
												<div class="divTdTableCenter" >
												</div>
											</TD>
											
											<!-- manage base -->
											<TD class="tdTableCenter" style="border-right:0px;">
												<div class="divTdTableCenter" >
												</div>
											</TD>
											
											<!-- struct base -->
											<TD class="tdTableCenter">
												<div class="divTdTableCenter" >
												</div>
											</TD>
											
											<!-- space end -->
											<TD  style="background-color:#ffffff;border-right:#ffffff 1px solid">
												
											</TD>
										</TR>
							<?php
								}
								$compt_base_cour++;
							}
							
							if($relationsbas!="")
								$relationsbas.=$relationsbastmp."}";
								
							$relationsbas = "var relsbas = { " . $relationsbas ." };";		
								 
							
							?>						
									</TBODY>
								</TABLE>						
							</DIV>		
									
						</DIV>
					</div>
				</center>
				</td>
			</tr>			
			 
			
			<tr style="height:30px;" >
			
				<td style="height:30px;width:20px;BACKGROUND-POSITION: 0px 0px;BACKGROUND-REPEAT:no-repeat">
					
				</td>
				
				<td style="height:30px;  BACKGROUND-POSITION:  0px 0px;BACKGROUND-REPEAT: repeat-x; font-size:12px;text-align:center;">
					<b><a href="javascript:void();" onclick="valid();return(false);"  id="genevalid" style="color:#000000;text-decoration:none"><?php echo _('boutton::valider')?></a> </b>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<b><a href="javascript:void();" onclick="mycancel();return(false);"  id="genecancel" style="color:#000000;text-decoration:none"><?php echo _('boutton::annuler')?></a> </b>
				</td>
				
				<td style="height:30px;width:20px;font-size:12px; BACKGROUND-POSITION:left ;">
				</td>
				
			</tr>
			
			
		</table>
		
	<div id="desktopMenu" class="desktopMenu" onMouseOver="evt_overMenu();" onMouseOut="evt_outMenu();">
		<div><A href="javascript:void();" onclick="checkall();return(false);"><?php echo _('admin::user: cocher toute la colonne')?></A></div>
		<div><A href="javascript:void();" onclick="uncheckall();return(false);"><?php echo _('admin::user: decocher toute la colonne')?></A></div>
	</div>
	
	<div id="MenuMask" class="desktopMenu2" onMouseOver="evt_overMenuMask();" onMouseOut="evt_outMenuMask();" style="height:30px" >
		<div style="height:30px; " ><A href="javascript:void();" onclick="clkMaskBas();return(false);"><center><?php echo _('admin::user: recapitulatif des droits sur les status bits de la base')?></center></A></div>
	</div>
		
<script type="text/javascript">
function mystatus(nameon,nameoff)
{
     this.on  = nameon;
     this.off = nameoff;
}

function mask(vand_and,vand_or,vxor_and,vxor_or)
{
     this.vand_and  = vand_and;
     this.vand_or 	= vand_or;
     this.vxor_and 	= vxor_and;
     this.vxor_or 	= vxor_or;
}
function timelimit(limited,from,to)
{
     this.limited 		= limited;
     this.limitedfrom 	= from;
     this.limitedto 	= to;
}

var totaccpersbas = new Array();	
<?php

  #### les acces par bases
	foreach ($totaccpersbas as $sbas=>$nbacc)
		echo "\ntotaccpersbas[\"$sbas\"] = $nbacc;";
		
  ## pour les quotas	
	echo "\nvar allquotas =  new Array();";	
	foreach($jsQuota as $sbas=>$array_coll)
	{
		echo "\nallquotas[\"$sbas\"] =  new Array();";
		foreach($array_coll as $idcoll=>$val)
			echo "\nallquotas[\"$sbas\"][\"$idcoll\"] =  $val";
	}
	
  ## pour les time limit	
	echo "\nvar alltimelimit =  new Array();";	
	foreach($jsTime as $sbas=>$array_coll)
	{
		echo "\nalltimelimit[\"$sbas\"] =  new Array();";
		foreach($array_coll as $idcoll=>$val)
			echo "\nalltimelimit[\"$sbas\"][\"$idcoll\"] =  $val";
	}
   ## pour les masks	
	echo "\nvar allmask =  new Array();";	
	foreach($allmask as $sbas=>$array_coll)
	{
		echo "\nallmask[\"$sbas\"] =  new Array();";
		foreach($array_coll as $idcoll=>$val)
			echo "\nallmask[\"$sbas\"][\"$idcoll\"] = new mask('".$val["vand_and"]."','".$val["vand_or"]."','".$val["vxor_and"]."','".$val["vxor_or"]."')";
	}
	
  ######  pour le nom des status
	echo "\nvar statusname =  new Array();";	
	foreach($allstatusname as $sbas=>$array_coll)
	{
		echo "\nstatusname[\"$sbas\"] =  new Array();";
		foreach($array_coll as $bit=>$nom)
			echo "\nstatusname[\"$sbas\"][\"$bit\"] =  new mystatus(\"".$nom["on"]."\",\"".$nom["off"]."\");";
	}
	
	echo "\n". $relationsbas;	

	
?>	
var modif = new Array();	
</script>
</body>
</html>
