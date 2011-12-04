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
class patch_320e implements patchInterface
{

  /**
   *
   * @var string
   */
  private $release = '3.2.0.0.a6';

  /**
   *
   * @var Array
   */
  private $concern = array(base::DATA_BOX);

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

  function apply(base &$databox)
  {
    $sql = 'UPDATE record r, subdef s
              SET r.mime = s.mime
              WHERE r.record_id = s.record_id AND s.name="document"';
    $stmt = $databox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();

    $sql = 'UPDATE subdef s, record r
              SET s.updated_on = r.moddate, s.created_on = r.credate
              WHERE s.record_id = r.record_id';
    $stmt = $databox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();

    $sql = 'UPDATE subdef SET `name` = LOWER( `name` )';
    $stmt = $databox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();


    $dom = $databox->get_dom_structure();
    $xpath = $databox->get_xpath_structure();

    $nodes = $xpath->query('//record/subdefs/subdefgroup/subdef');

    foreach ($nodes as $node)
    {
      $name = mb_strtolower(trim($node->getAttribute('name')));
      if ($name === '')
        continue;
      $node->setAttribute('name', $name);
    }

    $databox->saveStructure($dom);

    return true;
  }

}
