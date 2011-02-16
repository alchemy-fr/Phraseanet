<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("type", "bas","rec","sha","view");

$referrer = 'NO REFERRER';

if(isset($_SERVER['HTTP_REFERER']))
	$referrer = $_SERVER['HTTP_REFERER'];
	
if($parm['bas']=='' && $parm['rec']=='')
{
	phrasea::headers(403);
}

$conn = connection::getInstance();

$sbas = false;
$WM = $STAMP = 0;
$usr_id = false;
$ses_id = false;
$USR_WM = -1;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
}

if($parm['sha']!='' && $ses_id === false && $usr_id === false)
{
	if(!($ph_session = phrasea::load_settings($session->locale)))
	{
		header('Location: /include/logout.php');
		exit();
	}
}



if(trim($parm['type']) == "")
	$parm['type'] = 'preview';
	

$file = false;

$server_coll_id = phrasea::collFromBas($parm['bas']);
$sbas_id = phrasea::sbasFromBas($parm['bas']);

$connSbas = connection::getInstance($sbas_id);

if($usr_id !== false && $ses_id !== false)
{
	$sql = "SELECT bu.actif, bu.needwatermark, bu.canpreview FROM basusr bu, bas b 
			WHERE b.base_id=bu.base_id AND usr_id='".$conn->escape_string($session->usr_id)."' 
			AND b.base_id='".$conn->escape_string($parm['bas'])."' AND actif = '1'";

	if($rs2 = $conn->query($sql))
	{
		if($row2 = $conn->fetch_assoc($rs2))
		{
			$USR_WM = $row2["needwatermark"]; 
		}
		$conn->free_result($rs2);
	}
	
	if($USR_WM < 0)
	{
		$sql = 'SELECT v.id FROM sselcont c, validate v 
				WHERE c.base_id="'.$conn->escape_string($parm['bas']).'" 
					AND c.record_id="'.$conn->escape_string($parm['rec']).'" 
					AND v.usr_id="'.$conn->escape_string($session->usr_id).'" 
					AND c.ssel_id = v.ssel_id';
		
		if($rs2 = $conn->query($sql))
		{
			if($row2 = $conn->fetch_assoc($rs2))
			{
				$USR_WM = 0; 
			}
			$conn->free_result($rs2);
		}
		
		if($USR_WM < 0)
		{
			$sql = 'SELECT sselcont_id FROM sselcont c, ssel s 
					WHERE c.ssel_id=s.ssel_id 
						AND c.record_id="'.$conn->escape_string($parm['rec']).'" 
						AND c.base_id="'.$conn->escape_string($parm['bas']).'" 
						AND s.pushFrom > 0';
			
			if($rs2 = $conn->query($sql))
			{
				if($row2 = $conn->fetch_assoc($rs2))
				{
					$USR_WM = 0; 
				}
				$conn->free_result($rs2);
			}
		}
	}
}
if($connSbas)
{
	$sql = "SELECT pub_wm FROM coll WHERE coll_id = '".$connSbas->escape_string($server_coll_id)."'";
	
	if($parm['view'] == 'overview')
	{
		if($rs2 = $connSbas->query($sql))
		{
			if($row2 = $conn->fetch_assoc($rs2))
			{
				switch($row2['pub_wm'])
				{
					case 'none':
						$WM = 0;
						break;
					case 'stamp':
						$STAMP = 1;
						break;
					case 'wm':
						if($USR_WM != 0)
							$WM = 1;
						break;
				}
			}
			$rs2 = $connSbas->free_result($rs2);
		}
		if($USR_WM == -1)//j'ai pas les droits sur la coll, mais j'ai l'url unique
		{
			$sql = 'SELECT record_id FROM record WHERE sha256="'.$connSbas->escape_string($parm['sha']).'" 
					AND record_id = "'.$connSbas->escape_string($parm['rec']).'" 
					AND coll_id="'.$connSbas->escape_string($server_coll_id).'"';
			
			if($rs2 = $connSbas->query($sql))
			{
				if($connSbas->num_rows($rs2)==0)
				{
					phrasea::headers(403);
				}
				$connSbas->free_result($rs2);
			}
		}
	}
	elseif($USR_WM == -1)// pas de dri
	{
		$sql = 'SELECT record_id FROM record WHERE sha256="'.$connSbas->escape_string($parm['sha']).'" AND record_id = "'.$connSbas->escape_string($parm['rec']).'" and coll_id="'.$connSbas->escape_string($server_coll_id).'"';
		
		if($rs2 = $connSbas->query($sql))
		{
			if($connSbas->num_rows($rs2)==0)
			{
				phrasea::headers(403);
			}
			$connSbas->free_result($rs2);
		}
	}
	elseif($USR_WM == 1)// pas de dri
	{
		$WM = 1;
	}
	
	//si tout va bien on continue
	
	$sql = "SELECT path, file, mime, type, xml FROM subdef s, record r WHERE r.record_id='".$connSbas->escape_string($parm['rec'])."' AND r.record_id = s.record_id AND name='".$connSbas->escape_string($parm['type'])."'";
	
	
	if($rs3 = $connSbas->query($sql))
	{
		if($connSbas->num_rows($rs3) > 0)
		{
			if($row3 = $connSbas->fetch_assoc($rs3))
			{
				$file = array(
						'type'=>$row3['type']
						,'path'=>p4string::addEndSlash($row3['path'])
						,'file'=>$row3['file']
						,'mime'=>$row3['mime']
						,'xml'=>$row3['xml']
					);
			}
		}
		else
		{
			$sql = "SELECT path, file, mime, type, xml FROM subdef s, record r WHERE r.record_id='".$connSbas->escape_string($parm['rec'])."' AND r.record_id = s.record_id AND name='document'";
	
			if($rs2 = $connSbas->query($sql))
			{
				if($row2 = $connSbas->fetch_assoc($rs2))
				{
					if($row2['type'] === 'document')
					{
						$file = array(
								'type'=>$row2['type']
								,'path'=>p4string::addEndSlash($row2['path'])
								,'file'=>$row2['file']
								,'mime'=>$row2['mime']
								,'xml'=>$row2['xml']
							);
					}
				}
			}
		}
		$connSbas->free_result($rs3);
	}
}



		
if($file)
{
	$pathIn =  $pathOut = $file['path'].$file['file'];
	
	if($WM == 1 && $file['type'] ==='image')
	{
		$pathOut = record_image::watermark($parm['bas'],$parm['rec']);
	}
	elseif($STAMP == 1 && $file['type'] ==='image')
	{
		$pathOut = record_image::stamp($parm['bas'],$parm['rec']);
	}


	$log_id = null;
	if(isset($session->logs) && isset($session->logs[$sbas_id]))
	{
		$log_id = $session->logs[$sbas_id];
	}
	
	$sql = 'INSERT INTO log_view (id, log_id, date, record_id, referrer, site_id) VALUES (null, '.($log_id===null?"null":'"'.$connSbas->escape_string($log_id).'"').', now(), "'.$connSbas->escape_string($parm['rec']).'", "'.$connSbas->escape_string($referrer).'", "'.GV_sit.'")';
	$connSbas->query($sql);
	
	if(export::stream_file($pathOut,$file["file"],$file["mime"], 'inline'))
		exit();
	else
		phrasea::headers(404);
}
phrasea::headers(403);
