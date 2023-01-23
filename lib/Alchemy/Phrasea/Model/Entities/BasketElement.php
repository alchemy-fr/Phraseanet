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
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use record_adapter;

/**
 * @ORM\Table(name="BasketElements", uniqueConstraints={@ORM\UniqueConstraint(name="unique_recordcle", columns={"basket_id","sbas_id","record_id"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\BasketElementRepository")
 */
class BasketElement
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
    private $record_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $sbas_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $ord;

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
     * @ORM\OneToMany(targetEntity="BasketElementVote", mappedBy="basket_element", cascade={"all"})
     */
    private $votes;

    /**
     * @ORM\ManyToOne(targetEntity="Basket", inversedBy="elements", cascade={"persist"})
     * @ORM\JoinColumn(name="basket_id", referencedColumnName="id")
     */
    private $basket;

    /**
     * Constructor
     */
    public function __construct()
    {
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
     * Set record_id
     *
     * @param integer $recordId
     * @return BasketElement
     */
    public function setRecordId(int $recordId)
    {
        $this->record_id = $recordId;

        return $this;
    }

    /**
     * Get record_id
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->record_id;
    }

    /**
     * Set sbas_id
     *
     * @param integer $sbasId
     * @return self
     */
    public function setSbasId(int $sbasId)
    {
        $this->sbas_id = $sbasId;

        return $this;
    }

    /**
     * Get sbas_id
     *
     * @return integer
     */
    public function getSbasId()
    {
        return $this->sbas_id;
    }

    public function getRecord(Application $app)
    {
        return new record_adapter($app, $this->getSbasId(), $this->getRecordId(), $this->getOrd());
    }

    public function setRecord(record_adapter $record)
    {
        $this->setRecordId($record->getRecordId());
        $this->setSbasId($record->getDataboxId());
    }

    /**
     * Set ord
     *
     * @param integer $ord
     * @return self
     */
    public function setOrd(int $ord)
    {
        $this->ord = $ord;

        return $this;
    }

    /**
     * Get ord
     *
     * @return integer
     */
    public function getOrd()
    {
        return $this->ord;
    }

    /**
     * Set created
     *
     * @param  DateTime     $created
     * @return self
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
     * @param  DateTime     $updated
     * @return self
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
     * Add vote
     *
     * @param  BasketElementVote $vote
     * @return self
     */
    public function addVote(BasketElementVote $vote)
    {
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote
     *
     * @param BasketElementVote $vote
     */
    public function removeVote(BasketElementVote $vote)
    {
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes
     * @param false $includeUnVoted     true to include empty votes for all participants that haven't voted
     *
     * @return ArrayCollection|BasketElementVote[]
     */
    public function getVotes($includeUnVoted = false)
    {
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // hack : a participant+element may have no matching "vote" row
        // if the basket is a "vote", we fix this here
//        if($this->getBasket()->isVoteBasket()) {
//            /** @var BasketParticipant $participant */
//            foreach($this->getBasket()->getParticipants() as $participant) {
//                // don't call getUserVote() as it will call getVotes() ...
//                $found = false;
//                foreach ($this->votes as $vote) {
//                    if ($vote->getParticipant()->getId() == $participant->getId()) {
//                        $found = true;
//                        break;
//                    }
//                }
//                if(!$found) {
//                    $this->addVote($this->createVote($participant));
//                }
//            }
//        }

        if(!$includeUnVoted) {
            return $this->votes;
        }

        $votes = [];
        foreach($this->getBasket()->getParticipants() as $participant) {
            $participantId = $participant->getId();
            $vote = null;
            /** @var BasketElementVote $v */
            foreach ($this->votes as $v) {
                if($v->getParticipant()->getId() == $participantId) {
                    $vote = $v;
                    break;
                }
            }
            $votes[] = $vote ?: new BasketElementVote($participant, $this);
        }

        return $votes;
    }

    /**
     * Set basket
     *
     * @param  Basket        $basket
     * @return BasketElement
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
     * create a vote data for a participant on this element (unique)
     *
     * @param BasketParticipant $participant
     * @return BasketElementVote
     */
    public function createVote(BasketParticipant $participant)
    {
        $bev = new BasketElementVote($participant, $this);
        $participant->addVote($bev);

        return $bev;
    }

    /**
     * @param User $user
     * @param bool $createIfMissing
     * @return BasketElementVote
     * @throws Exception
     */
    public function getUserVote(User $user, bool $createIfMissing)
    {
        // ensure the user is a participant
        $participant = $this->getBasket()->getParticipant($user);
        $participantId = $participant->getId();

        foreach ($this->getVotes() as $vote) {
            if ($vote->getParticipant()->getId() == $participantId) {
                return $vote;
            }
        }
        if($createIfMissing) {
            return $this->createVote($participant);
        }

        throw new Exception('There is no such participant ' . $user->getEmail());
    }
}
