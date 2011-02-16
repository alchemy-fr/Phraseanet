<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$request = httpRequest::getInstance();
$parm = $request->get_parms( 'tid'	// task_id
					 );
?>
var allgetID = new Array ;
var total = 0;
var statuscoll="";	
var changeInXml = false ;
var avantModif="";

function loadXMLDoc(url, post_parms, asxml)
{
	if(typeof(asxml)=="undefined")
		asxml = false;
	out = null;
	xmlhttp = null;
	// code for Mozilla, etc.
	if (window.XMLHttpRequest)
		xmlhttp=new XMLHttpRequest();
	else if (window.ActiveXObject)
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		
	if (xmlhttp)
	{
		// xmlhttp.onreadystatechange=state_Change
		if(post_parms)
		{
			xmlhttp.open("POST", url, false);
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			xmlhttp.send(post_parms);
		}
		else
		{
			xmlhttp.open("GET", url, false);
			xmlhttp.send(null);
		}
		out = asxml ? xmlhttp.responseXML : xmlhttp.responseText;
	}
	return(out);
}


var pass=false;
/*
function redrawme()
{
	hauteur =  document.body.clientHeight;
//	document.getElementById("idBox2").style.height = (hauteur-140)+"px";	// div interface graph
//	document.getElementById("txtareaxml").style.height = (hauteur-170)+"px";	// textarea interface xml
	document.getElementById("idBox2").style.height = (hauteur-130)+"px";	// div interface graph
	document.getElementById("txtareaxml").style.height = (hauteur-160)+"px";	// textarea interface xml
}	
*/

var pref = new Array(0);
var lastpref=null;


function chgName(name)
{
	url  = "/xmlhttp/chgtask.x.php";
	parms  = "tid=<?php echo $parm['tid']?>";
	parms += "&name=" + encodeURIComponent(name);
// 	alert(url+"?"+parms);
	ret = loadXMLDoc(url, parms, true);
}

function chgActive(ck)
{
	url  = "/xmlhttp/chgtask.x.php";
	parms  = "tid=<?php echo $parm['tid']?>";
	parms += "&active=" + (ck ? "1":"0");
 	// alert(url+"?"+parms);
	if( (ret = loadXMLDoc(url, parms, true)) )
	{
		crashed = ret.documentElement.getAttribute("crashed");
		document.getElementById("idCrashCount").innerHTML = crashed;
		document.getElementById("idCrashLine").style.visibility = crashed > 0 ? "visible" : "hidden";
//		if(ret.documentElement.getAttribute("saved") == "1")
//			return(true);
	}
}


function saveXML()
{
	var xml = document.forms["fxml"].txtareaxml.value;
	if(xml)
	{
		url  = "/xmlhttp/chgtask.x.php";
		parms += "?tid=<?php echo $parm['tid']?>";
		parms += "&xml=" + encodeURIComponent(xml);
	 	// alert(url+"?"+parms);
		if( (ret = loadXMLDoc(url, parms, true)) )
		{
			if(ret.documentElement.getAttribute("saved") == "1")
				return(true);
		}
	}
	return(false);
}



function returnToTaskList()
{
	
}
