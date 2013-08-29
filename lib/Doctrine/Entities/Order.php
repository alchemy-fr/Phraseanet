<?php

namespace Entities;

use Alchemy\Phrasea\Application;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="Orders")
 * @ORM\Entity(repositoryClass="Repositories\OrderRepository")
 */
class Order
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
   private $id;

    /**
     * @ORM\Column(type="integer", name="usr_id")
     */
    private $usrId;

    /**
     * @ORM\Column(type="string", length=2048, name="order_usage")
     */
    private $orderUsage;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $todo;

    /**
     * @ORM\Column(type="datetime")
     */
    private $deadline;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity="OrderElement", mappedBy="order", cascade={"ALL"})
     */
    private $elements;

    /**
     * @ORM\OneToOne(targetEntity="Basket", inversedBy="order", cascade={"ALL"})
     */
    private $basket;

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
     * @param integer $usrId
     *
     * @return Order
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
     * @param \DateTime $deadline
     *
     * @return Order
     */
    public function setDeadline(\DateTime $deadline)
    {
        $this->deadline = $deadline;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDeadline()
    {
        return $this->deadline;
    }

    /**
     * @param \DateTime $created
     *
     * @return Order
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
     * @param OrderElement $elements
     *
     * @return Order
     */
    public function addElement(OrderElement $elements)
    {
        $this->elements[] = $elements;

        return $this;
    }

    /**
     * @param OrderElement $elements
     */
    public function removeElement(OrderElement $elements)
    {
        $this->elements->removeElement($elements);
    }

    /**
     * @return OrderElement[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * @param Application $app
     *
     * @return \User_Adapter
     */
    public function getUser(Application $app)
    {
        if ($this->getUsrId()) {
            return \User_Adapter::getInstance($this->getUsrId(), $app);
        }
    }

    /**
     * @return Order
     */
    public function setTodo($todo)
    {
        $this->todo = $todo;

        return $this;
    }

    /**
     * @return integer
     */
    public function getTodo()
    {
        return $this->todo;
    }

    /**
     * @return integer
     */
    public function getTotal()
    {
        return count($this->elements);
    }

    /**
     * @param string $orderUsage
     *
     * @return Order
     */
    public function setOrderUsage($orderUsage)
    {
        $this->orderUsage = $orderUsage;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrderUsage()
    {
        return $this->orderUsage;
    }

    /**
     * @param Basket $basket
     *
     * @return Order
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
}
