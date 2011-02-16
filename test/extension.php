<?php
showtime();
require(dirname(__FILE__)."/../lib/bootstrap.php");
set_time_limit(300);


define('USR_ID', 4);

$array_funcs = array(
	'phrasea_conn',       
	'phrasea_create_session',
	'phrasea_open_session',  
	'phrasea_save_session',  
	'phrasea_clear_cache',   
	'phrasea_register_base', 
	'phrasea_close_session', 
	'phrasea_query2',        
	'phrasea_fetch_results', 
	'phrasea_subdefs',       
	'phrasea_emptyw',        
	'phrasea_status',        
	'phrasea_xmlcaption',    
	'phrasea_setxmlcaption', 
	'phrasea_isgrp',         
	'phrasea_grpparent',     
	'phrasea_grpforselection',
	'phrasea_grpchild',      
	'phrasea_setstatus',     
	'phrasea_list_bases',
);

$tests = count($array_funcs) + 16;

$t = new lime_test($tests);

$t->comment('Functions provided by php-phrasea');

foreach($array_funcs as $func)
	$t->is(function_exists($func),true,showtime().'function '.$func);
	
	
$t->isnt(phrasea_list_bases(),false,showtime().'Test phrasea_list_bases not false');
$t->isnt(phrasea_list_bases(),null,showtime().'Test phrasea_list_bases not null');

$t->comment('Session');

$sessid = phrasea_create_session(USR_ID);
$t->isnt($sessid,null,showtime().'session_id value must not be null');
$t->isnt($sessid,false,showtime().'session_id value must not be false');
$t->cmp_ok($sessid,'>',0,showtime().'session_id value must be greater than 0');


$t->comment('Reouverture de session');

$ph_session = phrasea_open_session($sessid, USR_ID);

$t->is($sessid,$ph_session['session_id'],showtime().'phrasea_open_session must return same session_id');

$t->comment('Register base');


$rmax = 99999;
$basok = 0;

$lb = phrasea_list_bases();

$first = true;

foreach($lb["bases"] as $base)
{
	if($base["online"] == true)
	{
		$connbas = connection::getInstance($base['sbas_id']);

		if($connbas && $connbas->isok())
		{				
			foreach($base["collections"] as $coll_id=>$coll)
			{
				if($rmax-- > 0)
				{
 
					$rb = phrasea_register_base($sessid, $coll['base_id'], "", "");
					
					if($first)
						$t->is($rb,true,showtime().'register base on base '.$coll['base_id'].' must be true');
					if($rb)
					{
						$sql = sprintf("REPLACE INTO collusr (site, usr_id, coll_id, mask_and, mask_xor) VALUES ('%s', %s, %s, 0, 0)",
												mysql_escape_string(GV_sit),
												USR_ID,
												$coll['coll_id']
										);
						$connbas->query($sql);

						$basok++;
					}
					$first = false;
				}
			}
		}
	}
}

$rb = phrasea_register_base($sessid, -25, "", "");

$t->is($rb,false,showtime().'register base on inexistant base -25 must be false');


$ph_session = phrasea_open_session($sessid, USR_ID);

$t->cmp_ok(count($ph_session['bases']),'>',0,showtime().'Phrasea must return registered bases');



if($basok == 0)
{
	echo ("pas de base/coll ok, fin\n");
	phrasea_close_session($sessid);
	die();
}

$ret = phrasea_clear_cache($sessid);

$t->is($ret,true,showtime().'phrasea clear cache');


$result = ""; 

$tbases = array();
foreach($ph_session["bases"] as $kphbase=>$phbase)
{
	$tcoll = array();
	foreach($phbase["collections"] as $coll)
	{
		$tcoll[] = 0+$coll["base_id"];	// le tableau de colls doit contenir des int
	}
	if(sizeof($tcoll) > 0)	// au - une coll de la base etait cochee
	{
		$kbase = "S" . $phbase["sbas_id"];
		$tbases[$kbase] = array();
		$tbases[$kbase]["sbas_id"] = $phbase["sbas_id"];
		$tbases[$kbase]["searchcoll"] = $tcoll;
		$tbases[$kbase]["mask_xor"] = $tbases[$kbase]["mask_and"] = 0;

		$qp = new qparser();
		$treeq = $qp->parsequery('last');
		$arrayq = $qp->makequery($treeq);

		$tbases[$kbase]["arrayq"] = $arrayq;
	}
	break;
}

	
	
$nbanswers = 0;
foreach($tbases as $kb=>$base)
{
	$ret = null;
	$tbases[$kb]["results"] = NULL;

	set_time_limit(120);
	
	$ret = phrasea_query2($ph_session["session_id"],  $base["sbas_id"], $base["searchcoll"], $base["arrayq"],GV_sit, USR_ID, FALSE , PHRASEA_MULTIDOC_DOCONLY);

	$t->is(is_array($ret),true,showtime().'Les reponses sont renvoyes');
	
	if($ret)
	{
		$tbases[$kb]["results"] = $ret;
		
		$nbanswers += $tbases[$kb]["results"]["nbanswers"];
	}
	break;
}


$results = phrasea_fetch_results($ph_session["session_id"], 1, 20, true, '[[em]]', '[[/em]]');
$rs = array();
if(isset($results['results']) && is_array($results['results']))
{
	$rs = $results['results'];
}

if($nbanswers > 0)
{
	$t->is(is_array($ret),true, showtime().'Some results expected, get them as array');
	
	foreach($rs as $rec)
	{
		$grpchild = phrasea_grpchild($ph_session["session_id"], $rec['base_id'], $rec['record_id'], GV_sit, USR_ID );
		$t->is(($grpchild===true || $grpchild===null), true, showtime().'grpchild');
		
		$grpparent = phrasea_grpparent($ph_session["session_id"], $rec['base_id'], $rec['record_id'], GV_sit, USR_ID );
		$t->is(($grpparent===true || $grpparent===null), true, showtime().'grpparent');
		
		$subdef = phrasea_subdefs($ph_session["session_id"], $rec['base_id'], $rec['record_id']);
		$t->is(is_array($subdef), true, showtime().'subdef');
	
		break;
	}
		
}
else
{
	$t->is($ret,null, showtime().'No results, null value');
}

$t->is(phrasea_close_session($ph_session["session_id"]),true, showtime().'Closing session');


function showtime()
{
	static $last_t = false;
	$t = microtime(true);
	$ret = false;
	if($last_t !== false)
		$ret = sprintf(" -- Time : %0.5f \t", $t-$last_t);
	$last_t = $t;
	return $ret;
}
