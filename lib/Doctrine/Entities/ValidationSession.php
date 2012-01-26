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

/**
 * Kernel
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ValidationSession
{

  /**
   * @var integer $id
   */
  private $id;

  /**
   * @var string $name
   */
  private $name;

  /**
   * @var text $description
   */
  private $description;

  /**
   * @var boolean $archived
   */
  private $archived;

  /**
   * @var datetime $created
   */
  private $created;

  /**
   * @var datetime $updated
   */
  private $updated;

  /**
   * @var datetime $expires
   */
  private $expires;

  /**
   * @var datetime $reminded
   */
  private $reminded;

  /**
   * @var Entities\Basket
   */
  private $basket;

  /**
   * @var Entities\ValidationParticipant
   */
  private $participants;

  public function __construct()
  {
    $this->participants = new \Doctrine\Common\Collections\ArrayCollection();
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
   * Set expires
   *
   * @param datetime $expires
   */
  public function setExpires($expires)
  {
    $this->expires = $expires;
  }

  /**
   * Get expires
   *
   * @return datetime
   */
  public function getExpires()
  {
    return $this->expires;
  }

  /**
   * Set reminded
   *
   * @param datetime $reminded
   */
  public function setReminded($reminded)
  {
    $this->reminded = $reminded;
  }

  /**
   * Get reminded
   *
   * @return datetime
   */
  public function getReminded()
  {
    return $this->reminded;
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

  /**
   * Add participants
   *
   * @param Entities\ValidationParticipant $participants
   */
  public function addValidationParticipant(\Entities\ValidationParticipant $participants)
  {
    $this->participants[] = $participants;
  }

  /**
   * Get participants
   *
   * @return Doctrine\Common\Collections\Collection
   */
  public function getParticipants()
  {
    return $this->participants;
  }

  /**
   * Get a participant
   *
   * @return Entities\ValidationParticipant
   */
  public function getParticipant(\User_Adapter $user)
  {
    foreach ($this->getParticipants() as $participant)
    {
      if ($participant->getUser()->get_id() == $user->get_id())
      {
        return $participant;
      }
    }

    throw new \Exception_NotFound('Particpant not found');
  }

  /**
   * @var integer $initiator
   */
  private $initiator;

  /**
   * @var integer $initiator_id
   */
  private $initiator_id;

  /**
   * Set initiator_id
   *
   * @param integer $initiatorId
   */
  public function setInitiatorId($initiatorId)
  {
    $this->initiator_id = $initiatorId;
  }

  /**
   * Get initiator_id
   *
   * @return integer
   */
  public function getInitiatorId()
  {
    return $this->initiator_id;
  }

  public function isInitiator(\User_Adapter $user)
  {
    return $this->getInitiatorId() == $user->get_id();
  }

  public function setInitiator(\User_Adapter $user)
  {
    $this->initiator_id = $user->get_id();

    return;
  }

  public function getInitiator()
  {
    if ($this->initiator_id)
    {
      return \User_Adapter::getInstance($this->initiator_id, \appbox::get_instance());
    }
  }

  public function isFinished()
  {
    if (is_null($this->getExpires()))
    {
      return null;
    }

    $date_obj = new DateTime();

    return $date_obj > $this->getExpires();
  }

  public function getValidationString(\User_Adapter $user)
  {

    if ($this->isInitiator($user))
    {
      if ($this->isFinished())
      {
        return sprintf(
                        _('Vous aviez envoye cette demande a %d utilisateurs')
                        , (count($this->getParticipants()) - 1)
        );
      }
      else
      {
        return sprintf(
                        _('Vous avez envoye cette demande a %d utilisateurs')
                        , (count($this->getParticipants()) - 1)
        );
      }
    }
    else
    {
      if ($this->getParticipant($user)->getCanSeeOthers())
      {
        return sprintf(
                        _('Processus de validation recu de %s et concernant %d utilisateurs')
                        , $this->getInitiator()->get_display_name()
                        , (count($this->getParticipants()) - 1));
      }
      else
      {
        return sprintf(
                        _('Processus de validation recu de %s')
                        , $this->getInitiator()->get_display_name()
        );
      }
    }
  }

}
