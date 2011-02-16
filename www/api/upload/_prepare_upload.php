<?php

//SPECIAL ZINO
ini_set('display_errors','off');
ini_set('display_startup_errors','off');
ini_set('log_errors','off');
//SPECIAL ZINO

$base_id = (string)($sxParms->base_id);
$nfiles = (int)($sxParms->nfiles);

$conn = connection::getInstance();
if(!$conn)
{
	err('bad conn');
	return ;
}
	
if(!$ses_id || !$usr_id)
{
	err('!ses_id || !usr_id');
	return;
}

if( !$ph_session = phrasea_open_session($ses_id, $usr_id) )
{
	err('phrasea_open_session failed');
	return;
}

$sql = 'INSERT INTO uplbatch (base_id, nfiles, usr_id) VALUES (\''.$conn->escape_string($base_id).'\', \''.$conn->escape_string($nfiles).'\', \''.$conn->escape_string($usr_id).'\');' ;
if($conn->query($sql))
{
	$uplbatch_id = $conn->insert_id();
	
	$result->appendChild($dom->createElement('upload_batch_id'))->appendChild($dom->createTextNode((string)$uplbatch_id));
}