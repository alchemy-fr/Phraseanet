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
class set_order extends set_abstract
{

  /**
   *
   * @var int
   */
  protected $id;
  /**
   *
   * @var user
   */
  protected $user;
  /**
   *
   * @var int
   */
  protected $todo;
  /**
   *
   * @var DateTime
   */
  protected $created_on;
  /**
   *
   * @var string
   */
  protected $usage;
  /**
   *
   * @var DateTime
   */
  protected $deadline;
  /**
   *
   * @var int
   */
  protected $total;
  /**
   *
   * @var int
   */
  protected $ssel_id;

  /**
   *
   * @param int $id
   * @return set_order
   */
  public function __construct($id)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $conn = $appbox->get_connection();

    $sql = 'SELECT o.id, o.usr_id, o.created_on, o.`usage`, o.deadline,
              COUNT(e.id) as total, o.ssel_id, COUNT(e2.id) as todo
              FROM (`order` o, order_elements e)
              LEFT JOIN order_elements e2
                ON (
                      ISNULL(e2.order_master_id)
                      AND e.id = e2.id
                   )
              WHERE o.id = e.order_id
              AND o.id = :order_id';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':order_id' => $id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      throw new Exception('unknown order ' . $id);

    $current_user = User_Adapter::getInstance($row['usr_id'], $appbox);
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    $this->id = $row['id'];
    $this->user = $current_user;
    $this->todo = $row['todo'];
    $this->created_on = new DateTime($row['created_on']);
    $this->usage = $row['usage'];
    $this->deadline = new DateTime($row['deadline']);
    $this->total = (int) $row['total'];
    $this->ssel_id = (int) $row['ssel_id'];

    $base_ids = array_keys($user->ACL()->get_granted_base(array('order_master')));
    $sql = 'SELECT e.base_id, e.record_id, e.order_master_id, e.id, e.deny
              FROM order_elements e
              WHERE order_id = :order_id
              AND e.base_id
              IN ('.implode(',', $base_ids).')';

    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':order_id' => $id));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $elements = array();

    foreach ($rs as $row)
    {
      $order_master_id = $row['order_master_id'] ? $row['order_master_id'] : false;

      $elements[$row['id']] = new record_orderElement(
                      phrasea::sbasFromBas($row['base_id']),
                      $row['record_id'],
                      $row['deny'],
                      $order_master_id
      );
    }

    $this->elements = $elements;

    return $this;
  }

  /**
   *
   * @return User_Adapter
   */
  public function get_user()
  {
    return $this->user;
  }

  /**
   *
   * @return DateTime
   */
  public function get_created_on()
  {
    return $this->created_on;
  }

  /**
   *
   * @return DateTime
   */
  public function get_deadline()
  {
    return $this->deadline;
  }

  /**
   *
   * @return string
   */
  public function get_usage()
  {
    return $this->usage;
  }

  /**
   *
   * @return int
   */
  public function get_total()
  {
    return $this->total;
  }

  /**
   *
   * @return int
   */
  public function get_order_id()
  {
    return $this->id;
  }

  /**
   *
   * @return int
   */
  public function get_todo()
  {
    return $this->todo;
  }

  /**
   *
   * @param Array $elements_ids
   * @param boolean $force
   * @return set_order
   */
  public function send_elements(Array $elements_ids, $force)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $conn = $appbox->get_connection();
    $pusher = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    $basrecs = array();
    foreach ($elements_ids as $id)
    {
      if (isset($this->elements[$id]))
      {
        $basrecs[$id] = array(
            'base_id' => $this->elements[$id]->get_base_id(),
            'record_id' => $this->elements[$id]->get_record_id()
        );
      }
    }

    $core = \bootstrap::getCore();
    
    $em = $core->getEntityManager();
    $repository = $em->getRepository('\Entities\Basket');
    
    /* @var $repository \Repositories\BasketRepository */
    $Basket = $repository->findUserBasket($this->ssel_id, $core->getAuthenticatedUser());
    
    if(!$Basket)
    {
      $Basket = new Basket();
      $Basket->setName(sprintf(_('Commande du %s'), $this->created_on->format('Y-m-d')));
      $Basket->setOwner($this->user);
      $Basket->setPusher($core->getAuthenticatedUser());
      
      $em->persist($Basket);
      $em->flush();
      
      $this->ssel_id = $Basket->getId();
      
      $sql = 'UPDATE `order` SET ssel_id = :ssel_id WHERE id = :order_id';
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':ssel_id' => $Basket->getId(), ':order_id' => $this->id));
      $stmt->closeCursor();
    }

    $n = 0;

    $sql = 'UPDATE order_elements
              SET deny="0", order_master_id = :usr_id
              WHERE order_id = :order_id
                AND id = :order_element_id';

    if ($force == '0')
    {
      $sql .= ' AND ISNULL(order_master_id)';
    }
    $stmt = $conn->prepare($sql);

    foreach ($basrecs as $order_element_id => $basrec)
    {
      try
      {
        $sbas_id = phrasea::sbasFromBas($basrec['base_id']);
        $record = new record_adapter($sbas_id, $basrec['record_id']);
        $ret = $basket->push_element($record, false, false);
        if ($ret['error'] === false)
        {
          $params = array(
              ':usr_id' => $session->get_usr_id()
              , ':order_id' => $this->id
              , ':order_element_id' => $order_element_id
          );

          $stmt->execute($params);

          $n++;
          $this->user->ACL()->grant_hd_on($record, $pusher, 'order');
        }
        unset($record);
      }
      catch (Exception $e)
      {

      }
    }
    $stmt->closeCursor();

    if ($n > 0)
    {
      $evt_mngr = eventsmanager_broker::getInstance($appbox, $core);

      $params = array(
          'ssel_id' => $this->ssel_id,
          'from' => $session->get_usr_id(),
          'to' => $this->user->get_id(),
          'n' => $n
      );

      $evt_mngr->trigger('__ORDER_DELIVER__', $params);
    }

    return $this;
  }

  /**
   *
   * @param Array $elements_ids
   * @return set_order
   */
  public function deny_elements(Array $elements_ids)
  {
    $Core = bootstrap::getCore();
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $conn = $appbox->get_connection();

    $n = 0;

    foreach ($elements_ids as $order_element_id)
    {
      $sql = 'UPDATE order_elements
                SET deny="1", order_master_id = :order_master_id
                WHERE order_id = :order_id AND id = :order_element_id
                AND ISNULL(order_master_id)';

      $params = array(
          ':order_master_id' => $session->get_usr_id()
          , ':order_id' => $this->id
          , ':order_element_id' => $order_element_id
      );
      $stmt = $conn->prepare($sql);
      $stmt->execute($params);
      $stmt->closeCursor();
      $n++;
    }


    if ($n > 0)
    {
      $evt_mngr = eventsmanager_broker::getInstance($appbox, $Core);

      $params = array(
          'from' => $session->get_usr_id(),
          'to' => $this->user->get_id(),
          'n' => $n
      );

      $evt_mngr->trigger('__ORDER_NOT_DELIVERED__', $params);
    }

    return $this;
  }

}
