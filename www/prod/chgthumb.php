<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
phrasea::headers();
require(GV_RootPath."lib/index_utils2.php");

$request = httpRequest::getInstance();
$parm = $request->get_parms("act", "bid","rid");

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

$sbas_id = phrasea::sbasFromBas($parm["bid"]);

$pathThumb 	= null ;
$baseurl 	= null ;
$size 		= null ;


?> 
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/jquery-ui.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
	</head>
	<body>

<?php

if(  !isset($_FILES["newThumb"]) || $_FILES["newThumb"]["tmp_name"]=="" || $_FILES["newThumb"]["size"]=="" ||  ($_FILES["newThumb"]["size"]+0)==0 )
{
	echo "<center>",_('prod::substitution::erreur : impossible d\'ajouter ce document'),"<br><br>";
		echo "<a onClick=\"parent.hideDwnl();\">",_('boutton::fermer'),"</a>";
		die('</body></html>');
}
$namenewThumb = $parm["rid"] . "_thumbnail.jpg";

$connbas = connection::getInstance($sbas_id);

$found = false;
$sql = 'SELECT path, baseurl, width, height FROM subdef WHERE name="thumbnail" AND record_id="'.$connbas->escape_string($parm['rid']).'"';

if( $rs = $connbas->query($sql))
{
	if($row = $connbas->fetch_assoc($rs))
	{
		$pathThumb = $row['path'];
		$baseurl = $row['baseurl'];
		$size = max((int)$row['width'], (int)$row['height']);
		$found = true;
	}
	$connbas->free_result($rs);
}

if(!$found)
{
	echo "<center>",_('prod::erreur : impossible de lire les preferences de base'),"<br><br>";
		echo "<a onClick=\"parent.hideDwnl();\">",_('boutton::fermer'),"</a>";
	die('</body></html>');
}

if(!$pathThumb || $pathThumb==null || $pathThumb=="" || strlen($pathThumb)< 3 )
{
	echo "<center>",_('prod::substitution::erreur : impossible d\'acceder au dossier de stockage'),"<br><br>";
		echo "<a onClick=\"parent.hideDwnl();\">",_('boutton::fermer'),"</a>";
		die('</body></html>');
}

if($pathThumb)
{
	if( ! is_dir($pathThumb))
	{
	echo "<center>",_('prod::substitution::erreur : impossible d\'acceder au dossier de stockage'),"<br><br>";
		echo "<a onClick=\"parent.hideDwnl();\">",_('boutton::fermer'),"</a>";
		die('</body></html>');
	}

	if( !(substr($pathThumb,strlen($pathThumb)-1)=="/") &&  !(substr($pathThumb, strlen($pathThumb)-1 )=="\\") )
	{
			$pathThumb .= "/";
	}
	// j'ai le path pour la future thumbnail

	// je verifie qu'il n'y a pas une ancienne thumb sinon je la del
	$sd = phrasea_subdefs($ses_id, $parm["bid"], $parm["rid"],"thumbnail");
	if(isset($sd) && isset($sd["thumbnail"]))
	{
		if( !(substr($sd["thumbnail"]["path"],strlen($sd["thumbnail"]["path"])-1  )=="/") &&  (substr($sd["thumbnail"]["path"], strlen($sd["thumbnail"]["path"])-1 )=="\\") )
		{
			$sd["thumbnail"]["path"] .= "/";

		}
		if( file_exists($sd["thumbnail"]["path"].$sd["thumbnail"]["file"]) && !is_dir($sd["thumbnail"]["path"].$sd["thumbnail"]["file"]))
		{
			// ici on sais que la HD existe deja
			// on supprime l'ancien
			if( @unlink($sd["thumbnail"]["path"].$sd["thumbnail"]["file"]) )
			{
			}
			else
			{
	echo "<center>",_('prod::substitution::erreur : impossible de supprimer l\'ancien document'),"<br><br>";
		echo "<a onClick=\"parent.hideDwnl();\">",_('boutton::fermer'),"</a>";
				die('</body></html>');
			}

		}
	}
	// je vide la table subdefs
	$sql = "DELETE FROM subdef WHERE record_id='".$connbas->escape_string($parm["rid"])."' AND name='thumbnail'";
	$rs = $connbas->query($sql);
	$width = $height = 0 ;
	
	
	
	 
	$newTh = makeNewThumb($_FILES,$pathThumb,$namenewThumb,$baseurl,$size);
	if($newTh!==false && $newTemp=getimagesize($newTh) )
	{		
		$width  = $newTemp[0];
		$height = $newTemp[1];
		
		$mimeExt = giveMimeExt($pathThumb.$namenewThumb);
		
		$sql = "INSERT INTO subdef (    record_id   ,    name   ,   path    ,     file     ,  baseurl     , inbase, width   , height,       mime           ,        size        ) VALUES";
		$sql .=                   "('".$connbas->escape_string($parm["rid"])."', 'thumbnail', '".$connbas->escape_string($pathThumb)."' , '".$connbas->escape_string($namenewThumb)."' , '".$connbas->escape_string($baseurl)."'   , '1', '".$connbas->escape_string($width)."'  ,  '".$connbas->escape_string($height)."' , '".$connbas->escape_string($mimeExt['mime'])."', '".$connbas->escape_string(filesize($newTh))."')";
		if($connbas->query($sql))
		{
			answer::logEvent($sbas_id,$parm['rid'],'substit','thumb','');
		}
		  
		// On flag que l'imagette a ete substituee
		$sql = "UPDATE subdef SET substit=substit|1 WHERE record_id='".$connbas->escape_string($parm["rid"])."' AND name='thumbnail' ";
		if($connbas->query($sql))
		{
			$sql = "UPDATE record SET moddate=now() WHERE record_id='".$connbas->escape_string($parm["rid"])."'";
			$connbas->query($sql);
		} 
 
		$cache_thumbnail = cache_thumbnail::getInstance();
		$cache_thumbnail->delete($sbas_id,$parm["rid"]);
	}
	else
	{
	echo "<center>",_('prod::substitution::erreur : impossible d\'ajouter ce document'),"<br><br>";
		echo "<a onClick=\"parent.hideDwnl();\">",_('boutton::fermer'),"</a>";
		die('</body></html>');
	}
}
else
{
	echo "<center>",_('prod::substitution::erreur : impossible d\'ajouter ce document'),"<br><br>";
		echo "<a onClick=\"parent.hideDwnl();\">",_('boutton::fermer'),"</a>";
	die('</body></html>');
}

printf(  "<center>%s<br><br>", _('prod::substitution::document remplace avec succes'));
		echo "<a onClick=\"parent.hideDwnl();\">",_('boutton::fermer'),"</a>";


function makeNewThumb($_FILES,$pathThumb,$namenewThumb,$baseurl,$sdsize)
{
	require_once(GV_RootPath."lib/index_utils2.php");
	if(file_exists($pathThumb.$namenewThumb))
		@unlink($pathThumb.$namenewThumb);
	
	$system = p4utils::getSystem();
	$err ="";
	
	if($temp = getimagesize($_FILES["newThumb"]["tmp_name"]) )
	{
	 	# DANS $temp :  [0]=> int(1600)					 
		#			  [1]=>int(1194)					  
		#			  [2]=>int(2)					  
		#			  [3]=> string(26) "width="1600" height="1194""					 
		#			  ["bits"]=> int(8)					 
		#			  ["channels"]=>int(3)					  
		#			  ["mime"]=>string(10) "image/jpeg"		*/
		####
		#		ATTENTION
		#		Si JPEG
		#		$temp["channels"]  == 3    ===> image RGB
		#		$temp["channels"]  == 4    ===> image CMYK
		
		$CMYK = false;
		if(isset($temp["channels"]) && $temp["channels"]==4 )
			$CMYK = true;
			
		if($temp[1]<$sdsize && $temp[0]<$sdsize)
		{
			if($temp[1]>$temp[0])
				$plusGrand = $temp[1];
			else
				$plusGrand = $temp[0];
			if($sdsize>$plusGrand)
				$sdsize=$plusGrand;
		} 
		
		
		#***** AVEC SIPS *****
		if( $system == "DARWIN" && !$CMYK )
		{ 
			$cmd = "sips -s format jpeg -Z $sdsize '".$_FILES["newThumb"]["tmp_name"]."' --out '" .$pathThumb.$namenewThumb."'";
 
			$descriptorspec = array(0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
									1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
									2 => array("pipe", "w") // stderr is a file to write to
									);
			$process = proc_open($cmd, $descriptorspec, $pipes);
			if (is_resource($process))
			{
				fclose($pipes[0]);
				$err="";
				while (!feof($pipes[1]))
					$out = fgets($pipes[1], 1024);
				fclose($pipes[1]);
				while (!feof($pipes[2]))
					$err .= fgets($pipes[2], 1024);
				fclose($pipes[2]);
				$return_value = proc_close($process); 
				if($err!="")
				{  
					if( file_exists($pathThumb.$namenewThumb) )
						unlink($pathThumb.$namenewThumb);
				}
				else
				{
					return ($pathThumb.$namenewThumb);
				}
			}
		}
		
		
		#***** AVEC IMAGICK ***** 
		if(((!file_exists($pathThumb.$namenewThumb)  ) || $err!="") && GV_imagick!="")
		{			 
			$mimeExt = giveMimeExt($_FILES["newThumb"]["tmp_name"]);
			
			if($mimeExt['mime']=="application/pdf" || $_FILES["newThumb"]["type"]=="application/pdf" || mb_strtolower(substr($_FILES["newThumb"]["name"],-4)==".eps"))
			{
				$cmd = GV_imagick . " -strip -quality 75 -geometry ";
				$cmd .= $sdsize."x".$sdsize;
				$cmd .= " -define jpeg:preserve-settings -resize ";
				$cmd .= $sdsize."x".$sdsize;
				$cmd .=" -colorspace RGB ";
				$cmd .= "  \"".$_FILES["newThumb"]["tmp_name"] ."[0]\" \"".$pathThumb.$namenewThumb."\"";
			}
			else 
			{
				$cmd = GV_imagick . " -strip -quality 75 -size ";
				$cmd .= $sdsize."x".$sdsize;
				$cmd .= " -define jpeg:preserve-settings -resize ";
				$cmd .= $sdsize."x".$sdsize;
				$cmd .=" -colorspace RGB ";

				// attention, au cas ou il y aurait des espaces dans le path, il faut des quotes
				// windows n'accepte pas les simple quotes				
				if( $_FILES["newThumb"]["type"]=="image/tiff")
					$cmd .= " -quiet \"".$_FILES["newThumb"]["tmp_name"] ."\" \"".$pathThumb.$namenewThumb."\"";
				else
					$cmd .= "  \"".$_FILES["newThumb"]["tmp_name"] ."\" \"".$pathThumb.$namenewThumb."\"";
				 
			}  
			$descriptorspec = array(0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
									1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
									2 => array("pipe", "w") // stderr is a file to write to
									);
			$process = proc_open($cmd, $descriptorspec, $pipes);
			if (is_resource($process))
			{
				fclose($pipes[0]);
				$err="";
				while (!feof($pipes[1]))
					$out = fgets($pipes[1], 1024);
				fclose($pipes[1]);
				while (!feof($pipes[2]))
					$err .= fgets($pipes[2], 1024);
				fclose($pipes[2]);
			   	$return_value = proc_close($process);

				if($err!="")
			   	{
					$mimeExt = giveMimeExt($_FILES["newThumb"]["tmp_name"]);
					
			   		if( file_exists($pathThumb.$namenewThumb) && ($mimeExt['mime']=="application/pdf" || $_FILES["newThumb"]["type"]=="application/pdf") )
						return($pathThumb.$namenewThum);
					else
					{
						if( file_exists($pathThumb.$namenewThumb) )
							unlink($pathThumb.$namenewThumb);
					}
				}
				else 
					return($pathThumb.$namenewThumb);
			}
		}
		
		
		#***** AVEC GD ***** 
		if((!file_exists($pathThumb.$namenewThumb)) || $err!="")
		{	
			$imag_original = @imagecreatefromjpeg($_FILES["newThumb"]["tmp_name"]);
			if($imag_original)
			{
				$larg_act = imagesx($imag_original);
				$haut_act = imagesy($imag_original);

				$haut_futur = $larg_futur = 0 ;
				// cherche le max des 2 valeurs pour creer le coeff de resize
				if($larg_act > $haut_act)
					$haut_futur = (int)(($haut_act/$larg_act) * ($larg_futur = $sdsize));
				else
					$larg_futur = (int)(($larg_act/$haut_act) * ($haut_futur = $sdsize));			

				if($larg_futur > $larg_act)
				{
					// le doc d'origine est plus petit que le subdef : pas de resample
					$img_mini = imagecreatetruecolor($larg_futur=$larg_act, $haut_futur=$haut_act);
					imagecopy($img_mini, $imag_original, 0,0,0,0, $larg_act, $haut_act);
					// imagejpeg($imag_original, $physdpath. "/" . $newname, 90);

				}
				else
				{
					 
					$img_mini = imagecreatetruecolor($larg_futur, $haut_futur); 
					imagecopyresampled($img_mini, $imag_original, 0,0,0,0, $larg_futur, $haut_futur, $larg_act, $haut_act);
					 
				} 
				imagejpeg($img_mini, $pathThumb.$namenewThumb, 90);
				imagedestroy($img_mini);
				if( file_exists($pathThumb.$namenewThumb) )
					return($pathThumb.$namenewThumb);
			}
		}	 
	} 
	return false;
}

?>
</body>
</html>
