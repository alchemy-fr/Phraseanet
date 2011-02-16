<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("tsk", 	// le numero de task
								 "p0",	// id de la base locale
								 "p1", // id de la collection locale
								 "nrc"	// nb de records au debut du vidage
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

$conn = connection::getInstance();
if(!$conn)
{
		phrasea::headers(500);	
}

phrasea::headers();

$maxrec = 10;	// on supprime les records par paquets de 10
$maxrec = 2;	// on supprime les records par paquets de 10
$debug = false;	// en debug on ne del pas les records, on les marque

$sql = "SELECT * FROM bas WHERE base_id='" . $conn->escape_string($parm["p1"])."'";
if($rs = $conn->query($sql))
{
	if($row = $conn->fetch_assoc($rs))
	{
		$connbas = connection::getInstance($row['sbas_id']);
		if($connbas)
		{
			 
			if($debug)
				$sql = "SELECT record_id FROM record WHERE work=0 AND coll_id='".$connbas->escape_string($row["server_coll_id"])."' ORDER BY record_id DESC" ;		
			else
				$sql = "SELECT record_id FROM record WHERE coll_id='".$connbas->escape_string($row["server_coll_id"])."' ORDER BY record_id DESC" ;		
					
			$reclist = "";
			$nrec_total = $nrec_todel = 0;
			if($rs2 = $connbas->query($sql))
			{
				$nrec_total = $conn->num_rows($rs2);
				while(($nrec_todel < $maxrec) && ($row2 = $connbas->fetch_assoc($rs2)))
				{
					$reclist .= ($reclist==""?"":",") . ($lastrid=$row2["record_id"]);
					$nrec_todel++;
				}
				$connbas->free_result($rs2);
			}
			if($nrec_todel > 0)
			{
				if($nrec_total > (int)($parm["nrc"]))
					$parm["nrc"] = $nrec_total;
				$nrec_deleted = (int)($parm["nrc"]) - $nrec_total;
				
				$sql = "SELECT path, file FROM subdef WHERE record_id IN (".$reclist.")" ;
				$tfiles = array();
				if($rs2 = $connbas->query($sql))
				{
					while($row2 = $connbas->fetch_assoc($rs2))
					{
						$f = $row2["path"];
						if(substr($f, -1, 1) != "/" && substr($f, -1, 1) != "\\")
							$f .= "/";
						$f .= $row2["file"];
						$tfiles[] = $f;
					}
					$connbas->free_result($rs2);
				}
				
				// on commence par supprimer des tables pour qu'on ne trouve plus les records
				 
				if($debug)
				{
					$sql = "UPDATE record SET work=1 WHERE record_id IN (".$reclist.")";
					$connbas->query($sql);
				}
				else
				{
					$sql = "DELETE FROM idx WHERE record_id IN (".$reclist.")";
					$connbas->query($sql);
					$sql = "DELETE FROM record WHERE record_id IN (".$reclist.")";
					$connbas->query($sql);
					$sql = "DELETE FROM prop WHERE record_id IN (".$reclist.")";
					$connbas->query($sql);
					$sql = "DELETE FROM subdef WHERE record_id IN (".$reclist.")";
					$connbas->query($sql);
					
					// on supprime les subdefs
					foreach($tfiles as $f)
						@unlink($f);
				}
				
				
				
?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
	</head>
	<body>
	<form action="./emptycoll.php" style="visibility:hidden;display:none;">
		<input type="text" name="tsk" value="<?php echo $parm["tsk"]?>">
		<input type="text" name="p0" value="<?php echo $parm["p0"]?>">
		<input type="text" name="p1" value="<?php echo $parm["p1"]?>">
		<input type="text" name="nrc" value="<?php echo $parm["nrc"]?>">
		<input type="submit">
	</form>

<script type="text/javascript">
	if(parent.init==true)
		parent.window.frames["topframe"].initializebar(<?php echo $nrec_deleted?>, <?php echo $parm["nrc"]?>);
	self.document.forms[0].submit();
</script>
<?php
			}
			else
			{
				if($parm["nrc"]==NULL)
					$parm["nrc"]=1;
?>
<script type="text/javascript">
function retry()
{
	if(parent.init==true)
	{
		parent.window.frames["topframe"].initializebar(<?php echo $parm["nrc"]?>, <?php echo $parm["nrc"]?>);
		parent.window.frames["topframe"].rnbout("<?php echo _('boutton::fermer')?>");
		self.setTimeout("parent.self.close();",3000);
	}
	else
		self.setTimeout("retry();",1000);
}
retry();
</script>
<?php
			}
		}
	}
	$conn->free_result($rs);
}

?>
</body>
</html>
