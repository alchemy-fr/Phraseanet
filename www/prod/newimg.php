<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
phrasea::headers();
require(GV_RootPath."lib/index_utils2.php");

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"lst"
					,"ACT"
					,"operation"
					,"ForceThumbSubstit"
					);

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session((int)$ses_id, $usr_id)))
	{
		header("Location: /login/?err=no-session");
		exit();
	}
}
else
{
	header("Location: /login/");
	exit();
}

$conn = connection::getInstance();



$lst = explode(";", $parm["lst"]);
$newlist = '';	
if($parm['ForceThumbSubstit'] == 'on')
{
	foreach($lst as $basrec)
	{
		$basrec = explode('_',$basrec);
		if(isset($doc['sd'][$sd_name]) && isset($doc['sd'][$sd_name]['substit']) && $doc['sd'][$sd_name]['substit'] == 1)
		{
			$connbas = connection::getInstance(phrasea::sbasFromBas($basrec[0]));
			if($connbas)
			{
				$sql = 'UPDATE subdef SET substit="0" WHERE name="'.$connbas->escape_string($sd_name).'" AND record_id="'.$connbas->escape_string($basrec[1]).'"';
				$connbas->query($sql);
			}
		}
	}
}

record::rebuild_subdef($lst);
	