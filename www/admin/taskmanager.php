<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("act","tid",		// si act=DELETETASK, task_id
								"p0",		// afficher uniquement les task de cette base... (non utilise)
								"p1"		// ...et de cette coll (utilise)
							 );
							 

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	$user = user::getInstance($usr_id);
	
	if(!$user->_global_rights['taskmanager'])
	{
		phrasea::headers(403);
	}
}
else{
	phrasea::headers(403);
}

phrasea::headers();
	
	
$tasks = array();
$path = GV_RootPath."lib/classes/task";
if($hdir = opendir($path))
{
	$tskin = array();
	$max = 9999;
	while( ($max-- > 0) && (($file = readdir($hdir))!==false) )
	{
		if(!is_file($path.'/'.$file) || substr($file,0,1)=="." || substr($file, -10) != ".class.php")
			continue;
			
		$classname = 'task_'.substr($file, 0, strlen($file)-10);
		
		try
		{
			$testclass = new $classname();
			if($testclass->interfaceAvalaible())
			{
				$tasks[] = array("class"=>$classname, "name"=>$testclass->getName(), "err"=>null);
			}
		}
		catch(Exception $e)
		{
		}
	}
	closedir($hdir);
}

$conn = connection::getInstance();
if(!$conn)
{
	phrasea::headers(500);
}

?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>

		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
		<style>
			.divTop
			{  
				OVERFLOW: hidden; 
				height:18px; 
			}
			#redbox0 table{
				font-size:10px;
				text-align:center;
			}
			#TAB_CONTENT
			{
				TABLE-LAYOUT: fixed;
				WIDTH: 100%;
				
				position:relative; 
				top:0px;
				left:0px;
				TEXT-ALIGN: center;
				align:center;
				font-size:11px;
			}
			#TAB_CONTENT TR
			{
				height:20px;
				text-align:left;
			}
			#TAB_CONTENT TR.g
			{
				background-color : #808080; 
			}
			#TAB_CONTENT TR.b
			{
				background-color : #888888; 
			}
			#TAB_CONTENT TR.g TD
			{
				border:1px #808080 solid;
			}
			#TAB_CONTENT TR.b TD
			{
				border:1px #888888 solid;
			}
			
			
			* 
			{
				margin:0; 
				padding:0;
			}
</style>
<link rel="stylesheet" href="/include/jslibs/jquery.contextmenu.css" type="text/css" media="screen" />
<link rel="stylesheet" href="/skins/lightbox/ui-lightness/jquery-ui-1.8.4.custom.css" type="text/css" media="screen" />
<script type="text/javascript" src="/include/jslibs/jquery-1.4.4.js"></script>
<script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.6/jquery-ui-1.8.6.js"></script>
<script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.6/i18n/jquery-ui-i18n.js"></script>
<script type="text/javascript" src="/include/jslibs/jquery.contextmenu.js"></script>
<script type="text/javascript">
var newTaskMenu = null;

var allgetID = new Array ;
var total = 0;	



var thbout_timer = null;
var xMousePos = 0;
var yMousePos = 0;

function resized()
{
		$("#redbox0").width($("#tableau_center").width()-4);
		$("#redbox1").width = ($("#tableau_center").width()-4);
}

var T_task = {};
	
var menuTask = [
					{ 
						'Edit':
						{
							onclick:function(menuItem,menu) { doMenuTask($(this), 'edit'); },
							title:'Modifier cette tache'
						}
					},
					{ 
						'Start':
						{
							onclick:function(menuItem,menu) { doMenuTask($(this), 'start'); },
							title:'Demarrer cette tache'
						}
					},
					{ 
						'Stop':
						{
							onclick:function(menuItem,menu) { doMenuTask($(this), 'stop'); },
							title:'Arreter cette tache'
						}
					},
					{ 
						'Fix':
						{
							onclick:function(menuItem,menu) { doMenuTask($(this), 'fix'); },
							title:'Reparer'
						}
					},
					$.contextMenu.separator,
					{ 
						'Delete':
						{
							onclick:function(menuItem,menu) { doMenuTask($(this), 'delete'); },
							title:'Supprimer cette tache'
						}
					},
					$.contextMenu.separator,
					{ 
						'Show log':
						{
							onclick:function(menuItem,menu) { doMenuTask($(this), 'log'); },
							title:'Afficher les logs'
						}
					}
				];

var T_mi_task = {edit:0, start:1, stop:2, fix:3, del:4, log:5};


var menuSched = [
					{ 
						'Start':
						{
							onclick:function(menuItem,menu) { doMenuSched('start'); },
							title:'Demarrer le scheduler'
						}
					},
					{ 
						'Stop':
						{
							onclick:function(menuItem,menu) { doMenuSched('stop'); },
							title:'Arreter le scheduler'
						}
					},
					{ 
						'Fix':
						{
							onclick:function(menuItem,menu) { doMenuSched('fix'); },
							title:'Reparer'
						}
					},
					$.contextMenu.separator,
					{ 
						'Show log':
						{
							onclick:function(menuItem,menu) { doMenuSched('log'); },
							title:'Afficher les logs'
						}
					},
					{ 
						'Preferences':
						{
							onclick:function(menuItem,menu) { doMenuSched('preferences'); },
							title:'Scheduler preferences'
						}
					}
				];

var T_mi_sched = {start:0, stop:1, fix:2, log:4, preferences:5};
				
function newTask(tclass)
{
	document.forms["task"].target = "";
	document.forms["task"].act.value = "NEWTASK";
	document.forms["task"].tcl.value = tclass;
	document.forms["task"].submit();
}

function doMenuSched(act)
{
	switch(act)
	{
		case "start":
			lauchScheduler();
			break;
		case "preferences":
			preferencesScheduler();
			break;
		case "stop":
			setSchedStatus('tostop');
			break;
		case "fix":
			switch(T_task["SCHED"])
			{
				case "started_0":
				case "stopping_0":
				case "tostop_0":
					setSchedStatus('stopped');
					break;
				case "stopped_1":
				case "stopping_1":
				case "tostop_1":
					setSchedStatus('started');
					break;
			}
			break;
		case "log":
			window.open("/taskmanager/showlog.php?fil=<?php echo urlencode('scheduler.log')?>", "SCHEDLOG");
			break;
	}
}


function doMenuTask(context, act)
{
	var tid = context[0].id.substr(3);
	switch(act)
	{
		case "edit":
			editTask(tid);
			break;		
		case "start":
			setTaskStatus(tid, 'tostart');
			break;
		case "stop":
			setTaskStatus(tid, 'tostop');
			break;
		case "fix":
			switch(T_task[tid])
			{
				case "started_0":
				case "starting_0":
				case "stopping_0":
				case "tostart_0":
				case "tostop_0":
				case "manual_0":
					setTaskStatus(tid, 'stopped');
					break;

				case "stopped_1":
				case "starting_1":
				case "stopping_1":
				case "tostart_1":
				case "tostop_1":
			//	case "manual_1":
			//	case "torestart_1":
					setTaskStatus(tid, 'started');
					break;
			}
			break;
		case 'delete':
			switch(T_task[tid])
			{
				case "stopped_0":
				case "started_0":
				case "starting_0":
				case "stopping_0":
				case "tostart_0":
				case "tostop_0":
				case "manual_0":
				case "torestart_0":
					if(confirm("<?php echo p4string::MakeString(_('admin::tasks: supprimer la tache ?'), 'js', '"')?>"))
					{
						document.forms["taskManager"].target = "";
						document.forms["taskManager"].act.value = "DELETETASK";
						document.forms["taskManager"].tid.value = tid;
						document.forms["taskManager"].submit();
					}
					break;
			}
			break;
		case "log":
			window.open("/taskmanager/showlog.php?fil=<?php echo urlencode('task_t_')?>"+tid+"%2Elog", "TASKLOG_"+tid);
			break;
	}
}

function preferencesScheduler()
{
	var buttons = {
			'<?php echo _('Fermer')?>':function(){$('#scheduler-preferences').dialog('close').dialog('destroy')},
			'<?php echo _('Renouveller')?>':function(){renew_scheduler_key();}
		};
	$('#scheduler-preferences').dialog({
		width:400,
		height:200,
		modal:true,
		resizable:false,
		draggable:false,
		buttons:buttons
	});
}

function renew_scheduler_key()
{
	var datas = {action:'SCHEDULERKEY', renew:'1'};
	$.post("/admin/adminFeedback.php" 
			, datas
			, function(data){
				$('#scheduler_key').val(data);
			return;
		});
}

$(document).ready(function(){
	resized();

	$(this).bind('resize',function(){resized();});
	
	var allgetID = new Array ;
	var total = 0;	
	
	
	
	var thbout_timer = null;
	var xMousePos = 0;
	var yMousePos = 0;
	
	var menuNewTask = [
	<?php
	// fill the 'new task' menu
	$ntasks = count($tasks);
	foreach($tasks as $t)
	{
		printf("			{\n");
		printf("				'%s':\n", p4string::MakeString($t["name"], 'js'));
		printf("				{\n");
		printf("					disabled:%s,\n", $t["err"] ? 'true':'false');
		printf("					onclick:function(menuItem, menu) { newTask('%s'); },\n", p4string::MakeString($t["class"], 'js'));
		printf("					title:'%s'\n", p4string::MakeString($t["name"], 'js'));
		printf("				}\n");
		printf("			}%s\n", --$ntasks ? ',':'');
	}
	?>
	];


		$('#newTaskButton').contextMenu(
											menuNewTask,
											{
												theme:'vista'
											}
										);
		$('#TAB_CONTENT .task').contextMenu(
											menuTask,
											{
												theme:'vista',
												beforeShow:function()
												{
													var trid = $(this)[0].target.id;
													var tid = trid.substr(3);
													if(typeof(T_task[tid])=="undefined")
														return(false); 
													
													$("#TAB_CONTENT tr").removeClass('b');
													$("#"+trid).addClass('b');
													
													switch(T_task[tid])
													{
														case "stopped_0":	// normal
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.stop+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.start+')').removeClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.fix+')').addClass("context-menu-item-disabled");
	//														setTaskStatus(tid, 'tostart');
															break;
														case "started_0":
														case "starting_0":
														case "stopping_0":
														case "tostop_0":
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.stop+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.start+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.fix+')').removeClass("context-menu-item-disabled");
															break;
															break;
														case "torestart_0":
															break;

														case "started_1":
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.start+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.stop+')').removeClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.fix+')').addClass("context-menu-item-disabled");
															break;
														case "tostart_0":
														case "stopped_1":
														case "starting_1":
														case "stopping_1":
														case "tostart_1":
														case "tostop_1":
														case "manual_0":
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.stop+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.start+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.fix+')').removeClass("context-menu-item-disabled");
															break;
														case "manual_1":
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.start+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.stop+')').removeClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_task.fix+')').addClass("context-menu-item-disabled");
															break;
														case "torestart_1":
															break;
													}
												}
											}
										);
										
		$('#TR_SCHED').contextMenu(
											menuSched,
											{
												theme:'vista',
												beforeShow:function()
												{
													$("#TAB_CONTENT tr").removeClass('b');
													$("#TR_SCHED").addClass('b');
													
													switch(T_task["SCHED"])
													{
														case "stopped_0":	// normal
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.stop+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.start+')').removeClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.fix+')').addClass("context-menu-item-disabled");
															break;
														case "stopping_0":
														case "started_0":
														case "tostop_0":
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.stop+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.start+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.fix+')').removeClass("context-menu-item-disabled");
															break;
															break;
														case "stopped_1":
														case "stopping_1":
														case "tostop_1":
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.stop+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.start+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.fix+')').removeClass("context-menu-item-disabled");
															break;
														case "started_1":
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.start+')').addClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.stop+')').removeClass("context-menu-item-disabled");
															$(this.menu).find('.context-menu-item:eq('+T_mi_sched.fix+')').addClass("context-menu-item-disabled");
															break;
													}
												}
											}
										);
										
	    self.setTimeout("pingScheduler();", 100);
})
		</script>
	</head>
	<body>   


<?php


 
if($parm["act"]=="DELETETASK")
{
	$sql = "DELETE FROM task2 WHERE task_id='" . $conn->escape_string($parm["tid"])."'";
	$conn->query($sql);
	@unlink('./locks/task_'.$parm["tid"].'.lock');
}


$sql ="SELECT task2.* FROM task2 ORDER BY task_id ASC";

?>
	<iframe id="zsched" src="about:blank" style="position:absolute; top:0px; left:0px; width:50px; height:50px; visibility:hidden"></iframe>

	<h4 style="padding:2px; text-align:center"><?php echo _('admin::tasks: planificateur de taches')?> - <span id="pingTime"></span></h4>

		<form method="post" name="taskManager" action="./taskmanager.php" target="???" onsubmit="return(false);" >
			<input type="hidden" name="act" value="" />
			<input type="hidden" name="tid" value="" />
			<input type="hidden" name="att" value="" />
			<input type="hidden" name="p0" value="<?php echo $parm["p0"]?>" />
			<input type="hidden" name="p1" value="<?php echo $parm["p1"]?>" />
		</form>

	<div style="position:absolute;border:1px solid #ffffff; left:5px; right:5px; top:50px; bottom:30px;" >
		<div id="redbox0" style="width:100%; height:30px;">
			<table cellpadding="0" cellSpacing="0" style="width:100%; ">
				<thead>
					<tr>
						<th style="width:40px;">ID</th>
						<th style="width:80px;"><?php echo _('admin::tasks: statut de la tache')?></th>
						<th style="width:60px;"><?php echo _('admin::tasks: process_id de la tache')?></th>
						<th style="width:120px;"><?php echo _('admin::tasks: etat de progression de la tache')?></th>
						<th style="width:auto;"><?php echo _('admin::tasks: nom de la tache')?></th>
					</tr>
				</thead>
			</table>
		</div>
		<DIV id="tableau_center" style="position:absolute; top:30px; left:0px; right:0px; xwidth:100%; bottom:0px; overflow-y:scroll; overflow-x:hidden;" >
			<div id="redbox1" style="position:absolute; top:0px; left:0px; width:100%;">
												 			 
			<TABLE id="TAB_CONTENT" cellpadding="0" cellSpacing="0" style="width:100%;">

				<tr id="TR_SCHED" class="sched g">
					<td style="width:40px;">&nbsp</td>
					
					<td style="width:80px; text-align:center" id="STATUS_SCHED">
						<img id="STATUSIMG_SCHED" style="display:none;" />
						<img id='WARNING_SCHED' style='display:none' src="/skins/icons/alert.png" alt="" />
					</td>

					<td id="PID_SCHED" style="text-align:center;width:60px;">&nbsp;</td>
					
					<td style="width:120px;">
						&nbsp;
					</td>

					<td style="width:auto; font-weight:900" class="taskname">Scheduler</td>
				</tr>
<?php
	$i = 0;
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$tid = $row["task_id"];
?>			
				<tr id="TR_<?php echo $tid?>" class="task g">
					<td style="width:60px; text-align:center; font-weight:900"><?php echo $tid?></td>
					
					<td style="width:80px; text-align:center" id="STATUS_<?php echo $tid?>">
						<img id="STATUSIMG_<?php echo $tid?>" style="display:none;" />
						<img id='WARNING_<?php echo $tid?>' style='display:none' src="/skins/icons/alert.png" alt="" />
					</td>

					<td id="PID_<?php echo $tid?>" style="text-align:center;width:60px;">&nbsp;</td>
					
					<td style="width:120px;">
						<div id="COMPBOX_<?php echo $tid?>" style="position:relative; top:1px; left:5px; width:110px; height:5px; background-color:#787878; visibility:hidden">
							<div id="COMP_<?php echo $tid?>" style="position:absolute; top:1px; left:0px; width:0%; height:3px; background-color:#FFFF80">
							</div>
						</div>
					</td>

					<td style="width:auto" class="taskname"><?php echo p4string::MakeString($row["name"])?> [<?php echo p4string::MakeString($row["class"])?>]</td>
				</tr>
<?php
			$i = 1-$i;
		}
	}
	$conn->free_result($rs);
?>
			</TABLE>
			</div>		
		</DIV>	
	</div>

	<div style="position:absolute; left:5px; bottom:5px;" >
		<span id="newTaskButton" style="cursor:pointer"><?php echo _('admin::tasks: Nouvelle tache')?></span>
	</div>				
<?php

?>
 <script type="text/javascript">

function editTask(tid)
{
	document.forms["task"].target = "";
	document.forms["task"].act.value = "EDITTASK";
	document.forms["task"].tid.value = tid;
	document.forms["task"].submit();
}

function setTaskStatus(tid, status)
{
	var url  = "/taskmanager/xmlhttp/settaskstatus.x.php";
	url += "?tid=" + encodeURIComponent(tid);
	url += "&status=" + encodeURIComponent(status);
	
	$.ajax({
		url: url,
		dataType:'xml',
		success: function(ret)
		{

		}
	});
}


function setSchedStatus(status)
{
	var url  = "/taskmanager/xmlhttp/setschedstatus.x.php";
	url += "?status=" + encodeURIComponent(status);

	$.ajax({
		url: url,
		dataType:'xml',
		success: function(ret)
		{

		}
	});
}


function deleteTask(tid)
{
	if(confirm("<?php echo p4string::MakeString(_('admin::tasks: supprimer la tache ?'), 'js', '"')?>"))
	{
		document.forms["taskManager"].target = "";
		document.forms["taskManager"].act.value = "DELETETASK";
		document.forms["taskManager"].tid.value = tid;
		document.forms["taskManager"].submit();
	}
}

function pingScheduler()
{
	$.ajax({
		url: '/taskmanager/xmlhttp/pingscheduler.x.php',
		dataType:'xml',
		success: function(ret)
		{
			

			var ping='', pingTime='', newStr='', statusping='', running='', qdelay;
	
			status = ret.documentElement.getAttribute("status"); // +'_'+ret.documentElement.getAttribute("ping");
			pingTime = ret.documentElement.getAttribute("time");
			locked = ret.documentElement.getAttribute("locked");
			qdelay = ret.documentElement.getAttribute("qdelay");
			schedpid = ret.documentElement.getAttribute("pid");
					
			T_task['SCHED'] = status+'_'+locked;
	
			$("#PID_SCHED").empty().append(schedpid);
			switch(status+'_'+locked)
			{
				case "stopped_0":	// normal
	
					$("#STATUSIMG_SCHED").attr("src", "/skins/icons/stop-alt.png").show();
					break;
				case "stopping_0":	// not normal (scheduler killed ?)
				case "started_0":
				case "tostop_0":
					$("#STATUSIMG_SCHED"+id).attr("src", "/skins/icons/alert.png").show();
					break;
				case "stopped_1":	// not normal (no database ?)
	
					$("#STATUSIMG_SCHED"+id).attr("src", "/skins/icons/alert.png").show();
	
					break;
				case "stopping_1":	// normal, wait
				case "tostop_1":
	
					$("#STATUSIMG_SCHED").attr("src", "/skins/icons/indicator.gif").show();
	
					break
				case "started_1":
	
					$("#STATUSIMG_SCHED").attr("src", "/skins/icons/up.png").show();
	
					break;
			}
	
	
			$("#pingTime").empty().append(pingTime);
	
			
			var t = ret.getElementsByTagName('task');
			for(var i=0; i<t.length; i++)
			{
				var id = t.item(i).getAttribute('id');
				var status  = t.item(i).getAttribute('status') + "_" + t.item(i).getAttribute('running');
				var active  = t.item(i).getAttribute('active');
				var pid     = t.item(i).getAttribute('pid');
				var completed = 0 | (t.item(i).getAttribute('completed'));
				var crashed   = 0 | (t.item(i).getAttribute('crashed'));
	
				T_task[id] = status;
				switch(status)
				{
					case "stopped_0":	// normal
						$("#STATUSIMG_"+id).attr("src", "/skins/icons/stop-alt.png").show();
						break;
					case "started_0":
					case "stopping_0":
					case "manual_0":
					case "tostop_0":
					case "stopped_1":
					case "starting_1":
					case "tostart_1":
						$("#STATUSIMG_"+id).attr("src", "/skins/icons/alert.png").show();
						break;
					case "tostart_0":
					case "starting_0":
					case "stopping_1":
					case "tostop_1":
					case "torestart_0":
					case "torestart_1":
						$("#STATUSIMG_"+id).attr("src", "/skins/icons/indicator.gif").show();
						break;
					case "manual_1":
						$("#STATUSIMG_"+id).attr("src", "/skins/icons/public.png").show();
						break;
					case "started_1":
						$("#STATUSIMG_"+id).attr("src", "/skins/icons/up.png").show();
						break;
				}
						
						
						
				if(o = document.getElementById("COMP_"+id))
				{
					//if(completed >=0)
					//	o.innerHTML = completed + "%";
					//else
					//	o.innerHTML = '&nbsp;';
					if(completed >=0)
					{
						document.getElementById("COMP_"+id).style.width = completed + "%";
						document.getElementById("COMPBOX_"+id).style.visibility = "visible";
					}
					else
					{
						document.getElementById("COMPBOX_"+id).style.visibility = "hidden";
						document.getElementById("COMP_"+id).style.width = '0px';
					}
				}
				if(o = document.getElementById("PID_"+id))
				{
					if(pid != "")
						o.innerHTML = pid;
					else
						o.innerHTML = '&nbsp;';
				}
				if(o = document.getElementById("WARNING_"+id))
				{
					if(crashed > 0)
					{
						var str = "<?php echo p4string::MakeString(_('admin::tasks: Nombre de crashes : '), 'js', '"');?>"+crashed;
						o.setAttribute('alt', str);
					}
					o.style.display = crashed > 0 ? '':'none';
				}
			}
				
			self.setTimeout("pingScheduler();", 3000);
		}
	});
}

function lauchScheduler()
{
	url  = "./runscheduler.php";
	document.getElementById("zsched").src = url;
	self.setTimeout('document.getElementById("zsched").src="about:blank";', 2000);
}

</script>

<div id="scheduler-preferences" style="display:none;" title="<?php echo _('Preferences du scheduler');?>">

	<div style="margin:5px 0;"><span><?php echo _('Cette URL vous permet de controler le sheduler depuis un manager comme cron')?></span></div>
	<div style="margin:5px 0;"><input id="scheduler_key" style="width:100%;" type="text" readonly="readonly" value="<?php echo GV_ServerName?>admin/runscheduler.php?key=<?php echo phrasea::scheduler_key();?>" /></div>
</div>

<form method="post"  name="task" action="./task2.php" target="???" onsubmit="return(false);">
	<input type="hidden" name="p0" value="<?php echo $parm["p0"]?>" />
	<input type="hidden" name="p1" value="<?php echo $parm["p1"]?>" />
	<input type="hidden" name="act" value="???" />
	<input type="hidden" name="tid" value="" />
	<input type="hidden" name="tcl" value="" />
	<input type="hidden" name="view" value="GRAPHIC" />
</form>


</body>
</html>
