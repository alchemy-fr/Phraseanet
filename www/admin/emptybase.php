<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms('sbid', 'collid');

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	if(!$session->admin)
	{
		phrasea::headers(403);	
	}
}
else
{
	phrasea::headers(403);	
}

$conn = connection::getInstance();
if(!$conn)
{
	phrasea::headers(500);
}	

set_time_limit(0);
session_write_close();
ignore_user_abort(true);

$debug = false;	// en debug on ne del pas les records, on les marque

$connbas = connection::getInstance($parm['sbid']);
if($connbas && $connbas->isok())
{
	// on liste un paquet de records et de subdefs a supprimer
	if($parm['collid'] === null)
		$sql = 'SELECT record.record_id, path, file FROM record LEFT JOIN subdef USING(record_id) ORDER BY record_id DESC' ;
	else
		$sql = 'SELECT record.record_id, path, file FROM record LEFT JOIN subdef USING(record_id) WHERE coll_id=\''.$connbas->escape_string($parm['collid']).'\' ORDER BY record_id DESC' ;
	
	if($rs = $connbas->query($sql))
	{
		$trec = array();
		while($row = $connbas->fetch_assoc($rs))
		{
			$rid = $row['record_id'];
			$f = $row["path"];
			if(substr($f, -1, 1) != "/" && substr($f, -1, 1) != "\\")
				$f .= "/";
			$f .= $row["file"];
			if(!array_key_exists($rid, $trec))
				$trec[$rid] = array('rid'=>$rid, 'tfiles'=>array());
			$trec[$rid]['tfiles'][] = $f;
			if(count($trec) == 11)
				delrecs($trec);
		}
		$connbas->free_result($rs);
		delrecs($trec, true);
	}
}

return(p4string::jsonencode(null));
	
function delrecs(&$trec, $all=false)
{
  global $debug;
  global $connbas;
  
	if(!$all)
		$last = array_pop($trec);
	$reclist = '';
	$tfiles = array();
	foreach($trec as $rec)
	{
		$reclist .= ($reclist==''?'':',') . $rec['rid'];
		$tfiles = array_merge($tfiles, $rec['tfiles']);
	}
	if($reclist)
	{
		if($debug)
		{
			//printf("%s : %s\n", __LINE__, $reclist);
			$sql = "UPDATE record SET work=1 WHERE record_id IN (".$reclist.")";
			// printf("%s \n", $sql);
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
			$sql = "DELETE FROM thit WHERE record_id IN (".$reclist.")";
			$connbas->query($sql);
			// on supprime les subdefs
			foreach($tfiles as $f)
				@unlink($f);
		}
	}
	if(!$all)
		$trec = array($last['rid']=>$last);
}

?>
