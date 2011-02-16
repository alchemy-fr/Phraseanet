<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
phrasea::headers();

$request = httpRequest::getInstance();
$parm = $request->get_parms("lst","ACT","subjectmail","textmail","lstusr","nameBask","ccmail","rotation","chu","CHIM","chimlist");

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
<hml>
<head>
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/jquery-ui.css" />
<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
<script type="text/javascript" src="/include/minify/g=modalBox"></script>
</head>
<body>

<?php
############## ACT SEND ######################
if($parm["ACT"] == "SEND")
{		
	
	if($parm['rotation'] == null)
		exit('Choose a rotation !');

	$reloadTemp = true;

	$conn = connection::getInstance();
	
	$system = p4utils::getSystem();
	
	$orderBas = null;
	
	// d'abord on re-trie par base		
	$lst = explode(";", $parm["lst"]);	
	
	$mylist = null;
	foreach($lst as $basrec)
	{
		if($basrec!="")
		{
			$mybasrec = explode("_", $basrec);
			$mylist[$mybasrec[0]][] = $basrec;
		}
	}
	
	$connDist = null;
	$sqlThumb = "";
	$sqlPrev = "";
	
	$allChimlist = null;
	
	if($parm["chimlist"])
	{
		$chilist2 = explode(";", $parm["chimlist"]);	
		foreach($chilist2 as $oneBasRid)
		{
			$oneBasRid2 = explode("@", $oneBasRid);
			if($oneBasRid2 && count($oneBasRid2)==2)
				$allChimlist[$oneBasRid2[1]] = $oneBasRid2[0];
		}
	}
	
	$cache_thumb = cache_thumbnail::getInstance();
	$cache_prev = cache_preview::getInstance();
	
	foreach($ph_session["bases"] as $onebase)
	{
		set_time_limit(60);
		$connDist = null;
		$sqlThumb = "";
		$sqlPrev = "";
		$connDist = connection::getInstance($onebase['sbas_id']);
		
		foreach($onebase["collections"] as $onecoll)
		{
			if(isset($mylist[$onecoll["base_id"]]))
			{
				
				if($connDist)
				{
					foreach($mylist[$onecoll["base_id"]] as $basrec )
					{
						$basrec2 = explode("_", $basrec);
						$sd = phrasea_subdefs($ses_id, $basrec2[0],$basrec2[1]);
						
						if(!isset($sd["document"]))
						{
							break;
						}	
							
						$basrec = explode("_", $basrec);
						$rotation = Rotation( $ses_id, $basrec2[0],$basrec2[1] , "thumbnail" );
						if($rotation === true)
						{						
							if($sqlThumb!="")
								$sqlThumb.=",";
							$sqlThumb .= $basrec2[1];

							$url = '';
							
							$direct = false;
							if(isset($sd['thumbnail']))
							{
								if($sd['thumbnail']['baseurl'] != '')
									$url = p4string::addEndSlash($sd['thumbnail']['baseurl']).$sd['thumbnail']['file'].'?u='.rand();
								elseif($basrec[2] == 'image')
								{
									$direct = true;
									$url = "/include/directprev.php?bas=".$basrec[0]."&rec=".$basrec[1];
								}
								echo answer::correctScreenSubs($basrec[0], $basrec[1],$url,$direct,$sd['thumbnail']['height'],$sd['thumbnail']['width'],false,true);
							}
							$reloadTemp = false;
						
						}	
						
						$rotation = Rotation( $ses_id, $basrec2[0],$basrec2[1]  , "preview" );
						if($rotation === true)
						{
							if($sqlPrev!="")
								$sqlPrev.=",";
							$sqlPrev .= $basrec2[1];
							
							$cache_thumb->delete($onebase['sbas_id'],$basrec2[1]);
							$cache_prev->delete($onebase['sbas_id'],$basrec2[1]);
						}		
					}
				}
			}
		}
		
		
		if($connDist!=null && $sqlThumb!="")
		{
			$sql = "update subdef set height=@width , width=@height where ((@width:=width) and  (@height:=height)) and record_id IN (".$sqlThumb.") and name='thumbnail'";
			$connDist->query($sql);
			$sql = "UPDATE record SET moddate=NOW() WHERE record_id IN (".$sqlThumb.")";
			$connDist->query($sql);
			
		}
		if($connDist!=null && $sqlPrev!="")
		{
			$sql = "update subdef set height=@width , width=@height where ((@width:=width) and  (@height:=height)) and record_id IN (".$sqlPrev.") and name='preview'";
			$connDist->query($sql);
			$sql = "UPDATE record SET moddate=NOW() WHERE record_id IN (".$sqlPrev.")";
			$connDist->query($sql);
		}
	}	
	
?>
<script type="text/javascript">
	parent.hideDwnl();
</script>
	<div style="text-align:center;"><input onclick="parent.hideDwnl();" value="<?php echo _('boutton::fermer')?>" type="button" class="input-button" /></div>
<?php
}
?>
</body>
<?php
############## END ACT SEND ######################

function Rotation($session, $basrec0 ,$basrec1, $typeImg)
{
	GLOBAL $system, $parm;
	
	$rot_value = in_array($parm['rotation'], array('-90','90','180')) ? $parm['rotation'] : false;
	
	if(!$rot_value)
		return false;
		
	set_time_limit(60);

	$ret = false;
	$sd = phrasea_subdefs($session, $basrec0 , $basrec1,$typeImg);
	
	$rotation = false;
	
	if(isset($sd[$typeImg]))
	{		
		$fichier = p4string::addEndSlash($sd[$typeImg]["path"]) . $sd[$typeImg]["file"] ;

		if($rotation==false)
		{
			if(GV_imagick!="")
			{
				// IMAGICK
				
				
				$fichier = p4string::addEndSlash($sd[$typeImg]["path"]) . $sd[$typeImg]["file"] ;
				$cmd = GV_imagick . " -rotate ". $rot_value." ".$fichier." ".$fichier;
				
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
				   	if(trim($err)==="")
				   		return true;
				}
			}
		}
		if($rotation==false && function_exists('imagerotate'))
		{
			// GD
			$fichier = p4string::addEndSlash($sd[$typeImg]["path"]) . $sd[$typeImg]["file"] ;
			$source = imagecreatefromjpeg($fichier);	
			$rot = (int)$rot_value;
			$rot = $rot * -1;
			$source = imagerotate($source, $rot, 0);			
			imagejpeg($source, $fichier, 90);
			imagedestroy($source);
			return true;
		}
	}
	return $ret;
}
?>