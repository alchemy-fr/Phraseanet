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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="Baskets")
 * @ORM\Entity(repositoryClass="Repositories\BasketRepository")
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
     * @ORM\Column(type="integer", name="usr_id")
     */
    private $usrId;

    /**
     * @ORM\Column(type="boolean")
     */
    private $read = false;

    /**
     * @ORM\Column(type="integer", name="pusher_id", nullable=true)
     */
    private $pusherId;

    /**
     * @ORM\Column(type="boolean")
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
     * @ORM\OneToMany(targetEntity="BasketElement", mappedBy="basket", cascade={"ALL"})
     * @ORM\OrderBy({"ord" = "ASC"})
     */
    private $elements;

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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return Basket
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $description
     *
     * @return Basket
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param integer $usrId
     *
     * @return Basket
     */
    public function setUsrId($usrId)
    {
        $this->usrId = $usrId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usrId;
    }

    /**
     * @param \User_Adapter $user
     */
    public function setOwner(\User_Adapter $user)
    {
        $this->setUsrId($user->get_id());
    }

    /**
     * @param Application $app
     *
     * @return \User_Adapter or null
     */
    public function getOwner(Application $app)
    {
        if ($this->getUsrId()) {
            return \User_Adapter::getInstance($this->getUsrId(), $app);
        }
    }

    /**
     * @param boolean $read
     *
     * @return Basket
     */
    public function setRead($read)
    {
        $this->read = (Boolean) $read;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isRead()
    {
        return $this->read;
    }

    /**
     * @param integer $pusherId
     *
     * @return Basket
     */
    public function setPusherId($pusherId)
    {
        $this->pusherId = $pusherId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPusherId()
    {
        return $this->pusherId;
    }

    /**
     * @param \User_Adapter $user
     *
     * @return Basket
     */
    public function setPusher(\User_Adapter $user)
    {
        $this->setPusherId($user->get_id());

        return $this;
    }

    /**
     * @param Application $app
     *
     * @return \User_Adapter or null
     */
    public function getPusher(Application $app)
    {
        if ($this->getPusherId()) {
            return \User_Adapter::getInstance($this->getPusherId(), $app);
        }
    }

    /**
     * @param boolean $archived
     *
     * @return Basket
     */
    public function setArchived($archived)
    {
        $this->archived = (Boolean) $archived;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isArchived()
    {
        return $this->archived;
    }

    /**
     * @param \DateTime $created
     *
     * @return Basket
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
     * @param \DateTime $updated
     *
     * @return Basket
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
     * @param ValidationSession $validation
     *
     * @return Basket
     */
    public function setValidation(ValidationSession $validation = null)
    {
        $this->validation = $validation;

        return $this;
    }

    /**
     * @return ValidationSession
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * @param  BasketElement $elements
     * @return Basket
     */
    public function addElement(BasketElement $elements)
    {
        $this->elements[] = $elements;

        return $this;
    }

    /**
     * @param BasketElement $elements
     */
    public function removeElement(BasketElement $elements)
    {
        $this->elements->removeElement($elements);
    }

    /**
     * @param Order $order
     *
     * @return Basket
     */
    public function setOrder(Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return BasketElement[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Returns basket elements collections ordered by given sort option.
     *
     * @param type $ordre
     *
     * @return BasketElement[]
     */
    public function getElementsByOrder($ordre)
    {
        if ($ordre === self::ELEMENTSORDER_DESC) {
            $ret = new ArrayCollection();
            $elements = $this->elements->toArray();

            uasort($elements, 'self::setBEOrderDESC');

            foreach ($elements as $elem) {
                $ret->add($elem);
            }

            return $ret;
        } elseif ($ordre === self::ELEMENTSORDER_ASC) {
            $ret = new ArrayCollection();
            $elements = $this->elements->toArray();

            uasort($elements, 'self::setBEOrderASC');

            foreach ($elements as $elem) {
                $ret->add($elem);
            }

            return $ret;
        }

        return $this->elements;
    }

    /**
     * Sort desc algorithme function.
     *
     * @param BasketElement $element1
     * @param BasketElement $element2
     *
     * @return int
     */
    private static function setBEOrderDESC($element1, $element2)
    {
        $total_el1 = 0;
        $total_el2 = 0;

        foreach ($element1->getValidationDatas() as $datas) {
            if ($datas->getAgreement() !== null) {
                $total_el1 += $datas->getAgreement() ? 1 : 0;
            }
        }
        foreach ($element2->getValidationDatas() as $datas) {
            if ($datas->getAgreement() !== null) {
                $total_el2 += $datas->getAgreement() ? 1 : 0;
            }
        }

        if ($total_el1 === $total_el2) {
            return 0;
        }

        return $total_el1 < $total_el2 ? 1 : -1;
    }

    /**
     * Sort asc algorithme function.
     *
     * @param BasketElement $element1
     * @param BasketElement $element2
     *
     * @return int
     */
    private static function setBEOrderASC($element1, $element2)
    {
        $total_el1 = 0;
        $total_el2 = 0;

        foreach ($element1->getValidationDatas() as $datas) {
            if ($datas->getAgreement() !== null) {
                $total_el1 += $datas->getAgreement() ? 0 : 1;
            }
        }
        foreach ($element2->getValidationDatas() as $datas) {
            if ($datas->getAgreement() !== null) {
                $total_el2 += $datas->getAgreement() ? 0 : 1;
            }
        }

        if ($total_el1 === $total_el2) {
            return 0;
        }

        return $total_el1 < $total_el2 ? 1 : -1;
    }

    /**
     * Returns true if basket contains given record.
     *
     * @param Application     $app
     * @param \record_adapter $record
     *
     * @return boolean
     */
    public function hasRecord(Application $app, \record_adapter $record)
    {
        foreach ($this->getElements() as $basket_element) {
            $bask_record = $basket_element->getRecord($app);

            if ($bask_record->get_record_id() == $record->get_record_id()
                && $bask_record->get_sbas_id() == $record->get_sbas_id()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the total document size of basket elements.
     *
     * @param Application $app
     *
     * @return integer
     */
    public function getSize(Application $app)
    {
        $totSize = 0;

        foreach ($this->getElements() as $basketElement) {
            try {
                $totSize += $basketElement->getRecord($app)
                    ->get_subdef('document')
                    ->get_size();
            } catch (Exception $e) {

            }
        }

        return round($totSize / (1024 * 1024), 2);
    }
}
