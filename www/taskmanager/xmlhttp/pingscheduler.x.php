<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					 "lng"
					, "debug"
				);

if(!$parm["lng"])
{
		$parm["lng"] = GV_default_lng ; 
}

if($parm["debug"])
{
	header("Content-Type: text/html; charset=UTF-8");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
	header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");                          // HTTP/1.0
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 FRAMESET//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
<META http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
}
else
{
	header("Content-Type: text/xml; charset=UTF-8");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
	header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");                          // HTTP/1.0
}

$lockdir = GV_RootPath.'tmp/locks/';

$ret = new DOMDocument("1.0", "UTF-8");
$ret->standalone = true;
$ret->preserveWhiteSpace = false;
$root = $ret->appendChild($ret->createElement("result"));
$root->appendChild($ret->createCDATASection( var_export($parm, true) ));

$h = "";  
$dat = date("H:i:s");


$root->setAttribute('time', $dat);


	$conn = connection::getInstance();
	$schedstatus = $schedqdelay = '';
	$sql = "SELECT schedstatus, UNIX_TIMESTAMP()-UNIX_TIMESTAMP(schedqtime) AS schedqdelay, schedpid FROM sitepreff";
	if( ($rs = $conn->query($sql)) )
	{
		if( ($row=$conn->fetch_assoc($rs)) )
		{
			$schedstatus = $row['schedstatus'];
			$schedqdelay = $row['schedqdelay'];
			$schedpid    = $row['schedpid'];
		}
		$conn->free_result($rs);
	}
	$root->setAttribute('status', $schedstatus);
	$root->setAttribute('qdelay', $schedqdelay);

	$schedlock = fopen($lockfile = ($lockdir . 'scheduler.lock'), 'a+');
	if(flock($schedlock, LOCK_SH|LOCK_NB ) != true)
	{
		$root->setAttribute('locked', '1');
	}
	else
	{
		$root->setAttribute('locked', '0');
	}
//	$pid = @file_get_contents($lockdir . '/scheduler.lock');
	if($schedpid > 0)
		$root->setAttribute('pid', $schedpid);
	else
		$root->setAttribute('pid', '');
	
	
	$sql = "SELECT task_id, status, active, crashed, pid, completed FROM task2";
	if( ($rs = $conn->query($sql)) )
	{
		while( ($row=$conn->fetch_assoc($rs)) )
		{
			$task = $root->appendChild($ret->createElement("task"));
			$task->setAttribute('id', $row['task_id']);
			$task->setAttribute('status', $row['status']);
			$task->setAttribute('active', $row['active']);
			$task->setAttribute('crashed', $row['crashed']);
			$task->setAttribute('completed', $row['completed']);
			
			// try to lock one instance of this task
			$tasklock = fopen($lockfile = ($lockdir . '/task_'.$row['task_id'].'.lock'), 'a+');
			
			if(flock($tasklock, LOCK_SH|LOCK_NB ) != true)
			{
				$task->setAttribute('running', '1');
				$task->setAttribute('pid', $row['pid']);
			}
			else
			{
				$task->setAttribute('running', '0');
				$task->setAttribute('pid', '');
			}
				
			fclose($tasklock);
		}
		$conn->free_result($rs);
	}


// $htmlstatus->appendChild($ret->createTextNode($h));
// $htmlstatus->appendChild($ret->createTextNode('z'));
// $pingreturn->appendChild($ret->createTextNode($received));

if($parm["debug"])
	print("<pre>" . htmlentities($ret->saveXML()) . "</pre>");
else
	print($ret->saveXML());
?>