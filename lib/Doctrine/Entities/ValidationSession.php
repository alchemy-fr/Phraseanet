<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

use Alchemy\Phrasea\Application;

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
     * @var boolean $archived
     */
    protected $archived;

    /**
     * @var datetime $created
     */
    protected $created;

    /**
     * @var datetime $updated
     */
    protected $updated;

    /**
     * @var datetime $expires
     */
    protected $expires;

    /**
     * @var datetime $reminded
     */
    protected $reminded;

    /**
     * @var Entities\Basket
     */
    protected $basket;

    /**
     * @var Entities\ValidationParticipant
     */
    protected $participants;

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
    public function getParticipant(\User_Adapter $user, Application $app)
    {
        foreach ($this->getParticipants() as $participant) {
            if ($participant->getUser($app)->get_id() == $user->get_id()) {
                return $participant;
            }
        }

        throw new \Exception_NotFound('Particpant not found' . $user->get_email());
    }
    /**
     * @var integer $initiator
     */
    protected $initiator;

    /**
     * @var integer $initiator_id
     */
    protected $initiator_id;

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

    public function getInitiator(Application $app)
    {
        if ($this->initiator_id) {
            return \User_Adapter::getInstance($this->initiator_id, $app);
        }
    }

    public function isFinished()
    {
        if (is_null($this->getExpires())) {
            return null;
        }

        $date_obj = new \DateTime();

        return $date_obj > $this->getExpires();
    }

    public function getValidationString(Application $app, \User_Adapter $user)
    {

        if ($this->isInitiator($user)) {
            if ($this->isFinished()) {
                return sprintf(
                        _('Vous aviez envoye cette demande a %d utilisateurs')
                        , (count($this->getParticipants()) - 1)
                );
            } else {
                return sprintf(
                        _('Vous avez envoye cette demande a %d utilisateurs')
                        , (count($this->getParticipants()) - 1)
                );
            }
        } else {
            if ($this->getParticipant($user, $app)->getCanSeeOthers()) {
                return sprintf(
                        _('Processus de validation recu de %s et concernant %d utilisateurs')
                        , $this->getInitiator($app)->get_display_name()
                        , (count($this->getParticipants()) - 1));
            } else {
                return sprintf(
                        _('Processus de validation recu de %s')
                        , $this->getInitiator($app)->get_display_name()
                );
            }
        }
    }
}