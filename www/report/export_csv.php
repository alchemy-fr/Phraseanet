<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$request = httpRequest::getInstance();
$parm = $request->get_parms('name', 'csv');

function trimUltime($str)
{
	$str = preg_replace('/[ \t\r\f]+/', '', $str);
	return $str;
}


$parm['name'] ? $name = '_'.$parm['name'] : $name = "";
$name = preg_replace('/\s+/', '_', $name);
$filename = mb_strtolower('report'.$name.'_'.date('dmY').'.csv');

$content = "";


if($parm['csv'])
{
	$content = trimUltime($parm['csv']);
	export::stream_data($content, $filename, "text/csv");
}
?>