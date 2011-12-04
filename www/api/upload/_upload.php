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
$index = (int) ($sxParms->index);
$filename = (string) ($sxParms->filename);
$filesize = (int) ($sxParms->filesize);
$md5 = (string) ($sxParms->md5);
//$crc32          = (int)($sxParms->crc32);
// -------------------------------------------------------------------
// check parms, size, name, md5 etc...
// between post and _FILE
// -------------------------------------------------------------------

if (count($_FILES) != 1 || !array_key_exists('file', $_FILES))
{
  err('$_FILES[\'file\'] unknown');

  return;
}
$fil = $_FILES['file']['tmp_name'];
$tmp_filesize = filesize($fil);
$tmp_filename = $_FILES['file']['name'];
$tmp_k = file_get_contents($fil);
$tmp_md5 = md5($tmp_k, false);
//$tmp_crc32      = crc32($tmp_k);

if ($tmp_filename != $filename)
{
  err('filename : \'' . $tmp_filename . '\' != \'' . $filename . '\'');

  return;
}
if ($tmp_md5 != $md5)
{
  err('md5 : \'' . $tmp_md5 . '\' != \'' . $md5 . '\'');

  return;
}

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

$batchok = false;
$batch_nfiles = false;

$sql = 'SELECT * FROM uplbatch WHERE uplbatch_id = :upload_id';

$stmt = $conn->prepare($sql);
$stmt->execute(array('upload_id' => $upload_batch_id));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if ($row)
{
  $batch_nfiles = $row['nfiles'];
  $batchok = true;
}

if (!$batchok)
{
  err('bad upload_batch_id');

  return;
}

if ($index > $batch_nfiles)
{
  err('index : ' . $index . ' > ' . $batch_nfiles);

  return;
}

$registry = registry::get_instance();

$dir = $registry->get('GV_RootPath') . 'tmp/batches/' . $upload_batch_id . '/';

if (!is_dir($dir))
{
  @mkdir($dir, 0777, true);
}

//$tmp_k          = file_get_contents($fil);
if (is_dir($dir))
{
  if (!@move_uploaded_file($fil, $dir . $index))
  {
    err('error moving file \'' . $fil . '\' to \'' . $dir . $index . '\'');

    return;
  }
}
else
{
  err('error creating tmp folder \'' . $dir . '\'');

  return;
}


// -------------------------------------------------------------------
// ok, add (or replace) the file to the batch
// -------------------------------------------------------------------

$sql = 'REPLACE INTO uplfile (uplbatch_id, idx, filename)
        VALUES (:upload_id, :idx, :filename) ';

$params = array(
    ':upload_id' => $upload_batch_id
    , ':idx' => $index
    , ':filename' => $filename
);

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$affected = $stmt->rowCount();
$stmt->closeCursor();

$xupload_batch = $result->appendChild($dom->createElement('upload_batch'));
$xupload_batch->setAttribute('id', $upload_batch_id);
$xupload_batch->appendChild($dom->createElement('nfiles'))->appendChild($dom->createTextNode($batch_nfiles));

$xindex = $result->appendChild($dom->createElement('index'));
$xindex->appendChild($dom->createTextNode((string) $index));
if ($affected == 1)
{
  // inserted
  $xindex->setAttribute('action', 'INSERTED');
}
elseif ($affected == 2)
{
  // inserted
  $xindex->setAttribute('action', 'REPLACED');
}
?>
