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
class patch_360 implements patchInterface
{

  /**
   *
   * @var string
   */
  private $release = '3.6.0.0.a1';
  /**
   *
   * @var Array
   */
  private $concern = array(base::APPLICATION_BOX);

  /**
   *
   * @return string
   */
  function get_release()
  {
    return $this->release;
  }

  public function require_all_upgrades()
  {
    return true;
  }

  /**
   *
   * @return Array
   */
  function concern()
  {
    return $this->concern;
  }

  function apply(base &$appbox)
  {
//    $Core = bootstrap::getCore();
//    
//    $sql = 'SELECT ssel_id, name FROM ssel';
//
//    $stmt = $appbox->get_connection()->prepare($sql);
//    $stmt->execute();
//    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
//    $stmt->closeCursor();
    
    
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();
    
    $sql = 'INSERT INTO StoryWZ 
      (
        SELECT null as id, sbas_id, rid as record_id, usr_id, date as created
        FROM ssel 
        WHERE temporaryType = "1"
      )';
    
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();
    
    $sql = 'INSERT INTO Baskets 
      (
        SELECT ssel_id as id, name, descript as description, usr_id
          , pushFrom as pusher_id, 
          0 as archived, date as created, updater as updated, 1 as is_read 
        FROM ssel 
        WHERE temporaryType = "0"
      )';
    
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();
    
    $sql = 'UPDATE Baskets SET pusher_id = NULL WHERE pusher_id = 0';
    
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();
    
    $sql = 'INSERT INTO ValidationSessions 
      (
        SELECT id, v.ssel_id as basket_id ,created_on as created
          ,updated_on as updated ,expires_on as expires
          ,v.usr_id as initiator_id
        FROM ssel s, validate v
        WHERE v.ssel_id = s.ssel_id AND v.usr_id = s.usr_id
      )';
    
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();
    
    $sql = 'INSERT INTO ValidationParticipants 
      (
        SELECT id, v.usr_id, id AS ValidationSession_id
            , 1 AS is_aware, last_reminder AS reminded
          FROM validate v, ssel s
          WHERE s.ssel_id = v.ssel_id
      )';
    
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();
    
    $sql = 'INSERT INTO ValidationDatas 
      (
        SELECT null as id, v.id as participant_id
            , d.sselcont_id as basket_element_id
            , d.agreement, d.note, d.updated_on as updated
          FROM validate v, ssel s, validate_datas as d 
          WHERE s.ssel_id = v.ssel_id and v.id = d.validate_id
      )';
    
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();
    
    $sql = 'UPDATE ValidationDatas 
       SET agreement = NULL where agreement = "0"';
    
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();
    
    $sql = 'UPDATE ValidationDatas 
       SET agreement = "0" where agreement = "-1"';
    
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();
    
    return true;
  }

}
