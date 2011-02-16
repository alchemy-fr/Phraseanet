<?php
showtime();
require(dirname(__FILE__)."/../lib/bootstrap.php");
set_time_limit(300);

if(!GV_use_cache)
{
	exit("\nAucun cache utilise, fin\n");
}

define('USR_ID', 4);

$t = new lime_test(4);


$sessid = phrasea_create_session(USR_ID);


$ph_session = phrasea_open_session($sessid, USR_ID);


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


$ph_session = phrasea_open_session($sessid, USR_ID);


if($basok == 0)
{
	echo ("pas de base/coll ok, fin\n");
	phrasea_close_session($sessid);
	die();
}


$ret = phrasea_clear_cache($sessid);


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
	$rs = phrasea_fetch_results($ph_session["session_id"], 1, 20, true, '[[em]]', '[[/em]]');
	if($rs)
		$rs = $rs['results'];

	foreach($rs as $rec)
	{
		$subdef = phrasea_subdefs($ph_session["session_id"], $rec['base_id'], $rec['record_id']);
		
		$key = "test-set--".time();
		
		showtime();
		$cache = cache::getInstance();
		
		$connect_cache = showtime();
		$t->is(($connect_cache < 0.002),true,'Connection au serveur de cache < 0.002 : '.$connect_cache);
		
		$ret = getThumbnail($sessid, $rec['base_id'], $rec['record_id'],false);
		
		$get_sanscache = showtime();
		$t->comment("duree du get thumb sans cache :\t".$get_sanscache);
		
		$cache->delete($key);
		
		$delete_cache = showtime();
		$t->is(($delete_cache < 0.0003),true,'suppression d\'un element cache < 0.0003 : '.$delete_cache);
		
		$cache->set($key,$ret);
		
		$set_cache = showtime();
		$t->is(($set_cache < 0.0008),true,'duree d\'un set dans le cache < 0.0008 : '.$set_cache);
		
		$cache->get($key,$ret);
		
		$get_cache = showtime();
		$t->is(($get_cache < 0.0006),true,'duree d\'un get depuis le cache < 0.0006 : '.$get_cache);
		
		
		
		break;
	}
		
}

function getThumbnail($ses, $bid, $rid)
{
	global $substitutionfiles;
	$getPrev = true;
	$sbas_id = phrasea::sbasFromBas($bid);
	
	$w = $h = 64;
	$sd = phrasea_subdefs($ses, $bid, $rid);

	$thumbnail = null;
	$find = $sha = FALSE ;
	$mime = $extcur = '';
	$docType = 'unknown';
	$bitly = null;
	$url_ext = '';
	$deleted = false;
	
	if($sd)
	{
		if(isset($sd['thumbnail']) && $sd['thumbnail'])
		{
			$thumbnail = $sd['thumbnail']['baseurl'];

			if(substr($thumbnail, -1, 1) != '/')
				$thumbnail .= '/';

			$thumbnail .= $sd['thumbnail']['file'];

			$w = $sd['thumbnail']['width'];
			$h = $sd['thumbnail']['height'];
			$imgclass = ($sd['thumbnail']['width'] > $sd['thumbnail']['height']) ? 'hthbimg' : 'vthbimg';
			
			$bitly = $sd['thumbnail']['bitly'];

			if( file_exists($sd['thumbnail']['path'].$sd['thumbnail']['file']) )
				$find = TRUE ;
			else
				$thumbnail = null;
		}
		if(isset($sd['document']))
		{
			if(isset($sd['document']['file']))
			{
				$mime = isset($sd['document']['mime'])?$sd['document']['mime']:'application/octet-stream';
				$extcur 	= pathinfo($sd["document"]["file"]);
				$extcur 	= isset($extcur["extension"])?$extcur["extension"]:'';	
				$sha 	= isset($sd['document']['sha256'])?$sd['document']['sha256']:false;
				$bitly = $sd['document']['bitly'];
			}
			if(isset($sd['document']['credate']) && isset($sd['document']['moddate']))
			{
				if($sd['document']['credate'] != $sd['document']['moddate'])
				{
					$modtime = new DateTime($sd['document']['moddate']);
					$nowtime = new DateTime('-4 days');
					if($modtime>$nowtime)
						$url_ext = '?'.mt_rand();
				}
			}	
		}
		if(isset($sd['document']['type']))
			$docType = $sd['document']['type'];
		if(!$find)
		{

			// pas de thmbnail : substitution selon mime
			if(isset($sd['document']) && $sd['document'])
			{
				if(isset($sd['document']['mime']))
					$mime = str_replace('/', '_', $sd['document']['mime']);
				else
					$mime = 'application_octet-stream';
				$mime = trim($mime)!=''?$mime:'application_octet-stream';
				// on verifie que l'image de substitution est connue
				if(!isset($substitutionfiles[$mime]))
				{
					// non : on la cherche
					$thumbnail = 'skins/icons/substitution/' . $mime . '.png';
					$thumbnail = str_replace('+', '%20', $thumbnail);
					
					if(file_exists(GV_RootPath . $thumbnail) )
					{
						$substitutionfiles[$mime] = $thumbnail;
					}
					else
					{
						$substitutionfiles[$mime] = 'skins/icons/substitution.png';
					}
				}
				$w = $h = 256;
				$thumbnail = $substitutionfiles[$mime];
				$imgclass = 'vthbimg';
			}
		}
	}
	if(!$thumbnail)	// pas de subdefs du tout
	{
		$thumbnail = 'skins/icons/deleted.png';
		$imgclass = 'vthbimg';
		$w = '128';
		$h = '128';
		$deleted = true;
	}
	
	$ret = array('thumbnail'=>$thumbnail.$url_ext, 'deleted'=>$deleted ,'imgclass'=>$imgclass, 'w'=>$w, 'h'=>$h, 'mime'=>$mime, 'extension'=>$extcur, 'type'=>$docType, 'bitly'=>$bitly, 'sha256'=>$sha);
	if($getPrev)
		$ret['preview'] = $sd;
	
		
	return $ret;
}

function showtime()
{
	static $last_t = false;
	$t = microtime(true);
	if($last_t !== false)
		$ret = round($t-$last_t,5);
	$last_t = $t;
	return $ret;
}
