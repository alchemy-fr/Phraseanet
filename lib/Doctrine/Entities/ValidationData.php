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
     * @ORM\ManyToOne(targetEntity="ValidationParticipant", inversedBy="datas", cascade={"persist"})
     * @ORM\JoinColumn(name="participant_id", referencedColumnName="id")
     */
    private $participant;

    /**
     * @ORM\ManyToOne(targetEntity="BasketElement", inversedBy="validationDatas", cascade={"persist"})
     * @ORM\JoinColumn(name="basket_element_id", referencedColumnName="id")
     */
    private $basketElement;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  boolean        $agreement
     * 
     * @return ValidationData
     */
    public function setAgreement($agreement)
    {
        $this->agreement = (Boolean) $agreement;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getAgreement()
    {
        return $this->agreement;
    }

    /**
     * @param  string         $note
     * @return ValidationData
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param  \DateTime      $updated
     * 
     * @return ValidationData
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param  ValidationParticipant $participant
     * 
     * @return ValidationData
     */
    public function setParticipant(ValidationParticipant $participant = null)
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * @return ValidationParticipant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * @param  BasketElement $basketElement
     * 
     * @return ValidationData
     */
    public function setBasketElement(BasketElement $basketElement = null)
    {
        $this->basketElement = $basketElement;

        return $this;
    }

    /**
     * @return BasketElement
     */
    public function getBasketElement()
    {
        return $this->basketElement;
    }
}
