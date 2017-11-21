<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="Orders")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\OrderRepository")
 */
class Order
{

    const NOTIFY_MAIL = 'mail';

    const NOTIFY_WEBHOOK = 'webhook';

    const STATUS_TODO = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_NO_FILTER = 'no_filter';
    const STATUS_CURRENT_WEEK = 'current_week';
    const STATUS_PAST_WEEK = 'past_week';
    const STATUS_PAST_MONTH = 'past_month';
    const STATUS_BEFORE = 'before';
    const STATUS_AFTER = 'after';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @var User
     */
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
     * @var Basket|null
     */
    private $basket;

    /**
     * @ORM\Column(type="string", length=32, name="notification_method")
     */
    private $notificationMethod;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->elements = new ArrayCollection();
        $this->notificationMethod = self::NOTIFY_MAIL;
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
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Order
     */
    public function setUser(User $user)
    {
        $this->user = $user;

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
     * Get created_on
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
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
     * Add elements
     *
     * @param  OrderElement $elements
     * @return Order
     */
    public function addElement(OrderElement $elements)
    {
        $this->elements[] = $elements;
        $elements->setOrder($this);

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
        $elements->setOrder(null);
    }

    /**
     * Get elements
     *
     * @return OrderElement[]|\Doctrine\Common\Collections\Collection
     */
    public function getElements()
    {
        return $this->elements;
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
     * @param int $count
     */
    public function decrementTodo($count)
    {
        $this->todo -= $count;
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

    public function getTotalTreatedItems()
    {
        $count = 0;
        foreach($this->elements as $element) {
            if(!is_null($element->getDeny())) {
                $count++;
            }
        }
        return $count;
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
     * Get basket
     *
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * Set basket
     *
     * @param  Basket $basket
     * @return Order
     */
    public function setBasket(Basket $basket = null)
    {
        if ($this->basket) {
            $this->basket->setOrder(null);
        }

        $this->basket = $basket;

        if ($basket) {
            $basket->setOrder($this);
        }

        return $this;
    }

    /**
     * @return string The name of the notification method that will be used to handle this order's status change
     * notifications.
     */
    public function getNotificationMethod()
    {
        return $this->notificationMethod;
    }

    /**
     * Sets the name of the notification method to handle this order's status change
     * notifications.
     * @param string $methodName
     * @return void
     */
    public function setNotificationMethod($methodName)
    {
        if (trim($methodName) == '') {
            $methodName = self::NOTIFY_MAIL;
        }

        $this->notificationMethod = $methodName;
    }
}
