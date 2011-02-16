<?php

//SPECIAL ZINO
ini_set('display_errors','off');
ini_set('display_startup_errors','off');
ini_set('log_errors','off');
//SPECIAL ZINO

$upload_batch_id = (string)($sxParms->upload_batch_id);
$index          = (int)($sxParms->index);
$filename       = (string)($sxParms->filename);
$filesize       = (int)($sxParms->filesize);
$md5            = (string)($sxParms->md5);
//$crc32          = (int)($sxParms->crc32);


// -------------------------------------------------------------------
// check parms, size, name, md5 etc...
// between post and _FILE
// -------------------------------------------------------------------

if(count($_FILES) != 1 || !array_key_exists('file', $_FILES))
{
	err('$_FILES[\'file\'] unknown');
	return;
}
$fil = $_FILES['file']['tmp_name'];
$tmp_filesize   = filesize($fil);
$tmp_filename   = $_FILES['file']['name'];
$tmp_k          = file_get_contents($fil);
$tmp_md5        = md5($tmp_k, false);
//$tmp_crc32      = crc32($tmp_k);

if($tmp_filename != $filename)
{
	err('filename : \'' . $tmp_filename . '\' != \'' . $filename . '\'');
	return;
}
if($tmp_md5 != $md5)
{
	err('md5 : \'' . $tmp_md5 . '\' != \'' . $md5 . '\'');
	return;
}
//if($tmp_crc32 != $crc32)
//{
//	err('checksum32 : \'' . $tmp_crc32 . '\' != \'' . $crc32 . '\'');
//	return;
//}



// -------------------------------------------------------------------
// check cnx & session
// -------------------------------------------------------------------

$conn = connection::getInstance();
if(!$conn)
{
	err('bad conn');
	return ;
}
	
if(!$ses_id || !$usr_id || !$upload_batch_id)
{
	err('missing ses_id, usr_id or upload_batch_id');
	return;
}
	
if( !$ph_session = phrasea_open_session($ses_id, $usr_id) )
{
	err('bad ph_session');
	return;
}


// -------------------------------------------------------------------
// check batch
// -------------------------------------------------------------------

$batchok = false;
$batch_nfiles = false;
// $sql = 'SELECT b.*, SUM(1) AS uploaded FROM (uplbatch AS b LEFT JOIN uplfile AS f USING(uplbatch_id)) WHERE uplbatch_id=\'' . $conn->escape_string($upload_batch_id) . '\' GROUP BY uplbatch_id' ;
$sql = 'SELECT * FROM uplbatch WHERE uplbatch_id=\'' . $conn->escape_string($upload_batch_id) . '\'' ;
if( ($rs = $conn->query($sql)) )
{
	if( ($row = $conn->fetch_assoc($rs)) )
	{
		$batch_nfiles = $row['nfiles'];
		$batchok = true;
	}
	$conn->free_result($rs);
}

if(!$batchok)
{
	err('bad upload_batch_id');
	return;
}

if($index > $batch_nfiles)
{
	err('index : '.$index.' > '.$batch_nfiles);
	return;
}



$dir = GV_RootPath.'tmp/batches/'.$upload_batch_id.'/';

if(!is_dir($dir))
{
	@mkdir($dir, 0777, true);	
}

//$tmp_k          = file_get_contents($fil);
if(is_dir($dir))
{
	if(!@move_uploaded_file($fil, $dir.$index))
	{
		err('error moving file \''.$fil.'\' to \''.$dir.$index.'\'');
		return;
	}
}
else
{
	err('error creating tmp folder \''.$dir.'\'');
	return;
}


// -------------------------------------------------------------------
// ok, add (or replace) the file to the batch
// -------------------------------------------------------------------

$sql = 'REPLACE INTO uplfile (uplbatch_id, idx, filename) VALUES ('
		. '\'' . $conn->escape_string($upload_batch_id) . '\', '
		. '\'' . $conn->escape_string($index) . '\', '
		. '\'' . $conn->escape_string($filename) . '\') ';

$conn->query($sql);
$affected = $conn->affected_rows();

$xupload_batch = $result->appendChild($dom->createElement('upload_batch'));
$xupload_batch->setAttribute('id', $upload_batch_id);
$xupload_batch->appendChild($dom->createElement('nfiles'))->appendChild($dom->createTextNode($batch_nfiles));

$xindex = $result->appendChild($dom->createElement('index'));
$xindex->appendChild($dom->createTextNode((string)$index));
if($affected == 1)
{
	// inserted
	$xindex->setAttribute('action', 'INSERTED');
}
elseif($affected == 2)
{
	// inserted
	$xindex->setAttribute('action', 'REPLACED');
}

?>