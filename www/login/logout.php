<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";


$request = httpRequest::getInstance();
$parm = $request->get_parms("app");



$conn = connection::getInstance();
if(!$conn)
	die();

p4::logout();	


header("Location: /login/".$parm["app"]."/");
exit;

?>
