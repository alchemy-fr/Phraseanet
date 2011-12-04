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
class patch_320ab implements patchInterface
{

  /**
   *
   * @var string
   */
  private $release = '3.2.0.0.a1';
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
    return false;
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
    $sql = 'REPLACE INTO records_rights
            (SELECT null as id, usr_id, b.sbas_id, record_id, "1" as document
              , null as preview, "push" as `case`, pushFrom as pusher_usr_id
              FROM sselcont c, ssel s, bas b
              WHERE c.ssel_id = s.ssel_id
                AND b.base_id = c.base_id AND c.canHD = 1
             )';
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();

    return true;
  }

}
