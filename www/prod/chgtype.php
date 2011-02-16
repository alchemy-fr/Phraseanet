<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
phrasea::headers();

$request = httpRequest::getInstance();
$parm = $request->get_parms("ACT", "typelst");

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

$baseFeed = null;

if($parm['ACT'] == 'SEND')
{
	$lst = $parm['typelst'];
	
	$lst = explode(';',$lst);
	foreach($lst as $el)
	{
		if(strlen($el)>0)
		{
			$el = explode('=',$el);
			if(strpos($el[0],'img')!==false)
			{
				$basrec = explode('_',substr($el[0],3));
			
				$connbas = connection::getInstance(phrasea::sbasFromBas($basrec[0]));
				$type = $el[1];
				$rec = $basrec[1];
				
				if($connbas)
				{
					$sql = 'UPDATE record SET type="'.$connbas->escape_string($type).'" WHERE record_id="'.$connbas->escape_string($rec).'"';
					if($connbas->query($sql))
					{
						$sql = 'DELETE FROM subdef WHERE record_id="'.$connbas->escape_string($rec).'" AND name != "document"';
						$connbas->query($sql);
					}
				}
			}
		}
		
	}
	record::rebuild_subdef($lst);
	
	?>
<html lang="<?php echo $session->usr_i18n;?>">
		<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
		</head>
		<body onload="parent.hideDwnl();">
	<?php
		echo '<div style="font-size:11px;text-align:center;">';
		echo '<a href="#" onclick="parent.hideDwnl();">',_('boutton::fermer'),'</a>';
		echo '</div>';
	?>
		</body>
	</html>
	<?php
}