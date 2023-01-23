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
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @ORM\Table(name="ValidationSessions")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\ValidationSessionRepository")
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
     * @ORM\Column(type="integer", nullable=true, name="initiator_id")
     */
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
     * @ORM\Column(type="integer", nullable=true, name="basket_id")
     */
    private $basket;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->participants = new ArrayCollection();
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
     * @param  DateTime         $created
     * @return ValidationSession
     */
    public function setCreated(DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param  DateTime         $updated
     * @return ValidationSession
     */
    public function setUpdated(DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set expires
     *
     * @param  DateTime         $expires
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
     * @return DateTime|null
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
     * @return Collection
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

        $date_obj = new DateTime();

        return $date_obj > $this->getExpires();
    }

    public function getValidationString(Application $app, User $user)
    {
        if ($this->isInitiator($user)) {
            if ($this->isFinished()) {
                return $app->trans('Vous aviez envoye cette demande a %n% utilisateurs', ['%n%' => count($this->getParticipants()) - 1]);
            }

            return $app->trans('Vous avez envoye cette demande a %n% utilisateurs', ['%n%' => count($this->getParticipants()) - 1]);
        } else {
            if ($this->getParticipant($user)->getCanSeeOthers()) {
                return $app->trans('Processus de validation recu de %user% et concernant %n% utilisateurs', ['%user%' => $this->getInitiator()->getDisplayName(), '%n%' => count($this->getParticipants()) - 1]);
            }

            return $app->trans('Processus de validation recu de %user%', ['%user%' => $this->getInitiator()->getDisplayName()]);
        }
    }

    /**
     * Get a participant
     *
     * @param User $user
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

    /**
     * Check if an user is a participant
     *
     * @param User $user
     * @return bool
     */
    public function isParticipant(User $user)
    {
        foreach ($this->getParticipants() as $participant) {
            if ($participant->getUser()->getId() == $user->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of participant user Ids
     *
     * @return array
     */
    public function getListParticipantsUserId()
    {
        $userIds = [];
        foreach ($this->getParticipants() as $participant) {
            $userIds[] = $participant->getUser()->getId();
        }

        return $userIds;
    }
}
