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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @ORM\Table(name="ValidationSessions")
 * @ORM\Entity
 */
class ValidationSession
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $initiator_id;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $expires;

    /**
     * @ORM\OneToOne(targetEntity="Basket", inversedBy="validation", cascade={"persist"})
     * @ORM\JoinColumn(name="basket_id", referencedColumnName="id")
     */
    private $basket;

    /**
     * @ORM\OneToMany(targetEntity="ValidationParticipant", mappedBy="session", cascade={"all"})
     */
    private $participants;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  integer           $initiatorId
     * 
     * @return ValidationSession
     */
    public function setInitiatorId($initiatorId)
    {
        $this->initiator_id = $initiatorId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getInitiatorId()
    {
        return $this->initiator_id;
    }

    /**
     * @param \User_Adapter $user
     * 
     * @return boolean
     */
    public function isInitiator(\User_Adapter $user)
    {
        return $this->getInitiatorId() == $user->get_id();
    }

    /**
     * @return ValidationSession
     */
    public function setInitiator(\User_Adapter $user)
    {
        $this->initiator_id = $user->get_id();

        return $this;
    }

    /**
     * @param Application $app
     * @return \User_Adapter
     */
    public function getInitiator(Application $app)
    {
        if ($this->initiator_id) {
            return \User_Adapter::getInstance($this->initiator_id, $app);
        }
    }

    /**
     * @param  \DateTime         $created
     * 
     * @return ValidationSession
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param  \DateTime         $updated
     * 
     * @return ValidationSession
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param  \DateTime         $expires
     * 
     * @return ValidationSession
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * @param  Basket  $basket
     * 
     * @return ValidationSession
     */
    public function setBasket(Basket $basket = null)
    {
        $this->basket = $basket;

        return $this;
    }

    /**
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @param  ValidationParticipant $participants
     * 
     * @return ValidationSession
     */
    public function addParticipant(ValidationParticipant $participants)
    {
        $this->participants[] = $participants;

        return $this;
    }

    /**
     * @param ValidationParticipant $participants
     */
    public function removeParticipant(ValidationParticipant $participants)
    {
        $this->participants->removeElement($participants);
    }

    /**
     * @return ValidationParticipant[]
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * @return boolean
     */
    public function isFinished()
    {
        if (is_null($this->getExpires())) {
            return false;
        }

        $date_obj = new \DateTime();

        return $date_obj > $this->getExpires();
    }

    /**
     * Returns the appropriate validation sentence.
     * 
     * @param Application $app
     * @param \User_Adapter $user
     * 
     * @return string
     */
    public function getValidationString(Application $app, \User_Adapter $user)
    {
        if ($this->isInitiator($user)) {
            if ($this->isFinished()) {
                return sprintf(
                        _('Vous aviez envoye cette demande a %d utilisateurs')
                        , (count($this->getParticipants()) - 1)
                );
            }
            
            return sprintf(
                    _('Vous avez envoye cette demande a %d utilisateurs')
                    , (count($this->getParticipants()) - 1)
            );
        }
        
        if ($this->getParticipant($user, $app)->getCanSeeOthers()) {
            return sprintf(
                    _('Processus de validation recu de %s et concernant %d utilisateurs')
                    , $this->getInitiator($app)->get_display_name()
                    , (count($this->getParticipants()) - 1));
        }

        return sprintf(
                _('Processus de validation recu de %s')
                , $this->getInitiator($app)->get_display_name()
        );
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

        throw new NotFoundHttpException('Particpant not found' . $user->get_email());
    }
}
