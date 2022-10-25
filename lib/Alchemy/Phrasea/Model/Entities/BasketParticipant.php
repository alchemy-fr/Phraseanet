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
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

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
     * @ORM\OneToMany(targetEntity="BasketElementVote", mappedBy="participant", cascade={"all"})
     */
    private $votes;

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
     * BasketParticipant constructor.
     */
    public function __construct(User $user)
    {
        $this->setUser($user);
        $this->votes = new ArrayCollection();
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
    private function setUser(User $user)
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
    public function setBasket(Basket $basket)
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
     * @param boolean $isAware
     * @return self
     */
    public function setIsAware(bool $isAware)
    {
        $this->is_aware = $isAware;

        return $this;
    }

    /**
     * Get can_modify
     *
     * @return boolean
     */
    public function getCanModify()
    {
        return $this->can_modify;
    }

    /**
     * Set can_modify
     *
     * @param boolean $canModify
     * @return self
     */
    public function setCanModify(bool $canModify)
    {
        $this->can_modify = $canModify;

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
     * @param boolean $canAgree
     * @return self
     */
    public function setCanAgree(bool $canAgree)
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
     * @param boolean $canSeeOthers
     * @return self
     */
    public function setCanSeeOthers(bool $canSeeOthers)
    {
        $this->can_see_others = $canSeeOthers;

        return $this;
    }

    /**
     * Get reminded
     *
     * @return DateTime
     */
    public function getReminded(): DateTime
    {
        return $this->reminded;
    }

    /**
     * Set reminded
     *
     * @param DateTime $reminded
     * @return self
     */
    public function setReminded(DateTime $reminded)
    {
        $this->reminded = $reminded;

        return $this;
    }

    /**
     * Add vote
     *
     * @param  BasketElementVote $basketElementVote
     * @return self
     */
    public function addVote(BasketElementVote $basketElementVote)
    {
        $this->votes[] = $basketElementVote;

        return $this;
    }

    /**
     * Remove vote
     *
     * @param BasketElementVote $basketElementVote
     */
    public function removeVote(BasketElementVote $basketElementVote)
    {
        $this->votes->removeElement($basketElementVote);
    }

    public function isReleasable()
    {
        if ($this->getIsConfirmed()) {
            return false;
        }

        foreach ($this->getVotes() as $basketElementVote) {
            /* @var $basketElementVote BasketElementVote */
            if ($basketElementVote->getAgreement() === null) {
                return false;
            }
        }

        // check if participant not yet vote for all elements
        if (count($this->getBasket()->getElements()) !== count($this->getVotes())) {
            return false;
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
     * @param boolean $isConfirmed
     * @return self
     */
    public function setIsConfirmed(bool $isConfirmed)
    {
        $this->is_confirmed = $isConfirmed;

        return $this;
    }

    /**
     * Get datas
     *
     * @return ArrayCollection|PersistentCollection
     */
    public function getVotes()
    {
        return $this->votes;
    }
}
