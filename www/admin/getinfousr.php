<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();
$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	if(!$session->admin)
	{
		phrasea::headers(403);
	}
}
else{
		phrasea::headers(403);
}
	
$conn = connection::getInstance();
if(!$conn)
{
		phrasea::headers(500);
}

phrasea::headers();

$request = httpRequest::getInstance();
$parm = $request->get_parms("u");

$info  = "" ;
$info2 = "" ;

$sql = "SELECT * FROM usr WHERE usr_id='".$conn->escape_string($parm["u"])."'";
if($rs = $conn->query($sql))
{
	if($row = $conn->fetch_assoc($rs))
	{
		$info .= _('admin::compte-utilisateur identifiant') . " : " .  $row["usr_login"] ;
		if( trim($row["usr_nom"])!="" || trim($row["usr_prenom"])!="" )
		{
			$info2 .=  "<br>". _('admin::compte-utilisateur nom') . "/" . _('admin::compte-utilisateur prenom') . " : "  ;
			if( trim($row["usr_nom"])!="")
				$info2 .= $row["usr_nom"] ." ";
			if( trim($row["usr_prenom"])!="")
				$info2 .= $row["usr_prenom"];
			 
		}
		if( trim($row["usr_mail"])!="" )
		{
			$info2 .=  "<br>". _('admin::compte-utilisateur email') . " : "  ;
			if( trim($row["usr_mail"])!="")
				$info2 .= $row["usr_mail"]  ;
		}
		if( trim($row["tel"])!="" )
		{
			$info2 .=  "<br>". _('admin::compte-utilisateur telephone') . " : "  ;
			if( trim($row["tel"])!="")
				$info2 .= $row["tel"]  ;
		}
		if( trim($row["fonction"])!="" )
		{
			$info2 .=  "<br>". _('admin::compte-utilisateur poste') . " : "  ;
			if( trim($row["fonction"])!="")
				$info2 .= $row["fonction"]  ;
		}
		if( trim($row["societe"])!="" )
		{
			$info2 .=  "<br>". _('admin::compte-utilisateur societe') . " : "  ;
			if( trim($row["societe"])!="")
				$info2 .= $row["societe"]  ;
		}
		if( trim($row["activite"])!="" )
		{
			$info2 .=  "<br>". _('admin::compte-utilisateur activite') . " : "  ;
			if( trim($row["activite"])!="")
				$info2 .= $row["activite"]  ;
		}
		if( trim($row["adresse"])!="" || trim($row["cpostal"])!="" || trim($row["ville"])!="" )
		{
			 $info2 .= "<br><div style='background-color:#777777'><font color=#FFFFFF>" . _('admin::compte-utilisateur adresse'). "</font>";	
			 if( trim($row["adresse"])!="")
				$info2 .= "<br>". $row["adresse"];
			if( trim($row["adresse"])!="" || trim($row["cpostal"])!="" || trim($row["ville"])!="" )
			{	
				$info2 .= "<br>";
				if( trim($row["cpostal"])!="" )
					$info2 .= $row["cpostal"]." ";
				if( trim($row["ville"])!="" )
					$info2 .= $row["ville"];
			}
			$info2 .= "</div>";	
		 
		}
		if($info2!="")	
			$info .= "<font color=#EEEEEE>" . $info2 . "</font>";	
	}
}
$info = str_replace("<br><br>","<br>",$info);
$info = str_replace("\n","",$info);
$info = str_replace("\r","",$info);

?>
<script type="text/javascript">
parent.usrDesc[<?php echo $parm["u"]?>] = "<?php echo p4string::MakeString($info,"js")?>";
parent.redrawUsrDesc(<?php echo $parm["u"]?>);
</script>