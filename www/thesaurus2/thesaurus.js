
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


function showFound2(term, lterm, branch, depth)
{
	var c;
	var ret = 0;
	var thb = branch.firstChild.nextSibling.nextSibling;
	// branch est un <DIV ID="THE_xxx">
	
	if(thb)
	{
		for(c=thb.firstChild; c; c=c.nextSibling)	// THE, les SY ou les TA
		{
			if(c.nodeName=="DIV")
				ret += showFound2(term, lterm, c, depth+1);	// on descend uniquement les THE_yyy
		}
	}


	if(branch.firstChild.nextSibling.nodeValue.substr(0, lterm)==term)
	{
		ret = 1;
//		alert(branch.firstChild.nextSibling.nodeValue +  " : " + thb.id);
	}

//	if(ret > 0)	
//	if(depth > 0)
//	{
		if(ret > 0)
		{
			//eventObj.Src0.innerHTML = "+";
			thb.className = "OB";
		}
		else
		{
			//eventObj.Src0.innerHTML = "+";
			thb.className = "ob";
		}
//	}
/*
	if(depth == 0)
	{
		document.getElementById("WT1").style.visibility="hidden";
		if(document.forms["fTh"].textT1.value!=term)
		{
			// oups! le mot a changé durant le traitement, on recommence
			evt_kup_T1();
		}
	}
*/
	return(ret);
}

function showAll(branch, depth)
{
  depth = parseInt(depth);
	var c;
	for(c=branch.firstChild; c; c=c.nextSibling)
	{
		if(c.nodeType==1 && c.nodeName=="DIV")		// 1=XML_ELEMENT_NODE
			showAll(c, depth+1);
	}

	if(depth > 0)
		branch.style.display = "";

	if(depth===0)
	{
		document.getElementById("WT1").style.visibility="hidden";
		if(document.forms["fTh"].textT1.value!=="")
		{
			// oups! le mot a changé durant le traitement, on recommence
			evt_kup_T1();
		}
	}
}



function scanTerms(inputName, zTerm, showhide)
{
  showhide = !!showhide;
	var lTerm = zTerm.length;
	var zTable = document.getElementById("L"+inputName);
	var zTr = zTable.childNodes;	// TR's
	var l = zTr.length;
	var found = null;
	for(var i=0; i<l; i++)
	{
//		if(renum)
//			zTr[i].id = inputName+"_"+i
		var t = zTr[i].firstChild.firstChild.nodeValue;
//		alert(i+" "+t);
		if(zTerm == t)
			found = zTr[i];

		if(showhide === true)
		{
			if(lTerm==0 || (t.substr(0, lTerm)==zTerm))
				zTr[i].style.display = "";
			else
				zTr[i].style.display = "none";
		}
		else
		{
			zTr[i].style.display = "";
		}
	}
	return(found);
}

function addTerm(inputName, zTerm, oldid)	// inputName = "TS"|"TA"|"SY"
{
	if(typeof(zTerm)=="undefined")		// si pas de terme en argument, prendre dans la zone de saisie
		zTerm = document.forms["fTh"]["text"+inputName].value;
// alert(zTerm);
	// on cherche si le zTerm existe déjà
//	var parent_id = selectedThesaurusItem.getAttribute("id");
//	alert("parent_id = " + parent_id);

	// found = scanTerms(inputName, true, false);	// renuméroter et tout afficher
	var found = scanTerms(inputName, zTerm, false);	// tout afficher
	if(!found)
	{
		// on cherche la div "thb" si elle existe
		var thb, thRef;
		for(thb=selectedThesaurusItem.firstChild; thb; thb=thb.nextSibling)
		{
			if(thb.nodeType==1 && thb.tagName=="DIV" && thb.id.substr(0,4)=="THB_")
				break;
		}
		if(!thb)
		{
			// on ajoute le premier fils ...
			// ... on crée le +/- en face du terme
			selectedThesaurusItem.firstChild.className = "tri";
			selectedThesaurusItem.firstChild.id = "THP_" + selectedThesaurusItem.id.substr(4);
			selectedThesaurusItem.firstChild.innerHTML = "-";
	//		selectedThesaurusItem.nextid = "0";
			selectedThesaurusItem.setAttribute("nextid", "0");
			// ... on crée la div "THB"
			thb = selectedThesaurusItem.appendChild(document.createElement("DIV"));
			thb.className = "ob";
			thb.id = "THB_" + selectedThesaurusItem.id.substr(4);
		}
		
		if(inputName=="TS") // on ajoute un terme spécifique
		{
			// un id pour le nouveau terme
			var nextid = parseInt(selectedThesaurusItem.getAttribute("nextid"));
		//	selectedThesaurusItem.nextid = "" + (nextid+1);
			selectedThesaurusItem.setAttribute("nextid", "" + (nextid+1));

			// on ajoute le nouveau terme dans le thb : on crée une nouvelle div
			var div = document.createElement("DIV");
			div.className = "s_";
			if(selectedThesaurusItem.id == "THE_")
				div.id = "THE_" + nextid;
			else
				div.id = selectedThesaurusItem.id + "." + nextid;
			if(typeof(oldid)=="undefined")
			{
		//		div.oldid = "?";		// permettra de repérer les nouveaux termes
				div.setAttribute("oldid", "?");		// permettra de repérer les nouveaux termes
			}
			else
			{
		//		div.oldid = oldid;		// le terme a provient des termes candidats
				div.setAttribute("oldid", oldid);		// le terme a provient des termes candidats
			}
			div.setAttribute("lng", "");
			var u = div.appendChild(document.createElement("U"));
			//u.appendChild(document.createEntityReference("nbsp"));
			u.innerHTML = "&nbsp;";
			div.appendChild(document.createTextNode(zTerm));
			thRef = thb.appendChild(div);
		}
		else	// inputName="TA"|"SY" : on ajoute un terme associé ou un synonyme
		{
			var p = document.createElement("P");
			p.className = inputName.toLowerCase();	// ta ou sy
			p.appendChild(document.createTextNode(zTerm));
			thRef = thb.appendChild(p);
			nextid = document.getElementById("L"+inputName).nextid++;
		}

		// on ajoute aussi à la liste des termes
		tr = appendTerm(inputName, zTerm, nextid);
		// on scroll la liste pour montrer le nouveau terme, et on le selectionne
		tr.scrollIntoView(false);
		myGUI.select(tr);

		tr.thRef = thRef;	// lien du nouveau terme de la liste vers le thesaurus

		document.forms["fTh"]["text"+inputName].value = "";

		termChanged = true;
		
		dirty();
	}
//	else
//	{
		// alert("Le terme associé '"+newterm+"' existe déjà.");
//	}
	evt_kup(inputName);
}

function dirty()
{
	thesaurusChanged = true;
	document.getElementById("saveButton").style.display = "";
}
/*
function delTerm(inputName, zTerm)	// inputName = "TS"|"TA"|"SY"
{
	if(typeof(zTerm)=="undefined")
		zTerm = document.forms["fTh"]["text"+inputName].value;

	// on cherche si le zTerm existe déjà
	// zTr = scanTerms(inputName, true, false);	// renuméroter et tout afficher
	var zTr = scanTerms(inputName, zTerm, false);	//  tout afficher
	if(zTr)
	{
		// si on a supprimé un terme spécifique, on vérifie s'il en reste
		var thb = zTr.thRef.parentNode;
		
		if(inputName == "TS")
		{
			// on deplace du thesaurus vers les candidats (refuse), pour le champ special '(deleted)'
			var deleted=null
			var thb_deleted;
			// on cherche la branche de 'deleted' dans les cterms		
			for(c=document.getElementById("CTERMS").firstChild; c && !deleted; c=c.nextSibling)
			{
				if(c.nodeType==1 && c.field && c.field=="(deleted)")
					deleted = c;
			}
			// si elle n'existe pas on la cree
			if(!deleted)
			{
				var zid = document.getElementById("CTERMS").nextid;
				document.getElementById("CTERMS").setAttribute("nextid", parseInt(zid)+1);
				
				// on cree le grp
				deleted = document.getElementById("CTERMS").appendChild(document.createElement("DIV"));
				deleted.name = "CTERMSGRP";
				deleted.className = "s_ R_";
				deleted.id = "C"+zid;
				deleted.setAttribute("nextid", "0");
				deleted.setAttribute("field", "(deleted)");
				
				var u = deleted.appendChild(document.createElement("U"));
				u.className = "tri";
				u.id = "THP_C"+zid;
				u.innerText = "+ ";
				
				deleted.appendChild(document.createTextNode("(deleted)"));
				
				thb_deleted = deleted.appendChild(document.createElement("DIV"));
				thb_deleted.className = "ob";
				thb_deleted.id = "THB_C"+zid;
			}
			else
			{
				zid = deleted.id.substr(1);
				thb_deleted = document.getElementById("THB_C" + zid);
			}

			// var d = thb_deleted.appendChild(document.createElement("DIV") );
			// d.className = "s_ R_";
			// d.id = "TCE_R" + zid + "." + deleted.nextid;
			// d.appendChild(zTr.thRef.firstChild.nextSibling.cloneNode(false) );
			// d.setAttribute("oldid", zTr.thRef.oldid ? zTr.thRef.oldid : zTr.thRef.id.substr(4) );
			
			// deleted.setAttribute("nextid", parseInt(deleted.getAttribute("nextid")+1) );

			deleteBranch(zTr.thRef, thb_deleted);
		}
		thb.removeChild(zTr.thRef);	// supprime le node du thesaurus
		if(!thb.firstChild)
		{
			// plus de ts : on nettoie
			var the = thb.parentNode;
			the.removeChild(thb);	// supprime thb
			var u = the.firstChild;
			u.innerHTML = "&nbsp;"	// vire le +/-
			u.className = "";
		}

		// on supprime aussi de la liste des termes
		zTr.parentNode.removeChild(zTr);
		document.forms["fTh"]["text"+inputName].value = "";

		termChanged = true;
		
		dirty();
	}
	else
	{
		// alert("Le terme associé '"+newterm+"' n' existe pas.");
	}
	evt_kup(inputName);
}
*/
/*
// supprime un terme et tous ses fils (deplace la branche dans '(deleted)' )
function deleteBranch(the, thb_deleted)
{
	newdel = thb_deleted.appendChild(the.cloneNode(true));
	deleteBranch0(newdel, "R"+thb_deleted.parentNode.id.substr(1));
}

function deleteBranch0(node, pfxid)
{
	if(node.id && node.id.substr(0,2)=="TH")
	{
		oldid = node.oldid ? node.oldid : node.id.substr(4);
		if(node.id.substr(0,4)=="THE_")
		{
			node.id = "TCE_" + pfxid + "_" + oldid;
			node.className = "s_ R_";
			node.setAttribute("oldid", oldid);	
		}
		else	// THB_ ou THP_
		{
			node.id = node.id.substr(0,4) + pfxid + "_" + oldid;
		}
	}
	for(var node=node.firstChild; node; node=node.nextSibling)
	{
		deleteBranch0(node, pfxid);
	}
}
*/
// supprime un terme et tous ses fils (deplace 'e plat') dans '(deleted)'
function deleteBranch(the, thb_deleted)
{
	if(the.id.substr(0,4)=="THE_")
	{
		var d = thb_deleted.appendChild(document.createElement("DIV") );
		d.className = "s_ R_";
		d.id = "TCE_R" + (thb_deleted.parentNode.id.substr(1)) + "." + (thb_deleted.parentNode.getAttribute("nextid"));
		thb_deleted.parentNode.setAttribute("nextid", parseInt(thb_deleted.parentNode.getAttribute("nextid")+1) );
		d.appendChild(the.firstChild.nextSibling.cloneNode(false) );
		d.setAttribute("oldid", the.oldid ? the.oldid : the.id.substr(4) );
		if(the.firstChild.nextSibling.nextSibling)
		{
			for(var the=the.firstChild.nextSibling.nextSibling.firstChild; the; the=the.nextSibling)
			{
				deleteBranch(the, thb_deleted);
			}
		}
	}
}

function alertNode(n, msg)
{
	if(typeof(msg)=="undefined")
		msg = "";
	if(n)
	{
		if(n.nodeType==1)
		{
			alert(msg + " : <"+n.nodeName+" id='"+n.id+"'>");
		}
		else
		{
			alert(msg + " : nodeType="+n.nodeType);
		}
	}
	else
	{
		alert(msg + " : NULL");
	}
}

function appendTerm(inputName, new_term, id)
{
	var tr = document.createElement("TR");
	tr.id = inputName + "_"+id;
	tr.className = "s_";
	var td = tr.appendChild(document.createElement("TD"));
	td.appendChild(document.createTextNode(new_term));
	td = tr.appendChild(document.createElement("TD"));
	td.innerHTML = "<img id='"+inputName+"f_"+id+"' src='./images/noflag.gif' />";
	td = tr.appendChild(document.createElement("TD"));
	td.appendChild(document.createTextNode(" "));

	var zTable = document.getElementById("L"+inputName);
	return(zTable.appendChild(tr));
}

