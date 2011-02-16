<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					  "tid"
					, "active"
					, "status"
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

if($parm["tid"] !== null)
{
	$conn = connection::getInstance();
	$sql = '';
	if($parm["active"] !== null)
		$sql .= ($sql ? ', ':'') . "active='" . $conn->escape_string($parm["active"])."'";
	if($parm["status"] !== null)
		$sql .= ($sql ? ', ':'') . "status='" . $conn->escape_string($parm["status"]) . "'";
	
	if($sql)
	{
		$sql = "UPDATE task2 SET $sql WHERE task_id='" . $conn->escape_string($parm["tid"]) ."'";
		$conn->query($sql);
	}
		
}

?>