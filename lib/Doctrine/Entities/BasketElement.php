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

require_once __DIR__ . '/../../classes/record/Interface.class.php';
require_once __DIR__ . '/../../classes/record/adapter.class.php';

/**
 * Kernel
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class BasketElement
{

  /**
   * @var integer $id
   */
  private $id;

  /**
   * @var integer $record_id
   */
  private $record_id;

  /**
   * @var integer $sbas_id
   */
  private $sbas_id;

  /**
   * @var integer $ord
   */
  private $ord;

  /**
   * @var datetime $created
   */
  private $created;

  /**
   * @var datetime $updated
   */
  private $updated;

  /**
   * @var Entities\Basket
   */
  private $basket;

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
   * Set record_id
   *
   * @param integer $recordId
   */
  public function setRecordId($recordId)
  {
    $this->record_id = $recordId;
  }

  /**
   * Get record_id
   *
   * @return integer 
   */
  public function getRecordId()
  {
    return $this->record_id;
  }

  /**
   * Set sbas_id
   *
   * @param integer $sbasId
   */
  public function setSbasId($sbasId)
  {
    $this->sbas_id = $sbasId;
  }

  /**
   * Get sbas_id
   *
   * @return integer 
   */
  public function getSbasId()
  {
    return $this->sbas_id;
  }

  /**
   * Set ord
   *
   * @param integer $ord
   */
  public function setOrd($ord)
  {
    $this->ord = $ord;
  }

  /**
   * Get ord
   *
   * @return integer 
   */
  public function getOrd()
  {
    return $this->ord;
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
   * Set basket
   *
   * @param Entities\Basket $basket
   */
  public function setBasket(\Entities\Basket $basket)
  {
    $this->basket = $basket;
  }

  /**
   * Get basket
   *
   * @return Entities\Basket 
   */
  public function getBasket()
  {
    return $this->basket;
  }

  public function getRecord()
  {
    return new \record_adapter($this->getSbasId(), $this->getRecordId(), $this->getOrd());
  }

  public function setRecord(\record_adapter $record)
  {
    $this->setRecordId($record->get_record_id());
    $this->setSbasId($record->get_sbas_id());
  }

  public function setLastInBasket()
  {
    $this->setOrd($this->getBasket()->getElements()->count() + 1);
  }

  /**
   * @var Entities\ValidationData
   */
  private $validation_datas;

  public function __construct()
  {
    $this->validation_datas = new \Doctrine\Common\Collections\ArrayCollection();
  }

  /**
   * Add validation_datas
   *
   * @param Entities\ValidationData $validationDatas
   */
  public function addValidationData(\Entities\ValidationData $validationDatas)
  {
    $this->validation_datas[] = $validationDatas;
  }

  /**
   * Get validation_datas
   *
   * @return Doctrine\Common\Collections\Collection 
   */
  public function getValidationDatas()
  {
    return $this->validation_datas;
  }

  /**
   *
   * @param \User_Adapter $user
   * @return \Entities\ValidationData 
   */
  public function getUserValidationDatas(\User_Adapter $user)
  {
    foreach ($this->validation_datas as $validationData)
    {
      /* @var $validationData \Entities\ValidationData */
      if ($validationData->getParticipant()->getUser()->get_id() == $user->get_id())
      {
        return $validationData;
      }
    }

    throw new \Exception('There is no such participant');
  }

}