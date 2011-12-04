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
function deleteRecord($lst, $del_children)
{
  $appbox = appbox::get_instance();
  $session = $appbox->get_session();
  $registry = $appbox->get_registry();

  $usr_id = $session->get_usr_id();

  $ACL = User_Adapter::getInstance($usr_id, $appbox)->ACL();

  $lst = explode(";", $lst);

  $tcoll = array();
  $tbase = array();

  $conn = $appbox->get_connection();

  foreach ($lst as $basrec)
  {
    $basrec = explode("_", $basrec);
    if (!$basrec || count($basrec) !== 2)
      continue;

    $record = new record_adapter($basrec[0], $basrec[1]);
    $base_id = $record->get_base_id();
    if (!isset($tcoll["c" . $base_id]))
    {

      $tcoll["c" . $base_id] = null;

      foreach ($appbox->get_databoxes() as $databox)
      {
        foreach ($databox->get_collections() as $collection)
        {
          if ($collection->get_base_id() == $base_id)
          {
            $tcoll["c" . $base_id] = array("base_id" => $databox->get_sbas_id(), "id" => $base_id);
            if (!isset($tbase["b" . $base_id]))
            {
              $x = $databox->get_structure();
              $tbase["b" . $collection->get_base_id()] = array("id" => $collection->get_base_id(), "rids" => array());
            }
            break;
          }
        }
      }
    }

    $temp = null;
    $temp[0] = $basrec[0];
    $temp[1] = $basrec[1];

    $tbase["b" . $tcoll["c" . $base_id]["base_id"]]["rids"][] = $temp;
  }
  $ret = array();

  foreach ($tbase as $base)
  {
    try
    {
      foreach ($base["rids"] as $rid)
      {
        $record = new record_adapter($rid[0], $rid[1]);
        $rid[2] = $record->get_base_id();
        if (!$ACL->has_right_on_base($record->get_base_id(), 'candeleterecord'))
          continue;
        if ($del_children == "1")
        {
          foreach ($record->get_children() as $oneson)
          {
            if (!$ACL->has_right_on_base($oneson->get_base_id(), 'candeleterecord'))
              continue;

            $oneson->delete();
            $ret[] = implode('_', array($oneson->get_sbas_id(), $oneson->get_record_id(), $oneson->get_base_id()));
          }
        }
        $record->delete();
        unset($record);
        $ret[] = implode('_', $rid);
      }
    }
    catch (Exception $e)
    {

    }
  }

  $sql = array();
  $params = array();
  $n = 0;
  foreach ($ret as $key=>$basrec)
  {
    $br = explode('_', $basrec);
    $sql[] = '(base_id = :base_id' . $n . ' AND record_id = :record_id' . $n . ')';

    $params[':base_id' . $n] = $br[2];
    $params[':record_id' . $n] = $br[1];


    $sql_ssel = 'SELECT ssel_id, usr_id FROM ssel
                  WHERE sbas_id = :sbas_id AND rid = :record_id';

    $stmt = $conn->prepare($sql_ssel);
    $stmt->execute(array(':sbas_id' => $br[0], ':record_id' => $br[1]));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
      try
      {
        $basket = basket_adapter::getInstance($appbox, $row['ssel_id'], $row['usr_id']);
        $basket->delete_cache();
        unset($basket);
      }
      catch (Exception $e)
      {

      }
    }


    $sql_ssel = 'DELETE FROM ssel WHERE sbas_id = :sbas_id AND rid = :record_id';
    $stmt = $conn->prepare($sql_ssel);
    $stmt->execute(array(':sbas_id' => $br[0], ':record_id' => $br[1]));
    $stmt->closeCursor();

    unset($br[2]);
    $ret[$key] = implode('_', $br);
    $n++;
  }

  if (count($sql) > 0)
  {
    $sql_res = 'SELECT DISTINCT ssel.usr_id, ssel.ssel_id FROM sselcont ,ssel
      WHERE (' . implode(' OR ', $sql) . ')
        AND sselcont.ssel_id = ssel.ssel_id AND ssel.usr_id = :usr_id';
    $params[':usr_id'] = $usr_id;

    $stmt = $conn->prepare($sql_res);
    $stmt->execute($params);
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
      try
      {
        $basket = basket_adapter::getInstance($appbox, $row['ssel_id'], $usr_id);
        $basket->delete_cache();
      }
      catch (Exception $e)
      {

      }
    }


    $sql = 'DELETE FROM sselcont WHERE (' . implode(' OR ', $sql) . ')
      AND ssel_id IN (SELECT ssel_id FROM ssel WHERE usr_id = :usr_id)';
    $stmt = $conn->prepare($sql_res);
    $stmt->execute($params);
    $stmt->closeCursor();
  }

  return p4string::jsonencode($ret);
}

function whatCanIDelete($lst)
{

  $appbox = appbox::get_instance();
  $session = $appbox->get_session();

  $usr_id = $session->get_usr_id();

  $conn = $appbox->get_connection();

  $nbdocsel = 0;
  $nbgrp = 0;
  $oksel = array();
  $arrSel = explode(";", $lst);

  if (!is_array($lst))
    $lst = explode(';', $lst);

  foreach ($lst as $sel)
  {
    if ($sel == "")
      continue;
    $exp = explode("_", $sel);

    if (count($exp) !== 2)
      continue;

    $record = new record_adapter($exp[0], $exp[1]);
    $sqlV = 'SELECT mask_and, mask_xor, sb.*
               FROM (sbas sb, bas b, usr u)
                LEFT JOIN basusr bu ON
                  (bu.base_id = b.base_id AND bu.candeleterecord = "1"
                    AND bu.usr_id = u.usr_id AND actif="1")
               WHERE u.usr_id = :usr_id AND b.base_id = :base_id
                AND b.sbas_id = sb.sbas_id';

    $params = array(
        ':base_id' => $record->get_base_id()
        , ':usr_id' => $usr_id
    );

    $stmt = $conn->prepare($sqlV);
    $stmt->execute($params);
    $rowV = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($rowV && $rowV['mask_and'] != '' && $rowV['mask_xor'] != '')
    {
      try
      {
        $connbas = connection::getPDOConnection($rowV['sbas_id']);
        $sqlS2 = 'SELECT record_id FROM record
              WHERE ((status ^ ' . $rowV['mask_xor'] . ') & ' . $rowV['mask_and'] . ')=0
                AND record_id = :record_id';

        $stmt = $connbas->prepare($sqlS2);
        $stmt->execute(array(':record_id' => $exp[1]));
        $num_rows = $stmt->rowCount();
        $stmt->closeCursor();

        if ($num_rows > 0)
        {
          $oksel[] = implode('_', $exp);
          $nbdocsel++;
          if ($record->is_grouping())
            $nbgrp++;
        }
      }
      catch (Exception $e)
      {

      }
    }
  }

  $ret = array('lst' => $oksel, 'groupings' => $nbgrp);

  return p4string::jsonencode($ret);
}
