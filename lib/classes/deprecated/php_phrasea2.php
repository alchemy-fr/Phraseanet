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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
//if (!function_exists('phrasea_list_bases'))
//{
//
//  function phrasea_list_bases()
//  {
//
//    $conn = connection::getPDOConnection();
//    $sql = "SELECT base_id, host, port, dbname, user, pwd, server_coll_id, sbas.sbas_id, viewname
//      FROM (sbas INNER JOIN bas ON bas.sbas_id=sbas.sbas_id)
//      WHERE active>0
//      ORDER BY sbas.ord, sbas.sbas_id, bas.ord";
//    $stmt = $conn->prepare($sql);
//    $stmt->execute();
//    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
//    $stmt->closeCursor();
//
//    $last_sbas_id = false;
//
//    $list = array('bases' => array());
//
//    foreach ($rs as $row)
//    {
//      $basid = $row['base_id'];
//      $sbas_id = $row['sbas_id'];
//      $viewname = (trim($row['viewname']) !== '') ? $row['viewname'] : $row['dbname'];
//
//      try
//      {
//        $connbas = connection::getPDOConnection($sbas_id);
//
//
//        $list['bases'][$sbas_id] = array(
//            'sbas_id' => $sbas_id,
//            'host' => $row['host'],
//            'port' => $row['port'],
//            'dbname' => $row['dbname'],
//            'user' => $row['user'],
//            'pwd' => $row['pwd'],
//            'online' => false,
//            'collections' => array(),
//        );
//
//        $sql = "SELECT asciiname, prefs FROM coll WHERE coll_id = :coll_id";
//        $stmt = $connbas->prepare($sql);
//        $stmt->execute(array(':coll_id' => $row['coll_id']));
//        $row2 = $stmt->fetch(PDO::FETCH_ASSOC);
//
//        $list['bases'][$sbas_id]['collections'][$row['coll_id']] =
//                array('asciiname' => $row2['asciiname'], 'prefs' => $row2['prefs']);
//
//
//        if ($last_sbas_id == $sbas_id)
//          continue;
//
//        $list['bases'][$sbas_id]['online'] = true;
//        $last_sbas_id = $sbas_id;
//      }
//      catch (Exception $e)
//      {
//
//      }
//    }
//  }
//
//}

if (!function_exists('phrasea_open_session'))
{

  function phrasea_open_session($ses_id, $usr_id)
  {
    $conn = connection::getPDOConnection();
    $sql = "UPDATE cache SET nact=nact+1, lastaccess=NOW()
          WHERE session_id = :session_id AND usr_id = :usr_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':session_id' => $ses_id, ':usr_id' => $usr_id));

    return;
  }

}



if (!function_exists('phrasea_clear_cache'))
{

  function phrasea_clear_cache()
  {
    $registry = registry::get_instance();
    $filename = $registry->get('GV_RootPath') . '_phrasea_' . $registry->get('GV_sit') . '.answers.' . $ses_id . '';
    unlink($filename);
    $filename = $registry->get('GV_RootPath') . '_phrasea_' . $registry->get('GV_sit') . '.spots.' . $ses_id . '';
    unlink($filename);
  }

}



if (!function_exists('phrasea_create_session'))
{

  function phrasea_create_session($usr_id)
  {
    try
    {
      $conn = connection::getPDOConnection();
      $sql = "INSERT INTO cache (session_id, nact, lastaccess, answers, spots, session, usr_id)
      VALUES (null, 0, NOW(), '', '', '', :usr_id)";
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':usr_id', $usr_id));
      $ses_id = $conn->lastInsertId();

      return $ses_id;
    }
    catch (Exception $e)
    {
      return false;
    }
  }

}


if (!function_exists('phrasea_register_base'))
{

  function phrasea_register_base($ses_id, $base_id)
  {
    $registered[$base_id] = true;
  }

}

if (!function_exists('phrasea_close_session'))
{

  function phrasea_close_session($ses_id)
  {
    try
    {
      $conn = connection::getPDOConnection();
      $sql = "DELETE FROM cache WHERE session_id= :session_id";
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':session_id', $ses_id));

      return true;
    }
    catch (Exception $e)
    {
      return false;
    }
  }

}
