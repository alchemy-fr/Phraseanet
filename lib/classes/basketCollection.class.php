<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class basketCollection
{

  private $baskets = array();

  /**
   * @param string $order (optionnal name_asc or date_desc - defaut to name_asc)
   * @param array $except (array of element not return. available values are regroup baskets and recept)
   * @return basketCollectionObject
   */
  function __construct(appbox $appbox, $usr_id, $order='name asc', $except = array())
  {
    $user = User_Adapter::getInstance($usr_id, $appbox);

    $current_timestamp_obj = new DateTime();
    $current_timestamp = $current_timestamp_obj->format('U');

    $sql = 'SELECT ssel_id FROM ssel WHERE usr_id = :usr_id
                AND temporaryType="0"';
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':usr_id' => $usr_id));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (count($rs) === 0)
    {
      $basket = basket_adapter::create($appbox, '', $user);
    }

    $baskets = array();
    $baskets['baskets'] = $baskets['recept'] = $baskets['regroup'] = array();

    $sql = 'SELECT s.ssel_id, s.usr_id as owner, v.id as validate_id,
              s.temporaryType, s.pushFrom, v.expires_on FROM ssel s
            LEFT JOIN validate v
              ON (v.ssel_id = s.ssel_id AND v.usr_id = :usr_id_v)
            WHERE (s.usr_id = :usr_id_o OR v.id IS NOT NULL)';

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':usr_id_o' => $usr_id, ':usr_id_v' => $usr_id));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
        $is_mine = ($row['owner'] == $usr_id);

        $expires_on_obj = new DateTime($row['expires_on']);
        $expires_on = $expires_on_obj->format('U');

        if ($row['validate_id'] != null && !$is_mine && $expires_on < $current_timestamp)
          continue;

        if ($row['temporaryType'] == '1')
          $baskets['regroup'][] = basket_adapter::getInstance($appbox, $row['ssel_id'], $usr_id);
        elseif (!is_null($row['validate_id']))
          $baskets['baskets'][] = basket_adapter::getInstance($appbox, $row['ssel_id'], $usr_id);
        elseif ((int) $row['pushFrom'] > 0)
          $baskets['recept'][] = basket_adapter::getInstance($appbox, $row['ssel_id'], $usr_id);
        else
          $baskets['baskets'][] = basket_adapter::getInstance($appbox, $row['ssel_id'], $usr_id);
    }

    $to_remove = array_intersect(array('recept', 'regroup', 'baskets'), $except);

    foreach ($to_remove as $type)
      $baskets[$type] = array();

    if ($order == 'name asc')
    {
      uasort($baskets['baskets'], array('basketCollection', 'story_name_sort'));
      uasort($baskets['regroup'], array('basketCollection', 'story_name_sort'));
      uasort($baskets['recept'], array('basketCollection', 'story_name_sort'));
    }
    if ($order == 'date desc')
    {
      uasort($baskets['baskets'], array('basketCollection', 'story_date_sort'));
      uasort($baskets['regroup'], array('basketCollection', 'story_date_sort'));
      uasort($baskets['recept'], array('basketCollection', 'story_date_sort'));
    }

    $this->baskets = $baskets;

    return $this;
  }

  public function get_baskets()
  {
    return $this->baskets;
  }

  function get_names()
  {
    $array_names = array();

    foreach ($this->baskets as $type_name => $type)
    {
      foreach ($type as $basket)
      {

        $array_names[] = array('ssel_id' => $basket->get_ssel_id(), 'name' => $basket->get_name(), 'type' => $type_name);
      }
    }

    return $array_names;
  }

  function story_date_sort($a, $b)
  {
    if (!$a->create || !$b->create)

      return 0;

    $comp = strcasecmp($a->create, $b->create);

    if ($comp == 0)

      return 0;

    return $comp < 0 ? -1 : 1;
  }

  function story_name_sort($a, $b)
  {
    if (!$a->get_name() || !$b->get_name())
    {
      return 0;
    }
    $comp = strcasecmp($a->get_name(), $b->get_name());

    if ($comp == 0)

      return 0;

    return $comp < 0 ? -1 : 1;
  }

  public static function get_updated_baskets()
  {
    $appbox = appbox::get_instance();
    $conn = $appbox->get_connection();
    $session = $appbox->get_session();
    $sql = 'SELECT n.ssel_id FROM sselnew n 
            WHERE n.usr_id = :usr_id ';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':usr_id' => $session->get_usr_id()));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $baskets = array();
    foreach($rs as $row)
    {
      try
      {
        $basket = basket_adapter::getInstance($appbox, $row['ssel_id'], $session->get_usr_id());
        
        if ($basket->is_valid() && !$basket->is_my_valid() && $basket->is_validation_finished())
          throw new Exception('Finished');
        
        $baskets[] = $basket;
        
      }
      catch(Exception $e)
      {
        $sql = 'DELETE FROM sselnew WHERE ssel_id = :ssel_id AND usr_id = :usr_id';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':usr_id' => $session->get_usr_id(), ':ssel_id' => $row['ssel_id']));
        $stmt->closeCursor();
      }
    }

    return $baskets;
  }

}
