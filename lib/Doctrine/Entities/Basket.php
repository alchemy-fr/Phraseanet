<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

use Alchemy\Phrasea\Application;

/**
 * Basket
 */
class Basket
{
    const ELEMENTSORDER_NAT = 'nat';
    const ELEMENTSORDER_DESC = 'desc';
    const ELEMENTSORDER_ASC = 'asc';

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var integer
     */
    private $usr_id;

    /**
     * @var boolean
     */
    private $is_read = false;

    /**
     * @var integer
     */
    private $pusher_id;

    /**
     * @var boolean
     */
    private $archived = false;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var \Entities\ValidationSession
     */
    private $validation;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $elements;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->elements = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set usr_id
     *
     * @param  integer $usrId
     * @return Basket
     */
    public function setUsrId($usrId)
    {
        $this->usr_id = $usrId;

        return $this;
    }

    /**
     * Get usr_id
     *
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usr_id;
    }

    public function setOwner(\User_Adapter $user)
    {
        $this->setUsrId($user->get_id());
    }

    public function getOwner(Application $app)
    {
        if ($this->getUsrId()) {
            return \User_Adapter::getInstance($this->getUsrId(), $app);
        }
    }

    /**
     * Set is_read
     *
     * @param  boolean $isRead
     * @return Basket
     */
    public function setIsRead($isRead)
    {
        $this->is_read = $isRead;

        return $this;
    }

    /**
     * Get is_read
     *
     * @return boolean
     */
    public function getIsRead()
    {
        return $this->is_read;
    }

    /**
     * Set pusher_id
     *
     * @param  integer $pusherId
     * @return Basket
     */
    public function setPusherId($pusherId)
    {
        $this->pusher_id = $pusherId;

        return $this;
    }

    /**
     * Get pusher_id
     *
     * @return integer
     */
    public function getPusherId()
    {
        return $this->pusher_id;
    }

    public function setPusher(\User_Adapter $user)
    {
        $this->setPusherId($user->get_id());
    }

    public function getPusher(Application $app)
    {
        if ($this->getPusherId()) {
            return \User_Adapter::getInstance($this->getPusherId(), $app);
        }
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
    public function setCreated($created)
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
     * Set validation
     *
     * @param  \Entities\ValidationSession $validation
     * @return Basket
     */
    public function setValidation(\Entities\ValidationSession $validation = null)
    {
        $this->validation = $validation;

        return $this;
    }

    /**
     * Get validation
     *
     * @return \Entities\ValidationSession
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * Add elements
     *
     * @param  \Entities\BasketElement $elements
     * @return Basket
     */
    public function addElement(\Entities\BasketElement $elements)
    {
        $this->elements[] = $elements;

        return $this;
    }

    /**
     * Remove elements
     *
     * @param \Entities\BasketElement $elements
     */
    public function removeElement(\Entities\BasketElement $elements)
    {
        $this->elements->removeElement($elements);
    }

    /**
     * Get elements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getElements()
    {
        return $this->elements;
    }

    public function getElementsByOrder($ordre)
    {
        if ($ordre === self::ELEMENTSORDER_DESC) {
            $ret = new \Doctrine\Common\Collections\ArrayCollection();
            $elements = $this->elements->toArray();

            uasort($elements, 'self::setBEOrderDESC');

            foreach ($elements as $elem) {
                $ret->add($elem);
            }

            return $ret;
        } elseif ($ordre === self::ELEMENTSORDER_ASC) {
            $ret = new \Doctrine\Common\Collections\ArrayCollection();
            $elements = $this->elements->toArray();

            uasort($elements, 'self::setBEOrderASC');

            foreach ($elements as $elem) {
                $ret->add($elem);
            }

            return $ret;
        }

        return $this->elements;
    }

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
