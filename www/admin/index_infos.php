<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$lng = isset($session->locale)?$session->locale:GV_default_lng;


$request = httpRequest::getInstance();
$parm = $request->get_parms("ACTION",
					 "p0",	// id de la base
//					 "XML",	// 
//					 "THS",	// 
					 "INDEXABLE",	//
					'viewname'
					 );

if(function_exists($fct = ('action_'.$parm['ACTION'])))
	echo($fct());
	

function action_DODELETEBASE()
{
	global $parm;
	$ret = array(
		'sbas_id'=>null,
		'err'=>-1,
		'errmsg'=>null
	);
	$dbname = NULL;
	$conn = connection::getInstance();
	if($conn && $conn->isok())
	{
		$sql = 'SELECT dbname FROM sbas WHERE sbas_id=' . $conn->escape_string($parm['p0']);
		if( ($rs = $conn->query($sql)) )
		{
			if( ($row = $conn->fetch_assoc($rs)) )
				$dbname = $row['dbname'];
		}
	}
	if(!$dbname)
		return(p4string::jsonencode($ret));		
		
	$connbas = connection::getInstance($parm['p0']);
	if($connbas && $connbas->isok())
	{
		$sql = 'SELECT COUNT(record_id) AS n FROM record';
		if( ($rs = $connbas->query($sql)) )
		{
			if( ($row = $connbas->fetch_assoc($rs)) )
			{
				if($row['n'] == 0)
				{
					$sql = 'DROP DATABASE `' . $conn->escape_string($dbname) . '`';
					if($connbas->query($sql))
					{
						action_UMOUNTBASE();
						action_DELLOGOPDF();
						$ret['sbas_id'] = $parm['p0'];
						$ret['err'] = 0;
					}
					else
					{
						$ret['errmsg'] = 'DROPERR';
					}
				}
				else
				{
					$ret['errmsg'] = _('admin::base: vider la base avant de la supprimer');
				}
			}
			$connbas->free_result($rs);
		}
	}
	return(p4string::jsonencode($ret));
}	
	
function action_UMOUNTBASE()
{
	global $parm;
	$ret = array(
		'sbas_id'=>null
	);
	$conn = connection::getInstance();
	if($conn && $conn->isok())
	{
		// ici on demonte la base de ce site : on delete tout ce qui concerne toutes les collections de cette base
		$sql = "DELETE FROM basusr WHERE base_id IN(SELECT base_id FROM bas WHERE sbas_id='".$conn->escape_string($parm['p0'])."')";
		$conn->query($sql);
		$sql = "DELETE FROM order_masters WHERE base_id IN(SELECT base_id FROM bas WHERE sbas_id='".$conn->escape_string($parm['p0'])."')";
		$conn->query($sql);
		$sql = "DELETE FROM sselcont WHERE base_id IN(SELECT base_id FROM bas WHERE sbas_id='".$conn->escape_string($parm['p0'])."')";
		$conn->query($sql);
		$sql = "DELETE FROM bas WHERE sbas_id='".$conn->escape_string($parm['p0'])."'";
		$conn->query($sql);
		$sql = "DELETE FROM sbas WHERE sbas_id='".$conn->escape_string($parm['p0'])."'";
		$conn->query($sql);
		
		$ret['sbas_id'] = $parm['p0'];
	}
	return(p4string::jsonencode($ret));
}	
	
	
function action_DELLOGOPDF()
{
	global $parm;
	$ret = array(
		'sbas_id'=>null
	);
	if(@unlink(GV_RootPath.'config/minilogos/logopdf_'.$parm['p0'].'.jpg'));
	{
		$cache_data = cache_appbox::getInstance();
		$cache_data->delete('printLogo'.$parm['p0']);		
		$ret['sbas_id'] = $parm['p0'];
	}
	return(p4string::jsonencode($ret));
}
	
function action_CLEARALLLOG()
{
	global $parm;
	$ret = array(
		'sbas_id'=>null
	);
	$connbas = connection::getInstance($parm['p0']);
	if($connbas && $connbas->isok())
	{
		foreach(array('log', 'exports', 'quest') as $table)
		{
			$sql = 'TRUNCATE' . $table;
			$connbas->query($sql);
		}
		$ret['sbas_id'] = $parm['p0'];
	}
	return(p4string::jsonencode($ret));
}

function action_REINDEX()
{
	global $parm;
	$connbas = connection::getInstance($parm['p0']);
	$sql = 'UPDATE pref SET updated_on=\'0000-00-00 00:00:00\' WHERE prop=\'indexes\'';
	$connbas->query($sql);
	return('yes');
}

function action_MAKEINDEXABLE()
{
	global $parm;
	$ret = array(
		'sbas_id'=>null,
		'indexable'=>null
	);
	$conn = connection::getInstance();
	$sql  = 'UPDATE sbas SET indexable=\''.($parm['INDEXABLE']?'1':'0').'\' WHERE sbas_id=\''.$conn->escape_string($parm['p0']).'\'';
	if( ($rs = $conn->query($sql)) )
	{
		$ret['sbas_id'] = $parm['p0'];
		$ret['indexable'] = $parm['INDEXABLE'];
	}
	return('yes');
}

function action_CHGVIEWNAME()
{
	global $parm;
	$ret = array(
		'sbas_id'=>null,
		'viewname'=>null
	);
	$conn = connection::getInstance();
	if($conn && $conn->isok())
	{
		$sql = 'UPDATE sbas SET viewname=\''.$conn->escape_string($parm['viewname']).'\' WHERE sbas_id=\''.$conn->escape_string($parm['p0']).'\'';
		if( ($rs = $conn->query($sql)) )
		{
			$ret['sbas_id'] = $parm['p0'];
			$ret['viewname'] = $parm['viewname'];
			$cache = cache_appbox::getInstance();
			$cache->delete('list_bases');
			cache_databox::update($parm['p0'],'structure');
		}
	}
	return(p4string::jsonencode($ret));
}

function action_P_BAR_INFO()
{
	global $parm;
	$ret = array(
		'sbas_id'=>null,
		'indexable'=>false,
		'records'=>0,
		'xml_indexed'=>0,
		'thesaurus_indexed'=>0,
		'viewname'=>null,
		'printLogoURL'=>NULL
	);
		
	$conn = connection::getInstance();
	if($conn && $conn->isok())
	{
		$sql = 'SELECT indexable, viewname FROM sbas WHERE sbas_id=\''.$conn->escape_string($parm['p0']).'\'';
		if( ($rs = $conn->query($sql)) )
		{
			if( ($row = $conn->fetch_assoc($rs)) )
			{
				$ret['indexable'] = ($row['indexable'] != 0);
				$ret['viewname'] = $row['viewname'];
			}
			$conn->free_result($rs);
		}
		
		$connbas = connection::getInstance($parm['p0']);
		if($connbas && $connbas->isok())
		{
			$ret['sbas_id'] = $parm['p0'];
			$tot = $idxxml = $idxth = 0;
			$sql = "SELECT status & 3 AS status, SUM(1) AS n FROM record GROUP BY(status & 3)";
			if( ($rs = $connbas->query($sql)) )
			{
				while( ($row = $connbas->fetch_assoc($rs)) )
				{
					$status = $row['status'];
					$ret['records'] += $row['n'];
					if($status & 1)
						$ret['xml_indexed'] += $row['n'];
					if($status & 2)
						$ret['thesaurus_indexed'] += $row['n'];
				}
				$connbas->free_result($rs);
			}
		}
	}
	if( file_exists(GV_RootPath.'config/minilogos/logopdf_'.$parm['p0'].'.jpg') )
		$ret['printLogoURL'] = '/print/'.$parm['p0'];
	return(p4string::jsonencode($ret));
}
