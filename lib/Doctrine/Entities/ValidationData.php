<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ValidationData
{
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var boolean $agreement
     */
    protected $agreement;

    /**
     * @var text $note
     */
    protected $note;

    /**
     * @var datetime $updated
     */
    protected $updated;

    /**
     * @var Entities\ValidationParticipant
     */
    protected $participant;

    /**
     * @var Entities\BasketElement
     */
    protected $basket_element;

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
     * @param boolean $agreement
     */
    public function setAgreement($agreement)
    {
        $this->agreement = $agreement;
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
     * @param text $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * Get note
     *
     * @return text
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set updated
     *
     * @param datetime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * Get updated
     *
     * @return datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set participant
     *
     * @param Entities\ValidationParticipant $participant
     */
    public function setParticipant(\Entities\ValidationParticipant $participant)
    {
        $this->participant = $participant;
    }

    /**
     * Get participant
     *
     * @return Entities\ValidationParticipant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Set basket_element
     *
     * @param Entities\BasketElement $basketElement
     */
    public function setBasketElement(\Entities\BasketElement $basketElement)
    {
        $this->basket_element = $basketElement;
    }

    /**
     * Get basket_element
     *
     * @return Entities\BasketElement
     */
    public function getBasketElement()
    {
        return $this->basket_element;
    }
}
