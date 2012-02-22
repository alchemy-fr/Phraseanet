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
class record_orderElement extends record_adapter
{

  /**
   *
   * @var boolean
   */
  protected $deny;
  /**
   *
   * @var int
   */
  protected $order_master_id;

  /**
   *
   * @param int $base_id
   * @param int $record_id
   * @param boolean $deny
   * @param int $order_master_id
   */
  public function __construct($sbas_id, $record_id, $deny, $order_master_id)
  {
    $this->deny = !!$deny;
    $this->order_master_id = $order_master_id;

    parent::__construct($sbas_id, $record_id);

    $this->get_subdefs();

    return $this;
  }

  /**
   *
   * @return string
   */
  public function get_order_master_name()
  {
    if ($this->order_master_id)
    {
      $user = User_Adapter::getInstance($this->order_master_id, appbox::get_instance(\bootstrap::getCore()));

      return $user->get_display_name();
    }

    return '';
  }

  /**
   *
   * @return int
   */
  public function get_order_master_id()
  {
    return $this->order_master_id;
  }

  /**
   *
   * @return boolean
   */
  public function get_deny()
  {
    return $this->deny;
  }

}
