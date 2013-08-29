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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="BasketElements", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_recordcle", columns={"basket_id","sbas_id","record_id"})
 * })
 * @ORM\Entity(repositoryClass="Repositories\BasketElementRepository")
 * @ORM\HasLifecycleCallbacks
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
     * @ORM\OneToMany(targetEntity="ValidationData", mappedBy="basket_element", cascade={"all"})
     */
    private $validation_datas;

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
        $this->validation_datas = new ArrayCollection();
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
     * @param  integer       $recordId
     * @return BasketElement
     */
    public function setRecordId($recordId)
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
     * @param  integer       $sbasId
     * @return BasketElement
     */
    public function setSbasId($sbasId)
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

    /**
     * Returns the records associated to the basket element.
     * 
     * @param Application $app
     * 
     * @return \record_adapter
     */
    public function getRecord(Application $app)
    {
        return new \record_adapter($app, $this->getSbasId(), $this->getRecordId(), $this->getOrd());
    }

    /**
     * Associates the given record to the current basket element.
     * 
     * @param \record_adapter $record
     * 
     * @return BasketElement
     */
    public function setRecord(\record_adapter $record)
    {
        $this->setRecordId($record->get_record_id());
        $this->setSbasId($record->get_sbas_id());
        
        return $this;
    }

    /**
     * @param  integer       $ord
     * 
     * @return BasketElement
     */
    public function setOrd($ord)
    {
        $this->ord = $ord;

        return $this;
    }

    /**
     * @return integer
     */
    public function getOrd()
    {
        return $this->ord;
    }

    /**
     * @param  \DateTime     $created
     * 
     * @return BasketElement
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param  \DateTime     $updated
     * 
     * @return BasketElement
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
     * @param  ValidationData $validationDatas
     * 
     * @return BasketElement
     */
    public function addValidationData(ValidationData $validationDatas)
    {
        $this->validation_datas[] = $validationDatas;

        return $this;
    }

    /**
     * @param ValidationData $validationDatas
     * 
     * @return BasketElement
     */
    public function removeValidationData(ValidationData $validationDatas)
    {
        $this->validation_datas->removeElement($validationDatas);
        
        return $this;
    }

    /**
     * @return ValidationData[]
     */
    public function getValidationDatas()
    {
        return $this->validation_datas;
    }

    /**
     * @param  Basket $basket
     * 
     * @return BasketElement
     */
    public function setBasket(Basket $basket = null)
    {
        $this->basket = $basket;

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
     * Prepersist callback sets position of basket.
     * 
     * @ORM\PrePersist
     */
    public function setLastInBasket()
    {
        $this->setOrd($this->getBasket()->getElements()->count() + 1);
    }

    /**
     * @param  \User_Adapter            $user
     * 
     * @return ValidationData
     * 
     * @throws InvalidArgumentException if user is not found
     */
    public function getUserValidationDatas(\User_Adapter $user, Application $app)
    {
        foreach ($this->validation_datas as $validationData) {
            if ($validationData->getParticipant($app)->getUser($app)->get_id() == $user->get_id()) {
                return $validationData;
            }
        }

        throw new InvalidArgumentException(sprintf('There is no such participant %s.',  $user->get_email()));
    }
}
