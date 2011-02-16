<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
phrasea::headers();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					 "act"
					, "lst"
					, "mska"
					, "msko"
					, "chg_status_son"
					, 'dlgW'
					, 'dlgH'
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
?>

<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
		<script type="text/javascript">
					
				function loaded()
				{	
					parent.hideDwnl();
				}

		</script>
	</head>

	<body onload="loaded();" style="overflow:hidden; padding:0px; margin:0px;">
		<?php
		if($parm["act"] == "START" || $parm["act"] == "WORK")
		{
			$usrRight=null;
			$conn = connection::getInstance();
				### droits user
				$sql = 'SELECT bu.base_id,bu.chgstatus FROM (usr u, basusr bu) WHERE u.usr_id="'.$conn->escape_string($usr_id).'" AND bu.usr_id = u.usr_id';
				if($rs = $conn->query($sql))
				{
					while($row = $conn->fetch_assoc($rs))
					{
						$usrRight[$row["base_id"]] = $row["chgstatus"];
					}			
					$conn->free_result($rs);
				}
			
			
			$basdst2baslocal = NULL;
			$sbasNames = NULL;
			foreach($ph_session['bases'] as $onebase)
			{					 
				$sbasNames[$onebase['sbas_id']] = $onebase['dbname'];
				foreach($onebase['collections'] as $oneColl)
				{
					$basdst2baslocal[$onebase['sbas_id']][$oneColl['coll_id']] = $oneColl['base_id'];			
				}
			}
			
			if($parm["act"]=="WORK")
			{
				if($parm["chg_status_son"]=="1")
				{
					$lst = explode(";", $parm["lst"]);	
					foreach($lst as $basrec)
					{
						$basrec = explode('_',$basrec);
						if( function_exists("phrasea_isgrp") && phrasea_isgrp($ses_id, $basrec[0], $basrec[1]) )
						{
							$allson = phrasea_grpchild($ses_id,$basrec[0],$basrec[1],GV_sit,$usr_id) ;				
							foreach($allson as $oneson)
							{
								if( $usrRight[$oneson[0]]=="1")
								{
									if( $parm["lst"]!="" &&  $parm["lst"]!=null)
										 $parm["lst"].=",";
									$parm["lst"] .= ';'.$oneson[0].'_'.$oneson[1] ;
								}
							}
						}
					}	
				}
				
				$mska = $msko = null;
				
				$sbA = explode(';',$parm["mska"]);
				$sbO = explode(';',$parm["msko"]);
				
				foreach($sbA as $sbAnd)
				{
					$sbAnd = explode('_',$sbAnd);
					$mska[$sbAnd[0]] = $sbAnd[1];
				}
				foreach($sbO as $sbOr)
				{
					$sbOr = explode('_',$sbOr);
					$msko[$sbOr[0]] = $sbOr[1];
				}
				
				$lst = explode(";", $parm["lst"]);	
				$maj = 0;
				$recs = array();
				foreach($lst as $basrec)
				{
					$basrec=explode('_',$basrec);
					if(count($basrec) == 2)
					{
						$connbas = connection::getInstance(phrasea::sbasFromBas($basrec[0]));
						if(($connbas) && isset($mska[phrasea::sbasFromBas($basrec[0])]) && isset($msko[phrasea::sbasFromBas($basrec[0])]))
						{
							$recs[] = $basrec[1];
							$sql = "UPDATE record SET status=((status & ".$mska[phrasea::sbasFromBas($basrec[0])].") | ".$msko[phrasea::sbasFromBas($basrec[0])].") WHERE record_id='".$connbas->escape_string($basrec[1])."'";
							if($connbas->query($sql))
							{
								$maj++;
								answer::logEvent(phrasea::sbasFromBas($basrec[0]),$basrec[1],'status','','');
							}
						}
					}
				}
				$cache_basket = cache_basket::getInstance();
				$cache_basket->revoke_baskets_record($recs);
				?>
				<div style="font-size:11px;text-align:center;">
					<?php echo sprintf(_('prod::proprietes : %d documents modifies'),$maj)?><br>
					<a href="#" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer')?></a>
				</div>
				<?php 
			}
		}
		?>
	</body>
</html>