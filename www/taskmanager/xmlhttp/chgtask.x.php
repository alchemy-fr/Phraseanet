<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					'act'		// NEW or SAVE
					, 'tid'		// set if act==SAVE
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
$root->setAttribute("saved", "0");
$root->appendChild($ret->createCDATASection( var_export($parm, true) ));

if($parm["tid"] !== null)
{
	$conn = connection::getInstance();
	if($conn)
	{
		$ztask = null;
		$sql = "SELECT * FROM task2 WHERE task_id='" . $conn->escape_string($parm["tid"])."'";
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$root->setAttribute("crashed", $row["crashed"]);
				$task = null;
				$classname = $row["class"];
//				if(!class_exists($classname) && file_exists("../tasks/$classname.class.php"))
//				{
//					require_once("../tasks/$classname.class.php");
					if($parm["debug"])
						printf("class $classname loaded\n");
//					if(class_exists($classname))
						$ztask = new $classname;
//				}
			}
			$conn->free_result($rs);
		}
		if($ztask)
		{
			if($ztask->saveChanges($conn, $parm["tid"], $row))	// 'saveChanges(..)' doit retourner true si saved ok
				$root->setAttribute("saved", "1");
		}
	}
}

if($parm["debug"])
	print("<pre>" . htmlentities($ret->saveXML()) . "</pre>");
else
	print($ret->saveXML());

?>