<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Entities;

use Alchemy\Phrasea\Application;
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="initiator_id", referencedColumnName="id", nullable=false)
     *
     * @return User
     **/
    private $initiator;

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
     * @param User $user
     *
     * @return $this
     */
    public function setInitiator(User $user)
    {
        $this->initiator = $user;

        return $this;
    }

    /**
     * Get validation initiator
     *
     * @return User
     */
    public function getInitiator()
    {
        return $this->initiator;
    }

    /**
     * @param User $user
     *
     * @return boolean
     */
    public function isInitiator(User $user)
    {
        return $this->getInitiator()->getId() == $user->getId();
    }

    /**
     * Set created
     *
     * @param  \DateTime         $created
     * @return ValidationSession
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param  \DateTime         $updated
     * @return ValidationSession
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set expires
     *
     * @param  \DateTime         $expires
     * @return ValidationSession
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * Get expires
     *
     * @return \DateTime
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * Set basket
     *
     * @param  Basket            $basket
     * @return ValidationSession
     */
    public function setBasket(Basket $basket = null)
    {
        $this->basket = $basket;

        return $this;
    }

    /**
     * Get basket
     *
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * Add participants
     *
     * @param  ValidationParticipant $participants
     * @return ValidationSession
     */
    public function addParticipant(ValidationParticipant $participants)
    {
        $this->participants[] = $participants;

        return $this;
    }

    /**
     * Remove participants
     *
     * @param ValidationParticipant $participants
     */
    public function removeParticipant(ValidationParticipant $participants)
    {
        $this->participants->removeElement($participants);
    }

    /**
     * Get participants
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    public function isFinished()
    {
        if (is_null($this->getExpires())) {
            return null;
        }

        $date_obj = new \DateTime();

        return $date_obj > $this->getExpires();
    }

    public function getValidationString(Application $app, User $user)
    {
        if ($this->isInitiator($user)) {
            if ($this->isFinished()) {
                return $app->trans('Vous aviez envoye cette demande a %n% utilisateurs', ['%n%' => count($this->getParticipants()) - 1]);
            } else {
                return $app->trans('Vous avez envoye cette demande a %n% utilisateurs', ['%n%' => count($this->getParticipants()) - 1]);
            }
        } else {
            if ($this->getParticipant($user)->getCanSeeOthers()) {
                return $app->trans('Processus de validation recu de %user% et concernant %n% utilisateurs', ['%user%' => $this->getInitiator($app)->getDisplayName(), '%n%' => count($this->getParticipants()) - 1]);
            } else {
                return $app->trans('Processus de validation recu de %user%', ['%user%' => $this->getInitiator($app)->getDisplayName()]);
            }
        }
    }

    /**
     * Get a participant
     *
     * @return ValidationParticipant
     */
    public function getParticipant(User $user)
    {
        foreach ($this->getParticipants() as $participant) {
            if ($participant->getUser()->getId() == $user->getId()) {
                return $participant;
            }
        }

        throw new NotFoundHttpException('Participant not found' . $user->getEmail());
    }
}
