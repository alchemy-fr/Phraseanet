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

/**
 * @ORM\Table(name="Baskets")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\BasketRepository")
 */
class Basket
{
    const ELEMENTSORDER_NAT = 'nat';
    const ELEMENTSORDER_DESC = 'desc';
    const ELEMENTSORDER_ASC = 'asc';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @return User
     **/
    private $user;

    /**
     * @ORM\Column(name="is_read", type="boolean", options={"default" = 0})
     */
    private $isRead = false;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="pusher_id", referencedColumnName="id")
     *
     * @return User
     **/
    private $pusher;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $archived = false;

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
     * @ORM\OneToOne(targetEntity="ValidationSession", mappedBy="basket", cascade={"ALL"})
     */
    private $validation;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="vote_initiator_id", referencedColumnName="id", nullable=true)
     *
     * @return User
     **/
    private $vote_initiator;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $vote_created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $vote_updated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $vote_expires;

    /**
     * @ORM\OneToMany(targetEntity="BasketElement", mappedBy="basket", cascade={"ALL"})
     * @ORM\OrderBy({"ord" = "ASC"})
     */
    private $elements;

    /**
     * @ORM\OneToMany(targetEntity="BasketParticipant", mappedBy="basket", cascade={"ALL"})
     */
    private $participants;

    /**
     * @ORM\OneToOne(targetEntity="Order", mappedBy="basket", cascade={"ALL"})
     */
    private $order;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->elements = new ArrayCollection();
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
     * @param  string $name
     * @return Basket
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
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
     * @param  string $description
     * @return Basket
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param User $user
     *
     * @return Basket
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

    public function markRead()
    {
        $this->isRead = true;

        return $this;
    }

    public function markUnread()
    {
        $this->isRead = false;

        return $this;
    }

    public function isRead()
    {
        return $this->isRead;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setPusher(User $user = null)
    {
        $this->pusher = $user;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPusher()
    {
        return $this->pusher;
    }

    /**
     * Set archived
     *
     * @param  boolean $archived
     * @return Basket
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
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
     * @param  \DateTime $created
     * @return Basket
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
     * @param  \DateTime $updated
     * @return Basket
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
     * Set validation
     *
     * @param  ValidationSession $validation
     * @return Basket
     */
    public function setValidation(ValidationSession $validation = null)
    {
        $this->validation = $validation;

        return $this;
    }

    /**
     * Get validation
     *
     * @return ValidationSession
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setVoteInitiator(User $user)
    {
        $this->vote_initiator = $user;

        return $this;
    }

    /**
     * Get vote initiator
     *
     * @return User
     */
    public function getVoteInitiator()
    {
        return $this->vote_initiator;
    }

    /**
     * Set vote created
     *
     * @param  DateTime         $created
     * @return self
     */
    public function setVoteCreated(DateTime $created)
    {
        $this->vote_created = $created;

        return $this;
    }

    /**
     * Get vote created
     *
     * @return DateTime
     */
    public function getVoteCreated()
    {
        return $this->vote_created;
    }

    /**
     * Set vote updated
     *
     * @param  DateTime         $updated
     * @return self
     */
    public function setVoteUpdated(DateTime $updated)
    {
        $this->vote_updated = $updated;

        return $this;
    }

    /**
     * Get vote updated
     *
     * @return DateTime
     */
    public function getVoteUpdated()
    {
        return $this->vote_updated;
    }

    /**
     * Set vote expires
     *
     * @param  DateTime         $expires
     * @return self
     */
    public function setExpires($expires)
    {
        $this->vote_expires = $expires;

        return $this;
    }

    /**
     * Get vote expires
     *
     * @return DateTime|null
     */
    public function getVoteExpires()
    {
        return $this->vote_expires;
    }

    /**
     * Add element
     *
     * @param  BasketElement $element
     * @return Basket
     */
    public function addElement(BasketElement $element)
    {
        $this->elements[] = $element;
        $element->setBasket($this);
        $element->setOrd(count($this->elements));

        return $this;
    }

    /**
     * Remove element
     *
     * @param BasketElement $element
     * @return bool
     */
    public function removeElement(BasketElement $element)
    {
        if ($this->elements->removeElement($element)) {
            $element->setBasket();

            return true;
        }

        return false;
    }

    /**
     * Add participant
     *
     * @param  BasketParticipant $participant
     * @return Basket
     */
    public function addParticipant(BasketParticipant $participant)
    {
        $this->participants[] = $participant;
        $participant->setBasket($this);

        return $this;
    }

    /**
     * Remove participant
     *
     * @param BasketParticipant $participant
     * @return bool
     */
    public function removeParticipant(BasketParticipant $participant)
    {
        if ($this->elements->removeElement($participant)) {
            $participant->setBasket();

            return true;
        }

        return false;
    }

    /**
     * Set order
     *
     * @param  Order  $order
     * @return Basket
     */
    public function setOrder(Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Get elements
     *
     * @return Collection|BasketElement[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * @param string $order one of self::ELEMENTSORDER_* const
     * @return ArrayCollection|BasketElement[]
     */
    public function getElementsByOrder($order)
    {
        $elements = $this->elements->toArray();

        switch ($order)
        {
            case self::ELEMENTSORDER_DESC:
                uasort($elements, 'self::setBEOrderDESC');
                break;
            case self::ELEMENTSORDER_ASC:
                uasort($elements, 'self::setBEOrderASC');
                break;
        }

        return new ArrayCollection($elements);
    }

    /**
     * @param BasketElement $element1
     * @param BasketElement $element2
     * @return int
     */
    private static function setBEOrderDESC(BasketElement $element1, BasketElement $element2)
    {
        $total_el1 = 0;
        $total_el2 = 0;

        foreach ($element1->getValidationDatas() as $data) {
            if ($data->getAgreement() !== null) {
                $total_el1 += $data->getAgreement() ? 1 : 0;
            }
        }
        foreach ($element2->getValidationDatas() as $data) {
            if ($data->getAgreement() !== null) {
                $total_el2 += $data->getAgreement() ? 1 : 0;
            }
        }

        if ($total_el1 === $total_el2) {
            return 0;
        }

        return $total_el1 < $total_el2 ? 1 : -1;
    }

    /**
     * @param BasketElement $element1
     * @param BasketElement $element2
     * @return int
     */
    private static function setBEOrderASC(BasketElement $element1, BasketElement $element2)
    {
        $total_el1 = 0;
        $total_el2 = 0;

        foreach ($element1->getValidationDatas() as $data) {
            if ($data->getAgreement() !== null) {
                $total_el1 += $data->getAgreement() ? 0 : 1;
            }
        }
        foreach ($element2->getValidationDatas() as $data) {
            if ($data->getAgreement() !== null) {
                $total_el2 += $data->getAgreement() ? 0 : 1;
            }
        }

        if ($total_el1 === $total_el2) {
            return 0;
        }

        return $total_el1 < $total_el2 ? 1 : -1;
    }

    public function hasRecord(Application $app, \record_adapter $record)
    {
        return !is_null($this->getElementByRecord($app, $record));
    }

    /**
     * @param Application $app
     * @param \record_adapter $record
     * @return BasketElement
     */
    public function getElementByRecord(Application $app, \record_adapter $record)
    {
        foreach ($this->getElements() as $basket_element) {
            $bask_record = $basket_element->getRecord($app);

            if ($bask_record->getRecordId() == $record->getRecordId()
                && $bask_record->getDataboxId() == $record->getDataboxId()) {
                return $basket_element;
            }
        }

        return null;
    }

    public function getSize(Application $app)
    {
        $totSize = 0;

        foreach ($this->getElements() as $basket_element) {
            try {
                $totSize += $basket_element->getRecord($app)
                    ->get_subdef('document')
                    ->get_size();
            } catch (\Exception $e) {

            }
        }

        $totSize = round($totSize / (1024 * 1024), 2);

        return $totSize;
    }
}
