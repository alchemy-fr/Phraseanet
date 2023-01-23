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

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ValidationParticipants")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\ValidationParticipantRepository")
 */
class ValidationParticipant
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $is_aware = false;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $is_confirmed = false;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $can_agree = false;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $can_see_others = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $reminded;

    /**
     * @ORM\Column(type="integer", nullable=true, name="validation_session_id")
     */
    private $session;

    /**
     * ValidationParticipant constructor.
     */
    public function __construct()
    {
        $this->datas = new ArrayCollection();
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
     * @ORM\Column(type="integer", nullable=true, name="user_id")
     */
    private $user;

    /**
     * @param User $user
     *
     * @return self
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set is_aware
     *
     * @param  boolean               $isAware
     * @return ValidationParticipant
     */
    public function setIsAware($isAware)
    {
        $this->is_aware = $isAware;

        return $this;
    }

    /**
     * Get is_aware
     *
     * @return boolean
     */
    public function getIsAware()
    {
        return $this->is_aware;
    }

    /**
     * Set is_confirmed
     *
     * @param  boolean               $isConfirmed
     * @return ValidationParticipant
     */
    public function setIsConfirmed($isConfirmed)
    {
        $this->is_confirmed = $isConfirmed;

        return $this;
    }

    /**
     * Get is_confirmed
     *
     * @return boolean
     */
    public function getIsConfirmed()
    {
        return $this->is_confirmed;
    }

    /**
     * Set can_agree
     *
     * @param  boolean               $canAgree
     * @return self
     */
    public function setCanAgree($canAgree)
    {
        $this->can_agree = $canAgree;

        return $this;
    }

    /**
     * Get can_agree
     *
     * @return boolean
     */
    public function getCanAgree()
    {
        return $this->can_agree;
    }

    /**
     * Set can_see_others
     *
     * @param  boolean               $canSeeOthers
     * @return self
     */
    public function setCanSeeOthers($canSeeOthers)
    {
        $this->can_see_others = $canSeeOthers;

        return $this;
    }

    /**
     * Get can_see_others
     *
     * @return boolean
     */
    public function getCanSeeOthers()
    {
        return $this->can_see_others;
    }

    /**
     * Set reminded
     *
     * @param  DateTime             $reminded
     * @return ValidationParticipant
     */
    public function setReminded($reminded)
    {
        $this->reminded = $reminded;

        return $this;
    }

    /**
     * Get reminded
     *
     * @return DateTime
     */
    public function getReminded()
    {
        return $this->reminded;
    }

    /**
     * Add datas
     *
     * @param  ValidationData        $datas
     * @return ValidationParticipant
     */
    public function addData(ValidationData $datas)
    {
        $this->datas[] = $datas;

        return $this;
    }

    /**
     * Remove datas
     *
     * @param ValidationData $datas
     */
    public function removeData(ValidationData $datas)
    {
        $this->datas->removeElement($datas);
    }

    /**
     * Get datas
     *
     * @return Collection
     */
    public function getDatas()
    {
        return $this->datas;
    }

    /**
     * Set session
     *
     * @param  ValidationSession     $session
     * @return ValidationParticipant
     */
    public function setSession(ValidationSession $session = null)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     *
     * @return ValidationSession
     */
    public function getSession()
    {
        return $this->session;
    }

    public function isReleasable()
    {

        if ($this->getIsConfirmed()) {
            return false;
        }

        foreach ($this->getDatas() as $validation_data) {
            /* @var $validation_data ValidationData */
            if ($validation_data->getAgreement() === null) {
                return false;
            }
        }

        return true;
    }
}
