<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"class"
					, "usr"
					, "debug"
				);

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
				
				
				
$ret = new DOMDocument("1.0", "UTF-8");
$ret->standalone = true;
$ret->preserveWhiteSpace = false;
$root = $ret->appendChild($ret->createElement("result"));
$root->appendChild($ret->createCDATASection( var_export($parm, true) ));
if($parm["class"] !== null)
{
	$conn = connection::getInstance();
	$new_taskid = $conn->getId("TASK");
	
	$dom = new DOMDocument('1.0', 'UTF-8');
	$dom->standalone = true;
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;
	
	$dom_task = $dom->createElement('tasksettings');
	$dom->appendChild($dom_task);
			
	$xml = $dom->saveXML();
	
	
	$sql = "INSERT INTO task2 (task_id, usr_id_owner, name, active, class, settings) VALUES (".$conn->escape_string($new_taskid).", '".$conn->escape_string($parm['usr'])."', '".$conn->escape_string($parm["class"].'_'.$new_taskid)."', '0', '".$conn->escape_string($parm["class"])."', '".$conn->escape_string($xml)."')" ;
	if($parm["debug"])
		printf("sql=%s\n", htmlentities($sql));
	if($rs = $conn->query($sql))
	{
		$root->setAttribute("tid", $new_taskid);
	}
}
if($parm["debug"])
	print("<pre>" . htmlentities($ret->saveXML()) . "</pre>");
else
	print($ret->saveXML());
?>