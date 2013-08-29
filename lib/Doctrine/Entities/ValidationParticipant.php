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

/**
 * @ORM\Table(name="ValidationParticipants")
 * @ORM\Entity(repositoryClass="Repositories\ValidationParticipantRepository")
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
     * @ORM\Column(type="integer")
     */
    private $usr_id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_aware = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_confirmed = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $can_agree = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $can_see_others = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $reminded;

    /**
     * @ORM\OneToMany(targetEntity="ValidationData", mappedBy="participant", cascade={"all"})
     */
    private $datas;

    /**
     * @ORM\ManyToOne(targetEntity="ValidationSession", inversedBy="participants", cascade={"persist"})
     * @ORM\JoinColumn(name="ValidationSession_id", referencedColumnName="id")
     */
    private $session;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->datas = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  integer               $usrId
     * 
     * @return ValidationParticipant
     */
    public function setUsrId($usrId)
    {
        $this->usr_id = $usrId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usr_id;
    }

    /**
     * @param  \User_Adapter         $user
     * 
     * @return ValidationParticipant
     */
    public function setUser(\User_Adapter $user)
    {
        $this->usr_id = $user->get_id();

        return $this;
    }

    /**
     * @param Application $app
     * 
     * @return \User_Adapter
     */
    public function getUser(Application $app)
    {
        return \User_Adapter::getInstance($this->getUsrId(), $app);
    }

    /**
     * @param  boolean               $isAware
     * 
     * @return ValidationParticipant
     */
    public function setIsAware($isAware)
    {
        $this->is_aware = $isAware;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsAware()
    {
        return $this->is_aware;
    }

    /**
     * @param  boolean               $isConfirmed
     * 
     * @return ValidationParticipant
     */
    public function setIsConfirmed($isConfirmed)
    {
        $this->is_confirmed = $isConfirmed;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsConfirmed()
    {
        return $this->is_confirmed;
    }

    /**
     * @param  boolean               $canAgree
     * 
     * @return ValidationParticipant
     */
    public function setCanAgree($canAgree)
    {
        $this->can_agree = $canAgree;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getCanAgree()
    {
        return $this->can_agree;
    }

    /**
     * @param  boolean               $canSeeOthers
     * 
     * @return ValidationParticipant
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
     * @param  \DateTime             $reminded
     * 
     * @return ValidationParticipant
     */
    public function setReminded($reminded)
    {
        $this->reminded = $reminded;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getReminded()
    {
        return $this->reminded;
    }

    /**
     * @param  ValidationData $datas
     * 
     * @return ValidationParticipant
     */
    public function addData(ValidationData $datas)
    {
        $this->datas[] = $datas;

        return $this;
    }

    /**
     * @param ValidationData $datas
     */
    public function removeData(ValidationData $datas)
    {
        $this->datas->removeElement($datas);
    }

    /**
     * @return ValidationData[]
     */
    public function getDatas()
    {
        return $this->datas;
    }

    /**
     * @param  ValidationSession $session
     * 
     * @return ValidationParticipant
     */
    public function setSession(ValidationSession $session = null)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return ValidationSession
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Returns true if all data have been validated.
     * 
     * @return boolean
     */
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
