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

$base_id = (string) ($sxParms->base_id);
$nfiles = (int) ($sxParms->nfiles);

try
{
  $conn = connection::getPDOConnection();
}
catch (Exception $e)
{
  err('bad conn');

  return;
}

if (!$ses_id || !$usr_id)
{
  err('!ses_id || !usr_id');

  return;
}

try
{
  $sql = 'INSERT INTO uplbatch (base_id, nfiles, usr_id)
        VALUES (:base_id, :nfiles, :usr_id);';

  $params = array(
      ':base_id' => $base_id
      , ':nfiles' => $nfiles
      , ':usr_id' => $usr_id
  );

  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  $stmt->closeCursor();
  $uplbatch_id = $conn->lastInsertId();

  $result->appendChild($dom->createElement('upload_batch_id'))->appendChild($dom->createTextNode((string) $uplbatch_id));
}
catch (Exception $e)
{

}
