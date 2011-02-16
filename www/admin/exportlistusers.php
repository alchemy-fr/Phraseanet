<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
	
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("p0", "p1", "p4", "p5" ,"p6", "t"); 



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



function sylk($tableau,$nomfic,$typ="SYLK")
{
	
	if ($tableau)
	{
		if($typ=="TXT")
		{
                        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                        header("Cache-Control: no-store, no-cache, must-revalidate");
                        header("Cache-Control: post-check=0, pre-check=0", false);
                        header("Pragma: no-cache");
                        header("Content-Type: text/plain");
                        header("Cache-Control: max-age=3600, must-revalidate ");
                        header("Content-Disposition: attachment; filename=export.txt;");
				
			for($i=0;$i<count($tableau[0]); $i++)
			{
				print($tableau[0][$i]);
				if( ($i+1)<count($tableau[0]) )
					print("\t");
			}
			print("\n");
			for($j=1;$j<count($tableau); $j++)
			{
				for($i=0;$i<count($tableau[$j]); $i++)
				{
					
					print($tableau[$j][$i]);
					if( ($i+1)<count($tableau[$j]) )
						print("\t");
				}
				print("\n");
			}		
		}
		else
		{ 
                        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                        header("Cache-Control: no-store, no-cache, must-revalidate");
                        header("Cache-Control: post-check=0, pre-check=0", false);
                        header("Pragma: no-cache");
                        header("Content-Type: text/plain");
                        header("Cache-Control: max-age=3600, must-revalidate ");
                        header("Content-Disposition: attachment; filename=export.csv;");
				
			for($i=0;$i<count($tableau[0]); $i++)
			{
				print('"'.str_replace('"','""',$tableau[0][$i]).'"');
				if( ($i+1)<count($tableau[0]) )
					print(",");
			}
			print("\n");
			for($j=1;$j<count($tableau); $j++)
			{
				for($i=0;$i<count($tableau[$j]); $i++)
				{
					
					print('"'.str_replace('"','""',$tableau[$j][$i]).'"');
					if( ($i+1)<count($tableau[$j]) )
						print(",");
				}
				print("\n");
			}		
		}
	}
	else
	{
?>
<script type="text/javascript">
	alert("ERROR <?php echo __LINE__?>");
</script>
<?php
	}
}


 
	
	if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	{
		die();
	}
	
	
	$EquivBAStoSBAS = NULL ;
	$conn = connection::getInstance();
	if(!$conn)
	{
		die();
	}
	$out = "";
	$sql = "SELECT base_id,sbas_id FROM bas order by base_id";
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs) )
			$EquivBAStoSBAS[$row["base_id"]] = $row["sbas_id"] ;
	}
	 
	$seepwd = FALSE;	
	$sql = "SELECT usr.seepwd FROM usr WHERE usr.usr_id='".$conn->escape_string($usr_id)."'";
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs) )
		{
			if($row["seepwd"]=="1")	
				$seepwd = TRUE;			
		}
	}

	
	$baslist = "";
	$baslibs = "";	
	if($parm["p0"])
	{
		
			$ncolls = 0;
			
			$connbas = connection::getInstance($parm['p0']);
			
			$sql = 'SELECT bas.* FROM ((bas NATURAL JOIN basusr) NATURAL JOIN usr) WHERE sbas_id="'.$conn->escape_string($row['sbas_id']).'" AND usr.usr_id="'.$conn->escape_string($usr_id).'" AND basusr.canadmin=1';

			if($parm["p1"])			// on demande juste une collection, ce qui va verifier qu'elle est bien dans la base p0
				$sql .= " AND bas.base_id='" . $conn->escape_string($parm["p1"])."'";
				
	 		if($rs = $conn->query($sql))
			{
				while($rowcolls = $conn->fetch_assoc($rs))
				{
					$ncolls++;
					$baslist .= ($baslist==""?"":",") . $rowcolls["base_id"];
					$collname = "<i>id:" . $rowcolls["base_id"] . "</i>";				
					// on cherche le nom de la collection distante
					if($connbas)
					{
						$sql = "SELECT * FROM coll WHERE coll_id='" . $connbas->escape_string($rowcolls["server_coll_id"])."'";
						if($rsbas = $connbas->query($sql))
						{
							if($rowbas = $connbas->fetch_assoc($rsbas))
								$collname = $rowbas["asciiname"];
							$connbas->free_result($rsbas);
						}
					}
					$baslibs .= ($baslibs==""?"":", ") . "<b>" . $collname . "</b>";
				}
				$conn->free_result($rs);
			}
	}
	else
	{
		 // toutes les bases que je peux administrer
		$sql = "SELECT bas.base_id, sbas.host, sbas.port, sbas.dbname 
				FROM (((usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id)
					INNER JOIN bas ON basusr.base_id=bas.base_id)  
					INNER JOIN sbas ON bas.sbas_id=sbas.sbas_id) 
				WHERE usr.usr_id='".$conn->escape_string($usr_id)."' AND basusr.canadmin=1 
				ORDER BY host, port, dbname";
		
		
		if($rs = $conn->query($sql))
		{
			$nbas = 0;
			$lastk = "?";
			while($rowcolls = $conn->fetch_assoc($rs))
			{
				$k = $rowcolls["host"] . "_" . $rowcolls["port"] . "_" . $rowcolls["dbname"];
				if($k != $lastk)
				{
					$baslibs .= ($baslibs==""?"":", ") . "<b>". $rowcolls["dbname"] . "</b>";
					$lastk = $k;
					$nbas++;
				}
				$baslist .= ($baslist==""?"":",") . $rowcolls["base_id"];
			}
			$conn->free_result($rs);
		}
	}

	// on filtre les bases administrables
	$sql = "SELECT base_id FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) WHERE usr.usr_id=".$usr_id." AND basusr.canadmin=1 AND (basusr.base_id IN (".$baslist.")) ORDER BY usr.usr_id";

	$baslist = "(".$baslist.")";
	

	
	
	
	
	function parse4sql($name)
	{
		$name = $name;
		$name = str_replace("."," ",$name);
		$name = str_replace(",","_",$name);
		$name = trim($name);
		$name = str_replace(" ","_",$name);
		return( '`'.$name.'`' );
	}
	
	$precise = "" ;
	if($parm["p4"]!=null && $parm["p4"]!="" && $parm["p5"]!=null && $parm["p5"]!="" )
	{
		$precise ="";
		if($parm["p5"]=="LOGIN")
			$precise.=" AND usr_login like '".$conn->escape_string($parm["p4"])."%' ";
		elseif($parm["p5"]=="NAME")
			$precise.=" AND (usr_nom like '".$conn->escape_string($parm["p4"])."%' OR usr_prenom like '".$conn->escape_string($parm["p4"])."%' ) ";
		elseif($parm["p5"]=="COUNTRY")
			$precise.=" AND usr.pays like '".$conn->escape_string($parm["p4"])."%' ";
		elseif($parm["p5"]=="COMPANY")
			$precise.=" AND usr.societe like '".$conn->escape_string($parm["p4"])."%' ";
		elseif($parm["p5"]=="MAIL")
			$precise.=" AND usr.usr_mail like '".$conn->escape_string($parm["p4"])."%' ";
		 
	}
	$preciseBasusr = "";
	if($parm["p6"]!=null && $parm["p6"]!=""  )
	{
		
		if($parm["p6"]=="0") 	 // on veut pas voir personnne ( t'es bizarre toi !!! )
			$preciseBasusr.=" AND actif=9999 ";
		elseif($parm["p6"]=="1") // on veut que voir que les inactifs
			$preciseBasusr.=" AND actif=0 ";
		elseif($parm["p6"]=="2") // on veut que voir que les actifs
			$preciseBasusr.=" AND actif=1 ";
		 
	}
	
	$sql='
	SELECT
		usr.usr_id AS ID,
		usr_login AS Login,
		usr_password AS Password,
		usr_nom AS '.parse4sql(_('admin::compte-utilisateur nom')).',
		usr_prenom AS '.parse4sql(_('admin::compte-utilisateur prenom')).',
		usr_mail AS '.parse4sql(_('admin::compte-utilisateur email')).',
		usr_creationdate AS CreationDate,
		usr_modificationdate AS ModificationDate,
		adresse AS '.parse4sql(_('admin::compte-utilisateur adresse')).',
		ville AS '.parse4sql(_('admin::compte-utilisateur ville')).',
		cpostal AS '.parse4sql(_('admin::compte-utilisateur code postal')).',
		pays AS '.parse4sql(_('admin::compte-utilisateur pays')).',
		tel AS '.parse4sql(_('admin::compte-utilisateur telephone')).',
		fax AS '.parse4sql(_('admin::compte-utilisateur fax')).',
		fonction AS '.parse4sql(_('admin::compte-utilisateur poste')).',
		societe AS '.parse4sql(_('admin::compte-utilisateur societe')).',
		activite AS '.parse4sql(_('admin::compte-utilisateur activite')).'
		
		
		,activeFTP AS '.parse4sql(_('admin::compte-utilisateur:ftp: Activer le compte FTP')).'
		,addrFTP AS '.parse4sql(_('phraseanet:: adresse')).'
		,loginFTP 
		,pwdFTP  
		,destFTP AS '.parse4sql(_('admin::compte-utilisateur:ftp:  repertoire de destination ftp')).'
		,passifFTP AS '.parse4sql(_('admin::compte-utilisateur:ftp: Utiliser le mode passif')).'
		,retryFTP AS '.parse4sql(_('admin::compte-utilisateur:ftp: Nombre d\'essais max')).'		
		,prefixFTPfolder AS '.parse4sql(_('admin::compte-utilisateur:ftp: prefixe des noms de dossier ftp')).'		
		,defaultftpdatasent AS '.parse4sql(_('admin::compte-utilisateur:ftp: Donnees envoyees automatiquement par ftp')).' ';

	 
	
	$sql.= " FROM usr, 
					(
						SELECT 
						usr_id FROM basusr where
						basusr.base_id IN " . $baslist . " $preciseBasusr 
						GROUP BY usr_id
					) as basusrFiltre
				WHERE 
					usr.usr_id=basusrFiltre.usr_id 
					AND (model_of=0 OR model_of='".$conn->escape_string($usr_id)."')
					$precise	
				ORDER BY usr_login";;
		
	$userTable = null;
	$first = true;
	$i=1;
	if($rs = $conn->query($sql))
	{
		while(($row = $conn->fetch_assoc($rs)))
		{
			
			foreach($row as $fldname=>$fldval)
			{
				if($fldname=="Password")
					continue;
				if($first)
					$userTable[0][]=$fldname;
					
				$fldval = str_replace("\n"," ",$fldval);
				$fldval = str_replace("\r"," ",$fldval);
					
				if($fldname=="ID")
					$fldval = (int)	$fldval;
				$userTable[$i][]=$fldval;	
			}
			$i++;
			$first = false;
		}
	}

	sylk($userTable,"user",$parm["t"]);	
	 

