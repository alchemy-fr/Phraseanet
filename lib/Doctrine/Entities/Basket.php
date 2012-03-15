<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

require_once __DIR__ . '/../../classes/cache/cacheableInterface.class.php';
require_once __DIR__ . '/../../classes/User/Interface.class.php';
require_once __DIR__ . '/../../classes/User/Adapter.class.php';

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Basket
{

  /**
   * @var integer $id
   */
  protected $id;

  /**
   * @var string $name
   */
  protected $name;

  /**
   * @var text $description
   */
  protected $description;

  /**
   * @var integer $usr_id
   */
  protected $usr_id;

  /**
   * @var integer $pusher_id
   */
  protected $pusher_id;

  /**
   * @var boolean $archived
   */
  protected $archived = false;

  /**
   * @var datetime $created
   */
  protected $created;

  /**
   * @var datetime $updated
   */
  protected $updated;

  /**
   * @var Entities\BasketElement
   */
  protected $elements;

  public function __construct()
  {
    $this->elements = new \Doctrine\Common\Collections\ArrayCollection();
  }

  /**
   * Get id
   *
   * @return integer
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set name
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }

  /**
   * Get name
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set description
   *
   * @param text $description
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }

  /**
   * Get description
   *
   * @return text
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * Set usr_id
   *
   * @param integer $usrId
   */
  public function setUsrId($usrId)
  {
    $this->usr_id = $usrId;
  }

  /**
   * Get usr_id
   *
   * @return integer
   */
  public function getUsrId()
  {
    return $this->usr_id;
  }

  /**
   * Set pusher_id
   *
   * @param integer $pusherId
   */
  public function setPusherId($pusherId)
  {
    $this->pusher_id = $pusherId;
  }

  /**
   * Get pusher_id
   *
   * @return integer
   */
  public function getPusherId()
  {
    return $this->pusher_id;
  }

  /**
   * Set archived
   *
   * @param boolean $archived
   */
  public function setArchived($archived)
  {
    $this->archived = $archived;
  }

  /**
   * Get archived
   *
   * @return boolean
   */
  public function getArchived()
  {
    return $this->archived;
  }

  /**
   * Set created
   *
   * @param datetime $created
   */
  public function setCreated($created)
  {
    $this->created = $created;
  }

  /**
   * Get created
   *
   * @return datetime
   */
  public function getCreated()
  {
    return $this->created;
  }

  /**
   * Set updated
   *
   * @param datetime $updated
   */
  public function setUpdated($updated)
  {
    $this->updated = $updated;
  }

  /**
   * Get updated
   *
   * @return datetime
   */
  public function getUpdated()
  {
    return $this->updated;
  }

  /**
   * Add elements
   *
   * @param Entities\BasketElement $elements
   */
  public function addBasketElement(\Entities\BasketElement $elements)
  {
    $this->elements[] = $elements;
  }

  /**
   * Get elements
   *
   * @return Doctrine\Common\Collections\Collection
   */
  public function getElements()
  {
    return $this->elements;
  }

  public function setPusher(\User_Adapter $user)
  {
    $this->setPusherId($user->get_id());
  }

  public function getPusher()
  {
    if ($this->getPusherId())
    {
      return new \User_Adapter($this->getPusherId(), \appbox::get_instance(\bootstrap::getCore()));
    }
  }

  public function setOwner(\User_Adapter $user)
  {
    $this->setUsrId($user->get_id());
  }

  public function getOwner()
  {
    if ($this->getUsrId())
    {
      return new \User_Adapter($this->getUsrId(), \appbox::get_instance(\bootstrap::getCore()));
    }
  }

  /**
   * @var Entities\ValidationSession
   */
  protected $validation;

  /**
   * Set validation
   *
   * @param Entities\ValidationSession $validation
   */
  public function setValidation(\Entities\ValidationSession $validation)
  {
    $this->validation = $validation;
  }

  /**
   * Get validation
   *
   * @return Entities\ValidationSession
   */
  public function getValidation()
  {
    return $this->validation;
  }

  /**
   * @var boolean $is_read
   */
  protected $is_read = true;

  /**
   * Set is_read
   *
   * @param boolean $isRead
   */
  public function setIsRead($isRead)
  {
    $this->is_read = $isRead;
  }

  /**
   * Get is_read
   *
   * @return boolean
   */
  public function getIsRead()
  {
    return $this->is_read;
  }

  public function hasRecord(\record_adapter $record)
  {
    foreach ($this->getElements() as $basket_element)
    {
      $bask_record = $basket_element->getRecord();

      if ($bask_record->get_record_id() == $record->get_record_id()
              && $bask_record->get_sbas_id() == $record->get_sbas_id())
      {
        return true;
      }
    }

    return false;
  }

  public function getSize()
  {
    $totSize = 0;

    foreach ($this->getElements() as $basket_element)
    {
      try
      {
        $totSize += $basket_element->getRecord()
                ->get_subdef('document')
                ->get_size();
      }
      catch (Exception $e)
      {

      }
    }

    $totSize = round($totSize / (1024 * 1024), 2);

    return $totSize;
  }

}
