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
class module_admin
{

  function getTree($position=false)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();

    $usr_id = $session->get_usr_id();

    $user = User_Adapter::getInstance($usr_id, $appbox);

    $available = array(
        'connected'
        , 'registrations'
        , 'taskmanager'
        , 'base'
        , 'bases'
        , 'collection'
        , 'user'
        , 'users'
    );

    $feature = 'connected';
    $featured = false;
    $position = explode(':', $position);
    if (count($position) > 0)
    {
      if (in_array($position[0], $available))
      {
        $feature = $position[0];
        if (isset($position[1]))
          $featured = $position[1];
      }
    }

    $databoxes = $off_databoxes = array();
    foreach ($appbox->get_databoxes() as $databox)
    {
      try
      {
        if (!$user->ACL()->has_access_to_sbas($databox->get_sbas_id()))
          continue;

        $connbas = $databox->get_connection();
      }
      catch (Exception $e)
      {
        $off_databoxes[] = $databox;
        continue;
      }
      $databoxes[] = $databox;
    }

    $params = array(
        'feature' => $feature
        , 'featured' => $featured
        , 'databoxes' => $databoxes
        , 'off_databoxes' => $off_databoxes
    );

    $twig = new supertwig();

    return $twig->render('admin/tree.html.twig', $params);

  }

}

