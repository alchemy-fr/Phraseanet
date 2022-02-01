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
 * @ORM\Table(name="ValidationDatas")
 * @ORM\Entity
 */
class ValidationData
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
     * @ORM\Column(type="integer", nullable=true, name="participant_id")
     */
    private $participant;

    /**
     * @ORM\Column(type="integer", nullable=true, name="basket_element_id")
     */
    private $basket_element;

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
     * Set agreement
     *
     * @param  boolean        $agreement
     * @return ValidationData
     */
    public function setAgreement($agreement)
    {
        $this->agreement = $agreement;

        return $this;
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
     * Set note
     *
     * @param  string         $note
     * @return ValidationData
     */
    public function setNote($note)
    {
        $this->note = $note;

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
     * Set updated
     *
     * @param  \DateTime      $updated
     * @return ValidationData
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
     * Set participant
     *
     * @param  ValidationParticipant $participant
     * @return ValidationData
     */
    public function setParticipant(ValidationParticipant $participant = null)
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get participant
     *
     * @return ValidationParticipant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Set basket_element
     *
     * @param  BasketElement  $basketElement
     * @return ValidationData
     */
    public function setBasketElement(BasketElement $basketElement = null)
    {
        $this->basket_element = $basketElement;

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
}
