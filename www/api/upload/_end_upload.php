<?php

//SPECIAL ZINO
ini_set('display_errors','off');
ini_set('display_startup_errors','off');
ini_set('log_errors','off');
//SPECIAL ZINO

$upload_batch_id = (string)($sxParms->upload_batch_id);


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

$sql = 'SELECT * FROM uplbatch WHERE uplbatch_id=\'' . $conn->escape_string($upload_batch_id) . '\'' ;
if( ($rs = $conn->query($sql)) )
{
	$nr = $conn->num_rows($rs);
	$conn->free_result($rs);
	
	if($nr == 0)
	{
		err('bad upload_batch_id');
		return;
	}
	
	$sql = 'UPDATE uplbatch SET complete="1" WHERE uplbatch_id="'.$conn->escape_string($upload_batch_id).'"';
	
	if(!$conn->query($sql))
	{
		err('could not finish uplbatch');
		return;
	}
}

// -------------------------------------------------------------------
// do the job
// -------------------------------------------------------------------

$result->appendChild($dom->createElement('upload_batch'))->setAttribute('id', $upload_batch_id);
$xfiles = $result->appendChild($dom->createElement('files'));

$sql = 'SELECT * FROM uplfile WHERE uplbatch_id=\'' . $conn->escape_string($upload_batch_id) . '\' ORDER BY idx ASC' ;
if( ($rs = $conn->query($sql)) )
{
	$nr = $conn->num_rows($rs);
	$result->appendChild($dom->createElement('n_ok'))->appendChild($dom->createTextNode($nr));
	while( ($row = $conn->fetch_assoc($rs)) )
	{
		$xfile = $xfiles->appendChild($dom->createElement('file'));
		$xfile->setAttribute('index', $row['idx']);
		$xfile->appendChild($dom->createElement('filename'))->appendChild($dom->createTextNode($row['filename']));
	}
	$conn->free_result($rs);
}

?>