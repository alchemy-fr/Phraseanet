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
use record_adapter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $share_expires;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $wip;

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
        $this->elements     = new ArrayCollection();
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
     * return true  if the user is participant and is aware
     *        false if the user is participant and is not aware
     *        null  if the user is not particiapant
     *
     * used to display an "unread" flag near basket (use along with isRead() method)
     *
     * @param User $user
     * @return bool|null
     */
    public function isAwareByUserParticipant(User $user)
    {
        if($this->isParticipant($user)) {
            $now = new DateTime();
            if(!$this->getParticipant($user)->getIsAware()) {
                if (is_null($this->share_expires)
                    || $now < $this->share_expires
                    || (!is_null($this->vote_expires) && $now < $this->vote_expires)
                ) {
                    // unread
                    return false;
                }
                return true;
            }
            return true;
        }
        return null;
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
     * @return User
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
     * @param User|null $user
     *
     * @return $this
     */
    public function setVoteInitiator($user)
    {
        $this->vote_initiator = $user;
        $this->upadteVoteDates();

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
     * @param  DateTime|null         $expires
     * @return self
     */
    public function setVoteExpires($expires)
    {
        $this->vote_expires = $expires;
        $this->upadteVoteDates();

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
     * Set share expires
     *
     * @param  DateTime|null         $expires
     * @return self
     */
    public function setShareExpires($expires)
    {
        $this->share_expires = $expires;
        $this->upadteVoteDates();

        return $this;
    }

    /**
     * Get share expires
     *
     * @return DateTime|null
     */
    public function getShareExpires()
    {
        return $this->share_expires;
    }

    /**
     * @return DateTime|null
     */
    public function getWip()
    {
        return $this->wip;
    }

    /**
     * @param DateTime|null $wip
     */
    public function setWip($wip)
    {
        $this->wip = $wip;
    }



    /**
     * for every method that touch a "vote" data : maintain created/updated
     */
    private function upadteVoteDates()
    {
        $now = new DateTime();
        if(is_null($this->getVoteCreated())) {
            $this->setVoteCreated($now);
        }
        $this->setVoteUpdated($now);
    }

    /**
     * Add element
     *
     * @param  BasketElement $element
     * @return self
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
     * @param  User $participant
     * @return BasketParticipant
     */
    public function addParticipant(User $participant)
    {
        $bp = new BasketParticipant($participant);
        $bp->setBasket($this);
        $this->participants[] = $bp;

        return $bp;
    }

    /**
     * Remove participant
     *
     * @param BasketParticipant $participant
     * @return bool
     */
    public function removeParticipant(BasketParticipant $participant)
    {
        if ($this->participants->removeElement($participant)) {
            // $participant->setBasket();

            return true;
        }

        return false;
    }

    /**
     * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     * todo : implement getVoteParticipants (or accept a filter)
     * Get participants
     *
     * @return Collection|BasketParticipant[]
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Check if a user is a participant
     *
     * @param User $user
     * @return bool
     */
    public function isParticipant(User $user)
    {
        /** @var BasketParticipant $participant */
        foreach ($this->getParticipants() as $participant) {
            if ($participant->getUser()->getId() == $user->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param User $user
     *
     * @return boolean
     */
    public function isVoteInitiator(User $user)
    {
        if($this->getVoteInitiator() !== null) {
            return $this->getVoteInitiator()->getId() == $user->getId();
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

    /**
     * Get a participant
     *
     * @param User $user
     *
     * @return BasketParticipant
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
     * Set order
     *
     * @param  Order  $order
     * @return self
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
        $i = 1;
        /** @var BasketElement $element */
        foreach ($this->elements as $element) {
            // display the right ord in screen
            if ($element->getOrd() !== $i) {
                $element->setOrd($i);
            }
            $i++;
        }

        return $this->elements;
    }

    /**
     * start a vote session on this basket
     * !!!!!!!!!!!!!!!!!!!!! used only by RegenerateSqlite ? !!!!!!!!!!!!!!!
     *
     * @param User $initiator
     * @return self
     */
    public function startVoteSession(User $initiator)
    {
        $now = new DateTime();
        $this->setVoteInitiator($initiator)
            ->setVoteCreated($now)
            ->setVoteUpdated($now);

        return $this;
    }

    /**
     * @param string $order one of self::ELEMENTSORDER_* const
     * @return ArrayCollection|BasketElement[]
     */
    public function getElementsByOrder(string $order)
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

        foreach ($element1->getVotes() as $vote) {
            if ($vote->getAgreement() !== null) {
                $total_el1 += $vote->getAgreement() ? 1 : 0;
            }
        }
        foreach ($element2->getVotes() as $vote) {
            if ($vote->getAgreement() !== null) {
                $total_el2 += $vote->getAgreement() ? 1 : 0;
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

        foreach ($element1->getVotes() as $vote) {
            if ($vote->getAgreement() !== null) {
                $total_el1 += $vote->getAgreement() ? 0 : 1;
            }
        }
        foreach ($element2->getVotes() as $vote) {
            if ($vote->getAgreement() !== null) {
                $total_el2 += $vote->getAgreement() ? 0 : 1;
            }
        }

        if ($total_el1 === $total_el2) {
            return 0;
        }

        return $total_el1 < $total_el2 ? 1 : -1;
    }

    public function hasRecord(Application $app, record_adapter $record)
    {
        return !is_null($this->getElementByRecord($app, $record));
    }

    /**
     * @param Application $app
     * @param record_adapter $record
     * @return BasketElement
     */
    public function getElementByRecord(Application $app, record_adapter $record)
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

    /**
     * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! easy way to tell if a basket "is" a vote (former = has "validation")
     * @return bool
     */
    public function isVoteBasket()
    {
        return !!$this->getVoteInitiator();
    }

    /**
     * /!\ returns null if the basket has no voteExpires date
     *
     * @return bool|null
     */
    public function isVoteFinished()
    {
        if (is_null($this->getVoteExpires())) {
            return null;
        }

        $date_obj = new DateTime();

        return $date_obj > $this->getVoteExpires();
    }

    /**
     * returns null if the basket has no shareExpires date
     *
     * @return bool|null
     * @throws \Exception
     */
    public function isShareExpires()
    {
        if (is_null($this->getShareExpires())) {
            return null;
        }

        $date_obj = new DateTime();

        return $date_obj > $this->getShareExpires();
    }

    public function getVoteString(Application $app, User $user)
    {
        if ($this->isVoteInitiator($user)) {
            if ($this->isVoteFinished()) {
                return $app->trans('Vous aviez envoye cette demande a %n% utilisateurs', ['%n%' => count($this->getParticipants()) - 1]);
            }

            return $app->trans('Vous avez envoye cette demande a %n% utilisateurs', ['%n%' => count($this->getParticipants()) - 1]);
        }
        else {
            if ($this->getParticipant($user)->getCanSeeOthers()) {
                return $app->trans('Processus de validation recu de %user% et concernant %n% utilisateurs', ['%user%' => $this->getVoteInitiator()->getDisplayName(), '%n%' => count($this->getParticipants()) - 1]);
            }

            return $app->trans('Processus de validation recu de %user%', ['%user%' => $this->getVoteInitiator()->getDisplayName()]);
        }
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
