<?php
require_once dirname( __FILE__ ).'/../../lib/classes/httpRequest.class.php';

$request = httpRequest::getInstance();
$parm = $request->get_parms('session', 'coll', 'status');

if ($parm["session"]) {
	session_id($parm["session"]);
}

require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();
define("DEFAULT_MIMETYPE", "application/octet-stream");

if(isset($_SERVER['HTTP_USER_AGENT']) && strtolower($_SERVER['HTTP_USER_AGENT']) == 'shockwave flash')
	define("UPLOADER", "FLASH");
else
	define("UPLOADER", "HTML");
	
require(GV_RootPath."lib/index_utils2.php");

require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );

if(!isset($_FILES['Filedata']))
{
	if(UPLOADER == 'FLASH')
		header('HTTP/1.1 500 Internal Server Error');
	else
		echo '<script type="text/javascript">parent.classic_uploaded("'._("Internal Server Error").'")</script>';
	exit;
}
	
if($_FILES['Filedata']['error'] > 0)
{
	if(UPLOADER == 'FLASH')
		header('HTTP/1.1 500 Internal Server Error');
	else
		echo '<script type="text/javascript">parent.classic_uploaded("'._("Internal Server Error").'")</script>';
	exit(0);
}
	

	
$conn = connection::getInstance();
$sbas_id = false;
if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
		if(!$session->upload)
		{
			if(UPLOADER == 'FLASH')
				header("Location: /login/?error=auth&lng=".$lng);
			else
				echo '<script type="text/javascript">parent.classic_uploaded("'._("Session Lost").'")</script>';
			exit();
		}
	
}
else{
	if(UPLOADER == 'FLASH')
		header("Location: /login/upload/");
	else
		echo '<script type="text/javascript">parent.classic_uploaded("'._("Session Lost").'")</script>';
	exit();
}

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	die();
	
$base_id = (int)$parm['coll'];
	
$chStatus = false;
$sql = 'SELECT bu.base_id, bu.chgstatus, b.sbas_id FROM basusr bu, bas b WHERE bu.base_id = b.base_id AND usr_id="'.$conn->escape_string($usr_id).'" AND canaddrecord = "1" AND bu.base_id="'.$conn->escape_string($base_id).'"';

if($rs = $conn->query($sql))
{
	while($row = $conn->fetch_assoc($rs))
	{
		$chStatus = true;
		$sbas_id = $row['sbas_id'];
	}
}


$ext = pathinfo($_FILES['Filedata']["name"]);

$newname = $_FILES['Filedata']['tmp_name'].'.'.(isset($ext['extension'])?$ext['extension']:'');

if($newname !== $_FILES['Filedata']['tmp_name'])
	if(rename($_FILES['Filedata']['tmp_name'], $newname));
		$_FILES['Filedata']['tmp_name'] = $newname;


$mask_oui = '0000000000000000000000000000000000000000000000000000000000000000';
$mask_non = '1111111111111111111111111111111111111111111111111111111111111111';
if($sbas_id !== false && is_array($parm['status']))
{
	$mask_oui = '0000000000000000000000000000000000000000000000000000000000000000';
	$mask_non = '1111111111111111111111111111111111111111111111111111111111111111';

	foreach($parm['status'] as $k=>$v)
	{
		if((int)$k<=63 && (int)$k>=4)
		{
			if($v == '0')
				$mask_non[63-(int)$k] = $v;
			elseif($v == '1')
				$mask_oui[63-(int)$k] = $v;
		}
	}
}

try 
{
  $sha256 = hash_file('sha256',$newname);

  $uuid = false;
  $file_uuid = new uuid($newname);
  if(!$file_uuid->has_uuid())
  {
    $connbas = connection::getInstance($sbas_id);
    $sql = 'SELECT uuid FROM record WHERE sha256 = "'.$connbas->escape_string($sha256).'"';
    if($rs = $connbas->query($sql))
    {
      if($row = $connbas->fetch_assoc($rs))
      {
        if(uuid::uuid_is_valid($row['uuid']))
          $uuid = $row['uuid'];
      }
      $connbas->free_result($rs);
    }
  }

  $uuid = $file_uuid->write_uuid($uuid);

	$error_file = p4file::check_file_error($_FILES['Filedata']["tmp_name"], $sbas_id);
	$status_2 = status::and_operation($mask_oui,$mask_non);
	if(($uuid !== false && !$file_uuid->is_new_in_base(phrasea::sbasFromBas($base_id))) || count($error_file) > 0)
	{
		if(!lazaretFile::move_uploaded_to_lazaret($_FILES['Filedata']["tmp_name"], $base_id, $_FILES['Filedata']["name"], $uuid, $sha256, implode("\n",$error_file), $status_2))
		{
			if(UPLOADER == 'FLASH')
				header('HTTP/1.1 500 Internal Server Error');
			else
				echo '<script type="text/javascript">parent.classic_uploaded("'._("erreur lors de l'archivage").'")</script>';
		}
		
		if(UPLOADER == 'HTML')
			echo '<script type="text/javascript">parent.classic_uploaded("'._("Fichier uploade, en attente").'")</script>';
		exit;
	}
	
}
catch (Exception $e)
{
	
}



if(($record_id = p4file::archiveFile($_FILES['Filedata']['tmp_name'],$base_id,true,$_FILES['Filedata']["name"],$sha256)) === false)
{
	unlink($_FILES['Filedata']['tmp_name']);	
	if(UPLOADER == 'FLASH')
		header('HTTP/1.1 500 Internal Server Error');
	else
		echo '<script type="text/javascript">parent.classic_uploaded("'._("erreur lors de l'archivage").'")</script>';
	exit(0);
}


if($chStatus === true && $sbas_id !== false && is_array($parm['status']))
{

	$connbas = connection::getInstance($sbas_id);
	
	if($connbas)
	{
		$sql = 'SELECT status FROM record WHERE record_id="'.$connbas->escape_string($record_id).'"';
		if($rs = $connbas->query($sql))
		{
			if($row = $connbas->fetch_assoc($rs))
			{
				$status = $row['status'];
			
				$sql = 'UPDATE record SET status = ((' . $status . ' | 0b'.$mask_oui.') & 0b'.$mask_non.') WHERE record_id="'.$connbas->escape_string($record_id).'"';
		

				if($connbas->query($sql))
				{
					
				}
			}
		}
	}
}
if(file_exists($_FILES['Filedata']['tmp_name']))
	unlink($_FILES['Filedata']['tmp_name']);

if(UPLOADER == 'HTML')
	echo '<script type="text/javascript">parent.classic_uploaded("'._("Fichier uploade !").'")</script>';
exit(0);
