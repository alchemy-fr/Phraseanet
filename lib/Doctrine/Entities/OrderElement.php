<?php

namespace Entities;

use Alchemy\Phrasea\Application;
use Doctrine\ORM\Mapping as ORM;
use Entities\Order;

/**
 * @ORM\Table(name="OrderElements", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_ordercle", columns={"base_id","record_id","order_id"})
 * })
 * @ORM\Entity(repositoryClass="Repositories\OrderElementRepository")
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
     * @ORM\Column(type="integer", nullable=true, name="order_master_id")
     */
    private $orderMasterId;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $denied;

    /**
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="elements", cascade={"persist"})
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    private $order;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $orderMasterId
     *
     * @return OrderElement
     */
    public function setOrderMasterId($orderMasterId)
    {
        $this->orderMasterId = $orderMasterId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getOrderMasterId()
    {
        return $this->orderMasterId;
    }

    /**
     * @param Application $app
     *
     * @return string or null if order master does not exists
     */
    public function getOrderMasterName(Application $app)
    {
        if (isset($this->orderMasterId) && null !== $this->orderMasterId) {
            $user = \User_Adapter::getInstance($this->orderMasterId, $app);

            return $user->get_firstname();
        }

        return null;
    }

    /**
     * @param boolean $denied
     *
     * @return OrderElement
     */
    public function setDenied($denied)
    {
        $this->denied = (Boolean) $denied;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDenied()
    {
        return $this->denied;
    }

    /**
     * @param Order $order
     *
     * @return OrderElement
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
     * @param integer $baseId
     *
     * @return OrderElement
     */
    public function setBaseId($baseId)
    {
        $this->baseId = $baseId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getBaseId()
    {
        return $this->baseId;
    }

    /**
     * @param integer $recordId
     *
     * @return OrderElement
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * @param Application $app
     *
     * @return \record_adapter
     */
    public function getRecord(Application $app)
    {
        return new \record_adapter($app, $this->getSbasId($app), $this->getRecordId());
    }

    /**
     * @param Application $app
     *
     * @return integer
     */
    public function getSbasId(Application $app)
    {
        return \phrasea::sbasFromBas($app, $this->getBaseId());
    }
}
