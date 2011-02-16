<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$request = httpRequest::getInstance();
$parm = $request->get_parms('id');

if(!is_null($parm['id']))
	lazaretFile::stream_thumbnail((int)$parm['id']);
exit;