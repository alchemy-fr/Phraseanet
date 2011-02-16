<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";


//SPECIAL ZINO
ini_set('display_errors','off');
ini_set('display_startup_errors','off');
ini_set('log_errors','off');
//SPECIAL ZINO

// -------------- api_utils will set vars :

//$request = httpRequest::getInstance();
//$parm = $request->get_parms('p', 'ses_id', 'usr_id', 'debug');
// $sxParms = simplexml_load_string($parm['p']);
// $dom : domdocument for result
// $result : documentElement for result
require(GV_RootPath.'www/api/api_utils.php');

?>