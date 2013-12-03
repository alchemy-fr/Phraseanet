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

/**
 * ValidationData
 */
class ValidationData
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var boolean
     */
    private $agreement;

    /**
     * @var string
     */
    private $note;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var \Entities\ValidationParticipant
     */
    private $participant;

    /**
     * @var \Entities\BasketElement
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
    public function setUpdated($updated)
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
     * @param  \Entities\ValidationParticipant $participant
     * @return ValidationData
     */
    public function setParticipant(\Entities\ValidationParticipant $participant = null)
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get participant
     *
     * @return \Entities\ValidationParticipant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Set basket_element
     *
     * @param  \Entities\BasketElement $basketElement
     * @return ValidationData
     */
    public function setBasketElement(\Entities\BasketElement $basketElement = null)
    {
        $this->basket_element = $basketElement;

        return $this;
    }

    /**
     * Get basket_element
     *
     * @return \Entities\BasketElement
     */
    public function getBasketElement()
    {
        return $this->basket_element;
    }
}
