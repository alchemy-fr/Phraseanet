<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();


$request = httpRequest::getInstance();
$parm = $request->get_parms('act'		// NEWTASK or SAVETASK
					, "p0"
					, "p1"
					, "tid"	// task_id
					, 'tcl'	// task class
					, 'view'	// XML ou GRAPHIC
					 );

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	if(!$session->admin)
	{
	phrasea::headers(403);
	}
}
else{
	phrasea::headers(403);
}

phrasea::headers();
	
if(!$parm['view'])
	$parm['view'] = 'GRAPHIC';

	
$msg = "";
$refreshfinder = false;
$out = "";

$conn = connection::getInstance();
if(!$conn)
{
	die();
}

$rowtask = array(
					'active'=>'1',
					'name'=>_('admin::tasks: Nouvelle tache'),
					'class'=>$parm['tcl'],
					'settings'=>"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n</tasksettings>",
					'crashed'=>0
				);

$classname = null;
$taskid = '';


switch($parm['act'])
{
	case 'NEWTASK':		// blank task from scratch, NOT saved into sql
		$classname = $parm['tcl'];
		break;
	case 'EDITTASK':	// existing task
		if($parm["tid"]!==null)
		{
			$taskid = $parm["tid"];
			$sql = "SELECT * FROM task2 WHERE task_id='".$taskid."'";
			if($rs = $conn->query($sql))
			{
				if($rowtask = $conn->fetch_assoc($rs))
				{
					$classname = $rowtask["class"];
				}
				$conn->free_result($rs);
			}
		}
		break;
}

//$classname = 'task_'.$classname;

$ztask = null;
$zGraphicForm = 'graphicForm';
$hasGraphicMode = false;
		$ztask = new $classname();
		if(method_exists($ztask, 'getStrings'))
		{
		 	if($sx = simplexml_load_string($ztask->getStrings()))
		 	{
		 		$x = $sx->xpath('/strings/strings[@lng="'.$lng.'"]');
		 		if(count($x)==0)
					$x = $sx->xpath('/strings/strings[@lng="'.GV_default_lng.'"]');
		 		if(count($x)>0)
		 			$ztask->STRINGS = $x[0];
		 	}
		}
		// set some maybe useful values to inherited class
		$ztask->lng       = $lng;
		$ztask->act       = $parm['act'];
		$ztask->classname = $classname;
		$ztask->taskid    = $taskid;
//		$ztask->conn      = &$conn;
		$ztask->sxsettings = simplexml_load_string($rowtask['settings']);
		
		if(method_exists($ztask, 'getGraphicForm'))
		{
			$hasGraphicMode = true;
			$zGraphicForm = $ztask->getGraphicForm();
		}
		else
		{
			$parm['view'] = 'XML';
		}
		
		if($parm['act'] == 'NEWTASK' && method_exists($ztask, 'getName'))
		{
			$rowtask['name'] = $ztask->getName();
		}
//	}
//}

?>
<html lang="<?php echo $session->usr_i18n;?>">
<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
	<style>
		BODY
		{
			margin:10px;
		}
		
		* 
		{
			margin:0; 
			padding:0;
		}
		
		.divTab
		{
 			position:absolute;
 			left:30px;
 			top:-17px;
		}
		
		.tabFront
		{
			z-index:30;
			font-size:9px;
			position:relative;
			top:0px;
			background-color:#aaaaaa;
			border-top:#ffffff 1px solid;
			border-left:#ffffff 1px solid;
			border-bottom:#aaaaaa 1px solid;
			border-right:#000000 1px solid;
			padding-top:1px;
			padding-bottom:0px;
			padding-left:15px;
			padding-right:15px; 
			float:left;
			height:14px;
			cursor:pointer;
			color:#000000;
			text-decoration:none;
			text-align:center;
		}
		
		.tabBack
		{
			z-index:30;
			font-size:9px;
			position:relative;
			top:0px;
			background-color:#888888;
			border-top:#555555 1px solid;
			border-left:#555555 1px solid;
			border-bottom:#ffffff 1px solid;
			border-right:#bbbbbb 1px solid;
			padding-top:1px;
			padding-bottom:0px;
			padding-left:15px;
			padding-right:15px; 
			float:left;
			height:14px;
			cursor:pointer;
			color:#000000;
			text-decoration:none;
			text-align:center;
		}
		
		DIV.menu
		{
			font-size: 12px;
			border-left: 1px solid #ffffff;
			border-top: 1px solid #ffffff;
			border-right: 2px solid #000000;
			border-bottom: 2px solid #000000;
			padding:0px;
			margin:0px;
			visibility:hidden;
			position:absolute;
			top:0px;
			left:0px;
			background-color:#d4d0c8; 
		}
		DIV.menu IMG
		{
			padding:0px;
			margin:0px;
			position:relative;
			left:-10px;
			top:2px;
		}
		DIV.menu A
		{
			font-size: 12px;
			display:block;
			position:relative;
			text-decoration: none;
			color:#000000;
			padding-top:1px;
			padding-bottom:1px;
			padding-left:13px;
			padding-right:3px;
			overflow:hidden;
			border:none 0px #FFFFFF;
		}
		DIV.menu A:hover
		{
			font-size: 12px;
			display:block;
			position:relative;
			text-decoration: none;
			color:#ffffff;
			background-color:#000080;
		}
		DIV.menu A.disabled
		{
			font-size: 12px;
			display:block;
			position:relative;
			text-decoration: none;
			color:#A0A0A0;
			padding-top:1px;
			padding-bottom:1px;
			padding-left:13px;
			padding-right:3px;
			overflow:hidden;
		}
		DIV.menu A.disabled:hover
		{
			font-size: 12px;
			display:block;
			position:relative;
			text-decoration: none;
			color:#A0A0A0;
			background-color:#d4d0c8;
		}
		DIV.menu .line
		{
			display:block;
			position:relative;
			height:0px;
			overflow:hidden;
			margin-top:5px;
			margin-bottom:4px;
			padding:0px;
			border-top: 1px solid #555555;
			border-bottom: 1px solid #ffffff;
		}
	</style>
<?php
if(method_exists($ztask, 'printInterfaceHEAD'))
{
	printf("<!-- _____________  head added part of graphic interface of '%s'   _____________ -->\n", $ztask->getName());
	$ztask->printInterfaceHEAD();
	printf("<!-- ______________ end of head part of graphic interface of '%s' ______________ -->\n", $ztask->getName());
}
 ?>
	<script type="text/javascript">
	
	jsTaskObj = {
					SettingsIsDirty:false,
					
					currentView : null,
					
					oldXML:"<?php echo p4string::MakeString($rowtask['settings'], 'js')?>",

					view:function(type)
					{
						var o;
						var f;
						switch(type)
						{
							case 'PRE_XML':
<?php if($hasGraphicMode) { ?>
								document.getElementById('divGraph').style.display = "none";
								document.getElementById('divXml').style.display = "";
								document.getElementById('linkviewxml').className = "tabFront";		
								document.getElementById('linkviewgraph').className = "tabBack";
<?php } ?>
								this.currentView = "XML";
								break;
							case 'XML':
								if( (f = document.forms['<?php echo $zGraphicForm?>']) )
								{
									document.getElementById("__gxml").value = document.forms['fxml'].txtareaxml.value;
									document.getElementById("__gact").value = "FORM2XML";
									f.target = "hiddenFrame";
									f.action = "/admin/task2utils.php";
									f.submit();
									// this.view = "XML";	// made by task2utils.php
								}
								break;	
					
							case 'PRE_GRAPHIC':
							case 'GRAPHIC':
								document.forms['fxml'].target = "hiddenFrame";
								document.forms['fxml'].__act.value = "XML2FORM";
								document.forms['fxml'].submit();
								this.currentView = "GRAPHIC";
								break;
						}
					},

					saveTask:function(save)
					{
						if(save)
						{
							if(this.currentView == "GRAPHIC")
							{
								if( (f = document.forms['<?php echo $zGraphicForm?>']) )
								{
									document.getElementById("__gxml").value = document.forms['fxml'].txtareaxml.value;
									document.getElementById("__gtname").value = document.forms['__ftask'].__tname.value;
									document.getElementById("__gtactive").value = document.forms['__ftask'].__tactive.checked ? "1" : "0" ;
									document.getElementById("__gact").value = "SAVE_GRAPHIC";
									f.target = "hiddenFrame";
									f.action = "task2utils.php";
									f.submit();
								}
							}
							else
							{
								document.forms['fxml'].target = "hiddenFrame";
								document.forms['fxml'].__tname.value = document.forms['__ftask'].__tname.value;
								document.forms['fxml'].__tactive.value = document.forms['__ftask'].__tactive.checked ? "1" : "0" ;
								document.forms['fxml'].__act.value = "SAVE_XML";
								document.forms['fxml'].submit();
							}
						}
						else
						{
							if(document.forms["fxml"].__tid.value != "")
							{
								document.forms["__freturn"].submit();
							}
							else
							{
								document.forms["__freturn"].submit();
							}
						}
					},

					getHTTPObject:function()
					{
						var ret = null ;
						try
						{
							ret = new XMLHttpRequest();
						} 
						catch (e)
						{
							try
							{
								ret = new ActiveXObject("Msxml2.XMLHTTP");
							} 
							catch (e)
							{
								try
								{
									ret = new ActiveXObject("Microsoft.XMLHTTP");
								} 
								catch (e)
								{
									ret = null;
								}
							}
						}
						return ret;
					},

					resetCrashCounter:function()
					{
						if(typeof(this.resetCrashCounter.x) == 'undefined')	// simulate static : only 1 instance of getHTTPObject !
						{
							this.resetCrashCounter.x = {
												"xmlhttp":new this.getHTTPObject,	// une seule instance 
												"cb":function ()
														{
															var ret;
															if (this.readyState == 4)
															{
																try
																{
																	document.getElementById("idCrashLine").style.visibility = "hidden";
																	ret = this.responseXML;
																}
																catch(e)
																{
																}
																delete this.responseText;
																delete this.responseXML;
																delete this.onreadystatechange;
																
																this.abort();
															}
														}
											};
						}
					
						var url  = "/taskmanager/xmlhttp/resetcrashcounter.x.php?tid=<?php echo $parm['tid']?>";
						this.resetCrashCounter.x.xmlhttp.onreadystatechange = this.resetCrashCounter.x.cb; // ping_stateChange;
						this.resetCrashCounter.x.xmlhttp.open("POST", url, true);
						this.resetCrashCounter.x.xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
						this.resetCrashCounter.x.xmlhttp.send(null);
					}
	};



	function redrawme()
	{
		hauteur =  document.body.clientHeight;
//		document.getElementById("idBox2").style.height = (hauteur-130)+"px";	// div interface graph
//		document.getElementById("txtareaxml").style.height = (hauteur-160)+"px";	// textarea interface xml
	}

	function loaded()
	{
		var o;
		if( (f = document.forms['<?php echo $zGraphicForm?>']) )
		{
			o = document.createElement('input');
			o.setAttribute("name", "__class");
			o.setAttribute("type", "hidden");
			o.setAttribute("value", "<?php echo $classname?>");
			f.appendChild(o);
			
			o = document.createElement('input');
			o.setAttribute("id", "__gact");
			o.setAttribute("name", "__act");
			o.setAttribute("type", "hidden");
			o.setAttribute("value", "");
			f.appendChild(o);
			
			o = document.createElement('input');
			o.setAttribute("id", "__gtname");
			o.setAttribute("name", "__tname");
			o.setAttribute("type", "hidden");
			o.setAttribute("value", "");
			f.appendChild(o);
			
			o = document.createElement('input');
			o.setAttribute("id", "__gtactive");
			o.setAttribute("name", "__tactive");
			o.setAttribute("type", "hidden");
			o.setAttribute("value", "");
			f.appendChild(o);
			
			o = document.createElement('input');
			o.setAttribute("id", "__gxml");
			o.setAttribute("name", "__xml");
			o.setAttribute("type", "hidden");
			o.setAttribute("value", "");
			f.appendChild(o);
			
			o = document.createElement('input');
			o.setAttribute("id", "__gtid");
			o.setAttribute("name", "__tid");
			o.setAttribute("type", "hidden");
			o.setAttribute("value", "<?php echo $taskid?>");
			f.appendChild(o);
		}
		redrawme();
		jsTaskObj.view("PRE_<?php echo $parm['view']?>");
		if( (o = document.getElementById("iddivloading")) )
			o.style.visibility = "hidden";
	}
	
	function setDirty()
	{
		jsTaskObj.SettingsIsDirty = true;
//		document.getElementById("returnButton").style.display = "none";
//	 	document.getElementById("saveButtons").style.display = "";
	}


	</script>
<?php
if(method_exists($ztask, 'printInterfaceJS'))
{
	printf("<!-- _____________  javascript of graphic interface of '%s'   _____________ -->\n", $ztask->getName());
	$ztask->printInterfaceJS();
	printf("<!-- _____________ end javascript of graphic interface of '%s' _____________ -->\n", $ztask->getName());
}
 ?>

</head>

<body id="idBody"  onResize="redrawme();"  onLoad="loaded();" style="background-color:#AAAAAA; overflow:hidden" scroll="no" >
<!--
	<div class="menu" id="presetMenu" style="z-index:50; width:200px;">
<?php
$tasks = array();
$path = GV_RootPath."lib/classes/task";
$havePresets = false;
if($hdir = opendir($path))
{
	$tskin = array();
	$max = 9999;
	$l = strlen("$classname.preset.");
	while( ($max-- > 0) && (($file = readdir($hdir))!==false) )
	{
		if(!is_file($path."/".$file) || mb_strtolower(substr($file, -4)) != ".xml")
			continue;
		if(mb_strtolower(substr($file, 0, $l)) != "$classname.preset.")
			continue;
		$k = substr($file, $l, strlen($file)-$l-4);
		if( $dompreset = DOMDocument::load($path."/".$file) )
		{
			$name = $dompreset->documentElement->getAttribute("name");
			printf("		<a href=\"javascript:void(0)\" id=\"preset_%s\">%s</a>\n", $k, $name); 
			$havePresets = true;
		}
	}
	closedir($hdir);
}
?>
	</div>
-->
<?php
if($ztask)
{
//	printf("class:%s (%s)<br/>\n", $classname, $ztask->getName());
	
	$error = false;
	$loadit = true; 
	$rowbas = null;
	$title = $ztask->getName() . '<span id="taskid">' . ($taskid ? (' (id: '.$taskid.')'):'') . '</span>';
	
	$crashvisibility = "hidden";
	if(isset($rowtask["crashed"]) && (int)$rowtask["crashed"]> 0)
		$crashvisibility = "visible";

?>
		 
	<div style="position:absolute; top:0px; left:5px; right:5px; height:45px; " nowrap>
		<h4 style="padding:2px; text-align:center"><?php echo $title?></h4>
		<form name="__ftask" onsubmit="return(false);">
			<?php echo _('admin::tasks: nom de la tache')?> : <input type="text" name="__tname" style="width:200px" value="<?php echo p4string::MakeString($rowtask["name"], 'htmlprop')?>" onchange="setDirty();" />&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="checkbox" name="__tactive" <?php echo (((int)($rowtask["active"]>0))?"checked":"")?> onchange="setDirty();" />&nbsp;<?php echo _('admin::tasks: lancer au demarrage du scheduler')?>
		</form>		
		<div id="idCrashLine" style="visibility:<?php echo $crashvisibility?>">
			<?php echo _('admin::tasks: Nombre de crashes : ').' '.(int)$rowtask["crashed"]?>
			&nbsp;&nbsp;&nbsp;
			<a href="javascript:void();" onclick="jsTaskObj.resetCrashCounter();return(false);"><?php echo _('admin::tasks: reinitialiser el compteur de crashes')?></a>
		</div>
	</div>

	<div style="position:absolute; top:65px; bottom:30px; left:0px; width:100%;">
		<div id="idBox2" style="position:absolute; top:20px; left:5px; bottom:5px; right:5px; z-index:2; border-top:#ffffff 1px solid; border-left:#ffffff 1px solid; border-bottom:#000000 1px solid; border-right:#000000 1px solid;">
			<div class="divTab">
<?php
		$xmlTabClass = "tabFront";
		$xmlDivDispl = "";
		if(method_exists($ztask, 'printInterfaceHTML'))
		{
			$xmlTabClass = "tabBack";
			$xmlDivDispl =  "display:none; ";
?>
				<div id="linkviewgraph" class="tabFront" onClick="jsTaskObj.view('GRAPHIC');" style="width:100px;">
					<?php echo _('boutton::vue graphique')?>
				</div>
<?php
		}
?>
				<div id="linkviewxml" class="<?php echo $xmlTabClass?>" onClick="jsTaskObj.view('XML');" style="width:100px;">
					<?php echo _('boutton::vue xml')?>
				</div>
			</div>
<?php
		if($hasGraphicMode)
		{
?>
			<!-- _______________    graphic interface '<?php echo $ztask->getName()?>' _______________ -->
			<div id="divGraph" style="position:absolute; top:5px; left:5px; bottom:5px; right:5px; display:auto; overflow:scroll;" >
<?php
		if(method_exists($ztask, 'printInterfaceHTML'))
			$ztask->printInterfaceHTML();
		else
			print("					<form name=\"".$zGraphicForm."\" onsubmit=\"return(false);\"></form>\n");
?>
			</div>
			<!-- _____________  end graphic interface '<?php echo $ztask->getName()?>'   _________________ -->
<?php
		}
?>
			<!-- _____________      xml interface    _____________ -->
			<div id="divXml" style="position:absolute; top:5px; left:5px; bottom:5px; right:5px; <?php echo $xmlDivDispl?>;">
				<form style="position:absolute; top:0px; left:0px; right:4px; bottom:20px;" action="./task2utils.php" onsubmit="return(false);" name="fxml">
					<input type="hidden" name="__act" value="???" />
					<input type="hidden" name="__class" value="<?php echo $classname?>" />
					<input type="hidden" name="__tid" value="<?php echo $taskid?>" />
					<input type="hidden" name="__tname" value="" />
					<input type="hidden" name="__tactive" value="" />
					<TEXTAREA nowrap id="txtareaxml" style="position:absolute; top:0px; left:0px; width:100%; height:100%; white-space:pre;" onchange="setDirty();" name="txtareaxml" ><?php echo p4string::MakeString($rowtask['settings'], "form")?></TEXTAREA>
				</form>
<?php if(0 && $havePresets) { ?>
				<a href="javascript:void();" onclick="setPreset();return(false);" style="position:absolute; bottom:0px; color:#000000;text-decoration:none"><?php echo _('phraseanet:: prereglages')?>...</a>
<?php } ?>
			</div>
			<!-- _____________     xml interface    _____________ -->
		</div>
				
	</div>
					
	<div style="position:absolute; bottom:0px; height:30px; right:5px">			
		<div style="text-align:right; xdisplay:none;" id="saveButtons">
			<form onsubmit="return(false)">
				<input type="button" onclick="jsTaskObj.saveTask(false);" style="width:180px;" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler'), 'htmlprop', '"')?>">
				&nbsp;&nbsp;
				<input type="button" onclick="jsTaskObj.saveTask(true);" style="width:180px;" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider'), 'htmlprop', '"')?>">
			</form>
		</div>
		<form action="./taskmanager.php" method="post" name="__freturn">
<?php
foreach(array("p0", "p1") as $p)
	printf("\t\t\t\t\t<input type=\"hidden\" name=\"$p\" value=\"%s\">\n", p4string::MakeString($parm[$p], "form") );
?>
		</form>
	</div>
<?php
}
else
{
	// $ztask === null
	printf("taskclass '%s' unknown<br/>\n", $classname ? $classname : "?");
}
if($msg != "")
	printf("<script type=\"text/javascript\">alert(\"%s\")</script>", p4string::JSstring($msg));
?>

	<iframe id="hiddenFrame" name="hiddenFrame" src="about:blank" style="position:absolute; bottom:0px; left:0px; width:100px; height:100px; visibility:hidden" ></iframe>

	
	<script type="text/javascript">
	function setPreset()
	{
		document.getElementById("presetMenu").runAsMenu( null );
	}
	function cbME_tasks(action, cbParm, menuelem_id)
	{
		// alert("id=" + cbParm.id + "menuelem_id='" + menuelem_id + "'");
		// alert("cbParm.obj={obj:'" + cbParm.obj + "', id:'" + cbParm.id + "'} ; menuelem_id='" + menuelem_id + "'");
		switch(action)
		{
			case "INIT":
				// last chance to change menu content
				break;
			case "SELECT":
				if(menuelem_id.substr(0, 7) == "preset_")
				{
					url  = "/xmlhttp/gettaskpreset.x.php";
					url += "?preset=" + encodeURIComponent("<?php echo $classname?>.preset."+menuelem_id.substr(7));
			//		alert(url);
					ret = loadXMLDoc(url, null, true);
			//		try
			//		{
						t = ret.getElementsByTagName("preset").item(0).firstChild.nodeValue;
						oldt = document.forms["fxml"].prf.value;
						document.forms["fxml"].prf.value = t;
						if(confirm("Conserver les changements ?"))
							setDirty()
						else
							document.forms["fxml"].prf.value = oldt;
					//	alert(t);
			//		}
			//		catch(e)
			//		{
			//		}
				}
				break;
		}
	}
	</script>
	</body>
</html>

