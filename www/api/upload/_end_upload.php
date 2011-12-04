<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
//SPECIAL ZINO
ini_set('display_errors', 'off');
ini_set('display_startup_errors', 'off');
ini_set('log_errors', 'off');
//SPECIAL ZINO

$upload_batch_id = (string) ($sxParms->upload_batch_id);


// -------------------------------------------------------------------
// check cnx & session
// -------------------------------------------------------------------

try
{
  $conn = connection::getPDOConnection();
}
catch (Exception $e)
{
  err('bad conn');

  return;
}

if (!$ses_id || !$usr_id || !$upload_batch_id)
{
  err('missing ses_id, usr_id or upload_batch_id');

  return;
}

// -------------------------------------------------------------------
// check batch
// -------------------------------------------------------------------

$sql = 'SELECT * FROM uplbatch WHERE uplbatch_id = :upload_id';
$stmt = $conn->prepare($sql);
$stmt->execute(array('upload_id' => $upload_batch_id));
$nr = $stmt->rowCount();
$stmt->closeCursor();

if ($nr == 0)
{
  err('bad upload_batch_id');

  return;
}

try
{
  $sql = 'UPDATE uplbatch SET complete="1" WHERE uplbatch_id = :upload_id';
  $stmt = $conn->prepare($sql);
  $stmt->execute(array('upload_id' => $upload_batch_id));
  $stmt->closeCursor();
}
catch (Exception $e)
{
  err('could not finish uplbatch');

  return;
}

// -------------------------------------------------------------------
// do the job
// -------------------------------------------------------------------

$result->appendChild($dom->createElement('upload_batch'))->setAttribute('id', $upload_batch_id);
$xfiles = $result->appendChild($dom->createElement('files'));

$sql = 'SELECT * FROM uplfile WHERE uplbatch_id = :upload_id ORDER BY idx ASC';
$stmt = $conn->prepare($sql);
$stmt->execute(array('upload_id' => $upload_batch_id));
$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$nr = $stmt->rowCount();
$result->appendChild($dom->createElement('n_ok'))->appendChild($dom->createTextNode($nr));
foreach ($rs as $row)
{
  $xfile = $xfiles->appendChild($dom->createElement('file'));
  $xfile->setAttribute('index', $row['idx']);
  $xfile->appendChild($dom->createElement('filename'))->appendChild($dom->createTextNode($row['filename']));
}
