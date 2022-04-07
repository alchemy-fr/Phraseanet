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

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="BasketElementVotes")
 * @ORM\Entity
 */
class BasketElementVote
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $agreement;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @ORM\ManyToOne(targetEntity="BasketParticipant", inversedBy="votes", cascade={"persist"})
     * @ORM\JoinColumn(name="participant_id", referencedColumnName="id")
     */
    private $participant;

    /**
     * @ORM\ManyToOne(targetEntity="BasketElement", inversedBy="votes", cascade={"persist"})
     * @ORM\JoinColumn(name="basket_element_id", referencedColumnName="id")
     */
    private $basket_element;

    public function __construct(BasketParticipant $participant, BasketElement $element)
    {
        $this
            ->setBasketElement($element)
            ->setParticipant($participant);
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
     * Get agreement
     *
     * @return boolean
     */
    public function getAgreement()
    {
        return $this->agreement;
    }

    /**
     * Set agreement
     *
     * @param  boolean        $agreement
     * @return self
     */
    public function setAgreement($agreement)
    {
        $this->agreement = $agreement;

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set note
     *
     * @param  string         $note
     * @return self
     */
    public function setNote($note)
    {
        $this->note = $note;

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
     * Set updated
     *
     * @param  \DateTime      $updated
     * @return self
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get participant
     *
     * @return BasketParticipant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Set participant
     *
     * @param  BasketParticipant $participant
     * @return self
     */
    private function setParticipant(BasketParticipant $participant = null)
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get basket_element
     *
     * @return BasketElement
     */
    public function getBasketElement()
    {
        return $this->basket_element;
    }

    /**
     * Set basket_element
     *
     * @param  BasketElement  $basketElement
     * @return self
     */
    private function setBasketElement(BasketElement $basketElement = null)
    {
        $this->basket_element = $basketElement;

        return $this;
    }
}
