<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					  '__act'
					, '__class'	// task class
					, '__tname'
					, '__tactive'
					, '__xml'
					, '__tid'
					, 'txtareaxml'
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

$classname = $parm['__class'];
?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
			<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
<?php
$ztask = null;
		$ztask = new $classname();
		switch($parm['__act'])
		{
			case 'FORM2XML':
				if(method_exists($ztask, 'printInterfaceHTML'))
				{
					if(method_exists($ztask, 'graphic2xml'))
					{
						$xml = p4string::MakeString($ztask->graphic2xml($parm['__xml']), "js");
					}
					else
						$xml = p4string::MakeString($parm['__xml'], "js");
?>
		<script type="text/javascript">
			var d = parent.document;
			parent.jsTaskObj.oldXML = d.getElementById('txtareaxml').value = "<?php echo $xml?>";
			d.getElementById('divGraph').style.display = "none";
			d.getElementById('divXml').style.display = "";		
			d.getElementById('linkviewxml').className = "tabFront";		
			d.getElementById('linkviewgraph').className = "tabBack";		
			parent.jsTaskObj.currentView = "XML";
		</script>
<?php
				}
				break;

			case 'XML2FORM':
				if(method_exists($ztask, 'printInterfaceHTML'))
				{
					if( (simplexml_load_string($parm['txtareaxml'])))
					{
						if(method_exists($ztask, 'xml2graphic'))
						{
							if( ($msg = ($ztask->xml2graphic($parm['txtareaxml'], "parent.document.forms['".$ztask->getGraphicForm()."']"))) == "" )
							{
?>
		<script type="text/javascript">
			var d = parent.document;
			d.getElementById('divGraph').style.display = "";		
			d.getElementById('divXml').style.display = "none";	
			d.getElementById('linkviewxml').className = "tabBack";		
			d.getElementById('linkviewgraph').className = "tabFront";		
			parent.jsTaskObj.currentView = "GRAPHIC";
		</script>
<?php
							}
							else
							{
?>
		<script type="text/javascript">
			alert("<?php echo p4string::MakeString($msg, 'js', '"')?>");
		</script>
<?php
							}
						}
						else
						{
?>
		<script type="text/javascript">
			var d = parent.document;
			d.getElementById('divGraph').style.display = "";		
			d.getElementById('divXml').style.display = "none";	
			d.getElementById('linkviewxml').className = "tabBack";		
			d.getElementById('linkviewgraph').className = "tabFront";		
			parent.jsTaskObj.currentView = "GRAPHIC";
		</script>
<?php
						}
					}
					else
					{
?>
		<script type="text/javascript">
			if(confirm("<?php echo p4string::MakeString(_('admin::tasks: xml invalide, restaurer la version precedente ?'), 'js', '"') // xml invalide, restaurer la v. prec. ? ?>"))
				parent.document.forms['fxml'].txtareaxml.value = parent.jsTaskObj.oldXML;
		</script>
<?php
					}
				}
				break;
				
			case 'SAVE_GRAPHIC':
				$parm['txtareaxml'] = $ztask->graphic2xml($parm['__xml']);
				
				// printf("alert(\"%s\");", $parm['txtareaxml']);
				
			case 'SAVE_XML':
				if( (simplexml_load_string($parm['txtareaxml'])))
				{
					if(method_exists($ztask, 'checkXML'))
					{
						if( $ztask->checkXML($parm['txtareaxml']) != '')
						{
							return;
						}
					}
					
					$conn = connection::getInstance();
					
					if(!$parm['__tid'])
					{
						$tid = $conn->getID('TASK');
						$sql = 'INSERT INTO task2 (task_id, usr_id_owner, status, crashed, active, name, last_exec_time, class, settings)
						VALUES ('.$tid.', 0, \'stopped\', 0, "'.$conn->escape_string($parm['__tactive']).'",
						\''.$conn->escape_string($parm['__tname']).'\',
						\'0000/00/00 00:00:00\',
						\''.$conn->escape_string($parm['__class']).'\',
						\''.$conn->escape_string($parm['txtareaxml']).'\')';
						$conn->query($sql);
					}
					else
					{
						$tid = $parm['__tid'];
						$sql  = 'UPDATE task2 SET settings=\''.$conn->escape_string($parm['txtareaxml']). '\'';
						$sql .= ', name=\''.$conn->escape_string($parm['__tname']).'\'';
						$sql .= ', active="'.$conn->escape_string($parm['__tactive']).'"';
						$sql .= ' WHERE task_id="' . $conn->escape_string($tid).'"';
						$conn->query($sql);
					}
?>
		<script type="text/javascript">
			parent.document.getElementById("taskid").innerHTML = "id : <?php echo $tid?>";
			if(o=parent.document.getElementById("__gtid"))
				o.value = "<?php echo $tid?>";
			parent.document.forms['fxml'].__tid.value = "<?php echo $tid?>";
//			parent.document.getElementById("saveButtons").style.display = "none";
//			parent.document.getElementById("returnButton").style.display = "";
		</script>
<?php
				}
				else
				{
?>
		<script type="text/javascript">
			if(confirm("<?php echo p4string::MakeString(_('admin::tasks: xml invalide, restaurer la version precedente ?'), 'js', '"')?>"))
				parent.document.forms['fxml'].txtareaxml.value = parent.jsTaskObj.oldXML;
		</script>
<?php
				}
				break;
				
			case 'CANCEL_GRAPHIC':
				// oldxml dans parm['__xml']
				break;
				
			case 'CANCEL_XML':
				break;
		}
//	}
//}
?>
	</head>
	<body>
	</body>
</html>


