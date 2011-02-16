<?php
include(dirname(__FILE__).'/../../lib/bootstrap.php');
include(dirname(__FILE__).'/../../lib/geonames.php');
$output = '';

$request = httpRequest::getInstance();
$parm = $request->get_parms('action', 'city');

$action = $parm['action'];

switch($action)
{
	case 'FIND':
		$output = findGeoname($parm['city']);
		break;
}


echo $output;

