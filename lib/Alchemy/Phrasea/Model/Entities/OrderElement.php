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
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="OrderElements", uniqueConstraints={@ORM\UniqueConstraint(name="unique_ordercle", columns={"base_id","record_id","order_id"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\OrderElementRepository")
 */
class OrderElement
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="base_id")
     */
    private $baseId;

    /**
     * @ORM\Column(type="integer", name="record_id")
     */
    private $recordId;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="order_master", referencedColumnName="id")
     *
     * @var null|User
     **/
    private $orderMaster;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @var bool|null
     */
    private $deny;

    /**
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="elements", cascade={"persist"})
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    private $order;

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
     * @param User $user
     *
     * @return $this
     */
    public function setOrderMaster(User $user = null)
    {
        $this->orderMaster = $user;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getOrderMaster()
    {
        return $this->orderMaster;
    }

    /**
     * Set deny
     *
     * @param null|bool $deny
     * @return OrderElement
     */
    public function setDeny($deny)
    {
        $this->deny = $deny;

        return $this;
    }

    /**
     * Get deny
     *
     * @return bool|null
     */
    public function getDeny()
    {
        return $this->deny;
    }

    /**
     * Set order
     *
     * @param  Order        $order
     * @return OrderElement
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
     * Set base_id
     *
     * @param  integer      $baseId
     * @return OrderElement
     */
    public function setBaseId($baseId)
    {
        $this->baseId = $baseId;

        return $this;
    }

    /**
     * Get base_id
     *
     * @return integer
     */
    public function getBaseId()
    {
        return $this->baseId;
    }

    /**
     * Set record_id
     *
     * @param  integer      $recordId
     * @return OrderElement
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;

        return $this;
    }

    /**
     * Get record_id
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * Returns a record from the element's base_id and record_id
     *
     * @param  Application     $app
     * @return \record_adapter
     */
    public function getRecord(Application $app)
    {
        return new \record_adapter($app, $this->getSbasId($app), $this->getRecordId());
    }

    /**
     * Returns the matching sbasId
     *
     * @param  Application $app
     * @return int
     */
    public function getSbasId(Application $app)
    {
        return \phrasea::sbasFromBas($app, $this->getBaseId());
    }
}
