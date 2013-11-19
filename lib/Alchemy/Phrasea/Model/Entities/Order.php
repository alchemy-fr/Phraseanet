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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="Orders")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\OrderRepository")
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *
     * @return User
     **/
    private $user;

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
     * @ORM\Column(type="datetime", name="created_on")
     */
    private $createdOn;

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
     * @param User $user
     *
     * @return Order
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set deadline
     *
     * @param  \DateTime $deadline
     * @return Order
     */
    public function setDeadline($deadline)
    {
        $this->deadline = $deadline;

        return $this;
    }

    /**
     * Get deadline
     *
     * @return \DateTime
     */
    public function getDeadline()
    {
        return $this->deadline;
    }

    /**
     * Set created_on
     *
     * @param  \DateTime $createdOn
     * @return Order
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get created_on
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Add elements
     *
     * @param  OrderElement $elements
     * @return Order
     */
    public function addElement(OrderElement $elements)
    {
        $this->elements[] = $elements;

        return $this;
    }

    /**
     * Remove elements
     *
     * @param OrderElement $elements
     */
    public function removeElement(OrderElement $elements)
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

    /**
     * Set todo
     *
     * @param  integer $todo
     * @return Order
     */
    public function setTodo($todo)
    {
        $this->todo = $todo;

        return $this;
    }

    /**
     * Get todo
     *
     * @return integer
     */
    public function getTodo()
    {
        return $this->todo;
    }

    /**
     * Returns the total number of elements.
     *
     * @return integer
     */
    public function getTotal()
    {
        return count($this->elements);
    }

    /**
     * Set order_usage
     *
     * @param  string $orderUsage
     * @return Order
     */
    public function setOrderUsage($orderUsage)
    {
        $this->orderUsage = $orderUsage;

        return $this;
    }

    /**
     * Get order_usage
     *
     * @return string
     */
    public function getOrderUsage()
    {
        return $this->orderUsage;
    }

    /**
     * Set basket
     *
     * @param  Basket $basket
     * @return Order
     */
    public function setBasket(Basket $basket = null)
    {
        $this->basket = $basket;

        return $this;
    }

    /**
     * Get basket
     *
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }
}
