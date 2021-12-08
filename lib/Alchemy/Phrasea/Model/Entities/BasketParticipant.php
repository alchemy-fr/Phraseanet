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
 * @ORM\Table(name="BasketParticipants", uniqueConstraints={@ORM\UniqueConstraint(name="unique_participant", columns={"basket_id","user_id"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\BasketParticipantRepository")
 */
class BasketParticipant
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
    private $can_modify = false;

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
     * @ORM\OneToMany(targetEntity="ValidationData", mappedBy="participant", cascade={"all"})
     */
    private $datas;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @return User
     **/
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Basket")
     * @ORM\JoinColumn(name="basket_id", referencedColumnName="id", nullable=false)
     *
     * @return Basket
     **/
    private $basket;




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
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

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
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @param Basket $basket
     *
     * @return self
     */
    public function setBasket(Basket $basket = null)
    {
        $this->basket = $basket;

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
     * Set is_aware
     *
     * @param  boolean               $isAware
     * @return self
     */
    public function setIsAware($isAware)
    {
        $this->is_aware = $isAware;

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
     * Get can_see_others
     *
     * @return boolean
     */
    public function getCanSeeOthers()
    {
        return $this->can_see_others;
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
     * Get reminded
     *
     * @return DateTime
     */
    public function getReminded()
    {
        return $this->reminded;
    }

    /**
     * Set reminded
     *
     * @param  DateTime             $reminded
     * @return self
     */
    public function setReminded($reminded)
    {
        $this->reminded = $reminded;

        return $this;
    }

    /**
     * Add datas
     *
     * @param  ValidationData        $datas
     * @return self
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
     * Set is_confirmed
     *
     * @param  boolean               $isConfirmed
     * @return self
     */
    public function setIsConfirmed($isConfirmed)
    {
        $this->is_confirmed = $isConfirmed;

        return $this;
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
}
