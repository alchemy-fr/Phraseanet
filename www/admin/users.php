<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require(GV_RootPath.'lib/countries.php');
$session = session::getInstance();

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	$user = user::getInstance($usr_id);
	if(!$user->_global_rights['manageusers'])
		phrasea::headers(403);
}
else{
	phrasea::headers(403);
}

$APPLY_MODEL = null;
$UPD_USR = null;
$ADD_BASUSR = null;
$ADD_SBASUSR = null;
$UPD_BASUSR = null;
$UPD_SBASUSR = null;
$DEL_BASUSR = null;
$DEL_SBASUSR = null;

function start_element($parser, $name, $attrs)
{
	global $xmlpath;
	global $xmldata;  

  	if(($p = strrpos($name, ":")) !== false)
		$name = substr($name, $p+1);
	$xmlpath .= "/" . $name;
	$xmldata = "";
}

$arraytableusr 		= array("usr_password","usr_sexe","usr_nom","usr_prenom","usr_mail","societe","activite","tel","fax","adresse","cpostal","ville","pays","addrFTP","loginFTP","pwdFTP","destFTP","prefixFTPfolder","fonction","activeFTP","defaultftpdatasent","passifFTP","retryFTP","model_of","seepwd","canchgprofil" , "canchgftpprofil");

							
$arraytablesbasusr 	= array("baschupub"=>"bas_chupub",
							"basmodifth"=>"bas_modif_th",
							"basmanage"=>"bas_manage",
							"basmodifstruct"=>"bas_modify_struct");
							
$arraytablebasusr 	= array("acces" 		=> "acces",

							"actif" 		=> "actif",
							"album" 		=> "canputinalbum",
							"canprev" 		=> "canpreview",
							"water" 		=> "needwatermark",
							"canhd" 		=> "canhd",
							"dlprev" 		=> "candwnldpreview",
							"dlhd" 			=> "candwnldhd",
							"cmd" 			=> "cancmd",
							"addrec" 		=> "canaddrecord",
							"modifrec" 		=> "canmodifrecord",
							"chgstat" 		=> "chgstatus",
							"delrec" 		=> "candeleterecord",
							"imgtools"		=> "imgtools",
							"admin" 		=> "canadmin",
							"report" 		=> "canreport",
							"push" 			=> "canpush",
							"manage" 		=> "manage",
							"modifstruct" 	=> "modify_struct",
							"restrictdwnld" => "restrict_dwnld",
							"monthdwnldmax" => "month_dwnld_max", 
							"remaindwnld" 	=> "remain_dwnld",
							"timelimited" 	=> "time_limited",
							"limitedfrom" 	=> "limited_from",
							"limitedto" 	=> "limited_to",
							
							
							
							"vandand" 	=> "vandand",
							"vandor" 	=> "vandor",
							"vxorand" 	=> "vxorand", 
							"vxoror" 	=> "vxoror");
		
		
function end_element($parser, $name)
{
	global $xmldata;  
	global $arraytableusr, $arraytablesbasusr,$arraytablebasusr;
	global $UPD_USR, $ADD_BASUSR, $ADD_SBASUSR,  $UPD_BASUSR, $UPD_SBASUSR, $DEL_BASUSR, $DEL_SBASUSR, $APPLY_MODEL;
	 
	if($name!="modif")
	{
		if (in_array ($name, $arraytableusr)) 
		{
//			echo "<br><b>USR</b>";echo "<br>name : $name = $xmldata<hr>" ;
			$modifvalue = $xmldata ;
			for($i=0;$i<strlen($modifvalue);$i++)
			{
				if($modifvalue[$i]=="%")
				{   
					$modifvalue = substr($modifvalue,0,$i) . chr( hexdec(substr($modifvalue,$i,3))) . substr($modifvalue,$i+3) ;				
				}
			}
			$UPD_USR[$name] = utf8_encode(urldecode($modifvalue));
		}
		else
		{		 
			if($name=="applymodel")
			{
				$APPLY_MODEL = $xmldata;
			}
			elseif( ($l=strpos($name,"_")) && (array_key_exists(substr($name,0,$l),$arraytablesbasusr))  )
			{
				$key = substr($name,0,$l);
			
				$modifvalue = $xmldata ;
				for($i=0;$i<strlen($modifvalue);$i++)
				{
					if($modifvalue[$i]=="%")
					{   
						$modifvalue = substr($modifvalue,0,$i) . chr( hexdec(substr($modifvalue,$i,3))) . substr($modifvalue,$i+3) ;				
					}
				}
				
				$UPD_SBASUSR[substr(strchr($name,"_"),1)][$arraytablesbasusr[$key]] = $modifvalue;
				
			}
			elseif( ($l=strpos($name,"_")) && (array_key_exists(substr($name,0,$l),$arraytablebasusr))  )
			{
				$key = substr($name,0,$l);
				$tmp = explode("_",substr(strchr($name,"_"),1)) ;
				
				if( $arraytablebasusr[$key]=="acces" )
				{
					 
					if($xmldata=="1")
					{
						$ADD_SBASUSR[$tmp[0]]=true;
						$ADD_BASUSR[$tmp[0]][$tmp[1]]=true;
					}	
					else
					{
						$DEL_SBASUSR[$tmp[0]]=true;
						$DEL_BASUSR[$tmp[0]][$tmp[1]]=true;
					}
				}
				else
				{
					$modifvalue = $xmldata ;
					for($i=0;$i<strlen($modifvalue);$i++)
					{
						if($modifvalue[$i]=="%")
						{   
							$modifvalue = substr($modifvalue,0,$i) . chr( hexdec(substr($modifvalue,$i,3))) . substr($modifvalue,$i+3) ;				
						}
					}
					$UPD_BASUSR[$tmp[0]][$tmp[1]][$arraytablebasusr[$key]] = $modifvalue;
				}				
			}
			else
			{
	//			echo "<br><font color=\"#FF0000\">name : $name = $xmldata</font><hr>" ;
			}
		}
	}
}
function character_data($parser, $data)
{	
	global $xmldata;
	$xmldata .= $data;	
}


$conn = connection::getInstance();
if(!$conn)
{
	phrasea::headers(500);
}

phrasea::headers();



$request = httpRequest::getInstance();
$parm = $request->get_parms("srt", "ord", "act", "p0", "p1","p2","p3" ,"p4" , "p5" , "p6" );


$countries = getCountries($lng);

if( $parm["p6"]==NULL || $parm["p6"]=="")
	$parm["p6"]="2";
	
	
if(!$parm["ord"] || ($parm["ord"]!="ASC" && $parm["ord"]!="desc"))
	$parm["ord"]="asc";

$refreshfinder = false;


$out = "";

 
// les bases que je peux administrer : p0=base_id (base); p1=coll_id (collection)
// on construit une liste de base_id en fct de p0 et p1, ce qui permet de verifier que p1 est bien une collection de p0
// on en profite pour creer un tableau pour acceder facilement aux collections par id
$baslist = $basnotlist = array();

$baslibs = "";
if($parm['p0'])
{
	// users d'une base
	$rowbase = null;
	$rowcoll = null;
	// la base p0, si j'ai le droit de l'administrer
	$sql = "SELECT bas.* , sbas.dbname
			FROM (((bas NATURAL JOIN basusr) NATURAL JOIN usr) INNER JOIN sbas ON sbas.sbas_id = bas.sbas_id) 
			WHERE bas.sbas_id='".$conn->escape_string($parm['p0'])."' AND usr.usr_id='".$conn->escape_string($usr_id)."'";
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs))
			$rowcoll = $row;
		$conn->free_result($rs);
	}
	if($rowcoll)
	{
		$ncolls = 0;
		$connbas = connection::getInstance($row['sbas_id']);
		
		// toutes les bases(collections) sur la meme base, que j'ai le droit d'administrer
			$sql = sprintf("SELECT bas.* FROM ((bas NATURAL JOIN basusr) NATURAL JOIN usr) WHERE bas.sbas_id='".$conn->escape_string($parm['p0'])."' AND usr.usr_id=%s AND basusr.canadmin=1", $usr_id);
		if($parm['p1'])			// on demande juste une collection, ce qui va verifier qu'elle est bien dans la base p0
			$sql .= " AND bas.base_id='" . $conn->escape_string($parm['p1'])."'";
		if($rs = $conn->query($sql))
		{
			while($rowcolls = $conn->fetch_assoc($rs))
			{
				$ncolls++;
				$baslist[] = $rowcolls["base_id"];
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
		if($parm['p1'])
			$baslibs = '<h2>'.$baslibs.'<h2>'._('phraseanet::utilisateurs');
		else
		{
			$baslibs = "<h2>".$rowcoll["dbname"]."</h2>"._('phraseanet::utilisateurs');
		}			
	}
}
else
{
	
	// toutes les bases que je peux administrer
	$sql = "SELECT bas.base_id, sbas.host, sbas.port, sbas.dbname 
			FROM (((usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) 
				INNER JOIN bas ON basusr.base_id=bas.base_id) 
				INNER JOIN sbas ON sbas.sbas_id=bas.sbas_id) 
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
			$baslist[] = $conn->escape_string($rowcolls["base_id"]);
		}
		$conn->free_result($rs);
		if($nbas > 1)
			$baslibs = '<h2>'.$baslibs.'</h2>'._('phraseanet::utilisateurs') ; // $baslibs = "user(s) des bases " . $baslibs . "";
		else
			$baslibs = '<h2>'.$baslibs.'</h2>'._('phraseanet::utilisateurs') ; //$baslibs = "user(s) de la base " . $baslibs . "";
	}
}

				
// on filtre les bases administrables
$sql = "SELECT base_id FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) WHERE usr.usr_id='".$conn->escape_string($usr_id)."' AND basusr.canadmin=1 AND (basusr.base_id='".implode("' OR basusr.base_id='",$baslist)."') ORDER BY usr.usr_id";

	$baslist = array();
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$baslist[] =$row["base_id"];
		}
		$conn->free_result($rs);
	}
//ok j'ai mes bases, je vois les bases qui ne sont pas dedans
$sql = 'SELECT distinct base_id FROM basusr WHERE base_id!="'.implode('" AND base_id!="',$baslist).'"';

	$basnotlist = array();
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$basnotlist[] =$row["base_id"];
		}
		$conn->free_result($rs);
	}
 
if( $parm["act"]=="UPD" && $parm["p3"]!=null ) // update d'un ou plusieurs users
{
	$xml = "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?"."><modif>" . trim($parm["p3"]) . "</modif>" ;
	$ismymodel = false; 	// en ref de "mymodel" dans le xml
	$ismymodelvalue= null; 	// value contenue dans la branche "mymodel" du xml
	$applymodel = false;	// booleen pour savoir si on applique un modele
	$applymodelvalue= null;	// usr_id du modele a appliquer
	
	
	$encoding = "UTF-8";
	$xmlp = xml_parser_create($encoding);
	// Set the options for parsing the XML data.
	// xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parser_set_option($xmlp, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($xmlp, XML_OPTION_TARGET_ENCODING, $encoding);
	// Set the object for the parser.
	// xml_set_object($xmlp, $this);
	// Set the element handlers for the parser.
	xml_set_element_handler($xmlp, 'start_element','end_element');
	xml_set_character_data_handler($xmlp,'character_data');
	// Parse the XML file.
	$xmlpath = $xmldata = "";		// variables globales
	if(!xml_parse($xmlp, $xml, true))
	{    // Display an error message.
	    $err = sprintf('XML error on line %d: %s', xml_get_current_line_number($xmlp), xml_error_string(xml_get_error_code($xmlp)));
		$xml = null;
	}
	
	 
	$nbusr=0;	
	// calcul propre du nb d'utilisateurs a mettre a jour
	$tabusers = array();
	$sql = "SELECT usr_id from usr WHERE usr.usr_id in (".$parm["p2"].")";
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$nbusr++;
			$tabusers[$row["usr_id"]]=$row["usr_id"];
		}
	}

	if($APPLY_MODEL!=null)
	{
		// on applique un model
		// meme droit dans sbas
		// meme droits dans bas
		$sql = "SELECT  sbasusr.bas_chupub,sbasusr.bas_modif_th,sbasusr.bas_manage,sbasusr.bas_modify_struct,basusr.base_id,bas.sbas_id,basusr.canpreview,	basusr.canhd,	basusr.canputinalbum,	basusr.candwnldhd,	basusr.candwnldsubdef,	basusr.candwnldpreview,
						basusr.cancmd,	basusr.canadmin,	basusr.actif,	basusr.canreport,	basusr.canpush,	 basusr.mask_and,	basusr.mask_xor,
						basusr.restrict_dwnld,	basusr.month_dwnld_max,	basusr.remain_dwnld,	basusr.time_limited,	basusr.limited_from,basusr.limited_to,
						basusr.canaddrecord,	basusr.canmodifrecord,	basusr.candeleterecord,	basusr.chgstatus,basusr.imgtools,basusr.manage,	basusr.modify_struct,
						basusr.needwatermark FROM ((basusr inner join bas on(bas.base_id=basusr.base_id)) inner JOIN sbasusr on(bas.sbas_id=sbasusr.sbas_id and sbasusr.usr_id=basusr.usr_id)) where basusr.usr_id=".$APPLY_MODEL ." AND (basusr.base_id='".implode("' OR basusr.base_id='",$baslist)."') " ;
 
		// on remplira  $ADD_BASUSR, $ADD_SBASUSR,$UPD_BASUSR 
		if( $rs = $conn->query($sql) )
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$ADD_SBASUSR[$row["sbas_id"]]=true;
				$ADD_BASUSR[$row["sbas_id"]][$row["base_id"]]=true;
				foreach( $row as $key=>$val)
				{
					if( $key=="bas_chupub" || $key=="bas_modif_th" || $key=="bas_manage" || $key=="bas_modify_struct" )	
						$UPD_SBASUSR[$row["sbas_id"]][$key]=$val;						
					elseif($key!="sbas_id" && $key!="base_id"  )
						$UPD_BASUSR[$row["sbas_id"]][$row["base_id"]][$key]=$val;
					
				}
				
			}
			$conn->free_result($rs);
		}
		$sql = "SELECT usr_login as lastModel from usr where usr_id=".$APPLY_MODEL;
		if( $rs = $conn->query($sql) )
		{
			if($row = $conn->fetch_assoc($rs))
			{
				foreach( $row as $key=>$val)
				{
					$UPD_USR[$key]=$val;					
				}
				
			}
			$conn->free_result($rs);
		}
		
	}
	
	// mise a jour de USR
	if($UPD_USR!=NULL)
	{
		$sql1 = "";
		foreach($UPD_USR as $key=>$val)
		{
			if($sql1!="")
				$sql1.=",";
			if($key == 'usr_password')
				$val = hash('sha256',$val);
			if($key == 'usr_mail')
				$sql1 .= $key."=".(trim($val) != '' ? "'".$conn->escape_string($val)."'" : "null");
			else
				$sql1 .= $key."='".$conn->escape_string($val)."'";
		}		 
		$sql = "UPDATE usr SET " . $sql1 . ", usr_modificationdate = NOW() WHERE usr_id IN (".$parm["p2"].")";
	 
		$conn->query($sql);	
	}

	if($ADD_BASUSR!=NULL)
	{
		foreach($ADD_BASUSR as $onesbas=>$array)
		{
			foreach($array as $onebas=>$bool)
			{
				foreach($tabusers as $oneusrid)
				{
					$sql = "INSERT INTO basusr (id, base_id, usr_id, canpreview, canhd, canputinalbum, candwnldhd, candwnldsubdef, candwnldpreview, cancmd, canadmin, actif, canreport, canpush, creationdate, basusr_infousr, mask_and, mask_xor, restrict_dwnld, month_dwnld_max, remain_dwnld, time_limited, limited_from, limited_to, canaddrecord, canmodifrecord, candeleterecord, chgstatus, lastconn, imgtools, manage, modify_struct, bas_manage, bas_modify_struct, needwatermark) 
							VALUES (NULL, '".$conn->escape_string($onebas)."','".$conn->escape_string($oneusrid)."', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, now(), '', 0, 0, 0, 0, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, 0, 0, 0, '0000-00-00 00:00:00', 0, 0, 0, 0, 0, 0)";
		 			if($conn->query($sql))
		 			{
			 			user::clear_cache($oneusrid);
						$sql = "UPDATE usr SET usr_modificationdate = NOW() WHERE usr_id IN (".$parm["p2"].")";
						$conn->query($sql);
		 			}	
				}
			}
		}	
	}
	
	if($ADD_SBASUSR!=NULL)
	{
		foreach($ADD_SBASUSR as $onesbas=>$bool)
		{
			foreach($tabusers as $oneusrid)
			{
				$sql = "INSERT INTO sbasusr (sbasusr_id, sbas_id, usr_id, bas_manage, bas_modify_struct) VALUES (NULL, '".$conn->escape_string($onesbas)."', '".$conn->escape_string($oneusrid)."', 0, 0)";
				if($conn->query($sql))
	 			{	
		 			user::clear_cache($oneusrid);
					$sql = "UPDATE usr SET usr_modificationdate = NOW() WHERE usr_id IN (".$parm["p2"].")";
					$conn->query($sql);
	 			}
			}
		}		 
		
	}

	
	if($UPD_BASUSR!=NULL)
	{
		foreach($UPD_BASUSR as $onesbas=>$array)
		{
			foreach($array as $onebas=>$arrayval)
			{
				$sql1=""; 
				if( isset($arrayval["vandand"]) && isset($arrayval["vandor"]) &&  isset($arrayval["vxorand"]) && isset($arrayval["vxoror"]) )
				{
					 
						$vhex = array();
						foreach(array("vandand", "vandor", "vxorand", "vxoror") as $f)
						{
							$vhex[$f] = "0x";
							while(strlen($arrayval[$f])<64)
								$arrayval[$f] = "0".$arrayval[$f];
						}							
						foreach(array("vandand", "vandor", "vxorand", "vxoror") as $f)
						{
							while(strlen($arrayval[$f])>0)
							{
								$valtmp = substr($arrayval[$f], 0, 4);
								$arrayval[$f] = substr($arrayval[$f], 4);
								$vhex[$f] .= dechex(bindec($valtmp));	
							}
						}
						$sql1 = " mask_and=((mask_and & ".$vhex["vandand"].") | ".$vhex["vandor"].") , mask_xor=((mask_xor & ".$vhex["vxorand"].") | ".$vhex["vxoror"].") " ;
				}
		
				foreach($arrayval as $field=>$fldval)
				{
					if($field!="vandand" && $field!="vandor" && $field!="vxorand" && $field!="vxoror"  )
					{
						if($sql1!="")$sql1.=", "; 
						if( $field=='limited_from' || $field=='limited_to')
							$sql1.="$field='$fldval'";
						else
							$sql1.="$field=$fldval";
					}
					 
						
						
					 
				}	
				$sql = "UPDATE basusr SET ". $sql1 . " WHERE base_id='".$conn->escape_string($onebas)."' AND usr_id IN(".$parm["p2"].")"; 	
	 			if($conn->query($sql))
	 			{
	 				$usrs = explode(',',$parm['p2']);
	 				foreach($usrs as $u)
	 				{
			 			user::clear_cache(trim($u));
	 				}
					$sql = "UPDATE usr SET usr_modificationdate = NOW() WHERE usr_id IN (".$parm["p2"].")";
					$conn->query($sql);
	 				
	 			}
			}
		}	
	}
	if($UPD_SBASUSR!=NULL)
	{
		foreach($UPD_SBASUSR as $onesbas=>$array)
		{
			$sql1=""; 			
			foreach($array as $field=>$fldval)
			{
				if($sql1!="")$sql1.=", "; 
				$sql1.="$field=$fldval";
			}	
			$sql = "UPDATE sbasusr SET ". $sql1 . " WHERE sbas_id='".$conn->escape_string($onesbas)."' AND usr_id IN(".$parm["p2"].")"; 
			if($conn->query($sql))
			{
				$usrs = explode(',',$parm['p2']);
				foreach($usrs as $u)
	 			{
		 			user::clear_cache(trim($u));
	 			}	
				$sql = "UPDATE usr SET usr_modificationdate = NOW() WHERE usr_id IN (".$parm["p2"].")";
				$conn->query($sql);
			}		
		}	
	}
	if($DEL_BASUSR!=NULL)
	{
		foreach($DEL_BASUSR as $onesbas=>$array)
		{
			foreach($array as $onebas=>$bool)
			{
				$sql = "DELETE FROM basusr WHERE base_id='".$conn->escape_string($onebas)."' AND usr_id IN(".$parm["p2"].")"; 
				if($conn->query($sql))
				{
					$usrs = explode(',',$parm['p2']);
					foreach($usrs as $u)
		 			{
			 			user::clear_cache(trim($u));
		 			}	
				}							
			}
		}		
	}
	if($DEL_SBASUSR!=NULL)
	{
		// on doit regarder
		foreach($DEL_BASUSR as $onesbas=>$array)
		{
			foreach($tabusers as $oneusrid)
			{
				$sql = "select bas.* from basusr inner join bas on (basusr.base_id=bas.base_id) where usr_id='".$conn->escape_string($oneusrid)."' AND bas.sbas_id='".$conn->escape_string($onesbas)."'";
				if($rs=$conn->query($sql))
				{
					if($conn->num_rows($rs)==0)
					{
						$sql = "DELETE FROM sbasusr where usr_id='".$conn->escape_string($oneusrid)."' AND sbas_id='".$conn->escape_string($onesbas)."'";
						if($conn->query($sql))
						{
					 		user::clear_cache($oneusrid);
						}	
					}
				}
			}
		}		
	}
		
	
}
?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
		<style type="text/css">
		BODY
		{
			margin:10px;
			font-size:11px;
		}
		.divTop
		{  
			OVERFLOW: hidden; 
			height:18px; 
		}
		#tableaumid tr{
			color:black;
		}
		.tableTop
		{
			WIDTH: 100%;
			TEXT-ALIGN: center; 
			font-size:10px;
			align:center;
			background-color:#CCCCCC;
			COLOR:#000;
			TABLE-LAYOUT:fixed; 
			overflow:hidden;
			border:0px;
		
		} 
		
		
		.divCenter
		{ 
			background-color : #FFFFFF; 
			RIGHT: 3px; 
			OVERFLOW: auto; 
			WIDTH: 100%; 
			POSITION: relative; 
			HEIGHT: 100%;
			left:0px;
			text-align:left;			
		}
		.tableCenter
		{
			TABLE-LAYOUT: fixed;
			WIDTH: 100%;
			
			position:relative; 
			top:0px;
			left:0px;
			TEXT-ALIGN: center;
			align:center;
			background-color:#FFFFFF;
			cursor:default;
			border-top ::#ff0000 1px solid;
			font-size:9px;
		}
		
		.classdivtable
		{ 
			background-color : #FFFFFF; 
			WIDTH: 100%;
			height:500px;
			text-align:left
		}
		
		.tdTableCenter
		{
			OVERFLOW: hidden; 	
			HEIGHT: 25px;
			text-align:center;	
		}
		
		* 
		{
			margin:0; 
			padding:0;
		}
		#tableau table
		{
		}
		#tableau td, #tableau th
		{
			border-right:#CCCCCC 1px solid ;
		}
		.thtableTop
		{
			border-left :#AAAAAA 1px solid;
		}
		
		.trlistsel
		{
			background-color : #7e82bb; 
			color:#ffffff;
			height:20px;
			border-top :#FFFFFF 1px solid;
		}
		.g
		{
			background-color : #F4F4F4; 
			height:20px;
		}
		.b
		{
			background-color : #FFFFFF; 
			height:20px; 
			
		}
		.selfilter
		{
		width:140px;
		border:#cccccc 1px solid;
		font-size:10px;
		}
		
		</style>
		<script type="text/javascript"> 


		/*  anti selection de texte */
		function disableselect(e)
		{	return false;	}
		function reEnable()
		{	return true;	} 
//		document.onselectstart=new Function ("return false")
//		if (window.sidebar)
//		{
//			document.onmousedown=disableselect;
//			document.onclick=reEnable;
//		}
		
		
		var allgetID = new Array ;
		var total = 0;	
//		function tableScroll(theTable) 
//		{
//			var TableId = theTable.id.replace("_center", "");
//			var tableTop = document.getElementById(TableId+"_top");
//		}
		function addEvent(obj, evType, fn, useCapture)
		{
			if (obj.addEventListener)
			{
				obj.addEventListener(evType, fn, useCapture);
				return true;
			} 
			else 
			{
				if (obj.attachEvent)
				{
					var r = obj.attachEvent("on"+evType, fn);
					return r;
				} 
				else 
				{
					alert("Handler could not be attached");
				}
			}	
		}
		 
		/* Chargement de tt les elements dans un tableau pour un acces plus rapide */
		function scandom(node, depth)
		{
			var n;
			if(!node)
				return;
			if(node.id)
			{
				allgetID[node.id] = node;
				//node.style.visibility = "hidden";
				total++;
			}
			for(n=node.firstChild; n; n=n.nextSibling)
			{
				if(n.nodeType && n.nodeType == 1)
					scandom(n, depth+1);
			}
		}
		
		window.onload=function()
		{
			redrawme();
		//	scan(); 
			document.getElementById("iddivloading").style.display = "none";
			nofocus();
		}
		function scan()
		{
			if(document.all)
				scandom(document.documentElement, 0);
			else
			{
				allccuser = document.getElementsByName("ccuser");
				for (var i=0; i<allccuser.length;i++) 
				{
					allgetID[allccuser[i].id] = allccuser[i];
					total++ ;
				}
			}
		
		}
		
		function view(typeDiv)
		{
			
			switch (typeDiv)
			{
				case "RIGHTS":
					if( document.getElementById( "divRights") )
					{
						document.getElementById( "divRights").style.visibility = "visible";
						document.getElementById( "divRights").style.display = "";			
					}
				
					if( oo=returnElement("genecancel") )	
						oo.style.visibility = "visible" ;
					if( oo=returnElement("genevalid") )	
						oo.style.visibility = "visible" ;
				break;
				
				
			}
		}
		
		
		var pass=false;
		function verify()
		{
			
			/***************************************************/
			if(document.all)
			{ 
				if(document.documentElement.clientHeight)
					bodyH = document.documentElement.clientHeight - 5 ;			
				else
					bodyH = document.body.clientHeight - 5 ;
				scrollLeft = null;
				if(document.documentElement.scrollLeft)
					scrollLeft = document.documentElement.scrollLeft;
				else
					scrollLeft = document.body.scrollLeft;	
				scrolltop = null;
				if(document.documentElement.scrollTop)
				{
					scrolltop = document.documentElement.scrollTop;
				 document.documentElement.scrollTop = 0 ;
				}
				else
				{
					scrolltop = document.body.scrollTop;
					document.body.scrollTop=0;
					
				}
			}
			else
			{
				bodyH =  parent.window.document.clientHeight;	
				if(!bodyH)
					if(document.documentElement.clientHeight)
						bodyH = document.documentElement.clientHeight - 5 ;			
					else
						bodyH = document.body.clientHeight - 5 ;
				scrollLeft = null;
				if(document.documentElement.scrollLeft)
					scrollLeft = document.documentElement.scrollLeft;
				else
					scrollLeft = document.body.scrollLeft;
				scrolltop = null;		
				if(document.documentElement.scrollTop)
				{
					scrolltop = document.documentElement.scrollTop;
					 document.documentElement.scrollTop = 0;
				}
				else
				{
					scrolltop = document.body.scrollTop;
					document.body.scrollTop=0;
				}
			}
			/***************************************************/
			hauteur =  document.getElementById("spanref").offsetTop;
			
		}
		
		function redrawme()
		{
		// return;
			wb = document.getElementById("divref").offsetWidth;
			if(wb<150)
				wb= 150;
		
		
			document.getElementById("tableau_center").style.width = (wb-20)+"px";
			document.getElementById("tableau_top").style.width = (wb-20)+"px";
			document.getElementById("tableau").style.width = (wb-20)+"px";
			document.getElementById("tableaumid").style.width = (wb-20)+"px";
			/***************************************************/
			if(document.all)
			{ 
				if(document.documentElement.clientHeight)
					bodyH = document.documentElement.clientHeight - 5 ;			
				else
					bodyH = document.body.clientHeight - 5 ;
				scrollLeft = null;
				if(document.documentElement.scrollLeft)
					scrollLeft = document.documentElement.scrollLeft;
				else
					scrollLeft = document.body.scrollLeft;	
				scrolltop = null;
				if(document.documentElement.scrollTop)
				{
					scrolltop = document.documentElement.scrollTop;
				 document.documentElement.scrollTop = 0 ;
				}
				else
				{
					scrolltop = document.body.scrollTop;
					document.body.scrollTop=0;
					
				}
			}
			else
			{
				bodyH =  parent.window.document.clientHeight;	
				if(!bodyH)
					if(document.documentElement.clientHeight)
						bodyH = document.documentElement.clientHeight - 5 ;			
					else
						bodyH = document.body.clientHeight - 5 ;
				scrollLeft = null;
				if(document.documentElement.scrollLeft)
					scrollLeft = document.documentElement.scrollLeft;
				else
					scrollLeft = document.body.scrollLeft;
				scrolltop = null;		
				if(document.documentElement.scrollTop)
				{
					scrolltop = document.documentElement.scrollTop;
					 document.documentElement.scrollTop = 0;
				}
				else
				{
					scrolltop = document.body.scrollTop;
					document.body.scrollTop=0;
				}
			}
			/***************************************************/
			hauteur =  document.getElementById("spanref").offsetTop;
			hauteur =  bodyH-25;
			if(hauteur<10)
			{
			
				hauteur = document.getElementById("spanref").clientHeight;
			}
			if(hauteur<250)
				hauteur= 250;
		
			 
			document.getElementById("tableau_center").style.height = (hauteur-175)+"px";
			document.getElementById("tableau").style.height = (hauteur-130)+"px";
			
			hspacetabmiddle = (hauteur-160-150);
			if(hspacetabmiddle<120)
				hspacetabmiddle=120;
			
			
			if (o = returnElement("iddivloading") )
			{
				o.style.width = (wb-18)+"px";
				o.style.left = "10px";
				o.style.top = "95px";
				o.style.height = (hauteur-160)+"px";
				
			}
		
			if( parseInt(document.getElementById("tableau_center").style.height) < document.getElementById("tableaumid").clientHeight )
			{
				document.getElementById("tableaumid").style.width = (document.getElementById("tableau_center").clientWidth)+"px";  
				document.getElementById("tableau_top").style.width = (document.getElementById("tableau_center").clientWidth)+"px";  
			}
			self.setTimeout("verify();",1000);
		}
		function returnElement(unId)
		{
			  if(! allgetID[unId] )
			  { 
			  	 if( document.getElementById(unId) )
			  	 {
			  	 	allgetID[unId] = document.getElementById(unId);
			  	 }
			  }
			  return allgetID[unId];
		}
		var usrDesc = new Array();
		function test()
		{
			if(o = document.getElementById("ulist"))
			{
			alert(o.insertBefore);
				n = 10;
				for(r=0; r<o.rows.length-1; r++)
				{
					if(n-- == 0)
						break;
					o.insertBefore(o.rows.item(r), o.rows.item(0));
				//	o.moveRow(r, r+1);
				//	alert(o.rows.item(r).swapNode);
				//	o.rows.item(r).swapNode(o.rows.item(r+1));
				}
			}
		}
		function chgOrd(k)
		{
			sellist="";
			for(cc in usrsel)
			{
				if(usrsel[cc]==1)
				{
					 
					if(sellist!="")
						sellist += "," ;
					sellist += cc ;	
				}
			}	
			document.forms[0].p2.value = sellist ;
			
			document.forms[0].action = "./users.php";
			document.forms[0].srt.value = k;
			if(k == "<?php echo $parm["srt"]?>")
				document.forms[0].ord.value = document.forms[0].ord.value=="desc"?"asc":"desc";
			else
				document.forms[0].ord.value = "asc";
			document.forms[0].submit();
		}
		
		function importlist()
		{
			var myObj = new Object();
			myObj.myOpener = self;	
			window.showModalDialog ('import0.php?rand='+Math.random(),myObj, 'dialogWidth:550px;dialogHeight:330px;center:yes;help:no;status:no;scrollbars:no'  );
		
		}
		
		function exportlist()
		{
			var myObj = new Object();
			myObj.myOpener = self;	
			window.showModalDialog ( 'exportlistusers1.php',myObj, 'dialogWidth:350px;dialogHeight:250px;center:yes;help:no;status:no'  );
		
		}
		
		function exportlist2(t)
		{
			url = "exportlistusers.php?p0=<?php echo $parm['p0']?>&p1=<?php echo $parm['p1']?>&p4=<?php echo $parm["p4"]?>&p5=<?php echo $parm["p5"]?>&p6=<?php echo $parm["p6"]?>&t="+t;
		 	window.open(url, 'ExportUsers<?php echo $parm['p0'].$parm['p1'].$parm["p4"].$parm["p5"]?>',"menubar=yes, status=yes, scrollbars=yes,resizable=yes, width=300, height=150");
		}
		var thbout_timer = null;
		var xMousePos = 0;
		var yMousePos = 0;
		var curUsr = null;
		
		function newUser()
		{
			var b = prompt("<?php echo _('admin::compte-utilisateur identifiant')?>", '');	//'
			
			if(b!=null)
			{		
				self.location.replace('editusr.php?act=NEW&p2='+b+'&p0=&p1=');
			}
		}
		function divinfo2(e,usr)
		{
			
			xMousePos = e.clientX;
			yMousePos = e.clientY;
			curUsr = usr ;
			if( typeof(usrDesc[usr]) != "undefined" ) //			if(usrDesc[usr]!=null)
			{
			 	document.getElementById("FDESC").innerHTML  = usrDesc[usr];	
			}
			else
			{
			 	document.getElementById("FDESC").innerHTML  = '';		
				window.HFrameD.location.replace('./getinfousr.php?u='+usr);
			}	 
			
		
		
			document.getElementById("FDESC").style.left = (xMousePos + 10)+"px";	
		//	document.getElementById("FDESC").style.top  = (yMousePos + 15)+"px";	
			 
			if(thbout_timer)
			{
				self.clearTimeout(thbout_timer);
				thbout_timer = null;	
			}
			 	
			thbout_timer = self.setTimeout("seedivInfo();",1000);
		 
		}
		function redrawUsrDesc(oneUser)
		{
			if(oneUser == curUsr)
			{
				document.getElementById("FDESC").innerHTML  = usrDesc[curUsr];	
			}
			if(thbout_timer)
				seedivInfo();
		}
		function seedivInfo()
		{
			
			/***************************************************/
			if(document.all)
			{ 
				if(document.documentElement.clientHeight)
					bodyH = document.documentElement.clientHeight - 5 ;			
				else
					bodyH = document.body.clientHeight - 5 ;
				scrollLeft = null;
				if(document.documentElement.scrollLeft)
					scrollLeft = document.documentElement.scrollLeft;
				else
					scrollLeft = document.body.scrollLeft;	
				scrolltop = null;
				if(document.documentElement.scrollTop)
				{
					scrolltop = document.documentElement.scrollTop;
				 document.documentElement.scrollTop = 0 ;
				}
				else
				{
					scrolltop = document.body.scrollTop;
					document.body.scrollTop=0;
					
				}
			}
			else
			{
				bodyH =  parent.window.document.clientHeight;	
				if(!bodyH)
					if(document.documentElement.clientHeight)
						bodyH = document.documentElement.clientHeight - 5 ;			
					else
						bodyH = document.body.clientHeight - 5 ;
				scrollLeft = null;
				if(document.documentElement.scrollLeft)
					scrollLeft = document.documentElement.scrollLeft;
				else
					scrollLeft = document.body.scrollLeft;
				scrolltop = null;		
				if(document.documentElement.scrollTop)
				{
					scrolltop = document.documentElement.scrollTop;
					 document.documentElement.scrollTop = 0;
				}
				else
				{
					scrolltop = document.body.scrollTop;
					document.body.scrollTop=0;
				}
			}
			/***************************************************/
		  document.getElementById("FDESC").style.top  = (yMousePos + 15)+"px";
			hauteur =  document.getElementById("spanref").offsetTop;
			if(  document.getElementById('FDESC'))
			{
				tmp =  parseInt(document.getElementById('FDESC').style.top) + document.getElementById('FDESC').clientHeight ;
				document.getElementById('FDESC')
				h = parseInt(hauteur);
		
				if(h>0 && tmp>h)
				{
					document.getElementById('FDESC').style.top = (h-10-document.getElementById('FDESC').clientHeight)+"px";
				}
				else if(h==0)
				{
					h = document.getElementById("spanref").clientHeight	;
					if(h>0 && tmp>h)
					{
						document.getElementById('FDESC').style.top = (h-10-document.getElementById('FDESC').clientHeight)+"px";
					}	
				}
		
				document.getElementById('FDESC').style.visibility = 'visible';
			}
		}
		function cachDiv()
		{
			document.getElementById("FDESC").style.left = "0px";	
			document.getElementById("FDESC").style.top  = "0px";	
			self.clearTimeout(thbout_timer);//thbout_timer =null;
			thbout_timer = null;
			document.getElementById("FDESC").style.visibility = "hidden"; 
		}

		function is_ctrl_key(event)
		{
			if(event.altKey)
				return true;
			if(event.ctrlKey)
				return true;
			if(event.metaKey)	// apple key opera
				return true;
			if(event.keyCode == '17')	// apple key opera
				return true;
			if(event.keyCode == '224')	// apple key mozilla
				return true;
			if(event.keyCode == '91')	// apple key safari
				return true;
			
			return false;
		}

		function is_shift_key(event)
		{
			if(event.shiftKey)
				return true;
			return false;
		}
		
		var lastrowclicked = -1;
		var usrsel = new Array();
		var totusrsel = 0;
		function clk_list(event,usr_id)
		{
//			if(typeof(evt)==undefined)
//				evt = window.event;
//
//			if(evt && evt.target && ( evt.target.id=="findusr" || evt.target.id=="selfilter" ) )
//				return true;
//				
			document.getElementById("myfocus").focus();

//			if(evt && evt.target)
//			for(obj=evt.target; obj && (!obj.tagName || obj.tagName!="TR"); obj=obj.parentNode)
//				;
			var obj = document.getElementById('USER_'+usr_id);
			if(obj)
			{
				if( (!is_ctrl_key(event) && !is_shift_key(event)) || ((is_shift_key(event) && lastrowclicked==-1 && totusrsel==0)))
				{
					for(cc in usrsel)
					{
						if(usrsel[cc]==1)
						{
							if(cur=document.getElementById("USER_"+cc))
							{
								if( (parseInt(cur.getAttribute('i')) % 2) == 0 )
									cur.className = "b";
								else	
									cur.className = "g";					
								cur.setAttribute('s', "0");
								usrsel[cc] = 0;
								totusrsel--;
							}
						}
						
					}
					obj.className="trlistsel";
					obj.setAttribute('s', "1");
					usrsel[usr_id] = 1;
					totusrsel++;
				}
				if(is_shift_key(event) && lastrowclicked != -1)
				{
					curparent=obj.parentNode;
					var i = parseInt(obj.getAttribute('i'));
					begin = Math.min( lastrowclicked , i );
					end   = Math.max( lastrowclicked , i );
					
					continu = true;
					for(n=curparent.firstChild; n && continu; n=n.nextSibling)
					{
						if(n.nodeType != 1)
							continue;
						i = parseInt(n.getAttribute('i'));
						if(i >= begin && i <= end && n.getAttribute('s') != "1")
						{
							n.className="trlistsel";
							n.setAttribute('s', "1");
							usrsel[(n.id).substring(5)] = 1;
							totusrsel++;
							if(i == end)
								continu=false;
						}
					}
				}
				if(is_ctrl_key(event) || (is_shift_key(event) && lastrowclicked==-1 && totusrsel>0) )
				{
					if(obj.getAttribute('s')=="0")
					{
						obj.className="trlistsel";
						obj.setAttribute('s', "1");
						usrsel[usr_id] = 1;
						totusrsel++;
					}
					else
					{
						if( parseInt(obj.getAttribute('i') % 2) == 0 )
							obj.className="b";
						else	
							obj.className="g";					
						obj.setAttribute('s', "0");
						usrsel[usr_id]=0;
						totusrsel--;
					}
				}				
				lastrowclicked = parseInt(obj.getAttribute('i'));
				if( document.getElementById("spannbsel") )
					document.getElementById("spannbsel").innerHTML = totusrsel;
			}	
		}
		function dbclk_list(evt)
		{ 
			if(typeof(evt)==undefined)
				evt = window.event;

			var srcElement = evt.srcElement ? evt.srcElement : evt.target;
			
			if(evt && srcElement && srcElement.getAttribute('id')=="findusr")
					return true;

			document.getElementById("myfocus").focus();
			for(obj=srcElement; obj && (!obj.tagName || obj.tagName!="TR"); obj=obj.parentNode)
				;

			if(obj && obj.getAttribute('id').substr(0,5)=="USER_" )
			{
				for(cc in usrsel)
				{
					if(usrsel[cc]==1)
					{
						if(cur=document.getElementById("USER_"+cc))
						{
							if( (parseInt(cur.getAttribute('i')) % 2) == 0 )
								cur.className="b";
							else	
								cur.className="g";					
							cur.setAttribute('s', "0");
							usrsel[cc]=0;
						}
					}
					
				}
				obj.className="trlistsel";
				obj.setAttribute('s', "1");
				usrsel[(obj.getAttribute('id')).substring(5)]=1;
				usr_modify();
			}
		}
		
		function usr_modify()
		{ 
			editlist= "";
			nbuser=0;
			for(cc in usrsel)
			{
				if(usrsel[cc]==1)
				{
					nbuser++;
					if(editlist!="")
						editlist += "," ;
					editlist += cc ;	
				}
			}		
			if(nbuser>0)
			{
				document.forms[0].action = "./editusr.php";
				document.forms[0].act.value = "?";
				document.forms[0].p2.value = editlist ;
				document.forms[0].p3.value = "?";
				document.forms[0].submit();
			}
		}
		
		
		
		function usr_delete()
		{
		
			editlist= "";
			nbuser=0;
			for(cc in usrsel)
			{
				if(usrsel[cc]==1)
				{
					nbuser++;
					if(editlist!="")
						editlist += "," ;
					editlist += cc ;	
				}
			}	
			if(nbuser>0)
			{
				quest = "";
				quest = "<?php echo _('admin::user: etes vous sur de vouloir supprimer le(s) utilisateur(s) des bases ?')?>";
				
				var b = confirm(quest);
				
				if(b)
				{				
					self.location.replace('users.php?act=DELETE&p2='+editlist);
				}
			}
			
			
			
		}
		function chgactinact(obj)
		{
			if(obj.getAttribute('chk') == "1")
			{
				// on decheck
				obj.setAttribute('chk', "0");
				obj.src="/skins/icons/ccoch0.gif";
			}
			else
			{
				obj.setAttribute('chk', "1");
				obj.src="/skins/icons/ccoch1.gif";
			}
			searchusr();
		}
		
		function searchusr()
		{
			document.forms[0].action = "./users.php";
			document.forms[0].p4.value =  document.getElementById("findusr").value ;
			document.forms[0].p5.value =  document.getElementById("selfilter").value ;
			
			valFilter = 2;
			if( document.getElementById("filter_act") && document.getElementById("filter_inact") )
			{
				valFilter = 0;
				if( document.getElementById("filter_act").getAttribute('chk')=="1"  )
					valFilter += 2;
				if( document.getElementById("filter_inact").getAttribute('chk')=="1" )
					valFilter += 1;
			}
			document.forms[0].p6.value =  valFilter ;
			
			document.forms[0].submit();
			
		}
		
		function nofocus(evt)
		{
			if(typeof(evt=="undefined"))
				evt = window.event;
		
			if(evt && evt.srcElement && ( evt.srcElement.id=="findusr" || evt.srcElement.id=="selfilter" ) )
				return true;	
			document.getElementById("myfocus").focus();
		}
		function nothing()
		{
			return (false);
		}
		
//		document.onmousedown = clk_list;
		//document.onDblClick  = dbclk_list;
		
//		document.oncontextmenu = nothing;
		
		</script>
	</head>
<body id="idBody"  onResize="redrawme();"  ondblclick="dbclk_list(event)">   

<div id="FDESC" style="position:absolute;visibility:hidden; z-index:99;background-color:#888888;color:#FFFFFF;border: #000000 1px solid;padding:5px"  class="floatdesc" ></div>
<iframe scrolling="no" style="z-index:1; visibility:hidden; position:absolute; top:0px; left:0px; width:100px; height:200px;" id="idHFrameD" src="about:blank" name="HFrameD"></iframe>

	<form method="post" action="./users.php" target="_self" style="visibility:hidden; display:none" >
		<input type="hidden" name="ord" value="<?php echo $parm["ord"]?>" />
		<input type="hidden" name="srt" value="<?php echo $parm["srt"]?>" />
		<input type="hidden" name="act" value="<?php echo $parm["act"]?>" />
		<input type="hidden" name="p0" value="<?php echo $parm['p0']?>" />
		<input type="hidden" name="p1" value="<?php echo $parm['p1']?>" />
		<input type="hidden" name="p2" value="<?php echo $parm["p2"]?>" />
		<input type="hidden" name="p3" value="<?php echo $parm["p3"]?>" />
		<input type="hidden" name="p4" value="<?php echo $parm["p4"]?>" />
		<input type="hidden" name="p5" value="<?php echo $parm["p5"]?>" />
		<input type="hidden" name="p6" value="<?php echo $parm["p6"]?>" />
	</form>
<?php

if($parm["act"]=="DELETE")
{
	$sql = "SELECT usr_login, usr_id FROM usr WHERE usr_id IN (".$parm["p2"].")";
	
	$usr_list = array();
	
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			if(!in_array(trim($row['usr_login']), array('alchemy','admin','autoregister','invite')) && $row['usr_id']!= $usr_id)
			{
				$usr_list[] = $row['usr_id'];
			}
		}
		
	}
	
	$parm['p2'] = implode(',',$usr_list);
	$sql = "SELECT base_id FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) WHERE 
		usr.usr_id='".$conn->escape_string($usr_id)."' AND basusr.canadmin=1 AND (basusr.base_id='".implode("' OR basusr.base_id='",$baslist)."')  ORDER BY usr.usr_id";
	$mylist = "";
	if($rs = $conn->query($sql))
	{
		while( $row = $conn->fetch_assoc($rs) )
		{	
			if($mylist!="")$mylist.=",";
			$mylist	.= $row["base_id"];		
		}			
		$sqldelete = "DELETE FROM basusr WHERE base_id in ($mylist) and usr_id in (".$parm["p2"].")";

		if($conn->query($sqldelete))
		{
			$usrs = explode(',',$parm['p2']);
			foreach($usrs as $u)
 			{
	 			user::clear_cache(trim($u));
 			}	
		}
		
		$sql = "SELECT usr.usr_id,usr.usr_login,count(distinct(basusr.base_id)) as c
				FROM (usr left join basusr on basusr.usr_id=usr.usr_id) WHERE usr.usr_id IN ( ".$parm["p2"]." ) group by usr.usr_id order by c";
		if($rs = $conn->query($sql))
		{
			while( $row = $conn->fetch_assoc($rs) )
			{	
				if($row["c"]==0 || $row["c"]==null )
				{	
					
					$sqldelete = "select usr_id,sum(1) as n from demand where usr_id='".$conn->escape_string($row["usr_id"])."' and en_cours!='0' group by usr_id";
					if($rs2 = $conn->query($sqldelete))
					{	
						if($conn->num_rows($rs2)==0 )						
						{
							$sql = "DELETE FROM demand WHERE usr_id='".$conn->escape_string($row["usr_id"])."'";
							$conn->query($sql);
							$sql = "DELETE FROM sselcont WHERE ssel_id IN (SELECT ssel_id FROM ssel WHERE usr_id='".$conn->escape_string($row['usr_id'])."')";
							$conn->query($sql);
							$sql = "DELETE FROM validate_datas WHERE validate_id IN (SELECT id FROM validate WHERE usr_id='".$conn->escape_string($row['usr_id'])."')";
							$conn->query($sql);
							$sql = "DELETE FROM validate WHERE usr_id='".$conn->escape_string($row['usr_id'])."'";
							$conn->query($sql);
							$sql = "DELETE FROM sselnew WHERE usr_id='".$conn->escape_string($row['usr_id'])."'";
							$conn->query($sql);
							$sql = "DELETE FROM sbasusr WHERE usr_id='".$conn->escape_string($row['usr_id'])."'";
							$conn->query($sql);
							$sql = "DELETE FROM dsel WHERE usr_id='".$conn->escape_string($row['usr_id'])."'";
							$conn->query($sql);
							$sql = "DELETE FROM recusr WHERE usr_id='".$conn->escape_string($row['usr_id'])."'";
							$conn->query($sql);
							
							$old_login = $row["usr_login"];
							$i = 1;
							do
							{  
		              			$newlogin = "(#deleted_" . $old_login . ")" ;
								if ($i > 0)
									$newlogin = $newlogin . "#" . $i;
		               			$sqldelete = "SELECT usr_id FROM usr WHERE usr_login='" . $conn->escape_string($newlogin) . "'";
		               			$rsdelete = $conn->query($sqldelete);
		               			$rowdelete = $conn->fetch_assoc($rsdelete);
								if ($rowdelete == null)
								{
									$sqldel2 = "UPDATE usr SET usr_login='" . $conn->escape_string($newlogin) . "', usr_mail=null WHERE usr_id='" .$conn->escape_string($row["usr_id"])."'" ;	
		             				$rsdel = $conn->query($sqldel2);
									break;
								} 
		              			$i = $i + 1;
							} while ($i < 100);
						}	
					}	
					
					
				}
			}
		}
	}
	$parm["act"]="LISTUSERS";
}

if($parm["act"]=="LISTUSERS" || $parm["act"]=="UPD")
{
	
	$expP2 = explode(",",$parm["p2"]);
	
	
	$val_filt_act 	= (($parm["p6"])&2)>>1;
	$val_filt_inact = ($parm["p6"])&1; 
	
?>
<div id="iddivloading" style="background-image:url(./trans.gif);BACKGROUND-POSITION: top bottom; BACKGROUND-REPEAT: repeat; border:#ff0000 3px solid;position:absolute; width:94%;height:80%; top:95px; left:10px;z-index:99;text-align:center"><table style='width:100%;height:100%; text-align:center;valign:middle:; color:#FF0000; font-size:16px'><tr><td><br><div style='background-color:#FFFFFF'><b><?php echo _('phraseanet::chargement')?></b></div><br></td></tr></table></div>
		<span id="spanref" style="position:absolute; bottom:0px; left:5px;  background-color:#0f00cc; visibility:hidden">  
			<img src="./pixel.gif" name="test_longueur" width="1" height="100%" align="left">
		</span>	 
		<div id="divref" >&nbsp;</div>	
		<table id="presentUser" style="table-layout:fixed; width:100%;" border="0" cellpadding="0" cellspacing="0">		
			<tr style="height:30px; " >
				<td style="width:20px;">
				</td>				
				<td style="height:30px;font-size:12px;">
					<b><a href="javascript:void();return(false);"  onclick="newUser();return(false);" style="color:#000000; text-decoration:none"><?php echo _('admin::user: nouvel utilisateur')?></a></b>
					<input type="text" id="myfocus" style="width:0px;height:0px;position:absolute;left:-10px; top:-3px">
				</td>
				<td style="text-align:right">							
					<small><a href="javascript:void();return(false);"  onclick="importlist();return(false);" style="color:#000000; text-decoration:none"><?php echo _('admin::user: import d\'utilisateurs')?></a></small>
					 / 
					<small><a href="javascript:void();return(false);"  onclick="exportlist();return(false);" style="color:#000000; text-decoration:none"><?php echo _('admin::user: export d\'utilisateurs')?></a></small>
				</td>
				<td style="width:20px;">&nbsp;</td>
			</tr>
			
			<tr style="height:30px; " >
				<td style="width:10px"></td>
				<td style="text-align:left" nowrap>
				<?php  if(!($parm['p0']==""&&$parm['p1']==""))
							{
				?>
							<div style="border:#666666 1px solid;text-align:center">
								<img src="/skins/icons/ccoch<?php echo $val_filt_act?>.gif" chk="<?php echo $val_filt_act?>" name="filter_act" id="filter_act" onClick="chgactinact(this);return(false);" >
								<?php echo _('admin::user: utilisateurs actifs')?>&nbsp;/&nbsp;
								<img src="/skins/icons/ccoch<?php echo $val_filt_inact?>.gif" chk="<?php echo $val_filt_inact?>" name="filter_inact" id="filter_inact" onClick="chgactinact(this);return(false);" >
								<?php echo _('admin::user: utilisateurs inactifs')?></div>
				<?php } ?>
				</td>
				<td style="text-align:right" nowrap>
						&nbsp;<?php echo _('admin::user: utilisateurs inactifs')?> 
							<select class="selfilter" id="selfilter" name='filtre'>
								<option <?php echo ($parm["p5"]=="LOGIN"?"selected":"")?> value='LOGIN'><?php echo _('Push::filter on login')?></option>
								<option <?php echo ($parm["p5"]=="NAME"?"selected":"")?> value='NAME'><?php echo _('Push::filter on name')?></option>
								<option <?php echo ($parm["p5"]=="COUNTRY"?"selected":"")?> value='COUNTRY'><?php echo _('Push::filter on countries')?></option>
								<option <?php echo ($parm["p5"]=="COMPANY"?"selected":"")?> value='COMPANY'><?php echo _('Push::filter on companies')?></option>
								<option <?php echo ($parm["p5"]=="MAIL"?"selected":"")?> value='MAIL'><?php echo _('Push::filter on emails')?></option>
								<option <?php echo ($parm["p5"]=="LASTMODEL"?"selected":"")?> value='LASTMODEL'><?php echo _('Push::filter on templates')?></option>
								</select>
								<?php echo _('Push::filter starts')?>  : 
								<input type="text" id="findusr" style="font-size:9px; width:60px;" onkeypress="if(event.keyCode==13||event.keyCode==3)searchusr();" value="<?php echo $parm["p4"]?>" >&nbsp;
								<a href="javascript:void();return(false);" onClick="searchusr();return(false);" style="color:#000000;text-decoration:none"><b><?php echo _('boutton::valider')?></b></a>
				</td>
				<td style="width:10px"></td>		
			</tr>

<?php
	// on lit les users de mes bases
	switch($parm["srt"])
	{
		case 'name':
			$ord = "allname ".$parm["ord"].", usr.usr_id";
			break;
		case 'mail':
			$ord = "usr.usr_mail ".$parm["ord"].", usr.usr_id";
			break;
		case 'company':
			$ord = "usr.societe ".$parm["ord"].", usr.usr_id";
			break;
			
			
		case 'login':
			$ord = "usr.usr_login ".$parm["ord"].", usr.usr_id";
			break;
		case 'creationdate':
			$ord = "usr.usr_creationdate ".$parm["ord"].", usr.usr_id";
			break;
		case 'id':	
			$ord = "usr.usr_id ".$parm["ord"];
			break;
		case 'country':	
			$ord = "usr.pays ".$parm["ord"];
			break;
		case 'lastModel':	
			$ord = "usr.lastModel ".$parm["ord"];
			break;
		default:
			$parm["srt"] = "login";
			$ord = "usr.usr_login ".$parm["ord"].", usr.usr_id";
			break;
	}
	
	$precise = "" ;
	if($parm["p4"]!=null && $parm["p4"]!="" && $parm["p5"]!=null && $parm["p5"]!="" )
	{
		$precise ="";
		if($parm["p5"]=="LOGIN")
			$precise.=" AND usr_login like '".$conn->escape_string($parm["p4"])."%' COLLATE utf8_general_ci ";
		elseif($parm["p5"]=="NAME")
			$precise.=" AND (usr_nom like '".$conn->escape_string($parm["p4"])."%' OR usr_prenom like '".$conn->escape_string($parm["p4"])."%' ) ";
		elseif($parm["p5"]=="COUNTRY")
			$precise.=" AND usr.pays like '".$conn->escape_string($parm["p4"])."%' ";
		elseif($parm["p5"]=="COMPANY")
			$precise.=" AND usr.societe like '".$conn->escape_string($parm["p4"])."%' ";
		elseif($parm["p5"]=="MAIL")
			$precise.=" AND usr.usr_mail like '".$conn->escape_string($parm["p4"])."%' ";
		elseif($parm["p5"]=="LASTMODEL")
			$precise.=" AND usr.lastModel like '".$conn->escape_string($parm["p4"])."%' ";
			
			
			
		 
	}
	$preciseBasusr = "";
	if($parm["p6"]!=null && $parm["p6"]!=""  )
	{
		
		if($parm["p6"]=="0") 	 // on veut pas voir personnne ( bizarre comme demande !!! )
			$preciseBasusr.=" AND actif=9999 ";
		elseif($parm["p6"]=="1") // on veut que voir que les inactifs
			$preciseBasusr.=" AND actif=0 ";
		elseif($parm["p6"]=="2") // on veut que voir que les actifs
			$preciseBasusr.=" AND actif=1 ";
		 
	}


		
		$seeSuperu= ' AND issuperu="0" ';
		$sql = "SELECT usr_login, issuperu FROM usr WHERE usr_id = '".$conn->escape_string($usr_id)."'";
		if($rsSu = $conn->query($sql))
		{
			if($rowSu = $conn->fetch_assoc($rsSu))
			{
				if($rowSu['issuperu'] == '1' )
				{
					$seeSuperu='';
				}
			}
		}
		
		$sql = "SELECT DISTINCT 
				concat(usr.usr_nom,'_',usr.usr_prenom) as allname,
				usr.usr_mail, usr.usr_nom, usr.usr_prenom, usr.societe,
				usr.usr_id, usr_login, usr.model_of,
				usr_creationdate as creationdate,pays ,lastModel
				FROM usr LEFT JOIN basusr ON 
					 usr.usr_id = basusr.usr_id
				WHERE 
					((!isnull(basusr.base_id) ";
		if(count($basnotlist) > count($baslist))
		{
			if(count($baslist) > 0)
				$sql .= " AND ( basusr.base_id='".implode("' OR basusr.base_id='",$baslist)."') ";
		}
		else
		{
			if(count($basnotlist) > 0)
				$sql .= " AND ( basusr.base_id!='".implode("' AND basusr.base_id!='",$basnotlist)."') ";
		}
		
			$sql .= "$preciseBasusr ) AND usr_login != 'autoregister' AND usr_login != 'invite') 
					AND usr.invite='0' 
					AND usr_login not like '(#deleted_%' 
					AND (model_of=0 OR model_of='".$conn->escape_string($usr_id)."')".
					" $seeSuperu ".$precise. 	
				"  ORDER BY " . $ord . "";
				
	
	
	$out .= $baslibs . "\n";
	$out .= "<br><br><a href=\"javascript:newUser();\">"._('admin::user: nouvel utilisateur')."</a><br><br>";
	$ilig=0;
	
	if($rs = $conn->query($sql))
	{

		$again = true;
		$last_row = NULL;
?>


			<tr style="background-color:#aaaaaa; border:#cccccc 1px solid;"  >
				<td colspan="4" style="padding-left:3p;text-align:center; align:center">
				<center>
					<div id="divRights" style="background-color:#aaaaaa; width:100%; height:100%;overflow:hidden;" >
					 
						<DIV class="classdivtable" id="tableau" style="text-align:left; align:left;" >
							<DIV class="divTop"" id="tableau_top" style="height:25px;">
								<TABLE class="tableTop"  cellSpacing="0" id="imgtopinclin"  style="">
									<THEAD>
										<TR style="height:25px;">
										
<?php
		if(GV_admusr_id )
		{	
			printf('<th class="thtableTop" style="TEXT-ALIGN: center;width:58px; cursor:pointer" onClick="chgOrd(\'id\');" >');
			if($parm["srt"]=="id")
				printf("<img src=\"/skins/icons/tsort_".mb_strtolower($parm["ord"]).".gif\">&nbsp;");
			printf(_('admin::compte-utilisateur id utilisateur').'</th>');
		}		
		printf('<th class="thtableTop" style="TEXT-ALIGN: center;width:32px">'._('admin::user: informations utilisateur').'</th>');
		
		printf('<th class="thtableTop" style="TEXT-ALIGN: center; cursor:pointer" onClick="chgOrd(\'login\');" >');
		if($parm["srt"]=="login")
			printf("<img src=\"/skins/icons/tsort_".mb_strtolower($parm["ord"]).".gif\">&nbsp;");
		printf(_('admin::compte-utilisateur identifiant').'</th>');	
		
		if(GV_admusr_name )
		{
			printf('<th class="thtableTop" style="TEXT-ALIGN: center; cursor:pointer" onClick="chgOrd(\'name\');" >');
			if($parm["srt"]=="name")
				printf("<img src=\"/skins/icons/tsort_".mb_strtolower($parm["ord"]).".gif\">&nbsp;");
			printf(_('admin::compte-utilisateur nom/prenom').'</th>');				
		}
		if(GV_admusr_company )
		{
			printf('<th class="thtableTop" style="TEXT-ALIGN: center; cursor:pointer" onClick="chgOrd(\'company\');" >');
			if($parm["srt"]=="company")
				printf("<img src=\"/skins/icons/tsort_".mb_strtolower($parm["ord"]).".gif\">&nbsp;");
			printf(_('admin::compte-utilisateur societe').'</th>');			
		}
		if(GV_admusr_mail )
		{
			printf('<th class="thtableTop" style="TEXT-ALIGN: center; cursor:pointer" onClick="chgOrd(\'mail\');" >');
			if($parm["srt"]=="mail")
				printf("<img src=\"/skins/icons/tsort_".mb_strtolower($parm["ord"]).".gif\">&nbsp;");
			printf(_('admin::compte-utilisateur email').'</th>');			
		}
		if(GV_admusr_country )
		{ 
			printf('<th class="thtableTop" style="TEXT-ALIGN: center; cursor:pointer" onClick="chgOrd(\'country\');" >');
			if($parm["srt"]=="country")
				printf("<img src=\"/skins/icons/tsort_".mb_strtolower($parm["ord"]).".gif\">&nbsp;");
			printf(_('admin::compte-utilisateur pays').'</th>');			
		}
		if(GV_admusr_lastmodel )
		{ 
			printf('<th class="thtableTop" style="TEXT-ALIGN: center; cursor:pointer" onClick="chgOrd(\'lastModel\');" >');
			if($parm["srt"]=="lastModel")
				printf("<img src=\"/skins/icons/tsort_".mb_strtolower($parm["ord"]).".gif\">&nbsp;");
			printf(_('admin::compte-utilisateur dernier modele applique').'</th>');			
		}
			 
		printf('<th class="thtableTop" style="TEXT-ALIGN: center;width:125px; cursor:pointer" onClick="chgOrd(\'creationdate\');" >');
		if($parm["srt"]=="creationdate")
			printf("<img src=\"/skins/icons/tsort_".mb_strtolower($parm["ord"]).".gif\">&nbsp;");
		printf(_('admin::compte-utilisateur date de creation').'</th>');		
?>
										</TR>
									</THEAD>
								</TABLE>
							</DIV>
							<DIV class="divCenter" id="tableau_center">													 			 
								<TABLE id="tableaumid" class="tableCenter"  cellpadding="0" cellSpacing="0" >
									<TBODY>
<?php
		$nlig=10000;	
		$remJs = "" ;
		$nbusrsel = 0;
		if(1)
		{
			$ilig=0;
			while(($row = $conn->fetch_assoc($rs)) && $nlig-- > 0)
			{
				
				if(in_array($row['usr_login'],array('autoregister','invite')))
					continue;
				if(in_array($row["usr_id"],$expP2))
				{
					$nbusrsel++;	
					$remJs.= "\nusrsel[\"".$row["usr_id"]."\"]=1;";
					printf("\n\t\t\t\t\t\t\t\t\t\t<TR onclick=\"clk_list(event,'" . $row["usr_id"] ."')\"  id=\"USER_" . $row["usr_id"] ."\" s=\"0\" i=\"".$ilig."\" class=\"trlistsel\"   style=\"text-align:left;align:left;\" >");
				}
				else
				{
					printf("\n\t\t\t\t\t\t\t\t\t\t<TR onclick=\"clk_list(event,'" . $row["usr_id"] ."')\" id=\"USER_" . $row["usr_id"] ."\" s=\"0\" i=\"".$ilig."\" " . ($ilig % 2 == 0 ? " class=\"b\" ":" class=\"g\" ") . "  style=\"text-align:left;align:left;\" >");
				
				}
				if(GV_admusr_id )
				{
					printf("\n\t\t\t\t\t\t\t\t\t\t\t" . '<TD style="overflow:hidden; width:60px; border-top: #FFFFFF 1px solid;text-align:right;" >');
					printf("\n\t\t\t\t\t\t\t\t\t\t\t" . $row["usr_id"]);					
					printf("\n\t\t\t\t\t\t\t\t\t\t\t" . '&nbsp;</TD>');
				}
				printf("\n\t\t\t\t\t\t\t\t\t\t\t" . '<TD   style="overflow:hidden;text-align:center;height:20px; width:32px; border-top: #FFFFFF 1px solid;cursor:pointer" onMouseOver=\'divinfo2(event,'. $row["usr_id"].');\'  onMouseOut=\'cachDiv();\' >');
				printf("\n\t\t\t\t\t\t\t\t\t\t\t" . '<b>&nbsp;&nbsp;<i>i</i>&nbsp;&nbsp;</b>');
				printf("\n\t\t\t\t\t\t\t\t\t\t\t" . '</TD>');			
				
				printf("\n\t\t\t\t\t\t\t\t\t\t\t" . '<TD   style="overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px" >');
				print( $row["usr_login"]);					
				printf("\n\t\t\t\t\t\t\t\t\t\t\t" . '</TD>');
					
				$tmp = $row["usr_nom"]." ".$row["usr_prenom"]; //.( $row["societe"]?" (".$row["societe"].")":"" );
				if(GV_admusr_name )
					print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td  style=\"overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px\">" .trim($tmp)."&nbsp;</td>\n");
				
				if(GV_admusr_company )
					print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td  style=\"overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px\">" .trim($row["societe"])."&nbsp;</td>\n");
				
				if(GV_admusr_mail )
					print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td  style=\"overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px\">" . trim($row["usr_mail"])."&nbsp;</td>\n");
				
			
				$pays = "";
				if(isset($countries[trim($row["pays"])]))
					$pays = $countries[trim($row["pays"])];
				
				if(GV_admusr_country )
					print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td  style=\"overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px\">" . $pays."&nbsp;</td>\n");

				if(GV_admusr_lastmodel )
					print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td  style=\"overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px\">" .trim($row["lastModel"])."&nbsp;</td>\n");
				
				if($row["model_of"]!="" && $row["model_of"]!=0)
					print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td style=\"width:125px; border-top: #FFFFFF 1px solid;\"><center><font color=\"#FF0000\">"._('admin::user modele')."</font></center></td>\n");
				else
					print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td style=\"width:125px; border-top: #FFFFFF 1px solid;\">" .$row["creationdate"]. "</td>\n");
				
				printf("\t\t\t\t\t\t\t\t\t\t</tr>");
								
				$ilig++;
			}
		}
		else
		{
		
		}
	}
	############################################################# ET AUSSI LES USERS FANBTOMES ######################################################################
	
	if($parm['p0']=="" && $parm['p1']=="")
	{
		$seeSuperu="0";
		$sql = "SELECT usr_login, issuperu FROM usr WHERE usr_id = '".$conn->escape_string($usr_id)."'";
		if($rsSu = $conn->query($sql))
		{
			if($rowSu = $conn->fetch_assoc($rsSu))
			{
				if($rowSu['usr_login'] == 'alchemy' && $rowSu['issuperu'] == '1' )
				{
					$seeSuperu="0,1";
				}
			}
		}
		
		$sql = "select distinct usr.*,concat(usr.usr_nom,'_',usr.usr_prenom) as allname from ((usr left join basusr on basusr.usr_id=usr.usr_id) left join demand on (usr.usr_id=demand.usr_id )) where isnull(basusr.base_id) AND (isnull(demand.base_id) OR demand.refuser='1') AND usr.issuperu IN ($seeSuperu) AND usr.invite='0' AND usr_login not like '(#deleted_%' $precise ORDER BY " . $ord;

		if($rs = $conn->query($sql))
		{	
				while(($row = $conn->fetch_assoc($rs)) )
				{
					if(in_array($row['usr_login'],array('autoregister','invite')))
						continue;
						
					$out .= "<tr s=0 i=" . $ilig . "  onclick=\"clk_list(event,'" . $row["usr_id"] ."')\" id=\"USER_" . $row["usr_id"] ."\"". ($ilig % 2 == 0 ? " class=\"g\"":"") . ">\n";
					printf("\n\t\t\t\t\t\t\t\t\t\t<TR  onclick=\"clk_list(event,'" . $row["usr_id"] ."')\" id=\"USER_" . $row["usr_id"] . "\" s=\"0\" i=\"".$ilig."\" style=\"text-align:left;align:left;\" " . ($ilig % 2 == 0 ? " class=\"b\" ":" class=\"g\" ") . ">");
				
					
					if(GV_admusr_id )
					{
						printf("\n\t\t\t\t\t\t\t\t\t\t\t" . "<TD  style=\"overflow:hidden; width:60px; border-top: #FFFFFF 1px solid;text-align:right\" ><font color=\"#982929\"><i>"._('admin::user: utilisateur fantome')."</i>&nbsp;</td>");
						
					}
					
					printf("\n\t\t\t\t\t\t\t\t\t\t\t" . '<TD   style="overflow:hidden;text-align:center;height:20px; width:32px; border-top: #FFFFFF 1px solid;cursor:pointer" onMouseOver=\'divinfo2(event,'. $row["usr_id"].');\'  onMouseOut=\'cachDiv();\' >');
					printf("\n\t\t\t\t\t\t\t\t\t\t\t" . '<b>&nbsp;&nbsp;<i>i</i>&nbsp;&nbsp;</b>');
					printf("\n\t\t\t\t\t\t\t\t\t\t\t" . '</TD>');
									
					
					print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<TD style=\"overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px\" ><font color=\"#982929\">" .$row["usr_login"]."</font></td>\n");
				
						
					$tmp = $row["usr_nom"]." ".$row["usr_prenom"]; //.( $row["societe"]?" (".$row["societe"].")":"" );
					if(GV_admusr_name )
						print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td style=\"overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px\">" .trim($tmp)."&nbsp;</td>\n");
					
					if(GV_admusr_company )
						print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td style=\"overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px\">" .trim($row["societe"])."&nbsp;</td>\n");
					
					if(GV_admusr_mail )
						print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td style=\"overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px\">" .trim($row["usr_mail"])."&nbsp;</td>\n");
					
					$pays = "";
					if(isset($countries[trim($row["pays"])]))
						$pays = $countries[trim($row["pays"])];
					
					if(GV_admusr_country )
						print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td style=\"overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px\">" . $pays."&nbsp;</td>\n");
						
					if(GV_admusr_lastmodel )
						print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td style=\"overflow:hidden; border-top: #FFFFFF 1px solid;padding-left:2px\">" .trim($row["lastModel"])."&nbsp;</td>\n");
					
					if($row["model_of"]!="" && $row["model_of"]!=0)
						print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td style=\"width:125px; border-top: #FFFFFF 1px solid;\"><center><font color=\"#FF0000\">"._('admin::user modele')."</font></center></td>\n");
					else
						print("\n\t\t\t\t\t\t\t\t\t\t\t" . "<td style=\"width:125px; border-top: #FFFFFF 1px solid;\">" . $row["usr_creationdate"] . "</td>\n");
					
					printf("\t\t\t\t\t\t\t\t\t\t</tr>");
					
					$ilig++;
				}
		}
	}
	#################################################################################################################################################################
	
	$conn->free_result($rs);
	
	$out .= "(" . $ilig . " "._('phraseanet::utilisateurs')." )\n";
	$out .= "<center>";
	$out .= "<div id=\"divboutuser\" style=\"visibility:visible\">";	
	$out .= "<span class=\"bout\"   onClick=\"usr_modify();return(false);\" >"._('boutton::modifier')."</span>";
	$out .= "&nbsp;&nbsp;&nbsp;";
	$out .= "<span class=\"bout\"  onClick=\"javascript:usr_delete();\" >"._('boutton::supprimer')."</span>";
	$out .= "</div>";
	$out .= "</center>";
	$out .= "<br><br>";
?>
									</TBODY>
								</TABLE>	
							</DIV>	
							<DIV class="divTop" style="border-top:#000000 1px solid;text-align:center; font-size:10px"  >
								<?php echo  sprintf(_('phraseanet:: %d utilisateurs'),$ilig)?> - <?php echo sprintf(_('phraseanet:: %s utilisateurs selectionnes'),'<span id="spannbsel">'.$nbusrsel.'</span>');?>
							</DIV>
						</DIV>
						
					</div>
				</center>
				</td>
			</tr>	
			<tr style="height:25px;">			
				<td style="width:20px;">					
				</td>				
				<td style="height:30px;">
					<b><a href="javascript:void();" onclick="usr_modify();return(false);"  id="genevalid" style="color:#000000;text-decoration:none"><?php echo _('boutton::modifier')?></a> </b>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<b><a href="javascript:void();" onclick="usr_delete();return(false);"  id="genecancel" style="color:#000000;text-decoration:none"><?php echo _('boutton::supprimer')?></a> </b>
				</td>				
				<td style="width:20px;">
				</td>				
			</tr>			
		</table>
<?php
	if(isset($remJs) && $remJs!="")
		echo "\n<script type=\"text/javascript\">totusrsel=$nbusrsel;\n".$remJs."\n</script>\n";
}
?>
</body>
</html>