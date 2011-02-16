<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require_once(GV_RootPath."lib/inscript.api.php");

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("action","usr","cgus","date","bas","col");  

	
$conn = connection::getInstance();

$tab = null;

if($parm['action'] == 'REGIS_BAS')
{
	$ret = 1;
	$usr = $parm['usr'];
	
	// on recup le desktop du user
	$xml = null;
	$sql = 'SELECT desktop FROM usr WHERE usr_id=\''.$usr.'\'';
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs))
			$xml = $row["desktop"];
		$conn->free_result($rs);
	}
	
	$doc = new DOMDocument();
	if(!$xml || !($doc->loadXML($xml)))
		$doc = new DOMDocument('1.0', 'utf-8');
		
	$xp = new DOMXPath($doc);

	$_desktops = $xp->query('/desktops');
	if($_desktops->length == 0)
		$_desktops = $doc->appendChild($doc->createElement('desktops'));
	else
		$_desktops = $_desktops->item(0);
		
	$_cgus = $xp->query('cgus', $_desktops);
	if($_cgus->length == 0)
		$_cgus = $_desktops->appendChild($doc->createElement('cgus'));
	else
		$_cgus = $_cgus->item(0);
		

	$date = $parm['date'];
	
	$cgu = explode('FFFFFFFFFFFF', $parm['cgus']);

	$dbname = $cgu[0];
	$colls = explode('s', $cgu[1]);
	
	foreach($colls as $coll)
	{
		$cguid = $dbname."FFFFFFFFFFFF".$coll;
		
		$_cgu = $xp->query('cgu[@id="'.$cguid.'"]', $_cgus);
		if($_cgu->length == 0)
			$_cgu = $_cgus->appendChild($doc->createElement('cgu'));
		else
			$_cgu = $_cgu->item(0);
		
		$_cgu->setAttribute('id', $cguid);
		$_cgu->setAttribute('date', $date);
		$_cgu->setAttribute('dbname', $dbname);
		$_cgu->setAttribute('coll', $coll);
	}
	
	$sql = sprintf("UPDATE usr SET desktop='%s' WHERE usr_id=%s", $conn->escape_string($doc->saveXML()) , $conn->escape_string($usr_id));
	$conn->query($sql);
	
	echo $ret;
}

if($parm['action'] == 'CANCEL_BAS')
{
	$parm['cgus'] = explode('FFFFFFFFFFFF',$parm['cgus']);
	$tab[$parm['cgus'][0]] = explode('s',$parm['cgus'][1]);
	
	$usr = $parm['usr'];
	$date = $parm['date'];
	$ret = 1;
	
	foreach($tab as $dbname=>$colls)
	{
		foreach($colls as $coll)
		{
			$cguid = $dbname."FFFFFFFFFFFF".$coll;
		
			// on recup le desktop du user
			$sql = sprintf("SELECT basusr.id FROM basusr,bas, sbas WHERE sbas.sbas_id = bas.sbas_id AND usr_id='".$usr."' AND server_coll_id='".$coll."' AND sbas.dbname='".$dbname."' AND bas.base_id = basusr.base_id AND actif = '1'");

			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$sqlU = "DELETE FROM basusr WHERE id='".$row['id']."' AND usr_id='".$usr."'";

					$conn->query($sqlU);
				}
			}
	
		}
	}
	
	echo $ret;
}

if($parm['action'] == 'PRINT')
{
	$inscriptions = giveMeBases();
	
	phrasea::headers();
	?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<style>
			p{
				margin:15px;
			}
		</style>
	</head>
	<body>
	<?php
	
	foreach($inscriptions as $sbasId=>$baseInsc)
	{
		if(($baseInsc['CollsCGU'] || $baseInsc['Colls']) && $baseInsc['inscript'] && $sbasId == $parm['bas'])// il y a des coll ou s'inscrire !
		{
			$pot = false;
			if($baseInsc['CGU'])
			{
				//je pr�sente la base
				echo '<h3 style="text-align:center;background:#EFEFEF;">'.phrasea::sbas_names($sbasId).'</h3>';
				$pot = '<p>'.str_replace(array("\r\n","\n","\n"),"<br/>",(string)$baseInsc['CGU']).'</p>';
			}
			$found = false;
			foreach($baseInsc['CollsCGU'] as $collId=>$collDesc)
			{
				if($parm['col'] == $collId){
					echo '<p>'.str_replace(array("\r\n","\n","\n"),"<br/>",(string)$collDesc['CGU']).'</p>';
					$found = true;
				}
			}
		}
	}
	if(!$found)
		echo $pot;
	?>
	</body>
	<?php
}

