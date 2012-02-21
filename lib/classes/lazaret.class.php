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
 * Set of Lazaret element (quarantine)
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class lazaret extends set_abstract
{

  /**
   * Constructor
   *
   * @return lazaret
   */
  function __construct()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $session = $appbox->get_session();
    $registry = $appbox->get_registry();
    $conn = $appbox->get_connection();
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    $base_ids = array_keys($user->ACL()->get_granted_base(array('canaddrecord')));

    if (count($base_ids) == 0)

      return $this;

    $sql = "SELECT id, filepath, filename, base_id,
                    uuid, errors, created_on, usr_id
              FROM lazaret WHERE base_id IN (" . implode(', ', $base_ids) . ")
              ORDER BY uuid, id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $lazaret_group = array();

    foreach ($rs as $row)
    {
      $sbas_id = phrasea::sbasFromBas($row['base_id']);

      $row['uuid'] =
              trim($row['uuid']) !== '' ? $row['uuid'] : mt_rand(1000000, 9999999);

      $key = $row['uuid'] . '__' . $sbas_id;

      $pathfile = $registry->get('GV_RootPath') . 'tmp/lazaret/' . $row['filepath'];

      if (!file_exists($pathfile))
      {
        $sql = 'DELETE FROM lazaret WHERE id = :id';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':id' => $row['id']));
        $stmt->closeCursor();

        if (file_exists($pathfile . '_thumbnail.jpg'))
          unlink($pathfile . '_thumbnail.jpg');
        continue;
      }
      if (!isset($lazaret_group[$key]))
        $lazaret_group[$key] = array(
            'candidates' => array(),
            'potentials' => array()
        );

      $pathfile_thumbnail = $pathfile . '_thumbnail.jpg';

      if (is_file($pathfile_thumbnail)
              && $gis = @getimagesize($pathfile_thumbnail))
        $is = $gis;
      else
        $is = array(80, 80);

      $thumbnail = new media_adapter(
                      '/upload/lazaret_image.php?id=' . $row['id'],
                      $is[0],
                      $is[1]
      );

      $row['created_on'] = new DateTime($row['created_on']);

      if ($row['usr_id'])
        $row['usr_id'] = User_Adapter::getInstance($row['usr_id'], $appbox)->get_display_name();
      else
        $row['usr_id'] = _('tache d\'archivage');

      $lazaret_group[$key]['candidates'][$row['id']] = array_merge(
              array(
          'thumbnail' => $thumbnail,
          'title' => $row['filename'],
          'caption' => '',
          'potential_relationship' => array()
              ), $row
      );
    }

    foreach ($lazaret_group as $key_group => $lazaret)
    {
      $infos = explode('__', $key_group);

      $uuid = $infos[0];
      $sbas_id = $infos[1];

      try
      {
        $connbas = connection::getPDOConnection($sbas_id);

        $sql = "SELECT record_id, coll_id FROM record WHERE uuid = :uuid";

        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':uuid' => $uuid));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row)
        {
          $lazaret_group[$key_group]['potentials'][$row['record_id']] =
                  new record_adapter($sbas_id, $row['record_id'], FALSE);
        }
      }
      catch (Exception $e)
      {
        continue;
      }

      foreach ($lazaret['candidates'] as $lazaret_id => $lazaret_item)
      {
        foreach ($lazaret_group[$key_group]['potentials']
        as $record_id => $record)
        {
          $can_substitute = false;

          $potential_base_id = $record->get_base_id();

          if (
                  $user->ACL()->has_right_on_base($potential_base_id, 'canaddrecord') &&
                  $user->ACL()->has_right_on_base($potential_base_id, 'candeleterecord')
          )
            $can_substitute = false;

          $lazaret_group[$key_group]['candidates']
                  [$lazaret_id]['potential_relationship'][$record_id] =
                  array(
                      'can_substitute' => $can_substitute,
                      'same_coll' => ($potential_base_id == $lazaret_item['base_id']),
                      'title' => $record->get_title()
          );
        }
      }
    }
    $this->elements = $lazaret_group;

    return $this;
  }

}
